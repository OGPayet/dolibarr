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
    public $table_element = 'requestmanager_requestmanagermessage';
    protected $ismultientitymanaged = 1;	// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe

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
     *  Add an action/event into database.
     *  $this->type_id OR $this->type_code must be set.
     *
     * @param	User	$user      		Object user making action
     * @param   int		$notrigger		1 = disable triggers, 0 = enable triggers
     * @return  int 		        	Id of created event, < 0 if KO
     */
    public function create(User $user, $notrigger = 0)
    {
           //global $langs;

           // Clean parameters
           $this->fk_knowledge_base = $this->fk_knowledge_base > 0 ? $this->fk_knowledge_base : 0;

           // Check parameters
           $error = 0;
           /*$langs->load("requestmanager@requestmanager");
           if (empty($this->property)) {
               $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("PropertyLabel"));
               $error++;
           }*/

           if ($error) {
               dol_syslog(get_class($this) . "::create requestmanager_message Errors: " . $this->errorsToString(), LOG_ERR);
               return -4;
           }

           $this->db->begin();

           $result = parent::create($user,1);
           if ($result > 0) {
               dol_syslog(get_class($this) . "::create requestmanager_message: fk_knowledge_base=" . $this->fk_knowledge_base, LOG_DEBUG);

               $error=0;

               // Insert further information
               $sql = "INSERT INTO " . MAIN_DB_PREFIX . "requestmanager_message (";
               $sql .= "  fk_actioncomm";
               $sql .= ", fk_knowledge_base";
               $sql .= ") VALUES (";
               $sql .= "  " . $result;
               $sql .= ", " . $this->fk_knowledge_base;
               $sql .= ")";

               dol_syslog(get_class($this) . "::Create requestmanager_message", LOG_DEBUG);
               $result = $this->db->query($sql);
               if (!$result) {
                   $error++;
                   $this->error = $this->db->lasterror();
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
        //global $langs;

        // Clean parameters
        $this->fk_knowledge_base = $this->fk_knowledge_base > 0 ? $this->fk_knowledge_base : 0;

        // Check parameters
        $error = 0;
        /*$langs->load("requestmanager@requestmanager");
        if (empty($this->property)) {
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("PropertyLabel"));
            $error++;
        }*/

        if ($error) {
            dol_syslog(get_class($this) . "::update requestmanager_message Errors: " . $this->errorsToString(), LOG_ERR);
            return -4;
        }

        $this->db->begin();

        $result = parent::update($user, 1);
        if ($result > 0) {
            $error = 0;

            // Insert further information
            $sql = "UPDATE " . MAIN_DB_PREFIX . "requestmanager_message";
            $sql .= " SET";
            $sql .= "  fk_knowledge_base = " . $this->fk_knowledge_base;
            $sql .= " WHERE fk_actioncomm = " . $this->id;

            dol_syslog(get_class($this) . "::update requestmanager_message", LOG_DEBUG);
            $result = $this->db->query($sql);
            if (!$result) {
                $error++;
                $this->error = $this->db->lasterror();
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
            $sql = "DELETE FROM " . MAIN_DB_PREFIX . "requestmanager_message";
            $sql .= " WHERE fk_actioncomm = " . $this->id;
            dol_syslog(get_class($this) . '::delete requestmanager_message', LOG_DEBUG);
            $result = $this->db->query($sql);
            if (!$result) {
                $error++;
                $this->errors[] = $this->db->lasterror();
            }
        }

        // Removed extrafields
        if ((!$error) && (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED))) // For avoid conflicts if trigger used
        {
            $result = $this->deleteExtraFields();
            if ($result < 0) {
                $error++;
                dol_syslog(get_class($this) . "::delete requestmanager_message error -3 " . $this->error, LOG_ERR);
            }
        }

        if (!$error) {
            $result = parent::delete(1);
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
        dol_syslog(get_class($this) . "::fetch requestmanager_message id=" . $id . " ref=" . $ref . " ref_ext=" . $ref_ext);

        $result = parent::fetch($id, $ref, $ref_ext);
        if ($result > 0) {
            $sql = "SELECT fk_knowledge_base";
            $sql .= " FROM " . MAIN_DB_PREFIX . "requestmanager_message";
            $sql .= " WHERE fk_actioncomm = " . $this->id;

            $resql = $this->db->query($sql);
            if ($resql) {
                if ($obj = $this->db->fetch_object($resql)) {
                    $this->fk_knowledge_base = $obj->fk_knowledge_base;

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
}