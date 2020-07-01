<?php
namespace App\Models;

use HTR\System\ModelCRUD as CRUD;
use HTR\Helpers\Mensagem\Mensagem as msg;
use HTR\Helpers\Paginator\Paginator;
use Respect\Validation\Validator as v;
use App\Config\Configurations as cfg;
use App\Models\EmpenhoItemsModel;
use App\Helpers\Utils;

class EmpenhoModel extends CRUD
{

    protected $entidade = 'invoices';

    /**
     * @var \HTR\Helpers\Paginator\Paginator
     */
    protected $paginator;

    public function returnAll()
    {
        return $this->findAll();
    }

    public function paginator($pagina, $user, $busca = null)
    {
        $innerJoin = " INNER JOIN oms ON oms.id = invoices.oms_id ";

        $dados = [
            'select' => 'invoices.*, oms.naval_indicative',
            'entidade' => $this->entidade . $innerJoin,
            'pagina' => $pagina,
            'maxResult' => 100,
            'orderBy' => ''
        ];

        if (!in_array($user['level'], ['ADMINISTRADOR'])) {
            $dados['where'] = 'invoices.oms_id = :omsId ';
            $dados['bindValue'] = [':omsId' => $user['oms_id']];
        }

        if ($busca) {
            $dateInit = $dateEnd = $busca;

            if (preg_match('/\d{2}-\d{2}-\d{4}/', $busca)) {
                $dateEnd = Utils::dateDatabaseFormate($busca);
            }

            $andExists = isset($dados['where']) ? 'AND' : '';
            $dados['where'] = ($dados['where'] ?? "") . " {$andExists} ( "
                . ' invoices.status LIKE :search '
                . ' OR invoices.code LIKE :search '
                . ' OR oms.naval_indicative LIKE :search '
                . ' OR invoices.created_at BETWEEN :dInit AND :dEnd '
                . ' OR invoices.updated_at BETWEEN :dInit AND :dEnd '
                . ') ';

            $bindValue = [
                ':search' => '%' . $busca . '%',
                ':dInit' => $dateInit,
                ':dEnd' => $dateEnd
            ];
            $dados['bindValue'] = $dados['bindValue'] ?? [];
            $dados['bindValue'] = array_merge($dados['bindValue'], $bindValue);
        }

        $this->paginator = new Paginator($dados);
    }

    public function getResultadoPaginator()
    {
        return $this->paginator->getResultado();
    }

    public function getNavePaginator()
    {
        return $this->paginator->getNaveBtn();
    }

    /**
     * Select the Solicitation by Id Lista Field
     * @param int $requestId
     * @return array
     */
    public function findByIdlista($requestId)
    {
        $query = ""
            . " SELECT "
            . " invoices.*, oms.naval_indicative "
            . " FROM {$this->entidade} "
            . " INNER JOIN oms ON oms.id = invoices.oms_id "
            . " WHERE invoices.id = :requestId ";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':requestId' => $requestId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function novoRegistro($omsId)
    {
        // Valida dados
        $this->validaAll($omsId);

        $dados = [
            'oms_id' => $this->getOmsId(),
            'code' => $this->getCode(),
            'complement' => $this->getComplement(),
            'status' => 'EMPENHADO',
            'created_at' => date('Y-m-d'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if (parent::novo($dados)) {
            $lastId = $this->pdo->lastInsertId();
            (new EmpenhoItemsModel())->novoRegistro($this->getRequests(), $lastId);

            msg::showMsg('Empenho realizado com sucesso!'
                . '<meta http-equiv="refresh" content="5;URL=' . cfg::DEFAULT_URI . 'solicitacao/" />'
                . '<script>setTimeout(function(){ window.location = "' . cfg::DEFAULT_URI . 'solicitacao/"; }, 2000); </script>', 'success');
        }
    }

    public function atualizarStatus($id)
    {
        $dados = [
            'status' => 'PAGO-PARCIALMENTE',
            'updated_at' => date('Y-m-d H:i:s')
        ];

        parent::editar($dados, $id);
    }

    public function removerRegistro($id)
    {
        $result = $this->findInvoiceByRequestId($id);

        $query = ""
            . " DELETE "
            . " FROM invoices_has_requests "
            . " WHERE invoices_id = :invoicesId ";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':invoicesId' => $result['id']]);

        $query = ""
            . " DELETE "
            . " FROM {$this->entidade} "
            . " WHERE id = :invoicesId ";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':invoicesId' => $result['id']]);
    }

    private function validaAll($omsId)
    {
        $value = filter_input_array(INPUT_POST);
        // Seta todos os valores
        $this->setId(filter_input(INPUT_POST, 'id') ?? time())
            ->setOmsId($omsId)
            ->setCode(filter_input(INPUT_POST, 'code_invoice', FILTER_SANITIZE_SPECIAL_CHARS))
            ->setComplement(filter_input(INPUT_POST, 'complement', FILTER_SANITIZE_SPECIAL_CHARS));

        $result = [];
        foreach($value['requests'] as $value) {
            $id = filter_var($value, FILTER_VALIDATE_INT);
            $result[] = $id;
        }
        $this->setRequests($result);

        // Inicia a Validação dos dados
        $this->validaId();
    }

    // Validação
    private function validaId()
    {
        $value = v::intVal()->validate($this->getId());
        if (!$value) {
            msg::showMsg('O campo ID deve ser um número inteiro válido.', 'danger');
        }
        return $this;
    }
}
