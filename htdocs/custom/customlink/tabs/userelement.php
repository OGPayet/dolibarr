<?php
/* Copyright (C) 2005	  Patrick Rouillon	 <patrick@rouillon.net>
 * Copyright (C) 2005-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2012 Philippe Grand	   <philippe.grand@atoo-net.com>
 * Copyright (C) 2014-2017 Charlie BENKE		<charlie@patas-monkey.com>
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
 *	 \file	   htdocs/customlink/tabs/userelement.php
 *	 \ingroup	customlink
 *	 \brief	  Onglet de gestion des éléments des contacts d'une société
 */

$res=@include("../../main.inc.php");					// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../../main.inc.php");		// For "custom" directory

require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';

dol_include_once('/customlink/class/customlink.class.php');

$langs->load("other");
$langs->load("companies");

$id=GETPOST('id', 'int');
$ref=GETPOST('ref', 'alpha');
$action=GETPOST('action', 'alpha');



// If user is not user read and no permission to read other users, we stop
if (($object->id != $user->id) && (! $user->rights->user->user->lire)) accessforbidden();

// Security check
$socid=0;
if ($user->societe_id > 0) $socid = $user->societe_id;
$feature2 = (($socid && $user->rights->user->self->creer)?'':'user');
if ($user->id == $id) $feature2=''; // A user can always read its own card
$result = restrictedArea($user, 'user', $id, 'user&user', $feature2);

$object = new User($db);
$customlinkstatic = new Customlink($db);

/*
 * Ajout d'un nouveau contact
 */

/*
 * View
 */

$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('', $langs->trans("User"), $help_url);


$form = new Form($db);
$formcompany = new FormCompany($db);
$formother = new FormOther($db);
$userstatic=new User($db);


/* *************************************************************************** */
/*																			 */
/* Mode vue et edition														 */
/*																			 */
/* *************************************************************************** */
dol_htmloutput_mesg($mesg);

if ($id > 0 || ! empty($ref)) {
	$langs->trans("UserCard");

	if ($object->fetch($id, $ref) > 0) {

		$head = user_prepare_head($object);
		dol_fiche_head($head, 'customlink', $langs->trans("User"), 0, 'user');

		print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

		$linkback = '<a href="'.DOL_URL_ROOT.'/user/index.php">'.$langs->trans("BackToList").'</a>';
		dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', '');

		print '<div class="fichecenter">';

		print '<div class="underbanner clearboth"></div>';
		print '<table class="border centpercent">';


		print '<table class="border" width="100%">';
		print '<tr><td class="titlefield">'.$langs->trans("Login").'</td>';
		print '<td class="valeur">'.$object->login.'&nbsp;</td></tr>';
		print "</table>";

		print '<div>';
		print '<br>';	
		// list of elements where the contact is linked
		$sql = "SELECT ec.element_id, tc.element, tc.libelle";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_type_contact as tc, ".MAIN_DB_PREFIX."element_contact as ec ";
		$sql.= " WHERE tc.rowid = ec.fk_c_type_contact ";
		$sql.= " AND tc.source='internal' ";
		$sql.= " AND ec.fk_socpeople=".$id;
		$sql.= " ORDER BY tc.element";

		dol_syslog("get list sql=".$sql);
		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			if ($num  > 0 ) {
				$titre=$langs->trans("AssociatedElement");
				print '<br>';
				$param="&id=".$id;
				print_barre_liste(
								$titre, $page, $_SERVER["PHP_SELF"], $param, 
								$sortfield, $sortorder, '', $num, $nbtotalofrecords, ''
				);
				print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';
				print "<table class='noborder' width='100%' >";
				print '<tr class="liste_titre">';
				print_liste_field_titre(
								$langs->trans("Element"), $_SERVER["PHP_SELF"], "", "",
								$param, "", $sortfield, $sortorder
				);
				print_liste_field_titre(
								$langs->trans("Statut"), $_SERVER["PHP_SELF"], "", "", 
								$param, "", $sortfield, $sortorder
				);
				print_liste_field_titre(
								$langs->trans("TypeOfAssociation"), $_SERVER["PHP_SELF"], "", "", 
								$param, "", $sortfield, $sortorder
				);
				print "</tr>\n";
				
				$var=True;
				$i=0;
				while ($i < $num ) {
					$objp = $db->fetch_object($resql);
					$tmpelement = $customlinkstatic->getobjectclass($objp->element);

					$var=!$var;
					print "<tr ".$bc[$var].">";
					if ($tmpelement) {
						$tmpelement->fetch($objp->element_id);
						print "<td>".$tmpelement->getNomUrl(3)."</td>";
						print "<td>".$tmpelement->getLibStatut(2)."</td>";
					} else
						print '<td colspan=2></td>';
				
					print "<td>".$objp->libelle."</td>";
			
					print "</tr>\n";
					$i++;
				}
				print "</table>\n";
			}
		}
	} else
		print "RecordNotFound"; 		// Pas d'éléments associés
}
llxFooter();
$db->close();