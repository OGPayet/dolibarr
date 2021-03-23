<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2016 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2017      Open-DSI             <support@open-dsi.fr>
 * Copyright (C) 2019      Alexis LAURIER       <alexis@alexislaurier.fr>
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
 * 	\defgroup   synergiestech     Module Synergies-Tech
 *  \brief      Example of a module descriptor.
 *				Such a file must be copied into htdocs/synergiestech/core/modules directory.
 *  \file       htdocs/synergiestech/core/modules/modSynergiesTech.class.php
 *  \ingroup    synergiestech
 *  \brief      Description and activation file for module Synergies-Tech
 */
include_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';


/**
 *  Description and activation class for module Synergies-Tech
 */
class modSynergiesTech extends DolibarrModules
{
	/**
	 * @var array Module extrafiels entries
	 */
	public $extrafields;

	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $langs, $conf;

		$this->db = $db;
		$langs->load("synergiestech@synergiestech");
		$langs->load('opendsi@synergiestech');

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 500100;		// TODO Go on page http://wiki.dolibarr.org/index.php/List_of_modules_id to reserve id number for your module
		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'synergiestech';

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
		$this->description = "Description of module Synergies-Tech";
		$this->descriptionlong = "";
		$this->editor_name = 'Open-DSI';
		$this->editor_url = 'http://www.open-dsi.fr';

		// Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'
		$this->version = '1.0.10';
		// Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		$this->picto = 'opendsi@synergiestech';

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
			'tpl' => 1,
			'models' => 1,
			'triggers' => 1,
			'hooks' => array('globalapi', 'requestmanagercard', 'requestmanagerdao', 'propalcard', 'ordercard', 'contractcard', 'tab_supplier_order', 'tab_expedition_add', 'invoicelist', 'commonobject', 'main', 'interventionmail'),
			'moduleforexternal' => 1,
			'js' => array('/synergiestech/js/large_model_select.js'),
		);

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/mymodule/temp");
		$this->dirs = array('/synergiestech/temp');

		// Config pages. Put here list of php page, stored into mymodule/admin directory, to use to setup module.
		$this->config_page_url = array("setup.php@synergiestech");

		// Dependencies
		$this->hidden = false;			// A condition to hide module
		$this->depends = array('modLdap','modAdvanceDictionaries');		// List of modules id that must be enabled if this module is enabled
		$this->requiredby = array();	// List of modules id to disable if this one is disabled
		$this->conflictwith = array();	// List of modules id this module is in conflict with
		$this->phpmin = array(7, 0);					// Minimum version of PHP required by module
		$this->need_dolibarr_version = array(6, 0);	// Minimum version of Dolibarr required by module
		$this->langfiles = array("synergiestech@synergiestech", "opendsi@synergiestech");

		// Constants
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example: $this->const=array(0=>array('MYMODULE_MYNEWCONST1','chaine','myvalue','This is a constant to add',1),
		//                             1=>array('MYMODULE_MYNEWCONST2','chaine','myvalue','This is another constant to add',0, 'current', 1)
		// );
		$this->const = array(
			0 => array('SYNERGIESTECH_ENABLED_WORKFLOW_SHIPPING_CREATED_TO_ATTACH_EQUIPMENTS', 'chaine', '1', '', 0, 'current'),
			1 => array('SYNERGIESTECH_FORCE_ATTACH_EQUIPMENTS_AFTER_SHIPPING_CREATED', 'chaine', '1', '', 0, 'current'),
			2 => array('SYNERGIESTECH_ENABLED_WORKFLOW_ORDER_SUPPLIER_DISPATCH_TO_SET_EQUIPMENTS', 'chaine', '1', '', 0, 'current'),
			3 => array('SYNERGIESTECH_FORCE_SET_EQUIPMENTS_AFTER_ORDER_SUPPLIER_DISPATCH', 'chaine', '1', '', 0, 'current'),
			4 => array('SYNERGIESTECH_PRINCIPAL_WAREHOUSE_NB_SHOWED', 'chaine', '10', '', 0, 'current'),
		);

		// Array to add new pages in new tabs
		// Example: $this->tabs = array('objecttype:+tabname1:Title1:mylangfile@mymodule:$user->rights->mymodule->read:/mymodule/mynewtab1.php?id=__ID__',  					// To add a new tab identified by code tabname1
		//                              'objecttype:+tabname2:SUBSTITUTION_Title2:mylangfile@mymodule:$user->rights->othermodule->read:/mymodule/mynewtab2.php?id=__ID__',  	// To add another new tab identified by code tabname2. Label will be result of calling all substitution functions on 'Title2' key.
		//                              'objecttype:-tabname:NU:conditiontoremove');                                                     										// To remove an existing tab identified by code tabname
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
		$this->tabs = array(
			'intervention:-equipement:Equipements:@equipement:/equipement/tabs/fichinter.php?id=__ID__',
			'intervention:-eventadd:EventsAdd:@equipement:/equipement/tabs/fichinterAdd.php?id=__ID__',
			'product:-price::!$user->rights->synergiestech->product_line_price->lire:',
			'group:-ldap::true',
			'synergiestech_admin:+dictionary_synergiestechtimeslot:SynergiesTechTimeSlotDictionaryLabel:synergiestech@synergiestech:true:/synergiestech/admin/dictionaries.php?name=synergiestechtimeslot',
			'synergiestech_admin:+dictionary_activedirectorygroupmapping:SynergiesTechActiveDirectoryGroupMappingTabName:synergiestech@synergiestech:true:/synergiestech/admin/dictionaries.php?name=activedirectorygroupmapping',
			'synergiestech_admin:+autoactions:SynergiesTechAutoActionsTabName:synergiestech@synergiestech:true:/synergiestech/admin/agenda.php',
			'thirdparty:-rm_request_list::true',
            'thirdparty:-rm_request_planning_list::true',
			'thirdparty:+rm_request_list:SUBSTITUTION_RMLISTTABLABEL:requestmanager@requestmanager:$user->rights->requestmanager->lire:/requestmanager/list.php?socid=__ID__',
            'thirdparty:+rm_request_planning_list:SUBSTITUTION_RMPLANNINGLISTTABLABEL:requestmanager@requestmanager:$user->rights->requestmanager->planning->lire:/requestmanager/list.php?planning=1&socid=__ID__',
			'thirdparty:+listOfProposal:SynergiesTechListOfProposal:synergiestech@synergiestech:$user->rights->propal->lire:/comm/propal/list.php?socid=__ID__',
			'thirdparty:+listOfOrder:SynergiesTechListOfOrder:synergiestech@synergiestech:$user->rights->commande->lire:/commande/list.php?socid=__ID__',
			'thirdparty:+listOfIntervention:SynergiesTechListOfIntervention:synergiestech@synergiestech:$user->rights->ficheinter->lire:/fichinter/list.php?socid=__ID__',
			'thirdparty:+listOfInvoice:SynergiesTechListOfInvoice:synergiestech@synergiestech:$user->rights->facture->lire:/compta/facture/list.php?socid=__ID__',
			'thirdparty:-document::!$user->rights->synergiestech->documents->thirdparty',
			'order:-documents::!$user->rights->synergiestech->documents->customerorder',
			'propal:-document::!$user->rights->synergiestech->documents->customerpropal',
		);

		if (!isset($conf->synergiestech) || !isset($conf->synergiestech->enabled)) {
			$conf->synergiestech = new stdClass();
			$conf->synergiestech->enabled = 0;
		}

		// Dictionaries
		$this->dictionaries = array();
		/* Example:
        $this->dictionaries=array(
            'langs'=>'mylangfile@mymodule',
            'tabname'=>array(MAIN_DB_PREFIX."table1",MAIN_DB_PREFIX."table2",MAIN_DB_PREFIX."table3"),		// List of tables we want to see into dictonnary editor
            'tablib'=>array("Table1","Table2","Table3"),													// Label of tables
            'tabsql'=>array('SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table1 as f','SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table2 as f','SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table3 as f'),	// Request to select fields
            'tabsqlsort'=>array("label ASC","label ASC","label ASC"),																					// Sort order
            'tabfield'=>array("code,label","code,label","code,label"),																					// List of fields (result of select to show dictionary)
            'tabfieldvalue'=>array("code,label","code,label","code,label"),																				// List of fields (list of fields to edit a record)
            'tabfieldinsert'=>array("code,label","code,label","code,label"),																			// List of fields (list of fields for insert)
            'tabrowid'=>array("rowid","rowid","rowid"),																									// Name of columns with primary key (try to always name it 'rowid')
            'tabcond'=>array($conf->mymodule->enabled,$conf->mymodule->enabled,$conf->mymodule->enabled)												// Condition to show each dictionary
        );
        */

		// Boxes
		// Add here list of php file(s) stored in core/boxes that contains class to show a box.
		$this->boxes = array();			// List of boxes
		// Example:
		//$this->boxes=array(
		//    0=>array('file'=>'myboxa.php@mymodule','note'=>'','enabledbydefaulton'=>'Home'),
		//    1=>array('file'=>'myboxb.php@mymodule','note'=>''),
		//    2=>array('file'=>'myboxc.php@mymodule','note'=>'')
		//);

		// Cronjobs
		$this->cronjobs = array(
			0 => array(
				'label' => $langs->trans('SynergiesTechUserCronJob'),
				'jobtype' => 'command',
				'comment'=> ' ',
				'command' => 'php ' . DOL_DOCUMENT_ROOT . '/custom/synergiestech/scripts/sync_users_ldap2dolibarr.php commitiferror',
				'frequency' => 1,
				'unitfrequency' => 60,
				'test' => false
			)
		);
		// List of cron jobs entries to add
		// Example: $this->cronjobs=array(0=>array('label'=>'My label', 'jobtype'=>'method', 'class'=>'/dir/class/file.class.php', 'objectname'=>'MyClass', 'method'=>'myMethod', 'parameters'=>'', 'comment'=>'Comment', 'frequency'=>2, 'unitfrequency'=>3600, 'test'=>true),
		//                                1=>array('label'=>'My label', 'jobtype'=>'command', 'command'=>'', 'parameters'=>'', 'comment'=>'Comment', 'frequency'=>1, 'unitfrequency'=>3600*24, 'test'=>true)
		// );

		// Permissions
		$this->rights = array();		// Permission array used by this module
		$r = 0;

		$this->rights[$r][0] = 500100;
		$this->rights[$r][1] = 'Generate Ticket Report';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'generate';
		$this->rights[$r][5] = 'ticket_report';
		$r++;

		$this->rights[$r][0] = 500101;
		$this->rights[$r][1] = 'Voir les prix des lignes de produits';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'product_line_price';
		$this->rights[$r][5] = 'lire';
		$r++;

		$this->rights[$r][0] = 500102;
		$this->rights[$r][1] = 'Editer les prix des lignes de produits';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'product_line_price';
		$this->rights[$r][5] = 'creer';
		$r++;

		$this->rights[$r][0] = 500103;
		$this->rights[$r][1] = 'Voir les remises des lignes de produits';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'product_line_discount';
		$this->rights[$r][5] = 'lire';
		$r++;

		$this->rights[$r][0] = 500104;
		$this->rights[$r][1] = 'Editer les remises des lignes de produits';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'product_line_discount';
		$this->rights[$r][5] = 'creer';
		$r++;

		$this->rights[$r][0] = 500107;
		$this->rights[$r][1] = 'Accéder aux propositions qui ont une valeur pour le champs complémentaire "Valeur de l\'installation"';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'propal';
		$this->rights[$r][5] = 'installation_value';
		$r++;

		$this->rights[$r][0] = 500108;
		$this->rights[$r][1] = 'Accéder aux statistiques des propositions commerciales clientes';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'stats';
		$this->rights[$r][5] = 'customerpropal';
		$r++;

		$this->rights[$r][0] = 500109;
		$this->rights[$r][1] = 'Accéder aux statistiques des propositions commerciales fournisseur';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'stats';
		$this->rights[$r][5] = 'supplierpropal';
		$r++;

		$this->rights[$r][0] = 500110;
		$this->rights[$r][1] = 'Accéder aux statistiques des commandes clientes';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'stats';
		$this->rights[$r][5] = 'customerorder';
		$r++;

		$this->rights[$r][0] = 500111;
		$this->rights[$r][1] = 'Accéder aux statistiques des commandes fournisseur';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'stats';
		$this->rights[$r][5] = 'supplierorder';
		$r++;

		$this->rights[$r][0] = 500112;
		$this->rights[$r][1] = 'Accéder aux statistiques des interventions';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'stats';
		$this->rights[$r][5] = 'intervention';
		$r++;

		$this->rights[$r][0] = 500113;
		$this->rights[$r][1] = 'Accéder aux widgets statistiques des propositions commerciales clientes';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'stats';
		$this->rights[$r][5] = 'customerpropalwidget';
		$r++;

		$this->rights[$r][0] = 500114;
		$this->rights[$r][1] = 'Accéder aux widgets statistiques des propositions commerciales fournisseur';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'stats';
		$this->rights[$r][5] = 'supplierpropalwidget';
		$r++;

		$this->rights[$r][0] = 500115;
		$this->rights[$r][1] = 'Accéder aux widgets statistiques des commandes clientes';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'stats';
		$this->rights[$r][5] = 'customerorderwidget';
		$r++;

		$this->rights[$r][0] = 500116;
		$this->rights[$r][1] = 'Accéder aux widgets statistiques des commandes fournisseur';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'stats';
		$this->rights[$r][5] = 'supplierorderwidget';
		$r++;


		$this->rights[$r][0] = 500117;
		$this->rights[$r][1] = 'Voir les montants des propositions commerciales clientes';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'amount';
		$this->rights[$r][5] = 'customerpropal';
		$r++;

		$this->rights[$r][0] = 500118;
		$this->rights[$r][1] = 'Voir les montants des commandes clientes';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'amount';
		$this->rights[$r][5] = 'customerorder';
		$r++;

		$this->rights[$r][0] = 500119;
		$this->rights[$r][1] = 'Accéder les fichiers jointes des propositions commerciales client';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'documents';
		$this->rights[$r][5] = 'customerpropal';
		$r++;

		$this->rights[$r][0] = 500120;
		$this->rights[$r][1] = 'Accéder les fichiers jointes des commandes clients';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'documents';
		$this->rights[$r][5] = 'customerorder';
		$r++;

		$this->rights[$r][0] = 500121;
		$this->rights[$r][1] = "Autoriser l'upload de fichier (uniquement) sans contrôle précis des accès aux pièces via l'api document (CORE)";
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'api';
		$this->rights[$r][5] = 'uploadfile';
		$r++;

		$this->rights[$r][0] = 500122;
		$this->rights[$r][1] = "Autoriser la récupération du message du jour via l'API Status";
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'api';
		$this->rights[$r][5] = 'welcomeMessage';
		$r++;

		$this->rights[$r][0] = 500123;
		$this->rights[$r][1] = 'Réouvrir les interventions';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'fichinter';
		$this->rights[$r][5] = 'reopen';
		$r++;

		$this->rights[$r][0] = 500124;
		$this->rights[$r][1] = 'Voir/Modifier les attributs complémentaires restraint';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'extrafields';
		$r++;

		$this->rights[$r][0] = 500125;
		$this->rights[$r][1] = "Accéder à une liste d'utilisateur interne restreinte via l'api (sélection de collègue dans l'application technicien)";
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'user';
		$this->rights[$r][5] = 'lirerestreint';
		$r++;

		$this->rights[$r][0] = 500126;
		$this->rights[$r][1] = "Permettre la création de demande de type Intervention depuis l'espace Client";
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'requestmanager';
		$this->rights[$r][5] = 'creerIntervention';
		$r++;

		$this->rights[$r][0] = 500127;
		$this->rights[$r][1] = "Permettre de passer une intervention en A Faire même si le contrat lié est inactif";
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'intervention';
		$this->rights[$r][5] = 'validateWithStaleContract';
		$r++;

		$this->rights[$r][0] = 500128;
		$this->rights[$r][1] = "Permettre de passer une intervention en A Faire sans vérifier la justification commerciale de cette intervention";
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'intervention';
		$this->rights[$r][5] = 'validateWithoutCheck';
		$r++;

		$this->rights[$r][0] = 500129;
		$this->rights[$r][1] = "Accéder aux fichiers des tiers";
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'documents';
		$this->rights[$r][5] = 'thirdparty';
		$r++;

		$this->rights[$r][0] = 500130;
		$this->rights[$r][1] = $langs->trans('Permission500130');
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = $langs->trans('Permission500130Level1');
		$this->rights[$r][5] = $langs->trans('Permission500130Level2');
		$r++;
		// Add here list of permission defined by an id, a label, a boolean and two constant strings.
		// Example:
		// $this->rights[$r][0] = $this->numero + $r;	// Permission id (must not be already used)
		// $this->rights[$r][1] = 'Permision label';	// Permission label
		// $this->rights[$r][3] = 1; 					// Permission by default for new user (0/1)
		// $this->rights[$r][4] = 'level1';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		// $this->rights[$r][5] = 'level2';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		// $r++;

		// Main menu entries
		$this->menu = array();			// List of menus to add
		$r = 0;

		// Add here entries to declare new menus
		//
		// Example to declare a new Top Menu entry and its Left menu entry:
		// $this->menu[$r]=array(	'fk_menu'=>'',			                // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
		//							'type'=>'top',			                // This is a Top menu entry
		//							'titre'=>'MyModule top menu',
		//							'mainmenu'=>'mymodule',
		//							'leftmenu'=>'mymodule',
		//							'url'=>'/mymodule/pagetop.php',
		//							'langs'=>'mylangfile@mymodule',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
		//							'position'=>100,
		//							'enabled'=>'$conf->mymodule->enabled',	// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
		//							'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
		//							'target'=>'',
		//							'user'=>2);				                // 0=Menu for internal users, 1=external users, 2=both
		// $r++;
		//
		// Example to declare a Left Menu entry into an existing Top menu entry:
		// $this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=xxx',		    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
		//							'type'=>'left',			                // This is a Left menu entry
		//							'titre'=>'MyModule left menu',
		//							'mainmenu'=>'xxx',
		//							'leftmenu'=>'mymodule',
		//							'url'=>'/mymodule/pagelevel2.php',
		//							'langs'=>'mylangfile@mymodule',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
		//							'position'=>100,
		//							'enabled'=>'$conf->mymodule->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		//							'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
		//							'target'=>'',
		//							'user'=>2);				                // 0=Menu for internal users, 1=external users, 2=both


		// Exports
		$r = 1;

		// Example:
		// $this->export_code[$r]=$this->rights_class.'_'.$r;
		// $this->export_label[$r]='MyModule';	// Translation key (used only if key ExportDataset_xxx_z not found)
		// $this->export_enabled[$r]='1';                               // Condition to show export in list (ie: '$user->id==3'). Set to 1 to always show when module is enabled.
		// $this->export_icon[$r]='generic:MyModule';					// Put here code of icon then string for translation key of module name
		// $this->export_permission[$r]=array(array("mymodule","level1","level2"));
		// $this->export_fields_array[$r]=array('s.rowid'=>"IdCompany",'s.nom'=>'CompanyName','s.address'=>'Address','s.zip'=>'Zip','s.town'=>'Town','s.fk_pays'=>'Country','s.phone'=>'Phone','s.siren'=>'ProfId1','s.siret'=>'ProfId2','s.ape'=>'ProfId3','s.idprof4'=>'ProfId4','s.code_compta'=>'CustomerAccountancyCode','s.code_compta_fournisseur'=>'SupplierAccountancyCode','f.rowid'=>"InvoiceId",'f.facnumber'=>"InvoiceRef",'f.datec'=>"InvoiceDateCreation",'f.datef'=>"DateInvoice",'f.total'=>"TotalHT",'f.total_ttc'=>"TotalTTC",'f.tva'=>"TotalVAT",'f.paye'=>"InvoicePaid",'f.fk_statut'=>'InvoiceStatus','f.note'=>"InvoiceNote",'fd.rowid'=>'LineId','fd.description'=>"LineDescription",'fd.price'=>"LineUnitPrice",'fd.tva_tx'=>"LineVATRate",'fd.qty'=>"LineQty",'fd.total_ht'=>"LineTotalHT",'fd.total_tva'=>"LineTotalTVA",'fd.total_ttc'=>"LineTotalTTC",'fd.date_start'=>"DateStart",'fd.date_end'=>"DateEnd",'fd.fk_product'=>'ProductId','p.ref'=>'ProductRef');
		// $this->export_TypeFields_array[$r]=array('t.date'=>'Date', 't.qte'=>'Numeric', 't.poids'=>'Numeric', 't.fad'=>'Numeric', 't.paq'=>'Numeric', 't.stockage'=>'Numeric', 't.fadparliv'=>'Numeric', 't.livau100'=>'Numeric', 't.forfait'=>'Numeric', 's.nom'=>'Text','s.address'=>'Text','s.zip'=>'Text','s.town'=>'Text','c.code'=>'Text','s.phone'=>'Text','s.siren'=>'Text','s.siret'=>'Text','s.ape'=>'Text','s.idprof4'=>'Text','s.code_compta'=>'Text','s.code_compta_fournisseur'=>'Text','s.tva_intra'=>'Text','f.facnumber'=>"Text",'f.datec'=>"Date",'f.datef'=>"Date",'f.date_lim_reglement'=>"Date",'f.total'=>"Numeric",'f.total_ttc'=>"Numeric",'f.tva'=>"Numeric",'f.paye'=>"Boolean",'f.fk_statut'=>'Status','f.note_private'=>"Text",'f.note_public'=>"Text",'fd.description'=>"Text",'fd.subprice'=>"Numeric",'fd.tva_tx'=>"Numeric",'fd.qty'=>"Numeric",'fd.total_ht'=>"Numeric",'fd.total_tva'=>"Numeric",'fd.total_ttc'=>"Numeric",'fd.date_start'=>"Date",'fd.date_end'=>"Date",'fd.special_code'=>'Numeric','fd.product_type'=>"Numeric",'fd.fk_product'=>'List:product:label','p.ref'=>'Text','p.label'=>'Text','p.accountancy_code_sell'=>'Text');
		// $this->export_entities_array[$r]=array('s.rowid'=>"company",'s.nom'=>'company','s.address'=>'company','s.zip'=>'company','s.town'=>'company','s.fk_pays'=>'company','s.phone'=>'company','s.siren'=>'company','s.siret'=>'company','s.ape'=>'company','s.idprof4'=>'company','s.code_compta'=>'company','s.code_compta_fournisseur'=>'company','f.rowid'=>"invoice",'f.facnumber'=>"invoice",'f.datec'=>"invoice",'f.datef'=>"invoice",'f.total'=>"invoice",'f.total_ttc'=>"invoice",'f.tva'=>"invoice",'f.paye'=>"invoice",'f.fk_statut'=>'invoice','f.note'=>"invoice",'fd.rowid'=>'invoice_line','fd.description'=>"invoice_line",'fd.price'=>"invoice_line",'fd.total_ht'=>"invoice_line",'fd.total_tva'=>"invoice_line",'fd.total_ttc'=>"invoice_line",'fd.tva_tx'=>"invoice_line",'fd.qty'=>"invoice_line",'fd.date_start'=>"invoice_line",'fd.date_end'=>"invoice_line",'fd.fk_product'=>'product','p.ref'=>'product');
		// $this->export_dependencies_array[$r]=array('invoice_line'=>'fd.rowid','product'=>'fd.rowid'); // To add unique key if we ask a field of a child to avoid the DISTINCT to discard them
		// $this->export_sql_start[$r]='SELECT DISTINCT ';
		// $this->export_sql_end[$r]  =' FROM ('.MAIN_DB_PREFIX.'facture as f, '.MAIN_DB_PREFIX.'facturedet as fd, '.MAIN_DB_PREFIX.'societe as s)';
		// $this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'product as p on (fd.fk_product = p.rowid)';
		// $this->export_sql_end[$r] .=' WHERE f.fk_soc = s.rowid AND f.rowid = fd.fk_facture';
		// $this->export_sql_order[$r] .=' ORDER BY s.nom';
		// $r++;

		if (!empty($conf->fournisseur->enabled)) {
			// Import suppliers prices (note: this code is duplicated into module service)
			$r++;
			$this->import_code[$r] = $this->rights_class . '_supplierprices';
			$this->import_label[$r] = "SuppliersPricesOfProductsOrServicesUpgraded";	// Translation key
			$this->import_icon[$r] = 'product';
			$this->import_entities_array[$r] = array();		// We define here only fields that use another icon that the one defined into import_icon
			$this->import_tables_array[$r] = array('sp' => MAIN_DB_PREFIX . 'product_fournisseur_price');
			$this->import_tables_creator_array[$r] = array('sp' => 'fk_user');
			$this->import_fields_array[$r] = array(
				'sp.fk_product' => "ProductOrService*",
				'sp.fk_soc' => "Supplier*", 'sp.ref_fourn' => 'SupplierRef', 'sp.quantity' => "QtyMin*", 'sp.tva_tx' => 'VATRate',
				'sp.price' => "PriceQtyMinHT*",
				'sp.unitprice' => 'UnitPriceHT*',	// TODO Make this file not required and calculate it from price and qty
				'sp.remise_percent' => 'DiscountQtyMin'
			);

			$this->import_convertvalue_array[$r] = array(
				'sp.fk_soc' => array('rule' => 'fetchidfromref', 'classfile' => '/societe/class/societe.class.php', 'class' => 'Societe', 'method' => 'fetch', 'element' => 'ThirdParty'),
				'sp.fk_product' => array('rule' => 'fetchidfromref', 'classfile' => '/product/class/product.class.php', 'class' => 'Product', 'method' => 'fetch', 'element' => 'Product')
			);
			$this->import_examplevalues_array[$r] = array(
				'sp.fk_product' => "PREF123456",
				'sp.fk_soc' => "My Supplier", 'sp.ref_fourn' => "SupplierRef", 'sp.quantity' => "1", 'sp.tva_tx' => '21',
				'sp.price' => "50",
				'sp.unitprice' => '50',
				'sp.remise_percent' => '0'
			);
			$this->import_updatekeys_array[$r] = array('sp.fk_product' => 'ProductOrService', 'sp.ref_fourn' => 'SupplierRef', 'sp.fk_soc' => 'Supplier');
		}
	}

	/**
	 *		Function called when module is enabled.
	 *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *		It also creates data directories
	 *
	 *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		global $langs, $conf;
		$langs->load("synergiestech@synergiestech");

		$sql = array();

		$this->_load_tables('/synergiestech/sql/');

		// Create extrafields
		include_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
		$extrafields = new ExtraFields($this->db);
		// Product
		$result = $extrafields->addExtraField('synergiestech_to_serialize', $langs->trans("SynergiesTechSerializable"), 'boolean', 10,  '', 'product',   0, 0, '', '', 1, '', 1, 0, ''); // For >= v7: ", '', 'synergiestech@synergiestech', '$conf->synergiestech->enabled');"
		$result = $extrafields->addExtraField('synergiestech_warranty', $langs->trans("SynergiesTechWarranty"), 'int', 20,  '', 'product',   0, 0, '', '', 1, '', 1, 0, ''); // For >= v7: ", '', 'synergiestech@synergiestech', '$conf->synergiestech->enabled');"
		$result = $extrafields->addExtraField('st_estimated_begin_date', $langs->trans('SynergiesTechEstimatedBeginDate'), 'datetime', 100,  '', 'fichinter',   0, 0, '', null, 1, '', 1, 0, '');
		$result = $extrafields->addExtraField('st_estimated_end_date', $langs->trans('SynergiesTechEstimatedEndDate'), 'datetime', 101,  '', 'fichinter',   0, 0, '', null, 1, '', 1, 0, '');

		// Create tables of all dictionaries
		dol_include_once('/advancedictionaries/class/dictionary.class.php');
		$dictionaries = Dictionary::fetchAllDictionaries($this->db, 'synergiestech');

		if (! function_exists("ldap_connect"))
		{
			$this->error='LDAPFunctionsNotAvailableOnPHP';
			dol_syslog(get_class($this)."::connect_bind ".$this->error, LOG_WARNING);
			return -1;
		}

		foreach ($dictionaries as $dictionary) {
			if ($dictionary->createTables() < 0) {
				setEventMessage('Error create dictionary table: ' . $dictionary->errorsToString(), 'errors');
			}
		}

		//We add shared element for multicompany
        $arrayOfElement = json_decode($conf->global->MULTICOMPANY_EXTERNAL_MODULES_SHARING, true);
        foreach ($arrayOfElement as $index => $param) {
            if (is_array($param["sharingelements"]) && array_key_exists("contract", $param["sharingelements"])) {
                unset($arrayOfElement[$index]);
                break;
            }
        }
        $arrayOfElement[] =
            array("sharingelements" =>
            array('contract' => array(
                'type' => 'object',
                'icon' => 'file-pdf-o',
                'enable' => '! empty($conf->contrat->enabled)',
                'active' => true
            ),));
        dolibarr_set_const($this->db, "MULTICOMPANY_EXTERNAL_MODULES_SHARING", json_encode($arrayOfElement), 'chaine', 0, '', 0);


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
		global $conf;
        $arrayOfElement = json_decode($conf->global->MULTICOMPANY_EXTERNAL_MODULES_SHARING, true);
        foreach ($arrayOfElement as $index => $param) {
            if (is_array($param["sharingelements"]) && array_key_exists("contract", $param["sharingelements"])) {
                unset($arrayOfElement[$index]);
                break;
            }
        }

        dolibarr_set_const($this->db, "MULTICOMPANY_EXTERNAL_MODULES_SHARING", json_encode($arrayOfElement), 'chaine', 0, '', 0);

		return $this->_remove($sql, $options);
	}
}
