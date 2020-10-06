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
            $result = $solItem->findAllItemsByRequestId($requestId);
            foreach ($result as $value) {
                $dados = [
                    'invoices_id' => $invoicesId,
                    'requests_id' => $requestId,
                    'suppliers_id' => $value['suppliers_id'],
                    'biddings_id' => $value['biddings_id'],
                    'number' => $value['number'],
                    'name' => $value['name'],
                    'uf' => $value['uf'],
                    'quantity' => $value['quantidade_solicitada'],
                    'delivered' => 0,
                    'value' => $value['value']
                ];
                parent::novo($dados);
                $item->atualizarQtdEmpenhada($value['item_id'], $value['quantidade_solicitada']);
            }
        }
    }
}
