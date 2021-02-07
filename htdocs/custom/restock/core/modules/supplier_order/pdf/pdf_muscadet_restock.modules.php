<?php
/* Copyright (C) 2004-2013 	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2007	  	Franky Van Liedekerke	<franky.van.liedekerke@telenet.be>
 * Copyright (C) 2010-2013 	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2016-2017	Charlie Benke			<charlie@patas-monkey.com>
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
 *	\file	   htdocs/core/modules/supplier_order/pdf/pdf_dispatch_restock.modules.php
 *	\ingroup	fournisseur
 *	\brief	  File of class to generate suppliers orders from muscadet model
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/supplier_order/modules_commandefournisseur.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';


/**
 *	Class to generate the supplier orders with the muscadet model
 */
class pdf_dispatch_restock extends ModelePDFSuppliersOrders
{
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

	var $emetteur;	// Objet societe qui emet


	/**
	 *	Constructor
	 *
	 *  @param	DoliDB		$db	  	Database handler
	 *  @param	Object		$object		Supplier order
	 */
	function __construct($db)
	{
		global $conf, $langs, $mysoc;

		$langs->load("main");
		$langs->load("bills");

		$this->db = $db;
		$this->name = "dispatch_restock";
		$this->description = $langs->trans('DispatchOrderModel');

		// Dimension page pour format A4
		$this->type = 'pdf';
		$formatarray=pdf_getFormat();
		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = $formatarray['height'];
		$this->format = array($this->page_largeur, $this->page_hauteur);
		$this->marge_gauche=isset($conf->global->MAIN_PDF_MARGIN_LEFT)?$conf->global->MAIN_PDF_MARGIN_LEFT:10;
		$this->marge_droite=isset($conf->global->MAIN_PDF_MARGIN_RIGHT)?$conf->global->MAIN_PDF_MARGIN_RIGHT:10;
		$this->marge_haute =isset($conf->global->MAIN_PDF_MARGIN_TOP)?$conf->global->MAIN_PDF_MARGIN_TOP:10;
		$this->marge_basse =isset($conf->global->MAIN_PDF_MARGIN_BOTTOM)?$conf->global->MAIN_PDF_MARGIN_BOTTOM:10;

		$this->option_logo = 1;					// Affiche logo
		$this->option_tva = 1;					// Gere option tva FACTURE_TVAOPTION
		$this->option_modereg = 1;				// Affiche mode reglement
		$this->option_condreg = 1;				// Affiche conditions reglement
		$this->option_codeproduitservice = 1;	// Affiche code produit-service
		$this->option_multilang = 1;			// Dispo en plusieurs langues
		$this->option_escompte = 0;				// Affiche si il y a eu escompte
		$this->option_credit_note = 0;			// Support credit notes
		$this->option_freetext = 1;				// Support add of a personalised text
		$this->option_draft_watermark = 1;		// Support add of a watermark on drafts

		$this->franchise=!$mysoc->tva_assuj;

		// Get source company
		$this->emetteur=$mysoc;
		// By default, if was not defined
		if (empty($this->emetteur->country_code))
			$this->emetteur->country_code=substr($langs->defaultlang, -2);

		// Defini position des colonnes
		$this->posxcustomerinfo=$this->marge_gauche+1;
		$this->posxorderinfo=80;
		$this->posxproductinfo=110;
		$this->posxqty=180;
		$this->posxdispatch=190;

	}


	/**
	 *  Function to build pdf onto disk
	 *
	 *  @param		int		$object				Id of object to generate
	 *  @param		object	$outputlangs		Lang output object
	 *  @param		string	$srctemplatepath	Full path of source filename for generator using a template file
	 *  @param		int		$hidedetails		Do not show line details
	 *  @param		int		$hidedesc			Do not show desc
	 *  @param		int		$hideref			Do not show ref
	 *  @return	 int			 			1=OK, 0=KO
	 */
	function write_file($object, $outputlangs='', $srctemplatepath='', $hidedetails=0, $hidedesc=0, $hideref=0)
	{
		global $user, $langs, $conf, $hookmanager;

		if (! is_object($outputlangs)) $outputlangs=$langs;
		// For backward compatibility with FPDF, force output charset to ISO,
		// because FPDF expect text to be encoded in ISO
		if (! empty($conf->global->MAIN_USE_FPDF))
				$outputlangs->charset_output='ISO-8859-1';

		$outputlangs->load("main");
		$outputlangs->load("dict");
		$outputlangs->load("companies");
		$outputlangs->load("bills");
		$outputlangs->load("products");
		$outputlangs->load("orders");

		if ($conf->fournisseur->dir_output.'/commande') {
			$object->fetch_thirdparty();

			$deja_regle = "";
			$amount_credit_notes_included = 0;
			$amount_deposits_included = 0;
			//$amount_credit_notes_included = $object->getSumCreditNotesUsed();
			//$amount_deposits_included = $object->getSumDepositsUsed();

			// Definition of $dir and $file
			if ($object->specimen) {
				$dir = $conf->fournisseur->commande->dir_output;
				$file = $dir . "/SPECIMEN.pdf";
			} else {
				$objectref = dol_sanitizeFileName($object->ref);
				$dir = $conf->fournisseur->commande->dir_output . '/'. $objectref;
				$file = $dir . "/" . $objectref . ".pdf";
			}

			if (! file_exists($dir)) {
				if (dol_mkdir($dir) < 0) {
					$this->error=$langs->transnoentities("ErrorCanNotCreateDir", $dir);
					return 0;
				}
			}

			if (file_exists($dir)) {
				$nblignes = count($object->lines);

				$pdf=pdf_getInstance($this->format);
				$default_font_size = pdf_getPDFFontSize($outputlangs);	// Must be after pdf_getInstance
				$heightforinfotot = 50;	// Height reserved to output the info and total part
				// Height reserved to output the free text on last page
				$heightforfreetext= (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT)?$conf->global->MAIN_PDF_FREETEXT_HEIGHT:5);
				// Height reserved to output the footer (value include bottom margin)
				$heightforfooter = $this->marge_basse + 8;
				$pdf->SetAutoPageBreak(1,0);

				if (class_exists('TCPDF')) {
					$pdf->setPrintHeader(false);
					$pdf->setPrintFooter(false);
				}
				$pdf->SetFont(pdf_getPDFFont($outputlangs));
				// Set path to the background PDF File
				if (empty($conf->global->MAIN_DISABLE_FPDI) && ! empty($conf->global->MAIN_ADD_PDF_BACKGROUND)) {
					$pagecount = $pdf->setSourceFile($conf->mycompany->dir_output.'/'.$conf->global->MAIN_ADD_PDF_BACKGROUND);
					$tplidx = $pdf->importPage(1);
				}

				$pdf->Open();
				$pagenb=0;
				$pdf->SetDrawColor(128, 128, 128);

				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities("Order"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("Order"));
				if (! empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) $pdf->SetCompression(false);

				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right

				// New page
				$pdf->AddPage();
				if (! empty($tplidx)) $pdf->useTemplate($tplidx);
				$pagenb++;
				$this->_pagehead($pdf, $object, 1, $outputlangs);
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->MultiCell(0, 3, '');		// Set interline to 3
				$pdf->SetTextColor(0, 0, 0);

				$tab_top = 90;
				$tab_top_newpage = (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)?42:10);
				$tab_height = 130;
				$tab_height_newpage = 150;

				// Affiche notes
				if (! empty($object->note_public)) {
					$tab_top = 88;

					$pdf->SetFont('', '', $default_font_size - 1);
					$pdf->writeHTMLCell(190, 3, $this->posxdesc-1, $tab_top, dol_htmlentitiesbr($object->note_public), 0, 1);
					$nexY = $pdf->GetY();
					$height_note=$nexY-$tab_top;

					// Rect prend une longueur en 3eme param
					$pdf->SetDrawColor(192, 192, 192);
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

				// requete pour ordonner le dispatch et rcuprer les infos
				// c'est du lourd de chez lourd
				$sql = "SELECT c.fk_soc, cd.fk_commande, cd.fk_product, pfp.ref_fourn, p.ref, p.label, cd.qty";
				$sql.= ' FROM '.MAIN_DB_PREFIX.'product as p';
				$sql.= ' , '.MAIN_DB_PREFIX.'commande as c';
				$sql.= ' , '.MAIN_DB_PREFIX.'commandedet as cd';
				$sql.= ' , '.MAIN_DB_PREFIX.'commande_fournisseur  as cf';
				$sql.= ' , '.MAIN_DB_PREFIX.'commande_fournisseurdet as cfd';
				$sql.= ' , '.MAIN_DB_PREFIX.'product_fournisseur_price as pfp';
				$sql.= ' WHERE cf.rowid='.$object->id;
				$sql.= ' AND cf.rowid=cfd.fk_commande';
				$sql.= ' AND cfd.rowid=cd.fk_commandefourndet';
				$sql.= ' AND c.rowid=cd.fk_commande';
				$sql.= ' AND cfd.fk_product=p.rowid';
				$sql.= ' AND p.rowid=pfp.fk_product';
				$sql.= ' AND pfp.fk_soc='.$object->thirdparty->id; // l'id du fourn
				$sql.= ' ORDER BY c.fk_soc, cd.fk_commande, cd.fk_product';

				$resql = $this->db->query($sql);
				if ($resql) {
					$i=0;
					$num = $this->db->num_rows($resql);
					$socid=0;
					$orderid=0;
					$curYsoc=$curY;

					$objphoto = new Product($this->db);

					while ($i < $num) {
						$objp = $this->db->fetch_object($resql);
						if ($objp->fk_soc != $socid) {
							// si on a dpass la position du prochain
							if ($curYsoc < $curY)
								$curYsoc = $curY;

							// ligne au dessus
							// line prend une position y en 2eme param et 4eme param
							$pdf->line($this->marge_gauche, $curYsoc, $this->page_largeur-$this->marge_droite, $curYsoc);

							// Sender properties
							$soccli= new Societe($this->db);
							$soccli->fetch($objp->fk_soc);
							$carac_clientdest = pdf_build_address($outputlangs, $soccli);

							$orderid=0;		// on remet le compteur commande  0
							$pdf->SetXY($this->posxcustomerinfo, $curYsoc);
							$pdf->SetFont('', 'B', $default_font_size);
							$pdf->MultiCell(60, 4, $outputlangs->convToOutputCharset($soccli->name), 0, 'L');
							$posy=$pdf->getY();
							$pdf->SetFont('', '', $default_font_size);

							$pdf->SetXY($this->posxcustomerinfo, $curYsoc+4);
							$pdf->MultiCell($this->posxorderinfo-1, 3, $carac_clientdest, 0, 'L');
							$curYsoc = $pdf->GetY();
							$socid=$objp->fk_soc;
						}

						if ($objp->fk_commande != $orderid) {
							// Sender properties
							$cmdecli= new Commande($this->db);
							$cmdecli->fetch($objp->fk_commande);
							$orderid=$objp->fk_commande;

							// ligne au dessus
							$pdf->line($this->posxorderinfo -1, $curY, $this->page_largeur-$this->marge_droite, $curY);
							// line prend une position y en 2eme param et 4eme param

							$pdf->SetXY($this->posxorderinfo, $curY);
							$pdf->MultiCell($this->posxorderinfo-$this->posxcustomerinfo-1, 3, $cmdecli->ref, 0, 'L');
						}

						$objphoto->fetch($objp->fk_product);
						if (! empty($conf->global->PRODUCT_USE_OLD_PATH_FOR_PHOTO)) {
							$pdir[0] = get_exdir($objphoto->id, 2, 0, 0, $objphoto, 'product').$objphoto->id."/photos/";
							$pdir[1] = get_exdir(0, 0, 0, 0, $objphoto, 'product').dol_sanitizeFileName($objphoto->ref).'/';
						} else {
							$pdir[0] = get_exdir(0, 0, 0, 0, $objphoto, 'product').dol_sanitizeFileName($objphoto->ref).'/';
							$pdir[1] = get_exdir($objphoto->id, 2, 0, 0, $objphoto, 'product').$objphoto->id ."/photos/";
						}

						foreach ($pdir as $midir) {
							if (! $arephoto) {
								$dir = $conf->product->dir_output.'/'.$midir;
								foreach ($objphoto->liste_photos($dir,1) as $key => $obj) {
									// If CAT_HIGH_QUALITY_IMAGES not defined,
									// we use thumb if defined and then original photo
									if (empty($conf->global->CAT_HIGH_QUALITY_IMAGES)) {
										if ($obj['photo_vignette'])
											$filename= $obj['photo_vignette'];
										else
											$filename=$obj['photo'];
									} else
										$filename=$obj['photo'];

									$realpath = $dir.$filename;
									$arephoto = true;
								}
							}
						}
						if ($realpath && $arephoto)
							$realpatharray[$i]=$realpath;
						$barcode = $objphoto->barcode;
						$barcodearray[$i]=$barcode;

						$style = array(
							'position' => '',
							'align' => 'C',
							'stretch' => false,
							'fitwidth' => true,
							'cellfitalign' => '',
							'border' => true,
							'hpadding' => 'auto',
							'vpadding' => 'auto',
							'fgcolor' => array(0 ,0, 0),
							'bgcolor' => false, //array(255,255,255),
							'text' => true,
							'font' => 'helvetica',
							'fontsize' => 8,
							'stretchtext' => 4
						);
						//$curY2 = $curY-2;
						if ($i == 0)
							$curY2 = $curY-2;
						else
							$curY2 = $curY-1;

						$posiTX = $this->posxpicture-1;

						$pdf->write1DBarcode($barcodearray[$i], 'EAN13', $posiTX, $curY2, 20, 8, 0.4, $style, 'N');

						$pdf->SetXY($this->posxproductinfo, $curY);
						$infoprod = html_entity_decode($objp->ref_fourn . " - ". $objp->label);
						$pdf->MultiCell($this->posxqty-$this->posxproductinfo-1, 3, $infoprod, 0, 'L');

						$pdf->SetXY($this->posxqty, $curY);
						$pdf->MultiCell($this->posxdispatch-$this->posxqty-1, 3, $objp->qty, 0, 'R');
						$curY = $pdf->GetY();
						$i++;
					}
				}


				// Show square
				if ($pagenb == 1)
					$this->_tableau(
									$pdf, $tab_top,
									$this->page_hauteur - $tab_top - $heightforinfotot - $heightforfreetext - $heightforfooter,
									0, $outputlangs, 0, 0
					);
				else
					$this->_tableau(
									$pdf, $tab_top_newpage,
									$this->page_hauteur - $tab_top_newpage - $heightforinfotot - $heightforfreetext - $heightforfooter,
									0, $outputlangs, 1, 0
					);

				$bottomlasttab=$this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;

				// Pied de page
				$this->_pagefoot($pdf, $object, $outputlangs);
				if (method_exists($pdf, 'AliasNbPages'))
					$pdf->AliasNbPages();

				$pdf->Close();
				$pdf->Output($file, 'F');


				// Add pdfgeneration hook
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters=array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
				global $action;
				// Note that $action and $object may have been modified by some hooks
				$reshook=$hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action);

				if (! empty($conf->global->MAIN_UMASK))
					@chmod($file, octdec($conf->global->MAIN_UMASK));

				return 1;   // Pas d'erreur
			} else {
				$this->error=$langs->trans("ErrorCanNotCreateDir", $dir);
				return 0;
			}
		} else {
			$this->error=$langs->trans("ErrorConstantNotDefined","SUPPLIER_OUTPUTDIR");
			return 0;
		}
		$this->error=$langs->trans("ErrorUnknown");
		return 0;   // Erreur par defaut
	}

	/**
	 *   Show table for lines
	 *
	 *   @param		PDF			&$pdf	 		Object PDF
	 *   @param		string		$tab_top		Top position of table
	 *   @param		string		$tab_height		Height of table (rectangle)
	 *   @param		int			$nexY			Y (not used)
	 *   @param		Translate	$outputlangs	Langs object
	 *   @param		int			$hidetop		Hide top bar of array
	 *   @param		int			$hidebottom		Hide bottom bar of array
	 *   @return	void
	 */
	function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop=0, $hidebottom=0)
	{
		global $conf;

		// Force to disable hidetop and hidebottom
		$hidebottom=0;
		if ($hidetop) $hidetop=-1;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		// Amount in (at tab_top - 1)

		$pdf->SetDrawColor(128, 128, 128);
		$pdf->SetFont('', '', $default_font_size - 1);

		// Output Rect prend une longueur en 3eme param et 4eme param
		$this->printRect(
						$pdf, $this->marge_gauche, $tab_top,
						$this->page_largeur-$this->marge_gauche-$this->marge_droite,
						$tab_height, $hidetop, $hidebottom
		);

		if (empty($hidetop)) {
			// line prend une position y en 2eme param et 4eme param
//			$pdf->line($this->marge_gauche, $tab_top+5, $this->page_largeur-$this->marge_droite, $tab_top+5);

			$pdf->SetXY($this->posxcustomerinfo-1, $tab_top+1);
			$pdf->MultiCell(100, 2, $outputlangs->transnoentities("CustomerInfo"), '', 'L');

			$pdf->line($this->posxorderinfo-1, $tab_top, $this->posxorderinfo-1, $tab_top + $tab_height);
			$pdf->SetXY($this->posxorderinfo-1, $tab_top+1);
			$pdf->MultiCell(
							$this->posxorderinfo-$this->posxcustomerinfo+3, 2,
							$outputlangs->transnoentities("OrderInfo"), '', 'L'
			);

			$pdf->line($this->posxproductinfo-1, $tab_top, $this->posxproductinfo-1, $tab_top + $tab_height);
			$pdf->SetXY($this->posxproductinfo-1, $tab_top+1);
			$pdf->MultiCell(
							$this->posxproductinfo-$this->posxorderinfo-1, 2,
							$outputlangs->transnoentities("ProductInfo"), '', 'L'
			);

			$pdf->line($this->posxqty-1, $tab_top, $this->posxqty-1, $tab_top + $tab_height);
			$pdf->SetXY($this->posxqty-1, $tab_top+1);
			$pdf->MultiCell(
							$this->posxdispatch-$this->posxqty-1, 2,
							$outputlangs->transnoentities("Qty"), '', 'C'
			);

			$pdf->line($this->posxdispatch-1, $tab_top, $this->posxdispatch-1, $tab_top + $tab_height);
			$pdf->SetXY($this->posxdispatch-1, $tab_top+1);

			$pdf->MultiCell(
							$this->posxdispatch-$this->posxqty+1, 2,
							$outputlangs->transnoentities("Disp"), '', 'C'
			);
		}
	}

	/**
	 *  Show top header of page.
	 *
	 *  @param	PDF			&$pdf	 		Object PDF
	 *  @param  Object		$object	 	Object to show
	 *  @param  int			$showaddress	0=no, 1=yes
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @return	void
	 */
	function _pagehead(&$pdf, $object, $showaddress, $outputlangs)
	{
		global $langs, $conf, $mysoc;

		$outputlangs->load("main");
		$outputlangs->load("bills");
		$outputlangs->load("orders");
		$outputlangs->load("companies");
		$outputlangs->load("restock@restock");

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		// Do not add the BACKGROUND as this is for suppliers
		//pdf_pagehead($pdf, $outputlangs, $this->page_hauteur);

		//Affiche le filigrane brouillon - Print Draft Watermark
		/*if ($object->statut==0 && (! empty($conf->global->COMMANDE_DRAFT_WATERMARK)) )
			pdf_watermark($pdf, $outputlangs, $this->page_hauteur, $this->page_largeur,'mm', $conf->global->COMMANDE_DRAFT_WATERMARK);
		*/
		//Print content

		$pdf->SetTextColor(0, 0, 60);
		$pdf->SetFont('', 'B', $default_font_size + 3);

		$posx=$this->page_largeur-$this->marge_droite-100;
		$posy=$this->marge_haute;

		$pdf->SetXY($this->marge_gauche, $posy);

		// Logo
		$logo=$conf->mycompany->dir_output.'/logos/'.$this->emetteur->logo;
		if ($this->emetteur->logo) {
			if (is_readable($logo)) {
				$height=pdf_getHeightForLogo($logo);
				$pdf->Image($logo, $this->marge_gauche, $posy, 0, $height);	// width=0 (auto)
			} else {
				$pdf->SetTextColor(200, 0, 0);
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound", $logo), 0, 'L');
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorGoToModuleSetup"), 0, 'L');
			}
		} else {
			$text=$this->emetteur->name;
			$pdf->MultiCell(100, 4, $outputlangs->convToOutputCharset($text), 0, 'L');
		}

		$pdf->SetFont('', 'B', $default_font_size + 3);
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0,0,60);
		$title=$outputlangs->transnoentities("DispatchFournish");
		$pdf->MultiCell(100, 3, $title, '', 'R');

		$pdf->SetFont('', 'B', $default_font_size);

		$posy+=6;
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$pdf->MultiCell(
						100, 4,
						$outputlangs->transnoentities("Ref")." : " . $outputlangs->convToOutputCharset($object->ref),
						'', 'R'
		);

		if ($object->ref_supplier) {
			$posy+=4;
			$pdf->SetXY($posx, $posy);
			$pdf->SetTextColor(0, 0, 60);
			$textpdf = $outputlangs->transnoentities("RefSupplier")." : ";
			$textpdf.= $outputlangs->convToOutputCharset($object->ref_supplier);
			$pdf->MultiCell( 100, 3, $textpdf, '', 'R' );
		}

		$posy+=2;
		$pdf->SetFont('','', $default_font_size -1);

		$posy+=5;
		$pdf->SetXY($posx, $posy);
		if ($object->date_commande) {
			$pdf->SetTextColor(0, 0, 60);
			$textpdf=$outputlangs->transnoentities("OrderDate")." : ".dol_print_date(
							$object->date_commande, "day", false, $outputlangs, true
			);
			$pdf->MultiCell(100, 3, $textpdf, '', 'R');
			$textpdf = $outputlangs->transnoentities("DateDeliveryPlanned")." : ".dol_print_date(
							$object->date_livraison, "day", false, $outputlangs, true
			);
			$pdf->MultiCell(190, 3, $textpdf, '', 'R');
		} else {
			$pdf->SetTextColor(255, 0, 0);
			$pdf->MultiCell(100, 3, strtolower($outputlangs->transnoentities("OrderToProcess")), '', 'R');
		}

		$posy+=2;
		$pdf->SetTextColor(0,0,60);

		// pas utile : on a les infos dans le dispatch
//		$posy = pdf_writeLinkedObjects($pdf, $object, $outputlangs, $posx, $posy, 100, 3, 'R', $default_font_size);

		if ($showaddress) {
			// Sender properties
			$carac_emetteur = pdf_build_address($outputlangs, $this->emetteur);

			// Show sender
			$posy=42;
			$posx=$this->marge_gauche;
			if (! empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT))
				$posx=$this->page_largeur-$this->marge_droite-80;
			$hautcadre=40;

			// Show sender frame
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('','', $default_font_size - 2);
			$pdf->SetXY($posx, $posy-5);
			$pdf->MultiCell(66, 5, $outputlangs->transnoentities("BillFrom").":", 0, 'L');
			$pdf->SetXY($posx, $posy);
			$pdf->SetFillColor(240, 240, 240);
			$pdf->MultiCell(62, $hautcadre, "", 0, 'R', 1);
			$pdf->SetTextColor(0,0,60);

			// Show sender name
			$pdf->SetXY($posx+2, $posy+3);
			$pdf->SetFont('', 'B', $default_font_size);
			$pdf->MultiCell(60, 4, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');
			$posy=$pdf->getY();

			// Show sender information
			$pdf->SetXY($posx+2, $posy);
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->MultiCell(60, 4, $carac_emetteur, 0, 'L');


			// If BILLING contact defined on order, we use it
			$usecontact=false;
			$arrayidcontact=$object->getIdContact('external', 'BILLING');
			if (count($arrayidcontact) > 0) {
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
			} else
				$carac_client_name=$outputlangs->convToOutputCharset($object->thirdparty->name);

			$carac_client=pdf_build_address(
							$outputlangs, $this->emetteur,
							$object->thirdparty, ($usecontact?$object->contact:''),
							$usecontact, 'target'
			);

			// Show recipient
			$widthrecbox=60;
			if ($this->page_largeur < 210)
				$widthrecbox=84;	// To work with US executive format
			$posy=42;
			$posx=$this->page_largeur-$this->marge_droite-$widthrecbox;
			if (! empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx=$this->marge_gauche;

			// Show recipient frame
			$pdf->SetTextColor(0, 0, 0);
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetXY($posx+2, $posy-5);
			$pdf->MultiCell($widthrecbox, 5, $outputlangs->transnoentities("BillTo").":", 0, 'L');
			$pdf->Rect($posx, $posy, $widthrecbox, $hautcadre);

			// Show recipient name
			$pdf->SetXY($posx+2, $posy+3);
			$pdf->SetFont('', 'B', $default_font_size);
			$pdf->MultiCell($widthrecbox, 4, $carac_client_name, 0, 'L');

			// Show recipient information
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetXY($posx+2, $posy+4+(dol_nboflines_bis($carac_client_name, 50)*4));
			$pdf->MultiCell($widthrecbox, 4, $carac_client, 0, 'L');


			// adresse du client final destinataire
			$posy=42;
			$posx=75;
			$hautcadre=40;

			$usecontact=false;
			$arrayidcontact=$object->getIdContact('external', 'SHIPPING');
			if (count($arrayidcontact) > 0) {
				$usecontact=true;
				$result=$object->fetch_contact($arrayidcontact[0]);
				// pour rcuprer l'adresse de la socit du client (si
				$object->contact->fetch_thirdparty();
				$carac_client_name=$outputlangs->convToOutputCharset($object->contact->socname);
				$carac_destinataire=pdf_build_address(
								$outputlangs, $this->emetteur, $object->contact->thirdparty,
								$object->contact, $usecontact, 'target'
				);

				// Show RECEPT frame
				$pdf->SetTextColor(0, 0, 0);
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->SetXY($posx, $posy-5);
				$pdf->MultiCell(66, 5, $outputlangs->transnoentities("RecipientAdress").":", 0, 'L');
				$pdf->SetXY($posx, $posy);
				$pdf->SetFillColor(200, 200, 200);
				$pdf->MultiCell(62, $hautcadre, "", 0, 'R', 1);
				$pdf->SetTextColor(0, 0, 60);

				// Show RECEPT name
				$pdf->SetXY($posx+2, $posy+3);
				$pdf->SetFont('', 'B', $default_font_size);
				$pdf->MultiCell(60, 4, $outputlangs->convToOutputCharset($carac_client_name), 0, 'L');
				$posy=$pdf->getY();

				// Show RECEPT information
				$pdf->SetXY($posx+2, $posy);
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->MultiCell(60, 4, $carac_destinataire, 0, 'L');
			}
		}
	}

	/**
	 *   	Show footer of page. Need this->emetteur object
	 *
	 *   	@param	PDF			&$pdf	 			PDF
	 * 		@param	Object		$object				Object to show
	 *	  @param	Translate	$outputlangs		Object lang for output
	 *	  @param	int			$hidefreetext		1=Hide free text
	 *	  @return	int								Return height of bottom margin including footer text
	 */
	function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext=0)
	{
		return pdf_pagefoot(
						$pdf, $outputlangs,'SUPPLIER_INVOICE_FREE_TEXT',
						$this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur,
						$object, 0, $hidefreetext
		);
	}
}
