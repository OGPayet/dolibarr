<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2016 Veryphone <support@mydoli.fr>
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
 * 	\file		admin/about.php
 * 	\ingroup	mydoli
 * 	\brief		This file is an example about page
 * 				Put some comments here
 */
// Dolibarr environment
$res = @include("../../main.inc.php"); // From htdocs directory
if (! $res) {
    $res = @include("../../../main.inc.php"); // From "custom" directory
}

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";

// Translations
$langs->load("mydoli@mydoli");

// Access control
if (! $user->admin) {
    accessforbidden();
}

/*
 * View
 */
$page_name = "myDoliAboutPage";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
    . $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($langs->trans($page_name), $linkback);

// Configuration header
$h = 0;
$head = array();

$head[$h][0] = dol_buildpath("/mydoli/admin/about.php",1);
$head[$h][1] = $langs->trans("myDoliAbout");
$head[$h][2] = 'Apropos';
$h++;
complete_head_from_modules($conf, $langs, null, $head, $h, 'mydoli');
dol_fiche_head(
    $head,
    'Apropos',
    $langs->trans("myDoliModule"),
    0,
    'mydoli@mydoli'
);

// About page goes here
print '<div>';
print '<br><br>';
print $langs->trans("myDoliLongDescription");
print '<br><br>';
print '</div>';

dol_fiche_end();

print '<br><center>';
print '<a href="http://www.mydoli.fr" target="_blank"><img src="https://static.wixstatic.com/media/337fd7_218b5de496334bfb895481b0266c1b0d~mv2.png/v1/fill/w_61,h_63,al_c,usm_0.66_1.00_0.01/337fd7_218b5de496334bfb895481b0266c1b0d~mv2.png" /></a><br><font color=#000000><br>myDoli</font><br><br>';
print '</center>';

llxFooter();

$db->close();
