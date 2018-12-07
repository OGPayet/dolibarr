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
if (!empty($conf->equipement->enabled)) {
    dol_include_once('/equipement/class/equipement.class.php');
}

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

// all dispatched lines
$dispatchLineList = array();
$dispatchLinePrefixName = 'factory';
$factoryIdEntrepot = GETPOST('factory_id_entrepot', 'int') ? intval(GETPOST('factory_id_entrepot', 'int')) : -1;
$nbToBuild = GETPOST('nbToBuild', 'int') ? intval(GETPOST('nbToBuild', 'int')) : 1;
if ($nbToBuild <= 0) {
    $nbToBuild = 1;
}


/*
 * Actions
 */
if ($cancel == $langs->trans("Cancel"))
	$action = '';

// build product on each store
if ($action == 'createof' && GETPOST('createofrun') && $user->rights->factory->creer) {
    // error num
    $error = 0;

    $factory->get_sousproduits_arbo();
    $prods_arbo = $factory->get_arbo_each_prod();

    $factory->fk_entrepot = $factoryIdEntrepot;
    $factory->qty_planned = GETPOST("nbToBuild");
    $factory->date_start_planned = dol_mktime(
        GETPOST('plannedstarthour', 'int'), GETPOST('plannedstartmin', 'int'), 0,
        GETPOST('plannedstartmonth', 'int'), GETPOST('plannedstartday', 'int'), GETPOST('plannedstartyear', 'int')
    );
    $factory->date_end_planned = dol_mktime(
        GETPOST('plannedendhour', 'int'), GETPOST('plannedendmin', 'int'), 0,
        GETPOST('plannedendmonth', 'int'), GETPOST('plannedendday', 'int'), GETPOST('plannedendyear', 'int')
    );
    $factory->duration_planned = GETPOST("workloadhour")*3600+GETPOST("workloadmin")*60;
    $factory->description = GETPOST("description");

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
    $warehouseToUseList               = array();
    $componentProductEquipementIdList = array();
    if (count($prods_arbo) > 0) {
        // for all posted values
        foreach ($_POST as $postKey => $postValue) {
            $matches = array();

            // it's a dispatched line of factory
            if (preg_match('#^' . $dispatchLinePrefixName . '_([0-9]+)_id_component_product_([0-9]+)_([0-9]+)$#', $postKey, $matches)) {
                $indiceFactoryBuild = intval($matches[1]);
                $componentProductId = intval($matches[2]);
                $lineNum            = $matches[3];

                $dispatcherPrefix = $dispatchLinePrefixName . '_' . $indiceFactoryBuild . '_';
                $dispatcherSuffix = $componentProductId . '_' . $lineNum;

                $componentProductNb              = GETPOST($dispatcherPrefix . 'nb_component_product_' . $dispatcherSuffix, 'int');
                $componentProductIdEntrepot      = GETPOST($dispatcherPrefix . 'id_entrepot_' . $dispatcherSuffix, 'int');
                $componentProductQtyPost         = GETPOST($dispatcherPrefix . 'qty_' . $dispatcherSuffix, 'int');
                $componentProductToSerialize     = GETPOST($dispatcherPrefix . 'product_serializel_' . $dispatcherSuffix, 'int');
                $componentProductEquipementArray = GETPOST($dispatcherPrefix . 'equipementl_' . $dispatcherSuffix, 'array') ? GETPOST($dispatcherPrefix . 'equipementl_' . $dispatcherSuffix, 'array') : array();

                // add to dispatched lines
                $componentProduct = new Product($db);
                $componentProduct->fetch($componentProductId);
                $dispatchLineList[] = array(
                    'component_product'    => $componentProduct,
                    'indice_factory_build' => $indiceFactoryBuild,
                    'line'                 => $lineNum,
                    'nb'                   => $componentProductNb
                );

                // set line for errors
                $errorLine = $langs->trans('FactoryBuildIndice') . ' '  . ($indiceFactoryBuild+1) . ' - ' . $langs->trans('Product') . ' ' . $componentProduct->ref . ' - ' . $langs->trans('Line') . ' ' . ($lineNum+1);

                $componentProductQty = intval($componentProductQtyPost);

                // check qty and at least one warehouse
                if ($componentProductQty>0 && empty($componentProductIdEntrepot) && $componentProduct->type == PRODUCT::TYPE_PRODUCT) {
                    $error++;
                    $factory->error    = $errorLine . ' : ' . $langs->trans('ErrorFieldRequired', $langs->transnoentities('Warehouse'));
                    $factory->errors[] = $factory->error;
                }

                // it's a product to serialize
                if ($componentProductToSerialize==1) {
                    // check qty with equipment selected list
                    if (count($componentProductEquipementArray) != $componentProductQty) {
                        $error++;
                        $factory->error    = $errorLine . ' : ' . $langs->trans('EquipementErrorQtyToBuild');
                        $factory->errors[] = $factory->error;
                    }

                    // check all serial numbers for each component product
                    if (!isset($componentProductEquipementIdList[$componentProductId])) {
                        $componentProductEquipementIdList[$componentProductId] = array();
                    }
                    foreach ($componentProductEquipementArray as $equipementId) {
                        if (in_array($equipementId, $componentProductEquipementIdList[$componentProductId])) {
                            $error++;
                            $factory->error    = $errorLine . ' : ' . $langs->trans('EquipementErrorReferenceAlreadyUsed');
                            $factory->errors[] = $factory->error;
                        } else {
                            $componentProductEquipementIdList[$componentProductId][] = $equipementId;
                        }
                    }
                }

                // add warehouses to use
                if (!isset($warehouseToUseList[$componentProductId])) {
                    $warehouseToUseList[$componentProductId] = array();
                }
                if (!isset($warehouseToUseList[$componentProductId][$indiceFactoryBuild])) {
                    $warehouseToUseList[$componentProductId][$indiceFactoryBuild] = array();
                }
                $warehouseToUseList[$componentProductId][$indiceFactoryBuild][] = array('fk_entrepot' => $componentProductIdEntrepot, 'qty' => $componentProductQty, 'id_equipement_list' => $componentProductEquipementArray);
            }
        }
    }

    if (!$error) {
        $now = dol_now();

        $db->begin();

        // Fill array 'array_options' with data from add form
        $ret = $extrafields->setOptionalsFromPost($extralabels, $factory);
        if ($ret < 0) {
            $error++;
            $factory->error    = $extrafields->error;
            $factory->errors[] = $factory->error;
        }

        // create of
        if (!$error) {
            $newref = $factory->createof($warehouseToUseList);
            if ($newref < 0) {
                $error++;
            }

            // set date start to now
            if (!$error) {
                $ret = $factory->set_datestartmade($user, $now);
                if ($ret < 0) {
                    $error++;
                }
            }
        }

        // associate equipment list for each product and warehouses
        if (!$error && !empty($conf->equipement->enabled)) {
            // get all lines of this factory
            $sql  = "SELECT fd.rowid";
            $sql .= ", fd.fk_product";
            $sql .= ", fd.id_dispatched_line";
            $sql .= ", fd.indice_factory_build";
            $sql .= " FROM " . MAIN_DB_PREFIX . "factorydet as fd";
            $sql .= " WHERE fd.fk_factory = " . $factory->id;

            $resql = $db->query($sql);
            if (!$resql) {
                $error++;
                $factory->error    = $db->lasterror();
                $factory->errors[] = $factory->error;
            }
            if (!$error) {
                while ($obj = $db->fetch_object($resql)) {
                    $fkFactoryDet       = $obj->rowid;
                    $componentProductId = $obj->fk_product;
                    $indiceFactoryBuild = intval($obj->indice_factory_build);
                    $idDispatchedLine   = intval($obj->id_dispatched_line);

                    if (isset($warehouseToUseList[$componentProductId][$indiceFactoryBuild][$idDispatchedLine])) {
                        $equipementIdList = $warehouseToUseList[$componentProductId][$indiceFactoryBuild][$idDispatchedLine]['id_equipement_list'];

                        foreach ($equipementIdList as $equipementId) {
                            $equipement = new Equipement($db);
                            $equipement->fetch($equipementId);

                            // add line fk_equipement, fk_factory and fk_factorydet in equipementevt
                            $ret = $equipement->addline($equipement->id, -1, '', $now, $now, '', '', '', '', '', '', 0, 0, 0, 0, $factory->id, $fkFactoryDet);
                            if ($ret < 0) {
                                $error++;
                                $factory->error    = $equipement->errorsToString();
                                $factory->errors[] = $factory->error;
                            }

                            if ($error) {
                                break;
                            }
                        }
                    }

                    if ($error) {
                        break;
                    }
                }
            }
        }

        // commit or rollback
        if ($error) {
            $db->rollback();
        } else {
            $db->commit();
        }
    }

    if ($error) {
        setEventMessages($factory->error, $factory->errors, 'errors');
        $action = 'build';
    } else {
        // Little message to inform of the number of builded product
        setEventMessage($langs->trans("FactoryOrderSaved"), 'mesgs');

        // on affiche la liste des of en cours pour le produit
        //header("Location: list.php?fk_status=1&id=".$id);

        // go to the report of factory
        header("Location: " . dol_buildpath("/factory/report.php?id=" . $factory->id, 1));
        exit();
    }
}


/*
 * View
 */


$productstatic = new Product($db);
$form = new Form($db);
if (!empty($conf->equipement->enabled)) {
    $equipementStatic = new Equipement($db);
}

llxHeader("", "", $langs->trans("CardProduct".$product->type), '', 0, 0, array('/custom/factory/js/factory_dispatcher.js?sid=' . dol_now()));

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
			}
			print '<tr class="liste_total">';
			print '<td colspan=5 align=right >'.$langs->trans("Total").'</td>';
			print '<td align="right">'.price($pmpTot).'</td>';
			print '<td ></td>';
			print '<td align="right">'.price($mntTot).'</td>';
			print '<td align="right">'.price($mntTot-$pmpTot).'</td>';
            print '<td ></td>';
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
            if (!empty($conf->equipement->enabled)) {
                print '<input type="hidden" id="url_to_get_all_equipement_in_warehouse" name="url_to_get_all_equipement_in_warehouse" value="' . dol_buildpath('/equipement/ajax/all_equipement_in_warehouse.php', 1) . '" />';
            }

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

                // all js lines
                $outjsLineList = array();

                if (empty($dispatchLineList)) {
                    for ($indiceFactoryBuild = 0; $indiceFactoryBuild < $nbToBuild; $indiceFactoryBuild++) {
                        foreach ($prods_arbo as $value) {
                            // component product
                            $componentProduct = new Product($db);
                            $componentProduct->fetch($value['id']);
                            $dispatchLineList[] = array(
                                'component_product'    => $componentProduct,
                                'indice_factory_build' => $indiceFactoryBuild,
                                'line'                 => 0,
                                'nb'                   => intval($value['nb'])
                            );
                        }
                    }
                }

                foreach ($dispatchLineList as $dispatchLine) {
                    // javascript for line
                    $outjs = '';

                    // component product
                    $componentProduct   = $dispatchLine['component_product'];
                    $componentProductId = $componentProduct->id;

                    // dispatcher
                    $indiceFactoryBuild = $dispatchLine['indice_factory_build'];
                    $dispactherList = array(
                        'id'           => $componentProductId,
                        'name'         => $dispatchLinePrefixName . '_' . $indiceFactoryBuild,
                        'line'         => $dispatchLine['line'],
                        'nb'           => $dispatchLine['nb'],
                        'btn_nb'       => 0,
                        'mode'         => 'select',
                        'unlock_qty'   => 'false',
                        'element_type' => ''
                    );
                    $dispatcherPrefix = $dispactherList['name'] . '_';
                    $dispatcherSuffix = $dispactherList['id'] . '_' . $dispactherList['line'];

                    // select warehouse where there is enough stock with qty dispatcher
                    print '<tr name="' . $dispatcherPrefix . $dispatcherSuffix . '">';

                    print '<td>' . $langs->trans('FactoryBuildIndice') . ($indiceFactoryBuild+1) . '</td>';

                    // dispatch component ref
                    print '<td class="fieldrequired">';
                    print '<input type="hidden" id="' . $dispatcherPrefix . 'id_component_product_' . $dispatcherSuffix . '" name="' . $dispatcherPrefix . 'id_component_product_' . $dispatcherSuffix . '" value="' . $dispactherList['id'] . '" />';
                    print '<input type="hidden" id="' . $dispatcherPrefix . 'nb_component_product_' . $dispatcherSuffix . '" name="' . $dispatcherPrefix . 'nb_component_product_' . $dispatcherSuffix . '" value="' . $dispactherList['nb'] . '" />';
                    print '&nbsp;&nbsp;' . $componentProduct->getNomUrl(1) . '&nbsp;&nbsp;';
                    print '</td>';

                    // dispatch component warehouse
                    if ($componentProduct->type == PRODUCT::TYPE_PRODUCT) {
                        $componentEntrepotId = GETPOST($dispatcherPrefix . 'id_entrepot_' . $dispatcherSuffix, 'int') ? GETPOST($dispatcherPrefix . 'id_entrepot_' . $dispatcherSuffix, 'int') : '';
                        print '<td>';
                        print $formproduct->selectWarehouses($componentEntrepotId, $dispatcherPrefix . 'id_entrepot_' . $dispatcherSuffix, 'warehouseopen,warehouseinternal', 0, 0, $dispactherList['id'], '', 0, 1, null, 'minwidth100', '', 1, TRUE);
                        print '</td>';
                    } else {
                        print '<td></td>';
                    }

                    // dispatch component qty to use
                    if (!isset($_POST[$dispatcherPrefix . 'qty_' . $dispatcherSuffix])) {
                        $componentProductQty = $dispactherList['nb'];
                    } else {
                        $componentProductQty = GETPOST($dispatcherPrefix . 'qty_' . $dispatcherSuffix, 'int');
                    }
                    print '<td>';
                    print '&nbsp;&nbsp;' . $langs->trans('Qty') . ' : ';
                    print '<select id="' . $dispatcherPrefix . 'qty_' . $dispatcherSuffix . '" name="' . $dispatcherPrefix . 'qty_' . $dispatcherSuffix . '">';
                    for ($dispatcherQty = 0; $dispatcherQty <= $dispactherList['nb']; $dispatcherQty++) {
                        $dispatcherOptionSelected = '';
                        if ($dispatcherQty == $componentProductQty) {
                            $dispatcherOptionSelected = ' selected="selected"';
                        }

                        print '<option value="' . $dispatcherQty . '"' . $dispatcherOptionSelected . '>' . $dispatcherQty . '</option>';
                    }
                    print '</select>';
                    print '</td>';

                    // dispatch equipment
                    if (!empty($conf->equipement->enabled)) {
                        $componentProductToSerialize = 0;
                        $multiSelectEquipement = '';

                        print '<td>';
                        if ($componentProduct->array_options['options_synergiestech_to_serialize']==1) {
                            $componentProductToSerialize = 1;
                            $dispactherList['element_type'] = 'equipement';

                            // find all equipments for a product and warehouse
                            if ($componentEntrepotId > 0) {
                                $equipementList   = array();
                                $idEquipementList = GETPOST($dispatcherPrefix . 'equipementl_' . $dispatcherSuffix, 'array') ? GETPOST($dispatcherPrefix . 'equipementl_' . $dispatcherSuffix, 'array') : array();

                                $resql = $equipementStatic->findAllByFkProductAndFkEntrepot($dispactherList['id'], $componentEntrepotId);
                                if ($resql) {
                                    while ($obj = $db->fetch_object($resql)) {
                                        $equipementList[$obj->rowid] = $obj->ref;
                                    }

                                    $multiSelectEquipement = Form::multiselectarray($dispatcherPrefix . 'equipementl_' . $dispatcherSuffix, $equipementList, $idEquipementList, 0, 0, '', 0, 200);
                                }
                            }

                            // equipment multiselect (in warehouse selected)
                            print '&nbsp;&nbsp;' . $langs->trans('Equipement') . ' : ' . '<span id="' . $dispatcherPrefix . 'equipementl_multiselect_' . $dispatcherSuffix . '">' . $multiSelectEquipement . '</span>';

                            if (empty($multiSelectEquipement)) {
                                $outjs .= 'FactoryDispatcher.getAllEquipementInSelectedWarehouse(\'' . $dispactherList['id'] . '\',  \'' . $dispactherList['name'] . '\', \'' . $dispactherList['line'] . '\');';
                            }
                            // on warehouse change
                            $outjs .= 'jQuery("#' . $dispatcherPrefix . 'id_entrepot_' . $dispatcherSuffix . '").change(function(){';
                            $outjs .= 'FactoryDispatcher.getAllEquipementInSelectedWarehouse(\'' . $dispactherList['id'] . '\', \'' . $dispactherList['name'] . '\', \'' . $dispactherList['line'] . '\');';
                            $outjs .= '});';
                        }
                        print '<input type="hidden" name="' . $dispatcherPrefix . 'product_serializel_' . $dispatcherSuffix . '" value="' . $componentProductToSerialize . '" />';
                        print '</td>';
                    }

                    // dispatch action
                    print '<td name="' . $dispatcherPrefix . 'action_' . $dispatcherSuffix . '">';
                    if ($dispactherList['btn_nb'] === 0 && $dispactherList['nb'] > 1) {
                        print '&nbsp;&nbsp;' . img_picto($langs->trans('AddDispatchBatchLine'), 'split.png', 'onClick="FactoryDispatcher.addLineFromDispatcher(' . $dispactherList['id'] . ',\'' . $dispactherList['name'] . '\', \'' . $dispactherList['mode'] . '\', ' . $dispactherList['unlock_qty'] . ', \'' . $dispactherList['element_type'] . '\')"');
                        $dispactherList['btn_nb']++;
                    }
                    print '</td>';

                    print '</tr>';

                    $outjsLineList[] = $outjs;
                    $dispactherList['line']++;
                }

                $out  = '';
                $out .= '<script type="text/javascript">';
                $out .= 'jQuery(document).ready(function(){';
                foreach ($outjsLineList as $outjs) {
                    $out .= $outjs;
                }
                $out .= '});';
                $out .= '</script>';
                print $out;

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