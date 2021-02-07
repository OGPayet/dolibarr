<?php
/* Copyright (C) 2007-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2019      Open-DSI             <support@open-dsi.fr>
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
 *	    \file       htdocs/sirene/admin/setup.php
 *		\ingroup    sirene
 *		\brief      Page to setup sirene module
 */

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include '../../../main.inc.php';		// to work if your module directory is into a subdir of root htdocs directory
if (! $res) die("Include of main fails");
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
dol_include_once('/sirene/lib/sirene.lib.php');

$langs->load("admin");
$langs->load("sirene@sirene");
$langs->load("opendsi@sirene");

if (!$user->admin) accessforbidden();

$action = GETPOST('action', 'aZ09');


/*
 *	Actions
 */

$errors = [];
$error = 0;

if ($action == 'set_codenaf_options') {
    $reload_codenaf_csv = GETPOST('reload_codenaf_csv');
    if (empty($reload_codenaf_csv)) {
        $value = GETPOST('CODENAF_CSV_SEPARATOR_TO_USE', "alpha");
        $res = dolibarr_set_const($db, 'CODENAF_CSV_SEPARATOR_TO_USE', $value, 'chaine', 0, '', $conf->entity);
        if (!($res > 0)) {
            $errors[] = $db->lasterror();
            $error++;
        }
        $value = GETPOST('CODENAF_CSV_ENCLOSURE_TO_USE', "alpha");
        $res = dolibarr_set_const($db, 'CODENAF_CSV_ENCLOSURE_TO_USE', $value, 'chaine', 0, '', $conf->entity);
        if (!($res > 0)) {
            $errors[] = $db->lasterror();
            $error++;
        }
        $value = GETPOST('CODENAF_CSV_ESCAPE_TO_USE', "alpha");
        $res = dolibarr_set_const($db, 'CODENAF_CSV_ESCAPE_TO_USE', $value, 'chaine', 0, '', $conf->entity);
        if (!($res > 0)) {
            $errors[] = $db->lasterror();
            $error++;
        }
    } else {
        $res = sirene_reload_codenaf_csv();
        if (!$res) {
            $error++;
        }
    }
} elseif ($action == 'set_sirene_options') {
    $value = GETPOST('SIRENE_API_URL', "alpha");
    $res = dolibarr_set_const($db, 'SIRENE_API_URL', $value, 'chaine', 0, '', $conf->entity);
    if (!($res > 0)) {
        $errors[] = $db->lasterror();
        $error++;
    }
    $value = GETPOST('SIRENE_API_BEARER_KEY', "alpha");
    $res = dolibarr_set_const($db, 'SIRENE_API_BEARER_KEY', $value, 'chaine', 0, '', $conf->entity);
    if (!($res > 0)) {
        $errors[] = $db->lasterror();
        $error++;
    }
    $value = GETPOST('SIRENE_API_TIMEOUT', "int");
    $res = dolibarr_set_const($db, 'SIRENE_API_TIMEOUT', $value, 'chaine', 0, '', $conf->entity);
    if (!($res > 0)) {
        $errors[] = $db->lasterror();
        $error++;
    }
    $value = GETPOST('SIRENE_VERIFICATION_SIRET_URL', "alpha");
    $res = dolibarr_set_const($db, 'SIRENE_VERIFICATION_SIRET_URL', $value, 'chaine', 0, '', $conf->entity);
    if (!($res > 0)) {
        $errors[] = $db->lasterror();
        $error++;
    }
    $value = GETPOST('SIRENE_MAIL_TO_SEND', "alpha");
    $res = dolibarr_set_const($db, 'SIRENE_MAIL_TO_SEND', $value, 'chaine', 0, '', $conf->entity);
    if (!($res > 0)) {
        $errors[] = $db->lasterror();
        $error++;
    }

} elseif (preg_match('/set_(.*)/',$action,$reg)) {
    $code = $reg[1];
    $value = (GETPOST($code) ? GETPOST($code) : 1);
    $res = dolibarr_set_const($db, $code, $value, 'chaine', 0, '', $conf->entity);
    if (!($res > 0)) {
        $errors[] = $db->lasterror();
        $error++;
    }
} elseif (preg_match('/del_(.*)/',$action,$reg)) {
    $code = $reg[1];
    $res = dolibarr_del_const($db, $code, $conf->entity);
    if (!($res > 0)) {
        $errors[] = $db->lasterror();
        $error++;
    }
}

if ($action != '') {
    if (!$error) {
        setEventMessage($langs->trans("SetupSaved"));
//        Header("Location: " . $_SERVER["PHP_SELF"]);
//        exit;
    } else {
        setEventMessages('', $errors, 'errors');
    }
}

/*
 *	View
 */

$form = new Form($db);

llxHeader();

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("SireneSetup"),$linkback,'title_setup');
print "<br>\n";

$head=sirene_admin_prepare_head();

dol_fiche_head($head, 'settings', $langs->trans("Module163027Name"), 0, 'opendsi@sirene');

print '<br>';


/********************************************************
 *  Code Naf options
 ********************************************************/
print load_fiche_titre($langs->trans("SireneCodeNafOptions"),'','');

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" id="sirene_code_naf_action" name="action" value="set_codenaf_options">';

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td width="20%">'.$langs->trans("Parameters").'</td>'."\n";
print '<td>'.$langs->trans("Description").'</td>'."\n";
print '<td width="30%">'.$langs->trans("Value").'</td>'."\n";
print "</tr>\n";

// CODENAF_CSV_SEPARATOR_TO_USE
print '<tr class="oddeven">';
print '<td>'.$langs->trans("SireneCodeNafCSVSeparatorToUseName").'</td>';
print '<td>'.$langs->trans("SireneCodeNafCSVSeparatorToUseDesc").'</td>';
print '<td class="nowrap">';
print '<input type="text" name="CODENAF_CSV_SEPARATOR_TO_USE" value="'.htmlspecialchars($conf->global->CODENAF_CSV_SEPARATOR_TO_USE).'">';
print '</td></tr>';

// CODENAF_CSV_ENCLOSURE_TO_USE
print '<tr class="oddeven">';
print '<td>'.$langs->trans("SireneCodeNafCSVEnclosureToUseName").'</td>';
print '<td>'.$langs->trans("SireneCodeNafCSVEnclosureToUseDesc").'</td>';
print '<td class="nowrap">';
print '<input type="text" name="CODENAF_CSV_ENCLOSURE_TO_USE" value="'.htmlspecialchars($conf->global->CODENAF_CSV_ENCLOSURE_TO_USE).'">';
print '</td></tr>';

// CODENAF_CSV_ESCAPE_TO_USE
print '<tr class="oddeven">';
print '<td>'.$langs->trans("SireneCodeNafCSVEscapeToUseName").'</td>';
print '<td>'.$langs->trans("SireneCodeNafCSVEscapeToUseDesc").'</td>';
print '<td class="nowrap">';
print '<input type="text" name="CODENAF_CSV_ESCAPE_TO_USE" value="'.htmlspecialchars($conf->global->CODENAF_CSV_ESCAPE_TO_USE).'">';
print '</td></tr>';

print '</table>';
print '</div>';

print '<br>';
print '<div align="center">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print ' &nbsp; ';
print '<input type="submit" class="button" name="reload_codenaf_csv" value="'.$langs->trans("SireneCodeNafReloadCSV").'">';
print '</div>';

print '</form>' . "\n";

/********************************************************
 *  Sirene options
 ********************************************************/
print load_fiche_titre($langs->trans("SireneSireneOptions"),'','');

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_sirene_options">';

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td width="20%">'.$langs->trans("Parameters").'</td>'."\n";
print '<td>'.$langs->trans("Description").'</td>'."\n";
print '<td width="30%">'.$langs->trans("Value").'</td>'."\n";
print "</tr>\n";

// SIRENE_DEBUG
print '<tr class="oddeven">' . "\n";
print '<td>'.$langs->trans("SireneDebugName").'</td>';
print '<td>'.$langs->trans("SireneDebugDesc").'</td>';
print '<td align="right">' . "\n";
if (!empty($conf->use_javascript_ajax)) {
    print ajax_constantonoff('SIRENE_DEBUG');
} else {
    if (empty($conf->global->SIRENE_DEBUG)) {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_SIRENE_DEBUG">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
    } else {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_SIRENE_DEBUG">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
    }
}
print '</td></tr>' . "\n";

// SIRENE_API_URL
print '<tr class="oddeven">'."\n";
print '<td>'.$langs->trans("SireneApiUrlName").'</td>'."\n";
print '<td>'.$langs->trans("SireneApiUrlDesc").'</td>'."\n";
print '<td class="nowrap">'."\n";
print '<input type="text" name="SIRENE_API_URL" size="30" value="'.dol_escape_htmltag($conf->global->SIRENE_API_URL).'" />'."\n";
print '</td></tr>'."\n";

// SIRENE_API_BEARER_KEY
print '<tr class="oddeven">'."\n";
print '<td>'.$langs->trans("SireneApiBearerKeyName").'</td>'."\n";
print '<td>'.$langs->trans("SireneApiBearerKeyDesc").'</td>'."\n";
print '<td class="nowrap">'."\n";
print '<input type="text" name="SIRENE_API_BEARER_KEY" size="30" value="'.dol_escape_htmltag($conf->global->SIRENE_API_BEARER_KEY).'" />'."\n";
print '</td></tr>'."\n";

// SIRENE_API_TIMEOUT
print '<tr class="oddeven">'."\n";
print '<td>'.$langs->trans("SireneApiTimeOutName").'</td>'."\n";
print '<td>'.$langs->trans("SireneApiTimeOutDesc").'</td>'."\n";
print '<td class="nowrap">'."\n";
print '<input type="text" name="SIRENE_API_TIMEOUT" size="30" value="'.dol_escape_htmltag($conf->global->SIRENE_API_TIMEOUT).'" />'."\n";
print '</td></tr>'."\n";

// SIRENE_VERIFICATION_SIRET_URL
print '<tr class="oddeven">'."\n";
print '<td>'.$langs->trans("SireneVerificationSiretUrlName").'</td>'."\n";
print '<td>'.$langs->trans("SireneVerificationSiretUrlDesc").'</td>'."\n";
print '<td class="nowrap">'."\n";
print '<input type="text" name="SIRENE_VERIFICATION_SIRET_URL" size="30" value="'.dol_escape_htmltag($conf->global->SIRENE_VERIFICATION_SIRET_URL).'" />'."\n";
print '</td></tr>'."\n";


// SIRENE_VERIFICATION_SIRET_URL
print '<tr class="oddeven">'."\n";
print '<td>'.$langs->trans("SireneMailReceiverToSendName").'</td>'."\n";
print '<td>'.$langs->trans("SireneMailReceiverToSendDesc").'</td>'."\n";
print '<td class="nowrap">'."\n";
print '<input type="text" name="SIRENE_MAIL_TO_SEND" size="30" value="'.dol_escape_htmltag($conf->global->SIRENE_MAIL_TO_SEND).'" />'."\n";
print '</td></tr>'."\n";

// SIRENE_PROCESSING_TOKEN
print '<tr class="oddeven">' . "\n";
print '<td>'.$langs->trans("SireneApiProcessingName").'</td>';
print '<td>'.$langs->trans("SireneApiProcessingDesc").'</td>';
print '<td align="right">' . "\n";
//dolibarr_set_const($this->db, "SIRENE_PROCESSING_TOKEN", 0, 'chaine', 0, '', $conf->entity);

if (!empty($conf->use_javascript_ajax)) {
    //dolibarr_set_const($this->db, "SIRENE_PROCESSING_TOKEN", 0, 'chaine', 0, '', $conf->entity);
    //dolibarr_set_const($this->db, "SIRENE_PROCESSING_TOKEN", 0, 'chaine', 0, '', $conf->entity);

    print ajax_constantonoff('SIRENE_PROCESSING_TOKEN');
} else {
    if (empty($conf->global->SIRENE_PROCESSING_TOKEN)) {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_SIRENE_PROCESSING_TOKEN">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
        dolibarr_set_const($this->db, "SIRENE_PROCESSING_TOKEN", 0, 'chaine', 0, '', $conf->entity);

    } else {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_SIRENE_PROCESSING_TOKEN">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
        //dolibarr_set_const($this->db, "SIRENE_PROCESSING_TOKEN", 0, 'chaine', 0, '', $conf->entity);

    }
}
print '</td></tr>' . "\n";



print '</table>';
print '</div>';

print '<br>';
print '<div align="center">';
print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
print '</div>';

print '</form>';

print dol_get_fiche_end();

llxFooter();

$db->close();
