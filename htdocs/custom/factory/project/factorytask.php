<?php
/* Copyright (C) 2014-2017	Charlene BENKE	<charlie@patas-monkey.com>
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
 *	\file	   	htdocs/factory/factorytask.php
 *	\ingroup		taskproduct
 *	\brief	  	Page of product used in a task
 */


$res=@include("../../main.inc.php");					// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../../main.inc.php");		// For "custom" directory

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT."/categories/class/categorie.class.php";

dol_include_once('/factory/class/factory.class.php');

$langs->load('companies');
$langs->load('task');
$langs->load('factory@factory');
$langs->load('products');
if (! empty($conf->margin->enabled))
	$langs->load('margins');

$error=0;

$id=GETPOST('id', 'int');
$ref=GETPOST('ref', 'alpha');

$socid=GETPOST('socid', 'int');
$action=GETPOST('action', 'alpha');
$confirm=GETPOST('confirm', 'alpha');
$lineid=GETPOST('lineid', 'int');
$key=GETPOST('key');
$parent=GETPOST('parent');



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

$object = new Task($db);
$projectstatic = new Project($db);
$factory = new Factory($db);

if ($id > 0 || ! empty($ref)) {
	if ($object->fetch($id, $ref) > 0) {
		$projectstatic->fetch($object->fk_project);

		if (! empty($projectstatic->socid)) {
			if (DOL_VERSION < "3.7.0")
				$projectstatic->societe->fetch($projectstatic->socid);
			else
				$projectstatic->fetch_thirdparty();
		}
		$object->project = dol_clone($projectstatic);
	} else
		dol_print_error($db);

	if ($action != 'add') {
		$ret = $object->fetch($id, $ref);
		if ($ret == 0) {
			$langs->load("errors");
			setEventMessage($langs->trans('ErrorRecordNotFound'), 'errors');
			$error++;
		} elseif ($ret < 0) {
			setEventMessage($object->error, 'errors');
			$error++;
		}

	}
}

// Retreive First Task ID of Project if withprojet is on to allow project prev next to work
if (! empty($project_ref) && ! empty($withproject)) {
	if ($projectstatic->fetch('', $project_ref) > 0) {
		$tasksarray=$object->getTasksArray(0, 0, $projectstatic->id, $socid, 0);
		if (count($tasksarray) > 0) {
			$id = $tasksarray[0]->id;
			$object->fetch($id);
		} else {
			header("Location: ".DOL_URL_ROOT.'/projet/tasks.php?id='.$projectstatic->id.(empty($mode)?'':'&mode='.$mode));
		}
	}
}

/*
 * Actions
 */

$parameters=array('socid'=>$socid);


$productid=0;
if ($id || $ref) {
	$result = $projectstatic->fetch('', $project_ref);
	$productid = $projectstatic->id;
	$id = $object->id;
	$factory->id = $id;
}


/*
 * Actions
 */


// add sub-product to a product
if ( $action == 'add_prod' && $cancel <> $langs->trans("Cancel") && $user->rights->factory->creer ) {
	$error=0;
	for ($i=0; $i<$_POST["max_prod"]; $i++) {
		// print "<br> : ".$_POST["prod_id_chk".$i];
		if ($_POST["prod_id_chk".$i] != "") {
			if ($factory->add_componenttask(
							$id,
							$_POST["prod_id_".$i],
							$_POST["prod_qty_".$i],
							$_POST["pmp_".$i],
							$_POST["price_".$i]
				) > 0) {
				$action = '';
			} else {
				$error++;
				$action = 're-edit';
				if ($factory->error == "isFatherOfThis")
					$mesg = '<div class="error">'.$langs->trans("ErrorAssociationIsFatherOfThis").'</div>';
				else
					$mesg=$factory->error;
			}
		} else {
			if ($factory->del_componenttask($id, $_POST["prod_id_".$i]) > 0)
				$action = '';
			else {
				$error++;
				$action = 're-edit';
				$mesg=$product->error;
			}
		}
	}
	if (! $error) {
		header("Location: ".$_SERVER["PHP_SELF"].'?id='.$object->id.'&withproject='.$withproject);
		exit;
	}
}

if ($cancel == $langs->trans("Cancel")) {
	$action = '';
	Header("Location: factorytask.php?id=".$_POST["id"]);
	exit;
}
if ($action == 'getdefaultprice') {
	// on met à jour les prix à partir des valeurs par défaut
	$factory->getdefaultpricetask($id);
	$action="";
}
if ($action == 'updateprice') {
	// on modifie les prix
	$prods_arbo = $factory->getChildsTasks($object->fk_project, $id);
	//$id=  $object;
	// List of subproducts
	if (count($prods_arbo) > 0) {
		foreach ($prods_arbo as $value)
			$factory->updatefactorytaskprices(
							$id, $value['id'],
							GETPOST("prod_pmp_".$value['id']),
							GETPOST("prod_price_".$value['id'])
			);
	}
	$action="";
}
if ($action == 'updateqty') {
	// on modifie les quantités
	$prods_arbo = $factory->getChildsTasks($object->fk_project, $id);
	//$id=  $object;
	// List of subproducts
	if (count($prods_arbo) > 0)
		foreach ($prods_arbo as $value)
			$factory->updatefactorytaskqty(
							$id, $value['id'],
							GETPOST("qtyused_".$value['id']),
							GETPOST("qtydeleted_".$value['id'])
			);

	$action="";
}

if ($action == 'setmoveproduct') {
	// on tranfert les produits dans la composition d'un produit sélectionné
	// on vire les composants du produits
	$productid=GETPOST("productid");
	$sql="DELETE FROM ".MAIN_DB_PREFIX."product_factory where fk_product_father=".$productid;
	$db->query($sql);

	$prods_arbo = $factory->getChildsTasks($object->fk_project, $id);
	//$id=  $object;
	// List of subproducts
	if (count($prods_arbo) > 0)
		foreach ($prods_arbo as $value)
			$factory->add_componenttask($productid, $value['id'], $value['qtyplanned']);

	$mesg = '<div class="info">'.$langs->trans("ProductMovedToAproduct").'</div>';
	$action="";
}

if ($action == 'setmovetoanothertask') {
	// on tranfert les produits dans la composition d'un produit sélectionné
	// on vire les composants du produits
	$taskid=GETPOST("taskid");
	$sql="DELETE FROM ".MAIN_DB_PREFIX."projet_taskdet where fk_task=".$taskid;
	$db->query($sql);

	$prods_arbo = $factory->getChildsTasks($object->fk_project, $id);
	//$id=  $object;
	// List of subproducts
	if (count($prods_arbo) > 0)
		foreach ($prods_arbo as $value)
			$factory->add_componenttask($taskid, $value['id'], $value['qtyplanned']);

	$mesg = '<div class="info">'.$langs->trans("ProductMovedToAnotherTask").'</div>';
	$action="";
}

/*
 * View
 */

// search products by keyword and/or categorie
if ($action == 'search') {
	$sql = 'SELECT DISTINCT p.rowid, p.ref, p.label, p.price, p.fk_product_type as type, p.pmp';
	$sql.= ' FROM '.MAIN_DB_PREFIX.'product as p';
	$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie_product as cp ON p.rowid = cp.fk_product';
	$sql.= ' WHERE p.entity IN ('.getEntity("product", 1).')';
	if ($key != "") {
		$sql.= " AND (p.ref LIKE '%".$key."%'";
		$sql.= " OR p.label LIKE '%".$key."%')";
	}
	if ($conf->categorie->enabled && $parent != -1 and $parent)
		$sql.= " AND cp.fk_categorie ='".$db->escape($parent)."'";

	$sql.= " ORDER BY p.ref ASC";

	$resql = $db->query($sql);
}
//print $sql;

$productstatic = new Product($db);
$form = new Form($db);

llxHeader("", "", $langs->trans("CardProduct".$product->type));

dol_htmloutput_mesg($mesg);

/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);
$companystatic=new Societe($db);

$now=dol_now();

/*
 * Show object in view mode
 */

if (! empty($withproject)) {
	// Tabs for project
	$tab='tasks';
	$head=project_prepare_head($projectstatic);
	dol_fiche_head($head, $tab, $langs->trans("Project"), 0, ($projectstatic->public?'projectpub':'project'));

	$param=($mode=='mine'?'&mode=mine':'');
	$linkback = '<a href="'.DOL_URL_ROOT.'/projet/list.php">'.$langs->trans("BackToList").'</a>';

	if (DOL_VERSION >= "5.0.0") {
		$morehtmlref='<div class="refidno">';
		$morehtmlref.=$projectstatic->title;

		if ($projectstatic->thirdparty->id > 0)
			$morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $projectstatic->thirdparty->getNomUrl(1, 'project');
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
		$end = dol_print_date($projectstatic->date_end, 'day');
		if ($end)
			print ' - '.$end;
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
			if (count($projectsListId) >0)
				$projectstatic->next_prev_filter=" rowid in (".join(',', array_keys($projectsListId)).")";
			else
				$projectstatic->next_prev_filter=" rowid =0"; // pas de suivant/précédent
		}
		print $form->showrefnav($projectstatic, 'project_ref', '', 1, 'ref', 'ref', '', $param.'&withproject=1');
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
		print '<tr><td>'.$langs->trans("Status").'</td><td>'.$projectstatic->getLibStatut(4).'</td></tr>';
		print '</table>';
	}
	dol_fiche_end();
}


$soc = new Societe($db);
$soc->fetch($object->socid);

$head = task_prepare_head($object);

dol_fiche_head($head, 'factory', $langs->trans("Task"), 0, 'projecttask');

print '<table class="border" width="100%">';

$param=($withproject?'&withproject=1':'');
$linkback = '<a href="'.DOL_URL_ROOT.'/projet/tasks.php?id='.$projectstatic->id.$param.'">';
$linkback.= $langs->trans("BackToList").'</a>';

// Ref
print '<tr><td width="25%">';
print $langs->trans("Ref");
print '</td><td colspan="3">';
if (! GETPOST('withproject') || empty($projectstatic->id)) {
	$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user, $mine, 1);
	$object->next_prev_filter=" fk_projet in (".$projectsListId.")";
}
else
	$object->next_prev_filter=" fk_projet = ".$projectstatic->id;

print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref', '', $param);
print '</td></tr>';

// Label
print '<tr><td>'.$langs->trans("Label").'</td><td colspan="3">'.$object->label.'</td></tr>';

// Planned workload
print '<tr><td>'.$langs->trans("FactoryPlannedWorkload").'</td>';
print '<td colspan="3">'.convertSecondToTime($object->planned_workload, 'allhourmin').'</td></tr>';

// Progress
print '<tr><td>'.$langs->trans("Progress").'</td>';
print '<td colspan="3">'.$object->progress.' %'.'</td></tr>';

// Project
if (empty($withproject)) {
	print '<tr><td>'.$langs->trans("Project").'</td><td>';
	print $projectstatic->getNomUrl(1);
	print '</td></tr>';

	// Third party
	print '<td>'.$langs->trans("ThirdParty").'</td><td>';
	if ($projectstatic->societe->id)
		print $projectstatic->societe->getNomUrl(1);
	else
		print '&nbsp;';
	print '</td></tr>';
}

print '</table>';

dol_fiche_end();

$formconfirm='';

// indique si on a déjà une composition de présente ou pas
$compositionpresente=0;

$prods_arbo = $factory->getChildsTasks($object->fk_project, $object->id);
// something wrong in recurs, change id of object

print_fiche_titre($langs->trans("UsedProductsNumber").' : '.count($prods_arbo), '', '');

// List of subproducts
if (count($prods_arbo) > 0) {
	$compositionpresente=1;
	print '<br>';
	print '<table class="border" >';
	print '<tr class="liste_titre">';
	print '<td class="liste_titre" width=100px align="left">'.$langs->trans("Ref").'</td>';
	print '<td class="liste_titre" width=200px align="left">'.$langs->trans("Label").'</td>';
	print '<td class="liste_titre" width=50px align="center">'.$langs->trans("QuantityPlannedShort").'</td>';
	print '<td class="liste_titre" width=50px align="center">'.$langs->trans("QtyConsummed").'</td>';
	print '<td class="liste_titre" width=50px align="center">'.$langs->trans("QtyLosed").'</td>';
	// on affiche la colonne stock même si cette fonction n'est pas active
	print '<td class="liste_titre" width=50px align="center">'.$langs->trans("Stock").'</td>';
	if ($user->rights->factory->showprice ) {
		if ($conf->stock->enabled) {
			// we display vwap titles
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

	foreach ($prods_arbo as $value) {
		// verify if product have child then display it after the product name
		$tmpChildArbo=$factory->getChildsArbo($value['id']);
		$nbChildArbo="";
		if (count($tmpChildArbo) > 0)
			$nbChildArbo=" (".count($tmpChildArbo).")";

		print '<tr>';
		print '<td align="left">'.$factory->getNomUrlFactory($value['id'], 1, 'index').$nbChildArbo.'</td>';
		print '<td align="left">'.$value['label'].'</td>';
		print '<td align="center">'.$value['qtyplanned'].'</td>';
		print '<td align="center">'.$value['qtyused'].'</td>';
		print '<td align="center">'.$value['qtydeleted'].'</td>';
		$price = $value['price'];
		$pmp = $value['pmp'];

		if ($conf->stock->enabled) {
			// we store vwap in variable pmp and display stock
			$productstatic->fetch($value['id']);
			if ($value['fk_product_type']==0) {
				// if product
				$productstatic->load_stock();
				print '<td align=center>'.$factory->getUrlStock($value['id'], 1, $productstatic->stock_reel).'</td>';
			} else	// no stock management for services
				print '<td></td>';
		} else	// no stock management for services
			print '<td></td>';

		if ($user->rights->factory->showprice) {
			print '<td align="right">'.price($pmp).'</td>'; // display else vwap or else latest purchasing price
			print '<td align="right">'.price($pmp*$value['qtyplanned']).'</td>'; // display total line
			print '<td align="right">'.price($price).'</td>';
			print '<td align="right">'.price($price*$value['qtyplanned']).'</td>';
			print '<td align="right">'.price(($price-$pmp)*$value['qtyplanned']).'</td>';

			$mntTot=$mntTot+$productstatic->price*$value['qtyplanned'];
			$pmpTot=$pmpTot+$pmp*$value['qtyplanned']; // sub total calculation
		}
		print '</tr>';

		//var_dump($value);
		//print '<pre>'.$productstatic->ref.'</pre>';
		//print $productstatic->getNomUrl(1).'<br>';
		//print $value[0];	// This contains a tr line.
	}
	if ($user->rights->factory->showprice) {
		print '<tr class="liste_total" >';
		print '<th colspan=6 align=right >'.$langs->trans("Total").'</th>';
		print '<td ></td>';
		print '<th align="right" >'.price($pmpTot).'</th>';
		print '<td ></td>';
		print '<th align="right" >'.price($mntTot).'</th>';
		print '<th align="right" >'.price($mntTot-$pmpTot).'</th>';
	}
	print '</tr>';
	print '</table>';
}
print '<br>';

if ($action == 'adjustprice') {
	print '<br>';
	print_fiche_titre($langs->trans("AdjustPrice"), '', '');

	print '<form action="'.dol_buildpath('/factory/project/factorytask.php', 1).'" method="post">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="updateprice">';
	print '<input type="hidden" name="id" value="'.$id.'">';
	print '<input type="hidden" name="withproject" value="'.$withproject.'">';
	print '<table class="nobordernopadding" >';
	print '<tr class="liste_titre">';
	print '<th width="100px" class="liste_titre">'.$langs->trans("Ref").'</th>';
	print '<th width="200px" class="liste_titre">'.$langs->trans("Label").'</th>';
	print '<th width="150px" class="liste_titre" align="left">'.$langs->trans("BuyPrice").'</th>';
	print '<th width="150px" class="liste_titre" align="left">'.$langs->trans("SellPrice").'</th>';
	print '<th width="50px" class="liste_titre" align="right">'.$langs->trans("Quantity").'</th>';
	print '</tr>';

	foreach ($prods_arbo as $value) {
		$productstatic->id=$value['id'];
		$productstatic->fetch($value['id']);
		$productstatic->type=$value['type'];

		$var=!$var;
		print "\n<tr ".$bc[$var].">";

		print '<td>'.$factory->getNomUrlFactory($value['id'], 1, 'fiche', 24).'</td>';
		$labeltoshow=$productstatic->label;
		print '<td>'.$labeltoshow.'</td>';
		$nbproductintask = $factory->get_nb_ProductInTask($id, $productstatic->id);
		if ($factory->is_sousproduit($id, $productstatic->id))
			$qty=$factory->is_sousproduit_qty;
		else
			$qty="X"; // il y a un soucis, voir

		print '<td align="left">';
		print '<input type="text" size="5" name="prod_pmp_'.$value['id'].'" value="'.price2num($value['pmp']).'">';
		print '</td><td align="left">';
		print '<input type="text" size="5" name="prod_price_'.$value['id'].'" value="'.price2num($value['price']).'">';
		print '</td><td align="right">'.$nbproductintask.'</td>';
		print '</tr>';
	}

	print '</table>';
	print '<input type="hidden" name="max_prod" value="'.$i.'">';
	print '<br><center>';
	print ' <input type="submit" class="button" value="'.$langs->trans("Update").'">';
	print ' &nbsp; &nbsp;';
	print ' <input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</center>';
	print '</form>';
}


if ($action == 'adjustquantity') {
	print '<br>';
	print_fiche_titre($langs->trans("AdjustQty"), '', '');

	print '<form action="'.dol_buildpath('/factory/project/factorytask.php', 1).'" method="post">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="updateqty">';
	print '<input type="hidden" name="id" value="'.$id.'">';
	print '<input type="hidden" name="withproject" value="'.$withproject.'">';
	print '<table class="nobordernopadding" >';
	print '<tr class="liste_titre">';
	print '<th width="100px" class="liste_titre">'.$langs->trans("Ref").'</th>';
	print '<th width="200px" class="liste_titre">'.$langs->trans("Label").'</th>';
	print '<td class="liste_titre" width=50px align="center">'.$langs->trans("QtyNeed").'</td>';
	print '<td class="liste_titre" width=100px align="right">'.$langs->trans("QtyConsummed").'</td>';
	print '<td class="liste_titre" width=100px align="right">'.$langs->trans("QtyLosed").'</td>';
	print '</tr>';

	foreach ($prods_arbo as $value) {
		$productstatic->id=$value['id'];
		$productstatic->fetch($value['id']);
		$productstatic->type=$value['type'];

		$var=!$var;
		print "\n<tr ".$bc[$var].">";

		print '<td>'.$factory->getNomUrlFactory($value['id'], 1,'fiche', 24).'</td>';
		$labeltoshow=$productstatic->label;

		print '<td>'.$labeltoshow.'</td>';

		$nbproductintask = $factory->get_nb_ProductInTask($id, $productstatic->id);
		if ($factory->is_sousproduit($id, $productstatic->id))
			$qty=$factory->is_sousproduit_qty;
		else
			$qty="X"; // il y a un soucis, voir
		print '<td align="right">'.$nbproductintask.'</td>';
		// pour alimenter la valeur par défaut dans le meilleur des mondes...
		$qtyused=($value['qtyused']?$value['qtyused']:$value['qtyplanned']);
		print '<td align="right">';
		print '<input type="text" size="5" name="qtyused_'.$value['id'].'" value="'.$qtyused.'">';
		print '</td><td align="right">';
		print '<input type="text" size="5" name="qtydeleted_'.$value['id'].'" value="'.$value['qtydeleted'].'">';
		print '</td></tr>';
	}

	print '</table>';
	print '<input type="hidden" name="max_prod" value="'.$i.'">';

	print '<br><center>';
	print ' <input type="submit" class="button" value="'.$langs->trans("Update").'">';
	print ' &nbsp; &nbsp;';
	print ' <input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</center>';
	print '</form>';
}

$rowspan=1;
if ($conf->categorie->enabled) $rowspan++;
if ($action == 'edit' || $action == 'search' || $action == 're-edit' ) {
	print '<br>';
	print_fiche_titre($langs->trans("ProductToAddSearch"), '', '');
	print '<form action="'.dol_buildpath('/factory/project/factorytask.php', 1).'" method="post">';
	print '<table class="border" width="50%"><tr><td>';
	print '<table class="nobordernopadding" width="100%">';

	print '<tr><td>';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print $langs->trans("KeywordFilter").' &nbsp; ';
	print '</td>';
	print '<td><input type="text" name="key" value="'.$key.'">';
	print '<input type="hidden" name="action" value="search">';
	print '<input type="hidden" name="id" value="'.$id.'">';
	print '<input type="hidden" name="withproject" value="'.$withproject.'">';
	print '</td>';
	print '<td rowspan="'.$rowspan.'"  valign="bottom">';
	print '<input type="submit" class="button" value="'.$langs->trans("Search").'">';
	print '</td></tr>';
	if ($conf->categorie->enabled) {
		print '<tr><td>'.$langs->trans("CategoryFilter").' &nbsp; </td>';
		print '<td>'.$form->select_all_categories(0, $parent).'</td></tr>';
	}

	print '</table>';
	print '</td></tr></table>';
	print '</form>';

	if ($action == 'search') {
		print '<br>';
		print '<form action="'.dol_buildpath('/factory/project/factorytask.php', 1).'" method="post">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="add_prod">';
		print '<input type="hidden" name="id" value="'.$id.'">';
		print '<input type="hidden" name="withproject" value="'.$withproject.'">';

		print '<table class="nobordernopadding" width="100%">';
		print '<tr class="liste_titre">';
		print '<th class="liste_titre">'.$langs->trans("Ref").'</th>';
		print '<th class="liste_titre">'.$langs->trans("Label").'</th>';
		print '<th class="liste_titre" align="right">'.$langs->trans("BuyPrice").'</th>';
		print '<th class="liste_titre" align="right">'.$langs->trans("SellPrice").'</th>';
		print '<th class="liste_titre" align="center">'.$langs->trans("AddDel").'</th>';
		print '<th class="liste_titre" align="right">'.$langs->trans("Quantity").'</th>';
		print '</tr>';
		if ($resql) {
			$num = $db->num_rows($resql);
			$i=0;
			$var = true;

			if ($num == 0)
				print '<tr><td colspan="4">'.$langs->trans("NoMatchFound").'</td></tr>';

			while ($i < $num) {
				$objp = $db->fetch_object($resql);
				$var=!$var;
				print "\n<tr ".$bc[$var].">";
				$productstatic->fetch($objp->rowid);

				print '<td>'.$productstatic->getNomUrl($objp->rowid, 1,'index', 24).'</td>';
				$labeltoshow=$objp->label;

				print '<td>'.$labeltoshow.'</td>';
				$nbproductintask = $factory->get_nb_ProductInTask($id, $objp->rowid);
				if ($nbproductintask > 0) {
					$addchecked = ' checked="checked"';
					$qty = $nbproductintask;
				} else {
					$addchecked = '';
					$qty = "1";
				}

				$pmptaskproduct = $factory->get_value_ProductInTask($id, $objp->rowid, 'pmp', $objp->pmp);
				print '<td align="right">'.price($pmptaskproduct);
				print '<input type="hidden" name="pmp_'.$i.'" value="'.$pmptaskproduct.'"></td>';
				$pricetaskproduct = $factory->get_value_ProductInTask($id, $objp->rowid, 'price', $objp->price);
				print '<td align="right">'.price($pricetaskproduct);
				print '<input type="hidden" name="price_'.$i.'" value="'.$pricetaskproduct.'"></td>';

				print '<td align="center"><input type="hidden" name="prod_id_'.$i.'" value="'.$objp->rowid.'">';
				print '<input type="checkbox" '.$addchecked.'name="prod_id_chk'.$i.'" value="'.$objp->rowid.'"></td>';
				print '<td align="right"><input type="text" size="3" name="prod_qty_'.$i.'" value="'.$qty.'"></td>';
				print '</tr>';

				$i++;
			}
		}
		else
			dol_print_error($db);

		print '</table>';
		print '<input type="hidden" name="max_prod" value="'.$i.'">';

		if ($num > 0) {
			print '<br><center>';
			print '<input type="submit" class="button" value="'.$langs->trans("Add").'/'.$langs->trans("Update").'">';
			print '&nbsp; &nbsp; <input type="submit" name="cancel" class="button" value="'.$langs->trans("Cancel").'">';
			print '</center>';
		}
		print '</form>';
	}
}

// transfert composition dans un produit

if ($action == 'moveproduct') {
	print '<br>';
	print_fiche_titre($langs->trans("UseCompositionInProduct"), '', '');
	print '<form action="'.dol_buildpath('/factory/project/factorytask.php', 1).'" method="post">';
	print '<input type="hidden" name="action" value="setmoveproduct">';
	print '<input type="hidden" name="id" value="'.$id.'">';
	print '<input type="hidden" name="withproject" value="'.$withproject.'">';

	print '<table class="nobordernopadding" width="50%">';
	print '<tr><td class="fieldrequired">'.$langs->trans("Products").'</td><td>';
	print $form->select_produits('', 'productid', 0, $conf->product->limit_size, 0, 1, 2, '', 0); // only product on sell
	print '</td></tr>';
	print '</table>';

	print '<br><center><input type="submit" class="button" value="'.$langs->trans("Transfert").'">';
	print ' &nbsp; &nbsp; <input type="submit" name="cancel" class="button" value="'.$langs->trans("Cancel").'">';
	print '</center>';
}

if ($action == 'movetoanothertask') {
	print '<br>';
	print_fiche_titre($langs->trans("MoveToAnotherTask"), '', '');
	print '<form action="'.dol_buildpath('/factory/project/factorytask.php', 1).'" method="post">';
	print '<input type="hidden" name="action" value="setmovetoanothertask">';
	print '<input type="hidden" name="id" value="'.$id.'">';
	print '<input type="hidden" name="withproject" value="'.$withproject.'">';

	print '<table class="nobordernopadding" width="50%">';
	print '<tr><td class="fieldrequired">'.$langs->trans("Task").'</td><td>';
	select_tasks(-1, '', 'taskid', 16, 1, $show_empty=0);
	//print select_opentask('movetask'); // only open task
	print '</td></tr>';
	print '</table>';

	print '<br><center><input type="submit" class="button" value="'.$langs->trans("Transfert").'">';
	print ' &nbsp; &nbsp; <input type="submit" name="cancel" class="button" value="'.$langs->trans("Cancel").'">';
	print '</center>';
}

/* Barre d'action			*/
print '<div class="tabsAction">';
//$object->fetch($id, $ref);
if ($action == '' ) {
	if ($user->rights->factory->creer ) {
		//Le stock doit être actif et le produit ne doit pas être à l'achat
		if ($conf->stock->enabled ) {
			print '<a class="butAction" href="factorytask.php?action=edit&withproject=1&id='.$id.'">'.$langs->trans("EditComponent").'</a>';
			if ($compositionpresente) {
				// gestion des prix
				print '<a class="butAction" href="factorytask.php?action=getdefaultprice&withproject=1&id='.$id.'">';
				print $langs->trans("GetDefaultPrice").'</a>';
				print '<a class="butAction" href="factorytask.php?action=adjustprice&withproject=1&id='.$id.'">';
				print $langs->trans("AdjustPrice").'</a>';

				if ($projectstatic->statut == 1) {
					// saisie des quantités consommés uniquement si le projet est valide
					print '<a class="butAction" href="factorytask.php?action=adjustquantity&withproject=1&id='.$id.'">';
					print $langs->trans("AdjustQty").'</a>';
				}
				if ($projectstatic->statut == 2) {
					// actualisation de la formule du produit mis en description
					print '<a class="butAction" href="factorytask.php?action=moveproduct&withproject=1&id='.$id.'">';
					$langs->trans("UseCompositionInProduct").'</a>';
				}
				// si le module équipement est actif
				if ($conf->global->MAIN_MODULE_EQUIPEMENT && $projectstatic->statut == 1) {
					// on associe les équipement à la tache	(si il y en a
					print '<a class="butAction" href="factorytask.php?action=selectequipement&withproject=1&id='.$id.'">';
					print $langs->trans("SelectEquipement").'</a>';
				}

				if ($projectstatic->statut > 0) {
					// transfert des produits sur une autre tache
					print '<a class="butAction" href="factorytask.php?action=movetoanothertask&withproject=1&id='.$id.'">';
					print $langs->trans("MoveCompositionToAnotherTask").'</a>';
				}
			}
		}
		else
			print $langs->trans("NeedProductAndStockEnabled");
	}
}
print '</div>';

// End of page
llxFooter();
$db->close();

// TODO function added in html.formprojet.class.php
// use it when on the core
/**
 *	Show a combo list with projects qualified for a third party
 *
 *	@param	int		$socid	  	Id third party (-1=all, 0=only projects not linked to a third party, id=projects not linked or linked to third party id)
 *	@param  int		$selected   	Id task preselected
 *	@param  string	$htmlname   	Nom de la zone html
 *	@param	int		$maxlength		Maximum length of label
 *	@param	int		$open_only		OpenTask only
 *	@param	int		$show_empty		Add an empty line
 *	@return int		 			Nber of task if OK, <0 if KO
 */
function select_tasks($socid=-1, $selected='', $htmlname='taskid', $maxlength=16, $open_only=0, $show_empty=1)
{
	global $user, $conf, $langs, $db;

	require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';

	$out='';

	$tasksListId = false;
	if (empty($user->rights->projet->all->lire)) {
		$taskstatic=new Task($this->db);
		$tasksListId = $taskstatic->getTasksArray($user, 0, 1);
	}

	// Search all task
	$sql = 'SELECT t.rowid, t.ref, t.label, p.fk_soc, t.fk_statut, p.public';
	$sql.= ' FROM '.MAIN_DB_PREFIX .'projet as p';
	$sql.= ' , '.MAIN_DB_PREFIX .'projet_task as t';
	$sql.= " WHERE p.rowid = t.fk_projet";
	$sql.= " AND p.entity = ".$conf->entity;
	if ($tasksListId !== false)
		$sql.= " AND t.rowid IN (".$tasksListId.")";
	if ($socid == 0)
		$sql.= " AND (p.fk_soc=0 OR p.fk_soc IS NULL)";
	if ($open_only)
		$sql.= " AND t.progress < 100";

	$sql.= " ORDER BY t.label, t.ref ASC";

	//dol_syslog(get_class($this)."::select_tasks sql=".$sql,LOG_DEBUG);
	dol_syslog("factorystask::select_tasks sql=".$sql,LOG_DEBUG);
	$resql=$db->query($sql);
	if ($resql) {
		$out.= '<select class="flat" name="'.$htmlname.'">';
		if (!empty($show_empty))
			$out.= '<option value="0">&nbsp;</option>';

		$num = $db->num_rows($resql);
		$i = 0;
		if ($num) {
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				// If we ask to filter on a company and user has no permission to see all companies and project is linked to another company, we hide project.
				if ($socid > 0 && (empty($obj->fk_soc) || $obj->fk_soc == $socid) && ! $user->rights->societe->lire)
				{  } // Do nothing
				else {
					$labeltoshow=dol_trunc($obj->ref, 18);
					//if ($obj->public) $labeltoshow.=' ('.$langs->trans("SharedProject").')';
					//else $labeltoshow.=' ('.$langs->trans("Private").')';
					if (!empty($selected) && $selected == $obj->rowid && $obj->fk_statut > 0) {
						$out.= '<option value="'.$obj->rowid.'" selected="selected">';
						$out.= $labeltoshow.' - '.dol_trunc($obj->title, $maxlength).'</option>';
					} else {
						if (! $obj->fk_statut > 0)
							$labeltoshow.=' - '.$langs->trans("Draft");
						if ($socid > 0 && (! empty($obj->fk_soc) && $obj->fk_soc != $socid))
							$labeltoshow.=' - '.$langs->trans("LinkedToAnotherCompany");

						$resultat='<option value="'.$obj->rowid.'">'.$labeltoshow;
						$resultat.=' - '.dol_trunc($obj->label, $maxlength).'</option>';
						$out.= $resultat;
					}
				}
				$i++;
			}
		}
		if (empty($option_only))
			$out.= '</select>';

		print $out;

		$db->free($resql);
		return $num;
	} else {
		dol_print_error($db);
		return -1;
	}
}