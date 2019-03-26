<?php
/* Copyright (C) 2018      Open-DSI              <support@open-dsi.fr>
 * Copyright (C) 2019      Alexis LAURIER        <alexis@alexislaurier.fr>
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
 * \file 		htdocs/requestmanager/card.php
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

if (!empty($conf->categorie->enabled)) {
    require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
    dol_include_once('/requestmanager/class/categorierequestmanager.class.php');
}

dol_include_once('/advancedictionaries/class/dictionary.class.php');
dol_include_once('/requestmanager/class/requestmanager.class.php');
dol_include_once('/requestmanager/class/requestmanagermessage.class.php');
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
// lines
$lineid = GETPOST('lineid', 'int');
//links
$addlink = GETPOST('addlink', 'alpha');
$addlinkid = GETPOST('idtolinkto', 'int');
$dellinkid = GETPOST('dellinkid', 'int');

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
        $object->fetch_thirdparty_origin();
        $object->fetch_thirdparty();
        $object->fetch_thirdparty_benefactor();
        $object->fetch_thirdparty_watcher();
        $object->fetch_optionals();
        //$mysoc = $object->socid;
    } elseif ($ret < 0) {
        dol_print_error('', $object->errorsToString());
    } else {
        print $langs->trans('NoRecordFound');
        exit();
    }
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('requestmanagercard','globalcard'));

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context For event list
$list_mode = GETPOST('list_mode', 'int');
if ($list_mode === "") $list_mode = $_SESSION['rm_list_mode'];
if ($list_mode === "" || !isset($list_mode)) $list_mode = !empty($conf->global->REQUESTMANAGER_DEFAULT_LIST_MODE) ? $conf->global->REQUESTMANAGER_DEFAULT_LIST_MODE : 0;
$contextpage = $list_mode == 0 ? 'requestmanagereventlist' : ($list_mode == 1 ? 'requestmanagertimelineeventlist' : 'requestmanagertimeline');


$permissiondellink = $user->rights->requestmanager->creer; 	// Used by the include of actions_dellink.inc.php

include dol_buildpath('/requestmanager/core/init_events.inc.php');

/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
    if (!empty($cancel)) $action = '';

    include dol_buildpath('/requestmanager/core/actions_events.inc.php');

    // Confirm forcing principal company
    $force_principal_company = false;
    $force_principal_company_params = array();
    if ($action == 'confirm_force_principal_company' && $confirm == "yes" && $user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS) {
        $action = GETPOST('save_action', 'alpha');
        $force_principal_company = true;
    }

    // Create request child
    if ($action == 'create_request_child' && $user->rights->requestmanager->creer) {
        $new_request_type = GETPOST('new_request_type', 'int');
        $id = $object->createSubRequest($new_request_type, $user);
        if ($id > 0) {
            header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $id);
            exit();
        } else {
            $langs->load("errors");
            setEventMessages($object->error, $object->errors, 'errors');
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
    //elseif ($action == 'set_label' && $user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS) {
	  elseif ($action == 'set_label' && $user->rights->requestmanager->creer) {
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
    } // Set ThirdParty Origin
    elseif ($action == 'set_thirdparty_origin' && $user->rights->requestmanager->creer && ($object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS || $object->statut_type == RequestManager::STATUS_TYPE_INITIAL)) {
        $object->oldcopy = clone $object;
        $object->socid_origin = GETPOST('socid_origin', 'int');
        if (empty($conf->companyrelationships->enabled)) {
            $object->socid = $object->socid_origin;
            $object->socid_benefactor = $object->socid_origin;
        }
        $result = $object->update($user);
        if ($result < 0) {
            setEventMessages($object->error, $object->errors, 'errors');
            $action = 'edit_thirdparty_origin';
        } else {
            header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
            exit();
        }
    } // Set ThirdParty Bill
    elseif ($action == 'set_thirdparty' && !empty($conf->companyrelationships->enabled) && $user->rights->requestmanager->creer && ($object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS || $object->statut_type == RequestManager::STATUS_TYPE_INITIAL)) {
        $object->oldcopy = clone $object;
        $object->socid = GETPOST('socid', 'int');

        dol_include_once('/companyrelationships/class/companyrelationships.class.php');
        $companyrelationships = new CompanyRelationships($db);
        $principal_companies_ids = $companyrelationships->getRelationships($object->socid_benefactor, 0);
        $not_principal_company = !in_array($object->socid, $principal_companies_ids) && $object->socid != $object->socid_benefactor && ($object->oldcopy->socid != $object->socid || $object->oldcopy->socid_benefactor != $object->socid_benefactor);
        if ($not_principal_company && !$force_principal_company) {
            $force_principal_company_params = array(
                'save_action' => $action,
                'socid' => $object->socid,
            );
            $action = 'force_principal_company';
            $object->socid = $object->oldcopy->socid;
        } else {
            $result = $object->update($user);

            if ($result > 0 && $not_principal_company && $force_principal_company) {
                // Principal company forced for the benefactor
                $result = $object->addActionForcedPrincipalCompany($user);
            }

            if ($result < 0) {
                setEventMessages($object->error, $object->errors, 'errors');
                $action = 'edit_thirdparty';
            } else {
                header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
                exit();
            }
        }
    } // Set ThirdParty Benefactor
    elseif ($action == 'set_thirdparty_benefactor' && !empty($conf->companyrelationships->enabled) && $user->rights->requestmanager->creer && ($object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS || $object->statut_type == RequestManager::STATUS_TYPE_INITIAL)) {
        $object->oldcopy = clone $object;
        $object->socid_benefactor = GETPOST('socid_benefactor', 'int');

        dol_include_once('/companyrelationships/class/companyrelationships.class.php');
        $companyrelationships = new CompanyRelationships($db);
        $principal_companies_ids = $companyrelationships->getRelationships($object->socid_benefactor, 0);
        $not_principal_company = !in_array($object->socid, $principal_companies_ids) && $object->socid != $object->socid_benefactor && ($object->oldcopy->socid != $object->socid || $object->oldcopy->socid_benefactor != $object->socid_benefactor);
        if ($not_principal_company && !$force_principal_company) {
            $force_principal_company_params = array(
                'save_action' => $action,
                'socid_benefactor' => $object->socid_benefactor,
            );
            $action = 'force_principal_company';
            $object->socid_benefactor = $object->oldcopy->socid_benefactor;
        } else {
            $result = $object->update($user);

            if ($result > 0 && $not_principal_company && $force_principal_company) {
                // Principal company forced for the benefactor
                $result = $object->addActionForcedPrincipalCompany($user);
            }

            if ($result < 0) {
                setEventMessages($object->error, $object->errors, 'errors');
                $action = 'edit_thirdparty_benefactor';
            } else {
                header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
                exit();
            }
        }
    } // Set ThirdParty Watcher
    elseif ($action == 'set_thirdparty_watcher' && !empty($conf->companyrelationships->enabled) && $user->rights->requestmanager->creer && ($object->statut_type != RequestManager::STATUS_TYPE_CLOSED)) {
        $object->oldcopy = clone $object;
        $object->socid_watcher = GETPOST('socid_watcher', 'int');
        $result = $object->update($user);
        if ($result < 0) {
            setEventMessages($object->error, $object->errors, 'errors');
            $action = 'edit_thirdparty_watcher';
        } else {
            header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
            exit();
        }
    } // Set Availability of the request for the thirdparty principal
    elseif ($action == 'set_availability_for_thirdparty_principal' && !empty($conf->companyrelationships->enabled) && $user->rights->requestmanager->creer && $object->statut_type != RequestManager::STATUS_TYPE_CLOSED) {
        $object->oldcopy = clone $object;
        $object->availability_for_thirdparty_principal = GETPOST('availability_for_thirdparty_principal', 'int');
        $result = $object->update($user);
        if ($result < 0) {
            setEventMessages($object->error, $object->errors, 'errors');
            $action = 'edit_availability_for_thirdparty_principal';
        } else {
            header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
            exit();
        }
    } // Set Availability of the request for the thirdparty benefactor
    elseif ($action == 'set_availability_for_thirdparty_benefactor' && !empty($conf->companyrelationships->enabled) && $user->rights->requestmanager->creer && $object->statut_type != RequestManager::STATUS_TYPE_CLOSED) {
        $object->oldcopy = clone $object;
        $object->availability_for_thirdparty_benefactor = GETPOST('availability_for_thirdparty_benefactor', 'int');
        $result = $object->update($user);
        if ($result < 0) {
            setEventMessages($object->error, $object->errors, 'errors');
            $action = 'edit_availability_for_thirdparty_benefactor';
        } else {
            header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
            exit();
        }
    } // Set Availability of the request for the thirdparty watcher
    elseif ($action == 'set_availability_for_thirdparty_watcher' && !empty($conf->companyrelationships->enabled) && $user->rights->requestmanager->creer && $object->statut_type != RequestManager::STATUS_TYPE_CLOSED) {
        $object->oldcopy = clone $object;
        $object->availability_for_thirdparty_watcher = GETPOST('availability_for_thirdparty_watcher', 'int');
        $result = $object->update($user);
        if ($result < 0) {
            setEventMessages($object->error, $object->errors, 'errors');
            $action = 'edit_availability_for_thirdparty_watcher';
        } else {
            header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
            exit();
        }
    } // Set Source
    elseif ($action == 'set_source' && $user->rights->requestmanager->creer && ($object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS || $object->statut_type == RequestManager::STATUS_TYPE_INITIAL)) {
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
    elseif ($action == 'set_urgency' && $user->rights->requestmanager->creer && ($object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS || $object->statut_type == RequestManager::STATUS_TYPE_INITIAL)) {
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
    elseif ($action == 'set_impact' && $user->rights->requestmanager->creer && ($object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS || $object->statut_type == RequestManager::STATUS_TYPE_INITIAL)) {
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
    elseif ($action == 'set_priority' && $user->rights->requestmanager->creer && ($object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS || $object->statut_type == RequestManager::STATUS_TYPE_INITIAL)) {
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
    elseif ($action == 'set_duration' && $user->rights->requestmanager->creer && $object->statut_type != RequestManager::STATUS_TYPE_CLOSED) {
        $object->oldcopy = clone $object;
        $object->duration = GETPOST('duration_day', 'int') * 86400 + GETPOST('duration_hour', 'int') * 3600 + GETPOST('duration_min', 'int') * 60;
        $result = $object->update($user);
        if ($result < 0) {
            setEventMessages($object->error, $object->errors, 'errors');
            $action = 'edit_duration';
        } else {
            header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
            exit();
        }
    } // Set Date Creation
    elseif ($action == 'set_date_creation' && $user->rights->requestmanager->creer && $object->statut_type != RequestManager::STATUS_TYPE_CLOSED) {
        $object->oldcopy = clone $object;
        $object->date_creation = dol_mktime(GETPOST('date_creation_hour', 'int'), GETPOST('date_creation_min', 'int'), 0, GETPOST('date_creation_month', 'int'), GETPOST('date_creation_day', 'int'), GETPOST('date_creation_year', 'int'));
        $result = $object->update($user);
        if ($result < 0) {
            setEventMessages($object->error, $object->errors, 'errors');
            $action = 'edit_date_creation';
        } else {
            header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
            exit();
        }
    } // Set Date Operation
    elseif ($action == 'set_date_operation' && $user->rights->requestmanager->creer && $object->statut_type != RequestManager::STATUS_TYPE_CLOSED) {
        $object->oldcopy = clone $object;
        $object->date_operation = dol_mktime(GETPOST('operation_hour', 'int'), GETPOST('operation_min', 'int'), 0, GETPOST('operation_month', 'int'), GETPOST('operation_day', 'int'), GETPOST('operation_year', 'int'));

        // calculate deadline date with operation date or now and the offset deadline time in minutes
        if (GETPOST('recalculate_date_deadline', 'int') == 1) {
            dol_include_once('/advancedictionaries/class/dictionary.class.php');
            $requestManagerStatusDictionaryLine = Dictionary::getDictionaryLine($db, 'requestmanager', 'requestmanagerstatus');
            $res = $requestManagerStatusDictionaryLine->fetch($object->statut);
            if ($res > 0) {
                $deadline_offset = $requestManagerStatusDictionaryLine->fields['deadline'];
                $now = dol_now();
                if (isset($deadline_offset) && $deadline_offset > 0) {
                    $object->date_deadline = ($object->date_operation > 0 ? $object->date_operation : $now) + ($deadline_offset * 60);
//                } elseif (intval($conf->global->REQUESTMANAGER_DEADLINE_TIME_DEFAULT) > 0) {
//                    $object->date_deadline = ($object->date_operation > 0 ? $object->date_operation : $now) + (intval($conf->global->REQUESTMANAGER_DEADLINE_TIME_DEFAULT) * 60);
                }
            } else {
                setEventMessages($requestManagerStatusDictionaryLine->error, $requestManagerStatusDictionaryLine->errors, 'errors');
                $action = 'edit_date_operation';
                $error++;
            }
        }

        if (!$error) {
            $result = $object->update($user);
            if ($result < 0) {
                setEventMessages($object->error, $object->errors, 'errors');
                $action = 'edit_date_operation';
            } else {
                header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
                exit();
            }
        }
    } // Set Date Deadline
    elseif ($action == 'set_date_deadline' && $user->rights->requestmanager->creer && $object->statut_type != RequestManager::STATUS_TYPE_CLOSED) {
        $object->oldcopy = clone $object;
        $object->date_deadline = dol_mktime(GETPOST('deadline_hour', 'int'), GETPOST('deadline_min', 'int'), 0, GETPOST('deadline_month', 'int'), GETPOST('deadline_day', 'int'), GETPOST('deadline_year', 'int'));
        $result = $object->update($user);
        if ($result < 0) {
            setEventMessages($object->error, $object->errors, 'errors');
            $action = 'edit_date_deadline';
        } else {
            header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
            exit();
        }
    } // Set Assigned usergroups
    elseif ($action == 'set_assigned_usergroups' && $user->rights->requestmanager->creer && $object->statut_type != RequestManager::STATUS_TYPE_CLOSED) {
        $object->oldcopy = clone $object;
        $object->assigned_usergroup_ids = GETPOST('assigned_usergroups', 'array');
        $result = $object->update($user);
        if ($result < 0) {
            setEventMessages($object->error, $object->errors, 'errors');
            $action = 'edit_assigned_usergroups';
        } else {
            header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
            exit();
        }
    } // Set Assigned users
    elseif ($action == 'set_assigned_users' && $user->rights->requestmanager->creer && $object->statut_type != RequestManager::STATUS_TYPE_CLOSED) {
        $object->oldcopy = clone $object;
        $object->assigned_user_ids = GETPOST('assigned_users', 'array');
        $result = $object->update($user);
        if ($result < 0) {
            setEventMessages($object->error, $object->errors, 'errors');
            $action = 'edit_assigned_users';
        } else {
            header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
            exit();
        }
    } // Set myself in Assigned users
    elseif (($action == 'set_myself_assigned_user' || ($action == 'confirm_set_myself_assigned_user' && $confirm == 'yes')) && $user->rights->requestmanager->creer && $object->statut_type != RequestManager::STATUS_TYPE_CLOSED) {
        $object->oldcopy = clone $object;
        if (count($object->assigned_user_ids) == 0 || $action == 'confirm_set_myself_assigned_user') {
            $object->assigned_user_ids[] = $user->id;
            $result = $object->update($user);
            if ($result < 0) {
                setEventMessages($object->error, $object->errors, 'errors');
            } else {
                header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
                exit();
            }
        }
    } // Set Assigned Notification
    elseif (!empty($conf->global->REQUESTMANAGER_NOTIFICATION_ASSIGNED_BY_EMAIL) && $action == 'set_assigned_notification' && $user->rights->requestmanager->creer && $object->statut_type != RequestManager::STATUS_TYPE_CLOSED) {
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
    elseif ($action == 'set_description' && $user->rights->requestmanager->creer && ($object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS || $object->statut_type == RequestManager::STATUS_TYPE_INITIAL)) {
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
    } // Set categories
    elseif ($action == 'set_categories' && $user->rights->requestmanager->creer && $object->statut_type != RequestManager::STATUS_TYPE_CLOSED) {
        // Category association
        $object->fetch_tags(1);
        $object->oldcopy = clone $object;
        $categories = GETPOST('categories');
        $result = $object->setCategories($categories);
        if ($result < 0) {
            setEventMessages($object->error, $object->errors, 'errors');
            $action = 'edit_categories';
        } else {
            header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
            exit();
        }
    } // Set Requesters
    elseif ($action == 'set_requesters' && $user->rights->requestmanager->creer && $object->statut_type != RequestManager::STATUS_TYPE_CLOSED) {
        $object->oldcopy = clone $object;
        //$object->requester_ids = GETPOST('requester_contacts', 'array');
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
    elseif ($action == 'set_watchers' && $user->rights->requestmanager->creer && $object->statut_type != RequestManager::STATUS_TYPE_CLOSED) {
        $object->oldcopy = clone $object;
        //$object->watcher_ids = GETPOST('watcher_contacts', 'array');
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
    elseif ($action == 'set_status' && $user->rights->requestmanager->creer
	//&&
 //       ($object->statut_type == RequestManager::STATUS_TYPE_INITIAL || $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS)
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
    elseif ($action == 'confirm_resolve' && $confirm == 'yes' && $user->rights->requestmanager->creer
	//&& $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS
	) {
        $reason_resolution = GETPOST('reason_resolution', 'int');
        $reason_resolution_details = GETPOST('reason_resolution_details');
        $result = $object->set_status(0, RequestManager::STATUS_TYPE_RESOLVED, $user, $reason_resolution, $reason_resolution_details);
        if ($result < 0) {
            setEventMessages($object->error, $object->errors, 'errors');
        } else {
            header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
            exit();
        }
    } // Close
    elseif ($action == 'confirm_close' && $confirm == 'yes' && $user->rights->requestmanager->creer && /*$object->statut_type == RequestManager::STATUS_TYPE_RESOLVED &&*/
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
        $selected_status = GETPOST('new_status', 'int');
        $result = $object->set_status($selected_status, -1, $user);
        if ($result < 0) {
            setEventMessages($object->error, $object->errors, 'errors');
        } else {
            header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
            exit();
        }
    } // Re Open
    elseif ($action == 'confirm_reopen' && $confirm == 'yes' && $user->rights->requestmanager->cloturer && $object->statut_type == RequestManager::STATUS_TYPE_CLOSED) {
        $selected_status = GETPOST('new_status', 'int');
        $result = $object->set_status($selected_status, -1, $user);
        if ($result < 0) {
            setEventMessages($object->error, $object->errors, 'errors');
        } else {
            header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
            exit();
        }
    } // Extrafields
    elseif ($action == 'update_extras' && $user->rights->requestmanager->creer && $object->statut_type != RequestManager::STATUS_TYPE_CLOSED) {
        $object->oldcopy = clone $object;
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
            } else {
                // Call trigger
                $object->context = array('extrafieldaddupdate' => 1);
                $result = $object->call_trigger('REQUESTMANAGER_EXTRAFIELDS_ADD_UPDATE', $user);
                if ($result < 0) $error++;
                // End call trigger
            }
        }
        if ($error) $action = 'edit_extras';
    } // Add a new line
    elseif ($action == 'addline' && $user->rights->requestmanager->creer && ($object->statut_type == RequestManager::STATUS_TYPE_INITIAL || $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS)) {
        require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
        if (!empty($conf->variants->enabled)) {
            require_once DOL_DOCUMENT_ROOT . '/variants/class/ProductCombination.class.php';
        }

        $langs->load('errors');
        $error = 0;

        // Set if we used free entry or predefined product
        $predef = '';
        $product_desc = (GETPOST('dp_desc') ? GETPOST('dp_desc') : '');
        if (!$product_desc) {
            $product_desc = GETPOST('product_desc');
        }
        $price_ht = GETPOST('price_ht');
        $price_ht_devise = GETPOST('multicurrency_price_ht');
        $prod_entry_mode = GETPOST('prod_entry_mode');
        if ($prod_entry_mode == 'free') {
            $idprod = 0;
            $tva_tx = (GETPOST('tva_tx') ? GETPOST('tva_tx') : 0);
        } else {
            $idprod = GETPOST('idprod', 'int');
            $tva_tx = '';
        }

        $qty = GETPOST('qty' . $predef);
        $remise_percent = GETPOST('remise_percent' . $predef);

        // Extrafields
        $extrafieldsline = new ExtraFields($db);
        $extralabelsline = $extrafieldsline->fetch_name_optionals_label($object->table_element_line);
        $array_options = $extrafieldsline->getOptionalsFromPost($extralabelsline, $predef);
        // Unset extrafield
        if (is_array($extralabelsline)) {
            // Get extra fields
            foreach ($extralabelsline as $key => $value) {
                unset($_POST["options_" . $key]);
            }
        }

        if (empty($idprod) && ($price_ht < 0) && ($qty < 0)) {
            setEventMessages($langs->trans('ErrorBothFieldCantBeNegative', $langs->transnoentitiesnoconv('UnitPriceHT'), $langs->transnoentitiesnoconv('Qty')), null, 'errors');
            $error++;
        }
        if ($prod_entry_mode == 'free' && empty($idprod) && GETPOST('type') < 0) {
            setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Type')), null, 'errors');
            $error++;
        }
        if ($prod_entry_mode == 'free' && empty($idprod) && (!($price_ht >= 0) || $price_ht == '') && (!($price_ht_devise >= 0) || $price_ht_devise == ''))    // Unit price can be 0 but not ''
        {
            setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("UnitPriceHT")), null, 'errors');
            $error++;
        }
        if ($qty == '') {
            setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Qty')), null, 'errors');
            $error++;
        }
        if ($prod_entry_mode == 'free' && empty($idprod) && empty($product_desc)) {
            setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Description')), null, 'errors');
            $error++;
        }

        if (!$error && !empty($conf->variants->enabled) && $prod_entry_mode != 'free') {
            if ($combinations = GETPOST('combinations', 'array')) {
                //Check if there is a product with the given combination
                $prodcomb = new ProductCombination($db);

                if ($res = $prodcomb->fetchByProductCombination2ValuePairs($idprod, $combinations)) {
                    $idprod = $res->fk_product_child;
                } else {
                    setEventMessage($langs->trans('ErrorProductCombinationNotFound'), 'errors');
                    $error++;
                }
            }
        }

        if (!$error && ($qty >= 0) && (!empty($product_desc) || !empty($idprod))) {
            // Clean parameters
            $date_start = dol_mktime(GETPOST('date_start' . $predef . 'hour'), GETPOST('date_start' . $predef . 'min'), GETPOST('date_start' . $predef . 'sec'), GETPOST('date_start' . $predef . 'month'), GETPOST('date_start' . $predef . 'day'), GETPOST('date_start' . $predef . 'year'));
            $date_end = dol_mktime(GETPOST('date_end' . $predef . 'hour'), GETPOST('date_end' . $predef . 'min'), GETPOST('date_end' . $predef . 'sec'), GETPOST('date_end' . $predef . 'month'), GETPOST('date_end' . $predef . 'day'), GETPOST('date_end' . $predef . 'year'));
            $price_base_type = (GETPOST('price_base_type', 'alpha') ? GETPOST('price_base_type', 'alpha') : 'HT');

            // Ecrase $pu par celui du produit
            // Ecrase $desc par celui du produit
            // Ecrase $tva_tx par celui du produit
            // Ecrase $base_price_type par celui du produit
            if (!empty($idprod)) {
                $prod = new Product($db);
                $prod->fetch($idprod);

                $label = ((GETPOST('product_label') && GETPOST('product_label') != $prod->label) ? GETPOST('product_label') : '');

                // Update if prices fields are defined
                $tva_tx = get_default_tva($mysoc, $object->thirdparty, $prod->id);
                $tva_npr = get_default_npr($mysoc, $object->thirdparty, $prod->id);
                if (empty($tva_tx)) $tva_npr = 0;

                $pu_ht = $prod->price;
                $pu_ttc = $prod->price_ttc;
                $price_min = $prod->price_min;
                $price_base_type = $prod->price_base_type;

                // multiprix
                if (!empty($conf->global->PRODUIT_MULTIPRICES) && !empty($object->thirdparty->price_level)) {
                    $pu_ht = $prod->multiprices[$object->thirdparty->price_level];
                    $pu_ttc = $prod->multiprices_ttc[$object->thirdparty->price_level];
                    $price_min = $prod->multiprices_min[$object->thirdparty->price_level];
                    $price_base_type = $prod->multiprices_base_type[$object->thirdparty->price_level];
                    if (!empty($conf->global->PRODUIT_MULTIPRICES_USE_VAT_PER_LEVEL))  // using this option is a bug. kept for backward compatibility
                    {
                        if (isset($prod->multiprices_tva_tx[$object->thirdparty->price_level])) $tva_tx = $prod->multiprices_tva_tx[$object->thirdparty->price_level];
                        if (isset($prod->multiprices_recuperableonly[$object->thirdparty->price_level])) $tva_npr = $prod->multiprices_recuperableonly[$object->thirdparty->price_level];
                    }
                } elseif (!empty($conf->global->PRODUIT_CUSTOMER_PRICES)) {
                    require_once DOL_DOCUMENT_ROOT . '/product/class/productcustomerprice.class.php';

                    $prodcustprice = new Productcustomerprice($db);

                    $filter = array('t.fk_product' => $prod->id, 't.fk_soc' => $object->thirdparty->id);

                    $result = $prodcustprice->fetch_all('', '', 0, 0, $filter);
                    if ($result >= 0) {
                        if (count($prodcustprice->lines) > 0) {
                            $pu_ht = price($prodcustprice->lines[0]->price);
                            $pu_ttc = price($prodcustprice->lines[0]->price_ttc);
                            $price_base_type = $prodcustprice->lines[0]->price_base_type;
                            $tva_tx = $prodcustprice->lines[0]->tva_tx;
                            if ($prodcustprice->lines[0]->default_vat_code && !preg_match('/\(.*\)/', $tva_tx)) $tva_tx .= ' (' . $prodcustprice->lines[0]->default_vat_code . ')';
                            $tva_npr = $prodcustprice->lines[0]->recuperableonly;
                            if (empty($tva_tx)) $tva_npr = 0;
                        }
                    } else {
                        setEventMessages($prodcustprice->error, $prodcustprice->errors, 'errors');
                    }
                }

                $tmpvat = price2num(preg_replace('/\s*\(.*\)/', '', $tva_tx));
                $tmpprodvat = price2num(preg_replace('/\s*\(.*\)/', '', $prod->tva_tx));

                // if price ht is forced (ie: calculated by margin rate and cost price)
                if (!empty($price_ht)) {
                    $pu_ht = price2num($price_ht, 'MU');
                    $pu_ttc = price2num($pu_ht * (1 + ($tmpvat / 100)), 'MU');
                }
                // On reevalue prix selon taux tva car taux tva transaction peut etre different
                // de ceux du produit par defaut (par exemple si pays different entre vendeur et acheteur).
                elseif ($tmpvat != $tmpprodvat) {
                    if ($price_base_type != 'HT') {
                        $pu_ht = price2num($pu_ttc / (1 + ($tmpvat / 100)), 'MU');
                    } else {
                        $pu_ttc = price2num($pu_ht * (1 + ($tmpvat / 100)), 'MU');
                    }
                }

                $desc = '';

                // Define output language
                if (!empty($conf->global->MAIN_MULTILANGS) && !empty($conf->global->PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE)) {
                    $outputlangs = $langs;
                    $newlang = '';
                    if (empty($newlang) && GETPOST('lang_id', 'aZ09'))
                        $newlang = GETPOST('lang_id', 'aZ09');
                    if (empty($newlang))
                        $newlang = $object->thirdparty->default_lang;
                    if (!empty($newlang)) {
                        $outputlangs = new Translate("", $conf);
                        $outputlangs->setDefaultLang($newlang);
                    }

                    $desc = (!empty($prod->multilangs [$outputlangs->defaultlang] ["description"])) ? $prod->multilangs [$outputlangs->defaultlang] ["description"] : $prod->description;
                } else {
                    $desc = $prod->description;
                }

                $desc = dol_concatdesc($desc, $product_desc);

                // Add custom code and origin country into description
                if (empty($conf->global->MAIN_PRODUCT_DISABLE_CUSTOMCOUNTRYCODE) && (!empty($prod->customcode) || !empty($prod->country_code))) {
                    $tmptxt = '(';
                    // Define output language
                    if (!empty($conf->global->MAIN_MULTILANGS) && !empty($conf->global->PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE)) {
                        $outputlangs = $langs;
                        $newlang = '';
                        if (empty($newlang) && GETPOST('lang_id', 'alpha'))
                            $newlang = GETPOST('lang_id', 'alpha');
                        if (empty($newlang))
                            $newlang = $object->thirdparty->default_lang;
                        if (!empty($newlang)) {
                            $outputlangs = new Translate("", $conf);
                            $outputlangs->setDefaultLang($newlang);
                            $outputlangs->load('products');
                        }
                        if (!empty($prod->customcode))
                            $tmptxt .= $outputlangs->transnoentitiesnoconv("CustomCode") . ': ' . $prod->customcode;
                        if (!empty($prod->customcode) && !empty($prod->country_code))
                            $tmptxt .= ' - ';
                        if (!empty($prod->country_code))
                            $tmptxt .= $outputlangs->transnoentitiesnoconv("CountryOrigin") . ': ' . getCountry($prod->country_code, 0, $db, $outputlangs, 0);
                    } else {
                        if (!empty($prod->customcode))
                            $tmptxt .= $langs->transnoentitiesnoconv("CustomCode") . ': ' . $prod->customcode;
                        if (!empty($prod->customcode) && !empty($prod->country_code))
                            $tmptxt .= ' - ';
                        if (!empty($prod->country_code))
                            $tmptxt .= $langs->transnoentitiesnoconv("CountryOrigin") . ': ' . getCountry($prod->country_code, 0, $db, $langs, 0);
                    }
                    $tmptxt .= ')';
                    $desc = dol_concatdesc($desc, $tmptxt);
                }

                $type = $prod->type;
                $fk_unit = $prod->fk_unit;
            } else {
                $pu_ht = price2num($price_ht, 'MU');
                $pu_ttc = price2num(GETPOST('price_ttc'), 'MU');
                $tva_npr = (preg_match('/\*/', $tva_tx) ? 1 : 0);
                $tva_tx = str_replace('*', '', $tva_tx);
                $label = (GETPOST('product_label') ? GETPOST('product_label') : '');
                $desc = $product_desc;
                $type = GETPOST('type');
                $fk_unit = GETPOST('units', 'alpha');
                $pu_ht_devise = price2num($price_ht_devise, 'MU');
            }

            // Margin
            $fournprice = price2num(GETPOST('fournprice' . $predef) ? GETPOST('fournprice' . $predef) : '');
            $buyingprice = price2num(GETPOST('buying_price' . $predef) != '' ? GETPOST('buying_price' . $predef) : '');    // If buying_price is '0', we muste keep this value

            // Local Taxes
            $localtax1_tx = get_localtax($tva_tx, 1, $object->thirdparty);
            $localtax2_tx = get_localtax($tva_tx, 2, $object->thirdparty);

            $desc = dol_htmlcleanlastbr($desc);

            $info_bits = 0;
            if ($tva_npr)
                $info_bits |= 0x01;

            if (!empty($price_min) && (price2num($pu_ht) * (1 - price2num($remise_percent) / 100) < price2num($price_min))) {
                $mesg = $langs->trans("CantBeLessThanMinPrice", price(price2num($price_min, 'MU'), 0, $langs, 0, 0, -1, $conf->currency));
                setEventMessages($mesg, null, 'errors');
            } else {
                // Insert line
                $result = $object->addline($desc, $pu_ht, $qty, $tva_tx, $localtax1_tx, $localtax2_tx, $idprod, $remise_percent, $info_bits, 0, $price_base_type, $pu_ttc, $date_start, $date_end, $type, -1, 0, GETPOST('fk_parent_line'), $fournprice, $buyingprice, $label, $array_options, $fk_unit, '', 0, $pu_ht_devise);

                if ($result > 0) {
                    $ret = $object->fetch($object->id); // Reload to get new records

                    if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
                        // Define output language
                        $outputlangs = $langs;
                        $newlang = GETPOST('lang_id', 'alpha');
                        if (!empty($conf->global->MAIN_MULTILANGS) && empty($newlang))
                            $newlang = $object->thirdparty->default_lang;
                        if (!empty($newlang)) {
                            $outputlangs = new Translate("", $conf);
                            $outputlangs->setDefaultLang($newlang);
                        }

                        // TOODO : generate document
                        //$object->generateDocument($object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
                    }

                    unset($_POST['prod_entry_mode']);

                    unset($_POST['qty']);
                    unset($_POST['type']);
                    unset($_POST['remise_percent']);
                    unset($_POST['price_ht']);
                    unset($_POST['multicurrency_price_ht']);
                    unset($_POST['price_ttc']);
                    unset($_POST['tva_tx']);
                    unset($_POST['product_ref']);
                    unset($_POST['product_label']);
                    unset($_POST['product_desc']);
                    unset($_POST['fournprice']);
                    unset($_POST['buying_price']);
                    unset($_POST['np_marginRate']);
                    unset($_POST['np_markRate']);
                    unset($_POST['dp_desc']);
                    unset($_POST['idprod']);
                    unset($_POST['units']);

                    unset($_POST['date_starthour']);
                    unset($_POST['date_startmin']);
                    unset($_POST['date_startsec']);
                    unset($_POST['date_startday']);
                    unset($_POST['date_startmonth']);
                    unset($_POST['date_startyear']);
                    unset($_POST['date_endhour']);
                    unset($_POST['date_endmin']);
                    unset($_POST['date_endsec']);
                    unset($_POST['date_endday']);
                    unset($_POST['date_endmonth']);
                    unset($_POST['date_endyear']);
                } else {
                    setEventMessages($object->error, $object->errors, 'errors');
                }
            }
        }
        $action = '';
    } // Update a line
    elseif ($action == 'updateline' && $user->rights->requestmanager->creer && ($object->statut_type == RequestManager::STATUS_TYPE_INITIAL || $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS) && GETPOST('save')) {
        // Clean parameters
        $date_start = '';
        $date_end = '';
        $date_start = dol_mktime(GETPOST('date_starthour'), GETPOST('date_startmin'), GETPOST('date_startsec'), GETPOST('date_startmonth'), GETPOST('date_startday'), GETPOST('date_startyear'));
        $date_end = dol_mktime(GETPOST('date_endhour'), GETPOST('date_endmin'), GETPOST('date_endsec'), GETPOST('date_endmonth'), GETPOST('date_endday'), GETPOST('date_endyear'));
        $description = dol_htmlcleanlastbr(GETPOST('product_desc'));
        $pu_ht = GETPOST('price_ht');
        $vat_rate = (GETPOST('tva_tx') ? GETPOST('tva_tx') : 0);
        $pu_ht_devise = GETPOST('multicurrency_subprice');

        // Define info_bits
        $info_bits = 0;
        if (preg_match('/\*/', $vat_rate))
            $info_bits |= 0x01;

        // Define vat_rate
        $vat_rate = str_replace('*', '', $vat_rate);
        $localtax1_rate = get_localtax($vat_rate, 1, $object->thirdparty, $mysoc);
        $localtax2_rate = get_localtax($vat_rate, 2, $object->thirdparty, $mysoc);

        // Add buying price
        $fournprice = price2num(GETPOST('fournprice') ? GETPOST('fournprice') : '');
        $buyingprice = price2num(GETPOST('buying_price') != '' ? GETPOST('buying_price') : '');    // If buying_price is '0', we muste keep this value

        // Extrafields Lines
        $extrafieldsline = new ExtraFields($db);
        $extralabelsline = $extrafieldsline->fetch_name_optionals_label($object->table_element_line);
        $array_options = $extrafieldsline->getOptionalsFromPost($extralabelsline);
        // Unset extrafield POST Data
        if (is_array($extralabelsline)) {
            foreach ($extralabelsline as $key => $value) {
                unset($_POST["options_" . $key]);
            }
        }

        // Define special_code for special lines
        $special_code = GETPOST('special_code');
        if (!GETPOST('qty')) $special_code = 3;

        // Check minimum price
        $productid = GETPOST('productid', 'int');
        if (!empty($productid)) {
            $product = new Product($db);
            $product->fetch($productid);

            $type = $product->type;

            $price_min = $product->price_min;
            if (!empty($conf->global->PRODUIT_MULTIPRICES) && !empty($object->thirdparty->price_level))
                $price_min = $product->multiprices_min [$object->thirdparty->price_level];

            $label = ((GETPOST('update_label') && GETPOST('product_label')) ? GETPOST('product_label') : '');

            if ($price_min && (price2num($pu_ht) * (1 - price2num(GETPOST('remise_percent')) / 100) < price2num($price_min))) {
                setEventMessages($langs->trans("CantBeLessThanMinPrice", price(price2num($price_min, 'MU'), 0, $langs, 0, 0, -1, $conf->currency)), null, 'errors');
                $error++;
            }
        } else {
            $type = GETPOST('type');
            $label = (GETPOST('product_label') ? GETPOST('product_label') : '');

            // Check parameters
            if (GETPOST('type') < 0) {
                setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Type")), null, 'errors');
                $error++;
            }
        }

        if (!$error) {

            if (empty($user->rights->margins->creer)) {
                foreach ($object->lines as &$line) {
                    if ($line->id == GETPOST('lineid')) {
                        $fournprice = $line->fk_fournprice;
                        $buyingprice = $line->pa_ht;
                        break;
                    }
                }
            }
            $result = $object->updateline(GETPOST('lineid'), $description, $pu_ht, GETPOST('qty'), GETPOST('remise_percent'), $vat_rate, $localtax1_rate, $localtax2_rate, 'HT', $info_bits, $date_start, $date_end, $type, GETPOST('fk_parent_line'), 0, $fournprice, $buyingprice, $label, $special_code, $array_options, GETPOST('units'), $pu_ht_devise);

            if ($result >= 0) {
                if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
                    // Define output language
                    $outputlangs = $langs;
                    $newlang = '';
                    if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09'))
                        $newlang = GETPOST('lang_id', 'aZ09');
                    if ($conf->global->MAIN_MULTILANGS && empty($newlang))
                        $newlang = $object->thirdparty->default_lang;
                    if (!empty($newlang)) {
                        $outputlangs = new Translate("", $conf);
                        $outputlangs->setDefaultLang($newlang);
                    }

                    $ret = $object->fetch($object->id); // Reload to get new records
                }

                unset($_POST['qty']);
                unset($_POST['type']);
                unset($_POST['productid']);
                unset($_POST['remise_percent']);
                unset($_POST['price_ht']);
                unset($_POST['multicurrency_price_ht']);
                unset($_POST['price_ttc']);
                unset($_POST['tva_tx']);
                unset($_POST['product_ref']);
                unset($_POST['product_label']);
                unset($_POST['product_desc']);
                unset($_POST['fournprice']);
                unset($_POST['buying_price']);

                unset($_POST['date_starthour']);
                unset($_POST['date_startmin']);
                unset($_POST['date_startsec']);
                unset($_POST['date_startday']);
                unset($_POST['date_startmonth']);
                unset($_POST['date_startyear']);
                unset($_POST['date_endhour']);
                unset($_POST['date_endmin']);
                unset($_POST['date_endsec']);
                unset($_POST['date_endday']);
                unset($_POST['date_endmonth']);
                unset($_POST['date_endyear']);
            } else {
                setEventMessages($object->error, $object->errors, 'errors');
            }
        }
    } elseif ($action == 'updateline' && $user->rights->requestmanager->creer && ($object->statut_type == RequestManager::STATUS_TYPE_INITIAL || $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS) && GETPOST('cancel') == $langs->trans('Cancel')) {
        header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $object->id);
        exit();
    } // Remove a product line
    elseif ($action == 'confirm_deleteline' && $confirm == 'yes' && $user->rights->requestmanager->creer) {
        $result = $object->deleteline($lineid);
        if ($result > 0) {
            // Define output language
            $outputlangs = $langs;
            $newlang = '';
            if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09'))
                $newlang = GETPOST('lang_id', 'aZ09');
            if ($conf->global->MAIN_MULTILANGS && empty($newlang))
                $newlang = $object->thirdparty->default_lang;
            if (!empty($newlang)) {
                $outputlangs = new Translate("", $conf);
                $outputlangs->setDefaultLang($newlang);
            }
            if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
                $ret = $object->fetch($object->id); // Reload to get new records
            }

            header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
            exit();
        } else {
            setEventMessages($object->error, $object->errors, 'errors');
        }
    }
    /*********************************************************
     * Contacts management
     *********************************************************/
    // Add contact
    elseif ($action == 'add_contact' && $user->rights->requestmanager->creer && $object->statut_type != RequestManager::STATUS_TYPE_CLOSED && $object->statut_type != RequestManager::STATUS_TYPE_RESOLVED) {
        $object->oldcopy = clone $object;
        $object->add_contact_action(intval(GETPOST('add_contact_type_id')));
    }
    // Delete contact
    elseif ($action == 'del_contact' && $user->rights->requestmanager->creer && $object->statut_type != RequestManager::STATUS_TYPE_CLOSED && $object->statut_type != RequestManager::STATUS_TYPE_RESOLVED) {
        $object->oldcopy = clone $object;
        $object->del_contact_action(intval(GETPOST('del_contact_type_id')));
    }
    /*********************************************************
     * Links management
     *********************************************************/
    // Add link
    elseif ($action == 'addlink' && !empty($permissiondellink) && !GETPOST('cancel', 'alpha') && $object->id > 0 && $addlinkid > 0 && $result > 0) {
        $object->oldcopy = clone $object;
        $result = $object->add_object_linked($addlink, $addlinkid);
        if ($result < 0) {
            setEventMessages($object->error, $object->errors, 'errors');
            $error++;
        } else {
            // Call trigger
            $object->context = array('addlink' => $addlink, 'addlinkid' => $addlinkid);
            $result = $object->call_trigger('REQUESTMANAGER_ADD_LINK', $user);
            if ($result < 0) $error++;
            // End call trigger
        }
    }
    // Delete link
    elseif ($action == 'dellink' && !empty($permissiondellink) && !GETPOST('cancel', 'alpha') && $object->id > 0 && $dellinkid > 0 && $result > 0) {
        $object->oldcopy = clone $object;
        $result = $object->deleteObjectLinked(0, '', 0, '', $dellinkid);
        if ($result < 0) {
            setEventMessages($object->error, $object->errors, 'errors');
            $error++;
        } else {
            // Call trigger
            $object->context = array('dellink' => $dellinkid);
            $result = $object->call_trigger('REQUESTMANAGER_DEL_LINK', $user);
            if ($result < 0) $error++;
            // End call trigger
        }
    }
    /*********************************************************
     * Message form
     *********************************************************/
    // Add file
    elseif (GETPOST('addfile', 'alpha')) {
        dol_include_once('/requestmanager/lib/requestmanagermessage.lib.php');

        // Set tmp user directory
        $vardir = $conf->user->dir_output . "/" . $user->id;
        $upload_dir_tmp = $vardir . '/temp/rm-' . $object->id . '/';             // TODO Add $keytoavoidconflict in upload_dir path

        requestmanagermessage_add_file_process($object, $upload_dir_tmp, 0, 0, 'addedfile', '', null);
        $action = 'premessage';
    }
    // Remove file
    elseif (!empty($_POST['removedfile']) && empty($_POST['removAll'])) {
        dol_include_once('/requestmanager/lib/requestmanagermessage.lib.php');

        // Set tmp user directory
        $vardir = $conf->user->dir_output . "/" . $user->id;
        $upload_dir_tmp = $vardir . '/temp/rm-' . $object->id . '/';             // TODO Add $keytoavoidconflict in upload_dir path

        // TODO Delete only files that was uploaded from email form. This can be addressed by adding the trackid into the temp path then changing donotdeletefile to 2 instead of 1 to say "delete only if into temp dir"
        // GETPOST('removedfile','alpha') is position of file into $_SESSION["listofpaths"...] array.
        requestmanagermessage_remove_file_process($object, GETPOST('removedfile', 'alpha'), 0, 0);   // We do not delete because if file is the official PDF of doc, we don't want to remove it physically
        $action = 'premessage';
    }
    // Remove all files
    elseif (GETPOST('removAll', 'alpha')) {
        dol_include_once('/requestmanager/class/html.formrequestmanagermessage.class.php');
        $formrequestmanagermessage = new FormRequestManagerMessage($db, $requestmanager);
        $formrequestmanagermessage->remove_all_attached_files();
    }
    // Add message
    elseif ($action == 'premessage' && GETPOST('addmessage', 'alpha') && !$_POST['addfile'] && !$_POST['removAll'] && !$_POST['removedfile'] && !$_POST['modelselected'] && !$_POST['addknowledgebasedescription'] &&
        $user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS
    ) {
        dol_include_once('/requestmanager/class/html.formrequestmanagermessage.class.php');
        $formrequestmanagermessage = new FormRequestManagerMessage($db, $object);

        $requestmanagermessage = new RequestManagerMessage($db);

        $requestmanagermessage->message_type = GETPOST('message_type', 'int');
        $requestmanagermessage->notify_assigned = GETPOST('notify_assigned', 'int');
        $requestmanagermessage->notify_requesters = GETPOST('notify_requesters', 'int');
        $requestmanagermessage->notify_watchers = GETPOST('notify_watchers', 'int');
        $requestmanagermessage->attached_files = $formrequestmanagermessage->get_attached_files();
        $requestmanagermessage->knowledge_base_ids = GETPOST('knowledgebaseselected', 'array');
        $requestmanagermessage->label = GETPOST('subject', 'alpha');
        $requestmanagermessage->note = GETPOST('message');
        $requestmanagermessage->requestmanager = $object;

        // Get extra fields of the message
        $message_extrafields = new ExtraFields($db);
        $message_extralabels = $message_extrafields->fetch_name_optionals_label($requestmanagermessage->table_element);
        $ret = $message_extrafields->setOptionalsFromPost($message_extralabels, $requestmanagermessage);

        // create
        $result = $requestmanagermessage->create($user);
        if ($result < 0) {
            setEventMessages($requestmanagermessage->error, $requestmanagermessage->errors, 'errors');
            $action = 'premessage';
        } else {
            $formrequestmanagermessage->clear_datas_in_session();
            $formrequestmanagermessage->remove_all_attached_files();
            header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
            exit();
        }
    } // Clear message
    elseif ($action == 'rm_reset_data_in_session' && $user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS
    ) {
        dol_include_once('/requestmanager/class/html.formrequestmanagermessage.class.php');
        $formrequestmanagermessage = new FormRequestManagerMessage($db, $object);
        $formrequestmanagermessage->clear_datas_in_session();

        header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=premessage&messagemode=init#formmessagebeforetitle');
        exit();
    }

    // Actions to build doc
    //$upload_dir = $conf->requestmanager->multidir_output[$object->entity];
    //$permissioncreate=$user->rights->requestmanager->creer;
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
if ($object->id > 0) {
	/*
	 * Show object in view mode
	 */

	$head = requestmanager_prepare_head($object);
	dol_fiche_head($head, 'card', $langs->trans('RequestManagerCard'), 0, 'requestmanager@requestmanager');

	$formconfirm = '';

    // Confirm resolve
	if ($action == 'resolve') {
        $reason_resolution = GETPOST('reason_resolution', 'int');
        $reason_resolution_details = GETPOST('reason_resolution_details');

        $formquestion = array(
            array('type' => 'other', 'name' => 'reason_resolution', 'label' => $langs->trans('RequestManagerReasonResolution'),
                'value' => $formrequestmanager->select_reason_resolution($object->fk_type, $reason_resolution, 'reason_resolution', 1, 0, array(), 0, 0, 'minwidth300')),
        );

        $doleditor = new DolEditor('reason_resolution_details', $reason_resolution_details, '', 200, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, '90%');
        $formquestion = array_merge($formquestion, array(
            array('type' => 'other', 'name' => 'reason_resolution_details', 'label' => $langs->trans('RequestManagerReasonResolutionDetails'), 'value' => $doleditor->Create(1, !empty($conf->fckeditor->enabled) ? ".on('change', function(e) { $('textarea#reason_resolution_details').val(encodeURIComponent(this.getData())); });" : '')),
        ));

		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('RequestManagerResolveRequest'), $langs->trans('RequestManagerConfirmResolveRequest', $object->ref), 'confirm_resolve', $formquestion, 0, 1, 520, 800);
	}

    // Confirm close
	if ($action == 'close') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('RequestManagerCloseRequest'), $langs->trans('RequestManagerConfirmCloseRequest', $object->ref), 'confirm_close', '', 0, 1);
	}

    // Confirm not resolved
	if ($action == 'notresolved') {
        // Get group of user
        $user_groups = $usergroup_static->listGroupsForUser($user->id);
        $user_groups = is_array($user_groups) ? array_keys($user_groups) : array();

        // Get all status of the request type
        $requestManagerStatusDictionary = Dictionary::getDictionary($db, 'requestmanager', 'requestmanagerstatus');
        $requestManagerStatusDictionary->fetch_lines(1, array('request_type' => array($object->fk_type)));

        $status_list = array();
        foreach ($requestManagerStatusDictionary->lines as $s) {
            $not_authorized_user = !empty($s->fields['authorized_user']) ? !in_array($user->id, explode(',', $s->fields['authorized_user'])) : false;
            $not_authorized_usergroup = false;
            if (!empty($s->fields['authorized_usergroup'])) {
                $not_authorized_usergroup = true;
                $authorized_usergroup = explode(',', $s->fields['authorized_usergroup']);
                foreach ($authorized_usergroup as $group_id) {
                    if (in_array($group_id, $user_groups)) {
                        $not_authorized_usergroup = false;
                        break;
                    }
                }
            }
            if ($not_authorized_user || $not_authorized_usergroup) continue;
            if ($s->fields['type'] == RequestManager::STATUS_TYPE_RESOLVED || $s->fields['type'] == RequestManager::STATUS_TYPE_CLOSED) continue;

            $sort_key = $s->fields['type'] . '_' . $s->id;
            $status_list[$sort_key] = array($s->id, $object->LibStatut($s->id, 13));
        }
        ksort($status_list);

        $status_array = array();
        foreach ($status_list as $s) {
            $status_array[$s[0]] = $s[1];
        }

        $selected_status = GETPOST('new_status', 'int');

        $formquestion = array(
            array('type' => 'other', 'name' => 'new_status', 'label' => $langs->trans('RequestManagerStatus'), 'value' => $form->selectarray('new_status', $status_array, $selected_status)),
        );

		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('RequestManagerNotResolvedRequest'), $langs->trans('RequestManagerConfirmNotResolvedRequest', $object->ref), 'confirm_notresolved', $formquestion, 0, 1);
	}

    // Confirm re open
	if ($action == 'reopen') {
        // Get group of user
        $user_groups = $usergroup_static->listGroupsForUser($user->id);
        $user_groups = is_array($user_groups) ? array_keys($user_groups) : array();

        // Get all status of the request type
        $requestManagerStatusDictionary = Dictionary::getDictionary($db, 'requestmanager', 'requestmanagerstatus');
        $requestManagerStatusDictionary->fetch_lines(1, array('request_type' => array($object->fk_type)));

        $status_list = array();
        foreach ($requestManagerStatusDictionary->lines as $s) {
            $not_authorized_user = !empty($s->fields['authorized_user']) ? !in_array($user->id, explode(',', $s->fields['authorized_user'])) : false;
            $not_authorized_usergroup = false;
            if (!empty($s->fields['authorized_usergroup'])) {
                $not_authorized_usergroup = true;
                $authorized_usergroup = explode(',', $s->fields['authorized_usergroup']);
                foreach ($authorized_usergroup as $group_id) {
                    if (in_array($group_id, $user_groups)) {
                        $not_authorized_usergroup = false;
                        break;
                    }
                }
            }
            if ($not_authorized_user || $not_authorized_usergroup) continue;
            if ($s->fields['type'] == RequestManager::STATUS_TYPE_RESOLVED || $s->fields['type'] == RequestManager::STATUS_TYPE_CLOSED) continue;

            $sort_key = $s->fields['type'] . '_' . $s->id;
            $status_list[$sort_key] = array($s->id, $object->LibStatut($s->id, 13));
        }
        ksort($status_list);

        $status_array = array();
        foreach ($status_list as $s) {
            $status_array[$s[0]] = $s[1];
        }

        $selected_status = GETPOST('new_status', 'int');

        $formquestion = array(
            array('type' => 'other', 'name' => 'new_status', 'label' => $langs->trans('RequestManagerStatus'), 'value' => $form->selectarray('new_status', $status_array, $selected_status)),
        );

        $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('RequestManagerReOpenRequest'), $langs->trans('RequestManagerConfirmReOpenRequest', $object->ref), 'confirm_reopen', $formquestion, 0, 1);
    }

    // Confirm delete
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('RequestManagerDeleteRequest'), $langs->trans('RequestManagerConfirmDeleteRequest', $object->ref), 'confirm_delete', '', 0, 1);
	}

    // Confirmation to delete line
    if ($action == 'ask_deleteline')
    {
        $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id . '&lineid=' . $lineid, $langs->trans('DeleteProductLine'), $langs->trans('ConfirmDeleteProductLine'), 'confirm_deleteline', '', 0, 1);
    }

    // Confirmation to assign myself
    if ($action == 'set_myself_assigned_user')
    {
        $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('RequestManagerAssignMySelf'), $langs->trans('RequestManagerConfirmAssignMySelf', $object->ref), 'confirm_set_myself_assigned_user', '', 0, 1);
    }

    // Confirm force principal company
    if ($action == 'force_principal_company') {
        $formquestion = array();
        foreach ($force_principal_company_params as $k => $v) {
            $formquestion[] = array('type' => 'hidden', 'name' => $k, 'value' => $v);
        }

        $societe = new Societe($db);
        $societe->fetch(!empty($force_principal_company_params['socid']) ? $force_principal_company_params['socid'] : $object->socid);
        $formquestion[] = array('type' => 'other', 'label' => $langs->trans('RequestManagerThirdPartyPrincipal'), 'value' => $societe->getNomUrl(1));
        $societe->fetch(!empty($force_principal_company_params['socid_benefactor']) ? $force_principal_company_params['socid_benefactor'] : $object->socid_benefactor);
        $formquestion[] = array('type' => 'other', 'label' => $langs->trans('RequestManagerThirdPartyBenefactor'), 'value' => $societe->getNomUrl(1));

        $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('RequestManagerForcePrincipalCompany'), $langs->trans('RequestManagerConfirmForcePrincipalCompany'), 'confirm_force_principal_company', $formquestion, 0, 1);
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
    if ($object->fk_parent > 0) {
        $object->fetch_parent();
        $morehtmlref.='<br>'.$langs->trans('RequestManagerParentRequest') . ' : ' . $object->parent->getNomUrl(1, 'parent_path');
    }
    $morehtmlref.='</div>';

	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

    // Hook
    $parameters = array();
    $reshook = $hookmanager->executeHooks('addNextBannerTab', $parameters, $object, $action); // Note that $action and $object may have been modified by hook

    print '<div class="fichecenter">';
    print '<div class="fichehalfleft">';
    print '<div class="underbanner clearboth"></div>';

	print '<table class="border tableforfield" width="100%">';

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
		print '<form name="edittype" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
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
        print '<form name="editcategory" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
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
	//if ($action != 'edit_label' && $user->rights->requestmanager->creer && ($object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS || $object->statut_type == RequestManager::STATUS_TYPE_INITIAL))
	if ($action != 'edit_label' && $user->rights->requestmanager->creer)
		print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=edit_label&id=' . $object->id . '">' . img_edit($langs->trans('RequestManagerSetLabel'), 1) . '</a></td>';
	print '</tr></table>';
	print '</td><td>';
	if ($action == 'edit_label' && $user->rights->requestmanager->creer && ($object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS || $object->statut_type == RequestManager::STATUS_TYPE_INITIAL)) {
		print '<form name="editlabel" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
		print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
		print '<input type="hidden" name="action" value="set_label">';
        print '<input type="text" name="label" value="'.dol_escape_htmltag($object->label).'">';
		print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
		print '</form>';
	} else {
        print $object->label;
	}
    print '</td></tr>';

    // ThirdParty Origin
    print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('RequestManagerThirdPartyOrigin');
	print '</td>';
	if ($action != 'edit_thirdparty_origin' && $user->rights->requestmanager->creer && ($object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS || $object->statut_type == RequestManager::STATUS_TYPE_INITIAL))
		print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=edit_thirdparty_origin&id=' . $object->id . '">' . img_edit($langs->trans('RequestManagerSetThirdPartyOrigin'), 1) . '</a></td>';
	print '</tr></table>';
	print '</td><td>';
	if ($action == 'edit_thirdparty_origin' && $user->rights->requestmanager->creer && ($object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS || $object->statut_type == RequestManager::STATUS_TYPE_INITIAL)) {
		print '<form name="editthirdpartyorigin" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
		print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
		print '<input type="hidden" name="action" value="set_thirdparty_origin">';
        print $form->select_company($object->socid_origin, 'socid_origin', '(s.client = 1 OR s.client = 2 OR s.client = 3) AND status=1', 'SelectThirdParty', 0, 0, null, 0, 'minwidth300');
		print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
		print '</form>';
	} else {
        print $object->thirdparty_origin->getNomUrl(1);
	}
    print '</td></tr>';

    if (!empty($conf->companyrelationships->enabled)) {
        // ThirdParty Bill
        print '<tr><td>';
        print '<table class="nobordernopadding" width="100%"><tr><td>';
        print $langs->trans('RequestManagerThirdPartyPrincipal');
        print '</td>';
        if ($action != 'edit_thirdparty' && $user->rights->requestmanager->creer && ($object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS || $object->statut_type == RequestManager::STATUS_TYPE_INITIAL))
            print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=edit_thirdparty&id=' . $object->id . '">' . img_edit($langs->trans('RequestManagerSetThirdPartyBill'), 1) . '</a></td>';
        print '</tr></table>';
        print '</td><td>';
        if ($action == 'edit_thirdparty' && $user->rights->requestmanager->creer && ($object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS || $object->statut_type == RequestManager::STATUS_TYPE_INITIAL)) {
            print '<form name="editthirdparty" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
            print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
            print '<input type="hidden" name="action" value="set_thirdparty">';
            print $form->select_company($object->socid, 'socid', '(s.client = 1 OR s.client = 2 OR s.client = 3) AND status=1', 'SelectThirdParty', 0, 0, null, 0, 'minwidth300');
            print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
            print '</form>';
        } else {
            print $object->thirdparty->getNomUrl(1);
        }
        print '</td></tr>';

        // ThirdParty Benefactor
        print '<tr><td>';
        print '<table class="nobordernopadding" width="100%"><tr><td>';
        print $langs->trans('RequestManagerThirdPartyBenefactor');
        print '</td>';
        if ($action != 'edit_thirdparty_benefactor' && $user->rights->requestmanager->creer && ($object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS || $object->statut_type == RequestManager::STATUS_TYPE_INITIAL))
            print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=edit_thirdparty_benefactor&id=' . $object->id . '">' . img_edit($langs->trans('RequestManagerSetThirdPartyBenefactor'), 1) . '</a></td>';
        print '</tr></table>';
        print '</td><td>';
        if ($action == 'edit_thirdparty_benefactor' && $user->rights->requestmanager->creer && ($object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS || $object->statut_type == RequestManager::STATUS_TYPE_INITIAL)) {
            print '<form name="editthirdpartybenefactor" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
            print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
            print '<input type="hidden" name="action" value="set_thirdparty_benefactor">';
            print $form->select_company($object->socid_benefactor, 'socid_benefactor', '(s.client = 1 OR s.client = 2 OR s.client = 3) AND status=1', 'SelectThirdParty', 0, 0, null, 0, 'minwidth300');
            print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
            print '</form>';
        } else {
            print $object->thirdparty_benefactor->getNomUrl(1);
        }
        print '</td></tr>';

        // ThirdParty Watcher
        print '<tr><td>';
        print '<table class="nobordernopadding" width="100%"><tr><td>';
        print $langs->trans('RequestManagerThirdPartyWatcher');
        print '</td>';
        if ($action != 'edit_thirdparty_watcher' && $user->rights->requestmanager->creer && ($object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS || $object->statut_type == RequestManager::STATUS_TYPE_INITIAL))
            print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=edit_thirdparty_watcher&id=' . $object->id . '">' . img_edit($langs->trans('RequestManagerSetThirdPartyWatcher'), 1) . '</a></td>';
        print '</tr></table>';
        print '</td><td>';
        if ($action == 'edit_thirdparty_watcher' && $user->rights->requestmanager->creer && ($object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS || $object->statut_type == RequestManager::STATUS_TYPE_INITIAL)) {
            print '<form name="editthirdpartywatcher" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
            print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
            print '<input type="hidden" name="action" value="set_thirdparty_watcher">';
            print $form->select_company($object->socid_watcher, 'socid_watcher', '(s.client = 1 OR s.client = 2 OR s.client = 3) AND status=1', 'SelectThirdParty', 0, 0, null, 0, 'minwidth300');
            print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
            print '</form>';
        } else {
            if ($object->thirdparty_watcher) {
                print $object->thirdparty_watcher->getNomUrl(1);
            }
        }
        print '</td></tr>';

        // Availability of the request for the thirdparty principal
        print '<tr><td>';
        print '<table class="nobordernopadding" width="100%"><tr><td>';
        print $langs->trans('RequestManagerAvailabilityForThirdPartyPrincipal');
        print '</td>';
        if ($action != 'edit_availability_for_thirdparty_principal' && $user->rights->requestmanager->creer)
            print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=edit_availability_for_thirdparty_principal&id=' . $object->id . '">' . img_edit($langs->trans('RequestManagerSetAvailabilityForThirdPartyPrincipal'), 1) . '</a></td>';
        print '</tr></table>';
        print '</td><td>';
        if ($action == 'edit_availability_for_thirdparty_principal' && $user->rights->requestmanager->creer) {
            print '<form name="editavailability_for_thirdparty_principal" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
            print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
            print '<input type="hidden" name="action" value="set_availability_for_thirdparty_principal">';
            print '<input type="checkbox" name="availability_for_thirdparty_principal" value="1"' . ($object->availability_for_thirdparty_principal ? ' checked' : '') . ' />';
            print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
            print '</form>';
        } else {
            print yn($object->availability_for_thirdparty_principal);
        }
        print '</td></tr>';

        // Availability of the request for the thirdparty benefactor
        print '<tr><td>';
        print '<table class="nobordernopadding" width="100%"><tr><td>';
        print $langs->trans('RequestManagerAvailabilityForThirdPartyBenefactor');
        print '</td>';
        if ($action != 'edit_availability_for_thirdparty_benefactor' && $user->rights->requestmanager->creer)
            print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=edit_availability_for_thirdparty_benefactor&id=' . $object->id . '">' . img_edit($langs->trans('RequestManagerSetAvailabilityForThirdPartyBenefactor'), 1) . '</a></td>';
        print '</tr></table>';
        print '</td><td>';
        if ($action == 'edit_availability_for_thirdparty_benefactor' && $user->rights->requestmanager->creer) {
            print '<form name="editavailability_for_thirdparty_benefactor" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
            print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
            print '<input type="hidden" name="action" value="set_availability_for_thirdparty_benefactor">';
            print '<input type="checkbox" name="availability_for_thirdparty_benefactor" value="1"' . ($object->availability_for_thirdparty_benefactor ? ' checked' : '') . ' />';
            print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
            print '</form>';
        } else {
            print yn($object->availability_for_thirdparty_benefactor);
        }
        print '</td></tr>';

        // Availability of the request for the thirdparty watcher
        print '<tr><td>';
        print '<table class="nobordernopadding" width="100%"><tr><td>';
        print $langs->trans('RequestManagerAvailabilityForThirdPartyWatcher');
        print '</td>';
        if ($action != 'edit_availability_for_thirdparty_watcher' && $user->rights->requestmanager->creer)
            print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=edit_availability_for_thirdparty_watcher&id=' . $object->id . '">' . img_edit($langs->trans('RequestManagerSetAvailabilityForThirdPartyWatcher'), 1) . '</a></td>';
        print '</tr></table>';
        print '</td><td>';
        if ($action == 'edit_availability_for_thirdparty_watcher' && $user->rights->requestmanager->creer) {
            print '<form name="editavailability_for_thirdparty_watcher" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
            print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
            print '<input type="hidden" name="action" value="set_availability_for_thirdparty_watcher">';
            print '<input type="checkbox" name="availability_for_thirdparty_watcher" value="1"' . ($object->availability_for_thirdparty_watcher ? ' checked' : '') . ' />';
            print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
            print '</form>';
        } else {
            print yn($object->availability_for_thirdparty_watcher);
        }
        print '</td></tr>';
    }

    // Source
    print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('RequestManagerSource');
	print '</td>';
	if ($action != 'edit_source' && $user->rights->requestmanager->creer && ($object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS || $object->statut_type == RequestManager::STATUS_TYPE_INITIAL))
		print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=edit_source&id=' . $object->id . '">' . img_edit($langs->trans('RequestManagerSetSource'), 1) . '</a></td>';
	print '</tr></table>';
	print '</td><td>';
	if ($action == 'edit_source' && $user->rights->requestmanager->creer && ($object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS || $object->statut_type == RequestManager::STATUS_TYPE_INITIAL)) {
		print '<form name="editsource" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
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
	if ($action != 'edit_urgency' && $user->rights->requestmanager->creer && ($object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS || $object->statut_type == RequestManager::STATUS_TYPE_INITIAL))
		print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=edit_urgency&id=' . $object->id . '">' . img_edit($langs->trans('RequestManagerSetUrgency'), 1) . '</a></td>';
	print '</tr></table>';
	print '</td>';
	if ($action == 'edit_urgency' && $user->rights->requestmanager->creer && ($object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS || $object->statut_type == RequestManager::STATUS_TYPE_INITIAL)) {
        print '<td>';
		print '<form name="editurgency" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
		print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
		print '<input type="hidden" name="action" value="set_urgency">';
        print $formrequestmanager->select_urgency($object->fk_urgency, 'urgency', 1, 0, array(), 0, 0, 'minwidth300');
		print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
		print '</form>';
        print '</td>';
	} else {
        $color = $object->getColorUrgency();
        print '<td' . (empty($color) ? '' : ' style="background-color: ' . $color . ';"') . '>';
        print $object->getLibUrgency();
        print '</td>';
    }
    print '</tr>';

    // Impact
    print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('RequestManagerImpact');
	print '</td>';
	if ($action != 'edit_impact' && $user->rights->requestmanager->creer && ($object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS || $object->statut_type == RequestManager::STATUS_TYPE_INITIAL))
		print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=edit_impact&id=' . $object->id . '">' . img_edit($langs->trans('RequestManagerSetImpact'), 1) . '</a></td>';
	print '</tr></table>';
	print '</td><td>';
	if ($action == 'edit_impact' && $user->rights->requestmanager->creer && ($object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS || $object->statut_type == RequestManager::STATUS_TYPE_INITIAL)) {
		print '<form name="editimpact" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
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
	if ($action != 'edit_priority' && $user->rights->requestmanager->creer && ($object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS || $object->statut_type == RequestManager::STATUS_TYPE_INITIAL))
		print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=edit_priority&id=' . $object->id . '">' . img_edit($langs->trans('RequestManagerSetPriority'), 1) . '</a></td>';
	print '</tr></table>';
	print '</td><td>';
	if ($action == 'edit_priority' && $user->rights->requestmanager->creer && ($object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS || $object->statut_type == RequestManager::STATUS_TYPE_INITIAL)) {
		print '<form name="editpriority" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
		print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
		print '<input type="hidden" name="action" value="set_priority">';
        print $formrequestmanager->select_priority($object->fk_priority, 'priority', 1, 0, array(), 0, 0, 'minwidth300');
		print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
		print '</form>';
	} else {
        print $object->getLibPriority();
	}
    print '</td></tr>';

    // Date Creation
    print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('DateCreation');
	print '</td>';
	if ($action != 'edit_date_creation' && $user->rights->requestmanager->creer && $object->statut_type != RequestManager::STATUS_TYPE_CLOSED)
		print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=edit_date_creation&id=' . $object->id . '">' . img_edit($langs->trans('RequestManagerSetDateCreation'), 1) . '</a></td>';
	print '</tr></table>';
	print '</td><td>';
	if ($action == 'edit_date_creation' && $user->rights->requestmanager->creer && $object->statut_type != RequestManager::STATUS_TYPE_CLOSED) {
		print '<form name="editdatecreation" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
		print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
		print '<input type="hidden" name="action" value="set_date_creation">';
        $form->select_date($object->date_creation, 'date_creation_', 1, 1, 1, '', 1);
		print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
		print '</form>';
	} else {
        print $object->date_creation > 0 ? dol_print_date($object->date_creation, 'dayhour') : '';
	}
    print '</td></tr>';

    // Duration
    print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('RequestManagerDuration');
	print '</td>';
	if ($action != 'edit_duration' && $user->rights->requestmanager->creer && $object->statut_type != RequestManager::STATUS_TYPE_CLOSED)
		print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=edit_duration&id=' . $object->id . '">' . img_edit($langs->trans('RequestManagerSetDuration'), 1) . '</a></td>';
	print '</tr></table>';
	print '</td><td>';
	if ($action == 'edit_duration' && $user->rights->requestmanager->creer && $object->statut_type != RequestManager::STATUS_TYPE_CLOSED) {
		print '<form name="editduration" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
		print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
		print '<input type="hidden" name="action" value="set_duration">';
        $duration_infos = requestmanager_get_duration($object->duration);
        print '<input type="text" size="3" name="duration_day" value="'.$duration_infos['days'].'"> ' . $langs->trans('Days');
        print ' <input type="text" size="3" name="duration_hour" value="'.$duration_infos['hours'].'"> ' . $langs->trans('Hours');
        print ' <input type="text" size="3" name="duration_min" value="'.$duration_infos['minutes'].'"> ' . $langs->trans('Minutes');
		print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
		print '</form>';
	} else {
        print requestmanager_print_duration($object->duration);
    }
    print '</td></tr>';

    // Date Operation
    print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('RequestManagerOperation');
	print '</td>';
	if ($action != 'edit_date_operation' && $user->rights->requestmanager->creer && $object->statut_type != RequestManager::STATUS_TYPE_CLOSED)
		print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=edit_date_operation&id=' . $object->id . '">' . img_edit($langs->trans('RequestManagerSetOperation'), 1) . '</a></td>';
	print '</tr></table>';
	print '</td><td>';
	if ($action == 'edit_date_operation' && $user->rights->requestmanager->creer && $object->statut_type != RequestManager::STATUS_TYPE_CLOSED) {
		print '<form name="editdateoperation" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
		print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
		print '<input type="hidden" name="action" value="set_date_operation">';
        $form->select_date($object->date_operation, 'operation_', 1, 1, 1, '', 1);
        print ' <input type="checkbox" name="recalculate_date_deadline" value="1" checked="checked"> '.$langs->trans('RequestManagerRecalculateDeadLineDate');
		print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
		print '</form>';
	} else {
        print $object->date_operation > 0 ? dol_print_date($object->date_operation, 'dayhour') : '';
	}
    print '</td></tr>';

    // Date Deadline
    print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('RequestManagerDeadline');
	print '</td>';
	if ($action != 'edit_date_deadline' && $user->rights->requestmanager->creer && $object->statut_type != RequestManager::STATUS_TYPE_CLOSED)
		print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=edit_date_deadline&id=' . $object->id . '">' . img_edit($langs->trans('RequestManagerSetDeadline'), 1) . '</a></td>';
	print '</tr></table>';
	print '</td><td>';
	if ($action == 'edit_date_deadline' && $user->rights->requestmanager->creer && $object->statut_type != RequestManager::STATUS_TYPE_CLOSED) {
		print '<form name="editdatedeadline" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
		print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
		print '<input type="hidden" name="action" value="set_date_deadline">';
        $form->select_date($object->date_deadline, 'deadline_', 1, 1, 1, '', 1);
		print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
		print '</form>';
	} else {
        // picto warning for deadline
        $pictoWarning = '';
        if ($object->date_deadline) {
            $tmsDeadLine = strtotime($object->date_deadline);
            if ($tmsDeadLine < $now) {
                // alert time is up
                $pictoWarning = ' ' . img_warning($langs->trans("Late"));
            }
        }
        print $object->date_deadline > 0 ? (dol_print_date($object->date_deadline, 'dayhour') . $pictoWarning) : '';
	}
    print '</td></tr>';

    // Assigned usergroups
    print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('RequestManagerAssignedUserGroups');
	print '</td>';
	if ($action != 'edit_assigned_usergroups' && $user->rights->requestmanager->creer && $object->statut_type != RequestManager::STATUS_TYPE_CLOSED)
		print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=edit_assigned_usergroups&id=' . $object->id . '">' . img_edit($langs->trans('RequestManagerSetAssignedUserGroups'), 1) . '</a></td>';
	print '</tr></table>';
	print '</td><td>';
	if ($action == 'edit_assigned_usergroups' && $user->rights->requestmanager->creer && $object->statut_type != RequestManager::STATUS_TYPE_CLOSED) {
		print '<form name="editassignedusergroups" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
		print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
		print '<input type="hidden" name="action" value="set_assigned_usergroups">';
        print $formrequestmanager->multiselect_dolgroups($object->assigned_usergroup_ids, 'assigned_usergroups');
		print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
		print '</form>';
	} else {
        $toprint = array();
        foreach ($object->assigned_usergroup_ids as $assigned_usergroup_id) {
            $usergroup_static->fetch($assigned_usergroup_id);
            $toprint[] = $usergroup_static->getFullName($langs);
        }
        print implode(', ', $toprint);
	}
    print '</td></tr>';

    // Assigned users
    print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('RequestManagerAssignedUsers');
	print '</td>';
	if ($action != 'edit_assigned_users' && $user->rights->requestmanager->creer && $object->statut_type != RequestManager::STATUS_TYPE_CLOSED)
		print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=edit_assigned_users&id=' . $object->id . '">' . img_edit($langs->trans('RequestManagerSetAssignedUsers'), 1) . '</a></td>';
	print '</tr></table>';
	print '</td><td>';
	if ($action == 'edit_assigned_users' && $user->rights->requestmanager->creer && $object->statut_type != RequestManager::STATUS_TYPE_CLOSED) {
		print '<form name="editassignedusers" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
		print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
		print '<input type="hidden" name="action" value="set_assigned_users">';
        print $formrequestmanager->multiselect_dolusers($object->assigned_user_ids,'assigned_users', null, 0, '', '', 0, 0, 0, '', 0, '', '', 1, 0);
		print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
		print '</form>';
	} else {
        $toprint = array();
        foreach ($object->assigned_user_ids as $assigned_user_id) {
            $user_static->fetch($assigned_user_id);
            $toprint[] = $user_static->getNomUrl(1);
        }
        print implode(', ', $toprint);
        if (!in_array($user->id, $object->assigned_user_ids) && $user->rights->requestmanager->creer && $object->statut_type != RequestManager::STATUS_TYPE_CLOSED) {
	    print '&nbsp;&nbsp;<a href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=set_myself_assigned_user" class="button" style="color: #3c3c3c;" title="' . $langs->trans("RequestManagerAssignToMe") . '">' . $langs->trans("RequestManagerAssignToMe") .'</a>';
        }
	}
    print '</td></tr>';

	if (!empty($conf->global->REQUESTMANAGER_NOTIFICATION_ASSIGNED_BY_EMAIL)) {
        // Assigned Notification
        print '<tr><td>';
        print '<table class="nobordernopadding" width="100%"><tr><td>';
        print $langs->trans('RequestManagerAssignedNotification');
        print '</td>';
        if ($action != 'edit_assigned_notification' && $user->rights->requestmanager->creer && $object->statut_type != RequestManager::STATUS_TYPE_CLOSED)
            print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=edit_assigned_notification&id=' . $object->id . '">' . img_edit($langs->trans('RequestManagerSetAssignedNotification'), 1) . '</a></td>';
        print '</tr></table>';
        print '</td><td>';
        if ($action == 'edit_assigned_notification' && $user->rights->requestmanager->creer && $object->statut_type != RequestManager::STATUS_TYPE_CLOSED) {
            print '<form name="editassignednotification" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
            print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
            print '<input type="hidden" name="action" value="set_assigned_notification">';
            print '<input type="checkbox" name="assigned_notification" value="1"' . ($object->notify_assigned_by_email ? ' checked' : '') . ' />';
            print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
            print '</form>';
        } else {
            print yn($object->notify_assigned_by_email);
        }
        print '</td></tr>';
    }

    // Other attributes
    $object->save_status = $object->statut;
    if ($object->statut_type != RequestManager::STATUS_TYPE_CLOSED) $object->statut = 0;
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';
    $object->statut = $object->save_status;

    // Categories
    if ($conf->categorie->enabled) {
        print '<tr><td>';
        print '<table class="nobordernopadding" width="100%"><tr><td>';
        print $langs->trans('RequestManagerTags');
        print '</td>';
        if ($action != 'edit_categories' && $user->rights->requestmanager->creer && $object->statut_type != RequestManager::STATUS_TYPE_CLOSED)
            print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=edit_categories&id=' . $object->id . '">' . img_edit($langs->trans('RequestManagerSetCategories'), 1) . '</a></td>';
        print '</tr></table>';
        print '</td><td>';
        if ($action == 'edit_categories' && $user->rights->requestmanager->creer && $object->statut_type != RequestManager::STATUS_TYPE_CLOSED) {
            print '<form name="editcategories" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
            print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
            print '<input type="hidden" name="action" value="set_categories">';
            print $formrequestmanager->showCategories($object->id, CategorieRequestManager::TYPE_REQUESTMANAGER, 0, TRUE);
            print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
            print '</form>';
        } else {
            print $formrequestmanager->showCategories($object->id, CategorieRequestManager::TYPE_REQUESTMANAGER, 1);
        }
        print '</td></tr>';
    }

    // Reason for resolution
    if ($object->fk_reason_resolution > 0) {
        print '<tr><td>'.$langs->trans('RequestManagerReasonResolution').'</td><td>';
        print $object->getLibReasonResolution();
        print '</td></tr>';
    }

    // Details reason for resolution
    if (!empty($object->reason_resolution_details)) {
        print '<tr><td>'.$langs->trans('RequestManagerReasonResolutionDetails').'</td><td>';
        print $object->reason_resolution_details;
        print '</td></tr>';
    }

    // Description
    print '<tr><td colspan="2">';
    if ($action == 'edit_description' && $user->rights->requestmanager->creer && ($object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS || $object->statut_type == RequestManager::STATUS_TYPE_INITIAL)) {
        print '<form name="editdescription" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
        print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
        print '<input type="hidden" name="action" value="set_description">';
    }
	print '<table class="nobordernopadding" width="100%"><tr><td class="titlefield">';
    print '<table class="nobordernopadding" width="100%"><tr><td>';
    print $langs->trans('RequestManagerDescription');
    print '</td>';
    if ($action != 'edit_description' && $user->rights->requestmanager->creer && ($object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS || $object->statut_type == RequestManager::STATUS_TYPE_INITIAL))
        print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=edit_description&id=' . $object->id . '">' . img_edit($langs->trans('RequestManagerSetDescription'), 1) . '</a></td>';
    print '</tr></table>';
    print '</td><td>';
    if ($action == 'edit_description' && $user->rights->requestmanager->creer && ($object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS || $object->statut_type == RequestManager::STATUS_TYPE_INITIAL)) {
        print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
    }
    print '</td></tr>';
    print '<tr><td colspan="2">';
	if ($action == 'edit_description' && $user->rights->requestmanager->creer && ($object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS || $object->statut_type == RequestManager::STATUS_TYPE_INITIAL)) {
        $doleditor = new DolEditor('description', $object->description, '', 200, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, '90%');
        print $doleditor->Create(1);
	} else {
        print $object->description;
	}
    print '</td></tr>';
    print '</table>';
    if ($action == 'edit_description' && $user->rights->requestmanager->creer && ($object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS || $object->statut_type == RequestManager::STATUS_TYPE_INITIAL)) {
        print '</form>';
    }
    print '</td></tr>';

	print '</table>';

    print '</div>';
    print '<div class="fichehalfright"><div class="ficheaddleft">';
    print '<div class="underbanner clearboth"></div>';

    print '<table class="border centpercent">';

    // Requesters
    print '<tr><td align="center">';
	print $langs->trans('RequestManagerRequesterContacts');
    $notificationUrl = "#";
	if ($user->rights->requestmanager->creer && $object->statut_type != RequestManager::STATUS_TYPE_CLOSED) {
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
    if ($user->rights->requestmanager->creer && $object->statut_type != RequestManager::STATUS_TYPE_CLOSED) {
        // form to add requester contact
        $formrequestmanager->form_add_contact($object, RequestManager::CONTACT_TYPE_ID_REQUEST);
    }
    $object->show_contact_list(RequestManager::CONTACT_TYPE_ID_REQUEST);
    print '</td></tr>';

    // Watchers
    print '<tr><td align="center">';
	print $langs->trans('RequestManagerWatcherContacts');
    $notificationUrl = "#";
    if ($user->rights->requestmanager->creer && $object->statut_type != RequestManager::STATUS_TYPE_CLOSED) {
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
    if ($user->rights->requestmanager->creer && $object->statut_type != RequestManager::STATUS_TYPE_CLOSED) {
        // form to add requester contact
        $formrequestmanager->form_add_contact($object, RequestManager::CONTACT_TYPE_ID_WATCHER);
    }
    $object->show_contact_list(RequestManager::CONTACT_TYPE_ID_WATCHER);
    print '</td></tr>';

    // Linked Objects
    if ($conf->global->REQUESTMANAGER_POSITION_BLOC_OBJECT_LINKED == 'bottom') {
        print '<tr><td align="center">';
        print '<a href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '#linked_objects_list">' . $langs->trans('RelatedObjects') . '</a>';
        if ($conf->global->REQUESTMANAGER_POSITION_LINK_NEW_OBJECT_LINKED == 'top') {
            $linktoelem = $form->showLinkToObjectBlock($object);
            print ' ( ' . $linktoelem . ' )';
        }
        print '</td></tr>';
        $object->fetchObjectLinked();
        $element_infos = requestmanager_get_elements_infos();
        $linked_objects_list = array();
        foreach ($object->linkedObjects as $objecttype => $objects) {
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

            $linked_objects_list[] = img_picto($label, $icon) . ' ' . $label . ' (' . count($objects) . ')';
        }
        print '<tr><td>' . implode(', ', $linked_objects_list) . '</td></tr>';
    }

	print '</table>';

    // Linked Objects
    if ($conf->global->REQUESTMANAGER_POSITION_BLOC_OBJECT_LINKED != 'bottom') {
        // Show links to link elements
        print '<div id="linked_objects_list">';
        $linktoelem = $form->showLinkToObjectBlock($object);
        $somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);
        print '</div>';
    }

    // Children Request
    $formrequestmanager->showChildrenRequestBlock($object);

    // Children Request linked objects
    $formrequestmanager->showChildrenRequestLinkedObjectsBlock($object);

    print '</div>';
    print '</div></div>';
    print '<div class="clearboth"></div>';

	// Specifics Information
    $parameters = array();
    $reshook = $hookmanager->executeHooks('addMoreSpecificsInformation', $parameters, $object, $action); // Note that $action and $object may have been
    if (!empty($hookmanager->resArray)) {
        ksort($hookmanager->resArray);
        print implode("\n", $hookmanager->resArray);
    }

    /*
    * Lines
    */
    $langs->load('bills');
    $result = $object->getLinesArray();
    print '<br />';
    print '<form name="addproduct" id="addproduct" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . (($action != 'editline') ? '#addline' : '#line_' . GETPOST('lineid')) . '" method="POST">';
    print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
    print '<input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateline') . '">';
    print '<input type="hidden" name="mode" value="">';
    print '<input type="hidden" name="id" value="' . $object->id . '">';

    if (!empty($conf->use_javascript_ajax) && ($object->statut_type == RequestManager::STATUS_TYPE_INITIAL || $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS)) {
        include DOL_DOCUMENT_ROOT . '/core/tpl/ajaxrow.tpl.php';
    }

    $numlines = count($object->lines);

    print '<div class="fichecenter">';
    print '<div class="underbanner clearboth"></div><br>';

    print load_fiche_titre('<span class="rm_lines_block_title_label" style="font-weight: bolder !important; font-size: medium !important;">' . $langs->trans("RequestManagerProductLines") . '&nbsp;<img class="rm_lines_block_title_icon" src=""></img></span>', '', '', 0, 'rm_lines_block_title');
    print '<div id="rm_lines_block_content"' . (empty($numlines) ? ' style="display: none;"' : '') . '>';

    print '<div class="div-table-responsive">';
    print '<table id="tablelines" class="noborder noshadow" width="100%">';
    // Show object lines
    if (!empty($object->lines)) {
        // probleme de statut initial qui vaut 1 et non 0 (et ne permet pas de modifier ou supprimer une ligne)
        $object->save_status = $object->statut;
        if ($user->rights->requestmanager->creer && ($object->statut_type == RequestManager::STATUS_TYPE_INITIAL || $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS)) {
            $object->statut = 0;
        }
        $object->printObjectLines($action, $mysoc,  $object->thirdparty, $lineid, 1);
        $object->statut = $object->save_status;
    }
    // form to add new line
    if ($user->rights->requestmanager->creer && ($object->statut_type == RequestManager::STATUS_TYPE_INITIAL || $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS))
    {
        if ($action != 'editline')
        {
            $var = true;

            // Add free products/services
            $object->formAddObjectLine(1, $mysoc,  $object->thirdparty);

            $parameters = array();
            $reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
        }
    }
    print '</table>';
    print '</div>';

    print '</div>';

    $show_label = json_encode($langs->trans('Show'));
    $hide_label = json_encode($langs->trans('Hide'));
    $arrow_up = json_encode(img_picto('', 'sort_asc', '', false, 1));
    $arrow_down = json_encode(img_picto('', 'sort_desc', '', false, 1));
    print <<<SCRIPT
    <script type="text/javascript" language="javascript">
        $(document).ready(function () {
            var ei_count_block_title = $("#rm_lines_block_title");
            var ei_count_block_title_div = $('#rm_lines_block_title div.titre');
            var ei_count_block_title_icon = $(".rm_lines_block_title_icon");
            var ei_count_block_content = $("#rm_lines_block_content");

            ei_count_block_title_div.css('cursor', 'pointer');
            ei_update_title_icon();
            ei_count_block_title.on('click', function() {
                ei_count_block_content.toggle();
                ei_update_title_icon();
            });

            function ei_update_title_icon() {
                if (ei_count_block_content.is(':visible')) {
                    ei_count_block_title_div.attr('title', $hide_label)
                    ei_count_block_title_icon.attr('src', $arrow_down);
                } else {
                    ei_count_block_title_div.attr('title', $show_label)
                    ei_count_block_title_icon.attr('src', $arrow_up);
                }
            }
        });
    </script>
SCRIPT;

    print '</div>';
    print '<div class="clearboth"></div>';

    print "</form>\n";

    // Get current status infos
    $requestManagerStatusDictionary = Dictionary::getDictionary($db, 'requestmanager', 'requestmanagerstatus');
    $requestManagerStatusDictionaryLine = $requestManagerStatusDictionary->getNewDictionaryLine();
    $requestManagerStatusDictionaryLine->fetch($object->statut);

    /*
	 * Boutons status
	 */
    if ($user->rights->requestmanager->creer) {
        print '<div class="tabsStatusAction">';

        // Get group of user
        $user_groups = $usergroup_static->listGroupsForUser($user->id);
        $user_groups = is_array($user_groups) ? array_keys($user_groups) : array();

        // Get count children request by status type
        $children_count = $object->getCountChildrenRequestByStatusType();

        // Show status
		// We hide next statut only if new child requests are on this request, and that one (or more) request is not closed and that current status of this request bloc request process
		// We hide previous statut only if current statut is closed type
        if (true) {
            // Get all status of the request type
            $requestManagerStatusDictionary->fetch_lines(1, array('request_type' => array($object->fk_type)));
			//$requestManagerStatusDictionary->fetch_lines(1, array(''=>array()));
            // Parse all status and add status in previous or next groups
            $previousStatusButton = array();
            $nextStatusButton = array();
            $next_status = explode(',', $requestManagerStatusDictionaryLine->fields['next_status']);
            foreach ($requestManagerStatusDictionary->lines as $s) {
                $not_authorized_user = !empty($s->fields['authorized_user']) ? !in_array($user->id, explode(',', $s->fields['authorized_user'])) : false;
                $not_authorized_usergroup = false;
                if (!empty($s->fields['authorized_usergroup'])) {
                    $not_authorized_usergroup = true;
                    $authorized_usergroup = explode(',', $s->fields['authorized_usergroup']);
                    foreach ($authorized_usergroup as $group_id) {
                        if (in_array($group_id, $user_groups)) {
                            $not_authorized_usergroup = false;
                            break;
                        }
                    }
                }
                if ($not_authorized_user || $not_authorized_usergroup) continue;

                $out = '<div class="inline-block divButAction noMarginBottom">';
                $options_url = '';
                if ($s->fields['type'] == RequestManager::STATUS_TYPE_INITIAL || $s->fields['type'] == RequestManager::STATUS_TYPE_IN_PROGRESS) {
                    $options_url = '&action=set_status&status=' . $s->id;
                } elseif ($s->fields['type'] == RequestManager::STATUS_TYPE_RESOLVED) {
                    $options_url = '&action=resolve';
                } elseif ($s->fields['type'] == RequestManager::STATUS_TYPE_CLOSED) {
                    $options_url = '&action=close';
                }
                $out .= '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . $options_url . '">';
                $out .= $object->LibStatut($s->id, 10);
                $out .= '</a>';
                $out .= '</div>';

                $sort_key = $s->fields['type'] . '_' . $s->id;

                // Add in previous group
                $thisNextStatus = explode(',', $s->fields['next_status']);
                if ($object->statut_type != RequestManager::STATUS_TYPE_RESOLVED && $object->statut_type != RequestManager::STATUS_TYPE_CLOSED &&
                    in_array($object->statut, $thisNextStatus) && ($s->fields['type'] == RequestManager::STATUS_TYPE_IN_PROGRESS || $s->fields['type'] == RequestManager::STATUS_TYPE_INITIAL)) {
                    $previousStatusButton[$sort_key] = $out;
                }

                // Add in next group
                if (in_array($s->id, $next_status) && (empty($conf->global->REQUESTMANAGER_AUTO_CLOSE_REQUEST) || $s->fields['type'] != RequestManager::STATUS_TYPE_CLOSED)) {
                    $nextStatusButton[$sort_key] = $out;
                }
            }

            // Show previous status
			if($object->statut_type != RequestManager::STATUS_TYPE_CLOSED)
			{
            print '<div class="tabsStatusActionPrevious">';
            ksort($previousStatusButton);
            print implode('', $previousStatusButton);
            print '</div>';
			}
            // Show next status
			if(!empty($requestManagerStatusDictionaryLine->fields['do_not_bloc_process']) || ($children_count[RequestManager::STATUS_TYPE_INITIAL] + $children_count[RequestManager::STATUS_TYPE_IN_PROGRESS] + $children_count[RequestManager::STATUS_TYPE_RESOLVED] == 0 && $children_count[RequestManager::STATUS_TYPE_CLOSED] >= 0))
			{
            print '<div class="tabsStatusActionNext">';
            ksort($nextStatusButton);
            print implode('', $nextStatusButton);
            print '</div>';
			}
        }

        print '</div>';
    }

    dol_fiche_end();

    //Select mail models is same action as premessage
	if (GETPOST('modelmessageselected', 'int') > 0) $action = 'premessage';

	if ($action != 'premessage') {
        /*
	 * Boutons Actions
	 */
        print '<div class="tabsAction">';

        $parameters = array('status_infos'=>$requestManagerStatusDictionaryLine);
        $reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been
        // modified by hook
        if (empty($reshook)) {
            if ($object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS) {
                $authorizedButtons = !empty($requestManagerStatusDictionaryLine->fields['authorized_buttons']) ? explode(',', $requestManagerStatusDictionaryLine->fields['authorized_buttons']) : array();

                // Add child request
                if ($user->rights->requestmanager->creer) {
                    dol_include_once('/advancedictionaries/class/dictionary.class.php');
                    $requestManagerRequestTypeDictionary = Dictionary::getDictionary($db, 'requestmanager', 'requestmanagerrequesttype');
                    $requestManagerRequestTypeDictionary->fetch_lines(1);

                    if (!empty($requestManagerStatusDictionaryLine->fields['new_request_type'])) {
                        foreach (explode(',', $requestManagerStatusDictionaryLine->fields['new_request_type']) as $request_type_id) {
                            print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=create_request_child&new_request_type='.$request_type_id.'">'
                                . $langs->trans('RequestManagerCreateRequestChild', $requestManagerRequestTypeDictionary->lines[$request_type_id]->fields['label']) . '</a></div>';
                        }
                    }
                }

                $backtopage = dol_buildpath('/requestmanager/card.php', 1) . '?id=' . $object->id;
                $commun_params = '&originid=' . $object->id . '&origin=' . $object->element . '&socid=' . $object->socid . '&backtopage=' . urlencode($backtopage);
                $benefactor_params = !empty($conf->companyrelationships->enabled) ? '&companyrelationships_fk_soc_benefactor=' . $object->socid_benefactor : '';
                $watcher_params = !empty($conf->companyrelationships->enabled) ? '&companyrelationships_fk_soc_watcher=' . $object->socid_watcher : '';
                if (!empty($conf->global->REQUESTMANAGER_TITLE_TO_REF_CUSTOMER_WHEN_CREATE_OTHER_ELEMENT)) {
                    $ref_client = '&ref_client=' . urlencode($object->label);
                } else {
                    $ref_client = '';
                }

                // Add proposal
                if (!empty($conf->propal->enabled) && (count($authorizedButtons) == 0 || in_array('create_propal', $authorizedButtons)) && !in_array('no_buttons', $authorizedButtons)) {
                    $langs->load("propal");
                    if ($user->rights->propal->creer) {
                        print '<div class="inline-block divButAction"><a class="butAction" href="' . DOL_URL_ROOT . '/comm/propal/card.php?action=create' . $commun_params . $benefactor_params . $watcher_params . $ref_client . '">' . $langs->trans("AddProp") . '</a></div>';
                    } else {
                        print '<div class="inline-block divButAction"><a class="butActionRefused" title="' . dol_escape_js($langs->trans("NotAllowed")) . '" href="#">' . $langs->trans("AddProp") . '</a></div>';
                    }
                }

                // Add order
                if (!empty($conf->commande->enabled) && (count($authorizedButtons) == 0 || in_array('create_order', $authorizedButtons)) && !in_array('no_buttons', $authorizedButtons)) {
                    $langs->load("orders");
                    if ($user->rights->commande->creer) {
                        print '<div class="inline-block divButAction"><a class="butAction" href="' . DOL_URL_ROOT . '/commande/card.php?action=create' . $commun_params . $benefactor_params . $watcher_params . $ref_client . '">' . $langs->trans("AddOrder") . '</a></div>';
                    } else {
                        print '<div class="inline-block divButAction"><a class="butActionRefused" title="' . dol_escape_js($langs->trans("NotAllowed")) . '" href="#">' . $langs->trans("AddOrder") . '</a></div>';
                    }
                }

                // Add invoice
                if ($user->socid == 0 && !empty($conf->facture->enabled) && (count($authorizedButtons) == 0 || in_array('create_invoice', $authorizedButtons)) && !in_array('no_buttons', $authorizedButtons)) {
                    $langs->load("bills");
                    $langs->load("compta");
                    if ($user->rights->facture->creer) {
                        $object->fetch_thirdparty();
                        if ($object->thirdparty->client != 0 && $object->thirdparty->client != 2) {
                            print '<div class="inline-block divButAction"><a class="butAction" href="' . DOL_URL_ROOT . '/compta/facture/card.php?action=create' . $commun_params . $benefactor_params . $watcher_params . '">' . $langs->trans("AddBill") . '</a></div>';
                        } else {
                            print '<div class="inline-block divButAction"><a class="butActionRefused" title="' . dol_escape_js($langs->trans("ThirdPartyMustBeEditAsCustomer")) . '" href="#">' . $langs->trans("AddBill") . '</a></div>';
                        }
                    } else {
                        print '<div class="inline-block divButAction"><a class="butActionRefused" title="' . dol_escape_js($langs->trans("NotAllowed")) . '" href="#">' . $langs->trans("AddBill") . '</a></div>';
                    }
                }

                // Add supplier proposal
                if (!empty($conf->supplier_proposal->enabled) && (count($authorizedButtons) == 0 || in_array('create_supplier_proposal', $authorizedButtons)) && !in_array('no_buttons', $authorizedButtons)) {
                    $langs->load("supplier_proposal");
                    if ($user->rights->supplier_proposal->creer) {
                        print '<div class="inline-block divButAction"><a class="butAction" href="' . DOL_URL_ROOT . '/supplier_proposal/card.php?action=create' . $commun_params . '">' . $langs->trans("AddSupplierProposal") . '</a></div>';
                    } else {
                        print '<div class="inline-block divButAction"><a class="butActionRefused" title="' . dol_escape_js($langs->trans("NotAllowed")) . '" href="#">' . $langs->trans("AddSupplierProposal") . '</a></div>';
                    }
                }

                // Add supplier order
                if (!empty($conf->fournisseur->enabled) && (count($authorizedButtons) == 0 || in_array('create_supplier_order', $authorizedButtons)) && !in_array('no_buttons', $authorizedButtons)) {
                    $langs->load("suppliers");
                    if ($user->rights->fournisseur->commande->creer) {
                        print '<div class="inline-block divButAction"><a class="butAction" href="' . DOL_URL_ROOT . '/fourn/commande/card.php?action=create' . $commun_params . '">' . $langs->trans("AddSupplierOrder") . '</a></div>';
                    } else {
                        print '<div class="inline-block divButAction"><a class="butActionRefused" title="' . dol_escape_js($langs->trans("NotAllowed")) . '" href="#">' . $langs->trans("AddSupplierOrder") . '</a></div>';
                    }
                }

                // Add supplier invoice
                if (!empty($conf->fournisseur->enabled) && (count($authorizedButtons) == 0 || in_array('create_supplier_invoice', $authorizedButtons)) && !in_array('no_buttons', $authorizedButtons)) {
                    $langs->load("suppliers");
                    if ($user->rights->fournisseur->facture->creer) {
                        print '<div class="inline-block divButAction"><a class="butAction" href="' . DOL_URL_ROOT . '/fourn/facture/card.php?action=create' . $commun_params . '">' . $langs->trans("AddSupplierInvoice") . '</a></div>';
                    } else {
                        print '<div class="inline-block divButAction"><a class="butActionRefused" title="' . dol_escape_js($langs->trans("NotAllowed")) . '" href="#">' . $langs->trans("AddSupplierInvoice") . '</a></div>';
                    }
                }

                // Add contract
                if (!empty($conf->contrat->enabled) && (count($authorizedButtons) == 0 || in_array('create_contract', $authorizedButtons)) && !in_array('no_buttons', $authorizedButtons)) {
                    $langs->load("contracts");
                    if ($user->rights->contrat->creer) {
                        print '<div class="inline-block divButAction"><a class="butAction" href="' . DOL_URL_ROOT . '/contrat/card.php?action=create' . $commun_params . $benefactor_params . $watcher_params . $ref_client . '">' . $langs->trans("AddContract") . '</a></div>';
                    } else {
                        print '<div class="inline-block divButAction"><a class="butActionRefused" title="' . dol_escape_js($langs->trans("NotAllowed")) . '" href="#">' . $langs->trans("AddContract") . '</a></div>';
                    }
                }

                // Add intervention
                if (!empty($conf->ficheinter->enabled) && (count($authorizedButtons) == 0 || in_array('create_inter', $authorizedButtons)) && !in_array('no_buttons', $authorizedButtons)) {
                    $langs->load("interventions");
                    if ($user->rights->ficheinter->creer) {
                        print '<div class="inline-block divButAction"><a class="butAction" href="' . DOL_URL_ROOT . '/fichinter/card.php?action=create' . $commun_params . $benefactor_params . $watcher_params . '">' . $langs->trans("AddIntervention") . '</a></div>';
                    } else {
                        print '<div class="inline-block divButAction"><a class="butActionRefused" title="' . dol_escape_js($langs->trans("NotAllowed")) . '" href="#">' . $langs->trans("AddIntervention") . '</a></div>';
                    }
                }

                // Add project
                if (!empty($conf->projet->enabled) && (count($authorizedButtons) == 0 || in_array('create_project', $authorizedButtons)) && !in_array('no_buttons', $authorizedButtons)) {
                    $langs->load("projects");
                    if ($user->rights->projet->creer) {
                        print '<div class="inline-block divButAction"><a class="butAction" href="' . DOL_URL_ROOT . '/projet/card.php?action=create' . $commun_params . '">' . $langs->trans("AddProject") . '</a></div>';
                    } else {
                        print '<div class="inline-block divButAction"><a class="butActionRefused" title="' . dol_escape_js($langs->trans("NotAllowed")) . '" href="#">' . $langs->trans("AddProject") . '</a></div>';
                    }
                }

                // Add trip
                if (!empty($conf->deplacement->enabled) && (count($authorizedButtons) == 0 || in_array('create_trip', $authorizedButtons)) && !in_array('no_buttons', $authorizedButtons)) {
                    $langs->load("trips");
                    if ($user->rights->deplacement->creer) {
                        print '<div class="inline-block divButAction"><a class="butAction" href="' . DOL_URL_ROOT . '/compta/deplacement/card.php?action=create' . $commun_params . '">' . $langs->trans("AddTrip") . '</a></div>';
                    } else {
                        print '<div class="inline-block divButAction"><a class="butActionRefused" title="' . dol_escape_js($langs->trans("NotAllowed")) . '" href="#">' . $langs->trans("AddTrip") . '</a></div>';
                    }
                }

                // Add request
                if ((count($authorizedButtons) == 0 || in_array('create_request_manager', $authorizedButtons)) && !in_array('no_buttons', $authorizedButtons)) {
                    if ($user->rights->requestmanager->creer) {
                        print '<div class="inline-block divButAction"><a class="butAction" href="' . dol_buildpath('/requestmanager/createfast.php', 2) . '?action=createfast&socid_origin=' . $object->socid . $commun_params . '">' . $langs->trans("RequestManagerAddRequest") . '</a></div>';
                    } else {
                        print '<div class="inline-block divButAction"><a class="butActionRefused" title="' . dol_escape_js($langs->trans("NotAllowed")) . '" href="#">' . $langs->trans("RequestManagerAddRequest") . '</a></div>';
                    }
                }

                // Add message
                if ($user->rights->requestmanager->creer) {
                    print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=premessage&messagemode=init#formmessagebeforetitle">'
                        . $langs->trans('RequestManagerAddMessage') . '</a></div>';
                }

                // Add event
                if (!empty($conf->agenda->enabled) && (count($authorizedButtons) == 0 || in_array('create_event', $authorizedButtons)) && !in_array('no_buttons', $authorizedButtons)) {
                    $langs->load("commercial");
                    if (! empty($user->rights->agenda->myactions->create) || ! empty($user->rights->agenda->allactions->create)) {
                        print '<div class="inline-block divButAction"><a class="butAction" href="' . DOL_URL_ROOT.'/comm/action/card.php?action=create' . $commun_params . '">' . $langs->trans("AddAction") . '</a></div>';
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

        $parameters = array();
        $reshook = $hookmanager->executeHooks('addMoreInfoBlocs', $parameters, $object, $action); // Note that $action and $object may have been
        print $hookmanager->resPrint;

        /*
	 * Events
	 */
        // Output template part (modules that overwrite templates must declare this into descriptor)
        $dirtpls = array_merge($conf->modules_parts['tpl'], array('/requestmanager/tpl'));
        foreach ($dirtpls as $reldir) {
            $res = @include dol_buildpath($reldir . '/rm_request_events.tpl.php');
            if ($res) {
                break;
            }
        }

        /*
	 * Linked Objects
	 */

        print '<div class="fichecenter"><div class="fichehalfleft">';

        if ($conf->global->REQUESTMANAGER_POSITION_BLOC_OBJECT_LINKED == 'bottom') {
            // Show links to link elements
            print '<div id="linked_objects_list">';
            $linktoelem = '';
            if ($conf->global->REQUESTMANAGER_POSITION_LINK_NEW_OBJECT_LINKED == 'bottom') {
                $linktoelem = $form->showLinkToObjectBlock($object);
            }
            $somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);
            print '</div>';
        }

        print '</div><div class="fichehalfright"><div class="ficheaddleft">';

        print '</div></div></div>';
    } else {
        /*
         * Affiche formulaire message
         */

        print '<div id="formmessagebeforetitle" name="formmessagebeforetitle"></div>';
        print '<div class="clearboth"></div>';
        print '<br>';
        print load_fiche_titre($langs->trans('RequestManagerAddMessage') . '&nbsp;<span id="rm-saving-status"></span>');

        dol_fiche_head();

        // Cree l'objet formulaire message
        $formrequestmanagermessage = new FormRequestManagerMessage($db, $object);

        $loaded = $formrequestmanagermessage->load_datas_in_session();

        // Tableau des parametres complementaires du post
        $formrequestmanagermessage->param['action'] = $action;
        $formrequestmanagermessage->param['models_id'] = GETPOST('modelmessageselected', 'int');
        $formrequestmanagermessage->param['knowledgebase_ids'] = GETPOST('knowledgebaseselected', 'array');
        $formrequestmanagermessage->param['returnurl'] = $_SERVER["PHP_SELF"] . '?id=' . $object->id;

        // Init list of files
        if (!$loaded && GETPOST("messagemode") == 'init') {
            $formrequestmanagermessage->clear_attached_files();
//            $formrequestmanagermessage->add_attached_files($file, basename($file), dol_mimetype($file));
        }

        // Show form
        print $formrequestmanagermessage->get_message_form();

        dol_fiche_end();
    }
}

// End of page
llxFooter();
$db->close();
