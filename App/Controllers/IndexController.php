<?php
namespace App\Controllers;

use HTR\System\ControllerAbstract as Controller;
use HTR\Interfaces\ControllerInterface as CtrlInterface;
use HTR\Helpers\Access\Access;
use App\Models\SolicitacaoModel as Solicitacao;
use App\Models\AvaliacaoFornecedorModel as Avaliacao;
use App\Models\AvisosModel;
use App\Models\SolicitacaoEmpenhoModel;

class IndexController extends Controller implements CtrlInterface
{

    private $access;

    private static $status = [
        "ADMINISTRADOR" => "ELABORADO",
        "NORMAL" => "ELABORADO",
        "ENCARREGADO" => "ENCAMINHADO",
        "FISCAL" => "PROVISIONADO",
        "FISCAL_SUBSTITUTO" => "PROVISIONADO",
        "ORDENADOR" => "VERIFICADO",
        "ORDENADOR_SUBSTITUTO" => "VERIFICADO",
        "CONTROLADOR_OBTENCAO" => "AUTORIZADO",
        "CONTROLADOR_FINANCA" => "CONFERIDO",
    ];

    public function __construct($bootstrap)
    {
        parent::__construct($bootstrap);
        $this->access = new Access();
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'ENCARREGADO', 'NORMAL', 'FISCAL', 'FISCAL_SUBSTITUTO', 'ORDENADOR', 'ORDENADOR_SUBSTITUTO', 'CONTROLADOR_OBTENCAO', 'CONTROLADOR_FINANCA']);
    }

    public function indexAction()
    {
        $solicitacao = new Solicitacao();
        $solEmpenho = new SolicitacaoEmpenhoModel();
        $avaliacao = new Avaliacao();
        $arrEvaluation = $avaliacao->findBestBadSuppliers();
        $this->view->melhoresAvaliacoes = $arrEvaluation;
        $this->view->pioresAvaliacoes = array_reverse($arrEvaluation);
        $this->view->pendAprov = $solicitacao->findQtdSolicitByStatus($this->view->userLoggedIn, self::$status[$this->view->userLoggedIn['level']]);
        $this->view->solicitacoesMensal = $solicitacao->findSolitacoesMensal($this->view->userLoggedIn);
        $this->view->solicitacoesAtrasadas = $solEmpenho->findQtdSolicitAtrasadas($this->view->userLoggedIn);
        $this->view->resultAvisos = (new AvisosModel())->fetchAllAvisosByOmId($this->view->userLoggedIn['oms_id']);
        $this->view->lastUpdated = $solicitacao->lastUpdated($this->view->userLoggedIn);
        $this->render('index');
    }
}
