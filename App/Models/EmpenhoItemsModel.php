<?php

namespace App\Models;

use HTR\System\ModelCRUD as CRUD;
use HTR\Helpers\Paginator\Paginator;
use App\Models\ItemModel as Item;
use App\Models\SolicitacaoModel;
use App\Models\SolicitacaoItemModel;

class EmpenhoItemsModel extends CRUD
{

    protected $entidade = 'invoices_items';

    /**
     * @var \HTR\Helpers\Paginator\Paginator
     */
    protected $paginator;

    public function paginator($pagina, $idlista)
    {
        $subQuery = ",(
            SELECT SUM(reqInv.quantity) 
            FROM requests_invoices as reqInv 
            WHERE reqInv.invoices_id = items.invoices_id 
            and reqInv.number = items.number 
            and reqInv.status = 'SOLICITADO') as requested ";
        $dados = [
            'select' => 'items.*' . $subQuery,
            'entidade' => $this->entidade . " as items ",
            'pagina' => $pagina,
            'maxResult' => 50,
            'orderBy' => 'number ASC',
            'where' => 'items.invoices_id = ?',
            'bindValue' => [$idlista]
        ];

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

    public function novoRegistro($dados, $invoicesId)
    {
        $solItem = new SolicitacaoItemModel();
        $item = new Item();
        foreach ($dados as $requestId) {
            $data = (new SolicitacaoModel())->findById($requestId);
            if ($data['biddings_id'] == 0) {
                $result = $solItem->findAllItemsByRequestIdNaoLicitado($requestId);   
            } else {
                $result = $solItem->findAllItemsByRequestId($requestId);
            }
            foreach ($result as $value) {
                $dados = [
                    'invoices_id' => $invoicesId,
                    'requests_id' => $requestId,
                    'suppliers_id' => $value['suppliers_id'] ?? 0,
                    'biddings_id' => $value['biddings_id'] ?? 0,
                    'number' => $value['number'] ?? 0,
                    'name' => $value['name'] ?? $value['item_name'],
                    'uf' => $value['uf'] ?? $value['item_uf'],
                    'quantity' => $value['quantidade_solicitada'],
                    'delivered' => 0,
                    'value' => $value['value'] ?? $value['item_value']
                ];
                parent::novo($dados);
                $item->atualizarQtdEmpenhada($value['item_id'], $value['quantidade_solicitada']);
            }
        }
    }
}
