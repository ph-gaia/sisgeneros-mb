<?php
namespace App\Controllers;

use HTR\System\ControllerAbstract as Controller;
use HTR\Interfaces\ControllerInterface as CtrlInterface;
use HTR\Helpers\Access\Access;
use App\Models\OmModel;
use App\Config\Configurations as cfg;

class OmController extends Controller implements CtrlInterface
{

    private $access;

    public function __construct($bootstrap)
    {
        parent::__construct($bootstrap);
        $this->view->controller = cfg::DEFAULT_URI . 'om/';
        $this->access = new Access();
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'FISCAL', 'ORDENADOR', 'CONTROLADOR_OBTENCAO', 'CONTROLADOR_FINANCA']);
    }

    public function indexAction()
    {
        $this->verAction();
    }

    public function novoAction()
    {
        $this->view->title = 'Novo Registro';
        $this->render('form_novo');
    }

    public function editarAction()
    {
        $model = new OmModel();
        $this->view->title = 'Editando Registro';
        $this->view->result = $model->findById($this->getParametro('id'));
        $this->render('form_editar');
    }

    public function eliminarAction()
    {
        $model = new OmModel();
        $model->removerRegistro($this->getParametro('id'));
    }

    public function verAction()
    {
        $model = new OmModel();
        $this->view->title = 'Lista de Todas as OM';
        $model->paginator($this->getParametro('pagina'));
        $this->view->result = $model->getResultadoPaginator();
        $this->view->btn = $model->getNavePaginator();
        $this->render('index');
    }

    public function registraAction()
    {
        $model = new OmModel();
        $model->novoRegistro();
    }

    public function alteraAction()
    {
        $model = new OmModel();
        $model->editarRegistro();
    }
}
