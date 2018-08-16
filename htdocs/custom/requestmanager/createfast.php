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

$langs->load('requestmanager@requestmanager');

$error = 0;

$action  = GETPOST('action', 'alpha');
$cancel  = GETPOST('cancel', 'alpha');
$confirm = GETPOST('confirm', 'alpha');

// Security check
$result = restrictedArea($user, 'requestmanager');

$object = new RequestManager($db);

if (empty($reshook)) {
    if ($cancel) $action = '';

    include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';		// Must be include, not include_once

    // Create request
    if ($action == 'addfast' && $user->rights->requestmanager->creer) {
        $object->fk_type      = GETPOST('type', 'int');
        $object->label        = GETPOST('label', 'alpha');
        $object->socid        = GETPOST('socid', 'int');
        $object->fk_source    = GETPOST('source', 'int');
        $object->fk_urgency   = GETPOST('urgency', 'int');
        $object->description  = GETPOST('description');
        $selectedActionCommId = GETPOST('actioncomm_id')?GETPOST('actioncomm_id'):-1;

        $btnAction = '';
        if (GETPOST('btn_create')) {
            $btnAction = 'create';
        } else if (GETPOST('btn_associate')) {
            $btnAction = 'associate';
        }

        if ($selectedActionCommId <= 0) {
            $object->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("RequestManagerCreateFastActionCommLabel"));
            setEventMessages($object->error, $object->errors, 'errors');
            $error++;
        }

        $db->begin();
        if (!$error) {
            if ($btnAction == 'create') {
                $id = $object->create($user);
                if ($id < 0) {
                    setEventMessages($object->error, $object->errors, 'errors');
                    $error++;
                }

                if (!$error) {
                    // Category association
                    $categories = GETPOST('categories');
                    $result = $object->setCategories($categories);
                    if ($result < 0) {
                        setEventMessages($object->error, $object->errors, 'errors');
                        $error++;
                    }
                }

                if (!$error) {
                    // link event to this request
                    $result = $object->linkToActionComm($selectedActionCommId);
                    if ($result < 0) {
                        setEventMessages($object->error, $object->errors, 'errors');
                        $error++;
                    }
                }
            } else if ($btnAction == 'associate') {
                $associateList = GETPOST('associate_list', 'array')?GETPOST('associate_list', 'array'):array();
                if (count($associateList) <= 0) {
                    $object->errors[] = $langs->trans("RequestManagerCreateFastErrorNoRequestSelected");
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
            }
        }

        if (!$error) {
            $db->commit();
            if ($object->id > 0) {
                header('Location: ' . dol_buildpath('/requestmanager/card.php', 1). '?id=' . $object->id);
            } else {
                header('Location: ' . dol_buildpath('/requestmanager/list.php', 1));
            }
            exit();
        } else {
            $db->rollback();
            $action = 'createfast';
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

if ($action == 'createfast' && $user->rights->requestmanager->creer)
{
    $selectedActionCommId = GETPOST('actioncomm_id', 'int')?intval(GETPOST('actioncomm_id', 'int')):-1;
    $selectedCategories   = GETPOST('categories', 'array')?GETPOST('categories', 'array'):array();
    $selectedContactId    = GETPOST('contactid', 'int')?intval(GETPOST('contactid', 'int')):-1;
    $selectedDescription  = GETPOST('description', 'alpha')?GETPOST('description', 'alpha'):'';
    $selectedEquipementId = GETPOST('equipement_id', 'int')?intval(GETPOST('equipement_id', 'int')):-1;
    $selectedLabel        = GETPOST('label', 'alpha')?GETPOST('label', 'alpha'):'';
    $selectedSocId        = GETPOST('socid', 'int')?intval(GETPOST('socid', 'int')):-1;
    $selectedFkSource     = GETPOST('source', 'int')?intval(GETPOST('source', 'int')):-1;
    $selectedFkType       = GETPOST('type', 'int')?intval(GETPOST('type', 'int')):-1;
    $selectedFkUrgency    = GETPOST('urgency', 'int')?intval(GETPOST('urgency', 'int')):-1;

    /*
     *  Creation
     */
	print load_fiche_titre($langs->trans("RequestManagerCreateFastTitle"), '', 'requestmanager@requestmanager');

	print '<form name="addpropfast" action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" name="action" value="addfast">';

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
    $out .= '       contactid: ' . $selectedContactId . ',';
    if (!empty($selectedDescription)) {
        $out .= '   description: "' . $selectedDescription . '",';
    }
    if ($conf->equipement->enabled) {
        $out .= '   equipement_id: ' . $selectedEquipementId . ',';
    }
    if (!empty($selectedLabel)) {
        $out .= '   label: "' . $selectedLabel . '" ,';
    }
    $out .= '       socid: ' . $selectedSocId . ',';
    $out .= '       source: ' . $selectedFkSource . ',';
    $out .= '       type: ' . $selectedFkType . ',';
    $out .= '       urgency: ' . $selectedFkUrgency . ',';
    $out .= '       zone: 1';
    $out .= '   };';
    $out .= '   var requestManagerLoader = new RequestManagerLoader(0, "create_fast_zone", "' . dol_buildpath('/requestmanager/tpl/createfastzone.tpl.php', 1) . '", ajaxData);';
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
