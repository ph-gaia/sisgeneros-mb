<?php
namespace App\Controllers;

use HTR\System\ControllerAbstract as Controller;
use HTR\Interfaces\ControllerInterface as CtrlInterface;
use HTR\Helpers\Access\Access;
use App\Models\OmModel;
use App\Models\AcessoModel;
use App\Config\Configurations as cfg;

class AcessoController extends Controller implements CtrlInterface
{

    private $access;

    public function __construct($bootstrap)
    {
        parent::__construct($bootstrap);
        $this->view->controller = cfg::DEFAULT_URI . 'acesso/';
        $this->access = new Access();
    }

    public function indexAction()
    {
        $this->verAction();
    }

    public function novoAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR_OBTENCAO']);
        $om = new OmModel();
        $this->view->resultOm = $om->findAll();
        $this->view->title = 'Novo Registro';
        $this->render('form_novo');
    }

    public function editarAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR_OBTENCAO', 'CONTROLADOR_FINANCA', 'ENCARREGADO', 'NORMAL', 'FISCAL', 'FISCAL_SUBSTITUTO', 'ORDENADOR', 'ORDENADOR_SUBSTITUTO']);
        $om = new OmModel();
        $this->view->resultOm = $om->findAll();
        $model = new AcessoModel();
        $this->view->title = 'Editando Registro';
        $id = $this->getParametro('id');
        if (!in_array($this->view->userLoggedIn['level'], ['ADMINISTRADOR', 'CONTROLADOR_OBTENCAO'])) {
            $id = $this->view->userLoggedIn['id'];
        }
        $this->view->result = $model->findById($id);
        $this->render('form_editar');
    }

    public function eliminarAction()
    {
        $this->access->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR_OBTENCAO']);
        $model = new AcessoModel();
        $model->removerRegistro($this->getParametro('id'));
    }

    public function verAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR_OBTENCAO']);
        $model = new AcessoModel();
        $this->view->title = 'Lista de Todos os Usuários';
        $this->view->busca = $this->getParametro('busca');
        $model->paginator($this->getParametro('pagina'), $this->view->busca);
        $this->view->result = $model->getResultadoPaginator();
        $this->view->btn = $model->getNavePaginator();
        $this->render('index');
    }

    public function registraAction()
    {
        $this->access->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR_OBTENCAO']);
        $model = new AcessoModel();
        $model->novoRegistro();
    }

    public function alteraAction()
    {
        $this->access->authenticAccess(['ADMINISTRADOR', 'ENCARREGADO', 'NORMAL', 'FISCAL', 'FISCAL_SUBSTITUTO', 'ORDENADOR', 'ORDENADOR_SUBSTITUTO', 'CONTROLADOR_OBTENCAO', 'CONTROLADOR_FINANCA']);
        $model = new AcessoModel();
        $model->editarRegistro();
    }

    public function loginAction()
    {
        $this->access->notAuthenticatedAccess();
        $this->view->title = 'Autenticação de Usuário';
        $this->render('form_login', true, 'blank');
    }

    public function logoutAction()
    {
        $model = new AcessoModel();
        $model->logout();
        header('Location:' . cfg::DEFAULT_URI);
    }

    public function autenticaAction()
    {
        $model = new AcessoModel();
        $model->login();
    }

    public function mudarSenhaAction()
    {
        $this->access->breakRedirect();
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'ENCARREGADO', 'NORMAL', 'FISCAL', 'FISCAL_SUBSTITUTO', 'ORDENADOR', 'ORDENADOR_SUBSTITUTO', 'CONTROLADOR_OBTENCAO', 'CONTROLADOR_FINANCA']);
        $this->view->title = "Mudando Senha";
        $this->render('form_mudar_senha');
    }

    public function mudandoSenhaAction()
    {
        $model = new AcessoModel();
        $this->access->breakRedirect();
        $user = $this->access->authenticAccess(['ADMINISTRADOR', 'ENCARREGADO', 'NORMAL', 'FISCAL', 'FISCAL_SUBSTITUTO', 'ORDENADOR', 'ORDENADOR_SUBSTITUTO', 'CONTROLADOR_OBTENCAO', 'CONTROLADOR_FINANCA']);
        $model->mudarSenha($user['id']);
    }
}
