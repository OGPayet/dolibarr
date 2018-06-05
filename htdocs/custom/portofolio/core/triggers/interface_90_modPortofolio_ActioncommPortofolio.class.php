<?php
/* Copyright (C) 2005-2011 	Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 	Regis Houssin	   <regis.houssin@capnetworks.com>
 * Copyright (C) 2014-2017	Charlene BENKE		<charlie@patas-monkey.com>
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
 *  \file	   htdocs/management/core/triggers/interface_90_modManagement_Taskstatut.class.php
 *  \ingroup	core
 *  \brief	  Fichier de demo de personalisation des actions du workflow
 *  \remarks	Son propre fichier d'actions peut etre cree par recopie de celui-ci:
 *			  - Le nom du fichier doit etre: interface_99_modMymodule_Mytrigger.class.php
 *										   ou: interface_99_all_Mytrigger.class.php
 *			  - Le fichier doit rester stocke dans core/triggers
 *			  - Le nom de la classe doit etre InterfaceMytrigger
 *			  - Le nom de la propriete name doit etre Mytrigger
 */


/**
 *  Class of triggers for portofolio module
 */
class InterfaceActioncommPortofolio
{
	var $db;

	/**
	 *   Constructor
	 *
	 *   @param		DoliDB		$db	  Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;

		$this->name = preg_replace('/^Interface/i', '', get_class($this));
		$this->family = "dolibarr";
		$this->description = "Triggers of this module are empty functions. They have no effect.";
		$this->version = '3.9+2.3.0';			// 'development', 'experimental', 'dolibarr' or version
		$this->picto = 'technic';
	}

	/**
	 *   Return name of trigger file
	 *
	 *   @return	 string	  Name of trigger file
	 */
	function getName()
	{
		return $this->name;
	}

	/**
	 *   Return description of trigger file
	 *
	 *   @return	 string	  Description of trigger file
	 */
	function getDesc()
	{
		return $this->description;
	}

	/**
	 *   Return version of trigger file
	 *
	 *   @return	 string	  Version of trigger file
	 */
	function getVersion()
	{
		global $langs;
		$langs->load("admin");

		if ($this->version == 'development') return $langs->trans("Development");
		elseif ($this->version == 'experimental') return $langs->trans("Experimental");
		elseif ($this->version == 'dolibarr') return DOL_VERSION;
		elseif ($this->version) return $this->version;
		else return $langs->trans("Unknown");
	}

	/**
	 *	Function called when a Dolibarr business event is done.
	 *	All functions "run_trigger" are triggered if file is inside directory htdocs/core/triggers
	 *
	 *	@param	string		$action		Event action code
	 *	@param  Object		$object	 Object
	 *	@param  User		$user	   Object user
	 *	@param  Translate	$langs	  Object langs
	 *	@param  conf		$conf	   Object conf
	 *	@return int		 			<0 if KO, 0 if no triggered ran, >0 if OK
	 */
	function runTrigger($action, $object, $user, $langs, $conf)
	{
		if ($conf->global->PORTOFOLIO_ADDSALESMAN_TRIGGER == 1) {
			if ($action == 'ACTION_CREATE' || $action == 'ACTION_MODIFY') {
				// pour remettre le lien vers la fiche inter dans le bon sens
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

				foreach ($object->userassigned as $key => $value) {
					// dans le doute on supprime le commercial (si maj)
					$sql = "DELETE  from ".MAIN_DB_PREFIX."societe_commerciaux";
					$sql.= " WHERE fk_soc = ".$object->societe->id;
					$sql.= " AND fk_user = ".$value['id'];
					$result = $this->db->query($sql);

					// et enfin on ajoute ou pas le commercial
					$sql = "INSERT INTO ".MAIN_DB_PREFIX."societe_commerciaux";
					$sql.= " (fk_soc, fk_user) VALUES ";
					$sql.= " ( ".$object->societe->id.", ".$value['id'].")";
					$result = $this->db->query($sql);
				}
			}
		}
		return 0;
	}
}