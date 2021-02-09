<?php
/* Copyright (C) 2003       Rodolphe Quiedeville        <rodolphe@quiedeville.org>
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
 *  \file       htdocs/core/modules/fichinter/doc/pdf_soleil.modules.php
 *  \ingroup    ficheinter
 *  \brief      Fichier de la classe permettant de generer les fiches d'intervention au modele Soleil
 */
require_once DOL_DOCUMENT_ROOT . '/core/modules/fichinter/modules_fichinter.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
include_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
dol_include_once('/interventionsurvey/lib/libPDFST.trait.php');
dol_include_once('/interventionsurvey/lib/interventionsurvey.helper.php');
dol_include_once('/interventionsurvey/lib/opendsi_pdf.lib.php');
dol_include_once('/interventionsurvey/lib/interventionsurvey_jupiter.lib.php');
dol_include_once('/interventionsurvey/class/interventionsurvey.class.php');


function sortImageByPage($a, $b)
{
    if ($a["startPage"] == $b["startPage"]) {
        return 0;
    } else {
        return $a["startPage"] < $b["startPage"] ? 1 : -1;
    }
}

function sortImageByHeight($a, $b)
{
    if ($a["imageHeight"] == $b["imageHeight"]) {
        return 0;
    } else {
        return $a["imageHeight"] < $b["imageHeight"] ? 1 : -1;
    }
}
// Sort times of each involved users
function effective_working_time_cmp($a, $b)
{
    if ($a['begin'] == $b['begin']) {
        return 0;
    }
    return ($a['begin'] < $b['begin']) ? -1 : 1;
}

require_once __DIR__ . '/../../../../vendor/autoload.php';

use Tcpdf\Extension\Table\Table;

/**
 *  Class to build interventions documents with model Soleil with company relationships
 */
class pdf_jupiter extends ModelePDFFicheinter
{
    use lib_interventionsurvey_PDFST;
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
    var $top_margin;
    var $main_color = array(192, 0, 0);

    var $emetteur;    // Objet societe qui emet

    private $tempdir;
    /**
     *    Constructor
     *
     * @param        DoliDB $db Database handler
     */
    function __construct($db)
    {
        global $conf, $langs, $mysoc;

        $this->db = $db;
        $this->name = 'jupiter';
        $langs->load("interventionsurvey@interventionsurvey");
        $this->description = $langs->trans("InterventionSurveyJupiterPDFName");

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
        if (empty($this->emetteur->country_code)) {
            $this->emetteur->country_code = substr($langs->defaultlang, -2);    // By default, if was not defined
        }

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

        if (!is_object($outputlangs)) {
            $outputlangs = $langs;
        }
        // For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
        if (!empty($conf->global->MAIN_USE_FPDF)) {
            $outputlangs->charset_output = 'ISO-8859-1';
        }

        $outputlangs->load("main");
        $outputlangs->load("dict");
        $outputlangs->load("companies");
        $outputlangs->load("interventionsurvey@interventionsurvey");
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

            $this->tempdir = $conf->interventionsurvey->dir_output . '/temp/' . $object->id;
            if (!file_exists($this->tempdir)) {
                if (dol_mkdir($this->tempdir) < 0) {
                    $this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
                    return 0;
                }
            }
                $new_object = new InterventionSurvey($this->db);
                $new_object->fetch($object->id, '', true, true);
                $object = $new_object;
                $object->fetch_thirdparty();
                $effective_working_time = $this->_fetch_effective_working_time($object, $outputlangs);

                // Add pdfgeneration hook
            if (!is_object($hookmanager)) {
                $hookmanager = new HookManager($this->db);
            }

                $hookmanager->initHooks(array('pdfgeneration'));
                $parameters = array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs, 'pdfInstance' => &$this);
                global $action;
                $reshook = $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action);
                // Note that $action and $object may have been modified by some hooks

                // Create pdf instance
                $pdf = opendsi_interventionsurvey_pdf_getInstance($this->format);
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
            if (!empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) {
                $pdf->SetCompression(false);
            }

                $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right

                // New page
                $pdf->AddPage();
            if (!empty($tplidx)) {
                $pdf->useTemplate($tplidx);
            }
                $tab_top = $this->_pagehead($pdf, $object, 1, $outputlangs);
                $pdf->SetFont('', '', $default_font_size - 1);
                $pdf->SetTextColor(0, 0, 0);

                //we measure how many height is needed for page head
                $neededSpaceForPageHead = $this->getHeightForPageHead($pdf, $object, 0, $outputlangs);

                $tab_top_without_address = $neededSpaceForPageHead['heightOnLastPage'];

                $heightforinfotot = 0;    // Height reserved to output the info and total part
                $heightforfreetext = (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT) ? $conf->global->MAIN_PDF_FREETEXT_HEIGHT : 5);    // Height reserved to output the free text on last page
                $heightforfooter = $this->marge_basse + 8;    // Height reserved to output the footer (value include bottom margin)
            if ($neededSpaceForPageHead['numberOfPageCreated'] > 0) {
                $conf->global->MAIN_PDF_DONOTREPEAT_HEAD = 1;
                $tab_top_without_address = 10;
            }
                $tab_top_newpage = (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD) ? $tab_top_without_address : 10);
                $this->top_margin = $tab_top_newpage + 5;
                $pdf->setTopMargin($this->top_margin);
                $pdf->setPageOrientation('', 1, $heightforfooter);    // The only function to edit the bottom margin of current page to set it.


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
                $curY = $tab_top + 7;
                $listOfAttachedFiles = getListOfAttachedFiles($object->ref);

                $posx = $this->marge_gauche;
                $w = $this->page_largeur - $this->marge_gauche - $this->marge_droite;

                // Print survey
            foreach ($object->survey as $survey_part) {
                if ($survey_part->doesThisSurveyPartContainsAtLeastOnePublicBloc()) {
                    $curY = $this->_survey_bloc_part($pdf, $object, $survey_part, $posx, $curY, $w, $outputlangs, $heightforfooter, $listOfAttachedFiles) + 2;
                }
            }

                //We display intervention lines informations
                $curY = $this->displayDescriptionContents($pdf, $object, $posx, $curY, $w, $outputlangs, $heightforfooter, $default_font_size);

                //We display working time and signatory area
                $curY = $this->displayWorkingTimeAndSignatoryArea($pdf, $object, $effective_working_time, $curY, $outputlangs);

                $endPage = $pdf->getPage();

                //We add footer on first page
                $pdf->setPage(1);
                $this->printFooterOnCurrentPage($pdf, $object, $outputlangs, $heightforfooter);

                //We add footer on all created page
            for ($page = 2; $page <= $endPage; $page++) {
                $pdf->setPage($page);
                $this->_pageHeadForCreatedPage($pdf, $object, $outputlangs);
                $this->printFooterOnCurrentPage($pdf, $object, $outputlangs, $heightforfooter);
            }

            if (method_exists($pdf, 'AliasNbPages')) {
                $pdf->AliasNbPages();
            }
                $pdf->Close();
                $pdf->Output($file, 'F');

                // Add pdfgeneration hook
                $hookmanager->initHooks(array('pdfgeneration'));
                $parameters = array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
                global $action;
                $reshook = $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks

            if (!empty($conf->global->MAIN_UMASK)) {
                @chmod($file, octdec($conf->global->MAIN_UMASK));
            }
                return 1;
        } else {
            $this->error = $langs->trans("ErrorConstantNotDefined", "FICHEINTER_OUTPUTDIR");
            return 0;
        }
    }

    /**
     *
     * Function to display working time area and signatory area, in the way that both items are displayed together with their foot on the foot of the last page of this document
     * @param  PDF          $pdf            Object PDF
     * @param  Fichinter    $object         Object intervention
     * @param  arrat        $effective_working_time    array containing working time
     * @param  int          $curY           Positition where we may be able to start drawing such items
     * @param  Translate    $outputlangs    Object lang for output
     */
    private function displayWorkingTimeAndSignatoryArea($pdf, $object, $effective_working_time, $curY, $outputlangs)
    {
        $margin = $pdf->getMargins();

        //We determine size of working time area and compute starting position and page to generate Working time area
        $needeSpaceForWorkingTimeArea = $this->getHeightForWorkingTimeArea($pdf, $effective_working_time, $margin['top'], $outputlangs);

        //We determine size of signatory area and compute starting position and page to generate signatory area
        $neededSpaceForSignatureArea = $this->getHeightForSignatureArea($pdf, $object, $margin['top'], $outputlangs);

        $startPage = $pdf->getPage();

        $startPageAndYForWorkingTimeArea = self::getStartPageAndYPositionInOrderToItemEndsOnFooterOfAPage($pdf, $startPage, $margin['top'], $needeSpaceForWorkingTimeArea['spaceToFooterOnLastPage'], $needeSpaceForWorkingTimeArea['numberOfPageCreated'], $curY);
        $startPageAndYForSignatoryArea = self::getStartPageAndYPositionInOrderToItemEndsOnFooterOfAPage($pdf, $startPage, $margin['top'], $neededSpaceForSignatureArea['spaceToFooterOnLastPage'], $neededSpaceForSignatureArea['numberOfPageCreated'], $curY);

        if ($startPageAndYForWorkingTimeArea['lastPage'] < $startPageAndYForSignatoryArea['lastPage']) {
            $startPageAndYForWorkingTimeArea['startPage'] += $startPageAndYForSignatoryArea['lastPage'] - $startPageAndYForWorkingTimeArea['lastPage'];
            $startPageAndYForWorkingTimeArea['lastPage'] += $startPageAndYForSignatoryArea['lastPage'] - $startPageAndYForWorkingTimeArea['lastPage'];
        } elseif ($startPageAndYForWorkingTimeArea['lastPage'] > $startPageAndYForSignatoryArea['lastPage']) {
            $startPageAndYForSignatoryArea['startPage'] += $startPageAndYForWorkingTimeArea['lastPage'] - $startPageAndYForSignatoryArea['lastPage'];
            $startPageAndYForSignatoryArea['lastPage'] += $startPageAndYForWorkingTimeArea['lastPage'] - $startPageAndYForSignatoryArea['lastPage'];
        }

        if ($startPageAndYForSignatoryArea['startPage'] > $startPage && $startPageAndYForWorkingTimeArea['startPage'] > $startPage) {
            $pdf->AddPage('', '', true);
        }
        if ($startPageAndYForWorkingTimeArea['startPage'] < $startPageAndYForSignatoryArea['startPage']) {
            // Show effective working time
            $pdf->setPage($startPageAndYForWorkingTimeArea['startPage']);
            $endYWorkingTime = $this->_effective_working_time_area($pdf, $effective_working_time, $startPageAndYForWorkingTimeArea['Y'], $outputlangs);
            // Show signature
            $pdf->setPage($startPageAndYForSignatoryArea['startPage']);
            $endYSignatory = $this->_signature_area($pdf, $object, $startPageAndYForSignatoryArea['Y'], $outputlangs);
        } else {
            // Show signature
            $pdf->setPage($startPageAndYForSignatoryArea['startPage']);
            $endYSignatory = $this->_signature_area($pdf, $object, $startPageAndYForSignatoryArea['Y'], $outputlangs);
            // Show effective working time
            $pdf->setPage($startPageAndYForWorkingTimeArea['startPage']);
            $endYWorkingTime = $this->_effective_working_time_area($pdf, $effective_working_time, $startPageAndYForWorkingTimeArea['Y'], $outputlangs);
        }
        return max($endYSignatory, $endYWorkingTime);
    }

    /** Show Top Header on page created after first page
     * @param  PDF          $pdf            Object PDF
     * @param  Fichinter    $object         Object intervention
     * @param  Translate    $outputlangs    Object lang for output
     * @return int pos_y    position after head has been printed
     */
    private function _pageHeadForCreatedPage(&$pdf, $object, $outputlangs)
    {
        global $conf;
        if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) {
            return $this->_pagehead($pdf, $object, 0, $outputlangs);
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
     * @return  int                             Position pour suite
     */
    function _pagehead(&$pdf, $object, $showaddress, $outputlangs)
    {
        global $conf, $langs;

        $outputlangs->load("main");
        $outputlangs->load("dict");
        $outputlangs->load("companies");
        $outputlangs->load("interventions");
        $outputlangs->load("interventionsurvey@interventionsurvey");
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
        if (!empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) {
            $posx = $this->page_largeur - $this->marge_droite - 80;
        }

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
            $bulletSize      = 1;
            $bulletWidth     = 6;
            $multiCellBorder = 0;
            $showBenefactor  = false;
            if ($conf->companyrelationships->enabled) {
                $benefactor_id = $object->array_options['options_companyrelationships_fk_soc_benefactor'];
                if (isset($benefactor_id) && $benefactor_id > 0 && $benefactor_id != $object->thirdparty->id) {
                    $benefactor_company = new Societe($this->db);
                    $benefactor_company->fetch($benefactor_id);
                    $showBenefactor = true;
                }
            }

            if ($showBenefactor === true) {
                $w = intval(($this->page_largeur - ($this->marge_gauche + 7) - $this->marge_droite) / 3);
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
            $title = $outputlangs->transnoentities("InterventionSurveyInterventionTitle");
            $pdf->MultiCell($w, 5, $title, $multiCellBorder, 'L');
            $pdf->SetFont('', '', $default_font_size - 2);

            // Ref
            $posy += 7;
            $pdf->SetXY($posx + $bulletWidth, $posy);
            $pdf->SetTextColor(0, 0, 60);
            $this->addBullet($pdf, $bulletSize);
            $pdf->MultiCell($w - $bulletWidth, 4, $outputlangs->transnoentities("InterventionSurveyInterventionRef") . " : " . $outputlangs->convToOutputCharset($object->ref), $multiCellBorder, 'L');

            // Type
            $extrafields = new ExtraFields($this->db);
            $extrafields->fetch_name_optionals_label($object->table_element); // fetch optionals attributes and labels
            $posy += 5;
            $pdf->SetXY($posx + $bulletWidth, $posy);
            $pdf->SetTextColor(0, 0, 60);
            $this->addBullet($pdf, $bulletSize);
            $pdf->MultiCell($w - $bulletWidth, 3, $outputlangs->transnoentities("InterventionSurveyInterventionType") . " : " . $extrafields->showOutputField('ei_type', $object->array_options['options_ei_type']), $multiCellBorder, 'L');

            // Estimated date
            $posy += 5;
            $pdf->SetXY($posx + $bulletWidth, $posy);
            $pdf->SetTextColor(0, 0, 60);
            $this->addBullet($pdf, $bulletSize);
            $pdf->MultiCell($w - $bulletWidth, 3, $outputlangs->transnoentities("InterventionSurveyEstimatedStartDate") . " : " . dol_print_date($object->array_options['options_st_estimated_begin_date'], "day", false, $outputlangs), $multiCellBorder, 'L');

            // Ref principal company
            $posy += 5;
            $pdf->SetXY($posx + $bulletWidth, $posy);
            $pdf->SetTextColor(0, 0, 60);
            $this->addBullet($pdf, $bulletSize);
            $pdf->MultiCell($w - $bulletWidth, 3, $outputlangs->transnoentities("InterventionSurveyPdfPrincipalCompanyRef") . " : " . $principal_company->code_client, $multiCellBorder, 'L');

            // Ref benefactor company
            if ($showBenefactor) {
                $posy += 5;
                $pdf->SetXY($posx + $bulletWidth, $posy);
                $pdf->SetTextColor(0, 0, 60);
                $this->addBullet($pdf, $bulletSize);
                $pdf->MultiCell($w - $bulletWidth, 3, $outputlangs->transnoentities("InterventionSurveyPdfBenefactorCompanyRef") . " : " . $benefactor_company->code_client, $multiCellBorder, 'L');
            }
            $max_y = max($max_y, $pdf->GetY());

            if ($showBenefactor) {
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
                $pdf->MultiCell($widthrecbox, 5, mb_strtoupper($outputlangs->transnoentities("InterventionSurveyPdfBenefactorCompany"), 'UTF-8'), $multiCellBorder, 'L');
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
            }
            // Show recipient
            if ($showBenefactor === false) {
                $widthrecbox = !empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 92 : 100;
                if ($this->page_largeur < 210) {
                    $widthrecbox = 84; // To work with US executive format
                }
                $widthrecbox -= 20;
            } else {
                $widthrecbox = $w;
            }


            // Write Principal Company Information
            //-------------------------------------

            $posy = $this->marge_haute;
            $posx = $this->page_largeur - $this->marge_droite - $widthrecbox;
            if (!empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) {
                $posx = $this->marge_gauche;
            }
            $carac_principal_name = pdfBuildThirdpartyName($principal_company, $outputlangs, 1);
            $carac_principal = pdf_build_address($outputlangs, $this->emetteur, $principal_company, ($use_principal_contact ? $principal_company->contact : ''), $use_principal_contact, 'target', $object);

            // Show recipient frame
            call_user_func_array(array($pdf, 'SetTextColor'), $this->main_color);
            $pdf->SetFont('', 'B', $default_font_size);
            $pdf->SetXY($posx, $posy);
            $principal_company_title = $showBenefactor ? $outputlangs->transnoentities("InterventionSurveyPdfPrincipalCompany") : $outputlangs->transnoentities("InterventionSurveyPdfCustomer");
            $pdf->MultiCell($widthrecbox, 5, $principal_company_title, $multiCellBorder, 'L');
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
        $result = $this->pdf_pagefoot($pdf, $outputlangs, 'FICHINTER_FREE_TEXT', $this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object, $showdetails, $hidefreetext);
        $pdf->SetTextColor(0, 0, 0);
        return $result;
    }

    /**
     *  Show area for the survey part
     *
     * @param   PDF             $pdf                Object PDF
     * @param   Fichinter       $object             Object intervention
     * @param   SurveyPart    $survey_part         Object survey part
     * @param   int             $posx               Position X of the bloc
     * @param   int             $posy               Position Y of the bloc
     * @param   int             $w                  Width of the bloc
     * @param   Translate       $outputlangs        Objet langs
     * @param   int             $heightforfooter    Height for footer
     * @param   array           $listOfAttachedFiles    Informations on attached files on linked fichinter

     *
     * @return  int                                 Position pour suite
     */
    function _survey_bloc_part(&$pdf, $object, $survey_part, $posx, $posy, $w, $outputlangs, $heightforfooter, $listOfAttachedFiles = array())
    {
        global $conf;

        $default_font_size = pdf_getPDFFontSize($outputlangs);

        $column_left_w = ($w - 4) / 2;
        $column_right_w = $column_left_w;
        $column_right_posx = $this->page_largeur - $this->marge_droite - $column_left_w;

        //Print survey part title
        $survey_part_title = $survey_part->label;
        $start_y = $this->printTitleForPdfPart($pdf, $posx, $posy, $w, $survey_part_title, $default_font_size);
        $start_page = $pdf->GetPage();

        // Print left column
        $left_column_cur_Y = $start_y;
        $left_column_cur_page = $start_page;
        $right_column_cur_Y = $start_y;
        $right_column_cur_page = $start_page;
        $is_this_bloc_first_of_current_page_into_left_column = true;
        $is_this_bloc_first_of_current_page_into_rigth_column = true;
        $listOfBlocsToDisplay = array_values(array_filter($survey_part->blocs, function ($bloc) {
            return !$bloc->private;
        }));

        foreach ($listOfBlocsToDisplay as $question_bloc) {
            if (($left_column_cur_Y - 10 <= $right_column_cur_Y && $left_column_cur_page == $right_column_cur_page) || $left_column_cur_page < $right_column_cur_page) {
                $pdf->setPage($left_column_cur_page);
                //We print dot separator if this bloc is not the first printed on this page
                if (!$is_this_bloc_first_of_current_page_into_left_column) {
                    $pdf->SetLineStyle(array('dash' => '0.5', 'color' => array(0, 0, 0)));
                    $pdf->line($posx, $left_column_cur_Y, $posx + $column_left_w, $left_column_cur_Y);
                    $pdf->SetLineStyle(array('dash' => '0'));
                    $left_column_cur_Y += 1;
                }

                //We print bloc on the left
                $left_column_cur_Y = $this->_question_bloc_area($pdf, $question_bloc, $posx, $left_column_cur_Y, $column_left_w, $outputlangs, true, $listOfAttachedFiles);

                //Is end of this bloc end first of bloc of the page where it has been printed ?
                $is_this_bloc_first_of_current_page_into_left_column = $left_column_cur_page != $pdf->getPage();
                $left_column_cur_page = $pdf->getPage();
            } else {
                $pdf->setPage($right_column_cur_page);
                //We print dot separator if this bloc is not the first printed on this page
                if (!$is_this_bloc_first_of_current_page_into_rigth_column) {
                    $pdf->SetLineStyle(array('dash' => '0.5', 'color' => array(0, 0, 0)));
                    $pdf->line($column_right_posx, $right_column_cur_Y, $column_right_posx + $column_right_w, $right_column_cur_Y);
                    $pdf->SetLineStyle(array('dash' => '0'));
                    $right_column_cur_Y += 1;
                }

                //We print bloc on the right
                $right_column_cur_Y = $this->_question_bloc_area($pdf, $question_bloc, $column_right_posx, $right_column_cur_Y, $column_right_w, $outputlangs, true, $listOfAttachedFiles);
                //Is end of this bloc end first of bloc of the page where it has been printed ?
                $is_this_bloc_first_of_current_page_into_rigth_column = $right_column_cur_page != $pdf->getPage();
                $right_column_cur_page = $pdf->getPage();
            }
        }

        $end_page = max($left_column_cur_page, $right_column_cur_page);
        if ($left_column_cur_page == $right_column_cur_page) {
            $end_y = max($left_column_cur_Y, $right_column_cur_Y);
        } elseif ($end_page == $left_column_cur_page) {
            $end_y = $left_column_cur_Y;
        } else {
            $end_y = $right_column_cur_Y;
        }

        $pdf->SetPage($start_page);
        $pdf->SetY($start_y);

        // Print frame
        call_user_func_array(array($pdf, 'SetDrawColor'), $this->main_color);
        if ($end_page == $start_page) {
            // Draw frame
            $pdf->Rect($posx, $start_y, $w, $end_y - $start_y);
            $pdf->SetDrawColor(0, 0, 0);
        } else {
            $dash_style = array('dash' => '10,10', 'color' => $this->main_color);
            $no_style = array('dash' => 0);
            for ($page = $start_page; $page <= $end_page; ++$page) {
                $pdf->setPage($page);
                $page_height = $pdf->getPageHeight();
                $page_margins = $pdf->getMargins();
                $pdf->SetLineStyle($no_style);

                // First page
                if ($page == $start_page) {
                    $pdf->line($posx, $start_y, $posx, $page_height - $page_margins['bottom']); // Left
                    $pdf->line($posx + $w, $start_y, $posx + $w, $page_height - $page_margins['bottom']); // Right
                    $pdf->SetLineStyle($dash_style);
                    $pdf->line($posx, $page_height - $page_margins['bottom'], $posx + $w, $page_height - $page_margins['bottom']); // Bottom
                } // Last page
                elseif ($page == $end_page) {
                    $pdf->line($posx, $page_margins['top'], $posx, $end_y); // Left
                    $pdf->line($posx + $w, $page_margins['top'], $posx + $w, $end_y); // Right
                    $pdf->line($posx, $end_y, $posx + $w, $end_y); // Bottom
                    $pdf->SetLineStyle($dash_style);
                    $pdf->line($posx, $page_margins['top'], $posx + $w, $page_margins['top']); // Top
                } // Middle page
                else {
                    // Draw frame
                    $pdf->line($posx, $page_margins['top'], $posx, $page_height - $page_margins['bottom']); // Left
                    $pdf->line($posx + $w, $page_margins['top'], $posx + $w, $page_height - $page_margins['bottom']); // Right
                    $pdf->SetLineStyle($dash_style);
                    $pdf->line($posx, $page_margins['top'], $posx + $w, $page_margins['top']); // Top
                    $pdf->line($posx, $page_height - $page_margins['bottom'], $posx + $w, $page_height - $page_margins['bottom']); // Bottom
                }
            }
            $pdf->SetLineStyle($no_style);
        }

        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetPage($end_page);
        $pdf->SetY($end_y);
        return $end_y;
    }

    /**
     *  Show area for the question bloc
     *
     * @param   PDF             $pdf            Object PDF
     * @param   SurveyBlocQuestion  $question_bloc  Object question block
     * @param   int             $posx           Position X of the bloc
     * @param   int             $posy           Position Y of the bloc
     * @param   int             $width          Width of the bloc
     * @param   Translate       $outputlangs    Objet langs
     * @param   int             $addline        1=Add dash line after the bloc
     * @param   array           $listOfAttachedFiles    Informations on attached files on linked fichinter
     *
     * @return  int                             Position pour suite
     */

    function _question_bloc_area(&$pdf, $question_bloc, $posx, $posy, $width, $outputlangs, $addline = 0, $listOfAttachedFiles = array())
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
        $chosenStatus = $question_bloc->getChosenStatus();
        if (!empty($chosenStatus->color)) {
            $circle_style = 'F';
            list($r, $g, $b) = sscanf($chosenStatus->color, "#%02x%02x%02x");
            $circle_fill_color = array($r, $g, $b);
        }

        // Save for the calculation of the position Y origin
        $page_origin = $pdf->getPage();
        $last_page_margins = $pdf->getMargins();
        $posy_origin = $posy;

        // Print label (+ description and status justificatory) of the question bloc
        $question_bloc_sub_label = $question_bloc->desription .
            (!empty($question_bloc->desription) && !empty($question_bloc->justification_text) ? ' - ' : '')
            . dol_htmlentitiesbr($question_bloc->justification_text);
        $question_bloc_title = '<font size="' .
            $default_font_size . '">' . $question_bloc->label
            . (!empty($question_bloc_sub_label) ? '&nbsp;->&nbsp;</font><b><font size="'
                . ($default_font_size - 1) . '">' . $question_bloc_sub_label . '</font></b>' : '</font>');
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
        $question_bloc->fetch_optionals(null, null, true);
        foreach ($question_bloc->extrafields as $key) {
            // Save for the calculation of the position Y origin
            $page_origin = $pdf->getPage();
            $last_page_margins = $pdf->getMargins();
            $posy_origin = $posy;

            // Print label and value of the extrafield
            $question_bloc->fetchExtraFieldsInfo();
            $question_bloc_extrafield = '<font size="' . ($default_font_size - 1) . '">' . $question_bloc::$extrafields_cache->attribute_label[$key] . '&nbsp;:&nbsp;</font><b><font size="' . ($default_font_size - 2) . '">' . $question_bloc::$extrafields_cache->showOutputField($key, $question_bloc->array_options['options_' . $key]) . '</font></b>';
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
        if (!$question_bloc->isBlocDesactivated()) {
            foreach ($question_bloc->questions as $question) {
                $answer = $question->getChosenAnswer();
                if (!$question->mandatory_answer && empty($question->fk_chosen_answer) && empty($question->justification_text)) {
                    continue;
                }
                // Define info for color answer of the question
                $circle_style = '';
                $circle_fill_color = array();
                if (!empty($answer->color)) {
                    $circle_style = 'F';
                    list($r, $g, $b) = sscanf($answer->color, "#%02x%02x%02x");
                    $circle_fill_color = array($r, $g, $b);
                }

                // Save for the calculation of the position Y origin
                $page_origin = $pdf->getPage();
                $last_page_margins = $pdf->getMargins();
                $posy_origin = $posy;

                // Print label (+ answer justificatory) of the question
                $question_label = $question->label . (!empty($question->justification_text) ? '&nbsp;:&nbsp;' : '');
                $question_answer = '<font size="' . ($default_font_size - 1) . '">' . $question_label . '</font><b><font size="' . ($default_font_size - 2) . '">' . dol_htmlentitiesbr($question->justification_text) . '</font></b>';
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
                $question->fetchExtraFieldsInfo();
                $question->fetch_optionals(null, null, true);
                foreach ($question->extrafields as $key) {
                    // Save for the calculation of the position Y origin
                    $page_origin = $pdf->getPage();
                    $last_page_margins = $pdf->getMargins();
                    $posy_origin = $posy;

                    // Print label and value of the extrafield
                    $question_bloc_extrafield = '<font size="' . ($default_font_size - 1) . '">' . $question::$extrafields_cache->attribute_label[$key] . '&nbsp;:&nbsp;</font><b><font size="' . ($default_font_size - 2) . '">' . $question::$extrafields_cache->showOutputField($key, $question->array_options['options_' . $key]) . '</font></b>';
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
        }
        //We print files of this bloc of question if they are some image files
        $listOfFilePathToDisplay = getListOfWantedFilesInformation($question_bloc->attached_files, $listOfAttachedFiles, array('image/jpg', 'image/jpeg', 'image/gif', 'image/png'));
        if (!empty($listOfFilePathToDisplay)) {
            $posy = $this->_display_images($pdf, $listOfFilePathToDisplay, $posx, 30, $width);
        }

        return $posy + 1;
    }

    /**
     *  Load the effective working time into a array
     *
     * @param   Fichinter   $object         Object intervention
     * @param   Translate   $outputlangs    Objet langs
     *
     * @return  array array(array('name'=>Nom de l'utilisateur, 'times'=>array(array('begin'=>date, 'end'=>date, 'duration'=>time))))
     */
    private function _fetch_effective_working_time($object, $outputlangs)
    {
        $result = array();
        $user_cached = array();

        if (is_array($object->lines) && count($object->lines)) {
            foreach ($object->lines as $line) {
                // Get involved user id
                $line->fetch_optionals(null, null, true);
                $user_ids = !empty($line->array_options['options_involved_users']) ? explode(',', $line->array_options['options_involved_users']) : array();
                $user_ids = array_filter($user_ids);


                foreach ($user_ids as $user_id) {
                    $user_id = (int) $user_id;
                    if (!$user_id > 0) {
                        break;
                    }

                    if (!$user_cached[$user_id]) {
                        $user = new User($this->db);
                        $user->fetch($user_id);
                        $user_name = $user->getFullName($outputlangs);
                        if ($user_name) {
                            $user_cached[$user_id] = $user_name;
                        }
                    }

                    if (!$user_cached[$user_id]) {
                        break;
                    }

                    if (!$result[$user_id]) {
                        $result[$user_id] = array();
                    }

                    $result[$user_id]['name'] = $user_cached[$user_id];
                    if (!$result[$user_id]['times']) {
                        $result[$user_id]['times'] = array();
                    }
                    $result[$user_id]['times'][] = array('begin' => $line->datei, 'end' => $line->datei + $line->duration, 'duration' => $line->duration);
                }
            }
            foreach ($result as $k => $v) {
                uasort($result[$k]['times'], 'effective_working_time_cmp');
            }
            return $result;
        }
    }

    /**
     * Function to get total duration of a given working time
     * @param Array $workingTime array(array('name'=>Nom de l'utilisateur, 'times'=>array(array('begin'=>date, 'end'=>date, 'duration'=>time))))
     * @return int $duration - duration in second
     */
    private static function getWorkingTimeTotalDuration($workingTime)
    {
        $result = 0;
        foreach ($workingTime as $userWorkingInformation) {
            foreach ($userWorkingInformation['times'] as $periodInformations) {
                $result += $periodInformations['duration'];
            }
        }
        return $result;
    }

    /**
     *  Show area for the effective working time
     *
     * @param   TCPDF           $pdf            Object PDF
     * @param   Array   $effective_working_time         Object Containing effective working time
     * @param   int         $posy           Position depart
     * @param   Translate   $outputlangs    Objet langs
     *
     * @return  int                         Height of the area
     */
    function _effective_working_time_area(&$pdf, $effective_working_time, $posy, $outputlangs)
    {
        $default_font_size = pdf_getPDFFontSize($outputlangs);

        $tableWidth = ($this->page_largeur - $this->marge_gauche - $this->marge_droite - 4) / 2;
        $column_w_from = 30;
        $column_w_to = 15;
        $column_w_duration = 19;
        $column_w_involved_user = $tableWidth - $column_w_duration - $column_w_to - $column_w_from;

        $pdf->SetXY($this->marge_gauche, $posy);

        //We display headers
        call_user_func_array(array($pdf, 'SetDrawColor'), $this->main_color);
        $pdf->SetTextColor(255, 255, 255);
        $table = new Table($pdf);
        $row = $table->newRow();
        $this->addCellToRow($row, $outputlangs->transnoentities("InterventionSurveyEffectiveWorkingTimeTitle"), 1, 1, 1, $tableWidth, 'C', 'middle', 'bold', $this->main_color, 3, $default_font_size);
        $row = $row->end(); //This close the row
        $table->end(); // this prints the table to the PDF. Don't forget!

        //We display Column Title and content
        $pdf->SetTextColor(0, 0, 0);
        $table = new Table($pdf);

        //Row of column title
        $row = $table->newRow();
        $this->addCellToRow($row, $outputlangs->transnoentities("InterventionSurveyEffectiveWorkingTimeInvolvedUser"), 1, null, null, $column_w_involved_user, 'C', 'middle', 'bold');
        $this->addCellToRow($row, $outputlangs->transnoentities("InterventionSurveyEffectiveWorkingTimeFrom"), 1, null, null, $column_w_from, 'C', 'middle', 'bold');
        $this->addCellToRow($row, $outputlangs->transnoentities("InterventionSurveyEffectiveWorkingTimeTo"), 1, null, null, $column_w_to, 'C', 'middle', 'bold');
        $this->addCellToRow($row, $outputlangs->transnoentities("InterventionSurveyEffectiveWorkingTimeDuration"), 1, null, null, $column_w_duration, 'C', 'middle', 'bold');

        $row = $row->end(); //This close the row

        //Rows of content
        $singleLineMinHeight = 5;
        foreach ($effective_working_time as $user) {
            $row = $row->newRow();
            $nameToDisplay = $user['name'];
            $numberOfPeriodRowForThisUser = count($user['times']);
            $minRowHeight = $numberOfPeriodRowForThisUser * $singleLineMinHeight;
            $this->addCellToRow($row, $nameToDisplay, 1, $numberOfPeriodRowForThisUser, null, null, 'C', 'middle', 'normal', null, $minRowHeight, $default_font_size - 1);
            $lastIndex = end(array_keys($user['times']));
            foreach ($user['times'] as $index => $dateInformation) {
                $beginDate = $dateInformation['begin'];
                $endDate = $dateInformation['end'];
                $duration = $dateInformation['duration'];

                $displayedBeginDate = dol_print_date($beginDate, 'day') . ' ' . dol_print_date($beginDate, 'hour');
                $displayedEndDate = dol_print_date($endDate, 'hour');
                $displayedDuration = $this->_print_duration($duration, false, true, false);
                $this->addCellToRow($row, $displayedBeginDate, 1, null, null, null, 'C', 'middle', 'normal', null, null, $default_font_size - 1);
                $this->addCellToRow($row, $displayedEndDate, 1, null, null, null, 'C', 'middle', 'normal', null, null, $default_font_size - 1);
                $this->addCellToRow($row, $displayedDuration, 1, null, null, null, 'R', 'middle', 'normal', null, null, $default_font_size - 1);
                $row = $row->end();
                if ($index != $lastIndex) {
                    $row = $row->newRow();
                }
            }
            if (count($user['times']) == 0) {
                $row = $row->end();
            }
        }
        //We Add Total Row
        //Fake firt cell
        $row = $row->newRow();
        $this->addCellToRow($row, null, 0, null, 2);
        //Total Label Cell
        $this->addCellToRow($row, $outputlangs->transnoentities("InterventionSurveyEffectiveWorkingTimeDuration"), 1, null, null, null, 'C', 'middle', 'bold', null, 7, $default_font_size);
        $this->addCellToRow($row, $this->_print_duration(self::getWorkingTimeTotalDuration($effective_working_time), false, true, false), 1, null, null, null, 'R', 'middle', 'bold', null, 7, $default_font_size);
        $row = $row->end();
        //We display Row
        $table->end();
        //We display total Rows
        return $pdf->getY();
    }

    /**
     *  Show a description lines content
     *
     * @param   PDF         $pdf            Object PDF
     * @param   Fichinter   $Object        Object intervention
     * @param   int         $posx           Position depart
     * @param   int         $posy           Position depart
     * @param   int         $w              Largeur
     * @param   Translate   $outputlangs    Objet langs
     * @param   int         $heightforfooter    hauteur footer
     * @param   int         $default_font_size  Hauteur de base police
     *
     * @return  int                         Position at the end
     */
    private function displayDescriptionContents(&$pdf, $object, $posx, $posy, $w, $outputlangs, $heightforfooter, $default_font_size)
    {
        $lines = $object->lines;
        $linesToDisplay = array_filter($lines, function ($line) {
            return isThereInterestingTextContent($line->desc);
        });
        $i = 1;
        $curY = $posy;
        foreach ($linesToDisplay as $line) {
            $curY = $this->displayDescriptionContent($pdf, $object, $line, $posx, $curY, $w, $i, $outputlangs, $heightforfooter, $default_font_size);
            $curY += 4;
            $i++;
        }
        return $curY;
    }

    /**
     *  Show a description line content
     *
     * @param   PDF         $pdf            Object PDF
     * @param   Fichinter   $object        Object intervention
     * @param   FichinterDet   $line        Object intervention Line
     * @param   int         $posx           Position depart
     * @param   int         $posy           Position depart
     * @param   int         $w              Largeur
     * @param   int         $lineNumber     Numéro de la lingne
     * @param   Translate   $outputlangs    Objet langs
     * @param   int         $heightforfooter    hauteur footer
     * @param   int         $default_font_size  Hauteur de base police
     *
     * @return  int                         Position at the end
     */
    private function displayDescriptionContent(&$pdf, $object, $line, $posx, $posy, $w, $lineNumber, $outputlangs, $heightforfooter, $default_font_size)
    {
        $textToDisplay = dol_htmlentitiesbr($line->desc);
        $title = $outputlangs->transnoentities('InterventionSurveyLineDescriptionTitle', $lineNumber);

        //Display title
        $endY = $this->printTitleForPdfPart($pdf, $posx, $posy, $w, $title, $default_font_size);

        //Display content
        $startPage = $pdf->GetPage();
        $startY = $endY;
        $pdf->writeHTMLCell($w, null, $posx, $startY, $textToDisplay, 'LR', 1, false, true, 'L', true);
        $endPage = $pdf->getPage();
        $endY = $pdf->GetY();
        //Draw top border
        $dash_style = array('dash' => '10,10', 'color' => $this->main_color);
        $no_style = array('dash' => 0, 'color' => $this->main_color);

        //Draw intermediate border
        for ($page = $startPage; $page <= $endPage; $page++) {
            $pdf->setPage($page);
            $page_height = $pdf->getPageHeight();
            $page_margins = $pdf->getMargins();
            if ($page > $startPage) {
                $pdf->line($posx, $page_margins['top'], $posx + $w, $page_margins['top'], $dash_style);
            }
            if ($page < $endPage) {
                $pdf->line($posx, $page_height - $page_margins['bottom'], $posx + $w, $page_height - $page_margins['bottom'], $dash_style);
            }
        }
        $pdf->setPage($endPage);
        //Draw end border
        $pdf->line($posx, $endY, $posx + $w, $endY, $no_style);
        $pdf->SetDrawColor(0, 0, 0);
        return $endY;
    }

    /**
     * Show Title of a PDF part
     * @param   PDF         $pdf            Object PDF
     * @param   int         $posx           Position
     * @param   int         $posy           Position
     * @param   int         $w              Width
     * @param   text        $textToDisplay  Texte à afficher
     * @param   int         $defaultFontSize    Taille police
     */
    private function printTitleForPdfPart(&$pdf, $posx, $posy, $w, $textToDisplay, $defaultFontSize)
    {
        $startPage = $pdf->getPage();
        $pdf->startTransaction();
        $endY = $this->printTitleForPdfPartWithoutSamePageCheck($pdf, $posx, $posy, $w, $textToDisplay, $defaultFontSize);
        $endPage = $pdf->getPage();
        if ($startPage == $endPage || $endPage - $startPage > 1) {
            $pdf->commitTransaction();
        } else {
            $pdf->rollbackTransaction(true);
            //We add a new page and display again title
            $pdf->AddPage('', '', true);
            $page_margins = $pdf->getMargins();
            $endY = $this->printTitleForPdfPartWithoutSamePageCheck($pdf, $page_margins['left'], $page_margins['top'], $w, $textToDisplay, $defaultFontSize);
        }
        return $endY;
    }
    /**
     * Show Title of a PDF part without checking it is written on the same page
     * @param   PDF         $pdf            Object PDF
     * @param   int         $posx           Position
     * @param   int         $posy           Position
     * @param   int         $w              Width
     * @param   text        $textToDisplay  Texte à afficher
     * @param   int         $defaultFontSize    Taille police
     */
    private function printTitleForPdfPartWithoutSamePageCheck(&$pdf, $posx, $posy, $w, $textToDisplay, $defaultFontSize)
    {
        $pdf->startTransaction();
        // Define colors and font size
        $pdf->SetFont('', 'B', $defaultFontSize);
        call_user_func_array(array($pdf, 'SetFillColor'), $this->main_color);
        call_user_func_array(array($pdf, 'SetDrawColor'), $this->main_color);
        $pdf->SetTextColor(255, 255, 255);

        // Print title of the table
        $pdf->SetXY($posx, $posy);
        $pdf->MultiCell($w, 3, $textToDisplay, 1, 'C', 1);

        $pdf->SetFont('', '', $defaultFontSize);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetTextColor(0, 0, 0);
        return $pdf->getY();
    }

    /**
     *  Show area for the technician and customer to sign
     *
     * @param   PDF         $pdf            Object PDF
     * @param   Fichinter   $object         Object intervention
     * @param   int         $posy           Position depart
     * @param   Translate   $outputlangs    Objet langs
     *
     * @return  int                         Height of the area
     */
    function _signature_area(&$pdf, $object, $posy, $outputlangs)
    {
        $default_font_size = pdf_getPDFFontSize($outputlangs);
        $signature_font_size = $default_font_size - 6;

        $border = 0;
        $w = ($this->page_largeur - $this->marge_gauche - $this->marge_droite - 4) / 2;

        $start_y = $end_y = $posy;
        $signature_left_posx = $this->marge_droite + $w + 4;
        $signature_left_w = ($w - 4) / 2;
        $signature_right_posx = $signature_left_posx + $signature_left_w + 4;
        $signature_right_w = $signature_left_w;

        // Stakeholder signature
        //----------------------------------
        $signature_info = !empty($object->array_options['options_stakeholder_signature']) ? json_decode($object->array_options['options_stakeholder_signature'], true) : array();


        $signature_date_list = array();
        foreach ($signature_info['people'] as $people) {
            if ($people['date']) {
                $signature_date_list[] = $people['date'];
            }
        }
        if (count($signature_date_list) > 0) {
            $signature_date = max($signature_date_list);
        } else {
            $signature_date = "";
        }

        $signature_info_day = dol_print_date($signature_date, 'day', false, $outputlangs);
        $signature_info_day = !empty($signature_info_day) ? $signature_info_day : '...';
        $signature_info_hour = dol_print_date($signature_date, 'hour', false, $outputlangs);
        $signature_info_hour = !empty($signature_info_hour) ? $signature_info_hour : '...';
        $signature_info_people = $signature_info['people'];
        $signature_info_image = $signature_info['value'];

        // Print image
        if (!empty($signature_info_image)) {
            $img_src1 = $this->tempdir . '/stakeholder.png';
            $imageContent = @file_get_contents($signature_info_image);
            @file_put_contents($img_src1, $imageContent);
            $pdf->writeHTMLCell($signature_left_w, 1, $signature_left_posx, $posy, '<img src="' . $img_src1 . '"/>', $border, 1);
            $posy = $pdf->GetY();
        }

        // Print texte
        $signature_title = $outputlangs->transnoentities('InterventionSurveyPdfStakeholderSignature', $signature_info_day, $signature_info_hour);
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
        $signature_info = !empty($object->array_options['options_customer_signature']) ? json_decode($object->array_options['options_customer_signature'], true) : array();

        if (!$signature_info['isCustomerAbsent']) {
            $signature_date_list = array();
            foreach ($signature_info['people'] as $people) {
                if ($people['date']) {
                    $signature_date_list[] = $people['date'];
                }
            }
            if (count($signature_date_list) > 0) {
                $signature_date = max($signature_date_list);
            } else {
                $signature_date = "";
            }

            $signature_info_people = $signature_info['people'];
            $signature_info_image = $signature_info['value'];
        } else {
            $signature_date = $signature_info['date'];
            $signature_info_user = $signature_info['user'];
            $signature_info_localisation = $signature_info['user']['position'];
            $signature_info_latitude = $signature_info_localisation['coords']['latitude'];
            $signature_info_longitude = $signature_info_localisation['coords']['longitude'];
        }

        $signature_info_day = dol_print_date($signature_date, 'day', false, $outputlangs);
        $signature_info_day = !empty($signature_info_day) ? $signature_info_day : '...';
        $signature_info_hour = dol_print_date($signature_date, 'hour', false, $outputlangs);
        $signature_info_hour = !empty($signature_info_hour) ? $signature_info_hour : '...';

        if (!$signature_info['isCustomerAbsent']) {
            // Print image
            if (!empty($signature_info_image)) {
                $img_src2 = $this->tempdir . '/customer.png';
                $imageContent = @file_get_contents($signature_info_image);
                @file_put_contents($img_src2, $imageContent);
                $pdf->writeHTMLCell($signature_right_w, 1, $signature_right_posx, $posy, '<img src="' . $img_src2 . '"/>', $border, 1);
                $posy = $pdf->GetY();
            }

            // Print texte
            $signature_title = $outputlangs->transnoentities('InterventionSurveyPdfCustomerSignature', $signature_info_day, $signature_info_hour);
            $signature_people = array();
            foreach ($signature_info_people as $person) {
                $signature_people[] = $person['name'];
            }
            $signature_text = '<font size="' . $signature_font_size . '">' . $signature_title . '<br />' . implode(', ', $signature_people) . '</font>';
            $pdf->writeHTMLCell($signature_right_w, 1, $signature_right_posx, $posy, trim($signature_text), $border, 1, false, true, 'C', true);
            $end_y = max($end_y, $pdf->GetY());
        } else {
            dol_include_once('interventionsurvey/lib/interventionSurveyReverseGeocoding.php');

            // Print texte
            $signature_title = $outputlangs->transnoentities('InterventionSurveyPdfCustomerSignature', $signature_info_day, $signature_info_hour);
            $signature_user = $signature_info_user['name'];
            $signature_address = interventionSurveyReverseGeocoding($signature_info_latitude, $signature_info_longitude);
            $signature_text = '<br /><font size="10px">' . $outputlangs->transnoentities('InterventionSurveyPdfAbsentCustomerSignature') . '</font><br /><br />';
            $signature_text .= '<font size="' . $signature_font_size . '">' . $signature_title . '<br />';
            $signature_text .= $outputlangs->transnoentities('InterventionSurveyPdfAbsentCustomerSignatureAddressAndUser', $signature_address, $signature_user) . '</font>';
            $pdf->writeHTMLCell($signature_right_w, 1, $signature_right_posx, $posy, trim($signature_text), $border, 1, false, true, 'C', true);
            $end_y = max($end_y, $pdf->GetY());
        }

        // Draw frame
        call_user_func_array(array($pdf, 'SetDrawColor'), $this->main_color);
        $pdf->Rect($signature_left_posx, $start_y, $signature_left_w, $end_y - $start_y);
        $pdf->Rect($signature_right_posx, $start_y, $signature_right_w, $end_y - $start_y);
        $pdf->SetDrawColor(0, 0, 0);

        return $end_y;
    }

    /**
     *
     * Display an array of image without changing their proportion
     * @param   PDF         $pdf            Object PDF
     * @param array $listOfImageInformation     Array of image containing an array of information for each file
     * @param int $posx                         x coordinate of the gallery
     * @param int $max_image_width              Max width of each image
     * @param int $max_gallery_width            Max width of gallery
     *
     */
    function _display_images(&$pdf, &$listOfImageInformation, $posx, $max_image_width, $max_gallery_width)
    {
        if ($max_gallery_width < $max_image_width) {
            $max_image_width = $max_gallery_width;
        }
        $numberOfColumn = intdiv($max_gallery_width, $max_image_width);
        if ($numberOfColumn > 1) {
            $spaceBetweenImage = fmod($max_gallery_width, $max_image_width) / ($numberOfColumn - 1);
        }
        $cur_Y = $pdf->getY() + 1;
        $image_rows = $this->getImageSortedByHeight($pdf, $listOfImageInformation, $cur_Y, $max_image_width);
        $image_rows = $this->getImagesInformationsPerRow($image_rows, $numberOfColumn);
        foreach ($image_rows as $index => $row) {
            $current_page = $pdf->GetPage();
            $cur_Y = $this->_display_images_row($pdf, $row, $posx, $cur_Y, $max_image_width, $spaceBetweenImage);
            if ($current_page == $pdf->GetPage() && $index + 1 != count($image_rows)) {
                $cur_Y += $spaceBetweenImage; //we add some vertical marging for next row if it is not the last row and if last displayed row didn't pushed us to a new page
            }
        }
        return $cur_Y;
    }

    /**
     *
     * Display an array of image without changing their proportion
     * @param   PDF         $pdf            Object PDF
     * @param array $row     Array of each image information to display
     * @param int $posx                         x coordinate of the row
     * @param int $cur_Y                        y coordinate of the row
     * @param int $max_image_width              Max width of each image
     * @param int $horizontalSpace          horizontal space between image
     *
     */
    function _display_images_row(&$pdf, &$row, $posx, $cur_Y, $max_image_width, $horizontalSpace)
    {
        $x_offset = $max_image_width + $horizontalSpace;
        //first we add posx information to each image information
        foreach ($row as $index => &$image) {
            $image['posx'] = $posx + ($index * $x_offset);
        }
        //now we compute start page and position of each picture
        //in order to sort them and start by printing the biggest image (which may create new page)
        $row = $this->getRowSortedToBePrinted($pdf, $row, $cur_Y, $max_image_width, $horizontalSpace);
        $effective_start_page = $this->getMaxStartPage($row);
        $end_Y = 0;
        $max_page = $pdf->getPage();
        foreach ($row as $index => $imageToDisplay) {
            $end_pos_y = $this->_display_image($pdf, $max_image_width, 0, $imageToDisplay["posx"], $cur_Y, $imageToDisplay["fullname"]);
            $current_page = $pdf->getPage();
            if ($current_page > $max_page) {
                $end_Y = $end_pos_y;
                $max_page = $current_page;
                //we are on a new page, we use top_margin as new cur_y to display next image on this row
                $cur_Y = $this->top_margin;
            } elseif ($end_Y < $end_pos_y) {
                $end_Y = $end_pos_y;
            }
            if ($index + 1 < count($row)) {
                $pdf->setPage($effective_start_page); //to display next picture at the right page
            }
        }
        $pdf->setPage($max_page);
        return $end_Y;
    }

    function _display_image(&$pdf, $image_width, $image_heigth, $posx, $pos_y, $imagePath)
    {
        $pdf->writeHTMLCell($image_width, $image_heigth, $posx, $pos_y, '<img src="' . $imagePath . '"/>', 0, 1);
        return $pdf->GetY();
    }

    function getEffectiveInformationsForThisImage(&$pdf, $image_width, $image_heigth, $posx, $pos_y, $imagePath)
    {
        $result = array('startPage' => null, 'imageHeight' => null);
        $start_page = $pdf->getPage();
        $pdf->startTransaction();
        $this->_display_image($pdf, $image_width, $image_heigth, $posx, $pos_y, $imagePath);
        $new_y = $pdf->getY();
        $end_page = $pdf->getPage();
        $pdf->rollbackTransaction(true);
        $isEndPageSameThanStartPage = $start_page == $end_page;
        $result['startPage'] = $isEndPageSameThanStartPage ? $start_page : $start_page + 1;
        $result['imageHeight'] = $isEndPageSameThanStartPage ? $new_y - $pos_y : $new_y - $this->top_margin;
        return $result;
    }

    function addEffectiveInformationToImage(&$pdf, &$row, $cur_Y, $max_image_width)
    {
        foreach ($row as &$image) {
            $informationOfThisImage = $this->getEffectiveInformationsForThisImage($pdf, $max_image_width, 0, $image['posx'], $cur_Y, $image['fullname']);
            $image["startPage"] = $informationOfThisImage["startPage"];
            $image["imageHeight"] = $informationOfThisImage["imageHeight"];
        }
        return $row;
    }

    function getRowSortedToBePrinted(&$pdf, &$row, $cur_Y, $max_image_width)
    {
        $row = $this->addEffectiveInformationToImage($pdf, $row, $cur_Y, $max_image_width);
        usort($row, "sortImageByPage");
        return $row;
    }

    function getImageSortedByHeight(&$pdf, &$arrayOfImage, $cur_Y, $max_image_width)
    {
        $arrayOfImage = $this->addEffectiveInformationToImage($pdf, $arrayOfImage, $cur_Y, $max_image_width);
        usort($arrayOfImage, "sortImageByHeight");
        return $arrayOfImage;
    }

    function getMaxStartPage(&$row)
    {
        $max_page = 0;
        foreach ($row as &$image) {
            if ($image["startPage"] > $max_page) {
                $max_page = $image["startPage"];
            }
        }
        return $max_page;
    }

    /**
     * Add A footer to current pdf page
     * @param    PDF $pdf PDF
     * @param    Object $object Object to show
     * @param    Translate $outputlangs Object lang for output
     * @param    int $heightforfooter Needed reserved height for footer
     * @return    void
     */
    private function printFooterOnCurrentPage(&$pdf, $object, $outputlangs, $heightforfooter)
    {
        $pdf->setPageOrientation('', 1, 0);    // The only function to edit the bottom margin of current page to set it.
        $this->_pagefoot($pdf, $object, $outputlangs);
        $pdf->setPageOrientation('', 1, $heightforfooter);    // The only function to edit the bottom margin of current page to set it.
    }

    /**
     * Function to know, according to current pdf page and given posy, height and number of page needed to display Wording Time Area
     * @param   PDF         $pdf            Object PDF
     * @param   Fichinter   $object         Object intervention
     * @param   int         $posy           Position depart
     * @param   Translate   $outputlangs    Objet langs
     * @return array return an array as result - array("numberOfPageCreated"=>0,"finalPosition"=>0)
     */
    private function getHeightForWorkingTimeArea(&$pdf, $effective_working_time, $posy, $outputlangs)
    {
        $pdf->startTransaction();
        $current_page = $pdf->getPage();
        $YForEffectiveWorkingTimeAreaOnLastPage = $this->_effective_working_time_area($pdf, $effective_working_time, $posy, $outputlangs);
        $finalPage = $pdf->getPage();
        $page_height = $pdf->getPageHeight();
        $page_margins = $pdf->getMargins();
        $averageOffsetIfDisplayedOnSeveralPage = $current_page == $finalPage ? 0 : 2;
        $spaceBetweenEndOfPageAndEndOfTab = $page_height - $page_margins['bottom'] - $YForEffectiveWorkingTimeAreaOnLastPage - $averageOffsetIfDisplayedOnSeveralPage;
        $pdf->rollbackTransaction(true);
        $computedHeightOnLastPage = $current_page == $finalPage ? $YForEffectiveWorkingTimeAreaOnLastPage - $posy : $YForEffectiveWorkingTimeAreaOnLastPage - $this->top_margin;
        return array('numberOfPageCreated' => $finalPage - $current_page, 'heightOnLastPage' => $computedHeightOnLastPage, 'spaceToFooterOnLastPage' => $spaceBetweenEndOfPageAndEndOfTab);
    }

    /**
     * Function to know, according to current pdf page and given posy, height and number of page needed to display Signature Area
     * @param   PDF         $pdf            Object PDF
     * @param   Fichinter   $object         Object intervention
     * @param   int         $posy           Position depart
     * @param   Translate   $outputlangs    Objet langs
     * @return array return an array as result - array("numberOfPageCreated"=>0,"finalPosition"=>0)
     */
    private function getHeightForSignatureArea(&$pdf, $object, $posy, $outputlangs)
    {
        $pdf->startTransaction();
        $current_page = $pdf->getPage();
        $YForSignatoryAreaOnLastPage = $this->_signature_area($pdf, $object, $posy, $outputlangs);
        $finalPage = $pdf->getPage();
        $page_height = $pdf->getPageHeight();
        $page_margins = $pdf->getMargins();
        $spaceBetweenEndOfPageAndEndOfTab = $page_height - $page_margins['bottom'] - $YForSignatoryAreaOnLastPage;
        $pdf->rollbackTransaction(true);
        $computedHeightOnLastPage = $current_page == $finalPage ? $YForSignatoryAreaOnLastPage - $posy : $YForSignatoryAreaOnLastPage - $this->top_margin;
        return array('numberOfPageCreated' => $finalPage - $current_page, 'heightOnLastPage' => $computedHeightOnLastPage, 'spaceToFooterOnLastPage' => $spaceBetweenEndOfPageAndEndOfTab);
    }

    /**
     * Function to know height of page head to be displayed
     * @param   PDF         $pdf            Object PDF
     * @param   Fichinter   $object         Object intervention
     * @param   int         $showAdress     Display address ?
     * @param   Translate   $outputlangs    Objet langs
     * @return array return an array as result - array("numberOfPageCreated"=>0,"finalPosition"=>0)
     */
    private function getHeightForPageHead(&$pdf, $object, $displayAddress, $outputlangs)
    {
        $pdf->startTransaction();
        $current_page = $pdf->getPage();
        $YForPageHeadOnLastPage = $this->_pagehead($pdf, $object, $displayAddress, $outputlangs);
        $finalPage = $pdf->getPage();
        $pdf->rollbackTransaction(true);
        $computedHeightOnLastPage = $current_page == $finalPage ? $YForPageHeadOnLastPage : $YForPageHeadOnLastPage - $this->top_margin;
        return array('numberOfPageCreated' => $finalPage - $current_page, 'heightOnLastPage' => $computedHeightOnLastPage);
    }


    /**
     * Get a list of image information per row
     * @param array $listOfImageInformation     Array of image containing an array of information for each file
     * @param int numberOfImagePerRow
     * @return array
     */

    function getImagesInformationsPerRow(&$listOfImageInformation, $numberOfImagePerRow)
    {
        $result = array();
        $current_row = 0;
        foreach ($listOfImageInformation as &$image) {
            if (empty($result[$current_row])) {
                $result[$current_row] = array();
            }
            $result[$current_row][] = $image;
            if (count($result[$current_row]) == $numberOfImagePerRow) {
                $current_row += 1;
            }
        }
        return $result;
    }
    /**
     * @param Row $row row object
     * @param string $text Texte à afficher
     * @param string $border border param as used by TCPDF:Multicell (0,1 OR  L B T R in any order)
     * @param int $rowspan number of row used by this cell, like in HTML
     * @param int $colspan number of col used by this cell, like in HTML
     * @param int $width Width of this cell as used by TCPDF
     * @param string $aligment - horizontal aligment of the text - possible values : L (left), C (center), R (right), J (Justify)
     * @param string $verticalAlignment - vertical aligment of the text - possible values : top, bottom, middle
     * @param string $fontWeight - normal or bold
     * @param string|Array $backgroundColord - hexadecimal RGB color code or decimal RGB color array
     * @param string $minHeight css cell min height
     * @param string $fontSize - size to use for font
     * @return Row
     */
    private function addCellToRow(&$row, $text = "", $border = 0, $rowspan = null, $colspan = null, $width = null, $alignment = 'L', $verticalAlign = 'top', $fontWeight = 'normal', $backgroundColor = null, $minHeight = null, $fontSize = null)
    {
        $temp = $row->newCell();
        $temp->setText($text);
        if (!empty($border)) {
            $temp->setBorder($border);
        }
        if (!empty($rowspan)) {
            $temp->setRowspan($rowspan);
        }
        if (!empty($colspan)) {
            $temp->setColspan($colspan);
        }
        if (!empty($width)) {
            $temp->setWidth($width);
        }
        if (!empty($alignment)) {
            $temp->setAlign($alignment);
        }
        if (!empty($verticalAlign)) {
            $temp->setVerticalAlign($verticalAlign);
        }
        if (!empty($fontWeight)) {
            $temp->setFontWeight($fontWeight);
        }
        if (!empty($backgroundColor)) {
            $temp->setBackgroundColor($backgroundColor);
        }
        if (!empty($minHeight)) {
            $temp->setMinHeight($minHeight);
        }
        if (isset($fontSize)) {
            $temp->setFontSize($fontSize);
        }
        $row = $temp->end();
        return $row;
    }

    /**
     * Return the duration information array('days', 'hours', 'minutes', 'seconds')
     *
     * @param   int     $timestamp      Duration in second
     * @param   bool        $day            Get days
     * @param   bool     $hour_minute    Get hours / minutes
     * @param   bool     $second         Get seconds
     *
     * @return  array                  array informations
     */
    function _get_duration($timestamp, $day = true, $hour_minute = true, $second = false)
    {
        $days = $hours = $minutes = $seconds = 0;

        if (!empty($timestamp)) {
            if ($day > 0) {
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
     * @param   int     $timestamp      Duration in second
     * @param   bool        $day            Show days
     * @param   bool     $hour_minute    Show hours / minutes
     * @param   bool     $second         Show seconds
     *
     * @return  string                  Formated duration
     */
    function _print_duration($timestamp, $day = true, $hour_minute = true, $second = false)
    {
        $duration_infos = $this->_get_duration($timestamp, $day, $hour_minute, $second);

        $isDurationNull = $duration_infos['days'] == 0 && $duration_infos['hours'] == 0 && $duration_infos['minutes'] == 0 && $duration_infos['seconds'] == 0;
        $displayedUnit = array(
            'second' => $second,
            'hour_minute' => $hour_minute,
            'day' => $day,
        );
        $smallestUsedUnit = array_search(true, $displayedUnit);
        $text = '';
        if ($duration_infos['days'] > 0 || ($isDurationNull && $smallestUsedUnit == 'day')) {
            $text .= $duration_infos['days'] . 'j';
        }
        if ($duration_infos['hours'] > 0) {
            $text .= $duration_infos['hours'] . 'h';
        }
        if ($duration_infos['minutes'] > 0 || ($isDurationNull && $smallestUsedUnit == 'hour_minute')) {
            $text .= $duration_infos['minutes'] . 'm';
        }
        if ($duration_infos['seconds'] > 0 || ($isDurationNull && $smallestUsedUnit == 'second')) {
            $text .= $duration_infos['seconds'] . 's';
        }
        return trim($text);
    }

    public static function getStartPageAndYPositionInOrderToItemEndsOnFooterOfAPage($pdf, $curPage, $curYForItemEstimation, $spaceToFooter, $numberOfPage, $curY)
    {

        $page_height = $pdf->getPageHeight();
        $page_margins = $pdf->getMargins();
        $startPage = $curPage;

        $useFullAreaStartY = $page_margins['top'];
        $useFulAreaEndY = $page_height - $page_margins['bottom'];
        $computedY = $spaceToFooter + $curYForItemEstimation - 1;


        if ($computedY >= $useFulAreaEndY) {
            $offsetManagedOnStartPage = $useFulAreaEndY - $computedY;
            $remainingOffset = $computedY - $offsetManagedOnStartPage;
            $computedY = $useFullAreaStartY + $remainingOffset;
            $curPage += 1;
        }

        if ($curPage == $startPage && $curY + 10 > $computedY) {
            $curPage += 1;
        }

        return array('startPage' => $curPage, 'Y' => $computedY, 'lastPage' => $numberOfPage + $curPage);
    }
}
