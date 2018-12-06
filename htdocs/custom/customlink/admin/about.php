<?php
/* Copyright (C) 2014-2017	  Charlie BENKE	 <charlie@patas-monkey.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\file		htdocs/customlink/admin/about.php
 * 	\ingroup	customlink
 * 	\brief		about page
 */

// Dolibarr environment
$res=0;
if (! $res && file_exists("../../main.inc.php"))
	$res=@include("../../main.inc.php");		// For root directory
if (! $res && file_exists("../../../main.inc.php"))
	$res=@include("../../../main.inc.php");	// For "custom" directory


// Libraries
dol_include_once("/customlink/core/lib/customlink.lib.php");
dol_include_once("/customlink/core/lib/patasmonkey.lib.php");

// Translations
$langs->load("customlink@customlink");

// Access control
if (!$user->admin)
	accessforbidden();

/*
 * View
 */
$page_name = $langs->trans("CustomlinkSetup"). " - " .$langs->trans("About");
llxHeader('', $page_name);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($page_name, $linkback, 'title_setup');

// Configuration header
$head = customlink_admin_prepare_head();
dol_fiche_head($head, 'about', $langs->trans("CustomLink"), 0, "customlink@customlink");

print getChangeLog('customlink');

llxFooter();
$db->close();