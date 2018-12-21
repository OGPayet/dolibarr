<?php

/**
*      Return a PDF instance object. We create a FPDI instance that instantiate TCPDF.
*
*      @param	string		$format         Array(width,height). Keep empty to use default setup.
*      @param	string		$metric         Unit of format ('mm')
*      @param  string		$pagetype       'P' or 'l'
*      @return TCPDF						PDF object
*/
function opendsi_pdf_getInstance($format='',$metric='mm',$pagetype='P')
{
    global $conf;

    // Define constant for TCPDF
    if (!defined('K_TCPDF_EXTERNAL_CONFIG')) {
        define('K_TCPDF_EXTERNAL_CONFIG', 1);    // this avoid using tcpdf_config file
        define('K_PATH_CACHE', DOL_DATA_ROOT . '/admin/temp/');
        define('K_PATH_URL_CACHE', DOL_DATA_ROOT . '/admin/temp/');
        dol_mkdir(K_PATH_CACHE);
        define('K_BLANK_IMAGE', '_blank.png');
        define('PDF_PAGE_FORMAT', 'A4');
        define('PDF_PAGE_ORIENTATION', 'P');
        define('PDF_CREATOR', 'TCPDF');
        define('PDF_AUTHOR', 'TCPDF');
        define('PDF_HEADER_TITLE', 'TCPDF Example');
        define('PDF_HEADER_STRING', "by Dolibarr ERP CRM");
        define('PDF_UNIT', 'mm');
        define('PDF_MARGIN_HEADER', 5);
        define('PDF_MARGIN_FOOTER', 10);
        define('PDF_MARGIN_TOP', 27);
        define('PDF_MARGIN_BOTTOM', 25);
        define('PDF_MARGIN_LEFT', 15);
        define('PDF_MARGIN_RIGHT', 15);
        define('PDF_FONT_NAME_MAIN', 'helvetica');
        define('PDF_FONT_SIZE_MAIN', 10);
        define('PDF_FONT_NAME_DATA', 'helvetica');
        define('PDF_FONT_SIZE_DATA', 8);
        define('PDF_FONT_MONOSPACED', 'courier');
        define('PDF_IMAGE_SCALE_RATIO', 1.25);
        define('HEAD_MAGNIFICATION', 1.1);
        define('K_CELL_HEIGHT_RATIO', 1.25);
        define('K_TITLE_MAGNIFICATION', 1.3);
        define('K_SMALL_RATIO', 2 / 3);
        define('K_THAI_TOPCHARS', true);
        define('K_TCPDF_CALLS_IN_HTML', true);
        define('K_TCPDF_THROW_EXCEPTION_ERROR', false);
    }

    if (!empty($conf->global->MAIN_USE_FPDF) && !empty($conf->global->MAIN_DISABLE_FPDI))
        return "Error MAIN_USE_FPDF and MAIN_DISABLE_FPDI can't be set together";

    // We use by default TCPDF else FPDF
    if (empty($conf->global->MAIN_USE_FPDF)) require_once TCPDF_PATH . 'tcpdf.php';
    else require_once FPDF_PATH . 'fpdf.php';

    // We need to instantiate tcpdi or fpdi object (instead of tcpdf) to use merging features. But we can disable it (this will break all merge features).
    if (empty($conf->global->MAIN_DISABLE_TCPDI)) require_once TCPDI_PATH . 'tcpdi.php';
    else if (empty($conf->global->MAIN_DISABLE_FPDI)) require_once FPDI_PATH . 'fpdi.php';

    //$arrayformat=pdf_getFormat();
    //$format=array($arrayformat['width'],$arrayformat['height']);
    //$metric=$arrayformat['unit'];

    if (class_exists('TCPDI') && !class_exists('ExTCPDI')) {
        // Extend the TCPDF class to create custom Header and Footer
        class ExTCPDI extends TCPDI
        {
            public $backgroundImagePath = '';

            //Page header
            public function Header()
            {
                if (!empty($this->backgroundImagePath)) {
                    // get the current page break margin
                    $bMargin = $this->getBreakMargin();
                    // get current auto-page-break mode
                    $auto_page_break = $this->AutoPageBreak;
                    // disable auto-page-break
                    $this->SetAutoPageBreak(false, 0);
                    // set bacground image
                    $this->Image($this->backgroundImagePath, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);
                    // restore auto-page-break status
                    $this->SetAutoPageBreak($auto_page_break, $bMargin);
                    // set the starting point for the page content
                    $this->setPageMark();
                } else {
                    parent::Header();
                }
            }
        }

        $pdf = new ExTCPDI($pagetype, $metric, $format);
    } else if (class_exists('FPDI') && !class_exists('ExFPDI')) {
        class ExFPDI extends FPDI
        {
            public $backgroundImagePath = '';

            //Page header
            function Header()
            {
                if (!empty($this->backgroundImagePath)) {
                    // Logo
                    $this->Image($this->backgroundImagePath, 10, 6, 30);
                    // Police Arial gras 15
                    $this->SetFont('Arial', 'B', 15);
                    // Décalage à droite
                    $this->Cell(80);
                    // Titre
                    $this->Cell(30, 10, 'Titre', 1, 0, 'C');
                    // Saut de ligne
                    $this->Ln(20);
                } else {
                    parent::Header();
                }
            }
        }

        $pdf = new ExFPDI($pagetype, $metric, $format);
    } elseif (!class_exists('ExTCPDF')) {
        // Extend the TCPDF class to create custom Header and Footer
        class ExTCPDF extends TCPDF
        {
            public $backgroundImagePath = '';

            //Page header
            public function Header()
            {
                if (!empty($this->backgroundImagePath)) {
                    // get the current page break margin
                    $bMargin = $this->getBreakMargin();
                    // get current auto-page-break mode
                    $auto_page_break = $this->AutoPageBreak;
                    // disable auto-page-break
                    $this->SetAutoPageBreak(false, 0);
                    // set bacground image
                    $this->Image($this->backgroundImagePath, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);
                    // restore auto-page-break status
                    $this->SetAutoPageBreak($auto_page_break, $bMargin);
                    // set the starting point for the page content
                    $this->setPageMark();
                } else {
                    parent::Header();
                }
            }
        }

        $pdf = new ExTCPDF($pagetype, $metric, $format);
    }

    // Protection and encryption of pdf
    if (empty($conf->global->MAIN_USE_FPDF) && !empty($conf->global->PDF_SECURITY_ENCRYPTION)) {
        /* Permission supported by TCPDF
        - print : Print the document;
        - modify : Modify the contents of the document by operations other than those controlled by 'fill-forms', 'extract' and 'assemble';
        - copy : Copy or otherwise extract text and graphics from the document;
        - annot-forms : Add or modify text annotations, fill in interactive form fields, and, if 'modify' is also set, create or modify interactive form fields (including signature fields);
        - fill-forms : Fill in existing interactive form fields (including signature fields), even if 'annot-forms' is not specified;
        - extract : Extract text and graphics (in support of accessibility to users with disabilities or for other purposes);
        - assemble : Assemble the document (insert, rotate, or delete pages and create bookmarks or thumbnail images), even if 'modify' is not set;
        - print-high : Print the document to a representation from which a faithful digital copy of the PDF content could be generated. When this is not set, printing is limited to a low-level representation of the appearance, possibly of degraded quality.
        - owner : (inverted logic - only for public-key) when set permits change of encryption and enables all other permissions.
        */
        // For TCPDF, we specify permission we want to block
        $pdfrights = array('modify', 'copy');

        $pdfuserpass = ''; // Password for the end user
        $pdfownerpass = NULL; // Password of the owner, created randomly if not defined
        $pdf->SetProtection($pdfrights, $pdfuserpass, $pdfownerpass);
    }

    // If we use FPDF class, we may need to add method writeHTMLCell
    if (!empty($conf->global->MAIN_USE_FPDF) && !method_exists($pdf, 'writeHTMLCell')) {
        // Declare here a class to overwrite FPDI to add method writeHTMLCell
        /**
         *    This class is an enhanced FPDI class that support method writeHTMLCell
         */
        class FPDI_DolExtended extends FPDI
        {
            public $backgroundImagePath = '';

            //Page header
            function Header()
            {
                if (!empty($this->backgroundImagePath)) {
                    // Logo
                    $this->Image($this->backgroundImagePath, 10, 6, 30);
                    // Police Arial gras 15
                    $this->SetFont('Arial', 'B', 15);
                    // Décalage à droite
                    $this->Cell(80);
                    // Titre
                    $this->Cell(30, 10, 'Titre', 1, 0, 'C');
                    // Saut de ligne
                    $this->Ln(20);
                } else {
                    parent::Header();
                }
            }

            /**
             * __call
             *
             * @param    string $method Method
             * @param    mixed $args Arguments
             * @return    void
             */
            public function __call($method, $args)
            {
                if (isset($this->$method)) {
                    $func = $this->$method;
                    $func($args);
                }
            }

            /**
             * writeHTMLCell
             *
             * @param    int $w Width
             * @param    int $h Height
             * @param    int $x X
             * @param    int $y Y
             * @param    string $html Html
             * @param    int $border Border
             * @param    int $ln Ln
             * @param    boolean $fill Fill
             * @param    boolean $reseth Reseth
             * @param    string $align Align
             * @param    boolean $autopadding Autopadding
             * @return    void
             */
            public function writeHTMLCell($w, $h, $x, $y, $html = '', $border = 0, $ln = 0, $fill = false, $reseth = true, $align = '', $autopadding = true)
            {
                $this->SetXY($x, $y);
                $val = str_replace('<br>', "\n", $html);
                //$val=dol_string_nohtmltag($val,false,'ISO-8859-1');
                $val = dol_string_nohtmltag($val, false, 'UTF-8');
                $this->MultiCell($w, $h, $val, $border, $align, $fill);
            }
        }

        $pdf2 = new FPDI_DolExtended($pagetype, $metric, $format);
        unset($pdf);
        $pdf = $pdf2;
    }

    return $pdf;
}