<?php
/*  Copyright (C) 2019      Open-DSI             <support@open-dsi.fr>
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
 *	\file       requestmanager/core/class/requestmanagerplanning.class.php
 *  \ingroup    requestmanager
 *	\brief      File of class to manage the planning
 */

/**
 *	Class to manage the planning
 *
 */
class RequestManagerPlanning
{
    /**
     * @var DoliDB Database handler.
     */
    public $db;
    /**
     * @var string Error
     */
    public $error = '';
    /**
     * @var array Errors
     */
    public $errors = array();


    /**
     * Constructor
     *
     * @param   DoliDB      $db     Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     *  Get all users in charge for a company by request type
     *
     * @param   int             $company_id     Company ID
     * @return  int|array                       <0 if KO, List of Users ID in charge for the company by request type
     */
    public function getUsersInChargeForCompany($company_id)
    {
        $this->errors = array();
        $users_in_charge = array();

        dol_syslog(__METHOD__ . " company_id=" . $company_id, LOG_DEBUG);

        // Clean parameters
        $company_id = $company_id > 0 ? $company_id : 0;

        if ($company_id == 0) {
            dol_syslog(__METHOD__ . " Errors bad parameters: company_id=" . $company_id, LOG_ERR);
            return -1;
        }

        // Get users in charge
        $sql = "SELECT fk_user, fk_c_request_type FROM " . MAIN_DB_PREFIX . "societe_rm_user_in_charge";
        $sql .= " WHERE fk_soc = " . $company_id;

        $resql = $this->db->query($sql);
        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $users_in_charge[$obj->fk_c_request_type][$obj->fk_user] = $obj->fk_user;
            }
        } else {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
            return -1;
        }

        return $users_in_charge;
    }

    /**
     *  Set all users in charge for a company and a request type
     *
     * @param   int             $company_id             Company ID
     * @param   int             $request_type_id        Request Type ID
     * @param   array           $users_in_charge        List of Users ID in charge
     * @return  int                                     <0 if KO, >0 if OK
     */
    public function setUsersInChargeForCompany($company_id, $request_type_id, $users_in_charge)
    {
        $error = 0;
        $this->errors = array();

        dol_syslog(__METHOD__ . " company_id=" . $company_id . " request_type_id=" . $request_type_id . " users_in_charge=" . json_encode($users_in_charge), LOG_DEBUG);

        // Clean parameters
        $company_id = $company_id > 0 ? $company_id : 0;
        $request_type_id = $request_type_id > 0 ? $request_type_id : 0;
        $users_in_charge = is_array($users_in_charge) ? $users_in_charge : (is_string($users_in_charge) ? array_filter(explode(',', $users_in_charge), 'is_numeric') : array());

        if ($company_id == 0 || $request_type_id == 0) {
            dol_syslog(__METHOD__ . " Errors bad parameters: company_id=" . $company_id . " request_type_id=" . $request_type_id, LOG_ERR);
            return -1;
        }

        $this->db->begin();

        // Delete old values
        if ($this->deleteUsersInChargeForCompany($company_id) < 0) {
            $error++;
        }

        if (!$error && count($users_in_charge) > 0) {
            // Set users in charge
            $sql = "INSERT INTO " . MAIN_DB_PREFIX . "societe_rm_user_in_charge (fk_soc, fk_user, fk_c_request_type) VALUES";
            foreach ($users_in_charge as $user_id) {
                $sql .= " (" . $company_id . "," . $user_id . "," . $request_type_id . "),";
            }
            $sql = substr($sql, 0, -1);

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
            return -1 * $error;
        } else {
            $this->db->commit();
            dol_syslog(__METHOD__ . " success", LOG_DEBUG);
            return 1;
        }
    }

    /**
     *  Delete all users in charge for a company
     *
     * @param   int             $company_id     Company ID
     * @return  int                             <0 if KO, >0 if OK
     */
    public function deleteUsersInChargeForCompany($company_id)
    {
        $this->errors = array();

        dol_syslog(__METHOD__ . " company_id=" . $company_id, LOG_DEBUG);

        // Clean parameters
        $company_id = $company_id > 0 ? $company_id : 0;

        if ($company_id == 0) {
            dol_syslog(__METHOD__ . " Errors bad parameters: company_id=" . $company_id, LOG_ERR);
            return -1;
        }

        // Delete users in charge
        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "societe_rm_user_in_charge";
        $sql .= " WHERE fk_soc = " . $company_id;

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
            return -1;
        }

        return 1;
    }
}