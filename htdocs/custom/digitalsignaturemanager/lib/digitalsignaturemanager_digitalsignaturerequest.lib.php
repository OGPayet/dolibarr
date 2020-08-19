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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    lib/digitalsignaturemanager_digitalsignaturerequest.lib.php
 * \ingroup digitalsignaturemanager
 * \brief   Library files with common functions for DigitalSignatureRequest
 */

/**
 * Prepare array of tabs for DigitalSignatureRequest
 *
 * @param	DigitalSignatureRequest	$object		DigitalSignatureRequest
 * @return 	array					Array of tabs
 */
function digitalsignaturerequestPrepareHead($object)
{
	global $db, $langs, $conf;

	$langs->load("digitalsignaturemanager@digitalsignaturemanager");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/digitalsignaturemanager/digitalsignaturerequest_card.php", 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("DigitalSignatureCardTitle");
	$head[$h][2] = 'card';
	$h++;

	if (isset($object->fields['note_public']) || isset($object->fields['note_private']))
	{
		$nbNote = 0;
		if (!empty($object->note_private)) $nbNote++;
		if (!empty($object->note_public)) $nbNote++;
		$head[$h][0] = dol_buildpath('/digitalsignaturemanager/digitalsignaturerequest_note.php', 1).'?id='.$object->id;
		$head[$h][1] = $langs->trans("DigitalSignatureCardNotes");
		if ($nbNote > 0) $head[$h][1] .= (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '<span class="badge marginleftonlyshort">'.$nbNote.'</span>' : '');
		$head[$h][2] = 'note';
		$h++;
	}

	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$listOfSignedFiles = $object->getListOfSignedFiles('files', 0, '', '(\.meta|_preview.*\.png)$');
	$listOfFilesToSign = $object->getListOfFilesToSign('files', 0, '', '(\.meta|_preview.*\.png)$');
	$nbSignedFiles = count($listOfSignedFiles);
	$nbFilesToSign = count($listOfFilesToSign);
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][0] = dol_buildpath("/digitalsignaturemanager/digitalsignaturerequest_document.php", 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("DigitalSignatureCardLinkedFiles");
	if (($nbSignedFiles + $nbFilesToSign + $nbLinks) > 0) {
		$badgeListOfValue = array($nbFilesToSign, $nbSignedFiles, $nbLinks);
		$badgeListOfValue = array_filter($badgeListOfValue);
		$head[$h][1] .= '<span class="badge marginleftonlyshort">|'.(implode("|", $badgeListOfValue)).'</span>';
	}
	$head[$h][2] = 'document';
	$h++;

	$head[$h][0] = dol_buildpath("/digitalsignaturemanager/digitalsignaturerequest_agenda.php", 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("DigitalSignatureCardLinkedEvents");
	$head[$h][2] = 'agenda';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@digitalsignaturemanager:/digitalsignaturemanager/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@digitalsignaturemanager:/digitalsignaturemanager/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'digitalsignaturerequest@digitalsignaturemanager');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'digitalsignaturerequest@digitalsignaturemanager', 'remove');

	return $head;
}
