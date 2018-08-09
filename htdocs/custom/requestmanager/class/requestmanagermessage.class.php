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

require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';


/**
 * Class RequestManagerNotification
 *
 * Put here description of your class
 * @see CommonObject
 */
class RequestManagerMessage extends CommonObject
{

    public $element = 'requestmanager_message';
    public $table_element = 'requestmanager_message';
    protected $ismultientitymanaged = 1;	// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe


    /**
     * ID of the notification
     * @var int
     */
    public $id;

    /**
     * ActionComm of the notification
     * @var int
     */
    public $fk_actioncomm;

    /**
     * Id of knowledge base (in dictionary)
     * @var int
     */
    public $fk_knowledge_base;


    /**
     * Object KnowledgeBase
     * @var stdClass
     */
    public $knowledgeBase = NULL;


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
     * Find knowledge base by id of actioncomm
     *
     * @param   int         $fkActionComm       Id of actioncomm
     * @param   bool        $withKnowledgeBase  [=FALSE] without knowledge base, TRUE to join knowledge base
     * @return  resource    SQL resource
     */
    private function _findByFkAction($fkActionComm, $withKnowledgeBase=FALSE)
    {
        $sql  = "SELECT";
        $sql .= $this->_sqlSelectAllFields('rmm', TRUE);
        if ($withKnowledgeBase) {
            $sql .= ", crmkb.code";
            $sql .= ", crmkb.title";
        }
        $sql .= $this->_slqFromTableElement();
        if ($withKnowledgeBase) {
            $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "c_requestmanager_knowledge_base as crmkb ON crmkb.rowid = rmm.fk_knowledge_base";
        }
        $sql .= " WHERE rmm.fk_actioncomm = " . $fkActionComm;

        return $this->db->query($sql);
    }


    /**
     * Load this object from db object
     *
     * @param   stdClass    $obj    Db object
     */
    private function _loadFromDbObject($obj)
    {
        $this->id                = $obj->rowid;
        $this->fk_actioncomm     = $obj->fk_actioncomm;
        $this->fk_knowledge_base = $obj->fk_knowledge_base;
    }


    /**
     * Load knowledge base object from db object
     *
     * @param   stdClass    $obj    Db object
     */
    private function _loadKnowledgeBaseFromDbObject($obj)
    {
        $this->knowledgeBase = new stdClass();
        $this->knowledgeBase->code  = $obj->code;
        $this->knowledgeBase->title = $obj->title;
    }


    /**
     * Insert a message in database
     *
     * @param   int     $fkActionComm       Id of ActionComm
     * @param   int     $fkKnowledgeBase    Id of knowledge base (in dictionary)
     * @return  int     <0 if KO, >0 if OK
     */
    private function  _sqlInsert($fkActionComm, $fkKnowledgeBase)
    {
        $sql  = "INSERT INTO " . MAIN_DB_PREFIX . $this->table_element . " (";
        $sql .= " fk_actioncomm, fk_knowledge_base";
        $sql .= ") VALUES (";
        $sql .= $fkActionComm . ", " . $fkKnowledgeBase;
        $sql .= ")";

        $this->db->begin();

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->errors[] = $this->db->lasterror();
            dol_syslog( __METHOD__ . " Error sql=" . $sql, LOG_ERR);
            $this->db->rollback();
            return -1;
        }

        $this->db->commit();

        return 1;
    }


    /**
     * Create all SQL fields for this table element
     *
     * param    string  $tableAliasName         [=''] Table alias name
     * @param   bool    $firstFieldsInSelect    [=FALSE] if not for select fields in SQL, TRUE else
     * @return  string  SQL select fields
     */
    private function _sqlSelectAllFields($tableAliasName='', $firstFieldsInSelect=FALSE)
    {
        $tableAliasForField = '';
        if ($tableAliasName) {
            $tableAliasForField = $tableAliasName . '.';
        }

        $sql  = "";
        if($firstFieldsInSelect === FALSE) {
            $sql = ",";
        }
        $sql .= " " . $tableAliasForField . "rowid";
        $sql .= ", " . $tableAliasForField . "fk_actioncomm";
        $sql .= ", " . $tableAliasForField . "fk_knowledge_base";

        return $sql;
    }


    /**
     * Create SQL From request for this table element
     *
     * @return  string
     */
    private function _slqFromTableElement()
    {
        $sql = " FROM " . MAIN_DB_PREFIX . $this->table_element . " as rmm";

        return $sql;
    }


    /**
     * Create a message in database
     *
     * @param   User    $user           User that creates
     * @param   bool    $notrigger      [=FALSE] launch triggers after, TRUE disable triggers
     * @return  int     <0 if KO, >0 if OK
     */
    public function create(User $user, $notrigger=FALSE)
    {
        global $langs;

        $error = 0;

        // Clean parameters
        $this->fk_actioncomm     = $this->fk_actioncomm > 0 ? $this->fk_actioncomm : 0;
        $this->fk_knowledge_base = $this->fk_knowledge_base > 0 ? $this->fk_knowledge_base : 0;

        // Check parameters
        if (empty($this->fk_actioncomm)) {
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("RequestManagerMessageActionComm"));
            $error++;
        }

        $this->db->begin();
        if (!$error) {
            $result = $this->_sqlInsert($this->fk_actioncomm, $this->fk_knowledge_base);
            if ($result < 0) {
                $error++;
            }
        }

        if (!$error) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . $this->table_element);

            if (!$error && !$notrigger) {
                // Call trigger
                $result = $this->call_trigger('REQUESTMANAGERMESSAGE_CREATE', $user);
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
     *  Load request in memory from the database
     *
     * @param   int     $id         Id object
     * @return  int                 <0 if KO, 0 if not found, >0 if OK
     */
    public function fetch($id)
    {
        $sql  = "SELECT";
        $sql .= $this->_sqlSelectAllFields('rmm', TRUE);
        $sql .= $this->_slqFromTableElement();
        $sql .= " WHERE rmm.rowid = " . $id;

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
            return -1;
        }

        $numrows = $this->db->num_rows($resql);
        if ($numrows) {
            $obj = $this->db->fetch_object($resql);
            $this->_loadFromDbObject($obj);
            $this->db->free($resql);
            return 1;
        } else {
            return 0;
        }
    }


    /**
     * Load object with knowledge base
     *
     * @param   int     $fkActionComm       Id of ActionComm
     * @param   bool    $withKnowledgeBase  [=FALSE] without knowledge base, TRUE to join knowledge base
     * @return  int     <0 if KO, 0 if not found, >0 if OK
     */
    public function loadByFkAction($fkActionComm, $withKnowledgeBase=FALSE)
    {
        $resql = $this->_findByFkAction($fkActionComm, $withKnowledgeBase);

        if (!$resql) {
            $this->errors[] = $this->db->lasterror();
            return -1;
        }

        if ($resql) {
            if ($obj = $this->db->fetch_object($resql)) {
                $this->_loadFromDbObject($obj);
                $this->_loadKnowledgeBaseFromDbObject($obj);
                $this->db->free($resql);
                return 1;
            } else {
                return 0;
            }
        }
    }
}