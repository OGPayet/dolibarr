<?php
/* Copyright (C) 2018		Open-DSI		<support@open-dsi.fr>
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
 *      \file       htdocs/interventionsurvey/admin/interventionsurvey_blocquestion_extrafields.php
 *		\ingroup    interventionsurvey
 *		\brief      Page to setup extra fields of blocQuestion of interventionsurvey
 */

// Change this following line to use the correct relative path (../, ../../, etc)
$res = 0;
if (!$res && file_exists("../../main.inc.php")) $res = @include '../../main.inc.php';            // to work if your module directory is into a subdir of root htdocs directory
if (!$res && file_exists("../../../main.inc.php")) $res = @include '../../../main.inc.php';        // to work if your module directory is into a subdir of root htdocs directory
if (!$res) die("Include of main fails");
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
dol_include_once('/interventionsurvey/lib/interventionsurvey.lib.php');


if (!checkPermissionForAdminPages()) accessforbidden();

$langs->load("admin");
$langs->load("other");
$langs->load("interventionsurvey@interventionsurvey");

$extrafields = new ExtraFields($db);
$form = new Form($db);

// List of supported format
$tmptype2label = ExtraFields::$type2label;
$type2label = array('');
foreach ($tmptype2label as $key => $val) $type2label[$key] = $langs->trans($val);

$action = GETPOST('action', 'alpha');
$attrname = GETPOST('attrname', 'alpha');
$elementtype = 'interventionsurvey_surveyquestion'; //Must be the $table_element of the class that manage extrafield



/*
 * Actions
 */

require DOL_DOCUMENT_ROOT . '/core/actions_extrafields.inc.php';



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
dol_fiche_head($head, 'interventionSurvey_question_extrafields', '', -1, "interventionsurvey@interventionsurvey");


$textobject = $langs->transnoentitiesnoconv("InterventionSurveyQuestion");


require DOL_DOCUMENT_ROOT . '/core/tpl/admin_extrafields_view.tpl.php';

dol_fiche_end();


// Buttons
if ($action != 'create' && $action != 'edit') {
    print '<div class="tabsAction">';
    print "<a class=\"butAction\" href=\"" . $_SERVER["PHP_SELF"] . "?action=create\">" . $langs->trans("NewAttribute") . "</a>";
    print "</div>";
}


/* ************************************************************************** */
/*                                                                            */
/* Creation of an optional field											  */
/*                                                                            */
/* ************************************************************************** */

if ($action == 'create') {
    print "<br>";
    print load_fiche_titre($langs->trans('NewAttribute'));

    require DOL_DOCUMENT_ROOT . '/core/tpl/admin_extrafields_add.tpl.php';
}

/* ************************************************************************** */
/*                                                                            */
/* Edition of an optional field                                                */
/*                                                                            */
/* ************************************************************************** */
if ($action == 'edit' && !empty($attrname)) {
    print "<br>";
    print load_fiche_titre($langs->trans("FieldEdition", $attrname));

    require DOL_DOCUMENT_ROOT . '/core/tpl/admin_extrafields_edit.tpl.php';
}

llxFooter();

$db->close();
