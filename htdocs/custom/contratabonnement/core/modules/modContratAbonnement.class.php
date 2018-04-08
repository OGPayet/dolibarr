<?php
/* Copyright (C) 2014      Maxime MANGIN <maxime@tuxserv.fr>

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
 *      \file       htdocs/contratabonnement/core/modules/modContratAbonnement.class.php
 *      \ingroup    contratabonnement
 *      \brief      Module d'abonnement aux contrats Dolibarr
 */
include_once(DOL_DOCUMENT_ROOT ."/core/modules/DolibarrModules.class.php");


/**
 *  Description and activation class for module MyModule
 */
class modContratAbonnement extends DolibarrModules
{
	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	function modContratAbonnement($db)
	{
        global $langs,$conf;

        $this->db = $db;

		$this->numero = 54134;
		$this->rights_class = 'contratabonnement';
		$this->family = "crm";
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "Gestion des abonnements";
		$this->version = '6.0.*';
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 0;
		$this->picto='contract';

		$this->module_parts = array('substitutions' => 1);

		$this->dirs = array();

		// Config pages. Put here list of php page, stored into mymodule/admin directory, to use to setup module.
		if (file_exists(DOL_DOCUMENT_ROOT ."/custom/contratabonnement")) { // Si on utilise le repertoire custom
			$this->config_page_url = array("contratabonnement_conf.php@custom/contratabonnement");
		}
		else {
			$this->config_page_url = array("contratabonnement_conf.php@contratabonnement");
		}

		// Dependencies
		$this->depends = array('modContrat');		// List of modules id that must be enabled if this module is enabled
		$this->requiredby = array();	// List of modules id to disable if this one is disabled
		$this->phpmin = array(5,0);					// Minimum version of PHP required by module
		$this->need_dolibarr_version = array(6,0);	// Minimum version of Dolibarr required by module
		$this->langfiles = array("contratabonnement@contratabonnement");

		$this->const = array();
		$this->tabs = array();

		if (file_exists(DOL_DOCUMENT_ROOT ."/custom/contratabonnement")) { // Si on utilise le repertoire custom
			array_push($this->tabs, 'contract:+subscription:SubscriptionsTab:contratabonnement@contratabonnement:/custom/contratabonnement/fiche.php?id=__ID__');
			if (file_exists(DOL_DOCUMENT_ROOT ."/custom/contratabonnement/fichesupplier.php")) {
                array_push($this->tabs, 'supplier_order:+subscriptionsupplier:SubscriptionsTab:contratabonnement@contratabonnement:/custom/contratabonnement/fichesupplier.php?id=__ID__');
            }
		}
		else {
            array_push($this->tabs, 'contract:+subscription:SubscriptionsTab:contratabonnement@contratabonnement:/contratabonnement/fiche.php?id=__ID__');
            if (file_exists(DOL_DOCUMENT_ROOT ."/contratabonnement/fichesupplier.php")) {
                array_push($this->tabs, 'supplier_order:+subscriptionsupplier:SubscriptionsTab:contratabonnement@contratabonnement:/contratabonnement/fichesupplier.php?id=__ID__');
            }
        }

		if (file_exists(DOL_DOCUMENT_ROOT ."/custom/contratabonnement/company_masse_envoi.php")) { // Si on utilise le repertoire custom
			array_push($this->tabs, 'thirdparty:+subscriptionmail:SubscriptionsTab:contratabonnement@contratabonnement:/custom/contratabonnement/company_masse_envoi.php?id=__ID__');
		}
		else if (file_exists(DOL_DOCUMENT_ROOT ."/contratabonnement/company_masse_envoi.php")) {
			array_push($this->tabs, 'thirdparty:+subscriptionmail:SubscriptionsTab:contratabonnement@contratabonnement:/contratabonnement/company_masse_envoi.php?id=__ID__');
		}

        // Dictionnaries
		$this->dictionnaries=array();

        // Boxes
		// Add here list of php file(s) stored in core/boxes that contains class to show a box.
        $this->boxes = array();			// List of boxes
		$this->boxes[0][1] = "box_subscriptions_contracts@contratabonnement";
		if (file_exists(DOL_DOCUMENT_ROOT ."/custom/contratabonnement/fichesupplier.php") || file_exists(DOL_DOCUMENT_ROOT ."/contratabonnement/fichesupplier.php")) {
            $this->boxes[1][1] = "box_subscriptions_commandes@contratabonnement";
        }

		// Permissions
		$this->rights = array();		// Permission array used by this module
		$r=0;
		// Main menu entries
		$this->menus = array();			// List of menus to add
		$r=0;

		// Exports
		$r=1;

	}

	/**
	 *		Function called when module is enabled.
	 *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *		It also creates data directories
	 *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
	 */
	function init($options='')
	{
		$sql = array();
		$result=$this->load_tables();
        $this->modifyCore();

		return $this->_init($sql, $options);
	}

    function modifyCore() {
        $file = DOL_DOCUMENT_ROOT .'/core/class/commonobject.class.php';
        if (file_exists($file)) {
            $content = file_get_contents($file);
            if (!preg_match("/MAXIME MANGIN/", $content)) {
                $content = str_replace('// Set classfile', '// TODO ajout MAXIME MANGIN
                    else if ($objecttype == "contratabonnement") {$classpath = "contrat/class"; $subelement = "contrat"; $module = "contratabonnement";}
                    // Set classfile', $content);

                $fileOpen = fopen($file, 'r+');
                fseek($fileOpen, 0);
                fputs($fileOpen, $content);
                fclose($fileOpen);
            }
        }
    }

	/**
	 *		Function called when module is disabled.
	 *      Remove from database constants, boxes and permissions from Dolibarr database.
	 *		Data directories are not deleted
	 *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
	 */
	function remove($options='')
	{
		$sql = array();
		return $this->_remove($sql, $options);
	}


	/**
	 *		Create tables, keys and data required by module
	 * 		Files llx_table1.sql, llx_table1.key.sql llx_data.sql with create table, create keys
	 * 		and create data commands must be stored in directory /mymodule/sql/
	 *		This function is called by this->init
	 *
	 * 		@return		int		<=0 if KO, >0 if OK
	 */
	function load_tables()
	{
		if (file_exists(DOL_DOCUMENT_ROOT ."/custom/contratabonnement/sql/")) { // Si on utilise le repertoire custom
			return $this->_load_tables('/custom/contratabonnement/sql/');
		}
		else {
			return $this->_load_tables('/contratabonnement/sql/');
		}
	}
}

?>
