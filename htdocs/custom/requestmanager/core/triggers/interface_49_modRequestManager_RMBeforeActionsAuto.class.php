<?php
/* Copyright (C) 2005-2017	Laurent Destailleur 	<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2017	Regis Houssin		    <regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2014	Juanjo Menent		    <jmenent@2byte.es>
 * Copyright (C) 2013		Cedric GROSS			<c.gross@kreiz-it.fr>
 * Copyright (C) 2014		Marcos García		    <marcosgdf@gmail.com>
 * Copyright (C) 2015		Bahfir Abbes			<bafbes@gmail.com>
 * Copyright (C) 2018		Open-Dsi			    <support@open-dsi.fr>
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
 *	\file       htdocs/requestmanager/core/triggers/interface_49_modRequestManager_RMBeforeActionsAuto.class.php
 *  \ingroup    agenda
 *  \brief      Trigger file for agenda module
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';


/**
 *  Class of triggered functions for agenda module
 */
class InterfaceRMBeforeActionsAuto extends DolibarrTriggers
{
	public $family = 'agenda';
	public $description = "Triggers of this module add actions in agenda according to setup made in agenda setup before standard triggers and desactive standard trigger for RequestManager actions.";
	public $version = self::VERSION_DOLIBARR;
	public $picto = 'action';

	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "runTrigger" are triggered if file is inside directory htdocs/core/triggers or htdocs/module/code/triggers (and declared)
	 *
	 * Following properties may be set before calling trigger. The may be completed by this trigger to be used for writing the event into database:
	 *      $object->actiontypecode (translation action code: AC_OTH, ...)
	 *      $object->actionmsg (note, long text)
	 *      $object->actionmsg2 (label, short text)
	 *      $object->sendtoid (id of contact or array of ids)
	 *      $object->socid (id of thirdparty)
	 *      $object->fk_project
	 *      $object->fk_element
	 *      $object->elementtype
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
        if (empty($conf->agenda->enabled)) return 0;     // Module not active, we do nothing

        if ($action == 'REQUESTMANAGER_ADD_CONTACT' || $action == 'REQUESTMANAGER_DELETE_CONTACT' ||
            $action == 'LINEREQUESTMANAGER_INSERT' || $action == 'LINEREQUESTMANAGER_UPDATE' || $action == 'LINEREQUESTMANAGER_DELETE' ||
            $action == 'REQUESTMANAGER_EXTRAFIELDS_ADD_UPDATE' ||
            $action == 'REQUESTMANAGER_ADD_LINK' || $action == 'REQUESTMANAGER_DEL_LINK'
        ) {
            $action = 'REQUESTMANAGER_MODIFY';
        }

		$key = 'MAIN_AGENDA_ACTIONAUTO_'.$action;

		// Do not log events not enabled for this action
		if (empty($conf->global->$key)) {
			return 0;
		}

		$langs->load("agenda");

		if (empty($object->actiontypecode)) $object->actiontypecode='AC_OTH_AUTO';

		/**
         * Actions
         */
		$founded = false;
        $fk_element = $object->id;
        $elementtype = $object->element;

        // RequestManager
        //----------------------------------------
		if ($action == 'REQUESTMANAGER_CREATE') {
            $langs->load("other");
            $langs->load("requestmanager@requestmanager");

            if (empty($object->actionmsg2)) $object->actionmsg2 = $langs->transnoentities("RequestManagerCreatedInDolibarr", $object->ref);
            $object->actionmsg = $langs->transnoentities("RequestManagerCreatedInDolibarr", $object->ref);

            $object->sendtoid = 0;
            $founded = true;
        }
        elseif ($action == 'REQUESTMANAGER_MODIFY') {
            $langs->load("other");
            $langs->load("requestmanager@requestmanager");

            dol_include_once('/requestmanager/lib/opendsi_tools.lib.php');
            if (empty($object->actionmsg2)) $object->actionmsg2 = $langs->transnoentities("RequestManagerModifiedInDolibarr", $object->ref);
            $object->actionmsg = $langs->transnoentities("RequestManagerModifiedInDolibarr", $object->ref);
            $updatedProperty = array();
            // Request type
            if (opendsi_is_updated_property($object, 'fk_type')) {
                $updatedProperty[] = '- ' . $langs->transnoentities("RequestManagerRequestType") . ' : ' . $object->getLibType(2) . ' ( ' . $object->oldcopy->getLibType(2) . ' )';
            }
            // Request category
            if (opendsi_is_updated_property($object, 'fk_category')) {
                $updatedProperty[] = '- ' . $langs->transnoentities("RequestManagerCategory") . ' : ' . $object->getLibCategory(2) . ' ( ' . $object->oldcopy->getLibCategory(2) . ' )';
            }
            // Request title
            if (opendsi_is_updated_property($object, 'label')) {
                $updatedProperty[] = '- ' . $langs->transnoentities("RequestManagerLabel") . ' : ' . $object->label . ' ( ' . $object->oldcopy->label . ' )';
            }
            // Origin company
            if (opendsi_is_updated_property($object, 'socid_origin')) {
                $object->fetch_thirdparty_origin();
                $object->oldcopy->fetch_thirdparty_origin();
                $updatedProperty[] = '- ' . $langs->transnoentities("RequestManagerThirdPartyOrigin") . ' : ' . $object->thirdparty_origin->getFullName($langs) . ' ( ' . $object->oldcopy->thirdparty_origin->getFullName($langs) . ' )';
            }
            // Principal company
            if (opendsi_is_updated_property($object, 'socid')) {
                $object->fetch_thirdparty();
                $object->oldcopy->fetch_thirdparty();
                $updatedProperty[] = '- ' . $langs->transnoentities("RequestManagerThirdPartyPrincipal") . ' : ' . $object->thirdparty->getFullName($langs) . ' ( ' . $object->oldcopy->thirdparty->getFullName($langs) . ' )';
            }
            // Benefactor company
            if (opendsi_is_updated_property($object, 'socid_benefactor')) {
                $object->fetch_thirdparty_benefactor();
                $object->oldcopy->fetch_thirdparty_benefactor();
                $updatedProperty[] = '- ' . $langs->transnoentities("RequestManagerThirdPartyBenefactor") . ' : ' . $object->thirdparty_benefactor->getFullName($langs) . ' ( ' . $object->oldcopy->thirdparty_benefactor->getFullName($langs) . ' )';
            }
            // Request source
            if (opendsi_is_updated_property($object, 'fk_source')) {
                $updatedProperty[] = '- ' . $langs->transnoentities("RequestManagerSource") . ' : ' . $object->getLibSource(2) . ' ( ' . $object->oldcopy->getLibSource(2) . ' )';
            }
            // Request urgency
            if (opendsi_is_updated_property($object, 'fk_urgency')) {
                $updatedProperty[] = '- ' . $langs->transnoentities("RequestManagerUrgency") . ' : ' . $object->getLibUrgency(2) . ' ( ' . $object->oldcopy->getLibUrgency(2) . ' )';
            }
            // Request impact
            if (opendsi_is_updated_property($object, 'fk_impact')) {
                $updatedProperty[] = '- ' . $langs->transnoentities("RequestManagerImpact") . ' : ' . $object->getLibImpact(2) . ' ( ' . $object->oldcopy->getLibImpact(2) . ' )';
            }
            // Request priority
            if (opendsi_is_updated_property($object, 'fk_priority')) {
                $updatedProperty[] = '- ' . $langs->transnoentities("RequestManagerPriority") . ' : ' . $object->getLibPriority(2) . ' ( ' . $object->oldcopy->getLibPriority(2) . ' )';
            }
            // Request duration
            if (opendsi_is_updated_property($object, 'duration')) {
                dol_include_once('/requestmanager/lib/requestmanager.lib.php');
                $updatedProperty[] = '- ' . $langs->transnoentities("RequestManagerDuration") . ' : ' . requestmanager_print_duration($object->duration) . ' ( ' . requestmanager_print_duration($object->oldcopy->duration) . ' )';
            }
            // Request operation date
            if (opendsi_is_updated_property($object, 'date_operation')) {
                $updatedProperty[] = '- ' . $langs->transnoentities("RequestManagerOperation") . ' : ' . dol_print_date($object->date_operation, 'day') . ' ( ' . dol_print_date($object->oldcopy->date_operation, 'day') . ' )';
            }
            // Request deadline date
            if (opendsi_is_updated_property($object, 'date_deadline')) {
                $updatedProperty[] = '- ' . $langs->transnoentities("RequestManagerDeadline") . ' : ' . dol_print_date($object->date_deadline, 'day') . ' ( ' . dol_print_date($object->oldcopy->date_deadline, 'day') . ' )';
            }
            // Request notify assigned
            if (opendsi_is_updated_property($object, 'notify_assigned_by_email')) {
                $updatedProperty[] = '- ' . $langs->transnoentities("RequestManagerAssignedNotification") . ' : ' . yn($object->notify_assigned_by_email) . ' ( ' . yn($object->oldcopy->notify_assigned_by_email) . ' )';
            }
            // Request tags
            if (opendsi_is_updated_property($object, 'dd')) {
                $updatedProperty[] = '- ' . $langs->transnoentities("RequestManagerTags") . ' : ' . $object->thirdparty_benefactor->getFullName($langs) . ' ( ' . $object->oldcopy->thirdparty_benefactor->getFullName($langs) . ' )';
            }
            // Request description
            if (opendsi_is_updated_property($object, 'description')) {
                $updatedProperty[] = '- ' . $langs->transnoentities("RequestManagerNewDescription") . ' :<br>' . dol_nl2br($object->description);
                $updatedProperty[] = '- ' . $langs->transnoentities("RequestManagerOldDescription") . ' :<br>' . dol_nl2br($object->oldcopy->description);
            }
            // Request origin contacts
            if (opendsi_is_updated_property($object, 'dd')) {
                $updatedProperty[] = '- ' . $langs->transnoentities("RequestManagerRequesterContacts") . ' : ' . $object->thirdparty_benefactor->getFullName($langs) . ' ( ' . $object->oldcopy->thirdparty_benefactor->getFullName($langs) . ' )';
            }
            // Request notify origin contacts
            if (opendsi_is_updated_property($object, 'notify_requester_by_email')) {
                $updatedProperty[] = '- ' . $langs->transnoentities("RequestManagerRequesterNotification") . ' : ' . yn($object->notify_requester_by_email) . ' ( ' . yn($object->oldcopy->notify_requester_by_email) . ' )';
            }
            // Request watcher contacts
            if (opendsi_is_updated_property($object, 'dd')) {
                $updatedProperty[] = '- ' . $langs->transnoentities("RequestManagerWatcherContacts") . ' : ' . $object->thirdparty_benefactor->getFullName($langs) . ' ( ' . $object->oldcopy->thirdparty_benefactor->getFullName($langs) . ' )';
            }
            // Request notify watcher contacts
            if (opendsi_is_updated_property($object, 'notify_watcher_by_email')) {
                $updatedProperty[] = '- ' . $langs->transnoentities("RequestManagerWatcherNotification") . ' : ' . yn($object->notify_watcher_by_email) . ' ( ' . yn($object->oldcopy->notify_watcher_by_email) . ' )';
            }
            // Request linked objects
            if (opendsi_is_updated_property($object, 'dd')) {
                $updatedProperty[] = '- ' . $langs->transnoentities("RelatedObjects") . ' : ' . $object->thirdparty_benefactor->getFullName($langs) . ' ( ' . $object->oldcopy->thirdparty_benefactor->getFullName($langs) . ' )';
            }
            // Request product lines
            if (opendsi_is_updated_property($object, 'dd')) {
                $updatedProperty[] = '- ' . $langs->transnoentities("RequestManagerNewProductLines") . ' :<br>' . $object->thirdparty_benefactor->getFullName($langs) . ' ( ' . $object->oldcopy->thirdparty_benefactor->getFullName($langs) . ' )';
                $updatedProperty[] = '- ' . $langs->transnoentities("RequestManagerOldProductLines") . ' :<br>' . $object->thirdparty_benefactor->getFullName($langs) . ' ( ' . $object->oldcopy->thirdparty_benefactor->getFullName($langs) . ' )';
            }
            if (!empty($object->context['updated_properties']) && is_array($object->context['updated_properties'])) {
                $updatedProperty = array_merge($updatedProperty, $object->context['updated_properties']);
            }
            // count(array_diff($assigned_users, $this->assigned_user_ids))
            if (!empty($updatedProperty)) {
                $object->actionmsg .= '<br>' . $langs->transnoentities("RequestManagerModifiedPropertiesList") . ' : ';
                $object->actionmsg .= '<br>' . implode('<br>', $updatedProperty);
            } else {
                dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id . " : No properties modified.");
                $object->skipstandardaction = true;
                $conf->global->$key = 0;
                return 0;
            }

            $object->sendtoid = 0;
            $founded = true;
        }
        elseif ($action == 'REQUESTMANAGER_DELETE') {
            $langs->load("other");
            $langs->load("requestmanager@requestmanager");

            if (empty($object->actionmsg2)) $object->actionmsg2 = $langs->transnoentities("RequestManagerDeletedInDolibarr", $object->ref);
            $object->actionmsg = $langs->transnoentities("RequestManagerDeletedInDolibarr", $object->ref);

            $object->sendtoid = 0;
            $founded = true;
        }
        elseif ($action == 'REQUESTMANAGER_SET_ASSIGNED') {
            $langs->load("other");
            $langs->load("requestmanager@requestmanager");

            $object->actiontypecode = 'AC_RM_ASSMOD';

            if (empty($object->actionmsg2)) $object->actionmsg2 = $langs->transnoentities("RequestManagerAssignedModifiedInDolibarr", $object->ref);
            $object->actionmsg = $langs->transnoentities("RequestManagerAssignedModifiedInDolibarr", $object->ref);

            $assignedAdded = array();
            $assignedDeleted = array();
            $users_cache = array();
            $usergroups_cache = array();
            // Assigned users added
            if (!empty($object->assigned_user_added_ids)) {
                $user_names = array();
                foreach ($object->assigned_user_added_ids as $user_id) {
                    if (!isset($users_cache[$user_id])) {
                        $user_static = new User($this->db);
                        $user_static->fetch($user_id);
                        $users_cache[$user_id] = $user_static;
                    }
                    $user_names[] = $users_cache[$user_id]->getFullName($langs);
                }
                $assignedAdded[] = $langs->transnoentities("RequestManagerAssignedUsersAdded") . ' : ' . implode(', ', $user_names);
            }
            // Assigned user groups added
            if (!empty($object->assigned_usergroup_added_ids)) {
                $usergroup_names = array();
                foreach ($object->assigned_usergroup_ids as $usergroup_id) {
                    if (!isset($usergroups_cache[$usergroup_id])) {
                        $usergroup_static = new UserGroup($this->db);
                        $usergroup_static->fetch($usergroup_id);
                        $usergroups_cache[$usergroup_id] = $usergroup_static;
                    }
                    $usergroup_names[] = $usergroups_cache[$usergroup_id]->name;
                }
                $assignedAdded[] = $langs->transnoentities("RequestManagerAssignedUserGroupsAdded") . ' : ' . implode(', ', $usergroup_names);
            }
            // Assigned users deleted
            if (!empty($object->assigned_user_deleted_ids)) {
                $user_names = array();
                foreach ($object->assigned_user_deleted_ids as $user_id) {
                    if (!isset($users_cache[$user_id])) {
                        $user_static = new User($this->db);
                        $user_static->fetch($user_id);
                        $users_cache[$user_id] = $user_static;
                    }
                    $user_names[] = $users_cache[$user_id]->getFullName($langs);
                }
                $assignedDeleted[] = $langs->transnoentities("RequestManagerAssignedUsersDeleted") . ' : ' . implode(', ', $user_names);
            }
            // Assigned user groups deleted
            if (!empty($object->assigned_usergroup_deleted_ids)) {
                $usergroup_names = array();
                foreach ($object->assigned_usergroup_ids as $usergroup_id) {
                    if (!isset($usergroups_cache[$usergroup_id])) {
                        $usergroup_static = new UserGroup($this->db);
                        $usergroup_static->fetch($usergroup_id);
                        $usergroups_cache[$usergroup_id] = $usergroup_static;
                    }
                    $usergroup_names[] = $usergroups_cache[$usergroup_id]->name;
                }
                $assignedDeleted[] = $langs->transnoentities("RequestManagerAssignedUserGroupsDeleted") . ' : ' . implode(', ', $usergroup_names);
            }

            if (!empty($assignedAdded) || !empty($assignedDeleted)) {
                if (!empty($assignedAdded)) $object->actionmsg .= '<br>' . implode('<br>', $assignedAdded);
                if (!empty($assignedDeleted)) $object->actionmsg .= '<br>' . implode('<br>', $assignedDeleted);
            } else {
                dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id . " : No assigned used or user group modified.");
                $object->skipstandardaction = true;
                $conf->global->$key = 0;
                return 0;
            }

            $object->sendtoid = 0;
            $founded = true;
        }
        elseif ($action == 'REQUESTMANAGER_STATUS_MODIFY') {
            $langs->load("other");
            $langs->load("requestmanager@requestmanager");

            $object->actiontypecode = 'AC_RM_STATUS';

            if (empty($object->actionmsg2)) $object->actionmsg2 = $langs->transnoentities("RequestManagerStatusModifiedInDolibarr", $object->ref);
            $object->actionmsg = $langs->transnoentities("RequestManagerStatusModifiedInDolibarr", $object->ref);
            //if ($object->new_statut != $object->statut) {
                $object->actionmsg .= '<br>' . $langs->transnoentities("RequestManagerOldStatus") . ' : ' . $object->LibStatut($object->statut) . ' ( ' . $object->LibStatut($object->statut, 12) . ' )';
                $object->actionmsg .= '<br>' . $langs->transnoentities("RequestManagerNewStatus") . ' : ' . $object->LibStatut($object->new_statut) . ' ( ' . $object->LibStatut($object->new_statut, 12) . ' )';

                if ($object->fk_reason_resolution > 0) {
                    $object->actionmsg .= '<br>' . $langs->transnoentities("RequestManagerReasonResolution") . ' : ' . $object->getLibReasonResolution(2);
                }
                if (!empty($object->reason_resolution_details)) {
                    $object->actionmsg .= '<br>' . $langs->transnoentities("RequestManagerReasonResolutionDetails") . ' : ' . $object->reason_resolution_details;
                }
            /*} else {
                dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id . " : Status not modified.");
                $object->skipstandardaction = true;
                $conf->global->$key = 0;
                return 0;
            }*/

            $object->sendtoid = 0;
            $founded = true;
        }
        elseif ($action == 'REQUESTMANAGER_SET_ASSIGNED_SENTBYMAIL') {
            $langs->load("agenda");
            $langs->load("other");
            $langs->load("requestmanager@requestmanager");

            if (empty($object->actionmsg2)) dol_syslog('Trigger called with property actionmsg2 on object not defined', LOG_ERR);

            // Parameters $object->sendtoid defined by caller
            //$object->sendtoid=0;
            $founded = true;
		}
        elseif ($action == 'REQUESTMANAGER_STATUS_MODIFY_SENTBYMAIL') {
            $langs->load("agenda");
            $langs->load("other");
            $langs->load("requestmanager@requestmanager");

            if (empty($object->actionmsg2)) dol_syslog('Trigger called with property actionmsg2 on object not defined', LOG_ERR);

            // Parameters $object->sendtoid defined by caller
            //$object->sendtoid=0;
            $founded = true;
		}

        // RequestManager Message
        //----------------------------------------
        elseif ($action == 'REQUESTMANAGERMESSAGE_SENTBYMAIL') {
            $langs->load("agenda");
            $langs->load("other");
            $langs->load("requestmanager@requestmanager");

            if (empty($object->actionmsg2)) dol_syslog('Trigger called with property actionmsg2 on object not defined', LOG_ERR);

            // Parameters $object->sendtoid defined by caller
            //$object->sendtoid=0;
            $founded = true;
		}

        if ($founded) {
            $object->actionmsg .= '<br><br>' . $langs->transnoentities("Author") . ': ' . $user->login;
            $object->skipstandardaction = true;
            $conf->global->$key = 0;

            dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);

            // Add entry in event table
            $now = dol_now();

            if (isset($_SESSION['listofnames-' . $object->trackid])) {
                $attachs = $_SESSION['listofnames-' . $object->trackid];
                if ($attachs && strpos($action, 'SENTBYMAIL')) {
                    $object->actionmsg = dol_concatdesc($object->actionmsg, '<br>' . $langs->transnoentities("AttachedFiles") . ': ' . $attachs);
                }
            }

            require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
            require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
            $contactforaction = new Contact($this->db);
            $societeforaction = new Societe($this->db);
            // Set contactforaction if there is only 1 contact.
            if (is_array($object->sendtoid)) {
                if (count($object->sendtoid) == 1) $contactforaction->fetch(reset($object->sendtoid));
            } else {
                if ($object->sendtoid > 0) $contactforaction->fetch($object->sendtoid);
            }
            // Set societeforaction.
            if ($object->socid > 0) $societeforaction->fetch($object->socid);

            $projectid = isset($object->fk_project) ? $object->fk_project : 0;
            if ($object->element == 'project') $projectid = $object->id;

            // Insertion action
            require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
            $actioncomm = new ActionComm($this->db);
            $actioncomm->type_code = $object->actiontypecode;        // Type of event ('AC_OTH', 'AC_OTH_AUTO', 'AC_XXX'...)
            $actioncomm->code = 'AC_' . $action;
            $actioncomm->label = $object->actionmsg2;
            $actioncomm->note = $object->actionmsg;          // TODO Replace with $actioncomm->email_msgid ? $object->email_content : $object->actionmsg
            $actioncomm->fk_project = $projectid;
            $actioncomm->datep = $now;
            $actioncomm->datef = $now;
            $actioncomm->durationp = 0;
            $actioncomm->punctual = 1;
            $actioncomm->percentage = -1;   // Not applicable
            $actioncomm->societe = $societeforaction;
            $actioncomm->contact = $contactforaction;
            $actioncomm->socid = $societeforaction->id;
            $actioncomm->contactid = $contactforaction->id;
            $actioncomm->authorid = $user->id;   // User saving action
            $actioncomm->userownerid = $user->id;    // Owner of action
            // Fields when action is en email (content should be added into note)
            $actioncomm->email_msgid = $object->email_msgid;
            $actioncomm->email_from = $object->email_from;
            $actioncomm->email_sender = $object->email_sender;
            $actioncomm->email_to = $object->email_to;
            $actioncomm->email_tocc = $object->email_tocc;
            $actioncomm->email_tobcc = $object->email_tobcc;
            $actioncomm->email_subject = $object->email_subject;
            $actioncomm->errors_to = $object->errors_to;

            $actioncomm->fk_element = $fk_element;
            $actioncomm->elementtype = $elementtype;

            $ret = $actioncomm->create($user);       // User creating action

            if ($ret > 0 && $conf->global->MAIN_COPY_FILE_IN_EVENT_AUTO) {
                if (is_array($object->attachedfiles) && array_key_exists('paths', $object->attachedfiles) && count($object->attachedfiles['paths']) > 0) {
                    foreach ($object->attachedfiles['paths'] as $key => $filespath) {
                        $srcfile = $filespath;
                        $destdir = $conf->agenda->dir_output . '/' . $ret;
                        $destfile = $destdir . '/' . $object->attachedfiles['names'][$key];
                        if (dol_mkdir($destdir) >= 0) {
                            require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
                            dol_copy($srcfile, $destfile);
                        }
                    }
                }
            }

            unset($object->actionmsg);
            unset($object->actionmsg2);
            unset($object->actiontypecode);    // When several action are called on same object, we must be sure to not reuse value of first action.

            if ($ret > 0) {
                $_SESSION['LAST_ACTION_CREATED'] = $ret;
                return 1;
            } else {
                $error = "Failed to insert event : " . $actioncomm->error . " " . join(',', $actioncomm->errors);
                $this->error = $error;
                $this->errors = $actioncomm->errors;

                dol_syslog("interface_modAgenda_ActionsAuto.class.php: " . $this->error, LOG_ERR);
                return -1;
            }
        }

        return 0;
    }
}
