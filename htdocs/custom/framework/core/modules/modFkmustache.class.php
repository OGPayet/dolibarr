<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2013 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\defgroup	mymodule	MyModule module
 * 	\brief		MyModule module descriptor.
 * 	\file		core/modules/modMyModule.class.php
 * 	\ingroup	mymodule
 * 	\brief		Description and activation file for module MyModule
 */
global $conf;
if( isset($conf->framework) && $conf->framework->enabled ){
	dol_include_once('/framework/class/dolmodexts.class.php');
	global $langs;
	$langs->load('framework');
	class_alias('DolModExts', 'FKMUS');


}
else
	class_alias('DolibarrModules', 'FKMUS');

/**
 * Description and activation class for module MyModule
 */
class modFkmustache
	extends FKMUS
{


		public function FixGreffon(){
			$this->greffon= 1;
			$this->herit = 'framework';

			// No listen in general config module
			$this->special = 5;
			$this->hidden = true;

			$this->family = "Configuration";
		}


    /**
     * 	Constructor. Define names, constants, directories, boxes, permissions
     *
     * 	@param	DoliDB		$db	Database handler
     */
    public function __construct($db)
    {
        global $langs, $conf;

        $this->db = $db;

        // Id for module (must be unique).
        // Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
        $this->numero = 121055;
				$this->name = preg_replace('/^mod/i', '', get_class($this));

				if (isset($conf->framework) && $conf->framework->enabled) {
					$this->GetFileBuild();
					$this->loadOscssConf();
					// Boites
// 					$this->loadbox('/dolmessage/core/boxes/', '\CORE\DOLMESSAGE\\');
        }

        $this->FixGreffon();


        // Name of image file used for this module.
        // If file is in theme/yourtheme/img directory under name object_pictovalue.png
        // use this->picto='pictovalue'
        // If file is in module/img directory under name object_pictovalue.png
        // use this->picto='pictovalue@module'
        $this->picto = 'fkmustache@fkmustache'; // mypicto@mymodule
        // Defined all module parts (triggers, login, substitutions, menus, css, etc...)
        // for default path (eg: /mymodule/core/xxxxx) (0=disable, 1=enable)
        // for specific path of parts (eg: /mymodule/core/modules/barcode)
        // for specific css file (eg: /mymodule/css/mymodule.css.php)
        $this->module_parts = array(
            // Set this to 1 if module has its own trigger directory
            'triggers' => 0
            // Set this to 1 if module has its own login method directory
            //'login' => 0,
            // Set this to 1 if module has its own substitution function file
            //'substitutions' => 0,
            // Set this to 1 if module has its own menus handler directory
            //'menus' => 0,
            // Set this to 1 if module has its own barcode directory
            //'barcode' => 0,
            // Set this to 1 if module has its own models directory
            //'models' => 0,
            // Set this to relative path of css if module has its own css file
            //'css' => '/mymodule/css/mycss.css.php',
			, 'js' => array(
					'/framework/media/vendor/Mustache/extras/mustache.js'
			)
            // Set here all hooks context managed by module
//             'hooks' => array('dolmessage', 'greffon')
            // Set here all workflow context managed by module
            //'workflow' => array('order' => array('WORKFLOW_ORDER_AUTOCREATE_INVOICE'))


            , 'greffon' => array('framework')
        );

        // Data directories to create when module is enabled.
        // Example: this->dirs = array("/mymodule/temp");
//         $this->dirs = array();

        // Config pages. Put here list of php pages
        // stored into mymodule/admin directory, used to setup module.
        $this->config_page_url = array("index.php?page=fkmustache@fkmustache");

//         // Dependencies
//         // List of modules id that must be enabled if this module is enabled
//         $this->depends = array();
//         // List of modules id to disable if this one is disabled
//         $this->requiredby = array();
//         // Minimum version of PHP required by module
//         $this->phpmin = array(5, 3);
//         // Minimum version of Dolibarr required by module
//         $this->need_dolibarr_version = array(3, 2);
//         $this->langfiles = array("fkmustache@fkmustache"); // langfiles@mymodule
//         // Constants
//         // List of particular constants to add when module is enabled
//         // (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
//         // Example:
//         $this->const = array(
//
//         );

        // Array to add new pages in new tabs
        // Example:
//         $this->tabs = array(
//             //	// To add a new tab identified by code tabname1
//             //	'objecttype:+tabname1:Title1:langfile@mymodule:$user->rights->mymodule->read:/mymodule/mynewtab1.php?id=__ID__',
//             //	// To add another new tab identified by code tabname2
//             //	'objecttype:+tabname2:Title2:langfile@mymodule:$user->rights->othermodule->read:/mymodule/mynewtab2.php?id=__ID__',
//             //	// To remove an existing tab identified by code tabname
//             //	'objecttype:-tabname'
//         );
        // where objecttype can be
        // 'thirdparty'			to add a tab in third party view
        // 'intervention'		to add a tab in intervention view
        // 'order_supplier'		to add a tab in supplier order view
        // 'invoice_supplier'	to add a tab in supplier invoice view
        // 'invoice'			to add a tab in customer invoice view
        // 'order'				to add a tab in customer order view
        // 'product'			to add a tab in product view
        // 'stock'				to add a tab in stock view
        // 'propal'				to add a tab in propal view
        // 'member'				to add a tab in fundation member view
        // 'contract'			to add a tab in contract view
        // 'user'				to add a tab in user view
        // 'group'				to add a tab in group view
        // 'contact'			to add a tab in contact view
        // 'categories_x'		to add a tab in category view
        // (replace 'x' by type of category (0=product, 1=supplier, 2=customer, 3=member)
        // Dictionnaries
//         if (! isset($conf->fkmustache->enabled)) {
//             $conf->fkmustache=new stdClass();
//             $conf->fkmustache->enabled = 0;
//         }
//         $this->dictionnaries = array();



        //$r++;
        // Exports
//         $r = 1;
				/**
					@remarks End loaded config ans auto-config construct
				*/
				if (isset($conf->framework) && $conf->framework->enabled)
				$this->EndLoader();
    }

    /**
     * Function called when module is enabled.
     * The init function add constants, boxes, permissions and menus
     * (defined in constructor) into Dolibarr database.
     * It also creates data directories
     *
     * 	@param		string	$options	Options when enabling module ('', 'noboxes')
     * 	@return		int					1 if OK, 0 if KO
     */
    public function init($options = '')
    {
        global $db,$conf;

        $sql = array();


        return $this->_init($sql, $options);
    }

    /**
     * Function called when module is disabled.
     * Remove from database constants, boxes and permissions from Dolibarr database.
     * Data directories are not deleted
     *
     * 	@param		string	$options	Options when enabling module ('', 'noboxes')
     * 	@return		int					1 if OK, 0 if KO
     */
    public function remove($options = '')
    {
        $sql = array();

        return $this->_remove($sql, $options);
    }


    public function ListFile(){
			$this->files = array(
				 '/core/modules/modfkmustache.php'
				,'/class/action_fkmustache.class.php'

			);


    }

}