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

        $dados = [
            'select' => 'items.*',
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
        foreach ($dados as $requestId) {
            $result = $solItem->findAllItemsByRequestId($requestId);
            foreach ($result as $value) {
                $dados = [
                    'invoices_id' => $invoicesId,
                    'requests_id' => $requestId,
                    'suppliers_id' => $value['suppliers_id'],
                    'number' => $value['number'],
                    'name' => $value['name'],
                    'uf' => $value['uf'],
                    'quantity' => $value['quantity'],
                    'delivered' => 0,
                    'value' => $value['value']
                ];
                parent::novo($dados);
            }
        }
    }
}
