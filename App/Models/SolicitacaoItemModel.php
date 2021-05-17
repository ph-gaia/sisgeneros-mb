<?php

namespace App\Models;

use HTR\System\ModelCRUD as CRUD;
use HTR\Helpers\Mensagem\Mensagem as msg;
use HTR\Helpers\Paginator\Paginator;
use App\Models\ItemModel as Item;
use App\Models\SolicitacaoModel as Solicitacao;
use Respect\Validation\Validator as v;
use App\Config\Configurations as cfg;
use App\Helpers\Utils;

class SolicitacaoItemModel extends CRUD
{

    protected $entidade = 'requests_items';

    /**
     * @var \HTR\Helpers\Paginator\Paginator
     */
    protected $paginator;

    public function returnAll()
    {
        return $this->findAll();
    }

    public function findById($id)
    {
        $query = "
            SELECT A.*, C.quantity_available, C.id as itemId, SUM(A.quantity * A.value) as total FROM requests_items as A
            INNER JOIN requests as B ON B.id = A.requests_id
            INNER JOIN biddings_items as C ON C.number = A.number and C.biddings_id = B.biddings_id
            WHERE A.id = ?";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function recebimento($dados)
    {
        foreach ($dados as $id => $quantity) {
            parent::editar(['delivered' => $quantity], $id);
        }
    }

    public function recebimentoNaoLicitado()
    {
        $value = filter_input_array(INPUT_POST);

        for ($i = 0; $i < count($value['ids']); $i++) {
            $dados['delivered'] = $value['quantity'][$i];
            parent::editar($dados, $value['ids'][$i]);
        }
    }

    public function paginator($pagina, $idlista)
    {
        $inner = " as items " .
            " INNER JOIN requests as req ON req.id = items.requests_id " .
            " LEFT JOIN biddings_items as bidding ON bidding.biddings_id = req.biddings_id 
          and bidding.name LIKE items.name ";

        $dados = [
            'select' => 'items.*, bidding.quantity_available',
            'entidade' => $this->entidade . $inner,
            'pagina' => $pagina,
            'maxResult' => 50,
            'orderBy' => 'number ASC',
            'where' => 'items.requests_id = ?',
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

    public function novoRegistro($dados, $requestsId)
    {
        $item = new Item();
        foreach ($dados as $idItem => $quantity) {
            $value = $item->findById($idItem);
            $dados = [
                'requests_id' => $requestsId,
                'number' => $value['number'],
                'name' => $value['name'],
                'uf' => $value['uf'],
                'quantity' => $quantity,
                'delivered' => 0,
                'value' => $value['value']
            ];
            parent::novo($dados);
        }
    }

    public function novoNaoLicitado($dados, $requestId)
    {
        foreach ($dados as $value) {
            parent::novo([
                'requests_id' => $requestId,
                'name' => $value['name'],
                'uf' => $value['uf'],
                'quantity' => $value['quantity'],
                'delivered' => 0,
                'value' => $value['value']
            ]);
        }
    }

    public function novoRegistroByMenu($dados, $requestsId)
    {
        $item = new Item();
        foreach ($dados as $values) {
            $value = $item->findById($values['biddingItemsId']);
            $dados = [
                'requests_id' => $requestsId,
                'number' => $value['number'],
                'name' => $value['name'],
                'uf' => $value['uf'],
                'quantity' => $values['quantity'],
                'delivered' => 0,
                'value' => $value['value']
            ];
            parent::novo($dados);
        }
    }

    public function editarRegistro($idlista, $user)
    {
        $quantity = filter_input(INPUT_POST, 'quantity');
        $currentQtd = filter_input(INPUT_POST, 'currentQtd');

        $this->setQuantity($quantity)
            ->setId(filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT))
            ->validaQuantity()
            ->validaId();

        $item = $this->findById($this->getId());

        $solicitacaoModel = new Solicitacao();
        $solicitacao = $solicitacaoModel->findById($item['requests_id']);

        if (!in_array($solicitacao['status'], ['ELABORADO', 'REJEITADO'])) {
            // redireciona para solicitacao/ se a Solicitação ja estiver aprovada
            header("Location:" . cfg::DEFAULT_URI . 'solicitacao/');
            return true;
        }
        if ($item['requests_id'] != $idlista) {
            // não deixa prosseguir se o item pertencer a outra lista
            msg::showMsg('O Item não pode ser alterado.'
                . '<script>focusOn("quantity");</script>', 'danger');
        }
        // redireciona se o usuário tiver nível diferente de 1-Administrador e
        // se a Om da Solicitação for diferente da do usuário
        if ($user['level'] !== 'ADMINISTRADOR') {
            if ($solicitacao['oms_id'] != $user['oms_id']) {
                header("Location:" . cfg::DEFAULT_URI . 'solicitacao/');
                return true;
            }
        }

        $dados = [
            'quantity' => Utils::normalizeFloat($this->getQuantity(), 3),
        ];

        // $result = 0;
        // $itemModel = new Item();
        // $itemReq = $this->findItemByRequestIdAndNumber($item['requests_id'], $item['number']);
        // if ($quantity > $currentQtd) {
        //     $result = $quantity - $currentQtd;
        //     $itemModel->atualizarQtdComprometida($itemReq['item_id'], $result, 'soma');
        // } else {
        //     $result = $currentQtd - $quantity;
        //     $itemModel->atualizarQtdComprometida($itemReq['item_id'], $result, 'subtrai');
        // }

        if (parent::editar($dados, $this->getId())) {
            $solicitacaoModel->update($solicitacao['id']);
            msg::showMsg('001', 'success');
        }
    }

    public function removerRegistro($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->entidade} WHERE requests_id = ? ;");
        $stmt->bindValue(1, $id);
        return $stmt->execute();
    }

    public function eliminarItem($id, $idlitsa)
    {
        $this->db->instrucao('select')
            ->setaEntidade($this->getEntidade())
            ->setaFiltros()
            ->where('requests_id', '=', $idlitsa);
        $numRows = count($this->db->executa('select')->fetchAll(\PDO::FETCH_ASSOC));
        if ($numRows > 1) {
            parent::remover($id);
        }
        header("Location:" . cfg::DEFAULT_URI . "solicitacao/detalhar/idlista/{$idlitsa}");
    }

    public function quantidadeDemanda($itemnumber, $idLicitacao)
    {
        $stmt = $this->pdo->prepare(""
            . " SELECT SUM(`delivered`) as sum_quantity "
            . " FROM `requests_invoices` "
            . " WHERE `status` IN ('RECEBIDO', 'NF-ENTREGUE', 'NF-LIQUIDADA', 'NF-FINANCAS', 'NF-PAGA') "
            . "     AND `number` = ? "
            . "     AND `biddings_id` = ?;");
        $stmt->execute([$itemnumber, $idLicitacao]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ? $result['sum_quantity'] : false;
    }

    public function findAllItemsByRequestId($requestId)
    {
        $query = ""
            . " SELECT items.number as item_number, lic_items.id as item_id, "
            . " items.quantity as quantidade_solicitada, "
            . " lic_items.*, lic.number as licitacao, sol.suppliers_id "
            . " FROM requests_items as items "
            . " INNER JOIN biddings_items as lic_items ON lic_items.number = items.number "
            . " INNER JOIN biddings	as lic ON lic.id = lic_items.biddings_id "
            . " INNER JOIN requests as sol ON sol.id = items.requests_id and sol.biddings_id = lic.id "
            . " WHERE items.requests_id = ? ";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$requestId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findAllItemsByRequestIdNaoLicitado($requestId)
    {
        $query = ""
            . " SELECT items.number as item_number, lic_items.id as item_id, "
            . " items.quantity as quantidade_solicitada, "
            . " lic_items.*, lic.number as licitacao, sol.suppliers_id, "
            . " items.name as item_name, items.uf as item_uf, items.value as item_value "
            . " FROM requests_items as items "
            . " LEFT JOIN biddings_items as lic_items ON lic_items.number = items.number "
            . " LEFT JOIN biddings	as lic ON lic.id = lic_items.biddings_id "
            . " INNER JOIN requests as sol ON sol.id = items.requests_id "
            . " WHERE items.requests_id = ? ";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$requestId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findItemByRequestIdAndNumber($requestId, $number)
    {
        $query = ""
            . " SELECT lic_items.id as item_id "
            . " FROM requests_items as items "
            . " INNER JOIN requests as sol ON sol.id = items.requests_id "
            . " INNER JOIN biddings_items as lic_items ON lic_items.number = items.number AND lic_items.biddings_id = sol.biddings_id "
            . " WHERE items.requests_id = ? and items.number = ? ";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$requestId, $number]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function findTotalValueByRequestId($requestId)
    {
        $query = ""
            . " SELECT SUM(items.value * items.quantity) as total "
            . " FROM requests "
            . " INNER JOIN requests_items as items ON items.requests_id = requests.id "
            . " WHERE items.requests_id = ? ";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$requestId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    private function setAll($dados)
    {
        // Seta todos os valores
        $this->setId(filter_input(INPUT_POST, 'id') ?? time())
            ->setidlista($dados['requests_id'])
            ->setIdLicitacao($dados['biddings_id'])
            ->setListaItens($dados['lista_itens']);
    }

    private function validaId()
    {
        $value = v::intVal()->validate($this->getId());
        if (!$value) {
            msg::showMsg('O campo ID deve ser preenchido corretamente', 'danger');
        }
        return $this;
    }

    private function validaQuantity()
    {
        $value = v::floatVal()->notEmpty()->noWhitespace()->validate($this->getQuantity());
        if (!$value) {
            msg::showMsg('O campo Quantidade deve ser preenchido corretamente.'
                . '<script>focusOn("quantity");</script>', 'danger');
        }
        return $this;
    }
}
