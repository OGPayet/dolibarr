<?php
/* Copyright (C) 2001-2005	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010	Laurent Destailleur 	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2014-2017	Charlie Benke			<charlie@patas-monkey.com>
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
 */

/**
 *	   \file	   customlink/core/patastools.php
 *	   \brief	  Home page for top menu tools
 */

$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");		// For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");	// For "custom" directory

$langs->load("companies");
$langs->load("other");

// Security check
$socid=0;
if ($user->societe_id > 0) $socid=$user->societe_id;

/*
 * View
 */

$socstatic=new Societe($db);

llxHeader("", $langs->trans("PatasTools"), "");

$text=$langs->trans("PatasTools");

print_fiche_titre($text, '', 'patastools.png@customlink');


$fileinfo= @file_get_contents('http://www.patas-monkey.com/docs/patasToolsInfos.html');
if ($fileinfo === FALSE)
	print $langs->trans("PatasToolsNews"); 		// not connected
else
	print $fileinfo;


llxFooter();
$db->close();