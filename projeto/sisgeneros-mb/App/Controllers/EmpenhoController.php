<?php

namespace App\Controllers;

use HTR\System\ControllerAbstract as Controller;
use HTR\Interfaces\ControllerInterface as CtrlInterface;
use HTR\Helpers\Access\Access;
use App\Models\EmpenhoModel;
use App\Models\SolicitacaoItemModel as SolicitacaoItem;
use App\Models\SolicitacaoModel;
use App\Config\Configurations as cfg;
use App\Models\HistoricoEmpenhoModel;

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
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR', 'FISCAL', 'ENCARREGADO', 'NORMAL', 'ORDENADOR', 'CONTROLADOR_OBTENCAO', 'CONTROLADOR_FINANCA']);
        $model = new EmpenhoModel();
        $this->view->title = 'Lista de Todos os Empenhos';
        $model->paginator($this->getParametro('pagina'), $this->view->userLoggedIn, $this->getParametro('busca'));
        $this->view->result = $model->getResultadoPaginator();
        $this->view->btn = $model->getNavePaginator();
        $this->render('index');
    }

    public function detalharAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR', 'FISCAL', 'ENCARREGADO', 'NORMAL', 'ORDENADOR', 'CONTROLADOR_OBTENCAO', 'CONTROLADOR_FINANCA']);
        $model = new EmpenhoModel();
        $solicitacao = new SolicitacaoModel();
        $historico = new HistoricoEmpenhoModel();
        $this->view->title = 'Pedidos do empenho';
        $this->view->resultEmpenho = $model->findByIdlista($this->getParametro('idlista'));
        $this->view->resultPedidos = $solicitacao->findByInvoiceId($this->getParametro('idlista'));
        $this->view->historico = $historico->historicByInvoiceId($this->getParametro('idlista'));

        $this->render('detalhar');
    }

    public function abaterValorEmpenhoAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR', 'FISCAL', 'ORDENADOR', 'CONTROLADOR_OBTENCAO', 'CONTROLADOR_FINANCA']);
        $model = new EmpenhoModel();
        $model->abaterValor($this->view->userLoggedIn['id']);
    }
}
