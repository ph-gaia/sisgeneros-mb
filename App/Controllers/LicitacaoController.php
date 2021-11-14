<?php

namespace App\Controllers;

use HTR\System\ControllerAbstract as Controller;
use HTR\Interfaces\ControllerInterface as CtrlInterface;
use HTR\Helpers\Access\Access;
use App\Models\LicitacaoModel;
use App\Models\OmModel;
use App\Config\Configurations as cfg;

class LicitacaoController extends Controller implements CtrlInterface
{

    private $access;

    public function __construct($bootstrap)
    {
        parent::__construct($bootstrap);
        $this->view->controller = cfg::DEFAULT_URI . 'licitacao/';
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
        $this->view->resultOms = (new OmModel())->findAll();
        $this->render('form_novo');
    }

    public function editarAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR_OBTENCAO']);
        $model = new LicitacaoModel();
        $this->view->title = 'Editando Registro';
        $result = $model->fetchDataToEdit((int) $this->getParametro('id'));
        $this->view->resultOms = $result['oms'];
        $this->view->result = $result['result'];
        $this->render('form_editar');
    }

    public function eliminarAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR_OBTENCAO']);
        $model = new LicitacaoModel();
        $model->removerRegistro($this->getParametro('id'));
    }

    public function verAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR_OBTENCAO', 'NORMAL', 'ENCARREGADO', 'FISCAL', 'FISCAL_SUBSTITUTO', 'ORDENADOR', 'ORDENADOR_SUBSTITUTO']);
        $model = new LicitacaoModel();
        $this->view->title = 'Lista de Todas as Licitações';
        $model->paginator($this->getParametro('pagina'), null, $this->view->userLoggedIn['oms_id']);
        $this->view->result = $model->getResultadoPaginator();
        $this->view->btn = $model->getNavePaginator();
        $this->render('index');
    }

    public function registraAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR_OBTENCAO']);
        $model = new LicitacaoModel();
        $model->novoRegistro();
    }

    public function alteraAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR_OBTENCAO']);
        $model = new LicitacaoModel();
        $model->editarRegistro();
    }

    public function ingrendientesAction()
    {
        $result = (new LicitacaoModel())->findAllByIngredientsId(intval($this->getParametro('id')));
        echo json_encode($result);
    }

    public function eliminaromAction()
    {
        $id = $this->getParametro('id');
        $avisoId = $this->getParametro('avisoid');
        if ($id && $avisoId) {
            (new LicitacaoModel())->eliminarOm((int) $id, (int) $avisoId);
        } else {
            header('Location: ' . $this->view->controller);
        }
    }

    public function adicionaromAction()
    {
        $id = $this->getParametro('id');
        if ($id) {
            $result = (new LicitacaoModel())->fetchOmOut((int) $id);
            if (count($result)) {
                $this->view->title = 'Adicionar nova OM';
                $this->view->result = $result;

                $this->render('form_adicionar_om');
            } else {
                header('Location: ' . $this->view->controller . 'editar/id/' . $id);
            }
        } else {
            header('Location: ' . $this->view->controller);
        }
    }

    public function registrarnovaomAction()
    {
        (new LicitacaoModel())->adicionarNovaOM();
    }
}
