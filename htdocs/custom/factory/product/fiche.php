 <?php
/* Copyright (C) 2001-2007	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005		Eric Seigne				<eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2006		Andre Cianfarani		<acianfa@free.fr>
 * Copyright (C) 2011		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2013-2017	Charlie BENKE			<charlie@patas-monkey.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *  \file	   htdocs/factory/product/fiche.php
 *  \ingroup	product
 *  \brief	  Page de création des Ordres de fabrication sur la fiche produit
 */

$res=0;
if (! $res && file_exists("../../main.inc.php"))
	$res=@include("../../main.inc.php");		// For root directory
if (! $res && file_exists("../../../main.inc.php"))
	$res=@include("../../../main.inc.php");	// For "custom" directory


require_once DOL_DOCUMENT_ROOT."/core/lib/product.lib.php";
require_once DOL_DOCUMENT_ROOT."/product/class/product.class.php";
require_once DOL_DOCUMENT_ROOT."/product/stock/class/entrepot.class.php";
require_once DOL_DOCUMENT_ROOT."/categories/class/categorie.class.php";
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

dol_include_once('/factory/class/factory.class.php');
dol_include_once('/factory/core/lib/factory.lib.php');
dol_include_once('/factory/class/html.factoryformproduct.class.php');

if (! empty($conf->global->FACTORY_ADDON)
	&& is_readable(dol_buildpath("/factory/core/modules/factory/".$conf->global->FACTORY_ADDON.".php")))
	dol_include_once("/factory/core/modules/factory/".$conf->global->FACTORY_ADDON.".php");



$langs->load("bills");
$langs->load("products");
$langs->load("factory@factory");

$factoryid=GETPOST('factoryid', 'int');
$id		= GETPOST('id', 'int');
$ref	= GETPOST('ref', 'alpha');
$action	= GETPOST('action', 'alpha');
$confirm= GETPOST('confirm', 'alpha');
$cancel	= GETPOST('cancel', 'alpha');
$key	= GETPOST('key');
$parent = GETPOST('parent');

// Security check
if (! empty($user->societe_id)) $socid=$user->societe_id;
$fieldvalue = (! empty($id) ? $id : (! empty($ref) ? $ref : ''));
$fieldtype = (! empty($ref) ? 'ref' : 'rowid');
$result=restrictedArea($user, 'produit|service', $fieldvalue, 'product&product', '', '', $fieldtype, $objcanvas);

$mesg = '';

$object = new Product($db);
$factory = new Factory($db);

// fetch optionals attributes and labels
$extrafields = new ExtraFields($db);
$extralabels = $extrafields->fetch_name_optionals_label("factory");

$productid=0;
if ($id || $ref) {
	$result = $object->fetch($id, $ref);
	$productid=$object->id;
	$id=$object->id;
	$factory->id =$id;
}

$factoryIdEntrepot = (GETPOST('factory_id_entrepot', 'int')?intval(GETPOST('factory_id_entrepot', 'int')):-1);
$nbToBuild = (GETPOST('nbToBuild', 'int')?intval(GETPOST('nbToBuild', 'int')):1);
if ($nbToBuild <= 0) {
    $nbToBuild = 1;
}


/*
 * Actions
 */
if ($cancel == $langs->trans("Cancel"))
	$action = '';

// build product on each store
if ($action == 'createof' && GETPOST("createofrun")) {

    $error = 0;

    $factory->get_sousproduits_arbo();
    $prods_arbo = $factory->get_arbo_each_prod();

    $factory->fk_entrepot=$factoryIdEntrepot;
    $factory->qty_planned=GETPOST("nbToBuild");
    $factory->date_start_planned=dol_mktime(
        GETPOST('plannedstarthour', 'int'), GETPOST('plannedstartmin', 'int'), 0,
        GETPOST('plannedstartmonth', 'int'), GETPOST('plannedstartday', 'int'), GETPOST('plannedstartyear', 'int')
    );
    $factory->date_end_planned=dol_mktime(
        GETPOST('plannedendhour', 'int'), GETPOST('plannedendmin', 'int'), 0,
        GETPOST('plannedendmonth', 'int'), GETPOST('plannedendday', 'int'), GETPOST('plannedendyear', 'int')
    );
    $factory->duration_planned=GETPOST("workloadhour")*3600+GETPOST("workloadmin")*60;
    $factory->description=GETPOST("description");

    // check mandatory fields
    if ($factoryIdEntrepot <= 0) {
        $error++;
        $factory->error    = $langs->trans("ErrorFieldRequired", $langs->transnoentities("FactoryWarehouse"));
        $factory->errors[] = $factory->error;
    }

    if ($nbToBuild <= 0) {
        $error++;
        $factory->error    = $langs->trans("ErrorFieldRequired", $langs->trans("NbToBuild"));
        $factory->errors[] = $factory->error;
    }

    // list of warehouses for components to use to build final product
    $warehouseToUseList = array();
    if (count($prods_arbo) > 0) {
        foreach ($prods_arbo as $value) {
            // only for product
            if (intval($value['type']) == 0) {
                $lineNum = 0;
                $productId = $value['id'];
                $productNb = intval($value['nb']) * $nbToBuild;

                for ($productNum = 0; $productNum < $productNb; $productNum++) {
                    $productFactoryIdEntrepot = GETPOST('factory_id_entrepot_' . $productId . '_' . $lineNum, 'int');
                    $productFactoryQtyPost    = GETPOST('factory_qty_' . $productId . '_' . $lineNum, 'int');

                    if (!empty($productFactoryIdEntrepot)) {
                        $productFactoryQty = intval($productFactoryQtyPost);

                        if ($productFactoryQty >= 1) {
                            if ($productFactoryIdEntrepot <= 0) {
                                $error++;
                                $factory->error    = $langs->trans('ErrorFieldRequired', $langs->transnoentities('Warehouse'));
                                $factory->errors[] = $factory->error;
                                break;
                            }
                        }

                        // add warehouses to use
                        if (!isset($warehouseToUseList[$productId])) {
                            $warehouseToUseList[$productId] = array();
                        }
                        $warehouseToUseList[$productId][] = array('fk_entrepot' => $productFactoryIdEntrepot, 'qty' => $productFactoryQty);
                    }

                    $lineNum++;
                }
            }
        }
    }

    if (!$error) {
        // Fill array 'array_options' with data from add form
        $ret = $extrafields->setOptionalsFromPost($extralabels, $factory);
        if ($ret < 0) $error++;

        if (! $error) {
            $newref=$factory->createof($warehouseToUseList);

            if ($newref < 0) {
                $error++;
            }
        }
    }

    if ($error) {
        setEventMessages($factory->error, $factory->errors, 'errors');
        $action = 'build';
    } else {
        // Little message to inform of the number of builded product
        setEventMessage($langs->trans("FactoryOrderSaved"), 'mesgs');
        // on affiche la liste des of en cours pour le produit
        header("Location: list.php?fk_status=1&id=".$id);
        exit();
    }
}


/*
 * View
 */


$productstatic = new Product($db);
$form = new Form($db);

llxHeader("", "", $langs->trans("CardProduct".$product->type), '', 0, 0, array('/custom/factory/js/factory_dispatcher.js'));

$head=product_prepare_head($object, $user);
$titre=$langs->trans("CardProduct".$object->type);
$picto=('product');
dol_fiche_head($head, 'factory', $titre, 0, $picto);
$linkback = '<a href="'.DOL_URL_ROOT.'/product/list.php'.(! empty($socid)?'?socid='.$socid:'').'">';
$linkback.= $langs->trans("BackToList").'</a>';

if ($id || $ref) {
	$bproduit = ($object->isproduct());
	if ($result) {
		if (DOL_VERSION >= "5.0.0") {
			dol_banner_tab($object, 'ref', $linkback, ($user->societe_id?0:1), 'ref');
			$cssclass='titlefield';
			print '<div class="underbanner clearboth"></div>';
		} else {
			print '<table class="border" width="100%">';
			print "<tr>";

			// Reference
			print '<td width="25%">'.$langs->trans("Ref").'</td><td>';
			print $form->showrefnav($object, 'ref', '', 1, 'ref');
			print '</td></tr>';

			// Libelle
			print '<tr><td>'.$langs->trans("Label").'</td>';
			print '<td colspan="3">'.($object->label ? $object->label:$object->libelle).'</td></tr>';

			// Status (to sell)
			print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Sell").')</td><td colspan="2">';
			print $object->getLibStatut(2, 0);
			print '</td></tr>';

			// Status (to buy)
			print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Buy").')</td><td colspan="2">';
			print $object->getLibStatut(2, 1);
			print '</td></tr>';
			print '</table>';
		}

		print '<table class="border" width="100%">';
		// MultiPrix
		if ($conf->global->PRODUIT_MULTIPRICES) {
			if ($socid) {
				$soc = new Societe($db);
				$soc->id = $socid;
				$soc->fetch($socid);

				print '<tr><td width="25%">'.$langs->trans("SellingPrice").'</td>';

				if ($object->multiprices_base_type["$soc->price_level"] == 'TTC')
					print '<td>'.price($object->multiprices_ttc["$soc->price_level"]);
				else
					print '<td>'.price($object->multiprices["$soc->price_level"]);

				if ($object->multiprices_base_type["$soc->price_level"])
					print ' '.$langs->trans($object->multiprices_base_type["$soc->price_level"]);
				else
					print ' '.$langs->trans($object->price_base_type);
				print '</td></tr>';

				// Prix mini
				print '<tr><td>'.$langs->trans("MinPrice").'</td><td>';
				if ($object->multiprices_base_type["$soc->price_level"] == 'TTC')
					print price($object->multiprices_min_ttc["$soc->price_level"]);
				else
					print price($object->multiprices_min["$soc->price_level"]);
					print ' '.$langs->trans($object->multiprices_base_type["$soc->price_level"]);
				print '</td></tr>';

				// TVA
				print '<tr><td>'.$langs->trans("VATRate").'</td>';
				print '<td>'.vatrate($object->multiprices_tva_tx["$soc->price_level"], true).'</td></tr>';
			} else {
				for ($i=1; $i<=$conf->global->PRODUIT_MULTIPRICES_LIMIT; $i++) {
					// TVA
					if ($i == 1) // We show only price for level 1
						 print '<tr><td>'.$langs->trans("VATRate").'</td><td>'.vatrate($object->multiprices_tva_tx[1], true).'</td></tr>';

					print '<tr><td width="25%">'.$langs->trans("SellingPrice").' '.$i.'</td>';
					if ($object->multiprices_base_type["$i"] == 'TTC')
						print '<td>'.price($object->multiprices_ttc["$i"]);
					else
						print '<td>'.price($object->multiprices["$i"]);

					if ($object->multiprices_base_type["$i"])
						print ' '.$langs->trans($object->multiprices_base_type["$i"]);
					else
						print ' '.$langs->trans($object->price_base_type);
					print '</td></tr>';

					// Prix mini
					print '<tr><td>'.$langs->trans("MinPrice").' '.$i.'</td><td>';
					if ($object->multiprices_base_type["$i"] == 'TTC')
						print price($object->multiprices_min_ttc["$i"]);
					else
						print price($object->multiprices_min["$i"]);

					print ' '.$langs->trans($object->multiprices_base_type["$i"]);
					print '</td></tr>';
				}
			}
		} else {
			// TVA
			print '<tr><td width="25%">'.$langs->trans("VATRate").'</td>';
			print '<td>'.vatrate($object->tva_tx.($object->tva_npr?'*':''), true).'</td></tr>';

			// Price
			print '<tr><td>'.$langs->trans("SellingPrice").'</td><td>';
			if ($object->price_base_type == 'TTC') {
				print price($object->price_ttc).' '.$langs->trans($object->price_base_type);
				$sale="";
			} else {
				print price($object->price).' '.$langs->trans($object->price_base_type);
				$sale=$object->price;
			}
			print '</td></tr>';

			// Price minimum
			print '<tr><td>'.$langs->trans("MinPrice").'</td><td>';
			if ($object->price_base_type == 'TTC')
				print price($object->price_min_ttc).' '.$langs->trans($object->price_base_type);
			else
				print price($object->price_min).' '.$langs->trans($object->price_base_type);
			print '</td></tr>';
		}

		print '<tr><td>'.$langs->trans("PhysicalStock").'</td>';
		print '<td>'.$object->stock_reel.'</td></tr>';

		print '</table>';

		dol_fiche_end();

		// indique si on a déjà une composition de présente ou pas
		$compositionpresente=0;

		$head=factory_product_prepare_head($object, $user);
		$titre=$langs->trans("Factory");
		$picto="factory@factory";
		dol_fiche_head($head, 'neworderbuild', $titre, 0, $picto);

		$prodsfather = $factory->getFather(); //Parent Products
		$factory->get_sousproduits_arbo();
		// Number of subproducts
		$prods_arbo = $factory->get_arbo_each_prod();
		// somthing wrong in recurs, change id of object
		$factory->id = $id;
		print_fiche_titre($langs->trans("FactorisedProductsNumber").' : '.count($prods_arbo), '', '');

		// List of subproducts
		if (count($prods_arbo) > 0) {
			$compositionpresente=1;
			print '<b>'.$langs->trans("FactoryTableInfo").'</b><BR>';
			print '<table class="border" >';
			print '<tr class="liste_titre">';
			print '<td class="liste_titre" width=100px align="left">'.$langs->trans("Ref").'</td>';
			print '<td class="liste_titre" width=200px align="left">'.$langs->trans("Label").'</td>';
			print '<td class="liste_titre" width=50px align="center">'.$langs->trans("QtyNeed").'</td>';
			// on affiche la colonne stock même si cette fonction n'est pas active
			print '<td class="liste_titre" width=50px align="center">'.$langs->trans("RealStock").'</td>';
			print '<td class="liste_titre" width=100px align="center">'.$langs->trans("QtyOrder").'</td>';
			if ($conf->stock->enabled) { 	// we display swap titles
				print '<td class="liste_titre" width=100px align="right">'.$langs->trans("UnitPmp").'</td>';
				print '<td class="liste_titre" width=100px align="right">'.$langs->trans("CostPmpHT").'</td>';
			} else { 	// we display price as latest purchasing unit price title
				print '<td class="liste_titre" width=100px align="right">'.$langs->trans("UnitHA").'</td>';
				print '<td class="liste_titre" width=100px align="right">'.$langs->trans("CostHA").'</td>';
			}
			print '<td class="liste_titre" width=100px align="right">'.$langs->trans("FactoryUnitPriceHT").'</td>';
			print '<td class="liste_titre" width=100px align="right">'.$langs->trans("FactorySellingPriceHT").'</td>';
			print '<td class="liste_titre" width=100px align="right">'.$langs->trans("ProfitAmount").'</td>';

			print '</tr>';
			$mntTot=0;
			$pmpTot=0;

			foreach ($prods_arbo as $value) {
				// verify if product have child then display it after the product name
				$tmpChildArbo=$factory->getChildsArbo($value['id']);
				$nbChildArbo="";
				if (count($tmpChildArbo) > 0) $nbChildArbo=" (".count($tmpChildArbo).")";

				print '<tr>';
				print '<td align="left">'.$factory->getNomUrlFactory($value['id'], 1, 'fiche').$nbChildArbo;
				print $factory->PopupProduct($value['id']);
				print '</td>';
				print '<td align="left" title="'.$value['description'].'">';
				print $value['label'].'</td>';
				print '<td align="center">'.$value['nb'];
				if ($value['globalqty'] == 1)
					print "&nbsp;G";
				print '</td>';
				if ($value['fk_product_type']==0) { 	// if product
					$productstatic->fetch($value['id']);
					$productstatic->load_stock();
					print '<td align=center>'.$factory->getUrlStock($value['id'], 1, $productstatic->stock_reel).'</td>';
					$nbcmde=0;
					// on regarde si il n'y pas de commande fournisseur en cours
					$sql = 'SELECT DISTINCT sum(cofd.qty) as nbCmdFourn';
					$sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseurdet as cofd";
					$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."commande_fournisseur as cof ON cof.rowid = cofd.fk_commande";
					$sql.= " WHERE cof.entity = ".$conf->entity;
					$sql.= " AND cof.fk_statut = 3";
					$sql.= " and cofd.fk_product=".$value['id'];
					//print $sql;
					$resql = $db->query($sql);
					if ($resql) {
						$objp = $db->fetch_object($resql);
						if ($objp->nbCmdFourn)
							$nbcmde=$objp->nbCmdFourn;
					}
					print '<td align=right>'.$nbcmde.'</td>';
				} else // no stock management for services
					print '<td></td><td></td>';

				// display else vwap or else latest purchasing price
				print '<td align="right">'.price($value['pmp']).'</td>';
				print '<td align="right">'.price($value['pmp']*$value['nb']).'</td>'; // display total line

				print '<td align="right">'.price($value['price']).'</td>';
				print '<td align="right">'.price($value['price']*$value['nb']).'</td>';
				print '<td align="right">'.price(($value['price']-$value['pmp'])*$value['nb']).'</td>';

				$mntTot=$mntTot+$value['price']*$value['nb'];
				$pmpTot=$pmpTot+$value['pmp']*$value['nb']; // sub total calculation

				print '</tr>';

				//var_dump($value);
				//print '<pre>'.$productstatic->ref.'</pre>';
				//print $productstatic->getNomUrl(1).'<br>';
				//print $value[0];	// This contains a tr line.

			}
			print '<tr class="liste_total">';
			print '<td colspan=5 align=right >'.$langs->trans("Total").'</td>';
			print '<td align="right" >'.price($pmpTot).'</td>';
			print '<td ></td>';
			print '<td align="right" >'.price($mntTot).'</td>';
			print '<td align="right" >'.price($mntTot-$pmpTot).'</td>';
			print '</tr>';
			print '</table>';
		}

		if ($action == 'build' || $action == 'createof') {
            $formproduct = new FactoryFormProduct($db);

			// Display the list of store with buildable product
			print '<br>';
			print_fiche_titre($langs->trans("CreateOF"), '', '');

			print '<form action="fiche.php?id='.$id.'" id="factory_form" method="post">';
			print '<input type="hidden" name="action" value="createof">';
			print '<table class="nobordernopadding"><tr><td width=50% valign=top>';
			print '<table class="border">';
			print '<tr><td width=250px>'.$langs->trans("EntrepotStock").'</td><td width=250px>';

            print $formproduct->selectWarehouses('', 'factory_id_entrepot', 'warehouseopen,warehouseinternal', 0, 0, $object->id, '', 0, 0, null, 'minwidth100');

			print '</td></tr>';
			print '<tr><td>'.$langs->trans("QtyToBuild").'</td>';
			print '<td  ><input style="text-align:right;" type="text" id="factory_nbtobuild" name="nbToBuild" size=5 value="' . $nbToBuild .'">';
			print '</td></tr>';

			print '<tr><td>'.$langs->trans("FactoryDateStartPlanned").'</td>';
			print '<td >';
			$plannedstart=dol_mktime(
							GETPOST('plannedstarthour', 'int'), GETPOST('plannedstartmin', 'int'), 0,
							GETPOST('plannedstartmonth', 'int'), GETPOST('plannedstartday', 'int'),
							GETPOST('plannedstartyear', 'int')
			);
			print $form->select_date(
							(GETPOST("plannedstart")? $plannedstart:''), 'plannedstart',
							1, 1, '', "plannedstart"
			);
			print '</td></tr>';

			print '<tr><td>'.$langs->trans("FactoryDateEndBuildPlanned").'</td>';
			print '<td >';
			$plannedend=dol_mktime(
							GETPOST('plannedendhour', 'int'), GETPOST('plannedendmin', 'int'), 0,
							GETPOST('plannedendmonth', 'int'), GETPOST('plannedendday', 'int'),
							GETPOST('plannedendyear', 'int')
			);
			print $form->select_date(
							(GETPOST("plannedend")? $plannedend:''), 'plannedend',
							1, 1, '', "plannedend"
			);
			print '</td></tr>';

			print '<tr><td>'.$langs->trans("FactoryDurationPlanned").'</td>';
			print '<td>';
			print $form->select_duration(
							'workload', GETPOST("workloadhour")*3600+GETPOST("workloadmin")*60, 0, 'text'
			);
			print '</td></tr>';

			// Other attributes
			$parameters = array('objectsrc' => $objectsrc, 'colspan' => ' colspan="3"', 'socid'=>$socid);
			// Note that $action and $object may have been modified by
			$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $factory, $action);
			// hook
			if (empty($reshook) && ! empty($extrafields->attribute_label)) {
				print $factory->showOptionals($extrafields, 'edit');
			}

			print '<tr><td colspan=2 valign="top">'.$langs->trans("Description").'</td></tr>';
			print '<td colspan=2 align=center>';
			$description=GETPOST("description");
			// on récupère le text de l'extrafields si besoin
			if ($conf->global->factory_extrafieldsNameInfo) {
				$sql = 'SELECT DISTINCT pe.'.$conf->global->factory_extrafieldsNameInfo. ' as addinforecup';
				$sql.= ' FROM '.MAIN_DB_PREFIX.'product_extrafields as pe ';
				$sql.= ' WHERE pe.fk_object =' .$id;
				$resql = $db->query($sql);
				if ($resql) {
					$objp = $db->fetch_object($resql);
					if ($objp->addinforecup)
						$description=$objp->addinforecup;
				}
			}

			print '<textarea name="description" wrap="soft" cols="80" rows="'.ROWS_3.'">'.$description.'</textarea>';
			print '</td></tr>';
			print '</table>';
			print '</td>';
			print '<td valign=top width=50%>';

            //--------------------------------------------------------------------------
            // List of warehouses for each product - OpenDSI - Begin
            //--------------------------------------------------------------------------

            // List of subproducts
            if (count($prods_arbo) > 0) {
                print '<table class="border">';

                foreach ($prods_arbo as $value) {
                    // component product
                    $componentProduct = new Product($db);
                    $componentProduct->fetch($value['id']);

                    $dispactherList = array('id' => $value['id'], 'name' => 'factory', 'line' => 0, 'nb' => intval($value['nb']) * $nbToBuild, 'btn_nb' => 0);

                    // select warehouse where there is enough stock with qty dispatcher
                    print '<tr name="' . $dispactherList['name'] . '_' . $dispactherList['id'] . '_' . $dispactherList['line'] . '">';
                    print '<td class="fieldrequired">' . $componentProduct->ref . '</td>';
                    print '<td>';
                    print $formproduct->selectWarehouses('', $dispactherList['name'] . '_id_entrepot_' . $dispactherList['id'] . '_' . $dispactherList['line'], 'warehouseopen,warehouseinternal', 0, 0, $dispactherList['id'], '', 0, 1, null, 'minwidth100',  '', 1, FALSE);
                    print '</td>';

                    print '<td>';
                    print '<select id="' . $dispactherList['name'] . '_qty_' . $dispactherList['id'] . '_' . $dispactherList['line'] . '" name="' . $dispactherList['name'] . '_qty_' . $dispactherList['id'] . '_' . $dispactherList['line'] . '">';

                    for ($dispatcherQty = 0; $dispatcherQty <= $dispactherList['nb']; $dispatcherQty++) {
                        $dispatcherOptionSelected = '';
                        if ($dispatcherQty === $dispactherList['nb']) {
                            $dispatcherOptionSelected = ' selected="selected"';
                        }

                        print '<option value="' . $dispatcherQty . '"' . $dispatcherOptionSelected . '>' . $dispatcherQty . '</option>';
                    }

                    print '</select>';
                    print '</td>';

                    print '<td name="' . $dispactherList['name'] . '_action_' . $dispactherList['id'] . '_' . $dispactherList['line'] . '">';
                    if ($dispactherList['btn_nb'] === 0 && $dispactherList['nb'] > 1) {
                        print '&nbsp;&nbsp;' . img_picto($langs->trans('AddDispatchBatchLine'), 'split.png', 'onClick="FactoryDispatcher.addLineFromDispatcher(' . $dispactherList['id'] . ',\'' . $dispactherList['name'] . '\')"') . '';
                        $dispactherList['btn_nb']++;
                    }
                    print '</td>';

                    print '</tr>';

                    $dispactherList['line']++;
                }

                print '</table>';
            }

            print '</td></tr>';
            print '<tr>';
            print '<td align=center>';
            if (($action == 'build' || $action == 'createof')) {
                print '<td align=center>';
                print '<input type="submit" class="button" name="createofrun" value="' . $langs->trans("LaunchOF") . '">';
                print '</td>';
            }
            print '</tr>';

            print <<<SCRIPT
    <script type="text/javascript" language="javascript">
        jQuery(document).ready(function(){
            jQuery('#factory_nbtobuild').on('change', function(){
                jQuery('#factory_form').submit();
            })
        });
    </script>
SCRIPT;

            print '</table>';
            print '</form>';
            //--------------------------------------------------------------------------
            // List of warehouses for each product - OpenDSI - End
            //--------------------------------------------------------------------------
		}
	}
}

dol_htmloutput_mesg($mesg);

/* Barre d'action				*/
print '<div class="tabsAction">';
if ($conf->global->FACTORY_ADDON =="" )
	print $langs->trans("NeedToDefineFactorySettingFirst");
else {
	$object->fetch($id, $ref);
	if ($action == '' && $bproduit) {
		if ($user->rights->factory->creer) {
			//Le stock doit être actif et le produit ne doit pas être à l'achat
			if ($conf->stock->enabled && $object->status_buy == 0)
				if ($compositionpresente) {
					print '<a class="butAction" href="fiche.php?action=build&amp;id='.$productid.'">';
					print $langs->trans("LaunchCreateOF").'</a>';
				} else
					print $langs->trans("NeedNotBuyProductAndStockEnabled");
		}
	}
}
print '</div>';
llxFooter();
$db->close();