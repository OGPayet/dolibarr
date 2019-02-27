<?php
/* Copyright (C) 2007-2012  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2014       Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2015       Florian Henry       <florian.henry@open-concept.pro>
 * Copyright (C) 2015       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2017       Open-DSI            <support@open-dsi.fr>
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
 *  \file       htdocs/quicklist/class/quicklist.class.php
 *  \ingroup    quicklist
 *  \brief      Fichier des classes de quicklist
 */
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';

/**
 *  Class to manage quicklist
 */
class QuickList extends CommonObject
{
    public $element = 'quicklist';
    public $table_element = 'quicklist';

    /**
     * ID of filter
     * @var integer
     */
    public $id;
    /**
     * Entity of filter
     * @var integer
     */
    public $entity;

    /**
     * Name of filter
     * @var string
     */
    public $name;
    /**
     * Context of filter
     * @var string
     */
    public $context;
    /**
     * Parameters of the url of filter
     * @var string
     */
    public $params;
    /**
     * HashTag of the url of filter
     * @var string
     */
    public $hash_tag;
    /**
     * ID of author of filter
     * @var integer
     */
    public $fk_user_author;
    /**
     * Date of creation of filter
     * @var integer
     */
    public $date_creation;
    /**
     * Scope of filter
     * @var integer
     */
    public $scope;
    /**
     * Default filter
     * @var integer
     */
    public $default;
    /**
     * ID of menu linked with the filter (optional)
     * @var integer
     */
    public $fk_menu;
    /**
     * List of usergroup when the scope is 'usergroup' for the filter (optional)
     * @var array
     */
    public $usergroups;

    /**
     * Private scope
     */
    const QUICKLIST_SCOPE_PRIVATE = 0;
    /**
     * Usergroup scope
     */
    const QUICKLIST_SCOPE_USERGROUP = 1;
    /**
     * Public scope
     */
    const QUICKLIST_SCOPE_PUBLIC = 2;

    /**
     *  Constructor
     *
     * @param   DoliDB  $db     Database handler
     */
    function __construct($db)
    {
        $this->db = $db;
    }

    /**
     *  Create quicklist filter
     *
     * @param   User  $user       Objet user that make creation
     * @param   int   $notrigger  Disable all triggers
     *
     * @return  int               <0 if KO, >0 if OK
     */
    function create($user, $notrigger = 0)
    {
        global $conf, $langs;
        $error = 0;

        dol_syslog(get_class($this) . "::create user=" . $user->id);

        // Check parameters
        if ($this->scope != self::QUICKLIST_SCOPE_PRIVATE &&
            $this->scope != self::QUICKLIST_SCOPE_USERGROUP &&
            $this->scope != self::QUICKLIST_SCOPE_PUBLIC
        ) {
            $this->scope = self::QUICKLIST_SCOPE_PRIVATE;
        }

        $now = dol_now();

        $this->db->begin();

        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "quicklist (";
        $sql .= " entity, name, context, params, fk_user_author, date_creation, scope, fk_menu, `default`, hash_tag";
        $sql .= ")";
        $sql .= " VALUES (" . $conf->entity;
        $sql .= ", '" . $this->db->escape($this->name) . "'";
        $sql .= ", '" . $this->db->escape($this->context) . "'";
        $sql .= ", " . (!empty($this->params) ? "'" . $this->db->escape($this->params) . "'" : "NULL");
        $sql .= ", " . $user->id;
        $sql .= ", '" . $this->db->idate($now) . "'";
        $sql .= ", " . $this->scope;
        $sql .= ", " . (!empty($this->fk_menu) ? $this->fk_menu : "NULL");
        $sql .= ", " . (!empty($this->default) ? 1 : "NULL");
        $sql .= ", " . (!empty($this->hash_tag) ? "'" . $this->db->escape($this->hash_tag) . "'" : "NULL");
        $sql .= ")";

        dol_syslog(get_class($this) . "::create", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . 'quicklist');

            if (!$error && !$notrigger) {
                // Call trigger
                $result = $this->call_trigger('QUICKLIST_CREATE', $user);
                if ($result < 0) $error++;
                // End call triggers
            }

            if (!$error) {
                $this->db->commit();
                return $this->id;
            } else {
                $this->db->rollback();
                return -1 * $error;
            }
        } else {
            dol_print_error($this->db);
            $this->db->rollback();
            return -1;
        }
    }

    /**
     *  Get object
     *
     * @param    int    $id     Id of object to load
     *
     * @return   int            >0 if OK, <0 if KO, 0 if not found
     */
    function fetch($id)
    {
        // Check parameters
        if (empty($id)) return -1;

        $sql = 'SELECT ql.rowid, ql.entity, ql.name, ql.context, ql.params, ql.fk_user_author, ql.date_creation, ql.scope, ql.fk_menu, ql.default, ql.hash_tag';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'quicklist as ql';
        $sql .= " WHERE ql.entity IN (" . getEntity('quicklist', 1) . ")";
        $sql .= " AND ql.rowid=" . $id;

        dol_syslog(get_class($this) . "::fetch", LOG_DEBUG);
        $result = $this->db->query($sql);
        if ($result) {
            if ($obj = $this->db->fetch_object($result)) {
                $this->id = $obj->rowid;
                $this->entity = $obj->entity;
                $this->name = $obj->name;
                $this->context = $obj->context;
                $this->params = $obj->params;
                $this->fk_user_author = $obj->fk_user_author;
                $this->date_creation = $this->db->jdate($obj->date_creation);
                $this->scope = $obj->scope;
                $this->default = !empty($obj->default) ? 1 : 0;
                $this->fk_menu = $obj->fk_menu;
                $this->hash_tag = $obj->hash_tag;

                $this->db->free($result);

                return 1;
            } else {
                $this->error = 'Quicklist filter with id ' . $id . ' not found sql=' . $sql;
                return 0;
            }
        } else {
            $this->error = $this->db->error();
            return -1;
        }
    }

    /**
     *  Return list of quicklist filters of the page into an array
     *
     * @param    string     $context     Context of the page
     *
     * @return   int|array               -1 if KO, array of quicklist object if OK
     */
    function liste_array($context)
    {
        global $conf, $user;

        $usergroup_ids = array();
        $usergroup = new UserGroup($this->db);
        $groupslist = $usergroup->listGroupsForUser($user->id);
        foreach ($groupslist as $group) {
            if ($group->entity == $conf->entity) {
                $usergroup_ids[] = $group->id;
            }
        }

        $sql = 'SELECT ql.rowid';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'quicklist as ql';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'quicklist_usergroup as qlug';
        $sql .= '   ON ql.rowid = qlug.fk_quicklist';
        $sql .= " WHERE ql.entity IN (" . getEntity('quicklist', 1) . ")";
        $sql .= " AND context = '".$this->db->escape($context)."'";
        $sql .= " AND (";
        $sql .= "   ql.fk_user_author = " . $user->id;
        if (count($usergroup_ids) > 0) $sql .= "   OR qlug.fk_usergroup IN (" . implode(',', $usergroup_ids) . ")";
        $sql .= "   OR ql.scope = " . self::QUICKLIST_SCOPE_PUBLIC;
        $sql .= " )";
        $sql .= " ORDER BY ql.scope, ql.name";

        $result = $this->db->query($sql);
        if ($result) {
            $filters = array();

            while ($obj = $this->db->fetch_object($result)) {
                $newquicklist = new QuickList($this->db);
                $newquicklist->fetch($obj->rowid);
                $newquicklist->fetch_usergroup();
                $filters[$obj->rowid] = $newquicklist;
            }

            return $filters;
        } else {
            dol_print_error($this->db);
            return -1;
        }
    }

    /**
     *  Update database
     *
     * @param   User    $user       User that modify
     * @param   int     $notrigger  0=launch triggers after, 1=disable triggers
     *
     * @return  int                 <0 if KO, >0 if OK
     */
    function update($user = null, $notrigger = 0)
    {
        $error = 0;

        // Clean parameters
        if ($this->scope != self::QUICKLIST_SCOPE_PRIVATE &&
            $this->scope != self::QUICKLIST_SCOPE_USERGROUP &&
            $this->scope != self::QUICKLIST_SCOPE_PUBLIC
        ) {
            $this->scope = self::QUICKLIST_SCOPE_PRIVATE;
        }

        // Update request
        $sql = "UPDATE " . MAIN_DB_PREFIX . "quicklist SET";
        $sql .= " entity = " . $this->entity;
        $sql .= ", name = '" . $this->db->escape($this->name) . "'";
        $sql .= ", context = '" . $this->db->escape($this->context) . "'";
        $sql .= ", params = " . (!empty($this->params) ? "'" . $this->db->escape($this->params) . "'" : "NULL");
        $sql .= ", fk_user_author = " . $this->fk_user_author;
        $sql .= ", date_creation = '" . $this->db->idate($this->date_creation) . "'";
        $sql .= ", scope = " . $this->scope;
        $sql .= ", fk_menu = " . (!empty($this->fk_menu) ? $this->fk_menu : "NULL");
        $sql .= ", `default` = " . (!empty($this->default) ? 1 : "NULL");
        $sql .= ", hash_tag = " . (!empty($this->hash_tag) ? "'" . $this->db->escape($this->hash_tag) . "'" : "NULL");
        $sql .= " WHERE rowid=" . $this->id;

        $this->db->begin();

        dol_syslog(get_class($this) . "::update", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (!$resql) {
            $error++;
            $this->errors[] = "Error " . $this->db->lasterror();
        }

        if (!$error) {
            if (!$notrigger) {
                // Call trigger
                $result = $this->call_trigger('QUICKLIST_MODIFY', $user);
                if ($result < 0) $error++;
                // End call triggers
            }
        }

        // Commit or rollback
        if ($error) {
            foreach ($this->errors as $errmsg) {
                dol_syslog(get_class($this) . "::update " . $errmsg, LOG_ERR);
                $this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
            }
            $this->db->rollback();
            return -1 * $error;
        } else {
            $this->db->commit();
            return 1;
        }
    }

    /**
     *  Delete the quicklist filter
     *
     * @param    User   $user       User object
     * @param    int    $notrigger  1=Does not execute triggers, 0= execuete triggers
     *
     * @return   int                <=0 if KO, >0 if OK
     */
    function delete($user, $notrigger = 0)
    {
        global $conf, $langs;

        $error = 0;

        $this->db->begin();

        if (!$error && !$notrigger) {
            // Call trigger
            $result = $this->call_trigger('QUICKLIST_DELETE', $user);
            if ($result < 0) $error++;
            // End call triggers
        }

        //TODO: Check for error after each action. If one failed we rollback, don't waste time to do action if previous fail
        if (!$error) {
            // Delete groupuser
            $sql = 'DELETE FROM ' . MAIN_DB_PREFIX . "quicklist_usergroup WHERE fk_quicklist = " . $this->id;
            dol_syslog(get_class($this) . "::delete", LOG_DEBUG);
            if (!$this->db->query($sql)) {
                $error++;
                $this->errors[] = $this->db->lasterror();
            }

            // Delete order
            $sql = 'DELETE FROM ' . MAIN_DB_PREFIX . "quicklist WHERE rowid = " . $this->id;
            dol_syslog(get_class($this) . "::delete", LOG_DEBUG);
            if (!$this->db->query($sql)) {
                $error++;
                $this->errors[] = $this->db->lasterror();
            }
        }

        if (!$error) {
            dol_syslog(get_class($this) . "::delete $this->id by $user->id", LOG_DEBUG);
            $this->db->commit();
            return 1;
        } else {
            foreach ($this->errors as $errmsg) {
                dol_syslog(get_class($this) . "::delete " . $errmsg, LOG_ERR);
                $this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
            }
            $this->db->rollback();
            return -1 * $error;
        }
    }

    /**
     *  Get usergroup list
     *
     * @return  integer             <0 if KO, >0 if OK, 0 if not correct scope
     */
    function fetch_usergroup()
    {
        $this->usergroups = [];

        // Check parameters
        if (empty($this->id)) return -1;

        if ($this->scope != self::QUICKLIST_SCOPE_USERGROUP) return 0;

        $sql = "SELECT fk_usergroup FROM " . MAIN_DB_PREFIX . "quicklist_usergroup WHERE fk_quicklist = " . $this->id;

        dol_syslog(get_class($this) . "::fetch_usergroup", LOG_DEBUG);
        $result = $this->db->query($sql);
        if ($result) {
            while ($obj = $this->db->fetch_object($result)) {
                $usergroup = new UserGroup($this->db);
                $usergroup->fetch($obj->fk_usergroup);
                $this->usergroups[$obj->fk_usergroup] = $usergroup;
            }

            $this->db->free($result);
            return 1;
        } else {
            $this->error = $this->db->error();
            return -1;
        }
    }

    /**
     *  Get default quicklist filter
     *
     * @param    string     $context     Context of the page
     *
     * @return  integer             <0 if KO, >0 if OK, 0 if not correct scope
     */
    function fetch_default($context)
    {
        global $conf, $user;

        $usergroup_ids = array();
        $usergroup = new UserGroup($this->db);
        $groupslist = $usergroup->listGroupsForUser($user->id);
        foreach ($groupslist as $group) {
            if ($group->entity == $conf->entity) {
                $usergroup_ids[] = $group->id;
            }
        }

        $sql = 'SELECT ql.rowid';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'quicklist as ql';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'quicklist_usergroup as qlug';
        $sql .= '   ON ql.rowid = qlug.fk_quicklist';
        $sql .= " WHERE ql.entity IN (" . getEntity('quicklist', 1) . ")";
        $sql .= " AND context = '".$this->db->escape($context)."'";
        $sql .= " AND (";
        $sql .= "   ql.fk_user_author = " . $user->id;
        if (count($usergroup_ids) > 0) $sql .= "   OR qlug.fk_usergroup IN (" . implode(',', $usergroup_ids) . ")";
        $sql .= "   OR ql.scope = " . self::QUICKLIST_SCOPE_PUBLIC;
        $sql .= " )";
        $sql .= " AND ql.default IS NOT NULL";
        $sql .= " ORDER BY ql.scope, ql.name";

        $result = $this->db->query($sql);
        if ($result) {
            if ($obj = $this->db->fetch_object($result)) {
                $this->fetch($obj->rowid);
                $this->fetch_usergroup();

                return 1;
            }

            return 0;
        } else {
            dol_print_error($this->db);
            return -1;
        }
    }

    /**
     *  Set usergroup list
     *
     * @param   array   $usergroups_ids List of usergroup ids
     * @return  integer                 <0 if KO, >0 if OK, 0 if not correct scope
     */
    function set_usergroup($usergroups_ids)
    {
        // Check parameters
        if (empty($this->id) || !is_array($usergroups_ids)) return -1;

        dol_syslog(get_class($this) . "::set_usergroup", LOG_DEBUG);

        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "quicklist_usergroup WHERE fk_quicklist = " . $this->id;
        $this->db->query($sql);
        foreach ($usergroups_ids as $usergroups_id) {
            $usergroups_id = intval($usergroups_id);
            $sql = "INSERT INTO " . MAIN_DB_PREFIX . "quicklist_usergroup (fk_quicklist, fk_usergroup) VALUES ($this->id, $usergroups_id)";
            $this->db->query($sql);
        }

        return 1;
    }
}
