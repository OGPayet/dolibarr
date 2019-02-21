<?php
/*  Copyright (C) 2018      Open-DSI             <support@open-dsi.fr>
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
 *	\file       htdocs/requestmanager/core/triggers/interface_99_modRequestManager_RequestManager.class.php
 *  \ingroup    requestmanager
 *	\brief      File of class of triggers for requestmanager module
 */


require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';


/**
 *  Class of triggers for requestmanager module
 */
class InterfaceRMPlanning extends DolibarrTriggers
{
	public $family = 'requestmanager';
	public $description = "Triggers of this module catch triggers event for management of the planning in RequestManager module.";
	public $version = self::VERSION_DOLIBARR;
	public $picto = 'technic';


	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "runTrigger" are triggered if file is inside directory htdocs/core/triggers or htdocs/module/code/triggers (and declared)
	 *
	 * @param string		$action		Event action code
	 * @param Object		$object     Object
	 * @param User		    $user       Object user
	 * @param Translate 	$langs      Object langs
	 * @param conf		    $conf       Object conf
	 * @return int         				<0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
    {
        if (empty($conf->requestmanager->enabled)) return 0;     // Module not active, we do nothing

        switch ($action) {
            // Companies
		    case 'COMPANY_CREATE':
		    case 'COMPANY_MODIFY':
                // Management of the user group(s) in charge for the planning
                //----------------------------------------------------------------------
                if (!empty($conf->global->REQUESTMANAGER_PLANNING_ACTIVATE) && $user->rights->requestmanager->usergroup_in_charge->manage) {
                    $request_types_planned = !empty($conf->global->REQUESTMANAGER_PLANNING_REQUEST_TYPE) ? explode(',', $conf->global->REQUESTMANAGER_PLANNING_REQUEST_TYPE) : array();
                    dol_include_once('/advancedictionaries/class/dictionary.class.php');
                    $requestmanagerrequesttype = Dictionary::getDictionary($this->db, 'requestmanager', 'requestmanagerrequesttype');
                    $requestmanagerrequesttype->fetch_lines(1);

                    dol_include_once('/requestmanager/class/requestmanagerplanning.class.php');
                    $requestmanagerplanning = new RequestManagerPlanning($this->db);

                    foreach ($requestmanagerrequesttype->lines as $request_type) {
                        if (!in_array($request_type->id, $request_types_planned) || !isset($_POST['usergroups_in_charge_' . $request_type->id])) continue;

                        $usergroups_in_charge = GETPOST('usergroups_in_charge_' . $request_type->id, 'array');

                        // Save user groups in charge for the request type
                        if ($requestmanagerplanning->setUserGroupsInChargeForCompany($object->id, $request_type->id, $usergroups_in_charge) < 0) {
                            $this->error = $requestmanagerplanning->error;
                            $this->errors = $requestmanagerplanning->errors;
                            return -1;
                        }
                    }

                    dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
                }

                break;
		    case 'COMPANY_DELETE':
                // Management of the user group(s) in charge for the planning
                //----------------------------------------------------------------------
                if (!empty($conf->global->REQUESTMANAGER_PLANNING_ACTIVATE) && $user->rights->requestmanager->usergroup_in_charge->manage) {
                    dol_include_once('/requestmanager/class/requestmanagerplanning.class.php');
                    $requestmanagerplanning = new RequestManagerPlanning($this->db);

                    // Delete user groups in charge
                    if ($requestmanagerplanning->deleteUserGroupsInChargeForCompany($object->id) < 0) {
                        $this->error = $requestmanagerplanning->error;
                        $this->errors = $requestmanagerplanning->errors;
                        return -1;
                    }
                }

                dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
                break;
        }

        return 0;
    }
}