<?php
/*
 * Copyright (C) 2017		 Oscss-Shop       <support@oscss-shop.fr>.
 *
 * This program is free software; you can redistribute it and/or modifyion 2.0 (the "License");
 * it under the terms of the GNU General Public License as published bypliance with the License.
 * the Free Software Foundation; either version 3 of the License, or
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

trait libPDFST {

    var $debug = false;

    /**
     * Add a bullet
     *
     * @param	PDF			$pdf            Object PDF
     * @param   int         $size           [=2] size
     * return void
     */
    function addBullet(&$pdf, $size=2)
    {
        $pdf->Rect($pdf->GetX() - 4, $pdf->GetY() + 1, $size, $size, 'F', array(), $this->main_color);
    }

    /**
     *   	Return a string with full address formated for output on documents
     *
     * 		@param	Translate	$outputlangs		Output langs object
     *   	@param  Societe		$sourcecompany		Source company object
     *   	@param  Societe		$targetcompany		Target company object
     *      @param  Contact		$targetcontact		Target contact object
     * 		@param	int			$usecontact			Use contact instead of company
     * 		@param	int			$mode				Address type ('source', 'target', 'targetwithdetails', 'targetwithdetails_xxx': target but include also phone/fax/email/url)
     *      @param  Object      $object             Object we want to build document for
     * 		@return	string							String with full address
     */
    function pdf_build_details($outputlangs, $sourcecompany, $targetcompany = '', $targetcontact = '', $usecontact = 0, $mode = 'source', $object = null)
    {
        global $conf, $hookmanager;

        if ($mode == 'source' && !is_object($sourcecompany)) return -1;
        if ($mode == 'target' && !is_object($targetcompany)) return -1;

        if (!empty($sourcecompany->state_id) && empty($sourcecompany->departement)) $sourcecompany->departement = getState($sourcecompany->state_id); //TODO deprecated
        if (!empty($sourcecompany->state_id) && empty($sourcecompany->state)) $sourcecompany->state       = getState($sourcecompany->state_id);
        if (!empty($targetcompany->state_id) && empty($targetcompany->departement)) $targetcompany->departement = getState($targetcompany->state_id); //TODO deprecated
        if (!empty($targetcompany->state_id) && empty($targetcompany->state)) $targetcompany->state       = getState($targetcompany->state_id);

        $reshook       = 0;
        $stringaddress = '';
        if (is_object($hookmanager)) {
            $parameters    = array('sourcecompany' => &$sourcecompany, 'targetcompany' => &$targetcompany, 'targetcontact' => $targetcontact, 'outputlangs' => $outputlangs, 'mode' => $mode, 'usecontact' => $usecontact);
            $action        = '';
            $reshook       = $hookmanager->executeHooks('pdf_build_address', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
            $stringaddress .= $hookmanager->resPrint;
        }
        if (empty($reshook)) {
            if ($mode == 'source') {
                $withCountry = 0;
                if (!empty($sourcecompany->country_code) && ($targetcompany->country_code != $sourcecompany->country_code)) $withCountry = 1;

                //$stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->convToOutputCharset(dol_format_address($sourcecompany, $withCountry, "\n", $outputlangs))."\n";

                if (empty($conf->global->MAIN_PDF_DISABLESOURCEDETAILS)) {
                    // Phone
                    if ($sourcecompany->phone)
                            $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("PhoneShort").": ".$outputlangs->convToOutputCharset($sourcecompany->phone);
                    // Fax
                    if ($sourcecompany->fax)
                            $stringaddress .= ($stringaddress ? ($sourcecompany->phone ? "\n" : "\n") : '' ).$outputlangs->transnoentities("Fax").": ".$outputlangs->convToOutputCharset($sourcecompany->fax);
                    // EMail
                    if ($sourcecompany->email) $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->convToOutputCharset($sourcecompany->email);
                    // Web
                    if ($sourcecompany->url) $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->convToOutputCharset($sourcecompany->url);
                }
            }

            if ($mode == 'target' || preg_match('/targetwithdetails/', $mode)) {
                if ($usecontact) {
                    $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->convToOutputCharset($targetcontact->getFullName($outputlangs, 1));

                    if (!empty($targetcontact->address)) {
                        $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->convToOutputCharset(dol_format_address($targetcontact))."\n";
                    } else {
                        $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->convToOutputCharset(dol_format_address($targetcompany))."\n";
                    }
                    // Country
                    if (!empty($targetcontact->country_code) && $targetcontact->country_code != $sourcecompany->country_code) {
                        $stringaddress .= $outputlangs->convToOutputCharset($outputlangs->transnoentitiesnoconv("Country".$targetcontact->country_code))."\n";
                    } else if (empty($targetcontact->country_code) && !empty($targetcompany->country_code) && ($targetcompany->country_code != $sourcecompany->country_code)) {
                        $stringaddress .= $outputlangs->convToOutputCharset($outputlangs->transnoentitiesnoconv("Country".$targetcompany->country_code))."\n";
                    }

                    if (!empty($conf->global->MAIN_PDF_ADDALSOTARGETDETAILS) || preg_match('/targetwithdetails/', $mode)) {
                        // Phone
                        if (!empty($conf->global->MAIN_PDF_ADDALSOTARGETDETAILS) || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_phone/', $mode)) {
                            if (!empty($targetcontact->phone_pro) || !empty($targetcontact->phone_mobile)) $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("Phone").": ";
                            if (!empty($targetcontact->phone_pro)) $stringaddress .= $outputlangs->convToOutputCharset($targetcontact->phone_pro);
                            if (!empty($targetcontact->phone_pro) && !empty($targetcontact->phone_mobile)) $stringaddress .= " / ";
                            if (!empty($targetcontact->phone_mobile)) $stringaddress .= $outputlangs->convToOutputCharset($targetcontact->phone_mobile);
                        }
                        // Fax
                        if (!empty($conf->global->MAIN_PDF_ADDALSOTARGETDETAILS) || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_fax/', $mode)) {
                            if ($targetcontact->fax) $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("Fax").": ".$outputlangs->convToOutputCharset($targetcontact->fax);
                        }
                        // EMail
                        if (!empty($conf->global->MAIN_PDF_ADDALSOTARGETDETAILS) || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_email/', $mode)) {
                            if ($targetcontact->email)
                                    $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("Email").": ".$outputlangs->convToOutputCharset($targetcontact->email);
                        }
                        // Web
                        if (!empty($conf->global->MAIN_PDF_ADDALSOTARGETDETAILS) || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_url/', $mode)) {
                            if ($targetcontact->url) $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("Web").": ".$outputlangs->convToOutputCharset($targetcontact->url);
                        }
                    }
                }
                else {
                    $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->convToOutputCharset(dol_format_address($targetcompany))."\n";
                    // Country
                    if (!empty($targetcompany->country_code) && $targetcompany->country_code != $sourcecompany->country_code)
                            $stringaddress .= $outputlangs->convToOutputCharset($outputlangs->transnoentitiesnoconv("Country".$targetcompany->country_code))."\n";

                    if (!empty($conf->global->MAIN_PDF_ADDALSOTARGETDETAILS) || preg_match('/targetwithdetails/', $mode)) {
                        // Phone
                        if (!empty($conf->global->MAIN_PDF_ADDALSOTARGETDETAILS) || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_phone/', $mode)) {
                            if (!empty($targetcompany->phone) || !empty($targetcompany->phone_mobile)) $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("Phone").": ";
                            if (!empty($targetcompany->phone)) $stringaddress .= $outputlangs->convToOutputCharset($targetcompany->phone);
                            if (!empty($targetcompany->phone) && !empty($targetcompany->phone_mobile)) $stringaddress .= " / ";
                            if (!empty($targetcompany->phone_mobile)) $stringaddress .= $outputlangs->convToOutputCharset($targetcompany->phone_mobile);
                        }
                        // Fax
                        if (!empty($conf->global->MAIN_PDF_ADDALSOTARGETDETAILS) || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_fax/', $mode)) {
                            if ($targetcompany->fax) $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("Fax").": ".$outputlangs->convToOutputCharset($targetcompany->fax);
                        }
                        // EMail
                        if (!empty($conf->global->MAIN_PDF_ADDALSOTARGETDETAILS) || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_email/', $mode)) {
                            if ($targetcompany->email)
                                    $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("Email").": ".$outputlangs->convToOutputCharset($targetcompany->email);
                        }
                        // Web
                        if (!empty($conf->global->MAIN_PDF_ADDALSOTARGETDETAILS) || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_url/', $mode)) {
                            if ($targetcompany->url) $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("Web").": ".$outputlangs->convToOutputCharset($targetcompany->url);
                        }
                    }
                }

                // Intra VAT
                if (empty($conf->global->MAIN_TVAINTRA_NOT_IN_ADDRESS)) {
                    if ($targetcompany->tva_intra) $stringaddress .= "\n".$outputlangs->transnoentities("VATIntraShort").': '.$outputlangs->convToOutputCharset($targetcompany->tva_intra);
                }

                // Professionnal Ids
                if (!empty($conf->global->MAIN_PROFID1_IN_ADDRESS) && !empty($targetcompany->idprof1)) {
                    $tmp           = $outputlangs->transcountrynoentities("ProfId1", $targetcompany->country_code);
                    if (preg_match('/\((.+)\)/', $tmp, $reg)) $tmp           = $reg[1];
                    $stringaddress .= "\n".$tmp.': '.$outputlangs->convToOutputCharset($targetcompany->idprof1);
                }
                if (!empty($conf->global->MAIN_PROFID2_IN_ADDRESS) && !empty($targetcompany->idprof2)) {
                    $tmp           = $outputlangs->transcountrynoentities("ProfId2", $targetcompany->country_code);
                    if (preg_match('/\((.+)\)/', $tmp, $reg)) $tmp           = $reg[1];
                    $stringaddress .= "\n".$tmp.': '.$outputlangs->convToOutputCharset($targetcompany->idprof2);
                }
                if (!empty($conf->global->MAIN_PROFID3_IN_ADDRESS) && !empty($targetcompany->idprof3)) {
                    $tmp           = $outputlangs->transcountrynoentities("ProfId3", $targetcompany->country_code);
                    if (preg_match('/\((.+)\)/', $tmp, $reg)) $tmp           = $reg[1];
                    $stringaddress .= "\n".$tmp.': '.$outputlangs->convToOutputCharset($targetcompany->idprof3);
                }
                if (!empty($conf->global->MAIN_PROFID4_IN_ADDRESS) && !empty($targetcompany->idprof4)) {
                    $tmp           = $outputlangs->transcountrynoentities("ProfId4", $targetcompany->country_code);
                    if (preg_match('/\((.+)\)/', $tmp, $reg)) $tmp           = $reg[1];
                    $stringaddress .= "\n".$tmp.': '.$outputlangs->convToOutputCharset($targetcompany->idprof4);
                }
                if (!empty($conf->global->MAIN_PROFID5_IN_ADDRESS) && !empty($targetcompany->idprof5)) {
                    $tmp           = $outputlangs->transcountrynoentities("ProfId5", $targetcompany->country_code);
                    if (preg_match('/\((.+)\)/', $tmp, $reg)) $tmp           = $reg[1];
                    $stringaddress .= "\n".$tmp.': '.$outputlangs->convToOutputCharset($targetcompany->idprof5);
                }
                if (!empty($conf->global->MAIN_PROFID6_IN_ADDRESS) && !empty($targetcompany->idprof6)) {
                    $tmp           = $outputlangs->transcountrynoentities("ProfId6", $targetcompany->country_code);
                    if (preg_match('/\((.+)\)/', $tmp, $reg)) $tmp           = $reg[1];
                    $stringaddress .= "\n".$tmp.': '.$outputlangs->convToOutputCharset($targetcompany->idprof6);
                }

                // Public note
                if (!empty($conf->global->MAIN_PUBLIC_NOTE_IN_ADDRESS)) {
                    if ($mode == 'source' && !empty($sourcecompany->note_public)) {
                        $stringaddress .= "\n".dol_string_nohtmltag($sourcecompany->note_public);
                    }
                    if (($mode == 'target' || preg_match('/targetwithdetails/', $mode)) && !empty($targetcompany->note_public)) {
                        $stringaddress .= "\n".dol_string_nohtmltag($targetcompany->note_public);
                    }
                }
            }
        }

        return $stringaddress;
    }

    /**
     * Rect pdf
     *
     * @param	PDF		$pdf			Object PDF
     * @param	float	$x				Abscissa of first point
     * @param	float	$y		        Ordinate of first point
     * @param	float	$l				??
     * @param	float	$h				??
     * @param	int		$hidetop		1=Hide top bar of array and title, 0=Hide nothing, -1=Hide only title
     * @param	int		$hidebottom		Hide bottom
     * @return	void
     */
    function printRect($pdf, $x, $y, $l, $h, $hidetop = 0, $hidebottom = 0)
    {
        //$pdf->SetLineStyle(array('color'=>$this->main_color));
        if (empty($hidetop) || $hidetop == -1) $pdf->line($x, $y, $x + $l, $y);
        $pdf->line($x + $l, $y, $x + $l, $y + $h);
        if (empty($hidebottom)) $pdf->line($x + $l, $y + $h, $x, $y + $h);
        $pdf->line($x, $y + $h, $x, $y);
    }


    /**
     * 	set a background
     *
     * 	@param	PDF			$pdf            Object PDF
     * 	@param  image url		$img_file   string image url
     * 	@return void
     */
    function setBackgroundImage(&$pdf, $img_file)
    {
        // ADD background image
        // get the current page break margin
        $bMargin         = $pdf->getBreakMargin();
        // get current auto-page-break mode
        $auto_page_break = $pdf->GetAutoPageBreak();
        // disable auto-page-break
        $pdf->SetAutoPageBreak(false, 0);
        // set bacground image
        $pdf->Image($img_file, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);
        // restore auto-page-break status
        $pdf->SetAutoPageBreak($auto_page_break, $bMargin);
        // set the starting point for the page content
        $pdf->setPageMark();

        // fix bg for content
        $pdf->SetFillColor(255, 255, 255);
    }

    /**
     *  Show footer of page for PDF generation
     *
     * 	@param	TCPDF			$pdf     		The PDF factory
     *  @param  Translate	$outputlangs	Object lang for output
     * 	@param	string		$paramfreetext	Constant name of free text
     * 	@param	Societe		$fromcompany	Object company
     * 	@param	int			$marge_basse	Margin bottom we use for the autobreak
     * 	@param	int			$marge_gauche	Margin left (no more used)
     * 	@param	int			$page_hauteur	Page height (no more used)
     * 	@param	Object		$object			Object shown in PDF
     * 	@param	int			$showdetails	Show company adress details into footer (0=Nothing, 1=Show address, 2=Show managers, 3=Both)
     *  @param	int			$hidefreetext	1=Hide free text, 0=Show free text
     * 	@return	int							Return height of bottom margin including footer text
     */
    function pdf_pagefoot(&$pdf, $outputlangs, $paramfreetext, $fromcompany, $marge_basse, $marge_gauche, $page_hauteur, $object, $showdetails = 0, $hidefreetext = 0)
    {
        global $conf, $user, $mysoc;

        $outputlangs->load("dict");
        $line = '';

        $dims = $pdf->getPageDimensions();
        $dims['rm']=1;
//        echo '<pre>';var_dump($dims);die();

        // Line of free text
        if (!empty($conf->global->$paramfreetext)) {
            $substitutionarray                   = pdf_getSubstitutionArray($outputlangs, null, $object);
            // More substitution keys
            $substitutionarray['__FROM_NAME__']  = $fromcompany->name;
            $substitutionarray['__FROM_EMAIL__'] = $fromcompany->email;
            complete_substitutions_array($substitutionarray, $outputlangs, $object);
            $newfreetext                         = make_substitutions($conf->global->$paramfreetext, $substitutionarray, $outputlangs);

            // Make a change into HTML code to allow to include images from medias directory.
            // <img alt="" src="/dolibarr_dev/htdocs/viewimage.php?modulepart=medias&amp;entity=1&amp;file=image/ldestailleur_166x166.jpg" style="height:166px; width:166px" />
            // become
            // <img alt="" src="'.DOL_DATA_ROOT.'/medias/image/ldestailleur_166x166.jpg" style="height:166px; width:166px" />
            $newfreetext = preg_replace('/(<img.*src=")[^\"]*viewimage\.php[^\"]*modulepart=medias[^\"]*file=([^\"]*)("[^\/]*\/>)/', '\1'.DOL_DATA_ROOT.'/medias/\2\3', $newfreetext);

            $line .= $outputlangs->convToOutputCharset($newfreetext);
        }

        // First line of company infos
        $line1 = "";
        $line2 = "";
        $line3 = "";
        $line4 = "";

        if ($showdetails == 1 || $showdetails == 3) {
            // Company name
            if ($fromcompany->name) {
                $line1 .= ($line1 ? " - " : "").$outputlangs->transnoentities("RegisteredOffice").": ".$fromcompany->name;
            }
            // Address
            if ($fromcompany->address) {
                $line1 .= ($line1 ? " - " : "").str_replace("\n", ", ", $fromcompany->address);
            }
            // Zip code
            if ($fromcompany->zip) {
                $line1 .= ($line1 ? " - " : "").$fromcompany->zip;
            }
            // Town
            if ($fromcompany->town) {
                $line1 .= ($line1 ? " " : "").$fromcompany->town;
            }
            // Phone
            if ($fromcompany->phone) {
                $line2 .= ($line2 ? " - " : "").$outputlangs->transnoentities("Phone").": ".$fromcompany->phone;
            }
            // Fax
            if ($fromcompany->fax) {
                $line2 .= ($line2 ? " - " : "").$outputlangs->transnoentities("Fax").": ".$fromcompany->fax;
            }

            // URL
            if ($fromcompany->url) {
                $line2 .= ($line2 ? " - " : "").$fromcompany->url;
            }
            // Email
            if ($fromcompany->email) {
                $line2 .= ($line2 ? " - " : "").$fromcompany->email;
            }
        }
        if ($showdetails == 2 || $showdetails == 3 || ($fromcompany->country_code == 'DE')) {
            // Managers
            if ($fromcompany->managers) {
                $line2 .= ($line2 ? " - " : "").$fromcompany->managers;
            }
        }

        // Line 3 of company infos
        // Juridical status
        if ($fromcompany->forme_juridique_code) {
            $line3 .= ($line3 ? " - " : "").$outputlangs->convToOutputCharset(getFormeJuridiqueLabel($fromcompany->forme_juridique_code));
        }
        // Company name
        if ($fromcompany->name) {
            $line3 .= ($line3 ? " - " : "").$fromcompany->name;
        }
        // Capital
        if ($fromcompany->capital) {
            $tmpamounttoshow = price2num($fromcompany->capital); // This field is a free string
            if (is_numeric($tmpamounttoshow) && $tmpamounttoshow > 0)
                    $line3           .= ($line3 ? " - " : "").$outputlangs->transnoentities("CapitalOf", price($tmpamounttoshow, 0, $outputlangs, 0, 0, 0, $conf->currency));
            else $line3           .= ($line3 ? " - " : "").$outputlangs->transnoentities("CapitalOf", $tmpamounttoshow, $outputlangs);
        }
        // Prof Id 1
        if ($fromcompany->idprof1 && ($fromcompany->country_code != 'FR' || !$fromcompany->idprof2)) {
            $field = $outputlangs->transcountrynoentities("ProfId1", $fromcompany->country_code);
            if (preg_match('/\((.*)\)/i', $field, $reg)) $field = $reg[1];
            $line3 .= ($line3 ? " - " : "").$field.": ".$outputlangs->convToOutputCharset($fromcompany->idprof1);
        }
        // Prof Id 2
        if ($fromcompany->idprof2) {
            $field = $outputlangs->transcountrynoentities("ProfId2", $fromcompany->country_code);
            if (preg_match('/\((.*)\)/i', $field, $reg)) $field = $reg[1];
            $line3 .= ($line3 ? " - " : "").$field.": ".$outputlangs->convToOutputCharset($fromcompany->idprof2);
        }

        // Line 4 of company infos
        // Prof Id 3
        if ($fromcompany->idprof3) {
            $field = $outputlangs->transcountrynoentities("ProfId3", $fromcompany->country_code);
            if (preg_match('/\((.*)\)/i', $field, $reg)) $field = $reg[1];
            $line4 .= ($line4 ? " - " : "").$field.": ".$outputlangs->convToOutputCharset($fromcompany->idprof3);
        }
        // Prof Id 4
        if ($fromcompany->idprof4) {
            $field = $outputlangs->transcountrynoentities("ProfId4", $fromcompany->country_code);
            if (preg_match('/\((.*)\)/i', $field, $reg)) $field = $reg[1];
            $line4 .= ($line4 ? " - " : "").$field.": ".$outputlangs->convToOutputCharset($fromcompany->idprof4);
        }
        // Prof Id 5
        if ($fromcompany->idprof5) {
            $field = $outputlangs->transcountrynoentities("ProfId5", $fromcompany->country_code);
            if (preg_match('/\((.*)\)/i', $field, $reg)) $field = $reg[1];
            $line4 .= ($line4 ? " - " : "").$field.": ".$outputlangs->convToOutputCharset($fromcompany->idprof5);
        }
        // Prof Id 6
        if ($fromcompany->idprof6) {
            $field = $outputlangs->transcountrynoentities("ProfId6", $fromcompany->country_code);
            if (preg_match('/\((.*)\)/i', $field, $reg)) $field = $reg[1];
            $line4 .= ($line4 ? " - " : "").$field.": ".$outputlangs->convToOutputCharset($fromcompany->idprof6);
        }
        // IntraCommunautary VAT
        if ($fromcompany->tva_intra != '') {
            $line4 .= ($line4 ? " - " : "").$outputlangs->transnoentities("VATIntraShort").": ".$outputlangs->convToOutputCharset($fromcompany->tva_intra);
        }

        $pdf->SetTextColor(255, 255, 255);
        //$pdf->SetTextColor(0, 0, 60);
        $pdf->SetFont('', '', 6);
        $pdf->SetDrawColor(224, 224, 224);

        // The start of the bottom of this page footer is positioned according to # of lines
        $freetextheight = 0;
        if ($line) { // Free text
            //$line="eee<br>\nfd<strong>sf</strong>sdf<br>\nghfghg<br>";
            if (empty($conf->global->PDF_ALLOW_HTML_FOR_FREE_TEXT)) {
                $width = 20000;
                $align = 'L'; // By default, ask a manual break: We use a large value 20000, to not have automatic wrap. This make user understand, he need to add CR on its text.
                if (!empty($conf->global->MAIN_USE_AUTOWRAP_ON_FREETEXT)) {
                    $width = 200;
                    $align = 'C';
                }
                $freetextheight = $pdf->getStringHeight($width, $line);
            } else {
                $freetextheight = pdfGetHeightForHtmlContent($pdf, dol_htmlentitiesbr($line, 1, 'UTF-8', 0));      // New method (works for HTML content)
                //print '<br>'.$freetextheight;exit;
            }
        }

        $marginwithfooter = $marge_basse + (!empty($line1) ? 3 : 0) + (!empty($line2) ? 3 : 0) + (!empty($line3) ? 3 : 0) + (!empty($line4) ? 3 : 0) - 2;
        $posy             = $marginwithfooter + 0;
        $retraitST = 80;

        if ($line) { // Free text
            $pdf->SetXY($dims['lm'] + $retraitST, -$posy);
            if (empty($conf->global->PDF_ALLOW_HTML_FOR_FREE_TEXT)) {   // by default
                $pdf->MultiCell(0, 3, $line, 0, $align, 0);
            } else {
                $pdf->writeHTMLCell(120, $freetextheight, $dims['lm'] + $retraitST, $dims['hk'] - $marginwithfooter, dol_htmlentitiesbr($line, 1, 'UTF-8', 0));
            }
            $posy -= $freetextheight+5;
        }

        $pdf->SetY(-$posy);
        //$pdf->line($dims['lm'], $dims['hk'] - $posy, $dims['wk'] - $dims['rm'], $dims['hk'] - $posy);
        $posy--;
        $posy--;



        if (!empty($line1)) {
            $pdf->SetFont('', 'B', 7);
            $pdf->SetXY($dims['lm'] + $retraitST, -$posy);
            $pdf->MultiCell($dims['wk'] - $dims['rm'] - $dims['lm'] - $retraitST, 2, $line1, 0, 'L', 0);
            $posy -= 3;
            $pdf->SetFont('', '', 7);
        }

        if (!empty($line2)) {
            $pdf->SetFont('', 'B', 7);
            $pdf->SetXY($dims['lm'] + $retraitST, -$posy);
            $pdf->MultiCell($dims['wk'] - $dims['rm'] - $dims['lm'] - $retraitST, 2, $line2, 0, 'L', 0);
            $posy -= 3;
            $pdf->SetFont('', '', 7);
        }

        if (!empty($line3)) {
            $pdf->SetXY($dims['lm'] + $retraitST, -$posy);
            $pdf->MultiCell($dims['wk'] - $dims['rm'] - $dims['lm'] - $retraitST, 2, $line3, 0, 'L', 0);
        }

        if (!empty($line4)) {
            $posy -= 3;
            $pdf->SetXY($dims['lm'] + $retraitST, -$posy);
            $pdf->MultiCell($dims['wk'] - $dims['rm'] - $dims['lm'] - $retraitST, 2, $line4, 0, 'L', 0);
        }

        // Show page nb only on iso languages (so default Helvetica font)
        if (strtolower(pdf_getPDFFont($outputlangs)) == 'helvetica') {
            $pdf->SetXY(202, 293);
            $pdf->SetFont('', 'B', 8);
            //print 'xxx'.$pdf->PageNo().'-'.$pdf->getAliasNbPages().'-'.$pdf->getAliasNumPage();exit;
            if (empty($conf->global->MAIN_USE_FPDF)) $pdf->MultiCell(15, 2, $pdf->PageNo().'/'.$pdf->getAliasNbPages(), 0, 'R', 0);
            else $pdf->MultiCell(15, 2, $outputlangs->transnoentities("pageNB").' '. $pdf->PageNo().'/{nb}', 0, 'R', 0);
        }

        return $marginwithfooter;
    }
}