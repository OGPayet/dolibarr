<?php
/* Copyright (C) 2012-2017	Charlie BENKE	<charlie@patas-monkey.com>
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
 *	\file	   htdocs/equipement/tabs/entrepot.php
 *	\brief	  List of all equipement store in an entrepot
 *	\ingroup	equipement
 */
$res=@include("../../main.inc.php");					// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../../main.inc.php");		// For "custom" directory

require_once(DOL_DOCUMENT_ROOT."/product/stock/class/entrepot.class.php");
require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/stock.lib.php");
dol_include_once('/equipement/class/equipement.class.php');
dol_include_once('/equipement/core/lib/equipement.lib.php');

$langs->load("companies");
$langs->load("stocks");
$langs->load("equipement@equipement");

$entrepotid=GETPOST('id', 'int');

// Security check

if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'equipement', $equipementid, 'equipement');

$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST('page', 'int');
if ($page == -1) {
	$page = 0;
}
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="e.datec";

$limit = $conf->liste_limit;

$search_ref=GETPOST('search_ref', 'alpha');
$search_refProduct=GETPOST('search_refProduct', 'alpha');
if ($search_refProduct=="-1")
	$search_refProduct="";
$search_company_fourn=GETPOST('search_company_fourn', 'alpha');
$search_reffact_fourn=GETPOST('search_reffact_fourn', 'alpha');
$search_company_client=GETPOST('search_company_client', 'alpha');
$search_reffact_client=GETPOST('search_reffact_client', 'alpha');
$search_etatequipement=GETPOST('search_etatequipement', 'alpha');
if ($search_etatequipement=="-1")
	$search_etatequipement="";


$action = GETPOST('action', 'alpha');

/*
 *	View
 */



// gestion du transfert d'un équipement dans un entrepot
if ($action=="AddEquipement") {
	$tblSerial=explode(";", GETPOST('listEquipementRef', 'alpha'));
	$nbCreateSerial=count($tblSerial);
	$i=0;
	while ($nbCreateSerial > $i) {
		$equipement = new Equipement($db);
		$equipement->fetch('', $tblSerial[$i]);
		$equipement->set_entrepot($user, $entrepotid);
		$i++;
	}
}

$form = new Form($db);
llxHeader();

$object = new Entrepot($db);
$result = $object->fetch($entrepotid);

$head = stock_prepare_head($object);
dol_fiche_head($head, 'equipement', $langs->trans("Warehouse"), 0, 'stock');

print '<table class="border" width="100%">';

// Ref
print '<tr><td width="25%">'.$langs->trans("Ref").'</td><td colspan="3">';
print $form->showrefnav($object, 'id', '', 1, 'rowid', 'libelle');
print '</td>';

print '<tr><td>'.$langs->trans("LocationSummary").'</td><td colspan="3">'.$object->lieu.'</td></tr>';

// Description
print '<tr><td valign="top">'.$langs->trans("Description").'</td>';
print '<td colspan="3">'.dol_htmlentitiesbr($object->description).'</td></tr>';

// Address
print '<tr><td>'.$langs->trans('Address').'</td><td colspan="3">';
print $object->address;
print '</td></tr>';

// Town
print '<tr><td width="25%">'.$langs->trans('Zip').'</td><td width="25%">'.$object->zip.'</td>';
print '<td width="25%">'.$langs->trans('Town').'</td><td width="25%">'.$object->town.'</td></tr>';

print "</table>";
print "<br>";
dol_fiche_end();
print "<br>";
$head = equipement_entrepot_prepare_head($object);
$head = dol_fiche_head($head, 'add', $langs->trans("Equipement"), 0, 'equipement@equipement');


$form = new Form($db);

print "<br>";
// Ajout d'équipement dans l'entrepot
print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">'."\n";
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="AddEquipement">';
print '<input type="hidden" name="id" value="'.$entrepotid.'">';

print '<table class="noborder" width="100%">';

print '<tr class="liste_titre">';
print '<td>';
print '<a name="add"></a>'; // ancre
print $langs->trans('ListEquipementToAdd').'</td>';
print '<td colspan="5">&nbsp;</td>';
print "</tr>\n";

print '<tr '.$bc[$var].">\n";
print '<td>';
print '<textarea name="listEquipementRef" cols="132" rows="'.ROWS_3.'"></textarea>';
print '</td>';

print '<td align="center" valign="middle" colspan="4">';
print '<input type="submit" class="button" value="'.$langs->trans('Add').'" name="addline"></td>';
print "</tr>\n";
print '</table >';
print '</form>'."\n";

llxFooter();
$db->close();