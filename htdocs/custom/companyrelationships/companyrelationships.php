<?php
/* Copyright (C) 2005 		Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2010 		Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010-2011 	Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2018       Open-Dsi             <support@open-dsi.fr>
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
 *  \file       htdocs/companyrelationships/companiesrelationship.php
 *  \ingroup    companyrelationships
 *  \brief      Page of companies relationship
 */

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include '../main.inc.php';					// to work if your module directory is into dolibarr root htdocs directory
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (! $res && file_exists("../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../dolibarr/htdocs/main.inc.php';     // Used on dev env only
if (! $res && file_exists("../../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../../dolibarr/htdocs/main.inc.php';   // Used on dev env only
if (! $res) die("Include of main fails");
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
dol_include_once('/companyrelationships/class/companyrelationships.class.php');
dol_include_once('/companyrelationships/lib/companyrelationships.lib.php');

$langs->load("companies");
$langs->load('other');
$langs->load("companyrelationships@companyrelationships");

$action=GETPOST('action','aZ09');
$confirm=GETPOST('confirm');
$id=(GETPOST('socid','int') ? GETPOST('socid','int') : GETPOST('id','int'));
$ref = GETPOST('ref', 'alpha');

// Security check
if ($user->societe_id > 0)
{
	unset($action);
	$socid = $user->societe_id;
}
$result = restrictedArea($user, 'societe', $id, '&societe');

$hookmanager->initHooks(array('companiesrelationshipcard','globalcard'));

$object = new Societe($db);
$companyrelationships = new CompanyRelationships($db);

// Load object
if ($id > 0 || !empty($ref)) {
	$ret = $object->fetch($id, $ref);
    if ($ret > 0) {
        $id = $object->id;
        //$mysoc = $object->socid;
    } elseif ($ret < 0) {
        dol_print_error('', $object->error);
    } else {
        print $langs->trans('NoRecordFound');
        exit();
    }
}


/*
 *	Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
    $error = 0;

    if ($action == 'set_thirdparty_watcher' && $user->rights->societe->creer) {
        $socid_relation = intval(GETPOST('watcher_socid', 'int'));
        $relation_type  = intval(GETPOST('relation_type', 'int'));

        // save thirdparty watcher relationships
        $result = $companyrelationships->saveRelationshipThirdparty($object->id, $relation_type, $socid_relation);
        if ($result < 0) {
            $error++;
        }

        if ($error) {
            setEventMessages($companyrelationships->error, $companyrelationships->errors, 'errors');
        } else {
            header('Location: ' . $_SERVER["PHP_SELF"] . '?socid=' . $object->id);
            exit();
        }
    }
    elseif ($action == 'confirm_update_relationship_watcher' && $confirm == 'yes' && $user->rights->societe->creer) {
        $socid_relation = intval(GETPOST('watcher_socid', 'int'));
        $relation_type  = intval(GETPOST('relation_type', 'int'));

        $publicSpaceAvailabilityElementList = $companyrelationships->getAllPublicSpaceAvailabilityByDefault('element');
        if (!is_array($publicSpaceAvailabilityElementList)) {
            $error++;
        }

        $publicSpaceAvailabilityArray = array();
        foreach ($publicSpaceAvailabilityElementList as $psaId => $publicSpaceAvailabilityElement) {
            $publicSpaceAvailabilityArray[$psaId] = GETPOST('publicspaceavailability_' . $publicSpaceAvailabilityElement, 'int');
        }

        $db->begin();

        $result = $companyrelationships->saveRelationshipThirdparty($object->id, $relation_type, $socid_relation, $publicSpaceAvailabilityArray);
        if ($result < 0) {
            $error++;
        }

        if ($error) {
            $db->rollback();
            setEventMessages($companyrelationships->error, $companyrelationships->errors, 'errors');
            $action = 'edit_relationship_watcher';
        } else {
            $db->commit();
            header('Location: ' . $_SERVER["PHP_SELF"] . '?socid=' . $object->id);
            exit();
        }
    }
    else if ($action == 'add_relationship' && $user->rights->societe->creer) {
        $principal_socid = GETPOST('add_principal_socid', 'int');
        $benefactor_socid = GETPOST('add_benefactor_socid', 'int');

        $db->begin();

        // create relationship
        $result = $companyrelationships->createRelationshipThirdparty($principal_socid, CompanyRelationships::RELATION_TYPE_BENEFACTOR, $benefactor_socid);
        if ($result < 0) {
            $error++;
        }

        if ($error) {
            $db->rollback();
            setEventMessages($companyrelationships->error, $companyrelationships->errors, 'errors');
        } else {
            $db->commit();
            header('Location: ' . $_SERVER["PHP_SELF"] . '?socid=' . $object->id);
            exit();
        }
    }
    elseif ($action == 'confirm_update_relationship' && $confirm == 'yes' && $user->rights->societe->creer) {
        $rowid = GETPOST('rowid', 'int');
        $principal_socid = GETPOST('edit_principal_socid', 'int');
        $benefactor_socid = GETPOST('edit_benefactor_socid', 'int');

        $list_mode = intval(GETPOST('list_mode', 'int'));

        $publicSpaceAvailabilityElementList = $companyrelationships->getAllPublicSpaceAvailabilityByDefault('element');
        if (!is_array($publicSpaceAvailabilityElementList)) {
            $error++;
        }

        $publicSpaceAvailabilityArray = array();
        foreach ($publicSpaceAvailabilityElementList as $psaId => $publicSpaceAvailabilityElement)
        {
            $publicSpaceAvailabilityArray[$psaId] = GETPOST('publicspaceavailability_' . $publicSpaceAvailabilityElement, 'int');
        }

        $db->begin();

        $companyrelationships->id = $rowid;
        $relation_direction = ($list_mode ? 1 : -1);
        $result = $companyrelationships->updateRelationshipThirdparty($principal_socid, CompanyRelationships::RELATION_TYPE_BENEFACTOR, $benefactor_socid, $publicSpaceAvailabilityArray, $relation_direction);
        if ($result < 0) {
            $error++;
        }

        if ($error) {
            $db->rollback();
            setEventMessages($companyrelationships->error, $companyrelationships->errors, 'errors');
            $action = 'edit_relationship';
        } else {
            $db->commit();
            header('Location: ' . $_SERVER["PHP_SELF"] . '?socid=' . $object->id);
            exit();
        }
    }
    elseif ($action == 'confirm_delete_relationship' && $confirm == 'yes' && $user->rights->societe->creer) {
        $rowid = GETPOST('rowid', 'int');

        $result = $companyrelationships->deleteRelationships($rowid);
        if ($result < 0) {
            setEventMessages($companyrelationships->error, $companyrelationships->errors, 'errors');
            $action = 'delete_relationship';
        } else {
            header('Location: ' . $_SERVER["PHP_SELF"] . '?socid=' . $object->id);
            exit();
        }
    }
}


/*
 *	View
 */

$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('',$langs->trans("ThirdParty"),$help_url);

$form = new Form($db);

$head = societe_prepare_head($object);

$form=new Form($db);

dol_fiche_head($head, 'companyrelationships', $langs->trans("ThirdParty"), -1, 'company');

$formconfirm = '';

// watcher form confirm
$formconfirm = companyrelationships_formconfirm_relation_thirdparty($db, $object, $companyrelationships, CompanyRelationships::RELATION_TYPE_WATCHER);

// Print form confirm
print $formconfirm;

$linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

dol_banner_tab($object, 'socid', $linkback, ($user->societe_id?0:1), 'rowid', 'nom');

print '<div class="fichecenter">';

print '<div class="underbanner clearboth"></div>';
print '<table class="border centpercent">';

print '<tr>';
print '<td class="titlefield">'.$langs->trans('CustomerCode').'</td><td'.(empty($conf->global->SOCIETE_USEPREFIX)?' colspan="3"':'').'>';
print $object->code_client;
if ($object->check_codeclient() <> 0) print ' '.$langs->trans("WrongCustomerCode");
print '</td>';
if (! empty($conf->global->SOCIETE_USEPREFIX))  // Old not used prefix field
{
   print '<td>'.$langs->trans('Prefix').'</td><td>'.$object->prefix_comm.'</td>';
}
print '</td>';
print '</tr>';

// watcher relation thirdparty
companyrelationships_show_relation_thirdparty($db, $object, $companyrelationships, CompanyRelationships::RELATION_TYPE_WATCHER);

// watcher public space availability
companyrelationships_show_relation_psa($db, $object, $companyrelationships, CompanyRelationships::RELATION_TYPE_WATCHER);

print '</table>';
print "</div>\n";

dol_fiche_end();

/*
 * List of principal companies
 *
 */
companyrelationships_show_companyrelationships($conf, $langs, $db, $object, 0);

print '<br>';

/*
 * List of principal companies
 *
 */
companyrelationships_show_companyrelationships($conf, $langs, $db, $object, 1);

llxFooter();
$db->close();
