<?php

/**
 * @Model ProvisionedCredits
 */

namespace App\Models;

use HTR\System\ModelCRUD as CRUD;
use HTR\Helpers\Mensagem\Mensagem as msg;
use HTR\Helpers\Paginator\Paginator;
use Respect\Validation\Validator as v;
use App\Config\Configurations as cfg;
use App\Models\HistoricoCreditoProvisionadoModel;
use App\Helpers\Utils;

class CreditoProvisionadoModel extends CRUD
{

    protected $entidade = 'provisioned_credits';
    protected $id;
    protected $nome;
    protected $uasg;
    protected $indicativoNaval;
    protected $time;
    protected $resultadoPaginator;
    protected $navPaginator;

    /*
     * Método uaso para retornar todos os dados da tabela.
     */

    public function returnAll()
    {
        return $this->findAll();
    }

    public function paginator($pagina, $user)
    {
        $innerJoin = " AS credits INNER JOIN oms ON oms.id = credits.oms_id";

        $dados = [
            'select' => 'credits.*, oms.naval_indicative',
            'entidade' => $this->entidade . $innerJoin,
            'pagina' => $pagina,
            'maxResult' => 10,
            'orderBy' => ''
        ];

        if (!in_array($user['level'], ['ADMINISTRADOR', 'CONTROLADOR_OBTENCAO'])) {
            $dados['where'] = 'oms_id = :omsId ';
            $dados['bindValue'] = [':omsId' => $user['oms_id']];
        }

        $paginator = new Paginator($dados);
        $this->resultadoPaginator = $paginator->getResultado();
        $this->navPaginator = $paginator->getNaveBtn();
    }

    public function getResultadoPaginator()
    {
        return $this->resultadoPaginator;
    }

    public function getNavePaginator()
    {
        return $this->navPaginator;
    }

    public function findById($id)
    {
        $query = "" .
            " SELECT credits.*, oms.naval_indicative FROM {$this->entidade} AS credits " .
            " INNER JOIN oms ON oms.id = credits.oms_id " .
            " WHERE credits.id = :id ";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':id' => $id]);

        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function saldoComprometido($omId)
    {
        $stmt = $this->pdo->prepare(""
            . " SELECT SUM(`requests_items`.`quantity` * `requests_items`.`value`) as sum_value "
            . " FROM `requests_items` "
            . " INNER JOIN `requests` "
            . "     ON `requests_items`.`requests_id` = `requests`.`id` "
            . " WHERE "
            . "     `requests`.`status` IN ('PROVISIONADO','VERIFICADO') "
            . "     AND `requests`.`oms_id` = ?;");
        $stmt->execute([$omId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function findByOmId($omId)
    {
        $query = "" .
            " SELECT * FROM {$this->entidade} " .
            " WHERE oms_id = :id ";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':id' => $omId]);

        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function novoRegistro($user)
    {
        // Valida dados
        $this->validaAll($user);
        // Verifica se há registro igual
        $this->evitarDuplicidade();

        $dados = [
            'credit_note' => $this->getCreditNote(),
            'value' => $this->getValue(),
            'oms_id' => $this->getOmsId(),
            'created_at' => date('Y-m-d'),
            'updated_at' => date('Y-m-d')
        ];
        if (parent::novo($dados)) {
            $lastId = $this->pdo->lastInsertId();
            (new HistoricoCreditoProvisionadoModel())->novaTransacao($lastId, $this->getValue(), 'CREDITO', 'NOTA DE CREDITO INSERIDA');

            msg::showMsg('111', 'success');
        }
    }

    public function removerRegistro($id)
    {
        $query = "DELETE FROM historic_provisioned_credits WHERE provisioned_credits_id = :id";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':id' => $id]);

        if (parent::remover($id)) {
            header('Location: ' . cfg::DEFAULT_URI . 'creditoprovisionado/ver/');
        }
    }

    private function evitarDuplicidade()
    {
        /// Evita a duplicidade de registros
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->entidade} WHERE id != ? AND credit_note = ?");
        $stmt->bindValue(1, $this->getId());
        $stmt->bindValue(2, $this->getCreditNote());
        $stmt->execute();
        if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
            msg::showMsg('Já existe um registro com este Nome.<script>focusOn("credit_note")</script>', 'warning');
        }
    }

    private function validaAll($user)
    {
        // Seta todos os valores
        $this->setTime(time())
            ->setId(filter_input(INPUT_POST, 'id') ?? time())
            ->setCreditNote(filter_input(INPUT_POST, 'credit_note', FILTER_SANITIZE_SPECIAL_CHARS))
            ->setValue(filter_input(INPUT_POST, 'value', FILTER_SANITIZE_SPECIAL_CHARS));

        $value = str_replace(".", "", $this->getValue());
        $value = str_replace(",", ".", $value);
        $this->setValue($value);
        $omId = ($user['level'] != 'ADMINISTRADOR') ? $user['oms_id'] : filter_input(INPUT_POST, 'oms_id');
        $this->setOmsId($omId);

        // Inicia a Validação dos dados
        $this->validaId()
            ->validaCreditNote()
            ->validaValue();
    }

    private function validaId()
    {
        $value = v::intVal()->validate($this->getId());
        if (!$value) {
            msg::showMsg('O campo ID deve ser um número inteiro válido.', 'danger');
        }
        return $this;
    }

    private function validaCreditNote()
    {
        $value = v::stringType()->notEmpty()->validate($this->getCreditNote());
        if (!$value || !Utils::checkLength($this->getCreditNote(), 1, 30)) {
            msg::showMsg('O campo nota de crédito deve ser deve ser preenchido corretamente.'
                . '<script>focusOn("credit_note");</script>', 'danger');
        }
        return $this;
    }

    private function validaValue()
    {
        $value = str_replace(".", "", $this->getValue());
        $value = str_replace(",", ".", $value);

        $validate = v::floatVal()->notEmpty()->validate($value);
        if (!$validate) {
            msg::showMsg('O valor do campo VALOR deve ser preenchido corretamente', 'danger');
        }
        return $value;
    }
}
