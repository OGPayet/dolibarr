<?php
/* Copyright (C) 2007-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2017      Open-DSI             <support@open-dsi.fr>
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
 *	    \file       htdocs/extendedemail/admin/setup.php
 *		\ingroup    extendedemail
 *		\brief      Page to setup extendedemail module
 */

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include '../../../main.inc.php';		// to work if your module directory is into a subdir of root htdocs directory
if (! $res) die("Include of main fails");
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
dol_include_once('/extendedemail/lib/extendedemail.lib.php');

$langs->load("admin");
$langs->load("extendedemail@extendedemail");
$langs->load("opendsi@extendedemail");

if (!$user->admin) accessforbidden();

$action = GETPOST('action','alpha');


/*
 *	Actions
 */
$errors = [];
$error = 0;

if (preg_match('/set_(.*)/',$action,$reg)) {
    $code = $reg[1];
    $value = (GETPOST($code) ? GETPOST($code) : 1);
    if (dolibarr_set_const($db, $code, $value, 'chaine', 0, '', $conf->entity) > 0) {
        Header("Location: " . $_SERVER["PHP_SELF"]);
        exit;
    } else {
        $errors[] = $db->lasterror();
        setEventMessages($langs->trans("Error"), $errors, 'errors');
    }
} elseif (preg_match('/del_(.*)/',$action,$reg)) {
    $code = $reg[1];
    if (dolibarr_del_const($db, $code, $conf->entity) > 0) {
        Header("Location: " . $_SERVER["PHP_SELF"]);
        exit;
    } else {
        $errors[] = $db->lasterror();
        setEventMessages($langs->trans("Error"), $errors, 'errors');
    }
} elseif ($action == 'set') {
    $res = dolibarr_set_const($db, 'EXTENDEDEMAIL_MAX_LINE_HIDE_LIST', GETPOST('EXTENDEDEMAIL_MAX_LINE_HIDE_LIST', "int"), 'chaine', 0, '', $conf->entity);
    if (!$res > 0) {
        $errors[] = $db->lasterror();
        $error++;
    }
    
    if (!$errors) {
        $res = dolibarr_set_const($db, 'EXTENDEDEMAIL_SHIPPING_CONTACT_CODES', GETPOST('EXTENDEDEMAIL_SHIPPING_CONTACT_CODES', "alpha"), 'chaine', 0, '', $conf->entity);
        if (!$res > 0) {
            $errors[] = $db->lasterror();
            $error++;
        }
    }

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

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("ExtendedEmailSetup"),$linkback,'title_setup');
print "<br>\n";

$head=extendedemail_prepare_head();

dol_fiche_head($head, 'settings', $langs->trans("Module163006Name"), 0, 'action');

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

// EXTENDEDEMAIL_ADD_USER_TO_SENDTO
$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>' . $langs->trans("ExtendedEmailAddUserToSendto") . '</td>' . "\n";
print '<td align="center">&nbsp;</td>' . "\n";
print '<td align="right">' . "\n";
if (!empty($conf->use_javascript_ajax)) {
    print ajax_constantonoff('EXTENDEDEMAIL_ADD_USER_TO_SENDTO');
} else {
    if (empty($conf->global->EXTENDEDEMAIL_ADD_USER_TO_SENDTO)) {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_EXTENDEDEMAIL_ADD_USER_TO_SENDTO">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
    } else {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_EXTENDEDEMAIL_ADD_USER_TO_SENDTO">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
    }
}
print '</td></tr>' . "\n";

// EXTENDEDEMAIL_ADD_CONTACTS_THIRDPARTY_PARENT_TO_SENDTO
$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>' . $langs->trans("ExtendedEmailAddContactsThirdpartyParentToSendto") . '</td>' . "\n";
print '<td align="center">&nbsp;</td>' . "\n";
print '<td align="right">' . "\n";
if (!empty($conf->use_javascript_ajax)) {
    print ajax_constantonoff('EXTENDEDEMAIL_ADD_CONTACTS_THIRDPARTY_PARENT_TO_SENDTO');
} else {
    if (empty($conf->global->EXTENDEDEMAIL_ADD_CONTACTS_THIRDPARTY_PARENT_TO_SENDTO)) {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_EXTENDEDEMAIL_ADD_CONTACTS_THIRDPARTY_PARENT_TO_SENDTO">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
    } else {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_EXTENDEDEMAIL_ADD_CONTACTS_THIRDPARTY_PARENT_TO_SENDTO">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
    }
}
print '</td></tr>' . "\n";

// EXTENDEDEMAIL_ADD_USER_TO_SENDTOCC
$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>' . $langs->trans("ExtendedEmailAddUserToSendtocc") . '</td>' . "\n";
print '<td align="center">&nbsp;</td>' . "\n";
print '<td align="right">' . "\n";
if (!empty($conf->use_javascript_ajax)) {
    print ajax_constantonoff('EXTENDEDEMAIL_ADD_USER_TO_SENDTOCC');
} else {
    if (empty($conf->global->EXTENDEDEMAIL_ADD_USER_TO_SENDTOCC)) {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_EXTENDEDEMAIL_ADD_USER_TO_SENDTOCC">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
    } else {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_EXTENDEDEMAIL_ADD_USER_TO_SENDTOCC">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
    }
}
print '</td></tr>' . "\n";

// EXTENDEDEMAIL_ADD_CONTACTS_THIRDPARTY_PARENT_TO_SENDTOCC
$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>' . $langs->trans("ExtendedEmailAddContactsThirdpartyParentToSendtocc") . '</td>' . "\n";
print '<td align="center">&nbsp;</td>' . "\n";
print '<td align="right">' . "\n";
if (!empty($conf->use_javascript_ajax)) {
    print ajax_constantonoff('EXTENDEDEMAIL_ADD_CONTACTS_THIRDPARTY_PARENT_TO_SENDTOCC');
} else {
    if (empty($conf->global->EXTENDEDEMAIL_ADD_CONTACTS_THIRDPARTY_PARENT_TO_SENDTOCC)) {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_EXTENDEDEMAIL_ADD_CONTACTS_THIRDPARTY_PARENT_TO_SENDTOCC">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
    } else {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_EXTENDEDEMAIL_ADD_CONTACTS_THIRDPARTY_PARENT_TO_SENDTOCC">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
    }
}
print '</td></tr>' . "\n";

// EXTENDEDEMAIL_ADD_USER_TO_SENDTOCCC
$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>' . $langs->trans("ExtendedEmailAddUserToSendtoccc") . '</td>' . "\n";
print '<td align="center">&nbsp;</td>' . "\n";
print '<td align="right">' . "\n";
if (!empty($conf->use_javascript_ajax)) {
    print ajax_constantonoff('EXTENDEDEMAIL_ADD_USER_TO_SENDTOCCC');
} else {
    if (empty($conf->global->EXTENDEDEMAIL_ADD_USER_TO_SENDTOCCC)) {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_EXTENDEDEMAIL_ADD_USER_TO_SENDTOCCC">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
    } else {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_EXTENDEDEMAIL_ADD_USER_TO_SENDTOCCC">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
    }
}
print '</td></tr>' . "\n";

// EXTENDEDEMAIL_ADD_CONTACTS_THIRDPARTY_PARENT_TO_SENDTOCCC
$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>' . $langs->trans("ExtendedEmailAddContactsThirdpartyParentToSendtoccc") . '</td>' . "\n";
print '<td align="center">&nbsp;</td>' . "\n";
print '<td align="right">' . "\n";
if (!empty($conf->use_javascript_ajax)) {
    print ajax_constantonoff('EXTENDEDEMAIL_ADD_CONTACTS_THIRDPARTY_PARENT_TO_SENDTOCCC');
} else {
    if (empty($conf->global->EXTENDEDEMAIL_ADD_CONTACTS_THIRDPARTY_PARENT_TO_SENDTOCCC)) {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_EXTENDEDEMAIL_ADD_CONTACTS_THIRDPARTY_PARENT_TO_SENDTOCCC">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
    } else {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_EXTENDEDEMAIL_ADD_CONTACTS_THIRDPARTY_PARENT_TO_SENDTOCCC">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
    }
}
print '</td></tr>' . "\n";


// EXTENDEDEMAIL_ADD_CONTACTS_OF_OBJECT_TO_SENDTO
$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>' . $langs->trans("ExtendedEmailAddContactsOfObjectToSendTo") . '</td>' . "\n";
print '<td align="center">&nbsp;</td>' . "\n";
print '<td align="right">' . "\n";
if (!empty($conf->use_javascript_ajax)) {
    print ajax_constantonoff('EXTENDEDEMAIL_ADD_CONTACTS_OF_OBJECT_TO_SENDTO');
} else {
    if (empty($conf->global->EXTENDEDEMAIL_ADD_CONTACTS_OF_OBJECT_TO_SENDTO)) {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_EXTENDEDEMAIL_ADD_CONTACTS_OF_OBJECT_TO_SENDTO">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
    } else {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_EXTENDEDEMAIL_ADD_CONTACTS_OF_OBJECT_TO_SENDTO">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
    }
}
print '</td></tr>' . "\n";

// EXTENDEDEMAIL_ADD_CONTACTS_OF_OBJECT_TO_SENDTOCC
$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>' . $langs->trans("ExtendedEmailAddContactsOfObjectToSendToCC") . '</td>' . "\n";
print '<td align="center">&nbsp;</td>' . "\n";
print '<td align="right">' . "\n";
if (!empty($conf->use_javascript_ajax)) {
    print ajax_constantonoff('EXTENDEDEMAIL_ADD_CONTACTS_OF_OBJECT_TO_SENDTOCC');
} else {
    if (empty($conf->global->EXTENDEDEMAIL_ADD_CONTACTS_OF_OBJECT_TO_SENDTOCC)) {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_EXTENDEDEMAIL_ADD_CONTACTS_OF_OBJECT_TO_SENDTOCC">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
    } else {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_EXTENDEDEMAIL_ADD_CONTACTS_OF_OBJECT_TO_SENDTOCC">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
    }
}
print '</td></tr>' . "\n";

// EXTENDEDEMAIL_ADD_CONTACTS_OF_OBJECT_TO_SENDTOCCC
$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>' . $langs->trans("ExtendedEmailAddContactsOfObjectToSendToCCC") . '</td>' . "\n";
print '<td align="center">&nbsp;</td>' . "\n";
print '<td align="right">' . "\n";
if (!empty($conf->use_javascript_ajax)) {
    print ajax_constantonoff('EXTENDEDEMAIL_ADD_CONTACTS_OF_OBJECT_TO_SENDTOCCC');
} else {
    if (empty($conf->global->EXTENDEDEMAIL_ADD_CONTACTS_OF_OBJECT_TO_SENDTOCCC)) {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_EXTENDEDEMAIL_ADD_CONTACTS_OF_OBJECT_TO_SENDTOCCC">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
    } else {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_EXTENDEDEMAIL_ADD_CONTACTS_OF_OBJECT_TO_SENDTOCCC">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
    }
}
print '</td></tr>' . "\n";

// EXTENDEDEMAIL_SHIPPING_CONTACT_EMAIL_BY_DEFAULT
$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>' . $langs->trans("ExtendedEmailShippingContactEmailByDefault") . '</td>' . "\n";
print '<td align="center">&nbsp;</td>' . "\n";
print '<td align="right">' . "\n";
if (!empty($conf->use_javascript_ajax)) {
    print ajax_constantonoff('EXTENDEDEMAIL_SHIPPING_CONTACT_EMAIL_BY_DEFAULT');
} else {
    if (empty($conf->global->EXTENDEDEMAIL_SHIPPING_CONTACT_EMAIL_BY_DEFAULT)) {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_EXTENDEDEMAIL_SHIPPING_CONTACT_EMAIL_BY_DEFAULT">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
    } else {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_EXTENDEDEMAIL_SHIPPING_CONTACT_EMAIL_BY_DEFAULT">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
    }
}
print '</td></tr>' . "\n";

// EXTENDEDEMAIL_SHIPPING_CONTACT_CODES
$var=!$var;
print '<tr '.$bc[$var].'>'."\n";
print '<td>'.$langs->trans("ExtendedEmailShippingContactCodes").'</td>'."\n";
print '<td align="center">&nbsp;</td>' . "\n";
print '<td align="right">'."\n";
print '<input type="text" name="EXTENDEDEMAIL_SHIPPING_CONTACT_CODES" value="'.dol_escape_htmltag($conf->global->EXTENDEDEMAIL_SHIPPING_CONTACT_CODES).'">';
print '</td></tr>'."\n";

// EXTENDEDEMAIL_HIDE_NO_EMAIL
$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>' . $langs->trans("ExtendedEmailHideNoEmail") . '</td>' . "\n";
print '<td align="center">&nbsp;</td>' . "\n";
print '<td align="right">' . "\n";
if (!empty($conf->use_javascript_ajax)) {
    print ajax_constantonoff('EXTENDEDEMAIL_HIDE_NO_EMAIL');
} else {
    if (empty($conf->global->EXTENDEDEMAIL_HIDE_NO_EMAIL)) {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_EXTENDEDEMAIL_HIDE_NO_EMAIL">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
    } else {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_EXTENDEDEMAIL_HIDE_NO_EMAIL">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
    }
}
print '</td></tr>' . "\n";

// EXTENDEDEMAIL_MAX_LINE_HIDE_LIST
$var=!$var;
print '<tr '.$bc[$var].'>'."\n";
print '<td>'.$langs->trans("ExtendedEmailMaxLineHideList").'</td>'."\n";
print '<td align="center">&nbsp;</td>' . "\n";
print '<td align="right">'."\n";
print '<input type="text" name="EXTENDEDEMAIL_MAX_LINE_HIDE_LIST" value="'.$conf->global->EXTENDEDEMAIL_MAX_LINE_HIDE_LIST.'">';
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
