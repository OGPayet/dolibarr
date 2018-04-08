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

$res=@include("../../main.inc.php");					// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../../main.inc.php");		// For "custom" directory

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
	&& is_readable(dol_buildpath("/equipement/core/modules/equipement/".$conf->global->EQUIPEMENT_ADDON.".php")))
	dol_include_once("/equipement/core/modules/equipement/".$conf->global->EQUIPEMENT_ADDON.".php");



$langs->load("companies");
$langs->load("products");
$langs->load("equipement@equipement");

$action				= GETPOST('action', 'alpha');
$id					= GETPOST('id', 'int');
$ref				= GETPOST('ref', 'alpha');
$productid			= GETPOST('productid', 'int');
$action				= GETPOST('action', 'alpha');
$confirm			= GETPOST('confirm', 'alpha');
$mesg				= GETPOST('msg', 'alpha');
$socFournid			= GETPOST('fk_soc_fourn', 'alpha');
$socClienid			= GETPOST('fk_soc_client', 'alpha');
$nbAddEquipement	= GETPOST('nbAddEquipement', 'int');
//serialMethod		= GETPOST('serialMethod', 'int');


// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'equipement', $id, 'equipement', '', 'fk_soc_client');


$object = new Equipement($db);
$extrafieldsevt = new ExtraFields($db);
$extralabelsevt = $extrafieldsevt->fetch_name_optionals_label("equipementevt");



/*
 * Actions
 */


// Add line
if ($action == "addline" && $user->rights->equipement->creer) {

	// on r�cup�re les sn � utilser
	$separatorlist=$conf->global->EQUIPEMENT_SEPARATORLIST;
	if ($separatorlist == "__N__")
		$separatorlist="\n";
	if ($separatorlist == "__B__")
		$separatorlist="\b";

	$tblEquipementRef=explode(($separatorlist ? $separatorlist : ";"),
			GETPOST("listEquipementRef"));

	//$db->begin();
	$error=0;
	foreach ($tblEquipementRef as $keyEquipement) {
		// pour g�rer le cas du dernier ; foireux
		if ($keyEquipement) {
			$ret=$object->fetch(0, $keyEquipement);
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
			$fulldayevent 			= GETPOST('fulldayevent');
			$fk_equipementevt_type	= GETPOST('fk_equipementevt_type');
			$fk_contrat 			= GETPOST('fk_contrat');
			$fk_fichinter			= GETPOST('fk_fichinter');
			$fk_expedition			= GETPOST('fk_expedition');
			$fk_project				= GETPOST('fk_project');
			$fk_user_author			= GETPOST('userid');
			$total_ht				= GETPOST('total_ht');


			// Extrafields
			$array_option = $extrafieldsevt->getOptionalsFromPost($extralabelsevt, $predef);
			// Unset extrafield
			if (is_array($extralabelsevt))  {
				// Get extra fields
				foreach ($extralabelsevt as $key => $value)
					unset($_POST ["options_" . $key]);
			}

			$result=$object->addline(
							$object->id,
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
			if (!result) {
				$error++;
				$mesg.=$object->error."<br>";
			}
		}
	}
	if ($error == 0) {
	//	$db->commit();
		$action="";
	} else {
		$mesg=$msgerror;
	//	$db->rollback();
	}
}

/*
 * Create Events
 */
$extralabelsevt=$extrafieldsevt->fetch_name_optionals_label('equipementevt');

$form = new Form($db);
$formfile = new FormFile($db);

llxHeader();

/*
 * Affichage en mode visu
 */


print_fiche_titre($langs->trans("AddEquipementEvents"));

dol_htmloutput_mesg($mesg);


if ( $user->rights->equipement->creer ) {
	print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=addline" name="addequipevt" method="post">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';
	print '<input type="hidden" name="action" value="addline">';

	print '<br><br>';
	print '<table class="border" width="100%">';
	print '<tr class="liste_titre">';
	print '<th width=100px>'.$langs->trans('ListEquipementToAddEvents').'</th>';
	print '<th width=150px>'.$langs->trans('Description').'</th>';
	print '<th width=100px colspan=3 align="center" ></th>';
	print '<th align="left" width=200px colspan=2>'.$langs->trans('AssociatedWith').'</th>';
	print '<th align="center" >'.$langs->trans('EquipementLineTotalHT').'</th>';
	print "</tr>\n";

	// Ajout ligne d'intervention
	$var=false;

	print '<tr '.$bc[$var].">\n";
	print '<td rowspan=4 valign=top>';
	print '<textarea name="listEquipementRef" cols="60" rows="'.ROWS_6.'"></textarea>';
	print '</td>';
	// type d'�v�nement
	print '<td width=100px>'.$langs->trans('TypeofEquipementEvent').'</td><td>';
	print select_equipementevt_type('','fk_equipementevt_type',1,1);
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
	} else
		print '<td colspan=2></td>';

	print '<td align="center" valign="top" rowspan=4>';
	print '<input type="text" name="total_ht" size="5" value="">';
	print '<br><br><br>';
	print '<input type="submit" class="button" value="'.$langs->trans('Add').'" name="addline">';



	print '</td></tr>';
	print '<tr '.$bc[$var].">\n";

	// description de l'�v�nement de l'�quipement
	print '<td rowspan=3 colspan=2>';
	// editeur wysiwyg
	require_once(DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php");
	$doleditor=new DolEditor(
					'np_desc', GETPOST('np_desc'), '', 100, 'dolibarr_details',
					'', false, true, $conf->global->FCKEDITOR_ENABLE_DETAILS, ROWS_4, 60
	);
	$doleditor->Create();
	print '</td>';
	//

	// Date evenement d�but
	print '<td align="left" >'.$langs->trans("Dateo").'</td>';
	print '<td align="left" >';
	$timearray=dol_getdate(mktime());
	if (!GETPOST('deoday','int'))
		$timewithnohour=dol_mktime(0, 0, 0, $timearray['mon'], $timearray['mday'], $timearray['year']);
	else
		$timewithnohour=dol_mktime(
						GETPOST('deohour', 'int'), GETPOST('deomin', 'int'), 0,
						GETPOST('deomonth', 'int'), GETPOST('deoday', 'int'), GETPOST('deoyear', 'int')
		);
	$form->select_date($timewithnohour, 'deo', 1, 1, 0, "addequipevt");
	print '</td>';

	// lien vers les interventions si le module est actif
	if ($conf->ficheinter->enabled) {
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
		print select_expeditions('', $object->fk_soc_client, 'fk_expedition', 1, 1);
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

	// add a operation list
	if ($conf->global->EQUIPEMENTACTIVE =="1") {

	}
	print '</table>';
	print '</form>';
}

print '</div>'."\n";

llxFooter();
$db->close();
