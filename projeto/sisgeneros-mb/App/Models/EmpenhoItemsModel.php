<?php
namespace App\Models;

use HTR\System\ModelCRUD as CRUD;
use App\Models\SolicitacaoModel;

class EmpenhoItemsModel extends CRUD
{

    protected $entidade = 'invoices_has_requests';

    /**
     * @var \HTR\Helpers\Paginator\Paginator
     */
    protected $paginator;

    public function returnAll()
    {
        return $this->findAll();
    }

    public function novoRegistro($requestsId, $invoiceId)
    {
        foreach($requestsId as $value) {
            $dados = [
                'requests_id' => $value,
                'invoices_id' => $invoiceId
            ];
            parent::novo($dados);
        }
    }
}
