<?php
/* Copyright (C) 2015 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * or see http://www.gnu.org/
 */

/**
 *	\file			htdocs/core/init_events.inc.php
 *  \brief			Code for init on event of the request
 */


$list_mode = GETPOST('list_mode', 'int');
if ($list_mode === "") $list_mode = $_SESSION['rm_list_mode'];
if ($list_mode === "" || !isset($list_mode)) $list_mode = !empty($conf->global->REQUESTMANAGER_DEFAULT_LIST_MODE) ? $conf->global->REQUESTMANAGER_DEFAULT_LIST_MODE : 0;
$_SESSION['rm_list_mode'] = $list_mode;

$limit = GETPOST('limit') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOST("page", 'int');
if (empty($page) || $page == -1) {
    $page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) $sortfield = 'ac.datep';
if (!$sortorder) $sortorder = 'DESC';

$search_only_linked_to_request = GETPOST('search_only_linked_to_request', 'int');
$search_include_event_other_request = GETPOST('search_include_event_other_request', 'int');
$search_include_linked_event_to_children_request = GETPOST('search_include_linked_event_to_children_request', 'int');
$search_dont_show_selected_event_type_origin = GETPOST('search_dont_show_selected_event_type_origin', 'int');
$search_ref = GETPOST('search_ref', 'alpha');
$search_origin = GETPOST('search_origin', 'array');
$search_thirdparty = GETPOST('search_thirdparty', 'alpha');
$search_type = GETPOST('search_type', 'array');
$search_except_type = GETPOST('search_except_type', 'array');
$search_title = GETPOST('search_title', 'alpha');
$search_description = GETPOST('search_description', 'alpha');
$search_event_on_full_day = GETPOST('search_event_on_full_day', 'int');
$search_date_start = GETPOST('search_date_start', 'alpha');
$search_date_end = GETPOST('search_date_end', 'alpha');
$search_location = GETPOST('search_location', 'alpha');
$search_owned_by = GETPOST('search_owned_by', 'alpha');
//$search_assigned_to = GETPOST('search_assigned_to', 'alpha');
$search_done_by = GETPOST('search_done_by', 'alpha');
$search_project = GETPOST('search_project', 'alpha');
$search_priority = GETPOST('search_priority', 'alpha');
$search_internal_tag = GETPOST('search_internal_tag', 'alpha');
$search_external_tag = GETPOST('search_external_tag', 'alpha');
$search_level_tag = GETPOST('search_level_tag', 'int');
$search_author = GETPOST('search_author', 'alpha');
$search_modified_by = GETPOST('search_modified_by', 'alpha');
$search_date_created = GETPOST('search_date_created', 'alpha');
$search_date_modified = GETPOST('search_date_modified', 'alpha');
$search_status = GETPOST('search_status', 'alpha');
$optioncss = GETPOST('optioncss', 'alpha');
if ($search_event_on_full_day === "") $search_event_on_full_day = -1;
if ($search_level_tag === "") $search_level_tag = -1;

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$contextpage = $list_mode == 0 ? 'requestmanagereventlist' : ($list_mode == 2 ? 'requestmanagertimeline' : 'requestmanagertimelineeventlist');
$hookmanager->initHooks(array($contextpage));

require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
$extrafields_actioncomm = new ExtraFields($db);
$extrafields_message = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels_actioncomm = $extrafields_actioncomm->fetch_name_optionals_label('actioncomm');
$search_array_options = $extrafields_actioncomm->getOptionalsFromPost($extralabels, '', 'search_');
$extralabels_message = $extrafields_message->fetch_name_optionals_label('requestmanager_message');
$search_array_options_message = $extrafields_message->getOptionalsFromPost($extralabels_message, '', 'search_m_');
$search_array_options = array_merge($search_array_options, $search_array_options_message);

// Event confidentiality
//--------------------------------------
if ($conf->eventconfidentiality->enabled) {
    $langs->load('eventconfidentiality@eventconfidentiality');
}

$arrayfields = array(
    'fk_request' => array('label' => $langs->trans("RequestManagerRequest"), 'checked' => 1, 'ec_mode' => 1),
    'ac.elementtype' => array('label' => $langs->trans("Origin"), 'checked' => 1, 'ec_mode' => 1),
    's.nom' => array('label' => $langs->trans("ThirdParty"), 'checked' => 1, 'ec_mode' => 1),
    'ac.id' => array('label' => $langs->trans("Ref"), 'checked' => 1, 'ec_mode' => 1),
    'ac.fk_action' => array('label' => $langs->trans("Type"), 'checked' => 1, 'ec_mode' => 1),
    'ac.label' => array('label' => $langs->trans("Title"), 'checked' => 1, 'ec_mode' => 0),
    'ac.note' => array('label' => $langs->trans("Description"), 'checked' => 1, 'ec_mode' => 1),
    'ac.fulldayevent' => array('label' => $langs->trans("EventOnFullDay"), 'checked' => 0, 'ec_mode' => 0),
    'ac.datep' => array('label' => $langs->trans("DateStart"), 'checked' => 1, 'ec_mode' => 0),
    'ac.datep2' => array('label' => $langs->trans("DateEnd"), 'checked' => 1, 'ec_mode' => 0),
    'ac.location' => array('label' => $langs->trans("Location"), 'checked' => 0, 'enabled' => empty($conf->global->AGENDA_DISABLE_LOCATION), 'ec_mode' => 1),
    'ac.fk_element' => array('label' => $langs->trans("LinkedObject"), 'checked' => 0, 'ec_mode' => 0),
    'ac.fk_user_action' => array('label' => $langs->trans("ActionsOwnedByShort"), 'checked' => 0, 'ec_mode' => 1),
//        'ac.userassigned' => array('label' => $langs->trans("ActionAssignedTo"), 'checked' => 0, 'ec_mode' => 1),
    'ac.fk_user_done' => array('label' => $langs->trans("ActionDoneBy"), 'checked' => 0, 'enabled' => $conf->global->AGENDA_ENABLE_DONEBY, 'ec_mode' => 1),
    'ac.fk_project' => array('label' => $langs->trans("Project"), 'checked' => 0, 'ec_mode' => 1),
    'ac.priority' => array('label' => $langs->trans("Priority"), 'checked' => 0, 'ec_mode' => 1),
    'internal_tags' => array('label' => $langs->trans("EventConfidentialityTagInterneLabel"), 'checked' => 0, 'enabled' => $conf->eventconfidentiality->enabled && $user->rights->eventconfidentiality->manage, 'position' => 10, 'ec_mode' => 1),
    'external_tags' => array('label' => $langs->trans("EventConfidentialityTagExterneLabel"), 'checked' => 0, 'enabled' => $conf->eventconfidentiality->enabled && $user->rights->eventconfidentiality->manage, 'position' => 10, 'ec_mode' => 1),
    'ac.fk_user_author' => array('label' => $langs->trans("Author"), 'checked' => 0, 'position' => 10, 'ec_mode' => 1),
    'ac.fk_user_mod' => array('label' => $langs->trans("ModifiedBy"), 'checked' => 0, 'position' => 10, 'ec_mode' => 1),
    'ac.datec' => array('label' => $langs->trans("DateCreation"), 'checked' => 1, 'position' => 500, 'ec_mode' => 0),
    'ac.tms' => array('label' => $langs->trans("DateModificationShort"), 'checked' => 0, 'position' => 500, 'ec_mode' => 0),
    'ac.percent' => array('label' => $langs->trans("Status"), 'checked' => 1, 'position' => 1000, 'ec_mode' => 1),
);
// Extra fields
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) {
    foreach ($extrafields->attribute_label as $key => $val) {
        $arrayfields["ef." . $key] = array('label' => $extrafields->attribute_label[$key], 'checked' => $extrafields->attribute_list[$key], 'position' => $extrafields->attribute_pos[$key], 'enabled' => $extrafields->attribute_perms[$key], 'ec_mode' => 1);
    }
}
// Extra fields message
if (is_array($extrafields_message->attribute_label) && count($extrafields_message->attribute_label)) {
    foreach ($extrafields_message->attribute_label as $key => $val) {
        $arrayfields["efm." . $key] = array('label' => $extrafields_message->attribute_label[$key], 'checked' => $extrafields_message->attribute_list[$key], 'position' => $extrafields_message->attribute_pos[$key], 'enabled' => $extrafields_message->attribute_perms[$key], 'ec_mode' => 1);
    }
}
