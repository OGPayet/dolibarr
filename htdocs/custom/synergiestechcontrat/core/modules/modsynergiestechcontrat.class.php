<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C)  SuperAdmin
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
 * 	\defgroup   synergiestechcontrat     Module synergiestechcontrat
 *  \brief      synergiestechcontrat module descriptor.
 *
 *  \file       htdocs/synergiestechcontrat/core/modules/modsynergiestechcontrat.class.php
 *  \ingroup    synergiestechcontrat
 *  \brief      Description and activation file for module synergiestechcontrat
 */

global $conf;
if (isset($conf->framework) && $conf->framework->enabled) {
    dol_include_once('/framework/class/dolmodexts.class.php');
    global $langs;
    $langs->load('framework');
    class_alias('DolModExts', 'DolibarrModulessynergiestechcontrat');
} else class_alias('DolibarrModules', 'DolibarrModulessynergiestechcontrat');


// The class name should start with a lower case mod for Dolibarr to pick it up
// so we ignore the Squiz.Classes.ValidClassName.NotCamelCaps rule.
// @codingStandardsIgnoreStart
/**
 *  Description and activation class for module synergiestechcontrat
 */
class modsynergiestechcontrat extends DolibarrModulessynergiestechcontrat
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

        $this->db = $db;

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 12590;		// TODO Go on page https://wiki.dolibarr.org/index.php/List_of_modules_id to reserve id number for your module
		// Module label (no space allowed), used if translation string 'ModulesynergiestechcontratName' not found (MyModue is name of module).
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = preg_replace('/^mod/i','',get_class($this)).'Desc';

		if (isset($conf->framework) && $conf->framework->enabled) {
			$this->GetFileBuild();
			$this->loadOscssConf();
			// Boites
			$this->loadbox('/synergiestechcontrat/core/boxes/', '\CORE\SYNERGIESTECHCONTRAT\\');
		}


		// Used only if file README.md and README-LL.md not found.
		$this->descriptionlong = "synergiestechcontratDescription (Long)";


		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		$this->picto='synergies@synergiestechcontrat';

		// Defined all module parts (triggers, login, substitutions, menus, css, etc...)
		// for default path (eg: /synergiestechcontrat/core/xxxxx) (0=disable, 1=enable)
		// for specific path of parts (eg: /synergiestechcontrat/core/modules/barcode)
		// for specific css file (eg: /synergiestechcontrat/css/synergiestechcontrat.css.php)
		$this->module_parts = array(
									'triggers' => 1,                                 	// Set this to 1 if module has its own trigger directory (core/triggers)
									'login' => 0,                                    	// Set this to 1 if module has its own login method directory (core/login)
									'substitutions' => 1,                            	// Set this to 1 if module has its own substitution function file (core/substitutions)
									'menus' => 1,                                    	// Set this to 1 if module has its own menus handler directory (core/menus)
									'theme' => 0,                                    	// Set this to 1 if module has its own theme directory (theme)
									'tpl' => 0,                                      	// Set this to 1 if module overwrite template dir (core/tpl)
									'barcode' => 0,                                  	// Set this to 1 if module has its own barcode directory (core/modules/barcode)
									'models' => 0,                                   	// Set this to 1 if module has its own models directory (core/modules/xxx)
									//'css' => array(),	// Set this to relative path of css file if module has its own css file
									//'js' => array(),          // Set this to relative path of js file if module must load a js on all pages
									'hooks' => array('odtgeneration','all') 	// Set here all hooks context managed by module. You can also set hook context 'all'




						/**
							@remark Specifical section of framework compatibilitie view Oscss-Shop company
						*/
							// default 0, 1 for active ; Construst in admin section the panel for manage extra fields  fo this object ; the menu of config section is auto adjusted
						 , 'useextrafields' => 	0
						 // default 0, 1 for active ; Construst in admin section the panel for manage extra fields  fo this object ; the menu of config section is auto adjusted
						 , 'useextrafieldsline' => 0

						 // Actiavte segment of odt , complement of models param
						 // this section create In cst_table NAme of SAMPLE_ADDON_PDF
						, 'genericmodels' => array(
									'internal' =>array(
											'type'=> array('pdf', 'odt')
										)
// 								, 'external' =>array(
// 											'taskin'=>array(
// 													'type'=> array('pdf', 'odt')
// 													, 'perms' =>'!empty($conf->global->QUALITYREPORT_USE_TASKIN)'
// 												)
// 								)
							)
							// Activate module for generate ref in object
							// this section create In cst_table NAme of SAMPLE_ADDON
						, 'numberingrule' => array(
									'internal' =>array(
										'default' =>'mod_qualityreport_simple'
									)
// 								, 'external' =>array(
// 											'taskin'=>array(
// 													'default' =>'mod_qualityreport_simple'
// 													, 'perms' =>'!empty($conf->global->QUALITYREPORT_USE_TASKIN)'
// 												)
// 								)
							)

						, 'autotabs' => 1


								);

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/synergiestechcontrat/temp","/synergiestechcontrat/subdir");
		$this->dirs = array();


		// Dependencies
		$this->hidden = false;			// A condition to hide module

		$this->warnings_activation = array();                     // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		$this->warnings_activation_ext = array();                 // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','ES'='textes'...)



		// Array to add new pages in new tabs
		// Example: $this->tabs = array('objecttype:+tabname1:Title1:mylangfile@synergiestechcontrat:$user->rights->synergiestechcontrat->read:/synergiestechcontrat/mynewtab1.php?id=__ID__',  					// To add a new tab identified by code tabname1
        //                              'objecttype:+tabname2:SUBSTITUTION_Title2:mylangfile@synergiestechcontrat:$user->rights->othermodule->read:/synergiestechcontrat/mynewtab2.php?id=__ID__',  	// To add another new tab identified by code tabname2. Label will be result of calling all substitution functions on 'Title2' key.
        //                              'objecttype:-tabname:NU:conditiontoremove');                                                     										// To remove an existing tab identified by code tabname
        // Can also be:	$this->tabs = array('data'=>'...', 'entity'=>0);
        //
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
			'contract:+invoice:Factures:@synergiestechcontrat:/custom/synergiestechcontrat/tabs/invoice.php?id=__ID__',
			'contract:+terminate:Résiliation:@synergiestechcontrat:/custom/synergiestechcontrat/tabs/terminate.php?id=__ID__',
		);

//		if (! isset($conf->synergiestechcontrat) || ! isset($conf->synergiestechcontrat->enabled))
//        {
//        	$conf->synergiestechcontrat=new stdClass();
//        	$conf->synergiestechcontrat->enabled=0;
//        }

        // Dictionaries
		$this->dictionaries=array();

        $this->dictionaries=array(
            'langs'=>'synergiestechcontrat@synergiestechcontrat',
            'tabname'=>array("&nbsp;",MAIN_DB_PREFIX."c_indice_insee",MAIN_DB_PREFIX."c_indice_syntec"),		// List of tables we want to see into dictonnary editor
            'tablib'=>array("","Indice INSEE","Indice SYNTEC"),													// Label of tables
            'tabsql'=>array("",'SELECT f.rowid as rowid, f.active, f.year_indice as \'Year\', f.month_indice as \'Month\', f.indice as \'Indice\'FROM '.MAIN_DB_PREFIX.'c_indice_insee as f',
                'SELECT f.rowid as rowid, f.active, f.year_indice as \'Year\', f.month_indice as \'Month\', f.indice as \'Indice\'FROM '.MAIN_DB_PREFIX.'c_indice_syntec as f'
                ),	// Request to select fields
            'tabsqlsort'=>array("","year_indice DESC, month_indice DESC","year_indice DESC, month_indice DESC"),																					// Sort order
            'tabfield'=>array("","Year,Month,Indice","Year,Month,Indice"),															// List of fields (result of select to show dictionary)
            'tabfieldvalue'=>array("","Year,Month,Indice","Year,Month,Indice"),													// List of fields (list of fields to edit a record)
            'tabfieldinsert'=>array("","year_indice,month_indice,indice","year_indice,month_indice,indice"),													// List of fields (list of fields for insert)
            'tabrowid'=>array("","rowid","rowid"),																								// Name of columns with primary key (try to always name it 'rowid')
            'tabcond'=>array(1,1,1)												// Condition to show each dictionary
        );


        // Boxes/Widgets
		// Add here list of php file(s) stored in synergiestechcontrat/core/boxes that contains class to show a widget.
        $this->boxes = array(
//        	0=>array(
//        	'file'=>'synergiestechcontratwidget1.php@synergiestechcontrat',
//        	'note'=>'Widget provided by synergiestechcontrat',
//        	'enabledbydefaulton'=>'Home'
//        	),
		//1=>array('file'=>'synergiestechcontratwidget2.php@synergiestechcontrat','note'=>'Widget provided by synergiestechcontrat'),
		//2=>array('file'=>'synergiestechcontratwidget3.php@synergiestechcontrat','note'=>'Widget provided by synergiestechcontrat')
        );


		// Cronjobs (List of cron jobs entries to add when module is enabled)
		$this->cronjobs = array(
            0=>array('label'=>'TerminateContracts', 'jobtype'=>'method', 'class'=>'/custom/synergiestechcontrat/class/invoicescontracttools.class.php', 'objectname'=>'InvoicesContractTools', 'method'=>'terminateContracts', 'parameters'=>'', 'comment'=>'Terminate contracts periodically', 'frequency'=>1, 'unitfrequency'=>3600*24),
            1=>array('label'=>'ActivateContracts', 'jobtype'=>'method', 'class'=>'/custom/synergiestechcontrat/class/invoicescontracttools.class.php', 'objectname'=>'InvoicesContractTools', 'method'=>'activateContracts', 'parameters'=>'', 'comment'=>'Activate contracts periodically', 'frequency'=>1, 'unitfrequency'=>3600*24),
//			0=>array('label'=>'MyJob label', 'jobtype'=>'method', 'class'=>'/synergiestechcontrat/class/synergiestechcontratmyjob.class.php', 'objectname'=>'synergiestechcontratMyJob', 'method'=>'myMethod', 'parameters'=>'', 'comment'=>'Comment', 'frequency'=>2, 'unitfrequency'=>3600, 'status'=>0, 'test'=>true)
		);
		// Example: $this->cronjobs=array(0=>array('label'=>'My label', 'jobtype'=>'method', 'class'=>'/dir/class/file.class.php', 'objectname'=>'MyClass', 'method'=>'myMethod', 'parameters'=>'', 'comment'=>'Comment', 'frequency'=>2, 'unitfrequency'=>3600, 'status'=>0, 'test'=>true),
		//                                1=>array('label'=>'My label', 'jobtype'=>'command', 'command'=>'', 'parameters'=>'', 'comment'=>'Comment', 'frequency'=>1, 'unitfrequency'=>3600*24, 'status'=>0, 'test'=>true)
		// );


		// Permissions
		$this->rights = array();		// Permission array used by this module
		$this->rights_class = "contrat";

		$r=0;
		$this->rights[$r][0] = $this->numero + $r;	// Permission id (must not be already used)
		$this->rights[$r][1] = 'Résilier un contrat';	// Permission label
		$this->rights[$r][3] = 0; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'terminate';				// In php code, permission will be checked by test if ($user->rights->synergiestechcontrat->level1->level2)
//
//		$r++;
//		$this->rights[$r][0] = $this->numero + $r;	// Permission id (must not be already used)
//		$this->rights[$r][1] = 'Create/Update objects of My Module';	// Permission label
//		$this->rights[$r][3] = 1; 					// Permission by default for new user (0/1)
//		$this->rights[$r][4] = 'create';				// In php code, permission will be checked by test if ($user->rights->synergiestechcontrat->level1->level2)
//		$this->rights[$r][5] = '';				    // In php code, permission will be checked by test if ($user->rights->synergiestechcontrat->level1->level2)
//
//		$r++;
//		$this->rights[$r][0] = $this->numero + $r;	// Permission id (must not be already used)
//		$this->rights[$r][1] = 'Delete objects of My Module';	// Permission label
//		$this->rights[$r][3] = 1; 					// Permission by default for new user (0/1)
//		$this->rights[$r][4] = 'delete';				// In php code, permission will be checked by test if ($user->rights->synergiestechcontrat->level1->level2)
//		$this->rights[$r][5] = '';				    // In php code, permission will be checked by test if ($user->rights->synergiestechcontrat->level1->level2)


		// Main menu entries
		$this->menu = array();			// List of menus to add
		$r=0;

		// Add here entries to declare new menus

		// Example to declare a new Top Menu entry and its Left menu entry:
		/* BEGIN MODULEBUILDER TOPMENU */
//		$this->menu[$r++]=array('fk_menu'=>'',			                // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
//								'type'=>'top',			                // This is a Top menu entry
//								'titre'=>'synergiestechcontrat',
//								'mainmenu'=>'synergiestechcontrat',
//								'leftmenu'=>'',
//								'url'=>'/synergiestechcontrat/synergiestechcontratindex.php',
//								'langs'=>'synergiestechcontrat@synergiestechcontrat',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
//								'position'=>1000+$r,
//								'enabled'=>'$conf->synergiestechcontrat->enabled',	// Define condition to show or hide menu entry. Use '$conf->synergiestechcontrat->enabled' if entry must be visible if module is enabled.
//								'perms'=>'1',			                // Use 'perms'=>'$user->rights->synergiestechcontrat->level1->level2' if you want your menu with a permission rules
//								'target'=>'',
//								'user'=>2);				                // 0=Menu for internal users, 1=external users, 2=both

		/* END MODULEBUILDER TOPMENU */

		// Example to declare a Left Menu entry into an existing Top menu entry:
		/* BEGIN MODULEBUILDER LEFTMENU MYOBJECT
		$this->menu[$r++]=array(	'fk_menu'=>'fk_mainmenu=synergiestechcontrat',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
								'type'=>'left',			                // This is a Left menu entry
								'titre'=>'List MyObject',
								'mainmenu'=>'synergiestechcontrat',
								'leftmenu'=>'synergiestechcontrat',
								'url'=>'/synergiestechcontrat/myobject_list.php',
								'langs'=>'synergiestechcontrat@synergiestechcontrat',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
								'position'=>1000+$r,
								'enabled'=>'$conf->synergiestechcontrat->enabled',  // Define condition to show or hide menu entry. Use '$conf->synergiestechcontrat->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
								'perms'=>'1',			                // Use 'perms'=>'$user->rights->synergiestechcontrat->level1->level2' if you want your menu with a permission rules
								'target'=>'',
								'user'=>2);				                // 0=Menu for internal users, 1=external users, 2=both*/

        $this->menu[$r] = array(
            'fk_menu' => 'fk_mainmenu=accountancy,fk_leftmenu=customers_bills',
            'type' => 'left',
            'titre' => 'STCBillingContracts',
            'mainmenu' => 'accountancy',
            'leftmenu' => 'invoicescontractlist',
            'url' => '/synergiestechcontrat/invoicescontractlist.php',
            'langs' => 'synergiestechcontrat@synergiestechcontrat',
            'position' => 1000 + $r,
            'enabled' => '$conf->synergiestechcontrat->enabled',
            'perms' => '$user->rights->contrat->lire',
            'target' => '',
            'user' => 0
        );
        $r++;
		/* END MODULEBUILDER LEFTMENU MYOBJECT */


		// Exports
		$r=1;

		// Example:
		/* BEGIN MODULEBUILDER EXPORT MYOBJECT
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='synergiestechcontrat';	                         // Translation key (used only if key ExportDataset_xxx_z not found)
        $this->export_enabled[$r]='1';                               // Condition to show export in list (ie: '$user->id==3'). Set to 1 to always show when module is enabled.
        $this->export_icon[$r]='generic:synergiestechcontrat';					 // Put here code of icon then string for translation key of module name
		//$this->export_permission[$r]=array(array("synergiestechcontrat","level1","level2"));
        $this->export_fields_array[$r]=array('t.rowid'=>"Id",'t.ref'=>'Ref','t.label'=>'Label','t.datec'=>"DateCreation",'t.tms'=>"DateUpdate");
		$this->export_TypeFields_array[$r]=array('t.rowid'=>'Numeric', 't.ref'=>'Text', 't.label'=>'Label', 't.datec'=>"Date", 't.tms'=>"Date");
		// $this->export_entities_array[$r]=array('t.rowid'=>"company",'s.nom'=>'company','s.address'=>'company','s.zip'=>'company','s.town'=>'company','s.fk_pays'=>'company','s.phone'=>'company','s.siren'=>'company','s.siret'=>'company','s.ape'=>'company','s.idprof4'=>'company','s.code_compta'=>'company','s.code_compta_fournisseur'=>'company','f.rowid'=>"invoice",'f.facnumber'=>"invoice",'f.datec'=>"invoice",'f.datef'=>"invoice",'f.total'=>"invoice",'f.total_ttc'=>"invoice",'f.tva'=>"invoice",'f.paye'=>"invoice",'f.fk_statut'=>'invoice','f.note'=>"invoice",'fd.rowid'=>'invoice_line','fd.description'=>"invoice_line",'fd.price'=>"invoice_line",'fd.total_ht'=>"invoice_line",'fd.total_tva'=>"invoice_line",'fd.total_ttc'=>"invoice_line",'fd.tva_tx'=>"invoice_line",'fd.qty'=>"invoice_line",'fd.date_start'=>"invoice_line",'fd.date_end'=>"invoice_line",'fd.fk_product'=>'product','p.ref'=>'product');
		// $this->export_dependencies_array[$r]=array('invoice_line'=>'fd.rowid','product'=>'fd.rowid');   // To add unique key if we ask a field of a child to avoid the DISTINCT to discard them
		// $this->export_sql_start[$r]='SELECT DISTINCT ';
		// $this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'myobject as t';
		// $this->export_sql_order[$r] .=' ORDER BY t.ref';
		// $r++;
		END MODULEBUILDER EXPORT MYOBJECT */




//        if (!isset($conf->synergiestechcontrat) || !isset($conf->synergiestechcontrat->enabled)) {
//            $conf->synergiestechcontrat          = new stdClass();
//            $conf->synergiestechcontrat->enabled = 0;
//        } else {
//            $conf->synergiestechcontrat->enabled = 1;
//        }

		/**
			@remarks End loaded config ans auto-config construct
		*/
		if (isset($conf->framework) && $conf->framework->enabled)
			$this->EndLoader();
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
		global $conf, $langs, $db;
		$sql = array();

		$this->_load_tables('/synergiestechcontrat/sql/');

		// Create extrafields
        include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
        $extrafields = new ExtraFields($this->db);
        // Invoice
        $result=$extrafields->addExtraField('datedeb', 'Début de la période de facturation', 'date', 0,  '', 'facture',   0, 0, '', '', 1, '', 0, 0, '');
        $result=$extrafields->addExtraField('datefin', 'Fin de la période de facturation', 'date', 0,  '', 'facture',   0, 0, '', '', 1, '', 0, 0, '');
        $result=$extrafields->addExtraField('oldinvoice', "Facturation d'une période passé possible ?", 'boolean', 0,  '', 'contrat',   0, 0, '0', '', 1, '', 0, 0, '');
        $result=$extrafields->addExtraField('targetdate', "Date souhaitée de résiliation", 'date', 10,  '', 'contrat',   0, 0, '', '', 1, '', 0, 0, '');
        $result=$extrafields->addExtraField('realdate', "Date effective de résiliation", 'date', 11,  '', 'contrat',   0, 0, '', '', 1, '', 0, 0, '');

		//Myfield
		$target_array = array('datedeb','datefin');

		foreach($target_array as $target) {
			$sql1 = 'SELECT *';
			$sql1.= ' FROM '.MAIN_DB_PREFIX.'myfield';
			$sql1.= " WHERE label = '".$target."'";

			$result = $db->query($sql1);
			if ($result) {
				$num = $db->num_rows($result);
				if($num == 0) {
					$db->begin();

					// Insertion dans base de la ligne
					$sql2 = 'INSERT INTO '.MAIN_DB_PREFIX.'myfield';
					$sql2.= ' (label,context,author,active,typefield,movefield,formatfield,color,replacement,initvalue)';
					$sql2.= " VALUES ('".$target."','','',1,0,0,'','','','')";

					$resql=$db->query($sql2);
					if ($resql)
					{
						$db->commit();
					}
					else
					{
						$db->rollback();
					}
				}
			}
		}

		//Creation de la vue SQL
        $sql_view = "DROP VIEW `llx_view_c_indice`;";
        $resql=$db->query($sql_view);
        if ($resql) { $db->commit(); } else { $db->rollback(); }
		$sql_view = "CREATE VIEW `llx_view_c_indice` AS
				SELECT
						concat(`i`.`rowid`, '_Insee') AS `rowid`,
						`i`.`year_indice` AS `year_indice`,
						`i`.`month_indice` AS `month_indice`,
						`i`.`indice` AS `indice`,
					CONCAT(`i`.`year_indice`,'/',`i`.`month_indice`,' Insee') AS `label`,
						'Insee' AS `filter`,
						'3' AS `filter_id`
				FROM
						`llx_c_indice_insee` `i`
				WHERE
						(`i`.`active` = 1)
				UNION
				SELECT
						concat(`s`.`rowid`, '_Syntec') AS `rowid`,
						`s`.`year_indice` AS `year_indice`,
						`s`.`month_indice` AS `month_indice`,
						`s`.`indice` AS `indice`,
					CONCAT(`s`.`year_indice`,'/',`s`.`month_indice`,' Syntec') AS `label`,
						'Syntec' AS `filter`,
						'2' AS `filter_id`
				FROM
						`llx_c_indice_syntec` `s`
				WHERE
						(`s`.`active` = 1);";
		$resql=$db->query($sql_view);
		if ($resql) {
			$db->commit();
		} else {
			$db->rollback();
		}


		//ODT template
		$src= dol_buildpath('/synergiestechcontrat/doctemplates/invoices/template_invoice.odt');
		$dirodt=DOL_DATA_ROOT.'/doctemplates/invoices';
		$dest=$dirodt.'/template_invoice.odt';

		if (file_exists($src) && ! file_exists($dest))
		{
			require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
			dol_mkdir($dirodt);
			$result=dol_copy($src,$dest,0,0);
			if ($result < 0)
			{
				$langs->load("errors");
				$this->error=$langs->trans('ErrorFailToCopyFile',$src,$dest);
				return 0;
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
