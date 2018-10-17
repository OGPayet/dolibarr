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
 *  \file	   htdocs/factory/report.php
 *  \ingroup	factory
 *  \brief	  Page des Ordres de fabrication sur la fiche produit
 */

$res=@include("../main.inc.php");					// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../main.inc.php");		// For "custom" directory

require_once DOL_DOCUMENT_ROOT."/core/lib/product.lib.php";
require_once DOL_DOCUMENT_ROOT."/product/class/product.class.php";
require_once DOL_DOCUMENT_ROOT."/product/stock/class/entrepot.class.php";
require_once DOL_DOCUMENT_ROOT."/product/stock/class/mouvementstock.class.php";
require_once DOL_DOCUMENT_ROOT."/categories/class/categorie.class.php";

require_once DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php";
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT."/core/lib/date.lib.php";

dol_include_once('/factory/class/factory.class.php');
dol_include_once('/factory/core/lib/factory.lib.php');
dol_include_once('/factory/class/html.factoryformproduct.class.php');

if (! empty($conf->global->FACTORY_ADDON)
	&& is_readable(dol_buildpath("/factory/core/modules/factory/".$conf->global->FACTORY_ADDON.".php")))
	dol_include_once("/factory/core/modules/factory/".$conf->global->FACTORY_ADDON.".php");


$langs->load("bills");
$langs->load("products");
$langs->load("stocks");
$langs->load("factory@factory");

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
$result = restrictedArea($user, 'factory');

$mesg = '';

$product = new Product($db);
$factory = new Factory($db);
$form = new Form($db);

$productid=0;
if ($id || $ref) {
	// l'of et le produit associe
	$result = $factory->fetch($id, $ref);
	$result = $product->fetch($factory->fk_product);
	$id = $factory->id;
}


// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('factoryreport'));

$parameters = array('product' => $product);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $factory, $action);
// Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

/*
 * Actions
 */

if (empty($reshook)) {
	if ($action == 'closeof') {
	    $error = 0;

        $componentProductStatic  = new Product($db);
	    $componentEntrepotStatic = new Entrepot($db);

		$factory->qty_made=GETPOST("qtymade");
		$factory->date_end_made=dol_mktime(
						GETPOST('madeendhour', 'int'), GETPOST('madeendmin', 'int'), 0,
						GETPOST('madeendmonth', 'int'), GETPOST('madeendday', 'int'), GETPOST('madeendyear', 'int')
		);
		$factory->duration_made=GETPOST("duration_madehour")*3600+GETPOST("duration_mademin")*60;
		$factory->description = GETPOST("description");

		// si rien de fabrique le statut de l'of est mis a annule
		if (GETPOST("qtymade") == 0)
			$factory->statut = 3;
		else
			$factory->statut = 2;

		$db->begin();

		//on memorise les infos de l'OF
		$sql = "UPDATE ".MAIN_DB_PREFIX."factory ";
		$sql.= " SET date_end_made = ".($factory->date_end_made ? $db->idate($factory->date_end_made) :'null');
		$sql.= " , duration_made = ".($factory->duration_made ? $factory->duration_made :'null');
		$sql.= " , qty_made = ".($factory->qty_made ? $factory->qty_made :'null');
		$sql.= " , description = '".$db->escape($factory->description)."'" ;
		$sql.= " , fk_statut =2";
		$sql.= " WHERE rowid = ".$id;

		if (! $db->query($sql)) {
            $error++;
		    $factory->error    = $db->lasterror();
            $factory->errors[] = $factory->error;
        } else {
			// on boucle sur les lignes de l'OF
			$prods_arbo =$factory->getChildsOF($id);
			$product_chidren = $factory->getChildsArbo(0, $id);
			require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
			$mouvP = new MouvementStock($db);
			$mouvP->origin = new Factory($db);
			$mouvP->origin->id = $id;

			if (count($prods_arbo) > 0) {
				$totprixfabrication =0;

				foreach ($prods_arbo as $value) {
				    // get product values
                    $dispatchSuffix      = $value['dispatch_suffix'];
                    $componentFkProduct  = $value['id'];

                    // get post values
                    $componentFkEntrepot = GETPOST("id_entrepot_" . $dispatchSuffix, "int") ? GETPOST("id_entrepot_" . $dispatchSuffix, "int") : NULL;
                    $componentQtyUsed    = GETPOST("qtyused_" . $dispatchSuffix, "int") ? GETPOST("qtyused_" . $dispatchSuffix, "int") : 0;
                    $componentQtyDeleted = GETPOST("qtydeleted_" . $dispatchSuffix, "int") ? GETPOST("qtydeleted_" . $dispatchSuffix, "int") : 0;

					// on met a jour les infos des lignes de l'OF
					$sql  = "UPDATE " . MAIN_DB_PREFIX . "factorydet ";
					$sql .= " SET qty_used = " . $componentQtyUsed;
					$sql .= " , qty_deleted = " . $componentQtyDeleted;
                    $sql .= " , fk_entrepot = " . $componentFkEntrepot;
					$sql .= " WHERE fk_factory = " . $id;
					$sql .= " AND fk_product = " . $componentFkProduct;
                    $sql .= " AND id_dispatched_line = " . $value['id_dispatched_line'];

					if (!$db->query($sql)) {
                        $error++;
                        $factory->error    = $db->lasterror();
                        $factory->errors[] = $factory->error;
                    } else {
                        // s'il a ete detruit
                        if ($componentQtyDeleted > 0) {
                            // le prix est a 0 pour ne pas impacter le pmp
                            $idmv = $mouvP->livraison($user, $componentFkProduct, $componentFkEntrepot, $componentQtyDeleted, 0, $langs->trans("DeletedFactory", $factory->ref), $factory->date_end_made);
                            if ($idmv < 0) {
                                $error++;
                                $componentProductStatic->fetch($componentFkProduct);
                                $componentEntrepotStatic->fetch($componentFkEntrepot);
                                $factory->error = $componentProductStatic->ref . " : " . $mouvP->error . " (" . $componentEntrepotStatic->libelle . ")";
                                $factory->errors[] = $factory->error;
                            }
                        }

                        if (!$error) {
                            // le prix est a 0 pour ne pas impacter le pmp
                            $idmv = $mouvP->livraison($user, $componentFkProduct, $componentFkEntrepot, $componentQtyUsed, 0, $langs->trans("UsedforFactory", $factory->ref), $factory->date_end_made);
                            if ($idmv < 0) {
                                $error++;
                                $componentProductStatic->fetch($componentFkProduct);
                                $componentEntrepotStatic->fetch($componentFkEntrepot);
                                $factory->error = $componentProductStatic->ref . " : " . $mouvP->error . " (" . $componentEntrepotStatic->libelle . ")";
                                $factory->errors[] = $factory->error;
                            }
                        }

						// on totalise le prix d'achat des composants utilises pour determiner un prix de fabrication et mettre a jour le pmp du produit fabrique
						// attention on prend les quantites utilisees et detruites
						$totprixfabrication += $componentQtyUsed * $value['pmp'];
						$totprixfabrication += $componentQtyDeleted * $value['pmp'];
					}
				}
			}

            if (! $error) {
                // on ajoute un mouvement de stock d'entree de produit
                $idmv = $mouvP->reception($user, $factory->fk_product, $factory->fk_entrepot, $factory->qty_made, ($totprixfabrication / $factory->qty_made), $langs->trans("BuildedFactory", $factory->ref), $factory->date_end_made);
                if ($idmv < 0) {
                    $error++;
                    $componentProductStatic->fetch($factory->fk_product);
                    $componentEntrepotStatic->fetch($factory->fk_entrepot);
                    $factory->error = $componentProductStatic->ref . " : " . $mouvP->error . " (" . $componentEntrepotStatic->libelle . ")";
                    $factory->errors[] = $factory->error;
                }
            }

			if (! $error) {
                // Call trigger
                $result=$factory->call_trigger('FACTORY_CLOSE', $user);
            }
		}

		// commit or rollback
		if ($error) {
		    $db->rollback();
		    setEventMessages($factory->error, $factory->errors, 'errors');
		    // reload factory
            $factory->fetch($factory->id);
        } else {
		    $db->commit();
            setEventMessage($langs->trans("BuildedFactory", $factory->ref), 'mesgs');
            // on redirige pour eviter le doublement
            header("Location: ". $_SERVER["PHP_SELF"] . '?id='.$factory->id);
            exit();
        }

        $action = "";
	}

	if ($action == 'reopenof') {
		$factory->statut = 1;
		$sql = "UPDATE ".MAIN_DB_PREFIX."factory ";
		$sql.= " SET fk_statut =1";
		$sql.= " WHERE rowid = ".$id;
		if ($db->query($sql)) {
			// on supprimera les mouvements de stock quand le mouvement sera stocké V6?
		}
		$action="";
	}
}
/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);

llxHeader("", "", $langs->trans("CardFactory"));

dol_htmloutput_mesg($mesg);

$head=factory_prepare_head($factory, $user);
$titre=$langs->trans("Factory");
$picto="factory@factory";
dol_fiche_head($head, 'factoryreport', $titre, 0, $picto);


print '<form name="closeof" action="'.$_SERVER["PHP_SELF"].'?id='.$factory->id.'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="closeof">';
print '<table class="border" width="100%">';
print "<tr>";

//$bproduit = ($product->isproduct());

// Reference
print '<td width="15%">'.$langs->trans("Ref").'</td><td colspan=3>';
print $form->showrefnav($factory, 'ref', '', 1, 'ref');
print '</td></tr>';


// Lieu de stockage
print '<tr><td>'.$langs->trans("Warehouse").'</td><td colspan=3>';
if ($factory->fk_entrepot>0) {
	$entrepotStatic=new Entrepot($db);
	$entrepotStatic->fetch($factory->fk_entrepot);
	print $entrepotStatic->getNomUrl(1)." - ".$entrepotStatic->lieu." (".$entrepotStatic->zip.")" ;
}

print '</td></tr>';

// Date start planned
print '<tr><td width=20%>'.$langs->trans("FactoryDateStartPlanned").'</td><td width=30%>';
print dol_print_date($factory->date_start_planned, 'day');
print '</td><td width=20%>'.$langs->trans("DateStartMade").'</td><td width=30%>';
print dol_print_date($factory->date_start_made, 'day');
print '</td></tr>';

// Date end planned
print '<tr><td>'.$langs->trans("FactoryDateEndPlanned").'</td><td>';
print dol_print_date($factory->date_end_planned,'day');
print '</td><td>'.$langs->trans("DateEndMade").'</td><td>';
if ($factory->statut == 1)
	print $form->select_date(
					($factory->date_end_made ? $factory->date_end_made : $factory->date_end_planned),
					'madeend', 0, 0, '', "madeend"
	);
else
	print dol_print_date($factory->date_end_made, 'day');
print '</td></tr>';

// quantity
print '<tr><td>'.$langs->trans("QuantityPlanned").'</td><td>';
print $factory->qty_planned;
print '</td><td>'.$langs->trans("QuantityMade").'</td><td>';
if ($factory->statut == 1)
	print '<input type="text" id="qtymade" name="qtymade" size=6 value="'.($factory->qty_made ? $factory->qty_made : $factory->qty_planned).'">';
else
	print $factory->qty_made;
print '</td></tr>';

// duration
print '<tr><td>'.$langs->trans("FactoryDurationPlanned").'</td><td>';
print convertSecondToTime($factory->duration_planned, 'allhourmin');
print '</td><td>'.$langs->trans("DurationMade").'</td><td>';

if ($factory->statut == 1)
	print $form->select_duration(
					'duration_made',
					($factory->duration_made ? $factory->duration_made : $factory->duration_planned),
					0, 'text'
	);
else
	print convertSecondToTime($factory->duration_made, 'allhourmin');
print '</td></tr>';

print '<tr><td>'.$langs->trans('Status').'</td><td colspan=3>'.$factory->getLibStatut(4).'</td></tr>';
print '<tr><td valign=top>'.$langs->trans('Description').'</td><td colspan=3>';
if ($factory->statut == 1)
	print '<textarea name="description" wrap="soft" cols="120" rows="'.ROWS_4.'">'.$factory->description.'</textarea>';
else
	print str_replace(array("\r\n", "\n"), "<br>", $factory->description);
print '</td></tr>';
print '</table>';
print '<br>';


// tableau de description du produit
print '<table width=100% ><tr><td valign=top width=40%>';
print_fiche_titre($langs->trans("ProducttoBuild"), '', '');

print '<table class="border" width="100%">';

//$bproduit = ($object->isproduct());
print '<tr><td width=30% class="fieldrequired">'.$langs->trans("Product").'</td>';
print '<td>'.$product->getNomUrl(1)." : ".$product->label.'</td></tr>';

// TVA
print '<tr><td>'.$langs->trans("VATRate").'</td>';
print '<td>'.vatrate($product->tva_tx.($product->tva_npr?'*':''), true).'</td></tr>';

// Price
print '<tr><td>'.$langs->trans("SellingPrice").'</td><td>';
if ($product->price_base_type == 'TTC') {
	print price($product->price_ttc).' '.$langs->trans($product->price_base_type);
	$sale="";
} else {
	print price($product->price).' '.$langs->trans($product->price_base_type);
	$sale=$product->price;
}
print '</td></tr>';

// Price minimum
print '<tr><td>'.$langs->trans("MinPrice").'</td><td>';
if ($product->price_base_type == 'TTC')
	print price($product->price_min_ttc).' '.$langs->trans($product->price_base_type);
else
	print price($product->price_min).' '.$langs->trans($product->price_base_type);
print '</td></tr>';

// Status (to sell)
print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Sell").')</td><td colspan="2">';
print $product->getLibStatut(2, 0);
print '</td></tr>';

// Status (to buy)
print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Buy").')</td><td colspan="2">';
print $product->getLibStatut(2, 1);
print '</td></tr>';

print '<tr><td>'.$langs->trans("PhysicalStock").'</td>';
$product->load_stock();
print '<td>'.$product->stock_reel.'</td></tr>';

print '</table>';

print '</td>';

// tableau de description de la composition du produit
print '<td  valign=top>';

// indique si on a déjà une composition de présente ou pas
$compositionpresente=0;

$prods_arbo =$factory->getChildsOF($id);
print_fiche_titre($langs->trans("FactorisedProductsNumber").' : '.count($prods_arbo),'','');

// List of subproducts
if (count($prods_arbo) > 0) {
	$compositionpresente=1;
	//print '<b>'.$langs->trans("FactoryTableInfo").'</b><BR>';
	print '<table class="border" >';
	print '<tr class="liste_titre">';
	print '<td class="liste_titre" width=100px align="left">'.$langs->trans("Ref").'</td>';
	print '<td class="liste_titre" width=200px align="left">'.$langs->trans("Label").'</td>';
    print '<td class="liste_titre" width=200px align="left">'.$langs->trans("Warehouse").'</td>';
	print '<td class="liste_titre" width=50px align="center">'.$langs->trans("QtyUnitNeed").'</td>';
	print '<td class="liste_titre" width=50px align="center">'.$langs->trans("FactoryQtyPlanned").'</td>';
	print '<td class="liste_titre" width=50px align="center">'.$langs->trans("QtyConsummed").'</td>';
	print '<td class="liste_titre" width=50px align="center">'.$langs->trans("QtyLosed").'</td>';
	print '<td class="liste_titre" width=50px align="center">'.$langs->trans("QtyUsed").'</td>';
	print '<td class="liste_titre" width=50px align="center">'.$langs->trans("QtyRestocked").'</td>';

	print '</tr>';
	$mntTot=0;
	$pmpTot=0;

	$productEntrepotStatic = new Entrepot($db);
    $factoryformproduct = new FactoryFormProduct($db);

	foreach ($prods_arbo as $value) {
		// verify if product have child then display it after the product name
		$tmpChildArbo=$factory->getChildsArbo($value['id']);
		$nbChildArbo="";
		if (count($tmpChildArbo) > 0) $nbChildArbo=" (".count($tmpChildArbo).")";

        // get post values
        $dispatchSuffix = $value['dispatch_suffix'];
        $componentFkEntrepot = GETPOST("id_entrepot_" . $dispatchSuffix, "int")>0 ? GETPOST("id_entrepot_" . $dispatchSuffix, "int") : $value['child_fk_entrepot'];
        $componentQtyUsed    = GETPOST("qtyused_" . $dispatchSuffix, "int")!='' ? GETPOST("qtyused_" . $dispatchSuffix, "int") : $value['qtyplanned'];
        $componentQtyDeleted = GETPOST("qtydeleted_" . $dispatchSuffix, "int")!='' ? GETPOST("qtydeleted_" . $dispatchSuffix, "int") : 0;

		print '<tr>';
		print '<td align="left">'.$factory->getNomUrlFactory($value['id'], 1,'fiche').$nbChildArbo;
		print $factory->PopupProduct($value['id']);
		print '</td>';
		print '<td align="left" title="'.$value['description'].'">';
		print $value['label'].'</td>';

		// component warehouse
        print '<td>';
        if ($factory->statut == 1) {
            print $factoryformproduct->selectWarehouses($componentFkEntrepot, 'id_entrepot_' . $dispatchSuffix, 'warehouseopen,warehouseinternal', 0, 0, $value['id'], '', 0, 1, null, 'minwidth100', '', 1, TRUE);
        } else {
            if ($value['child_fk_entrepot']>0 && $productEntrepotStatic->fetch($value['child_fk_entrepot'])) {
                print $productEntrepotStatic->getNomUrl(1);
            }
        }
        print '</td>';

        // component qty unit
		print '<td align="center">'.$value['nb'];
		if ($value['globalqty'] == 1) {
            print "&nbsp;G";
            print '<input type="hidden" id="qtyunit_' . $dispatchSuffix . '" value="1" />';
        } else {
            print '<input type="hidden" id="qtyunit_' . $dispatchSuffix . '" value="' . $value['nb'] . '" />';
        }
		print '</td>';

		// component qty planned
		print '<td align="center">'.($value['qtyplanned']).'</td>';

		if ($factory->statut == 1) {
			// si c'est la première saisie on alimente avec les valeurs par défaut
			if ($value['qtyused']) {
				print '<td align="right">'.$value['qtyused'].'</td>';
				print '<td align="center">';
				print '<input type=text size=4 name="qtydeleted_'.$value['dispatch_suffix'].'" value="'.($value['qtydeleted']).'"></td>';
				print '<td align="right">'.($value['qtyused']+$value['qtydeleted']).'</td>';
				print '<td align="right">'.($value['qtyplanned']-($value['qtyused']+$value['qtydeleted'])).'</td>';
			} else {
				print '<td align="center">'.$value['qtyplanned'].'</td>';
				print '<td align="center"><input type="text" size="4" name="qtydeleted_' . $value['dispatch_suffix'] . '" value="' . $componentQtyDeleted . '" /></td>';
				print '<td><input type="text" size="4" id="qtyused_' . $value['dispatch_suffix'] . '" name="qtyused_' . $dispatchSuffix . '" value="' . $componentQtyUsed . '" /></td>';
				print '<td></td>';
			}
		} else {
			print '<td align="right">'.$value['qtyused'].'</td>';
			print '<td align="right">'.$value['qtydeleted'].'</td>';
			print '<td align="right">'.($value['qtyused']+$value['qtydeleted']).'</td>';
			print '<td align="right">'.($value['qtyplanned']-($value['qtyused']+$value['qtydeleted'])).'</td>';
		}
		print '</tr>';
	}
	print '</table>';

    if ($factory->statut == 1) {
        $productIdList = array();

        print '<script type="text/javascript" language="javascript">';
        print 'jQuery(document).ready(function(){';
        print ' jQuery("#qtymade").on("change", function(){';
        foreach ($prods_arbo as $value) {
            $dispatchSuffix = $value['dispatch_suffix'];

            if (!array_key_exists($value['id'], $productIdList)) {
                print 'jQuery("#qtyused_' . $dispatchSuffix . '").val(this.value*jQuery("#qtyunit_' . $dispatchSuffix . '").val());';
            } else {
                print 'jQuery("#qtyused_' . $dispatchSuffix . '").val(0);';
            }

            $productIdList[$value['id']] = $value['id'];
        }
        print ' });';
        print '});';
        print '</script>';
    }
}
print '</td>';
print '</tr></table>';

$parameters = array( 'colspan' => ' colspan="3"');
// Note that $action and $object may have been modified by
$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $factory, $action);

/* Barre d'action		*/
if ($action == '') {
	print '<div class="tabsAction">';
	if ($user->rights->factory->creer && $factory->statut == 1) {
        print '<input type=submit class="butAction" value="'.$langs->trans("CloseFactory").'">';
    }
	print '</div>';
}

print '</form>';


print '<br><hr><br>';
print_fiche_titre($langs->trans("FactoryMovement"), '', '');
// list des mouvements associés à l'of

$productstatic=new Product($db);
$movement=new MouvementStock($db);
$form=new Form($db);

$sql = "SELECT p.rowid, p.ref as product_ref, p.label as produit, p.fk_product_type as type,";
$sql.= " e.label as stock, e.rowid as entrepot_id, e.lieu,";
$sql.= " m.rowid as mid, m.value, m.datem, m.label, m.fk_origin, m.origintype";
//$sql.= ", m.inventorycode, m.batch, m.eatby, m.sellby";
$sql.= " FROM (".MAIN_DB_PREFIX."entrepot as e,";
$sql.= " ".MAIN_DB_PREFIX."product as p,";
$sql.= " ".MAIN_DB_PREFIX."stock_mouvement as m)";
$sql.= " WHERE m.fk_product = p.rowid";
$sql.= " AND m.fk_entrepot = e.rowid";
$sql.= " AND e.entity IN (".getEntity('stock', 1).")";
if (empty($conf->global->STOCK_SUPPORTS_SERVICES)) $sql.= " AND p.fk_product_type = 0";
$sql.= " AND m.fk_origin = ".$id;
$sql.= " AND m.origintype = 'factory'";

$sql.= $db->order($sortfield, $sortorder);

//print $sql;

$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);

	$param='';
	if ($id) $param.='&id='.$id;
	print '<table class="noborder" width="100%">';
	print "<tr class='liste_titre'>";
	print_liste_field_titre($langs->trans("Date"), $_SERVER["PHP_SELF"], "m.datem", "", $param, "", $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("ProductRef"), $_SERVER["PHP_SELF"], "p.ref", "", $param, "", $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("ProductLabel"), $_SERVER["PHP_SELF"], "p.ref", "", $param, "", $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("LabelMovement"), $_SERVER["PHP_SELF"], "m.label", "", $param, "", $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Units"), $_SERVER["PHP_SELF"], "m.value", "", $param, 'align="right"', $sortfield, $sortorder);
	print "</tr>\n";

	$arrayofuniqueproduct=array();

	$var=True;
	$i=0;
	while ($i < $num) {
		$objp = $db->fetch_object($resql);

		$var=!$var;
		print "<tr ".$bc[$var].">";
		print '<td>'.dol_print_date($db->jdate($objp->datem), 'dayhour').'</td>';
		// Product ref
		print '<td>';
		$productstatic->id=$objp->rowid;
		$productstatic->ref=$objp->product_ref;
		$productstatic->label=$objp->produit;
		$productstatic->type=$objp->type;
		print $productstatic->getNomUrl(1, '', 16);
		print "</td>\n";
		// Product label
		print '<td>';
		$productstatic->id=$objp->rowid;
		$productstatic->ref=$objp->produit;
		$productstatic->type=$objp->type;
		print $productstatic->getNomUrl(1, '', 16);
		print "</td>\n";
		// Label of movement
		print '<td>'.$objp->label.'</td>';
		// Value
		print '<td align="right">';
		if ($objp->value > 0) print '+';
		print $objp->value.'</td>';
		print "</tr>\n";
		$i++;
	}
	$db->free($resql);
	print "</table></form><br>";
}

llxFooter();
$db->close();