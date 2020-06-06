<?php
namespace App\Models;

use HTR\System\ModelCRUD as CRUD;
use HTR\Helpers\Mensagem\Mensagem as msg;
use HTR\Helpers\Paginator\Paginator;
use Respect\Validation\Validator as v;
use App\Config\Configurations as cfg;
use App\Models\EmpenhoItemsModel;
use App\Models\CreditoProvisionadoModel;
use App\Models\SolicitacaoItemModel;
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
        $innerJoin .= " INNER JOIN provisioned_credits as credit ON credit.id = invoices.provisioned_credits_id ";
        $dados = [
            'select' => 'invoices.*, oms.naval_indicative, credit.credit_note, credit.value as credit',
            'entidade' => $this->entidade . $innerJoin,
            'pagina' => $pagina,
            'maxResult' => 100,
            'orderBy' => ''
        ];

        if (!in_array($user['level'], ['ADMINISTRADOR'])) {
            $dados['where'] = 'invoices.oms_id = :omsId ';
            $dados['bindValue'] = [':omsId' => $user['oms_id']];
        }

        if ($user['level'] === 'CONTROLADOR') {
            $dados['where'] = 'status != :status ';
            $dados['bindValue'] = [':status' => 'ABERTO'];
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
        $model = new SolicitacaoModel();

        foreach($this->getNumbers() as $value) {
            $requestId = $model->findByNumber($value);
            $this->verificaSaldoItem($requestId['id']);
        }

        $dados = [
            'oms_id' => $this->getOmsId(),
            'provisioned_credits_id' => $this->getProvisionedCredits(),
            'code' => $this->getCode(),
            'total' => $this->getTotal(),
            'status' => 'ABERTO',
            'created_at' => date('Y-m-d'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if (parent::novo($dados)) {
            $lastId = $this->pdo->lastInsertId();
            (new EmpenhoItemsModel())->novoRegistro($this->getNumbers(), $lastId);

            foreach($this->getNumbers() as $value) {
                //$requestId = $model->findByNumber($value);
                $model->processStatus($value, 'PROCESSADO', 'PROXIMO');
            }

            msg::showMsg('Empenho realizado com sucesso!'
                . '<meta http-equiv="refresh" content="5;URL=' . cfg::DEFAULT_URI . 'solicitacao/" />'
                . '<script>setTimeout(function(){ window.location = "' . cfg::DEFAULT_URI . 'solicitacao/"; }, 2000); </script>', 'success');
        }
    }

    public function verificaSaldoItem($idSolicitacao)
    {
        $solicitacao = (new SolicitacaoModel())->findById($idSolicitacao);
        $itens = (new SolicitacaoItemModel())->findAllItemsByRequestId($idSolicitacao);

        foreach ($itens as $item) {
            $demanda = (new SolicitacaoItemModel())->quantidadeDemanda($item['item_number'], $solicitacao['biddings_id']);
            $restante = $item['quantity'] - $demanda;

            if ($restante < (float) $item['quantidade_solicitada']) {
                msg::showMsg('O item ' . $item['name']
                .' da solicitação nº.' . $solicitacao['number']
                .', não possui saldo na licitação nº.' . $item['licitacao']
                .', por favor revise seu pedido!', 'danger');
            }
        }
        return true;
    }

    public function abaterValor($userId)
    {
        $id = filter_input(INPUT_POST, 'id');
        $value = filter_input(INPUT_POST, 'value');
        $observation = filter_input(INPUT_POST, 'observation');

        $empenho = $this->findById($id);
        if ($value >= $empenho['total']) {
            msg::showMsg('O valor informado é superior ou igual ao valor do empenho', 'danger');
        }

        $atual = $empenho['total'] - $value;

        $dados = [
            'total' => $atual
        ];

        if (parent::editar($dados, $id)) {
            (new CreditoProvisionadoModel())->adicionarValor($empenho['provisioned_credits_id'], $value);
            (new HistoricoEmpenhoModel())->novoRegistro($empenho['id'], $userId, $value, $observation);

            header('Location: ' . cfg::DEFAULT_URI . 'empenho/detalhar/idlista/'.$id);
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

    public function findTotalByNumberRequests($ids)
    {
        $query = ""
            . " SELECT SUM(quantity * value) as total "
            . " FROM requests "
            . " INNER JOIN "
            . "     requests_items ON "
            . " requests.id = requests_items.requests_id "
            . " WHERE requests.id IN (". implode(',', $ids) .");";

        return $this->pdo->query($query)->fetch((\PDO::FETCH_ASSOC));
    }

    public function findInvoiceByRequestId($id)
    {
        $query = ""
            . " SELECT invoices.* "
            . " FROM invoices_has_requests "
            . " INNER JOIN 
                    invoices 
                ON invoices.id = invoices_has_requests.invoices_id "
            . " WHERE invoices_has_requests.requests_id = $id ";

        return $this->pdo->query($query)->fetch((\PDO::FETCH_ASSOC));
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
        // Seta todos os valores
        $this->setId(filter_input(INPUT_POST, 'id') ?? time())
            ->setOmsId($omsId)
            ->setCode(filter_input(INPUT_POST, 'code_invoice', FILTER_SANITIZE_SPECIAL_CHARS))
            ->setNumbers(explode(',', filter_input(INPUT_POST, 'empenhos_id')));

        $creditoModel = new CreditoProvisionadoModel();
        $creditoProvisionado = $creditoModel->creditProvisionedByOmId($this->getOmsId());
        $this->setProvisionedCredits($creditoProvisionado['id']);

        $consulta = $this->findTotalByNumberRequests($this->getNumbers());
        $this->setTotal($consulta['total']);

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
