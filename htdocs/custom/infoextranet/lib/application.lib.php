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
 * \file    lib/application.lib.php
 * \ingroup infoextranet
 * \brief   Library files with common functions for Application
 */

dol_include_once('/infoextranet/class/role.class.php');

/**
 * Prepare array of tabs for Application
 *
 * @param	Application	$object		Application
 * @return 	array					Array of tabs
 */
function applicationPrepareHead($object)
{
	global $db, $langs, $conf;

	$langs->load("infoextranet@infoextranet");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/infoextranet/application_card.php", 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';
	$h++;

//	if (isset($object->fields['note_public']) || isset($object->fields['note_private']))
//	{
//		$nbNote = 0;
//		if (!empty($object->note_private)) $nbNote++;
//		if (!empty($object->note_public)) $nbNote++;
//		$head[$h][0] = dol_buildpath('/infoextranet/application_note.php', 1).'?id='.$object->id;
//		$head[$h][1] = $langs->trans('Notes');
//		if ($nbNote > 0) $head[$h][1].= ' <span class="badge">'.$nbNote.'</span>';
//		$head[$h][2] = 'note';
//		$h++;
//	}

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->infoextranet->dir_output . "/application/" . dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir,'files',0,'','(\.meta|_preview.*\.png)$'));
	$nbLinks=Link::count($db, $object->element, $object->id);
	$head[$h][0] = dol_buildpath("/infoextranet/application_document.php", 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles+$nbLinks) > 0) $head[$h][1].= ' <span class="badge">'.($nbFiles+$nbLinks).'</span>';
	$head[$h][2] = 'document';
	$h++;

	$head[$h][0] = dol_buildpath("/infoextranet/application_agenda.php", 1).'?id='.$object->id;
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
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'application@infoextranet');

	return $head;
}

/**
 * Get all apps owned by thirdparty
 *
 * @param   int         $id         Id of thirdparty
 * @return  array
 */
function getAppOfThirdparty($id)
{
    global $db;

    $sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'infoextranet_societe_application sa INNER JOIN '.MAIN_DB_PREFIX.'infoextranet_application AS a ON sa.fk_app = a.rowid WHERE sa.fk_soc='.$id.' ORDER BY a.name';
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
 * Get all apps maintain by thirdparty
 *
 * @param   int         $id         Id of thirdparty
 * @return  array
 */
function getAppMaintainByThirdparty($id)
{
    global $db;

    $sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'infoextranet_application WHERE fk_soc_maintenance='.$id;
    $resql = $db->query($sql);
    if ($resql)
    {
        foreach ($resql as $key => $field)
            $arr[] = $field;
    }

    return $arr;
}

/**
 * Get all roles for an app
 *
 * @param   int         $id         Id of app
 * @return  array                   array($id, $id, ....)
 */
function getRolesOfApp($id)
{
    global $db;

    $sql = 'SELECT ar.fk_role FROM '.MAIN_DB_PREFIX.'infoextranet_application_role ar INNER JOIN '.MAIN_DB_PREFIX.'infoextranet_role AS r ON ar.fk_role = r.rowid WHERE ar.fk_app='.$id.' ORDER BY r.name';
    $arr = array();
    $resql = $db->query($sql);
    if ($resql)
    {
        foreach ($resql as $key => $field)
            $arr[] = $field['fk_role'];
    }

    return $arr;

}

/**
 * Get all Thirdparty of an app
 *
 * @param   int         $id         Thirdparty of app
 * @return  array                   array($id, $id, ....)
 */
function getSocOfApp($id)
{
    global $db;

    $sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'infoextranet_societe_application sa INNER JOIN '.MAIN_DB_PREFIX.'societe AS a ON sa.fk_soc = a.rowid WHERE sa.fk_app='.$id.' ORDER BY a.nom';
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
 * Function that get the Environment name of an application
 *
 * @param int   $id Id of app
 * @return array $arr
 */
function getEnvName($id)
{
    global $db;

    $sql = 'SELECT label FROM '.MAIN_DB_PREFIX.'infoextranet_environment WHERE rowid='.$id;
    $resql = $db->query($sql);
    $arr = array();
    if ($resql)
    {
        foreach ($resql as $key => $field)
            $arr[] = $field['label'];
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
 * Display all role name of an array of roleid (trunc at 20)
 *
 * @param   array           $roles              Array of roleid
 * @return  string                              String to display
 */
function printShortenRoles($roles)
{
    global $db;

    $str = '';
    if ($roles)
    {
        $entered = false;
        foreach ($roles as $key => $roleid)
        {
            $entered = true;
            $role = new Role($db);
            $role->fetch($roleid);
            $str.= $role->name.', ';
        }

        // If entered, delete last coma + space
        if ($entered)
            $str = substr($str, 0, -2);

    }

    return dol_trunc($str, 20);
}

/**
 * Display all thirdparty app's in an array of socid (trunc at 20)
 *
 * @param   array           $roles              Array of socid
 * @return  string                              String to display
 */
function printSoc($socs)
{
    global $db;

    $str = '';
    if ($socs)
    {
        $entered = false;
        foreach ($socs as $key => $socid)
        {
            $entered = true;
            $soc = new Societe($db);
            $soc->fetch($socid['fk_soc']);
            $str.= $soc->name.', ';
        }

        // If entered, delete last coma + space
        if ($entered)
            $str = substr($str, 0, -2);

    }
    return $str;
}