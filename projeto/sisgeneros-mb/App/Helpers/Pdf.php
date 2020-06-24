<?php

namespace App\Helpers;

use Knp\Snappy\Pdf as Snappy;
use App\Config\Configurations as cfg;
use Mpdf\Mpdf;

class Pdf
{

    public $url = '';
    public $html = '';
    public $number;
    private $urlBase = 'http://' . cfg::DOMAIN;

    public function gerar()
    {
        $url = $this->urlBase . $this->url;
        $number = $this->number ?? time();
        $snappy = new Snappy('/usr/bin/wkhtmltopdf');
        $snappy->setOptions([
            'orientation' => 'Landscape',
            //'default-header' => true,
            //'user-style-sheet' => true
        ]);

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="solicitacao_' . $number . '.pdf"');
        echo $snappy->getOutput($url);
    }

    public function salvar()
    {
        $url = $this->urlBase . $this->url;
        $number = $this->number ?? time();
        // $snappy = new Snappy('/usr/bin/wkhtmltopdf');
        // $snappy->setOptions([
        //     'orientation' => 'Landscape'
        // ]);
        // $snappy->generateFromHtml(file_get_contents($url), $path);

        $path = getcwd() . cfg::DS . 'arquivos' . cfg::DS . 'solemps' . cfg::DS . 'preSolempTest_' . $number . '.pdf';

        $mpdf = new Mpdf([
            'mode' => 'utf-8'
        ]);
        $mpdf->WriteHTML(file_get_contents($url));
        $mpdf->Output($path);
    }
}
