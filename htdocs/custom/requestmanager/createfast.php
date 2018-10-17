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
dol_include_once('/companyrelationships/class/companyrelationships.class.php');

$langs->load('requestmanager@requestmanager');

$error = 0;

$action  = GETPOST('action', 'alpha');
$cancel  = GETPOST('cancel', 'alpha');
$confirm = GETPOST('confirm', 'alpha');

// Security check
$result = restrictedArea($user, 'requestmanager');

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('requestmanagercard','globalcard'));

// Active chronometer
if (!empty($conf->global->REQUESTMANAGER_CHRONOMETER_ACTIVATE) && !isset($_SESSION['requestmanager_chronometer_activated']))
    $_SESSION['requestmanager_chronometer_activated'] = dol_now();

$object = new RequestManager($db);
$companyrelationships = new CompanyRelationships($db);

$force_principal_company = false;

/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
    if ($cancel) $action = '';
    if ($action == 'confirm_force_principal_company' && $confirm == "yes" && $user->rights->requestmanager->creer) {
        $force_principal_company = true;
        $action = "addfast";
    }
    // Create request
    if ($action == 'addfast' && $user->rights->requestmanager->creer) {
        $selectedCategories = GETPOST('categories', 'array') ? GETPOST('categories', 'array') : (GETPOST('categories', 'alpha') ? explode(',', GETPOST('categories', 'alpha')) : array());
        $selectedContacts = GETPOST('contact_ids', 'array') ? GETPOST('contact_ids', 'array') : (GETPOST('contact_ids', 'alpha') ? explode(',', GETPOST('contact_ids', 'alpha')) : array());

        $object->fk_type = GETPOST('type', 'int');
        $object->label = GETPOST('label', 'alpha');
        $object->socid_origin = GETPOST('socid_origin', 'int');
        $object->socid = GETPOST('socid', 'int');
        $object->socid_benefactor = GETPOST('socid_benefactor', 'int');
        $object->requester_ids = $selectedContacts;
        $object->fk_source = GETPOST('source', 'int');
        $object->fk_urgency = GETPOST('urgency', 'int');
        $object->description = GETPOST('description');
        $selectedActionCommId = GETPOST('actioncomm_id') ? GETPOST('actioncomm_id') : -1;

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

        $btnAction = '';
        if (GETPOST('btn_create')) {
            $btnAction = 'create';
        } else if (GETPOST('btn_associate')) {
            $btnAction = 'associate';
        }

        $db->begin();
        if ($btnAction == 'create' || $force_principal_company) {
            $principal_companies_ids = $companyrelationships->getRelationships($object->socid_benefactor, 0);
            $not_principal_company = !in_array($object->socid, $principal_companies_ids) && $object->socid != $object->socid_benefactor;
            if ($not_principal_company && !$force_principal_company) {
                $error++;
                $action = 'force_principal_company';
            } else {
                $id = $object->create($user);
                if ($id < 0) {
                    setEventMessages($object->error, $object->errors, 'errors');
                    $error++;
                }

                if (!$error && $not_principal_company && $force_principal_company) {
                    // Principal company forced for the benefactor
                    $result = $object->addActionForcedPrincipalCompany($user);
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

llxHeader('', $langs->trans('RequestManagerCreateFastTitle'), '', '', 0, 0, array('/custom/requestmanager/js/requestmanager.js'));

$form = new Form($db);
$formrequestmanager = new FormRequestManager($db);
$usergroup_static = new UserGroup($db);

$now = dol_now();

if (($action == 'createfast' || $action == 'force_principal_company') && $user->rights->requestmanager->creer) {
    $selectedActionJs = GETPOST('action_js') ? GETPOST('action_js') : '';
    $selectedActionCommId = GETPOST('actioncomm_id', 'int') ? intval(GETPOST('actioncomm_id', 'int')) : -1;
    $selectedCategories = GETPOST('categories', 'array') ? GETPOST('categories', 'array') : (GETPOST('categories', 'alpha') ? explode(',', GETPOST('categories', 'alpha')) : array());
    $selectedContacts = GETPOST('contact_ids', 'array') ? GETPOST('contact_ids', 'array') : (GETPOST('contact_ids', 'alpha') ? explode(',', GETPOST('contact_ids', 'alpha')) : array());
    $selectedDescription = GETPOST('description', 'alpha') ? GETPOST('description', 'alpha') : '';
    $selectedEquipementId = GETPOST('equipement_id', 'int') ? intval(GETPOST('equipement_id', 'int')) : -1;
    $selectedLabel = GETPOST('label', 'alpha') ? GETPOST('label', 'alpha') : '';
    $selectedSocIdOrigin = GETPOST('socid_origin', 'int') ? intval(GETPOST('socid_origin', 'int')) : -1;
    $selectedSocId = GETPOST('socid', 'int') ? intval(GETPOST('socid', 'int')) : -1;
    $selectedSocIdBenefactor = GETPOST('socid_benefactor', 'int') ? intval(GETPOST('socid_benefactor', 'int')) : -1;
    $selectedFkSource = GETPOST('source', 'int') ? intval(GETPOST('source', 'int')) : -1;
    $selectedFkType = GETPOST('type', 'int') ? intval(GETPOST('type', 'int')) : -1;
    $selectedFkUrgency = GETPOST('urgency', 'int') ? intval(GETPOST('urgency', 'int')) : -1;
    $origin = GETPOST('origin', 'alpha');
    $originid = GETPOST('originid', 'int');

    // Set default values
    $force_set = $selectedActionJs=='change_socid_origin';
    if ($selectedSocIdOrigin > 0) {
        $originRelationshipType = $companyrelationships->getRelationshipType($selectedSocIdOrigin);
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
        $benefactor_companies_ids = $companyrelationships->getRelationships($selectedSocId, 1);
        $benefactor_companies_ids = is_array($benefactor_companies_ids) ? array_values($benefactor_companies_ids) : array();
        $selectedSocIdBenefactor = $selectedSocIdBenefactor < 0 || $force_set ? (count($benefactor_companies_ids) > 0 ? $benefactor_companies_ids[0] : $selectedSocId) : $selectedSocIdBenefactor;
    }
    if ($selectedSocIdBenefactor > 0) {
        $principal_companies_ids = $companyrelationships->getRelationships($selectedSocIdBenefactor, 0);
        $principal_companies_ids = is_array($principal_companies_ids) ? array_values($principal_companies_ids) : array();
        $selectedSocId = $selectedSocId < 0 || $force_set ? (count($principal_companies_ids) > 0 ? $principal_companies_ids[0] : $selectedSocIdBenefactor) : $selectedSocId;
    }

    // Confirm force principal company
    if ($action == 'force_principal_company') {
        $formquestion = array();
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

        $societe = new Societe($db);
        $societe->fetch($selectedSocId);
        $formquestion[] = array('type' => 'other', 'label' => $langs->trans('RequestManagerThirdPartyPrincipal'), 'value' => $societe->getNomUrl(1));
        $societe->fetch($selectedSocIdBenefactor);
        $formquestion[] = array('type' => 'other', 'label' => $langs->trans('RequestManagerThirdPartyBenefactor'), 'value' => $societe->getNomUrl(1));

        print $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans('RequestManagerForcePrincipalCompany'), $langs->trans('RequestManagerConfirmForcePrincipalCompany'), 'confirm_force_principal_company', $formquestion, 0, 1);
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
    $out .= '       source: ' . $selectedFkSource . ',';
    $out .= '       type: ' . $selectedFkType . ',';
    $out .= '       urgency: ' . $selectedFkUrgency . ',';
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
