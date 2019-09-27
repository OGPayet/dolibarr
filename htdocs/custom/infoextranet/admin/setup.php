<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2018 SuperAdmin
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
 * \file    infoextranet/admin/setup.php
 * \ingroup infoextranet
 * \brief   InfoExtranet setup page.
 */

// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res=@include($_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php");
// Try main.inc.php into web root detected using web root caluclated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) $res=@include(substr($tmp, 0, ($i+1))."/main.inc.php");
if (! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php")) $res=@include(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php");
// Try main.inc.php using relative path
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");
if (! $res) die("Include of main fails");

global $langs, $user, $db, $conf;

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once '../lib/infoextranet.lib.php';
//require_once "../class/myclass.class.php";

// Translations
$langs->loadLangs(array("admin", "infoextranet@infoextranet"));

// Access control
if (! $user->rights->infoextranet->configure) accessforbidden();

// Parameters
$action = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$error = 0;
$msg = '';


$sections = array('M', 'P', 'R', 'SI', 'H');
$arrayofparameters=array();

/*
 * Actions
 */

if ($action == "update")
{
    $sec = GETPOST('section');
    $extra = getExtrafields($sec);
    $param = '';
    foreach ($extra as $key => $field)
    {
        if (!empty(GETPOST($field['name'])))
            $param .= $field['name'].", ";
    }
    $param = substr($param, 0, -2);

    $res = dolibarr_set_const($db, 'INFOEXTRANET_SELECTED_'.$sec, $param);
    if (! $res > 0) $error++;

    //TEST ERROR
    if (! $error && $msg == '') {
        setEventMessages($langs->trans("SetupSaved"), '', 'mesgs');
    }
    else if ($msg == '') {
        setEventMessages($langs->trans("Error"), '', 'errors');
    }
    else {
        setEventMessages($msg, '', $error == 0 ? 'mesgs' : 'errors');
    }
}

if (!empty($action))
    exit(header("Location:".$_SERVER['PHP_SELF']));

/*
 * View
 */

// Fill array
foreach ($sections as $section)
{
    $arrayofparameters['INFOEXTRANET_SELECTED_'.$section] = dolibarr_get_const($db, 'INFOEXTRANET_SELECTED_'.$section);
}

$extrafields = getExtrafields();
$page_name = "InfoExtranetSetup";
llxHeader('', $langs->trans($page_name));
// Subheader
$linkback = '<a href="'.($backtopage?$backtopage:DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'object_infoextranet@infoextranet');

// Configuration header
$head = infoextranetAdminPrepareHead();
dol_fiche_head($head, 'settings', '', -1, "infoextranet@infoextranet");

foreach ($sections as $section)
{
    // Get all extrafields of section
    $extra = getExtrafields($section);
    print '<h1>'.$langs->trans('Title'.$section).'</h1>';
    print '<form name="update_section" target="'.$_SERVER['PHP_SELF'].'" method="post">';
    print '<table class="noborder">';
    foreach ($extra as $key => $field)
    {
        print '<tr>';
        print '<td>'.$field['label'].'</td>';
        if (strpos($arrayofparameters['INFOEXTRANET_SELECTED_'.$section], $field['name']) !== false)
            $checked = 'checked';
        else
            $checked = '';

        print '<td>'.'<input type="checkbox" name="'.$field['name'].'" '.$checked.'>'.'</td>';
        print '</tr>';
    }
    print '<input type="hidden" name="action" value="update" />';
    print '<input type="hidden" name="section" value="'.$section.'" />';
    print '<tr class="pair"><td colspan="3" align="center"><input type="submit" class="button" value="'.$langs->trans("Save").'"></td>';
    print '</table>';
    print '</form>';

}

// Page end
dol_fiche_end();

llxFooter();
$db->close();
