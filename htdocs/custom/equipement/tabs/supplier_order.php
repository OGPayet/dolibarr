<?php
/* Copyright (C) 2014-2017	Charlie Benke	 <charles.fr@benke.fr>
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
 *	\file	   htdocs/equipement/tabs/fichinterAdd.php
 *	\brief	  List of Equipement for join Events with a fichinter
 *	\ingroup	equipement
 */
$res=@include("../../main.inc.php");					// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../../main.inc.php");		// For "custom" directory

require_once DOL_DOCUMENT_ROOT."/product/class/product.class.php";
require_once DOL_DOCUMENT_ROOT."/product/stock/class/entrepot.class.php";
require_once DOL_DOCUMENT_ROOT."/fichinter/class/fichinter.class.php";
require_once DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php";
require_once DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.facture.class.php";
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
require_once DOL_DOCUMENT_ROOT."/core/lib/date.lib.php";

require_once DOL_DOCUMENT_ROOT.'/core/lib/fourn.lib.php';
require_once DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php";

dol_include_once('/equipement/class/equipement.class.php');
dol_include_once('/equipement/core/lib/equipement.lib.php');

if (! empty($conf->global->EQUIPEMENT_ADDON)
	&& is_readable(dol_buildpath("/equipement/core/modules/equipement/".$conf->global->EQUIPEMENT_ADDON.".php")))
	dol_include_once("/equipement/core/modules/equipement/".$conf->global->EQUIPEMENT_ADDON.".php");


$langs->load("equipement@equipement");
$langs->load("orders");
$langs->load("suppliers");
$langs->load("companies");
$langs->load('stocks');

$id=GETPOST('id', 'int');
$action = GETPOST('action', 'alpha');
// Security check
if ($user->societe_id) $socid=$user->societe_id;

/* ----- OpenDSI - Access rights on equipements in supplier order - Begin ----- */
//$result = restrictedArea($user, 'fournisseur', $id, '', 'commande');
$result = restrictedArea($user, 'fournisseur', $id, 'commande_fournisseur', 'commande');
/* ----- OpenDSI - Access rights on equipements in supplier order - End ----- */


$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST('page', 'int');
if ($page == -1) {
	$page = 0;
}
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="e.datec";

$limit = $conf->liste_limit;

$search_ref=GETPOST('search_ref', 'alpha');
$search_refProduct=GETPOST('search_refProduct', 'alpha');
$search_company_fourn=GETPOST('search_company_fourn', 'alpha');
$search_company_client=GETPOST('search_company_client', 'alpha');
$search_entrepot=GETPOST('search_entrepot', 'alpha');

$search_equipevttype=GETPOST('search_equipevttype', 'alpha');
if ($search_equipevttype=="-1") $search_equipevttype="";


/*
 *	View
 */

$form = new Form($db);
llxHeader();


$object = new CommandeFournisseur($db);
$result = $object->fetch($id);
$object->fetch_thirdparty();


if ($action  == 'addequipement') {
	$objectequipement = new equipement($db);
	$num = count($object->lines);
	$nbligneorder = 0;
	while ($nbligneorder <	$num) {
		$line =	$object->lines[$nbligneorder];
		// only recept on serial product
		if ($line->fk_product > 0) {
			// on regarde si il y a des �quipement � cr�er (qty > O)
			if (GETPOST('quantity-'.$line->id)) {
				$objectequipement->fk_product		= $line->fk_product;
				$objectequipement->fk_soc_fourn 	= $object->thirdparty->id;
				$objectequipement->fk_soc_client 	= $idMeteoOmnium;
				$objectequipement->author			= $user->id;
				$objectequipement->description		= $langs->trans("SupplierOrder").":".$object->ref;
//				$objectequipement->ref				= $ref;
				$objectequipement->fk_entrepot		= GETPOST('fk_entrepot-'.$line->id, 'alpha');
                $objectequipement->fk_commande_fourn = $object->id;
				$datee = dol_mktime(
								'23', '59', '59',
								$_POST["datee-".$line->id."month"],
								$_POST["datee-".$line->id."day"],
								$_POST["datee-".$line->id."year"]
				);
				$objectequipement->datee			= $datee;
				$dateo = dol_mktime(
								'23', '59', '59',
								$_POST["dateo-".$line->id."month"],
								$_POST["dateo-".$line->id."day"],
								$_POST["dateo-".$line->id."year"]
				);
				$objectequipement->dateo			= $dateo;
				// selon le mode de s�rialisation de l'�quipement
				switch(GETPOST('SerialMethod-'.$line->id, 'int')) {
					case 1 : // en mode g�n�ration auto, on cr�e des num�ros s�rie interne
						$objectequipement->quantity 		= 1;
						$objectequipement->nbAddEquipement	= GETPOST('quantity-'.$line->id, 'int');;
						break;
					case 2 : // en mode g�n�ration � partir de la liste on d�termine en fonction de la saisie
						$objectequipement->quantity 		= 1;
						$objectequipement->nbAddEquipement	= 0; // sera calcul� en fonction
						break;
					case 3 : // en mode gestion de lot
						$objectequipement->quantity 		= GETPOST('quantity-'.$line->id, 'int');
						$objectequipement->nbAddEquipement	= 1;
						break;
				}

				$objectequipement->SerialMethod 	= GETPOST('SerialMethod-'.$line->id, 'int');
				$objectequipement->SerialFourn		= GETPOST('SerialFourn-'.$line->id, 'alpha');
				$objectequipement->numversion		= GETPOST('numversion-'.$line->id, 'alpha');
				//var_dump($objectequipement);
				$result = $objectequipement->create();
			}
		}
		$nbligneorder++;
	}
	$mesg='<div class="ok">'.$langs->trans("EquipementAdded").'</div>';
	$action="";
}


$head = ordersupplier_prepare_head($object);
dol_fiche_head($head, 'equipement', $langs->trans("SupplierOrder"), 0, 'order');
dol_htmloutput_mesg($mesg);
print '<table class="border" width="100%">';

$linkback = '<a href="'.DOL_URL_ROOT.'/fourn/commande/list.php'.(! empty($socid)?'?socid='.$socid:'').'">';
$linkback.= $langs->trans("BackToList").'</a>';

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


// Statut
print '<tr>';
print '<td>'.$langs->trans("Status").'</td>';
print '<td colspan="2">';
print $object->getLibStatut(4);
print "</td></tr>";
print "</table>";


dol_fiche_end();

// on r�cup�re les produit associ� � la commande fournisseur
print '<form name="equipement" action="'.$_SERVER['PHP_SELF'].'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="addequipement">';
print '<input type="hidden" name="id" value="'.$id.'">';
print '<table id="tablelines" class="noborder noshadow" width="100%">';

$num = count($object->lines);
$i = 0;	$total = 0;

if ($num) {
	print '<tr class="liste_titre">';
	print '<td align="left" width="200">'.$langs->trans('Label').'</td>';
	print '<td align="right" width="75">'.$langs->trans('Qty').'</td>';
	print '<td align="center" width="150">'.$langs->trans('EquipmentSerialMethod').'</td>';
	print '<td align="left" width="250">'.$langs->trans('ExternalSerial').'</td>';
	print '<td align="left" width="50">'.$langs->trans('Quantity').'</td>';
	print '<td align="left" width="100">'.$langs->trans('VersionNumber').'</td>';
	print '<td align="left" width="100">'.$langs->trans('EntrepotStock').'</td>';
	print '<td align="right" width="100">'.$langs->trans('Dateo').'</td>';
	print '<td align="right" width="100">'.$langs->trans('Datee').'</td>';
	print "</tr>\n";
}
$var=true;
while ($i <	$num) {
	$line =	$object->lines[$i];
	// only recept on serial product
	if ($line->fk_product > 0) {
		$var=!$var;
		// Show product and description
		$type=(! empty($line->product_type)?$line->product_type:(! empty($line->fk_product_type)?$line->fk_product_type:0));
		print '<tr '.$bc[$var].'>';
		// Show product and description
		print '<td valign=top>';

		print '<input type=hidden name="fk_product['.$line->id.']" value="'.$line->fk_product.'">';
		$product_static=new ProductFournisseur($db);
		$product_static->fetch($line->fk_product);
		$text=$product_static->getNomUrl(1, 'supplier');
		$text.= ' - '.$product_static->libelle;
		$description=($conf->global->PRODUIT_DESC_IN_FORM?'':dol_htmlentitiesbr($line->description));
		print $form->textwithtooltip($text, $description, 3, '', '', $i);

		// Show range
		print_date_range($date_start, $date_end);

		// Add description in form
		if (! empty($conf->global->PRODUIT_DESC_IN_FORM))
			print ($line->description && $line->description!=$product_static->libelle)?'<br>'.dol_htmlentitiesbr($line->description):'';

		print '<td  valign=top align="right" class="nowrap">'.$line->qty.'</td>';
		print '<td  valign=top align="center" >';
		$arraySerialMethod=array(
						'1'=>$langs->trans("InternalSerial"),
						'2'=>$langs->trans("ExternalSerial"),
						'3'=>$langs->trans("SeriesMode")
		);
		print $form->selectarray("SerialMethod-".$line->id, $arraySerialMethod, $conf->global->EQUIPEMENT_DEFAULTSERIALMODE);
		print '</td>';
		print '<td>';
		print '<textarea name="SerialFourn-'.$line->id.'" cols="50" rows="'.ROWS_3.'"></textarea>';
		print '</td>';
		print '<td  valign=top><input type=text name="quantity-'.$line->id.'" size=2 value="'.$line->qty.'"></td>';

		print '<td  valign=top><input type=text name="numversion-'.$line->id.'" value=""></td>';
		print '<td  valign=top>';
		print select_entrepot("", 'fk_entrepot-'.$line->id, 1, 1).'</td>';

		// Date open
		print '<td  valign=top align=right>';
		print $form->select_date(
						'', 'dateo-'.$line->id, 0, 0, '', 'dateo['.$line->id.']'
		).'</td>'."\n";

		// Date end
		print '<td  valign=top	align=right>';
		print $form->select_date(
						'', 'datee-'.$line->id, 0, 0, 1, 'datee['.$line->id.']'
		).'</td>'."\n";
		print '</tr>';
	}
	$i++;
}
print '</table>';

print '<div class="tabsAction">';
print '<input type="submit" class="button" value="'.$langs->trans("AddEquipement").'">';
print '</div>';
print '</form>';


$sql = "SELECT";
$sql.= " e.ref, e.rowid, e.fk_statut, e.fk_product, p.ref as refproduit, e.fk_entrepot, ent.label,";
$sql.= " e.unitweight, e.quantity,";
$sql.= " e.fk_soc_client, scli.nom as CompanyClient, e.fk_etatequipement, et.libelle as etatequiplibelle,";
$sql.= " ee.rowid as eerowid, ee.datee, ee.dateo, eet.libelle as equipevttypelibelle, ee.fk_equipementevt_type,";
$sql.= " ee.fk_fichinter, fi.ref as reffichinter, ee.fk_contrat, co.ref as refcontrat,";
$sql.= " ee.fk_expedition, exp.ref as refexpedition ";

$sql.= " FROM ".MAIN_DB_PREFIX."equipement as e";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_equipement_etat as et on e.fk_etatequipement = et.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."entrepot as ent on e.fk_entrepot = ent.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as scli on e.fk_soc_client = scli.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p on e.fk_product = p.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."equipementevt as ee ON e.rowid=ee.fk_equipement";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_equipementevt_type as eet on ee.fk_equipementevt_type = eet.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."fichinter as fi on ee.fk_fichinter = fi.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."contrat as co on ee.fk_contrat = co.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."expedition as exp on ee.fk_expedition = exp.rowid";

$sql.= " WHERE e.entity = ".$conf->entity;
$sql.= " and e.fk_commande_fourn=".$id;

if ($search_ref)			$sql .= " AND e.ref like '%".$db->escape($search_ref)."%'";
if ($search_refProduct)		$sql .= " AND p.ref like '%".$db->escape($search_refProduct)."%'";
if ($search_entrepot)		$sql .= " AND ent.label like '%".$db->escape($search_entrepot)."%'";
if ($search_company_client)	$sql .= " AND scli.nom like '%".$db->escape($search_company_client)."%'";
if ($search_etatequipement)	$sql .= " AND e.fk_etatequipement =".$search_etatequipement;
if ($search_equipevttype)	$sql .= " AND ee.fk_equipementevt_type =".$search_equipevttype;

$sql.= " ORDER BY ".$sortfield." ".$sortorder;
$sql.= $db->plimit($limit+1, $offset);

$result=$db->query($sql);
if ($result) {
	$num = $db->num_rows($result);

	$equipementstatic=new Equipement($db);

	$urlparam="&amp;id=".$id;
	if ($search_ref)				$urlparam .= "&amp;search_ref=".$db->escape($search_ref);
	if ($search_refProduct)			$urlparam .= "&amp;search_refProduct=".$db->escape($search_refProduct);
	if ($search_entrepot)			$urlparam .= "&amp;search_entrepot=".$db->escape($search_entrepot);
	if ($search_company_client)		$urlparam .= "&amp;search_company_client=".$db->escape($search_company_client);
	if ($search_etatequipement>=0)	$urlparam .= "&amp;search_etatequipement=".$search_etatequipement;
	if ($search_equipevttype>=0)	$urlparam .= "&amp;search_equipevttype=".$search_equipevttype;

	print_barre_liste(
					$langs->trans("ListOfEquipements").' ('.$num.')', $page, "expedition.php",
					$urlparam, $sortfield, $sortorder, '', $num
	);

	print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">'."\n";
	print '<input type="hidden" class="flat" name="id" value="'.$id.'">';
	print '<table class="noborder" width="100%">';

	print "<tr class='liste_titre'>";
	print_liste_field_titre(
					$langs->trans("Ref"), $_SERVER["PHP_SELF"], "e.ref",
					"", $urlparam, '', $sortfield, $sortorder
	);
	print_liste_field_titre(
					$langs->trans("RefProduit"), $_SERVER["PHP_SELF"], "p.ref",
					"", $urlparam, '', $sortfield, $sortorder
	);
	print_liste_field_titre(
					$langs->trans("Entrepot"), $_SERVER["PHP_SELF"], "ent.label",
					"", $urlparam, '', $sortfield, $sortorder
	);
	print_liste_field_titre($langs->trans("Dateo"), $_SERVER["PHP_SELF"], "e.dateo", "", $urlparam, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Datee"), $_SERVER["PHP_SELF"], "e.datee", "", $urlparam, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("UnitWeight"), $_SERVER["PHP_SELF"], "e.unitweight", "", $urlparam, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Contrat"), $_SERVER["PHP_SELF"], "co.ref", "", $urlparam, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Expedition"), $_SERVER["PHP_SELF"], "exp.ref", "", $urlparam, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("EtatEquip"), $_SERVER["PHP_SELF"], "e.fk_equipementetat", "", $urlparam, '', $sortfield, $sortorder);
	print_liste_field_titre(
					$langs->trans("TypeofEquipementEvent"), $_SERVER["PHP_SELF"], "ee.fk_equipementevt_type",
					"", $urlparam, ' colspan=2 ', $sortfield, $sortorder
	);
	print "</tr>\n";

	print '<tr class="liste_titre">';
	print '<td class="liste_titre"><input type="text" class="flat" name="search_ref" value="'.$search_ref.'" size="8"></td>';
	print '<td class="liste_titre"><input type="text" class="flat" name="search_refProduct" value="'.$search_refProduct.'" size="8"></td>';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_entrepot" value="'.$search_entrepot.'" size="10"></td>';


	print '<td class="liste_titre" colspan="1" align="right">';
	print '<input class="flat" type="text" size="1" maxlength="2" name="monthdatee" value="'.$monthdatee.'">';
	$syear = $yeardatee;
	if ($syear == '') $syear = date("Y");
	print '&nbsp;/&nbsp;<input class="flat" type="text" size="1" maxlength="4" name="yeardatee" value="'.$syear.'">';
	print '</td>';

	print '<td class="liste_titre" colspan="1" align="right">';
	print '<input class="flat" type="text" size="1" maxlength="2" name="monthdateo" value="'.$monthdateo.'">';
	$syear = $yeardateo;
	if ($syear == '') $syear = date("Y");
	print '&nbsp;/&nbsp;<input class="flat" type="text" size="1" maxlength="4" name="yeardateo" value="'.$syear.'">';
	print '</td>';
	print '<td></td>';
	print '<td class="liste_titre"><input type="text" class="flat" name="search_refcontrat" value="'.$search_refcontrat.'" size="10"></td>';
	print '<td class="liste_titre"><input type="text" class="flat" name="search_refexpedition" value="'.$search_refexpedition.'" size="10"></td>';

	// liste des �tat des �quipements
	print '<td class="liste_titre" align="right">';
	print select_equipement_etat($search_etatequipement, 'search_etatequipement', 1, 1);
	print '</td>';

	print '<td class="liste_titre" align="right">';
	print select_equipementevt_type($search_equipevttype, 'search_equipevttype', 1, 1);
	print '</td><td>';
	print '<input class="liste_titre" type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png"';
	print ' value="'.dol_escape_htmltag($langs->trans("Search")).'"';
	print ' title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '</td>';
	print "</tr>\n";


	$var=True;
	$total = 0;
	$totalWeight=0;
	$i = 0;
	while ($i < min($num, $limit)) {
		$objp = $db->fetch_object($result);
		$var=!$var;
		print "<tr $bc[$var]>";
		print "<td>";
		$equipementstatic->id=$objp->rowid;
		$equipementstatic->ref=$objp->ref;
		print $equipementstatic->getNomUrl(1);
		print "</td>";

		print '<td>';
		if ($objp->fk_product) {
			$productstatic=new Product($db);
			$productstatic->fetch($objp->fk_product);
			print $productstatic->getNomUrl(1);
		}
		print '</td>';

		// entrepot

		print "<td>";
		if ($objp->fk_entrepot>0) {
			$entrepotstatic = new Entrepot($db);
			$entrepotstatic->fetch($objp->fk_entrepot);
			print $entrepotstatic->getNomUrl(1);
		}
		print '</td>';



		print '</td>';
		print "<td nowrap align='center'>".dol_print_date($db->jdate($objp->dateo), 'day')."</td>\n";
		print "<td nowrap align='center'>".dol_print_date($db->jdate($objp->datee), 'day')."</td>\n";

		print "<td nowrap align='right'>".price($objp->unitweight)."</td>\n";
		$totalWeight+= ($objp->unitweight*$objp->quantity);

		print "<td>";
		if ($objp->fk_contrat>0) {
			$contrat = new Contrat($db);
			$contrat->fetch($objp->fk_contrat);
			print $contrat->getNomUrl(1);
			if ($objp->fk_soc_client != $contrat->socid) {
				$soc = new Societe($db);
				$soc->fetch($contrat->socid);
				print "<br>".$soc->getNomUrl(1);
			}
		}
		print '</td>';

		print "<td>";
		if ($objp->fk_fichinter>0) {
			$fichinter = new Fichinter($db);
			$fichinter->fetch($objp->fk_fichinter);
			print $fichinter->getNomUrl(1);
			if ($objp->fk_soc_client != $fichinter->socid) {
				$soc = new Societe($db);
				$soc->fetch($fichinter->socid);
				print "<br>".$soc->getNomUrl(1);
			}
		}
		print '</td>';

		print '<td align="right">'.($objp->etatequiplibelle ? $langs->trans($objp->etatequiplibelle):'').'</td>';
		print '<td align="right">'.($objp->equipevttypelibelle ? $langs->trans($objp->equipevttypelibelle):'').'</td>';
		print '<td align="right">';
        print '</td>';
		print "</tr>\n";

		$i++;
	}
	print '<tr class="liste_total"><td colspan="4" align=right class="liste_total"><b>'.$langs->trans("Total").'</b></td>';
	print '<td align="right" nowrap="nowrap" class="liste_total">'.$i.'</td>';
	print '<td align="right" nowrap="nowrap" class="liste_total">'.price($totalWeight).'</td><td colspan=5>&nbsp;</td>';
	print '</tr>';

	print '</table>';
	print "</form>\n";
	$db->free($result);
} else
	dol_print_error($db);

llxFooter();
$db->close();