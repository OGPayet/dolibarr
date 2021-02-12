<?php
/* Copyright (C) 2014-2017	Charlie BENKE	<charlie@patas-monkey.com>
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
 * or see http://www.gnu.org/
 */

/**
 *		\file	   htdocs/custom-parc/core/lib/custom-parc.lib.php
 *		\brief	  Ensemble de fonctions de base pour custom-parc
 */

function customlink_admin_prepare_head ()
{
	global $langs; //, $conf, $user;
	
	$h = 0;
	$head = array();
	
	$head[$h][0] = 'setup.php';
	$head[$h][1] = $langs->trans("Setup");
	$head[$h][2] = 'admin';
	
	$h++;
	$head[$h][0] = 'about.php';
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';

	return $head;
}

/**
 *	Return list of type
 *
 *	@param  string	$selected	   Preselected type
 *	@param  string	$htmlname	   Name of field in html form
 * 	@param	int		$showempty		Add an empty field
 * 	@param	int		$hidetext		Do not show label before combo box
 * 	@param	string	$forceall		Force to show products and services in combo list, whatever are activated modules
 *  @return	void
 */
function select_element_type($selected='', $htmlname='typeelement', $showempty=0, $hidetext=0)
{
	global $db, $langs; //, $user, $conf;

	if (empty($hidetext)) print $langs->trans("ElementType").': ';
	
	// boucle sur les ‚l‚ments
	$sql = "SELECT rowid, label, type, translatefile";
	$sql.= " FROM ".MAIN_DB_PREFIX."c_element_type";
	//$sql.= " WHERE active = 1";
	
	dol_syslog("Customlink.Lib::select_element_type sql=".$sql);

	$resql=$db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		if ($num) {
			print '<select class="flat" name="'.$htmlname.'">';
			if ($showempty) {
				print '<option value="-1"';
				if ($selected == -1) print ' selected="selected"';
				print '>&nbsp;</option>';
			}
			while ($i < $num) {

				$obj = $db->fetch_object($resql);
				$langs->load($obj->translatefile);
				print '<option value="'.$obj->type.'"';
				if ($obj->type == $selected) print ' selected="selected"';
				print ">".$langs->trans($obj->label)."</option>";
				$i++;
			}
			print '</select>';
		} else {
			// si pas de liste, on positionne un hidden à vide
			print '<input type="hidden" name="'.$htmlname.'" value=-1>';
		}
	}
}
// search the list of tag
function print_tag_list($element, $id)
{
	global $db; //, $langs, $user, $conf;
	
	$sql = "SELECT distinct rowid, tag FROM ".MAIN_DB_PREFIX."element_tag";
	$sql.=" WHERE element='".$element."' AND fk_element=".$id;
	$resql=$db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		if ($num) {
			print "<table class='noborder allwidth'>";
			print '<tr><td>';
			print '<form action="'.dol_buildpath("/customlink", 1).'/deltag.php" method="POST">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="redirect" value="http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'">';
			print '<input type="hidden" name="element" value="'.$element.'">';
			print '<input type="hidden" name="fk_element" value="'.$id.'">';
			print "<div style='display: inline;'>";
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				print '<div style="float: left; background:#E0E0E0;margin:2px;padding:5px;">';
				print '<a href="'.dol_buildpath("/customlink", 1).'/listetag.php?tag='.$obj->tag.'">'.$obj->tag.'</a>';

				// pour la suppression c'est trop chiant, on verra plus tard
				print '&nbsp;<button type="submit" name="delete" value="'.$obj->rowid.'">X</button>'; 
				print '</div>';
				$i++;
			}
			print "</div>";
			print '</form>';
			print '</td></tr>';
			print "</table>"; 
		}
	}
}

// search the list of tag
function print_tag_list_count($max=6)
{
	global $db; //, $langs, $user, $conf;

	$sql = "SELECT tag, count(*) as nb FROM ".MAIN_DB_PREFIX."element_tag";
	$sql.=" group by tag" ;
	$sql.=" order by count(*) desc, tag" ;
	$sql.= $db->plimit($max, 0);
	$resql=$db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		if ($num) {
			print "<div style='display: inline;'>";
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				print '<div style="float: left; background:#E0E0E0;margin:2px;padding:5px;">';
				print '<a href="listetag.php?tag='.$obj->tag.'">';
				print $obj->tag.'('.$obj->nb.')</a></div>';
				$i++;
			}
			print "</div>";
		}
	}
}