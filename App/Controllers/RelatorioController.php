<?php

namespace App\Controllers;

use HTR\System\ControllerAbstract as Controller;
use HTR\Interfaces\ControllerInterface as CtrlInterface;
use HTR\Helpers\Access\Access;
use App\Models\SolicitacaoModel as Solicitacao;
use App\Models\LicitacaoModel as Licitacao;
use App\Models\SolicitacaoItemModel as SolItem;
use App\Models\ItemModel as Item;
use App\Models\OmModel;
use App\Models\FornecedorModel;
use App\Models\RelatorioModel;
use App\Models\EmpenhoModel;
use App\Config\Configurations as cfg;
use App\Models\SolicitacaoEmpenhoModel;

class RelatorioController extends Controller implements CtrlInterface
{

    private $access;
    private $solItem;

    public function __construct($bootstrap)
    {
        parent::__construct($bootstrap);
        $this->view->controller = cfg::DEFAULT_URI . 'relatorio/';
        $this->access = new Access();
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'FISCAL', 'FISCAL_SUBSTITUTO', 'NORMAL', 'ENCARREGADO', 'ORDENADOR', 'ORDENADOR_SUBSTITUTO', 'CONTROLADOR_OBTENCAO', 'CONTROLADOR_FINANCA']);
        $this->view->idlista = $this->getParametro('idlista');
        $this->solItem = new SolItem();
    }

    public function indexAction()
    {
        $this->solicitacaoAction();
    }

    public function solicitacaoAction()
    {
        $model = new Solicitacao();
        $modelOms = new OmModel();
        $this->view->title = 'Relatório de Solicitações';
        $this->view->oms = $modelOms->findAll();
        $this->view->resultOms = (new OmModel())->findAll(function ($db) {
            $db->setaFiltros()->orderBy('oms.naval_indicative ASC');
        });
        $model->paginatorSolicitacoes($this);
        $this->view->result = $model->getResultadoPaginator();
        $this->view->btn = $model->getNavePaginator();
        $this->render('index');
    }

    public function verAction()
    {
        $model = new Solicitacao();
        $modelOms = new OmModel();
        $this->view->title = 'Relatório de Solicitações';
        $this->view->oms = $modelOms->findAll();
        $model->paginatorSolicitacoes($this);
        $this->view->result = $model->getResultadoPaginator();
        $this->view->btn = $model->getNavePaginator();
        $this->render('index');
    }

    public function detalharAction()
    {
        $model = new Solicitacao();
        $licitacao = new Licitacao();
        $solItem = new SolItem();
        $this->view->title = 'Itens da Solicitação';
        $this->view->resultSolicitacao = $model->recuperaDadosRelatorioSolicitacao($this->view->idlista);
        $this->view->resultLicitacao = $licitacao->findById($this->view->resultSolicitacao['biddings_id']);
        $solItem->paginator($this->getParametro('pagina'), $this->view->idlista);
        $this->view->result = $solItem->getResultadoPaginator();
        $this->view->btn = $solItem->getNavePaginator();
        $this->render('mostra_item_solicitacao');
    }

    public function demandaAction()
    {
        $licitacao = new Licitacao();
        $this->view->title = 'Licitações Registradas';
        $licitacao->paginator($this->getParametro('pagina'), null, $this->view->userLoggedIn['oms_id']);
        $this->view->result = $licitacao->getResultadoPaginator();
        $this->view->btn = $licitacao->getNavePaginator();
        $this->render('mostra_licitacao_disponivel');
    }

    public function licitacaoAction()
    {
        $item = new Item();
        $licitacao = new Licitacao();
        $this->view->title = 'Lista de Itens da Licitação';
        $item->paginator($this->getParametro('pagina'), $this->view->idlista);
        $this->view->result = $item->getResultadoPaginator();
        $this->view->btn = $item->getNavePaginator();
        $this->view->resultLicitacao = $licitacao->findById($this->view->idlista);
        $this->render('mostra_item_demanda');
    }

    public function entregaAction()
    {
        $this->view->title = 'Avaliação de Entrega dos Fornecedores';
        $this->view->resultOms = (new OmModel())->findAll(function ($db) {
            $db->setaFiltros()->orderBy('oms.naval_indicative ASC');
        });

        $this->view->resultFornecedor = (new FornecedorModel())->findAll(function ($db) {
            $db->setaFiltros()->orderBy('suppliers.name ASC');
        });

        $model = (new RelatorioModel())->paginatorDeliveryReport($this);
        $this->view->btn = $model->getNavePaginator();
        $this->view->result = $model->getResultadoPaginator();

        $this->render('mostra_avalicao_entrega');
    }

    protected function demanda($itemnumber, $idLicitacao)
    {
        return $this->solItem->quantidadeDemanda($itemnumber, $idLicitacao);
    }

    public function empenhoAction()
    {
        $this->view->title = 'Relatório de Empenhos';

        $model = new EmpenhoModel();
        $model->paginator($this->getParametro('pagina'), $this->view->userLoggedIn, $this->getParametro('busca'));
        $this->view->result = $model->getResultadoPaginator();
        $this->view->btn = $model->getNavePaginator();

        $this->render('mostra_empenho');
    }

    public function limiteDispensaAction()
    {
        $this->view->title = 'Relatório de Limite de dispensa de licitação';

        $model = new Solicitacao();
        $omModel = new OmModel();
        $om = $this->getParametro('om') ?? $this->view->userLoggedIn['oms_id'];
        $this->view->om = $omModel->findById($om);

        $this->view->resultOms = $omModel->findAll(function ($db) {
            $db->setaFiltros()->orderBy('oms.naval_indicative ASC');
        });

        $model->paginatorSolicitacoesNaoLicitado($this, $om);
        $this->view->result = $model->getResultadoPaginator();
        $this->view->btn = $model->getNavePaginator();

        $this->render('limite_dispensa_licitacao');
    }

    public function indicadorTempoAction()
    {
        $this->view->title = 'Relatório de indicador de tempo de pagamento das notas fiscais';

        $model = new SolicitacaoEmpenhoModel();
        $omModel = new OmModel();
        $this->view->resultOms = $omModel->findAll(function ($db) {
            $db->setaFiltros()->orderBy('oms.naval_indicative ASC');
        });

        $this->view->result = $model->findIndicadorTemporResume($this);
        $this->view->dados = $model->findIndicadorTempo($this);

        $this->render('mostra_indicador_tempo');
    }

    public function emissaoEntregaAction()
    {
        $this->view->title = 'Relatório de Indicador data de emissão e data de entrega do material';

        $model = new SolicitacaoEmpenhoModel();
        $omModel = new OmModel();
        $this->view->resultOms = $omModel->findAll(function ($db) {
            $db->setaFiltros()->orderBy('oms.naval_indicative ASC');
        });

        $this->view->result = $model->findIndicadorEmissaoEntrega($this);

        $this->render('mostra_indicador_tempo_emissao_entrega');
    }

    public function nfpagasConfigAction()
    {
        $this->view->title = '';

        $omModel = new OmModel();
        $this->view->resultOms = $omModel->findAll(function ($db) {
            $db->setaFiltros()->orderBy('oms.naval_indicative ASC');
        });

        $this->render('mostra_nf_pagas_config');
    }

    public function nfpagasAction()
    {
        $model = new SolicitacaoEmpenhoModel();
        $this->view->result = $model->findNfPagasPorFornecedor($this);

        $this->render('mostra_nf_pagas', true, 'blank');
    }
}
