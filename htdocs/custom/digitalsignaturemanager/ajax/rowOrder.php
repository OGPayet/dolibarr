<?php
/* Copyright (C) 2010-2015 Regis Houssin       <regis.houssin@capnetworks.com>
 * Copyright (C) 2017      Laurent Destailleur <eldy@users.sourceforge.net>
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
 *       \file       htdocs/core/ajax/row.php
 *       \brief      File to return Ajax response on Row move.
 *                   This ajax page is called when doing an up or down drag and drop.
 */

if (! defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1'); // Disable token renewal
}
if (! defined('NOREQUIREMENU'))  {
	define('NOREQUIREMENU', '1');
}
if (! defined('NOREQUIREHTML'))  {
	define('NOREQUIREHTML', '1');
}
if (! defined('NOREQUIREAJAX'))  {
	define('NOREQUIREAJAX', '1');
}

require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/genericobject.class.php';


/*
 * View
 */

top_httphead();

print '<!-- Ajax page called with url '.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.dol_escape_htmltag($_SERVER["QUERY_STRING"]).' -->'."\n";

$rowOrder = GETPOST('rowOrder');
$elementType = GETPOST('elementType');

dol_include_once('/digitalsignaturemanager/class/digitalsignaturedocument.class.php');
dol_include_once('/digitalsignaturemanager/class/digitalsignaturepeople.class.php');
dol_include_once('/digitalsignaturemanager/class/digitalsignaturecheckbox.class.php');
global $db;
$staticDigitalSignatureDocument = new DigitalSignatureDocument($db);
$staticDigitalSignaturePeople = new DigitalSignaturePeople($db);
$staticDigitalSignatureCheckBox = new DigitalSignatureCheckBox($db);

$managedElementTypeAndStaticInstance = array(
	$staticDigitalSignatureDocument->element => $staticDigitalSignatureDocument,
	$staticDigitalSignaturePeople->element => $staticDigitalSignaturePeople,
	$staticDigitalSignatureCheckBox->element => $staticDigitalSignatureCheckBox
);

// Registering the location of boxes
if ($managedElementTypeAndStaticInstance[$elementType])
{
	global $langs, $user;
	$langs = $GLOBALS['langs'];
	$orderOfElementId = array_filter(explode(',', $rowOrder));
	$db->begin();
	$errors = array();
	foreach($orderOfElementId as $position=>$id) {
		$staticObject = $managedElementTypeAndStaticInstance[$elementType];
		if($staticObject->fetch($id) > 0) {
			$staticObject->position = $position;
			$staticObject->update($user);
			$errors = array_merge($errors, $staticObject->errors);
		}
	}
	if(empty($errors))
	{
		$db->commit();
	}
	else {
		$db->rollback();
	}
}
