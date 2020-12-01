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
 * \file    digitalsignaturemanager/lib/digitalsignaturemanager.lib.php
 * \ingroup digitalsignaturemanager
 * \brief   Library files with common functions for DigitalSignatureManager
 */

/**
 * Prepare admin pages header
 *
 * @return array
 */
function digitalsignaturemanagerAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("digitalsignaturemanager@digitalsignaturemanager");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/digitalsignaturemanager/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'digitalsignaturemanager');

	$head[$h][0] = dol_buildpath("/digitalsignaturemanager/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

    complete_head_from_modules($conf, $langs, null, $head, $h, 'digitalsignaturemanager', 'remove');

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

	$result = $user->admin;

	return $result;
}
