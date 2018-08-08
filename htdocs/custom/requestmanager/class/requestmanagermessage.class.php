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
//dol_include_once('/requestmanager/class/requestmanager.class.php');

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
     * Constructor
     *
     * @param   DoliDb      $db     Database handler
     */
    public function __construct(DoliDB $db)
    {
        $this->db = $db;
    }


    /**
     * Create a notification in database
     *
     * @param   int     $fkActionComm       Id of ActionComm
     * @param   int     $fkKnowledgeBase    Id of knowledge base (in dictionary)
     * @return  int     <0 if KO, >0 if OK
     */
    public function create($fkActionComm, $fkKnowledgeBase)
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
}