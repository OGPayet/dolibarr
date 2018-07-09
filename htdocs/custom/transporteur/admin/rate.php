<?php
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003	  Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2013	   Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2015	  Alexandre Spangaro   <aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2016-2017 Charlene Benke		<charlie@patas-monkey.com>
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
 *	  \file	   transporteur/admin/rate.php
 *	  \ingroup	transporteur
 *		\brief	  transporteur rate setup
 */

$res=@include("../../main.inc.php");					// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/../main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/../main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../../main.inc.php");		// For "custom" directory

dol_include_once('/transporteur/core/lib/transporteur.lib.php');
dol_include_once('/transporteur/class/transporteur_rate.class.php');

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
//require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

$langs->load("products");
$langs->load("other");
$langs->load("transporteur@transporteur");

$rowid  = GETPOST('rowid', 'int');
$action = GETPOST('action', 'alpha');
$cancel = GETPOST('cancel', 'alpha');

$type		= GETPOST('type', 'alpha');
$active		= GETPOST('active', 'alpha');

$label		= GETPOST("label", "alpha");
$color		= GETPOST("color", "alpha");
$fk_pays	= GETPOST("fk_pays", "alpha");
$weightmax	= GETPOST("weightmax", "alpha");
$weightunit = GETPOST("weightunit", "alpha");

$subprice	= GETPOST("subprice", "alpha");


// Security check
if (! $user->admin || $user->design) accessforbidden();
/*
 *	Actions
 */
if ($action == 'add') {
	if (! $cancel) {
		$object = new transporteurRate($db);

		$object->label		= trim($label);
		$object->color		= trim($color);
		$object->fk_pays	= trim($fk_pays);
		$object->weightmax	= trim($weightmax);
		$object->weightunit = trim($weightunit);

		$object->subprice	= trim($subprice);

		// Fill array 'array_options' with data from add form
//		$ret = $extrafields->setOptionalsFromPost($extralabels, $object);
//		if ($ret < 0) $error++;

		if ($object->label) {
			$id=$object->create($user);
			if ($id > 0) {
				header("Location: ".$_SERVER["PHP_SELF"]);
				exit;
			} else {
				$mesg=$object->error;
				$action = 'create';
			}
		} else {
			$mesg=$langs->trans("ErrorFieldRequired", $langs->transnoentities("Label"));
			$action = 'create';
		}
	}
}

if ($action == 'update') {
	if (! $cancel) {
		$object = new transporteurRate($db);
		$object->id				= $rowid;
		$object->label			= trim($label);
		$object->color			= trim($color);
		$object->fk_pays		= trim($fk_pays);
		$object->weightmax		= trim($weightmax);
		$object->weightunit		= trim($weightunit);
		$object->subprice		= trim($subprice);

		// Fill array 'array_options' with data from add form
		//$ret = $extrafields->setOptionalsFromPost($extralabels, $object);
		//if ($ret < 0) $error++;

		$object->update($user);

//		header("Location: ".$_SERVER["PHP_SELF"]."?rowid=".$_POST["rowid"]);
//		exit;
	}
}

if ($action == 'delete' && $user->rights->transporteur->setup) {

	$object = new transporteurRate($db);
	$object->delete($rowid);
	$rowid="";
	$action="";
}

/*
 * View
 */

$page_name = $langs->trans("TransPorteurSetup") . " - " . $langs->trans("transporteurRate");
llxHeader('', $page_name);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($page_name, $linkback, 'title_setup');


$head = transporteur_admin_prepare_head();

dol_fiche_head($head, 'rate', $langs->trans("transPorteur"), 0, "transporteur@transporteur");


$form=new Form($db);
$formother=new FormOther($db);
$formproduct=new FormProduct($db);

// List of transoporteur Rate
if (! $rowid && $action != 'create' && $action != 'edit') {
	//dol_fiche_head('');

	$sql = "SELECT d.rowid, d.label, d.active, fk_pays, weightmax, weightunit, subprice";
	$sql.= " FROM ".MAIN_DB_PREFIX."transporteur_rate as d";
	$sql.= " WHERE d.entity IN (".getEntity(1).")";

	$result = $db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);
		$i = 0;

		print '<table class="noborder" width="100%">';

		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("Ref").'</td>';
		print '<td>'.$langs->trans("Label").'</td>';
		print '<td align=right>'.$langs->trans("Country").'</td>';
		print '<td align=right>'.$langs->trans("WeightMax").'</td>';
		print '<td align=right>'.$langs->trans("WeightUnit").'</td>';

		print '<td align=right>'.$langs->trans("Subprice").'</td>';
		print '<td align="center">'.$langs->trans("Enabled").'</td>';
		print "</tr>\n";

		$var=True;
		while ($i < $num) {
			$objp = $db->fetch_object($result);
			$var=!$var;
			print "<tr ".$bc[$var].">";
			print '<td><a href="'.$_SERVER["PHP_SELF"].'?rowid='.$objp->rowid.'">';
			print img_object($langs->trans("ShowType"), 'transporteur@transporteur').' '.$objp->rowid.'</a></td>';
			print '<td>'.dol_escape_htmltag($objp->label).'</td>';

			$tmparray=getCountry($objp->fk_pays, 'all');
			print '<td align=right>'.$tmparray['label'].'</td>';

			print '<td align=right>'.price($objp->weightmax).'</td>';
			print '<td align=right>'.measuring_units_string($objp->weightunit, "weight").'</td>';
			print '<td align=right>'.price($objp->subprice).'</td>';

			print '<td align="center">'.yn($objp->active).'</td>';
			print "</tr>";
			$i++;
		}
		print "</table>";
	} else
		dol_print_error($db);

	//dol_fiche_end();

	/*
	 * Hotbar
	 */
	print '<div class="tabsAction">';
	if ($user->rights->transporteur->setup) {
		print '<div class="inline-block divButAction">';
		print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=create">'.$langs->trans("NewRate");
		print '</a></div>';
	}
	print "</div>";
}


/* ************************************************************************** */
/*																			*/
/* Creation mode															  */
/*																			*/
/* ************************************************************************** */
if ($action == 'create') {
	$object = new transporteurRate($db);

	$linkback='<a href="rate.php">'.$langs->trans("BackToTransPorteurRateList").'</a>';
	print_fiche_titre(
					$langs->trans("NewTransporteurRate"), $linkback,
					dol_buildpath('/transporteur/img/transporteur.png', 1), 1
	);

	print '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add">';

	dol_fiche_head($langs->trans("NewTransporteurRate"));

	print '<table class="border" width="100%">';
	print '<tbody>';

	print '<tr><td width="25%" class="fieldrequired">'.$langs->trans("Label").'</td>';
	print '<td><input type="text" name="label" size="40" value="'.GETPOST('label').'"></td></tr>';

	print '<tr><td>'.$langs->trans("Color").'</td><td colspan="3">';
	print $formother->selectColor(GETPOST('color'), 'color', null, 1, '', 'hideifnotset');
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("Country").'</td><td colspan="3">';
	print $form->select_country(GETPOST('fk_pays'), 'fk_pays');
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("WeightMax").'</td><td colspan="3">';
	print '<input name="weightmax" size="4" value="'.GETPOST('weightmax').'">';
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("WeightUnit").'</td><td colspan="3">';
	print $formproduct->select_measuring_units("weightunit", "weight");
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("Subprice").'</td><td colspan="3">';
	print '<input name="subprice" size="4" value="'.GETPOST('subprice').'">';
	print '</td></tr>';

	print '<tbody>';
	print "</table>\n";

	dol_fiche_end();

	print '<div class="center">';
	print '<input type="submit" name="button" class="button" value="'.$langs->trans("Add").'">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input type="submit" name="cancel" class="button" value="';
	print $langs->trans("Cancel").'" onclick="history.go(-1)" />';
	print '</div>';

	print "</form>\n";
}

/* ************************************************************************** */
/*																			*/
/* View mode																  */
/*																			*/
/* ************************************************************************** */
if ($rowid > 0) {
	if ($action != 'edit') {
		$object = new transporteurRate($db);
		$object->fetch($rowid);

		dol_fiche_head('', $langs->trans("ShowTransporteurRate"));

		$linkback='<a href="rate.php">'.$langs->trans("BackToTransPorteurRateList").'</a>';
		print_fiche_titre(
						$langs->trans("EditTransporteurRate"), $linkback,
						dol_buildpath('/transporteur/img/transporteur.png', 1), 1
		);

		print '<table class="border" width="100%">';

		// Ref
		print '<tr><td width="15%">'.$langs->trans("Ref").'</td>';
		print '<td>';
		print $form->showrefnav($object, 'rowid', "");
		print '</td></tr>';

		// Label
		print '<tr><td width="15%">'.$langs->trans("Label").'</td>';
		print '<td>'.dol_escape_htmltag($object->label).'</td></tr>';

		print '<tr><td>'.$langs->trans("Color").'</td><td colspan="3">';
		print $formother->showColor($object->color, '');
		print '</td></tr>';

		print '<tr><td>'.$langs->trans("Country").'</td>';
		$tmparray=getCountry($object->fk_pays, 'all');
		print '<td colspan="3" >'.$tmparray['label'].'</td></tr>';

		print '<tr><td>'.$langs->trans("WeightMax").'</td>';
		print '<td>'.price($object->weightmax).'</td></tr>';

		print '<tr><td>'.$langs->trans("WeightUnit").'</td>';
		print '<td>'.measuring_units_string($object->weightunit, "weight").'</td></tr>';

		print '<tr><td>'.$langs->trans("Subprice").'</td>';
		print '<td>'.price($object->subprice).'</td></tr>';

		print '<tr><td>'.$langs->trans("Active").'</td>';
		print '<td align="left">'.yn($object->active).'</td>';

		print '</table>';

		dol_fiche_end();

		/*
		 * Hotbar
		 */
		print '<div class="tabsAction">';
		print '<div class="inline-block divButAction">';
		print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=edit&amp;rowid='.$object->rowid.'">';
		print $langs->trans("Modify").'</a></div>';

		print '<div class="inline-block divButAction">';
		if ($user->rights->transporteur->setup) {
			print '<a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?action=delete&rowid='.$object->rowid.'">';
		} else {
			print '<a class="butActionRefused" href="#" title="' . $langs->trans("NotAllowed") . '">';
		}
		print $langs->trans("DeleteRate") . '</a></div>';
		print "</div>";
	}

	/* ************************************************************************** */
	/*																			*/
	/* Edition mode															   */
	/*																			*/
	/* ************************************************************************** */

	if ($action == 'edit') {
		$object = new transporteurRate($db);
		$object->id = $rowid;
		$object->fetch($rowid);
//		$object->fetch_optionals($rowid, $extralabels);

		$head = array();

		print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?rowid='.$rowid.'">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="rowid" value="'.$rowid.'">';
		print '<input type="hidden" name="action" value="update">';

		$linkback='<a href="type.php">'.$langs->trans("BackToprojectbudgetTypeList").'</a>';
		print_fiche_titre(
						$langs->trans("EditTransporteurRate"), $linkback,
						dol_buildpath('/transporteur/img/transporteur.png', 1), 1
		);

		dol_fiche_head('', $langs->trans("EditTransporteurRate"));

		print '<table class="border" width="100%">';
		print '<tr><td width="15%">'.$langs->trans("Ref").'</td><td>'.$object->id.'</td></tr>';

		print '<tr><td>'.$langs->trans("Label").'</td>';
		print '<td><input type="text" name="label" size="40" value="'.dol_escape_htmltag($object->label).'">';
		print '</td></tr>';

		print '<tr><td>'.$langs->trans("Color").'</td><td colspan="3">';
		print $formother->selectColor($object->color, 'color', null, 1, '', 'hideifnotset');
		print '</td></tr>';

		print '<tr><td>'.$langs->trans("Country").'</td><td colspan="3">';
		print $form->select_country($object->fk_pays, 'fk_pays');
		print '</td></tr>';

		print '<tr><td>'.$langs->trans("WeightMax").'</td>';
		print '<td><input type="text" name="weightmax" size="5" value="'.price($object->weightmax).'">';
		print '</td></tr>';

		print '<tr><td>'.$langs->trans("WeightUnit").'</td>';
		print '<td>';
		print $formproduct->select_measuring_units("weightunit", "weight");
		print '</td></tr>';

		print '<tr><td>'.$langs->trans("SubPrice").'</td>';
		print '<td><input type="text" name="subprice" size="5" value="'.price($object->subprice).'">';
		print '</td></tr>';

		print '<tr><td>'.$langs->trans("Active").'</td><td align=left>';
		print $form->selectyesno("active", $object->active, 1);
		print '</td></tr>';

		print '</table>';

		dol_fiche_end();

		print '<div class="center">';
		print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
		print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		print '<input type="submit" name="button" class="button" value="'.$langs->trans("Cancel").'">';
		print '</div>';

		print "</form>";
	}
}
	dol_htmloutput_mesg($mesg);

llxFooter();
$db->close();