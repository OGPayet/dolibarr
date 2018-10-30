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
    if (empty($list_mode)) $list_mode = 0;
    if ($list_mode == 1) return 0;
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
    $search_author = GETPOST('search_author', 'alpha');
    $search_modified_by = GETPOST('search_modified_by', 'alpha');
    $search_date_created = GETPOST('search_date_created', 'alpha');
    $search_date_modified = GETPOST('search_date_modified', 'alpha');
    $search_status = GETPOST('search_status', 'alpha');
    $optioncss = GETPOST('optioncss', 'alpha');
    if ($search_event_on_full_day === "") $search_event_on_full_day = -1;

    // Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
    $contextpage = 'requestmanagereventlist';
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

    $arrayfields = array(
        'fk_request' => array('label' => $langs->trans("RequestManagerRequest"), 'checked' => 1, 'ec_mode' => 1),
        'ac.elementtype' => array('label' => $langs->trans("Origin"), 'checked' => 1, 'ec_mode' => 1),
        's.nom' => array('label' => $langs->trans("ThirdParty"), 'checked' => 1, 'ec_mode' => 1),
        'ac.id' => array('label' => $langs->trans("Ref"), 'checked' => 1, 'ec_mode' => 1),
        'ac.fk_action' => array('label' => $langs->trans("Type"), 'checked' => 1, 'ec_mode' => 1),
        'ac.label' => array('label' => $langs->trans("Title"), 'checked' => 1, 'ec_mode' => 2),
        'ac.note' => array('label' => $langs->trans("Description"), 'checked' => 1, 'ec_mode' => 1),
        'ac.fulldayevent' => array('label' => $langs->trans("EventOnFullDay"), 'checked' => 0, 'ec_mode' => 2),
        'ac.datep' => array('label' => $langs->trans("DateStart"), 'checked' => 1, 'ec_mode' => 2),
        'ac.datep2' => array('label' => $langs->trans("DateEnd"), 'checked' => 1, 'ec_mode' => 2),
        'ac.location' => array('label' => $langs->trans("Location"), 'checked' => 0, 'enabled' => empty($conf->global->AGENDA_DISABLE_LOCATION), 'ec_mode' => 1),
        'ac.fk_element' => array('label' => $langs->trans("LinkedObject"), 'checked' => 0, 'ec_mode' => 2),
        'ac.fk_user_action' => array('label' => $langs->trans("ActionsOwnedByShort"), 'checked' => 0, 'ec_mode' => 1),
//        'ac.userassigned' => array('label' => $langs->trans("ActionAssignedTo"), 'checked' => 0, 'ec_mode' => 1),
        'ac.fk_user_done' => array('label' => $langs->trans("ActionDoneBy"), 'checked' => 0, 'enabled' => $conf->global->AGENDA_ENABLE_DONEBY, 'ec_mode' => 1),
        'ac.fk_project' => array('label' => $langs->trans("Project"), 'checked' => 0, 'ec_mode' => 1),
        'ac.priority' => array('label' => $langs->trans("Priority"), 'checked' => 0, 'ec_mode' => 1),
        'ac.fk_user_author' => array('label' => $langs->trans("Author"), 'checked' => 0, 'position' => 10, 'ec_mode' => 1),
        'ac.fk_user_mod' => array('label' => $langs->trans("ModifiedBy"), 'checked' => 0, 'position' => 10, 'ec_mode' => 1),
        'ac.datec' => array('label' => $langs->trans("DateCreation"), 'checked' => 1, 'position' => 500, 'ec_mode' => 2),
        'ac.tms' => array('label' => $langs->trans("DateModificationShort"), 'checked' => 0, 'position' => 500, 'ec_mode' => 2),
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
    $sql .= " s.rowid as soc_id, s.client as soc_client, s.nom as soc_name, s.name_alias as soc_name_alias,";
    $sql .= " cac.id as type_id, cac.code as type_code, cac.libelle as type_label, cac.color as type_color, cac.picto as type_picto,";
    $sql .= " uo.firstname as userownerfirstname, uo.lastname as userownerlastname, uo.email as userowneremail,";
    $sql .= " ud.firstname as userdonefirstname, ud.lastname as userdonelastname, ud.email as userdoneemail,";
    $sql .= " ua.firstname as userauthorfirstname, ua.lastname as userauthorlastname, ua.email as userauthoremail, ua.photo as userauthorphoto, ua.gender as userauthorgender,";
    $sql .= " um.firstname as usermodfirstname, um.lastname as usermodlastname, um.email as usermodemail,";
    $sql .= " p.ref as projectref, p.title as projecttitle, p.public as projectpublic, p.fk_statut as projectstatus, p.datee as projectdatee";
    if ($conf->eventconfidentiality->enabled) {
        $sql .= ", MAX(ea.level_confid) as ec_mode";
    }
    // Add fields from extrafields
    foreach ($extrafields->attribute_label as $key => $val) $sql .= ($extrafields->attribute_type[$key] != 'separate' ? ",ef." . $key . ' as options_' . $key : '');
    foreach ($extrafields_message->attribute_label as $key => $val) $sql .= ($extrafields_message->attribute_type[$key] != 'separate' ? ",efm." . $key . ' as m_options_' . $key : '');
    // Add fields from hooks
    $parameters = array();
    $reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters);    // Note that $action and $object may have been modified by hook
    $sql .= $hookmanager->resPrint;
    $sql .= " FROM " . MAIN_DB_PREFIX . "actioncomm as ac ";
    if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "actioncomm_extrafields as ef on (ac.id = ef.fk_object)";
    if (is_array($extrafields_message->attribute_label) && count($extrafields_message->attribute_label)) $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "requestmanager_message_extrafields as efm on (ac.id = efm.fk_object)";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_actioncomm as cac ON cac.id = ac.fk_action";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as s ON s.rowid = ac.fk_soc";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as uo ON uo.rowid = ac.fk_user_action";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as ud ON ud.rowid = ac.fk_user_done";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as ua ON ua.rowid = ac.fk_user_author";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as um ON um.rowid = ac.fk_user_mod";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "projet as p ON p.rowid = ac.fk_project";
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
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "event_agenda as ea ON ea.fk_object = ac.id";
    }
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
        $search_type_tmp = $search_type;
        if (in_array('AC_NON_AUTO', $search_type_tmp) || in_array('AC_OTH', $search_type_tmp)) {
            $sql .= " AND cac.type != 'systemauto'";
            if (($key = array_search('AC_NON_AUTO', $search_type_tmp)) !== false) {
                unset($search_type_tmp[$key]);
            }
            if (($key = array_search('AC_OTH', $search_type_tmp)) !== false) {
                unset($search_type_tmp[$key]);
            }
        }
        if (in_array('AC_ALL_AUTO', $search_type_tmp) || in_array('AC_OTH_AUTO', $search_type_tmp)) {
            $sql .= " AND cac.type = 'systemauto'";
            if (($key = array_search('AC_ALL_AUTO', $search_type_tmp)) !== false) {
                unset($search_type_tmp[$key]);
            }
            if (($key = array_search('AC_OTH_AUTO', $search_type_tmp)) !== false) {
                unset($search_type_tmp[$key]);
            }
        }
        $sql .= " AND cac.code IN ('" . implode("','", $search_type_tmp) . "')";
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
    if ($conf->eventconfidentiality->enabled) {
        $tags_list = get_user_confidentiality_tags($user);
        $sql .= ' AND ea.externe ' . ($user->socid > 0 ? '=' : '!=') . ' 1';
        $sql .= ' AND ea.fk_dict_tag_confid IN (' . (count($tags_list) > 0 ? implode(',', $tags_list) : -1) . ')';
    }
    // Add where from hooks
    $parameters = array();
    $reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters);    // Note that $action and $object may have been modified by hook
    $sql .= $hookmanager->resPrint;

    $sql .= " GROUP BY ac.id";
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
        $morehtml .= '<a class="' . ($list_mode == 1 ? 'butActionRefused' : 'butAction') . '" href="' . $_SERVER['PHP_SELF'] . '?list_mode=1&page=' . urlencode($page) . $param2 . '#timeline-balise">';
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
        foreach ($elements as $key => $element) {
            $elements_array[$key] = img_picto($element['label'], $element['picto']) . ' ' . $element['label'];
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

        print '<div class="div-table-responsive">';
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
        print '<td class="liste_titre" align="middle">';
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
        print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', $sortfield, $sortorder, 'maxwidthsearch ');
        print '</tr>' . "\n";

        $actioncomm_static = new ActionComm($db);

        $societe_static = new Societe($db);

        require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
        $userowner_static = new User($db);
        $userdone_static = new User($db);
        $userauthor_static = new User($db);
        $usermodif_static = new User($db);

        require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
        $project_static = new Project($db);

        require_once DOL_DOCUMENT_ROOT . '/comm/action/class/cactioncomm.class.php';
        $caction_static = new CActionComm($db);
        $cActionList = $caction_static->liste_array(1, 'code', '', (empty($conf->global->AGENDA_USE_EVENT_TYPE) ? 1 : 0));

        $link_url_cache = array();
        $last_year = "";

        $i = 0;
        while ($i < min($num, $limit)) {
            $obj = $db->fetch_object($resql);

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

            print '<tr' . (empty($actioncomm_static->type_color) ? ' class="oddeven"' : '') . '>';

            $tdcolor = empty($actioncomm_static->type_color) ? '' : ' style="background-color: #' . ltrim($actioncomm_static->type_color, '#\s') . ';"';

            // Request Child
            if (!empty($arrayfields['fk_request']['checked'])) {
                print '<td class="nowrap"' . $tdcolor . '>';
                if (!$conf->eventconfidentiality->enabled || $arrayfields['fk_request']['ec_mode'] <= $obj->ec_mode) {
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
                if (!$conf->eventconfidentiality->enabled || $arrayfields['ac.elementtype']['ec_mode'] <= $obj->ec_mode) {
                    if (isset($elements_array[$actioncomm_static->elementtype])) print $elements_array[$actioncomm_static->elementtype];
                    else print $elements_array['societe'];
                }
                print '</td>';
            }
            // ThirdParty
            if (!empty($arrayfields['s.nom']['checked'])) {
                print '<td class="nowrap"' . $tdcolor . '>';
                if (!$conf->eventconfidentiality->enabled || $arrayfields['s.nom']['ec_mode'] <= $obj->ec_mode) {
                    print $societe_static->getNomUrl(1);
                }
                print '</td>';
            }
            // Ref
            if (!empty($arrayfields['ac.id']['checked'])) {
                print '<td class="nowrap"' . $tdcolor . '>';
                if (!$conf->eventconfidentiality->enabled || $arrayfields['ac.id']['ec_mode'] <= $obj->ec_mode) {
                    print $actioncomm_static->getNomUrl(1);
                }
                print '</td>';
            }
            // Type
            if (!empty($arrayfields['ac.fk_action']['checked'])) {
                print '<td class="nowrap"' . $tdcolor . '>';
                if (!$conf->eventconfidentiality->enabled || $arrayfields['ac.fk_action']['ec_mode'] <= $obj->ec_mode) {
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
                    print $labeltype;
                }
                print '</td>';
            }
            // Title
            if (!empty($arrayfields['ac.label']['checked'])) {
                print '<td class="nowrap"' . $tdcolor . '>';
                if (!$conf->eventconfidentiality->enabled || $arrayfields['ac.label']['ec_mode'] <= $obj->ec_mode) {
                    print $actioncomm_static->label;
                }
                print '</td>';
            }
            // Description
            if (!empty($arrayfields['ac.note']['checked'])) {
                print '<td class="nowrap tdoverflowmax300"' . $tdcolor . '>';
                if (!$conf->eventconfidentiality->enabled || $arrayfields['ac.note']['ec_mode'] <= $obj->ec_mode) {
                    print $actioncomm_static->note;
                }
                print '</td>';
            }
            // Event On Full Day
            if (!empty($arrayfields['ac.fulldayevent']['checked'])) {
                print '<td class="nowrap"' . $tdcolor . '>';
                if (!$conf->eventconfidentiality->enabled || $arrayfields['ac.fulldayevent']['ec_mode'] <= $obj->ec_mode) {
                    print yn($actioncomm_static->fulldayevent);
                }
                print '</td>';
            }
            // Date Start
            if (!empty($arrayfields['ac.datep']['checked'])) {
                print '<td class="nowrap" align="center"' . $tdcolor . '>';
                if (!$conf->eventconfidentiality->enabled || $arrayfields['ac.datep']['ec_mode'] <= $obj->ec_mode) {
                    if ($actioncomm_static->datep > 0) print dol_print_date($actioncomm_static->datep, "dayhour");
                }
                print '</td>';
            }
            // Date End
            if (!empty($arrayfields['ac.datep2']['checked'])) {
                print '<td class="nowrap" align="center"' . $tdcolor . '>';
                if (!$conf->eventconfidentiality->enabled || $arrayfields['ac.datep2']['ec_mode'] <= $obj->ec_mode) {
                    if ($actioncomm_static->datef > 0) print dol_print_date($actioncomm_static->datef, "dayhour");
                }
                print '</td>';
            }
            // Location
            if (!empty($arrayfields['ac.location']['checked'])) {
                print '<td class="nowrap"' . $tdcolor . '>';
                if (!$conf->eventconfidentiality->enabled || $arrayfields['ac.location']['ec_mode'] <= $obj->ec_mode) {
                    print $actioncomm_static->location;
                }
                print '</td>';
            }
            // Linked Object
            if (!empty($arrayfields['ac.fk_element']['checked'])) {
                print '<td class="nowrap"' . $tdcolor . '>';
                if (!$conf->eventconfidentiality->enabled || $arrayfields['ac.fk_element']['ec_mode'] <= $obj->ec_mode) {
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
                if (!$conf->eventconfidentiality->enabled || $arrayfields['ac.fk_user_action']['ec_mode'] <= $obj->ec_mode) {
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
                if (!$conf->eventconfidentiality->enabled || $arrayfields['ac.fk_user_done']['ec_mode'] <= $obj->ec_mode) {
                    if ($userdone_static->id > 0) print $userdone_static->getNomUrl(1);
                }
                print '</td>';
            }
            // Project
            if (!empty($arrayfields['ac.fk_project']['checked'])) {
                print '<td class="nowrap"' . $tdcolor . '>';
                if (!$conf->eventconfidentiality->enabled || $arrayfields['ac.fk_project']['ec_mode'] <= $obj->ec_mode) {
                    if ($project_static->id > 0) print $project_static->getNomUrl(1);
                }
                print '</td>';
            }
            // Priority
            if (!empty($arrayfields['ac.priority']['checked'])) {
                print '<td class="nowrap"' . $tdcolor . '>';
                if (!$conf->eventconfidentiality->enabled || $arrayfields['ac.priority']['ec_mode'] <= $obj->ec_mode) {
                    print $actioncomm_static->priority;
                }
                print '</td>';
            }
            // Author
            if (!empty($arrayfields['ac.fk_user_author']['checked'])) {
                print '<td class="nowrap" align="center"' . $tdcolor . '>';
                if (!$conf->eventconfidentiality->enabled || $arrayfields['ac.fk_user_author']['ec_mode'] <= $obj->ec_mode) {
                    if ($userauthor_static->id > 0) print $userauthor_static->getNomUrl(1);
                }
                print '</td>';
            }
            // Modified By
            if (!empty($arrayfields['ac.fk_user_mod']['checked'])) {
                print '<td class="nowrap" align="center"' . $tdcolor . '>';
                if (!$conf->eventconfidentiality->enabled || $arrayfields['ac.fk_user_mod']['ec_mode'] <= $obj->ec_mode) {
                    if ($usermodif_static->id > 0) print $usermodif_static->getNomUrl(1);
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
                        print $tdcolor . '>';
                        if (!$conf->eventconfidentiality->enabled || $arrayfields["ef." . $key]['ec_mode'] <= $obj->ec_mode) {
                            $tmpkey = 'options_' . $key;
                            print $extrafields->showOutputField($key, $obj->$tmpkey, '', 1);
                        }
                        print '</td>';
                    }
                }
            }
            // Extra fields message
            if (is_array($extrafields_message->attribute_label) && count($extrafields_message->attribute_label)) {
                foreach ($extrafields_message->attribute_label as $key => $val) {
                    if (!empty($arrayfields["efm." . $key]['checked'])) {
                        print '<td';
                        $align = $extrafields_message->getAlignFlag($key);
                        if ($align) print ' align="' . $align . '"';
                        print $tdcolor . '>';
                        if (!$conf->eventconfidentiality->enabled || $arrayfields["efm." . $key]['ec_mode'] <= $obj->ec_mode) {
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
                if (!$conf->eventconfidentiality->enabled || $arrayfields['ac.datec']['ec_mode'] <= $obj->ec_mode) {
                    print dol_print_date($actioncomm_static->datec, 'dayhour');
                }
                print '</td>';
            }
            // Date modif
            if (!empty($arrayfields['ac.tms']['checked'])) {
                print '<td align="center" class="nowrap"' . $tdcolor . '>';
                if (!$conf->eventconfidentiality->enabled || $arrayfields['ac.tms']['ec_mode'] <= $obj->ec_mode) {
                    print dol_print_date($actioncomm_static->datem, 'dayhour');
                }
                print '</td>';
            }
            // Status
            if (!empty($arrayfields['ac.percent']['checked'])) {
                print '<td align="right" class="nowrap"' . $tdcolor . '>';
                if (!$conf->eventconfidentiality->enabled || $arrayfields['ac.percent']['ec_mode'] <= $obj->ec_mode) {
                    print $actioncomm_static->getLibStatut(3);
                }
                print '</td>';
            }
            // Action column
            print '<td class="nowrap" align="center"' . $tdcolor . '>';
            print '</td>';

            print "</tr>\n";
            $i++;
        }

        $db->free($resql);

        $parameters = array('arrayfields' => $arrayfields, 'sql' => $sql);
        $reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters);    // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;

        // Mode list
        print '</table>' . "\n";
        print '</div>' . "\n";

        print '</form>' . "\n";
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
    if (empty($list_mode)) $list_mode = 0;
    if ($list_mode != 1) return 0;
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
    if ($conf->eventconfidentiality->enabled) {
        $sql .= ", MAX(ea.level_confid) as ec_mode";
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
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "event_agenda as ea ON ea.fk_object = ac.id";
    }
    // Add 'from' from hooks
    $parameters = array();
    $reshook = $hookmanager->executeHooks('printFieldListFrom', $parameters);    // Note that $action and $object may have been modified by hook
    $sql .= ' WHERE ac.entity IN (' . getEntity('agenda') . ')';
    $sql .= " AND (ac.fk_element = " . $requestmanager->id . " AND ac.elementtype = '" . $requestmanager->element . "')";
    $sql .= " AND (ac.code = 'AC_RM_IN' OR ac.code = 'AC_RM_OUT' OR ac.code = 'AC_RM_PRIV')";
    // Event confidentiality support
    if ($conf->eventconfidentiality->enabled) {
        $tags_list = get_user_confidentiality_tags($user);
        $sql .= ' AND ea.externe ' . ($user->socid > 0 ? '=' : '!=') . ' 1';
        $sql .= ' AND ea.fk_dict_tag_confid IN (' . (count($tags_list) > 0 ? implode(',', $tags_list) : -1) . ')';
    }
    // Add where from hooks
    $parameters = array();
    $reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters);    // Note that $action and $object may have been modified by hook
    $sql .= $hookmanager->resPrint;

    $sql .= " GROUP BY ac.id";
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
        $morehtml .= '<a class="' . ($list_mode == 1 ? 'butActionRefused' : 'butAction') . '" href="' . $_SERVER['PHP_SELF'] . '?list_mode=1&page=' . urlencode($page) . $param . '#timeline-balise">';
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

        print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $morehtml, $num, $nbtotalofrecords, '', 0, '', '', $limit);

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

        dol_include_once('/advancedictionaries/class/dictionary.class.php');
        $knowledge_base = Dictionary::getDictionary($db, 'requestmanager', 'requestmanagerknowledgebase');
        $knowledge_base->fetch_lines(1);

        require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
        $userauthor_static = new User($db);
        $usermodif_static = new User($db);

        $last_day = "";
        $today_day = dol_print_date(dol_now(), 'daytext');

        $i = 0;
        while ($i < min($num, $limit)) {
            $obj = $db->fetch_object($resql);

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
            if ($today_day = $current_day) $current_day = $langs->trans('Today');

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
                } else {
                    if ($page > 0) {
                        print '<div class="timeline-block">' . "\n";
                        print '<div class="timeline-events">' . "\n";
                        print '<br>' . "\n";
                        print '</div><!-- end of timeline-events -->' . "\n";
                        print '<div class="clearfix"></div>' . "\n";
                        print '</div><!-- end of timeline-block -->' . "\n";
                    }
                    print '<div class="timeline-top">' . "\n";
                    print '<div class="top-day">' . $current_day . '</div>' . "\n";
                    print '</div>' . "\n";
                    print '<div class="timeline-block">' . "\n";
                    print '<div class="timeline-events">' . "\n";
                    print '<br>' . "\n";
                    $last_day = $current_day;
                }
            }

            $notified = array();
            if (!empty($requestmessage_static->notify_assigned)) $notified[] = $langs->trans('RequestManagerAssignedNotified');
            if (!empty($requestmessage_static->notify_requesters)) $notified[] = $langs->trans('RequestManagerRequesterNotified');
            if (!empty($requestmessage_static->notify_watchers)) $notified[] = $langs->trans('RequestManagerWatcherNotified');
            $notification = count($notified) > 0 ? ' ' . $form->textwithpicto('', implode('<br>', $notified), 1, 'object_email.png') : '';

            $icon = $requestmessage_static->code == 'AC_RM_IN' ? 'fa-angle-double-left' : ($requestmessage_static->code == 'AC_RM_OUT' ? 'fa-angle-double-right' : 'fa-lock');
            $direction = $requestmessage_static->code == 'AC_RM_IN' ? 'r-event' : 'l-event';

            $knowledge_base_toprint = array();
            foreach ($requestmessage_static->knowledge_base_ids as $knowledge_base_id) {
                $knowledge_base_toprint[] = '<li class="select2-search-choice-dolibarr noborderoncategories style="background: #aaa">' . $knowledge_base->lines[$knowledge_base_id]->fields['code'] . ' - ' . $knowledge_base->lines[$knowledge_base_id]->fields['title'] . '</li>';
            }

            $files_toprint = array();
            $upload_dir = $conf->agenda->dir_output.'/'.dol_sanitizeFileName($requestmessage_static->ref);
            $filearray = dol_dir_list($upload_dir,"files",0,'','(\.meta|_preview.*\.png)$');
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

                $files_toprint[] = $out;
            }

            print '<div class="row"></div>' . "\n";
            print '<div class="event ' . $direction . ' col-md-6 col-sm-6 col-xs-8 "><span class="thumb fa ' . $icon . '"></span>' . "\n";
            print '<div class=" event-body">' . "\n";
            print '<div class="person-image pull-left ">' . "\n";
            print Form::showphoto('userphoto', $userauthor_static, 100, 0, 0, '', 'small', 0, 1) . "\n";
            print '</div>' . "\n";
            print '<div class="event-content">' . "\n";
            print '<h5 class="text-primary text-left">' . $requestmessage_static->getNomUrl(2) . ' ' . $requestmessage_static->type . $notification . '</h5>' . "\n";
            print '<span class="text-muted text-left" style="display:block; margin: 0"><small>' . $userauthor_static->getNomUrl() . ' , ' . dol_print_date($requestmessage_static->datec, 'dayhour') . "\n";
            if ($requestmessage_static->datem > 0 && $requestmessage_static->datec != $requestmessage_static->datem) {
                print ' ( ' . $langs->trans('ModifiedBy') . ': ' . $usermodif_static->getNomUrl() . ' , ' . dol_print_date($requestmessage_static->datem, 'dayhour') . ' )' . "\n";
            }
            print '</small></span>' . "\n";
            print '<br>' . "\n";
            print '<br>' . "\n";
            if (count($knowledge_base_toprint) || (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))) {
                print '<blockquote class="text-muted text-left">' . "\n";
                if (count($knowledge_base_toprint)) {
                    print $langs->trans("RequestManagerMessageKnowledgeBase") . ' : ';
                    print '<div class="select2-container-multi-dolibarr" style="width: 90%;"><ul class="select2-choices-dolibarr">' . implode(' ', $knowledge_base_toprint) . '</ul></div>' . "\n";
                }
                // Extra fields
                if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) {
                    foreach ($extrafields->attribute_label as $key => $label) {
                        if ($extrafields->attribute_type[$key] == 'separate') {
                            print $label . ' :<br>' . "\n";
                        } else {
                            print $label . ' : ' . $extrafields->showOutputField($key, 'options_' . $key, '', 1) . '<br>' . "\n";
                        }
                    }
                }
                // Fields from hook
                $parameters = array('obj' => $obj);
                $reshook = $hookmanager->executeHooks('printFieldListValue', $parameters);    // Note that $action and $object may have been modified by hook
                print $hookmanager->resPrint;
                print '</blockquote>' . "\n";
            }
            print '<blockquote class="text-muted text-left">' . dol_nl2br($requestmessage_static->note) . '</blockquote>' . "\n";
            if (count($files_toprint)) {
                print '<blockquote class="text-muted text-left">' . "\n";
                print $langs->trans("Documents") . ' : ' . implode(' , ', $files_toprint) . "\n";
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

/**
 *  Get list of confidentiality tag of the user
 *
 * @param   User        $user       User handler
 * @return  array                   List of confidentiality tag of the user
 */
function get_user_confidentiality_tags($user)
{
    global $db;

    $tags_list = array();

    if (!isset($user->id) || !($user->id > 0))
        return $tags_list;

    // Get user tags
    if (empty($user->array_options))
        $user->fetch_optionals();
    if (!empty($user->array_options['options_user_tag'])) {
        $u_tags = explode(',', $user->array_options['options_user_tag']);
        foreach ($u_tags as $tag_id) {
            if ($tag_id > 0)
                $tags_list[$tag_id] = $tag_id;
        }
    }

    // Get user groups tags of the user
    require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';
    $usergroup = new UserGroup($db);
    $usergroups = $usergroup->listGroupsForUser($user->id);
    foreach ($usergroups as $group) {
        $ug_tags = explode(',', $group->array_options['options_group_tag']);
        foreach ($ug_tags as $tag_id) {
            if ($tag_id > 0 && !isset($tags_list[$tag_id]))
                $tags_list[$tag_id] = $tag_id;
        }
    }

    return $tags_list;
}
