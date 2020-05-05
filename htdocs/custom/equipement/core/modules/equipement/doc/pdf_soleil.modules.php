<?php
/* Copyright (C) 2012-2017	Charlene Benke	<charlie@patas-monkey.com>
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
 *	\file	   equipement/core/modules/equipement/doc/pdf_messine.modules.php
 *	\ingroup	equipement
 *	\brief	  Fichier de la classe permettant de generer les fiches d'�quipement au modele Soleil
 */
dol_include_once("/equipement/core/modules/equipement/modules_equipement.php");

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
class pdf_soleil extends ModelePDFEquipement
{
	var $db;
	var $name;
	var $description;
	var $type;

	var $phpmin = array(4, 3, 0); // Minimum version of PHP required by module
	var $version = '3.3.+1.1.0';

	var $page_largeur;
	var $page_hauteur;
	var $format;
	var $marge_gauche;
	var	$marge_droite;
	var	$marge_haute;
	var	$marge_basse;

	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db	  Database handler
	 */
	function __construct($db)
	{
		global $conf, $langs, $mysoc;

		$this->db = $db;
		$this->name = 'soleil';
		$this->description = $langs->trans("DocumentEquipementModelStandard");

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

		if (! is_object($outputlangs)) $outputlangs=$langs;
		// For backward compatibility with FPDF,
		//force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (! empty($conf->global->MAIN_USE_FPDF))
			$outputlangs->charset_output='ISO-8859-1';

		$outputlangs->load("main");
		$outputlangs->load("dict");
		$outputlangs->load("companies");
		$outputlangs->load("equipement@equipement");

		if ($conf->equipement->dir_output) {
			$object->fetch_thirdparty();

			$objectId = dol_sanitizeFileName($object->id);
			$objectRef = dol_sanitizeFileName($object->ref);
			$dir = $conf->equipement->dir_output;
			if (! preg_match('/specimen/i', $objectId)) $dir.= "/" . $objectId;
			$file = $dir . "/" . $objectRef . ".pdf";

            if (! file_exists($dir)) {
                if (dol_mkdir($dir) < 0) {
                    $this->error=$langs->transnoentities("ErrorCanNotCreateDir",$dir);
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
                if (empty($conf->global->MAIN_DISABLE_FPDI) && ! empty($conf->global->MAIN_ADD_PDF_BACKGROUND)) {
                    $pagecount = $pdf->setSourceFile($conf->mycompany->dir_output.'/'.$conf->global->MAIN_ADD_PDF_BACKGROUND);
                    $tplidx = $pdf->importPage(1);
                }

                $pdf->Open();
                $pagenb=0;
                $pdf->SetDrawColor(128, 128, 128);

                $pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
                $pdf->SetSubject($outputlangs->transnoentities("EquipementCard"));
                $pdf->SetCreator("Dolibarr ".DOL_VERSION);
                $pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
                $pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("InterventionCard"));
                if ($conf->global->MAIN_DISABLE_PDF_COMPRESSION)
                    $pdf->SetCompression(false);

                $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
                $pdf->SetAutoPageBreak(1, 0);

                // New page
                $pdf->AddPage();
                if (! empty($tplidx)) $pdf->useTemplate($tplidx);
                $pagenb++;
                $this->_pagehead($pdf, $object, 1, $outputlangs);
                $pdf->SetTextColor(0, 0, 0);
                $pdf->SetFont('', '', $default_font_size - 1);
                $pdf->MultiCell(0, 3, '');		// Set interline to 3

                $tab_top = 100;
                $tab_top_middlepage = 50;
                $tab_top_newpage = 50;
                $tab_height = 170;
                $tab_height_newpage = 170;
                $tab_height_middlepage = 170;
                $tab_height_endpage = 170;

                // Affiche notes
                if (! empty($object->note_public)) {
                    $tab_top = 88;

                    $pdf->SetFont('', '', $default_font_size - 1);   // Dans boucle pour gerer multi-page
                    $pdf->writeHTMLCell(190, 3, $this->posxdesc-1, $tab_top, dol_htmlentitiesbr($object->note_public), 0, 1);
                    $nexY = $pdf->GetY();
                    $height_note=$nexY-$tab_top;

                    // Rect prend une longueur en 3eme param
                    $pdf->SetDrawColor(192, 192, 192);
                    $pdf->Rect(
                                    $this->marge_gauche, $tab_top-1,
                                    $this->page_largeur-$this->marge_gauche-$this->marge_droite,
                                    $height_note+1
                    );

                    $tab_height = $tab_height - $height_note;
                    $tab_top = $nexY+6;
                } else
                    $height_note=0;

                $iniY = $tab_top + 7;
                $curY = $tab_top + 7;
                $nexY = $tab_top + 7;


                //$pdf->line($this->marge_gauche, $nexY, $this->page_largeur-$this->marge_droite, $nexY);
                $pdf->MultiCell(0, 2, '');		// Set interline to 3. Then writeMultiCell must use 3 also.

                $nblines = count($object->lines);

                // Loop on each lines
                for ($i = 0; $i < $nblines; $i++) {
                    $objectligne = $object->lines[$i];

                    $valide = $objectligne->id ? $objectligne->fetch($objectligne->id) : 0;
                    if ($valide > 0 || $object->specimen) {
                        $curY = $nexY;

                        // type d'�v�nement
                        if ($objectligne->equipeventlib) {
                            $pdf->SetFont('', 'B', $default_font_size - 1);
                            $pdf->SetXY($this->marge_gauche, $curY);
                            $txt=dol_htmlentitiesbr($outputlangs->transnoentities($objectligne->equipeventlib));
                            $pdf->writeHTMLCell(0, 3, $this->marge_gauche, $curY, $txt, 0, 1, 0);
                        }
                        // date de d�but et de fin de l'�v�nement
                        $pdf->SetFont('', '', $default_font_size - 1);
                        if ($objectligne->fulldayevent)
                            $fullday='day';
                        else
                            $fullday='dayhour';
                        $txt=dol_htmlentitiesbr(
                                        " : ".dol_print_date($objectligne->dateo, $fullday, false, $outputlangs, true).
                                        " - ".dol_print_date($objectligne->datee, $fullday, false, $outputlangs, true)
                        );

                        $pdf->writeHTMLCell(0, 3, $this->marge_gauche+25, $curY, $txt, 0, 1, 0);

                        // on affiche le contrat, l'intervention et l'exp�dition si elles sont renseign�es
                        $pdf->SetXY($this->marge_gauche, $curY);
                        $txt=dol_htmlentitiesbr(
                                        $objectligne->ref_fichinter."  ".$objectligne->ref_contrat."  ".$objectligne->ref_expedition
                        );
                        $pdf->writeHTMLCell(0, 3, $this->marge_gauche+90, $curY, $txt, 0, 1, 0);

                        // prix associ� � la ligne de l'�v�nement
                        $pdf->writeHTMLCell(
                                        0, 3, $this->marge_gauche+150, $curY,
                                        price($objectligne->total_ht), 0, 1, 0, true, "R"
                        );
                        // on totalise pour la forme;
                        $tottotal_ht=$tottotal_ht+$objectligne->total_ht;

                        $curYold=$nexYold=$nexY;

                        $curY = $pdf->GetY();
                        $nexY+=3;

                        // la description de l'�v�nement sur une seconde ligne
                        $pdf->SetFont('', '', $default_font_size - 1);
                        $pdf->SetXY($this->marge_gauche, $nexY);
                        //$desc = dol_htmlentitiesbr($objectligne->desc, 1);
                        $desc = strip_tags($objectligne->desc); // delete all tags html in description
                        $pdf->line($this->marge_gauche, $nexY+5, $this->page_largeur-$this->marge_droite, $nexY+5);

                        $curYold = $pdf->GetY();
                        $nexYold = $curYold;

                        $pdf->writeHTMLCell(0, 3, $this->marge_gauche, $curY, $desc, 0, 1, 0);

                        $stringheight=$pdf->getStringHeight('A', $txt);
                        $curY = $pdf->GetY();

                        $nexY+=(dol_nboflines_bis($objectligne->desc, 0, $outputlangs->charset_output)*$stringheight);

                        $nexY+=2;	// Passe espace entre les lignes

                        // Cherche nombre de lignes a venir pour savoir si place suffisante
                        if ($i < ($nblines - 1) && empty($hidedesc)) {
                            //on recupere la description du produit suivant
                            $follow_descproduitservice = $objectligne->desc;
                            //on compte le nombre de ligne afin de verifier la place disponible (largeur de ligne 52 caracteres)
                            $nblineFollowDesc = (dol_nboflines_bis($follow_descproduitservice, 52, $outputlangs->charset_output)*3);
                        }
                        else	// If it's last line
                            $nblineFollowDesc = 0;

                        // Test if a new page is required
                        if ($pagenb == 1) {
                            $tab_top_in_current_page=$tab_top;
                            $tab_height_in_current_page=$tab_height;
                        } else {
                            $tab_top_in_current_page=$tab_top_newpage;
                            $tab_height_in_current_page=$tab_height_middlepage;
                        }
                        if (($nexY+$nblineFollowDesc) > ($tab_top_in_current_page+$tab_height_in_current_page) && $i < ($nblines - 1)) {
                            if ($pagenb == 1)
                                $this->_tableau($pdf, $tab_top, $tab_height + 20, $nexY, $outputlangs);
                            else
                                $this->_tableau($pdf, $tab_top_newpage, $tab_height_middlepage, $nexY, $outputlangs);

                            $this->_pagefoot($pdf, $object, $outputlangs);

                            // New page
                            $pdf->AddPage();
                            if (! empty($tplidx)) $pdf->useTemplate($tplidx);
                            $pagenb++;
                            $this->_pagehead($pdf, $object, 0, $outputlangs);
                            $pdf->SetFont('', '', $default_font_size - 1);
                            $pdf->MultiCell(0, 3, '');		// Set interline to 3
                            $pdf->SetTextColor(0, 0, 0);

                            $nexY = $tab_top_newpage + 7;
                        }
                    }
                }

                // Show square
                if ($pagenb == 1) {
                    $this->_tableau($pdf, $tab_top, $tab_height, $nexY, $outputlangs);
                    $bottomlasttab=$tab_top + $tab_height + 1;
                } else {
                    $this->_tableau($pdf, $tab_top_newpage, $tab_height_newpage, $nexY, $outputlangs);
                    $bottomlasttab=$tab_top_newpage + $tab_height_newpage + 1;
                }

                $pdf->line(
                                $this->marge_gauche, $tab_top+$tab_height-7,
                                $this->page_largeur-$this->marge_droite, $tab_top+$tab_height-7
                );
                $pdf->writeHTMLCell(
                                0, 3, $this->marge_gauche+5, $tab_top+$tab_height-4,
                                $outputlangs->transnoentities("NbOfEvenement")." : ".$nblines, 0, 1, 0, true, "L"
                );
                $pdf->writeHTMLCell(
                                0, 3, $this->marge_gauche+150, $tab_top+$tab_height-4,
                                $outputlangs->transnoentities("EquipementLineTotalHT")." : ".price($tottotal_ht),
                                0, 1, 0, true, "R"
                );

                $pdf->SetFont('', '', $default_font_size - 1);   // On repositionne la police par defaut

                $this->_pagefoot($pdf, $object, $outputlangs);
                if (method_exists($pdf, 'AliasNbPages')) $pdf->AliasNbPages();

                $pdf->Close();

                $pdf->Output($file, 'F');
                if (! empty($conf->global->MAIN_UMASK)) {
                    @chmod($file, octdec($conf->global->MAIN_UMASK));
                }

                return 1; // No error
			} else {
				$this->error=$langs->trans("ErrorCanNotCreateFile", $dir);
				return 0;
			}

		} else {
			$this->error=$langs->trans("ErrorConstantNotDefined", "EQUIPEMENT_OUTPUTDIR");
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
		$pdf->MultiCell(190, 8, $outputlangs->transnoentities("ListOfEquipEvents"), 0, 'L', 0);
		$pdf->line($this->marge_gauche, $tab_top + 5, $this->page_largeur-$this->marge_droite, $tab_top + 6);

		$pdf->SetFont('', '', $default_font_size - 1);

		$pdf->MultiCell(0, 3, '');		// Set interline to 3
		$pdf->SetXY($this->marge_gauche, $tab_top + 8);

		$pdf->MultiCell(0, 3, '');		// Set interline to 3. Then writeMultiCell must use 3 also.

		$pdf->Rect(
						$this->marge_gauche, $tab_top,
						($this->page_largeur-$this->marge_gauche-$this->marge_droite), $tab_height+3
		);
		$pdf->SetXY($this->marge_gauche, $pdf->GetY() + 20);
		$pdf->MultiCell(60, 5, '', 0, 'J', 0);
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
		global $conf, $langs, $pagenb;
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$outputlangs->load("main");
		$outputlangs->load("dict");
		$outputlangs->load("companies");
		$outputlangs->load("interventions");
		$outputlangs->load("equipement@equipement");

		pdf_pagehead($pdf, $outputlangs, $this->page_hauteur);

		//Affiche le filigrane brouillon - Print Draft Watermark
		if ($object->statut==0 && (! empty($conf->global->EQUIPEMENT_DRAFT_WATERMARK)) )
			pdf_watermark(
							$pdf, $outputlangs,
							$this->page_hauteur, $this->page_largeur,
							'mm', $conf->global->FICHINTER_DRAFT_WATERMARK
			);

		//Prepare la suite
		$pdf->SetTextColor(0, 0, 60);
		$pdf->SetFont('', 'B', $default_font_size + 3);

        //
        // top left column
        //
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

        // ref de l'�quipement + num immocompta si saisie
        $posy+=10;
        //$posx=100;
        if (intval($height) > 0) {
            $posy += $height;
        }
        $posx=$this->marge_gauche;
        $pdf->SetXY($posx, $posy);
        $pdf->SetTextColor(0, 0, 60);
        if ($object->numimmocompta)
            $refequipement=$object->ref." - ".$object->numimmocompta;
        else
            $refequipement=$object->ref;

        $pdf->MultiCell(100, 4, $outputlangs->transnoentities("Ref")." : " . $refequipement, '', 'L');

        // product reference
        $prod=new Product($this->db);
        $prod->fetch($object->fk_product);
        $posy+=6;
        //$posx=100;
        $posx=$this->marge_gauche;
        $posy+=6;
        $pdf->SetXY($posx, $posy);
        $pdf->SetFont('', 'B', $default_font_size + 2);
        $pdf->MultiCell(
            100, 1,
            $outputlangs->transnoentities("Product")." : " .$prod->ref." (vers. : ". $object->numversion.")",
            '', 'L'
        );
        $pdf->SetFont('', '', $default_font_size);
        //$posx=120;
        $posx=$this->marge_gauche;
        $posy+=6;
        $pdf->SetXY($posx, $posy);
        $pdf->MultiCell(100, 1, $prod->label, '', 'L');


        //
        // top right column
        //
        $posy=$this->marge_haute;

        // title equipment
        $posy+=6;
        $posx=100;
		$pdf->SetFont('', 'B', $default_font_size + 3);
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$title=$outputlangs->transnoentities("EquipementCard");
		$pdf->MultiCell(100, 4, $title, '', 'R');

		$pdf->SetFont('', 'B', $default_font_size + 2);

		// date
        //$posx=$this->page_largeur-$this->marge_droite-100;
        //$posx=$this->marge_gauche;
        $posy+=12;
        $pdf->SetXY($posx, $posy);
        $pdf->SetFont('', '', $default_font_size);
        $datec=dol_print_date($object->datec, "day", false, $outputlangs, true);
        $pdf->MultiCell(100, 3, $outputlangs->transnoentities("Date")." : " . $datec, '', 'R');

		// le reste seulement sur la premi�re page
		if ($nbpage == 1) {
			// dates de l'�quipement si renseign�
			$posy+=10;
			$posx=100;
			$pdf->SetXY($posx, $posy);
			$pdf->MultiCell(
							100, 3,
							$outputlangs->transnoentities("Dateo")." : " . dol_print_date(
											$object->dateo, "day", false, $outputlangs, true
							), '', 'L'
			);
			if ($object->datee) {
				$posx=$this->page_largeur-$this->marge_droite-100;
				$pdf->SetXY($posx, $posy);
				$datee = $outputlangs->transnoentities("Datee")." : ";
				$datee.= dol_print_date($object->datee, "day", false, $outputlangs, true);
				$pdf->MultiCell(100, 3, $datee, '', 'R');
			}

			// d'abord le fournisseur et sa facture
			if ($object->fk_soc_fourn) {
				$soc=new Societe($this->db);
				$soc->fetch($object->fk_soc_fourn);
				$posy+=6;
				$pdf->SetXY($posx, $posy);
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("Fournisseur")." : " . $soc->nom, '', 'L');
				if ($object->fk_fact_fourn) {
					$factfourn = new FactureFournisseur($this->db);
					$factfourn->fetch($object->fk_fact_fourn);
					$posy+=4;
					$pdf->SetXY($posx, $posy);
					$pdf->MultiCell(
									100, 3,
									$outputlangs->transnoentities("RefFactFourn")." : " . $factfourn->ref.' - '.dol_print_date(
													$factfourn->date, 'day'
									).' - '.price($factfourn->total_ttc), '', 'L'
					);
				}
			}

			// ensuite l'entrepot
			if ($object->fk_entrepot) {
				$entrepot=new Entrepot($this->db);
				$entrepot->fetch($object->fk_entrepot);
				$posy+=6;
				$pdf->SetXY($posx, $posy);
				$pdf->MultiCell(
								100, 3,
								$outputlangs->transnoentities("Entrepot")." : ".$entrepot->libelle." - ".$entrepot->lieu." (".$entrepot->cp.")",
								'', 'L'
				);
			}

			// enfin le client et sa facture
			if ($object->fk_soc_client) {
				$soc=new Societe($this->db);
				$soc->fetch($object->fk_soc_client);
				$posy+=6;
				$pdf->SetXY($posx, $posy);
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("Client")." : " . $soc->nom, '', 'L');
				if ($object->fk_fact_client) {
					$factclient = new Facture($this->db);
					$factclient->fetch($object->fk_fact_client);
					$posy+=4;
					$pdf->SetXY($posx, $posy);
					$pdf->MultiCell(
									100, 3,
									$outputlangs->transnoentities("RefFactClient")." : " . $factclient->ref.' - '.dol_print_date(
													$factclient->date, 'day'
									).' - '.price($factclient->total_ttc), '', 'L'
					);
				}
			}

			$posy=78;
			$posx=100;
			if ($object->etatequiplibelle) {
				$pdf->SetXY($posx, $posy);
				$txtToPrint =$outputlangs->transnoentities("EtatEquip")." : ";
				$txtToPrint.=$outputlangs->transnoentities($object->etatequiplibelle);
				$pdf->MultiCell(100, 3, $txtToPrint, '', 'L');
			}
			$pdf->SetXY($posx, $posy);
			$txtToPrint =$outputlangs->transnoentities("Status")." : ";
			$txtToPrint.=$outputlangs->transnoentities($object->statuts[$object->statut]);
			$pdf->MultiCell(100, 3, $txtToPrint, '', 'R');

			// la description du produit � la gauche dans une zone rectangulaire
			if ($object->description) {
				// Show description
				$posy=42;
				$posx=$this->marge_gauche;
				//if (! empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx=$this->page_largeur-$this->marge_droite-80;
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
		return pdf_pagefoot(
						$pdf, $outputlangs, 'EQUIPEMENT_FREE_TEXT', $this->emetteur,
						$this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object
		);
	}
}