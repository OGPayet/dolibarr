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
 *	\file       htdocs/extendedintervention/core/triggers/interface_99_modExtendedIntervention_ExtendedIntervention.class.php
 *  \ingroup    extendedintervention
 *	\brief      File of class of triggers for Extended Intervention module
 */


require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';


/**
 *  Class of triggers for Extended Intervention module
 */
class InterfaceEIPlanning extends DolibarrTriggers
{
	public $family = 'extendedintervention';
	public $description = "Triggers of this module catch triggers event for Extended Intervention module.";
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
        if (empty($conf->extendedintervention->enabled)) return 0;     // Module not active, we do nothing

        switch ($action) {
            // Request Manager
            case 'REQUESTMANAGER_MODIFY':
                // Assign users to the linked intervention when the request is planned
                if (!empty($conf->requestmanager->enabled) && !empty($conf->global->REQUESTMANAGER_PLANNING_ACTIVATE) && !empty($object->context['rm_planning']) && $user->rights->requestmanager->planning->manage) {
                    $object->fetchObjectLinked();

                    if (is_array($object->linkedObjects['fichinter'])) {
                        foreach ($object->linkedObjects['fichinter'] as $intervention) {
                            if ($intervention->statut <= 1) { // 0=draft, 1=validated
                                if ($intervention->delete_linked_contact('internal', 'INTERVENING') < 0) {
                                    dol_syslog(__METHOD__ . " Delete linked contact for intervention (ID: " . $intervention->id . ") : " . $intervention->errorsToString(), LOG_ERR);
                                    $this->error = $intervention->error;
                                    $this->errors = $intervention->errors;
                                    return -1;
                                }
                                foreach ($object->assigned_user_ids as $assigned_user_id) {
                                    if ($intervention->add_contact($assigned_user_id, 'INTERVENING', 'internal') < 0) {
                                        dol_syslog(__METHOD__ . " Add linked contact (ID: " . $assigned_user_id . ") for intervention (ID: " . $intervention->id . ") : " . $intervention->errorsToString(), LOG_ERR);
                                        $this->error = $intervention->error;
                                        $this->errors = $intervention->errors;
                                        return -1;
                                    }
                                }
                            }

                            if ($intervention->statut == 0 && !empty($conf->global->EXTENDEDINTERVENTION_PLANNING_AUTO_VALIDATE)) {
                                if ($intervention->setValid($user) < 0) {
                                    $this->error = $intervention->error;
                                    $this->errors = $intervention->errors;
                                    return -1;
                                }
                            }
                        }
                    }
                }

                dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
                break;

            // Contract
//            case 'CONTRACT_EC_RENEC':
//                if (!empty($conf->requestmanager->enabled) && !empty($conf->global->REQUESTMANAGER_PLANNING_ACTIVATE)) {
//                    // Todo generate the intervention planned ??
//                }
//
//                dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
//                break;
        }

        return 0;
    }
}