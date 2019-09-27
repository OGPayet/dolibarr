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
 * \file    lib/address.lib.php
 * \ingroup infoextranet
 * \brief   Library files with common functions for AddressExtra
 */

//dol_include_once('/infoextranet/class/role.class.php');

/**
 * Prepare array of tabs for AddressExtra
 *
 * @param	AddressExtra	    $object		AddressExtra
 * @return 	array				            Array of tabs
 */
function addressPrepareHead($object)
{
	global $db, $langs, $conf;

	$langs->load("infoextranet@infoextranet");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/infoextranet/address_card.php", 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';
	$h++;

//	if (isset($object->fields['note_public']))
//	{
//		$nbNote = 0;
//		if (!empty($object->note_public)) $nbNote++;
//		$head[$h][0] = dol_buildpath('/infoextranet/address_note.php', 1).'?id='.$object->id;
//		$head[$h][1] = $langs->trans('Notes');
//		if ($nbNote > 0) $head[$h][1].= ' <span class="badge">'.$nbNote.'</span>';
//		$head[$h][2] = 'note';
//		$h++;
//	}

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->infoextranet->dir_output . "/address/" . dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir,'files',0,'','(\.meta|_preview.*\.png)$'));
	$nbLinks=Link::count($db, $object->element, $object->id);
	$head[$h][0] = dol_buildpath("/infoextranet/address_document.php", 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles+$nbLinks) > 0) $head[$h][1].= ' <span class="badge">'.($nbFiles+$nbLinks).'</span>';
	$head[$h][2] = 'document';
	$h++;

	$head[$h][0] = dol_buildpath("/infoextranet/address_agenda.php", 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("Events");
	$head[$h][2] = 'agenda';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@infoextranet:/infoextranet/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@infoextranet:/infoextranet/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'address@infoextranet');

	return $head;
}

/**
 * Get all addresss owned by thirdparty
 *
 * @param   int         $id         Id of thirdparty
 * @return  array
 */
function getaddressOfThirdparty($id)
{
    global $db;

    $sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'infoextranet_societe_ip sa INNER JOIN '.MAIN_DB_PREFIX.'infoextranet_ip AS a ON sa.fk_ip = a.rowid WHERE sa.fk_soc='.$id.' ORDER BY a.name';
    $arr = array();
    $resql = $db->query($sql);
    if ($resql)
    {
        foreach ($resql as $key => $field)
            $arr[] = $field;
    }

    return $arr;
}

/**
 * Get all addresss maintain by thirdparty
 *
 * @param   int         $id         Id of thirdparty
 * @return  array
 */
function getAddressMaintainByThirdparty($id)
{
    global $db;

    $sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'infoextranet_ip a INNER JOIN '.MAIN_DB_PREFIX.'infoextranet_societe_ip AS sa ON sa.fk_ip = a.rowid WHERE a.fk_soc_maintenance='.$id.' ORDER BY a.name';
    $arr = array();
    $resql = $db->query($sql);
    if ($resql)
    {
        foreach ($resql as $key => $field)
            $arr[] = $field;
    }

    return $arr;
}

/**
 * Get contact of thirdparty for applicative maintenance
 *
 * @param   int         $socid          Id of thirdparty
 * @return  array
 */
function getContactForMaintenance($socid)
{
    global $db;

    $arr = array();
    $sql = "SELECT sc.fk_socpeople, t.element, t.source, t.code, t.libelle FROM llx_societe_contact AS sc INNER JOIN llx_c_type_contact
            AS t ON t.rowid = sc.fk_c_type_contact WHERE sc.element_id = " .
        $socid . " AND t.active = '1' AND t.code = 'MAINTENANCE'";

    $resql = $db->query($sql);
    if ($resql)
    {
        foreach($resql as $key => $field)
            $arr[] = $field;
    }

    return $arr;
}

/**
 * Get all roles for an Address
 *
 * @param   int         $id         Id of the Address
 * @return  array                   array($id, $id, ....)
 */
function getRolesOfAddress($id)
{
    global $db;

    $sql = 'SELECT ar.fk_role FROM '.MAIN_DB_PREFIX.'infoextranet_ip_role ar INNER JOIN '.MAIN_DB_PREFIX.'infoextranet_role AS r ON ar.fk_role = r.rowid WHERE ar.fk_ip='.$id.' ORDER BY r.name';
    $arr = array();
    $resql = $db->query($sql);
    if ($resql)
    {
        foreach ($resql as $key => $field)
            $arr[] = $field['fk_role'];
    }

    return $arr;

}