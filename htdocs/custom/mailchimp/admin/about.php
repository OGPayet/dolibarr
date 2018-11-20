<?php
/* <MailChimp connector>
 * Copyright (C) 2013 Florian Henry florian.henry@open-concept.pro
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
* 	\ingroup	mailchimp
 * 	\brief		This file is about page
 */
// Dolibarr environment
$res = @include("../../main.inc.php"); // From htdocs directory
if (! $res) {
	$res = @include("../../../main.inc.php"); // From "custom" directory
}

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once "../lib/mailchimp.lib.php";

dol_include_once('/mailchimp/lib/PHP_Markdown_1.0.1o/markdown.php');

// Translations
$langs->load("mailchimp@mailchimp");

// Access control
if (! $user->admin) {
	accessforbidden();
}

/*
 * Actions
 */

/*
 * View
 */
$page_name = "MailChimpAbout";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'. $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($langs->trans($page_name), $linkback);

// Configuration header
$head = mailchimpAdminPrepareHead();
dol_fiche_head($head,'about',$langs->trans("Module103020Name"),0,'mailchimp@mailchimp');

// About page goes here
echo $langs->trans("MailChimpAboutPage");

echo '<br>';

print '<BR><BR><BR><BR>--------------------------------';
print '<BR><a href="http://www.open-concept.pro/images/doc/Agefodd%20-%20Guide%20utilisateur.pdf" target="_blanck">Lien Documentation Utilisateur Français</a>';
print '<BR><a href="http://www.open-concept.pro/images/doc/Mailchimp%20-%20User%20guide.pdf" target="_blanck">Link english user documentation</a>';
print '<BR><a href="http://www.open-concept.pro/images/doc/Mailchimp%20-%20Guia%20del%20usuario.pdf" target="_blanck">Link spanish Guía del usuario</a>';
print '<BR>--------------------------------';

$buffer = file_get_contents(dol_buildpath('/mailchimp/README.md', 0));
echo Markdown($buffer);

echo '<br>',
'<a href="'.dol_buildpath('/mailchimp/COPYING', 1).'">',
'<img src="'.dol_buildpath('/mailchimp/img/gplv3.png', 1).'"/>',
'</a>';

dol_fiche_end();
llxFooter();

$db->close();