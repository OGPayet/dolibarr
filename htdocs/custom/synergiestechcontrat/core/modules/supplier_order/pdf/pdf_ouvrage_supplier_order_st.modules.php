<?php
/* Copyright (C) 2004-2014 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin         <regis.houssin@capnetworks.com>
 * Copyright (C) 2007      Franky Van Liedekerke <franky.van.liedekerke@telenet.be>
 * Copyright (C) 2010-2014 Juanjo Menent         <jmenent@2byte.es>
 * Copyright (C) 2015       Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2017      Ferran Marcet         <fmarcet@2byte.es>
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
 *	\file       htdocs/core/modules/supplier_order/pdf/pdf_ouvrage_supplier_order_st.modules.php
 *	\ingroup    fournisseur
 *	\brief      File of class to generate suppliers orders from muscadet st model
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/supplier_order/modules_commandefournisseur.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';

dol_include_once('/synergiestechcontrat/class/libPDFST.trait.php');
dol_include_once('/synergiestech/lib/opendsi_pdf.lib.php');


/**
 *	Class to generate the supplier orders with the muscadet St model
 */
class pdf_ouvrage_supplier_order_st extends ModelePDFSuppliersOrders
{
    use libPDFST;
    var $db;
    var $name;
    var $description;
    var $type;

    var $phpmin = array(4,3,0); // Minimum version of PHP required by module
    var $version = 'dolibarr';

    var $page_largeur;
    var $page_hauteur;
    var $format;
	var $marge_gauche;
	var	$marge_droite;
	var	$marge_haute;
	var	$marge_basse;
    var $main_color = array(224, 208, 64);

	var $emetteur;	// Objet societe qui emet


	/**
	 *	Constructor
	 *
	 *  @param	DoliDB		$db      	Database handler
	 */
	function __construct($db)
	{
		global $conf,$langs,$mysoc;

		$langs->load("main");
		$langs->load("bills");

		$this->db = $db;
		$this->name = "muscadet_st";
		$this->description = $langs->trans('SuppliersCommandModel');

		// Dimension page pour format A4
		$this->type = 'pdf';
		$formatarray=pdf_getFormat();
		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = $formatarray['height'];
		$this->format = array($this->page_largeur,$this->page_hauteur);
        $this->marge_gauche=10;
        $this->marge_droite=10;
        $this->marge_haute =50;
        $this->marge_basse =15;

		$this->option_logo = 1;                    // Affiche logo
		$this->option_tva = 1;                     // Gere option tva FACTURE_TVAOPTION
		$this->option_modereg = 1;                 // Affiche mode reglement
		$this->option_condreg = 1;                 // Affiche conditions reglement
		$this->option_codeproduitservice = 1;      // Affiche code produit-service
		$this->option_multilang = 1;               // Dispo en plusieurs langues
		$this->option_escompte = 0;                // Affiche si il y a eu escompte
		$this->option_credit_note = 0;             // Support credit notes
		$this->option_freetext = 1;				   // Support add of a personalised text
		$this->option_draft_watermark = 1;		   // Support add of a watermark on drafts

		$this->franchise=!$mysoc->tva_assuj;

        // Get source company
		$this->emetteur=$mysoc;
		if (empty($this->emetteur->country_code)) $this->emetteur->country_code=substr($langs->defaultlang,-2);    // By default, if was not defined

		// Defini position des colonnes
		$this->posxdesc=$this->marge_gauche+1;
		if ($conf->global->PRODUCT_USE_UNITS)
		{
            $this->posxtva=118;
            $this->posxup=130;
            $this->posxqty=151;
            $this->posxunit=162;
		} else {
            $this->posxtva=109;
            $this->posxup=123;
            $this->posxqty=145;
		}
        $this->posxdiscount=170;
        $this->postotalht=180;
		//if (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT)) $this->posxtva=$this->posxup;
		$this->posxpicture=$this->posxtva - (empty($conf->global->MAIN_DOCUMENTS_WITH_PICTURE_WIDTH)?20:$conf->global->MAIN_DOCUMENTS_WITH_PICTURE_WIDTH);	// width of images
		if ($this->page_largeur < 210) // To work with US executive format
		{
			$this->posxpicture-=20;
			$this->posxtva-=20;
			$this->posxup-=20;
			$this->posxqty-=20;
			$this->posxunit-=20;
			$this->posxdiscount-=20;
			$this->postotalht-=20;
		}

		$this->tva=array();
        $this->localtax1=array();
        $this->localtax2=array();
        $this->atleastoneratenotnull=0;
		$this->atleastonediscount=0;
	}


    /**
     *  Function to build pdf onto disk
     *
     *  @param		CommandeFournisseur	$object				Id of object to generate
     *  @param		Translate			$outputlangs		Lang output object
     *  @param		string				$srctemplatepath	Full path of source filename for generator using a template file
     *  @param		int					$hidedetails		Do not show line details
     *  @param		int					$hidedesc			Do not show desc
     *  @param		int					$hideref			Do not show ref
     *  @return		int										1=OK, 0=KO
     */
	function write_file($object,$outputlangs='',$srctemplatepath='',$hidedetails=0,$hidedesc=0,$hideref=0)
	{
		global $user,$langs,$conf,$hookmanager,$mysoc;

		if (! is_object($outputlangs)) $outputlangs=$langs;
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (! empty($conf->global->MAIN_USE_FPDF)) $outputlangs->charset_output='ISO-8859-1';

		$outputlangs->load("main");
		$outputlangs->load("dict");
		$outputlangs->load("companies");
		$outputlangs->load("bills");
		$outputlangs->load("products");
		$outputlangs->load("orders");
        $outputlangs->load("pdf@synergiestechcontrat");

        $nblignes = count($object->lines);

        // Loop on each lines to detect if there is at least one image to show
        $realpatharray=array();
        if (! empty($conf->global->MAIN_GENERATE_SUPPLIER_ORDERS_WITH_PICTURE))
        {
            $objphoto = new Product($this->db);

            for ($i = 0 ; $i < $nblignes ; $i++)
            {
                if (empty($object->lines[$i]->fk_product)) continue;

                $objphoto->fetch($object->lines[$i]->fk_product);
                //var_dump($objphoto->ref);exit;
                if (! empty($conf->global->PRODUCT_USE_OLD_PATH_FOR_PHOTO))
                {
                    $pdir[0] = get_exdir($objphoto->id,2,0,0,$objphoto,'product') . $objphoto->id ."/photos/";
                    $pdir[1] = get_exdir(0,0,0,0,$objphoto,'product') . dol_sanitizeFileName($objphoto->ref).'/';
                }
                else
                {
                    $pdir[0] = get_exdir(0,0,0,0,$objphoto,'product') . dol_sanitizeFileName($objphoto->ref).'/';				// default
                    $pdir[1] = get_exdir($objphoto->id,2,0,0,$objphoto,'product') . $objphoto->id ."/photos/";	// alternative
                }

                $arephoto = false;
                foreach ($pdir as $midir)
                {
                    if (! $arephoto)
                    {
                        $dir = $conf->product->dir_output.'/'.$midir;

                        foreach ($objphoto->liste_photos($dir,1) as $key => $obj)
                        {
                            if (empty($conf->global->CAT_HIGH_QUALITY_IMAGES))		// If CAT_HIGH_QUALITY_IMAGES not defined, we use thumb if defined and then original photo
                            {
                                if ($obj['photo_vignette'])
                                {
                                    $filename= $obj['photo_vignette'];
                                }
                                else
                                {
                                    $filename=$obj['photo'];
                                }
                            }
                            else
                            {
                                $filename=$obj['photo'];
                            }

                            $realpath = $dir.$filename;
                            $arephoto = true;
                        }
                    }
                }

                if ($realpath && $arephoto) $realpatharray[$i]=$realpath;
            }
        }

        if (count($realpatharray) == 0) $this->posxpicture=$this->posxtva;

		if ($conf->fournisseur->dir_output.'/commande')
		{
			$object->fetch_thirdparty();

			$deja_regle = 0;
			$amount_credit_notes_included = 0;
			$amount_deposits_included = 0;
			//$amount_credit_notes_included = $object->getSumCreditNotesUsed();
            //$amount_deposits_included = $object->getSumDepositsUsed();

			// Definition of $dir and $file
			if ($object->specimen)
			{
				$dir = $conf->fournisseur->commande->dir_output;
				$file = $dir . "/SPECIMEN.pdf";
			}
			else
			{
				$objectref = dol_sanitizeFileName($object->ref);
				$objectrefsupplier = dol_sanitizeFileName($object->ref_supplier);
				$dir = $conf->fournisseur->commande->dir_output . '/'. $objectref;
				$file = $dir . "/" . $objectref . ".pdf";
				if (! empty($conf->global->SUPPLIER_REF_IN_NAME)) $file = $dir . "/" . $objectref . ($objectrefsupplier?"_".$objectrefsupplier:"").".pdf";
			}

			if (! file_exists($dir))
			{
				if (dol_mkdir($dir) < 0)
				{
					$this->error=$langs->transnoentities("ErrorCanNotCreateDir",$dir);
					return 0;
				}
			}

			if (file_exists($dir))
			{
				// Add pdfgeneration hook
				if (! is_object($hookmanager))
				{
					include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
					$hookmanager=new HookManager($this->db);
				}
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters=array('file'=>$file,'object'=>$object,'outputlangs'=>$outputlangs);
				global $action;
				$reshook=$hookmanager->executeHooks('beforePDFCreation',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks

				$nblignes = count($object->lines);

                // Create pdf instance
                $pdf = opendsi_pdf_getInstance($this->format);
                $pdf->backgroundImagePath = dol_buildpath('/synergiestechcontrat/img/fond_5.jpg');

                $default_font_size = pdf_getPDFFontSize($outputlangs);	// Must be after pdf_getInstance
                $pdf->SetAutoPageBreak(1,0);

                if (class_exists('TCPDF'))
                {
                    $pdf->setPrintHeader(false);
                    $pdf->setPrintFooter(false);
                }
                if (!empty($pdf->backgroundImagePath) && (class_exists('TCPDI') || class_exists('TCPDF'))) {
                    $pdf->setPrintHeader(true);
                }
                $pdf->SetFont(pdf_getPDFFont($outputlangs));
                // Set path to the background PDF File
                if (empty($conf->global->MAIN_DISABLE_FPDI) && ! empty($conf->global->MAIN_ADD_PDF_BACKGROUND))
                {
                    $pagecount = $pdf->setSourceFile($conf->mycompany->dir_output.'/'.$conf->global->MAIN_ADD_PDF_BACKGROUND);
                    $tplidx = $pdf->importPage(1);
                }

				$pdf->Open();
				$pagenb=0;
				$pdf->SetDrawColor(128,128,128);

				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities("Order"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("Order")." ".$outputlangs->convToOutputCharset($object->thirdparty->name));
				if (! empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) $pdf->SetCompression(false);

				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right

				// Positionne $this->atleastonediscount si on a au moins une remise
				for ($i = 0 ; $i < $nblignes ; $i++)
				{
					if ($object->lines[$i]->remise_percent)
					{
						$this->atleastonediscount++;
					}
				}
				if (empty($this->atleastonediscount) && empty($conf->global->PRODUCT_USE_UNITS))
				{
					$this->posxpicture+=($this->postotalht - $this->posxdiscount);
					$this->posxtva+=($this->postotalht - $this->posxdiscount);
					$this->posxup+=($this->postotalht - $this->posxdiscount);
					$this->posxqty+=($this->postotalht - $this->posxdiscount);
					$this->posxdiscount+=($this->postotalht - $this->posxdiscount);
					//$this->postotalht;
				}

				// New page
				$pdf->AddPage();
				if (! empty($tplidx)) $pdf->useTemplate($tplidx);
				$pagenb++;

                $heightforinfotot = 52;	// Height reserved to output the info and total part
                $heightforfreetext= (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT)?$conf->global->MAIN_PDF_FREETEXT_HEIGHT:5);	// Height reserved to output the free text on last page
                $heightforfooter = $this->marge_basse + 8;	// Height reserved to output the footer (value include bottom margin)

				$this->_pagehead($pdf, $object, 1, $outputlangs);
				$pdf->SetFont('','', $default_font_size - 1);
				$pdf->MultiCell(0, 3, '');		// Set interline to 3
				$pdf->SetTextColor(0,0,0);


                $tab_top = 100;
                $tab_top_newpage = (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)?40:10);
				$tab_height = 130;
				$tab_height_newpage = 150;

				// Incoterm
				$height_incoterms = 0;
				if ($conf->incoterm->enabled)
				{
					$desc_incoterms = $object->getIncotermsForPDF();
					if ($desc_incoterms)
					{
						$tab_top = 88;

						$pdf->SetFont('','', $default_font_size - 1);
						$pdf->writeHTMLCell(190, 3, $this->posxdesc-1, $tab_top-1, dol_htmlentitiesbr($desc_incoterms), 0, 1);
						$nexY = $pdf->GetY();
						$height_incoterms=$nexY-$tab_top;

						// Rect prend une longueur en 3eme param
						$pdf->SetDrawColor(192,192,192);
						$pdf->Rect($this->marge_gauche, $tab_top-1, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $height_incoterms+1);

						$tab_top = $nexY+6;
						$height_incoterms += 4;
					}
				}

				// Affiche notes
				if (! empty($object->note_public))
				{
					$tab_top = 88 + $height_incoterms;

					$pdf->SetFont('','', $default_font_size - 1);
					$pdf->writeHTMLCell(190, 3, $this->posxdesc-1, $tab_top, dol_htmlentitiesbr($object->note_public), 0, 1);
					$nexY = $pdf->GetY();
					$height_note=$nexY-$tab_top;

					// Rect prend une longueur en 3eme param
					$pdf->SetDrawColor(192,192,192);
					$pdf->Rect($this->marge_gauche, $tab_top-1, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $height_note+1);

					$tab_height = $tab_height - $height_note;
					$tab_top = $nexY+6;
				}
				else
				{
					$height_note=0;
				}

				$iniY = $tab_top + 7;
				$curY = $tab_top + 7;
				$nexY = $tab_top + 7;

				// Loop on each lines
				for ($i = 0 ; $i < $nblignes ; $i++)
				{
					$curY = $nexY;
					$pdf->SetFont('','', $default_font_size - 1);   // Into loop to work with multipage
					$pdf->SetTextColor(0,0,0);

                    // Define size of image if we need it
                    $imglinesize=array();
                    if (! empty($realpatharray[$i])) $imglinesize=pdf_getSizeForImage($realpatharray[$i]);

					$pdf->setTopMargin($tab_top_newpage);
					$pdf->setPageOrientation('', 1, $heightforfooter+$heightforfreetext+$heightforinfotot);	// The only function to edit the bottom margin of current page to set it.
					$pageposbefore=$pdf->getPage();

                    $showpricebeforepagebreak=1;
                    $posYAfterImage=0;
                    $posYAfterDescription=0;

                    // We start with Photo of product line
                    if (isset($imglinesize['width']) && isset($imglinesize['height']) && ($curY + $imglinesize['height']) > ($this->page_hauteur-($heightforfooter+$heightforfreetext+$heightforinfotot)))	// If photo too high, we moved completely on new page
                    {
                        $pdf->AddPage('','',true);
                        if (! empty($tplidx)) $pdf->useTemplate($tplidx);
                        if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
                        $pdf->setPage($pageposbefore+1);

                        $curY = $tab_top_newpage+6;
                        $showpricebeforepagebreak=0;
                    }

                    if (isset($imglinesize['width']) && isset($imglinesize['height']))
                    {
                        $curX = $this->posxpicture-1;
                        if (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT) || !empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT_COLUMN)) {
                            $pdf->Image($realpatharray[$i], $curX + (($this->posxtva-$this->posxpicture-$imglinesize['width'])/2) + 10, $curY+3, $imglinesize['width'], $imglinesize['height'], '', '', '', 2, 300);	// Use 300 dpi
                        } else {
                            $pdf->Image($realpatharray[$i], $curX + (($this->posxtva-$this->posxpicture-$imglinesize['width'])/2) - 1, $curY+3, $imglinesize['width'], $imglinesize['height'], '', '', '', 2, 300);	// Use 300 dpi
                        }
                        // $pdf->Image does not increase value return by getY, so we save it manually
                        $posYAfterImage=$curY+$imglinesize['height'];
                    }

					// Description of product line
					$curX = $this->posxdesc-1;

					$showpricebeforepagebreak=1;

					$pdf->startTransaction();
					pdf_writelinedesc($pdf,$object,$i,$outputlangs,$this->posxpicture-$curX,3,$curX,$curY,$hideref,$hidedesc,1);
					$pageposafter=$pdf->getPage();
					if ($pageposafter > $pageposbefore)	// There is a pagebreak
					{
						$pdf->rollbackTransaction(true);
						$pageposafter=$pageposbefore;
						//print $pageposafter.'-'.$pageposbefore;exit;
						$pdf->setPageOrientation('', 1, $heightforfooter);	// The only function to edit the bottom margin of current page to set it.
						pdf_writelinedesc($pdf,$object,$i,$outputlangs,$this->posxpicture-$curX,3,$curX,$curY,$hideref,$hidedesc,1);

                        $pageposafter=$pdf->getPage();
						$posyafter=$pdf->GetY();
						if ($posyafter > ($this->page_hauteur - ($heightforfooter+$heightforfreetext+$heightforinfotot)))	// There is no space left for total+free text
						{
							if ($i == ($nblignes-1))	// No more lines, and no space left to show total, so we create a new page
							{
								$pdf->AddPage('','',true);
								if (! empty($tplidx)) $pdf->useTemplate($tplidx);
								if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
								$pdf->setPage($pageposafter+1);
							}
						}
						else
						{
							// We found a page break
							$showpricebeforepagebreak=0;
						}
					}
					else	// No pagebreak
					{
						$pdf->commitTransaction();
					}
                    $posYAfterDescription=$pdf->GetY();

					$nexY = $pdf->GetY();
                    $pageposafter=$pdf->getPage();

					$pdf->setPage($pageposbefore);
					$pdf->setTopMargin($this->marge_haute);
					$pdf->setPageOrientation('', 1, 0);	// The only function to edit the bottom margin of current page to set it.

					// We suppose that a too long description is moved completely on next page
					if ($pageposafter > $pageposbefore && empty($showpricebeforepagebreak)) {
						$pdf->setPage($pageposafter); $curY = $tab_top_newpage;
					}

					$pdf->SetFont('','', $default_font_size - 1);   // On repositionne la police par defaut

					// VAT Rate
					if (empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT))
					{
						$vat_rate = pdf_getlinevatrate($object, $i, $outputlangs, $hidedetails);
						$pdf->SetXY($this->posxtva, $curY);
						$pdf->MultiCell($this->posxup-$this->posxtva-0.8, 3, $vat_rate, 0, 'R');
					}

					// Unit price before discount
					$up_excl_tax = pdf_getlineupexcltax($object, $i, $outputlangs, $hidedetails);
					$pdf->SetXY($this->posxup-1.6, $curY);
					$pdf->MultiCell($this->posxqty-$this->posxup+1.2, 4, $up_excl_tax, 0, 'R', 0);

					// Quantity
					$pdf->SetXY($this->posxqty, $curY);
					// Enough for 6 chars
					if($conf->global->PRODUCT_USE_UNITS)
					{
						$pdf->MultiCell($this->posxunit-$this->posxqty-0.8, 4, $object->lines[$i]->qty, 0, 'R');
					}
					else
					{
						$pdf->MultiCell($this->posxdiscount-$this->posxqty-0.8, 4, $object->lines[$i]->qty, 0, 'R');
					}

					// Unit
					if($conf->global->PRODUCT_USE_UNITS)
					{
						$unit = pdf_getlineunit($object, $i, $outputlangs, $hidedetails, $hookmanager);
						$pdf->SetXY($this->posxunit, $curY);
						$pdf->MultiCell($this->posxdiscount-$this->posxunit-0.8, 4, $unit, 0, 'L');
					}

					// Discount on line
					$pdf->SetXY($this->posxdiscount, $curY);
					if ($object->lines[$i]->remise_percent)
					{
                        $pdf->SetXY($this->posxdiscount-2, $curY);
                        $remise_percent = pdf_getlineremisepercent($object, $i, $outputlangs, $hidedetails);
						$pdf->MultiCell($this->postotalht-$this->posxdiscount+2, 3, $remise_percent, 0, 'R');
					}

					// Total HT line
                    $total_excl_tax = pdf_getlinetotalexcltax($object, $i, $outputlangs);
					$pdf->SetXY($this->postotalht-8, $curY);
					$pdf->MultiCell($this->page_largeur-$this->marge_droite-$this->postotalht+8, 3, $total_excl_tax, 0, 'R', 0);

					// Collecte des totaux par valeur de tva dans $this->tva["taux"]=total_tva
					if ($conf->multicurrency->enabled && $object->multicurrency_tx != 1) $tvaligne=$object->lines[$i]->multicurrency_total_tva;
					else $tvaligne=$object->lines[$i]->total_tva;
					$localtax1ligne=$object->lines[$i]->total_localtax1;
					$localtax2ligne=$object->lines[$i]->total_localtax2;
					$localtax1_rate=$object->lines[$i]->localtax1_tx;
					$localtax2_rate=$object->lines[$i]->localtax2_tx;
					$localtax1_type=$object->lines[$i]->localtax1_type;
					$localtax2_type=$object->lines[$i]->localtax2_type;

					if (! empty($object->remise_percent)) $tvaligne-=($tvaligne*$object->remise_percent)/100;
					if (! empty($object->remise_percent)) $localtax1ligne-=($localtax1ligne*$object->remise_percent)/100;
					if (! empty($object->remise_percent)) $localtax2ligne-=($localtax2ligne*$object->remise_percent)/100;

					$vatrate=(string) $object->lines[$i]->tva_tx;

					// Retrieve type from database for backward compatibility with old records
					if ((! isset($localtax1_type) || $localtax1_type=='' || ! isset($localtax2_type) || $localtax2_type=='') // if tax type not defined
					&& (! empty($localtax1_rate) || ! empty($localtax2_rate))) // and there is local tax
					{
						$localtaxtmp_array=getLocalTaxesFromRate($vatrate,0,$mysoc,$object->thirdparty);
						$localtax1_type = $localtaxtmp_array[0];
						$localtax2_type = $localtaxtmp_array[2];
					}

				    // retrieve global local tax
					if ($localtax1_type && $localtax1ligne != 0)
						$this->localtax1[$localtax1_type][$localtax1_rate]+=$localtax1ligne;
					if ($localtax2_type && $localtax2ligne != 0)
						$this->localtax2[$localtax2_type][$localtax2_rate]+=$localtax2ligne;

					if (($object->lines[$i]->info_bits & 0x01) == 0x01) $vatrate.='*';
					if (! isset($this->tva[$vatrate])) 				$this->tva[$vatrate]=0;
					$this->tva[$vatrate] += $tvaligne;

                    if ($posYAfterImage > $posYAfterDescription) $nexY=$posYAfterImage;

					// Add line
					if (! empty($conf->global->MAIN_PDF_DASH_BETWEEN_LINES) && $i < ($nblignes - 1))
					{
						$pdf->setPage($pageposafter);
						$pdf->SetLineStyle(array('dash'=>'1,1','color'=>array(80,80,80)));
						//$pdf->SetDrawColor(190,190,200);
						$pdf->line($this->marge_gauche, $nexY+1, $this->page_largeur - $this->marge_droite, $nexY+1);
						$pdf->SetLineStyle(array('dash'=>0));
					}

					$nexY+=2;    // Passe espace entre les lignes

					// Detect if some page were added automatically and output _tableau for past pages
					while ($pagenb < $pageposafter)
					{
						$pdf->setPage($pagenb);
						if ($pagenb == 1)
						{
							$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1, $object->multicurrency_code);
						}
						else
						{
							$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1, $object->multicurrency_code);
						}
						$this->_pagefoot($pdf,$object,$outputlangs,1);
						$pagenb++;
						$pdf->setPage($pagenb);
						$pdf->setPageOrientation('', 1, 0);	// The only function to edit the bottom margin of current page to set it.
						if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
					}
					if (isset($object->lines[$i+1]->pagebreak) && $object->lines[$i+1]->pagebreak)
					{
						if ($pagenb == 1)
						{
							$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1, $object->multicurrency_code);
						}
						else
						{
							$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1, $object->multicurrency_code);
						}
						$this->_pagefoot($pdf,$object,$outputlangs,1);
						// New page
						$pdf->AddPage();
						if (! empty($tplidx)) $pdf->useTemplate($tplidx);
						$pagenb++;
						if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
					}
				}

				// Show square
				if ($pagenb == 1)
				{
					$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 0, 0, $object->multicurrency_code);
					$bottomlasttab=$this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;
				}
				else
				{
					$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 1, 0, $object->multicurrency_code);
					$bottomlasttab=$this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;
				}
                $bottomlasttab+=2;

				// Affiche zone infos
				$posy=$this->_tableau_info($pdf, $object, $bottomlasttab, $outputlangs);

				// Affiche zone totaux
				$posy=$this->_tableau_tot($pdf, $object, $deja_regle, $bottomlasttab, $outputlangs);

				// Affiche zone versements
				if ($deja_regle || $amount_credit_notes_included || $amount_deposits_included)
				{
					$posy=$this->_tableau_versements($pdf, $object, $posy, $outputlangs);
				}

                // Pied de page
				$this->_pagefoot($pdf, $object, $outputlangs);
				if (method_exists($pdf,'AliasNbPages')) $pdf->AliasNbPages();

				$pdf->Close();

                if($this->debug){
                    $pdf->Output('test.pdf', 'I');
                } else {
                    $pdf->Output($file, 'F');
                }

				// Add pdfgeneration hook
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters=array('file'=>$file,'object'=>$object,'outputlangs'=>$outputlangs);
				global $action;
				$reshook=$hookmanager->executeHooks('afterPDFCreation',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks

				if (! empty($conf->global->MAIN_UMASK))
				    @chmod($file, octdec($conf->global->MAIN_UMASK));

				return 1;   // Pas d'erreur
			}
			else
			{
				$this->error=$langs->trans("ErrorCanNotCreateDir",$dir);
				return 0;
			}
		}
		else
		{
			$this->error=$langs->trans("ErrorConstantNotDefined","SUPPLIER_OUTPUTDIR");
			return 0;
		}
	}


	/**
	 *  Show payments table
	 *
	 *  @param	PDF			$pdf     		Object PDF
	 *  @param  CommandeFournisseur		$object			Object order
	 *	@param	int			$posy			Position y in PDF
	 *	@param	Translate	$outputlangs	Object langs for output
	 *	@return int							<0 if KO, >0 if OK
	 */
	function _tableau_versements(&$pdf, $object, $posy, $outputlangs)
	{

	}


	/**
	 *   Show miscellaneous information (payment mode, payment term, ...)
	 *
	 *   @param		PDF			$pdf     		Object PDF
	 *   @param		CommandeFournisseur		$object			Object to show
	 *   @param		int			$posy			Y
	 *   @param		Translate	$outputlangs	Langs object
	 *   @return	integer
	 */
	function _tableau_info(&$pdf, $object, $posy, $outputlangs)
	{
	    //global $conf;
	    $default_font_size = pdf_getPDFFontSize($outputlangs);

        $posyinit = $posy;
        $pdf->Rect($this->marge_gauche, $posy, 100, 5, 'F', array(), $this->main_color);
        $pdf->SetFont('', 'B', $default_font_size - 2);
        $pdf->SetXY($this->marge_gauche+1, $posy+1);
        $pdf->SetTextColor(255,255,255);
        $pdf->MultiCell(90, 3, $outputlangs->transnoentities("MeanOP"), 0, 'L', 0);
        $pdf->SetTextColor(0, 0, 60);
        $posy+=7;

        $pdf->SetFont('','', $default_font_size - 1);

        // If France, show VAT mention if not applicable
		if ($this->emetteur->country_code == 'FR' && $this->franchise == 1) {
			$pdf->SetFont('','B', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche, $posy);
			$pdf->MultiCell(100, 3, $outputlangs->transnoentities("VATIsNotUsedForInvoice"), 0, 'L', 0);

			$posy=$pdf->GetY()+4;
		}

		$posxval=52;

	    // Show payments conditions
	    if (!empty($object->cond_reglement_code) || $object->cond_reglement) {
	        $pdf->SetFont('','B', $default_font_size - 2);
	        $pdf->SetXY($this->marge_gauche, $posy);
	        $titre = $outputlangs->transnoentities("PaymentConditions").':';
	        $pdf->MultiCell(80, 4, $titre, 0, 'L');

			$pdf->SetFont('','', $default_font_size - 2);
			$pdf->SetXY($posxval, $posy);
            $testpayementcondition="PaymentCondition".$object->cond_reglement_code;
            if ($outputlangs->transnoentities($testpayementcondition)!=$testpayementcondition)
                $lib_condition_paiement = $outputlangs->transnoentities($testpayementcondition);
            else
                $lib_condition_paiement = $outputlangs->convToOutputCharset($object->cond_reglement);
			$lib_condition_paiement=str_replace('\n',"\n",$lib_condition_paiement);
			$pdf->MultiCell(80, 4, $lib_condition_paiement,0,'L');

	        $posy=$pdf->GetY()+3;
	    }

	// Show payment mode
        if (!empty($object->mode_reglement_code))
        {
		$pdf->SetFont('','B', $default_font_size - 2);
		$pdf->SetXY($this->marge_gauche, $posy);
		$titre = $outputlangs->transnoentities("PaymentMode").':';
		$pdf->MultiCell(80, 5, $titre, 0, 'L');

		$pdf->SetFont('','', $default_font_size - 2);
		$pdf->SetXY($posxval, $posy);
            $testpayementtype="PaymentType".$object->mode_reglement_code;
            if ($outputlangs->transnoentities($testpayementtype)!=$testpayementtype)
                $lib_mode_reg = $outputlangs->transnoentities($testpayementtype);
            else
                $lib_mode_reg = $outputlangs->convToOutputCharset($object->mode_reglement);
		$pdf->MultiCell(80, 5, $lib_mode_reg,0,'L');

		$posy=$pdf->GetY()+2;
        }

        $this->printRect($pdf,$this->marge_gauche,$posyinit,100,$posy - $posyinit);

		return $posy;
	}

	/**
	 *	Show total to pay
	 *
	 *	@param	PDF			$pdf           Object PDF
	 *	@param  Facture		$object         Object invoice
	 *	@param  int			$deja_regle     Montant deja regle
	 *	@param	int			$posy			Position depart
	 *	@param	Translate	$outputlangs	Objet langs
	 *	@return int							Position pour suite
	 */
	function _tableau_tot(&$pdf, $object, $deja_regle, $posy, $outputlangs)
	{
		global $conf,$mysoc;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

        $tab2_top = $posy;
		$tab2_hl = 6;
		$pdf->SetFont('','', $default_font_size - 1);

		// Tableau total
		$col1x = 120; $col2x = 170;
		if ($this->page_largeur < 210) // To work with US executive format
		{
			$col2x-=20;
		}
		$largcol2 = ($this->page_largeur - $this->marge_droite - $col2x);

		$useborder=0;
		$index = 0;

		// Total HT
		$pdf->SetFillColor(255, 255, 255);
		$pdf->SetXY($col1x, $tab2_top + 1);
        $this->printRect($pdf,$col1x, $tab2_top + 0,80,$tab2_hl ,0,1);
		$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("TotalHT"), 0, 'L', 1);

		$total_ht = ($conf->multicurrency->enabled && $object->mylticurrency_tx != 1 ? $object->multicurrency_total_ht : $object->total_ht);
		$pdf->SetXY($col2x, $tab2_top + 1);
		$pdf->MultiCell($largcol2, $tab2_hl, price($total_ht + (! empty($object->remise)?$object->remise:0), 0, $outputlangs, 1, -1, -1, 'auto'), 0, 'R', 1);

		// Show VAT by rates and total
		$pdf->SetFillColor(248,248,248);

		$this->atleastoneratenotnull=0;
		foreach( $this->tva as $tvakey => $tvaval )
		{
			if ($tvakey > 0)    // On affiche pas taux 0
			{
				$this->atleastoneratenotnull++;

				$index++;
				$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index +1);

				$tvacompl='';

				if (preg_match('/\*/',$tvakey))
				{
					$tvakey=str_replace('*','',$tvakey);
					$tvacompl = " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
				}

				$totalvat =$outputlangs->transcountrynoentities("TotalVAT",$mysoc->country_code).' ';
				$totalvat.=vatrate($tvakey,1).$tvacompl;
                $this->printRect($pdf, $col1x, $tab2_top + $tab2_hl * $index,80, $tab2_hl ,0,1);
				$pdf->MultiCell($col2x-$col1x, $tab2_hl, $totalvat, 0, 'L', 1);

				$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index +1);
				$pdf->MultiCell($largcol2, $tab2_hl, price($tvaval, 0, $outputlangs, 1, -1, -1, 'auto'), 0, 'R', 1);
			}
		}
		if (! $this->atleastoneratenotnull) // If no vat at all
		{
			$index++;
			$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transcountrynoentities("TotalVAT", $mysoc->country_code), 0, 'L', 1);

			$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($largcol2, $tab2_hl, price($object->total_tva, 0, $outputlangs, 1, -1, -1, 'auto'), 0, 'R', 1);

			// Total LocalTax1
			if (! empty($conf->global->FACTURE_LOCAL_TAX1_OPTION) && $conf->global->FACTURE_LOCAL_TAX1_OPTION=='localtax1on' && $object->total_localtax1>0)
			{
				$index++;
				$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
				$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transcountrynoentities("TotalLT1",$mysoc->country_code), 0, 'L', 1);
				$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
				$pdf->MultiCell($largcol2, $tab2_hl, price($object->total_localtax1, 0, $outputlangs, 1, -1, -1, 'auto'), $useborder, 'R', 1);
			}

			// Total LocalTax2
			if (! empty($conf->global->FACTURE_LOCAL_TAX2_OPTION) && $conf->global->FACTURE_LOCAL_TAX2_OPTION=='localtax2on' && $object->total_localtax2>0)
			{
				$index++;
				$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
				$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transcountrynoentities("TotalLT2",$mysoc->country_code), 0, 'L', 1);
				$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
				$pdf->MultiCell($largcol2, $tab2_hl, price($object->total_localtax2, 0, $outputlangs, 1, -1, -1, 'auto'), $useborder, 'R', 1);
			}
		}
		else
		{
			//if (! empty($conf->global->FACTURE_LOCAL_TAX1_OPTION) && $conf->global->FACTURE_LOCAL_TAX1_OPTION=='localtax1on')
			//{
			//Local tax 1
			foreach( $this->localtax1 as $localtax_type => $localtax_rate )
			{
				if (in_array((string) $localtax_type, array('2','4','6'))) continue;

				foreach( $localtax_rate as $tvakey => $tvaval )
				{
					if ($tvakey != 0)    // On affiche pas taux 0
					{
						//$this->atleastoneratenotnull++;

						$index++;
						$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

						$tvacompl='';
						if (preg_match('/\*/',$tvakey))
						{
							$tvakey=str_replace('*','',$tvakey);
							$tvacompl = " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
						}
						$totalvat =$outputlangs->transcountrynoentities("TotalLT1",$mysoc->country_code).' ';
						$totalvat.=vatrate(abs($tvakey),1).$tvacompl;
						$pdf->MultiCell($col2x-$col1x, $tab2_hl, $totalvat, 0, 'L', 1);

						$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
						$pdf->MultiCell($largcol2, $tab2_hl, price($tvaval, 0, $outputlangs, 1, -1, -1, 'auto'), 0, 'R', 1);
					}
				}
			}

			//if (! empty($conf->global->FACTURE_LOCAL_TAX2_OPTION) && $conf->global->FACTURE_LOCAL_TAX2_OPTION=='localtax2on')
			//{
			//Local tax 2
			foreach( $this->localtax2 as $localtax_type => $localtax_rate )
			{
				if (in_array((string) $localtax_type, array('2','4','6'))) continue;

				foreach( $localtax_rate as $tvakey => $tvaval )
				{
					if ($tvakey != 0)    // On affiche pas taux 0
					{
						//$this->atleastoneratenotnull++;

						$index++;
						$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

						$tvacompl='';
						if (preg_match('/\*/',$tvakey))
						{
							$tvakey=str_replace('*','',$tvakey);
							$tvacompl = " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
						}
						$totalvat =$outputlangs->transcountrynoentities("TotalLT2",$mysoc->country_code).' ';
						$totalvat.=vatrate(abs($tvakey),1).$tvacompl;
						$pdf->MultiCell($col2x-$col1x, $tab2_hl, $totalvat, 0, 'L', 1);

						$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
						$pdf->MultiCell($largcol2, $tab2_hl, price($tvaval, 0, $outputlangs, 1, -1, -1, 'auto'), 0, 'R', 1);
					}
				}
			}
		}

		// Total TTC
		$index++;
		$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index +1);
		$pdf->SetTextColor(0, 0, 60);
        $pdf->Rect($col1x, $tab2_top + $tab2_hl * $index,80,$tab2_hl, 'F', array(), $this->main_color);
		$pdf->SetFillColor(255, 255, 255);
		$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("TotalTTC"), $useborder, 'L', 1);

		$total_ttc = ($conf->multicurrency->enabled && $object->multicurrency_tx != 1) ? $object->multicurrency_total_ttc : $object->total_ttc;
		$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index +1);
		$pdf->MultiCell($largcol2, $tab2_hl, price($total_ttc, 0, $outputlangs, 1, -1, -1, 'auto'), $useborder, 'R', 1);
		$pdf->SetFont('','', $default_font_size - 1);
		$pdf->SetTextColor(0,0,0);

        $creditnoteamount=0;
        $depositsamount=0;
		//$creditnoteamount=$object->getSumCreditNotesUsed();
		//$depositsamount=$object->getSumDepositsUsed();
		//print "x".$creditnoteamount."-".$depositsamount;exit;
		$resteapayer = price2num($total_ttc - $deja_regle - $creditnoteamount - $depositsamount, 'MT');
		if (! empty($object->paye)) $resteapayer=0;

		if ($deja_regle > 0)
		{
			// Already paid + Deposits
		    $index++;

			$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("AlreadyPaid"), 0, 'L', 0);
			$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($largcol2, $tab2_hl, price($deja_regle, 0, $outputlangs, 1, -1, -1, 'auto'), 0, 'R', 0);

			$index++;
			$pdf->SetTextColor(0,0,60);
			$pdf->SetFillColor(224,224,224);
			$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("RemainderToPay"), $useborder, 'L', 1);

			$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($largcol2, $tab2_hl, price($resteapayer, 0, $outputlangs, 1, -1, -1, 'auto'), $useborder, 'R', 1);

			$pdf->SetFont('','', $default_font_size - 1);
			$pdf->SetTextColor(0,0,0);
		}

		$index++;
		return ($tab2_top + ($tab2_hl * $index));
	}

    /**
	 *   Show table for lines
	 *
	 *   @param		PDF			$pdf     		Object PDF
	 *   @param		string		$tab_top		Top position of table
	 *   @param		string		$tab_height		Height of table (rectangle)
	 *   @param		int			$nexY			Y (not used)
	 *   @param		Translate	$outputlangs	Langs object
	 *   @param		int			$hidetop		Hide top bar of array
	 *   @param		int			$hidebottom		Hide bottom bar of array
	 *   @param		string		$currency		Currency code
	 *   @return	void
	 */
	function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop=0, $hidebottom=0, $currency='')
	{
		global $conf;

		// Force to disable hidetop and hidebottom
		$hidebottom=0;
		if ($hidetop) $hidetop=-1;

		$currency = !empty($currency) ? $currency : $conf->currency;
		$default_font_size = pdf_getPDFFontSize($outputlangs);

        // Amount in (at tab_top - 1)
		$pdf->SetTextColor(0, 0, 0);
		$pdf->SetFont('','', $default_font_size - 2);

		if (empty($hidetop))
		{
			$titre = $outputlangs->transnoentities("AmountInCurrency",$outputlangs->transnoentitiesnoconv("Currency".$currency));
			$pdf->SetXY($this->page_largeur - $this->marge_droite - ($pdf->GetStringWidth($titre) + 3), $tab_top-10);
			$pdf->MultiCell(($pdf->GetStringWidth($titre) + 3), 2, $titre);

			//$conf->global->MAIN_PDF_TITLE_BACKGROUND_COLOR='230,230,230';
			if (! empty($conf->global->MAIN_PDF_TITLE_BACKGROUND_COLOR)) $pdf->Rect($this->marge_gauche, $tab_top, $this->page_largeur-$this->marge_droite-$this->marge_gauche, 5, 'F', null, explode(',',$conf->global->MAIN_PDF_TITLE_BACKGROUND_COLOR));
		}

		$pdf->SetDrawColor(128,128,128);
		$pdf->SetFont('','', $default_font_size - 1);

        //ADD
        if (empty($hidetop)) {
            $pdf->SetDrawColor(255, 255, 255);
            $pdf->SetTextColor(255, 255, 255);

            $pdf->Rect($this->marge_gauche, $tab_top - 5, $this->page_largeur - $this->marge_gauche - $this->marge_droite, 7, 'F', array(), $this->main_color);
            //$this->printRect($pdf, $this->marge_gauche, $tab_top -5 , $this->page_largeur - $this->marge_gauche - $this->marge_droite, 5, 0, 0); // Rect prend une longueur en 3eme param et 4eme param
            $pdf->SetDrawColor(128, 128, 128);
            //$hidetop=1;
        }
        $tab_top+=5;
        $tab_height-=5;

		// Output Rect
		$this->printRect($pdf,$this->marge_gauche, $tab_top, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $tab_height, $hidetop, $hidebottom);	// Rect prend une longueur en 3eme param et 4eme param

		if (empty($hidetop))
		{
			//$pdf->line($this->marge_gauche, $tab_top+5, $this->page_largeur-$this->marge_droite, $tab_top+5);	// line prend une position y en 2eme param et 4eme param

			$pdf->SetXY($this->posxdesc-1, $tab_top - 8.5);
			$pdf->MultiCell(108,2, $outputlangs->transnoentities("description"),'','L');
		}

        if (empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT))
        {
		$pdf->line($this->posxtva-1, $tab_top, $this->posxtva-1, $tab_top + $tab_height);
			if (empty($hidetop))
			{
			$pdf->SetXY($this->posxtva-3, $tab_top - 8.5);
			$pdf->MultiCell($this->posxup-$this->posxtva+3,2, $outputlangs->transnoentities("VAT"),'','C');
			}
        }

		$pdf->line($this->posxup-1, $tab_top, $this->posxup-1, $tab_top + $tab_height);
		if (empty($hidetop))
		{
			$pdf->SetXY($this->posxup-1, $tab_top - 8.5);
			$pdf->MultiCell($this->posxqty-$this->posxup-1,2, $outputlangs->transnoentities("PriceUHT"),'','C');
		}

		$pdf->line($this->posxqty-1, $tab_top, $this->posxqty-1, $tab_top + $tab_height);
		if (empty($hidetop))
		{
			$pdf->SetXY($this->posxqty-1, $tab_top - 8.5);
			if($conf->global->PRODUCT_USE_UNITS)
			{
				$pdf->MultiCell($this->posxunit-$this->posxqty-1,2, $outputlangs->transnoentities("Qty"),'','C');
			}
			else
			{
				$pdf->MultiCell($this->posxdiscount-$this->posxqty-1,2, $outputlangs->transnoentities("Qty"),'','C');
			}
		}

		if($conf->global->PRODUCT_USE_UNITS) {
			$pdf->line($this->posxunit - 1, $tab_top, $this->posxunit - 1, $tab_top + $tab_height);
			if (empty($hidetop)) {
				$pdf->SetXY($this->posxunit - 1, $tab_top - 8.5);
				$pdf->MultiCell($this->posxdiscount - $this->posxunit - 1, 2, $outputlangs->transnoentities("U"), '', 'C');
			}
		}

		$pdf->line($this->posxdiscount-1, $tab_top, $this->posxdiscount-1, $tab_top + $tab_height);
		if (empty($hidetop))
		{
			if ($this->atleastonediscount)
			{
				$pdf->SetXY($this->posxdiscount-1, $tab_top - 8.5);
				$pdf->MultiCell($this->postotalht-$this->posxdiscount+1,2, $outputlangs->transnoentities("Redu"),'','C');
			}
		}

		if ($this->atleastonediscount)
		{
			$pdf->line($this->postotalht, $tab_top, $this->postotalht, $tab_top + $tab_height);
		}
		if (empty($hidetop))
		{
			$pdf->SetXY($this->postotalht-1, $tab_top - 8.5);
			$pdf->MultiCell(20, 2, $outputlangs->transnoentities("TotalHT"),'','C');
		}
	}

	/**
	 *  Show top header of page.
	 *
	 *  @param	PDF			$pdf     		Object PDF
	 *  @param  CommandeFournisseur		$object     	Object to show
	 *  @param  int	    	$showaddress    0=no, 1=yes
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @return	void
	 */
	function _pagehead(&$pdf, $object, $showaddress, $outputlangs)
	{
		global $langs,$conf,$mysoc;

		$outputlangs->load("main");
		$outputlangs->load("bills");
		$outputlangs->load("orders");
		$outputlangs->load("companies");
		$outputlangs->load("sendings");
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		pdf_pagehead($pdf,$outputlangs,$this->page_hauteur);


		$pdf->SetTextColor(0, 0, 60);
		$pdf->SetFont('','B',$default_font_size + 3);

        $w = 110;

		$posy=$this->marge_haute;
        $posx=$this->page_largeur-$this->marge_droite-$w;

		$pdf->SetXY($this->marge_gauche,$posy);

		// Logo
		$logo=$conf->mycompany->dir_output.'/logos/'.$this->emetteur->logo;
		if ($this->emetteur->logo)
		{
			if (is_readable($logo))
			{
//			    $height=pdf_getHeightForLogo($logo);
//			    $pdf->Image($logo, $this->marge_gauche, $posy, 0, $height);	// width=0 (auto)
                $pdf->Image($logo, 1, 0, 0, 46); // width=0 (auto)
			}
			else
			{
				$pdf->SetTextColor(200, 0, 0);
				$pdf->SetFont('','B', $default_font_size - 2);
				$pdf->MultiCell($w, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound", $logo), 0, 'L');
				$pdf->MultiCell($w, 3, $outputlangs->transnoentities("ErrorGoToModuleSetup"), 0, 'L');
			}
		}
		else
		{
			$text=$this->emetteur->name;
			$pdf->MultiCell($w, 4, $outputlangs->convToOutputCharset($text), 0, 'L');
		}

        // Sender properties
        $carac_emetteur = $this->pdf_build_details($outputlangs, $this->emetteur, $object->thirdparty);
        //echo'<pre>';var_dump($this->emetteur);die();
        // Show sender
        $posy           = 0;
        $posx           = $this->marge_gauche + 92;
        if (!empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx           = $this->page_largeur - $this->marge_droite - 80;

        $hautcadre   = !empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 38 : 40;
        $widthrecbox = !empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 92 : 82;


        // Show sender frame
        $pdf->SetTextColor(0, 0, 0);
//        $pdf->SetFont('', '', $default_font_size - 2);
//        $pdf->SetXY($posx, $posy - 5);
//        $pdf->MultiCell(66, 5, $outputlangs->transnoentities("BillFrom").":", 0, 'L');
        $pdf->SetXY($posx, $posy);
//            $pdf->SetFillColor(230, 230, 230);
//            $pdf->MultiCell($widthrecbox, $hautcadre, "", 0, 'R', 1);
        $pdf->SetTextColor(255, 255, 255);

        // Show sender name
        $pdf->SetXY($posx + 0, $posy + 2);
        $pdf->SetFont('', '', $default_font_size - 2);
        $nameAdress = $this->emetteur->name."\n".$this->emetteur->address."\n".$this->emetteur->zip." ".$this->emetteur->town."\n".$this->emetteur->country;
        $pdf->MultiCell($widthrecbox - 2, 4, $outputlangs->convToOutputCharset($nameAdress), 0, 'L');
        //$posy = $pdf->getY();
        // Show sender information
        $pdf->SetXY($posx + 36, $posy + 2);
        $pdf->SetFont('', '', $default_font_size - 2);
        $pdf->MultiCell($widthrecbox - 2, 4, $carac_emetteur, 0, 'L');

		if ($showaddress)
		{
            $bulletSize      = 1;
            $bulletWidth     = 6;
            $multiCellBorder = 0;

            // Open DSI - use contact delivery address -- Begin
            $use_contact_delivery = false;
            $array_idcontact_delivery = $object->getIdContact('external', 'SHIPPING');
            if (count($array_idcontact_delivery) > 0) {
                $use_contact_delivery = true;
                $w = intval(($this->page_largeur - ($this->marge_gauche + 7) - $this->marge_droite) / 3);
            }
            // Open DSI - use contact delivery address -- End

            $posx = $this->marge_gauche;
            $posy = $this->marge_haute;
            $pdf->SetFont('', 'B', $default_font_size+4);
            $pdf->SetXY($posx, $posy);
            //$pdf->SetTextColor(0, 0, 60);
            call_user_func_array(array($pdf, 'SetTextColor'), $this->main_color);
            $title = $outputlangs->transnoentities("SupplierOrder");
            $pdf->MultiCell($w, 5, $title, $multiCellBorder, 'L');

            $pdf->SetFont('', '', $default_font_size-2);

            $posy += 10;
            $pdf->SetXY($posx+$bulletWidth, $posy);
            $pdf->SetTextColor(0, 0, 60);
            $this->addBullet($pdf, $bulletSize);
            $pdf->MultiCell($w-$bulletWidth, 4, $outputlangs->transnoentities("Ref")." : ".$outputlangs->convToOutputCharset($object->ref), $multiCellBorder, 'L');

            if (! empty($object->date_commande))
            {
                $posy += 5;
                $pdf->SetXY($posx+$bulletWidth, $posy);
                $pdf->SetTextColor(0, 0, 60);
                $this->addBullet($pdf, $bulletSize);
                $pdf->MultiCell($w-$bulletWidth, 3, $outputlangs->transnoentities("OrderDate")." : " . dol_print_date($object->date_commande,"day",false,$outputlangs,true), $multiCellBorder, 'L');
            }
            else
            {
                $posy += 5;
                $pdf->SetXY($posx+$bulletWidth, $posy);
                $pdf->SetTextColor(255, 0, 0);
                $this->addBullet($pdf, $bulletSize);
                $pdf->MultiCell($w-$bulletWidth, 3, $outputlangs->transnoentities("OrderToProcess"), $multiCellBorder, 'L');
            }

            if (! empty($object->date_livraison))
            {
                $posy += 5;
                $pdf->SetXY($posx+$bulletWidth,$posy);
                $pdf->SetTextColor(0, 0, 60);
                $this->addBullet($pdf, $bulletSize);
                $pdf->MultiCell($w-$bulletWidth, 3, $outputlangs->transnoentities("DateDeliveryPlanned")." : " . dol_print_date($object->date_livraison, "day",false, $outputlangs, true), $multiCellBorder, 'L');
            }

            $posy += 1;
            if ($object->ref_supplier) {
                $posy += 4;
                $pdf->SetXY($posx+$bulletWidth,$posy);
                $pdf->SetTextColor(0, 0, 60);
                $this->addBullet($pdf, $bulletSize);
                $pdf->MultiCell($w-$bulletWidth, 3, $outputlangs->transnoentities("RefSupplier")." : " . $outputlangs->convToOutputCharset($object->ref_supplier), $multiCellBorder, 'L');
            }

            $posy+=1;

            // Show list of linked objects
            // $posy = pdf_writeLinkedObjects($pdf, $object, $outputlangs, $posx, $posy, 100, 3, 'R', $default_font_size);

            // Open DSI - use contact delivery address -- Begin
            if ($use_contact_delivery === true) {
                $result = $object->fetch_contact($array_idcontact_delivery[0]);
                // pour recuperer l'adresse de la societe du client (si
                $object->contact->fetch_thirdparty();
                $carac_client_name = $outputlangs->convToOutputCharset($object->contact->socname);
                $carac_destinataire = pdf_build_address($outputlangs, $this->emetteur, $object->contact->thirdparty, $object->contact, $use_contact_delivery, 'target');

                $posy = $this->marge_haute;
                $posx = $this->page_largeur - $this->marge_droite - 2*$w - 3.5;
                call_user_func_array(array($pdf, 'SetTextColor'), $this->main_color);
                $pdf->SetFont('', 'B', $default_font_size);
                $pdf->SetXY($posx, $posy);
                $pdf->MultiCell($w, 5, $outputlangs->transnoentities("RecipientAdress"), $multiCellBorder, 'L');
                $pdf->SetTextColor(0, 0, 0);

                $posy = $pdf->getY();

                // Show recipient name
                $pdf->SetXY($posx, $posy + 1);
                $pdf->SetFont('', 'B', $default_font_size-2);
                $pdf->MultiCell($w, 2, $carac_client_name, $multiCellBorder, 'L');

                $posy = $pdf->getY();

                // Show recipient information
                $pdf->SetFont('', '', $default_font_size-2);
                $pdf->SetXY($posx+$bulletWidth, $posy + 1);
                $this->addBullet($pdf, $bulletSize);
                $pdf->MultiCell($w-$bulletWidth, 4, $carac_destinataire, $multiCellBorder, 'L');
            }
            // Open DSI - use contact delivery address -- End

			// If BILLING contact defined on order, we use it
			$usecontact=false;
			$arrayidcontact=$object->getIdContact('external','BILLING');
			if (count($arrayidcontact) > 0)
			{
				$usecontact=true;
				$result=$object->fetch_contact($arrayidcontact[0]);
			}

            // Recipient name
            if (! empty($usecontact)) {
                // On peut utiliser le nom de la societe du contact
                if (! empty($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT))
                    $socname = $object->contact->socname;
                else
                    $socname = $object->thirdparty->name;
                $carac_client_name=$outputlangs->convToOutputCharset($socname);
            } else {
                $carac_client_name=$outputlangs->convToOutputCharset($object->thirdparty->name);
            }

			$carac_client = pdf_build_address($outputlangs,$this->emetteur,$object->thirdparty,($usecontact?$object->contact:''),$usecontact,'target');

            // Show recipient
            if ($use_contact_delivery === FALSE) {
                $widthrecbox = !empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 92 : 100;
                if ($this->page_largeur < 210) $widthrecbox = 84; // To work with US executive format
                $widthrecbox -= 20;
            } else {
                $widthrecbox = $w;
            }

            $posy = $this->marge_haute;
            $posx = $this->page_largeur - $this->marge_droite - $widthrecbox;
            if (!empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx = $this->marge_gauche;

            // Show recipient frame
            call_user_func_array(array($pdf, 'SetTextColor'), $this->main_color);
            $pdf->SetFont('', 'B', $default_font_size);
            $pdf->SetXY($posx, $posy);
            $pdf->MultiCell($widthrecbox, 5, $outputlangs->transnoentities("sentToMAJ"), $multiCellBorder, 'L');
            //$pdf->Rect($posx, $posy, $widthrecbox, $hautcadre);
            $pdf->SetTextColor(0, 0, 0);

            $posy = $pdf->getY();

            // Show recipient name
            $pdf->SetXY($posx, $posy + 1);
            $pdf->SetFont('', 'B', $default_font_size-2);
            $pdf->MultiCell($widthrecbox, 2, $carac_client_name, $multiCellBorder, 'L');

            $posy = $pdf->getY();

            // Show recipient information
            $pdf->SetFont('', '', $default_font_size-2);
            $pdf->SetXY($posx+$bulletWidth, $posy + 1);
            $this->addBullet($pdf, $bulletSize);
            $pdf->MultiCell($widthrecbox-$bulletWidth, 4, $carac_client, $multiCellBorder, 'L');

            $posy = $pdf->getY();

            // Show recipient email
            $pdf->SetFont('', '', $default_font_size-2);
            $pdf->SetXY($posx+$bulletWidth, $posy);
            $this->addBullet($pdf, $bulletSize);
            $pdf->MultiCell($widthrecbox-$bulletWidth, 4, (($usecontact) ? $object->contact->email : $object->thirdparty->email), $multiCellBorder, 'L');
		}

        $pdf->SetFont('', '', $default_font_size - 1);
        $pdf->SetTextColor(0, 0, 0);
	}

	/**
	 *   	Show footer of page. Need this->emetteur object
     *
	 *   	@param	PDF			$pdf     			PDF
	 * 		@param	CommandeFournisseur		$object				Object to show
	 *      @param	Translate	$outputlangs		Object lang for output
	 *      @param	int			$hidefreetext		1=Hide free text
	 *      @return	int								Return height of bottom margin including footer text
	 */
	function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext=0)
	{
		global $conf;
		$showdetails=$conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS;
		return $this->pdf_pagefoot($pdf,$outputlangs,'SUPPLIER_ORDER_FREE_TEXT',$this->emetteur,$this->marge_basse,$this->marge_gauche,$this->page_hauteur,$object,$showdetails,$hidefreetext);
	}

}
