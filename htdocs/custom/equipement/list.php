<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne           <eric.seigne@ryxeo.com>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2013 Regis Houssin         <regis.houssin@capnetworks.com>
 * Copyright (C) 2006      Andre Cianfarani      <acianfa@free.fr>
 * Copyright (C) 2010-2011 Juanjo Menent         <jmenent@2byte.es>
 * Copyright (C) 2010-2011 Philippe Grand        <philippe.grand@atoo-net.com>
 * Copyright (C) 2012      Christophe Battarel   <christophe.battarel@altairis.fr>
 * Copyright (C) 2013      Cédric Salvador       <csalvador@gpcsolutions.fr>
 * Copyright (C) 2015      Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2016      Ferran Marcet	     <fmarcet@2byte.es>
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
 *	\file       	htdocs/comm/propal/list.php
 *	\ingroup    	propal
 *	\brief      	Page of commercial proposals card and list
 */

$res = @include("../main.inc.php");                    // For root directory
if (!$res && file_exists($_SERVER['DOCUMENT_ROOT'] . "/main.inc.php"))
    $res = @include($_SERVER['DOCUMENT_ROOT'] . "/main.inc.php"); // Use on dev env only
if (!$res) $res = @include("../../main.inc.php");        // For "custom" directory
require_once DOL_DOCUMENT_ROOT . "/product/class/product.class.php";
require_once DOL_DOCUMENT_ROOT . "/product/stock/class/entrepot.class.php";
require_once DOL_DOCUMENT_ROOT . "/compta/facture/class/facture.class.php";
require_once DOL_DOCUMENT_ROOT . "/fourn/class/fournisseur.commande.class.php";
require_once DOL_DOCUMENT_ROOT . "/fourn/class/fournisseur.facture.class.php";
require_once DOL_DOCUMENT_ROOT . "/core/lib/date.lib.php";
require_once DOL_DOCUMENT_ROOT . "/core/class/html.form.class.php";

dol_include_once('/equipement/class/equipement.class.php');
dol_include_once('/equipement/core/lib/equipement.lib.php');

$langs->load("companies");
$langs->load("equipement@equipement");

$productid = GETPOST('productid', 'int');
$equipementid = GETPOST('id', 'int');

$action = GETPOST('action', 'alpha');
$massaction = GETPOST('massaction', 'alpha');
$show_files = GETPOST('show_files', 'int');
$confirm = GETPOST('confirm', 'alpha');
$toselect = GETPOST('toselect', 'array');

$search_ref = GETPOST('search_ref', 'alpha');
$search_refProduct = GETPOST('search_refProduct', 'alpha');
$search_labelProduct = GETPOST('search_labelProduct', 'alpha');
$search_numversion = GETPOST('search_numversion', 'alpha');
$search_company_fourn = GETPOST('search_company_fourn', 'alpha');
$search_reffact_fourn = GETPOST('search_reffact_fourn', 'alpha');
$search_reforder_fourn = GETPOST('search_reforder_fourn', 'alpha');
$search_company_client = GETPOST('search_company_client', 'alpha');
$search_reffact_client = GETPOST('search_reffact_client', 'alpha');
$search_note_private = GETPOST('search_note_private', 'alpha');
$search_entrepot = GETPOST('search_entrepot', 'alpha');
$search_etatequipement = GETPOST('search_etatequipement', 'alpha');
$viewstatut = GETPOST('viewstatut', 'alpha');
$optioncss = GETPOST('optioncss', 'alpha');
if ($search_entrepot == "") $search_entrepot = "-1";
if ($search_etatequipement == "") $search_etatequipement = "-1";
if ($viewstatut == "") $viewstatut = "-1";

$sall = GETPOST('sall', 'alphanohtml');

$limit = GETPOST('limit') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOST("page", 'int');
if (empty($page) || $page == -1) {
    $page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) $sortfield = 'e.ref';
if (!$sortorder) $sortorder = 'ASC';

// Security check
if (!empty($user->societe_id)) $socid = $user->societe_id;
$result = restrictedArea($user, 'equipement', $equipementid, 'equipement');

$diroutputmassaction = $conf->equipement->dir_output . '/temp/massgeneration/' . $user->id;

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('equipementlist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('equipement');
$search_array_options = $extrafields->getOptionalsFromPost($extralabels, '', 'search_');

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
    'e.ref' => 'Ref',
    'p.ref' => 'RefProduct',
    'p.label' => 'ProductDescription',
    'sfou.nom' => "SupplierName",
    'scli.nom' => 'CustomerName',
    'f.ref' => 'CustomerInvoiceRef',
    'ff.ref' => 'SupplierInvoiceRef',
    'e.datec' => 'DateCreate',
    'e.dateo' => 'DateOpen',
    'e.datee' => 'DateClose',
    'e.dated' => 'DateDluo',
);
if (empty($user->socid)) $fieldstosearchall["e.note_private"] = "NotePrivate";


$checkedtypetiers = 0;
$arrayfields = array(
    'e.ref' => array('label' => $langs->trans("Ref"), 'checked' => 1),
    'e.description' => array('label' => $langs->trans("Description"), 'checked' => 0),

    'p.ref' => array('label' => $langs->trans("RepProduct"), 'checked' => 1),
    'p.label' => array('label' => $langs->trans("LabelProduct"), 'checked' => 0,),

    'e.quantity' => array('label' => $langs->trans("Qty"), 'checked' => 0),
    'e.unitweight' => array('label' => $langs->trans("UnitWeight"), 'checked' => 0),
    'e.numversion' => array('label' => $langs->trans("VersionName"), 'checked' => 0, 'position' => 500),

    'ent.label' => array('label' => $langs->trans("Warehouse"), 'checked' => 1),

    'sfou.nom' => array('label' => $langs->trans("SupplierName"), 'checked' => 0),
    'cf.ref' => array('label' => $langs->trans("SupplierOrderRef"), 'checked' => 1, 'enabled' => $user->rights->fournisseur->commande->lire),
    'ff.ref' => array('label' => $langs->trans("SupplierInvoiceRef"), 'checked' => 1, 'enabled' => $user->rights->fournisseur->facture->lire),

    'scli.nom' => array('label' => $langs->trans("CustomerName"), 'checked' => 0),
    'f.ref' => array('label' => $langs->trans("FacNumber"), 'checked' => 1, 'enabled' => $user->rights->facture->lire),

    'e.dateo' => array('label' => $langs->trans("DateOpen"), 'checked' => 0, 'position' => 500),
    'e.datee' => array('label' => $langs->trans("DateEnd"), 'checked' => 0, 'position' => 500),
    'e.dated' => array('label' => $langs->trans("Dated"), 'checked' => 0, 'position' => 500, 'enabled' => !empty($conf->global->EQUIPEMENT_USEDLUODATE)),
    'ee.libelle' => array('label' => $langs->trans("EquipementStatut"), 'checked' => 0, 'position' => 500),

    'e.note_private' => array('label' => $langs->trans("NotePrivate"), 'checked' => 0, 'position' => 500),

    'e.datec' => array('label' => $langs->trans("DateCreate"), 'checked' => 1, 'position' => 500),
    'e.tms' => array('label' => $langs->trans("DateModificationShort"), 'checked' => 0, 'position' => 500),
    'e.fk_statut' => array('label' => $langs->trans("Status"), 'checked' => 1, 'position' => 1000),
);
// Extra fields
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) {
    foreach ($extrafields->attribute_label as $key => $val) {
        //---------------------------------------------------------------
        // Modification - Read hidden extrafields - Open-Dsi - Begin
        if (!empty($extrafields->attribute_list[$key]) || $user->admin || !empty($user->rights->user->extrafields->read)) $arrayfields["ef." . $key] = array('label' => $extrafields->attribute_label[$key], 'checked' => (($extrafields->attribute_list[$key] < 0) ? 0 : 1), 'position' => $extrafields->attribute_pos[$key], 'enabled' => (abs($extrafields->attribute_list[$key]) != 3 && $extrafields->attribute_perms[$key]));
        // Modification - Read hidden extrafields - Open-Dsi - End
        //---------------------------------------------------------------
    }
}

$object = new Equipement($db);    // To be passed as parameter of executeHooks that need


/*
 * Actions
 */

if (GETPOST('cancel')) {
    $action = 'list';
    $massaction = '';
}
if (!GETPOST('confirmmassaction') && $massaction != 'presend' && $massaction != 'confirm_presend') {
    $massaction = '';
}

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

include DOL_DOCUMENT_ROOT . '/core/actions_changeselectedfields.inc.php';

// Do we click on purge search criteria ?
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
{
    $sall = '';
    $search_ref = '';
    $search_refProduct = '';
    $search_labelProduct = '';
    $search_numversion = '';
    $search_company_fourn = '';
    $search_reffact_fourn = '';
    $search_company_client = '';
    $search_reffact_client = '';
    $search_entrepot = -1;
    $search_etatequipement = -1;
    $viewstatut = -1;
    $toselect = '';
    $search_array_options = array();
}

if (empty($reshook)) {
    if (GETPOST("updatecheck") == $langs->trans("Update")) {
        foreach ($toselect as $toselectid) {
            // on récupère les anciennes valeurs
            $equipementstatic = new Equipement($db);
            $equipementstatic->fetch($toselectid);
            // on met à jour que si la case est cochée

            if (GETPOST("chk_statut"))
                $equipementstatic->fk_statut = GETPOST("update_statut");
            else
                $equipementstatic->fk_statut = -1;

            if (GETPOST("chk_etatequipement"))
                $equipementstatic->fk_etatequipement = GETPOST("update_etatequipement");
            else
                $equipementstatic->fk_etatequipement = 0;

            if (GETPOST("chk_soc_client"))
                $equipementstatic->fk_soc_client = GETPOST("update_soc_client");
            else
                $equipementstatic->fk_soc_client = 0;

            if (GETPOST("chk_entrepot"))
                $equipementstatic->fk_etatentrepot = GETPOST("update_entrepot");
            else
                $equipementstatic->fk_etatentrepot = 0;

            if (GETPOST("chk_datee")) {
                if (GETPOST("datee"))
                    $equipementstatic->datee = dol_mktime(
                        '23', '59', '59',
                        GETPOST("dateemonth"), GETPOST("dateeday"), GETPOST("dateeyear")
                    );
                else
                    $equipementstatic->datee = -1;
            } else
                $equipementstatic->fk_datee = 0;

            if (GETPOST("chk_dateo")) {
                if (GETPOST("dateo"))
                    $equipementstatic->dateo = dol_mktime(
                        '23', '59', '59',
                        GETPOST("dateomonth"), GETPOST("dateoday"), GETPOST("dateoyear")
                    );
                else
                    $equipementstatic->dateo = -1;
            } else
                $equipementstatic->fk_dateo = 0;

            //var_dump($equipementstatic);
            // on met à jour l'équipement
            $equipementstatic->updateInfos($user, GETPOST("update_entrepotmove"));
        }
    }

//    $objectclass = 'Equipement';
//    $objectlabel = 'Equipements';
//    $permtoread = $user->rights->equipement->lire;
//    $permtodelete = $user->rights->equipement->supprimer;
//    $uploaddir = $conf->equipement->dir_output;
//    include DOL_DOCUMENT_ROOT . '/core/actions_massactions.inc.php';
}


/*
 * View
 */

$now = dol_now();

$form = new Form($db);

//$help_url='EN:Equipment_EN|FR:Equipment_FR|ES:Equipment_ES';
$help_url = '';
llxHeader('', $langs->trans('ListOfEquipements'), $help_url);

$sql = 'SELECT';
if ($sall) $sql = 'SELECT DISTINCT';
$sql .= " e.ref, e.rowid as equipementid, e.fk_statut, e.fk_product, p.ref as refproduit, p.label as labelproduit,";
$sql .= " e.fk_entrepot, e.quantity, e.tms, e.numversion, e.description, e.note_private,";
$sql .= " e.fk_soc_fourn, sfou.nom as CompanyFourn, e.fk_commande_fourn, cf.ref as refCommFourn, e.fk_facture_fourn, ff.ref as refFactureFourn,";
$sql .= " e.fk_soc_client, scli.nom as CompanyClient, e.fk_facture, f.ref as refFacture,";
$sql .= " e.datec, e.datee, e.dateo, e.dated, ee.libelle as etatequiplibelle, ";
/// Add fields from extrafields
if (!empty($extrafields->attributes[$object->table_element]['label']))
foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) $sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? "ef.".$key.' as options_'.$key.', ' : '');
// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters);    // Note that $action and $object may have been modified by hook
$sql .= preg_replace('/^,/', '', $hookmanager->resPrint);
$sql = preg_replace('/,\s*$/', '', $sql);
$sql .= " FROM " . MAIN_DB_PREFIX . "equipement as e";
if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$object->table_element."_extrafields as ef on (e.rowid = ef.fk_object)";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_equipement_etat as ee on e.fk_etatequipement = ee.rowid";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as sfou on e.fk_soc_fourn = sfou.rowid";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "entrepot as ent on e.fk_entrepot = ent.rowid";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as scli on e.fk_soc_client = scli.rowid";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "facture as f on e.fk_facture = f.rowid";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "commande_fournisseur as cf on e.fk_commande_fourn = cf.rowid";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "facture_fourn as ff on e.fk_facture_fourn = ff.rowid";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product as p on e.fk_product = p.rowid";
$sql .= " WHERE e.entity IN (" . getEntity('equipement') . ")";
if ($search_ref) $sql .= " AND e.ref like '%" . $db->escape($search_ref) . "%'";
if ($search_labelProduct) $sql .= " AND p.label like '%" . $db->escape($search_labelProduct) . "%'";
if ($search_refProduct) $sql .= " AND p.ref like '%" . $db->escape($search_refProduct) . "%'";
if ($search_numversion) $sql .= " AND e.numversion like '%" . $db->escape($search_numversion) . "%'";
if ($search_company_fourn) $sql .= " AND sfou.nom like '%" . $db->escape($search_company_fourn) . "%'";
if ($search_reffact_fourn) $sql .= " AND ff.ref like '%" . $db->escape($search_reffact_fourn) . "%'";
if ($search_reforder_fourn) $sql .= " AND cf.ref like '%" . $db->escape($search_reforder_fourn) . "%'";
if ($search_company_client) $sql .= " AND scli.nom like '%" . $db->escape($search_company_client) . "%'";
if ($search_reffact_client) $sql .= " AND f.ref like '%" . $db->escape($search_reffact_client) . "%'";
if ($search_note_private) $sql .= " AND e.note_private like '%" . $db->escape($search_note_private) . "%'";
if ($search_entrepot >= 0) $sql .= " AND ent.rowid =" . $search_entrepot;
if ($search_etatequipement >= 0) $sql .= " AND e.fk_etatequipement =" . $search_etatequipement;
if ($sall) {
    $sql .= natural_search(array_keys($fieldstosearchall), $sall);
}
if ($viewstatut >= 0) $sql .= " AND e.fk_statut =" . $viewstatut;
// Add where from extra fields
include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_sql.tpl.php';

// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters);    // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

$sql .= $db->order($sortfield, $sortorder);

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
    $result = $db->query($sql);
    $nbtotalofrecords = $db->num_rows($result);
}

$sql .= $db->plimit($limit + 1, $offset);

$resql = $db->query($sql);
if ($resql) {
    $objectstatic = new Equipement($db);
    $status_array = array(
        0 => $objectstatic->LibStatut(0),
        1 => $objectstatic->LibStatut(1),
        2 => $objectstatic->LibStatut(2),
    );

    $title = $langs->trans('ListOfEquipements');

    $num = $db->num_rows($resql);

    $arrayofselected = is_array($toselect) ? $toselect : array();

    $param = '&viewstatut=' . urlencode($viewstatut);
    if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage=' . urlencode($contextpage);
    if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit=' . urlencode($limit);
    if ($sall) $param .= '&sall=' . urlencode($sall);
    if ($search_ref) $param .= "&search_ref=" . urlencode($search_ref);
    if ($search_refProduct) $param .= "&search_refProduct=" . urlencode($search_refProduct);
    if ($search_labelProduct) $param .= "&search_labelProduct=" . urlencode($search_labelProduct);
    if ($search_numversion) $param .= "&search_numversion=" . urlencode($search_numversion);
    if ($search_company_fourn) $param .= "&search_company_fourn=" . urlencode($search_company_fourn);
    if ($search_reffact_fourn) $param .= "&search_reffact_fourn=" . urlencode($search_reffact_fourn);
    if ($search_reforder_fourn) $param .= "&search_reforder_fourn=" . urlencode($search_reforder_fourn);
    if ($search_entrepot) $param .= "&search_entrepot=" . urlencode($search_entrepot);
    if ($search_company_client) $param .= "&search_company_client=" . urlencode($search_company_client);
    if ($search_reffact_client) $param .= "&search_reffact_client=" . urlencode($search_reffact_client);
    if ($search_note_private) $param .= "&search_note_private=" . urlencode($search_note_private);
    if ($search_etatequipement >= 0) $param .= "&search_etatequipement=" . urlencode($search_etatequipement);
    if ($viewstatut >= 0) $param .= "&viewstatut=" . urlencode($viewstatut);
    if ($optioncss != '') $param .= '&optioncss=' . urlencode($optioncss);

    // Add $param from extra fields
    include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_param.tpl.php';

    // List of mass actions available
    $arrayofmassactions = array(//	    'builddoc'=>$langs->trans("PDFMerge"),
    );
//	if ($user->rights->equipment->supprimer) $arrayofmassactions['delete']=$langs->trans("Delete");
    $massactionbutton = $form->selectMassAction('', $arrayofmassactions);

    // Lignes des champs de filtre
    print '<form method="POST" id="searchFormList" action="' . $_SERVER["PHP_SELF"] . '">';
    if ($optioncss != '') print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
    print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
    print '<input type="hidden" name="action" value="list">';
    print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
    print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
    print '<input type="hidden" name="page" value="' . $page . '">';

    print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'title_commercial.png', 0, '', '', $limit);

    if ($sall) {
        foreach ($fieldstosearchall as $key => $val) $fieldstosearchall[$key] = $langs->trans($val);
        print $langs->trans("FilterOnInto", $sall) . join(', ', $fieldstosearchall);
    }

    $i = 0;

    $moreforfilter = '';

    $parameters = array();
    $reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters);    // Note that $action and $object may have been modified by hook
    if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
    else $moreforfilter = $hookmanager->resPrint;

    if (!empty($moreforfilter)) {
        print '<div class="liste_titre liste_titre_bydiv centpercent">';
        print $moreforfilter;
        print '</div>';
    }

    $varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
    $selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);    // This also change content of $arrayfields
    /*if ($massactionbutton)*/ $selectedfields .= $form->showCheckAddButtons('checkforselect', 1);

    print '<div class="div-table-responsive">';
    print '<table class="tagtable liste' . ($moreforfilter ? " listwithfilterbefore" : "") . '">' . "\n";

    print '<tr class="liste_titre_filter">';
    if (!empty($arrayfields['e.ref']['checked'])) {
        print '<td class="liste_titre">';
        print '<input class="flat" size="6" type="text" name="search_ref" value="' . $search_ref . '">';
        print '</td>';
    }
    if (!empty($arrayfields['e.description']['checked'])) {
        print '<td class="liste_titre">';
        print '</td>';
    }
    if (!empty($arrayfields['p.ref']['checked'])) {
        print '<td class="liste_titre">';
        print '<input class="flat" type="text" size="8" name="search_refProduct" value="' . $search_refProduct . '">';
        print '</td>';
    }
    if (!empty($arrayfields['p.label']['checked'])) {
        print '<td class="liste_titre">';
        print '<input class="flat" type="text" size="10" name="search_labelProduct" value="' . $search_labelProduct . '">';
        print '</td>';
    }
    if (!empty($arrayfields['e.quantity']['checked'])) {
        print '<td class="liste_titre">';
        print '</td>';
    }
    if (!empty($arrayfields['e.unitweight']['checked'])) {
        print '<td class="liste_titre">';
        print '</td>';
    }
    if (!empty($arrayfields['e.numversion']['checked'])) {
        print '<td class="liste_titre">';
        print '<input class="flat" type="text" size="8" name="search_numversion" value="' . $search_numversion . '">';
        print '</td>';
    }
    if (!empty($arrayfields['ent.label']['checked'])) {
        print '<td class="liste_titre">';
        select_entrepot($search_entrepot, 'search_entrepot', 1, 1, 0, 0);
        print '</td>';
    }
    if (!empty($arrayfields['sfou.nom']['checked'])) {
        print '<td class="liste_titre">';
        print '<input class="flat" type="text" size="10" name="search_company_fourn" value="' . $search_company_fourn . '">';
        print '</td>';
    }
    if (!empty($arrayfields['cf.ref']['checked'])) {
        print '<td class="liste_titre">';
        print '<input class="flat" type="text" size="8" name="search_reforder_fourn" value="' . $search_reforder_fourn . '">';
        print '</td>';
    }
    if (!empty($arrayfields['ff.ref']['checked'])) {
        print '<td class="liste_titre">';
        print '<input class="flat" type="text" size="8" name="search_reffact_fourn" value="' . $search_reffact_fourn . '">';
        print '</td>';
    }
    if (!empty($arrayfields['scli.nom']['checked'])) {
        print '<td class="liste_titre">';
        print '<input class="flat" type="text" size="8" name="search_company_client" value="' . $search_company_client . '">';
        print '</td>';
    }
    if (!empty($arrayfields['f.ref']['checked'])) {
        print '<td class="liste_titre">';
        print '<input class="flat" type="text" size="8" name="search_reffact_client" value="' . $search_reffact_client . '">';
        print '</td>';
    }
    if (!empty($arrayfields['e.dateo']['checked'])) {
        print '<td class="liste_titre">';
//        print '<input class="flat" type="text" size="8" name="search_dateo" value="' . $search_dateo . '">';
        print '</td>';
    }
    if (!empty($arrayfields['e.datee']['checked'])) {
        print '<td class="liste_titre">';
//        print '<input class="flat" type="text" size="8" name="search_datee" value="' . $search_datee . '">';
        print '</td>';
    }
    if (!empty($arrayfields['e.dated']['checked'])) {
        print '<td class="liste_titre">';
//        print '<input class="flat" type="text" size="8" name="search_dated" value="' . $search_dated . '">';
        print '</td>';
    }
    if (!empty($arrayfields['ee.libelle']['checked'])) {
        print '<td class="liste_titre">';
        select_equipement_etat($search_etatequipement, 'search_etatequipement', 1, 1);
        print '</td>';
    }
    if (!empty($arrayfields['e.note_private']['checked'])) {
        print '<td class="liste_titre">';
        print '<input class="flat" type="text" size="8" name="search_note_private" value="' . $search_note_private . '">';
        print '</td>';
    }
    // Extra fields
    include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_input.tpl.php';

    // Fields from hook
    $parameters = array('arrayfields' => $arrayfields);
    $reshook = $hookmanager->executeHooks('printFieldListOption', $parameters);    // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;
    // Date creation
    if (!empty($arrayfields['e.datec']['checked'])) {
        print '<td class="liste_titre" align="center">';
//        print '<input class="flat" type="text" size="8" name="search_datec" value="' . $search_datec . '">';
        print '</td>';
    }
    // Date modification
    if (!empty($arrayfields['e.tms']['checked'])) {
        print '<td class="liste_titre" align="center">';
        print '</td>';
    }
    // Status
    if (!empty($arrayfields['e.fk_statut']['checked'])) {
        print '<td class="liste_titre maxwidthonsmartphone" align="right">';
        print $form->selectarray('viewstatut', $status_array, $viewstatut, 1);
        print '</td>';
    }
    // Action column
    print '<td class="liste_titre" align="middle">';
    $searchpicto = $form->showFilterButtons();
    print $searchpicto;
    print '</td>';

    print "</tr>\n";


    // Fields title
    print '<tr class="liste_titre">';
    if (!empty($arrayfields['e.ref']['checked'])) print_liste_field_titre($arrayfields['e.ref']['label'], $_SERVER["PHP_SELF"], 'e.ref', '', $param, '', $sortfield, $sortorder);
    if (!empty($arrayfields['e.description']['checked'])) print_liste_field_titre($arrayfields['e.description']['label'], $_SERVER["PHP_SELF"], 'e.description', '', $param, '', $sortfield, $sortorder);
    if (!empty($arrayfields['p.ref']['checked'])) print_liste_field_titre($arrayfields['p.ref']['label'], $_SERVER["PHP_SELF"], 'p.ref', '', $param, '', $sortfield, $sortorder);
    if (!empty($arrayfields['p.label']['checked'])) print_liste_field_titre($arrayfields['p.label']['label'], $_SERVER["PHP_SELF"], 'p.label', '', $param, '', $sortfield, $sortorder);
    if (!empty($arrayfields['e.quantity']['checked'])) print_liste_field_titre($arrayfields['e.quantity']['label'], $_SERVER["PHP_SELF"], 'e.quantity', '', $param, '', $sortfield, $sortorder);
    if (!empty($arrayfields['e.unitweight']['checked'])) print_liste_field_titre($arrayfields['e.unitweight']['label'], $_SERVER["PHP_SELF"], "e.unitweight", "", $param, '', $sortfield, $sortorder);
    if (!empty($arrayfields['e.numversion']['checked'])) print_liste_field_titre($arrayfields['e.numversion']['label'], $_SERVER["PHP_SELF"], "e.numversion", "", $param, '', $sortfield, $sortorder);
    if (!empty($arrayfields['ent.label']['checked'])) print_liste_field_titre($arrayfields['ent.label']['label'], $_SERVER["PHP_SELF"], "ent.label", "", $param, '', $sortfield, $sortorder);
    if (!empty($arrayfields['sfou.nom']['checked'])) print_liste_field_titre($arrayfields['sfou.nom']['label'], $_SERVER["PHP_SELF"], "sfou.nom", "", $param, '', $sortfield, $sortorder);
    if (!empty($arrayfields['cf.ref']['checked'])) print_liste_field_titre($arrayfields['cf.ref']['label'], $_SERVER["PHP_SELF"], "cf.ref", "", $param, '', $sortfield, $sortorder);
    if (!empty($arrayfields['ff.ref']['checked'])) print_liste_field_titre($arrayfields['ff.ref']['label'], $_SERVER["PHP_SELF"], "ff.ref", "", $param, '', $sortfield, $sortorder);
    if (!empty($arrayfields['scli.nom']['checked'])) print_liste_field_titre($arrayfields['scli.nom']['label'], $_SERVER["PHP_SELF"], "scli.nom", "", $param, '', $sortfield, $sortorder);
    if (!empty($arrayfields['f.ref']['checked'])) print_liste_field_titre($arrayfields['f.ref']['label'], $_SERVER["PHP_SELF"], "f.ref", "", $param, '', $sortfield, $sortorder);
    if (!empty($arrayfields['e.dateo']['checked'])) print_liste_field_titre($arrayfields['e.dateo']['label'], $_SERVER["PHP_SELF"], "e.dateo", "", $param, 'align="center" class="nowrap"', $sortfield, $sortorder);
    if (!empty($arrayfields['e.datee']['checked'])) print_liste_field_titre($arrayfields['e.datee']['label'], $_SERVER["PHP_SELF"], "e.datee", "", $param, 'align="center" class="nowrap"', $sortfield, $sortorder);
    if (!empty($arrayfields['e.dated']['checked'])) print_liste_field_titre($arrayfields['e.dated']['label'], $_SERVER["PHP_SELF"], "e.dated", "", $param, 'align="center" class="nowrap"', $sortfield, $sortorder);
    if (!empty($arrayfields['ee.libelle']['checked'])) print_liste_field_titre($arrayfields['ee.libelle']['label'], $_SERVER["PHP_SELF"], "ee.libelle", "", $param, '', $sortfield, $sortorder);
    if (!empty($arrayfields['e.note_private']['checked'])) print_liste_field_titre($arrayfields['e.note_private']['label'], $_SERVER["PHP_SELF"], "e.note_private", "", $param, '', $sortfield, $sortorder);
    // Extra fields
    include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_title.tpl.php';
    // Hook fields
    $parameters = array('arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder);
    $reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters);    // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;
    if (!empty($arrayfields['e.datec']['checked'])) print_liste_field_titre($arrayfields['e.datec']['label'], $_SERVER["PHP_SELF"], "e.datec", "", $param, 'align="center" class="nowrap"', $sortfield, $sortorder);
    if (!empty($arrayfields['e.tms']['checked'])) print_liste_field_titre($arrayfields['e.tms']['label'], $_SERVER["PHP_SELF"], "e.tms", "", $param, 'align="center" class="nowrap"', $sortfield, $sortorder);
    if (!empty($arrayfields['e.fk_statut']['checked'])) print_liste_field_titre($arrayfields['e.fk_statut']['label'], $_SERVER["PHP_SELF"], "e.fk_statut", "", $param, 'align="right"', $sortfield, $sortorder);
    print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', $sortfield, $sortorder, 'maxwidthsearch ');
    print '</tr>' . "\n";

    $reflist = "";
    $separatorlist = $conf->global->EQUIPEMENT_SEPARATORLIST;
    $separatorlist = ($separatorlist ? $separatorlist : ";");
    if ($separatorlist == "__N__")
        $separatorlist = "\n";
    if ($separatorlist == "__B__")
        $separatorlist = "\b";

    $now = dol_now();
    $i = 0;
    $totalarray = array();
    while ($i < min($num, $limit)) {
        $obj = $db->fetch_object($resql);

        $reflist .= $obj->ref . $separatorlist;

        print '<tr class="oddeven">';

        if (!empty($arrayfields['e.ref']['checked'])) {
            print '<td class="nowrap">';
            $objectstatic->fetch($obj->equipementid);
            print $objectstatic->getNomUrl(1);
            print $obj->increment;
            print "</td>\n";
            if (!$i) $totalarray['nbfield']++;
        }

        if (!empty($arrayfields['e.description']['checked'])) {
            // Customer ref
            print '<td class="nocellnopadd nowrap">';
            print $obj->description;
            print '</td>';
            if (!$i) $totalarray['nbfield']++;
        }

        if (!empty($arrayfields['p.ref']['checked'])) {
            // toujours un produit associé à un équipement // cela va changer
            print '<td class="nocellnopadd">';
            if ($obj->fk_product > 0) {
                $productstatic = new Product($db);
                $productstatic->fetch($obj->fk_product);
                print $productstatic->getNomUrl(1);
            }
            print '</td>';
            if (!$i) $totalarray['nbfield']++;
        }

        if (!empty($arrayfields['p.label']['checked'])) {
            // toujours un produit associé à un équipement
            print '<td class="nocellnopadd">';
            print $obj->labelproduit;
            print '</td>';
            if (!$i) $totalarray['nbfield']++;
        }

        if (!empty($arrayfields['e.quantity']['checked'])) {
            print '<td class="nocellnopadd">';
            if ($obj->quantity != 0) {
                print $obj->quantity;
            }
            print '</td>';
            if (!$i) $totalarray['nbfield']++;
        }

        if (!empty($arrayfields['e.unitweight']['checked'])) {
            print '<td class="nocellnopadd">';
            if ($obj->unitweight != 0) {
                print $obj->unitweight;
            }
            print '</td>';
            if (!$i) $totalarray['nbfield']++;
        }

        if (!empty($arrayfields['p.numversion']['checked'])) {
            print '<td class="nocellnopadd">';
            print $obj->numversion;
            print '</td>';
            if (!$i) $totalarray['nbfield']++;
        }

        if (!empty($arrayfields['ent.label']['checked'])) {
            print '<td class="nocellnopadd">';
            if ($obj->fk_entrepot > 0) {
                $entrepotstatic = new Entrepot($db);
                $entrepotstatic->fetch($obj->fk_entrepot);
                print $entrepotstatic->getNomUrl(1);
            }
            print '</td>';
            if (!$i) $totalarray['nbfield']++;
        }

        if (!empty($arrayfields['sfou.nom']['checked'])) {
            print '<td class="nocellnopadd">';
            if ($obj->fk_soc_fourn > 0) {
                $socfourn = new Societe($db);
                $socfourn->fetch($obj->fk_soc_fourn);
                print $socfourn->getNomUrl(1);
            }
            print '</td>';
            if (!$i) $totalarray['nbfield']++;
        }

        if (!empty($arrayfields['cf.ref']['checked'])) {
            print '<td class="nocellnopadd">';
            if ($obj->fk_commande_fourn > 0) {
                $commfournstatic = new CommandeFournisseur($db);
                $commfournstatic->fetch($obj->fk_commande_fourn);
                print $commfournstatic->getNomUrl(1);
            }
            print '</td>';
            if (!$i) $totalarray['nbfield']++;
        }

        if (!empty($arrayfields['ff.ref']['checked'])) {
            print '<td class="nocellnopadd">';
            if ($obj->fk_facture_fourn > 0) {
                $factfournstatic = new FactureFournisseur($db);
                $factfournstatic->fetch($obj->fk_facture_fourn);
                print $factfournstatic->getNomUrl(1);
            }
            print '</td>';
            if (!$i) $totalarray['nbfield']++;
        }

        if (!empty($arrayfields['scli.nom']['checked'])) {
            print '<td class="nocellnopadd">';
            if ($obj->fk_soc_client > 0) {
                $soc = new Societe($db);
                $soc->fetch($obj->fk_soc_client);
                print $soc->getNomUrl(1);
            }
            print '</td>';
            if (!$i) $totalarray['nbfield']++;
        }

        if (!empty($arrayfields['f.ref']['checked'])) {
            print '<td class="nocellnopadd">';
            if ($obj->fk_facture > 0) {
                $facturestatic = new Facture($db);
                $facturestatic->fetch($obj->fk_facture);
                print $facturestatic->getNomUrl(1);
            }
            print '</td>';
            if (!$i) $totalarray['nbfield']++;
        }

        if (!empty($arrayfields['e.dateo']['checked'])) {
            print '<td align="center" class="nowrap">';
            print dol_print_date($db->jdate($obj->dateo), 'day');
            print '</td>';
            if (!$i) $totalarray['nbfield']++;
        }

        if (!empty($arrayfields['e.datee']['checked'])) {
            print '<td align="center" class="nowrap">';
            print dol_print_date($db->jdate($obj->datee), 'day');
            print '</td>';
            if (!$i) $totalarray['nbfield']++;
        }

        if (!empty($arrayfields['e.dated']['checked'])) {
            print '<td align="center" class="nowrap">';
            print dol_print_date($db->jdate($obj->dated), 'day');
            print '</td>';
            if (!$i) $totalarray['nbfield']++;
        }

        if (!empty($arrayfields['ee.libelle']['checked'])) {
            print '<td align="center" class="nowrap">';
            if ($obj->etatequiplibelle) {
                print $langs->trans($obj->etatequiplibelle);
            }
            print '</td>';
            if (!$i) $totalarray['nbfield']++;
        }

        if (!empty($arrayfields['e.note_private']['checked'])) {
            print '<td align="center" class="nowrap">';
            $tmpcontent = dol_htmlentitiesbr($obj->note_private);
            if (!empty($conf->global->MAIN_DISABLE_NOTES_TAB)) {
                $firstline = preg_replace('/<br>.*/', '', $tmpcontent);
                $firstline = preg_replace('/[\n\r].*/', '', $firstline);
                $tmpcontent = $firstline . ((strlen($firstline) != strlen($tmpcontent)) ? '...' : '');
            }
            print $tmpcontent;
            print '</td>';
            if (!$i) $totalarray['nbfield']++;
        }

        // Extra fields
        include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_print_fields.tpl.php';
        // Fields from hook
        $parameters = array('arrayfields' => $arrayfields, 'obj' => $obj);
        $reshook = $hookmanager->executeHooks('printFieldListValue', $parameters);    // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;
        // Date creation
        if (!empty($arrayfields['e.datec']['checked'])) {
            print '<td align="center" class="nowrap">';
            print dol_print_date($db->jdate($obj->datec), 'dayhour');
            print '</td>';
            if (!$i) $totalarray['nbfield']++;
        }
        // Date modification
        if (!empty($arrayfields['e.tms']['checked'])) {
            print '<td align="center" class="nowrap">';
            print dol_print_date($db->jdate($obj->tms), 'dayhour');
            print '</td>';
            if (!$i) $totalarray['nbfield']++;
        }
        // Status
        if (!empty($arrayfields['e.fk_statut']['checked'])) {
            print '<td align="right" class="nowrap">' . $objectstatic->LibStatut($obj->fk_statut, 5) . '</td>';
            if (!$i) $totalarray['nbfield']++;
        }
        // Action column
        print '<td class="nowrap" align="center">';
//        if ($massactionbutton || $massaction)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
//        {
            $selected = 0;
            if (in_array($obj->equipementid, $arrayofselected)) $selected = 1;
            print '<input id="cb' . $obj->equipementid . '" class="flat checkforselect" type="checkbox" name="toselect[]" value="' . $obj->equipementid . '"' . ($selected ? ' checked="checked"' : '') . '>';
//        }
        print '</td>';
        if (!$i) $totalarray['nbfield']++;

        print "</tr>\n";

        $i++;
    }

    $db->free($resql);

    $parameters = array('arrayfields' => $arrayfields, 'sql' => $sql);
    $reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters);    // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;

    print '</table>' . "\n";
    print '</div>' . "\n";

    print '<br>';
    print "<label id='showreflist'>";
    print img_picto("", "edit_add") . "&nbsp;" . $langs->trans("ShowRefEquipement") . "</label><br>";
    print '<textarea cols="80" id="reflist" style="display:none;" rows="' . ROWS_6 . '">' . $reflist . '</textarea>';
    print "<script>
        $(document).ready(function() {
            $('#showreflist').click(function() {  //on click
                $('#reflist').toggle();
            });
        });
    </script>";
    print '<br>';

    print_fiche_titre($langs->trans("EquipementMassChange"));
    print '<br>';
    print '<table class="border">';

    print "<tr>";
    print "<td align=left>" . $langs->trans("Entrepot") . "</td>";
    print "<td align=left>";
    print '<input type=checkbox name="chk_entrepot">&nbsp;';
    select_entrepot(''/*$update_entrepot*/, 'update_entrepot', 1, 1, 0, 1);
    print "</td>";
    print "</tr>";

    print "<tr>";
    print "<td align=left>" . $langs->trans("Customer") . "</td>";
    print "<td align=left>";
    print '<input type=checkbox name="chk_soc_client" >';
    print $form->select_company('', 'update_soc_client', '', 'SelectThirdParty', 1);
    print "</td>";
    print "</tr>";

    print "<tr>";
    print "<td align=left>" . $langs->trans("Dateo") . "</td>";
    print "<td align=left>";
    print '<input type=checkbox name="chk_dateo" >&nbsp;';
    print $form->select_date('', 'dateo', 0, 0, 1, "dateo");
    print "</td>";
    print "</tr>";

    print "<tr >";
    print "<td align=left>" . $langs->trans("Datee") . "</td>";
    print "<td align=left>";
    print '<input type=checkbox name="chk_datee" >&nbsp;';
    print $form->select_date('', 'datee', 0, 0, 1, "datee");
    print "</td>";
    print "</tr>";

    if (!empty($conf->global->EQUIPEMENT_USEDLUODATE)) {
        // Date DLUO
        print "<tr >";
        print "<td align=left>" . $langs->trans("DateDluo") . "</td>";
        print "<td align=left>";
        print '<input type=checkbox name="chk_dated" >&nbsp;';
        print $form->select_date('', 'dated', 0, 0, 1, "dated");
        print "</td>";
        print "</tr >";
    }

    print "<tr >";
    print "<td align=left>" . $langs->trans("EtatEquip") . "</td>";
    print "<td align=left>";
    print '<input type=checkbox name="chk_etatequipement" >&nbsp;';
    select_equipement_etat('', 'update_etatequipement', 1, 1);
    print "</td>";
    print "</tr>";

    print "<tr>";
    print "<td align=left >" . $langs->trans("Status") . "</td>";
    print "<td align=left>";
    print '<input type=checkbox name="chk_statut" >&nbsp;';
    print $form->selectarray('update_statut', $status_array, '', 1);
    print "</td>";
    print "</tr >";

    print "<tr>";
    print "<td align=center colspan=2>";
    print "<input type=submit name='updatecheck' value='" . $langs->trans("Update") . "'>";
    print "</td>";
    print "</tr>\n";

    //print '<tr class="liste_total"><td colspan="7" class="liste_total">'.$langs->trans("Total").'</td>';
    //print '<td align="right" nowrap="nowrap" class="liste_total">'.$i.'</td><td>&nbsp;</td>';
    //print '</tr>';

    print '</table>';

    print '</form>' . "\n";

//    if ($massaction == 'builddoc' || $action == 'remove_file' || $show_files) {
//        /*
//         * Show list of available documents
//         */
//        $urlsource = $_SERVER['PHP_SELF'] . '?sortfield=' . $sortfield . '&sortorder=' . $sortorder;
//        $urlsource .= str_replace('&amp;', '&', $param);
//
//        $filedir = $diroutputmassaction;
//        $genallowed = $user->rights->equipement->lire;
//        $delallowed = $user->rights->equipement->creer;
//
//        // TODO massfilesarea_equipements add support
//        print $formfile->showdocuments('massfilesarea_equipements', '', $filedir, $urlsource, 0, $delallowed, '', 1, 1, 0, 48, 1, $param, '', '');
//    } else {
//        print '<br><a name="show_files"></a><a href="' . $_SERVER["PHP_SELF"] . '?show_files=1' . $param . '#show_files">' . $langs->trans("ShowTempMassFilesArea") . '</a>';
//    }
} else {
    dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
