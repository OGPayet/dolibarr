<?php
/* Copyright (C) 2005	  Patrick Rouillon	 <patrick@rouillon.net>
 * Copyright (C) 2005-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2012 Philippe Grand	   <philippe.grand@atoo-net.com>
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
 *	 \file	   htdocs/customlink/tabs/contactelement.php
 *	 \ingroup	customlink
 *	 \brief	  Onglet de gestion des éléments des contacts d'une société
 */

$res=@include("../../main.inc.php");					// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../../main.inc.php");		// For "custom" directory

require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/contact.lib.php';

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';

dol_include_once('/customlink/class/customlink.class.php');

$langs->load("other");
$langs->load("companies");

$id=GETPOST('id', 'int');
$ref=GETPOST('ref', 'alpha');
$action=GETPOST('action', 'alpha');

$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');

if (! $sortfield) $sortfield='s.nom';
if (! $sortorder) $sortorder='ASC';

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe', $id, '');

$object = new Contact($db);
$customlinkstatic = new Customlink($db);

/*
 * Ajout d'un nouveau contact
 */

/*
 * View
 */

$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('', $langs->trans("ThirdParty"), $help_url);


$form = new Form($db);
$formcompany = new FormCompany($db);
$formother = new FormOther($db);
$contactstatic=new Contact($db);
$userstatic=new User($db);


/* *************************************************************************** */
/*																			 */
/* Mode vue et edition														 */
/*																			 */
/* *************************************************************************** */
dol_htmloutput_mesg($mesg);

if ($id > 0 || ! empty($ref)) {
	$langs->trans("OrderCard");

	if ($object->fetch($id, $ref) > 0) {
		$soc = new Contact($db);
		$soc->fetch($object->socid);

		$head = contact_prepare_head($object);
		dol_fiche_head($head, 'customlink', $langs->trans("Contact"), 0, 'contact');

		print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

		$linkback = '<a href="'.DOL_URL_ROOT.'/contact/list.php">'.$langs->trans("BackToList").'</a>';
		dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', '');

		print '<div class="fichecenter">';
		print '<div class="underbanner clearboth"></div>';
		print '<table class="border centpercent">';

		// Company
		if (empty($conf->global->SOCIETE_DISABLE_CONTACTS)) {
			if ($object->socid > 0) {
				$objsoc = new Societe($db);
				$objsoc->fetch($object->socid);

				print '<tr><td>'.$langs->trans("ThirdParty").'</td><td colspan="3">';
				print $objsoc->getNomUrl(1).'</td></tr>';
			} else {
				print '<tr><td>'.$langs->trans("ThirdParty").'</td><td colspan="3">';
				print $langs->trans("ContactNotLinkedToCompany");
				print '</td></tr>';
			}
		}

		// Civility
		print '<tr><td class="titlefield">'.$langs->trans("UserTitle").'</td><td colspan="3">';
		print $object->getCivilityLabel();
		print '</td></tr>';

		// Date To Birth
		print '<tr>';
		if (! empty($object->birthday)) {
			include_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

			print '<td class="titlefield">'.$langs->trans("DateToBirth");
			print '</td><td colspan="3">'.dol_print_date($object->birthday, "day");

			print ' &nbsp; ';
			//var_dump($birthdatearray);
			$ageyear=convertSecondToTime($now-$object->birthday, 'year')-1970;
			$agemonth=convertSecondToTime($now-$object->birthday, 'month')-1;
			if ($ageyear >= 2)
				print '('.$ageyear.' '.$langs->trans("DurationYears").')';
			else if ($agemonth >= 2)
				print '('.$agemonth.' '.$langs->trans("DurationMonths").')';
			else
				print '('.$agemonth.' '.$langs->trans("DurationMonth").')';

			print ' &nbsp; - &nbsp; ';
			if ($object->birthday_alert)
				print $langs->trans("BirthdayAlertOn");
			else
				print $langs->trans("BirthdayAlertOff");
			print '</td>';
		} else {
			print '<td>'.$langs->trans("DateToBirth").'</td><td colspan="3">';
			print $langs->trans("Unknown")."</td>";
		}
		print "</tr>";
		print "</table>";
		print '<div>';

		print '<br>';

		// list of elements where the contact is linked
		$sql = "SELECT ec.element_id, tc.element, tc.libelle";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_type_contact as tc, ".MAIN_DB_PREFIX."element_contact as ec ";
		$sql.= " WHERE tc.rowid = ec.fk_c_type_contact ";
		$sql.= " AND tc.source='external' ";
		$sql.= " AND ec.fk_socpeople=".$id;
		$sql.= " ORDER BY tc.element";

		dol_syslog("get list sql=".$sql);
		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			if ($num  > 0) {
				$titre=$langs->trans("AssociatedElement");
				print '<br>';
				$param="&id=".$id;
				print_barre_liste(
								$titre, $page, $_SERVER["PHP_SELF"],
								$param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, ''
				);
				print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';
				print "<table class='noborder' width='100%' >";
				print '<tr class="liste_titre">';
				print_liste_field_titre(
								$langs->trans("Element"), $_SERVER["PHP_SELF"], "",
								"", $param, "", $sortfield, $sortorder
				);
				print_liste_field_titre(
								$langs->trans("Statut"), $_SERVER["PHP_SELF"], "",
								"", $param, "", $sortfield, $sortorder
				);
				print_liste_field_titre(
								$langs->trans("TypeOfAssociation"), $_SERVER["PHP_SELF"], "",
								"", $param, "", $sortfield, $sortorder
				);
				print "</tr>\n";

				$var=True;
				$i=0;
				while ($i < $num ) {
					$objp = $db->fetch_object($resql);
					$tmpelement = $customlinkstatic->getobjectclass($objp->element);

					$var=!$var;
					print "<tr ".$bc[$var].">";

					$tmpelement->fetch($objp->element_id);
					print "<td>".$tmpelement->getNomUrl(3)."</td>";
					print "<td>".$tmpelement->getLibStatut(2)."</td>";

					print "<td>".$objp->libelle."</td>";

					print "</tr>\n";
					$i++;
				}
				print "</table>\n";
			}
		}
	} else
		print "RecordNotFound";
}
llxFooter();
$db->close();