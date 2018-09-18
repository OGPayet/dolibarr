<?php
/* Copyright (C) 2018      Open-DSI              <support@open-dsi.fr>
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
 *	\file       	htdocs/requestmanager/lists_follow.php
 *	\ingroup    	requestmanager
 *	\brief      	Page of request manager lists to follow
 */

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include '../main.inc.php';					// to work if your module directory is into dolibarr root htdocs directory
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (! $res && file_exists("../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../dolibarr/htdocs/main.inc.php';     // Used on dev env only
if (! $res && file_exists("../../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../../dolibarr/htdocs/main.inc.php';   // Used on dev env only
if (! $res) die("Include of main fails");
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';
dol_include_once('/requestmanager/class/requestmanager.class.php');
dol_include_once('/requestmanager/class/html.formrequestmanager.class.php');
dol_include_once('/requestmanager/lib/requestmanager.lib.php');

$langs->load('requestmanager@requestmanager');

$search_ref                       = GETPOST('search_ref','alpha');
$search_ref_ext                   = GETPOST('search_ref_ext','alpha');
$search_type                      = GETPOST('search_type','alpha');
$search_category                  = GETPOST('search_category','alpha');
$search_label                     = GETPOST('search_label','alpha');
$search_thridparty_origin         = GETPOST('search_thridparty_origin','alpha');
$search_thridparty                = GETPOST('search_thridparty','alpha');
$search_thridparty_benefactor     = GETPOST('search_thridparty_benefactor','alpha');
$search_source                    = GETPOST('search_source','alpha');
$search_urgency                   = GETPOST('search_urgency','alpha');
$search_impact                    = GETPOST('search_impact','alpha');
$search_priority                  = GETPOST('search_priority','alpha');
$search_duration                  = GETPOST('search_duration','alpha');
$search_date_operation            = GETPOST('search_date_operation','alpha');
$search_date_deadline             = GETPOST('search_date_deadline','alpha');
$search_notify_requester_by_email = GETPOST('search_notify_requester_by_email','int');
$search_notify_watcher_by_email   = GETPOST('search_notify_watcher_by_email','int');
$search_assigned_user             = GETPOST('search_assigned_user','alpha');
$search_assigned_usergroup        = GETPOST('search_assigned_usergroup','alpha');
$search_notify_assigned_by_email  = GETPOST('search_notify_assigned_by_email','int');
$search_description               = GETPOST('search_description','alpha');
$search_date_resolved             = GETPOST('search_date_resolved','alpha');
$search_date_cloture              = GETPOST('search_date_cloture','alpha');
$search_user_resolved             = GETPOST('search_user_resolved','alpha');
$search_user_cloture              = GETPOST('search_user_cloture','alpha');
$search_date_creation             = GETPOST('search_date_creation','alpha');
$search_date_modification         = GETPOST('search_date_modification','alpha');
$search_user_author               = GETPOST('search_user_author','alpha');
$search_user_modification         = GETPOST('search_user_modification','alpha');
$sortfield                        = GETPOST("sortfield",'alpha');
$sortorder                        = GETPOST("sortorder",'alpha');

// Security check
$socid = 0;
if (! empty($user->societe_id))	$socid=$user->societe_id;
if (! empty($socid)) {
    $result = restrictedArea($user, 'societe', $socid, '&societe');
}
$result = restrictedArea($user, 'requestmanager', '', '');

if (!$sortfield) $sortfield = 'rm.date_deadline';
if (!$sortorder) $sortorder = 'DESC';

$arrayfields = array(
    'rm.ref'                       => array('label' => $langs->trans("Ref"), 'checked' => 1),
    'rm.ref_ext'                   => array('label' => $langs->trans("RequestManagerExternalReferenceShort"), 'checked' => 1),
    'rm.fk_type'                   => array('label' => $langs->trans("RequestManagerType"), 'checked' => 1),
    'rm.fk_category'               => array('label' => $langs->trans("RequestManagerCategory"), 'checked' => 1),
    'rm.label'                     => array('label' => $langs->trans("RequestManagerLabel"), 'checked' => 1),
    'rm.fk_soc_origin'             => array('label' => $langs->trans("RequestManagerThirdPartyOrigin"), 'checked' => 1),
    'rm.fk_soc'                    => array('label' => $langs->trans("RequestManagerThirdPartyBill"), 'checked' => 1),
    'rm.fk_soc_benefactor'         => array('label' => $langs->trans("RequestManagerThirdPartyBenefactor"), 'checked' => 1),
    'rm.description'               => array('label' => $langs->trans("RequestManagerDescription"), 'checked' => 0),
    'rm.fk_source'                 => array('label' => $langs->trans("RequestManagerSource"), 'checked' => 0),
    'rm.fk_urgency'                => array('label' => $langs->trans("RequestManagerUrgency"), 'checked' => 0),
    'rm.fk_impact'                 => array('label' => $langs->trans("RequestManagerImpact"), 'checked' => 0),
    'rm.fk_priority'               => array('label' => $langs->trans("RequestManagerPriority"), 'checked' => 0),
    'rm.duration'                  => array('label' => $langs->trans("RequestManagerDuration"), 'checked' => 0),
    'rm.date_operation'            => array('label' => $langs->trans("RequestManagerOperation"), 'checked' => 1),
    'rm.date_deadline'             => array('label' => $langs->trans("RequestManagerDeadline"), 'checked' => 1),
    'rm.notify_requester_by_email' => array('label' => $langs->trans("RequestManagerRequesterNotification"), 'checked' => 0),
    'rm.notify_watcher_by_email'   => array('label' => $langs->trans("RequestManagerWatcherNotification"), 'checked' => 0),
    'assigned_users'               => array('label' => $langs->trans("RequestManagerAssignedUsers"), 'checked' => 1),
    'assigned_usergroups'          => array('label' => $langs->trans("RequestManagerAssignedUserGroups"), 'checked' => 1),
    'rm.notify_assigned_by_email'  => array('label' => $langs->trans("RequestManagerAssignedNotification"), 'checked' => 0),
    'rm.fk_user_resolved'          => array('label' => $langs->trans("RequestManagerResolvedBy"), 'checked' => 0, 'position' => 10),
    'rm.fk_user_closed'            => array('label' => $langs->trans("ClosedBy"), 'checked' => 0, 'position' => 10),
    'rm.date_resolved'             => array('label' => $langs->trans("RequestManagerDateResolved"), 'checked' => 0, 'position' => 10),
    'rm.date_cloture'              => array('label' => $langs->trans("DateClosing"), 'checked' => 0, 'position' => 10),
    'rm.fk_user_author'            => array('label' => $langs->trans("Author"), 'checked' => 0, 'position' => 10),
    'rm.fk_user_modif'             => array('label' => $langs->trans("ModifiedBy"), 'checked' => 0, 'position' => 10),
    'rm.datec'                     => array('label' => $langs->trans("DateCreation"), 'checked' => 0, 'position' => 500),
    'rm.tms'                       => array('label' => $langs->trans("DateModificationShort"), 'checked' => 0, 'position' => 500),
    'rm.fk_status'                 => array('label' => $langs->trans("Status"), 'checked' => 1, 'position' => 1000),
);


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT . '/core/actions_changeselectedfields.inc.php';

if ($search_notify_requester_by_email == '') $search_notify_requester_by_email = -1;
if ($search_notify_watcher_by_email == '') $search_notify_watcher_by_email = -1;
if ($search_notify_assigned_by_email == '') $search_notify_assigned_by_email = -1;


/*
 * View
 */

$now = dol_now();

// save last view date of this page
$_SESSION['rm_lists_follow_last_date'] = $now;

$form             = new Form($db);
$usergroup_static = new UserGroup($db);

llxHeader('',$langs->trans('RequestManagerListsFollowTitle'));

if ($status_type == '') $status_type = -1;


$param = '';
if ($search_ref) $param .= '&search_ref=' . urlencode($search_ref);
if ($search_ref_ext) $param .= '&search_ref_ext=' . urlencode($search_ref_ext);
if ($search_type) $param .= '&search_type=' . urlencode($search_type);
if ($search_category) $param .= '&search_category=' . urlencode($search_category);
if ($search_label) $param .= '&search_label=' . urlencode($search_label);
if ($search_thridparty_origin) $param .= '&search_thridparty_origin=' . urlencode($search_thridparty_origin);
if ($search_thridparty) $param .= '&search_thridparty=' . urlencode($search_thridparty);
if ($search_thridparty_benefactor) $param .= '&search_thridparty_benefactor=' . urlencode($search_thridparty_benefactor);
if ($search_source) $param .= '&search_source=' . urlencode($search_source);
if ($search_urgency) $param .= '&search_urgency=' . urlencode($search_urgency);
if ($search_impact) $param .= '&search_impact=' . urlencode($search_impact);
if ($search_priority) $param .= '&search_priority=' . urlencode($search_priority);
if ($search_duration) $param .= '&search_duration=' . urlencode($search_duration);
if ($search_date_operation) $param .= '&search_date_operation=' . urlencode($search_date_operation);
if ($search_date_deadline) $param .= '&search_date_deadline=' . urlencode($search_date_deadline);
if ($search_notify_requester_by_email >= 0) $param .= '&search_notify_requester_by_email=' . urlencode($search_notify_requester_by_email);
if ($search_notify_watcher_by_email >= 0) $param .= '&search_notify_watcher_by_email=' . urlencode($search_notify_watcher_by_email);
if ($search_assigned_user) $param .= '&search_assigned_user=' . urlencode($search_assigned_user);
if ($search_assigned_usergroup) $param .= '&search_assigned_usergroup=' . urlencode($search_assigned_usergroup);
if ($search_notify_assigned_by_email >= 0) $param .= '&search_notify_assigned_by_email=' . urlencode($search_notify_assigned_by_email);
if ($search_description) $param .= '&search_description=' . urlencode($search_description);
if ($search_date_resolved) $param .= '&search_date_resolved=' . urlencode($search_date_resolved);
if ($search_date_cloture) $param .= '&search_date_cloture=' . urlencode($search_date_cloture);
if ($search_user_resolved) $param .= '&search_user_resolved=' . urlencode($search_user_resolved);
if ($search_user_cloture) $param .= '&search_user_cloture=' . urlencode($search_user_cloture);
if ($search_date_creation) $param .= '&search_date_creation=' . urlencode($search_date_creation);
if ($search_date_modification) $param .= '&search_date_modification=' . urlencode($search_date_modification);
if ($search_user_author) $param .= '&search_user_author=' . urlencode($search_user_author);
if ($search_user_modification) $param .= '&search_user_modification=' . urlencode($search_user_modification);

$objectstatic  = new RequestManager($db);
$societestatic_origin = new Societe($db);
$societestatic = new Societe($db);
$societestatic_benefactor = new Societe($db);
$userstatic    = new User($db);


print_fiche_titre($langs->trans('RequestManagerListsFollowTitle'), '', 'requestmanager@requestmanager');

// Lignes des champs de filtre
print '<form method="POST" id="searchFormList" action="' . $_SERVER["PHP_SELF"] . '">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';

$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);    // This also change content of $arrayfields

print '<div class="div-table-responsive">';
print '<table class="tagtable liste">' . "\n";

// Fields title
$nbCol = 0;
print '<tr class="liste_titre">';
if (!empty($arrayfields['rm.ref']['checked'])) print_liste_field_titre($arrayfields['rm.ref']['label'], $_SERVER["PHP_SELF"], 'rm.ref', '', $param, '', $sortfield, $sortorder); $nbCol++;
if (!empty($arrayfields['rm.ref_ext']['checked'])) print_liste_field_titre($arrayfields['rm.ref_ext']['label'], $_SERVER["PHP_SELF"], 'rm.ref_ext', '', $param, '', $sortfield, $sortorder); $nbCol++;
if (!empty($arrayfields['rm.fk_type']['checked'])) print_liste_field_titre($arrayfields['rm.fk_type']['label'], $_SERVER["PHP_SELF"], 'crmrt.label', '', $param, '', $sortfield, $sortorder); $nbCol++;
if (!empty($arrayfields['rm.fk_category']['checked'])) print_liste_field_titre($arrayfields['rm.fk_category']['label'], $_SERVER["PHP_SELF"], 'crmc.label', '', $param, '', $sortfield, $sortorder); $nbCol++;
if (!empty($arrayfields['rm.label']['checked'])) print_liste_field_titre($arrayfields['rm.label']['label'], $_SERVER["PHP_SELF"], 'rm.label', '', $param, '', $sortfield, $sortorder); $nbCol++;
if (!empty($arrayfields['rm.fk_soc_origin']['checked'])) print_liste_field_titre($arrayfields['rm.fk_soc_origin']['label'], $_SERVER["PHP_SELF"], 'so.nom', '', $param, '', $sortfield, $sortorder); $nbCol++;
if (!empty($arrayfields['rm.fk_soc']['checked'])) print_liste_field_titre($arrayfields['rm.fk_soc']['label'], $_SERVER["PHP_SELF"], 's.nom', '', $param, '', $sortfield, $sortorder); $nbCol++;
if (!empty($arrayfields['rm.fk_soc_benefactor']['checked'])) print_liste_field_titre($arrayfields['rm.fk_soc_benefactor']['label'], $_SERVER["PHP_SELF"], 'sb.nom', '', $param, '', $sortfield, $sortorder); $nbCol++;
if (!empty($arrayfields['rm.description']['checked'])) print_liste_field_titre($arrayfields['rm.description']['label'], $_SERVER["PHP_SELF"], 'rm.description', '', $param, '', $sortfield, $sortorder); $nbCol++;
if (!empty($arrayfields['rm.fk_source']['checked'])) print_liste_field_titre($arrayfields['rm.fk_source']['label'], $_SERVER["PHP_SELF"], 'crms.label', '', $param, '', $sortfield, $sortorder); $nbCol++;
if (!empty($arrayfields['rm.fk_urgency']['checked'])) print_liste_field_titre($arrayfields['rm.fk_urgency']['label'], $_SERVER["PHP_SELF"], 'crmu.label', '', $param, '', $sortfield, $sortorder); $nbCol++;
if (!empty($arrayfields['rm.fk_impact']['checked'])) print_liste_field_titre($arrayfields['rm.fk_impact']['label'], $_SERVER["PHP_SELF"], 'crmi.label', '', $param, '', $sortfield, $sortorder); $nbCol++;
if (!empty($arrayfields['rm.fk_priority']['checked'])) print_liste_field_titre($arrayfields['rm.fk_priority']['label'], $_SERVER["PHP_SELF"], 'crmp.label', '', $param, '', $sortfield, $sortorder); $nbCol++;
if (!empty($arrayfields['rm.duration']['checked'])) print_liste_field_titre($arrayfields['rm.duration']['label'], $_SERVER["PHP_SELF"], 'rm.duration', '', $param, '', $sortfield, $sortorder); $nbCol++;
if (!empty($arrayfields['rm.date_operation']['checked'])) print_liste_field_titre($arrayfields['rm.date_operation']['label'], $_SERVER["PHP_SELF"], 'rm.date_operation', 'align="center"', $param, '', $sortfield, $sortorder); $nbCol++;
if (!empty($arrayfields['rm.date_deadline']['checked'])) print_liste_field_titre($arrayfields['rm.date_deadline']['label'], $_SERVER["PHP_SELF"], 'rm.date_deadline', 'align="center"', $param, '', $sortfield, $sortorder); $nbCol++;
if (!empty($arrayfields['rm.notify_requester_by_email']['checked'])) print_liste_field_titre($arrayfields['rm.notify_requester_by_email']['label'], $_SERVER["PHP_SELF"], 'rm.notify_requester_by_email', 'align="center"', $param, '', $sortfield, $sortorder); $nbCol++;
if (!empty($arrayfields['rm.notify_watcher_by_email']['checked'])) print_liste_field_titre($arrayfields['rm.notify_watcher_by_email']['label'], $_SERVER["PHP_SELF"], 'rm.notify_watcher_by_email', 'align="center"', $param, '', $sortfield, $sortorder); $nbCol++;
if (!empty($arrayfields['assigned_users']['checked'])) print_liste_field_titre($arrayfields['assigned_users']['label'], $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder); $nbCol++;
if (!empty($arrayfields['assigned_usergroups']['checked'])) print_liste_field_titre($arrayfields['assigned_usergroups']['label'], $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder); $nbCol++;
if (!empty($arrayfields['rm.notify_assigned_by_email']['checked'])) print_liste_field_titre($arrayfields['rm.notify_assigned_by_email']['label'], $_SERVER["PHP_SELF"], 'rm.notify_assigned_by_email', 'align="center"', $param, '', $sortfield, $sortorder); $nbCol++;
if (!empty($arrayfields['rm.fk_user_resolved']['checked'])) print_liste_field_titre($arrayfields['rm.fk_user_resolved']['label'], $_SERVER["PHP_SELF"], 'ur.lastname', '', $param, '', $sortfield, $sortorder); $nbCol++;
if (!empty($arrayfields['rm.fk_user_closed']['checked'])) print_liste_field_titre($arrayfields['rm.fk_user_closed']['label'], $_SERVER["PHP_SELF"], 'uc.lastname', '', $param, '', $sortfield, $sortorder); $nbCol++;
if (!empty($arrayfields['rm.date_resolved']['checked'])) print_liste_field_titre($arrayfields['rm.date_resolved']['label'], $_SERVER["PHP_SELF"], 'rm.date_resolved', 'align="center"', $param, '', $sortfield, $sortorder); $nbCol++;
if (!empty($arrayfields['rm.date_cloture']['checked'])) print_liste_field_titre($arrayfields['rm.date_cloture']['label'], $_SERVER["PHP_SELF"], 'rm.date_cloture', 'align="center"', $param, '', $sortfield, $sortorder); $nbCol++;
if (!empty($arrayfields['rm.fk_user_author']['checked'])) print_liste_field_titre($arrayfields['rm.fk_user_author']['label'], $_SERVER["PHP_SELF"], 'ua.lastname', '', $param, '', $sortfield, $sortorder); $nbCol++;
if (!empty($arrayfields['rm.fk_user_modif']['checked'])) print_liste_field_titre($arrayfields['rm.fk_user_modif']['label'], $_SERVER["PHP_SELF"], 'um.lastname', '', $param, '', $sortfield, $sortorder); $nbCol++;
if (!empty($arrayfields['rm.datec']['checked'])) print_liste_field_titre($arrayfields['rm.datec']['label'], $_SERVER["PHP_SELF"], "rm.datec", "", $param, 'align="center" class="nowrap"', $sortfield, $sortorder); $nbCol++;
if (!empty($arrayfields['rm.tms']['checked'])) print_liste_field_titre($arrayfields['rm.tms']['label'], $_SERVER["PHP_SELF"], "rm.tms", "", $param, 'align="center" class="nowrap"', $sortfield, $sortorder); $nbCol++;
if (!empty($arrayfields['rm.fk_status']['checked'])) print_liste_field_titre($arrayfields['rm.fk_status']['label'], $_SERVER["PHP_SELF"], "crmst.label", "", $param, 'align="right"', $sortfield, $sortorder); $nbCol++;
print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], '', '', '', 'align="center"', $sortfield, $sortorder, 'maxwidthsearch'); $nbCol++;
print '</tr>' . "\n";

// Join conditions
$sqlJoinActionComm = ' LEFT JOIN ' . MAIN_DB_PREFIX . 'actioncomm as ac ON ac.elementtype="requestmanager" AND ac.fk_element=rm.rowid';

$sqlAssignedSubSelectBegin =
    ' SELECT DISTINCT rm.rowid' .
    ' FROM ' . MAIN_DB_PREFIX . 'requestmanager as rm' .
    ' LEFT JOIN ' . MAIN_DB_PREFIX . 'requestmanager_assigned_user as rmau ON rmau.fk_requestmanager = rm.rowid' .
    ' LEFT JOIN ' . MAIN_DB_PREFIX . 'requestmanager_assigned_usergroup as rmaug ON rmaug.fk_requestmanager = rm.rowid' .
    ' WHERE rm.entity IN (' . getEntity('requestmanager') . ')';
$sqlFilterAssignedToMe       = ' AND rmau.fk_user = ' . $user->id;
$sqlFilterAssignedToMyGroups = '';
$sqlFilterAssignedToMeOrMyGroups = $sqlFilterAssignedToMe;
$groupslist = $usergroup_static->listGroupsForUser($user->id);
if (!empty($groupslist)) {
    $myGroups = implode(',', array_keys($groupslist));
    $sqlFilterAssignedToMyGroups = ' AND rmaug.fk_usergroup IN (' . $myGroups . ')';
    $sqlFilterAssignedToMeOrMyGroups = ' AND (rmau.fk_user = ' . $user->id . ' OR rmaug.fk_usergroup IN (' . $myGroups . '))';
}

$sqlJoinAssignedFilterBegin = ' INNER JOIN (';
$sqlJoinAssignedFilterEnd = ' ) as assigned ON assigned.rowid = rm.rowid';
$sqlJoinNotAssignedFilterBegin = ' LEFT JOIN (';
$sqlJoinNotAssignedFilterEnd = ' ) as not_assigned ON not_assigned.rowid = rm.rowid';

// Different filters for all lists
$sqlFilterInProgress             = ' AND crmst.type IN (' . RequestManager::STATUS_TYPE_INITIAL . ', ' . RequestManager::STATUS_TYPE_IN_PROGRESS . ')';
$sqlFilterNotInFuture            = ' AND (rm.date_operation IS NULL OR rm.date_operation <= NOW())';
$sqlFilterInFuture               = ' AND rm.date_operation IS NOT NULL AND rm.date_operation > NOW()';
$sqlFilterNotAssigned            = ' AND not_assigned.rowid IS NULL';
$sqlFilterActionCommAssignedToMe = ' AND ac.fk_user_action = ' . $user->id;

// 1 - List of requests in progress assigned to me
FormRequestManager::listsFollowPrintListFrom($db, $arrayfields, $objectstatic, $societestatic_origin, $societestatic, $societestatic_benefactor, $userstatic,
    $sqlJoinAssignedFilterBegin . $sqlAssignedSubSelectBegin . $sqlFilterAssignedToMe . $sqlJoinAssignedFilterEnd,
    $sqlFilterInProgress . $sqlFilterNotInFuture,
    $sortfield, $sortorder, 'RequestManagerListsFollowMyRequest', $nbCol);

// 2 - List of requests in progress assigned to my group(s) and not to me
FormRequestManager::listsFollowPrintListFrom($db, $arrayfields, $objectstatic, $societestatic_origin, $societestatic, $societestatic_benefactor, $userstatic,
    $sqlJoinAssignedFilterBegin . $sqlAssignedSubSelectBegin . $sqlFilterAssignedToMyGroups . $sqlJoinAssignedFilterEnd .
    $sqlJoinNotAssignedFilterBegin . $sqlAssignedSubSelectBegin . $sqlFilterAssignedToMe . $sqlJoinNotAssignedFilterEnd,
    $sqlFilterInProgress . $sqlFilterNotInFuture . $sqlFilterNotAssigned,
    $sortfield, $sortorder, 'RequestManagerListsFollowMyGroupRequest', $nbCol);

// 3 - List of requests in progress not assigned to my group(s) and not assigned to me
FormRequestManager::listsFollowPrintListFrom($db, $arrayfields, $objectstatic, $societestatic_origin, $societestatic, $societestatic_benefactor, $userstatic,
    $sqlJoinActionComm . $sqlJoinNotAssignedFilterBegin . $sqlAssignedSubSelectBegin . $sqlFilterAssignedToMeOrMyGroups . $sqlJoinNotAssignedFilterEnd,
    $sqlFilterInProgress . $sqlFilterActionCommAssignedToMe . $sqlFilterNotAssigned,
    $sortfield, $sortorder, 'RequestManagerListsFollowLinkToMyEvent', $nbCol);

// 4 - List of requests in future assigned to me
FormRequestManager::listsFollowPrintListFrom($db, $arrayfields, $objectstatic, $societestatic_origin, $societestatic, $societestatic_benefactor, $userstatic,
    $sqlJoinAssignedFilterBegin . $sqlAssignedSubSelectBegin . $sqlFilterAssignedToMe . $sqlJoinAssignedFilterEnd,
    $sqlFilterInProgress . $sqlFilterInFuture,
    $sortfield, $sortorder, 'RequestManagerListsFollowMyFutureRequest', $nbCol);

// 5 - List request in future assigned to my group(s) and not to me
FormRequestManager::listsFollowPrintListFrom($db, $arrayfields, $objectstatic, $societestatic_origin, $societestatic, $societestatic_benefactor, $userstatic,
    $sqlJoinAssignedFilterBegin . $sqlAssignedSubSelectBegin . $sqlFilterAssignedToMyGroups . $sqlJoinAssignedFilterEnd .
    $sqlJoinNotAssignedFilterBegin . $sqlAssignedSubSelectBegin . $sqlFilterAssignedToMe . $sqlJoinNotAssignedFilterEnd,
    $sqlFilterInProgress . $sqlFilterInFuture . $sqlFilterNotAssigned,
    $sortfield, $sortorder, 'RequestManagerListsFollowMyFutureGroupRequest', $nbCol);


print '</table>' . "\n";
print '</div>' . "\n";
print '</form>' . "\n";


// End of page
llxFooter();
$db->close();
