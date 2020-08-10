<?php
namespace App\Controllers;

use HTR\System\ControllerAbstract as Controller;
use HTR\Interfaces\ControllerInterface as CtrlInterface;
use HTR\Helpers\Access\Access;
use App\Models\CreditoProvisionadoModel;
use App\Models\HistoricoCreditoProvisionadoModel;
use App\Models\OmModel;
use App\Config\Configurations as cfg;

class CreditoprovisionadoController extends Controller implements CtrlInterface
{

    private $access;

    public function __construct($bootstrap)
    {
        parent::__construct($bootstrap);
        $this->view->controller = cfg::DEFAULT_URI . 'creditoprovisionado/';
        $this->access = new Access();
        $this->view->userLoggedIn = $this->access->authenticAccess(['ADMINISTRADOR', 'CONTROLADOR', 'ORDENADOR', 'CONTROLADOR_OBTENCAO', 'CONTROLADOR_FINANCA']);
    }

    public function indexAction()
    {
        $this->verAction();
    }

    public function novoAction()
    {
        $this->view->title = 'Novo Registro';
        $om = new OmModel();
        $this->view->resultOm = $om->findAll();
        $this->render('form_novo');
    }

    public function detalharAction()
    {
        $model = new CreditoProvisionadoModel();
        $this->view->title = 'Editando Registro';
        $this->view->result = $model->findById($this->getParametro('id'));

        $historicoModel = new HistoricoCreditoProvisionadoModel();
        $historicoModel->paginator($this->getParametro('pagina'), $this->getParametro('id'));
        $this->view->historico = $historicoModel->getResultadoPaginator();
        $this->view->btn = $historicoModel->getNavePaginator();

        $this->render('form_editar');
    }

    public function eliminarAction()
    {
        $model = new CreditoProvisionadoModel();
        $model->removerRegistro($this->getParametro('id'));
    }

    public function verAction()
    {
        $model = new CreditoProvisionadoModel();
        $this->view->title = 'Lista de Todos os Créditos provisionados';
        $model->paginator($this->getParametro('pagina'), $this->view->userLoggedIn);
        $this->view->result = $model->getResultadoPaginator();
        $this->view->btn = $model->getNavePaginator();
        $this->render('index');
    }

    public function registraAction()
    {
        $model = new CreditoProvisionadoModel();
        $model->novoRegistro($this->view->userLoggedIn);
    }

    public function registraOperacaoAction()
    {
        $model = new HistoricoCreditoProvisionadoModel();
        $model->novoRegistro();
    }

    public function alteraAction()
    {
        $model = new CreditoProvisionadoModel();
        $model->editarRegistro();
    }

    public function findCreditProvisionedByOmIdAction()
    {
        $model = new CreditoProvisionadoModel();
        $result = $model->creditProvisionedByOmId($this->getParametro('oms_id'));
        echo json_encode($result);
    }
}
