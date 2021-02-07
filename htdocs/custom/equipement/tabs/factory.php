<?php
/* Copyright (C) 2012-2016	Charlie Benke	 <charlie@patas-monkey.com>
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
 *	\file	   htdocs/equipement/tabs/factory.php
 *	\brief	  List of all equipement associated with factory
 *	\ingroup	equipement
 */
$res=@include("../../main.inc.php");					// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../../main.inc.php");		// For "custom" directory

require_once(DOL_DOCUMENT_ROOT."/product/stock/class/entrepot.class.php");
require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");
//require_once(DOL_DOCUMENT_ROOT."/core/lib/product.lib.php");

dol_include_once('/equipement/class/equipement.class.php');
//dol_include_once('/equipement/core/lib/equipement.lib.php');

dol_include_once('/factory/class/factory.class.php');
dol_include_once('/factory/core/lib/factory.lib.php');


$langs->load("companies");
$langs->load("product");
$langs->load("equipement@equipement");

$factoryid=GETPOST('id', 'int');



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

$search_ref				= GETPOST('search_ref', 'alpha');
$search_numversion		= GETPOST('search_numversion', 'alpha');
$search_company_fourn	= GETPOST('search_company_fourn', 'alpha');
$search_reffact_fourn	= GETPOST('search_reffact_fourn', 'alpha');
$search_company_client	= GETPOST('search_company_client', 'alpha');
$search_reffact_client	= GETPOST('search_reffact_client', 'alpha');
$search_entrepot		= GETPOST('search_entrepot', 'alpha');
$search_etatequipement	= GETPOST('search_etatequipement');

$form = new Form($db);
llxHeader();

$factory = new Factory($db);
$product = new Product($db);

$reffactory=GETPOST('ref', 'alpha');
$result = $factory->fetch($factoryid, $reffactory);
$result = $product->fetch($factory->fk_product);
if ($reffactory) $factoryid = $factory->id;

$head = factory_prepare_head($factory, $user);

dol_fiche_head($head, 'equipement', $langs->trans("Factory"), 0, 'factory@factory');

$refOF=$factory->ref;


print '<table class="border" width="100%">';
print "<tr>";

//$bproduit = ($product->isproduct());

// Reference OF
print '<td width="20%">'.$langs->trans("Ref").'</td><td colspan=3>';
print $form->showrefnav($factory, 'ref', '', 1, 'ref');
print '</td></tr>';

// Lieu de stockage
print '<tr><td><table class="nobordernopadding" width="100%"><tr><td>'.$langs->trans("Warehouse").'</td>';
print '</tr></table></td><td colspan="3">';
if ($factory->fk_entrepot>0) {
	$entrepotStatic=new Entrepot($db);
	$entrepotStatic->fetch($factory->fk_entrepot);
	$fk_entrepot=$factory->fk_entrepot;
	print $entrepotStatic->getNomUrl(1)." - ".$entrepotStatic->lieu." (".$entrepotStatic->zip.")" ;
}
print '</td></tr>';

// Date start planned
print '<tr><td width=20%>'.$langs->trans("FactoryDateStartPlanned");
print '</td ><td width=20%>';
print dol_print_date($factory->date_start_planned, 'day');
print '</td>';

// Date start made
print '<td valign=top  width=20%>'.$langs->trans("DateStartMade").'</td><td>';
print dol_print_date($factory->date_start_made, 'day');
print '</td></tr>';

// Date end planned
print '<tr><td >'.$langs->trans("FactoryDateEndPlanned").'</td>';
print '<td >';
print dol_print_date($factory->date_end_planned, 'day');
print '</td>';

// Date end made
print '<td valign=top  width=20%>'.$langs->trans("DateEndMade").'</td><td>';
print dol_print_date($factory->date_end_made, 'day');
print '</td></tr>';

// Qty planned
print '<tr><td>'.$langs->trans("QuantityPlanned").'</td>';
print '<td >';
print $factory->qty_planned;
print '</td>';

// Qty made
print '<td valign=top >'.$langs->trans("QuantityMade").'</td><td>';
print $factory->qty_made;
print '</td></tr>';
// send the good value in equipment create form
$qtyequipement = ($factory->qty_made ? $factory->qty_made : $factory->qty_planned);

// Planned workload
print '<tr><td>'.$langs->trans("FactoryDurationPlanned").'</td>';
print '<td >';
print convertSecondToTime($factory->duration_planned, 'allhourmin');
print '</td>';
print '<td>'.$langs->trans("DurationMade").'</td><td>';
print convertSecondToTime($factory->duration_made, 'allhourmin');
print '</td></tr>';


print '<tr><td>'.$langs->trans('Status').'</td><td colspan=3>'.$factory->getLibStatut(4).'</td></tr>';


// Description
print '<tr><td valign=top><table class="nobordernopadding" width="100%">';
print '<tr><td valign=top>'.$langs->trans("Description").'</td>';
print '</tr></table></td><td colspan="3">';
print $factory->description;
print '</td></tr>';

print '</table>';
print '<br>';

// tableau de description du produit
print '<table width=100% ><tr><td valign=top width=35%>';
print_fiche_titre($langs->trans("ProducttoBuild"), '', '');

print '<table class="border" width="100%">';

//$bproduit = ($object->isproduct());
print '<tr><td width="20%">'.$langs->trans("Product").'</td>';
print '<td>'.$product->getNomUrl(1)." : ".$product->label.'</td></tr>';

// TVA
print '<tr><td>'.$langs->trans("VATRate").'</td><td>'.vatrate($product->tva_tx.($product->tva_npr?'*':''),true).'</td></tr>';

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

print '<br>';


// uniquement pour les OF termin�es
if ($factory->statut == 2) {
	// bouton de cr�ation d'un �quipement � partir du produit
	print '<form name="equipement" action="../card.php" method="POST">';
	print '<table >';
	print '<tr><td >'.$langs->trans("NewEquipementOfProduct").'</td><td>';
	print '<input type=hidden name=factoryid value="'.$factoryid.'">';
	print '<input type=hidden name=productid value="'.$factory->fk_product.'">';
	print '<input type=hidden name=fk_entrepot value="'.$fk_entrepot.'">';
	// on transfert la r�f�rence de l'of en num�ro de version
	print '<input type=hidden name=numversion value="'.$refOF.'">';
	print '<input type=hidden name=qtyEquipement value="'.$qtyequipement .'">';
	print '<input type=hidden name=dateoday value="'.$factory->date_end_made.'">';
	print '<input type=hidden name=dateomonth value="'.$factory->date_end_made.'">';
	print '<input type=hidden name=dateoyear value="'.$factory->date_end_made.'">';

	$arraySerialMethod=array(
					'1'=>$langs->trans("InternalSerial"),
					'2'=>$langs->trans("ExternalSerial"),
					'3'=>$langs->trans("SeriesMode")
	);
	print $form->selectarray("serialMethod", $arraySerialMethod, $conf->global->EQUIPEMENT_DEFAULTSERIALMODE);
	print '</td><td>';
	print '<input type="hidden" name="action" value="create">';
	print '<input type="submit" class="button" value="'.$langs->trans("CreateDraftEquipement").'">';
	print '</td></tr>';
	print '</table></form><br>';
}


$sql = "SELECT";
$sql.= " e.ref, e.rowid, e.fk_statut, e.fk_product, p.ref as refproduit, e.fk_entrepot, ent.ref,";
$sql.= " e.fk_soc_fourn, sfou.nom as CompanyFourn, e.fk_facture_fourn, ff.ref as refFactureFourn,";
$sql.= " e.fk_soc_client, scli.nom as CompanyClient, e.fk_facture, f.ref as refFacture,";
$sql.= " e.datee, e.dateo, ee.libelle as etatequiplibelle, e.numversion";

$sql.= " FROM ".MAIN_DB_PREFIX."equipement as e";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_equipement_etat as ee on e.fk_etatequipement = ee.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."equipement_factory as ef on e.rowid = ef.fk_equipement";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as sfou on e.fk_soc_fourn = sfou.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."entrepot as ent on e.fk_entrepot = ent.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as scli on e.fk_soc_client = scli.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture as f on e.fk_facture = f.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture_fourn as ff on e.fk_facture_fourn = ff.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p on e.fk_product = p.rowid";
$sql.= ' WHERE e.entity IN (' . getEntity('equipement') . ')';
$sql.= " AND ef.children = 0";
$sql.= " AND e.fk_product = ".$factory->fk_product;
$sql.= " AND ef.fk_factory = ".$factoryid;

if ($search_ref)				$sql .= " AND e.ref like '%".$db->escape($search_ref)."%'";
if ($search_numversion)			$sql .= " AND e.numversion like '%".$db->escape($search_numversion)."%'";
if ($search_company_fourn)		$sql .= " AND sfou.nom like '%".$db->escape($search_company_fourn)."%'";
if ($search_reffact_fourn)		$sql .= " AND ff.ref like '%".$db->escape($search_reffact_fourn)."%'";
if ($search_entrepot)			$sql .= " AND ent.ref like '%".$db->escape($search_entrepot)."%'";
if ($search_company_client)		$sql .= " AND scli.nom like '%".$db->escape($search_company_client)."%'";
if ($search_reffact_client)		$sql .= " AND f.ref like '%".$db->escape($search_reffact_client)."%'";
if ($search_etatequipement>0)	$sql .= " AND e.fk_etatequipement =".$search_etatequipement;

$sql.= " ORDER BY ".$sortfield." ".$sortorder;
$sql.= $db->plimit($limit+1, $offset);

//print $sql;

$result=$db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);

	$equipementstatic=new Equipement($db);
	$urlparam="&amp;id=".$factoryid;
	if ($search_ref)				$urlparam .= "&amp;search_ref=".$db->escape($search_ref);
	if ($search_numversion)			$urlparam .= "&amp;search_numversion=".$db->escape($search_numversion);
	if ($search_company_fourn)		$urlparam .= "&amp;search_company_fourn=".$db->escape($search_company_fourn);
	if ($search_reffact_fourn)		$urlparam .= "&amp;search_reffact_fourn=".$db->escape($search_reffact_fourn);
	if ($search_entrepot)			$urlparam .= "&amp;search_entrepot=".$db->escape($search_entrepot);
	if ($search_company_client)		$urlparam .= "&amp;search_company_client=".$db->escape($search_company_client);
	if ($search_reffact_client)		$urlparam .= "&amp;search_reffact_client=".$db->escape($search_reffact_client);
	if ($search_etatequipement>=0)	$urlparam .= "&amp;search_etatequipement=".$search_etatequipement;
	print_barre_liste(
					$langs->trans("ListOfEquipements"), $page, "produit.php",
					$urlparam, $sortfield, $sortorder, '', $num
	);

	print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">'."\n";
	print '<input type="hidden" class="flat" name="id" value="'.$factoryid.'">';
	print '<table class="noborder" width="100%">';

	print "<tr class='liste_titre'>";
	print_liste_field_titre(
					$langs->trans("Ref"), $_SERVER["PHP_SELF"], "e.ref",
					"", $urlparam, '', $sortfield, $sortorder
	);
	print_liste_field_titre(
					$langs->trans("NumVersion"), $_SERVER["PHP_SELF"], "e.numversion",
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
					$langs->trans("RefFactClient"), $_SERVER["PHP_SELF"], "f.ref",
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
					$langs->trans("EtatEquip"), $_SERVER["PHP_SELF"], "e.fk_etatequipement",
					"", $urlparam, '', $sortfield, $sortorder
	);
	print_liste_field_titre(
					$langs->trans("Status"), $_SERVER["PHP_SELF"], "e.fk_statut",
					"", $urlparam, 'align="right"', $sortfield, $sortorder
	);
	print "</tr>\n";

	print '<tr class="liste_titre">';
	print '<td class="liste_titre"><input type="text" class="flat" name="search_ref" value="'.$search_ref.'" size="8"></td>';
	print '<td class="liste_titre"><input type="text" class="flat" name="search_numversion" value="'.$search_numversion.'" size="10"></td>';
	print '<td class="liste_titre"><input type="text" class="flat" name="search_company_fourn" value="'.$search_company_fourn.'" size="10"></td>';
	print '<td class="liste_titre"><input type="text" class="flat" name="search_reffact_fourn" value="'.$search_reffact_fourn.'" size="10"></td>';
	print '<td class="liste_titre"><input type="text" class="flat" name="search_entrepot" value="'.$search_entrepot.'" size="10"></td>';
	print '<td class="liste_titre"><input type="text" class="flat" name="search_company_client" value="'.$search_company_client.'" size="10"></td>';
	print '<td class="liste_titre"><input type="text" class="flat" name="search_reffact_client" value="'.$search_reffact_client.'" size="10"></td>';

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
	while ($i < min($num, $limit)) {
		$objp = $db->fetch_object($result);
		$var=!$var;
		print "<tr $bc[$var]>";
		print "<td>";
		$equipementstatic->fetch($objp->rowid);
		print $equipementstatic->getNomUrl(1);
		print "</td>";

		print "<td>";
			print $objp->numversion;
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

		// entrepot
		print "<td>";
		if ($objp->fk_entrepot>0) {
			$entrepotstatic = new Entrepot($db);
			$entrepotstatic ->fetch($objp->fk_entrepot);
			print $entrepotstatic  ->getNomUrl(1);
		}
		print '</td>';

		print "<td>";
		if ($objp->fk_soc_client >0) {
			$soc = new Societe($db);
			$soc->fetch($objp->fk_soc_client);
			print $soc->getNomUrl(1);
		}
		print '</td>';

		print "<td>";
		if ($objp->fk_facture) {
			$facturestatic=new Facture($db);
			$facturestatic->fetch($objp->fk_facture);
			print $facturestatic ->getNomUrl(1);
		}
		print '</td>';
		print "<td nowrap align='center'>".dol_print_date($db->jdate($objp->dateo), 'day')."</td>\n";
		print "<td nowrap align='center'>".dol_print_date($db->jdate($objp->datee), 'day')."</td>\n";
		print '<td align="right">'.($objp->etatequiplibelle ? $langs->trans($objp->etatequiplibelle):'').'</td>';
		print '<td align="right">'.$equipementstatic->LibStatut($objp->fk_statut, 5).'</td>';
		print "</tr>\n";

		$i++;
	}
	//print '<tr class="liste_total"><td colspan="7" class="liste_total">'.$langs->trans("Total").'</td>';
	//print '<td align="right" nowrap="nowrap" class="liste_total">'.$i.'</td><td>&nbsp;</td>';
	//print '</tr>';

	print '</table>';
	print "</form>\n";
	$db->free($result);
} else
	dol_print_error($db);


llxFooter();
$db->close();


/**
 *	Return list of status of equipement
 *
 *	@param  string	$selected	   Preselected type
 *	@param  string	$htmlname	   Name of field in html form
 * 	@param	int		$showempty		Add an empty field
 * 	@param	int		$hidetext		Do not show label before combo box
 * 	@param	string	$forceall		Force to show products and services in combo list, whatever are activated modules
 *  @return	void
 */
function select_equipement_etat($selected='', $htmlname='fk_etatequipement', $showempty=0, $hidetext=0)
{
	global $db, $langs, $user, $conf;

	if (empty($hidetext)) print $langs->trans("EquipementState").': ';

	// boucle sur les entrepots
	$sql = "SELECT rowid, libelle";
	$sql.= " FROM ".MAIN_DB_PREFIX."c_equipement_etat";
	$sql.= " WHERE active = 1";

	dol_syslog("Equipement.Lib::select_equipement_etat sql=".$sql);

	$resql=$db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		if ($num) {
			print '<select class="flat" name="'.$htmlname.'">';
			if ($showempty) {
				print '<option value="-1"';
				if ($selected == -1)
					print ' selected="selected"';
				print '>&nbsp;</option>';
			}
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				print '<option value="'.$obj->rowid.'"';
				if ($obj->rowid == $selected)
					print ' selected="selected"';
				print ">".$langs->trans($obj->libelle)."</option>";
				$i++;
			}
			print '</select>';
		} else {
			// si pas de liste, on positionne un hidden � vide
			print '<input type="hidden" name="'.$htmlname.'" value=-1>';
		}
	}
}
