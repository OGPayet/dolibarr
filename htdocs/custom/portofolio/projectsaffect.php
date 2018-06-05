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
 *	\file	   /management/projet/reporttime.php
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
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

//dol_include_once ('/management/core/lib/management.lib.php');

$langs->load('portofolio@portofolio');

$action=GETPOST('action');
$mode=GETPOST("mode");

$catuserselect = GETPOST('catuserselect', 'array');
$userstatut=(GETPOST('userstatut')!="" ? GETPOST('userstatut') : -1);
$usergroupsel=(GETPOST('usergroupsel')!="" ? GETPOST('usergroupsel') : -1);

$projectstatut=(GETPOST('projectstatut')!="" ? GETPOST('projectstatut') : -1);

// récupération du nombre de jour dans le mois
$time = mktime(0, 0, 0, $periodmonth+1, 1, $periodyear); // premier jour du mois suivant
$time--; // Recule d'une seconde
$nbdaymonth=date('d', $time); // on récupère le dernier jour

$projectid='';
$projectid=GETPOST("id");
$projectref=GETPOST("ref");

$object = new Project($db);
$result = $object->fetch($projectid, $projectref);

if (!$projectid)
	$projectid=$object->id;

// Security check
$socid=0;
if ($user->societe_id > 0) $socid=$user->societe_id;
$result = restrictedArea($user, 'projet', $projectid);
$result = restrictedArea($user, 'portofolio');


$form=new Form($db);
$formother = new FormOther($db);
$projectstatic = new Project($db);
$taskstatic = new Task($db);

// We want to see all task of project i am allowed to see, not only mine. Later only mine will be editable later.
$tasksarray		=$taskstatic->getTasksArray(0, 0, $projectid, $socid, 0);
$projectsrole	=$taskstatic->getUserRolesForProjectsOrTasks($user, 0, $projectid, 0);
$tasksrole		=$taskstatic->getUserRolesForProjectsOrTasks(0, $user, $projectid, 0);

/*
 * Actions
 */


/*
 * View
 */


$form = new Form($db);

$title=$langs->trans("ProjectAffectUsers");

llxHeader("", $title, "");


if ($object->societe->id > 0)
	$result=$object->societe->fetch($object->societe->id);


$head = project_prepare_head($object);
dol_fiche_head($head, 'portofolio', $langs->trans("projet"), 0, 'project');
$linkback = '<a href="'.DOL_URL_ROOT.'/projet/liste.php">'.$langs->trans("BackToList").'</a>';

if (DOL_VERSION >= "5.0.0") {
	$morehtmlref='<div class="refidno">';
	$morehtmlref.=$object->title;

	if ($projectstatic->thirdparty->id > 0)
		$morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $object->thirdparty->getNomUrl(1, 'project');
	$morehtmlref.='</div>';

	// Define a complementary filter for search of next/prev ref.
	if (! $user->rights->projet->all->lire) {
		$objectsListId = $object->getProjectsAuthorizedForUser($user, 0, 0);
		$object->next_prev_filter=" rowid in (".(count($objectsListId)?join(',', array_keys($objectsListId)):'0').")";
	}
	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border" width="100%">';

	// Visibility
	print '<tr><td class="titlefield">'.$langs->trans("Visibility").'</td><td>';
	if ($object->public) print $langs->trans('SharedProject');
	else print $langs->trans('PrivateProject');
	print '</td></tr>';

	if (! empty($conf->global->PROJECT_USE_OPPORTUNITIES)) {
		// Opportunity status
		print '<tr><td>'.$langs->trans("OpportunityStatus").'</td><td>';
		$code = dol_getIdFromCode($db, $object->opp_status, 'c_lead_status', 'rowid', 'code');
		if ($code) print $langs->trans("OppStatus".$code);
		print '</td></tr>';

		// Opportunity percent
		print '<tr><td>'.$langs->trans("OpportunityProbability").'</td><td>';
		if (strcmp($object->opp_percent, ''))
			print price($object->opp_percent, '', $langs, 1, 0).' %';
		print '</td></tr>';

		// Opportunity Amount
		print '<tr><td>'.$langs->trans("OpportunityAmount").'</td><td>';
		if (strcmp($object->opp_amount, ''))
			print price($object->opp_amount, '', $langs, 1, 0, 0, $conf->currency);
		print '</td></tr>';
	}

	// Date start - end
	print '<tr><td>'.$langs->trans("DateStart").' - '.$langs->trans("DateEnd").'</td><td>';
	print dol_print_date($object->date_start, 'day');
	$end=dol_print_date($object->date_end, 'day');
	if ($end) print ' - '.$end;
	print '</td></tr>';

	// Budget
	print '<tr><td>'.$langs->trans("Budget").'</td><td>';
	if (strcmp($object->budget_amount, ''))
		print price($object->budget_amount, '', $langs, 1, 0, 0, $conf->currency);
	print '</td></tr>';

	// Other attributes
	$cols = 2;
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print '</table>';

	print '</div>';
	print '<div class="fichehalfright">';
	print '<div class="ficheaddleft">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border" width="100%">';

	// Description
	print '<td class="titlefield tdtop">'.$langs->trans("Description").'</td><td>';
	print nl2br($object->description);
	print '</td></tr>';

	// Categories
	if ($conf->categorie->enabled) {
		print '<tr><td valign="middle">'.$langs->trans("Categories").'</td><td>';
		print $form->showCategories($object->id, 'project', 1);
		print "</td></tr>";
	}

	print '</table>';
	print '</div>';
	print '</div>';
	print '</div>';
	print '<div class="clearboth"></div>';

} else {
	print '<table class="border" width="100%">';
	$urlparam =($periodyear ? "&periodyear=".$periodyear:''). "&periodmonth=".$periodmonth;
	$urlparam.=($perioduser ? "&perioduser=".$perioduser:'').($displaymode ? "&displaymode=".$displaymode:'');

	print '<tr><td width="30%">'.$langs->trans("Ref").'</td><td>';
	// Define a complementary filter for search of next/prev ref.
	if (! $user->rights->projet->all->lire) {
		$projectsListId = $object->getProjectsAuthorizedForUser($user, $mine, 0);
		$object->next_prev_filter=" rowid in (".(count($projectsListId)?join(',', array_keys($projectsListId)):'0').")";
	}
	print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref', '', $urlparam);
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("Label").'</td><td>'.$object->title.'</td></tr>';

	print '<tr><td>'.$langs->trans("ThirdParty").'</td><td>';
	if (! empty($object->societe->id))
		print $object->societe->getNomUrl(1);
	else
		print '&nbsp;';
	print '</td></tr>';

	// Visibility
	print '<tr><td>'.$langs->trans("Visibility").'</td><td>';
	if ($object->public)
		print $langs->trans('SharedProject');
	else
		print $langs->trans('PrivateProject');
	print '</td></tr>';

	// Statut
	print '<tr><td>'.$langs->trans("Status").'</td><td>'.$object->getLibStatut(4).'</td></tr>';
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
print '<tr><td colspan=2 align=center><b>'.$langs->trans('UserFilters').'</b></td></tr>';

$cate_arbo = $form->select_all_categories(4, null, 'parent', null, null, 1);
if (count($cate_arbo) >0) {
	print '<tr><td>' . fieldLabel('CategoriesUser', 'catuserselect') . '</td><td >';
	print $form->multiselectarray('catuserselect', $cate_arbo, $catuserselect, null, null, null, null, '90%');
	print "</td></tr>";
}


$sql = "SELECT g.rowid, g.nom, g.entity";
$sql.= " FROM ".MAIN_DB_PREFIX."usergroup as g";
if (! empty($conf->multicompany->enabled) && $conf->entity == 1 && $user->admin && ! $user->entity)
	$sql.= " WHERE g.entity IS NOT NULL";
else
	$sql.= " WHERE g.entity IN (0,".$conf->entity.")";
$sql.= " ORDER BY g.nom";

$result = $db->query($sql);
$usergrouparray=array();
if ($result) {
	while ($obj = $db->fetch_object($result))
		$usergrouparray[$obj->rowid]=$obj->nom;
	$db->free($result);
}


print '<tr><td>'.$langs->trans('UserGroup').'</td><td>';
print $form->selectarray('usergroupsel', $usergrouparray, $usergroupsel, 1);
print '</td></tr>';
print '<tr><td>'.$langs->trans('UserStatut').'</td><td>';
$statutarray=array(
				'1' => $langs->trans("Enabled"),
				'0' => $langs->trans("Disabled")
);

print $form->selectarray('userstatut', $statutarray, $userstatut, 1);
print '</td></tr>';
print '<tr><td colspan=2 align=center><input type=submit value="'.$langs->trans('ApplyFilter').'"></td></tr>';
print '</table>';
print '</form>';
print '<br><br>';

$userstatic = new User($db);
$taskstatic	= new Task($db);


// récupération des contacts interne du projet
$contactlistproject=$object->liste_contact(-1, 'internal');

$sql = "SELECT  rowid, fk_statut FROM ".MAIN_DB_PREFIX."projet_task";
$sql.= " WHERE fk_projet=".$projectid;
//print $sql;
$resqlline = $db->query($sql);
$sqlbis=$sql;
if ($resqlline) {
	$i=0;
	$num = $db->num_rows($resqlline);

	// récupération des utilisateurs disponibles
	$sqluser = "SELECT  DISTINCT u.rowid, u.statut FROM ".MAIN_DB_PREFIX."user as u";
	if ($usergroupsel != -1)
		$sqluser.= " LEFT JOIN ".MAIN_DB_PREFIX."usergroup_user as ug ON ug.fk_user = u.rowid";

	if (GETPOST('catuserselect', 'array')) {
		$sqluser.= ", ".MAIN_DB_PREFIX."categorie_user as cu";
		$sqluser.= " WHERE cu.fk_user=u.rowid";
	} else
		$sqluser.= " WHERE 1=1";

	if ($userstatut != -1)
		$sqluser.= " AND u.statut =".$userstatut;

	if ($usergroupsel != -1)
		$sqluser.= " AND ug.fk_usergroup =".$usergroupsel;

	if ($action == 'adduser') {
		// on boucle sur les taches
		$i = 0;
		while ($i < $num) {
			$objp = $db->fetch_object($resqlline);
			$resqlcol = $db->query($sqluser);
			// on boucle sur les utilisateurs
			if ($resqlcol) {
				$j=0;
				$numuser = $db->num_rows($resqlcol);
				if ($numuser > 0) {
					while ($j < $numuser) {
						$obju = $db->fetch_object($resqlcol);
						// si on a coché la case pour mise à jour
						if (GETPOST("chk-".$obju->rowid."-".$objp->rowid)) {
							if (GETPOST("chk-".$obju->rowid."-".$objp->rowid) != "XXX") {
								$sql = " DELETE FROM " . MAIN_DB_PREFIX . "element_contact " ;
								$sql.= " WHERE rowid = ".GETPOST("chk-".$obju->rowid."-".$objp->rowid);
								$result = $db->query($sql);
							}
							if (GETPOST('contactselect') > 0) {
								$sql = "INSERT INTO ".MAIN_DB_PREFIX."element_contact ";
								$sql.= " (statut, datecreate, element_id, fk_c_type_contact, fk_socpeople)";
								$sql.= " VALUES (4, now() ";
								$sql.= " , ".$objp->rowid;
								$sql.= " , ".GETPOST('contactselect');
								$sql.= " , ".$obju->rowid;
								$sql.= ")";
								$result = $db->query($sql);
							}
						}
						$j++;
					}
				}
			}
			$i++;
		}

		$resqlcol = $db->query($sqluser);
		// on boucle sur les utilisateurs
		if ($resqlcol) {
			$j=0;
			$numuser = $db->num_rows($resqlcol);
			if ($numuser > 0) {
				while ($j < $numuser) {
					$obju = $db->fetch_object($resqlcol);
					// Si on a modifié l'affectation du projet
					$selectcontactvalue="";
					foreach ($contactlistproject as $contactproject)
						if ($contactproject['id'] == $obju->rowid) {
							$selectcontactvalue=$contactproject['fk_c_type_contact'];
							break;
						}
						if (GETPOST("contactprojectselect-".$obju->rowid) != $selectcontactvalue) {
							// on supprime si besoin
							if ($selectcontactvalue !="") {
								$sql = " DELETE FROM " . MAIN_DB_PREFIX . "element_contact " ;
								$sql.= " WHERE element_id = ".$projectid;
								$sql.= " AND fk_socpeople = ".$obju->rowid;
								$sql.= " AND fk_c_type_contact = ".$selectcontactvalue;
								$result = $db->query($sql);
							}
							// on ajoute si besoin
							if (GETPOST("contactprojectselect-".$obju->rowid) >0) {
								$sql = "INSERT INTO ".MAIN_DB_PREFIX."element_contact ";
								$sql.= " (statut, datecreate, element_id, fk_c_type_contact, fk_socpeople)";
								$sql.= " VALUES (4, now() ";
								$sql.= " , ".$projectid;
								$sql.= " , ".GETPOST("contactprojectselect-".$obju->rowid);
								$sql.= " , ".$obju->rowid;
								$sql.= ")";
								$result = $db->query($sql);
							}
						}
					$j++;
				}
				// on remet à jour au cas ou
				$contactlistproject=$object->liste_contact(-1, 'internal');

			}
		}

		// trigger à la fin des mise à jour
		$result=$object->call_trigger('PORTOFOLIO_MASS_AFFECT_PROJECT', $user);
		if ($result < 0) $error++;

	}

	$resqlcol = $db->query($sqluser);

	print '<br><form action="" method="POST" name="LinkedOrder">';

	//print '<input type=hidden name=key value="'.$key.'">';
	//print '<input type=hidden name=companyfilterkey value="'.$companyfilterkey.'">';

	$sql = "SELECT tc.rowid, tc.libelle";
	$sql .= " FROM " . MAIN_DB_PREFIX . "c_type_contact as tc";
	$sql .= " WHERE tc.element = 'project' AND source = 'internal' and active=1";
	$resqltypecontactproject = $db->query($sql);
	//print $sql;
	if ($resqltypecontactproject ) {
		$k=0;
		$numtypecontactproject = $db->num_rows($resqltypecontactproject);
		if ($numtypecontactproject > 0) {
			$typecontactselectproject = array();
			while ($k < $numtypecontactproject) {
				$objtc = $db->fetch_object($resqltypecontactproject);
				$typecontactselectproject[$objtc->rowid]= $objtc->libelle;
				$k++;
			}
		}
	}

	print '<input type=hidden name=usergroupsel value="'.$usergroupsel.'">';
	print '<input type=hidden name=userstatut value="'.$userstatut.'">';
	print '<input type=hidden name=action value="adduser">';
	print '<input type=hidden name=catuserselect value="'.$catuserselect.'">';
	print '<table class="noborder">';
	print '<tr class="liste_titre">';
	print '<th align="left" width=20%>' . $langs->trans("Ref") . '</th>';
	print '<th align="left" width=20%>' . $langs->trans("Status") . '</th>';
	print '<th align="left" width=20%>' . $langs->trans("progress") . '</th>';
	if ($resqlcol) {
		$j=0;
		$numuser = $db->num_rows($resqlcol);
		if ($numuser > 0) {
			while ($j < $numuser) {
				$obju = $db->fetch_object($resqlcol);
				print '<th align=left valign=top nowrap>';
				$userstatic->fetch($obju->rowid);

				print $userstatic->getLibStatut(3).'&nbsp;'.$userstatic->getNomUrl(0);
				print '<br>';
				print '<input type=checkbox class="dochkall" id="chkidu'.$obju->rowid.'">&nbsp;';
				$selectcontactvalue="";
				foreach ($contactlistproject as $contactproject)
					if ($contactproject['id'] == $obju->rowid) {
						$selectcontactvalue=$contactproject['fk_c_type_contact'];
						break;
					}

				print $form->selectarray(
								"contactprojectselect-".$obju->rowid,
								$typecontactselectproject, $selectcontactvalue, 1
				);
				print '</th>';
				$j++;
			}
		}
	} else
		print '<th></th>';
	print '</tr>';

	$sql = "SELECT tc.rowid, tc.libelle";
	$sql .= " FROM " . MAIN_DB_PREFIX . "c_type_contact as tc";
	$sql .= " WHERE tc.element = 'project_task' AND source = 'internal' and active=1";
	$resqltypecontact = $db->query($sql);
	if ($resqltypecontact ) {
		$k=0;
		$numtypecontact = $db->num_rows($resqltypecontact);
		if ($numtypecontact > 0) {
			$typecontactselect = array();
			while ($k < $numtypecontact) {
				$objtc = $db->fetch_object($resqltypecontact);
				$typecontactselect[$objtc->rowid]= $objtc->libelle;
				$k++;
			}
		}
	}


	$var=true;
	$resqldata = $db->query($sqlbis);
	// si c'est le premier accès et plus de 200 lignes
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
		print '<input type=checkbox class="dochkall" id="chkidt'.$objp->rowid.'">&nbsp;';
		$taskstatic->fetch($objp->rowid);
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

		// la liste des utilisateurs
		if ($numuser > 0) {
			$resqlcol = $db->query($sqluser);
			if ($resqlcol) {
				$j=0;
				while ($j < $numuser) {
					$obju = $db->fetch_object($resqlcol);
					$sql = "SELECT ec.rowid, tc.libelle";
					$sql .= " FROM " . MAIN_DB_PREFIX . "element_contact as ec";
					$sql .= " , " . MAIN_DB_PREFIX . "c_type_contact as tc";
					$sql .= " WHERE tc.rowid = ec.fk_c_type_contact";
					$sql .= " AND tc.element = 'project_task' AND source = 'internal' and active=1";
					$sql .= " AND fk_socpeople = ".$obju->rowid;
					$sql .= " AND element_id = ".$objp->rowid;
					$restypecontact = $db->query($sql);
					print '<td align=left>';

					if ($restypecontact) {
						$objtc = $db->fetch_object($restypecontact);
						print '<input type=checkbox class="chkidu'.$obju->rowid.' chkidt'.$objp->rowid.'"';
						if ($objtc->rowid > 0) {
							print ' value="'.$objtc->rowid.'" name="chk-'.$obju->rowid.'-'.$objp->rowid.'"> ';
							print  $objtc->libelle;
						} else
							print ' value="XXX" name="chk-'.$obju->rowid.'-'.$objp->rowid.'"> ';
					}
					print '</td>';
					$j++;
				}
			} else
				print '<td ></td>';
		} else
			print '<td></td>';
		print '</tr>';
		$i++;
	}
	print '<tr><td colspan=3 align=right >';
	print $langs->trans("TypeTaskContactToAffect")." : ";
	print $form->selectarray("contactselect", $typecontactselect, "", 1);
	print '</td>';
	print '<td align=left colspan='.$numuser.'>';

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