<?php
/* Copyright (C) 2018      Open-DSI             <support@open-dsi.fr>
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
 *	\file       htdocs/eventconfidentiality/lib/eventconfidentiality.lib.php
 * 	\ingroup	eventconfidentiality
 *	\brief      Functions for the module Event Confidentiality
 */

/**
 * Prepare array with list of tabs for admin
 *
 * @return  array				Array of tabs to show
 */
function eventconfidentiality_admin_prepare_head()
{
    global $langs, $conf, $user;
    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath("/eventconfidentiality/admin/setup.php", 1);
    $head[$h][1] = $langs->trans("Parameters");
    $head[$h][2] = 'settings';
    $h++;

    $head[$h][0] = dol_buildpath("/eventconfidentiality/admin/dictionaries.php", 1);
    $head[$h][1] = $langs->trans("Dictionary");
    $head[$h][2] = 'dictionaries';
    $h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
    complete_head_from_modules($conf, $langs, null, $head, $h, 'eventconfidentiality_admin');

    $head[$h][0] = dol_buildpath("/eventconfidentiality/admin/about.php", 1);
    $head[$h][1] = $langs->trans("About");
    $head[$h][2] = 'about';
    $h++;

    $head[$h][0] = dol_buildpath("/eventconfidentiality/admin/changelog.php", 1);
    $head[$h][1] = $langs->trans("OpenDsiChangeLog");
    $head[$h][2] = 'changelog';
    $h++;

    complete_head_from_modules($conf, $langs, null, $head, $h, 'eventconfidentiality_admin', 'remove');

    return $head;
}

/**
 *    Get default tag, external and mode for an action and an origin
 *
 */
function getDefaultTag($elementtype, $type_id, $fk_object)
{
	global $langs, $db, $conf;

	$return = array();
	$sql = "SELECT t.fk_target, d.external, d.mode";
	$sql .= " FROM";
	$sql .= " ".MAIN_DB_PREFIX."c_eventconfidentiality_default as d,";
	$sql .= " ".MAIN_DB_PREFIX."c_eventconfidentiality_default_cbl_tags as t,";
	$sql .= " ".MAIN_DB_PREFIX."c_eventconfidentiality_default_cbl_action_type as a";
	$sql .= " WHERE t.fk_line = d.rowid";
	$sql .= " AND a.fk_line = d.rowid";
	$sql .= " AND d.active = 1";
	$sql .= " AND d.element_origin LIKE '%".$elementtype."%'"; //Origin
	$sql .= " AND a.fk_target = ".$type_id; //Action id

	$resql = $db->query($sql);
	if ($resql) {
		$num=$db->num_rows($resql);
		$i = 0;
		while($i<$num) {
			$obj = $db->fetch_object($resql);

			$object = array();
			$object['fk_object']           = $fk_object;
			$object['fk_dict_tag_confid']  = $obj->fk_target;
			$object['externe']  = $obj->external;
			$object['level_confid']  = $obj->mode;

			$return[] = $object;

			$i++;
		}
		$db->free($resql);
	}
	return $return;
}

/**
 *    Load all tag for an object
 *
 */
function fetchAllTagForObject($id)
{
	global $langs, $db, $conf;

	$list_tag = array();
	$sql = "SELECT";
	$sql .= " a.rowid, a.fk_object, a.fk_dict_tag_confid, a.externe, a.level_confid, t.label";
	$sql .= " FROM ".MAIN_DB_PREFIX."event_agenda as a,";
	$sql .= " ".MAIN_DB_PREFIX."c_eventconfidentiality_tag as t";
	$sql .= " WHERE a.fk_object = ".$id;
	$sql .= " AND a.fk_dict_tag_confid = t.rowid";

	$resql = $db->query($sql);
	if ($resql) {
		$num=$db->num_rows($resql);
		$i = 0;
		while($i<$num) {
			$obj = $db->fetch_object($resql);

			$array = array();
			$array['id']                   = $obj->rowid;
			$array['fk_object']            = $obj->fk_object;
			$array['fk_dict_tag_confid']   = $obj->fk_dict_tag_confid;
			$array['externe']              = $obj->externe;
			$array['level_confid']         = $obj->level_confid;
			$array['externe']              = $obj->externe;
			$array['level_confid']         = $obj->level_confid;
			if($obj->level_confid == 0) {
				$array['level_label']      = $langs->trans('EventConfidentialityModeVisible');
			} else if($obj->level_confid == 1) {
				$array['level_label']      = $langs->trans('EventConfidentialityModeBlurred');
			} else if($obj->level_confid == 2) {
				$array['level_label']     = $langs->trans('EventConfidentialityModeHidden');
			}
			$array['label']        	      = $obj->label;

			$list_tag[] = $array;

			$i++;
		}
		$db->free($resql);
	}
	return $list_tag;
}
?>