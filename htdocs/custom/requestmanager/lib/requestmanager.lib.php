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

    /* TODO : Tab "Fichiers joints" temporary removed
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
    */

    /* TODO : Tab "Events/Agenda" temporary removed
    $head[$h][0] = dol_buildpath('/requestmanager/agenda.php', 1) . '?id=' . $object->id;
    $head[$h][1] .= $langs->trans("Events");
    if (!empty($conf->agenda->enabled) && (!empty($user->rights->agenda->myactions->read) || !empty($user->rights->agenda->allactions->read))) {
        $head[$h][1] .= '/';
        $head[$h][1] .= $langs->trans("Agenda");
    }
    $head[$h][2] = 'agenda';
    $h++;
    */

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

    $mode = GETPOST('mode', 'int');

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

    $search_ref = GETPOST('search_ref', 'alpha');
    $search_origin = GETPOST('search_origin', 'array');
    $search_type = GETPOST('search_type', 'array');
    $search_title = GETPOST('search_title', 'alpha');
    $search_description = GETPOST('search_description', 'alpha');
    $search_event_on_full_day = GETPOST('search_event_on_full_day', 'int');
    $search_date_start = GETPOST('search_date_start', 'alpha');
    $search_date_end = GETPOST('search_date_end', 'alpha');
    $search_location = GETPOST('search_location', 'alpha');
    $search_owned_by = GETPOST('search_owned_by', 'alpha');
    $search_assigned_to = GETPOST('search_assigned_to', 'alpha');
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
    if ($mode == 1) {
        $contextpage = 'requestmanagereventbloclist';
    } else {
        $contextpage = 'requestmanagereventlist';
    }
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
        'ac.elementtype' => array('label' => $langs->trans("Origin"), 'checked' => 1),
        'ac.id' => array('label' => $langs->trans("Ref"), 'checked' => 1),
        'ac.fk_action' => array('label' => $langs->trans("Type"), 'checked' => 1),
        'ac.label' => array('label' => $langs->trans("Title"), 'checked' => 1),
        'ac.note' => array('label' => $langs->trans("Description"), 'checked' => 1),
        'ac.fulldayevent' => array('label' => $langs->trans("EventOnFullDay"), 'checked' => 0),
        'ac.datep' => array('label' => $langs->trans("DateStart"), 'checked' => 1),
        'ac.datep2' => array('label' => $langs->trans("DateEnd"), 'checked' => 1),
        'ac.location' => array('label' => $langs->trans("Location"), 'checked' => 0, 'enabled' => empty($conf->global->AGENDA_DISABLE_LOCATION)),
        'ac.fk_element' => array('label' => $langs->trans("LinkedObject"), 'checked' => 0),
        'ac.fk_user_action' => array('label' => $langs->trans("ActionsOwnedByShort"), 'checked' => 0),
//        'ac.userassigned' => array('label' => $langs->trans("ActionAssignedTo"), 'checked' => 0),
        'ac.fk_user_done' => array('label' => $langs->trans("ActionDoneBy"), 'checked' => 0, 'enabled' => $conf->global->AGENDA_ENABLE_DONEBY),
        'ac.fk_project' => array('label' => $langs->trans("Project"), 'checked' => 0),
        'ac.priority' => array('label' => $langs->trans("Priority"), 'checked' => 0),
        'ac.fk_user_author' => array('label' => $langs->trans("Author"), 'checked' => 0, 'position' => 10),
        'ac.fk_user_mod' => array('label' => $langs->trans("ModifiedBy"), 'checked' => 0, 'position' => 10),
        'ac.datec' => array('label' => $langs->trans("DateCreation"), 'checked' => 1, 'position' => 500),
        'ac.tms' => array('label' => $langs->trans("DateModificationShort"), 'checked' => 0, 'position' => 500),
        'ac.percent' => array('label' => $langs->trans("Status"), 'checked' => 1, 'position' => 1000),
    );
    // Extra fields
    if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) {
        foreach ($extrafields->attribute_label as $key => $val) {
            $arrayfields["ef." . $key] = array('label' => $extrafields->attribute_label[$key], 'checked' => $extrafields->attribute_list[$key], 'position' => $extrafields->attribute_pos[$key], 'enabled' => $extrafields->attribute_perms[$key]);
        }
    }
    // Extra fields message
    if (is_array($extrafields_message->attribute_label) && count($extrafields_message->attribute_label)) {
        foreach ($extrafields_message->attribute_label as $key => $val) {
            $arrayfields["efm." . $key] = array('label' => $extrafields_message->attribute_label[$key], 'checked' => $extrafields_message->attribute_list[$key], 'position' => $extrafields_message->attribute_pos[$key], 'enabled' => $extrafields_message->attribute_perms[$key]);
        }
    }

    require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
    $actioncomm = new ActionComm($db);    // To be passed as parameter of executeHooks that need

    /*
     * Actions
     */

    $parameters = array('requestmanager' => &$requestmanager);
    $reshook = $hookmanager->executeHooks('doActions', $parameters, $formactions, $action);    // Note that $action and $object may have been modified by some hooks
    if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

    include DOL_DOCUMENT_ROOT . '/core/actions_changeselectedfields.inc.php';

    // Do we click on purge search criteria ?
    if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
    {
        $search_ref = '';
        $search_origin = array();
        $search_type = array();
        $search_title = '';
        $search_description = '';
        $search_event_on_full_day = -1;
        $search_date_start = '';
        $search_date_end = '';
        $search_location = '';
        $search_owned_by = '';
        $search_assigned_to = '';
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

    $sql = "SELECT ac.id,";
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
    $sql .= " cac.id as type_id, cac.code as type_code, cac.libelle as type_label, cac.color as type_color, cac.picto as type_picto,";
    $sql .= " uo.firstname as userownerfirstname, uo.lastname as userownerlastname, uo.email as userowneremail,";
    $sql .= " ud.firstname as userdonefirstname, ud.lastname as userdonelastname, ud.email as userdoneemail,";
    $sql .= " ua.firstname as userauthorfirstname, ua.lastname as userauthorlastname, ua.email as userauthoremail,";
    $sql .= " um.firstname as usermodfirstname, um.lastname as usermodlastname, um.email as usermodemail,";
    $sql .= " p.ref as projectref, p.title as projecttitle, p.public as projectpublic, p.fk_statut as projectstatus, p.datee as projectdatee";
    // Add fields from extrafields
    foreach ($extrafields->attribute_label as $key => $val) $sql .= ($extrafields->attribute_type[$key] != 'separate' ? ",ef." . $key . ' as options_' . $key : '');
    foreach ($extrafields_message->attribute_label as $key => $val) $sql .= ($extrafields_message->attribute_type[$key] != 'separate' ? ",efm." . $key . ' as m_options_' . $key : '');
    // Add fields from hooks
    $parameters = array();
    $reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters);    // Note that $action and $object may have been modified by hook
    $sql .= $hookmanager->resPrint;
    $sql .= " FROM " . MAIN_DB_PREFIX . "actioncomm as ac ";
    if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "actioncomm_extrafields as ef on (ac.rowid = ef.fk_object)";
    if (is_array($extrafields_message->attribute_label) && count($extrafields_message->attribute_label)) $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "requestmanager_message_extrafields as efm on (ac.rowid = efm.fk_object)";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_actioncomm as cac ON cac.id = ac.fk_action";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as uo on uo.rowid = ac.fk_user_action";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as ud on ud.rowid = ac.fk_user_done";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as ua on ua.rowid = ac.fk_user_author";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as um on um.rowid = ac.fk_user_mod";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "projet as p on p.rowid = ac.fk_project";
    // Add 'from' from hooks
    $parameters = array();
    $reshook = $hookmanager->executeHooks('printFieldListFrom', $parameters);    // Note that $action and $object may have been modified by hook
    $sql .= ' WHERE ac.fk_soc = ' . $requestmanager->socid;
    $sql .= ' AND ac.entity IN (' . getEntity('agenda') . ')';
    if ($search_ref) $sql .= natural_search('ac.id', $search_ref);
    if (!empty($search_origin)) $sql .= " AND ac.elementtype IN ('" . implode("','", $search_origin) . "')";
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
    // Add where from hooks
    $parameters = array();
    $reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters);    // Note that $action and $object may have been modified by hook
    $sql .= $hookmanager->resPrint;

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
        if ($search_ref) $param .= '&search_ref=' . urlencode($search_ref);
        if (!empty($search_origin)) $param .= '&search_origin=' . urlencode($search_origin);
        if ($search_type) $param .= '&search_type=' . urlencode($search_type);
        if ($search_title) $param .= '&search_title=' . urlencode($search_title);
        if ($search_description) $param .= '&search_description=' . urlencode($search_description);
        if ($search_event_on_full_day >= 0) $param .= '&search_event_on_full_day=' . urlencode($search_event_on_full_day);
        if ($search_date_start) $param .= '&search_date_start=' . urlencode($search_date_start);
        if ($search_date_end) $param .= '&search_date_end=' . urlencode($search_date_end);
        if ($search_location) $param .= '&search_location=' . urlencode($search_location);
        if ($search_owned_by) $param .= '&search_owned_by=' . urlencode($search_owned_by);
        if ($search_assigned_to) $param .= '&search_assigned_to=' . urlencode($search_assigned_to);
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
        /*$morehtml = '<a class="' . (empty($mode) ? 'butActionRefused' : 'butAction') . '" href="' . $_SERVER['PHP_SELF'] . '?mode=0&sortfield=' . urlencode($sortfield) . '&sortorder=' . urlencode($sortorder) . '&page=' . urlencode($page) . $param . '">';
        $morehtml .= $langs->trans("RequestManagerListMode");
        $morehtml .= '</a>';
        $morehtml .= '<a class="' . (!empty($mode) ? 'butActionRefused' : 'butAction') . '" href="' . $_SERVER['PHP_SELF'] . '?mode=1&sortfield=' . urlencode($sortfield) . '&sortorder=' . urlencode($sortorder) . '&page=' . urlencode($page) . $param . '">';
        $morehtml .= $langs->trans("RequestManagerBlocMode");
        $morehtml .= '</a>';*/

        if ($mode > 0) $param .= '&mode=' . urlencode($mode);

        // Lignes des champs de filtre
        print '<form method="POST" id="searchFormList" action="' . $_SERVER["PHP_SELF"] . '?id=' . $requestmanager->id . '">';
        if ($optioncss != '') print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
        print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
        print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
        print '<input type="hidden" name="action" value="list">';
        print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
        print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
        print '<input type="hidden" name="page" value="' . $page . '">';

        print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $morehtml, $num, $nbtotalofrecords, '', 0, '', '', $limit);

        $i = 0;

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
        // Origin
        if (!empty($arrayfields['ac.elementtype']['checked'])) {
            print '<td class="liste_titre">';
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
        if (!empty($arrayfields['ac.userassigned']['checked'])) {
            print '<td class="liste_titre" align="center">';
            print '<input class="flat" size="6" type="text" name="search_assigned_to" value="' . dol_escape_htmltag($search_assigned_to) . '">';
            print '</td>';
        }
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
        if (!empty($arrayfields['ac.elementtype']['checked'])) print_liste_field_titre($arrayfields['ac.elementtype']['label'], $_SERVER["PHP_SELF"], 'ac.elementtype', '', $param, '', $sortfield, $sortorder);
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

        // Mode bloc
        if (!empty($mode)) {
            print '</table>' . "\n";
        }

        $actioncomm_static = new ActionComm($db);

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
            // User Modif
            $usermodif_static->id = $obj->fk_user_mod;
            $usermodif_static->firstname = $obj->usermodfirstname;
            $usermodif_static->lastname = $obj->usermodlastname;
            $usermodif_static->email = $obj->usermodemail;

            if (!empty($mode)) {
            } else {
                print '<tr'.(empty($actioncomm_static->type_color) ? ' class="oddeven"' : '').'>';

                $tdcolor = empty($actioncomm_static->type_color) ? '' : ' style="background-color: #'.ltrim($actioncomm_static->type_color, '#\s').';"';

                // Source
                if (!empty($arrayfields['ac.elementtype']['checked'])) {
                    print '<td class="nowrap"'.$tdcolor.'>';
                    if (isset($elements_array[$actioncomm_static->elementtype])) print $elements_array[$actioncomm_static->elementtype];
                    else print $elements_array['societe'];
                    print '</td>';
                }
                // Ref
                if (!empty($arrayfields['ac.id']['checked'])) {
                    print '<td class="nowrap"'.$tdcolor.'>';
                    print $actioncomm_static->getNomUrl(1, -1);
                    print '</td>';
                }
                // Type
                if (!empty($arrayfields['ac.fk_action']['checked'])) {
                    print '<td class="nowrap"'.$tdcolor.'>';
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
                    print dol_trunc($labeltype, 28);
                    print '</td>';
                }
                // Title
                if (!empty($arrayfields['ac.label']['checked'])) {
                    print '<td class="nowrap"'.$tdcolor.'>';
                    print dol_trunc($actioncomm_static->label, 50);
                    print '</td>';
                }
                // Description
                if (!empty($arrayfields['ac.note']['checked'])) {
                    print '<td class="nowrap tdoverflowmax300"'.$tdcolor.'>';
                    print dol_trunc($actioncomm_static->note, 255);
                    print '</td>';
                }
                // Event On Full Day
                if (!empty($arrayfields['ac.fulldayevent']['checked'])) {
                    print '<td class="nowrap"'.$tdcolor.'>';
                    print yn($actioncomm_static->fulldayevent);
                    print '</td>';
                }
                // Date Start
                if (!empty($arrayfields['ac.datep']['checked'])) {
                    print '<td class="nowrap" align="center"'.$tdcolor.'>';
                    if ($actioncomm_static->datep > 0) print dol_print_date($actioncomm_static->datep, "dayhour");
                    print '</td>';
                }
                // Date End
                if (!empty($arrayfields['ac.datep2']['checked'])) {
                    print '<td class="nowrap" align="center"'.$tdcolor.'>';
                    if ($actioncomm_static->datef > 0) print dol_print_date($actioncomm_static->datef, "dayhour");
                    print '</td>';
                }
                // Location
                if (!empty($arrayfields['ac.location']['checked'])) {
                    print '<td class="nowrap"'.$tdcolor.'>';
                    print dol_trunc($actioncomm_static->location, 50);
                    print '</td>';
                }
                // Linked Object
                if (!empty($arrayfields['ac.fk_element']['checked'])) {
                    print '<td class="nowrap"'.$tdcolor.'>';
                    if ($actioncomm_static->fk_element > 0 && !empty($actioncomm_static->elementtype)) {
                        include_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
                        print dolGetElementUrl($actioncomm_static->fk_element, $actioncomm_static->elementtype, 1);
                    }
                    print '</td>';
                }
                // Owned By
                if (!empty($arrayfields['ac.fk_user_action']['checked'])) {
                    print '<td class="nowrap" align="center"'.$tdcolor.'>';
                    if ($userowner_static->id > 0) print $userowner_static->getNomUrl(1);
                    print '</td>';
                }
                // Assigned To
                if (!empty($arrayfields['ac.userassigned']['checked'])) {
                    print '<td class="nowrap" align="center"'.$tdcolor.'>';
                    print '</td>';
                }
                // Done By
                if (!empty($arrayfields['ac.fk_user_done']['checked'])) {
                    print '<td class="nowrap" align="center"'.$tdcolor.'>';
                    if ($userdone_static->id > 0) print $userdone_static->getNomUrl(1);
                    print '</td>';
                }
                // Project
                if (!empty($arrayfields['ac.fk_project']['checked'])) {
                    print '<td class="nowrap"'.$tdcolor.'>';
                    if ($project_static->id > 0) print $project_static->getNomUrl(1);
                    print '</td>';
                }
                // Priority
                if (!empty($arrayfields['ac.priority']['checked'])) {
                    print '<td class="nowrap"'.$tdcolor.'>';
                    print dol_trunc($actioncomm_static->priority, 50);
                    print '</td>';
                }
                // Author
                if (!empty($arrayfields['ac.fk_user_author']['checked'])) {
                    print '<td class="nowrap" align="center"'.$tdcolor.'>';
                    if ($userauthor_static->id > 0) print $userauthor_static->getNomUrl(1);
                    print '</td>';
                }
                // Modified By
                if (!empty($arrayfields['ac.fk_user_mod']['checked'])) {
                    print '<td class="nowrap" align="center"'.$tdcolor.'>';
                    if ($usermodif_static->id > 0) print $usermodif_static->getNomUrl(1);
                    print '</td>';
                }

                // Extra fields
                if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) {
                    foreach ($extrafields->attribute_label as $key => $val) {
                        if (!empty($arrayfields["ef." . $key]['checked'])) {
                            print '<td';
                            $align = $extrafields->getAlignFlag($key);
                            if ($align) print ' align="' . $align . '"';
                            print $tdcolor.'>';
                            $tmpkey = 'options_' . $key;
                            print $extrafields->showOutputField($key, $obj->$tmpkey, '', 1);
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
                            print $tdcolor.'>';
                            $tmpkey = 'options_m_' . $key;
                            print $extrafields_message->showOutputField($key, $obj->$tmpkey, '', 1);
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
                    print '<td align="center" class="nowrap"'.$tdcolor.'>';
                    print dol_print_date($actioncomm_static->datec, 'dayhour');
                    print '</td>';
                }
                // Date modif
                if (!empty($arrayfields['ac.tms']['checked'])) {
                    print '<td align="center" class="nowrap"'.$tdcolor.'>';
                    print dol_print_date($actioncomm_static->datem, 'dayhour');
                    print '</td>';
                }
                // Status
                if (!empty($arrayfields['ac.percent']['checked'])) {
                    print '<td align="right" class="nowrap"'.$tdcolor.'>' . $actioncomm_static->LibStatut($actioncomm_static->percentage, 3, 0, $actioncomm_static->datep) . '</td>';
                }
                // Action column
                print '<td class="nowrap" align="center"'.$tdcolor.'>';
                print '</td>';

                print "</tr>\n";
            }
            $i++;
        }

        $db->free($resql);

        $parameters = array('arrayfields' => $arrayfields, 'sql' => $sql);
        $reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters);    // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;

        // Mode list
        if (empty($mode)) {
            print '</table>' . "\n";
        }
        print '</div>' . "\n";

        print '</form>' . "\n";

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
            'label' => '',
            'langs' => array(),
            'picto' => '',
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
            'label' => '',
            'langs' => array(),
            'picto' => '',
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
            'label' => '',
            'langs' => array(),
            'picto' => '',
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
            'label' => '',
            'langs' => array(),
            'picto' => '',
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
    $hookmanager->initHooks('requestmanagerdao');
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
