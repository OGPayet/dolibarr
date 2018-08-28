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
$langs->load("requestmanager@requestmanager");
$langs->load("opendsi@requestmanager");

if (!$user->admin) accessforbidden();

$action = GETPOST('action','alpha');


/*
 *	Actions
 */

$errors = [];
$error = 0;

if ($action == 'updateMask') {
    $maskconst = GETPOST('maskconst', 'alpha');
    $maskvalue = GETPOST('maskvalue', 'alpha');
    if ($maskconst) $res = dolibarr_set_const($db, $maskconst, $maskvalue, 'chaine', 0, '', $conf->entity);

    if (!$res > 0) $error++;

    if (!$error) {
        setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
    } else {
        setEventMessages($langs->trans("Error"), null, 'errors');
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
    $res = dolibarr_set_const($db, 'REQUESTMANAGER_NOTIFICATION_SEND_FROM', GETPOST('REQUESTMANAGER_NOTIFICATION_SEND_FROM'), 'chaine', 0, '', $conf->entity);
    if (!$res > 0) {
        $errors[] = $db->lasterror();
        $error++;
    }

    // operation time default in minute
    $operationTimeDefault = GETPOST('REQUESTMANAGER_OPERATION_TIME_DEFAULT', 'int') ? GETPOST('REQUESTMANAGER_OPERATION_TIME_DEFAULT', 'int') : 0;
    $res = dolibarr_set_const($db, 'REQUESTMANAGER_OPERATION_TIME_DEFAULT', $operationTimeDefault, 'chaine', 0, '', $conf->entity);
    if (!$res > 0) {
        $errors[] = $db->lasterror();
        $error++;
    }

    // deadline time default in minute
    $deadlineTimeDefault = GETPOST('REQUESTMANAGER_DEADLINE_TIME_DEFAULT', 'int') ? GETPOST('REQUESTMANAGER_DEADLINE_TIME_DEFAULT', 'int') : 0;
    $res = dolibarr_set_const($db, 'REQUESTMANAGER_DEADLINE_TIME_DEFAULT', $deadlineTimeDefault, 'chaine', 0, '', $conf->entity);
    if (!$res > 0) {
        $errors[] = $db->lasterror();
        $error++;
    }

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

    /*$res = dolibarr_set_const($db, 'requestmanager_BASE_PRICE_DISCOUNT', GETPOST('requestmanager_BASE_PRICE_DISCOUNT', "alpha"), 'chaine', 0, '', $conf->entity);
    if (!$res > 0) {
        $errors[] = $db->lasterror();
        $error++;
    }

    $res = dolibarr_set_const($db, 'requestmanager_CALCULATION_MODE_WITH_EXISTING_DISCOUNT', GETPOST('requestmanager_CALCULATION_MODE_WITH_EXISTING_DISCOUNT', "int"), 'chaine', 0, '', $conf->entity);
    if (!$res > 0) {
        $errors[] = $db->lasterror();
        $error++;
    }

    $round_precision = GETPOST('requestmanager_DISCOUNT_ROUND_PRECISION', "int");
    $res = dolibarr_set_const($db, 'requestmanager_DISCOUNT_ROUND_PRECISION', (empty($round_precision) ? 2 : $round_precision), 'chaine', 0, '', $conf->entity);
    if (!$res > 0) {
        $errors[] = $db->lasterror();
        $error++;
    }*/

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


/*
 *  Module ref numerotation
 */
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


/*
 *  Module external ref numerotation
 */
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

print '<br>';
print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set">';

$var=true;

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>'."\n";
print '<td>'.$langs->trans("Description").'</td>'."\n";
print '<td align="right">'.$langs->trans("Value").'</td>'."\n";
print "</tr>\n";

// REQUESTMANAGER_NOTIFICATION_SEND_FROM
$var=!$var;
print '<tr '.$bc[$var].'>'."\n";
print '<td>'.$langs->trans("RequestManagerNotificationSendFromName").'</td>'."\n";
print '<td>'.$langs->trans("RequestManagerNotificationSendFromDesc").'</td>'."\n";
print '<td align="right">'."\n";
print '<input type="text" name="REQUESTMANAGER_NOTIFICATION_SEND_FROM" value="'.$conf->global->REQUESTMANAGER_NOTIFICATION_SEND_FROM.'">';
print '</td></tr>'."\n";

// REQUESTMANAGER_NOTIFICATION_USERS_IN_DB
$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>'.$langs->trans("RequestManagerNotificationUsersName").'</td>'."\n";
print '<td>'.$langs->trans("RequestManagerNotificationUsersDesc").'</td>'."\n";
print '<td align="right">' . "\n";
if (empty($conf->global->REQUESTMANAGER_NOTIFICATION_USERS_IN_DB)) {
    print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_REQUESTMANAGER_NOTIFICATION_USERS_IN_DB&REQUESTMANAGER_NOTIFICATION_USERS_IN_DB=1">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
} else {
    print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_REQUESTMANAGER_NOTIFICATION_USERS_IN_DB&REQUESTMANAGER_NOTIFICATION_USERS_IN_DB=0">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
}
print '</td></tr>' . "\n";

// REQUESTMANAGER_NOTIFICATION_BY_MAIL
$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>'.$langs->trans("RequestManagerNotificationByMailName").'</td>'."\n";
print '<td>'.$langs->trans("RequestManagerNotificationByMailDesc").'</td>'."\n";
print '<td align="right">' . "\n";
if (empty($conf->global->REQUESTMANAGER_NOTIFICATION_BY_MAIL)) {
    print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_REQUESTMANAGER_NOTIFICATION_BY_MAIL&REQUESTMANAGER_NOTIFICATION_BY_MAIL=1">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
} else {
    print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_REQUESTMANAGER_NOTIFICATION_BY_MAIL&REQUESTMANAGER_NOTIFICATION_BY_MAIL=0">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
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

// REQUESTMANAGER_OPERATION_TIME_DEFAULT
$var=!$var;
print '<tr '.$bc[$var].'>'."\n";
print '<td>' . $langs->trans("RequestManagerOperationTimeDefaultName") . '</td>'."\n";
print '<td>' . $langs->trans("RequestManagerOperationTimeDefaultDesc") . '</td>'."\n";
print '<td align="right">'."\n";
print '<input type="number" name="REQUESTMANAGER_OPERATION_TIME_DEFAULT" min="0" value="' . intval($conf->global->REQUESTMANAGER_OPERATION_TIME_DEFAULT) . '">';
print ' ' . $langs->trans("Minutes");
print '</td></tr>'."\n";

// REQUESTMANAGER_DEADLINE_TIME_DEFAULT
$var=!$var;
print '<tr '.$bc[$var].'>'."\n";
print '<td>' . $langs->trans("RequestManagerDeadlineTimeDefaultName") . '</td>'."\n";
print '<td>' . $langs->trans("RequestManagerDeadlineTimeDefaultDesc") . '</td>'."\n";
print '<td align="right">'."\n";
print '<input type="number" name="REQUESTMANAGER_DEADLINE_TIME_DEFAULT" min="0" value="' . intval($conf->global->REQUESTMANAGER_DEADLINE_TIME_DEFAULT) . '">';
print ' ' . $langs->trans("Minutes");
print '</td></tr>'."\n";

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

/*
// requestmanager_BASE_PRICE_DISCOUNT
$var=!$var;
print '<tr '.$bc[$var].'>'."\n";
print '<td>'.$langs->trans("requestmanagerBasePriceDiscountName").'</td>'."\n";
print '<td>'.$langs->trans("requestmanagerBasePriceDiscountDesc").'</td>'."\n";
print '<td align="right">'."\n";
print '<input type="radio" name="requestmanager_BASE_PRICE_DISCOUNT" value="HT"'.($conf->global->requestmanager_BASE_PRICE_DISCOUNT=='HT'?' checked':'').'>&nbsp;'.$langs->trans("HT")."\n";
print '&nbsp;'."\n";
print '<input type="radio" name="requestmanager_BASE_PRICE_DISCOUNT" value="TTC"'.($conf->global->requestmanager_BASE_PRICE_DISCOUNT=='TTC'?' checked':'').'>&nbsp;'.$langs->trans("TTC")."\n";
print '</td></tr>'."\n";

// requestmanager_DISABLED_DISCOUNT_WHEN_HAS_CUSTOMER_PRICE
$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>'.$langs->trans("requestmanagerDisabledDiscountWhenHasCustomerPriceName").'</td>'."\n";
print '<td>'.$langs->trans("requestmanagerDisabledDiscountWhenHasCustomerPriceDesc").'</td>'."\n";
print '<td align="right">' . "\n";
if (!empty($conf->use_javascript_ajax)) {
    print ajax_constantonoff('requestmanager_DISABLED_DISCOUNT_WHEN_HAS_CUSTOMER_PRICE');
} else {
    if (empty($conf->global->requestmanager_DISABLED_DISCOUNT_WHEN_HAS_CUSTOMER_PRICE)) {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_requestmanager_DISABLED_DISCOUNT_WHEN_HAS_CUSTOMER_PRICE">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
    } else {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_requestmanager_DISABLED_DISCOUNT_WHEN_HAS_CUSTOMER_PRICE">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
    }
}
print '</td></tr>' . "\n";

// requestmanager_CALCULATION_MODE_WITH_EXISTING_DISCOUNT
$var=!$var;
print '<tr '.$bc[$var].'>'."\n";
print '<td>'.$langs->trans("requestmanagerCalculationModeWithExistingDiscountName").'</td>'."\n";
print '<td>'.$langs->trans("requestmanagerCalculationModeWithExistingDiscountDesc").'</td>'."\n";
print '<td align="right">'."\n";
print '<input type="radio" name="requestmanager_CALCULATION_MODE_WITH_EXISTING_DISCOUNT" value="1"'.($conf->global->requestmanager_CALCULATION_MODE_WITH_EXISTING_DISCOUNT==1?' checked':'').'>&nbsp;'.$langs->trans("requestmanagerCalculationModeReplace")."\n";
print '&nbsp;'."\n";
print '<input type="radio" name="requestmanager_CALCULATION_MODE_WITH_EXISTING_DISCOUNT" value="2"'.($conf->global->requestmanager_CALCULATION_MODE_WITH_EXISTING_DISCOUNT==2?' checked':'').'>&nbsp;'.$langs->trans("requestmanagerCalculationModeCascade")."\n";
print '</td></tr>'."\n";

// requestmanager_DISCOUNT_ROUND_PRECISION
$var=!$var;
print '<tr '.$bc[$var].'>'."\n";
print '<td>'.$langs->trans("requestmanagerDiscountRoundPrecisionName").'</td>'."\n";
print '<td>'.$langs->trans("requestmanagerDiscountRoundPrecisionDesc").'</td>'."\n";
print '<td align="right">'."\n";
print '<input type="text" name="requestmanager_DISCOUNT_ROUND_PRECISION" value="'.$conf->global->requestmanager_DISCOUNT_ROUND_PRECISION.'">';
print '</td></tr>'."\n";
*/

print '</table>';

dol_fiche_end();

print '<br>';
print '<div align="center">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</div>';

print '</form>';

llxFooter();

$db->close();
