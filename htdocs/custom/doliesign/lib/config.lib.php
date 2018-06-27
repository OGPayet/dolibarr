<?php
/* Copyright (C) ---Put here your own copyright and developer email---
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
 * \file    lib/config.lib.php
 * \ingroup doliesign
 * \brief   Library files with common functions for Config
 */

/**
 * Prepare array of tabs for Config
 *
 * @param	Config	$object		Config
 * @return 	array					Array of tabs
 */
function configPrepareHead($object)
{
	global $db, $langs, $conf;

	$langs->load("doliesign@doliesign");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/doliesign/config_card.php", 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';
	$h++;

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->doliesign->dir_output . "/config/" . dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir,'files',0,'','(\.meta|_preview.*\.png)$'));
	$nbLinks=Link::count($db, $object->element, $object->id);

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'config@doliesign');

	return $head;
}