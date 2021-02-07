<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
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
 * 	\defgroup   parcautomobile     Module parcautomobile
 *  \brief      Example of a module descriptor.
 *				Such a file must be copied into htdocs/parcautomobile/core/modules directory.
 *  \file       htdocs/parcautomobile/core/modules/modparcautomobile.class.php
 *  \ingroup    parcautomobile
 *  \brief      Description and activation file for module parcautomobile
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';

/**
 *  Description and activation class for module parcautomobile
 */
class modparcautomobile extends DolibarrModules
{
	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
        global $langs,$conf;

        $this->db = $db;

		// $this->editor_name = 'Editor';
		// $this->editor_url = 'https://www.site.ma';
		
		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 1909680988; 
		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'parcautomobile';

		// Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
		// It is used to group modules in module setup page
		$this->family = "NextConcept";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		// Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
		$this->description = "ModuleDesc1909680988parcautomobile";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = '12.4';
		// Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
		$this->special = 0;
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		$this->picto='parcautomobile@parcautomobile';
		
		// Defined all module parts (triggers, login, substitutions, menus, css, etc...)
		// for default path (eg: /parcautomobile/core/xxxxx) (0=disable, 1=enable)
		// for specific path of parts (eg: /parcautomobile/core/modules/barcode)
		// for specific css file (eg: /parcautomobile/css/parcautomobile.css.php)
		//$this->module_parts = array(
		//                        	'triggers' => 0,                                 	// Set this to 1 if module has its own trigger directory (core/triggers)
		//							'login' => 0,                                    	// Set this to 1 if module has its own login method directory (core/login)
		//							'substitutions' => 0,                            	// Set this to 1 if module has its own substitution function file (core/substitutions)
		//							'menus' => 0,                                    	// Set this to 1 if module has its own menus handler directory (core/menus)
		//							'theme' => 0,                                    	// Set this to 1 if module has its own theme directory (theme)
		//                        	'tpl' => 0,                                      	// Set this to 1 if module overwrite template dir (core/tpl)
		//							'barcode' => 0,                                  	// Set this to 1 if module has its own barcode directory (core/modules/barcode)
		//							'models' => 0,                                   	// Set this to 1 if module has its own models directory (core/modules/xxx)
		//							'css' => array('/parcautomobile/css/parcautomobile.css.php'),	// Set this to relative path of css file if module has its own css file
	 	//							'js' => array('/parcautomobile/js/parcautomobile.js'),          // Set this to relative path of js file if module must load a js on all pages
		//							'hooks' => array('hookcontext1','hookcontext2')  	// Set here all hooks context managed by module
		//							'dir' => array('output' => 'othermodulename'),      // To force the default directories names
		//							'workflow' => array('WORKFLOW_MODULE1_YOURACTIONTYPE_MODULE2'=>array('enabled'=>'! empty($conf->module1->enabled) && ! empty($conf->module2->enabled)', 'picto'=>'yourpicto@parcautomobile')) // Set here all workflow context managed by module
		//                        );
		$this->module_parts = array(
		    'css' => array("/parcautomobile/css/parcautomobile.css"),
		    'js' => array("/parcautomobile/js/parcautomobile.js.php"),
		    // 'hooks' => array('all'),
		    // 'hooks' => array(),
		);

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/parcautomobile/temp");
		$this->dirs = array();

		// Config pages. Put here list of php page, stored into parcautomobile/admin directory, to use to setup module.
		$this->config_page_url = array();
		// $this->config_page_url = array("admin.php@parcautomobile");
		$this->config_page_url = array("parcautomobile_setup.php@parcautomobile");

		// Dependencies
		$this->hidden = false;			// A condition to hide module
		$this->depends = array();		// List of modules id that must be enabled if this module is enabled
		$this->requiredby = array();	// List of modules id to disable if this one is disabled
		$this->conflictwith = array();	// List of modules id this module is in conflict with
		$this->phpmin = array(5,0);					// Minimum version of PHP required by module
		$this->need_dolibarr_version = array(3,0);	// Minimum version of Dolibarr required by module
		$this->langfiles = array("parcautomobile@parcautomobile");

		// Constants
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example: $this->const=array(0=>array('MYMODULE_MYNEWCONST1','chaine','myvalue','This is a constant to add',1),
		//                             1=>array('MYMODULE_MYNEWCONST2','chaine','myvalue','This is another constant to add',0, 'current', 1)
		// );
		$this->const = array();

		// Array to add new pages in new tabs
		// Example: $this->tabs = array('objecttype:+tabname1:Title1:parcautomobile@parcautomobile:$user->rights->parcautomobile->read:/parcautomobile/mynewtab1.php?id=__ID__',  	// To add a new tab identified by code tabname1
        //                              'objecttype:+tabname2:Title2:parcautomobile@parcautomobile:$user->rights->othermodule->read:/parcautomobile/mynewtab2.php?id=__ID__',  	// To add another new tab identified by code tabname2
        //                              'objecttype:-tabname:NU:conditiontoremove');                                                     						// To remove an existing tab identified by code tabname
		// where objecttype can be
		// 'categories_x'	  to add a tab in category view (replace 'x' by type of category (0=product, 1=supplier, 2=customer, 3=member)
		// 'contact'          to add a tab in contact view
		// 'contract'         to add a tab in contract view
		// 'group'            to add a tab in group view
		// 'intervention'     to add a tab in intervention view
		// 'invoice'          to add a tab in customer invoice view
		// 'invoice_supplier' to add a tab in supplier invoice view
		// 'member'           to add a tab in fundation member view
		// 'opensurveypoll'	  to add a tab in opensurvey poll view
		// 'order'            to add a tab in customer order view
		// 'order_supplier'   to add a tab in supplier order view
		// 'payment'		  to add a tab in payment view
		// 'payment_supplier' to add a tab in supplier payment view
		// 'product'          to add a tab in product view
		// 'propal'           to add a tab in propal view
		// 'project'          to add a tab in project view
		// 'stock'            to add a tab in stock view
		// 'thirdparty'       to add a tab in third party view
		// 'user'             to add a tab in user view
        $this->tabs = array();

        // Dictionaries
	    if (! isset($conf->parcautomobile->enabled))
        {
        	$conf->parcautomobile=new stdClass();
        	$conf->parcautomobile->enabled=0;
        }
		$this->dictionaries=array();
        /* Example:
        if (! isset($conf->parcautomobile->enabled)) $conf->parcautomobile->enabled=0;	// This is to avoid warnings
        $this->dictionaries=array(
            'langs'=>'parcautomobile@parcautomobile',
            'tabname'=>array(MAIN_DB_PREFIX."table1",MAIN_DB_PREFIX."table2",MAIN_DB_PREFIX."table3"),		// List of tables we want to see into dictonnary editor
            'tablib'=>array("Table1","Table2","Table3"),													// Label of tables
            'tabsql'=>array('SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table1 as f','SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table2 as f','SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table3 as f'),	// Request to select fields
            'tabsqlsort'=>array("label ASC","label ASC","label ASC"),																					// Sort order
            'tabfield'=>array("code,label","code,label","code,label"),																					// List of fields (result of select to show dictionary)
            'tabfieldvalue'=>array("code,label","code,label","code,label"),																				// List of fields (list of fields to edit a record)
            'tabfieldinsert'=>array("code,label","code,label","code,label"),																			// List of fields (list of fields for insert)
            'tabrowid'=>array("rowid","rowid","rowid"),																									// Name of columns with primary key (try to always name it 'rowid')
            'tabcond'=>array($conf->parcautomobile->enabled,$conf->parcautomobile->enabled,$conf->parcautomobile->enabled)												// Condition to show each dictionary
        );
        */

        // Boxes
		// Add here list of php file(s) stored in core/boxes that contains class to show a box.
        $this->boxes = array();			// List of boxes
		// Example:
		//$this->boxes=array(array(0=>array('file'=>'myboxa.php','note'=>'','enabledbydefaulton'=>'Home'),1=>array('file'=>'myboxb.php','note'=>''),2=>array('file'=>'myboxc.php','note'=>'')););

		// Permissions
		$this->rights = array();		// Permission array used by this module
		$r=1;

		// Add here list of permission defined by an id, a label, a boolean and two constant strings.

		$this->rights[$r][0] = $this->numero+$r;	// Permission id (must not be already used)
		$this->rights[$r][1] = 'consulter';	// Permission label
		$this->rights[$r][2] = 'r';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$this->rights[$r][3] = 1; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'lire';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;

		$this->rights[$r][0] = $this->numero+$r;
		$this->rights[$r][1] = 'Ajouter/Modifier';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'creer';
		$r++;

		$this->rights[$r][0] = $this->numero+$r;
		$this->rights[$r][1] = 'Supprimer';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'supprimer';
		$r++;
		
		// Main menu entries
		$this->menu = array();			// List of menus to add
		$r=0;

		// Add here entries to declare new menus

		// Top Menu

		$this->menu[$r]=array(	'fk_menu'=>0,
			'type'=>'top',
			'titre'=>'parcautomobile',
			'mainmenu'=>'parcautomobile',
			'leftmenu'=>'parcautomobile',
			'url'=>'/parcautomobile/index.php',
			'langs'=>'parcautomobile@parcautomobile',
			'position'=>203,
			'enabled'=>'1',
			'perms'=>'$user->rights->parcautomobile->lire',
			'target'=>'',
			'user'=>2);
		$r++;

		
		$this->menu[$r]=array('fk_menu'=>'fk_mainmenu=parcautomobile',
			'type'=>'left',
			'titre'=>'vehicules',
            'leftmenu'=>'vehicules',
			'url'=>'/parcautomobile/kanban.php',
			'langs'=>'parcautomobile@parcautomobile',
			'position'=>1,
			'enabled'=>'1',
			'perms'=>'$user->rights->parcautomobile->lire',
			'target'=>'',
			'user'=>2);
		$r++;

			$this->menu[$r]=array('fk_menu'=>'fk_mainmenu=parcautomobile,fk_leftmenu=vehicules',
				'type'=>'left',
				'titre'=>'list_vehicules',
				'url'=>'/parcautomobile/index.php',
				'langs'=>'parcautomobile@parcautomobile',
				'position'=>2,
				'enabled'=>'1',
				'perms'=>'$user->rights->parcautomobile->lire',
				'target'=>'',
				'user'=>2);
			$r++;

			$this->menu[$r]=array('fk_menu'=>'fk_mainmenu=parcautomobile,fk_leftmenu=vehicules',
				'type'=>'left',
				'titre'=>'add_vehicule',
				'url'=>'/parcautomobile/card.php?action=add',
				'langs'=>'parcautomobile@parcautomobile',
				'position'=>2,
				'enabled'=>'1',
				'perms'=>'$user->rights->parcautomobile->creer',
				'target'=>'',
				'user'=>2);
			$r++;

			$this->menu[$r]=array('fk_menu'=>'fk_mainmenu=parcautomobile,fk_leftmenu=vehicules',
				'type'=>'left',
				'titre'=>'releve_kilometrique',
				'url'=>'/parcautomobile/kilometrage/index.php',
				'langs'=>'parcautomobile@parcautomobile',
				'position'=>2,
				'enabled'=>'1',
				'perms'=>'$user->rights->parcautomobile->lire',
				'target'=>'',
				'user'=>2);
			$r++;

			$this->menu[$r]=array('fk_menu'=>'fk_mainmenu=parcautomobile,fk_leftmenu=vehicules',
				'type'=>'left',
				'titre'=>'costsvehicule',
				'url'=>'/parcautomobile/costsvehicule/index.php',
				'langs'=>'parcautomobile@parcautomobile',
				'position'=>2,
				'enabled'=>'1',
				'perms'=>'$user->rights->parcautomobile->lire',
				'target'=>'',
				'user'=>2);
			$r++;

			$this->menu[$r]=array('fk_menu'=>'fk_mainmenu=parcautomobile,fk_leftmenu=vehicules',
				'type'=>'left',
				'titre'=>'vehiculecontrat',
				'url'=>'/parcautomobile/contrat_parc/index.php',
				'langs'=>'parcautomobile@parcautomobile',
				'position'=>2,
				'enabled'=>'1',
				'perms'=>'$user->rights->parcautomobile->lire',
				'target'=>'',
				'user'=>2);
			$r++;
			

			$this->menu[$r]=array('fk_menu'=>'fk_mainmenu=parcautomobile,fk_leftmenu=vehicules',
				'type'=>'left',
				'titre'=>'suivi_essence',
				'url'=>'/parcautomobile/suivi_essence/index.php',
				'langs'=>'parcautomobile@parcautomobile',
				'position'=>2,
				'enabled'=>'1',
				'perms'=>'$user->rights->parcautomobile->lire',
				'target'=>'',
				'user'=>2);
			$r++;

			$this->menu[$r]=array('fk_menu'=>'fk_mainmenu=parcautomobile,fk_leftmenu=vehicules',
				'type'=>'left',
				'titre'=>'suivi_intervention',
				'url'=>'/parcautomobile/interventions_parc/index.php',
				'langs'=>'parcautomobile@parcautomobile',
				'position'=>2,
				'enabled'=>'1',
				'perms'=>'$user->rights->parcautomobile->lire',
				'target'=>'',
				'user'=>2);
			$r++;

			
			$this->menu[$r]=array('fk_menu'=>'fk_mainmenu=parcautomobile,fk_leftmenu=vehicules',
				'type'=>'left',
				'titre'=>'modeles_vehicule',
				'url'=>'/parcautomobile/modeles/index.php',
				'langs'=>'parcautomobile@parcautomobile',
				'position'=>2,
				'enabled'=>'1',
				'perms'=>'$user->rights->parcautomobile->lire',
				'target'=>'',
				'user'=>2);	
			$r++;


		$this->menu[$r]=array('fk_menu'=>'fk_mainmenu=parcautomobile',
			'type'=>'left',
			'titre'=>'analyse',
            'leftmenu'=>'analyse',
			'url'=>'/parcautomobile/analyses_couts/chart_couts.php',
			'langs'=>'parcautomobile@parcautomobile',
			'position'=>3,
			'enabled'=>'1',
			'perms'=>'$user->rights->parcautomobile->lire',
			'target'=>'',
			'user'=>2);
		$r++;

			$this->menu[$r]=array('fk_menu'=>'fk_mainmenu=parcautomobile,fk_leftmenu=analyse',
				'type'=>'left',
				'titre'=>'couts',
				'url'=>'/parcautomobile/analyses_couts/chart_couts.php',
				'langs'=>'parcautomobile@parcautomobile',
				'position'=>4,
				'enabled'=>'1',
				'perms'=>'$user->rights->parcautomobile->lire',
				'target'=>'',
				'user'=>2);
			$r++;

			$this->menu[$r]=array('fk_menu'=>'fk_mainmenu=parcautomobile,fk_leftmenu=analyse',
				'type'=>'left',
				'titre'=>'couts_estimes',
				'url'=>'/parcautomobile/analyses_couts/chart_couts_estimes.php',
				'langs'=>'parcautomobile@parcautomobile',
				'position'=>4,
				'enabled'=>'1',
				'perms'=>'$user->rights->parcautomobile->lire',
				'target'=>'',
				'user'=>2);
			$r++;

		$this->menu[$r]=array('fk_menu'=>'fk_mainmenu=parcautomobile',
			'type'=>'left',
			'titre'=>'configuration',
            'leftmenu'=>'config_',
			'url'=>'/parcautomobile/marques/index.php',
			'langs'=>'parcautomobile@parcautomobile',
			'position'=>5,
			'enabled'=>'1',
			'perms'=>'$user->rights->parcautomobile->lire',
			'target'=>'',
			'user'=>2);
		$r++;


			$this->menu[$r]=array('fk_menu'=>'fk_mainmenu=parcautomobile,fk_leftmenu=config_',
				'type'=>'left',
				'titre'=>'marque_model',
				'url'=>'/parcautomobile/marques/index.php',
				'langs'=>'parcautomobile@parcautomobile',
				'position'=>6,
				'enabled'=>'1',
				'perms'=>'$user->rights->parcautomobile->lire',
				'target'=>'',
				'user'=>2);
			$r++;

			$this->menu[$r]=array('fk_menu'=>'fk_mainmenu=parcautomobile,fk_leftmenu=config_',
				'type'=>'left',
				'titre'=>'modeles',
				'url'=>'/parcautomobile/modeles/index.php',
				'langs'=>'parcautomobile@parcautomobile',
				'position'=>10,
				'enabled'=>'1',
				'perms'=>'$user->rights->parcautomobile->lire',
				'target'=>'',
				'user'=>2);
			$r++;

			$this->menu[$r]=array('fk_menu'=>'fk_mainmenu=parcautomobile,fk_leftmenu=config_',
				'type'=>'left',
				'titre'=>'typeintervention',
				'url'=>'/parcautomobile/typeintervention/index.php',
				'langs'=>'parcautomobile@parcautomobile',
				'position'=>7,
				'enabled'=>'1',
				'perms'=>'$user->rights->parcautomobile->lire',
				'target'=>'',
				'user'=>2);
			$r++;

			$this->menu[$r]=array('fk_menu'=>'fk_mainmenu=parcautomobile,fk_leftmenu=config_',
				'type'=>'left',
				'titre'=>'typecontrat',
				'url'=>'/parcautomobile/typecontrat/index.php',
				'langs'=>'parcautomobile@parcautomobile',
				'position'=>8,
				'enabled'=>'1',
				'perms'=>'$user->rights->parcautomobile->lire',
				'target'=>'',
				'user'=>2);
			$r++;

			$this->menu[$r]=array('fk_menu'=>'fk_mainmenu=parcautomobile,fk_leftmenu=config_',
				'type'=>'left',
				'titre'=>'statut',
				'url'=>'/parcautomobile/statut/index.php',
				'langs'=>'parcautomobile@parcautomobile',
				'position'=>9,
				'enabled'=>'1',
				'perms'=>'$user->rights->parcautomobile->lire',
				'target'=>'',
				'user'=>2);
			$r++;


			$this->menu[$r]=array('fk_menu'=>'fk_mainmenu=parcautomobile,fk_leftmenu=config_',
				'type'=>'left',
				'titre'=>'etiquettes_parc',
				'url'=>'/parcautomobile/etiquettes_parc/index.php',
				'langs'=>'parcautomobile@parcautomobile',
				'position'=>10,
				'enabled'=>'1',
				'perms'=>'$user->rights->parcautomobile->lire',
				'target'=>'',
				'user'=>2);
			$r++;


		$r=1;
		
	}




	function init($options='')
	{
		global $conf;
		$sqlm = array();

		if (!dolibarr_get_const($this->db,'PARCAUTOMOBILE_CHECKINTERVENTIONSFORMAIL',0))
			dolibarr_set_const($this->db,'PARCAUTOMOBILE_CHECKINTERVENTIONSFORMAIL',0,'chaine',0,'',0);
		if (!dolibarr_get_const($this->db,'PARCAUTOMOBILE_NUMBEROFDAYSBEFORETOSENDMAIL',0))
			dolibarr_set_const($this->db,'PARCAUTOMOBILE_NUMBEROFDAYSBEFORETOSENDMAIL',0,'chaine',0,'',0);


		$this->cronjobs[0]['entity']  	= 0;
        $this->cronjobs[0]['label']  	= 'parcautomobilecheckInterventionsMails';
        $this->cronjobs[0]['jobtype']  	= 'method';
        $this->cronjobs[0]['class']  	= 'parcautomobile/class/interventions_parc.class.php';
        $this->cronjobs[0]['objectname']  = 'interventions_parc';
        $this->cronjobs[0]['method'] 	= 'checkInterventionsMails';
        $this->cronjobs[0]['frequency'] = 1;
        $this->cronjobs[0]['unitfrequency'] = 86400;
        $this->cronjobs[0]['priority'] 	= 10;
        $this->cronjobs[0]['datestart'] = date('Y-m-d H:i:s');
        $this->cronjobs[0]['status'] 	= 1;
        $this->cronjobs[0]['test'] 		= '$conf->global->PARCAUTOMOBILE_INTERVENTION_SEND_EMAIL';
        $this->cronjobs[0]['note'] 		= 'parcautomobilecheckInterventionsMails';


        $this->insert_cronjobs();



        $sql2 = "UPDATE " . MAIN_DB_PREFIX. "cronjob SET status = 1 WHERE module_name = 'parcautomobile' AND classesname = 'parcautomobile/class/interventions_parc.class.php' AND objectname = 'interventions_parc' AND methodename = 'checkInterventionsMails'";
        $resql = $this->db->query($sql2);













		// global $dolibarr_main_data_root;
		// if (!dolibarr_get_const($this->db,'PARCAUTOMOBILE_CHANGEPATHDOCS',0)){
		// 	$docdir = $dolibarr_main_data_root.'/parcautomobile';
		// 	$dmkdir = dol_mkdir($docdir, '', 0755);
		// 	if($dmkdir >= 0){
		// 		$source = dol_buildpath('/uploads/parcautomobile');
		// 		@chmod($docdir, 0775);
		// 		$dcopy = dolCopyDir($source, $docdir, 0775, 1);
		// 		if($dcopy >= 0){
		// 			parcautomobilepermissionto($docdir);
		// 			dolibarr_set_const($this->db,'PARCAUTOMOBILE_CHANGEPATHDOCS',1,'chaine',0,'',0);
		// 		}
		// 	}
		// }



		$sql = "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."parc` (
		  	`rowid` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		  	`label` varchar(255) NULL,
		  	`adress` varchar(355) NULL
		);";
		$resql = $this->db->query($sql);

		$sql = "ALTER table  `".MAIN_DB_PREFIX."parc` MODIFY label varchar(255) NULL";
		$resql = $this->db->query($sql);

		$sql = "ALTER table  `".MAIN_DB_PREFIX."parc` MODIFY adress varchar(355) NULL";
		$resql = $this->db->query($sql);

		$sql = "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."vehiculeparc` (
		  	`rowid` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		  	`plaque` varchar(255) NULL,
		  	`logo` varchar(255) NULL,
		  	`model`  int(11) NULL,
		  	`conducteur` int(11) NULL,
		  	`lieu` varchar(255) NULL,
		  	`date_immatriculation` date NULL,
		  	`date_contrat` date NULL,
		  	`num_chassi` int(11) NULL,
		  	`statut` varchar(255) NULL,
		  	`nb_porte` int(11) NULL,
		  	`nb_place` int(11) NULL,
		  	`kilometrage` DECIMAL NULL,
		  	`unite` varchar(255) NULL,
		  	`color` varchar(255) NULL,
		  	`value_catalogue` DECIMAL NULL,
		  	`value_residuelle` DECIMAL NULL,
		  	`anne_model` varchar(255) NULL,
		  	`transmission` varchar(255) NULL,
		  	`type_carburant` varchar(255) NULL,
		  	`emission_co2` float NULL,
		  	`nb_chevaux` DECIMAL NULL ,
		  	`tax` float NULL,
		  	`puissance` DECIMAL NULL,
		  	`etiquettes` varchar(255) NULL,
		  	`parc` int(11) NULL
		);";
		$resql = $this->db->query($sql);

		$sql = "ALTER table  `".MAIN_DB_PREFIX."vehiculeparc` ADD `etiquettes` varchar(255) NULL";
		$resql = $this->db->query($sql);

		$sql = "ALTER table `".MAIN_DB_PREFIX."vehiculeparc` add sendmail boolean NULL DEFAULT 0";
		$resql = $this->db->query($sql);
		

		$sql = "ALTER table  `".MAIN_DB_PREFIX."vehiculeparc` MODIFY plaque varchar(255) NULL,
			MODIFY logo varchar(255) NULL,
			MODIFY model  int(11) NULL, 
			MODIFY conducteur int(11) NULL, 
			MODIFY `lieu` varchar(255) NULL ,
			MODIFY `date_immatriculation` date NULL,
			MODIFY `date_contrat` date NULL,
			MODIFY `num_chassi` int(11) NULL,
			MODIFY `statut` varchar(255) NULL,
			MODIFY `nb_porte` int(11) NULL,
			MODIFY `nb_place` int(11) NULL,
			MODIFY `kilometrage` DECIMAL NULL,
			MODIFY `unite` varchar(255) NULL,
			MODIFY `color` varchar(255) NULL,
			MODIFY `value_catalogue` DECIMAL NULL,
			MODIFY `value_residuelle` DECIMAL NULL,
			MODIFY `anne_model` varchar(255) NULL,
			MODIFY `transmission` varchar(255) NULL,
			MODIFY `emission_co2` float NULL,
			MODIFY `nb_chevaux` DECIMAL NULL,
			MODIFY `tax` float NULL,
			MODIFY `puissance` DECIMAL NULL,
			MODIFY `etiquettes` varchar(255)` NULL,
			MODIFY `parc` int(11) NULL";
		$resql = $this->db->query($sql);


		$sql = "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."kilometrage` (
		  	`rowid` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		  	`vehicule` int(11) NULL,
		  	`kilometrage` DECIMAL NULL,
		  	`unite` varchar(255) NULL,
		  	`date` date NULL
		);";
		
		$resql = $this->db->query($sql);

		$sql = "ALTER table  `".MAIN_DB_PREFIX."kilometrage` ADD `unite` varchar(255) NULL";
		$resql = $this->db->query($sql);

		$sql = "ALTER table  `".MAIN_DB_PREFIX."kilometrage` MODIFY `date` date NULL";
		$resql = $this->db->query($sql);

		$sql = "ALTER table  `".MAIN_DB_PREFIX."kilometrage` 
			MODIFY `vehicule` int(11) NULL,
			MODIFY `kilometrage` DECIMAL NULL,
			MODIFY `unite` varchar(255) NULL,
			MODIFY `date` date NULL";
		$resql = $this->db->query($sql);



		$sql = "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."interventions_parc` (
				  	`rowid` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				  	`typeintervention` int(11) NULL,
				  	`vehicule` int(11) NULL,
				  	`acheteur` int(11) NULL,
				  	`kilometrage` DECIMAL NULL,
				  	`fournisseur` int(11) NULL,
				  	`ref_facture`  varchar(255) NULL,
				  	`prix` DECIMAL NULL,
				  	`date` date NULL,
				  	`service_inclus` text NULL,
				  	`notes` text NULL
				);";
		$resql = $this->db->query($sql);


		$sql = "ALTER table `".MAIN_DB_PREFIX."interventions_parc` add service_inclus text NULL";
		$resql = $this->db->query($sql);
		
	

		$sql = "ALTER table  `".MAIN_DB_PREFIX."interventions_parc` 
				MODIFY `typeintervention` int(11) NULL,
				MODIFY `vehicule` int(11) NULL,
				MODIFY `acheteur` int(11) NULL,
				MODIFY `kilometrage` DECIMAL NULL,
				MODIFY `fournisseur` int(11) NULL,
				MODIFY `ref_facture` varchar(255) NULL,
				MODIFY `prix` DECIMAL NULL,
				MODIFY `date` date NULL,
				MODIFY `service_inclus` text NULL,
				MODIFY `notes` text NULL";
		$resql = $this->db->query($sql);


		$sql = "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."costsvehicule` (
				  	`rowid` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				  	`type` varchar(255) NULL,
				  	`vehicule` int(11) NULL,
				  	`id_contrat` int(11) NULL,
				  	`id_intervention` int(11) NULL,
				  	`id_suiviessence` int(11) NULL,
				  	`prix` DECIMAL NULL,
				  	`date` date NULL,
				  	`notes` text NULL
				);";
		$resql = $this->db->query($sql);

		$sql = "ALTER table  `".MAIN_DB_PREFIX."costsvehicule` ADD `id_suiviessence` int(11) NULL";
		$resql = $this->db->query($sql);

		$sql = "ALTER table  `".MAIN_DB_PREFIX."costsvehicule`
				MODIFY `type` varchar(255) NULL,
				MODIFY `vehicule` int(11) NULL,
				MODIFY `id_contrat` int(11) NULL,
				MODIFY `id_intervention` int(11) NULL,
				MODIFY `id_suiviessence` int(11) NULL,
				MODIFY `prix` DECIMAL NULL,
				MODIFY `date` date NULL,
				MODIFY `notes` text NULL";
		$resql = $this->db->query($sql);





		$sql = "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."typeintervention` (
				  	`rowid` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				  	`label` varchar(255) NULL
				);";
		$resql = $this->db->query($sql);


		$sql = "ALTER table  `".MAIN_DB_PREFIX."typeintervention` MODIFY `label` varchar(255) NULL";
		$resql = $this->db->query($sql);


		$sql = "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."suivi_essence` (
				  	`rowid` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				  	`vehicule` int(11) NULL,
				  	`litre` float NULL,
				  	`prix` float NULL,
				  	`date` date NULL,
				  	`acheteur` int(11) NULL,
				  	`fournisseur` int(11) NULL,
				  	`ref_facture` varchar(255) NULL,
				  	`kilometrage` DECIMAL NULL,
				  	`remarques` text NULL
				);";
		$resql = $this->db->query($sql);


		$sql = "ALTER table  `".MAIN_DB_PREFIX."suivi_essence` 
				MODIFY `vehicule` int(11) NULL,
				MODIFY `litre` float NULL,
				MODIFY `prix` float NULL,
				MODIFY `date` date NULL,
				MODIFY `acheteur` int(11) NULL,
				MODIFY `fournisseur` int(11) NULL,
				MODIFY `ref_facture` varchar(255) NULL,
				MODIFY `kilometrage` DECIMAL NULL,
				MODIFY `remarques` text NULL";
		$resql = $this->db->query($sql);


		$sql = "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."contrat_parc` (
				  	`rowid` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				  	`vehicule` int(11) NULL,
				  	`kilometrage` DECIMAL NULL,
				  	`typecontrat` int(11) NULL,
				  	`activation_couts` float NULL,
				  	`type_montant`  varchar(255) NULL,
				  	`montant_recurrent` float NULL,
				  	`date_facture` date NULL,
				  	`date_debut` date NULL,
				  	`date_fin` date NULL,
				  	`responsable` int(11) NULL,
				  	`fournisseur` int(11) NULL,
				  	`conducteur` int(11) NULL,
				  	`ref_contrat` varchar(255) NULL,
				  	`etat` varchar(255) NULL,
				  	`condition` text NULL,
				  	`services_inclus` text NULL,
				  	`couts_recurrent` varchar(255) NULL
				);";
		$resql = $this->db->query($sql);

		$sql = "ALTER table  `".MAIN_DB_PREFIX."contrat_parc` ADD `services_inclus` text NULL";
		$resql = $this->db->query($sql);
		$sql = "ALTER table  `".MAIN_DB_PREFIX."contrat_parc` ADD `couts_recurrent` varchar(255) NULL";
		$resql = $this->db->query($sql);

		$sql = "ALTER table  `".MAIN_DB_PREFIX."contrat_parc` 
				MODIFY `vehicule` int(11) NULL,
				MODIFY `kilometrage` DECIMAL NULL,
				MODIFY `typecontrat` int(11) NULL,
				MODIFY `activation_couts` float NULL,
				MODIFY `type_montant` varchar(255) NULL,
				MODIFY `montant_recurrent` float NULL,
				MODIFY `date_facture` date NULL,
				MODIFY `date_debut` date NULL,
				MODIFY `date_fin` date NULL,
				MODIFY `responsable` int(11) NULL,
				MODIFY `fournisseur` int(11) NULL,
				MODIFY `conducteur` int(11) NULL,
				MODIFY `ref_contrat` varchar(255) NULL,
				MODIFY `etat` varchar(255) NULL,
				MODIFY `condition` text NULL,
				MODIFY `services_inclus` text NULL,
				MODIFY `couts_recurrent` varchar(255) NULL";
		$resql = $this->db->query($sql);



		$sql = "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."typecontrat` (
				  	`rowid` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				  	`label` varchar(255) NULL
				);";
		$resql = $this->db->query($sql);


		$sql = "ALTER table  `".MAIN_DB_PREFIX."typecontrat` MODIFY `label` varchar(255) NULL";
		$resql = $this->db->query($sql);


		$sql = "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."statut` (
				  	`rowid` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				  	`label` varchar(255) NULL,
				  	`color` varchar(255) NULL
				);";
		$resql = $this->db->query($sql);

		$sql = "ALTER table  `".MAIN_DB_PREFIX."statut` ADD `color` varchar(255) NULL";
		$resql = $this->db->query($sql);

		$sql = "INSERT INTO `".MAIN_DB_PREFIX."statut` (`rowid`, `label`, `color`) VALUES
		(1, 'annuler', '#DBE270'),
		(2, 'active', '#F59A9A'),
		(3, 'inshop', '#62B0F7'),
		(4, 'inactive', '#FFB164'),
		(5, 'sold', '#59D859');";
		$resql = $this->db->query($sql);

		$sql = "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."etiquettes_parc` (
				  	`rowid` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				  	`label` varchar(255) NULL,
				  	`color` varchar(255) NULL
				);";
		$resql = $this->db->query($sql);

		$sql = "INSERT INTO `".MAIN_DB_PREFIX."etiquettes_parc` (`rowid`, `label`, `color`) VALUES
		(1, 'Automobile', '#e62828'),
		(2, '4x4', '#00d5d5'),
		(3, 'Toyota', '#cc0066'),
		(4, 'Pickup', '#caca39'),
		(5, 'Profilé', '#000000'),
		(6, 'Camion', '#004080'),
		(7, 'Remorque', '#ff8000');";
		
		$resql = $this->db->query($sql);

		$sql = "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."marques` (
				  	`rowid` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				  	`label` varchar(255) NULL,
				  	`logo` varchar(255) NULL
				);";
		$resql = $this->db->query($sql);
		
		$sql = "ALTER table  `".MAIN_DB_PREFIX."marques` 
				MODIFY `label` varchar(255) NULL,
				MODIFY `logo` varchar(255) NULL";
		$resql = $this->db->query($sql);



		$sql = "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."modeles` (
				  	`rowid` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				  	`label` varchar(255) NULL,
				  	`marque` int(11) NULL
				);";
		$resql = $this->db->query($sql);


		$sql = "ALTER table  `".MAIN_DB_PREFIX."modeles` 
				MODIFY `label` varchar(255) NULL,
				MODIFY `marque` int(11) NULL";
		$resql = $this->db->query($sql);


		$sql = "ALTER table  `".MAIN_DB_PREFIX."interventions_parc` ADD `datevalidate` date NULL";
		$resql = $this->db->query($sql);
		
		$sql = "ALTER table  `".MAIN_DB_PREFIX."interventions_parc` ADD `checkmail` date NULL";
		$resql = $this->db->query($sql);
		

		$sql = "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."kilometrage_extrafields` (
	  		`rowid` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		    `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		    `fk_object` int(11) NOT NULL,
		    `import_key` varchar(14) DEFAULT NULL
		);";
		$resql = $this->db->query($sql);

		$sql = "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."suivi_essence_extrafields` (
	  		`rowid` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		    `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		    `fk_object` int(11) NOT NULL,
		    `import_key` varchar(14) DEFAULT NULL
		);";
		$resql = $this->db->query($sql);

		$sql = "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."contrat_parc_extrafields` (
	  		`rowid` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		    `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		    `fk_object` int(11) NOT NULL,
		    `import_key` varchar(14) DEFAULT NULL
		);";
		$resql = $this->db->query($sql);

		$sql = "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."interventions_parc_extrafields` (
	  		`rowid` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		    `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		    `fk_object` int(11) NOT NULL,
		    `import_key` varchar(14) DEFAULT NULL
		);";
		$resql = $this->db->query($sql);

		$sql = "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."costsvehicule_extrafields` (
	  		`rowid` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		    `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		    `fk_object` int(11) NOT NULL,
		    `import_key` varchar(14) DEFAULT NULL
		);";
		$resql = $this->db->query($sql);

		$sql = "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."vehiculeparc_extrafields` (
	  		`rowid` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		    `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		    `fk_object` int(11) NOT NULL,
		    `import_key` varchar(14) DEFAULT NULL
		);";
		$resql = $this->db->query($sql);

		return $this->_init($sqlm, $options);	
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

		$sql2 = "UPDATE " . MAIN_DB_PREFIX. "cronjob SET status = 0 WHERE module_name = 'parcautomobile' AND classesname = 'parcautomobile/class/interventions_parc.class.php' AND objectname = 'interventions_parc' AND methodename = 'checkInterventionsMails'";
		$resql = $this->db->query($sql2);

		return $this->_remove($sql, $options);
	}

}


// function parcautomobilepermissionto($source){
//     if(is_dir($source)) {
//     	@chmod($source, 0775);
//         $dir_handle=opendir($source);
//         while($file=readdir($dir_handle)){
//             if($file!="." && $file!=".."){
//                 if(is_dir($source."/".$file)){
//                     @chmod($source."/".$file, 0775);
//                     parcautomobilepermissionto($source."/".$file);
//                 } else {
//                     @chmod($source."/".$file, 0664);
//                 }
//             }
//         }
//         closedir($dir_handle);
//     } else {
//         @chmod($source, 0664);
//     }
// }