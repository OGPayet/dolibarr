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
 * 	\file 		htdocs/companyrelationships/class/companyrelationshipsavailability.class.php
 *	\ingroup    companyrelationships
 *	\brief      File of class to manage company relationships public space availability
 */


/**
 *	Class to manage company relationships availability
 */
class CompanyRelationshipsAvailability
{
    public $table_element = 'companyrelationships_availability';

    /**
     * Database handler
     * @var DoliDB
     */
    public $db;

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
     * Id
     * @var int
     */
    public $id;

    /**
     * Company relationships
     * @var int
     */
    public $fk_companyrelationships;

    /**
     * Id line in dictionary Company relationships public space availability
     * @var int
     */
    public $fk_c_companyrelationships_availability;

    /**
     * Principal availability
     * @var int
     */
    public $principal_availability;

    /**
     * Benefactor availability
     * @var int
     */
    public $benefactor_availability;

    /**
     * Watcher availability
     * @var int
     */
    public $watcher_availability;


    /**
     * Constructor
     *
     * @param   DoliDb $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        $this->db = $db;
    }


    /**
     * Method to output saved errors
     *
     * @return	string		String with errors
     */
    public function errorsToString()
    {
        return $this->error.(is_array($this->errors)?(($this->error!=''?', ':'').join(', ',$this->errors)):'');
    }


    /**
     * Create public space availability into database
     *
     * @param   User    $user           User that creates
     * @param   bool    $notrigger      [=FALSE] Launch triggers after, TRUE=disable triggers
     * @return  int
     *
     * @throws  Exception
     */
    public function create(User $user, $notrigger=FALSE)
    {
        global $conf, $langs, $hookmanager;
        $error = 0;
        $this->errors = array();
        $langs->load("relationstierscontacts@relationstierscontacts");

        dol_syslog(__METHOD__ . " user_id=" . $user->id, LOG_DEBUG);

        // Clean parameters
        $this->fk_companyrelationships = $this->fk_companyrelationships > 0 ? $this->fk_companyrelationships : 0;
        $this->fk_c_companyrelationships_availability = $this->fk_c_companyrelationships_availability > 0 ? $this->fk_c_companyrelationships_availability : 0;
        $this->principal_availability  = $this->principal_availability > 0 ? $this->principal_availability : 0;
        $this->benefactor_availability = $this->benefactor_availability > 0 ? $this->benefactor_availability : 0;
        $this->watcher_availability    = $this->watcher_availability > 0 ? $this->watcher_availability : 0;

        // Check parameters
        if (empty($this->fk_companyrelationships)) {
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("CompanyRelationshipsID"));
            $error++;
        }
        if (empty($this->fk_c_companyrelationships_availability)) {
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("CompanyRelationshipsPublicSpaceAvailabilityID"));
            $error++;
        }
        if ($error) {
            dol_syslog(__METHOD__ . " Errors check parameters: " . $this->errorsToString(), LOG_ERR);
            return -3;
        }

        $this->db->begin();

        if (!$error) {
            // Insert request
            $sql  = "INSERT INTO " . MAIN_DB_PREFIX . $this->table_element . " (";
            $sql .= " fk_companyrelationships";
            $sql .= ", fk_c_companyrelationships_availability";
            $sql .= ", principal_availability";
            $sql .= ", benefactor_availability";
            $sql .= ", watcher_availability";
            $sql .= ")";
            $sql .= " VALUES (";
            $sql .= " " . $this->fk_companyrelationships;
            $sql .= ", " . $this->fk_c_companyrelationships_availability;
            $sql .= ", " . $this->principal_availability;
            $sql .= ", " . $this->benefactor_availability;
            $sql .= ", " . $this->watcher_availability;
            $sql .= ")";

            $resql = $this->db->query($sql);
            if (!$resql) {
                $error++;
                $this->errors[] = 'Error ' . $this->db->lasterror();
                dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
            }
        }

        if (!$error) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . $this->table_element);
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
     * Load public space availability in memory from the database
     *
     * @param   int     $id         Id object
     * @return  int                 <0 if KO, 0 if not found, >0 if OK
     *
     * @throws  Exception
     */
    public function fetch($id)
    {
        global $langs;
        $this->errors = array();
        $langs->load("companyrelationships@companyrelationships");

        dol_syslog(__METHOD__ . " id=" . $id, LOG_DEBUG);

        $sql  = "SELECT";
        $sql .= " t.rowid";
        $sql .= ", t.fk_companyrelationships";
        $sql .= ", t.fk_c_companyrelationships_availability";
        $sql .= ", t.principal_availability";
        $sql .= ", t.benefactor_availability";
        $sql .= ", t.watcher_availability";
        $sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element . " as t";
        if ($id) $sql .= " WHERE t.rowid = " . $id;

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
            return -1;
        } else {
            $numrows = $this->db->num_rows($resql);
            if ($numrows) {
                $obj = $this->db->fetch_object($resql);

                $this->id                                     = $obj->rowid;
                $this->fk_companyrelationships                = $obj->fk_companyrelationships;
                $this->fk_c_companyrelationships_availability = $obj->fk_c_companyrelationships_availability;
                $this->principal_availability                 = $obj->principal_availability;
                $this->benefactor_availability                = $obj->benefactor_availability;
                $this->watcher_availability                   = $obj->watcher_availability;
            }
            $this->db->free($resql);

            if ($numrows) {
                return 1;
            } else {
                return 0;
            }
        }
    }


    /**
     * Update public space availability into database
     *
     * @param   User    $user           User that modifies
     * @param   bool    $notrigger      [=FALSE] Launch triggers after, TRUE=disable triggers
     * @return  int                     <0 if KO, >0 if OK
     *
     * @throws  Exception
     */
    public function update(User $user, $notrigger=FALSE)
    {
        global $langs;
        $error = 0;
        $this->errors = array();
        $langs->load("companyrelationships@companyrelationships");

        dol_syslog(__METHOD__ . " user_id=" . $user->id . " id=" . $this->id, LOG_DEBUG);

        // Clean parameters
        $this->fk_companyrelationships = $this->fk_companyrelationships > 0 ? $this->fk_companyrelationships : 0;
        $this->fk_c_companyrelationships_availability = $this->fk_c_companyrelationships_availability > 0 ? $this->fk_c_companyrelationships_availability : 0;
        $this->principal_availability = $this->principal_availability > 0 ? $this->principal_availability : 0;
        $this->benefactor_availability = $this->benefactor_availability > 0 ? $this->benefactor_availability : 0;

        // Check parameters
        if (!($this->id > 0)) {
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("TechnicalID"));
            $error++;
        }
        if (empty($this->fk_companyrelationships)) {
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("CompanyRelationshipsID"));
            $error++;
        }
        if (empty($this->fk_c_companyrelationships_availability)) {
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("CompanyRelationshipsPublicSpaceAvailabilityID"));
            $error++;
        }
        if ($error) {
            dol_syslog(__METHOD__ . " Errors check parameters: " . $this->errorsToString(), LOG_ERR);
            return -3;
        }

        $this->db->begin();

        if (!$error) {
            $sql = "UPDATE " . MAIN_DB_PREFIX . $this->table_element;
            $sql .= " SET fk_companyrelationships = " . $this->fk_companyrelationships;
            $sql .= ", fk_c_companyrelationships_availability = " . $this->fk_c_companyrelationships_availability;
            $sql .= ", principal_availability = " . $this->principal_availability;
            $sql .= ", benefactor_availability = " . $this->benefactor_availability;
            $sql .= ", watcher_availability = " . $this->watcher_availability;
            $sql .= " WHERE rowid = " . $this->id;

            $resql = $this->db->query($sql);
            if (!$resql) {
                $error++;
                $this->errors[] = 'Error ' . $this->db->lasterror();
                dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
            }
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
     * Delete public space availability in database
     *
     * @param   User    $user           User that deletes
     * @param   bool    $notrigger      [=FALSE] Launch triggers after, TRUE=disable triggers
     * @return  int                     <0 if KO, >0 if OK
     *
     * @throws  Exception
     */
    public function delete(User $user, $notrigger=FALSE)
    {
        global $conf, $langs;
        $error = 0;
        $this->errors = array();
        $langs->load("companyrelationships@companyrelationships");

        dol_syslog(__METHOD__ . " user_id=" . $user->id . " id=" . $this->id, LOG_DEBUG);

        // Check parameters
        if (!($this->id > 0)) {
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("TechnicalID"));
            $error++;
        }
        if ($error) {
            dol_syslog(__METHOD__ . " Errors check parameters: " . $this->errorsToString(), LOG_ERR);
            return -3;
        }

        $this->db->begin();

        // remove
        if (!$error) {
            $sql  = "DELETE FROM " . MAIN_DB_PREFIX . $this->table_element;
            $sql .= " WHERE rowid = " . $this->id;

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
     * Delete relations thirdparty linked to a contact
     *
     * @param   int     $fk_companyrelationships    Contact id in relation
     * @param   User    $user                       User that deletes
     * @param   bool    $notrigger                  [=FALSEl Launch triggers after, TRUE=disable triggers
     * @return  int     <0 if KO, >0 if OK
     * @throws  Exception
     */
    public function deleteAllByFkCompanyRelationships($fk_companyrelationships, User $user, $notrigger=FALSE)
    {
        $error = 0;

        $sql = "SELECT";
        $sql .= " t.rowid";
        $sql .= ", t.fk_companyrelationships";
        $sql .= ", t.fk_c_companyrelationships_availability";
        $sql .= ", t.principal_availability";
        $sql .= ", t.benefactor_availability";
        $sql .= ", t.watcher_availability";
        $sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element . " as t";
        $sql .= " WHERE t.fk_companyrelationships = " . $fk_companyrelationships;

        $resql = $this->db->query($sql);
        if (!$resql) {
            return -1;
        } else {
            while ($obj = $this->db->fetch_object($resql)) {
                $this->id                                     = $obj->rowid;
                $this->fk_companyrelationships                = $obj->fk_companyrelationships;
                $this->fk_c_companyrelationships_availability = $obj->fk_c_companyrelationships_availability;
                $this->principal_availability                 = $obj->principal_availability;
                $this->benefactor_availability                = $obj->benefactor_availability;
                $this->watcher_availability                   = $obj->watcher_availability;

                $ret = $this->delete($user, $notrigger);

                if ($ret < 0) {
                    $error++;
                    break;
                }
            }

            if ($error) {
                return -1;
            } else {
                return 1;
            }
        }
    }


    /**
     * Load a CompanyRelationshipsAvailability object by unique key if exists
     *
     * @param   DoliDB                                  $db                                         Database handler
     * @param   int                                     $fk_companyrelationships
     * @param   int                                     $fk_c_companyrelationships_availability
     * @return  CompanyRelationshipsAvailability|int    <0 if KO or CompanyRelationshipsAvailability object if OK
     * @throws Exception
     */
    public static function loadByUniqueKey(DoliDB $db, $fk_companyrelationships, $fk_c_companyrelationships_availability)
    {
        dol_syslog(__METHOD__, LOG_DEBUG);

        $companyRelationshipsAvailability = new self($db);
        $companyRelationshipsAvailability->fk_companyrelationships                = $fk_companyrelationships;
        $companyRelationshipsAvailability->fk_c_companyrelationships_availability = $fk_c_companyrelationships_availability;

        $sql  = "SELECT";
        $sql .= " t.rowid";
        $sql .= ", t.fk_companyrelationships";
        $sql .= ", t.fk_c_companyrelationships_availability";
        $sql .= ", t.principal_availability";
        $sql .= ", t.benefactor_availability";
        $sql .= ", t.watcher_availability";
        $sql .= " FROM " . MAIN_DB_PREFIX . $companyRelationshipsAvailability->table_element . " as t";
        $sql .= " WHERE t.fk_companyrelationships = " . $fk_companyrelationships;
        $sql .= " AND t.fk_c_companyrelationships_availability = " . $fk_c_companyrelationships_availability;

        $resql = $db->query($sql);

        if (!$resql) {
            dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $db->lasterror(), LOG_ERR);
            return -1;
        } else {
            $numrows = $db->num_rows($resql);
            if ($numrows > 0) {
                $obj = $db->fetch_object($resql);

                $companyRelationshipsAvailability->id                                     = $obj->rowid;
                $companyRelationshipsAvailability->fk_companyrelationships                = $obj->fk_companyrelationships;
                $companyRelationshipsAvailability->fk_c_companyrelationships_availability = $obj->fk_c_companyrelationships_availability;
                $companyRelationshipsAvailability->principal_availability                 = $obj->principal_availability;
                $companyRelationshipsAvailability->benefactor_availability                = $obj->benefactor_availability;
                $companyRelationshipsAvailability->watcher_availability                   = $obj->watcher_availability;
            }

            return $companyRelationshipsAvailability;
        }
    }
}