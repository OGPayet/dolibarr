<?php
/* Copyright (C) 2012-2017	Charlie BENKE	<charlie@patas-monkey.com>
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
 */

/**
 *	\file		htdocs/equipement/events.php
 *	\brief		event equipement tabs
 *	\ingroup	equipement
 */

$res=@include("../main.inc.php");					// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res)
	$res=@include("../../main.inc.php");		// For "custom" directory

require_once DOL_DOCUMENT_ROOT."/product/class/product.class.php";
require_once DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php";
require_once DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.facture.class.php";
require_once DOL_DOCUMENT_ROOT."/product/stock/class/entrepot.class.php";
require_once DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php";
require_once DOL_DOCUMENT_ROOT."/expedition/class/expedition.class.php";
require_once DOL_DOCUMENT_ROOT."/fichinter/class/fichinter.class.php";
require_once DOL_DOCUMENT_ROOT."/projet/class/project.class.php";

require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/date.lib.php";

dol_include_once('/equipement/core/modules/equipement/modules_equipement.php');
dol_include_once('/equipement/class/equipement.class.php');
dol_include_once('/equipement/core/lib/equipement.lib.php');

if (! empty($conf->global->EQUIPEMENT_ADDON)
&& is_readable(dol_buildpath("/equipement/core/modules/equipement/".$conf->global->EQUIPEMENT_ADDON.".php"))
)
	dol_include_once("/equipement/core/modules/equipement/".$conf->global->EQUIPEMENT_ADDON.".php");


$langs->load("companies");
$langs->load("products");
$langs->load("equipement@equipement");

$action		= GETPOST('action', 'alpha');
$id			= GETPOST('id', 'int');
$ref		= GETPOST('ref', 'alpha');
$productid	= GETPOST('productid', 'int');
$action		= GETPOST('action', 'alpha');
$confirm	= GETPOST('confirm', 'alpha');
$mesg		= GETPOST('msg', 'alpha');
$socFournid = GETPOST('fk_soc_fourn', 'alpha');
$socClienid = GETPOST('fk_soc_client', 'alpha');
$nbAddEquipement = GETPOST('nbAddEquipement', 'int');
//$serialMethod = GETPOST('SerialMethod', 'int');


// Security check
if ($user->societe_id)
	$socid=$user->societe_id;
$result = restrictedArea($user, 'equipement', $id, 'equipement', '', 'fk_soc_client');

$object = new Equipement($db);
$extrafieldsevt = new ExtraFields($db);
$extralabelsevt = $extrafieldsevt->fetch_name_optionals_label("equipementevt");

/*
 * Actions
 */


// Add line
if ($action == "addline" && $user->rights->equipement->creer) {

	if (! $error) {
		$db->begin();

		$ret=$object->fetch($id);
		$object->fetch_thirdparty();

		$desc=GETPOST('np_desc');
		$dateo = dol_mktime(
						GETPOST('deohour', 'int'), GETPOST('deomin', 'int'), 0,
						GETPOST('deomonth', 'int'), GETPOST('deoday', 'int'), GETPOST('deoyear', 'int')
		);
		$datee = dol_mktime(
						GETPOST('deehour', 'int'), GETPOST('deemin', 'int'), 0,
						GETPOST('deemonth', 'int'), GETPOST('deeday', 'int'), GETPOST('deeyear', 'int')
		);
		$fulldayevent =GETPOST('fulldayevent');
		$fk_equipementevt_type =GETPOST('fk_equipementevt_type');
		$fk_contrat =GETPOST('fk_contrat');
		$fk_fichinter =GETPOST('fk_fichinter');
		$fk_expedition =GETPOST('fk_expedition');
		$fk_project =GETPOST('fk_project');
		$fk_user_author=GETPOST('userid');
		$total_ht =GETPOST('total_ht');


		// Extrafields
		$array_option = $extrafieldsevt->getOptionalsFromPost($extralabelsevt, $predef);
		// Unset extrafield
		if (is_array($extralabelsevt)) {
			// Get extra fields
			foreach ($extralabelsevt as $key => $value)
				unset($_POST ["options_" . $key]);
		}

		$result=$object->addline(
						$id,
						$fk_equipementevt_type,
						$desc,
						$dateo,
						$datee,
						$fulldayevent,
						$fk_contrat,
						$fk_fichinter,
						$fk_expedition,
						$fk_project,
						$fk_user_author,
						$total_ht,
						$array_option
		);



		if ($result >= 0) {
			$db->commit();

			// Define output language
			$outputlangs = $langs;
			$newlang='';
			if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'alpha'))
				$newlang=GETPOST('lang_id', 'alpha');
			if ($conf->global->MAIN_MULTILANGS && empty($newlang))
				$newlang=$object->thirdparty->default_lang;
			if (! empty($newlang)) {
				$outputlangs = new Translate("", $conf);
				$outputlangs->setDefaultLang($newlang);
			}

			// pour la mise à jour du pdf
			equipement_create($db, $object, $object->modelpdf, $outputlangs);

			Header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
			exit;
		} else {
			$mesg=$object->error;
			$db->rollback();
		}
	}
} elseif ($action == 'updateline'
	&& $user->rights->equipement->creer
	&& GETPOST('save', 'alpha') == $langs->trans("Save")
) {
	/*
	 *  Mise a jour d'une ligne d'évènement
	 */

	$objectline = new Equipementevt($db);
	if ($objectline->fetch(GETPOST('line_id', 'int')) <= 0) {
		dol_print_error($db);
		exit;
	}
	if ($object->fetch($objectline->fk_equipement) <= 0) {
		dol_print_error($db);
		exit;
	}
	$object->fetch_thirdparty();

	$desc=GETPOST('np_desc');
	$dateo = dol_mktime(
					GETPOST('deohour', 'int'), GETPOST('deomin', 'int'), 0,
					GETPOST('deomonth', 'int'), GETPOST('deoday', 'int'), GETPOST('deoyear', 'int')
	);
	$datee = dol_mktime(
					GETPOST('deehour', 'int'), GETPOST('deemin', 'int'), 0,
					GETPOST('deemonth', 'int'), GETPOST('deeday', 'int'), GETPOST('deeyear', 'int')
	);
	$fulldayevent 	=GETPOST('fulldayevent');
	$fk_equipementevt_type =GETPOST('fk_equipementevt_type');
	$fk_contrat 	=GETPOST('fk_contrat');
	$fk_fichinter 	=GETPOST('fk_fichinter');
	$fk_expedition 	=GETPOST('fk_expedition');
	$fk_project 	=GETPOST('fk_project');
	$total_ht		=GETPOST('total_ht');

	$objectline->fk_equipementevt_type	= $fk_equipementevt_type;
	$objectline->datee			= $datee;
	$objectline->dateo			= $dateo;
	$objectline->fulldayevent	= $fulldayevent;
	$objectline->desc			= $desc;
	$objectline->duration		= $duration;
	$objectline->total_ht		= $total_ht;
	$objectline->fk_contrat		= $fk_contrat;
	$objectline->fk_fichinter	= $fk_fichinter;
	$objectline->fk_expedition	= $fk_expedition;
	$objectline->fk_project		= $fk_project;

	// Extrafields Lines
	$extrafieldsline = new ExtraFields($db);
	$extralabelsline = $extrafieldsline->fetch_name_optionals_label("Equipementevt");
	$array_options = $extrafieldsline->getOptionalsFromPost($extralabelsline);
	// Unset extrafield POST Data
	if (is_array($extralabelsline)) {
		foreach ($extralabelsline as $key => $value)
			unset($_POST ["options_" . $key]);
	}
	$objectline->array_options = $array_options;


	$result = $objectline->update();
	if ($result < 0) {
		dol_print_error($db);
		exit;
	}

	// Define output language
	$outputlangs = $langs;
	$newlang='';
	if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'alpha'))
		$newlang=GETPOST('lang_id', 'alpha');
	if ($conf->global->MAIN_MULTILANGS && empty($newlang))
		$newlang=$object->thirdparty->default_lang;
	if (! empty($newlang)) {
		$outputlangs = new Translate("", $conf);
		$outputlangs->setDefaultLang($newlang);
	}

	equipement_create($db, $object, $object->modelpdf, $outputlangs);

	Header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
	exit;
} elseif ($action == 'confirm_deleteline' && $confirm == 'yes' && $user->rights->equipement->creer) {
	/*
	 *  Supprime une ligne d'évènement AVEC confirmation
	 */

	$objectline = new Equipementevt($db);
	if ($objectline->fetch(GETPOST('line_id', 'int')) <= 0) {
		dol_print_error($db);
		exit;
	}

	$result=$objectline->deleteline();
	if ($object->fetch($id) <= 0) {
		dol_print_error($db);
		exit;
	}

	// Define output language
	$outputlangs = $langs;
	$newlang='';
	if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'alpha'))
		$newlang=GETPOST('lang_id', 'alpha');
	if ($conf->global->MAIN_MULTILANGS && empty($newlang))
		$newlang=$object->thirdparty->default_lang;
	if (! empty($newlang)) {
		$outputlangs = new Translate("", $conf);
		$outputlangs->setDefaultLang($newlang);
	}

	equipement_create($db, $object, $object->modelpdf, $outputlangs);
}


/*
 * View
 */
$extralabelsevt=$extrafieldsevt->fetch_name_optionals_label('equipementevt');

$form = new Form($db);
$formfile = new FormFile($db);

llxHeader();

if ($id > 0 || ! empty($ref)) {
	/*
	 * Affichage en mode visu
	 */

	$object->fetch($id, $ref);
	if (!$id) $id=$object->id;
	$object->fetch_thirdparty();
	$res=$object->fetch_optionals($object->id, $extralabels);

	dol_htmloutput_mesg($mesg);

	$head = equipement_prepare_head($object);

	dol_fiche_head($head, 'event', $langs->trans("Equipement"), 0, 'equipement@equipement');

	// Confirmation de la suppression d'une ligne d'intervention
	if ($action == 'ask_deleteline') {
		$ret=$form->form_confirm(
						$_SERVER["PHP_SELF"].'?id='.$object->id.'&line_id='.GETPOST('line_id', 'int'),
						$langs->trans('DeleteEquipementLine'),
						$langs->trans('ConfirmDeleteEquipementLine'), 'confirm_deleteline', '', 0, 1
		);
		if ($ret == 'html') print '<br>';
	}

	print '<table class="border" width="100%">';

	// Ref
	print '<tr><td width=250px>'.$langs->trans("Ref").'</td>';
	print '<td colspan="3">';
	$linkback = '<a href="list.php'.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';
	print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref');
	print '</td></tr>';

	// produit
	$prod=new Product($db);
	$prod->fetch($object->fk_product);
	print '<tr><td >'.$langs->trans("Product").'';

	print '</td><td colspan="3">';
	print "<a href=# onclick=\"$('#descprod').toggle();\" >".img_picto("", "edit_add")."</a>";
	print $prod->getNomUrl(1)." : ".$prod->label.'</td></tr>';
	print "<tr style='display:none' id='descprod'>";
	print '<td></td><td>'.$prod->description.'</td><tr>';

	// Numéro de version
	print '<tr><td>'.$langs->trans("VersionNumber").'</td>';
	print '<td colspan="3">';
	print $object->numversion;
	print '</td></tr>';

	// quantité modifiable et visible uniquement si supérieur à 1
	if ($object->quantity >1) {
		print '<tr><td>'.$langs->trans("Quantity").'</td>';
		print '<td colspan="3">';
		print $object->quantity;
		print '</td></tr>';
	}

	// Etat de l'équipement
	print '<tr><td>'.$langs->trans("EtatEquip").'</td>';
	print '<td colspan="3">';
	if ($object->etatequiplibelle)
		print $langs->trans($object->etatequiplibelle);
	print '</td></tr>';

	// Statut
	print '<tr><td>'.$langs->trans("Status").'</td><td>'.$object->getLibStatut(4).'</td></tr>';
	print "</table><br>";

	/*
	 * Lignes d'evènement
	 */
	$sql = 'SELECT ee.rowid, ee.description, ee.fk_equipement, ee.fk_equipementevt_type, ee.total_ht, ';
	$sql.= ' ee.fulldayevent, ee.dateo, ee.datee, eet.libelle as equipeventlib, ';
	$sql.= ' ee.fk_fichinter, ee.fk_contrat, ee.fk_expedition, ee.fk_project ';
	$sql.= ' FROM '.MAIN_DB_PREFIX.'equipementevt as ee';
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_equipementevt_type as eet on ee.fk_equipementevt_type = eet.rowid";

	$sql.= ' WHERE ee.fk_equipement = '.$id;
	$sql.= ' ORDER BY ee.dateo ASC, ee.fk_equipementevt_type';

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;

		if ($num) {
			print '<table class="noborder" width="100%">';
			print '<tr class="liste_titre">';
			print '<td width=50px><a name="add"></a>'.$langs->trans('TypeEvent').'</td>'; // ancre
			print '<td width=200px>'.$langs->trans('Description').'</td>'; // ancre
			print '<td width=100px align="center"></td>';
			print '<td width=250px align="center" ></td>';
			print '<td align="left" width=220px colspan=2>'.$langs->trans('AssociedWith').'</td>';
			print '<td align="center" colspan=3>'.$langs->trans('EquipementLineTotalHT').'</td>';
			print "</tr>\n";
		}
		$var=true;
		while ($i < $num) {
			$lineevt = new Equipementevt($db);
			$objp = $db->fetch_object($resql);
			$var=!$var;
			$editable=true;

			// Ligne en mode visu
			if ($action != 'editline' || GETPOST('line_id', 'int') != $objp->rowid) {
				print '<tr '.$bc[$var].">\n";
				print '<td >';
				if ($objp->equipeventlib)
					print $langs->trans($objp->equipeventlib);
				// type d'évènement
				print '</td>';

				// description de l'évènement de l'équipement
				print '<td >';
				print dol_htmlentitiesbr($objp->description);
				print '</td>';

				// Date complète ou pas.
				$dayordayhour=($objp->fulldayevent?"day":"dayhour");
				print '<td align="center">'.dol_print_date($db->jdate($objp->dateo), $dayordayhour).'</td>';
				print '<td align="center" >'.dol_print_date($db->jdate($objp->datee), $dayordayhour).'</td>';

				// bloc des éléments liés à l'évènement de l'équipement
				print '<td align="center" colspan=3>';

				// contrat
				if ($objp->fk_contrat > 0) {
					print "<div style='float: left;background:#E0E0E0;margin:2px;padding:2px;'>";
					$contrat = new Contrat($db);
					$contrat->fetch($objp->fk_contrat);
					print $contrat->getNomUrl(1);
					// si les clients ne sont pas les mêmes entre le client et le contrat
					// on affiche le client du contrat
					if ($object->fk_soc_client != $contrat->socid) {
						$soc = new Societe($db);
						$soc->fetch($contrat->socid);
						print "<br>".$soc->getNomUrl(1);
						$editable=false;
					}
					print '</div>';
				}


				// fiche intervention
				if ($objp->fk_fichinter > 0) {
					print "<div style='float: left;background:#E0E0E0;margin:2px;padding:2px;'";
					$fichinter = new Fichinter($db);
					$fichinter->fetch($objp->fk_fichinter);
					print $fichinter->getNomUrl(1);
					// si les clients ne sont pas les mêmes entre le client et le contrat
					// on affiche le client du contrat
					if ($object->fk_soc_client != $fichinter->socid) {
						$soc = new Societe($db);
						$soc->fetch($fichinter->socid);
						print "<br>".$soc->getNomUrl(1);
						$editable=false;
					}
					print '</div>';
				}


				// Expedition
				if ($objp->fk_expedition > 0) {
					print "<div style='float: left;background:#E0E0E0;margin:2px;padding:2px;'";
					$expedition = new Expedition($db);
					$expedition->fetch($objp->fk_expedition);
					print $expedition->getNomUrl(1);
					// si les clients ne sont pas les mêmes entre le client et le contrat
					// on affiche le client du contrat
					if ($object->fk_soc_client != $expedition->socid) {
						$soc = new Societe($db);
						$soc->fetch($expedition->socid);
						print "<br>".$soc->getNomUrl(1);
						$editable=false;
					}
					print '</div>';
				}


				// project
				if ($objp->fk_project > 0) {
					print "<div style='float: left;background:#E0E0E0;margin:2px;padding:2px;'";
					$project = new Project($db);
					$project->fetch($objp->fk_project);
					print $project->getNomUrl(1);
					// si les clients ne sont pas les mêmes entre le client et le projet
					// on affiche le client du contrat
					if ($object->fk_soc_client != $project->socid) {
						$soc = new Societe($db);
						$soc->fetch($project->socid);
						print "<br>".$soc->getNomUrl(1);
						$editable=false;
					}
					print '</div>';
				}

				print '</td>';

				// total_HT
				print '<td align="right">'.price($objp->total_ht).'</td>';

				print "</td>\n";

				// Icone d'edition et suppression
				if ($user->rights->equipement->creer) {
					print '<td align="center">';
					if ($editable) {
						print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=editline';
						print '&line_id='.$objp->rowid.'#'.$objp->rowid.'">'.img_edit().'</a>';
						print '&nbsp';
					}
					print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=ask_deleteline';
					print '&line_id='.$objp->rowid.'">'.img_delete().'</a></td>';
				} else
					print '<td >&nbsp;</td>';
				print '</tr>';



				// extrafields on equipementevent
				if (!empty($extrafieldsevt->attribute_label)) {
					$lineevt->id = $objp->rowid;
					$res=$lineevt->fetch_optionals($lineevt->id, $extralabelsevt);
					foreach ($extrafieldsevt->attribute_label as $key=>$label) {
						$value=(isset($_POST["options_".$key])?$_POST["options_".$key]:$lineevt->array_options["options_".$key]);
						print '<tr '.$bc[$var].">\n";
						print '<td>'.$label.'</td><td colspan=8>';
						print $extrafieldsevt->showOutputField($key, $value);
						print '</td></tr>'."\n";
					}
				}
			}

			// Ligne en mode update
			if ($action == 'editline'
				&& $user->rights->equipement->creer
				&& GETPOST('line_id', 'int') == $objp->rowid
			) {
				print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'#'.$objp->rowid.'" method="post">';
				print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
				print '<input type="hidden" name="action" value="updateline">';
				print '<input type="hidden" name="id" value="'.$object->id.'">';
				print '<input type="hidden" name="line_id" value="'.GETPOST('line_id', 'int').'">';
				print '<tr '.$bc[$var].'>';
				print '<td width=100px>'.$langs->trans('TypeofEquipementEvent').'</td><td>';
				print select_equipementevt_type($objp->fk_equipementevt_type, 'fk_equipementevt_type', 1, 1);
				// type d'évènement
				print '</td>';

				print '<td align="left" >'.$langs->trans('Author').'</td><td>';
				print $form->select_dolusers($objp->fk_user_author, 'userid', 0, null, 0, null, null, 0, 56).'</td>';

				// lien vers les contrats si le module est actif
				if ($conf->contrat->enabled) {
					print '<td align="left">'.$langs->trans("Contrats").'</td>';
					print '<td align="left" colspan=2>';
					if ($user->rights->contrat->lire)
						print select_contracts($objp->fk_contrat, $object->fk_soc_client, 'fk_contrat', 1, 1);
					else
						print "<input type=hidden value='".$objp->fk_contrat."' name='fk_contrat'>";
					print '</td>';
				} else
					print '<td colspan=3></td>';

				print '<td align="center" colspan=5 rowspan=4 valign="middle" >';
				print '<input type="text" name="total_ht" size="5" value="'.price($objp->total_ht).'"><br>';
				print '<br><input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
				print '<br><input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
				print '</td></tr>';

				print '<tr '.$bc[$var].">\n";
				// description de l'évènement de l'équipement
				print '<td rowspan=3 colspan=2>';
				// editeur wysiwyg
				require_once(DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php");
				$doleditor=new DolEditor(
								'np_desc', $objp->description, '', 100, 'dolibarr_details',
								'', false, true, $conf->global->FCKEDITOR_ENABLE_DETAILS, ROWS_4, 60
				);
				$doleditor->Create();
				print '</td>';
				//
				// Date evenement début
				print '<td align="left" >'.$langs->trans('Dateo').'</td>';
				print '<td align="left" >';
				$form->select_date($objp->dateo, 'deo', 1, 1, 0, "addequipevt");
				print '</td>';

				// lien vers les interventions si le module est actif
				if ($conf->ficheinter->enabled ) {
					print '<td align="left">'.$langs->trans("Interventions").'</td>';
					print '<td align="left" colspan=2>';
					if ($user->rights->ficheinter->lire)
						print select_interventions($objp->fk_fichinter, $object->fk_soc_client, 'fk_fichinter', 1, 1);
					else
						print "<input type=hidden value='".$objp->fk_fichinter."' name='fk_fichinter'>";
					print '</td>';
				} else
					print '<td colspan=3 >&nbsp;</td>';

				print '</tr>';
				print '<tr '.$bc[$var].">\n";

				// Date evenement fin
				print '<td align="left" >'.$langs->trans("Datee").'</td><td>';
				$form->select_date($objp->datee, 'dee', 1, 1, 0, "addequipevt");
				print '</td>';

				//
				if ($conf->expedition->enabled) {
					print '<td align="left">'.$langs->trans("Expeditions").'</td>';
					print '<td align="left" colspan=2>';
					if ($user->rights->expedition->lire)
						print select_expeditions($objp->fk_expedition, $object->fk_soc_client, 'fk_expedition', 1, 1, 1);
					else
						print "<input type=hidden value='".$objp->fk_expedition."' name='fk_expedition'>";

					print '</td>';
				} else
					print '<td colspan=3></td>';
				print '</tr>' . "\n";

				print '<tr '.$bc[$var].">\n";

				// fullday event
				print '<td align="center" colspan=2>';
				if ($objp->fulldayevent)
					$checked=" checked='checked' ";
				print '<input type="checkbox" id="fulldayevent" value=1 '.$checked.' name="fulldayevent" >';
				print "&nbsp;".$langs->trans("EventOnFullDay");
				print '</td>';

				//
				if ($conf->projet->enabled ) {
					print '<td align="left">'.$langs->trans("Projects").'</td>';
					print '<td align="left" colspan=2>';
					if ($user->rights->projet->lire)
						print select_projects($objp->fk_project, $object->fk_soc_client, 'fk_project', 1, 1);
					else
						print "<input type=hidden value='".$objp->fk_project."' name='fk_project'>";

					print '</td>';
				} else
					print '<td colspan=3></td>';
				print '</tr>' . "\n";


				// extrafields on equipementevent
				if (!empty($extrafieldsevt->attribute_label)) {
					$lineevt->id = $objp->rowid;
					$res=$lineevt->fetch_optionals($lineevt->id, $extralabelsevt);
					foreach ($extrafieldsevt->attribute_label as $key=>$label) {
						$value=(isset($_POST["options_".$key])?$_POST["options_".$key]:$lineevt->array_options["options_".$key]);
						print '<tr '.$bc[$var].">\n";
						print '<td>'.$label.'</td><td colspan=8>';
						print $extrafieldsevt->showInputField($key, $value);
						print '</td></tr>'."\n";
					}
				}

				print "</form>\n";
			}

			$i++;
		}
		if ($num)
			print '</table>';

		$db->free($resql);

		/*
		 * Add line on a le droit de créer un évènement à tous moment
		 */
		if ($action <> 'editline' && $user->rights->equipement->creer ) {
			print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=addline" name="addequipevt" method="post">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="id" value="'.$object->id.'">';
			print '<input type="hidden" name="action" value="addline">';

			print '<table class="noborder" width="100%">';
			print '<tr class="liste_titre">';
			print '<td colspan=2 width=150px><a name="add"></a>'.$langs->trans('Description').'</td>'; // ancre
			print '<td width=300px colspan=2 align="center" ></td>';
			print '<td align="left" width=200px colspan=2>'.$langs->trans('AssociatedWith').'</td>';
			print '<td align="center" colspan=3>'.$langs->trans('EquipementLineTotalHT').'</td>';
			print "</tr>\n";


			// si il s'agit d'un évènement prédefinie
			$predeftypeid="";
			$predefdescription="";
			if (GETPOST("prefefid") >0 ) {
				$sql = "SELECT eep.description";
				$sql.= " FROM ".MAIN_DB_PREFIX."c_equipementevt_type as eet";
				$sql.= " , ".MAIN_DB_PREFIX."equipementevt_predef as eep";
				$sql.= " WHERE eep.fk_equipementevt_type = eet.rowid";
				$sql.= " AND eep.fk_product = ".$object->fk_product;
				$sql.= " AND eep.fk_equipementevt_type = ".GETPOST("prefefid");
				$result=$db->query($sql);
				if ($result) {
					$objp = $db->fetch_object($result);
					$predeftypeid=GETPOST("prefefid");
					$predefdescription=$objp->description;
				}
			}
			$var=false;

			print '<tr '.$bc[$var].">\n";
			// type d'évènement
			print '<td width=100px>'.$langs->trans('TypeofEquipementEvent').'</td><td>';
			print select_equipementevt_type($predeftypeid, 'fk_equipementevt_type', 1, 1);
			print '</td>';

			print '<td align="left" >'.$langs->trans("Author").'</td>';
			print '<td align="left" >';
			print $form->select_dolusers($user->id, 'userid', 0, null, 0, null, null, 0, 56).'</td>';

			// lien vers les contrats si le module est actif
			if ($conf->contrat->enabled) {
				print '<td align="left">'.$langs->trans("Contrats").'</td>';
				print '<td align="left">';
				print select_contracts('', $object->fk_soc_client, 'fk_contrat', 1, 1);
				print '</td>';
			}
			else
				print '<td colspan=2></td>';

			print '<td align="center" valign="top" rowspan=4>';
			print '<input type="text" name="total_ht" size="5" value="">';
			print '<br><br><br>';
			print '<input type="submit" class="button" value="'.$langs->trans('Add').'" name="addline">';



			print '</td></tr>';
			print '<tr '.$bc[$var].">\n";

			// description de l'évènement de l'équipement
			print '<td rowspan=3 colspan=2>';
			// editeur wysiwyg
			require_once(DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php");
			$doleditor=new DolEditor(
							'np_desc', ($predefdescription ? $predefdescription :GETPOST('np_desc')),
							'', 100, 'dolibarr_details', '', false, true,
							$conf->global->FCKEDITOR_ENABLE_DETAILS, ROWS_6, 60
			);
			$doleditor->Create();
			print '</td>';
			//

			// Date evenement début
			print '<td align="left" >'.$langs->trans("Dateo").'</td>';
			print '<td align="left" >';
			$timearray=dol_getdate(mktime());
			if (!GETPOST('deoday', 'int'))
				 $timewithnohour=dol_mktime(0, 0, 0, $timearray['mon'], $timearray['mday'], $timearray['year']);
			else
				$timewithnohour=dol_mktime(
								GETPOST('deohour', 'int'), GETPOST('deomin', 'int'), 0,
								GETPOST('deomonth', 'int'), GETPOST('deoday', 'int'), GETPOST('deoyear', 'int')
				);
			$form->select_date($timewithnohour, 'deo', 1, 1, 0, "addequipevt");
			print '</td>';

			// lien vers les interventions si le module est actif
			if ($conf->ficheinter->enabled && $user->rights->ficheinter->lire) {
				print '<td align="left">'.$langs->trans("Interventions").'</td>';
				print '<td align="left">';
				print select_interventions('', $object->fk_soc_client, 'fk_fichinter', 1, 1);
				print '</td>';
			} else
				print '<td colspan=2></td>';

			print '</tr>';
			print '<tr '.$bc[$var].">\n";

			// Date evenement fin
			print '<td align="left" >'.$langs->trans("Datee").'</td>';
			print '<td align="left" >';
			$timearray=dol_getdate(mktime());
			if (!GETPOST('deeday', 'int'))
				$timewithnohour=dol_mktime(0, 0, 0, $timearray['mon'], $timearray['mday'], $timearray['year']);
			else
				$timewithnohour=dol_mktime(
								GETPOST('deehour', 'int'), GETPOST('deemin', 'int'), 0,
								GETPOST('deemonth', 'int'), GETPOST('deeday', 'int'), GETPOST('deeyear', 'int')
				);
			$form->select_date($timewithnohour, 'dee', 1, 1, 0, "addequipevt");
			print '</td>';

			//
			if ($conf->expedition->enabled) {
				print '<td align="left">'.$langs->trans("Expeditions").'</td>';
				print '<td align="left">';
				print select_expeditions('', $object->fk_soc_client, 'fk_expedition', 1, 1, 1);
				print '</td>';
			} else
				print '<td colspan=2></td>';

			print '</tr>';

			print '<tr '.$bc[$var].">\n";

			print '<td></td>';
			print '<td align="left" >';
			print '<input type="checkbox" id="fulldayevent" value=1 name="fulldayevent" >';
			print "&nbsp;".$langs->trans("EventOnFullDay");
			print '</td>';

			// lien vers les projet si le module est actif
			if (! empty($conf->projet->enabled)) {
				print '<td align="left">';
				print $langs->trans("Project");
				print '</td>';
				print '<td align="left">';
				print select_projects('', $object->fk_soc_client, 'fk_project', 1, 1);
				print '</td>';
			} else
				print '<td colspan=2></td>';

			print '</tr>';
			// extrafields on equipementevent
			if (!empty($extrafieldsevt->attribute_label)) {
				foreach ($extrafieldsevt->attribute_label as $key=>$label) {
					print '<tr '.$bc[$var].">\n";
					print '<td>'.$label.'</td><td>';
					print $extrafieldsevt->showInputField($key, "");
					print '</td></tr>'."\n";
				}
			}
			if (! $num)
				print '</table>';
			print '</form>';
		}
		if ($num)
			print '</table>';
	} else
		dol_print_error($db);
	print '</div>';
	print "\n";
}
llxFooter();
$db->close();