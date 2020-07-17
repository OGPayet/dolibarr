<?php
/* Copyright (C) 2013-2017	Charlene Benke	<charlie@patas-monkey.com>
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
require_once DOL_DOCUMENT_ROOT."/core/lib/date.lib.php";

require_once DOL_DOCUMENT_ROOT."/core/lib/fichinter.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php";

dol_include_once('/equipement/class/equipement.class.php');
dol_include_once('/equipement/core/lib/equipement.lib.php');

$langs->load("companies");
$langs->load("equipement@equipement");
$langs->load("interventions");

$id=GETPOST('id', 'int');

// Security check
if ($user->societe_id)
	$socid=$user->societe_id;

$result = restrictedArea($user, 'ficheinter', $id, 'fichinter');

$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST('page', 'int');
if ($page == -1)
	$page = 0;

$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="e.datec";

$search_ref				= GETPOST('search_ref', 'alpha');
$search_refProduct		= GETPOST('search_refProduct', 'alpha');
$search_company_fourn	= GETPOST('search_company_fourn', 'alpha');
$search_soc_client		= GETPOST('search_soc_client', 'alpha');
$search_entrepot		= GETPOST('search_entrepot', 'alpha');
$search_equipevttype	= GETPOST('search_equipevttype', 'alpha');
if ($search_equipevttype == "-1")
	$search_equipevttype="";

/*
 *	View
 */

$form = new Form($db);
llxHeader();


$object = new Fichinter($db);
$result = $object->fetch($id);
$object->fetch_thirdparty();

$head = fichinter_prepare_head($object);

dol_fiche_head($head, 'eventadd', $langs->trans("InterventionCard"), 0, 'intervention');

print '<table class="border" width="100%">';

// Ref
print '<tr><td width="25%">'.$langs->trans("Ref").'</td><td>';
print $form->showrefnav($object, 'ref', '', 1, 'ref', 'ref');
print '</td></tr>';

// Societe
print "<tr><td>".$langs->trans("Company")."</td><td>".$object->thirdparty->getNomUrl(1)."</td></tr>";
print "</table><br>";


$sql = "SELECT";
$sql.= " e.ref, e.rowid, e.fk_statut, e.fk_product, p.ref as refproduit, e.fk_entrepot, ";
$sql.= " ent.label, e.fk_soc_fourn, sfou.nom as CompanyFourn,";
$sql.= " e.fk_etatequipement, et.libelle as etatequiplibelle";

$sql.= " FROM ".MAIN_DB_PREFIX."equipement as e";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_equipement_etat as et on e.fk_etatequipement = et.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as sfou on e.fk_soc_fourn = sfou.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."entrepot as ent on e.fk_entrepot = ent.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p on e.fk_product = p.rowid";

$sql.= ' WHERE IN (' . getEntity('equipement') . ')';


// on n'affiche que les �quipement associ� au client
if ($search_soc_client)
	$sql .= " and e.fk_soc_client = ".$search_soc_client;
else {
	$search_soc_client=$object->socid;
	$sql.= " and e.fk_soc_client = ".$object->socid;
}

if ($search_ref)			$sql .= " AND e.ref like '%".$db->escape($search_ref)."%'";
if ($search_refProduct)		$sql .= " AND p.ref like '%".$db->escape($search_refProduct)."%'";
if ($search_company_fourn)	$sql .= " AND sfou.nom like '%".$db->escape($search_company_fourn)."%'";
if ($search_entrepot)		$sql .= " AND ent.label like '%".$db->escape($search_entrepot)."%'";
if ($search_etatequipement)	$sql .= " AND e.fk_etatequipement =".$search_etatequipement;

$sql.= " ORDER BY ".$sortfield." ".$sortorder;

$result=$db->query($sql);
if ($result) {
	$num = $db->num_rows($result);

	$equipementstatic=new Equipement($db);

	$urlparam="&amp;id=".$id;

	print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">'."\n";
	print '<input type="hidden" class="flat" name="id" value="'.$id.'">';
	print '<table class="noborder" width="100%">';

	print "<tr class='liste_titre'>";
	print '<td width=15px class="liste_titre">&nbsp;</td>';
	print '<td  class="liste_titre">'.$langs->trans("OtherAssociatedClient").'</td>';
	print '<td  class="liste_titre">';
	print $form->select_company($search_soc_client, 'search_soc_client', '', 1 );
	print '</td><td colspan=7 class="liste_titre"></td>';
	print "</tr>";

	print "<tr class='liste_titre'>";
	print '<td width=15px class="liste_titre">&nbsp;</td>';
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
					$langs->trans("Entrepot"), $_SERVER["PHP_SELF"], "ent.label",
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
	print '<td class="liste_titre" ></td>';
	print "</tr>\n";

	print '<tr class="liste_titre">';
	print '<td class="liste_titre" align="right"></td>';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_ref" value="'.$search_ref.'" size="8"></td>';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_refProduct" value="'.$search_refProduct.'" size="8"></td>';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_company_fourn" value="'.$search_company_fourn.'" size="10">';
	print '</td><td class="liste_titre">';
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

	// liste des �tat des �quipements
	print '<td class="liste_titre" align="right">';
	print select_equipement_etat($search_etatequipement, 'search_etatequipement', 1, 1);
	print '</td>';
	print '<td class="liste_titre" align="right">';
	print '<input class="liste_titre" type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png"';
	print ' value="'.dol_escape_htmltag($langs->trans("Search")).'"';
	print ' title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '</td>';
	print "</tr>\n";

	$var=True;
	$total = 0;
	$i = 0;
	while ($i < $num) {
		$objp = $db->fetch_object($result);
		$var=!$var;
		print "<tr $bc[$var]>";

		// ici la case � cocher de s�lection pour ajouter un �v�nement sur l'�quipement
		print "<td width=15px>";
		print '<input type=checkbox value=1 name="chk'.$objp->rowid.'">';
		print "</td>";
		print "<td>";
		$equipementstatic->id=$objp->rowid;
		$equipementstatic->ref=$objp->ref;
		print $equipementstatic->getNomUrl(1);
		print "</td>";

		// si la case � coch� �tait coch�, on cr�e l'�v�nement
		if (GETPOST('chk'.$objp->rowid)==1) {
			$ret=$equipementstatic->fetch($objp->rowid);
			$equipementstatic->fetch_thirdparty();

			$desc=GETPOST('np_desc','alpha');
			$dateo = dol_mktime(
							GETPOST('deohour', 'int'), GETPOST('deomin', 'int'), 0,
							GETPOST('deomonth', 'int'), GETPOST('deoday', 'int'), GETPOST('deoyear', 'int')
			);
			$datee = dol_mktime(
							GETPOST('deehour', 'int'), GETPOST('deemin', 'int'), 0,
							GETPOST('deemonth', 'int'), GETPOST('deeday', 'int'), GETPOST('deeyear', 'int')
			);
			$fulldayevent =GETPOST('fulldayevent');
			$fk_equipementevt_type =GETPOST('fk_equipementevt_type');
			$fk_contrat =GETPOST('fk_contrat');
			$fk_fichinter = $id;
			$fk_expedition =GETPOST('fk_expedition');
			$fk_project =GETPOST('fk_project');
			$fk_user_author= $user->id;
			$total_ht =GETPOST('total_ht');

			$resultAdd=$equipementstatic->addline(
							$objp->rowid,
							$fk_equipementevt_type,
							$desc,
							$dateo,
							$datee,
							$fulldayevent,
							$fk_contrat,
							$fk_fichinter,
							$fk_expedition,
							$fk_project,
							$fk_user_author,
							$total_ht
			);
		}

		print '<td>';
		if ($objp->fk_product > 0) {
			$productstatic=new Product($db);
			$productstatic->fetch($objp->fk_product);
			print $productstatic->getNomUrl(1);
		}
		print '</td>';

		print "<td>";
		if ($objp->fk_soc_fourn > 0) {
			$soc = new Societe($db);
			$soc->fetch($objp->fk_soc_fourn);
			print $soc->getNomUrl(1);
		}
		print '</td>';

		// entrepot
		print "<td>";
		if ($objp->fk_entrepot > 0) {
			$entrepotstatic = new Entrepot($db);
			$entrepotstatic->fetch($objp->fk_entrepot);
			print $entrepotstatic->getNomUrl(1);
		}
		print '</td>';

		print "<td nowrap align='center'>".dol_print_date($db->jdate($objp->dateo), 'day')."</td>\n";
		print "<td nowrap align='center'>".dol_print_date($db->jdate($objp->datee), 'day')."</td>\n";
		print '<td align="right">'.($objp->etatequiplibelle ? $langs->trans($objp->etatequiplibelle):'').'</td>';
		print '<td align="right"></td>';
		print "</tr>\n";
		$i++;
	}

	print '</table>';
	print '<br><br>';

	// on permet d'ajouter ssi il y a des �quipements ajoutable...
	if ($num > 0 ) {
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td colspan=2 width=180px align="left">'.$langs->trans('Description').'</td>'; // ancre
		print '<td width=120px align="center">'.$langs->trans('Dateo').'</td>';
		print '<td width=120px align="center" >'.$langs->trans('Datee').'</td>';
		print '<td align="left" colspan=2>'.$langs->trans('AssociatedWith').'</td>';
		print '<td colspan=2 align="right">'.$langs->trans('EquipementLineTotalHT').'</td>';
		print "</tr>\n";

		print '<tr '.$bc[$var].">\n";
		print '<td width=100px>'.$langs->trans('TypeofEquipementEvent').'</td><td>';
		print select_equipementevt_type('', 'fk_equipementevt_type', 1, 1);
		// type d'�v�nement
		print '</td>';

		// Date evenement d�but
		print '<td align="center" rowspan=2>';
		$timearray=dol_getdate(mktime());
		if (!GETPOST('deoday','int'))
			$timewithnohour = dol_mktime(0, 0, 0, $timearray['mon'], $timearray['mday'], $timearray['year']);
		else
			$timewithnohour = dol_mktime(
							GETPOST('deohour', 'int'), GETPOST('deomin', 'int'), 0,
							GETPOST('deomonth', 'int'), GETPOST('deoday', 'int'), GETPOST('deoyear', 'int')
			);
		$form->select_date($timewithnohour, 'deo', 1, 1, 0, "addequipevt");
		print '</td>';
		// Date evenement fin
		print '<td align="center" rowspan=2>';
		$timearray=dol_getdate(mktime());
		if (!GETPOST('deeday','int'))
			$timewithnohour = dol_mktime(0, 0, 0, $timearray['mon'], $timearray['mday'], $timearray['year']);
		else
			$timewithnohour = dol_mktime(
							GETPOST('deehour', 'int'), GETPOST('deemin', 'int'), 0,
							GETPOST('deemonth', 'int'), GETPOST('deeday', 'int'), GETPOST('deeyear', 'int')
			);
		$form->select_date($timewithnohour, 'dee', 1, 1, 0, "addequipevt");
		print '</td>';

		print '<td align="left">';
		print $langs->trans("Contrats");
		print '</td>';
		print '<td align="left">';
		print select_contracts('', $object->fk_soc_client, 'fk_contrat', 1, 1);
		print '</td>';

		print '<td align="center" valign="middle" >';
		print '<input type="text" name="total_ht" size="5" value="">';
		print '</td></tr>';

		print '<tr '.$bc[$var].">\n";
				// description de l'�v�nement de l'�quipement
		print '<td rowspan=2 colspan=2>';
		// editeur wysiwyg
		require_once(DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php");
		$doleditor=new DolEditor(
						'np_desc', GETPOST('np_desc', 'alpha'), '', 100,
						'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_DETAILS,ROWS_3, 60
		);
		$doleditor->Create();
		print '</td>';
		//
		print '<td align="left">';
		print $langs->trans("Expeditions");
		print '</td>';
		print '<td align="left">';
		print select_expeditions('', $object->fk_soc_client, 'fk_expedition', 1, 1);
		print '</td>';

		print '<td align="center" rowspan=2>';
		print '<input type="submit" class="button" value="'.$langs->trans('Add').'" name="addline">';
		print '</td>';
		print '</tr>';

		// fullday event
		print '<tr '.$bc[$var].">\n";
		print '<td align="center" colspan=2>';
		print '<input type="checkbox" id="fulldayevent" value=1 name="fulldayevent" >';
		print "&nbsp;".$langs->trans("EventOnFullDay");
		print '</td>';

		print '<td align="left">';
		print $langs->trans("Project");
		print '</td>';
		print '<td align="left">';
		print select_projects('', $object->fk_soc_client, 'fk_project', 1, 1);
		print '</td>';

		print '</tr>';
		print '</table>';
	}
	else
		print $langs->trans("NoEquipementLink");
	print "</form>\n";
	$db->free($result);
}
else
	dol_print_error($db);

llxFooter();
$db->close();