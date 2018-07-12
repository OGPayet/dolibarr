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
 *  \file	   htdocs/factory/product/direct.php
 *  \ingroup	product
 *  \brief	  Page de fabrication direct sur la fiche produit
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
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';

dol_include_once('/factory/class/factory.class.php');
dol_include_once('/factory/core/lib/factory.lib.php');
dol_include_once('/factory/class/html.factoryformproduct.class.php');

$langs->load("bills");
$langs->load("products");

$id=GETPOST('id', 'int');
$ref=GETPOST('ref', 'alpha');
$action=GETPOST('action', 'alpha');
$confirm=GETPOST('confirm', 'alpha');
$cancel=GETPOST('cancel', 'alpha');
$key=GETPOST('key');
$parent=GETPOST('parent');

// Security check
if (! empty($user->societe_id)) $socid=$user->societe_id;
$fieldvalue = (! empty($id) ? $id : (! empty($ref) ? $ref : ''));
$fieldtype = (! empty($ref) ? 'ref' : 'rowid');
$result=restrictedArea($user,'produit|service', $fieldvalue,'product&product','','', $fieldtype, $objcanvas);

$mesg = '';

$object = new Product($db);
$factory = new Factory($db);
$productid=0;
if ($id || $ref) {
	$result = $object->fetch($id, $ref);
	$productid=$object->id;
	$id=$object->id;
	$factory->id =$id;
}


/*
 * Actions
 */


$factoryIdEntrepot = (GETPOST('factory_id_entrepot', 'int')?intval(GETPOST('factory_id_entrepot', 'int')):-1);
$nbToBuild = (GETPOST('nbToBuild', 'int')?intval(GETPOST('nbToBuild', 'int')):1);

if ($action == 'buildit') {

    $error = 0;
    $mesg = '';

    // check mandatory fields
    if ($factoryIdEntrepot <= 0) {
        $error++;
        $mesg .= '<div class="error">' . $langs->trans("ErrorFieldRequired", $langs->transnoentities("FactoryWarehouse")) . '</div>';
    }

    if ($nbToBuild <= 0)
    {
        $error++;
        $mesg .= '<div class="error">' . $langs->trans("ErrorFieldRequired", $langs->trans("NbToBuild")) . '</div>';
    }

    if (!$error) {
        $factory->get_sousproduits_arbo();
        $prods_arbo = $factory->get_arbo_each_prod();

        if (count($prods_arbo) > 0) {
            $pmpprice = 0;
            $productTouUseList = array();

            foreach ($prods_arbo as $value) {
                // only for product
                if (intval($value['type']) == 0) {
                    $lineNum = 0;
                    $productId = $value['id'];
                    $productNb = intval($value['nb']) * $nbToBuild;

                    // init product stock list by warehouse
                    $productEntrepotStockNbList = array();

                    // load warehouses stocks of this product
                    $productstatic = new Product($db);
                    $productstatic->fetch($productId);
                    $productstatic->load_stock();

                    for ($productNum = 0; $productNum < $productNb; $productNum++)
                    {
                        $productFactoryIdEntrepot = GETPOST('factory_id_entrepot_' . $productId . '_' . $lineNum, 'int');
                        $productFactoryQtyPost = GETPOST('factory_qty_' . $productId . '_' . $lineNum, 'int');
                        $productFactoryQty = intval($productFactoryQtyPost);

                        if ($productFactoryQty >= 1) {
                            if ($productFactoryIdEntrepot <= 0) {
                                $error++;
                                $mesg .= '<div class="error">' . $langs->trans('ErrorFieldRequired', $langs->transnoentities('Warehouse')) . '</div>';
                                break;
                            }

                            if (!isset($productEntrepotStockNbList[$productFactoryIdEntrepot])) {
                                $productEntrepotStockNbList[$productFactoryIdEntrepot] = intval($productstatic->stock_warehouse[$productFactoryIdEntrepot]->real);
                            }

                            // if there is not enough product in the warehouse stock
                            if (($productEntrepotStockNbList[$productFactoryIdEntrepot] - $productFactoryQty) <= 0) {
                                // get product warehouse
                                $productEntrepot = new Entrepot($db);
                                $productEntrepot->fetch($productFactoryIdEntrepot);

                                $error++;
                                $mesg .= '<div class="error">' . $productEntrepot->lieu . ' : ' . $langs->trans('ErrorNotEnoughtComponentToBuild') . '</div>';
                            } else {
                                // add this product to use list and extract from the warehouse stock
                                $productTouUseList[] = array('idEntrepot' => $productFactoryIdEntrepot, 'product' => $productstatic, 'qty' => $productFactoryQty);
                                $productEntrepotStockNbList[$productFactoryIdEntrepot] -= $productFactoryQty;
                            }
                        }

                        $lineNum++;
                    }
                }
            }

            // if no errors and we have product component list
            if (!$error && count($productTouUseList)>0) {
                $pmpprice = 0;

                // use product components and extract them from warehouse stock
                foreach ($productTouUseList as $productToUse) {
                    $productstatic = $productToUse['product'];
                    $pmpprice += $productToUse['qty'] * $productstatic->pmp;

                    $productstatic->correct_stock($user, $productToUse['idEntrepot'], $productToUse['qty'], 1, $langs->trans("ProductUsedForDirectBuild"), $productstatic->price);
                    $mesg .= '<div class="ok"> Produit [id=' . $productstatic->id . '] -> ' . $productToUse['qty'] . '</div>';
                }

                // add the new product to the warehouse
                $object->correct_stock($user, $factoryIdEntrepot, $nbToBuild, 0, $langs->trans("ProductDirectBuilded"), $pmpprice);

                // little message to inform of the number of builded product
                $mesg .= '<div class="ok">' . $nbToBuild . ' ' . $langs->trans("ProductBuilded") . '</div>';
            }
        }

        // return on the product screen
        $action = '';
    }
}

/*
// build product on each store
if ($action == 'buildit') {

	// Loop on each store
	$sql = "SELECT rowid, lieu, zip";
	$sql.= " FROM ".MAIN_DB_PREFIX."entrepot";
	$sql.= " WHERE statut = 1";
	$sql.= " ORDER BY zip ASC";

	dol_syslog("factory/factory.php::Buildit composed product sql=".$sql);

	$resql=$db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		if ($num) {
			$nbTotBuilded=0;
			// loop on each store
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				$fk_entrepot=$obj->rowid;
				$nbToBuild= GETPOST('nbToBuild'.$fk_entrepot, 'int');

				require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
				$mouvP = new MouvementStock($db);
				$mouvP->origin = new Factory($db);
				$mouvP->origin->id = $id;

				// number to build on this store
				if ($nbToBuild > 0) {
					// How number of product is buildable on this store
					$nbmaxfabricable=$factory->getNbProductBuildable($fk_entrepot, $id);
					// verify if the have enough component on the store
					if ($nbToBuild > $nbmaxfabricable) {
						//we can't build the quantity needed
						$error++;
						$action = 'build';
						$mesg='<div class="error">'.$obj->lieu." (".$obj->cp.") :".$langs->trans("ErrorNotEnoughtComponentToBuild").'</div>';
					} else {
						//print "==".$nbToBuild;
						// extract from store the component needed
						$prodsfather = $factory->getFather(); //Parent Products
						$factory->get_sousproduits_arbo();
						$prods_arbo = $factory->get_arbo_each_prod();
						$pmpprice= 0;
						if (count($prods_arbo) > 0) {
							// Loop on products to extract to the store
							foreach ($prods_arbo as $value) {
								// only product, not the services
								if ($value['type']==0) {
									$productstatic = new Product($db);
									$productstatic->id=$value['id'];
									$productstatic->fetch($value['id']);

									$nbToUse=$value['nb']*$nbToBuild;
									$pmpprice+= $value['nb']*$productstatic->pmp;
									// Extract product of the stock
									// 1 = build extract to store
									$productstatic->correct_stock($user, $fk_entrepot, $nbToUse, 1, $langs->trans("ProductUsedForDirectBuild"), $productstatic->price);
								}
							}
						}
						// At the end we build : add the new products to the store
						// need to be on the good product
						$result = $object->fetch($id, $ref);
						// 0 = build add to store
						$object->correct_stock($user, $fk_entrepot, $nbToBuild, 0, $langs->trans("ProductDirectBuilded"), $pmpprice);
						$nbTotBuilded+=$nbToBuild;
					}
				}
				$i++;
			}
			// Little message to inform of the number of builded product
			$mesg='<div class="ok">'.$nbTotBuilded.' '.$langs->trans("ProductBuilded").'</div>';
		}
		// return on the product screen
		$result = $object->fetch($id, $ref);
		$productid=$object->id;
		$action ="";
	}
}
*/


/*
 * View
 */


//print $sql;

$productstatic = new Product($db);
$form = new Form($db);

//llxHeader("", "", $langs->trans("CardProduct".$product->type));
llxHeader('', '', $langs->trans("CardProduct".$product->type), '', 0, 0, array('/custom/factory/js/factory_dispatcher.js'));

dol_htmloutput_mesg($mesg);

$head=product_prepare_head($object, $user);
$titre=$langs->trans("CardProduct".$object->type);
$picto=('product');
dol_fiche_head($head, 'factory', $titre, 0, $picto);
$linkback = '<a href="'.DOL_URL_ROOT.'/product/list.php'.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';
if ($id || $ref) {

	if ($result) {
		if (DOL_VERSION >= "5.0.0"){
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
					print price($object->multiprices_min_ttc["$soc->price_level"]).' '.$langs->trans($object->multiprices_base_type["$soc->price_level"]);
				else
					print price($object->multiprices_min["$soc->price_level"]).' '.$langs->trans($object->multiprices_base_type["$soc->price_level"]);
				print '</td></tr>';

				// TVA
				print '<tr><td>'.$langs->trans("VATRate").'</td>';
				print '<td>'.vatrate($object->multiprices_tva_tx["$soc->price_level"],true).'</td></tr>';
			} else {
				for ($i=1; $i<=$conf->global->PRODUIT_MULTIPRICES_LIMIT; $i++) {
					// TVA
					if ($i == 1) // We show only price for level 1
						print '<tr><td>'.$langs->trans("VATRate").'</td><td>'.vatrate($object->multiprices_tva_tx[1], true).'</td></tr>';

					print '<tr><td  width="25%">'.$langs->trans("SellingPrice").' '.$i.'</td>';

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
						print price($object->multiprices_min_ttc["$i"]).' '.$langs->trans($object->multiprices_base_type["$i"]);
					else
						print price($object->multiprices_min["$i"]).' '.$langs->trans($object->multiprices_base_type["$i"]);
					print '</td></tr>';
				}
			}
		} else {
			// TVA
			print '<tr><td  width="25%">'.$langs->trans("VATRate").'</td>';
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

		// indique si on a d�j� une composition de pr�sente ou pas
		$compositionpresente=0;

		$head=factory_product_prepare_head($object, $user);
		$titre=$langs->trans("Factory");
		$picto="factory@factory";
		dol_fiche_head($head, 'directbuild', $titre, 0, $picto);

		$prodsfather = $factory->getFather(); //Parent Products
		$factory->get_sousproduits_arbo();
		// Number of subproducts
		$prods_arbo = $factory->get_arbo_each_prod();
		// something wrong in recurs, change id of object
		$factory->id = $id;
		$nbcomposantinproduct = count($prods_arbo);
		print_fiche_titre($langs->trans("FactorisedProductsNumber").' : '.$nbcomposantinproduct,'','');


		// List of subproducts
		if (count($prods_arbo) > 0) {
            $compositionpresente = 1;
            print '<b>' . $langs->trans("FactoryTableInfo") . '</b><BR>';
            print '<table class="border" >';
            print '<tr class="liste_titre">';
            print '<td class="liste_titre" width=100px align="left">' . $langs->trans("Ref") . '</td>';
            print '<td class="liste_titre" width=200px align="left">' . $langs->trans("Label") . '</td>';
            print '<td class="liste_titre" width=50px align="center">' . $langs->trans("QtyNeed") . '</td>';
            // on affiche la colonne stock m�me si cette fonction n'est pas active
            print '<td class="liste_titre" width=50px align="center">' . $langs->trans("Stock") . '</td>';
            print '<td class="liste_titre" width=100px align="center">' . $langs->trans("QtyOrder") . '</td>';
            if ($conf->stock->enabled) {    // we display vwap titles
                print '<td class="liste_titre" width=100px align="right">' . $langs->trans("UnitPmp") . '</td>';
                print '<td class="liste_titre" width=100px align="right">' . $langs->trans("CostPmpHT") . '</td>';
            } else {    // we display price as latest purchasing unit price title
                print '<td class="liste_titre" width=100px align="right">' . $langs->trans("UnitHA") . '</td>';
                print '<td class="liste_titre" width=100px align="right">' . $langs->trans("CostHA") . '</td>';
            }
            print '<td class="liste_titre" width=100px align="right">' . $langs->trans("FactoryUnitPriceHT") . '</td>';
            print '<td class="liste_titre" width=100px align="right">' . $langs->trans("SellingPriceHT") . '</td>';
            print '<td class="liste_titre" width=100px align="right">' . $langs->trans("ProfitAmount") . '</td>';

            print '</tr>';
            $mntTot = 0;
            $pmpTot = 0;
            $bAllService = true;

            foreach ($prods_arbo as $value) {
                // verify if product have child then display it after the product name
                $tmpChildArbo = $factory->getChildsArbo($value['id']);
                $nbChildArbo = "";
                if (count($tmpChildArbo) > 0)
                    $nbChildArbo = " (" . count($tmpChildArbo) . ")";

                print '<tr>';
                print '<td align="left">' . $factory->getNomUrlFactory($value['id'], 1, 'direct') . $nbChildArbo;
                print $factory->PopupProduct($value['id']);
                print '</td>';

                print '<td align="left" title="' . $value['description'] . '">';
                print $value['label'] . '</td>';
                print '<td align="center">' . $value['nb'];
                if ($value['globalqty'] == 1)
                    print "&nbsp;G";
                print '</td>';

                if ($conf->stock->enabled) {
                    // we store vwap in variable pmp and display stock
                    $productstatic->fetch($value['id']);
                    $price = $value['price'];
                    $pmp = $value['pmp'];
                    if ($value['fk_product_type'] == 0) {
                        // if product
                        $bAllService = false;
                        $productstatic->load_stock();
                        print '<td align=center>' . $factory->getUrlStock($value['id'], 1, $productstatic->stock_reel) . '</td>';
                        // on regarde si il n'y pas de commande fournisseur en cours
                        $sql = 'SELECT DISTINCT sum(cofd.qty) as nbCmdFourn';
                        $sql .= " FROM " . MAIN_DB_PREFIX . "commande_fournisseurdet as cofd";
                        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "commande_fournisseur as cof ON cof.rowid = cofd.fk_commande";
                        $sql .= " WHERE cof.entity = " . $conf->entity;
                        $sql .= " AND cof.fk_statut = 3";
                        $sql .= " and cofd.fk_product=" . $value['id'];
                        //print $sql;
                        $resql = $db->query($sql);
                        if ($resql) {
                            $objp = $db->fetch_object($resql);
                            if ($objp->nbCmdFourn)
                                $nbcmde = $objp->nbCmdFourn;
                        }
                        print '<td align=right>' . $nbcmde . '</td>';
                    } else
                        print '<td></td>'; // no stock management for services
                } else
                    print '<td></td>'; // no stock management for services

                print '<td align="right">' . price($pmp) . '</td>'; // display else vwap or else latest purchasing price
                print '<td align="right">' . price($pmp * $value['nb']) . '</td>'; // display total line
                print '<td align="right">' . price($price) . '</td>';
                print '<td align="right">' . price($price * $value['nb']) . '</td>';
                print '<td align="right">' . price(($price - $pmp) * $value['nb']) . '</td>';

                $mntTot = $mntTot + $price * $value['nb'];
                $pmpTot = $pmpTot + $pmp * $value['nb']; // sub total calculation

                print '</tr>';

                //var_dump($value);
                //print '<pre>'.$productstatic->ref.'</pre>';
                //print $productstatic->getNomUrl(1).'<br>';
                //print $value[0];	// This contains a tr line.

            }
            print '<tr class="liste_total">';
            print '<td colspan=6 align=right >' . $langs->trans("Total") . '</td>';
            print '<td align="right" >' . price($pmpTot) . '</td>';
            print '<td ></td>';
            print '<td align="right" >' . price($mntTot) . '</td>';
            print '<td align="right" >' . price($mntTot - $pmpTot) . '</td>';
            print '</tr>';
            print '</table>';

            // Display the list of store with buildable product
            print '<br>';
            print_fiche_titre($langs->trans("Building"), '', '');
            print '<b>' . $langs->trans("BuildindListInfo") . '</b><br>';
            print '<form action="direct.php?id=' . $id . '" id="factory_form" method="post">';
            print '<input type="hidden" id="factory_form_action" name="action" value="buildit">';
            print '<table class="border">';

            //-------------------------------------
            // Modification - OpenDSI - Begin
            //-------------------------------------
            // loop on the store

            // select factory warehouse
            $formproduct = new FactoryFormProduct($db);
            print '<tr>';
            print '<td class="fieldrequired">' . $langs->trans("FactoryWarehouse") . '</td>';
            print '<td>';
            print $formproduct->selectWarehouses('', 'factory_id_entrepot', 'warehouseopen,warehouseinternal', 0, 0, $object->id, '', 0, 0, null, 'minwidth100');
            print '</td>';
            print '<td>';
            print '<input style="text-align:right;" type="text" id="factory_nbtobuild" name="nbToBuild" size="2" value="' . $nbToBuild . '" />';
            print '</td>';
            print '</tr>';

            foreach ($prods_arbo as $value) {
                // component product
                $componentProduct = new Product($db);
                $componentProduct->fetch($value['id']);

                $dispactherList = array('id' => $value['id'], 'name' => 'factory', 'line' => 0, 'nb' => intval($value['nb']) * $nbToBuild, 'btn_nb' => 0);

                // select warehouse where there is enough stock with qty dispatcher
                print '<tr name="' . $dispactherList['name'] . '_' . $dispactherList['id'] . '_' . $dispactherList['line'] . '">';
                print '<td class="fieldrequired">' . $componentProduct->ref . '</td>';
                print '<td>';
                print $formproduct->selectWarehouses('', $dispactherList['name'] . '_id_entrepot_' . $dispactherList['id'] . '_' . $dispactherList['line'], 'warehouseopen,warehouseinternal', 0, 0, $dispactherList['id'], '', 0, 1, null, 'minwidth100',  '', 1, TRUE);
                print '</td>';

                print '<td>';
                print '<select id="' . $dispactherList['name'] . '_qty_' . $dispactherList['id'] . '_' . $dispactherList['line'] . '" name="' . $dispactherList['name'] . '_qty_' . $dispactherList['id'] . '_' . $dispactherList['line'] . '">';

                for ($dispatcherQty = 1; $dispatcherQty <= $dispactherList['nb']; $dispatcherQty++) {
                    $dispatcherOptionSelected = '';
                    if ($dispatcherQty === $nbToBuild) {
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

            print '<tr>';
            print '<td colspan="3" align="right">';
            print '<input type="submit" class="button" value="' . $langs->trans("BuildIt") . '" />';
            print '</td>';
            print '</tr>';

            print <<<SCRIPT
    <script type="text/javascript" language="javascript">
        jQuery(document).ready(function(){
            jQuery('#factory_nbtobuild').on('change', function(){
                jQuery('#factory_form_action').val('prebuildit');
                jQuery('#factory_form').submit();
            })
        });
    </script>
SCRIPT;


            /*
            $sql = "SELECT e.rowid, e.lieu, e.zip, IF(ee.rowid IS NULL, 0, 1) as favorite";
            $sql .= " FROM " . MAIN_DB_PREFIX . "entrepot as e";
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "element_element as ee";
            $sql .= " ON (ee.fk_source = " . $productid . " AND ee.sourcetype = 'product' AND ee.targettype = 'stock' AND ee.fk_target = e.rowid)";
            $sql .= " WHERE e.statut = 1";
            $sql .= " ORDER BY e.zip ASC";

            dol_syslog("/factory/product/direct.php::Build composed product sql=" . $sql);
            $resql = $db->query($sql);
            $totFabricable = 0;
            $listNbToBuild = array();
            if ($resql) {
                $num = $db->num_rows($resql);

                $i = 0;
                if ($num) {
                    while ($i < $num) {
                        $obj = $db->fetch_object($resql);
                        // get the number of product buidable on the store
                        $fabricable = $factory->getNbProductBuildable($obj->rowid, $id);
                        if ($fabricable < 0)
                            $fabricable = 0; // il ne sert a rien d'afficher un montant negatif
                        $totFabricable += $fabricable;
                        $listNbToBuild[$obj->favorite . '_' . $fabricable . '_' . $obj->rowid] = $obj->lieu . (!empty($obj->zip) ? " (" . $obj->zip . ")" : "") . " => " . $fabricable;
                        //print "<tr><td>".$obj->lieu." (".$obj->zip.")</td>";
                        //print '<td align=right >';
                        //print '<input style="text-align:right;" type="text" name="nbToBuild'.$obj->rowid.'" size=5 value="'.$fabricable.'">';
                        //print '</td></tr>';
                        $i++;
                    }

                    krsort($listNbToBuild);
                }
            }


            if ($totFabricable > 0 || $bAllService) {
                print '<tr><td>';
                print $form->selectarray('nbtobuild_warehouse', $listNbToBuild, GETPOST('nbToBuild', 'alpha'));
                print '</td><td align=right >';
                print '<input style="text-align:right;" type="text" id="nb_to_build" size=5 value="">';
                print '</td></tr>';
                print <<<SCRIPT
    <script type="text/javascript" language="javascript">
        $(document).ready(function () {
            function setDefaultNbToBuild()
            {
                var select = $('#nbtobuild_warehouse').val();
                var tmp = select.split('_');
                var nb_build = tmp[1];
                var id = tmp[2];

                $('#nb_to_build').val(nb_build).attr('name', 'nbToBuild'+id);
            }

            setDefaultNbToBuild();
            $('#nbtobuild_warehouse').on('change', function() {
                setDefaultNbToBuild();
            })
        });
    </script>
SCRIPT;
            }
            */
            //-------------------------------------
            // Modification - OpenDSI - End
            //-------------------------------------

            //print '<tr>';
            // si il y a du fabricable ou les composants ne sont que des services
            //if ($totFabricable > 0 || $bAllService) {
            //    print '<td colspan=3 align=right>';
            //    print '<input type="submit" class="button" value="' . $langs->trans("BuildIt") . '">';
            //} else {
            //    print '<td colspan=3 align=left>';
            //    print $langs->trans("NotEnoughStockForBuildIt");
            //}
            //print '</td>';
            //print '</tr>';

            print '</table>';
            print '</form>';
        }
		else
			print_fiche_titre("<br>".$langs->trans("NothingtoBuild")."<br><br>", '', '');
	}
}

llxFooter();
$db->close();