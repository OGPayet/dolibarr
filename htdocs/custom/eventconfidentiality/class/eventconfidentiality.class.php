<?php
/* Copyright (C) 2007-2012  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2014       Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2015       Florian Henry       <florian.henry@open-concept.pro>
 * Copyright (C) 2015       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2018       Open-DSI            <support@open-dsi.fr>
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
 * \file    eventconfidentiality/class/eventconfidentiality.class.php
 * \ingroup eventconfidentiality
 * \brief
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

/**
 * Class EventConfidentiality
 */
class EventConfidentiality extends CommonObject
{
	public $element = 'eventconfidentiality';
	public $table_element = 'eventconfidentiality';
    public $table_element_line = 'eventconfidentiality';
    public $fk_element = 'fk_eventconfidentiality';
    protected $ismultientitymanaged = 1;	// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe

    /**
     * Id
     * @var int
     */
    var $id;

	var $fk_object;  //Id of the event
	var $fk_dict_tag_confid; //Id of the tag
	var $externe;  //Externe/Interne
	var $level_confid;  //Confidentiality level
	var $level_label; //Confidentiality level label
	var $label;  //Label of tag

    /**
	 * Constructor
	 *
	 * @param   DoliDb      $db     Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}


	/**
     *    Add an relation event/confidentiality
     *
     *    @param	User	$user      		Object user making action
     *    @return   int 		        	Id of created event, < 0 if KO
     */
    public function create(User $user)
    {
        global $langs,$conf;

        $error=0;
        $now=dol_now();

        $this->db->begin();
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."event_agenda";
        $sql.= "(fk_object,";
        $sql.= "fk_dict_tag_confid,";
        $sql.= "externe,";
        $sql.= "level_confid";
        $sql.= ") VALUES (";
        $sql.= $this->fk_object.",";
        $sql.= $this->fk_dict_tag_confid.",";
        $sql.= $this->externe.",";
        $sql.= $this->level_confid;
        $sql.= ")";

        dol_syslog(get_class($this)."::add", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."event_agenda","rowid");

            if (! $error)
            {
		$this->db->commit();
		return $this->id;
            }
            else
            {
			$this->db->rollback();
			return -1;
            }
        }
        else
        {
            $this->db->rollback();
            $this->error=$this->db->lasterror();
            return -1;
        }

    }

	/**
     *    Load an relation event/confidentiality
     *
     *    @param	int		$id     	Id of action to get
     */
    function fetch($id)
    {
        global $langs;

		$sql = "SELECT";
		$sql .= " a.rowid, a.fk_object, a.fk_dict_tag_confid, a.externe, a.level_confid, t.label";
		$sql .= " FROM ".MAIN_DB_PREFIX."event_agenda as a,";
		$sql .= " ".MAIN_DB_PREFIX."c_eventconfidentiality_tag as t";
		$sql .= " WHERE a.rowid = ".$id;
		$sql .= " AND a.fk_dict_tag_confid = t.rowid";

        dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num=$this->db->num_rows($resql);
			$i = 0;
			while($i<$num) {
				$obj = $this->db->fetch_object($resql);

				$this->id                  = $obj->rowid;
				$this->fk_object           = $obj->fk_object;
				$this->fk_dict_tag_confid  = $obj->fk_dict_tag_confid;
				$this->externe             = $obj->externe;
				$this->level_confid        = $obj->level_confid;
				$this->externe             = $obj->externe;
				$this->level_confid        = $obj->level_confid;
				if($obj->level_confid == 0) {
					$this->level_label     = $langs->trans('EventConfidentialityModeVisible');
				} else if($obj->level_confid == 1) {
					$this->level_label     = $langs->trans('EventConfidentialityModeBlurred');
				} else if($obj->level_confid == 2) {
					$this->level_label     = $langs->trans('EventConfidentialityModeHidden');
				}
				$this->label        	   = $obj->label;

				$i++;
			}
			$this->db->free($resql);
		}
		else
        {
            $this->error=$this->db->lasterror();
            return -1;
        }
        return $this;
    }

	/**
     *    Update an relation event/confidentiality
     *
     *    @param	User	$user      		Object user making action
     *    @return   int 		        	Id of created event, < 0 if KO
     */
    public function update(User $user)
    {
        global $langs,$conf;

        $error=0;
        $now=dol_now();

        $this->db->begin();
        $sql = "UPDATE ".MAIN_DB_PREFIX."event_agenda";
        $sql.= " SET";
        $sql.= " externe = ".$this->externe;
        $sql.= ", level_confid=".$this->level_confid;
        $sql.= " WHERE fk_object=".$this->fk_object;
        $sql.= " AND fk_dict_tag_confid=".$this->fk_dict_tag_confid;

        dol_syslog(get_class($this)."::add", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if (! $error)
            {
		$this->db->commit();
		return $this->id;
            }
            else
            {
			$this->db->rollback();
			return -1;
            }
        }
        else
        {
            $this->db->rollback();
            $this->error=$this->db->lasterror();
            return -1;
        }

    }

    /**
     *    Delete eventconfidentiality
     *
     *    @param    int		$notrigger		1 = disable triggers, 0 = enable triggers
     *    @return   int 					<0 if KO, >0 if OK
     */
    function delete()
    {
        global $user,$langs,$conf;

        $error=0;

        $this->db->begin();

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."event_agenda";
        $sql.= " WHERE rowid=".$this->id;

        dol_syslog(get_class($this)."::delete", LOG_DEBUG);
        $res=$this->db->query($sql);
        if ($res < 0) {
		$this->error=$this->db->lasterror();
		$error++;
        }

		if (! $error) {
			$this->db->commit();
			return 1;
		} else	{
			$this->db->rollback();
			return -2;
		}
    }

	/**
     *    Get default external and mode for a tag, an action and an origin
     *
     *    @param	int		$id     	Id of action to get
     *    @param	string	$ref    	Ref of action to get
     *    @param	string	$ref_ext	Ref ext to get
     *    @return	int					<0 if KO, >0 if OK
     */
    function getDefaultMode($id_tag, $elementtype, $type_id, $fk_object)
    {
        global $langs;


		$sql = "SELECT d.external, d.mode";
		$sql .= " FROM";
		$sql .= " ".MAIN_DB_PREFIX."c_eventconfidentiality_default as d,";
		$sql .= " ".MAIN_DB_PREFIX."c_eventconfidentiality_default_cbl_tags as t,";
		$sql .= " ".MAIN_DB_PREFIX."c_eventconfidentiality_default_cbl_action_type as a";
		$sql .= " WHERE t.fk_line = d.rowid";
		$sql .= " AND a.fk_line = d.rowid";
		$sql .= " AND d.active = 1";
		$sql .= " AND d.element_origin LIKE '%".$elementtype."%'"; //Origin
		$sql .= " AND t.fk_target = ".$id_tag; //Tag id
		$sql .= " AND a.fk_target = ".$type_id; //Action id

        dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num=$this->db->num_rows($resql);
			$i = 0;
			while($i<$num) {
				$obj = $this->db->fetch_object($resql);

				$this->fk_object           = $fk_object;
				$this->fk_dict_tag_confid  = $id_tag;
				$this->externe             = (!empty($obj->external)?$obj->external:0);
				$this->level_confid        = $obj->mode;

				$i++;
			}
			$this->db->free($resql);
		}
		else
        {
            $this->error=$this->db->lasterror();
            return -1;
        }
        return $this;
    }
}