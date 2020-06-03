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
 *	    \file       htdocs/synergiestech/admin/setup.php
 *		\ingroup    synergiestech
 *		\brief      Page to setup synergiestech module
 */

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include '../../../main.inc.php';		// to work if your module directory is into a subdir of root htdocs directory
if (! $res) die("Include of main fails");
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
dol_include_once('/synergiestech/lib/synergiestech.lib.php');
dol_include_once('/advancedictionaries/class/html.formdictionary.class.php');
dol_include_once('/advancedictionaries/class/dictionary.class.php');

$langs->load("admin");
$langs->load("synergiestech@synergiestech");
$langs->load("opendsi@synergiestech");

if (!$user->admin) accessforbidden();

$action = GETPOST('action','alpha');

// Get request types list
$requestmanagerrequesttype = Dictionary::getDictionary($db, 'requestmanager', 'requestmanagerrequesttype');
$request_types = $requestmanagerrequesttype->fetch_lines(1, array(), array(), 0, 0, false, true);
$request_types_array = array();
foreach ($request_types as $request_type) {
    $request_types_array[$request_type->id] = $request_type->fields['label'];
}

/*
 *	Actions
 */

if (preg_match('/set_(.*)/',$action,$reg))
{
    $code=$reg[1];
    $value=(GETPOST($code) ? GETPOST($code) : 1);
    $error = 0;

    if ($code == 'SYNERGIESTECH_FORCE_ATTACH_EQUIPMENTS_AFTER_SHIPPING_CREATED') {
        if (dolibarr_set_const($db, 'SYNERGIESTECH_ENABLED_WORKFLOW_SHIPPING_CREATED_TO_ATTACH_EQUIPMENTS', $value, 'chaine', 0, '', $conf->entity) <= 0) {
            $error++;
        }
    } elseif ($code == 'SYNERGIESTECH_FORCE_SET_EQUIPMENTS_AFTER_ORDER_SUPPLIER_DISPATCH') {
        if (dolibarr_set_const($db, 'SYNERGIESTECH_ENABLED_WORKFLOW_ORDER_SUPPLIER_DISPATCH_TO_SET_EQUIPMENTS', $value, 'chaine', 0, '', $conf->entity) <= 0) {
            $error++;
        }
    }

    if (! $error) {
        if (dolibarr_set_const($db, $code, $value, 'chaine', 0, '', $conf->entity) <= 0) {
            $error++;
        }
    }

    if (! $error)
    {
        Header("Location: ".$_SERVER["PHP_SELF"]);
        exit;
    }
    else
    {
        dol_print_error($db);
    }
} elseif (preg_match('/del_(.*)/',$action,$reg)) {
    $code=$reg[1];
    $error = 0;

    if ($code == 'SYNERGIESTECH_ENABLED_WORKFLOW_SHIPPING_CREATED_TO_ATTACH_EQUIPMENTS') {
        if (dolibarr_del_const($db, 'SYNERGIESTECH_FORCE_ATTACH_EQUIPMENTS_AFTER_SHIPPING_CREATED', $conf->entity) <= 0) {
            $error++;
        }
    } elseif ($code == 'SYNERGIESTECH_ENABLED_WORKFLOW_ORDER_SUPPLIER_DISPATCH_TO_SET_EQUIPMENTS') {
        if (dolibarr_del_const($db, 'SYNERGIESTECH_FORCE_SET_EQUIPMENTS_AFTER_ORDER_SUPPLIER_DISPATCH', $conf->entity) <= 0) {
            $error++;
        }
    }

    if (! $error) {
        if (dolibarr_del_const($db, $code, $conf->entity) <= 0) {
            $error++;
        }
    }

    if (! $error)
    {
        Header("Location: ".$_SERVER["PHP_SELF"]);
        exit;
    }
    else
    {
        dol_print_error($db);
    }
} elseif ($action == 'set') {
    $error = 0;

    if (dolibarr_set_const($db, 'SYNERGIESTECH_PRODUCT_CATEGORY_FOR_CONTRACT_FORMULE', GETPOST('SYNERGIESTECH_PRODUCT_CATEGORY_FOR_CONTRACT_FORMULE', 'int'), 'chaine', 0, '', $conf->entity) <= 0) {
        $error++;
    }

    /*if (dolibarr_set_const($db, 'SYNERGIESTECH_PRODUCT_CATEGORY_FOR_ADVANCEDTICKECT_EMPLACEMENT', GETPOST('SYNERGIESTECH_PRODUCT_CATEGORY_FOR_ADVANCEDTICKECT_EMPLACEMENT', 'int'), 'chaine', 0, '', $conf->entity) <= 0) {
        $error++;
    }*/

    if (dolibarr_set_const($db, 'SYNERGIESTECH_PRINCIPAL_WAREHOUSE', GETPOST('SYNERGIESTECH_PRINCIPAL_WAREHOUSE', 'int'), 'chaine', 0, '', $conf->entity) <= 0) {
        $error++;
    }

    if (dolibarr_set_const($db, 'SYNERGIESTECH_PRINCIPAL_WAREHOUSE_NB_SHOWED', GETPOST('SYNERGIESTECH_PRINCIPAL_WAREHOUSE_NB_SHOWED', 'int'), 'chaine', 0, '', $conf->entity) <= 0) {
        $error++;
    }

    if (dolibarr_set_const($db, 'SYNERGIESTECH_CREATE_REQUEST_EVENT', implode(',', GETPOST('SYNERGIESTECH_CREATE_REQUEST_EVENT', 'array')), 'chaine', 0, '', $conf->entity) <= 0) {
        $error++;
    }

    if (dolibarr_set_const($db, 'SYNERGIESTECH_DEFAULT_REQUEST_TYPE_WHEN_CREATE', GETPOST('SYNERGIESTECH_DEFAULT_REQUEST_TYPE_WHEN_CREATE', 'int'), 'chaine', 0, '', $conf->entity) <= 0) {
        $error++;
    }
    $request_types_selected = !empty(GETPOST('SYNERGIESTECH_AUTO_ADD_CONTRACT_IF_MISSING', 'array')) ? implode(',', GETPOST('SYNERGIESTECH_AUTO_ADD_CONTRACT_IF_MISSING', 'array')) : "";

    if (dolibarr_set_const($db, 'SYNERGIESTECH_AUTO_ADD_CONTRACT_IF_MISSING', $request_types_selected, 'chaine', 0, '', $conf->entity) <= 0) {
        $error++;
    }

    if (dolibarr_set_const($db, 'SYNERGIESTECH_USERACCESSCONTROL_ACCOUNTENABLE', GETPOST('SYNERGIESTECH_USERACCESSCONTROL_ACCOUNTENABLE', 'alpha'), 'chaine', 0, '', 0) <= 0) {//No entity
        $error++;
    }

    if (dolibarr_set_const($db, 'SYNERGIESTECH_USERACCESSCONTROL_ACCOUNTDISABLE', GETPOST('SYNERGIESTECH_USERACCESSCONTROL_ACCOUNTDISABLE', 'alpha'), 'chaine', 0, '', 0) <= 0) {//No entity
        $error++;
    }

    if (dolibarr_set_const($db, 'SYNERGIESTECH_USERACCESSCONTROL_LDAPFIELDNAME', GETPOST('SYNERGIESTECH_USERACCESSCONTROL_LDAPFIELDNAME', 'alpha'), 'chaine', 0, '', 0) <= 0) {//No entity
        $error++;
    }

    if (dolibarr_set_const($db, 'SYNERGIESTECH_USERMEMBEROF_LDAPFIELD', GETPOST('SYNERGIESTECH_USERMEMBEROF_LDAPFIELD', 'alpha'), 'chaine', 0, '', 0) <= 0) {//No entity
        $error++;
    }

    if (!$error) {
        Header("Location: " . $_SERVER["PHP_SELF"]);
        exit;
    } else {
        dol_print_error($db);
    }
}

/*
 *	View
 */

$formproduct = new FormProduct($db);
$formother = new FormOther($db);
$formactions = new FormActions($db);
$formdictionary = new FormDictionary($db);
$form = new Form($db);

llxHeader();

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("SynergiesTechSetup"),$linkback,'title_setup');
print "<br>\n";

$head=synergiestech_prepare_head();

dol_fiche_head($head, 'settings', $langs->trans("Module500100Name"), 0, 'action');


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

// SYNERGIESTECH_ENABLED_WORKFLOW_SHIPPING_CREATED_TO_ATTACH_EQUIPMENTS
$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>' . $langs->trans("SynergiesTechEnabledWorkflowShippingCreatedToAttachEquipments") . '</td>' . "\n";
print '<td align="center">&nbsp;</td>' . "\n";
print '<td align="right">' . "\n";
if (empty($conf->global->SYNERGIESTECH_ENABLED_WORKFLOW_SHIPPING_CREATED_TO_ATTACH_EQUIPMENTS)) {
    print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_SYNERGIESTECH_ENABLED_WORKFLOW_SHIPPING_CREATED_TO_ATTACH_EQUIPMENTS">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
} else {
    print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_SYNERGIESTECH_ENABLED_WORKFLOW_SHIPPING_CREATED_TO_ATTACH_EQUIPMENTS">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
}
print '</td></tr>' . "\n";

// SYNERGIESTECH_FORCE_ATTACH_EQUIPMENTS_AFTER_SHIPPING_CREATED
$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>' . $langs->trans("SynergiesTechForceAttachEquipmentsAfterShippingCreated") . '</td>' . "\n";
print '<td align="center">&nbsp;</td>' . "\n";
print '<td align="right">' . "\n";
if (empty($conf->global->SYNERGIESTECH_FORCE_ATTACH_EQUIPMENTS_AFTER_SHIPPING_CREATED)) {
    print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_SYNERGIESTECH_FORCE_ATTACH_EQUIPMENTS_AFTER_SHIPPING_CREATED">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
} else {
    print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_SYNERGIESTECH_FORCE_ATTACH_EQUIPMENTS_AFTER_SHIPPING_CREATED">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
}
print '</td></tr>' . "\n";

// SYNERGIESTECH_ENABLED_WORKFLOW_ORDER_SUPPLIER_DISPATCH_TO_SET_EQUIPMENTS
$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>' . $langs->trans("SynergiesTechEnabledWorkflowOrderSupplierDispatchToSetEquipments") . '</td>' . "\n";
print '<td align="center">&nbsp;</td>' . "\n";
print '<td align="right">' . "\n";
if (empty($conf->global->SYNERGIESTECH_ENABLED_WORKFLOW_ORDER_SUPPLIER_DISPATCH_TO_SET_EQUIPMENTS)) {
    print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_SYNERGIESTECH_ENABLED_WORKFLOW_ORDER_SUPPLIER_DISPATCH_TO_SET_EQUIPMENTS">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
} else {
    print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_SYNERGIESTECH_ENABLED_WORKFLOW_ORDER_SUPPLIER_DISPATCH_TO_SET_EQUIPMENTS">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
}
print '</td></tr>' . "\n";

// SYNERGIESTECH_FORCE_SET_EQUIPMENTS_AFTER_ORDER_SUPPLIER_DISPATCH
$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>' . $langs->trans("SynergiesTechForceSetEquipmentsAfterOrderSupplierDispatch") . '</td>' . "\n";
print '<td align="center">&nbsp;</td>' . "\n";
print '<td align="right">' . "\n";
if (empty($conf->global->SYNERGIESTECH_FORCE_SET_EQUIPMENTS_AFTER_ORDER_SUPPLIER_DISPATCH)) {
    print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_SYNERGIESTECH_FORCE_SET_EQUIPMENTS_AFTER_ORDER_SUPPLIER_DISPATCH">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
} else {
    print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_SYNERGIESTECH_FORCE_SET_EQUIPMENTS_AFTER_ORDER_SUPPLIER_DISPATCH">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
}
print '</td></tr>' . "\n";

// SYNERGIESTECH_PRODUCT_CATEGORY_FOR_CONTRACT_FORMULE
$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>' . $langs->trans("SynergiesTechProductCategoryForContractFormule") . '</td>' . "\n";
print '<td align="center">&nbsp;</td>' . "\n";
print '<td align="right">' . "\n";
print $formother->select_categories('product', $conf->global->SYNERGIESTECH_PRODUCT_CATEGORY_FOR_CONTRACT_FORMULE, 'SYNERGIESTECH_PRODUCT_CATEGORY_FOR_CONTRACT_FORMULE', 0 ,1);
print '</td></tr>' . "\n";


// SYNERGIESTECH_PRODUCT_CATEGORY_FOR_ADVANCEDTICKECT_EMPLACEMENT
/*$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>' . $langs->trans("SynergiesTechProductCategoryForAdvancedTicketEmplacement") . '</td>' . "\n";
print '<td align="center">&nbsp;</td>' . "\n";
print '<td align="right">' . "\n";
print $formother->select_categories('product', $conf->global->SYNERGIESTECH_PRODUCT_CATEGORY_FOR_ADVANCEDTICKECT_EMPLACEMENT, 'SYNERGIESTECH_PRODUCT_CATEGORY_FOR_ADVANCEDTICKECT_EMPLACEMENT', 0 ,1);
print '</td></tr>' . "\n";*/

// SYNERGIESTECH_PRINCIPAL_WAREHOUSE
$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>' . $langs->trans("SynergiesTechPrincipalWarehouse") . '</td>' . "\n";
print '<td align="center">&nbsp;</td>' . "\n";
print '<td align="right">' . "\n";
print $formproduct->selectWarehouses($conf->global->SYNERGIESTECH_PRINCIPAL_WAREHOUSE, 'SYNERGIESTECH_PRINCIPAL_WAREHOUSE', 'warehouseopen,warehouseinternal', 1, 0, 0, '', 0, 0, null, 'minwidth300');
print '</td></tr>' . "\n";

// SYNERGIESTECH_PRINCIPAL_WAREHOUSE_NB_SHOWED
$var=!$var;
print '<tr '.$bc[$var].'>'."\n";
print '<td>'.$langs->trans("SynergiesTechPrincipalWarehouseNbShowed").'</td>'."\n";
print '<td align="center">&nbsp;</td>' . "\n";
print '<td align="right">'."\n";
print '<input type="number" name="SYNERGIESTECH_PRINCIPAL_WAREHOUSE_NB_SHOWED" value="'.$conf->global->SYNERGIESTECH_PRINCIPAL_WAREHOUSE_NB_SHOWED.'">';
print '</td></tr>'."\n";

// SYNERGIESTECH_CREATE_REQUEST_EVENT
$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>' . $langs->trans("SynergiesTechCreateRequestEvent") . '</td>' . "\n";
print '<td align="center">&nbsp;</td>' . "\n";
print '<td align="right">' . "\n";
print $formactions->select_type_actions($conf->global->SYNERGIESTECH_CREATE_REQUEST_EVENT, "SYNERGIESTECH_CREATE_REQUEST_EVENT", '', (empty($conf->global->AGENDA_USE_EVENT_TYPE) ? 1 : -1), 0, 1, 1);
print '</td></tr>' . "\n";

// SYNERGIESTECH_DEFAULT_REQUEST_TYPE_WHEN_CREATE
$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>' . $langs->trans("SynergiesTechDefaultRequestTypeWhenCreate") . '</td>' . "\n";
print '<td align="center">&nbsp;</td>' . "\n";
print '<td align="right">' . "\n";
print $formdictionary->select_dictionary('requestmanager', 'requestmanagerrequesttype', $conf->global->SYNERGIESTECH_DEFAULT_REQUEST_TYPE_WHEN_CREATE, 'SYNERGIESTECH_DEFAULT_REQUEST_TYPE_WHEN_CREATE', 1, 'rowid', '{{label}}', array(), array('label'=>'ASC'), 0, array(), 0, 0, 'minwidth300');
print '</td></tr>' . "\n";

// SYNERGIESTECH_AUTO_ADD_CONTRACT_IF_MISSING
$request_types_selected = !empty($conf->global->SYNERGIESTECH_AUTO_ADD_CONTRACT_IF_MISSING) ? explode(',', $conf->global->SYNERGIESTECH_AUTO_ADD_CONTRACT_IF_MISSING) : array();

$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>' . $langs->trans("SynergiesTechAddContractOnRequestCreation") . '</td>' . "\n";
print '<td align="center">&nbsp;</td>' . "\n";
print '<td align="right">'."\n";
print $form->multiselectarray("SYNERGIESTECH_AUTO_ADD_CONTRACT_IF_MISSING", $request_types_array, $request_types_selected, 0, 0, 'minwidth300');
print '</td></tr>'."\n";

// SYNERGIESTECH_DO_NOT_KEEP_LINKED_OBJECT_WHEN_CLONING_SUPPLIER_ORDER
$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>' . $langs->trans("SynergiesTechDoNotKeepLinkedObjectWhenCloningSupplierOrder") . '</td>' . "\n";
print '<td align="center">&nbsp;</td>' . "\n";
print '<td align="right">' . "\n";
if (empty($conf->global->SYNERGIESTECH_DO_NOT_KEEP_LINKED_OBJECT_WHEN_CLONING_SUPPLIER_ORDER)) {
    print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_SYNERGIESTECH_DO_NOT_KEEP_LINKED_OBJECT_WHEN_CLONING_SUPPLIER_ORDER">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
} else {
    print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_SYNERGIESTECH_DO_NOT_KEEP_LINKED_OBJECT_WHEN_CLONING_SUPPLIER_ORDER">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
}
print '</td></tr>' . "\n";

// SYNERGIESTECH_ORDER_SUPPLIER_SUBMIT_CUSTOM_EVENT
$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>' . $langs->trans("SynergiesTechCustomSupplierOrderSubmitEvent") . '</td>' . "\n";
print '<td align="center">&nbsp;</td>' . "\n";
print '<td align="right">' . "\n";
if (empty($conf->global->SYNERGIESTECH_ORDER_SUPPLIER_SUBMIT_CUSTOM_EVENT)) {
    print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_SYNERGIESTECH_ORDER_SUPPLIER_SUBMIT_CUSTOM_EVENT">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
} else {
    print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_SYNERGIESTECH_ORDER_SUPPLIER_SUBMIT_CUSTOM_EVENT">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
<<<<<<< HEAD
=======
}

// SYNERGIESTECH_DISABLEDCLASSIFIEDBILLED_SUPPLIERORDER
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>' . $langs->trans("SynergiesTechDisabledClassifyBilledOnSupplierOrder") . '</td>' . "\n";
print '<td align="center">&nbsp;</td>' . "\n";
print '<td align="right">' . "\n";
if (empty($conf->global->SYNERGIESTECH_DISABLEDCLASSIFIEDBILLED_SUPPLIERORDER)) {
    print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_SYNERGIESTECH_DISABLEDCLASSIFIEDBILLED_SUPPLIERORDER">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
} else {
    print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_SYNERGIESTECH_DISABLEDCLASSIFIEDBILLED_SUPPLIERORDER">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
>>>>>>> fix merge issue
}
print '</td></tr>' . "\n";

// SYNERGIESTECH_FICHINTER_CUSTOMSELECTCONTRACT
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>' . $langs->trans("SynergiesTechCustomSelectContractOnInterventionCard") . '</td>' . "\n";
print '<td align="center">&nbsp;</td>' . "\n";
print '<td align="right">' . "\n";
if (empty($conf->global->SYNERGIESTECH_FICHINTER_CUSTOMSELECTCONTRACT)) {
    print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_SYNERGIESTECH_FICHINTER_CUSTOMSELECTCONTRACT">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
} else {
    print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_SYNERGIESTECH_FICHINTER_CUSTOMSELECTCONTRACT">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
}
print '</td></tr>' . "\n";


print '<tr class="liste_titre">';
print '<td>'.$langs->trans("SynergiesTechADSynchro").'</td>'."\n";
print '<td align="center">&nbsp;</td>'."\n";
print '<td align="right">'.$langs->trans("Value").'</td>'."\n";
print "</tr>\n";

// SYNERGIESTECH_USERACCESSCONTROL_LDAPFIELDNAME
$var=!$var;
print '<tr '.$bc[$var].'>'."\n";
print '<td>'.$langs->trans("SynergiesTechUserAccessControlAccountFieldName").'</td>'."\n";
print '<td align="center">&nbsp;</td>' . "\n";
print '<td align="right">'."\n";
print '<input name="SYNERGIESTECH_USERACCESSCONTROL_LDAPFIELDNAME" value="'.$conf->global->SYNERGIESTECH_USERACCESSCONTROL_LDAPFIELDNAME.'">';
print '</td></tr>'."\n";


// SYNERGIESTECH_USERACCESSCONTROL_ACCOUNTENABLE
$var=!$var;
print '<tr '.$bc[$var].'>'."\n";
print '<td>'.$langs->trans("SynergiesTechUserAccessControlAccountEnableValue").'</td>'."\n";
print '<td align="center">&nbsp;</td>' . "\n";
print '<td align="right">'."\n";
print '<input name="SYNERGIESTECH_USERACCESSCONTROL_ACCOUNTENABLE" value="'.$conf->global->SYNERGIESTECH_USERACCESSCONTROL_ACCOUNTENABLE.'">';
print '</td></tr>'."\n";


// SYNERGIESTECH_USERACCESSCONTROL_ACCOUNTDISABLE
$var=!$var;
print '<tr '.$bc[$var].'>'."\n";
print '<td>'.$langs->trans("SynergiesTechUserAccessControlAccountDisableValue").'</td>'."\n";
print '<td align="center">&nbsp;</td>' . "\n";
print '<td align="right">'."\n";
print '<input name="SYNERGIESTECH_USERACCESSCONTROL_ACCOUNTDISABLE" value="'.$conf->global->SYNERGIESTECH_USERACCESSCONTROL_ACCOUNTDISABLE.'">';
print '</td></tr>'."\n";

// SYNERGIESTECH_USERMEMBEROF_LDAPFIELD
$var=!$var;
print '<tr '.$bc[$var].'>'."\n";
print '<td>'.$langs->trans("SynergiesTechUserMemberOfLdapField").'</td>'."\n";
print '<td align="center">&nbsp;</td>' . "\n";
print '<td align="right">'."\n";
print '<input name="SYNERGIESTECH_USERMEMBEROF_LDAPFIELD" value="'.$conf->global->SYNERGIESTECH_USERMEMBEROF_LDAPFIELD.'">';
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
