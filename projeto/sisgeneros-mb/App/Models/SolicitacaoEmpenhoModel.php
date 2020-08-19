<?php

namespace App\Models;

use HTR\Helpers\Mensagem\Mensagem as msg;
use Respect\Validation\Validator as v;
use App\Config\Configurations as cfg;
use HTR\Helpers\Paginator\Paginator;
use HTR\System\ModelCRUD as CRUD;
use App\Models\EmpenhoItemsModel;
use App\Helpers\Utils;

class SolicitacaoEmpenhoModel extends CRUD
{

    protected $entidade = 'requests_invoices';

    /**
     * @var \HTR\Helpers\Paginator\Paginator
     */
    protected $paginator;

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

    public function cancelarRegistro($id)
    {
        $result = $this->findByCode($id);
        $stmt = $this->pdo->prepare("DELETE FROM {$this->entidade} WHERE code = ?");
        if ($stmt->execute([$id])) {
            header('Location: ' . cfg::DEFAULT_URI . 'empenho/detalhar/idlista/' . $result['invoices_id']);
        }
    }

    public function entregarNf($id)
    {
        $result = $this->findByCode($id);

        $query = "UPDATE {$this->entidade} SET
        status = 'NF-ENTREGUE', 
        updated_at = '" . date('Y-m-d H:i:s') . "'
        WHERE code = ?";

        $stmt = $this->pdo->prepare($query);
        if ($stmt->execute([$id])) {
            header('Location: ' . cfg::DEFAULT_URI . 'empenho/detalhar/idlista/' . $result['invoices_id']);
        }
    }

    public function liquidarNf($id)
    {
        $result = $this->findByCode($id);

        $query = "UPDATE {$this->entidade} SET 
        status = 'NF-LIQUIDADA', 
        updated_at = '" . date('Y-m-d H:i:s') . "'
        WHERE code = ?";

        $stmt = $this->pdo->prepare($query);
        if ($stmt->execute([$id])) {
            header('Location: ' . cfg::DEFAULT_URI . 'empenho/detalhar/idlista/' . $result['invoices_id']);
        }
    }

    public function pagarNf($id)
    {
        $result = $this->findByCode($id);

        $query = "UPDATE {$this->entidade} SET 
        status = 'NF-PAGA', 
        updated_at = '" . date('Y-m-d H:i:s') . "'
        WHERE code = ?";

        $stmt = $this->pdo->prepare($query);
        if ($stmt->execute([$id])) {
            header('Location: ' . cfg::DEFAULT_URI . 'empenho/detalhar/idlista/' . $result['invoices_id']);
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
        $this->setInvoice(filter_input(INPUT_POST, 'invoice'));
        $this->validaInvoice($this->getInvoice());
        $this->setObservation(filter_input(INPUT_POST, 'observation', FILTER_SANITIZE_SPECIAL_CHARS));
        $this->setItemsList($this->buildItems(filter_input_array(INPUT_POST)));
        $this->validaItemsList();
        $val = $this->validaQtdRecebidaMenorQtdSol();

        $query = "
            UPDATE {$this->entidade} SET 
            status = '" . (($val) ? 'RECEBIDO-PARCIAL' : 'RECEBIDO') . "', 
            updated_at = '" . date('Y-m-d H:i:s') . "', 
            invoice = " . $this->getInvoice() . ",
            observation = '" . $this->getObservation() . "' WHERE code = ?";

        $stmt = $this->pdo->prepare($query);

        if ($stmt->execute([$this->getId()])) {

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

        if (!in_array($user['level'], ['ADMINISTRADOR', 'CONTROLADOR'])) {
            $where = " AND req.oms_id = {$user['oms_id']} ";
            $query . $where;
        }
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':status' => $status]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    private function validaAll()
    {
        $this->setItemsList($this->buildItems(filter_input_array(INPUT_POST)));
        $this->setCode($this->numberGenerator());

        $this->validaItemsList();
        $this->validaQtdSolMaiorQtdDisponivel();
    }

    private function validaQtdRecebidaMenorQtdSol()
    {
        foreach ($this->getItemsList() as $value) {
            // if (($value['quantidade'] - $value['entregue']) > $value['solicitada']) {
            //     msg::showMsg('A quantidade recebida é maior do que a quantidade solicitada.', 'danger');
            // }
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
            $disponivel = floatval($result['quantity'] - $result['delivered']);
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

    /**
     * Generate the number of Solicitação
     * @return int The solictação number
     */
    protected function numberGenerator(int $number = 0): int
    {
        if ($number > 0) {
            $hasEqualsRegister = $this->pdo
                ->query("SELECT id FROM requests_invoices WHERE number = {$number}")
                ->fetch(\PDO::FETCH_OBJ);

            // If exists a register with this number, try with the number plus one
            if ($hasEqualsRegister) {
                return $this->numberGenerator($number + 1);
            }

            return $number;
        }

        $currentYear = date('Y');
        $currentYearShort = date('y');
        $query = "SELECT COUNT(id) as quantity FROM requests_invoices WHERE YEAR(created_at) = '{$currentYear}'";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        $registersQuantity = $stmt->fetch(\PDO::FETCH_OBJ)->quantity;
        $number = (int) $currentYearShort . ($registersQuantity + 1);
        // check if in the exact momsent exists a register with this number
        return $this->numberGenerator($number);
    }
}
