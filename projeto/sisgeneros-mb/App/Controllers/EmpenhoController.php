<?php

namespace App\Controllers;

use HTR\System\ControllerAbstract as Controller;
use HTR\Interfaces\ControllerInterface as CtrlInterface;
use HTR\Helpers\Access\Access;
use App\Models\EmpenhoModel;
use App\Models\EmpenhoItemsModel;
use App\Models\SolicitacaoItemModel as SolicitacaoItem;
use App\Models\SolicitacaoModel;
use App\Config\Configurations as cfg;

class EmpenhoController extends Controller implements CtrlInterface
{

    private $access;

    public function __construct($bootstrap)
    {
        parent::__construct($bootstrap);

        $this->view->controller = cfg::DEFAULT_URI . 'empenho/';
        $this->access = new Access();
    }

    public function indexAction()
    {
        $this->verAction();
    }

    public function verAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR','CONTROLADOR_FINANCA']);
        $model = new EmpenhoModel();
        $this->view->title = 'Lista de Todos os Empenhos';
        $model->paginator($this->getParametro('pagina'), $this->view->userLoggedIn, $this->getParametro('busca'));
        $this->view->result = $model->getResultadoPaginator();
        $this->view->btn = $model->getNavePaginator();
        $this->render('index');
    }

    public function detalharAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR_FINANCA']);
        $model = new EmpenhoModel();
        $items = new EmpenhoItemsModel();
        $this->view->title = 'Itens do empenho';
        $this->view->resultEmpenho = $model->findByIdlista($this->getParametro('idlista'));
        $items->paginator($this->getParametro('pagina'), $this->getParametro('idlista'));
        $this->view->result = $items->getResultadoPaginator();
        $this->view->btn = $items->getNavePaginator();

        $this->render('detalhar');
    }

    public function novoAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR','CONTROLADOR_FINANCA']);
        $this->view->title = 'Novo registro de empenho';

        $solicitacao = new SolicitacaoModel();
        $solicitacao->paginator($this->getParametro('pagina'), $this->view->userLoggedIn, 'CONFERIDO');
        $this->view->result = $solicitacao->getResultadoPaginator();

        $this->render('form_novo');
    }

    public function registraAction()
    {
        $user = $this->access->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR_FINANCA']);
        $model = new EmpenhoModel();
        $model->novoRegistro($user['oms_id']);
    }
}
