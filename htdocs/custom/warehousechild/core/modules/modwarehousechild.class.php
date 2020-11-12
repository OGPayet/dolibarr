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
 * 	\defgroup   warehousechild     Module warehousechild
 *  \brief      warehousechild module descriptor.
 *
 *  \file       htdocs/warehousechild/core/modules/modwarehousechild.class.php
 *  \ingroup    warehousechild
 *  \brief      Description and activation file for module warehousechild
 */
global $conf;
if (isset($conf->framework) && $conf->framework->enabled) {
    dol_include_once('/framework/class/dolmodexts.class.php');
    global $langs;
    $langs->load('framework');
    class_alias('DolModExts', 'DolibarrModuleswarehousechild');
} else class_alias('DolibarrModules', 'DolibarrModuleswarehousechild');

// The class name should start with a lower case mod for Dolibarr to pick it up
// so we ignore the Squiz.Classes.ValidClassName.NotCamelCaps rule.
// @codingStandardsIgnoreStart
/**
 *  Description and activation class for module warehousechild
 */
class modwarehousechild extends DolibarrModuleswarehousechild
{

    // @codingStandardsIgnoreEnd
    /**
     * Constructor. Define names, constants, directories, boxes, permissions
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        global $langs, $conf;

        $this->db = $db;

        // Id for module (must be unique).
        // Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
        $this->numero = 15100;  // TODO Go on page https://wiki.dolibarr.org/index.php/List_of_modules_id to reserve id number for your module
        // Module label (no space allowed), used if translation string 'ModulewarehousechildName' not found (MyModue is name of module).
        $this->name   = preg_replace('/^mod/i', '', get_class($this));

        if (isset($conf->framework) && $conf->framework->enabled) {
            $this->GetFileBuild();
            $this->loadOscssConf();
            // Boites
            $this->loadbox('/warehousechild/core/boxes/', '\CORE\WAREHOUSECHILD\\');
        }


        $this->description     = "ModulewarehousechildDesc";
        // Used only if file README.md and README-LL.md not found.
        $this->descriptionlong = "warehousechildDescription (Long)";


        // Name of image file used for this module.
        // If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
        // If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
        $this->picto = 'warehousechild@warehousechild';

        // Defined all module parts (triggers, login, substitutions, menus, css, etc...)
        // for default path (eg: /warehousechild/core/xxxxx) (0=disable, 1=enable)
        // for specific path of parts (eg: /warehousechild/core/modules/barcode)
        // for specific css file (eg: /warehousechild/css/warehousechild.css.php)
        $this->module_parts = array(
            'triggers' => 0, // Set this to 1 if module has its own trigger directory (core/triggers)
            'login' => 0, // Set this to 1 if module has its own login method directory (core/login)
            'substitutions' => 0, // Set this to 1 if module has its own substitution function file (core/substitutions)
            'menus' => 1, // Set this to 1 if module has its own menus handler directory (core/menus)
            'theme' => 0, // Set this to 1 if module has its own theme directory (theme)
            'tpl' => 1, // Set this to 1 if module overwrite template dir (core/tpl)
            'barcode' => 0, // Set this to 1 if module has its own barcode directory (core/modules/barcode)
            'models' => 0, // Set this to 1 if module has its own models directory (core/modules/xxx)
            //'css' => array(''), // Set this to relative path of css file if module has its own css file
            //'js' => array(''), // Set this to relative path of js file if module must load a js on all pages
            'hooks' => array('main', 'toprightmenu')  // Set here all hooks context managed by module. You can also set hook context 'all'




            /**
              @remark Specifical section of framework compatibilitie view Oscss-Shop company
             */
            // default 0, 1 for active ; Construst in admin section the panel for manage extra fields  fo this object ; the menu of config section is auto adjusted
            , 'useextrafields' => 0
            // default 0, 1 for active ; Construst in admin section the panel for manage extra fields  fo this object ; the menu of config section is auto adjusted
            , 'useextrafieldsline' => 0

            // Actiavte segment of odt , complement of models param
            // this section create In cst_table NAme of SAMPLE_ADDON_PDF
            , 'genericmodels' => array(
                'internal' => array(
                    'type' => array('pdf', 'odt')
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
                'internal' => array(
                    'default' => 'mod_qualityreport_simple'
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
        // Example: this->dirs = array("/warehousechild/temp","/warehousechild/subdir");
        $this->dirs = array();


        // Dependencies
        $this->hidden  = false;   // A condition to hide module
        $this->depends = array(
            'modframework'
            , 'modMasterlink'
        );

        $this->warnings_activation     = array();                     // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
        $this->warnings_activation_ext = array();                 // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
        // Array to add new pages in new tabs
        // Example: $this->tabs = array('objecttype:+tabname1:Title1:mylangfile@warehousechild:$user->rights->warehousechild->read:/warehousechild/mynewtab1.php?id=__ID__',  					// To add a new tab identified by code tabname1
        //                              'objecttype:+tabname2:SUBSTITUTION_Title2:mylangfile@warehousechild:$user->rights->othermodule->read:/warehousechild/mynewtab2.php?id=__ID__',  	// To add another new tab identified by code tabname2. Label will be result of calling all substitution functions on 'Title2' key.
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
//         $this->tabs = array();

        if (!isset($conf->warehousechild) || !isset($conf->warehousechild->enabled)) {
            $conf->warehousechild          = new stdClass();
            $conf->warehousechild->enabled = 0;
        }

        // Dictionaries
        $this->dictionaries = array();
        /* Example:
          $this->dictionaries=array(
          'langs'=>'mylangfile@warehousechild',
          'tabname'=>array(MAIN_DB_PREFIX."table1",MAIN_DB_PREFIX."table2",MAIN_DB_PREFIX."table3"),		// List of tables we want to see into dictonnary editor
          'tablib'=>array("Table1","Table2","Table3"),													// Label of tables
          'tabsql'=>array('SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table1 as f','SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table2 as f','SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table3 as f'),	// Request to select fields
          'tabsqlsort'=>array("label ASC","label ASC","label ASC"),																					// Sort order
          'tabfield'=>array("code,label","code,label","code,label"),																					// List of fields (result of select to show dictionary)
          'tabfieldvalue'=>array("code,label","code,label","code,label"),																				// List of fields (list of fields to edit a record)
          'tabfieldinsert'=>array("code,label","code,label","code,label"),																			// List of fields (list of fields for insert)
          'tabrowid'=>array("rowid","rowid","rowid"),																									// Name of columns with primary key (try to always name it 'rowid')
          'tabcond'=>array($conf->warehousechild->enabled,$conf->warehousechild->enabled,$conf->warehousechild->enabled)												// Condition to show each dictionary
          );
         */


        // Boxes/Widgets
        // Add here list of php file(s) stored in warehousechild/core/boxes that contains class to show a widget.
        $this->boxes = array(
//        	0=>array(
//        	'file'=>'warehousechildwidget1.php@warehousechild',
//        	'note'=>'Widget provided by warehousechild',
//        	'enabledbydefaulton'=>'Home'
//        	),
            //1=>array('file'=>'warehousechildwidget2.php@warehousechild','note'=>'Widget provided by warehousechild'),
            //2=>array('file'=>'warehousechildwidget3.php@warehousechild','note'=>'Widget provided by warehousechild')
        );


        // Cronjobs (List of cron jobs entries to add when module is enabled)
        $this->cronjobs = array(
//			0=>array('label'=>'MyJob label', 'jobtype'=>'method', 'class'=>'/warehousechild/class/warehousechildmyjob.class.php', 'objectname'=>'warehousechildMyJob', 'method'=>'myMethod', 'parameters'=>'', 'comment'=>'Comment', 'frequency'=>2, 'unitfrequency'=>3600, 'status'=>0, 'test'=>true)
        );
        // Example: $this->cronjobs=array(0=>array('label'=>'My label', 'jobtype'=>'method', 'class'=>'/dir/class/file.class.php', 'objectname'=>'MyClass', 'method'=>'myMethod', 'parameters'=>'', 'comment'=>'Comment', 'frequency'=>2, 'unitfrequency'=>3600, 'status'=>0, 'test'=>true),
        //                                1=>array('label'=>'My label', 'jobtype'=>'command', 'command'=>'', 'parameters'=>'', 'comment'=>'Comment', 'frequency'=>1, 'unitfrequency'=>3600*24, 'status'=>0, 'test'=>true)
        // );
        // Permissions
        $this->rights   = array();  // Permission array used by this module
//		$r=0;
//		$this->rights[$r][0] = $this->numero + $r;	// Permission id (must not be already used)
//		$this->rights[$r][1] = 'Read objects of My Module';	// Permission label
//		$this->rights[$r][3] = 1; 					// Permission by default for new user (0/1)
//		$this->rights[$r][4] = 'read';				// In php code, permission will be checked by test if ($user->rights->warehousechild->level1->level2)
//		$this->rights[$r][5] = '';				    // In php code, permission will be checked by test if ($user->rights->warehousechild->level1->level2)
//
//		$r++;
//		$this->rights[$r][0] = $this->numero + $r;	// Permission id (must not be already used)
//		$this->rights[$r][1] = 'Create/Update objects of My Module';	// Permission label
//		$this->rights[$r][3] = 1; 					// Permission by default for new user (0/1)
//		$this->rights[$r][4] = 'create';				// In php code, permission will be checked by test if ($user->rights->warehousechild->level1->level2)
//		$this->rights[$r][5] = '';				    // In php code, permission will be checked by test if ($user->rights->warehousechild->level1->level2)
//
//		$r++;
//		$this->rights[$r][0] = $this->numero + $r;	// Permission id (must not be already used)
//		$this->rights[$r][1] = 'Delete objects of My Module';	// Permission label
//		$this->rights[$r][3] = 1; 					// Permission by default for new user (0/1)
//		$this->rights[$r][4] = 'delete';				// In php code, permission will be checked by test if ($user->rights->warehousechild->level1->level2)
//		$this->rights[$r][5] = '';				    // In php code, permission will be checked by test if ($user->rights->warehousechild->level1->level2)
        // Main menu entries
        $this->menu     = array();   // List of menus to add
        $r              = 0;

        // Add here entries to declare new menus
        // Example to declare a new Top Menu entry and its Left menu entry:
        /* BEGIN MODULEBUILDER TOPMENU */
//        $this->menu[$r++] = array('fk_menu' => 'fk_mainmenu=products,fk_leftmenu=stock', // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
//            'type' => 'left', // This is a Top menu entry
//            'titre' => 'warehousechildMenu',
//            'mainmenu' => 'products',
//            'leftmenu' => 'stock',
//            'url' => '/warehousechild/warehousechildindex.php?limit=1000',
//            'langs' => 'warehousechild@warehousechild', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
//            'position' => 2,
//            'enabled' => '$conf->warehousechild->enabled', // Define condition to show or hide menu entry. Use '$conf->warehousechild->enabled' if entry must be visible if module is enabled.
//            'perms' => '1', // Use 'perms'=>'$user->rights->warehousechild->level1->level2' if you want your menu with a permission rules
//            'target' => '',
//            'user' => 2);                    // 0=Menu for internal users, 1=external users, 2=both

        /* END MODULEBUILDER TOPMENU */

        // Example to declare a Left Menu entry into an existing Top menu entry:
        /* BEGIN MODULEBUILDER LEFTMENU MYOBJECT
          $this->menu[$r++]=array(	'fk_menu'=>'fk_mainmenu=warehousechild',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
          'type'=>'left',			                // This is a Left menu entry
          'titre'=>'List MyObject',
          'mainmenu'=>'warehousechild',
          'leftmenu'=>'warehousechild',
          'url'=>'/warehousechild/myobject_list.php',
          'langs'=>'warehousechild@warehousechild',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
          'position'=>1000+$r,
          'enabled'=>'$conf->warehousechild->enabled',  // Define condition to show or hide menu entry. Use '$conf->warehousechild->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
          'perms'=>'1',			                // Use 'perms'=>'$user->rights->warehousechild->level1->level2' if you want your menu with a permission rules
          'target'=>'',
          'user'=>2);				                // 0=Menu for internal users, 1=external users, 2=both
          $this->menu[$r++]=array(	'fk_menu'=>'fk_mainmenu=warehousechild,fk_leftmenu=warehousechild',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
          'type'=>'left',			                // This is a Left menu entry
          'titre'=>'New MyObject',
          'mainmenu'=>'warehousechild',
          'leftmenu'=>'warehousechild',
          'url'=>'/warehousechild/myobject_page.php?action=create',
          'langs'=>'warehousechild@warehousechild',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
          'position'=>1000+$r,
          'enabled'=>'$conf->warehousechild->enabled',  // Define condition to show or hide menu entry. Use '$conf->warehousechild->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
          'perms'=>'1',			                // Use 'perms'=>'$user->rights->warehousechild->level1->level2' if you want your menu with a permission rules
          'target'=>'',
          'user'=>2);				                // 0=Menu for internal users, 1=external users, 2=both
          END MODULEBUILDER LEFTMENU MYOBJECT */


        // Exports
        $r = 1;

        // Example:
        /* BEGIN MODULEBUILDER EXPORT MYOBJECT
          $this->export_code[$r]=$this->rights_class.'_'.$r;
          $this->export_label[$r]='warehousechild';	                         // Translation key (used only if key ExportDataset_xxx_z not found)
          $this->export_enabled[$r]='1';                               // Condition to show export in list (ie: '$user->id==3'). Set to 1 to always show when module is enabled.
          $this->export_icon[$r]='generic:warehousechild';					 // Put here code of icon then string for translation key of module name
          //$this->export_permission[$r]=array(array("warehousechild","level1","level2"));
          $this->export_fields_array[$r]=array('t.rowid'=>"Id",'t.ref'=>'Ref','t.label'=>'Label','t.datec'=>"DateCreation",'t.tms'=>"DateUpdate");
          $this->export_TypeFields_array[$r]=array('t.rowid'=>'Numeric', 't.ref'=>'Text', 't.label'=>'Label', 't.datec'=>"Date", 't.tms'=>"Date");
          // $this->export_entities_array[$r]=array('t.rowid'=>"company",'s.nom'=>'company','s.address'=>'company','s.zip'=>'company','s.town'=>'company','s.fk_pays'=>'company','s.phone'=>'company','s.siren'=>'company','s.siret'=>'company','s.ape'=>'company','s.idprof4'=>'company','s.code_compta'=>'company','s.code_compta_fournisseur'=>'company','f.rowid'=>"invoice",'f.facnumber'=>"invoice",'f.datec'=>"invoice",'f.datef'=>"invoice",'f.total'=>"invoice",'f.total_ttc'=>"invoice",'f.tva'=>"invoice",'f.paye'=>"invoice",'f.fk_statut'=>'invoice','f.note'=>"invoice",'fd.rowid'=>'invoice_line','fd.description'=>"invoice_line",'fd.price'=>"invoice_line",'fd.total_ht'=>"invoice_line",'fd.total_tva'=>"invoice_line",'fd.total_ttc'=>"invoice_line",'fd.tva_tx'=>"invoice_line",'fd.qty'=>"invoice_line",'fd.date_start'=>"invoice_line",'fd.date_end'=>"invoice_line",'fd.fk_product'=>'product','p.ref'=>'product');
          // $this->export_dependencies_array[$r]=array('invoice_line'=>'fd.rowid','product'=>'fd.rowid');   // To add unique key if we ask a field of a child to avoid the DISTINCT to discard them
          // $this->export_sql_start[$r]='SELECT DISTINCT ';
          // $this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'myobject as t';
          // $this->export_sql_order[$r] .=' ORDER BY t.ref';
          // $r++;
          END MODULEBUILDER EXPORT MYOBJECT */




        /**
          @remarks End loaded config ans auto-config construct
         */
        if (isset($conf->framework) && $conf->framework->enabled) $this->EndLoader();
    }

    /**
     * 		Function called when module is enabled.
     * 		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
     * 		It also creates data directories
     *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
     *      @return     int             	1 if OK, 0 if KO
     */
    public function init($options = '')
    {
        global $user, $db;
        $sql = array(
            array('ignoreerror' => true, 'sql' => "ALTER TABLE ".MAIN_DB_PREFIX."entrepot ADD `fk_user_modif` INT(11) NULL DEFAULT '0'"),
            array('ignoreerror' => true, 'sql' => "ALTER TABLE ".MAIN_DB_PREFIX."entrepot DROP INDEX uk_entrepot_label"),
            array('ignoreerror' => true, 'sql' => "ALTER TABLE ".MAIN_DB_PREFIX."entrepot ADD UNIQUE INDEX uk_entrepot_ref_fk_parent (ref, entity, fk_parent)")
        );

        $this->_load_tables('/warehousechild/sql/');

        // Create extrafields
        include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
        $extrafields = new ExtraFields($this->db);
        //$result1=$extrafields->addExtraField('myattr1', "New Attr 1 label", 'boolean', 1, 3, 'thirdparty');
        //$result2=$extrafields->addExtraField('myattr2', "New Attr 2 label", 'string', 1, 10, 'project');




        dol_include_once('/framework/class/masterlink.class.php');

        //v5
        $ml           = new masterlink($db);
        $ml->original = '/product/stock/card.php';
        $ml->custom   = '/warehousechild/masterlink/product/stock/card.php';
        $ml->active   = 1;
        $ml->create($user);
        $ml->original = '/product/stock/list.php';
        $ml->custom   = '/warehousechild/masterlink/product/stock/list.php';
        $ml->active   = 1;
        $ml->create($user);
        $ml->original = '/product/stock/product.php';
        $ml->custom   = '/warehousechild/masterlink/product/stock/product.php';
        $ml->active   = 1;
        $ml->create($user);
        $ml->original = '/product/card.php';
        $ml->custom   = '/warehousechild/masterlink/product/card.php';
        $ml->active   = 1;
        $ml->create($user);
        $ml->original = '/fourn/commande/dispatch.php';
        $ml->custom   = '/warehousechild/masterlink/fourn/commande/dispatch.php';
        $ml->active   = 1;
        $ml->create($user);
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
        global $db;
        dol_include_once('/framework/class/masterlink.class.php');

        $ml = new masterlink($db);

        $ml->delete(0, '/product/stock/product.php', '/warehousechild/masterlink/product/stock/product.php');
        $ml->delete(0, '/product/stock/card.php', '/warehousechild/masterlink/product/stock/card.php');
        $ml->delete(0, '/product/stock/list.php', '/warehousechild/masterlink/product/stock/list.php');
        $ml->delete(0, '/product/card.php', '/warehousechild/masterlink/product/card.php');
        $ml->delete(0, '/fourn/commande/dispatch.php', '/warehousechild/masterlink/fourn/commande/dispatch.php');

        $sql = array();

        return $this->_remove($sql, $options);
    }
}