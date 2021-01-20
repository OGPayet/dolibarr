<?php
/* Copyright (C) 2001-2006  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@capnetworks.com>
 * Copyright (C) 2012-2016  Marcos Garca           <marcosgdf@gmail.com>
 * Copyright (C) 2013-2016	Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2013-2015  Raphal Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2013       Jean Heimburger         <jean@tiaris.info>
 * Copyright (C) 2013       Cdric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2013       Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2013       Adolfo segura           <adolfo.segura@gmail.com>
 * Copyright (C) 2015       Jean-Franois Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2016       Ferran Marcet		    <fmarcet@2byte.es>
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
 *  \file       htdocs/product/list.php
 *  \ingroup    produit
 *  \brief      Page to list products and services
 */
// Load Dolibarr environment
$res  = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res  = @include($_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php");
// Try main.inc.php into web root detected using web root caluclated from SCRIPT_FILENAME
$tmp  = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i    = strlen($tmp) - 1;
$j    = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
    $i--;
    $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) $res = @include(substr($tmp, 0, ($i + 1))."/main.inc.php");
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) $res = @include(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php");
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) $res = @include("../main.inc.php");
if (!$res && file_exists("../../main.inc.php")) $res = @include("../../main.inc.php");
if (!$res && file_exists("../../../main.inc.php")) $res = @include("../../../main.inc.php");
if (!$res && file_exists("../../../../main.inc.php")) $res = @include("../../../../main.inc.php");
if (!$res && file_exists("../../../../../main.inc.php")) $res = @include("../../../../../main.inc.php");
if (!$res) die("Include of main fails");


//if (! $user->rights->stock->read) accessforbidden();



require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
if (!empty($conf->categorie->enabled)) require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

$langs->load("products");
$langs->load("stocks");
$langs->load("suppliers");
$langs->load("companies");

if (!empty($conf->productbatch->enabled)) $langs->load("productbatch");

$langs->load("warehousechild@warehousechild");

$action     = GETPOST('action', 'alpha');
$massaction = GETPOST('massaction', 'alpha');
$show_files = GETPOST('show_files', 'int');
$confirm    = GETPOST('confirm', 'alpha');
$toselect   = GETPOST('toselect', 'array');

$sall                         = GETPOST('sall', 'alphanohtml');
$sref                         = GETPOST("sref");
$sbarcode                     = GETPOST("sbarcode");
$snom                         = GETPOST("snom");
$search_type                  = GETPOST("search_type", 'int');
$search_sale                  = GETPOST("search_sale");
$search_categ                 = GETPOST("search_categ", 'int');
$status                       = GETPOST("statut", 'int');
$tobuy                        = GETPOST("tobuy", 'int');
$fourn_id                     = GETPOST("fourn_id", 'int');
$catid                        = GETPOST('catid', 'int');
$search_tobatch               = GETPOST("search_tobatch", 'int');
$search_accountancy_code_sell = GETPOST("search_accountancy_code_sell", 'alpha');
$search_accountancy_code_buy  = GETPOST("search_accountancy_code_buy", 'alpha');
$optioncss                    = GETPOST('optioncss', 'alpha');
$type                         = GETPOST("type", "int");
$fk_parent                    = GETPOST("fk_parent", "int");

//Show/hide child products. Hidden by default
if (!$_POST) {
    $search_hidechildproducts = 'on';
} else {
    $search_hidechildproducts = GETPOST('search_hidechildproducts');
}

$diroutputmassaction = $conf->product->dir_output.'/temp/massgeneration/'.$user->id;

$limit     = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page      = (GETPOST("page", 'int') ? GETPOST("page", 'int') : 0);
if (empty($page) || $page == -1) {
    $page = 0;
}     // If $page is not defined, or '' or -1
$offset    = $limit * $page;
$pageprev  = $page - 1;
$pagenext  = $page + 1;
if (!$sortfield) $sortfield = " COALESCE(e.fk_parent, e.rowid), e.fk_parent IS NOT NULL, e.rowid ";
if (!$sortorder) $sortorder = " ";

// Initialize context for list
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'entrepotlist';
//if ((string) $type == '1') {
//    $contextpage = 'servicelist';
//    if ($search_type == '') $search_type = '1';
//}
//if ((string) $type == '0') {
//    $contextpage = 'productlist';
//    if ($search_type == '') $search_type = '0';
//}

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array of hooks
$hookmanager->initHooks(array($contextpage));
$extrafields = new ExtraFields($db);
$form        = new Form($db);

// fetch optionals attributes and labels
$extralabels          = $extrafields->fetch_name_optionals_label('stock');
$search_array_options = $extrafields->getOptionalsFromPost($extralabels, '', 'search_');

if (empty($action)) $action = 'list';

// Get object canvas (By default, this is not defined, so standard usage of dolibarr)
$canvas    = GETPOST("canvas");
$objcanvas = null;
if (!empty($canvas)) {
    require_once DOL_DOCUMENT_ROOT.'/core/class/canvas.class.php';
    $objcanvas = new Canvas($db, $action);
    $objcanvas->getCanvas('product', 'list', $canvas);
}

// Security check
if ($search_type == '0') $result = restrictedArea($user, 'produit', '', '', '', '', '', $objcanvas);
else if ($search_type == '1') $result = restrictedArea($user, 'service', '', '', '', '', '', $objcanvas);
else $result = restrictedArea($user, 'produit|service', '', '', '', '', '', $objcanvas);

// Define virtualdiffersfromphysical
$virtualdiffersfromphysical = 0;
if (!empty($conf->global->STOCK_CALCULATE_ON_SHIPMENT) || !empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER)) {
    $virtualdiffersfromphysical = 1;  // According to increase/decrease stock options, virtual and physical stock may differs.
}

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
    'e.ref' => "Ref",
    'pfp.ref_fourn' => "RefSupplier",
    'e.label' => "ProductLabel",
    'e.description' => "Description",
    "e.note" => "Note",
);
// multilang
if (!empty($conf->global->MAIN_MULTILANGS)) {
    $fieldstosearchall['pl.label']       = 'ProductLabelTranslated';
    $fieldstosearchall['pl.description'] = 'ProductDescriptionTranslated';
    $fieldstosearchall['pl.note']        = 'ProductNoteTranslated';
}
if (!empty($conf->barcode->enabled)) {
    $fieldstosearchall['e.barcode'] = 'Gencod';
}

if (empty($conf->global->PRODUIT_MULTIPRICES)) {
    $titlesellprice = $langs->trans("SellingPrice");
    if (!empty($conf->global->PRODUIT_CUSTOMER_PRICES)) {
        $titlesellprice = $form->textwithpicto($langs->trans("SellingPrice"), $langs->trans("DefaultPriceRealPriceMayDependOnCustomer"));
    }
}

// Definition of fields for lists
$arrayfields = array(
//    'e.ref'=>array('label'=>$langs->trans("Ref"), 'checked'=>1),
//    'pfp.ref_fourn'=>array('label'=>$langs->trans("RefSupplier"), 'checked'=>1, 'enabled'=>(! empty($conf->barcode->enabled))),
    'e.label' => array('label' => $langs->trans("Ref"), 'checked' => 1),
    'e.lieu' => array('label' => $langs->trans("LocationSummary"), 'checked' => 1),
//    'e.barcode'=>array('label'=>$langs->trans("Gencod"), 'checked'=>($contextpage != 'servicelist'), 'enabled'=>(! empty($conf->barcode->enabled))),
//    'e.duration'=>array('label'=>$langs->trans("Duration"), 'checked'=>($contextpage != 'productlist'), 'enabled'=>(! empty($conf->service->enabled))),
//	'e.sellprice'=>array('label'=>$langs->trans("SellingPrice"), 'checked'=>1, 'enabled'=>empty($conf->global->PRODUIT_MULTIPRICES)),
//    'e.minbuyprice'=>array('label'=>$langs->trans("BuyingPriceMinShort"), 'checked'=>1, 'enabled'=>(! empty($user->rights->fournisseur->lire))),
//	'e.numbuyprice'=>array('label'=>$langs->trans("BuyingPriceNumShort"), 'checked'=>0, 'enabled'=>(! empty($user->rights->fournisseur->lire))),
//	'e.pmp'=>array('label'=>$langs->trans("PMPValueShort"), 'checked'=>0, 'enabled'=>(! empty($user->rights->fournisseur->lire))),
//	'e.seuil_stock_alerte'=>array('label'=>$langs->trans("StockLimit"), 'checked'=>0, 'enabled'=>(! empty($conf->stock->enabled) && $user->rights->stock->lire && $contextpage != 'service')),
//    'e.desiredstock'=>array('label'=>$langs->trans("DesiredStock"), 'checked'=>1, 'enabled'=>(! empty($conf->stock->enabled) && $user->rights->stock->lire && $contextpage != 'service')),
//    'e.stock'=>array('label'=>$langs->trans("PhysicalStock"), 'checked'=>1, 'enabled'=>(! empty($conf->stock->enabled) && $user->rights->stock->lire && $contextpage != 'service')),
//    'stock_virtual'=>array('label'=>$langs->trans("VirtualStock"), 'checked'=>1, 'enabled'=>(! empty($conf->stock->enabled) && $user->rights->stock->lire && $contextpage != 'service' && $virtualdiffersfromphysical)),
//    'e.tobatch'=>array('label'=>$langs->trans("ManageLotSerial"), 'checked'=>0, 'enabled'=>(! empty($conf->productbatch->enabled))),
//	'e.accountancy_code_sell'=>array('label'=>$langs->trans("ProductAccountancySellCode"), 'checked'=>0),
//	'e.accountancy_code_buy'=>array('label'=>$langs->trans("ProductAccountancyBuyCode"), 'checked'=>0),
    'e.datec' => array('label' => $langs->trans("DateCreation"), 'checked' => 0, 'position' => 500),
    'e.tms' => array('label' => $langs->trans("DateModificationShort"), 'checked' => 0, 'position' => 500),
    'e.statut' => array('label' => $langs->trans("Status"), 'checked' => 1, 'position' => 1000),
//    'e.tobuy'=>array('label'=>$langs->trans("Status").' ('.$langs->trans("Buy").')', 'checked'=>1, 'position'=>1000)
);
// Extra fields
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) {
    foreach ($extrafields->attribute_label as $key => $val) {
        $arrayfields["ef.".$key] = array('label' => $extrafields->attribute_label[$key], 'checked' => $extrafields->attribute_list[$key], 'position' => $extrafields->attribute_pos[$key]);
    }
}



/*
 * Actions
 */

if (GETPOST('cancel')) {
    $action     = 'list';
    $massaction = '';
}
if (!GETPOST('confirmmassaction') && $massaction != 'presend' && $massaction != 'confirm_presend') {
    $massaction = '';
}

$parameters = array();
$reshook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
    // Selection of new fields
    include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

    // Purge search criteria
    if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
        $sall                         = "";
        $sref                         = "";
        $snom                         = "";
        $sbarcode                     = "";
        $search_categ                 = 0;
        $status                       = "";
        $tobuy                        = "";
        $search_tobatch               = '';
        $search_type                  = '';
        $search_accountancy_code_sell = '';
        $search_accountancy_code_buy  = '';
        $search_array_options         = array();
    }

    // Mass actions
    $objectclass = 'Entrepot';
//    if ((string) $search_type == '1') {
//        $objectlabel = 'Services';
//    }
//    if ((string) $search_type == '0') {
//        $objectlabel = 'Products';
//    }

    $permtoread   = $user->rights->stock->lire;
    $permtodelete = $user->rights->stock->supprimer;
    $uploaddir    = $conf->product->dir_output;
    include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}


/*
 * View
 */

$htmlother = new FormOther($db);
$warehouse=new Entrepot($db);

if (is_object($objcanvas) && $objcanvas->displayCanvasExists($action)) {
    $objcanvas->assign_values($action);       // This must contains code to load data (must call LoadListDatas($limit, $offset, $sortfield, $sortorder))
    $objcanvas->display_canvas($action);     // This is code to show template
} else {
    $texte = $title = $langs->trans("ListOfWarehouses");

//    if ($search_type != '' && $search_type != '-1') {
//        if ($search_type == 1) {
//            $texte = $langs->trans("Services");
//        } else {
//            $texte = $langs->trans("Products");
//        }
//    } else {
//        $texte = $langs->trans("ProductsAndServices");
//    }

    $sql = 'SELECT DISTINCT e.rowid, e.ref, e.entity,e.statut,e.fk_parent, ';
//    $sql.= ' e.fk_product_type, e.duration, e.tosell, e.tobuy, e.seuil_stock_alerte, e.desiredstock,';
//    $sql.= ' e.tobatch, e.accountancy_code_sell, e.accountancy_code_buy,';
    $sql .= ' e.datec as date_creation, e.tms as date_update ';
    //$sql.= ' pfe.ref_fourn as ref_supplier, ';
//    $sql.= ' MIN(pfe.unitprice) as minsellprice';
    if (!empty($conf->variants->enabled) && $search_hidechildproducts && ($search_type === 0)) {
        $sql .= ', pac.rowid prod_comb_id';
    }
    // Add fields from extrafields
    foreach ($extrafields->attribute_label as $key => $val)
        $sql        .= ($extrafields->attribute_type[$key] != 'separate' ? ",ef.".$key.' as options_'.$key : '');
    // Add fields from hooks
    $parameters = array();
    $reshook    = $hookmanager->executeHooks('printFieldListSelect', $parameters);    // Note that $action and $object may have been modified by hook
    $sql        .= $hookmanager->resPrint;
    $sql        .= ' FROM '.MAIN_DB_PREFIX.'entrepot as e';
    //if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."entrepot_extrafields as ef on (e.rowid = ef.fk_object)";
    if (!empty($search_categ) || !empty($catid))
            $sql        .= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_product as cp ON e.rowid = cp.fk_product"; // We'll need this table joined to the select in order to filter by categ
    $sql        .= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as pfp ON e.rowid = pfp.fk_product";
    // multilang
    if (!empty($conf->global->MAIN_MULTILANGS)) $sql        .= " LEFT JOIN ".MAIN_DB_PREFIX."product_lang as pl ON pl.fk_product = e.rowid AND pl.lang = '".$langs->getDefaultLang()."'";
    if (!empty($conf->variants->enabled) && $search_hidechildproducts && ($search_type === 0)) {
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_attribute_combination pac ON pac.fk_product_child = e.rowid";
    }

    $sql .= ' WHERE e.entity IN ('.getEntity('product').')';
    if ($sall) $sql .= natural_search(array_keys($fieldstosearchall), $sall);
    // if the type is not 1, we show all products (type = 0,2,3)
    if (dol_strlen($search_type) && $search_type != '-1') {
        if ($search_type == 1) $sql .= " AND e.fk_product_type = 1";
        else $sql .= " AND e.fk_product_type <> 1";
    }
    //if ($sref) $sql .= natural_search('e.ref', $sref);
    if ($snom) $sql .= natural_search('e.ref', $snom);
    if ($sbarcode) $sql .= natural_search('e.barcode', $sbarcode);
    if (isset($status) && dol_strlen($status) > 0 && $status != -1) $sql .= " AND e.statut = ".$db->escape($status);
    if (isset($tobuy) && dol_strlen($tobuy) > 0 && $tobuy != -1) $sql .= " AND e.tobuy = ".$db->escape($tobuy);
    if (dol_strlen($canvas) > 0) $sql .= " AND e.canvas = '".$db->escape($canvas)."'";
    if ($catid > 0) $sql .= " AND cp.fk_categorie = ".$catid;
    if ($catid == -2) $sql .= " AND cp.fk_categorie IS NULL";
    if ($search_categ > 0) $sql .= " AND cp.fk_categorie = ".$db->escape($search_categ);
    if ($search_categ == -2) $sql .= " AND cp.fk_categorie IS NULL";
    if ($fourn_id > 0) $sql .= " AND pfp.fk_soc = ".$fourn_id;
    if ($search_tobatch != '' && $search_tobatch >= 0) $sql .= " AND e.tobatch = ".$db->escape($search_tobatch);
    if ($search_accountancy_code_sell) $sql .= natural_search('e.accountancy_code_sell', $search_accountancy_code_sell);
    if ($search_accountancy_code_buy) $sql .= natural_search('e.accountancy_code_buy', $search_accountancy_code_buy);

    if($fk_parent)$sql .= " AND e.fk_parent = ".$fk_parent;
    // Add where from extra fields

    if (!empty($conf->variants->enabled) && $search_hidechildproducts && ($search_type === 0)) {
        $sql .= " AND pac.rowid IS NULL";
    }

    // Add where from extra fields
    foreach ($search_array_options as $key => $val) {
        $crit   = $val;
        $tmpkey = preg_replace('/search_options_/', '', $key);
        $typ    = $extrafields->attribute_type[$tmpkey];
        $mode   = 0;
        if (in_array($typ, array('int', 'double', 'real'))) $mode   = 1;           // Search on a numeric
        if (in_array($typ, array('sellist')) && $crit != '0' && $crit != '-1') $mode   = 2;      // Search on a foreign key int
        if ($crit != '' && (!in_array($typ, array('select', 'sellist')) || $crit != '0')) {
            $sql .= natural_search('ef.'.$tmpkey, $crit, $mode);
        }
    }
    // Add where from hooks
    $parameters = array();
    $reshook    = $hookmanager->executeHooks('printFieldListWhere', $parameters);    // Note that $action and $object may have been modified by hook
    $sql        .= $hookmanager->resPrint;
//    $sql.= " GROUP BY e.rowid, e.ref, e.label, e.barcode, e.price, e.price_ttc, e.price_base_type,";
//    $sql.= " e.fk_product_type, e.duration, e.tosell, e.tobuy, e.seuil_stock_alerte, e.desiredstock,";
//    $sql.= ' e.datec, e.tms, e.entity, e.tobatch, e.accountancy_code_sell, e.accountancy_code_buy, e.pmp';
    if (!empty($conf->variants->enabled) && $search_hidechildproducts && ($search_type === 0)) {
        $sql .= ', pac.rowid';
    }
    // Add fields from extrafields
    foreach ($extrafields->attribute_label as $key => $val)
        $sql              .= ($extrafields->attribute_type[$key] != 'separate' ? ",ef.".$key : '');
    // Add fields from hooks
    $parameters       = array();
    $reshook          = $hookmanager->executeHooks('printFieldSelect', $parameters);    // Note that $action and $object may have been modified by hook
    $sql              .= $hookmanager->resPrint;
    //if (GETPOST("toolowstock")) $sql.= " HAVING SUM(s.reel) < e.seuil_stock_alerte";    // Not used yet
//    $sql.= $db->order($sortfield,$sortorder);
    $sql              .= ' ORDER BY '.$sortfield.' '.$sortorder;
    $nbtotalofrecords = '';
    if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
        $result           = $db->query($sql);
        $nbtotalofrecords = $db->num_rows($result);
    }
    $sql .= $db->plimit($limit + 1, $offset);
    $resql = $db->query($sql);
    if ($resql) {
        $num = $db->num_rows($resql);

        $arrayofselected = is_array($toselect) ? $toselect : array();

        if ($num == 1 && !empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && $sall) {
            $obj = $db->fetch_object($resql);
            $id  = $obj->rowid;
            header("Location: ".DOL_URL_ROOT.'/product/card.php?id='.$id);
            exit;
        }

        $helpurl = '';
        if ($search_type != '') {
            if ($search_type == 0) {
                $helpurl = 'EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';
            } else if ($search_type == 1) {
                $helpurl = 'EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';
            }
        }

        llxHeader('', $title, $helpurl, '');

        // Displays product removal confirmation
        if (GETPOST('delprod')) {
            setEventMessages($langs->trans("ProductDeleted", GETPOST('delprod')), null, 'mesgs');
        }

        $param = '';
        if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.urlencode($contextpage);
        if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.urlencode($limit);
        if ($search_categ > 0) $param .= "&amp;search_categ=".urlencode($search_categ);
        if ($sref) $param = "&amp;sref=".urlencode($sref);
        if ($search_ref_supplier) $param = "&amp;search_ref_supplier=".urlencode($search_ref_supplier);
        if ($sbarcode) $param .= ($sbarcode ? "&amp;sbarcode=".urlencode($sbarcode) : "");
        if ($snom) $param .= "&amp;snom=".urlencode($snom);
        if ($sall) $param .= "&amp;sall=".urlencode($sall);
        if ($status != '') $param .= "&amp;status=".urlencode($status);
        if ($tobuy != '') $param .= "&amp;tobuy=".urlencode($tobuy);
        if ($fourn_id > 0) $param .= ($fourn_id ? "&amp;fourn_id=".$fourn_id : "");
        if ($seach_categ) $param .= ($search_categ ? "&amp;search_categ=".urlencode($search_categ) : "");
        if ($type != '') $param .= '&amp;type='.urlencode($type);
        if ($search_type != '') $param .= '&amp;search_type='.urlencode($search_type);
        if ($optioncss != '') $param .= '&optioncss='.urlencode($optioncss);
        if ($search_tobatch) $param = "&amp;search_ref_supplier=".urlencode($search_ref_supplier);
        if ($search_accountancy_code_sell) $param = "&amp;search_accountancy_code_sell=".urlencode($search_accountancy_code_sell);
        if ($search_accountancy_code_buy) $param = "&amp;search_accountancy_code_buy=".urlencode($search_accountancy_code_buy);
        // Add $param from extra fields
        foreach ($search_array_options as $key => $val) {
            $crit   = $val;
            $tmpkey = preg_replace('/search_options_/', '', $key);
            if ($val != '') $param  .= '&search_options_'.$tmpkey.'='.urlencode($val);
        }

        // List of mass actions available
        $arrayofmassactions           = array(
            //'presend'=>$langs->trans("SendByMail"),
            //'builddoc'=>$langs->trans("PDFMerge"),
        );
        if ($user->rights->produit->supprimer) $arrayofmassactions['delete'] = $langs->trans("Delete");
        if ($massaction == 'presend' || $massaction == 'createbills') $arrayofmassactions           = array();
        $massactionbutton             = $form->selectMassAction('', $arrayofmassactions);

        print '<form action="'.$_SERVER["PHP_SELF"].'" method="post" name="formulaire">';
        if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
        print '<input type="hidden" name="action" value="list">';
        print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
        print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
        print '<input type="hidden" name="page" value="'.$page.'">';
        print '<input type="hidden" name="type" value="'.$type.'">';
        if (empty($arrayfields['e.fk_product_type']['checked'])) print '<input type="hidden" name="search_type" value="'.dol_escape_htmltag($search_type).'">';

        print_barre_liste($texte, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'title_generic.png', 0, '', '', $limit);

        if (!empty($catid)) {
            print "<div id='ways'>";
            $c    = new Categorie($db);
            $ways = $c->print_all_ways(' &gt; ', 'product/list.php');
            print " &gt; ".$ways[0]."<br>\n";
            print "</div><br>";
        }

        if (!empty($canvas) && file_exists(DOL_DOCUMENT_ROOT.'/product/canvas/'.$canvas.'/actions_card_'.$canvas.'.class.php')) {
            $fieldlist   = $object->field_list;
            $datas       = $object->list_datas;
            $picto       = 'title.png';
            $title_picto = img_picto('', $picto);
            $title_text  = $title;

            // Default templates directory
            $template_dir = DOL_DOCUMENT_ROOT.'/product/canvas/'.$canvas.'/tpl/';
            // Check if a custom template is present
            if (file_exists(DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/tpl/product/'.$canvas.'/list.tpl.php')) {
                $template_dir = DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/tpl/product/'.$canvas.'/';
            }

            include $template_dir.'list.tpl.php'; // Include native PHP templates
        } else {
            if ($sall) {
                foreach ($fieldstosearchall as $key => $val)
                    $fieldstosearchall[$key] = $langs->trans($val);
                print $langs->trans("FilterOnInto", $sall).join(', ', $fieldstosearchall);
            }

//            // Filter on categories
//            $moreforfilter = '';
//            if (!empty($conf->categorie->enabled)) {
//                $moreforfilter .= '<div class="divsearchfield">';
//                $moreforfilter .= $langs->trans('Categories').': ';
//                $moreforfilter .= $htmlother->select_categories(Categorie::TYPE_PRODUCT, $search_categ, 'search_categ', 1);
//                $moreforfilter .= '</div>';
//            }
//
//            //Show/hide child products. Hidden by default
//            if (!empty($conf->variants->enabled) && $search_type === 0) {
//                $moreforfilter .= '<div class="divsearchfield">';
//                $moreforfilter .= '<input type="checkbox" id="search_hidechildproducts" name="search_hidechildproducts" value="on"'.($search_hidechildproducts ? 'checked="checked"' : '').'>';
//                $moreforfilter .= ' <label for="search_hidechildproducts">'.$langs->trans('HideChildProducts').'</label>';
//                $moreforfilter .= '</div>';
//            }
//
//            $parameters    = array();
//            $reshook       = $hookmanager->executeHooks('printFieldPreListTitle', $parameters);    // Note that $action and $object may have been modified by hook
//            if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
//            else $moreforfilter = $hookmanager->resPrint;
//
//            if ($moreforfilter) {
//                print '<div class="liste_titre liste_titre_bydiv centpercent">';
//                print $moreforfilter;
//                print '</div>';
//            }

            $varpage        = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
            $selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields
            if ($massactionbutton) $selectedfields .= $form->showCheckAddButtons('checkforselect', 1);

            print '<div class="div-table-responsive">';
            print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

            // Lines with input filters
            print '<tr class="liste_titre_filter">';
            if (!empty($arrayfields['e.ref']['checked'])) {
                print '<td class="liste_titre" align="left">';
                print '<input class="flat" type="text" name="sref" size="8" value="'.dol_escape_htmltag($sref).'">';
                print '</td>';
            }
            if (!empty($arrayfields['pfp.ref_fourn']['checked'])) {
                print '<td class="liste_titre" align="left">';
                print '<input class="flat" type="text" name="search_ref_supplier" size="8" value="'.dol_escape_htmltag($search_ref_supplier).'">';
                print '</td>';
            }
            if (!empty($arrayfields['e.label']['checked'])) {
                print '<td class="liste_titre" align="left">';
                print '<input class="flat" type="text" name="snom" size="12" value="'.dol_escape_htmltag($snom).'">';
                print '</td>';
            }
            // Type
            if (!empty($arrayfields['e.fk_product_type']['checked'])) {
                print '<td class="liste_titre" align="left">';
                $array = array('-1' => '&nbsp;', '0' => $langs->trans('Product'), '1' => $langs->trans('Service'));
                print $form->selectarray('search_type', $array, $search_type);
                print '</td>';
            }
            // Barcode
            if (!empty($arrayfields['e.barcode']['checked'])) {
                print '<td class="liste_titre">';
                print '<input class="flat" type="text" name="sbarcode" size="6" value="'.dol_escape_htmltag($sbarcode).'">';
                print '</td>';
            }
            // Duration
            if (!empty($arrayfields['e.duration']['checked'])) {
                print '<td class="liste_titre">';
                print '&nbsp;';
                print '</td>';
            }
            // Sell price
            if (!empty($arrayfields['e.sellprice']['checked'])) {
                print '<td class="liste_titre" align="right">';
                print '</td>';
            }
            // Minimum buying Price
            if (!empty($arrayfields['e.minbuyprice']['checked'])) {
                print '<td class="liste_titre">';
                print '&nbsp;';
                print '</td>';
            }
            // Number buying Price
            if (!empty($arrayfields['e.numbuyprice']['checked'])) {
                print '<td class="liste_titre">';
                print '&nbsp;';
                print '</td>';
            }
            // WAP
            if (!empty($arrayfields['e.pmp']['checked'])) {
                print '<td class="liste_titre">';
                print '&nbsp;';
                print '</td>';
            }
            // Limit for alert
            if (!empty($arrayfields['e.seuil_stock_alerte']['checked'])) {
                print '<td class="liste_titre">';
                print '&nbsp;';
                print '</td>';
            }
            // Desired stock
            if (!empty($arrayfields['e.desiredstock']['checked'])) {
                print '<td class="liste_titre">';
                print '&nbsp;';
                print '</td>';
            }
            // Stock
            if (!empty($arrayfields['e.lieu']['checked'])) print '<td class="liste_titre">&nbsp;</td>';
            // Stock
            if (!empty($arrayfields['stock_virtual']['checked'])) print '<td class="liste_titre">&nbsp;</td>';
            // To batch
            if (!empty($arrayfields['e.tobatch']['checked'])) print '<td class="liste_titre center">'.$form->selectyesno($search_tobatch, '', '', '', 1).'</td>';
            // Accountancy code sell
            if (!empty($arrayfields['e.accountancy_code_sell']['checked']))
                    print '<td class="liste_titre"><input class="flat" type="text" name="search_accountancy_code_sell" size="6" value="'.dol_escape_htmltag($search_accountancy_code_sell).'"></td>';
            // Accountancy code sell
            if (!empty($arrayfields['e.accountancy_code_buy']['checked']))
                    print '<td class="liste_titre"><input class="flat" type="text" name="search_accountancy_code_buy" size="6" value="'.dol_escape_htmltag($search_accountancy_code_buy).'"></td>';
            // Extra fields
            if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) {
                foreach ($extrafields->attribute_label as $key => $val) {
                    if (!empty($arrayfields["ef.".$key]['checked'])) print '<td class="liste_titre"></td>';
                }
            }
            // Fields from hook
            $parameters = array('arrayfields' => $arrayfields);
            $reshook    = $hookmanager->executeHooks('printFieldListOption', $parameters);    // Note that $action and $object may have been modified by hook
            print $hookmanager->resPrint;
            // Date creation
            if (!empty($arrayfields['e.datec']['checked'])) {
                print '<td class="liste_titre">';
                print '</td>';
            }
            // Date modification
            if (!empty($arrayfields['e.tms']['checked'])) {
                print '<td class="liste_titre">';
                print '</td>';
            }
            if (!empty($arrayfields['e.statut']['checked'])) {
                print '<td class="liste_titre" align="right">';
                print $form->selectarray('statut', array(0=>$langs->trans('Closed2'),1=>$langs->trans('Opened')), $status, 1);
            }
            if (!empty($arrayfields['e.tobuy']['checked'])) {
                print '<td class="liste_titre" align="right">';
                print $form->selectarray('tobuy', array('0' => $langs->trans('ProductStatusNotOnBuyShort'), '1' => $langs->trans('ProductStatusOnBuyShort')), $tobuy, 1);
                print '</td>';
            }
            print '<td class="liste_titre" align="middle">';
            $searchpicto = $form->showFilterButtons();
            print $searchpicto;
            print '</td>';

            print '</tr>';

            print '<tr class="liste_titre">';
            if (!empty($arrayfields['e.ref']['checked'])) print_liste_field_titre($arrayfields['e.ref']['label'], $_SERVER["PHP_SELF"], "e.ref", "", $param, "", $sortfield, $sortorder);
            if (!empty($arrayfields['pfp.ref_fourn']['checked']))
                    print_liste_field_titre($arrayfields['pfp.ref_fourn']['label'], $_SERVER["PHP_SELF"], "pfp.ref_fourn", "", $param, "", $sortfield, $sortorder);
            if (!empty($arrayfields['e.label']['checked'])) print_liste_field_titre($arrayfields['e.label']['label'], $_SERVER["PHP_SELF"], "e.label", "", $param, "", $sortfield, $sortorder);
            if (!empty($arrayfields['e.lieu']['checked'])) print_liste_field_titre($arrayfields['e.lieu']['label'], $_SERVER["PHP_SELF"], "e.lieu", "", $param, "", $sortfield, $sortorder);
            if (!empty($arrayfields['e.fk_product_type']['checked']))
                    print_liste_field_titre($arrayfields['e.fk_product_type']['label'], $_SERVER["PHP_SELF"], "e.fk_product_type", "", $param, "", $sortfield, $sortorder);
            if (!empty($arrayfields['e.barcode']['checked'])) print_liste_field_titre($arrayfields['e.barcode']['label'], $_SERVER["PHP_SELF"], "e.barcode", "", $param, "", $sortfield, $sortorder);
            if (!empty($arrayfields['e.duration']['checked']))
                    print_liste_field_titre($arrayfields['e.duration']['label'], $_SERVER["PHP_SELF"], "e.duration", "", $param, 'align="center"', $sortfield, $sortorder);
            if (!empty($arrayfields['e.sellprice']['checked'])) print_liste_field_titre($arrayfields['e.sellprice']['label'], $_SERVER["PHP_SELF"], "", "", $param, 'align="right"', $sortfield,
                    $sortorder);
            if (!empty($arrayfields['e.minbuyprice']['checked']))
                    print_liste_field_titre($arrayfields['e.minbuyprice']['label'], $_SERVER["PHP_SELF"], "", "", $param, 'align="right"', $sortfield, $sortorder);
            if (!empty($arrayfields['e.numbuyprice']['checked']))
                    print_liste_field_titre($arrayfields['e.numbuyprice']['label'], $_SERVER["PHP_SELF"], "", "", $param, 'align="right"', $sortfield, $sortorder);
            if (!empty($arrayfields['e.pmp']['checked'])) print_liste_field_titre($arrayfields['e.pmp']['label'], $_SERVER["PHP_SELF"], "", "", $param, 'align="right"', $sortfield, $sortorder);
            if (!empty($arrayfields['e.seuil_stock_alerte']['checked']))
                    print_liste_field_titre($arrayfields['e.seuil_stock_alerte']['label'], $_SERVER["PHP_SELF"], "e.seuil_stock_alerte", "", $param, 'align="right"', $sortfield, $sortorder);
            if (!empty($arrayfields['e.desiredstock']['checked']))
                    print_liste_field_titre($arrayfields['e.desiredstock']['label'], $_SERVER["PHP_SELF"], "e.desiredstock", "", $param, 'align="right"', $sortfield, $sortorder);
            if (!empty($arrayfields['e.stock']['checked'])) print_liste_field_titre($arrayfields['e.stock']['label'], $_SERVER["PHP_SELF"], "e.stock", "", $param, 'align="right"', $sortfield,
                    $sortorder);
            if (!empty($arrayfields['stock_virtual']['checked']))
                    print_liste_field_titre($arrayfields['stock_virtual']['label'], $_SERVER["PHP_SELF"], "", "", $param, 'align="right"', $sortfield, $sortorder);
            if (!empty($arrayfields['e.tobatch']['checked']))
                    print_liste_field_titre($arrayfields['e.tobatch']['label'], $_SERVER["PHP_SELF"], "e.tobatch", "", $param, 'align="center"', $sortfield, $sortorder);
            if (!empty($arrayfields['e.accountancy_code_sell']['checked']))
                    print_liste_field_titre($arrayfields['e.accountancy_code_sell']['label'], $_SERVER["PHP_SELF"], "e.accountancy_code_sell", "", $param, '', $sortfield, $sortorder);
            if (!empty($arrayfields['e.accountancy_code_buy']['checked']))
                    print_liste_field_titre($arrayfields['e.accountancy_code_buy']['label'], $_SERVER["PHP_SELF"], "e.accountancy_code_buy", "", $param, '', $sortfield, $sortorder);
            if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) {
                foreach ($extrafields->attribute_label as $key => $val) {
                    if (!empty($arrayfields["ef.".$key]['checked'])) {
                        $align       = $extrafields->getAlignFlag($key);
                        $sortonfield = "ef.".$key;
                        if (!empty($extrafields->attribute_computed[$key])) $sortonfield = '';
                        print_liste_field_titre($extralabels[$key], $_SERVER["PHP_SELF"], $sortonfield, "", $param, ($align ? 'align="'.$align.'"' : ''), $sortfield, $sortorder);
                    }
                }
            }
            // Hook fields
            $parameters = array('arrayfields' => $arrayfields);
            $reshook    = $hookmanager->executeHooks('printFieldListTitle', $parameters);    // Note that $action and $object may have been modified by hook
            print $hookmanager->resPrint;
            if (!empty($arrayfields['e.datec']['checked']))
                    print_liste_field_titre($arrayfields['e.datec']['label'], $_SERVER["PHP_SELF"], "e.datec", "", $param, 'align="center" class="nowrap"', $sortfield, $sortorder);
            if (!empty($arrayfields['e.tms']['checked']))
                    print_liste_field_titre($arrayfields['e.tms']['label'], $_SERVER["PHP_SELF"], "e.tms", "", $param, 'align="center" class="nowrap"', $sortfield, $sortorder);
            if (!empty($arrayfields['e.statut']['checked'])) print_liste_field_titre($arrayfields['e.statut']['label'], $_SERVER["PHP_SELF"], "e.statut", "", $param, 'align="right"', $sortfield,
                    $sortorder);
            if (!empty($arrayfields['e.tobuy']['checked'])) print_liste_field_titre($arrayfields['e.tobuy']['label'], $_SERVER["PHP_SELF"], "e.tobuy", "", $param, 'align="right"', $sortfield,
                    $sortorder);
            print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', $sortfield, $sortorder, 'maxwidthsearch ');
            print "</tr>\n";


            $product_static = new Entrepot($db);
            $product_fourn  = new Entrepot($db);

            $i          = 0;
            $totalarray = array();
            while ($i < min($num, $limit)) {
                $obj = $db->fetch_object($resql);

                // Multilangs
                if (!empty($conf->global->MAIN_MULTILANGS)) { // si l'option est active
                    $sql = "SELECT label";
                    $sql .= " FROM ".MAIN_DB_PREFIX."product_lang";
                    $sql .= " WHERE fk_product=".$obj->rowid;
                    $sql .= " AND lang='".$langs->getDefaultLang()."'";
                    $sql .= " LIMIT 1";

                    $result = $db->query($sql);
                    if ($result) {
                        $objtp      = $db->fetch_object($result);
                        if (!empty($objtp->label)) $obj->label = $objtp->label;
                    }
                }

                $product_static->id         = $obj->rowid;
                $product_static->ref        = $obj->ref;
                $product_static->ref_fourn  = $obj->ref_supplier;
                $product_static->label      = $obj->ref;
                $product_static->type       = $obj->fk_product_type;
//                $product_static->status_buy = $obj->tobuy;
//                $product_static->status     = $obj->tosell;
                $product_static->status     = $obj->statut;
                $product_static->statut     = $obj->statut;
                $product_static->fk_parent  = $obj->fk_parent;
                $product_static->entity     = $obj->entity;
                $product_static->pmp        = $obj->pmp;

                if ((!empty($conf->stock->enabled) && $user->rights->stock->lire && $search_type != 1) || !empty($conf->global->STOCK_DISABLE_OPTIM_LOAD)) { // To optimize call of load_stock
                    if ($obj->fk_product_type != 1 || !empty($conf->global->STOCK_SUPPORTS_SERVICES)) {    // Not a service
                        // $product_static->load_stock('nobatch');             // Load stock_reel + stock_warehouse. This also call load_virtual_stock()
                    }
                }


                print '<tr class="oddeven">';

                // Ref
                if (!empty($arrayfields['e.label']['checked'])) {
                    print '<td class="nowrap">';
                    print $product_static->getNomUrl(1, '', 1);
                    print "</td>\n";
                    if (!$i) $totalarray['nbfield'] ++;
                }
//       			// Ref supplier
//			    elseif (! empty($arrayfields['pfe.ref_fourn']['checked']))
//			    {
//	    			print '<td class="nowrap">';
//	    			print $product_static->getNomUrl(1,'',24);
//	    			print "</td>\n";
//		            if (! $i) $totalarray['nbfield']++;
//			    }
                // Label
                if (!empty($arrayfields['e.label']['checked'])) {
                    print '<td>'.dol_trunc($obj->ref, 40).'</td>';
                    if (!$i) $totalarray['nbfield'] ++;
                }

                // Type
                if (!empty($arrayfields['e.fk_product_type']['checked'])) {
                    print '<td>'.$obj->fk_product_type.'</td>';
                    if (!$i) $totalarray['nbfield'] ++;
                }

                // Barcode
                if (!empty($arrayfields['e.barcode']['checked'])) {
                    print '<td>'.$obj->barcode.'</td>';
                    if (!$i) $totalarray['nbfield'] ++;
                }

                // Duration
                if (!empty($arrayfields['e.duration']['checked'])) {
                    print '<td align="center">';
                    if (preg_match('/([^a-z]+)[a-z]/i', $obj->duration)) {
                        if (preg_match('/([^a-z]+)y/i', $obj->duration, $regs)) print $regs[1].' '.$langs->trans("DurationYear");
                        elseif (preg_match('/([^a-z]+)m/i', $obj->duration, $regs)) print $regs[1].' '.$langs->trans("DurationMonth");
                        elseif (preg_match('/([^a-z]+)w/i', $obj->duration, $regs)) print $regs[1].' '.$langs->trans("DurationWeek");
                        elseif (preg_match('/([^a-z]+)d/i', $obj->duration, $regs)) print $regs[1].' '.$langs->trans("DurationDay");
                        //elseif (preg_match('/([^a-z]+)h/i',$obj->duration,$regs)) print $regs[1].' '.$langs->trans("DurationHour");
                        else print $obj->duration;
                    }
                    print '</td>';
                    if (!$i) $totalarray['nbfield'] ++;
                }

                // Sell price
                if (!empty($arrayfields['e.sellprice']['checked'])) {
                    print '<td align="right">';
                    if ($obj->tosell) {
                        if ($obj->price_base_type == 'TTC') print price($obj->price_ttc).' '.$langs->trans("TTC");
                        else print price($obj->price).' '.$langs->trans("HT");
                    }
                    print '</td>';
                    if (!$i) $totalarray['nbfield'] ++;
                }

                // Better buy price
                if (!empty($arrayfields['e.minbuyprice']['checked'])) {
                    print '<td align="right">';
                    if ($obj->tobuy && $obj->minsellprice != '') {
                        //print price($obj->minsellprice).' '.$langs->trans("HT");
                        if ($product_fourn->find_min_price_product_fournisseur($obj->rowid) > 0) {
                            if ($product_fourn->product_fourn_price_id > 0) {
                                if (!empty($conf->fournisseur->enabled) && $user->rights->fournisseur->lire) {
                                    $htmltext = $product_fourn->display_price_product_fournisseur(1, 1, 0, 1);
                                    print $form->textwithpicto(price($product_fourn->fourn_unitprice * (1 - $product_fourn->fourn_remise_percent / 100) + $product_fourn->fourn_unitcharges - $product_fourn->fourn_remise).' '.$langs->trans("HT"),
                                            $htmltext);
                                } else print price($product_fourn->fourn_unitprice).' '.$langs->trans("HT");
                            }
                        }
                    }
                    print '</td>';
                    if (!$i) $totalarray['nbfield'] ++;
                }

                // Number of buy prices
                if (!empty($arrayfields['e.numbuyprice']['checked'])) {
                    print '<td align="right">';
                    if ($obj->tobuy) {
                        if (count($productFournList = $product_fourn->list_product_fournisseur_price($obj->rowid)) > 0) {
                            $htmltext = $product_fourn->display_price_product_fournisseur(1, 1, 0, 1, $productFournList);
                            print $form->textwithpicto(count($productFournList), $htmltext);
                        }
                    }
                    print '</td>';
                }

                // WAP
                if (!empty($arrayfields['e.pmp']['checked'])) {
                    print '<td class="nowrap" align="right">';
                    print price($product_static->pmp, 1, $langs);
                    print '</td>';
                }

                // Limit alert
                if (!empty($arrayfields['e.seuil_stock_alerte']['checked'])) {
                    print '<td align="right">';
                    if ($obj->fk_product_type != 1) {
                        print $obj->seuil_stock_alerte;
                    }
                    print '</td>';
                    if (!$i) $totalarray['nbfield'] ++;
                }
                // Desired stock
                if (!empty($arrayfields['e.desiredstock']['checked'])) {
                    print '<td align="right">';
                    if ($obj->fk_product_type != 1) {
                        print $obj->desiredstock;
                    }
                    print '</td>';
                    if (!$i) $totalarray['nbfield'] ++;
                }
                // Stock
                if (!empty($arrayfields['e.stock']['checked'])) {
                    print '<td align="right">';
                    if ($obj->fk_product_type != 1) {
                        if ($obj->seuil_stock_alerte != '' && $product_static->stock_reel < (float) $obj->seuil_stock_alerte)
                                print img_warning($langs->trans("StockLowerThanLimit", $obj->seuil_stock_alerte)).' ';
                        print $product_static->stock_reel;
                    }
                    print '</td>';
                    if (!$i) $totalarray['nbfield'] ++;
                }
                // Stock
                if (!empty($arrayfields['stock_virtual']['checked'])) {
                    print '<td align="right">';
                    if ($obj->fk_product_type != 1) {
                        if ($obj->seuil_stock_alerte != '' && $product_static->stock_theorique < (float) $obj->seuil_stock_alerte)
                                print img_warning($langs->trans("StockLowerThanLimit", $obj->seuil_stock_alerte)).' ';
                        print $product_static->stock_theorique;
                    }
                    print '</td>';
                    if (!$i) $totalarray['nbfield'] ++;
                }
                // Lot/Serial
                if (!empty($arrayfields['e.tobatch']['checked'])) {
                    print '<td align="center">';
                    print yn($obj->tobatch);
                    print '</td>';
                    if (!$i) $totalarray['nbfield'] ++;
                }
                // Accountancy code sell
                if (!empty($arrayfields['e.accountancy_code_sell']['checked'])) {
                    print '<td>'.$obj->accountancy_code_sell.'</td>';
                    if (!$i) $totalarray['nbfield'] ++;
                }
                // Accountancy code sell
                if (!empty($arrayfields['e.accountancy_code_buy']['checked'])) {
                    print '<td>'.$obj->accountancy_code_buy.'</td>';
                    if (!$i) $totalarray['nbfield'] ++;
                }
                // Extra fields
                if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) {
                    foreach ($extrafields->attribute_label as $key => $val) {
                        if (!empty($arrayfields["ef.".$key]['checked'])) {
                            print '<td';
                            $align  = $extrafields->getAlignFlag($key);
                            if ($align) print ' align="'.$align.'"';
                            print '>';
                            $tmpkey = 'options_'.$key;
                            print $extrafields->showOutputField($key, $obj->$tmpkey, '', 1);
                            print '</td>';
                            if (!$i) $totalarray['nbfield'] ++;
                        }
                    }
                }
                // Fields from hook
                $parameters = array('arrayfields' => $arrayfields, 'obj' => $obj);
                $reshook    = $hookmanager->executeHooks('printFieldListValue', $parameters);    // Note that $action and $object may have been modified by hook
                print $hookmanager->resPrint;
                // Date creation
                if (!empty($arrayfields['e.datec']['checked'])) {
                    print '<td align="center">';
                    print dol_print_date($obj->date_creation, 'dayhour');
                    print '</td>';
                    if (!$i) $totalarray['nbfield'] ++;
                }
                // Date modification
                if (!empty($arrayfields['e.tms']['checked'])) {
                    print '<td align="center">';
                    print dol_print_date($obj->date_update, 'dayhour');
                    print '</td>';
                    if (!$i) $totalarray['nbfield'] ++;
                }

                // Status (to sell)fk_user_modif
                if (!empty($arrayfields['e.statut']['checked'])) {
                    print '<td align="right" nowrap="nowrap">';
                    if (!empty($conf->use_javascript_ajax) && $user->rights->produit->creer && !empty($conf->global->MAIN_DIRECT_STATUS_UPDATE)) {
                        //Bug ajax_machin
                        $product_static->element='entrepot';
                        print ajax_object_onoff($product_static, 'statut', 'status', 'ProductStatusOnSell', 'ProductStatusNotOnSell');
                    } else {
                        print $product_static->LibStatut($obj->statut, 5, 0);
                    }
                    print '</td>';
                    if (!$i) $totalarray['nbfield'] ++;
                }
                // Status (to buy)
                elseif (!empty($arrayfields['e.tobuy']['checked'])) {
                    print '<td align="right" nowrap="nowrap">';
                    if (!empty($conf->use_javascript_ajax) && $user->rights->produit->creer && !empty($conf->global->MAIN_DIRECT_STATUS_UPDATE)) {
                        print ajax_object_onoff($product_static, 'status_buy', 'tobuy', 'ProductStatusOnBuy', 'ProductStatusNotOnBuy');
                    } else {
                        print $product_static->LibStatut($obj->tobuy, 5, 1);
                    }
                    print '</td>';
                    if (!$i) $totalarray['nbfield'] ++;
                }
                // Action
                print '<td class="nowrap" align="center">';
                if ($massactionbutton || $massaction) {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
                    $selected = 0;
                    if (in_array($obj->rowid, $arrayofselected)) $selected = 1;
                    print '<input id="cb'.$obj->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected ? ' checked="checked"' : '').'>';
                }
                print '</td>';
                if (!$i) $totalarray['nbfield'] ++;

                print "</tr>\n";
                $i++;
            }

            $db->free($resql);

            print "</table>";
            print "</div>";
        }
        print '</form>';
    }
    else {
        dol_print_error($db);
    }
}


llxFooter();
$db->close();
