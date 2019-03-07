<?php
/* Copyright (C) 2003		Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin				<regis.houssin@capnetworks.com>
 * Copyright (C) 2008		Raphael Bertrand (Resultic)	<raphael.bertrand@resultic.fr>
 * Copyright (C) 2011		Fabrice CHERRIER
 * Copyright (C) 2013		Cédric Salvador				<csalvador@gpcsolutions.fr>
 * Copyright (C) 2015       Marcos García               <marcosgdf@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/core/modules/fichinter/doc/pdf_soleil.modules.php
 *	\ingroup    ficheinter
 *	\brief      Fichier de la classe permettant de generer les fiches d'intervention au modele Soleil
 */
require_once DOL_DOCUMENT_ROOT.'/core/modules/fichinter/modules_fichinter.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

dol_include_once('/synergiestechcontrat/class/libPDFST.trait.php');
dol_include_once('/synergiestech/lib/opendsi_pdf.lib.php');


/**
 *	Class to build interventions documents with model Soleil with company relationships
 */
class pdf_soleil_st extends ModelePDFFicheinter
{
    use libPDFST;
    var $db;
    var $name;
    var $description;
    var $type;

    var $phpmin = array(4, 3, 0); // Minimum version of PHP required by module
    var $version = 'dolibarr';

    var $page_largeur;
    var $page_hauteur;
    var $format;
    var $marge_gauche;
    var $marge_droite;
    var $marge_haute;
    var $marge_basse;
    var $main_color = array(192, 0, 0);

    var $emetteur;    // Objet societe qui emet

    /**
     *  List of effective working time by technician
     * @var array
     */
    public $effective_working_time;

    /**
     *    Constructor
     *
     * @param        DoliDB $db Database handler
     */
    function __construct($db)
    {
        global $conf, $langs, $mysoc;

        $this->db = $db;
        $this->name = 'soleil_st';
        $this->description = $langs->trans("DocumentModelStandardPDF");

        // Dimension page pour format A4
        $this->type = 'pdf';
        $formatarray = pdf_getFormat();
        $this->page_largeur = $formatarray['width'];
        $this->page_hauteur = $formatarray['height'];
        $this->format = array($this->page_largeur, $this->page_hauteur);
        $this->marge_gauche = 10;
        $this->marge_droite = 10;
        $this->marge_haute = 50;
        $this->marge_basse = 15;

        $this->option_logo = 1;                    // Affiche logo
        $this->option_tva = 1;                     // Gere option tva FACTURE_TVAOPTION
        $this->option_modereg = 1;                 // Affiche mode reglement
        $this->option_condreg = 1;                 // Affiche conditions reglement
        $this->option_codeproduitservice = 1;      // Affiche code produit-service
        $this->option_multilang = 1;               // Dispo en plusieurs langues
        $this->option_draft_watermark = 1;           //Support add of a watermark on drafts

        // Get source company
        $this->emetteur = $mysoc;
        if (empty($this->emetteur->country_code)) $this->emetteur->country_code = substr($langs->defaultlang, -2);    // By default, if was not defined

        // Define position of columns
        $this->posxdesc = $this->marge_gauche + 1;
    }

    /**
     *  Function to build pdf onto disk
     *
     * @param        Object $object Object to generate
     * @param        Translate $outputlangs Lang output object
     * @param        string $srctemplatepath Full path of source filename for generator using a template file
     * @param        int $hidedetails Do not show line details
     * @param        int $hidedesc Do not show desc
     * @param        int $hideref Do not show ref
     * @return        int                                    1=OK, 0=KO
     */
    function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
    {
        global $user, $langs, $conf, $mysoc, $db, $hookmanager;

        if (!is_object($outputlangs)) $outputlangs = $langs;
        // For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
        if (!empty($conf->global->MAIN_USE_FPDF)) $outputlangs->charset_output = 'ISO-8859-1';

        $outputlangs->load("main");
        $outputlangs->load("dict");
        $outputlangs->load("companies");
        $outputlangs->load("synergiestech@synergiestech");
        $outputlangs->load("interventions");

        if ($conf->ficheinter->dir_output) {
            $object->fetch_thirdparty();

            // Definition of $dir and $file
            if ($object->specimen) {
                $dir = $conf->ficheinter->dir_output;
                $file = $dir . "/SPECIMEN.pdf";
            } else {
                $objectref = dol_sanitizeFileName($object->ref);
                $dir = $conf->ficheinter->dir_output . "/" . $objectref;
                $file = $dir . "/" . $objectref . ".pdf";
            }

            if (!file_exists($dir)) {
                if (dol_mkdir($dir) < 0) {
                    $this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
                    return 0;
                }
            }

            if (file_exists($dir)) {
                // Add pdfgeneration hook
                if (!is_object($hookmanager)) {
                    include_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
                    $hookmanager = new HookManager($this->db);
                }

                $hookmanager->initHooks(array('pdfgeneration'));
                $parameters = array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
                global $action;
                $reshook = $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks

                // Create pdf instance
                $pdf = opendsi_pdf_getInstance($this->format);
                $pdf->backgroundImagePath = dol_buildpath('/synergiestechcontrat/img/fond_6.jpg');

                $default_font_size = pdf_getPDFFontSize($outputlangs);    // Must be after pdf_getInstance
                $heightforinfotot = 0;    // Height reserved to output the info and total part
                $heightforsignature = max(40, 30+7*$this->_get_nb_effective_working_time());  // Height reserved to output the effective working time info and signature part
                $heightforfreetext = (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT) ? $conf->global->MAIN_PDF_FREETEXT_HEIGHT : 5);    // Height reserved to output the free text on last page
                $heightforfooter = $this->marge_basse + 8;    // Height reserved to output the footer (value include bottom margin)
                $pdf->SetAutoPageBreak(1, 0);

                if (class_exists('TCPDF')) {
                    $pdf->setPrintHeader(false);
                    $pdf->setPrintFooter(false);
                }
                if (!empty($pdf->backgroundImagePath) && (class_exists('TCPDI') || class_exists('TCPDF'))) {
                    $pdf->setPrintHeader(true);
                }
                $pdf->SetFont(pdf_getPDFFont($outputlangs));
                // Set path to the background PDF File
                if (empty($conf->global->MAIN_DISABLE_FPDI) && !empty($conf->global->MAIN_ADD_PDF_BACKGROUND)) {
                    $pagecount = $pdf->setSourceFile($conf->mycompany->dir_output . '/' . $conf->global->MAIN_ADD_PDF_BACKGROUND);
                    $tplidx = $pdf->importPage(1);
                }

                $pdf->Open();
                $pagenb = 0;
                $pdf->SetDrawColor(128, 128, 128);

                $pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
                $pdf->SetSubject($outputlangs->transnoentities("InterventionCard"));
                $pdf->SetCreator("Dolibarr " . DOL_VERSION);
                $pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
                $pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref) . " " . $outputlangs->transnoentities("InterventionCard"));
                if (!empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) $pdf->SetCompression(false);

                $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right

                // New page
                $pdf->AddPage();
                if (!empty($tplidx)) $pdf->useTemplate($tplidx);
                $pagenb++;
                $this->_pagehead($pdf, $object, 1, $outputlangs);
                $pdf->SetFont('', '', $default_font_size - 1);
                $pdf->SetTextColor(0, 0, 0);

                $tab_top = 90;
                $tab_top_newpage = (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD) ? 40 : 10);
                $tab_height = 130;
                $tab_height_newpage = 150;

                // Affiche notes
                $notetoshow = empty($object->note_public) ? '' : $object->note_public;
                if ($notetoshow) {
                    $tab_top = 88;

                    $pdf->SetFont('', '', $default_font_size - 1);
                    $pdf->writeHTMLCell(190, 3, $this->posxdesc - 1, $tab_top, dol_htmlentitiesbr($notetoshow), 0, 1);
                    $nexY = $pdf->GetY();
                    $height_note = $nexY - $tab_top;

                    // Rect prend une longueur en 3eme param
                    $pdf->SetDrawColor(192, 192, 192);
                    $pdf->Rect($this->marge_gauche, $tab_top - 1, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $height_note + 1);

                    $tab_height = $tab_height - $height_note;
                    $tab_top = $nexY + 6;
                } else {
                    $height_note = 0;
                }

                $iniY = $tab_top + 7;
                $curY = $tab_top + 7;
                $nexY = $tab_top + 7;

                $pdf->SetXY($this->marge_gauche, $curY);













                $bottomlasttab = $this->page_hauteur - $heightforinfotot - $heightforsignature - $heightforfreetext - $heightforfooter + 1;

                // Show effective working time
//                $this->_effective_working_time_area($pdf, $object, $bottomlasttab, $outputlangs);
//
//                // Show signature
//                $this->_signature_area($pdf, $object, $bottomlasttab, $outputlangs);

                $this->_pagefoot($pdf, $object, $outputlangs);
                if (method_exists($pdf, 'AliasNbPages')) $pdf->AliasNbPages();

                $pdf->Close();
                $pdf->Output($file, 'F');

                // Add pdfgeneration hook
                $hookmanager->initHooks(array('pdfgeneration'));
                $parameters = array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
                global $action;
                $reshook = $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks

                if (!empty($conf->global->MAIN_UMASK))
                    @chmod($file, octdec($conf->global->MAIN_UMASK));

                return 1;
            } else {
                $this->error = $langs->trans("ErrorCanNotCreateDir", $dir);
                return 0;
            }
        } else {
            $this->error = $langs->trans("ErrorConstantNotDefined", "FICHEINTER_OUTPUTDIR");
            return 0;
        }
    }

    /**
     *  Show top header of page.
     *
     * @param  PDF          $pdf            Object PDF
     * @param  Fichinter    $object         Object intervention
     * @param  int          $showaddress    0=no, 1=yes
     * @param  Translate    $outputlangs    Object lang for output
     *
     * @return void
     */
    function _pagehead(&$pdf, $object, $showaddress, $outputlangs)
    {
        global $conf, $langs;

        $outputlangs->load("main");
        $outputlangs->load("dict");
        $outputlangs->load("companies");
        $outputlangs->load("interventions");
        $outputlangs->load("synergiestech@synergiestech");
        if ($conf->companyrelationships->enabled) {
            $outputlangs->load("companyrelationships@companyrelationships");
        }

        $default_font_size = pdf_getPDFFontSize($outputlangs);

        pdf_pagehead($pdf, $outputlangs, $this->page_hauteur);

        // Affiche le filigrane brouillon - Print Draft Watermark
        if ($object->statut == 0 && (!empty($conf->global->FICHINTER_DRAFT_WATERMARK))) {
            pdf_watermark($pdf, $outputlangs, $this->page_hauteur, $this->page_largeur, 'mm', $conf->global->FICHINTER_DRAFT_WATERMARK);
        }

        // Prepare la suite
        $pdf->SetTextColor(0, 0, 60);
        $pdf->SetFont('', 'B', $default_font_size + 3);

        $w = 110;
        $posy = $this->marge_haute;
        $posx = $this->page_largeur - $this->marge_droite - $w;

        $pdf->SetXY($this->marge_gauche, $posy);

        // Logo
        $logo = $conf->mycompany->dir_output . '/logos/' . $this->emetteur->logo;
        if ($this->emetteur->logo) {
            if (is_readable($logo)) {
                $pdf->Image($logo, 1, 0, 0, 46); // width=0 (auto)
            } else {
                $pdf->SetTextColor(200, 0, 0);
                $pdf->SetFont('', 'B', $default_font_size - 2);
                $pdf->MultiCell($w, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound", $logo), 0, 'L');
                $pdf->MultiCell($w, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
            }
        } else {
            $text = $this->emetteur->name;
            $pdf->MultiCell($w, 4, $outputlangs->convToOutputCharset($text), 0, 'L');
        }

        // Sender properties
        $carac_emetteur = $this->pdf_build_details($outputlangs, $this->emetteur, $object->thirdparty);
        // Show sender
        $posy = 0;
        $posx = $this->marge_gauche + 92;
        if (!empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx = $this->page_largeur - $this->marge_droite - 80;

        $hautcadre = !empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 38 : 40;
        $widthrecbox = !empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 92 : 82;

        // Show sender frame
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY($posx, $posy);
        $pdf->SetTextColor(255, 255, 255);

        // Show sender name
        $pdf->SetXY($posx + 0, $posy + 2);
        $pdf->SetFont('', '', $default_font_size - 2);
        $nameAdress = $this->emetteur->name . "\n" . $this->emetteur->address . "\n" . $this->emetteur->zip . " " . $this->emetteur->town . "\n" . $this->emetteur->country;
        $pdf->MultiCell($widthrecbox - 2, 4, $outputlangs->convToOutputCharset($nameAdress), 0, 'L');

        // Show sender information
        $pdf->SetXY($posx + 36, $posy + 2);
        $pdf->SetFont('', '', $default_font_size - 2);
        $pdf->MultiCell($widthrecbox - 2, 4, $carac_emetteur, 0, 'L');

        if ($showaddress) {
            // Get extrafields
            require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
            $extrafields = new ExtraFields($this->db);
            $extralabels = $extrafields->fetch_name_optionals_label($object->table_element); // fetch optionals attributes and labels
            $object->fetch_optionals();

            // Set default values
            $bulletSize = 1;
            $bulletWidth = 6;
            $multiCellBorder = 0;
            $w = intval(($this->page_largeur - ($this->marge_gauche + 7) - $this->marge_droite) / 3);

            // Set benefactor company
            $benefactor_company = $object->thirdparty;
            if ($conf->companyrelationships->enabled) {
                $benefactor_id = $object->array_options['options_companyrelationships_fk_soc_benefactor'];
                if (isset($benefactor_id) && $benefactor_id > 0 && $benefactor_id != $object->thirdparty->id) {
                    $benefactor_company = new Societe($this->db);
                    $benefactor_company->fetch($benefactor_id);
                }
            }
            $use_benefactor_contact = false;
            $arrayidcontact = $object->getIdContact('external', 'CUSTOMER');
            if (count($arrayidcontact) > 0) {
                $use_benefactor_contact = true;
                $object->fetch_contact($arrayidcontact[0]);
                $object->contact->fetch_thirdparty();
                $benefactor_company = $object->contact->thirdparty;
                $benefactor_company->contact = $object->contact;
            }

            // Set principal company
            $principal_company = $object->thirdparty;
            $use_principal_contact = false;
            $arrayidcontact = $object->getIdContact('external', 'BILLING');
            if (count($arrayidcontact) > 0) {
                $use_principal_contact = true;
                $object->fetch_contact($arrayidcontact[0]);
                $object->contact->fetch_thirdparty();
                $principal_company = $object->contact->thirdparty;
                $principal_company->contact = $object->contact;
            }

            // Write Intervention Information
            //-------------------------------------
            $posx = $this->marge_gauche;
            $posy = $this->marge_haute;
            $pdf->SetFont('', 'B', $default_font_size + 3);
            $pdf->SetXY($posx, $posy);
            call_user_func_array(array($pdf, 'SetTextColor'), $this->main_color);
            $title = $outputlangs->transnoentities("SynergiesTechPDFInterventionTitle");
            $pdf->MultiCell($w, 5, $title, $multiCellBorder, 'L');
            $pdf->SetFont('', '', $default_font_size - 2);

            // Ref
            $posy += 7;
            $pdf->SetXY($posx + $bulletWidth, $posy);
            $pdf->SetTextColor(0, 0, 60);
            $this->addBullet($pdf, $bulletSize);
            $pdf->MultiCell($w - $bulletWidth, 4, $outputlangs->transnoentities("SynergiesTechPDFInterventionRef") . " : " . $outputlangs->convToOutputCharset($object->ref), $multiCellBorder, 'L');

            // Type
            $posy += 5;
            $pdf->SetXY($posx + $bulletWidth, $posy);
            $pdf->SetTextColor(0, 0, 60);
            $this->addBullet($pdf, $bulletSize);
            $pdf->MultiCell($w - $bulletWidth, 3, $outputlangs->transnoentities("SynergiesTechPDFInterventionType") . " : " . $extrafields->showOutputField('ei_type', $object->array_options['options_ei_type']), $multiCellBorder, 'L');

            // Estimated date
            $posy += 5;
            $pdf->SetXY($posx + $bulletWidth, $posy);
            $pdf->SetTextColor(0, 0, 60);
            $this->addBullet($pdf, $bulletSize);
            $pdf->MultiCell($w - $bulletWidth, 3, $outputlangs->transnoentities("SynergiesTechPDFInterventionEstimatedDate") . " : " . dol_print_date($object->array_options['options_st_estimated_begin_date'], "day", false, $outputlangs), $multiCellBorder, 'L');

            // Ref principal company
            $posy += 5;
            $pdf->SetXY($posx + $bulletWidth, $posy);
            $pdf->SetTextColor(0, 0, 60);
            $this->addBullet($pdf, $bulletSize);
            $pdf->MultiCell($w - $bulletWidth, 3, $outputlangs->transnoentities("SynergiesTechPDFInterventionRefPrincipalCompany") . " : " . $principal_company->code_client, $multiCellBorder, 'L');

            // Ref benefactor company
            $posy += 5;
            $pdf->SetXY($posx + $bulletWidth, $posy);
            $pdf->SetTextColor(0, 0, 60);
            $this->addBullet($pdf, $bulletSize);
            $pdf->MultiCell($w - $bulletWidth, 3, $outputlangs->transnoentities("SynergiesTechPDFInterventionRefBenefactorCompany") . " : " . $benefactor_company->code_client, $multiCellBorder, 'L');

            // Write Benefactor Company Information
            //-------------------------------------
            $widthrecbox = $w;
            $posy = $this->marge_haute;
            $posx = $this->marge_gauche + $w + 4;
            $carac_benefactor_name = pdfBuildThirdpartyName($benefactor_company, $outputlangs, 1);
            $carac_benefactor = pdf_build_address($outputlangs, $this->emetteur, $benefactor_company, ($use_benefactor_contact ? $benefactor_company->contact : ''), $use_benefactor_contact, 'target', $object);

            // show benefactor frame
            call_user_func_array(array($pdf, 'SetTextColor'), $this->main_color);
            $pdf->SetFont('', 'B', $default_font_size);
            $pdf->SetXY($posx, $posy);
            $pdf->MultiCell($widthrecbox, 5, mb_strtoupper($outputlangs->transnoentities("SynergiesTechPDFInterventionBenefactorCompany"), 'UTF-8'), $multiCellBorder, 'L');
            $pdf->SetTextColor(0, 0, 0);
            $posy = $pdf->getY();

            // show benefactor name
            $pdf->SetXY($posx, $posy + 1);
            $pdf->SetFont('', 'B', $default_font_size - 2);
            $pdf->MultiCell($widthrecbox, 2, $carac_benefactor_name, $multiCellBorder, 'L');
            $posy = $pdf->getY();

            // show benefactor information
            $pdf->SetFont('', '', $default_font_size - 2);
            $pdf->SetXY($posx + $bulletWidth, $posy + 1);
            $this->addBullet($pdf, $bulletSize);
            $pdf->MultiCell($widthrecbox - $bulletWidth, 4, $carac_benefactor, $multiCellBorder, 'L');
            $posy = $pdf->getY();

            // Show recipient email
            $pdf->SetFont('', '', $default_font_size - 2);
            $pdf->SetXY($posx + $bulletWidth, $posy);
            $this->addBullet($pdf, $bulletSize);
            $pdf->MultiCell($widthrecbox - $bulletWidth, 4, (($use_benefactor_contact) ? $benefactor_company->contact->email : $benefactor_company->email), $multiCellBorder, 'L');

            // Write Principal Company Information
            //-------------------------------------
            $widthrecbox = $w;
            $posy = $this->marge_haute;
            $posx = $this->page_largeur - $this->marge_droite - $widthrecbox;
            if (!empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx = $this->marge_gauche;
            $carac_principal_name = pdfBuildThirdpartyName($principal_company, $outputlangs, 1);
            $carac_principal = pdf_build_address($outputlangs, $this->emetteur, $principal_company, ($use_principal_contact ? $principal_company->contact : ''), $use_principal_contact, 'target', $object);

            // Show recipient frame
            call_user_func_array(array($pdf, 'SetTextColor'), $this->main_color);
            $pdf->SetFont('', 'B', $default_font_size);
            $pdf->SetXY($posx, $posy);
            $pdf->MultiCell($widthrecbox, 5, $outputlangs->transnoentities("SynergiesTechPDFInterventionPrincipalCompany"), $multiCellBorder, 'L');
            $pdf->SetTextColor(0, 0, 0);
            $posy = $pdf->getY();

            // Show recipient name
            $pdf->SetXY($posx, $posy + 1);
            $pdf->SetFont('', 'B', $default_font_size - 2);
            $pdf->MultiCell($widthrecbox, 2, $carac_principal_name, $multiCellBorder, 'L');
            $posy = $pdf->getY();

            // Show recipient information
            $pdf->SetFont('', '', $default_font_size - 2);
            $pdf->SetXY($posx + $bulletWidth, $posy + 1);
            $this->addBullet($pdf, $bulletSize);
            $pdf->MultiCell($widthrecbox - $bulletWidth, 4, $carac_principal, $multiCellBorder, 'L');
            $posy = $pdf->getY();

            // Show recipient email
            $pdf->SetFont('', '', $default_font_size - 2);
            $pdf->SetXY($posx + $bulletWidth, $posy);
            $this->addBullet($pdf, $bulletSize);
            $pdf->MultiCell($widthrecbox - $bulletWidth, 4, (($use_principal_contact) ? $principal_company->contact->email : $principal_company->email), $multiCellBorder, 'L');
        }

        $pdf->SetFont('', '', $default_font_size - 1);
        $pdf->SetTextColor(0, 0, 0);
    }

    /**
     *    Show footer of page. Need this->emetteur object
     *
     * @param    PDF $pdf PDF
     * @param    Object $object Object to show
     * @param    Translate $outputlangs Object lang for output
     * @param    int $hidefreetext 1=Hide free text
     * @return    int                                Return height of bottom margin including footer text
     */
    function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
    {
        global $conf;
        $showdetails = $conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS;
        return $this->pdf_pagefoot($pdf, $outputlangs, 'FICHINTER_FREE_TEXT', $this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object, $showdetails, $hidefreetext);
    }

    /**
	 *  Load the effective working time into a array
     * @see $this->effective_working_time
	 *
	 * @param   Fichinter   $object         Object intervention
     * @param   Translate   $outputlangs    Objet langs
     *
	 * @return  void
	 */
	function _fetch_effective_working_time($object, $outputlangs)
    {
        $this->effective_working_time = array();

        if (is_array($object->lines) && count($object->lines)) {
            foreach ($object->lines as $line) {
                // Get involved user id
                $line->fetch_optionals();
                $user_id = $line->array_options['options_st_involved_users'] > 0 ? $line->array_options['options_st_involved_users'] : 0;

                if (!isset($this->effective_working_time[$user_id])) {
                    $user_name = '';
                    if ($user_id > 0) {
                        $user = new User($this->db);
                        $user->fetch($user_id);
                        $user_name = $user->getFullName($outputlangs);
                    }
                    $this->effective_working_time[$user_id] = array('name' => $user_name, 'times' => array());
                }

                $this->effective_working_time[$user_id]['times'][] = array('begin' => $line->datei, 'end' => $line->datei + $line->duration, 'duration' => $line->duration);
            }

            // Sort times of each involved users
            function effective_working_time_cmp($a, $b)
            {
                if ($a['begin'] == $b['begin']) return 0;
                return ($a['begin'] < $b['begin']) ? -1 : 1;
            }

            foreach ($this->effective_working_time as $k => $v) {
                uasort($this->effective_working_time[$k]['times'], 'effective_working_time_cmp');
            }
        }
    }

    /**
	 *  Get nb line of effective working time
	 *
	 * @param  Fichinter   $object         Object intervention
     *
	 * @return int                         Return nb line of effective working time
	 */
	function _get_nb_effective_working_time($object)
	{
        return is_array($object->lines) ? count($object->lines) : 0;
	}

    /**
	 *	Show area for the effective working time
	 *
	 * @param   PDF			$pdf            Object PDF
     * @param   Fichinter   $object         Object intervention
	 * @param   int			$posy			Position depart
	 * @param   Translate	$outputlangs	Objet langs
     *
	 * @return  int							Position pour suite
	 */
	function _effective_working_time_area(&$pdf, $object, $posy, $outputlangs)
	{
		$default_font_size = pdf_getPDFFontSize($outputlangs);
		$tab_top = $posy + 4;
		if($posy > 241 && $posy < 247) {
			$tab_hl = 6;
		} else if($posy > 241 && $posy > 247)  {
			$tab_hl = 5;
		} else {
			$tab_hl = 7;
		}

		$posx = 120;
		$largcol = ($this->page_largeur - $this->marge_droite - $posx);
		$useborder=0;
		$index = 0;
		// Total HT
		$pdf->SetFillColor(255,255,255);
		$pdf->SetXY($posx, $tab_top + 0);
		$pdf->SetFont('','', $default_font_size - 2);
		$pdf->MultiCell($largcol, $tab_hl, $outputlangs->transnoentities("ProposalCustomerSignature"), 0, 'L', 1);

		$pdf->SetXY($posx, $tab_top + $tab_hl);
		$pdf->MultiCell($largcol, $tab_hl*3, '', 1, 'R');

		return ($tab_hl*7);
	}

    /**
	 *	Show area for the technician and customer to sign
	 *
	 * @param   PDF         $pdf            Object PDF
     * @param   Fichinter   $object         Object intervention
	 * @param   int         $posy			Position depart
	 * @param   Translate   $outputlangs	Objet langs
     *
	 * @return  int							Position pour suite
	 */
	function _signature_area(&$pdf, $object, $posy, $outputlangs)
	{
		$default_font_size = pdf_getPDFFontSize($outputlangs);
		$tab_top = $posy + 4;
		if($posy > 241 && $posy < 247) {
			$tab_hl = 6;
		} else if($posy > 241 && $posy > 247)  {
			$tab_hl = 5;
		} else {
			$tab_hl = 7;
		}

        // Output Rect
        $this->printRect($pdf, $this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $tab_height + 1, 0, 0);    // Rect prend une longueur en 3eme param et 4eme param

        if (empty($hidebottom)) {
            $pdf->SetXY(20, 230);
            $pdf->MultiCell(66, 5, $outputlangs->transnoentities("NameAndSignatureOfInternalContact"), 0, 'L', 0);

            $pdf->SetXY(20, 235);
            $pdf->MultiCell(80, 25, '', 1);

            $pdf->SetXY(110, 230);
            $pdf->MultiCell(80, 5, $outputlangs->transnoentities("NameAndSignatureOfExternalContact"), 0, 'L', 0);

            $pdf->SetXY(110, 235);
            $pdf->MultiCell(80, 25, '', 1);
        }

		$posx = 120;
		$largcol = ($this->page_largeur - $this->marge_droite - $posx);
		$useborder=0;
		$index = 0;
		// Total HT
		$pdf->SetFillColor(255,255,255);
		$pdf->SetXY($posx, $tab_top + 0);
		$pdf->SetFont('','', $default_font_size - 2);
		$pdf->MultiCell($largcol, $tab_hl, $outputlangs->transnoentities("ProposalCustomerSignature"), 0, 'L', 1);

		$pdf->SetXY($posx, $tab_top + $tab_hl);
		$pdf->MultiCell($largcol, $tab_hl*3, '', 1, 'R');

		return ($tab_hl*7);
	}
}
