<?php
/* Copyright (C) 2002		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2009		Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2016-2017	Charlene Benke		<charlie@patas-monkey.com>
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
 *	\file	   /transporteur/class/transporteur_rate.class.php
 *	\ingroup	member
 *	\brief	  File of class to manage transporteur Rate
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';


/**
 *	Class to manage members type
 */
class transporteurRate extends CommonObject
{
	public $table_element = 'transporteur_rate';
	public $element = 'transporteur_rate';

	var $rowid;
	var $id;
	var $ref;
	var $label;
	var $fk_pays;
	var $weightmax;
	var $weightunit;
	var $color;

	var $subprice;

	var $active;		// le type est actif

	/**
	 *	Constructor
	 *
	 *	@param 		DoliDB		$db		Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
		$this->active = 1;
	}


/**
 *  Fonction qui permet de creer le status de l'adherent
 *
 *  @param	  User		$user		User making creation
 *  @return	 int						>0 if OK, < 0 if KO
 */
function create($user)
{
	global $conf;

	$sql = "INSERT INTO ".MAIN_DB_PREFIX."transporteur_rate (";
	$sql.= "label, color, fk_pays, weightmax, weightunit, subprice, entity, active";

	$sql.= ") VALUES (";
	$sql.= "  '".$this->db->escape($this->label)."'";
	$sql.= ", '".$this->db->escape($this->color)."'";
	$sql.= ", ".$this->fk_pays;
	$sql.= ", ".price2num($this->weightmax);
	$sql.= ", ".$this->weightunit;
	$sql.= ", ".price2num($this->subprice);
	$sql.= ", ".$conf->entity;
	$sql.= ", 1)";

	dol_syslog("transporteur_rate::create", LOG_DEBUG);
	$result = $this->db->query($sql);
	if ($result) {
		$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);
		return $this->id ;
	} else {
		$this->error=$this->db->error().' sql='.$sql;
		return -1;
	}
}


	/**
	 *  Met a jour en base donnees du type
	 *
	 *	@param		User	$user	Object user making change
	 *  @return		int				>0 if OK, < 0 if KO
	 */
	function update($user)
	{
		$this->libelle=trim($this->libelle);

		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
		$sql.= " SET active = ".$this->active;
		$sql.= ", label = '".$this->db->escape($this->label) ."'";
		$sql.= ", fk_pays = ".$this->fk_pays;
		$sql.= ", weightmax = ".price2num($this->weightmax);
		$sql.= ", weightunit = ".$this->weightunit;
		$sql.= ", subprice = ".price2num($this->subprice);

		$sql.= " WHERE rowid =".$this->id;
//print $sql;
		$result = $this->db->query($sql);
		if ($result) {
			return 1;
		} else {
			$this->error=$this->db->error().' sql='.$sql;
			return -1;
		}
	}

	/**
	 *	Fonction qui permet de supprimer le status de l'adherent
	 *
	 *	@param	  int		$rowid		Id of member type to delete
	 *  @return		int					>0 if OK, 0 if not found, < 0 if KO
	 */
	function delete($rowid='')
	{
		if (empty($rowid)) $rowid=$this->id;

		$sql = "DELETE FROM ".MAIN_DB_PREFIX.$this->table_element." WHERE rowid = ".$rowid;

		$resql=$this->db->query($sql);
		if ($resql) {
			if ($this->db->affected_rows($resql))
				return 1;
		} else
			print "Err Delete: ".$sql." - ".$this->db->error();

		return 0;
	}

	/**
	 *  Fonction qui permet de recuperer le status de l'adherent
	 *
	 *  @param 		int		$rowid		Id of member type to load
	 *  @return		int					<0 if KO, >0 if OK
	 */
	function fetch($rowid)
	{
		$sql = "SELECT *";
		$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element." as d";
		$sql .= " WHERE d.rowid = ".$rowid;

		dol_syslog($this->table_element."::fetch", LOG_DEBUG);

		$resql=$this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id			= $obj->rowid;
				$this->rowid		= $obj->rowid;
				$this->ref			= $obj->rowid;
				$this->label		= $obj->label;
				$this->color		= $obj->color;
				$this->fk_pays		= $obj->fk_pays;
				$this->weightmax	= $obj->weightmax;
				$this->weightunit	= $obj->weightunit;
				$this->subprice		= $obj->subprice;
				$this->active		= $obj->active;
			}
			return 1;
		} else {
			$this->error=$this->db->lasterror();
			return -1;
		}
	}

	/**
	 *  Return list of members' type
	 *
	 *  @return 	array	List of types of members
	 */
	function liste_array()
	{
		global $conf, $langs;

		$projectbudgettypes = array();

		$sql = "SELECT rowid, label";
		$sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element;
		$sql.= " WHERE entity = ".$conf->entity;
		$sql.= " ORDER BY label ";

		$resql=$this->db->query($sql);
		if ($resql) {
			$nump = $this->db->num_rows($resql);
			if ($nump) {
				$i = 0;
				while ($i < $nump) {
					$obj = $this->db->fetch_object($resql);
					$projectbudgettypes[$obj->rowid] = $langs->trans($obj->label);
					$i++;
				}
			}
		}
		else
			print $this->db->error();

		return $projectbudgettypes;
	}

	/**
	 *		Return clicable name (with picto eventually)
	 *
	 *		@param		int		$withpicto		0=No picto, 1=Include picto into link, 2=Only picto
	 *		@param		int		$maxlen			length max libelle
	 *		@return		string					String with URL
	 */
	function getNomUrl($withpicto=0, $maxlen=0)
	{
		global $langs;

		$result='';
		$label=$langs->trans("ShowTypeCard", $this->label);

		$link = '<a href="'.dol_buildpath('/transporteur/admin/type.php', 1).'?rowid='.$this->id.'"';
		$link.= ' title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
		$linkend='</a>';

		$picto='group';

		if ($withpicto) $result.=($link.img_object($label, $picto, 'class="classfortooltip"').$linkend);
		if ($withpicto && $withpicto != 2) $result.=' ';
		$result.=$link.($maxlen?dol_trunc($this->label, $maxlen):$this->label).$linkend;
		return $result;
	}
}