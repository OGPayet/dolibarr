<?php
/* Copyright (C) 2014-2016 Charlie BENKE  <charlie@patas-monkey.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * or see http://www.gnu.org/
 */

/**
 *		\file	   /restock/lib/restock.lib.php
 *		\brief	  Ensemble de fonctions de base pour le module restock
 *	  \ingroup	restock
 */

function restock_admin_prepare_head()
{
	global $langs; //, $conf;
	$langs->load('restock@restock');

	$h = 0;
	$head = array();

	$head[$h][0] = "setup.php";
	$head[$h][1] = $langs->trans("Setup");
	$head[$h][2] = 'setup';
	$h++;

	$head[$h][0] = "about.php";
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	return $head;
}