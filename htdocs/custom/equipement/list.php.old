<?php
/* Copyright (C) 2012-2017		Charlene Benke	<charlie@patas-monkey.com>
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
 *	\file	   htdocs/equipement/list.php
 *	\brief	  List of all equipement
 *	\ingroup	equipement
 */
$res=@include("../main.inc.php");					// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../main.inc.php");		// For "custom" directory

require_once DOL_DOCUMENT_ROOT."/product/class/product.class.php";
require_once DOL_DOCUMENT_ROOT."/product/stock/class/entrepot.class.php";
require_once DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php";
require_once DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.commande.class.php";
require_once DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.facture.class.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/date.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/class/html.form.class.php";

dol_include_once('/equipement/class/equipement.class.php');
dol_include_once('/equipement/core/lib/equipement.lib.php');

$langs->load("companies");
$langs->load("equipement@equipement");

$productid=GETPOST('productid', 'int');


// Security check
$equipementid = GETPOST('id', 'int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'equipement', $equipementid, 'equipement');

$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST('page', 'int');
if ($page == -1)
	$page = 0;

$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;


// Initialize technical object to manage context to save list fields
$contextpage='equipementlist';


if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="e.ref";


$sall=GETPOST('sall', 'alpha');
$search_ref=GETPOST('search_ref', 'alpha');
$search_refProduct=GETPOST('search_refProduct', 'alpha');
$search_labelProduct=GETPOST('search_labelProduct', 'alpha');
$search_numversion=GETPOST('search_numversion', 'alpha');
$search_company_fourn=GETPOST('search_company_fourn', 'alpha');
$search_reffact_fourn=GETPOST('search_reffact_fourn', 'alpha');
$search_reforder_fourn=GETPOST('search_reforder_fourn', 'alpha');
$search_company_client=GETPOST('search_company_client', 'alpha');
$search_reffact_client=GETPOST('search_reffact_client', 'alpha');
$search_note_private=GETPOST('search_note_private', 'alpha');
$search_entrepot=GETPOST('search_entrepot', 'alpha');
if ($search_entrepot=="") $search_entrepot="-1";
$search_etatequipement=GETPOST('search_etatequipement', 'alpha');
if ($search_etatequipement=="") $search_etatequipement="-1";
$viewstatut=GETPOST('viewstatut', 'alpha');
if ($viewstatut=="") $viewstatut="-1";

$equipementstatic=new Equipement($db);

/*
 *	Action
 */

// Selection of new fields
include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

// Purge search criteria
if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) // All tests are required to be compatible with all browsers
{
    $sall='';
    $search_ref='';
    $search_refProduct='';
    $search_labelProduct='';
    $search_numversion='';
    $search_company_fourn='';
    $search_reffact_fourn='';
    $search_entrepot=-1;
    $search_company_client='';
    $search_etatequipement=-1;
    $viewstatut=-1;
}

if (GETPOST("updatecheck") == $langs->trans("Update")) {

	$separatorlist=$conf->global->EQUIPEMENT_SEPARATORLIST;
	if (!isset($separatorlist)) // Si non saisie on utilise le ; par d�faut
		$separatorlist=";";
	if ($separatorlist == "__N__")
		$separatorlist="\n";
	if ($separatorlist == "__B__")
		$separatorlist="\b";

	$idlist=explode($separatorlist, GETPOST("idlist"));

	foreach ($idlist as $key) {
		if (GETPOST(trim("chk-".$key))) {

			// on r�cup�re les anciennes valeurs
			$equipementstatic->fetch($key);
			// on met � jours que si la case est coch�e

			if (GETPOST("chk_statut"))
				$equipementstatic->fk_statut = GETPOST("update_statut");
			else
				$equipementstatic->fk_statut = -1;

			if (GETPOST("chk_etatequipement"))
				$equipementstatic->fk_etatequipement = GETPOST("update_etatequipement");
			else
				$equipementstatic->fk_etatequipement = 0;

			if (GETPOST("chk_soc_client"))
				$equipementstatic->fk_soc_client = GETPOST("update_soc_client");
			else
				$equipementstatic->fk_soc_client = 0;

			if (GETPOST("chk_entrepot"))
				$equipementstatic->fk_etatentrepot = GETPOST("update_entrepot");
			else
				$equipementstatic->fk_etatentrepot = 0;

			if (GETPOST("chk_datee")) {
				if (GETPOST("datee"))
					$equipementstatic->datee = dol_mktime(
									'23', '59', '59',
									GETPOST("dateemonth"), GETPOST("dateeday"), GETPOST("dateeyear")
					);
				else
					$equipementstatic->datee = -1;
			} else
				$equipementstatic->fk_datee = 0;

			if (GETPOST("chk_dateo")) {
				if (GETPOST("dateo"))
					$equipementstatic->dateo = dol_mktime(
									'23', '59', '59',
									GETPOST("dateomonth"), GETPOST("dateoday"), GETPOST("dateoyear")
					);
				else
					$equipementstatic->dateo = -1;
			} else
				$equipementstatic->fk_dateo = 0;


//var_dump($equipementstatic);
			// on met � jour l'�quipement
			$equipementstatic->updateInfos($user, GETPOST("update_entrepotmove"));
		}
	}
}

$fieldstosearchall = array(
    'e.ref'=>'Ref',
      'p.ref'=>'RefProduct',
      'p.label'=>'ProductDescription',
      'sfou.nom'=>"SupplierName",
      'scli.nom'=>'CustomerName',
      'f.facnumber'=>'CustomerInvoiceRef',
      'ff.ref'=>'SupplierInvoiceRef',
      'e.datec'=>'DateCreate',
      'e.dateo'=>'DateOpen',
      'e.datee'=>'DateClose',
      'e.dated'=>'DateDluo',
);
if (empty($user->socid)) $fieldstosearchall["e.note_private"]="NotePrivate";


// construction de la structure des champs
$arrayfields=array(
	'e.ref'=>array('label'=>$langs->trans("Ref"), 'checked'=>1),
	'e.description'=>array('label'=>$langs->trans("Description"), 'checked'=>0),

	'p.ref'=>array('label'=>$langs->trans("RepProduct"), 'checked'=>1),
	'p.label'=>array('label'=>$langs->trans("LabelProduct"), 'checked'=>0, ),

	'e.quantity'=>array('label'=>$langs->trans("Qty"), 'checked'=>0),
	'e.unitweight'=>array('label'=>$langs->trans("UnitWeight"), 'checked'=>0),
	'e.numversion'=>array('label'=>$langs->trans("VersionName"), 'checked'=>0, 'position'=>500),


	'ent.label'=>array('label'=>$langs->trans("Warehouse"), 'checked'=>1),

	'sfou.nom'=>array('label'=>$langs->trans("SupplierName"), 'checked'=>0),
  'cf.ref'=>array('label'=>$langs->trans("SupplierOrderRef"), 'checked'=>1),
  'ff.ref'=>array('label'=>$langs->trans("SupplierInvoiceRef"), 'checked'=>1),

	'scli.nom'=>array('label'=>$langs->trans("CustomerName"), 'checked'=>0),
	'f.facnumber'=>array('label'=>$langs->trans("FacNumber"), 'checked'=>1),

	'e.datec'=>array('label'=>$langs->trans("DateCreate"), 'checked'=>1, 'position'=>500),
	'e.dateo'=>array('label'=>$langs->trans("DateOpen"), 'checked'=>0, 'position'=>500),
	'e.datee'=>array('label'=>$langs->trans("DateEnd"), 'checked'=>0, 'position'=>500),
	'e.dated'=>array('label'=>$langs->trans("Dated"), 'checked'=>0, 'position'=>500),
	'ee.libelle'=>array('label'=>$langs->trans("EquipementStatut"), 'checked'=>0, 'position'=>500),

  'e.note_private'=>array('label'=>$langs->trans("NotePrivate"), 'checked'=>0, 'position'=>500),

	'e.tms'=>array('label'=>$langs->trans("DateModificationShort"), 'checked'=>0, 'position'=>500),
	'e.fk_statut'=>array('label'=>$langs->trans("Status"), 'checked'=>1, 'position'=>1000),
);

// Extra fields
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) {
	foreach($extrafields->attribute_label as $key => $val)
		$arrayfields["ef.".$key]=array(
						'label'=>$extrafields->attribute_label[$key],
						'checked'=>$extrafields->attribute_list[$key],
						'position'=>$extrafields->attribute_pos[$key],
						'enabled'=>$extrafields->attribute_perms[$key]
		);
}

/*
 *	View
 */

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';


llxHeader();

$sql = "SELECT";
$sql.= " e.ref, e.rowid as equipementid, e.fk_statut, e.fk_product, p.ref as refproduit, p.label as labelproduit,";
$sql.= " e.fk_entrepot, e.quantity, e.tms, e.numversion, e.description, e.note_private,";
$sql.= " e.fk_soc_fourn, sfou.nom as CompanyFourn, e.fk_commande_fourn, cf.ref as refCommFourn, e.fk_facture_fourn, ff.ref as refFactureFourn,";
$sql.= " e.fk_soc_client, scli.nom as CompanyClient, e.fk_facture, f.facnumber as refFacture,";
$sql.= " e.datec, e.datee, e.dateo, e.dated, ee.libelle as etatequiplibelle, ef.* ";

$sql.= " FROM ".MAIN_DB_PREFIX."equipement as e";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."equipement_extrafields as ef on e.rowid = ef.fk_object";

$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_equipement_etat as ee on e.fk_etatequipement = ee.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as sfou on e.fk_soc_fourn = sfou.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."entrepot as ent on e.fk_entrepot = ent.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as scli on e.fk_soc_client = scli.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture as f on e.fk_facture = f.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."commande_fournisseur as cf on e.fk_commande_fourn = cf.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture_fourn as ff on e.fk_facture_fourn = ff.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p on e.fk_product = p.rowid";
$sql.= " WHERE e.entity = ".$conf->entity;

if ($sall) {
    $sql .= natural_search(array_keys($fieldstosearchall), $sall);
} else {
	if ($search_ref)				$sql .= " AND e.ref like '%".$db->escape($search_ref)."%'";
	if ($search_labelProduct)		$sql .= " AND p.label like '%".$db->escape($search_labelProduct)."%'";
	if ($search_refProduct)			$sql .= " AND p.ref like '%".$db->escape($search_refProduct)."%'";
	if ($search_numversion)			$sql .= " AND e.numversion like '%".$db->escape($search_numversion)."%'";
	if ($search_company_fourn)		$sql .= " AND sfou.nom like '%".$db->escape($search_company_fourn)."%'";
	if ($search_reffact_fourn)		$sql .= " AND ff.ref like '%".$db->escape($search_reffact_fourn)."%'";
	if ($search_reforder_fourn)		$sql .= " AND cf.ref like '%".$db->escape($search_reforder_fourn)."%'";
	if ($search_company_client)		$sql .= " AND scli.nom like '%".$db->escape($search_company_client)."%'";
  if ($search_reffact_client)		$sql .= " AND f.facnumber like '%".$db->escape($search_reffact_client)."%'";
  if ($search_note_private)		$sql .= " AND e.note_private like '%".$db->escape($search_note_private)."%'";
}
if ($search_entrepot >=0)		$sql .= " AND ent.rowid =".$search_entrepot;
if ($search_etatequipement>=0)	$sql .= " AND e.fk_etatequipement =".$search_etatequipement;
if ($viewstatut >=0)			$sql .= " AND e.fk_statut =".$viewstatut;
$sql.= $db->order($sortfield,$sortorder);;
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
    $result = $db->query($sql);
    $nbtotalofrecords = $db->num_rows($result);
}
$sql.= $db->plimit($limit+1, $offset);

//print $sql;
$resql=$db->query($sql);

$form = new Form($db);


if ($resql) {
	$num = $db->num_rows($resql);

	$urlparam='';
	if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $urlparam .='&contextpage='.urlencode($contextpage);
    if ($limit > 0 && $limit != $conf->liste_limit) $urlparam.='&limit='.urlencode($limit);
	if ($sall)						$urlparam .= "&sall=".$db->escape($sall);
	if ($search_ref)				$urlparam .= "&search_ref=".$db->escape($search_ref);
	if ($search_refProduct)			$urlparam .= "&search_refProduct=".$db->escape($search_refProduct);
	if ($search_labelProduct)		$urlparam .= "&search_labelProduct=".$db->escape($search_labelProduct);
	if ($search_numversion)			$urlparam .= "&search_numversion=".$db->escape($search_numversion);
	if ($search_company_fourn)		$urlparam .= "&search_company_fourn=".$db->escape($search_company_fourn);
	if ($search_reffact_fourn)		$urlparam .= "&search_reffact_fourn=".$db->escape($search_reffact_fourn);
	if ($search_reforder_fourn)		$urlparam .= "&search_reforder_fourn=".$db->escape($search_reforder_fourn);
	if ($search_entrepot)			$urlparam .= "&search_entrepot=".$search_entrepot;
	if ($search_company_client)		$urlparam .= "&search_company_client=".$db->escape($search_company_client);
	if ($search_reffact_client)		$urlparam .= "&search_reffact_client=".$db->escape($search_reffact_client);
	if ($search_note_private)		$urlparam .= "&search_note_private=".$db->escape($search_note_private);
	if ($search_etatequipement>=0)	$urlparam .= "&search_etatequipement=".$search_etatequipement;
	if ($viewstatut >=0)			$urlparam .= "&viewstatut=".$viewstatut;

	print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" name="formulaire">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'" />';
    print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list" />';
    print '<input type="hidden" name="action" value="list" />';
    print '<input type="hidden" name="sortfield" value="'.$sortfield.'" />';
    print '<input type="hidden" name="sortorder" value="'.$sortorder.'" />';
    print '<input type="hidden" name="page" value="'.$page.'" />';

    print_barre_liste($langs->trans("ListOfEquipements"), $page, $_SERVER['PHP_SELF'], $urlparam, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'title_generic.png', 0, '', '', $limit);

	$varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;

  if ($sall)
  {
     foreach($fieldstosearchall as $key => $val) $fieldstosearchall[$key]=$langs->trans($val);
     print $langs->trans("FilterOnInto", $sall) . join(', ',$fieldstosearchall);
  }

	// This also change content of $arrayfields
	$selectedfields=$form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

	print '<tr class="liste_titre">';


	if (! empty($arrayfields['e.ref']['checked'])) {
		print_liste_field_titre(
						$arrayfields['e.ref']['label'], $_SERVER["PHP_SELF"], "e.ref", "",
						$urlparam, '', $sortfield, $sortorder
		);
	}
	if (! empty($arrayfields['e.description']['checked'])) {
		print_liste_field_titre(
						$arrayfields['e.description']['label'], $_SERVER["PHP_SELF"], "e.description", "",
						$urlparam, '', $sortfield, $sortorder
		);
	}

	if (! empty($arrayfields['p.ref']['checked'])) {
		print_liste_field_titre(
						$arrayfields['p.ref']['label'], $_SERVER["PHP_SELF"], "p.ref", "",
						$urlparam, '', $sortfield, $sortorder
		);
	}
	if (! empty($arrayfields['p.label']['checked'])) {
		print_liste_field_titre(
						$arrayfields['p.label']['label'], $_SERVER["PHP_SELF"], "p.label", "",
						$urlparam, '', $sortfield, $sortorder
		);
	}
	if (! empty($arrayfields['e.quantity']['checked'])) {
		print_liste_field_titre(
						$arrayfields['e.quantity']['label'], $_SERVER["PHP_SELF"], "e.quantity", "",
						$urlparam, '', $sortfield, $sortorder
		);
	}
	if (! empty($arrayfields['e.unitweight']['checked'])) {
		print_liste_field_titre(
						$arrayfields['e.unitweight']['label'], $_SERVER["PHP_SELF"], "e.unitweight", "",
						$urlparam, '', $sortfield, $sortorder
		);
	}
	if (! empty($arrayfields['e.numversion']['checked'])) {
		print_liste_field_titre(
						$arrayfields['e.numversion']['label'], $_SERVER["PHP_SELF"], "e.numversion", "",
						$urlparam, '', $sortfield, $sortorder
		);
	}
	if (! empty($arrayfields['ent.label']['checked'])) {
		print_liste_field_titre(
						$arrayfields['ent.label']['label'], $_SERVER["PHP_SELF"], "ent.label", "",
						$urlparam, '', $sortfield, $sortorder
		);
	}
	if (! empty($arrayfields['sfou.nom']['checked'])) {
		print_liste_field_titre(
						$arrayfields['sfou.nom']['label'], $_SERVER["PHP_SELF"], "sfou.nom", "",
						$urlparam, '', $sortfield, $sortorder
		);
	}
	if (! empty($arrayfields['cf.ref']['checked'])) {
		print_liste_field_titre(
						$arrayfields['cf.ref']['label'], $_SERVER["PHP_SELF"], "cf.ref", "",
						$urlparam, '', $sortfield, $sortorder
		);
	}
  if (! empty($arrayfields['ff.ref']['checked'])) {
    print_liste_field_titre(
            $arrayfields['ff.ref']['label'], $_SERVER["PHP_SELF"], "ff.ref", "",
            $urlparam, '', $sortfield, $sortorder
    );
  }
	if (! empty($arrayfields['scli.nom']['checked'])) {
		print_liste_field_titre(
						$arrayfields['scli.nom']['label'], $_SERVER["PHP_SELF"], "scli.nom", "",
						$urlparam, '', $sortfield, $sortorder
		);
	}
	if (! empty($arrayfields['f.facnumber']['checked'])) {
		print_liste_field_titre(
						$arrayfields['f.facnumber']['label'], $_SERVER["PHP_SELF"], "f.facnumber", "",
						$urlparam, '', $sortfield, $sortorder
		);
	}
	if (! empty($arrayfields['e.datec']['checked'])) {
		print_liste_field_titre(
						$arrayfields['e.datec']['label'], $_SERVER["PHP_SELF"], "e.datec", "",
						$urlparam, '', $sortfield, $sortorder
		);
	}
	if (! empty($arrayfields['e.dateo']['checked'])) {
		print_liste_field_titre(
						$arrayfields['e.dateo']['label'], $_SERVER["PHP_SELF"], "e.dateo", "",
						$urlparam, '', $sortfield, $sortorder
		);
	}
	if (! empty($arrayfields['e.datee']['checked'])) {
		print_liste_field_titre(
						$arrayfields['e.datee']['label'], $_SERVER["PHP_SELF"], "e.datee", "",
						$urlparam, '', $sortfield, $sortorder
		);
	}
	if (! empty($arrayfields['e.dated']['checked']) && $conf->global->EQUIPEMENT_USEDLUODATE == 1) {
		print_liste_field_titre(
						$arrayfields['e.dated']['label'], $_SERVER["PHP_SELF"], "e.dated", "",
						$urlparam, '', $sortfield, $sortorder
		);
	}
	if (! empty($arrayfields['ee.libelle']['checked'])) {
		print_liste_field_titre(
						$arrayfields['ee.libelle']['label'], $_SERVER["PHP_SELF"], "ee.libelle", "",
						$urlparam, '', $sortfield, $sortorder
		);
	}
  if (! empty($arrayfields['e.note_private']['checked'])) {
    print_liste_field_titre(
            $arrayfields['e.note_private']['label'], $_SERVER["PHP_SELF"], "e.note_private", "",
            $urlparam, '', $sortfield, $sortorder
    );
  }
	if (! empty($arrayfields['e.tms']['checked'])) {
		print_liste_field_titre(
						$arrayfields['e.tms']['label'], $_SERVER["PHP_SELF"], "e.tms", "",
						$urlparam, '', $sortfield, $sortorder
		);
	}
	if (! empty($arrayfields['e.fk_statut']['checked'])) {
		print_liste_field_titre(
						$arrayfields['e.fk_statut']['label'], $_SERVER["PHP_SELF"], "e.fk_statut", "",
						$urlparam, 'align="right"', $sortfield, $sortorder
		);
	}

	// Extra fields
	if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) {
		foreach ($extrafields->attribute_label as $key => $val) {
			if (! empty($arrayfields["ef.".$key]['checked'])) {
				$align=$extrafields->getAlignFlag($key);
				print_liste_field_titre(
								$extralabels[$key], $_SERVER["PHP_SELF"], "ef.".$key, "", $param,
								($align?'align="'.$align.'"':''), $sortfield, $sortorder
				);
			}
		}
	}

	print_liste_field_titre(
					$selectedfields, $_SERVER["PHP_SELF"], "", '', '',
					'align="right"', $sortfield, $sortorder, 'maxwidthsearch '
	);

	print "</tr>\n";

	// Filters lines � placer plus haut dans un truc qui se replie
	print '<tr class="liste_titre">';

	if (! empty($arrayfields['e.ref']['checked'])) {
		print '<td class="liste_titre" valign=top>';
		print '<input type="text" class="flat" name="search_ref" value="'.$search_ref.'" size="8"></td>';
	}
	if (! empty($arrayfields['e.description']['checked']))
		print '<td class="liste_titre" valign=top></td>';

	if (! empty($arrayfields['p.ref']['checked'])) {
		print '<td class="liste_titre" valign=top>';
		print '<input type="text" class="flat" name="search_refProduct" value="'.$search_refProduct.'" size="8">';
		print '</td>';
	}
	if (! empty($arrayfields['p.label']['checked'])) {
		print '<td class="liste_titre" valign=top>';
		print '<input type="text" class="flat" name="search_labelProduct" value="'.$search_labelProduct.'" size="10">';
		print '</td>';
	}

	if (! empty($arrayfields['e.quantity']['checked']))
		print '<td class="liste_titre" valign=top></td>';

	if (! empty($arrayfields['e.unitweight']['checked']))
		print '<td class="liste_titre" valign=top></td>';

	if (! empty($arrayfields['e.numversion']['checked'])) {
		print '<td class="liste_titre" valign=top>';
		print '<input type="text" class="flat" name="search_numversion" value="'.$search_numversion.'" size="8"></td>';
	}

	if (! empty($arrayfields['ent.label']['checked'])) {
		print '<td class="liste_titre" valign=top>';
		select_entrepot($search_entrepot, 'search_entrepot', 1, 1, 0, 0);
		print '</td>';
	}

	if (! empty($arrayfields['sfou.nom']['checked'])) {
		print '<td class="liste_titre" valign=top>';
		print '<input type="text" class="flat" name="search_company_fourn" value="'.$search_company_fourn.'" size="10">';
		print '</td>';
	}
	if (! empty($arrayfields['cf.ref']['checked'])) {
		print '<td class="liste_titre" valign=top>';
		print '<input type="text" class="flat" name="search_reforder_fourn" value="'.$search_reforder_fourn.'" size="8">';
		print '</td>';
	}
  if (! empty($arrayfields['ff.ref']['checked'])) {
    print '<td class="liste_titre" valign=top>';
    print '<input type="text" class="flat" name="search_reffact_fourn" value="'.$search_reffact_fourn.'" size="8">';
    print '</td>';
  }

	if (! empty($arrayfields['scli.nom']['checked'])) {
		print '<td class="liste_titre" valign=top>';
		print '<input type="text" class="flat" name="search_company_client" value="'.$search_company_client.'" size="8">';
		print '</td>';
	}
	if (! empty($arrayfields['f.facnumber']['checked'])) {
		print '<td class="liste_titre" valign=top>';
		print '<input type="text" class="flat" name="search_facnumber" value="'.$search_facnumber.'" size="8">&nbsp;';
		print '</td>';
	}

	if (! empty($arrayfields['e.datec']['checked'])) {
		print '<td class="liste_titre" valign=top>';
		print '<input class="flat" type="text" size="3" maxlength="8" name="search_datec" value="'.$search_datec.'">';
		print '</td>';
	}
	if (! empty($arrayfields['e.dateo']['checked'])) {
		print '<td class="liste_titre" valign=top>';
		print '<input class="flat" type="text" size="3" maxlength="8" name="search_dateo" value="'.$search_dateo.'">';
		print '</td>';
	}
	if (! empty($arrayfields['e.datee']['checked'])) {
		print '<td class="liste_titre" valign=top>';
		print '<input class="flat" type="text" size="3" maxlength="8" name="search_datee" value="'.$search_datee.'">';
		print '</td>';
	}
	if (! empty($arrayfields['e.dated']['checked']) && $conf->global->EQUIPEMENT_USEDLUODATE == 1) {
		print '<td class="liste_titre" valign=top>';
		print '<input class="flat" type="text" size="3" maxlength="8" name="search_dated" value="'.$search_dated.'">';
		print '</td>';
	}

	if (! empty($arrayfields['ee.libelle']['checked'])) {
		print '<td class="liste_titre" valign=top>';
		print select_equipement_etat($search_etatequipement, 'search_etatequipement', 1, 1);
		print '</td>';
	}

  if (! empty($arrayfields['e.note_private']['checked'])) {
    print '<td class="liste_titre" valign=top>';
    print '<input class="flat" type="text" size="10" name="search_note_private" value="'.$search_note_private.'">';
    print '</td>';
  }

	if (! empty($arrayfields['e.tms']['checked']))
		print '<td class="liste_titre" align="right"></td>';

	if (! empty($arrayfields['e.fk_statut']['checked'])) {
		print '<td class="liste_titre" align="right" valign="top">';
		print '<select class="flat" name="viewstatut">';
		print '<option value="-1">&nbsp;</option>';
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
		print '</td>';
	}

	// Action column
	print '<td class="liste_titre" align="center">';

	$searchpitco=$form->showFilterAndCheckAddButtons($massactionbutton?1:0, 'checkforselect', 1);
	print $searchpitco;
	print '<input type=checkbox id="dochkall">';

	print '</td>';
	print "</tr>\n";

	$var=True;
	$idlist="";
	$reflist="";
	$total = 0;
	$i = 0;
	$separatorlist=$conf->global->EQUIPEMENT_SEPARATORLIST;
	$separatorlist =($separatorlist ? $separatorlist : ";");
	if ($separatorlist == "__N__")
		$separatorlist="\n";
	if ($separatorlist == "__B__")
		$separatorlist="\b";

	while ($i < min($num, $limit)) {
		$objp = $db->fetch_object($resql);

		$idlist.=$objp->equipementid.$separatorlist;
		$reflist.=$objp->ref.$separatorlist;
		$var=!$var;
		print "<tr $bc[$var]>";

		if (! empty($arrayfields['e.ref']['checked'])) {
			print '<td class="nowrap">';
			$equipementstatic->fetch($objp->equipementid);
			print $equipementstatic->getNomUrl(1);
			// si c'est un lot
//			if ($objp->quantity > 1)
//				print ' ('.$objp->quantity.')';
			print $obj->increment;

			print "</td>\n";
			if (! $i) $totalarray['nbfield']++;
		}
		if (! empty($arrayfields['e.description']['checked'])) {
			print '<td >';
			print $objp->description;
			print '</td>';
		}

		if (! empty($arrayfields['p.ref']['checked'])) {
			// toujours un produit associ� � un �quipement // cela va changer
			print '<td >';
			if ($objp->fk_product > 0) {
				$productstatic=new Product($db);
				$productstatic->fetch($objp->fk_product);
				print $productstatic->getNomUrl(1);
			}
			print '</td>';
		}

		if (! empty($arrayfields['p.label']['checked'])) {
			// toujours un produit associ� � un �quipement
			print '<td >';

			print $objp->labelproduit;
			print '</td>';
		}

		if (! empty($arrayfields['e.quantity']['checked'])) {
			print "<td >";
			if ($equipementstatic->quantity !=0)
				print $equipementstatic->quantity;
			print "</td>";
		}


		if (! empty($arrayfields['e.unitweight']['checked'])) {
			print "<td >";
			if ($equipementstatic->unitweight !=0)
				print $equipementstatic->unitweight;
			print "</td>";
		}


		if (! empty($arrayfields['e.numversion']['checked'])) {
			print "<td>";
			print $objp->numversion;
			print '</td>';
		}


		if (! empty($arrayfields['ent.label']['checked'])) {
			print "<td>";
			if ($objp->fk_entrepot>0) {
				$entrepotstatic = new Entrepot($db);
				$entrepotstatic->fetch($objp->fk_entrepot);
				print $entrepotstatic->getNomUrl(1);
			}
			print '</td>';
		}

		if (! empty($arrayfields['sfou.nom']['checked'])) {
			print "<td>";
			if ($objp->fk_soc_fourn > 0) {
				$socfourn = new Societe($db);
				$socfourn->fetch($objp->fk_soc_fourn);
				print $socfourn->getNomUrl(1);
			}
			print '</td>';
		}

		if (! empty($arrayfields['cf.ref']['checked'])) {
			print "<td align=left>";
			if ( $objp->fk_commande_fourn > 0
				&& $user->rights->fournisseur->commande->lire) {
				$commfournstatic = new CommandeFournisseur($db);
          $commfournstatic->fetch($objp->fk_commande_fourn);
				print $commfournstatic ->getNomUrl(1);
			}
			print '</td>';
		}

    if (! empty($arrayfields['ff.ref']['checked'])) {
      print "<td align=left>";
      if ( $objp->fk_facture_fourn > 0
        && $user->rights->facture->lire) {
        $factfournstatic = new FactureFournisseur($db);
        $factfournstatic->fetch($objp->fk_facture_fourn);
        print $factfournstatic ->getNomUrl(1);
      }
      print '</td>';
    }


		if (! empty($arrayfields['scli.nom']['checked'])) {
			print "<td>";
			if ($objp->fk_soc_client > 0) {
				$soc = new Societe($db);
				$soc->fetch($objp->fk_soc_client);
				print $soc->getNomUrl(1);
			}
			print '</td>';
		}
		if (! empty($arrayfields['f.facnumber']['checked'])) {
			print "<td align=left>";
			if ($objp->fk_facture > 0
				&& $user->rights->facture->lire) {
				$facturestatic=new Facture($db);
				$facturestatic->fetch($objp->fk_facture);
				print $facturestatic ->getNomUrl(1);
			}
			print '</td>';
		}

		if (! empty($arrayfields['e.datec']['checked']))
			print "<td nowrap align='center'>".dol_print_date($db->jdate($objp->datec), 'day')."</td>\n";
		if (! empty($arrayfields['e.dateo']['checked']))
			print "<td nowrap align='center'>".dol_print_date($db->jdate($objp->dateo), 'day')."</td>\n";
		if (! empty($arrayfields['e.datee']['checked']))
			print "<td nowrap align='center'>".dol_print_date($db->jdate($objp->datee), 'day')."</td>\n";

		if (! empty($arrayfields['e.dated']['checked']) && $conf->global->EQUIPEMENT_USEDLUODATE == 1)
			print "<td nowrap align='center'>".dol_print_date($db->jdate($objp->dated), 'day')."</td>\n";

		if (! empty($arrayfields['ee.libelle']['checked'])) {
			print '<td align="right">';
			if ($objp->etatequiplibelle)
				print $langs->trans($objp->etatequiplibelle);
			print '</td>';
		}

		if (! empty($arrayfields['e.note_private']['checked'])) {
        print "<td align='left'>";
        $tmpcontent=dol_htmlentitiesbr($objp->note_private);
        if (! empty($conf->global->MAIN_DISABLE_NOTES_TAB))
        {
            $firstline=preg_replace('/<br>.*/','',$tmpcontent);
            $firstline=preg_replace('/[\n\r].*/','',$firstline);
            $tmpcontent=$firstline.((strlen($firstline) != strlen($tmpcontent))?'...':'');
        }
        print $tmpcontent;
        print "</td>\n";

    }


    if (! empty($arrayfields['e.tms']['checked']))
        print "<td nowrap align='center'>".dol_print_date($db->jdate($objp->tms), 'day')."</td>\n";


		if (! empty($arrayfields['e.fk_statut']['checked'])) {
			print '<td align=right>';
			print $equipementstatic->LibStatut($objp->fk_statut, 5);
			print '</td><td align=right>';
		}




		print '<input type=checkbox class=chkall name=chk-'.$objp->equipementid.'>';
		print '</td>';

		// seconde ligne
		print "</tr>\n";

		$i++;
	}
	print '</table>';

	print "<label id='showreflist'>";
	print img_picto("", "edit_add")."&nbsp;".$langs->trans("ShowRefEquipement")."</label><br>";
	print '<textarea cols="80" id="reflist" style="display:none;" rows="'.ROWS_6.'">'.$reflist.'</textarea>';
	print '<br>';

	print_fiche_titre($langs->trans("EquipementMassChange"));
	print '<br>';
	// to do : after test made it hidden
	print "<input type=hidden size=50 name=idlist value='".$idlist."'>";
	print '<table class="border">';

	print "<tr>";
	print "<td align=left>".$langs->trans("Entrepot")."</td>";
	print "<td align=left>";
	print '<input type=checkbox name="chk_entrepot">&nbsp;';
	select_entrepot($update_entrepot, 'update_entrepot', 1, 1, 0, 1);
	print "</td>";
	print "</tr>";

	print "<tr>";
	print "<td align=left>".$langs->trans("Customer")."</td>";
	print "<td align=left>";
	print '<input type=checkbox name="chk_soc_client" >';
	print $form->select_company('', 'update_soc_client', '', 'SelectThirdParty', 1);
	print "</td>";
	print "</tr>";

	print "<tr>";
	print "<td align=left>".$langs->trans("Dateo")."</td>";
	print "<td align=left>";
	print '<input type=checkbox name="chk_dateo" >&nbsp;';
	print $form->select_date('', 'dateo', 0, 0, 1, "dateo");
	print "</td>";
	print "</tr>";

	print "<tr >";
	print "<td align=left>".$langs->trans("Datee")."</td>";
	print "<td align=left>";
	print '<input type=checkbox name="chk_datee" >&nbsp;';
	print $form->select_date('', 'datee', 0, 0, 1, "datee");
	print "</td>";
	print "</tr>";

	if ($conf->global->EQUIPEMENT_USEDLUODATE == 1) {
		// Date DLUO
		print "<tr >";
		print "<td align=left>".$langs->trans("DateDluo")."</td>";
		print "<td align=left>";
		print '<input type=checkbox name="chk_dated" >&nbsp;';
		print $form->select_date('', 'dated', 0, 0, 1, "dated");
		print "</td>";
		print "</tr >";
	}

	print "<tr >";
	print "<td align=left>".$langs->trans("EtatEquip")."</td>";
	print "<td align=left>";
	print '<input type=checkbox name="chk_etatequipement" >&nbsp;';
	print select_equipement_etat('', 'update_etatequipement', 1, 1);
	print "</td>";
	print "</tr>";

	print "<tr>";
	print "<td align=left >".$langs->trans("Status")."</td>";
	print "<td align=left>";
	print '<input type=checkbox name="chk_statut" >&nbsp;';
	print '<select class="flat" name="update_statut">';
	print '<option value="-1">&nbsp;</option>';
	print '<option value="0">'.$equipementstatic->LibStatut(0).'</option>';
	print '<option value="1">'.$equipementstatic->LibStatut(1).'</option>';
	print '<option value="2">'.$equipementstatic->LibStatut(2).'</option>';
	print '</select>';
	print "</td>";
	print "</tr >";

	print "<tr>";
	print "<td align=center colspan=2>";
	print "<input type=submit name='updatecheck' value='".$langs->trans("Update")."'>";
	print "</td>";
	print "</tr>\n";

	//print '<tr class="liste_total"><td colspan="7" class="liste_total">'.$langs->trans("Total").'</td>';
	//print '<td align="right" nowrap="nowrap" class="liste_total">'.$i.'</td><td>&nbsp;</td>';
	//print '</tr>';

	print '</table>';
//	dol_fiche_end();
	print "</form>\n";
	$db->free($resql);
}
else
	dol_print_error($db);

llxFooter();
$db->close();

?>
<script>
$(document).ready(function() {
	$('#showreflist').click(function() {  //on click
		$('#reflist').toggle();
	});

	$('#dochkall').click(function(event) {  //on click
		if (this.checked) { // check select status
			$('.chkall').each(function() { //loop through each checkbox
				this.checked = true;
			});
		}else{
			$('.chkall').each(function() { //loop through each checkbox
				this.checked = false;
			});
		}
	});
});
</script>