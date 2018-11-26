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
 *	    \file       htdocs/eventconfidentiality/admin/setup.php
 *		\ingroup    eventconfidentiality
 *		\brief      Page to setup eventconfidentiality module
 */

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include '../../../main.inc.php';		// to work if your module directory is into a subdir of root htdocs directory
if (! $res) die("Include of main fails");
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
dol_include_once('/eventconfidentiality/lib/eventconfidentiality.lib.php');
dol_include_once('/eventconfidentiality/class/eventconfidentiality.class.php');

$langs->load("admin");
$langs->load("eventconfidentiality@eventconfidentiality");
$langs->load("opendsi@eventconfidentiality");

if (!$user->admin) accessforbidden();

$action = GETPOST('action','alpha');

/*
 *	Actions
 */
$errors = array();
$error = 0;

if (preg_match('/set_(.*)/',$action,$reg)) {
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
} elseif ($action == 'set') {
    $value = GETPOST('EVENTCONFIDENTIALITY_DEFAULT_INTERNAL_LEVEL', "int");
    $res = dolibarr_set_const($db, 'EVENTCONFIDENTIALITY_DEFAULT_INTERNAL_LEVEL', $value, 'chaine', 0, '', $conf->entity);
    if (!$res > 0) {
        $errors[] = $db->lasterror();
        $error++;
    }

    $value = GETPOST('EVENTCONFIDENTIALITY_DEFAULT_EXTERNAL_LEVEL', "int");
    $res = dolibarr_set_const($db, 'EVENTCONFIDENTIALITY_DEFAULT_EXTERNAL_LEVEL', $value, 'chaine', 0, '', $conf->entity);
    if (!$res > 0) {
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
print load_fiche_titre($langs->trans("EventConfidentialitySetup"),$linkback,'title_setup');
print "<br>\n";

$head=eventconfidentiality_admin_prepare_head();

dol_fiche_head($head, 'settings', $langs->trans("Module163022Name"), 0, 'opendsi@eventconfidentiality');

print '<br>';
print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set">';

$var=true;

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td>'."\n";
print '<td align="center">&nbsp;</td>'."\n";
print '<td align="right">'.$langs->trans("Value").'</td>'."\n";
print "</tr>\n";

$confidential_leves = array(
    0 => $langs->trans('EventConfidentialityModeVisible'),
    1 => $langs->trans('EventConfidentialityModeBlurred'),
    2 => $langs->trans('EventConfidentialityModeHidden'),
);

// EVENTCONFIDENTIALITY_DEFAULT_INTERNAL_LEVEL
$var=!$var;
print '<tr '.$bc[$var].'>'."\n";
print '<td>'.$langs->trans("EventConfidentialityDefaultInternalLevel").'</td>'."\n";
print '<td align="center">&nbsp;</td>'."\n";
print '<td align="right">'."\n";
print $form->selectarray('EVENTCONFIDENTIALITY_DEFAULT_INTERNAL_LEVEL', $confidential_leves, $conf->global->EVENTCONFIDENTIALITY_DEFAULT_INTERNAL_LEVEL);
print '</td></tr>'."\n";

// EVENTCONFIDENTIALITY_DEFAULT_EXTERNAL_LEVEL
$var=!$var;
print '<tr '.$bc[$var].'>'."\n";
print '<td>'.$langs->trans("EventConfidentialityDefaultExternalLevel").'</td>'."\n";
print '<td align="center">&nbsp;</td>'."\n";
print '<td align="right">'."\n";
print $form->selectarray('EVENTCONFIDENTIALITY_DEFAULT_EXTERNAL_LEVEL', $confidential_leves, $conf->global->EVENTCONFIDENTIALITY_DEFAULT_EXTERNAL_LEVEL);
print '</td></tr>'."\n";

print '</table>';

dol_fiche_end();

print '<br>';
print '<div align="center">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</div>';

print '</form>';

llxFooter();

$db->close();
