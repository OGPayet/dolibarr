<?php
/* Copyright (C) 2014-2017		Charlie BENKE	<charlie@patas-monkey.com>
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
 *	\file	   	htdocs/factory/project/productinproject.php
 *	\ingroup		factry
 *	\brief	  	Page of product used in a project task
 */

$res=@include("../../main.inc.php");					// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res)
	$res=@include("../../../main.inc.php");		// For "custom" directory

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';

require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

dol_include_once('/factory/class/factory.class.php');
dol_include_once('/factory/core/lib/factory.lib.php');

$langs->load('companies');
$langs->load('task');
$langs->load('factory@factory');
$langs->load('products');
if (! empty($conf->margin->enabled))
	$langs->load('margins');

$error=0;

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$socid = GETPOST('socid', 'int');
$action = GETPOST('action', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$lineid = GETPOST('lineid', 'int');

$mine = $_REQUEST['mode']=='mine' ? 1 : 0;
//if (! $user->rights->projet->all->lire) $mine=1;	// Special for projects
$withproject = GETPOST('withproject', 'int');
$project_ref = GETPOST('project_ref', 'alpha');

// Security check
$socid=0;
if ($user->societe_id > 0) $socid = $user->societe_id;
if (!$user->rights->projet->lire) accessforbidden();
//$result = restrictedArea($user, 'projet', $id, '', 'task'); // TODO ameliorer la verification


//print "withproject=".$withproject."<br>";

// Nombre de ligne pour choix de produit/service predefinis
$NBLINES=4;


$taskstatic = new Task($db);
$projectstatic = new Project($db);
$factory = new Factory($db);
$productstatic = new Product($db);
$entrepot = new Entrepot($db);

/*
 * Actions
 */

$parameters=array('socid'=>$socid);

if ($id || $ref)
	$result = $projectstatic->fetch($id, $ref);
$projectstatic->fetch_thirdparty();
/*
 * Actions
 */

if ($action=='sendtoproduct') {
	$error=0;

	if (GETPOST("entrepotid")==-1) {
		$error++;
		$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired", $langs->transnoentities("Warehouse")).'</div>';
		$action='edit';
	}
	if (! $error) {
		// on ajoute un mouvement de stock d'entrée de produit
		require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
		$mouvP = new MouvementStock($db);
		$mouvP->origin = $projectstatic;

		$idmv=$mouvP->livraison($user, GETPOST("idproduct"), GETPOST("entrepotid"),
									GETPOST("qtylefted"), 0,
									GETPOST("movedescr"), $date);

		// si on a une ancienne version se dolibarr, on sera obligé d'ajuster,
		// pas possible de modifier l'ancien mouvement prévue
		// ce sera à modifier pour les prochaines versions de factory
		if ($idmv == 1)
			$idmv = -1;

		$factory->createmvtproject($id, GETPOST("idproduct"), GETPOST("entrepotid"), GETPOST("qtylefted"), $idmv);
	}
}

/*
 * View
 */


llxHeader("", "", $langs->trans("Project"));
dol_htmloutput_mesg($mesg);


/*
 * View
 */


$form = new Form($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);
$companystatic = new Societe($db);

$now = dol_now();

/*
 * Show object in view mode
 */

// Tabs for project
$head=project_prepare_head($projectstatic);
dol_fiche_head($head, 'factory', $langs->trans("Project"), 0, ($projectstatic->public?'projectpub':'project'));

$param=($mode=='mine'?'&mode=mine':'');
$linkback = '<a href="'.DOL_URL_ROOT.'/projet/list.php">'.$langs->trans("BackToList").'</a>';

if (DOL_VERSION >= "5.0.0") {
	$morehtmlref='<div class="refidno">';
	$morehtmlref.=$projectstatic->title;

	if ($projectstatic->thirdparty->id > 0)
		$morehtmlref.='<br>'.$langs->trans('ThirdParty').' : '.$projectstatic->thirdparty->getNomUrl(1, 'project');
	$morehtmlref.='</div>';

	// Define a complementary filter for search of next/prev ref.
	if (! $user->rights->projet->all->lire) {
		$objectsListId = $object->getProjectsAuthorizedForUser($user, 0, 0);
		$projectstatic->next_prev_filter=" rowid in (".(count($objectsListId)?join(',', array_keys($objectsListId)):'0').")";
	}
	dol_banner_tab($projectstatic, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border" width="100%">';

	// Visibility
	print '<tr><td class="titlefield">'.$langs->trans("Visibility").'</td><td>';
	if ($projectstatic->public)
		print $langs->trans('SharedProject');
	else
		print $langs->trans('PrivateProject');
	print '</td></tr>';

	// Date start - end
	print '<tr><td>'.$langs->trans("DateStart").' - '.$langs->trans("DateEnd").'</td><td>';
	print dol_print_date($projectstatic->date_start, 'day');
	$end=dol_print_date($projectstatic->date_end, 'day');
	if ($end) print ' - '.$end;
	print '</td></tr>';

	// Budget
	print '<tr><td>'.$langs->trans("Budget").'</td><td>';
	if (strcmp($projectstatic->budget_amount, ''))
		print price($projectstatic->budget_amount, '', $langs, 1, 0, 0, $conf->currency);
	print '</td></tr>';

	// Other attributes
	$cols = 2;
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print '</table>';
	print '</div>';
	print '<div class="fichehalfright">';
	print '<div class="ficheaddleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border" width="100%">';

	// Description
	print '<td class="titlefield tdtop">'.$langs->trans("Description").'</td><td>';
	print nl2br($projectstatic->description);
	print '</td></tr>';

	// Categories
	if ($conf->categorie->enabled) {
		print '<tr><td valign="middle">'.$langs->trans("Categories").'</td><td>';
		print $form->showCategories($projectstatic->id, 'project', 1);
		print "</td></tr>";
	}

	print '</table>';
	print '</div>';
	print '</div>';
	print '</div>';
	print '<div class="clearboth"></div>';
} else {
	print '<table class="border" width="100%">';

	// Ref
	print '<tr><td width="30%">';
	print $langs->trans("Ref");
	print '</td><td>';

	// Define a complementary filter for search of next/prev ref.
	if (! $user->rights->projet->all->lire) {
		$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user, $mine, 0);
		$projectstatic->next_prev_filter=" rowid in (".(count($projectsListId)?join(',', array_keys($projectsListId)):'0').")";
	}
	print $form->showrefnav($projectstatic, 'ref', $linkback, 1, 'ref', 'ref');
	print '</td></tr>';

	// Project
	print '<tr><td>'.$langs->trans("Label").'</td><td>'.$projectstatic->title.'</td></tr>';

	// Company
	print '<tr><td>'.$langs->trans("Company").'</td><td>';
	if (! empty($projectstatic->societe->id))
		print $projectstatic->societe->getNomUrl(1);
	else
		print '&nbsp;';
	print '</td>';
	print '</tr>';

	// Visibility
	print '<tr><td>'.$langs->trans("Visibility").'</td><td>';
	if ($projectstatic->public)
		print $langs->trans('SharedProject');
	else
		print $langs->trans('PrivateProject');
	print '</td></tr>';

	// Statut
	print '<tr><td>'.$langs->trans("Status").'</td>';
	print '<td>'.$projectstatic->getLibStatut(4).'</td></tr>';

	print '</table>';
}
dol_fiche_end();

print '<br>';

$formconfirm='';
// indique si on a déjà une composition de présente ou pas
$compositionpresente = 0;

$prods_arbo = $factory->getChildsTasks($projectstatic->id, '');
// something wrong in recurs, change id of object

print_fiche_titre($langs->trans("ProductsUsedInProject"), '', '');

// List of subproducts
if (count($prods_arbo) > 0)
{
	$compositionpresente=1;

	print '<table class="border" >';
	print '<tr class="liste_titre">';
	print '<td class="liste_titre" width=100px align="left">'.$langs->trans("Ref").'</td>';
	print '<td class="liste_titre" width=200px align="left">'.$langs->trans("Label").'</td>';
	print '<td class="liste_titre" width=50px align="center">'.$langs->trans("QuantityPlannedShort").'</td>';
	print '<td class="liste_titre" width=50px align="center">'.$langs->trans("QtyFromStock").'</td>';
	print '<td class="liste_titre" width=50px align="center">'.$langs->trans("QtyUsedInTask").'</td>';
	print '<td class="liste_titre" width=50px align="center">'.$langs->trans("QtyLosed").'</td>';
	print '<td class="liste_titre" width=50px align="center">'.$langs->trans("QtyLefted").'</td>';
	// on affiche la colonne stock même si cette fonction n'est pas active
	print '<td class="liste_titre" width=50px align="center">'.$langs->trans("Stock").'</td>';
	if ($user->rights->factory->showprice) {
		if ($conf->stock->enabled) {
			// we display swap titles
			print '<td class="liste_titre" width=100px align="right">'.$langs->trans("UnitPmp").'</td>';
			print '<td class="liste_titre" width=100px align="right">'.$langs->trans("CostPmpHT").'</td>';
		} else {
			// we display price as latest purchasing unit price title
			print '<td class="liste_titre" width=100px align="right">'.$langs->trans("UnitHA").'</td>';
			print '<td class="liste_titre" width=100px align="right">'.$langs->trans("CostHA").'</td>';
		}
		print '<td class="liste_titre" width=100px align="right">'.$langs->trans("UnitPriceHT").'</td>';
		print '<td class="liste_titre" width=100px align="right">'.$langs->trans("SellingPriceHT").'</td>';
		print '<td class="liste_titre" width=100px align="right">'.$langs->trans("ProfitAmount").'</td>';
	}
	print '</tr>';
	$mntTot=0;
	$pmpTot=0;
	$szligne="";

	$qtyplannedtotal=0;
	$qtyusedtotal=0;
	$qtyfromstock=0;
	$idproduct=0;
	$var=True;

	foreach ($prods_arbo as $value) {
		if ($idproduct==0) {
			$idproduct=$value['id'];
			$labelproduct=$value['label'];
			$price=$value['price'];
			$pmp=$value['pmp'];
		}
		if ($idproduct != $value['id']) { // on affiche la ligne
			if (GETPOST("idproduct") == $idproduct)
				print "<tr bgcolor=orange>";
			else
				print "<tr $bc[$var]>";

			print "<td><a href=# onclick=\"$('.detailligne".$idproduct."').toggle();\" >".img_picto("", "edit_add")."</a>&nbsp;";
			$tmpChildArbo = $factory->getChildsArbo($idproduct);
			$nbChildArbo="";
			if (count($tmpChildArbo) > 0)
				$nbChildArbo=" (".count($tmpChildArbo).")";
			// on récupère le stock qui a été mouvementé sur le projet
			print $factory->getNomUrlFactory($idproduct, 1, 'index').$nbChildArbo.'</td>';
			print '<td>'.$labelproduct.'</td>';
			$qtyfromstock=$factory->getQtyFromStock($id, $idproduct);
			print '<td align=right><b>'.$qtyplannedtotal.'</b></td>';
			print '<td align=right><b>'.$qtyfromstock.'</b></td>';
			print '<td align=right><b>'.$qtyusedtotal.'</b></td>';
			print '<td align=right><b>'.$qtydeletedtotal.'</b></td>';
			if ($qtyplannedtotal - ($qtyusedtotal+$qtydeletedtotal) > 0 )
				$qtylefted = $qtyplannedtotal - $qtyfromstock;
			else
				$qtylefted = ($qtyusedtotal+$qtydeletedtotal) - $qtyfromstock;

			if ($user->rights->factory->creer ) {
				//Le stock doit être actif et le projet en activité
				if ($conf->stock->enabled && $projectstatic->statut == 1) {
					$url='<a href="'.dol_buildpath('/factory/project/', 1).'productinproject.php?action=edit&withproject=1';
					$url.='&id='.$id.'&idproduct='.$idproduct.'&qtylefted='.$qtylefted.'">';
					$qtylefted = $url.$qtylefted." ".img_object($langs->trans("GetFromStock"), "sending").'</a>';
				}
			}
			print '<td align=right><b>'.$qtylefted.'</b></td>';

			if ($conf->stock->enabled) {
				// we store vwap in variable pmp and display stock
				$productstatic->fetch($idproduct);
				if ($value['fk_product_type']==0) {
					// if product
					$productstatic->load_stock();
					print '<td align=center><b>'.$factory->getUrlStock($idproduct, 1, $productstatic->stock_reel).'</b></td>';
				}
				else	// no stock management for services
					print '<td></td>';
			}
			else	// no stock management for services
				print '<td></td>';

			if ($user->rights->factory->showprice ) {
				print '<td align="right"><b>'.price($pmp).'</b></td>'; // display else vwap or else latest purchasing price
				print '<td align="right"><b>'.price($pmp*$qtyproducttotal).'</b></td>'; // display total line
				print '<td align="right"><b>'.price($price).'</b></td>';
				print '<td align="right"><b>'.price($price*$qtyproducttotal).'</b></td>';
				print '<td align="right"><b>'.price(($price-$pmp)*$qtyproducttotal).'</b></td>';
			}
			print "</tr>";
			// on affiche le détail des taches
			print $szligne;
			$var=!$var;
			$szligne="";
			$idproduct=$value['id'];
			$labelproduct=$value['label'];
			$price=$value['price'];
			$pmp=$value['pmp'];
			$qtyplannedtotal=0;
			$qtyusedtotal=0;
			$qtydeletedtotal=0;
			$qtyproducttotal=0;
		}
		// verify if product have child then display it after the product name

		if ($bc[$var]=='class="pair"')
			$szligne.="<tr style='display:none' class='pair detailligne".$idproduct."'>";
		else
			$szligne.="<tr style='display:none' class='impair detailligne".$idproduct."'>";

		$taskstatic->fetch($value['idtask']);

		$szligne.='<td align="right"></td>';
		// on affiche les infos de la tache
		$szligne.='<td align="right">'.$taskstatic->getNomUrl(1, 'withproject');
		$szligne.=' ('.$taskstatic->progress." %)";
		$szligne.='</td>';
		$szligne.='<td align="center">'.$value['qtyplanned'].'</td>';
		$szligne.='<td align="right">'.'</td>';
		$szligne.='<td align="center">'.$value['qtyused'].'</td>';
		$szligne.='<td align="center">'.$value['qtydeleted'].'</td>';
		$szligne.='<td align="right">'.'</td>';
		$price=$value['price'];
		$pmp=$value['pmp'];

		$szligne.='<td></td>';

		if ($user->rights->factory->showprice ) {
			$szligne.='<td align="right">'.price($pmp).'</td>'; // display else vwap or else latest purchasing price
			$szligne.='<td align="right">'.price($pmp*$value['qtyplanned']).'</td>'; // display total line
			$szligne.='<td align="right">'.price($price).'</td>';
			$szligne.='<td align="right">'.price($price*$value['qtyplanned']).'</td>';
			$szligne.='<td align="right">'.price(($price-$pmp)*$value['qtyplanned']).'</td>';

			$mntTot+=$productstatic->price*$value['qtyplanned'];
			$pmpTot+=$pmp*$value['qtyplanned']; // sub total calculation
		}
		$qtyplannedtotal+=$value['qtyplanned'];
		$qtyusedtotal+=$value['qtyused'];
		$qtydeletedtotal+=$value['qtydeleted'];

		// on utilise toujours le plus grand
		if ($value['qtyused'] > $value['qtyplanned'])
			$qtyproducttotal+=$value['qtyused'];
		else
			$qtyproducttotal+=$value['qtyplanned'];

		$szligne.='</tr>';

		//var_dump($value);
		//print '<pre>'.$productstatic->ref.'</pre>';
		//print $productstatic->getNomUrl(1).'<br>';
		//print $value[0];	// This contains a tr line.

	}
	// on affiche le dernier produit
	if (GETPOST("idproduct") == $idproduct)
		print "<tr bgcolor=orange>";
	else
		print "<tr $bc[$var]>";
	print "<td><a href=# onclick=\"$('.detailligne".$idproduct."').toggle();\" >".img_picto("", "edit_add")."</a>&nbsp;";
	$tmpChildArbo = $factory->getChildsArbo($idproduct);
	$nbChildArbo="";
	if (count($tmpChildArbo) > 0)
		$nbChildArbo=" (".count($tmpChildArbo).")";

	print $factory->getNomUrlFactory($idproduct, 1,'index').$nbChildArbo.'</td>';
	print '<td>'.$labelproduct.'</td>';
	print '<td align=right><b>'.$qtyplannedtotal.'</b></td>';
	print '<td align=right><b>'.$factory->getQtyFromStock($id, $idproduct).'</b></td>';
	print '<td align=right><b>'.$qtyusedtotal.'</b></td>';
	print '<td align=right><b>'.$qtydeletedtotal.'</b></td>';
	if ($qtyplannedtotal - ($qtyusedtotal+$qtydeletedtotal) > 0 )
		$qtylefted = $qtyplannedtotal - $qtyfromstock;
	else
		$qtylefted = ($qtyusedtotal+$qtydeletedtotal) - $qtyfromstock;

	if ($user->rights->factory->creer ) {
		//Le stock doit être actif et le projet en activité
		if ($conf->stock->enabled && $projectstatic->statut == 1) {
			$url='<a href="'.dol_buildpath('/factory/project/', 1).'productinproject.php?action=edit&withproject=1';
			$url.='&id='.$id.'&idproduct='.$idproduct.'&qtylefted='.$qtylefted.'">';
			$qtylefted = $url.$qtylefted." ".img_object($langs->trans("GetFromStock"), "sending").'</a>';
		}
	}
	print '<td align=right><b>'.$qtylefted .'</b></td>';

	if ($conf->stock->enabled) {
		// we store vwap in variable pmp and display stock
		$productstatic->fetch($idproduct);
		if ($value['fk_product_type']==0) {
			// if product
			$productstatic->load_stock();
			print '<td align=center>'.$factory->getUrlStock($idproduct, 1, $productstatic->stock_reel).'</b></td>';
		}
		else	// no stock management for services
			print '<td></td>';
	}
	else	// no stock management for services
		print '<td></td>';

	if ($user->rights->factory->showprice ) {
		print '<td align="right"><b>'.price($pmp).'</b></td>'; // display else vwap or else latest purchasing price
		print '<td align="right"><b>'.price($pmp*$qtyproducttotal).'</b></td>'; // display total line
		print '<td align="right"><b>'.price($price).'</b></td>';
		print '<td align="right"><b>'.price($price*$qtyproducttotal).'</b></td>';
		print '<td align="right"><b>'.price(($price-$pmp)*$qtyproducttotal).'</b></td>';
	}

	print "</tr>";
	print $szligne;

	if ($user->rights->factory->showprice ) {
		print '<tr class="liste_total" >';
		print '<th colspan=8 align=right >'.$langs->trans("Total").'</th>';
		print '<td ></td>';
		print '<th align="right" >'.price($pmpTot).'</th>';
		print '<td ></td>';
		print '<th align="right" >'.price($mntTot).'</th>';
		print '<th align="right" >'.price($mntTot-$pmpTot).'</th>';
		print '</tr>';
	}
	print '</table>';
}
print '<br><br>';

if (GETPOST("idproduct") > 0 && $action != "sendtoproduct") {
	print_fiche_titre($langs->trans("AddMovement"),'','');
	print '<form action="'.dol_buildpath('/factory/project/', 1).'productinproject.php" method="post">';
	print '<input type="hidden" name="action" value="sendtoproduct">';
	print '<input type="hidden" name="id" value="'.$id.'">';
	print '<input type="hidden" name="withproject" value="'.$withproject.'">';
	print '<table class="border" >';
	print '<tr class="liste_titre">';
	print '<td class="liste_titre" width=100px align="left">'.$langs->trans("Product").'</td>';
	print '<td class="liste_titre" width=200px align="left">'.$langs->trans("Warehouse").'</td>';
	print '<td class="liste_titre" width=100px align="center">'.$langs->trans("QtyLefted").'</td>';
	print '<td class="liste_titre" colspan=2 width=200px align="center">'.$langs->trans("Label").'</td>';
	//print '<td class="liste_titre" align="center"></td>';
	print '</tr >';
	print '<tr >';
	$productstatic->fetch(GETPOST("idproduct"));

	print '<td align="left">'.$productstatic->getNomUrl(1).'</td>';
	print '<td align="left">';
	print '<input type=hidden name="idproduct" value="'. GETPOST("idproduct") .'">';
	print select_entrepot_list($search_entrepot, "entrepotid", 1, 1, GETPOST("idproduct"));
	print '</td>';

	print '<td align="right">';
	print '<input type=text size=3 name=qtylefted value="'.GETPOST("qtylefted").'">';
	print '</td>';
	print '<td align="right">';
	print '<input type=text size=50 name=movedescr value="'.$langs->trans("ProjectFactory", $projectstatic->ref).'">';
	print '</td>';

	print '<td><input type="submit" class="button" value="'.$langs->trans("Transfert").'">';
	print ' &nbsp; <input type="submit" name="cancel" class="button" value="'.$langs->trans("Cancel").'">';
	print '</td>';
	print '</tr >';
	print '</table>';

	print '</form>';
}

$sql = "SELECT * ";
$sql.= " FROM ".MAIN_DB_PREFIX."projet_stock as ps";
$sql.= " WHERE ps.fk_project = ".$id;
$sql.= " ORDER BY ps.date_creation ";
$res = $db->query($sql);

if ($res) {
	$nump = $db->num_rows($res);
	if ($nump) {
		print "<br>";

		// liste des mouvements de stock effectué sur le projet
		print_fiche_titre($langs->trans("ProductsMovedInProject"), '', '');

		print '<table class="border" >';
		print '<tr class="liste_titre">';
		print '<td class="liste_titre" width=100px align="left">'.$langs->trans("Product").'</td>';
		print '<td class="liste_titre" width=200px align="left">'.$langs->trans("Warehouse").'</td>';
		print '<td class="liste_titre" width=50px align="center">'.$langs->trans("Quantity").'</td>';
		print '<td class="liste_titre" width=50px align="center">'.$langs->trans("DateMvt").'</td>';
		if ($user->rights->factory->showprice ) {
			print '<td class="liste_titre" width=100px align="right">'.$langs->trans("UnitPmp").'</td>';
			print '<td class="liste_titre" width=100px align="right">'.$langs->trans("CostPmpHT").'</td>';
			print '<td class="liste_titre" width=100px align="right">'.$langs->trans("UnitPriceHT").'</td>';
			print '<td class="liste_titre" width=100px align="right">'.$langs->trans("SellingPriceHT").'</td>';
			print '<td class="liste_titre" width=100px align="right">'.$langs->trans("ProfitAmount").'</td>';
		}
		print '</tr>';

		$i = 0;
		while ($i < $nump) {
			$obj = $db->fetch_object($res);
			print '<tr >';
			$productstatic->fetch($obj->fk_product);
			print "<td>".$productstatic->getnomurl(1)."</td>";
			$entrepot->fetch($obj->fk_entrepot);
			print "<td>".$entrepot->getnomurl(1)."</td>";
			print "<td align=right>".$obj->qty_from_stock."</td>";
			print "<td align=right>".dol_print_date($obj->date_creation, 'day')."</td>";
			if ($user->rights->factory->showprice ) {
				print "<td align=right>".price($obj->pmp)."</td>";
				print "<td align=right>".price($obj->pmp * $obj->qty_from_stock)."</td>";
				$totpmp += ($obj->pmp * $obj->qty_from_stock);
				print "<td align=right>".price($obj->price)."</td>";
				print "<td align=right>".price($obj->price * $obj->qty_from_stock)."</td>";
				$totprice+=($obj->price * $obj->qty_from_stock);
				print "<td align=right>".price(($obj->price - $obj->pmp) * $obj->qty_from_stock)."</td>";
			}
			print '</tr >';
			$i++;
		}
		if ($user->rights->factory->showprice ) {
			print "<td colspan=5 align=right>".$langs->trans("Total")."</td>";
			print "<td align=right>".price($totpmp)."</td>";
			print "<td align=right></td>";
			print "<td align=right>".price($totprice)."</td>";
			print "<td align=right>".price($totprice-$totpmp)."</td>";
		}
		print '</table>';
	}
}
// End of page
llxFooter();
$db->close();