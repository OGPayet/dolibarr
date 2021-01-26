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
 *	\file	   htdocs/equipement/tabs/produit.php
 *	\brief	  List of all equipement of the product
 *	\ingroup	equipement
 */
$res=@include("../../main.inc.php");					// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../../main.inc.php");		// For "custom" directory

require_once(DOL_DOCUMENT_ROOT."/product/stock/class/entrepot.class.php");
require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
require_once DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.commande.class.php";
require_once(DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/product.lib.php");

dol_include_once('/equipement/class/equipement.class.php');
dol_include_once('/equipement/core/lib/equipement.lib.php');

$langs->load("companies");
$langs->load("product");
$langs->load("equipement@equipement");

$productid=GETPOST('id', 'int');
$action=GETPOST('action','alpha');


if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'equipement', $equipementid, 'equipement');

$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST('page', 'int');
if ($page == -1) {
	$page = 0;
}
$offset = (int) $conf->liste_limit * (int) $page;
$pageprev = (int) $page - 1;
$pagenext = (int) $page + 1;

if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="e.datec";

$limit = $conf->liste_limit;

$search_ref=GETPOST('search_ref', 'alpha');
$search_numversion=GETPOST('search_numversion', 'alpha');
$search_company_fourn=GETPOST('search_company_fourn', 'alpha');
$search_refcomm_fourn=GETPOST('search_refcomm_fourn', 'alpha');
$search_reffact_fourn=GETPOST('search_reffact_fourn', 'alpha');
$search_company_client=GETPOST('search_company_client', 'alpha');
$search_reffact_client=GETPOST('search_reffact_client', 'alpha');
$search_entrepot=GETPOST('search_entrepot', 'alpha');
$search_etatequipement=GETPOST('search_etatequipement');


$object = new Product($db);

$refproduct=GETPOST('ref', 'alpha');
$result = $object->fetch($productid, $refproduct);
if ($refproduct) $productid = $object->id;

/************************************************
 * Action
 ************************************************/

if ($action == 'reassignsn' && $object->id > 0) {
    $error = 0;

    $reassignsn = GETPOST('equipmentsn', 'alpha');

    if (empty($reassignsn)) {
        setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Equipementcomposant")), null, 'errors');
        $error++;
    }

    if (!$error) {
        $equipment = new Equipement($db);
        if ($equipment->fetch('', $reassignsn) > 0) {
            if ($equipment->fk_product != $object->id) {
                $db->begin();

                $sql = "UPDATE " . MAIN_DB_PREFIX . "equipement";
                $sql .= " SET fk_product = " . $object->id;
                $sql .= " WHERE rowid = " . $equipment->id;

                $resql = $db->query($sql);
                if ($resql) {
                    $fk_equipementevt_type = dol_getIdFromCode($db, 'REASSIGN', 'c_equipementevt_type', 'code', 'rowid');
                    $now = dol_now();
                    $product_static = new Product($db);
                    $product_static->fetch($equipment->fk_product);

                    $result = $equipment->addline(
                        $equipment->id,
                        $fk_equipementevt_type,
                        $langs->trans('ReassignEquipmentProduct', $product_static->getNomUrl(1), $object->getNomUrl(1)),
                        $now,
                        $now,
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        ''
                    );
                    if ($result < 0) {
                        setEventMessage($equipment->errorsToString(), 'errors');
                        $error++;
                    }
                } else {
                    setEventMessage($db->lasterror(), 'errors');
                    $error++;
                }

                if (!$error) {
                    setEventMessage($langs->trans('EquipmentReassigned'));
                    $db->commit();
                } else {
                    $db->rollback();
                }
            } else {
                setEventMessage($langs->trans('EquipmentAlreadyAssignedToThisProduct'));
            }
        } else {
            setEventMessage($langs->trans('EquipmentNotFound'), 'errors');
        }
    }
}

/************************************************
 * View
 ************************************************/

$form = new Form($db);
llxHeader();

$head = product_prepare_head($object, $user);
$titre=$langs->trans("CardProduct".$object->type);
$picto=($object->type==1?'service':'product');
dol_fiche_head($head, 'equipement', $titre, 0, $picto);

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<table class="border" width="100%">';

print '<tr>';
print '<td width="30%">'.$langs->trans("Ref").'</td><td colspan="3">';
print $form->showrefnav($object, 'ref', '', 1, 'ref');

print '</td>';
print '</tr>';

// Label
print '<tr><td>'.$langs->trans("Label").'</td>';
print '<td colspan="3">'.($object->label ? $object->label:$object->libelle).'</td></tr>';

// Status (to sell)
print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Sell").')</td><td>';
print $object->getLibStatut(2, 0);
print '</td></tr>';

// Status (to buy)
print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Buy").')</td><td>';
print $object->getLibStatut(2, 1);
print '</td></tr>';

print '</table></form><br>';

print dol_fiche_end();

$equipement = new Equipement($db);

$head = equipement_product_prepare_head($object, $user);

//dol_fiche_head($head, 'task_task', $langs->trans("Task"),0,'projecttask');
dol_fiche_head($head, 'equipement', $langs->trans("AssociatedEquipement"), 0, "equipement@equipement");

// bouton de cr�ation d'un �quipement � partir du produit
print '<form name="equipement" action="../card.php" method="POST">';
print '<table >';
print '<tr><td >'.$langs->trans("NewEquipementOfProduct").'</td><td>';
print '<input type=hidden name=productid value="'.$productid.'">';

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

// bouton de cr�ation d'un �quipement � partir du produit
print '<form name="equipement" action="'.$_SERVER['PHP_SELF'].'?id='.$productid.'" method="POST">';
print '<input type="hidden" name="action" value="reassignsn">';
print $langs->trans("ReassignEquipmentOfProductLeft");
print '&nbsp;<input type="text" name="equipmentsn" value="">&nbsp;';
print $langs->trans("ReassignEquipmentOfProductRigth");
print '&nbsp;<input type="submit" class="button" value="'.$langs->trans("ReassignEquipment").'">';
print '</form><br>';

$sql = "SELECT";
$sql.= " e.ref, e.rowid, e.fk_statut, e.fk_product, p.ref as refproduit, e.fk_entrepot, ent.ref,";
$sql.= " e.fk_soc_fourn, sfou.nom as CompanyFourn, e.fk_commande_fourn, cf.ref as refCommFourn, e.fk_facture_fourn, ff.ref as refFactureFourn,";
$sql.= " e.fk_soc_client, scli.nom as CompanyClient, e.fk_facture, f.ref as refFacture,";
$sql.= " e.datee, e.dateo, ee.libelle as etatequiplibelle, e.numversion";

$sql.= " FROM ".MAIN_DB_PREFIX."equipement as e";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_equipement_etat as ee on e.fk_etatequipement = ee.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as sfou on e.fk_soc_fourn = sfou.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."entrepot as ent on e.fk_entrepot = ent.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as scli on e.fk_soc_client = scli.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture as f on e.fk_facture = f.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."commande_fournisseur as cf on e.fk_commande_fourn = cf.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture_fourn as ff on e.fk_facture_fourn = ff.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p on e.fk_product = p.rowid";
$sql.= ' WHERE e.entity IN (' . getEntity('equipement') . ')';
$sql.= " AND e.fk_product = ".$productid;

if ($search_ref)			$sql .= " AND e.ref like '%".$db->escape($search_ref)."%'";
if ($search_numversion)		$sql .= " AND e.numversion like '%".$db->escape($search_numversion)."%'";
if ($search_company_fourn)	$sql .= " AND sfou.nom like '%".$db->escape($search_company_fourn)."%'";
if ($search_refcomm_fourn)	$sql .= " AND cf.ref like '%".$db->escape($search_refcomm_fourn)."%'";
if ($search_reffact_fourn)	$sql .= " AND ff.ref like '%".$db->escape($search_reffact_fourn)."%'";
if ($search_entrepot)		$sql .= " AND ent.ref like '%".$db->escape($search_entrepot)."%'";
if ($search_company_client)	$sql .= " AND scli.nom like '%".$db->escape($search_company_client)."%'";
if ($search_reffact_client)	$sql .= " AND f.ref like '%".$db->escape($search_reffact_client)."%'";
if ($search_etatequipement>0)	$sql .= " AND e.fk_etatequipement =".$search_etatequipement;

$sql.= " ORDER BY ".$sortfield." ".$sortorder;
$sql.= $db->plimit($limit+1, $offset);

$result=$db->query($sql);
if ($result) {
	$num = $db->num_rows($result);

	$equipementstatic=new Equipement($db);
	$urlparam="&amp;id=".$productid;
	if ($search_ref)				$urlparam .= "&amp;search_ref=".$db->escape($search_ref);
	if ($search_numversion)			$urlparam .= "&amp;search_numversion=".$db->escape($search_numversion);
	if ($search_company_fourn)		$urlparam .= "&amp;search_company_fourn=".$db->escape($search_company_fourn);
	if ($search_refcomm_fourn)		$urlparam .= "&amp;search_refcomm_fourn=".$db->escape($search_refcomm_fourn);
    if ($search_reffact_fourn)		$urlparam .= "&amp;search_reffact_fourn=".$db->escape($search_reffact_fourn);
	if ($search_entrepot)			$urlparam .= "&amp;search_entrepot=".$db->escape($search_entrepot);
	if ($search_company_client)		$urlparam .= "&amp;search_company_client=".$db->escape($search_company_client);
	if ($search_reffact_client)		$urlparam .= "&amp;search_reffact_client=".$db->escape($search_reffact_client);
	if ($search_etatequipement>=0)	$urlparam .= "&amp;search_etatequipement=".$search_etatequipement;

	print_barre_liste(
					$langs->trans("ListOfEquipements"), $page, "produit.php",
					$urlparam, $sortfield, $sortorder, "", $num
	);

	print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">'."\n";
	print '<input type="hidden" class="flat" name="id" value="'.$productid.'">';
	print '<table class="noborder" width="100%">';

	print "<tr class=\"liste_titre\">";
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
					$langs->trans("RefCommFourn"), $_SERVER["PHP_SELF"], "cf.ref",
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
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_ref" value="'.$search_ref.'" size="8"></td>';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_numversion" value="'.$search_numversion.'" size="10"></td>';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_company_fourn" value="'.$search_company_fourn.'" size="10"></td>';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_refcomm_fourn" value="'.$search_refcomm_fourn.'" size="10"></td>';
    print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_reffact_fourn" value="'.$search_reffact_fourn.'" size="10"></td>';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_entrepot" value="'.$search_entrepot.'" size="10"></td>';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_company_client" value="'.$search_company_client.'" size="10"></td>';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_reffact_client" value="'.$search_reffact_client.'" size="10"></td>';

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
	print select_equipement_etat($search_etatequipement,'search_etatequipement',1,1);
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
	print ' value="'.dol_escape_htmltag($langs->trans("Search")).'"';
	print ' title="'.dol_escape_htmltag($langs->trans("Search")).'"></td>';
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
        if ($objp->fk_commande_fourn) {
            $commfournstatic = new CommandeFournisseur($db);
            $commfournstatic->fetch($objp->fk_commande_fourn);
            print $commfournstatic ->getNomUrl(1);
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
		if ($objp->fk_soc_client>0) {
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

print dol_fiche_end();

llxFooter();
$db->close();
