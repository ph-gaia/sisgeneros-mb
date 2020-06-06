<?php
namespace App\Models;

use HTR\System\ModelCRUD as CRUD;
use HTR\Helpers\Mensagem\Mensagem as msg;
use HTR\Helpers\Paginator\Paginator;
use Respect\Validation\Validator as v;
use App\Config\Configurations as cfg;

class HistoricoEmpenhoModel extends CRUD
{

    protected $entidade = 'historic_invoices';

    /**
     * @var \HTR\Helpers\Paginator\Paginator
     */
    protected $paginator;

    public function returnAll()
    {
        return $this->findAll();
    }

    public function paginator($pagina)
    {
        $dados = [
            'entidade' => $this->entidade,
            'pagina' => $pagina,
            'maxResult' => 100,
            'orderBy' => ''
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

    public function historicByInvoiceId($invoiceId)
    {
        $query = "" .
            " SELECT A.*, users.name FROM {$this->entidade} as A " .
            " INNER JOIN users ON users.id = A.users_id " .
            " WHERE A.invoices_id = :invoiceId ";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':invoiceId' => $invoiceId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function novoRegistro($invoiceId, $userId, $value, $observation)
    {
        $dados = [
            'invoices_id' => $invoiceId,
            'users_id' => $userId,
            'value' => $value,
            'observation' => $observation,
            'created_at' => date('Y-m-d')
        ];
        parent::novo($dados);
    }
}
