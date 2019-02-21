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
 *	\file       	htdocs/requestmanager/list.php
 *	\ingroup    	requestmanager
 *	\brief      	Page of request manager list
 */

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include '../main.inc.php';					// to work if your module directory is into dolibarr root htdocs directory
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (! $res && file_exists("../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../dolibarr/htdocs/main.inc.php';     // Used on dev env only
if (! $res && file_exists("../../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../../dolibarr/htdocs/main.inc.php';   // Used on dev env only
if (! $res) die("Include of main fails");
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
dol_include_once('/requestmanager/class/requestmanager.class.php');
dol_include_once('/requestmanager/class/html.formrequestmanager.class.php');
dol_include_once('/advancedictionaries/class/dictionary.class.php');
dol_include_once('/advancedictionaries/class/html.formdictionary.class.php');
dol_include_once('/requestmanager/lib/requestmanager.lib.php');

$langs->load('requestmanager@requestmanager');

$action=GETPOST('action','alpha');
$massaction=GETPOST('massaction','alpha');
$confirm=GETPOST('confirm','alpha');
$toselect = GETPOST('toselect', 'array');
$request_id = GETPOST('request_id', 'int');

$socid=GETPOST('socid','int');

$sall=GETPOST('sall', 'alphanohtml');
$search_ref=GETPOST('search_ref','alpha');
$search_ref_ext=GETPOST('search_ref_ext','alpha');
$search_type=GETPOST('search_type','alpha');
$search_category=GETPOST('search_category','alpha');
$search_label=GETPOST('search_label','alpha');
$search_thridparty_origin=GETPOST('search_thridparty_origin','alpha');
$search_thridparty=GETPOST('search_thridparty','alpha');
$search_thridparty_benefactor=GETPOST('search_thridparty_benefactor','alpha');
$search_thridparty_watcher=GETPOST('search_thridparty_watcher','alpha');
$search_source=GETPOST('search_source','alpha');
$search_urgency=GETPOST('search_urgency','alpha');
$search_impact=GETPOST('search_impact','alpha');
$search_priority=GETPOST('search_priority','alpha');
$search_duration=GETPOST('search_duration','alpha');
$search_date_operation=GETPOST('search_date_operation','alpha');
$search_date_deadline=GETPOST('search_date_deadline','alpha');
$search_notify_requester_by_email=GETPOST('search_notify_requester_by_email','int');
$search_notify_watcher_by_email=GETPOST('search_notify_watcher_by_email','int');
$search_assigned_user=GETPOST('search_assigned_user','alpha');
$search_assigned_usergroup=GETPOST('search_assigned_usergroup','alpha');
$search_notify_assigned_by_email=GETPOST('search_notify_assigned_by_email','int');
$search_description=GETPOST('search_description','alpha');
$search_tags=GETPOST('search_tags','array');
$search_reason_resolution=GETPOST('search_reason_resolution','int');
$search_reason_resolution_details=GETPOST('search_reason_resolution_details','alpha');
$search_date_resolved=GETPOST('search_date_resolved','alpha');
$search_date_cloture=GETPOST('search_date_cloture','alpha');
$search_user_resolved=GETPOST('search_user_resolved','alpha');
$search_user_cloture=GETPOST('search_user_cloture','alpha');
$search_date_creation=GETPOST('search_date_creation','alpha');
$search_date_modification=GETPOST('search_date_modification','alpha');
$search_user_author=GETPOST('search_user_author','alpha');
$search_user_modification=GETPOST('search_user_modification','alpha');
$search_status_det=GETPOST('search_status_det','array');
$search_in_charge=GETPOST('search_in_charge','array');
$my_list=GETPOST('mylist','int');
$not_assigned=GETPOST('notassigned','int');
$planning=GETPOST('planning','int');
$status_type=GETPOST('status_type','int');
$optioncss = GETPOST('optioncss','alpha');

$limit = GETPOST('limit')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield='rm.ref';
if (! $sortorder) $sortorder='DESC';

// Security check
if (! empty($user->societe_id))	$socid=$user->societe_id;
if (! empty($socid)) {
    $result = restrictedArea($user, 'societe', $socid, '&societe');
}
$result = restrictedArea($user, 'requestmanager', '', '');
if ($planning && !$user->rights->requestmanager->planning->lire) {
    accessforbidden();
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$contextpage='requestmanagerlist';
if ($planning) {
    $contextpage='requestmanagerplanninglist';
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array($contextpage));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('requestmanager');
$search_array_options=$extrafields->getOptionalsFromPost($extralabels,'','search_');

// Get planning info
if ($planning) {
    $request_types_planned = !empty($conf->global->REQUESTMANAGER_PLANNING_REQUEST_TYPE) ? explode(',', $conf->global->REQUESTMANAGER_PLANNING_REQUEST_TYPE) : array();
    dol_include_once('/advancedictionaries/class/dictionary.class.php');
    $requestmanagerrequesttype = Dictionary::getDictionary($db, 'requestmanager', 'requestmanagerrequesttype');
    $requestmanagerrequesttype->fetch_lines(1);
}

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
    'rm.ref'=>'Ref',
    'rm.ref_ext'=>'RequestManagerExternalReference',
    's.nom'=>"RequestManagerThirdParty",
    'rm.label'=>'RequestManagerLabel',
);

$arrayfields = array(
    'rm.ref' => array('label' => $langs->trans("Ref"), 'checked' => 1),
    'rm.ref_ext' => array('label' => $langs->trans("RequestManagerExternalReferenceShort"), 'checked' => 1),
    'rm.fk_type' => array('label' => $langs->trans("RequestManagerType"), 'checked' => 1),
    'rm.fk_category' => array('label' => $langs->trans("RequestManagerCategory"), 'checked' => 1),
    'rm.label' => array('label' => $langs->trans("RequestManagerLabel"), 'checked' => 1),
    'rm.fk_soc_origin' => array('label' => $langs->trans("RequestManagerThirdPartyOrigin"), 'checked' => 1),
    'rm.fk_soc' => array('label' => $langs->trans("RequestManagerThirdPartyPrincipal"), 'checked' => 1),
    'rm.fk_soc_benefactor' => array('label' => $langs->trans("RequestManagerThirdPartyBenefactor"), 'checked' => 1),
    'rm.fk_soc_watcher' => array('label' => $langs->trans("RequestManagerThirdPartyWatcher"), 'checked' => 0),
    'rm.description' => array('label' => $langs->trans("RequestManagerDescription"), 'checked' => 0),
    'rm.fk_source' => array('label' => $langs->trans("RequestManagerSource"), 'checked' => 0),
    'rm.fk_urgency' => array('label' => $langs->trans("RequestManagerUrgency"), 'checked' => 0),
    'rm.fk_impact' => array('label' => $langs->trans("RequestManagerImpact"), 'checked' => 0),
    'rm.fk_priority' => array('label' => $langs->trans("RequestManagerPriority"), 'checked' => 0),
    'rm.duration' => array('label' => $langs->trans("RequestManagerDuration"), 'checked' => 0),
    'rm.date_operation' => array('label' => $langs->trans("RequestManagerOperation"), 'checked' => 0),
    'rm.date_deadline' => array('label' => $langs->trans("RequestManagerDeadline"), 'checked' => 0),
    'rm.notify_requester_by_email' => array('label' => $langs->trans("RequestManagerRequesterNotification"), 'checked' => 0),
    'rm.notify_watcher_by_email' => array('label' => $langs->trans("RequestManagerWatcherNotification"), 'checked' => 0),
    'users_in_charge' => array('label' => $langs->trans("RequestManagerPlanningUsersInCharge"), 'checked' => 1, 'enabled'=> ($planning ? 1 : 0)),
    'assigned_users' => array('label' => $langs->trans("RequestManagerAssignedUsers"), 'checked' => 1),
    'assigned_usergroups' => array('label' => $langs->trans("RequestManagerAssignedUserGroups"), 'checked' => 1),
    'rm.notify_assigned_by_email' => array('label' => $langs->trans("RequestManagerAssignedNotification"), 'checked' => 0),
    'rm.fk_reason_resolution' => array('label' => $langs->trans("RequestManagerReasonResolution"), 'checked' => 0),
    'rm.reason_resolution_details' => array('label' => $langs->trans("RequestManagerReasonResolutionDetails"), 'checked' => 0),
    'rm.fk_user_resolved' => array('label' => $langs->trans("RequestManagerResolvedBy"), 'checked' => 0, 'position' => 10),
    'rm.fk_user_closed' => array('label' => $langs->trans("ClosedBy"), 'checked' => 0, 'position' => 10),
    'rm.date_resolved' => array('label' => $langs->trans("RequestManagerDateResolved"), 'checked' => 0, 'position' => 10),
    'rm.date_cloture' => array('label' => $langs->trans("DateClosing"), 'checked' => 0, 'position' => 10),
    'rm.fk_user_author' => array('label' => $langs->trans("Author"), 'checked' => 0, 'position' => 10),
    'rm.fk_user_modif' => array('label' => $langs->trans("ModifiedBy"), 'checked' => 0, 'position' => 10),
    'rm.datec' => array('label' => $langs->trans("DateCreation"), 'checked' => 1, 'position' => 500),
    'rm.tms' => array('label' => $langs->trans("DateModificationShort"), 'checked' => 0, 'position' => 500),
    'rm.fk_status' => array('label' => $langs->trans("Status"), 'checked' => 1, 'position' => 1000),
);
// Extra fields
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
{
    foreach($extrafields->attribute_label as $key => $val)
    {
        $arrayfields["ef.".$key]=array('label'=>$extrafields->attribute_label[$key], 'checked'=>$extrafields->attribute_list[$key], 'position'=>$extrafields->attribute_pos[$key], 'enabled'=>$extrafields->attribute_perms[$key]);
    }
}

$object = new RequestManager($db);	// To be passed as parameter of executeHooks that need


/*
 * Actions
 */

if (GETPOST('cancel')) { $action='list'; $massaction=''; }
if (! GETPOST('confirmmassaction') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction=''; }

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
    $search_tags=array();
    $search_reason_resolution=-1;
    $search_reason_resolution_details='';
    $search_date_resolved='';
    $search_date_cloture='';
    $search_user_resolved='';
    $search_user_cloture='';
    $search_date_creation='';
    $search_date_modification='';
    $search_user_author='';
    $search_user_modification='';
    $status_type=-1;
    $search_in_charge = array();
	$toselect='';
    $search_array_options=array();
    $search_status_det = array();
}
if ($search_notify_requester_by_email === '') $search_notify_requester_by_email = -1;
if ($search_notify_watcher_by_email === '') $search_notify_watcher_by_email = -1;
if ($search_notify_assigned_by_email === '') $search_notify_assigned_by_email = -1;
if ($search_reason_resolution === '') $search_reason_resolution = -1;
if ($status_type === '') $status_type = -1;
if ($planning && count($search_in_charge)) $search_in_charge = array_filter(array_map('trim', $search_in_charge), 'strlen');
if ($planning && empty($search_in_charge) && $my_list) $search_in_charge = array($user->id);
if ($planning && count($search_in_charge) == 1 && in_array($user->id, $search_in_charge)) $my_list = 1;

if (empty($reshook)) {
    if ($planning && $action == 'confirm_planning' && $confirm == 'yes' && $user->rights->requestmanager->planning->manage) {
        $nb_error = 0;
        $date_operation = dol_mktime(GETPOST('operate_datehour', 'int'), GETPOST('operate_datemin', 'int'), 0, GETPOST('operate_datemonth', 'int'), GETPOST('operate_dateday', 'int'), GETPOST('operate_dateyear', 'int'));
        $assigned_user_ids = GETPOST('assigned_user_ids', 'array');
        $request_list = GETPOST('request_list', 'array');
        $old_action = GETPOST('old_action', 'alpha');
        $old_massaction = GETPOST('old_massaction', 'alpha');

        if (empty($date_operation)) {
            setEventMessage($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('RequestManagerOperation')), 'errors');
            $nb_error++;
        }
        if (empty($assigned_user_ids)) {
            setEventMessage($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('RequestManagerAssignedUsers')), 'errors');
            $nb_error++;
        }

        if (!$nb_error && count($request_list) > 0) {
            foreach ($request_list as $r_id) {
                $error = 0;
                $db->begin();

                $request_static = new RequestManager($db);
                if ($request_static->fetch($r_id) <= 0) $error++;
                if (!($conf->global->{'REQUESTMANAGER_PLANNING_REQUEST_STATUS_PLANNED_' . $request_static->fk_type} > 0)) {
                    $request_static->error = $langs->trans('RequestManagerErrorPlanningRequestStatusPlannedNotConfigured');
                    $request_static->errors = array();
                    $error++;
                }
                if (!$error && $request_static->set_status($conf->global->{'REQUESTMANAGER_PLANNING_REQUEST_STATUS_PLANNED_' . $request_static->fk_type}, -1, $user) <= 0) $error++;
                $request_static->date_operation = $date_operation;
                // calculate deadline date with operation date or now and the offset deadline time in minutes
                if (GETPOST('recalculate_date_deadline', 'int') == 1) {
                    dol_include_once('/advancedictionaries/class/dictionary.class.php');
                    $requestManagerStatusDictionaryLine = Dictionary::getDictionaryLine($db, 'requestmanager', 'requestmanagerstatus');
                    $res = $requestManagerStatusDictionaryLine->fetch($object->statut);
                    if ($res > 0) {
                        $deadline_offset = $requestManagerStatusDictionaryLine->fields['deadline'];
                        $now = dol_now();
                        if (isset($deadline_offset) && $deadline_offset > 0) {
                            $object->date_deadline = ($object->date_operation > 0 ? $object->date_operation : $now) + ($deadline_offset * 60);
        //                } elseif (intval($conf->global->REQUESTMANAGER_DEADLINE_TIME_DEFAULT) > 0) {
        //                    $object->date_deadline = ($object->date_operation > 0 ? $object->date_operation : $now) + (intval($conf->global->REQUESTMANAGER_DEADLINE_TIME_DEFAULT) * 60);
                        }
                    } else {
                        setEventMessages($requestManagerStatusDictionaryLine->error, $requestManagerStatusDictionaryLine->errors, 'errors');
                        $error++;
                    }
                }
                $request_static->assigned_user_ids = $assigned_user_ids;
                $request_static->context['rm_planning'] = true;
                if (!$error && $request_static->update($user) <= 0) $error++;

                if (!$error) {
                    $db->commit();
                } else {
                    setEventMessage($langs->trans('RequestManagerErrorForRequest', !empty($request_static->ref) ? $request_static->ref : 'ID: ' . $r_id), 'errors');
                    setEventMessages($request_static->error, $request_static->errors, 'errors');
                    $db->rollback();
                    $nb_error++;
                }
            }
        }

        if ($nb_error) {
            $action = $old_action;
            $massaction = $old_massaction;
        }
    }

    $objectclass = 'RequestManager';
    $objectlabel = 'RequestManagerRequests';
    $permtoread = $user->rights->requestmanager->lire;
    $permtodelete = $user->rights->requestmanager->supprimer;
    $uploaddir = $conf->requestmanager->multidir_output[$conf->entity];
    include DOL_DOCUMENT_ROOT . '/core/actions_massactions.inc.php';
}



/*
 * View
 */

$now=dol_now();

$form = new Form($db);
$formother = new FormOther($db);
$formdictionary = new FormDictionary($db);
$formrequestmanager = new FormRequestManager($db);
$usergroup_static = new UserGroup($db);

$title = $langs->trans('RequestManagerListOfRequests');
if ($planning) {
    $title = $langs->trans('RequestManagerPlanningListOfRequests');
    if ($my_list && in_array($user->id, $search_in_charge)) $title .= ' (' . $langs->trans('RequestManagerPlanningInCharge') . ')';
} else {
    if ($my_list) $title .= ' (' . $langs->trans('RequestManagerMyRequest') . ')';
    if ($not_assigned) $title .= ' (' . $langs->trans('RequestManagerNotAssigned') . ')';
}
if ($socid > 0) {
    $langs->load("companies");
}

$help_url='EN:RequestManager_En|FR:RequestManager_Fr|ES:RequestManager_Es';
llxHeader('', ($socid > 0 ? $langs->trans("ThirdParty") . ' - ' : '') . $title, $help_url, '', 0, 0, $planning ? array('/requestmanager/js/opendsi.js') : array());

if ($socid > 0) {
    /*
     * Affichage onglets
     */
    $societe = new Societe($db);
    $societe->fetch($socid);

    require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
    $head = societe_prepare_head($societe);

    $active_tab = 'rm_request_list';
    if ($planning) $active_tab = 'rm_request_planning_list';
    dol_fiche_head($head, $active_tab, $langs->trans("ThirdParty"), -1, 'company');

    $linkback = '<a href="' . DOL_URL_ROOT . '/societe/list.php?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';

    dol_banner_tab($societe, 'socid', $linkback, ($user->societe_id ? 0 : 1), 'rowid', 'nom');

    $cssclass = 'titlefield';

    print '<div class="fichecenter">';

    print '<div class="underbanner clearboth"></div>';
    print '<table class="border centpercent">';

    if (!empty($conf->global->SOCIETE_USEPREFIX))  // Old not used prefix field
    {
        print '<tr><td class="' . $cssclass . '">' . $langs->trans('Prefix') . '</td><td colspan="3">' . $societe->prefix_comm . '</td></tr>';
    }

    if ($societe->client) {
        print '<tr><td class="' . $cssclass . '">';
        print $langs->trans('CustomerCode') . '</td><td colspan="3">';
        print $societe->code_client;
        if ($societe->check_codeclient() <> 0) print ' <font class="error">(' . $langs->trans("WrongCustomerCode") . ')</font>';
        print '</td></tr>';
    }

    if ($societe->fournisseur) {
        print '<tr><td class="' . $cssclass . '">';
        print $langs->trans('SupplierCode') . '</td><td colspan="3">';
        print $societe->code_fournisseur;
        if ($societe->check_codefournisseur() <> 0) print ' <font class="error">(' . $langs->trans("WrongSupplierCode") . ')</font>';
        print '</td></tr>';
    }

    print "</table>";

    print '</div>';
}

if (empty($planning) || count($request_types_planned) > 0) {
    // Confirm box for the planning
    if ($planning && ($massaction == 'rm_planning' || $action == 'to_plan') && $user->rights->requestmanager->planning->manage) {
        $users_in_charge = array();
        $request_list = $massaction == 'rm_planning' ? $toselect : array($request_id);
        $request_list = array_flip(array_flip($request_list));
        if ($nb_request_selected = count($request_list)) {
            $sql = 'SELECT ugu.fk_user, COUNT(DISTINCT rm.rowid) AS nb_request_in_charge FROM ' . MAIN_DB_PREFIX . 'societe_rm_usergroup_in_charge AS srmugic' .
                ' LEFT JOIN ' . MAIN_DB_PREFIX . 'usergroup_user as ugu ON ugu.fk_usergroup = srmugic.fk_usergroup AND ' . (!empty($conf->multicompany->enabled) && $conf->entity == 1 && $user->admin && !$user->entity ? 'ugu.entity IS NOT NULL' : 'ugu.entity IN (0,' . $conf->entity . ')') .
                ' LEFT JOIN ' . MAIN_DB_PREFIX . 'requestmanager as rm ON (srmugic.fk_soc = rm.fk_soc OR srmugic.fk_soc = rm.fk_soc_benefactor) AND srmugic.fk_c_request_type = rm.fk_type' .
                ' WHERE rm.rowid IN (' . implode(',', $request_list) . ')' .
                ' GROUP BY ugu.fk_user';

            $resql = $db->query($sql);
            if ($resql) {
                while ($obj = $db->fetch_object($resql)) {
                    if ($nb_request_selected == $obj->nb_request_in_charge) {
                        $users_in_charge[] = $obj->fk_user;
                    }
                }
            }

            $date_operation = dol_mktime(GETPOST('operate_datehour', 'int'), GETPOST('operate_datemin', 'int'), 0, GETPOST('operate_datemonth', 'int'), GETPOST('operate_dateday', 'int'), GETPOST('operate_dateyear', 'int'));
            $assigned_user_ids = isset($_POST['operate_datehour']) ? GETPOST('assigned_user_ids', 'array') : $users_in_charge;

            $formquestion = array(
                array('type' => 'date', 'name' => 'operate_date', 'label' => '<span class="fieldrequired">' . $langs->trans("RequestManagerOperation") . '</span>', 'hour' => 1, 'minute' => 1, 'value' => $date_operation > 0 ? $date_operation : -1),
                array('type' => 'other', 'name' => 'assigned_user_ids', 'label' => '<span class="fieldrequired">' . $langs->trans("RequestManagerAssignedUsers") . '</span>', 'value' => $formrequestmanager->multiselect_dolusers(array(), 'assigned_user_ids', null, 0, '', '', 0, 0, 0, '', 0, '', 'minwidth300', 1, 0)),
                array('type' => 'hidden', 'name' => 'old_massaction', 'value' => $massaction),
                array('type' => 'hidden', 'name' => 'old_action', 'value' => $action),
            );

            foreach ($request_list as $r_id) {
                $formquestion[] = array('type' => 'hidden', 'name' => 'request_list[]', 'value' => $r_id);
            }

            $params_array = array_merge($_GET, $_POST);
            $exclude_keys = array('action', 'massaction', 'operate_date', 'operate_datehour', 'operate_datemin', 'operate_datemonth', 'operate_dateday', 'operate_dateyear', 'assigned_user_ids', 'old_massaction', 'old_action', 'request_list');
            foreach ($params_array as $k => $v) {
                if (in_array($k, $exclude_keys)) continue;
                if (is_array($v)) {
                    foreach ($v as $va) {
                        $formquestion[] = array('type' => 'hidden', 'name' => $k . '[]', 'value' => $va);
                    }
                } else {
                    $formquestion[] = array('type' => 'hidden', 'name' => $k, 'value' => $v);
                }
            }

            $params2 = '';
            if ($planning) $params2 .= '&planning=' . urlencode($planning);
            if ($my_list) $params2 .= '&my_list=' . urlencode($my_list);
            if (empty($planning) && $not_assigned) $params2 .= '&not_assigned=' . urlencode($not_assigned);
            if ($socid > 0) $params2 .= '&socid=' . urlencode($socid);
            $params2 = !empty($params2) ? '?' . substr($params2, 1) : '';

            print $formrequestmanager->formconfirm($_SERVER["PHP_SELF"] . $params2, $langs->trans('RequestManagerPlanningRequest'), $langs->trans('RequestManagerConfirmPlanningRequest'), 'confirm_planning', $formquestion, 'yes', 1, 300, 800, 1);

            $users_in_charge = json_encode($users_in_charge);
            $assigned_user_ids = json_encode($assigned_user_ids);
            print <<<SCRIPT
    <script type="text/javascript">
        $(document).ready(function () {
            move_top_select_options('assigned_user_ids', $users_in_charge);
            set_select_options('assigned_user_ids', $assigned_user_ids);
        });
    </script>
SCRIPT;
        }
    }

    $sql = 'SELECT';
    if ($sall) $sql = 'SELECT DISTINCT';
    $sql .= ' rm.rowid, rm.fk_parent, rm.ref, rm.ref_ext,';
    $sql .= ' rm.fk_soc_origin, so.nom as soc_name_origin, so.client as soc_client_origin, so.fournisseur as soc_fournisseur_origin, so.code_client as soc_code_client_origin, so.code_fournisseur as soc_code_fournisseur_origin,';
    $sql .= ' rm.fk_soc, s.nom as soc_name, s.client as soc_client, s.fournisseur as soc_fournisseur, s.code_client as soc_code_client, s.code_fournisseur as soc_code_fournisseur,';
    $sql .= ' rm.fk_soc_benefactor, sb.nom as soc_name_benefactor, sb.client as soc_client_benefactor, sb.fournisseur as soc_fournisseur_benefactor, sb.code_client as soc_code_client_benefactor, sb.code_fournisseur as soc_code_fournisseur_benefactor,';
    $sql .= ' rm.fk_soc_watcher, sw.nom as soc_name_watcher, sw.client as soc_client_watcher, sw.fournisseur as soc_fournisseur_watcher, sw.code_client as soc_code_client_watcher, sw.code_fournisseur as soc_code_fournisseur_watcher,';
    $sql .= ' rm.label, rm.description,';
    $sql .= ' rm.fk_type, crmrt.label as type_label,';
    $sql .= ' rm.fk_category, crmc.label as category_label,';
    $sql .= ' rm.fk_source, crms.label as source_label,';
    $sql .= ' rm.fk_urgency, crmu.label as urgency_label,';
    $sql .= ' rm.fk_impact, crmi.label as impact_label,';
    $sql .= ' rm.fk_priority, crmp.label as priority_label,';
    $sql .= ' rm.notify_requester_by_email, rm.notify_watcher_by_email, rm.notify_assigned_by_email,';
    if ($planning) $sql .= ' GROUP_CONCAT(DISTINCT ugu.fk_user SEPARATOR \',\') as users_in_charge,';
    $sql .= ' GROUP_CONCAT(DISTINCT rmau.fk_user SEPARATOR \',\') as assigned_users,';
    $sql .= ' GROUP_CONCAT(DISTINCT rmaug.fk_usergroup SEPARATOR \',\') as assigned_usergroups,';
    $sql .= ' rm.duration, rm.date_operation, rm.date_deadline, rm.date_resolved, rm.date_closed,';
    $sql .= ' rm.fk_user_resolved, ur.firstname as userresolvedfirstname, ur.lastname as userresolvedlastname, ur.email as userresolvedemail,';
    $sql .= ' rm.fk_user_closed, uc.firstname as userclosedfirstname, uc.lastname as userclosedlastname, uc.email as userclosedemail,';
    $sql .= ' rm.fk_status,';
    $sql .= ' rm.datec, rm.tms,';
    $sql .= ' rm.fk_user_author, ua.firstname as userauthorfirstname, ua.lastname as userauthorlastname, ua.email as userauthoremail,';
    $sql .= ' rm.fk_user_modif, um.firstname as usermodiffirstname, um.lastname as usermodiflastname, um.email as usermodifemail,';
    $sql .= ' crmrr.label AS reason_resolution, rm.reason_resolution_details';
    // Add fields from extrafields
    foreach ($extrafields->attribute_label as $key => $val) $sql .= ($extrafields->attribute_type[$key] != 'separate' ? ",ef." . $key . ' as options_' . $key : '');
    // Add fields from hooks
    $parameters = array();
    $reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters);    // Note that $action and $object may have been modified by hook
    $sql .= $hookmanager->resPrint;
    $sql .= ' FROM ' . MAIN_DB_PREFIX . 'requestmanager as rm';
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_requestmanager_request_type as crmrt on (crmrt.rowid = rm.fk_type)";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_requestmanager_category as crmc on (crmc.rowid = rm.fk_category)";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_requestmanager_source as crms on (crms.rowid = rm.fk_source)";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_requestmanager_urgency as crmu on (crmu.rowid = rm.fk_urgency)";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_requestmanager_impact as crmi on (crmi.rowid = rm.fk_impact)";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_requestmanager_priority as crmp on (crmp.rowid = rm.fk_priority)";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_requestmanager_status as crmst on (crmst.rowid = rm.fk_status)";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_requestmanager_reason_resolution as crmrr on (crmrr.rowid = rm.fk_reason_resolution)";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as so on (so.rowid = rm.fk_soc_origin)";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as s on (s.rowid = rm.fk_soc)";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as sb on (sb.rowid = rm.fk_soc_benefactor)";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as sw on (sw.rowid = rm.fk_soc_watcher)";
    if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "requestmanager_extrafields as ef on (rm.rowid = ef.fk_object)";
    $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'user as ur ON ur.rowid = rm.fk_user_resolved';
    $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'user as uc ON uc.rowid = rm.fk_user_closed';
    $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'user as ua ON ua.rowid = rm.fk_user_author';
    $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'user as um ON um.rowid = rm.fk_user_modif';
    if ($planning) {
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'societe_rm_usergroup_in_charge as srmugic ON (srmugic.fk_soc = rm.fk_soc OR srmugic.fk_soc = rm.fk_soc_benefactor) AND srmugic.fk_c_request_type = rm.fk_type';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'usergroup_user as ugu ON ugu.fk_usergroup = srmugic.fk_usergroup AND ' . (!empty($conf->multicompany->enabled) && $conf->entity == 1 && $user->admin && !$user->entity ? 'ugu.entity IS NOT NULL' : 'ugu.entity IN (0,' . $conf->entity . ')');
    }
    $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'requestmanager_assigned_user as rmau ON rmau.fk_requestmanager = rm.rowid';
    $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'requestmanager_assigned_usergroup as rmaug ON rmaug.fk_requestmanager = rm.rowid';
    if (count($search_tags)) $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . "categorie_requestmanager as cp ON rm.rowid = cp.fk_requestmanager"; // We'll need this table joined to the select in order to filter by categ
    if ($search_assigned_user || $search_assigned_usergroup || $my_list || (empty($planning) && $not_assigned)) {
        $sql .= ' INNER JOIN (';
        $sql .= '   SELECT rm.rowid';
        $sql .= '   FROM ' . MAIN_DB_PREFIX . 'requestmanager as rm';
        $sql .= '   LEFT JOIN ' . MAIN_DB_PREFIX . 'requestmanager_assigned_user as rmau ON rmau.fk_requestmanager = rm.rowid';
        $sql .= '   LEFT JOIN ' . MAIN_DB_PREFIX . 'requestmanager_assigned_usergroup as rmaug ON rmaug.fk_requestmanager = rm.rowid';
        $sql .= '   LEFT JOIN ' . MAIN_DB_PREFIX . 'user as uas ON uas.rowid = rmau.fk_user';
        $sql .= '   LEFT JOIN ' . MAIN_DB_PREFIX . 'usergroup as uga ON uga.rowid = rmaug.fk_usergroup';
        $sql .= '   WHERE rm.entity IN (' . getEntity('requestmanager') . ')';
        if ($search_assigned_user) $sql .= natural_search(array('uas.firstname', 'uas.lastname'), $search_assigned_user);
        if ($search_assigned_usergroup) $sql .= natural_search('uga.nom', $search_assigned_usergroup);
        if (empty($planning)) {
            if ($my_list) {
                $groupslist = $usergroup_static->listGroupsForUser($user->id);
                $sql .= ' AND (rmau.fk_user = ' . $user->id;
                if (!empty($groupslist)) {
                    $sql .= ' OR rmaug.fk_usergroup IN (' . implode(',', array_keys($groupslist)) . ')';
                }
                $sql .= ')';
            }
            if ($not_assigned) $sql .= ' AND (rmau.fk_user IS NULL OR rmau.fk_user = 0) AND (rmaug.fk_usergroup IS NULL OR rmaug.fk_usergroup = 0)';
        }
        $sql .= '   GROUP BY rm.rowid';
        $sql .= ' ) as assigned ON assigned.rowid = rm.rowid';
    }
    if ($planning && count($search_in_charge) > 0) {
        $sql .= ' INNER JOIN (';
        $sql .= '   SELECT srmugic.fk_soc, srmugic.fk_c_request_type ';
        $sql .= '   FROM ' . MAIN_DB_PREFIX . 'societe_rm_usergroup_in_charge as srmugic';
        $sql .= '   LEFT JOIN ' . MAIN_DB_PREFIX . 'usergroup_user as ugu ON ugu.fk_usergroup = srmugic.fk_usergroup AND ' . (!empty($conf->multicompany->enabled) && $conf->entity == 1 && $user->admin && !$user->entity ? 'ugu.entity IS NOT NULL' : 'ugu.entity IN (0,' . $conf->entity . ')');
        $sql .= '   WHERE ugu.fk_user IN (' . implode(',', $search_in_charge) . ')';
        $sql .= '   GROUP BY srmugic.fk_soc, srmugic.fk_c_request_type';
        $sql .= ' ) as in_charge ON (in_charge.fk_soc = rm.fk_soc OR in_charge.fk_soc = rm.fk_soc_benefactor) AND in_charge.fk_c_request_type = rm.fk_type';
    }
    // Add From from hooks
    $parameters = array();
    $reshook = $hookmanager->executeHooks('printFieldListFrom', $parameters);    // Note that $action and $object may have been modified by hook
    $sql .= $hookmanager->resPrint;
    $sql .= ' WHERE rm.entity IN (' . getEntity('requestmanager') . ')';
    if ($planning) {
        $status_filter = array();
        foreach ($request_types_planned as $request_type_id) {
            if ($conf->global->{'REQUESTMANAGER_PLANNING_REQUEST_STATUS_TO_PLAN_' . $request_type_id} > 0) {
                $status_filter[] = $conf->global->{'REQUESTMANAGER_PLANNING_REQUEST_STATUS_TO_PLAN_' . $request_type_id};
            }
        }
        $sql .= " AND rm.fk_status IN (" . implode(',', $status_filter) . ")";
    }
    if ($search_ref) $sql .= natural_search('rm.ref', $search_ref);
    if ($search_ref_ext) $sql .= natural_search('rm.ref_ext', $search_ref_ext);
    if ($search_type) $sql .= natural_search('crmrt.label', $search_type);
    if ($search_category) $sql .= natural_search('crmc.label', $search_category);
    if ($search_label) $sql .= natural_search('rm.label', $search_label);
    if ($search_thridparty_origin) $sql .= natural_search('so.nom', $search_thridparty_origin);
    if ($search_thridparty) $sql .= natural_search('s.nom', $search_thridparty);
    if ($search_thridparty_benefactor) $sql .= natural_search('sb.nom', $search_thridparty_benefactor);
    if ($search_thridparty_watcher) $sql .= natural_search('sw.nom', $search_thridparty_watcher);
    if ($socid > 0) $sql .= ' AND (rm.fk_soc_origin = ' . $socid . ' OR rm.fk_soc = ' . $socid . ' OR rm.fk_soc_benefactor = ' . $socid . ')';
    if ($search_source) $sql .= natural_search('crms.label', $search_source);
    if ($search_urgency) $sql .= natural_search('crmu.label', $search_urgency);
    if ($search_impact) $sql .= natural_search('crmi.label', $search_impact);
    if ($search_priority) $sql .= natural_search('crmp.label', $search_priority);
    if ($search_duration) $sql .= natural_search('rm.duration', $search_duration, 1);
    if ($search_date_operation) $sql .= natural_search('rm.date_operation', $search_date_operation, 1);
    if ($search_date_deadline) $sql .= natural_search('rm.date_deadline', $search_date_deadline, 1);
    if ($search_notify_requester_by_email >= 0) $sql .= ' AND rm.notify_requester_by_email = ' . $search_notify_requester_by_email;
    if ($search_notify_watcher_by_email >= 0) $sql .= ' AND rm.notify_watcher_by_email = ' . $search_notify_watcher_by_email;
    if ($search_notify_assigned_by_email >= 0) $sql .= ' AND rm.notify_assigned_by_email = ' . $search_notify_assigned_by_email;
    if ($search_description) $sql .= natural_search('rm.description', $search_description);
    if (count($search_tags)) $sql .= " AND cp.fk_categorie IN (" . implode(',', $search_tags) . ")";
    if ($search_reason_resolution > 0) $sql .= " AND rm.fk_reason_resolution = " . $search_reason_resolution;
    if ($search_reason_resolution_details) $sql .= natural_search('rm.reason_resolution_details', $search_reason_resolution_details);
    if ($search_date_resolved) $sql .= natural_search('rm.date_resolved', $search_date_resolved, 1);
    if ($search_date_cloture) $sql .= natural_search('rm.date_closed', $search_date_cloture, 1);
    if ($search_user_resolved) $sql .= natural_search(array('ur.firstname', 'ur.lastname'), $search_user_resolved);
    if ($search_user_cloture) $sql .= natural_search(array('uc.firstname', 'uc.lastname'), $search_user_cloture);
    if ($search_date_creation) $sql .= natural_search('rm.datec', $search_date_creation, 1);
    if ($search_date_modification) $sql .= natural_search('rm.tms', $search_date_modification, 1);
    if ($search_user_author) $sql .= natural_search(array('ua.firstname', 'ua.lastname'), $search_user_author);
    if ($search_user_modification) $sql .= natural_search(array('um.firstname', 'um.lastname'), $search_user_modification);
    if ($search_user_modification) $sql .= natural_search(array('um.firstname', 'um.lastname'), $search_user_modification);
    if (count($search_status_det)) $sql .= natural_search(array('rm.fk_status'), implode(',', $search_status_det), 2);
    if ($status_type >= 0) $sql .= ' AND crmst.type = ' . $status_type;
    elseif ($status_type == -2) $sql .= ' AND crmst.type IN (' . RequestManager::STATUS_TYPE_INITIAL . ', ' . RequestManager::STATUS_TYPE_IN_PROGRESS . ')';
    if ($sall) {
        $sql .= natural_search(array_keys($fieldstosearchall), $sall);
    }
    // Add where from extra fields
    foreach ($search_array_options as $key => $val) {
        $crit = $val;
        $tmpkey = preg_replace('/search_options_/', '', $key);
        $typ = $extrafields->attribute_type[$tmpkey];
        $mode = 0;
        if (in_array($typ, array('int', 'double', 'real'))) $mode = 1;                                // Search on a numeric
        if (in_array($typ, array('sellist')) && $crit != '0' && $crit != '-1') $mode = 2;            // Search on a foreign key int
        if ($crit != '' && (!in_array($typ, array('select', 'sellist')) || $crit != '0')) {
            $sql .= natural_search('ef.' . $tmpkey, $crit, $mode);
        }
    }
    // Add where from hooks
    $parameters = array();
    $reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters);    // Note that $action and $object may have been modified by hook
    $sql .= $hookmanager->resPrint;

    $sql .= ' GROUP BY rm.rowid';
    $sql .= $db->order($sortfield, $sortorder);

    // Count total nb of records
    $nbtotalofrecords = '';
    if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
        $result = $db->query($sql);
        $nbtotalofrecords = $db->num_rows($result);
    }

    $sql .= $db->plimit($limit + 1, $offset);

    $resql = $db->query($sql);
    if ($resql) {
        $objectstatic = new RequestManager($db);
        $userstatic = new User($db);
        $societestatic_origin = new Societe($db);
        $societestatic = new Societe($db);
        $societestatic_benefactor = new Societe($db);
        $societestatic_watcher = new Societe($db);

        $num = $db->num_rows($resql);

        $arrayofselected = is_array($toselect) ? $toselect : array();

        $param = '';
        if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage=' . urlencode($contextpage);
        if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit=' . urlencode($limit);
        if ($sall) $param .= '&sall=' . urlencode($sall);
        if ($socid) $param .= '&socid=' . urlencode($socid);
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
        if (count($search_tags)) $param .= '&'.http_build_query(array('search_tags'=>$search_tags));
        if ($search_reason_resolution > 0) $param .= '&search_reason_resolution=' . urlencode($search_reason_resolution);
        if ($search_reason_resolution_details) $param .= '&search_reason_resolution_details=' . urlencode($search_reason_resolution_details);
        if ($search_date_resolved) $param .= '&search_date_resolved=' . urlencode($search_date_resolved);
        if ($search_date_cloture) $param .= '&search_date_cloture=' . urlencode($search_date_cloture);
        if ($search_user_resolved) $param .= '&search_user_resolved=' . urlencode($search_user_resolved);
        if ($search_user_cloture) $param .= '&search_user_cloture=' . urlencode($search_user_cloture);
        if ($search_date_creation) $param .= '&search_date_creation=' . urlencode($search_date_creation);
        if ($search_date_modification) $param .= '&search_date_modification=' . urlencode($search_date_modification);
        if ($search_user_author) $param .= '&search_user_author=' . urlencode($search_user_author);
        if ($search_user_modification) $param .= '&search_user_modification=' . urlencode($search_user_modification);
        if (count($search_status_det)) $param .= '&search_status_det=' . urlencode($search_status_det);
        if (count($search_in_charge)) $param .= '&search_in_charge=' . urlencode($search_in_charge);
        if ($planning) $param .= '&planning=' . urlencode($planning);
        if ($my_list) $param .= '&my_list=' . urlencode($my_list);
        if (empty($planning) && $not_assigned) $param .= '&not_assigned=' . urlencode($not_assigned);
        if ($status_type !== '' && $status_type != -1) $param .= '&status_type=' . urlencode($status_type);
        if ($optioncss != '') $param .= '&optioncss=' . urlencode($optioncss);

        // Add $param from extra fields
        foreach ($search_array_options as $key => $val) {
            $crit = $val;
            $tmpkey = preg_replace('/search_options_/', '', $key);
            if ($val != '') {
                if (is_array($val)) {
                    $param .= '&'.http_build_query(array('search_options_' . $tmpkey=>$val));
                } else {
                    $param .= '&search_options_' . $tmpkey . '=' . urlencode($val);
                }
            }
        }

        // List of mass actions available
        $arrayofmassactions = array();
        if ($planning) {
            if ($user->rights->requestmanager->planning->manage) $arrayofmassactions['rm_planning'] = $langs->trans("RequestManagerPlanning");
        } else {
            if ($user->rights->requestmanager->supprimer) $arrayofmassactions['delete'] = $langs->trans("Delete");
        }
        $massactionbutton = $form->selectMassAction('', $arrayofmassactions);

        $params2 = '';
        if ($planning) $params2 .= '&planning=' . urlencode($planning);
        if ($my_list) $params2 .= '&my_list=' . urlencode($my_list);
        if (empty($planning) && $not_assigned) $params2 .= '&not_assigned=' . urlencode($not_assigned);
        if ($socid > 0) $params2 .= '&socid=' . urlencode($socid);
        $params2 = !empty($params2) ? '?' . substr($params2, 1) : '';

        // Lignes des champs de filtre
        print '<form method="POST" id="searchFormList" action="' . $_SERVER["PHP_SELF"] . $params2 . '">';
        if ($optioncss != '') print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
        print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
        print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
        print '<input type="hidden" name="action" value="list">';
        print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
        print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
        print '<input type="hidden" name="page" value="' . $page . '">';
        //if ($status_type != '' && $status_type != -1) print '<input type="hidden" name="status_type" value="' . dol_escape_htmltag($status_type) . '">';

        print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'requestmanager@requestmanager', 0, '', '', $limit);

        if ($sall) {
            foreach ($fieldstosearchall as $key => $val) $fieldstosearchall[$key] = $langs->trans($val);
            print $langs->trans("FilterOnInto", $sall) . join(', ', $fieldstosearchall);
        }

        $i = 0;

        $moreforfilter = '';

        // Filter on users in charge
        if ($planning) {
            $moreforfilter .= '<div class="divsearchfield">';
            $moreforfilter .= $langs->trans('RequestManagerPlanningUsersInCharge') . ': ';
            $moreforfilter .= $formrequestmanager->multiselect_dolusers($search_in_charge, 'search_in_charge', '', 0, '', array(), 0, 0, 0, 'AND fk_soc IS NULL', 0, '', 'minwidth300');
            $moreforfilter .= '</div>';
        }

        //$moreforfilter.='<div class="divsearchfield">';
        //$moreforfilter.=$langs->trans('RequestManagerType'). ': ';
        //$moreforfilter.=$formrequestmanager->select_type(null,  $search_type, 'search_type', 1, 0, array(), 0, 0, ' minwidth200', ' multiple');
        //$moreforfilter.='</div>';

        $moreforfilter .= '<div class="divsearchfield">';
        $moreforfilter .= $langs->trans('Status') . ': ';
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
                if (empty($planning) || (in_array($type_id, $request_types_planned) && $line->id == $conf->global->{'REQUESTMANAGER_PLANNING_REQUEST_STATUS_TO_PLAN_' . $type_id})) {
                    $toprint[] = isset($rmtypedictionary->lines[$type_id]) ? $rmtypedictionary->lines[$type_id]->fields['label'] : $unknown_label . $type_id;
                }
            }
            if ($planning && empty($toprint)) continue;
            $statut_array[$line->id] = $line->fields['label'] . (count($toprint) > 0 ? ' ( ' . implode(', ', $toprint) . ' )' : '');
        }
        $moreforfilter .= $form->multiselectarray('search_status_det', $statut_array, $search_status_det, 0, 0, ' minwidth300');
        $moreforfilter .= '</div>';

        // Filter on tags/categories
        if (!empty($conf->categorie->enabled)) {
            $moreforfilter .= '<div class="divsearchfield">';
            $moreforfilter .= $langs->trans('RequestManagerTags') . ': ';
            $moreforfilter .= $formrequestmanager->multiselect_categories($search_tags, 'search_tags', '', 0, 'minwidth300');
            $moreforfilter .= '</div>';
        }

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
        // Thridparty Benefactor
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
        // Users in charge
        if (!empty($arrayfields['users_in_charge']['checked'])) {
            print '<td class="liste_titre">';
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
        // Reason for resolution
        if (!empty($arrayfields['rm.fk_reason_resolution']['checked'])) {
            print '<td class="liste_titre">';
            print $formdictionary->select_dictionary('requestmanager', 'requestmanagerreasonresolution', $search_reason_resolution, 'search_reason_resolution', 1);
            print '</td>';
        }
        // Details reason for resolution
        if (!empty($arrayfields['rm.reason_resolution_details']['checked'])) {
            print '<td class="liste_titre">';
            print '<input class="flat" size="6" type="text" name="search_reason_resolution_details" value="' . dol_escape_htmltag($search_reason_resolution_details) . '">';
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
        print '<tr class="liste_titre">';
        if (!empty($arrayfields['rm.ref']['checked'])) print_liste_field_titre($arrayfields['rm.ref']['label'], $_SERVER["PHP_SELF"], 'rm.ref', '', $param, '', $sortfield, $sortorder);
        if (!empty($arrayfields['rm.ref_ext']['checked'])) print_liste_field_titre($arrayfields['rm.ref_ext']['label'], $_SERVER["PHP_SELF"], 'rm.ref_ext', '', $param, '', $sortfield, $sortorder);
        if (!empty($arrayfields['rm.fk_type']['checked'])) print_liste_field_titre($arrayfields['rm.fk_type']['label'], $_SERVER["PHP_SELF"], 'crmrt.label', '', $param, '', $sortfield, $sortorder);
        if (!empty($arrayfields['rm.fk_category']['checked'])) print_liste_field_titre($arrayfields['rm.fk_category']['label'], $_SERVER["PHP_SELF"], 'crmc.label', '', $param, '', $sortfield, $sortorder);
        if (!empty($arrayfields['rm.label']['checked'])) print_liste_field_titre($arrayfields['rm.label']['label'], $_SERVER["PHP_SELF"], 'rm.label', '', $param, '', $sortfield, $sortorder);
        if (!empty($arrayfields['rm.fk_soc_origin']['checked'])) print_liste_field_titre($arrayfields['rm.fk_soc_origin']['label'], $_SERVER["PHP_SELF"], 'so.nom', '', $param, '', $sortfield, $sortorder);
        if (!empty($arrayfields['rm.fk_soc']['checked'])) print_liste_field_titre($arrayfields['rm.fk_soc']['label'], $_SERVER["PHP_SELF"], 's.nom', '', $param, '', $sortfield, $sortorder);
        if (!empty($arrayfields['rm.fk_soc_benefactor']['checked'])) print_liste_field_titre($arrayfields['rm.fk_soc_benefactor']['label'], $_SERVER["PHP_SELF"], 'sb.nom', '', $param, '', $sortfield, $sortorder);
        if (!empty($arrayfields['rm.fk_soc_watcher']['checked'])) print_liste_field_titre($arrayfields['rm.fk_soc_watcher']['label'], $_SERVER["PHP_SELF"], 'sw.nom', '', $param, '', $sortfield, $sortorder);
        if (!empty($arrayfields['rm.description']['checked'])) print_liste_field_titre($arrayfields['rm.description']['label'], $_SERVER["PHP_SELF"], 'rm.description', '', $param, '', $sortfield, $sortorder);
        if (!empty($arrayfields['rm.fk_source']['checked'])) print_liste_field_titre($arrayfields['rm.fk_source']['label'], $_SERVER["PHP_SELF"], 'crms.label', '', $param, '', $sortfield, $sortorder);
        if (!empty($arrayfields['rm.fk_urgency']['checked'])) print_liste_field_titre($arrayfields['rm.fk_urgency']['label'], $_SERVER["PHP_SELF"], 'crmu.label', '', $param, '', $sortfield, $sortorder);
        if (!empty($arrayfields['rm.fk_impact']['checked'])) print_liste_field_titre($arrayfields['rm.fk_impact']['label'], $_SERVER["PHP_SELF"], 'crmi.label', '', $param, '', $sortfield, $sortorder);
        if (!empty($arrayfields['rm.fk_priority']['checked'])) print_liste_field_titre($arrayfields['rm.fk_priority']['label'], $_SERVER["PHP_SELF"], 'crmp.label', '', $param, '', $sortfield, $sortorder);
        if (!empty($arrayfields['rm.duration']['checked'])) print_liste_field_titre($arrayfields['rm.duration']['label'], $_SERVER["PHP_SELF"], 'rm.duration', '', $param, '', $sortfield, $sortorder);
        if (!empty($arrayfields['rm.date_operation']['checked'])) print_liste_field_titre($arrayfields['rm.date_operation']['label'], $_SERVER["PHP_SELF"], 'rm.date_operation', 'align="center"', $param, '', $sortfield, $sortorder);
        if (!empty($arrayfields['rm.date_deadline']['checked'])) print_liste_field_titre($arrayfields['rm.date_deadline']['label'], $_SERVER["PHP_SELF"], 'rm.date_deadline', 'align="center"', $param, '', $sortfield, $sortorder);
        if (!empty($arrayfields['rm.notify_requester_by_email']['checked'])) print_liste_field_titre($arrayfields['rm.notify_requester_by_email']['label'], $_SERVER["PHP_SELF"], 'rm.notify_requester_by_email', 'align="center"', $param, '', $sortfield, $sortorder);
        if (!empty($arrayfields['rm.notify_watcher_by_email']['checked'])) print_liste_field_titre($arrayfields['rm.notify_watcher_by_email']['label'], $_SERVER["PHP_SELF"], 'rm.notify_watcher_by_email', 'align="center"', $param, '', $sortfield, $sortorder);
        if (!empty($arrayfields['users_in_charge']['checked'])) print_liste_field_titre($arrayfields['users_in_charge']['label'], $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder);
        if (!empty($arrayfields['assigned_users']['checked'])) print_liste_field_titre($arrayfields['assigned_users']['label'], $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder);
        if (!empty($arrayfields['assigned_usergroups']['checked'])) print_liste_field_titre($arrayfields['assigned_usergroups']['label'], $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder);
        if (!empty($arrayfields['rm.notify_assigned_by_email']['checked'])) print_liste_field_titre($arrayfields['rm.notify_assigned_by_email']['label'], $_SERVER["PHP_SELF"], 'rm.notify_assigned_by_email', 'align="center"', $param, '', $sortfield, $sortorder);
        if (!empty($arrayfields['rm.fk_reason_resolution']['checked'])) print_liste_field_titre($arrayfields['rm.fk_reason_resolution']['label'], $_SERVER["PHP_SELF"], 'crmrr.label', '', $param, '', $sortfield, $sortorder);
        if (!empty($arrayfields['rm.reason_resolution_details']['checked'])) print_liste_field_titre($arrayfields['rm.reason_resolution_details']['label'], $_SERVER["PHP_SELF"], 'rm.reason_resolution_details', '', $param, '', $sortfield, $sortorder);
        if (!empty($arrayfields['rm.fk_user_resolved']['checked'])) print_liste_field_titre($arrayfields['rm.fk_user_resolved']['label'], $_SERVER["PHP_SELF"], 'ur.lastname', '', $param, '', $sortfield, $sortorder);
        if (!empty($arrayfields['rm.fk_user_closed']['checked'])) print_liste_field_titre($arrayfields['rm.fk_user_closed']['label'], $_SERVER["PHP_SELF"], 'uc.lastname', '', $param, '', $sortfield, $sortorder);
        if (!empty($arrayfields['rm.date_resolved']['checked'])) print_liste_field_titre($arrayfields['rm.date_resolved']['label'], $_SERVER["PHP_SELF"], 'rm.date_resolved', 'align="center"', $param, '', $sortfield, $sortorder);
        if (!empty($arrayfields['rm.date_cloture']['checked'])) print_liste_field_titre($arrayfields['rm.date_cloture']['label'], $_SERVER["PHP_SELF"], 'rm.date_cloture', 'align="center"', $param, '', $sortfield, $sortorder);
        if (!empty($arrayfields['rm.fk_user_author']['checked'])) print_liste_field_titre($arrayfields['rm.fk_user_author']['label'], $_SERVER["PHP_SELF"], 'ua.lastname', '', $param, '', $sortfield, $sortorder);
        if (!empty($arrayfields['rm.fk_user_modif']['checked'])) print_liste_field_titre($arrayfields['rm.fk_user_modif']['label'], $_SERVER["PHP_SELF"], 'um.lastname', '', $param, '', $sortfield, $sortorder);
        // Extra fields
        if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) {
            foreach ($extrafields->attribute_label as $key => $val) {
                if (!empty($arrayfields["ef." . $key]['checked'])) {
                    $align = $extrafields->getAlignFlag($key);
                    $sortonfield = "ef." . $key;
                    if (!empty($extrafields->attribute_computed[$key])) $sortonfield = '';
                    print_liste_field_titre($extralabels[$key], $_SERVER["PHP_SELF"], $sortonfield, "", $param, ($align ? 'align="' . $align . '"' : ''), $sortfield, $sortorder);
                }
            }
        }
        // Hook fields
        $parameters = array('arrayfields' => $arrayfields);
        $reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters);    // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;
        if (!empty($arrayfields['rm.datec']['checked'])) print_liste_field_titre($arrayfields['rm.datec']['label'], $_SERVER["PHP_SELF"], "rm.datec", "", $param, 'align="center" class="nowrap"', $sortfield, $sortorder);
        if (!empty($arrayfields['rm.tms']['checked'])) print_liste_field_titre($arrayfields['rm.tms']['label'], $_SERVER["PHP_SELF"], "rm.tms", "", $param, 'align="center" class="nowrap"', $sortfield, $sortorder);
        if (!empty($arrayfields['rm.fk_status']['checked'])) print_liste_field_titre($arrayfields['rm.fk_status']['label'], $_SERVER["PHP_SELF"], "crmst.label", "", $param, 'align="right"', $sortfield, $sortorder);
        print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', $sortfield, $sortorder, 'maxwidthsearch ');
        print '</tr>' . "\n";

        $users_cache = array();
        $usergroups_cache = array();

        $now = dol_now();
        $i = 0;
        $totalarray = array();
        while ($i < min($num, $limit)) {
            $obj = $db->fetch_object($resql);

            $societestatic_origin->id = $obj->fk_soc_origin;
            $societestatic_origin->name = $obj->soc_name_origin;
            $societestatic_origin->client = $obj->soc_client_origin;
            $societestatic_origin->fournisseur = $obj->soc_fournisseur_origin;
            $societestatic_origin->code_client = $obj->soc_code_client_origin;
            $societestatic_origin->code_fournisseur = $obj->soc_code_fournisseur_origin;

            $societestatic->id = $obj->fk_soc;
            $societestatic->name = $obj->soc_name;
            $societestatic->client = $obj->soc_client;
            $societestatic->fournisseur = $obj->soc_fournisseur;
            $societestatic->code_client = $obj->soc_code_client;
            $societestatic->code_fournisseur = $obj->soc_code_fournisseur;

            $societestatic_benefactor->id = $obj->fk_soc_benefactor;
            $societestatic_benefactor->name = $obj->soc_name_benefactor;
            $societestatic_benefactor->client = $obj->soc_client_benefactor;
            $societestatic_benefactor->fournisseur = $obj->soc_fournisseur_benefactor;
            $societestatic_benefactor->code_client = $obj->soc_code_client_benefactor;
            $societestatic_benefactor->code_fournisseur = $obj->soc_code_fournisseur_benefactor;

            $societestatic_watcher->id = $obj->fk_soc_watcher;
            $societestatic_watcher->name = $obj->soc_name_watcher;
            $societestatic_watcher->client = $obj->soc_client_watcher;
            $societestatic_watcher->fournisseur = $obj->soc_fournisseur_watcher;
            $societestatic_watcher->code_client = $obj->soc_code_client_watcher;
            $societestatic_watcher->code_fournisseur = $obj->soc_code_fournisseur_watcher;

            $objectstatic->id = $obj->rowid;
            $objectstatic->fk_parent = $obj->fk_parent;
            $objectstatic->ref = $obj->ref;
            $objectstatic->ref_ext = $obj->ref_ext;
            $objectstatic->fk_type = $obj->fk_type;
            $objectstatic->label = $obj->label;
            $objectstatic->socid = $obj->fk_soc;
            $objectstatic->thirdparty_origin = $societestatic_origin;
            $objectstatic->thirdparty = $societestatic;
            $objectstatic->thirdparty_benefactor = $societestatic_benefactor;
            $objectstatic->thirdparty_watcher = $societestatic_watcher;

            // picto warning for deadline
            $pictoWarning = '';
            if ($obj->date_deadline) {
                $tmsDeadLine = strtotime($obj->date_deadline);
                if ($tmsDeadLine < $now) {
                    // alert time is up
                    $pictoWarning = ' ' . img_warning($langs->trans("Late"));
                }
            }

            print '<tr class="oddeven">';

            // Ref
            if (!empty($arrayfields['rm.ref']['checked'])) {
                print '<td class="nowrap">';
                print $objectstatic->getNomUrl(1, 'parent_path', 24) . $pictoWarning;
                print '</td>';
            }
            //External Ref
            if (!empty($arrayfields['rm.ref_ext']['checked'])) {
                print '<td class="nowrap">';
                print $obj->ref_ext;
                print '</td>';
            }
            // Type
            if (!empty($arrayfields['rm.fk_type']['checked'])) {
                print '<td class="nowrap">';
                print $obj->type_label;
                print '</td>';
            }
            // Category
            if (!empty($arrayfields['rm.fk_category']['checked'])) {
                print '<td class="nowrap">';
                print $obj->category_label;
                print '</td>';
            }
            // Label
            if (!empty($arrayfields['rm.label']['checked'])) {
                print '<td class="nowrap">';
                $toprint = dol_trunc($obj->label, 24);
                if (empty($conf->global->MAIN_DISABLE_TRUNC)) {
                    $toprint = $form->textwithtooltip($toprint, $obj->label);
                }
                print $toprint;
                print '</td>';
            }
            // Thridparty Origin
            if (!empty($arrayfields['rm.fk_soc_origin']['checked'])) {
                print '<td class="nowrap">';
                print $societestatic_origin->getNomUrl(1, '', 24);
                print '</td>';
            }
            // Thridparty Bill
            if (!empty($arrayfields['rm.fk_soc']['checked'])) {
                print '<td class="nowrap">';
                print $societestatic->getNomUrl(1, '', 24);
                print '</td>';
            }
            // Thridparty Benefactor
            if (!empty($arrayfields['rm.fk_soc_benefactor']['checked'])) {
                print '<td class="nowrap">';
                print $societestatic_benefactor->getNomUrl(1, '', 24);
                print '</td>';
            }
            // Thridparty Watcher
            if (!empty($arrayfields['rm.fk_soc_watcher']['checked'])) {
                print '<td class="nowrap">';
                if ($societestatic_watcher->id > 0) {
                    print $societestatic_watcher->getNomUrl(1, '', 24);
                }
                print '</td>';
            }
            // Description
            if (!empty($arrayfields['rm.description']['checked'])) {
                print '<td class="nowrap">';
                $toprint = dol_trunc($obj->description, 24);
                if (empty($conf->global->MAIN_DISABLE_TRUNC)) {
                    $toprint = $form->textwithtooltip($toprint, $obj->description);
                }
                print $toprint;
                print '</td>';
            }
            // Source
            if (!empty($arrayfields['rm.fk_source']['checked'])) {
                print '<td class="nowrap">';
                print $obj->source_label;
                print '</td>';
            }
            // Urgency
            if (!empty($arrayfields['rm.fk_urgency']['checked'])) {
                print '<td class="nowrap">';
                print $obj->urgency_label;
                print '</td>';
            }
            // Impact
            if (!empty($arrayfields['rm.fk_impact']['checked'])) {
                print '<td class="nowrap">';
                print $obj->impact_label;
                print '</td>';
            }
            // Priority
            if (!empty($arrayfields['rm.fk_priority']['checked'])) {
                print '<td class="nowrap">';
                print $obj->priority_label;
                print '</td>';
            }
            // Duration
            if (!empty($arrayfields['rm.duration']['checked'])) {
                print '<td class="nowrap">';
                if ($obj->duration > 0) print requestmanager_print_duration($obj->duration);
                print '</td>';
            }
            // Date Operation
            if (!empty($arrayfields['rm.date_operation']['checked'])) {
                print '<td class="nowrap" align="center">';
                if ($obj->date_operation > 0) print dol_print_date($db->jdate($obj->date_operation), 'dayhour');
                print '</td>';
            }
            // Date Deadline
            if (!empty($arrayfields['rm.date_deadline']['checked'])) {
                print '<td class="nowrap" align="center">';
                if ($obj->date_deadline > 0) print dol_print_date($db->jdate($obj->date_deadline), 'dayhour');
                print '</td>';
            }
            // Notification requesters
            if (!empty($arrayfields['rm.notify_requester_by_email']['checked'])) {
                print '<td class="nowrap" align="center">';
                print yn($obj->notify_requester_by_email);
                print '</td>';
            }
            // Notification watchers
            if (!empty($arrayfields['rm.notify_watcher_by_email']['checked'])) {
                print '<td class="nowrap" align="center">';
                print yn($obj->notify_watcher_by_email);
                print '</td>';
            }
            // Users in charge
            if (!empty($arrayfields['users_in_charge']['checked'])) {
                print '<td class="nowrap">';
                $users_in_charge = explode(',', $obj->users_in_charge);
                if (is_array($users_in_charge) && count($users_in_charge) > 0) {
                    $toprint = array();
                    $blocktoprint = array();
                    foreach ($users_in_charge as $user_id) {
                        if ($user_id > 0) {
                            if (!isset($users_cache[$user_id])) {
                                $user_in_charge = new User($db);
                                $user_in_charge->fetch($user_id);
                                $users_cache[$user_id] = $user_in_charge;
                            }
                            $blocktoprint[] = $users_cache[$user_id]->getNomUrl(1);
                        }
                        if (count($blocktoprint) == 5) { // split nb of users into a line
                            $toprint[] = implode(', ', $blocktoprint);
                            $blocktoprint = array();
                        }
                    }
                    if (count($blocktoprint)) $toprint[] = implode(', ', $blocktoprint);
                    print implode('<br>', $toprint);
                }
                print '</td>';
            }
            // Assigned user
            if (!empty($arrayfields['assigned_users']['checked'])) {
                print '<td class="nowrap">';
                $assigned_users = explode(',', $obj->assigned_users);
                if (is_array($assigned_users) && count($assigned_users) > 0) {
                    $toprint = array();
                    foreach ($assigned_users as $user_id) {
                        if ($user_id > 0) {
                            if (!isset($users_cache[$user_id])) {
                                $assigned_user = new User($db);
                                $assigned_user->fetch($user_id);
                                $users_cache[$user_id] = $assigned_user;
                            }
                            $toprint[] = $users_cache[$user_id]->getNomUrl(1);
                        }
                    }
                    print implode(', ', $toprint);
                }
                print '</td>';
            }
            // Assigned usergroup
            if (!empty($arrayfields['assigned_usergroups']['checked'])) {
                print '<td class="nowrap">';
                $assigned_usergroups = explode(',', $obj->assigned_usergroups);
                if (is_array($assigned_usergroups) && count($assigned_usergroups) > 0) {
                    $toprint = array();
                    foreach ($assigned_usergroups as $usergroup_id) {
                        if (!isset($usergroups_cache[$usergroup_id])) {
                            $assigned_usergroup = new UserGroup($db);
                            $assigned_usergroup->fetch($usergroup_id);
                            $usergroups_cache[$usergroup_id] = $assigned_usergroup;
                        }
                        $toprint[] = $usergroups_cache[$usergroup_id]->getFullName($langs);
                    }
                    print implode(', ', $toprint);
                }
                print '</td>';
            }
            // Notification assigned
            if (!empty($arrayfields['rm.notify_assigned_by_email']['checked'])) {
                print '<td class="nowrap" align="center">';
                print yn($obj->notify_assigned_by_email);
                print '</td>';
            }
            // Reason for resolution
            if (!empty($arrayfields['rm.fk_reason_resolution']['checked'])) {
                print '<td class="nowrap">';
                print $obj->reason_resolution;
                print '</td>';
            }
            // Details reason for resolution
            if (!empty($arrayfields['rm.reason_resolution_details']['checked'])) {
                print '<td class="nowrap">';
                print $obj->reason_resolution_details;
                print '</td>';
            }
            // User resolved
            if (!empty($arrayfields['rm.fk_user_resolved']['checked'])) {
                print '<td class="nowrap">';
                if ($obj->fk_user_resolved > 0) {
                    $userstatic->id = $obj->fk_user_resolved;
                    $userstatic->firstname = $obj->userresolvedfirstname;
                    $userstatic->lastname = $obj->userresolvedlastname;
                    $userstatic->email = $obj->userresolvedemail;
                    print $userstatic->getNomUrl(1);
                }
                print '</td>';
            }
            // User closed
            if (!empty($arrayfields['rm.fk_user_closed']['checked'])) {
                print '<td class="nowrap">';
                if ($obj->fk_user_closed > 0) {
                    $userstatic->id = $obj->fk_user_closed;
                    $userstatic->firstname = $obj->userclosedfirstname;
                    $userstatic->lastname = $obj->userclosedlastname;
                    $userstatic->email = $obj->userclosedemail;
                    print $userstatic->getNomUrl(1);
                }
                print '</td>';
            }
            // Date resolved
            if (!empty($arrayfields['rm.date_resolved']['checked'])) {
                print '<td class="nowrap" align="center">';
                if ($obj->date_resolved > 0) print dol_print_date($db->jdate($obj->date_resolved), 'dayhour');
                print '</td>';
            }
            // Date closed
            if (!empty($arrayfields['rm.date_cloture']['checked'])) {
                print '<td class="nowrap" align="center">';
                if ($obj->date_closed > 0) print dol_print_date($db->jdate($obj->date_closed), 'dayhour');
                print '</td>';
            }
            // Author
            if (!empty($arrayfields['rm.fk_user_author']['checked'])) {
                print '<td class="nowrap">';
                if ($obj->fk_user_author > 0) {
                    $userstatic->id = $obj->fk_user_author;
                    $userstatic->firstname = $obj->userauthorfirstname;
                    $userstatic->lastname = $obj->userauthorlastname;
                    $userstatic->email = $obj->userauthoremail;
                    print $userstatic->getNomUrl(1);
                }
                print '</td>';
            }
            // Modified by
            if (!empty($arrayfields['rm.fk_user_modif']['checked'])) {
                print '<td class="nowrap">';
                if ($obj->fk_user_modif > 0) {
                    $userstatic->id = $obj->fk_user_modif;
                    $userstatic->firstname = $obj->usermodiffirstname;
                    $userstatic->lastname = $obj->usermodiflastname;
                    $userstatic->email = $obj->usermodifemail;
                    print $userstatic->getNomUrl(1);
                }
                print '</td>';
            }

            // Extra fields
            if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) {
                foreach ($extrafields->attribute_label as $key => $val) {
                    if (!empty($arrayfields["ef." . $key]['checked'])) {
                        print '<td';
                        $align = $extrafields->getAlignFlag($key);
                        if ($align) print ' align="' . $align . '"';
                        print '>';
                        $tmpkey = 'options_' . $key;
                        print $extrafields->showOutputField($key, $obj->$tmpkey, '', 1);
                        print '</td>';
                    }
                }
            }
            // Fields from hook
            $parameters = array('arrayfields' => $arrayfields, 'obj' => $obj);
            $reshook = $hookmanager->executeHooks('printFieldListValue', $parameters);    // Note that $action and $object may have been modified by hook
            print $hookmanager->resPrint;

            // Date creation
            if (!empty($arrayfields['rm.datec']['checked'])) {
                print '<td align="center" class="nowrap">';
                print dol_print_date($db->jdate($obj->datec), 'dayhour');
                print '</td>';
            }
            // Date modification
            if (!empty($arrayfields['rm.tms']['checked'])) {
                print '<td align="center" class="nowrap">';
                print dol_print_date($db->jdate($obj->tms), 'dayhour');
                print '</td>';
            }
            // Status
            if (!empty($arrayfields['rm.fk_status']['checked'])) {
                print '<td align="right" class="nowrap">' . $objectstatic->LibStatut($obj->fk_status, 5) . '</td>';
            }

            // Action column
            print '<td class="nowrap" align="center">';
            if ($planning && $user->rights->requestmanager->planning->manage) {
                print '<a href="' . $_SERVER['PHP_SELF'] . '?action=to_plan&request_id=' . $obj->rowid . $param . '">';
                print img_object($langs->trans("RequestManagerPlanningToPlan"), "action");
                print '</a>&nbsp;';
            }
            if ($massactionbutton || $massaction)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
            {
                $selected = 0;
                if (in_array($obj->rowid, $arrayofselected)) $selected = 1;
                print '<input id="cb' . $obj->rowid . '" class="flat checkforselect" type="checkbox" name="toselect[]" value="' . $obj->rowid . '"' . ($selected ? ' checked="checked"' : '') . '>';
            }
            print '</td>';

            print "</tr>\n";

            $i++;
        }

        $db->free($resql);

        $parameters = array('arrayfields' => $arrayfields, 'sql' => $sql);
        $reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters);    // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;

        print '</table>' . "\n";
        print '</div>' . "\n";

        print '</form>' . "\n";
    } else {
        dol_print_error($db);
    }
} else {
    print $langs->trans('RequestManagerPlanningRequestTypeNotConfigured');
}

// End of page
llxFooter();
$db->close();
