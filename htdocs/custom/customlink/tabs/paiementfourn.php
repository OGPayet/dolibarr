<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2014 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005	  Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2012 Regis Houssin		 <regis.houssin@capnetworks.com>
 * Copyright (C) 2007	  Franky Van Liedekerke <franky.van.liedekerke@telenet.be>
 * Copyright (C) 2014-2016 Charlie BENKE 	 	 <charlie@patas-monkey.com>
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
 *	\file	   htdocs/customlink/tabs/paiement.php
 *	\ingroup	facture
 *	\brief	  Payment page for customers linked invoices
 */

$res=@include("../../main.inc.php");					// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../../main.inc.php");		// For "custom" directory

require_once DOL_DOCUMENT_ROOT.'/fourn/class/paiementfourn.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';

require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';

$langs->load('companies');
$langs->load('bills');
$langs->load('banks');

$action		= GETPOST('action', 'alpha');
$confirm	= GETPOST('confirm');

$id			= GETPOST('id', 'int');
$socname	= GETPOST('socname');
$accountid	= GETPOST('accountid');
$paymentnum	= GETPOST('num_paiement');

$sortfield	= GETPOST('sortfield', 'alpha');
$sortorder	= GETPOST('sortorder', 'alpha');
$page		= GETPOST('page', 'int');

$amounts=array();
$amountsresttopay=array();
$addwarning=0;

// Security check
$socid=0;
if ($user->societe_id > 0)
	$socid = $user->societe_id;

$result = restrictedArea($user, 'societe', $id, '');

$object = new Societe($db);

// Load object
if ($id > 0)
	$ret=$object->fetch($id);


/*
 * Actions
 */
if ($action == 'add_paiement' || ($action == 'confirm_paiement' && $confirm=='yes')) {
	$error = 0;

	$datepaye = dol_mktime(12, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));
	$paiement_id = 0;
	$totalpayment = 0;
	$atleastonepaymentnotnull = 0;

	// Generate payment array and check if there is payment higher than invoice and payment date before invoice date
	$tmpinvoice=new FactureFournisseur($db);
	foreach ($_POST as $key => $value) {
		if (substr($key, 0, 7) == 'amount_') {
			$cursorfacid = substr($key, 7);
			$amounts[$cursorfacid] = price2num(trim(GETPOST($key)));
			$totalpayment = $totalpayment + $amounts[$cursorfacid];
			if (! empty($amounts[$cursorfacid])) $atleastonepaymentnotnull++;
			$result=$tmpinvoice->fetch($cursorfacid);
			if ($result <= 0)
				dol_print_error($db);
			$amountsresttopay[$cursorfacid]=price2num($tmpinvoice->total_ttc - $tmpinvoice->getSommePaiement());
			if ($amounts[$cursorfacid]) {
				// Check amount
				if ($amounts[$cursorfacid] && (abs($amounts[$cursorfacid]) > abs($amountsresttopay[$cursorfacid]))) {
					$addwarning=1;
					$formquestion['text'] = img_warning(
									$langs->trans("PaymentHigherThanReminderToPay")
					).' '.$langs->trans("HelpPaymentHigherThanReminderToPay");
				}
				// Check date
				if ($datepaye && ($datepaye < $tmpinvoice->date)) {
					$langs->load("errors");
					//$error++;
					setEventMessage(
									$langs->transnoentities(
													"WarningPaymentDateLowerThanInvoiceDate",
													dol_print_date($datepaye, 'day'),
													dol_print_date($tmpinvoice->date, 'day'),
													$tmpinvoice->ref
									), 'warnings'
					);
				}
			}
			$formquestion[$i++]=array('type' => 'hidden', 'name' => $key, 'value' => $_POST[$key]);
		}
	}

	// Check parameters
	if (! GETPOST('paiementcode')) {
		setEventMessage(
						$langs->transnoentities('ErrorFieldRequired', $langs->transnoentities('PaymentMode')),
						'errors'
		);
		$error++;
	}

	if (! empty($conf->banque->enabled)) {
		// If bank module is on, account is required to enter a payment
		if (GETPOST('accountid') <= 0) {
			setEventMessage(
							$langs->transnoentities('ErrorFieldRequired', $langs->transnoentities('AccountToCredit')),
							'errors'
			);
			$error++;
		}
	}

	if (empty($totalpayment) && empty($atleastonepaymentnotnull)) {
		setEventMessage($langs->transnoentities('ErrorFieldRequired', $langs->trans('PaymentAmount')), 'errors');
		$error++;
	}

	if (empty($datepaye)) {
		setEventMessage($langs->transnoentities('ErrorFieldRequired', $langs->transnoentities('Date')), 'errors');
		$error++;
	}
}

/*
 * Action add_paiement
 */
if ($action == 'add_paiement') {
	if ($error)
		$action = '';
	// Le reste propre a cette action s'affiche en bas de page.
}

/*
 * Action confirm_paiement
 */
if ($action == 'confirm_paiement' && $confirm == 'yes') {
	$error=0;

	$datepaye = dol_mktime(12, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));

	$db->begin();

	// Clean parameters amount if payment is for a credit note
	if (GETPOST('type') == 2) {
		// How payment is dispatch
		foreach ($amounts as $key => $value) {
			$newvalue = price2num($value, 'MT');
			$amounts[$key] = -$newvalue;
		}
	}

	if (! empty($conf->banque->enabled)) {
		// Si module bank actif, un compte est obligatoire lors de la saisie d'un paiement
		if (GETPOST('accountid') <= 0) {
			setEventMessage(
							$langs->trans('ErrorFieldRequired', $langs->transnoentities('AccountToCredit')),
							'errors'
			);
			$error++;
		}
	}

	// Creation of payment line
	$paiementFourn = new PaiementFourn($db);
	$paiementFourn->datepaye	 	= $datepaye;
	$paiementFourn->amounts			= $amounts;   // Array with all payments dispatching
	$paiementFourn->paiementid		= dol_getIdFromCode($db, $_POST['paiementcode'], 'c_paiement');
	$paiementFourn->num_paiement	= $_POST['num_paiement'];
	$paiementFourn->note		 	= $_POST['comment'];

	if (!$error) {
		$paiement_id = $paiementFourn->create($user, (GETPOST('closepaidinvoices')=='on'?1:0));
		if ($paiement_id < 0) {
			setEventMessage($paiementFourn->error, 'errors');
			$error++;
		}
	}

	if (! $error) {
		$paiement = new Paiement($db);
		$paiement->id		 			= $paiement_id; // l'id du paiement cr�e
		$paiement->amount				= $paiementFourn->amount;
		$paiement->total				= $paiementFourn->total;
		$paiement->multicurrency_amount	= $paiementFourn->multicurrency_amount;

		$paiement->datepaye	 			= $datepaye;
		$paiement->amounts				= $amounts;   // Array with all payments dispatching
		$paiement->paiementid			= dol_getIdFromCode($db, $_POST['paiementcode'], 'c_paiement');
		$paiement->num_paiement			= $_POST['num_paiement'];
		$paiement->note		 			= $_POST['comment'];

		$label='(SupplierInvoicePayment)';
		if (GETPOST('type') == 2)
			$label='(CustomerInvoicePaymentBack)';

		$result=$paiement->addPaymentToBank(
						$user, 'payment_supplier', $label,
						GETPOST('accountid'), GETPOST('chqemetteur'), GETPOST('chqbank')
		);

		if ($result < 0) {
			setEventMessage($paiement->error, 'errors');
			$error++;
		} else {
			// mise � jour du nom du payeur pour �tre propre
			$sql= "UPDATE ".MAIN_DB_PREFIX."bank_url SET url_id=".$id.", label='".$object->nom."'";
			$sql.=" WHERE fk_bank=".$result." AND label <> '(paiement)'";
			$reuptsql = $db->query($sql);
		}
	}

	if (! $error) {
		$db->commit();

		// If payment dispatching on more than one invoice, we keep on summary page, otherwise go on invoice card
		$invoiceid=0;
		foreach ($paiement->amounts as $key => $amount) {
			$facid = $key;
			if (is_numeric($amount) && $amount <> 0) {
				if ($invoiceid != 0)
					$invoiceid=-1; // There is more than one invoice payed by this payment
				else
					$invoiceid=$facid;
			}
		}
		if ($invoiceid > 0)
			$loc = DOL_URL_ROOT.'/fourn/facture/card.php?facid='.$invoiceid;
		else
			$loc = DOL_URL_ROOT.'/fourn/paiement/card.php?id='.$paiement_id;
		header('Location: '.$loc);
		exit;
	}
	else
		$db->rollback();
}


/*
 * View
 */

$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('', $langs->trans("ThirdParty"), $help_url);

$form=new Form($db);

if ($object->fetch($id, $ref) > 0) {
	$soc = new Societe($db);
	$soc->fetch($object->socid);

// TODO format V500 de l'entete


	$head = societe_prepare_head($object);
	dol_fiche_head($head, 'externalsupplierbill', $langs->trans("ThirdParty"), 0, 'company');

	$linkback = '<a href="'.DOL_URL_ROOT.'/compta/facture/list.php'.(! empty($socid)?'?socid='.$socid:'').'">';
	$linkback.= $langs->trans("BackToList").'</a>';

	if (DOL_VERSION >= "5.0.0") {
		dol_banner_tab($object, 'socid', $linkback, ($user->societe_id?0:1), 'rowid', 'nom');

		print '<div class="fichecenter">';
		print '<div class="underbanner clearboth"></div>';
		print '<table class="border centpercent">';

	} else {
		print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="id" value="'.$id.'">';
		print '<table class="border" width="100%">';
		print '<tr><td width="20%">'.$langs->trans('ThirdPartyName').'</td>';
		print '<td colspan="3">';
		print $form->showrefnav($object, 'id', '', ($user->societe_id?0:1), 'rowid', 'nom');
		print '</td></tr>';
		if ($object->client) {
			print '<tr><td>';
			print $langs->trans('CustomerCode').'</td><td colspan="3">';
			print $object->code_client;
			if ($object->check_codeclient() <> 0)
				print ' <font class="error">('.$langs->trans("WrongCustomerCode").')</font>';
			print '</td></tr>';
		}

		if ($object->fournisseur) {
			print '<tr><td>';
			print $langs->trans('SupplierCode').'</td><td colspan="3">';
			print $object->code_fournisseur;
			if ($object->check_codefournisseur() <> 0)
				print ' <font class="error">('.$langs->trans("WrongSupplierCode").')</font>';
			print '</td></tr>';
		}
		print '</table></form><br>';
	}
}


if (! $user->rights->facture->paiement) accessforbidden();

if ($action == '' || $action == 'confirm_paiement' || $action == 'add_paiement') {
	// Initialize data for confirmation (this is used because data can be change during confirmation)
	if ($action == 'add_paiement') {
		$i=0;
		$formquestion[$i++]=array('type' => 'hidden', 'name' => 'facid', 'value' => $facture->id);
		$formquestion[$i++]=array('type' => 'hidden', 'name' => 'socid', 'value' => $facture->socid);
		$formquestion[$i++]=array('type' => 'hidden', 'name' => 'type',  'value' => $facture->type);
	}

	// Add realtime total information
	if ($conf->use_javascript_ajax) {
		print "\n".'<script type="text/javascript" language="javascript">';
		print '$(document).ready(function () {
					setPaiementCode();
					$("#selectpaiementcode").change(function() {
						setPaiementCode();
					});

					function setPaiementCode() {
						var code = $("#selectpaiementcode option:selected").val();

						if (code == "CHQ" || code == "VIR") {
							$(".fieldrequireddyn").addClass("fieldrequired");
							if ($(\'#fieldchqemetteur\').val() == \'\') {
								if ('.$facture->type.' == 2)
									var emetteur = \''.dol_escape_htmltag(MAIN_INFO_SOCIETE_NOM).'\' ;
								else
									var emetteur = jQuery(\'#thirdpartylabel\').val();
								$(\'#fieldchqemetteur\').val(emetteur);
							}
						} else {
							$(".fieldrequireddyn").removeClass("fieldrequired");
							$("#fieldchqemetteur").val(\'\');
						}
					}

					function _elemToJson(selector)
					{
						var subJson = {};
						$.map(selector.serializeArray(), function(n,i)
						{
							subJson[n["name"]] = n["value"];
						});
						return subJson;
					}
					function callForResult(imgId)
					{
						var json = {};
						var form = $("#payment_form");

						json["invoice_type"] = $("#invoice_type").val();
						json["amountPayment"] = $("#amountpayment").attr("value");
						json["amounts"] = _elemToJson(form.find("input[name*=\"amount_\"]"));
						json["remains"] = _elemToJson(form.find("input[name*=\"remain_\"]"));

						if (imgId != null) {
							json["imgClicked"] = imgId;
						}

						$.post("'.DOL_URL_ROOT.'/compta/ajaxpayment.php", json, function(data)
						{
							json = $.parseJSON(data);

							form.data(json);

							for (var key in json)
							{
								if (key == "result")	{
									if (json["makeRed"]) {
										$("#"+key).addClass("error");
									} else {
										$("#"+key).removeClass("error");
									}
									json[key]=json["label"]+" "+json[key];
									$("#"+key).text(json[key]);
								} else {
									form.find("input[name*=\""+key+"\"]").each(function() {
										$(this).attr("value", json[key]);
									});
								}
							}
						});
					}
					$("#payment_form").find("input[name*=\"amount_\"]").change(function() {
						callForResult();
					});
					$("#payment_form").find("input[name*=\"amount_\"]").keyup(function() {
						callForResult();
					});
		';

		// Add user helper to input amount on invoices
		if (! empty($conf->global->MAIN_JS_ON_PAYMENT) && $facture->type != 2) {
			print '	$("#payment_form").find("img").click(function() {
						callForResult(jQuery(this).attr("id"));
					});

					$("#amountpayment").change(function() {
						callForResult();
					});';
		}

		print '	});'."\n";
		print '	</script>'."\n";
	}

	print '<form id="payment_form" name="add_paiement" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add_paiement">';
	print '<input type="hidden" name="id" value="'.$id.'">';
	print '<input type="hidden" name="thirdpartylabel" id="thirdpartylabel"';
	print ' value="'.dol_escape_htmltag($facture->client->name).'">';

	print '<table class="border" width="100%">';

	// Date payment
	print '<tr><td><span class="fieldrequired">'.$langs->trans('Date').'</span></td><td>';
	$datepayment = dol_mktime(12, 0, 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
	$datepayment= ($datepayment == '' ? (empty($conf->global->MAIN_AUTOFILL_DATE)?-1:'') : $datepayment);
	$form->select_date($datepayment, '', '', '', 0, "add_paiement", 1, 1);
	print '</td>';
	print '<td>'.$langs->trans('Comments').'</td></tr>';

	$rowspan=5;
	if ($conf->use_javascript_ajax && !empty($conf->global->MAIN_JS_ON_PAYMENT))
		$rowspan++;

	// Payment mode
	print '<tr><td><span class="fieldrequired">'.$langs->trans('PaymentMode').'</span></td><td>';
	$form->select_types_paiements(
					(GETPOST('paiementcode')?GETPOST('paiementcode'):$facture->mode_reglement_code),
					'paiementcode', '', 2
	);
	print "</td>\n";
	print '<td rowspan="'.$rowspan.'" valign="top">';
	print '<textarea name="comment" wrap="soft" cols="60" rows="'.ROWS_4.'">';
	print (empty($_POST['comment'])?'':$_POST['comment']).'</textarea>';

	print '</td>';
	print '</tr>';

	// Bank account
	print '<tr>';
	if (! empty($conf->banque->enabled)) {
		print '<td><span class="fieldrequired">'.$langs->trans('AccountToDebit').'</span></td>';
		print '<td>';
		$form->select_comptes($accountid, 'accountid', 0, '', 2);
		print '</td>';
	} else
		print '<td colspan="2">&nbsp;</td>';

	print "</tr>\n";

	// Payment amount
	if ($conf->use_javascript_ajax && !empty($conf->global->MAIN_JS_ON_PAYMENT)) {
		print '<tr><td><span class="fieldrequired">'.$langs->trans('AmountPayment').'</span></td>';
		print '<td>';
		if ($action == 'add_paiement') {
			print '<input id="amountpayment" name="amountpaymenthidden" size="8" type="text"';
			print ' value="'.(empty($_POST['amountpayment'])?'':$_POST['amountpayment']).'" disabled="disabled">';
			print '<input name="amountpayment" type="hidden" value="';
			print (empty($_POST['amountpayment'])?'':$_POST['amountpayment']).'">';
		} else {
			print '<input id="amountpayment" name="amountpayment" size="8" type="text" value="';
			print (empty($_POST['amountpayment'])?'':$_POST['amountpayment']).'">';
		}
		print '</td>';
		print '</tr>';
	}

	// Cheque number
	print '<tr><td>'.$langs->trans('Numero');
	print ' <em>('.$langs->trans("ChequeOrTransferNumber").')</em>';
	print '</td>';
	print '<td><input name="num_paiement" type="text" value="'.$paymentnum.'"></td></tr>';

	// Check transmitter
	print '<tr><td class="'.(GETPOST('paiementcode')=='CHQ'?'fieldrequired ':'').'fieldrequireddyn">';
	print $langs->trans('CheckTransmitter');
	print ' <em>('.$langs->trans("ChequeMaker").')</em>';
	print '</td>';
	print '<td><input id="fieldchqemetteur" name="chqemetteur" size="30" type="text" value="'.GETPOST('chqemetteur').'">';
	print '</td></tr>';

	// Bank name
	print '<tr><td>'.$langs->trans('Bank');
	print ' <em>('.$langs->trans("ChequeBank").')</em>';
	print '</td>';
	print '<td><input name="chqbank" size="30" type="text" value="'.GETPOST('chqbank').'"></td></tr>';

	print '</table>';

	/*
	 * List of unpaid invoices
	 */

	// on r�cup�re d'abord la liste des clients li�s � ce tiers
	$sqlTiers = "SELECT ec.element_id as othertiers";
	$sqlTiers.= " FROM ".MAIN_DB_PREFIX."element_contact as ec, ".MAIN_DB_PREFIX."socpeople as sp";
	$sqlTiers.= " WHERE sp.rowid= ec.fk_socpeople and sp.fk_soc=".$id;
	$resqlTiers = $db->query($sqlTiers);
	if ($resqlTiers) {
		$num = $db->num_rows($resqlTiers);
		$i=0;
		$szlistTiers = $id.", ";
		while ($i < $num) {
			$objp = $db->fetch_object($resqlTiers);
			$szlistTiers.= $objp->othertiers.", ";
			$i++;
		}
	}
	$db->free($resqlTiers);


	// ensuite la liste des factures impay�s
	$sql = 'SELECT  f.rowid as facid, s.nom, f.ref as facnumber, f.total_ttc, f.type, f.datef as df';
	$sql.= ' FROM '.MAIN_DB_PREFIX.'facture_fourn as f, '.MAIN_DB_PREFIX.'societe as s';
	$sql.= ' WHERE f.entity = '.$conf->entity;
	$sql.= ' AND f.paye = 0';
	$sql.= ' AND f.fk_statut = 1'; // Statut=0 => not validated, Statut=2 => canceled
	$sql.= ' AND f.type IN (0, 1, 2, 3)';	// Standard invoice, replacement, credit note, deposit
	$sql.= ' AND f.fk_soc = s.rowid';
	if ($szlistTiers)
		$sql.= ' AND f.fk_soc in ('.substr($szlistTiers, 0, -2).')';

	// Sort invoices by date and serial number: the older one comes first
	$sql.=' ORDER BY f.datef ASC, f.ref ASC';


	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		if ($num > 0) {
			$sign=1;
			if ($facture->type == 2) $sign=-1;

			$arraytitle=$langs->trans('Invoice');
			if ($facture->type == 2) $arraytitle=$langs->trans("CreditNotes");
			$alreadypayedlabel=$langs->trans('Received');
			if ($facture->type == 2) $alreadypayedlabel=$langs->trans("PaidBack");
			$remaindertopay=$langs->trans('RemainderToTake');
			if ($facture->type == 2) $remaindertopay=$langs->trans("RemainderToPayBack");

			$i = 0;
			//print '<tr><td colspan="3">';
			print '<br>';
			print '<table class="noborder" width="100%">';
			print '<tr class="liste_titre">';
			print '<td>'.$arraytitle.'</td>';
			print '<td align="left">'.$langs->trans('Company').'</td>';
			print '<td align="center">'.$langs->trans('Date').'</td>';
			print '<td align="right">'.$langs->trans('AmountTTC').'</td>';
			print '<td align="right">'.$alreadypayedlabel.'</td>';
			print '<td align="right">'.$remaindertopay.'</td>';
			print '<td align="right">'.$langs->trans('PaymentAmount').'</td>';
//				print '<td align="center" colspan=2><input type=button name=check value="Select"> </td>';
			print "</tr>\n";

			$var=True;
			$total=0;
			$totalrecu=0;
			$totalrecucreditnote=0;
			$totalrecudeposits=0;

			while ($i < $num) {
				$objp = $db->fetch_object($resql);
				$var=!$var;

				$invoice=new FactureFournisseur($db);
				$invoice->fetch($objp->facid);
				$paiement = $invoice->getSommePaiement();
// present sur les fournisseurs
//				$creditnotes=$invoice->getSumCreditNotesUsed();
//				$deposits=$invoice->getSumDepositsUsed();
				$alreadypayed=price2num($paiement, 'MT');
				$remaintopay=price2num($invoice->total_ttc - $paiement, 'MT');

				print '<tr '.$bc[$var].'>';
				print '<td>';
				print $invoice->getNomUrl(1, '');
				print "</td>\n";
				print '<td>';
				print $objp->nom;
				print "</td>\n";
				// Date
				print '<td align="center">'.dol_print_date($db->jdate($objp->df), 'day')."</td>\n";

				// Price
				print '<td align="right">'.price($sign * $objp->total_ttc).'</td>';

				// Received or paid back
				print '<td align="right">'.price($sign * $paiement);
				if ($creditnotes) print '+'.price($creditnotes);
				if ($deposits) print '+'.price($deposits);
				print '</td>';

				// Remain to take or to pay back
				print '<td align="right">'.price($sign * $remaintopay).'</td>';
				//$test= price(price2num($objp->total_ttc - $paiement - $creditnotes - $deposits));

				// Amount
				print '<td align="right">';

				// Add remind amount
				$namef = 'amount_'.$objp->facid;
				$nameRemain = 'remain_'.$objp->facid;

				if ($action != 'add_paiement') {
					if ($conf->use_javascript_ajax && !empty($conf->global->MAIN_JS_ON_PAYMENT))
						print img_picto($langs->trans('AddRemind'), 'rightarrow.png', 'id="'.$objp->facid.'"');

					print '<input type=hidden name="'.$nameRemain.'" value="'.$remaintopay.'">';
					print '<input type="text" size="8" name="'.$namef.'" value="'.$_POST[$namef].'">';
				} else {
					print '<input type="text" size="8" name="'.$namef.'_disabled" value="'.$_POST[$namef].'" disabled="disabled">';
					print '<input type="hidden" name="'.$namef.'" value="'.$_POST[$namef].'">';
				}
				print "</td>";

				// Warning
				print '<td align="center" width="16">';
				//print "xx".$amounts[$invoice->id]."-".$amountsresttopay[$invoice->id]."<br>";
				if ($amounts[$invoice->id] && (abs($amounts[$invoice->id]) > abs($amountsresttopay[$invoice->id])))
					print ' '.img_warning($langs->trans("PaymentHigherThanReminderToPay"));

				print '</td>';
//				print '<td><input type=checkbox></td>';

				$parameters=array();
				// Note that $action and $object may have been modified by hook
				$reshook=$hookmanager->executeHooks('printObjectLine', $parameters, $objp, $action);
				print "</tr>\n";

				$total+=$objp->total;
				$total_ttc+=$objp->total_ttc;
				$totalrecu+=$paiement;
				$totalrecucreditnote+=$creditnotes;
				$totalrecudeposits+=$deposits;
				$i++;
			}
			if ($i > 1) {
				// Print total
				print '<tr class="liste_total">';
				print '<td colspan="3" align="left">'.$langs->trans('TotalTTC').'</td>';
				print '<td align="right"><b>'.price($sign * $total_ttc).'</b></td>';
				print '<td align="right"><b>'.price($sign * $totalrecu);
				if ($totalrecucreditnote) print '+'.price($totalrecucreditnote);
				if ($totalrecudeposits) print '+'.price($totalrecudeposits);
				print '</b></td>';
				print '<td align="right"><b>';
				print price($sign * price2num($total_ttc - $totalrecu - $totalrecucreditnote - $totalrecudeposits, 'MT'));
				print '</b></td>';
				print '<td align="right" id="result" style="font-weight: bold;"></td>';
				print '<td align="center" colspan=2>&nbsp;</td>';
				print "</tr>\n";
			}
			print "</table>";
			//print "</td></tr>\n";
		}
		$db->free($resql);
	} else
		dol_print_error($db);

	// Bouton Enregistrer
	if ($action != 'add_paiement') {
		$checkboxlabel=$langs->trans("ClosePaidInvoicesAutomatically");
		if ($facture->type == 2) $checkboxlabel=$langs->trans("ClosePaidCreditNotesAutomatically");
		$buttontitle=$langs->trans('ToMakePayment');
		if ($facture->type == 2) $buttontitle=$langs->trans('ToMakePaymentBack');
		print "<table width=100% ><tr><td width=80%>";
		print '<center><br>';
		print '<input type="checkbox" checked="checked" name="closepaidinvoices"> '.$checkboxlabel;
		print '<br><input type="submit" class="button" value="'.dol_escape_htmltag($buttontitle).'"><br><br>';
		print '</center>';

		print "</td></tr></table>";
	} else {
		$preselectedchoice=$addwarning?'no':'yes';
		print '<br>';
		$text=$langs->trans('ConfirmCustomerPayment', $totalpayment, $langs->trans("Currency".$conf->currency));
		if (GETPOST('closepaidinvoices')) {
			$text.='<br>'.$langs->trans("AllCompletelyPayedInvoiceWillBeClosed");
			print '<input type="hidden" name="closepaidinvoices" value="'.GETPOST('closepaidinvoices').'">';
		}
		$form->form_confirm(
						$_SERVER['PHP_SELF'].'?facid='.$facture->id.'&socid='.$facture->socid.'&type='.$facture->type,
						$langs->trans('ReceivedCustomersPayments'), $text, 'confirm_paiement',
						$formquestion, $preselectedchoice
		);
	}
	print "</form>\n";
}


/**
 *  Show list of payments
 */
if (! GETPOST('action')) {
	if ($page == -1) $page = 0 ;
	$limit = $conf->liste_limit;
	$offset = $limit * $page;

	if (! $sortorder) $sortorder='DESC';
	if (! $sortfield) $sortfield='p.datep';

	$sql = 'SELECT p.datep as dp, p.amount, f.amount as fa_amount, f.ref as facnumber';
	$sql.=', f.rowid as facid, c.libelle as paiement_type, p.num_paiement';
	$sql.= ' FROM '.MAIN_DB_PREFIX.'paiement as p, '.MAIN_DB_PREFIX.'facture_fourn as f';
	$sql.= ' , '.MAIN_DB_PREFIX.'c_paiement as c, '.MAIN_DB_PREFIX.'societe as s';
	$sql.= " , ".MAIN_DB_PREFIX."element_contact_societe as ec , ".MAIN_DB_PREFIX."socpeople as sp";
	$sql.= ' WHERE p.fk_facture = f.rowid AND p.fk_paiement = c.id';
	$sql.= ' AND f.entity = '.$conf->entity;
	$sql.= ' AND f.fk_soc = s.rowid';
	if ($id) {
		$sql.= ' AND ((f.fk_soc = '.$id.') OR ' ;
		$sql.= ' (sp.rowid= ec.fk_socpeople AND ec.element_id = s.rowid ';
		$sql.= '  AND ec.fk_c_type_contact=192 AND sp.fk_soc='.$id.'))';
	}
//print $sql;
	$sql.= ' ORDER BY '.$sortfield.' '.$sortorder;
	$sql.= $db->plimit($limit+1, $offset);
	$resql = $db->query($sql);

	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		$var=True;

		print_barre_liste(
						$langs->trans('Payments'), $page, 'paiement.php',
						'', $sortfield, $sortorder, '', $num
		);

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print_liste_field_titre(
						$langs->trans('Invoice'), 'paiement.php', 'facnumber',
						'', '', '', $sortfield, $sortorder
		);
		print_liste_field_titre(
						$langs->trans('Date'), 'paiement.php', 'dp',
						'', '', '', $sortfield, $sortorder
		);
		print_liste_field_titre(
						$langs->trans('Type'), 'paiement.php', 'libelle',
						'', '', '', $sortfield, $sortorder
		);
		print_liste_field_titre(
						$langs->trans('Amount'), 'paiement.php', 'fa_amount',
						'', '', 'align="right"', $sortfield, $sortorder
		);
		print '<td>&nbsp;</td>';
		print "</tr>\n";

		while ($i < min($num, $limit)) {
			$objp = $db->fetch_object($resql);
			$var=!$var;
			print '<tr '.$bc[$var].'>';
			print '<td><a href="'.DOL_URL_ROOT.'/fourn/facture/card.php?facid='.$objp->facid.'">';
			print $objp->facnumber."</a></td>\n";
			print '<td>'.dol_print_date($db->jdate($objp->dp))."</td>\n";
			print '<td>'.$objp->paiement_type.' '.$objp->num_paiement."</td>\n";
			print '<td align="right">'.price($objp->amount).'</td><td>&nbsp;</td>';

			$parameters=array();
			// Note that $action and $object may have been modified by hook
			$reshook=$hookmanager->executeHooks('printObjectLine', $parameters, $objp, $action);

			print '</tr>';
			$i++;
		}
		print '</table>';
	}
}

llxFooter();
$db->close();