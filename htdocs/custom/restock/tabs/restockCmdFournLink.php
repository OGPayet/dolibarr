<?php
/* Copyright (C) 2013-2017		Charlie BENKE		<charlie@patas-monkey.com>
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
 *  \file	   htdocs/restock/restockCmdClient.php
 *  \ingroup	stock
 *  \brief	  Page to manage reodering
 */

// Dolibarr environment
$res=0;
if (! $res && file_exists("../../main.inc.php"))
	$res=@include("../../main.inc.php");		// For root directory
if (! $res && file_exists("../../../main.inc.php"))
	$res=@include("../../../main.inc.php");	// For "custom" directory

require_once DOL_DOCUMENT_ROOT . '/core/lib/fourn.lib.php';
dol_include_once('/restock/class/restock.class.php');
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT."/projet/class/project.class.php";
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

if (! empty($conf->categorie->enabled))
	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

$langs->load("products");
$langs->load("stocks");
$langs->load("restock@restock");
$langs->load("suppliers");
$langs->load('orders');
$langs->load('sendings');
$langs->load('supplier_proposal');
$langs->load('deliveries');


// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user, 'commande', $id, '');
//$result=restrictedArea($user,'produit','','','','','','');

$id = GETPOST('id', 'int');
$ref=GETPOST('ref', 'alpha');
$action=GETPOST('action', 'alpha');
$confirm=GETPOST('confirm', 'alpha');

$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST('page', 'int');
$userid=GETPOST('userid', 'int');

if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="cfd.rowid";


$object = new CommandeFournisseur($db);
$result = $object->fetch($id, $ref);
$object->fetch_thirdparty();
$id=$object->id;

$formconfirm='';


$htmlother=new FormOther($db);
$form=new Form($db);

$restockcmde_static=new RestockCmde($db);

/*
 * Confirmation de la suppression de la commande
 */

if ($action == 'delete')
	$formconfirm = $form->formconfirm(
					$_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteOrder'),
					$langs->trans('ConfirmDeleteOrder'), 'confirm_delete', '', 0, 2
	);


if ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->fournisseur->commande->supprimer) {
	$result=$restockcmde_static->deletelink($id, $user);
	if ($result > 0) {
		// on supprime les liens et la commande donc retour � la liste
		header("Location: ".DOL_URL_ROOT.'/fourn/commande/list.php');
		exit;
	} else {
		setEventMessages($object->error, $object->errors, 'errors');
	}
}


/*
 * View
 */


llxHeader('', $langs->trans('Order'), 'EN:Customers_Orders|FR:Commandes_Clients|ES:Pedidos de clientes');


// Print form confirm
print $formconfirm;

if ($id > 0 || ! empty($ref)) {

	$ret = $object->fetch_thirdparty();

	$head = ordersupplier_prepare_head($object);
	dol_fiche_head($head, 'restock', $langs->trans("SupplierOrder"), 0, 'order');
	$linkback = '<a href="'.DOL_URL_ROOT.'/fourn/commande/list.php'.(! empty($socid)?'?socid='.$socid:'').'">';
	$linkback.= $langs->trans("BackToList").'</a>';

	if (version_compare(DOL_VERSION, "5.0.0") >= 0) {
		$morehtmlref='<div class="refidno">';
		// Ref supplier
		$morehtmlref.=$form->editfieldkey(
						"RefSupplier", 'ref_supplier', $object->ref_supplier,
						$object, 0, 'string', '', 0, 1
		);
		$morehtmlref.=$form->editfieldval(
						"RefSupplier", 'ref_supplier', $object->ref_supplier,
						$object, 0, 'string', '', null, null, '', 1
		);
		// Thirdparty
		$morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $object->thirdparty->getNomUrl(1);
		// Project
		if (! empty($conf->projet->enabled)) {
			require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
			$langs->load("projects");
			$morehtmlref.='<br>'.$langs->trans('Project').' ';
			if (! empty($object->fk_project)) {
				$proj = new Project($db);
				$proj->fetch($object->fk_project);
				$morehtmlref.='<a href="'.DOL_URL_ROOT.'/projet/card.php?id='.$object->fk_project.'"';
				$morehtmlref.=' title="' . $langs->trans('ShowProject') . '">';
				$morehtmlref.=$proj->ref;
				$morehtmlref.='</a>';
			} else {
				$morehtmlref.='';
			}
		}
		$morehtmlref.='</div>';

		dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

		print '<div class="fichecenter">';
		print '<div class="underbanner clearboth"></div>';
	} else {
		// Ref
		print '<table class="border" width="100%">';

		// Ref
		print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
		print '<td colspan="2">';
		print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref');
		print '</td>';
		print '</tr>';

		// Ref supplier
		print '<tr><td width="20%">'.$langs->trans("RefSupplier").'</td>';
		print '<td colspan="2">';
		print $object->ref_supplier;
		print '</td></tr>';

		// Fournisseur
		print '<tr><td>'.$langs->trans("Supplier")."</td>";
		print '<td colspan="2">'.$object->thirdparty->getNomUrl(1, 'supplier').'</td>';
		print '</tr>';

		// Delivery date planned
		print '<tr><td>';
		print $langs->trans('DateDeliveryPlanned');
		print '</td><td colspan="2">';

		$usehourmin='day';
		if (! empty($conf->global->SUPPLIER_ORDER_USE_HOUR_FOR_DELIVERY_DATE))
			$usehourmin='dayhour';
		print $object->date_livraison ? dol_print_date($object->date_livraison, $usehourmin) : '&nbsp;';

		print '</td></tr>';

		// Statut
		print '<tr>';
		print '<td>'.$langs->trans("Status").'</td>';
		print '<td colspan="2">';
		print $object->getLibStatut(4);
		print "</td></tr>";
		print "</table>";
	}
}
dol_fiche_end();


/*
 * Le tableau des lignes de commandes fournisseurs
*/

$sql = 'SELECT c.fk_soc, c.rowid, c.date_commande, cd.fk_product, cfd.ref, cd.qty, c.date_livraison';
$sql.= '  FROM '.MAIN_DB_PREFIX.'commande_fournisseur as cf';
$sql.= '  , '.MAIN_DB_PREFIX.'commande_fournisseurdet as cfd';
$sql.= '  , '.MAIN_DB_PREFIX.'commande as c';
$sql.= '  , '.MAIN_DB_PREFIX.'commandedet as cd';
$sql.= ' WHERE cf.rowid = '.$object->id;
$sql.= ' AND cf.rowid = cfd.fk_commande';
$sql.= ' AND cd.fk_commandefourndet = cfd.rowid';
$sql.= ' AND c.rowid = cd.fk_commande';

$nbtotalofrecords = 0;
$sql.= " ORDER BY $sortfield $sortorder ";
$sql.= " ".$db->plimit($conf->liste_limit+1, $offset);

$commande_static = new Commande($db);
$product_static = new Product($db);

//print $sql;
dol_syslog("factory/list.php sql=".$sql);
$result = $db->query($sql);
//print $sql;
if ($result) {
	$num = $db->num_rows($result);
	$i = 0;

	print '<table class="noborder noshadow" width="100%">';
	print "<tr class=\"liste_titre\">";
	print '<td class="liste_titre" align="Left" width=100px >'.$langs->trans("Product").'</td>';
	print '<td class="liste_titre" align="Left" width=100px >'.$langs->trans("ProductRefFourn").'</td>';
	print '<td class="liste_titre" align="right" width=50px >'.$langs->trans("Qty").'</td>';

	print '<td class="liste_titre" align="left" width=100px >'.$langs->trans("CustomerOrderRef").'</td>';
	print '<td class="liste_titre" align="left" width=150px >'.$langs->trans("Customer").'</td>';
	print '<td class="liste_titre" align="left" width=150px >'.$langs->trans("CustomerRef").'</td>';
	print '<td class="liste_titre" align="center" width=80px >'.$langs->trans("CustomerOrderDate").'</td>';
	print '<td class="liste_titre" align="center" width=80px >'.$langs->trans("DateDeliveryPlanned").'</td>';

	print "</tr>\n";

	$var=True;
	while ($i < $num) {
		$obj = $db->fetch_object($result);

		$var=!$var;
		print "<tr ".$bc[$var].">";

		$product_static->fetch($obj->fk_product);
		print '<td align="Left">';
		print $product_static->getNomUrl(1);
		print '</td>';

		print '<td align="Left">';
		print $obj->ref;
		print '</td>';

		print '<td align="right">';
		print $obj->qty;
		print '</td>';

		$commande_static->fetch($obj->rowid);
		$ret = $commande_static->fetch_thirdparty();

		print '<td align="Left">';
		print $commande_static->getNomUrl(1);
		print '</td>';

		// customer
		print '<td align="Left">';
		print $commande_static->thirdparty->getNomUrl(1);
		print '</td>';

		print '<td class="nowrap">';
		print $commande_static->ref_client;
		print '</td>';

		print '<td align="center">';
		print dol_print_date($db->jdate($obj->date_commande), "day");
		print '</td>';

		print '<td align="center">';
		print dol_print_date($db->jdate($obj->date_livraison), "day");
		print '</td>';

		print "</tr>\n";
		$i++;
	}

	print "</table>";
	print '</form>';

	$db->free($result);
}
// on ajoute un bouton pour d�lier les lignes de commandes avant suppression
print '<div class="tabsAction">';
if ($user->rights->fournisseur->commande->supprimer) {
	print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=delete">';
	print $langs->trans("Delete").'</a>';
}
print "</div>";

llxFooter();
$db->close();
