<?php
	include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';

	/**
	 * 	Class to describe module Warranty
	 */
	class modContacttiers extends DolibarrModules {
		/**
		 * 	Constructor
		 *
		 * 	@param	DoliDB	$db		Database handler
		 */
		function __construct($db) {
			global $conf, $langs;

			$this->db = $db;

			// Id for module (must be unique).
			// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
			$this->numero = 555555;
			// Key text used to identify module (for permissions, menus, etc...)
			$this->rights_class = '';
			$this->editor_name = "<b>Elonet</b>";
			$this->editor_web = "https://elonet.fr/";

			// Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
			// It is used to group modules in module setup page
			$this->family = "hr";
			// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
			$this->name = "Contact->Tiers";
			// Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
			$this->description = "Module de création d'un tiers depuis un contact";
			// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
			$this->version = '1.0';
			// Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
			$this->const_name = 'MAIN_MODULE_CONTACTTIERS';
			// Where to store the module in setup page (0=common,1=interface,2=other)
			$this->special = 0;
			// Name of png file (without png) used for this module.
			// Png file must be in theme/yourtheme/img directory under name object_pictovalue.png.
			$this->picto='img/logo.png@contacttiers';

			// Data directories to create when module is enabled.
			$this->dirs = array();

			// Config pages. Put here list of php page names stored in admmin directory used to setup module.
			$this->config_page_url = array();

			// Dependencies
			$this->depends = array();		// List of modules id that must be enabled if this module is enabled
			$this->requiredby = array();				// List of modules id to disable if this one is disabled
			$this->phpmin = array(5,1);					// Minimum version of PHP required by module
			$this->need_dolibarr_version = array(6,0);	// Minimum version of Dolibarr required by module
			$this->langfiles = array();

			// Constants
			$this->const = array();

			// hooks
			$this->module_parts = array(
				'hooks' => array('contactcard'),  // Set here all hooks context managed by module
			);

			// Boxes
			$this->boxes = array();			// List of boxes

			// Permissions
			$this->rights = array();		// Permission array used by this module

			// Main menu entries
			$this->menu = array();

			// New pages on tabs
			$this->tabs = array();
		}

		/**
		 *	Function called when module is enabled.
		 *	The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
		 *	It also creates data directories.
		 *
		 *	@return     int             1 if OK, 0 if KO
		 */
		function init($options='') {
			global $conf, $langs, $user;
			$this->load_tables();

			$sql = array();

			return $this->_init($sql);

		}

		/**
		 *	Function called when module is disabled.
		 *	Remove from database constants, boxes and permissions from Dolibarr database.
		 *	Data directories are not deleted.
		 *
		 *	@return     int             1 if OK, 0 if KO
		 */
		function remove($options='') {
			$sql = array();
			return $this->_remove($sql);
		}


		/**
		 * 	Create tables and keys required by module
		 * 	Files mymodule.sql and mymodule.key.sql with create table and create keys
		 * 	commands must be stored in directory /mymodule/sql/
		 * 	This function is called by this->init.
		 *
		 * 	@return		int		<=0 if KO, >0 if OK
		 */
		function load_tables() {
			global $conf, $langs, $user;
		}
	}

?>
