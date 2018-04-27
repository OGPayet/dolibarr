<?php
/* Copyright (C) 2010-2016	Regis Houssin	<regis.houssin@inodbox.com>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *      \file       /multicompany/core/triggers/interface_25_modMulticompany_MulticompanyWorkflow.class.php
 *      \ingroup    multicompany
 *      \brief      Trigger file for create milticompany data
 */


/**
 *      \class      InterfaceMulticompanyWorkflow
 *      \brief      Classe des fonctions triggers des actions personnalisees du module multicompany
 */

class InterfaceMulticompanyWorkflow
{
    private $db;

    /**
     *   Constructor
     *
     *   @param      DoliDB		$db		Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;

        $this->name = preg_replace('/^Interface/i','',get_class($this));
        $this->family = "multicompany";
        $this->description = "Triggers of this module allows to create multicompany data";
        $this->version = '4.0.0';            // 'development', 'experimental', 'dolibarr' or version
        $this->picto = 'multicompany@multicompany';
    }


    /**
     * Trigger name
     *
     * 	@return		string	Name of trigger file
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Trigger description
     *
     * 	@return		string	Description of trigger file
     */
    public function getDesc()
    {
        return $this->description;
    }

    /**
     * Trigger version
     *
     * 	@return		string	Version of trigger file
     */
    public function getVersion()
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
     * Function called when a Dolibarrr business event is done.
     * All functions "run_trigger" are triggered if file
     * is inside directory core/triggers
     *
     * 	@param		string		$action		Event action code
     * 	@param		Object		$object		Object
     * 	@param		User		$user		Object user
     * 	@param		Translate	$langs		Object langs
     * 	@param		conf		$conf		Object conf
     * 	@return		int						<0 if KO, 0 if no triggered ran, >0 if OK
     */
    public function run_trigger($action, $object, $user, $langs, $conf)
    {
        // Mettre ici le code a executer en reaction de l'action
        // Les donnees de l'action sont stockees dans $object

        if ($action == 'COMPANY_CREATE' || $action == 'COMPANY_MODIFY')
        {
		$linked_entity = GETPOST('linked_entity', 'int', 2); // limit to POST

		if ($linked_entity > 0)
		{
			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ". __FILE__ .". id=".$object->rowid);



			return $ret;
		}
        }

		return 0;
    }

}
