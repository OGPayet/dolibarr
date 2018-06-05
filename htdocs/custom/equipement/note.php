<?php
/* Copyright (C) 2012-2016	Charlie Benke	<charlie@patas-monkey.com>
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
 *	\file	   htdocs/equipement/note.php
 *	\ingroup	equipement
 *	\brief	  Fiche d'information sur un equipement
 */


$res=@include("../main.inc.php");					// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../main.inc.php");		// For "custom" directory

require_once DOL_DOCUMENT_ROOT."/product/class/product.class.php";

dol_include_once('/equipement/class/equipement.class.php');
dol_include_once('/equipement/core/lib/equipement.lib.php');

$langs->load('companies');
$langs->load("equipement@equipement");

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action=GETPOST('action', 'alpha');

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'equipement', $id, 'equipement', '', 'fk_soc_client');


$object = new Equipement($db);
$object->fetch($id, $ref);

/*
 * Actions
 */
if ($action == 'setnote_public' && $user->rights->equipement->creer) {
	$result=$object->update_note_public(dol_html_entity_decode(GETPOST('note_public'), ENT_QUOTES), '_public');
	if ($result < 0) dol_print_error($db, $object->error);
} else if ($action == 'setnote_private' && $user->rights->equipement->creer) {
	$result=$object->update_note(dol_html_entity_decode(GETPOST('note_private'), ENT_QUOTES), '_private');
	if ($result < 0) dol_print_error($db, $object->error);
}


/*
 * View
 */
llxHeader();

$form = new Form($db);

if ($id > 0 || ! empty($ref)) {
	dol_htmloutput_mesg($mesg);

	$societe = new Societe($db);
	$societe->fetch($object->fk_soc_client);

	$head = equipement_prepare_head($object);
	dol_fiche_head($head, 'note', $langs->trans('EquipementCard'), 0, 'equipement@equipement');

	print '<table class="border" width="100%">';
	print '<tr><td width="25%">'.$langs->trans('Ref').'</td><td colspan="3">';
	print $form->showrefnav($object, 'ref', '', 1, 'ref', 'ref');
	print '</td></tr>';

	$prod=new Product($db);
	$prod->fetch($object->fk_product);
	print '<tr><td class="fieldrequired">';
	print $langs->trans("Product").'</td><td>'.$prod->getNomUrl(1)." : ".$prod->label.'</td></tr>';

	// fournisseur
	print '<tr><td class="fieldrequired">'.$langs->trans("Fournisseur").'</td><td>';
	if ($object->fk_soc_fourn > 0) {
		$soc=new Societe($db);
		$soc->fetch($object->fk_soc_fourn);
		print $soc->getNomUrl(1);
	}
	print '</td></tr>';

	// client
	print '<tr><td >'.$langs->trans("Client").'</td><td>';
	if ($object->fk_soc_client > 0) {
		$soc=new Societe($db);
		$soc->fetch($object->fk_soc_client);
		print $soc->getNomUrl(1);
	}
	print '</td></tr>';
	print "</table>";
	print '<br>';

	include(DOL_DOCUMENT_ROOT.'/core/tpl/notes.tpl.php');
	dol_fiche_end();
}

llxFooter();
$db->close();