<?php
/* Copyright (C) 2010		Regis Houssin	   <regis.houssin@capnetworks.com>
 * Copyright (C) 2012-2014	Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2015-2017	Charlie Benke		<charlie@patas-monkey.com>
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
 *	   \file	   htdocs/equipement/contact.php
 *	   \ingroup	equipement
 *	   \brief	  Onglet de gestion des contacts de l'Equipement
 */

$res=@include("../main.inc.php");					// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../main.inc.php");		// For "custom" directory

dol_include_once('/equipement/class/equipement.class.php');
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

dol_include_once('/equipement/core/lib/equipement.lib.php');
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';

$langs->load("equipement@equipement");
$langs->load("companies");

$id	 = GETPOST('id', 'int');
$ref	= GETPOST('ref', 'alpha');
$lineid = GETPOST('lineid', 'int');
$socid  = GETPOST('socid', 'int');
$action = GETPOST('action', 'alpha');

$mine   = GETPOST('mode')=='mine' ? 1 : 0;
//if (! $user->rights->projet->all->lire) $mine=1;	// Special for projects

$object = new Equipement($db);
if ($id > 0 || ! empty($ref)) {
	$object->fetch($id, $ref);
	$object->fetch_thirdparty();
	$id=$object->id;
}

// Security check
$socid=0;
if ($user->societe_id > 0) $socid=$user->societe_id;
$result = restrictedArea($user, 'equipement', $id);


/*
 * Actions
 */

// Add new contact
if ($action == 'addcontact' && $user->rights->equipement->creer) {
	$result = 0;
	$result = $object->fetch($id);

	if ($result > 0 && $id > 0) {
		$contactid = (GETPOST('userid') ? GETPOST('userid', 'int') : GETPOST('contactid', 'int'));
		$result = $object->add_contact($contactid, $_POST["type"], $_POST["source"]);
	}

	if ($result >= 0) {
		header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
		exit;
	} else {
		if ($object->error == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
			$langs->load("errors");
			setEventMessage($langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType"), 'errors');
		} else
			setEventMessage($object->error, 'errors');

	}
}

// bascule du statut d'un contact
if ($action == 'swapstatut' && $user->rights->equipement->creer) {
	if ($object->fetch($id))
		$result=$object->swapContactStatus(GETPOST('ligne', 'int'));
	else
		dol_print_error($db);
}

// Efface un contact
if (($action == 'deleteline' || $action == 'deletecontact') && $user->rights->equipement->creer) {
	$object->fetch($id);
	$result = $object->delete_contact(GETPOST("lineid"));

	if ($result >= 0) {
		header("Location: contact.php?id=".$object->id);
		exit;
	} else
		dol_print_error($db);
}


/*
 * View
 */

$help_url="EN:Module_Equipement|FR:Module_Equipement|ES:M&oacute;dulo_Equipement";
llxHeader('', $langs->trans("Equipement"), $help_url);

$form = new Form($db);
$formcompany= new FormCompany($db);
$contactstatic=new Contact($db);
$userstatic=new User($db);


/* *************************************************************************** */
/*																			 */
/* Mode vue et edition														 */
/*																			 */
/* *************************************************************************** */

if ($id > 0 || ! empty($ref)) {
	// To verify role of users

	$head = equipement_prepare_head($object);
	dol_fiche_head($head, 'contact', $langs->trans("Equipement"), 0, 'equipement@equipement');


	/*
	 *   Projet synthese pour rappel
	*/
	print '<table class="border" width="100%">';

	$linkback = '<a href="list.php">'.$langs->trans("BackToList").'</a>';

// Ref
	print '<tr><td width=250px>'.$langs->trans("Ref").'</td>';
	$linkback = '<a href="'.DOL_URL_ROOT.'/equipement/list.php'.(! empty($socid)?'?socid='.$socid:'').'">';
	$linkback.= $langs->trans("BackToList").'</a>';
	print '<td>'.$form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref');
	print '</td></tr>';

	// produit
	$prod=new Product($db);
	$prod->fetch($object->fk_product);
	print '<tr><td class="fieldrequired">';
	print '<table class="nobordernopadding" width="100%">';
	print '<tr><td >'.$langs->trans("Product").'</td><td align=right>';
	print "<a href=# onclick=\"$('#descprod').toggle();\" >".img_picto("", "edit_add")."</a>";
	print '</td></tr></table></td><td colspan="3">';
	print $prod->getNomUrl(1)." : ".$prod->label.'</td></tr>';
	print "<tr style='display:none' id='descprod'>";
	print '<td></td><td>'.$prod->description.'</td><tr>';

	// Numéro de version
	print '<tr><td>'.$langs->trans("VersionNumber").'</td>';
	print '<td colspan="3">';
	print $object->numversion;
	print '</td></tr>';

	print "</table>";
	print '</div>';

	// Contacts lines (modules that overwrite templates must declare this into descriptor)
	$dirtpls=array_merge($conf->modules_parts['tpl'], array('/core/tpl'));
	foreach ($dirtpls as $reldir) {
		$res=@include dol_buildpath($reldir.'/contacts.tpl.php');
		if ($res) break;
	}
}

llxFooter();
$db->close();