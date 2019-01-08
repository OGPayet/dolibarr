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
 * 	\defgroup	query	query module
 * 	\brief		query module descriptor.
 * 	\file		core/modules/modquery.class.php
 * 	\ingroup	query
 * 	\brief		Description and activation file for module query
 */
include_once DOL_DOCUMENT_ROOT . "/core/modules/DolibarrModules.class.php";

/**
 * Description and activation class for module query
 */
class modquery extends DolibarrModules
{

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
        // Use a free id here
        // (See in Home -> System information -> Dolibarr for list of used modules id).
        $this->numero = 104778; // 104000 to 104999 for ATM CONSULTING
        // Key text used to identify module (for permissions, menus, etc...)
        $this->rights_class = 'query';

        // Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
        // It is used to group modules in module setup page
        $this->family = "ATM";
        // Module label (no space allowed)
        // used if translation string 'ModuleXXXName' not found
        // (where XXX is value of numeric property 'numero' of module)
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        // Module description
        // used if translation string 'ModuleXXXDesc' not found
        // (where XXX is value of numeric property 'numero' of module)
        $this->description = "Description of module query";
        // Possible values for version are: 'development', 'experimental' or version
        $this->version = '1.5.5';
        // Key used in llx_const table to save module status enabled/disabled
        // (where MYMODULE is value of property name of module in uppercase)
        $this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
        // Where to store the module in setup page
        // (0=common,1=interface,2=others,3=very specific)
        $this->special = 0;
        // Name of image file used for this module.
        // If file is in theme/yourtheme/img directory under name object_pictovalue.png
        // use this->picto='pictovalue'
        // If file is in module/img directory under name object_pictovalue.png
        // use this->picto='pictovalue@module'
        $this->picto = 'query@query'; // mypicto@query
        // Defined all module parts (triggers, login, substitutions, menus, css, etc...)
        // for default path (eg: /query/core/xxxxx) (0=disable, 1=enable)
        // for specific path of parts (eg: /query/core/modules/barcode)
        // for specific css file (eg: /query/css/query.css.php)
        $this->module_parts = array(
		'triggers'=>1
			,'hooks'=>array('index','projectcard','productcard','thirdpartycard','usercard')

        );

        // Data directories to create when module is enabled.
        // Example: this->dirs = array("/query/temp");
        $this->dirs = array();

        // Config pages. Put here list of php pages
        // stored into query/admin directory, used to setup module.
        $this->config_page_url = array("query_setup.php@query");

        // Dependencies
        // List of modules id that must be enabled if this module is enabled
        $this->depends = array('modExport');
        // List of modules id to disable if this one is disabled
        $this->requiredby = array();
        // Minimum version of PHP required by module
        $this->phpmin = array(5, 3);
        // Minimum version of Dolibarr required by module
        $this->need_dolibarr_version = array(3, 4);
        $this->langfiles = array("query@query"); // langfiles@query
        // Constants
        // List of particular constants to add when module is enabled
        // (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
        // Example:
        $this->const = array(
		0=>array(
			'QUERY_HOME_SELECTOR',
			'chaine',
			'1',
			'add box on home page',
			1
		),
            //	1=>array(
            //		'MYMODULE_MYNEWCONST2',
            //		'chaine',
            //		'myvalue',
            //		'This is another constant to add',
            //		0
            //	)
        );

        // Array to add new pages in new tabs
        // Example:
        $this->tabs = array(
            //	// To add a new tab identified by code tabname1
            //	'objecttype:+tabname1:Title1:langfile@query:$user->rights->query->read:/query/mynewtab1.php?id=__ID__',
            //	// To add another new tab identified by code tabname2
            //	'objecttype:+tabname2:Title2:langfile@query:$user->rights->othermodule->read:/query/mynewtab2.php?id=__ID__',
            //	// To remove an existing tab identified by code tabname
            //	'objecttype:-tabname'
        );
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
        if (! isset($conf->query->enabled)) {
            $conf->query=new stdClass();
            $conf->query->enabled = 0;
        }
        $this->dictionnaries = array();
        /* Example:
          // This is to avoid warnings
          if (! isset($conf->query->enabled)) $conf->query->enabled=0;
          $this->dictionnaries=array(
          'langs'=>'query@query',
          // List of tables we want to see into dictonnary editor
          'tabname'=>array(
          MAIN_DB_PREFIX."table1",
          MAIN_DB_PREFIX."table2",
          MAIN_DB_PREFIX."table3"
          ),
          // Label of tables
          'tablib'=>array("Table1","Table2","Table3"),
          // Request to select fields
          'tabsql'=>array(
          'SELECT f.rowid as rowid, f.code, f.label, f.active'
          . ' FROM ' . MAIN_DB_PREFIX . 'table1 as f',
          'SELECT f.rowid as rowid, f.code, f.label, f.active'
          . ' FROM ' . MAIN_DB_PREFIX . 'table2 as f',
          'SELECT f.rowid as rowid, f.code, f.label, f.active'
          . ' FROM ' . MAIN_DB_PREFIX . 'table3 as f'
          ),
          // Sort order
          'tabsqlsort'=>array("label ASC","label ASC","label ASC"),
          // List of fields (result of select to show dictionnary)
          'tabfield'=>array("code,label","code,label","code,label"),
          // List of fields (list of fields to edit a record)
          'tabfieldvalue'=>array("code,label","code,label","code,label"),
          // List of fields (list of fields for insert)
          'tabfieldinsert'=>array("code,label","code,label","code,label"),
          // Name of columns with primary key (try to always name it 'rowid')
          'tabrowid'=>array("rowid","rowid","rowid"),
          // Condition to show each dictionnary
          'tabcond'=>array(
          $conf->query->enabled,
          $conf->query->enabled,
          $conf->query->enabled
          )
          );
         */

        // Boxes
        // Add here list of php file(s) stored in core/boxes that contains class to show a box.
        $this->boxes = array(
			/*'query_box.php'*/
		); // Boxes list
        $r = 0;
        // Example:

        $this->boxes[$r][1] = "query_box@query";
        $r ++;
        /*
          $this->boxes[$r][1] = "myboxb.php";
          $r++;
         */

        // Permissions
        $this->rights = array(); // Permission array used by this module
		$r=0;

		$this->rights[$r][0] = $this->numero+$r; 				// Permission id (must not be already used)
		$this->rights[$r][1] = 'Exécuter une requête';	// Permission label
		$this->rights[$r][3] = 0; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'all';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$this->rights[$r][5] = 'read';				// In php code, permission will be_o checked by test if ($user->rights->permkey->level1->level2)
		$r++;

		$this->rights[$r][0] = $this->numero+$r; 				// Permission id (must not be already used)
		$this->rights[$r][1] = 'Créer une requête';	// Permission label
		$this->rights[$r][3] = 0; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'all';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$this->rights[$r][5] = 'create';			// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;

		$this->rights[$r][0] = $this->numero+$r; 				// Permission id (must not be already used)
		$this->rights[$r][1] = 'Ecrire dans la base de donnée';	// Permission label
		$this->rights[$r][3] = 0; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'bdd';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$this->rights[$r][5] = 'write';			// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;

		$this->rights[$r][0] = $this->numero+$r; 				// Permission id (must not be already used)
		$this->rights[$r][1] = 'Créer un panneau de contrôle';	// Permission label
		$this->rights[$r][3] = 0; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'dashboard';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$this->rights[$r][5] = 'create';			// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;

		$this->rights[$r][0] = $this->numero+$r; 				// Permission id (must not be already used)
		$this->rights[$r][1] = 'Voir les panneaux intégrés aux fiches (projet,..)';	// Permission label
		$this->rights[$r][3] = 0; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'dashboard';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$this->rights[$r][5] = 'viewin';			// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;

		$this->rights[$r][0] = $this->numero+$r; 				// Permission id (must not be already used)
		$this->rights[$r][1] = 'Faire des requêtes en expert';	// Permission label
		$this->rights[$r][3] = 0; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'all';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$this->rights[$r][5] = 'expert';			// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;

		$this->rights[$r][0] = $this->numero+$r; 				// Permission id (must not be already used)
		$this->rights[$r][1] = 'ViewDashboard';	// Permission label
		$this->rights[$r][3] = 0; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'dashboard';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$this->rights[$r][5] = 'read';			// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;

		$this->rights[$r][0] = $this->numero+$r; 				// Permission id (must not be already used)
		$this->rights[$r][1] = 'ViewAllDashboard';	// Permission label
		$this->rights[$r][3] = 0; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'dashboard';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$this->rights[$r][5] = 'readall';			// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;

		$this->rights[$r][0] = $this->numero+$r; 				// Permission id (must not be already used)
		$this->rights[$r][1] = 'UseOtherDB';	// Permission label
		$this->rights[$r][3] = 0; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'bdd';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$this->rights[$r][5] = 'use_other_db';			// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;



        // Add here list of permission defined by
        // an id, a label, a boolean and two constant strings.
        // Example:
        //// Permission id (must not be already used)
        //$this->rights[$r][0] = 2000;
        //// Permission label
        //$this->rights[$r][1] = 'Permision label';
        //// Permission by default for new user (0/1)
        //$this->rights[$r][3] = 1;
        //// In php code, permission will be checked by test
        //// if ($user->rights->permkey->level1->level2)
        //$this->rights[$r][4] = 'level1';
        //// In php code, permission will be checked by test
        //// if ($user->rights->permkey->level1->level2)
        //$this->rights[$r][5] = 'level2';
        //$r++;
        // Main menu entries
        $this->menus = array(); // List of menus to add
        $r = 0;

        // Add here entries to declare new menus
        //
        // Example to declare a new Top Menu entry and its Left menu entry:
        $this->menu[$r]=array(
		'fk_menu'=>'fk_mainmenu=tools',
		'type'=>'left',
		'titre'=>'Query',
		'mainmenu'=>'tools',
		'leftmenu'=>'query',
		'url'=>'/query/query.php',
		'position'=>100,
		'perms'=>'$user->rights->query->all->read',
		'target'=>'',
		'user'=>2
        );

        $r++;

        $this->menu[$r]=array(
		'fk_menu'=>'fk_mainmenu=tools,fk_leftmenu=query',
		'type'=>'left',
		'titre'=>'Créer une requête',
		'mainmenu'=>'tools',
		'leftmenu'=>'query_add',
		'url'=>'/query/query.php?action=add',
		'position'=>102,
		'perms'=>'$user->rights->query->all->create',
		'target'=>'',
		'user'=>2
        );

        $r++;

		$this->menu[$r]=array(
		'fk_menu'=>'fk_mainmenu=tools,fk_leftmenu=query',
		'type'=>'left',
		'titre'=>'Liste des requêtes',
		'mainmenu'=>'tools',
		'leftmenu'=>'query_list',
		'url'=>'/query/query.php',
		'langs'=>'query@query',
		'position'=>101,
		'perms'=>'$user->rights->query->all->read',
		'target'=>'',
		'user'=>2
        );

		$r++;

        $this->menu[$r]=array(
		'fk_menu'=>'fk_mainmenu=tools,fk_leftmenu=query',
		'type'=>'left',
		'titre'=>'Liste des panneaux',
		'mainmenu'=>'tools',
		'leftmenu'=>'dash_list',
		'url'=>'/query/dashboard.php',
		'position'=>201,
		'perms'=>'$user->rights->query->dashboard->read',
		'target'=>'',
		'user'=>2
        );
		$r++;

        $this->menu[$r]=array(
		'fk_menu'=>'fk_mainmenu=tools,fk_leftmenu=query',
		'type'=>'left',
		'titre'=>'Créer un panneau',
		'mainmenu'=>'tools',
		'leftmenu'=>'dash_add',
		'url'=>'/query/dashboard.php?action=add',
		'langs'=>'query@query',
		'position'=>202,
		'perms'=>'$user->rights->query->dashboard->create',
		'target'=>'',
		'user'=>2
        );

        $r++;


        $this->menu[$r]=array(
		'fk_menu'=>'fk_mainmenu=tools,fk_leftmenu=query',
		'type'=>'left',
		'titre'=>'Liste des entrées menu',
		'mainmenu'=>'tools',
		'leftmenu'=>'menu_query_list',
		'url'=>'/query/menu.php',
		'perms'=>'$user->rights->query->all->create',
		'position'=>311,
		'target'=>'',
		'user'=>2
        );
		$r++;

        $this->menu[$r]=array(
		'fk_menu'=>'fk_mainmenu=tools,fk_leftmenu=query',
		'type'=>'left',
		'titre'=>'Créer une entrée menu',
		'mainmenu'=>'tools',
		'leftmenu'=>'menu_query_add',
		'url'=>'/query/menu.php?action=add',
		'langs'=>'query@query',
		'position'=>312,
		'perms'=>'$user->rights->query->all->create',
		'target'=>'',
		'user'=>2
        );

        $r++;


        $this->menu[$r]=array(
		'fk_menu'=>'fk_mainmenu=tools,fk_leftmenu=query',
		'type'=>'left',
		'titre'=>'DatabaseAccessAdminer',
		'mainmenu'=>'tools',
		'leftmenu'=>'bdd_access',
		'url'=>'/query/bdd.php',
		'position'=>301,
		'enabled'=>'',
		'langs'=>'query@query',
		'perms'=>'$user->rights->query->bdd->write',
		'target'=>'',
		'user'=>2
        );

        $r++;

        $this->menu[$r]=array(
		'fk_menu'=>'fk_mainmenu=tools,fk_leftmenu=query',
		'type'=>'left',
		'titre'=>'AddDataBaseAccess',
		'mainmenu'=>'tools',
		'leftmenu'=>'bdd_access',
		'url'=>'/query/bdd.php?action=new',
		'position'=>302,
		'enabled'=>'',
		'langs'=>'query@query',
		'perms'=>'$user->rights->query->bdd->use_other_db',
		'target'=>'',
		'user'=>2
        );

        $r++;


        //$this->menu[$r]=array(
        //	// Use r=value where r is index key used for the parent menu entry
        //	// (higher parent must be a top menu entry)
        //	'fk_menu'=>'r=0',
        //	// This is a Left menu entry
        //	'type'=>'left',
        //	'titre'=>'query left menu',
        //	'mainmenu'=>'query',
        //	'leftmenu'=>'query',
        //	'url'=>'/query/pagelevel1.php',
        //	// Lang file to use (without .lang) by module.
        //	// File must be in langs/code_CODE/ directory.
        //	'langs'=>'mylangfile',
        //	'position'=>100,
        //	// Define condition to show or hide menu entry.
        //	// Use '$conf->query->enabled' if entry must be visible if module is enabled.
        //	'enabled'=>'$conf->query->enabled',
        //	// Use 'perms'=>'$user->rights->query->level1->level2'
        //	// if you want your menu with a permission rules
        //	'perms'=>'1',
        //	'target'=>'',
        //	// 0=Menu for internal users, 1=external users, 2=both
        //	'user'=>2
        //);
        //$r++;
        //
        // Example to declare a Left Menu entry into an existing Top menu entry:
        //$this->menu[$r]=array(
        //	// Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy'
        //	'fk_menu'=>'fk_mainmenu=mainmenucode',
        //	// This is a Left menu entry
        //	'type'=>'left',
        //	'titre'=>'query left menu',
        //	'mainmenu'=>'mainmenucode',
        //	'leftmenu'=>'query',
        //	'url'=>'/query/pagelevel2.php',
        //	// Lang file to use (without .lang) by module.
        //	// File must be in langs/code_CODE/ directory.
        //	'langs'=>'mylangfile',
        //	'position'=>100,
        //	// Define condition to show or hide menu entry.
        //	// Use '$conf->query->enabled' if entry must be visible if module is enabled.
        //	// Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
        //	'enabled'=>'$conf->query->enabled',
        //	// Use 'perms'=>'$user->rights->query->level1->level2'
        //	// if you want your menu with a permission rules
        //	'perms'=>'1',
        //	'target'=>'',
        //	// 0=Menu for internal users, 1=external users, 2=both
        //	'user'=>2
        //);
        //$r++;
        // Exports
        $r = 1;

        // Example:
        //$this->export_code[$r]=$this->rights_class.'_'.$r;
        //// Translation key (used only if key ExportDataset_xxx_z not found)
        //$this->export_label[$r]='CustomersInvoicesAndInvoiceLines';
        //// Condition to show export in list (ie: '$user->id==3').
        //// Set to 1 to always show when module is enabled.
        //$this->export_enabled[$r]='1';
        //$this->export_permission[$r]=array(array("facture","facture","export"));
        //$this->export_fields_array[$r]=array(
        //	's.rowid'=>"IdCompany",
        //	's.nom'=>'CompanyName',
        //	's.address'=>'Address',
        //	's.cp'=>'Zip',
        //	's.ville'=>'Town',
        //	's.fk_pays'=>'Country',
        //	's.tel'=>'Phone',
        //	's.siren'=>'ProfId1',
        //	's.siret'=>'ProfId2',
        //	's.ape'=>'ProfId3',
        //	's.idprof4'=>'ProfId4',
        //	's.code_compta'=>'CustomerAccountancyCode',
        //	's.code_compta_fournisseur'=>'SupplierAccountancyCode',
        //	'f.rowid'=>"InvoiceId",
        //	'f.facnumber'=>"InvoiceRef",
        //	'f.datec'=>"InvoiceDateCreation",
        //	'f.datef'=>"DateInvoice",
        //	'f.total'=>"TotalHT",
        //	'f.total_ttc'=>"TotalTTC",
        //	'f.tva'=>"TotalVAT",
        //	'f.paye'=>"InvoicePaid",
        //	'f.fk_statut'=>'InvoiceStatus',
        //	'f.note'=>"InvoiceNote",
        //	'fd.rowid'=>'LineId',
        //	'fd.description'=>"LineDescription",
        //	'fd.price'=>"LineUnitPrice",
        //	'fd.tva_tx'=>"LineVATRate",
        //	'fd.qty'=>"LineQty",
        //	'fd.total_ht'=>"LineTotalHT",
        //	'fd.total_tva'=>"LineTotalTVA",
        //	'fd.total_ttc'=>"LineTotalTTC",
        //	'fd.date_start'=>"DateStart",
        //	'fd.date_end'=>"DateEnd",
        //	'fd.fk_product'=>'ProductId',
        //	'p.ref'=>'ProductRef'
        //);
        //$this->export_entities_array[$r]=array('s.rowid'=>"company",
        //	's.nom'=>'company',
        //	's.address'=>'company',
        //	's.cp'=>'company',
        //	's.ville'=>'company',
        //	's.fk_pays'=>'company',
        //	's.tel'=>'company',
        //	's.siren'=>'company',
        //	's.siret'=>'company',
        //	's.ape'=>'company',
        //	's.idprof4'=>'company',
        //	's.code_compta'=>'company',
        //	's.code_compta_fournisseur'=>'company',
        //	'f.rowid'=>"invoice",
        //	'f.facnumber'=>"invoice",
        //	'f.datec'=>"invoice",
        //	'f.datef'=>"invoice",
        //	'f.total'=>"invoice",
        //	'f.total_ttc'=>"invoice",
        //	'f.tva'=>"invoice",
        //	'f.paye'=>"invoice",
        //	'f.fk_statut'=>'invoice',
        //	'f.note'=>"invoice",
        //	'fd.rowid'=>'invoice_line',
        //	'fd.description'=>"invoice_line",
        //	'fd.price'=>"invoice_line",
        //	'fd.total_ht'=>"invoice_line",
        //	'fd.total_tva'=>"invoice_line",
        //	'fd.total_ttc'=>"invoice_line",
        //	'fd.tva_tx'=>"invoice_line",
        //	'fd.qty'=>"invoice_line",
        //	'fd.date_start'=>"invoice_line",
        //	'fd.date_end'=>"invoice_line",
        //	'fd.fk_product'=>'product',
        //	'p.ref'=>'product'
        //);
        //$this->export_sql_start[$r] = 'SELECT DISTINCT ';
        //$this->export_sql_end[$r] = ' FROM (' . MAIN_DB_PREFIX . 'facture as f, '
        //	. MAIN_DB_PREFIX . 'facturedet as fd, ' . MAIN_DB_PREFIX . 'societe as s)';
        //$this->export_sql_end[$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX
        //	. 'product as p on (fd.fk_product = p.rowid)';
        //$this->export_sql_end[$r] .= ' WHERE f.fk_soc = s.rowid '
        //	. 'AND f.rowid = fd.fk_facture';
        //$r++;
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
        $sql = array();

        $result = $this->loadTables();

	define('INC_FROM_DOLIBARR',true);
        dol_include_once('/query/script/create-maj-base.php');

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

    /**
     * Create tables, keys and data required by module
     * Files llx_table1.sql, llx_table1.key.sql llx_data.sql with create table, create keys
     * and create data commands must be stored in directory /query/sql/
     * This function is called by this->init
     *
     * 	@return		int		<=0 if KO, >0 if OK
     */
    private function loadTables()
    {
        return $this->_load_tables('/query/sql/');
    }
}
