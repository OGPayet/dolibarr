<?php
/* Copyright (C) 2015-2018	Charlie BENKE		<charlie@patas-monkey.com>
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
 *	\file	   htdocs/myfield/class/myfield.class.php
 *	\ingroup	base
 *	\brief	  File of class to manage field visibility
 */


/**
 *	Class to manage categories
 */
class Myfield extends CommonObject
{
	public $element='myfield';
	public $table_element='myfield';

	var $label;
	var $context;
	var $author;
	var $active;
	var $typefield;
	var $color;
	var $initvalue;
	var $replacement;
	var $compulsory;
	var $sizefield;
	var $movefield;
	var $formatfield;
	var $querydisplay;

	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db	 Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
	}



	/**
	 * 	Load field into memory from database
	 *
	 * 	@param		int		$rowid	code of the field
	 * 	@return		int				<0 if KO, >0 if OK
	 */
	function fetch($rowid)
	{
//		global $conf;

		$sql = "SELECT rowid, label, context, author, active, color, initvalue,";
		$sql.= " movefield, typefield, replacement, compulsory, sizefield, formatfield, querydisplay";
		$sql.= " FROM ".MAIN_DB_PREFIX."myfield";
		$sql.= " WHERE rowid = ".$rowid;

		dol_syslog(get_class($this)."::fetch sql=".$sql);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql) > 0) {
				$res = $this->db->fetch_array($resql);

				$this->rowid		= $res['rowid'];
				$this->typefield	= $res['typefield'];
				$this->label		= $res['label'];
				$this->context		= $res['context'];
				$this->author		= $res['author'];
				$this->active		= $res['active'];
				$this->movefield	= $res['movefield'];
				$this->color		= $res['color'];
				$this->initvalue	= $res['initvalue'];
				$this->replacement	= $res['replacement'];
				$this->compulsory	= $res['compulsory'];
				$this->sizefield	= $res['sizefield'];
				$this->formatfield	= $res['formatfield'];
				$this->querydisplay	= $res['querydisplay'];


				$this->db->free($resql);

				return 1;
			} else
				return 0;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 * 	Add mylist into database
	 *
	 * 	@param	User	$user		Object user
	 * 	@return	int 				-1 : erreur SQL

	 */
	function create($user='')
	{
		global $langs, $user;  // $conf,
		$langs->load('myfield@myfield');

		$error=0;

		$this->label		=trim($this->label);
		$this->context		=(!empty($this->context)?trim($this->context):"");
		$this->author		=(!empty($this->author)?trim($this->author):"");
		$this->initvalue	=(!empty($this->initvalue)?trim($this->initvalue):"");
		$this->replacement	=(!empty($this->replacement)?trim($this->replacement):"");
		$this->compulsory	=($this->compulsory=='yes'?'true':'false');
		$this->sizefield	=(!empty($this->sizefield)?trim($this->sizefield):"");
		$this->formatfield	=(!empty($this->formatfield)?trim($this->formatfield):"");
		$this->querydisplay	=(!empty($this->querydisplay)?trim($this->querydisplay):"");


		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."myfield (";
		$sql.= " label,";
		$sql.= " context,";
		$sql.= " author,";
		$sql.= " active,";
		$sql.= " typefield,";
		$sql.= " movefield,";
		$sql.= " color,";
		$sql.= " initvalue,";
		$sql.= " replacement,";
		$sql.= " compulsory,";
		$sql.= " sizefield,";
		$sql.= " formatfield,";
		$sql.= " querydisplay";

		$sql.= ") VALUES (";
		$sql.= " '".$this->db->escape($this->label)."'";
		$sql.= ", '".$this->db->escape($this->context)."'";
		$sql.= ", '".$this->db->escape($this->author)."'";
		$sql.= ", ".$this->active;
		$sql.= ", ".$this->typefield;
		$sql.= ", ".($this->movefield?$this->movefield:'0');
		$sql.= ", '".$this->db->escape($this->color)."'";
		$sql.= ", '".$this->db->escape($this->initvalue)."'";
		$sql.= ", '".$this->db->escape($this->replacement)."'";
		$sql.= ", ".$this->db->escape($this->compulsory);
		if ($this->sizefield)
			$sql.= ", ".$this->db->escape($this->sizefield);
		else
			$sql.= ", null";
		$sql.= ", '".$this->db->escape($this->formatfield)."'";
		$sql.= ", '".$this->db->escape($this->querydisplay)."'";
		$sql.= ")";
//print $sql;
		dol_syslog(get_class($this).'::create sql='.$sql);
		if ($this->db->query($sql)) {
			$this->id=$this->db->last_insert_id(MAIN_DB_PREFIX."myfield");
			$this->db->commit();
			return $this->id;
		} else {
			$this->error=$this->db->error();
			dol_syslog(get_class($this)."::create error ".$this->error." sql=".$sql, LOG_ERR);
			$this->db->rollback();
			return 0;
		}
	}

	/**
	 * 	Update myfield
	 *
	 *	@param	User	$user		Object user
	 * 	@return	int		 			1 : OK
	 *		  					-1 : SQL error
	 *		  					-2 : invalid category
	 */
	function update($user='')
	{
		global $langs;  // $conf,
		$this->db->begin();

		$error=0;

		// si il y a un onglet on fait de meme
		$sql = "UPDATE ".MAIN_DB_PREFIX."myfield";
		$sql.= " SET label = '".$this->db->escape($this->label)."'";
		$sql.= ", context ='".$this->db->escape($this->context)."'";
		$sql.= ", author ='".$this->db->escape($this->author)."'";
		$sql.= ", active =".$this->db->escape($this->active);
		$sql.= ", movefield =".($this->movefield?$this->db->escape($this->movefield):'0');
		$sql.= ", color ='".$this->db->escape($this->color)."'";
		$sql.= ", initvalue = '".$this->db->escape($this->initvalue)."'";
		$sql.= ", replacement = '".$this->db->escape($this->replacement)."'";

		$sql.= ", compulsory =".($this->compulsory?$this->db->escape($this->compulsory):'null');
		if ($this->sizefield)
			$sql.= ", sizefield =".$this->db->escape($this->sizefield);
		else
			$sql.= ", sizefield =null";
		$sql.= ", formatfield ='".$this->db->escape($this->formatfield)."'";
		$sql.= ", querydisplay ='".$this->db->escape($this->querydisplay)."'";


		$sql.= " WHERE rowid =".$this->rowid;
		print $sql;

		dol_syslog(get_class($this)."::update sql=".$sql);

		if ($this->db->query($sql)) {
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 * 	Delete a field from database
	 *
	 * 	@param	User	$rowid		Object user that ask to delete
	 *	@return	void
	 */
	function delete($rowid)
	{
		//global $conf, $langs;

		$error=0;

		dol_syslog(get_class($this)."::delete");

		$sql  = "DELETE FROM ".MAIN_DB_PREFIX."myfield";
		if ($rowid > 0)
			$sql .= " WHERE rowid = ".$rowid;

		if (!$this->db->query($sql)) {
			$this->error=$this->db->lasterror();
			dol_syslog("Error sql=".$sql." ".$this->error, LOG_ERR);
			$error++;
		}
	}

	/**
	 * 	Retourne toutes les champs dans un tableau
	 *
	 *	@return	array					Tableau d'objet list
	 */
	function get_all_myfield($context='', $typefield=-1)
	{
//		global $conf;

		$sql = "SELECT rowid, label, context, author, color, active, initvalue,";
		$sql.= " movefield, typefield, replacement, compulsory, sizefield, formatfield, querydisplay";
		$sql.= " FROM ".MAIN_DB_PREFIX."myfield";
		$sql.= " WHERE 1 = 1";

		// si il y a des context de précisé
		if ($context !="") {
			if ($typefield >= 0) {
				$sql.= " AND ( context=''";
				$tblcontext=explode(":", $context);

				foreach ($tblcontext as $contextwhere)
					$sql.= " OR context like '%".$contextwhere."%' "; ;
				$sql.= ")";
			} else
				$sql.= " AND context='".$context."'";
		}
		if ($typefield >= 0)
			$sql.= " AND typefield =".$typefield ;
		$sql.= " ORDER BY context, label";
		//print $sql;

		$res = $this->db->query($sql);
		if ($res) {
			$cats = array ();
			while ($rec = $this->db->fetch_array($res)) {
				$cat = array ();
				$cat['rowid']		= $rec['rowid'];
				$cat['label']		= $rec['label'];
				$cat['context']		= $rec['context'];
				$cat['author']		= $rec['author'];
				$cat['active']		= $rec['active'];
				$cat['typefield']	= $rec['typefield'];
				$cat['movefield']	= $rec['movefield'];
				$cat['color']		= $rec['color'];
				$cat['initvalue']	= $rec['initvalue'];
				$cat['replacement']	= $rec['replacement'];
				$cat['compulsory']	= $rec['compulsory'];
				$cat['sizefield']	= $rec['sizefield'];
				$cat['formatfield']	= $rec['formatfield'];
				$cat['querydisplay']= $rec['querydisplay'];

				$cats[$rec['rowid']] = $cat;
			}
			return $cats;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}

	// permet de savoir si l'utilisateur a des droits d'accès particulier
	function getUserSpecialsRights($myFielId, $user, $querydisplay="")
	{
		global $conf, $object;
		$array_return = array();

		// si l'affichage du champ est conditionné par une requête SQL
		if (!empty($querydisplay)) {
			$array_return = array('read'=>1, 'write'=>1);
			$querydisplay = str_replace("#SEL#", "SELECT", $querydisplay);
			$querydisplay = str_replace("#ID#", ($object->rowid?$object->rowid:$object->id), $querydisplay);
			$resql=$this->db->query($querydisplay);
			if ($resql) {
				$obj = $this->db->fetch_object($resql);
				$array_return['read']=$obj->read;
				$array_return['write']=$obj->write;
			}
			return $array_return;
		}

		if (!empty($user->id)) {
			//If user is admin he get all rights by default
			if ($user->admin && $conf->global->MYFIELD_ADMIN_ALL_RIGHT) {
				$array_return = array('read'=>1, 'write'=>1);
			} else {
				require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
				$usr_group = new UserGroup($this->db);
				$group_array=$usr_group->listGroupsForUser($user->id);
				if (is_array($group_array) && count($group_array)>0) {
					$sql = 'SELECT rights FROM '.MAIN_DB_PREFIX.'myfield_usergroup_rights WHERE fk_myfield='.$myFielId;
					$sql .= ' AND fk_usergroup IN ('.implode(", ", array_keys($group_array)).')';

					dol_syslog(get_class($this).'::getUserSpecialsRights sql='.$sql);
					$resql=$this->db->query($sql);
					if ($resql) {
						$array_return=array('read'=>0, 'write'=>0);
						$nump = $this->db->num_rows($resql);
						if ($nump) {
							$array_return['read']=1;
							while ($obj = $this->db->fetch_object($resql)) //User in in group that allow write
								if ($obj->rights=='U' )
									$array_return['write']=1;
						}
						$this->db->free($resql);
					} else
						print $this->db->error();
				} else
					$array_return = array('read'=>1, 'write'=>1); // no usergroup : open bar
			}
		}
		return $array_return;
	}


	function getexporttable()
	{
		$tmp.="<?xml version='1.0' encoding='ISO-8859-1'?>\n";
		$tmp.="<myfields>\n";
		$arraymyfields=$this->get_all_myfield();
		foreach ($arraymyfields as $key=> $value ) {
			$tmp.="\t".'<myfield >'."\n";
			$tmp.="\t \t<label>".$value['label']."</label>\n";
			$tmp.="\t \t<context>".$value['context']."</context>\n";
			$tmp.="\t \t<author>".$value['author']."</author>\n";
			$tmp.="\t \t<active>".$value['active']."</active>\n";
			$tmp.="\t \t<typefield>".$value['typefield']."</typefield>\n";
			$tmp.="\t \t<movefield>".$value['movefield']."</movefield>\n";
			$tmp.="\t \t<color>".$value['color']."</color>\n";
			$tmp.="\t \t<initvalue>".$value['initvalue']."</initvalue>\n";
			$tmp.="\t \t<replacement>".$value['replacement']."</replacement>\n";
			$tmp.="\t \t<compulsory>".$value['compulsory']."</compulsory>\n";
			$tmp.="\t \t<sizefield>".$value['sizefield']."</sizefield>\n";
			$tmp.="\t \t<formatfield>".$value['formatfield']."</formatfield>\n";
			$tmp.="\t \t<querydisplay>".$value['querydisplay']."</querydisplay>\n";
			$tmp.="\t</myfield>\n";
		}
		$tmp.="</myfields>\n";
		return $tmp;
	}

	function importlist($xml, $deletebefore)
	{
		global $user;
		// on récupère le fichier et on le parse
		libxml_use_internal_errors(true);
		$sxe = simplexml_load_string($xml);
		if ($sxe === false) {
			echo "Erreur lors du chargement du XML\n";
			foreach (libxml_get_errors() as $error) {
				echo "\t", $error->message;
			}
			exit;
		}
		else
			$arraydata = json_decode(json_encode($sxe), true);

		// on supprime dans myFields

		if ($deletebefore ==1)
			$this->delete(0);
		// pour gérer la blague si il n'y a qu'un seul myfield dans le XML
		if (is_array($arraydata['myfield'][0]))
			$tblmyfields=$arraydata['myfield'];
		else
			$tblmyfields=$arraydata;

		foreach ($tblmyfields as $myfieldimport) {
			$this->label=			$myfieldimport['label'];
			$this->context=			$myfieldimport['context'];
			$this->author=			$myfieldimport['author'];
			$this->active=			$myfieldimport['active'];
			$this->typefield=		$myfieldimport['typefield'];
			$this->movefield=		$myfieldimport['movefield'];
			$this->color=			$myfieldimport['color'];
			$this->initvalue=		$myfieldimport['initvalue'];
			$this->replacement=		$myfieldimport['replacement'];
			$this->compulsory=		$myfieldimport['compulsory'];
			$this->sizefield=		$myfieldimport['sizefield'];
			$this->formatfield=		$myfieldimport['formatfield'];
			$this->querydisplay=	$myfieldimport['querydisplay'];

			$fk_myfield = $this->create($user);
		}
	}
}
?>