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

$type_source=GETPOST('type_source', 'alpha');
$fk_source=GETPOST('fk_source', 'alpha');
$tag=GETPOST('tag', 'alpha');
$redirect=GETPOST('redirect', 'alpha');

//print "type_source=".$type_source."<br>";
//print "fk_source=".$fk_source."<br>";
//print "tag=".$tag."<br>";
//print "redirect=".$redirect."<br>";

if (empty($type_source)) {
	setEventMessage($langs->trans("ErrorFieldRequired", $langs->transnoentities("TypeSource")), 'errors');
	$error++;
} else {
	// si on a pas de source
	if (empty($fk_source)) {	
		setEventMessage($langs->trans("ErrorFieldRequired", $langs->transnoentities("RefSource")), 'errors');
		$error++;
	}
}

// on controle la target
if (empty($tag)) {
	setEventMessage($langs->trans("ErrorFieldRequired", $langs->transnoentities("TypeTarget")), 'errors');
	$error++;
}

if (! $error) {
	// les fk_ sont déjà renseigné
	$object->type_source = $type_source;
	$object->fk_source	 = $fk_source;
	$object->tag		 = $tag;

	$result = $object->createtag($user);
	if ($result == -1) {
		$langs->load("errors");
		setEventMessage($object->error, 'errors');
		$error++;
	}
}

// on se positionne sur les même sources dans la liste
header("Location:".$redirect);
exit;

$db->close();