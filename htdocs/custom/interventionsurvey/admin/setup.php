<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2018		Open-DSI		<support@open-dsi.fr>
 * Copyright (C) 2020 Alexis LAURIER <contact@alexislaurier.fr>
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    interventionsurvey/admin/setup.php
 * \ingroup interventionsurvey
 * \brief   InterventionSurvey setup page.
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
    $i--;
    $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

global $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once '../lib/interventionsurvey.lib.php';
//require_once "../class/myclass.class.php";

// Translations
$langs->loadLangs(array("admin", "interventionsurvey@interventionsurvey"));

// Access control
if (!checkPermissionForAdminPages()) accessforbidden();

// Parameters
$action = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

$arrayofparameters = array(
    'INTERVENTIONSURVEY_ROOT_PRODUCT_CATEGORIES' => array('enabled' => 1),
    'INTERVENTIONSURVEY_ROOT_PRODUCT_CATEGORY_INCLUDE' => array('enabled' => 1),
    'INTERVENTIONSURVEY_STRICT_DATA_CHECK_ON_CLOTURED' => array('enabled' => 1),
    'INTERVENTIONSURVEY_ATLEASTONINTERVENTIONLINESMUSTEXISTONCLOTURED' => array('enabled' => 1),
    'INTERVENTIONSURVEY_SEND_FICHINTER_BY_MAIL' => array('enabled' => 1),
    'INTERVENTIONSURVEY_DEFAULT_EMAIL_ADDRESS_SENDER' => array('enabled' => 1),
    'INTERVENTIONSURVEY_CHECK_INTERVENTION_FIELDS' => array('enabled' => 1),
);



/*
 * Actions
 */

if ((float) DOL_VERSION >= 6) {
    include DOL_DOCUMENT_ROOT . '/core/actions_setmoduleoptions.inc.php';
}



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
dol_fiche_head($head, 'settings', '', -1, "interventionsurvey@interventionsurvey");

// Setup page goes here
/********************************************************
 *  General options
 ********************************************************/
print load_fiche_titre($langs->trans("Other"), '', '');

print '<form method="post" action="' . $_SERVER["PHP_SELF"] . '">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="update">';

$var = true;

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="20%">' . $langs->trans("Name") . '</td>' . "\n";
print '<td>' . $langs->trans("Description") . '</td>' . "\n";
print '<td align="right">' . $langs->trans("Value") . '</td>' . "\n";
print "</tr>\n";

// INTERVENTIONSURVEY_ROOT_PRODUCT_CATEGORIES
$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>' . $langs->trans("InterventionSurveyProductCategoriesName") . '</td>' . "\n";
print '<td>' . $langs->trans("InterventionSurveyProductCategoriesDesc") . '</td>' . "\n";
print '<td align="right">' . "\n";
print $form->select_all_categories('product', $conf->global->INTERVENTIONSURVEY_ROOT_PRODUCT_CATEGORIES, "INTERVENTIONSURVEY_ROOT_PRODUCT_CATEGORIES");
print '</td></tr>' . "\n";

// INTERVENTIONSURVEY_ROOT_PRODUCT_CATEGORY_INCLUDE
$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>' . $langs->trans("InterventionSurveyRootProductFieldName") . '</td>' . "\n";
print '<td>' . $langs->trans("InterventionSurveyRootProductFieldDesc") . '</td>' . "\n";
print '<td align="right">' . "\n";
if (!empty($conf->use_javascript_ajax)) {
    print ajax_constantonoff('INTERVENTIONSURVEY_ROOT_PRODUCT_CATEGORY_INCLUDE');
} else {
    if (empty($conf->global->INTERVENTIONSURVEY_ROOT_PRODUCT_CATEGORY_INCLUDE)) {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_INTERVENTIONSURVEY_ROOT_PRODUCT_CATEGORY_INCLUDE">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
    } else {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_INTERVENTIONSURVEY_ROOT_PRODUCT_CATEGORY_INCLUDE">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
    }
}
print '</td></tr>' . "\n";

//INTERVENTIONSURVEY_STRICT_DATA_CHECK_ON_CLOTURED
$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>' . $langs->trans("InterventionSurveyStricDataCheckOnCloturedName") . '</td>' . "\n";
print '<td>' . $langs->trans("InterventionSurveyStricDataCheckOnCloturedDesc") . '</td>' . "\n";
print '<td align="right">' . "\n";
if (!empty($conf->use_javascript_ajax)) {
    print ajax_constantonoff('INTERVENTIONSURVEY_STRICT_DATA_CHECK_ON_CLOTURED');
} else {
    if (empty($conf->global->INTERVENTIONSURVEY_STRICT_DATA_CHECK_ON_CLOTURED)) {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_INTERVENTIONSURVEY_STRICT_DATA_CHECK_ON_CLOTURED">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
    } else {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_INTERVENTIONSURVEY_STRICT_DATA_CHECK_ON_CLOTURED">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
    }
}
print '</td></tr>' . "\n";

//INTERVENTIONSURVEY_ATLEASTONINTERVENTIONLINESMUSTEXISTONCLOTURED
$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>' . $langs->trans("InterventionSurveyAtLeastOneListMustHaveBeenCreatedName") . '</td>' . "\n";
print '<td>' . $langs->trans("InterventionSurveyAtLeastOneListMustHaveBeenCreatedDesc") . '</td>' . "\n";
print '<td align="right">' . "\n";
if (!empty($conf->use_javascript_ajax)) {
    print ajax_constantonoff('INTERVENTIONSURVEY_ATLEASTONINTERVENTIONLINESMUSTEXISTONCLOTURED');
} else {
    if (empty($conf->global->INTERVENTIONSURVEY_ATLEASTONINTERVENTIONLINESMUSTEXISTONCLOTURED)) {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_INTERVENTIONSURVEY_ATLEASTONINTERVENTIONLINESMUSTEXISTONCLOTURED">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
    } else {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_INTERVENTIONSURVEY_ATLEASTONINTERVENTIONLINESMUSTEXISTONCLOTURED">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
    }
}
print '</td></tr>' . "\n";

// INTERVENTIONSURVEY_SEND_FICHINTER_BY_MAIL
$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>' . $langs->trans("InterventionSurveySendFichinterByMail") . '</td>' . "\n";
print '<td>' . $langs->trans("InterventionSurveySendFichinterByMailDescription") . '</td>' . "\n";
print '<td align="right">' . "\n";
if (!empty($conf->use_javascript_ajax)) {
    print ajax_constantonoff('INTERVENTIONSURVEY_SEND_FICHINTER_BY_MAIL');
} else {
    if (empty($conf->global->INTERVENTIONSURVEY_SEND_FICHINTER_BY_MAIL)) {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=INTERVENTIONSURVEY_SEND_FICHINTER_BY_MAIL">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
    } else {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=INTERVENTIONSURVEY_SEND_FICHINTER_BY_MAIL">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
    }
}
print '</td></tr>' . "\n";

//INTERVENTIONSURVEY_DEFAULT_EMAIL_ADDRESS_SENDER
$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>' . $langs->trans("InterventionSurveyDefaultEmailAddressSender") . '</td>' . "\n";
print '<td>' . $langs->trans("InterventionSurveyDefaultEmailAddressSenderDescription") . '</td>' . "\n";
print '<td align="right">'."\n";
print '<input type="text" name="INTERVENTIONSURVEY_DEFAULT_EMAIL_ADDRESS_SENDER" value="'.dol_escape_htmltag($conf->global->INTERVENTIONSURVEY_DEFAULT_EMAIL_ADDRESS_SENDER).'">';
print '</td></tr>'."\n";
print '</td></tr>' . "\n";

// INTERVENTIONSURVEY_CHECK_INTERVENTION_FIELDS
$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>' . $langs->trans("InterventionSurveyCheckInterventionFields") . '</td>' . "\n";
print '<td>' . $langs->trans("InterventionSurveyCheckInterventionFieldsDescription") . '</td>' . "\n";
print '<td align="right">' . "\n";
if (!empty($conf->use_javascript_ajax)) {
    print ajax_constantonoff('INTERVENTIONSURVEY_CHECK_INTERVENTION_FIELDS');
} else {
    if (empty($conf->global->INTERVENTIONSURVEY_CHECK_INTERVENTION_FIELDS)) {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=INTERVENTIONSURVEY_CHECK_INTERVENTION_FIELDS">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
    } else {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=INTERVENTIONSURVEY_CHECK_INTERVENTION_FIELDS">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
    }
}
print '</td></tr>' . "\n";

print '</table>';

print '<br>';
print '<div align="center">';
print '<input type="submit" class="button" value="' . $langs->trans("InterventionSurveySetupModifyButton") . '">';
print '</div>';

print '</form>';


// Page end
dol_fiche_end();

llxFooter();
$db->close();
