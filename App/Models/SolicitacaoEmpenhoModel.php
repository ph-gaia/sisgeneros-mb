<?php

namespace App\Models;

use HTR\Helpers\Mensagem\Mensagem as msg;
use Respect\Validation\Validator as v;
use App\Config\Configurations as cfg;
use HTR\Helpers\Paginator\Paginator;
use HTR\System\ModelCRUD as CRUD;
use App\Models\EmpenhoItemsModel;
use App\Helpers\Utils;
use HTR\System\ControllerAbstract;

class SolicitacaoEmpenhoModel extends CRUD
{

    protected $entidade = 'requests_invoices';

    /**
     * @var \HTR\Helpers\Paginator\Paginator
     */
    protected $paginator;

    public function paginator($pagina, $busca = null, $user = null)
    {
        $innerJoin = " as sol_inv INNER JOIN invoices as inv ON inv.id = sol_inv.invoices_id INNER JOIN oms ON oms.id = inv.oms_id ";
        $dados = [
            'select' => 'sol_inv.*, inv.code as codeInvoice, oms.naval_indicative',
            'entidade' => $this->entidade . $innerJoin,
            'pagina' => $pagina,
            'maxResult' => 10,
            'orderBy' => ' sol_inv.updated_at DESC '
        ];

        if (!in_array($user['level'], ['ADMINISTRADOR', 'CONTROLADOR_FINANCA'])) {
            $dados['where'] = " oms.id =  {$user['oms_id']} ";
        }

        if ($busca) {
            $andExists = isset($dados['where']) ? 'AND' : '';
            $dados['where'] = ($dados['where'] ?? "") . " {$andExists} "
                . ' sol_inv.status LIKE :search '
                . ' OR inv.code LIKE :search '
                . ' OR oms.naval_indicative LIKE :search '
                . ' OR sol_inv.code LIKE :search ';

            $bindValue = [
                ':search' => '%' . $busca . '%'
            ];
            $dados['bindValue'] = $bindValue;
        }

        $paginator = new Paginator($dados);
        $this->resultadoPaginator = $paginator->getResultado();
        $this->navPaginator = $paginator->getNaveBtn();
    }

    public function findIndicadorTemporResume(ControllerAbstract $controller)
    {
        $query = ""
            . " SELECT "
            . " DATEDIFF(req_inv.number_order_bank_date, req_inv.invoice_date) as date_diff, "
            . " count(req_inv.id) as qtd "
            . " FROM requests_invoices as req_inv "
            . " INNER JOIN invoices as inv ON inv.id = req_inv.invoices_id "
            . " WHERE req_inv.status = 'NF-PAGA' ";

        $params = $controller->getParametro();

        // search by Om
        if (isset($params['om']) && intval($params['om']) !== 0) {
            $query .= " AND inv.oms_id = {$params['om']} ";
        }

        // search by Date Init
        if (isset($params['dateInit']) && preg_match('/\d{2}-\d{2}-\d{4}/', $params['dateInit'])) {
            $date = Utils::dateDatabaseFormate($params['dateInit']);

            $query .= " AND req_inv.number_order_bank_date >= '{$date}' ";
        }

        // search by Date Init
        if (isset($params['dateEnd']) && preg_match('/\d{2}-\d{2}-\d{4}/', $params['dateEnd'])) {
            $date = Utils::dateDatabaseFormate($params['dateEnd']);

            $query .= " AND req_inv.number_order_bank_date <= '{$date}' ";
        }

        $query .= " GROUP BY req_inv.code ";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findIndicadorTempo(ControllerAbstract $controller)
    {
        $query = ""
            . " SELECT "
            . " req_inv.*, oms.naval_indicative "
            . " FROM requests_invoices as req_inv "
            . " INNER JOIN invoices as inv ON inv.id = req_inv.invoices_id "
            . " INNER JOIN oms ON oms.id = inv.oms_id "
            . " WHERE req_inv.status = 'NF-PAGA' ";

        $params = $controller->getParametro();
        $dados = [];

        // search by Om
        if (isset($params['om']) && intval($params['om']) !== 0) {
            $query .= " AND inv.oms_id = {$params['om']} ";
        }

        // search by Date Init
        if (isset($params['dateInit']) && preg_match('/\d{2}-\d{2}-\d{4}/', $params['dateInit'])) {
            $date = Utils::dateDatabaseFormate($params['dateInit']);

            $query .= " AND req_inv.number_order_bank_date >= '{$date}' ";
        }

        // search by Date Init
        if (isset($params['dateEnd']) && preg_match('/\d{2}-\d{2}-\d{4}/', $params['dateEnd'])) {
            $date = Utils::dateDatabaseFormate($params['dateEnd']);

            $query .= " AND req_inv.number_order_bank_date <= '{$date}' ";
        }

        $query .= " GROUP BY req_inv.code ";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findIndicadorEmissaoEntrega(ControllerAbstract $controller)
    {
        $query = ""
            . " SELECT "
            . " DATEDIFF(req_inv.date_delivery, req_inv.invoice_date_emission) as date_diff "
            . " FROM requests_invoices as req_inv "
            . " INNER JOIN invoices as inv ON inv.id = req_inv.invoices_id "
            . " INNER JOIN oms ON oms.id = inv.oms_id "
            . " WHERE req_inv.status in ('RECEBIDO', 'RECEBIDO-PARCIAL', 'NF-ENTREGUE','NF-LIQUIDADA', 'NF-PAGA') "
            . " AND req_inv.date_delivery IS NOT NULL AND req_inv.invoice_date_emission IS NOT NULL ";

        $params = $controller->getParametro();

        // search by Om
        if (isset($params['om']) && intval($params['om']) !== 0) {
            $query .= " AND inv.oms_id = {$params['om']} ";
        }

        // search by Date Init
        if (isset($params['dateInit']) && preg_match('/\d{2}-\d{2}-\d{4}/', $params['dateInit'])) {
            $date = Utils::dateDatabaseFormate($params['dateInit']);

            $query .= " AND req_inv.created_at >= '{$date}' ";
        }

        // search by Date Init
        if (isset($params['dateEnd']) && preg_match('/\d{2}-\d{2}-\d{4}/', $params['dateEnd'])) {
            $date = Utils::dateDatabaseFormate($params['dateEnd']);

            $query .= " AND req_inv.created_at <= '{$date}' ";
        }

        $query .= " GROUP BY req_inv.code ";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findNfPagasPorFornecedor(ControllerAbstract $controller)
    {
        $query = ""
            . " SELECT oms.*, oms.name AS oms_name, sup.*, "
            . " sup.name as supplier_name, SUM(req_inv.value) as total, req_inv.* "
            . " FROM requests_invoices as req_inv "
            . " INNER JOIN invoices as inv ON inv.id = req_inv.invoices_id "
            . " INNER JOIN oms ON oms.id = inv.oms_id "
            . " INNER JOIN suppliers as sup ON sup.id = req_inv.suppliers_id "
            . " WHERE req_inv.status LIKE 'NF-PAGA' ";

        $params = $controller->getParametro();

        // search by Om
        if (isset($params['om']) && intval($params['om']) !== 0) {
            $query .= " AND inv.oms_id = {$params['om']} ";
        }

        // search by Date Init
        if (isset($params['dateInit']) && preg_match('/\d{2}-\d{2}-\d{4}/', $params['dateInit'])) {
            $date = Utils::dateDatabaseFormate($params['dateInit']);

            $query .= " AND req_inv.created_at >= '{$date}' ";
        }

        // search by Date Init
        if (isset($params['dateEnd']) && preg_match('/\d{2}-\d{2}-\d{4}/', $params['dateEnd'])) {
            $date = Utils::dateDatabaseFormate($params['dateEnd']);

            $query .= " AND req_inv.created_at <= '{$date}' ";
        }

        $query .= " GROUP BY req_inv.code ORDER BY sup.id; ";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getResultadoPaginator()
    {
        return $this->resultadoPaginator;
    }

    public function getNavePaginator()
    {
        return $this->navPaginator;
    }

    public function findByRequestId($id)
    {
        $query = "" .
            " SELECT * FROM invoices_items " .
            " WHERE requests_id = :id ";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function findAllByInvoiceId($idLista)
    {
        $query = "" .
            " SELECT * FROM $this->entidade " .
            " WHERE invoices_id = :id " .
            " ORDER BY code, number ASC ";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':id' => $idLista]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function novoRegistro()
    {
        $this->validaAll();

        $invoicesId = 0;
        $solItem = new EmpenhoItemsModel();
        foreach ($this->getItemsList() as $idItem => $value) {
            $result = $solItem->findById($idItem);
            $invoicesId = $result['invoices_id'];
            $dados = [
                'invoices_id' => $result['invoices_id'],
                'suppliers_id' => $result['suppliers_id'],
                'biddings_id' => $result['biddings_id'],
                'code' => $this->getCode(),
                'number' => $result['number'],
                'name' => $result['name'],
                'uf' => $result['uf'],
                'quantity' => $value['quantidade'],
                'status' => 'SOLICITADO',
                'delivered' => 0,
                'value' => $result['value'],
                'created_at' => date('Y-m-d'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            parent::novo($dados);
        }

        msg::showMsg('Solicitação Registrada com Sucesso!<br>'
            . "<strong>Solicitação Nº {$this->getCode()} <br>"
            . "Status: SOLICITADO.</strong><br>"
            . '<meta http-equiv="refresh" content="3;URL=' . cfg::DEFAULT_URI . 'empenho/detalhar/idlista/' . $invoicesId . '" />'
            . '<script>setTimeout(function(){ window.location = "' . cfg::DEFAULT_URI . 'empenho/detalhar/idlista/' . $invoicesId . '"; }, 3000); </script>', 'success');
    }

    public function cancelarRegistro($id, $invoiceId)
    {
        $result = $this->findByCode($id);
        $stmt = $this->pdo->prepare("DELETE FROM {$this->entidade} WHERE code = ? and invoices_id = ?");
        if ($stmt->execute([$id, $invoiceId])) {
            header('Location: ' . cfg::DEFAULT_URI . 'empenho/detalhar/idlista/' . $result['invoices_id']);
        }
    }

    public function entregarNf()
    {
        $this->setPedidoId(filter_input(INPUT_POST, 'request_id'));
        $this->setEmpenhoId(filter_input(INPUT_POST, 'invoice_id'));
        $this->setInvoice(filter_input(INPUT_POST, 'nota_fiscal'));
        $this->setAction(filter_input(INPUT_POST, 'method_action'));
        $this->setDataDocumento($this->abstractDateValidate(filter_input(INPUT_POST, 'date_document')));
        $this->setDateEmission($this->abstractDateValidate(filter_input(INPUT_POST, 'date_document_emission')));
        $this->setDateDelivery($this->abstractDateValidate(filter_input(INPUT_POST, 'date_delivery')));

        $status = ($this->getAction() == 'add') ? " status = 'NF-ENTREGUE', " : "";
        $query = "
            UPDATE {$this->entidade} SET "
            . $status .
            "invoice_date = '" . date('Y-m-d', strtotime($this->getDataDocumento())) . "',
            invoice = '" . $this->getInvoice() . "',
            invoice_date_emission = '" . date('Y-m-d', strtotime($this->getDateEmission())) . "',
            date_delivery = '" . date('Y-m-d', strtotime($this->getDateDelivery())) . "',
            updated_at = '" . date('Y-m-d H:i:s') . "'
            WHERE code = ? and invoices_id = ?";

        $stmt = $this->pdo->prepare($query);
        if ($stmt->execute([$this->getPedidoId(), $this->getEmpenhoId()])) {
            header('Location: ' . cfg::DEFAULT_URI . 'empenho/solicitacoes');
        }
    }

    public function liquidarNf()
    {
        $this->setPedidoId(filter_input(INPUT_POST, 'request_id'));
        $this->setEmpenhoId(filter_input(INPUT_POST, 'invoice_id'));
        $this->setNumPedido(filter_input(INPUT_POST, 'number_request'));
        $this->setAction(filter_input(INPUT_POST, 'method_action'));
        $this->setDataDocumento($this->abstractDateValidate(filter_input(INPUT_POST, 'date_document')));


        $status = ($this->getAction() == 'add') ? " status = 'NF-LIQUIDADA', " : "";

        $query = "
            UPDATE {$this->entidade} SET "
            . $status .
            "number_request_date = '" . date('Y-m-d', strtotime($this->getDataDocumento())) . "',
            number_request = '" . $this->getNumPedido() . "', 
            updated_at = '" . date('Y-m-d H:i:s') . "'
            WHERE code = ? and invoices_id = ?";

        $stmt = $this->pdo->prepare($query);
        if ($stmt->execute([$this->getPedidoId(), $this->getEmpenhoId()])) {
            header('Location: ' . cfg::DEFAULT_URI . 'empenho/solicitacoes');
        }
    }

    public function pagarNf()
    {
        $this->setPedidoId(filter_input(INPUT_POST, 'request_id'));
        $this->setEmpenhoId(filter_input(INPUT_POST, 'invoice_id'));
        $this->setOrdemBancaria(filter_input(INPUT_POST, 'order_bank'));
        $this->setAction(filter_input(INPUT_POST, 'method_action'));
        $this->setDataDocumento($this->abstractDateValidate(filter_input(INPUT_POST, 'date_document')));

        $status = ($this->getAction() == 'add') ? " status = 'NF-PAGA', " : "";

        $query = "
            UPDATE {$this->entidade} SET "
            . $status .
            "number_order_bank_date = '" . date('Y-m-d', strtotime($this->getDataDocumento())) . "',
            number_order_bank = '" . $this->getOrdemBancaria() . "', 
            updated_at = '" . date('Y-m-d H:i:s') . "'
            WHERE code = ? and invoices_id = ?";

        $stmt = $this->pdo->prepare($query);
        if ($stmt->execute([$this->getPedidoId(), $this->getEmpenhoId()])) {
            header('Location: ' . cfg::DEFAULT_URI . 'empenho/solicitacoes');
        }
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

    public function recebimento()
    {
        $this->setId(filter_input(INPUT_POST, 'request_id'));
        $this->setEmpenhoId(filter_input(INPUT_POST, 'invoice_id'));
        $this->setInvoice(filter_input(INPUT_POST, 'nota_fiscal'));
        $this->setDateEmission($this->abstractDateValidate(filter_input(INPUT_POST, 'date_document_emission')));
        $this->setDateDelivery($this->abstractDateValidate(filter_input(INPUT_POST, 'date_delivery')));
        $this->validaInvoice($this->getInvoice());
        $this->setObservation(filter_input(INPUT_POST, 'observation', FILTER_SANITIZE_SPECIAL_CHARS));
        $this->setItemsList($this->buildItems(filter_input_array(INPUT_POST)));
        $this->validaItemsList();
        $val = $this->validaQtdRecebidaMenorQtdSol();

        $query = "
            UPDATE {$this->entidade} SET 
            status = '" . (($val) ? 'RECEBIDO-PARCIAL' : 'RECEBIDO') . "', 
            updated_at = '" . date('Y-m-d H:i:s') . "', 
            invoice = '" . $this->getInvoice() . "',
            invoice_date_emission = '" . date('Y-m-d', strtotime($this->getDateEmission())) . "',
            date_delivery = '" . date('Y-m-d', strtotime($this->getDateDelivery())) . "',
            observation = '" . $this->getObservation() . "' WHERE code = ? and invoices_id = ?";

        $stmt = $this->pdo->prepare($query);

        if ($stmt->execute([$this->getId(), $this->getEmpenhoId()])) {

            foreach ($this->getItemsList() as $id => $quantity) {
                $data = $this->findById($id);
                parent::editar(['delivered' => $data['delivered'] + $quantity['quantidade']], $id);
            }

            foreach ($this->getItemsList() as $id => $quantity) {
                $data = $this->findById($id);
                $query = " 
                    UPDATE invoices_items 
                    SET delivered = delivered + " . $quantity['quantidade'] . "
                    WHERE invoices_id = " . $data['invoices_id'] . " 
                    and number = " . $data['number'];
                $stmt = $this->pdo->prepare($query);
                $stmt->execute();
            }

            (new AvaliacaoFornecedorModel())->novoRegistro([
                'evaluation' => filter_input(INPUT_POST, 'evaluation', FILTER_VALIDATE_INT) ?: 3,
                'requests_id' => $this->getId()
            ]);

            msg::showMsg('Operação efetuada com sucesso!'
                . '<script>'
                . 'mostrar("btn_voltar");'
                . 'ocultar("tabela_result");'
                . 'ocultar("btn_enviar");'
                . 'ocultar("invoice_container");'
                . 'ocultar("observation");'
                . 'ocultar("evaluation");'
                . 'ocultar("legenda");'
                . '</script>', 'success');
        }
    }

    public function findQtdSolicitAtrasadas($user, $status = 'SOLICITADO')
    {
        $query = ""
            . " SELECT "
            . " COUNT(*) AS quantity "
            . " FROM invoices as req "
            . " INNER JOIN requests_invoices as items "
            . "  ON req.id = items.invoices_id "
            . " WHERE items.status LIKE :status";

        if (!in_array($user['level'], ['ADMINISTRADOR', 'CONTROLADOR_OBTENCAO'])) {
            $where = " AND req.oms_id = {$user['oms_id']} ";
            $query .= $where;
        }
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':status' => $status]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    private function validaAll()
    {
        $this->setItemsList($this->buildItems(filter_input_array(INPUT_POST)));
        $this->setCode($this->codeGenerator());

        $this->validaItemsList();
        $this->validaQtdSolMaiorQtdDisponivel();
    }

    private function validaQtdRecebidaMenorQtdSol()
    {
        foreach ($this->getItemsList() as $value) {
            if ($value['quantidade'] > $value['solicitada']) {
                msg::showMsg('Não é possível receber uma quantidade maior que a solicitada.', 'danger');
            }
            if (($value['quantidade'] - $value['entregue']) != $value['solicitada']) {
                return true;
            }
        }
        return false;
    }

    private function validaQtdSolMaiorQtdDisponivel()
    {
        $solItem = new EmpenhoItemsModel();
        foreach ($this->getItemsList() as $idItem => $value) {
            $result = $solItem->findById($idItem);
            $disponivel = floatval($result['quantity'] - ($result['delivered'] + $value['solicitada']));
            if ($value['quantidade'] > $disponivel) {
                msg::showMsg('A quantidade solicitada de ' . $result['name']
                    . ' é superior a quantidade disponível.', 'danger');
            }
        }
    }

    private function validaItemsList()
    {
        if (empty($this->getItemsList())) {
            msg::showMsg('Para realizar uma solicitação, é imprescindível'
                . ' fornecer a quantidade de no mínimo um Item.', 'danger');
        }
        return $this;
    }

    private function validaInvoice($value)
    {
        $validate = v::stringType()->notEmpty()->validate($value);
        if (!$validate || !Utils::checkLength($value, 3, 20)) {
            msg::showMsg('Para realizar o recebimeto é necessário fornecer o número da nota fiscal'
                . '<script>focusOn("invoice");</script>', 'danger');
        }
        return $this;
    }

    private function abstractDateValidate(string $value)
    {
        try {
            $date = Utils::dateDatabaseFormate($value);
            if (!v::date()->validate($date)) {
                echo '<script>
                window.location.replace("' . cfg::DEFAULT_URI . 'empenho/solicitacoes");
                alert("O campo de data deve ser preenchido corretamente.");</script>';
                exit;
            }
            return $date;
        } catch (\Exception $e) {
            echo '<script>
            window.location.replace("' . cfg::DEFAULT_URI . 'empenho/solicitacoes");
            alert("' . $e->getMessage() . '");</script>';
            exit;
        }
    }

    /**
     * Generate the code of Solicitação
     * @return The solictação code
     */
    protected function codeGenerator()
    {
        $invoiceCode = filter_input(INPUT_POST, 'code_invoice');

        $query = "" .
            " SELECT MAX(sol_inv.code) as lastId " .
            " FROM requests_invoices as sol_inv " .
            " INNER JOIN invoices as inv ON inv.id = sol_inv.invoices_id " .
            " WHERE inv.code LIKE '{$invoiceCode}' ";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        $lastId = $stmt->fetch(\PDO::FETCH_OBJ)->lastId;
        $lastId = $lastId ?? 0;

        $query2 = "SELECT COUNT(*) as quantity FROM requests_invoices";
        $stmt2 = $this->pdo->prepare($query2);
        $stmt2->execute();
        $quantity = $stmt2->fetch(\PDO::FETCH_OBJ)->quantity;

        $code = $quantity + 1 . substr($lastId, -1) + 1;
        return $code;
    }
}
