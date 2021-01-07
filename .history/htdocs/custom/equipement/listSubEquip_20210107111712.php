<?php
/* Copyright (C) 2013-2016	Charlie Benke	<charlie@patas-monkey.com>
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
 *	\file	   htdocs/equipement/listSubEquip.php
 *	\brief	  List sub-equipement component
 *	\ingroup	equipement
 */

$res=@include("../main.inc.php");					// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../main.inc.php");		// For "custom" directory

require_once DOL_DOCUMENT_ROOT."/product/class/product.class.php";
require_once DOL_DOCUMENT_ROOT."/product/stock/class/entrepot.class.php";
require_once DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php";
require_once DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.facture.class.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/date.lib.php";

dol_include_once('/equipement/class/equipement.class.php');
dol_include_once('/equipement/core/lib/equipement.lib.php');

$langs->load("companies");
$langs->load("equipement@equipement");


// Security check
$equipementid = GETPOST('id', 'int');
if ($user->societe_id)
	$socid=$user->societe_id;
$result = restrictedArea($user, 'equipement', $id, 'equipement', '', 'fk_soc_client');

$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST('page', 'int');
if ($page == -1) {
	$page = 0;
}
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="e.ref";

$limit = $conf->liste_limit;
$offset = $limit * $page ;

$search_ref=GETPOST('search_ref', 'alpha');
$search_refProduct=GETPOST('search_refProduct', 'alpha');
$search_company_fourn=GETPOST('search_company_fourn', 'alpha');
$search_reffact_fourn=GETPOST('search_reffact_fourn', 'alpha');
$search_company_client=GETPOST('search_company_client', 'alpha');
$search_reffact_client=GETPOST('search_reffact_client', 'alpha');
$search_entrepot=GETPOST('search_entrepot', 'alpha');
$search_etatequipement=GETPOST('search_etatequipement', 'alpha');
if ($search_etatequipement=="-1")
	$search_etatequipement="";

/*
 *	View
 */

llxHeader();

// premiere �tape on cherche les id des �quipements composants
$scomposition_ref=GETPOST('scomposition_ref', 'alpha');
$scomposition_numversion=GETPOST('scomposition_numversion', 'alpha');
$scomposition_productid=GETPOST('scomposition_productid', 'alpha');
if ($scomposition_productid=="-1")
	$scomposition_productid="";
$scomposition_fk_soc_fourn=GETPOST('scomposition_fk_soc_fourn', 'alpha');
if ($scomposition_fk_soc_fourn=="-1")
	$scomposition_fk_soc_fourn="";

// on ne prend que les �quipement qui matche et qui rentrent d�j� dans une composition
$sql  = "SELECT ea.fk_equipement_pere";
$sql .= " FROM "  . MAIN_DB_PREFIX . "equipementassociation as ea";
$sql .= " INNER JOIN "  . MAIN_DB_PREFIX . "equipement as e ON e.rowid = ea.fk_equipement_fils";
$sql .= " INNER JOIN "  . MAIN_DB_PREFIX . "equipement as epere ON epere.rowid = ea.fk_equipement_pere";
$sql .= " WHERE e.entity = " . getEntity('equipement');
if ($scomposition_ref)			$sql .= " AND e.ref like '%".$db->escape($scomposition_ref)."%'";
if ($scomposition_numversion)	$sql .= " AND e.numversion like '%".$db->escape($scomposition_numversion)."%'";
if ($scomposition_productid)	$sql .= " AND e.fk_product = ".$scomposition_productid;
if ($scomposition_fk_soc_fourn)	$sql .= " AND e.fk_soc_fourn =".$scomposition_fk_soc_fourn;

// si il y a une recherche de faite
if ($scomposition_ref.$scomposition_numversion.$scomposition_productid.$scomposition_fk_soc_fourn) {
	// ensuite pour chaque equipements trouv� on cherche les premiers parents
	$result=$db->query($sql);
	if ($result) {
		// on m�morise les id qui sont parents
		$ListEquipmentParent="";

		$num = $db->num_rows($result);
		$i=0;
		while ($i < $num) {
			$objp = $db->fetch_object($result);
            $ListEquipmentParent .= $objp->fk_equipement_pere . ", ";
			$i++;
		}
		// on vire la derni�re virgule et l'espace
		$ListEquipmentParent =substr($ListEquipmentParent, 0, -2);
	}
}

$sql = "SELECT";
$sql.= " e.ref, e.rowid, e.fk_statut, e.fk_product, p.ref as refproduit, e.fk_entrepot,";
$sql.= " e.fk_soc_fourn, sfou.nom as CompanyFourn, e.fk_facture_fourn, ff.ref as refFactureFourn,";
$sql.= " e.fk_soc_client, scli.nom as CompanyClient, e.fk_facture, f.facnumber as refFacture,";
$sql.= " e.datee, e.dateo, ee.libelle as etatequiplibelle";

$sql.= " FROM ".MAIN_DB_PREFIX."equipement as e";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_equipement_etat as ee on e.fk_etatequipement = ee.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as sfou on e.fk_soc_fourn = sfou.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."entrepot as ent on e.fk_entrepot = ent.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as scli on e.fk_soc_client = scli.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture as f on e.fk_facture = f.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture_fourn as ff on e.fk_facture_fourn = ff.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p on e.fk_product = p.rowid";
$sql.= " WHERE e.entity = ". getEntity('equipement');

// on ne prend que les �quipements dans la liste
if ($ListEquipmentParent)
	$sql.=" and e.rowid in(".$ListEquipmentParent.")";
else
	$sql.=" and 1=2"; // on n'affiche que si il y a des donn�es recherch�es

if ($search_ref)			$sql .= " AND e.ref like '%".$db->escape($search_ref)."%'";
if ($search_refProduct)		$sql .= " AND p.ref like '%".$db->escape($search_refProduct)."%'";
if ($search_company_fourn)	$sql .= " AND sfou.nom like '%".$db->escape($search_company_fourn)."%'";
if ($search_reffact_fourn)	$sql .= " AND ff.facnumber like '%".$db->escape($search_reffact_fourn)."%'";
if ($search_entrepot)		$sql .= " AND ent.label like '%".$db->escape($search_entrepot)."%'";
if ($search_company_client)	$sql .= " AND scli.nom like '%".$db->escape($search_company_client)."%'";
if ($search_reffact_client)	$sql .= " AND f.facnumber like '%".$db->escape($search_reffact_client)."%'";
if ($search_etatequipement)	$sql .= " AND e.fk_etatequipement =".$search_etatequipement;
$sql.= " ORDER BY ".$sortfield." ".$sortorder;
$sql.= $db->plimit($limit+1, $offset);

$result=$db->query($sql);
if ($result) {
	$form = new Form($db);
	$num = $db->num_rows($result);

	$equipementstatic=new Equipement($db);

	$urlparam="";
	print_barre_liste(
					$langs->trans("ListOfSubEquipement"), $page, "list.php",
					$urlparam, $sortfield, $sortorder, '', $num
	);

	print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">';

	// un tableau de saisie des infos du composant recherch�
	print '<table class="nobordernopadding" >';
	print "<tr class='liste_titre'>";
	print '<td colspan="4">'.$langs->trans("SearchInsideEquipement").'</td></tr>';
	print "<tr $bc[0] ><td width=100px>".$langs->trans("Ref").':</td>';
	print '<td width=100px>';
	print '<input class="flat" type="text" size="14" name="scomposition_ref" value="'.$scomposition_ref.'"></td>';

	print "<td width=100px>".$langs->trans("VersionNumber").':</td>';
	print '<td width=100px>';
	print '<input class="flat" type="text" size="14" name="scomposition_numversion" value="'.$scomposition_numversion.'">';
	print '</td></tr>';
	print "<tr $bc[0] ><td>".$langs->trans("RefProduit").':</td>';
	print '<td colspan=2>';
	print $form->select_produits(
					$scomposition_productid, 'scomposition_productid',
					0, $conf->product->limit_size, 0, -1, 2, '', 0
	);
	print '</td>';
	print '<td rowspan="3"><input type="submit" class="button" value="'.$langs->trans("Search").'"></td></tr>';
	print '</tr>';
	print "<tr $bc[0] ><td>".$langs->trans("Fournisseur").':</td>';

	// on filtre pour n'afficher que des tiers fournisseurs
	print '<td colspan=2>';
	print $form->select_company(
					$scomposition_fk_soc_fourn, 'scomposition_fk_soc_fourn', 's.fournisseur=1', 1
	);;
	print '</td></tr>';
	print "</table><br><br>";

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
					$langs->trans("Fournisseur"), $_SERVER["PHP_SELF"], "sfou.nom",
					"", $urlparam, '', $sortfield, $sortorder
	);
	print_liste_field_titre(
					$langs->trans("RefFactFourn"), $_SERVER["PHP_SELF"], "ff.facnumber",
					"", $urlparam, '', $sortfield, $sortorder
	);
	print_liste_field_titre(
					$langs->trans("Entrepot"), $_SERVER["PHP_SELF"], "ent.label",
					"", $urlparam, '', $sortfield, $sortorder
	);
	print_liste_field_titre(
					$langs->trans("CompanyClient"), $_SERVER["PHP_SELF"], "scli.nom",
					"", $urlparam, '', $sortfield, $sortorder
	);
	print_liste_field_titre(
					$langs->trans("RefFactClient"), $_SERVER["PHP_SELF"], "f.facnumber",
					"", $urlparam, '', $sortfield, $sortorder
	);
	print_liste_field_titre(
					$langs->trans("Dateo"), $_SERVER["PHP_SELF"], "e.dateo",
					"", $urlparam, '', $sortfield, $sortorder
	);
	print_liste_field_titre(
					$langs->trans("Datee"), $_SERVER["PHP_SELF"], "e.datee",
					"", $urlparam, '', $sortfield, $sortorder
	);
	print_liste_field_titre(
					$langs->trans("EtatEquip"), $_SERVER["PHP_SELF"], "e.fk_equipementetat",
					"", $urlparam, '', $sortfield, $sortorder
	);
	print_liste_field_titre(
					$langs->trans("Status"), $_SERVER["PHP_SELF"], "e.fk_statut",
					"", $urlparam, 'align="right"', $sortfield, $sortorder
	);
	print "</tr>\n";

	print '<tr class="liste_titre">';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_ref" value="'.$search_ref.'" size="10"></td>';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_refProduct" value="'.$search_refProduct.'" size="10"></td>';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_company_fourn" value="'.$search_company_fourn.'" size="10"></td>';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_reffact_fourn" value="'.$search_reffact_fourn.'" size="10"></td>';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_entrepot" value="'.$search_entrepot.'" size="10"></td>';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_company_client" value="'.$search_company_client.'" size="10"></td>';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_reffact_client" value="'.$search_reffact_client.'" size="10"></td>';

	print '<td class="liste_titre" colspan="1" align="right">';
	print '<input class="flat" type="text" size="2" maxlength="2" name="monthdatee" value="'.$monthdatee.'">';
	$syear = $yeardatee;
	if ($syear == '') $syear = date("Y");
	print '&nbsp;/&nbsp;<input class="flat" type="text" size="4" maxlength="4" name="yeardatee" value="'.$syear.'">';
	print '</td>';

	print '<td class="liste_titre" colspan="1" align="right">';
	print '<input class="flat" type="text" size="2" maxlength="2" name="monthdateo" value="'.$monthdateo.'">';
	$syear = $yeardateo;
	if ($syear == '') $syear = date("Y");
	print '&nbsp;/&nbsp;<input class="flat" type="text" size="4" maxlength="4" name="yeardateo" value="'.$syear.'">';
	print '</td>';

	// liste des �tat des �quipements
	print '<td class="liste_titre" align="right">';
	print select_equipement_etat($search_etatequipement, 'search_etatequipement', 1, 1);
	print '</td>';

	print '<td class="liste_titre" align="right">';

	print '<select class="flat" name="viewstatut">';
	print '<option value="">&nbsp;</option>';
	print '<option ';
	if ($viewstatut=='0') print ' selected ';
	print ' value="0">'.$equipementstatic->LibStatut(0).'</option>';
	print '<option ';
	if ($viewstatut=='1') print ' selected ';
	print ' value="1">'.$equipementstatic->LibStatut(1).'</option>';
	print '<option ';
	if ($viewstatut=='2') print ' selected ';
	print ' value="2">'.$equipementstatic->LibStatut(2).'</option>';
	print '</select>';
	print '<input class="liste_titre" type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png"';
	print ' value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print "</td></tr>\n";

	$var=True;
	$total = 0;
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

		print "<td>";
		if ($objp->fk_soc_fourn) {
			$soc = new Societe($db);
			$soc->fetch($objp->fk_soc_fourn);
			print $soc->getNomUrl(1);
		}
		print '</td>';

		print "<td>";
		if ($objp->fk_facture_fourn>0) {
			$factfournstatic = new FactureFournisseur($db);
			$factfournstatic->fetch($objp->fk_facture_fourn);
			print $factfournstatic ->getNomUrl(1);
		}
		print '</td>';

		// entrepot
		print "<td>";
		if ($objp->fk_entrepot>0) {
			$entrepotstatic = new Entrepot($db);
			$entrepotstatic ->fetch($objp->fk_entrepot);
			print $entrepotstatic  ->getNomUrl(1);
		}
		print '</td>';

		print "<td>";
		if ($objp->fk_soc_client>0) {
			$soc = new Societe($db);
			$soc->fetch($objp->fk_soc_client);
			print $soc->getNomUrl(1);
		}
		print '</td>';

		print "<td>";
		if ($objp->fk_facture>0) {
			$facturestatic=new Facture($db);
			$facturestatic->fetch($objp->fk_facture);
			print $facturestatic ->getNomUrl(1);
		}
		print '</td>';

		print "<td nowrap align='center'>".dol_print_date($db->jdate($objp->dateo), 'day')."</td>\n";
		print "<td nowrap align='center'>".dol_print_date($db->jdate($objp->datee), 'day')."</td>\n";

<<<<<<< HEAD
        print '<td align="right">';
=======
		print '<td align="right">';
>>>>>>> Module Equipement : correction de la liste des equipements composant
        if($objp->etatequiplibelle)
        {
            print $langs->trans($objp->etatequiplibelle);
        }
        print '</td>';

<<<<<<< HEAD
		print '<td align="right">'.$equipementstatic->LibStatut($objp->fk_statut, 5).'</td>';
=======
        print '<td align="right">'.$equipementstatic->LibStatut($objp->fk_statut, 5).'</td>';
>>>>>>> Module Equipement : correction de la liste des equipements composant
		print "</tr>\n";
		$i++;
	}
	print '</table>';
	print "</form>\n";
	$db->free($result);
} else
	dol_print_error($db);

llxFooter();
$db->close();