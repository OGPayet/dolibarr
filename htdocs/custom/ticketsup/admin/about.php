<?php
/**
 * Ticket incident/support management
 * Copyright (C) 2013-2016  Jean-François FERRY <jfefe@aternatik.fr>
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
 *     \file        admin/about.php
 *     \ingroup    ticketsup
 *     \brief        This file is about page of ticket module
 */
// Dolibarr environment
$res = '';
if (file_exists("../../main.inc.php")) {
    $res = include "../../main.inc.php"; // From htdocs directory
} elseif (! $res && file_exists("../../../main.inc.php")) {
    $res = include "../../../main.inc.php"; // From "custom" directory
} else {
    die("Include of main fails");
}


// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once "../lib/ticketsup.lib.php";
dol_include_once('/ticketsup/lib/PHP_Markdown_1.0.1o/markdown.php');


// Translations
$langs->load("ticketsup@ticketsup");

// Access control
if (! $user->admin) {
    accessforbidden();
}

/*
 * View
 */
$page_name = "TicketsupAbout";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
    . $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans($page_name), $linkback);

// Configuration header
$head = ticketsupAdminPrepareHead();
dol_fiche_head(
    $head,
    'about',
    $langs->trans("Module110120Name"),
    0,
    'ticketsup@ticketsup'
);

// About page goes here
print '<p>'. $langs->trans("TicketSupAboutModule").'</p>';

print '<a href="http://www.aternatik.fr" style="float: right">'.
'<img src="' . dol_buildpath('/ticketsup/img/logo_aternatik.png', 1) . '"/>'.
'</a>';

print '<p>'. $langs->trans("TicketSupAboutModuleHelp").'</p>';

print '<p>'. $langs->trans("TicketSupAboutModuleImprove").'</p>';

print '<p>'. $langs->trans("TicketSupAboutModuleThanks").'</p>';

print '<div>';
$buffer = file_get_contents(dol_buildpath('/ticketsup/README.md', 0));
print Markdown($buffer);

print '<br>'.
'<a href="' . dol_buildpath('/ticketsup/COPYING', 1) . '">'.
'<img src="' . dol_buildpath('/ticketsup/img/gplv3.png', 1) . '"/>'.
'</a>';
print '</div>';
llxFooter();

$db->close();
