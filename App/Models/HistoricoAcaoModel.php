<?php

namespace App\Models;

use HTR\System\ModelCRUD as CRUD;
use App\Models\AcessoModel;

class HistoricoAcaoModel extends CRUD
{

    protected $entidade = 'historic_action_requests';

    /**
     * Process the historic request status.
     * @param int $id Identification of Solicitação
     * @param int $userId Identification of Usuários
     * @param string $action The action to be executed
     */
    public function novoRegistro(int $requestId, int $userId, string $action)
    {
        $result = (new AcessoModel())->findById($userId);

        $dados = [
            'requests_id' => $requestId,
            'users_id' => $userId,
            'action' => $action,
            'nip' => $result['nip'],
            'user_name' => $result['name'],
            'user_profile' => $result['level'],
            'date_action' => date('Y-m-d H:i:s')
        ];
        parent::novo($dados);
    }

    public function historicByRequestId($requestId)
    {
        $query = "" .
            " SELECT * FROM {$this->entidade} " .
            " WHERE requests_id = :requestsId ";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':requestsId' => $requestId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}