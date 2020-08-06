<?php
/* Change Tiers
 * Copyright (C) 2018       Inovea-conseil.com     <info@inovea-conseil.com>
 */
/**
 * \file    admin/setup.php
 * \ingroup Change Tiers
 * \brief   Change Tiers module setup page.
 *
 *
 */
// Load Dolibarr environment
$res = 0;
if (!$res && file_exists("../main.inc.php"))
    $res = @include '../main.inc.php';     // to work if your module directory is into dolibarr root htdocs directory
if (!$res && file_exists("../../main.inc.php"))
    $res = @include '../../main.inc.php';   // to work if your module directory is into a subdir of root htdocs directory
if (!$res && file_exists("../../../main.inc.php"))
    $res = @include '../../../main.inc.php';   // to work if your module directory is into a subdir of root htdocs directory
if (!$res && file_exists("../../../dolibarr/htdocs/main.inc.php"))
    $res = @include '../../../dolibarr/htdocs/main.inc.php';     // Used on dev env only
if (!$res && file_exists("../../../../dolibarr/htdocs/main.inc.php"))
    $res = @include '../../../../dolibarr/htdocs/main.inc.php';   // Used on dev env only
if (!$res)
    die("Include of main fails");

global $langs, $user;
// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once '../lib/changetiers.lib.php';
// Translations
$langs->load("changetiers@changetiers");
$langs->load("admin");
// Access control
if (! $user->admin) {
	accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');
if ($action == 'setvalue' && $user->admin)
{
    $db->begin();
    $result=dolibarr_set_const($db, "COMMANDE_CHANGE_THIRDPARTY",GETPOST('COMMANDE_CHANGE_THIRDPARTY','alpha'),'yesno',0,'',$conf->entity);
    if (! $result > 0) $error++;
    $result=dolibarr_set_const($db, "FACTURE_CHANGE_THIRDPARTY",GETPOST('FACTURE_CHANGE_THIRDPARTY','alpha'),'yesno',0,'',$conf->entity);
    if (! $result > 0) $error++;
    $result=dolibarr_set_const($db, "PROPAL_CHANGE_THIRDPARTY",GETPOST('PROPAL_CHANGE_THIRDPARTY','alpha'),'yesno',0,'',$conf->entity);
    if (! $result > 0) $error++;
    $result=dolibarr_set_const($db, "SUPPLIER_PROPOSAL_CHANGE_THIRDPARTY",GETPOST('SUPPLIER_PROPOSAL_CHANGE_THIRDPARTY','alpha'),'yesno',0,'',$conf->entity);
    if (! $result > 0) $error++;
    $result=dolibarr_set_const($db, "ORDER_SUPPLIER_CHANGE_THIRDPARTY",GETPOST('ORDER_SUPPLIER_CHANGE_THIRDPARTY','alpha'),'yesno',0,'',$conf->entity);
    if (! $result > 0) $error++;
    $result=dolibarr_set_const($db, "EXPEDITION_CHANGE_THIRDPARTY",GETPOST('EXPEDITION_CHANGE_THIRDPARTY','alpha'),'yesno',0,'',$conf->entity);
    if (! $result > 0) $error++;
    $result=dolibarr_set_const($db, "INVOICE_SUPPLIER_CHANGE_THIRDPARTY",GETPOST('INVOICE_SUPPLIER_CHANGE_THIRDPARTY','alpha'),'yesno',0,'',$conf->entity);
    if (! $result > 0) $error++;

    if (! $error) {
        $db->commit();
    } else {
        $db->rollback();
        dol_print_error($db);
    }
}
/*
 * View
 */
$page_name = "changetiersSetup";
llxHeader('', $langs->trans($page_name));
// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
	. $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans($page_name), $linkback);
print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="setvalue">';
// Configuration header
$head = changetiersPrepareHead();
dol_fiche_head(
	$head,
	'settings',
	$langs->trans("Module432446Name"),
	0,
	"changetiers@changetiers"
);
print '<br>';
print '<br>';
print '<table class="noborder" width="100%">';
$var=true;
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print "</tr>\n";

$var=!$var;
print '<tr '.$bc[$var].'><td>';
print $langs->trans("COMMANDE_CHANGE_THIRDPARTY").'</td><td>';
print '<input size="64" type="hidden" name="COMMANDE_CHANGE_THIRDPARTY" value="0">';
print '<input size="64" type="checkbox" name="COMMANDE_CHANGE_THIRDPARTY" value="1" ';
print $conf->global->COMMANDE_CHANGE_THIRDPARTY ? 'checked="checked"' : '';
print '>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'><td>';
print $langs->trans("FACTURE_CHANGE_THIRDPARTY").'</td><td>';
print '<input size="64" type="hidden" name="FACTURE_CHANGE_THIRDPARTY" value="0">';
print '<input size="64" type="checkbox" name="FACTURE_CHANGE_THIRDPARTY" value="1" ';
print $conf->global->FACTURE_CHANGE_THIRDPARTY ? 'checked="checked"' : '';
print '>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'><td>';
print $langs->trans("PROPAL_CHANGE_THIRDPARTY").'</td><td>';
print '<input size="64" type="hidden" name="PROPAL_CHANGE_THIRDPARTY" value="0">';
print '<input size="64" type="checkbox" name="PROPAL_CHANGE_THIRDPARTY" value="1" ';
print $conf->global->PROPAL_CHANGE_THIRDPARTY ? 'checked="checked"' : '';
print '>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'><td>';
print $langs->trans("SUPPLIER_PROPOSAL_CHANGE_THIRDPARTY").'</td><td>';
print '<input size="64" type="hidden" name="SUPPLIER_PROPOSAL_CHANGE_THIRDPARTY" value="0">';
print '<input size="64" type="checkbox" name="SUPPLIER_PROPOSAL_CHANGE_THIRDPARTY" value="1" ';
print $conf->global->SUPPLIER_PROPOSAL_CHANGE_THIRDPARTY ? 'checked="checked"' : '';
print '>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'><td>';
print $langs->trans("ORDER_SUPPLIER_CHANGE_THIRDPARTY").'</td><td>';
print '<input size="64" type="hidden" name="ORDER_SUPPLIER_CHANGE_THIRDPARTY" value="0">';
print '<input size="64" type="checkbox" name="ORDER_SUPPLIER_CHANGE_THIRDPARTY" value="1" ';
print $conf->global->ORDER_SUPPLIER_CHANGE_THIRDPARTY ? 'checked="checked"' : '';
print '>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'><td>';
print $langs->trans("EXPEDITION_CHANGE_THIRDPARTY").'</td><td>';
print '<input size="64" type="hidden" name="EXPEDITION_CHANGE_THIRDPARTY" value="0">';
print '<input size="64" type="checkbox" name="EXPEDITION_CHANGE_THIRDPARTY" value="1" ';
print $conf->global->EXPEDITION_CHANGE_THIRDPARTY ? 'checked="checked"' : '';
print '>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'><td>';
print $langs->trans("INVOICE_SUPPLIER_CHANGE_THIRDPARTY").'</td><td>';
print '<input size="64" type="hidden" name="INVOICE_SUPPLIER_CHANGE_THIRDPARTY" value="0">';
print '<input size="64" type="checkbox" name="INVOICE_SUPPLIER_CHANGE_THIRDPARTY" value="1" ';
print $conf->global->INVOICE_SUPPLIER_CHANGE_THIRDPARTY ? 'checked="checked"' : '';
print '>';
print '</td></tr>';

print '</table>';
// Page end
dol_fiche_end();
print '<div class="center"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></div>';
print '</form>';

llxFooter();