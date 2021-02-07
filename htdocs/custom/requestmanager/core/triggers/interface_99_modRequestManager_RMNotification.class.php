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
 *	\file       htdocs/requestmanager/core/triggers/interface_99_modRequestManager_RMNotification.class.php
 *  \ingroup    requestmanager
 *	\brief      File of class of triggers for notification in requestmanager module
 */


require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';


/**
 *  Class of triggers for notification in RequestManager module
 */
class InterfaceRMNotification extends DolibarrTriggers
{
	public $family = 'notification';
	public $description = "Triggers of this module send email notifications according to RequestManager module setup.";
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
        if (empty($conf->requestmanager->enabled)) return 0;     // Module not active, we do nothing

        $key = $action . '_NOTIFY';

        // Do not notify, not enabled for this action
        if (empty($conf->global->$key)) {
            return 0;
        }

        if ($action == 'REQUESTMANAGER_CREATE') {
            // Notification by email of a new message to the request
            dol_include_once('/requestmanager/class/requestmanagernotify.class.php');
            $requestmanagernotify = new RequestManagerNotify($this->db);

            $requestmanagernotify->sendNotify(empty($object->created_out_of_time) ? RequestManagerNotify::TYPE_REQUEST_CREATED : RequestManagerNotify::TYPE_REQUEST_CREATED_OUT_OF_TIME, $object);
            if (count($requestmanagernotify->errors)) {
                $object->context['send_notify_errors'] = $requestmanagernotify->errors;
            }

            // Notify by website to assigned
            if (!empty($conf->global->REQUESTMANAGER_NOTIFICATION_ASSIGNED_BY_WEBSITE)) {

            }

            dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
            return 1;
        } elseif ($action == 'REQUESTMANAGERMESSAGE_CREATE') {
            // Notification by email of a new message to the request
            dol_include_once('/requestmanager/class/requestmanagernotify.class.php');
            $requestmanagernotify = new RequestManagerNotify($this->db);

            $object->fetch_requestmanager();
            $requestmanagernotify->sendNotify(RequestManagerNotify::TYPE_MESSAGE_ADDED, $object->requestmanager, $object);
            if (count($requestmanagernotify->errors)) {
                $object->context['send_notify_errors'] = $requestmanagernotify->errors;
            }

            // Notify by website to assigned
            if (!empty($conf->global->REQUESTMANAGER_NOTIFICATION_ASSIGNED_BY_WEBSITE)) {

            }

            dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
            return 1;
        } elseif ($action == 'REQUESTMANAGER_SET_ASSIGNED') {
            if (!empty($object->assigned_user_added_ids) || !empty($object->assigned_usergroup_added_ids) ||
                !empty($object->assigned_user_deleted_ids) || !empty($object->assigned_usergroup_deleted_ids)
            ) {
                // Notification by email of a modification of assigned user or user groups to the request
                dol_include_once('/requestmanager/class/requestmanagernotify.class.php');
                $requestmanagernotify = new RequestManagerNotify($this->db);

                $requestmanagernotify->sendNotify(RequestManagerNotify::TYPE_ASSIGNED_MODIFIED, $object);
                if (count($requestmanagernotify->errors)) {
                    $object->context['send_notify_errors'] = $requestmanagernotify->errors;
                }

                // Notify by website to assigned
                if (!empty($conf->global->REQUESTMANAGER_NOTIFICATION_ASSIGNED_BY_WEBSITE)) {

                }
            }

            dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
            return 1;
        } elseif ($action == 'REQUESTMANAGER_STATUS_MODIFY') {
            if ($object->statut != $object->oldcopy->statut) {
                // Notification by email of a modification of status of the request
                dol_include_once('/requestmanager/class/requestmanagernotify.class.php');
                $requestmanagernotify = new RequestManagerNotify($this->db);

                $requestmanagernotify->sendNotify(RequestManagerNotify::TYPE_STATUS_MODIFIED, $object);
                if (count($requestmanagernotify->errors)) {
                    $object->context['send_notify_errors'] = $requestmanagernotify->errors;
                }

                // Notify by website to assigned
                if (!empty($conf->global->REQUESTMANAGER_NOTIFICATION_ASSIGNED_BY_WEBSITE)) {

                }
            }

            dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
            return 1;
        }

        return 0;
    }
}
