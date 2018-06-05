<?php
/* Copyright (C) 2015-2016	  Charlie BENKE	 <charlie@patas-monkey.com>
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
 * 	\file		htdocs/portofolio/admin/about.php
 * 	\ingroup	portofolio
 * 	\brief		about page of portofolio
 */

// Dolibarr environment
$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");		// For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");	// For "custom" directory


// Libraries
dol_include_once("/portofolio/core/lib/portofolio.lib.php");
dol_include_once("/portofolio/core/lib/patasmonkey.lib.php");


// Translations
$langs->load("portofolio@portofolio");

// Access control
if (!$user->admin)
	accessforbidden();

/*
 * View
 */
$pagename = $langs->trans("PortofolioSetup") . " - " .$langs->trans("About");
llxHeader('', $pagename);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($pagename, $linkback, 'title_setup');


// Configuration header
$head = portofolio_admin_prepare_head();
dol_fiche_head($head, 'about', $langs->trans("portofolio"), 0, "portofolio@portofolio");

print  getChangeLog('portofolio');


llxFooter();
$db->close();
