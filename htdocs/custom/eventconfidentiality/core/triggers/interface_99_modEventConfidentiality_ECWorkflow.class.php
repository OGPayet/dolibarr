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
class InterfaceECWorkflow extends DolibarrTriggers
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
	    if ($action == 'ACTION_CREATE') {
			$tags = GETPOST('add_tag', 'array');
			$externe = GETPOST('add_external');
			$mode = GETPOST('add_mode');
        }

	    if ($action == 'ACTION_MODIFY') {
			$tags = GETPOST('edit_tag', 'array');
			$externe = GETPOST('edit_external');
			$mode = GETPOST('edit_mode');
        }

        return 0;
    }
}