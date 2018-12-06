<?php
/* Copyright (C) 2014-2017 Charlie BENKE 	 	 <charlie@patas-monkey.com>
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
 *	\file	   htdocs/customlink/tabs/factureVentil.php
 *	\brief	  liaison de facture fournisseur et calcul de la marge
 *	\ingroup	customlink
 */
$res=@include("../../main.inc.php");					// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res)
	$res=@include("../../../main.inc.php");		// For "custom" directory


require_once(DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");
require_once DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php';

require_once DOL_DOCUMENT_ROOT.'/margin/lib/margins.lib.php';

dol_include_once('/customlink/class/customlink.class.php');
dol_include_once('/customlink/core/lib/customlink.lib.php');

if (!empty($conf->projet->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	$langs->load('projects');
}


$langs->load("companies");
$langs->load("customlink@customlink");
$langs->load("bills");

$id = (GETPOST('id', 'int') ? GETPOST('id', 'int') : GETPOST('facid', 'int'));
$ref = GETPOST('ref', 'alpha');

$object = new Facture($db);
$object->fetch($id, $ref);
if ($id=="")
	$id=$object->id;

$soc = new Societe($db);
$soc->fetch($object->socid);


// Security check
if (! empty($user->societe_id)) $socid=$user->societe_id;
$result = restrictedArea($user, 'fournisseur', $id, 'facture_fourn', 'facture');

$action	= GETPOST('action', 'alpha');

/*
 *	View
 */

llxHeader();

$form = new Form($db);

$object->fetch_thirdparty();

$head = facture_prepare_head($object);
dol_fiche_head($head, 'customlink', $langs->trans("InvoiceCustomer"), 0, 'bill');


$linkback = '<a href="'.DOL_URL_ROOT.'/compta/facture/list.php'.(! empty($socid)?'?socid='.$socid:'').'">';
$linkback.= $langs->trans("BackToList").'</a>';

if (DOL_VERSION >= "5.0.0") {


} else {
	print '<table class="border" width="100%">';

	// Ref
	print '<tr><td width="20%">'.$langs->trans("Ref").'</td><td colspan="5">';
	$morehtmlref='';

	print $form->showrefnav($object, 'ref', $linkback, 1, 'facnumber', 'ref', $morehtmlref);
	print "</td></tr>";

	// Ref customer
	print '<tr><td width="20%">';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('RefCustomer');
	print '</td>';
	print '</tr></table>';
	print '</td>';
	print '<td colspan="5">';
	print $object->ref_client;
	print '</td></tr>';

	// Third party
	print '<tr><td>'.$langs->trans('Company').'</td>';
	print '<td colspan="5">'.$object->thirdparty->getNomUrl(1, 'compta');
	print ' &nbsp; (<a href="'.DOL_URL_ROOT.'/compta/facture/list.php?socid='.$object->socid.'">';
	print $langs->trans('OtherBills').'</a>)</td>';
	print '</tr>';

	// Type
	print '<tr><td>'.$langs->trans('Type').'</td><td colspan="5">';
	print $object->getLibType();
	if ($object->type == 1) {
		$facreplaced=new Facture($db);
		$facreplaced->fetch($object->fk_facture_source);
		print ' ('.$langs->transnoentities("ReplaceInvoice", $facreplaced->getNomUrl(1)).')';
	}
	if ($object->type == 2) {
		$facusing=new Facture($db);
		$facusing->fetch($object->fk_facture_source);
		print ' ('.$langs->transnoentities("CorrectInvoice", $facusing->getNomUrl(1)).')';
	}

	$facidavoir=$object->getListIdAvoirFromInvoice();
	if (count($facidavoir) > 0) {
		print ' ('.$langs->transnoentities("InvoiceHasAvoir");
		$i=0;
		foreach ($facidavoir as $id) {
			if ($i==0)
				print ' ';
			else
				print ',';
			$facavoir=new Facture($db);
			$facavoir->fetch($id);
			print $facavoir->getNomUrl(1);
		}
		print ')';
	}

	print '</td></tr>';

	// Statut
	print '<tr><td>'.$langs->trans('Status').'</td>';
	print '<td align="left" colspan="3">'.($object->getLibStatut(4, $totalpaye)).'</td></tr>';
	print '</table>';
}

print '<table class="border" width="100%">';

// Date invoice
print '<tr><td width="20%">';
print '<table class="nobordernopadding" width="100%"><tr><td>';
print $langs->trans('Date');
print '</td>';
if ($object->type != 2 && $action != 'editinvoicedate'
	&& ! empty($object->brouillon) && $user->rights->facture->creer
) {
	print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editinvoicedate&amp;id='.$object->id.'">';
	print img_edit($langs->trans('SetDate'), 1).'</a></td>';
}
print '</tr></table>';
print '</td><td colspan="4">';

if ($object->type != 2) {
	if ($action == 'editinvoicedate')
		$form->form_date($_SERVER['PHP_SELF'].'?id='.$object->id, $object->date, 'invoicedate');
	else
		print dol_print_date($object->date, 'daytext');
} else
	print dol_print_date($object->date, 'daytext');

print '</td>';
print '</tr>';

// Date payment term
print '<tr><td>';
print '<table class="nobordernopadding" width="100%"><tr><td>';
print $langs->trans('DateMaxPayment');
print '</td>';
if ($object->type != 2 && $action != 'editpaymentterm'
	&& !empty($object->brouillon) && $user->rights->facture->creer
) {
	print '<td align="right">';
	print '<a href="'.$_SERVER["PHP_SELF"].'?action=editpaymentterm&amp;id='.$object->id.'">';
	print img_edit($langs->trans('SetDate'), 1).'</a></td>';
}
print '</tr></table>';
print '</td><td colspan="4">';
if ($object->type != 2) {
	if ($action == 'editpaymentterm')
		$form->form_date($_SERVER['PHP_SELF'].'?id='.$object->id, $object->date_lim_reglement, 'paymentterm');
	else {
		print dol_print_date($object->date_lim_reglement, 'daytext');
		if ($object->date_lim_reglement < ($now - $conf->facture->client->warning_delay)
				&& ! $object->paye && $object->statut == 1 && ! isset($object->am))
			print img_warning($langs->trans('Late'));
	}
}
else
	print '&nbsp;';
print '</td></tr>';

// Conditions de reglement
print '<tr><td>';
print '<table class="nobordernopadding" width="100%"><tr><td>';
print $langs->trans('PaymentConditionsShort');
print '</td>';
if ($object->type != 2 && $action != 'editconditions'
	&& ! empty($object->brouillon) && $user->rights->facture->creer) {
	print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editconditions&amp;id='.$object->id.'">';
	print img_edit($langs->trans('SetConditions'), 1).'</a></td>';
}
print '</tr></table>';
print '</td><td colspan="4">';
if ($object->type != 2) {
	if ($action == 'editconditions')
		$form->form_conditions_reglement(
						$_SERVER['PHP_SELF'].'?id='.$object->id,
						$object->cond_reglement_id, 'cond_reglement_id'
		);
	else
		$form->form_conditions_reglement(
						$_SERVER['PHP_SELF'].'?id='.$object->id,
						$object->cond_reglement_id, 'none'
		);
}
else
	print '&nbsp;';
print '</td>';
print '</tr>';



// Mode de reglement
print '<tr><td>';
print '<table class="nobordernopadding" width="100%"><tr><td>';
print $langs->trans('PaymentMode');
print '</td>';
if ($action != 'editmode' && ! empty($object->brouillon) && $user->rights->facture->creer) {
	print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editmode&amp;id='.$object->id.'">';
	print img_edit($langs->trans('SetMode'), 1).'</a></td>';
}
print '</tr></table>';
print '</td><td colspan="3">';
if ($action == 'editmode')
	$form->form_modes_reglement(
					$_SERVER['PHP_SELF'].'?id='.$object->id,
					$object->mode_reglement_id, 'mode_reglement_id'
	);
else
	$form->form_modes_reglement(
					$_SERVER['PHP_SELF'].'?id='.$object->id,
					$object->mode_reglement_id, 'none'
	);

print '</td>';
	print '<td rowspan="7" colspan="2" valign="top">';
// Margin Infos
if (! empty($conf->margin->enabled))
	displayMarginInfos($object->statut > 0);

print '</td>';
print '</tr>';

// Montants
print '<tr><td>'.$langs->trans('AmountHT').'</td>';
print '<td align="right" colspan="2" nowrap>'.price($object->total_ht).'</td>';
print '<td>'.$langs->trans('Currency'.$conf->currency).'</td></tr>';
print '<tr><td>'.$langs->trans('AmountVAT').'</td>';
print '<td align="right" colspan="2" nowrap>'.price($object->total_tva).'</td>';
print '<td>'.$langs->trans('Currency'.$conf->currency).'</td></tr>';
print '<tr><td>'.$langs->trans('AmountTTC').'</td>';
print '<td align="right" colspan="2" nowrap>'.price($object->total_ttc).'</td>';
print '<td>'.$langs->trans('Currency'.$conf->currency).'</td></tr>';

// We can also use bcadd to avoid pb with floating points
// For example print 239.2 - 229.3 - 9.9; does not return 0.
//$resteapayer=bcadd($object->total_ttc, $totalpaye, $conf->global->MAIN_MAX_DECIMALS_TOT);
//$resteapayer=bcadd($resteapayer, $totalavoir, $conf->global->MAIN_MAX_DECIMALS_TOT);
$resteapayer = price2num($object->total_ttc - $totalpaye - $totalcreditnotes - $totaldeposits, 'MT');

print '<tr><td>'.$langs->trans('RemainderToPay').'</td>';
print '<td align="right" colspan="2" nowrap>'.price($resteapayer).'</td>';
print '<td>'.$langs->trans('Currency'.$conf->currency).'</td></tr>';


print '</table>';


print '<br>';

$sql = "SELECT *  FROM ".MAIN_DB_PREFIX."facture_fourn_ventil as ffv";
$sql.= " WHERE ffv.entity = ".$conf->entity;
$sql.= " AND ffv.fk_facture_link =".$id;
$sql.= " AND ffv.fk_facture_typelink =0";
$sql.= " ORDER BY ffv.rowid";

$result=$db->query($sql);
if ($result) {
	$num = $db->num_rows($result);

	// c'est forcément une facture fournisseur qui est ventilé
	//$Facture=new FactureFournisseur($db);


	print_barre_liste(
					$langs->trans("ListOfVentiledBillsInput"), $page, "facturefournventil.php",
					$urlparam, $sortfield, $sortorder, '', $num, 0, 'ventilinput@customlink'
	);

	print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">'."\n";
	print '<input type="hidden" class="flat" name="id" value="'.$id.'">';
	print '<table class="noborder" width="100%">';

	print "<tr class=\"liste_titre\">";
	print_liste_field_titre($langs->trans("Ref"), "", "", "", $urlparam, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Company"), "", "", "", $urlparam, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("DateInvoice"), "", "", "", $urlparam, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("DateVentilation"), "", "", "", $urlparam, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("label"), "", "", "", $urlparam, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("PriceUHT"), "", "", "", $urlparam, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("VAT"), "", "", "", $urlparam, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Qty"), "", "", "", $urlparam, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("TotalTTC"), "", "", "", $urlparam, '', $sortfield, $sortorder);
	print "</tr>\n";

	$var=True;
	$total = 0;
	$i = 0;
	while ($i < $num) {
		$objp = $db->fetch_object($result);
		$var=!$var;
		$linkedobject = new FactureFournisseur($db);
		$linkedobject->fetch($objp->fk_facture_fourn);
		print "<tr $bc[$var]>";
		print "<td>".$linkedobject->getNomUrl()."</td>";
		$soc = new Societe($db);
		$soc->fetch($linkedobject->socid);
		print "<td>".$soc->getNomUrl()."</td>";
		print "<td>".dol_print_date($linkedobject->date, "%d/%m/%Y")."</td>";
		print "<td>".dol_print_date($objp->datev, "%d/%m/%Y")."</td>";
		print "<td>".$objp->label."</td>";
		print "<td>".price($objp->subprice)."</td>";
		print "<td>".price($objp->tva_tx)."</td>";
		print "<td>".$objp->qty."</td>";
		print "<td>".price($objp->total_ttc)."</td>";
		print "</tr>\n";
		$i++;
	}
	print '</table>';
}
else
	dol_print_error($db);


dol_fiche_end();
llxFooter();
$db->close();

function displayMarginInfos($force_price=false)
{
	global $object, $db, $langs, $conf, $user;

	if (! empty($user->societe_id)) return;

	if (! $user->rights->margins->liretous) return;

	$rounding = min($conf->global->MAIN_MAX_DECIMALS_UNIT, $conf->global->MAIN_MAX_DECIMALS_TOT);

	$marginInfo = getMarginInfos($force_price, 0, 0, 0, 0, 0, 0);

	print '<table class="noborder margininfos" width="100%">';
	print '<tr class="liste_titre">';
	print '<td width="30%" >'.$langs->trans('Margins').'</td>';
	print '<td width="20%" align="right">'.$langs->trans('SellingPrice').'</td>';
	if ($conf->global->MARGIN_TYPE == "1")
		print '<td width="20%" align="right">'.$langs->trans('BuyingPrice').'</td>';
	else
		print '<td width="20%" align="right">'.$langs->trans('CostPrice').'</td>';
	print '<td width="20%" align="right">'.$langs->trans('Margin').'</td>';
	print '</tr>';

	print '<tr class="impair">';
	print '<td>'.$langs->trans('MarginOnProducts').'</td>';
	print '<td align="right">'.price($marginInfo['pv_products'], null, null, null, null, $rounding).'</td>';
	print '<td align="right">'.price($marginInfo['pa_products'], null, null, null, null, $rounding).'</td>';
	print '<td align="right">'.price($marginInfo['margin_on_products'], null, null, null, null, $rounding).'</td>';
	print '</tr>';

	print '<tr class="pair">';
	print '<td>'.$langs->trans('MarginOnServices').'</td>';
	print '<td align="right">'.price($marginInfo['pv_services'], null, null, null, null, $rounding).'</td>';
	print '<td align="right">'.price($marginInfo['pa_services'], null, null, null, null, $rounding).'</td>';
	print '<td align="right">'.price($marginInfo['margin_on_services'], null, null, null, null, $rounding).'</td>';
	print '</tr>';

	$sql = "SELECT sum(total_ht) as totalventil";
	$sql.= " FROM ".MAIN_DB_PREFIX."facture_fourn_ventil as ffv";
	$sql.= " WHERE ffv.entity = ".$conf->entity;
	$sql .= " AND ffv.fk_facture_link =".$object->id;
	$sql .= " AND ffv.fk_facture_typelink =0";
	$sql.= " ORDER BY ffv.rowid";

	$result=$db->query($sql);

	if ($result) {
		$obj = $db->fetch_object($result);
		$totalventil = $obj->totalventil;
	}

	print '<tr class="impair">';
	print '<td>'.$langs->trans('MarginOnVentilation').'</td>';
	print '<td align="right">'.price(0, null, null, null, null, $rounding).'</td>';
	print '<td align="right">'.price($totalventil, null, null, null, null, $rounding).'</td>';
	print '<td align="right">'.price(-$totalventil, null, null, null, null, $rounding).'</td>';
	print '</tr>';

	print '<tr class="pair">';
	print '<td>'.$langs->trans('TotalMargin').'</td>';
	print '<td align="right">'.price($marginInfo['pv_total'], null, null, null, null, $rounding).'</td>';
	print '<td align="right">'.price($marginInfo['pa_total']+$totalventil, null, null, null, null, $rounding).'</td>';
	print '<td align="right">'.price($marginInfo['total_margin']-$totalventil, null, null, null, null, $rounding).'</td>';
	print '</tr>';
	print '</table>';
}