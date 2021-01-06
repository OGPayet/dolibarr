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
 *	    \file       htdocs/requestmanager/admin/setup.php
 *		\ingroup    requestmanager
 *		\brief      Page to setup requestmanager module
 */

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include '../../../main.inc.php';		// to work if your module directory is into a subdir of root htdocs directory
if (! $res) die("Include of main fails");
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
dol_include_once('/requestmanager/lib/requestmanager.lib.php');
dol_include_once('/requestmanager/class/requestmanager.class.php');

$langs->load("admin");
$langs->load("errors");
$langs->load("mails");
$langs->load("requestmanager@requestmanager");
$langs->load("opendsi@requestmanager");

if (!$user->admin) accessforbidden();

$action = GETPOST('action','alpha');


/*
 *	Actions
 */

$errors = [];
$error = 0;

if ($action == 'set_notification_assigned_options') {
    $email_address = $email = GETPOST('REQUESTMANAGER_ASSIGNED_NOTIFICATION_SEND_FROM', "alpha");
    if (preg_match('/<([^>]+)>/i', $email, $matches)) $email_address = $matches[1];
    if (empty($email) || isValidEmail($email_address)) {
        $res = dolibarr_set_const($db, 'REQUESTMANAGER_ASSIGNED_NOTIFICATION_SEND_FROM', $email, 'chaine', 0, '', $conf->entity);
        if (!$res > 0) {
            $errors[] = $db->lasterror();
            $error++;
        }
    } elseif (!empty($email)) {
        $errors[] = $langs->trans('BadEMail') . ': ' . dol_htmlentities($email);
        $error++;
    }
} elseif ($action == 'set_notification_requester_options') {
    $email_address = $email = GETPOST('REQUESTMANAGER_REQUESTER_NOTIFICATION_SEND_FROM', "alpha");
    if (preg_match('/<([^>]+)>/i', $email, $matches)) $email_address = $matches[1];
    if (empty($email) || isValidEmail($email_address)) {
        $res = dolibarr_set_const($db, 'REQUESTMANAGER_REQUESTER_NOTIFICATION_SEND_FROM', $email, 'chaine', 0, '', $conf->entity);
        if (!$res > 0) {
            $errors[] = $db->lasterror();
            $error++;
        }
    } elseif (!empty($email)) {
        $errors[] = $langs->trans('BadEMail') . ': ' . dol_htmlentities($email);
        $error++;
    }
} elseif ($action == 'set_notification_watchers_options') {
    $email_address = $email = GETPOST('REQUESTMANAGER_WATCHERS_NOTIFICATION_SEND_FROM', "alpha");
    if (preg_match('/<([^>]+)>/i', $email, $matches)) $email_address = $matches[1];
    if (empty($email) || isValidEmail($email_address)) {
        $res = dolibarr_set_const($db, 'REQUESTMANAGER_WATCHERS_NOTIFICATION_SEND_FROM', $email, 'chaine', 0, '', $conf->entity);
        if (!$res > 0) {
            $errors[] = $db->lasterror();
            $error++;
        }
    } elseif (!empty($email)) {
        $errors[] = $langs->trans('BadEMail') . ': ' . dol_htmlentities($email);
        $error++;
    }
} elseif ($action == 'set') {
//    // operation time default in minute
//    $operationTimeDefault = GETPOST('REQUESTMANAGER_OPERATION_TIME_DEFAULT', 'int') ? GETPOST('REQUESTMANAGER_OPERATION_TIME_DEFAULT', 'int') : 0;
//    $res = dolibarr_set_const($db, 'REQUESTMANAGER_OPERATION_TIME_DEFAULT', $operationTimeDefault, 'chaine', 0, '', $conf->entity);
//    if (!$res > 0) {
//        $errors[] = $db->lasterror();
//        $error++;
//    }
//
//    // deadline time default in minute
//    $deadlineTimeDefault = GETPOST('REQUESTMANAGER_DEADLINE_TIME_DEFAULT', 'int') ? GETPOST('REQUESTMANAGER_DEADLINE_TIME_DEFAULT', 'int') : 0;
//    $res = dolibarr_set_const($db, 'REQUESTMANAGER_DEADLINE_TIME_DEFAULT', $deadlineTimeDefault, 'chaine', 0, '', $conf->entity);
//    if (!$res > 0) {
//        $errors[] = $db->lasterror();
//        $error++;
//    }

    // root product categories
    $rootProductCategories = GETPOST('REQUESTMANAGER_ROOT_PRODUCT_CATEGORIES', 'int') > 0 ? GETPOST('REQUESTMANAGER_ROOT_PRODUCT_CATEGORIES', 'int') : '';
    $res = dolibarr_set_const($db, 'REQUESTMANAGER_ROOT_PRODUCT_CATEGORIES', $rootProductCategories, 'chaine', 0, '', $conf->entity);
    if (!$res > 0) {
        $errors[] = $db->lasterror();
        $error++;
    }

    // Position bloc linked objects
    $res = dolibarr_set_const($db, 'REQUESTMANAGER_POSITION_BLOC_OBJECT_LINKED', GETPOST('REQUESTMANAGER_POSITION_BLOC_OBJECT_LINKED', 'alpha'), 'chaine', 0, '', $conf->entity);
    if (!$res > 0) {
        $errors[] = $db->lasterror();
        $error++;
    }

    // Position link new linked object
    $res = dolibarr_set_const($db, 'REQUESTMANAGER_POSITION_LINK_NEW_OBJECT_LINKED', GETPOST('REQUESTMANAGER_POSITION_LINK_NEW_OBJECT_LINKED', 'alpha'), 'chaine', 0, '', $conf->entity);
    if (!$res > 0) {
        $errors[] = $db->lasterror();
        $error++;
    }
} elseif ($action == 'updateMask') {
    $maskconst = GETPOST('maskconst', 'alpha');
    $maskvalue = GETPOST('maskvalue', 'alpha');
    if ($maskconst) $res = dolibarr_set_const($db, $maskconst, $maskvalue, 'chaine', 0, '', $conf->entity);
    if (!$res > 0) {
        $error++;
    }
} else if ($action == 'setrefmod') {
    // TODO Verifier si module numerotation choisi peut etre active
    // par appel methode canBeActivated

    dolibarr_set_const($db, "REQUESTMANAGER_REF_ADDON", $value, 'chaine', 0, '', $conf->entity);
} else if ($action == 'setrefextmod') {
    // TODO Verifier si module numerotation choisi peut etre active
    // par appel methode canBeActivated

    dolibarr_set_const($db, "REQUESTMANAGER_REFEXT_ADDON", $value, 'chaine', 0, '', $conf->entity);
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
print load_fiche_titre($langs->trans("RequestManagerSetup"),$linkback,'title_setup');
print "<br>\n";

$head=requestmanager_admin_prepare_head();

dol_fiche_head($head, 'settings', $langs->trans("Module163018Name"), 0, 'opendsi@requestmanager');

$dirmodels=array_merge(array('/requestmanager/'),(array) $conf->modules_parts['models']);


/********************************************************
 *  Module ref numbering
 ********************************************************/
print load_fiche_titre($langs->trans("RequestManagerRefNumberingModules"),'','');

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name")."</td>\n";
print '<td>'.$langs->trans("Description")."</td>\n";
print '<td class="nowrap">'.$langs->trans("Example")."</td>\n";
print '<td align="center" width="60">'.$langs->trans("Status").'</td>';
print '<td align="center" width="16">'.$langs->trans("ShortInfo").'</td>';
print '</tr>'."\n";

clearstatcache();

foreach ($dirmodels as $reldir)
{
    $dir = dol_buildpath($reldir."core/modules/requestmanager/");

    if (is_dir($dir))
    {
        $handle = opendir($dir);
        if (is_resource($handle))
        {
            $var=true;

            while (($file = readdir($handle))!==false)
            {
                if (substr($file, 0, 23) == 'mod_requestmanager_ref_' && substr($file, dol_strlen($file)-3, 3) == 'php')
                {
                    $file = substr($file, 0, dol_strlen($file)-4);

                    require_once $dir.$file.'.php';

                    $module = new $file;

                    // Show modules according to features level
                    if ($module->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) continue;
                    if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) continue;

                    if ($module->isEnabled())
                    {
                        $var=!$var;
                        print '<tr '.$bc[$var].'><td>'.$module->nom."</td><td>\n";
                        print $module->info();
                        print '</td>';

                        // Show example of numbering module
                        print '<td class="nowrap">';
                        $tmp=$module->getExample();
                        if (preg_match('/^Error/',$tmp)) print '<div class="error">'.$langs->trans($tmp).'</div>';
                        elseif ($tmp=='NotConfigured') print $langs->trans($tmp);
                        else print $tmp;
                        print '</td>'."\n";

                        print '<td align="center">';
                        if ($conf->global->REQUESTMANAGER_REF_ADDON == "$file")
                        {
                            print img_picto($langs->trans("Activated"),'switch_on');
                        }
                        else
                        {
                            print '<a href="'.$_SERVER["PHP_SELF"].'?action=setrefmod&amp;value='.$file.'">';
                            print img_picto($langs->trans("Disabled"),'switch_off');
                            print '</a>';
                        }
                        print '</td>';

                        $requestmanager=new RequestManager($db);

                        // Info
                        $htmltooltip='';
                        $htmltooltip.=''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';
                        $nextval=$module->getNextValue($mysoc,$requestmanager);
                        if ("$nextval" != $langs->trans("NotAvailable")) {  // Keep " on nextval
                            $htmltooltip.=''.$langs->trans("NextValue").': ';
                            if ($nextval) {
                                if (preg_match('/^Error/',$nextval) || $nextval=='NotConfigured')
                                    $nextval = $langs->trans($nextval);
                                $htmltooltip.=$nextval.'<br>';
                            } else {
                                $htmltooltip.=$langs->trans($module->error).'<br>';
                            }
                        }

                        print '<td align="center">';
                        print $form->textwithpicto('',$htmltooltip,1,0);
                        print '</td>';

                        print "</tr>\n";
                    }
                }
            }
            closedir($handle);
        }
    }
}
print "</table><br>\n";


/********************************************************
 *  Module external ref numbering
 ********************************************************/
print load_fiche_titre($langs->trans("RequestManagerExternalRefNumberingModules"),'','');

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name")."</td>\n";
print '<td>'.$langs->trans("Description")."</td>\n";
print '<td class="nowrap">'.$langs->trans("Example")."</td>\n";
print '<td align="center" width="60">'.$langs->trans("Status").'</td>';
print '<td align="center" width="16">'.$langs->trans("ShortInfo").'</td>';
print '</tr>'."\n";

clearstatcache();

foreach ($dirmodels as $reldir)
{
    $dir = dol_buildpath($reldir."core/modules/requestmanager/");

    if (is_dir($dir))
    {
        $handle = opendir($dir);
        if (is_resource($handle))
        {
            $var=true;

            while (($file = readdir($handle))!==false)
            {
                if (substr($file, 0, 26) == 'mod_requestmanager_refext_' && substr($file, dol_strlen($file)-3, 3) == 'php')
                {
                    $file = substr($file, 0, dol_strlen($file)-4);

                    require_once $dir.$file.'.php';

                    $module = new $file;

                    // Show modules according to features level
                    if ($module->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) continue;
                    if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) continue;

                    if ($module->isEnabled())
                    {
                        $var=!$var;
                        print '<tr '.$bc[$var].'><td>'.$module->nom."</td><td>\n";
                        print $module->info();
                        print '</td>';

                        // Show example of numbering module
                        print '<td class="nowrap">';
                        $tmp=$module->getExample();
                        if (preg_match('/^Error/',$tmp)) print '<div class="error">'.$langs->trans($tmp).'</div>';
                        elseif ($tmp=='NotConfigured') print $langs->trans($tmp);
                        else print $tmp;
                        print '</td>'."\n";

                        print '<td align="center">';
                        if ($conf->global->REQUESTMANAGER_REFEXT_ADDON == "$file")
                        {
                            print img_picto($langs->trans("Activated"),'switch_on');
                        }
                        else
                        {
                            print '<a href="'.$_SERVER["PHP_SELF"].'?action=setrefextmod&amp;value='.$file.'">';
                            print img_picto($langs->trans("Disabled"),'switch_off');
                            print '</a>';
                        }
                        print '</td>';

                        $requestmanager=new RequestManager($db);

                        // Info
                        $htmltooltip='';
                        $htmltooltip.=''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';
                        $nextval=$module->getNextValue($mysoc,$requestmanager);
                        if ("$nextval" != $langs->trans("NotAvailable")) {  // Keep " on nextval
                            $htmltooltip.=''.$langs->trans("NextValue").': ';
                            if ($nextval) {
                                if (preg_match('/^Error/',$nextval) || $nextval=='NotConfigured')
                                    $nextval = $langs->trans($nextval);
                                $htmltooltip.=$nextval.'<br>';
                            } else {
                                $htmltooltip.=$langs->trans($module->error).'<br>';
                            }
                        }

                        print '<td align="center">';
                        print $form->textwithpicto('',$htmltooltip,1,0);
                        print '</td>';

                        print "</tr>\n";
                    }
                }
            }
            closedir($handle);
        }
    }
}
print "</table><br>\n";

/********************************************************
 *  Notification options
 ********************************************************/
print load_fiche_titre($langs->trans("RequestManagerNotificationOptions"),'','');

print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_notification_options">';

$var=true;

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="20%">'.$langs->trans("Name").'</td>'."\n";
print '<td>'.$langs->trans("Description").'</td>'."\n";
print '<td align="right">'.$langs->trans("Value").'</td>'."\n";
print "</tr>\n";

// REQUESTMANAGERMESSAGE_CREATE_NOTIFY
$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>'.$langs->trans("RequestManagerMessageCreateNotifyName").'</td>'."\n";
print '<td>'.$langs->trans("RequestManagerMessageCreateNotifyDesc").'</td>'."\n";
print '<td align="right">' . "\n";
if (!empty($conf->use_javascript_ajax)) {
    print ajax_constantonoff('REQUESTMANAGERMESSAGE_CREATE_NOTIFY');
} else {
    if (empty($conf->global->REQUESTMANAGERMESSAGE_CREATE_NOTIFY)) {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_REQUESTMANAGERMESSAGE_CREATE_NOTIFY">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
    } else {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_REQUESTMANAGERMESSAGE_CREATE_NOTIFY">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
    }
}
print '</td></tr>' . "\n";

// REQUESTMANAGER_SET_ASSIGNED_NOTIFY
$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>'.$langs->trans("RequestManagerSetAssignedNotifyName").'</td>'."\n";
print '<td>'.$langs->trans("RequestManagerSetAssignedNotifyDesc").'</td>'."\n";
print '<td align="right">' . "\n";
if (!empty($conf->use_javascript_ajax)) {
    print ajax_constantonoff('REQUESTMANAGER_SET_ASSIGNED_NOTIFY');
} else {
    if (empty($conf->global->REQUESTMANAGER_SET_ASSIGNED_NOTIFY)) {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_REQUESTMANAGER_SET_ASSIGNED_NOTIFY">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
    } else {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_REQUESTMANAGER_SET_ASSIGNED_NOTIFY">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
    }
}
print '</td></tr>' . "\n";

// REQUESTMANAGER_STATUS_MODIFY_NOTIFY
$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>'.$langs->trans("RequestManagerStatusModifyNotifyName").'</td>'."\n";
print '<td>'.$langs->trans("RequestManagerStatusModifyNotifyDesc").'</td>'."\n";
print '<td align="right">' . "\n";
if (!empty($conf->use_javascript_ajax)) {
    print ajax_constantonoff('REQUESTMANAGER_STATUS_MODIFY_NOTIFY');
} else {
    if (empty($conf->global->REQUESTMANAGER_STATUS_MODIFY_NOTIFY)) {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_REQUESTMANAGER_STATUS_MODIFY_NOTIFY">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
    } else {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_REQUESTMANAGER_STATUS_MODIFY_NOTIFY">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
    }
}
print '</td></tr>' . "\n";

print '</table>';

print '</form><br>' . "\n";

/********************************************************
 *  Notification assigned options
 ********************************************************/
print load_fiche_titre($langs->trans("RequestManagerNotificationAssignedOptions"),'','');

print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_notification_assigned_options">';

$var=true;

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="20%">'.$langs->trans("Name").'</td>'."\n";
print '<td>'.$langs->trans("Description").'</td>'."\n";
print '<td align="right">'.$langs->trans("Value").'</td>'."\n";
print "</tr>\n";

// REQUESTMANAGER_NOTIFICATION_ASSIGNED_BY_EMAIL
$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>'.$langs->trans("RequestManagerNotificationAssignedByEmailName").'</td>'."\n";
print '<td>'.$langs->trans("RequestManagerNotificationAssignedByEmailDesc").'</td>'."\n";
print '<td align="right">' . "\n";
if (!empty($conf->use_javascript_ajax)) {
    print ajax_constantonoff('REQUESTMANAGER_NOTIFICATION_ASSIGNED_BY_EMAIL');
} else {
    if (empty($conf->global->REQUESTMANAGER_NOTIFICATION_ASSIGNED_BY_EMAIL)) {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_REQUESTMANAGER_NOTIFICATION_ASSIGNED_BY_EMAIL">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
    } else {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_REQUESTMANAGER_NOTIFICATION_ASSIGNED_BY_EMAIL">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
    }
}
print '</td></tr>' . "\n";

// REQUESTMANAGER_NOTIFICATION_ASSIGNED_BY_WEBSITE
$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>'.$langs->trans("RequestManagerNotificationAssignedByWebSiteName").'</td>'."\n";
print '<td>'.$langs->trans("RequestManagerNotificationAssignedByWebSiteDesc").'</td>'."\n";
print '<td align="right">' . "\n";
if (!empty($conf->use_javascript_ajax)) {
    print ajax_constantonoff('REQUESTMANAGER_NOTIFICATION_ASSIGNED_BY_WEBSITE');
} else {
    if (empty($conf->global->REQUESTMANAGER_NOTIFICATION_ASSIGNED_BY_WEBSITE)) {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_REQUESTMANAGER_NOTIFICATION_ASSIGNED_BY_WEBSITE">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
    } else {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_REQUESTMANAGER_NOTIFICATION_ASSIGNED_BY_WEBSITE">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
    }
}
print '</td></tr>' . "\n";

// REQUESTMANAGER_ASSIGNED_NOTIFICATION_SEND_FROM
$var=!$var;
print '<tr '.$bc[$var].'>'."\n";
print '<td>'.$langs->trans("RequestManagerAssignedNotificationSendFromName").'</td>'."\n";
print '<td>'.$langs->trans("RequestManagerAssignedNotificationSendFromDesc").'</td>'."\n";
print '<td align="right">'."\n";
$email = GETPOST('REQUESTMANAGER_ASSIGNED_NOTIFICATION_SEND_FROM', "alpha");
print '<input type="text" size="100" name="REQUESTMANAGER_ASSIGNED_NOTIFICATION_SEND_FROM" value="'.(!empty($email) ? $email : $conf->global->REQUESTMANAGER_ASSIGNED_NOTIFICATION_SEND_FROM).'">';
print '</td></tr>'."\n";

// REQUESTMANAGER_SPLIT_ASSIGNED_NOTIFICATION
$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>'.$langs->trans("RequestManagerSplitAssignedNotificationName").'</td>'."\n";
print '<td>'.$langs->trans("RequestManagerSplitAssignedNotificationDesc").'</td>'."\n";
print '<td align="right">' . "\n";
if (!empty($conf->use_javascript_ajax)) {
    print ajax_constantonoff('REQUESTMANAGER_SPLIT_ASSIGNED_NOTIFICATION');
} else {
    if (empty($conf->global->REQUESTMANAGER_SPLIT_ASSIGNED_NOTIFICATION)) {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_REQUESTMANAGER_SPLIT_ASSIGNED_NOTIFICATION">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
    } else {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_REQUESTMANAGER_SPLIT_ASSIGNED_NOTIFICATION">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
    }
}
print '</td></tr>' . "\n";

print '</table>';

print '<br>';
print '<div align="center">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</div>';

print '</form>';

/********************************************************
 *  Notification requester options
 ********************************************************/
print load_fiche_titre($langs->trans("RequestManagerNotificationRequesterOptions"),'','');

print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_notification_requester_options">';

$var=true;

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="20%">'.$langs->trans("Name").'</td>'."\n";
print '<td>'.$langs->trans("Description").'</td>'."\n";
print '<td align="right">'.$langs->trans("Value").'</td>'."\n";
print "</tr>\n";

// REQUESTMANAGER_REQUESTER_NOTIFICATION_SEND_FROM
$var=!$var;
print '<tr '.$bc[$var].'>'."\n";
print '<td>'.$langs->trans("RequestManagerRequesterNotificationSendFromName").'</td>'."\n";
print '<td>'.$langs->trans("RequestManagerRequesterNotificationSendFromDesc").'</td>'."\n";
print '<td align="right">'."\n";
$email = GETPOST('REQUESTMANAGER_REQUESTER_NOTIFICATION_SEND_FROM', "alpha");
print '<input type="text" size="100" name="REQUESTMANAGER_REQUESTER_NOTIFICATION_SEND_FROM" value="'.(!empty($email) ? $email : $conf->global->REQUESTMANAGER_REQUESTER_NOTIFICATION_SEND_FROM).'">';
print '</td></tr>'."\n";

// REQUESTMANAGER_SPLIT_REQUESTER_NOTIFICATION
$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>'.$langs->trans("RequestManagerSplitRequesterNotificationName").'</td>'."\n";
print '<td>'.$langs->trans("RequestManagerSplitRequesterNotificationDesc").'</td>'."\n";
print '<td align="right">' . "\n";
if (!empty($conf->use_javascript_ajax)) {
    print ajax_constantonoff('REQUESTMANAGER_SPLIT_REQUESTER_NOTIFICATION');
} else {
    if (empty($conf->global->REQUESTMANAGER_SPLIT_REQUESTER_NOTIFICATION)) {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_REQUESTMANAGER_SPLIT_REQUESTER_NOTIFICATION">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
    } else {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_REQUESTMANAGER_SPLIT_REQUESTER_NOTIFICATION">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
    }
}
print '</td></tr>' . "\n";

print '</table>';

print '<br>';
print '<div align="center">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</div>';

print '</form>';

/********************************************************
 *  Notification watchers options
 ********************************************************/
print load_fiche_titre($langs->trans("RequestManagerNotificationWatchersOptions"),'','');

print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_notification_watchers_options">';

$var=true;

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="20%">'.$langs->trans("Name").'</td>'."\n";
print '<td>'.$langs->trans("Description").'</td>'."\n";
print '<td align="right">'.$langs->trans("Value").'</td>'."\n";
print "</tr>\n";

// REQUESTMANAGER_WATCHERS_NOTIFICATION_SEND_FROM
$var=!$var;
print '<tr '.$bc[$var].'>'."\n";
print '<td>'.$langs->trans("RequestManagerWatchersNotificationSendFromName").'</td>'."\n";
print '<td>'.$langs->trans("RequestManagerWatchersNotificationSendFromDesc").'</td>'."\n";
print '<td align="right">'."\n";
$email = GETPOST('REQUESTMANAGER_WATCHERS_NOTIFICATION_SEND_FROM', "alpha");
print '<input type="text" size="100" name="REQUESTMANAGER_WATCHERS_NOTIFICATION_SEND_FROM" value="'.(!empty($email) ? $email : $conf->global->REQUESTMANAGER_WATCHERS_NOTIFICATION_SEND_FROM).'">';
print '</td></tr>'."\n";

// REQUESTMANAGER_SPLIT_WATCHERS_NOTIFICATION
$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>'.$langs->trans("RequestManagerSplitWatchersNotificationName").'</td>'."\n";
print '<td>'.$langs->trans("RequestManagerSplitWatchersNotificationDesc").'</td>'."\n";
print '<td align="right">' . "\n";
if (!empty($conf->use_javascript_ajax)) {
    print ajax_constantonoff('REQUESTMANAGER_SPLIT_WATCHERS_NOTIFICATION');
} else {
    if (empty($conf->global->REQUESTMANAGER_SPLIT_WATCHERS_NOTIFICATION)) {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_REQUESTMANAGER_SPLIT_WATCHERS_NOTIFICATION">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
    } else {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_REQUESTMANAGER_SPLIT_WATCHERS_NOTIFICATION">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
    }
}
print '</td></tr>' . "\n";

print '</table>';

print '<br>';
print '<div align="center">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</div>';

print '</form>';

/********************************************************
 *  General options
 ********************************************************/
print load_fiche_titre($langs->trans("Other"),'','');

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

// REQUESTMANAGER_AUTO_ADD_CONTRACT_OF_PRINCIPAL_COMPANY
$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>'.$langs->trans("RequestManagerAutoAddContractOfPrincipalCompanyName").'</td>'."\n";
print '<td>'.$langs->trans("RequestManagerAutoAddContractOfPrincipalCompanyDesc").'</td>'."\n";
print '<td align="right">' . "\n";
if (!empty($conf->use_javascript_ajax)) {
    print ajax_constantonoff('REQUESTMANAGER_AUTO_ADD_CONTRACT_OF_PRINCIPAL_COMPANY');
} else {
    if (empty($conf->global->REQUESTMANAGER_AUTO_ADD_CONTRACT_OF_PRINCIPAL_COMPANY)) {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_REQUESTMANAGER_AUTO_ADD_CONTRACT_OF_PRINCIPAL_COMPANY">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
    } else {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_REQUESTMANAGER_AUTO_ADD_CONTRACT_OF_PRINCIPAL_COMPANY">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
    }
}
print '</td></tr>' . "\n";

// REQUESTMANAGER_AUTO_ADD_CONTRACT_OF_BENEFICIAL_COMPANY
$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>'.$langs->trans("RequestManagerAutoAddContractOfBeneficialCompanyName").'</td>'."\n";
print '<td>'.$langs->trans("RequestManagerAutoAddContractOfBeneficialCompanyDesc").'</td>'."\n";
print '<td align="right">' . "\n";
if (!empty($conf->use_javascript_ajax)) {
    print ajax_constantonoff('REQUESTMANAGER_AUTO_ADD_CONTRACT_OF_BENEFICIAL_COMPANY');
} else {
    if (empty($conf->global->REQUESTMANAGER_AUTO_ADD_CONTRACT_OF_BENEFICIAL_COMPANY)) {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_REQUESTMANAGER_AUTO_ADD_CONTRACT_OF_BENEFICIAL_COMPANY">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
    } else {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_REQUESTMANAGER_AUTO_ADD_CONTRACT_OF_BENEFICIAL_COMPANY">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
    }
}
print '</td></tr>' . "\n";

// REQUESTMANAGER_CONTRACT_SEARCH_IN_PARENT_COMPANY
$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>'.$langs->trans("RequestManagerContractSearchInParentCompanyName").'</td>'."\n";
print '<td>'.$langs->trans("RequestManagerContractSearchInParentCompanyDesc").'</td>'."\n";
print '<td align="right">' . "\n";
if (empty($conf->global->REQUESTMANAGER_CONTRACT_SEARCH_IN_PARENT_COMPANY)) {
    print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_REQUESTMANAGER_CONTRACT_SEARCH_IN_PARENT_COMPANY&REQUESTMANAGER_CONTRACT_SEARCH_IN_PARENT_COMPANY=1">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
} else {
    print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_REQUESTMANAGER_CONTRACT_SEARCH_IN_PARENT_COMPANY&REQUESTMANAGER_CONTRACT_SEARCH_IN_PARENT_COMPANY=0">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
}
print '</td></tr>' . "\n";

//// REQUESTMANAGER_OPERATION_TIME_DEFAULT
//$var=!$var;
//print '<tr '.$bc[$var].'>'."\n";
//print '<td>' . $langs->trans("RequestManagerOperationTimeDefaultName") . '</td>'."\n";
//print '<td>' . $langs->trans("RequestManagerOperationTimeDefaultDesc") . '</td>'."\n";
//print '<td align="right">'."\n";
//print '<input type="number" name="REQUESTMANAGER_OPERATION_TIME_DEFAULT" min="0" value="' . intval($conf->global->REQUESTMANAGER_OPERATION_TIME_DEFAULT) . '">';
//print ' ' . $langs->trans("Minutes");
//print '</td></tr>'."\n";
//
//// REQUESTMANAGER_DEADLINE_TIME_DEFAULT
//$var=!$var;
//print '<tr '.$bc[$var].'>'."\n";
//print '<td>' . $langs->trans("RequestManagerDeadlineTimeDefaultName") . '</td>'."\n";
//print '<td>' . $langs->trans("RequestManagerDeadlineTimeDefaultDesc") . '</td>'."\n";
//print '<td align="right">'."\n";
//print '<input type="number" name="REQUESTMANAGER_DEADLINE_TIME_DEFAULT" min="0" value="' . intval($conf->global->REQUESTMANAGER_DEADLINE_TIME_DEFAULT) . '">';
//print ' ' . $langs->trans("Minutes");
//print '</td></tr>'."\n";

// REQUESTMANAGER_ROOT_PRODUCT_CATEGORIES
$var=!$var;
print '<tr '.$bc[$var].'>'."\n";
print '<td>' . $langs->trans("RequestManagerRootProductCategoriesName") . '</td>'."\n";
print '<td>' . $langs->trans("RequestManagerRootProductCategoriesDesc") . '</td>'."\n";
print '<td align="right">'."\n";
print $form->select_all_categories('product', $conf->global->REQUESTMANAGER_ROOT_PRODUCT_CATEGORIES, "REQUESTMANAGER_ROOT_PRODUCT_CATEGORIES");
print '</td></tr>'."\n";

// REQUESTMANAGER_ROOT_PRODUCT_CATEGORY_INCLUDE
$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>'.$langs->trans("RequestManagerRootProductCategoryIncludeName").'</td>'."\n";
print '<td>'.$langs->trans("RequestManagerRootProductCategoryIncludeDesc").'</td>'."\n";
print '<td align="right">' . "\n";
if (!empty($conf->use_javascript_ajax)) {
    print ajax_constantonoff('REQUESTMANAGER_ROOT_PRODUCT_CATEGORY_INCLUDE');
} else {
    if (empty($conf->global->REQUESTMANAGER_ROOT_PRODUCT_CATEGORY_INCLUDE)) {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_REQUESTMANAGER_ROOT_PRODUCT_CATEGORY_INCLUDE">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
    } else {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_REQUESTMANAGER_ROOT_PRODUCT_CATEGORY_INCLUDE">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
    }
}
print '</td></tr>' . "\n";

$position_array = array('top'=>$langs->trans('Top'), 'bottom'=>$langs->trans('Bottom'));
// REQUESTMANAGER_POSITION_BLOC_OBJECT_LINKED
$var=!$var;
print '<tr '.$bc[$var].'>'."\n";
print '<td>' . $langs->trans("RequestManagerPositionBlocObjectLinkedName") . '</td>'."\n";
print '<td>' . $langs->trans("RequestManagerPositionBlocObjectLinkedDesc") . '</td>'."\n";
print '<td align="right">'."\n";
print $form->selectarray("REQUESTMANAGER_POSITION_BLOC_OBJECT_LINKED", $position_array, $conf->global->REQUESTMANAGER_POSITION_BLOC_OBJECT_LINKED);
print '</td></tr>'."\n";

// REQUESTMANAGER_POSITION_LINK_NEW_OBJECT_LINKED
$var=!$var;
print '<tr '.$bc[$var].'>'."\n";
print '<td>' . $langs->trans("RequestManagerPositionLinkNewObjectLinkedName") . '</td>'."\n";
print '<td>' . $langs->trans("RequestManagerPositionLinkNewObjectLinkedDesc") . '</td>'."\n";
print '<td align="right">'."\n";
print $form->selectarray("REQUESTMANAGER_POSITION_LINK_NEW_OBJECT_LINKED", $position_array, $conf->global->REQUESTMANAGER_POSITION_LINK_NEW_OBJECT_LINKED);
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
