<?php
/* <MailChimp connector>
 * Copyright (C) 2013 Florian Henry florian.henry@open-concept.pro
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
 * 	\file		admin/admin_mailchimp.php
* 	\ingroup	mailchimp
 * 	\brief		This file is module setup page
 */

// Dolibarr environment
$res = @include("../../main.inc.php"); // From htdocs directory
if (! $res) {
	$res = @include("../../../main.inc.php"); // From "custom" directory
}


// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once "../lib/mailchimp.lib.php";
dol_include_once('/mailchimp/class/dolmailchimp.class.php');

// Translations
$langs->load("mailchimp@mailchimp");
$langs->load("admin");

// Access control
if (! $user->admin) {
    accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');
$value = GETPOST('value', 'int');

$error=0;

/*
 * Actions
 */

if ($action == 'setvar') {
	$res = dolibarr_set_const($db, 'MAILCHIMP_MAIL_SMTP_SERVER', GETPOST('MAILCHIMP_MAIL_SMTP_SERVER'),'chaine',0,'',$conf->entity);
	if (! $res > 0) $error++;
	$res = dolibarr_set_const($db, 'MAILCHIMP_SMTP_PORT', GETPOST('MAILCHIMP_SMTP_PORT'),'chaine',0,'',$conf->entity);
	if (! $res > 0) $error++;
	$res = dolibarr_set_const($db, 'MAILCHIMP_MAIL_SMTPS_ID', GETPOST('MAILCHIMP_MAIL_SMTPS_ID'),'chaine',0,'',$conf->entity);
	if (! $res > 0) $error++;
	$res = dolibarr_set_const($db, 'MAILCHIMP_MAIL_SMTPS_PW', GETPOST('MAILCHIMP_MAIL_SMTPS_PW'),'chaine',0,'',$conf->entity);
	if (! $res > 0) $error++;
	$res = dolibarr_set_const($db, 'MAILCHIMP_MAIL_EMAIL_TLS', GETPOST('MAILCHIMP_MAIL_EMAIL_TLS'),'chaine',0,'',$conf->entity);
	if (! $res > 0) $error++;
	$res = dolibarr_set_const($db, 'MAILCHIMP_MAIL_EMAIL_FROM', GETPOST('MAILCHIMP_MAIL_EMAIL_FROM'),'chaine',0,'',$conf->entity);
	if (! $res > 0) $error++;
	$res = dolibarr_set_const($db, 'MAILCHIMP_MANDRILL_API_KEY', GETPOST('MAILCHIMP_MANDRILL_API_KEY'),'chaine',0,'',$conf->entity);
	if (! $res > 0) $error++;
	$res = dolibarr_set_const($db, 'MAILCHIMP_NB_MAX_LIST', GETPOST('MAILCHIMP_NB_MAX_LIST'),'chaine',0,'',$conf->entity);
	if (! $res > 0) $error++;

	if ($error) {
		setEventMessage('Error','errors');
	}else {
		setEventMessage($langs->trans('MailChimpSuccessSave'),'mesgs');
	}
}

if ($action=='mailchimpactiv') {



	if ($value==0) {

		$res = dolibarr_set_const($db, 'MAILCHIMP_ACTIVE', $value,'chaine',0,'',$conf->entity);
		if (! $res > 0) $error++;

		$res =dolibarr_set_const($db, "MAIN_MAIL_SENDMODE", $conf->global->MAILCHIMP_MAIL_SENDMODE_STD,'chaine',0,'',0);
		if (! $res > 0) $error++;
		$res =dolibarr_set_const($db, "MAIN_MAIL_SMTP_PORT",   $conf->global->MAILCHIMP_SMTP_PORT_STD,'chaine',0,'',0);
		if (! $res > 0) $error++;
		$res =dolibarr_set_const($db, "MAIN_MAIL_SMTP_SERVER", $conf->global->MAILCHIMP_MAIL_SMTP_SERVER_STD,'chaine',0,'',0);
		if (! $res > 0) $error++;
		$res =dolibarr_set_const($db, "MAIN_MAIL_SMTPS_ID",    $conf->global->MAILCHIMP_MAIL_SMTPS_ID_STD, 'chaine',0,'',0);
		if (! $res > 0) $error++;
		$res =dolibarr_set_const($db, "MAIN_MAIL_SMTPS_PW",   $conf->global->MAILCHIMP_MAIL_SMTPS_PW_STD, 'chaine',0,'',0);
		if (! $res > 0) $error++;
		$res =dolibarr_set_const($db, "MAIN_MAIL_EMAIL_TLS",   $conf->global->MAILCHIMP_MAIL_EMAIL_TLS_STD,'chaine',0,'',0);
		if (! $res > 0) $error++;
		$res =dolibarr_set_const($db, "MAIN_MAIL_EMAIL_FROM",   $conf->global->MAILCHIMP_MAIL_EMAIL_FROM_STD,'chaine',0,'',0);
		if (! $res > 0) $error++;
	}
	if ($value==1) {


		if (empty($conf->global->MAILCHIMP_MAIL_SMTPS_ID)) {
			setEventMessage($langs->trans("MailChimpAPIKeyNotSet"),'errors');
			$error++;
		}
		if (empty($conf->global->MAILCHIMP_MAIL_SMTPS_PW)){
			setEventMessage($langs->trans("MailChimpSecretKeyNotSet"),'errors');
			$error++;
		}
		if (empty($conf->global->MAILCHIMP_ACTIVE_MAILING_ONLY) &&
				empty($conf->global->MAILCHIMP_MANDRILL_API_KEY)) {
			setEventMessage($langs->trans("MandrillSecretKeyNotSet"),'errors');
			$error++;
		}

		if (empty($error)) {

			$res = dolibarr_set_const($db, 'MAILCHIMP_ACTIVE', $value,'chaine',0,'',$conf->entity);
			if (! $res > 0) $error++;

			if (!empty($conf->global->MAILCHIMP_ACTIVE_MAILING_ONLY)) {
				$res = dolibarr_set_const($db, 'MAILCHIMP_ACTIVE_MAILING_ONLY', 0,'chaine',0,'',$conf->entity);
			}

			$res =dolibarr_set_const($db, "MAILCHIMP_MAIL_SENDMODE_STD", $conf->global->MAIN_MAIL_SENDMODE,'chaine',0,'',$conf->entity);
			if (! $res > 0) $error++;
			$res =dolibarr_set_const($db, "MAILCHIMP_SMTP_PORT_STD",   $conf->global->MAIN_MAIL_SMTP_PORT,'chaine',0,'',$conf->entity);
			if (! $res > 0) $error++;
			$res =dolibarr_set_const($db, "MAILCHIMP_MAIL_SMTP_SERVER_STD", $conf->global->MAIN_MAIL_SMTP_SERVER,'chaine',0,'',$conf->entity);
			if (! $res > 0) $error++;
			$res =dolibarr_set_const($db, "MAILCHIMP_MAIL_SMTPS_ID_STD",    $conf->global->MAIN_MAIL_SMTPS_ID, 'chaine',0,'',$conf->entity);
			if (! $res > 0) $error++;
			$res =dolibarr_set_const($db, "MAILCHIMP_MAIL_SMTPS_PW_STD",   $conf->global->MAIN_MAIL_SMTPS_PW, 'chaine',0,'',$conf->entity);
			if (! $res > 0) $error++;
			$res =dolibarr_set_const($db, "MAILCHIMP_MAIL_EMAIL_TLS_STD",   $conf->global->MAIN_MAIL_EMAIL_TLS,'chaine',0,'',$conf->entity);
			if (! $res > 0) $error++;
			$res =dolibarr_set_const($db, "MAILCHIMP_MAIL_EMAIL_FROM_STD",   $conf->global->MAIN_MAIL_EMAIL_FROM,'chaine',0,'',$conf->entity);
			if (! $res > 0) $error++;

			$res =dolibarr_set_const($db, "MAIN_MAIL_SENDMODE", $conf->global->MAILCHIMP_MAIL_SENDMODE,'chaine',0,'',0);
			if (! $res > 0) $error++;
			$res =dolibarr_set_const($db, "MAIN_MAIL_SMTP_PORT",   $conf->global->MAILCHIMP_SMTP_PORT,'chaine',0,'',0);
			if (! $res > 0) $error++;
			$res =dolibarr_set_const($db, "MAIN_MAIL_SMTP_SERVER", $conf->global->MAILCHIMP_MAIL_SMTP_SERVER,'chaine',0,'',0);
			if (! $res > 0) $error++;
			$res =dolibarr_set_const($db, "MAIN_MAIL_SMTPS_ID",    $conf->global->MAILCHIMP_MAIL_SMTPS_ID, 'chaine',0,'',0);
			if (! $res > 0) $error++;
			$res =dolibarr_set_const($db, "MAIN_MAIL_SMTPS_PW",   $conf->global->MAILCHIMP_MANDRILL_API_KEY, 'chaine',0,'',0);
			if (! $res > 0) $error++;
			$res =dolibarr_set_const($db, "MAIN_MAIL_EMAIL_TLS",   $conf->global->MAILCHIMP_MAIL_EMAIL_TLS,'chaine',0,'',0);
			if (! $res > 0) $error++;
			$res =dolibarr_set_const($db, "MAIN_MAIL_EMAIL_FROM",   $conf->global->MAILCHIMP_MAIL_EMAIL_FROM,'chaine',0,'',0);
			if (! $res > 0) $error++;
			$res =dolibarr_set_const($db, "MAILING_EMAIL_FROM",   $conf->global->MAILCHIMP_MAIL_EMAIL_FROM,'chaine',0,'',$conf->entity);
			if (! $res > 0) $error++;

			$res =dolibarr_set_const($db, "MAIN_DISABLE_ALL_MAILS", 0,'chaine',0,'',0);
			if (! $res > 0) $error++;
		}
	}

	if ($error) {
		setEventMessage('Error','errors');
	}else {
		setEventMessage($langs->trans('MailChimpSuccessSave'),'mesgs');
	}
}

if ($action=='activmailchimpmailingonly') {

	$res = dolibarr_set_const($db, 'MAILCHIMP_ACTIVE_MAILING_ONLY', $value,'chaine',0,'',$conf->entity);
	if (! $res > 0) $error++;

	if (!empty($conf->global->MAILCHIMP_ACTIVE)) {

		$res = dolibarr_set_const($db, 'MAILCHIMP_ACTIVE', 0,'chaine',0,'',$conf->entity);
		if (! $res > 0) $error++;

		$res =dolibarr_set_const($db, "MAIN_MAIL_SENDMODE", $conf->global->MAILCHIMP_MAIL_SENDMODE_STD,'chaine',0,'',0);
		if (! $res > 0) $error++;
		$res =dolibarr_set_const($db, "MAIN_MAIL_SMTP_PORT",   $conf->global->MAILCHIMP_SMTP_PORT_STD,'chaine',0,'',0);
		if (! $res > 0) $error++;
		$res =dolibarr_set_const($db, "MAIN_MAIL_SMTP_SERVER", $conf->global->MAILCHIMP_MAIL_SMTP_SERVER_STD,'chaine',0,'',0);
		if (! $res > 0) $error++;
		$res =dolibarr_set_const($db, "MAIN_MAIL_SMTPS_ID",    $conf->global->MAILCHIMP_MAIL_SMTPS_ID_STD, 'chaine',0,'',0);
		if (! $res > 0) $error++;
		$res =dolibarr_set_const($db, "MAIN_MAIL_SMTPS_PW",   $conf->global->MAILCHIMP_MAIL_SMTPS_PW_STD, 'chaine',0,'',0);
		if (! $res > 0) $error++;
		$res =dolibarr_set_const($db, "MAIN_MAIL_EMAIL_TLS",   $conf->global->MAILCHIMP_MAIL_EMAIL_TLS_STD,'chaine',0,'',0);
		if (! $res > 0) $error++;
		$res =dolibarr_set_const($db, "MAIN_MAIL_EMAIL_FROM",   $conf->global->MAILCHIMP_MAIL_EMAIL_FROM_STD,'chaine',0,'',0);
		if (! $res > 0) $error++;
	}
}

if ($action=='activmailchimpactivitiesstorage') {
	$res = dolibarr_set_const($db, 'MAILCHIMP_SAVE_ACTIVITY_LOCALY', $value,'chaine',0,'',$conf->entity);
	if (! $res > 0) $error++;
}
if($action=="hidecontactactivites"){
	$res = dolibarr_set_const($db, 'MAILCHIMP_HIDE_CONTACT_ACTIVITES', $value,'chaine',0,'',$conf->entity);
	if (! $res > 0) $error++;
}


/*
 * View
 */
$page_name = "MailChimpSetup";
llxHeader('', $langs->trans($page_name));

$form=new Form($db);

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
	. $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($langs->trans($page_name), $linkback);

// Configuration header
$head = mailchimpAdminPrepareHead();
dol_fiche_head(
	$head,
	'settings',
	$langs->trans("Module103020Name"),
	0,
	"mailchimp@mailchimp"
);

// Setup page goes here

print '<BR>';
echo $langs->trans("MailChimpExplain");
print '<BR>';

if( !in_array('curl', get_loaded_extensions()))
{
	print '<div class="error">'.$langs->trans('MailChimpErrorCurlNotLoaded').'</div>';
	exit();
}

print '<form method="post" action="'.$_SERVER['PHP_SELF'].'" enctype="multipart/form-data" >';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="setvar">';

print '<table class="noborder" width="100%">';

print '<tr class="liste_titre">';
print '<td width="40%">'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Valeur").'</td>';
print '<td></td>';
print "</tr>\n";

//MAILCHIMP_MAIL_SMTP_SERVER
print '<tr class="pair"><td>'.$langs->trans("MAILCHIMP_MAIL_SMTP_SERVER").'</td>';
print '<td align="left">';
print '<input type="text" name="MAILCHIMP_MAIL_SMTP_SERVER" value="'.$conf->global->MAILCHIMP_MAIL_SMTP_SERVER.'" size="30" ></td>';
print '<td align="left">';
print $form->textwithpicto('',$langs->trans("MAILCHIMP_MAIL_SMTP_SERVERHelp"),1,'help');
print '</td>';
print '</tr>';

//MAILCHIMP_SMTP_PORT
print '<tr class="impair"><td>'.$langs->trans("MAILCHIMP_SMTP_PORT").'</td>';
print '<td align="left">';
print '<input type="text" name="MAILCHIMP_SMTP_PORT" value="'.$conf->global->MAILCHIMP_SMTP_PORT.'" size="5" ></td>';
print '<td align="left">';
print $form->textwithpicto('',$langs->trans("MAILCHIMP_SMTP_PORTHelp"),1,'help');
print '</td>';
print '</tr>';

//MAILCHIMP_MAIL_SMTPS_ID
print '<tr class="pair"><td>'.$langs->trans("MAILCHIMP_MAIL_SMTPS_ID").'</td>';
print '<td align="left">';
print '<input type="text" name="MAILCHIMP_MAIL_SMTPS_ID" value="'.$conf->global->MAILCHIMP_MAIL_SMTPS_ID.'" size="20" ></td>';
print '<td align="left">';
print $form->textwithpicto('',$langs->trans("MAILCHIMP_MAIL_SMTPS_IDHelp"),1,'help');
print '</td>';
print '</tr>';

//MAILCHIMP_MAIL_SMTPS_PW
print '<tr class="impair"><td>'.$langs->trans("MAILCHIMP_MAIL_SMTPS_PW").'</td>';
print '<td align="left">';
print '<input type="password" name="MAILCHIMP_MAIL_SMTPS_PW" value="'.$conf->global->MAILCHIMP_MAIL_SMTPS_PW.'" size="20" ></td>';
print '<td align="left">';
print $form->textwithpicto('',$langs->trans("MAILCHIMP_MAIL_SMTPS_PWHelp"),1,'help');
print '</td>';
print '</tr>';

//MAILCHIMP_MANDRILL_API_KEY
print '<tr class="impair"><td>'.$langs->trans("MAILCHIMP_MANDRILL_API_KEY").'</td>';
print '<td align="left">';
print '<input type="password" name="MAILCHIMP_MANDRILL_API_KEY" value="'.$conf->global->MAILCHIMP_MANDRILL_API_KEY.'" size="20" ></td>';
print '<td align="left">';
print $form->textwithpicto('',$langs->trans("MAILCHIMP_MANDRILL_API_KEYHelp"),1,'help');
print '</td>';
print '</tr>';

//MAILCHIMP_MAIL_EMAIL_TLS_STD
print '<tr class="pair"><td>'.$langs->trans("MAILCHIMP_MAIL_EMAIL_TLS").'</td>';
print '<td align="left">';
if (function_exists('openssl_open')) {
	print $form->selectyesno('MAILCHIMP_MAIL_EMAIL_TLS',(! empty($conf->global->MAILCHIMP_MAIL_EMAIL_TLS)?$conf->global->MAILCHIMP_MAIL_EMAIL_TLS:0),1);
}
else print yn(0).' ('.$langs->trans("YourPHPDoesNotHaveSSLSupport").')';
print '<td align="left">';
print $form->textwithpicto('',$langs->trans("MAILCHIMP_MAIL_EMAIL_TLSHelp"),1,'help');
print '</td>';
print '</tr>';

//MAILCHIMP_NB_MAX_LIST
print '<tr class="pair"><td>'.$langs->trans("MAILCHIMP_NB_MAX_LIST").'</td>';
print '<td align="left">';
print '<input type="text" name="MAILCHIMP_NB_MAX_LIST" value="'.$conf->global->MAILCHIMP_NB_MAX_LIST.'" size="30" ></td>';
print '<td align="left">';
print $form->textwithpicto('',$langs->trans("MAILCHIMP_NB_MAX_LISTHelp"),1,'help');
print '</td>';
print '</tr>';

//MAILCHIMP_MAIL_EMAIL_FROM
print '<tr class="pair"><td>'.$langs->trans("MAILCHIMP_MAIL_EMAIL_FROM").'</td>';
print '<td align="left">';
print '<input type="text" name="MAILCHIMP_MAIL_EMAIL_FROM" value="'.$conf->global->MAILCHIMP_MAIL_EMAIL_FROM.'" size="20" ></td>';
print '<td align="left">';
if (!empty($conf->global->MAILCHIMP_MAIL_EMAIL_FROM)) {
	if (!isValidEmail($conf->global->MAILCHIMP_MAIL_EMAIL_FROM)) {
		$langs->load("errors");
		print img_warning($langs->trans("ErrorBadEMail",$conf->global->MAILCHIMP_MAIL_EMAIL_FROM));
	} else {
		$mailchimp= new DolMailchimp($db);
		$result=$mailchimp->checkMailSender($conf->global->MAILCHIMP_MAIL_EMAIL_FROM);
		if ($result<0) {
			setEventMessage($mailchimp->error,'errors');
		}else {
			if (!$result) {
				print '<a href="https://admin.mailchimp.com/account/users/" target="_blank">'.img_warning($langs->transnoentities("MailChimpSenderNameNotValid")).'</a>';
			}else {
				print img_picto_common($langs->transnoentities("MailChimpSenderNameValid"), 'redstar');
			}
		}
	}
}
else {
	print $form->textwithpicto('',$langs->trans("MAILCHIMP_MAIL_EMAIL_FROMHelp"),1,'help');
}
print '</td>';
print '</tr>';

print '<tr class="liste_titre"><td colspan="3" align="center"><input type="submit" class="button" value="'.$langs->trans("Save").'"></td></tr>';

print '</table>';
print '</form>';

print '<BR>';

dol_htmloutput_mesg($langs->trans("MailChimpDolibarrSettings",dol_buildpath('/admin/mails.php',1).'?mainmenu=home&leftmenu=setup'),'','warning',1);


print '<form method="post" action="'.$_SERVER['PHP_SELF'].'" enctype="multipart/form-data" >';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="activmailchimp">';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="40%">'.$langs->trans("MailChimpActiveSending").'</td>';
print '<td align="center">';
if (!empty($conf->global->MAILCHIMP_ACTIVE)) {
	print '<a href="'.$_SERVER['PHP_SELF'].'?action=mailchimpactiv&value=0">';
	print img_picto($langs->trans("Enabled"),'switch_on');
	print "</a></td>\n";
}else {
	print '<a href="'.$_SERVER['PHP_SELF'].'?action=mailchimpactiv&value=1">';
	print img_picto($langs->trans("Disabled"),'switch_off');
	print "</a></td>\n";
}
print '</td>';
print '</tr>';
print '</table>';
print '</form>';

if (! empty($conf->global->MAILCHIMP_ACTIVE)) {
	dol_htmloutput_mesg($langs->trans("MailChimpDolibarrCheckSettings",dol_buildpath('/admin/mails.php',1).'?mainmenu=home&leftmenu=setup'),'','ok',1);
}

print $langs->trans('or');
dol_htmloutput_mesg($langs->trans("MailChimpActiveSendingForMailingOnlyHelp"),'','warning',1);


print '<form method="post" action="'.$_SERVER['PHP_SELF'].'" enctype="multipart/form-data" >';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="activmailchimpmailingony">';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="40%">'.$langs->trans("MailChimpActiveSendingForMailingOnly").'</td>';
print '<td align="center">';
if (!empty($conf->global->MAILCHIMP_ACTIVE_MAILING_ONLY)) {
	print '<a href="'.$_SERVER['PHP_SELF'].'?action=activmailchimpmailingonly&value=0">';
	print img_picto($langs->trans("Enabled"),'switch_on');
	print "</a></td>\n";
}else {
	print '<a href="'.$_SERVER['PHP_SELF'].'?action=activmailchimpmailingonly&value=1">';
	print img_picto($langs->trans("Disabled"),'switch_off');
	print "</a></td>\n";
}
print '</td>';
print '</tr>';
print '</table>';
print '</form>';

if (!empty($conf->global->MAILCHIMP_ACTIVE_MAILING_ONLY) || ! empty($conf->global->MAILCHIMP_ACTIVE)) {


	print '<form method="post" action="'.$_SERVER['PHP_SELF'].'" enctype="multipart/form-data" >';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="activmailchimpactivitiesstorage">';
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td width="40%">'.$langs->trans("MailChimpActiveActivityLocalStorage").'</td>';
	print '<td align="center">';
	if (!empty($conf->global->MAILCHIMP_SAVE_ACTIVITY_LOCALY)) {
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=activmailchimpactivitiesstorage&value=0">';
		print img_picto($langs->trans("Enabled"),'switch_on');
		print "</a></td>\n";
	}else {
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=activmailchimpactivitiesstorage&value=1">';
		print img_picto($langs->trans("Disabled"),'switch_off');
		print "</a></td>\n";
	}
	print '</td>';
	print '</tr>';
	print '</table>';
	print '</form>';
}

print '<form method="post" action="'.$_SERVER['PHP_SELF'].'" enctype="multipart/form-data" >';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="hidecontactactivites">';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="40%">'.$langs->trans("MAILCHIMPHIDECONTACTACTIVITES").'</td>';
print '<td align="center">';
if (!empty($conf->global->MAILCHIMP_HIDE_CONTACT_ACTIVITES)) {
	print '<a href="'.$_SERVER['PHP_SELF'].'?action=hidecontactactivites&value=0">';
	print img_picto($langs->trans("Enabled"),'switch_on');
	print "</a></td>\n";
}else {
	print '<a href="'.$_SERVER['PHP_SELF'].'?action=hidecontactactivites&value=1">';
	print img_picto($langs->trans("Disabled"),'switch_off');
	print "</a></td>\n";
}
print '</td>';
print '</tr>';
print '</table>';
print '</form>';

dol_fiche_end();
llxFooter();
$db->close();