<?php
/* Copyright (C) 2014-2017	Charlie BENKE	<charlie@patas-monkey.Com>
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
 *	\file	   htdocs/customlink/fiche.php
 *	\ingroup	tools
 *	\brief	  customelink card
 */

$res=@include("../main.inc.php");					// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res)
	$res=@include("../../main.inc.php");		// For "custom" directory

dol_include_once('/customlink/class/customlink.class.php');
dol_include_once('/customlink/core/lib/customlink.lib.php');
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

$langs->load("customlink@customlink");

$rowid=GETPOST('rowid', 'alpha');
$action=GETPOST('action', 'alpha');
//$fk_entrepot=GETPOST('fk_entrepot');
$backtopage=GETPOST('backtopage', 'alpha');
$type_source=GETPOST('type_source', 'alpha');
$ref_source=GETPOST('ref_source', 'alpha');
$type_target=GETPOST('type_target', 'alpha');
$ref_target=GETPOST('ref_target', 'alpha');

if (!$user->rights->customlink->lire) accessforbidden();

$object = new Customlink($db);

/*
 * Actions
 */


if ($action == 'add' && $user->rights->customlink->creer) {
	$error=0;
	// on controle la source
	if (empty($type_source)) {
		setEventMessage($langs->trans("ErrorFieldRequired", $langs->transnoentities("TypeSource")), 'errors');
		$error++;
	} else {	// on controle que la ref est bien saisie
		if (empty($ref_source)) {
			setEventMessage($langs->trans("ErrorFieldRequired", $langs->transnoentities("RefSource")), 'errors');
			$error++;
		} else {	// on controle qu'il y a bien quelquechose de lié
			$object->fk_source = $object->get_idlink($type_source, $ref_source);
			if ($object->fk_source <=0 ) {
				setEventMessage($langs->trans("ErrorRefNotFound", $langs->transnoentities("RefSource")), 'errors');
				$error++;
			}
		}
	}

	// on controle la target
	if (empty($type_target)) {
		setEventMessage($langs->trans("ErrorFieldRequired", $langs->transnoentities("TypeTarget")), 'errors');
		$error++;
	} else {	// on controle que la ref est bien saisie
		if (empty($ref_target)) {
			setEventMessage($langs->trans("ErrorFieldRequired", $langs->transnoentities("RefTarget")), 'errors');
			$error++;
		} else {	// on controle qu'il y a bien quelquechose de lié
			$object->fk_target = $object->get_idlink($type_target, $ref_target);
			if ($object->fk_target <=0 ) {
				setEventMessage($langs->trans("ErrorRefNotFound", $langs->transnoentities("RefTarget")), 'errors');
				$error++;
			}
		}
	}

	if (! $error) {
		// les fk_ sont déjà renseigné
		$object->type_target = $_POST["type_target"];
		$object->type_source = $_POST["type_source"];
		$object->ref_source	 = $_POST["ref_source"];
		$object->ref_target	 = $_POST["ref_target"];

		$result = $object->create($user);
		if ($result == -1) {
			$langs->load("errors");
			setEventMessage($object->error, 'errors');
			$error++;
		}

		if (! $error) {
			// on se positionne sur les même sources dans la liste
			header("Location:index.php?refelement=".$object->ref_source."&typeelement=".$object->type_source);
			exit;
		} else
			$action = '';
	} else
		$action = '';
} elseif ($action == 'setUpdate' && $user->rights->customlink->modifier) {
	// met à jour la liste
	$object->rowid=			GETPOST("rowid");
	$object->type_target=	GETPOST("type_target");
	$object->type_source=	GETPOST("type_source");
	$object->ref_source=	GETPOST("ref_target");
	$object->ref_source=	GETPOST("ref_source");

	$result=$object->update();
	if ($result<0) {
		setEventMessage($object->error, 'errors');
	}
} elseif ($action == 'delete' && $user->rights->customlink->supprimer) {
	$ret=$object->fetch($rowid);
	$ret=$object->delete($user);
	// retour à la liste
	header("Location:index.php");
	exit;
}
/*
 *	View
 */

$form = new Form($db);
$formfile = new FormFile($db);

$help_url="EN:Module_customlink|FR:Module_customlink|ES:M&oacute;dulo_customlink";
llxHeader("", $langs->trans("CustomLink"), $help_url);

// accès direct = mode création
if ($action == '' && $user->rights->customlink->creer) {
	/*
	 * Create
	 */
	print_fiche_titre($langs->trans("CreateCustomLink"));

	print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

	print '<table class="border" width="30%">';

	print '<tr><th colspan=2>'.$langs->trans("Source").'</th>';
	print '<th></th>';
	print '<th colspan=2>'.$langs->trans("Target").'</th></tr>';

	print '<tr><td >'.$langs->trans("Type").'</td><td>';
	select_element_type($type_source, 'type_source', 0, 1);
	print '</td>';
	print '<td></td><td >'.$langs->trans("Type").'</td><td>';
	select_element_type($type_target, 'type_target', 0, 1);
	print '</td></tr>';
	print '<tr><td >'.$langs->trans("Ref").'</td><td>';
	print '<input type="text" name=ref_source value="'.$ref_source.'">';
	print '</td>';
	print '<td></td><td >'.$langs->trans("Ref").'</td><td>';
	print '<input type="text" name="ref_target" value="'.$ref_target.'">';
	print '</td></tr>';
	print '<tr><td colspan=5>';
	print '<div class="tabsAction">';
	print '<input type="submit" class="button" value="'.$langs->trans("CreateCustomLink").'">';
	if (! empty($backtopage)) {
		print ' &nbsp; &nbsp; ';
		print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	}
	print '</div>';
	print '</td></tr>';
	print '</table>';
	print '</form>';
} elseif ($action == 'update' && $user->rights->localise->creer) {
	print_fiche_titre($langs->trans("UpdateLocalise"));

	$ret=$object->fetch($rowid);

	print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="setUpdate">';
	print '<input type="hidden" name="rowid" value="'.$rowid.'">';
	print '<table class="border" width="100%">';

	// description
	print '<tr><td>'.$langs->trans("Description").'</td>';
	print '<td colspan=2><input size="50" type="text" name="label" value="'.$object->label.'">';
	print '</td></tr>';

	// on définie le niveau d'arborescence en fonction du nombre de niveau saisie
	// arborescence level definition
	print '<tr><td>'.$langs->trans("Level1SizeAndLabel").'</td><td>'.$object->sizearbo1.'</td><td>';
	print '&nbsp;<input size="20" type="text" name="Level1title" value="'.$object->labelarbo1.'"></td></tr>';
	print '<tr><td>'.$langs->trans("Level2SizeAndLabel").'</td><td>'.$object->sizearbo2.'</td><td>';
	if ($object->sizearbo2 > 0)
		print '&nbsp;<input size="20" type="text" name="Level2title" value="'.$object->labelarbo2.'">';
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("DateoLong").'</td><td colspan="3">';
	print '&nbsp;<input size="20" type="text" name="dateo" value="'.$object->dateo.'"></td></tr>';
	print '<tr><td>'.$langs->trans("DateeLong").'</td><td colspan="3">';
	print '&nbsp;<input size="20" type="text" name="datee" value="'.$object->datee.'"></td></tr>';

	print '<tr><td>'.$langs->trans("active").'</td><td align=left colspan=3>';
	print $form->selectyesno('active', $object->active, 1);
	print '</td></tr>';

	print '</table>';

	print '<br><center>';
	print '<input type="submit" class="button" value="'.$langs->trans("Update").'">';
	if (! empty($backtopage)) {
		print ' &nbsp; &nbsp; ';
		print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	}
	print '</center>';
	print '</form>';
} else {
	/*
	 * Show
	 */
	$ret=$object->fetch($rowid);
	//var_dump($object);

	print_fiche_titre($langs->trans("ViewLocalise"));

	print '<table class="border" width="100%">';

	$linkback = '<a href="'.dol_buildpath('index.php', 1).'">'.$langs->trans("BackToList").'</a>';

	// Define a complementary filter for search of next/prev ref.
	print $code;
	//print $form->showrefnav($object, 'code', $linkback, 1, 'code', 'code');
	print '</td></tr>';

	// description
	print '<tr><td>'.$langs->trans("Description").'</td><td colspan=2>'.$object->label.'</td></tr>';

	// on définie le niveau d'arborescence en fonction du nombre de niveau saisie
	// arborescence level definition

	print '<tr><td>'.$langs->trans("Level1SizeAndLabel").'</td><td>'.$object->sizearbo1.'</td><td>';
	print '&nbsp;'.$object->labelarbo1.'</td></tr>';
	// Dates
	print '<tr><td>'.$langs->trans("DateoLong").'</td><td colspan="3">'.dol_print_date($object->dateo, 'day').'</td></tr>';
	print '<tr><td>'.$langs->trans("DateeLong").'</td><td colspan="3">'.dol_print_date($object->datee, 'day').'</td></tr>';

	print '<tr><td>'.$langs->trans("active").'</td><td colspan=3>'.yn($object->active).'</td></tr>';
	print '</table>';

	/*
	 * Boutons actions de la liste
	 */
	print '<div class="tabsAction">';

	if ($user->rights->localise->creer)
		print '<a class="butAction" href="fiche.php?rowid='.$object->rowid.'&action=update">'.$langs->trans('Update').'</a>';
	else
		print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans('Update').'</a>';

	if ($user->rights->localise->supprimer)
		print '<a class="butAction" href="fiche.php?rowid='.$object->rowid.'&action=delete">'.$langs->trans('Delete').'</a>';
	else
		print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans('Delete').'</a>';

	print "<br>\n";
	print '</div>';
}
llxFooter();
$db->close();