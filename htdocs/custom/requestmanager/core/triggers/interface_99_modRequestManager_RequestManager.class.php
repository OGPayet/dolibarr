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
class InterfaceRequestManager extends DolibarrTriggers
{
	public $family = 'requestmanager';
	public $description = "Triggers of this module catch triggers event for RequestManager module.";
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
            // Contracts
            case 'CONTRACT_CREATE':
                if (empty($conf->global->REQUESTMANAGER_TIMESLOTS_ACTIVATE))
                    return 0;

                dol_include_once('/requestmanager/lib/requestmanagertimeslots.lib.php');
                $res = requestmanagertimeslots_get_periods($object->array_options['options_rm_timeslots_periods']);
                if (!is_array($res)) {
                    $this->errors[] = $langs->trans('RequestManagerTimeSlotsPeriodsName') . ': ' . $res;
                    return -1;
                }

                dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
                break;

        }

        return 0;
    }
}