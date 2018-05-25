<?php
/* Copyright (C) 2013-2017	Charlie Benke	<charlie@patas-monkey.com>
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
 *	\file	   htdocs/equipement/tabs/expeditionAdd.php
 *	\brief	  List of Equipement for join Events with an expedition
 *	\ingroup	equipement
 */
$res=@include("../../main.inc.php");					// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../../main.inc.php");		// For "custom" directory

require_once DOL_DOCUMENT_ROOT."/product/class/product.class.php";
require_once DOL_DOCUMENT_ROOT."/product/stock/class/entrepot.class.php";
require_once DOL_DOCUMENT_ROOT."/expedition/class/expedition.class.php";
require_once DOL_DOCUMENT_ROOT."/fichinter/class/fichinter.class.php";
require_once DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php";
require_once DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.facture.class.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/date.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/fichinter.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/sendings.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php";

dol_include_once('/equipement/class/equipement.class.php');
dol_include_once('/equipement/core/lib/equipement.lib.php');

$langs->load("companies");
$langs->load("equipement@equipement");
$langs->load("interventions");

$origin		= GETPOST('origin', 'alpha')?GETPOST('origin', 'alpha'):'expedition';   // Example: commande, propal
$origin_id 	= GETPOST('id', 'int')?GETPOST('id', 'int'):'';
if (empty($origin_id)) $origin_id  = GETPOST('origin_id', 'int');	// Id of order or propal
if (empty($origin_id)) $origin_id  = GETPOST('object_id', 'int');	// Id of order or propal
$id = $origin_id;
$ref=GETPOST('ref', 'alpha');

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user, $origin, $origin_id);

$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');


if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="e.datec";

$action=GETPOST('action', 'alpha');


$search_ref=GETPOST('search_ref', 'alpha');
$search_refProduct=GETPOST('search_refProduct', 'alpha');
$search_company_fourn=GETPOST('search_company_fourn', 'alpha');

$search_entrepot=GETPOST('search_entrepot', 'alpha');

$search_equipevttype=GETPOST('search_equipevttype', 'alpha');
if ($search_equipevttype=="-1") $search_equipevttype="";

$object = new Expedition($db);
$result = $object->fetch($id, $ref);
if (!$id) $id=$object->id;
$object->fetch_thirdparty();

if (!empty($object->origin)) {
	$typeobject = $object->origin;
	$origin = $object->origin;
	$object->fetch_origin();
}
if ($action == 'joindre' && $user->rights->equipement->creer) {
	// récupération des équipements de type lot à joindre
	$listLot= GETPOST('lotEquipement');
	if (!empty($listLot)) {
		foreach ($listLot as $fk_product => $lotproduct) {
			//print $fk_product."<br>";
			foreach ($lotproduct as $idlot => $qtyequipement) {
				//print "prod=".$fk_product." Lot=".$idlot." Qty=".$qtyequipement."<br>";
				// si on a des choses à envoyer depuis ce lot
				if ($qtyequipement > 0) {
					// récupération de la quantité du lot
					$tblLot=explode("-", $idlot);

					if ($qtyequipement > $tblLot[1]) {	// erreur sur les quantités saisie sur le lots
						$mesg='<div class="error">'.$langs->trans("ErrorQuantityMustLower", $qtyequipement, $tblLot[1]).'</div>';
						$error++;
						setEventMessage($mesg);
						header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
						exit;
					}

					// on ajoute tous le lot à l'expédition
					$equipementstatic=new Equipement($db);
					$ret=$equipementstatic->fetch($tblLot[0]);
					$equipementstatic->fetch_thirdparty();

					if ($qtyequipement < $tblLot[1]) {	// ON découpe le lot en deux parties et on associe le nouveau
						// la réf de du lot

						$newequipid = $equipementstatic->cut_equipement($equipementstatic->ref."-".$object->ref, $qtyequipement, 1);
						// on se positionne sur l'équipement nouvellement crée
						$ret=$equipementstatic->fetch($newequipid);
						$equipementstatic->fetch_thirdparty();
					}

					// on affecte l'équipement à expédier au client à qui on l'envoie
					$equipementstatic->set_client($user, $object->socid);

					// on enlève l'équipement du stock
					$equipementstatic->set_entrepot($user, -1);

					// on cree enfin un evenement
					$desc=GETPOST('np_desc', 'alpha');
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

					$fk_contrat = GETPOST('fk_contrat');
					$fk_fichinter = GETPOST('fk_fichinter');
					$fk_project =GETPOST('fk_project');
					$fk_user_author= $user->id;
					$fk_expedition = $id;

					$total_ht =GETPOST('total_ht');

					$result=$equipementstatic->addline(
									$newequipid,
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
			}
		}
		// on redirige sur l'onglet à coté
		Header('Location: expedition.php?id='.$id);
		exit;
	}

	// récupération des équipements unitaires
	$listEquip= GETPOST('chkequipement');
	// on boucle sur les équipements sélectionnés si il y en a
	if ($listEquip !="" ) {
		foreach ($listEquip as $equipID) {
			//print "==".$equipID."<br>";
			$equipementstatic=new Equipement($db);
			$ret=$equipementstatic->fetch($equipID);
			$equipementstatic->fetch_thirdparty();

			// on affecte l'équipement à expédier au client à qui on l'envoie
			$equipementstatic->set_client($user, $object->socid);

			// on enlève l'équipement du stock
			//$equipementstatic->set_entrepot($user, -1);

			$desc=GETPOST('np_desc', 'alpha');
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

			$fk_contrat = GETPOST('fk_contrat');
			$fk_fichinter = GETPOST('fk_fichinter');
			$fk_project = GETPOST('fk_project');
			$fk_user_author = $user->id;
			$fk_expedition = $id;

			$total_ht = GETPOST('total_ht');

			$result=$equipementstatic->addline(
							$equipID,
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

			//  gestion des sous composant si il y en a
			$sql = "SELECT fk_equipement_fils FROM ".MAIN_DB_PREFIX."equipementassociation ";
			$sql.= " WHERE fk_equipement_pere=".$equipID;

			dol_syslog("Equipement/expeditionadd sql=".$sql, LOG_DEBUG);
			$resql=$db->query($sql);
			if ($resql) {
				$num = $db->num_rows($resql);
				$i = 0;
				$tblrep=array();
				while ($i < $num) {
					$objp = $db->fetch_object($resql);

					$result=$equipementstatic->addline(
									$objp->fk_equipement_fils,
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
									0		// seul le prix du parent compte
					);
					$i++;
				}
			}
		}
	}
}


/*
 *	View
 */

$form = new Form($db);
llxHeader();


$head = shipping_prepare_head($object);

dol_fiche_head($head, 'eventadd', $langs->trans("Sending"), 0, 'sending');

print '<table class="border" width="100%">';

// Ref
print '<tr><td width="25%">'.$langs->trans("Ref").'</td><td>';
print $form->showrefnav($object, 'ref', '', 1, 'ref', 'ref');
print '</td></tr>';

// Societe
print "<tr><td>".$langs->trans("Company")."</td><td>".$object->thirdparty->getNomUrl(1)."</td></tr>";

// Linked documents
if ($typeobject == 'commande' && $object->$typeobject->id && $conf->commande->enabled) {
	print '<tr><td>';
	$objectsrc=new Commande($db);
	$objectsrc->fetch($object->$typeobject->id);
	print $langs->trans("RefOrder").'</td>';
	print '<td colspan="3">';
	print $objectsrc->getNomUrl(1, 'commande');
	print "</td>\n";
	print '</tr>';
}
if ($typeobject == 'propal' && $object->$typeobject->id && $conf->propal->enabled) {
	print '<tr><td>';
	$objectsrc=new Propal($db);
	$objectsrc->fetch($object->$typeobject->id);
	print $langs->trans("RefProposal").'</td>';
	print '<td colspan="3">';
	print $objectsrc->getNomUrl(1, 'expedition');
	print "</td>\n";
	print '</tr>';
}

// Ref customer
print '<tr><td>'.$langs->trans("RefCustomer").'</td>';
print '<td colspan="3">'.$object->ref_customer."</a></td>\n";
print '</tr>';

// Date creation
print '<tr><td>'.$langs->trans("DateCreation").'</td>';
print '<td colspan="3">'.dol_print_date($object->date_creation, "day")."</td>\n";
print '</tr>';

print "</table><br>";



// on récupère les produits à expédier et l'entrepot associé
$object = new Expedition($db);
$result = $object->fetch($id, $ref);

if ($result) {
	$lines = $object->lines;
	$num_prod = count($lines);

	$equipementstatic=new Equipement($db);

	$urlparam="&amp;id=".$id;

	print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">'."\n";
	print '<input type="hidden" name="action" value="joindre">';
	print '<input type="hidden" class="flat" name="id" value="'.$id.'">';
	print '<table class="noborder" width="100%">';

	print "<tr class='liste_titre'>";
	if (! empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
		print '<td align="center"></td>';
	}
	print_liste_field_titre(
					$langs->trans("RefProduit"), $_SERVER["PHP_SELF"], "p.ref", "",
					$urlparam, '', $sortfield, $sortorder
	);
	// Entrepot source
	if ($conf->stock->enabled)
		print_liste_field_titre(
						$langs->trans("entrepot"), $_SERVER["PHP_SELF"], "sfou.nom",
						"", $urlparam, '', $sortfield, $sortorder
		);

	print_liste_field_titre(
					$langs->trans("QtyOrdered"), $_SERVER["PHP_SELF"], "", "",
					$urlparam, '', $sortfield, $sortorder
	);
	print_liste_field_titre(
					$langs->trans("QtyEquipementNeed"), $_SERVER["PHP_SELF"], "", "",
					$urlparam, '', $sortfield, $sortorder
	);

	print_liste_field_titre(
					$langs->trans("EquipementLot"), $_SERVER["PHP_SELF"], "", " align=left ",
					$urlparam, '', $sortfield, $sortorder
	);

	print_liste_field_titre(
					$langs->trans("EquipementUnitaire"), $_SERVER["PHP_SELF"], "", " align=left ",
					$urlparam, '', $sortfield, $sortorder
	);
	print '<td class="liste_titre" ></td>';
	print "</tr>\n";

	for ($i = 0 ; $i < $num_prod ; $i++) {
		// seulement si produit non libre
		if (!empty($lines[$i]->fk_product)) {
			// détermination du nombre d'équipement à transmettre
			$nbequipement = $lines[$i]->qty_shipped;
			$nbequipement-= $equipementstatic->get_nbEquipementProductExpedition($lines[$i]->fk_product, $id);

			print "<tr ".$bc[$var].">";
			if (! empty($conf->global->MAIN_VIEW_LINE_NUMBER))
				print '<td valign=top align="center">'.($i+1).'</td>';

			$prod = new Product($db);
			$prod->fetch($lines[$i]->fk_product);

			// Define output language
			$label =  $lines[$i]->product_label;
			if (! empty($conf->global->MAIN_MULTILANGS)
				&& ! empty($conf->global->PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE)) {
				$prod = new Product($db);
				$prod->fetch($lines[$i]->fk_product);
				if ( ! empty($prod->multilangs[$outputlangs->defaultlang]["libelle"]))
					$label = $prod->multilangs[$outputlangs->defaultlang]["libelle"];
			}
			print '<td valign=top>';
			print $prod->getNomUrl(2)." - ".$label ;
			print '</td>';

			// Entrepot source
			if ($conf->stock->enabled) {
				print '<td valign=top align="left">';
				if ($lines[$i]->entrepot_id > 0) {
					$entrepot = new Entrepot($db);
					$entrepot->fetch($lines[$i]->entrepot_id);
					print $entrepot->getNomUrl(1)." - ".$entrepot->lieu." (".$entrepot->cp.")";
				}
				print '</td>';
			}

			print '<td valign=top align="center">'.$lines[$i]->qty_shipped.'</td>';
			print '<td valign=top align="center">'.$nbequipement.'</td>';

			// équipement correspondant au produit et à l'entrepot d'expédition
			print '<td align="left" valign=top>';
			// si il y a des lots
			print_lotequipement($lines[$i]->fk_product, $lines[$i]->entrepot_id, $nbequipement);
			print '</td>';
			print '<td align="left" valign=top>';
			// on affiche le nombre d'équipement dispo à cocher
			print_equipementdispo($lines[$i]->fk_product, $lines[$i]->entrepot_id, $nbequipement);

			print '</td>';
			print '</tr>';
		}
		$var=!$var;
	}

	print '</table>';
	print '<br><br>';
	print '<table class="noborder" width="100%">';

	print '<tr class="liste_titre">';
	print '<td colspan=2 width=180px><a name="add"></a>'.$langs->trans('Description').'</td>'; // ancre
	print '<td width=120px align="center">'.$langs->trans('Dateo').'</td>';
	print '<td width=120px align="center" >'.$langs->trans('Datee').'</td>';
	print '<td align="left" colspan=2>'.$langs->trans('AssociatedWith').'</td>';
	print '<td colspan=2 align="right">'.$langs->trans('EquipementLineTotalHT').'</td>';

	print "</tr>\n";
	print '<tr '.$bc[$var].">\n";
	print '<td width=100px>'.$langs->trans('TypeofEquipementEvent').'</td><td>';
	print select_equipementevt_type('', 'fk_equipementevt_type', 1, 1);
	// type d'évènement
	print '</td>';

	// Date evenement début
	print '<td align="center" rowspan=2>';
	$timearray=dol_getdate(mktime());
	if (!GETPOST('deoday', 'int'))
		$timewithnohour=dol_mktime(0, 0, 0, $timearray['mon'], $timearray['mday'], $timearray['year']);
	else
		$timewithnohour=dol_mktime(
						GETPOST('deohour', 'int'), GETPOST('deomin', 'int'), 0,
						GETPOST('deomonth', 'int'), GETPOST('deoday', 'int'), GETPOST('deoyear', 'int')
		);
	$form->select_date($timewithnohour, 'deo', 1, 1, 0, "addequipevt");
	print '</td>';
	// Date evenement fin
	print '<td align="center" rowspan=2>';
	$timearray=dol_getdate(mktime());
	if (!GETPOST('deeday', 'int'))
		$timewithnohour=dol_mktime(0, 0, 0, $timearray['mon'], $timearray['mday'], $timearray['year']);
	else
		$timewithnohour=dol_mktime(
						GETPOST('deehour', 'int'), GETPOST('deemin', 'int'), 0,
						GETPOST('deemonth', 'int'), GETPOST('deeday', 'int'), GETPOST('deeyear', 'int')
		);
	$form->select_date($timewithnohour, 'dee', 1, 1, 0, "addequipevt");
	print '</td>';

	//
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
	// description de l'évènement de l'équipement
	print '<td rowspan=2 colspan=2>';
	// editeur wysiwyg
	require_once(DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php");
	$doleditor=new DolEditor(
					'np_desc', GETPOST('np_desc', 'alpha'), '', 100,
					'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_DETAILS,
					ROWS_3, 60
	);
	$doleditor->Create();
	print '</td>';

	//
	print '<td align="left">';
	print $langs->trans("Interventions");
	print '</td>';
	print '<td align="left">';
	print select_interventions('', $object->fk_soc_client, 'fk_fichinter', 1, 1);
	print '</td>';

	if ($object->fk_statut !=2) {
		print '<td align="center" rowspan=2>';
		print '<input type="submit" class="button" value="'.$langs->trans('Joindre').'" name="addline">';
		print '</td>';
	} else
		print '<td align="center" rowspan=2></td>';

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

	print '</table>';
	print "</form>\n";
	$db->free($result);
} else
	dol_print_error($db);

?>
<script>
$(document).ready(function(){

// gestion de la selection des references
$('#filterchk').keyup(function() {
	// on nettoie les case à cocher
	$('input[type=checkbox]').each(function()
	{
		// si la zone est a vide on decoche tous
		if ($('#filterchk').val().length == 0)
			this.checked = false;
	});

	// on regarde si l'id/ref correspond
	$('input[type=checkbox]').each(function()
	{
//		alert('id='+this.id);
		var currentId = this.id;
		if ($('#filterchk').val().length > 4)
		{
			if (currentId.substring(0, $('#filterchk').val().length) == $('#filterchk').val())
				this.checked = true;
			else
				this.checked = false;
		}
	});

});
	// gestion de l'expansion des div d'equipements
	$('.lotcontent').hide();
	$('.lot').click(function() {
		$(this).next('.lotcontent').toggle();
	});
});
</script>
<?php
llxFooter();
$db->close();