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
 *	\file       /contratabonnement/imprimer.php
 *	\ingroup    contract
 *	\brief      Page to print subscriptions
 */

$res=@include("../main.inc.php");					// For root directory
if (! $res) $res=@include("../../main.inc.php");	// For "custom" directory

require 'class/contratabonnement_term.class.php';

require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php');
require_once(DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php');
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once(DOL_DOCUMENT_ROOT.'/core/class/discount.class.php');
require_once(DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php');
require_once(DOL_DOCUMENT_ROOT."/core/lib/functions2.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php');
require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");
if ($conf->projet->enabled)   require_once(DOL_DOCUMENT_ROOT.'/projet/class/project.class.php');
if ($conf->projet->enabled)   require_once(DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php');

$langs->load('bills');
$langs->load('companies');
$langs->load('products');
$langs->load('main');
$langs->load("contratabonnement@contratabonnement");
$sall=isset($_GET['sall'])?trim($_GET['sall']):trim($_POST['sall']);
$mesg=isset($_GET['mesg'])?$_GET['mesg']:'';
$projectid=isset($_GET['projectid'])?$_GET['projectid']:0;

if ((isset($_GET["type"]) && $_GET["type"] == "supplier") || (isset($_POST["type"]) && $_POST["type"] == "supplier")) {$type = "supplier";}
else {$type = "contract";}
/******************************************************************************/
/*                     Actions                                                */
/******************************************************************************/



/*
/*
 * Insert new invoice in database
 */
if ($_POST['action'] == 'add' && $user->rights->facture->creer) {
	$db->begin();
	$facture = new Facture($db);
	$facture->socid = $_POST['socid'];
	$facture->socid 		 = $_POST['socid'];
	$facture->type           = $_POST['type'];
	$facture->number         = $_POST['facnumber'];
	$facture->date           = time();
	$facture->note_public    = trim($_POST['note_public']);
	$facture->note           = trim($_POST['note']);
	$facture->ref_client     = $_POST['ref_client'];
	$facture->modelpdf       = 'generic_invoice_odt:'.dol_buildpath('/contratabonnement/tpl/contratabonnement.odt');
	$facture->amount            = $_POST['amount'];
	$facture->remise_absolue    = $_POST['remise_absolue'];
	$facture->remise_percent    = $_POST['remise_percent'];
	//
	$facid = $facture->create($user);
	//Add lines
	$countAFacturer = count($_POST['aFacturer']);
	if ($countAFacturer <= 0) {
		$error=1;
	}
	else {
		$listId="";
		for ($i = 0; $i < $countAFacturer; $i++) {
			$listId.= $_POST['aFacturer'][$i]." OR ca.rowid=";
		}
		$listId  = substr($listId,0 ,-12);
		$sql = "SELECT cat.rowid as rowcat, cd.fk_product, cd.tva_tx, cd.description, cd.localtax1_tx, cd.localtax2_tx, cd.remise_percent, cd.qty,";
		$sql.= " cat.datedebutperiode, cat.datefinperiode, cat.montantperiode, cat.facture,";
		$sql.= " p.price_base_type, p.fk_product_type";
		$sql.= " FROM ".MAIN_DB_PREFIX."contratabonnement_term as cat";
		$sql.= " INNER JOIN ".MAIN_DB_PREFIX."contratabonnement as ca";
		$sql.= " ON ca.rowid = cat.fk_contratabonnement";
        if ($type == "supplier") {
            $sql.= " INNER JOIN ".MAIN_DB_PREFIX."commande_fournisseurdet as cd";
            $sql.= " ON cd.rowid = ca.fk_commandefournisseurdet";
        }
        else {
            $sql.= " INNER JOIN ".MAIN_DB_PREFIX."contratdet as cd";
            $sql.= " ON cd.rowid = ca.fk_contratdet";
        }
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p";
		$sql.= " ON p.rowid = cd.fk_product";
		$sql.= " WHERE ca.rowid = ".$listId;
		$resql=$db->query($sql);
		$numresql = $db->num_rows($resql);
		if ($numresql <= 0) {dol_print_error($db); $error = 1;}
		for($i = 0 ; $i < $numresql ; $i++) {
			$resultSql = $db->fetch_object($resql);
			$startday = dol_mktime(12, 0 , 0, substr($resultSql->datedebutperiode,5,2), substr($resultSql->datedebutperiode,8,2), substr($resultSql->datedebutperiode,0,4));
			$endday = dol_mktime(12, 0 , 0, substr($resultSql->datefinperiode,5,2), substr($resultSql->datefinperiode,8,2), substr($resultSql->datefinperiode,0,4));
			$prixttc = $resultSql->montantperiode; // + $resultSql->montantperiode * ($resultSql->tva_tx/100);   TVA déjà appliquée ?
			$qty = $resultSql->qty;
			if ($resultSql->fk_product_type == null || $resultSql->fk_product_type == '') {$resultSql->fk_product_type=0;}
			if (empty($resultSql->price_base_type)) {$resultSql->price_base_type = "HT";}
			if ($resultSql->facture == 1) {
				$tradInvoiced = $langs->trans("Invoiced");
				$tradInvoiced = str_replace("&eacute;", "é", $tradInvoiced); // Etrange mais c'est comme ça ...
				$resultSql->description .= " (".$tradInvoiced.")";
			}

			$result = $facture->addline($resultSql->description, $resultSql->montantperiode / $qty, $qty, $resultSql->tva_tx, $resultSql->localtax1_tx, $resultSql->localtax2_tx,
				$resultSql->fk_product, $resultSql->remise_percent, $startday, $endday, 0, 0, '', $resultSql->price_base_type, $prixttc / $qty, $resultSql->fk_product_type);
			if ($result <= 0) {dol_print_error($db);$error = 1;}
		}
	}

	// Fin creation facture, génération et affichage
	if ($facid > 0 && ! $error) {
		//Génération du pdf
		if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id')) $newlang=GETPOST('lang_id');
		if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->thirdparty->default_lang;
		if (! empty($newlang)) {$outputlangs = new Translate("",$conf); $outputlangs->setDefaultLang($newlang);}

		// Champs additionnels
		$facture->fetch_thirdparty();
		$facture->fetch_lines();
		$facture->contract_ref = $_POST['contract_ref'];

        $result = $facture->generateDocument($facture->modelpdf, $langs);
		if ($result <= 0) {dol_print_error($db,$result);exit;}

		$dll = DOL_URL_ROOT . '/document.php?modulepart=facture&file='.$facture->ref.'/'.$facture->ref.'_contratabonnement.odt';
		Header('Location: '.$dll);
	}
	else {
        llxHeader('',$langs->trans('Bill'),'EN:Customers_Invoices|FR:Factures_Clients|ES:Facturas_a_clientes');
		$_GET["action"] = 'create';
		$_GET["id"] = $_POST["id"];
		dol_htmloutput_mesg('$langs->trans("ErrorPrinting")', null, 'error');
	}
	$db->rollback();
}
else {

llxHeader('',$langs->trans('Bill'),'EN:Customers_Invoices|FR:Factures_Clients|ES:Facturas_a_clientes');
}


/*
 * View
 */


$html = new Form($db);
$formfile = new FormFile($db);

/*********************************************************************
 *
 * Mode creation
 *
 **********************************************************************/
if ($_GET['action'] == 'create' && $_GET['id']) {

    $facturestatic=new Facture($db);
	print_fiche_titre($langs->trans('PrintingSub'));
	if ($mesg) print $mesg;

	require_once(DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php');
	if ($type == "supplier") {
        $object = new CommandeFournisseur($db);
    }
    else {
        $object = new contrat($db);
    }

	$object->fetch($_GET['id']);
	$object->fetch_thirdparty();
	$soc = $object->thirdparty;

	$projectid			= (!empty($object->fk_project)?$object->fk_project:'');
	$ref_client			= (!empty($object->ref_client)?$object->ref_client:'');
	$cond_reglement_id 	= (!empty($object->cond_reglement_id)?$object->cond_reglement_id:(!empty($soc->cond_reglement_id)?$soc->cond_reglement_id:1));
	$mode_reglement_id 	= (!empty($object->mode_reglement_id)?$object->mode_reglement_id:(!empty($soc->mode_reglement_id)?$soc->mode_reglement_id:0));
	$remise_percent 	= (!empty($object->remise_percent)?$object->remise_percent:(!empty($soc->remise_percent)?$soc->remise_percent:0));
	$remise_absolue 	= (!empty($object->remise_absolue)?$object->remise_absolue:(!empty($soc->remise_absolue)?$soc->remise_absolue:0));
	$dateinvoice		= empty($conf->global->MAIN_AUTOFILL_DATE)?-1:0;

	print '<form name="add" action="'.$_SERVER["PHP_SELF"].'" method="post">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="type" value="'.$_GET["type"].'">';
	print '<input type="hidden" name="socid" value="'.$soc->id.'">' ."\n";
	print '<input type="hidden" name="contract_ref" value="'.$object->ref.'">' ."\n";
	print '<input type="hidden" name="id" value="'.$_GET['id'].'">';
	print '<input name="facnumber" type="hidden" value="provisoire">';
	print '<table class="border" width="100%">';

	// Tiers
	print '<tr><td width="200" class="fieldrequired">'.$langs->trans('Company').'</td><td colspan="2">';
	print $soc->getNomUrl(1);
	print '<input type="hidden" name="socid" value="'.$soc->id.'">';
	print '</td>';
	print '</tr>'."\n";
	print '<tr><td>'.$langs->trans('Contratabonnement').'</td><td colspan="2">'.$object->getNomUrl(1).'</td></tr>';
	// Bouton "Print"
	print '<tr><td colspan="3" align="center"><input type="submit" class="button" name="bouton" value="'.$langs->trans('Print').'"></td></tr>';
	print "</table>\n";
	$sql = "SELECT ca.rowid, ca.fk_contratdet, ca.fk_frequencerepetition,";
	$sql.= " cd.fk_product, cd.description,";
	$sql.= " p.label as product, p.ref, p.fk_product_type, p.rowid as prodid";
	$sql.= " FROM ".MAIN_DB_PREFIX."contratabonnement as ca";
	if ($type == "supplier") {
        $sql.= " INNER JOIN ".MAIN_DB_PREFIX."commande_fournisseurdet as cd";
	    $sql.= " ON cd.rowid = ca.fk_commandefournisseurdet";
    }
    else {
        $sql.= " INNER JOIN ".MAIN_DB_PREFIX."contratdet as cd";
	    $sql.= " ON cd.rowid = ca.fk_contratdet";
    }
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p";
	$sql.= " ON p.rowid = cd.fk_product";
    if ($type == "supplier") {
	   $sql.= " WHERE ca.statut = 1 AND cd.fk_commande = ".$object->id;
    }
    else {
        $sql.= " WHERE ca.statut = 1 AND cd.fk_contrat = ".$object->id;
    }
	print '<br>';
	print_titre($langs->trans('SubToPrint'));

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td align="left">'.$langs->trans('Sel.').'</td>';
	print '<td align="left">'.$langs->trans('Ref').'</td>';
	print '<td align="left">'.$langs->trans('Description').'</td>';
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
			print '<input name="aFacturer[]" value='.$objp->rowid.' type="checkbox" checked="checked" />';
			print '</td>';
			//Ref
			print '<td align="left">';
			if (($objp->info_bits & 2) == 2) {
				print '<a href="'.DOL_URL_ROOT.'/comm/remx.php?id='.$propal->socid.'">';
				print img_object($langs->trans("ShowReduc"),'reduc').' '.$langs->trans("Discount");
				print '</a>';
			}
			else if ($objp->prodid)	{
				print '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$objp->prodid.'">';
				print ($objp->fk_product_type == 1 ? img_object($langs->trans(''),'service') : img_object($langs->trans(''),'product'));
				print ' '.$objp->ref.'</a>';
				print $objp->product?' - '.$objp->product:'';
				// Dates
				if ($date_start || $date_end) {print_date_range($date_start,$date_end);}
			}
			else {
				print ($objp->product_type == -1 ? '&nbsp;' : ($objp->product_type == 1 ? img_object($langs->trans(''),'service') : img_object($langs->trans(''),'product')));
				if ($date_start || $date_end) {print_date_range($date_start,$date_end);}
			}
			print '</td>';
			print '<td align="left">';
				print dol_trunc($objp->description,60);
			print '</td>'."\n";

			print '</tr>';
			$i++;
		}
	}
	else {
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
