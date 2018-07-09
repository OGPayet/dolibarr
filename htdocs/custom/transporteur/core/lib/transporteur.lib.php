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
 *		\file	   htdocs/transpoteur/core/lib/transpoteur.lib.php
 *		\brief	  Ensemble de fonctions de base pour transpoteur
 */

/**
 *  Return array head with list of tabs to view object informations
 *
 *  @param	Object	$object		 Member
 *  @return array		   		head
 */
function transporteur_admin_prepare_head ()
{
	global $langs; //, $conf, $user;

	$h = 0;
	$head = array();

	$head[$h][0] = 'setup.php';
	$head[$h][1] = $langs->trans("Setup");
	$head[$h][2] = 'setup';

	$h++;
	$head[$h][0] = 'rate.php';
	$head[$h][1] = $langs->trans("Prices");
	$head[$h][2] = 'rate';

	$h++;
	$head[$h][0] = 'about.php';
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';

	return $head;
}