<?php
/* Copyright (C) 2012-2017	Charlie BENKE	<charlie@patas-monkey.com>
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
 *	\file	   htdocs/equipement/composition.php
 *	\ingroup	equipement
 *	\brief	  Page d'affichage de la composition d'un equipement
 */

$res=@include("../main.inc.php");					// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res)
	$res=@include("../../main.inc.php");		// For "custom" directory

require_once DOL_DOCUMENT_ROOT."/core/lib/functions2.lib.php";
require_once DOL_DOCUMENT_ROOT."/product/class/product.class.php";
require_once DOL_DOCUMENT_ROOT."/product/class/product.class.php";
require_once DOL_DOCUMENT_ROOT."/core/class/html.form.class.php";

dol_include_once('/equipement/class/equipement.class.php');
dol_include_once('/equipement/core/lib/equipement.lib.php');

dol_include_once('/factory/class/factory.class.php');
dol_include_once('/factory/class/html.factoryformproduct.class.php');

$langs->load('companies');
$langs->load("equipement@equipement");

$id 	= GETPOST('id', 'int');
$ref	= GETPOST('ref', 'alpha');
$action	= GETPOST('action');

$addProductId = (GETPOST('add_product_id')?GETPOST('add_product_id', 'int'):-1);
$addProductEntropotId = (GETPOST('add_product_entrepot_id')?GETPOST('add_product_entrepot_id', 'int'):-1);

$object = new Equipement($db);
$object->fetch($id, $ref);
if ($id == 0)
	$id = $object->id;


// Security check
if ($user->societe_id)
	$socid=$user->societe_id;
$result = restrictedArea($user, 'equipement', $id, 'equipement', '', 'fk_soc_client');


/*
*	Action
*/

if ($action == 'addproductline')
{
    $error = 0;
    $msgs = '';

    $addProductQty = 1;

    if ($addProductId <= 0) {
        $error++;
        $msgs .= $langs->trans('ErrorFieldRequired', $langs->transnoentities('Ref') ) . '<br />';
    }

    if ($addProductEntropotId <= 0) {
        $error++;
        $msgs .= $langs->trans('ErrorFieldRequired', $langs->transnoentities('Warehouse') ) . '<br />';
    }

    if (!$error) {
        // add to the factory components
        $factory = new Factory($db);
        $addProduct = new Product($db);
        $addProduct->fetch($addProductId);

        if (!$addProduct->id) {
            $error++;
            $msgs .= $langs->trans('ErrorEquipementProductNotFound') . '<br />';
        }

        // warehouse of product
        $entrepot = new Entrepot($db);
        $entrepot->fetch($addProductEntropotId);

        if (!$entrepot->id) {
            $error++;
            $msgs .= $langs->trans('ErrorEquipementWarehouseNotFound') . '<br />';
        }

         if (!$error) {

            // check if this component has not been linked to this equipement yet
            $addProductFactoryId = -1;
            $sql  = "SELECT rowid";
            $sql .= " FROM " . MAIN_DB_PREFIX . "product_factory";
            $sql .= " WHERE fk_product_father = " . $object->fk_product;
            $sql .= " AND fk_product_children = " . $addProductId;

            $resql = $db->query($sql);
            if ($resql) {
                if ($obj = $db->fetch_object($resql)) {
                    $addProductFactoryId = $obj->rowid;
                }
            }

            // check if enough stock for this product
            $addProduct->load_stock();

            $addProductStockQty = intval($addProduct->stock_warehouse[$addProductEntropotId]->real);

            if ($addProductQty > $addProductStockQty) {
                // not enough stock
                $error++;
                $msgs .= $langs->trans('ErrorEquipementAddProductNotEnoughStock') . '<br />';
            }

            if (!$error) {
                // remove product from warehouse stock
                $res = $addProduct->correct_stock($user, $addProductEntropotId, $addProductQty, 1, $langs->trans("ProductUsedForDirectBuild"), $addProduct->price);

                if (!$res) {
                    $error++;
                    $msgs .= $langs->trans('ErrorEquipementAddProductCorrectStock') . '<br />';
                }
            }

            if (!$error) {
                // if this product has not been a component of this equipement
                if ($addProductFactoryId <= 0) {
                    // add product component in the factory
                    $res = $factory->add_component($object->fk_product, $addProduct->id, $addProductQty, $addProduct->pmp, $addProduct->price);
                } else {
                    $sql  = "UPDATE " . MAIN_DB_PREFIX . "product_factory";
                    $sql .= " SET qty = qty + " . $addProductQty;
                    $sql .= " WHERE rowid = " . $addProductFactoryId;

                    $res = $db->query($sql);
                }

                if (!$res) {
                    $error++;
                    $msgs .= $langs->trans('ErrorEquipementAddProductComponent') . '<br />';
                }
            }
        }
    }

    if ($error) {
        setEventMessage($msgs, 'errors');
    } else {
        $msgs = $langs->trans('SuccessEquipementAddProduct') . '<br />';
        setEventMessage($msgs);
    }

    $action = '';
}


/*
*	View
*/

llxHeader();

$form = new Form($db);

$societe = new Societe($db);
$societe->fetch($object->socid);

$head = equipement_prepare_head($object);
dol_fiche_head($head, 'composition', $langs->trans('EquipementCard'), 0, 'equipement@equipement');

$prod=new Product($db);
$prod->fetch($object->fk_product);
$factory=new Factory($db);

print '<table class="border" width="100%">';
print '<tr><td width="25%">'.$langs->trans('Ref').'</td><td colspan="3">';
print $form->showrefnav($object, 'ref', '', 1, 'ref', 'ref');
print '</td></tr>';

print '<tr><td class="fieldrequired">'.$langs->trans("Product").'</td>';
print '<td>'.$prod->getNomUrl(1)." : ".$prod->label.'</td></tr>';

// fournisseur
print '<tr><td >'.$langs->trans("Fournisseur").'</td><td>';
if ($object->fk_soc_fourn > 0) {
	$soc=new Societe($db);
	$soc->fetch($object->fk_soc_fourn);
	print $soc->getNomUrl(1);
}
print '</td></tr>';

// client
print '<tr><td >'.$langs->trans("Client").'</td><td>';
if ($object->fk_soc_client > 0) {
	$soc=new Societe($db);
	$soc->fetch($object->fk_soc_client);
	print $soc->getNomUrl(1);
}
print '</td></tr>';
print "</table>";
print '<br>';

// display the parent if they have a parent
$componentstatic=new Equipement($db);
$tblParent=$componentstatic->get_parent($id);
if (count($tblParent) > 0) {
	print '<b>'.$langs->trans("EquipementParentAssociation").'</b><BR>';
	$productstatic=new Product($db);
	$productstatic->id=$tblParent[1];
	$productstatic->fetch($tblParent[1]);

	$parentstatic=new Equipement($db);
	$parentstatic->fetch($tblParent[0]);
	print '<table class="border" >';
	print '<tr class="liste_titre">';
	print '<td class="liste_titre" width=150px align="left">'.$langs->trans("Ref").'</td>';
	print '<td class="liste_titre" width=200px align="left">'.$langs->trans("Label").'</td>';
	print '<td class="liste_titre" width=150px align="center">'.$langs->trans("Equipement").'</td>';
	print '</tr>';

	print '<tr>';
	print '<td align="left">'.$productstatic->getNomUrl(1, 'composition').'</td>';
	print '<td align="left">'.$productstatic->label.'</td>';
	print '<td align="left">'.$parentstatic->getNomUrl(1).'</td>';
	print '</tr>';
	print '</table ><br>';
}

$factory->id=$object->fk_product;

// un OF est-il li� � l'�quipement (factory)?
$factoryid = $factory->get_equipement_linked($id);
if ($factoryid >0 )
	$factory->get_sousproduits_factory_arbo($factoryid);
else
	$factory->get_sousproduits_arbo();

$prods_arbo = $factory->get_arbo_each_prod();

// save the equipement component
if ($action == 'save' && $user->rights->equipement->creer) {
	if (count($prods_arbo) > 0) {
		foreach ($prods_arbo as $value) {
			if ($value['type']==0) {
				// on boucle sur le nombre d'�quipement saisie
				for ($i=0; $i < $value['nb']; $i++) {
				    $refComponent = GETPOST('ref_'.$value['id'].'_'.$i);
					// on enregistre ce qui a �t� saisie
					$object->set_component($id, $value['id'], $i, $refComponent);
                    if ($refComponent) {
                        $componentstatic=new Equipement($db);
                        $componentstatic->fetch('', $refComponent);
                        $componentstatic->update_note(GETPOST('note_'.$value['id'].'_'.$i), '_private');
                    }
				}
			}
		}
	}
}

// Number of subproducts
//print_fiche_titre($langs->trans("AssociatedProductsNumber").' : '.count($prod->get_arbo_each_prod()),'','');

// List of subproducts
if (count($prods_arbo) > 0) {
	print '<b>'.$langs->trans("EquipementChildAssociationList").'</b><BR>';
	print '<form action="'.dol_buildpath('/equipement', 1).'/composition.php?id='.$id.'" method="post">';
	print '<input type="hidden" name="action" value="save">';
	print '<table class="border" width="100%">';
	print '<tr class="liste_titre">';
	print '<td class="liste_titre" width=100px align="left">'.$langs->trans("Ref").'</td>';
	print '<td class="liste_titre" width=200px align="left">'.$langs->trans("Label").'</td>';
    print '<td class="liste_titre" width=150px align="left">'.$langs->trans("Equipementcomposant").'</td>';
    print '<td class="liste_titre" width=150px align="center">'.$langs->trans("Note").'</td>';
	print '</tr>';

	foreach ($prods_arbo as $value) {
		$productstatic=new Product($db);
		$productstatic->id=$value['id'];
		$productstatic->fetch($value['id']);
		$productstatic->type=$value['type'];

		if ($value['type']==0) {
			// on boucle sur le nombre d'�quipement � saisir
			for ($i=0; $i < $value['nb']; $i++) {
				print '<tr>';
				print '<td width=100px align="left">'.$productstatic->getNomUrl(1, 'composition').'</td>';
				print '<td width=200px align="left">'.$productstatic->label.'</td>';
				$componentstatic=new Equipement($db);

				$refComponent=$componentstatic->get_component($id, $value['id'], $i);
				print '<td width=150px align="left">';
				if ($refComponent) {
					$componentstatic->fetch('', $refComponent);
					print $componentstatic->getNomUrl(2);
					print "&nbsp;&nbsp;";
				}

				// serial number field
				$refFieldName = 'ref_'.$value['id'].'_'.$i;

                $resql = $componentstatic->findAllInWarehouseByFkProduct($productstatic->id);
				if (!$resql || $resql->num_rows<=0) {
                    print '<input type="text" name="'.$refFieldName.'" value="'.$refComponent.'">';
                } else {
                    print '<select name="'.$refFieldName.'">';
                    print '<option value=""></option>';

                    while ($obj = $db->fetch_object($resql)) {
                        $optionSelected = '';

                        if ($obj->ref == $refComponent) {
                            $optionSelected = ' selected="selected"';
                        }

                        print '<option value="'. $obj->ref .'"' . $optionSelected .'>' . $obj->ref . '</option>';
                    }

                    print '</select>';
                }

				print '<td align="left">';
                require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
                $doleditor = new DolEditor('note_'.$value['id'].'_'.$i, dol_escape_htmltag($componentstatic->note_private), '', '100', 'dolibarr_notes', 'In', 0, true, true, 20, '100');
                print $doleditor->Create(1);
               // print '<input type="text" name="note_'.$value['id'].'_'.$i.'" value="'..'">';
                print '</td>';
				print '</td></tr>';
			}
		} else {
			// pas de num�ro de s�rie � saisir sur la main-d'oeuvre
			print '<tr>';
			print '<td align="left">'.$productstatic->getNomUrl(1, 'composition').'</td>';
			print '<td align="left">'.$productstatic->label.'</td>';
			print '<td></td>';
			print '</tr>';
		}
		print '</tr>';
	}
	print '<tr>';
	print '<td colspan=3 align=right><input type="submit" class="button" value="'.$langs->trans("Update").'"></td>';
	print '</tr>';

	print '</table>';
	print '</form>';
}

// form to add product line
$form = new Form($db);
$factoryformproduct = new FactoryFormProduct($db);

print '<br />';
print '<b>'.$langs->trans("EquipementAddProductLine").'</b><BR>';
print '<form action="'.dol_buildpath('/equipement', 1).'/composition.php?id='.$id.'" method="post">';
print '<input type="hidden" name="action" value="addproductline">';
print '<table class="border" width="50%">';

// product
$events = array();
$events[] = array('action' => 'getWarehouses', 'url' => dol_buildpath('/equipement/ajax/warehouses.php', 1), 'htmlname' => 'add_product_entrepot_id', 'params' => array());
print '<tr>';
print '<td width="50%">';
print $langs->trans("Product");
print '</td>';
print '<td>';
$form->select_produits($addProductId, 'add_product_id', '', 20,0,1,2,'', 0);
print '</td>';
print '</tr>';

// warehouse (select the warehouse from product ref)
print '<tr>';
print '<td>';
print $langs->trans("Warehouse");
print '</td>';
print '<td>';
print $factoryformproduct->selectWarehouses('', 'add_product_entrepot_id', 'warehouseopen,warehouseinternal', 0, 0, $addProductId, '', 0, 0, null, '', '', 1, TRUE);
print '</td>';
print '</tr>';

// add javascript code for ajax auto completion of warehouse
print $factoryformproduct->add_select_events('add_product_id', $events);

// action add
print '<tr>';
print '<td>';
print '</td>';
print '<td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Add").'" />';
print '</td>';
print '</tr>';

print '</table>';
print '</form>';

print '</div>';

llxFooter();
$db->close();