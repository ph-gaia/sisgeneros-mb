<?php

namespace App\Controllers;

use HTR\System\ControllerAbstract as Controller;
use HTR\Interfaces\ControllerInterface as CtrlInterface;
use HTR\Helpers\Access\Access;
use App\Models\EmpenhoModel;
use App\Models\EmpenhoItemsModel;
use App\Models\SolicitacaoEmpenhoModel;
use App\Models\SolicitacaoModel;
use App\Config\Configurations as cfg;
use App\Helpers\Pdf;

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
        $this->view->userLoggedIn = $this->access->authenticAccess(['NORMAL', 'ENCARREGADO', 'FISCAL', 'FISCAL_SUBSTITUTO', 'ORDENADOR', 'ORDENADOR_SUBSTITUTO', 'ADMINISTRADOR', 'CONTROLADOR_FINANCA']);
        $model = new EmpenhoModel();
        $this->view->title = 'Lista de Todos os Empenhos';
        $model->paginator($this->getParametro('pagina'), $this->view->userLoggedIn, $this->getParametro('busca'));
        $this->view->result = $model->getResultadoPaginator();
        $this->view->btn = $model->getNavePaginator();
        $this->render('index');
    }

    public function detalharAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess(['ENCARREGADO', 'NORMAL', 'FISCAL', 'FISCAL_SUBSTITUTO', 'ORDENADOR', 'ORDENADOR_SUBSTITUTO', 'ADMINISTRADOR', 'CONTROLADOR_FINANCA']);
        $model = new EmpenhoModel();
        $items = new EmpenhoItemsModel();
        $itemsEmpenho = new SolicitacaoEmpenhoModel();
        $this->view->title = 'Detalhes do empenho';
        $this->view->idlista = $this->getParametro('idlista');
        $this->view->resultEmpenho = $model->findByIdlista($this->getParametro('idlista'));
        $items->paginator($this->getParametro('pagina'), $this->getParametro('idlista'));
        $this->view->result = $items->getResultadoPaginator();
        $this->view->btn = $items->getNavePaginator();

        $this->view->resultItems = $itemsEmpenho->findAllByInvoiceId($this->getParametro('idlista'));

        $this->render('detalhar');
    }

    public function novoAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR_FINANCA']);
        $this->view->title = 'Novo registro de empenho';

        $solicitacao = new SolicitacaoModel();
        $solicitacao->paginator($this->getParametro('pagina'), $this->view->userLoggedIn, 'CONFERIDO');
        $this->view->result = $solicitacao->getResultadoPaginator();

        $this->render('form_novo');
    }

    public function receberAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'NORMAL', 'ENCARREGADO']);

        $this->view->title = 'Lista de itens solicitados';

        $empenho = new EmpenhoModel();
        $itens = new EmpenhoItemsModel();
        //$this->view->resultSolicitacao = $empenho->findByinvoices_id($this->getParametro('id'));
        $this->view->result = $empenho->retornaDadosPapeleta($this->getParametro('id'), $this->getParametro('idlista'), $this->view->userLoggedIn);

        $this->render('mostra_item_recebimento');
    }

    public function solicitacoesAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'NORMAL', 'ENCARREGADO', 'CONTROLADOR_FINANCA', 'FISCAL', 'FISCAL_SUBSTITUTO', 'ORDENADOR', 'ORDENADOR_SUBSTITUTO']);

        $this->view->title = 'Lista de solicitações de empresas';

        $model = new SolicitacaoEmpenhoModel();
        $model->paginator($this->getParametro('pagina'), $this->getParametro('busca'), $this->view->userLoggedIn);
        $this->view->result = $model->getResultadoPaginator();
        $this->view->btn = $model->getNavePaginator();

        $this->render('solicitacao_empenho');
    }

    public function registrarrecebimentoAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ENCARREGADO', 'NORMAL', 'ADMINISTRADOR']);

        $solicitacao = new SolicitacaoEmpenhoModel();
        $solicitacao->recebimento($this->view->userLoggedIn);
    }

    public function pdfAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ENCARREGADO', 'NORMAL', 'ADMINISTRADOR', 'FISCAL', 'FISCAL_SUBSTITUTO', 'ENCARREGADO', 'NORMAL', 'ORDENADOR', 'ORDENADOR_SUBSTITUTO', 'CONTROLADOR_OBTENCAO', 'CONTROLADOR_FINANCA']);

        $id = $this->getParametro('id');
        $invoiceId = $this->getParametro('idlista');
        $pdf = new Pdf();
        $pdf->number = $id;
        $pdf->url = $this->view->controller . 'papeleta/id/' . $id . '//idlista/' . $invoiceId;
        $pdf->gerar();
    }

    public function papeletaAction()
    {
        $model = new EmpenhoModel();
        $this->view->title = 'Solicitação de Material';
        $this->view->result = $model->retornaDadosPapeleta($this->getParametro('id'), $this->getParametro('idlista'));
        $this->render('papeleta_solicitacao', true, 'blank');
    }

    public function registraAction()
    {
        $user = $this->access->authenticAccess(['ENCARREGADO', 'NORMAL', 'ADMINISTRADOR', 'CONTROLADOR_FINANCA']);
        $model = new EmpenhoModel();
        $model->novoRegistro($user);
    }

    public function editarAction()
    {
        $user = $this->access->authenticAccess(['ENCARREGADO', 'NORMAL', 'ADMINISTRADOR', 'CONTROLADOR_FINANCA']);
        $model = new EmpenhoModel();
        $model->editarRegistro();
    }

    public function cancelaAction()
    {
        $user = $this->access->authenticAccess(['ENCARREGADO', 'NORMAL', 'ADMINISTRADOR', 'CONTROLADOR_FINANCA']);
        $model = new SolicitacaoEmpenhoModel();
        $model->cancelarRegistro($this->getParametro('id'), $this->getParametro('idlista'));
    }

    public function registraItensEmpenhoAction()
    {
        $user = $this->access->authenticAccess(['ENCARREGADO', 'NORMAL', 'ADMINISTRADOR', 'CONTROLADOR_FINANCA']);
        $model = new SolicitacaoEmpenhoModel();
        $model->novoRegistro();
    }

    public function entregarNfAction()
    {
        $user = $this->access->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR_FINANCA']);
        $model = new SolicitacaoEmpenhoModel();
        $model->entregarNf();
    }

    public function liquidarNfAction()
    {
        $user = $this->access->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR_FINANCA']);
        $model = new SolicitacaoEmpenhoModel();
        $model->liquidarNf();
    }

    public function pagarNfAction()
    {
        $user = $this->access->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR_FINANCA']);
        $model = new SolicitacaoEmpenhoModel();
        $model->pagarNf();
    }

    public function cancelarEmpenhoAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess(['ENCARREGADO', 'NORMAL', 'ADMINISTRADOR', 'CONTROLADOR_FINANCA']);
        $model = new EmpenhoModel();
        $items = new EmpenhoItemsModel();

        $this->view->title = 'Cancelar itens do empenho';
        $this->view->idlista = $this->getParametro('idlista');
        $this->view->resultEmpenho = $model->findByIdlista($this->getParametro('idlista'));
        $items->paginator($this->getParametro('pagina'), $this->getParametro('idlista'));
        $this->view->result = $items->getResultadoPaginator();
        $this->view->btn = $items->getNavePaginator();

        $this->render('cancelar_empenho');
    }

    public function cancelarItensEmpenhoAction()
    {
        $user = $this->access->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR_FINANCA']);
        $model = new EmpenhoModel();
        $model->cancelarEmpenho($user);
    }
}
