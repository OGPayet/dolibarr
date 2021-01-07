<?php
/* Copyright (C) 2014-2017	Charlene BENKE	<charlie@patas-monkey.com>
 * Module pour gerer la saisie pièces simplifiée
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
include_once(DOL_DOCUMENT_ROOT ."/core/modules/DolibarrModules.class.php");

/**
 * 		\class	  modcustomline
 *	  \brief	  Description and activation class for module customLine
 */
class modtransporteur extends DolibarrModules
{
	/**
	 *   \brief	  Constructor. Define names, constants, directories, boxes, permissions
	 *   \param	  DB	  Database handler
	 */
	function __construct($db)
	{

		$this->db = $db;

		global $langs; // $conf,

		$langs->load('transporteur@transporteur');

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 160190;

		$this->editor_name = "<b>Patas-Monkey</b>";
		$this->editor_web = "http://www.patas-monkey.com";

		// Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
		$this->family = "Patas-Tools";

		// Module label (no space allowed),
		$this->name = preg_replace('/^mod/i', '', get_class($this));

		// Module description, used if translation string 'ModuleXXXDesc'
		$this->description = $langs->trans("TransPorteurPresentation");

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = $this->getLocalVersion();

		// Key used in llx_const table to save module status enabled/disabled
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);

		// Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
		$this->special = 0;

		// Name of image file used for this module.
		$this->picto=$this->name .'.png@'.$this->name ;

		// Defined if the directory /mymodule/inc/triggers/ contains triggers or not
		$this->module_parts = array(
			'hooks' => array( 'globalcard', 'propalcard', 'ordercard', 'invoicecard')
		);

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/mymodule/temp");
		$this->dirs = array();
		$r=0;


		// Dependencies
		// List of modules id that must be enabled if this module is enabled
		$this->requiredby = array();	// List of modules id to disable if this one is disabled
		$this->phpmin = array(4,3);					// Minimum version of PHP required by module
		$this->need_dolibarr_version = array(3,4);	// Minimum version of Dolibarr required by module

		$this->langfiles = array($this->name ."@". $this->name );

		// Config pages
		$this->config_page_url = array("setup.php@".$this->name );

		// Constants
		// List of particular constants to add when module is enabled
		$this->const = array();
		// Array to add new pages in new tabs

		$tabsArray = array();
		$this->tabs = $tabsArray;

		// Boxes
		$this->boxes = array();			// List of boxes
		$r=0;

		// Permissions
		$this->rights = array();
		$this->rights_class = $this->name ;
		$r=0;

		$r++;
		$this->rights[$r][0] = 1601901;
		$this->rights[$r][1] = 'Permettre la suppression des taux';
		$this->rights[$r][2] = 's';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'setup';


		// $this->rights[$r][0]	 Id permission (unique tous modules confondus)
		// $this->rights[$r][1]	 Libelle par defaut si traduction de cle "PermissionXXX"
		// $this->rights[$r][2]	 Non utilise
		// $this->rights[$r][3]	 1=Permis par defaut, 0=Non permis par defaut
		// $this->rights[$r][4]	 Niveau 1 pour nommer permission dans code
		// $this->rights[$r][5]	 Niveau 2 pour nommer permission dans code


		// Main menu entries
		$this->menus = array();			// List of menus to add

	}

	/**
	 *		\brief	  Function called when module is enabled.
	 *					The init function add constants, boxes, permissions and menus
	 *					(defined in constructor) into Dolibarr database.
	 *					It also creates data directories.
	 *	  \return	 int			 1 if OK, 0 if KO
	 */
	function init($options = '')
	{
		$sql = array();
		$result=$this->load_tables();
		return $this->_init($sql, $options);
	}

	/**
	 *		\brief		Function called when module is disabled.
	 *			  	Remove from database constants, boxes and permissions from Dolibarr database.
	 *					Data directories are not deleted.
	 *	  \return	 int			 1 if OK, 0 if KO
	 */
	function remove($options = '')
	{
		$sql = array();
		return $this->_remove($sql, $options);
	}

	function load_tables()
	{
		return $this->_load_tables('/'.$this->name.'/sql/');
	}


	function getVersion($translated = 1)
	{
		global $langs, $conf;
		$currentversion = $this->version;

		if ($conf->global->PATASMONKEY_SKIP_CHECKVERSION == 1)
			return $currentversion;

		if ($this->disabled) {
			$newversion= $langs->trans("DolibarrMinVersionRequiered")." : ".$this->dolibarrminversion;
			$currentversion="<font color=red><b>".img_error($newversion).$currentversion."</b></font>";
			return $currentversion;
		}

		$context  = stream_context_create(array('http' => array('header' => 'Accept: application/xml')));
		$changelog = @file_get_contents(
						str_replace("www", "dlbdemo", $this->editor_web).'/htdocs/custom/'.$this->name.'/changelog.xml',
						false, $context
		);
		//$htmlversion = @file_get_contents($this->editor_web.$this->editor_version_folder.$this->name.'/');

		if ($htmlversion === false)	// not connected
			return $currentversion;
		else {
			$sxelast = simplexml_load_string(nl2br($changelog));
			if ($sxelast === false)
				return $currentversion;
			else
				$tblversionslast=$sxelast->Version;

			$lastversion = $tblversionslast[count($tblversionslast)-1]->attributes()->Number;

			if ($lastversion != (string) $this->version) {
				if ($lastversion > $this->version) {
					$newversion= $langs->trans("NewVersionAviable")." : ".$lastversion;
					$currentversion="<font title='".$newversion."' color=orange><b>".$currentversion."</b></font>";
				}
				else
					$currentversion="<font title='Version Pilote' color=red><b>".$currentversion."</b></font>";
			}
		}
		return $currentversion;
	}

	function getChangeLog()
	{
		// Libraries
		dol_include_once("/".$this->name."/core/lib/patasmonkey.lib.php");
		return getChangeLog($this->name);
	}

	function getLocalVersion()
	{
		global $langs;
		$context  = stream_context_create(array('http' => array('header' => 'Accept: application/xml')));
		$changelog = @file_get_contents(dol_buildpath($this->name, 0).'/changelog.xml', false, $context);
		$sxelast = simplexml_load_string(nl2br($changelog));
		if ($sxelast === false)
			return $langs->trans("ChangelogXMLError");
		else {
			$tblversionslast=$sxelast->Version;
			$currentversion = $tblversionslast[count($tblversionslast)-1]->attributes()->Number;
			$tblDolibarr=$sxelast->Dolibarr;
			$minversionDolibarr=$tblDolibarr->attributes()->minVersion;
			if (DOL_VERSION < $minversionDolibarr) {
				$this->dolibarrminversion=$minversionDolibarr;
				//$this->disabled = true;
			}
		}
		return $currentversion;
	}



}
