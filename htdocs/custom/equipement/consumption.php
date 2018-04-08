<?php
/* Copyright (C) 2012-2017	Charlie BENKE	<charlie@patas-monkey.com>
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
 *	\file		htdocs/equipement/consumption.php
 *	\brief		consumption equipement tabs
 *	\ingroup	equipement
 */

$res=@include("../main.inc.php");					// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../main.inc.php");		// For "custom" directory

require_once DOL_DOCUMENT_ROOT."/product/class/product.class.php";
require_once DOL_DOCUMENT_ROOT."/product/stock/class/entrepot.class.php";

require_once DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/date.lib.php";

dol_include_once('/equipement/core/modules/equipement/modules_equipement.php');
dol_include_once('/equipement/class/equipement.class.php');
dol_include_once('/equipement/core/lib/equipement.lib.php');

if (! empty($conf->global->EQUIPEMENT_ADDON)
	&& is_readable(dol_buildpath("/equipement/core/modules/equipement/".$conf->global->EQUIPEMENT_ADDON.".php")))
	dol_include_once("/equipement/core/modules/equipement/".$conf->global->EQUIPEMENT_ADDON.".php");



$langs->load("companies");
$langs->load("products");
$langs->load("equipement@equipement");

$action		= GETPOST('action', 'alpha');
$id			= GETPOST('id', 'int');
$ref		= GETPOST('ref', 'alpha');

$fk_product		= GETPOST('productid', 'int');
$fk_entrepot	= GETPOST('fk_entrepot', 'int');
$fk_entrepotmove= GETPOST('fk_entrepotmove', 'int');
$descr			= GETPOST('np_desc', 'alpha');
$datecons 		= dol_mktime(
				GETPOST('deohour', 'int'), GETPOST('deomin', 'int'), 0,
				GETPOST('deomonth', 'int'), GETPOST('deoday', 'int'), GETPOST('deoyear', 'int')
);
$fk_user_author = GETPOST('userid');
$qty			= GETPOST('qty', 'int');
$confirm		= GETPOST('confirm', 'alpha');
$mesg			= GETPOST('msg', 'alpha');


// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'equipement', $id, 'equipement', '', 'fk_soc_client');


$object = new Equipement($db);
$equipement= new Equipement($db);
$object->fetch($id, $ref);
$entrepot=new Entrepot($db);


/*
 * Actions
 */


// Add line
if ($action == "addline" && $user->rights->equipement->creer) {
	// on vérifie les valeurs obligatoire


	if (! $error) {
		$result=$object->addconsumption(
						$id,
						$fk_product,
						$descr,
						$datecons,
						$fk_entrepot,
						$fk_entrepotmove,
						$fk_user_author,
						$qty
		);

		// Seulement si un seul produit a été ajouté
		if ($result >= 0 && $qty == 1) {
			// on vérifie si le produit est un équipements dans l'entrepot
			//  (saisie supplémentaire possible alors)
			$sql = 'SELECT e.* FROM '.MAIN_DB_PREFIX.'equipement as e';
			$sql.= ' WHERE e.fk_product = '.$fk_product;
			$sql.= ' AND e.fk_entrepot = '.$fk_entrepot;
			$sql.= ' AND e.fk_statut = 1';
			$resql = $db->query($sql);
			if ($resql) {
				$num = $db->num_rows($resql);
				$i = 0;

				if ($num > 0) {
					// si la produit est sérialisé on ouvre la ligne en modification pour le choisir
					Header('Location: '.$_SERVER["PHP_SELF"].'?id='.$id.'&amp;action=editline&amp;line_id='.$result);
					exit;
				}
			}
			// sinon on retour en affiche classique
			Header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
			exit;
		} else {
			$mesg=$object->error;
		}
	}
} elseif ($action == 'updateline' && $user->rights->equipement->creer
	&& GETPOST('save', 'alpha') == $langs->trans("Save")) {
	/*
	*  Mise a jour d'une ligne d'évènement
	*/

	$object->fetch_thirdparty();

	$desc				= GETPOST('np_desc');
	$dateo 				= dol_mktime(
					GETPOST('deohour', 'int'), GETPOST('deomin', 'int'), 0,
					GETPOST('deomonth', 'int'), GETPOST('deoday', 'int'), GETPOST('deoyear', 'int')
	);
	$fk_entrepot 		= GETPOST('fk_entrepot');
	$fk_equipementcons  = GETPOST('fk_equipementcons');

	$total_ht			= GETPOST('total_ht');

	$objectline = new Equipementconsumption($db);
	if ($objectline->fetch(GETPOST('line_id', 'int')) > 0) {
		$objectline->fk_equipementcons	= $fk_equipementcons;
		$objectline->fk_entrepot_dest	= $fk_entrepot;
		$objectline->fk_entrepotmove	= $fk_entrepotmove;
		$objectline->datecons			= $datecons;
		$objectline->desc				= $desc;
		$objectline->total_ht			= $total_ht;

		$result = $objectline->update();
		if ($result < 0) {
			dol_print_error($db);
			exit;
		}

		Header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
		exit;
	}
} elseif ($action == 'confirm_deleteline' && $confirm == 'yes' && $user->rights->equipement->creer) {
	/*
	 *  Supprime une ligne d'évènement AVEC confirmation
	 */

	$objectline = new Equipementconsumption($db);
	if ($objectline->fetch(GETPOST('line_id', 'int')) <= 0) {
		dol_print_error($db);
		exit;
	}

	$result=$objectline->deleteline();
	if ($object->fetch($id) <= 0) {
		dol_print_error($db);
		exit;
	}
}


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);

llxHeader();

if ($id > 0 || ! empty($ref)) {
	/*
	 * Affichage en mode visu
	 */

	$object->fetch($id, $ref);
	if (!$id) $id=$object->id;
	$object->fetch_thirdparty();
	$res=$object->fetch_optionals($object->id, $extralabels);

	dol_htmloutput_mesg($mesg);

	$head = equipement_prepare_head($object);

	dol_fiche_head($head, 'consumption', $langs->trans("Equipement"), 0, 'equipement@equipement');


	// Confirmation de la suppression d'une ligne d'intervention
	if ($action == 'ask_deleteline') {
		$ret=$form->form_confirm(
						$_SERVER["PHP_SELF"].'?id='.$object->id.'&line_id='.GETPOST('line_id', 'int'),
						$langs->trans('DeleteConsumptionLine'),
						$langs->trans('ConfirmDeleteconsumptionLine'),
						'confirm_deleteline', '', 0, 1
		);
		if ($ret == 'html') print '<br>';
	}

	print '<table class="border" width="100%">';

	// Ref
	print '<tr><td width=250px>'.$langs->trans("Ref").'</td>';
	print '<td colspan="3">';
	$linkback = '<a href="list.php'.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';
	print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref');
	print '</td></tr>';

	// produit
	$prod=new Product($db);
	$prod->fetch($object->fk_product);
	print '<tr><td >'.$langs->trans("Product").'';

	print '</td><td colspan="3">';
	print "<a href=# onclick=\"$('#descprod').toggle();\" >".img_picto("", "edit_add")."</a>";
	print $prod->getNomUrl(1)." : ".$prod->label.'</td></tr>';
	print "<tr style='display:none' id='descprod'>";
	print '<td></td><td>'.$prod->description.'</td><tr>';

	// Numéro de version
	print '<tr><td>'.$langs->trans("VersionNumber").'</td>';
	print '<td colspan="3">';
	print $object->numversion;
	print '</td></tr>';

	// quantité modifiable et visible uniquement si supérieur à 1
	if ($object->quantity >1) {
		print '<tr><td>'.$langs->trans("Quantity").'</td>';
		print '<td colspan="3">';
		print $object->quantity;
		print '</td></tr>';
	}

	// Etat de l'équipement
	print '<tr><td>'.$langs->trans("EtatEquip").'</td>';
	print '<td colspan="3">';
	if ($object->etatequiplibelle)
		print $langs->trans($object->etatequiplibelle);
	print '</td></tr>';

	// Statut
	print '<tr><td>'.$langs->trans("Status").'</td><td>'.$object->getLibStatut(4).'</td></tr>';

	print "</table><br>";

	/*
	 * Lignes de consumption
	 */
	$sql = 'SELECT ec.*';
	$sql.= ' FROM '.MAIN_DB_PREFIX.'equipementconsumption as ec';
	$sql.= ' WHERE ec.fk_equipement = '.$id;
	$sql.= ' ORDER BY ec.datecons ASC';

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;

		if ($num > 0) {
			print_fiche_titre($langs->trans("ConsumptionProductList"));

			print '<table class="noborder" width="100%">';
			print '<tr class="liste_titre">';
			print '<td width=30% colspan=2><a name="add"></a>'.$langs->trans('MainInformation').'</td>'; // ancre
			print '<td  align="left" >'.$langs->trans('Description').'</td>';
			print '<td width=25% colspan=2 align="left" colspan=2>'.$langs->trans('AdditionnalInfo').'</td>';
			print '<td width=50px ></td>';
			print "</tr>\n";
		}
		$var=true;
		while ($i < $num) {
			$lineevt = new Equipementconsumption($db);
			$lineuser = new User($db);
			$objp = $db->fetch_object($resql);
			$var=!$var;
			// Ligne en mode visu
			if ($action != 'editline' || GETPOST('line_id', 'int') != $objp->rowid) {

				print '<tr '.$bc[$var].">\n";
				print '<td >'.$langs->trans("Products").'</td><td>';
				$prod->fetch($objp->fk_product);
				print $prod->getNomUrl(1);
				print '</td>';

				// description de l'évènement de la consommation
				print '<td  rowspan=3 valign=top>';
				print dol_htmlentitiesbr($objp->description);
				print '</td>';

				print '<td align="left" >'.$langs->trans("Author").'</td>';
				print '<td align="left" >';
				$lineuser->fetch($objp->fk_user_author);
				print $lineuser->getNomUrl(1);
				print '</td>';

				// Icone d'edition et suppression
				if ($user->rights->equipement->creer) {
					print '<td align="center" rowspan=3 valign=top>';
					print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id;
					print '&action=editline&line_id='.$objp->rowid.'#'.$objp->rowid.'">';
					print img_edit();
					print '</a>';
					print '&nbsp';
					print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id;
					print '&action=ask_deleteline&line_id='.$objp->rowid.'">';
					print img_delete();
					print '</a></td>';
				} else
					print '<td rowspan=3 valign=top>&nbsp;</td>';

				print '</tr>';

				print '<tr '.$bc[$var].">\n";
				print '<td align="left" >';
				print $langs->trans("EntrepotStock");
				print '</td><td align="left" >';
				$entrepot->fetch($objp->fk_entrepot);
				print $entrepot->getNomUrl(2);
				print '</td>';
				// Date evenement début
				print '<td align="left" >'.$langs->trans("DateCons").'</td>';
				print '<td align="left" >';
				print dol_print_date($db->jdate($objp->datecons), "dayhour");
				print '</td></tr>';

				print '<tr '.$bc[$var].">\n";
				print '<td align="left" >'.$langs->trans("Ref").'</td>';
				print '<td >';
				if ($objp->fk_equipementcons > 0) {
					$equipement->fetch($objp->fk_equipementcons);
					print $equipement->getNomUrl(1);
				}
				print '</td>';

				print '<td align="left" >'.$langs->trans("Price").'</td>';
				print '<td align="right">'.price($objp->price).'</td>';
				print '</tr>';
			}

			// Ligne en mode update
			if ($action == 'editline'
				&& $user->rights->equipement->creer
				&& GETPOST('line_id', 'int') == $objp->rowid
			) {
				print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'#'.$objp->rowid.'" method="post">';
				print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
				print '<input type="hidden" name="action" value="updateline">';
				print '<input type="hidden" name="id" value="'.$object->id.'">';
				print '<input type="hidden" name="line_id" value="'.GETPOST('line_id', 'int').'">';

				print '<tr '.$bc[$var].">\n";
				print '<td class="fieldrequired">'.$langs->trans("Products").'</td><td>';
				$prod->fetch($objp->fk_product);
				print $prod->getNomUrl(1);
				print '</td>';

				// description de l'évènement de la consommation
				print '<td  rowspan=3>';
				// editeur wysiwyg
				require_once(DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php");
				$doleditor=new DolEditor(
								'np_desc', ($objp->description ? $objp->description :GETPOST('np_desc')),
								'', 100, 'dolibarr_details', '', false, true,
								$conf->global->FCKEDITOR_ENABLE_DETAILS, ROWS_6, 60
				);
				$doleditor->Create();
				print '</td>';


				// Date evenement début
				print '<td align="left" >'.$langs->trans("DateCons").'</td>';
				print '<td align="left" >';
				$timearray=dol_getdate(mktime());
				if (!GETPOST('deoday', 'int'))
					$timewithnohour=dol_mktime(0, 0, 0, $timearray['mon'], $timearray['mday'], $timearray['year']);
				else
					$timewithnohour=dol_mktime(
									GETPOST('deohour', 'int'), GETPOST('deomin', 'int'), 0,
									GETPOST('deomonth', 'int'), GETPOST('deoday', 'int'), GETPOST('deoyear', 'int')
					);
				$form->select_date($timewithnohour, 'deo', 1, 1, 0, "addequicons");
				print '</td>';
				print '<td align="center" rowspan=3>';
				print '<input type="submit" class="button" value="'.$langs->trans('Save').'" name="save">';
				print '<input type="submit" class="button" value="'.$langs->trans('Cancel').'" name="cancel">';
				print '</td >';


				print '<tr '.$bc[$var].">\n";
				print '<td align="left" >';
				print $langs->trans("EntrepotStock");
				print '</td><td align="left" >';
				select_entrepot($objp->fk_entrepot, 'fk_entrepot', 1, 1);
				print '</td>';

				print '<td align="left" >'.$langs->trans("Author").'</td>';
				print '<td align="left" >';
				print $form->select_dolusers($user->id, 'userid', 0, null, 0, null, null, 0, 56).'</td>';
				print '</tr>';


				print '<tr '.$bc[$var].">\n";
				print '<td align="left" >'.$langs->trans("Ref").'</td>';
				print '<td >';
				select_equipements($objp->fk_equipementcons, $objp->fk_product, $objp->fk_entrepot, 'fk_equipementcons', 1, 1, 0);
				print '</td >';
				print '<td align="left" >'.$langs->trans("Price").'</td>';
				print '<td >';
				print '<input type=text name=price value="'.$objp->price.'">';

				print '</td></tr>';

				print "</form>\n";
			}

			$i++;
		}

		if ($num)
			print '</table>';

		$db->free($resql);

		dol_fiche_end();

		/*
		 * Add line on a le droit de créer un évènement à tous moment
		 */
		if ($action <> 'editline' && $user->rights->equipement->creer) {
			print_fiche_titre($langs->trans("AddConsumptionProduct"));

			print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=addline" name="addequipevt" method="post">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="id" value="'.$object->id.'">';
			print '<input type="hidden" name="action" value="addline">';

			print '<table class="noborder" width="100%">';
			print '<tr class="liste_titre">';
			print '<td width=30% colspan=2><a name="add"></a>'.$langs->trans('MainInformation').'</td>'; // ancre
			print '<td  align="left" >'.$langs->trans('Description').'</td>';
			print '<td width=25% colspan=2 align="left" colspan=2>'.$langs->trans('AdditionnalInfo').'</td>';
			print '<td width=50px ></td>';
			print "</tr>\n";

			print '<tr '.$bc[$var].">\n";
			print '<td class="fieldrequired">'.$langs->trans("Products").'</td><td>';
			print $form->select_produits($fk_product, 'productid', 0, $conf->product->limit_size, 0, -1, 2, '', 0);
			print '</td>';

			// description de l'évènement de la consommation
			print '<td  rowspan=3>';
			// editeur wysiwyg
			require_once(DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php");
			$doleditor=new DolEditor(
							'np_desc', ($predefdescription ? $predefdescription :''), '', 100,
							'dolibarr_details', '', false, true,
							$conf->global->FCKEDITOR_ENABLE_DETAILS, ROWS_6, 60
			);
			$doleditor->Create();
			print '</td>';

			// Date consommation
			print '<td align="left" >'.$langs->trans("DateCons").'</td>';
			print '<td align="left" colspan=2>';
			$timearray=dol_getdate(mktime());
			if (!GETPOST('deoday', 'int'))
				$timewithnohour=dol_mktime(0, 0, 0, $timearray['mon'], $timearray['mday'], $timearray['year']);
			else
				$timewithnohour=dol_mktime(
								GETPOST('deohour', 'int'), GETPOST('deomin', 'int'), 0,
								GETPOST('deomonth', 'int'), GETPOST('deoday', 'int'), GETPOST('deoyear', 'int')
				);
			$form->select_date($timewithnohour, 'deo', 1, 1, 0, "addequicons");

			print '</td>';
			print '</tr>';


			print '<tr '.$bc[$var].">\n";
			print '<td align="left" >';
			print $langs->trans("EntrepotStock");
			print '</td><td align="left" >';
			select_entrepot($fk_entrepot, 'fk_entrepot', 1, 1);
			print '</td>';

			print '<td align="left" >'.$langs->trans("Author").'</td>';
			print '<td align="left" >';
			print $form->select_dolusers($user->id, 'userid', 0, null, 0, null, null, 0, 56).'</td>';
			print '<td></td>';
			print '</tr>';

			print '<tr '.$bc[$var].">\n";
			print '<td colspan=2></td>'; // pas de numéro d'équipement à la création

			print '<td align="left" >'.$langs->trans("Qty").'</td>';
			print '<td align="left" colspan=2><input type=text name=qty value=1 size=2 >';
			print '&nbsp;&nbsp;&nbsp;';
			print '<input type="submit" class="button" value="'.$langs->trans('Add').'" name="addline">';
			print '</td></tr>';
			print '</table>';
			print '</form>';
		}
	} else
		dol_print_error($db);

	print '</div>';
	print "\n";
}
llxFooter();
$db->close();