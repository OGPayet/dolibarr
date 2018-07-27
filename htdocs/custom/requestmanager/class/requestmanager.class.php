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

/**
 * Class RequestManager
 *
 * Put here description of your class
 * @see CommonObject
 */
class RequestManager extends CommonObject
{
	public $element = 'requestmanager';
	public $table_element = 'requestmanager';
    protected $ismultientitymanaged = 1;	// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe

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
     * ID of the thirdparty
     * @var int
     */
    public $socid;

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
     * List requester contact ID (uID for User or cID for Contact)
     * @var string[]
     */
    public $requester_ids;
    /**
     * List requester object (User or Contact)
     * @var User[]|Contact[]
     */
    public $requester_list;
    /**
     * Notify requester by email the request (0 / 1)
     * @var int
     */
    public $notify_requester_by_email;
    /**
     * List watcher contact ID (uID for User or cID for Contact)
     * @var string[]
     */
    public $watcher_ids;
    /**
     * List watcher object (User or Contact)
     * @var User[]|Contact[]
     */
    public $watcher_list;
    /**
     * Notify watcher by email the request (0 / 1)
     * @var int
     */
    public $notify_watcher_by_email;
    /**
     * Id of the User who is assigned to this request
     * @var int
     */
    public $assigned_user_id;
    /**
     * User who is assigned to this request
     * @var User
     */
    public $assigned_user;
    /**
     * Id of the UserGroup who is assigned to this request
     * @var int
     */
    public $assigned_usergroup_id;
    /**
     * UserGroup who is assigned to this request
     * @var UserGroup
     */
    public $assigned_usergroup;
    /**
     * Notify assigned by email the request (0 / 1)
     * @var int
     */
    public $notify_assigned_by_email;

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
     * New status of the request when set_status() is called
     * @var int
     */
    public $new_statut;
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
        $this->socid = $this->socid > 0 ? $this->socid : 0;
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
        $this->assigned_user_id = $this->assigned_user_id > 0 ? $this->assigned_user_id : 0;
        $this->assigned_usergroup_id = $this->assigned_usergroup_id > 0 ? $this->assigned_usergroup_id : 0;
        $this->notify_assigned_by_email = !empty($this->notify_assigned_by_email) ? 1 : 0;
        $this->date_deadline = $this->date_deadline > 0 ? $this->date_deadline : 0;
        $this->statut = $status > 0 ? $status : 0;
        $this->entity = empty($this->entity) ? $conf->entity : $this->entity;
        $this->date_creation = $now;
        $this->user_creation_id = $user->id;
        $this->requester_ids = empty($this->requester_ids) ? array() : (is_string($this->requester_ids) ? explode(',', $this->requester_ids) : $this->requester_ids);
        $this->watcher_ids = empty($this->watcher_ids) ? array() : (is_string($this->watcher_ids) ? explode(',', $this->watcher_ids) : $this->watcher_ids);

        // Check parameters
        if (empty($this->socid)) {
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("RequestManagerThirdParty"));
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
        if (empty($this->description)) {
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("RequestManagerDescription"));
            $error++;
        }
        if ($this->date_deadline > 0 && $this->date_deadline < $now) {
            $this->errors[] = $langs->trans("RequestManagerErrorDeadlineDateInferiorAtCreateDate");
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
        if (!is_array($this->watcher_ids)) {
            $this->errors[] = $langs->trans("ErrorBadParameters") . ': ' . $langs->trans("RequestManagerWatcher");
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
        $sql .= " ref";
        $sql .= ", ref_ext";
        $sql .= ", fk_soc";
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
        $sql .= ", fk_assigned_user";
        $sql .= ", fk_assigned_usergroup";
        $sql .= ", notify_assigned_by_email";
        $sql .= ", date_deadline";
        $sql .= ", fk_status";
        $sql .= ", entity";
        $sql .= ", datec";
        $sql .= ", fk_user_author";
        $sql .= ")";
        $sql .= " VALUES (";
        $sql .= " '" . $this->db->escape($this->ref) . "'";
        $sql .= ", '" . $this->db->escape($this->ref_ext) . "'";
        $sql .= ", " . $this->socid;
        $sql .= ", '" . $this->db->escape($this->label) . "'";
        $sql .= ", '" . $this->db->escape($this->description) . "'";
        $sql .= ", " . $this->fk_type;
        $sql .= ", " . ($this->fk_category > 0 ? $this->fk_category : 'NULL');
        $sql .= ", " . ($this->fk_source > 0 ? $this->fk_source : 'NULL');
        $sql .= ", " . ($this->fk_urgency > 0 ? $this->fk_urgency : 'NULL');
        $sql .= ", " . ($this->fk_impact > 0 ? $this->fk_impact : 'NULL');
        $sql .= ", " . ($this->fk_priority > 0 ? $this->fk_priority : 'NULL');
        $sql .= ", " . ($this->notify_requester_by_email > 0 ? $this->notify_requester_by_email : 'NULL');
        $sql .= ", " . ($this->notify_watcher_by_email > 0 ? $this->notify_watcher_by_email : 'NULL');
        $sql .= ", " . ($this->assigned_user_id > 0 ? $this->assigned_user_id : 'NULL');
        $sql .= ", " . ($this->assigned_usergroup_id > 0 ? $this->assigned_usergroup_id : 'NULL');
        $sql .= ", " . ($this->notify_assigned_by_email > 0 ? $this->notify_assigned_by_email : 'NULL');
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
                            if (!$ret) {
                                $this->errors[] = $this->db->lasterror();
                                $error++;
                            }
                        }
                    } else {                               // Old behaviour, if linkedObjectsIds has only one link per type, so is something like array('contract'=>id1))
                        $origin_id = $tmp_origin_id;
                        $ret = $this->add_object_linked($origin, $origin_id);
                        if (!$ret) {
                            $this->errors[] = $this->db->lasterror();
                            $error++;
                        }
                    }
                }
            }

            if (!$error && !empty($this->requester_ids)) {
                // Set requester contacts
                foreach ($this->requester_ids as $requester) {
                    if (preg_match('/(u|c)(\d+)/i', $requester, $matches)) {
                        $this->add_contact($matches[2], 'REQUESTER', $matches[1] == 'u' ? 'internal' : 'external');
                    }
                }
            }

            if (!$error && !empty($this->watcher_ids)) {
                // Set watcher contacts
                foreach ($this->watcher_ids as $watcher) {
                    if (preg_match('/(u|c)(\d+)/i', $watcher, $matches)) {
                        $this->add_contact($matches[2], 'WATCHER', $matches[1] == 'u' ? 'internal' : 'external');
                    }
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
        global $langs;
        $this->errors = array();
        $langs->load("requestmanager@requestmanager");

        dol_syslog(__METHOD__ . " id=" . $id . " ref=" . $ref . " refext=" . $refext, LOG_DEBUG);

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
        $sql .= ' t.fk_assigned_user,';
        $sql .= ' t.fk_assigned_usergroup,';
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
                $this->assigned_user_id             = $obj->fk_assigned_user;
                $this->assigned_usergroup_id        = $obj->fk_assigned_usergroup;
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

                $this->fetch_requester();
                $this->fetch_watcher();
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
     *	Fetch array of objects linked to current object. Links are loaded into this->linkedObjects array and this->linkedObjectsIds
     *  Possible usage for parameters:
     *  - all parameters empty -> we look all link to current object (current object can be source or target)
     *  - source id+type -> will get target list linked to source
     *  - target id+type -> will get source list linked to target
     *  - source id+type + target type -> will get target list of the type
     *  - target id+type + target source -> will get source list of the type
     *
     *	@param	int		$sourceid		Object source id (if not defined, id of object)
     *	@param  string	$sourcetype		Object source type (if not defined, element name of object)
     *	@param  int		$targetid		Object target id (if not defined, id of object)
     *	@param  string	$targettype		Object target type (if not defined, elemennt name of object)
     *	@param  string	$clause			'OR' or 'AND' clause used when both source id and target id are provided
     *  @param	int		$alsosametype	0=Return only links to object that differs from source. 1=Include also link to objects of same type.
     *	@return	void
     *  @see	add_object_linked, updateObjectLinked, deleteObjectLinked
     */
    public function fetchObjectLinked($sourceid=null, $sourcetype='', $targetid=null, $targettype='', $clause='OR', $alsosametype=1)
    {
        global $conf;

        parent::fetchObjectLinked($sourceid, $sourcetype, $targetid, $targettype, $clause, $alsosametype);

        // add contract objects
        if ($conf->contrat->enabled) {
            require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
            require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

            $societe = new Societe($this->db);
            $societe->fetch($this->socid);

            $sql  = "SELECT c.rowid, c.ref";
            $sql .= " FROM " . MAIN_DB_PREFIX . "contrat as c";
            $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "societe as s ON s.rowid = c.fk_soc";
            $sql .= " WHERE c.entity = " . $conf->entity;
            if (empty($conf->global->REQUESTMANAGER_CONTRACT_SEARCH_IN_PARENT_COMPANY)) {
                $sql .= " AND s.rowid = " . $this->socid;
            } else {
                // add search in parent company
                $sql .= " AND (s.rowid = " . $this->socid . " OR s.rowid = " . $societe->parent . ")";
            }
            $sql .= " ORDER BY c.rowid DESC";

            $resql = $this->db->query($sql);
            if ($resql) {
                while($obj = $this->db->fetch_object($resql)) {
                    $contrat = new Contrat($this->db);
                    $contrat->fetch($obj->rowid);
                    $this->linkedObjects['contrat'][] = $contrat;
                }
            }
        }

        // TODO : add equipement objects
    }


    /**
     *  Load the requester contacts
     *
     * @param   int     $with_object    Load also the object
     * @param   string  $sourceFilter   [=''] for all, internal for users, external for soc people
     * @return  int                     <0 if KO, >0 if OK
     */
    function fetch_requester($with_object=0, $sourceFilter='')
    {
        $ids_users = array();
        $object_users = array();

        $ids_contacts = array();
        $object_contacts = array();

        if ($sourceFilter == '' || $sourceFilter == 'internal') {
            require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';

            // Get users
            $users = $this->liste_contact(-1, 'internal', 1, 'REQUESTER');
            if (!is_array($users))
                return -1;

            foreach ($users as $user_id) {
                $ids_users['u' . $user_id] = $user_id;
                if ($with_object) {
                    $user = new User($this->db);
                    $user->fetch($user_id);
                    $object_users['u' . $user_id] = $user;
                }
            }
        }

        if ($sourceFilter == '' || $sourceFilter == 'external') {
            require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';

            // Get contacts
            $contacts = $this->liste_contact(-1, 'external', 1, 'REQUESTER');
            if (!is_array($users))
                return -1;

            foreach ($contacts as $contact_id) {
                $ids_contacts['c' . $contact_id] = $contact_id;
                if ($with_object) {
                    $contact = new Contact($this->db);
                    $contact->fetch($contact_id);
                    $object_contacts['c' . $contact_id] = $contact;
                }
            }
        }

        $this->requester_ids = array_merge($ids_users, $ids_contacts);
        $this->requester_list = array_merge($object_users, $object_contacts);
    }

    /**
     *  Load the watcher contacts
     *
     * @param   int     $with_object    Load also the object
     * @param   string  $sourceFilter   [=''] for all, internal for users, external for soc people
     * @return  int                     <0 if KO, >0 if OK
     */
    function fetch_watcher($with_object=0, $sourceFilter='')
    {
        $ids_users = array();
        $object_users = array();

        $ids_contacts = array();
        $object_contacts = array();

        if ($sourceFilter == '' || $sourceFilter == 'internal') {
            require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
            // Get users
            $users = $this->liste_contact(-1, 'internal', 1, 'WATCHER');
            if (!is_array($users))
                return -1;


            $ids_users = array();
            $object_users = array();
            foreach ($users as $user_id) {
                $ids_users['u' . $user_id] = $user_id;
                if ($with_object) {
                    $user = new User($this->db);
                    $user->fetch($user_id);
                    $object_users['u' . $user_id] = $user;
                }
            }
        }

        if ($sourceFilter == '' || $sourceFilter == 'external') {
            require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';

            // Get contacts
            $contacts = $this->liste_contact(-1, 'external', 1, 'WATCHER');
            if (!is_array($contacts))
                return -1;

            foreach ($contacts as $contact_id) {
                $ids_contacts['c' . $contact_id] = $contact_id;
                if ($with_object) {
                    $contact = new Contact($this->db);
                    $contact->fetch($contact_id);
                    $object_contacts['c' . $contact_id] = $contact;
                }
            }
        }

        $this->watcher_ids = array_merge($ids_users, $ids_contacts);
        $this->watcher_list = array_merge($object_users, $object_contacts);
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
        $this->socid = $this->socid > 0 ? $this->socid : 0;
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
        $this->assigned_user_id = $this->assigned_user_id > 0 ? $this->assigned_user_id : 0;
        $this->assigned_usergroup_id = $this->assigned_usergroup_id > 0 ? $this->assigned_usergroup_id : 0;
        $this->notify_assigned_by_email = !empty($this->notify_assigned_by_email) ? 1 : 0;
        $this->date_deadline = $this->date_deadline > 0 ? $this->date_deadline : 0;
        $this->duration = $this->duration > 0 ? $this->duration : 0;
        $this->user_modification_id = $user->id;
        $this->requester_ids = empty($this->requester_ids) ? array() : (is_string($this->requester_ids) ? explode(',', $this->requester_ids) : $this->requester_ids);
        $this->watcher_ids = empty($this->watcher_ids) ? array() : (is_string($this->watcher_ids) ? explode(',', $this->watcher_ids) : $this->watcher_ids);

        // Check parameters
        if (!($this->id > 0)) {
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("TechnicalID"));
            $error++;
        }
        if (empty($this->socid)) {
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("RequestManagerThirdParty"));
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
        if (empty($this->description)) {
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("RequestManagerDescription"));
            $error++;
        }
        if ($this->date_deadline > 0 && $this->date_deadline < $this->date_creation) {
            $this->errors[] = $langs->trans("RequestManagerErrorDeadlineDateInferiorAtCreateDate");
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

		// Update request
		$sql = 'UPDATE ' . MAIN_DB_PREFIX . $this->table_element . ' SET';
        $sql .= " fk_soc = " . $this->socid;
        $sql .= ", label = '" . $this->db->escape($this->label) . "'";
        $sql .= ", description = '" . $this->db->escape($this->description) . "'";
        $sql .= ", fk_type = " . $this->fk_type;
        $sql .= ", fk_category = " . ($this->fk_category > 0 ? $this->fk_category : 'NULL');
        $sql .= ", fk_source = " . ($this->fk_source > 0 ? $this->fk_source : 'NULL');
        $sql .= ", fk_urgency = " . ($this->fk_urgency > 0 ? $this->fk_urgency : 'NULL');
        $sql .= ", fk_impact = " . ($this->fk_impact > 0 ? $this->fk_impact : 'NULL');
        $sql .= ", fk_priority = " . ($this->fk_priority > 0 ? $this->fk_priority : 'NULL');
        $sql .= ", notify_requester_by_email = " . ($this->notify_requester_by_email > 0 ? $this->notify_requester_by_email : 'NULL');
        $sql .= ", notify_watcher_by_email = " . ($this->notify_watcher_by_email > 0 ? $this->notify_watcher_by_email : 'NULL');
        $sql .= ", fk_assigned_user = " . ($this->assigned_user_id > 0 ? $this->assigned_user_id : 'NULL');
        $sql .= ", fk_assigned_usergroup = " . ($this->assigned_usergroup_id > 0 ? $this->assigned_usergroup_id : 'NULL');
        $sql .= ", notify_assigned_by_email = " . ($this->notify_assigned_by_email > 0 ? $this->notify_assigned_by_email : 'NULL');
        $sql .= ", duration = " . $this->duration;
        $sql .= ", date_deadline = " . ($this->date_deadline > 0 ? "'" . $this->db->idate($this->date_deadline) . "'" : 'NULL');
        $sql .= ", fk_user_modif = " . $this->user_modification_id;
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
			$this->db->commit();
            dol_syslog(__METHOD__ . " success", LOG_DEBUG);

			return 1;
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
	 *  Set status request into database
	 *
     * @param   int     $status         New status
     * @param   int     $status         New status type (initial, first in progress, resolved or closed)
     * @param   User    $user           User that modifies
	 * @param   bool    $notrigger      false=launch triggers after, true=disable triggers
     * @param   int     $forcereload    Force reload of the cache
	 * @return  int                     <0 if KO, >0 if OK
	 */
	public function set_status($status=0, $status_type=-1, User $user, $notrigger = false, $forcereload = 0)
	{
        global $langs;
        $error = 0;
        $this->errors = array();
        $langs->load("requestmanager@requestmanager");

        dol_syslog(__METHOD__ . " user_id=" . $user->id . " id=" . $this->id . " status=" . $status, LOG_DEBUG);

        // Clean parameters
        $status = $status > 0 ? $status : 0;
        $status_type = $status_type == self::STATUS_TYPE_INITIAL || $status_type == self::STATUS_TYPE_IN_PROGRESS || $status_type == self::STATUS_TYPE_RESOLVED || $status_type == self::STATUS_TYPE_CLOSED ? $status_type : -1;

        if (empty(self::$status_list) || $forcereload) {
            dol_include_once('/advancedictionaries/class/dictionary.class.php');
            $dictionary = Dictionary::getDictionary($this->db, 'requestmanager', 'requestmanagerstatus');
            $dictionary->fetch_lines(1, array(), array('type' => 'ASC', 'position' => 'ASC'));
            self::$status_list = $dictionary->lines;
        }

        // Check parameters
        if ($status_type >= 0) {
            $found = false;
            foreach (self::$status_list as $s) {
                if ($status_type == $s->fields['type']) {
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
        if ($error) {
            dol_syslog(__METHOD__ . " Errors check parameters: " . $this->errorsToString(), LOG_ERR);
            return -3;
        }

        $this->new_statut = $status;

		// Update request
		$sql = 'UPDATE ' . MAIN_DB_PREFIX . $this->table_element . ' SET';
        $sql .= " fk_status = " . $status;
        $sql .= ' WHERE rowid = '.$this->id;

		$this->db->begin();

        $resql = $this->db->query($sql);
        if (!$resql) {
            $error++;
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
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

		// Commit or rollback
		if ($error) {
			$this->db->rollback();

			return - 1 * $error;
		} else {
			$this->db->commit();
			$this->statut = $status;
            $this->statut_type = self::$status_list[$status]->fields['type'];
            dol_syslog(__METHOD__ . " success", LOG_DEBUG);

			return 1;
		}
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
     *  Return label of status
     *
     * @param   int         $mode       0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long
     * @return  string                  Libelle
     */
    function getLibStatut($mode=0)
    {
        return $this->LibStatut($this->statut, $mode);
    }

    /**
     *  Return label of status provides
     *
     * @param   int         $statut         Id statut
     * @param   int         $mode           0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Picto + Libelle court + Status type picto
     * @param   int         $forcereload    Force reload of the cache
     * @return  string                      Libelle du statut
     */
    function LibStatut($statut,$mode=0,$forcereload=0)
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

        $statutInfos = self::$status_list[$statut];

        $type = $statutInfos->fields['type'];
        $label_short = $label = $statutInfos->fields['label'];
        $picto = $statutInfos->fields['picto'];

        $statuttypepicto = '';
        $statuttypetext = '';
        if ($type == self::STATUS_TYPE_INITIAL) { $statuttypepicto = 'statut0'; $statuttypetext = $langs->trans('RequestManagerTypeInitial'); }
        if ($type == self::STATUS_TYPE_IN_PROGRESS) { $statuttypepicto = 'statut1'; $statuttypetext = $langs->trans('RequestManagerTypeInProgress'); }
        if ($type == self::STATUS_TYPE_RESOLVED) { $statuttypepicto = 'statut3'; $statuttypetext = $langs->trans('RequestManagerTypeResolved'); }
        if ($type == self::STATUS_TYPE_CLOSED) { $statuttypepicto = 'statut4'; $statuttypetext = $langs->trans('RequestManagerTypeClosed'); }
        if ($mode >= 7 && !empty($picto)) { $statuttypepicto = $picto; }
        if ($mode >= 9 && !empty($picto)) { $statuttypetext = $label; }

        if ($mode == 0) return $label;
        if ($mode == 1) return $label_short;
        if ($mode == 2) return img_picto($label_short, $picto) . ' ' . $label_short;
        if ($mode == 3) return img_picto($label, $statuttypepicto);
        if ($mode == 4) return img_picto($label, $picto) . ' ' . $label;
        if ($mode == 5) return img_picto($label, $picto) . ' ' . '<span class="hideonsmartphone">' . $label_short . ' </span>' . img_picto($langs->trans($statuttypetext), $statuttypepicto);
        if ($mode == 6) return img_picto($label, $picto) . ' ' . '<span class="hideonsmartphone">' . $label . ' </span>' . img_picto($langs->trans($statuttypetext), $statuttypepicto);
        if ($mode == 7) return img_picto($label, $statuttypepicto) . ' ' . '<span class="hideonsmartphone">' . $label_short . ' </span>';
        if ($mode == 8) return img_picto($label, $statuttypepicto) . ' ' . '<span class="hideonsmartphone">' . $label . ' </span>';
        if ($mode == 9) return img_picto($statuttypetext, $statuttypepicto) . ' ' . $label_short;
        if ($mode == 10) return img_picto($statuttypetext, $statuttypepicto) . ' ' . $label;
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
            if ($option == '') {
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

        $nb = 0;

        // get all groups for user
        $userGroup = new UserGroup($this->db);
        $userGroupList = $userGroup->listGroupsForUser($user->id);

        // TODO : filter status
        // self::STATUS_TYPE_INITIAL
        // self::STATUS_TYPE_IN_PROGRESS

        // count all assigned requests for user
        $sql  = "SELECT COUNT(rm.rowid) as nb";
        $sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element . " as rm";
        if (count($statusTypeList) > 0) {
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_requestmanager_status as crmst ON crmst.rowid = rm.fk_status";
        }
        $sql .= " WHERE rm.entity IN (" . getEntity('requestmanager') . ")";
        $sql .= " AND (rm.fk_assigned_user = " . $user->id;
        if (count($userGroupList) > 0) {
            $sql .= " OR rm.fk_assigned_usergroup IN (" . implode(',', array_keys($userGroupList)) . ")";
        }
        $sql .= ")";
        if (count($statusTypeList) > 0) {
            $sql.= " AND crmst.type IN (" . implode(',', $statusTypeList) . ")";
        }

        $resql = $this->db->query($sql);
        if ($resql) {
            if ($obj = $this->db->fetch_object($resql)) {
                $nb = intval($obj->nb);
            }
        }

        return $nb;
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
            $this->fetch_requester(1);
            $contactList = $this->requester_list;
        } else if ($idContactType == self::CONTACT_TYPE_ID_WATCHER) {
            $this->fetch_watcher(1);
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
            $this->fetch_requester($withObject, $sourceFilter);
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
            $this->fetch_watcher($withObject, $sourceFilter);
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
     * Create new event in actioncomm for all type of messages
     *
     * @param   int     $typeCode       Code of message type (AC_RM_OUT, AC_RM_IN, etc)
     * @param   string  $label          Label of event
     * @param   string  $note           Note of event
     * @param   bool    $noTransaction  [=FALSE] Use transaction in SQL requests, TRUE to desactivate transaction (ex :for triggers calls)
     * @return  int     <0 if KO, >0 if OK (idAction)
     */
    private function _createActionComm($typeCode, $label, $note, $noTransaction = FALSE)
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

        if (!$noTransaction)    $this->db->begin();
        $idActionComm = $actionCom->create($user);
        if ($idActionComm > 0) {
            if ($actionCom->error) {
                $langs->load("errors");
                $this->error = $actionCom->error;
                $this->errors = $actionCom->errors;
                dol_syslog(get_class($this) . "::createActionComm Error create: " . $this->error, LOG_ERR);
                if (!$noTransaction)    $this->db->rollback();
                return -1;
            }

            // linked object
            $actionCom->id = $idActionComm;
            $actionCom->add_object_linked($this->element, $this->id);
        } else {
            $this->error = $actionCom->error;
            $this->errors = $actionCom->errors;
            dol_syslog(get_class($this) . "::createActionComm Error create: " . $this->error, LOG_ERR);
            if (!$noTransaction)    $this->db->rollback();
            return -1;
        }

        if (!$noTransaction)    $this->db->commit();
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
     * @param   bool    $noTransaction          [=FALSE] Use transaction in SQL requests, TRUE to desactivate transaction (ex :for triggers calls)
     * @return  int     <0 if KO, >0 if OK
     */
    private function _createActionCommAndNotify($actionCommTypeCode, $actionCommLabel, $actionCommNote, $messageNotifyByMail, $messageSubject, $messageBody, $noTransaction = FALSE)
    {
        global $langs, $conf;

        dol_include_once('/requestmanager/class/requestmanagernotification.class.php');

        $error = 0;

        // create new event
        $langs->load('requestmanager@requestmanager');
        if (!$noTransaction)    $this->db->begin();
        $idActionComm = $this->_createActionComm($actionCommTypeCode, $actionCommLabel, $actionCommNote, $noTransaction);
        if ($idActionComm < 0) {
            $error++;
        }

        if (!$error) {
            // user or group assigned to notify (save in database)
            $requestManagerNotification = new RequestManagerNotification($this->db);
            $requestManagerNotification->contactList = $this->getUserToNotifyList(1);

            // if we have at least one user to notify
            if (count($requestManagerNotification->contactList) > 0) {
                if (!empty($conf->global->REQUESTMANAGER_NOTIFICATION_USERS_IN_DB)) {
                    // notify the assigned user if different of user (save in database)
                    $result = $requestManagerNotification->notify($idActionComm, $noTransaction);
                    if ($result < 0) {
                        $error++;
                        $this->error = $requestManagerNotification->error;
                        $this->errors[] = $this->error;
                    }
                }

                if (!empty($conf->global->REQUESTMANAGER_NOTIFICATION_BY_MAIL)) {
                    // send a mail
                    $result = $requestManagerNotification->notifyByMail($messageSubject, $messageBody, 1);
                    if ($result < 0) {
                        $error++;
                        $this->error = $requestManagerNotification->error;
                        $this->errors[] = $this->error;
                    }
                }
            }

            // notify by mail
            if (!$error && $messageNotifyByMail === 1) {
                // send to requesters (sendto) and watchers (copy carbone) to notify
                $atLeastOneContactToNotify = FALSE;
                $requestManagerNotification->contactList = $this->getContactRequestersToNotifyList(1);
                if (count($requestManagerNotification->contactList) > 0) {
                    $atLeastOneContactToNotify = TRUE;
                    $requestManagerNotification->contactCcList = $this->getContactWatchersToNotifyList(1);
                } else {
                    $requestManagerNotification->contactList = $this->getContactWatchersToNotifyList(1);
                    if (count($requestManagerNotification->contactList) > 0) {
                        $atLeastOneContactToNotify = TRUE;
                    }
                }

                if ($atLeastOneContactToNotify) {
                    $result = $requestManagerNotification->notifyByMail($messageSubject, $messageBody);
                    if ($result < 0) {
                        $error++;
                        $this->error = $requestManagerNotification->error;
                        $this->errors[] = $this->error;
                    }
                }
            }
        }

        if ($error) {
            if (!$noTransaction)    $this->db->rollback();
            return -1;
        }

        if (!$noTransaction)    $this->db->commit();
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
            dol_syslog(__METHOD__ . " Error : No template [" . $templateType . "] for this type of request [" . $this->fk_type. "]", LOG_ERR);
        } else {
            if (count($lines) <= 0) {
                $this->error = $langs->trans("RequestManagerErrorNoTemplateLines");
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
     * @param   bool        $noTransaction          [=FALSE] Use transaction in SQL requests, TRUE to desactivate transaction (ex :for triggers calls)
     * @return  int         <0 if KO, >0 if OK
     */
    public function createActionCommAndNotifyFromTemplateType($templateType, $actionCommTypeCode, $noTransaction = FALSE)
    {
        // get substitute values in input message template
        $substituteList = $this->_substituteNotificationMessageTemplate($templateType);
        if ($this->error) {
            return -1;
        }

        // create event and notify users and send mail to contacts requesters and watchers (if notified)
        $result = $this->_createActionCommAndNotify($actionCommTypeCode, $substituteList['subject'], $substituteList['boby'], 1, $substituteList['subject'], $substituteList['boby'], $noTransaction);
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
     * @param   bool        $noTransaction          [=FALSE] Use transaction in SQL requests, TRUE to desactivate transaction (ex :for triggers calls)
     * @return  int         <0 if KO, >0 if OK
     */
    public function createActionCommAndNotifyFromTemplateTypeWithMessage($templateType, $actionCommTypeCode,  $messageNotifyByMail, $messageSubject, $messageBody, $noTransaction = FALSE)
    {
        // get substitute values in input message template
        $substituteList = $this->_substituteNotificationMessageTemplate($templateType);
        if ($this->error) {
            return -1;
        }

        // create event and notify users and send mail to contacts requesters and watchers (if notified)
        $result = $this->_createActionCommAndNotify($actionCommTypeCode, $substituteList['subject'], $substituteList['boby'], $messageNotifyByMail, $messageSubject, $messageBody, $noTransaction);
        if ($result < 0) {
            return -1;
        }

        return 1;
    }
}