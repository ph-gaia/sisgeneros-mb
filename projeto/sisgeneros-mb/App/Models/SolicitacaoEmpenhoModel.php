<?php

namespace App\Models;

use HTR\Helpers\Paginator\Paginator;
use HTR\System\ModelCRUD as CRUD;
use HTR\Helpers\Mensagem\Mensagem as msg;
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
            " ORDER BY number ASC ";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':id' => $idLista]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function novoRegistro()
    {
        $this->validaAll();

        $solItem = new EmpenhoItemsModel();
        foreach ($this->getItemsList() as $idItem => $quantity) {
            $result = $solItem->findById($idItem);
            $dados = [
                'invoices_id' => $result['invoices_id'],
                'code' => $this->getCode(),
                'number' => $result['number'],
                'name' => $result['name'],
                'uf' => $result['uf'],
                'quantity' => $quantity,
                'status' => 'ABERTO',
                'delivered' => 0,
                'value' => $result['value'],
                'created_at' => date('Y-m-d'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            parent::novo($dados);
        }

        msg::showMsg('Solicitação Registrada com Sucesso!<br>'
                . "<strong>Solicitação Nº {$this->getCode()} <br>"
                . "Status: ABERTO.</strong><br>", 'success');
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
                $quantidade = filter_var(Utils::normalizeFloat($value), FILTER_VALIDATE_FLOAT);

                if ($id && $quantidade) {
                    $result[$id] = $quantidade;
                }
            }
        }

        return $result;
    }

    private function validaAll()
    {
        $value = filter_input_array(INPUT_POST);
        $this->setItemsList($this->buildItems($value));
        $this->setCode($this->numberGenerator());

        $this->validaItemsList();
    }

    private function validaItemsList()
    {
        if (empty($this->getItemsList())) {
            msg::showMsg('Para realizar uma solicitação, é imprescindível'
                . ' fornecer a quantidade de no mínimo um Item.', 'danger');
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
