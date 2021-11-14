<?php

namespace App\Controllers;

use HTR\System\ControllerAbstract as Controller;
use HTR\Interfaces\ControllerInterface as CtrlInterface;
use HTR\Helpers\Access\Access;
use App\Models\LicitacaoModel as Licitacao;
use App\Models\ItemModel as Item;
use App\Models\SolicitacaoItemModel as SolicitacaoItem;
use App\Models\SolicitacaoModel;
use App\Models\FornecedorModel;
use App\Models\OmModel;
use App\Models\CreditoProvisionadoModel;
use App\Models\HistoricoAcaoModel;
use App\Helpers\Pdf;
use App\Config\Configurations as cfg;
use App\Models\SolicitacaoEmpenhoModel;
use HTR\Helpers\Mensagem\Mensagem as msg;

class SolicitacaoController extends Controller implements CtrlInterface
{

    private $access;

    public function __construct($bootstrap)
    {
        parent::__construct($bootstrap);
        $this->view->controller = cfg::DEFAULT_URI . 'solicitacao/';
        $this->access = new Access();
        $this->view->idlista = $this->getParametro('idlista');
    }

    public function indexAction()
    {
        $this->verAction();
    }

    public function licitacaoAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'FISCAL', 'FISCAL_SUBSTITUTO', 'ENCARREGADO', 'NORMAL', 'ORDENADOR', 'ORDENADOR_SUBSTITUTO', 'CONTROLADOR_OBTENCAO', 'CONTROLADOR_FINANCA']);

        $licitacao = new Licitacao();
        $this->view->title = 'Licitações Disponíveis';
        $licitacao->paginator($this->getParametro('pagina'), date("Y-m-d", time()), $this->view->userLoggedIn['oms_id']);
        $this->view->result = $licitacao->getResultadoPaginator();
        $this->view->btn = $licitacao->getNavePaginator();
        $this->render('mostra_licitacao_disponivel');
    }

    public function formnaolicitadoAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'FISCAL', 'FISCAL_SUBSTITUTO', 'ENCARREGADO', 'NORMAL', 'ORDENADOR', 'ORDENADOR_SUBSTITUTO', 'CONTROLADOR_OBTENCAO', 'CONTROLADOR_FINANCA']);

        $this->view->title = "Adicionar itens";
        $this->view->resultFornecedor = (new FornecedorModel())->findAll(function ($e) {
            return $e->setaCampos(['id', 'name', 'cnpj'])
                ->setaFiltros()
                ->orderBy('suppliers.name ASC');
        });
        $this->view->resultOm = (new OmModel())->findById($this->view->userLoggedIn['oms_id']);
        $this->render('mostra_item_nao_licitado');
    }

    public function itemAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'FISCAL', 'FISCAL_SUBSTITUTO', 'ENCARREGADO', 'NORMAL', 'ORDENADOR', 'ORDENADOR_SUBSTITUTO', 'CONTROLADOR_OBTENCAO', 'CONTROLADOR_FINANCA']);

        $this->view->title = 'Lista dos Itens da Licitação';
        $item = new Item();
        $this->view->result = $item->findByIdlista($this->view->idlista, $this->getParametro('fornecedor'));
        $licitacao = new Licitacao();
        $this->view->resultLicitacao = $licitacao->findById($this->view->idlista);
        $this->view->resultOm = (new OmModel())->findById($this->view->userLoggedIn['oms_id']);
        $this->view->credito = (new CreditoProvisionadoModel())->findByOmId($this->view->userLoggedIn['oms_id']);
        $this->view->creditoComprometido = (new CreditoProvisionadoModel())->saldoComprometido($this->view->userLoggedIn['oms_id']);
        $this->render('mostra_item');
    }

    public function receberAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'FISCAL', 'FISCAL_SUBSTITUTO', 'ENCARREGADO', 'NORMAL', 'ORDENADOR', 'ORDENADOR_SUBSTITUTO', 'CONTROLADOR_OBTENCAO', 'CONTROLADOR_FINANCA']);

        $this->view->title = 'Lista de itens solicitados';
        $solicitacao = new SolicitacaoModel();
        $licitacao = new Licitacao();
        $this->view->resultSolicitacao = $solicitacao->findByIdlista($this->getParametro('id'));
        $this->view->resultLicitacao = $licitacao->findById($this->view->resultSolicitacao['biddings_id']);
        $this->view->result = $solicitacao->retornaDadosPapeleta($this->getParametro('id'), $this->view->userLoggedIn, true);
        $this->render('mostra_item_recebimento');
    }

    public function editarAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'FISCAL', 'FISCAL_SUBSTITUTO', 'ENCARREGADO', 'NORMAL', 'ORDENADOR', 'ORDENADOR_SUBSTITUTO', 'CONTROLADOR_OBTENCAO', 'CONTROLADOR_FINANCA']);

        $model = new SolicitacaoModel();
        $solicitacaoItem = new SolicitacaoItem();
        $model->avaliaAcesso($this->view->idlista, $this->view->userLoggedIn);
        $this->view->title = 'Editando Registro';
        $this->view->solicitacao = $model->findById($this->getParametro('idlista'));
        $this->view->result = $solicitacaoItem->findById($this->getParametro('id'));
        $this->view->totalSolicitacao = $solicitacaoItem->findTotalValueByRequestId($this->view->idlista);
        $this->view->credito = (new CreditoProvisionadoModel())->findByOmId($this->view->userLoggedIn['oms_id']);
        $this->render('form_editar');
    }

    public function alterardataAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'FISCAL', 'FISCAL_SUBSTITUTO', 'ENCARREGADO', 'NORMAL', 'ORDENADOR', 'ORDENADOR_SUBSTITUTO', 'CONTROLADOR_OBTENCAO', 'CONTROLADOR_FINANCA']);

        $model = new SolicitacaoModel();
        $this->view->title = 'Alterando data de entrega';
        $this->view->result = $model->avaliaAcesso($this->getParametro('id'), $this->view->userLoggedIn);
        $this->render('form_alteracao_data');
    }

    public function historicoSolicitacaoAction()
    {
        $this->view->title = 'Histórico de ações na solicitação';

        $this->view->acoes = (new HistoricoAcaoModel())->allHistoricByRequestId($this->getParametro('id'));

        $this->render('mostra_historico_solicitacao', true, 'blank');
    }

    public function solicitarAction()
    {
        $result = (new SolicitacaoEmpenhoModel())->findByRequestId($this->getParametro('id'));

        header('Location: ' . cfg::DEFAULT_URI . 'empenho/detalhar/idlista/' . $result['invoices_id']);
    }

    public function itensLicitadosAction()
    {
        $this->view->busca = $this->getParametro('busca');

        if ($this->view->busca) {
            $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
                ->clearAccessList()
                ->authenticAccess(['ADMINISTRADOR', 'FISCAL', 'FISCAL_SUBSTITUTO', 'ENCARREGADO', 'NORMAL', 'ORDENADOR', 'ORDENADOR_SUBSTITUTO', 'CONTROLADOR_OBTENCAO', 'CONTROLADOR_FINANCA']);

            $licitacao = new Licitacao();
            $this->view->title = 'Forncedores e itens encontrados';
            $this->view->result = $licitacao->listaItemFornecedor($this->view->userLoggedIn['oms_id'], $this->getParametro('busca'));
            $this->render('mostra_item_buscado');
        } else {
            $this->licitacaobuscaAction();
        }
    }

    public function eliminarAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'FISCAL', 'FISCAL_SUBSTITUTO', 'ENCARREGADO', 'NORMAL', 'ORDENADOR', 'ORDENADOR_SUBSTITUTO', 'CONTROLADOR_OBTENCAO', 'CONTROLADOR_FINANCA']);

        $model = new SolicitacaoModel();
        $solicitacaoItem = new SolicitacaoItem();
        $model->avaliaAcesso($this->getParametro('id'), $this->view->userLoggedIn);
        if ($solicitacaoItem->removerRegistro($this->getParametro('id'))) {
            $model->removerRegistro($this->getParametro('id'));
        }
    }

    public function eliminarItemAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'FISCAL', 'FISCAL_SUBSTITUTO', 'ENCARREGADO', 'NORMAL', 'ORDENADOR', 'ORDENADOR_SUBSTITUTO', 'CONTROLADOR_OBTENCAO', 'CONTROLADOR_FINANCA']);

        $model = new SolicitacaoModel();
        $model->avaliaAcesso($this->view->idlista, $this->view->userLoggedIn);
        $solicitacaoItem = new SolicitacaoItem();
        $solicitacaoItem->eliminarItem($this->getParametro('id'), $this->view->idlista);
    }

    public function verAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess([
            'ADMINISTRADOR',
            'FISCAL',
            'FISCAL_SUBSTITUTO',
            'ENCARREGADO',
            'NORMAL',
            'ORDENADOR',
            'ORDENADOR_SUBSTITUTO',
            'CONTROLADOR_OBTENCAO',
            'CONTROLADOR_FINANCA'
        ]);
        $model = new SolicitacaoModel();
        $this->view->title = 'Histórico de Solicitações';
        $model->paginator($this->getParametro('pagina'), $this->view->userLoggedIn, $this->getParametro('busca'), null, null, null, $this->getParametro('ordenar'));
        $this->view->result = $model->getResultadoPaginator();
        $this->view->btn = $model->getNavePaginator();

        $this->render('index');
    }

    public function detalharAction()
    {
        $this->view->userLoggedIn = $this->access->authenticAccess([
            'ADMINISTRADOR',
            'FISCAL',
            'FISCAL_SUBSTITUTO',
            'ENCARREGADO',
            'NORMAL',
            'ORDENADOR',
            'ORDENADOR_SUBSTITUTO',
            'CONTROLADOR_OBTENCAO',
            'CONTROLADOR_FINANCA'
        ]);
        $model = new SolicitacaoModel();
        $licitacao = new Licitacao();
        $solicitacaoItem = new SolicitacaoItem();
        $this->view->title = 'Itens da Solicitação';
        $this->view->resultSolicitacao = $model->findByIdlista($this->view->idlista);
        $this->view->resultLicitacao = $licitacao->findById($this->view->resultSolicitacao['biddings_id']);
        $solicitacaoItem->paginator($this->getParametro('pagina'), $this->view->idlista);
        $this->view->result = $solicitacaoItem->getResultadoPaginator();
        $this->view->btn = $solicitacaoItem->getNavePaginator();
        $this->view->totalSolicitacao = $solicitacaoItem->findTotalValueByRequestId($this->view->idlista);
        $this->view->credito = (new CreditoProvisionadoModel())->findByOmId($this->view->userLoggedIn['oms_id']);
        $this->view->creditoComprometido = (new CreditoProvisionadoModel())->saldoComprometido($this->view->userLoggedIn['oms_id']);
        $this->render('mostra_item_solicitacao');
    }

    public function registraAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'FISCAL', 'FISCAL_SUBSTITUTO', 'ENCARREGADO', 'NORMAL', 'ORDENADOR', 'ORDENADOR_SUBSTITUTO', 'CONTROLADOR_OBTENCAO', 'CONTROLADOR_FINANCA']);

        $model = new SolicitacaoModel();
        $model->novoRegistro($this->view->userLoggedIn);
    }

    public function registranaolicitadoAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'FISCAL', 'FISCAL_SUBSTITUTO', 'ENCARREGADO', 'NORMAL', 'ORDENADOR', 'ORDENADOR_SUBSTITUTO', 'CONTROLADOR_OBTENCAO', 'CONTROLADOR_FINANCA']);

        (new SolicitacaoModel())->novoNaoLicitado($this->view->userLoggedIn, getcwd());
    }

    public function alteraAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'FISCAL', 'FISCAL_SUBSTITUTO', 'ENCARREGADO', 'NORMAL', 'ORDENADOR', 'ORDENADOR_SUBSTITUTO', 'CONTROLADOR_OBTENCAO', 'CONTROLADOR_FINANCA']);

        $solicitacaoItem = new SolicitacaoItem();
        $solicitacaoItem->editarRegistro($this->view->idlista, $this->view->userLoggedIn);
    }

    public function rejeitarCancelarAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'FINANCAS', 'OBTENCAO', 'FISCAL', 'FISCAL_SUBSTITUTO', 'ENCARREGADO', 'NORMAL', 'ORDENADOR', 'ORDENADOR_SUBSTITUTO', 'CONTROLADOR_OBTENCAO', 'CONTROLADOR_FINANCA']);

        $model = new SolicitacaoModel();
        $model->rejeitarCancelar($this->view->userLoggedIn['id']);
    }

    public function provisionarAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'ENCARREGADO']);

        $model = new SolicitacaoModel();
        $model->provisionar($this->getParametro('id'), $this->view->userLoggedIn['id']);
    }

    public function encaminharAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'FINANCAS', 'OBTENCAO', 'FISCAL', 'FISCAL_SUBSTITUTO', 'ENCARREGADO', 'NORMAL', 'ORDENADOR', 'ORDENADOR_SUBSTITUTO', 'CONTROLADOR_OBTENCAO', 'CONTROLADOR_FINANCA']);

        $model = new SolicitacaoModel();
        $model->encaminhar($this->getParametro('id'), $this->view->userLoggedIn['id']);
    }

    public function pagarnfAction()
    {
        $this->access->setRedirect('solicitacao/');
        $this->access->clearAccessList();
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'FISCAL', 'FISCAL_SUBSTITUTO', 'FISCAL_SUBSTITUTO']);
        $model = new SolicitacaoModel();
        $model->pagarnf($this->getParametro('id'));
    }

    public function fornecedorAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'FISCAL', 'FISCAL_SUBSTITUTO', 'ENCARREGADO', 'NORMAL', 'ORDENADOR', 'ORDENADOR_SUBSTITUTO', 'CONTROLADOR_OBTENCAO', 'CONTROLADOR_FINANCA']);
        $licitacao = new Licitacao;

        $this->view->result = $licitacao->listaPorFornecedor($this->view->idlista);

        if (!$this->view->result) {
            header('Location: ' . $this->view->controller);
        }

        $this->view->title = 'Lista de fornecedor';
        $this->render('mostra_fornecedor');
    }

    public function pdfAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'FISCAL', 'FISCAL_SUBSTITUTO', 'ENCARREGADO', 'NORMAL', 'ORDENADOR', 'ORDENADOR_SUBSTITUTO', 'CONTROLADOR_OBTENCAO', 'CONTROLADOR_FINANCA']);

        $id = $this->getParametro('id');
        $pdf = new Pdf();
        $pdf->number = $id;
        $pdf->url = $this->view->controller . 'papeleta/id/' . $id;
        $pdf->gerar();
    }

    public function papeletaAction()
    {
        $model = new SolicitacaoModel();
        $this->view->title = 'Solicitação de Material';
        $this->view->result = $model->retornaDadosPapeleta($this->getParametro('id'));
        $this->render('papeleta_solicitacao', true, 'blank');
    }

    public function registrarrecebimentoAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'FISCAL', 'FISCAL_SUBSTITUTO', 'ENCARREGADO', 'NORMAL', 'ORDENADOR', 'ORDENADOR_SUBSTITUTO', 'CONTROLADOR_OBTENCAO', 'CONTROLADOR_FINANCA']);

        $solicitacao = new SolicitacaoModel();
        $resultSolicitacao = $solicitacao->findById($this->getParametro('id'));

        if ($resultSolicitacao['biddings_id']) {
            $solicitacao->recebimento($resultSolicitacao['id']);
        } else {
            $solicitacao->recebimentoNaoLicitado($resultSolicitacao['id']);
        }
    }

    public function processarAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess([
                'ADMINISTRADOR',
                'FISCAL',
                'FISCAL_SUBSTITUTO',
                'ENCARREGADO',
                'NORMAL',
                'ORDENADOR',
                'ORDENADOR_SUBSTITUTO',
                'CONTROLADOR_OBTENCAO',
                'CONTROLADOR_FINANCA'
            ]);

        $id = (int) $this->getParametro('id');
        $status = strtoupper($this->getParametro('status') ?? '');
        $action = strtoupper($this->getParametro('acao') ?? '');

        (new SolicitacaoModel())->processStatus($id, $status, $action, $this->view->userLoggedIn['id']);

        if ($status == 'AUTORIZADO' && $action == 'PROXIMO') {
            $solicitacao = (new SolicitacaoModel())->findById($id);
            header('location: '
                . $this->view->controller
                . 'detalhar/idlista/' . $solicitacao['id']);
        } else {
            header('location: ' . $this->view->controller);
        }
    }

    public function verificarEmLoteAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'FISCAL', 'FISCAL_SUBSTITUTO', 'ORDENADOR_SUBSTITUTO']);

        $ids = $this->getParametro('ids');
        $ids = explode(",", $ids);
        $status = 'PROVISIONADO';
        $action = 'PROXIMO';

        foreach ($ids as $id) {
            (new SolicitacaoModel())->processStatus($id, $status, $action, $this->view->userLoggedIn['id']);
        }

        header('location: ' . $this->view->controller);
    }

    public function autorizarAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'ORDENADOR', 'ORDENADOR_SUBSTITUTO']);

        $id = (int) $this->getParametro('id');

        (new SolicitacaoModel())->autorizar($id, $this->view->userLoggedIn);

        header('location: ' . $this->view->controller);
    }

    public function autorizarEmLoteAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'ORDENADOR', 'ORDENADOR_SUBSTITUTO']);

        $ids = $this->getParametro('ids');
        $ids = explode(",", $ids);

        foreach ($ids as $id) {
            (new SolicitacaoModel())->autorizar($id, $this->view->userLoggedIn);
        }
        header('location: ' . $this->view->controller);
    }

    public function presolempAction()
    {
        $model = new SolicitacaoModel();
        $licitacao = new Licitacao();
        $oms = new OmModel();
        $solicitacaoItem = new SolicitacaoItem();
        $historico = new HistoricoAcaoModel();

        $id = $this->getParametro('id');
        $this->view->resultSolicitacao = $model->findByIdlista($id);
        $this->view->resultLicitacao = $licitacao->findById($this->view->resultSolicitacao['biddings_id']);
        $this->view->resultOm = $oms->findById($this->view->resultSolicitacao['oms_id']);
        $solicitacaoItem->paginator($this->getParametro('pagina'), $id);
        $this->view->result = $solicitacaoItem->getResultadoPaginator();
        $this->view->btn = $solicitacaoItem->getNavePaginator();
        $this->view->acoes = $historico->historicByRequestId($id);

        $this->render('papeleta_presolemp', true, 'blank');
    }

    public function salvarPresolempAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR']);

        $idlista = explode(',', filter_input(INPUT_POST, 'idlista'));

        foreach ($idlista as $value) {
            $pdf = new Pdf();
            $pdf->number = $value;
            $pdf->url = $this->view->controller
                . 'presolemp/id/' . $value . '/'
                . 'plano_contas/' . filter_input(INPUT_POST, 'plano_contas') . '/'
                . 'tipo_empenho/' . filter_input(INPUT_POST, 'tipo_empenho') . '/'
                . 'modalidade/' . filter_input(INPUT_POST, 'modalidade') . '/'
                . 'finalidade/' . filter_input(INPUT_POST, 'finalidade') . '/'
                . 'usuario/' . $this->view->userLoggedIn['name'];
            //$pdf->salvar();
        }

        msg::showMsg('SOLEMP DE GÊNEROS gerada(s) com sucesso!', 'success');
    }

    public function eliminararquivoAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'FISCAL', 'FISCAL_SUBSTITUTO', 'NORMAL', 'ORDENADOR', 'ORDENADOR_SUBSTITUTO', 'CONTROLADOR_OBTENCAO', 'CONTROLADOR_FINANCA']);

        $file = $this->getParametro('file');
        $solicitacao = (new SolicitacaoModel())->findByIdlista($this->view->idlista);
        $number = $solicitacao['number'] ?? 'error';
        $fullPath = getcwd() . cfg::DS . 'arquivos' . cfg::DS . $number . cfg::DS . $file;
        if (file_exists($fullPath)) {
            @unlink($fullPath);
        }
        header("Location: {$this->view->controller}detalhar/idlista/{$this->view->idlista}");
    }

    public function adicionararquivoAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'FISCAL', 'FISCAL_SUBSTITUTO', 'NORMAL', 'ORDENADOR', 'ORDENADOR_SUBSTITUTO', 'CONTROLADOR_OBTENCAO', 'CONTROLADOR_FINANCA']);

        $this->view->title = 'Adicionar novo arquivo';
        $this->render('form_adicionar_arquivo');
    }

    public function salvararquivoAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'FISCAL', 'FISCAL_SUBSTITUTO', 'NORMAL', 'ORDENADOR', 'ORDENADOR_SUBSTITUTO', 'CONTROLADOR_OBTENCAO', 'CONTROLADOR_FINANCA']);

        $solicitacaoModel = new SolicitacaoModel();
        $solicitacao = $solicitacaoModel->findByIdlista($this->view->idlista);
        $number = $solicitacao['number'] ?? 'error';
        if ($number !== 'error') {
            $solicitacaoModel->saveOneFile(getcwd(), $number);
        }
    }

    public function licitacaobuscaAction()
    {
        $this->view->userLoggedIn = $this->access->setRedirect('solicitacao/')
            ->clearAccessList()
            ->authenticAccess(['ADMINISTRADOR', 'FISCAL', 'FISCAL_SUBSTITUTO', 'ENCARREGADO', 'NORMAL', 'ORDENADOR', 'ORDENADOR_SUBSTITUTO', 'CONTROLADOR_OBTENCAO', 'CONTROLADOR_FINANCA']);

        $this->view->title = 'Busca de itens licitados';
        $this->render('mostra_busca_fornecedor');
    }
}
