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
 * \file 		htdocs/requestmanager/createfast.php
 * \ingroup 	requestmanager
 * \brief 		Page of Request create fast
 */

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include '../main.inc.php';					// to work if your module directory is into dolibarr root htdocs directory
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (! $res && file_exists("../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../dolibarr/htdocs/main.inc.php';     // Used on dev env only
if (! $res && file_exists("../../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../../dolibarr/htdocs/main.inc.php';   // Used on dev env only
if (! $res) die("Include of main fails");
require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';

if (!empty($conf->categorie->enabled)) {
    require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
    dol_include_once('/requestmanager/class/categorierequestmanager.class.php');
}

dol_include_once('/requestmanager/class/requestmanager.class.php');
dol_include_once('/requestmanager/class/html.formrequestmanager.class.php');
dol_include_once('/requestmanager/lib/requestmanagertimeslots.lib.php');

$langs->load('requestmanager@requestmanager');

$error = 0;

$action  = GETPOST('action', 'alpha');
$cancel  = GETPOST('cancel', 'alpha');
$confirm = GETPOST('confirm', 'alpha');

// Security check
$result = restrictedArea($user, 'requestmanager');
if (!$user->rights->requestmanager->creer) {
    accessforbidden();
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('requestmanagerfastcard','globalcard'));

// Active chronometer
if (!empty($conf->global->REQUESTMANAGER_CHRONOMETER_ACTIVATE) && !isset($_SESSION['requestmanager_chronometer_activated']))
    $_SESSION['requestmanager_chronometer_activated'] = dol_now();

$object = new RequestManager($db);

if (!empty($conf->companyrelationships->enabled)) {
    dol_include_once('/companyrelationships/class/companyrelationships.class.php');
    $companyrelationships = new CompanyRelationships($db);
}

$force_principal_company_confirmed = GETPOST('force_principal_company_confirmed', 'int');
$force_out_of_time_confirmed = GETPOST('force_out_of_time_confirmed', 'int');

/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
    if ($cancel) $action = '';
    if ($action == 'confirm_force_principal_company' && $confirm == "yes" && $user->rights->requestmanager->creer) {
        $force_principal_company_confirmed = true;
        $action = "addfast";
    } elseif ($action == 'confirm_force_out_of_time' && $confirm == "yes" && $user->rights->requestmanager->creer) {
        $force_out_of_time_confirmed = true;
        $action = "addfast";
    }
    // Create request
    if ($action == 'addfast' && $user->rights->requestmanager->creer) {
        $selectedCategories = GETPOST('categories', 'array') ? GETPOST('categories', 'array') : (GETPOST('categories', 'alpha') ? explode(',', GETPOST('categories', 'alpha')) : array());
        $selectedContacts = GETPOST('contact_ids', 'array') ? GETPOST('contact_ids', 'array') : (GETPOST('contact_ids', 'alpha') ? explode(',', GETPOST('contact_ids', 'alpha')) : array());

        $object->fk_type = GETPOST('type', 'int');
        $object->label = GETPOST('label');
        $object->socid_origin = GETPOST('socid_origin', 'int');
        $object->socid = GETPOST('socid', 'int');
        $object->socid_benefactor = GETPOST('socid_benefactor', 'int');
        $object->socid_watcher = GETPOST('socid_watcher', 'int');
        $object->requester_ids = $selectedContacts;
        $object->fk_source = GETPOST('source', 'int');
        $object->fk_urgency = GETPOST('urgency', 'int');
        $object->description = GETPOST('description');
        $selectedActionCommId = GETPOST('actioncomm_id') ? GETPOST('actioncomm_id') : -1;
        $object->date_creation = dol_now();

        // Add equipment links
        $selectedEquipementId = GETPOST('equipement_id', 'int') ? intval(GETPOST('equipement_id', 'int')) : -1;
        if ($selectedEquipementId > 0) {
            $object->linkedObjectsIds['equipement'][] = $selectedEquipementId;
        }

        // Possibility to add external linked objects with hooks
        $object->origin = GETPOST('origin', 'alpha');
        $object->origin_id = GETPOST('originid', 'int');
        if ($object->origin && $object->origin_id > 0) {
            $object->linkedObjectsIds[$object->origin] = $object->origin_id;
            if (is_array($_POST['other_linked_objects']) && !empty($_POST['other_linked_objects'])) {
                $object->linkedObjectsIds = array_merge($object->linkedObjectsIds, $_POST['other_linked_objects']);
            }
        }

        if (GETPOST('btn_associate')) {
            $btnAction = 'associate';
        } else {
            $btnAction = 'create';
        }

        $db->begin();
        if ($btnAction == 'create' || $force_principal_company_confirmed || $force_out_of_time_confirmed) {
            $date_creation = $object->date_creation;
            if ($selectedActionCommId > 0) {
                require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
                $actioncomm = new ActionComm($db);
                $actioncomm->fetch($selectedActionCommId);
                $date_creation = $actioncomm->datep;
            }
            $res = requestmanagertimeslots_is_in_time_slot($object->socid, $date_creation);
            $object->created_out_of_time = is_array($res) ? 0 : ($res ? 0 : 1);
            if (!empty($conf->companyrelationships->enabled)) {
                $principal_companies_ids = $companyrelationships->getRelationships($object->socid_benefactor, 0);
                $not_principal_company = !in_array($object->socid, $principal_companies_ids) && $object->socid != $object->socid_benefactor;
            } else {
                $not_principal_company = false;
            }
            if ($not_principal_company && !$force_principal_company_confirmed) {
                $error++;
                $action = 'force_principal_company';
            } elseif (!empty($conf->global->REQUESTMANAGER_TIMESLOTS_ACTIVATE) && $object->created_out_of_time && !$force_out_of_time_confirmed) {
                $error++;
                $action = 'force_out_of_time';
            } else {
                $id = $object->create($user);
                if ($id < 0) {
                    setEventMessages($object->error, $object->errors, 'errors');
                    $error++;
                }

                if (!$error && $not_principal_company && $force_principal_company_confirmed) {
                    // Principal company forced for the benefactor
                    $result = $object->addActionForcedPrincipalCompany($user);
                    if ($result < 0) {
                        setEventMessages($object->error, $object->errors, 'errors');
                        $error++;
                    }
                }

                if (!$error && !empty($conf->global->REQUESTMANAGER_TIMESLOTS_ACTIVATE) && $object->created_out_of_time && $force_out_of_time_confirmed) {
                    // Create forced out of time
                    $result = $object->addActionForcedCreatedOutOfTime($user);
                    if ($result < 0) {
                        setEventMessages($object->error, $object->errors, 'errors');
                        $error++;
                    }
                }

                if (!$error) {
                    // Category association
                    $result = $object->setCategories($selectedCategories);
                    if ($result < 0) {
                        setEventMessages($object->error, $object->errors, 'errors');
                        $error++;
                    }
                }

                if (!$error && $selectedActionCommId > 0) {
                    // link event to this request
                    $result = $object->linkToActionComm($selectedActionCommId);
                    if ($result < 0) {
                        setEventMessages($object->error, $object->errors, 'errors');
                        $error++;
                    }
                }

                if ($error) {
                    $action = 'createfast';
                }
            }
        } else if ($btnAction == 'associate') {
            $associateList = GETPOST('associate_list', 'array') ? GETPOST('associate_list', 'array') : array();
            if (count($associateList) <= 0) {
                $object->errors[] = $langs->trans("RequestManagerCreateFastErrorNoRequestSelected");
                setEventMessages($object->error, $object->errors, 'errors');
                $error++;
            }

            if ($selectedActionCommId <= 0) {
                $object->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("RequestManagerCreateFastActionCommLabel"));
                setEventMessages($object->error, $object->errors, 'errors');
                $error++;
            }

            if (!$error) {
                $object->fetch(intval($associateList[0]));

                // link event to this request
                $result = $object->linkToActionComm($selectedActionCommId);
                if ($result < 0) {
                    setEventMessages($object->error, $object->errors, 'errors');
                    $error++;
                }
            }

            if ($error) {
                $action = 'createfast';
            }
        }

        if (!$error) {
            $db->commit();
            if ($object->id > 0) {
                header('Location: ' . dol_buildpath('/requestmanager/card.php', 1) . '?id=' . $object->id);
            } else {
                header('Location: ' . dol_buildpath('/requestmanager/list.php', 1));
            }
            exit();
        } else {
            $db->rollback();
        }
    }
}

/*
 * View
 */

llxHeader('', $langs->trans('RequestManagerCreateFastTitle'), '', '', 0, 0, array('/requestmanager/js/requestmanager.js', '/requestmanager/js/opendsi.js'));

$form = new Form($db);
$formrequestmanager = new FormRequestManager($db);
$usergroup_static = new UserGroup($db);

$now = dol_now();

if (($action == 'createfast' || $action == 'force_principal_company' || $action == 'force_out_of_time') && $user->rights->requestmanager->creer) {
    $selectedActionJs = GETPOST('action_js') ? GETPOST('action_js') : '';
    $selectedActionCommId = GETPOST('actioncomm_id', 'int') ? intval(GETPOST('actioncomm_id', 'int')) : -1;
    $selectedCategories = GETPOST('categories', 'array') ? GETPOST('categories', 'array') : (GETPOST('categories', 'alpha') ? explode(',', GETPOST('categories', 'alpha')) : array());
    $selectedContacts = GETPOST('contact_ids', 'array') ? GETPOST('contact_ids', 'array') : (GETPOST('contact_ids', 'alpha') ? explode(',', GETPOST('contact_ids', 'alpha')) : array());
    $selectedDescription = GETPOST('description') ? GETPOST('description') : '';
    $selectedEquipementId = GETPOST('equipement_id', 'int') ? intval(GETPOST('equipement_id', 'int')) : -1;
    $selectedLabel = GETPOST('label', 'alpha') ? GETPOST('label', 'alpha') : '';
    $selectedSocIdOrigin = GETPOST('socid_origin', 'int') ? intval(GETPOST('socid_origin', 'int')) : -1;
    $selectedSocId = GETPOST('socid', 'int') ? intval(GETPOST('socid', 'int')) : -1;
    $selectedSocIdBenefactor = GETPOST('socid_benefactor', 'int') ? intval(GETPOST('socid_benefactor', 'int')) : -1;
    $selectedSocIdWatcher = GETPOST('socid_watcher', 'int') ? intval(GETPOST('socid_watcher', 'int')) : -1;
    $selectedFkSource = GETPOST('source', 'int') ? intval(GETPOST('source', 'int')) : -1;
    $selectedFkType = GETPOST('type', 'int') ? intval(GETPOST('type', 'int')) : -1;
    $selectedFkUrgency = GETPOST('urgency', 'int') ? intval(GETPOST('urgency', 'int')) : -1;
    $selectedRequesterNotification = GETPOST('notify_requester_by_email', 'int') > 0 ? 1 : 0;
    $origin = GETPOST('origin', 'alpha');
    $originid = GETPOST('originid', 'int');

    if ($selectedSocIdOrigin === '' && $selectedSocId > 0) {
        $selectedSocIdOrigin = $selectedSocId;
    }

    if (!empty($conf->companyrelationships->enabled)) {
        // Set default values
        $force_set = $selectedActionJs == 'change_socid_origin';
        if ($selectedSocIdOrigin > 0) {
            $originRelationshipType = $companyrelationships->getRelationshipTypeThirdparty($selectedSocIdOrigin, CompanyRelationships::RELATION_TYPE_BENEFACTOR);
            if ($originRelationshipType == 0) { // Benefactor company
                $selectedSocIdBenefactor = $selectedSocIdBenefactor < 0 || $force_set ? $selectedSocIdOrigin : $selectedSocIdBenefactor;
            } elseif ($originRelationshipType > 0) { // Principal company or both
                $selectedSocId = $selectedSocId < 0 || $force_set ? $selectedSocIdOrigin : $selectedSocId;
            } else { // None
                $selectedSocId = $selectedSocId < 0 || $force_set ? $selectedSocIdOrigin : $selectedSocId;
                $selectedSocIdBenefactor = $selectedSocIdBenefactor < 0 || $force_set ? $selectedSocId : $selectedSocIdBenefactor;
            }
        }
        if ($selectedSocId > 0) {
            $benefactor_companies_ids = $companyrelationships->getRelationshipsThirdparty($selectedSocId, CompanyRelationships::RELATION_TYPE_BENEFACTOR, 1);
            $benefactor_companies_ids = is_array($benefactor_companies_ids) ? array_values($benefactor_companies_ids) : array();
            $selectedSocIdBenefactor = $selectedSocIdBenefactor < 0 || $force_set ? (count($benefactor_companies_ids) > 0 ? $benefactor_companies_ids[0] : $selectedSocId) : $selectedSocIdBenefactor;
        }
        if ($selectedSocIdBenefactor > 0) {
            $principal_companies_ids = $companyrelationships->getRelationshipsThirdparty($selectedSocIdBenefactor, CompanyRelationships::RELATION_TYPE_BENEFACTOR,0);
            $principal_companies_ids = is_array($principal_companies_ids) ? array_values($principal_companies_ids) : array();
            $selectedSocId = $selectedSocId < 0 || $force_set ? (count($principal_companies_ids) > 0 ? $principal_companies_ids[0] : $selectedSocIdBenefactor) : $selectedSocId;
        }

        // default watcher
        if ($selectedSocId > 0) {
            $watcher_companies_ids = $companyrelationships->getRelationshipsThirdparty($selectedSocId, CompanyRelationships::RELATION_TYPE_WATCHER, 1);
            $watcher_companies_ids = is_array($watcher_companies_ids) ? array_values($watcher_companies_ids) : array();
            $selectedSocIdWatcher = $force_set ? (count($watcher_companies_ids) > 0 ? $watcher_companies_ids[0] : $selectedSocIdWatcher) : $selectedSocIdWatcher;
        }
    } else {
        $selectedSocId = $selectedSocIdOrigin;
        $selectedSocIdBenefactor = $selectedSocIdOrigin;
    }

    // Confirm force principal company
    $formquestion = array();
    if ($action == 'force_principal_company') {
        if (!empty($selectedActionJs)) $formquestion[] = array('type' => 'hidden', 'name' => 'action_js', 'value' => $selectedActionJs);
        if (!empty($selectedActionCommId)) $formquestion[] = array('type' => 'hidden', 'name' => 'actioncomm_id', 'value' => $selectedActionCommId);
        if (!empty($selectedCategories)) $formquestion[] = array('type' => 'hidden', 'name' => 'categories', 'value' => implode(',', $selectedCategories));
        if (!empty($selectedContacts)) $formquestion[] = array('type' => 'hidden', 'name' => 'contact_ids', 'value' => implode(',', $selectedContacts));
        if (!empty($selectedDescription)) $formquestion[] = array('type' => 'hidden', 'name' => 'description', 'value' => $selectedDescription);
        if (!empty($selectedEquipementId)) $formquestion[] = array('type' => 'hidden', 'name' => 'equipement_id', 'value' => $selectedEquipementId);
        if (!empty($selectedLabel)) $formquestion[] = array('type' => 'hidden', 'name' => 'label', 'value' => $selectedLabel);
        if (!empty($selectedSocIdOrigin)) $formquestion[] = array('type' => 'hidden', 'name' => 'socid_origin', 'value' => $selectedSocIdOrigin);
        if (!empty($selectedSocId)) $formquestion[] = array('type' => 'hidden', 'name' => 'socid', 'value' => $selectedSocId);
        if (!empty($selectedSocIdBenefactor)) $formquestion[] = array('type' => 'hidden', 'name' => 'socid_benefactor', 'value' => $selectedSocIdBenefactor);
        if (!empty($selectedFkSource)) $formquestion[] = array('type' => 'hidden', 'name' => 'source', 'value' => $selectedFkSource);
        if (!empty($selectedFkType)) $formquestion[] = array('type' => 'hidden', 'name' => 'type', 'value' => $selectedFkType);
        if (!empty($selectedFkUrgency)) $formquestion[] = array('type' => 'hidden', 'name' => 'urgency', 'value' => $selectedFkUrgency);
        if (!empty($origin)) $formquestion[] = array('type' => 'hidden', 'name' => 'origin', 'value' => $origin);
        if (!empty($originid)) $formquestion[] = array('type' => 'hidden', 'name' => 'originid', 'value' => $originid);
    }

    if ($action == 'force_principal_company') {
        if (!empty($force_out_of_time)) $formquestion[] = array('type' => 'hidden', 'name' => 'force_out_of_time', 'value' => $force_out_of_time ? 1 : 0);
        $societe = new Societe($db);
        $societe->fetch($selectedSocId);
        $formquestion[] = array('type' => 'other', 'label' => $langs->trans('RequestManagerThirdPartyPrincipal'), 'value' => $societe->getNomUrl(1));
        $societe->fetch($selectedSocIdBenefactor);
        $formquestion[] = array('type' => 'other', 'label' => $langs->trans('RequestManagerThirdPartyBenefactor'), 'value' => $societe->getNomUrl(1));

        print $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans('RequestManagerForcePrincipalCompany'), $langs->trans('RequestManagerConfirmForcePrincipalCompany'), 'confirm_force_principal_company', $formquestion, 0, 1);
    } elseif (!empty($conf->global->REQUESTMANAGER_TIMESLOTS_ACTIVATE) && $action == 'force_out_of_time') {
        if (!empty($force_principal_company)) $formquestion[] = array('type' => 'hidden', 'name' => 'force_principal_company', 'value' => $force_principal_company ? 1 : 0);
        if (!empty($originid)) $formquestion[] = array('type' => 'hidden', 'name' => 'originid', 'value' => $originid);
        $outOfTimes = requestmanagertimeslots_get_out_of_time_infos($selectedSocId);
        if (is_array($outOfTimes) && count($outOfTimes) > 0) {
            $toprint = array();
            foreach ($outOfTimes as $infos) {
                $toprint[] = '&nbsp;-&nbsp;' . $infos['year'] . (isset($infos['month']) ? '-' . $infos['month'] : '') . ' : ' . $infos['count'];
            }
            $formquestion[] = array('type' => 'onecolumn', 'value' => $langs->trans('RequestManagerCreatedOutOfTime') . ':<br>' . implode('<br>', $toprint));
        }

        print $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans('RequestManagerForceCreateOutOfTime'), $langs->trans('RequestManagerConfirmForceCreateOutOfTime'), 'confirm_force_out_of_time', $formquestion, 0, 1);
    }

    /*
     *  Creation
     */
    print '<form name="addpropfast" action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
    print '<input type="hidden" name="action" value="addfast">';

    $origin_title = '';
    // Origin
    if (!empty($origin) && !empty($originid)) {
        require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
        $origin_title = ' (' . $langs->trans('Origin') . ' : ' . dolGetElementUrl($originid, $origin, 1) . ')';
        print '<input type="hidden" name="origin" value="' . dol_escape_htmltag($origin) . '">';
        print '<input type="hidden" name="originid" value="' . $originid . '">';
    }

    print load_fiche_titre($langs->trans("RequestManagerCreateFastTitle") . $origin_title, '', 'requestmanager@requestmanager');

    dol_fiche_head();
    print '<div id="create_fast_zone1"></div>';
    dol_fiche_end();

    print '<div id="create_fast_zone2"></div>';
    print "</form>";

    print '<div id="create_fast_zone3"></div>';

    $out .= '<script type="text/javascript" language="javascript">';
    $out .= 'jQuery(document).ready(function(){';
    $out .= '   var ajaxData = {';
    $out .= '       actioncomm_id: ' . $selectedActionCommId . ',';
    if ($conf->categorie->enabled) {
        $out .= '   categories: ' . json_encode($selectedCategories) . ',';
    }
    $out .= '       contact_ids: ' . json_encode($selectedContacts) . ',';
    if (!empty($selectedDescription)) {
        $out .= '   description: "' . $selectedDescription . '",';
    }
    if ($conf->equipement->enabled) {
        $out .= '   equipement_id: ' . $selectedEquipementId . ',';
    }
    if (!empty($selectedLabel)) {
        $out .= '   label: "' . $selectedLabel . '" ,';
    }
    $out .= '       socid_origin: ' . $selectedSocIdOrigin . ',';
    $out .= '       socid: ' . $selectedSocId . ',';
    $out .= '       socid_benefactor: ' . $selectedSocIdBenefactor . ',';
    $out .= '       socid_watcher: ' . $selectedSocIdWatcher . ',';
    $out .= '       source: ' . $selectedFkSource . ',';
    $out .= '       type: ' . $selectedFkType . ',';
    $out .= '       urgency: ' . $selectedFkUrgency . ',';
    $out .= '       notify_requester_by_email: ' . $selectedRequesterNotification . ',';
    $out .= '       zone: 1';
    $out .= '   };';
    $out .= '   var requestManagerLoader = new RequestManagerLoader(0, "create_fast_zone", "' . dol_buildpath('/requestmanager/tpl/rm_createfastzone.tpl.php', 1) . '", ajaxData);';
    if ($selectedSocId > 0) {
        $out .= '   requestManagerLoader.loadZone(1, "load_auto");';
    } else {
        $out .= '   requestManagerLoader.loadZone(1, "");';
    }
    $out .= '});';
    $out .= '</script>' . "\n";
    print $out;
}

// End of page
llxFooter();
$db->close();
