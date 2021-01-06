<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014 Maxime MANGIN maxime@tuxserv.fr
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *  \file       dev/skeletons/contratabonnement.class.php
 *  \ingroup    mymodule othermodule1 othermodule2
 *  \brief      This file is an example for a CRUD class file (Create/Read/Update/Delete)
 *				Initialy built by build_class_from_table on 2012-12-21 10:43
 */

// Put here all includes required by your class file
//require_once(DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php");
//require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
//require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");


/**
 *	Put here description of your class
 */
class Contratabonnement // extends CommonObject
{
	var $db;							//!< To store db handler
	var $error;							//!< To return error code (or message)
	var $errors=array();				//!< To return several error codes (or messages)
	//var $element='contratabonnement';			//!< Id that identify managed objects
	//var $table_element='contratabonnement';	//!< Name of table without prefix where object is stored

    var $id;

	var $fk_contratdet;
	var $fk_frequencerepetition;
	var $periodepaiement;
	var $remise;
	var $statut;


    /**
     *  Constructor
     *
     *  @param	DoliDb		$db      Database handler
     */
    function __construct($db)
    {
        $this->db = $db;
        return 1;
    }


    /**
     *  Create object into database
     *
     *  @param	User	$user        User that create
     *  @param  int		$notrigger   0=launch triggers after, 1=disable triggers
     *  @return int      		   	 <0 if KO, Id of created object if OK
     */
    function create($user, $notrigger=0)
    {
	global $conf, $langs;
		$error=0;

		// Clean parameters

		if (isset($this->fk_contratdet)) $this->fk_contratdet=trim($this->fk_contratdet);
		if (isset($this->fk_frequencerepetition)) $this->fk_frequencerepetition=trim($this->fk_frequencerepetition);
		if (isset($this->periodepaiement)) $this->periodepaiement=trim($this->periodepaiement);
		if (isset($this->remise)) $this->remise=trim($this->remise);



		// Check parameters
		// Put here code to add control on parameters values

        // Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."contratabonnement(";

		$sql.= "fk_contratdet,";
		$sql.= "fk_frequencerepetition,";
		$sql.= "periodepaiement,";
		$sql.= "remise";


        $sql.= ") VALUES (";

		$sql.= " ".(! isset($this->fk_contratdet)?'NULL':"'".$this->fk_contratdet."'").",";
		$sql.= " ".(! isset($this->fk_frequencerepetition)?'NULL':"'".$this->fk_frequencerepetition."'").",";
		$sql.= " ".(! isset($this->periodepaiement)?'NULL':"'".$this->periodepaiement."'").",";
		$sql.= " ".(! isset($this->remise)?'0':"'".$this->remise."'")."";


		$sql.= ")";

		$this->db->begin();

		dol_syslog(get_class($this)."::create sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."_contratabonnement");

			if (! $notrigger)
			{
	            // Uncomment this and change MYOBJECT to your own tag if you
	            // want this action call a trigger.

	            //// Call triggers
	            //include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
	            //$interface=new Interfaces($this->db);
	            //$result=$interface->run_triggers('MYOBJECT_CREATE',$this,$user,$langs,$conf);
	            //if ($result < 0) { $error++; $this->errors=$interface->errors; }
	            //// End call triggers
			}
        }

        // Commit or rollback
        if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::create ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
            return $this->id;
		}
    }


    /**
     *  Load object in memory from database
     *
     *  @param	int		$id    Id object
     *  @return int          	<0 if KO, >0 if OK
     */
    function fetch($id)
    {
	global $langs;
        $sql = "SELECT";
		$sql.= " t.rowid,";

		$sql.= " t.fk_contratdet,";
		$sql.= " t.fk_frequencerepetition,";
		$sql.= " t.periodepaiement,";
		$sql.= " t.remise,";
		$sql.= " t.statut";

        $sql.= " FROM ".MAIN_DB_PREFIX."contratabonnement as t";
		$sql.= " WHERE t.rowid = ".$id;

	dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id    = $obj->rowid;

				$this->fk_contratdet = $obj->fk_contratdet;
				$this->fk_frequencerepetition = $obj->fk_frequencerepetition;
				$this->periodepaiement = $obj->periodepaiement;
				$this->remise = $obj->remise;
				$this->statut = $obj->statut;

            }
            $this->db->free($resql);

            return 1;
        }
        else
        {
	    $this->error="Error ".$this->db->lasterror();
            dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
            return -1;
        }
    }


    /**
     *  Update object into database
     *
     *  @param	User	$user        User that modify
     *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
     *  @return int     		   	 <0 if KO, >0 if OK
     */
    function update($user=0, $notrigger=0)
    {
	global $conf, $langs;
		$error=0;

		// Clean parameters

		if (isset($this->fk_contratdet)) $this->fk_contratdet=trim($this->fk_contratdet);
		if (isset($this->fk_frequencerepetition)) $this->fk_frequencerepetition=trim($this->fk_frequencerepetition);
		if (isset($this->periodepaiement)) $this->periodepaiement=trim($this->periodepaiement);
		if (isset($this->remise)) $this->remise=trim($this->remise);
		if (isset($this->statut)) $this->statut=trim($this->statut);


		// Check parameters
		// Put here code to add control on parameters values

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX."contratabonnement SET";

		$sql.= " fk_contratdet=".(isset($this->fk_contratdet)?$this->fk_contratdet:"null").",";
		$sql.= " fk_frequencerepetition=".(isset($this->fk_frequencerepetition)?$this->fk_frequencerepetition:"null").",";
		$sql.= " statut=".(isset($this->statut)?$this->statut:"0").",";
		$sql.= " periodepaiement=".(isset($this->periodepaiement)?$this->periodepaiement:"null");


        $sql.= " WHERE rowid=".$this->id;

		$this->db->begin();

		dol_syslog(get_class($this)."::update sql=".$sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

        // Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
			return 1;
		}
    }


	/**
	 *  Delete object in database
	 *
     *	@param  User	$user        User that delete
     *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
	 *  @return	int					 <0 if KO, >0 if OK
	 */
	function delete($user, $notrigger=0)
	{
		global $conf, $langs;
		$error=0;

		$this->db->begin();

		if (! $error)
		{
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."contratabonnement";
		$sql.= " WHERE rowid=".$this->id;

		dol_syslog(get_class($this)."::delete sql=".$sql);
		$resql = $this->db->query($sql);
		if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

			$sql = "DELETE FROM ".MAIN_DB_PREFIX."contratabonnement_term";
		$sql.= " WHERE fk_contratabonnement=".$this->id;

			dol_syslog(get_class($this)."::delete sql=".$sql);
		$resql = $this->db->query($sql);
		if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
		}

        // Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
			return 1;
		}
	}



	/**
	 *	Initialise object with example values
	 *	Id must be 0 if object instance is a specimen
	 *
	 *	@return	void
	 */
	function initAsSpecimen()
	{
		$this->id=0;

		$this->fk_contratdet='';
		$this->fk_frequencerepetition='';
		$this->periodepaiement='';
		$this->remise='';


	}

}
?>
