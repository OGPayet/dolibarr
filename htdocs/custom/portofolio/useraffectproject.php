<?php
/* Copyright (C) 2005	 	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013	Laurent Destailleur 	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010	Regis Houssin	   	<regis.houssin@capnetworks.com>
 * Copyright (C) 2010	 	François Legastelois	<flegastelois@teclib.com>
 * Copyright (C) 2014-2017	Charlie BENKE			<charlie@patas-monkey.com>
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
 *	\file	   /portofolio/useraffectproject.php
 *	\ingroup	projet
 *	\brief	  show time used in project
 */

$res=0;
if (! $res && file_exists("../main.inc.php"))
	$res=@include("../main.inc.php");		// For root directory
if (! $res && file_exists("../../main.inc.php"))
	$res=@include("../../main.inc.php");	// For "custom" directory

require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

dol_include_once('/portofolio/core/lib/portofolio.lib.php');

$langs->load('portofolio@portofolio');

$action=GETPOST('action');
$mode=GETPOST("mode");
$projectstatut=GETPOST('projectstatut');
if ($projectstatut =='') $projectstatut = -1;
$refprojet=GETPOST('refprojet');


$id	= GETPOST('id', 'int');
$object = new User($db);

$object->fetch($id);

// Security check
$canreaduser=(! empty($user->admin) || $user->rights->user->user->lire);
$socid=0;
if ($user->societe_id > 0)
	$socid = $user->societe_id;
$feature2='user';
if ($user->id == $id) {
	$feature2='';
	$canreaduser=1;
} // A user can always read its own card
if (!$canreaduser) {
	$result = restrictedArea($user, 'user', $id, '&user', $feature2);
}
if ($user->id <> $id && ! $canreaduser) accessforbidden();

$form = new Form($db);
$formother = new FormOther($db);
$projectstatic = new Project($db);
$taskstatic = new Task($db);


/*
 * Actions
 */


/*
 * View
 */

$form = new Form($db);
$title=$langs->trans("UserAffectProjects");

llxHeader("", $title, "");

if ($object->societe->id > 0)
	$result=$object->societe->fetch($object->societe->id);



$head = user_prepare_head($object);
dol_fiche_head($head, 'portofolio', $langs->trans("User"), 0, 'user');
$linkback = '<a href="'.DOL_URL_ROOT.'/user/index.php">'.$langs->trans("BackToList").'</a>';

if (DOL_VERSION >= "5.0.0") {
	dol_banner_tab($object, 'id', $linkback, $user->rights->user->user->lire || $user->admin);
	print '<div class="fichecenter">';
	print '<div class="clearboth"></div>';
} else {
	print '<table class="border" width="100%">';
	// Ref
	print '<tr><td width="20%" valign="top">'.$langs->trans("Ref").'</td>';
	print '<td colspan="3">';
	print $form->showrefnav($object, 'id', '', $user->rights->user->user->lire || $user->admin);
	print '</td>';
	print '</tr>'."\n";

	// Lastname
	print '<tr><td valign="top">'.$langs->trans("Lastname").'</td>';
	print '<td colspan="2">'.$object->lastname.'</td>';

	// Photo
	print '<td align="center" valign="middle" width="25%" rowspan="3">';
	print $form->showphoto('userphoto', $object, 100);
	print '</td>';
	print '</tr>'."\n";

	// Firstname
	print '<tr><td valign="top">'.$langs->trans("Firstname").'</td>';
	print '<td colspan="2">'.$object->firstname.'</td>';
	print '</tr>'."\n";

	// Position/Job
	print '<tr><td valign="top">'.$langs->trans("PostOrFunction").'</td>';
	print '<td colspan="2">'.$object->job.'</td>';
	print '</tr>'."\n";
	print '</table>';
}
print "<br>";


//$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user, $mine,1);
//var_dump($tasksarray);
//var_dump($projectsrole);
//var_dump($taskrole);

dol_htmloutput_mesg($mesg);

print '<form method=post>';
print '<input type=hidden name=showall value="1">';
print '<table class="border" width="50%">';
print '<tr><td colspan=2 align=center><b>'.$langs->trans('ProjectFilters').'</b></td></tr>';

// en V5 on peu filter par la catégorie de projet
$cate_arbo = $form->select_all_categories(4, null, 'parent', null, null, 1);
if (count($cate_arbo) >0 ) {
	print '<tr><td>'.fieldLabel('CategoriesUser', 'catuserselect').'</td><td >';
	print $form->multiselectarray('catuserselect', $cate_arbo, $catuserselect, null, null, null, null, '90%');
	print "</td></tr>";
}
print '<tr><td>'.$langs->trans('RefProject').'</td><td>';

print '<input type=text name=refprojet value="'.$refprojet.'">';
print '</td></tr>';

print '<tr><td>'.$langs->trans('ProjectStatut').'</td><td>';
$statutarray=array(	'1' => $langs->trans("Enabled"), '0' => $langs->trans("Disabled"));

print $form->selectarray('projectstatut', $statutarray, $projectstatut, 1);
print '</td></tr>';

print '<tr><td colspan=2 align=center>';
print '<input type=submit value="'.$langs->trans('ApplyFilter').'"></td></tr>';
print '</table>';
print '</form>';
print '<br><br>';

$taskstatic	= new Task($db);
$projetstatic	= new Project($db);

// récupération des type de contact possible pour les projet et les taches
$arraytypecontactproject = aviable_type_contact('project', 'internal', 1);
$arraytypecontactprojecttask = aviable_type_contact('project_task', 'internal', 1);

// on boucle sur le projets d'abord
$sql = "SELECT  rowid, fk_statut FROM ".MAIN_DB_PREFIX."projet";
$sql.= " WHERE 1=1";
if ($refprojet)
	$sql.= " AND ref like '".$refprojet."%'";
if ($projectstatut >= 0)
	$sql.= " AND fk_statut =".$projectstatut;

//print $sql;
$resqlline = $db->query($sql);
$num = $db->num_rows($resqlline);
$sqlprojet=$sql;
if ($resqlline && ($refprojet != "" || $projectstatut != -1 || $num <= 10)) {
	$i=0;
	if ($action == 'adduser') {
		// on boucle sur les projets
		$i = 0;
		while ($i < $num) {
			$objp = $db->fetch_object($resqlline);
			$resqlcol = $db->query($sqlprojet);
			// on boucle sur les utilisateurs
			if ($resqlcol) {
				$j=0;
				$numprojet = $db->num_rows($resqlcol);
				if ($numprojet > 0) {
					while ($j < $numprojet) {
						$objp = $db->fetch_object($resqlcol);
						// on parcourt les type de contact
						foreach ($arraytypecontactproject as $key => $value) {
							// on supprime le rattachement au projet si il existe
							$sql = " DELETE FROM " . MAIN_DB_PREFIX . "element_contact " ;
							$sql.= " WHERE element_id = ".$objp->rowid;
							$sql.= " AND fk_socpeople = ".$id;
							$sql.= " AND fk_c_type_contact = ".$key;

							//print $sql."<br>";
							$result = $db->query($sql);

							if (GETPOST('chkp-'.$objp->rowid.'-'.$key) == "XXX") {
								$sql = "INSERT INTO ".MAIN_DB_PREFIX."element_contact";
								$sql.= " (statut, datecreate, element_id, fk_c_type_contact, fk_socpeople)";
								$sql.= " VALUES (4, now() ";
								$sql.= " , ".$objp->rowid;
								$sql.= " , ".$key;
								$sql.= " , ".$id;
								$sql.= ")";
								//print $sql."<br>";
								$result = $db->query($sql);
							}
						}

						// on boucle sur les taches du projet
						// ensuite les lignes de tache du projet
						$sql = "SELECT  rowid, fk_statut FROM ".MAIN_DB_PREFIX."projet_task";
						$sql.= " WHERE fk_projet=".$objp->rowid;
						//print $sql."<br>";
						$resqltask = $db->query($sql);
						$k = 0;
						$numtask = $db->num_rows($resqltask);
						while ($k < $numtask) {
							$objpt = $db->fetch_object($resqltask);
							// on parcourt les type de contact
							foreach ($arraytypecontactprojecttask as $keytask => $valuetask) {
								// on supprime le rattachement au projet si il existe
								$sql = " DELETE FROM " . MAIN_DB_PREFIX . "element_contact " ;
								$sql.= " WHERE element_id = ".$objpt->rowid;
								$sql.= " AND fk_socpeople = ".$id;
								$sql.= " AND fk_c_type_contact = ".$keytask;

								//print $sql."<br>";
								$result = $db->query($sql);

								if (GETPOST('chkpt-'.$objpt->rowid.'-'.$keytask) == "XXX") {
									$sql = "INSERT INTO ".MAIN_DB_PREFIX."element_contact";
									$sql.= " (statut, datecreate, element_id, fk_c_type_contact, fk_socpeople)";
									$sql.= " VALUES (4, now() ";
									$sql.= " , ".$objpt->rowid;
									$sql.= " , ".$keytask;
									$sql.= " , ".$id;
									$sql.= ")";
									//print $sql."<br>";
									$result = $db->query($sql);
								}
							}
							$k++;
						}
						$j++;
					}
				}
			}
			$i++;
		}
		// trigger à la fin des mise à jour
		$result=$object->call_trigger('PORTOFOLIO_MASS_AFFECT_USER', $user);
		if ($result < 0) $error++;
	}

	print '<br><form action="" method="POST" name="LinkedOrder">';

	print '<input type=hidden name=categselect value="'.$categselect.'">';
	print '<input type=hidden name=key value="'.$key.'">';
	print '<input type=hidden name=companyfilterkey value="'.$companyfilterkey.'">';
	print '<input type=hidden name=projectstatut value="'.$projectstatut.'">';
	print '<input type=hidden name=refprojet value="'.$refprojet.'">';

	print '<input type=hidden name=action value="adduser">';
	print '<table class="noborder">';
	print '<tr class="liste_titre">';
	print '<th align="left" width=20%>'.$langs->trans("Ref").'</th>';
	print '<th align="left" width=20%>'.$langs->trans("Status").'</th>';
	print '<th align="left" width=20%>'.$langs->trans("progress").'</th>';

	// on boucle d'abord sur les projets
	foreach ($arraytypecontactproject as $key => $value) {
		print '<th align=left >';
		print  $langs->trans("Project").'<br>';
		print '<input type=checkbox class="dochkall" id="chkidp-'.$key.'">&nbsp;';
		print $value;
	}

	// on boucle d'abord sur les projets
	foreach ($arraytypecontactprojecttask as $key => $value) {
		print '<th align=center >';
		print  $langs->trans("Task").'<br>';
		print '<input type=checkbox class="dochkall" id="chkidpt-'.$key.'">&nbsp;';
		print $value;
	}

	print '</tr>';

	$var=true;
	$resqldata = $db->query($sqlprojet);
	// si c'est le premier accès et plus de 200 lignes, on n'affiche que les 200 premières lignes
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
		$projetstatic->fetch($objp->rowid);
		print $projetstatic->getNomUrl(3);
		print '</td>';

		// on ajoute les statuts modifiables
		print '<td width=50px>';
		print $projetstatic->getLibStatut(2);
		print '</td>';

		// on ajoute les statuts modifiables
		print '<td width=50px>';
		print $projetstatic->progress;
		print '</td>';

		// ensuite les lignes de tache du projet
		$sql = "SELECT  rowid, fk_statut FROM ".MAIN_DB_PREFIX."projet_task";
		$sql.= " WHERE fk_projet=".$objp->rowid;
		$resqltask = $db->query($sql);
		$j = 0;
		$numtask = $db->num_rows($resqltask);

		// on boucle d'abord sur les projets
		foreach ($arraytypecontactproject as $key => $value) {
			print '<td align=left>';
			// déterminer si checked ou pas
			$projetstaticlistcontact=$projetstatic->liste_contact(-1, 'internal');
			//var_dump($projetstaticlistcontact);
			$selected="";
			foreach ($projetstaticlistcontact as  $valuecontact)
				if ($valuecontact['id'] == $id && $valuecontact['fk_c_type_contact'] == $key)
					$selected=" checked ";
			print '<input type=checkbox '.$selected.' class="chkidp-'.$key.'"';
			print 'value="XXX" name="chkp-'.$objp->rowid.'-'.$key.'"> ';
			print  $value;
			print '</td>';
		}

		// on boucle d'abord sur les taches
		foreach ($arraytypecontactprojecttask as $key => $value) {
			print '<th align=center >';
			print  $langs->trans("Task").'<br>';
			print '<input type=checkbox class="dochkall" id="chkidptp-'.$i."-".$key.'">&nbsp;';
			print $value;
		}
		print '</tr>';

		while ($j < $numtask) {
			$var=!$var;
			$objpt = $db->fetch_object($resqltask);
			print '<tr '.$bc[$var].'>';
			print '<td align=right>';

			$taskstatic->fetch($objpt->rowid);
			print $taskstatic->getNomUrl(3);
			print '</td>';

			// on ajoute les statuts modifiables
			print '<td width=50px>';
			print $taskstatic->getLibStatut(2);
			print '</td>';

			// on ajoute les statuts modifiables
			print '<td width=50px>';
			print $taskstatic->progress;
			print '</td>';

			// on saute les colonnes de type de contact du projet
			print '<td colspan='.count($arraytypecontactproject).'></td>';

			$taskstaticlist=$taskstatic->liste_contact(-1, 'internal');

			// on boucle sur les type de tache
			foreach ($arraytypecontactprojecttask as $key => $value) {
				print '<td align=center>';
				$projettaskstaticlistcontact=$taskstatic->liste_contact(-1, 'internal');
				$selected="";
				foreach ($projettaskstaticlistcontact as  $valuecontact)
					if ($valuecontact['id'] == $id && $valuecontact['fk_c_type_contact'] == $key)
						$selected=" checked ";
				print '<input '.$selected.' type=checkbox class="chkidpt-'.$key.' chkidptp-'.$i."-".$key.'"';
				print ' value="XXX" name="chkpt-'.$objpt->rowid.'-'.$key.'"> ';
				//print  $value;
				print '</td>';
			}
			$j++;
		}
		$i++;
	}
	print '<tr><td colspan=3 align=right >';
	print '</td>';
	print '<td align=center colspan="'.(count($arraytypecontactproject)+count($arraytypecontactprojecttask)).'">';

	if ($numshow == $num ) {
		if ($user->rights->portofolio->setup)
			print '<input type=submit value="'. $langs->trans("ApplyChange").'">';
	} else
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