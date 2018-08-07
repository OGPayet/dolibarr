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
 *	\file       htdocs/requestmanager/core/triggers/interface_99_modRequestManager_Notification.class.php
 *  \ingroup    requestmanager
 *	\brief      File of class of triggers for notification in requestmanager module
 */


require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';


/**
 *  Class of triggers for notification in requestmanager module
 */
class InterfaceRMNotification extends DolibarrTriggers
{
	public $family = 'requestmanager';
	public $description = "Triggers of this module send notifications according to RequestManager module setup.";
	public $version = self::VERSION_DOLIBARR;
	public $picto = 'email';


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
	    if ($action == 'REQUESTMANAGER_STATUS_MODIFY') {
            dol_include_once('/requestmanager/class/requestmanager.class.php');

            $result = $object->createActionCommAndNotifyFromTemplateType(RequestManager::TEMPLATE_TYPE_NOTIFY_STATUS_MODIFIED, RequestManager::ACTIONCOMM_TYPE_CODE_STAT);

            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
	        return $result;
        }

        return 0;
    }
}