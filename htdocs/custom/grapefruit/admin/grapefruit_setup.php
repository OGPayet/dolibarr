<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2015 ATM Consulting <support@atm-consulting.fr>
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
 * \file admin/grapefruit.php
 * \ingroup grapefruit
 * \brief This file is an example module setup page
 * Put some comments here
 */
// Dolibarr environment
$res = @include ("../../main.inc.php"); // From htdocs directory
if (! $res) {
	$res = @include ("../../../main.inc.php"); // From "custom" directory
}

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once '../lib/grapefruit.lib.php';
dol_include_once('/product/class/html.formproduct.class.php');
dol_include_once('/core/class/html.formorder.class.php');
dol_include_once('/grapefruit/class/grapefruit.class.php');
dol_include_once('/projet/class/task.class.php');
dol_include_once('/core/class/html.formcompany.class.php');


// Translations
$langs->load("grapefruit@grapefruit");
$langs->load("contracts");
$langs->load("fournisseur");
$langs->load("orders");
$langs->load("sendings");
$langs->load("bills");
$langs->load("projects");
$langs->load("propal");

// Access control
if (! $user->admin) {
	accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');

$object=new TGrappeFruit();

/*
 * Actions
 */
if (preg_match('/set_(.*)/', $action, $reg)) {
	$code = $reg[1];
	if (dolibarr_set_const($db, $code, GETPOST($code), 'chaine', 0, '', $conf->entity) > 0) {

		if ($code=='GRAPEFRUIT_MANAGE_DOWNLOAD_OWN_DOC_USERS') {
			$result=$object->setupUserDownloadRights(GETPOST($code));
			if ($result<0) {
				setEventMessage($object->error,'errors');
			}
		}

		setEventMessage("ValuesUpdated");

		header("Location: " . $_SERVER["PHP_SELF"]);
		exit();
	} else {
		dol_print_error($db);
	}
}

if (preg_match('/del_(.*)/', $action, $reg)) {
	$code = $reg[1];
	if (dolibarr_del_const($db, $code, 0) > 0) {
		Header("Location: " . $_SERVER["PHP_SELF"]);
		exit();
	} else {
		dol_print_error($db);
	}
}

/*
 * View
 */
$page_name = "GrapeFruitSetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($langs->trans($page_name), $linkback);

// Configuration header
$head = grapefruitAdminPrepareHead();
dol_fiche_head($head, 'settings', $langs->trans("Module104997Name"), 0, "grapefruit@grapefruit");

// Setup page goes here
$form = new Form($db);
$formcompany   = new FormCompany($db);
$var = false;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Project") . '</td>' . "\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">' . $langs->trans("Value") . '</td>' . "\n";

// Example with a yes / no select
/*$var=!$var;
 print '<tr '.$bc[$var].'>';
 print '<td>'.$langs->trans("ParamLabel").'</td>';
 print '<td align="center" width="20">&nbsp;</td>';
 print '<td align="right" width="300">';
 print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
 print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
 print '<input type="hidden" name="action" value="set_CONSTNAME">';
 print $form->selectyesno("CONSTNAME",$conf->global->CONSTNAME,1);
 print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
 print '</form>';
 print '</td></tr>';
 */
$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td>' . $langs->trans("set_GRAPEFRUIT_BUDGET_NEEDED") . '</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="set_GRAPEFRUIT_BUDGET_NEEDED">';
echo ajax_constantonoff('GRAPEFRUIT_BUDGET_NEEDED');
print '</form>';
print '</td></tr>';
$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td>' . $langs->trans("set_GRAPEFRUIT_DATEEND_NEEDED") . '</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="set_GRAPEFRUIT_DATEEND_NEEDED">';
echo ajax_constantonoff('GRAPEFRUIT_DATEEND_NEEDED');
print '</form>';
print '</td></tr>';

$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td>' . $langs->trans("set_GRAPEFRUIT_PROJECT_AUTO_ADD_TASKS_ON_CREATE") . '</td>';
print '<td colspan="2"  align="right">';
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="set_GRAPEFRUIT_PROJECT_AUTO_ADD_TASKS_ON_CREATE">';
print '<textarea cols="80" rows="5" name="GRAPEFRUIT_PROJECT_AUTO_ADD_TASKS_ON_CREATE">' . $conf->global->GRAPEFRUIT_PROJECT_AUTO_ADD_TASKS_ON_CREATE . '</textarea>';
print '<input type="submit" class="button" value="' . $langs->trans("Modify") . '">';
print '</form>';
print '</td></tr>';

$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td>' . $langs->trans("GRAPEFRUIT_PROJECT_TYPE_FOR_TASK") . '</td>';
print '<td colspan="2"  align="right">';
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="set_GRAPEFRUIT_PROJECT_TYPE_FOR_TASK">';

$defaultTask = new Task($db);
print $formcompany->selectTypeContact($defaultTask,$conf->global->GRAPEFRUIT_PROJECT_TYPE_FOR_TASK, 'GRAPEFRUIT_PROJECT_TYPE_FOR_TASK','internal','rowid', 1);



print '<input type="submit" class="button" value="' . $langs->trans("Modify") . '">';
print '</form>';
print '</td></tr>';


$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td>' . $langs->trans("set_GRAPEFRUIT_ADD_PROJECT_TO_PDF") . '</td>';
print '<td colspan="2"  align="right">';
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="set_GRAPEFRUIT_ADD_PROJECT_TO_PDF">';
echo ajax_constantonoff('GRAPEFRUIT_ADD_PROJECT_TO_PDF');
print '</form>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("set_GRAPEFRUIT_PROJECT_AUTO_WIN").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_GRAPEFRUIT_PROJECT_AUTO_WIN">';
echo ajax_constantonoff('GRAPEFRUIT_PROJECT_AUTO_WIN');
print '</form>';
print '</td></tr>';

if(!empty($conf->multicompany->enabled)) {
	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("set_GRAPEFRUIT_DISALLOW_SAME_REF_MULTICOMPANY").'</td>';
	print '<td colspan="2"  align="right">';
	print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" name="action" value="set_GRAPEFRUIT_DISALLOW_SAME_REF_MULTICOMPANY">';
	echo ajax_constantonoff('GRAPEFRUIT_DISALLOW_SAME_REF_MULTICOMPANY');
	print '</form>';
	print '</td></tr>';
}

print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Proposal") . '</td>' . "\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">' . $langs->trans("Value") . '</td>' . "\n";

$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td>' . $langs->trans("set_GRAPEFRUIT_PROPAL_DEFAULT_BANK_ACOUNT") . '</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="set_GRAPEFRUIT_PROPAL_DEFAULT_BANK_ACOUNT">';
print $form->select_comptes($conf->global->GRAPEFRUIT_PROPAL_DEFAULT_BANK_ACOUNT, 'GRAPEFRUIT_PROPAL_DEFAULT_BANK_ACOUNT', 0, '', 1);
print '<input type="submit" class="button" value="' . $langs->trans("Modify") . '">';
print '</form>';
print '</td></tr>';

$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td>' . $langs->trans("set_GRAPEFRUIT_LINK_PROPAL_2_PROJECT") . '</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="set_GRAPEFRUIT_LINK_PROPAL_2_PROJECT">';
echo ajax_constantonoff('GRAPEFRUIT_LINK_PROPAL_2_PROJECT');
print '</form>';
print '</td></tr>';

$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td>' . $langs->trans("set_GRAPEFRUIT_INVOICE_CLASSIFY_BILLED_PROPAL") . '</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="set_GRAPEFRUIT_INVOICE_CLASSIFY_BILLED_PROPAL">';
echo ajax_constantonoff('GRAPEFRUIT_INVOICE_CLASSIFY_BILLED_PROPAL');
print '</form>';
print '</td></tr>';

$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td>' . $langs->trans("set_GRAPEFRUIT_ALLOW_CREATE_ORDER_AND_BILL_ON_UNSIGNED_PROPAL") . '</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="set_GRAPEFRUIT_ALLOW_CREATE_ORDER_AND_BILL_ON_UNSIGNED_PROPAL">';
echo ajax_constantonoff('GRAPEFRUIT_ALLOW_CREATE_ORDER_AND_BILL_ON_UNSIGNED_PROPAL');
print '</form>';
print '</td></tr>';

$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td>' . $langs->trans("set_GRAPEFRUIT_PROPAL_ADD_DISCOUNT_COLUMN") . '</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="set_GRAPEFRUIT_PROPAL_ADD_DISCOUNT_COLUMN">';
echo ajax_constantonoff('GRAPEFRUIT_PROPAL_ADD_DISCOUNT_COLUMN');
print '</form>';
print '</td></tr>';


print '<tr class="liste_titre">';
print '<td>' . $langs->trans("SupplierProposal") . '</td>' . "\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">' . $langs->trans("Value") . '</td>' . "\n";

$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td>' . $langs->trans("set_GRAPEFRUIT_SUPPLIER_PROPAL_CREATE_PRICE_ON_ACCEP") . '</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="set_GRAPEFRUIT_SUPPLIER_PROPAL_CREATE_PRICE_ON_ACCEP">';
echo ajax_constantonoff('GRAPEFRUIT_SUPPLIER_PROPAL_CREATE_PRICE_ON_ACCEP');
print '</form>';
print '</td></tr>';

$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td>' . $langs->trans("set_GRAPEFRUIT_SUPPLIER_PROPAL_ADDLINE_ZERO") . '</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="set_GRAPEFRUIT_SUPPLIER_PROPAL_ADDLINE_ZERO">';
echo ajax_constantonoff('GRAPEFRUIT_SUPPLIER_PROPAL_ADDLINE_ZERO');
print '</form>';
print '</td></tr>';

print '<tr class="liste_titre">';
print '<td>' . $langs->trans("SupplierOrder") . '</td>' . "\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">' . $langs->trans("Value") . '</td>' . "\n";


$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td>' . $langs->trans("GRAPEFRUIT_SUPPLIER_ORDER_COPY_LINK_FROM_ORIGIN") . '</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="set_GRAPEFRUIT_SUPPLIER_ORDER_COPY_LINK_FROM_ORIGIN">';
echo ajax_constantonoff('GRAPEFRUIT_SUPPLIER_ORDER_COPY_LINK_FROM_ORIGIN');
print '</form>';
print '</td></tr>';

$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td>' . $langs->trans("set_GRAPEFRUIT_SUPPLIER_FORCE_BT_ORDER_TO_INVOICE") . '</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="set_GRAPEFRUIT_SUPPLIER_FORCE_BT_ORDER_TO_INVOICE">';
echo ajax_constantonoff('GRAPEFRUIT_SUPPLIER_FORCE_BT_ORDER_TO_INVOICE');
print '</form>';
print '</td></tr>';

$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td>' . $langs->trans("set_GRAPEFRUIT_SUPPLIER_CONTACT_SHIP_ADDRESS") . '</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="set_GRAPEFRUIT_SUPPLIER_CONTACT_SHIP_ADDRESS">';
echo ajax_constantonoff('GRAPEFRUIT_SUPPLIER_CONTACT_SHIP_ADDRESS');
print '</form>';
print '</td></tr>';

$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td>' . $langs->trans("set_GRAPEFRUIT_SUPPLIER_CONTACT_SHIP_ADDRESS_SHOW_DETAILS") . '</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="set_GRAPEFRUIT_SUPPLIER_CONTACT_SHIP_ADDRESS_SHOW_DETAILS">';
echo ajax_constantonoff('GRAPEFRUIT_SUPPLIER_CONTACT_SHIP_ADDRESS_SHOW_DETAILS');
print '</form>';
print '</td></tr>';

$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td>' . $langs->trans("set_GRAPEFRUIT_SHOW_SUPPLIER_ORDER_REFS") . '</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="set_GRAPEFRUIT_SHOW_SUPPLIER_ORDER_REFS">';
echo ajax_constantonoff('GRAPEFRUIT_SHOW_SUPPLIER_ORDER_REFS');
print '</form>';
print '</td></tr>';

$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td>' . $langs->trans("set_GRAPEFRUIT_FORCE_VAR_HIDEREF_ON_SUPPLIER_ORDER") . '</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
echo ajax_constantonoff('GRAPEFRUIT_FORCE_VAR_HIDEREF_ON_SUPPLIER_ORDER');
print '</td></tr>';

if (! empty($conf->fournisseur->enabled) && ! empty($conf->commande->enabled) && ! empty($conf->stock->enabled) && ! empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER)) {

	$var = ! $var;
	print '<tr ' . $bc[$var] . '>';
	print '<td>' . $langs->trans("set_GRAPEFRUIT_SUPPLIER_ORDER_CLASSIFY_RECEIPT_ORDER") . '</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="300">';
	print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" name="action" value="set_GRAPEFRUIT_SUPPLIER_ORDER_CLASSIFY_RECEIPT_ORDER">';
	echo ajax_constantonoff('GRAPEFRUIT_SUPPLIER_ORDER_CLASSIFY_RECEIPT_ORDER');
	print '</form>';
	print '</td></tr>';
}

$formorder = new FormOrder($db);
$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td>' . $langs->trans("set_GRAPEFRUIT_AUTO_ORDER_ON_SUPPLIERORDER_VALIDATION_WITH_METHOD") . '</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '" />';
print '<input type="hidden" name="action" value="set_GRAPEFRUIT_AUTO_ORDER_ON_SUPPLIERORDER_VALIDATION_WITH_METHOD" />';
$formorder->selectInputMethod($conf->global->GRAPEFRUIT_AUTO_ORDER_ON_SUPPLIERORDER_VALIDATION_WITH_METHOD, "GRAPEFRUIT_AUTO_ORDER_ON_SUPPLIERORDER_VALIDATION_WITH_METHOD", 1);
print '<input type="submit" class="button" value="' . $langs->trans("Modify") . '">';
print '</form>';
print '</td></tr>';

$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td>' . $langs->trans("set_GRAPEFRUIT_CONFIRM_ON_CREATE_INVOICE_FROM_SUPPLIER_ORDER") . '</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="set_GRAPEFRUIT_CONFIRM_ON_CREATE_INVOICE_FROM_SUPPLIER_ORDER">';
echo ajax_constantonoff('GRAPEFRUIT_CONFIRM_ON_CREATE_INVOICE_FROM_SUPPLIER_ORDER');
print '</form>';
print '</td></tr>';

print '<tr class="liste_titre">';
print '<td>' . $langs->trans("SupplierInvoice") . '</td>' . "\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">' . $langs->trans("Value") . '</td>' . "\n";
$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td>' . $langs->trans("set_GRAPEFRUIT_VALIDATE_SUPPLIERINVOICE_ON_RECEIPT_SUPPLIERORDER") . '</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
echo ajax_constantonoff('GRAPEFRUIT_VALIDATE_SUPPLIERINVOICE_ON_RECEIPT_SUPPLIERORDER');
print '</td></tr><tr ' . $bc[$var] . '>';
print '<td>' . $langs->trans("set_GRAPEFRUIT_ALLOW_UPDATE_SUPPLIER_INVOICE_DATE") . '</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
echo ajax_constantonoff('GRAPEFRUIT_ALLOW_UPDATE_SUPPLIER_INVOICE_DATE');
print '</td></tr>';

print '<tr class="liste_titre">';
print '<td>' . $langs->trans("CustomerOrder") . '</td>' . "\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">' . $langs->trans("Value") . '</td>' . "\n";
$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td>' . $langs->trans("set_GRAPEFRUIT_ORDER_CONTACT_SHIP_ADDRESS") . '</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="set_GRAPEFRUIT_ORDER_CONTACT_SHIP_ADDRESS">';
echo ajax_constantonoff('GRAPEFRUIT_ORDER_CONTACT_SHIP_ADDRESS');
print '</form>';
print '</td></tr>';

$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td>' . $langs->trans("set_GRAPEFRUIT_ORDER_CREATE_BILL_ON_VALIDATE") . '</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="set_GRAPEFRUIT_ORDER_CREATE_BILL_ON_VALIDATE">';
echo ajax_constantonoff('GRAPEFRUIT_ORDER_CREATE_BILL_ON_VALIDATE');
print '</form>';
print '</td></tr>';

$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td>' . $langs->trans("set_GRAPEFRUIT_ALLOW_CREATE_BILL_EXPRESS") . '</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
echo ajax_constantonoff('GRAPEFRUIT_ALLOW_CREATE_BILL_EXPRESS');
print '</td></tr>';

$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td>' . $langs->trans("set_GRAPEFRUIT_SET_ORDER_BILLED_IF_SAME_MONTANT") . '</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
echo ajax_constantonoff('GRAPEFRUIT_SET_ORDER_BILLED_IF_SAME_MONTANT');
print '</td></tr>';

$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td>' . $langs->trans("set_GRAPEFRUIT_SET_RIGHT_ORDER_STATUS_ON_SHIPPING_DELETE") . '</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
echo ajax_constantonoff('GRAPEFRUIT_SET_RIGHT_ORDER_STATUS_ON_SHIPPING_DELETE');
print '</td></tr>';

$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td>' . $langs->trans("set_GRAPEFRUIT_CONFIRM_ON_CREATE_INVOICE_FROM_ORDER") . '</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="set_GRAPEFRUIT_CONFIRM_ON_CREATE_INVOICE_FROM_ORDER">';
echo ajax_constantonoff('GRAPEFRUIT_CONFIRM_ON_CREATE_INVOICE_FROM_ORDER');
print '</form>';
print '</td></tr>';

$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td>' . $langs->trans("set_GRAPEFRUIT_ORDER_ADD_DISCOUNT_COLUMN") . '</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="set_GRAPEFRUIT_ORDER_ADD_DISCOUNT_COLUMN">';
echo ajax_constantonoff('GRAPEFRUIT_ORDER_ADD_DISCOUNT_COLUMN');
print '</form>';
print '</td></tr>';

$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td>' . $langs->trans("set_GRAPEFRUIT_ORDER_EXPRESS_FROM_PROPAL") . '</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="set_GRAPEFRUIT_ORDER_EXPRESS_FROM_PROPAL">';
echo ajax_constantonoff('GRAPEFRUIT_ORDER_EXPRESS_FROM_PROPAL');
print '</form>';
print '</td></tr>';

print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Sending") . '</td>' . "\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">' . $langs->trans("Value") . '</td>' . "\n";
$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td>' . $langs->trans("set_GRAPEFRUIT_SHIPPING_CREATE_FROM_ORDER_WHERE_BILL_PAID") . '</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="set_GRAPEFRUIT_SHIPPING_CREATE_FROM_ORDER_WHERE_BILL_PAID">';
echo ajax_constantonoff('GRAPEFRUIT_SHIPPING_CREATE_FROM_ORDER_WHERE_BILL_PAID');
print '</form>';
print '</td></tr>';

$formProduct = new FormProduct($db);
$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td>' . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $langs->trans("set_GRAPEFRUIT_SHIPPING_CREATE_FROM_ORDER_WHERE_BILL_PAID_WAREHOUSE") . '</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="set_GRAPEFRUIT_SHIPPING_CREATE_FROM_ORDER_WHERE_BILL_PAID_WAREHOUSE">';
echo $formProduct->selectWarehouses($conf->global->GRAPEFRUIT_SHIPPING_CREATE_FROM_ORDER_WHERE_BILL_PAID_WAREHOUSE, 'GRAPEFRUIT_SHIPPING_CREATE_FROM_ORDER_WHERE_BILL_PAID_WAREHOUSE', '', 1);
print '<input type="submit" class="button" value="' . $langs->trans("Modify") . '">';
print '</form>';
print '</td></tr>';

$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td>' . $langs->trans("set_GRAPEFRUIT_SET_ORDER_SHIPPED_IF_ALL_PRODUCT_SHIPPED") . '</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="set_GRAPEFRUIT_SET_ORDER_SHIPPED_IF_ALL_PRODUCT_SHIPPED">';
echo ajax_constantonoff('GRAPEFRUIT_SET_ORDER_SHIPPED_IF_ALL_PRODUCT_SHIPPED');
print '</form>';
print '</td></tr>';

$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td>' . $langs->trans("set_GRAPEFRUIT_CREATE_DELIVERY_FROM_SHIPPING") . '</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="set_GRAPEFRUIT_CREATE_DELIVERY_FROM_SHIPPING">';
echo ajax_constantonoff('GRAPEFRUIT_CREATE_DELIVERY_FROM_SHIPPING');
print '</form>';
print '</td></tr>';

print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Contract") . '</td>' . "\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">' . $langs->trans("Value") . '</td>' . "\n";
$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td>' . $langs->trans("set_GRAPEFRUIT_CONTRACT_DEFAUL_FOURN") . '</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="set_GRAPEFRUIT_CONTRACT_DEFAUL_FOURN">';
echo $form->select_thirdparty_list($conf->global->GRAPEFRUIT_CONTRACT_DEFAUL_FOURN, 'GRAPEFRUIT_CONTRACT_DEFAUL_FOURN', 'fournisseur=1');
print '<input type="submit" class="button" value="' . $langs->trans("Modify") . '">';
print '</form>';
print '</td></tr>';

if ($conf->facture->enabled) {

	print '<tr class="liste_titre">';
	print '<td>' . $langs->trans("Bill") . '</td>' . "\n";
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="100">' . $langs->trans("Value") . '</td>' . "\n";
	$var = ! $var;
	print '<tr ' . $bc[$var] . '>';
	print '<td>' . $langs->trans("set_GRAPEFRUIT_SEND_BILL_BY_MAIL_ON_VALIDATE") . '</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="300">';
	print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" name="action" value="set_GRAPEFRUIT_SEND_BILL_BY_MAIL_ON_VALIDATE">';
	echo ajax_constantonoff('GRAPEFRUIT_SEND_BILL_BY_MAIL_ON_VALIDATE');
	print '</form>';
	print '</td></tr>';
	$var = ! $var;
	print '<tr ' . $bc[$var] . '>';
	print '<td>' . $langs->trans("set_GRAPEFRUIT_SEND_BILL_BY_MAIL_ON_BILLED") . '</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="300">';
	print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" name="action" value="set_GRAPEFRUIT_SEND_BILL_BY_MAIL_ON_BILLED">';
	echo ajax_constantonoff('GRAPEFRUIT_SEND_BILL_BY_MAIL_ON_BILLED');
	print '</form>';
	print '</td></tr>';
	$var = ! $var;
	print '<tr ' . $bc[$var] . '>';
	print '<td>' . $langs->trans("set_GRAPEFRUIT_SEND_BILL_BY_MAIL_ON_VALIDATE_ORDER") . '</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="300">';
	print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" name="action" value="set_GRAPEFRUIT_SEND_BILL_BY_MAIL_ON_VALIDATE_ORDER">';
	echo ajax_constantonoff('GRAPEFRUIT_SEND_BILL_BY_MAIL_ON_VALIDATE_ORDER');
	print '</form>';
	print '</td></tr>';

	$sql = "SELECT rowid, label, topic, content, lang";
	$sql .= " FROM " . MAIN_DB_PREFIX . 'c_email_templates';
	$sql .= " WHERE type_template='facture_send'";
	$sql .= " AND entity IN (" . getEntity("c_email_templates") . ")";
	$res = $db->query($sql);
	while ( $obj = $db->fetch_object($res) ) {
		$TModel[$obj->rowid] = $obj->label . (! empty($obj->lang) ? '(' . $obj->lang . ')' : '');
	}

	$var = ! $var;
	print '<tr ' . $bc[$var] . '>';
	print '<td>' . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $langs->trans("set_GRAPEFRUIT_SEND_BILL_BY_MAIL_ON_VALIDATE_MODEL") . '</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="300">';
	print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" name="action" value="set_GRAPEFRUIT_SEND_BILL_BY_MAIL_ON_VALIDATE_MODEL">';

	echo $form->selectarray('GRAPEFRUIT_SEND_BILL_BY_MAIL_ON_VALIDATE_MODEL', $TModel, $conf->global->GRAPEFRUIT_SEND_BILL_BY_MAIL_ON_VALIDATE_MODEL);

	print '<input type="submit" class="button" value="' . $langs->trans("Modify") . '">';
	print '</form>';
	print '</td></tr>';

	$var = ! $var;
	print '<tr ' . $bc[$var] . '>';
	print '<td>' . $langs->trans("set_GRAPEFRUIT_BILL_COPY_LINKS_ON_CLONE") . '</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="300">';
	print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" name="action" value="set_GRAPEFRUIT_BILL_COPY_LINKS_ON_CLONE">';
	echo ajax_constantonoff('GRAPEFRUIT_BILL_COPY_LINKS_ON_CLONE');
	print '</form>';
	print '</td></tr>';

	$var = ! $var;
	print '<tr ' . $bc[$var] . '>';
	print '<td>' . $langs->trans("set_GRAPEFRUIT_SITUATION_INVOICE_DEFAULT_PROGRESS") . '</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="300">';
	echo ajax_constantonoff('GRAPEFRUIT_SITUATION_INVOICE_DEFAULT_PROGRESS');
	print '</td></tr>';

	$var = ! $var;
	print '<tr ' . $bc[$var] . '>';
	print '<td>' . $langs->trans("set_GRAPEFRUIT_REMINDER_BILL_DELAY") . '</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="300">';
	print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" name="action" value="set_GRAPEFRUIT_REMINDER_BILL_DELAY">';
	print '<input type="text" name="GRAPEFRUIT_REMINDER_BILL_DELAY" value="' . $conf->global->GRAPEFRUIT_REMINDER_BILL_DELAY . '" style="width:300px;max-width:100%;" />';
	print '<input type="submit" class="button" value="' . $langs->trans("Modify") . '">';
	print '</form>';
	print '</td></tr>';

	$var = ! $var;
	print '<tr ' . $bc[$var] . '>';
	print '<td>' . $langs->trans("set_GRAPEFRUIT_EVENT_DESCRIPTION") . '</td>';
	print '<td colspan="2"  align="right">';
	print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" name="action" value="set_GRAPEFRUIT_EVENT_DESCRIPTION">';
	print '<textarea cols="80" rows="5" name="GRAPEFRUIT_EVENT_DESCRIPTION">' . $conf->global->GRAPEFRUIT_EVENT_DESCRIPTION . '</textarea>';
	print '<input type="submit" class="button" value="' . $langs->trans("Modify") . '">';
	print '</form>';
	print '</td></tr>';

	$var = ! $var;
	print '<tr ' . $bc[$var] . '>';
	print '<td>' . $langs->trans("set_GRAPEFRUIT_BILL_ADD_DISCOUNT_COLUMN") . '</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="300">';
	print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" name="action" value="set_GRAPEFRUIT_BILL_ADD_DISCOUNT_COLUMN">';
	echo ajax_constantonoff('GRAPEFRUIT_BILL_ADD_DISCOUNT_COLUMN');
	print '</form>';
	print '</td></tr>';

	$var = ! $var;
	print '<tr ' . $bc[$var] . '>';
	print '<td>' . $langs->trans("set_GRAPEFRUIT_BILL_AUTO_VALIDATE_IF_ORIGIN") . '</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="300">';
	print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" name="action" value="set_GRAPEFRUIT_BILL_AUTO_VALIDATE_IF_ORIGIN">';
	echo ajax_constantonoff('GRAPEFRUIT_BILL_AUTO_VALIDATE_IF_ORIGIN');
	print '</form>';
	print '</td></tr>';

	$var = ! $var;
	print '<tr ' . $bc[$var] . '>';
	print '<td>' . $langs->trans("set_GRAPEFRUIT_ALLOW_RESTOCK_ON_CREDIT_NOTES") . '</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="300">';
	echo ajax_constantonoff('GRAPEFRUIT_ALLOW_RESTOCK_ON_CREDIT_NOTES');
	print '</td></tr>';

}

if ($conf->agefodd->enabled) {

	print '<tr class="liste_titre">';
	print '<td>' . $langs->trans("Agefodd") . '</td>' . "\n";
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="100">' . $langs->trans("Value") . '</td>' . "\n";

	$var = ! $var;
	print '<tr ' . $bc[$var] . '>';
	print '<td>' . $langs->trans("set_GRAPEFRUIT_LINK_INVOICE_TO_SESSION_IF_PROPAL_IS") . '</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="300">';
	print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" name="action" value="set_GRAPEFRUIT_LINK_INVOICE_TO_SESSION_IF_PROPAL_IS">';
	echo ajax_constantonoff('GRAPEFRUIT_LINK_INVOICE_TO_SESSION_IF_PROPAL_IS');
	print '</form>';
	print '</td></tr>';
}

print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Customer") . '</td>' . "\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">' . $langs->trans("Value") . '</td>' . "\n";

$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td>' . $langs->trans("set_GRAPEFRUIT_DISABLE_PROSPECTCUSTOMER_CHOICE") . '</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
echo ajax_constantonoff('GRAPEFRUIT_DISABLE_PROSPECTCUSTOMER_CHOICE');
print '</td></tr>';

$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td>' . $langs->trans("set_GRAPEFRUIT_DEFAULT_TVA_ON_DOCUMENT_CLIENT_ENABLED") . '</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
echo ajax_constantonoff('GRAPEFRUIT_DEFAULT_TVA_ON_DOCUMENT_CLIENT_ENABLED');
print '</td></tr>';

print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Contact") . '</td>' . "\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">' . $langs->trans("Value") . '</td>' . "\n";

$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td>' . $langs->trans("set_GRAPEFRUIT_CONTACT_FORCE_FIELDS") . '</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="set_GRAPEFRUIT_CONTACT_FORCE_FIELDS">';
print '<input type="text" name="GRAPEFRUIT_CONTACT_FORCE_FIELDS" value="' . $conf->global->GRAPEFRUIT_CONTACT_FORCE_FIELDS . '" style="width:300px;max-width:100%;" />';
print '<input type="submit" class="button" value="' . $langs->trans("Modify") . '">';
print '</form>';
print '</td></tr>';

print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Agenda") . '</td>' . "\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">' . $langs->trans("Value") . '</td>' . "\n";

$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td>' . $langs->trans("set_GRAPEFRUIT_CAN_ASSOCIATE_TASK_TO_ACTIONCOMM") . '</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="set_GRAPEFRUIT_CAN_ASSOCIATE_TASK_TO_ACTIONCOMM">';
print $form->selectyesno('GRAPEFRUIT_CAN_ASSOCIATE_TASK_TO_ACTIONCOMM', $conf->global->GRAPEFRUIT_CAN_ASSOCIATE_TASK_TO_ACTIONCOMM, 1);
print '<input type="submit" class="button" value="' . $langs->trans("Modify") . '">';
print '</form>';
print '</td></tr>';

$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td>' . $langs->trans("set_MAIN_ADD_EVENT_ON_ELEMENT_CARD") . '</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="set_MAIN_ADD_EVENT_ON_ELEMENT_CARD">';
echo ajax_constantonoff('MAIN_ADD_EVENT_ON_ELEMENT_CARD');
print '</form>';
print '</td></tr>';


print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Product") . '</td>' . "\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">' . $langs->trans("Value") . '</td>' . "\n";

$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td>' . $langs->trans("set_GRAPEFRUIT_COPY_CAT_ON_CLONE") . '</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="set_GRAPEFRUIT_COPY_CAT_ON_CLONE">';
echo ajax_constantonoff('GRAPEFRUIT_COPY_CAT_ON_CLONE');
print '</form>';
print '</td></tr>';

/*
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Company") . '</td>' . "\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">' . $langs->trans("Value") . '</td>' . "\n";

$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td>' . $langs->trans("set_GRAPEFRUIT_COPY_CAT_ON_CLONE") . '</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="set_GRAPEFRUIT_COPY_THIRDPARTY_CAT_ON_CLONE">';
echo ajax_constantonoff('GRAPEFRUIT_COPY_THIRDPARTY_CAT_ON_CLONE');
print '</form>';
print '</td></tr>';
*/
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Global") . '</td>' . "\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">' . $langs->trans("Value") . '</td>' . "\n";

$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td>' . $langs->trans("set_GRAPEFRUIT_BYPASS_CONFIRM_ACTIONS") . '</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="set_GRAPEFRUIT_BYPASS_CONFIRM_ACTIONS">';
print '<input type="text" name="GRAPEFRUIT_BYPASS_CONFIRM_ACTIONS" value="' . $conf->global->GRAPEFRUIT_BYPASS_CONFIRM_ACTIONS . '" style="width:300px;max-width:100%;" />';
print '<input type="submit" class="button" value="' . $langs->trans("Modify") . '">';
print '</form>';
print '</td></tr>';

$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td>' . $langs->trans("set_GRAPEFRUIT_FAST_UPDATE_ON_HREF") . '</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="set_GRAPEFRUIT_FAST_UPDATE_ON_HREF">';
echo ajax_constantonoff('GRAPEFRUIT_FAST_UPDATE_ON_HREF');
print '</form>';
print '</td></tr>';

$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td>' . $langs->trans("set_GRAPEFRUIT_SHOW_THIRDPARTY_INTO_LINKED_ELEMENT") . '</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="set_GRAPEFRUIT_SHOW_THIRDPARTY_INTO_LINKED_ELEMENT">';
echo ajax_constantonoff('GRAPEFRUIT_SHOW_THIRDPARTY_INTO_LINKED_ELEMENT');
print '</form>';
print '</td></tr>';



print '<tr class="liste_titre">';
print '<td>' . $langs->trans("User") . '</td>' . "\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">' . $langs->trans("Value") . '</td>' . "\n";

$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td>' . $langs->trans("set_GRAPEFRUIT_MANAGE_DOWNLOAD_OWN_DOC_USERS") . '</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="set_GRAPEFRUIT_MANAGE_DOWNLOAD_OWN_DOC_USERS">';
print $form->selectyesno('GRAPEFRUIT_MANAGE_DOWNLOAD_OWN_DOC_USERS', $conf->global->GRAPEFRUIT_MANAGE_DOWNLOAD_OWN_DOC_USERS, 1);
print '<input type="submit" class="button" value="' . $langs->trans("Modify") . '">';
print '</form>';
print '</td></tr>';

$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td>' . $langs->trans("set_GRAPEFRUIT_FILTER_HOMEPAGE_BY_USER") . '</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="set_GRAPEFRUIT_FILTER_HOMEPAGE_BY_USER">';
print $form->selectyesno('GRAPEFRUIT_FILTER_HOMEPAGE_BY_USER', $conf->global->GRAPEFRUIT_FILTER_HOMEPAGE_BY_USER, 1);
print '<input type="submit" class="button" value="' . $langs->trans("Modify") . '">';
print '</form>';
print '</td></tr>';

print '</table>';

llxFooter();

$db->close();
