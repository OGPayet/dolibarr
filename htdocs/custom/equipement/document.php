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
 *	   \file	   htdocs/equipement/document.php
 *	   \ingroup	equipement
 *	   \brief	  Page des documents joints sur les equipements
 */

$res=@include("../main.inc.php");					// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../main.inc.php");		// For "custom" directory


require_once DOL_DOCUMENT_ROOT."/product/class/product.class.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/files.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/images.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php";

dol_include_once('/equipement/class/equipement.class.php');
dol_include_once('/equipement/core/lib/equipement.lib.php');

$langs->load("other");
$langs->load("equipement@equipement");
$langs->load("companies");

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'alpha');
$confirm = GETPOST('confirm', 'alpha');

$mesg='';
if (isset($_SESSION['DolMessage'])) {
	$mesg=$_SESSION['DolMessage'];
	unset($_SESSION['DolMessage']);
}

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'equipement', $id, 'equipement', '', 'fk_soc_client');


// Get parameters
$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST('page', 'int');

if ($page == -1)
	$page = 0;
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="name";


$object = new Equipement($db);
$object->fetch($id, $ref);

$upload_dir = $conf->equipement->dir_output.'/'.dol_sanitizeFileName($object->id);
$modulepart='equipement';


/*
 * Actions
 */

if (DOL_VERSION < "4.0.0")
	include_once DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_pre_headers.tpl.php';
else
	include_once DOL_DOCUMENT_ROOT . '/core/actions_linkedfiles.inc.php';



/*
 * View
 */

$form = new Form($db);

llxHeader("", "", $langs->trans("EquipementCard"));


if ($object->id) {
	$object->fetch_thirdparty();

	$head=equipement_prepare_head($object, $user);

	dol_fiche_head($head, 'documents', $langs->trans("EquipementCard"), 0, 'equipement@equipement');

	if (!empty($conf->global->MAIN_MODULE_ULTIMATEQRCODE)) {
		dol_include_once("/ultimateqrcode/lib/ultimateqrcode.lib.php");
		dol_include_once("/ultimateqrcode/includes/phpqrcode/qrlib.php");

		$tempDir=$conf->equipement->dir_output.DIRECTORY_SEPARATOR.$object->id.DIRECTORY_SEPARATOR;

		if (!file_exists($tempDir))
			mkdir($tempDir);
		if (!file_exists($tempDir.DIRECTORY_SEPARATOR.'thumbs'))
			mkdir($tempDir.DIRECTORY_SEPARATOR.'thumbs');

		$codeContents = '<a href="fiche.php?id='.$object->id.'">';

		// generating
		QRcode::png($codeContents, $tempDir.'/'.$object->id.'.png', QR_ECLEVEL_L, 2);
		QRcode::png($codeContents, $tempDir.'/thumbs/'.$object->id.'_mini.png', QR_ECLEVEL_L, 2);
	}

	// Construit liste des fichiers
	$filearray=dol_dir_list(
					$upload_dir, "files", 0, '', '\.meta$',
					$sortfield, (strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC), 1
	);

	$totalsize=0;
	foreach ($filearray as $key => $file)
		$totalsize+= $file['size'];


	print '<table class="border" width="100%">';

	// Ref
	print '<tr><td width="25%">'.$langs->trans("Ref").'</td><td>';
	print $form->showrefnav($object, 'ref', '', 1, 'ref', 'ref');
	print '</td></tr>';

	// produit
	$prod=new Product($db);
	$prod->fetch($object->fk_product);
	print '<tr><td class="fieldrequired">'.$langs->trans("Product").'</td>';
	print '<td>'.$prod->getNomUrl(1)." : ".$prod->label.'</td></tr>';

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
	print '</table><br>';
	print '<table class="border" width="100%">';
	print '<tr><td width="25%">'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';
	print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td>';
	print '<td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';
	print '<tr><td>'.$langs->trans("QRCodeEnabled").'</td>';
	if (!empty($conf->global->MAIN_MODULE_ULTIMATEQRCODE))
		print '<td colspan=3>'.$langs->trans("QRCodeEnabled").'</td></tr>';
	else
		print '<td colspan=3>'.$langs->trans("QRCodeDisabled").'</td></tr>';
	print '</table>';
	print '</div>';

	dol_htmloutput_mesg($mesg, $mesgs);

	/*
	 * Confirmation suppression fichier
	 */
	if ($action == 'delete') {
		$ret=$form->form_confirm(
						$_SERVER["PHP_SELF"].'?id='.$object->id.'&urlfile='.urlencode($_GET["urlfile"]),
						$langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile'),
						'confirm_deletefile', '', 0, 1
		);
		if ($ret == 'html') print '<br>';
	}

	// Affiche formulaire upload
	$formfile=new FormFile($db);
	$formfile->form_attach_new_file(
					dol_buildpath('/equipement/', 1).'document.php?id='.$object->id,
					'', 0, 0, $user->rights->equipement->creer, 50, $object
	);

	// List of document
	$param='&id='.$object->id;
	$formfile->list_of_documents($filearray, $object, 'equipement', $param, 0, $object->id.DIRECTORY_SEPARATOR);

} else
	print $langs->trans("UnkownError");

llxFooter();
$db->close();