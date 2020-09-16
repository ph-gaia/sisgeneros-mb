<?php

namespace App\Controllers;

use HTR\System\ControllerAbstract as Controller;
use HTR\Interfaces\ControllerInterface as CtrlInterface;
use HTR\Helpers\Access\Access;
use App\Models\RecipesPatternsModel;
use App\Models\IngredientesModel;
use App\Models\RecipesPatternsItemsModel;
use App\Config\Configurations as cfg;

class RecipespatternsController extends Controller implements CtrlInterface
{
    private $access;

    public function __construct($bootstrap)
    {
        parent::__construct($bootstrap);

        $this->view->controller = cfg::DEFAULT_URI . 'recipespatterns/';
        $this->access = new Access();
    }

    public function indexAction()
    {
        $this->verAction();
    }

    public function novoAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR_OBTENCAO']);
        $this->view->title = 'Novo Registro';
        $this->view->resultIngredients = (new IngredientesModel())->findAll();
        $this->render('form_novo');
    }

    public function editarAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess(['NORMAL', 'ENCARREGADO', 'FISCAL', 'ORDENADOR', 'ADMINISTRADOR', 'CONTROLADOR_OBTENCAO']);
        $model = new RecipesPatternsModel();
        $this->view->title = 'Editando Registro';
        $this->view->result = $model->findById($this->getParametro('id'));
        $this->view->resultIngredients = (new IngredientesModel())->findAll();
        $this->view->resultIngredientsPatterns = (new RecipesPatternsItemsModel())->findByidRecipes($this->getParametro('id'));
        $this->render('form_editar');
    }

    public function eliminarAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR_OBTENCAO']);
        $model = new RecipesPatternsModel();
        $model->removerRegistro($this->getParametro('id'));
    }

    public function eliminarIngredienteAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR_OBTENCAO']);
        $model = new RecipesPatternsItemsModel();
        $model->removerRegistro($this->getParametro('id'));
    }

    public function verAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR_OBTENCAO', 'ENCARREGADO', 'NORMAL', 'FISCAL', 'ORDENADOR']);
        $model = new RecipesPatternsModel();
        $this->view->title = 'Lista de Todas as Receitas Padrões';
        $model->paginator($this->getParametro('pagina'));
        $this->view->result = $model->getResultadoPaginator();
        $this->view->btn = $model->getNavePaginator();
        $this->render('index');
    }

    public function registraAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR_OBTENCAO']);
        $model = new RecipesPatternsModel();
        $model->novoRegistro();
    }

    public function alteraAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR_OBTENCAO']);
        $model = new RecipesPatternsModel();
        $model->editarRegistro();
    }

    public function findRecipeItemsByRecipesIdAction()
    {
        $model = new RecipesPatternsModel();
        $result = $model->findRecipeItemsByRecipesId($this->getParametro('id'));
        echo json_encode($result);
    }
}
