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
 *	\file		/mailchimp/mailchimp/destinaries_list.php
 *	\ingroup	mailchimp
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
require_once '../class/dolmailchimp.class.php';
require_once '../class/html.formmailchimp.class.php';

global $bc, $conf, $db, $langs;

// Load translation files required by the page
$langs->load("mailchimp@mailchimp");

// Get parameters
$productid = GETPOST('productid', 'int');
$action = GETPOST('action', 'alpha');
$type = GETPOST('type', 'alpha');

//Set page var
$refemail=false;
$error=0;

$mailchimp= new DolMailchimp($db);
$result=$mailchimp->getListDestinaries();
if ($result<0) {
	setEventMessage($mailchimp->error,'errors');
}


// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
$hookmanager=new HookManager($db);
$hookmanager->initHooks(array('mailchimpdestlist'));


/*
 * ACTIONS
*
* Put here all code to do according to value of "action" parameter
*/

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks

if ($action=='associateconfirm') {
	$listid=GETPOST('selectlist','alpha');
	$emailtoadd=GETPOST('emailtoadd');
	$segmentname=GETPOST('segmentname','alpha');
	$segmentid=GETPOST('segmentlist','alpha');
	$resetseg=GETPOST('resetseg','int');

	if (empty($listid)) {
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("MailChimpUpdateExistingList")),'errors');
		$error++;
	}

	if (empty($segmentid) && empty($segmentname)) {
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("MailChimpNewSegmentName")),'errors');
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("MailChimpUpdateExistingSegments")),'errors');
		$error++;
	}

	if (!empty($segmentid) && !empty($segmentname)) {
		setEventMessage($langs->trans("MailChimpSelectSegmentOrNewOnes"),'errors');
		$error++;
	}

	if (!is_array($emailtoadd)) {
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("EMail")),'errors');
		$error++;
	} else if (count($emailtoadd)==0) {
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("EMail")),'errors');
		$error++;
	}

	if (!$error) {
		$result=$mailchimp->addEmailToList($listid,$emailtoadd);
		if ($result<0) {
			setEventMessage($mailchimp->error,'errors');
		} else {
				if(!empty($segmentname)) {
					$result=$mailchimp->createSegment($listid,$segmentname);
					if ($result<0) {
						setEventMessage($mailchimp->error,'errors');
					}
					$segmentid=$result;
			}

			$result=$mailchimp->updateSegment($listid,$segmentid,$emailtoadd,$resetseg);
			if ($result<0) {
				setEventMessage($mailchimp->error,'errors');
			}
		}
	}else {
		$action='associate';
	}

}


/*
 * VIEW
*
* Put here all code to build page
*/

llxHeader('',$langs->trans("MailChimpDestList"));

$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$product,$action);

$formmailchimp = new FormMailChimp($db);

if (!empty($conf->global->MAILCHIMP_ACTIVE) || !empty($conf->global->MAILCHIMP_ACTIVE_MAILING_ONLY)) {

	//View associate
	if ($action=='associate') {

		print_fiche_titre($langs->trans('MailChimpDestListAction'));

		print '<form name="formsoc" method="post" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data">';
		print '<input type="hidden" value="associateconfirm" name="action">';
		print '<input type="hidden" value="'.$_SESSION['newtoken'].'" name="token">';
		print '<input type="hidden" value="'.$productid.'" name="productid">';
		print '<input type="hidden" value="'.$type.'" name="type">';

		print '<table class="border" width="100%">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans('EMail').'</td>';
		print '<td>'.$langs->trans('ThirdParty').'</td>';
		print '<td>'.$langs->trans('ContactsForCompany').'</td>';
		print '<td>'.$langs->trans('MailChimpSelected').'</td>';
		print '</tr>';

		$result=$mailchimp->getEmailListFromReferent($type,$productid);
		if ($result<0) {
			setEventMessage($mailchimp->error,'errors');
		}

		if (is_array($mailchimp->email_lines) && count($mailchimp->email_lines)>0) {

			$refemail=true;

			foreach ($mailchimp->email_lines as $line) {

				$var=!$var;

				print "<tr " . $bc[$var] . ">";
				print '<td>'.$line->email.'</td>';
				if ($line->type=='thirdparty') {
					print '<td><a href="'.DOL_URL_ROOT.'/societe/soc.php?socid='.$line->id.'">'.$line->thirdparty.'</a></td>';
				}else {
					print '<td>'.$line->thirdparty.'</td>';
				}
				if ($line->type=='contact') {
					print '<td><a href="'.DOL_URL_ROOT.'/contact/card.php?id='.$line->id.'">'.$line->contactfullname.'</a></td>';
				}else {
					print '<td>'.$line->contactfullname.'</td>';
				}
				print '<td><input type="checkbox"  checked="checked" name="emailtoadd[]" value="'.$line->email.'&'.$line->type.'&'.$line->id.'"></td>';
				print '</tr>';
			}
		}else {
			print "<tr " . $bc[$var] . ">";
			print '<td colspan="4">'.$langs->trans('MailChimpNoEmailFound').'</td>';
			print '</tr>';
		}

		print '</table>';


		if ($refemail) {

			print '<center><br>';
			print $langs->trans('MailChimpUpdateExistingList');
			$events=array();
			if ($conf->use_javascript_ajax) {
				$events[]=array('method' => 'getSegment', 'url' => dol_buildpath('/mailchimp/mailchimp/ajax/mailchimp.php',1), 'htmlname' => 'segmentlist','params' => array('blocksegement' => 'style'));
			}
			print $formmailchimp->select_mailchimplist('selectlist',1,'',0,$events);
			print '<br>'.$langs->trans('MailChimpOr');

			print '&nbsp;<a href="https://admin.mailchimp.com/lists/#" target="_blanck" >'.$langs->trans('MailChimpNewListName').'</a>';

			print '<div id="blocksegement" style="display:none">';
			print $langs->trans('MailChimpUpdateExistingSegments');
			print $formmailchimp->select_mailchimpsegement(0,'segmentlist');
			print '<input type="checkbox" name="resetseg" value="1"/>'.$langs->trans('MailChimpResetSegment');
			print '<br>'.$langs->trans('MailChimpOr');

			print '&nbsp;'.$langs->trans('MailChimpNewSegmentName').': <input type="text" class="flat" size="8" maxsize="50" name="segmentname">';

			print '<br><input type="submit" class="button" value="'.$langs->trans('Save').'"/>';
			print '</div>';
			print '</center>';

		}
	}

	print '<form>';

	print '<BR>';
	print_fiche_titre($langs->trans('MailChimpDestList'));

	print '<table class="border" width="100%">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans('MailChimpListName').'</td>';
	print '<td>'.$langs->trans('MailChimpSubcribers').'</td>';
	print '<td>'.$langs->trans('DateCreation').'</td>';
	print '</tr>';

	$mailchimpsegment= new DolMailchimp($db);

	if (is_array($mailchimp->listdest_lines) && count($mailchimp->listdest_lines)>0) {
		foreach($mailchimp->listdest_lines as $dest_line) {

			$var=!$var;

			print "<tr " . $bc[$var] . ">";
			print '<td><a target="_blanck" href="http://admin.mailchimp.com/lists/members?id='.$dest_line['id'].'">'.$dest_line['name'].'</a></td>';
			print '<td>'.$dest_line['stats']['member_count'].'</td>';
			print '<td>'.$dest_line['date_created'].'</td>';
			print '</tr>';


			$result=$mailchimpsegment->getListSegmentDestinaries($dest_line['id']);
			if ($result<0) {
				setEventMessage($mailchimpsegment->error,'errors');
			}

			if (is_array($mailchimpsegment->listsegment_lines) && count($mailchimpsegment->listsegment_lines)>0) {
				foreach($mailchimpsegment->listsegment_lines as $seg_line) {

					print "<tr " . $bc[$var] . ">";
					print '<td>&nbsp;&nbsp;&nbsp;&nbsp;'.$langs->trans('MailChimpSegment').' : '.$seg_line['name'].'</td>';
					print '<td>'.$seg_line['member_count'].'</td>';
					print '<td>'.$seg_line['created_at'].'</td>';
					print '</tr>';
				}

			}

		}
	}
	else {
		print "<tr " . $bc[$var] . ">";
		print '<td colspan="3">'.$langs->trans('MailChimpListNoEmailFound').'</td>';
		print '</tr>';
	}

	print '</table>';


}

llxFooter();
$db->close();
