<?php
/* Ouvrage
 * Copyright (C) 2017       Inovea-conseil.com     <info@inovea-conseil.com>
 */

/**
 * \file    admin/setup.php
 * \ingroup ouvrage
 * \brief   ouvrage module setup page.
 *
 * Set SMS API keys
 */

// Load Dolibarr environment
if (false === (@include '../../main.inc.php')) {  // From htdocs directory
	require '../../../main.inc.php'; // From "custom" directory
}

global $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once '../lib/ouvrage.lib.php';
// Translations
$langs->load("ouvrage@ouvrage");
$langs->load("admin");

// Type forfait ou ouvrage
$types = array(
    'BTP_' => $langs->trans('BTP_CHOICE'),
    'SERVICE_' => $langs->trans('SERVICE_CHOICE'),
);

// Access control
if (! $user->admin) {
	accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');

if ($action == 'setvalue' && $user->admin)
{
    $db->begin();
    $result=dolibarr_set_const($db, "OUVRAGE_TYPE",GETPOST('OUVRAGE_TYPE','alpha'),'chaine',0,'',$conf->entity);
    if (! $result > 0) $error++;
    $result=dolibarr_set_const($db, "OUVRAGE_HIDE_PRODUCT_DETAIL",GETPOST('OUVRAGE_HIDE_PRODUCT_DETAIL','alpha'),'chaine',0,'',$conf->entity);
    if (! $result > 0) $error++;
    $result=dolibarr_set_const($db, "OUVRAGE_HIDE_PRODUCT_DESCRIPTION",GETPOST('OUVRAGE_HIDE_PRODUCT_DESCRIPTION','alpha'),'chaine',0,'',$conf->entity);
    if (! $result > 0) $error++;
    $result=dolibarr_set_const($db, "OUVRAGE_HIDE_MONTANT",GETPOST('OUVRAGE_HIDE_MONTANT','alpha'),'chaine',0,'',$conf->entity);
    if (! $result > 0) $error++;

    if (! $error) {
        $db->commit();
        require_once '../core/modules/modouvrage.class.php';
        $ouvrage = new modOuvrage($db);
        $ouvrage->_remove(NULL);
        $ouvrage->init();
    } else {
        $db->rollback();
        dol_print_error($db);
    }
}

/*
 * View
 */
$page_name = "ouvrageSetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
	. $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans($page_name), $linkback);

print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="setvalue">';

// Configuration header
$head = ouvragePrepareHead();
dol_fiche_head(
	$head,
	'settings',
	$langs->trans("Module432406Name"),
	0,
	"ouvrage@ouvrage"
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
print $langs->trans("Type").'</td><td>';
print '<select name="OUVRAGE_TYPE">';


?>
<?php foreach ($types as $k=>$type) : ?>
<option value="<?php echo $k ?>" <?php if ($conf->global->OUVRAGE_TYPE == $k) : ?>selected="selected"<?php endif ?>><?php echo $type ?></option>
<?php endforeach ?>
<?php
print '</select>';
print '</td></tr>';

$var=!$var;
print '<tr class="liste_titre">';
print '<td colspan="2">'.$langs->trans("PDFOptions").'</td>';
print "</tr>\n";

$var=!$var;
print '<tr '.$bc[$var].'><td>';
print $langs->trans("OUVRAGE_HIDE_PRODUCT_DETAIL").'</td><td>';
?>
<input type="hidden" name="OUVRAGE_HIDE_PRODUCT_DETAIL" value="0">
<input type="checkbox" name="OUVRAGE_HIDE_PRODUCT_DETAIL" value="1" <?php if ($conf->global->OUVRAGE_HIDE_PRODUCT_DETAIL == 1) : ?>checked="checked"<?php endif ?> >

<?php
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'><td>';
print $langs->trans("OUVRAGE_HIDE_PRODUCT_DESCRIPTION").'</td><td>';
?>
<input type="hidden" name="OUVRAGE_HIDE_PRODUCT_DESCRIPTION" value="0">
<input type="checkbox" name="OUVRAGE_HIDE_PRODUCT_DESCRIPTION" value="1" <?php if ($conf->global->OUVRAGE_HIDE_PRODUCT_DESCRIPTION == 1) : ?>checked="checked"<?php endif ?> >

<?php
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'><td>';
print $langs->trans("OUVRAGE_HIDE_MONTANT").'</td><td>';
?>
<input type="hidden" name="OUVRAGE_HIDE_MONTANT" value="0">
<input type="checkbox" name="OUVRAGE_HIDE_MONTANT" value="1" <?php if ($conf->global->OUVRAGE_HIDE_MONTANT == 1) : ?>checked="checked"<?php endif ?> >

<?php
print '</td></tr>';

print '</table>';

// Page end
dol_fiche_end();

print '<div class="center"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></div>';

print '</form>';
llxFooter();