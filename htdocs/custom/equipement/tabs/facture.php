<?php
/* Copyright (C) 2012-2017	Charlie Benke	 <charlie@patas-monkey.com>
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
 *	\file	   htdocs/equipement/tabs/facture.php
 *	\brief	  List of all equipement associated with a bill
 *	\ingroup	equipement
 */
$res=@include("../../main.inc.php");					// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../../main.inc.php");		// For "custom" directory

require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
require_once(DOL_DOCUMENT_ROOT.'/core/class/discount.class.php');
require_once(DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/invoice.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/stock.lib.php");

dol_include_once('/equipement/class/equipement.class.php');
dol_include_once('/equipement/core/lib/equipement.lib.php');

$langs->load("companies");
$langs->load("equipement@equipement");
$langs->load("bills");

$factureid=GETPOST('id', 'int');
$factureref=GETPOST('ref');

$fk_entrepot = GETPOST('fk_entrepot');
if ($fk_entrepot == -1 || $fk_entrepot == "")
	$fk_entrepot = 0;
// Security check

if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'equipement', $equipementid, 'equipement');

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
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

$search_ref=GETPOST('search_ref', 'alpha');
$search_refProduct=GETPOST('search_refProduct', 'alpha');
$search_company_fourn=GETPOST('search_company_fourn', 'alpha');
$search_reffact_fourn=GETPOST('search_reffact_fourn', 'alpha');
$search_entrepot=$entrepotid;

$action	= GETPOST('action', 'alpha');

$object = new Facture($db);
$result = $object->fetch($factureid, $factureref);
if ($factureid =="")
	$factureid = $object->id;

// gestion du transfert d'un �quipement dans un entrepot
if ($action=="AddEquipement") {
	// a partir de la facture on r�cup�re l'id du client
	$socid=$object->socid;
	$separatorlist=$conf->global->EQUIPEMENT_SEPARATORLIST;
	$separatorlist =($separatorlist ? $separatorlist : ";");
	if ($separatorlist == "__N__")
		$separatorlist="\n";
	if ($separatorlist == "__B__")
		$separatorlist="\b";
	$tblSerial=explode($separatorlist, GETPOST('listEquipementRef', 'alpha'));
	$nbCreateSerial=count($tblSerial);
	$i=0;
	while ($nbCreateSerial > $i) {
		$equipement = new Equipement($db);
		// on associe aussi avec le client de la facture
		$equipement->fetch('', $tblSerial[$i]);
		$equipement->set_client($user, $socid);
		// on associe avec la facture
		$equipement->fetch('', $tblSerial[$i]);
		$equipement->set_fact_client($user, $factureid);
		$i++;
	}
}

if ($action=="AddEquipementAuto") {
	$socid=$object->socid;
	$tblproduct = explode(";", GETPOST('listEquipementRef', 'alpha'));

	foreach ($tblproduct as $fk_product) {
		// on regarde si il y a des �quipements de dispo pour ce produit dans cet EntrepotStock
		$sql = "SELECT e.rowid, e.quantity  FROM ".MAIN_DB_PREFIX."equipement as e";
		$sql.= " WHERE fk_product=".$fk_product;
		$sql.= " AND fk_entrepot=".$fk_entrepot;
		$sql.= " AND (fk_soc_client is null or fk_soc_client = -1 or fk_soc_client = 0) ";
		$sql.= " AND fk_statut=1";

		$resql=$db->query($sql);
		if ($resql) {
			// on boucle sur les �quipements
			$num = $db->num_rows($resql);
			if ($num > 0) {
				$i=0;
				while ($i < $num) {
					$obj = $db->fetch_object($resql);
					// on regarde si il s'agit d'un equipement unique ou multiple
					$equipement = new Equipement($db);
					// on associe aussi avec le client de la facture
					$equipement->fetch($obj->rowid);

					// si c'est un �quipement de type lot
					if ($obj->quantity > 1) {
						$nbtocut = GETPOST("multi-".$obj->rowid);
						// si pas de quantit� � couper/utiliser
						if ($nbtocut > 0 ) {
							// si on a s�lectionn� qu'une partie du lot, on d�coupe et on se positionne sur le d�coup�
							if ($nbtocut < $obj->quantity)
								$equipement->fetch($equipement->cut_equipement($equipement->ref."-F", $nbtocut, 1));

							$equipement->set_client($user, $socid);
							// on associe avec la facture
							//$equipement->fetch($obj->rowid);
							$equipement->set_fact_client($user, $factureid);
							// et on le sort enfin de l'EntrepotStock
							$equipement->set_entrepot($user, -1);
						}
					} else {
						// si la case est coch� on ajoute � la facture
						if (GETPOST("unit-".$obj->rowid) == 1) {
							$equipement->set_client($user, $socid);
							// on associe avec la facture
							$equipement->set_fact_client($user, $factureid);
							// et on le sort enfin de l'EntrepotStock
							$equipement->set_entrepot($user, -1);
						}
					}
					$i++;
				}
			}
		}
	}

} elseif ($action=="Fillinvoice") {
	$equipement = new Equipement($db);
	$equipement->fillinvoice($factureid);
} elseif ($action=="GetEquipmentFromShipping") {
	$equipement = new Equipement($db);
	$shippingid=GETPOST("shippingid");
	$equipement->GetEquipementFromShipping($factureid, $shippingid);

}


/*
 *	View
 */
llxHeader();
$form = new Form($db);

$object = new Facture($db);
$result = $object->fetch($factureid);

$head = facture_prepare_head($object);
dol_fiche_head($head, 'equipement', $langs->trans('InvoiceCustomer'), 0, 'bill');

print '<table class="border" width="100%">';

// Ref
print '<tr><td width="20%">'.$langs->trans('Ref').'</td>';
print '<td colspan="5">';
$morehtmlref='';
$discount=new DiscountAbsolute($db);
$result=$discount->fetch(0, $object->id);
if ($result > 0) {
	$morehtmlref=' ('.$langs->trans("CreditNoteConvertedIntoDiscount", $discount->getNomUrl(1, 'discount')).')';
}
if ($result < 0) {
	dol_print_error('', $discount->error);
}
print $form->showrefnav($object, 'ref', '', 1, 'facnumber', 'ref', $morehtmlref);
print '</td></tr>';

// Third party

$soc=new Societe($db);
$soc->fetch($object->socid);
print '<tr><td>'.$langs->trans('Company').'</td><td colspan="3">'.$soc->getNomUrl(1).'</td></tr>';
print '</table>';
print '<br><br>';



$sql = "SELECT";
$sql.= " e.ref, e.rowid, e.fk_statut, e.fk_product, p.ref as refproduit, e.fk_entrepot,";
$sql.= " e.fk_soc_fourn, sfou.nom as CompanyFourn, e.fk_facture_fourn, ff.ref as refFactureFourn,";
$sql.= " e.fk_facture, f.ref as refFacture, e.quantity,";
$sql.= " e.datee, e.dateo, ee.libelle as etatequiplibelle";

$sql.= " FROM ".MAIN_DB_PREFIX."equipement as e";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_equipement_etat as ee on e.fk_etatequipement = ee.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as sfou on e.fk_soc_fourn = sfou.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."entrepot as ent on e.fk_entrepot = ent.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture as f on e.fk_facture = f.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture_fourn as ff on e.fk_facture_fourn = ff.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p on e.fk_product = p.rowid";
$sql.= ' WHERE e.entity IN (' . getEntity('equipement') . ')';
if ($search_ref)			$sql .= " AND e.ref like '%".$db->escape($search_ref)."%'";
if ($search_refProduct)		$sql .= " AND p.ref like '%".$db->escape($search_refProduct)."%'";
if ($search_company_fourn)	$sql .= " AND sfou.nom like '%".$db->escape($search_company_fourn)."%'";
if ($search_reffact_fourn)	$sql .= " AND ff.ref like '%".$db->escape($search_reffact_fourn)."%'";
if ($search_entrepot)		$sql .= " AND ent.ref like '%".$db->escape($search_entrepot)."%'";
if ($search_etatequipement)	$sql .= " AND e.fk_etatequipement =".$search_etatequipement;

$sql .= " AND e.fk_facture =".$factureid;
$sql.= " ORDER BY ".$sortfield." ".$sortorder;
$sql.= $db->plimit($limit+1, $offset);

$result=$db->query($sql);
if ($result) {
	$numEquipement = $db->num_rows($result);

	$equipementstatic=new Equipement($db);

	$urlparam="&amp;id=".$factureid;
	if ($search_ref)				$urlparam .= "&amp;search_ref=".$db->escape($search_ref);
	if ($search_refProduct)			$urlparam .= "&amp;search_refProduct=".$db->escape($search_refProduct);
	if ($search_company_fourn)		$urlparam .= "&amp;search_company_fourn=".$db->escape($search_company_fourn);
	if ($search_reffact_fourn)		$urlparam .= "&amp;search_reffact_fourn=".$db->escape($search_reffact_fourn);
	if ($search_entrepot)			$urlparam .= "&amp;search_entrepot=".$db->escape($search_entrepot);
	if ($search_etatequipement>=0)	$urlparam .= "&amp;search_etatequipement=".$search_etatequipement;

	print_barre_liste($langs->trans("ListOfEquipements"), $page, "facture.php", $urlparam, $sortfield, $sortorder, '', $num);

	print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">'."\n";
	print '<input type="hidden" class="flat" name="id" value="'.$factureid.'">';
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
	print '<input type="text" class="flat" name="search_ref" value="'.$search_ref.'" size="8"></td>';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_refProduct" value="'.$search_refProduct.'" size="8"></td>';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_company_fourn" value="'.$search_company_fourn.'" size="10"></td>';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_reffact_fourn" value="'.$search_reffact_fourn.'" size="10"></td>';


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
	print '<input class="liste_titre" type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'"></td>';
	print "</tr>\n";


	$var=True;
	$total = 0;
	$i = 0;
	while ($i < $numEquipement) {
		$objp = $db->fetch_object($result);
		$var=!$var;
		print "<tr $bc[$var]>";
		print "<td>";

		$equipementstatic->id=$objp->rowid;
		$equipementstatic->ref=$objp->ref;
		print $equipementstatic->getNomUrl(1);
		if ($objp->quantity > 1)
			print ' ('.$objp->quantity.')';
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
		if ($objp->fk_facture_fourn) {
			$factfournstatic = new FactureFournisseur($db);
			$factfournstatic->fetch($objp->fk_facture_fourn);
			print $factfournstatic ->getNomUrl(1);
		}
		print '</td>';


		print "<td nowrap align='center'>".dol_print_date($db->jdate($objp->dateo), 'day')."</td>\n";
		print "<td nowrap align='center'>".dol_print_date($db->jdate($objp->datee), 'day')."</td>\n";

		print '<td align="right">'.($objp->etatequiplibelle ? $langs->trans($objp->etatequiplibelle):'').'</td>';
		print '<td align="right">'.$equipementstatic->LibStatut($objp->fk_statut, 5).'</td>';
		print "</tr>\n";

		$i++;
	}

	print '</table>';
	print "</form>\n";
	$db->free($result);
} else
	dol_print_error($db);

$form = new Form($db);

// Association d'�quipement � la facture
foreach ($object->lines as $line) {
	if ($line->product_type == 0 && $line->fk_product > 0) {
		$remainqty = $line->qty;

		// on v�rifie que le produit n'est pas d�j� totalement affect�
		// on regarde si il y a des �quipements de dispo pour ce produit dans cet EntrepotStock
		$sql = "SELECT sum(e.quantity) as nb FROM ".MAIN_DB_PREFIX."equipement as e";
		$sql.= " WHERE fk_product=".$line->fk_product;
		$sql.= " AND fk_facture=".$factureid;
//		print $sql;
		$resql=$db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			if ($num > 0) {
				$obj = $db->fetch_object($resql);
				$remainqty -= $obj->nb;
			}
		}

		if ($remainqty > 0)
			$blinepresent=true;
	}
}

if ($blinepresent) {
	print "<br><br>";
	print '<form method="post" id="formselectentrepot" action="'.$_SERVER["PHP_SELF"].'">'."\n";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="AddEquipementAutoselect">';
	print '<input type="hidden" name="id" value="'.$factureid.'">';

	print '<table  width="50%">';
	print '<tr><td width="25%">'.$langs->trans("SelectEquipementStorage").'</td><td width="50%">';
	select_entrepot($fk_entrepot, 'fk_entrepot', 1, 1, 1, 0);
	print '</td><td width="25%" align=left>'."\n";
	print '<input type="submit" class="button" value="'.$langs->trans('SelectEntrepot').'" name="selectentrepot">';
	print '</td></tr>'."\n";
	print '</table  >';
	print '</form>'."\n";


	if ($fk_entrepot > 0) {
		print '<script>  $( function() {';
		print '		$( "#accordion" ).accordion({ collapsible: true, heightStyle: "panel" });  ';
		print '} );  </script>';
		print '<div id="accordion">';

		// saisie en mode pav� de lignes
		print '<h3>'.$langs->trans('ManualListEquipementToAdd').'</h3>';
		print '<div>';
		print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">'."\n";
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="AddEquipement">';
		print '<input type="hidden" name="id" value="'.$factureid.'">';
		print '<table  width="50%">';
		print "<tr >\n".'<td>';

		print '<textarea name="listEquipementRef" cols="80" rows="'.ROWS_3.'"></textarea>';
		print '</td><td align="left" valign="middle" >';
		print '<input type="submit" class="button" value="'.$langs->trans('Add').'" name="addline"></td>';
		print "</tr>\n";
		print '</table ></form>'."\n";
		print '</div>';

		// saisie intelligente...
		print '<h3>'.$langs->trans('SelectedListEquipementToAdd').'</h3>';
		print '<div style="padding:0;" >';
		print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">'."\n";
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="AddEquipementAuto">';
		print '<input type="hidden" name="id" value="'.$factureid.'">';
		print '<input type="hidden" name="fk_entrepot" value="'.$fk_entrepot.'">';
		print '<table  width="100%" class="noborder" >';
		print "<tr class='liste_titre' >";
		print '<th width="10%">'.$langs->trans("Product").'</th>';
		print '<th align=center>'.$langs->trans("AviableEquipement").'</th>'."\n";
		print '<th width=120px align=center>'.$langs->trans("QtyNeed").'</th>';

		print '</tr>';
		// on boucle sur les produits de la factures

		$product_static = new Product($db);
		$equipement_static = new Equipement($db);

		$blinepresent=false;
		$tmpproduct="";
		foreach ($object->lines as $line) {

			if ($line->product_type == 0 && $line->fk_product > 0 ) {
				$remainqty= $line->qty;
		//		var_dump($line->fk_product);
				// on v�rifie que le produit n'est pas d�j� totalement affect�
				// on regarde si il y a des �quipements de dispo pour ce produit dans cet EntrepotStock
				$sql = "SELECT sum(e.quantity) as nb FROM ".MAIN_DB_PREFIX."equipement as e";
				$sql.= " WHERE fk_product=".$line->fk_product;
				$sql.= " AND fk_facture=".$factureid;
		//		print $sql;
				$resql=$db->query($sql);
				if ($resql) {
					$num = $db->num_rows($resql);
					if ($num > 0) {
						$obj = $db->fetch_object($resql);
						$remainqty -= $obj->nb;
					}
				}

				if ($remainqty > 0) {
					$blinepresent=true;
					$tmpproduct.=$line->fk_product.";";
					$product_static->fetch($line->fk_product);
					$var=!$var;
					print "<tr $bc[$var]>";
					print '<td valign=top >';
					print "<a href='#detailligne".$line->fk_product."' onclick=\"$('.detailligne".$line->fk_product."').toggle();\" >";
					print img_picto("", "edit_add")."</a>&nbsp;";

					print $product_static->getNomUrl(1).'</td>';

					print '<td valign=top align=left colspan=2 style="padding:0px;">';

					// on regarde si il y a des �quipements de dispo pour ce produit dans cet EntrepotStock
					$sql = "SELECT e.rowid FROM ".MAIN_DB_PREFIX."equipement as e";
					$sql.= " WHERE fk_product=".$line->fk_product;
					$sql.= " AND fk_entrepot=".GETPOST('fk_entrepot');
					$sql.= " AND (fk_soc_client is null or fk_soc_client = -1 or fk_soc_client = 0) ";
					$sql.= " AND fk_statut=1";
					if ($conf->global->EQUIPEMENT_USEDLUODATE =="1")
						$sql.= " ORDER BY dated ASC";
					else
						$sql.= " ORDER BY datec ASC";

					dol_syslog("equipement/facture::fetch sql=".$sql, LOG_DEBUG);
					$resql=$db->query($sql);
					if ($resql) {
						// on boucle sur les �quipements
						$num = $db->num_rows($resql);
						if ($num > 0) {
							print '<table width="100%" style="padding:0px;">';
							print "<tr style='padding:0px;' $bc[$var] id='#detailligne".$line->fk_product."'>";
							print '<th width=150px align=left>'.$langs->trans("Ref").'</th>';
							print '<th width=1O0px align=center>'.$langs->trans("Version").'</th>';
							print '<th width=80px  align=center>'.$langs->trans("Datec").'</th>';
							print '<th width=80px  align=center>'.$langs->trans("Datee").'</th>';
							if ($conf->global->EQUIPEMENT_USEDLUODATE =="1")
								print '<th width=80px  align=center>'.$langs->trans("Dated").'</th>';

							print '<th width=120px align=right>'.$remainqty;

							print '</th></tr>';

							$i=0;
							while ($i < $num) {
								$obj = $db->fetch_object($resql);
								$equipement_static->fetch($obj->rowid);
								if ($bc[$var]=='class="pair"')
									print "<tr class='pair detailligne".$line->fk_product."'>";
								else
									print "<tr class='impair detailligne".$line->fk_product."'>";

								print '<td>'.$equipement_static->getNomUrl(1).'</td>';
								print '<td align=center>'.$equipement_static->numversion.'</td>';
								print '<td align=center>'.dol_print_date($equipement_static->datec).'</td>';
								print '<td align=center>'.dol_print_date($equipement_static->datee).'</td>';
								if ($conf->global->EQUIPEMENT_USEDLUODATE =="1")
									print '<td align=center>'.dol_print_date($equipement_static->dated).'</td>';

								if ($equipement_static->quantity == 1) {
									$checked="";
									if ($remainqty >= 1) {
										$checked=" checked ";
										$remainqty--;
									}
									print '<td align=right>';
									print '<input '.$checked.' id="unit-'.$obj->rowid.'" type=checkbox name="unit-'.$obj->rowid.'" value="1">';
									print '</td></tr>';
								} else {
									print '<td align=right>';
									if ($equipement_static->quantity >= $remainqty) {
										$selectqty=$remainqty;
										$remainqty=0;
									} else {
										$selectqty=$equipement_static->quantity;
										$remainqty-=$equipement_static->quantity;
									}

									print '<input type="text" size="4" class="amount" name="multi-'.$obj->rowid.'" value="'.$selectqty.'">';
									print '&nbsp;/&nbsp;'.$equipement_static->quantity;
								}
								$i++;
							}
							print '</table>';
						} else {
							print '<table width="100%">';
							print "<tr $bc[$var]>";
							print '<th align=center colspan=3>'.$langs->trans("NoEquipementInEntrepot").'</th>';
							print '<th align=left>'.$line->qty.'</th>';
							print '</tr></table>';
						}
					}
					print '</td></tr>'."\n";
				}
			}
		}

		if ($blinepresent) {
			print '<tr>';

			print '<td colspan=3 align=right > '."\n";
			$tmpproduct = substr($tmpproduct, 0, -1);
			print '<input type="hidden" name="listEquipementRef" value="'.$tmpproduct.'" >';
			print '<input type="submit" class="button" value="'.$langs->trans('SelectAndCut').'" name="selectentrepot" >';

			print '</td></tr>'."\n";
		} else {
			// si pas d'�quipement � affect� � la facture
			// ben on cache la fonction de s�lection
			print '	<script>'."\n";
			print ' $("#formselectentrepot").hide();';
			print ' $("#accordion").hide();';
			print '	</script>'."\n";

		}
		print '</table  >';
		print '</form>'."\n";

		print '	<script>'."\n";
		print ' $(document).ready(function () {';
		print ' 	$(".AutoFillAmout").on(\'click touchstart\', function(){
						$("input[name="+$(this).data(\'rowname\')+"]").val($(this).data("value")).trigger("change");
					});';
		print '	});'."\n";
		print '	</script>'."\n";

		print '</div>';

		print '</div>';
	}
}

dol_fiche_end();

print '<div class="tabsAction">';
// as-ton une exp�dition associ� � la facture?
$object->fetchObjectLinked('', 'shipping', $object->id, 'facture');
if (count($object->linkedObjects) > 0 ) {
	$shippingid=(array_values($object->linkedObjectsIds['shipping']));
	print '<a class="butAction" href="facture.php?id='.$factureid.'&action=GetEquipmentFromShipping&shippingid='.$shippingid[0].'">';
	print $langs->trans("GetEquipmentFromShipping").'</a>';
}
// Bouton pour ajouter les num�ros de s�rie des �quipements de la commande dans la facture ' si il existe
if ($numEquipement > 0 ) {
	print '<a class="butAction" href="facture.php?id='.$factureid.'&action=Fillinvoice">';
	print $langs->trans("FillInvoiceFromEquipment").'</a>';
}
print '</div>';

llxFooter();
$db->close();
