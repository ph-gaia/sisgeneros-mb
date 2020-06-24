<?php

namespace App\Models;

use HTR\System\ModelCRUD as CRUD;
use HTR\Helpers\Mensagem\Mensagem as msg;
use HTR\Helpers\Paginator\Paginator;
use Respect\Validation\Validator as v;
use App\Config\Configurations as cfg;
use App\Helpers\Utils;

class HistoricoCreditoProvisionadoModel extends CRUD
{

    protected $entidade = 'historic_provisioned_credits';
    protected $resultadoPaginator;
    protected $navPaginator;

    /*
     * Método uaso para retornar todos os dados da tabela.
     */

    public function returnAll()
    {
        return $this->findAll();
    }

    public function paginator($pagina, $id)
    {
        $dados = [
            'entidade' => $this->entidade,
            'pagina' => $pagina,
            'maxResult' => 10,
            'where' => 'provisioned_credits_id = :id',
            'bindValue' => [':id' => $id],
            'orderBy' => 'created_at DESC',
        ];

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

    public function novoRegistro()
    {
        // Valida dados
        $this->validaAll();

        $dados = [
            'operation_type' => $this->getOperationType(),
            'value' => $this->getValue(),
            'observation' => $this->getObservation(),
            'provisioned_credits_id' => $this->getProvisionedCredits(),
            'created_at' => date('Y-m-d')
        ];
        if (parent::novo($dados)) {
            $this->atualizaSaldoCredito($this->getProvisionedCredits());
            header('Location: ' . cfg::DEFAULT_URI . 'creditoprovisionado/detalhar/id/' . $this->getProvisionedCredits());
        }
    }

    public function novaTransacao($id, $value, $operation, $observation)
    {
        $dados = [
            'operation_type' => $operation,
            'value' => $value,
            'observation' => $observation,
            'provisioned_credits_id' => $id,
            'created_at' => date('Y-m-d')
        ];
        if (parent::novo($dados)) {
            $this->atualizaSaldoCredito($id);
            return true;
        }
    }

    private function atualizaSaldoCredito($id)
    {
        $query = "" .
            " SELECT " .
            " (SELECT SUM(value) FROM 
                historic_provisioned_credits 
                WHERE operation_type = 'CREDITO'
                and provisioned_credits_id = :id) as credito, " .
            " (SELECT SUM(value) FROM 
                historic_provisioned_credits 
                WHERE operation_type = 'DEBITO'
                and provisioned_credits_id = :id) as debito ";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        $total = $result['credito'] - $result['debito'];
        $query = " UPDATE provisioned_credits SET `value` = :total WHERE id = :id";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':total' => $total, ':id' => $id]);
    }

    private function validaAll()
    {
        // Seta todos os valores
        $this->setTime(time())
            ->setId(filter_input(INPUT_POST, 'id') ?? time())
            ->setOperationType(filter_input(INPUT_POST, 'operation_type', FILTER_SANITIZE_SPECIAL_CHARS))
            ->setObservation(mb_strtoupper(filter_input(INPUT_POST, 'observation', FILTER_SANITIZE_SPECIAL_CHARS)))
            ->setProvisionedCredits(filter_input(INPUT_POST, 'provisioned_credits_id'))
            ->setValue(filter_input(INPUT_POST, 'value', FILTER_SANITIZE_SPECIAL_CHARS));

        $this->setValue(Utils::moneyToFloat($this->getValue()));
        // Inicia a Validação dos dados
        $this->validaId()
            ->validaObservation()
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

    private function validaObservation()
    {
        $value = v::stringType()->notEmpty()->validate($this->getObservation());
        if (!$value || !Utils::checkLength($this->getObservation(), 1, 60)) {
            msg::showMsg('O campo observação deve ser deve ser preenchido corretamente.'
                . '<script>focusOn("observation");</script>', 'danger');
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
