<?php

/**
 * @Model Om
 */

namespace App\Models;

use HTR\System\ModelCRUD as CRUD;
use HTR\Helpers\Mensagem\Mensagem as msg;
use HTR\Helpers\Paginator\Paginator;
use Respect\Validation\Validator as v;
use App\Config\Configurations as cfg;
use App\Helpers\Utils;

class OmModel extends CRUD
{

    protected $entidade = 'oms';
    protected $id;
    protected $nome;
    protected $uasg;
    protected $indicativoNaval;
    protected $time;
    protected $resultadoPaginator;
    protected $navPaginator;

    /*
     * Método uaso para retornar todos os dados da tabela.
     */

    public function returnAll()
    {
        return $this->findAll();
    }

    public function paginator($pagina, $busca = null)
    {
        $dados = [
            'entidade' => $this->entidade,
            'pagina' => $pagina,
            'maxResult' => 10,
            'orderBy' => 'name ASC'
        ];

        if ($busca) {
            $dados['where'] = " "
            . " oms.name LIKE :seach"
            . " OR oms.naval_indicative LIKE :seach"
            . " OR oms.uasg LIKE :seach";
            $dados['bindValue'][':seach'] = '%' . $busca . '%';
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

    public function novoRegistro()
    {
        // Valida dados
        $this->validaAll();
        // Verifica se há registro igual
        $this->evitarDuplicidade();

        $dados = [
            'name' => $this->getNome(),
            'uasg' => $this->getUasg(),
            'naval_indicative' => $this->getIndicativoNaval(),
            'fiscal_agent' => $this->getAgenteFiscal(),
            'fiscal_agent_graduation' => $this->getAgenteFiscalPosto(),
            'munition_manager' => $this->getGestorMuniciamento(),
            'munition_manager_graduation' => $this->getGestorMuniciamentoPosto(),
            'munition_fiel' => $this->getFielMuniciamento(),
            'munition_fiel_graduation' => $this->getFielMuniciamentoPosto(),
            'expense_originator' => $this->getOrdenadorDespesa(),
            'expense_originator_graduation' => $this->getOrdenadorDespesaPosto(),
            'ug' => $this->getUg(),
            'ptres' => $this->getPtres(),
            'ai' => $this->getAi(),
            'do' => $this->getDo(),
            'bi' => $this->getBi(),
            'fr' => $this->getFr(),
            'nd' => $this->getNd(),
            'cost_center' => $this->getCostCenter(),
            'limit_request_nl' => $this->getLimitRequest(),
            'classification_items' => $this->getClassificationItems(),
            'created_at' => date('Y-m-d'),
            'updated_at' => date('Y-m-d')
        ];
        if (parent::novo($dados)) {
            msg::showMsg('111', 'success');
        }
    }

    public function editarRegistro()
    {
        // Valida dados
        $this->validaAll();
        // Verifica se há registro igual
        $this->evitarDuplicidade();

        $dados = [
            'name' => $this->getNome(),
            'uasg' => $this->getUasg(),
            'naval_indicative' => $this->getIndicativoNaval(),
            'fiscal_agent' => $this->getAgenteFiscal(),
            'fiscal_agent_graduation' => $this->getAgenteFiscalPosto(),
            'munition_manager' => $this->getGestorMuniciamento(),
            'munition_manager_graduation' => $this->getGestorMuniciamentoPosto(),
            'munition_fiel' => $this->getFielMuniciamento(),
            'munition_fiel_graduation' => $this->getFielMuniciamentoPosto(),
            'expense_originator' => $this->getOrdenadorDespesa(),
            'expense_originator_graduation' => $this->getOrdenadorDespesaPosto(),
            'ug' => $this->getUg(),
            'ptres' => $this->getPtres(),
            'ai' => $this->getAi(),
            'do' => $this->getDo(),
            'bi' => $this->getBi(),
            'fr' => $this->getFr(),
            'nd' => $this->getNd(),
            'cost_center' => $this->getCostCenter(),
            'limit_request_nl' => $this->getLimitRequest(),
            'classification_items' => $this->getClassificationItems(),
            'updated_at' => date('Y-m-d')
        ];

        if (parent::editar($dados, $this->getId())) {
            msg::showMsg('001', 'success');
        }
    }

    public function removerRegistro($id)
    {
        if (parent::remover($id)) {
            header('Location: ' . cfg::DEFAULT_URI . 'om/ver/');
        }
    }

    private function evitarDuplicidade()
    {
        /// Evita a duplicidade de registros
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->entidade} WHERE id != ? AND name = ?");
        $stmt->bindValue(1, $this->getId());
        $stmt->bindValue(2, $this->getNome());
        $stmt->execute();
        if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
            msg::showMsg('Já existe um registro com este Nome.<script>focusOn("name")</script>', 'warning');
        }

        $stmt = $this->pdo->prepare("SELECT * FROM {$this->entidade} WHERE id != ? AND uasg = ?");
        $stmt->bindValue(1, $this->getId());
        $stmt->bindValue(2, $this->getUasg());
        $stmt->execute();
        if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
            msg::showMsg('Já existe um registro com este número de UASG.<script>focusOn("uasg")</script>', 'warning');
        }

        $stmt = $this->pdo->prepare("SELECT * FROM {$this->entidade} WHERE id != ? AND naval_indicative = ?");
        $stmt->bindValue(1, $this->getId());
        $stmt->bindValue(2, $this->getIndicativoNaval());
        $stmt->execute();
        if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
            msg::showMsg('Já existe um registro com este Indicativo Naval.<script>focusOn("naval_indicative")</script>', 'warning');
        }
    }

    private function validaAll()
    {
        // Seta todos os valores
        $this->setTime(time())
            ->setId(filter_input(INPUT_POST, 'id') ?? time())
            ->setUasg(filter_input(INPUT_POST, 'uasg', FILTER_VALIDATE_INT))
            ->setNome(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS))
            ->setIndicativoNaval(filter_input(INPUT_POST, 'naval_indicative'))
            ->setAgenteFiscal(filter_input(INPUT_POST, 'fiscal_agent'))
            ->setAgenteFiscalPosto(filter_input(INPUT_POST, 'fiscal_agent_graduation'))
            ->setGestorMuniciamento(filter_input(INPUT_POST, 'munition_manager'))
            ->setGestorMuniciamentoPosto(filter_input(INPUT_POST, 'munition_manager_graduation'))
            ->setFielMuniciamento(filter_input(INPUT_POST, 'munition_fiel'))
            ->setFielMuniciamentoPosto(filter_input(INPUT_POST, 'munition_fiel_graduation'))
            ->setOrdenadorDespesa(filter_input(INPUT_POST, 'expense_originator'))
            ->setOrdenadorDespesaPosto(filter_input(INPUT_POST, 'expense_originator_graduation'))
            ->setUg(filter_input(INPUT_POST, 'ug'))
            ->setPtres(filter_input(INPUT_POST, 'ptres'))
            ->setAi(filter_input(INPUT_POST, 'ai'))
            ->setDo(filter_input(INPUT_POST, 'do'))
            ->setBi(filter_input(INPUT_POST, 'bi'))
            ->setFr(filter_input(INPUT_POST, 'fr'))
            ->setNd(filter_input(INPUT_POST, 'nd'))
            ->setLimitRequest(filter_input(INPUT_POST, 'limit_request_nl', FILTER_SANITIZE_SPECIAL_CHARS))
            ->setClassificationItems(filter_input(INPUT_POST, 'classification_items'))
            ->setCostCenter(filter_input(INPUT_POST, 'cost_center'));

        $value = str_replace(".", "", $this->getLimitRequest());
        $value = str_replace(",", ".", $value);

        $this->setLimitRequest($value);

        // Inicia a Validação dos dados
        $this->validaId()
            ->validaUasg()
            ->validaNome()
            ->validaIndicativoNaval()
            ->validaAgenteFiscal()
            ->validaAgenteFiscalPosto()
            ->validaGestorMuniciamento()
            ->validaGestorMuniciamentoPosto()
            ->validaFielMuniciamento()
            ->validaFielMuniciamentoPosto();
    }

    private function validaId()
    {
        $value = v::intVal()->validate($this->getId());
        if (!$value) {
            msg::showMsg('O campo ID deve ser um número inteiro válido.', 'danger');
        }
        return $this;
    }

    private function validaNome()
    {
        $value = v::stringType()->notEmpty()->validate($this->getNome());
        if (!$value || !Utils::checkLength($this->getNome(), 1, 60)) {
            msg::showMsg('O campo Nome deve ser deve ser preenchido corretamente.'
                . '<script>focusOn("name");</script>', 'danger');
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

    private function validaIndicativoNaval()
    {
        $value = v::stringType()->notEmpty()->validate($this->getIndicativoNaval());
        if (!$value || !Utils::checkLength($this->getIndicativoNaval(), 6, 6)) {
            msg::showMsg('O campo Indicativo Naval deve ser preenchido '
                . 'corretamente <strong>com 6 caracteres</strong>.'
                . '<script>focusOn("naval_indicative");</script>', 'danger');
        }
        return $this;
    }

    private function validaAgenteFiscal()
    {
        $value = v::stringType()->notEmpty()->validate($this->getAgenteFiscal());
        if (!$value || !Utils::checkLength($this->getAgenteFiscal(), 3, 100)) {
            msg::showMsg('O campo Agente Fiscal deve ser preenchido '
                . 'corretamente <strong>com no mínimo 3 e máximo 100 caracteres</strong>.'
                . '<script>focusOn("fiscal_agent");</script>', 'danger');
        }
        return $this;
    }

    private function validaAgenteFiscalPosto()
    {
        $value = v::stringType()->notEmpty()->validate($this->getAgenteFiscalPosto());
        if (!$value || !Utils::checkLength($this->getAgenteFiscalPosto(), 2, 50)) {
            msg::showMsg('O campo Agente Fiscal Posto deve ser preenchido '
                . 'corretamente <strong>com no mínimo 2 e máximo 50 caracteres</strong>.'
                . '<script>focusOn("fiscal_agent_graduation");</script>', 'danger');
        }
        return $this;
    }

    private function validaGestorMuniciamento()
    {
        $value = v::stringType()->notEmpty()->validate($this->getGestorMuniciamento());
        if (!$value || !Utils::checkLength($this->getGestorMuniciamento(), 3, 100)) {
            msg::showMsg('O campo Gestor Municiamento deve ser preenchido '
                . 'corretamente <strong>com no mínimo 3 e máximo 100 caracteres</strong>.'
                . '<script>focusOn("munition_manager");</script>', 'danger');
        }
        return $this;
    }

    private function validaGestorMuniciamentoPosto()
    {
        $value = v::stringType()->notEmpty()->validate($this->getGestorMuniciamentoPosto());
        if (!$value || !Utils::checkLength($this->getGestorMuniciamentoPosto(), 2, 50)) {
            msg::showMsg('O campo Gestor Municiamento Posto deve ser preenchido '
                . 'corretamente <strong>com no mínimo 2 e máximo 50 caracteres</strong>.'
                . '<script>focusOn("munition_manager_graduation");</script>', 'danger');
        }
        return $this;
    }

    private function validaFielMuniciamento()
    {
        $value = v::stringType()->notEmpty()->validate($this->getFielMuniciamento());
        if (!$value || !Utils::checkLength($this->getFielMuniciamento(), 3, 100)) {
            msg::showMsg('O campo Fiel Municiamento deve ser preenchido '
                . 'corretamente <strong>com no mínimo 3 e máximo 100 caracteres</strong>.'
                . '<script>focusOn("munition_fiel");</script>', 'danger');
        }
        return $this;
    }

    private function validaFielMuniciamentoPosto()
    {
        $value = v::stringType()->notEmpty()->validate($this->getFielMuniciamentoPosto());
        if (!$value || !Utils::checkLength($this->getFielMuniciamentoPosto(), 2, 50)) {
            msg::showMsg('O campo Fiel Municiamento Posto deve ser preenchido '
                . 'corretamente <strong>com no mínimo 10 e máximo 50 caracteres</strong>.'
                . '<script>focusOn("munition_fiel_graduation");</script>', 'danger');
        }
        return $this;
    }
}
