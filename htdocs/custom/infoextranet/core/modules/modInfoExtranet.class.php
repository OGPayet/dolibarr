<?php
/* Copyright (C) 2004-2018 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2018 SuperAdmin
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
 * 	\defgroup   infoextranet     Module InfoExtranet
 *  \brief      InfoExtranet module descriptor.
 *
 *  \file       htdocs/infoextranet/core/modules/modInfoExtranet.class.php
 *  \ingroup    infoextranet
 *  \brief      Description and activation file for module InfoExtranet
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';
include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';


// The class name should start with a lower case mod for Dolibarr to pick it up
// so we ignore the Squiz.Classes.ValidClassName.NotCamelCaps rule.
// @codingStandardsIgnoreStart
/**
 *  Description and activation class for module InfoExtranet
 */
class modInfoExtranet extends DolibarrModules
{
	// @codingStandardsIgnoreEnd
	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
        global $langs,$conf;

        $langs->load("infoextranet@infoextranet");

        $this->db = $db;

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 448020;
		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'infoextranet';

		// Family can be 'crm','financial','hr','projects','products','ecm','technic','interface','other'
		// It is used to group modules by family in module setup page
		$this->family = "other";
		// Module position in the family on 2 digits ('01', '10', '20', ...)
		$this->module_position = '90';
		// Gives the possibility to the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
		$this->familyinfo = array('code42' => array('position' => '90', 'label' => $langs->trans("code42")));

		// Module label (no space allowed), used if translation string 'ModuleInfoExtranetName' not found (MyModue is name of module).
		$this->name = preg_replace('/^mod/i','',get_class($this));
		// Module description, used if translation string 'ModuleInfoExtranetDesc' not found (MyModue is name of module).
		$this->description = "InfoExtranetDescription";
		// Used only if file README.md and README-LL.md not found.
		$this->descriptionlong = "InfoExtranetDescription (Long)";

		$this->editor_name = 'Code 42';
		$this->editor_url = 'https://www.code42.fr';

		// Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'
		$this->version = '1.3.1';
		// Key used in llx_const table to save module status enabled/disabled (where INFOEXTRANET is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		$this->picto='infoextranet@infoextranet';

		// Defined all module parts (triggers, login, substitutions, menus, css, etc...)
		// for default path (eg: /infoextranet/core/xxxxx) (0=disable, 1=enable)
		// for specific path of parts (eg: /infoextranet/core/modules/barcode)
		// for specific css file (eg: /infoextranet/css/infoextranet.css.php)
		$this->module_parts = array(
						'triggers' => 1,                                 	// Set this to 1 if module has its own trigger directory (core/triggers)
									'login' => 0,                                    	// Set this to 1 if module has its own login method file (core/login)
									'substitutions' => 1,                            	// Set this to 1 if module has its own substitution function file (core/substitutions)
									'menus' => 0,                                    	// Set this to 1 if module has its own menus handler directory (core/menus)
									'theme' => 0,                                    	// Set this to 1 if module has its own theme directory (theme)
						'tpl' => 0,                                      	// Set this to 1 if module overwrite template dir (core/tpl)
									'barcode' => 0,                                  	// Set this to 1 if module has its own barcode directory (core/modules/barcode)
									'models' => 0,                                   	// Set this to 1 if module has its own models directory (core/modules/xxx)
									'css' => array('/infoextranet/css/infoextranet.css.php'),	// Set this to relative path of css file if module has its own css file
									'js' => array('/infoextranet/js/infoextranet.js.php'),          // Set this to relative path of js file if module must load a js on all pages
									'hooks' => array('data'=>array('thirdpartydao','hookcontext2'), 'entity'=>'0') 	// Set here all hooks context managed by module. To find available hook context, make a "grep -r '>initHooks(' *" on source code. You can also set hook context 'all'
		                        );

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/infoextranet/temp","/infoextranet/subdir");
		$this->dirs = array();

		// Config pages. Put here list of php page, stored into infoextranet/admin directory, to use to setup module.
		$this->config_page_url = array("setup.php@infoextranet");

		// Dependencies
		$this->hidden = false;			// A condition to hide module
		$this->depends = array("modAgenda");		// List of module class names as string that must be enabled if this module is enabled
		$this->requiredby = array();	// List of module ids to disable if this one is disabled
		$this->conflictwith = array();	// List of module class names as string this module is in conflict with
		$this->langfiles = array("infoextranet@infoextranet");
		$this->phpmin = array(5,3);					// Minimum version of PHP required by module
		$this->need_dolibarr_version = array(7,0);	// Minimum version of Dolibarr required by module
		$this->warnings_activation = array();                     // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		$this->warnings_activation_ext = array();                 // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		//$this->automatic_activation = array('FR'=>'InfoExtranetWasAutomaticallyActivatedBecauseOfYourCountryChoice');
		//$this->always_enabled = true;								// If true, can't be disabled

		// Constants
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example: $this->const=array(0=>array('INFOEXTRANET_MYNEWCONST1','chaine','myvalue','This is a constant to add',1),
		//                             1=>array('INFOEXTRANET_MYNEWCONST2','chaine','myvalue','This is another constant to add',0, 'current', 1)
		// );
		$this->const = array();


		if (! isset($conf->infoextranet) || ! isset($conf->infoextranet->enabled))
		{
			$conf->infoextranet=new stdClass();
			$conf->infoextranet->enabled=0;
		}


		// Array to add new pages in new tabs
        $this->tabs = array();
		// Etat de parc
        $this->tabs[] = array('data'=>'thirdparty:+infoExtranet:InfoExtranetTabname:infoextranet@infoextranet:$user->rights->infoextranet->read:/infoextranet/index.php?socid=__ID__');
        //Devices
        $this->tabs[] = array('data'=>'thirdparty:+infoExtranetDevice:InfoExtranetTabnameDevice:infoextranet@infoextranet:$user->rights->infoextranet->read:/infoextranet/device.php?socid=__ID__');
        // Applications
        $this->tabs[] = array('data'=>'thirdparty:+infoExtranetApp:InfoExtranetTabnameApp:infoextranet@infoextranet:$user->rights->infoextranet->read:/infoextranet/application.php?socid=__ID__');

        // Example:
		// $this->tabs[] = array('data'=>'objecttype:+tabname1:Title1:mylangfile@infoextranet:$user->rights->infoextranet->read:/infoextranet/mynewtab1.php?id=__ID__');  					// To add a new tab identified by code tabname1
        // $this->tabs[] = array('data'=>'objecttype:+tabname2:SUBSTITUTION_Title2:mylangfile@infoextranet:$user->rights->othermodule->read:/infoextranet/mynewtab2.php?id=__ID__',  	// To add another new tab identified by code tabname2. Label will be result of calling all substitution functions on 'Title2' key.
        // $this->tabs[] = array('data'=>'objecttype:-tabname:NU:conditiontoremove');                                                     										// To remove an existing tab identified by code tabname
        //
        // Where objecttype can be
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


        // Dictionaries
        $this->dictionaries=array(
            'langs'=>'infoextranet@infoextranet',
            'tabname'=>array(
                MAIN_DB_PREFIX."infoextranet_accesspoint",
                MAIN_DB_PREFIX."infoextranet_dedicatedvlan",
                MAIN_DB_PREFIX."infoextranet_destbackup",
                MAIN_DB_PREFIX."infoextranet_dnshost",
                MAIN_DB_PREFIX."infoextranet_firewall",
                MAIN_DB_PREFIX."infoextranet_mailhost",
                MAIN_DB_PREFIX."infoextranet_softbackup",
                MAIN_DB_PREFIX."infoextranet_switch",
                MAIN_DB_PREFIX."infoextranet_voipoperator",
                MAIN_DB_PREFIX."infoextranet_volumebackup",
                MAIN_DB_PREFIX."infoextranet_wanoperator",
                MAIN_DB_PREFIX."infoextranet_roletype",
                MAIN_DB_PREFIX."infoextranet_environment",
                MAIN_DB_PREFIX."infoextranet_hebergeursi",
                MAIN_DB_PREFIX."infoextranet_webhost",
                MAIN_DB_PREFIX."infoextranet_addresstypes",
                MAIN_DB_PREFIX."infoextranet_devicelabels",
                MAIN_DB_PREFIX."infoextranet_devicemodels",
                MAIN_DB_PREFIX."infoextranet_devicetypes",
                MAIN_DB_PREFIX."infoextranet_osfirmwaretypes"
            ),
            'tablib'=>array(
                "ExtranetAccessPoint",
                "ExtranetDedicatedVlan",
                "ExtranetDestBackup",
                "ExtranetDnsHost",
                "ExtranetFirewall",
                "ExtranetMailHost",
                "ExtranetSoftBackup",
                "ExtranetSwitch",
                "ExtranetVoipOperator",
                "ExtranetVolumeBackupExt",
                "ExtranetWanOperator",
                "ExtranetRoleType",
                "ExtranetEnvironment",
                "ExtranetHebergeurSi",
                "ExtranetWebHost",
                "ExtranetAddressTypes",
                "ExtranetDeviceModels",
                "ExtranetDeviceLabels",
                "ExtranetDeviceTypes",
                "ExtranetOsFirmwareTypes"
            ),
            'tabsql'=>array(
                'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'infoextranet_accesspoint as f',
                'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'infoextranet_dedicatedvlan as f',
                'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'infoextranet_destbackup as f',
                'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'infoextranet_dnshost as f',
                'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'infoextranet_firewall as f',
                'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'infoextranet_mailhost as f',
                'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'infoextranet_softbackup as f',
                'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'infoextranet_switch as f',
                'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'infoextranet_voipoperator as f',
                'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'infoextranet_volumebackup as f',
                'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'infoextranet_wanoperator as f',
                'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'infoextranet_roletype as f',
                'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'infoextranet_environment as f',
                'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'infoextranet_hebergeursi as f',
                'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'infoextranet_webhost as f',
                'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'infoextranet_addresstypes as f',
                'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'infoextranet_devicelabels as f',
                'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'infoextranet_devicemodels as f',
                'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'infoextranet_devicetypes as f',
                'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'infoextranet_osfirmwaretypes as f'

            ),
            'tabsqlsort'=>array(
                "label ASC",
                "label ASC",
                "label ASC",
                "label ASC",
                "label ASC",
                "label ASC",
                "label ASC",
                "label ASC",
                "label ASC",
                "label ASC",
                "label ASC",
                "label ASC",
                "label ASC",
                "label ASC",
                "label ASC",
                "label ASC",
                "label ASC",
                "label ASC",
                "label ASC",
                "label ASC"
            ),
            'tabfield'=>array(
                "code,label",
                "code,label",
                "code,label",
                "code,label",
                "code,label",
                "code,label",
                "code,label",
                "code,label",
                "code,label",
                "code,label",
                "code,label",
                "code,label",
                "code,label",
                "code,label",
                "code,label",
                "code,label",
                "code,label",
                "code,label",
                "code,label",
                "code,label"
            ),
            'tabfieldvalue'=>array(
                "code,label",
                "code,label",
                "code,label",
                "code,label",
                "code,label",
                "code,label",
                "code,label",
                "code,label",
                "code,label",
                "code,label",
                "code,label",
                "code,label",
                "code,label",
                "code,label",
                "code,label",
                "code,label",
                "code,label",
                "code,label",
                "code,label",
                "code,label"
            ),
            'tabfieldinsert'=>array(
                "code,label",
                "code,label",
                "code,label",
                "code,label",
                "code,label",
                "code,label",
                "code,label",
                "code,label",
                "code,label",
                "code,label",
                "code,label",
                "code,label",
                "code,label",
                "code,label",
                "code,label",
                "code,label",
                "code,label",
                "code,label",
                "code,label",
                "code,label"
            ),
            'tabrowid'=>array(
                "rowid",
                "rowid",
                "rowid",
                "rowid",
                "rowid",
                "rowid",
                "rowid",
                "rowid",
                "rowid",
                "rowid",
                "rowid",
                "rowid",
                "rowid",
                "rowid",
                "rowid",
                "rowid",
                "rowid",
                "rowid",
                "rowid",
                "rowid"
            ),
            'tabcond'=>array(
                $conf->infoextranet->enabled,
                $conf->infoextranet->enabled,
                $conf->infoextranet->enabled,
                $conf->infoextranet->enabled,
                $conf->infoextranet->enabled,
                $conf->infoextranet->enabled,
                $conf->infoextranet->enabled,
                $conf->infoextranet->enabled,
                $conf->infoextranet->enabled,
                $conf->infoextranet->enabled,
                $conf->infoextranet->enabled,
                $conf->infoextranet->enabled,
                $conf->infoextranet->enabled,
                $conf->infoextranet->enabled,
                $conf->infoextranet->enabled,
                $conf->infoextranet->enabled,
                $conf->infoextranet->enabled,
                $conf->infoextranet->enabled,
                $conf->infoextranet->enabled,
                $conf->infoextranet->enabled
            )
        );
        /* Example:
        $this->dictionaries=array(
            'langs'=>'mylangfile@infoextranet',
            'tabname'=>array(MAIN_DB_PREFIX."table1",MAIN_DB_PREFIX."table2",MAIN_DB_PREFIX."table3"),		// List of tables we want to see into dictonnary editor
            'tablib'=>array("Table1","Table2","Table3"),													// Label of tables
            'tabsql'=>array('SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table1 as f','SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table2 as f','SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table3 as f'),	// Request to select fields
            'tabsqlsort'=>array("label ASC","label ASC","label ASC"),																					// Sort order
            'tabfield'=>array("code,label","code,label","code,label"),																					// List of fields (result of select to show dictionary)
            'tabfieldvalue'=>array("code,label","code,label","code,label"),																				// List of fields (list of fields to edit a record)
            'tabfieldinsert'=>array("code,label","code,label","code,label"),																			// List of fields (list of fields for insert)
            'tabrowid'=>array("rowid","rowid","rowid"),																									// Name of columns with primary key (try to always name it 'rowid')
            'tabcond'=>array($conf->infoextranet->enabled,$conf->infoextranet->enabled,$conf->infoextranet->enabled)												// Condition to show each dictionary
        );
        */


        // Boxes/Widgets
		// Add here list of php file(s) stored in infoextranet/core/boxes that contains class to show a widget.
        $this->boxes = array();

        $this->boxes[0]['file']='infoextranetwidget1.php@infoextranet';
        $this->boxes[0]['note']='';
        $this->boxes[0]['picto']='infoextranet_32@infoextranet';

        $this->boxes[1]['file']='infoextranetwidget2.php@infoextranet';
        $this->boxes[1]['note']='';


		// Cronjobs (List of cron jobs entries to add when module is enabled)
		// unit_frequency must be 60 for minute, 3600 for hour, 86400 for day, 604800 for week
		$this->cronjobs = array();
		// Example: $this->cronjobs=array(0=>array('label'=>'My label', 'jobtype'=>'method', 'class'=>'/dir/class/file.class.php', 'objectname'=>'MyClass', 'method'=>'myMethod', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>2, 'unitfrequency'=>3600, 'status'=>0, 'test'=>true),
		//                                1=>array('label'=>'My label', 'jobtype'=>'command', 'command'=>'', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>1, 'unitfrequency'=>3600*24, 'status'=>0, 'test'=>true)
		// );


		// Permissions
		$this->rights = array();		// Permission array used by this module

		$r=0;
		$this->rights[$r][0] = $this->numero + $r;	// Permission id (must not be already used)
		$this->rights[$r][1] = 'Read extrafields info with InfoExtranet';	// Permission label
		$this->rights[$r][3] = 1; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'read';				// In php code, permission will be checked by test if ($user->rights->infoextranet->level1->level2)
		$this->rights[$r][5] = '';				    // In php code, permission will be checked by test if ($user->rights->infoextranet->level1->level2)

		$r++;
		$this->rights[$r][0] = $this->numero + $r;	// Permission id (must not be already used)
		$this->rights[$r][1] = 'Create/Update extrafields info with InfoExtranet';	// Permission label
		$this->rights[$r][3] = 1; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'write';				// In php code, permission will be checked by test if ($user->rights->infoextranet->level1->level2)
		$this->rights[$r][5] = '';				    // In php code, permission will be checked by test if ($user->rights->infoextranet->level1->level2)

		$r++;
		$this->rights[$r][0] = $this->numero + $r;	// Permission id (must not be already used)
		$this->rights[$r][1] = 'Delete extrafields info with InfoExtranet';	// Permission label
		$this->rights[$r][3] = 1; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'delete';				// In php code, permission will be checked by test if ($user->rights->infoextranet->level1->level2)
		$this->rights[$r][5] = '';				    // In php code, permission will be checked by test if ($user->rights->infoextranet->level1->level2)

        $r++;
        $this->rights[$r][0] = $this->numero + $r;	// Permission id (must not be already used)
        $this->rights[$r][1] = 'Configure the module';	// Permission label
        $this->rights[$r][3] = 1; 					// Permission by default for new user (0/1)
        $this->rights[$r][4] = 'configure';				// In php code, permission will be checked by test if ($user->rights->infoextranet->level1->level2)
        $this->rights[$r][5] = '';				    // In php code, permission will be checked by test if ($user->rights->infoextranet->level1->level2)

        $r++;
        $this->rights[$r][0] = $this->numero + $r;	// Permission id (must not be already used)
        $this->rights[$r][1] = 'Clone extrafields info with InfoExtranet';	// Permission label
        $this->rights[$r][3] = 1; 					// Permission by default for new user (0/1)
        $this->rights[$r][4] = 'clone';				// In php code, permission will be checked by test if ($user->rights->infoextranet->level1->level2)
        $this->rights[$r][5] = '';				    // In php code, permission will be checked by test if ($user->rights->infoextranet->level1->level2)

        $r++;
        $this->rights[$r][0] = $this->numero + $r;	// Permission id (must not be already used)
        $this->rights[$r][1] = 'Use dictionary';	// Permission label
        $this->rights[$r][3] = 1; 					// Permission by default for new user (0/1)
        $this->rights[$r][4] = 'dict';				// In php code, permission will be checked by test if ($user->rights->infoextranet->level1->level2)
        $this->rights[$r][5] = '';				    // In php code, permission will be checked by test if ($user->rights->infoextranet->level1->level2)

		// Main menu entries
		$this->menu = array();			// List of menus to add
		$r=0;

		// Add here entries to declare new menus

		/* BEGIN MODULEBUILDER TOPMENU */
		$this->menu[$r++]=array('fk_menu'=>'',			                // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
								'type'=>'top',			                // This is a Top menu entry
								'titre'=>'InfoExtranet',
								'mainmenu'=>'infoextranet',
								'leftmenu'=>'',
								'url'=>'/infoextranet/list.php',
								'langs'=>'infoextranet@infoextranet',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
								'position'=>1000+$r,
								'enabled'=>'$conf->infoextranet->enabled',	// Define condition to show or hide menu entry. Use '$conf->infoextranet->enabled' if entry must be visible if module is enabled.
								'perms'=>'1',			                // Use 'perms'=>'$user->rights->infoextranet->level1->level2' if you want your menu with a permission rules
								'target'=>'',
								'user'=>2);				                // 0=Menu for internal users, 1=external users, 2=both

		/* END MODULEBUILDER TOPMENU */

		/* BEGIN MODULEBUILDER LEFTMENU MYOBJECT */
        $this->menu[$r++]=array('fk_menu'=>'fk_mainmenu=infoextranet',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
                                'type'=>'left',			                // This is a Left menu entry
                                'titre'=>'Info Extranet - v.'.$this->version,
                                'mainmenu'=>'infoextranet',
                                'leftmenu'=>'infoextranet_list',
                                'url'=>'/infoextranet/list.php',
                                'langs'=>'infoextranet@infoextranet',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
                                'position'=>1000+$r,
                                'enabled'=>'$conf->infoextranet->enabled',  // Define condition to show or hide menu entry. Use '$conf->infoextranet->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
                                'perms'=>'1',			                // Use 'perms'=>'$user->rights->infoextranet->level1->level2' if you want your menu with a permission rules
                                'target'=>'',
                                'user'=>2);				                // 0=Menu for internal users, 1=external users, 2=both

        $this->menu[$r++]=array('fk_menu'=>'fk_mainmenu=infoextranet,fk_leftmenu=infoextranet_list',
                                'type'=>'left',
                                'titre'=>'Liste - '.$langs->trans('TitleM'),
                                'mainmenu'=>'infoextranet',
                                'leftmenu'=>'infoextranet_listm',
                                'url'=>'/infoextranet/list.php',
                                'langs'=>'infoextranet@infoextranet',
                                'position'=>1000+$r,
                                'enabled'=>'$conf->infoextranet->enabled',
                                'perms'=>'1',
                                'target'=>'',
                                'user'=>2);

        $this->menu[$r++]=array('fk_menu'=>'fk_mainmenu=infoextranet,fk_leftmenu=infoextranet_list',
                                'type'=>'left',
                                'titre'=>'Liste - '.$langs->trans('TitleP'),
                                'mainmenu'=>'infoextranet',
                                'leftmenu'=>'infoextranet_listp',
                                'url'=>'/infoextranet/listP.php',
                                'langs'=>'infoextranet@infoextranet',
                                'position'=>1000+$r,
                                'enabled'=>'$conf->infoextranet->enabled',
                                'perms'=>'1',
                                'target'=>'',
                                'user'=>2);

        $this->menu[$r++]=array('fk_menu'=>'fk_mainmenu=infoextranet,fk_leftmenu=infoextranet_list',
                                'type'=>'left',
                                'titre'=>'Liste - '.$langs->trans('TitleR'),
                                'mainmenu'=>'infoextranet',
                                'leftmenu'=>'infoextranet_listr',
                                'url'=>'/infoextranet/listR.php',
                                'langs'=>'infoextranet@infoextranet',
                                'position'=>1000+$r,
                                'enabled'=>'$conf->infoextranet->enabled',
                                'perms'=>'1',
                                'target'=>'',
                                'user'=>2);

        $this->menu[$r++]=array('fk_menu'=>'fk_mainmenu=infoextranet,fk_leftmenu=infoextranet_list',
                                'type'=>'left',
                                'titre'=>'Liste - '.$langs->trans('TitleSI'),
                                'mainmenu'=>'infoextranet',
                                'leftmenu'=>'infoextranet_listsi',
                                'url'=>'/infoextranet/listSI.php',
                                'langs'=>'infoextranet@infoextranet',
                                'position'=>1000+$r,
                                'enabled'=>'$conf->infoextranet->enabled',
                                'perms'=>'1',
                                'target'=>'',
                                'user'=>2);

        $this->menu[$r++]=array('fk_menu'=>'fk_mainmenu=infoextranet,fk_leftmenu=infoextranet_list',
                                'type'=>'left',
                                'titre'=>'Liste - '.$langs->trans('TitleH'),
                                'mainmenu'=>'infoextranet',
                                'leftmenu'=>'infoextranet_listh',
                                'url'=>'/infoextranet/listH.php',
                                'langs'=>'infoextranet@infoextranet',
                                'position'=>1000+$r,
                                'enabled'=>'$conf->infoextranet->enabled',
                                'perms'=>'1',
                                'target'=>'',
                                'user'=>2);



		/*$this->menu[$r++]=array(	'fk_menu'=>'fk_mainmenu=infoextranet,fk_leftmenu=infoextranet',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
								'type'=>'left',			                // This is a Left menu entry
								'titre'=>'New Role',
								'mainmenu'=>'infoextranet',
								'leftmenu'=>'infoextranet_role_new',
								'url'=>'/infoextranet/role_page.php?action=create',
								'langs'=>'infoextranet@infoextranet',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
								'position'=>1000+$r,
								'enabled'=>'$conf->infoextranet->enabled',  // Define condition to show or hide menu entry. Use '$conf->infoextranet->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
								'perms'=>'1',			                // Use 'perms'=>'$user->rights->infoextranet->level1->level2' if you want your menu with a permission rules
								'target'=>'',
								'user'=>2);				                // 0=Menu for internal users, 1=external users, 2=both*/


		$this->menu[$r++]=array(
						'fk_menu'=>'fk_mainmenu=infoextranet',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
								'type'=>'left',			                // This is a Left menu entry
								'titre'=>$langs->trans('Application'),
								'mainmenu'=>'infoextranet',
								'leftmenu'=>'infoextranet_application',
								'url'=>'/infoextranet/application_list.php',
								'langs'=>'infoextranet@infoextranet',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
								'position'=>1100+$r,
								'enabled'=>'$conf->infoextranet->enabled',  // Define condition to show or hide menu entry. Use '$conf->infoextranet->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
								'perms'=>'1',			                // Use 'perms'=>'$user->rights->infoextranet->level1->level2' if you want your menu with a permission rules
								'target'=>'',
								'user'=>2);
		$this->menu[$r++]=array(
						'fk_menu'=>'fk_mainmenu=infoextranet,fk_leftmenu=infoextranet_application',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
								'type'=>'left',			                // This is a Left menu entry
								'titre'=>$langs->trans('ListApplication'),
								'mainmenu'=>'infoextranet',
								'leftmenu'=>'infoextranet_applicationlist',
								'url'=>'/infoextranet/application_list.php',
								'langs'=>'infoextranet@infoextranet',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
								'position'=>1100+$r,
								'enabled'=>'$conf->infoextranet->enabled',  // Define condition to show or hide menu entry. Use '$conf->infoextranet->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
								'perms'=>'1',			                // Use 'perms'=>'$user->rights->infoextranet->level1->level2' if you want your menu with a permission rules
								'target'=>'',
								'user'=>2);				                // 0=Menu for internal users, 1=external users, 2=both
		$this->menu[$r++]=array(
						'fk_menu'=>'fk_mainmenu=infoextranet,fk_leftmenu=infoextranet_application',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
								'type'=>'left',			                // This is a Left menu entry
								'titre'=>$langs->trans('NewApplication'),
								'mainmenu'=>'infoextranet',
								'leftmenu'=>'infoextranet_applicationnew',
								'url'=>'/infoextranet/application_card.php?action=create',
								'langs'=>'infoextranet@infoextranet',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
								'position'=>1100+$r,
								'enabled'=>'$conf->infoextranet->enabled',  // Define condition to show or hide menu entry. Use '$conf->infoextranet->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
								'perms'=>'1',			                // Use 'perms'=>'$user->rights->infoextranet->level1->level2' if you want your menu with a permission rules
								'target'=>'',
								'user'=>2);				                // 0=Menu for internal users, 1=external users, 2=both

        $this->menu[$r++]=array(
                                'fk_menu'=>'fk_mainmenu=infoextranet',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
                                'type'=>'left',			                // This is a Left menu entry
                                'titre'=>$langs->trans('Device'),
                                'mainmenu'=>'infoextranet',
                                'leftmenu'=>'infoextranet_device',
                                'url'=>'/infoextranet/device_list.php',
                                'langs'=>'infoextranet@infoextranet',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
                                'position'=>1100+$r,
                                'enabled'=>'$conf->infoextranet->enabled',  // Define condition to show or hide menu entry. Use '$conf->infoextranet->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
                                'perms'=>'1',			                // Use 'perms'=>'$user->rights->infoextranet->level1->level2' if you want your menu with a permission rules
                                'target'=>'',
                                'user'=>2);				                // 0=Menu for internal users, 1=external users, 2=both
        $this->menu[$r++]=array(
                                'fk_menu'=>'fk_mainmenu=infoextranet,fk_leftmenu=infoextranet_device',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
                                'type'=>'left',			                // This is a Left menu entry
                                'titre'=>$langs->trans('ListDevice'),
                                'mainmenu'=>'infoextranet',
                                'leftmenu'=>'infoextranet_devicelist',
                                'url'=>'/infoextranet/device_list.php',
                                'langs'=>'infoextranet@infoextranet',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
                                'position'=>1100+$r,
                                'enabled'=>'$conf->infoextranet->enabled',  // Define condition to show or hide menu entry. Use '$conf->infoextranet->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
                                'perms'=>'1',			                // Use 'perms'=>'$user->rights->infoextranet->level1->level2' if you want your menu with a permission rules
                                'target'=>'',
                                'user'=>2);				                // 0=Menu for internal users, 1=external users, 2=both

        $this->menu[$r++]=array(
                                'fk_menu'=>'fk_mainmenu=infoextranet,fk_leftmenu=infoextranet_device',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
                                'type'=>'left',			                // This is a Left menu entry
                                'titre'=>$langs->trans('NewDevice'),
                                'mainmenu'=>'infoextranet',
                                'leftmenu'=>'infoextranet_devicenew',
                                'url'=>'/infoextranet/device_card.php?action=create',
                                'langs'=>'infoextranet@infoextranet',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
                                'position'=>1100+$r,
                                'enabled'=>'$conf->infoextranet->enabled',  // Define condition to show or hide menu entry. Use '$conf->infoextranet->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
                                'perms'=>'1',			                // Use 'perms'=>'$user->rights->infoextranet->level1->level2' if you want your menu with a permission rules
                                'target'=>'',
                                'user'=>2);				                // 0=Menu for internal users, 1=external users, 2=both

        $this->menu[$r++]=array(
                                'fk_menu'=>'fk_mainmenu=infoextranet',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
                                'type'=>'left',			                // This is a Left menu entry
                                'titre'=>$langs->trans('Addresses'),
                                'mainmenu'=>'infoextranet',
                                'leftmenu'=>'infoextranet_address',
                                'url'=>'/infoextranet/address_list.php',
                                'langs'=>'infoextranet@infoextranet',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
                                'position'=>1100+$r,
                                'enabled'=>'$conf->infoextranet->enabled',  // Define condition to show or hide menu entry. Use '$conf->infoextranet->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
                                'perms'=>'1',			                // Use 'perms'=>'$user->rights->infoextranet->level1->level2' if you want your menu with a permission rules
                                'target'=>'',
                                'user'=>2);				                // 0=Menu for internal users, 1=external users, 2=both

        $this->menu[$r++]=array(
                                'fk_menu'=>'fk_mainmenu=infoextranet,fk_leftmenu=infoextranet_address',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
                                'type'=>'left',			                // This is a Left menu entry
                                'titre'=>$langs->trans('ListAddress'),
                                'mainmenu'=>'infoextranet',
                                'leftmenu'=>'infoextranet_addresslist',
                                'url'=>'/infoextranet/address_list.php',
                                'langs'=>'infoextranet@infoextranet',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
                                'position'=>1100+$r,
                                'enabled'=>'$conf->infoextranet->enabled',  // Define condition to show or hide menu entry. Use '$conf->infoextranet->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
                                'perms'=>'1',			                // Use 'perms'=>'$user->rights->infoextranet->level1->level2' if you want your menu with a permission rules
                                'target'=>'',
                                'user'=>2);				                // 0=Menu for internal users, 1=external users, 2=both
        $this->menu[$r++]=array(
                                'fk_menu'=>'fk_mainmenu=infoextranet,fk_leftmenu=infoextranet_address',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
                                'type'=>'left',			                // This is a Left menu entry
                                'titre'=>$langs->trans('NewAddress'),
                                'mainmenu'=>'infoextranet',
                                'leftmenu'=>'infoextranet_addressnew',
                                'url'=>'/infoextranet/address_card.php?action=create',
                                'langs'=>'infoextranet@infoextranet',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
                                'position'=>1100+$r,
                                'enabled'=>'$conf->infoextranet->enabled',  // Define condition to show or hide menu entry. Use '$conf->infoextranet->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
                                'perms'=>'1',			                // Use 'perms'=>'$user->rights->infoextranet->level1->level2' if you want your menu with a permission rules
                                'target'=>'',
                                'user'=>2);				                // 0=Menu for internal users, 1=external users, 2=both

        $this->menu[$r++]=array(
                                'fk_menu'=>'fk_mainmenu=infoextranet',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
                                'type'=>'left',			                // This is a Left menu entry
                                'titre'=>$langs->trans('Role'),
                                'mainmenu'=>'infoextranet',
                                'leftmenu'=>'infoextranet_role',
                                'url'=>'/infoextranet/role_list.php',
                                'langs'=>'infoextranet@infoextranet',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
                                'position'=>1100+$r,
                                'enabled'=>'$conf->infoextranet->enabled',  // Define condition to show or hide menu entry. Use '$conf->infoextranet->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
                                'perms'=>'1',			                // Use 'perms'=>'$user->rights->infoextranet->level1->level2' if you want your menu with a permission rules
                                'target'=>'',
                                'user'=>2);				                // 0=Menu for internal users, 1=external users, 2=both

        $this->menu[$r++]=array(
                                'fk_menu'=>'fk_mainmenu=infoextranet,fk_leftmenu=infoextranet_role',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
                                'type'=>'left',			                // This is a Left menu entry
                                'titre'=>$langs->trans('ListRole'),
                                'mainmenu'=>'infoextranet',
                                'leftmenu'=>'infoextranet_rolelist',
                                'url'=>'/infoextranet/role_list.php',
                                'langs'=>'infoextranet@infoextranet',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
                                'position'=>1100+$r,
                                'enabled'=>'$conf->infoextranet->enabled',  // Define condition to show or hide menu entry. Use '$conf->infoextranet->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
                                'perms'=>'1',			                // Use 'perms'=>'$user->rights->infoextranet->level1->level2' if you want your menu with a permission rules
                                'target'=>'',
                                'user'=>2);				                // 0=Menu for internal users, 1=external users, 2=both

        $this->menu[$r++]=array(
                                'fk_menu'=>'fk_mainmenu=infoextranet,fk_leftmenu=infoextranet_role',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
                                'type'=>'left',			                // This is a Left menu entry
                                'titre'=>$langs->trans('NewRole'),
                                'mainmenu'=>'infoextranet',
                                'leftmenu'=>'infoextranet_rolenew',
                                'url'=>'/infoextranet/role_card.php?action=create',
                                'langs'=>'infoextranet@infoextranet',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
                                'position'=>1100+$r,
                                'enabled'=>'$conf->infoextranet->enabled',  // Define condition to show or hide menu entry. Use '$conf->infoextranet->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
                                'perms'=>'1',			                // Use 'perms'=>'$user->rights->infoextranet->level1->level2' if you want your menu with a permission rules
                                'target'=>'',
                                'user'=>2);				                // 0=Menu for internal users, 1=external users, 2=both

        /* */       $this->menu[$r++]=array(
                                'fk_menu'=>'fk_mainmenu=infoextranet',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
                                'type'=>'left',			                // This is a Left menu entry
                                'titre'=>$langs->trans('Dictionnary'),
                                'mainmenu'=>'infoextranet',
                                'leftmenu'=>'infoextranet_setup',
                                'url'=>'/infoextranet/dict.php',
                                'langs'=>'infoextranet@infoextranet',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
                                'position'=>1100+$r,
                                'enabled'=>'$conf->infoextranet->enabled',  // Define condition to show or hide menu entry. Use '$conf->infoextranet->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
                                'perms'=>'1',			                // Use 'perms'=>'$user->rights->infoextranet->level1->level2' if you want your menu with a permission rules
                                'target'=>'',
                                'user'=>2);				                // 0=Menu for internal users, 1=external users, 2=both

        /* */


        $this->menu[$r++]=array(
                                'fk_menu'=>'fk_mainmenu=infoextranet',
                                'type'=>'left',
                                'titre'=>'Configuration',
                                'mainmenu'=>'infoextranet',
                                'leftmenu'=>'infoextranet_setup',
                                'url'=>'/infoextranet/admin/setup.php',
                                'langs'=>'infoextranet@infoextranet',
                                'position'=>11000+$r,
                                'enabled'=>'$conf->infoextranet->enabled',
                                'perms'=>'$user->rights->infoextranet->configure',
                                'target'=>'',
                                'user'=>2);


        $this->menu[$r++]=array(
                                'fk_menu'=>'fk_mainmenu=infoextranet',
                                'type'=>'left',
                                'titre'=>'Documentation',
                                'mainmenu'=>'infoextranet',
                                'leftmenu'=>'infoextranet_setup',
                                'url'=>'/infoextranet/user_doc.php',
                                'langs'=>'infoextranet@infoextranet',
                                'position'=>11000+$r,
                                'enabled'=>'$conf->infoextranet->enabled',
                                'perms'=>'$user->rights->infoextranet->configure',
                                'target'=>'',
                                'user'=>2);

		/* END MODULEBUILDER LEFTMENU MYOBJECT */


		// Exports
		$r=1;

		/* BEGIN MODULEBUILDER EXPORT MYOBJECT */
		/*
		$langs->load("infoextranet@infoextranet");
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='RoleLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_icon[$r]='role@infoextranet';
		$keyforclass = 'Role'; $keyforclassfile='/mymobule/class/role.class.php'; $keyforelement='role';
		include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
		$keyforselect='role'; $keyforaliasextra='extra'; $keyforelement='role';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		//$this->export_dependencies_array[$r]=array('mysubobject'=>'ts.rowid', 't.myfield'=>array('t.myfield2','t.myfield3')); // To force to activate one or several fields if we select some fields that need same (like to select a unique key if we ask a field of a child to avoid the DISTINCT to discard them, or for computed field than need several other fields)
		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'role as t';
		$this->export_sql_end[$r] .=' WHERE 1 = 1';
		$this->export_sql_end[$r] .=' AND t.entity IN ('.getEntity('role').')';
		$r++; */
		/* END MODULEBUILDER EXPORT MYOBJECT */
	}

    /**
     *      Update or create extrafields if it doesn't exist
     *
     *      @param      Extrafield      $extrafields            Extrafield class
     *      @param      string          $attrname               Code of attribute
     *      @param      string          $label                  Label of attribute
     *      @param      int             $type                   Type of attribute ('int', 'text', 'varchar', 'date', 'datehour', 'float')
     *      @param      int             $pos                    Position of attribute
     *      @param      int             $size                   Size of attribut
     *      @param      string          $default_value          Default value on database
     *      @param      string          $elementtype            Element type ('member', 'product', 'thirdparty', ...)
     *      @param      array | string  $param                  Params for field (ex for select list : array('options' => array(value'=>'label of option')) )
     *      @param      int             $alwayseditable         Is attribute always editable regardless of the document status
     *      @param      int             $hidden                 Visibility
     *      @return     int                                     <= 0 on error, > 0 on success
     */
    private function addUpdateExtrafields($extrafields, $attrname, $label, $type, $pos, $size, $default_value, $elementtype, $param, $alwayseditable, $hidden)
    {
        $res = $extrafields->update($attrname, $label, $type, $size, $elementtype, 0, 0, $pos, $param, $alwayseditable, '', $hidden, 0,  $default_value);
        if ($res <= 0)
            $res = $extrafields->addExtrafield($attrname, $label, $type, $pos, $size, $elementtype, 0, 0, $default_value, $param, $alwayseditable, '', $hidden);

        return $res;
    }

    /**
     *      Create extrafields used by infoExtranet
     *
     *      @return     void
     */
    private function createExtrafields()
    {
        global $langs;

        $langs->load("infoextranet@infoextranet");
        $extrafields = new Extrafields($this->db);
        $prefix = 'c42';

        $i = 0;
        $pos = 42000;

        // Outils Externes
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'O_evernote_cli', $langs->trans('ExtranetEvernoteCli'), 'url', $pos, '','','thirdparty', '', 1, 0);
        $pos++;
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'O_zendesk_orga', $langs->trans('ExtranetZendeskOrga'), 'url', $pos, '','','thirdparty', '', 1, 1);
        $pos++;
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'O_zendesk_wiki', $langs->trans('ExtranetZendeskWiki'), 'url', $pos, '','','thirdparty', '', 1, 0);
        $pos++;
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'O_ocs', $langs->trans('ExtranetOCS'), 'url', $pos, '','','thirdparty', '', 1, 0);

        $pos = 42100;

        // Maintenance
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'M_contract', $langs->trans('ExtranetContract'), 'int', $pos, 10, '-1','thirdparty', '', 1, 0);
        $pos++;
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'M_nb_serv_contract', $langs->trans('ExtranetServerContract'), 'int', $pos, 10, '','thirdparty', '', 1, 0);
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'M_nb_serv_nocontract', $langs->trans('ExtranetServerNoContract'), 'int', $pos, 10,'','thirdparty', '', 1, 0);
        $pos++;
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'M_nb_post_contract', $langs->trans('ExtranetPostContract'), 'int', $pos, 10,'','thirdparty', '', 1, 0);
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'M_nb_post_nocontract', $langs->trans('ExtranetPostNoContract'), 'int', $pos, 10,'','thirdparty', '', 1, 0);
        $pos++;
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'M_public_note', $langs->trans('ExtranetPublicNoteM'), 'text', $pos, '','','thirdparty', '', 1, 0);

        $pos = 42200;

        // Infra Poste
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'P_contract', $langs->trans('ExtranetContract'), 'int', $pos, 10,'-1','thirdparty', '', 1, 0);
        $pos++;
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'P_nb_serv', $langs->trans('ExtranetNbServer'), 'int', $pos, 10,'','thirdparty', '', 1, 0);
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'P_nb_post', $langs->trans('ExtranetNbPost'), 'int', $pos, 10,'','thirdparty', '', 1, 0);
        $pos++;
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'P_public_note', $langs->trans('ExtranetPublicNoteP'), 'text', $pos, '','','thirdparty', '', 1, 0);
        $pos++;
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'P_soft_backup', $langs->trans('ExtranetSoftBackup'), 'sellist', $pos, '','','thirdparty', array("options" => array("infoextranet_softbackup:label:rowid::active=1" => null)), 1, 0);
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'P_dest_backup', $langs->trans('ExtranetDestBackup'), 'sellist', $pos, '','','thirdparty', array("options" => array("infoextranet_destbackup:label:rowid::active=1" => null)), 1, 0);
        $pos++;
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'P_dir_backup', $langs->trans('ExtranetDirBackup'), 'text', $pos, '','','thirdparty', '', 1, 0);
        $pos++;
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'P_soft_backup_ext', $langs->trans('ExtranetSoftBackupExt'), 'sellist', $pos, '','','thirdparty', array("options" => array("infoextranet_softbackup:label:rowid::active=1" => null)), 1, 0);
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'P_volume_backup_ext', $langs->trans('ExtranetVolumeBackupExt'), 'sellist', $pos, '','','thirdparty', array("options" => array("infoextranet_volumebackup:label:rowid::active=1" => null)), 1, 0);
        $pos++;
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'P_dir_backup_ext', $langs->trans('ExtranetDirBackupExt'), 'text', $pos, '','','thirdparty', '', 1, 0);

        $pos = 42300;

        //Infra Réseau
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'R_contract', $langs->trans('ExtranetContract'), 'int', $pos, 10,'-1','thirdparty', '', 1, 0);
        $pos++;
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'R_vlan_management', $langs->trans('ExtranetVlanManagment'), 'boolean', $pos, '','','thirdparty', '', 1, 0);
        $pos++;
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'R_vpn_ipsec_buro', $langs->trans('ExtranetVpnIpsecBuro'), 'boolean', $pos, '','','thirdparty', '', 1, 0);
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'R_vpn_ssl_cli', $langs->trans('ExtranetVpnSslCli'), 'boolean', $pos, '','','thirdparty', '', 1, 0);
        $pos++;
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'R_vpn_ssl_roc', $langs->trans('ExtranetVpnSslRoc'), 'boolean', $pos, '','','thirdparty', '', 1, 0);
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'R_vpn_ipsec_roc', $langs->trans('ExtranetVpnIpsecRoc'), 'boolean', $pos, '','','thirdparty', '', 1, 0);
        $pos++;
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'R_wifi_site', $langs->trans('ExtranetWifiSite'), 'boolean', $pos, '','','thirdparty', '', 1, 0);
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'R_wifi_guest', $langs->trans('ExtranetWifiGuest'), 'boolean', $pos, '','','thirdparty', '', 1, 0);
        $pos++;
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'R_multiple_wan', $langs->trans('ExtranetMultipleWan'), 'boolean', $pos, '','','thirdparty', '', 1, 0);
        $pos++;
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'R_wan1_operator', $langs->trans('ExtranetWan1Operator'), 'sellist', $pos, '','','thirdparty', array("options" => array("infoextranet_wanoperator:label:rowid::active=1" => null)), 1, 0);
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'R_wan1_ip', $langs->trans('ExtranetWan1Ip'), 'varchar', $pos, 255,'','thirdparty', '', 1, 0);
        $pos++;
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'R_wan2_operator', $langs->trans('ExtranetWan2Operator'), 'sellist', $pos, '','','thirdparty', array("options" => array("infoextranet_wanoperator:label:rowid::active=1" => null)), 1, 0);
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'R_wan2_ip', $langs->trans('ExtranetWan2Ip'), 'varchar', $pos, 255,'','thirdparty', '', 1, 0);
        $pos++;
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'R_firewall1', $langs->trans('ExtranetFirewall1'), 'sellist', $pos, '','','thirdparty', array("options" => array("infoextranet_firewall:label:rowid::active=1" => null)), 1, 0);
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'R_firewall2', $langs->trans('ExtranetFirewall2'), 'sellist', $pos, '','','thirdparty', array("options" => array("infoextranet_firewall:label:rowid::active=1" => null)), 1, 0);
        $pos++;
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'R_access_point1', $langs->trans('ExtranetAccessPoint1'), 'sellist', $pos, '','','thirdparty', array("options" => array("infoextranet_accesspoint:label:rowid::active=1" => null)), 1, 0);
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'R_access_point2', $langs->trans('ExtranetAccessPoint2'), 'sellist', $pos, '','','thirdparty', array("options" => array("infoextranet_accesspoint:label:rowid::active=1" => null)), 1, 0);
        $pos++;
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'R_switch1', $langs->trans('ExtranetSwitch1'), 'sellist', $pos, '','','thirdparty', array("options" => array("infoextranet_switch:label:rowid::active=1" => null)), 1, 0);
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'R_switch2', $langs->trans('ExtranetSwitch2'), 'sellist', $pos, '','','thirdparty', array("options" => array("infoextranet_switch:label:rowid::active=1" => null)), 1, 0);
        $pos++;
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'R_public_note', $langs->trans('ExtranetPublicNoteR'), 'text', $pos, '','','thirdparty', '', 1, 0);
        $pos++;
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'R_voip_operator', $langs->trans('ExtranetVoipOperator'), 'sellist', $pos, '','','thirdparty', array("options" => array("infoextranet_voipoperator:label:rowid::active=1" => null)), 1, 0);
        $pos++;
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'R_voip_details', $langs->trans('ExtranetVoipDetails'), 'text', $pos, '','','thirdparty', '', 1, 0);

        $pos = 42400;

        // Hébergement S.I
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'SI_contract', $langs->trans('ExtranetContract'), 'int', $pos, 10,'-1','thirdparty', '', 1, 0);
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'SI_hebergeur_si', $langs->trans('ExtranetHebergeurSi'), 'sellist', $pos, '','','thirdparty', array("options" => array("infoextranet_hebergeursi:label:rowid::active=1" => null)), 1, 0);
        $pos++;
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'SI_nb_serv', $langs->trans('ExtranetNbServer'), 'int', $pos, 10,'','thirdparty', '', 1, 0);
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'SI_dedicated_vlan', $langs->trans('ExtranetDedicatedVlan'), 'sellist', $pos, '','','thirdparty', array("options" => array("infoextranet_dedicatedvlan:label:rowid::active=1" => null)), 1, 0);
        $pos++;
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'SI_vpn_ssl_roc', $langs->trans('ExtranetVpnSslRoc'), 'boolean', $pos, '','','thirdparty', '', 1, 0);
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'SI_vpn_ipsec_roc', $langs->trans('ExtranetVpnIpsecRoc'), 'boolean', $pos, '','','thirdparty', '', 1, 0);
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'SI_websitepanl_url', $langs->trans('ExtranetWebsitepanlUrlSI'), 'url', $pos, '','','thirdparty', '', 1, 0);
        $pos++;
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'SI_public_note', $langs->trans('ExtranetPublicNoteSI'), 'text', $pos, '','','thirdparty', '', 1, 0);

        $pos = 42500;

        // Hébergement Web
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'H_contract', $langs->trans('ExtranetContract'), 'int', $pos, 10,'-1','thirdparty', '', 1, 0);
        $pos++;
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'H_nb_domain', $langs->trans('ExtranetNbDomain'), 'int', $pos, 10, '','thirdparty', '', 1, 0);
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'H_dns_host', $langs->trans('ExtranetDnsHost'), 'sellist', $pos, '','','thirdparty', array("options" => array("infoextranet_dnshost:label:rowid::active=1" => null)), 1, 0);
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'H_admin_dns_url', $langs->trans('ExtranetAdminDnsUrl'), 'url', $pos, '','','thirdparty', '', 1, 0);
        $pos++;
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'H_web_host', $langs->trans('ExtranetWebHost'), 'sellist', $pos, '','','thirdparty', array("options" => array("infoextranet_webhost:label:rowid::active=1" => null)), 1, 0);
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'H_admin_web_url', $langs->trans('ExtranetAdminWebUrl'), 'url', $pos, '','','thirdparty', '', 1, 0);
        $pos++;
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'H_mail_host', $langs->trans('ExtranetMailHost'), 'sellist', $pos, '','','thirdparty', array("options" => array("infoextranet_mailhost:label:rowid::active=1" => null)), 1, 0);
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'H_websitepanl_url', $langs->trans('ExtranetWebsitepanlUrlH'), 'url', $pos, '','','thirdparty', '', 1, 0);
        $pos++;
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'H_nb_exchange', $langs->trans('ExtranetNbExchange'), 'int', $pos, 10,'','thirdparty', '', 1, 0);
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'H_nb_pop', $langs->trans('ExtranetNbPop'), 'int', $pos, 10,'','thirdparty', '', 1, 0);
        $pos++;
        $res[$i++] = $this->addUpdateExtrafields($extrafields,$prefix.'H_public_note', $langs->trans('ExtranetPublicNoteH'), 'text', $pos, '','','thirdparty', '', 1, 0);

    }


    /**
	 *	Function called when module is enabled.
	 *	The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *	It also creates data directories
	 *
     *	@param      string	$options    Options when enabling module ('', 'noboxes')
	 *	@return     int             	1 if OK, 0 if KO
	 */
	public function init($options='')
	{
		$this->_load_tables('/infoextranet/sql/');

		// Create extrafields
        $this->createExtrafields();

		$sql = array();

		return $this->_init($sql, $options);
	}

	/**
	 *	Function called when module is disabled.
	 *	Remove from database constants, boxes and permissions from Dolibarr database.
	 *	Data directories are not deleted
	 *
	 *	@param      string	$options    Options when enabling module ('', 'noboxes')
	 *	@return     int             	1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
		$sql = array();

		return $this->_remove($sql, $options);
	}

}
