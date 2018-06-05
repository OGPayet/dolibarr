<?php
/* Copyright (C) 2010-2012 	Regis Houssin  	<regis.houssin@capnetworks.com>
 * Copyright (C) 2015-2018	Charlie Benke	<charlie@patas-monkey.com>

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
 *	\file	   htdocs/mylist/modules/mylistydoc/pdf_mylist_landscape.modules.php
 *	\ingroup	mylist
 *	\brief	  pdf de mylist au format paysage
 *	\author		Charlie Benke
 */

dol_include_once("/mylist/core/modules/mylist/modules_mylist.php");
dol_include_once('/mylist/class/mylist.class.php');

require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';


/**
 *	\class	  pdf_mylist_portrait
 *	\brief	  Classe permettant de generer les mylist au format portrait
 */

class pdf_mylist_portrait extends ModeleMylist
{
	var $emetteur;	// Objet societe qui emet

	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db	  Database handler
	 */
	function __construct($db)
	{
		global $conf, $langs, $mysoc;

		$langs->load("main");
		$langs->load("projects");
		$langs->load("companies");

		$langs->load("mylist@mylist");

		$this->db = $db;
		$this->name = "mylist Portrait";
		$this->description = $langs->trans("DocumentModelMylistPortrait")." - ".$langs->trans("MadeByPatasMonkey");

		// Dimension page pour format A4
		$this->type = 'pdf';
		$formatarray=pdf_getFormat();
		// format portrait
		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = $formatarray['height'];

		$this->format = array($this->page_largeur, $this->page_hauteur);
		$this->marge_gauche=isset($conf->global->MAIN_PDF_MARGIN_LEFT)?$conf->global->MAIN_PDF_MARGIN_LEFT:10;
		$this->marge_droite=isset($conf->global->MAIN_PDF_MARGIN_RIGHT)?$conf->global->MAIN_PDF_MARGIN_RIGHT:10;
		$this->marge_haute =isset($conf->global->MAIN_PDF_MARGIN_TOP)?$conf->global->MAIN_PDF_MARGIN_TOP:10;
		$this->marge_basse =isset($conf->global->MAIN_PDF_MARGIN_BOTTOM)?$conf->global->MAIN_PDF_MARGIN_BOTTOM:10;

		$this->option_logo = 1;					// Affiche logo FAC_PDF_LOGO

		$this->emetteur=$mysoc;
		if (! $this->emetteur->country_code)
			$this->emetteur->country_code=substr($langs->defaultlang, -2);	// By default if not defined
	}


	/**
	 *	Fonction generant le projet sur le disque
	 *
	 *	@param	Project		$object   		Object project a generer
	 *	@param	Translate	$outputlangs	Lang output object
	 *	@return	int		 				1 if OK, <=0 if KO
	 */
	function write_file($object, $outputlangs)
	{
		global $user, $langs, $conf;

		if (! is_object($outputlangs))
			$outputlangs=$langs;

		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text
		if (! empty($conf->global->MAIN_USE_FPDF))
			$outputlangs->charset_output='ISO-8859-1';

		$outputlangs->load("main");
		$outputlangs->load("dict");
		$outputlangs->load("companies");
		$outputlangs->load("mylist@mylist");

		if ($object->langs)
			foreach (explode(":", $object->langs) as $newlang)
				$outputlangs->load($newlang);

		if ($conf->mylist->dir_output) {
			//var_dump($object);

			$objectref = dol_sanitizeFileName($object->label);
			$dir = $conf->mylist->dir_output;
			if (! preg_match('/specimen/i', $objectref))
				$dir.= "/" . $objectref;

			$yymmdd = strftime("%y%m%d", dol_now());
			$file = $dir . "/" . $objectref.'-'.$yymmdd. ".pdf";
			if (! file_exists($dir)) {
				if (dol_mkdir($dir) < 0) {
					$this->error=$langs->transnoentities("ErrorCanNotCreateDir", $dir);
					return 0;
				}
			}

			if (file_exists($dir)) {
				$pdf=pdf_getInstance($this->format);
				$default_font_size = pdf_getPDFFontSize($outputlangs);	// Must be after pdf_getInstance
				$heightforinfotot = 10;	// Height reserved to output the info and total part
				// Height reserved to output the free text on last page
				$heightforfreetext= (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT)?$conf->global->MAIN_PDF_FREETEXT_HEIGHT:5);
				// Height reserved to output the footer (value include bottom margin)
				$heightforfooter = $this->marge_basse + 8;
				$pdf->SetAutoPageBreak(1, 0);

				if (class_exists('TCPDF')) {
					$pdf->setPrintHeader(false);
					$pdf->setPrintFooter(false);
				}
				$pdf->SetFont(pdf_getPDFFont($outputlangs));

				//on récupère le nombre de ligne
				$ressql = $this->db->query($object->sqlquery);
				$nblignes = $this->db->num_rows($ressql);

				$pdf->Open();
				$pagenb=0;
				$pdf->SetDrawColor(192, 192, 192);

				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities("Mylist"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("Project"));
				if (! empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION))
					$pdf->SetCompression(false);
				// Left, Top, Right
				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);

				// New page Landscape mode
				$pdf->AddPage();
				$pagenb++;


				$this->_pagehead($pdf, $object, 1, $outputlangs);
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->MultiCell(0, 3, '');		// Set interline to 3
				$pdf->SetTextColor(0, 0, 0);

				$tab_top = 48;
				$tab_height = 280;
				$tab_top_newpage = 38;

				// Affiche la requete SQL en mode test
				//  pour les tests on affiche la requete SQL
				if ($object->active == 0) {
					// affichage de la requete sur l'édition
					$pdf->SetFont('', '', $default_font_size - 1);
					$pdf->writeHTMLCell(190, 3, 10, $tab_top-2, dol_htmlentitiesbr($object->sqlquery), 0, 1);
					$nexY = $pdf->GetY();
					$height_note=$nexY-($tab_top-2);

					// Rect prend une longueur en 3eme param
					$pdf->SetDrawColor(192, 192, 192);
					$pdf->Rect(
									$this->marge_gauche, $tab_top-3,
									$this->page_largeur-$this->marge_gauche-$this->marge_droite,
									$height_note+1
					);

					$tab_height = $tab_height - $height_note;
					$tab_top = $nexY+6;
				} else
					$height_note=0;

				$iniY = $tab_top + 9;
				$curY = $tab_top + 9;
				$nexY = $tab_top + 9;

				$i = 0;
				while ($i < $nblignes) {
					$objp = $this->db->fetch_object($ressql);
					$curY = $nexY;
					$nexY = $curY + 6;
					$pdf->setTopMargin($tab_top_newpage);
					// The only function to edit the bottom margin of current page to set it.
					$pdf->setPageOrientation('', 1, $heightforfooter+$heightforfreetext+$heightforinfotot);

					$pageposbefore=$pdf->getPage();

					$pdf->startTransaction();

					// print the line
					$arrayTable = $object->listsUsed;
					$poxcol=10;
					$nbcolmax=5;
					$nbcol=0;
					$heightline=1;
					foreach ($arrayTable as $key => $fields) {
						// si on doit afficher le champs
						if ($fields['widthpdf'] != -1) {
							$tmp=$fields['field'];
							if ($fields['alias']!="")
								$codFields=$fields['alias'];
							else
								$codFields=str_replace(array('.', '-'), "_", $fields['field']);

							if (strpos($fields['field'], 'fk_statut') > 0) {
								$tblelement=explode(":", $fields['param']);
								if (count($tblelement) >0) {
									if ($tblelement[1]!="")
										require_once DOL_DOCUMENT_ROOT.$tblelement[1];
									$objectstatic = new $tblelement[0]($this->db);
									$objectstatic->statut=$objp->$codFields;
									// for compatibility case
									$objectstatic->fk_statut=$objp->$codFields;
									if ($objp->f_paye == 1)
										$objectstatic->paye=1;
									$tmp = html_entity_decode(strip_tags($objectstatic->getLibStatut(1)));
								} else
									$tmp = $langs->transnoentities("LeftStatutSetting", $dir);
							} elseif ((	strpos($fields['field'], '.fk_') > 0
										||	strpos($fields['field'], '.rowid') > 0
										||	strpos($fields['field'], '.id') > 0)
									&& $fields['param']) {
								$tblelement=explode(":", $fields['param']);
								if (count($tblelement) == 4) {
									if ($tblelement[1]!="")
										dol_include_once($tblelement[1]);
									if ($fields['alias']!="")
										$fieldsname=$fields['alias'];
									else
										$fieldsname=str_replace(array('.', '-'), "_", $fields['field']);
									// seulement si le champs est renseigné
									if ($objp->$fieldsname) {
										$objectstatic = new $tblelement[0]($this->db);
										$objectstatic->id=$objp->$fieldsname;
										$objectstatic->fetch($objp->$fieldsname);
										$tmp= strip_tags($objectstatic->getNomUrl(1));
									}
								}
							} else {
								switch($fields['type']) {
									case "Price":
										$tmp= price($objp->$codFields);
										if ($conf->global->MYLIST_DISPLAY_CURRENCY_PRICE)
											$tmp.= " ".$langs->trans("Currency" . $conf->currency);
										break;

									case "Number":
										$tmp= price($objp->$codFields);
										break;

									case "Percent":
										$tmp= price($objp->$codFields * 100)." %";
										break;

									case "Date":
										$tmp= dol_print_date($this->db->jdate($objp->$codFields), 'day');
										break;

									case "Boolean":
										$tmp= yn($objp->$codFields);
										break;

									default:
										$tmp= $objp->$codFields;
										break;
								}
							}

							$pdf->SetXY($poxcol, $curY);
							if ($fields['widthpdf'] != -1 && $fields['type'] != 'CategoriesFilter') {
								$pdf->MultiCell(
												($fields['widthpdf']?$fields['widthpdf']:20),
												$heightline, $tmp, 1,
												strtoupper(substr($fields['align'], 0, 1)), 0
								);
								$poxcol+= ($fields['widthpdf']?$fields['widthpdf']:20);

								// si on dépasse la largeur de la feuille
								if ($poxcol >$this->page_largeur -20)
									break;
							}
						}
						// pour gérer l'espace de zone si ca dépasse de la hauteur de ligne
						$tmpY = $pdf->GetY();
						if ($tmpY > $curY + $heightline) {
							$nexY = $tmpY;
							// on agrandis alors la hauteur des cellules
							$heightline = $tmpY - $curY;
						}
					}
					$pdf->SetFont('', '', $default_font_size - 1);   // On repositionne la police par defaut
					$pageposafter=$pdf->getPage();

					if ($pageposafter > $pageposbefore) {
						// There is a pagebreak
						$pdf->rollbackTransaction(true);

						// The only function to edit the bottom margin of current page to set it.
						$pdf->AddPage('', '', true);
						$pdf->setPageOrientation('', 1, $heightforfooter);
						$pageposafter=$pdf->getPage();
						$pdf->SetFont('', '', $default_font_size - 1);   // On repositionne la police par defaut
						$nexY = $pdf->GetY();

						$pdf->SetFont('', '', $default_font_size - 1);   // On repositionne la police par defaut
						if (! empty($tplidx)) $pdf->useTemplate($tplidx);
						if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD))
							$this->_pagehead($pdf, $object, 0, $outputlangs);


					} else
						$pdf->commitTransaction(); // No pagebreak

					$pageposafter=$pdf->getPage();

					$pdf->setPage($pageposbefore);
					$pdf->setTopMargin($this->marge_haute);
					// The only function to edit the bottom margin of current page to set it.
					$pdf->setPageOrientation('', 1, 0);

					while ($pagenb < $pageposafter) {
						$pdf->setPage($pagenb);
						if ($pagenb == 1)
							$this->_tableau(
											$pdf, $object, $tab_top,
											$this->page_hauteur - $tab_top - $heightforfooter,
											0, $outputlangs, 0, 1
							);
						else
							$this->_tableau(
											$pdf, $object, $tab_top_newpage,
											$this->page_hauteur - $tab_top_newpage - $heightforfooter,
											0, $outputlangs, 1, 1
							);

						$pdf->SetFont('', '', $default_font_size - 1);
						$this->_pagefoot($pdf, $object, $outputlangs, 1);
						$pagenb++;

						$pdf->setPage($pagenb);

						// The only function to edit the bottom margin of current page to set it.
						$pdf->setPageOrientation('', 1, 0);

						if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD))
							$this->_pagehead($pdf, $object, 0, $outputlangs);

						$iniY = $tab_top_newpage+9;
						$curY = $tab_top_newpage+9;
						$nexY = $tab_top_newpage+9;

					}
					$i++;
				}

				// Show square
				if ($pagenb == 1)
					$this->_tableau(
									$pdf, $object, $tab_top,
									$this->page_hauteur - $tab_top - $heightforinfotot - $heightforfreetext - $heightforfooter,
									0, $outputlangs, 0, 0
					);
				else
					$this->_tableau(
									$pdf, $object, $tab_top_newpage,
									$this->page_hauteur - $tab_top_newpage - $heightforinfotot - $heightforfreetext - $heightforfooter,
									0, $outputlangs, 1, 0
					);

				$bottomlasttab=$this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;

				// Affiche zone totaux
				$posy=$this->_tableau_tot($pdf, $object, $bottomlasttab, $outputlangs);

				/*
				 * Pied de page
				 */
				$this->_pagefoot($pdf, $object, $outputlangs);
				if (method_exists($pdf, 'AliasNbPages'))
					$pdf->AliasNbPages();

				$pdf->Close();

				$pdf->Output($file, 'F');
				if (! empty($conf->global->MAIN_UMASK))
					@chmod($file, octdec($conf->global->MAIN_UMASK));

				return 1;   // Pas d'erreur
			} else {
				$this->error=$langs->transnoentities("ErrorCanNotCreateDir", $dir);
				return 0;
			}
		}

		$this->error=$langs->transnoentities("ErrorConstantNotDefined", "MYLIST_OUTPUTDIR");
		return 0;
	}

	/**
	 *	Show total to pay
	 *
	 *	@param	PDF			&$pdf		   Object PDF
	 *	@param  Facture		$object		 Object invoice
	 *	@param  int			$deja_regle	 Montant deja regle
	 *	@param	int			$posy			Position depart
	 *	@param	Translate	$outputlangs	Objet langs
	 *	@return int							Position pour suite
	 */
	function _tableau_tot(&$pdf, $object,  $posy, $outputlangs)
	{
		global  $db; //$conf,
		$outputlangs->load("mylist@mylist");

		$sql=$object->sqlquery;
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$tab2_top = $posy;
		$tab2_hl = 4;
		$pdf->SetFont('', '', $default_font_size - 1);

		// Tableau total
		$col1x = 100;
		$col2x = 150;
		$col3x = 170;
		// To work with US executive format
		if ($this->page_largeur < 210) {
			$col2x-=20;
			$col3x-=20;
		}

		// on récupère le tableau des champs à traiter
		$fieldsreport = array ();

		foreach ($object->listsUsed as $key) {
			$fieldreport=array();
			if ($key['sumreport']=="1")
				$fieldreport['sum']='1';

			if ($key['avgreport']=="1")
				$fieldreport['avg']=1;

			if ($fieldreport['sum'] + $fieldreport['avg'] > 0) {
				$fieldreport['name']=$key['name'];
				$fieldreport['totalsum']=0;
				if ($key['alias']!="")
					$fieldreport['alias']=$key['alias'];
				else
					$fieldreport['alias']=str_replace(array('.', '-'), "_", $key['field']);
				$fieldsreport[]=$fieldreport;
			}
		}

		// si il y a un tableau à réaliser
		if (count($fieldsreport) >0) {
			$result=$db->query($sql);
			if ($result) {
				$i=0;
				$num = $db->num_rows($result);
				while ($i < $num) {
					$objp = $db->fetch_object($result);
					foreach ($fieldsreport as $key => $value) {
						if ($value['sum'] + $value['avg'] > 0) {
							$valuealias=$value['alias'];
							$fieldsreport[$key]['totalsum']+=$objp->$valuealias;
						}
					}
					$i++;
				}
			}

			$pdf->SetXY($col1x, $tab2_top);
			$pdf->MultiCell(90, $tab2_hl, $outputlangs->transnoentities("SumAvgTable"), 1, 'C', 0, 1);
			$pdf->SetXY($col1x, $tab2_top+4);
			$pdf->MultiCell(50, 4, $outputlangs->transnoentities('FieldsSelect'), 1, "L", 0, 1);
			$pdf->SetXY($col2x, $tab2_top+4);
			$pdf->MultiCell(20, 4, $outputlangs->transnoentities('Sum'), 1, "R", 0, 1);
			$pdf->SetXY($col3x, $tab2_top+4);
			$pdf->MultiCell(20, 4, $outputlangs->transnoentities('Average'), 1, "R", 0, 1);

			$i=8;
			foreach ($fieldsreport as $key) {
				$pdf->SetXY($col1x, $tab2_top+$i);
				$pdf->MultiCell(50, 4, $outputlangs->transnoentities($key['name']), 1, "L", 0, 1);
				$pdf->SetXY($col2x, $tab2_top+$i);
				$pdf->MultiCell(20, 4, ($key['sum']==1 ? $key['totalsum']:''), 1, "R", 0, 1);
				$pdf->SetXY($col3x, $tab2_top+$i);
				$pdf->MultiCell(
								20, 4,
								($num > 0 ? ($key['avg']==1 ? price($key['totalsum']/$num, 2):''):'N/A'),
								1, "R", 0, 1
				);
				$i=$i+4;
			}
		}
		return ($tab2_top + $tab2_hl);
	}


	/**
	 *   Show table for lines
	 *
	 *   @param		PDF			&$pdf	 		Object PDF
	 *   @param		string		$tab_top		Top position of table
	 *   @param		string		$tab_height		Height of table (rectangle)
	 *   @param		int			$nexY			Y
	 *   @param		Translate	$outputlangs	Langs object
	 *   @param		int			$hidetop		Hide top bar of array
	 *   @param		int			$hidebottom		Hide bottom bar of array
	 *   @return	void
	 */
	function _Tableau(&$pdf, $object, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop=0, $hidebottom=0)
	{
//		global $conf, $mysoc;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$pdf->SetDrawColor(192, 192, 192);

		// Rect prend une longueur en 3eme param
		//$pdf->Rect($this->marge_gauche, $tab_top, $this->page_largeur-$this->marge_gauche-$this->marge_droite, 6);

		$pdf->SetTextColor(0, 0, 0);
		$pdf->SetFont('', '', $default_font_size-1);

		$arrayTable = $object->listsUsed;
		$poxcol=10;
		$heightcell=7;	// par défaut on prévoie large en hauteur de tableau
		foreach ($arrayTable as $key => $fields) {
			$pdf->SetXY($poxcol, $tab_top+2);
			if ($fields['widthpdf'] != -1 && $fields['type'] != 'CategoriesFilter') {
				$tmpy=$pdf->GetY();
				$pdf->MultiCell(
								($fields['widthpdf']?$fields['widthpdf']:20), $heightcell,
								$outputlangs->transnoentities($fields['name']),
								1, substr($fields['align'], 0, 1)
				);
				$heightcell=(($pdf->GetY()-$tmpy) > $heightcell?($pdf->GetY()-$tmpy):$heightcell);
				$poxcol=$poxcol+($fields['widthpdf']?$fields['widthpdf']:20);
			}
			if ($poxcol > $this->page_largeur-20)
				break;
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

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		pdf_pagehead($pdf, $outputlangs, $this->page_hauteur);

		$pdf->SetTextColor(0, 0, 60);
		$pdf->SetFont('', 'B', $default_font_size + 3);

		$posx=$this->page_largeur-$this->marge_droite-100;
		$posy=$this->marge_haute;
		$pdf->SetXY($this->marge_gauche, $posy);

		// Logo
		$logo=$conf->mycompany->dir_output.'/logos/'.$mysoc->logo;
		if ($mysoc->logo) {
			if (is_readable($logo)) {
				$height=pdf_getHeightForLogo($logo);
				$pdf->Image($logo, $this->marge_gauche, $posy, 0, $height);	// width=0 (auto)
			} else {
				$pdf->SetTextColor(200, 0, 0);
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$pdf->MultiCell(100, 3, $langs->transnoentities("ErrorLogoFileNotFound", $logo), 0, 'L');
				$pdf->MultiCell(100, 3, $langs->transnoentities("ErrorGoToModuleSetup"), 0, 'L');
			}
		}
		else
			$pdf->MultiCell(100, 4, $outputlangs->transnoentities($this->emetteur->name), 0, 'L');

		$pdf->SetFont('', 'B', $default_font_size + 3);
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$pdf->MultiCell(100, 4, $object->label, '', 'L');

		$posy+=4;
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$textcell = $outputlangs->transnoentities("Date")." : ".dol_print_date(
						dol_now(), "daytext", false, $outputlangs, true
		);
		$pdf->MultiCell(100, 4, $textcell, '', 'L');

		$pdf->SetFont('', '', $default_font_size - 1);   // On repositionne la police par defaut

		// aficher le titre du reporting
		$pdf->SetTextColor(0, 0, 60);
	}

	/**
	 *   	Show footer of page. Need this->emetteur object
	 *
	 *   	@param	PDF			&$pdf	 			PDF
	 * 		@param	Object		$object				Object to show
	 *	  @param	Translate	$outputlangs		Object lang for output
	 *	  @param	int			$hidefreetext		1=Hide free text
	 *	  @return	void
	 */
	function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext=0)
	{
		return pdf_pagefoot(
						$pdf, $outputlangs, 'MYLIST_FREE_TEXT', $this->emetteur,
						$this->marge_basse, $this->marge_gauche, $this->page_hauteur,
						$object, 0, $hidefreetext
		);
	}
}