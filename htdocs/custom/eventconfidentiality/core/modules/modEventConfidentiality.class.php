<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2016 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2018      Open-DSI             <support@open-dsi.fr>
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
 * 	\defgroup   eventconfidentiality     Module Event Confidentiality
 *  \brief      Example of a module descriptor.
 *				Such a file must be copied into htdocs/eventconfidentiality/core/modules directory.
 *  \file       htdocs/eventconfidentiality/core/modules/modEventConfidentiality.class.php
 *  \ingroup    eventconfidentiality
 *  \brief      Description and activation file for module Event Confidentiality
 */
include_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

/**
 *  Description and activation class for module Event Confidentiality
 */
class modEventConfidentiality extends DolibarrModules
{
	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
    {
        global $langs, $conf;

        $this->db = $db;
        $langs->load('opendsi@eventconfidentiality');

        // Id for module (must be unique).
        // Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
        $this->numero = 163092;        // TODO Go on page http://wiki.dolibarr.org/index.php/List_of_modules_id to reserve id number for your module
        // Key text used to identify module (for permissions, menus, etc...)
        $this->rights_class = 'eventconfidentiality';

        // Family can be 'crm','financial','hr','projects','products','ecm','technic','interface','other'
        // It is used to group modules by family in module setup page
        $this->family = "opendsi";
        // Module position in the family
        $this->module_position = 500;
        // Gives the possibility to the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
        $this->familyinfo = array('opendsi' => array('position' => '001', 'label' => $langs->trans("OpenDsiFamily")));

        // Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        // Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
        $this->description = "Description of module Event Confidentiality";
        $this->descriptionlong = "";
        $this->editor_name = 'Open-DSI';
        $this->editor_url = 'http://www.open-dsi.fr';

        // Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'
        $this->version = '1.0.0';
        // Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
        $this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
        // Name of image file used for this module.
        // If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
        // If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
        $this->picto = 'opendsi@eventconfidentiality';

        // Defined all module parts (triggers, login, substitutions, menus, css, etc...)
        // for default path (eg: /mymodule/core/xxxxx) (0=disable, 1=enable)
        // for specific path of parts (eg: /mymodule/core/modules/barcode)
        // for specific css file (eg: /mymodule/css/mymodule.css.php)
        //$this->module_parts = array(
        //                        	'triggers' => 0,                                 	// Set this to 1 if module has its own trigger directory (core/triggers)
        //							'login' => 0,                                    	// Set this to 1 if module has its own login method directory (core/login)
        //							'substitutions' => 0,                            	// Set this to 1 if module has its own substitution function file (core/substitutions)
        //							'menus' => 0,                                    	// Set this to 1 if module has its own menus handler directory (core/menus)
        //							'theme' => 0,                                    	// Set this to 1 if module has its own theme directory (theme)
        //                        	'tpl' => 0,                                      	// Set this to 1 if module overwrite template dir (core/tpl)
        //							'barcode' => 0,                                  	// Set this to 1 if module has its own barcode directory (core/modules/barcode)
        //							'models' => 0,                                   	// Set this to 1 if module has its own models directory (core/modules/xxx)
        //							'css' => array('/mymodule/css/mymodule.css.php'),	// Set this to relative path of css file if module has its own css file
        //							'js' => array('/mymodule/js/mymodule.js'),          // Set this to relative path of js file if module must load a js on all pages
        //							'hooks' => array('hookcontext1','hookcontext2',...) // Set here all hooks context managed by module. You can also set hook context 'all'
        //							'dir' => array('output' => 'othermodulename'),      // To force the default directories names
        //							'workflow' => array('WORKFLOW_MODULE1_YOURACTIONTYPE_MODULE2'=>array('enabled'=>'! empty($conf->module1->enabled) && ! empty($conf->module2->enabled)', 'picto'=>'yourpicto@mymodule')) // Set here all workflow context managed by module
        //                        );
        $this->module_parts = array(
            'dictionaries' => 1,
            'hooks' => array(),
            'triggers' => 1
        );

        // Data directories to create when module is enabled.
        // Example: this->dirs = array("/mymodule/temp");
        $this->dirs = array('/eventconfidentiality');

        // Config pages. Put here list of php page, stored into mymodule/admin directory, to use to setup module.
        $this->config_page_url = array("setup.php@eventconfidentiality");

        // Dependencies
        $this->hidden = false;            // A condition to hide module
        $this->depends = array('modAgenda');        // List of modules id that must be enabled if this module is enabled
        $this->requiredby = array();    // List of modules id to disable if this one is disabled
        $this->conflictwith = array();    // List of modules id this module is in conflict with
        $this->phpmin = array(5, 0);                    // Minimum version of PHP required by module
        $this->need_dolibarr_version = array(4, 0);    // Minimum version of Dolibarr required by module
        $this->langfiles = array("eventconfidentiality@eventconfidentiality", "opendsi@eventconfidentiality");
        $langs->load('eventconfidentiality@eventconfidentiality');

        // Constants
        $this->const = array();


        $this->tabs = array();

        if (!isset($conf->eventconfidentiality) || !isset($conf->eventconfidentiality->enabled)) {
            $conf->eventconfidentiality = new stdClass();
            $conf->eventconfidentiality->enabled = 0;
        }

        // Dictionaries
        $this->dictionaries = array();

        // Boxes
        // Add here list of php file(s) stored in core/boxes that contains class to show a box.
        $this->boxes = array();            // List of boxes

        // Cronjobs
        $this->cronjobs = array();            // List of cron jobs entries to add

        // Permissions
        $this->rights = array();        // Permission array used by this module
        $r = 0;

        // Main menu entries
        $this->menu = array();            // List of menus to add
        $r = 0;

        // Exports
        $r = 1;
    }

	/**
	 *		Function called when module is enabled.
	 *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *		It also creates data directories
	 *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
	 */
	public function init($options='')
	{
	    global $langs, $conf;
		$sql = array();

        $this->_load_tables('/eventconfidentiality/sql/');

        // Create tables of all dictionaries
        dol_include_once('/advancedictionaries/class/dictionary.class.php');
        $dictionaries = Dictionary::fetchAllDictionaries($this->db, 'eventconfidentiality');
        foreach ($dictionaries as $dictionary) {
            if ($dictionary->createTables() < 0) {
                setEventMessage('Error create dictionary table: ' . $dictionary->errorsToString(), 'errors');
            }
        }

        return $this->_init($sql, $options);
	}

	/**
	 * Function called when module is disabled.
	 * Remove from database constants, boxes and permissions from Dolibarr database.
	 * Data directories are not deleted
	 *
	 * @param      string	$options    Options when enabling module ('', 'noboxes')
	 * @return     int             	1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
		$sql = array();

		return $this->_remove($sql, $options);
	}
}
