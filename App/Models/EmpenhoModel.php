<?php

namespace App\Models;

use HTR\System\ModelCRUD as CRUD;
use HTR\Helpers\Mensagem\Mensagem as msg;
use HTR\Helpers\Paginator\Paginator;
use Respect\Validation\Validator as v;
use App\Config\Configurations as cfg;
use App\Models\EmpenhoItemsModel;
use App\Models\SolicitacaoModel;
use App\Helpers\Utils;
use App\Helpers\View;

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
        $select = 'invoices.*, oms.naval_indicative, ';
        $select .= ' (SELECT SUM(quantity * value) FROM invoices_items WHERE invoices_id = invoices.id) as total_requested, ';
        $select .= ' (SELECT SUM(delivered * value) FROM invoices_items WHERE invoices_id = invoices.id) as total_delivered';

        $dados = [
            'select' => $select,
            'entidade' => $this->entidade . $innerJoin,
            'pagina' => $pagina,
            'maxResult' => 100,
            'orderBy' => 'invoices.updated_at DESC'
        ];

        if (!in_array($user['level'], ['ADMINISTRADOR', 'CONTROLADOR_OBTENCAO', 'CONTROLADOR_FINANCA'])) {
            $dados['where'] = ' invoices.oms_id = :omsId ';
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

    public function novoRegistro($user)
    {
        // Valida dados
        $this->validaAll($user);

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

            foreach ($this->getRequests() as $requestId) {
                (new SolicitacaoModel())->processStatus($requestId, 'CONFERIDO', 'PROXIMO', $this->getUserId());
            }

            (new EmpenhoItemsModel())->novoRegistro($this->getRequests(), $lastId);

            msg::showMsg('Empenho realizado com sucesso!'
                . '<meta http-equiv="refresh" content="5;URL=' . cfg::DEFAULT_URI . 'empenho/" />'
                . '<script>setTimeout(function(){ window.location = "' . cfg::DEFAULT_URI . 'empenho/"; }, 2000); </script>', 'success');
        }
    }

    public function editarRegistro()
    {
        $this->setCode(filter_input(INPUT_POST, 'invoice_id', FILTER_SANITIZE_SPECIAL_CHARS));
        $this->setNewCode(filter_input(INPUT_POST, 'code_invoice', FILTER_SANITIZE_SPECIAL_CHARS));

        $result = $this->findByCode($this->getNewCode());

        if ($result) {
            header('Location: ' . cfg::DEFAULT_URI . 'empenho/');
            exit;
        }

        // atualizando o código do empenho
        $query = "" .
            " UPDATE invoices SET code = ? WHERE code = ? ";

        $stmt = $this->pdo->prepare($query);

        if ($stmt->execute([$this->getNewCode(), $this->getCode()])) {
            header('Location: ' . cfg::DEFAULT_URI . 'empenho/');
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

    public function retornaDadosPapeleta($id, $invoiceId, $user = null)
    {
        $where = '';
        if (isset($user['level']) && $user['level'] !== 'ADMINISTRADOR') {
            $where = ' AND oms.id = ' . $user['oms_id'];
        }

        $query = "
            SELECT 
                oms.*, oms.name AS oms_name,
                item.number AS requests_number,
                item.name, item.code, item.suppliers_id,
                item.uf, item.quantity,
                item.value, item.delivered,
                item.invoice, item.status, item.id, item.number,
                item.biddings_id, suppliers.name AS suppliers_name,
                suppliers.cnpj, biddings.number as biddingsNumber
            FROM invoices AS inv
                INNER JOIN requests_invoices AS item ON item.invoices_id = inv.id
                LEFT JOIN suppliers ON suppliers.id = item.suppliers_id
                INNER JOIN oms ON oms.id = inv.oms_id
                LEFT JOIN biddings ON biddings.id = item.biddings_id
            WHERE item.code = ? and invoices_id = ? {$where} ";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$id, $invoiceId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function cancelarEmpenho($user)
    {
        $this->setItemsList($this->buildItems(filter_input_array(INPUT_POST)));
        $this->setCode(filter_input(INPUT_POST, 'code_invoice', FILTER_SANITIZE_SPECIAL_CHARS));

        $empenhoResult = $this->findByCode($this->getCode());
        $creditoModel = new CreditoProvisionadoModel();
        $item = new ItemModel();
        $invoiceItem = new EmpenhoItemsModel();

        $creditoProvisionado = $creditoModel->findByOmId($empenhoResult['oms_id']);

        $total = 0;
        foreach ($this->getItemsList() as $idItem => $value) {
            $result = $invoiceItem->findById($idItem);

            if ($result['biddings_id'] == 0 && $result['number'] == 0) {
                $this->processaCancelamentoNaoLicitado($result, $value['quantidade'], $idItem);

                // somando o total dos itens cancelados
                $total += $value['quantidade'] * $result['value'];

                continue;
            }

            // atualizando a quantidade comprometida da licitação
            if ($value['quantidade'] > ($result['quantity'] - $result['delivered'])) {
                msg::showMsg('A quantidade para cancelar deve ser igual ou inferior a quantidade disponível', 'danger');
            }

            $produto = $item->findByNumberAnBiddings($result['number'], $result['biddings_id']);
            $item->cancelarQtdEmpenhada($produto['id'], $value['quantidade']);

            $this->atualizaQuantidaEmpenho($value['quantidade'], $result['quantity'], $idItem);

            // somando o total dos itens cancelados
            $total += $value['quantidade'] * $result['value'];
        }

        if ($total > 0) {
            // devolvendo o saldo do crédito provisionado
            (new HistoricoCreditoProvisionadoModel())->novaTransacao(
                $creditoProvisionado['id'],
                $total,
                'CREDITO',
                "CRÉDITO DE " . View::floatToMoney($total) . "; REFERENTE AO CANCELAMENTO DO EMPENHO " . $this->getCode()
            );
        }

        msg::showMsg('Cancelado os itens do empenho com sucesso!'
            . '<meta http-equiv="refresh" content="5;URL=' . cfg::DEFAULT_URI . 'empenho/" />'
            . '<script>setTimeout(function(){ window.location = "' . cfg::DEFAULT_URI . 'empenho/"; }, 2000); </script>', 'success');
    }

    private function atualizaQuantidaEmpenho($quantitySol, $quantityCurrent, $id)
    {
        $query = "" .
            " UPDATE invoices_items SET " .
            " quantity = ? " .
            " WHERE id = ? ";

        $stmt = $this->pdo->prepare($query);
        if ($stmt->execute([($quantityCurrent - $quantitySol), $id])) {
            return true;
        }
    }

    private function processaCancelamentoNaoLicitado($item, $quantitySol, $id)
    {
        // atualizando a quantidade comprometida da licitação
        if ($quantitySol > ($item['quantity'] - $item['delivered'])) {
            msg::showMsg('A quantidade para cancelar deve ser igual ou inferior a quantidade disponível', 'danger');
        }

        $this->atualizaQuantidaEmpenho($quantitySol, $item['quantity'], $id);

        $query = "" .
            " UPDATE requests_items SET quantity = quantity - ? " .
            " WHERE requests_id = ? and name LIKE ?";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$quantitySol, $item['requests_id'], $item['name']]);
    }

    /**
     * Make the itens of Biggings requests
     * @param array $values The input values
     * @return array
     */
    private function buildItems(array $values): array
    {
        $result = [];
        if (isset($values['quantity'], $values['ids']) && is_array($values['quantity'])) {
            foreach ($values['quantity'] as $index => $value) {
                $id = filter_var($values['ids'][$index], FILTER_VALIDATE_INT);
                $requested = filter_var(Utils::normalizeFloat($values['requested'][$index]), FILTER_VALIDATE_FLOAT);
                $delivered = filter_var(Utils::normalizeFloat($values['delivered'][$index]), FILTER_VALIDATE_FLOAT);
                $delived = filter_var(Utils::normalizeFloat($value), FILTER_VALIDATE_FLOAT);

                if ($id && $delived) {
                    $result[$id] = ['quantidade' => $delived, 'solicitada' => $requested, 'entregue' => $delivered];
                }
            }
        }

        return $result;
    }

    private function validaAll($user)
    {
        $value = filter_input_array(INPUT_POST);
        // Seta todos os valores
        $this->setId(filter_input(INPUT_POST, 'id') ?? time())
            ->setUserId($user['id'])
            ->setCode(filter_input(INPUT_POST, 'code_invoice', FILTER_SANITIZE_SPECIAL_CHARS))
            ->setComplement(filter_input(INPUT_POST, 'complement', FILTER_SANITIZE_SPECIAL_CHARS));

        $result = [];
        if (!empty($value['requests'])) {
            foreach ($value['requests'] as $value) {
                $id = filter_var($value, FILTER_VALIDATE_INT);
                $result[] = $id;
            }
        }
        $this->setRequests($result);

        // Inicia a Validação dos dados
        $this->validaId();
        $this->validaRequestsList();
        $this->validaRequest();
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

    private function validaRequestsList()
    {
        if (empty($this->getRequests())) {
            msg::showMsg('Para realizar registrar um empenho, é imprescindível'
                . ' selecionar no mínimo uma solicitação.', 'danger');
        }
        return $this;
    }

    private function validaRequest()
    {
        $request = [];
        foreach ($this->getRequests() as $requestId) {
            array_push($request, $requestId);
        }

        $query = "
            SELECT * FROM requests 
            WHERE id IN (" . implode(',', $request) . ") 
            GROUP BY suppliers_id, biddings_id, oms_id ";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $this->setOmsId($result[0]['oms_id']);

        if (count($result) > 1) {
            msg::showMsg('Verifique as solicitações selecionadas, ' .
                'só é possível empenhar mais de um pedido, caso a solicitação possua a mesma Licitação, ' .
                'Fornecedor e Organização Militar', 'danger');
        }
    }
}
