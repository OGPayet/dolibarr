<?php
/* Copyright (C) 2015-2018	Charlie BENKE	<charlie@patas-monkey.com>
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
 *	  \file	   htdocs/myfield/card.php
 *	  \ingroup	myfield
 *		\brief	  myfield card
 */

$res=@include("../main.inc.php");					// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../main.inc.php");		// For "custom" directory

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

require_once 'core/lib/myfield.lib.php';
require_once 'class/myfield.class.php';

$langs->load("myfield@myfield");

$rowid		= GETPOST('rowid', 'int');
$action		= GETPOST('action', 'alpha');
$typefield	= GETPOST('typefield');

// Security check
if (!$user->rights->myfield->lire) accessforbidden();

/*
 *	Actions
 */
if ($action == 'add'
	&& $user->rights->myfield->setup) {

	$myfield = new Myfield($db);

	$label=GETPOST("label");
	// libellé de l'onglet obligatoire
	if (empty($label)
		&& $_POST["button"] != $langs->trans("Cancel")) {
		$mesg=$langs->trans("ErrorFieldRequired", $langs->transnoentities("Label"));
		$action = 'create';
	}

	// si on peu toujours créer un onglet (pas d'erreur)
	if ($action == 'add') {
		if ($_POST["button"] != $langs->trans("Cancel")) {
			$myfield->label			= trim($label);					// libellé du champs
			$myfield->context		= trim($_POST["context"]);		// nom du context où il se trouve
			$myfield->author		= trim($_POST["author"]);		// créateur du champs, sert à rien
			$myfield->color			= trim($_POST["color"]);		// couleur si mise en avant
			$myfield->active		= trim(GETPOST("activemode"));	// mode d'affichage du champs par défaut à non
			$myfield->replacement	= trim($_POST["replacement"]);	// text de remplacement par un autre
			$myfield->initvalue		= trim($_POST["initvalue"]);	// valeur par défaut si besoin
			$myfield->compulsory	= trim($_POST["compulsory"]);	// valeur par défaut si besoin
			$myfield->sizefield		= trim($_POST["sizefield"]);	// valeur par défaut si besoin
			$myfield->movefield		= trim($_POST["movefield"]);	// valeur par défaut si besoin
			$myfield->formatfield	= trim($_POST["formatfield"]);	// valeur par défaut si besoin
			$myfield->typefield		= trim($_POST["typefield"]);
			$myfield->querydisplay		= trim($_POST["querydisplay"]);
			$id=$myfield->create($user->id);

			if ( $id > 0 ) {	// la saisie du nom de la table est obligatoire sinon on ne crée pas la table
				header("Location: ".$_SERVER["PHP_SELF"].'?rowid='.$id);
				exit;
			} else {
				$mesg=$myfield->error;
				$action = 'create';
			}
		} else {
			// la saisie du nom du champ est obligatoire sinon on ne crée pas le field
			header("Location: list.php");
			exit;
		}

	}
}

if ($action == 'update' && $user->rights->myfield->setup) {
	if ($_POST["button"] != $langs->trans("Cancel")) {
		$label=GETPOST("label");
		// libellé de l'onglet obligatoire
		if (empty($label)) {
			$mesg=$langs->trans("ErrorFieldRequired", $langs->transnoentities("Label"));
			$action = 'edit';
		} else {
			$myfield = new Myfield($db);
			$myfield->rowid			= $rowid;
			$myfield->label			= trim($_POST["label"]);
			$myfield->context		= trim($_POST["context"]);
			$myfield->author		= trim($_POST["author"]);
			$myfield->color			= trim($_POST["color"]);
			$myfield->initvalue		= trim($_POST["initvalue"]);
			$myfield->active		= trim($_POST["activemode"]);
			$myfield->replacement	= trim($_POST["replacement"]);
			$myfield->compulsory	= trim($_POST["compulsory"]);
			$myfield->sizefield		= trim($_POST["sizefield"]);
			$myfield->movefield		= trim($_POST["movefield"]);
			$myfield->formatfield	= trim($_POST["formatfield"]);
			$myfield->querydisplay	= trim($_POST["querydisplay"]);

			$myfield->update($user);
			header("Location: list.php");
			exit;
		}
	}


}

if ( $action == 'delete'
		&& $user->rights->myfield->supprimer) {
	// you delete customtabs but not the table created
	$myfield = new Myfield($db);
	$myfield->delete($rowid);
	header("Location: list.php");
	exit;
}
if ( $action == 'importation'
		&& $user->rights->myfield->setup) {
	if (GETPOST("importexport")) {
		$myfields = new Myfield($db);
		$result=$myfields->importlist(GETPOST("importexport"), GETPOST("deletebefore"));

		if ($result<0) {
			setEventMessage($myfields->error, 'errors');
		}
	}

	header("Location:list.php");
	exit;
}


/*
 * View
 */

llxHeader('', $langs->trans("myField"), 'EN:Module_myField|FR:Module_myField|ES:M&oacute;dulo_myField');

$form=new Form($db);
$formother=new FormOther($db);



/* ************************************************************************** */
/*																			*/
/* Creation d'un myfield													  */
/*																			*/
/* ************************************************************************** */
if ($action == 'create') {
	print_fiche_titre($langs->trans("NewField"), '', "myfield@myfield");

	if ($mesg) print '<div class="error">'.$mesg.'</div>';

	print '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="typefield" value="'.$typefield.'">';

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border tableforfield" width="100%">';
	print '<tr><td width=30%>'.$langs->trans("TypeOfMyField").'</td><td>'.ShowMyFieldType($typefield).'</td></tr>';

	print '<tr><td class="fieldrequired">'.$langs->trans("myFieldLabel").'</td>';
	print '<td><input type="text" name="label" size="40" value="'.$label.'"></td></tr>';
	if ($typefield == 2) {
		print '<tr><td class="fieldrequired">'.$langs->trans("TypeMenu").'</td><td>';
		print SelectMyFieldMenuType(GETPOST("context"));
		print '</td></tr>';
	}
	else
		print '<tr><td >'.$langs->trans("Context").'</td><td><input type="text" name="context" size="40"></td></tr>';

	print '<tr><td >'.$langs->trans("Replacement").'</td><td><input type="text" name="replacement" size="40"></td></tr>';
	print '<tr><td >'.$langs->trans("Author").'</td><td><input type="text" name="author" size="20"></td></tr>';

	print "</table>\n";
	print '</div>';
	print '<div class="fichehalfright"><div class="ficheaddleft">';

	print '<div class="underbanner clearboth"></div>';
	print '<table class="border tableforfield" width="100%">';

	print '<tr><td width=30%>'.$langs->trans("ActiveFieldMode").'</td><td>';
	print SelectActiveMode($myfield->active, ($typefield == 0));

	print '</td></tr>';

	if ($typefield != 3) {
		print '<tr class="fieldinfo"><td >'.$langs->trans("BGColor").'</td><td>';
		print $formother->selectColor(GETPOST('color'), 'color', 'color', 1, '', 'hideifnotset');
		print '</td></tr>';
		print '<tr><td >'.$langs->trans("MoveField").'</td>';
		print '<td><input type="text" name="movefield" size="1" value="'.GETPOST('movefield').'"></td></tr>';
	}
	// des infos en plus pour les myfields de type champs
	if ($typefield == 0) {
		print '<tr><td >'.$langs->trans("InitValue").'</td><td><input type="text" name="initvalue" size="10"></td></tr>';
		print '<tr><td >'.$langs->trans("SizeField").'</td><td><input type="text" name="sizefield" size="10"></td></tr>';
		print '<tr class="fieldinfo"><td >'.$langs->trans("Compulsory").'</td>';
		print '<td>'.$form->selectyesno("compulsory", 0).'</td></tr>';
		print '<tr class="fieldinfo"><td >'.$langs->trans("FormatField").'</td>';
		print '<td><input type="text" name="formatfield" size="10"></td></tr>';
	} elseif ($typefield != 3) {
		print '<tr class="fieldinfo"><td >'.$langs->trans("RedirectURL").'</td>';
		print '<td><input type="text" name="formatfield" size="40"></td></tr>';
	}


	print '<tr><td colspan=2>'.$langs->trans("QueryDisplay").'</td></tr>';
	print '<tr><td colspan=2><textarea name="querydisplay" cols=50 rows=5>';
	print GETPOST('querydisplay').'</textarea></td></tr>';

	print "</table>\n";
	print '</div>';
	print '</div></div>';
	print '<div style="clear:both"></div>';
	print "\n".'<div class="tabsAction">'."\n";

	print '<input type="submit" name="button" class="butAction" value="'.$langs->trans("Add").'"> &nbsp; &nbsp; ';
	print '<input type="submit" name="button" class="butAction" value="'.$langs->trans("Cancel").'"><br>';
	print '</div>';
	print "</form>\n";

	print $langs->trans("ThinkOfGroupRight");

}

/* ************************************************************************** */
/*																			*/
/* Visualisation / Edition de la fiche										*/
/*																			*/
/* ************************************************************************** */
if ($rowid > 0) {
	if ($action == 'edit') {
		$myfield = new Myfield($db);
		$myfield->fetch($rowid);

		$head = myField_prepare_head($myfield);
		dol_fiche_head($head, 'card', $langs->trans("myField"), 0, 'myfield@myfield');

		$linkback = '<a href="'.DOL_URL_ROOT.'/myField/list.php">'.$langs->trans("BackToList").'</a>';

		print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="update">';
		print '<input type="hidden" name="rowid" value="'.$rowid.'">';

		print '<div class="fichecenter">';
		print '<div class="fichehalfleft">';
		print '<div class="underbanner clearboth"></div>';
		print '<table class="border tableforfield" width="100%">';

		print '<tr><td width=30%>'.$langs->trans("TypeOfMyField").'</td>';
		print '<td>'.ShowMyFieldType($myfield->typefield).'</td></tr>';
		print '<tr><td class="fieldrequired">'.$langs->trans("myFieldLabel").'</td>';
		print '<td><input type="text" name="label" size="40" value="'.$myfield->label.'"></td></tr>';

		if ($myfield->typefield == 2) {
			print '<tr><td class="fieldrequired">'.$langs->trans("TypeMenu").'</td><td>';
			print SelectMyFieldMenuType($myfield->context);
			print '</td></tr>';
		} else {
			print '<tr><td >'.$langs->trans("Context").'</td>';
			print '<td><input type="text" name="context" value="'.$myfield->context.'" size="40"></td></tr>';
		}
		print '<tr><td >'.$langs->trans("Replacement").'</td>';
		print '<td><input type="text" name="replacement" size="40" value="'.$myfield->replacement.'"></td></tr>';

		print '<tr><td >'.$langs->trans("Author").'</td>';
		print '<td><input type="text" name="author" size="10" value="'.$myfield->author.'"></td></tr>';

		print "</table>\n";
		print '</div>';
		print '<div class="fichehalfright"><div class="ficheaddleft">';

		print '<div class="underbanner clearboth"></div>';
		print '<table class="border tableforfield" width="100%">';

		print '<tr><td width=30% >'.$langs->trans("ActiveFieldMode").'</td><td>';
		print SelectActiveMode($myfield->active, ($typefield==0));

		print '</td></tr>';

		print '<tr><td >'.$langs->trans("BGColor").'</td><td>';
		print $formother->selectColor($myfield->color, 'color', 'color', 1, '', 'hideifnotset');
		print '</td></tr>';

		print '<tr><td >'.$langs->trans("MoveField").'</td>';
		print '<td><input type="text" name="movefield" size="1" value="'.$myfield->movefield.'"></td></tr>';

		if ($myfield->typefield == 0) {
			print '<tr><td >'.$langs->trans("FormatField").'</td>';
			print '<td><input type="text" name="formatfield" size="10" value="'.$myfield->formatfield.'"></td></tr>';

			print '<tr><td >'.$langs->trans("Compulsory").'</td><td>';
			print $form->selectyesno("compulsory", $myfield->compulsory, 1);
			print '</td></tr>';

			print '<tr><td >'.$langs->trans("InitValue").'</td>';
			print '<td><input type="text" name="initvalue" size="40" value="'.$myfield->initvalue.'"></td></tr>';

			print '<tr><td >'.$langs->trans("SizeField").'</td>';
			print '<td><input type="text" name="sizefield" size="10" value="'.$myfield->sizefield.'"></td></tr>';
		} else {
			print '<tr><td >'.$langs->trans("RedirectURL").'</td>';
			print '<td><input type="text" name="formatfield" size="40" value="'.$myfield->formatfield.'"></td></tr>';
		}

		print '<tr><td >'.$langs->trans("QueryDisplay").'</td>';
		print '<td><textarea name="querydisplay" cols=60 rows=5>';
		print $myfield->querydisplay.'</textarea></td></tr>';

		print "</table>\n";

		print '</div>';
		print '</div></div>';
		print '<div style="clear:both"></div>';

		/*
		 * Barre d'actions
		 *
		 */

		print "\n".'<div class="tabsAction">'."\n";
		print '<input type="submit" name="button" class="butAction" value="'.$langs->trans("Update").'"> &nbsp; &nbsp; ';
		print '<input type="submit" name="button" class="butAction" value="'.$langs->trans("Cancel").'">';
		print '</div>';
		print $langs->trans("ThinkOfGroupRight");
		print "</form>\n";

	} else {
		$form = new Form($db);

		$myfield = new Myfield($db);
		$myfield->fetch($rowid);

		$head = myField_prepare_head($myfield);

		dol_fiche_head($head, 'card', $langs->trans("myField"), 0, 'myfield@myfield');

		print '<div class="fichecenter">';
		print '<div class="fichehalfleft">';
		print '<div class="underbanner clearboth"></div>';
		print '<table class="border tableforfield" width="100%">';

		print '<tr><td width="30%">'.$langs->trans("TypeOfMyField").'</td>';
		print '<td>'.ShowMyFieldType($myfield->typefield).'</td></tr>';
		print '<tr><td>'.$langs->trans("myFieldLabel").'</td><td>'.$myfield->label.'</td></tr>';
		if ($myfield->typefield == 2)
			print '<tr><td>'.$langs->trans("Context").'</td><td>'.ShowMyFieldMenuType($myfield->context).'</td></tr>';
		else
			print '<tr><td>'.$langs->trans("Context").'</td><td>'.$myfield->context.'</td></tr>';
		print '<tr><td >'.$langs->trans("Replacement").'</td><td>'.$myfield->replacement.'</td></tr>';
		print '<tr><td>'.$langs->trans("Author").'</td><td>'.$myfield->author.'</td></tr>';
		print '</table>';

		print '</div>';
		print '<div class="fichehalfright"><div class="ficheaddleft">';

		print '<div class="underbanner clearboth"></div>';
		print '<table class="border tableforfield" width="100%">';

		print '<tr><td width="30%">'.$langs->trans("ActiveFieldMode").'</td><td>';
		print ShowActiveMode($myfield->active).'</td></tr>';

		print '<tr><td >'.$langs->trans("BGColor").'</td>';
		print '<td bgcolor='.$myfield->color.'>'.$myfield->color.'</td></tr>';
		print '<tr><td >'.$langs->trans("MoveField").'</td><td>'.$myfield->movefield.'</td></tr>';
		if ($myfield->typefield == 0) {
			print '<tr><td >'.$langs->trans("Compulsory").'</td><td>'.yn($myfield->compulsory).'</td></tr>';
			print '<tr><td >'.$langs->trans("InitValue").'</td><td>'.$myfield->initvalue.'</td></tr>';
			print '<tr><td >'.$langs->trans("SizeField").'</td><td>'.$myfield->sizefield.'</td></tr>';
		}
		else
			print '<tr><td >'.$langs->trans("RedirectURL").'</td><td>'.$myfield->formatfield.'</td></tr>';

		print '<tr><td >'.$langs->trans("QueryDisplay").'</td>';
		print '<td>'.$myfield->querydisplay.'</td></tr>';


		print '</table>';

		print '</div>';
		print '</div></div>';
		print '<div style="clear:both"></div>';

		/*
		 * Barre d'actions
		 *
		 */

		print "\n".'<div class="tabsAction">'."\n";
		// Edit
		if ($user->rights->myfield->setup) {
			print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=edit&amp;rowid='.$myfield->rowid.'">';
			print $langs->trans("Modify").'</a>';
		}
		// Delete
		if ($user->rights->myfield->supprimer) {
			print '<a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?action=delete&rowid='.$myfield->rowid.'">';
			print $langs->trans("DeleteField").'</a>';
		}
		print '</div>';
	}
}


/* ************************************************************************** */
/*																			*/
/* Importation / des myfields 												  */
/*																			*/
/* ************************************************************************** */
if ($action == 'import' || $action == 'export') {
	/*
	 * Import/export myfields
	 */
	if ($action == 'export')
		print_fiche_titre($langs->trans("ExportMyfields"));
	else {
		print_fiche_titre($langs->trans("ImportMyfields"));
		print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="importation">';
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	}
	print '<table class="border" width="75%">';
	if ($action == 'export')
		print '<tr><td><span class="fieldrequired">'.$langs->trans("ShowXMLExportData").'</span></td></tr>';
	else
		print '<tr><td><span class="fieldrequired">'.$langs->trans("FillImportExportData").'</span></td></tr>';
	print '<td><textarea name=importexport cols=132 rows=20>';
	if ($action == 'export') {
		$myfield = new Myfield($db);
		print $myfield->getexporttable();
	}
	print '</textarea></td></tr>';

	if ($action == 'import' ) {
		print '<tr><td align=center >';
		print '<input type="submit" class="button" value="'.$langs->trans("LaunchImport").'">'.'&nbsp;';
		print '<input type="checkbox" name="deletebefore" value="1">'.'&nbsp;'.$langs->trans("ErasePreviousMyFields");
		print '</td></tr>';
		print '</table>';
		print '</form>';
	}
	else
		print '</table>';
}

llxFooter();
$db->close();

// pour empecher le trigger de myfield de s'activer
function llxFooter()
{
}