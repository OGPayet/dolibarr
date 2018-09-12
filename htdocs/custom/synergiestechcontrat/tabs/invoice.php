<?php
/* Copyright (C) 2012-2016	  Charlie Benke	<charlie@patas-monkey.com>
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
 *	\file	   htdocs/equipement/tabs/contrat.php
 *	\brief	  List of all Events of equipements associated with a contract
 *	\ingroup	equipement
 */
$res=@include("../../main.inc.php");					// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../../main.inc.php");		// For "custom" directory

require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/product/stock/class/entrepot.class.php");
require_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/contract.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");

$langs->load("companies");
$langs->load("synergiestechcontrat@synergiestechcontrat");
$langs->load("contracts");

$id=GETPOST('id', 'int');

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user, 'contrat', $id);

$page = GETPOST('page', 'int');
if ($page == -1) {
	$page = 0;
}
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="f.datec";


$limit = $conf->liste_limit;

/*
 *	View
 */

$form = new Form($db);
llxHeader();


$object = new contrat($db);
$result = $object->fetch($id);
$object->fetch_thirdparty();

$head = contract_prepare_head($object);

dol_fiche_head($head, 'invoice', $langs->trans("Contract"), 0, 'contract');

print '<table class="border" width="100%">';

// Ref
print '<tr><td width="25%">'.$langs->trans("Ref").'</td><td>';
print $form->showrefnav($object, 'ref', '', 1, 'ref', 'ref');
print '</td></tr>';

// Societe
print "<tr><td>".$langs->trans("Company")."</td><td>".$object->thirdparty->getNomUrl(1)."</td></tr>";

print "</table><br>";


$sql = "SELECT";
$sql .= " f.rowid";
$sql .= " FROM ".MAIN_DB_PREFIX."element_element as e";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."facture as f ON f.rowid = e.fk_target";
$sql .= " WHERE e.targettype = 'facture'";
$sql .= " AND e.sourcetype = 'contrat'";
$sql .= " AND e.fk_source = ".$id;
$sql .= " ORDER BY ".$sortfield." ".$sortorder;
$sql .= $db->plimit($limit+1, $offset);

$result=$db->query($sql);
if ($result) {
	$num = $db->num_rows($result);

	$facturestatic=new Facture($db);


	print_barre_liste($langs->trans("ListOfInvoices"), $page, "", $urlparam, $sortfield, $sortorder, '', $num);

	print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">'."\n";
	print '<input type="hidden" class="flat" name="id" value="'.$id.'">';
	print '<table class="noborder" width="100%">';

	print "<tr class='liste_titre'>";
	print "<th class='liste_titre'>".$langs->trans("Ref")."</th>";
	print "<th class='liste_titre'>".$langs->trans("RefCustomer")."</th>";
	print "<th class='liste_titre'>".$langs->trans("Description")."</th>";
	print "<th class='liste_titre' align='center'>".$langs->trans("DateInvoice")."</th>";
	print "<th class='liste_titre' align='center'>".$langs->trans("DateDue")."</th>";
	print "<th class='liste_titre'>".$langs->trans("PaymentMode")."</th>";
	print "<th class='liste_titre' align='right'>".$langs->trans("AmountHT")."</th>";
	print "<th class='liste_titre' align='right'>".$langs->trans("Status")."</th>";
	print "</tr>\n";

	$var=True;
	$total = 0;
	$i = 0;
	while ($i < min($num, $limit)) {
		$objp = $db->fetch_object($result);

		$facturestatic->fetch($objp->rowid);
        $paiement = $facturestatic->getSommePaiement();
		$facturestatic->getLinesArray();

		$var=!$var;
		print "<tr $bc[$var]>";

		//Ref
		print "<td>";
		print $facturestatic->getNomUrl(1);
		print "</td>";

		//Ref client
		print '<td class="nowrap">';
		print $facturestatic->ref_client;
		print '</td>';

		print '<td class="nowrap">';
		foreach($facturestatic->lines as $line) {
			if ($line->fk_product > 0) {
				$product_static = new Product($db);
				$product_static->fetch($line->fk_product);

				$text=$product_static->getNomUrl(1);
				$text.= ' - '.$product_static->label;

				$description=dol_htmlentitiesbr($line->desc);

				print $text."<br/>".$description."<br/>";
			}
		}
		print '</td>';

		//Date
		print '<td align="center" class="nowrap">';
		print dol_print_date($facturestatic->date,'day');
		print '</td>';

		// Date limit
		print '<td align="center" class="nowrap">'.dol_print_date($facturestatic->date_lim_reglement,'day');
		if ($facturestatic->hasDelay())
		{
			print img_warning($langs->trans('Late'));
		}
		print '</td>';

        // Payment mode
		print '<td>';
		$form->form_modes_reglement($_SERVER['PHP_SELF'], $facturestatic->mode_reglement_id, 'none', '', -1);
		print '</td>';

		//Amount HT
		print '<td align="right">'.price($facturestatic->total_ht)."</td>\n";

		//Status
		print '<td align="right" class="nowrap">';
		print $facturestatic->LibStatut($facturestatic->paye,$facturestatic->fk_statut,5,$paiement,$facturestatic->type);
		print "</td>";

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