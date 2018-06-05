<?php
/* Copyright (C) 2002-2005 	Rodolphe Quiedeville 	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 	Laurent Destailleur  	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2015	  	Alexandre Spangaro   	<aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2016-2017	Charlie Benke			<charlie@patas-monkey.com>
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
 *	  \file	   portofolio/taskaffect.php
 * 		\ingroup	portofolio
 *	  \brief	  Page of change categories selection
 */

$res=0;
if (! $res && file_exists("../main.inc.php"))
	$res=@include("../main.inc.php");		// For root directory
if (! $res && file_exists("../../main.inc.php"))
	$res=@include("../../main.inc.php");	// For "custom" directory

require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

if (! $user->rights->portofolio->lire && ! $user->admin)
	accessforbidden();

$langs->load("users");
$langs->load("companies");
$langs->load("project");

$langs->load("portofolio@portofolio");

// Security check (for external users)
$socid=0;
if ($user->societe_id > 0)
	$socid = $user->societe_id;

$action=GETPOST('action', 'alpha');
$showall=GETPOST('showall');

$key = GETPOST('key', 'alpha');

$catuserselect = GETPOST('catuserselect', 'array');
$catprojectselect = GETPOST('catprojectselect', 'array');
$userstatut=(GETPOST('userstatut')!="" ? GETPOST('userstatut') : -1);
$projectstatut=(GETPOST('projectstatut')!="" ? GETPOST('projectstatut') : -1);

$form = new Form($db);

/*
 * View
 */

llxHeader('', $langs->trans("MassProjectUserAffect"));

$categoriestatic	= new Categorie($db);

$buttonmulticompany="";
$picto="portofolio@portofolio";
print_fiche_titre($langs->trans("MassProjectUserAffect"), $buttonmulticompany, $picto);
print '<br>';

print '<form method=post>';

print '<input type=hidden name=showall value="1">';
print '<table class="border" width="50%">';
print '<tr><td colspan=2 align=center><b>'.$langs->trans('AssociatedFilter').'</b></td></tr>';


$cate_arbo = $form->select_all_categories(4, null, 'parent', null, null, 1);
if (count($cate_arbo) >0 ) {

	print '<tr><td>' . fieldLabel('CategoriesUser', 'catuserselect').'</td><td >';
	print $form->multiselectarray('catuserselect', $cate_arbo, $catuserselect, null, null, null, null, '90%');
	print "</td></tr>";

}
print '<tr><td>'.$langs->trans('UserStatut').'</td><td>';
$statutarray=array(	'1' => $langs->trans("Enabled"),
					'0' => $langs->trans("Disabled")
);
print $form->selectarray('userstatut', $statutarray, $userstatut, 1);
print '</td></tr>';

if ( DOL_VERSION >= "5.0.0") {
	$cate_arbo = $form->select_all_categories(6, null, 'parent', null, null, 1);
	if (count($cate_arbo) >0) {
		print '<tr><td>' . fieldLabel('CategoriesProject', 'catprojectselect') . '</td><td >';
		print $form->multiselectarray(
						'catprojectselect', $cate_arbo, $catprojectselect,
						null, null, null, null, '90%'
		);
		print "</td></tr>";
	}
}
print '<tr><td>'.$langs->trans('ProjectFilter').'</td><td>';
print '<input type=text name=key value="'.$key.'">';
print '</td></tr>';

print '<tr><td>'.$langs->trans('ProjectStatut').'</td><td>';
$statutarray=array(
				'0' => $langs->trans("Draft"),
				'1' => $langs->trans("Enabled"),
				'2' => $langs->trans("Close"),
				'99' => $langs->trans("NotClose")
);
print $form->selectarray('projectstatut', $statutarray, $projectstatut, 1);
print '</td></tr>';
print '<tr><td colspan=2 align=center><input type=submit value="'.$langs->trans('ApplyFilter').'"></td></tr>';
print '</table>';
print '</form>';
print '<br><br>';


$userstatic 	= new User($db);
$projectstatic	= new Project($db);

$sql = "SELECT  rowid, fk_statut FROM ".MAIN_DB_PREFIX."projet";
$sql.= " WHERE 1=1";
if ($key != "") {
	$sql.= " AND ( ref		LIKE '%".$key."%'";
	$sql.= " OR	title		LIKE '%".$key."%'";
	$sql.= " OR	description	LIKE '%".$key."%')";
}
if ($projectstatut != -1)
	$sql.= " AND fk_statut =".$projectstatut;

//print $sql;
$resqlline = $db->query($sql);
$sqlbis=$sql;
if ($resqlline) {
	$i=0;
	$num = $db->num_rows($resqlline);

	// récupération des utilisateurs disponibles
	$sqluser = "SELECT  DISTINCT u.rowid, u.statut FROM ".MAIN_DB_PREFIX."user as u";
	if (GETPOST('catuserselect', 'array')) {
		$sqluser.= ", ".MAIN_DB_PREFIX."categorie_user as cu";
		$sqluser.= " WHERE cu.fk_user=u.rowid";
	} else
		$sqluser.= " WHERE 1=1";

	if ($userstatut != -1)
		$sqluser.= " AND u.statut =".$userstatut;
	//print $sqluser;
	$resqlcol = $db->query($sqluser);


	if ($action == 'adduser') {
		while ($i < $num) {
			$objp = $db->fetch_object($resqlline);

			// la liste des catégories présentes
			if (count($tblcats) > 0) {
				foreach ($tblcats as $categ) {
					$tablecateg=($categselect=='socpeople'?'contact':$categselect);
					if (DOL_VERSION < "3.8.0")
						$categtable=(($categselect=='societe' || $categselect=='fournisseur' ) ?'societe':$categselect);
					else
						$categtable=(($categselect=='societe' || $categselect=='fournisseur' ) ?'soc':$categselect);

						// on supprime toujours
						$sql= " DELETE FROM " . MAIN_DB_PREFIX . "categorie_" . $tablecateg;
						$sql .= " WHERE fk_categorie = ".$categ->id;
						$sql .= " AND fk_" . $categtable . " = ".$objp->rowid;
						$result = $db->query($sql);

						// on ajoute parfois
						if (GETPOST('chk-'.$categ->id."-".$objp->rowid)==1) {
							$sql= " INSERT INTO ".MAIN_DB_PREFIX."categorie_" . $tablecateg;
							$sql.= "(fk_categorie, fk_" . $categtable.") values ";
							$sql.= " ( ".$categ->id;
							$sql.= " , ".$objp->rowid;
							$sql.= ")";
							$result = $db->query($sql);
							$checked=' checked ';
						}
				}
			}
			// mise à jours des statut
			switch ($categselect) {
				case 'product' :
					// si on a changé quelque chose on met à jour sinon on ne fait rien
					if ($objp->tosell != GETPOST('statut_'.$objp->rowid)
					||	$objp->tobuy  != GETPOST('statut_buy_'.$objp->rowid)) {
						$sql= " UPDATE ".MAIN_DB_PREFIX."product";
						$sql.= " SET tosell =".GETPOST('statut_'.$objp->rowid);
						$sql.= " ,   tobuy =".GETPOST('statut_buy_'.$objp->rowid);
						$sql.= " WHERE rowid=".$objp->rowid;
						$result = $db->query($sql);
						$productstatic->fetch($objp->rowid);
					}
					break;
				case 'societe' :
				case 'fournisseur' :
					// si on a changé quelque chose on met à jour sinon on ne fait rien
					if ($objp->statut != GETPOST('statut_'.$objp->rowid)) {
						$sql = " UPDATE ".MAIN_DB_PREFIX."societe";
						$sql.= " SET status =".GETPOST('statut_'.$objp->rowid);
						$sql.= " WHERE rowid=".$objp->rowid;
						$result = $db->query($sql);
						$companystatic->fetch($objp->rowid);
					}
					break;
				case 'member' :
					// si on a changé quelque chose on met à jour sinon on ne fait rien
					if ($objp->statut != GETPOST('statut_'.$objp->rowid)) {
						$sql = " UPDATE ".MAIN_DB_PREFIX."adherent";
						$sql.= " SET statut =".GETPOST('statut_'.$objp->rowid);
						$sql.= " WHERE rowid=".$objp->rowid;
						$result = $db->query($sql);
						$adherentstatic->fetch($objp->rowid);
					}
					break;
				case 'socpeople' :
					// si on a changé quelque chose on met à jour sinon on ne fait rien
					if ($objp->statut != GETPOST('statut_'.$objp->rowid)) {
						$sql = " UPDATE ".MAIN_DB_PREFIX."socpeople";
						$sql.= " SET statut =".GETPOST('statut_'.$objp->rowid);
						$sql.= " WHERE rowid=".$objp->rowid;
						$result = $db->query($sql);
						$contact->fetch($objp->rowid);
					}
					break;
				case 'user' :
					// si on a changé quelque chose on met à jour sinon on ne fait rien
					if ($objp->statut != GETPOST('statut_'.$objp->rowid)) {
						$sql = " UPDATE ".MAIN_DB_PREFIX."user";
						$sql.= " SET statut =".GETPOST('statut_'.$objp->rowid);
						$sql.= " WHERE rowid=".$objp->rowid;
						$result = $db->query($sql);
						$user->fetch($objp->rowid);
					}
					break;
			}
			$i++;
		}
	}

	print '<br><form action="" method="POST" name="LinkedOrder">';

	print '<input type=hidden name=categselect value="'.$categselect.'">';
	print '<input type=hidden name=key value="'.$key.'">';
	print '<input type=hidden name=companyfilterkey value="'.$companyfilterkey.'">';
	print '<input type=hidden name=statut value="'.$statut.'">';

	print '<input type=hidden name=action value="chgcateg">';
	print '<input type=hidden name=selectedcateg value="'.$categselect.'">';
	print '<table class="noborder">';
	print '<tr class="liste_titre">';
	print '<th align="left" width=20%>' . $langs->trans("Ref") . '</th>';
	print '<th align="left" width=20%>' . $langs->trans("Status") . '</th>';
	if ($resqlcol) {
		$j=0;
		$numuser = $db->num_rows($resqlcol);
		if ($numuser > 0) {
			while ($j < $numuser) {
				$obju = $db->fetch_object($resqlcol);
				print '<th align=center >';
				$userstatic->fetch($obju->rowid);
				print $userstatic->getNomUrl(0).'&nbsp;'.$userstatic->getLibStatut(3);
				print '</th>';
				$j++;
			}
		}
	}
	else
		print '<th ></th>';

	print '</tr>';

	$sql = "SELECT tc.rowid, tc.libelle";
	$sql .= " FROM " . MAIN_DB_PREFIX . "c_type_contact as tc";
	$sql .= " WHERE tc.element = 'project' AND source = 'internal' and active=1";
	$resqltypecontact = $db->query($sql);
	if ($resqltypecontact ) {
		$k=0;
		$numtypecontact = $db->num_rows($resqltypecontact);
		if ($numtypecontact > 0) {
			$typecontactselect = array();
			while ($k < $numtypecontact) {
				$objtc = $db->fetch_object($resqltypecontact);
				$typecontactselect=array_merge(
								$typecontactselect,
								array($objtc->rowid	=> $objtc->libelle)
				);

				$k++;
			}
		}
	}

	$var=true;
	$resqldata = $db->query($sqlbis);
	// si c'est le premier accès et plus de 200 lignes,
	// on n'affiche que les 200 premières lignes et le bouton de mise à jour n'est pas présent
	if ($showall=="")
		$numshow = min(array($num, 200));
	else
		$numshow = $num; // après filtrage on ouvre toute les vannes
	$i = 0;
	while ($i < $numshow) {
		$var=!$var;
		$objp = $db->fetch_object($resqldata);
		print '<tr '.$bc[$var].'>';
		print '<td >';
		$projectstatic->fetch($objp->rowid);
		print $projectstatic->getNomUrl(3);
		print '</td>';

		// on ajoute les statuts modifiables
		print '<td >';
		print $projectstatic->getLibStatut(1);
		print '</td>';

		// la liste des utilisateurs
		if ($numuser > 0) {
			$resqlcol = $db->query($sqluser);
			if ($resqlcol) {
				$j=0;
				while ($j < $numuser) {
					$obju = $db->fetch_object($resqlcol);
					$sql = "SELECT ec.fk_c_type_contact";
					$sql .= " FROM " . MAIN_DB_PREFIX . "element_contact as ec";
					$sql .= " , " . MAIN_DB_PREFIX . "c_type_contact as tc";
					$sql .= " WHERE tc.rowid = ec.fk_c_type_contact";
					$sql .= " WHERE tc.element = 'project' AND source = 'internal' and active=1";
					$sql .= " AND fk_socpeople = ".$obju->rowid;
					$rescateg = $db->query($sql);

					print '<td align=center >';
					print $form->selectarray("categselect", $typecontactselect, $categselect, 1);
					print '<br>';
					print '<input type=checkbox>';
					print '&nbsp;'.$langs->trans("PropagateToTasks");
					print '</td>';
					$j++;
				}
			}
			else
				print '<td ></td>';
		}
		else
			print '<td></td>';
		print '</tr>';
		$i++;
	}
	print '<tr><td colspan=2 align=center ></td><td align=center colspan='.$numuser.'>';
	if ($numshow == $num) {
		if ($user->rights->portofolio->setup)
			print '<input type=submit value="'. $langs->trans("ApplyChange").'">';
	}
	else
		print $langs->trans("TooMuchDataPleaseFilterBeforeChange");

	print '</td>';
	print '<td '.(count($tblcats) >0?'colspan="'.count($tblcats).'"':"").'">';
	print '</td></tr>';
	print '</table>';
	print '</form>';
}

llxFooter();
$db->close();
?>
<script>
$(document).ready(function() {
	$('#showreflist').click(function() {  //on click
		$('#reflist').toggle();
	});

	$('.dochkall').click(function(event) {
		if (this.checked) { // check select status
			$('.'+ event.target.id).each(function() { //loop through each checkbox
				this.checked = true;
			});
		}else {
			$('.'+ event.target.id).each(function() { //loop through each checkbox
				this.checked = false;
			});
		}
	});
});
</script>