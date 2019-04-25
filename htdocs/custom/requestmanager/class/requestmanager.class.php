<?php
/* Copyright (C) 2007-2012  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2014       Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2015       Florian Henry       <florian.henry@open-concept.pro>
 * Copyright (C) 2015       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2018       Open-DSI            <support@open-dsi.fr>
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
 * \file    requestmanager/class/requestmanager.class.php
 * \ingroup requestmanager
 * \brief
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobjectline.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/margin/lib/margins.lib.php';
require_once DOL_DOCUMENT_ROOT . '/multicurrency/class/multicurrency.class.php';

/**
 * Class RequestManager
 */
class RequestManager extends CommonObject
{
	public $element = 'requestmanager';
	public $table_element = 'requestmanager';
    public $table_element_line = 'requestmanagerdet';
    public $fk_element = 'fk_requestmanager';
    public $ismultientitymanaged = 1;	// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
    public $picto = 'requestmanager@requestmanager';

    /**
     * Array of whitelist of properties keys for this object used for the API
     * @var  array
     *      array('properties_name'=> '' or array('properties_name'=> '' or array(...), ...)
     *      if property is a object and this properties_name value is not a array then get whitelist of his object element
     *      if property is a object and this properties_name value is a array then get whitelist set in the array
     *      if property is a array and this properties_name value is not a array then get all values
     *      if property is a array and this properties_name value is a array then get whitelist set in the array
     */
    static public $API_WHITELIST_OF_PROPERTIES = array(
        "id" => '', "fk_parent" => '',/* "parent" => '',*/ "children_request_ids" => '', "children_request_list" => '',
        "ref" => '', "ref_ext" => '', "socid_origin" => '', "socid" => '', "socid_benefactor" => '', "socid_watcher" => '',
        "thirdparty_origin" => '', "thirdparty" => '', "thirdparty_benefactor" => '', "thirdparty_watcher" => '',
        "availability_for_thirdparty_principal" => '', "availability_for_thirdparty_benefactor" => '', "availability_for_thirdparty_watcher" => '',
        "label" => '', "description" => '', "fk_type" => '', "fk_category" => '', "fk_source" => '', "fk_urgency" => '',
        "fk_impact" => '', "fk_priority" => '', "duration" => '', "fk_reason_resolution" => '', "reason_resolution_details" => '',
        "requester_ids" => '', "requester_list" => '', "notify_requester_by_email" => '', "watcher_ids" => '', "watcher_list" => '',
        "notify_watcher_by_email" => '', "assigned_user_ids" => '', "assigned_user_list" => '', "assigned_usergroup_ids" => '',
        "assigned_usergroup_list" => '', "notify_assigned_by_email" => '', "tag_ids" => '', "tag_list" => '', "date_operation" => '',
        "date_deadline" => '', "date_resolved" => '', "date_cloture" => '', "user_resolved_id" => '', "user_cloture_id" => '',
        "statut" => '', "statut_type" => '', "entity" => '', "date_creation" => '', "date_modification" => '', "user_creation_id" => '',
        "user_modification_id" => '', "array_options" => '', "linkedObjectsIds" => '',
    );

    /**
     * Array of whitelist of properties keys for this object when is a linked object used for the API
     * @var  array
     *      if empty array then equal at $api_whitelist_of_properties
     *      array('properties_name'=> '' or array('properties_name'=> '' or array(...), ...)
     *      if property is a object and this properties_name value is not a array then get whitelist of his object element
     *      if property is a object and this properties_name value is a array then get whitelist set in the array
     *      if property is a array and this properties_name value is not a array then get all values
     *      if property is a array and this properties_name value is a array then get whitelist set in the array
     */
    static public $API_WHITELIST_OF_PROPERTIES_LINKED_OBJECT = array(
    );

    /**
     * Cache of type list
     * @var DictionaryLine[]
     */
    static public $type_list;
    /**
     * Cache of category list
     * @var DictionaryLine[]
     */
    static public $category_list;
    /**
     * Cache of source list
     * @var DictionaryLine[]
     */
    static public $source_list;
    /**
     * Cache of urgency list
     * @var DictionaryLine[]
     */
    static public $urgency_list;
    /**
     * Cache of impact list
     * @var DictionaryLine[]
     */
    static public $impact_list;
    /**
     * Cache of priority list
     * @var DictionaryLine[]
     */
    static public $priority_list;
    /**
     * Cache of reason for resolution list
     * @var DictionaryLine[]
     */
    static public $reason_resolution_list;
    /**
     * Cache of status list
     * @var DictionaryLine[]
     */
    static public $status_list;

    /**
     * Error message
     * @var string
     */
    public $error;
    /**
     * List of error message
     * @var array
     */
    public $errors;
    /**
     * Old request object before update
     * @var RequestManager
     */
    public $oldcopy;

    /**
     * ID of the request
     * @var int
     */
    public $id;

    /**
     * ID of the request parent
     * @var int
     */
    public $fk_parent;
    /**
     * Object of the request parent
     * @var RequestManager
     */
    public $parent;
    /**
     * List children request ID
     * @var string[]
     */
    public $children_request_ids;
    /**
     * List children request object
     * @var RequestManager[]
     */
    public $children_request_list;

    /**
     * Ref of the request
     * @var string
     */
    public $ref;
    /**
     * Ref external of the request
     * @var string
     */
    public $ref_ext;

    /**
     * If created out of time (value only set at the creation)
     * @var boolean
     */
    public $created_out_of_time;

    /**
     * ID of the thirdparty origin
     * @var int
     */
    public $socid_origin;
    /**
     * ID of the thirdparty bill
     * @var int
     */
    public $socid;
    /**
     * ID of the thirdparty benefactor
     * @var int
     */
    public $socid_benefactor;
    /**
     * ID of the thirdparty watcher
     * @var int
     */
    public $socid_watcher;
    /**
	 * @var Societe A related thirdparty origin
	 * @see fetch_thirdparty_origin()
	 */
	public $thirdparty_origin;
    /**
	 * @var Societe A related thirdparty bill
	 * @see fetch_thirdparty()
	 */
	public $thirdparty;
    /**
	 * @var Societe A related thirdparty benefactor
	 * @see fetch_thirdparty_benefactor()
	 */
	public $thirdparty_benefactor;
    /**
     * @var Societe A related thirdparty benefactor
     * @see fetch_thirdparty_benefactor()
     */
    public $thirdparty_watcher;
    /**
     * Availability of the request for the thirdparty principal
     * @var boolean
     */
    public $availability_for_thirdparty_principal;
    /**
     * Availability of the request for the thirdparty benefactor
     * @var boolean
     */
    public $availability_for_thirdparty_benefactor;
    /**
     * Availability of the request for the thirdparty watcher
     * @var boolean
     */
    public $availability_for_thirdparty_watcher;

    /**
     * Label of the request
     * @var string
     */
    public $label;
    /**
     * Description of the request
     * @var string
     */
    public $description;
    /**
     * Ref client of the request
     * For propagation of the label in ref client when create object from this request and REQUESTMANAGER_TITLE_TO_REF_CUSTOMER_WHEN_CREATE_OTHER_ELEMENT equal 1
     * @var string
     */
    public $ref_client;

    /**
     * Type of the request
     * @var int
     */
    public $fk_type;
    /**
     * Category of the request
     * @var int
     */
    public $fk_category;
    /**
     * Source of the request
     * @var int
     */
    public $fk_source;
    /**
     * Urgency of the request
     * @var int
     */
    public $fk_urgency;
    /**
     * Impact of the request
     * @var int
     */
    public $fk_impact;
    /**
     * Priority of the request
     * @var int
     */
    public $fk_priority;
    /**
     * Duration of the request in second
     * @var int
     */
    public $duration;
    /**
     * Reason for resolution ID of the request
     * @var int
     */
    public $fk_reason_resolution;
    /**
     * Details of the reason for resolution of the request
     * @var string
     */
    public $reason_resolution_details;

    /**
     * List requester contact ID
     * @var string[]
     */
    public $requester_ids;
    /**
     * List requester contact object
     * @var Contact[]
     */
    public $requester_list;
    /**
     * Notify requester by email the request (0 / 1)
     * @var int
     */
    public $notify_requester_by_email;
    /**
     * List watcher contact ID
     * @var string[]
     */
    public $watcher_ids;
    /**
     * List watcher contact object
     * @var Contact[]
     */
    public $watcher_list;
    /**
     * Notify watcher by email the request (0 / 1)
     * @var int
     */
    public $notify_watcher_by_email;
    /**
     * List of user ID who is assigned to this request
     * @var string[]
     */
    public $assigned_user_ids;
    /**
     * List of user who is assigned to this request
     * @var User[]
     */
    public $assigned_user_list;
    /**
     * List of usergroup ID who is assigned to this request
     * @var string[]
     */
    public $assigned_usergroup_ids;
    /**
     * List of usergroup who is assigned to this request
     * @var UserGroup[]
     */
    public $assigned_usergroup_list;
    /**
     * Notify assigned by email the request (0 / 1)
     * @var int
     */
    public $notify_assigned_by_email;

    /**
     * List of assigned user ID passed when the function set_assigned() is called for infos into triggers
     * @var string[]
     */
    public $new_assigned_user_ids;
    /**
     * List of assigned usergroup ID passed when the function set_assigned() is called for infos into triggers
     * @var string[]
     */
    public $new_assigned_usergroup_ids;
    /**
     * List of assigned user ID added when the function set_assigned() is called for infos into triggers
     * @var string[]
     */
    public $assigned_user_added_ids;
    /**
     * List of assigned usergroup ID added when the function set_assigned() is called for infos into triggers
     * @var string[]
     */
    public $assigned_usergroup_added_ids;
    /**
     * List of assigned user ID deleted when the function set_assigned() is called for infos into triggers
     * @var string[]
     */
    public $assigned_user_deleted_ids;
    /**
     * List of assigned usergroup ID deleted when the function set_assigned() is called for infos into triggers
     * @var string[]
     */
    public $assigned_usergroup_deleted_ids;

    /**
     * List of tags ID to this request
     * @var string[]
     */
    public $tag_ids;
    /**
     * List of tags ID to this request
     * @var string[]
     * @see fetch_tags()
     */
    public $tag_list;

    /**
     * Date operation of the request
     * @var int
     */
    public $date_operation;
    /**
     * Date deadline of the request
     * @var int
     */
    public $date_deadline;
    /**
     * Date resolved of the request
     * @var int
     */
    public $date_resolved;
    /**
     * Date closed of the request
     * @var int
     */
    public $date_cloture;
    /**
     * Id of the user who resolved the request
     * @var int
     */
    public $user_resolved_id;
    /**
     * User who resolved the request
     * @var User
     */
    public $user_resolved;
    /**
     * Id of the user who closed the request
     * @var int
     */
    public $user_cloture_id;
    /**
     * User who closed the request
     * @var User
     */
    public $user_cloture;

    /**
     * Status of the request
     * @var int
     */
    public $statut;
    /**
     * Save status of the request when hack dolibarr (extrafields permission, ...)
     * @var int
     */
    public $save_status;
    /**
     * Type of the status of the request (initial, in progress, resolved, closed)
     * @var int
     */
    public $statut_type;
    /**
     * Entity of the request
     * @var int
     */
    public $entity;
    /**
     * Date created of the request
     * @var int
     */
    public $date_creation;
    /**
     * Date modified of the request
     * @var int
     */
    public $date_modification;
    /**
     * Id of the user who created the request
     * @var int
     */
    public $user_creation_id;
    /**
     * User who created the request
     * @var User
     */
    public $user_creation;
    /**
     * Id of the user who modified the request
     * @var int
     */
    public $user_modification_id;
    /**
     * User who modified the request
     * @var User
     */
    public $user_modification;

    //public $total_ht;
    //public $tva;
    //public $localtax1;
    //public $localtax2;
    //public $total_ttc;

    // Multicurrency
    public $fk_multicurrency;
    public $multicurrency_code;
    public $multicurrency_tx;
    public $multicurrency_total_ht;
    public $multicurrency_total_tva;
    public $multicurrency_total_ttc;

    /**
     * @var RequestManagerLine[]
     */
    public $lines = array();


    const STATUS_TYPE_INITIAL = 0;
    const STATUS_TYPE_IN_PROGRESS = 1;
    const STATUS_TYPE_RESOLVED = 2;
    const STATUS_TYPE_CLOSED = 3;

    /**
     * Contact types
     */
    const CONTACT_TYPE_ID_REQUEST = 0;
    const CONTACT_TYPE_ID_WATCHER = 1;
    private static $_contactTypeCodeList = array(self::CONTACT_TYPE_ID_REQUEST => 'REQUESTER', self::CONTACT_TYPE_ID_WATCHER => 'WATCHER');

    /**
     * ActionComm code types
     */
    const ACTIONCOMM_TYPE_CODE_ASSUSR = 'AC_RM_ASSUSR';
    const ACTIONCOMM_TYPE_CODE_IN = 'AC_RM_IN';
    const ACTIONCOMM_TYPE_CODE_OUT = 'AC_RM_OUT';
    const ACTIONCOMM_TYPE_CODE_STAT = 'AC_RM_STAT';

    /**
     * Templates types
     */
    const TEMPLATE_TYPE_NOTIFY_ASSIGNED_USERS_MODIFIED = 'notify_assigned_users_modified';
    const TEMPLATE_TYPE_NOTIFY_INPUT_MESSAGE_ADDED = 'notify_input_message_added';
    const TEMPLATE_TYPE_NOTIFY_OUTPUT_MESSAGE_ADDED = 'notify_output_message_added';
    const TEMPLATE_TYPE_NOTIFY_STATUS_MODIFIED = 'notify_status_modified';
    const TEMPLATE_TYPE_MESSAGE_TEMPLATE_USER = 'message_template_user';


    /**
	 * Constructor
	 *
	 * @param   DoliDb      $db     Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}

    /**
     * Get all errors
     *
     * @return array    Array of errors
     */
	public function getErrors() {
	    $errors = is_array($this->errors) ? $this->errors : array();
	    $errors = array_merge($errors, (!empty($this->error) ? array($this->error) : array()));

	    return $errors;
    }

	/**
	 *  Create request into database
	 *
	 * @param   User    $user           User that creates
	 * @param   bool    $notrigger      false=launch triggers after, true=disable triggers
	 * @return  int                     <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = false)
    {
        global $conf, $langs, $hookmanager;
        $error = 0;
        $this->errors = array();
        $now = dol_now();
        $langs->load("requestmanager@requestmanager");

        dol_syslog(__METHOD__ . " user_id=" . $user->id, LOG_DEBUG);

        dol_include_once('/advancedictionaries/class/dictionary.class.php');
        $requestManagerStatusDictionary = Dictionary::getDictionary($this->db, 'requestmanager', 'requestmanagerstatus');
        $status = $requestManagerStatusDictionary->getCodeFromFilter('{{rowid}}', array('type' => array(self::STATUS_TYPE_INITIAL), 'request_type'=> array($this->fk_type)));
        if ($status === -2) {
            array_merge($this->errors, $requestManagerStatusDictionary->errors);
            $error++;
        }

        // Clean parameters
        $this->fk_parent = $this->fk_parent > 0 ? $this->fk_parent : 0;
        $this->socid_origin = $this->socid_origin > 0 ? $this->socid_origin : 0;
        $this->socid = $this->socid > 0 ? $this->socid : $this->socid_origin;
        $this->socid_benefactor = $this->socid_benefactor > 0 ? $this->socid_benefactor : $this->socid_origin;
        $this->socid_watcher = $this->socid_watcher > 0 ? $this->socid_watcher : 0;
        $this->availability_for_thirdparty_principal = !isset($this->availability_for_thirdparty_principal) ? 1 : (!empty($this->availability_for_thirdparty_principal) ? 1 : 0);
        $this->availability_for_thirdparty_benefactor = !isset($this->availability_for_thirdparty_benefactor) ? 1 : (!empty($this->availability_for_thirdparty_benefactor) ? 1 : 0);
        $this->availability_for_thirdparty_watcher = !isset($this->availability_for_thirdparty_watcher) ? 1 : (!empty($this->availability_for_thirdparty_watcher) ? 1 : 0);
        $this->label = trim($this->label);
        $this->description = trim($this->description);
        $this->fk_type = $this->fk_type > 0 ? $this->fk_type : 0;
        $this->fk_category = $this->fk_category > 0 ? $this->fk_category : 0;
        $this->fk_source = $this->fk_source > 0 ? $this->fk_source : 0;
        $this->fk_urgency = $this->fk_urgency > 0 ? $this->fk_urgency : 0;
        $this->fk_impact = $this->fk_impact > 0 ? $this->fk_impact : 0;
        $this->fk_priority = $this->fk_priority > 0 ? $this->fk_priority : 0;
        $this->notify_requester_by_email = !empty($this->notify_requester_by_email) ? 1 : 0;
        $this->notify_watcher_by_email = !empty($this->notify_watcher_by_email) ? 1 : 0;
        $this->assigned_user_ids = empty($this->assigned_user_ids) ? array() : (is_string($this->assigned_user_ids) ? explode(',', $this->assigned_user_ids) : $this->assigned_user_ids);
        $this->assigned_usergroup_ids = empty($this->assigned_usergroup_ids) ? array() : (is_string($this->assigned_usergroup_ids) ? explode(',', $this->assigned_usergroup_ids) : $this->assigned_usergroup_ids);
        $this->notify_assigned_by_email = !empty($conf->global->REQUESTMANAGER_NOTIFICATION_ASSIGNED_BY_EMAIL) ? (!isset($this->notify_assigned_by_email) ? 1 : (!empty($this->notify_assigned_by_email) ? 1 : 0)) : 0;
        $this->date_operation = $this->date_operation > 0 ? $this->date_operation : 0;
        $this->date_deadline = $this->date_deadline > 0 ? $this->date_deadline : 0;
        $this->statut = $status > 0 ? $status : 0;
        $this->entity = empty($this->entity) ? $conf->entity : $this->entity;
        $this->date_creation = empty($this->date_creation) ? $now : $this->date_creation;
        $this->user_creation_id = $user->id;
        $this->requester_ids = empty($this->requester_ids) ? array() : (is_string($this->requester_ids) ? explode(',', $this->requester_ids) : $this->requester_ids);
        if (empty($conf->companyrelationships->enabled)) {
            $this->socid = $this->socid_origin;
            $this->socid_benefactor = $this->socid_origin;
        }

        // Check parameters
        if (empty($this->socid_origin)) {
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("RequestManagerThirdPartyOrigin"));
            $error++;
        }
        if (empty($this->fk_type)) {
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("RequestManagerType"));
            $error++;
        }
        if (!empty($this->fk_category)) {
            $requestManagerRequestTypeDictionaryLine = Dictionary::getDictionaryLine($this->db, 'requestmanager', 'requestmanagerrequesttype');
            $res = $requestManagerRequestTypeDictionaryLine->fetch($this->fk_type);
            if ($res == 0) {
                $this->errors[] = $langs->trans('RequestManagerErrorRequestTypeNotFound');
                $error++;
            } elseif ($res < 0) {
                array_merge($this->errors, $requestManagerRequestTypeDictionaryLine->errors);
                $error++;
            } else {
                $cat = explode(',', $requestManagerRequestTypeDictionaryLine->fields['category']);
                if (!in_array($this->fk_category, $cat)) {
                    $this->errors[] = $langs->trans("RequestManagerErrorFieldCategoryNotInThisRequestType");
                    $error++;
                }
            }
        }
        if (empty($this->label)) {
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("RequestManagerLabel"));
            $error++;
        }
//        if (empty($this->description)) {
//            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("RequestManagerDescription"));
//            $error++;
//        }
        if ($this->date_deadline > 0 && $this->date_deadline < $this->date_creation) {
            $this->errors[] = $langs->trans("RequestManagerErrorDeadlineDateMustBeGreaterThanCreateDate");
            $error++;
        }
        if (empty($this->statut)) {
            $this->errors[] = $langs->trans("RequestManagerErrorInitStatusNotFound");
            $error++;
        }
        if (!is_array($this->requester_ids)) {
            $this->errors[] = $langs->trans("ErrorBadParameters") . ': ' . $langs->trans("RequestManagerRequester");
            $error++;
        }
        if ($error) {
            dol_syslog(__METHOD__ . " Errors check parameters: " . $this->errorsToString(), LOG_ERR);
            return -3;
        }

        // Numbering module definition
        $soc = new Societe($this->db);
        $soc->fetch($this->socid);

        // Generate refs
        $this->ref = $this->getNextNumRef($soc);
        $this->ref_ext = $this->getNextNumRefExt($soc);
        if (empty($this->ref)) {
            $error++;
        }
        if (empty($this->ref_ext)) {
            $error++;
        }
        if ($error) {
            dol_syslog(__METHOD__ . " Errors generate refs: " . $this->errorsToString(), LOG_ERR);
            return -3;
        }

        // Insert request
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . $this->table_element . " (";
        $sql .= " fk_parent";
        $sql .= ", ref";
        $sql .= ", ref_ext";
        $sql .= ", fk_soc_origin";
        $sql .= ", fk_soc";
        $sql .= ", fk_soc_benefactor";
        $sql .= ", fk_soc_watcher";
        $sql .= ", availability_for_thirdparty_principal";
        $sql .= ", availability_for_thirdparty_benefactor";
        $sql .= ", availability_for_thirdparty_watcher";
        $sql .= ", label";
        $sql .= ", description";
        $sql .= ", fk_type";
        $sql .= ", fk_category";
        $sql .= ", fk_source";
        $sql .= ", fk_urgency";
        $sql .= ", fk_impact";
        $sql .= ", fk_priority";
        $sql .= ", notify_requester_by_email";
        $sql .= ", notify_watcher_by_email";
        $sql .= ", notify_assigned_by_email";
        $sql .= ", date_operation";
        $sql .= ", date_deadline";
        $sql .= ", fk_status";
        $sql .= ", entity";
        $sql .= ", datec";
        $sql .= ", fk_user_author";
        $sql .= ")";
        $sql .= " VALUES (";
        $sql .= " " . ($this->fk_parent > 0 ? $this->fk_parent : 'NULL');
        $sql .= ", '" . $this->db->escape($this->ref) . "'";
        $sql .= ", '" . $this->db->escape($this->ref_ext) . "'";
        $sql .= ", " . $this->socid_origin;
        $sql .= ", " . $this->socid;
        $sql .= ", " . $this->socid_benefactor;
        $sql .= ", " . ($this->socid_watcher > 0 ? $this->socid_watcher : 'NULL');
        $sql .= ", " . ($this->availability_for_thirdparty_principal > 0 ? $this->availability_for_thirdparty_principal : 'NULL');
        $sql .= ", " . ($this->availability_for_thirdparty_benefactor > 0 ? $this->availability_for_thirdparty_benefactor : 'NULL');
        $sql .= ", " . ($this->availability_for_thirdparty_watcher > 0 ? $this->availability_for_thirdparty_watcher : 'NULL');
        $sql .= ", '" . $this->db->escape($this->label) . "'";
        $sql .= ", " . (!empty($this->description) ? "'" . $this->db->escape($this->description) . "'" : 'NULL');
        $sql .= ", " . $this->fk_type;
        $sql .= ", " . ($this->fk_category > 0 ? $this->fk_category : 'NULL');
        $sql .= ", " . ($this->fk_source > 0 ? $this->fk_source : 'NULL');
        $sql .= ", " . ($this->fk_urgency > 0 ? $this->fk_urgency : 'NULL');
        $sql .= ", " . ($this->fk_impact > 0 ? $this->fk_impact : 'NULL');
        $sql .= ", " . ($this->fk_priority > 0 ? $this->fk_priority : 'NULL');
        $sql .= ", " . ($this->notify_requester_by_email > 0 ? $this->notify_requester_by_email : 'NULL');
        $sql .= ", " . ($this->notify_watcher_by_email > 0 ? $this->notify_watcher_by_email : 'NULL');
        $sql .= ", " . ($this->notify_assigned_by_email > 0 ? $this->notify_assigned_by_email : 'NULL');
        $sql .= ", " . ($this->date_operation > 0 ? "'" . $this->db->idate($this->date_operation) . "'" : 'NULL');
        $sql .= ", " . ($this->date_deadline > 0 ? "'" . $this->db->idate($this->date_deadline) . "'" : 'NULL');
        $sql .= ", " . $this->statut;
        $sql .= ", " . $this->entity;
        $sql .= ", '" . $this->db->idate($this->date_creation) . "'";
        $sql .= ", " . $this->user_creation_id;
        $sql .= ")";

        $this->db->begin();

        $resql = $this->db->query($sql);
        if (!$resql) {
            $error++;
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
        }

        if (!$error) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . $this->table_element);

            // Add object linked
            if (is_array($this->linkedObjectsIds) && !empty($this->linkedObjectsIds)) {
                foreach ($this->linkedObjectsIds as $origin => $tmp_origin_id) {
                    if (is_array($tmp_origin_id)) {       // New behaviour, if linkedObjectsIds can have several links per type, so is something like array('contract'=>array(id1, id2, ...))
                        foreach ($tmp_origin_id as $origin_id) {
                            $ret = $this->add_object_linked($origin, $origin_id);
                            if (!$ret && $this->db->lasterrno() != 'DB_ERROR_RECORD_ALREADY_EXISTS') {
                                $this->errors[] = $this->db->lasterror();
                                $error++;
                            } else {
                                // Call trigger
                                $this->context = array('addlink' => $origin, 'addlinkid' => $origin_id);
                                $result = $this->call_trigger('REQUESTMANAGER_ADD_LINK', $user);
                                if ($result < 0) $error++;
                                // End call trigger
                            }
                        }
                    } else {                               // Old behaviour, if linkedObjectsIds has only one link per type, so is something like array('contract'=>id1))
                        $origin_id = $tmp_origin_id;
                        $ret = $this->add_object_linked($origin, $origin_id);
                        if (!$ret && $this->db->lasterrno() != 'DB_ERROR_RECORD_ALREADY_EXISTS') {
                            $this->errors[] = $this->db->lasterror();
                            $error++;
                        } else {
                            // Call trigger
                            $this->context = array('addlink' => $origin, 'addlinkid' => $origin_id);
                            $result = $this->call_trigger('REQUESTMANAGER_ADD_LINK', $user);
                            if ($result < 0) $error++;
                            // End call trigger
                        }
                    }
                }
            }

            // Add linked contracts of the principal company
            if (!$error) {
                if (!empty($conf->global->REQUESTMANAGER_AUTO_ADD_CONTRACT_OF_PRINCIPAL_COMPANY)) {
                    $ret = $this->addContract($this->socid);
                    if ($ret < 0) {
                        $this->errors[] = $this->db->lasterror();
                        $error++;
                    }
                }
            }

            // Add linked contracts of the principal company
            if (!$error) {
                if (!empty($conf->global->REQUESTMANAGER_AUTO_ADD_CONTRACT_OF_BENEFACTOR_COMPANY)) {
                    $ret = $this->addContract($this->socid_benefactor);
                    if ($ret < 0) {
                        $this->errors[] = $this->db->lasterror();
                        $error++;
                    }
                }
            }

            // Add linked equipement
            /*if (!$error) {
                $ret = $this->addEquipement();
                if ($ret < 0) {
                    $error++;
                }
            }*/

            // Set requester contacts
            if (!$error && !empty($this->requester_ids)) {
                foreach ($this->requester_ids as $requester_id) {
                    $this->add_contact($requester_id, 'REQUESTER', 'external');
                }
            }

            // Set status
            if (!$error) {
                $ret = $this->set_status($this->statut, -1, $user, 0, '', 1, 0, 1);
                if ($ret < 0) {
                    $error++;
                }
            }

            if (!$error) {
                // Actions on extra fields (by external module or standard code)
                // TODO le hook fait double emploi avec le trigger !!
                $hookmanager->initHooks(array('requestmanagerdao'));
                $parameters = array();
                $reshook = $hookmanager->executeHooks('insertExtraFields', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks
                if (empty($reshook)) {
                    if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
                    {
                        $result = $this->insertExtraFields();
                        if ($result < 0) {
                            $error++;
                            dol_syslog(__METHOD__ . " Errors insert extra fields: " . $this->errorsToString(), LOG_ERR);
                        }
                    }
                } else if ($reshook < 0) {
                    $error++;
                    dol_syslog(__METHOD__ . " Errors hook insertExtraFields: " . $hookmanager->error . (is_array($hookmanager->errors) ? (($hookmanager->error != '' ? ', ' : '') . join(', ', $hookmanager->errors)) : ''), LOG_ERR);
                }
            }

            if (!$error && !$notrigger) {
                // Call trigger
                $result = $this->call_trigger('REQUESTMANAGER_CREATE', $user);
                if ($result < 0) {
                    $error++;
                    dol_syslog(__METHOD__ . " Errors call trigger: " . $this->errorsToString(), LOG_ERR);
                }
                // End call triggers
            }
        }

        // Commit or rollback
        if ($error) {
            $this->db->rollback();
            return -1 * $error;
        } else {
            $this->db->commit();
            dol_syslog(__METHOD__ . " success", LOG_DEBUG);
            return $this->id;
        }
    }

    /**
     * Get the contact code name in HTML pages
     *
     * @param   string  $contactTypeCode    Contact type code
     * @return  string  Contact type code for HTML pages
     */
    public static function getContactTypeCodeHtmlNameById($idContactType)
    {
        return strtolower(self::getContactTypeCodeById($idContactType));
    }


    /**
     * Get contact type code from constant Id contact type
     *
     * @param   int     $idContactType      Constant id contact type (ex : CONTACT_TYPE_ID_REQUEST)
     * @return  string  Contact type code
     */
    public static function getContactTypeCodeById($idContactType)
    {
        if (isset(self::$_contactTypeCodeList[$idContactType])) {
            return self::$_contactTypeCodeList[$idContactType];
        } else {
            return '';
        }
    }


    /**
     * Get contact type code list
     *
     * @return  array   List of contact type codes
     */
    public function getContactTypeCodeList()
    {
        return self::$_contactTypeCodeList;
    }


    /**
     *  Returns the reference to the following non used request used depending on the active numbering module
     *  defined into REQUESTMANAGER_REF_ADDON
     *
     * @param   Societe     $soc        Object thirdparty
     * @return  string                  Reference for request
     */
    function getNextNumRef($soc)
    {
        global $conf, $langs;
        $langs->load("requestmanager@requestmanager");

        if (!empty($conf->global->REQUESTMANAGER_REF_ADDON)) {
            $mybool = false;

            $file = $conf->global->REQUESTMANAGER_REF_ADDON . ".php";
            $classname = $conf->global->REQUESTMANAGER_REF_ADDON;

            // Include file with class
            $dirmodels = array_merge(array('/requestmanager/'), (array)$conf->modules_parts['models']);
            foreach ($dirmodels as $reldir) {

                $dir = dol_buildpath($reldir . "core/modules/requestmanager/");

                // Load file with numbering class (if found)
                $mybool |= @include_once $dir . $file;
            }

            if (!$mybool) {
                dol_print_error('', "Failed to include file " . $file);
                return '';
            }

            $obj = new $classname();
            $numref = $obj->getNextValue($soc, $this);

            if ($numref != "") {
                return $numref;
            } else {
                $this->error = $obj->error;
                return "";
            }
        } else {
            $langs->load("errors");
            print $langs->trans("Error") . " " . $langs->trans("ErrorModuleSetupNotComplete");
            return "";
        }
    }

    /**
     *  Returns the external reference to the following non used request used depending on the active numbering module
     *  defined into REQUESTMANAGER_REFEXT_ADDON
     *
     * @param   Societe     $soc        Object thirdparty
     * @return  string                  External reference for request
     */
    function getNextNumRefExt($soc)
    {
        global $conf, $langs;
        $langs->load("requestmanager@requestmanager");

        if (!empty($conf->global->REQUESTMANAGER_REFEXT_ADDON)) {
            $mybool = false;

            $file = $conf->global->REQUESTMANAGER_REFEXT_ADDON . ".php";
            $classname = $conf->global->REQUESTMANAGER_REFEXT_ADDON;

            // Include file with class
            $dirmodels = array_merge(array('/requestmanager/'), (array)$conf->modules_parts['models']);
            foreach ($dirmodels as $reldir) {

                $dir = dol_buildpath($reldir . "core/modules/requestmanager/");

                // Load file with numbering class (if found)
                $mybool |= @include_once $dir . $file;
            }

            if (!$mybool) {
                dol_print_error('', "Failed to include file " . $file);
                return '';
            }

            $obj = new $classname();
            $numref = $obj->getNextValue($soc, $this);

            if ($numref != "") {
                return $numref;
            } else {
                $this->error = $obj->error;
                return "";
            }
        } else {
            $langs->load("errors");
            print $langs->trans("Error") . " " . $langs->trans("ErrorModuleSetupNotComplete");
            return "";
        }
    }


	/**
	 *  Load request in memory from the database
	 *
     * @param   int     $id         Id object
     * @param   string	$ref		Reference of request
     * @param   string	$refext		External reference of request
     * @param   int     $entity     Force entity (list id separate by coma)
	 * @return  int                 <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref='', $refext='', $entity=0)
    {
        global $conf, $langs;
        $this->errors = array();
        $langs->load("requestmanager@requestmanager");

        dol_syslog(__METHOD__ . " id=" . $id . " ref=" . $ref . " refext=" . $refext, LOG_DEBUG);

        $sql = 'SELECT';
        $sql .= ' t.rowid,';
        $sql .= ' t.fk_parent,';
        $sql .= ' t.ref,';
        $sql .= ' t.ref_ext,';
        $sql .= ' t.fk_soc_origin,';
        $sql .= ' t.fk_soc,';
        $sql .= ' t.fk_soc_benefactor,';
        $sql .= ' t.fk_soc_watcher,';
        $sql .= ' t.availability_for_thirdparty_principal,';
        $sql .= ' t.availability_for_thirdparty_benefactor,';
        $sql .= ' t.availability_for_thirdparty_watcher,';
        $sql .= ' t.label,';
        $sql .= ' t.description,';
        $sql .= ' t.fk_type,';
        $sql .= ' t.fk_category,';
        $sql .= ' t.fk_source,';
        $sql .= ' t.fk_urgency,';
        $sql .= ' t.fk_impact,';
        $sql .= ' t.fk_priority,';
        $sql .= ' t.notify_requester_by_email,';
        $sql .= ' t.notify_watcher_by_email,';
        $sql .= ' t.notify_assigned_by_email,';
        $sql .= ' t.duration,';
        $sql .= ' t.fk_reason_resolution,';
        $sql .= ' t.reason_resolution_details,';
        $sql .= ' t.date_operation,';
        $sql .= ' t.date_deadline,';
        $sql .= ' t.date_resolved,';
        $sql .= ' t.date_closed,';
        $sql .= ' t.fk_user_resolved,';
        $sql .= ' t.fk_user_closed,';
        $sql .= ' t.fk_status,';
        $sql .= ' t.entity,';
        $sql .= ' t.datec,';
        $sql .= ' t.tms,';
        $sql .= ' t.fk_user_author,';
        $sql .= ' t.fk_user_modif';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . ' as t';
        $sql .= ' WHERE t.entity IN (' . (!empty($entity) ? $entity : getEntity($this->element)) . ')';
        if ($id) $sql .= ' AND t.rowid = ' . $id;
        else if ($ref) $sql .= " AND t.ref = '".$this->db->escape($ref)."'";
        else if ($refext) $sql .= " AND t.ref_ext = '".$this->db->escape($refext)."'";

        $resql = $this->db->query($sql);
        if ($resql) {
            $numrows = $this->db->num_rows($resql);
            if ($numrows) {
                $obj = $this->db->fetch_object($resql);

                dol_include_once('/advancedictionaries/class/dictionary.class.php');
                $requestManagerStatusDictionaryLine = Dictionary::getDictionaryLine($this->db, 'requestmanager', 'requestmanagerstatus');
                $res = $requestManagerStatusDictionaryLine->fetch($obj->fk_status);
                if ($res == 0) {
                    $this->errors[] = $langs->trans('RequestManagerErrorStatusNotFound');
                    return -1;
                } elseif ($res < 0) {
                    array_merge($this->errors, $requestManagerStatusDictionaryLine->errors);
                    return -1;
                }
                $this->statut_type                  = $requestManagerStatusDictionaryLine->fields['type'];

                $this->id                           = $obj->rowid;
                $this->fk_parent                    = $obj->fk_parent;
                $this->ref                          = $obj->ref;
                $this->ref_ext                      = $obj->ref_ext;
                $this->socid_origin                 = $obj->fk_soc_origin;
                $this->socid                        = $obj->fk_soc;
                $this->socid_benefactor             = $obj->fk_soc_benefactor;
                $this->socid_watcher                = $obj->fk_soc_watcher;
                $this->availability_for_thirdparty_principal    = empty($obj->availability_for_thirdparty_principal) ? 0 : 1;
                $this->availability_for_thirdparty_benefactor   = empty($obj->availability_for_thirdparty_benefactor) ? 0 : 1;
                $this->availability_for_thirdparty_watcher      = empty($obj->availability_for_thirdparty_watcher) ? 0 : 1;
                $this->label                        = $obj->label;
                $this->description                  = $obj->description;
                $this->fk_type                      = $obj->fk_type;
                $this->fk_category                  = $obj->fk_category;
                $this->fk_source                    = $obj->fk_source;
                $this->fk_urgency                   = $obj->fk_urgency;
                $this->fk_impact                    = $obj->fk_impact;
                $this->fk_priority                  = $obj->fk_priority;
                $this->notify_requester_by_email    = empty($obj->notify_requester_by_email) ? 0 : 1;
                $this->notify_watcher_by_email      = empty($obj->notify_watcher_by_email) ? 0 : 1;
                $this->notify_assigned_by_email     = !empty($conf->global->REQUESTMANAGER_NOTIFICATION_ASSIGNED_BY_EMAIL) && !empty($obj->notify_assigned_by_email) ? 1 : 0;
                $this->duration                     = $obj->duration;
                $this->fk_reason_resolution         = $obj->fk_reason_resolution;
                $this->reason_resolution_details    = $obj->reason_resolution_details;
                $this->date_operation               = $this->db->jdate($obj->date_operation);
                $this->date_deadline                = $this->db->jdate($obj->date_deadline);
                $this->date_resolved                = $this->db->jdate($obj->date_resolved);
                $this->date_cloture                 = $this->db->jdate($obj->date_closed);
                $this->user_resolved_id             = $obj->fk_user_resolved;
                $this->user_cloture_id              = $obj->fk_user_closed;
                $this->statut                       = $obj->fk_status;
                $this->entity                       = $obj->entity;
                $this->date_creation                = $this->db->jdate($obj->datec);
                $this->date_modification            = $this->db->jdate($obj->tms);
                $this->user_creation_id             = $obj->fk_user_author;
                $this->user_modification_id         = $obj->fk_user_modif;

                if (!empty($conf->global->REQUESTMANAGER_TITLE_TO_REF_CUSTOMER_WHEN_CREATE_OTHER_ELEMENT)) {
                    $this->ref_client = $obj->label;
                }

                $this->fetch_assigned();
                $this->fetch_requesters();
                $this->fetch_watchers();
            }
            $this->db->free($resql);

            if ($numrows) {
                return 1;
            } else {
                return 0;
            }
        } else {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);

            return -1;
        }
    }


    /**
     * Add contracts of thirdparty and thirdparty parent (if set in module configuration)
     *
     * @param   int     $socId          [=0] Id of request thirdparty or specific thirdparty if > 0
     * @param   bool    $confChecked    [=FALSE] if configuration not checked yet, TRUE if configuration already checked
     * @return  int     <0 if KO,       <0 if KO, 0 if nothing to link, >0 Id of the last contract linked if OK
     */
    public function addContract($socId=0, $confChecked=FALSE)
    {
        global $conf;

        if ($confChecked===FALSE && empty($conf->contrat->enabled)) {
            return 0;
        }

        require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';

        $contractId = 0;
        if (!($socId > 0)) {
            $socId = $this->socid;
        }

        $contrat = new Contrat($this->db);
        $contrat->socid = $socId;
        $list = $contrat->getListOfContracts();
        if ($list < 0) {
            return -1;
        }

        foreach ($list as $contract) {
            if ($contract->statut == 1) { // draft(0) validated(1) and closed(2)
                if (is_array($this->linkedObjectsIds['contrat']) && in_array($contract->id, $this->linkedObjectsIds['contrat'])) continue;
                $contractId = $contract->id;
                $result = $this->setContract($contract->id);
                if ($result < 0 && $this->db->lasterrno() != 'DB_ERROR_RECORD_ALREADY_EXISTS') {
                    return -1;
                }
            }
        }

        if (!empty($conf->global->REQUESTMANAGER_CONTRACT_SEARCH_IN_PARENT_COMPANY)) {
            require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
            $thirdparty = new Societe($this->db);
            $thirdparty->fetch($socId);
            if ($thirdparty->parent > 0) {
                $contractId = $this->addContract($thirdparty->parent, TRUE);
                if ($contractId < 0) {
                    return -1;
                }
            }
        }

        return $contractId;
    }


    /**
     * Link element with a contract
     *
     * @param  int      $contractId             Contract id to link element to
     * @return int      <0 if KO, >0 if OK
     */
    public function setContract($contractId)
    {
        global $user;

        $result = $this->add_object_linked('contrat', $contractId);

        if ($result <= 0) {
            dol_syslog(__METHOD__, LOG_ERR);
            return -1;
        } else {
            // Call trigger
            $this->context = array('addlink' => 'contrat', 'addlinkid' => $contractId);
            $result = $this->call_trigger('REQUESTMANAGER_ADD_LINK', $user);
            if ($result < 0) return -1;
            // End call trigger
            return 1;
        }
    }


    /**
     * Find all equipement for a thirdparty
     *
     * @param   int         $fkSoc          [=0] Id of thirdparty, -1 for all
     * @return  resource    SQL resource
     */
    public function findAllEquipemenByFkSoc($fkSoc=0)
    {
        global $conf;

        $sql  = "SELECT";
        $sql .= " e.rowid";
        $sql .= ", e.ref";
        $sql .= " FROM " . MAIN_DB_PREFIX . "equipement as e";
        //$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as sfou on e.fk_soc_fourn = sfou.rowid";
        //$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as scli on e.fk_soc_client = scli.rowid";
        $sql .= " WHERE e.entity = " . $conf->entity;
        if ($fkSoc >= 0) {
            $sql .= " AND (e.fk_soc_fourn = ". $fkSoc . " OR e.fk_soc_client = " . $fkSoc . ")";
        }

        return $this->db->query($sql);
    }

    /**
     * Find all equipments for a benefactor company with principal company contract
     *
     * @param   int         $fkSocPrincipal         Id of principal company
     * @param   int         $fkSocBenefactor        Id of benefactor company
     * @return  resource    SQL resource
     */
    public function findAllBenefactorEquipments($fkSocPrincipal, $fkSocBenefactor)
    {
        global $conf, $hookmanager;

        $hookmanager->initHooks(array('requestmanagerdao'));
        $parameters = array('socid_principal'=>$fkSocPrincipal, 'socid_benefactor'=>$fkSocBenefactor);
        $reshook = $hookmanager->executeHooks('findAllBenefactorEquipmentsSQL', $parameters); // Note that $action and $object may have been
        if ($reshook) {
            $sql = $hookmanager->resPrint;
        } else {
            $sql = "SELECT e.rowid , e.ref, p.label";
            $sql .= " FROM " . MAIN_DB_PREFIX . "equipement as e";
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product as p ON e.fk_product = p.rowid";
            $sql .= " WHERE e.entity = " . $conf->entity;
            if ($fkSocBenefactor >= 0) {
                $sql .= " AND e.fk_soc_client = " . $fkSocBenefactor;
            }
        }

        return $this->db->query($sql);
    }


    /**
     * Add equipement of thirdparty
     *
     * @param   bool    $confChecked    [=FALSE] if configuration not checked yet, TRUE if configuration already checked
     * @return  int     <0 if KO, 0 if nothing to link, >0 if OK
     */
    public function addEquipement($confChecked=FALSE)
    {
        global $conf, $user;

        if ($confChecked===FALSE && empty($conf->equipement->enabled)) {
            return 0;
        }

        // find all equipement linked to the thirdpaty
        if ($this->socid > 0) {
            $resql = $this->findAllEquipemenByFkSoc($this->socid);
            if (!$resql) {
                $this->errors[] = $this->db->lasterror();
                return -1;
            } else {
                while ($obj = $this->db->fetch_object($resql)) {
                    $result = $this->add_object_linked('equipement', $obj->rowid);
                    if ($result <= 0) {
                        $this->errors[] = $this->db->lasterror();
                        dol_syslog(__METHOD__, LOG_ERR);
                        return -1;
                    } else {
                        // Call trigger
                        $this->context = array('addlink' => 'equipement', 'addlinkid' => $obj->rowid);
                        $result = $this->call_trigger('REQUESTMANAGER_ADD_LINK', $user);
                        if ($result < 0) return -1;
                        // End call trigger
                    }
                }
            }

            return 1;
        }

        return 0;
    }


    /**
     * Load all contracts of thirdparty and thirdparty parent (if set in module configuration)
     *
     * @param   int     $socId          [=0] Id of thirdparty
     * @param   bool    $confChecked    [=FALSE] if configuration not checked yet, TRUE if configuration already checked
     * @return  int     <0 if KO,       <0 if KO, 0 if nothing to link, >0 Id of the last contract linked if OK
     */
    public function loadAllContract($socId=0, $confChecked=FALSE, &$contractList=array())
    {
        global $conf;

        if ($confChecked===FALSE && empty($conf->contrat->enabled)) {
            return 0;
        }

        require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';

        if ($socId <= 0) {
            return 0;
        }

        $contrat = new Contrat($this->db);
        $contrat->socid = $socId;
        $thirdPartyContractList = $contrat->getListOfContracts();
        if (!is_array($thirdPartyContractList)) {
            return -1;
        } else {
            $contractList = array_merge($contractList, $thirdPartyContractList);
        }

        if (!empty($conf->global->REQUESTMANAGER_CONTRACT_SEARCH_IN_PARENT_COMPANY)) {
            require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
            $thirdparty = new Societe($this->db);
            $thirdparty->fetch($socId);
            if ($thirdparty->parent > 0) {
                $result = $this->loadAllContract($thirdparty->parent, TRUE, $contractList);
                if ($result < 0) {
                    return -1;
                }
            }
        }

        return 1;
    }


    /**
     * Load all equipement linked to third party
     *
     * @param   int     $fkSoc        [=0] Id of thirdparty
     * @return  array   List of equipement
     */
    public function loadAllEquipementByFkSoc($fkSoc=0)
    {
        $equipementList = array();

        dol_include_once('/equipement/class/equipement.class.php');

        $resql = $this->findAllEquipemenByFkSoc($fkSoc);

        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $equipement = new Equipement($this->db);
                $equipement->fetch($obj->rowid);
                $equipementList[] = $equipement;
            }
        }

        return $equipementList;
    }

    /**
     * Load all equipments for a benefactor company with principal company contract
     *
     * @param   int         $fkSocPrincipal         Id of principal company
     * @param   int         $fkSocBenefactor        Id of benefactor company
     * @return  array                               List of equipments
     */
    public function loadAllBenefactorEquipments($fkSocPrincipal, $fkSocBenefactor)
    {
        $equipmentList = array();

        $resql = $this->findAllBenefactorEquipments($fkSocPrincipal, $fkSocBenefactor);
        if ($resql) {
            dol_include_once('/equipement/class/equipement.class.php');
            while ($obj = $this->db->fetch_object($resql)) {
                $equipment = new Equipement($this->db);
                $equipment->fetch($obj->rowid);
                $equipmentList[] = $equipment;
            }
        }

        return $equipmentList;
    }


    /**
     * Load all avents linked to third party
     *
     * @param   int     $fkSoc      [=0] Id of thirdparty
     * @param   int     $limit      [=0] Limit to load, 0 to load nothing, -1 to load all
     * @param   string  $join       Join SQL
     * @param   string  $filter     Filter SQL
     * @return  array   List of ActionCom
     */
    public function loadAllLastEventByFkSoc($fkSoc=0, $limit=0, $join='', $filter='')
    {
        $lastEventList = array();

        $sql  = "SELECT DISTINCT ac.id";
        $sql .= " FROM " . MAIN_DB_PREFIX . "actioncomm ac";
        if (!empty($join)) $sql .= $join;
        $sql .= " WHERE ac.entity IN (" . getEntity('agenda') . ")";
        if ($fkSoc >= 0) {
            $sql .= " AND ac.fk_soc= " . $fkSoc;
        }
        if (!empty($filter)) $sql .= $filter;
        $sql .= " ORDER BY ac.datep DESC";
        if ($limit > 0) {
            $sql .= " LIMIT " . $limit;
        }

        $resql = $this->db->query($sql);

        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $actionComm = new ActionComm($this->db);
                $actionComm->fetch($obj->id);
                $lastEventList[] = $actionComm;
            }
        }

        return $lastEventList;
    }


    /**
     *  Load the assigned users and usergroups
     *
     * @param   int     $with_object    Load also the object
     * @return  int     <0 if KO, >0 if OK
     */
    function fetch_assigned($with_object=0)
    {
        require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
        require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';

        // Get assigned users
        $this->assigned_user_ids = array();
        $this->assigned_user_list = array();
        if ($this->id > 0) {
            $sql = 'SELECT fk_user FROM '.MAIN_DB_PREFIX.'requestmanager_assigned_user WHERE fk_requestmanager = '.$this->id;
            $resql = $this->db->query($sql);
            if ($resql) {
                while ($obj = $this->db->fetch_object($resql)) {
                    $this->assigned_user_ids[] = $obj->fk_user;
                    if ($with_object) {
                        $user = new User($this->db);
                        $user->fetch($obj->fk_user);
                        $this->assigned_user_list[$obj->fk_user] = $user;
                    }
                }
                $this->db->free($resql);
            }
        }

        // Get assigned users
        $this->assigned_usergroup_ids = array();
        $this->assigned_usergroup_list = array();
        if ($this->id > 0) {
            $sql = 'SELECT fk_usergroup FROM '.MAIN_DB_PREFIX.'requestmanager_assigned_usergroup WHERE fk_requestmanager = '.$this->id;
            $resql = $this->db->query($sql);
            if ($resql) {
                while ($obj = $this->db->fetch_object($resql)) {
                    $this->assigned_usergroup_ids[] = $obj->fk_usergroup;
                    if ($with_object) {
                        $usergroup = new UserGroup($this->db);
                        $usergroup->fetch($obj->fk_usergroup);
                        $this->assigned_usergroup_list[$obj->fk_usergroup] = $usergroup;
                    }
                }
                $this->db->free($resql);
            }
        }
    }

    /**
     *  Load the requester contacts
     *
     * @param   int     $with_object    Load also the object
     * @return  int     <0 if KO, >0 if OK
     */
    function fetch_requesters($with_object=0)
    {
        $this->requester_ids = array();
        $this->requester_list = array();

        require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';

        // Get contacts
        $contacts = $this->liste_contact(-1, 'external', 1, 'REQUESTER');
        if (!is_array($contacts))
            return -1;

        foreach ($contacts as $contact_id) {
            $this->requester_ids[$contact_id] = $contact_id;
            if ($with_object) {
                $contact = new Contact($this->db);
                $contact->fetch($contact_id);
                $this->requester_list[$contact_id] = $contact;
            }
        }
    }

    /**
     *  Load the watcher contacts
     *
     * @param   int     $with_object    Load also the object
     * @return  int     <0 if KO, >0 if OK
     */
    function fetch_watchers($with_object=0)
    {
        $this->watcher_ids = array();
        $this->watcher_list = array();

        require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';

        // Get contacts
        $contacts = $this->liste_contact(-1, 'external', 1, 'WATCHER');
        if (!is_array($contacts))
            return -1;

        foreach ($contacts as $contact_id) {
            $this->watcher_ids[$contact_id] = $contact_id;
            if ($with_object) {
                $contact = new Contact($this->db);
                $contact->fetch($contact_id);
                $this->watcher_list[$contact_id] = $contact;
            }
        }
    }

    /**
     *  Load the tag of the request
     *
     * @param   int     $with_object    Load also the object
     * @return  int     <0 if KO, >0 if OK
     */
    function fetch_tags($with_object=0)
    {
        $this->tag_ids = array();
        $this->tag_list = array();

        dol_include_once('/requestmanager/class/categorierequestmanager.class.php');
        $categorierequestmanager = new CategorieRequestManager($this->db);

        // Get tags
        $categories = $categorierequestmanager->containing($this->id, CategorieRequestManager::TYPE_REQUESTMANAGER, 'id');
        if (!is_array($categories))
            return -1;

        foreach ($categories as $category_id) {
            $this->tag_ids[$category_id] = $category_id;
            if ($with_object) {
                $category = new Categorie($this->db);
                $category->fetch($category_id);
                $this->tag_list[$category_id] = $category;
            }
        }
    }

    /**
     *  Load the parent request of object, from id $this->fk_parent, into this->parent
     *
     * @param		int		$force_request_id	    Force request id
     * @return		int								<0 if KO, >0 if OK
     */
    function fetch_parent($force_request_id=0)
    {
        $this->parent = null;
        if (empty($this->fk_parent))
            return 0;

        $idtofetch = $this->fk_parent;
        if ($force_request_id)
            $idtofetch = $force_request_id;

        if ($idtofetch) {
            $request = new RequestManager($this->db);
            $result = $request->fetch($this->fk_parent);
            $this->parent = $request;

            return $result;
        } else
            return -1;
    }

    /**
     *  Load the third party origin of object, from id $this->socid_origin, into this->thirdparty_origin
     *
     * @param		int		$force_thirdparty_id	Force thirdparty id
     * @return		int								<0 if KO, >0 if OK
     */
    function fetch_thirdparty_origin($force_thirdparty_id=0)
    {
        global $conf;

        if (empty($this->socid_origin))
            return 0;

        require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

        $idtofetch = $this->socid_origin;
        if ($force_thirdparty_id)
            $idtofetch = $force_thirdparty_id;

        if ($idtofetch) {
            $thirdparty = new Societe($this->db);
            $result = $thirdparty->fetch($idtofetch);
            $this->thirdparty_origin = $thirdparty;

            // Use first price level if level not defined for third party
            if (!empty($conf->global->PRODUIT_MULTIPRICES) && empty($this->thirdparty_origin->price_level)) {
                $this->thirdparty_origin->price_level = 1;
            }

            return $result;
        } else
            return -1;
    }

    /**
     *  Load the third party benefactor of object, from id $this->socid_benefactor, into this->thirdparty_benefactor
     *
     * @param		int		$force_thirdparty_id	Force thirdparty id
     * @return		int								<0 if KO, >0 if OK
     */
    function fetch_thirdparty_benefactor($force_thirdparty_id=0)
    {
        global $conf;

        if (empty($this->socid_benefactor))
            return 0;

        require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

        $idtofetch = $this->socid_benefactor;
        if ($force_thirdparty_id)
            $idtofetch = $force_thirdparty_id;

        if ($idtofetch) {
            $thirdparty = new Societe($this->db);
            $result = $thirdparty->fetch($idtofetch);
            $this->thirdparty_benefactor = $thirdparty;

            // Use first price level if level not defined for third party
            if (!empty($conf->global->PRODUIT_MULTIPRICES) && empty($this->thirdparty_benefactor->price_level)) {
                $this->thirdparty_benefactor->price_level = 1;
            }

            return $result;
        } else
            return -1;
    }

    /**
     *  Load the third party watcher of object, from id $this->socid_watcher, into this->thirdparty_watcher
     *
     * @param		int		$force_thirdparty_id	Force thirdparty id
     * @return		int								<0 if KO, >0 if OK
     */
    function fetch_thirdparty_watcher($force_thirdparty_id=0)
    {
        global $conf;

        if (empty($this->socid_watcher))
            return 0;

        require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

        $idtofetch = $this->socid_watcher;
        if ($force_thirdparty_id)
            $idtofetch = $force_thirdparty_id;

        if ($idtofetch) {
            $thirdparty = new Societe($this->db);
            $result = $thirdparty->fetch($idtofetch);
            $this->thirdparty_watcher = $thirdparty;

            // Use first price level if level not defined for third party
            if (!empty($conf->global->PRODUIT_MULTIPRICES) && empty($this->thirdparty_watcher->price_level)) {
                $this->thirdparty_watcher->price_level = 1;
            }

            return $result;
        } else
            return -1;
    }

    /**
	 *  Update request into database
	 *
	 * @param   User    $user           User that modifies
	 * @param   bool    $notrigger      false=launch triggers after, true=disable triggers
	 * @return  int                     <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = false)
	{
        global $conf, $langs, $hookmanager;
        $error = 0;
        $this->errors = array();
        $langs->load("requestmanager@requestmanager");

        dol_syslog(__METHOD__ . " user_id=" . $user->id . " id=" . $this->id, LOG_DEBUG);

        // Clean parameters
        $this->fk_parent = $this->fk_parent > 0 ? $this->fk_parent : 0;
        $this->socid_origin = $this->socid_origin > 0 ? $this->socid_origin : 0;
        $this->socid = $this->socid > 0 ? $this->socid : $this->socid_origin;
        $this->socid_benefactor = $this->socid_benefactor > 0 ? $this->socid_benefactor : $this->socid_origin;
        $this->socid_watcher = $this->socid_watcher > 0 ? $this->socid_watcher : 0;
        $this->availability_for_thirdparty_principal = !isset($this->availability_for_thirdparty_principal) ? 1 : (!empty($this->availability_for_thirdparty_principal) ? 1 : 0);
        $this->availability_for_thirdparty_benefactor = !isset($this->availability_for_thirdparty_benefactor) ? 1 : (!empty($this->availability_for_thirdparty_benefactor) ? 1 : 0);
        $this->availability_for_thirdparty_watcher = !isset($this->availability_for_thirdparty_watcher) ? 1 : (!empty($this->availability_for_thirdparty_watcher) ? 1 : 0);
        $this->label = trim($this->label);
        $this->description = trim($this->description);
        $this->fk_type = $this->fk_type > 0 ? $this->fk_type : 0;
        $this->fk_category = $this->fk_category > 0 ? $this->fk_category : 0;
        $this->fk_source = $this->fk_source > 0 ? $this->fk_source : 0;
        $this->fk_urgency = $this->fk_urgency > 0 ? $this->fk_urgency : 0;
        $this->fk_impact = $this->fk_impact > 0 ? $this->fk_impact : 0;
        $this->fk_priority = $this->fk_priority > 0 ? $this->fk_priority : 0;
        $this->notify_requester_by_email = !empty($this->notify_requester_by_email) ? 1 : 0;
        $this->notify_watcher_by_email = !empty($this->notify_watcher_by_email) ? 1 : 0;
        $this->assigned_user_ids = empty($this->assigned_user_ids) ? array() : (is_string($this->assigned_user_ids) ? explode(',', $this->assigned_user_ids) : $this->assigned_user_ids);
        $this->assigned_usergroup_ids = empty($this->assigned_usergroup_ids) ? array() : (is_string($this->assigned_usergroup_ids) ? explode(',', $this->assigned_usergroup_ids) : $this->assigned_usergroup_ids);
        $this->notify_assigned_by_email = !empty($conf->global->REQUESTMANAGER_NOTIFICATION_ASSIGNED_BY_EMAIL) && !empty($this->notify_assigned_by_email) ? 1 : 0;
        $this->date_creation = $this->date_creation > 0 ? $this->date_creation : 0;
        $this->date_operation = $this->date_operation > 0 ? $this->date_operation : 0;
        $this->date_deadline = $this->date_deadline > 0 ? $this->date_deadline : 0;
        $this->duration = $this->duration > 0 ? $this->duration : 0;
        $this->user_modification_id = $user->id;
        //$this->requester_ids = empty($this->requester_ids) ? array() : (is_string($this->requester_ids) ? explode(',', $this->requester_ids) : $this->requester_ids);
        //$this->watcher_ids = empty($this->watcher_ids) ? array() : (is_string($this->watcher_ids) ? explode(',', $this->watcher_ids) : $this->watcher_ids);
        if (empty($conf->companyrelationships->enabled)) {
            $this->socid = $this->socid_origin;
            $this->socid_benefactor = $this->socid_origin;
        }

        // Check parameters
        if (!($this->id > 0)) {
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("TechnicalID"));
            $error++;
        }
        if (empty($this->socid_origin)) {
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("RequestManagerThirdPartyOrigin"));
            $error++;
        }
        if (empty($this->fk_type)) {
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("RequestManagerType"));
            $error++;
        }
        if (!empty($this->fk_category)) {
            dol_include_once('/advancedictionaries/class/dictionary.class.php');
            $requestManagerRequestTypeDictionaryLine = Dictionary::getDictionaryLine($this->db, 'requestmanager', 'requestmanagerrequesttype');
            $res = $requestManagerRequestTypeDictionaryLine->fetch($this->fk_type);
            if ($res == 0) {
                $this->errors[] = $langs->trans('RequestManagerErrorRequestTypeNotFound');
                $error++;
            } elseif ($res < 0) {
                array_merge($this->errors, $requestManagerRequestTypeDictionaryLine->errors);
                $error++;
            } else {
                $cat = explode(',', $requestManagerRequestTypeDictionaryLine->fields['category']);
                if (!in_array($this->fk_category, $cat)) {
                    $this->errors[] = $langs->trans("RequestManagerErrorFieldCategoryNotInThisRequestType");
                    $error++;
                }
            }
        }
        if (empty($this->label)) {
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("RequestManagerLabel"));
            $error++;
        }
//        if (empty($this->description)) {
//            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("RequestManagerDescription"));
//            $error++;
//        }
        if ($this->date_deadline > 0 && $this->date_deadline < $this->date_creation) {
            $this->errors[] = $langs->trans("RequestManagerErrorDeadlineDateMustBeGreaterThanCreateDate");
            $error++;
        }
        if (!is_array($this->requester_ids)) {
            $this->errors[] = $langs->trans("ErrorBadParameters") . ': ' . $langs->trans("RequestManagerRequester");
            $error++;
        }
        if (!is_array($this->watcher_ids)) {
            $this->errors[] = $langs->trans("ErrorBadParameters") . ': ' . $langs->trans("RequestManagerWatcher");
            $error++;
        }
        if ($error) {
            dol_syslog(__METHOD__ . " Errors check parameters: " . $this->errorsToString(), LOG_ERR);
            return -3;
        }

        // Check if standard properties modified
        dol_include_once('/requestmanager/lib/opendsi_tools.lib.php');
        if (opendsi_is_updated_property($this, 'fk_parent') ||
            opendsi_is_updated_property($this, 'socid_origin') ||
            opendsi_is_updated_property($this, 'socid') ||
            opendsi_is_updated_property($this, 'socid_benefactor') ||
            opendsi_is_updated_property($this, 'socid_watcher') ||
            opendsi_is_updated_property($this, 'availability_for_thirdparty_principal') ||
            opendsi_is_updated_property($this, 'availability_for_thirdparty_benefactor') ||
            opendsi_is_updated_property($this, 'availability_for_thirdparty_watcher') ||
            opendsi_is_updated_property($this, 'label') ||
            opendsi_is_updated_property($this, 'description') ||
            opendsi_is_updated_property($this, 'fk_type') ||
            opendsi_is_updated_property($this, 'fk_category') ||
            opendsi_is_updated_property($this, 'fk_source') ||
            opendsi_is_updated_property($this, 'fk_urgency') ||
            opendsi_is_updated_property($this, 'fk_impact') ||
            opendsi_is_updated_property($this, 'fk_priority') ||
            opendsi_is_updated_property($this, 'notify_requester_by_email') ||
            opendsi_is_updated_property($this, 'notify_watcher_by_email') ||
            opendsi_is_updated_property($this, 'notify_assigned_by_email') ||
            opendsi_is_updated_property($this, 'duration') ||
            opendsi_is_updated_property($this, 'date_creation') ||
            opendsi_is_updated_property($this, 'date_operation') ||
            opendsi_is_updated_property($this, 'date_deadline') ||
            opendsi_is_updated_property($this, 'user_modification_id') ||
            opendsi_is_updated_property($this, 'array_options')
        ) {
            $this->context['has_properties_updated'] = true; // Can be modified by triggers
        }

        $now = dol_now();

		// Update request
		$sql = 'UPDATE ' . MAIN_DB_PREFIX . $this->table_element . ' SET';
        $sql .= " fk_parent = " . ($this->fk_parent > 0 ? $this->fk_parent : 'NULL');
        $sql .= ", fk_soc_origin = " . $this->socid_origin;
        $sql .= ", fk_soc = " . $this->socid;
        $sql .= ", fk_soc_benefactor = " . $this->socid_benefactor;
        $sql .= ", fk_soc_watcher = " . ($this->socid_watcher > 0 ? $this->socid_watcher : 'NULL');
        $sql .= ", availability_for_thirdparty_principal = " . ($this->availability_for_thirdparty_principal > 0 ? $this->availability_for_thirdparty_principal : 'NULL');
        $sql .= ", availability_for_thirdparty_benefactor = " . ($this->availability_for_thirdparty_benefactor > 0 ? $this->availability_for_thirdparty_benefactor : 'NULL');
        $sql .= ", availability_for_thirdparty_watcher = " . ($this->availability_for_thirdparty_watcher > 0 ? $this->availability_for_thirdparty_watcher : 'NULL');
        $sql .= ", label = '" . $this->db->escape($this->label) . "'";
        $sql .= ", description = " . (!empty($this->description) ? "'" . $this->db->escape($this->description) . "'" : 'NULL');
        $sql .= ", fk_type = " . $this->fk_type;
        $sql .= ", fk_category = " . ($this->fk_category > 0 ? $this->fk_category : 'NULL');
        $sql .= ", fk_source = " . ($this->fk_source > 0 ? $this->fk_source : 'NULL');
        $sql .= ", fk_urgency = " . ($this->fk_urgency > 0 ? $this->fk_urgency : 'NULL');
        $sql .= ", fk_impact = " . ($this->fk_impact > 0 ? $this->fk_impact : 'NULL');
        $sql .= ", fk_priority = " . ($this->fk_priority > 0 ? $this->fk_priority : 'NULL');
        $sql .= ", notify_requester_by_email = " . ($this->notify_requester_by_email > 0 ? $this->notify_requester_by_email : 'NULL');
        $sql .= ", notify_watcher_by_email = " . ($this->notify_watcher_by_email > 0 ? $this->notify_watcher_by_email : 'NULL');
        $sql .= ", notify_assigned_by_email = " . ($this->notify_assigned_by_email > 0 ? $this->notify_assigned_by_email : 'NULL');
        $sql .= ", duration = " . $this->duration;
        $sql .= ", datec = '" . $this->db->idate($this->date_creation) . "'";
        $sql .= ", date_operation = " . ($this->date_operation > 0 ? "'" . $this->db->idate($this->date_operation) . "'" : 'NULL');
        $sql .= ", date_deadline = " . ($this->date_deadline > 0 ? "'" . $this->db->idate($this->date_deadline) . "'" : 'NULL');
        $sql .= ", fk_user_modif = " . $this->user_modification_id;
        $sql .= ", tms = '" . $this->db->idate($now) ."'";
        $sql .= ' WHERE rowid = '.$this->id;

		$this->db->begin();

        $resql = $this->db->query($sql);
        if (!$resql) {
            $error++;
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
        }

        if (!$error) {
            // Actions on extra fields (by external module or standard code)
            // TODO le hook fait double emploi avec le trigger !!
            $hookmanager->initHooks(array('requestmanagerdao'));
            $parameters = array();
            $reshook = $hookmanager->executeHooks('insertExtraFields', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks
            if (empty($reshook)) {
                if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) { // For avoid conflicts if trigger used
                    $result = $this->insertExtraFields();
                    if ($result < 0) {
                        $error++;
                        dol_syslog(__METHOD__ . " Errors insert extra fields: " . $this->errorsToString(), LOG_ERR);
                    }
                }
            } else if ($reshook < 0) {
                $error++;
                dol_syslog(__METHOD__ . " Errors hook insertExtraFields: " . $hookmanager->error . (is_array($hookmanager->errors) ? (($hookmanager->error != '' ? ', ' : '') . join(', ', $hookmanager->errors)) : ''), LOG_ERR);
            }
        }

        // Set assigned
        if (!$error) {
            $result = $this->set_assigned($user, $this->assigned_user_ids, $this->assigned_usergroup_ids);
            if ($result < 0) {
                $error++;
            }
        }

        if (!$error && !$notrigger) {
            // Call trigger
            $result = $this->call_trigger('REQUESTMANAGER_MODIFY', $user);
            if ($result < 0) {
                $error++;
                dol_syslog(__METHOD__ . " Errors call trigger: " . $this->errorsToString(), LOG_ERR);
            }
            // End call triggers
        }

		// Commit or rollback
		if ($error) {
			$this->db->rollback();

			return - 1 * $error;
		} else {
            if (!empty($this->context['has_properties_updated']))
			    $this->db->commit();
            else
                $this->db->rollback();
            dol_syslog(__METHOD__ . " success", LOG_DEBUG);

			return 1;
		}
	}

    /**
     *  Create a sub request
     *
     * @param       int	    $new_request_type		Id of new request _type
     * @param       User    $user                   User that deletes
	 * @param       bool    $notrigger              false=launch triggers after, true=disable triggers
     * @return      int	                            New id of sub request
     */
    function createSubRequest($new_request_type, User $user, $notrigger = false)
    {
        global $conf, $langs;
        $error = 0;
        $this->errors = array();
        $langs->load("requestmanager@requestmanager");

        // Clean parameters
        $new_request_type = $new_request_type > 0 ? $new_request_type : 0;

        // Check parameters
        if (empty($new_request_type)) {
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("RequestManagerType"));
            $error++;
        } else {
            dol_include_once('/advancedictionaries/class/dictionary.class.php');
            $requestManagerRequestTypeDictionaryLine = Dictionary::getDictionaryLine($this->db, 'requestmanager', 'requestmanagerrequesttype');
            $res = $requestManagerRequestTypeDictionaryLine->fetch($new_request_type);
            if ($res == 0) {
                $this->errors[] = $langs->trans('RequestManagerErrorRequestTypeNotFound');
                $error++;
            } elseif ($res < 0) {
                $this->errors = array_merge($this->errors, $requestManagerRequestTypeDictionaryLine->errors);
                $error++;
            }
        }
        if ($error) {
            dol_syslog(__METHOD__ . " Errors check parameters: " . $this->errorsToString(), LOG_ERR);
            return -3;
        }

        $this->db->begin();

        // Clone the current request
        $requestChild = clone $this;

        $requestChild->fk_parent = $this->id;
        $requestChild->fk_type = $new_request_type;
        $requestChild->date_creation = 0;
        $requestChild->date_operation = 0;
        $requestChild->date_deadline = 0;
        $requestChild->context['createSubRequest'] = 'createSubRequest';

        // Fetch extrafields
        $requestChild->fetch_optionals();

        // Fetch lines with extrafields
        $this->fetch_lines();

        // Fetch categories
        $categories = $this->loadCategorieList('id');

        // Fetch linked objects
        $requestChild->fetchObjectLinked();

        // Create request child
        $result = $requestChild->create($user);
        if ($result < 0) {
            $this->errors = array_merge($this->errors, $requestChild->errors);
            $error++;
        }

        if (!$error) {
            $lines = $this->lines;
            $fk_parent_line = 0;
            $num = count($lines);
            for ($i = 0; $i < $num; $i++) {
                $label = (!empty($lines[$i]->label) ? $lines[$i]->label : '');
                $desc = (!empty($lines[$i]->desc) ? $lines[$i]->desc : $lines[$i]->libelle);

                // Positive line
                $product_type = ($lines[$i]->product_type ? $lines[$i]->product_type : 0);

                // Date start
                $date_start = false;
                if ($lines[$i]->date_debut_prevue)
                    $date_start = $lines[$i]->date_debut_prevue;
                if ($lines[$i]->date_debut_reel)
                    $date_start = $lines[$i]->date_debut_reel;
                if ($lines[$i]->date_start)
                    $date_start = $lines[$i]->date_start;

                // Date end
                $date_end = false;
                if ($lines[$i]->date_fin_prevue)
                    $date_end = $lines[$i]->date_fin_prevue;
                if ($lines[$i]->date_fin_reel)
                    $date_end = $lines[$i]->date_fin_reel;
                if ($lines[$i]->date_end)
                    $date_end = $lines[$i]->date_end;

                // Reset fk_parent_line for no child products and special product
                if (($lines[$i]->product_type != 9 && empty($lines[$i]->fk_parent_line)) || $lines[$i]->product_type == 9) {
                    $fk_parent_line = 0;
                }

                // Extrafields
                $array_options = 0;
                if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED) && method_exists($lines[$i], 'fetch_optionals')) {
                    $lines[$i]->fetch_optionals($lines[$i]->rowid);
                    $array_options = $lines[$i]->array_options;
                }

                $tva_tx = $lines[$i]->tva_tx;
                if (!empty($lines[$i]->vat_src_code) && !preg_match('/\(/', $tva_tx)) $tva_tx .= ' (' . $lines[$i]->vat_src_code . ')';

                $result = $requestChild->addline($desc, $lines[$i]->subprice, $lines[$i]->qty, $tva_tx, $lines[$i]->localtax1_tx, $lines[$i]->localtax2_tx, $lines[$i]->fk_product, $lines[$i]->remise_percent,
                    $lines[$i]->info_bits, 0, 'HT', 0, $date_start, $date_end, $product_type, $lines[$i]->rang, $lines[$i]->special_code, $fk_parent_line,
                    $lines[$i]->fk_fournprice, $lines[$i]->pa_ht, $label, $array_options, $lines[$i]->fk_unit
                );

                if ($result > 0) {
                    $lineid = $result;
                } else {
                    $lineid = 0;
                    $error++;
                    break;
                }

                // Defined the new fk_parent_line
                if ($result > 0 && $lines[$i]->product_type == 9) {
                    $fk_parent_line = $result;
                }
            }
        }

        if (!$error) {
            // copy external contacts if same company
            if ($requestChild->copy_linked_contact($this, 'external') < 0) {
                $this->errors = array_merge($this->errors, $requestChild->errors);
                $error++;
            }
        }

        if (!$error) {
            // copy categories
            if ($requestChild->setCategories($categories) < 0) {
                $this->errors = array_merge($this->errors, $requestChild->errors);
                $error++;
            }
        }

        if (!$error && !$notrigger) {
            // Call trigger
            $result = $requestChild->call_trigger('REQUESTMANAGER_SUBREQUEST_CREATE', $user);
            if ($result < 0) {
                $this->errors = array_merge($this->errors, $requestChild->errors);
                $error++;
            }
            // End call triggers
        }

        // End
        if (!$error) {
            $this->db->commit();
            return $requestChild->id;
        } else {
            $this->db->rollback();
            return -1;
        }
    }

	/**
	 *  Delete request in database
	 *
     * @param   User    $user           User that deletes
	 * @param   bool    $notrigger      false=launch triggers after, true=disable triggers
	 * @return  int                     <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = false)
    {
        global $conf, $langs;
        $error = 0;
        $this->errors = array();
        $langs->load("requestmanager@requestmanager");

        dol_syslog(__METHOD__ . " user_id=" . $user->id . " id=" . $this->id, LOG_DEBUG);

        // Check parameters
        if (!($this->id > 0)) {
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("TechnicalID"));
            $error++;
        }
        if (!isset($this->statut_type) && $this->id > 0) {
            $this->fetch($this->id);
        }
        if (!isset($this->statut_type) || $this->statut_type != self::STATUS_TYPE_INITIAL) {
            $this->errors[] = $langs->trans("RequestManagerErrorFieldStatusMustBeOfInitialType");
            $error++;
        }
        if ($error) {
            dol_syslog(__METHOD__ . " Errors check parameters: " . $this->errorsToString(), LOG_ERR);
            return -3;
        }

        $this->db->begin();

        // User is mandatory for trigger call
        if (!$notrigger) {
            // Call trigger
            $result = $this->call_trigger('REQUESTMANAGER_DELETE', $user);
            if ($result < 0) {
                $error++;
                dol_syslog(__METHOD__ . " Errors call trigger: " . $this->errorsToString(), LOG_ERR);
            }
            // End call triggers
        }

        // Delete assigned
        if (!$error) {
            $res = $this->set_assigned($user, array(), array(), 1);
            if ($res < 0) {
                $error++;
                dol_syslog(__METHOD__ . " Errors delete assigned: " . $this->errorsToString(), LOG_ERR);
            }
        }

        // Delete linked categories
        if (!$error) {
            $sql  = 'DELETE FROM ' . MAIN_DB_PREFIX . 'categorie_requestmanager';
            $sql .= ' WHERE fk_requestmanager = ' . $this->id;

            $resql = $this->db->query($sql);
            if (!$resql) {
                $error++;
                $this->errors[] = 'Error ' . $this->db->lasterror();
                dol_syslog(__METHOD__ . " SQL: " . $sql . "; Errors delete linked categories: " . $this->db->lasterror(), LOG_ERR);
            }
        }

        // Delete linked object
        if (!$error) {
            $res = $this->deleteObjectLinked();
            if ($res < 0) {
                $error++;
                dol_syslog(__METHOD__ . " Errors delete linked object: " . $this->errorsToString(), LOG_ERR);
            }
        }

        // Delete linked contacts
        if (!$error) {
            $res = $this->delete_linked_contact();
            if ($res < 0) {
                $error++;
                dol_syslog(__METHOD__ . " Errors delete linked contacts: " . $this->errorsToString(), LOG_ERR);
            }
        }

        // Removed extrafields
        if (!$error && empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) { // For avoid conflicts if trigger used
            $result = $this->deleteExtraFields();
            if ($result < 0) {
                $error++;
                dol_syslog(__METHOD__ . " Errors delete extra fields: " . $this->errorsToString(), LOG_ERR);
            }
        }

        // Remove request lines
        if (!$error) {
            $sql = "DELETE FROM " . MAIN_DB_PREFIX . $this->table_element_line;
            $sql .= ' WHERE fk_requestmanager = ' . $this->id;

            $resql = $this->db->query($sql);
            if (!$resql) {
                $error++;
                $this->errors[] = 'Error ' . $this->db->lasterror();
                dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
            }
        }

        // Remove request
        if (!$error) {
            $sql = "DELETE FROM " . MAIN_DB_PREFIX . $this->table_element;
            $sql .= ' WHERE rowid = ' . $this->id;

            $resql = $this->db->query($sql);
            if (!$resql) {
                $error++;
                $this->errors[] = 'Error ' . $this->db->lasterror();
                dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
            }
        }

        if (!$error) {
            $this->db->commit();
            dol_syslog(__METHOD__ . " success", LOG_DEBUG);

            return 1;
        } else {
            $this->db->rollback();

            return -1;
        }
    }

    /**
     *  Set assigned to the request
     *
     *  @param  User    $user                       User that action
     *  @param	array	$assigned_user_ids          List of user ID assigned to the request
     *  @param 	array   $assigned_usergroup_ids 	List of usergroup ID assigned to the request
     *  @param  int		$notrigger			        Disable all triggers
     *  @return int                 		        <0 if KO, >0 if OK
     */
    public function set_assigned(User $user, $assigned_user_ids, $assigned_usergroup_ids, $notrigger=0)
    {
        dol_syslog(get_class($this)."::set_assigned assigned_user_ids:".implode(', ', $assigned_user_ids).", assigned_usergroup_ids:".implode(', ', $assigned_usergroup_ids).", notrigger:$notrigger", LOG_DEBUG);

        $this->new_assigned_user_ids = empty($assigned_user_ids) ? array() : (is_string($assigned_user_ids) ? explode(',', $assigned_user_ids) : $assigned_user_ids);
        $this->new_assigned_usergroup_ids = empty($assigned_usergroup_ids) ? array() : (is_string($assigned_usergroup_ids) ? explode(',', $assigned_usergroup_ids) : $assigned_usergroup_ids);

        $this->new_assigned_user_ids = array_unique($this->new_assigned_user_ids);
        $this->new_assigned_usergroup_ids = array_unique($this->new_assigned_usergroup_ids);

        $error = 0;
        $this->errors = array();
        $sql = '';

        // Get old assigned
        $this->fetch_assigned();

        // Get assigned user added
        $this->assigned_user_added_ids = array();
        foreach ($this->new_assigned_user_ids as $assigned_user_id) {
            if (!in_array($assigned_user_id, $this->assigned_user_ids))
                $this->assigned_user_added_ids[] = $assigned_user_id;
        }

        // Get assigned usergroup added
        $this->assigned_usergroup_added_ids = array();
        foreach ($this->new_assigned_usergroup_ids as $assigned_usergroup_id) {
            if (!in_array($assigned_usergroup_id, $this->assigned_usergroup_ids))
                $this->assigned_usergroup_added_ids[] = $assigned_usergroup_id;
        }

        // Get assigned user deleted
        $this->assigned_user_deleted_ids = array();
        foreach ($this->assigned_user_ids as $assigned_user_id) {
            if (!in_array($assigned_user_id, $this->new_assigned_user_ids))
                $this->assigned_user_deleted_ids[] = $assigned_user_id;
        }

        // Get assigned usergroup deleted
        $this->assigned_usergroup_deleted_ids = array();
        foreach ($this->assigned_usergroup_ids as $assigned_usergroup_id) {
            if (!in_array($assigned_usergroup_id, $this->new_assigned_usergroup_ids))
                $this->assigned_usergroup_deleted_ids[] = $assigned_usergroup_id;
        }

        if (count($this->assigned_user_added_ids) == 0 && count($this->assigned_user_deleted_ids) == 0 && count($this->assigned_usergroup_added_ids) == 0 && count($this->assigned_usergroup_deleted_ids) == 0) {
            dol_syslog(get_class($this)."::set_assigned : Assigned users and usergroups not changed", LOG_DEBUG);
            return 1;
        }

        $this->db->begin();

        // Delete assigned user into database
        if (count($this->assigned_user_deleted_ids) > 0) {
            $sql = "DELETE FROM " . MAIN_DB_PREFIX . "requestmanager_assigned_user WHERE fk_requestmanager = " . $this->id . " AND fk_user IN (" . implode(',', $this->assigned_user_deleted_ids) . ")";
            $resql = $this->db->query($sql);
            if (!$resql) {
                $this->errors[] = 'Error ' . $this->db->lasterror();
                dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);

                $error++;
            }
        }

        // Delete assigned usergroup into database
        if (!$error && count($this->assigned_usergroup_deleted_ids) > 0) {
            $sql = "DELETE FROM " . MAIN_DB_PREFIX . "requestmanager_assigned_usergroup WHERE fk_requestmanager = " . $this->id . " AND fk_usergroup IN (" . implode(',', $this->assigned_usergroup_deleted_ids) . ")";
            $resql = $this->db->query($sql);
            if (!$resql) {
                $this->errors[] = 'Error ' . $this->db->lasterror();
                dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);

                $error++;
            }
        }

        // Add assigned user into database
        if (!$error && count($this->assigned_user_added_ids) > 0) {
            $requests = array();
            $values = array();
            $idx = 0;
            foreach ($this->assigned_user_added_ids as $user_id) {
                $idx++;
                if ($idx % 100 == 0) {
                    $requests[] = "INSERT INTO " . MAIN_DB_PREFIX . "requestmanager_assigned_user (fk_requestmanager, fk_user) VALUES " . implode(',', $values);
                    $values = array();
                }
                $values[] = "(" . $this->id . ", " . $user_id . ")";
            }
            if (count($values) > 0) {
                $requests[] = "INSERT INTO " . MAIN_DB_PREFIX . "requestmanager_assigned_user (fk_requestmanager, fk_user) VALUES " . implode(',', $values);
            }

            foreach ($requests as $request) {
                $sql = $request;
                $resql = $this->db->query($sql);
                if (!$resql) {
                    $this->errors[] = 'Error ' . $this->db->lasterror();
                    dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);

                    $error++;
                    break;
                }
            }
        }

        // Add assigned usergroup into database
        if (!$error && count($this->assigned_usergroup_added_ids) > 0) {
            $requests = array();
            $values = array();
            $idx = 0;
            foreach ($this->assigned_usergroup_added_ids as $usergroup_id) {
                $idx++;
                if ($idx % 100 == 0) {
                    $requests[] = "INSERT INTO " . MAIN_DB_PREFIX . "requestmanager_assigned_usergroup (fk_requestmanager, fk_usergroup) VALUES " . implode(',', $values);
                    $values = array();
                }
                $values[] = "(" . $this->id . ", " . $usergroup_id . ")";
            }
            if (count($values) > 0) {
                $requests[] = "INSERT INTO " . MAIN_DB_PREFIX . "requestmanager_assigned_usergroup (fk_requestmanager, fk_usergroup) VALUES " . implode(',', $values);
            }

            foreach ($requests as $request) {
                $sql = $request;
                $resql = $this->db->query($sql);
                if (!$resql) {
                    $this->errors[] = 'Error ' . $this->db->lasterror();
                    dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);

                    $error++;
                    break;
                }
            }
        }

        if (!$error && (count($this->assigned_user_added_ids) > 0 || count($this->assigned_usergroup_added_ids) > 0 ||
                count($this->assigned_user_deleted_ids) > 0 || count($this->assigned_usergroup_deleted_ids) > 0)) {
            $this->context['has_properties_updated'] = true; // Can be modified by triggers
        }

        if (!$error && !$notrigger) {
            $result = $this->call_trigger('REQUESTMANAGER_SET_ASSIGNED', $user);
            if ($result < 0) {
                $error++;
            }
        }

        if ($error) {
            $this->db->rollback();
            return -1;
        } else {
            $this->assigned_user_ids = $this->new_assigned_user_ids;
            $this->assigned_usergroup_ids = $this->new_assigned_usergroup_ids;
            $this->db->commit();
            return 1;
        }
    }

    /**
	 *  Set status request into database
	 *
     * @param   int     $status                     New status
     * @param   int     $status_type                New status type (initial, first in progress, resolved or closed) (-1 if not used)
     * @param   User    $user                       User that modifies
     * @param   int     $reason_resolution          Id of the reason for resolution (when set STATUS_RESOLVED)
     * @param   string  $reason_resolution_details  Details of the reason for resolution (when set STATUS_RESOLVED)
	 * @param   bool    $notrigger                  false=launch triggers after, true=disable triggers
     * @param   int     $forcereload                Force reload of the cache
     * @param   int		$dont_check		            Don't check the old and new status is equal do pass
	 * @return  int                                 <0 if KO, >0 if OK
	 */
	public function set_status($status, $status_type, User $user, $reason_resolution=0, $reason_resolution_details='', $notrigger = false, $forcereload = 0, $dont_check=0)
    {
        global $langs;
        $error = 0;
        $this->errors = array();
        $langs->load("requestmanager@requestmanager");

        dol_syslog(__METHOD__ . " user_id=" . $user->id . " id=" . $this->id . " status=" . $status, LOG_DEBUG);

        // Clean parameters
        $status = $status > 0 ? $status : 0;
        $status_type = $status_type == self::STATUS_TYPE_INITIAL || $status_type == self::STATUS_TYPE_IN_PROGRESS || $status_type == self::STATUS_TYPE_RESOLVED || $status_type == self::STATUS_TYPE_CLOSED ? $status_type : -1;

        // Check parameters
        if (!($this->fk_type > 0)) {
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("RequestManagerType"));
            $error++;
        }
        if (!$error) {
            if (empty(self::$status_list) || $forcereload) {
                dol_include_once('/advancedictionaries/class/dictionary.class.php');
                $dictionary = Dictionary::getDictionary($this->db, 'requestmanager', 'requestmanagerstatus');
                $dictionary->fetch_lines(1, array(), array('type' => 'ASC', 'position' => 'ASC'));
                self::$status_list = $dictionary->lines;
            }
            if ($status_type >= 0) {
                $found = false;
                foreach (self::$status_list as $s) {
                    if ($status_type == $s->fields['type'] && in_array($this->fk_type, explode(',', $s->fields['request_type']))) {
                        $found = true;
                        $status = $s->id;
                    }
                }
                if (!$found) {
                    $this->errors[] = $langs->trans('RequestManagerErrorStatusNotFound');
                    $error++;
                }
            } elseif ($status > 0) {
                if (!isset(self::$status_list[$status])) {
                    $this->errors[] = $langs->trans('RequestManagerErrorStatusNotFound');
                    $error++;
                }
            } else {
                $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Status"));
                $error++;
            }
        }
        if (!$error) {
            // Get group of user
            require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';
            $usergroup_static = new UserGroup($this->db);
            $user_groups = $usergroup_static->listGroupsForUser($user->id);
            $user_groups = is_array($user_groups) ? array_keys($user_groups) : array();

            $authorized_user = self::$status_list[$status]->fields['authorized_user'];
            $authorized_usergroup = self::$status_list[$status]->fields['authorized_usergroup'];

            $not_authorized_user = !empty($authorized_user) ? !in_array($user->id, explode(',', $authorized_user)) : false;
            $not_authorized_usergroup = false;
            if (!empty($authorized_usergroup)) {
                $not_authorized_usergroup = true;
                $authorized_usergroup = explode(',', $authorized_usergroup);
                foreach ($authorized_usergroup as $group_id) {
                    if (in_array($group_id, $user_groups)) {
                        $not_authorized_usergroup = false;
                        break;
                    }
                }
            }

            if ($not_authorized_user || $not_authorized_usergroup) {
                $this->errors[] = $langs->trans('RequestManagerErrorNotAuthorized');
                $error++;
            }
        }
        if (!$error) {
            if (self::$status_list[$status]->fields['type'] == self::STATUS_TYPE_RESOLVED) {
                if ($reason_resolution > 0) {
                    $reasons_resolutions = self::$status_list[$status]->fields['reason_resolution'];
                    $reasons_resolutions = isset($reasons_resolutions) ? $reasons_resolutions : '';
                    $reasons_resolutions = array_filter(array_map('trim', explode(',', $reasons_resolutions)), 'strlen');
                    if (!in_array($reason_resolution, $reasons_resolutions)) {
                        $this->errors[] = $langs->trans('RequestManagerErrorStatusDoNotHaveThisReasonResolution', self::$status_list[$status]->fields['label']);
                        $error++;
                    }
                }
            } else {
                $reason_resolution = 0;
                $reason_resolution_details = '';
            }
        }
        if ($error) {
            dol_syslog(__METHOD__ . " Errors check parameters: " . $this->errorsToString(), LOG_ERR);
            return -3;
        }

        // Get new status information
        $new_status_infos = self::$status_list[$status];

        /*if ($status == $this->statut && !$dont_check) {
            dol_syslog(__METHOD__ . " : Status not changed", LOG_DEBUG);
            return 1;
        }*/

        $this->db->begin();

        // Auto update values
        $now = dol_now();
        $assigned_users = !empty($new_status_infos->fields['assigned_user']) ? (is_string($new_status_infos->fields['assigned_user']) ? explode(',', $new_status_infos->fields['assigned_user']) : $new_status_infos->fields['assigned_user']) : $this->assigned_user_ids;
        $assigned_usergroups = !empty($new_status_infos->fields['assigned_usergroup']) ? (is_string($new_status_infos->fields['assigned_usergroup']) ? explode(',', $new_status_infos->fields['assigned_usergroup']) : $new_status_infos->fields['assigned_usergroup']) : $this->assigned_usergroup_ids;
        if (!isset($new_status_infos->fields['assigned_user_replaced']) || !$new_status_infos->fields['assigned_user_replaced']) {
            $assigned_users = array_merge($assigned_users, $this->assigned_user_ids);
        }
        if (!isset($new_status_infos->fields['assigned_usergroup_replaced']) || !$new_status_infos->fields['assigned_usergroup_replaced']) {
            $assigned_usergroups = array_merge($assigned_usergroups, $this->assigned_usergroup_ids);
        }
        if (!empty($new_status_infos->fields['assigned_user_current'])) {
            if (!is_array($assigned_users)) {
                $assigned_users = array($user->id);
            } elseif (!in_array($user->id, $assigned_users)) {
                $assigned_users[] = $user->id;
            }
        }
        $date_operation = null;
        if (isset($new_status_infos->fields['operation'])) {
            if ($new_status_infos->fields['operation'] > 0) {
                $date_operation = $now + ($new_status_infos->fields['operation'] * 60);
            } elseif ($new_status_infos->fields['operation'] == -1 || $new_status_infos->fields['type'] == self::STATUS_TYPE_INITIAL) {
                $date_operation = $this->date_operation;
            } elseif ($new_status_infos->fields['operation'] == -2) {
                $date_operation = $now;
            }
        }
        $date_deadline = null;
        if (isset($new_status_infos->fields['deadline'])) {
            if ($new_status_infos->fields['deadline'] > 0) {
                $date_deadline = (!empty($date_operation) ? $date_operation : $now) + ($new_status_infos->fields['deadline'] * 60);
            } elseif ($new_status_infos->fields['deadline'] == -1 || $new_status_infos->fields['type'] == self::STATUS_TYPE_INITIAL) {
                $date_deadline = $this->date_deadline;
            } elseif ($new_status_infos->fields['deadline'] == -2) {
                $date_deadline = $now;
            }
        }
        if (count(array_diff($assigned_users, $this->assigned_user_ids)) || count(array_diff($assigned_usergroups, $this->assigned_usergroup_ids)) || $date_operation != $this->date_operation || $date_deadline != $this->date_deadline) {
            $this->fetch($this->id);
            $this->oldcopy = clone $this;
            $this->assigned_user_ids = $assigned_users;
            $this->assigned_usergroup_ids = $assigned_usergroups;
            $this->date_operation = $date_operation;
            $this->date_deadline = $date_deadline;
            $result = $this->update($user);
            if ($result < 0) {
                $error++;
            }
        }

        // Close all events linked to this request
        if (!$error && !empty($new_status_infos->fields['close_all_event'])) {
            $sql = "UPDATE " . MAIN_DB_PREFIX . "actioncomm as t";
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "element_element as ee";
            $element_correspondance = "(" . // Todo a completer si il y a d'autres correspondances
                "IF(t.elementtype = 'contract', 'contrat', " .
                " IF(t.elementtype = 'invoice', 'facture', " .
                "  IF(t.elementtype = 'order', 'commande', " .
                "   t.elementtype)))" .
                ")";
            $sql .= " ON (ee.sourcetype = " . $element_correspondance . " AND ee.fk_source = t.fk_element) OR (ee.targettype = " . $element_correspondance . " AND ee.fk_target = t.fk_element)";
            $sql .= ' SET t.percent = 100';
            $sql .= ' WHERE t.entity IN (' . getEntity('agenda') . ')';
            $soc_ids = array_unique(array($this->socid_origin, $this->socid, $this->socid_benefactor));
            $sql .= ' AND t.fk_soc IN (' . implode(',', $soc_ids) . ')';
            $request_children_ids = $this->getAllChildrenRequest();
            $request_ids = array_merge($request_children_ids, array($this->id));
            $sql .= " AND IF(t.elementtype='requestmanager', t.fk_element, IF(ee.targettype='requestmanager', ee.fk_target, IF(ee.sourcetype='requestmanager', ee.fk_source, NULL))) IN(" . implode(',', $request_ids) . ")";
            $sql .= ' AND t.percent < 100 AND t.percent >= 0';

            $resql = $this->db->query($sql);
            if (!$resql) {
                $error++;
                $this->errors[] = 'Error ' . $this->db->lasterror();
                dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
            }
        }

        // Create sub request
        if (!$error && !empty($new_status_infos->fields['new_request_type']) && $new_status_infos->fields['new_request_type_auto']) {
            foreach (explode(',', $new_status_infos->fields['new_request_type']) as $new_request_type_id) {
                $id = $this->createSubRequest($new_request_type_id, $user);
                if ($id < 0) {
                    $error++;
                    break;
                }
            }
        }

        // Update request status
        if (!$error) {
            // Update status information
            $this->oldcopy = clone $this;
            $this->statut = $status;
            $this->statut_type = $new_status_infos->fields['type'];

            $sql = 'UPDATE ' . MAIN_DB_PREFIX . $this->table_element . ' SET';
            $sql .= " fk_status = " . $this->statut;
            if ($new_status_infos->fields['type'] == self::STATUS_TYPE_RESOLVED) {
                $this->fk_reason_resolution = $reason_resolution;
                $this->reason_resolution_details = $reason_resolution_details;
                $sql .= ", fk_reason_resolution = " . ($this->fk_reason_resolution > 0 ? $this->fk_reason_resolution : 'NULL');
                $sql .= ", reason_resolution_details = " . ($this->reason_resolution_details > 0 && !empty($this->reason_resolution_details) ? "'" . $this->db->escape($this->reason_resolution_details) . "'" : 'NULL');
            }
            $sql .= ' WHERE rowid = ' . $this->id;

            $resql = $this->db->query($sql);
            if (!$resql) {
                $error++;
                $this->errors[] = 'Error ' . $this->db->lasterror();
                dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
            }
        }

        if (!$error && !$notrigger) {
            // Call trigger
            $result = $this->call_trigger('REQUESTMANAGER_STATUS_MODIFY', $user);
            if ($result < 0) {
                $error++;
                dol_syslog(__METHOD__ . " Errors call trigger: " . $this->errorsToString(), LOG_ERR);
            }
            // End call triggers
        }

        // Auto next status
        if (!$error) {
            $next_status = !empty($new_status_infos->fields['next_status']) ? explode(',', $new_status_infos->fields['next_status']) : array();
            if (!$error && count($next_status) == 1 && $new_status_infos->fields['next_status_auto']) {
                $result = $this->set_status($next_status[0], -1, $user);
                if ($result < 0) {
                    $error++;
                }
            }
        }

        // Auto next status when all children of the parent closed
        if (!$error && $this->statut_type == RequestManager::STATUS_TYPE_CLOSED && $this->fk_parent > 0) {
            if (!is_object($this->parent)) {
                if ($this->fetch_parent() < 0) {
                    $this->error = $this->parent->error;
                    $this->errors = $this->parent->errors;
                    $error++;
                }
                if (empty($this->parent->id)) {
                    $error_msg = 'Fetch parent request of this request (ID: '.$this->fk_parent.') not found for check auto next status when close child.';
                    dol_syslog(__METHOD__ . " " . $error_msg, LOG_ERR);
                    $this->errors[] = $error_msg;
                    $error++;
                }
            }

            if (!$error) {
                $parent_status_infos = self::$status_list[$this->parent->statut];

                if (!empty($parent_status_infos->fields['new_request_type']) && !empty($parent_status_infos->fields['new_request_type_next_status_auto'])) {
                    $parent_next_status = !empty($parent_status_infos->fields['next_status']) ? explode(',', $parent_status_infos->fields['next_status']) : array();
                    // Get count children request by status type
                    $children_count = $this->parent->getCountChildrenRequestByStatusType();

                    if ($children_count[RequestManager::STATUS_TYPE_INITIAL] + $children_count[RequestManager::STATUS_TYPE_IN_PROGRESS] + $children_count[RequestManager::STATUS_TYPE_RESOLVED] == 0 &&
                        $children_count[RequestManager::STATUS_TYPE_CLOSED] >= 0
                    ) {
                        $result = $this->parent->set_status($parent_next_status[0], -1, $user);
                        if ($result < 0) {
                            $this->error = $this->parent->error;
                            $this->errors = $this->parent->errors;
                            $error++;
                        }
                    }
                }
            }
        }

        // Commit or rollback
        if ($error) {
            foreach ($this->oldcopy as $k => $v) {
                if ($k == 'oldcopy') continue;
                $this->$k = $v;
            }
            $this->oldcopy = null;
            $this->db->rollback();

            return -1 * $error;
        } else {
            $this->db->commit();
            dol_syslog(__METHOD__ . " success", LOG_DEBUG);

            return 1;
        }
    }

    /**
     *  Load the children request
     *
     * @param   int     $with_object    Load also the object
     * @return  int                     <0 if KO, >0 if OK
     */
    function fetch_children_request($request_id=null, $with_object=0)
    {
        $this->children_request_ids = array();
        $this->children_request_list = array();

        // Get contacts
        $children_ids = $this->getAllChildrenRequest($request_id);
        if (!is_array($children_ids))
            return -1;

        foreach ($children_ids as $request_id) {
            $this->children_request_ids[$request_id] = $request_id;
            if ($with_object) {
                $request = new RequestManager($this->db);
                $request->fetch($request_id);
                $this->children_request_list[$request_id] = $request;
            }
        }
    }

    /**
     *  Return list of children request ID
     *
     * @param   int         $request_id         Id of the parent request
     * @return  array                           List of children request ID
     */
    function getAllChildrenRequest($request_id=null)
    {
        if (!isset($request_id)) $request_id = $this->id;

        $children_ids = array();

        // Get all children request
        $sql = 'SELECT rowid as child_id FROM '.MAIN_DB_PREFIX . $this->table_element . ' WHERE fk_parent = ' . $request_id;

        $resql = $this->db->query($sql);
        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $children_ids[] = $obj->child_id;
                $result = $this->getAllChildrenRequest($obj->child_id);
                $children_ids = array_merge($children_ids, $result);
            }
        }

        return $children_ids;
    }

    /**
     *  Return count of children request by status type
     *
     * @return  array   Count of children request by status type
     */
    function getCountChildrenRequestByStatusType()
    {
        $children_count = array(
            self::STATUS_TYPE_INITIAL => 0,
            self::STATUS_TYPE_IN_PROGRESS => 0,
            self::STATUS_TYPE_RESOLVED => 0,
            self::STATUS_TYPE_CLOSED => 0,
        );

        $children_ids = $this->getAllChildrenRequest();
        if (count($children_ids) > 0) {
            // Get all children request
            $sql = 'SELECT crmst.type AS status_type, count(rm.rowid) AS nb_children';
            $sql .= ' FROM ' . MAIN_DB_PREFIX . 'requestmanager AS rm';
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_requestmanager_status AS crmst ON (crmst.rowid = rm.fk_status)";
            $sql .= " WHERE rm.rowid IN (" . implode(',', $children_ids) . ")";
            $sql .= " GROUP BY crmst.type";

            $resql = $this->db->query($sql);
            if ($resql) {
                while ($obj = $this->db->fetch_object($resql)) {
                    $children_count[$obj->status_type] = $obj->nb_children;
                }
            }
        }

        return $children_count;
    }

    /**
     *  Return label of type
     *
     * @param   int         $mode       0=libelle, 1=code, 2=code - libelle
     * @return  string                  Label
     */
    function getLibType($mode=0)
    {
        return $this->LibType($this->fk_type, $mode);
    }

    /**
     *  Return label of type provides
     *
     * @param   int         $id             Id
     * @param   int         $mode           0=libelle, 1=code, 2=code - libelle
     * @param   int         $forcereload    Force reload of the cache
     * @return  string                      Label
     */
    function LibType($id,$mode=0,$forcereload=0)
    {
        global $langs;

        if (!($id > 0))
            return '';

        $langs->load("requestmanager@requestmanager");

        if (empty(self::$type_list) || $forcereload) {
            dol_include_once('/advancedictionaries/class/dictionary.class.php');
            $dictionary = Dictionary::getDictionary($this->db, 'requestmanager', 'requestmanagerrequesttype');
            $dictionary->fetch_lines(1, array(), array('label' => 'ASC'));
            self::$type_list = $dictionary->lines;
        }

        if (!isset(self::$type_list[$id])) {
            return $langs->trans('RequestManagerErrorNotFound');
        }

        $typeInfos = self::$type_list[$id];

        if ($mode == 0) return $typeInfos->fields['label'];
        if ($mode == 1) return $typeInfos->fields['code'];
        if ($mode == 2) return $typeInfos->fields['code'] . ' - ' . $typeInfos->fields['label'];
    }

    /**
     *  Return label of category
     *
     * @param   int         $mode       0=libelle, 1=code, 2=code - libelle
     * @return  string                  Label
     */
    function getLibCategory($mode=0)
    {
        return $this->LibCategory($this->fk_category, $mode);
    }

    /**
     *  Return label of category provides
     *
     * @param   int         $id             Id
     * @param   int         $mode           0=libelle, 1=code, 2=code - libelle
     * @param   int         $forcereload    Force reload of the cache
     * @return  string                      Label
     */
    function LibCategory($id,$mode=0,$forcereload=0)
    {
        global $langs;

        if (!($id > 0))
            return '';

        $langs->load("requestmanager@requestmanager");

        if (empty(self::$category_list) || $forcereload) {
            dol_include_once('/advancedictionaries/class/dictionary.class.php');
            $dictionary = Dictionary::getDictionary($this->db, 'requestmanager', 'requestmanagercategory');
            $dictionary->fetch_lines(1, array(), array('label' => 'ASC'));
            self::$category_list = $dictionary->lines;
        }

        if (!isset(self::$category_list[$id])) {
            return $langs->trans('RequestManagerErrorNotFound');
        }

        $categoryInfos = self::$category_list[$id];

        if ($mode == 0) return $categoryInfos->fields['label'];
        if ($mode == 1) return $categoryInfos->fields['code'];
        if ($mode == 2) return $categoryInfos->fields['code'] . ' - ' . $categoryInfos->fields['label'];
    }

    /**
     *  Return label of source
     *
     * @param   int         $mode       0=libelle, 1=code, 2=code - libelle
     * @return  string                  Label
     */
    function getLibSource($mode=0)
    {
        return $this->LibSource($this->fk_source, $mode);
    }

    /**
     *  Return label of source provides
     *
     * @param   int         $id             Id
     * @param   int         $mode           0=libelle, 1=code, 2=code - libelle
     * @param   int         $forcereload    Force reload of the cache
     * @return  string                      Label
     */
    function LibSource($id,$mode=0,$forcereload=0)
    {
        global $langs;

        if (!($id > 0))
            return '';

        $langs->load("requestmanager@requestmanager");

        if (empty(self::$source_list) || $forcereload) {
            dol_include_once('/advancedictionaries/class/dictionary.class.php');
            $dictionary = Dictionary::getDictionary($this->db, 'requestmanager', 'requestmanagersource');
            $dictionary->fetch_lines(1, array(), array('label' => 'ASC'));
            self::$source_list = $dictionary->lines;
        }

        if (!isset(self::$source_list[$id])) {
            return $langs->trans('RequestManagerErrorNotFound');
        }

        $sourceInfos = self::$source_list[$id];

        if ($mode == 0) return $sourceInfos->fields['label'];
        if ($mode == 1) return $sourceInfos->fields['code'];
        if ($mode == 2) return $sourceInfos->fields['code'] . ' - ' . $sourceInfos->fields['label'];
    }

    /**
     *  Return label of urgency
     *
     * @param   int         $mode       0=libelle, 1=code, 2=code - libelle
     * @return  string                  Label
     */
    function getLibUrgency($mode=0)
    {
        return $this->LibUrgency($this->fk_urgency, $mode);
    }

    /**
     *  Return label of urgency provides
     *
     * @param   int         $id             Id
     * @param   int         $mode           0=libelle, 1=code, 2=code - libelle
     * @param   int         $forcereload    Force reload of the cache
     * @return  string                      Label
     */
    function LibUrgency($id,$mode=0,$forcereload=0)
    {
        global $langs;

        if (!($id > 0))
            return '';

        $langs->load("requestmanager@requestmanager");

        if (empty(self::$urgency_list) || $forcereload) {
            dol_include_once('/advancedictionaries/class/dictionary.class.php');
            $dictionary = Dictionary::getDictionary($this->db, 'requestmanager', 'requestmanagerurgency');
            $dictionary->fetch_lines(1, array(), array('label' => 'ASC'));
            self::$urgency_list = $dictionary->lines;
        }

        if (!isset(self::$urgency_list[$id])) {
            return $langs->trans('RequestManagerErrorNotFound');
        }

        $urgencyInfos = self::$urgency_list[$id];

        if ($mode == 0) return $urgencyInfos->fields['label'];
        if ($mode == 1) return $urgencyInfos->fields['code'];
        if ($mode == 2) return $urgencyInfos->fields['code'] . ' - ' . $urgencyInfos->fields['label'];
    }

    /**
     *  Return color of urgency
     *
     * @return  string                  Color (HTML format: #00000000)
     */
    function getColorUrgency()
    {
        return $this->ColorUrgency($this->fk_urgency);
    }

    /**
     *  Return color of urgency provides
     *
     * @param   int         $id             Id
     * @param   int         $forcereload    Force reload of the cache
     * @return  string                      Color (HTML format: #00000000)
     */
    function ColorUrgency($id, $forcereload=0)
    {
        global $langs;

        if (!($id > 0))
            return '';

        $langs->load("requestmanager@requestmanager");

        if (empty(self::$urgency_list) || $forcereload) {
            dol_include_once('/advancedictionaries/class/dictionary.class.php');
            $dictionary = Dictionary::getDictionary($this->db, 'requestmanager', 'requestmanagerurgency');
            $dictionary->fetch_lines(1, array(), array('label' => 'ASC'));
            self::$urgency_list = $dictionary->lines;
        }

        if (!isset(self::$urgency_list[$id])) {
            return '';
        }

        $urgencyInfos = self::$urgency_list[$id];

        return $urgencyInfos->fields['color'];
    }

    /**
     *  Return label of impact
     *
     * @param   int         $mode       0=libelle, 1=code, 2=code - libelle
     * @return  string                  Label
     */
    function getLibImpact($mode=0)
    {
        return $this->LibImpact($this->fk_impact, $mode);
    }

    /**
     *  Return label of impact provides
     *
     * @param   int         $id             Id
     * @param   int         $mode           0=libelle, 1=code, 2=code - libelle
     * @param   int         $forcereload    Force reload of the cache
     * @return  string                      Label
     */
    function LibImpact($id,$mode=0,$forcereload=0)
    {
        global $langs;

        if (!($id > 0))
            return '';

        $langs->load("requestmanager@requestmanager");

        if (empty(self::$impact_list) || $forcereload) {
            dol_include_once('/advancedictionaries/class/dictionary.class.php');
            $dictionary = Dictionary::getDictionary($this->db, 'requestmanager', 'requestmanagerimpact');
            $dictionary->fetch_lines(1, array(), array('label' => 'ASC'));
            self::$impact_list = $dictionary->lines;
        }

        if (!isset(self::$impact_list[$id])) {
            return $langs->trans('RequestManagerErrorNotFound');
        }

        $impactInfos = self::$impact_list[$id];

        if ($mode == 0) return $impactInfos->fields['label'];
        if ($mode == 1) return $impactInfos->fields['code'];
        if ($mode == 2) return $impactInfos->fields['code'] . ' - ' . $impactInfos->fields['label'];
    }

    /**
     *  Return label of priority
     *
     * @param   int         $mode       0=libelle, 1=code, 2=code - libelle
     * @return  string                  Label
     */
    function getLibPriority($mode=0)
    {
        return $this->LibPriority($this->fk_priority, $mode);
    }

    /**
     *  Return label of priority provides
     *
     * @param   int         $id             Id
     * @param   int         $mode           0=libelle, 1=code, 2=code - libelle
     * @param   int         $forcereload    Force reload of the cache
     * @return  string                      Label
     */
    function LibPriority($id,$mode=0,$forcereload=0)
    {
        global $langs;

        if (!($id > 0))
            return '';

        $langs->load("requestmanager@requestmanager");

        if (empty(self::$priority_list) || $forcereload) {
            dol_include_once('/advancedictionaries/class/dictionary.class.php');
            $dictionary = Dictionary::getDictionary($this->db, 'requestmanager', 'requestmanagerpriority');
            $dictionary->fetch_lines(1, array(), array('label' => 'ASC'));
            self::$priority_list = $dictionary->lines;
        }

        if (!isset(self::$priority_list[$id])) {
            return $langs->trans('RequestManagerErrorNotFound');
        }

        $priorityInfos = self::$priority_list[$id];

        if ($mode == 0) return $priorityInfos->fields['label'];
        if ($mode == 1) return $priorityInfos->fields['code'];
        if ($mode == 2) return $priorityInfos->fields['code'] . ' - ' . $priorityInfos->fields['label'];
    }

    /**
     *  Return label of reason for resolution
     *
     * @param   int         $mode       0=libelle, 1=code, 2=code - libelle
     * @return  string                  Label
     */
    function getLibReasonResolution($mode=0)
    {
        return $this->LibReasonResolution($this->fk_reason_resolution, $mode);
    }

    /**
     *  Return label of reason for resolution provides
     *
     * @param   int         $id             Id
     * @param   int         $mode           0=libelle, 1=code, 2=code - libelle
     * @param   int         $forcereload    Force reload of the cache
     * @return  string                      Label
     */
    function LibReasonResolution($id,$mode=0,$forcereload=0)
    {
        global $langs;

        if (!($id > 0))
            return '';

        $langs->load("requestmanager@requestmanager");

        if (empty(self::$reason_resolution_list) || $forcereload) {
            dol_include_once('/advancedictionaries/class/dictionary.class.php');
            $dictionary = Dictionary::getDictionary($this->db, 'requestmanager', 'requestmanagerreasonresolution');
            $dictionary->fetch_lines(1, array(), array('label' => 'ASC'));
            self::$reason_resolution_list = $dictionary->lines;
        }

        if (!isset(self::$reason_resolution_list[$id])) {
            return $langs->trans('RequestManagerErrorNotFound');
        }

        $reasonInfos = self::$reason_resolution_list[$id];

        if ($mode == 0) return $reasonInfos->fields['label'];
        if ($mode == 1) return $reasonInfos->fields['code'];
        if ($mode == 2) return $reasonInfos->fields['code'] . ' - ' . $reasonInfos->fields['label'];
    }

    /**
     *  Return label of status
     *
     * @param   int         $mode           0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long
     * @param   int         $forcereload    Force reload of the cache
     * @param   int         $submode        Show status of all children request
     *                                      -1=Don't show, 0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Picto + Libelle court + Status type picto
     * @param   int         $return_line    0=Return line equal "\n", 1=Return line equal "<br>", 2=No return line but a separator
     * @param   string      $separator      Separator between each status
     * @return  string                      Libelle
     */
    function getLibStatut($mode=0, $forcereload=0, $submode=-1, $return_line=1, $separator=" / ")
    {
        return $this->LibStatut($this->statut, $mode,$forcereload, $submode, $return_line, $separator);
    }

    /**
     *  Return label of status provides
     *
     * @param   int         $statut         Id statut
     * @param   int         $mode           0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Picto + Libelle court + Status type picto
     * @param   int         $forcereload    Force reload of the cache
     * @param   int         $submode        Show status of all children request
     *                                      -1=Don't show, 0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Picto + Libelle court + Status type picto
     * @param   int         $return_line    0=Return line equal "\n", 1=Return line equal "<br>", 2=No return line but a separator
     * @param   string      $separator      Separator between each status
     * @return  string                      Libelle du statut
     */
    function LibStatut($statut,$mode=0,$forcereload=0, $submode=-1, $return_line=1, $separator= " / ")
    {
        global $langs;

        if (!($statut > 0))
            return '';

        $langs->load("requestmanager@requestmanager");

        if (empty(self::$status_list) || $forcereload) {
            dol_include_once('/advancedictionaries/class/dictionary.class.php');
            $dictionary = Dictionary::getDictionary($this->db, 'requestmanager', 'requestmanagerstatus');
            $dictionary->fetch_lines(1, array(), array('type' => 'ASC', 'position' => 'ASC'));
            self::$status_list = $dictionary->lines;
        }

        if (!isset(self::$status_list[$statut])) {
            return $langs->trans('RequestManagerErrorStatusNotFound');
        }

		if (empty(self::$type_list) || $forcereload) {
            dol_include_once('/advancedictionaries/class/dictionary.class.php');
            $dictionary = Dictionary::getDictionary($this->db, 'requestmanager', 'RequestManagerRequestType');
            $dictionary->fetch_lines(1, array(), array('type' => 'ASC', 'position' => 'ASC'));
            self::$type_list = $dictionary->lines;
        }

        $statutInfos = self::$status_list[$statut];
		$requestTypeInfos = self::$type_list[$this->fk_type];

        $type = $statutInfos->fields['type'];
		$label_short = $label = $statutInfos->fields['label'];
        $picto = $statutInfos->fields['picto'];

        $statuttypepicto = '';
        $statuttypetext = '';
        if ($type == self::STATUS_TYPE_INITIAL) {
            $statuttypepicto = 'statut0';
            $statuttypetext = $langs->trans('RequestManagerTypeInitial');
        }
        if ($type == self::STATUS_TYPE_IN_PROGRESS) {
            $statuttypepicto = 'statut1';
            $statuttypetext = $langs->trans('RequestManagerTypeInProgress');
        }
        if ($type == self::STATUS_TYPE_RESOLVED) {
            $statuttypepicto = 'statut3';
            $statuttypetext = $langs->trans('RequestManagerTypeResolved');
        }
        if ($type == self::STATUS_TYPE_CLOSED) {
            $statuttypepicto = 'statut4';
            $statuttypetext = $langs->trans('RequestManagerTypeClosed');
        }
        if ($mode >= 7 && !empty($picto)) {
            $statuttypepicto = $picto;
        }
        if ($mode >= 9 && !empty($picto)) {
            $statuttypetext = $label;
        }

        $out = array();
        if ($mode == 0) $out[] = $label;
        if ($mode == 1) $out[] = $label_short;
        if ($mode == 2) $out[] = (!empty($picto) ? img_picto($label_short, $picto) . ' ' : '') . $label_short;
        if ($mode == 3) $out[] = img_picto($label. " (" .$requestTypeInfos->fields['label'].")", $statuttypepicto);
        if ($mode == 4) $out[] = (!empty($picto) ? img_picto($label, $picto) . ' ' : '') . $label;
        if ($mode == 5) $out[] = (!empty($picto) ? img_picto($label, $picto) . ' ' : '') . '<span class="hideonsmartphone">' . $label_short . ' </span>' . img_picto($langs->trans($statuttypetext) . " (" .$requestTypeInfos->fields['label'].")", $statuttypepicto);
        if ($mode == 6) $out[] = (!empty($picto) ? img_picto($label, $picto) . ' ' : '') . '<span class="hideonsmartphone">' . $label . ' </span>' . img_picto($langs->trans($statuttypetext), $statuttypepicto);
        if ($mode == 7) $out[] = img_picto($label, $statuttypepicto) . ' ' . '<span class="hideonsmartphone">' . $label_short . ' </span>';
        if ($mode == 8) $out[] = img_picto($label, $statuttypepicto) . ' ' . '<span class="hideonsmartphone">' . $label . ' </span>';
        if ($mode == 9) $out[] = img_picto($statuttypetext, $statuttypepicto) . ' ' . $label_short;
        if ($mode == 10) $out[] = img_picto($statuttypetext, $statuttypepicto) . ' ' . $label;
        if ($mode == 11) $out[] = img_picto($statuttypetext, $statuttypepicto) . ' ' . $statuttypetext;
        if ($mode == 12) $out[] = $statuttypetext;
        if ($mode == 13) $out[] = $label . ' ('.$statuttypetext.')';

        if ($submode >= 0) {
            $this->fetch_children_request(null, 1);
            if (count($this->children_request_list) > 0) {
                $to_print = array();
                foreach ($this->children_request_list as $child_request) {
                    $to_print[] = $child_request->getLibStatut($submode);
                }
                $out[] = implode($separator, $to_print);
            }
        }

        return implode($return_line == 1 ? '<br>' : ($return_line == 2 ? $separator : "\n"), $out);
    }

    /**
     *  Add action : Forced principal company choice
     *
     * @param   User    $user           User that modifies
     * @return  int                     <0 if KO, >0 if OK
     */
    function addActionForcedPrincipalCompany($user)
    {
        global $langs;

        $this->fetch_thirdparty();
        $this->fetch_thirdparty_benefactor();
        $title = $langs->trans('RequestManagerForcedPrincipalCompanyActionLabel', $this->ref);
        $msg = '';
        if (isset($this->oldcopy->socid) && $this->socid != $this->oldcopy->socid) {
            $this->oldcopy->fetch_thirdparty();
            $msg .= $langs->trans('RequestManagerThirdPartyPrincipalOld') . ' : ' . $this->oldcopy->thirdparty->getNomUrl(1) . '<br>';
        }
        $msg .= $langs->trans('RequestManagerThirdPartyPrincipal') . ' : ' . $this->thirdparty->getNomUrl(1) . '<br>';
        if (isset($this->oldcopy->socid_benefactor) && $this->socid_benefactor != $this->oldcopy->socid_benefactor) {
            $this->oldcopy->fetch_thirdparty_benefactor();
            $msg .= $langs->trans('RequestManagerThirdPartyBenefactorOld') . ' : ' . $this->oldcopy->thirdparty_benefactor->getNomUrl(1) . '<br>';
        }
        $msg .= $langs->trans('RequestManagerThirdPartyBenefactor') . ' : ' . $this->thirdparty_benefactor->getNomUrl(1);
        $msg .= '<br><br>' . $langs->transnoentities("Author") . ': ' . $user->login;

        $now = dol_now();
        // Insertion action
        require_once(DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php');
        $actioncomm = new ActionComm($this->db);
        $actioncomm->type_code = 'AC_RM_FPC';
        $actioncomm->label = $title;
        $actioncomm->note = $msg;
        $actioncomm->datep = $now;
        $actioncomm->datef = $now;
        $actioncomm->durationp = 0;
        $actioncomm->punctual = 1;
        $actioncomm->percentage = -1; // Not applicable
        $actioncomm->contactid = 0;
        $actioncomm->socid = $this->socid;
        $actioncomm->author = $user; // User saving action
        // $actioncomm->usertodo = $user; // User affected to action
        $actioncomm->userdone = $user; // User doing action
        $actioncomm->fk_element = $this->id;
        $actioncomm->elementtype = $this->element;
        $actioncomm->userownerid = $user->id;

        $result = $actioncomm->create($user); // User qui saisit l'action
        if ($result < 0) {
            $this->error = $actioncomm->error;
            $this->errors = $actioncomm->errors;
        }

        return $result;
    }
    /**
     *  Add action : Forced created out of time
     *
     * @param   User    $user           User that modifies
     * @return  int                     <0 if KO, >0 if OK
     */
    function addActionForcedCreatedOutOfTime($user)
    {
        global $langs;

        $title = $langs->trans('RequestManagerForcedCreatedOutOfTimeActionLabel', $this->ref);
        $msg = $langs->trans('RequestManagerForcedCreatedOutOfTimeActionLabel', $this->ref);
        $msg .= '<br><br>' . $langs->transnoentities("Author") . ': ' . $user->login;

        $now = dol_now();
        // Insertion action
        require_once(DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php');
        $actioncomm = new ActionComm($this->db);
        $actioncomm->type_code = 'AC_RM_FCOOT';
        $actioncomm->label = $title;
        $actioncomm->note = $msg;
        $actioncomm->datep = $now;
        $actioncomm->datef = $now;
        $actioncomm->durationp = 0;
        $actioncomm->punctual = 1;
        $actioncomm->percentage = -1; // Not applicable
        $actioncomm->contactid = 0;
        $actioncomm->socid = $this->socid;
        $actioncomm->author = $user; // User saving action
        // $actioncomm->usertodo = $user; // User affected to action
        $actioncomm->userdone = $user; // User doing action
        $actioncomm->fk_element = $this->id;
        $actioncomm->elementtype = $this->element;
        $actioncomm->userownerid = $user->id;

        $result = $actioncomm->create($user); // User qui saisit l'action
        if ($result < 0) {
            $this->error = $actioncomm->error;
            $this->errors = $actioncomm->errors;
        }

        return $result;
    }

    /**
     *  Add all contract of the equipment
     *
     * @param   int         $equipment_id       Equipment ID
     * @return  int                             <0 if KO, >0 if OK
     */
    function addContractsOfEquipment($equipment_id)
    {
        $sql = "SELECT DISTINCT IF(sourcetype = 'equipement', fk_target, fk_source) AS fk_contrat FROM " . MAIN_DB_PREFIX . "element_element".
            " WHERE (sourcetype = 'equipement' AND fk_source = ".$equipment_id." AND targettype = 'contrat')" .
            " OR (sourcetype = 'contrat' AND targettype = 'equipement' AND fk_target = ".$equipment_id.")";

        $resql = $this->db->query($sql);
        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                if ($obj->fk_contrat > 0) {
                    $ret = $this->setContract($obj->fk_contrat);
                    if (!$ret && $this->db->lasterrno() != 'DB_ERROR_RECORD_ALREADY_EXISTS') {
                        $this->errors[] = $this->db->lasterror();
                        return -1;
                    }
                }
            }
        }

        return 1;
    }

    /**
     *  Return a link on thirdparty (with picto)
     *
     * @param   int         $withpicto                  Add picto into link (0=No picto, 1=Include picto with link, 2=Picto only)
     * @param   string      $option                     Target of link ('', 'customer', 'prospect', 'supplier', 'project')
     * @param   int         $maxlen                     Max length of name
     * @param   int         $notooltip                  1=Disable tooltip
     * @param   int         $save_lastsearch_value      -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
     * @return  string                                  String with URL
     */
    function getNomUrl($withpicto=0, $option='', $maxlen=0, $notooltip=0, $save_lastsearch_value=-1)
    {
        global $langs, $conf, $user;

        if (!empty($conf->dol_no_mouse_hover)) $notooltip = 1;   // Force disable tooltips

        $result = '';
        $label = '';
        $url = '';

        if ($user->rights->requestmanager->lire) {
            $this->fetch_thirdparty();
            $label = '<u>' . $langs->trans("RequestManagerShowRequest") . '</u>';
            $label .= '<br><b>' . $langs->trans('Ref') . ':</b> ' . $this->ref;
            $label .= '<br><b>' . $langs->trans('RequestManagerExternalReference') . ':</b> ' . $this->ref_ext;
            $label .= '<br><b>' . $langs->trans('RequestManagerRequestType') . ':</b> ' . $this->getLibType();
            $label .= '<br><b>' . $langs->trans('RequestManagerThirdParty') . ':</b> ' . $this->thirdparty->getFullName($langs);
            $label .= '<br><b>' . $langs->trans('RequestManagerLabel') . ':</b> ' . $this->label;
            if ($option != 'nolink') {
                $url = dol_buildpath('/requestmanager/card.php', 2) . '?id=' . $this->id;
            }

            if ($option != 'nolink') {
                // Add param to save lastsearch_values or not
                $add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
                if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) $add_save_lastsearch_values = 1;
                if ($add_save_lastsearch_values) $url .= '&save_lastsearch_values=1';
            }
        }

        $linkclose = '';
        if (empty($notooltip) && $user->rights->requestmanager->lire) {
            if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
                $label = $langs->trans("RequestManagerShowRequest");
                $linkclose .= ' alt="' . dol_escape_htmltag($label, 1) . '"';
            }
            $linkclose .= ' title="' . dol_escape_htmltag($label, 1) . '"';
            $linkclose .= ' class="classfortooltip"';
        }

        $linkstart = '<a href="' . $url . '"';
        $linkstart .= $linkclose . '>';
        $linkend = '</a>';

        if ($withpicto) $result.=($linkstart.img_object(($notooltip?'':$label), 'requestmanager@requestmanager', ($notooltip?'':'class="classfortooltip valigntextbottom"'), 0, 0, $notooltip?0:1).$linkend);
        if ($withpicto && $withpicto != 2) $result.=' ';
        if ($withpicto != 2) $result.=$linkstart.($maxlen?dol_trunc($this->ref,$maxlen):$this->ref).$linkend;

        if (preg_match('/^parent_path(.*)/i', $option, $matches) && $this->fk_parent > 0) {
            if (preg_match('/parent_path_stop_parent_(\d+)/i', $option, $matches)) {
                $stop_parent_id = $matches[1];
            }
            if (!isset($stop_parent_id) || $stop_parent_id != $this->parent->id) {
                $this->fetch_parent();
                $result = $this->parent->getNomUrl($withpicto, $option, $maxlen, $notooltip, $save_lastsearch_value) . ' >> ' . $result;
            }
        }

        return $result;
    }


    /**
     * Count all assigned requests for connected user
     *
     * @param   array   $statusTypeList         Liste des types de statut
     * @return  int     Nb assigned requests
     */
    public function countMyAssignedRequests($statusTypeList = array())
    {
        global $user;

        require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';

        $nb = 0;

        // get all groups for user
        $userGroup = new UserGroup($this->db);
        $userGroupList = $userGroup->listGroupsForUser($user->id);

        // count all assigned requests for user
        $sql = "SELECT COUNT(DISTINCT rm.rowid) as nb";
        $sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element . " as rm";
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'requestmanager_assigned_user as rmau ON rmau.fk_requestmanager = rm.rowid';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'requestmanager_assigned_usergroup as rmaug ON rmaug.fk_requestmanager = rm.rowid';
        if (count($statusTypeList) > 0) {
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_requestmanager_status as crmst ON crmst.rowid = rm.fk_status";
        }
        $sql .= " WHERE rm.entity IN (" . getEntity('requestmanager') . ")";
        if (count($statusTypeList) > 0) {
            $sql .= " AND crmst.type IN (" . implode(',', $statusTypeList) . ")";
        }
        $sql .= "   AND (rmau.fk_user = " . $user->id;
        if (count($userGroupList) > 0) {
            $sql .= "   OR rmaug.fk_usergroup IN (" . implode(',', array_keys($userGroupList)) . ")";
        }
        $sql .= "   )";

        $resql = $this->db->query($sql);
        if ($resql) {
            if ($obj = $this->db->fetch_object($resql)) {
                $nb = intval($obj->nb);
            }
        }

        return $nb;
    }


    /**
     * Determine if lists to follow has been modified since last view
     *
     * @param   string      $lastViewDate     Date of last view
     * @return  bool        TRUE If modified, else FALSE
     */
    public function isListsFollowModified($lastViewDate)
    {
        global $user;

        require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';

        $isModified = FALSE;

        // get all groups for user
        $userGroup = new UserGroup($this->db);
        $userGroupList = $userGroup->listGroupsForUser($user->id);

        // different filters for all lists
        $sqlFilterInProgress            = 'crmst.type IN (' . self::STATUS_TYPE_INITIAL . ', ' . self::STATUS_TYPE_IN_PROGRESS . ')';
        $sqlFilterInFuture              = 'rm.datec IS NOT NULL AND rm.datec > NOW()';
        $sqlFilterInProgressOrFuture    = '((' . $sqlFilterInProgress . ') OR (' . $sqlFilterInFuture . '))';
        $sqlFilterAssignedToMeOrMyGroup = '(rmau.fk_user = ' . $user->id;
        if (count($userGroupList) > 0) {
            $sqlFilterAssignedToMeOrMyGroup .= ' OR rmaug.fk_usergroup IN (' . implode(',', array_keys($userGroupList)) . ')';
        }
        $sqlFilterAssignedToMeOrMyGroup .= ')';
        $sqlFilterActionCommAssignedToMe = 'ac.fk_user_action = ' . $user->id;

        $sql  = 'SELECT';
        $sql .= ' rm.rowid';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'requestmanager as rm';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'requestmanager_assigned_user as rmau ON rmau.fk_requestmanager = rm.rowid';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'requestmanager_assigned_usergroup as rmaug ON rmaug.fk_requestmanager = rm.rowid';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'c_requestmanager_status as crmst on (crmst.rowid = rm.fk_status)';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'actioncomm as ac ON ac.elementtype="requestmanager" AND ac.fk_element=rm.rowid';
        $sql .= ' WHERE rm.entity IN (' . getEntity('requestmanager') . ')';
        $sql .= ' AND (';
        $sql .= ' (' . $sqlFilterInProgressOrFuture . ' AND ' . $sqlFilterAssignedToMeOrMyGroup . ')';
        $sql .= ' OR (' . $sqlFilterInProgress . ' AND ' . $sqlFilterActionCommAssignedToMe . ')';
        $sql .= ')';
        if ($lastViewDate) {
            $sql .= " AND rm.tms > '" . $this->db->idate($lastViewDate) . "'";
            $sql .= " AND rm.fk_user_modif != " . $user->id;
        }
        $sql .= " GROUP BY rm.rowid";
        $sql .= ' ORDER BY rm.tms DESC';

        $resql = $this->db->query($sql);
        if (!$resql) {
            dol_syslog(__METHOD__, LOG_ERR);
        } else {
            $num = $this->db->num_rows($resql);
            if ($num > 0) {
                $isModified = TRUE;
            }
        }

        return $isModified;
    }


    /**
     *  Load all dictionary lines from knwoladge base dictionary
     *
     * @return	array       Dictionary lines
     */
    public function fetchAllDictionaryLinesForKnowledgeBase()
    {
        $lines = array();

        dol_include_once('/advancedictionaries/class/dictionary.class.php');

        $dictionary = Dictionary::getDictionary($this->db, 'requestmanager', 'requestmanagerknowledgebase');

        $filters = array();
        $filters['request_type'] = array($this->fk_type);
        $resultLines = $dictionary->fetch_lines(1, $filters, array('position' => 'ASC'), 0, 0, false, true);

        if (is_array($resultLines)) {
            $lines = $resultLines;
        }

        return $lines;
    }

    /**
     * Load all ordered dictionary lines with fields from knowledge base dictionary
     *
     * @param   array   $orderByList    Order by (ex : array('nb_categorie' => SORT_DESC, 'position' => SORT_ASC))
     * @return  array   Ordered list lines with fields of this dictionary
     */
    public function fetchAllDictionaryLinesForKnowledgeBaseAndOrderBy($orderByList = array())
    {
        $knowledgeBaseOrderedList = array();

        // load dictionary lines of the type of request (order by position)
        $knowledgeBaseLines = $this->fetchAllDictionaryLinesForKnowledgeBase();

        if (count($knowledgeBaseLines) > 0) {
            $countCategories = FALSE;
            if (key_exists('nb_categorie', $orderByList)) {
                $countCategories = TRUE;
                // load categories id list in the request
                $categorieRequestIdList = $this->loadCategorieList('id');
            }

            foreach ($knowledgeBaseLines as $knowledgeBaseLine) {
                $nbCategorieRequestInKnowledgeBase = 0;
                if ($countCategories === TRUE) {
                    // count categories of the request that are in dictionary lines
                    $knowledgeBaseCategorieIdList = explode(',', $knowledgeBaseLine->fields['categorie']);
                    foreach ($categorieRequestIdList as $categorieId) {
                        if (in_array($categorieId, $knowledgeBaseCategorieIdList)) {
                            $nbCategorieRequestInKnowledgeBase++;
                        }
                    }
                }

                $knowledgeBaseOrderedList[] = array('id' => $knowledgeBaseLine->id, 'code' => $knowledgeBaseLine->fields['code'], 'title' => $knowledgeBaseLine->fields['title'], 'position' => $knowledgeBaseLine->fields['position'], 'description' => $knowledgeBaseLine->fields['description'], 'nb_categorie' => $nbCategorieRequestInKnowledgeBase);
            }

            if (count($orderByList) > 0) {
                $eval = 'array_multisort(';
                foreach ($orderByList as $field => $orderBy) {
                    $eval .= 'array_column($knowledgeBaseOrderedList, "' . $field . '"), ' . $orderBy . ', ';
                }
                $eval .= '$knowledgeBaseOrderedList);';
                eval($eval);
            }
        }

        return $knowledgeBaseOrderedList;
    }


    /**
     * Load categories list
     *
     * @param   string  $mode   [='object'] Get array of fetched category instances, 'id'=Get array of category ids, 'label'=Get array of category labels
     * @return  array   List of categories
     */
    public function loadCategorieList($mode='object')
    {
        $categorieList = array();

        dol_include_once('/requestmanager/class/categorierequestmanager.class.php');

        $categorieRequestManager = new CategorieRequestManager($this->db);

        $resultList = $categorieRequestManager->containing($this->id, CategorieRequestManager::TYPE_REQUESTMANAGER, $mode);

        if (is_array($resultList)) {
            $categorieList = $resultList;
        }

        return $categorieList;
    }


    /**
     * Sets object to supplied categories.
     *
     * Deletes object from existing categories not supplied.
     * Adds it to non existing supplied categories.
     * Existing categories are left untouch.
     *
     * @param int[]|int $categories Category or categories IDs
     * @return  int     <0 if KO, >0 if OK
     */
    public function setCategories($categories)
    {
        $result = 1;

        dol_include_once('/requestmanager/class/categorierequestmanager.class.php');

        // Handle single category
        if (! is_array($categories)) {
            $categories = array($categories);
        }

        // Get current categories
        $categorie = new CategorieRequestManager($this->db);
        $existing = $categorie->containing($this->id, CategorieRequestManager::TYPE_REQUESTMANAGER, 'id');

        // Diff
        if (is_array($existing)) {
            $to_del = array_diff($existing, $categories);
            $to_add = array_diff($categories, $existing);
        } else {
            $to_del = array(); // Nothing to delete
            $to_add = $categories;
        }

        // Process
        foreach($to_del as $del) {
            if ($categorie->fetch($del) > 0) {
                $result = $categorie->del_type($this, CategorieRequestManager::TYPE_REQUESTMANAGER);
                if ($result < 0) {
                    $this->error = $categorie->error;
                    break;
                }
            }
        }
        foreach ($to_add as $add) {
            if ($categorie->fetch($add) > 0) {
                $result = $categorie->add_type($this, CategorieRequestManager::TYPE_REQUESTMANAGER);

                if ($result < 0) {
                    $this->error = $categorie->error;
                    break;
                }
            }
        }

        return $result;
    }


    /**
     * Delete contact by id and type
     *
     * @param   int     $fkSocpeople            Id of soc people
     * @param   int     $idContactType          Id contact type
     * @param	int		$notrigger		        Disable all triggers
     * @return  int     <0 if KO, >0 if OK
     */
    private function _deleteContactByFkSocpeopleAndIdContactType($fkSocpeople, $idContactType, $notrigger = 0)
    {
        global $user;

        $this->db->begin();

        $sql   = "DELETE ec.* FROM " . MAIN_DB_PREFIX . "element_contact as ec";
        $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "c_type_contact as ctc ON ctc.rowid = ec.fk_c_type_contact";
        $sql .= " WHERE ec.element_id = " . $this->id;
        $sql .= " AND ctc.code = '" . $this->db->escape(self::getContactTypeCodeById($idContactType)) . "'";
        $sql .= " AND fk_socpeople = " . $fkSocpeople;

        dol_syslog(get_class($this)."::_deleteContactByFkSocpeopleAndIdContactType", LOG_DEBUG);
        if ($this->db->query($sql)) {
            if (!$notrigger) {
                $result = $this->call_trigger(strtoupper($this->element).'_DELETE_CONTACT', $user);
                if ($result < 0) {
                    $this->db->rollback();
                    return -1;
                }
            }

            $this->db->commit();
            return 1;
        } else {
            $this->error = $this->db->lasterror();
            $this->db->rollback();
            return -1;
        }
    }


    /**
     * Action to add contact
     *
     * @param   int         $idContactType      Id contact type
     * @return  void        Action execute
     */
    public function add_contact_action($idContactType)
    {
        global $langs;

        $contactTypeCodeHtml = self::getContactTypeCodeHtmlNameById($idContactType);
        $fkSocpeople = intval(GETPOST($contactTypeCodeHtml . '_fk_socpeople' , 'int'));

        if ($fkSocpeople <= 0) {
            $this->error = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Contact"));
            setEventMessages($this->error, $this->errors, 'errors');
        } else {
            $result = $this->add_contact($fkSocpeople, self::getContactTypeCodeById($idContactType));
            if ($result < 0) {
                setEventMessages($this->error, $this->errors, 'errors');
            } else {
                header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $this->id);
                exit();
            }
        }
    }


    /**
     * Action to delete contact
     *
     * @param   int         $idContactType      Id contact type
     * @return  void        Action execute
     */
    public function del_contact_action($idContactType)
    {
        $fkSocpeople = intval(GETPOST('fk_socpeople' , 'int'));

        $result = $this->_deleteContactByFkSocpeopleAndIdContactType($fkSocpeople, $idContactType);
        if ($result < 0) {
            setEventMessages($this->error, $this->errors, 'errors');
        } else {
            header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $this->id);
            exit();
        }
    }

    /**
     * Show contact list
     *
     * @param   int         $idContactType      Id contact type
     * @return  void        Print contact list
     */
    public function show_contact_list($idContactType)
    {
        global $langs, $user;

        require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

        // authorised action list
        $actionList = array();
        if ($user->rights->requestmanager->creer && $this->statut_type != self::STATUS_TYPE_CLOSED && $this->statut_type != self::STATUS_TYPE_RESOLVED) {
            $actionList[] = 'delete';
        }

        if ($idContactType == self::CONTACT_TYPE_ID_REQUEST) {
            $this->fetch_requesters(1);
            $contactList = $this->requester_list;
        } else if ($idContactType == self::CONTACT_TYPE_ID_WATCHER) {
            $this->fetch_watchers(1);
            $contactList = $this->watcher_list;
        }

        if (count($contactList) > 0) {
            print '<table class="nobordernopadding" width="100%">';
            print '<tr class="liste_titre">';
            print '<td align="left">' . $langs->trans("Company") . '</td>';
            print '<td align="left">' . $langs->trans("Name") . '</td>';
            print '<td align="center">' . $langs->trans("Phone") . '</td>';
            if (count($actionList) > 0) {
                print '<td></td>';
            }
            print '</tr>';

            foreach ($contactList as $contact) {
                print '<tr>';

                // company name
                $societe = new Societe($this->db);
                $societe->fetch($contact->socid);
                print '<td class="titlefield">' . $societe->getNomUrl(1) . '</td>';

                // contact name
                print '<td class="titlefield">' . $contact->getNomUrl(1) . '</td>';

                // contact phone
                print '<td align="center">';
                $phones = array();
                if (!empty($contact->office_phone)) $phones[] = dol_print_phone($contact->office_phone, $contact->country_code, $contact->contact_id, $contact->socid, 'AC_TEL', '&nbsp;', 'phone', $langs->trans("PhonePro"));
                if (!empty($contact->user_mobile)) $phones[] = dol_print_phone($contact->user_mobile, $contact->country_code, $contact->contact_id, $contact->socid, 'AC_TEL', '&nbsp;', 'mobile', $langs->trans("PhoneMobile"));
                if (!empty($contact->phone_pro)) $phones[] = dol_print_phone($contact->phone_pro, $contact->country_code, $contact->id, $contact->socid, 'AC_TEL', '&nbsp;', 'phone', $langs->trans("PhonePro"));
                if (!empty($contact->phone_mobile)) $phones[] = dol_print_phone($contact->phone_mobile, $contact->country_code, $contact->id, $contact->socid, 'AC_TEL', '&nbsp;', 'mobile', $langs->trans("PhoneMobile"));
                if (!empty($contact->phone_perso)) $phones[] = dol_print_phone($contact->phone_perso, $contact->country_code, $contact->id, $contact->socid, 'AC_TEL', '&nbsp;', 'phone', $langs->trans("PhonePerso"));
                print implode(', ', $phones);
                print '</td>';

                // contact actions
                if (count($actionList) > 0) {
                    print '<td align="right">';
                    foreach ($actionList as $actionName) {
                        if ($actionName === 'delete') {
                            print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $this->id . '&action=del_contact&del_contact_type_id=' . $idContactType . '&fk_socpeople=' . $contact->id . '">' . img_delete($langs->transnoentitiesnoconv("RemoveContact")) . '</a>';
                        }
                    }
                    print '</td>';
                }

                print '</tr>';
            }
            print '</table>';
        }
    }


    /**
     * Get user list to notify
     *
     * @param   int         $withObject     Load also the object
     * @param   string      $sourceFilter   [=''] for all, internal for users, external for soc people
     * @return  array       Contact list
     */
    public function getUserToNotifyList($withObject = 0, $sourceFilter = '')
    {
        global $user;

        $contactList = array();

        if ($sourceFilter == '' || $sourceFilter == 'internal') {
            if ($this->notify_assigned_by_email == TRUE) {
                // assigned users
                if ($this->assigned_user_id > 0) {
                    if ($user->id != $this->assigned_user_id) {
                        if ($withObject == 1) {
                            $userstatic = new User($this->db);
                            $userstatic->fetch($this->assigned_user_id);
                            $contactList['u' . $this->assigned_user_id] = $userstatic;
                        } else {
                            $contactList['u' . $this->assigned_user_id] = $this->assigned_user_id;
                        }
                    }
                } else if ($this->assigned_usergroup_id > 0) {
                    // assigned group
                    $groupstatic = new UserGroup($this->db);
                    $groupstatic->fetch($this->assigned_usergroup_id);
                    $userGroupList = $groupstatic->listUsersForGroup();
                    foreach ($userGroupList as $userGroup) {
                        if ($withObject == 1) {
                            $contactList['u' . $userGroup->id] = $userGroup;
                        } else {
                            $contactList['u' . $userGroup->id] = $userGroup->id;
                        }
                    }
                }
            }
        }

        return $contactList;
    }


    /**
     * Get contact requesters list to notify
     *
     * @param   int     $withObject     Load also the object
     * @param   string  $sourceFilter   [=''] for all, internal for users, external for soc people
     * @return  array   Contact list
     */
    public function getContactRequestersToNotifyList($withObject = 0, $sourceFilter = '')
    {
        $contactList = array();

        // contact requesters
        if ($this->notify_requester_by_email == TRUE) {
            $this->fetch_requesters($withObject, $sourceFilter);
            $contactList = array_merge($contactList, $this->requester_list);
        }

        return $contactList;
    }


    /**
     * Get contact watchers list to notify
     *
     * @param   int     $withObject     Load also the object
     * @param   string  $sourceFilter   [=''] for all, internal for users, external for soc people
     * @return  array   Contact list
     */
    public function getContactWatchersToNotifyList($withObject = 0, $sourceFilter = '')
    {
        $contactList = array();

        // contact watchers
        if ($this->notify_watcher_by_email == TRUE) {
            $this->fetch_watchers($withObject, $sourceFilter);
            $contactList = array_merge($contactList, $this->watcher_list);
        }

        return $contactList;
    }


    /**
     * Get contact list to notify (all : assigned, requesters and watchers)
     *
     * @param   int     $withObject     Load also the object
     * @param   string  $sourceFilter   [=''] for all, internal for users, external for soc people
     * @return  array   Contact list
     */
    public function getContactToNotifyList($withObject = 0, $sourceFilter = '')
    {
        $contactList = array();

        // assigned users (or group)
        if ($sourceFilter == '' || $sourceFilter == 'internal') {
            $contactList = $this->getUserToNotifyList($withObject, $sourceFilter);
        }

        // contact requesters
        $contactList = array_merge($contactList, $this->getContactRequestersToNotifyList($withObject, $sourceFilter));

        // contact watchers
        $contactList = array_merge($contactList, $this->getContactWatchersToNotifyList($withObject, $sourceFilter));

        return $contactList;
    }


    /**
     * Make contact email unique list
     *
     * @param   array   $contactList        Contact list
     * @param   array   $notInEmailList     Not in email list
     * @return  array   Contact unique email list
     */
    public static function makeEmailUniqueListFromContactList($contactList, $notInEmailList = array())
    {
        $emailUniqueList = array();

        // unique email list
        foreach ($contactList as $contact) {
            if ($contact->email && !in_array($contact->email, $emailUniqueList) && !in_array($contact->email, $notInEmailList)) {
                if ($contact->email) {
                    $emailUniqueList[] = $contact->email;
                }
            }
        }

        return $emailUniqueList;
    }


    /**
     * Create new event in actioncomm for all type of messages
     *
     * @param   int     $typeCode               Code of message type (AC_RM_OUT, AC_RM_IN, etc)
     * @param   string  $label                  Label of event
     * @param   string  $note                   Note of event
     * @param   int     $fkKnowledgeBase        [=0] Id of knowledge base
     * @return  int     <0 if KO, >0 if OK (idAction)
     */
    private function _createActionComm($typeCode, $label, $note, $fkKnowledgeBase=0)
    {
        global $langs, $user;

        require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';

        $actionCom = new ActionComm($this->db);
        $actionCom->type_code = $typeCode;
        $actionCom->label = $label;
        $actionCom->note = $note;
        $actionCom->datep = dol_now();
        $actionCom->datef = dol_now();
        $actionCom->socid = $this->socid;
        $actionCom->fk_element = $this->id;
        $actionCom->elementtype = $this->element;
        $actionCom->userownerid = $user->id;
        $actionCom->percentage = -1;
        // assigned users
        /*
        $contactUserIdList = $this->getContactToNotifyList(0, 'internal');
        foreach($contactUserIdList as $contactUserId) {
            $actionCom->userassigned[$contactUserId] = array('id' => $contactUserId);
        }
        */

        $this->db->begin();
        $idActionComm = $actionCom->create($user);
        if ($idActionComm > 0) {
            if ($actionCom->error) {
                $langs->load("errors");
                $this->error = $actionCom->error;
                $this->errors = $actionCom->errors;
                dol_syslog(__METHOD__ . " Error create: " . $this->error, LOG_ERR);
                $this->db->rollback();
                return -1;
            }
        } else {
            $this->error = $actionCom->error;
            $this->errors = $actionCom->errors;
            dol_syslog(__METHOD__ . " Error create: " . $this->error, LOG_ERR);
            $this->db->rollback();
            return -1;
        }

        $this->db->commit();
        return $idActionComm;
    }


    /**
     * Create new event in actioncomm for all type of messages and notify users, requesters and watchers
     * @param   int     $actionCommTypeCode     Code of message type (AC_RM_OUT, AC_RM_IN, etc)
     * @param   string  $actionCommLabel        Label of event
     * @param   string  $actionCommNote         Note (description) of event
     * @param   int     $messageNotifyByMail    If send a mail to contacts requesters and watchers
     * @param   string  $messageSubject         Mail subject
     * @param   string  $messageBody            Mail content
     * @param   bool    $mailSubstitut          [=FALSE] not to subtitute mail variables, TRUE to substitute mail variables
     * @param   int     $fkKnowledgeBase        [=0] Id of knowledge base
     * @return  int     <0 if KO, >0 if OK
     */
    private function _createActionCommAndNotify($actionCommTypeCode, $actionCommLabel, $actionCommNote, $messageNotifyByMail, $messageSubject, $messageBody, $mailSubstitut=FALSE, $fkKnowledgeBase=0)
    {
        global $conf, $langs, $user;

        dol_include_once('/requestmanager/class/requestmanagermessage.class.php');
        dol_include_once('/requestmanager/class/requestmanagernotification.class.php');

        $error = 0;

        $langs->load('requestmanager@requestmanager');

        // make contact list to notify by mail
        $contactToNotifyByMailList   = array();
        $contactCcToNotifyByMailList = array();
        if ($messageNotifyByMail === 1) {
            $contactToNotifyByMailList = self::makeEmailUniqueListFromContactList($this->getContactRequestersToNotifyList(1));
            if (count($contactToNotifyByMailList) > 0) {
                $contactCcToNotifyByMailList = self::makeEmailUniqueListFromContactList($this->getContactWatchersToNotifyList(1), $contactToNotifyByMailList);
            } else {
                $contactToNotifyByMailList = self::makeEmailUniqueListFromContactList($this->getContactWatchersToNotifyList(1));
            }
        }

        // substitute mail variables
        if ($mailSubstitut === TRUE)
        {
            $mailTo          = implode(', ', $contactToNotifyByMailList);
            $mailCcTo        = implode(', ', $contactCcToNotifyByMailList);
            $substitut       = FormRequestManagerMessage::setAvailableSubstitKeyForMail(RequestManagerNotification::getSendFrom(), $mailTo, $messageSubject, $messageBody, $mailCcTo);
            $actionCommLabel = make_substitutions($actionCommLabel, $substitut);
            $actionCommNote  = make_substitutions($actionCommNote, $substitut);
        }

        // create new event
        $this->db->begin();
        $idActionComm = $this->_createActionComm($actionCommTypeCode, $actionCommLabel, $actionCommNote, $fkKnowledgeBase);
        if ($idActionComm < 0) {
            $error++;
        }

        if (!$error && $fkKnowledgeBase > 0) {
            // create a link between id of event and id of knowledge base
            $requestManagerMessage = new RequestManagerMessage($this->db);
            $requestManagerMessage->fk_actioncomm     = $idActionComm;
            $requestManagerMessage->fk_knowledge_base = $fkKnowledgeBase;
            $result = $requestManagerMessage->create($user);
            if ($result < 0) {
                $error++;
                $this->errors[] = $requestManagerMessage->errorsToString();
            }
        }

        if (!$error) {
            // user or group assigned to notify (save in database)
            $requestManagerNotification = new RequestManagerNotification($this->db);
            $contactToNotifyList = $this->getUserToNotifyList(1);
            $requestManagerNotification->contactList = $contactToNotifyList;

            // if we have at least one user to notify
            if (count($requestManagerNotification->contactList) > 0) {
                if (!empty($conf->global->REQUESTMANAGER_NOTIFICATION_USERS_IN_DB)) {
                    // notify the assigned user if different of user (save in database)
                    $result = $requestManagerNotification->notify($idActionComm);
                    if ($result < 0) {
                        $error++;
                        $this->errors = $requestManagerNotification->errors;
                    }
                }

                if (!empty($conf->global->REQUESTMANAGER_NOTIFICATION_BY_MAIL)) {
                    // send a mail
                    $requestManagerNotification->contactList = self::makeEmailUniqueListFromContactList($contactToNotifyList);
                    $result = $requestManagerNotification->notifyByMail($messageSubject, $messageBody, 1);
                    if ($result < 0) {
                        $error++;
                        $this->errors = $requestManagerNotification->errors;
                    }
                }
            }

            // notify by mail
            if (!$error && count($contactToNotifyByMailList)>0) {
                // send to requesters (sendto) and watchers (copy carbone) to notify
                $requestManagerNotification->contactList   = $contactToNotifyByMailList;
                $requestManagerNotification->contactCcList = $contactCcToNotifyByMailList;
                $result = $requestManagerNotification->notifyByMail($messageSubject, $messageBody);
                if ($result < 0) {
                    $error++;
                    $this->errors[] = $requestManagerNotification->errors;
                }
            }
        }

        if ($error) {
            $this->db->rollback();
            return -1;
        }

        $this->db->commit();
        return 1;
    }


    /**
     * Find first template for email notification
     *
     * @param   string      $templateType   Template type
     * @return  array       Template
     */
    private function _findNotificationMessageTemplate($templateType)
    {
        global $langs;

        dol_include_once('/advancedictionaries/class/dictionary.class.php');

        $template = array();

        $dictionary = Dictionary::getDictionary($this->db, 'requestmanager', 'requestmanagermessagetemplate');
        $lines = $dictionary->fetch_lines(1, array('template_type' => array($templateType), 'request_type' => array($this->fk_type)), array('position' => 'ASC'), 0, 1, false, true);
        if ($lines < 0) {
            $this->error = $dictionary->errorsToString();
            $this->errors[] = $this->error;
            dol_syslog(__METHOD__ . " Error : No template [" . $templateType . "] for this type of request [" . $this->fk_type. "]", LOG_ERR);
        } else {
            if (count($lines) <= 0) {
                $this->error = $langs->trans("RequestManagerErrorNoTemplateLines");
                $this->errors[] = $this->error;
                dol_syslog(__METHOD__ . " Error : No template lines [" . $templateType . "] for this type of request [" . $this->fk_type. "]", LOG_ERR);
            } else {
                $template = current($lines)->fields;
            }
        }

        return $template;
    }


    /**
     * Substitute values in a template
     *
     * @param   string      $templateType   Template type
     * @return  array       Subsitued templated
     */
    private function _substituteNotificationMessageTemplate($templateType)
    {
        global $langs;

        dol_include_once('/requestmanager/class/html.formrequestmanagermessage.class.php');

        $substituteList = array();

        $template = $this->_findNotificationMessageTemplate($templateType);

        if (count($template) > 0) {
            if (isset($template['subject']) && isset($template['boby'])) {
                $formRequestManagerMessage = new FormRequestManagerMessage($this->db, $this);
                $formRequestManagerMessage->setSubstitFromObject($this);
                $substituteList['subject'] = make_substitutions($template['subject'], $formRequestManagerMessage->substit);
                $substituteList['boby']    = make_substitutions($template['boby'], $formRequestManagerMessage->substit);
            } else {
                $this->error = $langs->trans("RequestManagerErrorMissingFieldsInTemplate");
                $this->errors[] = $this->error;
                dol_syslog(__METHOD__ . " Error : Missing fields in this template", LOG_ERR);
            }
        }

        return $substituteList;
    }


    /**
     * Create new event in actioncomm for all type of messages and notify users, requesters and watchers from a message template
     *
     * @param   string      $templateType           Template type (ex : RequestManager::TEMPLATE_TYPE_NOTIFY_STATUS_MODIFIED)
     * @param   string      $actionCommTypeCode     Code of message type (AC_RM_OUT, AC_RM_IN, etc)
     * @return  int         <0 if KO, >0 if OK
     */
    public function createActionCommAndNotifyFromTemplateType($templateType, $actionCommTypeCode)
    {
        // get substitute values in input message template
        $substituteList = $this->_substituteNotificationMessageTemplate($templateType);
        if ($this->error) {
            return -1;
        }

        // create event and notify users and send mail to contacts requesters and watchers (if notified)
        $result = $this->_createActionCommAndNotify($actionCommTypeCode, $substituteList['subject'], $substituteList['boby'], 1, $substituteList['subject'], $substituteList['boby']);
        if ($result < 0) {
            return -1;
        }

        return 1;
    }


    /**
     * Create new event in actioncomm for all type of messages and notify users, requesters and watchers from a message template for notification and with a specific message for mail
     *
     * @param   string      $templateType           Template type (ex : RequestManager::TEMPLATE_TYPE_NOTIFY_STATUS_MODIFIED)
     * @param   string      $actionCommTypeCode     Code of message type (AC_RM_OUT, AC_RM_IN, etc)
     * @param   int         $messageNotifyByMail    If send a mail to contacts requesters and watchers
     * @param   string      $messageSubject         Mail subject
     * @param   string      $messageBody            Mail content
     * @param   int         $fkKnowledgeBase        [=0] Id of knowledge base
     * @return  int         <0 if KO, >0 if OK
     */
    public function createActionCommAndNotifyFromTemplateTypeWithMessage($templateType, $actionCommTypeCode, $messageNotifyByMail, $messageSubject, $messageBody, $fkKnowledgeBase=0)
    {
        // get substitute values in input message template
        $substituteList = $this->_substituteNotificationMessageTemplate($templateType);
        if ($this->error) {
            return -1;
        }

        // create event and notify users and send mail to contacts requesters and watchers (if notified)
        $result = $this->_createActionCommAndNotify($actionCommTypeCode, $substituteList['subject'], $substituteList['boby'], $messageNotifyByMail, $messageSubject, $messageBody, TRUE, $fkKnowledgeBase);
        if ($result < 0) {
            return -1;
        }

        return 1;
    }


    /**
     * Load object from SQL resource
     *
     * @param   Object  $obj    SQL object from resource
     * @return  int
     */
    private function _loadFromDbObject($obj)
    {
        $this->id                           = $obj->rowid;
        $this->ref                          = $obj->ref;
        $this->ref_ext                      = $obj->ref_ext;
        $this->socid                        = $obj->fk_soc;
        $this->label                        = $obj->label;
        $this->description                  = $obj->description;
        $this->fk_type                      = $obj->fk_type;
        $this->fk_category                  = $obj->fk_category;
        $this->fk_source                    = $obj->fk_source;
        $this->fk_urgency                   = $obj->fk_urgency;
        $this->fk_impact                    = $obj->fk_impact;
        $this->fk_priority                  = $obj->fk_priority;
        $this->notify_requester_by_email    = empty($obj->notify_requester_by_email) ? 0 : 1;
        $this->notify_watcher_by_email      = empty($obj->notify_watcher_by_email) ? 0 : 1;
        //$this->assigned_user_id             = $obj->fk_assigned_user;
        //$this->assigned_usergroup_id        = $obj->fk_assigned_usergroup;
        $this->notify_assigned_by_email     = empty($obj->notify_assigned_by_email) ? 0 : 1;
        $this->duration                     = $obj->duration;
        $this->date_deadline                = $this->db->jdate($obj->date_deadline);
        $this->date_resolved                = $this->db->jdate($obj->date_resolved);
        $this->date_cloture                 = $this->db->jdate($obj->date_closed);
        $this->user_resolved_id             = $obj->fk_user_resolved;
        $this->user_cloture_id              = $obj->fk_user_closed;
        $this->statut                       = $obj->fk_status;
        $this->entity                       = $obj->entity;
        $this->date_creation                = $this->db->jdate($obj->datec);
        $this->date_modification            = $this->db->jdate($obj->tms);
        $this->user_creation_id             = $obj->fk_user_author;
        $this->user_modification_id         = $obj->fk_user_modif;
    }

    /**
     * Load status type from SQL resource
     *
     * @param   Object  $obj    SQL object from resource
     * @return  int     <0 if KO, >0 if OK
     */
    private function _loadStatutTypeFromDbObject($obj)
    {
        global $langs;

        dol_include_once('/advancedictionaries/class/dictionary.class.php');
        $requestManagerStatusDictionaryLine = Dictionary::getDictionaryLine($this->db, 'requestmanager', 'requestmanagerstatus');
        $res = $requestManagerStatusDictionaryLine->fetch($obj->fk_status);
        if ($res == 0) {
            $this->errors[] = $langs->trans('RequestManagerErrorStatusNotFound');
            return -1;
        } elseif ($res < 0) {
            array_merge($this->errors, $requestManagerStatusDictionaryLine->errors);
            return -1;
        }

        $this->statut_type = $requestManagerStatusDictionaryLine->fields['type'];

        return 1;
    }


    /**
     * Prepare SQL request to find all for a company
     *
     * @param   int     $fkSoc              [=0] Id of company
     * @param   array   $statusTypeList         [=array()] List of status
     * @param   int     $categorieList      [=array()] List of categories
     * @param   int     $equipementId       [=0] Id of equipement
     * @return  string  SQL request
     */
    private function _sqlFindAllByFkSoc($fkSoc=0, $statusTypeList=array(), $categorieList=array(), $equipementId=0)
    {
        $sql  = $this->_sqlSelectAllFromTableElement();
        if (count($categorieList)) {
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'categorie_requestmanager as cr ON cr.fk_requestmanager = t.rowid';
        }
        if ($equipementId > 0) {
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'element_element as ee ON ee.fk_target = t.rowid AND ee.targettype = "' . $this->db->escape($this->element) . '" AND ee.sourcetype = "equipement"';
        }
        if (count($statusTypeList)) {
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'c_requestmanager_status as crms ON crms.rowid = t.fk_status';
        }
        $sql .= ' WHERE t.entity IN (' . getEntity($this->element) . ')';
        if ($fkSoc > 0) {
            $sql .= ' AND (';
            $sql .= ' t.fk_soc = ' . $fkSoc;
            $sql .= ' OR t.fk_soc_benefactor = ' . $fkSoc;
            $sql .= ' OR t.fk_soc_watcher = ' . $fkSoc;
            $sql .= ')';
        }
        if (count($statusTypeList)) {
            $sql .= ' AND crms.type IN (' . implode(',', $statusTypeList) . ')';
        }
        if (count($categorieList)) {
            $sql .= ' AND cr.fk_categorie IN (' . implode(',', $categorieList) . ')';
        }
        if ($equipementId > 0) {
            $sql .= ' AND ee.fk_source = ' . $equipementId;
        }

        return $sql;
    }


    /**
     * Prepare SQL request
     *
     * @return string   SQL request
     */
    private function _sqlSelectAllFromTableElement()
    {
        $sql = 'SELECT';
        $sql .= ' t.rowid,';
        $sql .= ' t.ref,';
        $sql .= ' t.ref_ext,';
        $sql .= ' t.fk_soc,';
        $sql .= ' t.label,';
        $sql .= ' t.description,';
        $sql .= ' t.fk_type,';
        $sql .= ' t.fk_category,';
        $sql .= ' t.fk_source,';
        $sql .= ' t.fk_urgency,';
        $sql .= ' t.fk_impact,';
        $sql .= ' t.fk_priority,';
        $sql .= ' t.notify_requester_by_email,';
        $sql .= ' t.notify_watcher_by_email,';
        //$sql .= ' t.fk_assigned_user,';
        //$sql .= ' t.fk_assigned_usergroup,';
        $sql .= ' t.notify_assigned_by_email,';
        $sql .= ' t.duration,';
        $sql .= ' t.date_deadline,';
        $sql .= ' t.date_resolved,';
        $sql .= ' t.date_closed,';
        $sql .= ' t.fk_user_resolved,';
        $sql .= ' t.fk_user_closed,';
        $sql .= ' t.fk_status,';
        $sql .= ' t.entity,';
        $sql .= ' t.datec,';
        $sql .= ' t.tms,';
        $sql .= ' t.fk_user_author,';
        $sql .= ' t.fk_user_modif';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . ' as t';

        return $sql;
    }


    /**
     * Load all filter for a company
     *
     * @param   int     $fkSoc              [=0] Id of company
     * @param   array   $statusTypeList         [=array()] List of status
     * @param   int     $categorieList      [=array()] List of categories
     * @param   int     $equipementId       [=0] Id of equipement
     * @return  int     <0 if KO, 0 if not found, >0 if OK
     */
    public function loadAllByFkSoc($fkSoc=0, $statusTypeList=array(), $categorieList=array(), $equipementId=0)
    {
        global $langs;

        $objectList = array();

        $this->errors = array();
        $langs->load("requestmanager@requestmanager");

        dol_syslog(__METHOD__ . " fkSoc=" . $fkSoc, LOG_DEBUG);

        $sql = $this->_sqlFindAllByFkSoc($fkSoc, $statusTypeList, $categorieList, $equipementId);

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
        } else {
            $numrows = $this->db->num_rows($resql);
            if ($numrows) {
                while ($obj = $this->db->fetch_object($resql)) {
                    $requestmanagerStatic = new self($this->db);

                    $res = $requestmanagerStatic->_loadStatutTypeFromDbObject($obj);
                    if ($res < 0)   return $objectList;
                    $requestmanagerStatic->_loadFromDbObject($obj);
                    //$requestmanagerStatic->fetch_requesters();
                    //$requestmanagerStatic->fetch_watchers();

                    $objectList[] = $requestmanagerStatic;
                }
            }
            $this->db->free($resql);
        }

        return $objectList;
    }


    /**
     * Link to actioncomm
     *
     * @param   int     $actionCommId       Id of actioncomm
     * @return  int     <0 if KO, >0 if OK
     */
    public function linkToActionComm($actionCommId)
    {
        global $user;

        // link to actioncomm
        $actionComm = new ActionComm($this->db);
        $actionComm->fetch($actionCommId);
        $actionComm->socid  = $this->socid_origin;
        $actionComm->fk_element  = $this->id;
        $actionComm->elementtype = $this->element;
        $result = $actionComm->update($user);

        if ($result < 0) {
            $this->errors[] = $actionComm->errorsToString();
            return -1;
        }

        return 1;
    }


    //
    // RequestManagerLine
    //

    /**
     * 	Create an array of order lines
     *
     * 	@return int		>0 if OK, <0 if KO
     */
    function getLinesArray()
    {
        return $this->fetch_lines();
    }


    /**
     *	Load array lines
     *
     *	@param		int		$only_product	[=0] Return only physical products
     *	@return		int						<0 if KO, >0 if OK
     */
    function fetch_lines($only_product=0)
    {
        $this->lines = array();

        $sql  = "SELECT";
        $sql .= " l.rowid, l.fk_requestmanager, l.fk_parent_line, l.fk_product, l.label as custom_label, l.description";
        $sql .= ", l.vat_src_code, l.tva_tx, l.localtax1_tx, l.localtax1_type, l.localtax2_tx, l.localtax2_type";
        $sql .= ", l.qty, l.remise_percent, l.fk_remise_except, l.subprice";
        $sql .= ", l.total_ht, l.total_tva, l.total_localtax1, l.total_localtax2, l.total_ttc";
        $sql .= ", l.product_type";
        $sql .= ", l.date_start, l.date_end";
        $sql .= ", l.info_bits, l.buy_price_ht as pa_ht, l.fk_product_fournisseur_price as fk_fournprice, l.special_code, l.rang, l.fk_unit";
        $sql .= ", l.fk_multicurrency, l.multicurrency_code, l.multicurrency_subprice, l.multicurrency_total_ht, l.multicurrency_total_tva, l.multicurrency_total_ttc";
        $sql .= ", p.ref as product_ref, p.description as product_desc, p.fk_product_type, p.label as product_label, p.tobatch as product_tobatch";
        $sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element_line . " as l";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product as p ON (p.rowid = l.fk_product)";
        $sql .= " WHERE l.fk_requestmanager = " . $this->id;
        if ($only_product) $sql .= " AND p.fk_product_type = 0";
        $sql .= " ORDER BY l.rang, l.rowid";

        dol_syslog(__METHOD__, LOG_DEBUG);
        $result = $this->db->query($sql);
        if ($result)
        {
            $num = $this->db->num_rows($result);

            $i = 0;
            while ($i < $num)
            {
                $objp = $this->db->fetch_object($result);

                $line = new RequestManagerLine($this->db);

                $line->id                      = $objp->rowid;
                $line->rowid                   = $objp->rowid;
                $line->fk_requestmanager       = $objp->fk_requestmanager;
                $line->fk_parent_line	       = $objp->fk_parent_line;
                $line->fk_product              = $objp->fk_product;
                $line->label                   = $objp->custom_label;
                $line->desc                    = $objp->description;
                $line->description             = $objp->description;		// Description line
                $line->vat_src_code            = $objp->vat_src_code;
                $line->tva_tx                  = $objp->tva_tx;
                $line->localtax1_tx            = $objp->localtax1_tx;
                $line->localtax1_type	       = $objp->localtax1_type;
                $line->localtax2_tx            = $objp->localtax2_tx;
                $line->localtax2_type	       = $objp->localtax2_type;
                $line->qty                     = $objp->qty;
                $line->remise_percent          = $objp->remise_percent;
                $line->fk_remise_except        = $objp->fk_remise_except;
                $line->subprice                = $objp->subprice;
                $line->total_ht                = $objp->total_ht;
                $line->total_tva               = $objp->total_tva;
                $line->total_localtax1         = $objp->total_localtax1;
                $line->total_localtax2         = $objp->total_localtax2;
                $line->total_ttc               = $objp->total_ttc;
                $line->product_type            = $objp->product_type;
                $line->date_start              = $this->db->jdate($objp->date_start);
                $line->date_end                = $this->db->jdate($objp->date_end);
                $line->info_bits               = $objp->info_bits;
                $line->fk_fournprice 	       = $objp->fk_fournprice; // fk_product_fournisseur_price
                $line->special_code		       = $objp->special_code;
                $line->rang                    = $objp->rang;
                $line->fk_unit                 = $objp->fk_unit;
                $marginInfos			       = getMarginInfos($objp->subprice, $objp->remise_percent, $objp->tva_tx, $objp->localtax1_tx, $objp->localtax2_tx, $line->fk_fournprice, $objp->pa_ht);
                $line->pa_ht 			       = $marginInfos[0];
                $line->marge_tx			       = $marginInfos[1];
                $line->marque_tx		       = $marginInfos[2];
                // Multicurrency
                $line->fk_multicurrency        = $objp->fk_multicurrency;
                $line->multicurrency_code      = $objp->multicurrency_code;
                $line->multicurrency_subprice  = $objp->multicurrency_subprice;
                $line->multicurrency_total_ht  = $objp->multicurrency_total_ht;
                $line->multicurrency_total_tva = $objp->multicurrency_total_tva;
                $line->multicurrency_total_ttc = $objp->multicurrency_total_ttc;

                $line->ref				       = $objp->product_ref; // deprecated
                $line->product_ref		       = $objp->product_ref;
                $line->libelle			       = $objp->product_label;
                $line->product_label	       = $objp->product_label;
                $line->product_desc            = $objp->product_desc;
                $line->product_tobatch         = $objp->product_tobatch;
                $line->fk_product_type         = $objp->fk_product_type;	// Produit ou service

                $this->lines[$i] = $line;

                $i++;
            }

            $this->db->free($result);

            return 1;
        }
        else
        {
            $this->error = $this->db->error();
            $this->errors[] = $this->error;
            return -3;
        }
    }


    /**
     *	Add an requestmanager line into database (linked to product/service or not)
     *
     *	@param      string			$desc            	Description of line
     *	@param      float			$pu_ht    	        Unit price (without tax)
     *	@param      float			$qty             	Quantite
     * 	@param    	float			$txtva           	Force Vat rate, -1 for auto (Can contain the vat_src_code too with syntax '9.9 (CODE)')
     * 	@param		float			$txlocaltax1		Local tax 1 rate (deprecated, use instead txtva with code inside)
     * 	@param		float			$txlocaltax2		Local tax 2 rate (deprecated, use instead txtva with code inside)
     *	@param      int				$fk_product      	Id of product
     *	@param      float			$remise_percent  	Pourcentage de remise de la ligne
     *	@param      int				$info_bits			Bits de type de lignes
     *	@param      int				$fk_remise_except	Id remise
     *	@param      string			$price_base_type	HT or TTC
     *	@param      float			$pu_ttc    		    Prix unitaire TTC
     *	@param      int				$date_start       	Start date of the line
     *	@param      int				$date_end         	End date of the line
     *	@param      int				$type				Type of line (0=product, 1=service). Not used if fk_product is defined, the type of product is used.
     *	@param      int				$rang             	Position of line
     *	@param		int				$special_code		Special code (also used by externals modules!)
     *	@param		int				$fk_parent_line		Parent line
     *  @param		int				$fk_fournprice		Id supplier price
     *  @param		int				$pa_ht				Buying price (without tax)
     *  @param		string			$label				Label
     *  @param		array			$array_options		extrafields array. Example array('options_codeforfield1'=>'valueforfield1', 'options_codeforfield2'=>'valueforfield2', ...)
     * 	@param 		string			$fk_unit 			Code of the unit to use. Null to use the default one
     * 	@param		string		    $origin				Object class 'order', ...
     *  @param		int			    $origin_id			Id of origin object
     * 	@param		double			$pu_ht_devise		Unit price in currency
     *	@return     int             					>0 if OK, <0 if KO
     *
     *	@see        add_product
     *
     *	Les parametres sont deja cense etre juste et avec valeurs finales a l'appel
     *	de cette methode. Aussi, pour le taux tva, il doit deja avoir ete defini
     *	par l'appelant par la methode get_default_tva(societe_vendeuse,societe_acheteuse,produit)
     *	et le desc doit deja avoir la bonne valeur (a l'appelant de gerer le multilangue)
     */
    function addline($desc, $pu_ht, $qty, $txtva, $txlocaltax1=0, $txlocaltax2=0, $fk_product=0, $remise_percent=0, $info_bits=0, $fk_remise_except=0, $price_base_type='HT', $pu_ttc=0, $date_start='', $date_end='', $type=0, $rang=-1, $special_code=0, $fk_parent_line=0, $fk_fournprice=null, $pa_ht=0, $label='',$array_options=0, $fk_unit=null, $origin='', $origin_id=0, $pu_ht_devise=0)
    {
        global $mysoc, $conf, $langs, $user;

        dol_syslog(__METHOD__ . " requestmanager id=$this->id, desc=$desc, pu_ht=$pu_ht, qty=$qty, txtva=$txtva, fk_product=$fk_product, remise_percent=$remise_percent, info_bits=$info_bits, fk_remise_except=$fk_remise_except, price_base_type=$price_base_type, pu_ttc=$pu_ttc, date_start=$date_start, date_end=$date_end, type=$type special_code=$special_code, fk_unit=$fk_unit", LOG_DEBUG);

        include_once DOL_DOCUMENT_ROOT . '/core/lib/price.lib.php';

        // Clean parameters
        if (empty($fk_parent_line) || $fk_parent_line < 0) $fk_parent_line=0;
        if (empty($txtva)) $txtva=0;
        if (empty($txlocaltax1)) $txlocaltax1=0;
        if (empty($txlocaltax2)) $txlocaltax2=0;
        if (empty($qty)) $qty=0;
        if (empty($remise_percent)) $remise_percent=0;
        if (empty($info_bits)) $info_bits=0;
        if (empty($rang)) $rang=0;
        if (empty($this->fk_multicurrency)) $this->fk_multicurrency=0;

        $label = trim($label);
        $desc = trim($desc);
        $txtva = price2num($txtva);
        $txlocaltax1 = price2num($txlocaltax1);
        $txlocaltax2 = price2num($txlocaltax2);
        $qty = price2num($qty);
        $remise_percent = price2num($remise_percent);
        $pu_ht = price2num($pu_ht);
        $pu_ttc = price2num($pu_ttc);
        $pa_ht = price2num($pa_ht);
        if ($price_base_type == 'HT') {
            $pu = $pu_ht;
        } else {
            $pu = $pu_ttc;
        }

        // Check parameters
        if ($type < 0) return -1;

        if ($this->statut_type == self::STATUS_TYPE_INITIAL || $this->statut_type == self::STATUS_TYPE_IN_PROGRESS)
        {
            $this->db->begin();

            $product_type = $type;
            if (!empty($fk_product))
            {
                $product = new Product($this->db);
                $product->fetch($fk_product);
                $product_type = $product->type;

                if (!empty($conf->global->STOCK_MUST_BE_ENOUGH_FOR_REQUESTMANAGER) && $product_type==0 && $product->stock_reel<$qty)
                {
                    $langs->load("errors");
                    $this->error = $langs->trans('ErrorStockIsNotEnoughToAddProductOnRequestManager', $product->ref);
                    $this->errors[] = $this->error;
                    dol_syslog(__METHOD__ . " error=Product ".$product->ref.": ".$this->error, LOG_ERR);
                    $this->db->rollback();
                    //return self::STOCK_NOT_ENOUGH_FOR_REQUESTMANAGER;
                    return -3;
                }
            }
            // Calcul du total TTC et de la TVA pour la ligne a partir de
            // qty, pu, remise_percent et txtva
            // TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
            // la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.

            $localtaxes_type = getLocalTaxesFromRate($txtva, 0, $this->thirdparty, $mysoc);

            // Clean vat code
            $vat_src_code = '';
            if (preg_match('/\((.*)\)/', $txtva, $reg))
            {
                $vat_src_code = $reg[1];
                $txtva = preg_replace('/\s*\(.*\)/', '', $txtva);    // Remove code into vatrate.
            }

            $tabprice = calcul_price_total($qty, $pu, $remise_percent, $txtva, $txlocaltax1, $txlocaltax2, 0, $price_base_type, $info_bits, $product_type, $mysoc, $localtaxes_type, 100, $this->multicurrency_tx, $pu_ht_devise);

            $total_ht        = $tabprice[0];
            $total_tva       = $tabprice[1];
            $total_localtax1 = $tabprice[9];
            $total_localtax2 = $tabprice[10];
            $total_ttc       = $tabprice[2];
            $pu_ht           = $tabprice[3];

            // MultiCurrency
            $multicurrency_total_ht  = $tabprice[16];
            $multicurrency_total_tva = $tabprice[17];
            $multicurrency_total_ttc = $tabprice[18];
            $pu_ht_devise            = $tabprice[19];

            // Rang to use
            $rangtouse = $rang;
            if ($rangtouse == -1)
            {
                $rangmax = $this->line_max($fk_parent_line);
                $rangtouse = $rangmax + 1;
            }

            // Insert line
            $this->line = new RequestManagerLine($this->db);

            $this->line->context                 = $this->context;
            $this->line->origin                  = $origin;
            $this->line->origin_id               = $origin_id;

            $this->line->fk_requestmanager       = $this->id;
            $this->line->fk_parent_line          = $fk_parent_line;
            $this->line->fk_product              = $fk_product;
            $this->line->label                   = $label;
            $this->line->desc                    = $desc;
            $this->line->vat_src_code            = $vat_src_code;
            $this->line->tva_tx                  = $txtva;
            $this->line->localtax1_tx            = $localtaxes_type[1];
            $this->line->localtax1_type          = $localtaxes_type[0];
            $this->line->localtax2_tx            = $localtaxes_type[3];
            $this->line->localtax2_type          = $localtaxes_type[2];
            $this->line->qty                     = $qty;
            $this->line->remise_percent          = $remise_percent;
            $this->line->fk_remise_except        = $fk_remise_except;
            $this->line->subprice                = $pu_ht;
            $this->line->total_ht                = $total_ht;
            $this->line->total_tva               = $total_tva;
            $this->line->total_localtax1         = $total_localtax1;
            $this->line->total_localtax2         = $total_localtax2;
            $this->line->total_ttc               = $total_ttc;
            $this->line->product_type            = $product_type;
            $this->line->date_start              = $date_start;
            $this->line->date_end                = $date_end;
            $this->line->info_bits               = $info_bits;
            $this->line->pa_ht                   = $pa_ht;
            $this->line->fk_fournprice           = $fk_fournprice;
            $this->line->special_code            = $special_code;
            $this->line->rang                    = $rangtouse;
            $this->line->fk_unit                 = $fk_unit;
            // Multicurrency
            $this->line->fk_multicurrency        = $this->fk_multicurrency;
            $this->line->multicurrency_code      = $this->multicurrency_code;
            $this->line->multicurrency_subprice  = $pu_ht_devise;
            $this->line->multicurrency_total_ht  = $multicurrency_total_ht;
            $this->line->multicurrency_total_tva = $multicurrency_total_tva;
            $this->line->multicurrency_total_ttc = $multicurrency_total_ttc;

            if (is_array($array_options) && count($array_options)>0) {
                $this->line->array_options = $array_options;
            }

            $result = $this->line->insert();
            if ($result > 0)
            {
                // Reorder if child line
                if (! empty($fk_parent_line)) $this->line_order(true,'DESC');

                // Mise a jour informations denormalisees au niveau de l'objet meme
                // ex : UPDATE llx_requestmanager SET total_ht, tva, localtax1, localtax2, total_ttc, multicurrency_total_ht, multicurrency_total_tva, multicurrency_total_ttc WHERE rowid = object->id
                $result = $this->update_price(1,'auto',0, $mysoc);	// This method is designed to add line from user input so total calculation must be done using 'auto' mode.
                if ($result > 0)
                {
                    $this->db->commit();
                    return $this->line->rowid;
                }
                else
                {
                    $this->db->rollback();
                    return -1;
                }
            }
            else
            {
                $this->error = $this->line->error;
                $this->errors[] = $this->error;
                dol_syslog(__METHOD__ . " error=" . $this->error, LOG_ERR);
                $this->db->rollback();
                return -2;
            }
        }
        else
        {
            dol_syslog(__METHOD__ . " status of requestmanager must be in progress to allow use of ->addline()", LOG_ERR);
            return -3;
        }
    }


    /**
     *  Delete detail line
     *
     *  @param		int		$lineid			Id of line to delete
     *  @return     int         			>0 if OK, <0 if KO
     */
    function deleteline($lineid)
    {
        if ($this->statut_type == self::STATUS_TYPE_INITIAL || $this->statut_type == self::STATUS_TYPE_IN_PROGRESS)
        {
            $line = new RequestManagerLine($this->db);

            // For triggers
            $line->fetch($lineid);

            if ($line->delete() > 0)
            {
                $this->update_price(1);

                return 1;
            }
            else
            {
                return -1;
            }
        }
        else
        {
            return -2;
        }
    }


    /**
     *  Update a line in database
     *
     *  @param    	int				$rowid            	Id of line to update
     *  @param    	string			$desc             	Description of line
     *  @param    	float			$pu               	Unit price
     *  @param    	float			$qty              	Quantity
     *  @param    	float			$remise_percent   	Percent of discount
     *  @param    	float			$txtva           	Taux TVA
     * 	@param		float			$txlocaltax1		Local tax 1 rate
     *  @param		float			$txlocaltax2		Local tax 2 rate
     *  @param    	string			$price_base_type	HT or TTC
     *  @param    	int				$info_bits        	Miscellaneous informations on line
     *  @param    	int				$date_start        	Start date of the line
     *  @param    	int				$date_end          	End date of the line
     * 	@param		int				$type				Type of line (0=product, 1=service)
     * 	@param		int				$fk_parent_line		Id of parent line (0 in most cases, used by modules adding sublevels into lines).
     * 	@param		int				$skip_update_total	Keep fields total_xxx to 0 (used for special lines by some modules)
     *  @param		int				$fk_fournprice		Id of origin supplier price
     *  @param		int				$pa_ht				Price (without tax) of product when it was bought
     *  @param		string			$label				Label
     *  @param		int				$special_code		Special code (also used by externals modules!)
     *  @param		array			$array_options		extrafields array
     * 	@param 		string			$fk_unit 			Code of the unit to use. Null to use the default one
     *  @param		double			$pu_ht_devise		Amount in currency
     * 	@param		int				$notrigger			disable line update trigger
     *  @return   	int              					< 0 if KO, > 0 if OK
     */
    function updateline($rowid, $desc, $pu, $qty, $remise_percent, $txtva, $txlocaltax1=0.0, $txlocaltax2=0.0, $price_base_type='HT', $info_bits=0, $date_start='', $date_end='', $type=0, $fk_parent_line=0, $skip_update_total=0, $fk_fournprice=null, $pa_ht=0, $label='', $special_code=0, $array_options=0, $fk_unit=null, $pu_ht_devise=0, $notrigger=0)
    {
        global $conf, $mysoc, $langs, $user;

        dol_syslog(__METHOD__ . " id=$rowid, desc=$desc, pu=$pu, qty=$qty, remise_percent=$remise_percent, txtva=$txtva, txlocaltax1=$txlocaltax1, txlocaltax2=$txlocaltax2, price_base_type=$price_base_type, info_bits=$info_bits, date_start=$date_start, date_end=$date_end, type=$type, fk_parent_line=$fk_parent_line, pa_ht=$pa_ht, special_code=$special_code");
        include_once DOL_DOCUMENT_ROOT . '/core/lib/price.lib.php';

        if ($this->statut_type == self::STATUS_TYPE_INITIAL || $this->statut_type == self::STATUS_TYPE_IN_PROGRESS)
        {
            $this->db->begin();

            // Clean parameters
            if (empty($txtva)) $txtva=0;
            if (empty($txlocaltax1)) $txlocaltax1=0;
            if (empty($txlocaltax2)) $txlocaltax2=0;
            if (empty($qty)) $qty=0;
            if (empty($remise_percent)) $remise_percent=0;
            if (empty($info_bits)) $info_bits=0;
            if (empty($special_code) || $special_code == 3) $special_code=0;

            $txtva=price2num($txtva);
            $txlocaltax1=price2num($txlocaltax1);
            $txlocaltax2=price2num($txlocaltax2);
            $qty=price2num($qty);
            $remise_percent=price2num($remise_percent);
            $pu = price2num($pu);
            $pa_ht=price2num($pa_ht);

            // Calcul du total TTC et de la TVA pour la ligne a partir de
            // qty, pu, remise_percent et txtva
            // TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
            // la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.

            $localtaxes_type = getLocalTaxesFromRate($txtva,0,$this->thirdparty, $mysoc);

            // Clean vat code
            $vat_src_code = '';
            if (preg_match('/\((.*)\)/', $txtva, $reg))
            {
                $vat_src_code = $reg[1];
                $txtva = preg_replace('/\s*\(.*\)/', '', $txtva);    // Remove code into vatrate.
            }

            $tabprice = calcul_price_total($qty, $pu, $remise_percent, $txtva, $txlocaltax1, $txlocaltax2, 0, $price_base_type, $info_bits, $type, $mysoc, $localtaxes_type, 100, $this->multicurrency_tx, $pu_ht_devise);

            $total_ht  = $tabprice[0];
            $total_tva = $tabprice[1];
            $total_ttc = $tabprice[2];
            $total_localtax1 = $tabprice[9];
            $total_localtax2 = $tabprice[10];
            $pu_ht  = $tabprice[3];
            $pu_ttc = $tabprice[5];

            // MultiCurrency
            $multicurrency_total_ht  = $tabprice[16];
            $multicurrency_total_tva = $tabprice[17];
            $multicurrency_total_ttc = $tabprice[18];
            $pu_ht_devise = $tabprice[19];

            // Anciens indicateurs: $price, $subprice, $remise (a ne plus utiliser)
            if ($price_base_type == 'TTC')
            {
                $subprice = $pu_ttc;
            }
            else
            {
                $subprice = $pu_ht;
            }

            //Fetch current line from the database and then clone the object and set it in $oldline property
            $line = new RequestManagerLine($this->db);
            $line->fetch($rowid);

            $staticline = clone $line;

            $line->oldline = $staticline;
            $this->line = $line;
            $this->line->context = $this->context;

            // Reorder if fk_parent_line change
            if (! empty($fk_parent_line) && ! empty($staticline->fk_parent_line) && $fk_parent_line != $staticline->fk_parent_line)
            {
                $rangmax = $this->line_max($fk_parent_line);
                $this->line->rang = $rangmax + 1;
            }

            $this->line->rowid                   = $rowid;
            $this->line->fk_parent_line          = $fk_parent_line;
            $this->line->label                   = $label;
            $this->line->desc                    = $desc;
            $this->line->vat_src_code	         = $vat_src_code;
            $this->line->tva_tx                  = $txtva;
            $this->line->localtax1_tx            = $txlocaltax1;
            $this->line->localtax1_type          = $localtaxes_type[0];
            $this->line->localtax2_tx            = $txlocaltax2;
            $this->line->localtax2_type          = $localtaxes_type[2];
            $this->line->qty                     = $qty;
            $this->line->remise_percent          = $remise_percent;
            $this->line->subprice                = $subprice;
            $this->line->total_ht                = $total_ht;
            $this->line->total_tva               = $total_tva;
            $this->line->total_localtax1         = $total_localtax1;
            $this->line->total_localtax2         = $total_localtax2;
            $this->line->total_ttc               = $total_ttc;
            $this->line->product_type            = $type;
            $this->line->date_start              = $date_start;
            $this->line->date_end                = $date_end;
            $this->line->info_bits               = $info_bits;
            $this->line->pa_ht                   = $pa_ht;
            $this->line->fk_fournprice           = $fk_fournprice;
            $this->line->special_code            = $special_code;
            $this->line->fk_unit                 = $fk_unit;
            $this->line->skip_update_total       = $skip_update_total;
            // Multicurrency
            $this->line->multicurrency_subprice	 = $pu_ht_devise;
            $this->line->multicurrency_total_ht  = $multicurrency_total_ht;
            $this->line->multicurrency_total_tva = $multicurrency_total_tva;
            $this->line->multicurrency_total_ttc = $multicurrency_total_ttc;

            if (is_array($array_options) && count($array_options)>0) {
                $this->line->array_options = $array_options;
            }

            $result = $this->line->update($notrigger);
            if ($result > 0)
            {
                // Reorder if child line
                if (! empty($fk_parent_line)) $this->line_order(true,'DESC');

                // Mise a jour info denormalisees
                $this->update_price(1);

                $this->db->commit();
                return $result;
            }
            else
            {
                $this->error = $this->line->error;
                $this->errors[] = $this->error;
                $this->db->rollback();
                return -1;
            }
        }
        else
        {
            $this->error = __METHOD__ . " RequestManager status makes operation forbidden";
            $this->errors[] = $this->error;
            return -2;
        }
    }

    /**
     *	Create event call from API
     *
     *	@return		int		<0 if ko, >0 if ok
     */
    function create_event_api($actioncomm_object)
    {
        global $conf,$user;

        require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
		$actioncomm = new ActionComm($this->db);
		$actioncomm = $actioncomm_object;

		$this->db->begin();

		if ($actioncomm->create($user) < 0) {
			return 0;
		} else {
			$this->db->commit();
			return 1;
		}
    }

    /**
     *	Update event call from API
     *
     *	@return		int		<0 if ko, >0 if ok
     */
    function update_event_api($actioncomm_object)
    {
        global $conf,$user;

        require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
		$actioncomm = new ActionComm($this->db);
		$actioncomm = $actioncomm_object;

		$this->db->begin();

		if ($actioncomm->update($user) < 0) {
			return 0;
		} else {
			$this->db->commit();
			return 1;
		}
    }
}


/**
 *  Class to manage requestmanager lines
 */
class RequestManagerLine extends CommonObjectLine
{
    public $element = 'requestmanagerdet';
    public $table_element = 'requestmanagerdet';

    /**
     * Array of whitelist of properties keys for this object used for the API
     * @var  array
     *      array('properties_name'=> '' or array('properties_name'=> '' or array(...), ...)
     *      if property is a object and this properties_name value is not a array then get whitelist of his object element
     *      if property is a object and this properties_name value is a array then get whitelist set in the array
     *      if property is a array and this properties_name value is not a array then get all values
     *      if property is a array and this properties_name value is a array then get whitelist set in the array
     */
    static public $API_WHITELIST_OF_PROPERTIES = array(
        "fk_requestmanager" => '', "fk_parent_line" => '', "fk_product" => '', "label" => '', "description" => '',
        "vat_src_code" => '', "tva_tx" => '', "localtax1_tx" => '', "localtax1_type" => '', "localtax2_tx" => '',
        "localtax2_type" => '', "qty" => '', "remise_percent" => '', "fk_remise_except" => '', "subprice" => '',
        "product_type" => '', "date_start" => '', "date_end" => '', "info_bits" => '', "pa_ht" => '', "fk_fournprice" => '',
        "marge_tx" => '', "marque_tx" => '', "special_code" => '', "rang" => '', "multicurrency_subprice" => '',
        "multicurrency_total_ht" => '', "multicurrency_total_tva" => '', "multicurrency_total_ttc" => '', "product_ref" => '',
        "product_label" => '', "product_desc" => '', "fk_product_type" => '', "product_tobatch" => '', "id" => '',
        "fk_unit" => '', "array_options" => '', "total_ht" => '', "total_tva" => '', "total_localtax1" => '',
        "total_localtax2" => '', "total_ttc" => '',
    );

    /**
     * Array of whitelist of properties keys for this object when is a linked object used for the API
     * @var  array
     *      if empty array then equal at $api_whitelist_of_properties
     *      array('properties_name'=> '' or array('properties_name'=> '' or array(...), ...)
     *      if property is a object and this properties_name value is not a array then get whitelist of his object element
     *      if property is a object and this properties_name value is a array then get whitelist set in the array
     *      if property is a array and this properties_name value is not a array then get all values
     *      if property is a array and this properties_name value is a array then get whitelist set in the array
     */
    static public $API_WHITELIST_OF_PROPERTIES_LINKED_OBJECT = array(
    );

    /**
     * Array of blacklist of properties keys for this object used for the API
     * @var  array
     *      array('element_type' => array('properties_name'=> '' or array('properties_name'=> '' or array(...), ...), ...)
     *      if property is a object and this properties_name value is not a array then get blacklist of his object element
     *      if property is a object and this properties_name value is a array then get blacklist set in the array
     *      if property is a array and this properties_name value is not a array then get all values
     *      if property is a array and this properties_name value is a array then get blacklist set in the array
     */
    static protected $API_BLACKLIST_OF_PROPERTIES = array(
    );

    /**
     * Array of blacklist of properties keys for this object when is a linked object used for the API
     * @var  array
     *      array('element_type' => array('properties_name'=> '' or array('properties_name'=> '' or array(...), ...), ...)
     *      if property is a object and this properties_name value is not a array then get blacklist of his object element
     *      if property is a object and this properties_name value is a array then get blacklist set in the array
     *      if property is a array and this properties_name value is not a array then get all values
     *      if property is a array and this properties_name value is a array then get blacklist set in the array
     */
    static protected $API_BLACKLIST_OF_PROPERTIES_LINKED_OBJECT = array(
    );

    var $oldline;

    /**
     * Id of parent requestmanager
     * @var int
     */
    public $fk_requestmanager;

    // From llx_requestmanangerdet
    var $fk_parent_line;
    var $fk_product;
    var $label;
    var $desc;
    var $description;
    var $vat_src_code;
    var $tva_tx;
    var $localtax1_tx;		// Local tax 1
    var $localtax1_type;	// Local tax 1 type
    var $localtax2_tx;		// Local tax 2
    var $localtax2_type;	// Local tax 2 type
    var $qty;
    var $remise_percent;
    var $fk_remise_except;
    var $subprice;
    var $product_type = Product::TYPE_PRODUCT;

    // Start and end date of the line
    var $date_start;
    var $date_end;
    var $info_bits = 0;	// Liste d'options cumulables:
    // Bit 0: 	0 si TVA normal - 1 si TVA NPR
    // Bit 1:	0 ligne normale - 1 si ligne de remise fixe

    /**
     * Buy price without taxes
     * @var float
     */
    var $pa_ht;
    var $fk_fournprice;     // fk_product_fournisseur_price
    var $marge_tx;
    var $marque_tx;

    var $special_code;	// Tag for special lines (exlusive tags)
    // 1: frais de port
    // 2: ecotaxe
    // 3: option line (when qty = 0)
    var $rang = 0;
    //var $fk_unit; // see CommonObjectLine

    // Multicurrency
    var $fk_multicurrency;
    var $multicurrency_code;
    var $multicurrency_subprice;
    var $multicurrency_total_ht;
    var $multicurrency_total_tva;
    var $multicurrency_total_ttc;


    // From llx_product
    /**
     * @deprecated
     * @see product_ref
     */
    var $ref;
    /**
     * Product reference
     * @var string
     */
    public $product_ref;
    /**
     * @deprecated
     * @see product_label
     */
    var $libelle;
    /**
     *  Product label
     * @var string
     */
    public $product_label;
    /**
     * Product description
     * @var string
     */
    public $product_desc;
    /**
     * Product type
     * @var int
     * @see Product::TYPE_PRODUCT, Product::TYPE_SERVICE
     */
    var $fk_product_type;
    /**
     * Product to batch
     * @var int
     */
    var $product_tobatch;


    var $skip_update_total; // Skip update price total for special lines


    /**
     *      Constructor
     *
     *      @param     DoliDB	$db      handler d'acces base de donnee
     */
    function __construct($db)
    {
        $this->db= $db;
    }

    /**
     * Get all errors
     *
     * @return array    Array of errors
     */
	public function getErrors() {
	    $errors = is_array($this->errors) ? $this->errors : array();
	    $errors = array_merge($errors, (!empty($this->error) ? array($this->error) : array()));

	    return $errors;
    }

    /**
     * 	Delete line in database
     *
     *  @param      int		$notrigger	    0=launch triggers after, 1=disable triggers
     *	@return     int     <0 si ko, >0 si ok
     */
    function delete($notrigger=0)
    {
        global $conf, $user;

        $error=0;

        $this->db->begin();

        $sql = "DELETE FROM " . MAIN_DB_PREFIX . $this->table_element . " WHERE rowid=" . $this->rowid;

        dol_syslog(__METHOD__, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            // Remove extrafields
            if ((! $error) && (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED))) // For avoid conflicts if trigger used
            {
                $this->id=$this->rowid;
                $result = $this->deleteExtraFields();
                if ($result < 0)
                {
                    $error++;
                    dol_syslog(__METHOD__ . " error -4 ".$this->error, LOG_ERR);
                }
            }

            if (! $error && ! $notrigger)
            {
                // Call trigger
                $result=$this->call_trigger('LINEREQUESTMANAGER_DELETE', $user);
                if ($result < 0) $error++;
                // End call triggers
            }

            if (!$error) {
                $this->db->commit();
                return 1;
            }

            foreach($this->errors as $errmsg)
            {
                dol_syslog(__METHOD__ . " " . $errmsg, LOG_ERR);
                $this->error .= ($this->error?', '.$errmsg:$errmsg);
            }
            $this->db->rollback();
            return -1*$error;
        }
        else
        {
            $this->error = $this->db->lasterror();
            return -1;
        }
    }


    /**
     *  Load line requestmanager
     *
     *  @param  int		$rowid          Id line order
     *  @return	int						<0 if KO, >0 if OK
     */
    function fetch($rowid)
    {
        $sql  = "SELECT";
        $sql .= " l.rowid, l.fk_requestmanager, l.fk_parent_line, l.fk_product, l.label as custom_label, l.description";
        $sql .= ", l.vat_src_code, l.tva_tx, l.localtax1_tx, l.localtax1_type, l.localtax2_tx, l.localtax2_type";
        $sql .= ", l.qty, l.remise_percent, l.fk_remise_except, l.subprice";
        $sql .= ", l.total_ht, l.total_tva, l.total_localtax1, l.total_localtax2, l.total_ttc";
        $sql .= ", l.product_type";
        $sql .= ", l.date_start, l.date_end";
        $sql .= ", l.info_bits, l.buy_price_ht as pa_ht, l.fk_product_fournisseur_price as fk_fournprice, l.special_code, l.rang, l.fk_unit";
        $sql .= ", l.fk_multicurrency, l.multicurrency_code, l.multicurrency_subprice, l.multicurrency_total_ht, l.multicurrency_total_tva, l.multicurrency_total_ttc";
        $sql .= ", p.ref as product_ref, p.description as product_desc, p.fk_product_type, p.label as product_label, p.tobatch as product_tobatch";
        $sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element . " as l";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product as p ON (p.rowid = l.fk_product)";
        $sql .= " WHERE l.rowid = " . $rowid;

        $result = $this->db->query($sql);
        if ($result)
        {
            $objp = $this->db->fetch_object($result);

            $this->id                      = $objp->rowid;
            $this->rowid                   = $objp->rowid;
            $this->fk_requestmanager       = $objp->fk_requestmanager;
            $this->fk_parent_line	       = $objp->fk_parent_line;
            $this->fk_product              = $objp->fk_product;
            $this->label                   = $objp->custom_label;
            $this->desc                    = $objp->description;
            $this->description             = $objp->description;		// Description line
            $this->vat_src_code            = $objp->vat_src_code;
            $this->tva_tx                  = $objp->tva_tx;
            $this->localtax1_tx            = $objp->localtax1_tx;
            $this->localtax1_type	       = $objp->localtax1_type;
            $this->localtax2_tx            = $objp->localtax2_tx;
            $this->localtax2_type	       = $objp->localtax2_type;
            $this->qty                     = $objp->qty;
            $this->remise_percent          = $objp->remise_percent;
            $this->fk_remise_except        = $objp->fk_remise_except;
            $this->subprice                = $objp->subprice;
            $this->total_ht                = $objp->total_ht;
            $this->total_tva               = $objp->total_tva;
            $this->total_localtax1         = $objp->total_localtax1;
            $this->total_localtax2         = $objp->total_localtax2;
            $this->total_ttc               = $objp->total_ttc;
            $this->product_type            = $objp->product_type;
            $this->date_start              = $this->db->jdate($objp->date_start);
            $this->date_end                = $this->db->jdate($objp->date_end);
            $this->info_bits               = $objp->info_bits;
            $this->fk_fournprice 	       = $objp->fk_fournprice; // fk_product_fournisseur_price
            $this->special_code		       = $objp->special_code;
            $this->rang                    = $objp->rang;
            $this->fk_unit                 = $objp->fk_unit;
            $marginInfos			       = getMarginInfos($objp->subprice, $objp->remise_percent, $objp->tva_tx, $objp->localtax1_tx, $objp->localtax2_tx, $this->fk_fournprice, $objp->pa_ht);
            $this->pa_ht 			       = $marginInfos[0];
            $this->marge_tx			       = $marginInfos[1];
            $this->marque_tx		       = $marginInfos[2];
            // Multicurrency
            $this->fk_multicurrency        = $objp->fk_multicurrency;
            $this->multicurrency_code      = $objp->multicurrency_code;
            $this->multicurrency_subprice  = $objp->multicurrency_subprice;
            $this->multicurrency_total_ht  = $objp->multicurrency_total_ht;
            $this->multicurrency_total_tva = $objp->multicurrency_total_tva;
            $this->multicurrency_total_ttc = $objp->multicurrency_total_ttc;

            $this->ref				       = $objp->product_ref; // deprecated
            $this->product_ref		       = $objp->product_ref;
            $this->libelle			       = $objp->product_label;
            $this->product_label	       = $objp->product_label;
            $this->product_desc            = $objp->product_desc;
            $this->product_tobatch         = $objp->product_tobatch;
            $this->fk_product_type         = $objp->fk_product_type;	// Produit ou service

            $this->db->free($result);

            return 1;
        }
        else
        {
            $this->error = $this->db->lasterror();
            return -1;
        }
    }


    /**
     *  Insert object line requestmanager in database
     *
     *	@param		int		$notrigger		1=Does not execute triggers, 0= execute triggers
     *	@return		int						<0 if KO, >0 if OK
     */
    function insert($notrigger=0)
    {
        global $conf,$user;

        $error = 0;

        dol_syslog(__METHOD__ . " rang=" . $this->rang);

        $pa_ht_isemptystring = (empty($this->pa_ht) && $this->pa_ht == ''); // If true, we can use a default value. If this->pa_ht = '0', we must use '0'.

        // Clean parameters
        if (empty($this->fk_parent_line)) $this->fk_parent_line=0;
        if (empty($this->tva_tx)) $this->tva_tx=0;
        if (empty($this->localtax1_tx)) $this->localtax1_tx=0;
        if (empty($this->localtax1_type)) $this->localtax1_type=0;
        if (empty($this->localtax2_tx)) $this->localtax2_tx=0;
        if (empty($this->localtax2_type)) $this->localtax2_type=0;
        if (!is_numeric($this->qty)) $this->qty=0;
        if (empty($this->remise_percent) || ! is_numeric($this->remise_percent)) $this->remise_percent=0;
        if (empty($this->total_localtax1)) $this->total_localtax1=0;
        if (empty($this->total_localtax2)) $this->total_localtax2=0;
        if (empty($this->info_bits)) $this->info_bits=0;
        if (empty($this->pa_ht)) $this->pa_ht=0;
        if (empty($this->fk_fournprice)) $this->fk_fournprice=0;
        if (empty($this->special_code)) $this->special_code=0;
        if (empty($this->rang)) $this->rang=0;
        if (empty($this->multicurrency_subprice))  $this->multicurrency_subprice=0;
        if (empty($this->multicurrency_total_ht))  $this->multicurrency_total_ht=0;
        if (empty($this->multicurrency_total_tva)) $this->multicurrency_total_tva=0;
        if (empty($this->multicurrency_total_ttc)) $this->multicurrency_total_ttc=0;

        // if buy price not defined, define buyprice as configured in margin admin
        if ($this->pa_ht == 0 && $pa_ht_isemptystring)
        {
            if (($result = $this->defineBuyPrice($this->subprice, $this->remise_percent, $this->fk_product)) < 0)
            {
                return $result;
            }
            else
            {
                $this->pa_ht = $result;
            }
        }

        // Check parameters
        if ($this->product_type < 0) return -1;

        $this->db->begin();

        // Insert line into database
        $sql  = "INSERT INTO " . MAIN_DB_PREFIX . $this->table_element . " (";
        $sql .= "fk_requestmanager, fk_parent_line, fk_product, label, description";
        $sql .= ", vat_src_code, tva_tx, localtax1_tx, localtax1_type, localtax2_tx, localtax2_type";
        $sql .= ", qty, remise_percent, fk_remise_except, subprice";
        $sql .= ", total_ht, total_tva, total_localtax1, total_localtax2, total_ttc";
        $sql .= ", product_type";
        $sql .= ", date_start, date_end";
        $sql .= ", info_bits, buy_price_ht, fk_product_fournisseur_price, special_code, rang, fk_unit";
        $sql .= ", fk_multicurrency, multicurrency_code, multicurrency_subprice, multicurrency_total_ht, multicurrency_total_tva, multicurrency_total_ttc";
        $sql .= ") VALUES (";
        $sql .= $this->fk_requestmanager;
        $sql .= ", " . ($this->fk_parent_line>0 ? "'".$this->fk_parent_line."'" : "null");
        $sql .= ", " . ($this->fk_product?"'".$this->fk_product."'":"null");
        $sql .= ", " . (!empty($this->label) ? "'".$this->db->escape($this->label)."'" : "null");
        $sql .= ", '" . $this->db->escape($this->desc) . "'";
        $sql .= ", " . (empty($this->vat_src_code) ? "''" : "'".$this->vat_src_code."'");
        $sql .= ", " . price2num($this->tva_tx);
        $sql .= ", " . price2num($this->localtax1_tx);
        $sql .= ", '" . $this->localtax1_type . "'";
        $sql .= ", " . price2num($this->localtax2_tx);
        $sql .= ", '" . $this->localtax2_type . "'";
        $sql .= ", " . price2num($this->qty);
        $sql .= ", " . price2num($this->remise_percent);
        $sql .= ", " . ($this->fk_remise_except ? "'".$this->fk_remise_except."'" : "null");
        $sql .= ", " . ($this->subprice?price2num($this->subprice):"null");
        $sql .= ", " . price2num($this->total_ht);
        $sql .= ", " . price2num($this->total_tva);
        $sql .= ", " . price2num($this->total_localtax1);
        $sql .= ", " . price2num($this->total_localtax2);
        $sql .= ", " . price2num($this->total_ttc);
        $sql .= ", '" . $this->product_type . "'";
        $sql .= ", " . (!empty($this->date_start) ? "'".$this->db->idate($this->date_start)."'" : "null");
        $sql .= ", " . (!empty($this->date_end) ? "'".$this->db->idate($this->date_end)."'" : "null");
        $sql .= ", " . (isset($this->info_bits) ? "'".$this->info_bits."'" : "null");
        $sql .= ", " . (isset($this->pa_ht) ? "'".price2num($this->pa_ht)."'" : "null");
        $sql .= ", " . (!empty($this->fk_fournprice) ? "'".$this->fk_fournprice."'" : "null");
        $sql .= ", " . $this->special_code;
        $sql .= ", " . $this->rang;
        $sql .= ", " . (!$this->fk_unit ? 'NULL' : $this->fk_unit);
        $sql .= ", " . ($this->fk_multicurrency>0 ? $this->fk_multicurrency : 'null');
        $sql .= ", '" . $this->db->escape($this->multicurrency_code) . "'";
        $sql .= ", " . $this->multicurrency_subprice;
        $sql .= ", " . $this->multicurrency_total_ht;
        $sql .= ", " . $this->multicurrency_total_tva;
        $sql .= ", " . $this->multicurrency_total_ttc;
        $sql .= ")";

        dol_syslog(__METHOD__, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $this->rowid = $this->db->last_insert_id(MAIN_DB_PREFIX . $this->table_element);

            if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
            {
                $this->id = $this->rowid;
                $result = $this->insertExtraFields();
                if ($result < 0)
                {
                    $error++;
                }
            }

            if (!$notrigger)
            {
                // Call trigger
                $result = $this->call_trigger('LINEREQUESTMANAGER_INSERT', $user);
                if ($result < 0)
                {
                    $this->db->rollback();
                    return -1;
                }
                // End call triggers
            }

            $this->db->commit();
            return 1;
        }
        else
        {
            $this->error = $this->db->error() . " sql=" . $sql;
            $this->db->rollback();
            return -1;
        }
    }


    /**
     *	Update the line object into db
     *
     *	@param      User	$user        	User that modify
     *	@param      int		$notrigger		1 = disable triggers
     *	@return		int		<0 si ko, >0 si ok
     */
    function update($user=null, $notrigger=0)
    {
        global $conf;

        $error=0;

        $pa_ht_isemptystring = (empty($this->pa_ht) && $this->pa_ht == ''); // If true, we can use a default value. If this->pa_ht = '0', we must use '0'.

        // Clean parameters
        if (empty($this->fk_parent_line)) $this->fk_parent_line=0;
        if (empty($this->tva_tx)) $this->tva_tx=0;
        if (empty($this->localtax1_tx)) $this->localtax1_tx=0;
        if (empty($this->localtax1_type)) $this->localtax1_type=0;
        if (empty($this->localtax2_tx)) $this->localtax2_tx=0;
        if (empty($this->localtax2_type)) $this->localtax2_type=0;
        if (empty($this->qty)) $this->qty=0;
        if (empty($this->remise_percent)) $this->remise_percent=0;
        if (empty($this->total_localtax1)) $this->total_localtax1=0;
        if (empty($this->total_localtax2)) $this->total_localtax2=0;
        if (empty($this->product_type)) $this->product_type=0;
        if (empty($this->info_bits)) $this->info_bits=0;
        if (empty($this->pa_ht)) $this->pa_ht=0;
        if (empty($this->special_code)) $this->special_code=0;
        if (empty($this->marge_tx)) $this->marge_tx=0;
        if (empty($this->marque_tx)) $this->marque_tx=0;

        // if buy price not defined, define buyprice as configured in margin admin
        if ($this->pa_ht == 0 && $pa_ht_isemptystring)
        {
            if (($result = $this->defineBuyPrice($this->subprice, $this->remise_percent, $this->fk_product)) < 0)
            {
                return $result;
            }
            else
            {
                $this->pa_ht = $result;
            }
        }

        $this->db->begin();

        // Mise a jour ligne en base
        $sql  = "UPDATE " . MAIN_DB_PREFIX . $this->table_element . " SET";
        $sql .= " fk_parent_line=".(! empty($this->fk_parent_line)?$this->fk_parent_line:"null");
        $sql .= ", label=".(! empty($this->label)?"'".$this->db->escape($this->label)."'":"null");
        $sql .= ", description='".$this->db->escape($this->desc)."'";
        $sql .= ", vat_src_code=".(! empty($this->vat_src_code)?"'".$this->db->escape($this->vat_src_code)."'":"''");
        $sql .= ", tva_tx=".price2num($this->tva_tx);
        $sql .= ", localtax1_tx=".price2num($this->localtax1_tx);
        $sql .= ", localtax1_type='".$this->db->escape($this->localtax1_type)."'";
        $sql .= ", localtax2_tx=".price2num($this->localtax2_tx);
        $sql .= ", localtax2_type='".$this->db->escape($this->localtax2_type)."'";
        $sql .= ", qty=".price2num($this->qty);
        $sql .= ", remise_percent=".price2num($this->remise_percent)."";
        $sql .= ", subprice=".price2num($this->subprice)."";
        if (empty($this->skip_update_total))
        {
            $sql .= ", total_ht=".price2num($this->total_ht)."";
            $sql .= ", total_tva=".price2num($this->total_tva)."";
            $sql .= ", total_localtax1=".price2num($this->total_localtax1);
            $sql .= ", total_localtax2=".price2num($this->total_localtax2);
            $sql .= ", total_ttc=".price2num($this->total_ttc)."";
        }
        $sql .= ", product_type=".$this->product_type;
        $sql .= ", date_start=".(! empty($this->date_start)?"'".$this->db->idate($this->date_start)."'":"null");
        $sql .= ", date_end=".(! empty($this->date_end)?"'".$this->db->idate($this->date_end)."'":"null");
        $sql .= ", info_bits=".$this->info_bits;
        $sql .= ", buy_price_ht='".price2num($this->pa_ht)."'";
        $sql .= ", fk_product_fournisseur_price=".(! empty($this->fk_fournprice)?$this->fk_fournprice:"null");
        $sql .= ", special_code=".$this->special_code;
        if (! empty($this->rang)) $sql.= ", rang=".$this->rang;
        $sql .= ", fk_unit=".(!$this->fk_unit ? 'NULL' : $this->fk_unit);
        // Multicurrency
        $sql .= ", multicurrency_subprice=".price2num($this->multicurrency_subprice)."";
        $sql .= ", multicurrency_total_ht=".price2num($this->multicurrency_total_ht)."";
        $sql .= ", multicurrency_total_tva=".price2num($this->multicurrency_total_tva)."";
        $sql .= ", multicurrency_total_ttc=".price2num($this->multicurrency_total_ttc)."";
        $sql .= " WHERE rowid = ".$this->rowid;

        dol_syslog(__METHOD__, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
            {
                $this->id = $this->rowid;
                $result=$this->insertExtraFields();
                if ($result < 0)
                {
                    $error++;
                }
            }

            if (! $notrigger)
            {
                // Call trigger
                $result = $this->call_trigger('LINEREQUESTMANAGER_UPDATE',$user);
                if ($result < 0) $error++;
                // End call triggers
            }

            if (!$error) {
                $this->db->commit();
                return 1;
            }

            foreach($this->errors as $errmsg)
            {
                dol_syslog(__METHOD__ . " " . $errmsg, LOG_ERR);
                $this->error .= ($this->error?', '.$errmsg:$errmsg);
            }
            $this->db->rollback();
            return -1*$error;
        }
        else
        {
            $this->error = $this->db->error();
            $this->db->rollback();
            return -2;
        }
    }


    /**
     *	Update totals of requestmanager into database
     *
     *	@return		int		<0 if ko, >0 if ok
     */
    function update_total()
    {
        $this->db->begin();

        // Clean parameters
        if (empty($this->total_localtax1)) $this->total_localtax1 = 0;
        if (empty($this->total_localtax2)) $this->total_localtax2 = 0;

        // Mise a jour ligne en base
        $sql = "UPDATE " . MAIN_DB_PREFIX . $this->element . " SET";
        $sql .= "total_ht='" . price2num($this->total_ht) . "'";
        $sql .= ", total_tva='" . price2num($this->total_tva) . "'";
        $sql .= ", total_localtax1='" . price2num($this->total_localtax1) . "'";
        $sql .= ", total_localtax2='" . price2num($this->total_localtax2) . "'";
        $sql .= ", total_ttc='" . price2num($this->total_ttc) . "'";
        $sql .= " WHERE rowid = " . $this->rowid;

        dol_syslog(__METHOD__, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            $this->db->commit();
            return 1;
        } else {
            $this->error = $this->db->error();
            $this->db->rollback();
            return -2;
        }
    }
}