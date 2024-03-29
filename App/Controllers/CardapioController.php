<?php
namespace App\Controllers;

use HTR\System\ControllerAbstract as Controller;
use HTR\Interfaces\ControllerInterface as CtrlInterface;
use HTR\Helpers\Access\Access;
use App\Models\CardapioModel;
use App\Models\SolicitacaoModel;
use App\Models\OmModel;
use App\Models\MealsModel;
use App\Models\RecipesModel;
use App\Models\RecipesItemsModel;
use App\Models\RecipesPatternsModel;
use App\Config\Configurations as cfg;
use App\Helpers\Pdf;

class CardapioController extends Controller implements CtrlInterface
{

    private $access;

    public function __construct($bootstrap)
    {
        parent::__construct($bootstrap);

        $this->view->controller = cfg::DEFAULT_URI . 'cardapio/';
        $this->access = new Access();
    }

    public function indexAction()
    {
        $this->verAction();
    }

    public function novoAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'ENCARREGADO', 'NORMAL', 'FISCAL', 'FISCAL_SUBSTITUTO']);
        $this->view->title = 'Novo Registro';
        $modelOms = new OmModel();
        $modelMeals = new MealsModel();
        $modelRecipes = new RecipesPatternsModel();
        $this->view->resultOms = $modelOms->findAll();
        $this->view->resultMeals = $modelMeals->findAll();
        $this->view->resultRecipes = $modelRecipes->findAll();
        $this->render('form_novo');
    }

    public function detalharAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'ENCARREGADO', 'NORMAL', 'FISCAL', 'FISCAL_SUBSTITUTO']);
        $model = new CardapioModel();
        $this->view->title = 'Detalhes do cardápio';
        $this->view->result = $model->findById($this->getParametro('id'));
        $this->view->recipes = (new RecipesModel())->findByRecipeByMenuId($this->getParametro('id'));
        $this->render('form_editar');
    }

    public function detalharItemsAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'ENCARREGADO', 'NORMAL', 'FISCAL', 'FISCAL_SUBSTITUTO']);
        $model = new RecipesItemsModel();
        $this->view->title = 'Detalhes dos ingredientes';
        $this->view->result = $model->findByRecipe($this->getParametro('idRecipes'));
        $this->render('form_detalhar_items');
    }

    public function editarRecipesAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'ENCARREGADO', 'NORMAL', 'FISCAL', 'FISCAL_SUBSTITUTO']);
        $this->view->title = 'Editar receita';
        $modelMeals = new MealsModel();
        $modelRecipes = new RecipesPatternsModel();
        $this->view->result = (new RecipesModel())->findById($this->getParametro('idRecipes'));
        $this->view->resultMeals = $modelMeals->findAll();
        $this->view->resultRecipes = $modelRecipes->findAll();
        $this->render('form_editar_recipe');
    }

    public function editarIngredientesAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'ENCARREGADO', 'NORMAL', 'FISCAL', 'FISCAL_SUBSTITUTO']);
        $this->view->title = 'Editar itens do cardápio';
        $this->view->result = (new RecipesItemsModel())->findById($this->getParametro('itemId'));
        $this->render('form_editar_items');
    }

    public function alteraMenuDaysAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'ENCARREGADO', 'NORMAL', 'FISCAL', 'FISCAL_SUBSTITUTO']);
        $model = new CardapioModel();
        $model->atualizaDataCardapio();
    }

    public function eliminarMenuAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'ENCARREGADO', 'NORMAL', 'FISCAL', 'FISCAL_SUBSTITUTO']);
        $model = new CardapioModel();
        $model->removerMenu($this->getParametro('id'));
    }

    public function eliminarAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'ENCARREGADO', 'NORMAL', 'FISCAL', 'FISCAL_SUBSTITUTO']);
        $model = new CardapioModel();
        $model->removerRegistro($this->getParametro('id'), $this->getParametro('menusId'));
    }

    public function verAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'ENCARREGADO', 'ORDENADOR', 'ORDENADOR_SUBSTITUTO',  'NORMAL', 'FISCAL', 'FISCAL_SUBSTITUTO']);
        $model = new CardapioModel();
        $this->view->title = 'Lista de Todos os Cardápios';
        $model->paginator($this->getParametro('pagina'));
        $this->view->result = $model->getResultadoPaginator();
        $this->view->btn = $model->getNavePaginator();
        $this->render('index');
    }
    
    public function itensNaoLicitadosAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'ENCARREGADO', 'NORMAL', 'FISCAL', 'FISCAL_SUBSTITUTO']);
        $solicitacaoModel = new SolicitacaoModel();
        $this->view->title = 'Forncedores e itens não encontrados';
        $this->view->result = $solicitacaoModel->requestItemsNaoLicitadosByMenu($this->getParametro('id'));
        $this->render('mostra_itens_nao_licitados');
    }

    public function pdfAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('cardapio/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'ENCARREGADO', 'NORMAL', 'FISCAL', 'FISCAL_SUBSTITUTO']);

        $id = $this->getParametro('id');
        $pdf = new Pdf();
        $pdf->number = $id;
        $pdf->url = $this->view->controller . 'relatorioCardapio/id/' . $id;
        $pdf->gerar();
    }

    public function relatorioCardapioAction()
    {
        $model = new CardapioModel();
        $this->view->title = 'Cardápio semanal';
        $this->view->result = $model->returnsDataFromMenus(intval($this->getParametro('id')));
        $this->render('relatorio_cardapio', true, 'blank');
    }

    public function registraAction()
    {
        $user = $this->access->authenticAccess(['ADMINISTRADOR', 'ENCARREGADO', 'NORMAL', 'FISCAL', 'FISCAL_SUBSTITUTO']);
        $model = new CardapioModel();
        $model->novoRegistro($user);
    }

    public function aprovarAction()
    {
        $user = $this->access->authenticAccess(['ADMINISTRADOR', 'ENCARREGADO']);
        $model = new CardapioModel();
        $model->aprovar($this->getParametro('id'), $user);
    }

    public function gerarSolicitacoesAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'ENCARREGADO', 'NORMAL', 'FISCAL', 'FISCAL_SUBSTITUTO']);
        $requestsNumbers = (new SolicitacaoModel())->gerarSolicitacoes($this->getParametro('id'));
        $this->view->urlRedirect = cfg::DEFAULT_URI . "solicitacao/ver";

        if (!empty($requestsNumbers)) {
            $this->view->requestsNumbers = implode(', ', $requestsNumbers);
            $this->view->title = "Solicitações Geradas com sucesso";
            $this->render('mensagem_geracao_sucesso');
        } else {
            header("Location: {$this->view->urlRedirect}");
        }
    }

    public function alteraAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'ENCARREGADO', 'NORMAL', 'FISCAL', 'FISCAL_SUBSTITUTO']);
        $model = new CardapioModel();
        $model->editarRegistro();
    }

    public function alteraRecipesAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'ENCARREGADO', 'NORMAL', 'FISCAL', 'FISCAL_SUBSTITUTO']);
        $model = new RecipesModel();
        $model->editarRegistro();
    }

    public function alteraItemsAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'ENCARREGADO', 'NORMAL', 'FISCAL', 'FISCAL_SUBSTITUTO']);
        $model = new RecipesItemsModel();
        $model->editarRegistro();
    }

    public function checkdateAction()
    {
        $user = $this->access->authenticAccess(['ADMINISTRADOR', 'ENCARREGADO', 'NORMAL', 'FISCAL', 'FISCAL_SUBSTITUTO']);
        $omId = intval($user['oms_id'] ?? 0);
        $result = (new CardapioModel())->checkDate($this->getParametro('value'), $omId);
        echo json_encode($result);
    }
}
