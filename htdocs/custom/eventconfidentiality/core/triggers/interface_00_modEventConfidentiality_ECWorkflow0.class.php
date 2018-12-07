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
 *	\file       htdocs/eventconfidentiality/core/triggers/interface_99_modEventConfidentiality_ECWorkflow.class.php
 *  \ingroup    eventconfidentiality
 *	\brief      File of class of triggers for workflow in eventconfidentiality module
 */


require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';


/**
 *  Class of triggers for workflow in eventconfidentiality module
 */
class InterfaceECWorkflow0 extends DolibarrTriggers
{
	public $family = 'eventconfidentiality';
	public $description = "Triggers of this module catch triggers event for the workflow of EventConfidentiality module.";
	public $version = self::VERSION_DOLIBARR;
	public $picto = 'eventconfidentiality@eventconfidentiality';

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
        if (empty($conf->eventconfidentiality->enabled)) return 0;     // Module not active, we do nothing

        switch ($action) {
            // Action
            case 'ACTION_MODIFY':
                // Get mode for the user and event
                $user_f = isset($user) ? $user : DolibarrApiAccess::$user;
                dol_include_once('/eventconfidentiality/class/eventconfidentiality.class.php');
                $eventconfidentiality = new EventConfidentiality($this->db);
                $mode = $eventconfidentiality->getModeForUserAndEvent($user_f, $object->id);
                if ($mode < 0) {
                    $this->error = $eventconfidentiality->error;
                    $this->errors = $eventconfidentiality->errors;
                    return -1;
                }

                if ($mode == EventConfidentiality::MODE_BLURRED) {
                    foreach ($object->ec_save_values as $key => $value) {
                        $object->$key = $value;
                    }
                    $result = $object->update($user, 1);
                    if ($result < 0) {
                        $this->error = $object->error;
                        $this->errors = $object->errors;
                        return -1;
                    }
                }

                dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
                break;
        }

        return 0;
    }
}