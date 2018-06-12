<?php
/* Copyright (C) 2014-2016	Charlie BENKE		<charlie@patas-monkey.com>
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
 *  \file	   htdocs/factory/changeprice/changeprice.php
 *  \ingroup	changeprice
 *  \brief	  Permet de mettre à jour les prix de vente à partir de leur composition
 */


$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");					// For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");	// For "custom" directory

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';

$langs->load("admin");
$langs->load("products");
$langs->load("factory@factory");

// Security check
$socid=0;
if ($user->societe_id > 0) $socid=$user->societe_id;
$result = restrictedArea($user, 'factory', $id);

$action = GETPOST('action', 'alpha');

// pour activer/désactiver les mouchards de test
$btest = false;

// parfois c'est très/ trop long
set_time_limit(0);

$objectstatic = new Product($db);

/*
 * Actions
 */

$form=new Form($db);

$title = $langs->trans('GeneratePrice');

llxHeader('', $title);

print_fiche_titre($title, '', 'setup');

print $langs->trans("ChangePriceDesc").'<br><br>';

if ($action == 'generateref') {
	$error=0;
	$nbupdate=0;
	$num =0;

	// récupération des produits dont il faut déterminer le prix ET des paramètres utilisés pour le calcul
	// il doivent avoir une composition (lien vers product_factory)
	$sql = 'SELECT distinct p.rowid, p.ref, p.label, p.price, p.price_ttc, p.price_base_type, p.fk_product_type,';
	$sql.= '  p.tva_tx, fcc.price_level, fcc.init_price, fcc.computemode, fcc.computevalue, fcc.multiplyby';
	$sql.= ' FROM '.MAIN_DB_PREFIX.'product as p, '.MAIN_DB_PREFIX.'product_factory as pf';
	$sql.= ' , '.MAIN_DB_PREFIX.'categorie_product as cp, '.MAIN_DB_PREFIX.'factory_categ_changeprice as fcc';
	$sql.= ' WHERE p.entity IN ('.getEntity('product', 1).')';
	$sql.= " AND p.tosell = 1 AND p.tobuy = 0";
	$sql.= " AND p.rowid = cp.fk_product";
	$sql.= " AND p.rowid = pf.fk_product_father";
	$sql.= " AND cp.fk_categorie = fcc.fk_categories";

	$resql=$db->query($sql);
	if ($resql) {
		$db->begin();

		$num = $db->num_rows($resql);

		// on boucle sur les produits composés
		$i = 0;
		while ($i < $num) {
			$obj = $db->fetch_object($resql);
			$prodid=$obj->rowid;

			if (btest) print $obj->ref." - ".$obj->label." = ".$obj->price." - ".$obj->price_ttc;
			// récupération du prix des composants
			$sql = 'SELECT p.rowid, p.price, p.pmp, pf.qty, p.fk_product_type, p.tva_tx';
			$sql.= ' FROM '.MAIN_DB_PREFIX.'product as p, '.MAIN_DB_PREFIX.'product_factory as pf';
			$sql.= ' WHERE p.entity IN ('.getEntity('product', 1).')';
			$sql.= " AND p.rowid = pf.fk_product_children";
			$sql.= " AND pf.fk_product_father =".$obj->rowid;
			$resqlcomp=$db->query($sql);

			if ($resqlcomp) {
				$numcomp = $db->num_rows($resqlcomp);
				// on boucle sur les matières premières
				$j = 0;
				$total=0;
				$total_ttc=0;
				$unitservice=0;
				$unitproduct=0;
				$unitremise=1;

				while ($j < $numcomp) {
					$objcomp = $db->fetch_object($resqlcomp);
					// selon le type de prix de base (prix de vente ou pmp
					if ($obj->init_price  == 'sellprice')
						$total+=$objcomp->price * $objcomp->qty;
					else
						$total+=$objcomp->pmp * $objcomp->qty;

					// pour gérer la remise selon la quantité de produit ou de service
					if ($objcomp->fk_product_type == 1)
						$unitservice+= $objcomp->qty;
					else
						$unitproduct+= $objcomp->qty;
					$j++;
				}

				// récup du coeficient multiplicateur de la remise
				switch($obj->multiplyby) {
					case 'nbservice' :
						$unitremise = $unitservice;
						break;
					case 'nbproduct' :
						$unitremise = $unitproduct;
						break;
				}

				// selon le mode de calcul défini dans la catégorie du produit
				switch($obj->computemode) {
					case 'add' :
						$price = $total + ($obj->computevalue * $unitremise);
						break;
					case 'subtract' :
						$price = $total - ($obj->computevalue * $unitremise);
						break;
					case 'multiply' :
						$price = $total * ($obj->computevalue * $unitremise);
						break;
					case 'divide' :
						$price = $total / ($obj->computevalue * $unitremise);
						break;
				}

				$price_ttc = $price * (1+ $objcomp->tva_tx/100);

				if ($obj->price_level == 1) {
					// le premier prix va toujours sur la fiche produit
					$sql='UPDATE '.MAIN_DB_PREFIX.'product';
					$sql.= " SET price=".$price;
					$sql.= " , price_ttc=".$price_ttc;
					$sql.= " WHERE rowid =".$obj->rowid;
				}
				if (btest) print " ==> ".$price." - ".$price_ttc."<br>";

				$resupdt=$db->query($sql);

				// Add new prices
				$now=dol_now();
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_price(";
				$sql.= " price_level, date_price, fk_product, fk_user_author, price, price_ttc, price_base_type, tosell, tva_tx, ";
				$sql.= " recuperableonly, localtax1_tx, localtax2_tx, price_min, price_min_ttc, price_by_qty, entity) ";
				$sql.= " VALUES(".$obj->price_level .", '".$db->idate($now)."', ".$obj->rowid.", ".$user->id.", ";
				$sql.= $price.", ".$price_ttc.", '".$obj->price_base_type."', 1, ".$obj->tva_tx.", ";
				$sql.= " 0, 0, 0, 0, 0, 0, ".$conf->entity.")";
				$resupdt=$db->query($sql);

				if (btest) print $sql."<br><br>";
			}
			$i++;
		}
		$db->commit();
	}
	else
		print "ERROR = ".$db->error();
}

/*
 * View
 */

$var=true;

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'" />';
print '<input type="hidden" name="action" value="generateref" />';

print '<div class="tabsAction">';
print '<input type="submit" id="launch_generate" name="launch_generate" value="';
print $langs->trans("LaunchChangePrice").'" class="button" />';
print '</div>';

print '</form>';

// tableau de résultats de la génération
print '<table class="noborder " style="width:300px;" >';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("ResultGeneration").'</td>'."\n";
print '<td align="right" width="60">'.$langs->trans("Value").'</td>'."\n";
print '</tr>'."\n";

$var=!$var;
print '<tr '.$bc[$var].'>'."\n";
print '<td>'.$langs->trans("UpdatedPrice").'</td>'."\n";
print '<td width="60" align="right">'."\n";
print $num;
print '</td>'."\n";
print '</tr>'."\n";

print '</table>';
print '</div>';

llxFooter();
$db->close();