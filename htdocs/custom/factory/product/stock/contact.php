<?php
/*
 * Copyright (C) 2014 		Florian HENRY 	<florian.henry@open-concept.pro>
 * Copyright (C) 2014-2017	Charlie Benke		<charlie@patas-monkey.com>
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
 * \file htdocs/factory/product/stock/contact.php
 * \ingroup factory
 * \brief Onglet de gestion des contacts de l'entrepot
 */
$res = @include("../../../main.inc.php"); // For root directory
if (! $res)
	$res = @include("../../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';

require_once DOL_DOCUMENT_ROOT . '/core/lib/stock.lib.php';

$langs->load("factory@factory");
$langs->load("orders");
$langs->load("sendings");
$langs->load("companies");

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$lineid = GETPOST('lineid', 'int');
$action = GETPOST('action', 'alpha');

// Security check
if ($user->societe_id)
	$socid = $user->societe_id;
$result = restrictedArea($user, 'stock');

$object = new entrepot($db);

// Load object
if ($id > 0) {
	$ret = $object->fetch($id);
	if ($ret == 0) {
		$langs->load("errors");
		setEventMessage($langs->trans('ErrorRecordNotFound'), 'errors');
		$error ++;
	} else if ($ret < 0) {
		setEventMessage($object->error, 'errors');
		$error ++;
	}
}
if (! $error) {
	$object->fetch_thirdparty();
} else {
	header('Location: list.php');
	exit();
}

/*
 * Ajout d'un nouveau contact
 */

if ($action == 'addcontact' && $user->rights->stock->creer) {
	if ($object->id > 0) {
		$contactid = (GETPOST('userid', 'int') ? GETPOST('userid', 'int') : GETPOST('contactid', 'int'));
		$result = $object->add_contact($contactid, $_POST["type"], $_POST["source"]);
	}

	if ($result >= 0) {
		header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $object->id);
		exit();
	} else {
		if ($object->error == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
			$langs->load("errors");
			setEventMessage($langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType"), 'errors');
		} else {
			setEventMessage($object->error, 'errors');
		}
	}
} elseif ($action == 'swapstatut' && $user->rights->stock->creer) {
	// Bascule du statut d'un contact
	if ($object->id > 0)
		$result = $object->swapContactStatus(GETPOST('ligne'));
} elseif ($action == 'deletecontact' && $user->rights->stock->creer) {
	// Efface un contact
	$result = $object->delete_contact($lineid);

	if ($result >= 0) {
		header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $object->id);
		exit();
	} else {
		dol_print_error($db);
	}
}

/*
 * View
 */

llxHeader('', $langs->trans('EntrepotContact'));

$form = new Form($db);
$formcompany = new FormCompany($db);
$formother = new FormOther($db);
$contactstatic=new Contact($db);
$userstatic=new User($db);

if ($object->id > 0) {
	$head = stock_prepare_head($object);
	dol_fiche_head($head, 'contact', $langs->trans("Warehouse"), 0, 'stock');
	$linkback = '<a href="'.DOL_URL_ROOT.'/product/stock/list.php">'.$langs->trans("BackToList").'</a>';

	if (DOL_VERSION >= "5.0.0") {
		$morehtmlref='<div class="refidno">';
		$morehtmlref.=$langs->trans("LocationSummary").' : '.$object->lieu;
		$morehtmlref.='</div>';

		dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'libelle', $morehtmlref);

		print '<div class="fichecenter">';
		print '<div class="underbanner clearboth"></div>';
	} else {
		/*
		 * synthese entrepot pour rappel
		 */
		print '<table class="border" width="100%">';

		// Ref
		print '<tr><td width="25%">' . $langs->trans('Ref') . '</td><td colspan="3">';
		print $form->showrefnav($object, 'id', $linkback, 1, 'rowid', 'libelle');
		print '</td></tr>';

		print '<tr><td>'.$langs->trans("LocationSummary").'</td><td colspan="3">'.$object->lieu.'</td></tr>';

		// Description
		print '<tr><td valign="top">'.$langs->trans("Description").'</td>';
		print '<td colspan="3">'.nl2br($object->description).'</td></tr>';

		// Address
		print '<tr><td>'.$langs->trans('Address').'</td><td colspan="3">';
		print $object->address;
		print '</td></tr>';

		// Town
		print '<tr><td width="25%">'.$langs->trans('Zip').'</td><td width="25%">'.$object->zip.'</td>';
		print '<td width="25%">'.$langs->trans('Town').'</td><td width="25%">'.$object->town.'</td></tr>';

		// Country
		print '<tr><td>'.$langs->trans('Country').'</td><td colspan="3">';
		if (! empty($object->country_code)) {
			$img=picto_from_langcode($object->country_code);
			print ($img?$img.' ':'');
		}
		print $object->country;
		print '</td></tr>';

		// Status
		print '<tr><td>'.$langs->trans("Status").'</td><td colspan="3">'.$object->getLibStatut(4).'</td></tr>';
		print "</table>";
	}
	print '</div>';

	print '<br>';
	$object->element = "stock";  // bad setting on the core
	include DOL_DOCUMENT_ROOT.'/core/tpl/contacts.tpl.php';
}
llxFooter();
$db->close();