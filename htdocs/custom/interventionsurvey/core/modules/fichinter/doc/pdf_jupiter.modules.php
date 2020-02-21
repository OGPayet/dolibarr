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
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

dol_include_once('/interventionsurvey/lib/libPDFST.trait.php');
dol_include_once('/interventionsurvey/lib/opendsi_pdf.lib.php');
dol_include_once('/interventionsurvey/class/interventionsurvey.class.php');

/**
 *	Class to build interventions documents with model Soleil with company relationships
 */
class pdf_jupiter_st extends ModelePDFFicheinter
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

    var $extrafields_question_bloc;
    var $extralabels_question_bloc;
    var $extrafields_question;
    var $extralabels_question;

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
        $this->name = 'jupiter_st';
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

            $temp_dir_signature = DOL_DATA_ROOT . '/synergiestech/temp/'.$object->element.'_'.$object->id;
            if (file_exists($temp_dir_signature)) {
                unlink($temp_dir_signature);
            }
            if (dol_mkdir($temp_dir_signature) < 0) {
                $this->error = $langs->transnoentities("ErrorCanNotCreateDir", $temp_dir_signature);
                return 0;
            }

            if (file_exists($dir) && file_exists($temp_dir_signature)) {
                $new_object = new ExtendedIntervention($this->db);
                $new_object->fetch($object->id);
                $object = $new_object;

                $object->fetch_survey();
                $object->fetch_thirdparty();
                $object->fetch_optionals();
                $this->_fetch_effective_working_time($object, $outputlangs);

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
                $tab_top = $this->_pagehead($pdf, $object, 1, $outputlangs);
                $pdf->SetFont('', '', $default_font_size - 1);
                $pdf->SetTextColor(0, 0, 0);

                $pdf->startTransaction();
                $heightforeffectiveworkingtime = $this->_effective_working_time_area($pdf, $object, 0, $outputlangs);
                $heightforsignature = $this->_signature_area($pdf, $object, 0, $outputlangs);
                $tab_top_without_address = $this->_pagehead($pdf, $object, 0, $outputlangs);
                $pdf->rollbackTransaction(true);

                $heightforinfotot = 0;    // Height reserved to output the info and total part
                $heightforsignature = max($heightforsignature, $heightforeffectiveworkingtime);  // Height reserved to output the effective working time info and signature part
                $heightforfreetext = (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT) ? $conf->global->MAIN_PDF_FREETEXT_HEIGHT : 5);    // Height reserved to output the free text on last page
                $heightforfooter = $this->marge_basse + 8;    // Height reserved to output the footer (value include bottom margin)

                $tab_top_newpage = (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD) ? $tab_top_without_address : 10);
                $pdf->setTopMargin($tab_top_newpage + 5);
                $pdf->setPageOrientation('', 1, $heightforfooter);	// The only function to edit the bottom margin of current page to set it.

                // Affiche notes
                $notetoshow = empty($object->note_public) ? '' : $object->note_public;
                if ($notetoshow) {
                    $tab_top = $tab_top + 5;

                    $pdf->SetFont('', '', $default_font_size - 1);
                    $pdf->writeHTMLCell(190, 3, $this->posxdesc - 1, $tab_top, dol_htmlentitiesbr($notetoshow), 0, 1);
                    $nexY = $pdf->GetY();
                    $height_note = $nexY - $tab_top;

                    // Rect prend une longueur en 3eme param
                    $pdf->SetDrawColor(192, 192, 192);
                    $pdf->Rect($this->marge_gauche, $tab_top - 1, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $height_note + 1);

                    $tab_top = $nexY + 5;
                }

                $iniY = $curY = $nexY = $tab_top;

                // Print survey
                foreach ($object->survey as $survey_bloc) {
                    $curY = $this->_survey_bloc_area($pdf, $object, $survey_bloc, $curY, $outputlangs, $heightforfooter) + 2;
                }

                $bottomlasttab = $this->page_hauteur - $heightforinfotot - $heightforsignature - $heightforfreetext - $heightforfooter + 3;

                if ($curY > $bottomlasttab) {
                    $pdf->AddPage('', '', true);
                    if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
                }

                // Show effective working time
                $this->_effective_working_time_area($pdf, $object, $bottomlasttab, $outputlangs);

                // Show signature
                $this->_signature_area($pdf, $object, $bottomlasttab, $outputlangs);

                $pdf->setPageOrientation('', 1, 0);	// The only function to edit the bottom margin of current page to set it.
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
            } elseif (!file_exists($dir)) {
                $this->error = $langs->trans("ErrorCanNotCreateDir", $dir);
                return 0;
            } else {
                $this->error = $langs->trans("ErrorCanNotCreateDir", $temp_dir_signature);
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
     * @return  int							    Position pour suite
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

        $max_y = $pdf->GetY();

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

        $max_y = max($max_y, $pdf->GetY()) + 5;
        $max_y = min($max_y, 40);

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

            $max_y = max($max_y, $pdf->GetY());

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

            $max_y = max($max_y, $pdf->GetY());

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

            $max_y = max($max_y, $pdf->GetY());
        }

        $pdf->SetFont('', '', $default_font_size - 1);
        $pdf->SetTextColor(0, 0, 0);

        return $max_y;
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
	 *	Show area for the survey bloc
	 *
	 * @param   PDF			    $pdf                Object PDF
     * @param   Fichinter       $object             Object intervention
     * @param   EISurveyBloc    $survey_bloc        Object survey block
     * @param   int			    $posy			    Position Y of the bloc
	 * @param   Translate	    $outputlangs	    Objet langs
     * @param   int			    $heightforfooter	Height for footer
     *
	 * @return  int							        Position pour suite
	 */
	function _survey_bloc_area(&$pdf, $object, $survey_bloc, $posy, $outputlangs, $heightforfooter)
    {
        global $conf;

        $default_font_size = pdf_getPDFFontSize($outputlangs);

        $posx = $this->marge_droite;
        $w = $this->page_largeur - $this->marge_gauche - $this->marge_droite;

        $column_left_w = ($w - 4) / 2;
        $column_right_w = $column_left_w;
        $column_right_posx = $this->page_largeur - $this->marge_droite - $column_left_w;

        // Define colors and font size
        $pdf->SetFont('', 'B', $default_font_size);
        call_user_func_array(array($pdf, 'SetFillColor'), $this->main_color);
        call_user_func_array(array($pdf, 'SetDrawColor'), $this->main_color);
        $pdf->SetTextColor(255, 255, 255);

        // Print title of the table
        $survey_bloc_title = $survey_bloc->fk_equipment != 0 ? $outputlangs->transnoentities('SynergiesTechPDFInterventionSurveyBlocTitle', $survey_bloc->product_label, $survey_bloc->product_ref, $survey_bloc->equipment_ref) : $outputlangs->transnoentities('SynergiesTechPDFInterventionSurveyBlocGeneralTitle');
        $pdf->SetXY($posx, $posy);
        $pdf->MultiCell($w, 3, $survey_bloc_title, 1, 'C', 1);

        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetTextColor(0, 0, 0);

        $start_y = $pdf->GetY();
        $start_page = $pdf->GetPage();

        $nb_question_bloc = count($survey_bloc->survey);

        // Print left column
        $cur_Y = $start_y;
        $idx = 0;
        foreach ($survey_bloc->survey as $question_bloc) {
            $idx++;
            if ($idx % 2 == 0) continue;

            $cur_Y = $this->_question_bloc_area($pdf, $question_bloc, $posx, $cur_Y, $column_left_w, $outputlangs, $idx < $nb_question_bloc - 1);
        }

        $end_y = $pdf->GetY();
        $end_page = $pdf->getPage();

        $pdf->SetPage($start_page);
        $pdf->SetY($start_y);

        // Print right column
        $cur_Y = $start_y;
        $idx = 0;
        foreach ($survey_bloc->survey as $question_bloc) {
            $idx++;
            if ($idx % 2 == 1) continue;

            $cur_Y = $this->_question_bloc_area($pdf, $question_bloc, $column_right_posx, $cur_Y, $column_right_w, $outputlangs, $idx < $nb_question_bloc);
        }

        if ($end_y < $pdf->GetY() || $end_page < $pdf->getPage()) {
            $end_y = $pdf->GetY();
            $end_page = $pdf->getPage();
        }

        // Print frame
        if ($end_page == $start_page) {
            // Draw frame
            call_user_func_array(array($pdf, 'SetDrawColor'), $this->main_color);
            $pdf->Rect($posx, $start_y, $w, $end_y - $start_y);
            $pdf->SetDrawColor(0, 0, 0);
        } else {
            $dash_style = array('dash' => '10,10', 'color' => $this->main_color);
            $no_style = array('dash' => 0);
            for ($page = $start_page; $page <= $end_page; ++$page) {
                $pdf->setPage($page);
                $page_height = $pdf->getPageHeight();
                $page_margins = $pdf->getMargins();

                // First page
                if ($page == $start_page) {
                    // Print Footer
                    $pdf->setPageOrientation('', 1, 0);    // The only function to edit the bottom margin of current page to set it.
                    $this->_pagefoot($pdf, $object, $outputlangs, 1);
                    $pdf->setPageOrientation('', 1, $heightforfooter);    // The only function to edit the bottom margin of current page to set it.

                    // Draw frame
                    call_user_func_array(array($pdf, 'SetDrawColor'), $this->main_color);
                    $pdf->line($posx, $start_y, $posx, $page_height - $page_margins['bottom']); // Left
                    $pdf->line($posx + $w, $start_y, $posx + $w, $page_height - $page_margins['bottom']); // Right
                    $pdf->SetLineStyle($dash_style);
                    $pdf->line($posx, $page_height - $page_margins['bottom'], $posx + $w, $page_height - $page_margins['bottom']); // Bottom
                    $pdf->SetLineStyle($no_style);
                    $pdf->SetDrawColor(0, 0, 0);
                } // Last page
                elseif ($page == $end_page) {
                    // Print Header
                    if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);

                    // Draw frame
                    call_user_func_array(array($pdf, 'SetDrawColor'), $this->main_color);
                    $pdf->line($posx, $page_margins['top'], $posx, $end_y); // Left
                    $pdf->line($posx + $w, $page_margins['top'], $posx + $w, $end_y); // Right
                    $pdf->line($posx, $end_y, $posx + $w, $end_y); // Bottom
                    $pdf->SetLineStyle($dash_style);
                    $pdf->line($posx, $page_margins['top'], $posx + $w, $page_margins['top']); // Top
                    $pdf->SetLineStyle($no_style);
                    $pdf->SetDrawColor(0, 0, 0);
                } // Middle page
                else {
                    // Print Header
                    if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);

                    // Print Footer
                    $pdf->setPageOrientation('', 1, 0);    // The only function to edit the bottom margin of current page to set it.
                    $this->_pagefoot($pdf, $object, $outputlangs, 1);
                    $pdf->setPageOrientation('', 1, $heightforfooter);    // The only function to edit the bottom margin of current page to set it.

                    // Draw frame
                    call_user_func_array(array($pdf, 'SetDrawColor'), $this->main_color);
                    $pdf->line($posx, $page_margins['top'], $posx, $page_margins['bottom']); // Left
                    $pdf->line($posx + $w, $page_margins['top'], $posx + $w, $page_margins['bottom']); // Right
                    $pdf->SetLineStyle($dash_style);
                    $pdf->line($posx, $page_margins['top'], $posx + $w, $page_margins['top']); // Top
                    $pdf->line($posx, $page_margins['bottom'], $posx + $w, $page_margins['bottom']); // Bottom
                    $pdf->SetLineStyle($no_style);
                    $pdf->SetDrawColor(0, 0, 0);
                }
            }
        }

        $pdf->SetPage($end_page);
        $pdf->SetY($end_y);
        $posy = $end_y;

        return $posy;
    }

    /**
	 *	Show area for the question bloc
	 *
	 * @param   PDF			    $pdf            Object PDF
     * @param   EIQuestionBloc  $question_bloc  Object question block
     * @param   int			    $posx			Position X of the bloc
     * @param   int			    $posy			Position Y of the bloc
     * @param   int			    $width			Width of the bloc
	 * @param   Translate	    $outputlangs	Objet langs
     * @param   int	            $addline    	1=Add dash line after the bloc
     *
	 * @return  int							    Position pour suite
	 */
	function _question_bloc_area(&$pdf, $question_bloc, $posx, $posy, $width, $outputlangs, $addline=0)
    {
        $default_font_size = pdf_getPDFFontSize($outputlangs);

        $pdf->SetFont('', '', $default_font_size - 1);   // Into loop to work with multipage
        $pdf->SetTextColor(0, 0, 0);
        $margin = 0;
        $padding = 1;
        $border = 0;

        // Define info status of the question bloc
        $circle_ray = 1.25;
        $circle_offset = $circle_ray + 1;
        $circle_style = '';
        $circle_fill_color = array();
        if (!empty($question_bloc->color_status)) {
            $circle_style = 'F';
            list($r, $g, $b) = sscanf($question_bloc->color_status, "#%02x%02x%02x");
            $circle_fill_color = array($r, $g, $b);
        }

        // Save for the calculation of the position Y origin
        $page_origin = $pdf->getPage();
        $last_page_margins = $pdf->getMargins();
        $posy_origin = $posy;

        // Print label (+ complementary text and status justificatory) of the question bloc
        $question_bloc_complementary_text = $question_bloc->complementary_question_bloc . (!empty($question_bloc->complementary_question_bloc) && !empty($question_bloc->justificatory_status) ? ' - ' : '') . $question_bloc->justificatory_status;
        $question_bloc_title = '<b><font size="' . $default_font_size . '">' . $question_bloc->label_question_bloc . (!empty($question_bloc_complementary_text) ? '&nbsp;->&nbsp;</font><font size="' . ($default_font_size - 1) . '">' . $question_bloc_complementary_text : '') . '</font></b>';
        $pdf->writeHTMLCell($width - ($circle_offset * 2 + $margin), 3, $posx + $circle_offset * 2 + $margin, $posy, trim($question_bloc_title), $border, 1, false, true, 'L', true);
        $posy = $pdf->GetY();
        $page = $pdf->getPage();

        // Position Y origin
        if ($page_origin != $page) {
            $text_height = $pdf->getStringHeight(100, '<b><font size="' . $default_font_size . '">pP</font></b>', true);
            $last_page_text_height = $last_page_margins['bottom'] - $posy_origin;
            if ($text_height > $last_page_text_height) {
                $page_margins = $pdf->getMargins();
                $posy_origin = $page_margins['top'];
                $page_origin = $page;
            }
        }

        // Print status of the question bloc
        $pdf->setPage($page_origin);
        $pdf->Circle($posx + $circle_offset, $posy_origin + $circle_offset, $circle_ray, 0, 360, $circle_style, array(), $circle_fill_color);
        $pdf->setPage($page);

        $posx_question = $posx + $circle_offset * 2 + $padding;
        $width_question = $width - ($circle_offset * 2 + $padding);

        $bullet_ray = 0.5;
        $circle_ray = 1;
        $circle_offset = $circle_ray + 1;
        $posx_question_extrafield = $posx_question + $circle_offset * 2 + $padding;
        $width_question_extrafield = $width_question - ($circle_offset * 2 + $padding);

        // Print extrafields of the question bloc
        $question_bloc->fetch_optionals();
        foreach ($question_bloc->extrafields_question_bloc as $key) {
            // Save for the calculation of the position Y origin
            $page_origin = $pdf->getPage();
            $last_page_margins = $pdf->getMargins();
            $posy_origin = $posy;

            // Print label and value of the extrafield
            $question_bloc_extrafield = '<b><font size="' . ($default_font_size - 1) . '">' . $this->extrafields_question_bloc->attribute_label[$key] . '&nbsp;:&nbsp;</font></b><font size="' . ($default_font_size - 2) . '">' . $this->extrafields_question_bloc->showOutputField($key, $question_bloc->array_options['options_' . $key]) . '</font>';
            $pdf->writeHTMLCell($width_question - ($circle_offset * 2 + $margin), 3, $posx_question + $circle_offset * 2 + $margin, $posy, trim($question_bloc_extrafield), $border, 1, false, true, 'L', true);
            $posy = $pdf->GetY();
            $page = $pdf->getPage();

            // Position Y origin
            if ($page_origin != $page) {
                $text_height = $pdf->getStringHeight(100, '<b><font size="' . ($default_font_size - 1) . '">pP</font></b>', true);
                $last_page_text_height = $last_page_margins['bottom'] - $posy_origin;
                if ($text_height > $last_page_text_height) {
                    $page_margins = $pdf->getMargins();
                    $posy_origin = $page_margins['top'];
                    $page_origin = $page;
                }
            }

            // Print bullet of the extrafield
            $pdf->setPage($page_origin);
            $pdf->Circle($posx_question + $circle_offset, $posy_origin + $circle_offset, $bullet_ray, 0, 360, 'F', array(), array(0, 0, 0));
            $pdf->setPage($page);
        }

        // Print questions
        foreach ($question_bloc->lines as $line) {
            // Define info for color answer of the question
            $circle_style = '';
            $circle_fill_color = array();
            if (!empty($line->color_answer)) {
                $circle_style = 'F';
                list($r, $g, $b) = sscanf($line->color_answer, "#%02x%02x%02x");
                $circle_fill_color = array($r, $g, $b);
            }

            // Save for the calculation of the position Y origin
            $page_origin = $pdf->getPage();
            $last_page_margins = $pdf->getMargins();
            $posy_origin = $posy;

            // Print label (+ answer justificatory) of the question
            $question_answer = '<b><font size="' . ($default_font_size - 1) . '">' . $line->label_question . '&nbsp;:&nbsp;</font></b><font size="' . ($default_font_size - 2) . '">' . $line->text_answer . '</font>';
            $pdf->writeHTMLCell($width_question - ($circle_offset * 2 + $margin), 3, $posx_question + $circle_offset * 2 + $margin, $posy, trim($question_answer), $border, 1, false, true, 'L', true);
            $posy = $pdf->GetY();
            $page = $pdf->getPage();

            // Position Y origin
            if ($page_origin != $page) {
                $text_height = $pdf->getStringHeight(100, '<b><font size="' . ($default_font_size - 1) . '">pP</font></b>', true);
                $last_page_text_height = $this->page_hauteur - $last_page_margins['bottom'] - $posy_origin;
                if ($text_height > $last_page_text_height) {
                    $page_margins = $pdf->getMargins();
                    $posy_origin = $page_margins['top'];
                    $page_origin = $page;
                }
            }

            // Print color answer of the question
            $pdf->setPage($page_origin);
            $pdf->Circle($posx_question + $circle_offset, $posy_origin + $circle_offset, $circle_ray, 0, 360, $circle_style, array(), $circle_fill_color);
            $pdf->setPage($page);

            // Print extrafields of the question
            $line->fetch_optionals();
            foreach ($line->extrafields_question as $key) {
                // Save for the calculation of the position Y origin
                $page_origin = $pdf->getPage();
                $last_page_margins = $pdf->getMargins();
                $posy_origin = $posy;

                // Print label and value of the extrafield
                $question_bloc_extrafield = '<b><font size="' . ($default_font_size - 1) . '">' . $this->extrafields_question->attribute_label[$key] . '&nbsp;:&nbsp;</font></b><font size="' . ($default_font_size - 2) . '">' . $this->extrafields_question->showOutputField($key, $line->array_options['options_' . $key]) . '</font>';
                $pdf->writeHTMLCell($width_question_extrafield - ($circle_offset * 2 + $margin), 3, $posx_question_extrafield + $circle_offset * 2 + $margin, $posy, trim($question_bloc_extrafield), $border, 1, false, true, 'L', true);
                $posy = $pdf->GetY();
                $page = $pdf->getPage();

                // Position Y origin
                if ($page_origin != $page) {
                    $text_height = $pdf->getStringHeight(100, '<b><font size="' . ($default_font_size - 1) . '">pP</font></b>', true);
                    $last_page_text_height = $last_page_margins['bottom'] - $posy_origin;
                    if ($text_height > $last_page_text_height) {
                        $page_margins = $pdf->getMargins();
                        $posy_origin = $page_margins['top'];
                        $page_origin = $page;
                    }
                }

                // Print bullet of the extrafield
                $pdf->setPage($page_origin);
                $pdf->Circle($posx_question_extrafield + $circle_offset, $posy_origin + $circle_offset, $bullet_ray, 0, 360, 'F', array(), array(0, 0, 0));
                $pdf->setPage($page);
            }
        }

        if ($addline) {
            $posy += 1;
            // Print dash line
            $pdf->SetLineStyle(array('dash' => '0.5', 'color' => array(0, 0, 0)));
            $pdf->line($posx, $posy, $posx + $width, $posy);
            $pdf->SetLineStyle(array('dash' => '0'));
        }

        $posy += 1;

        return $posy;
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
        $user_cached = array();

        if (is_array($object->lines) && count($object->lines)) {
            foreach ($object->lines as $line) {
                // Get involved user id
                $line->fetch_optionals();
                $user_ids = !empty($line->array_options['options_st_involved_users']) ? explode(',', $line->array_options['options_st_involved_users']) : array('');

                foreach ($user_ids as $user_id) {
                    if (!isset($this->effective_working_time[$user_id])) {
                        if (!isset($user_cached[$user_id])) {
                            $user_name = '';
                            if ($user_id > 0) {
                                $user = new User($this->db);
                                $user->fetch($user_id);
                                $user_name = $user->getFullName($outputlangs);
                            }
                            $user_cached[$user_id] = $user_name;
                        }
                        $this->effective_working_time[$user_id] = array('name' => $user_cached[$user_id], 'times' => array());
                    }

                    $this->effective_working_time[$user_id]['times'][] = array('begin' => $line->datei, 'end' => $line->datei + $line->duration, 'duration' => $line->duration);
                }
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
	 * @return  int							Height of the area
	 */
	function _effective_working_time_area(&$pdf, $object, $posy, $outputlangs)
    {
        $default_font_size = pdf_getPDFFontSize($outputlangs);

        $table_padding_x = 0.5;
        $table_padding_y = 1;

        $top_posy = $posy;
        $w = ($this->page_largeur - $this->marge_gauche - $this->marge_droite - 4) / 2;
        $column_w_from = 30;
        $column_w_to = 15;
        $column_w_duration = 15;
        $column_w_involved_user = $w - $column_w_duration - $column_w_to - $column_w_from;

        $column_posx_involved_user = $this->marge_gauche;
        $column_posx_from = $column_posx_involved_user + $column_w_involved_user;
        $column_posx_to = $column_posx_from + $column_w_from;
        $column_posx_duration = $column_posx_to + $column_w_to;
        $column_posx_end_table = $column_posx_duration + $column_w_duration;

        // Define colors and font size
        $pdf->SetFont('', 'B', $default_font_size);
        call_user_func_array(array($pdf, 'SetFillColor'), $this->main_color);
        call_user_func_array(array($pdf, 'SetDrawColor'), $this->main_color);
        $pdf->SetTextColor(255, 255, 255);

        // Print title of the table
        $pdf->SetXY($column_posx_involved_user, $posy);
        $pdf->MultiCell($w, 3, $outputlangs->transnoentities("SynergiesTechPDFInterventionEffectiveWorkingTimeTitle"), 1, 'C', 1);

        // Define positions, colors and font size
        $posy = $top_table = $pdf->GetY();
        $max_posy = $posy;
        $pdf->SetFont('', 'B', $default_font_size - 1);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetTextColor(0, 0, 0);

        // Print involved user header of the table
        $pdf->SetXY($column_posx_involved_user + $table_padding_x, $posy + $table_padding_y);
        $pdf->MultiCell($column_w_involved_user - ($table_padding_x * 2), 3, $outputlangs->transnoentities("SynergiesTechPDFInterventionEffectiveWorkingTimeInvolvedUser"), 0, 'C', 0);
        $max_posy = max($pdf->GetY(), $max_posy);

        // Print from header of the table
        $pdf->SetXY($column_posx_from + $table_padding_x, $posy + $table_padding_y);
        $pdf->MultiCell($column_w_from - ($table_padding_x * 2), 3, $outputlangs->transnoentities("SynergiesTechPDFInterventionEffectiveWorkingTimeFrom"), 0, 'C', 0);
        $max_posy = max($pdf->GetY(), $max_posy);

        // Print to header of the table
        $pdf->SetXY($column_posx_to + $table_padding_x, $posy + $table_padding_y);
        $pdf->MultiCell($column_w_to - ($table_padding_x * 2), 3, $outputlangs->transnoentities("SynergiesTechPDFInterventionEffectiveWorkingTimeTo"), 0, 'C', 0);
        $max_posy = max($pdf->GetY(), $max_posy);

        // Print duration header of the table
        $pdf->SetXY($column_posx_duration + $table_padding_x, $posy + $table_padding_y);
        $pdf->MultiCell($column_w_duration - ($table_padding_x * 2), 3, $outputlangs->transnoentities("SynergiesTechPDFInterventionEffectiveWorkingTimeDuration"), 0, 'C', 0);
        $max_posy = max($pdf->GetY(), $max_posy);

        // Print bottom line
        $posy = $max_posy + $table_padding_y;
        $pdf->line($column_posx_involved_user, $posy, $column_posx_end_table, $posy);

        // Define positions, colors and font size
        $pdf->SetFont('', '', $default_font_size - 1);

        $total_duration = 0;

        // Print effective working time
        //-------------------------------------
        foreach ($this->effective_working_time as $user) {
            $max_user_posy = $posy;
            if (is_array($user['times'])) {
                $idx = 0;
                $nb_times = count($user['times']);
                $last_date = '';
                $top_cell = $posy;

                foreach ($user['times'] as $time) {
                    $idx++;
                    $date = dol_print_date($time['begin'], 'day');
                    if ($last_date == $date) {
                        $date = '';
                    } else {
                        $last_date = $date;
                        $date .= ' ';
                    }
                    $date .= dol_print_date($time['begin'], 'hour');

                    // Print from value
                    $pdf->SetXY($column_posx_from + $table_padding_x, $posy + $table_padding_y);
                    $pdf->MultiCell($column_w_from - ($table_padding_x * 2), 3, $date, 0, 'R', 0);
                    $max_user_posy = max($pdf->GetY(), $max_user_posy);

                    // Print to value
                    $pdf->SetXY($column_posx_to + $table_padding_x, $posy + $table_padding_y);
                    $pdf->MultiCell($column_w_to - ($table_padding_x * 2), 3, dol_print_date($time['end'], 'hour'), 0, 'C', 0);
                    $max_user_posy = max($pdf->GetY(), $max_user_posy);

                    // Print duration value
                    $pdf->SetXY($column_posx_duration + $table_padding_x, $posy + $table_padding_y);
                    $pdf->MultiCell($column_w_duration - ($table_padding_x * 2), 3, $this->_print_duration($time['duration']), 0, 'C', 0);
                    $max_user_posy = max($pdf->GetY(), $max_user_posy);
                    $total_duration += $time['duration'];

                    $posy = $max_user_posy + $table_padding_y;
                    if ($idx != $nb_times) {
                        // Print bottom line
                        $pdf->line($column_posx_from, $posy, $column_posx_end_table, $posy);
                    }
                }

                // Print user name
                $pdf->SetXY($column_posx_involved_user + $table_padding_x, $top_cell + $table_padding_y);
                $pdf->MultiCell($column_w_involved_user - ($table_padding_x * 2), $max_user_posy - $top_cell - ($table_padding_y * 2), $user['name'], 0, 'C', 0, 1, '', '', true, 0, false, true, 0, 'M');
                $max_user_posy = max($pdf->GetY(), $max_user_posy);

                // Print bottom line
                $posy = $max_user_posy + $table_padding_y;
                $pdf->line($column_posx_involved_user, $posy, $column_posx_end_table, $posy);
            }
        }

        // Print involved user left line
        $pdf->line($column_posx_involved_user, $top_table, $column_posx_involved_user, $posy);
        // Print from left line
        $pdf->line($column_posx_from, $top_table, $column_posx_from, $posy);

        // Define positions, colors and font size
        $pdf->SetFont('', 'B', $default_font_size);

        // Print total label
        $pdf->SetXY($column_posx_to + $table_padding_x, $posy + $table_padding_y);
        $pdf->MultiCell($column_w_to - ($table_padding_x * 2), 3, $outputlangs->transnoentities("SynergiesTechPDFInterventionEffectiveWorkingTimeTotal"), 0, 'C', 0);
        $max_posy = max($pdf->GetY(), $max_posy);

        // Print total value
        $pdf->SetXY($column_posx_duration + $table_padding_x, $posy + $table_padding_y);
        $pdf->MultiCell($column_w_duration - ($table_padding_x * 2), 3, $this->_print_duration($total_duration), 0, 'C', 0);
        $max_posy = max($pdf->GetY(), $max_posy);

        $posy = $max_posy + $table_padding_y;

        // Print to left line
        $pdf->line($column_posx_to, $top_table, $column_posx_to, $posy);
        // Print duration left line
        $pdf->line($column_posx_duration, $top_table, $column_posx_duration, $posy);
        // Print duration right line
        $pdf->line($column_posx_end_table, $top_table, $column_posx_end_table, $posy);
        // Print bottom line
        $pdf->line($column_posx_to, $posy, $column_posx_end_table, $posy);

        return $posy - $top_posy;
    }

    /**
	 *	Show area for the technician and customer to sign
	 *
	 * @param   PDF         $pdf            Object PDF
     * @param   Fichinter   $object         Object intervention
	 * @param   int         $posy			Position depart
	 * @param   Translate   $outputlangs	Objet langs
     *
	 * @return  int							Height of the area
	 */
	function _signature_area(&$pdf, $object, $posy, $outputlangs)
    {
        $default_font_size = pdf_getPDFFontSize($outputlangs);
        $signature_font_size = $default_font_size - 6;

        $border = 0;
        $w = ($this->page_largeur - $this->marge_gauche - $this->marge_droite - 4) / 2;
        $temp_dir_signature = DOL_DATA_ROOT . '/synergiestech/temp/' . $object->element . '_' . $object->id;

        $start_y = $end_y = $posy;
        $signature_left_posx = $this->marge_droite + $w + 4;
        $signature_left_w = ($w - 4) / 2;
        $signature_right_posx = $signature_left_posx + $signature_left_w + 4;
        $signature_right_w = $signature_left_w;

        // Stakeholder signature
        //----------------------------------
        $signature_info = !empty($object->array_options['options_st_stakeholder_signature']) ? json_decode($object->array_options['options_st_stakeholder_signature'], true) : array();
        $signature_info_day = dol_print_date($signature_info['date'], 'day', false, $outputlangs);
        $signature_info_day = !empty($signature_info_day) ? $signature_info_day : '...';
        $signature_info_hour = dol_print_date($signature_info['date'], 'hour', false, $outputlangs);
        $signature_info_hour = !empty($signature_info_hour) ? $signature_info_hour : '...';
        $signature_info_people = $signature_info['people'];
        $signature_info_image = $signature_info['value'];

        // Print image
        if (!empty($signature_info_image)) {
            $img_src1 = $temp_dir_signature.'/signature1';
            $imageContent = @file_get_contents($signature_info_image);
            @file_put_contents($img_src1, $imageContent);

            $pdf->writeHTMLCell($signature_left_w, 1, $signature_left_posx, $posy, '<img src="' . $img_src1 . '"/>', $border, 1);
            $posy = $pdf->GetY();
        }

        // Print texte
        $signature_title = $outputlangs->transnoentities('SynergiesTechPDFInterventionSignatureStakeholderTitle', $signature_info_day, $signature_info_hour);
        $signature_people = array();
        foreach ($signature_info_people as $person) {
            $signature_people[] = $person['name'];
        }
        $signature_text = '<font size="' . $signature_font_size . '">' . $signature_title . '<br />' . implode(', ', $signature_people) . '</font>';
        $pdf->writeHTMLCell($signature_left_w, 1, $signature_left_posx, $posy, trim($signature_text), $border, 1, false, true, 'C', true);
        $end_y = $pdf->GetY();

        $posy = $start_y;

        // Customer signature
        //----------------------------------
        $signature_info = !empty($object->array_options['options_st_customer_signature']) ? json_decode($object->array_options['options_st_customer_signature'], true) : array();
        $signature_info_day = dol_print_date($signature_info['date'], 'day', false, $outputlangs);
        $signature_info_day = !empty($signature_info_day) ? $signature_info_day : '...';
        $signature_info_hour = dol_print_date($signature_info['date'], 'hour', false, $outputlangs);
        $signature_info_hour = !empty($signature_info_hour) ? $signature_info_hour : '...';
        $signature_info_people = $signature_info['people'];
        $signature_info_image = $signature_info['value'];

        // Print image
        if (!empty($signature_info_image)) {
            $img_src2 = $temp_dir_signature.'/signature2';
            $imageContent = @file_get_contents($signature_info_image);
            @file_put_contents($img_src2, $imageContent);

            $pdf->writeHTMLCell($signature_right_w, 1, $signature_right_posx, $posy, '<img src="' . $img_src2 . '"/>', $border, 1);
            $posy = $pdf->GetY();
        }

        // Print texte
        $signature_title = $outputlangs->transnoentities('SynergiesTechPDFInterventionSignatureCustomerTitle', $signature_info_day, $signature_info_hour);
        $signature_people = array();
        foreach ($signature_info_people as $person) {
            $signature_people[] = $person['name'];
        }
        $signature_text = '<font size="' . $signature_font_size . '">' . $signature_title . '<br />' . implode(', ', $signature_people) . '</font>';
        $pdf->writeHTMLCell($signature_right_w, 1, $signature_right_posx, $posy, trim($signature_text), $border, 1, false, true, 'C', true);
        $end_y = max($end_y, $pdf->GetY());

        if(!empty($img_src1) && file_exists($img_src1)) @unlink($img_src1);
        if(!empty($img_src2) && file_exists($img_src2)) @unlink($img_src2);
        @unlink($temp_dir_signature);

        // Draw frame
        call_user_func_array(array($pdf, 'SetDrawColor'), $this->main_color);
        $pdf->Rect($signature_left_posx, $start_y, $signature_left_w, $end_y - $start_y);
        $pdf->Rect($signature_right_posx, $start_y, $signature_right_w, $end_y - $start_y);
        $pdf->SetDrawColor(0, 0, 0);

        return $end_y;
    }

    /**
     * Return the duration information array('days', 'hours', 'minutes', 'seconds')
     *
     * @param	int	    $timestamp		Duration in second
     * @param	int	    $day			Get days
     * @param   int     $hour_minute    Get hours / minutes
     * @param   int     $second         Get seconds
     *
     * @return	array                  array informations
     */
    function _get_duration($timestamp, $day = 1, $hour_minute = 1, $second = 0)
    {
        $days = $hours = $minutes = $seconds = 0;

        if (!empty($timestamp)) {
            if ($day) {
                $days = floor($timestamp / 86400);
                $timestamp -= $days * 86400;
            }

            if ($hour_minute) {
                $hours = floor($timestamp / 3600);
                $timestamp -= $hours * 3600;

                $minutes = floor($timestamp / 60);
                $timestamp -= $minutes * 60;
            }

            if ($second) {
                $seconds = $timestamp;
            }
        }

        return array('days' => $days, 'hours' => $hours, 'minutes' => $minutes, 'seconds' => $seconds);
    }

    /**
     * Return a formatted duration (x days x hours x minutes x seconds)
     *
     * @param	int	    $timestamp		Duration in second
     * @param	int	    $day			Show days
     * @param   int     $hour_minute    Show hours / minutes
     * @param   int     $second         Show seconds
     *
     * @return	string                  Formated duration
     */
    function _print_duration($timestamp, $day = 1, $hour_minute = 1, $second = 0)
    {
        $duration_infos = $this->_get_duration($timestamp, $day, $hour_minute, $second);

        $text = '';
        if ($duration_infos['days'] > 0) $text .= $duration_infos['days'] . 'j';
        if ($duration_infos['hours'] > 0) $text .= $duration_infos['hours'] . 'h';
        if ($duration_infos['minutes'] > 0) $text .= $duration_infos['minutes'] . 'm';
        if ($duration_infos['seconds'] > 0) $text .= $duration_infos['seconds'] . 's';

        return trim($text);
    }
}
