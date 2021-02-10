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

require_once DOL_DOCUMENT_ROOT.'/core/lib/order.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT."/projet/class/project.class.php";
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
if (! empty($conf->categorie->enabled))
	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

dol_include_once('restock/class/restock.class.php');

$langs->load("products");
$langs->load("stocks");
$langs->load("restock@restock");
$langs->load("suppliers");


// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user, 'commande', $id, '');
//$result=restrictedArea($user,'produit','','','','','','');

$action=GETPOST("action");
$id = GETPOST('id', 'int');
$ref= GETPOST('ref', 'alpha');
$object = new Commande($db);

$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST('page', 'int');
$userid=GETPOST('userid', 'int');

if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="cd.rowid";


if (! $object->fetch($id, $ref) > 0)
	dol_print_error($db);

$object->fetch_thirdparty();
$id = $object->id;

/*
 * View
 */

$htmlother=new FormOther($db);
$form=new Form($db);

$restock_static=new Restock($db);

if ( isset($_POST['reload']) ) $action = 'restock';

// header forwarding issue
if ($action!="createrestock") {
	$title=$langs->trans("RestockOrderProduct");
	llxHeader('', $title, 'EN:Customers_Orders|FR:Commandes_Clients|ES:Pedidos de clientes', '');
	$head = commande_prepare_head($object);
	dol_fiche_head($head, 'restocklink', $langs->trans("CustomerOrder"), 0, 'order');
	$linkback = '<a href="'.DOL_URL_ROOT.'/commande/list.php'.(! empty($socid)?'?socid='.$socid:'').'">';
	$linkback.= $langs->trans("BackToList").'</a>';

	if (version_compare(DOL_VERSION, "5.0.0") >= 0) {
		$morehtmlref='<div class="refidno">';
		// Ref customer
		$morehtmlref.=$form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', 0, 1);
		$morehtmlref.=$form->editfieldval(
						"RefCustomer", 'ref_client', $object->ref_client,
						$object, 0, 'string', '', null, null, '', 1
		);
		// Thirdparty
		$morehtmlref.='<br>'.$langs->trans('ThirdParty').' : '.$object->thirdparty->getNomUrl(1);
		// Project
		if (! empty($conf->projet->enabled)) {
			require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
			$langs->load("projects");
			$morehtmlref.='<br>'.$langs->trans('Project') . ' ';
			if (! empty($object->fk_project)) {
				$proj = new Project($db);
				$proj->fetch($object->fk_project);
				$morehtmlref.='<a href="'.DOL_URL_ROOT.'/projet/card.php?id='.$object->fk_project.'"';
				$morehtmlref.=' title="' . $langs->trans('ShowProject') . '">';
				$morehtmlref.=$proj->ref;
				$morehtmlref.='</a>';
			} else
				$morehtmlref.='';
		}
		$morehtmlref.='</div>';

		dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

		print '<div class="fichecenter">';
		print '<div class="underbanner clearboth"></div>';
	} else {
		print '<table class="border" width="100%">';

		// Ref
		print '<tr><td width="25%">'.$langs->trans("Ref").'</td><td colspan="3">';
		print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref');
		print "</td></tr>";

		// Ref commande client
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td nowrap>';
		print $langs->trans('RefCustomer').'</td><td align="left">';
		print '</td>';
		print '</tr></table>';
		print '</td><td colspan="3">';
		print $object->ref_client;
		print '</td>';
		print '</tr>';

		// Customer
		print "<tr><td>".$langs->trans("ThirdParty")."</td>";
		print '<td colspan="3">'.$object->thirdparty->getNomUrl(1).'</td></tr>';
		print '</table>';
	}
}
dol_fiche_end();

/*
 * Le tableau des lignes de commandes fournisseurs
*/

$sql = 'SELECT cf.fk_soc, cf.rowid, cf.date_commande, cfd.fk_product, cd.qty, cf.date_livraison';
$sql.= '  FROM '.MAIN_DB_PREFIX.'commande_fournisseur as cf';
$sql.= '  , '.MAIN_DB_PREFIX.'commande_fournisseurdet as cfd';
$sql.= '  , '.MAIN_DB_PREFIX.'commande as c';
$sql.= '  , '.MAIN_DB_PREFIX.'commandedet as cd';
$sql.= ' WHERE c.rowid = '.$object->id;
$sql.= ' AND cf.rowid = cfd.fk_commande';
$sql.= ' AND cd.fk_commandefourndet = cfd.rowid';
$sql.= ' AND c.rowid = cd.fk_commande';

$nbtotalofrecords = 0;
$sql.= " ORDER BY $sortfield $sortorder ";

$sql.= " ".$db->plimit($conf->liste_limit+1, $offset);

$commande_static = new CommandeFournisseur($db);
$product_static = new Product($db);

//print $sql;

dol_syslog("restock/tabs/restockcmdclientlink.php sql=".$sql);
$result = $db->query($sql);

if ($result) {
	$num = $db->num_rows($result);
	$i = 0;

	print '<table class="noborder noshadow" width="100%">';
	print "<tr class=\"liste_titre\">";
	print '<td class="liste_titre" align="Left" width=100px >'.$langs->trans("Product").'</td>';
	print '<td class="liste_titre" align="right" width=50px >'.$langs->trans("Qty").'</td>';

	print '<td class="liste_titre" align="left" width=150px >'.$langs->trans("Fournish").'</td>';
	print '<td class="liste_titre" align="left" width=100px >'.$langs->trans("FournishOrderRef").'</td>';
	print '<td class="liste_titre" align="center" width=80px >'.$langs->trans("FournishOrderDate").'</td>';
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

		print '<td align="right">';
		print $obj->qty;
		print '</td>';

		$commande_static->fetch($obj->rowid);
		$ret = $commande_static->fetch_thirdparty();

		// Name
		print '<td align="Left">';
		//var_dump($commande_static->thirdparty);
		//print $commande_static->thirdparty->getNomUrl(1);
		print '</td>';

		print '<td align="Left">';
		print $commande_static->getNomUrl(1);
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

	if ($num > $limit)
		print_barre_liste(
						'', $page, $_SERVER["PHP_SELF"],
						'&amp;begin='.$begin.'&amp;view='.$view.'&amp;userid='.$userid,
						$sortfield, $sortorder, '', $num, $nbtotalofrecords, ''
		);

	$db->free($result);
}

llxFooter();
$db->close();
