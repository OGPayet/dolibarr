<?php
/* Copyright (C) 2014-2017	Charlie Benke	 <charles.fr@benke.fr>
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
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
require_once DOL_DOCUMENT_ROOT."/core/lib/date.lib.php";

require_once DOL_DOCUMENT_ROOT.'/core/lib/fourn.lib.php';
require_once DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php";

dol_include_once('/equipement/class/equipement.class.php');
dol_include_once('/equipement/core/lib/equipement.lib.php');

if (! empty($conf->global->EQUIPEMENT_ADDON)
	&& is_readable(dol_buildpath("/equipement/core/modules/equipement/".$conf->global->EQUIPEMENT_ADDON.".php")))
	dol_include_once("/equipement/core/modules/equipement/".$conf->global->EQUIPEMENT_ADDON.".php");


$langs->load("equipement@equipement");
$langs->load("orders");
$langs->load("suppliers");
$langs->load("companies");
$langs->load('stocks');

$id=GETPOST('id', 'int');
$action = GETPOST('action', 'alpha');
// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'fournisseur', $id, '', 'commande');

/*
 *	View
 */

$form = new Form($db);
llxHeader();


$object = new CommandeFournisseur($db);
$result = $object->fetch($id);
$object->fetch_thirdparty();


if ($action  == 'addequipement') {
	$objectequipement = new equipement($db);
	$num = count($object->lines);
	$nbligneorder = 0;
	while ($nbligneorder <	$num) {
		$line =	$object->lines[$nbligneorder];
		// only recept on serial product
		if ($line->fk_product > 0) {
			// on regarde si il y a des équipement à créer (qty > O)
			if (GETPOST('quantity-'.$line->id)) {
				$objectequipement->fk_product		= $line->fk_product;
				$objectequipement->fk_soc_fourn 	= $object->thirdparty->id;
				$objectequipement->fk_soc_client 	= $idMeteoOmnium;
				$objectequipement->author			= $user->id;
				$objectequipement->description		= $langs->trans("SupplierOrder").":".$object->ref;
//				$objectequipement->ref				= $ref;
				$objectequipement->fk_entrepot		= GETPOST('fk_entrepot-'.$line->id, 'alpha');
				$datee = dol_mktime(
								'23', '59', '59',
								$_POST["datee-".$line->id."month"],
								$_POST["datee-".$line->id."day"],
								$_POST["datee-".$line->id."year"]
				);
				$objectequipement->datee			= $datee;
				$dateo = dol_mktime(
								'23', '59', '59',
								$_POST["dateo-".$line->id."month"],
								$_POST["dateo-".$line->id."day"],
								$_POST["dateo-".$line->id."year"]
				);
				$objectequipement->dateo			= $dateo;
				// selon le mode de sérialisation de l'équipement
				switch(GETPOST('SerialMethod-'.$line->id, 'int')) {
					case 1 : // en mode génération auto, on crée des numéros série interne
						$objectequipement->quantity 		= 1;
						$objectequipement->nbAddEquipement	= GETPOST('quantity-'.$line->id, 'int');;
						break;
					case 2 : // en mode génération à partir de la liste on détermine en fonction de la saisie
						$objectequipement->quantity 		= 1;
						$objectequipement->nbAddEquipement	= 0; // sera calculé en fonction
						break;
					case 3 : // en mode gestion de lot
						$objectequipement->quantity 		= GETPOST('quantity-'.$line->id, 'int');
						$objectequipement->nbAddEquipement	= 1;
						break;
				}

				$objectequipement->SerialMethod 	= GETPOST('SerialMethod-'.$line->id, 'int');
				$objectequipement->SerialFourn		= GETPOST('SerialFourn-'.$line->id, 'alpha');
				$objectequipement->numversion		= GETPOST('numversion-'.$line->id, 'alpha');
				//var_dump($objectequipement);
				$result = $objectequipement->create();
			}
		}
		$nbligneorder++;
	}
	$mesg='<div class="ok">'.$langs->trans("EquipementAdded").'</div>';
	$action="";
}


$head = ordersupplier_prepare_head($object);
dol_fiche_head($head, 'equipement', $langs->trans("SupplierOrder"), 0, 'order');
dol_htmloutput_mesg($mesg);
print '<table class="border" width="100%">';

$linkback = '<a href="'.DOL_URL_ROOT.'/fourn/commande/list.php'.(! empty($socid)?'?socid='.$socid:'').'">';
$linkback.= $langs->trans("BackToList").'</a>';

// Ref
print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
print '<td colspan="2">';
print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref');
print '</td>';
print '</tr>';

// Ref supplier
print '<tr><td width="20%">'.$langs->trans("RefSupplier").'</td>';
print '<td colspan="2">';
print $object->ref_supplier;
print '</td></tr>';


// Fournisseur
print '<tr><td>'.$langs->trans("Supplier")."</td>";
print '<td colspan="2">'.$object->thirdparty->getNomUrl(1, 'supplier').'</td>';
print '</tr>';


// Statut
print '<tr>';
print '<td>'.$langs->trans("Status").'</td>';
print '<td colspan="2">';
print $object->getLibStatut(4);
print "</td></tr>";
print "</table>";


dol_fiche_end();

// on récupère les produit associé à la commande fournisseur
print '<form name="equipement" action="'.$_SERVER['PHP_SELF'].'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="addequipement">';
print '<input type="hidden" name="id" value="'.$id.'">';
print '<table id="tablelines" class="noborder noshadow" width="100%">';

$num = count($object->lines);
$i = 0;	$total = 0;

if ($num) {
	print '<tr class="liste_titre">';
	print '<td align="left" width="200">'.$langs->trans('Label').'</td>';
	print '<td align="right" width="75">'.$langs->trans('Qty').'</td>';
	print '<td align="center" width="150">'.$langs->trans('EquipmentSerialMethod').'</td>';
	print '<td align="left" width="250">'.$langs->trans('ExternalSerial').'</td>';
	print '<td align="left" width="50">'.$langs->trans('Quantity').'</td>';
	print '<td align="left" width="100">'.$langs->trans('VersionNumber').'</td>';
	print '<td align="left" width="100">'.$langs->trans('EntrepotStock').'</td>';
	print '<td align="right" width="100">'.$langs->trans('Dateo').'</td>';
	print '<td align="right" width="100">'.$langs->trans('Datee').'</td>';
	print "</tr>\n";
}
$var=true;
while ($i <	$num) {
	$line =	$object->lines[$i];
	// only recept on serial product
	if ($line->fk_product > 0) {
		$var=!$var;
		// Show product and description
		$type=(! empty($line->product_type)?$line->product_type:(! empty($line->fk_product_type)?$line->fk_product_type:0));
		print '<tr '.$bc[$var].'>';
		// Show product and description
		print '<td valign=top>';

		print '<input type=hidden name="fk_product['.$line->id.']" value="'.$line->fk_product.'">';
		$product_static=new ProductFournisseur($db);
		$product_static->fetch($line->fk_product);
		$text=$product_static->getNomUrl(1, 'supplier');
		$text.= ' - '.$product_static->libelle;
		$description=($conf->global->PRODUIT_DESC_IN_FORM?'':dol_htmlentitiesbr($line->description));
		print $form->textwithtooltip($text, $description, 3, '', '', $i);

		// Show range
		print_date_range($date_start, $date_end);

		// Add description in form
		if (! empty($conf->global->PRODUIT_DESC_IN_FORM))
			print ($line->description && $line->description!=$product_static->libelle)?'<br>'.dol_htmlentitiesbr($line->description):'';

		print '<td  valign=top align="right" class="nowrap">'.$line->qty.'</td>';
		print '<td  valign=top align="center" >';
		$arraySerialMethod=array(
						'1'=>$langs->trans("InternalSerial"),
						'2'=>$langs->trans("ExternalSerial"),
						'3'=>$langs->trans("SeriesMode")
		);
		print $form->selectarray("SerialMethod-".$line->id, $arraySerialMethod, $conf->global->EQUIPEMENT_DEFAULTSERIALMODE);
		print '</td>';
		print '<td>';
		print '<textarea name="SerialFourn-'.$line->id.'" cols="50" rows="'.ROWS_3.'"></textarea>';
		print '</td>';
		print '<td  valign=top><input type=text name="quantity-'.$line->id.'" size=2 value="'.$line->qty.'"></td>';

		print '<td  valign=top><input type=text name="numversion-'.$line->id.'" value=""></td>';
		print '<td  valign=top>';
		print select_entrepot("", 'fk_entrepot-'.$line->id, 1, 1).'</td>';

		// Date open
		print '<td  valign=top align=right>';
		print $form->select_date(
						'', 'dateo-'.$line->id, 0, 0, '', 'dateo['.$line->id.']'
		).'</td>'."\n";

		// Date end
		print '<td  valign=top	align=right>';
		print $form->select_date(
						'', 'datee-'.$line->id, 0, 0, 1, 'datee['.$line->id.']'
		).'</td>'."\n";
		print '</tr>';
	}
	$i++;
}
print '</table>';

print '<div class="tabsAction">';
print '<input type="submit" class="button" value="'.$langs->trans("AddEquipement").'">';
print '</div>';
print '</form>';

llxFooter();
$db->close();