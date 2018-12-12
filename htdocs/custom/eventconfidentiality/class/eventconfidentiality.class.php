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
 * \file    eventconfidentiality/class/eventconfidentiality.class.php
 * \ingroup eventconfidentiality
 * \brief
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

/**
 * Class EventConfidentiality
 */
class EventConfidentiality extends CommonObject
{
	public $element = 'eventconfidentiality';
	public $table_element = 'eventconfidentiality_mode';
    protected $ismultientitymanaged = 1;	// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe

    /**
     * @var int     Id of the event
     */
    public $fk_actioncomm;
    /**
     * @var int     Id of the tag
     */
    public $fk_c_eventconfidentiality_tag;
    /**
     * @var int     Confidentiality mode of the tag
     */
    public $mode;
    /**
     * @var string  Label of the confidentiality mode of the tag
     */
    public $mode_label;
    /**
     * @var string  Label of the tag
     */
    public $label;
    /**
     * @var int     Is the tag external ?
     */
    public $external;

    /**
     * @var DictionaryLine[]
     */
    protected static $tags_cached = array();

    /**
     * @var array   List of properties blurred array('property_name' => array('input_html_name', ...), ...)
     */
    public static $blurred_properties = array(
        //'datec' => array(),
        //'datem' => array(),
        //'datep' => array('ap', 'apmonth', 'apday', 'apyear', 'apyear', 'aphour', 'apmin', 'apButton', 'apButtonNow'),
        //'datef' => array('p2', 'p2month', 'p2day', 'p2year', 'p2year', 'p2hour', 'p2min', 'p2Button', 'p2ButtonNow'),
        //'fulldayevent' => array('fullday'),
        //'fk_action' => array(),
        // 'type' => array(),
        'code' => array('actioncode'),
        'note' => array('note'),
        //'date_start_in_calendar' => array(),
        //'date_end_in_calendar' => array(),
        //'type_code' => array(),
        //'type_label' => array(),
        'dp' => array(),
        'dp2' => array(),
		'email_subject' => array()
    );

    /**
     * @var array     List of the label of the confidentiality mode of the tag
     */
    public $mode_labels = array();

    // Mode of a confidentiality tag
    const MODE_VISIBLE = 0;
    const MODE_BLURRED = 1;
    const MODE_HIDDEN = 2;

    /**
	 * Constructor
	 *
	 * @param   DoliDb      $db     Database handler
	 */
	public function __construct(DoliDB $db)
	{
	    global $langs;

	    $langs->load('eventconfidentiality@eventconfidentiality');
		$this->db = $db;

        $this->mode_labels = array(
            self::MODE_VISIBLE => $langs->trans('EventConfidentialityModeVisible'),
            self::MODE_BLURRED => $langs->trans('EventConfidentialityModeBlurred'),
            self::MODE_HIDDEN => $langs->trans('EventConfidentialityModeHidden'),
        );
	}

	/**
     *  Add a confidentiality tag of a event
     *
     * @param   User    $user       Object user making action
     * @return  int                 Id of created event, < 0 if KO
     */
    public function create(User $user)
    {
        $error = 0;

        $this->db->begin();
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . $this->table_element;
        $sql .= "(fk_actioncomm";
        $sql .= ",fk_c_eventconfidentiality_tag";
        $sql .= ",mode";
        $sql .= ") VALUES (";
        $sql .= $this->fk_actioncomm . ",";
        $sql .= $this->fk_c_eventconfidentiality_tag . ",";
        $sql .= $this->mode;
        $sql .= ")";

        dol_syslog(__METHOD__, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->error = $this->db->lasterror();
            $error++;
        }

        if (!$error) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . $this->table_element, "rowid");
        }

        if (!$error) {
            $this->db->commit();
            return 1;
        } else {
            dol_syslog(__METHOD__ . ' SQL: ' . $sql . '; Error:' . $this->error, LOG_ERR);
            $this->db->rollback();
            return -1;
        }
    }

	/**
     *  Load an confidentiality tag of a action
     *
     * @param	int		$id     	    Id of confidentiality tag of a action
     * @param	int		$action_id     	Id of the action
     * @param	int		$tag_id     	Id of the tag
     *
     * @return  int                     <0 if KO, 0 if not found, >0 if OK
     */
    function fetch($id, $action_id=0, $tag_id=0)
    {
        $sql = "SELECT";
        $sql .= " ecm.rowid, ecm.fk_actioncomm, ecm.fk_c_eventconfidentiality_tag, ecm.mode, cect.label, cect.external";
        $sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element . " AS ecm";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_eventconfidentiality_tag AS cect ON ecm.fk_c_eventconfidentiality_tag = cect.rowid";
        if ($this->fk_actioncomm > 0 && $this->fk_c_eventconfidentiality_tag > 0) {
            $sql .= " WHERE ecm.fk_actioncomm = " . $action_id;
            $sql .= " AND ecm.fk_c_eventconfidentiality_tag = " . $tag_id;
        } else {
            $sql .= " WHERE ecm.rowid = " . $id;
        }

        dol_syslog(__METHOD__, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            if ($num) {
                $obj = $this->db->fetch_object($resql);

                $this->id = $obj->rowid;
                $this->fk_actioncomm = $obj->fk_actioncomm;
                $this->fk_c_eventconfidentiality_tag = $obj->fk_c_eventconfidentiality_tag;
                $this->mode = $obj->mode;
                $this->mode_label = $this->mode_labels[$obj->mode];
                $this->label = $obj->label;
                $this->external = $obj->external;

                $this->db->free($resql);
                return 1;
            } else {
                return 0;
            }
        } else {
            $this->error = $this->db->lasterror();
            dol_syslog(__METHOD__ . ' SQL: ' . $sql . '; Error:' . $this->error, LOG_ERR);
            return -1;
        }
    }

	/**
     *  Update a confidentiality tag of a event
     *
     * @param	User    $user       Object user making action
     * @return  int                 >0 if OK, <0 if KO
     */
    public function update(User $user)
    {
        $error = 0;

        $this->db->begin();
        $sql = "UPDATE " . MAIN_DB_PREFIX . $this->table_element;
        $sql .= " SET mode = " . $this->mode;
        if ($this->fk_actioncomm > 0 && $this->fk_c_eventconfidentiality_tag > 0) {
            $sql .= " WHERE fk_actioncomm = " . $this->fk_actioncomm;
            $sql .= " AND fk_c_eventconfidentiality_tag = " . $this->fk_c_eventconfidentiality_tag;
        } else {
            $sql .= " WHERE rowid = " . $this->id;
        }

        dol_syslog(__METHOD__, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->error = $this->db->lasterror();
            $error++;
        }

        if (!$error) {
            $this->db->commit();
            return 1;
        } else {
            $this->db->rollback();
            dol_syslog(__METHOD__ . ' SQL: ' . $sql . '; Error:' . $this->error, LOG_ERR);
            return -1;
        }
    }

    /**
     *  Delete a confidentiality tag of a event
     *
     * @return  int                 <0 if KO, >0 if OK
     */
    function delete()
    {
        $error = 0;

        $this->db->begin();

        $sql = "DELETE FROM " . MAIN_DB_PREFIX . $this->table_element;
        if ($this->fk_actioncomm > 0 && $this->fk_c_eventconfidentiality_tag > 0) {
            $sql .= " WHERE fk_actioncomm = " . $this->fk_actioncomm;
            $sql .= " AND fk_c_eventconfidentiality_tag=" . $this->fk_c_eventconfidentiality_tag;
        } elseif ($this->fk_actioncomm > 0) {
            $sql .= " WHERE fk_actioncomm = " . $this->fk_actioncomm;
        } else {
            $sql .= " WHERE rowid = " . $this->id;
        }

        dol_syslog(__METHOD__, LOG_DEBUG);
        $res = $this->db->query($sql);
        if ($res < 0) {
            $this->error = $this->db->lasterror();
            $error++;
        }

        if (!$error) {
            $this->db->commit();
            return 1;
        } else {
            $this->db->rollback();
            dol_syslog(__METHOD__ . ' SQL: ' . $sql . '; Error:' . $this->error, LOG_ERR);
            return -1;
        }
    }

    /**
     *  Get default tags information for an origin and an action type
     *
     * @param   string      $elementtype    Element type linked to the event (if empty is a event)
     * @param   int         $type_id        Id of the event type (search all if = 0)
     *
     * @return  array|int                   <0 if not OK, else list of default tags with information
     */
    function getDefaultTags($elementtype = '', $type_id=0)
    {
        global $conf;

        if (empty($elementtype)) $elementtype = 'ec_event';

        // Get tags
        dol_include_once('/advancedictionaries/class/dictionary.class.php');
        $tags_dictionary = Dictionary::getDictionary($this->db, 'eventconfidentiality', 'eventconfidentialitytag');
        $result = $tags_dictionary->fetch_lines(1, array(), array('label' => 'ASC'));
        if ($result < 0) {
            $this->error = $tags_dictionary->error;
            $this->errors = $tags_dictionary->errors;
            dol_syslog(__METHOD__ . ' Error:' . $this->errorsToString(), LOG_ERR);
            return -1;
        }

        // Get action types
        $cActionList = array();
        $sql = "SELECT id, code, module, type";
        $sql .= " FROM " . MAIN_DB_PREFIX . "c_actioncomm";
        if ($type_id > 0) $sql .= " WHERE id = " . $type_id;
        else $sql .= " WHERE active = 1";
        $sql .= " ORDER BY code";

        $resql = $this->db->query($sql);
        if ($resql) {
            $onlyautoornot = empty($conf->global->AGENDA_USE_EVENT_TYPE) ? 1 : 0;

            while ($obj = $this->db->fetch_object($resql)) {
                $qualified = 1;

                // $obj->type can be system, systemauto, module, moduleauto, xxx, xxxauto
                if ($qualified && $onlyautoornot > 0 && preg_match('/^system/', $obj->type) && !preg_match('/^AC_OTH/', $obj->code)) $qualified = 0;    // We discard detailed system events. We keep only the 2 generic lines (AC_OTH and AC_OTH_AUTO)

                if ($qualified && $obj->module) {
                    if ($obj->module == 'invoice' && !$conf->facture->enabled) $qualified = 0;
                    if ($obj->module == 'order' && !$conf->commande->enabled) $qualified = 0;
                    if ($obj->module == 'propal' && !$conf->propal->enabled) $qualified = 0;
                    if ($obj->module == 'invoice_supplier' && !$conf->fournisseur->enabled) $qualified = 0;
                    if ($obj->module == 'order_supplier' && !$conf->fournisseur->enabled) $qualified = 0;
                    if ($obj->module == 'shipping' && !$conf->expedition->enabled) $qualified = 0;
                }

                if ($qualified || $type_id > 0) {
                    $cActionList[$obj->code] = $obj->id;
                }
            }

            $this->db->free($resql);
        } else {
            $this->error = $this->db->lasterror();
            dol_syslog(__METHOD__ . ' Error:' . $this->errorsToString(), LOG_ERR);
            return -1;
        }

        // Get default tags
        $default_tags_dictionary = Dictionary::getDictionary($this->db, 'eventconfidentiality', 'eventconfidentialitydefault');
        $result = $default_tags_dictionary->fetch_lines(1);
        if ($result < 0) {
            $this->error = $tags_dictionary->error;
            $this->errors = $tags_dictionary->errors;
            dol_syslog(__METHOD__ . ' Error:' . $this->errorsToString(), LOG_ERR);
            return -1;
        }
        $default_tags = array();
        foreach ($cActionList as $action_type_code => $action_type_id) {
            foreach ($default_tags_dictionary->lines as $line) {
                if ((empty($line->fields['action_type']) || in_array($action_type_id, array_filter(array_map('trim', explode(',', $line->fields['action_type'])), 'strlen'))) &&
                    (empty($line->fields['element_origin']) || in_array($elementtype, array_filter(array_map('trim', explode(',', $line->fields['element_origin'])), 'strlen')))
                ) {
                    foreach (array_filter(array_map('trim', explode(',', $line->fields['tags'])), 'strlen') as $tag_id) {
                        $default_tags[$action_type_id][$tag_id] = $line->fields['mode'];
                    }
                }
            }
        }

        // Set result
        $tags = array();
        foreach ($cActionList as $action_type_code => $action_type_id) {
            foreach ($tags_dictionary->lines as $line) {
                $mode = isset($default_tags[$action_type_id][$line->id]) ? $default_tags[$action_type_id][$line->id] : ($conf->global->{'EVENTCONFIDENTIALITY_DEFAULT_' . (!empty($line->fields['external']) ? 'EXTERNAL' : 'INTERNAL') . '_TAG'} == $line->id ? $conf->global->{'EVENTCONFIDENTIALITY_DEFAULT_' . (!empty($line->fields['external']) ? 'EXTERNAL' : 'INTERNAL') . '_MODE'} : self::MODE_HIDDEN);
                $tags[$action_type_code][$line->id] = array(
                    'label' => $line->fields['label'],
                    'mode' => $mode,
                    'mode_label' => $this->mode_labels[$mode],
                    'external' => !empty($line->fields['external']) ? 1 : 0,
                );
            }
        }

        return $tags;
    }

    /**
     *  Load all tags information for an action
     *
     * @param   int         $id     Id of the event type
     *
     * @return  array|int           <0 if not OK, else list of tags with information
     */
    function fetchAllTagsOfEvent($id)
    {
        // Get tags
        dol_include_once('/advancedictionaries/class/dictionary.class.php');
        $tags_dictionary = Dictionary::getDictionary($this->db, 'eventconfidentiality', 'eventconfidentialitytag');
        $result = $tags_dictionary->fetch_lines(1, array(), array('label' => 'ASC'));
        if ($result < 0) {
            $this->error = $tags_dictionary->error;
            $this->errors = $tags_dictionary->errors;
            dol_syslog(__METHOD__ . ' Error:' . $this->errorsToString(), LOG_ERR);
            return -1;
        }

        // Get tags set
        $tags_set = array();
        $sql = "SELECT fk_c_eventconfidentiality_tag, mode";
        $sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element;
        $sql .= " WHERE fk_actioncomm = " . $id;

        $resql = $this->db->query($sql);
        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $tags_set[$obj->fk_c_eventconfidentiality_tag] = $obj->mode;
            }
            $this->db->free($resql);
        } else {
            $this->error = $this->db->lasterror();
            dol_syslog(__METHOD__ . ' SQL: ' . $sql . '; Error:' . $this->errorsToString(), LOG_ERR);
            return -1;
        }

        // Set result
        $tags = array();
        foreach ($tags_dictionary->lines as $line) {
            $mode = isset($tags_set[$line->id]) ? $tags_set[$line->id] : self::MODE_HIDDEN;
            $tags[$line->id] = array(
                'label' => $line->fields['label'],
                'mode' => $mode,
                'mode_label' => $this->mode_labels[$mode],
                'external' => !empty($line->fields['external']) ? 1 : 0,
            );
        }

        return $tags;
    }

    /**
     *  Get mode for an user and an event
     *
     * @param   User        $user           User handler
     * @param   int         $event_id       Id of the event
     *
     * @return  int                         <0 if not OK, else mode for the user and the event
     */
    function getModeForUserAndEvent(&$user, $event_id)
    {
        // Get user tags
        $user_tags = $this->getConfidentialTagsOfUser($user);
        if (!is_array($user_tags)) {
            return -1;
        }

        // Is external user ?
        $external_user = $user->socid > 0 ? 1 : 0;

        // Get tags set
        $tags_set = $this->fetchAllTagsOfEvent($event_id);
        if (!is_array($tags_set)) {
            return -1;
        }

        // Get the maximal visibility for all the tags set with the user tags
        $mode = null;
        foreach ($tags_set as $tag_id => $tag) {
            if (in_array($tag_id, $user_tags) && $tag['external'] == $external_user) {
                if (!isset($mode)) $mode = EventConfidentiality::MODE_HIDDEN;
                $mode = min($mode, $tag['mode']);
            }
        }

        // If mode not found then set to hidden mode for external user and visible for internal user
        if (!isset($mode)) $mode = $external_user ? EventConfidentiality::MODE_HIDDEN : EventConfidentiality::MODE_VISIBLE;

        // If mode is hidden and the user as the right to manage the tags then set to visible mode
        if ($user->rights->eventconfidentiality->manage && $mode == EventConfidentiality::MODE_HIDDEN) $mode = EventConfidentiality::MODE_VISIBLE;

        return $mode;
    }

    /**
     *  Get confidential tags for an user
     *
     * @param   User        $user           User handler
     *
     * @return  int|array                   <0 if not OK, else confidential tags for an user
     */
    function getConfidentialTagsOfUser(&$user)
    {
        if (empty($user->array_options) && $user->id > 0) {
            $user->fetch_optionals();
        }

        // Get user tags
        $user_tags = array_filter(array_map('trim', explode(',', $user->array_options['options_user_tag'])), 'strlen');

        // Get groups of the user
        require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';
        $usergroup = new UserGroup($this->db);
        $usergroups = $usergroup->listGroupsForUser($user->id);
        if (!is_array($usergroups)) {
            $this->error = $usergroup->error;
            $this->errors = $usergroup->errors;
            return -1;
        }

        // Get groups tags
        foreach ($usergroups as $group) {
            $user_tags = array_merge($user_tags, array_filter(array_map('trim', explode(',', $group->array_options['options_group_tag'])), 'strlen'));
        }

        return array_unique($user_tags);
    }
}