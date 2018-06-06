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
 *  \file	   htdocs/factory/declinaison.php
 *  \ingroup	serem
 *  \brief	  Permet de mettre à jour les références
 */

// remove ../ when OK
// Dolibarr environment
$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");		// For root directory
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");	// For "custom" directory


require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
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
$btest = $conf->global->FACTORY_DECLINAISON_SHOWLOG;

// parfois c'est très/ trop long
set_time_limit(0);

$objectstatic = new Product($db);

/*
 * Actions
 */

$form=new Form($db);

$title = $langs->trans('GenerateRef');

llxHeader('', $title);

print_fiche_titre($title, '', 'setup');

print $langs->trans("GenerateRefDesc").'<br><br>';

if ($action == 'generateref') {
	$error=0;
	$nbcreate=0;
	$nbupdate=0;

	$db->begin();

	// détemination du nombre de produit à la vente
	$sql = 'SELECT count(rowid) as nb';
	$sql.= ' FROM '.MAIN_DB_PREFIX.'product';
	$sql.= ' WHERE entity IN ('.getEntity('product', 1).')';
	$sql.= " AND tosell = 1";

	$resql=$db->query($sql);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		$nbProduitOpenDeb = $obj->nb;
	}

	// on désactive tous les produits à la vente dans le catalog
	$sql='UPDATE '.MAIN_DB_PREFIX.'product as p, '.MAIN_DB_PREFIX.'categorie_product as cp ';
	$sql.= ' SET p.to_sell = 0';
	$sql.= " WHERE cp.fk_categorie =".$conf->global->FACTORY_CATEGORIE_CATALOG;
	$sql.= " AND p.rowid=cp_fkproduct";
	$resql=$db->query($sql);

	// on vire du catalogue
	$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'categorie_product as cp';
	$sql.= " WHERE cp.fk_categorie =".$conf->global->FACTORY_CATEGORIE_CATALOG;
	$resql=$db->query($sql);

	// on sélectionne tous les root
	$sql = 'SELECT rowid, ref, label, price, description, customcode, accountancy_code_buy, accountancy_code_sell';
	$sql.= ' FROM '.MAIN_DB_PREFIX.'product as p';
	$sql.= ' , '.MAIN_DB_PREFIX.'categorie_product as cp';
	$sql.= ' WHERE p.entity IN ('.getEntity('product',1).')';
	$sql.= " AND cp.fk_product = p.rowid";
	$sql.= " AND cp.fk_categorie =".$conf->global->FACTORY_CATEGORIE_ROOT;

	$resql=$db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);

		// on boucle sur les roots
		$i = 0;
		while ($i < $num) {
			$obj = $db->fetch_object($resql);

			$refroot=$obj->ref;
			$rootid=$obj->rowid;
			$labelroot=$obj->label;
			$priceroot=$obj->price;
			$descroot=$obj->description;

			if ($btest) print "ROOT =".$obj->ref."<br>";

			$ret=$objectstatic->fetch($obj->rowid);
			if ($ret > 0) {
				// définition du tableau des variant du root
				// col 0 : la position du variant dans la codification temporaire
				// col 1 : le libellé du variant (pas utile, juste à titre d'info)
				// col 2 : les id des produits séparé par ":"
				// col 3 : les qté des produits séparé par ":"
				$tblElemVariant=array();
				// tableau des références à créer
				$listNewProd=array();

				// on récupère	les variants du root
				$sql = 'SELECT pf.fk_product_children, p.ref, c.label, pf.qty ';
				$sql.= ' FROM '.MAIN_DB_PREFIX.'product_factory as pf';
				$sql.= ' , '.MAIN_DB_PREFIX.'categorie_product as cp';
				$sql.= ' , '.MAIN_DB_PREFIX.'product as p';
				$sql.= ' , '.MAIN_DB_PREFIX.'categorie as c';
				$sql.= ' WHERE pf.fk_product_father = '.$rootid;
				$sql.= " AND cp.fk_product = p.rowid";
				$sql.= " AND cp.fk_product = pf.fk_product_children";
				$sql.= " AND cp.fk_categorie = c.rowid";
				$sql.= " AND c.type = 0";
				$sql.= " AND c.fk_parent= ".$conf->global->FACTORY_CATEGORIE_VARIANT;
				$sql.= " ORDER BY c.label";
				// mouchard pour les test
				//if ($btest) print $sql."<br>";

				$resvariantsql=$db->query($sql);
				if ($resvariantsql) {
					$numvar = $db->num_rows($resvariantsql);
					if ($numvar > 0) {
						// on crée un tableau avec les variants
						$j = 0;
						while ($j < $numvar) {
							$objvar = $db->fetch_object($resvariantsql);
							if ($btest) print "VARIANT ref =".$objvar->ref."<br>";
							$numvariant =substr($objvar->label,0,2);
							$labelvariant =substr($objvar->label,4);
							$tblElemVariant[$numvariant][0]=0; // pour le traitement en boucle ensuite
							$tblElemVariant[$numvariant][1]=$labelvariant;
							$tblElemVariant[$numvariant][2]=$tblElemVariant[$numvariant][2].$objvar->fk_product_children.":";
							$tblElemVariant[$numvariant][3]=$tblElemVariant[$numvariant][3].$objvar->qty.":";
							$j++;
						}

						if ($btest) var_dump($tblElemVariant);

						$numvariant=count($tblElemVariant);
						$listNewProd = array();
						foreach ($tblElemVariant as $key => $lgncomponent) {

							// la première itération des produit correspond à la première liste
							$infocomponent = explode(":", substr($lgncomponent[2], 0, -1));
							$qtecomponent = explode(":", substr($lgncomponent[3], 0, -1));

							//if ($btest) var_dump($infocomponent );
							if (count($listNewProd) == 0) {
								$listNewProd = $infocomponent;
								$lisQteProd=$qtecomponent;
							} else {
								foreach ($infocomponent as $keycomp => $lgnelement) {
									foreach ($listNewProd as $keyprod => $valueprod)  {
										$listprod[] = $lgnelement.":".$lisQteProd[$keycomp] ."_".$valueprod.":".$qtecomponent[$keyprod];

										//print $lgnelement ."_".$valueprod."//".$keyprod."==".$qtecomponent[$keyprod].'<br>';
									}
								}
								$listNewProd = $listprod;
							}
						}

						// liste des ref à construire pour le produit

						if ($btest) var_dump($listNewProd);

						$objectelement = new Product($db);
						// contruction des produits à gérer
						foreach ($listNewProd as $productelement) {
							$tblproduct=explode("_", $productelement);
							$newref=$refroot;
							$newlabel=$labelroot;
							$priceadd=$priceroot;
							$descref = $descroot.'<br>';
							$pmpadd=0;
							foreach ($tblproduct as $idproduct) {
								// col 0 : l'id du produit
								// col 1 : les quantités du produit
								$infoproduct=explode(":", $idproduct);
								$ret=$objectelement->fetch($infoproduct[0]);
								$newref.='-'.$objectelement->ref;
								$newlabel.=' '.$objectelement->label;
								$descref.= $db->escape($objectelement->description)."<br>";
								$priceadd+=$objectelement->price * $infoproduct[1];
								$pmpadd+=$objectelement->pmp * $infoproduct[1];
								if ($btest)
									print "composant ".$objectelement->ref." = ".$objectelement->price." - ".$objectelement->pmp." * ".$infoproduct[1] ."<br>";

							}

							// quelques mouchard pour les tests
							if ($btest) print $newref."/".$newlabel." = ".$priceadd." - ".$pmpadd."<br>";

							// on regarde si la ref existe déjà
							$retnewref=$objectelement->fetch('', $newref);

							$objectelement->ref= $newref;
							$objectelement->description = $descref;
							$objectelement->price_base_type == 'HT';
							$objectelement->price=$priceadd;
							$objectelement->status=1; // à la vente
							$objectelement->type=0;

							// si la ref existe déjà on ne fait qu'un update
							if ($retnewref) {

								$objectelement->update($objectelement->id, $user);
								// mise à jour du prix de vente
								$objectelement->updateprice($priceadd, "HT", $user);

								$nbupdate+=1;

								// mise à jour du pmp à part
								$sql = "UPDATE ".MAIN_DB_PREFIX."product SET";
								$sql.= " pmp=".$pmpadd;
								$sql.= " WHERE rowid = ".$objectelement->id;

								dol_syslog("generateref.php::"."update pmp sql=".$sql, LOG_DEBUG);
								$resupdate=$db->query($sql);
							} else {
								// sinon on fait une création
								if ($btest) print $newref." a creer<br>";
								// en création on utilise le nouveau label
								$objectelement->label = $newlabel;

								$objectelement->rowid=$objectelement->create($user);
								$nbcreate+=1;
								//print "Erreur fetch newref=".$newref."<br>";
							}

							// on enlève si elle existe, puis on ajoute la composition des déclinaisons au produit

							// on ajoute ce qui est actif dans le catalogue
							$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'categorie_product (fk_product, fk_categorie)';
							$sql.= " values (".$objectelement->id.", ".$conf->global->FACTORY_CATEGORIE_CATALOG.")";
							//if ($btest) print 'insert catalog='.$sql."<br>";
							$resinsert=$db->query($sql);

							// Récupération les traductions du root et on les affecte au catalog

							// on supprime les traductions catalogues si elles existent
							$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'product_lang';
							$sql.= " WHERE fk_product = ".$objectelement->id;
							$resdeleteTrad=$db->query($sql);

							$sql = 'SELECT *';
							$sql.= ' FROM '.MAIN_DB_PREFIX.'product_lang as pl';
							$sql.= " WHERE pl.fk_product = ".$rootid;
							$restraduction=$db->query($sql);
							//if ($btest) print "trad=".$sql."<br>" ;
							if ($restraduction) {
								$numtrad = $db->num_rows($restraduction);
								if ($btest)
									print "nbtrads=".$numtrad."<br>" ;
								if ($numtrad > 0) {
									// on crée un tableau avec les variants
									$j = 0;
									while ($j < $numtrad) {
										$objtrad = $db->fetch_object($restraduction);
										// on supprime les traductions catalogues si elles existent
										$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'product_lang';
										$sql.= " (fk_product, lang, label, description, note) values (";
										$sql.= " ".$objectelement->id.", '".$db->escape($objtrad->lang)."', '".$db->escape($objtrad->label)."'";
										$sql.= ", '".$db->escape($objtrad->description)."', '".$db->escape($objtrad->note)."')";
										$resinsert = $db->query($sql);
										if ($btest) print "SQLTRAD =  ".$sql."<br>";
										// on ajoute la traduction
										$j++;
									}
								}
							}
						}
					}
				}
			}
			$i++;
		}
	}
	$db->commit();
}

/*
 * View
 */

$var=true;

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'" />';
print '<input type="hidden" name="action" value="generateref" />';

print '<div class="tabsAction">';
print '<input type="submit" id="launch_generate" name="launch_generate" value="'.$langs->trans("LaunchGenerate").'" class="button" />';
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
print '<td>'.$langs->trans("NewDeclination").'</td>'."\n";
print '<td width="60" align="right">'."\n";
print $nbcreate;
print '</td>'."\n";
print '</tr>'."\n";

$var=!$var;
print '<tr '.$bc[$var].'>'."\n";
print '<td>'.$langs->trans("UpdatedDeclination").'</td>'."\n";
print '<td width="60" align="right">'."\n";
print $nbupdate;
print '</td>'."\n";
print '</tr>'."\n";

$var=!$var;
print '<tr '.$bc[$var].'>'."\n";
print '<td>'.$langs->trans("ActiveDeclination").'</td>'."\n";
print '<td width="60" align="right">'."\n";
print $nbcreate+$nbupdate;
print '</td>'."\n";
print '</tr>'."\n";

$var=!$var;
print '<tr '.$bc[$var].'>'."\n";
print '<td>'.$langs->trans("NotActiveDeclination").'</td>'."\n";
print '<td width="60" align="right">'."\n";
print $nbProduitOpenDeb-($nbcreate+$nbupdate);
print '</td>'."\n";
print '</tr>'."\n";

print '</table>';
print '</div>';


llxFooter();

$db->close();

function getelementVariant($keytbl)
{
	global $tblElemVariant;
	global $btest;
	global $db, $user;
	// col 0 : la position du variant dans la codification temporaire
	// col 1 : le libellé du variant (pas utile, juste à titre d'info)
	// col 2 : les id des produits séparé par ":"
	// col 3 : les qty des produits séparé par ":"

	// on compte les variables du tableau
	$tblelement=explode(":", $tblElemVariant[$keytbl][2]);
	$tblqtyelement=explode(":", $tblElemVariant[$keytbl][3]);
	$nbelement=count($tblelement)-1;

	// cas simple 1 seul élément on retourna le variable tel quel
	if ($nbelement == 1) {
		$idtoreturn = substr($tblElemVariant[$keytbl][2], 0, -1);
		$idtoreturn .= ":".substr($tblElemVariant[$keytbl][3], 0, -1);
	} else {
		// on récupère l'index à retourner
		$idtoreturn= $tblelement[$tblElemVariant[$keytbl][0]];
		$idtoreturn.= ":".$tblqtyelement[$tblElemVariant[$keytbl][0]];
	}

	// on retourne seulement l'id car on a des choses à reprendre sur le produit
	return $idtoreturn;
}

// fonction récursive d'incrémenation pour parcourir le tableaux
function incrementevariant($lastkey)
{
	global $tblElemVariant;

	if ($lastkey !=0) {
		$tblelement=explode(":", $tblElemVariant[$lastkey][2]);
		$nbelement=count($tblelement)-1;
		$tblElemVariant[$lastkey][0]+=1;
		// si on est au bout du tableau on incrémente le précédent
		if ($tblElemVariant[$lastkey][0] == $nbelement) {
			$tblElemVariant[$lastkey][0] =0;
			$previouskey = 0;
			foreach ($tblElemVariant as $currentkey => $lgncomponent) {
				if ($lastkey == $currentkey)
					incrementevariant($previouskey);
				$previouskey = $currentkey;
			}
		}
	}
}