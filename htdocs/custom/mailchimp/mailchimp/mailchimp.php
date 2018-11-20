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

/*error_reporting(E_ALL);
 ini_set('display_errors', true);
ini_set('html_errors', false);*/

/**
 *	\file		/mailchimp/mailchimp/mailchimp.php
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
require_once DOL_DOCUMENT_ROOT.'/core/lib/emailing.lib.php';
require_once DOL_DOCUMENT_ROOT.'/comm/mailing/class/mailing.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once '../class/dolmailchimp.class.php';
require_once '../class/html.formmailchimp.class.php';

// Load translation files required by the page
$langs->load("mailchimp@mailchimp");
$langs->load("mails");

// Get parameters
$id = GETPOST('id', 'int');
$action = GETPOST('action', 'alpha');
$confirm = GETPOST('confirm');

$error=0;

// Access control
if (! $user->rights->mailing->creer || (empty($conf->global->EXTERNAL_USERS_ARE_AUTHORIZED) && $user->societe_id > 0)) {
	accessforbidden();
}

$object=new Mailing($db);
$result=$object->fetch($id);
if ($result<0) {
	setEventMessage($object->error,'errors');
}

$mailchimp= new DolMailchimp($db);
$result=$mailchimp->fetch_by_mailing($id);
if ($result<0) {
	setEventMessage($mailchimp->error,'errors');
}

$extrafields = new ExtraFields($db);

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
$hookmanager=new HookManager($db);
$hookmanager->initHooks(array('mailchimpcard'));



$error_mailchimp_control=0;



//$mailchimp->APIListExport();

/*
 * ACTIONS
*
* Put here all code to do according to value of "action" parameter
*/

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks

// Action update description of emailing
if ($action == 'settitre' || $action == 'setemail_from') {

	if ($action == 'settitre')					$object->titre          = trim(GETPOST('titre','alpha'));
	else if ($action == 'setemail_from')		$object->email_from     = trim(GETPOST('email_from','alpha'));

	else if ($action == 'settitre' && empty($object->titre))		$mesg.=($mesg?'<br>':'').$langs->trans("ErrorFieldRequired",$langs->transnoentities("MailTitle"));
	else if ($action == 'setfrom' && empty($object->email_from))	$mesg.=($mesg?'<br>':'').$langs->trans("ErrorFieldRequired",$langs->transnoentities("MailFrom"));

	if (empty($mesg)) {
		if ($object->update($user) >= 0) {
			header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
			exit;
		} else {
			setEventMessage($object->error,'errors');
		}
	} else {
		setEventMessage($mesg,'errors');
	}

	$action="";
}

if ($action=='associateconfirm') {

	$import=GETPOST('import','alpha');
	$export=GETPOST('exportList','alpha');
	$exportSegment=GETPOST('exportSegment','alpha');
	$updateonly=GETPOST('updateonly','alpha');
	$updatesegment=GETPOST('updatesegment','alpha');
	$segmentid=GETPOST('segmentlist','alpha');
	$listid=GETPOST('selectlist','alpha');
	$newsegmentname=GETPOST('segmentname','alpha');
	$resetseg=GETPOST('resetseg','int');

	$mailchimp->mailchimp_listid=$listid;

	if (empty($mailchimp->id)) {
		$mailchimp->fk_mailing=$id;
		$result=$mailchimp->create($user);
		if ($result<0) {
			setEventMessage($mailchimp->error,'errors');
			$error++;
		}
	}

	if (empty($error)) {
		if (!empty($import)) {
			if (!empty($segmentid)) {
				$result=$mailchimp->importSegmentDestToDolibarr($segmentid);
				if ($result<0) {
					setEventMessage($mailchimp->error,'errors');
				}else {
					$mailchimp->mailchimp_segmentid=$segmentid;
					$result=$mailchimp->update($user);
					if ($result<0) {
						setEventMessage($mailchimp->error,'errors');
					}
				}
			}
		}

		if (!empty($export)) {

				$result=$mailchimp->exportListDesttoMailChimp();

				if ($result<0) {
					setEventMessage($mailchimp->error,'errors');
				}else {
					$result=$mailchimp->update($user);
					if ($result<0) {
						setEventMessage($mailchimp->error,'errors');
					}
				}

		}

		if (!empty($exportSegment)) {
			if (empty($segmentid) && empty($newsegmentname)) {
				setEventMessage($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("MailChimpSegment")), 'errors');
			} else {
				$result=$mailchimp->exportSegmentDesttoMailChimp($segmentid,$newsegmentname,$resetseg);

				if ($result<0) {
					setEventMessage($mailchimp->error,'errors');
				}else {
					$result=$mailchimp->update($user);
					if ($result<0) {
						setEventMessage($mailchimp->error,'errors');
					}
				}
			}
		}

		if (!empty($updatesegment)) {
			if (empty($segmentid)) {
				setEventMessage($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("MailChimpSegment")), 'errors');
			} else {
				$result=$mailchimp->exportSegmentOnlyDesttoMailChimp($segmentid,$newsegmentname,$resetseg);
				if ($result<0) {
					setEventMessage($mailchimp->error,'errors');
				}else {
					$result=$mailchimp->update($user);
					if ($result<0) {
						setEventMessage($mailchimp->error,'errors');
					}
				}
			}
		}

		if (!empty($updateonly)) {
			$mailchimp->mailchimp_listid=$listid;
			$mailchimp->mailchimp_segmentid=$segmentid;
			$result=$mailchimp->update($user);
			if ($result<0) {
				setEventMessage($mailchimp->error,'errors');
			}
		}
	}

	$result=$object->fetch($id);
	if ($result<0) {
		setEventMessage($object->error,'errors');
	}
}

if ($action=='setmailchimp_sender_name') {
	$mailchimp->mailchimp_sender_name  = GETPOST('mailchimp_sender_name','alpha');
	if (empty($mailchimp->id)) {
		$mailchimp->fk_mailing=$id;
		$result=$mailchimp->create($user);
	}else {
		$result=$mailchimp->update($user);
	}
	if ($result<0) {
		setEventMessage($mailchimp->error,'errors');
	}else {
		$result=$mailchimp->fetch_by_mailing($id);
		if ($result<0) {
			setEventMessage($mailchimp->error,'errors');
		}
	}
}


if ($action == 'createmailchimpcampaign') {

	$mailchimp->currentmailing=$object;

	$result=$mailchimp->createMailChimpCampaign($user);
	if ($result<0) {
		setEventMessage($mailchimp->error,'errors');
	}
}

if ($action=='sendmailchimpcampaign') {
	//Send campaign
	$result=$mailchimp->sendMailChimpCampaign();
	if ($result<0) {
		setEventMessage($mailchimp->error,'errors');
	} else {
		//Update mailing general status
		$object->statut=3;
		$sql="UPDATE ".MAIN_DB_PREFIX."mailing SET statut=".$object->statut." WHERE rowid=".$object->id;
		dol_syslog("mailchimp/mailchimp/mailchimp.php: update global status sql=".$sql, LOG_DEBUG);
		$resql2=$db->query($sql);
		if (! $resql2)	{
			setEventMessage($db->lasterror(),'errors');
		}
	}
}

if ($action=='updatemailchimpcampaignstatus_confirm' && $confirm='yes') {
	$result=$mailchimp->updateMailChimpCampaignStatus($user);
	if ($result<0) {
		setEventMessage($mailchimp->error,'errors');
	} else {
		//Header("Location: " . $_SERVER ['PHP_SELF'] . "?id=" . $id);
	}
}




//Attached file are not allowed for MailChimp Mailing
$error_file_attach=false;
$upload_dir = $conf->mailing->dir_output . "/" . get_exdir($object->id,2,0,1,$object,'mailing');
$listofpaths=dol_dir_list($upload_dir,'all',0,'','','name',SORT_ASC,0);
if (count($listofpaths))
{
	$error_file_attach =true;
	$error_mailchimp_control++;
}
//Unsubscribe link mandatory
$error_no_unscubscribe_link=false;

//if (preg_match('/\*\|UNSUB\|\*/',$object->body)==0) {
//	$error_no_unscubscribe_link=true;
//	$error_mailchimp_control++;
//}


//Listid must be define
$error_list_define=false;
if (empty($mailchimp->mailchimp_listid)) {
	$error_list_define=true;
	$error_mailchimp_control++;
}


/*
 * Control for MailChimp regarding Dolibarr standard mailing
*/
//Check sender name
$error_sendername=false;
if (empty($mailchimp->mailchimp_sender_name)) {
	$error_sendername=true;
	$error_mailchimp_control++;
}


//Check dolibarr dest versus list segment define
$warning_destnotsync=false;
if (!empty($mailchimp->id)) {

	if ($object->statut==0 || $object->statut==1) {
		$email_seg_array=array();

		//retrive email for segment and Or List
		if (!empty($mailchimp->mailchimp_segmentid)) {
			$result=$mailchimp->getEmailSegment();
			if ($result<0) {
				setEventMessage($mailchimp->error,'errors');
			} else {
				$email_seg_array=$mailchimp->email_lines;
			}
		}
		else {
			if (!empty($mailchimp->mailchimp_listid)) {
				$result=$mailchimp->getEmailList();
				if ($result<0) {
					setEventMessage($mailchimp->error,'errors');
				} else {
					$email_seg_array=$mailchimp->email_lines;
				}
			}
		}


		//Retreive mail from mailling destinaries
		$mailchimp->fk_mailing=$id;
		$result=$mailchimp->getEmailMailingDolibarr();
		if ($result<0) {
			setEventMessage($mailchimp->error,'errors');
		} else {
			$email_dol_array=$mailchimp->email_lines;
		}

		$email_in_dol_not_in_mailchimp=array();
		//First compare count easy and quick
		if (count($email_dol_array)!=count($email_seg_array)) {
			$warning_destnotsync=true;
			foreach ($email_dol_array as $emailadress) {
				if (array_search($emailadress, $email_seg_array)===false) {
					$email_in_dol_not_in_mailchimp[]=$emailadress;
				}
			}

		}else {

			//if count is same compare email by email
			foreach($email_dol_array as $emailadress) {
				if (!in_array($emailadress,$email_seg_array)) {
					$warning_destnotsync=true;
					break;
				}
			}

			foreach($email_seg_array as $emailadress) {
				if (!in_array($emailadress,$email_dol_array)) {
					$warning_destnotsync=true;
					break;
				}
			}
		}
	}
} else {
	$warning_destnotsync=true;
}


/*
 * VIEW
*
* Put here all code to build page
*/

// fetch optionals attributes and labels
$extralabels=$extrafields->fetch_name_optionals_label('mailing');

llxHeader('',$langs->trans("Mailing"));

if ($action=='updatemailchimpcampaignstatus') {
	$form = new Form($db);
	$urlconfirm='id='.$id;
	$text=$langs->trans("MailChimpConfirmUpdateCampaignText",dol_buildpath('/mailchimp/scripts/update_all_campagin_target.php').' '.$user->login.' '.$langs->defaultlang.' '.$id);
	$ret=$form->form_confirm($_SERVER['PHP_SELF'].'?'.$urlconfirm,$langs->trans("MailChimpConfirmUpdateCampaign"),$text,"updatemailchimpcampaignstatus_confirm",'','',1,250);
	if ($ret == 'html') print '<br>';
}



$head = emailing_prepare_head($object);

dol_fiche_head($head, 'tabMailChimpSending', $langs->trans("MailChimp"), 0, 'email');

if (!empty($conf->global->MAILCHIMP_ACTIVE) || !empty($conf->global->MAILCHIMP_ACTIVE_MAILING_ONLY)) {

	$form = new Form($db);
	$formmailchimp = new FormMailChimp($db);

	/*print '<script type="text/javascript" language="javascript">
			$(document).ready(function() {

				// Click Function
				$(":button[name=updatemailchimpcampaignstatus]").click(function() {
						$( "#dialog" ).dialog();
						$( "#progressbar" ).progressbar({
							value: 37
						});
				});
			});
		</script>';

	print '<div id="dialog" title="Basic dialog" style="display:none">';
	print '<p>Operation in progress</p>';
	print '<div id="progressbar"></div>';
	print '</div>';*/

	print '<table class="border" width="100%">';

	if ((float) DOL_VERSION <= 3.6)	$linkback = '<a href="'.DOL_URL_ROOT.'/comm/mailing/liste.php">'.$langs->trans("BackToList").'</a>';
	else $linkback = '<a href="'.DOL_URL_ROOT.'/comm/mailing/list.php">'.$langs->trans("BackToList").'</a>';

	print '<tr><td width="15%">'.$langs->trans("Ref").'</td>';
	print '<td colspan="3">';
	print $form->showrefnav($object,'id', $linkback);
	print '</td></tr>';

	// Description
	print '<tr><td>'.$form->editfieldkey("MailTitle",'titre',$object->titre,$object,$user->rights->mailing->creer && $object->statut < 3,'string').'</td><td colspan="3">';
	print $form->editfieldval("MailTitle",'titre',$object->titre,$object,$user->rights->mailing->creer && $object->statut < 3,'string');
	print '</td></tr>';

	// From
	print '<tr><td>'.$form->editfieldkey("MailFrom",'email_from',$object->email_from,$object,$user->rights->mailing->creer && $object->statut < 3,'string').'</td><td colspan="3">';
	print $form->editfieldval("MailFrom",'email_from',$object->email_from,$object,$user->rights->mailing->creer && $object->statut < 3,'string');
	print '</td></tr>';

	// Status
	print '<tr><td width="15%">'.$langs->trans("Status").'</td><td colspan="3">'.$object->getLibStatut(4).'</td></tr>';

	// Nb of distinct emails
	print '<tr><td width="15%">';
	print $langs->trans("TotalNbOfDistinctRecipients");
	print '</td><td colspan="3">';
	$nbemail = ($object->nbemail?$object->nbemail:img_warning('').' <font class="warning">'.$langs->trans("MailChimpSelectSegmentOrList").'</font>');
	if ($object->statut != 3 && !empty($conf->global->MAILING_LIMIT_SENDBYWEB) && is_numeric($nbemail) && $conf->global->MAILING_LIMIT_SENDBYWEB < $nbemail)
	{
		if ($conf->global->MAILING_LIMIT_SENDBYWEB > 0)	{
			$text=$langs->trans('LimitSendingEmailing',$conf->global->MAILING_LIMIT_SENDBYWEB);
			print $form->textwithpicto($nbemail,$text,1,'warning');
		} else {
			$text=$langs->trans('NotEnoughPermissions');
			print $form->textwithpicto($nbemail,$text,1,'warning');
		}
	} else {
		print $nbemail;
	}
	print '</td></tr>';

	//Glue to avoid problem with edit in place option
	if (! empty($conf->global->MAIN_USE_JQUERY_JEDITABLE)) {
		$objecttoedit=$mailchimp;
		if (empty($mailchimp->id)) {
			$mailchimp->fk_mailing=$object->id;
			$result=$mailchimp->create($user);
		}
	}else {
		$objecttoedit=$object;
	}

	// MailChimp Sender Name
	print '<tr><td width="15%">';
	print $form->editfieldkey("MailChimpSenderName",'mailchimp_sender_name',$mailchimp->mailchimp_sender_name,$objecttoedit,$user->rights->mailing->creer && $object->statut < 3 && empty($mailchimp->mailchimp_id),'string');
	print '</td><td colspan="3">';
	print $form->editfieldval("MailChimpSenderName",'mailchimp_sender_name',$mailchimp->mailchimp_sender_name,$objecttoedit,$user->rights->mailing->creer && $object->statut < 3 && empty($mailchimp->mailchimp_id),'string');
	print '</td></tr>';


	if (!empty($mailchimp->mailchimp_id)) {

		//Status campaign mailchimp
		print '<tr><td width="15%">';
		print $langs->trans("MailChimpStatus");
		print '</td><td colspan="3">';
		if (!empty($mailchimp->mailchimp_id)) {
			print $mailchimp->getMailChimpCampaignStatus();
		}
		print '</td></tr>';

		// MailChimp Campaign
		print '<tr><td width="15%">';
		print $langs->trans("MailChimpCampaign");
		print '</td><td colspan="3">';
		print '<a target="_blanck" href="https://us7.admin.mailchimp.com/campaigns/">'.$langs->trans('MailChimpCampaign').'</a>';
		print '</td></tr>';

		//List campaign mailchimp
		print '<tr><td width="15%">';
		print $langs->trans("MailChimpDestList");
		print '</td><td colspan="3">';
		if (!empty($mailchimp->mailchimp_listid)) {
			$result=$mailchimp->getListDestinaries(array('list_id'=>$mailchimp->mailchimp_listid));
			if ($result<0) {
				setEventMessage($mailchimp->error,'errors');
			}
			if (is_array($mailchimp->listdest_lines) && count($mailchimp->listdest_lines)>0) {
				foreach($mailchimp->listdest_lines as $line) {
					print $line['name'];
				}
			}
		}
		print '</td></tr>';
		print '<tr><td width="15%">';
		print $langs->trans("MailChimpSegment");
		print '</td><td colspan="3">';
		if (!empty($mailchimp->mailchimp_segmentid) && !empty($mailchimp->mailchimp_listid)) {
			$result=$mailchimp->getListSegmentDestinaries($mailchimp->mailchimp_listid);
			if ($result<0) {
				setEventMessage($mailchimp->error,'errors');
			}
			if (is_array($mailchimp->listsegment_lines) && count($mailchimp->listsegment_lines)>0) {
				foreach($mailchimp->listsegment_lines as $line) {
					if ($mailchimp->mailchimp_segmentid== $line['id']) {
						print $line['name'];
					}
				}
			}
		}
		print '</td></tr>';
	}


	// Other attributes
	$parameters=array();
	$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);
	if (empty($reshook) && ! empty($extrafields->attribute_label)) {
		foreach($extrafields->attribute_label as $key=>$label) {
			$value=(isset($_POST["options_".$key])?$_POST["options_".$key]:$object->array_options["options_".$key]);
			print '<tr><td';
			if (! empty($extrafields->attribute_required[$key])) print ' class="fieldrequired"';
			print '>'.$label.'</td><td colspan="3">';
			print $extrafields->showInputField($key,$value);
			print "</td></tr>\n";
		}
	}

	print '</table>';

	if (empty($mailchimp->mailchimp_id) || $object->statut==0) {

		print '<form name="formmailing" method="post" action="'.$_SERVER["PHP_SELF"].'?id='.$id.'">';
		print '<input type="hidden" value="associateconfirm" name="action">';
		print '<input type="hidden" value="'.$_SESSION['newtoken'].'" name="token">';

		print '<table class="border" width="100%">';
		print '<tr class="pair"><td class="fieldrequired">';
		print $langs->trans('MailChimpUpdateExistingList');
		print '</td><td>';
		$events=array();
		if ($conf->use_javascript_ajax) {
			$events[]=array('method' => 'getSegment', 'url' => dol_buildpath('/mailchimp/mailchimp/ajax/mailchimp.php',1), 'htmlname' => 'segmentlist','params' => array('blocksegement' => 'style'));
		}
		print $formmailchimp->select_mailchimplist('selectlist',1,$mailchimp->mailchimp_listid,0,$events);
		print '&nbsp;'.$langs->trans('MailChimpOr');

		print '&nbsp;<a href="https://admin.mailchimp.com/lists/#" target="_blanck" >'.$langs->trans('MailChimpNewListName').'</a>';
		print '</td></tr>';

		print '<tr class="impair" id="blocksegement"><td class="fieldrequired">';
		print $langs->trans('MailChimpUpdateExistingSegments');
		print '</td><td>';
		print $formmailchimp->select_mailchimpsegement($mailchimp->mailchimp_listid,'segmentlist',1,$mailchimp->mailchimp_segmentid);
		print '<input type="checkbox" name="resetseg" value="1"/>'.$langs->trans('MailChimpResetSegment');
		print '&nbsp;'.$langs->trans('MailChimpOr');
		print '&nbsp;'.$langs->trans('MailChimpNewSegmentName').': <input type="text" class="flat" size="8" maxsize="50" name="segmentname">';
		print '</td></tr>';
		print '<tr class="pair"><td colspan="2" style="text-align:center">';
		print '<input type="submit" class="button" name="import" value="'.$langs->trans('MailChimpImportForm').'"/>';
		print '<input type="submit" class="button" name="exportList" value="'.$langs->trans('MailChimpExportToList').'"/>';
		print '<input type="submit" class="button" name="exportSegment" value="'.$langs->trans('MailChimpExportToSegment').'"/>';
		print '<input type="submit" class="button" name="updateonly" value="'.$langs->trans('MailChimpUpdateOnly').'"/>';
		print '<input type="submit" class="button" name="updatesegment" value="'.$langs->trans('MailChimpUpdateSegmentOnly').'"/>';
		print '</td></tr></table>';

		print '<form>';
	}

	print "</div>";

	if ($error_file_attach) {
		dol_htmloutput_mesg($langs->trans("MailChimpNoFileAttached"),'','error',1);
	}
	if ($error_no_unscubscribe_link) {
		dol_htmloutput_mesg($langs->trans("MailChimpUnsubLinkMandatory"),'','error',1);
	}
	if ($error_sendername) {
		dol_htmloutput_mesg($langs->trans("MailChimpSenderNameMandatory"),'','error',1);
	}
	if ($warning_destnotsync) {
		dol_htmloutput_mesg($langs->trans("MailChimpEmailNotSync"),'','warning',1);
		if (count($email_in_dol_not_in_mailchimp)>0) {
			dol_htmloutput_mesg($langs->trans("MailChimpEmailNotSyncInDolNotMailchimp").'<br>'.implode('<br>',$email_in_dol_not_in_mailchimp),'','warning',1);
		}
	}
	if ($object->statut == 0) {
		if ((float) DOL_VERSION < 3.7) dol_htmloutput_mesg($langs->trans("MailChimpNotValidated").' : <a href="'.dol_buildpath('/comm/mailing/fiche.php',1).'?id='.$object->id.'">'.$langs->trans('Mailing').'</a>','','warning',1);
		else  dol_htmloutput_mesg($langs->trans("MailChimpNotValidated").' : <a href="'.dol_buildpath('/comm/mailing/card.php',1).'?id='.$object->id.'">'.$langs->trans('Mailing').'</a>','','warning',1);
	}

	print "\n\n<div class=\"tabsAction\">\n";
	if (($object->statut == 0) && $user->rights->mailing->creer) {
		if ((float) DOL_VERSION < 3.7) print '<a class="butAction" href="'.dol_buildpath('/comm/mailing/fiche.php',1).'?action=edit&amp;id='.$object->id.'">'.$langs->trans("EditMailing").'</a>';
		else print '<a class="butAction" href="'.dol_buildpath('/comm/mailing/card.php',1).'?action=edit&amp;id='.$object->id.'">'.$langs->trans("EditMailing").'</a>';
	}

	if (($object->statut == 1 || $object->statut == 2) && $object->nbemail > 0 && $user->rights->mailing->valider && !$error_mailchimp_control) {
		if ((! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! $user->rights->mailing->mailing_advance->send)) {
			print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->transnoentitiesnoconv("NotEnoughPermissions")).'">'.$langs->trans("MailChimpCreateCampaign").'</a>';
		} else {
			if (empty($mailchimp->mailchimp_id)) {
				print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=createmailchimpcampaign&amp;id='.$object->id.'">'.$langs->trans("MailChimpCreateCampaign").'</a>';
			}
		}
	}else {
		print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->transnoentitiesnoconv("MailChimpCannotSendControlNotOK")).'">'.$langs->trans("MailChimpCreateCampaign").'</a>';
	}

	if (!empty($mailchimp->mailchimp_id) && !$error_mailchimp_control) {
		if (($object->statut == 1 || $object->statut == 2) && $object->nbemail > 0 && $user->rights->mailing->valider) {
			if ((! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! $user->rights->mailing->mailing_advance->send)) {
				print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->transnoentitiesnoconv("NotEnoughPermissions")).'">'.$langs->trans("SendMailing").'</a>';
			} else {
				print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=sendmailchimpcampaign&amp;id='.$object->id.'">'.$langs->trans("MailChimpSendMailing").'</a>';
			}
		}
	}

	if (!empty($mailchimp->mailchimp_id) && !$error_mailchimp_control) {
		if (($object->statut == 3 ) && $object->nbemail > 0 && $user->rights->mailing->valider) {
			if ((! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! $user->rights->mailing->mailing_advance->send)) {
				print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->transnoentitiesnoconv("NotEnoughPermissions")).'">'.$langs->trans("SendMailing").'</a>';
			} else {
				print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=updatemailchimpcampaignstatus&amp;id='.$object->id.'">'.$langs->trans("MailChimpUpdateStatus").'</a>';
			}
		}
		//TODO: manage with jquery to avoid timeout browser
		//print '<input type="button" name="updatemailchimpcampaignstatus" id="updatemailchimpcampaignstatus" value="' . $langs->trans ( 'MailChimpUpdateStatus' ) . '" class="butAction"/>';
	}

	if (!empty($export)) {// disable le boutton si on vient de remplir la liste


				?>
				<script type="text/javascript">
					$(function(){

						$("input[name='exportSegment']").prop('disabled', true);
					});
				</script>

				<?php
	}


	?>
				<script type="text/javascript">
					function checkFinishedImport(){
						check = setInterval(function() {
						$.ajax({
								method:'POST'
								,url:'<?php echo dol_buildpath('/mailchimp/scripts/interface.php',1); ?>'
								,dataType:'json'
								,data:{
									action:'getBatchesInfo',
									fk_camp:<?php echo $id;?>
								}

							}).done(function(data) {

								if(data.status == 'finished'){
									$("input[name='exportSegment']").prop('disabled', false);

									var txt= "<?php echo $langs->trans('AddFinishedYouCanSyncSeg'); ?>";
									$.jnotify(txt, "", true);

									clearInterval(check);
								}
							});
						},2000);

					}
				</script>


	<?php
	if(!empty($_SESSION['MAILCHIMP_ID_BATCH'][$id])){
		?><script type="text/javascript">
			checkFinishedImport();//on ne peut synchroniser le segment qu'une fois que la liste est remplie.
			</script><?php
	}
	if (!empty($conf->global->MAILCHIMP_SHOW_API_MESSAGE))
	{
		if (empty($_SESSION['MAILCHIMP_LAST_BATCHES_ID'])) print '<input class="butActionRefused" title="'.$langs->trans('DEV_EmptyBatchIdStartExport').'" type="button" id="getInfoFromLastBatchesEmpty" value="'.$langs->trans('DEV_InfoExport').'" />';
		else print '<input class="butAction" type="button" id="getInfoFromLastBatches" value="'.$langs->trans('DEV_InfoExport').'" />';

		if (empty($_SESSION['MAILCHIMP_MEMBERS_LAST_SEGMENT'])) print '<input class="butActionRefused" title="'.$langs->trans('DEV_EmptySegIdStartExport').'" type="button" id="getMembersSegmentInfoEmpty" value="'.$langs->trans('DEV_InfoSegExport').'" />';
		else print '<input class="butAction" type="button" title="'.$langs->trans('DEV_InfoSegExport_LookConsol').'" id="getMembersSegmentInfo" value="'.$langs->trans('DEV_InfoSegExport').'" />';

		?>
		<script type="text/javascript">
			$(function() {
				$('#getInfoFromLastBatches').click(function() {
					$.ajax({
						method:'POST'
						,url:'<?php echo dol_buildpath('/mailchimp/scripts/interface.php',1); ?>'
						,dataType:'json'
						,data:{
							action:'getLastBatchesInfo'
						}

					}).done(function(data) {
						console.log(data);

						var txt = 'Status: '+data.status+'<br />';
						txt += 'Total operations: '+data.total_operations+'<br />';
						txt += 'Finished operations: '+data.finished_operations+'<br />';
						txt += 'Errored operations: '+data.errored_operations+'<br />';
						txt += 'See: <a href="'+data.response_body_url+'">'+data.response_body_url+'</a> for more informations'
						$.jnotify(txt, "warning", true);
					});
				});


				$('#getMembersSegmentInfo').click(function() {
					$.ajax({
						method:'POST'
						,url:'<?php echo dol_buildpath('/mailchimp/scripts/interface.php',1); ?>'
						,dataType:'json'
						,data:{
							action:'getMembersSegmentInfo'
						}

					}).done(function(data) {
						console.log(data);

//						for (let i in data) {
//							console.log(data[i]);
//						}

					});
				});
			});
		</script>
		<?php
	}

	print '<br><br></div>';


	// Print mail content
	print_fiche_titre($langs->trans("EMail"),'','');
	print '<table class="border" width="100%">';

	// Subject
	print '<tr><td width="15%">'.$langs->trans("MailTopic").'</td><td colspan="3">'.$object->sujet.'</td></tr>';

	// Message
	print '<tr><td valign="top">'.$langs->trans("MailMessage").'</td>';
	print '<td colspan="3" bgcolor="'.($object->bgcolor?(preg_match('/^#/',$object->bgcolor)?'':'#').$object->bgcolor:'white').'">';
	print dol_htmlentitiesbr($object->body);
	print '</td>';
	print '</tr>';

	print '</table>';
	print "<br>";
}else {
	dol_htmloutput_mesg($langs->trans("MailChimpNotActive"),'','error',1);
}


// End of page
dol_fiche_end();
llxFooter();
$db->close();
