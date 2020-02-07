<?php
/* Copyright (C) 2020 Alexis LAURIER
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    interventionsurvey/lib/interventionsurvey.lib.php
 * \ingroup interventionsurvey
 * \brief   Library files with common functions for InterventionSurvey
 */

/**
 * Prepare admin pages header
 *
 * @return array
 */
function interventionsurveyAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("interventionsurvey@interventionsurvey");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/interventionsurvey/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("InterventionSurveySettings");
	$head[$h][2] = 'settings';
    $h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@interventionsurvey:/interventionsurvey/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@interventionsurvey:/interventionsurvey/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'interventionsurvey');

	$head[$h][0] = dol_buildpath("/interventionsurvey/admin/about.php", 1);
	$head[$h][1] = $langs->trans("InterventionSurveyAbout");
	$head[$h][2] = 'about';
    $h++;

    complete_head_from_modules($conf, $langs, null, $head, $h, 'interventionsurvey', 'remove');
	return $head;
}

/**
 * Check Permission to Admin pages for intervention survey module
 *
 * @return array
 */
function checkPermissionForAdminPages()
{
	global $conf, $user;

	$result = $user->rights->interventionsurvey->settings->manage || $user->admin;

	return $result;
}
