<?php
/* Copyright (C) 2014-2017		Charlene Benke	<charlie@patas-monkey.com>
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
 *	\file	   htdocs/factory/core/modules/factory/doc/pdf_capucin.modules.php
 *	\ingroup	factory
 *	\brief	  Fichier de la classe permettant de generer les fiches d'OF de factory
 */
dol_include_once("/factory/core/modules/factory/modules_factory.php");

require_once DOL_DOCUMENT_ROOT."/product/class/product.class.php";
require_once DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php";
require_once DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.facture.class.php";
require_once DOL_DOCUMENT_ROOT."/product/stock/class/entrepot.class.php";

require_once DOL_DOCUMENT_ROOT."/core/lib/company.lib.php";
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';


/**
 *	Class to build equipement documents with model Soleil
 */
class pdf_capucin extends ModelePDFFactory
{
	var $db;
	var $name;
	var $description;
	var $type;

	var $phpmin = array(4, 3, 0); // Minimum version of PHP required by module
	var $version = '3.9.+1.4.0';

	var $page_largeur;
	var $page_hauteur;
	var $format;
	var $marge_gauche;
	var	$marge_droite;
	var	$marge_haute;
	var	$marge_basse;

	var $posqtyplanned;
	var $posqtyused;
	var $posqtydeleted;

	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db	  Database handler
	 */
	function __construct($db)
	{
		global $conf, $langs, $mysoc;

		$this->db = $db;
		$this->name = 'capucin';
		$this->description = $langs->trans("DocumentModelStandardFactory");

		// Dimension page pour format A4
		$this->type = 'pdf';
		$formatarray=pdf_getFormat();
		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = $formatarray['height'];
		$this->format = array($this->page_largeur, $this->page_hauteur);
		$this->marge_gauche=10;
		$this->marge_droite=10;
		$this->marge_haute=10;
		$this->marge_basse=10;
		$this->posqtyplanned=125;
		$this->posqtyused=150;
		$this->posqtydeleted=175;

		$this->option_logo = 1;					// Affiche logo
		$this->option_tva = 0;					 // Gere option tva FACTURE_TVAOPTION
		$this->option_modereg = 0;				 // Affiche mode reglement
		$this->option_condreg = 0;				 // Affiche conditions reglement
		$this->option_codeproduitservice = 0;	  // Affiche code produit-service
		$this->option_multilang = 0;			   // Dispo en plusieurs langues
		$this->option_draft_watermark = 1;		   //Support add of a watermark on drafts


		// Recupere emmetteur
		$this->emetteur=$mysoc;
		if (! $this->emetteur->code_pays) // By default, if not defined
			$this->emetteur->code_pays=substr($langs->defaultlang, -2);

		// Defini position des colonnes
		$this->posxdesc=$this->marge_gauche+1;
	}

	/**
	 *  Function to build pdf onto disk
	 *
	 *  @param		object	$object				Object to generate
	 *  @param		object	$outputlangs		Lang output object
	 *  @return		int							1=ok, 0=ko
	 */
	function write_file($object, $outputlangs)
	{
		global $user, $langs, $conf, $mysoc;
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		if (! is_object($outputlangs))
			$outputlangs=$langs;
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (! empty($conf->global->MAIN_USE_FPDF))
			$outputlangs->charset_output='ISO-8859-1';

		$outputlangs->load("main");
		$outputlangs->load("dict");
		$outputlangs->load("companies");
		$outputlangs->load("factory@factory");

		if ($conf->factory->dir_output) {
			$object->fetch_thirdparty();

			$objectref = dol_sanitizeFileName($object->ref);
			$dir = $conf->factory->dir_output;
			if (! preg_match('/specimen/i', $objectref))
				$dir.= "/".$objectref;
			$file = $dir."/".$objectref.".pdf";

			if (! file_exists($dir)) {
				if (dol_mkdir($dir) < 0) {
					$this->error=$outputlangs->trans("ErrorCanNotCreateDir", $dir);
					return 0;
				}
			}

			if (file_exists($dir)) {
				$pdf=pdf_getInstance($this->format);
				if (class_exists('TCPDF')) {
					$pdf->setPrintHeader(false);
					$pdf->setPrintFooter(false);
				}
				$pdf->SetFont(pdf_getPDFFont($outputlangs));
				// Set path to the background PDF File
				if (empty($conf->global->MAIN_DISABLE_FPDI)
					&& ! empty($conf->global->MAIN_ADD_PDF_BACKGROUND)) {
					$pagecount = $pdf->setSourceFile($conf->mycompany->dir_output.'/'.$conf->global->MAIN_ADD_PDF_BACKGROUND);
					$tplidx = $pdf->importPage(1);
				}

				$pdf->Open();
				$pagenb=0;
				$pdf->SetDrawColor(128, 128, 128);

				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities("FactoryCard"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("FactoryCard"));
				if ($conf->global->MAIN_DISABLE_PDF_COMPRESSION)
					$pdf->SetCompression(false);

				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);
				// Left, Top, Right
				$pdf->SetAutoPageBreak(1, 0);

				// New page
				$pdf->AddPage();
				if (! empty($tplidx)) $pdf->useTemplate($tplidx);
				$pagenb++;
				$this->_pagehead($pdf, $object, 1, $outputlangs);
				$pdf->SetTextColor(0, 0, 0);
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->MultiCell(0, 3, '');		// Set interline to 3

				$tab_top = 90;
				$tab_top_middlepage = 50;
				$tab_top_newpage = 50;
				$tab_height = 180;
				$tab_height_newpage = 180;
				$tab_height_middlepage = 180;
				$tab_height_endpage = 180;

				// Affiche notes
				if (! empty($object->note_public)) {
					$tab_top = 88;

					$pdf->SetFont('', '', $default_font_size - 1);   // Dans boucle pour gerer multi-page
					$pdf->writeHTMLCell(190, 3, $this->posxdesc-1, $tab_top, dol_htmlentitiesbr($object->note_public), 0, 1);
					$nexY = $pdf->GetY();
					$height_note = $nexY-$tab_top;

					// Rect prend une longueur en 3eme param
					$pdf->SetDrawColor(192, 192, 192);
					$pdf->Rect(
									$this->marge_gauche, $tab_top-1,
									$this->page_largeur-$this->marge_gauche-$this->marge_droite, $height_note+1
					);

					$tab_height = $tab_height - $height_note;
					$tab_top = $nexY+6;
				} else
					$height_note=0;

				$iniY = $tab_top + 7;
				$curY = $tab_top + 7;
				$nexY = $tab_top + 3;


				$pdf->MultiCell(0, 2, '');		// Set interline to 3. Then writeMultiCell must use 3 also.

				$prods_arbo =$object->getChildsOF($object->id);
				$nblines = count($prods_arbo);

				// Loop on each lines
				for ($i = 0; $i < $nblines; $i++) {
					$objectligne = $prods_arbo[$i];
					$curY = $nexY+3;

					// quantit� unitaire
					$pdf->SetXY($this->marge_gauche, $curY);
					$txt=dol_htmlentitiesbr($objectligne['nb']);
					$pdf->MultiCell(18, 8, $txt, 0, "R", 0);

					$pdf->SetXY($this->marge_gauche +20, $curY);
					$txt=dol_htmlentitiesbr($objectligne['refproduct']." - ".$objectligne['label']);
					$pdf->writeHTMLCell(0, 3, $this->marge_gauche +20, $curY, $txt, 0, 1, 0);

					$pdf->SetXY($this->posqtyplanned -1, $curY);
					$pdf->MultiCell(25, 8, price($objectligne['qtyplanned']), 0, 'R', 0);

					if ($objectligne['qtyused']) {
						$pdf->SetXY($this->posqtyused -1, $curY);
						$pdf->MultiCell(25, 8, price($objectligne['qtyused']), 0, 'R', 0);
					}
					if ($objectligne['qtydeleted']) {
						$pdf->SetXY($this->posqtydeleted -1, $curY);
						$pdf->MultiCell(25, 8, price($objectligne['qtydeleted']), 0, 'R', 0);
					}

					// on totalise pour la forme;
					$total_planned = $total_planned + $objectligne['qtyplanned'];
					$total_used = $total_used + $objectligne['qtyused'];
					$total_deleted = $total_deleted  +$objectligne['qtydeleted'];

					$curYold=$nexYold=$nexY;

					$curY = $pdf->GetY();
					$nexY+=3;

					$stringheight=$pdf->getStringHeight('A', $txt);
					$curY = $pdf->GetY();

					$nexY+=(dol_nboflines_bis($objectligne->desc, 0, $outputlangs->charset_output)*$stringheight);

					$nexY+=2;	// Passe espace entre les lignes

					// Cherche nombre de lignes a venir pour savoir si place suffisante
					if ($i < ($nblines - 1) && empty($hidedesc)) {
						//on recupere la description du produit suivant
						$follow_descproduitservice = $objectligne->desc;
						//on compte le nombre de ligne afin de verifier la place disponible (largeur de ligne 52 caracteres)
						$nblineFollowDesc = dol_nboflines_bis($follow_descproduitservice, 52, $outputlangs->charset_output)*3;
					} else	// If it's last line
						$nblineFollowDesc = 0;

					// Test if a new page is required
					if ($pagenb == 1) {
						$tab_top_in_current_page=$tab_top;
						$tab_height_in_current_page=$tab_height;
					} else {
						$tab_top_in_current_page=$tab_top_newpage;
						$tab_height_in_current_page=$tab_height_middlepage;
					}
					if (($nexY+$nblineFollowDesc) > ($tab_top_in_current_page+$tab_height_in_current_page)
						&& $i < ($nblines - 1)) {
						if ($pagenb == 1)
							$this->_tableau($pdf, $tab_top, $tab_height + 20, $nexY, $outputlangs);
						else
							$this->_tableau($pdf, $tab_top_newpage, $tab_height_middlepage, $nexY, $outputlangs);

						$this->_pagefoot($pdf, $object, $outputlangs);

						// New page
						$pdf->AddPage();
						if (! empty($tplidx))
							$pdf->useTemplate($tplidx);
						$pagenb++;
						$this->_pagehead($pdf, $object, 0, $outputlangs);
						$pdf->SetFont('', '', $default_font_size - 1);
						$pdf->MultiCell(0, 3, '');		// Set interline to 3
						$pdf->SetTextColor(0, 0, 0);

						$nexY = $tab_top_newpage + 7;
					}
				}

				// Show square
				if ($pagenb == 1) {
					$this->_tableau($pdf, $tab_top, $tab_height, $nexY, $outputlangs);
					$bottomlasttab = $tab_top + $tab_height + 1;
				} else {
					$this->_tableau($pdf, $tab_top_newpage, $tab_height_newpage, $nexY, $outputlangs);
					$bottomlasttab = $tab_top_newpage + $tab_height_newpage + 1;
				}

				$pdf->line(
								$this->marge_gauche, $tab_top+$tab_height-7,
								$this->page_largeur-$this->marge_droite,
								$tab_top+$tab_height-7
				);
				$pdf->writeHTMLCell(
								0, 3, $this->marge_gauche+19, $tab_top+$tab_height-5,
								$outputlangs->transnoentities("NbOfComponent")." : ".$nblines,
								0, 1, 0, true, "L"
				);

				$pdf->SetXY($this->posqtyplanned -1, $tab_top+$tab_height-5);
				$pdf->MultiCell(20, 8, price($total_planned), 0, 'R', 0);

				$pdf->SetXY($this->posqtyused -1 , $tab_top+$tab_height-5);
				$pdf->MultiCell(20, 8, price($total_used), 0, 'R', 0);

				$pdf->SetXY($this->posqtydeleted -1, $tab_top+$tab_height-5);
				$pdf->MultiCell(20, 8, price($total_deleted), 0, 'R', 0);

				// On repositionne la police par defaut
				$pdf->SetFont('','', $default_font_size - 1);

				$this->_pagefoot($pdf, $object, $outputlangs);
				if (method_exists($pdf, 'AliasNbPages'))
					$pdf->AliasNbPages();

				$pdf->Close();

				$pdf->Output($file, 'F');
				if (! empty($conf->global->MAIN_UMASK))
					@chmod($file, octdec($conf->global->MAIN_UMASK));

				return 1;
			} else {
				$this->error=$langs->trans("ErrorCanNotCreateDir", $dir);
				return 0;
			}
		} else {
			$this->error=$langs->trans("ErrorConstantNotDefined", "FACTORY_OUTPUTDIR");
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
	 *   @param		int			$nexY			Y
	 *   @param		Translate	$outputlangs	Langs object
	 *   @return	void
	 */
	function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs)
	{
		global $conf;
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$pdf->SetXY($this->marge_gauche, $tab_top+1);
		$pdf->MultiCell(18 ,8, $outputlangs->transnoentities("QtyUnit"), 0, 'C', 0);
		$pdf->SetXY($this->marge_gauche+20, $tab_top+1);
		$pdf->MultiCell(50, 8, $outputlangs->transnoentities("DescOfComponents"), 0, 'L', 0);
		$pdf->SetXY($this->posqtyplanned, $tab_top+1);
		$pdf->MultiCell(20, 8, $outputlangs->transnoentities("QtyNeed"), 0, 'C', 0);
		$pdf->SetXY($this->posqtyused, $tab_top+1);
		$pdf->MultiCell(20, 8, $outputlangs->transnoentities("QtyConsummedOF"), 0, 'C', 0);
		$pdf->SetXY($this->posqtydeleted, $tab_top+1);
		$pdf->MultiCell(20, 8, $outputlangs->transnoentities("QtyLosedOF"), 0, 'C', 0);

		$pdf->line($this->marge_gauche, $tab_top + 5, $this->page_largeur-$this->marge_droite, $tab_top + 6);

		$pdf->SetFont('','', $default_font_size - 1);

		$pdf->MultiCell(0, 3, '');		// Set interline to 3
		$pdf->SetXY($this->marge_gauche, $tab_top + 8);

		$pdf->line($this->marge_gauche+19, $tab_top, $this->marge_gauche+19, $tab_top + $tab_height);

		// Set interline to 3. Then writeMultiCell must use 3 also.
		$pdf->MultiCell(0, 3, '');

		$pdf->Rect(
						$this->marge_gauche, $tab_top,
						($this->page_largeur-$this->marge_gauche-$this->marge_droite), $tab_height
		);
		$pdf->SetXY($this->marge_gauche, $pdf->GetY() + 20);
		$pdf->MultiCell(60, 5, '', 0, 'J', 0);

		$pdf->line($this->posqtyplanned-1, $tab_top, $this->posqtyplanned-1, $tab_top + $tab_height);
		$pdf->line($this->posqtyused-1, $tab_top, $this->posqtyused-1, $tab_top + $tab_height);
		$pdf->line($this->posqtydeleted-1, $tab_top, $this->posqtydeleted-1, $tab_top + $tab_height);

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
		global $conf, $langs;
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$outputlangs->load("main");
		$outputlangs->load("dict");
		$outputlangs->load("product");
		$outputlangs->load("factory@factory");

		pdf_pagehead($pdf, $outputlangs, $this->page_hauteur);

		//Affiche le filigrane brouillon - Print Draft Watermark
		if ($object->statut==0 && (! empty($conf->global->FACTORY_DRAFT_WATERMARK)) )
			pdf_watermark(
							$pdf, $outputlangs,
							$this->page_hauteur, $this->page_largeur,
							'mm', $conf->global->FACTORY_DRAFT_WATERMARK
			);

		//Prepare la suite
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
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
			}
		} else {
			$text=$this->emetteur->name;
			$pdf->MultiCell(100, 4, $outputlangs->convToOutputCharset($text), 0, 'L');
		}

		$pdf->SetFont('', 'B', $default_font_size + 3);
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$title=$outputlangs->transnoentities("FactoryReport");
		$pdf->MultiCell(100, 4, $title, '', 'R');

		$pdf->SetFont('', 'B', $default_font_size + 2);

		// ref de l'�quipement + num immocompta si saisie
		$posy+=10;
		$posx=100;
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);

		$pdf->MultiCell(100, 4, $outputlangs->transnoentities("Ref")." : " . $object->ref, '', 'L');

		// r�f�rence du produit
		$prod=new Product($this->db);
		$prod->fetch($object->fk_product);
		$posy+=4;
		$posx=100;
		$pdf->SetXY($posx, $posy);

		$pdf->SetFont('', 'B', $default_font_size + 2);
		$pdf->MultiCell(100, 1, $outputlangs->transnoentities("Product")." : " .$prod->ref, '', 'L');
		$pdf->SetFont('', '', $default_font_size);
		$posy+=4;
		$posx=120;
		$pdf->SetXY($posx, $posy);
		$pdf->MultiCell(100, 1, $prod->label , '', 'L');

		// ensuite l'entrepot
		if ($object->fk_entrepot) {
			$entrepot=new Entrepot($this->db);
			$entrepot->fetch($object->fk_entrepot);
			$posy+=4;
			$posx=100;
			$pdf->SetXY($posx, $posy);
			$pdf->SetFont('', 'B', $default_font_size + 2);
			$pdf->MultiCell(
							100, 3,
							$outputlangs->transnoentities("Entrepot")." : " . $entrepot->libelle." - ".$entrepot->lieu." (".$entrepot->cp.")",
							'', 'L'
			);
			$pdf->SetFont('', '', $default_font_size);
		}

		$posy+=8;
		$posx=100;
		$pdf->SetXY($posx, $posy);
		$pdf->SetFont('', '', $default_font_size);
		$datestart = dol_print_date($object->date_start_planned, "day", false, $outputlangs, true);
		$datestart.= ($object->date_start_made ? " / ".dol_print_date($object->date_start_made, "day", false, $outputlangs, true):'');
		$pdf->MultiCell(120, 3, $outputlangs->transnoentities("DateStartPlannedMade")." : ".$datestart, '', 'L');

		$posy+=5;
		$pdf->SetXY($posx, $posy);
		$dateend = dol_print_date($object->date_end_planned, "day", false, $outputlangs, true);
		$dateend.= ($object->date_end_made ? " / ".dol_print_date($object->date_end_made, "day", false, $outputlangs, true):'');
		$pdf->MultiCell(120, 3, $outputlangs->transnoentities("DateEndPlannedMade")." : ".$dateend, '', 'L');

		$posy+=8;
		$posx=100;
		$pdf->SetXY($posx, $posy);
		$qtyFactory = $object->qty_planned.($object->qty_made ? " / ".$object->qty_made : '');
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities("QtyPlannedMade")." : ".$qtyFactory, '', 'L');

		$posy+=5;
		$posx=100;
		$pdf->SetXY($posx, $posy);

		$durationFactory = convertSecondToTime($object->duration_planned, 'allhourmin');
		$durationFactory.= ($object->duration_made ? " / ".convertSecondToTime($object->duration_made, 'allhourmin') : '');
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities("FactoryDurationPlannedMade")." : ".$durationFactory, '', 'L');

		$posy+=8;
		$posx=100;
		$pdf->SetXY($posx, $posy);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities("Status")." : ".$object->getLibStatut(0, 1), '', 'L');

		// les liens
		$posy = pdf_writeLinkedObjects($pdf, $object, $outputlangs, $posx, $posy, 100, 3, 'R', $default_font_size);

		// la description du produit � la gauche dans une zone rectangulaire
		if ($object->description) {
			// Show description
			$posy=42;
			$posx=$this->marge_gauche;
			$hautcadre=40;

			// Show sender frame
			$pdf->SetTextColor(0, 0, 0);
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetXY($posx, $posy-5);
			$pdf->SetXY($posx, $posy);
			$pdf->SetFillColor(230, 230, 230);
			$pdf->MultiCell(82, $hautcadre, "", 0, 'R', 1);

			$pdf->SetXY($posx+2, $posy+3);
			$pdf->SetTextColor(0, 0, 60);
			$pdf->SetFont('', 'B', $default_font_size);
			$pdf->MultiCell(80, 3, $outputlangs->transnoentities("Description"), 0, 'L', 0);

			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetXY($posx+2, $posy+8);
			$pdf->MultiCell(80, 4, $object->description, 0, 'L');
		}
	}

	/**
	 *		Show footer of page. Need this->emetteur object
	*
	 *		@param	PDF			&$pdf	 			PDF
	 *		@param	Object		$object				Object to show
	 *		@param	Translate	$outputlangs		Object lang for output
	 *		@return	void
	 */
	function _pagefoot(&$pdf, $object, $outputlangs)
	{
		return pdf_pagefoot($pdf, $outputlangs, 'EQUIPEMENT_FREE_TEXT', $this->emetteur,
			$this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object);
	}
}
