<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2013 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file		lib/searcheverywhere.lib.php
 *	\ingroup	searcheverywhere
 *	\brief		This file is an example module library
 *				Put some comments here
 */

function searcheverywhereAdminPrepareHead()
{
    global $langs, $conf;

    $langs->load("searcheverywhere@searcheverywhere");

    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath("/searcheverywhere/admin/searcheverywhere_setup.php", 1);
    $head[$h][1] = $langs->trans("Settings");
    $head[$h][2] = 'settings';
    $h++;
    $head[$h][0] = dol_buildpath("/searcheverywhere/admin/about.php", 1);
    $head[$h][1] = $langs->trans("About");
    $head[$h][2] = 'about';
    $h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    //$this->tabs = array(
    //	'entity:+tabname:Title:@searcheverywhere:/searcheverywhere/mypage.php?id=__ID__'
    //); // to add new tab
    //$this->tabs = array(
    //	'entity:-tabname:Title:@searcheverywhere:/searcheverywhere/mypage.php?id=__ID__'
    //); // to remove a tab
    complete_head_from_modules($conf, $langs, $object, $head, $h, 'searcheverywhere');

    return $head;
}

function searcheverywhere_prepare_head($str)
{
	global $langs, $conf;
	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath('/searcheverywhere/search.php?keyword='.urlencode($str), 1);
	$head[$h][1] = $langs->trans('Searcheverywhere');
	$head[$h][2] = 'search';
	$h++;

	return $head;
}
