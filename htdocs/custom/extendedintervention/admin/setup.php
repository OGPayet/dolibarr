<?php
/* Copyright (C) 2018      Open-DSI             <support@open-dsi.fr>
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
 *	    \file       htdocs/extendedintervention/admin/setup.php
 *		\ingroup    extendedintervention
 *		\brief      Page to setup extendedintervention module
 */

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include '../../../main.inc.php';		// to work if your module directory is into a subdir of root htdocs directory
if (! $res) die("Include of main fails");
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
dol_include_once('/extendedintervention/lib/extendedintervention.lib.php');

$langs->load("admin");
$langs->load("extendedintervention@extendedintervention");
$langs->load("opendsi@extendedintervention");

if (!$user->admin) accessforbidden();

$action = GETPOST('action','alpha');


/*
 *	Actions
 */

$errors = [];
$error = 0;

if ($action == 'set') {
    $value = GETPOST('EXTENDEDINTERVENTION_QUOTA_SHOW_X_PERIOD', "int");
    if (!($value > 0)) $value = 5;
    $res = dolibarr_set_const($db, 'EXTENDEDINTERVENTION_QUOTA_SHOW_X_PERIOD', $value, 'chaine', 0, '', $conf->entity);
    if (!$res > 0) {
        $errors[] = $db->lasterror();
        $error++;
    }
} elseif (preg_match('/set_(.*)/',$action,$reg)) {
    $code = $reg[1];
    $value = (GETPOST($code) ? GETPOST($code) : 1);
    if (dolibarr_set_const($db, $code, $value, 'chaine', 0, '', $conf->entity) > 0) {
        Header("Location: " . $_SERVER["PHP_SELF"]);
        exit;
    } else {
        $errors[] = $db->lasterror();
        $error++;
    }
} elseif (preg_match('/del_(.*)/',$action,$reg)) {
    $code = $reg[1];
    if (dolibarr_del_const($db, $code, $conf->entity) > 0) {
        Header("Location: " . $_SERVER["PHP_SELF"]);
        exit;
    } else {
        $errors[] = $db->lasterror();
        $error++;
    }
}

if ($action != '') {
    if (!$error) {
        setEventMessage($langs->trans("SetupSaved"));
    } else {
        setEventMessages($langs->trans("Error"), $errors, 'errors');
    }
}


/*
 *	View
 */

llxHeader();

$form = new Form($db);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("ExtendedInterventionSetup"),$linkback,'title_setup');
print "<br>\n";

$head=extendedintervention_admin_prepare_head();

dol_fiche_head($head, 'settings', $langs->trans("Module163023Name"), 0, 'opendsi@extendedintervention');

/********************************************************
 *  Quota options
 ********************************************************/
print load_fiche_titre($langs->trans("ExtendedInterventionQuotaCategory"),'','');

print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set">';

$var=true;

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="20%">'.$langs->trans("Name").'</td>'."\n";
print '<td>'.$langs->trans("Description").'</td>'."\n";
print '<td align="right">'.$langs->trans("Value").'</td>'."\n";
print "</tr>\n";

// EXTENDEDINTERVENTION_QUOTA_ACTIVATE
$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>'.$langs->trans("ExtendedInterventionQuotaActivateName").'</td>'."\n";
print '<td>'.$langs->trans("ExtendedInterventionQuotaActivateDesc").'</td>'."\n";
print '<td align="right">' . "\n";
if (!empty($conf->use_javascript_ajax)) {
    print ajax_constantonoff('EXTENDEDINTERVENTION_QUOTA_ACTIVATE');
} else {
    if (empty($conf->global->EXTENDEDINTERVENTION_QUOTA_ACTIVATE)) {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_EXTENDEDINTERVENTION_QUOTA_ACTIVATE">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
    } else {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_EXTENDEDINTERVENTION_QUOTA_ACTIVATE">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
    }
}
print '</td></tr>' . "\n";

// EXTENDEDINTERVENTION_QUOTA_SHOW_BLOCK
$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>'.$langs->trans("ExtendedInterventionQuotaShowBlockName").'</td>'."\n";
print '<td>'.$langs->trans("ExtendedInterventionQuotaShowBlockDesc").'</td>'."\n";
print '<td align="right">' . "\n";
if (!empty($conf->use_javascript_ajax)) {
    print ajax_constantonoff('EXTENDEDINTERVENTION_QUOTA_SHOW_BLOCK');
} else {
    if (empty($conf->global->EXTENDEDINTERVENTION_QUOTA_SHOW_BLOCK)) {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_EXTENDEDINTERVENTION_QUOTA_SHOW_BLOCK">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
    } else {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_EXTENDEDINTERVENTION_QUOTA_SHOW_BLOCK">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
    }
}
print '</td></tr>' . "\n";

// EXTENDEDINTERVENTION_QUOTA_SHOW_ONLY_TYPE_OF_INTERVENTION
$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>'.$langs->trans("ExtendedInterventionQuotaShowOnlyTypeOfInterventionName").'</td>'."\n";
print '<td>'.$langs->trans("ExtendedInterventionQuotaShowOnlyTypeOfInterventionDesc").'</td>'."\n";
print '<td align="right">' . "\n";
if (!empty($conf->use_javascript_ajax)) {
    print ajax_constantonoff('EXTENDEDINTERVENTION_QUOTA_SHOW_ONLY_TYPE_OF_INTERVENTION');
} else {
    if (empty($conf->global->EXTENDEDINTERVENTION_QUOTA_SHOW_ONLY_TYPE_OF_INTERVENTION)) {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_EXTENDEDINTERVENTION_QUOTA_SHOW_ONLY_TYPE_OF_INTERVENTION">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
    } else {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_EXTENDEDINTERVENTION_QUOTA_SHOW_ONLY_TYPE_OF_INTERVENTION">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
    }
}
print '</td></tr>' . "\n";

// EXTENDEDINTERVENTION_QUOTA_SHOW_X_PERIOD
$var=!$var;
print '<tr '.$bc[$var].'>'."\n";
print '<td>'.$langs->trans("ExtendedInterventionQuotaShowXPeriodName").'</td>'."\n";
print '<td>'.$langs->trans("ExtendedInterventionQuotaShowXPeriodDesc").'</td>'."\n";
print '<td align="right">'."\n";
print '<input type="number" name="EXTENDEDINTERVENTION_QUOTA_SHOW_X_PERIOD" value="'.dol_escape_htmltag($conf->global->EXTENDEDINTERVENTION_QUOTA_SHOW_X_PERIOD).'">';
print '</td></tr>'."\n";

print '</table>';

print '<br>';
print '<div align="center">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</div>';

print '</form>';

dol_fiche_end();

llxFooter();

$db->close();
