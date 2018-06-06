<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005	  Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2012 Regis Houssin		 <regis.houssin@capnetworks.com>
 * Copyright (C) 2013	  Cédric Salvador	   <csalvador@gpcsolutions.fr>
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
 *	\file	   htdocs/Factory/document.php
 *	\ingroup	Factory
 *	\brief	  Management page of documents attached to a Factory
 */

// Dolibarr environment
$res=0;
if (! $res && file_exists("../main.inc.php"))
	$res=@include("../main.inc.php");		// For root directory
if (! $res && file_exists("../../main.inc.php"))
	$res=@include("../../main.inc.php");	// For "custom" directory


dol_include_once('/factory/class/factory.class.php');
dol_include_once('/factory/core/lib/factory.lib.php');

require_once DOL_DOCUMENT_ROOT."/core/lib/files.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/images.lib.php";
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . "/product/stock/class/entrepot.class.php";
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';

$langs->load('factory@factory');
$langs->load('other');

$action		= GETPOST('action');
$confirm	= GETPOST('confirm');
$id			= GETPOST('id', 'int');
$ref		= GETPOST('ref');

// Security check
if ($user->societe_id) {
	$action='';
	$socid = $user->societe_id;
}

$result=restrictedArea($user, 'factory');

$object = new Factory($db);
$product = new Product($db);

/*
 * Actions
 */
if ($object->fetch($id, $ref)) {
	$result = $product->fetch($object->fk_product);
	$object->fetch_thirdparty();
	$upload_dir = $conf->factory->dir_output . "/" . dol_sanitizeFileName($object->ref);

}

if (DOL_VERSION < "4.0.0")
	include_once DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_pre_headers.tpl.php';
else
	include_once DOL_DOCUMENT_ROOT . '/core/actions_linkedfiles.inc.php';


/*
 * View
 */

llxHeader('', $langs->trans('Factory'), 'EN:Factory|FR:Factory|ES:Factory');

$form = new Form($db);

if ($id > 0 || ! empty($ref)) {
	if ($object->fetch($id, $ref)) {
		$object->fetch_thirdparty();

		$upload_dir = $conf->factory->dir_output.'/'.dol_sanitizeFileName($object->ref);

		$head = factory_prepare_head($object, $user);
		dol_fiche_head($head, 'document', $langs->trans("Factory"), 0, 'factory@factory');

		// Construit liste des fichiers
		$filearray=dol_dir_list(
						$upload_dir, "files", 0, '', '\.meta$',
						$sortfield, (strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC), 1
		);
		$totalsize=0;
		foreach ($filearray as $key => $file)
			$totalsize+=$file['size'];
		print '<table class="border" width="100%">';

		$linkback = '<a href="list.php">' . $langs->trans("BackToList") . '</a>';

		// Ref
		print '<tr><td width="25%">' . $langs->trans('Ref') . '</td><td colspan="3">';
		print $form->showrefnav($object, 'id', $linkback, 1, 'rowid', 'ref', '');
		print '</td></tr>';

		print '<tr><td >'.$langs->trans("Product").'</td><td>'.$product->getNomUrl(1)." : ".$product->label.'</td></tr>';

		// Lieu de stockage
		print '<tr><td>'.$langs->trans("EntrepotStock").'</td><td>';
		if ($object->fk_entrepot>0) {
			$entrepotStatic=new Entrepot($db);
			$entrepotStatic->fetch($object->fk_entrepot);
			print $entrepotStatic->getNomUrl(1)." - ".$entrepotStatic->lieu." (".$entrepotStatic->zip.")" ;
		}
		print '</td></tr>';

		// Date start planned
		print '<tr><td width=20% >'.$langs->trans("FactoryDateStartPlanned").'</td><td width=30% valign=top>';
		print dol_print_date($object->date_start_planned, 'day');
		print '</td>';
		// Date start made
		print '<td valign=top  width=20%>'.$langs->trans("DateStartMade").'</td>';
		print '<td width=30% >';
			print dol_print_date($object->date_start_made, 'day');
		print '</td></tr>';


		// Date end planned
		print '<tr><td>'.$langs->trans("DateEndPlanned").'</td>';
		print '<td colspan="3">';
			print dol_print_date($object->date_end_planned, 'day');
		print '</td></tr>';

		print '<tr><td>'.$langs->trans("QuantityPlanned").'</td>';
		print '<td colspan="3">';
		print $object->qty_planned;
		print '</td></tr>';

		print '<tr><td>'.$langs->trans('Status').'</td><td colspan=3>'.$object->getLibStatut(4).'</td></tr>';

		print "</table>";
		print "</div>\n";

		$modulepart = 'factory';
		$permission = $user->rights->factory->creer;
		$param = '&id=' . $object->id;
		include_once DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_post_headers.tpl.php';
	} else
		dol_print_error($db);

}
else
	header('Location: index.php');

llxFooter();
$db->close();