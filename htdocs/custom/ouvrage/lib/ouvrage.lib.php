<?php
/* Ouvrage
 * Copyright (C) 2017       Inovea-conseil.com     <info@inovea-conseil.com>
 */

/**
 * \file    lib/ouvrage.lib.php
 * \ingroup ouvrage
 * \brief   ouvrage
 *
 * Show admin header
 */

/**
 * Prepare admin pages header
 *
 * @return array
 */
function ouvragePrepareHead()
{
	global $langs, $conf;

	$langs->load("ouvrage@ouvrage");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/ouvrage/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;
    $head[$h][0] = dol_buildpath("/ouvrage/admin/ouvrage_extrafields.php", 1);
    $head[$h][1] = $langs->trans("Extrafield");
    $head[$h][2] = 'extrafield';
    $h++;
        
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'ouvrage');

	return $head;
}
