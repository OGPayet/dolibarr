<?php
/* Copyright (C) 2012-2016	  Charlie Benke	<charlie@patas-monkey.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *	\file	   htdocs/equipement/tabs/contrat.php
 *	\brief	  List of all Events of equipements associated with a contract
 *	\ingroup	equipement
 */
$res=@include("../../main.inc.php");					// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../../main.inc.php");		// For "custom" directory

require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/product/stock/class/entrepot.class.php");
require_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/contract.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

$langs->load("companies");
$langs->load("synergiestechcontrat@synergiestechcontrat");
$langs->load("contracts");

$id=GETPOST('id', 'int');
$action=GETPOST('action','alpha');

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user, 'contrat', $id);
if (!$user->rights->synergiestechcontrat->terminate) accessforbidden();

$langs->load("link");
if (empty($relativepathwithnofile)) $relativepathwithnofile='';
if (empty($permtoedit)) $permtoedit=-1;
$pagenext = $page + 1;

/*
 *	View
 */

$form = new Form($db);
$formfile = new Formfile($db);
llxHeader();


$object = new contrat($db);
$result = $object->fetch($id);
$object->fetch_thirdparty();

if($action == "terminate") {
	$error = 0;
	if (!GETPOST('targetdatemonth') && !GETPOST('targetdateday') && !GETPOST('targetdateyear')) {
		setEventMessages("Veuillez ajouter une date effective de résiliation pour résilier ce contrat", null, "errors");
		$error++;
	}
	if (!GETPOST('realdatemonth') && !GETPOST('realdateday') && !GETPOST('realdateyear')) {
		setEventMessages("Veuillez ajouter une date souhaitée de résiliation pour résilier ce contrat", null, "errors");
		$error++;
	}
	if($error == 0) {
		$targetdate='';
		$realdate='';
		if (GETPOST('targetdatemonth') && GETPOST('targetdateday') && GETPOST('targetdateyear'))
		{
			$targetdate=dol_mktime(GETPOST('targetdatehour'), GETPOST('targetdatemin'), 0, GETPOST('targetdatemonth'), GETPOST('targetdateday'), GETPOST('targetdateyear'));
		}
		if (GETPOST('realdatemonth') && GETPOST('realdateday') && GETPOST('realdateyear'))
		{
			$realdate=dol_mktime(GETPOST('realdatehour'), GETPOST('realdatemin'), 0, GETPOST('realdatemonth'), GETPOST('realdateday'), GETPOST('realdateyear'));
		}

		$target_dir = $conf->contrat->dir_output.'/'.$object->ref.'/';
		if (! is_dir($target_dir)) {
			dol_mkdir($target_dir);
		}
		$target_file = $target_dir ."Document_de_resiliation_".basename($_FILES["document"]["name"]);

		$result = dol_move($_FILES["document"]["tmp_name"], $target_file, 0, 1, 1);
		if ($result) {
			setEventMessages("Justificatif de résiliation enregistré avec succès", null, "mesgs");
		} else {
			setEventMessages("Problème lors de l'enregistrement du justificatif de résiliation", null, "errors");
			$error++;
		}

		$object->array_options['options_targetdate'] = $targetdate;
		$object->array_options['options_realdate'] = $realdate;

		if($error == 0) {
			$object->update();
		}
	}
}

$head = contract_prepare_head($object);

dol_fiche_head($head, 'terminate', $langs->trans("Contract"), 0, 'contract');

print '<table class="border" width="100%">';

// Ref
print '<tr><td width="25%">'.$langs->trans("Ref").'</td><td>';
print $form->showrefnav($object, 'ref', '', 1, 'ref', 'ref');
print '</td></tr>';

// Societe
print "<tr><td>".$langs->trans("Company")."</td><td>".$object->thirdparty->getNomUrl(1)."</td></tr>";
print '</table>';

print '<b>Résiliation de ce contrat et tous ses services</b>';

print '<form name="form_contract" action="'.$_SERVER["PHP_SELF"].'" method="post" enctype="multipart/form-data">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="terminate">';
print '<input type="hidden" name="id" value="'.$object->id.'">';

//Target Date
print '<table class="paddingtopbottomonly" width="100%">';
print '<tr><td>Date souhaitée de résiliation</td>';
print '<td colspan="2" align="left">';
print $form->select_date($object->array_options['options_targetdate'],"targetdate",0,0,0,'',1,0,1);
print '</td></tr>';

//Real Date
print '<tr><td>Date effective de résiliation</td>';
print '<td colspan="2" align="left">';
print $form->select_date($object->array_options['options_realdate'],"realdate",0,0,0,'',1,0,1);
print '</td></tr>';

//Document
print '<tr><td>Justificatif de résiliation</td>';
print '<td colspan="2" align="left">';
print '<input class="flat minwidth400" type="file" name="document" id="document"/>';
print '</td></tr>';

print '</table>';
print '<br/>';
print '<div class="center">';
print '<input type="submit" class="button" value="Résilier ce contrat">';
print '</div>';
print '</form>';
llxFooter();
$db->close();
