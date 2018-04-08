<?php
/* Copyright (C) 2014	Maxime MANGIN	<maxime@tuxserv.fr>
 * Copyright (C) 2012	Regis Houssin	<regis@dolibarr.fr>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	\file       /contratabonnement/facturer.php
 *	\ingroup    contract
 *	\brief      Page to create an invoice
 */

$res=@include("../main.inc.php");					// For root directory
if (! $res) $res=@include("../../main.inc.php");	// For "custom" directory

require 'class/contratabonnement_term.class.php';

require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php');
require_once(DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php');
require_once(DOL_DOCUMENT_ROOT.'/core/class/discount.class.php');
require_once(DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php');
require_once(DOL_DOCUMENT_ROOT."/core/lib/functions2.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php');
require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

if (! empty($conf->projet->enabled))  {
	require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
	require_once(DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php');
}

$langs->load('bills');
$langs->load('companies');
$langs->load('products');
$langs->load('main');
$langs->load("contratabonnement@contratabonnement");

$sall=isset($_GET['sall'])?trim($_GET['sall']):trim($_POST['sall']);
$mesg=isset($_GET['mesg'])?$_GET['mesg']:'';
$projectid=isset($_GET['projectid'])?$_GET['projectid']:0;
$usehm=$conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE;


/******************************************************************************/
/*                     Actions                                                */
/******************************************************************************/

/*
/*
 * Insert new invoice in database
 */

if ($_POST['action'] == 'add' && $user->rights->facture->creer) {
	$facture = new Facture($db);
	$facture->socid=$_POST['socid'];
	$db->begin();
	// Replacement invoice
	if ($_POST['type'] == 1) {
		$datefacture = dol_mktime(12, 0 , 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
		if (empty($datefacture)) {
			$error=1;
			$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->trans("Date")).'</div>';
		}
		if (!($_POST['fac_replacement'] > 0)){
			$error=1;
			$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->trans("ReplaceInvoice")).'</div>';
		}
		if (!$error) {
			// This is a replacement invoice
			$result=$facture->fetch($_POST['fac_replacement']);
			$facture->fetch_thirdparty();

			$facture->date           = $datefacture;
			$facture->note_public    = trim($_POST['note_public']);
			$facture->note_private   = trim($_POST['note_private']);
			$facture->ref_client     = $_POST['ref_client'];
			$facture->modelpdf       = $_POST['model'];
			$facture->fk_project        = $_POST['projectid'];
			$facture->cond_reglement_id = $_POST['cond_reglement_id'];
			$facture->mode_reglement_id = $_POST['mode_reglement_id'];
	        $facture->fk_account         = GETPOST('fk_account', 'int');
			$facture->remise_absolue    = $_POST['remise_absolue'];
			$facture->remise_percent    = $_POST['remise_percent'];

			// Proprietes particulieres a facture de remplacement
			$facture->fk_facture_source = $_POST['fac_replacement'];
			$facture->type              = 1;

			$facid=$facture->createFromCurrent($user);
		}
	}

	// Credit note invoice
	if ($_POST['type'] == 2) {
		if (! $_POST['fac_avoir'] > 0) {
			$error=1;
			$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->trans("CorrectInvoice")).'</div>';
		}

		$datefacture = dol_mktime(12, 0 , 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
		if (empty($datefacture)) {$datefacture = time();}

		if (! $error) {
			// Si facture avoir
			$datefacture = dol_mktime(12, 0 , 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
			$facture->socid 		 = $_POST['socid'];
			$facture->number         = $_POST['facnumber'];
			$facture->date           = $datefacture;
			$facture->note_public    = trim($_POST['note_public']);
			$facture->note_private   = trim($_POST['note_private']);
			$facture->ref_client     = $_POST['ref_client'];
			$facture->modelpdf       = $_POST['model'];
			$facture->fk_project        = $_POST['projectid'];
			$facture->cond_reglement_id = 0;
			$facture->mode_reglement_id = $_POST['mode_reglement_id'];
	        $facture->fk_account         = GETPOST('fk_account', 'int');
			$facture->remise_absolue    = $_POST['remise_absolue'];
			$facture->remise_percent    = $_POST['remise_percent'];

			// Proprietes particulieres a facture avoir
			$facture->fk_facture_source = $_POST['fac_avoir'];
			$facture->type              = 2;

			$facid = $facture->create($user);

			//Add lines
			$countAFacturer = count($_POST['aFacturer']);
			$tabAboAReconduire = Array();
			for ($i = 0; $i < $countAFacturer; $i++){
				$sql = "SELECT  cd.fk_product, cd.tva_tx, cd.description, cd.localtax1_tx, cd.localtax2_tx, cd.remise_percent, cd.qty,";
				$sql.= " cat.datedebutperiode, cat.datefinperiode, cat.montantperiode,cat.fk_contratabonnement,";
				$sql.= " p.price_base_type, p.fk_product_type,";
				$sql.= " min(pfp.unitprice) as pa_ht";
				$sql.= " FROM ".MAIN_DB_PREFIX."contratabonnement_term as cat";
				$sql.= " INNER JOIN ".MAIN_DB_PREFIX."contratabonnement as ca";
				$sql.= " ON ca.rowid = cat.fk_contratabonnement";
				$sql.= " INNER JOIN ".MAIN_DB_PREFIX."contratdet as cd";
				$sql.= " ON cd.rowid = ca.fk_contratdet";
				$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p";
				$sql.= " ON p.rowid = cd.fk_product";
                $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as pfp";
				$sql.= " ON p.rowid = pfp.fk_product";
				$sql.= " WHERE cat.rowid = ".$_POST['aFacturer'][$i];
				$sql.= " GROUP BY cat.rowid";
                $sql.= " ORDER BY ca.rowid";

				$resql=$db->query($sql);
				if ($resql){
					$resultSql = $db->fetch_object($resql);
					//Tableau des abonnements à reconduire
					array_push($tabAboAReconduire,$resultSql->fk_contratabonnement);
					$qty = $resultSql->qty;
					$startday=dol_mktime(12, 0 , 0, substr($resultSql->datedebutperiode,5,2), substr($resultSql->datedebutperiode,8,2), substr($resultSql->datedebutperiode,0,4));
					$endday=dol_mktime(12, 0 , 0, substr($resultSql->datefinperiode,5,2), substr($resultSql->datefinperiode,8,2), substr($resultSql->datefinperiode,0,4));
					$prixttc = $resultSql->montantperiode + $resultSql->montantperiode * ($resultSql->tva_tx/100);
					if ($resultSql->fk_product_type == null || $resultSql->fk_product_type == '') {$resultSql->fk_product_type = 0;}
                    if (!isset($resultSql->price_base_type)) {$resultSql->price_base_type = "HT";}

					$result=$facture->addline($resultSql->description,$resultSql->montantperiode / $qty, $qty, $resultSql->tva_tx, $resultSql->localtax1_tx, $resultSql->localtax2_tx,
						$resultSql->fk_product, $resultSql->remise_percent, $startday, $endday, 0, 0, '', $resultSql->price_base_type, $prixttc / $qty, $resultSql->fk_product_type,
                                               -1, 0, '', 0, 0, null, $resultSql->pa_ht);
				}
				else{
					dol_print_error($db);
					$error=1;
				}
			}
		}
	}

	// Standard invoice or Deposit invoice created from a predefined invoice
	if (($_POST['type'] == 0 || $_POST['type'] == 3) && $_POST['fac_rec'] > 0) {
		$datefacture = dol_mktime(12, 0 , 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
		if (empty($datefacture)) {$datefacture = time();}

		if (!$error){
			$facture->socid 		 = $_POST['socid'];
			$facture->type           = $_POST['type'];
			$facture->number         = $_POST['facnumber'];
			$facture->date           = $datefacture;
			$facture->note_public    = trim($_POST['note_public']);
			$facture->note           = trim($_POST['note_private']);
			$facture->ref_client     = $_POST['ref_client'];
			$facture->modelpdf       = $_POST['model'];
			// Source facture
			$facture->fac_rec        = $_POST['fac_rec'];
			$facid = $facture->create($user);
		}
	}

	// Standard or deposit or proforma invoice
	if (($_POST['type'] == 0 || $_POST['type'] == 3 || $_POST['type'] == 4) && $_POST['fac_rec'] <= 0) {
		$datefacture = dol_mktime(12, 0 , 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
		if (empty($datefacture)) {$datefacture = time();}

		if (! $error) {
			// Si facture standard
			$facture->socid 		 = $_POST['socid'];
			$facture->type           = $_POST['type'];
			$facture->number         = $_POST['facnumber'];
			$facture->date           = $datefacture;
			$facture->note_public    = trim($_POST['note_public']);
			$facture->note_private   = trim($_POST['note_private']);
			$facture->ref_client     = $_POST['ref_client'];
			$facture->modelpdf       = $_POST['model'];
			$facture->fk_project        = $_POST['projectid'];
			$facture->cond_reglement_id = ($_POST['type'] == 3?1:$_POST['cond_reglement_id']);
			$facture->mode_reglement_id = $_POST['mode_reglement_id'];
	        $facture->fk_account         = GETPOST('fk_account', 'int');
			$facture->amount            = $_POST['amount'];
			$facture->remise_absolue    = $_POST['remise_absolue'];
			$facture->remise_percent    = $_POST['remise_percent'];
			// pour lier dans element_element
			$facture->origin 	= 'contratabonnement';
			$facture->origin_id = $_POST['id'];
			$facture->linked_objects['contratabonnement'] = $facture->origin_id;
			//
			$facid = $facture->create($user);
			//Add lines
			$countAFacturer = count($_POST['aFacturer']);
			$tabAboAReconduire = Array();
			for ($i = 0; $i < $countAFacturer; $i++){
				$sql = "SELECT  cd.fk_product, cd.tva_tx, cd.description, cd.localtax1_tx, cd.localtax2_tx, cd.remise_percent, cd.qty,";
				$sql.= " cat.datedebutperiode, cat.datefinperiode, cat.montantperiode,cat.fk_contratabonnement,";
				$sql.= " p.price_base_type, p.fk_product_type,";
				$sql.= " min(pfp.unitprice) as pa_ht";
				$sql.= " FROM ".MAIN_DB_PREFIX."contratabonnement_term as cat";
				$sql.= " INNER JOIN ".MAIN_DB_PREFIX."contratabonnement as ca";
				$sql.= " ON ca.rowid = cat.fk_contratabonnement";
				$sql.= " INNER JOIN ".MAIN_DB_PREFIX."contratdet as cd";
				$sql.= " ON cd.rowid = ca.fk_contratdet";
				$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p";
				$sql.= " ON p.rowid = cd.fk_product";
                $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as pfp";
				$sql.= " ON p.rowid = pfp.fk_product";
				$sql.= " WHERE cat.rowid = ".$_POST['aFacturer'][$i];
				$sql.= " GROUP BY cat.rowid";
                $sql.= " ORDER BY ca.rowid";

				$resql=$db->query($sql);
				if ($resql){
					$resultSql = $db->fetch_object($resql);

					//Tableau des abonnements à reconduire
					array_push($tabAboAReconduire, $resultSql->fk_contratabonnement);

					$startday = dol_mktime(12, 0 , 0, substr($resultSql->datedebutperiode,5,2), substr($resultSql->datedebutperiode,8,2), substr($resultSql->datedebutperiode,0,4));
					$endday = dol_mktime(12, 0 , 0, substr($resultSql->datefinperiode,5,2), substr($resultSql->datefinperiode,8,2), substr($resultSql->datefinperiode,0,4));
					$prixttc = $resultSql->montantperiode + $resultSql->montantperiode * ($resultSql->tva_tx/100);
					if ($resultSql->fk_product == null || $resultSql->fk_product=='') {$resultSql->fk_product = 0;}
					if ($resultSql->fk_product_type == null || $resultSql->fk_product_type == '') {$resultSql->fk_product_type = 0;}

					$qty = $resultSql->qty;
					$result = $facture->addline($resultSql->description, $resultSql->montantperiode / $qty, $qty, $resultSql->tva_tx, $resultSql->localtax1_tx,
						$resultSql->localtax2_tx, $resultSql->fk_product, $resultSql->remise_percent,
						$startday, $endday, 0, 0, '', $resultSql->price_base_type, $prixttc / $qty, $resultSql->fk_product_type,
                                               -1, 0, '', 0, 0, null, $resultSql->pa_ht);

					//Coche comme facturé si demandé
					if ($_POST['cochecommefacture']) {
						$objContratAboTerm = new Contratabonnement_term($db);
						$objContratAboTerm->fetch($_POST['aFacturer'][$i]);
						$objContratAboTerm->facture=1;
						$result = $objContratAboTerm->update($users);
						if ($result < 0) { $error++; dol_print_error($db,$objContratAboTerm->error); }
					}
				}
				else{
					dol_print_error($db);
					$error=1;
				}
			}
		}
	}

	//Reconductions
	if(file_exists('facturer_reconduction.php') && !$error){
		include('facturer_reconduction.php');
	}

	// Fin creation facture, on l'affiche
	if ($facid > 0 && ! $error)	{
		$db->commit();
		Header('Location: '.DOL_URL_ROOT.'/compta/facture/card.php?facid='.$facid);
		exit;
	}
	else {
		$db->rollback();
		$_GET["action"]='create';
		$_GET["id"]=$_POST["id"];
		dol_htmloutput_mesg($facture->error, null, 'error');
	}
}


/*
 * View
 */

llxHeader('',$langs->trans('Bill'),'EN:Customers_Invoices|FR:Factures_Clients|ES:Facturas_a_clientes');

?>
<script language="javascript" type="text/javascript">
	function checkAll() {
		jQuery(".checkElement").attr('checked', true);
	}

	function checkNone() {
		jQuery(".checkElement").attr('checked', false);
	}
</script>
<?php


$html = new Form($db);
$formfile = new FormFile($db);

/*********************************************************************
 *
 * Mode creation
 *
 **********************************************************************/
if ($_GET['action'] == 'create' && $_GET['id']) {
	$facturestatic=new Facture($db);
	print_fiche_titre($langs->trans('NewSubBill'));
	if ($mesg) print $mesg;

	require_once(DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php');
	$object = new contrat($db);
	$object->fetch($_GET['id']);
	$object->fetch_thirdparty();
	$soc = $object->thirdparty;
	$projectid			= (!empty($object->fk_project)?$object->fk_project:'');
	$ref_client			= (!empty($object->ref_client)?$object->ref_client:'');
	$cond_reglement_id 	= (!empty($object->cond_reglement_id)?$object->cond_reglement_id:(!empty($soc->cond_reglement_id)?$soc->cond_reglement_id:1));
	$mode_reglement_id 	= (!empty($object->mode_reglement_id)?$object->mode_reglement_id:(!empty($soc->mode_reglement_id)?$soc->mode_reglement_id:0));
	$fk_account         = (! empty($object->fk_account)?$object->fk_account:(! empty($soc->fk_account)?$soc->fk_account:0));
    $remise_percent 	= (!empty($object->remise_percent)?$object->remise_percent:(!empty($soc->remise_percent)?$soc->remise_percent:0));
	$remise_absolue 	= (!empty($object->remise_absolue)?$object->remise_absolue:(!empty($soc->remise_absolue)?$soc->remise_absolue:0));
	$dateinvoice		= empty($conf->global->MAIN_AUTOFILL_DATE)?-1:0;

	$absolute_discount = $soc->getAvailableDiscounts();

	print '<form name="add" action="'.$_SERVER["PHP_SELF"].'" method="post">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="socid" value="'.$soc->id.'">' ."\n";
	print '<input type="hidden" name="id" value="'.$_GET['id'].'">';
	print '<input name="facnumber" type="hidden" value="provisoire">';
	print '<table class="border" width="100%">';
	// Ref
	print '<tr><td>'.$langs->trans('Ref').'</td><td colspan="2">'.$langs->trans('Draft').'</td></tr>';
	if ($conf->global->FAC_USE_CUSTOMER_ORDER_REF) {
		print '<tr><td>'.$langs->trans('RefCustomerOrder').'</td><td>';
		print '<input type="text" name="ref_client" value="'.$ref_client.'">';
		print '</td></tr>';
	}
	// Factures predefinies
	if (empty($_GET['propalid']) && empty($_GET['commandeid']) && empty($_GET['contratid']) && empty($_GET['id']))	{
		$sql = 'SELECT r.rowid, r.titre, r.total_ttc';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'facture_rec as r';
		$sql.= ' WHERE r.fk_soc = '.$soc->id;

		$resql=$db->query($sql);
		if ($resql)	{
			$num = $db->num_rows($resql);
			$i = 0;
			if ($num > 0) {
				print '<tr><td>'.$langs->trans('CreateFromRepeatableInvoice').'</td><td>';
				print '<select class="flat" name="fac_rec">';
				print '<option value="0" selected="true"></option>';
				while ($i < $num) {
					$objp = $db->fetch_object($resql);
					print '<option value="'.$objp->rowid.'"';
					if ($_POST["fac_rec"] == $objp->rowid) print ' selected="true"';
					print '>'.$objp->titre.' ('.price($objp->total_ttc).' '.$langs->trans("TTC").')</option>';
					$i++;
				}
				print '</select></td></tr>';
			}
			$db->free($resql);
		}
		else {dol_print_error($db);}
	}

	// Tiers
	print '<tr><td class="fieldrequired">'.$langs->trans('Company').'</td><td colspan="2">';
	print $soc->getNomUrl(1);
	print '<input type="hidden" name="socid" value="'.$soc->id.'">';
	print '</td>';
	print '</tr>'."\n";

	// Type de facture
	$facids=$facturestatic->list_replacable_invoices($soc->id);
	if ($facids < 0) {
		dol_print_error($db,$facturestatic);
		exit;
	}
	$options = "";
	foreach ($facids as $facparam) {
		$options.='<option value="'.$facparam['id'].'"';
		if ($facparam['id'] == $_POST['fac_replacement']) $options.=' selected="true"';
		$options.='>'.$facparam['ref'];
		$options.=' ('.$facturestatic->LibStatut(0,$facparam['status']).')';
		$options.='</option>';
	}

	$facids=$facturestatic->list_qualified_avoir_invoices($soc->id);
	if ($facids < 0) {
		dol_print_error($db,$facturestatic);
		exit;
	}
	$optionsav="";
	foreach ($facids as $key => $value)	{
		$newinvoice=new Facture($db);
		$newinvoice->fetch($key);
		$optionsav.='<option value="'.$key.'"';
		if ($key == $_POST['fac_avoir']) $optionsav.=' selected="true"';
		$optionsav.='>';
		$optionsav.=$newinvoice->ref;
		$optionsav.=' ('.$newinvoice->getLibStatut(1,$value).')';
		$optionsav.='</option>';
	}

	print '<tr><td valign="top" class="fieldrequired">'.$langs->trans('Type').'</td><td colspan="2">';
	print '<table class="nobordernopadding">'."\n";

	// Standard invoice
	print '<tr height="18"><td width="16px" valign="middle">';
	print '<input type="radio" name="type" value="0"'.($_POST['type']==0?' checked="true"':'').'>';
	print '</td><td valign="middle">';
	$desc = $html->textwithpicto($langs->trans("InvoiceStandardAsk"),$langs->transnoentities("InvoiceStandardDesc"),1);
	print $desc;
	print '</td></tr>'."\n";

	// Deposit
	print '<tr height="18"><td width="16px" valign="middle">';
	print '<input type="radio" name="type" value="3"'.($_POST['type']==3?' checked="true"':'').'>';
	print '</td><td valign="middle">';
	$desc=$html->textwithpicto($langs->trans("InvoiceDeposit"),$langs->transnoentities("InvoiceDepositDesc"),1);
	print $desc;
	print '</td></tr>'."\n";

	// Proforma
	if ($conf->global->FACTURE_USE_PROFORMAT)
	{
		print '<tr height="18"><td width="16px" valign="middle">';
		print '<input type="radio" name="type" value="4"'.($_POST['type']==4?' checked="true"':'').'>';
		print '</td><td valign="middle">';
		$desc = $html->textwithpicto($langs->trans("InvoiceProForma"),$langs->transnoentities("InvoiceProFormaDesc"),1);
		print $desc;
		print '</td></tr>'."\n";
	}

	// Replacement
	print '<tr height="18"><td valign="middle">';
	print '<input type="radio" name="type" value="1"'.($_POST['type']==1?' checked=true':'');
	if (!$options) print ' disabled="true"';
	print '>';
	print '</td><td valign="middle">';
	$text=$langs->trans("InvoiceReplacementAsk").' ';
	$text.='<select class="flat" name="fac_replacement"';
	if (! $options) $text.=' disabled="true"';
	$text.='>';
	if ($options) {
		$text.='<option value="-1">&nbsp;</option>';
		$text.=$options;
	}
	else {
		$text.='<option value="-1">'.$langs->trans("NoReplacableInvoice").'</option>';
	}
	$text.='</select>';
	$desc=$html->textwithpicto($text,$langs->transnoentities("InvoiceReplacementDesc"),1);
	print $desc;
	print '</td></tr>'."\n";

	// Credit note
	print '<tr height="18"><td valign="middle">';
	print '<input type="radio" name="type" value="2"'.($_POST['type']==2?' checked=true':'');
	if (! $optionsav) print ' disabled="true"';
	print '>';
	print '</td><td valign="middle">';
	$text=$langs->transnoentities("InvoiceAvoirAsk").' ';
	$text.='<select class="flat" name="fac_avoir"';
	if (! $optionsav) $text.=' disabled="true"';
	$text.='>';
	if ($optionsav)
	{
		$text.='<option value="-1">&nbsp;</option>';
		$text.=$optionsav;
	}
	else
	{
		$text.='<option value="-1">'.$langs->trans("NoInvoiceToCorrect").'</option>';
	}
	$text.='</select>';
	$desc=$html->textwithpicto($text,$langs->transnoentities("InvoiceAvoirDesc"),1);
	print $desc;
	print '</td></tr>'."\n";

	print '</table>';
	print '</td></tr>';

	// Discounts for third party
	print '<tr><td>'.$langs->trans('Discounts').'</td><td colspan="2">';
	if ($soc->remise_client) print $langs->trans("CompanyHasRelativeDiscount",$soc->remise_client);
	else print $langs->trans("CompanyHasNoRelativeDiscount");
	print '. ';
	if ($absolute_discount) print $langs->trans("CompanyHasAbsoluteDiscount",price($absolute_discount),$langs->trans("Currency".$conf->monnaie));
	else print $langs->trans("CompanyHasNoAbsoluteDiscount");
	print '.';
	print '</td></tr>';

	// Date invoice
	print '<tr><td class="fieldrequired">'.$langs->trans('Date').'</td><td colspan="2">';
	$html->select_date($dateinvoice,'','','','',"add",1,1);
	print '</td></tr>';

	// Payment term
	print '<tr><td nowrap>'.$langs->trans('PaymentConditionsShort').'</td><td colspan="2">';
	$html->select_conditions_paiements(isset($_POST['cond_reglement_id'])?$_POST['cond_reglement_id']:$cond_reglement_id,'cond_reglement_id');
	print '</td></tr>';

	// Payment mode
	print '<tr><td>'.$langs->trans('PaymentMode').'</td><td colspan="2">';
	$html->select_types_paiements(isset($_POST['mode_reglement_id'])?$_POST['mode_reglement_id']:$mode_reglement_id,'mode_reglement_id');
	print '</td></tr>';

    // Bank Account
	if (isset($_POST['fk_account'])) {
		$fk_account = $_POST['fk_account'];
	}

    print '<tr><td>' . $langs->trans('BankAccount') . '</td><td colspan="2">';
    $form->select_comptes($fk_account, 'fk_account', 0, '', 1);
    print '</td></tr>';

	// Project
	if ($conf->projet->enabled)
	{
		$formproject=new FormProjets($db);

		$langs->load('projects');
		print '<tr><td>'.$langs->trans('Project').'</td><td colspan="2">';
		$formproject->select_projects($soc->id, $projectid, 'projectid');
		print '</td></tr>';
	}

	// Modele PDF
	print '<tr><td>'.$langs->trans('Model').'</td>';
	print '<td>';
	include_once(DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php');
	$liste = ModelePDFFactures::liste_modeles($db);
	$html->selectarray('model',$liste,$conf->global->FACTURE_ADDON_PDF);
	print "</td></tr>";

	// Public note
	print '<tr><td>'.$langs->trans('NotePublic').'</td>';
    print '<td>';
    $doleditor = new DolEditor('note_public', GETPOST('note_public'), '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, 70);
    print $doleditor->Create(1);
    print '</td>';
    print '</tr>';

    // Private note
    print '<tr><td>'.$langs->trans('NotePrivate').'</td>';
    print '<td>';
    $doleditor = new DolEditor('note_private', GETPOST('note_private'), '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, 70);
    print $doleditor->Create(1);
    print '</td>';
    print '</tr>';
	print '<tr><td>'.$langs->trans('Contratabonnement').'</td><td colspan="2">'.$object->getNomUrl(1).'</td></tr>';
	//Cocher comme facturé
	print '<tr><td>';
	print $html->textwithpicto($langs->trans('Showasbilling'),$langs->transnoentities("ShowasbillingDesc"),1);
	print '</td>';
	print '<td>'.'<input name="cochecommefacture" type="checkbox" checked="checked"/>'.'</td></tr>';
	//Reconduire
	if(file_exists('facturer_reconduction.php')) {
		print '<tr><td>';
		print $html->textwithpicto($langs->trans('Renew'),$langs->transnoentities("CheckAsRenew"),1);
		print '</td>';
		print '<td>'.'<input name="cochereconduire" type="checkbox" checked="checked"/>'.'</td></tr>';
	}
	// Bouton "Brouillon"
	print '<tr><td colspan="3" align="center"><input type="submit" class="button" name="bouton" value="'.$langs->trans('CreateDraft').'"></td></tr>';
	print "</table>\n";
	$title = $langs->trans('BillingPeriods');

	$sql = "SELECT cat.datedebutperiode, cat.datefinperiode, cat.montantperiode, cat.rowid as idperiode,";
	$sql.= " ca.rowid, ca.fk_contratdet, ca.fk_frequencerepetition, ca.periodepaiement,";
	$sql.= " cd.fk_product, cd.description,";
	$sql.= " p.label as product, p.ref, p.fk_product_type, p.rowid as prodid";
	$sql.= " FROM ".MAIN_DB_PREFIX."contratabonnement as ca";
	$sql.= " INNER JOIN ".MAIN_DB_PREFIX."contratdet as cd";
	$sql.= " ON cd.rowid = ca.fk_contratdet";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p";
	$sql.= " ON p.rowid = cd.fk_product";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."contratabonnement_term as cat ON cat.fk_contratabonnement = ca.rowid";
	$sql.= " WHERE cat.facture=0 AND ca.statut = 1 AND cd.fk_contrat = ".$object->id;
	$sql.= " ORDER BY ca.rowid";
	print '<br>';
	print_titre($title);

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	if ($conf->use_javascript_ajax) {print '<td align="left" width="80"><a onClick="checkAll()">'.$langs->trans("All").'</a> / <a onClick="checkNone()">'.$langs->trans("None").'</a></td>';}
	else {print '<td align="left" width="80">'.$langs->trans('Sel.').'</td>';}
	print '<td align="left">'.$langs->trans('Ref').'</td>';
	print '<td>'.$langs->trans('Description').'</td>';
	print '<td align="center">'.$langs->trans('Period').'</td>';
	print '<td align="right">'.$langs->trans('Amount').'</td></tr>';

	// Lignes
	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		$var=True;
		while ($i < $num) {
			$objp = $db->fetch_object($resql);
			$var=!$var;
			print '<tr '.$bc[$var].'>';
			//Checkbox
			print '<td>';
			if ($rowidAvant != $objp->rowid && ($objp->periodepaiement == 0 || ($objp->periodepaiement == 1 && strtotime($objp->datedebutperiode) <= time() && strtotime($objp->datefinperiode) >= time()))) {
				print '<input name="aFacturer[]" class="checkElement" value='.$objp->idperiode.' type="checkbox" checked="checked" />';
			}
			else {print '<input name="aFacturer[]" class="checkElement" value='.$objp->idperiode.' type="checkbox"/>';}
			$rowidAvant = $objp->rowid;
			print '</td>';
			//Ref
			if($objp->fk_product != $prodidAvant || $objp->description != $descAvant){
				print '<td align="left">';
				if (($objp->info_bits & 2) == 2)
				{
					print '<a href="'.DOL_URL_ROOT.'/comm/remx.php?id='.$propal->socid.'">';
					print img_object($langs->trans("ShowReduc"),'reduc').' '.$langs->trans("Discount");
					print '</a>';
				}
				else if ($objp->prodid)
				{
					print '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$objp->prodid.'">';
					print ($objp->fk_product_type == 1 ? img_object($langs->trans(''),'service') : img_object($langs->trans(''),'product'));
					print ' '.$objp->ref.'</a>';
					print $objp->product?' - '.$objp->product:'';

					// Dates
					if ($date_start || $date_end)
					{
						print_date_range($date_start,$date_end);
					}
				}
				else
				{
					print ($objp->product_type == -1 ? '&nbsp;' : ($objp->product_type == 1 ? img_object($langs->trans(''),'service') : img_object($langs->trans(''),'product')));
					// Dates
					if ($date_start || $date_end)
					{
						print_date_range($date_start,$date_end);
					}
				}
				print '</td>'."\n";
				//Description
				print '<td>';
				if ($objp->description)
				{
					if ($objp->description == '(CREDIT_NOTE)')
					{
						$discount=new DiscountAbsolute($db);
						$discount->fetch($objp->fk_remise_except);
						print $langs->transnoentities("DiscountFromCreditNote",$discount->getNomUrl(0));
					}
					elseif ($obj->description == '(DEPOSIT)')
					{
						$discount=new DiscountAbsolute($db);
						$discount->fetch($objp->fk_remise_except);
						print $langs->transnoentities("DiscountFromDeposit",$discount->getNomUrl(0));
					}
					else
					{
						print dol_trunc($objp->description,60);
					}
				}
				else
				{
					print '&nbsp;';
				}
				print '</td>';
			}else{
				print '<td></td><td></td>'; //Affiche lignes vides pour pas surcharger
			}
			$prodidAvant = $objp->fk_product;
			$descAvant = $objp->description;

			//Periodes
			print '<td align="center">'.dateUsVersDateFr($objp->datedebutperiode).' '.$langs->trans("to").' '.dateUsVersDateFr($objp->datefinperiode).'</td>';
			//Montants
			print '<td align="right">'.price($objp->montantperiode).'</td>';
			print '</tr>';
			$i++;
		}
	}
	else
	{
		dol_print_error($db);
	}


	print '</table>';
	print "</form>\n";



}

function dateUsVersDateFr($date){
	return substr($date,8,2).'/'.substr($date,5,2).'/'.substr($date,0,4);
}


llxFooter();
$db->close();
?>
