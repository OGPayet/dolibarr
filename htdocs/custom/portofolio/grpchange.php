<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2015	   Alexandre Spangaro   <aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2015-2017 Charlie Benke		<charlie@patas-monkey.com>
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
 *	  \file	   htdocs/user/index.php
 * 		\ingroup	core
 *	  \brief	  Page of users
 */

$res=0;
if (! $res && file_exists("../main.inc.php"))
	$res=@include("../main.inc.php");		// For root directory
if (! $res && file_exists("../../main.inc.php"))
	$res=@include("../../main.inc.php");	// For "custom" directory

if (! empty($conf->multicompany->enabled))
	dol_include_once('/multicompany/class/actions_multicompany.class.php', 'ActionsMulticompany');


if (! $user->rights->user->user->lire && ! $user->admin)
	accessforbidden();

$langs->load("users");
$langs->load("companies");
$langs->load("portofolio@portofolio");

// Security check (for external users)
$socid=0;
if ($user->societe_id > 0)
	$socid = $user->societe_id;

$action=GETPOST('action', 'alpha');
if ($action == 'chggrp') {
	if (GETPOST('value')==1) {
		// on ajoute
		$sql= " INSERT INTO ".MAIN_DB_PREFIX."usergroup_user (fk_user, fk_usergroup) values ";
		$sql.= " ( ".GETPOST('user');
		$sql.= " , ".GETPOST('usergroup');
		$sql.= ")";
	} else {
		// on supprime
		$sql= " DELETE FROM ".MAIN_DB_PREFIX."usergroup_user ";
		$sql.= " WHERE fk_user = ".GETPOST('user');
		$sql.= " AND fk_usergroup = ".GETPOST('usergroup');
	}
	//print $sql;
	$result = $db->query($sql);
}

$sall=GETPOST('sall', 'alpha');
$searchuser=GETPOST('searchuser', 'alpha');
$searchlogin=GETPOST('searchlogin', 'alpha');
$searchlastname=GETPOST('searchlastname', 'alpha');
$searchfirstname=GETPOST('searchfirstname', 'alpha');
$searchstatut=GETPOST('searchstatut', 'alpha');
$searchthirdparty=GETPOST('searchthirdparty', 'alpha');

if ($searchstatut == '') $searchstatut='1';

$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST('page', 'int');
if ($page == -1)
	$page = 0;
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
$limit = $conf->liste_limit;
if (! $sortfield) $sortfield="u.login";
if (! $sortorder) $sortorder="ASC";

$userstatic=new User($db);
$companystatic = new Societe($db);
$form = new Form($db);

// Both test are required to be compatible with all browsers
if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter")) {
	$searchuser="";
	$searchlogin="";
	$searchlastname="";
	$searchfirstname="";
	$searchstatut="";
	$searchthirdparty="";
}


/*
 * View
 */

llxHeader('', $langs->trans("MassGroupUserChange"));

$buttonmulticompany="";
$picto="portofolio@portofolio";
print_fiche_titre($langs->trans("MassGroupUserChange"), $buttonmulticompany, $picto);

$sql = "SELECT u.rowid, u.lastname, u.firstname, u.admin,  u.login, u.email, ";
if (DOL_VERSION >= "3.8.0")
	$sql.= " u.gender, u.fk_soc,";
else
	$sql.= " u.fk_societe as fk_soc ,";
$sql.= " u.datec, u.tms as datem, u.color, u.datelastlogin, u.ldap_sid, u.statut, u.entity,";
$sql.= " u2.login as login2, u2.firstname as firstname2, u2.lastname as lastname2,";
$sql.= " s.nom as name, s.canvas";
$sql.= " FROM ".MAIN_DB_PREFIX."user as u";
if (DOL_VERSION >= "3.8.0")
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON u.fk_soc = s.rowid";
else
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON u.fk_societe = s.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user as u2 ON u.fk_user = u2.rowid";

if (! empty($conf->multicompany->enabled) && $conf->entity == 1
	&& (! empty($conf->multicompany->transverse_mode)
	|| (! empty($user->admin) && empty($user->entity))))
	$sql.= " WHERE u.entity IS NOT NULL";
else
	$sql.= " WHERE u.entity IN (".getEntity('user', 1).")";

if ($socid > 0) $sql.= " AND u.fk_soc = ".$socid;
if ($searchuser != '') $sql.=natural_search(array('u.login', 'u.lastname', 'u.firstname'), $searchuser);
if ($searchthirdparty != '') $sql.=natural_search(array('s.nom'), $searchthirdparty);
if ($searchlogin != '') $sql.= natural_search("u.login", $searchlogin);
if ($searchlastname != '') $sql.= natural_search("u.lastname", $searchlastname);
if ($searchfirstname != '') $sql.= natural_search("u.firstname", $searchfirstname);
if ($searchstatut != '' && $searchstatut >= 0) $sql.= " AND (u.statut=".$searchstatut.")";
if ($sall) $sql.= natural_search(array('u.login', 'u.lastname', 'u.firstname', 'u.email', 'u.note'), $sall);
$sql.=$db->order($sortfield, $sortorder);

$result = $db->query($sql);
if ($result) {
	print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">'."\n";
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';

	$param="searchuser=".$searchuser."&sall=".$sall;
	$param.="&searchstatut=".$searchstatut;
	$param.="&searchstatut=".$searchstatut;
	$param.="&searchstatut=".$searchstatut;

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print_liste_field_titre(
					$langs->trans("Login"), $_SERVER['PHP_SELF'], "u.login",
					$param, "", "", $sortfield, $sortorder
	);
	print_liste_field_titre(
					$langs->trans("LastName"), $_SERVER['PHP_SELF'], "u.lastname",
					$param, "", "", $sortfield, $sortorder
	);
	print_liste_field_titre(
					$langs->trans("FirstName"), $_SERVER['PHP_SELF'], "u.firstname",
					$param, "", "", $sortfield, $sortorder
	);
	print_liste_field_titre(
					$langs->trans("Company"), $_SERVER['PHP_SELF'], "u.fk_soc",
					$param, "", "", $sortfield, $sortorder
	);

	//	liste des groupes d'utilisateurs

	$sqlgrp = "SELECT g.rowid, g.nom as name, g.entity";
	$sqlgrp.= " FROM ".MAIN_DB_PREFIX."usergroup as g";

	if (! empty($conf->multicompany->enabled) && $conf->entity == 1
		&& ($conf->multicompany->transverse_mode || ($user->admin && ! $user->entity)))
		$sqlgrp.= " WHERE g.entity IS NOT NULL";
	else
		$sqlgrp.= " WHERE g.entity IN (0, ".$conf->entity.")";

	$sqlgrp.= " ORDER BY g.nom";
	$resqlgroup = $db->query($sqlgrp);
	if ($resqlgroup) {
		$num = $db->num_rows($resqlgroup);
		$i = 0;
		while ($i < $num) {
			$objg = $db->fetch_object($resqlgroup);
			print_liste_field_titre($objg->name, $_SERVER['PHP_SELF'], "", $param, "", 'align="center"');
			$i++;
		}
	}

	print_liste_field_titre(
					$langs->trans("Status"), $_SERVER['PHP_SELF'], "u.statut",
					$param, "", 'align="right"', $sortfield, $sortorder
	);

	print '<td align="right">';
	print '<input type="image" class="liste_titre" name="button_search"';
	print ' src="'.img_picto($langs->trans("Search"), 'search.png', '', '', 1).'"';
	print ' value="'.dol_escape_htmltag($langs->trans("Search")).'"';
	print ' title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '</td>';
	print "</tr>\n";

	// Search bar
	if (! empty($conf->multicompany->enabled) && empty($conf->multicompany->transverse_mode)) $colspan++;
	print '<tr class="liste_titre">';
	print '<td><input type="text" name="searchlogin" size="6" value="'.$searchlogin.'"></td>';
	print '<td><input type="text" name="searchlastname" size="6" value="'.$searchlastname.'"></td>';
	print '<td><input type="text" name="searchfirstname" size="6" value="'.$searchfirstname.'"></td>';
	print '<td><input type="text" name="searchthirdparty" size="6" value="'.$searchthirdparty.'"></td>';
	if ($num > 0)
		print '<td colspan="'.$num.'">&nbsp;</td>';

	// Status
	print '<td align="right">';
	$statutArray = array('-1'=>'', '0'=>$langs->trans('Disabled'), '1'=>$langs->trans('Enabled'));
	print $form->selectarray('searchstatut', $statutArray, $searchstatut);
	print '</td>';

	$imgfilter = '<input type="image" class="liste_titre" name="button_removefilter"';
	$imgfilter.= ' src="'.img_picto($langs->trans("Search"), 'searchclear.png', '', '', 1).'"';
	$imgfilter.= ' value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'"';
	$imgfilter.= ' title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
	print '<td class="liste_titre" align="right">'.$imgfilter.'</td>';
	print "</tr>\n";

	$num = $db->num_rows($result);
	$i = 0;
	$user2=new User($db);

	$var=True;
	while ($i < $num) {
		$obj = $db->fetch_object($result);
		$var=!$var;

		$userstatic->id=$obj->rowid;
		$userstatic->ref=$obj->label;
		$userstatic->color=$obj->color;
		$userstatic->login=$obj->login;
		$userstatic->statut=$obj->statut;
		$userstatic->email=$obj->email;
		$userstatic->gender=$obj->gender;
		$userstatic->societe_id=$obj->fk_soc;
		$userstatic->firstname='';
		$userstatic->lastname=$obj->login;

		$li=$userstatic->getNomUrl(1, '', 0, 0, 24, 1);

		print "<tr ".$bc[$var].">";
		print '<td>';
		print $li;
		if (! empty($conf->multicompany->enabled) && $obj->admin && ! $obj->entity)
			print img_picto($langs->trans("SuperAdministrator"), 'redstar');
		else if ($obj->admin)
			print img_picto($langs->trans("Administrator"), 'star');

		print '</td>';

		print '<td bgcolor="'.$userstatic->color.'">'.ucfirst($obj->lastname).'</td>';
		print '<td>'.ucfirst($obj->firstname).'</td>';
		print "<td>";
		if ($obj->fk_soc) {
			$companystatic->id=$obj->fk_soc;
			$companystatic->name=$obj->name;
			$companystatic->canvas=$obj->canvas;
			print $companystatic->getNomUrl(1);
		} elseif ($obj->ldap_sid)
			print $langs->trans("DomainUser");
		else
			print $langs->trans("InternalUser");
		print '</td>';

		// Multicompany enabled
		if (! empty($conf->multicompany->enabled) && empty($conf->multicompany->transverse_mode)) {
			print '<td>';
			if (! $obj->entity)
				print $langs->trans("AllEntities");
			else {
				// $mc is defined in conf.class.php if multicompany enabled.
				if (is_object($mc)) {
					$mc->getInfo($obj->entity);
					print $mc->label;
				}
			}
			print '</td>';
		}

		$resqlgroup = $db->query($sqlgrp);
		if ($resqlgroup) {
			$numgrp = $db->num_rows($resqlgroup);
			$ig = 0;
			while ($ig < $numgrp) {
				$objg = $db->fetch_object($resqlgroup);
				$fk_user=$obj->rowid;
				$fk_usergroup=$objg->rowid;
				if ($user->rights->portofolio->setup) {
					$url='<a href="'.$_SERVER["PHP_SELF"].'?action=chggrp&amp;value=1';
					$url.='&amp;user='.$obj->rowid.'&amp;usergroup='.$objg->rowid.'">';
					$url.=img_picto($langs->trans("AddInGroup"), "off");
					$url.='</a>';
				} else
					$url = img_picto($langs->trans("NotInGroup"), "off");

				// on vérifie que le group est associé ou pas à l'utilisateur
				$sqlgroupuser= " SELECT rowid FROM ".MAIN_DB_PREFIX."usergroup_user as gu";
				$sqlgroupuser.= " WHERE fk_user = ".$fk_user;
				$sqlgroupuser.= " AND fk_usergroup = ".$fk_usergroup;
				$resgroupuser = $db->query($sqlgroupuser);
				if ($resgroupuser) {
					$numgrpuser = $db->num_rows($resgroupuser);
					if ($db->num_rows($resgroupuser) > 0) {
						if ($user->rights->portofolio->setup) {
							$url='<a href="'.$_SERVER["PHP_SELF"].'?action=chggrp&amp;value=0';
							$url.='&amp;user='.$fk_user.'&amp;usergroup='.$fk_usergroup.'">';
							$url.=img_picto($langs->trans("OutInGroup"), "on");
							$url.='</a>';
						} else
							$url = img_picto($langs->trans("PresentInGroup"), "on");
					}
				}
				print '<td class="nowrap" align="center">'.$url.'</td>';
				$ig++;
			}
		}
		// Statut
		$userstatic->statut=$obj->statut;
		print '<td align="right">'.$userstatic->getLibStatut(5).'</td>';
		print '<td>&nbsp;</td>';
		print "</tr>\n";
		$i++;
	}
	print "</table>";
	print "</form>\n";
	$db->free($result);
	$db->free($resqlgroup);
} else
	dol_print_error($db);

llxFooter();
$db->close();