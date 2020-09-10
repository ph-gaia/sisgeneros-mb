<?php
namespace App\Models;

use HTR\System\ModelCRUD as CRUD;
use HTR\Helpers\Mensagem\Mensagem as msg;
use HTR\Helpers\Paginator\Paginator;
use Respect\Validation\Validator as v;
use App\Config\Configurations as cfg;
use App\Helpers\View;

class LicitacaoModel extends CRUD
{

    protected $entidade = 'biddings';

    /**
     * @var \HTR\Helpers\Paginator\Paginator
     */
    protected $paginator;

    public function returnAll()
    {
        return $this->findAll();
    }

    public function paginator($pagina, $dateLimit = null, $omId = null)
    {
        $innerJoin = " INNER JOIN biddings_oms_lists ON biddings.id = biddings_oms_lists.biddings_id ";
        $dados = [
            'select' => 'biddings.*',
            'entidade' => $this->entidade . $innerJoin,
            'pagina' => $pagina,
            'maxResult' => 20,
            'orderBy' => 'created_at DESC',
            'where' => ' biddings_oms_lists.oms_id = :omId ',
            'bindValue' => [':omId' => $omId]
        ];

        if ($dateLimit) {
            $dados['where'] = ' biddings.validate >= :date and biddings_oms_lists.oms_id = :omId ';
            $dados['bindValue'] = [':date' => $dateLimit, ':omId' => $omId];
        }

        $paginator = new Paginator($dados);
        $this->resultadoPaginator = $paginator->getResultado();
        $this->navPaginator = $paginator->getNaveBtn();
    }

    public function getResultadoPaginator()
    {
        return $this->resultadoPaginator;
    }

    public function getNavePaginator()
    {
        return $this->navPaginator;
    }

    public function listaPorFornecedor($idLita)
    {
        $stmt = $this->pdo->prepare("
            SELECT
                DISTINCT biddings.number,
                    biddings.id AS biddings_id,
                    biddings.uasg,
                    biddings.description,
                    biddings.uasg_name,
                    suppliers.name,
                    suppliers.id as suppliers_id
            FROM biddings
            INNER JOIN biddings_items AS item
                ON item.biddings_id = biddings.id AND item.active = 'yes'
            INNER JOIN suppliers
                ON suppliers.id = item.suppliers_id
            WHERE biddings.id = ?
            ORDER BY suppliers.name");
        $stmt->execute([$idLita]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function listaItemFornecedor($omId, $search)
    {
        $stmt = $this->pdo->prepare("
            SELECT
                DISTINCT biddings.number,
                    biddings.id AS biddings_id,
                    biddings.uasg,
                    biddings.uasg_name,
                    item.name produtoNome,
                    suppliers.name AS name,
                    suppliers.id as suppliers_id
            FROM biddings
            INNER JOIN biddings_items AS item
                ON item.biddings_id = biddings.id AND item.active = 'yes'
            INNER JOIN biddings_oms_lists AS biddings_oms
                ON biddings_oms.biddings_id = biddings.id AND biddings_oms.oms_id = {$omId}
            INNER JOIN suppliers
                ON suppliers.id = item.suppliers_id
            WHERE item.name LIKE :search AND biddings.validate >= '" . date('Y-m-d') . "'
            ORDER BY item.name");
        $stmt->execute([':search' => "%{$search}%"]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function novoRegistro()
    {
        // Valida dados
        $this->validaAll();
        // Verifica se há registro igual
        $this->evitarDuplicidade();

        $dados = [
            'number' => $this->getNumber(),
            'uasg' => $this->getUasg(),
            'description' => $this->getDescription(),
            'uasg_name' => $this->getUasgName(),
            'validate' => $this->getValidate(),
            'created_at' => date('Y-m-d')
        ];

        if (parent::novo($dados)) {
            $lastId = $this->pdo->lastInsertId();
            $licitacaoListaOms = new LicitacaoListaOmsModel();

            foreach ($this->buildOmsId() as $omId) {
                $dados = [
                    'oms_id' => $omId,
                    'biddings_id' => $lastId
                ];
                $licitacaoListaOms->novo($dados);
            }

            msg::showMsg('Licitação Registrada com Sucesso. '
                . "<a href='" . cfg::DEFAULT_URI . "item/novo/idlista/" . $lastId . "' class='btn btn-info'>"
                . "<i class='fa fa-plus-circle'></i> Adicionar Item</a>"
                . '<script>resetForm();</script>', 'success');
        }
    }

    public function editarRegistro()
    {
        // Valida dados
        $this->validaAll();
        // Verifica se há registro igual
        $this->evitarDuplicidade();

        $dados = [
            'number' => $this->getnumber(),
            'uasg' => $this->getUasg(),
            'description' => $this->getDescription(),
            'uasg_name' => $this->getUasgName(),
            'validate' => $this->getValidate()
        ];

        if (parent::editar($dados, $this->getId())) {
            msg::showMsg('001', 'success');
        }
    }

    public function removerRegistro($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM biddings_oms_lists WHERE biddings_id = ?");
        if ($stmt->execute([$id])) {
            parent::remover($id);
            header('Location: ' . cfg::DEFAULT_URI . 'licitacao/ver/');
        }
    }

    public function fetchDataToEdit(int $id): array
    {
        $result = [];
        $licitacao = $this->findById($id);
        if ($licitacao) {
            $result['result'] = $licitacao;
            $query = ""
                . "SELECT "
                . " bol.id, oms.naval_indicative, oms.name "
                . " FROM biddings_oms_lists AS bol "
                . " INNER JOIN oms "
                . "     ON oms.id = bol.oms_id "
                . " WHERE bol.biddings_id = {$licitacao['id']} "
                . " ORDER BY oms.name ";
            $result['oms'] = $this->pdo->query($query)->fetchAll(\PDO::FETCH_ASSOC);
            return $result;
        }
        header('Location: ' . cfg::DEFAULT_URI . 'licitacao/ver');
    }

    public function fetchOmOut(int $id)
    {
        $result = [];
        $omCount = count((new OmModel())->findAll());
        $omInsertedCount = count((new LicitacaoListaOmsModel())->findAllByBiddings_id($id));

        if ($omCount != $omInsertedCount) {
            $query = ""
                . " SELECT "
                . " oms.id, oms.naval_indicative, oms.name "
                . " FROM oms "
                . " WHERE oms.id NOT IN ("
                . "     SELECT "
                . "         oms.id "
                . "     FROM biddings_oms_lists AS bol "
                . "     INNER JOIN oms "
                . "         ON oms.id = bol.oms_id "
                . "     WHERE bol.biddings_id = {$id} "
                . " ) "
                . " ORDER BY oms.name";
            $result = $this->pdo->query($query)->fetchAll(\PDO::FETCH_ASSOC);
        }
        return $result;
    }

    public function adicionarNovaOM()
    {
        $this->setId(filter_input(INPUT_POST, 'id') ?? time())
            ->setOmsId(filter_input(INPUT_POST, 'oms'));

        $this->validaId()
            ->validaInt($this->getOmsId());

        $query = ""
            . " SELECT "
            . " id "
            . " FROM biddings_oms_lists AS bol "
            . " WHERE bol.oms_id = :omId AND bol.biddings_id = :biddId";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute([
            ':omId' => $this->getOmsId(),
            ':biddId' => $this->getId()
        ]);

        if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
            msg::showMsg('Esta oms já foi adicionada.', 'danger');
        }

        $dados = [
            'oms_id' => $this->getOmsId(),
            'biddings_id' => $this->getId()
        ];

        if ((new LicitacaoListaOmsModel())->novo($dados)) {
            msg::showMsg('111', 'success');
        }
    }

    public function eliminarOm(int $id, $licitacaoId)
    {
        if ((new LicitacaoListaOmsModel())->remover($id)) {
            header('Location: ' . cfg::DEFAULT_URI . 'licitacao/editar/id/' . $licitacaoId);
        }
    }

    /**
     * Returns all elements from database according the Ingredients ID 
     * @param int $ingredientsId
     * @return array
     */
    public function findAllByIngredientsId(int $ingredientsId): array
    {
        $query = ""
            . " SELECT "
            . "     bi.*, "
            . "     b.number AS biddings_number "
            . " FROM "
            . "     biddings_items AS bi "
            . " INNER JOIN "
            . "     biddings AS b ON "
            . "     b.id = bi.biddings_id "
            . " WHERE "
            . "     ingredients_id = {$ingredientsId} ";
        return $this->pdo->query($query)->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function evitarDuplicidade()
    {
        /// Evita a duplicidade de registros
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->entidade} WHERE id != ? AND number = ? AND uasg = ?");
        $stmt->bindValue(1, $this->getId());
        $stmt->bindValue(2, $this->getnumber());
        $stmt->bindValue(3, $this->getUasg());
        $stmt->execute();
        if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
            msg::showMsg('Já existe um registro com este Número de Licitação'
                . 'para a UASG nº <strong>' . $this->getUasg() . '</strong>'
                . '<script>focusOn("number")</script>', 'warning');
        }
    }

    private function validaAll()
    {
        // Seta todos os valores
        $this->setId(filter_input(INPUT_POST, 'id') ?? time())
            ->setnumber(filter_input(INPUT_POST, 'number', FILTER_SANITIZE_SPECIAL_CHARS))
            ->setUasg(filter_input(INPUT_POST, 'uasg', FILTER_VALIDATE_INT))
            ->setDescription(filter_input(INPUT_POST, 'description', FILTER_SANITIZE_SPECIAL_CHARS))
            ->setUasgName(filter_input(INPUT_POST, 'uasg_name', FILTER_SANITIZE_SPECIAL_CHARS))
            ->setValidate(filter_input(INPUT_POST, 'validate', FILTER_SANITIZE_SPECIAL_CHARS));

        // Inicia a Validação dos dados
        $this->validaId()
            ->validaNumber()
            ->validaUasg()
            ->validaDescription()
            ->validaUasgName()
            ->validaValidate();
    }

    private function validaInt($value)
    {
        $value = v::intVal()->validate($value);
        if (!$value) {
            msg::showMsg('Não foi possível registrar a solicitação', 'danger');
        }
        return $value;
    }

    // Validação
    private function validaId()
    {
        $value = v::intVal()->validate($this->getId());
        if (!$value) {
            msg::showMsg('O campo ID deve ser um número inteiro válido.', 'danger');
        }
        return $this;
    }

    private function validanumber()
    {
        $value = v::stringType()->notEmpty()->noWhitespace()->validate($this->getNumber());
        if (!$value || !View::checkLength($this->getNumber(), 10, 10)) {
            msg::showMsg('O campo number deve ser preenchido corretamente'
                . ' com <strong>10 caracteres obrigatoriamente</strong>.'
                . '<script>focusOn("number");</script>', 'danger');
        }
        return $this;
    }

    private function validaUasg()
    {
        $value = v::intVal()->notEmpty()->min(6, 6)->validate($this->getUasg());
        if (!$value) {
            msg::showMsg('O campo UASG deve ser um número inteiro válido '
                . '<strong>com 6 caracteres</strong>.'
                . '<script>focusOn("uasg");</script>', 'danger');
        }
        return $this;
    }

    private function validaUasgName()
    {
        $value = v::stringType()->notEmpty()->validate($this->getUasgName());
        if (!$value || !View::checkLength($this->getUasgName(), 1, 50)) {
            msg::showMsg('O campo Nome da Uasg deve ser deve ser preenchido corretamente.'
                . '<script>focusOn("uasg_name");</script>', 'danger');
        }
        return $this;
    }

    private function validadescription()
    {
        $value = v::stringType()->notEmpty()->validate($this->getDescription());
        if (!$value || !View::checkLength($this->getDescription(), 1, 30)) {
            msg::showMsg('O campo Descrição da licitação deve ser deve ser preenchido corretamente.'
                . '<script>focusOn("description");</script>', 'danger');
        }
        return $this;
    }

    private function validaValidate()
    {
        $validate = View::dateDatabaseFormate($this->getValidate());
        $value = v::date()->validate($validate);
        if (!$value) {
            msg::showMsg('O campo Validade deve ser preenchido corretamente.'
                . '<script>focusOn("validate");</script>', 'danger');
        }
        $this->setValidate($validate);
        return $this;
    }

    private function buildOmsId(): array
    {
        $result = [];
        $requestPost = filter_input_array(INPUT_POST);
        $items = is_array($requestPost['oms'] ?? null) ? $requestPost['oms'] : [];

        foreach ($items as $omId) {
            $value = v::intVal()->validate($omId);
            if (!$value) {
                msg::showMsg('O campo ID deve ser um número inteiro válido.', 'danger');
            }
            $result[] = $omId;
        }

        return $result;
    }
}
