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
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
dol_include_once('/requestmanager/class/requestmanager.class.php');
dol_include_once('/requestmanager/class/html.formrequestmanager.class.php');
dol_include_once('/advancedictionaries/class/dictionary.class.php');
dol_include_once('/requestmanager/lib/requestmanager.lib.php');

$langs->load('requestmanager@requestmanager');

$action         = GETPOST('action', 'alpha');
//$massaction     = GETPOST('massaction', 'alpha');
$confirm        = GETPOST('confirm', 'alpha');
//$toselect       = GETPOST('toselect', 'array');

//$sall                               = GETPOST('sall', 'alphanohtml');
$search_ref                         = GETPOST('search_ref', 'alpha');
$search_ref_ext                     = GETPOST('search_ref_ext', 'alpha');
$search_type                        = GETPOST('search_type', 'alpha');
$search_category                    = GETPOST('search_category', 'alpha');
$search_label                       = GETPOST('search_label', 'alpha');
$search_thridparty_origin           = GETPOST('search_thridparty_origin', 'alpha');
$search_thridparty                  = GETPOST('search_thridparty', 'alpha');
$search_thridparty_benefactor       = GETPOST('search_thridparty_benefactor', 'alpha');
$search_thridparty_watcher          = GETPOST('search_thridparty_watcher', 'alpha');
$search_source                      = GETPOST('search_source', 'alpha');
$search_urgency                     = GETPOST('search_urgency', 'alpha');
$search_impact                      = GETPOST('search_impact', 'alpha');
$search_priority                    = GETPOST('search_priority', 'alpha');
$search_duration                    = GETPOST('search_duration', 'alpha');
$search_date_operation              = GETPOST('search_date_operation', 'alpha');
$search_date_deadline               = GETPOST('search_date_deadline', 'alpha');
$search_notify_requester_by_email   = GETPOST('search_notify_requester_by_email', 'int');
$search_notify_watcher_by_email     = GETPOST('search_notify_watcher_by_email', 'int');
$search_assigned_user               = GETPOST('search_assigned_user', 'alpha');
$search_assigned_usergroup          = GETPOST('search_assigned_usergroup', 'alpha');
$search_notify_assigned_by_email    = GETPOST('search_notify_assigned_by_email', 'int');
$search_description                 = GETPOST('search_description', 'alpha');
$search_date_resolved               = GETPOST('search_date_resolved', 'alpha');
$search_date_cloture                = GETPOST('search_date_cloture', 'alpha');
$search_user_resolved               = GETPOST('search_user_resolved', 'alpha');
$search_user_cloture                = GETPOST('search_user_cloture', 'alpha');
$search_date_creation               = GETPOST('search_date_creation', 'alpha');
$search_date_modification           = GETPOST('search_date_modification', 'alpha');
$search_user_author                 = GETPOST('search_user_author', 'alpha');
$search_user_modification           = GETPOST('search_user_modification', 'alpha');
$search_status_det                  = GETPOST('search_status_det','array');
$status_type                        = GETPOST('status_type', 'int');
$optioncss                          = GETPOST('optioncss', 'alpha');

$limit      = GETPOST('limit')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield  = GETPOST("sortfield",'alpha');
$sortorder  = GETPOST("sortorder",'alpha');
$page       = GETPOST("page",'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) $sortfield = 'rm.date_deadline';
if (!$sortorder) $sortorder = 'DESC';

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$contextpage='requestmanagerfollowlist';

// Security check
$socid = 0;
if (! empty($user->societe_id))	$socid=$user->societe_id;
if (! empty($socid)) {
    $result = restrictedArea($user, 'societe', $socid, '&societe');
}
$result = restrictedArea($user, 'requestmanager', '', '');

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array($contextpage));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('requestmanager');
$search_array_options=$extrafields->getOptionalsFromPost($extralabels,'','search_');

$arrayfields = array(
    'rm.ref'                       => array('label' => $langs->trans("Ref"), 'checked' => 1),
    'rm.ref_ext'                   => array('label' => $langs->trans("RequestManagerExternalReferenceShort"), 'checked' => 1),
    'rm.fk_type'                   => array('label' => $langs->trans("RequestManagerType"), 'checked' => 1),
    'rm.fk_category'               => array('label' => $langs->trans("RequestManagerCategory"), 'checked' => 1),
    'rm.label'                     => array('label' => $langs->trans("RequestManagerLabel"), 'checked' => 1),
    'rm.fk_soc_origin'             => array('label' => $langs->trans("RequestManagerThirdPartyOrigin"), 'checked' => 1),
    'rm.fk_soc'                    => array('label' => $langs->trans("RequestManagerThirdPartyPrincipal"), 'checked' => 1),
    'rm.fk_soc_benefactor'         => array('label' => $langs->trans("RequestManagerThirdPartyBenefactor"), 'checked' => 1),
    'rm.fk_soc_watcher'            => array('label' => $langs->trans("RequestManagerThirdPartyWatcher"), 'checked' => 0),
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
// Extra fields
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
{
    foreach($extrafields->attribute_label as $key => $val)
    {
        $arrayfields["ef.".$key]=array('label'=>$extrafields->attribute_label[$key], 'checked'=>$extrafields->attribute_list[$key], 'position'=>$extrafields->attribute_pos[$key], 'enabled'=>$extrafields->attribute_perms[$key]);
    }
}


/*
 * Actions
 */
if (GETPOST('cancel')) { $action='list'; $massaction=''; }
//if (! GETPOST('confirmmassaction') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction=''; }

$parameters=array('socid'=>$socid);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

// Do we click on purge search criteria ?
if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) // All tests are required to be compatible with all browsers
{
    $search_ref='';
    $search_ref_ext='';
    $search_type='';
    $search_category='';
    $search_label='';
    $search_thridparty_origin='';
    $search_thridparty='';
    $search_thridparty_benefactor='';
    $search_thridparty_watcher='';
    $search_source='';
    $search_urgency='';
    $search_impact='';
    $search_priority='';
    $search_duration='';
    $search_date_operation='';
    $search_date_deadline='';
    $search_notify_requester_by_email=-1;
    $search_notify_watcher_by_email=-1;
    $search_assigned_user='';
    $search_assigned_usergroup='';
    $search_notify_assigned_by_email=-1;
    $search_description='';
    $search_date_resolved='';
    $search_date_cloture='';
    $search_user_resolved='';
    $search_user_cloture='';
    $search_date_creation='';
    $search_date_modification='';
    $search_user_author='';
    $search_user_modification='';
    $status_type=-1;
	//$toselect='';
    $search_array_options=array();
    $search_status_det=array();
}
if ($search_notify_requester_by_email === '') $search_notify_requester_by_email = -1;
if ($search_notify_watcher_by_email === '') $search_notify_watcher_by_email = -1;
if ($search_notify_assigned_by_email === '') $search_notify_assigned_by_email = -1;
if ($status_type === '') $status_type = -1;

if (empty($reshook))
{
//    $objectclass='RequestManager';
//    $objectlabel='RequestManagerRequests';
//    $permtoread = $user->rights->requestmanager->lire;
//    $permtodelete = $user->rights->requestmanager->supprimer;
//    $uploaddir = $conf->requestmanager->multidir_output[$conf->entity];
//    include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}


/*
 * View
 */

$now = dol_now();

// last view date
$user->fetch_optionals();
$lists_follow_last_date = isset($user->array_options['options_rm_last_check_follow_list_date']) ? $user->array_options['options_rm_last_check_follow_list_date'] : '';
if (is_string($lists_follow_last_date)) $lists_follow_last_date = strtotime($lists_follow_last_date);

// save last view date of this page
$user->array_options['options_rm_last_check_follow_list_date'] = $now;
$user->updateExtraField('rm_last_check_follow_list_date');

llxHeader('',$langs->trans('RequestManagerListsFollowTitle'));

$form                       = new Form($db);
$usergroup_static           = new UserGroup($db);
$objectstatic               = new RequestManager($db);
$userstatic                 = new User($db);
$societestatic_origin       = new Societe($db);
$societestatic              = new Societe($db);
$societestatic_benefactor   = new Societe($db);
$societestatic_watcher      = new Societe($db);

$title = $langs->trans('RequestManagerListsFollowTitle');

$num = '';
$nbtotalofrecords = '';
$limit = -1;

//$arrayofselected = is_array($toselect) ? $toselect : array();

$param = '';
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage=' . urlencode($contextpage);
if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit=' . urlencode($limit);
//if ($sall) $param .= '&sall=' . urlencode($sall);
if ($search_ref) $param .= '&search_ref=' . urlencode($search_ref);
if ($search_ref_ext) $param .= '&search_ref_ext=' . urlencode($search_ref_ext);
if ($search_type) $param .= '&search_type=' . urlencode($search_type);
if ($search_category) $param .= '&search_category=' . urlencode($search_category);
if ($search_label) $param .= '&search_label=' . urlencode($search_label);
if ($search_thridparty_origin) $param .= '&search_thridparty_origin=' . urlencode($search_thridparty_origin);
if ($search_thridparty) $param .= '&search_thridparty=' . urlencode($search_thridparty);
if ($search_thridparty_benefactor) $param .= '&search_thridparty_benefactor=' . urlencode($search_thridparty_benefactor);
if ($search_thridparty_watcher) $param .= '&search_thridparty_watcher=' . urlencode($search_thridparty_watcher);
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
if (count($search_status_det)) $param .= '&search_status_det=' . urlencode($search_status_det);
if ($status_type !== '' && $status_type != -1) $param .= '&status_type=' . urlencode($status_type);
if ($optioncss != '') $param .= '&optioncss=' . urlencode($optioncss);

// Add $param from extra fields
foreach ($search_array_options as $key => $val) {
    $crit = $val;
    $tmpkey = preg_replace('/search_options_/', '', $key);
    if ($val != '') $param .= '&search_options_' . $tmpkey . '=' . urlencode($val);
}

// List of mass actions available
$arrayofmassactions = array();
//if ($user->rights->requestmanager->supprimer) $arrayofmassactions['delete'] = $langs->trans("Delete");
$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

// Lignes des champs de filtre
print '<form method="POST" id="searchFormList" action="' . $_SERVER["PHP_SELF"] . '">';
if ($optioncss != '') print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
print '<input type="hidden" name="page" value="' . $page . '">';
//if ($status_type != '' && $status_type != -1) print '<input type="hidden" name="status_type" value="' . dol_escape_htmltag($status_type) . '">';

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'requestmanager@requestmanager', 0, '', '', $limit);

$i = 0;

$moreforfilter = '';

//$moreforfilter.='<div class="divsearchfield">';
//$moreforfilter.=$langs->trans('RequestManagerType'). ': ';
//$moreforfilter.=$formrequestmanager->select_type(null,  $search_type, 'search_type', 1, 0, array(), 0, 0, ' minwidth200', ' multiple');
//$moreforfilter.='</div>';

$moreforfilter.='<div class="divsearchfield">';
$moreforfilter.=$langs->trans('Status'). ': ';
$statut_array = array();
$rmtypedictionary = Dictionary::getDictionary($db, 'requestmanager', 'requestmanagerrequesttype');
$rmtypedictionary->fetch_lines();
$rmstatusdictionary = Dictionary::getDictionary($db, 'requestmanager', 'requestmanagerstatus');
$rmstatusdictionary->fetch_lines();
$unknown_label = $langs->trans('Unknown') . ': ';
foreach ($rmstatusdictionary->lines as $line) {
    $type_list = is_array($line->fields['request_type']) ? $line->fields['request_type'] : (is_string($line->fields['request_type']) ? explode(',', $line->fields['request_type']) : array());
    $toprint = array();
    foreach ($type_list as $type_id) {
        $toprint[] = isset($rmtypedictionary->lines[$type_id]) ? $rmtypedictionary->lines[$type_id]->fields['label'] : $unknown_label . $type_id;
    }
    $statut_array[$line->id] = $line->fields['label'] . (count($toprint) > 0 ? ' ( ' . implode(', ', $toprint) . ' )' : '');
}
$moreforfilter.=$form->multiselectarray('search_status_det',  $statut_array, $search_status_det, 0, 0, ' minwidth300');
$moreforfilter.='</div>';

$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters);    // Note that $action and $object may have been modified by hook
if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
else $moreforfilter = $hookmanager->resPrint;

if (!empty($moreforfilter)) {
    print '<div class="liste_titre liste_titre_bydiv centpercent">';
    print $moreforfilter;
    print '</div>';
}

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);    // This also change content of $arrayfields
if ($massactionbutton) $selectedfields .= $form->showCheckAddButtons('checkforselect', 1);

print '<div class="div-table-responsive">';
print '<table class="tagtable liste' . ($moreforfilter ? " listwithfilterbefore" : "") . '">' . "\n";

print '<tr class="liste_titre_filter">';
// Ref
if (!empty($arrayfields['rm.ref']['checked'])) {
    print '<td class="liste_titre">';
    print '<input class="flat" size="6" type="text" name="search_ref" value="' . dol_escape_htmltag($search_ref) . '">';
    print '</td>';
}
//External Ref
if (!empty($arrayfields['rm.ref_ext']['checked'])) {
    print '<td class="liste_titre">';
    print '<input class="flat" size="6" type="text" name="search_ref_ext" value="' . dol_escape_htmltag($search_ref_ext) . '">';
    print '</td>';
}
// Type
if (!empty($arrayfields['rm.fk_type']['checked'])) {
    print '<td class="liste_titre">';
    print '<input class="flat" size="6" type="text" name="search_type" value="' . dol_escape_htmltag($search_type) . '">';
    print '</td>';
}
// Category
if (!empty($arrayfields['rm.fk_category']['checked'])) {
    print '<td class="liste_titre">';
    print '<input class="flat" size="6" type="text" name="search_category" value="' . dol_escape_htmltag($search_category) . '">';
    print '</td>';
}
// Label
if (!empty($arrayfields['rm.label']['checked'])) {
    print '<td class="liste_titre">';
    print '<input class="flat" size="6" type="text" name="search_label" value="' . dol_escape_htmltag($search_label) . '">';
    print '</td>';
}
// Thridparty Origin
if (!empty($arrayfields['rm.fk_soc_origin']['checked'])) {
    print '<td class="liste_titre">';
    print '<input class="flat" size="6" type="text" name="search_thridparty_origin" value="' . dol_escape_htmltag($search_thridparty_origin) . '">';
    print '</td>';
}
// Thridparty Bill
if (!empty($arrayfields['rm.fk_soc']['checked'])) {
    print '<td class="liste_titre">';
    print '<input class="flat" size="6" type="text" name="search_thridparty" value="' . dol_escape_htmltag($search_thridparty) . '">';
    print '</td>';
}
// Thridparty Benefactor
if (!empty($arrayfields['rm.fk_soc_benefactor']['checked'])) {
    print '<td class="liste_titre">';
    print '<input class="flat" size="6" type="text" name="search_thridparty_benefactor" value="' . dol_escape_htmltag($search_thridparty_benefactor) . '">';
    print '</td>';
}
// Thridparty Watcher
if (!empty($arrayfields['rm.fk_soc_watcher']['checked'])) {
    print '<td class="liste_titre">';
    print '<input class="flat" size="6" type="text" name="search_thridparty_watcher" value="' . dol_escape_htmltag($search_thridparty_watcher) . '">';
    print '</td>';
}
// Description
if (!empty($arrayfields['rm.description']['checked'])) {
    print '<td class="liste_titre">';
    print '<input class="flat" size="6" type="text" name="search_description" value="' . dol_escape_htmltag($search_description) . '">';
    print '</td>';
}
// Source
if (!empty($arrayfields['rm.fk_source']['checked'])) {
    print '<td class="liste_titre">';
    print '<input class="flat" size="6" type="text" name="search_source" value="' . dol_escape_htmltag($search_source) . '">';
    print '</td>';
}
// Urgency
if (!empty($arrayfields['rm.fk_urgency']['checked'])) {
    print '<td class="liste_titre">';
    print '<input class="flat" size="6" type="text" name="search_urgency" value="' . dol_escape_htmltag($search_urgency) . '">';
    print '</td>';
}
// Impact
if (!empty($arrayfields['rm.fk_impact']['checked'])) {
    print '<td class="liste_titre">';
    print '<input class="flat" size="6" type="text" name="search_impact" value="' . dol_escape_htmltag($search_impact) . '">';
    print '</td>';
}
// Priority
if (!empty($arrayfields['rm.fk_priority']['checked'])) {
    print '<td class="liste_titre">';
    print '<input class="flat" size="6" type="text" name="search_priority" value="' . dol_escape_htmltag($search_priority) . '">';
    print '</td>';
}
// Duration
if (!empty($arrayfields['rm.duration']['checked'])) {
    print '<td class="liste_titre">';
    print '<input class="flat" size="6" type="text" name="search_duration" value="' . dol_escape_htmltag($search_duration) . '">';
    print '</td>';
}
// Date Operation
if (!empty($arrayfields['rm.date_operation']['checked'])) {
    print '<td class="liste_titre" align="center">';
    print '<input class="flat" size="6" type="text" name="search_date_operation" value="' . dol_escape_htmltag($search_date_operation) . '">';
    print '</td>';
}
// Date Deadline
if (!empty($arrayfields['rm.date_deadline']['checked'])) {
    print '<td class="liste_titre" align="center">';
    print '<input class="flat" size="6" type="text" name="search_date_deadline" value="' . dol_escape_htmltag($search_date_deadline) . '">';
    print '</td>';
}
// Notification requesters
if (!empty($arrayfields['rm.notify_requester_by_email']['checked'])) {
    print '<td class="liste_titre" align="center">';
    print $form->selectyesno('search_notify_assigned_by_email', $search_notify_assigned_by_email, 1, 0, 1);
    print '</td>';
}
// Notification watchers
if (!empty($arrayfields['rm.notify_watcher_by_email']['checked'])) {
    print '<td class="liste_titre" align="center">';
    print $form->selectyesno('search_notify_assigned_by_email', $search_notify_assigned_by_email, 1, 0, 1);
    print '</td>';
}
// Assigned user
if (!empty($arrayfields['assigned_users']['checked'])) {
    print '<td class="liste_titre">';
    print '<input class="flat" size="6" type="text" name="search_assigned_user" value="' . dol_escape_htmltag($search_assigned_user) . '">';
    print '</td>';
}
// Assigned usergroup
if (!empty($arrayfields['assigned_usergroups']['checked'])) {
    print '<td class="liste_titre">';
    print '<input class="flat" size="6" type="text" name="search_assigned_usergroup" value="' . dol_escape_htmltag($search_assigned_usergroup) . '">';
    print '</td>';
}
// Notification assigned
if (!empty($arrayfields['rm.notify_assigned_by_email']['checked'])) {
    print '<td class="liste_titre" align="center">';
    print $form->selectyesno('search_notify_assigned_by_email', $search_notify_assigned_by_email, 1, 0, 1);
    print '</td>';
}
// User resolved
if (!empty($arrayfields['rm.fk_user_resolved']['checked'])) {
    print '<td class="liste_titre">';
    print '<input class="flat" size="6" type="text" name="search_user_resolved" value="' . dol_escape_htmltag($search_user_resolved) . '">';
    print '</td>';
}
// User closed
if (!empty($arrayfields['rm.fk_user_closed']['checked'])) {
    print '<td class="liste_titre">';
    print '<input class="flat" size="6" type="text" name="search_user_cloture" value="' . dol_escape_htmltag($search_user_cloture) . '">';
    print '</td>';
}
// Date resolved
if (!empty($arrayfields['rm.date_resolved']['checked'])) {
    print '<td class="liste_titre" align="center">';
    print '<input class="flat" size="6" type="text" name="search_date_resolved" value="' . dol_escape_htmltag($search_date_resolved) . '">';
    print '</td>';
}
// Date closed
if (!empty($arrayfields['rm.date_cloture']['checked'])) {
    print '<td class="liste_titre" align="center">';
    print '<input class="flat" size="6" type="text" name="search_date_cloture" value="' . dol_escape_htmltag($search_date_cloture) . '">';
    print '</td>';
}
// Author
if (!empty($arrayfields['rm.fk_user_author']['checked'])) {
    print '<td class="liste_titre">';
    print '<input class="flat" size="6" type="text" name="search_user_author" value="' . dol_escape_htmltag($search_user_author) . '">';
    print '</td>';
}
// Modified by
if (!empty($arrayfields['rm.fk_user_modif']['checked'])) {
    print '<td class="liste_titre">';
    print '<input class="flat" size="6" type="text" name="search_user_modification" value="' . dol_escape_htmltag($search_user_modification) . '">';
    print '</td>';
}
// Extra fields
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) {
    foreach ($extrafields->attribute_label as $key => $val) {
        if (!empty($arrayfields["ef." . $key]['checked'])) {
            $align = $extrafields->getAlignFlag($key);
            $typeofextrafield = $extrafields->attribute_type[$key];
            print '<td class="liste_titre' . ($align ? ' ' . $align : '') . '">';
            if (in_array($typeofextrafield, array('varchar', 'int', 'double', 'select'))) {
                $crit = $val;
                $tmpkey = preg_replace('/search_options_/', '', $key);
                $searchclass = '';
                if (in_array($typeofextrafield, array('varchar', 'select'))) $searchclass = 'searchstring';
                if (in_array($typeofextrafield, array('int', 'double'))) $searchclass = 'searchnum';
                print '<input class="flat' . ($searchclass ? ' ' . $searchclass : '') . '" size="4" type="text" name="search_options_' . $tmpkey . '" value="' . dol_escape_htmltag($search_array_options['search_options_' . $tmpkey]) . '">';
            }
            print '</td>';
        }
    }
}
// Fields from hook
$parameters = array('arrayfields' => $arrayfields);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
// Date creation
if (!empty($arrayfields['rm.datec']['checked'])) {
    print '<td class="liste_titre" align="center">';
    print '<input class="flat" size="6" type="text" name="search_date_creation" value="' . dol_escape_htmltag($search_date_creation) . '">';
    print '</td>';
}
// Date modification
if (!empty($arrayfields['rm.tms']['checked'])) {
    print '<td class="liste_titre" align="center">';
    print '<input class="flat" size="6" type="text" name="search_date_modification" value="' . dol_escape_htmltag($search_date_modification) . '">';
    print '</td>';
}
// Status
if (!empty($arrayfields['rm.fk_status']['checked'])) {
    print '<td class="liste_titre maxwidthonsmartphone" align="right">';
    print $form->selectarray('status_type', array(-2 => $langs->trans('RequestManagerTypeNotResolved'), RequestManager::STATUS_TYPE_INITIAL => $langs->trans('RequestManagerTypeInitial'), RequestManager::STATUS_TYPE_IN_PROGRESS => $langs->trans('RequestManagerTypeInProgress'), RequestManager::STATUS_TYPE_RESOLVED => $langs->trans('RequestManagerTypeResolved'), RequestManager::STATUS_TYPE_CLOSED => $langs->trans('RequestManagerTypeClosed')), $status_type, 1);
    print '</td>';
}
// Action column
print '<td class="liste_titre" align="middle">';
$searchpicto = $form->showFilterButtons();
print $searchpicto;
print '</td>';

print "</tr>\n";

// Fields title
$nbCol = 0;
print '<tr class="liste_titre">';
if (!empty($arrayfields['rm.ref']['checked'])) { print_liste_field_titre($arrayfields['rm.ref']['label'], $_SERVER["PHP_SELF"], 'rm.ref', '', $param, '', $sortfield, $sortorder); $nbCol++; }
if (!empty($arrayfields['rm.ref_ext']['checked'])) { print_liste_field_titre($arrayfields['rm.ref_ext']['label'], $_SERVER["PHP_SELF"], 'rm.ref_ext', '', $param, '', $sortfield, $sortorder); $nbCol++; }
if (!empty($arrayfields['rm.fk_type']['checked'])) { print_liste_field_titre($arrayfields['rm.fk_type']['label'], $_SERVER["PHP_SELF"], 'crmrt.label', '', $param, '', $sortfield, $sortorder); $nbCol++; }
if (!empty($arrayfields['rm.fk_category']['checked'])) { print_liste_field_titre($arrayfields['rm.fk_category']['label'], $_SERVER["PHP_SELF"], 'crmc.label', '', $param, '', $sortfield, $sortorder); $nbCol++; }
if (!empty($arrayfields['rm.label']['checked'])) { print_liste_field_titre($arrayfields['rm.label']['label'], $_SERVER["PHP_SELF"], 'rm.label', '', $param, '', $sortfield, $sortorder); $nbCol++; }
if (!empty($arrayfields['rm.fk_soc_origin']['checked'])) { print_liste_field_titre($arrayfields['rm.fk_soc_origin']['label'], $_SERVER["PHP_SELF"], 'so.nom', '', $param, '', $sortfield, $sortorder); $nbCol++; }
if (!empty($arrayfields['rm.fk_soc']['checked'])) { print_liste_field_titre($arrayfields['rm.fk_soc']['label'], $_SERVER["PHP_SELF"], 's.nom', '', $param, '', $sortfield, $sortorder); $nbCol++; }
if (!empty($arrayfields['rm.fk_soc_benefactor']['checked'])) { print_liste_field_titre($arrayfields['rm.fk_soc_benefactor']['label'], $_SERVER["PHP_SELF"], 'sb.nom', '', $param, '', $sortfield, $sortorder); $nbCol++; }
if (!empty($arrayfields['rm.fk_soc_watcher']['checked'])) { print_liste_field_titre($arrayfields['rm.fk_soc_watcher']['label'], $_SERVER["PHP_SELF"], 'sw.nom', '', $param, '', $sortfield, $sortorder); $nbCol++; }
if (!empty($arrayfields['rm.description']['checked'])) { print_liste_field_titre($arrayfields['rm.description']['label'], $_SERVER["PHP_SELF"], 'rm.description', '', $param, '', $sortfield, $sortorder); $nbCol++; }
if (!empty($arrayfields['rm.fk_source']['checked'])) { print_liste_field_titre($arrayfields['rm.fk_source']['label'], $_SERVER["PHP_SELF"], 'crms.label', '', $param, '', $sortfield, $sortorder); $nbCol++; }
if (!empty($arrayfields['rm.fk_urgency']['checked'])) { print_liste_field_titre($arrayfields['rm.fk_urgency']['label'], $_SERVER["PHP_SELF"], 'crmu.label', '', $param, '', $sortfield, $sortorder); $nbCol++; }
if (!empty($arrayfields['rm.fk_impact']['checked'])) { print_liste_field_titre($arrayfields['rm.fk_impact']['label'], $_SERVER["PHP_SELF"], 'crmi.label', '', $param, '', $sortfield, $sortorder); $nbCol++; }
if (!empty($arrayfields['rm.fk_priority']['checked'])) { print_liste_field_titre($arrayfields['rm.fk_priority']['label'], $_SERVER["PHP_SELF"], 'crmp.label', '', $param, '', $sortfield, $sortorder); $nbCol++; }
if (!empty($arrayfields['rm.duration']['checked'])) { print_liste_field_titre($arrayfields['rm.duration']['label'], $_SERVER["PHP_SELF"], 'rm.duration', '', $param, '', $sortfield, $sortorder); $nbCol++; }
if (!empty($arrayfields['rm.date_operation']['checked'])) { print_liste_field_titre($arrayfields['rm.date_operation']['label'], $_SERVER["PHP_SELF"], 'rm.date_operation', 'align="center"', $param, '', $sortfield, $sortorder); $nbCol++; }
if (!empty($arrayfields['rm.date_deadline']['checked'])) { print_liste_field_titre($arrayfields['rm.date_deadline']['label'], $_SERVER["PHP_SELF"], 'rm.date_deadline', 'align="center"', $param, '', $sortfield, $sortorder); $nbCol++; }
if (!empty($arrayfields['rm.notify_requester_by_email']['checked'])) { print_liste_field_titre($arrayfields['rm.notify_requester_by_email']['label'], $_SERVER["PHP_SELF"], 'rm.notify_requester_by_email', 'align="center"', $param, '', $sortfield, $sortorder); $nbCol++; }
if (!empty($arrayfields['rm.notify_watcher_by_email']['checked'])) { print_liste_field_titre($arrayfields['rm.notify_watcher_by_email']['label'], $_SERVER["PHP_SELF"], 'rm.notify_watcher_by_email', 'align="center"', $param, '', $sortfield, $sortorder); $nbCol++; }
if (!empty($arrayfields['assigned_users']['checked'])) { print_liste_field_titre($arrayfields['assigned_users']['label'], $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder); $nbCol++; }
if (!empty($arrayfields['assigned_usergroups']['checked'])) { print_liste_field_titre($arrayfields['assigned_usergroups']['label'], $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder); $nbCol++; }
if (!empty($arrayfields['rm.notify_assigned_by_email']['checked'])) { print_liste_field_titre($arrayfields['rm.notify_assigned_by_email']['label'], $_SERVER["PHP_SELF"], 'rm.notify_assigned_by_email', 'align="center"', $param, '', $sortfield, $sortorder); $nbCol++; }
if (!empty($arrayfields['rm.fk_user_resolved']['checked'])) { print_liste_field_titre($arrayfields['rm.fk_user_resolved']['label'], $_SERVER["PHP_SELF"], 'ur.lastname', '', $param, '', $sortfield, $sortorder); $nbCol++; }
if (!empty($arrayfields['rm.fk_user_closed']['checked'])) { print_liste_field_titre($arrayfields['rm.fk_user_closed']['label'], $_SERVER["PHP_SELF"], 'uc.lastname', '', $param, '', $sortfield, $sortorder); $nbCol++; }
if (!empty($arrayfields['rm.date_resolved']['checked'])) { print_liste_field_titre($arrayfields['rm.date_resolved']['label'], $_SERVER["PHP_SELF"], 'rm.date_resolved', 'align="center"', $param, '', $sortfield, $sortorder); $nbCol++; }
if (!empty($arrayfields['rm.date_cloture']['checked'])) { print_liste_field_titre($arrayfields['rm.date_cloture']['label'], $_SERVER["PHP_SELF"], 'rm.date_cloture', 'align="center"', $param, '', $sortfield, $sortorder); $nbCol++; }
if (!empty($arrayfields['rm.fk_user_author']['checked'])) { print_liste_field_titre($arrayfields['rm.fk_user_author']['label'], $_SERVER["PHP_SELF"], 'ua.lastname', '', $param, '', $sortfield, $sortorder); $nbCol++; }
if (!empty($arrayfields['rm.fk_user_modif']['checked'])) { print_liste_field_titre($arrayfields['rm.fk_user_modif']['label'], $_SERVER["PHP_SELF"], 'um.lastname', '', $param, '', $sortfield, $sortorder); $nbCol++; }
// Extra fields
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) {
    foreach ($extrafields->attribute_label as $key => $val) {
        if (!empty($arrayfields["ef." . $key]['checked'])) {
            $align = $extrafields->getAlignFlag($key);
            $sortonfield = "ef." . $key;
            if (!empty($extrafields->attribute_computed[$key])) $sortonfield = '';
            print_liste_field_titre($extralabels[$key], $_SERVER["PHP_SELF"], $sortonfield, "", $param, ($align ? 'align="' . $align . '"' : ''), $sortfield, $sortorder);
            $nbCol++;
        }
    }
}
// Hook fields
$parameters = array('nbCol' => &$nbCol, 'arrayfields' => &$arrayfields);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
if (!empty($arrayfields['rm.datec']['checked'])) { print_liste_field_titre($arrayfields['rm.datec']['label'], $_SERVER["PHP_SELF"], "rm.datec", "", $param, 'align="center" class="nowrap"', $sortfield, $sortorder); $nbCol++; }
if (!empty($arrayfields['rm.tms']['checked'])) { print_liste_field_titre($arrayfields['rm.tms']['label'], $_SERVER["PHP_SELF"], "rm.tms", "", $param, 'align="center" class="nowrap"', $sortfield, $sortorder); $nbCol++; }
if (!empty($arrayfields['rm.fk_status']['checked'])) { print_liste_field_titre($arrayfields['rm.fk_status']['label'], $_SERVER["PHP_SELF"], "crmst.label", "", $param, 'align="right"', $sortfield, $sortorder); $nbCol++; }
print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', $sortfield, $sortorder, 'maxwidthsearch '); $nbCol++;
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

// Search join filters
$sqlJoinFilterSearch = '';
if ($search_assigned_user || $search_assigned_usergroup) {
    $sqlJoinFilterSearch .= ' INNER JOIN (';
    $sqlJoinFilterSearch .= '   SELECT rm.rowid';
    $sqlJoinFilterSearch .= '   FROM ' . MAIN_DB_PREFIX . 'requestmanager as rm';
    $sqlJoinFilterSearch .= '   LEFT JOIN ' . MAIN_DB_PREFIX . 'requestmanager_assigned_user as rmau ON rmau.fk_requestmanager = rm.rowid';
    $sqlJoinFilterSearch .= '   LEFT JOIN ' . MAIN_DB_PREFIX . 'requestmanager_assigned_usergroup as rmaug ON rmaug.fk_requestmanager = rm.rowid';
    $sqlJoinFilterSearch .= '   LEFT JOIN ' . MAIN_DB_PREFIX . 'user as uas ON uas.rowid = rmau.fk_user';
    $sqlJoinFilterSearch .= '   LEFT JOIN ' . MAIN_DB_PREFIX . 'usergroup as uga ON uga.rowid = rmaug.fk_usergroup';
    $sqlJoinFilterSearch .= '   WHERE rm.entity IN (' . getEntity('requestmanager') . ')';
    if ($search_assigned_user) $sqlJoinFilterSearch .= natural_search(array('uas.firstname', 'uas.lastname'), $search_assigned_user);
    if ($search_assigned_usergroup) $sqlJoinFilterSearch .= natural_search('uga.nom', $search_assigned_usergroup);
    $sqlJoinFilterSearch .= '   GROUP BY rm.rowid';
    $sqlJoinFilterSearch .= ' ) as search_assigned ON search_assigned.rowid = rm.rowid';
}

// Search filters
$sqlFilterSearch = '';
if ($search_ref)  $sqlFilterSearch.= natural_search('rm.ref', $search_ref);
if ($search_ref_ext)  $sqlFilterSearch.= natural_search('rm.ref_ext', $search_ref_ext);
if ($search_type)  $sqlFilterSearch.= natural_search('crmrt.label', $search_type);
if ($search_category)  $sqlFilterSearch.= natural_search('crmc.label', $search_category);
if ($search_label)  $sqlFilterSearch.= natural_search('rm.label', $search_label);
if ($search_thridparty_origin)  $sqlFilterSearch.= natural_search('so.nom', $search_thridparty_origin);
if ($search_thridparty)  $sqlFilterSearch.= natural_search('s.nom', $search_thridparty);
if ($search_thridparty_benefactor)  $sqlFilterSearch.= natural_search('sb.nom', $search_thridparty_benefactor);
if ($search_thridparty_watcher)  $sqlFilterSearch.= natural_search('sw.nom', $search_thridparty_watcher);
if ($search_source)  $sqlFilterSearch.= natural_search('crms.label', $search_source);
if ($search_urgency)  $sqlFilterSearch.= natural_search('crmu.label', $search_urgency);
if ($search_impact)  $sqlFilterSearch.= natural_search('crmi.label', $search_impact);
if ($search_priority)  $sqlFilterSearch.= natural_search('crmp.label', $search_priority);
if ($search_duration)  $sqlFilterSearch.= natural_search('rm.duration', $search_duration, 1);
if ($search_date_operation)  $sqlFilterSearch.= natural_search('rm.date_operation', $search_date_operation, 1);
if ($search_date_deadline)  $sqlFilterSearch.= natural_search('rm.date_deadline', $search_date_deadline, 1);
if ($search_notify_requester_by_email >= 0) $sqlFilterSearch.= ' AND rm.notify_requester_by_email = ' . $search_notify_requester_by_email;
if ($search_notify_watcher_by_email >= 0) $sqlFilterSearch.= ' AND rm.notify_watcher_by_email = ' . $search_notify_watcher_by_email;
if ($search_notify_assigned_by_email >= 0) $sqlFilterSearch.= ' AND rm.notify_assigned_by_email = ' . $search_notify_assigned_by_email;
if ($search_description)  $sqlFilterSearch.= natural_search('rm.description', $search_description);
if ($search_date_resolved)  $sqlFilterSearch.= natural_search('rm.date_resolved', $search_date_resolved, 1);
if ($search_date_cloture)  $sqlFilterSearch.= natural_search('rm.date_closed', $search_date_cloture, 1);
if ($search_user_resolved)  $sqlFilterSearch.= natural_search(array('ur.firstname', 'ur.lastname'), $search_user_resolved);
if ($search_user_cloture)  $sqlFilterSearch.= natural_search(array('uc.firstname', 'uc.lastname'), $search_user_cloture);
if ($search_date_creation)  $sqlFilterSearch.= natural_search('rm.datec', $search_date_creation, 1);
if ($search_date_modification)  $sqlFilterSearch.= natural_search('rm.tms', $search_date_modification, 1);
if ($search_user_author)  $sqlFilterSearch.= natural_search(array('ua.firstname', 'ua.lastname'), $search_user_author);
if ($search_user_modification)  $sqlFilterSearch.= natural_search(array('um.firstname', 'um.lastname'), $search_user_modification);
if (count($search_status_det))  $sqlFilterSearch.= natural_search(array('rm.fk_status'), implode(',', $search_status_det), 2);
if ($status_type >= 0)  $sqlFilterSearch.= ' AND crmst.type = ' . $status_type;
elseif ($status_type == -2)  $sqlFilterSearch.= ' AND crmst.type IN (' . RequestManager::STATUS_TYPE_INITIAL . ', ' . RequestManager::STATUS_TYPE_IN_PROGRESS . ')';
//if ($sall) {
//    $sqlFilterSearch .= natural_search(array_keys($fieldstosearchall), $sall);
//}
// Add where from extra fields
foreach ($search_array_options as $key => $val)
{
    $crit=$val;
    $tmpkey=preg_replace('/search_options_/','',$key);
    $typ=$extrafields->attribute_type[$tmpkey];
    $mode=0;
    if (in_array($typ, array('int','double','real'))) $mode=1;    							// Search on a numeric
    if (in_array($typ, array('sellist')) && $crit != '0' && $crit != '-1') $mode=2;    		// Search on a foreign key int
    if ($crit != '' && (! in_array($typ, array('select','sellist')) || $crit != '0'))
    {
        $sqlFilterSearch .= natural_search('ef.'.$tmpkey, $crit, $mode);
    }
}
// Add where from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListWhere',$parameters);    // Note that $action and $object may have been modified by hook
$sqlFilterSearch.=$hookmanager->resPrint;


// 1 - List of requests in progress assigned to me
FormRequestManager::listsFollowPrintListFrom($db, $arrayfields, $objectstatic, $societestatic_origin, $societestatic, $societestatic_benefactor, $societestatic_watcher, $userstatic,
    $sqlJoinAssignedFilterBegin . $sqlAssignedSubSelectBegin . $sqlFilterAssignedToMe . $sqlJoinAssignedFilterEnd . $sqlJoinFilterSearch,
    $sqlFilterInProgress . $sqlFilterNotInFuture . $sqlFilterSearch,
    $sortfield, $sortorder, 'RequestManagerListsFollowMyRequest', $nbCol, $lists_follow_last_date);

// 2 - List of requests in progress assigned to my group(s) and not to me
FormRequestManager::listsFollowPrintListFrom($db, $arrayfields, $objectstatic, $societestatic_origin, $societestatic, $societestatic_benefactor, $societestatic_watcher, $userstatic,
    $sqlJoinAssignedFilterBegin . $sqlAssignedSubSelectBegin . $sqlFilterAssignedToMyGroups . $sqlJoinAssignedFilterEnd .
    $sqlJoinNotAssignedFilterBegin . $sqlAssignedSubSelectBegin . $sqlFilterAssignedToMe . $sqlJoinNotAssignedFilterEnd . $sqlJoinFilterSearch,
    $sqlFilterInProgress . $sqlFilterNotInFuture . $sqlFilterNotAssigned . $sqlFilterSearch,
    $sortfield, $sortorder, 'RequestManagerListsFollowMyGroupRequest', $nbCol, $lists_follow_last_date);

// 3 - List of requests in progress not assigned to my group(s) and not assigned to me
FormRequestManager::listsFollowPrintListFrom($db, $arrayfields, $objectstatic, $societestatic_origin, $societestatic, $societestatic_benefactor, $societestatic_watcher, $userstatic,
    $sqlJoinActionComm . $sqlJoinNotAssignedFilterBegin . $sqlAssignedSubSelectBegin . $sqlFilterAssignedToMeOrMyGroups . $sqlJoinNotAssignedFilterEnd .
    $sqlJoinFilterSearch,
    $sqlFilterInProgress . $sqlFilterActionCommAssignedToMe . $sqlFilterNotAssigned . $sqlFilterSearch,
    $sortfield, $sortorder, 'RequestManagerListsFollowLinkToMyEvent', $nbCol, $lists_follow_last_date);

// 4 - List of requests in future assigned to me
FormRequestManager::listsFollowPrintListFrom($db, $arrayfields, $objectstatic, $societestatic_origin, $societestatic, $societestatic_benefactor, $societestatic_watcher, $userstatic,
    $sqlJoinAssignedFilterBegin . $sqlAssignedSubSelectBegin . $sqlFilterAssignedToMe . $sqlJoinAssignedFilterEnd . $sqlJoinFilterSearch,
    $sqlFilterInProgress . $sqlFilterInFuture . $sqlFilterSearch,
    $sortfield, $sortorder, 'RequestManagerListsFollowMyFutureRequest', $nbCol, $lists_follow_last_date);

// 5 - List request in future assigned to my group(s) and not to me
FormRequestManager::listsFollowPrintListFrom($db, $arrayfields, $objectstatic, $societestatic_origin, $societestatic, $societestatic_benefactor, $societestatic_watcher, $userstatic,
    $sqlJoinAssignedFilterBegin . $sqlAssignedSubSelectBegin . $sqlFilterAssignedToMyGroups . $sqlJoinAssignedFilterEnd .
    $sqlJoinNotAssignedFilterBegin . $sqlAssignedSubSelectBegin . $sqlFilterAssignedToMe . $sqlJoinNotAssignedFilterEnd . $sqlJoinFilterSearch,
    $sqlFilterInProgress . $sqlFilterInFuture . $sqlFilterNotAssigned . $sqlFilterSearch,
    $sortfield, $sortorder, 'RequestManagerListsFollowMyFutureGroupRequest', $nbCol, $lists_follow_last_date);


print '</table>' . "\n";
print '</div>' . "\n";
print '</form>' . "\n";


// End of page
llxFooter();
$db->close();
