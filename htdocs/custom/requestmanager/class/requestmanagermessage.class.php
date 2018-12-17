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
 * \file    htdocs/requestmanager/class/requestmanagermessage.class.php
 * \ingroup requestmanager
 * \brief
 */

require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';


/**
 * Class RequestManagerMessage
 *
 * Put here description of your class
 * @see ActionComm
 */
class RequestManagerMessage extends ActionComm
{
    public $element = 'requestmanager_requestmanagermessage';
    public $table_element = 'requestmanager_message';

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
        "message_type" => '', "notify_assigned" => '', "notify_requesters" => '', "notify_watchers" => '', "knowledge_base_ids" => '',
        "id" => '', "ref" => '', "type_id" => '', "type_code" => '', "type" => '', "type_color" => '', "code" => '',
        "label" => '', "datec" => '', "datem" => '', "authorid" => '', "usermodid" => '', "datep" => '', "datef" => '',
        "userassigned" => '', "userownerid" => '', "userdoneid" => '', "socid" => '', "contactid" => '', "contact" => '',
        "array_options" => '', "fk_project" => '', "ref_ext" => '', "note" => '', "type_picto" => '', "user_mod" => '',
        "user_done" => '', "user_owner" => '', "thirdparty" => '', "entity" => '',
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

    /**
     * RequestManager handle
     * @var RequestManager
     */
    public $requestmanager;

    /**
     * Message type (Out, Private or In)
     * @var int
     */
    public $message_type;

    /**
     * Notify assigned (Used in triggers)
     * @var int
     */
    public $notify_assigned;
    /**
     * Notify requester (Used in triggers)
     * @var int
     */
    public $notify_requesters;
    /**
     * Notify watchers (Used in triggers)
     * @var int
     */
    public $notify_watchers;

    /**
     * Attached files (Can be used in triggers)
     * @var int
     */
    public $attached_files;

    /**
     * List knowledge base ID
     * @var string[]
     */
    public $knowledge_base_ids;
    /**
     * List knowledge base object
     * @var DictionaryLine[]
     */
    public $knowledge_base_list;

    /**
     * Message types
     */
    const MESSAGE_TYPE_OUT = 0;
    const MESSAGE_TYPE_PRIVATE = 1;
    const MESSAGE_TYPE_IN = 2;

    /**
     *  Add an action/event into database.
     *  $this->type_id OR $this->type_code must be set.
     *
     * @param	User	        $user      		    Object user making action
     * @param   int		        $notrigger		    1 = disable triggers, 0 = enable triggers
     * @return  int 		        	            Id of created event, < 0 if KO
     */
    public function create(User $user, $notrigger = 0)
    {
        global $langs;

        $now = dol_now();

        // Clean parameters
        $this->message_type = $this->message_type != self::MESSAGE_TYPE_PRIVATE && $this->message_type != self::MESSAGE_TYPE_IN ? self::MESSAGE_TYPE_OUT : $this->message_type;
        $this->notify_assigned = !empty($this->notify_assigned) ? 1 : 0;
        $this->notify_requesters = !empty($this->notify_requesters) ? 1 : 0;
        $this->notify_watchers = !empty($this->notify_watchers) ? 1 : 0;
        $this->attached_files = is_array($this->attached_files) ? $this->attached_files : array();
        $this->knowledge_base_ids = is_array($this->knowledge_base_ids) ? $this->knowledge_base_ids : (is_string($this->knowledge_base_ids) ? explode(',', $this->knowledge_base_ids) : array());

        // Check parameters
        $error = 0;

        $langs->load("requestmanager@requestmanager");
        if (!($this->requestmanager->id > 0)) {
            $this->errors[] = $langs->trans("ErrorBadParameters") . ': ' . $langs->trans("RequestManagerRequest");
            $error++;
        }
        if (empty($this->note)) {
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("RequestManagerMessage"));
            $error++;
        }
        if (empty($this->label) && $this->message_type == 'AC_RM_OUT') {
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("RequestManagerSubject"));
            $error++;
        }
        if ($error) {
            dol_syslog(__METHOD__ . " requestmanager_message Errors: " . $this->errorsToString(), LOG_ERR);
            return -4;
        }

        switch ($this->message_type) {
            case self::MESSAGE_TYPE_PRIVATE:
                $this->type_code = 'AC_RM_PRIV';
                $this->notify_requesters = 0;
                $this->notify_watchers = 0;
                $this->label = $langs->trans('RequestManagerMessageTitlePrivate', $this->requestmanager->ref);
                break;
            case self::MESSAGE_TYPE_IN:
                $this->type_code = 'AC_RM_IN';
                $this->notify_requesters = 0;
                $this->notify_watchers = 0;
                if (empty($this->label)) $this->label = $langs->trans('RequestManagerMessageTitleIn', $this->requestmanager->ref);
                break;
            default:
                $this->type_code = 'AC_RM_OUT';
                break;
        }

        $this->fk_project = 0;
        $this->datep = $now;
        $this->datef = $now;
        $this->fulldayevent = 0;
        $this->durationp = 0;
        $this->punctual = 1;
        $this->percentage = -1;           // Not applicable
        $this->transparency = 0;          // Not applicable
        $this->authorid = $user->id;      // User saving action
        $this->userownerid = $user->id;   // User saving action
        $this->elementtype = $this->requestmanager->element;
        $this->fk_element = $this->requestmanager->id;
        $this->socid = $user->socid > 0 && ($user->socid == $this->requestmanager->socid_origin || $user->socid == $this->requestmanager->socid || $user->socid == $this->requestmanager->socid_benefactor) ? $user->socid : $this->requestmanager->socid;

        $this->db->begin();

        $result = parent::create($user);
        if ($result > 0) {
            $error = 0;

            // Insert further information
            $sql = "INSERT INTO " . MAIN_DB_PREFIX . "requestmanager_message (";
            $sql .= "  fk_actioncomm";
            $sql .= ", notify_assigned";
            $sql .= ", notify_requesters";
            $sql .= ", notify_watchers";
            $sql .= ") VALUES (";
            $sql .= "  " . $result;
            $sql .= ", " . ($this->notify_assigned ? 1 : 'NULL');
            $sql .= ", " . ($this->notify_requesters ? 1 : 'NULL');
            $sql .= ", " . ($this->notify_watchers ? 1 : 'NULL');
            $sql .= ")";

            dol_syslog(__METHOD__ . " requestmanager_message", LOG_DEBUG);
            $result = $this->db->query($sql);
            if (!$result) {
                $error++;
                $this->errors[] = $this->db->lasterror();
                dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
            }

            if (!$error) {
                $result = $this->set_knowledge_base($this->knowledge_base_ids);
                if (!$result) {
                    $error++;
                }
            }

            if (!$error) {
                $result = $this->add_files($this->attached_files);
                if (!$result) {
                    $error++;
                }
            }

            if (!$error && !$notrigger) {
                // Call trigger
                $result = $this->call_trigger('REQUESTMANAGERMESSAGE_CREATE', $user);
                if ($result < 0) {
                    $error++;
                }
                // End call triggers
            }

            if (!$error) {
                $this->db->commit();
                return $this->id;
            } else {
                $this->db->rollback();
                return -$error;
            }
        }

        return $result;
    }

    /**
     *  Update action into database
     *  If percentage = 100, on met a jour date 100%
     *
     * @param    User	$user			Object user making change
     * @param    int	$notrigger		1 = disable triggers, 0 = enable triggers
     * @return   int     				<0 if KO, >0 if OK
     */
    function update($user, $notrigger=0)
    {
        global $langs;

        // Clean parameters
        $this->message_type = $this->message_type != self::MESSAGE_TYPE_PRIVATE && $this->message_type != self::MESSAGE_TYPE_IN ? self::MESSAGE_TYPE_OUT : $this->message_type;
        $this->notify_assigned = !empty($this->notify_assigned) ? 1 : 0;
        $this->notify_requesters = !empty($this->notify_requesters) ? 1 : 0;
        $this->notify_watchers = !empty($this->notify_watchers) ? 1 : 0;
        $this->attached_files = is_array($this->attached_files) ? $this->attached_files : array();
        $this->knowledge_base_ids = is_array($this->knowledge_base_ids) ? $this->knowledge_base_ids : (is_string($this->knowledge_base_ids) ? explode(',', $this->knowledge_base_ids) : array());

        // Check parameters
        $error = 0;

        $langs->load("requestmanager@requestmanager");
        if (!($this->requestmanager->id > 0)) {
            $this->errors[] = $langs->trans("ErrorBadParameters") . ': ' . $langs->trans("RequestManagerRequest");
            $error++;
        }
        if (empty($this->note)) {
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("RequestManagerMessage"));
            $error++;
        }
        if ($error) {
            dol_syslog(__METHOD__ . " requestmanager_message Errors: " . $this->errorsToString(), LOG_ERR);
            return -4;
        }

        switch ($this->message_type) {
            case self::MESSAGE_TYPE_PRIVATE:
                $this->type_code = 'AC_RM_PRIV';
                $this->label = $langs->trans('RequestManagerMessageTitlePrivate', $this->requestmanager->ref);
                break;
            case self::MESSAGE_TYPE_IN:
                $this->type_code = 'AC_RM_IN';
                $this->label = $langs->trans('RequestManagerMessageTitleIn', $this->requestmanager->ref);
                break;
            default:
                $this->type_code = 'AC_RM_OUT';
                $this->label = $langs->trans('RequestManagerMessageTitleOut', $this->requestmanager->ref);
                break;
        }

        $this->db->begin();

        $result = parent::update($user);
        if ($result > 0) {
            $error = 0;

            // Insert further information
            $sql = "UPDATE " . MAIN_DB_PREFIX . "requestmanager_message";
            $sql .= " SET";
            $sql .= "   notify_assigned = " . $this->notify_assigned;
            $sql .= " , notify_requesters = " . $this->notify_requesters;
            $sql .= " , notify_watchers = " . $this->notify_watchers;
            $sql .= " WHERE fk_actioncomm = " . $this->id;

            dol_syslog(__METHOD__ . " requestmanager_message", LOG_DEBUG);
            $result = $this->db->query($sql);
            if (!$result) {
                $error++;
                $this->errors[] = $this->db->lasterror();
                dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
            }

            if (!$error) {
                $result = $this->set_knowledge_base($this->knowledge_base_ids);
                if (!$result) {
                    $error++;
                }
            }

            if (!$error) {
                $result = $this->add_files($this->attached_files);
                if (!$result) {
                    $error++;
                }
            }

            if (!$error && !$notrigger) {
                // Call trigger
                $result = $this->call_trigger('REQUESTMANAGERMESSAGE_MODIFY', $user);
                if ($result < 0) {
                    $error++;
                }
                // End call triggers
            }

            if (!$error) {
                $this->db->commit();
                return 1;
            } else {
                $this->db->rollback();
                return -$error;
            }
        }

        return $result;
    }

    /**
     *    Delete event from database
     *
     *    @param    int		$notrigger		1 = disable triggers, 0 = enable triggers
     *    @return   int 					<0 if KO, >0 if OK
     */
    function delete($notrigger=0)
    {
        global $user, $conf;
        $this->db->begin();
        $error = 0;

        dol_syslog(__METHOD__ . ' requestmanager_message', LOG_DEBUG);

        if (!$notrigger) {
            // Call trigger
            $result = $this->call_trigger('REQUESTMANAGERMESSAGE_DELETE', $user);
            if ($result < 0) {
                $error++;
            }
            // End call triggers
        }

        // Removed further information
        if (!$error) {
            $sql = "DELETE FROM " . MAIN_DB_PREFIX . "requestmanager_message_knowledge_base WHERE fk_actioncomm = " . $this->id;
            $result = $this->db->query($sql);
            if (!$result) {
                $error++;
                $this->errors[] = $this->db->lasterror();
                dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
            }
        }

        // Removed further information
        if (!$error) {
            $sql = "DELETE FROM " . MAIN_DB_PREFIX . "requestmanager_message WHERE fk_actioncomm = " . $this->id;
            $result = $this->db->query($sql);
            if (!$result) {
                $error++;
                $this->errors[] = $this->db->lasterror();
                dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
            }
        }

        if (!$error) {
            $result = parent::delete();
            if ($result < 0) {
                $error -= $result;
            }
        }

        if (!$error) {
            $this->db->commit();
            return 1;
        } else {
            $this->db->rollback();
            return -$error;
        }
    }

    /**
     *    Load object from database
     *
     *    @param	int		$id     	Id of action to get
     *    @param	string	$ref    	Ref of action to get
     *    @param	string	$ref_ext	Ref ext to get
     *    @return	int					<0 if KO, >0 if OK
     */
    function fetch($id, $ref='',$ref_ext='')
    {
        dol_syslog(__METHOD__ . " requestmanager_message id=" . $id . " ref=" . $ref . " ref_ext=" . $ref_ext);

        $result = parent::fetch($id, $ref, $ref_ext);
        if ($result > 0 && $this->id > 0) {
            $sql = "SELECT notify_assigned, notify_requesters, notify_watchers";
            $sql .= " FROM " . MAIN_DB_PREFIX . "requestmanager_message";
            $sql .= " WHERE fk_actioncomm = " . $this->id;

            $resql = $this->db->query($sql);
            if ($resql) {
                if ($obj = $this->db->fetch_object($resql)) {
                    $this->notify_assigned = $obj->notify_assigned;
                    $this->notify_requesters = $obj->notify_requesters;
                    $this->notify_watchers = $obj->notify_watchers;
                    $this->fetch_message_type();

                    $this->db->free($resql);

                    return 1;
                } else {
                    return 0;
                }
            } else {
                $this->error = $this->db->lasterror();
                return -1;
            }
        }

        return $result;
    }

    /**
     *  Load the request manager
     *
     * @return  int     <0 if KO, >0 if OK
     */
    function fetch_requestmanager()
    {
        dol_include_once('/requestmanager/class/requestmanager.class.php');
        $this->requestmanager = null;

        $requestmanager = new RequestManager($this->db);
        if ($this->fk_element > 0 && $this->elementtype == $requestmanager->element) {
            $requestmanager->fetch($this->fk_element);
            $this->requestmanager = $requestmanager;
        } else {
            return 0;
        }

        return 1;
    }

    /**
     *  Load the knowledge base
     *
     * @param   int     $with_object    Load also the object
     * @return  int     <0 if KO, >0 if OK
     */
    function fetch_knowledge_base($with_object=0)
    {
        $this->knowledge_base_ids = array();
        $this->knowledge_base_list = array();

        // Get contacts
        $sql = 'SELECT fk_knowledge_base FROM '.MAIN_DB_PREFIX.'requestmanager_message_knowledge_base WHERE fk_actioncomm = '.$this->id;
        $resql = $this->db->query($sql);
        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $this->knowledge_base_ids[] = $obj->fk_knowledge_base;
            }
            $this->db->free($resql);
        }

        if ($with_object && count($this->knowledge_base_ids) > 0) {
            dol_include_once('/advancedictionaries/class/dictionary.class.php');
            $dictionary = Dictionary::getDictionary($this->db, 'requestmanager', 'requestmanagerknowledgebase');
            $lines = $dictionary->fetch_lines(-1, array('rowid' => $this->knowledge_base_ids));
            if ($lines > 0) {
                $this->knowledge_base_list = $dictionary->lines;
            }
        }
    }

    /**
     *  Set knowledge base of this message
     *
     * @param	array	$knowledge_base_ids	    List of knowledge base ID
     * @return	int					            <0 if KO, >0 if OK
     */
    function set_knowledge_base($knowledge_base_ids)
    {
        // Clean parameters
        $knowledge_base_ids = is_array($knowledge_base_ids) ? $knowledge_base_ids : (is_string($knowledge_base_ids) ? explode(',', $knowledge_base_ids) : array());

        dol_syslog(__METHOD__ . " id=" . $this->id . " knowledge_base_ids=" . json_encode($knowledge_base_ids));

        // Delete old values
        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "requestmanager_message_knowledge_base WHERE fk_actioncomm = " . $this->id;
        $resql = $this->db->query($sql);

        // Insert new values
        foreach ($knowledge_base_ids as $knowledge_base_id) {
            $sql = "INSERT INTO " . MAIN_DB_PREFIX . "requestmanager_message_knowledge_base (fk_actioncomm, fk_knowledge_base)" .
                " VALUES (" . $this->id . ", " . $knowledge_base_id . ")";

            $resql = $this->db->query($sql);
            if (!$resql) {
                $this->errors[] = $this->db->lasterror();
                dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
                return -1;
            }
        }

        return 1;
    }

    /**
     *  Add attached files in documents of the message
     *
     * @param	array	$attached_files	    List of knowledge base ID
     * @return	int					            <0 if KO, >0 if OK
     */
    function add_files($attached_files)
    {
        global $conf;

        if (is_array($attached_files) && array_key_exists('paths', $attached_files) && count($attached_files['paths']) > 0) {
            require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
            foreach ($attached_files['paths'] as $key => $filespath) {
                $srcfile = $filespath;
                $destdir = $conf->agenda->dir_output . '/' . $this->id;
                $destfile = $destdir . '/' . $attached_files['names'][$key];
                if (dol_mkdir($destdir) >= 0) {
                    dol_copy($srcfile, $destfile);
                }
            }
        }

        return 1;
    }

    /**
     *  Fetch message type
     *
     * @return void
     */
    function fetch_message_type()
    {
        $this->message_type = -1;

        switch ($this->type_code) {
            case 'AC_RM_PRIV':
                $this->message_type = self::MESSAGE_TYPE_PRIVATE;
                break;
            case 'AC_RM_IN':
                $this->message_type = self::MESSAGE_TYPE_IN;
                break;
            case 'AC_RM_OUT':
                $this->message_type = self::MESSAGE_TYPE_OUT;
                break;
        }
    }

    /**
     *  Return label of message type
     *
     * @return  string                  Label
     */
    function getMessageType()
    {
        global $langs;

        $langs->load("requestmanager@requestmanager");

        switch ($this->type_code) {
            case 'AC_RM_PRIV':
            case 'AC_RM_IN':
            case 'AC_RM_OUT':
                return $langs->trans('Action'.$this->type_code);
            default:
                return $langs->trans('RequestManagerErrorNotFound');
        }
    }
}