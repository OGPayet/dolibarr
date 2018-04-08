<?php
/* Copyright (C) 2005		Rodolphe Quiedeville 	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013	Laurent Destailleur  	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2010	 	François Legastelois 	<flegastelois@teclib.com>
 * Copyright (C) 2014-2016	Charlie BENKE			<charlie@patas-monkey.com>
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
 *	\file	   /equipement/agendaevent.php
 *	\ingroup	projet
 *	\brief	  List activities of tasks
 */

$res=0;
if (! $res && file_exists("../main.inc.php"))
	$res=@include("../main.inc.php");		// For root directory
if (! $res && file_exists("../../main.inc.php"))
	$res=@include("../../main.inc.php");	// For "custom" directory

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';


dol_include_once('/equipement/class/equipement.class.php');
dol_include_once('/equipement/core/lib/equipement.lib.php');

$langs->load('equipement@equipement');

$action=GETPOST('action');
$mode=GETPOST("mode");
$id=GETPOST('id', 'int');

$periodyear=GETPOST('periodyear', 'int');
if (!$periodyear)
	$periodyear=date('Y');

$periodmonth=GETPOST('periodmonth', 'int');
if (!$periodmonth)
	$periodmonth=date('m');
if ($periodmonth < 10)
	$periodmonth = "0".$periodmonth;

$entrepotid=GETPOST('entrepotid', 'int');
if (!$perioduser)
	$entrepotid=-1;

$equipmentetatid=GETPOST('equipmentetatid', 'int');
if (!$equipmentetatid)
	$equipmentetatid=-1;

$eventypeid=GETPOST('eventypeid', 'int');
if (!$eventypeid)
	$eventypeid=-1;



// récupération du nombre de jour dans le mois
$time = mktime(0, 0, 0, $periodmonth+1, 1, $periodyear); // premier jour du mois suivant
$time--; // Recule d'une seconde
$nbdaymonth=date('d', $time); // on récupère le dernier jour

// Security check
// Security check
$equipementid = GETPOST('id', 'int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'equipement', $id, 'equipement', '', 'fk_soc_client');

$form=new Form($db);
$formother = new FormOther($db);

$equipementstatic=new Equipement($db);
$equipementevtstatic=new Equipementevt($db);


/*
 * Actions
 */



/*
 * View
 */


$title=$langs->trans("CalendarEvents");

llxHeader("", $title, "");

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], "", $sortfield, $sortorder, "", $num);
dol_htmloutput_mesg($mesg);

//

print '<form name="selectperiod" method="POST" action="'.$_SERVER["PHP_SELF"].'?id='.$project->id.'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="selectperiod">';

print '<table  width="100%" >';
print '<tr >';
print '<td>'.$langs->trans("PeriodEventAAAAMM").'</td>';
print '<td>'.$formother->selectyear($periodyear, 'periodyear');
print $formother->select_month($periodmonth, 'periodmonth').'</td>';
print '<td></td>';
print "</tr>\n";
print '<tr >';

print '<td>'.$langs->trans("Warehouse").'</td>';
print '<td>';
select_entrepot($entrepotid, 'entrepotid', 1, 1, 0, 0);
print '</td>';

print '<td>'.$langs->trans("EtatEquip").'</td>';
print '<td>';
select_equipement_etat($equipmentetatid, 'equipmentetatid', 1, 1, 0, 0);
print '</td>';

print '<td>'.$langs->trans("TypeofEquipementEvent").'</td>';
print '<td>';
select_equipementevt_type($eventypeid, 'eventypeid', 1, 1, 0, 0);
print '</td>';


print '<td ><input type=submit name="change" value="'.$langs->trans("Change").'"></td>';
print "</tr>\n";
print "</table>";
print '</form>';


print '<form name="addtime" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

print '<input type="hidden" name="periodyear" value="'.$periodyear.'">';
print '<input type="hidden" name="periodmonth" value="'.$periodmonth.'">';
print '<input type="hidden" name="entrepotid" value="'.$entrepotid.'">';
print '<input type="hidden" name="equipmentetatid" value="'.$equipmentetatid.'">';
print '<input type="hidden" name="eventypeid" value="'.$eventypeid.'">';
print '<br><br>';
print '<table class="noborder" width="100%" border=1 >';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Equipement").'</td>';
print '<td>'.$langs->trans("Product").'</td>';
print '<td>'.$langs->trans("Warehouse").'</td>';

for ($day=1;$day <= $nbdaymonth ;$day++) {
	$curday=mktime(0, 0, 0, $periodmonth, $day, $periodyear);
	$bgcolor="";
	if (date('N', $curday) == 6 || date('N', $curday) == 7)
		$bgcolor=" bgcolor=lightgrey ";
	print '<td '.$bgcolor.' align=center>';
	print substr($langs->trans(date('l', $curday)),0,1)."<br>".($day<10?"0":"").$day.'</td>';
	print '</td>';
}

print "</tr>\n";

// uniquement si il y a un type d'équipement sélectionné ou un entrepot
if ($equipmentetatid > 0 || $entrepotid > 0) {
	// récupération des équipement associé à l'état
	$sql = "SELECT rowid, fk_product FROM ".MAIN_DB_PREFIX."equipement as e";
		$sql.= " WHERE 1=1";
	if ($equipmentetatid > 0)
		$sql.= " AND e.fk_etatequipement=".$equipmentetatid ;
	// un entrepot si il est défini
	if ($entrepotid > 0)
		$sql.= " AND e.fk_entrepot =".$entrepotid;
	$sql.= " ORDER BY e.ref";

} else {
	// si on ne filtre que sur la plage ou le type de l'évènement
	// on n'affiche que les équipement qui sont concerné
	$sql = "SELECT distinct e.rowid, e.fk_product FROM ".MAIN_DB_PREFIX."equipement as e,";
	$sql.= MAIN_DB_PREFIX."equipementevt as ee";
	$sql.= " WHERE e.rowid=ee.fk_equipement";
	// pour ne filtrer que sur les types d'évènement sélectionné
	if ($eventypeid != -1)
		$sql.= " AND ee.fk_equipementevt_type =".$eventypeid;

	$sql.= " AND ( DATE_FORMAT(ee.datee, '%Y%m')= '".$periodyear.$periodmonth."'" ;
	$sql.= " 	OR DATE_FORMAT(ee.dateo, '%Y%m')= '".$periodyear.$periodmonth."'" ;
	$sql.= " 	OR ( DATE_FORMAT(ee.dateo, '%Y%m')< '".$periodyear.$periodmonth."'" ;
	$sql.= " 		AND  DATE_FORMAT(ee.datee, '%Y%m')> '".$periodyear.$periodmonth."'" ;
	$sql.= " 		)";
	$sql.= " 	)";
	$sql.= " ORDER BY ee.dateo";
}

$resql=$db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;
	// boucle sur les équipements
	$equipementStatic = new Equipement($db);
	$productStatic = new Product($db);
	$entrepotStatic = new Entrepot($db);

	while ($i < $num) {
		$obj = $db->fetch_object($resql);
		$equipementStatic->fetch($obj->rowid);
		$productStatic->fetch($obj->fk_product);
		$entrepotStatic->fetch($obj->fk_entrepot);

		print '<tr valign=top><td>'.$equipementStatic->getNomUrl(1, 1)."</td>\n"; //vers la liste des evenements
		print '<td>'.$productStatic->getNomUrl(1)."</td>\n";
		print '<td>'.$entrepotStatic->getNomUrl(1)."</td>\n";

		// récup des évènements dont la date de début ou de fin est dans la plage d'affichage
		// récupération des équipement associé à l'état
		$sql = "SELECT ee.rowid, ee.dateo, ee.datee, eet.color FROM ".MAIN_DB_PREFIX."equipementevt as ee";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_equipementevt_type as eet ON eet.rowid = ee.fk_equipementevt_type";
		$sql.= " WHERE ee.fk_equipement=".$obj->rowid;
		// pour ne filtrer que sur les types d'évènement sélectionné
		if ($eventypeid != -1)
			$sql.= " AND eet.rowid =".$eventypeid;

		$sql.= " AND ( DATE_FORMAT(ee.datee, '%Y%m')= '".$periodyear.$periodmonth."'" ;
		$sql.= " 	OR DATE_FORMAT(ee.dateo, '%Y%m')= '".$periodyear.$periodmonth."'" ;
		$sql.= " 	OR ( DATE_FORMAT(ee.dateo, '%Y%m')< '".$periodyear.$periodmonth."'" ;
		$sql.= " 		AND  DATE_FORMAT(ee.datee, '%Y%m')> '".$periodyear.$periodmonth."'" ;
		$sql.= " 		)";
		$sql.= " 	)";
		$sql.= " ORDER BY dateo";
		$resqlevt=$db->query($sql);
		//print $sql.'<br>';
		if ($resqlevt) {
			$numevt = $db->num_rows($resqlevt);
			if ($numevt > 0) {
				// on constitue un tableau d'évent associé pour la période à l'équipement
				$j = 0;
				$tblEvt=array();
				// boucle sur les évenements de l'équipement
				while ($j < $numevt) {
					$obje = $db->fetch_object($resqlevt);

					$datedeb = strtotime($obje->dateo);
					// si l'évenement débute avant le mois on le positionne au début du mois
					if ($datedeb < mktime(0, 0, 0, $periodmonth, 1, $periodyear))
						$datedeb = mktime(0, 0, 0, $periodmonth, 1, $periodyear);

					$datefin = strtotime($obje->datee);
					// si l'évenement débute avant le mois on le positionne au début du mois
					if ($datefin > mktime(0, 0, 0, $periodmonth, $nbdaymonth, $periodyear))
						$datefin = mktime(0, 0, 0, $periodmonth, $nbdaymonth, $periodyear);

					$tblEvt[$j][0] = $obje->rowid;
					$tblEvt[$j][32] = $obje->color;

					// on boucle sur le tableau des jours du mois
					for ($day=1;$day <= $nbdaymonth ;$day++) {
						$curday=mktime(0, 0, 0, $periodmonth, $day, $periodyear);
						// si l'évènement se passe ce jour là
						if ($curday >= $datedeb	&& $curday <= $datefin )
							$tblEvt[$j][$day] = 1;
					}
					$j++;
				}

				// on boucle sur le tableau des jours en posant les éléments dans les cellules

				for ($day=1;$day <= $nbdaymonth ;$day++) {
					$curday=mktime(0, 0, 0, $periodmonth, $day, $periodyear);
					$bgcolor="";
					if (date('N', $curday) == 6 || date('N', $curday) == 7)
						$bgcolor=" bgcolor=lightgrey ";
					print "<td ".$bgcolor." valign=top style='padding-right:0px;padding-left:0px'>";
					$j = 0;

					// boucle sur les évenements de l'équipement
					while ($j < $numevt) {
						if ($tblEvt[$j][$day] == 1) {
							print "<div class='classtipfor".$j."_".$i."'";
							print " style='text-align:center;background:#".$tblEvt[$j][32]."'>";
							print img_picto("", "object_task.png", '', false, 0, 1);
							print '</div>';
						}
						$j++;
					}
					print "</td>";
				}
				// on termine par le tooltip de l'évènements
				// et un peu de jquery pour lier tous cela
				$j=0;
				while ($j < $numevt) {
					// récup des infos de l'évènement
					$equipementevtstatic->fetch($tblEvt[$j][0]);
					$content = '<b>'.$langs->trans($equipementevtstatic->equipeventlib).'</b>';
					// on vire le contenu html si il y en a...
					$content.= '<br>'.strip_tags ($equipementevtstatic->desc, '<br>');
					print '<script>
					szcontent'.$j."_".$i.'="'.$content.'";

					jQuery(document).ready(function () {
						jQuery(".classtipfor'.$j."_".$i.'").tipTip({maxWidth: "400px", defaultPosition : "right",
						edgeOffset: 10, delay: 50, fadeIn: 50, fadeOut: 2000, content:szcontent'.$j."_".$i.'});
					});
					</script>';
					$j++;
				}

			} else {
				// si pas d'evt on affiche qd meme les cellules
				for ($day=1;$day <= $nbdaymonth ;$day++) {
					$curday=mktime(0, 0, 0, $periodmonth, $day, $periodyear);
					$bgcolor="";
					if (date('N', $curday) == 6 || date('N', $curday) == 7)
						$bgcolor=" bgcolor=lightgrey ";
					print "<td ".$bgcolor." valign=top style='padding-right:0px;padding-left:0px'>";
					print "</td>";
				}
			}
		}
		print '</tr>';
		$i++;
	}
}

print "</table>";
print '</form>';

llxFooter();
$db->close();