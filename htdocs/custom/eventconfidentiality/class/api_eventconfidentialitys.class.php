<?php
/* Copyright (C) 2018	Julien Vercruysse	<julien.vercruysse@elonet.fr>
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

use Luracast\Restler\RestException;

require_once DOL_DOCUMENT_ROOT.'/custom/eventconfidentiality/class/eventconfidentiality.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';

/**
 * API class for eventconfidentialitys
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class EventConfidentialitys extends DolibarrApi {
    /**
     * @var array   $FIELDS     Mandatory fields, checked when create and update object
     */
    static $FIELDS = array();

    /**
     * @var EventConfidentiality $eventconfidentiality {@type EventConfidentiality}
     */
    public $eventconfidentiality;

    /**
     * Constructor
     */
    function __construct() {
        global $db, $conf;
        $this->db = $db;

		$this->eventconfidentiality = new EventConfidentiality($this->db);

    }

	/**
	 * Get properties of a event confidentiality for an event
	 *
	 * Return an array with eventconfidentiality informations
	 *
	 * @param       int         $id         ID of event
	 * @return 	array|mixed data without useless information
	 *
	 * @throws 	RestException
	 */
	function getAllTagForEvent($id) {
		global $db, $conf, $langs;

		$langs->load("eventconfidentiality@eventconfidentiality");

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
					$array['level_label']      = html_entity_decode($langs->trans('EventConfidentialityModeVisible'));
				} else if($obj->level_confid == 1) {
					$array['level_label']      = html_entity_decode($langs->trans('EventConfidentialityModeBlurred'));
				} else if($obj->level_confid == 2) {
					$array['level_label']     = html_entity_decode($langs->trans('EventConfidentialityModeHidden'));
				}
				$array['label']        	      = $obj->label;

				$list_tag[] = $array;

				$i++;
			}
			$db->free($resql);
		}
		return $list_tag;
	}

	/**
	 * Get default tag, external and mode for an action and an origin
	 *
	 * Return an array with eventconfidentiality informations
	 *
	 * @param       string         $elementtype         Name of element concerned by event ('event','propal','order', ...)
	 * @param       int        $type_id         	Id of type of action (40, 50, ...)
	 * @param       int        $fk_object         	Id of event
	 * @return 	array|mixed data without useless information
	 *
	 * @throws 	RestException
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
     * Create EventConfidentiality Object
     *
     * @param   array   $request_data   Request data
     * @return  int                     ID of eventconfidentiality
     */
    function post($request_data = NULL)
    {
        foreach($request_data as $field => $value) {
            $this->eventconfidentiality->$field = $value;
        }
        if ($this->eventconfidentiality->create(DolibarrApiAccess::$user) < 0) {
            throw new RestException(500, "Error creating eventconfidentiality", array_merge(array($this->eventconfidentiality->error), $this->eventconfidentiality->errors));
        }

        return $this->eventconfidentiality->id;
    }

    /**
     * Get mode view for actual user and an event
     *
	 * @param       int        $object_id         	Id of event
     * @return  int                     Mode view
     */
    function getModeViewForActualUser($object_id)
    {
		global $db, $conf;

		$fk_tags = array();
		$sql = "SELECT";
		$sql .= " a.rowid, a.fk_object, a.fk_dict_tag_confid, a.externe, a.level_confid, t.label";
		$sql .= " FROM ".MAIN_DB_PREFIX."event_agenda as a,";
		$sql .= " ".MAIN_DB_PREFIX."c_eventconfidentiality_tag as t";
		$sql .= " WHERE a.fk_object = ".$object_id;
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
				$array['label']        	      = $obj->label;

				$fk_tags[] = $array;

				$i++;
			}
			$db->free($resql);
		}

		$mode = 2;
		$user_tags = explode(",",DolibarrApiAccess::$user->array_options['options_user_tag']);

		$usergroup = new UserGroup($db);
		$usergroups = $usergroup->listGroupsForUser(DolibarrApiAccess::$user->id);
		foreach($usergroups as $group) {
			$user_tags[] = $group->array_options['options_group_tag'];
		}

		$tmp_mode = -1;
		$externe = (empty(DolibarrApiAccess::$user->socid)?0:1); //Utilisateur interne ou externe

		foreach($fk_tags as $fk_tag) {
			if(in_array($fk_tag['fk_dict_tag_confid'],$user_tags) && $fk_tag['externe'] == $externe) { //Si on a un tag en commun et que ce tag est interne
				$tmp_mode = max($tmp_mode,$fk_tag['level_confid']);
			}
		}
		if($tmp_mode > -1) { //Si l'utilisateur un tag en commun avec l'event on considère la visilibité minimal parmi les tags en commun
			$mode = $tmp_mode;
		}

		return $mode;
    }
}
