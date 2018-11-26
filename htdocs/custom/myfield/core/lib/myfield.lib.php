<?php
/* Copyright (C) 2015-2016	Charlie BENKE	<charlie@patas-monkey.com>
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
 *		\file	   htdocs/myfield/core/lib/myfield.lib.php
 *		\brief	  Ensemble de fonctions de base pour myfield
 */

/**
 *  Return array head with list of tabs to view object informations
 *
 *  @param	Object	$object		 Member
 *  @return array		   		head
 */
function myfield_admin_prepare_head ()
{
	global $langs; //, $conf, $user;

	$h = 0;
	$head = array();

	$head[$h][0] = 'setup.php';
	$head[$h][1] = $langs->trans("Setup");
	$head[$h][2] = 'setup';

	$h++;
	$head[$h][0] = 'about.php';
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';

	return $head;
}

/**
 *  Return array head with list of tabs to view object informations
 *
 *  @param	Object	$object		 Member
 *  @return array		   		head
 */
function myfield_prepare_head ($object)
{
	global $langs; //, $conf, $user;

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath('/myfield/card.php?rowid='.$object->rowid, 1);
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';


	return $head;
}

function SelectActiveMode($selected, $withHidden=true)
{
	global $langs;

	$tmp="<select name=activemode>";
	$tmp.="<option value='0' >".$langs->trans("Visible")."</option>";
	if ($withHidden)
		$tmp.="<option value='1' ".($selected=="1"?" selected ":"").">".$langs->trans("Hidden")."</option>";
	$tmp.="<option value='2' ".($selected=="2"?" selected ":"").">".$langs->trans("Invisible")."</option>";
	$tmp.="</select>";
	return $tmp;
}
function ShowActiveMode($selected)
{
	global $langs;
	$tmp=$langs->trans("Visible");
	$tmp=($selected=="1"?$langs->trans("Hidden"):$tmp);
	$tmp=($selected=="2"?$langs->trans("Invisible"):$tmp);
	return $tmp;
}

function SelectMyFieldType($selected, $showempty=0)
{
	global $langs;

	$tmp="<select name=typefield>";
	if ($showempty)
		$tmp.='<option value="-1"'.($selected == -1 ? ' selected="selected"':"").'>&nbsp;</option>';

	$tmp.="<option value='0' ".($selected=="0"?" selected ":"").">".$langs->trans("Field")."</option>";
	$tmp.="<option value='3' ".($selected=="3"?" selected ":"").">".$langs->trans("List")."</option>";
	$tmp.="<option value='1' ".($selected=="1"?" selected ":"").">".$langs->trans("Tabs")."</option>";
	$tmp.="<option value='2' ".($selected=="2"?" selected ":"").">".$langs->trans("Menu")."</option>";
	$tmp.="</select>";
	return $tmp;
}
function ShowMyFieldType($selected)
{
	global $langs;
	$tmp=$langs->trans("Field");
	$tmp=($selected=="1"?$langs->trans("Tabs"):$tmp);
	$tmp=($selected=="2"?$langs->trans("Menu"):$tmp);
	$tmp=($selected=="3"?$langs->trans("List"):$tmp);

	return $tmp;
}

function SelectMyFieldMenuType($selected, $showempty=0)
{
	global $langs;

	$tmp="<select name=context>";
	if ($showempty)
		$tmp.='<option value="-1"'.($selected == -1 ? ' selected="selected"':"").'>&nbsp;</option>';

	$tmp.="<option value='tmenu' ".($selected=="tmenu"?" selected ":"").">".$langs->trans("MainMenu")."</option>";
	$tmp.="<option value='vmenu' ".($selected=="vmenu"?" selected ":"").">".$langs->trans("LeftMenu")."</option>";
	$tmp.="<option value='vsmenu' ".($selected=="vsmenu"?" selected ":"").">".$langs->trans("SubLeftMenu")."</option>";
	$tmp.="</select>";
	return $tmp;
}
function ShowMyFieldMenuType($selected)
{
	global $langs;
	$tmp=$langs->trans("MainMenu");
	$tmp=($selected=="vmenu"?$langs->trans("LeftMenu"):$tmp);
	$tmp=($selected=="vsmenu"?$langs->trans("SubLeftMenu"):$tmp);
	return $tmp;
}