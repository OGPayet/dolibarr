<?php
/* Copyright (C) 2018      Open-DSI             <support@open-dsi.fr>
 * Copyright (C) 2020 Alexis LAURIER <contact@alexislaurier.fr>
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
 *	    \file       htdocs/interventionsurvey/admin/dictionaries.php
 *		\ingroup    interventionsurvey
 *		\brief      Page dictionaries of interventionsurvey module
 */

// Change this following line to use the correct relative path (../, ../../, etc)
$res = 0;
if (!$res && file_exists("../../main.inc.php")) $res = @include '../../main.inc.php';            // to work if your module directory is into a subdir of root htdocs directory
if (!$res && file_exists("../../../main.inc.php")) $res = @include '../../../main.inc.php';        // to work if your module directory is into a subdir of root htdocs directory
if (!$res) die("Include of main fails");
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
dol_include_once('/interventionsurvey/lib/interventionsurvey.lib.php');

if (!checkPermissionForAdminPages()) accessforbidden();


$langs->load("admin");
$langs->load("interventionsurvey@interventionsurvey");
$langs->load("extendedintervention@extendedintervention");

$action      = GETPOST('action', 'alpha');
$confirm     = GETPOST('confirm', 'alpha');
$id          = GETPOST('id', 'int');
$rowid       = GETPOST('rowid', 'int');
$prevrowid   = GETPOST('prevrowid', 'int');
$module      = 'interventionsurvey';
$name        = GETPOST('name', 'alpha');

$canRead = true;
$canCreate = true;
$canUpdate = true;
$canDelete = true;
$canDisable = true;

include dol_buildpath('/advancedictionaries/core/actions_dictionaries.inc.php');

/*
 * View
 */

$page_name = "InterventionSurveySetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . ($backtopage ? $backtopage : DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'object_interventionsurvey@interventionsurvey');

// Configuration header
$head = interventionsurveyAdminPrepareHead();
dol_fiche_head($head, 'dictionary_' . $name, '', -1, "interventionsurvey@interventionsurvey");

$moduleFilter = ''; // array or string to set the dictionaries of witch modules to show in dictionaries list
$familyFilter = 'interventionsurvey'; // array or string to set the dictionaries of witch family to show in dictionaries list

if (isset($dictionary)) {
    $dictionary->customLinkBack = "<a></a>";
}
include dol_buildpath('/advancedictionaries/core/tpl/dictionaries.tpl.php');

dol_fiche_end();

llxFooter();

$db->close();
