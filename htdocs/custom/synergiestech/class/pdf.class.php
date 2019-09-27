<?php

dol_include_once('/opendsi/vendor/autoload.php');

/**
 * Date: 29/08/2016
 * Time: 15:13
 */
class create_pdf
{
    public $content = "";

    /**
     * add_content function
     * permet de créer une page pdf en ajoutant son contenu et un titre
     */
    public function add_content($data, $title = "")
    {
        $this->content .= '<page backtop="10mm" backbottom="5mm" backleft="0mm" backright="0mm">' .
            $this->pdf_header($title) .
            $this->pdf_footer() .
            $data .
            '</page>';
    }

    /**
     * generate_pdf function
     * permet de générer le contenu avec le template css
     */
    public function generate_pdf($content)
    {
        return $this->css() . $content;
    }

    /**
     * css function
     * permet de charger le template CSS du PDF
     */
    public function css()
    {
        $css = dol_buildpath('/opendsi/css/template.css');
        if(file_exists($css)){
            return '<style type="text/css">' . file_get_contents($css) . '</style>';
        }
    }

    /**
     * pdf_footer function
     * permet de générer le pied de page du PDF
     */
    public function pdf_footer()
    {
        return '<page_footer>
        <table style="width: 100%; border: none;">
            <tr>
                <td style="text-align: left;    width: 33%">(*) 1 ticket = 15 minutes</td>
                <td style="text-align: center;    width: 33%">Open-DSI Expert en solution informatiques libres</td>
                <td style="text-align: right;    width: 33%">page [[page_cu]]/[[page_nb]]</td>
            </tr>
        </table>
    </page_footer>';

    }

    /**
     * pdf_header function
     * permet de générer les entêtes du PDF
     */
    public function pdf_header($title = "")
    {
        $logo = dol_buildpath('/opendsi/img/Logo.png');
        return '<page_header>
        <table>
            <tr>
                <td style="width:10%;"><img src="'.$logo.'" alt="" style="height:20px" class="logo"></td>
                <td style="width:80%;" class="center"><strong>' . $title . '</strong></td>
                <td style="width:10%;">&nbsp;</td>
            </tr>
        </table>
        </page_header>';
    }

    /**
     * output function
     * permet de générer le document pdf en fournissant son contenu $data
     */
    public function output($filename = null)
    {
        try {
            $html2pdf = new HTML2PDF('L', 'A4', 'fr', true, 'UTF-8', array(15, 5, 15, 5));
            $html2pdf->pdf->SetDisplayMode('fullpage');
            $html2pdf->writeHTML($this->generate_pdf($this->content));
            $html2pdf->Output($filename . '.pdf', "F");
        }catch (HTML2PDF_exception $e) {
            die($e);
        }
    }
}