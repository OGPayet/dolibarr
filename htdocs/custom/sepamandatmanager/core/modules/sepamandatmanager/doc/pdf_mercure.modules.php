<?php
/* Copyright (C) 2004-2014  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2008       Raphael Bertrand        <raphael.bertrand@resultic.fr>
 * Copyright (C) 2010-2014  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2012       Christophe Battarel     <christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cédric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014  Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2015       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2017       Ferran Marcet           <fmarcet@2byte.es>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2020-2021 Alexis LAURIER <contact@alexislaurier.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 *  \file       core/modules/sepamandatmanager/doc/pdf_standard.modules.php
 *  \ingroup    sepamandatmanager
 *  \brief      File of class to generate document from standard template
 */

dol_include_once('/sepamandatmanager/core/modules/sepamandatmanager/modules_sepamandat.php');
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';


/**
 *  Class to manage PDF template standard_sepamandat
 */
class pdf_mercure extends ModelePDFSepamandat
{
    var $emetteur;  // Objet societe qui emet
    var $version = 'dolibarr';

    /**
     *  Constructor
     *
     *  @param      DoliDB      $db      Database handler
     */
    function __construct($db)
    {
        global $conf, $langs, $mysoc;

        $langs->load("main");
        $langs->load("bank");
        $langs->load("withdrawals");
        $langs->load("companies");

        $this->db = $db;
        $this->name = "mercure";
        $this->description = $langs->transnoentitiesnoconv("DocumentModelSepaMandateMercure");

        // Dimension page pour format A4
        $this->type = 'pdf';
        $formatarray = pdf_getFormat();
        $this->page_largeur = $formatarray['width'];
        $this->page_hauteur = $formatarray['height'];
        $this->format = array($this->page_largeur, $this->page_hauteur);
        $this->marge_gauche = isset($conf->global->MAIN_PDF_MARGIN_LEFT) ? $conf->global->MAIN_PDF_MARGIN_LEFT : 10;
        $this->marge_droite = isset($conf->global->MAIN_PDF_MARGIN_RIGHT) ? $conf->global->MAIN_PDF_MARGIN_RIGHT : 10;
        $this->marge_haute = isset($conf->global->MAIN_PDF_MARGIN_TOP) ? $conf->global->MAIN_PDF_MARGIN_TOP : 10;
        $this->marge_basse = isset($conf->global->MAIN_PDF_MARGIN_BOTTOM) ? $conf->global->MAIN_PDF_MARGIN_BOTTOM : 10;

        $this->option_logo = 1;                    // Affiche logo FAC_PDF_LOGO
        $this->option_tva = 1;                     // Gere option tva FACTURE_TVAOPTION
        $this->option_codeproduitservice = 1;      // Affiche code produit-service
        $this->option_multilang = 1;
        // Recupere emmetteur
        $this->emetteur = $mysoc;
        if (!$this->emetteur->country_code) {
            $this->emetteur->country_code = substr($langs->defaultlang, -2);    // By default if not defined
        }

        // Defini position des colonnes
        $this->posxref = $this->marge_gauche + 1;
        $this->posxlabel = $this->marge_gauche + 25;
        $this->posxworkload = $this->marge_gauche + 100;
        $this->posxprogress = $this->marge_gauche + 130;
        $this->posxdatestart = $this->marge_gauche + 150;
        $this->posxdateend = $this->marge_gauche + 170;
    }


    /**
     *  Fonction generant le projet sur le disque
     *
     *  @param      SepaMandat      $object             Object project a generer
     *  @param      Translate   $outputlangs        Lang output object
     *  @param      string      $srctemplatepath    Full path of source filename for generator using a template file
     *  @param      int         $hidedetails        Do not show line details (not used for this template)
     *  @param      int         $hidedesc           Do not show desc (not used for this template)
     *  @param      int         $hideref            Do not show ref (not used for this template)
     *  @param      null|array  $moreparams         More parameters
     *  @return     int                             1 if OK, <=0 if KO
     */
    function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = null)
    {
        global $conf, $hookmanager, $langs, $user, $mysoc;

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
        $outputlangs->load("projects");
        $outputlangs->load("withdrawals");
		$outputlangs->load("bills");
		$outputlangs->load("sepamandatmanager@sepamandatmanager");

        $directoryPath = !empty($moreparams['force_dir_output']) ? $moreparams['force_dir_output'] : $object->getAbsolutePath();
        $fileName = $object->specimen ? 'SPECIMEN.pdf' : $object->ref . '.pdf';
        $file = $directoryPath . '/' . $fileName;
        if (!empty($directoryPath)) {
            if (!file_exists($directoryPath)) {
                if (dol_mkdir($directoryPath) < 0) {
                    $this->error = $langs->transnoentities("ErrorCanNotCreateDir", $directoryPath);
                    return 0;
                }
            }

            if (file_exists($directoryPath)) {
                // Add pdfgeneration hook
                if (!is_object($hookmanager)) {
                    include_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
                    $hookmanager = new HookManager($this->db);
                }
                $hookmanager->initHooks(array('pdfgeneration'));
                $parameters = array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
                global $action;
                $reshook = $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks

                $pdf = pdf_getInstance($this->format);
                $default_font_size = pdf_getPDFFontSize($outputlangs);  // Must be after pdf_getInstance
                $heightforinfotot = 50; // Height reserved to output the info and total part
                $heightforfreetext = (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT) ? $conf->global->MAIN_PDF_FREETEXT_HEIGHT : 5);    // Height reserved to output the free text on last page
                $heightforfooter = $this->marge_basse + 8;  // Height reserved to output the footer (value include bottom margin)
                $pdf->SetAutoPageBreak(1, 0);

                if (class_exists('TCPDF')) {
                    $pdf->setPrintHeader(false);
                    $pdf->setPrintFooter(false);
                }
                $pdf->SetFont(pdf_getPDFFont($outputlangs));

                $pdf->Open();
                $pagenb = 0;
                $pdf->SetDrawColor(128, 128, 128);

                $pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
                $pdf->SetSubject($outputlangs->transnoentities("SepaMandate"));
                $pdf->SetCreator("Dolibarr " . DOL_VERSION);
                $pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
                $pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref) . " " . $outputlangs->transnoentities("SepaMandate"));
                if (!empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) {
                    $pdf->SetCompression(false);
                }

                $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right

                // New page
                $pdf->AddPage();
                $pagenb++;
                $this->_pagehead($pdf, $object, 1, $outputlangs);
                $pdf->SetFont('', '', $default_font_size - 1);
                $pdf->MultiCell(0, 3, '');      // Set interline to 3
                $pdf->SetTextColor(0, 0, 0);

                $tab_top = 50;
                $tab_height = 200;
                $tab_top_newpage = 40;
                $tab_height_newpage = 210;

                // Affiche notes
                if (!empty($object->note_public)) {
                    $pdf->SetFont('', '', $default_font_size - 1);
                    $pdf->writeHTMLCell(190, 3, $this->posxref - 1, $tab_top - 2, dol_htmlentitiesbr($object->note_public), 0, 1);
                    $nexY = $pdf->GetY();
                    $height_note = $nexY - ($tab_top - 2);

                    // Rect prend une longueur en 3eme param
                    $pdf->SetDrawColor(192, 192, 192);
                    $pdf->Rect($this->marge_gauche, $tab_top - 3, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $height_note + 1);

                    $tab_height = $tab_height - $height_note;
                    $tab_top = $nexY + 6;
                } else {
                    $height_note = 0;
                }

                $iniY = $tab_top + 7;
                $curY = $tab_top + 7;
                $nexY = $tab_top + 7;

                $posY = $curY;

                $pdf->SetFont('', '', $default_font_size);

                $pdf->line($this->marge_gauche, $posY, $this->page_largeur - $this->marge_droite, $posY);
                $posY += 2;

                $pdf->SetXY($this->marge_gauche, $posY);
                $pdf->MultiCell($this->page_largeur - $this->marge_gauche - $this->marge_droite, 3, $outputlangs->transnoentitiesnoconv("RUMLong") . ' (' . $outputlangs->transnoentitiesnoconv("RUM") . ')' . ' : ' . $object->rum, 0, 'L');

                $posY = $pdf->GetY();
                $posY += 2;
                $pdf->SetXY($this->marge_gauche, $posY);
                $ics = $object->ics;
                $pdf->MultiCell($this->page_largeur - $this->marge_gauche - $this->marge_droite, 3, $outputlangs->transnoentitiesnoconv("SepaMandateIdentityCreditorName") . ' (' . $outputlangs->transnoentitiesnoconv("ICS") . ')' . ' : ' . $ics, 0, 'L');

                $posY = $pdf->GetY();
                $posY += 1;
                $pdf->SetXY($this->marge_gauche, $posY);
                $pdf->MultiCell($this->page_largeur - $this->marge_gauche - $this->marge_droite, 3, $outputlangs->transnoentitiesnoconv("SepaMandateCreditorName") . ' : ' . $mysoc->name, 0, 'L');

                $posY = $pdf->GetY();
                $posY += 1;
                $pdf->SetXY($this->marge_gauche, $posY);
                $pdf->MultiCell($this->page_largeur - $this->marge_gauche - $this->marge_droite, 3, $outputlangs->transnoentitiesnoconv("Address") . ' : ', 0, 'L');
                $pdf->MultiCell($this->page_largeur - $this->marge_gauche - $this->marge_droite, 3, $mysoc->getFullAddress(1), 0, 'L');

                $posY = $pdf->GetY();
                $posY += 3;

                $pdf->line($this->marge_gauche, $posY, $this->page_largeur - $this->marge_droite, $posY);

                $pdf->SetFont('', '', $default_font_size - 1);

                $posY += 8;
                $pdf->SetXY($this->marge_gauche, $posY);
                $pdf->MultiCell($this->page_largeur - $this->marge_gauche - $this->marge_droite, 8, $outputlangs->transnoentitiesnoconv("SEPAMandateLegalText", $mysoc->name, $mysoc->name), 0, 'J');

                // Your data form
                $posY = $pdf->GetY();
                $posY += 8;
                $pdf->line($this->marge_gauche, $posY, $this->page_largeur - $this->marge_droite, $posY);
                $posY += 2;

                $pdf->SetFont('', '', $default_font_size + 2);
                $posY += 3;
                $pdf->SetXY($this->marge_gauche, $posY);
                $pdf->MultiCell($this->page_largeur - $this->marge_gauche - $this->marge_droite, 3, $outputlangs->transnoentitiesnoconv("SEPADebitorSection"), 0, 'L');
                $pdf->SetFont('', '', $default_font_size);

                $pdf->SetXY($this->marge_gauche, $posY);
                $pdf->MultiCell($this->page_largeur - $this->marge_gauche - $this->marge_droite, 3, $outputlangs->transnoentitiesnoconv("SEPAMandateFillForm"), 0, 'C');

                $thirdparty = $object->thirdparty;
                if (!$thirdparty) {
                    $thirdparty = new Societe($this->db);
                    if ($object->fk_soc) {
                        $thirdparty->fetch($object->fk_soc);
                    }
                }

                $sepaname = $thirdparty->name . ($object->account_owner ? ' (' . $object->account_owner . ')' : '');
                if (empty($sepaname)) {
                    $sepaname = '______________________________________________';
                }

                $posY = $pdf->GetY();
                $posY += 3;
                $pdf->SetXY($this->marge_gauche, $posY);
                $pdf->MultiCell($this->page_largeur - $this->marge_gauche - $this->marge_droite, 3, $outputlangs->transnoentitiesnoconv("SEPAFormYourName") . ' * : ', 0, 'L');
                $pdf->SetXY(80, $posY);
                $pdf->MultiCell($this->page_largeur - $this->marge_gauche - $this->marge_droite, 3, $sepaname, 0, 'L');

                $address = $thirdparty->getFullAddress(1);
                if (empty($address)) {
                    $address = '______________________________________________';
                }

                $posY = $pdf->GetY();
                $posY += 1;
                $pdf->SetXY($this->marge_gauche, $posY);
                $pdf->MultiCell($this->page_largeur - $this->marge_gauche - $this->marge_droite, 3, $outputlangs->transnoentitiesnoconv("Address") . ' : ', 0, 'L');
                $pdf->SetXY(80, $posY);
                $pdf->MultiCell($this->page_largeur - $this->marge_gauche - $this->marge_droite, 3, $address, 0, 'L');
                if (preg_match('/_____/', $address)) {
                    $posY += 6;
                    $pdf->SetXY(80, $posY);
                    $pdf->MultiCell($this->page_largeur - $this->marge_gauche - $this->marge_droite, 3, $address, 0, 'L');
                }

                $iban = '__________________________________________________';
                if (!empty($object->iban)) {
                    $iban = $object->iban;
                }
                $posY = $pdf->GetY();
                $posY += 1;
                $pdf->SetXY($this->marge_gauche, $posY);
                $pdf->MultiCell($this->page_largeur - $this->marge_gauche - $this->marge_droite, 3, $outputlangs->transnoentitiesnoconv("SEPAFormYourBAN") . ' * : ', 0, 'L');
                $pdf->SetXY(80, $posY);
                $pdf->MultiCell($this->page_largeur - $this->marge_gauche - $this->marge_droite, 3, $iban, 0, 'L');

                $bic = '__________________________________________________';
                if (!empty($object->bic)) {
                    $bic = $object->bic;
                }
                $posY = $pdf->GetY();
                $posY += 1;
                $pdf->SetXY($this->marge_gauche, $posY);
                $pdf->MultiCell($this->page_largeur - $this->marge_gauche - $this->marge_droite, 3, $outputlangs->transnoentitiesnoconv("SEPAFormYourBIC") . ' * : ', 0, 'L');
                $pdf->SetXY(80, $posY);
                $pdf->MultiCell($this->page_largeur - $this->marge_gauche - $this->marge_droite, 3, $bic, 0, 'L');


                $posY = $pdf->GetY();
                $posY += 1;
                $pdf->SetXY($this->marge_gauche, $posY);
                $txt = $outputlangs->transnoentitiesnoconv("SEPAFrstOrRecur") . ' * : ';
                $pdf->MultiCell($this->page_largeur - $this->marge_gauche - $this->marge_droite, 3, $txt, 0, 'L');
                $pdf->Rect(80, $posY, 5, 5);
                $pdf->SetXY(80, $posY);
                if ($object->type == $object::TYPE_RECURRENT) {
                    $pdf->MultiCell(5, 3, 'X', 0, 'L');
                }
                $pdf->SetXY(86, $posY);
                $txt = $langs->transnoentitiesnoconv("SEPAMandateModeRECUR");
                $pdf->MultiCell($this->page_largeur - $this->marge_gauche - $this->marge_droite, 3, $txt, 0, 'L');
                $posY += 6;
                $pdf->Rect(80, $posY, 5, 5);
                $pdf->SetXY(80, $posY);
                if ($object->type == $object::TYPE_PUNCTUAL) {
                    $pdf->MultiCell(5, 3, 'X', 0, 'L');
                }
                $pdf->SetXY(86, $posY);
                $txt = $langs->transnoentitiesnoconv("SEPAMandateModeFRST");
                $pdf->MultiCell($this->page_largeur - $this->marge_gauche - $this->marge_droite, 3, $txt, 0, 'L');
                if (empty($object->type)) {
                    $posY += 6;
                    $pdf->SetXY(80, $posY);
                    $txt = '(' . $langs->transnoentitiesnoconv("PleaseCheckOne") . ')';
                    $pdf->MultiCell($this->page_largeur - $this->marge_gauche - $this->marge_droite, 3, $txt, 0, 'L');
                }

                $posY = $pdf->GetY();
                $posY += 3;
                $pdf->line($this->marge_gauche, $posY, $this->page_largeur - $this->marge_droite, $posY);
                $posY += 3;

                // Show square
                if ($pagenb == 1) {
                    $this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 0, 0);
                    $bottomlasttab = $this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;
                } else {
                    $this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 1, 0);
                    $bottomlasttab = $this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;
                }

                // Affiche zone infos
                $posy = $this->_tableau_info($pdf, $object, $bottomlasttab, $outputlangs);

                /*
                 * Pied de page
                 */
                $this->_pagefoot($pdf, $object, $outputlangs);
                if (method_exists($pdf, 'AliasNbPages')) {
                    $pdf->AliasNbPages();
                }

                $pdf->Close();

                $pdf->Output($file, 'F');

                // Add pdfgeneration hook
                if (!is_object($hookmanager)) {
                    include_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
                    $hookmanager = new HookManager($this->db);
                }
                $hookmanager->initHooks(array('pdfgeneration'));
                $parameters = array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
                global $action;
                $reshook = $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks

                if (!empty($conf->global->MAIN_UMASK)) {
                    @chmod($file, octdec($conf->global->MAIN_UMASK));
                }

                return 1;   // Pas d'erreur
            } else {
                $this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
                return 0;
            }
        }

        $this->error = $langs->transnoentities("ErrorConstantNotDefined", "LIVRAISON_OUTPUTDIR");
        return 0;
    }


    /**
     *   Show table for lines
     *
     *   @param     PDF         $pdf            Object PDF
     *   @param     string      $tab_top        Top position of table
     *   @param     string      $tab_height     Height of table (rectangle)
     *   @param     int         $nexY           Y
     *   @param     Translate   $outputlangs    Langs object
     *   @param     int         $hidetop        Hide top bar of array
     *   @param     int         $hidebottom     Hide bottom bar of array
     *   @return    void
     */
    function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop = 0, $hidebottom = 0)
    {
        global $conf, $mysoc;

        $default_font_size = pdf_getPDFFontSize($outputlangs);
    }


    /**
     *   Show miscellaneous information (payment mode, payment term, ...)
     *
     *   @param     PDF         $pdf            Object PDF
     *   @param     Object      $object         Object to show
     *   @param     int         $posy           Y
     *   @param     Translate   $outputlangs    Langs object
     *   @return    void
     */
    function _tableau_info(&$pdf, $object, $posy, $outputlangs)
    {
        global $conf, $mysoc;

        $default_font_size = pdf_getPDFFontSize($outputlangs);

        $diffsizetitle = (empty($conf->global->PDF_DIFFSIZE_TITLE) ? 1 : $conf->global->PDF_DIFFSIZE_TITLE);

        $posy += $this->_signature_area($pdf, $object, $posy, $outputlangs);

        return $posy;
    }



    /**
     *  Show area for the customer to sign
     *
     *  @param  PDF         $pdf            Object PDF
     *  @param  Facture     $object         Object invoice
     *  @param  int         $posy           Position depart
     *  @param  Translate   $outputlangs    Objet langs
     *  @return int                         Position pour suite
     */
    function _signature_area(&$pdf, $object, $posy, $outputlangs)
    {
        global $langs;
        $default_font_size = pdf_getPDFFontSize($outputlangs);
        $tab_top = $posy + 4;
        $tab_hl = 4;

        $posx = 120;
        $pdf->SetXY($posx, $tab_top - 10);

        $pdf->SetFont('', '', $default_font_size - 2);
        $textDate = $outputlangs->transnoentitiesnoconv("Date") . ' : ';
        if (!empty($object->date_rum)) {
            $textDate .= $object->showOutputField($object->fields['date_rum'], 'date_rum', $object->date_rum, '', '', '', 0);
        } else {
            $textDate .= '__________________';
        }
        $thirdparty = $object->thirdparty;
        if (!$thirdparty) {
            $thirdparty = new Societe($this->db);
            if ($object->fk_soc) {
                $thirdparty->fetch($object->fk_soc);
            }
		}
		$textPlace = $langs->transnoentities("SepaMandateAt") . ' ';
		if (!empty($thirdparty->town)) {
            $textPlace .= $thirdparty->town;
        } else {
            $textPlace .= '__________________';
		}

        $pdf->MultiCell(100, 3, $textDate . ' ' . $textPlace, 0, 'L', 0);

        $largcol = ($this->page_largeur - $this->marge_droite - $posx);
        $useborder = 0;
        $index = 0;
        // Total HT
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetXY($posx, $tab_top + 0);
        $pdf->MultiCell($largcol, $tab_hl, $outputlangs->transnoentitiesnoconv("Signature"), 0, 'L', 1);

        $pdf->SetXY($posx, $tab_top + $tab_hl);
        $pdf->MultiCell($largcol, $tab_hl * 5, '', 1, 'R');

        return ($tab_hl * 7);
    }


    /**
     *  Show top header of page.
     *
     *  @param  PDF         $pdf            Object PDF
     *  @param  Project     $object         Object to show
     *  @param  int         $showaddress    0=no, 1=yes
     *  @param  Translate   $outputlangs    Object lang for output
     *  @return void
     */
    function _pagehead(&$pdf, $object, $showaddress, $outputlangs)
    {
        global $langs, $conf, $mysoc;

        $default_font_size = pdf_getPDFFontSize($outputlangs);

        pdf_pagehead($pdf, $outputlangs, $this->page_hauteur);

        $pdf->SetTextColor(0, 0, 60);
        $pdf->SetFont('', 'B', $default_font_size + 3);

        $posx = $this->page_largeur - $this->marge_droite - 100;
        $posy = $this->marge_haute;

        $pdf->SetXY($this->marge_gauche, $posy);

        // Logo
        $logo = $conf->mycompany->dir_output . '/logos/' . $mysoc->logo;
        if ($mysoc->logo) {
            if (is_readable($logo)) {
                $height = pdf_getHeightForLogo($logo);
                $pdf->Image($logo, $this->marge_gauche, $posy, 0, $height); // width=0 (auto)
            } else {
                $pdf->SetTextColor(200, 0, 0);
                $pdf->SetFont('', 'B', $default_font_size - 2);
                $pdf->MultiCell(100, 3, $langs->transnoentities("ErrorLogoFileNotFound", $logo), 0, 'L');
                $pdf->MultiCell(100, 3, $langs->transnoentities("ErrorGoToModuleSetup"), 0, 'L');
            }
        } else {
            $pdf->MultiCell(100, 4, $outputlangs->transnoentities($this->emetteur->name), 0, 'L');
        }

        $pdf->SetFont('', 'B', $default_font_size + 3);
        $pdf->SetXY($posx, $posy);
        $pdf->SetTextColor(0, 0, 60);
        $pdf->MultiCell(100, 4, $outputlangs->transnoentities("SepaMandatePdfTitle"), '', 'R');
        $pdf->SetFont('', '', $default_font_size + 2);

        // $posy += 6;
        // $pdf->SetXY($posx, $posy);
        // $pdf->SetTextColor(0, 0, 60);
        // $pdf->MultiCell(100, 4, $outputlangs->transnoentities("Date") . " : " . $object->date_rum, '', 'R');

        $pdf->SetTextColor(0, 0, 60);
    }

    /**
     *      Show footer of page. Need this->emetteur object
     *
     *      @param  PDF         $pdf                PDF
     *      @param  Project     $object             Object to show
     *      @param  Translate   $outputlangs        Object lang for output
     *      @param  int         $hidefreetext       1=Hide free text
     *      @return integer
     */
    function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
    {
        global $conf;
        $showdetails = $conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS;
        return pdf_pagefoot($pdf, $outputlangs, 'SEPAMANDATMANAGER_FOOTERTEXT', null, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object, $showdetails, $hidefreetext);
    }
}
