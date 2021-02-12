<?php
/* Copyright (C) 2014-2017		Charlie BENKE	<charlie@patas-monkey.com>
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
 *	\file	   htdocs/customlink/addtag.php
 *	\ingroup	tools
 *	\brief	  customelink addtag
 */

$res=@include("../main.inc.php");					// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../main.inc.php");		// For "custom" directory

dol_include_once('/customlink/class/customlink.class.php');
dol_include_once('/customlink/core/lib/customlink.lib.php');

$langs->load("customlink@customlink");


$object = new Customlink($db);

/*
 * Actions
 */

$idtag=GETPOST('delete', 'alpha');
$redirect=GETPOST('redirect', 'alpha');

//print "idtag=".$idtag."<br>";
//print "redirect=".$redirect."<br>";

// les fk_ sont déjà renseigné
$object->rowid = $idtag;

$result = $object->deletetag($user);
if ($result == -1) {
	$langs->load("errors");
	setEventMessage($object->error, 'errors');
	$error++;
}
// on se positionne sur les même sources dans la liste
header("Location:".$redirect);
exit;

$db->close();