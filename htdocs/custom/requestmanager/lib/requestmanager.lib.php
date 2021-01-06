<?php
/* Copyright (C) 2018      Open-DSI             <support@open-dsi.fr>
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
 *	\file       htdocs/requestmanager/lib/requestmanager.lib.php
 * 	\ingroup	requestmanager
 *	\brief      Functions for the module Request Manager
 */

/**
 * Prepare array with list of tabs for admin
 *
 * @return  array				Array of tabs to show
 */
function requestmanager_admin_prepare_head()
{
    global $langs, $conf, $user;
    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath("/requestmanager/admin/setup.php", 1);
    $head[$h][1] = $langs->trans("Parameters");
    $head[$h][2] = 'settings';
    $h++;

    $head[$h][0] = dol_buildpath("/requestmanager/admin/dictionaries.php", 1);
    $head[$h][1] = $langs->trans("Dictionary");
    $head[$h][2] = 'dictionaries';
    $h++;

    $head[$h][0] = dol_buildpath("/requestmanager/admin/requestmanager_extrafields.php", 1);
    $head[$h][1] = $langs->trans("RequestManagerExtraFields");
    $head[$h][2] = 'requestmanager_attributes';
    $h++;

    $head[$h][0] = dol_buildpath("/requestmanager/admin/requestmanager_message_extrafields.php", 1);
    $head[$h][1] = $langs->trans("RequestManagerMessageExtraFields");
    $head[$h][2] = 'requestmanager_message_attributes';
    $h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
    complete_head_from_modules($conf, $langs, null, $head, $h, 'requestmanager_admin');

    $head[$h][0] = dol_buildpath("/requestmanager/admin/about.php", 1);
    $head[$h][1] = $langs->trans("About");
    $head[$h][2] = 'about';
    $h++;

    $head[$h][0] = dol_buildpath("/requestmanager/admin/changelog.php", 1);
    $head[$h][1] = $langs->trans("OpenDsiChangeLog");
    $head[$h][2] = 'changelog';
    $h++;

    complete_head_from_modules($conf, $langs, null, $head, $h, 'requestmanager_admin', 'remove');

    return $head;
}

/**
 * Return array of tabs to used on pages for request manager cards.
 *
 * @param 	RequestManager	    $object		Object request manager shown
 * @return 	array				            Array of tabs
 */
function requestmanager_prepare_head(RequestManager $object)
{
    global $db, $langs, $conf, $user;
    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath('/requestmanager/card.php', 1) . '?id=' . $object->id;
    $head[$h][1] = $langs->trans("Card");
    $head[$h][2] = 'card';
    $h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
    complete_head_from_modules($conf, $langs, $object, $head, $h, 'requestmanager');

    if ($user->societe_id == 0) {
        // Attached files
        require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
        require_once DOL_DOCUMENT_ROOT . '/core/class/link.class.php';
        $upload_dir = $conf->requestmanager->multidir_output[$object->entity] . "/" . $object->ref;
        $nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
        $nbLinks = Link::count($db, $object->element, $object->id);

        if ($user->rights->requestmanager->read_file) {
            $head[$h][0] = dol_buildpath('/requestmanager/document.php', 1) . '?id=' . $object->id;
            $head[$h][1] = $langs->trans("Documents");
            if (($nbFiles + $nbLinks) > 0) $head[$h][1] .= ' <span class="badge">' . ($nbFiles + $nbLinks) . '</span>';
            $head[$h][2] = 'document';
            $h++;
        }
    }

    complete_head_from_modules($conf, $langs, $object, $head, $h, 'requestmanager', 'remove');

    return $head;
}

/**
 *  Show html area for list of events of the request
 *
 * @param	RequestManager	$requestmanager     Request Manager object
 * @return	void
 */
function requestmanager_show_events(&$requestmanager)
{
    global $conf, $langs, $db, $user, $hookmanager;
    global $action, $form;

    $list_mode = GETPOST('list_mode', 'int');
    if ($list_mode === "") $list_mode = $_SESSION['rm_list_mode'];
    if ($list_mode === "" || !isset($list_mode)) $list_mode = !empty($conf->global->REQUESTMANAGER_DEFAULT_LIST_MODE) ? $conf->global->REQUESTMANAGER_DEFAULT_LIST_MODE : 0;
    if ($list_mode == 2) return 0;
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
    $search_ref = GETPOST('search_ref', 'alpha');
    $search_origin = GETPOST('search_origin', 'array');
    $search_thirdparty = GETPOST('search_thirdparty', 'alpha');
    $search_type = GETPOST('search_type', 'array');
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
    $contextpage = $list_mode == 0 ? 'requestmanagereventlist' : 'requestmanagertimelineeventlist';
    $hookmanager->initHooks(array($contextpage));

    require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
    $extrafields = new ExtraFields($db);
    $extrafields_message = new ExtraFields($db);

    // fetch optionals attributes and labels
    $extralabels = $extrafields->fetch_name_optionals_label('actioncomm');
    $search_array_options = $extrafields->getOptionalsFromPost($extralabels, '', 'search_');
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

    require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
    $actioncomm = new ActionComm($db);    // To be passed as parameter of executeHooks that need

    /*
     * Actions
     */

    $parameters = array('requestmanager' => &$requestmanager);
    $reshook = $hookmanager->executeHooks('doActions', $parameters, $actioncomm, $action);    // Note that $action and $object may have been modified by some hooks
    if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

    include DOL_DOCUMENT_ROOT . '/core/actions_changeselectedfields.inc.php';

    // Do we click on purge search criteria ?
    if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
    {
        $search_only_linked_to_request = 1;
        $search_include_event_other_request = 0;
        $search_include_linked_event_to_children_request = 1;
        $search_ref = '';
        $search_origin = array();
        $search_thirdparty = '';
        $search_type = array();
        $search_title = '';
        $search_description = '';
        $search_event_on_full_day = -1;
        $search_date_start = '';
        $search_date_end = '';
        $search_location = '';
        $search_owned_by = '';
        //$search_assigned_to = '';
        $search_done_by = '';
        $search_project = '';
        $search_priority = '';
        $search_internal_tag = '';
        $search_external_tag = '';
        $search_level_tag = -1;
        $search_author = '';
        $search_modified_by = '';
        $search_date_created = '';
        $search_date_modified = '';
        $search_status = '';
        $search_array_options = array();
    }
    if ($search_only_linked_to_request === '') $search_only_linked_to_request = 1;
    if ($search_include_event_other_request === '') $search_include_event_other_request = 0;
    if ($search_include_linked_event_to_children_request === '') $search_include_linked_event_to_children_request = 1;


    /*
     * View
     */

    $now = dol_now();

    require_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
    $formactions = new FormActions($db);

    // Assigned user
    //$sql ="SELECT fk_actioncomm, element_type, fk_element, answer_status, mandatory, transparency";
    //$sql.=" FROM ".MAIN_DB_PREFIX."actioncomm_resources";
    //$sql.=" WHERE element_type = 'user' AND fk_actioncomm = ".$this->id;
    //while ($obj = $this->db->fetch_object($resql2))
    //{
    //	if ($obj->fk_element > 0) $this->userassigned[$obj->fk_element]=array('id'=>$obj->fk_element, 'mandatory'=>$obj->mandatory, 'answer_status'=>$obj->answer_status, 'transparency'=>$obj->transparency);
    //	if (empty($this->userownerid)) $this->userownerid=$obj->fk_element;	// If not defined (should not happened, we fix this)
    //}

    // Get request ids (parent + children)
    $request_children_ids = $requestmanager->getAllChildrenRequest();
    if ($search_include_linked_event_to_children_request) {
        $request_ids = array_merge($request_children_ids, array($requestmanager->id));
        $request_ids = implode(',', $request_ids);
    } else {
        $request_ids = $requestmanager->id;
    }
    $request_children_ids = implode(',', $request_children_ids);

    $sql = "SELECT ac.id,";
    $sql .= " IF(ac.elementtype='requestmanager', ac.fk_element, IF(ee.sourcetype='requestmanager', ee.fk_source, IF(ee.targettype='requestmanager', ee.fk_target, NULL))) as fk_request,";
    $sql .= " ac.id as ref,";
    $sql .= " ac.datep,";
    $sql .= " ac.datep2,";
    $sql .= " ac.datec,";
    $sql .= " ac.tms as datem,";
    $sql .= " ac.code, ac.label, ac.note,";
    $sql .= " ac.fk_project,";
    $sql .= " ac.fk_user_author, ac.fk_user_mod,";
    $sql .= " ac.fk_user_action, ac.fk_user_done,";
    $sql .= " ac.percent as percentage,";
    $sql .= " ac.fk_element, ac.elementtype,";
    $sql .= " ac.priority, ac.fulldayevent, ac.location,";
    $sql .= " rmm.notify_assigned, rmm.notify_requesters, rmm.notify_watchers,";
    $sql .= " GROUP_CONCAT(DISTINCT rmmkb.fk_knowledge_base SEPARATOR ',') AS knowledge_base_ids,";
    $sql .= " s.rowid as soc_id, s.client as soc_client, s.nom as soc_name, s.name_alias as soc_name_alias,";
    $sql .= " cac.id as type_id, cac.code as type_code, cac.libelle as type_label, cac.color as type_color, cac.picto as type_picto,";
    $sql .= " uo.firstname as userownerfirstname, uo.lastname as userownerlastname, uo.email as userowneremail,";
    $sql .= " ud.firstname as userdonefirstname, ud.lastname as userdonelastname, ud.email as userdoneemail,";
    $sql .= " ua.firstname as userauthorfirstname, ua.lastname as userauthorlastname, ua.email as userauthoremail, ua.photo as userauthorphoto, ua.gender as userauthorgender,";
    $sql .= " um.firstname as usermodfirstname, um.lastname as usermodlastname, um.email as usermodemail,";
    $sql .= " p.ref as projectref, p.title as projecttitle, p.public as projectpublic, p.fk_statut as projectstatus, p.datee as projectdatee";
    // Event confidentiality support
    if ($conf->eventconfidentiality->enabled) {
        dol_include_once('/eventconfidentiality/class/eventconfidentiality.class.php');
        $sql .= ", MIN(IFNULL(ecm.mode, " . EventConfidentiality::MODE_HIDDEN . ")) as ec_mode, tags_info.internal_tags_info, tags_info.external_tags_info";
    }
    // Add fields from extrafields
    foreach ($extrafields->attribute_label as $key => $val) $sql .= ($extrafields->attribute_type[$key] != 'separate' ? ",ef." . $key . ' as options_' . $key : '');
    foreach ($extrafields_message->attribute_label as $key => $val) $sql .= ($extrafields_message->attribute_type[$key] != 'separate' ? ",efm." . $key . ' as m_options_' . $key : '');
    // Add fields from hooks
    $parameters = array();
    $reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters);    // Note that $action and $object may have been modified by hook
    $sql .= $hookmanager->resPrint;
    $sql .= " FROM " . MAIN_DB_PREFIX . "actioncomm as ac ";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "element_element as ee";
    $element_correspondance = "(" . // Todo a completer si il y a d'autres correspondances
        "IF(ac.elementtype = 'contract', 'contrat', " .
        " IF(ac.elementtype = 'invoice', 'facture', " .
        "  IF(ac.elementtype = 'order', 'commande', " .
        "   ac.elementtype)))" .
        ")";
    $sql .= " ON (ee.sourcetype = " . $element_correspondance . " AND ee.fk_source = ac.fk_element) OR (ee.targettype = " . $element_correspondance . " AND ee.fk_target = ac.fk_element)";
    // Event confidentiality support
    if ($conf->eventconfidentiality->enabled) {
        if ($user->rights->eventconfidentiality->manage && ($search_internal_tag || $search_external_tag || $search_level_tag >= 0)) {
            $sql .= " INNER JOIN (";
            $sql .= "   SELECT ecm.fk_actioncomm AS event_id";
            $sql .= "   FROM " . MAIN_DB_PREFIX . "eventconfidentiality_mode AS ecm";
            $sql .= "   LEFT JOIN " . MAIN_DB_PREFIX . "c_eventconfidentiality_tag AS cecti ON ecm.fk_c_eventconfidentiality_tag = cecti.rowid AND (cecti.external != 1 OR cecti.external IS NULL)";
            $sql .= "   LEFT JOIN " . MAIN_DB_PREFIX . "c_eventconfidentiality_tag AS cecte ON ecm.fk_c_eventconfidentiality_tag = cecte.rowid AND cecti.external = 1";
            $search_tag_where = array();
            if ($search_internal_tag) $search_tag_where[] = natural_search("cecti.label", $search_internal_tag, 0, 1);
            if ($search_external_tag) $search_tag_where[] = natural_search("cecte.label", $search_external_tag, 0, 1);
            if ($search_level_tag >= 0) $search_tag_where[] = 'ecm.mode = ' . $search_level_tag;
            $sql .= "   WHERE " . implode(' AND ', $search_tag_where);
            $sql .= "   GROUP BY ecm.fk_actioncomm";
            $sql .= " ) AS search_tag ON search_tag.event_id = ac.id";
        }
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "eventconfidentiality_mode AS ecm ON ecm.fk_actioncomm = ac.id";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_eventconfidentiality_tag AS cect ON ecm.fk_c_eventconfidentiality_tag = cect.rowid";
        $sql .= " LEFT JOIN (";
        $sql .= "   SELECT ecm.fk_actioncomm AS event_id";
        $sql .= "   , GROUP_CONCAT(DISTINCT IF(cecti.rowid IS NOT NULL, CONCAT(cecti.rowid, ':', ecm.mode), NULL) SEPARATOR ',') AS internal_tags_info";
        $sql .= "   , GROUP_CONCAT(DISTINCT IF(cecte.rowid IS NOT NULL, CONCAT(cecte.rowid, ':', ecm.mode), NULL) SEPARATOR ',') AS external_tags_info";
        $sql .= "   FROM " . MAIN_DB_PREFIX . "eventconfidentiality_mode AS ecm";
        $sql .= "   LEFT JOIN " . MAIN_DB_PREFIX . "c_eventconfidentiality_tag AS cecti ON ecm.fk_c_eventconfidentiality_tag = cecti.rowid AND (cecti.external != 1 OR cecti.external IS NULL)";
        $sql .= "   LEFT JOIN " . MAIN_DB_PREFIX . "c_eventconfidentiality_tag AS cecte ON ecm.fk_c_eventconfidentiality_tag = cecte.rowid AND cecti.external = 1";
        $sql .= "   GROUP BY ecm.fk_actioncomm";
        $sql .= " ) AS tags_info ON tags_info.event_id = ac.id";
    }
    if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "actioncomm_extrafields as ef on (ac.id = ef.fk_object)";
    if (is_array($extrafields_message->attribute_label) && count($extrafields_message->attribute_label)) $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "requestmanager_message_extrafields as efm on (ac.id = efm.fk_object)";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "requestmanager_message as rmm ON ac.id = rmm.fk_actioncomm";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "requestmanager_message_knowledge_base as rmmkb ON ac.id = rmmkb.fk_actioncomm";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_actioncomm as cac ON cac.id = ac.fk_action";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as s ON s.rowid = ac.fk_soc";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as uo ON uo.rowid = ac.fk_user_action";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as ud ON ud.rowid = ac.fk_user_done";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as ua ON ua.rowid = ac.fk_user_author";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as um ON um.rowid = ac.fk_user_mod";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "projet as p ON p.rowid = ac.fk_project";
    // Add 'from' from hooks
    $parameters = array();
    $reshook = $hookmanager->executeHooks('printFieldListFrom', $parameters);    // Note that $action and $object may have been modified by hook
    $soc_ids = array_merge(array($requestmanager->socid_origin), array($requestmanager->socid), array($requestmanager->socid_benefactor));
    $sql .= ' WHERE ac.fk_soc IN (' . implode(',', $soc_ids) . ')';
    $sql .= ' AND ac.entity IN (' . getEntity('agenda') . ')';
    if ($search_only_linked_to_request) {
        $sql .= " AND IF(ac.elementtype='requestmanager', ac.fk_element, IF(ee.targettype='requestmanager', ee.fk_target, IF(ee.sourcetype='requestmanager', ee.fk_source, NULL))) IN(" . (!empty($request_ids) ? $request_ids : '-1') . ")";
    } else {
        if (!$search_include_event_other_request) {
            $sql .= " AND (ac.elementtype != 'requestmanager' OR ac.fk_element IN (" . (!empty($request_ids) ? $request_ids : '-1') . "))";
        }
        if (!$search_include_linked_event_to_children_request) {
            $sql .= " AND IF(ac.elementtype='requestmanager', ac.fk_element, IF(ee.targettype='requestmanager', ee.fk_target, IF(ee.sourcetype='requestmanager', ee.fk_source, NULL))) NOT IN (" . (!empty($request_children_ids) ? $request_children_ids : '-1') . ")";
        }
    }
    if ($search_ref) $sql .= natural_search('ac.id', $search_ref);
    if (!empty($search_origin)) $sql .= " AND ac.elementtype IN ('" . implode("','", $search_origin) . "')";
    if ($search_thirdparty) $sql .= natural_search(array('s.nom', 's.name_alias'), $search_thirdparty);
    if (!empty($search_type)) {
        $cac_sql = array();
        $search_type_tmp = $search_type;
        if (in_array('AC_NON_AUTO', $search_type_tmp) || in_array('AC_OTH', $search_type_tmp)) {
            $cac_sql[] = "cac.type != 'systemauto'";
            $search_type_tmp = array_diff($search_type_tmp, array('AC_NON_AUTO', 'AC_OTH'));
        }
        if (in_array('AC_ALL_AUTO', $search_type_tmp) || in_array('AC_OTH_AUTO', $search_type_tmp)) {
            $cac_sql[] = "cac.type = 'systemauto'";
            $search_type_tmp = array_diff($search_type_tmp, array('AC_ALL_AUTO', 'AC_OTH_AUTO'));
        }
        $cac_sql[] = "cac.code IN ('" . implode("','", $search_type_tmp) . "')";
        $sql .= " AND (" . implode(" OR ", $cac_sql) . ")";
    }
    if ($search_title) $sql .= natural_search('ac.label', $search_title);
    if ($search_description) $sql .= natural_search('ac.note', $search_title);
    if ($search_event_on_full_day >= 0) $sql .= ' AND ac.fulldayevent = ' . $search_event_on_full_day;
    if ($search_date_start) $sql .= natural_search('ac.datep', $search_date_start, 1);
    if ($search_date_end) $sql .= natural_search('ac.datep2', $search_date_end, 1);
    if ($search_location) $sql .= natural_search("ac.location", $search_location);
    if ($search_owned_by) $sql .= natural_search(array('uo.firstname', 'uo.lastname'), $search_owned_by);
    //if ($search_assigned_to) $sql .= natural_search("u.login", $search_assigned_to);
    if ($search_done_by) $sql .= natural_search(array('ud.firstname', 'ud.lastname'), $search_done_by);
    if ($search_project) $sql .= natural_search("p.ref", $search_project);
    if ($search_priority) $sql .= natural_search("ac.priority", $search_priority, 1);
    if ($search_author) $sql .= natural_search(array('ua.firstname', 'ua.lastname'), $search_author);
    if ($search_modified_by) $sql .= natural_search(array('um.firstname', 'um.lastname'), $search_modified_by);
    if ($search_date_created) $sql .= natural_search("ac.datec", $search_date_created, 1);
    if ($search_date_modified) $sql .= natural_search("ac.tms", $search_date_modified, 1);
    if ($search_status == '0') {
        $sql .= " AND ac.percent = 0";
    }
    if ($search_status == '-1') {    // Not applicable
        $sql .= " AND ac.percent = -1";
    }
    if ($search_status == '50') {    // Running already started
        $sql .= " AND (ac.percent > 0 AND ac.percent < 100)";
    }
    if ($search_status == '100') {
        $sql .= " AND (ac.percent = 100 OR (ac.percent = -1 AND ac.datep2 <= '" . $db->idate($now) . "'))";
    }
    if ($search_status == 'todo') {
        $sql .= " AND ((ac.percent >= 0 AND ac.percent < 100) OR (ac.percent = -1 AND ac.datep2 > '" . $db->idate($now) . "'))";
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
    // Add where from extra fields message
    foreach ($search_array_options as $key => $val) {
        $crit = $val;
        $tmpkey = preg_replace('/search_m_options_/', '', $key);
        $typ = $extrafields->attribute_type[$tmpkey];
        $mode = 0;
        if (in_array($typ, array('int', 'double', 'real'))) $mode = 1;                                // Search on a numeric
        if (in_array($typ, array('sellist')) && $crit != '0' && $crit != '-1') $mode = 2;            // Search on a foreign key int
        if ($crit != '' && (!in_array($typ, array('select', 'sellist')) || $crit != '0')) {
            $sql .= natural_search('efm.' . $tmpkey, $crit, $mode);
        }
    }
    // Event confidentiality support
    if ($conf->eventconfidentiality->enabled && !$user->rights->eventconfidentiality->manage) {
        $eventconfidentiality = new EventConfidentiality($db);
        $tags_list = $eventconfidentiality->getConfidentialTagsOfUser($user);
        if (!is_array($tags_list)) {
            dol_print_error('', $eventconfidentiality->error, $eventconfidentiality->errors);
        }
        if ($user->socid > 0) {
            $sql .= ' AND cect.external = 1';
        } else {
            $sql .= ' AND (cect.external IS NULL OR cect.external = 0)';
        }
        $sql .= ' AND ecm.fk_c_eventconfidentiality_tag IN (' . (count($tags_list) > 0 ? implode(',', $tags_list) : -1) . ')';
    }
    // Add where from hooks
    $parameters = array();
    $reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters);    // Note that $action and $object may have been modified by hook
    $sql .= $hookmanager->resPrint;

    $sql .= " GROUP BY ac.id";
    // Event confidentiality support
    if ($conf->eventconfidentiality->enabled && !$user->rights->eventconfidentiality->manage) {
        $sql .= ' HAVING ec_mode != ' . EventConfidentiality::MODE_HIDDEN;
    }
    $sql .= $db->order($sortfield.', ac.id', $sortorder.','.$sortorder);

    // Count total nb of records
    $nbtotalofrecords = '';
    if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
        $result = $db->query($sql);
        $nbtotalofrecords = $db->num_rows($result);
    }

    $sql .= $db->plimit($limit + 1, $offset);

    $resql = $db->query($sql);
    if ($resql) {
        $title = $langs->trans('RequestManagerListOfEvents');

        $num = $db->num_rows($resql);

        $param = '&id=' . urlencode($requestmanager->id);
        if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit=' . urlencode($limit);
        $param2 = $param;
        if ($search_only_linked_to_request !== '') $param .= '&search_only_linked_to_request=' . urlencode($search_only_linked_to_request);
        if ($search_include_event_other_request !== '') $param .= '&search_include_event_other_request=' . urlencode($search_include_event_other_request);
        if ($search_include_linked_event_to_children_request !== '') $param .= '&search_include_linked_event_to_children_request=' . urlencode($search_include_linked_event_to_children_request);
        if ($search_ref) $param .= '&search_ref=' . urlencode($search_ref);
        if (!empty($search_origin)) $param .= '&search_origin=' . urlencode($search_origin);
        if ($search_thirdparty) $param .= '&search_thirdparty=' . urlencode($search_thirdparty);
        if ($search_type) $param .= '&search_type=' . urlencode($search_type);
        if ($search_title) $param .= '&search_title=' . urlencode($search_title);
        if ($search_description) $param .= '&search_description=' . urlencode($search_description);
        if ($search_event_on_full_day >= 0) $param .= '&search_event_on_full_day=' . urlencode($search_event_on_full_day);
        if ($search_date_start) $param .= '&search_date_start=' . urlencode($search_date_start);
        if ($search_date_end) $param .= '&search_date_end=' . urlencode($search_date_end);
        if ($search_location) $param .= '&search_location=' . urlencode($search_location);
        if ($search_owned_by) $param .= '&search_owned_by=' . urlencode($search_owned_by);
        //if ($search_assigned_to) $param .= '&search_assigned_to=' . urlencode($search_assigned_to);
        if ($search_done_by) $param .= '&search_done_by=' . urlencode($search_done_by);
        if ($search_project) $param .= '&search_project=' . urlencode($search_project);
        if ($search_priority) $param .= '&search_priority=' . urlencode($search_priority);
        if ($search_internal_tag) $param .= '&search_internal_tag=' . urlencode($search_internal_tag);
        if ($search_external_tag) $param .= '&search_external_tag=' . urlencode($search_external_tag);
        if ($search_level_tag) $param .= '&search_level_tag=' . urlencode($search_level_tag);
        if ($search_author) $param .= '&search_author=' . urlencode($search_author);
        if ($search_modified_by) $param .= '&search_modified_by=' . urlencode($search_modified_by);
        if ($search_date_created) $param .= '&search_date_created=' . urlencode($search_date_created);
        if ($search_date_modified) $param .= '&search_date_modified=' . urlencode($search_date_modified);
        if ($search_status >= 0) $param .= '&search_status=' . urlencode($search_status);
        if ($optioncss != '') $param .= '&optioncss=' . urlencode($optioncss);
        // Add $param from extra fields
        foreach ($search_array_options as $key => $val) {
            $crit = $val;
            $tmpkey = preg_replace('/search_options_/', '', $key);
            if ($val != '') $param .= '&search_options_' . $tmpkey . '=' . urlencode($val);
        }
        // Add $param from extra fields message
        foreach ($search_array_options as $key => $val) {
            $crit = $val;
            $tmpkey = preg_replace('/search_m_options_/', '', $key);
            if ($val != '') $param .= '&search_m_options_' . $tmpkey . '=' . urlencode($val);
        }

        // Button for change the view mode of the list
        $morehtml = '';
        $morehtml .= '<a class="' . ($list_mode == 0 ? 'butActionRefused' : 'butAction') . '" href="' . $_SERVER['PHP_SELF'] . '?list_mode=0&page=' . urlencode($page) . $param . '#events-balise">';
        $morehtml .= $langs->trans("RequestManagerListMode");
        $morehtml .= '</a>';
        $morehtml .= '<a class="' . ($list_mode == 1 ? 'butActionRefused' : 'butAction') . '" href="' . $_SERVER['PHP_SELF'] . '?list_mode=1&page=' . urlencode($page) . $param2 . '#events-balise">';
        $morehtml .= $langs->trans("RequestManagerTimeLineListMode");
        $morehtml .= '</a>';
        $morehtml .= '<a class="' . ($list_mode == 2 ? 'butActionRefused' : 'butAction') . '" href="' . $_SERVER['PHP_SELF'] . '?list_mode=2&page=' . urlencode($page) . $param2 . '#timeline-balise">';
        $morehtml .= $langs->trans("RequestManagerTimeLineMode");
        $morehtml .= '</a>';

        if ($list_mode > 0) $param .= '&list_mode=' . urlencode($list_mode);

        print '<div id="events-balise"></div>'."\n";

        // Lignes des champs de filtre
        print '<form method="POST" id="searchFormList" action="' . $_SERVER["PHP_SELF"] . '?id=' . $requestmanager->id . '#events-container">';
        if ($optioncss != '') print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
        print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
        print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
        print '<input type="hidden" name="action" value="list">';
        print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
        print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
        print '<input type="hidden" name="page" value="' . $page . '">';

        print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $morehtml, $num, $nbtotalofrecords, '', 0, '', '', $limit);

        // Mode timeline of message
        $moreforfilter = '';

        // Filter on the origin type
        $moreforfilter .= '<div class="divsearchfield">';
        $moreforfilter .= $langs->trans('Origin') . ' : ';
        $elements = requestmanager_get_all_element_of_events($requestmanager->socid);
        $elements_array = array();
        $elements_picto_array = array();
        foreach ($elements as $key => $element) {
            $elements_array[$key] = img_picto($element['label'], $element['picto']) . ' ' . $element['label'];
            $elements_picto_array[$key] = img_picto($element['label'], $element['picto'], 'style="width: auto; height: 20px;"');
        }
        $moreforfilter .= $form->multiselectarray('search_origin', $elements_array, $search_origin, 0, 0, ' minwidth300');
        $moreforfilter .= '</div>';

        // Filter on the type of the event
        $moreforfilter .= '<div class="divsearchfield">';
        $moreforfilter .= $langs->trans('Type') . ' : ';
        $moreforfilter .= $formactions->select_type_actions($search_type, "search_type", '', (empty($conf->global->AGENDA_USE_EVENT_TYPE) ? 1 : -1), 0, 1, 1);
        $moreforfilter .= <<<SCRIPT
    <script type="text/javascript" language="javascript">
        $(document).ready(function () {
            $('#search_type').removeClass('centpercent');
            $('#search_type').addClass('minwidth300');
        });
    </script>
SCRIPT;
        $moreforfilter .= '</div>';

        // Event confidentiality support
        if ($conf->eventconfidentiality->enabled) {
            // Filter of level confidentiality of a tag
            $moreforfilter .= '<div class="divsearchfield">';
            $moreforfilter .= $langs->trans('EventConfidentialityMode') . ' : ';
            $confidentiality_levels = array(
                0 => $langs->trans('EventConfidentialityModeVisible'),
                1 => $langs->trans('EventConfidentialityModeBlurred'),
            );
            $moreforfilter .= $form->selectarray('search_level_tag', $confidentiality_levels, $search_level_tag, 1);
            $moreforfilter .= '</div>';
        }

        // Filter: include only event linked to the request
        $moreforfilter .= '<div class="divsearchfield">';
        $moreforfilter .= $langs->trans('RequestManagerOnlyLinkedObjectToThisRequest') . ' : ';
        $moreforfilter .= $form->selectyesno('search_only_linked_to_request', $search_only_linked_to_request, 1);
        $moreforfilter .= '</div>';
        $moreforfilter .= <<<SCRIPT
    <script type="text/javascript" language="javascript">
        $(document).ready(function () {
            update_div_search_include_event_other_request();
            $('#search_only_linked_to_request').on('change', function() {
                update_div_search_include_event_other_request();
            });

            function update_div_search_include_event_other_request() {
                if ($('#search_only_linked_to_request').val() > 0)
                    $('#div_search_include_event_other_request').hide();
                else
                    $('#div_search_include_event_other_request').show();
            }
        });
    </script>
SCRIPT;

        // Filter: include event linked to the children request
        $moreforfilter .= '<div class="divsearchfield">';
        $moreforfilter .= $langs->trans('RequestManagerIncludeLinkedEventToChildrenOfThisRequest') . ' : ';
        $moreforfilter .= $form->selectyesno('search_include_linked_event_to_children_request', $search_include_linked_event_to_children_request, 1);
        $moreforfilter .= '</div>';

        // Filter: include event of other request
        $moreforfilter .= '<div class="divsearchfield" id="div_search_include_event_other_request">';
        $moreforfilter .= $langs->trans('RequestManagerIncludeEventOfOtherRequest') . ' : ';
        $moreforfilter .= $form->selectyesno('search_include_event_other_request', $search_include_event_other_request, 1);
        $moreforfilter .= '</div>';

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

        if ($list_mode == 0) {
            print '<div class="div-table-responsive">';
        }
        print '<table class="tagtable liste' . ($moreforfilter ? " listwithfilterbefore" : "") . '">' . "\n";

        print '<tr class="liste_titre_filter">';
        // Request
        if (!empty($arrayfields['fk_request']['checked'])) {
            print '<td class="liste_titre">';
            print '</td>';
        }
        // Origin
        if (!empty($arrayfields['ac.elementtype']['checked'])) {
            print '<td class="liste_titre">';
            print '</td>';
        }
        // ThirdParty
        if (!empty($arrayfields['s.nom']['checked'])) {
            print '<td class="liste_titre">';
            print '<input class="flat" size="6" type="text" name="search_thirdparty" value="' . dol_escape_htmltag($search_thirdparty) . '">';
            print '</td>';
        }
        // Ref
        if (!empty($arrayfields['ac.id']['checked'])) {
            print '<td class="liste_titre">';
            print '<input class="flat" size="6" type="text" name="search_ref" value="' . dol_escape_htmltag($search_ref) . '">';
            print '</td>';
        }
        // Type
        if (!empty($arrayfields['ac.fk_action']['checked'])) {
            print '<td class="liste_titre" align="left">';
            print '</td>';
        }
        // Title
        if (!empty($arrayfields['ac.label']['checked'])) {
            print '<td class="liste_titre">';
            print '<input class="flat" size="8" type="text" name="search_title" value="' . dol_escape_htmltag($search_title) . '">';
            print '</td>';
        }
        // Description
        if (!empty($arrayfields['ac.note']['checked'])) {
            print '<td class="liste_titre">';
            print '<input class="flat" size="8" type="text" name="search_description" value="' . dol_escape_htmltag($search_description) . '">';
            print '</td>';
        }
        // Event On Full Day
        if (!empty($arrayfields['ac.fulldayevent']['checked'])) {
            print '<td class="liste_titre">';
            print $form->selectyesno('search_event_on_full_day', $search_event_on_full_day, 1, 0, 1);
            print '</td>';
        }
        // Date Start
        if (!empty($arrayfields['ac.datep']['checked'])) {
            print '<td class="liste_titre" align="center">';
            print '<input class="flat" size="6" type="text" name="search_date_start" value="' . dol_escape_htmltag($search_date_start) . '">';
            print '</td>';
        }
        // Date End
        if (!empty($arrayfields['ac.datep2']['checked'])) {
            print '<td class="liste_titre" align="center">';
            print '<input class="flat" size="6" type="text" name="search_date_end" value="' . dol_escape_htmltag($search_date_end) . '">';
            print '</td>';
        }
        // Location
        if (!empty($arrayfields['ac.location']['checked'])) {
            print '<td class="liste_titre">';
            print '<input class="flat" size="6" type="text" name="search_location" value="' . dol_escape_htmltag($search_location) . '">';
            print '</td>';
        }
        // Linked Object
        if (!empty($arrayfields['ac.fk_element']['checked'])) {
            print '<td class="liste_titre">';
            print '</td>';
        }
        // Owned By
        if (!empty($arrayfields['ac.fk_user_action']['checked'])) {
            print '<td class="liste_titre" align="center">';
            print '<input class="flat" size="6" type="text" name="search_owned_by" value="' . dol_escape_htmltag($search_owned_by) . '">';
            print '</td>';
        }
        // Assigned To
//        if (!empty($arrayfields['ac.userassigned']['checked'])) {
//            print '<td class="liste_titre" align="center">';
//            print '<input class="flat" size="6" type="text" name="search_assigned_to" value="' . dol_escape_htmltag($search_assigned_to) . '">';
//            print '</td>';
//        }
        // Done By
        if (!empty($arrayfields['ac.fk_user_done']['checked'])) {
            print '<td class="liste_titre" align="center">';
            print '<input class="flat" size="6" type="text" name="search_done_by" value="' . dol_escape_htmltag($search_done_by) . '">';
            print '</td>';
        }
        // Project
        if (!empty($arrayfields['ac.fk_project']['checked'])) {
            print '<td class="liste_titre">';
            print '<input class="flat" size="6" type="text" name="search_project" value="' . dol_escape_htmltag($search_project) . '">';
            print '</td>';
        }
        // Priority
        if (!empty($arrayfields['ac.priority']['checked'])) {
            print '<td class="liste_titre">';
            print '<input class="flat" size="6" type="text" name="search_priority" value="' . dol_escape_htmltag($search_priority) . '">';
            print '</td>';
        }
        // Internal tags
        if (!empty($arrayfields['internal_tags']['checked'])) {
            print '<td class="liste_titre">';
            print '<input class="flat" size="6" type="text" name="search_internal_tag" value="' . dol_escape_htmltag($search_internal_tag) . '">';
            print '</td>';
        }
        // External tags
        if (!empty($arrayfields['external_tags']['checked'])) {
            print '<td class="liste_titre">';
            print '<input class="flat" size="6" type="text" name="search_external_tag" value="' . dol_escape_htmltag($search_external_tag) . '">';
            print '</td>';
        }
        // Author
        if (!empty($arrayfields['ac.fk_user_author']['checked'])) {
            print '<td class="liste_titre" align="center">';
            print '<input class="flat" size="6" type="text" name="search_author" value="' . dol_escape_htmltag($search_author) . '">';
            print '</td>';
        }
        // Modified by
        if (!empty($arrayfields['ac.fk_user_mod']['checked'])) {
            print '<td class="liste_titre" align="center">';
            print '<input class="flat" size="6" type="text" name="search_modified_by" value="' . dol_escape_htmltag($search_modified_by) . '">';
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
        // Extra fields message
        if (is_array($extrafields_message->attribute_label) && count($extrafields_message->attribute_label)) {
            foreach ($extrafields_message->attribute_label as $key => $val) {
                if (!empty($arrayfields["efm." . $key]['checked'])) {
                    $align = $extrafields_message->getAlignFlag($key);
                    $typeofextrafield = $extrafields_message->attribute_type[$key];
                    print '<td class="liste_titre' . ($align ? ' ' . $align : '') . '">';
                    if (in_array($typeofextrafield, array('varchar', 'int', 'double', 'select'))) {
                        $crit = $val;
                        $tmpkey = preg_replace('/search_m_options_/', '', $key);
                        $searchclass = '';
                        if (in_array($typeofextrafield, array('varchar', 'select'))) $searchclass = 'searchstring';
                        if (in_array($typeofextrafield, array('int', 'double'))) $searchclass = 'searchnum';
                        print '<input class="flat' . ($searchclass ? ' ' . $searchclass : '') . '" size="4" type="text" name="search_m_options_' . $tmpkey . '" value="' . dol_escape_htmltag($search_array_options['search_m_options_' . $tmpkey]) . '">';
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
        if (!empty($arrayfields['ac.datec']['checked'])) {
            print '<td class="liste_titre" align="center">';
            print '<input class="flat" size="6" type="text" name="search_date_created" value="' . dol_escape_htmltag($search_date_created) . '">';
            print '</td>';
        }
        // Date modif
        if (!empty($arrayfields['ac.tms']['checked'])) {
            print '<td class="liste_titre" align="center">';
            print '<input class="flat" size="6" type="text" name="search_date_modified" value="' . dol_escape_htmltag($search_date_modified) . '">';
            print '</td>';
        }
        // Status
        if (!empty($arrayfields['ac.percent']['checked'])) {
            print '<td class="liste_titre maxwidthonsmartphone" align="right">';
            $formactions->form_select_status_action('formaction', $search_status, 1, 'search_status', 1, 2);
            print '</td>';
        }
        // Action column
        print '<td class="liste_titre" align="middle" colspan="2">';
        $searchpicto = $form->showFilterButtons();
        print $searchpicto;
        print '</td>';

        print "</tr>\n";

        // Fields title
        print '<tr class="liste_titre">';
        if (!empty($arrayfields['fk_request']['checked'])) print_liste_field_titre($arrayfields['fk_request']['label'], $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder);
        if (!empty($arrayfields['ac.elementtype']['checked'])) print_liste_field_titre($arrayfields['ac.elementtype']['label'], $_SERVER["PHP_SELF"], 'ac.elementtype', '', $param, '', $sortfield, $sortorder);
        if (!empty($arrayfields['s.nom']['checked'])) print_liste_field_titre($arrayfields['s.nom']['label'], $_SERVER["PHP_SELF"], 's.nom', '', $param, '', $sortfield, $sortorder);
        if (!empty($arrayfields['ac.id']['checked'])) print_liste_field_titre($arrayfields['ac.id']['label'], $_SERVER["PHP_SELF"], 'ac.id', '', $param, '', $sortfield, $sortorder);
        if (!empty($arrayfields['ac.fk_action']['checked'])) print_liste_field_titre($arrayfields['ac.fk_action']['label'], $_SERVER["PHP_SELF"], 'cac.libelle', '', $param, '', $sortfield, $sortorder);
        if (!empty($arrayfields['ac.label']['checked'])) print_liste_field_titre($arrayfields['ac.label']['label'], $_SERVER["PHP_SELF"], 'ac.label', '', $param, '', $sortfield, $sortorder);
        if (!empty($arrayfields['ac.note']['checked'])) print_liste_field_titre($arrayfields['ac.note']['label'], $_SERVER["PHP_SELF"], 'ac.note', '', $param, '', $sortfield, $sortorder);
        if (!empty($arrayfields['ac.fulldayevent']['checked'])) print_liste_field_titre($arrayfields['ac.fulldayevent']['label'], $_SERVER["PHP_SELF"], "ac.fulldayevent", "", $param, '', $sortfield, $sortorder);
        if (!empty($arrayfields['ac.datep']['checked'])) print_liste_field_titre($arrayfields['ac.datep']['label'], $_SERVER["PHP_SELF"], "ac.datep", "", $param, 'align="center"', $sortfield, $sortorder);
        if (!empty($arrayfields['ac.datep2']['checked'])) print_liste_field_titre($arrayfields['ac.datep2']['label'], $_SERVER["PHP_SELF"], "ac.datep2", "", $param, 'align="center"', $sortfield, $sortorder);
        if (!empty($arrayfields['ac.location']['checked'])) print_liste_field_titre($arrayfields['ac.location']['label'], $_SERVER["PHP_SELF"], 'ac.location', '', $param, '', $sortfield, $sortorder);
        if (!empty($arrayfields['ac.fk_element']['checked'])) print_liste_field_titre($arrayfields['ac.fk_element']['label'], $_SERVER["PHP_SELF"], 'ac.fk_element', '', $param, '', $sortfield, $sortorder);
        if (!empty($arrayfields['ac.fk_user_action']['checked'])) print_liste_field_titre($arrayfields['ac.fk_user_action']['label'], $_SERVER["PHP_SELF"], 'uo.lastname', '', $param, 'align="center"', $sortfield, $sortorder);
        if (!empty($arrayfields['ac.userassigned']['checked'])) print_liste_field_titre($arrayfields['ac.userassigned']['label'], $_SERVER["PHP_SELF"], '', '', $param, 'align="center"', $sortfield, $sortorder);
        if (!empty($arrayfields['ac.fk_user_done']['checked'])) print_liste_field_titre($arrayfields['ac.fk_user_done']['label'], $_SERVER["PHP_SELF"], 'ud.lastname', '', $param, 'align="center"', $sortfield, $sortorder);
        if (!empty($arrayfields['ac.fk_project']['checked'])) print_liste_field_titre($arrayfields['ac.fk_project']['label'], $_SERVER["PHP_SELF"], 'ac.fk_project', '', $param, '', $sortfield, $sortorder);
        if (!empty($arrayfields['ac.priority']['checked'])) print_liste_field_titre($arrayfields['ac.priority']['label'], $_SERVER["PHP_SELF"], 'ac.priority', '', $param, '', $sortfield, $sortorder);
        if (!empty($arrayfields['internal_tags']['checked'])) print_liste_field_titre($arrayfields['internal_tags']['label'], $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder);
        if (!empty($arrayfields['external_tags']['checked'])) print_liste_field_titre($arrayfields['external_tags']['label'], $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder);
        if (!empty($arrayfields['ac.fk_user_author']['checked'])) print_liste_field_titre($arrayfields['ac.fk_user_author']['label'], $_SERVER["PHP_SELF"], 'ua.lastname', '', $param, 'align="center"', $sortfield, $sortorder);
        if (!empty($arrayfields['ac.fk_user_mod']['checked'])) print_liste_field_titre($arrayfields['ac.fk_user_mod']['label'], $_SERVER["PHP_SELF"], 'um.lastname', '', $param, 'align="center"', $sortfield, $sortorder);
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
        // Extra fields message
        if (is_array($extrafields_message->attribute_label) && count($extrafields_message->attribute_label)) {
            foreach ($extrafields_message->attribute_label as $key => $val) {
                if (!empty($arrayfields["efm." . $key]['checked'])) {
                    $align = $extrafields_message->getAlignFlag($key);
                    $sortonfield = "efm." . $key;
                    if (!empty($extrafields_message->attribute_computed[$key])) $sortonfield = '';
                    print_liste_field_titre($extralabels[$key], $_SERVER["PHP_SELF"], $sortonfield, "", $param, ($align ? 'align="' . $align . '"' : ''), $sortfield, $sortorder);
                }
            }
        }
        // Hook fields
        $parameters = array('arrayfields' => $arrayfields);
        $reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters);    // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;
        if (!empty($arrayfields['ac.datec']['checked'])) print_liste_field_titre($arrayfields['ac.datec']['label'], $_SERVER["PHP_SELF"], "ac.datec", "", $param, 'align="center" class="nowrap"', $sortfield, $sortorder);
        if (!empty($arrayfields['ac.tms']['checked'])) print_liste_field_titre($arrayfields['ac.tms']['label'], $_SERVER["PHP_SELF"], "ac.tms", "", $param, 'align="center" class="nowrap"', $sortfield, $sortorder);
        if (!empty($arrayfields['ac.percent']['checked'])) print_liste_field_titre($arrayfields['ac.percent']['label'], $_SERVER["PHP_SELF"], "ac.percent", "", $param, 'align="right"', $sortfield, $sortorder);
        print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'align="center" colspan="2"', $sortfield, $sortorder, 'maxwidthsearch ');
        print '</tr>' . "\n";

        if ($list_mode != 0) {
            print '</table>' . "\n";
            print '</form>' . "\n";
            print '<br>' . "\n";

            // Mode timeline of message
            print '<link rel="stylesheet" type="text/css" href="' . dol_buildpath('/requestmanager/css/requestmanager_timeline.css.php', 1) . '">';
            print '<link rel="stylesheet" type="text/css" href="' . dol_buildpath('/requestmanager/css/bootstrap.min.css', 1) . '">';
            print '<div id="timeline-container">' . "\n";
            print '<section id="timeline-wrapper">' . "\n";
            print '<div class="container-fluid">' . "\n";
            print '<div class="row">' . "\n";
        }

        dol_include_once('/requestmanager/class/requestmanagermessage.class.php');
        $requestmessage_static = new RequestManagerMessage($db);

        dol_include_once('/advancedictionaries/class/dictionary.class.php');
        $knowledge_base = Dictionary::getDictionary($db, 'requestmanager', 'requestmanagerknowledgebase');
        $knowledge_base->fetch_lines(1);

        // Event confidentiality
        //--------------------------------------
        if ($conf->eventconfidentiality->enabled) {
            $confidentiality_tags = Dictionary::getDictionary($db, 'eventconfidentiality', 'eventconfidentialitytag');
            $confidentiality_tags->fetch_lines(1);
        }

        $actioncomm_static = new ActionComm($db);

        $societe_static = new Societe($db);

        require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
        $formfile = new FormFile($db);

        require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
        $userowner_static = new User($db);
        $userdone_static = new User($db);
        $userauthor_static = new User($db);
        $usermodif_static = new User($db);
        $userempty_static = new User($db);

        require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
        $project_static = new Project($db);

        require_once DOL_DOCUMENT_ROOT . '/comm/action/class/cactioncomm.class.php';
        $caction_static = new CActionComm($db);
        $cActionList = $caction_static->liste_array(1, 'code', '', (empty($conf->global->AGENDA_USE_EVENT_TYPE) ? 1 : 0));

        $link_url_cache = array();

        $last_day = "";
        $today_day = dol_print_date(dol_now(), 'daytext');

        $action_card_url = dol_buildpath('/comm/action/card.php', 1);
        $backtopage = dol_buildpath('/requestmanager/card.php', 1) . '?id=' . $requestmanager->id;

        $i = 0;
        while ($i < min($num, $limit)) {
            $obj = $db->fetch_object($resql);

            $ec_mode = $obj->ec_mode;
            if ($ec_mode == EventConfidentiality::MODE_HIDDEN && $user->rights->eventconfidentiality->manage) $ec_mode = EventConfidentiality::MODE_VISIBLE;

            $actioncomm_static->id = $obj->id;
            $actioncomm_static->ref = $obj->ref;

            // Properties of parent table llx_c_actioncomm
            $actioncomm_static->type_id = $obj->type_id;
            $actioncomm_static->type_code = $obj->type_code;
            $actioncomm_static->type_color = $obj->type_color;
            $actioncomm_static->type_picto = $obj->type_picto;
            $transcode = $langs->trans("Action" . $obj->type_code);
            $type_label = ($transcode != "Action" . $obj->type_code ? $transcode : $obj->type_label);
            $actioncomm_static->type = $type_label;

            $actioncomm_static->code = $obj->code;
            $actioncomm_static->label = $obj->label;
            $actioncomm_static->datep = $db->jdate($obj->datep);
            $actioncomm_static->datef = $db->jdate($obj->datep2);

            $actioncomm_static->datec = $db->jdate($obj->datec);
            $actioncomm_static->datem = $db->jdate($obj->datem);
            $current_year = dol_print_date($actioncomm_static->datec, '%Y');

            $actioncomm_static->note = $obj->note;
            $actioncomm_static->percentage = $obj->percentage;

            $actioncomm_static->priority = $obj->priority;
            $actioncomm_static->fulldayevent = $obj->fulldayevent;
            $actioncomm_static->location = $obj->location;

            $actioncomm_static->fk_element = $obj->fk_element;
            $actioncomm_static->elementtype = $obj->elementtype;

            $requestmessage_static->notify_assigned = $obj->notify_assigned;
            $requestmessage_static->notify_requesters = $obj->notify_requesters;
            $requestmessage_static->notify_watchers = $obj->notify_watchers;

            $requestmessage_static->knowledge_base_ids = !empty($obj->knowledge_base_ids) ? explode(',', $obj->knowledge_base_ids) : array();

            // Project
            $project_static->id = $obj->fk_project;
            $project_static->ref = $obj->projectref;
            $project_static->title = $obj->projecttitle;
            $project_static->public = $obj->projectpublic;
            $project_static->statut = $obj->projectstatus;
            $project_static->datee = $db->jdate($obj->projectdatee);

            // ThirdParty
            $societe_static->id = $obj->soc_id;
            $societe_static->client = $obj->soc_client;
            $societe_static->name = $obj->soc_name;
            $societe_static->name_alias = $obj->soc_name_alias;

            // User Owner
            $userowner_static->id = $obj->fk_user_action;
            $userowner_static->firstname = $obj->userownerfirstname;
            $userowner_static->lastname = $obj->userownerlastname;
            $userowner_static->email = $obj->userowneremail;
            // User Done
            $userdone_static->id = $obj->fk_user_done;
            $userdone_static->firstname = $obj->userdonefirstname;
            $userdone_static->lastname = $obj->userdonelastname;
            $userdone_static->email = $obj->userdoneemail;
            // User Author
            $userauthor_static->id = $obj->fk_user_author;
            $userauthor_static->firstname = $obj->userauthorfirstname;
            $userauthor_static->lastname = $obj->userauthorlastname;
            $userauthor_static->email = $obj->userauthoremail;
            $userauthor_static->photo = $obj->userauthorphoto;
            $userauthor_static->gender = $obj->userauthorgender;
            // User Modif
            $usermodif_static->id = $obj->fk_user_mod;
            $usermodif_static->firstname = $obj->usermodfirstname;
            $usermodif_static->lastname = $obj->usermodlastname;
            $usermodif_static->email = $obj->usermodemail;

            if ($list_mode == 0) {
                print '<tr' . (empty($actioncomm_static->type_color) ? ' class="oddeven"' : '') . '>';

                $tdcolor = empty($actioncomm_static->type_color) ? '' : ' style="background-color: #' . ltrim($actioncomm_static->type_color, '#\s') . ';"';

                // Request Child
                if (!empty($arrayfields['fk_request']['checked'])) {
                    print '<td class="nowrap"' . $tdcolor . '>';
                    if (!$conf->eventconfidentiality->enabled || $arrayfields['fk_request']['ec_mode'] >= $ec_mode) {
                        if ($obj->fk_request > 0) {
                            include_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
                            if (!isset($link_url_cache['requestmanager'][$obj->fk_request]))
                                $link_url_cache['requestmanager'][$obj->fk_request] = dolGetElementUrl($obj->fk_request, 'requestmanager', 1);
                            print $link_url_cache['requestmanager'][$obj->fk_request];
                        }
                    }
                    print '</td>';
                }
                // Origin
                if (!empty($arrayfields['ac.elementtype']['checked'])) {
                    print '<td class="nowrap"' . $tdcolor . '>';
                    if (!$conf->eventconfidentiality->enabled || $arrayfields['ac.elementtype']['ec_mode'] >= $ec_mode) {
                        if (isset($elements_array[$actioncomm_static->elementtype])) print $elements_array[$actioncomm_static->elementtype];
                        else print $elements_array['societe'];
                    }
                    print '</td>';
                }
                // ThirdParty
                if (!empty($arrayfields['s.nom']['checked'])) {
                    print '<td class="nowrap"' . $tdcolor . '>';
                    if (!$conf->eventconfidentiality->enabled || $arrayfields['s.nom']['ec_mode'] >= $ec_mode) {
                        print $societe_static->getNomUrl(1, '', 24);
                    }
                    print '</td>';
                }
                // Ref
                if (!empty($arrayfields['ac.id']['checked'])) {
                    print '<td class="nowrap"' . $tdcolor . '>';
                    if (!$conf->eventconfidentiality->enabled || $arrayfields['ac.id']['ec_mode'] >= $ec_mode) {
                        print $actioncomm_static->getNomUrl(1, 24);
                    }
                    print '</td>';
                }
                // Type
                if (!empty($arrayfields['ac.fk_action']['checked'])) {
                    print '<td class="nowrap"' . $tdcolor . '>';
                    if (!$conf->eventconfidentiality->enabled || $arrayfields['ac.fk_action']['ec_mode'] >= $ec_mode) {
                        if (!empty($conf->global->AGENDA_USE_EVENT_TYPE)) {
                            if ($actioncomm_static->type_picto) print img_picto('', $actioncomm_static->type_picto);
                            else {
                                if ($actioncomm_static->type_code == 'AC_RDV') print img_picto('', 'object_group') . ' ';
                                if ($actioncomm_static->type_code == 'AC_TEL') print img_picto('', 'object_phoning') . ' ';
                                if ($actioncomm_static->type_code == 'AC_FAX') print img_picto('', 'object_phoning_fax') . ' ';
                                if ($actioncomm_static->type_code == 'AC_EMAIL') print img_picto('', 'object_email') . ' ';
                            }
                        }
                        $labeltype = $obj->type_code;
                        if (empty($conf->global->AGENDA_USE_EVENT_TYPE) && empty($cActionList[$labeltype])) $labeltype = 'AC_OTH';
                        if (!empty($cActionList[$labeltype])) $labeltype = $cActionList[$labeltype];
                        $toprint = dol_trunc($labeltype, 24);
                        if (empty($conf->global->MAIN_DISABLE_TRUNC)) {
                            $toprint = $form->textwithtooltip($toprint, $labeltype);
                        }
                        print $toprint;
                    }
                    print '</td>';
                }
                // Title
                if (!empty($arrayfields['ac.label']['checked'])) {
                    print '<td class="nowrap"' . $tdcolor . '>';
                    if (!$conf->eventconfidentiality->enabled || $arrayfields['ac.label']['ec_mode'] >= $ec_mode) {
                        $toprint = dol_trunc($actioncomm_static->label, 24);
                        if (empty($conf->global->MAIN_DISABLE_TRUNC)) {
                            $toprint = $form->textwithtooltip($toprint, $actioncomm_static->label);
                        }
                        print $toprint;
                    }
                    print '</td>';
                }
                // Description
                if (!empty($arrayfields['ac.note']['checked'])) {
                    print '<td class="nowrap tdoverflowmax300"' . $tdcolor . '>';
                    if (!$conf->eventconfidentiality->enabled || $arrayfields['ac.note']['ec_mode'] >= $ec_mode) {
                        $toprint = dol_trunc($actioncomm_static->note, 24);
                        if (empty($conf->global->MAIN_DISABLE_TRUNC)) {
                            $toprint = $form->textwithtooltip($toprint, $actioncomm_static->note);
                        }
                        print $toprint;
                    }
                    print '</td>';
                }
                // Event On Full Day
                if (!empty($arrayfields['ac.fulldayevent']['checked'])) {
                    print '<td class="nowrap"' . $tdcolor . '>';
                    if (!$conf->eventconfidentiality->enabled || $arrayfields['ac.fulldayevent']['ec_mode'] >= $ec_mode) {
                        print yn($actioncomm_static->fulldayevent);
                    }
                    print '</td>';
                }
                // Date Start
                if (!empty($arrayfields['ac.datep']['checked'])) {
                    print '<td class="nowrap" align="center"' . $tdcolor . '>';
                    if (!$conf->eventconfidentiality->enabled || $arrayfields['ac.datep']['ec_mode'] >= $ec_mode) {
                        if ($actioncomm_static->datep > 0) print dol_print_date($actioncomm_static->datep, "dayhour");
                    }
                    print '</td>';
                }
                // Date End
                if (!empty($arrayfields['ac.datep2']['checked'])) {
                    print '<td class="nowrap" align="center"' . $tdcolor . '>';
                    if (!$conf->eventconfidentiality->enabled || $arrayfields['ac.datep2']['ec_mode'] >= $ec_mode) {
                        if ($actioncomm_static->datef > 0) print dol_print_date($actioncomm_static->datef, "dayhour");
                    }
                    print '</td>';
                }
                // Location
                if (!empty($arrayfields['ac.location']['checked'])) {
                    print '<td class="nowrap"' . $tdcolor . '>';
                    if (!$conf->eventconfidentiality->enabled || $arrayfields['ac.location']['ec_mode'] >= $ec_mode) {
                        $toprint = dol_trunc($actioncomm_static->location, 24);
                        if (empty($conf->global->MAIN_DISABLE_TRUNC)) {
                            $toprint = $form->textwithtooltip($toprint, $actioncomm_static->location);
                        }
                        print $toprint;
                    }
                    print '</td>';
                }
                // Linked Object
                if (!empty($arrayfields['ac.fk_element']['checked'])) {
                    print '<td class="nowrap"' . $tdcolor . '>';
                    if (!$conf->eventconfidentiality->enabled || $arrayfields['ac.fk_element']['ec_mode'] >= $ec_mode) {
                        if ($actioncomm_static->fk_element > 0 && !empty($actioncomm_static->elementtype)) {
                            include_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
                            if (!isset($link_url_cache[$actioncomm_static->elementtype][$actioncomm_static->fk_element]))
                                $link_url_cache[$actioncomm_static->elementtype][$actioncomm_static->fk_element] = dolGetElementUrl($actioncomm_static->fk_element, $actioncomm_static->elementtype, 1);
                            print $link_url_cache[$actioncomm_static->elementtype][$actioncomm_static->fk_element];
                        }
                    }
                    print '</td>';
                }
                // Owned By
                if (!empty($arrayfields['ac.fk_user_action']['checked'])) {
                    print '<td class="nowrap" align="center"' . $tdcolor . '>';
                    if (!$conf->eventconfidentiality->enabled || $arrayfields['ac.fk_user_action']['ec_mode'] >= $ec_mode) {
                        if ($userowner_static->id > 0) print $userowner_static->getNomUrl(1);
                    }
                    print '</td>';
                }
                // Assigned To
                if (!empty($arrayfields['ac.userassigned']['checked'])) {
                    print '<td class="nowrap" align="center"' . $tdcolor . '>';
                    print '</td>';
                }
                // Done By
                if (!empty($arrayfields['ac.fk_user_done']['checked'])) {
                    print '<td class="nowrap" align="center"' . $tdcolor . '>';
                    if (!$conf->eventconfidentiality->enabled || $arrayfields['ac.fk_user_done']['ec_mode'] >= $ec_mode) {
                        if ($userdone_static->id > 0) print $userdone_static->getNomUrl(1);
                    }
                    print '</td>';
                }
                // Project
                if (!empty($arrayfields['ac.fk_project']['checked'])) {
                    print '<td class="nowrap"' . $tdcolor . '>';
                    if (!$conf->eventconfidentiality->enabled || $arrayfields['ac.fk_project']['ec_mode'] >= $ec_mode) {
                        if ($project_static->id > 0) print $project_static->getNomUrl(1);
                    }
                    print '</td>';
                }
                // Priority
                if (!empty($arrayfields['ac.priority']['checked'])) {
                    print '<td class="nowrap"' . $tdcolor . '>';
                    if (!$conf->eventconfidentiality->enabled || $arrayfields['ac.priority']['ec_mode'] >= $ec_mode) {
                        print $actioncomm_static->priority;
                    }
                    print '</td>';
                }
                // Internal tags
                if (!empty($arrayfields['internal_tags']['checked'])) {
                    print '<td class="nowrap"' . $tdcolor . '>';
                    if (!$conf->eventconfidentiality->enabled || $arrayfields['internal_tags']['ec_mode'] >= $ec_mode) {
                        $tags_list = !empty($obj->internal_tags_info) ? explode(',', $obj->internal_tags_info) : array();
                        $to_print = array();
                        foreach ($tags_list as $tag_info) {
                            $info = explode(':', $tag_info);
                            $to_print[] = '<li class="select2-search-choice-dolibarr noborderoncategories style="background: #aaa">' .
                                (!empty($confidentiality_tags->lines[$info[0]]) ? $confidentiality_tags->lines[$info[0]]->fields['label'] : '') .
                                ' (' . (!empty($confidentiality_levels[$info[1]]) ? $confidentiality_levels[$info[1]] : '') . ')' . '</li>';
                        }
                        print '<div class="select2-container-multi-dolibarr" style="width: 90%;"><ul class="select2-choices-dolibarr">' . implode(' ', $to_print) . '</ul></div>';
                    }
                    print '</td>';
                }
                // External tags
                if (!empty($arrayfields['external_tags']['checked'])) {
                    print '<td class="nowrap"' . $tdcolor . '>';
                    if (!$conf->eventconfidentiality->enabled || $arrayfields['external_tags']['ec_mode'] >= $ec_mode) {
                        $tags_list = !empty($obj->external_tags_info) ? explode(',', $obj->external_tags_info) : array();
                        $to_print = array();
                        foreach ($tags_list as $tag_info) {
                            $info = explode(':', $tag_info);
                            $to_print[] = '<li class="select2-search-choice-dolibarr noborderoncategories style="background: #aaa">' .
                                (!empty($confidentiality_tags->lines[$info[0]]) ? $confidentiality_tags->lines[$info[0]]->fields['label'] : '') .
                                ' (' . (!empty($confidentiality_levels[$info[1]]) ? $confidentiality_levels[$info[1]] : '') . ')' . '</li>';
                        }
                        print '<div class="select2-container-multi-dolibarr" style="width: 90%;"><ul class="select2-choices-dolibarr">' . implode(' ', $to_print) . '</ul></div>';
                    }
                    print '</td>';
                }
                // Author
                if (!empty($arrayfields['ac.fk_user_author']['checked'])) {
                    print '<td class="nowrap" align="center"' . $tdcolor . '>';
                    if (!$conf->eventconfidentiality->enabled || $arrayfields['ac.fk_user_author']['ec_mode'] >= $ec_mode) {
                        if ($userauthor_static->id > 0) print $userauthor_static->getNomUrl(1);
                    }
                    print '</td>';
                }
                // Modified By
                if (!empty($arrayfields['ac.fk_user_mod']['checked'])) {
                    print '<td class="nowrap" align="center"' . $tdcolor . '>';
                    if (!$conf->eventconfidentiality->enabled || $arrayfields['ac.fk_user_mod']['ec_mode'] >= $ec_mode) {
                        if ($usermodif_static->id > 0) print $usermodif_static->getNomUrl(1);
                    }
                    print '</td>';
                }

                // Extra fields
                if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) {
                    foreach ($extrafields->attribute_label as $key => $label) {
                        if (!empty($arrayfields["ef." . $key]['checked'])) {
                            print '<td';
                            $align = $extrafields->getAlignFlag($key);
                            if ($align) print ' align="' . $align . '"';
                            print $tdcolor . '>';
                            if (!$conf->eventconfidentiality->enabled || $arrayfields["ef." . $key]['ec_mode'] >= $ec_mode) {
                                $tmpkey = 'options_' . $key;
                                print $extrafields->showOutputField($key, $obj->$tmpkey, '', 1);
                            }
                            print '</td>';
                        }
                    }
                }
                // Extra fields message
                if (is_array($extrafields_message->attribute_label) && count($extrafields_message->attribute_label)) {
                    foreach ($extrafields_message->attribute_label as $key => $label) {
                        if (!empty($arrayfields["efm." . $key]['checked'])) {
                            print '<td';
                            $align = $extrafields_message->getAlignFlag($key);
                            if ($align) print ' align="' . $align . '"';
                            print $tdcolor . '>';
                            if (!$conf->eventconfidentiality->enabled || $arrayfields["efm." . $key]['ec_mode'] >= $ec_mode) {
                                $tmpkey = 'options_m_' . $key;
                                print $extrafields_message->showOutputField($key, $obj->$tmpkey, '', 1);
                            }
                            print '</td>';
                        }
                    }
                }
                // Fields from hook
                $parameters = array('arrayfields' => $arrayfields, 'obj' => $obj);
                $reshook = $hookmanager->executeHooks('printFieldListValue', $parameters);    // Note that $action and $object may have been modified by hook
                print $hookmanager->resPrint;

                // Date creation
                if (!empty($arrayfields['ac.datec']['checked'])) {
                    print '<td align="center" class="nowrap"' . $tdcolor . '>';
                    if (!$conf->eventconfidentiality->enabled || $arrayfields['ac.datec']['ec_mode'] >= $ec_mode) {
                        print dol_print_date($actioncomm_static->datec, 'dayhour');
                    }
                    print '</td>';
                }
                // Date modif
                if (!empty($arrayfields['ac.tms']['checked'])) {
                    print '<td align="center" class="nowrap"' . $tdcolor . '>';
                    if (!$conf->eventconfidentiality->enabled || $arrayfields['ac.tms']['ec_mode'] >= $ec_mode) {
                        print dol_print_date($actioncomm_static->datem, 'dayhour');
                    }
                    print '</td>';
                }
                // Status
                if (!empty($arrayfields['ac.percent']['checked'])) {
                    print '<td align="right" class="nowrap"' . $tdcolor . '>';
                    if (!$conf->eventconfidentiality->enabled || $arrayfields['ac.percent']['ec_mode'] >= $ec_mode) {
                        print $actioncomm_static->getLibStatut(3);
                    }
                    print '</td>';
                }
                // Action column
                // Edit event
                if ($user->rights->agenda->allactions->create ||
                    (($userauthor_static->id == $user->id || $userowner_static->id == $user->id) && $user->rights->agenda->myactions->create)
                ) {
                    print '<td class="nowrap" align="center"' . $tdcolor . '>';
                    print '<a href="'.$action_card_url.'?id='.$actioncomm_static->id.'&action=edit&backtopage='.urlencode($backtopage).'">';
                    print img_edit();
                    print '</a></td>';
                }
                else print '<td>&nbsp;</td>';
                // Delete event
                if ($user->rights->agenda->allactions->delete ||
                    (($userauthor_static->id == $user->id || $userowner_static->id == $user->id) && $user->rights->agenda->myactions->delete)
                ) {
                    print '<td class="nowrap" align="center"' . $tdcolor . '>';
                    print '<a href="'.$action_card_url.'?id='.$actioncomm_static->id.'&action=delete&backtopage='.urlencode($backtopage).'">';
                    print img_delete();
                    print '</a></td>';
                }
                else print '<td>&nbsp;</td>';

                print "</tr>\n";
            } else {
                $current_day = dol_print_date($actioncomm_static->datec, 'daytext');

                // Cutting by date
                if ($last_day != $current_day) {
                    if (!empty($last_day)) {
                        print '<br>' . "\n";
                        print '</div><!-- end of timeline-events -->' . "\n";
                        print '<div class="clearfix"></div>' . "\n";
                        print '</div><!-- end of timeline-block -->' . "\n";
                    } elseif ($page > 0) {
                        print '<div class="timeline-block">' . "\n";
                        print '<div class="timeline-events">' . "\n";
                        print '<br>' . "\n";
                        print '</div><!-- end of timeline-events -->' . "\n";
                        print '<div class="clearfix"></div>' . "\n";
                        print '</div><!-- end of timeline-block -->' . "\n";
                    }
                    print '<div class="timeline-top">' . "\n";
                    print '<div class="top-day">' . ($today_day == $current_day ? $langs->trans('Today') : $current_day) . '</div>' . "\n";
                    print '</div>' . "\n";
                    print '<div class="timeline-block">' . "\n";
                    print '<div class="timeline-events">' . "\n";
                    $last_day = $current_day;
                }

                // Get infos to print
                $infos_to_print = array();
                $notification = '';
                $icon = '';
                $icon_img = '';
                if ($actioncomm_static->code == 'AC_RM_IN' || $actioncomm_static->code == 'AC_RM_OUT' || $actioncomm_static->code == 'AC_RM_PRIV') {
                    if (!$conf->eventconfidentiality->enabled || 1 >= $ec_mode) {
                        $notified = array();
                        if (!empty($requestmessage_static->notify_assigned)) $notified[] = $langs->trans('RequestManagerAssignedNotified');
                        if (!empty($requestmessage_static->notify_requesters)) $notified[] = $langs->trans('RequestManagerRequesterNotified');
                        if (!empty($requestmessage_static->notify_watchers)) $notified[] = $langs->trans('RequestManagerWatcherNotified');
                        $notification = count($notified) > 0 ? ' ' . $form->textwithpicto('', implode('<br>', $notified), 1, 'object_email.png') : '';
                    }

                    $icon = $actioncomm_static->code == 'AC_RM_IN' ? 'fa-angle-double-left' : ($actioncomm_static->code == 'AC_RM_OUT' ? 'fa-angle-double-right' : 'fa-lock');
                    $direction = $actioncomm_static->code == 'AC_RM_IN' ? 'r-event' : 'l-event';

                    if (!$conf->eventconfidentiality->enabled || 1 >= $ec_mode) {
                        $knowledge_base_to_print = array();
                        foreach ($requestmessage_static->knowledge_base_ids as $knowledge_base_id) {
                            $knowledge_base_to_print[] = '<li class="select2-search-choice-dolibarr noborderoncategories style="background: #aaa">' . $knowledge_base->lines[$knowledge_base_id]->fields['code'] . ' - ' . $knowledge_base->lines[$knowledge_base_id]->fields['title'] . '</li>';
                        }
                        if (count($knowledge_base_to_print)) {
                            $infos_to_print[] = $langs->trans("RequestManagerMessageKnowledgeBase") . ' : ' .
                                '<div class="select2-container-multi-dolibarr" style="width: 90%;"><ul class="select2-choices-dolibarr">' . implode(' ', $knowledge_base_to_print) . '</ul></div>';
                        }
                    }
                } else {
                    $direction = 'l-event';
                    if (!empty($elements_picto_array[$actioncomm_static->elementtype])) {
                        $icon_img = $elements_picto_array[$actioncomm_static->elementtype];
                    } else {
                        $icon = 'fa-list-alt';
                    }
                }

                // Request Child
                if (!empty($arrayfields['fk_request']['checked']) && (!$conf->eventconfidentiality->enabled || $arrayfields['fk_request']['ec_mode'] >= $ec_mode) && $obj->fk_request > 0) {
                    include_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
                    if (!isset($link_url_cache['requestmanager'][$obj->fk_request]))
                        $link_url_cache['requestmanager'][$obj->fk_request] = dolGetElementUrl($obj->fk_request, 'requestmanager', 1);
                    $infos_to_print[] = $arrayfields['fk_request']['label'] . ' : ' . $link_url_cache['requestmanager'][$obj->fk_request];
                }
                // Origin
                if (!empty($arrayfields['ac.elementtype']['checked']) && (!$conf->eventconfidentiality->enabled || $arrayfields['ac.elementtype']['ec_mode'] >= $ec_mode)) {
                    $infos_to_print[] = $arrayfields['ac.elementtype']['label'] . ' : ' . (isset($elements_array[$actioncomm_static->elementtype]) ? $elements_array[$actioncomm_static->elementtype] : $elements_array['societe']);
                }
                // ThirdParty
                if (!empty($arrayfields['s.nom']['checked']) && (!$conf->eventconfidentiality->enabled || $arrayfields['s.nom']['ec_mode'] >= $ec_mode) && $societe_static->id > 0) {
                    $infos_to_print[] = $arrayfields['s.nom']['label'] . ' : ' . $societe_static->getNomUrl(1);
                }
                // Event On Full Day
                if (!empty($arrayfields['ac.fulldayevent']['checked']) && (!$conf->eventconfidentiality->enabled || $arrayfields['ac.fulldayevent']['ec_mode'] >= $ec_mode) && !empty($actioncomm_static->fulldayevent)) {
                    $infos_to_print[] = $arrayfields['ac.fulldayevent']['label'] . ' : ' . yn($actioncomm_static->fulldayevent);
                }
                // Date Start
                if (!empty($arrayfields['ac.datep']['checked']) && (!$conf->eventconfidentiality->enabled || $arrayfields['ac.datep']['ec_mode'] >= $ec_mode) && $actioncomm_static->datep > 0) {
                    $infos_to_print[] = $arrayfields['ac.datep']['label'] . ' : ' . dol_print_date($actioncomm_static->datep, "dayhour");
                }
                // Date End
                if (!empty($arrayfields['ac.datep2']['checked']) && (!$conf->eventconfidentiality->enabled || $arrayfields['ac.datep2']['ec_mode'] >= $ec_mode) && $actioncomm_static->datef > 0) {
                    $infos_to_print[] = $arrayfields['ac.datep2']['label'] . ' : ' . dol_print_date($actioncomm_static->datef, "dayhour");
                }
                // Location
                if (!empty($arrayfields['ac.location']['checked']) && (!$conf->eventconfidentiality->enabled || $arrayfields['ac.location']['ec_mode'] >= $ec_mode) && !empty($actioncomm_static->location)) {
                    $infos_to_print[] = $arrayfields['ac.location']['label'] . ' : ' . $actioncomm_static->location;
                }
                // Linked Object
                if (!empty($arrayfields['ac.fk_element']['checked']) && (!$conf->eventconfidentiality->enabled || $arrayfields['ac.fk_element']['ec_mode'] >= $ec_mode) && $actioncomm_static->fk_element > 0 && !empty($actioncomm_static->elementtype)) {
                    include_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
                    if (!isset($link_url_cache[$actioncomm_static->elementtype][$actioncomm_static->fk_element]))
                        $link_url_cache[$actioncomm_static->elementtype][$actioncomm_static->fk_element] = dolGetElementUrl($actioncomm_static->fk_element, $actioncomm_static->elementtype, 1);
                    $infos_to_print[] = $arrayfields['ac.fk_element']['label'] . ' : ' . $link_url_cache[$actioncomm_static->elementtype][$actioncomm_static->fk_element];
                }
                // Owned By
                if (!empty($arrayfields['ac.fk_user_action']['checked']) && (!$conf->eventconfidentiality->enabled || $arrayfields['ac.fk_user_action']['ec_mode'] >= $ec_mode) && $userowner_static->id > 0) {
                    $infos_to_print[] = $arrayfields['ac.fk_user_action']['label'] . ' : ' . $userowner_static->getNomUrl(1);
                }
                // Assigned To
//                if (!empty($arrayfields['ac.userassigned']['checked']) && (!$conf->eventconfidentiality->enabled || $arrayfields['ac.userassigned']['ec_mode'] >= $ec_mode)) {
//                    $infos_to_print[] = $arrayfields['ac.userassigned']['label'] . ' : ' . '';
//                }
                // Done By
                if (!empty($arrayfields['ac.fk_user_done']['checked']) && (!$conf->eventconfidentiality->enabled || $arrayfields['ac.fk_user_done']['ec_mode'] >= $ec_mode) && $userdone_static->id > 0) {
                    $infos_to_print[] = $arrayfields['ac.fk_user_done']['label'] . ' : ' . $userdone_static->getNomUrl(1);
                }
                // Project
                if (!empty($arrayfields['ac.fk_project']['checked']) && (!$conf->eventconfidentiality->enabled || $arrayfields['ac.fk_project']['ec_mode'] >= $ec_mode) && $project_static->id > 0) {
                    $infos_to_print[] = $arrayfields['ac.fk_project']['label'] . ' : ' . $project_static->getNomUrl(1);
                }
                // Priority
                if (!empty($arrayfields['ac.priority']['checked']) && (!$conf->eventconfidentiality->enabled || $arrayfields['ac.priority']['ec_mode'] >= $ec_mode) && !empty($actioncomm_static->priority)) {
                    $infos_to_print[] = $arrayfields['ac.priority']['label'] . ' : ' . $actioncomm_static->priority;
                }
                // Internal tags
                if (!empty($arrayfields['internal_tags']['checked']) && (!$conf->eventconfidentiality->enabled || $arrayfields['internal_tags']['ec_mode'] >= $ec_mode) && !empty($obj->internal_tags_info)) {
                    $tags_list = explode(',', $obj->internal_tags_info);
                    $to_print = array();
                    foreach ($tags_list as $tag_info) {
                        $info = explode(':', $tag_info);
                        $to_print[] = '<li class="select2-search-choice-dolibarr noborderoncategories style="background: #aaa">' .
                            (!empty($confidentiality_tags->lines[$info[0]]) ? $confidentiality_tags->lines[$info[0]]->fields['label'] : '') .
                            ' (' . (!empty($confidentiality_levels[$info[1]]) ? $confidentiality_levels[$info[1]] : '') . ')' . '</li>';
                    }
                    $infos_to_print[] = $arrayfields['internal_tags']['label'] . ' : <div class="select2-container-multi-dolibarr" style="width: 90%;"><ul class="select2-choices-dolibarr">' . implode(' ', $to_print) . '</ul></div>';
                }
                // External tags
                if (!empty($arrayfields['external_tags']['checked']) && (!$conf->eventconfidentiality->enabled || $arrayfields['external_tags']['ec_mode'] >= $ec_mode) && !empty($obj->external_tags_info)) {
                    $tags_list = explode(',', $obj->external_tags_info);
                    $to_print = array();
                    foreach ($tags_list as $tag_info) {
                        $info = explode(':', $tag_info);
                        $to_print[] = '<li class="select2-search-choice-dolibarr noborderoncategories style="background: #aaa">' .
                            (!empty($confidentiality_tags->lines[$info[0]]) ? $confidentiality_tags->lines[$info[0]]->fields['label'] : '') .
                            ' (' . (!empty($confidentiality_levels[$info[1]]) ? $confidentiality_levels[$info[1]] : '') . ')' . '</li>';
                    }
                    $infos_to_print[] = $arrayfields['external_tags']['label'] . ' : <div class="select2-container-multi-dolibarr" style="width: 90%;"><ul class="select2-choices-dolibarr">' . implode(' ', $to_print) . '</ul></div>';
                }
                // Extra fields
                if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) {
                    foreach ($extrafields->attribute_label as $key => $label) {
                        if (!empty($arrayfields["ef." . $key]['checked'])) {
                        if (!$conf->eventconfidentiality->enabled || $arrayfields["ef." . $key]['ec_mode'] >= $ec_mode) {
                            if ($extrafields->attribute_type[$key] == 'separate') {
                                $infos_to_print[] = $label . ' :<br>' . "\n";
                            } else {
                                $tmpkey = 'options_' . $key;
                                $infos_to_print[] = $label . ' : ' . $extrafields->showOutputField($key, $obj->$tmpkey, '', 1);
                            }
                        }
                        }
                    }
                }
                // Extra fields message
                if (is_array($extrafields_message->attribute_label) && count($extrafields_message->attribute_label)) {
                    foreach ($extrafields_message->attribute_label as $key => $label) {
                        if (!empty($arrayfields["efm." . $key]['checked'])) {
                        if (!$conf->eventconfidentiality->enabled || $arrayfields["efm." . $key]['ec_mode'] >= $ec_mode) {
                            if ($extrafields->attribute_type[$key] == 'separate') {
                                $infos_to_print[] = $label . ' :<br>' . "\n";
                            } else {
                                $tmpkey = 'options_m_' . $key;
                                $infos_to_print[] = $label . ' : ' . $extrafields->showOutputField($key, $obj->$tmpkey, '', 1);
                            }
                        }
                        }
                    }
                }
                // Fields from hook
                $parameters = array('arrayfields' => $arrayfields, 'obj' => $obj);
                $reshook = $hookmanager->executeHooks('printFieldListValue', $parameters);    // Note that $action and $object may have been modified by hook
                if (is_array($hookmanager->resArray) && count($hookmanager->resArray)) {
                    $infos_to_print = array_merge($infos_to_print, $hookmanager->resArray);
                }
                // Status
                if (!empty($arrayfields['ac.percent']['checked']) && (!$conf->eventconfidentiality->enabled || $arrayfields['ac.percent']['ec_mode'] >= $ec_mode)) {
                    $infos_to_print[] = $arrayfields['ac.percent']['label'] . ' : ' . $actioncomm_static->getLibStatut(3);
                }
                // Attached files
                if (!$conf->eventconfidentiality->enabled || 1 >= $ec_mode) {
                    $files_to_print = array();
                    $upload_dir = $conf->agenda->dir_output . '/' . dol_sanitizeFileName($actioncomm_static->ref);
                    $filearray = dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$');
                    foreach ($filearray as $file) {
                        $relativepath = $actioncomm_static->id . '/' . $file["name"];

                        $documenturl = DOL_URL_ROOT . '/document.php';
                        if (isset($conf->global->DOL_URL_ROOT_DOCUMENT_PHP)) $documenturl = $conf->global->DOL_URL_ROOT_DOCUMENT_PHP;    // To use another wrapper

                        // Show file name with link to download
                        $tmp = $formfile->showPreview($file, 'agenda', $relativepath, 0, '');
                        $out = ($tmp ? $tmp . ' ' : '');
                        $out .= '<a class="documentdownload" href="' . $documenturl . '?modulepart=agenda&amp;file=' . urlencode($relativepath) . '"';
                        $mime = dol_mimetype($relativepath, '', 0);
                        if (preg_match('/text/', $mime)) $out .= ' target="_blank"';
                        $out .= ' target="_blank">';
                        $out .= img_mime($file["name"], $langs->trans("File") . ': ' . $file["name"]) . ' ' . $file["name"];
                        $out .= '</a>';

                        $files_to_print[] = $out;
                    }
                    if (count($files_to_print)) {
                        $infos_to_print[] = '<br>' . $langs->trans("Documents") . ' : ' . implode(' , ', $files_to_print);
                    }
                }

                // Action icons
                $action_icons = array();
                // Edit event
                if ($user->rights->agenda->allactions->create ||
                    (($userauthor_static->id == $user->id || $userowner_static->id == $user->id) && $user->rights->agenda->myactions->create)
                ) {
                    $action_icons[] = '<a href="' . $action_card_url . '?id=' . $actioncomm_static->id . '&action=edit&backtopage=' . urlencode($backtopage) . '">' . img_edit() . '</a>';
                }
                // Delete event
                if ($user->rights->agenda->allactions->delete ||
                    (($userauthor_static->id == $user->id || $userowner_static->id == $user->id) && $user->rights->agenda->myactions->delete)
                ) {
                    $action_icons[] = '<a href="' . $action_card_url . '?id=' . $actioncomm_static->id . '&action=delete&backtopage=' . urlencode($backtopage) . '">' . img_delete() . '</a>';
                }

                print '<div class="row"></div>' . "\n";
                print '<div class="event ' . $direction . ' col-md-6 col-sm-6 col-xs-8 ">' . (!empty($icon) ? '<span class="thumb fa ' . $icon . '"></span>' : '<span class="thumb">' . $icon_img . '</span>') . "\n";
                print '<div class=" event-body">' . "\n";
                print '<div class="person-image pull-left ">' . "\n";
                if (!empty($arrayfields['ac.fk_user_author']['checked']) && (!$conf->eventconfidentiality->enabled || $arrayfields['ac.fk_user_author']['ec_mode'] >= $ec_mode)) {
                    print Form::showphoto('userphoto', $userauthor_static, 100, 0, 0, '', 'small', 0, 1) . "\n";
                } else {
                    print Form::showphoto('userphoto', $userempty_static, 100, 0, 0, '', 'small', 0, 1) . "\n";
                }
                print '</div>' . "\n";
                print '<div class="event-content">' . "\n";
                print '<h5 class="text-primary text-left">' . "\n";
                $to_print = array();
                // Ref
                if (!empty($arrayfields['ac.id']['checked']) && (!$conf->eventconfidentiality->enabled || $arrayfields['ac.id']['ec_mode'] >= $ec_mode) && $actioncomm_static->id > 0) {
                    $to_print[] = $actioncomm_static->getNomUrl(2);
                }
                // Title
                if (!empty($arrayfields['ac.label']['checked']) && (!$conf->eventconfidentiality->enabled || $arrayfields['ac.label']['ec_mode'] >= $ec_mode) && !empty($actioncomm_static->label)) {
                    $to_print[] = $actioncomm_static->label;
                }
                // Type
                if (!empty($arrayfields['ac.fk_action']['checked']) && (!$conf->eventconfidentiality->enabled || $arrayfields['ac.fk_action']['ec_mode'] >= $ec_mode)) {
                    $labeltype = $obj->type_code;
                    if (empty($conf->global->AGENDA_USE_EVENT_TYPE) && empty($cActionList[$labeltype])) $labeltype = 'AC_OTH';
                    if (!empty($cActionList[$labeltype])) $labeltype = $cActionList[$labeltype];
                    if (!empty($labeltype)) $to_print[] = '(' . $labeltype . ')';
                }
                $to_print[] = $notification;
                print implode(' ', $to_print);
                print '<span class="right">'.implode(' ', $action_icons).'</span>';
                print '</h5>' . "\n";
                print '<span class="text-muted text-left" style="display:block; margin: 0"><small>' . "\n";
                if (!empty($arrayfields['ac.fk_user_author']['checked']) && (!$conf->eventconfidentiality->enabled || $arrayfields['ac.fk_user_author']['ec_mode'] >= $ec_mode)) {
                    print $userauthor_static->getNomUrl();
                }
                if (!empty($arrayfields['ac.datec']['checked']) && (!$conf->eventconfidentiality->enabled || $arrayfields['ac.datec']['ec_mode'] >= $ec_mode)) {
                    print ' , ' . dol_print_date($actioncomm_static->datec, 'dayhour');
                }
                if ($actioncomm_static->datem > 0 && $actioncomm_static->datec != $actioncomm_static->datem) {
                    $to_print = array();
                    if (!empty($arrayfields['ac.fk_user_mod']['checked']) && (!$conf->eventconfidentiality->enabled || $arrayfields['ac.fk_user_mod']['ec_mode'] >= $ec_mode)) {
                        $to_print[] = $usermodif_static->getNomUrl();
                    }
                    if (!empty($arrayfields['ac.tms']['checked']) && (!$conf->eventconfidentiality->enabled || $arrayfields['ac.tms']['ec_mode'] >= $ec_mode)) {
                        $to_print[] = ' , ' . dol_print_date($actioncomm_static->datem, 'dayhour');
                    }
                    if (count($to_print)) {
                        print ' ( ' . $langs->trans('ModifiedBy') . ': ' . implode('', $to_print) . "\n";
                    }
                }
                print '</small></span>' . "\n";
                print '<br>' . "\n";
                print '<br>' . "\n";
                // Description
                if (!empty($arrayfields['ac.note']['checked']) &&(!$conf->eventconfidentiality->enabled || $arrayfields['ac.note']['ec_mode'] >= $ec_mode) && !empty($actioncomm_static->note)) {
                    print '<blockquote class="text-muted text-left">' . dol_nl2br($actioncomm_static->note) . '</blockquote>' . "\n";
                }
                // Infos
                if (count($infos_to_print)) {
                    print '<blockquote class="text-muted text-left">' . "\n";
                    print implode("<br>\n", $infos_to_print);
                    print '</blockquote>' . "\n";
                }
                print '</div>' . "\n";
                print '</div>' . "\n";
                print '</div><!-- end of right event <-->' . "\n";
                print '<div class="clearfix"></div>' . "\n";
            }
            $i++;
        }

        $db->free($resql);

        $parameters = array('arrayfields' => $arrayfields, 'list_mode' => $list_mode, 'sql' => $sql);
        $reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters);    // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;

        // Mode list
        if ($list_mode == 0) {
            print '</table>' . "\n";
            print '</div>' . "\n";
            print '</form>' . "\n";
        }
    } else {
        dol_print_error($db);
    }
}

/**
 *  Show html area for list of time lines of the request
 *
 * @param	RequestManager	$requestmanager     Request Manager object
 * @return	void
 */
function requestmanager_show_timelines(&$requestmanager)
{
    global $conf, $langs, $db, $user, $hookmanager;
    global $action, $form;

    $list_mode = GETPOST('list_mode', 'int');
    if ($list_mode === "") $list_mode = $_SESSION['rm_list_mode'];
    if ($list_mode === "" || !isset($list_mode)) $list_mode = !empty($conf->global->REQUESTMANAGER_DEFAULT_LIST_MODE) ? $conf->global->REQUESTMANAGER_DEFAULT_LIST_MODE : 0;
    if ($list_mode != 2) return 0;
    $_SESSION['rm_list_mode'] = $list_mode;

    $limit = GETPOST('limit') ? GETPOST('limit', 'int') : $conf->liste_limit;
    $page = GETPOST("page", 'int');
    if (empty($page) || $page == -1) {
        $page = 0;
    }     // If $page is not defined, or '' or -1
    $offset = $limit * $page;
    $pageprev = $page - 1;
    $pagenext = $page + 1;
    $sortfield = 'ac.datec';
    $sortorder = 'DESC';

    // Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
    $contextpage = 'requestmanagertimeline';
    $hookmanager->initHooks(array($contextpage));

    require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
    $extrafields = new ExtraFields($db);

    // fetch optionals attributes and labels
    $extralabels = $extrafields->fetch_name_optionals_label('requestmanager_message');

    /*
     * Actions
     */

    $parameters = array('requestmanager' => &$requestmanager);
    $reshook = $hookmanager->executeHooks('doActions', $parameters, $actioncomm, $action);    // Note that $action and $object may have been modified by some hooks
    if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

    /*
     * View
     */

    $sql = "SELECT ac.id,";
    $sql .= " ac.id as ref,";
    $sql .= " ac.datec,";
    $sql .= " ac.tms as datem,";
    $sql .= " ac.code, ac.label, ac.note,";
    $sql .= " ac.fk_user_author, ac.fk_user_mod,";
    $sql .= " rmm.notify_assigned, rmm.notify_requesters, rmm.notify_watchers,";
    $sql .= " GROUP_CONCAT(DISTINCT rmmkb.fk_knowledge_base SEPARATOR ',') AS knowledge_base_ids,";
    $sql .= " cac.id as type_id, cac.code as type_code, cac.libelle as type_label, cac.color as type_color, cac.picto as type_picto,";
    $sql .= " ua.firstname as userauthorfirstname, ua.lastname as userauthorlastname, ua.email as userauthoremail, ua.photo as userauthorphoto, ua.gender as userauthorgender,";
    $sql .= " um.firstname as usermodfirstname, um.lastname as usermodlastname, um.email as usermodemail";
    // Event confidentiality support
    if ($conf->eventconfidentiality->enabled) {
        dol_include_once('/eventconfidentiality/class/eventconfidentiality.class.php');
        $sql .= ", MIN(IFNULL(ecm.mode, " . EventConfidentiality::MODE_HIDDEN . ")) as ec_mode";
    }
    // Add fields from extrafields
    foreach ($extrafields->attribute_label as $key => $val) $sql .= ($extrafields->attribute_type[$key] != 'separate' ? ",ef." . $key . ' as options_' . $key : '');
    // Add fields from hooks
    $parameters = array();
    $reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters);    // Note that $action and $object may have been modified by hook
    $sql .= $hookmanager->resPrint;
    $sql .= " FROM " . MAIN_DB_PREFIX . "actioncomm as ac ";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "requestmanager_message as rmm ON ac.id = rmm.fk_actioncomm";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "requestmanager_message_knowledge_base as rmmkb ON ac.id = rmmkb.fk_actioncomm";
    if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "requestmanager_message_extrafields as ef on (ac.id = ef.fk_object)";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_actioncomm as cac ON cac.id = ac.fk_action";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as ua ON ua.rowid = ac.fk_user_author";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as um ON um.rowid = ac.fk_user_mod";
    // Event confidentiality support
    if ($conf->eventconfidentiality->enabled) {
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "eventconfidentiality_mode as ecm ON ecm.fk_actioncomm = ac.id";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_eventconfidentiality_tag AS cect ON ecm.fk_c_eventconfidentiality_tag = cect.rowid";
    }
    // Add 'from' from hooks
    $parameters = array();
    $reshook = $hookmanager->executeHooks('printFieldListFrom', $parameters);    // Note that $action and $object may have been modified by hook
    $sql .= ' WHERE ac.entity IN (' . getEntity('agenda') . ')';
    $sql .= " AND (ac.fk_element = " . $requestmanager->id . " AND ac.elementtype = '" . $requestmanager->element . "')";
    $sql .= " AND (ac.code = 'AC_RM_IN' OR ac.code = 'AC_RM_OUT' OR ac.code = 'AC_RM_PRIV')";
    // Event confidentiality support
    if ($conf->eventconfidentiality->enabled && !$user->rights->eventconfidentiality->manage) {
        $eventconfidentiality = new EventConfidentiality($db);
        $tags_list = $eventconfidentiality->getConfidentialTagsOfUser($user);
        if (!is_array($tags_list)) {
            dol_print_error('', $eventconfidentiality->error, $eventconfidentiality->errors);
        }
        if ($user->socid > 0) {
            $sql .= ' AND cect.external = 1';
        } else {
            $sql .= ' AND (cect.external IS NULL OR cect.external = 0)';
        }
        $sql .= ' AND ecm.fk_c_eventconfidentiality_tag IN (' . (count($tags_list) > 0 ? implode(',', $tags_list) : -1) . ')';
    }
    // Add where from hooks
    $parameters = array();
    $reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters);    // Note that $action and $object may have been modified by hook
    $sql .= $hookmanager->resPrint;

    $sql .= " GROUP BY ac.id";
    // Event confidentiality support
    if ($conf->eventconfidentiality->enabled && !$user->rights->eventconfidentiality->manage) {
        $sql .= ' HAVING ec_mode != ' . EventConfidentiality::MODE_HIDDEN;
    }
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
        $title = $langs->trans('RequestManagerTimeLineMessages');

        $num = $db->num_rows($resql);

        $param = '&id=' . urlencode($requestmanager->id);
        if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit=' . urlencode($limit);

        // Button for change the view mode of the list
        $morehtml = '';
        $morehtml .= '<a class="' . ($list_mode == 0 ? 'butActionRefused' : 'butAction') . '" href="' . $_SERVER['PHP_SELF'] . '?list_mode=0&page=' . urlencode($page) . $param . '#events-balise">';
        $morehtml .= $langs->trans("RequestManagerListMode");
        $morehtml .= '</a>';
        $morehtml .= '<a class="' . ($list_mode == 1 ? 'butActionRefused' : 'butAction') . '" href="' . $_SERVER['PHP_SELF'] . '?list_mode=1&page=' . urlencode($page) . $param . '#events-balise">';
        $morehtml .= $langs->trans("RequestManagerTimeLineListMode");
        $morehtml .= '</a>';
        $morehtml .= '<a class="' . ($list_mode == 2 ? 'butActionRefused' : 'butAction') . '" href="' . $_SERVER['PHP_SELF'] . '?list_mode=2&page=' . urlencode($page) . $param . '#timeline-balise">';
        $morehtml .= $langs->trans("RequestManagerTimeLineMode");
        $morehtml .= '</a>';

        if ($list_mode > 0) $param .= '&list_mode=' . urlencode($list_mode);

        print '<div id="timeline-balise"></div>' . "\n";

        // Lignes des champs de filtre
        print '<form method="POST" id="searchFormList" action="' . $_SERVER["PHP_SELF"] . '?id=' . $requestmanager->id . '#timeline-container">';
        print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
        print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
        print '<input type="hidden" name="action" value="list">';
        print '<input type="hidden" name="page" value="' . $page . '">';
        print '<input type="hidden" name="list_mode" value="' . $list_mode . '">';

        print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, '', '', $morehtml, $num, $nbtotalofrecords, '', 0, '', '', $limit);

        print '</form>' . "\n";

        // Mode timeline of message
        print '<link rel="stylesheet" type="text/css" href="' . dol_buildpath('/requestmanager/css/requestmanager_timeline.css.php', 1) . '">';
        print '<link rel="stylesheet" type="text/css" href="' . dol_buildpath('/requestmanager/css/bootstrap.min.css', 1) . '">';
        print '<script src="' . dol_buildpath('/requestmanager/js/TweenMax.min.js', 1) . '"></script>';
        print <<<SCRIPT
    <script type="text/javascript" language="javascript">
        $(document).ready(function () {
            // Effect show message HTML 5
            TweenMax.staggerFrom($('.r-event .event-body'), 1, {x:  100, ease:Bounce.easeOut}, 1);
            TweenMax.staggerFrom($('.l-event .event-body'), 1, {x: -100, ease:Bounce.easeOut}, 1);
        });
    </script>
SCRIPT;
        print '<div id="timeline-container">' . "\n";
        print '<section id="timeline-wrapper">' . "\n";
        print '<div class="container-fluid">' . "\n";
        print '<div class="row">' . "\n";

        dol_include_once('/requestmanager/class/requestmanagermessage.class.php');
        $requestmessage_static = new RequestManagerMessage($db);

        require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
        $formfile = new FormFile($db);

        require_once DOL_DOCUMENT_ROOT . '/comm/action/class/cactioncomm.class.php';
        $caction_static = new CActionComm($db);
        $cActionList = $caction_static->liste_array(1, 'code', '', (empty($conf->global->AGENDA_USE_EVENT_TYPE) ? 1 : 0));

        dol_include_once('/advancedictionaries/class/dictionary.class.php');
        $knowledge_base = Dictionary::getDictionary($db, 'requestmanager', 'requestmanagerknowledgebase');
        $knowledge_base->fetch_lines(1);

        require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
        $userauthor_static = new User($db);
        $usermodif_static = new User($db);
        $userempty_static = new User($db);

        $last_day = "";
        $today_day = dol_print_date(dol_now(), 'daytext');

        $i = 0;
        while ($i < min($num, $limit)) {
            $obj = $db->fetch_object($resql);

            $ec_mode = $obj->ec_mode;
            if ($ec_mode == EventConfidentiality::MODE_HIDDEN && $user->rights->eventconfidentiality->manage) $ec_mode = EventConfidentiality::MODE_VISIBLE;

            $requestmessage_static->id = $obj->id;
            $requestmessage_static->ref = $obj->ref;

            // Properties of parent table llx_c_actioncomm
            $requestmessage_static->type_id = $obj->type_id;
            $requestmessage_static->type_code = $obj->type_code;
            $requestmessage_static->type_color = $obj->type_color;
            $requestmessage_static->type_picto = $obj->type_picto;
            $transcode = $langs->trans("Action" . $obj->type_code);
            $type_label = ($transcode != "Action" . $obj->type_code ? $transcode : $obj->type_label);
            $requestmessage_static->type = $type_label;

            $requestmessage_static->code = $obj->code;
            $requestmessage_static->label = $obj->label;

            $requestmessage_static->notify_assigned = $obj->notify_assigned;
            $requestmessage_static->notify_requesters = $obj->notify_requesters;
            $requestmessage_static->notify_watchers = $obj->notify_watchers;

            $requestmessage_static->knowledge_base_ids = !empty($obj->knowledge_base_ids) ? explode(',', $obj->knowledge_base_ids) : array();

            $requestmessage_static->datec = $db->jdate($obj->datec);
            $requestmessage_static->datem = $db->jdate($obj->datem);
            $current_day = dol_print_date($requestmessage_static->datec, 'daytext');

            $requestmessage_static->note = $obj->note;

            $requestmessage_static->fk_element = $obj->fk_element;
            $requestmessage_static->elementtype = $obj->elementtype;

            // User Author
            $userauthor_static->id = $obj->fk_user_author;
            $userauthor_static->firstname = $obj->userauthorfirstname;
            $userauthor_static->lastname = $obj->userauthorlastname;
            $userauthor_static->email = $obj->userauthoremail;
            $userauthor_static->photo = $obj->userauthorphoto;
            $userauthor_static->gender = $obj->userauthorgender;
            // User Modif
            $usermodif_static->id = $obj->fk_user_mod;
            $usermodif_static->firstname = $obj->usermodfirstname;
            $usermodif_static->lastname = $obj->usermodlastname;
            $usermodif_static->email = $obj->usermodemail;

            if ($last_day != $current_day) {
                if (!empty($last_day)) {
                    print '<br>' . "\n";
                    print '</div><!-- end of timeline-events -->' . "\n";
                    print '<div class="clearfix"></div>' . "\n";
                    print '</div><!-- end of timeline-block -->' . "\n";
                } elseif ($page > 0) {
                    print '<div class="timeline-block">' . "\n";
                    print '<div class="timeline-events">' . "\n";
                    print '<br>' . "\n";
                    print '</div><!-- end of timeline-events -->' . "\n";
                    print '<div class="clearfix"></div>' . "\n";
                    print '</div><!-- end of timeline-block -->' . "\n";
                }
                print '<div class="timeline-top">' . "\n";
                print '<div class="top-day">' . ($today_day == $current_day ? $langs->trans('Today') : $current_day) . '</div>' . "\n";
                print '</div>' . "\n";
                print '<div class="timeline-block">' . "\n";
                print '<div class="timeline-events">' . "\n";
                $last_day = $current_day;
            }

            // Get infos to print
            $infos_to_print = array();
            $notification = '';
                if (!$conf->eventconfidentiality->enabled || EventConfidentiality::MODE_BLURRED >= $ec_mode) {
                    $notified = array();
                    if (!empty($requestmessage_static->notify_assigned)) $notified[] = $langs->trans('RequestManagerAssignedNotified');
                    if (!empty($requestmessage_static->notify_requesters)) $notified[] = $langs->trans('RequestManagerRequesterNotified');
                    if (!empty($requestmessage_static->notify_watchers)) $notified[] = $langs->trans('RequestManagerWatcherNotified');
                    $notification = count($notified) > 0 ? ' ' . $form->textwithpicto('', implode('<br>', $notified), 1, 'object_email.png') : '';
                }

                $icon = $requestmessage_static->code == 'AC_RM_IN' ? 'fa-angle-double-left' : ($requestmessage_static->code == 'AC_RM_OUT' ? 'fa-angle-double-right' : 'fa-lock');
                $direction = $requestmessage_static->code == 'AC_RM_IN' ? 'r-event' : 'l-event';

                if (!$conf->eventconfidentiality->enabled || EventConfidentiality::MODE_BLURRED >= $ec_mode) {
                    $knowledge_base_to_print = array();
                    foreach ($requestmessage_static->knowledge_base_ids as $knowledge_base_id) {
                        $knowledge_base_to_print[] = '<li class="select2-search-choice-dolibarr noborderoncategories style="background: #aaa">' . $knowledge_base->lines[$knowledge_base_id]->fields['code'] . ' - ' . $knowledge_base->lines[$knowledge_base_id]->fields['title'] . '</li>';
                    }
                    if (count($knowledge_base_to_print)) {
                        $infos_to_print[] = $langs->trans("RequestManagerMessageKnowledgeBase") . ' : ' .
                            '<div class="select2-container-multi-dolibarr" style="width: 90%;"><ul class="select2-choices-dolibarr">' . implode(' ', $knowledge_base_to_print) . '</ul></div>';
                    }
                }

            // Attached files
            if (!$conf->eventconfidentiality->enabled || EventConfidentiality::MODE_BLURRED >= $ec_mode) {
                $files_to_print = array();
                $upload_dir = $conf->agenda->dir_output . '/' . dol_sanitizeFileName($requestmessage_static->ref);
                $filearray = dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$');
                foreach ($filearray as $file) {
                    $relativepath = $requestmessage_static->id . '/' . $file["name"];

                    $documenturl = DOL_URL_ROOT . '/document.php';
                    if (isset($conf->global->DOL_URL_ROOT_DOCUMENT_PHP)) $documenturl = $conf->global->DOL_URL_ROOT_DOCUMENT_PHP;    // To use another wrapper

                    // Show file name with link to download
                    $tmp = $formfile->showPreview($file, 'agenda', $relativepath, 0, '');
                    $out = ($tmp ? $tmp . ' ' : '');
                    $out .= '<a class="documentdownload" href="' . $documenturl . '?modulepart=agenda&amp;file=' . urlencode($relativepath) . '"';
                    $mime = dol_mimetype($relativepath, '', 0);
                    if (preg_match('/text/', $mime)) $out .= ' target="_blank"';
                    $out .= ' target="_blank">';
                    $out .= img_mime($file["name"], $langs->trans("File") . ': ' . $file["name"]) . ' ' . $file["name"];
                    $out .= '</a>';

                    $files_to_print[] = $out;
                }
                if (count($files_to_print)) {
                    $infos_to_print[] = '<br>' . $langs->trans("Documents") . ' : ' . implode(' , ', $files_to_print);
                }
            }

            print '<div class="row"></div>' . "\n";
            print '<div class="event ' . $direction . ' col-md-6 col-sm-6 col-xs-8 "><span class="thumb fa ' . $icon . '"></span>' . "\n";
            print '<div class=" event-body">' . "\n";
            print '<div class="person-image pull-left ">' . "\n";
            if ((!$conf->eventconfidentiality->enabled || EventConfidentiality::MODE_BLURRED >= $ec_mode)) {
                print Form::showphoto('userphoto', $userauthor_static, 100, 0, 0, '', 'small', 0, 1) . "\n";
            } else {
                print Form::showphoto('userphoto', $userempty_static, 100, 0, 0, '', 'small', 0, 1) . "\n";
            }
            print '</div>' . "\n";
            print '<div class="event-content">' . "\n";
            print '<h5 class="text-primary text-left">' . "\n";
            $to_print = array();
            // Ref
            if ((!$conf->eventconfidentiality->enabled || EventConfidentiality::MODE_BLURRED >= $ec_mode) && $requestmessage_static->id > 0) {
                $to_print[] = $requestmessage_static->getNomUrl(2);
            }
            // Title
            if ((!$conf->eventconfidentiality->enabled || EventConfidentiality::MODE_VISIBLE >= $ec_mode) && !empty($requestmessage_static->label)) {
                $to_print[] = $requestmessage_static->label;
            }
            // Type
            if ((!$conf->eventconfidentiality->enabled || EventConfidentiality::MODE_BLURRED >= $ec_mode)) {
                $labeltype = $requestmessage_static->type_code;
                if (empty($conf->global->AGENDA_USE_EVENT_TYPE) && empty($cActionList[$labeltype])) $labeltype = 'AC_OTH';
                if (!empty($cActionList[$labeltype])) $labeltype = $cActionList[$labeltype];
                if (!empty($labeltype)) $to_print[] = '(' . $labeltype . ')';
            }
            $to_print[] = $notification;
            print implode(' ', $to_print);
            print '</h5>' . "\n";
            print '<span class="text-muted text-left" style="display:block; margin: 0"><small>' . "\n";
            if ((!$conf->eventconfidentiality->enabled || EventConfidentiality::MODE_BLURRED >= $ec_mode) && $userauthor_static->id > 0) {
                print $userauthor_static->getNomUrl();
            }
            if ((!$conf->eventconfidentiality->enabled || EventConfidentiality::MODE_VISIBLE >= $ec_mode) && $requestmessage_static->datec > 0) {
                print ' , ' . dol_print_date($requestmessage_static->datec, 'dayhour');
            }
            if ($requestmessage_static->datem > 0 && $requestmessage_static->datec != $requestmessage_static->datem) {
                $to_print = array();
                if ((!$conf->eventconfidentiality->enabled || EventConfidentiality::MODE_BLURRED >= $ec_mode) && $usermodif_static->id > 0) {
                    $to_print[] = $usermodif_static->getNomUrl();
                }
                if ((!$conf->eventconfidentiality->enabled || EventConfidentiality::MODE_VISIBLE >= $ec_mode) && $requestmessage_static->datem > 0) {
                    $to_print[] = ' , ' . dol_print_date($requestmessage_static->datem, 'dayhour');
                }
                if (count($to_print)) {
                    print ' ( ' . $langs->trans('ModifiedBy') . ': ' . implode('', $to_print) . "\n";
                }
            }
            print '</small></span>' . "\n";
            print '<br>' . "\n";
            print '<br>' . "\n";
            // Description
            if ((!$conf->eventconfidentiality->enabled || EventConfidentiality::MODE_BLURRED >= $ec_mode) && !empty($requestmessage_static->note)) {
                print '<blockquote class="text-muted text-left">' . dol_nl2br($requestmessage_static->note) . '</blockquote>' . "\n";
            }
            // Infos
            if (count($infos_to_print)) {
                print '<blockquote class="text-muted text-left">' . "\n";
                print implode("<br>\n", $infos_to_print);
                print '</blockquote>' . "\n";
            }
            print '</div>' . "\n";
            print '</div>' . "\n";
            print '</div><!-- end of right event <-->' . "\n";
            print '<div class="clearfix"></div>' . "\n";
            $i++;
        }

        $db->free($resql);

        $parameters = array('sql' => $sql, 'i' => &$i);
        $reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters);    // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;

        if ($i > 0) {
            print '</div><!-- end of timeline-events -->' . "\n";
            print '<div class="clearfix"></div>' . "\n";
            print '</div><!-- end of timeline-block -->' . "\n";
        }

        print '</div><!-- end of row -->' . "\n";
        print '</div><!-- end of container-fluid -->' . "\n";
        print '</section><!-- end of timeline-wrapper -->' . "\n";
        print '</div><!-- end of timeline-container -->' . "\n";
    } else {
        dol_print_error($db);
    }
}

/**
 *	Get an array with properties of all element of the linked object of the event of a thridparty
 *
 * @param 	int 	$socid 	 	Id of the thridparty
 * @return 	array 	 	 	 	array('element'=>array('label'=>'label of the element', 'picto' => 'picto of the element'))
 */
function requestmanager_get_all_element_of_events($socid)
{
    global $langs, $db;
    $elements = array();

    // Get all element type of the event linked to the thridparty
    $sql = "SELECT DISTINCT elementtype FROM " . MAIN_DB_PREFIX . "actioncomm WHERE fk_soc = " . $socid;
    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $key = empty($obj->elementtype) ? 'societe' : $obj->elementtype;
            $elements[$key] = '';
        }
    }

    // Load infos
    $elements_infos = requestmanager_get_elements_infos();
    foreach ($elements as $key => $element) {
        $label = '';
        $picto = '';
        if (isset($elements_infos[$key])) {
            $langs->loadLangs($elements_infos[$key]['langs']);
            $label = $langs->trans($elements_infos[$key]['label']);
            $picto = $elements_infos[$key]['picto'];
        }
        $elements[$key] = array('label'=>$label, 'picto' => $picto);
    }

    return $elements;
}

/**
 *	Get an array with properties of all element object
 *
 * @return 	array 	 	 	array('element'=>array('label'=>'label of the element', 'langs'=>'language file of the element', 'picto' => 'picto of the element')
 */
function requestmanager_get_elements_infos()
{
    global $hookmanager;

    // TODO to completed
    $elements = array(
        'accounting_category' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'accounting_journal' => array(
            'label' => 'AccountingJournal',
            'langs' => array('accountancy'),
            'picto' => 'object_billr',
        ),
        'accountingbookkeeping' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'action' => array(
            'label' => 'Action',
            'langs' => array(),
            'picto' => 'object_action',
        ),
        'adherent_type' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'advtargetemailing' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'bank' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'bank_account' => array(
            'label' => 'Account',
            'langs' => array('banks'),
            'picto' => 'object_account',
        ),
        'bookmark' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'category' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'cchargesociales' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'chargesociales' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'chequereceipt' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'commande' => array(
            'label' => 'Order',
            'langs' => array('orders'),
            'picto' => 'object_order',
        ),
        'order' => array(
            'label' => 'Order',
            'langs' => array('orders'),
            'picto' => 'object_order',
        ),
        'commandefournisseurdispatch' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'contact' => array(
            'label' => 'Contact',
            'langs' => array('compagnies'),
            'picto' => 'object_contact',
        ),
        'contrat' => array(
            'label' => 'Contract',
            'langs' => array('contracts'),
            'picto' => 'object_contract',
        ),
        'contract' => array(
            'label' => 'Contract',
            'langs' => array('contracts'),
            'picto' => 'object_contract',
        ),
        'cpaiement' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'cronjob' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'ctyperesource' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'delivery' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'deplacement' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'dolresource' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'don' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'ecm_directories' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'ecmfiles' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'equipement' => array(
            'label' => 'Equipement',
            'langs' => array('equipement@equipement'),
            'picto' => 'object_equipement@equipement',
        ),
        'establishment' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'events' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'expeditionlignebatch' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'expensereport' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'facture' => array(
            'label' => 'Invoice',
            'langs' => array('bills'),
            'picto' => 'object_bill',
        ),
        'invoice' => array(
            'label' => 'Invoice',
            'langs' => array('bills'),
            'picto' => 'object_bill',
        ),
        'facturerec' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'fichinter' => array(
            'label' => 'Intervention',
            'langs' => array('interventions'),
            'picto' => 'object_intervention',
        ),
        'fiscalyear' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'holiday' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'inventory' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'invoice_supplier' => array(
            'label' => 'SupplierInvoice',
            'langs' => array('bills'),
            'picto' => 'object_bill',
        ),
        'link' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'loan' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'loan_schedule' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'mailing' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'member' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'multicurrency' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'multicurrency_rate' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'opensurvey_sondage' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'order_supplier' => array(
            'label' => 'SupplierOrder',
            'langs' => array('orders'),
            'picto' => 'object_order',
        ),
        'paiementcharge' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'payment' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'payment_donation' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'payment_expensereport' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'payment_loan' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'payment_supplier' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'product' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'productbatch' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'productlot' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'ProductStockEntrepot' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'project' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'project_task' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'propal' => array(
            'label' => 'Propal',
            'langs' => array('propal'),
            'picto' => 'object_propal',
        ),
        'propal_merge_pdf_product' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'shipping' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'societe' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'stock' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'stockmouvement' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'subscription' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'supplier_proposal' => array(
            'label' => 'SupplierProposal',
            'langs' => array('supplier_proposal'),
            'picto' => 'object_supplier_proposal',
        ),
        'user' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'usergroup' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'website' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'websitepage' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'widthdraw' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'requestmanager' => array(
            'label' => 'RequestManagerRequest',
            'langs' => array('requestmanager@requestmanager'),
            'picto' => 'object_requestmanager@requestmanager',
        ),
    );

    // Add custom object
    $hookmanager->initHooks(array('requestmanagerdao'));
    $parameters = array();
    $reshook = $hookmanager->executeHooks('getElementsInfos', $parameters); // Note that $action and $object may have been
    if ($reshook) $elements = array_merge($elements, $hookmanager->resArray);

    return $elements;
}

/**
 * Return the duration information array('days', 'hours', 'minutes', 'seconds')
 *
 * @param	int	    $timestamp		Duration in second
 * @param	int	    $day			Get days
 * @param   int     $hour_minute    Get hours / minutes
 * @param   int     $second         Get seconds
 *
 * @return	array                  array informations
 */
function requestmanager_get_duration($timestamp, $day = 1, $hour_minute = 1, $second = 0)
{
    $days = $hours = $minutes = $seconds = 0;

    if (!empty($timestamp)) {
        if ($day) {
            $days = floor($timestamp / 86400);
            $timestamp -= $days * 86400;
        }

        if ($hour_minute) {
            $hours = floor($timestamp / 3600);
            $timestamp -= $hours * 3600;

            $minutes = floor($timestamp / 60);
            $timestamp -= $minutes * 60;
        }

        if ($second) {
            $seconds = $timestamp;
        }
    }

    return array('days' => $days, 'hours' => $hours, 'minutes' => $minutes, 'seconds' => $seconds);
}

/**
 * Return a formatted duration (x days x hours x minutes x seconds)
 *
 * @param	int	    $timestamp		Duration in second
 * @param	int	    $day			Show days
 * @param   int     $hour_minute    Show hours / minutes
 * @param   int     $second         Show seconds
 *
 * @return	string                  Formated duration
 */
function requestmanager_print_duration($timestamp, $day = 1, $hour_minute = 1, $second = 0)
{
    global $langs;

    $duration_infos = requestmanager_get_duration($timestamp, $day, $hour_minute, $second);

    $text = '';
    if ($duration_infos['days'] > 0) $text .= $duration_infos['days'] . ' ' . $langs->trans('Days');
    if ($duration_infos['hours'] > 0) $text .= ' ' . $duration_infos['hours'] . ' ' . $langs->trans('Hours');
    if ($duration_infos['minutes'] > 0) $text .= ' ' . $duration_infos['minutes'] . ' ' . $langs->trans('Minutes');
    if ($duration_infos['seconds'] > 0) $text .= ' ' . $duration_infos['seconds'] . ' ' . $langs->trans('Seconds');

    return trim($text);
}
