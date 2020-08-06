<?php
/* changetiers
 * Copyright (C) 2018       Inovea-conseil.com     <info@inovea-conseil.com>
 */

/**
 * \file    lib/changetiers.lib.php
 * \ingroup changetiers
 * \brief   changetiers
 *
 * Show admin header
 */

/**
 * Prepare admin pages header
 *
 * @return array
 */
function changetiersPrepareHead()
{
	global $langs, $conf;

	$langs->load("changetiers@changetiers");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/changetiers/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'changetiers');

	return $head;
}