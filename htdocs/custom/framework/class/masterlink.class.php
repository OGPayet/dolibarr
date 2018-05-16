<?php
/* Copyright (C) 2016		 Oscss-Shop       <support@oscss-shop.fr>
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



class masterlink {

	public $db;

	function __construct($db){
		$this->db = $db ;
	}

	function create(){
		global $conf;

		// Create withdraw receipt in database
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."masterlink (";
		$sql.= " original, entity,custom, active ";
		$sql.= ") VALUES (";
		$sql.= "'".addslashes($this->original)."'";
		$sql.= ", '".(int)$conf->entity."' ";
		$sql.= ", '".addslashes($this->custom)."' ";
		$sql.= ", '".$this->active."'";
		$sql.= ")";

		dol_syslog(get_class($this)."::Create sql=".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);

		if ($resql)
		{
				$this->db->commit();
		}
		else
		{
				$error++;
				$this->db->rollback();
		}
	}

	function update(){

		$sql = "UPDATE ".MAIN_DB_PREFIX."masterlink  ";
		$sql.= " SET ";

		if(!empty($this->original) )
			$sql.= " original = '".$this->original."' ,";
		if(!empty($this->custom) )
			$sql.= " custom = '".$this->custom."' ,";

		if(!empty($this->active) )
			$sql.= " active = '".$this->active."' ,";

		$sql = substr($sql,0, -1);
		$sql.= " WHERE rowid = '".$this->id."' ";
// 		$sql.= " SET ";


		$result=$this->db->query($sql);
		if ($result)
		{
				$this->db->commit();
		}
		else
		{
				$error++;
				$this->db->rollback();
		}

	}

	function fetch($id=0, $original=''){
		global $conf;

		$sql = "SELECT p.rowid, p.original, p.custom, p.active";
		$sql.= " FROM ".MAIN_DB_PREFIX."masterlink as p";
		$sql.= " WHERE 1 ";
		if($id > 0 )
			$sql.= " AND p.rowid = ".$id;
		elseif(!empty($original))
			$sql.= " AND p.original= '".$original."' ";
		$sql.= " AND p.entity = ".$conf->entity;
// 		$sql.= " AND p.active = 1";
// echo $sql;
		dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
		$result=$this->db->query($sql);
		if ($result)
		{
				if ($this->db->num_rows($result))
				{
						$obj = $this->db->fetch_object($result);

						$this->id                 = $obj->rowid;
						$this->rowid                 = $obj->rowid;
						$this->original                = $obj->original;
						$this->custom             = $obj->custom;
						$this->active               = $obj->active;
						return 0;
				}
				else
				{
						dol_syslog(get_class($this)."::Fetch Erreur aucune ligne retournee");
						return -1;
				}
		}
		else
		{
				dol_syslog(get_class($this)."::Fetch Erreur sql=".$sql, LOG_ERR);
				return -2;
		}
	}

	function delete($id=0, $original='', $cutsom=''){

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."masterlink WHERE 1 ";
		if($id > 0)
			$sql .=" AND rowid='".$id."' LIMIT  1 " ;
		else{
            if(empty($original)){
                $original = $this->original;
            }
            if(empty($cutsom)){
                $cutsom = $this->cutsom;
            }
			if(!empty($original) )
				$sql .=" AND  original='".$original."' " ;
			if(!empty($cutsom) )
				$sql .=" AND  custom='".$cutsom."' " ;
		}

		return $this->db->query($sql);
	}

	function fetchall($active = -1){
		global $conf;

		$sql = "SELECT p.rowid, p.original, p.custom, p.active";
		$sql.= " FROM ".MAIN_DB_PREFIX."masterlink as p";
		$sql.= " WHERE 1 ";
		$sql.= " AND p.entity = ".$conf->entity;
// 		if($active >=0)
// 			$sql.= " AND p.active = '".$conf->entity."' ";
// 		$sql.= " GROUP BY  p.original ";
		$sql.= " ORDER BY  p.active DESC";
// COUNT(rowid) as nbr,
// echo $sql;
		dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);

		$res = array();

		$result=$this->db->query($sql);
		if ($result)
		{
				$num = $this->db->num_rows($result);
				if ($num > 0 )
				{
					while ($i < $num) {
						$obj = $this->db->fetch_object($result);

						$obj->id                 = $obj->rowid;

						$res[] = $obj;

						$i++;
					}

					return $res;
				}
				else
				{
						dol_syslog(get_class($this)."::Fetch Erreur aucune ligne retournee");
						return -1;
				}
		}
		else
		{
				dol_syslog(get_class($this)."::Fetch Erreur sql=".$sql, LOG_ERR);
				return -2;
		}

	}

	function fetchalloriginal(){
	}
}
