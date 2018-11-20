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
 *	\file		/mailchimp/index.php
 *	\ingroup	mailchimp
 */
/*
error_reporting(E_ALL);
ini_set('display_errors', true);
ini_set('html_errors', false);
 */
$res = 0;
if (! $res && file_exists("../main.inc.php")) {
	$res = @include("../main.inc.php");
}
if (! $res && file_exists("../../main.inc.php")) {
	$res = @include("../../main.inc.php");
}
if (! $res && file_exists("../../../main.inc.php")) {
	$res = @include("../../../main.inc.php");
}
if (! $res) {
	die("Main include failed");
}

require_once 'class/dolmailchimp.class.php';

$action=GETPOST('action','alpha');
$confirm = GETPOST('confirm');

if ($action=='updateallcampagin_confirm' && $confirm='yes' && $user->rights->mailing->creer) {
	$mailchimp= new DolMailchimp($db);
	$result=$mailchimp->updateMailChimpAllCampaignStatus($user);
	if ($result<0) {
		setEventMessage($mailchimp->error,'errors');
	}
}


// Load translation files required by the page
$langs->load("mailchimp@mailchimp");

llxHeader('',$langs->trans("Module103020Name"));

if ($action=='updateallcampagin') {
	$form = new Form($db);
	$text=$langs->trans("MailChimpConfirmUpdateAllCampaignText",dol_buildpath('/mailchimp/scripts/update_all_campagin_target.php').' '.$user->login.' '.$langs->defaultlang);
	$ret=$form->form_confirm($_SERVER['PHP_SELF'].'?'.$urlconfirm,$langs->trans("MailChimpConfirmUpdateAllCampaign"),$text,"updateallcampagin_confirm",'','',1,250);
	if ($ret == 'html') print '<br>';
}


$mailchimp= new DolMailchimp($db);
$result=$mailchimp->getListCampaign();

if ($result<0) {
	setEventMessage($mailchimp->error,'errors');
}
$langs->load('products');

dol_htmloutput_mesg($langs->trans('MailChimpExplainIndex',$langs->transnoentities('Reference'),$langs->transnoentities('ProductServiceCard')),'','warning',1);

print_fiche_titre($langs->trans('MailChimpCampaign'));

print '<table class="border" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans('MailChimpCampaign').'</td>';
print '<td>'.$langs->trans('DolibarrCampaignLink').'</td>';
print '<td>'.$langs->trans('Status').'</td>';
print '</tr>';

if (is_array($mailchimp->listcampaign_lines) && count($mailchimp->listcampaign_lines)>0) {

	foreach($mailchimp->listcampaign_lines as $line) {
		$var=!$var;
		$mailchimp_dolibarr= new DolMailchimp($db);
		if (!empty($line['id'])) {

			$result=$mailchimp_dolibarr->fetch_by_mailchimpid($line['id']);
			if ($result<0) {
				setEventMessage($mailchimp_dolibarr->error,'errors');
			}
		}

		print "<tr " . $bc[$var] . ">";
		print '<td><a target="_blanck" href="http://admin.mailchimp.com/campaigns/show?id='.$line['id'].'">'.$line['settings']['title'].'</a></td>';
		print '<td>';
		if (!empty($mailchimp_dolibarr->fk_mailing)) {
			print '<a target="_blanck" href="'.dol_buildpath('/comm/mailing/card.php',1).'?id='.$mailchimp_dolibarr->fk_mailing.'">'.$line['settings']['title'].'</a>';
		}
		print '</td>';
		print '<td>'.DolMailchimp::getLibStatus($line['status']).'</td>';
		print '</tr>';
	}
}else {
	print "<tr " . $bc[$var] . ">";
	print '<td>'.$langs->trans('NoRecords').'</td>';
	print '</tr>';
}
print '<table>';

print "\n\n<div class=\"tabsAction\">\n";
if ($user->rights->mailing->creer && count($mailchimp->listcampaign_lines)>0) {
	print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=updateallcampagin">'.$langs->trans("MailChimpUpdateAllCampaign").'</a>';
}
print '<br><br></div>';

llxFooter();
$db->close();