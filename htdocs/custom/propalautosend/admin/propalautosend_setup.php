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
 * 	\file		admin/propalautosend.php
 * 	\ingroup	propalautosend
 * 	\brief		This file is an example module setup page
 * 				Put some comments here
 */
// Dolibarr environment
$res = @include("../../main.inc.php"); // From htdocs directory
if (! $res) {
    $res = @include("../../../main.inc.php"); // From "custom" directory
}

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once '../lib/propalautosend.lib.php';
dol_include_once('/core/class/doleditor.class.php');

// Translations
$langs->load("propalautosend@propalautosend");

// Access control
if (! $user->admin) {
    accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');

/*
 * Actions
 */
if (preg_match('/set_(.*)/',$action,$reg))
{
	$code=$reg[1];
	if (dolibarr_set_const($db, $code, GETPOST($code), 'chaine', 0, '', $conf->entity) > 0)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

if (preg_match('/del_(.*)/',$action,$reg))
{
	$code=$reg[1];
	if (dolibarr_del_const($db, $code, 0) > 0)
	{
		Header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

/*
 * View
 */
$page_name = "propalAutoSendSetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
    . $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($langs->trans($page_name), $linkback, 'title_setup.png');

// Configuration header
$head = propalautosendAdminPrepareHead();
dol_fiche_head(
    $head,
    'settings',
    $langs->trans("Module104860Name"),
    0,
    "propalautosend@propalautosend"
);

echo "
	<style type='text/css'>
		div.detail { display:none; }
		span.showdetail { cursor:pointer; }
	</style>

	<script type='text/javascript'>
		$(function() {
			$('span.showdetail').click(function() {
				$(this).parent().children('div.detail').slideToggle();
			});
		})
	</script>
";

print $langs->transnoentitiesnoconv('propalAutoSendScriptPath', dol_buildpath('/propalautosend/script/propalAutoSend.php'));

// Setup page goes here
$form=new Form($db);
$var=false;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td>'."\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">'.$langs->trans("Value").'</td>'."\n";

// Minimal amount to do reminder
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("propalAutoAmountReminder").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="800">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_PROPALAUTOSEND_MINIMAL_AMOUNT">';
print '<input type="text" name="PROPALAUTOSEND_MINIMAL_AMOUNT" value="'.$conf->global->PROPALAUTOSEND_MINIMAL_AMOUNT.'" size="54" />&nbsp;';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

// Calcul `relance_date` after propale validation
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("propalAutoSendCalculDateOnValidation").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="800">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_PROPALAUTOSEND_CALCUL_DATE_ON_VALIDATION">';
print $form->selectyesno("PROPALAUTOSEND_CALCUL_DATE_ON_VALIDATION",$conf->global->PROPALAUTOSEND_CALCUL_DATE_ON_VALIDATION, 1);
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

// Calcul `relance_date` after propale sent by mail
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("propalAutoSendCalculDateOnPropaleSentByMail").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="800">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_PROPALAUTOSEND_CALCUL_DATE_ON_EMAIL">';
print $form->selectyesno("PROPALAUTOSEND_CALCUL_DATE_ON_EMAIL",$conf->global->PROPALAUTOSEND_CALCUL_DATE_ON_EMAIL, 0);
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

// Example with a yes / no select
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("propalAutoSendUseAttachFile").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="800">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_PROPALAUTOSEND_JOIN_PDF">';
print $form->selectyesno("PROPALAUTOSEND_JOIN_PDF",$conf->global->PROPALAUTOSEND_JOIN_PDF,1);
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("propalAutoSendSubject").'<div class="detail">'.$langs->transnoentitiesnoconv('propalAutoSendToolTipPropalValues').'</div></td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="800">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_PROPALAUTOSEND_MSG_SUBJECT">';
print '<input type="text" name="PROPALAUTOSEND_MSG_SUBJECT" value="'.$conf->global->PROPALAUTOSEND_MSG_SUBJECT.'" size="54" />&nbsp;';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

if ($conf->fckeditor->enabled && !empty($conf->global->FCKEDITOR_ENABLE_MAIL)) $withfckeditor = 1;
else $withfckeditor = 0;

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("propalAutoSendMsgThirdParty").'<div class="detail">'.$langs->transnoentitiesnoconv('propalAutoSendToolTipPropalValues').$langs->transnoentitiesnoconv('propalAutoSendToolTipMsgThirdParty').'</div></td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="800">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_PROPALAUTOSEND_MSG_THIRDPARTY">';
$doleditor=new DolEditor('PROPALAUTOSEND_MSG_THIRDPARTY', $conf->global->PROPALAUTOSEND_MSG_THIRDPARTY, '', 153, 'dolibarr_notes', 'In', true, true, $withfckeditor, 10, 52);
$doleditor->Create();
print '&nbsp;<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("propalAutoSendMsgContact").'<div class="detail">'.$langs->transnoentitiesnoconv('propalAutoSendToolTipPropalValues').$langs->transnoentitiesnoconv('propalAutoSendToolTipMsgContact').'</div></td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="800">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_PROPALAUTOSEND_MSG_CONTACT">';
$doleditor=new DolEditor('PROPALAUTOSEND_MSG_CONTACT', $conf->global->PROPALAUTOSEND_MSG_CONTACT, '', 153, 'dolibarr_notes', 'In', true, true, $withfckeditor, 10, 52);
$doleditor->Create();
print '&nbsp;<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("propalAutoSendDefaultNbDay").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="800">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_PROPALAUTOSEND_DEFAULT_NB_DAY">';
print '+&nbsp;<input type="text" name="PROPALAUTOSEND_DEFAULT_NB_DAY" value="'.$conf->global->PROPALAUTOSEND_DEFAULT_NB_DAY.'" size="5" />&nbsp;'.$langs->trans('Days').'&nbsp;';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

print '</table>';

llxFooter();

$db->close();