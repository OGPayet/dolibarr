<?php
/* Copyright (C) 2018      Open-DSI              <support@open-dsi.fr>
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
 * \file 		htdocs/comm/requestmanager/card.php
 * \ingroup 	requestmanager
 * \brief 		Page of Request card
 */

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include '../main.inc.php';					// to work if your module directory is into dolibarr root htdocs directory
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (! $res && file_exists("../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../dolibarr/htdocs/main.inc.php';     // Used on dev env only
if (! $res && file_exists("../../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../../dolibarr/htdocs/main.inc.php';   // Used on dev env only
if (! $res) die("Include of main fails");
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
dol_include_once('/advancedictionaries/class/dictionary.class.php');
dol_include_once('/requestmanager/class/requestmanager.class.php');
dol_include_once('/requestmanager/class/requestmanagernotification.class.php');
dol_include_once('/requestmanager/class/html.formrequestmanager.class.php');
dol_include_once('/requestmanager/class/html.formrequestmanagermessage.class.php');
dol_include_once('/requestmanager/lib/requestmanager.lib.php');

$langs->load('mails');
$langs->load('requestmanager@requestmanager');

$error = 0;

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'alpha');
$cancel = GETPOST('cancel', 'alpha');
$confirm = GETPOST('confirm', 'alpha');

// Security check
$result = restrictedArea($user, 'requestmanager', $id);

$object = new RequestManager($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);

// Load object
if ($id > 0 || !empty($ref)) {
	$ret = $object->fetch($id, $ref);
    if ($ret > 0) {
        $ret = $object->fetch_thirdparty();
        $ret = $object->fetch_optionals();
    } elseif ($ret < 0) {
        dol_print_error('', $object->error);
    } else {
        print $langs->trans('NoRecordFound');
        exit();
    }
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('requestmanagercard','globalcard'));

$permissiondellink = $user->rights->requestmanager->creer; 	// Used by the include of actions_dellink.inc.php

/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
    if ($cancel) $action = '';

    include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';		// Must be include, not include_once

    // Create request
    if ($action == 'add' && $user->rights->requestmanager->creer) {
        $object->fk_type = GETPOST('type', 'int');
        $object->fk_category = GETPOST('category', 'int');
        $object->label = GETPOST('label', 'alpha');
        $object->socid = GETPOST('socid', 'int');
        $object->fk_source = GETPOST('source', 'int');
        $object->fk_urgency = GETPOST('urgency', 'int');
        $object->fk_impact = GETPOST('impact', 'int');
        $object->fk_priority = GETPOST('priority', 'int');
        $object->date_deadline = dol_mktime(GETPOST('deadline_hour', 'int'), GETPOST('deadline_minute', 'int'), 0, GETPOST('deadline_month', 'int'), GETPOST('deadline_day', 'int'), GETPOST('deadline_year', 'int'));
        $object->requester_ids = GETPOST('requester_contacts', 'array');
        $object->notify_requester_by_email = GETPOST('requester_notification', 'int');
        $object->watcher_ids = GETPOST('watcher_contacts', 'array');
        $object->notify_watcher_by_email = GETPOST('watcher_notification', 'int');
        $object->assigned_usergroup_id = GETPOST('assigned_usergroup', 'int');
        $object->assigned_user_id = GETPOST('assigned_user', 'int');
        $object->notify_assigned_by_email = GETPOST('assigned_notification', 'int');
        $object->description = GETPOST('description');

        // Possibility to add external linked objects with hooks
        $object->origin = GETPOST('origin', 'alpha');
        $object->origin_id = GETPOST('originid', 'int');
        if ($object->origin && $object->origin_id) {
            $object->linkedObjectsIds[$object->origin] = $object->origin_id;
            if (is_array($_POST['other_linked_objects']) && !empty($_POST['other_linked_objects'])) {
                $object->linkedObjectsIds = array_merge($object->linkedObjectsIds, $_POST['other_linked_objects']);
            }
        }

        // Fill array 'array_options' with data from add form
        $ret = $extrafields->setOptionalsFromPost($extralabels, $object);
        if ($ret < 0) {
            $error++;
        }

        if (!$error) {
            $db->begin();

            $id = $object->create($user);
            if ($id < 0) {
                setEventMessages($object->error, $object->errors, 'errors');
                $error++;
            }
        }

        if (!$error) {
            $db->commit();
            header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $id);
            exit();
        } else {
            $db->rollback();
            $action = 'create';
        }
    } // Delete request
    elseif ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->requestmanager->supprimer) {
        $result = $object->delete($user);
        if ($result > 0) {
            header('Location: ' . dol_buildpath('/requestmanager/list.php', 2));
            exit();
        } else {
            $langs->load("errors");
            setEventMessages($object->error, $object->errors, 'errors');
        }
    } // Set Type
    elseif ($action == 'set_type' && $user->rights->requestmanager->creer &&
        $object->statut_type == RequestManager::STATUS_TYPE_INITIAL
    ) {
        $object->oldcopy = clone $object;
        $object->fk_type = GETPOST('type', 'int');
        $object->fk_category = 0;

        $db->begin();

        $result = $object->set_status(0, RequestManager::STATUS_TYPE_INITIAL, $user, 0, 1);
        if ($result < 0) {
            setEventMessages($object->error, $object->errors, 'errors');
            $error++;
        }

        if (!$error) {
            $result = $object->update($user);
            if ($result < 0) {
                setEventMessages($object->error, $object->errors, 'errors');
                $error++;
            }
        }

        if (!$error) {
            $db->commit();
            header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
            exit();
        } else {
            $db->rollback();
            $action = 'edit_type';
        }
    } // Set Category
    elseif ($action == 'set_category' && $user->rights->requestmanager->creer &&
        ($object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS || $object->statut_type == RequestManager::STATUS_TYPE_INITIAL)
    ) {
        $object->oldcopy = clone $object;
        $object->fk_category = GETPOST('category', 'int');
        $result = $object->update($user);
        if ($result < 0) {
            setEventMessages($object->error, $object->errors, 'errors');
            $action = 'edit_category';
        } else {
            header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
            exit();
        }
    } // Set Label
    elseif ($action == 'set_label' && $user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS) {
        $object->oldcopy = clone $object;
        $object->label = GETPOST('label', 'alpha');
        $result = $object->update($user);
        if ($result < 0) {
            setEventMessages($object->error, $object->errors, 'errors');
            $action = 'edit_label';
        } else {
            header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
            exit();
        }
    } // Set ThirdParty
    elseif ($action == 'set_thirdparty' && $user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS) {
        $object->oldcopy = clone $object;
        $object->socid = GETPOST('socid', 'int');
        $result = $object->update($user);
        if ($result < 0) {
            setEventMessages($object->error, $object->errors, 'errors');
            $action = 'edit_thirdparty';
        } else {
            header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
            exit();
        }
    } // Set Source
    elseif ($action == 'set_source' && $user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS) {
        $object->oldcopy = clone $object;
        $object->fk_source = GETPOST('source', 'int');
        $result = $object->update($user);
        if ($result < 0) {
            setEventMessages($object->error, $object->errors, 'errors');
            $action = 'edit_source';
        } else {
            header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
            exit();
        }
    } // Set Urgency
    elseif ($action == 'set_urgency' && $user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS) {
        $object->oldcopy = clone $object;
        $object->fk_urgency = GETPOST('urgency', 'int');
        $result = $object->update($user);
        if ($result < 0) {
            setEventMessages($object->error, $object->errors, 'errors');
            $action = 'edit_urgency';
        } else {
            header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
            exit();
        }
    } // Set Impact
    elseif ($action == 'set_impact' && $user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS) {
        $object->oldcopy = clone $object;
        $object->fk_impact = GETPOST('impact', 'int');
        $result = $object->update($user);
        if ($result < 0) {
            setEventMessages($object->error, $object->errors, 'errors');
            $action = 'edit_impact';
        } else {
            header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
            exit();
        }
    } // Set Priority
    elseif ($action == 'set_priority' && $user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS) {
        $object->oldcopy = clone $object;
        $object->fk_priority = GETPOST('priority', 'int');
        $result = $object->update($user);
        if ($result < 0) {
            setEventMessages($object->error, $object->errors, 'errors');
            $action = 'edit_priority';
        } else {
            header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
            exit();
        }
    } // Set Duration
    elseif ($action == 'set_duration' && $user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS) {
        $object->oldcopy = clone $object;
        $object->duration = GETPOST('duration_day', 'int') * 86400 + GETPOST('duration_hour', 'int') * 3600 + GETPOST('duration_minute', 'int') * 60;
        $result = $object->update($user);
        if ($result < 0) {
            setEventMessages($object->error, $object->errors, 'errors');
            $action = 'edit_duration';
        } else {
            header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
            exit();
        }
    } // Set Date Deadline
    elseif ($action == 'set_date_deadline' && $user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS) {
        $object->oldcopy = clone $object;
        $object->date_deadline = dol_mktime(GETPOST('deadline_hour', 'int'), GETPOST('deadline_minute', 'int'), 0, GETPOST('deadline_month', 'int'), GETPOST('deadline_day', 'int'), GETPOST('deadline_year', 'int'));
        $result = $object->update($user);
        if ($result < 0) {
            setEventMessages($object->error, $object->errors, 'errors');
            $action = 'edit_date_deadline';
        } else {
            header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
            exit();
        }
    } // Set Assigned usergroup
    elseif ($action == 'set_assigned_usergroup' && $user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS) {
        $object->oldcopy = clone $object;
        $object->assigned_usergroup_id = GETPOST('assigned_usergroup', 'int');
        $result = $object->update($user);
        if ($result < 0) {
            setEventMessages($object->error, $object->errors, 'errors');
            $action = 'edit_assigned_usergroup';
        } else {
            header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
            exit();
        }
    } // Set Assigned user
    elseif ($action == 'set_assigned_user' && $user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS) {
        $object->oldcopy = clone $object;
        $object->assigned_user_id = GETPOST('assigned_user', 'int');
        $result = $object->update($user);
        if ($result < 0) {
            setEventMessages($object->error, $object->errors, 'errors');
            $action = 'edit_assigned_user';
        } else {
            header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
            exit();
        }
    } // Set Assigned Notification
    elseif ($action == 'set_assigned_notification' && $user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS) {
        $object->oldcopy = clone $object;
        $object->notify_assigned_by_email = GETPOST('assigned_notification', 'int');
        $result = $object->update($user);
        if ($result < 0) {
            setEventMessages($object->error, $object->errors, 'errors');
            $action = 'edit_assigned_notification';
        } else {
            header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
            exit();
        }
    } // Set Description
    elseif ($action == 'set_description' && $user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS) {
        $object->oldcopy = clone $object;
        $object->description = GETPOST('description');
        $result = $object->update($user);
        if ($result < 0) {
            setEventMessages($object->error, $object->errors, 'errors');
            $action = 'edit_description';
        } else {
            header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
            exit();
        }
    } // Set Requesters
    elseif ($action == 'set_requesters' && $user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS) {
        $object->oldcopy = clone $object;
        $object->requester_ids = GETPOST('requester_contacts', 'array');
        $object->notify_requester_by_email = GETPOST('requester_notification', 'int');
        $result = $object->update($user);
        if ($result < 0) {
            setEventMessages($object->error, $object->errors, 'errors');
            $action = 'edit_requesters';
        } else {
            header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
            exit();
        }
    } // Set Watchers
    elseif ($action == 'set_watchers' && $user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS) {
        $object->oldcopy = clone $object;
        $object->watcher_ids = GETPOST('watcher_contacts', 'array');
        $object->notify_watcher_by_email = GETPOST('watcher_notification', 'int');
        $result = $object->update($user);
        if ($result < 0) {
            setEventMessages($object->error, $object->errors, 'errors');
            $action = 'edit_watchers';
        } else {
            header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
            exit();
        }
    } // Set Status
    elseif ($action == 'set_status' && $user->rights->requestmanager->creer &&
        ($object->statut_type == RequestManager::STATUS_TYPE_INITIAL || $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS)
    ) {
        $status = GETPOST('status', 'int');
        $result = $object->set_status($status, -1, $user);
        if ($result < 0) {
            setEventMessages($object->error, $object->errors, 'errors');
        } else {
            header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
            exit();
        }
    } // Resolve
    elseif ($action == 'confirm_resolve' && $confirm == 'yes' && $user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS) {
        $result = $object->set_status(0, RequestManager::STATUS_TYPE_RESOLVED, $user);
        if ($result < 0) {
            setEventMessages($object->error, $object->errors, 'errors');
        } else {
            header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
            exit();
        }
    } // Close
    elseif ($action == 'confirm_close' && $confirm == 'yes' && $user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_RESOLVED &&
        empty($conf->global->REQUESTMANAGER_AUTO_CLOSE_REQUEST)
    ) {
        $result = $object->set_status(0, RequestManager::STATUS_TYPE_CLOSED, $user);
        if ($result < 0) {
            setEventMessages($object->error, $object->errors, 'errors');
        } else {
            header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
            exit();
        }
    } // Set Not Resolved
    elseif ($action == 'confirm_notresolved' && $confirm == 'yes' && $user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_RESOLVED) {
        $result = $object->set_status(0, RequestManager::STATUS_TYPE_IN_PROGRESS, $user);
        if ($result < 0) {
            setEventMessages($object->error, $object->errors, 'errors');
        } else {
            header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
            exit();
        }
    } // Re Open
    elseif ($action == 'confirm_reopen' && $confirm == 'yes' && $user->rights->requestmanager->cloturer && $object->statut_type == RequestManager::STATUS_TYPE_CLOSED) {
        $result = $object->set_status(0, RequestManager::STATUS_TYPE_IN_PROGRESS, $user);
        if ($result < 0) {
            setEventMessages($object->error, $object->errors, 'errors');
        } else {
            header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
            exit();
        }
    } // Extrafields
    elseif ($action == 'update_extras' && $user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS) {
        // Fill array 'array_options' with data from update form
        $ret = $extrafields->setOptionalsFromPost($extralabels, $object, GETPOST('attribute'));
        if ($ret < 0) {
            $error++;
        }
        if (!$error) {
            $result = $object->insertExtraFields();
            if ($result < 0) {
                setEventMessages($object->error, $object->errors, 'errors');
                $error++;
            }
        }
        if ($error) $action = 'edit_extras';
    }
    // Add message
    elseif ($action == 'addmessage' && $user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS) {
        $messageNotifyByMail = GETPOST('message_notify_by_mail', 'int')?1:0;
        $messageDirection = GETPOST('message_direction', 'int')?intval(GETPOST('message_direction', 'int')):RequestManagerNotification::getMessageDirectionIdDefault();
        $messageSubject = GETPOST('message_subject')?GETPOST('message_subject'):'';
        $messageBody = GETPOST('message_body')?GETPOST('message_body'):'';

        if (!$messageSubject) {
            $error++;
            $object->error = $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('MessageSubject'));
        }

        $actionCommTypeCode = '';
        $actionCommLabel = '';
        $actionCommNote = '';
        if ($messageDirection === RequestManagerNotification::MESSAGE_DIRECTION_ID_IN) {
            // message in
            $actionCommTypeCode = RequestManager::ACTIONCOMM_TYPE_CODE_IN;
            $actionCommLabel = $langs->trans('RequestManagerNotificationMessageIn', $langs->transnoentitiesnoconv($object->ref));
            $actionCommNote = $langs->trans('RequestManagerNotificationMessageIn', $langs->transnoentitiesnoconv($object->ref));

            // TODO : get a message template from dictionnary
            //$template = $object->findNotificationMessageTemplate('notify_status_modified');
        } else if ($messageDirection === RequestManagerNotification::MESSAGE_DIRECTION_ID_OUT) {
            // message out
            $actionCommTypeCode = RequestManager::ACTIONCOMM_TYPE_CODE_OUT;
            $actionCommLabel = $langs->trans('RequestManagerNotificationMessageOut', $langs->transnoentitiesnoconv($object->ref));
            $actionCommNote = $langs->trans('RequestManagerNotificationMessageOut', $langs->transnoentitiesnoconv($object->ref));

            // TODO : get a message template from dictionnary
            //$template = $object->findNotificationMessageTemplate('notify_status_modified');
        } else {
            $error++;
            $object->error = $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('MessageDirection'));
        }

        if (!$error) {
            // create new event
            $langs->load('requestmanager@requestmanager');
            $idActionComm = $object->createActionComm($actionCommTypeCode, $actionCommLabel, $actionCommNote);
            if ($idActionComm < 0) {
                $error++;
            } else {
                // user or group assigned to notify (save in database)
                $requestManagerNotification = new RequestManagerNotification($db);
                $requestManagerNotification->contactList = $object->getUserToNotifyList(1);

                if (count($requestManagerNotification->contactList) > 0) {
                    // notify the assigned user if different of user (only save in database)
                    $result = $requestManagerNotification->notify($idActionComm);
                }

                // send by mail
                if ($messageNotifyByMail === 1 && $messageDirection === RequestManagerNotification::MESSAGE_DIRECTION_ID_OUT) {
                    // if notification by mail is activated and have user to notify
                    if (!empty($conf->global->REQUESTMANAGER_NOTIFICATION_BY_MAIL) && count($requestManagerNotification->contactList)>0) {
                        // notify by mail
                        $result = $requestManagerNotification->notifyByMailForMessageInAndOut($messageSubject, $messageBody, 1);
                    }

                    // send to requesters (sendto) and watchers (copy carbone) if notification activated
                    $requestManagerNotification->contactList = $object->getContactRequestersToNotifyList(1);
                    if (count($requestManagerNotification->contactList) > 0) {
                        $requestManagerNotification->contactCcList = $object->getContactWatchersToNotifyList(1);
                        $result = $requestManagerNotification->notifyByMailForMessageInAndOut($messageSubject, $messageBody);
                    }
                }
            }
        }

        if ($error) {
            setEventMessages($object->error, $object->errors, 'errors');
        } else {
            header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
            exit();
        }

        $action = '';
    }
    else if ($action == 'add_contact' && $user->rights->requestmanager->creer && $object->statut_type != RequestManager::STATUS_TYPE_CLOSED && $object->statut_type != selfRequestManagerSTATUS_TYPE_RESOLVED) {
        $object->add_contact_action(intval(GETPOST('add_contact_type_id')));
    }
    else if ($action == 'del_contact' && $user->rights->requestmanager->creer && $object->statut_type != RequestManager::STATUS_TYPE_CLOSED && $object->statut_type != selfRequestManagerSTATUS_TYPE_RESOLVED) {
        $object->del_contact_action(intval(GETPOST('del_contact_type_id')));
    }


    // Actions to send emails
    //$actiontypecode='AC_OTH_AUTO';
    //$trigger_name='PROPAL_SENTBYMAIL';
    //$paramname='id';
    //$mode='emailfromproposal';
    //$trackid='pro'.$object->id;
    //include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';

    // Actions to build doc
    //$upload_dir = $conf->propal->dir_output;
    //$permissioncreate=$user->rights->propal->creer;
    //include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';
}


/*
 * View
 */

llxHeader('', $langs->trans('RequestManagerRequest'), 'EN:Request_Manager_En|FR:Request_Manager_Fr|ES:Request_Manager_Es');

$form = new Form($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);
$formrequestmanager = new FormRequestManager($db);

$user_static = new User($db);
$usergroup_static = new UserGroup($db);
$contact_static = new Contact($db);

$now = dol_now();

if ($action == 'create')
{
    /*
     *  Creation
     */
	print load_fiche_titre($langs->trans("RequestManagerNewRequest"), '', 'requestmanager@requestmanager');

    $object->fk_type = GETPOST('type', 'int');
    $object->fk_category = GETPOST('category', 'int');
    $object->label = GETPOST('label', 'alpha');
    $object->socid = GETPOST('socid', 'int');
    $object->fk_source = GETPOST('source', 'int');
    $object->fk_urgency = GETPOST('urgency', 'int');
    $object->fk_impact = GETPOST('impact', 'int');
    $object->fk_priority = GETPOST('priority', 'int');
    $object->date_deadline = dol_mktime(GETPOST('deadline_hour', 'int'), GETPOST('deadline_minute', 'int'), 0, GETPOST('deadline_month', 'int'), GETPOST('deadline_day', 'int'), GETPOST('deadline_year', 'int'));
    $object->requester_ids = GETPOST('requester_contacts', 'array');
    $object->notify_requester_by_email = isset($_POST['requester_notification']) ? GETPOST('requester_notification', 'int') : 1;
    $object->watcher_ids = GETPOST('watcher_contacts', 'array');
    $object->notify_watcher_by_email = isset($_POST['watcher_notification']) ? GETPOST('watcher_notification', 'int') : 1;
    $object->assigned_usergroup_id = GETPOST('assigned_usergroup', 'int');
    $object->assigned_user_id = GETPOST('assigned_user', 'int');
    $object->notify_assigned_by_email = isset($_POST['assigned_notification']) ? GETPOST('assigned_notification', 'int') : 1;
    $object->description = GETPOST('description');

    $object->origin = GETPOST('origin', 'alpha');
    $object->origin_id = GETPOST('originid', 'int');

	print '<form name="addprop" action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
	print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
	print '<input type="hidden" name="action" value="add">';

	dol_fiche_head();

	print '<table class="border" width="100%">';

    // Type
	print '<tr><td class="fieldrequired">' . $langs->trans('RequestManagerType') . '</td><td>';
    $events = array();
    $events[] = array('method' => 'getCategories', 'url' => dol_buildpath('/requestmanager/ajax/categories.php', 1), 'htmlname' => 'category', 'showempty' => '1');
    $groupslist = $usergroup_static->listGroupsForUser($user->id);
    print $formrequestmanager->select_type(array_keys($groupslist), $object->fk_type, 'type', 1, 0, $events, 0, 0, 'minwidth300');
    print '</td></tr>';

    // Category
	print '<tr><td>' . $langs->trans('RequestManagerCategory') . '</td><td>';
    print $formrequestmanager->select_category($object->fk_type, $object->fk_category, 'category', 1, 0, array(), 0, 0, 'minwidth300');
    print '</td></tr>';

    // Label
	print '<tr><td class="titlefield fieldrequired">' . $langs->trans('RequestManagerLabel') . '</td><td>';
	print '<input class="quatrevingtpercent" type="text" name="label" value="'.dol_escape_htmltag($object->label).'">';
    print '</td></tr>';

    // ThirdParty
    print '<tr><td class="fieldrequired">' . $langs->trans('RequestManagerThirdParty') . '</td><td>';
    print $form->select_company($object->socid, 'socid', '(s.client = 1 OR s.client = 2 OR s.client = 3) AND status=1', 'SelectThirdParty', 0, 0, null, 0, 'minwidth300');
    if (!empty($conf->societe->enabled) &&  $user->rights->societe->creer) {
        print ' <a id="new_thridparty" href="' . DOL_URL_ROOT . '/societe/card.php?action=create&client=3&fournisseur=0&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create' . ($object->fk_type ? '&type=' . $object->fk_type : '')) . '">' . $langs->trans("AddThirdParty") . '</a>';
    }
    print '</td></tr>';

    // Source
	print '<tr><td>' . $langs->trans('RequestManagerSource') . '</td><td>';
    print $formrequestmanager->select_source($object->fk_source, 'source', 1, 0, array(), 0, 0, 'minwidth300');
    print '</td></tr>';

    // Urgency
	print '<tr><td>' . $langs->trans('RequestManagerUrgency') . '</td><td>';
    print $formrequestmanager->select_urgency($object->fk_urgency, 'urgency', 1, 0, array(), 0, 0, 'minwidth300');
    print '</td></tr>';

    // Impact
	print '<tr><td>' . $langs->trans('RequestManagerImpact') . '</td><td>';
    print $formrequestmanager->select_impact($object->fk_impact, 'impact', 1, 0, array(), 0, 0, 'minwidth300');
    print '</td></tr>';

    // Priority
	print '<tr><td>' . $langs->trans('RequestManagerPriority') . '</td><td>';
    print $formrequestmanager->select_priority($object->fk_priority, 'priority', 1, 0, array(), 0, 0, 'minwidth300');
    print '</td></tr>';

    // Date Deadline
	print '<tr><td>' . $langs->trans('RequestManagerDeadline') . '</td><td>';
	$form->select_date($object->date_deadline, 'deadline_', 1, 1, 1, '', 1);
	print '</td></tr>';

    // Requester Contacts
	print '<tr><td>' . $langs->trans('RequestManagerRequesterContacts') . '</td><td>';
    //print $formrequestmanager->multiselect_contacts($object->requester_ids, 'requester_contacts'); // get ajax multiselect contacts, users search on thirdparty/contact or login/user
    print '</td></tr>';

    // Requester Notification
	print '<tr><td>' . $langs->trans('RequestManagerRequesterNotification') . '</td><td>';
    print '<input type="checkbox" name="requester_notification" value="1"' . ($object->notify_requester_by_email ? ' checked' : '') . ' />';
    print '</td></tr>';

    // Watcher Contacts
	print '<tr><td>' . $langs->trans('RequestManagerWatcherContacts') . '</td><td>';
    //print $formrequestmanager->multiselect_contacts($object->watcher_ids, 'watcher_contacts');
    print '</td></tr>';

    // Watcher Notification
	print '<tr><td>' . $langs->trans('RequestManagerWatcherNotification') . '</td><td>';
    print '<input type="checkbox" name="watcher_notification" value="1"' . ($object->notify_watcher_by_email ? ' checked' : '') . ' />';
    print '</td></tr>';

    // Assigned usergroup
    print "<tr><td>" . $langs->trans("RequestManagerAssignedUserGroup") . '</td><td>';
    print $form->select_dolgroups($object->assigned_usergroup_id,'assigned_usergroup',1);
    print '</td></tr>';

    // Assigned user
    print "<tr><td>" . $langs->trans("RequestManagerAssignedUser") . '</td><td>';
    print $form->select_dolusers($object->assigned_user_id,'assigned_user',1, null, 0, '', '', 0, 0, 0, '', 0, '', '', 1, 0);
    print '</td></tr>';

    // Assigned Notification
	print '<tr><td>' . $langs->trans('RequestManagerAssignedNotification') . '</td><td>';
    print '<input type="checkbox" name="assigned_notification" value="1"' . ($object->notify_assigned_by_email ? ' checked' : '') . ' />';
    print '</td></tr>';

    // Other attributes
	$parameters = array();
	$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
       print $hookmanager->resPrint;
	if (empty($reshook) && ! empty($extrafields->attribute_label)) {
		print $object->showOptionals($extrafields, 'edit');
	}

    // Origin
	if (! empty($object->origin) && ! empty($object->origin_id)) {
        print '<tr><td>' . $langs->trans('Origin') . '</td><td>';
        print '<input type="hidden" name="origin" value="' . dol_escape_htmltag($object->origin) . '">';
        print '<input type="hidden" name="originid" value="' . $object->origin_id . '">';
        print dolGetElementUrl($object->origin_id, $object->origin, 1);
        print '</td></tr>';
    }

    // Description
    print '<tr><td class="tdtop fieldrequired">' . $langs->trans('RequestManagerDescription') . '</td><td valign="top">';
    $doleditor = new DolEditor('description', $object->description, '', 200, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, '90%');
    print $doleditor->Create(1);
    print '</td></tr>';

	print "</table>\n";

	dol_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button" value="' . $langs->trans("RequestManagerAddRequest") . '">';
    print ' &nbsp; &nbsp; ';
	print '<input type="button" class="button" value="' . $langs->trans("Cancel") . '" onClick="javascript:history.go(-1)">';
	print '</div>';

	print "</form>";
} elseif ($object->id > 0) {
	/*
	 * Show object in view mode
	 */

    $object->fetch_thirdparty();
	$head = requestmanager_prepare_head($object);
	dol_fiche_head($head, 'card', $langs->trans('RequestManagerCard'), 0, 'requestmanager@requestmanager');

	$formconfirm = '';

    // Confirm resolve
	if ($action == 'resolve') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('RequestManagerResolveRequest'), $langs->trans('RequestManagerConfirmResolveRequest', $object->ref), 'confirm_resolve', '', 0, 1);
	}

    // Confirm close
	if ($action == 'close') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('RequestManagerCloseRequest'), $langs->trans('RequestManagerConfirmCloseRequest', $object->ref), 'confirm_close', '', 0, 1);
	}

    // Confirm not resolved
	if ($action == 'notresolved') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('RequestManagerNotResolvedRequest'), $langs->trans('RequestManagerConfirmNotResolvedRequest', $object->ref), 'confirm_notresolved', '', 0, 1);
	}

    // Confirm re open
	if ($action == 'reopen') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('RequestManagerReOpenRequest'), $langs->trans('RequestManagerConfirmReOpenRequest', $object->ref), 'confirm_reopen', '', 0, 1);
	}

    // Confirm delete
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('RequestManagerDeleteRequest'), $langs->trans('RequestManagerConfirmDeleteRequest', $object->ref), 'confirm_delete', '', 0, 1);
	}

	// Hook
    $parameters = array();
    $reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
    if (empty($reshook)) $formconfirm.=$hookmanager->resPrint;
    elseif ($reshook > 0) $formconfirm=$hookmanager->resPrint;

	// Print form confirm
	print $formconfirm;

	// Request card
	$linkback = '<a href="' . dol_buildpath('/requestmanager/list.php', 1) . '?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';

	$morehtmlref='<div class="refidno">';
	// External Reference
    $morehtmlref.='<br>'.$langs->trans('RequestManagerExternalReference') . ' : ' . $object->ref_ext;
    $morehtmlref.='</div>';

	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

    print '<div class="fichecenter">';
    print '<div class="fichehalfleft">';
    print '<div class="underbanner clearboth"></div>';

	print '<table class="border" width="100%">';

    // Type
    print '<tr><td class="titlefield">';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('RequestManagerType');
	print '</td>';
	if ($action != 'edit_type' && $user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_INITIAL)
		print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=edit_type&id=' . $object->id . '">' . img_edit($langs->trans('RequestManagerSetType'), 1) . '</a></td>';
	print '</tr></table>';
	print '</td><td>';
	if ($action == 'edit_type' && $user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_INITIAL) {
		print '<form name="editecheance" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
		print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
		print '<input type="hidden" name="action" value="set_type">';
        $groupslist = $usergroup_static->listGroupsForUser($user->id);
        print $formrequestmanager->select_type(array_keys($groupslist), $object->fk_type, 'type', 1, 0, array(), 0, 0, 'minwidth300');
		print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
		print '</form>';
	} else {
        print $object->getLibType();
	}
    print '</td></tr>';

    // Category
    print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('RequestManagerCategory');
	print '</td>';
	if ($action != 'edit_category' && $user->rights->requestmanager->creer &&
        ($object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS || $object->statut_type == RequestManager::STATUS_TYPE_INITIAL))
		print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=edit_category&id=' . $object->id . '">' . img_edit($langs->trans('RequestManagerSetCategory'), 1) . '</a></td>';
	print '</tr></table>';
	print '</td><td>';
	if ($action == 'edit_category' && $user->rights->requestmanager->creer &&
	        ($object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS || $object->statut_type == RequestManager::STATUS_TYPE_INITIAL)) {
        print '<form name="editecheance" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
        print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
        print '<input type="hidden" name="action" value="set_category">';
        print $formrequestmanager->select_category($object->fk_type, $object->fk_category, 'category', 1, 0, array(), 0, 0, 'minwidth300');
        print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
        print '</form>';
    } else {
        print $object->getLibCategory();
	}
    print '</td></tr>';

    // Label
    print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('RequestManagerLabel');
	print '</td>';
	if ($action != 'edit_label' && $user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS)
		print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=edit_label&id=' . $object->id . '">' . img_edit($langs->trans('RequestManagerSetLabel'), 1) . '</a></td>';
	print '</tr></table>';
	print '</td><td>';
	if ($action == 'edit_label' && $user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS) {
		print '<form name="editecheance" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
		print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
		print '<input type="hidden" name="action" value="set_label">';
        print '<input type="text" name="label" value="'.dol_escape_htmltag($object->label).'">';
		print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
		print '</form>';
	} else {
        print $object->label;
	}
    print '</td></tr>';

    // ThirdParty
    print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('RequestManagerThirdParty');
	print '</td>';
	if ($action != 'edit_thirdparty' && $user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS)
		print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=edit_thirdparty&id=' . $object->id . '">' . img_edit($langs->trans('RequestManagerSetThirdParty'), 1) . '</a></td>';
	print '</tr></table>';
	print '</td><td>';
	if ($action == 'edit_thirdparty' && $user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS) {
		print '<form name="editecheance" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
		print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
		print '<input type="hidden" name="action" value="set_thirdparty">';
        print $form->select_company($object->socid, 'socid', '(s.client = 1 OR s.client = 2 OR s.client = 3) AND status=1', 'SelectThirdParty', 0, 0, null, 0, 'minwidth300');
		print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
		print '</form>';
	} else {
        print $object->thirdparty->getNomUrl(1);
	}
    print '</td></tr>';

    // Source
    print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('RequestManagerSource');
	print '</td>';
	if ($action != 'edit_source' && $user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS)
		print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=edit_source&id=' . $object->id . '">' . img_edit($langs->trans('RequestManagerSetSource'), 1) . '</a></td>';
	print '</tr></table>';
	print '</td><td>';
	if ($action == 'edit_source' && $user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS) {
		print '<form name="editecheance" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
		print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
		print '<input type="hidden" name="action" value="set_source">';
        print $formrequestmanager->select_source($object->fk_source, 'source', 1, 0, array(), 0, 0, 'minwidth300');
		print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
		print '</form>';
	} else {
        print $object->getLibSource();
	}
    print '</td></tr>';

    // Urgency
    print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('RequestManagerUrgency');
	print '</td>';
	if ($action != 'edit_urgency' && $user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS)
		print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=edit_urgency&id=' . $object->id . '">' . img_edit($langs->trans('RequestManagerSetUrgency'), 1) . '</a></td>';
	print '</tr></table>';
	print '</td><td>';
	if ($action == 'edit_urgency' && $user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS) {
		print '<form name="editecheance" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
		print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
		print '<input type="hidden" name="action" value="set_urgency">';
        print $formrequestmanager->select_urgency($object->fk_urgency, 'urgency', 1, 0, array(), 0, 0, 'minwidth300');
		print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
		print '</form>';
	} else {
        print $object->getLibUrgency();
	}
    print '</td></tr>';

    // Impact
    print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('RequestManagerImpact');
	print '</td>';
	if ($action != 'edit_impact' && $user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS)
		print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=edit_impact&id=' . $object->id . '">' . img_edit($langs->trans('RequestManagerSetImpact'), 1) . '</a></td>';
	print '</tr></table>';
	print '</td><td>';
	if ($action == 'edit_impact' && $user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS) {
		print '<form name="editecheance" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
		print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
		print '<input type="hidden" name="action" value="set_impact">';
        print $formrequestmanager->select_impact($object->fk_impact, 'impact', 1, 0, array(), 0, 0, 'minwidth300');
		print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
		print '</form>';
	} else {
        print $object->getLibImpact();
	}
    print '</td></tr>';

    // Priority
    print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('RequestManagerPriority');
	print '</td>';
	if ($action != 'edit_priority' && $user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS)
		print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=edit_priority&id=' . $object->id . '">' . img_edit($langs->trans('RequestManagerSetPriority'), 1) . '</a></td>';
	print '</tr></table>';
	print '</td><td>';
	if ($action == 'edit_priority' && $user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS) {
		print '<form name="editecheance" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
		print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
		print '<input type="hidden" name="action" value="set_priority">';
        print $formrequestmanager->select_priority($object->fk_priority, 'priority', 1, 0, array(), 0, 0, 'minwidth300');
		print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
		print '</form>';
	} else {
        print $object->getLibPriority();
	}
    print '</td></tr>';

    // Date creation
    print '<tr><td>'.$langs->trans('DateCreation').'</td><td>';
    print dol_print_date($object->date_creation, 'dayhour');
    print '</td></tr>';

    // Duration
    print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('RequestManagerDuration');
	print '</td>';
	if ($action != 'edit_duration' && $user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS)
		print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=edit_duration&id=' . $object->id . '">' . img_edit($langs->trans('RequestManagerSetDuration'), 1) . '</a></td>';
	print '</tr></table>';
	print '</td><td>';
	if ($action == 'edit_duration' && $user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS) {
		print '<form name="editecheance" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
		print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
		print '<input type="hidden" name="action" value="set_duration">';
        $duration_infos = requestmanager_get_duration($object->duration);
        print '<input type="text" size="3" name="duration_day" value="'.$duration_infos['days'].'"> ' . $langs->trans('Days');
        print ' <input type="text" size="3" name="duration_hour" value="'.$duration_infos['hours'].'"> ' . $langs->trans('Hours');
        print ' <input type="text" size="3" name="duration_minute" value="'.$duration_infos['minutes'].'"> ' . $langs->trans('Minutes');
		print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
		print '</form>';
	} else {
        print requestmanager_print_duration($object->duration);
    }
    print '</td></tr>';

    // Date Deadline
    print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('RequestManagerDeadline');
	print '</td>';
	if ($action != 'edit_date_deadline' && $user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS)
		print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=edit_date_deadline&id=' . $object->id . '">' . img_edit($langs->trans('RequestManagerSetDeadline'), 1) . '</a></td>';
	print '</tr></table>';
	print '</td><td>';
	if ($action == 'edit_date_deadline' && $user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS) {
		print '<form name="editecheance" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
		print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
		print '<input type="hidden" name="action" value="set_date_deadline">';
        $form->select_date($object->date_deadline, 'deadline_', 1, 1, 1, '', 1);
		print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
		print '</form>';
	} else {
        print $object->date_deadline > 0 ? dol_print_date($object->date_deadline, 'dayhour') : '';
	}
    print '</td></tr>';

    // Assigned usergroup
	print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('RequestManagerAssignedUserGroup');
	print '</td>';
	if ($action != 'edit_assigned_usergroup' && $user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS)
		print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=edit_assigned_usergroup&id=' . $object->id . '">' . img_edit($langs->trans('RequestManagerSetAssignedUserGroup'), 1) . '</a></td>';
	print '</tr></table>';
	print '</td><td>';
	if ($action == 'edit_assigned_usergroup' && $user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS) {
		print '<form name="editecheance" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
		print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
		print '<input type="hidden" name="action" value="set_assigned_usergroup">';
        print $form->select_dolgroups($object->assigned_usergroup_id, 'assigned_usergroup', 1);
		print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
		print '</form>';
	} else {
        if ($usergroup_static->fetch($object->assigned_usergroup_id) > 0) {
            print $usergroup_static->getFullName($langs);
        }
	}
    print '</td></tr>';

    // Assigned user
    print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('RequestManagerAssignedUser');
	print '</td>';
	if ($action != 'edit_assigned_user' && $user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS)
		print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=edit_assigned_user&id=' . $object->id . '">' . img_edit($langs->trans('RequestManagerSetAssignedUser'), 1) . '</a></td>';
	print '</tr></table>';
	print '</td><td>';
	if ($action == 'edit_assigned_user' && $user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS) {
		print '<form name="editecheance" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
		print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
		print '<input type="hidden" name="action" value="set_assigned_user">';
        print $form->select_dolusers($object->assigned_user_id,'assigned_user',1, null, 0, '', '', 0, 0, 0, '', 0, '', '', 1, 0);
		print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
		print '</form>';
	} else {
        if ($user_static->fetch($object->assigned_user_id) > 0) {
            print $user_static->getNomUrl(1);
        }
	}
    print '</td></tr>';

    // Assigned Notification
    print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('RequestManagerAssignedNotification');
	print '</td>';
	if ($action != 'edit_assigned_notification' && $user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS)
		print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=edit_assigned_notification&id=' . $object->id . '">' . img_edit($langs->trans('RequestManagerSetAssignedNotification'), 1) . '</a></td>';
	print '</tr></table>';
	print '</td><td>';
	if ($action == 'edit_assigned_notification' && $user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS) {
		print '<form name="editecheance" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
		print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
		print '<input type="hidden" name="action" value="set_assigned_notification">';
        print '<input type="checkbox" name="assigned_notification" value="1"' . ($object->notify_assigned_by_email ? ' checked' : '') . ' />';
		print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
		print '</form>';
	} else {
        print yn($object->notify_assigned_by_email);
	}
    print '</td></tr>';

    // Other attributes
    $object->save_status = $object->statut;
    if ($object->statut_type != RequestManager::STATUS_TYPE_IN_PROGRESS) $object->statut = 0;
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';
    $object->statut = $object->save_status;

    // Description
    if ($action == 'edit_description' && $user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS) {
        print '<form name="editecheance" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
        print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
        print '<input type="hidden" name="action" value="set_description">';
    }
    print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('RequestManagerDescription');
	print '</td>';
	if ($action != 'edit_description' && $user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS)
		print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=edit_description&id=' . $object->id . '">' . img_edit($langs->trans('RequestManagerSetDescription'), 1) . '</a></td>';
	print '</tr></table>';
    print '</td><td>';
    if ($action == 'edit_description' && $user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS) {
		print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
	}
    print '</td></tr>';
    print '<tr><td colspan="2">';
	if ($action == 'edit_description' && $user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS) {
        $doleditor = new DolEditor('description', $object->description, '', 200, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, '90%');
        print $doleditor->Create(1);
	} else {
        print $object->description;
	}
    print '</td></tr>';
    if ($action == 'edit_description' && $user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS) {
        print '</form>';
    }

	print '</table>';

    print '</div>';
    print '<div class="fichehalfright"><div class="ficheaddleft">';
    print '<div class="underbanner clearboth"></div>';

    print '<table class="border centpercent">';

    // Requesters
    print '<tr><td align="center">';
	print $langs->trans('RequestManagerRequesterContacts');
    $notificationUrl = "#";
	if ($user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS) {
        if ($object->notify_requester_by_email) {
            $notificationChangeTo = '0';
        } else {
            $notificationChangeTo = '1';
        }
        $notificationUrl = $_SERVER['PHP_SELF'] . '?id=' . $object->id. '&action=set_requesters&requester_notification=' . $notificationChangeTo;
    }
    print '&nbsp;';
    print '<a href="' . $notificationUrl . '">';
    print img_object($langs->trans($object->notify_requester_by_email ? "Notifications" : "RequestManagerNoNotifications"), $object->notify_requester_by_email ? 'email' : 'no_email@requestmanager');
    print '</a>';
    print '</td></tr>';
    print '<tr><td>';
    if ($user->rights->requestmanager->creer && $object->statut_type != RequestManager::STATUS_TYPE_CLOSED && $object->statut_type != RequestManager::STATUS_TYPE_RESOLVED) {
        // form to add requester contact
        $formrequestmanager->form_add_contact($object, RequestManager::CONTACT_TYPE_ID_REQUEST);
    }
    $object->show_contact_list(RequestManager::CONTACT_TYPE_ID_REQUEST);
    print '</td></tr>';

    // Watchers
    print '<tr><td align="center">';
	print $langs->trans('RequestManagerWatcherContacts');
    $notificationUrl = "#";
    if ($user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS) {
        if ($object->notify_watcher_by_email) {
            $notificationChangeTo = '0';
        } else {
            $notificationChangeTo = '1';
        }
        $notificationUrl = $_SERVER['PHP_SELF'] . '?id=' . $object->id. '&action=set_watchers&watcher_notification=' . $notificationChangeTo;
    }
    print '&nbsp;';
    print '<a href="' . $notificationUrl . '">';
    print img_object($langs->trans($object->notify_watcher_by_email ? "Notifications" : "RequestManagerNoNotifications"), $object->notify_watcher_by_email ? 'email' : 'no_email@requestmanager');
    print '</a>';
    print '</td></tr>';
    print '<tr><td>';
    if ($user->rights->requestmanager->creer && $object->statut_type != RequestManager::STATUS_TYPE_CLOSED && $object->statut_type != RequestManager::STATUS_TYPE_RESOLVED) {
        // form to add requester contact
        $formrequestmanager->form_add_contact($object, RequestManager::CONTACT_TYPE_ID_WATCHER);
    }
    $object->show_contact_list(RequestManager::CONTACT_TYPE_ID_WATCHER);
    print '</td></tr>';

    // Linked Objects
    print '<tr><td align="center">';
    print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'#linked_objects_list">' . $langs->trans('RelatedObjects') . '</a>';
    // Do not add 2 times
    //$linktoelem = $form->showLinkToObjectBlock($object, null, array('requestmanager'));
    //print ' ( ' . $linktoelem . ' )';
    print '</td></tr>';
    $object->fetchObjectLinked();
    $element_infos = requestmanager_get_elements_infos();
    $linked_objects_list = array();
    foreach($object->linkedObjects as $objecttype => $objects) {
        // Do not show if module disabled
        if ($objecttype == 'facture' && empty($conf->facture->enabled)) continue;
        elseif ($objecttype == 'facturerec' && empty($conf->facture->enabled)) continue;
        elseif ($objecttype == 'propal' && empty($conf->propal->enabled)) continue;
        elseif ($objecttype == 'supplier_proposal' && empty($conf->supplier_proposal->enabled)) continue;
        elseif (($objecttype == 'shipping' || $objecttype == 'shipment') && empty($conf->expedition->enabled)) continue;
        elseif ($objecttype == 'delivery' && empty($conf->expedition->enabled)) continue;

        if (isset($element_infos[$objecttype]['langs'])) $langs->loadLangs($element_infos[$objecttype]['langs']);
        $label = $langs->trans(isset($element_infos[$objecttype]['label']) ? $element_infos[$objecttype]['label'] : 'Unknown');
        $icon = isset($element_infos[$objecttype]['picto']) ? $element_infos[$objecttype]['picto'] : '';

        $linked_objects_list[] = img_picto($label, $icon) . ' ' . $label . ' (' . count($objects) .')';
    }
    print '<tr><td>' . implode(', ', $linked_objects_list) . '</td></tr>';

	print '</table>';

    print '</div>';
    print '</div></div>';
    print '<div class="clearboth"></div>';

	// Specifics Information
    $parameters = array();
    $reshook = $hookmanager->executeHooks('addMoreSpecificsInformation', $parameters, $object, $action); // Note that $action and $object may have been
    if ($reshook) {
        print '<div class="fichecenter">';
        print '<div class="underbanner clearboth"></div>';

        print $hookmanager->resPrint;

        print '</div>';
        print '<div class="clearboth"></div>';
    }

    /*
	 * Boutons status
	 */
    if ($user->rights->requestmanager->creer) {
        print '<div class="tabsAction noMarginBottom">';
        dol_include_once('/advancedictionaries/class/dictionary.class.php');
        $requestManagerStatusDictionary = Dictionary::getDictionary($db, 'requestmanager', 'requestmanagerstatus');

        if ($object->statut_type == RequestManager::STATUS_TYPE_INITIAL || $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS) {
            $filter_type = array(RequestManager::STATUS_TYPE_IN_PROGRESS, RequestManager::STATUS_TYPE_RESOLVED);
        } else {
            $filter_type = array(RequestManager::STATUS_TYPE_CLOSED);
        }

        // Get lines
        $requestManagerStatusDictionary->fetch_lines(1,
            array('request_type' => array($object->fk_type), 'type' => $filter_type),
            array('type' => 'ASC', 'position' => 'ASC')
        );
        foreach ($requestManagerStatusDictionary->lines as $line) {
            if ($line->id == $object->statut ||
                ($line->fields['type'] == RequestManager::STATUS_TYPE_CLOSED && !empty($conf->global->REQUESTMANAGER_AUTO_CLOSE_REQUEST))) continue;
            print '<div class="inline-block divButAction noMarginBottom">';
            $options_url = '';
            if ($line->fields['type'] == RequestManager::STATUS_TYPE_IN_PROGRESS) {
                $options_url = '&action=set_status&status=' . $line->id;
            } elseif ($line->fields['type'] == RequestManager::STATUS_TYPE_RESOLVED) {
                $options_url = '&action=resolve';
            } elseif ($line->fields['type'] == RequestManager::STATUS_TYPE_CLOSED) {
                $options_url = '&action=close';
            }
            print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . $options_url . '">';
            print $object->LibStatut($line->id, 10);
            print '</a>';
            print '</div>';
        }

        print '</div>';
    }

    dol_fiche_end();

    //Select mail models is same action as premessage
	if (GETPOST('modelselected')) $action = 'premessage';

	if ($action != 'premessage') {
        /*
	 * Boutons Actions
	 */
        print '<div class="tabsAction">';

        $parameters = array();
        $reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been
        // modified by hook
        if (empty($reshook)) {
            if ($object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS) {
                // Add message
                if ($user->rights->requestmanager->creer) {
                    print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=premessage#formmessagebeforetitle">'
                        . $langs->trans('RequestManagerAddMessage') . '</a></div>';
                }

                // Add Propale
                if (!empty($conf->propal->enabled)) {
                    $langs->load("propal");
                    if ($user->rights->propal->creer) {
                        print '<div class="inline-block divButAction"><a class="butAction" href="' . DOL_URL_ROOT . '/comm/propal/card.php?originid=' . $object->id . '&origin=' . $object->element . ($object->socid > 0 ? '&socid=' . $object->socid : '') . '&action=create">' . $langs->trans("AddProp") . '</a></div>';
                    } else {
                        print '<div class="inline-block divButAction"><a class="butActionRefused" title="' . dol_escape_js($langs->trans("NotAllowed")) . '" href="#">' . $langs->trans("AddProp") . '</a></div>';
                    }
                }

                // Add Order
                if (!empty($conf->commande->enabled)) {
                    $langs->load("orders");
                    if ($user->rights->commande->creer) {
                        print '<div class="inline-block divButAction"><a class="butAction" href="' . DOL_URL_ROOT . '/commande/card.php?originid=' . $object->id . '&origin=' . $object->element . ($object->socid > 0 ? '&socid=' . $object->socid : '') . '&action=create">' . $langs->trans("AddOrder") . '</a></div>';
                    } else {
                        print '<div class="inline-block divButAction"><a class="butActionRefused" title="' . dol_escape_js($langs->trans("NotAllowed")) . '" href="#">' . $langs->trans("AddOrder") . '</a></div>';
                    }
                }

                // Add invoice
                if ($user->societe_id == 0 && !empty($conf->facture->enabled)) {
                    $langs->load("bills");
                    $langs->load("compta");
                    if ($user->rights->facture->creer) {
                        $object->fetch_thirdparty();
                        if ($object->thirdparty->client != 0 && $object->thirdparty->client != 2) {
                            print '<div class="inline-block divButAction"><a class="butAction" href="' . DOL_URL_ROOT . '/compta/facture/card.php?originid=' . $object->id . '&origin=' . $object->element . ($object->socid > 0 ? '&socid=' . $object->socid : '') . '&action=create">' . $langs->trans("AddBill") . '</a></div>';
                        } else {
                            print '<div class="inline-block divButAction"><a class="butActionRefused" title="' . dol_escape_js($langs->trans("ThirdPartyMustBeEditAsCustomer")) . '" href="#">' . $langs->trans("AddBill") . '</a></div>';
                        }
                    } else {
                        print '<div class="inline-block divButAction"><a class="butActionRefused" title="' . dol_escape_js($langs->trans("NotAllowed")) . '" href="#">' . $langs->trans("AddBill") . '</a></div>';
                    }
                }

                // Add Intervention
                if (!empty($conf->ficheinter->enabled)) {
                    $langs->load("interventions");
                    if ($user->rights->ficheinter->creer) {
                        print '<div class="inline-block divButAction"><a class="butAction" href="' . DOL_URL_ROOT . '/fichinter/card.php?originid=' . $object->id . '&origin=' . $object->element . ($object->socid > 0 ? '&socid=' . $object->socid : '') . '&action=create">' . $langs->trans("AddIntervention") . '</a></div>';
                    } else {
                        print '<div class="inline-block divButAction"><a class="butActionRefused" title="' . dol_escape_js($langs->trans("NotAllowed")) . '" href="#">' . $langs->trans("AddIntervention") . '</a></div>';
                    }
                }

                // Add Event
                if (!empty($conf->agenda->enabled)) {
                    $langs->load("commercial");
                    if (! empty($user->rights->agenda->myactions->create) || ! empty($user->rights->agenda->allactions->create)) {
                        print '<div class="inline-block divButAction"><a class="butAction" href="' . DOL_URL_ROOT.'/comm/action/card.php?originid=' . $object->id . '&origin=' . $object->element . ($object->socid > 0 ? '&socid=' . $object->socid : '') . '&action=create">' . $langs->trans("AddAction") . '</a></div>';
                    } else {
                        print '<div class="inline-block divButAction"><a class="butActionRefused" title="' . dol_escape_js($langs->trans("NotAllowed")) . '" href="#">' . $langs->trans("AddAction") . '</a></div>';
                    }
                }
            }

            // Not Resolved
            if ($object->statut_type == RequestManager::STATUS_TYPE_RESOLVED) {
                if ($user->rights->requestmanager->creer) {
                    print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=notresolved">'
                        . $langs->trans('ReOpen') . '</a></div>';
                } else {
                    print '<div class="inline-block divButAction"><a class="butActionRefused" title="' . dol_escape_js($langs->trans("NotAllowed")) . '" href="#">'
                        . $langs->trans("ReOpen") . '</a></div>';
                }
            }

            // ReOpen
            if ($object->statut_type == RequestManager::STATUS_TYPE_CLOSED) {
                if ($user->rights->requestmanager->cloturer) {
                    print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=reopen">'
                        . $langs->trans('ReOpen') . '</a></div>';
                } else {
                    print '<div class="inline-block divButAction"><a class="butActionRefused" title="' . dol_escape_js($langs->trans("NotAllowed")) . '" href="#">'
                        . $langs->trans("ReOpen") . '</a></div>';
                }
            }

            // Delete
            if ($object->statut_type == RequestManager::STATUS_TYPE_INITIAL) {
                if ($user->rights->requestmanager->supprimer) {
                    print '<div class="inline-block divButAction"><a class="butActionDelete" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=delete">'
                        . $langs->trans('Delete') . '</a></div>';
                } else {
                    print '<div class="inline-block divButAction"><a class="butActionRefused" title="' . dol_escape_js($langs->trans("NotAllowed")) . '" href="#">'
                        . $langs->trans("Delete") . '</a></div>';
                }
            }
        }

        print '</div>';

        /*
	 * Events
	 */
        requestmanager_show_events($object);

        /*
	 * Linked Objects
	 */

        print '<div class="fichecenter"><div class="fichehalfleft">';

        // Show links to link elements
        print '<div id="linked_objects_list">';
        $linktoelem = $form->showLinkToObjectBlock($object, null, array('requestmanager'));
        $somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);
        print '</div>';

        print '</div><div class="fichehalfright"><div class="ficheaddleft">';

        print '</div></div></div>';
    } else {
        /*
         * Affiche formulaire message
         */

        print '<div id="formmessagebeforetitle" name="formmessagebeforetitle"></div>';
        print '<div class="clearboth"></div>';
        print '<br>';
        print load_fiche_titre($langs->trans('RequestManagerAddMessage'));

        dol_fiche_head();

        // Cree l'objet formulaire message
        $formrequestmanagermessage = new FormRequestManagerMessage($db, $object);

        // Tableau des parametres complementaires du post
        $formmail->param['action'] = $action;
        $formmail->param['models'] = $modelmail;
        $formmail->param['models_id'] = GETPOST('modelmailselected', 'int');
        $formmail->param['returnurl'] = $_SERVER["PHP_SELF"] . '?id=' . $object->id;

        // Init list of files
        if (GETPOST("messagemode") == 'init') {
            $formrequestmanagermessage->clear_attached_files();
            $formrequestmanagermessage->add_attached_files($file, basename($file), dol_mimetype($file));
        }

        $actioncomm = GETPOST('actioncomm')?GETPOST('actioncomm'):'';
        $actionurl = (GETPOST('actionurl')?GETPOST('actionurl'):$_SERVER["PHP_SELF"] . '?id=' . $object->id);
        $templateType = GETPOST('type_template')?GETPOST('type_template'):'message_template_user';
        $templateId = GETPOST('message_template_selected')?GETPOST('message_template_selected'):0;

        // Show form
        print $formrequestmanagermessage->get_message_form($actioncomm, $actionurl, $templateType, $templateId, $formmail->param);

        dol_fiche_end();
    }
}

// End of page
llxFooter();
$db->close();
