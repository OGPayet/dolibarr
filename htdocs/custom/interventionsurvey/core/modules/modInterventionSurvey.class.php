<?php
/* Copyright (C) 2004-2018  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2019  Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2019       Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2020 Alexis LAURIER
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * 	\defgroup   interventionsurvey     Module InterventionSurvey
 *  \brief      InterventionSurvey module descriptor.
 *
 *  \file       htdocs/interventionsurvey/core/modules/modInterventionSurvey.class.php
 *  \ingroup    interventionsurvey
 *  \brief      Description and activation file for module InterventionSurvey
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 *  Description and activation class for module InterventionSurvey
 */
class modInterventionSurvey extends DolibarrModules
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

        // Id for module (must be unique).
        // Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
        $this->numero = 468400; // TODO Go on page https://wiki.dolibarr.org/index.php/List_of_modules_id to reserve an id number for your module
        // Key text used to identify module (for permissions, menus, etc...)
        $this->rights_class = 'interventionsurvey';
        // Family can be 'base' (core modules),'crm','financial','hr','projects','products','ecm','technic' (transverse modules),'interface' (link with external tools),'other','...'
        // It is used to group modules by family in module setup page
        $this->family = "crm";
        // Module position in the family on 2 digits ('01', '10', '20', ...)
        $this->module_position = '90';
        // Gives the possibility for the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
        //$this->familyinfo = array('myownfamily' => array('position' => '01', 'label' => $langs->trans("MyOwnFamily")));
        // Module label (no space allowed), used if translation string 'ModuleInterventionSurveyName' not found (InterventionSurvey is name of module).
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        // Module description, used if translation string 'ModuleInterventionSurveyDesc' not found (InterventionSurvey is name of module).
        $this->description = "InterventionSurveyDescription";
        // Used only if file README.md and README-LL.md not found.
        $this->descriptionlong = "InterventionSurvey description (Long)";
        $this->editor_name = 'Alexis LAURIER';
        $this->editor_url = 'https://www.alexislaurier.fr';
        // Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'
        $this->version = '1.0';
        // Url to the file with your last numberversion of this module
        //$this->url_last_version = 'http://www.example.com/versionmodule.txt';

        // Key used in llx_const table to save module status enabled/disabled (where INTERVENTIONSURVEY is value of property name of module in uppercase)
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        // Name of image file used for this module.
        // If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
        // If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
        $this->picto = 'interventionsurvey_tip@interventionsurvey';
        // Define some features supported by module (triggers, login, substitutions, menus, css, etc...)
        $this->module_parts = array(
            'dictionaries' => 1,
            // Set this to 1 if module has its own trigger directory (core/triggers)
            'triggers' => 1,
            // Set this to 1 if module has its own login method file (core/login)
            'login' => 0,
            // Set this to 1 if module has its own substitution function file (core/substitutions)
            'substitutions' => 0,
            // Set this to 1 if module has its own menus handler directory (core/menus)
            'menus' => 0,
            // Set this to 1 if module overwrite template dir (core/tpl)
            'tpl' => 1,
            // Set this to 1 if module has its own barcode directory (core/modules/barcode)
            'barcode' => 0,
            // Set this to 1 if module has its own models directory (core/modules/xxx)
            'models' => 1,
            // Set this to 1 if module has its own theme directory (theme)
            'theme' => 0,
            // Set this to relative path of css file if module has its own css file
            'css' => array(
                //    '/interventionsurvey/css/interventionsurvey.css.php',
            ),
            // Set this to relative path of js file if module must load a js on all pages
            'js' => array(
                //   '/interventionsurvey/js/interventionsurvey.js.php',
            ),
            // Set here all hooks context managed by module. To find available hook context, make a "grep -r '>initHooks(' *" on source code. You can also set hook context to 'all'
            'hooks' => array(
                'interventiondocument',
                'interventioncard',
                'equipementcard'
            ),
            // Set this to 1 if features of module are opened to external users
            'moduleforexternal' => 0,
        );
        // Data directories to create when module is enabled.
        // Example: this->dirs = array("/interventionsurvey/temp","/interventionsurvey/subdir");
        $this->dirs = array("/interventionsurvey/temp");
        // Config pages. Put here list of php page, stored into interventionsurvey/admin directory, to use to setup module.
        $this->config_page_url = array("setup.php@interventionsurvey");
        // Dependencies
        // A condition to hide module
        $this->hidden = false;
        // List of module class names as string that must be enabled if this module is enabled. Example: array('always1'=>'modModuleToEnable1','always2'=>'modModuleToEnable2', 'FR1'=>'modModuleToEnableFR'...)
        $this->depends = array('modFicheinter','modAdvanceDictionaries');
        $this->requiredby = array(); // List of module class names as string to disable if this one is disabled. Example: array('modModuleToDisable1', ...)
        $this->conflictwith = array(); // List of module class names as string this module is in conflict with. Example: array('modModuleToDisable1', ...)
        $this->langfiles = array("interventionsurvey@interventionsurvey");
        $this->phpmin = array(5, 5); // Minimum version of PHP required by module
        $this->need_dolibarr_version = array(6, -3); // Minimum version of Dolibarr required by module
        $this->warnings_activation = array(); // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
        $this->warnings_activation_ext = array(); // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
        //$this->automatic_activation = array('FR'=>'InterventionSurveyWasAutomaticallyActivatedBecauseOfYourCountryChoice');
        //$this->always_enabled = true;								// If true, can't be disabled

        // Constants
        // List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
        // Example: $this->const=array(1 => array('INTERVENTIONSURVEY_MYNEWCONST1', 'chaine', 'myvalue', 'This is a constant to add', 1),
        //                             2 => array('INTERVENTIONSURVEY_MYNEWCONST2', 'chaine', 'myvalue', 'This is another constant to add', 0, 'current', 1)
        // );
        $this->const = array(
            // 1 => array('INTERVENTIONSURVEY_MYCONSTANT', 'chaine', 'avalue', 'This is a constant to add', 1, 'allentities', 1)
        );

        // Some keys to add into the overwriting translation tables
        /*$this->overwrite_translation = array(
            'en_US:ParentCompany'=>'Parent company or reseller',
            'fr_FR:ParentCompany'=>'Maison mère ou revendeur'
        )*/

        if (!isset($conf->interventionsurvey) || !isset($conf->interventionsurvey->enabled)) {
            $conf->interventionsurvey = new stdClass();
            $conf->interventionsurvey->enabled = 0;
        }

        // Array to add new pages in new tabs
        $this->tabs = array(
            'intervention:+interventionsurvey:InterventionSurveyTabTitle:interventionsurvey@interventionsurvey:$user->rights->interventionsurvey->survey->read:/custom/interventionsurvey/survey.php?id=__ID__',
            'intervention:-documents',
            'intervention:+interventionsurvey_documents:InterventionSurveyDocuments:interventionsurvey@interventionsurvey:1:/custom/interventionsurvey/document.php?id=__ID__',
            'interventionsurvey:+dictionary_surveyanswerpredefinedtext:InterventionSurveyAnswerPredefinedTextDictionaryTabTitle:interventionsurvey@interventionSurvey:$user->rights->interventionsurvey->settings->manage:/interventionsurvey/admin/dictionaries.php?name=surveyanswerpredefinedtext',
            'interventionsurvey:+dictionary_surveyanswer:InterventionSurveyAnswerDictionaryTabTitle:interventionsurvey@interventionSurvey:$user->rights->interventionsurvey->settings->manage:/interventionsurvey/admin/dictionaries.php?name=surveyanswer',
            'interventionsurvey:+interventionSurvey_question_extrafields:InterventionSurveyQuestionExtrafields:interventionsurvey@interventionSurvey:$user->rights->interventionsurvey->settings->manage:/interventionsurvey/admin/interventionsurvey_question_extrafields.php',
            'interventionsurvey:+dictionary_surveyquestion:InterventionSurveyQuestionDictionaryTabTitle:interventionsurvey@interventionSurvey:$user->rights->interventionsurvey->settings->manage:/interventionsurvey/admin/dictionaries.php?name=surveyquestion',
            'interventionsurvey:+dictionary_surveyblocstatuspredefinedtext:InterventionSurveyBlocStatusPredefinedTextDictionaryTabTitle:interventionsurvey@interventionSurvey:$user->rights->interventionsurvey->settings->manage:/interventionsurvey/admin/dictionaries.php?name=surveyblocstatuspredefinedtext',
            'interventionsurvey:+dictionary_surveyblocstatus:InterventionSurveyBlocStatusDictionaryTabTitle:interventionsurvey@interventionSurvey:$user->rights->interventionsurvey->settings->manage:/interventionsurvey/admin/dictionaries.php?name=surveyblocstatus',
            'interventionsurvey:+interventionSurvey_blocQuestion_extrafields:InterventionSurveyBlocQuestionExtrafield:interventionsurvey@interventionSurvey:$user->rights->interventionsurvey->settings->manage:/interventionsurvey/admin/interventionsurvey_blocquestion_extrafields.php',
            'interventionsurvey:+dictionary_surveyblocquestion:InterventionSurveyBlocQuestionDictionaryTabTitle:interventionsurvey@interventionSurvey:$user->rights->interventionsurvey->settings->manage:/interventionsurvey/admin/dictionaries.php?name=surveyblocquestion',
        );
        // Example:
        // $this->tabs[] = array('data'=>'objecttype:+tabname1:Title1:mylangfile@interventionsurvey:$user->rights->interventionsurvey->read:/interventionsurvey/mynewtab1.php?id=__ID__');  					// To add a new tab identified by code tabname1
        // $this->tabs[] = array('data'=>'objecttype:+tabname2:SUBSTITUTION_Title2:mylangfile@interventionsurvey:$user->rights->othermodule->read:/interventionsurvey/mynewtab2.php?id=__ID__',  	// To add another new tab identified by code tabname2. Label will be result of calling all substitution functions on 'Title2' key.
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
        $this->dictionaries = array();
        /* Example:
        $this->dictionaries=array(
            'langs'=>'interventionsurvey@interventionsurvey',
            // List of tables we want to see into dictonnary editor
            'tabname'=>array(MAIN_DB_PREFIX."table1", MAIN_DB_PREFIX."table2", MAIN_DB_PREFIX."table3"),
            // Label of tables
            'tablib'=>array("Table1", "Table2", "Table3"),
            // Request to select fields
            'tabsql'=>array('SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table1 as f', 'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table2 as f', 'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table3 as f'),
            // Sort order
            'tabsqlsort'=>array("label ASC", "label ASC", "label ASC"),
            // List of fields (result of select to show dictionary)
            'tabfield'=>array("code,label", "code,label", "code,label"),
            // List of fields (list of fields to edit a record)
            'tabfieldvalue'=>array("code,label", "code,label", "code,label"),
            // List of fields (list of fields for insert)
            'tabfieldinsert'=>array("code,label", "code,label", "code,label"),
            // Name of columns with primary key (try to always name it 'rowid')
            'tabrowid'=>array("rowid", "rowid", "rowid"),
            // Condition to show each dictionary
            'tabcond'=>array($conf->interventionsurvey->enabled, $conf->interventionsurvey->enabled, $conf->interventionsurvey->enabled)
        );
        */

        // Boxes/Widgets
        // Add here list of php file(s) stored in interventionsurvey/core/boxes that contains a class to show a widget.
        $this->boxes = array(
            //  0 => array(
            //      'file' => 'interventionsurveywidget1.php@interventionsurvey',
            //      'note' => 'Widget provided by InterventionSurvey',
            //      'enabledbydefaulton' => 'Home',
            //  ),
            //  ...
        );

        // Cronjobs (List of cron jobs entries to add when module is enabled)
        // unit_frequency must be 60 for minute, 3600 for hour, 86400 for day, 604800 for week
        $this->cronjobs = array(
            //  0 => array(
            //      'label' => 'MyJob label',
            //      'jobtype' => 'method',
            //      'class' => '/interventionsurvey/class/interventionsurvey.class.php',
            //      'objectname' => 'interventionSurvey',
            //      'method' => 'doScheduledJob',
            //      'parameters' => '',
            //      'comment' => 'Comment',
            //      'frequency' => 2,
            //      'unitfrequency' => 3600,
            //      'status' => 0,
            //      'test' => '$conf->interventionsurvey->enabled',
            //      'priority' => 50,
            //  ),
        );
        // Example: $this->cronjobs=array(
        //    0=>array('label'=>'My label', 'jobtype'=>'method', 'class'=>'/dir/class/file.class.php', 'objectname'=>'MyClass', 'method'=>'myMethod', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>2, 'unitfrequency'=>3600, 'status'=>0, 'test'=>'$conf->interventionsurvey->enabled', 'priority'=>50),
        //    1=>array('label'=>'My label', 'jobtype'=>'command', 'command'=>'', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>1, 'unitfrequency'=>3600*24, 'status'=>0, 'test'=>'$conf->interventionsurvey->enabled', 'priority'=>50)
        // );

        // Permissions provided by this module
        $this->rights = array();
        $r = 0;
        // Add here entries to declare new permissions
        /* BEGIN MODULEBUILDER PERMISSIONS */
        $this->rights[$r][0] = $this->numero + $r; // Permission id (must not be already used)
        $this->rights[$r][1] = 'Read objects of InterventionSurvey'; // Permission label
        $this->rights[$r][4] = 'survey'; // In php code, permission will be checked by test if ($user->rights->interventionsurvey->level1->level2)
        $this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->interventionsurvey->level1->level2)
        $r++;
        $this->rights[$r][0] = $this->numero + $r; // Permission id (must not be already used)
        $this->rights[$r][1] = 'Create/Update objects of InterventionSurvey'; // Permission label
        $this->rights[$r][4] = 'survey'; // In php code, permission will be checked by test if ($user->rights->interventionsurvey->level1->level2)
        $this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->rights->interventionsurvey->level1->level2)
        $r++;
        $this->rights[$r][0] = $this->numero + $r; // Permission id (must not be already used)
        $this->rights[$r][1] = 'Read objects of InterventionSurvey through API'; // Permission label
        $this->rights[$r][4] = 'survey'; // In php code, permission will be checked by test if ($user->rights->interventionsurvey->level1->level2)
        $this->rights[$r][5] = 'readApi'; // In php code, permission will be checked by test if ($user->rights->interventionsurvey->level1->level2)
        $r++;
        $this->rights[$r][0] = $this->numero + $r; // Permission id (must not be already used)
        $this->rights[$r][1] = 'Create/Update objects of InterventionSurvey through API'; // Permission label
        $this->rights[$r][4] = 'survey'; // In php code, permission will be checked by test if ($user->rights->interventionsurvey->level1->level2)
        $this->rights[$r][5] = 'writeApi'; // In php code, permission will be checked by test if ($user->rights->interventionsurvey->level1->level2)
        $r++;
        $this->rights[$r][0] = $this->numero + $r; // Permission id (must not be already used)
        $this->rights[$r][1] = 'Manage Settings of this module'; // Permission label
        $this->rights[$r][4] = 'settings'; // In php code, permission will be checked by test if ($user->rights->interventionsurvey->level1->level2)
        $this->rights[$r][5] = 'manage'; // In php code, permission will be checked by test if ($user->rights->interventionsurvey->level1->level2)
        $r++;
        $this->rights[$r][0] = $this->numero + $r; // Permission id (must not be already used)
        $this->rights[$r][1] = 'Manage simple regeneration operations'; // Permission label
        $this->rights[$r][4] = 'survey'; // In php code, permission will be checked by test if ($user->rights->interventionsurvey->level1->level2)
        $this->rights[$r][5] = 'manage'; // In php code, permission will be checked by test if ($user->rights->interventionsurvey->level1->level2)
        $r++;
        $this->rights[$r][0] = $this->numero + $r; // Permission id (must not be already used)
        $this->rights[$r][1] = 'Manage advanced regeneration operations'; // Permission label
        $this->rights[$r][4] = 'survey'; // In php code, permission will be checked by test if ($user->rights->interventionsurvey->level1->level2)
        $this->rights[$r][5] = 'manageMore'; // In php code, permission will be checked by test if ($user->rights->interventionsurvey->level1->level2)
        $r++;
        /* END MODULEBUILDER PERMISSIONS */

        // Main menu entries to add
        $this->menu = array();
        $r = 0;
        // Add here entries to declare new menus

        /* BEGIN MODULEBUILDER LEFTMENU INTERVENTIONSURVEY
        $this->menu[$r++]=array(
            'fk_menu'=>'fk_mainmenu=interventionsurvey',      // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'type'=>'left',                          // This is a Top menu entry
            'titre'=>'interventionSurvey',
            'mainmenu'=>'interventionsurvey',
            'leftmenu'=>'interventionsurvey',
            'url'=>'/interventionsurvey/interventionsurveyindex.php',
            'langs'=>'interventionsurvey@interventionsurvey',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'position'=>1000+$r,
            'enabled'=>'$conf->interventionsurvey->enabled',  // Define condition to show or hide menu entry. Use '$conf->interventionsurvey->enabled' if entry must be visible if module is enabled.
            'perms'=>'$user->rights->interventionsurvey->interventionsurvey->read',			                // Use 'perms'=>'$user->rights->interventionsurvey->level1->level2' if you want your menu with a permission rules
            'target'=>'',
            'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
        );
        $this->menu[$r++]=array(
            'fk_menu'=>'fk_mainmenu=interventionsurvey,fk_leftmenu=interventionsurvey',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'type'=>'left',			                // This is a Left menu entry
            'titre'=>'List interventionSurvey',
            'mainmenu'=>'interventionsurvey',
            'leftmenu'=>'interventionsurvey_interventionsurvey_list',
            'url'=>'/interventionsurvey/interventionsurvey_list.php',
            'langs'=>'interventionsurvey@interventionsurvey',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'position'=>1000+$r,
            'enabled'=>'$conf->interventionsurvey->enabled',  // Define condition to show or hide menu entry. Use '$conf->interventionsurvey->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
            'perms'=>'$user->rights->interventionsurvey->interventionsurvey->read',			                // Use 'perms'=>'$user->rights->interventionsurvey->level1->level2' if you want your menu with a permission rules
            'target'=>'',
            'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
        );
        $this->menu[$r++]=array(
            'fk_menu'=>'fk_mainmenu=interventionsurvey,fk_leftmenu=interventionsurvey',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'type'=>'left',			                // This is a Left menu entry
            'titre'=>'New interventionSurvey',
            'mainmenu'=>'interventionsurvey',
            'leftmenu'=>'interventionsurvey_interventionsurvey_new',
            'url'=>'/interventionsurvey/interventionsurvey_page.php?action=create',
            'langs'=>'interventionsurvey@interventionsurvey',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'position'=>1000+$r,
            'enabled'=>'$conf->interventionsurvey->enabled',  // Define condition to show or hide menu entry. Use '$conf->interventionsurvey->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
            'perms'=>'$user->rights->interventionsurvey->interventionsurvey->write',			                // Use 'perms'=>'$user->rights->interventionsurvey->level1->level2' if you want your menu with a permission rules
            'target'=>'',
            'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
        );
        */

		/* END MODULEBUILDER LEFTMENU INTERVENTIONSURVEY */

        // Exports profiles provided by this module
        $r = 1;
        /* BEGIN MODULEBUILDER EXPORT INTERVENTIONSURVEY */
        /*
        $langs->load("interventionsurvey@interventionsurvey");
        $this->export_code[$r]=$this->rights_class.'_'.$r;
        $this->export_label[$r]='surveyInterventionLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
        $this->export_icon[$r]='interventionsurvey@interventionsurvey';
        // Define $this->export_fields_array, $this->export_TypeFields_array and $this->export_entities_array
        $keyforclass = 'interventionSurvey'; $keyforclassfile='/mymobule/class/interventionsurvey.class.php'; $keyforelement='interventionsurvey@interventionsurvey';
        include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
        //$this->export_fields_array[$r]['t.fieldtoadd']='FieldToAdd'; $this->export_TypeFields_array[$r]['t.fieldtoadd']='Text';
        //unset($this->export_fields_array[$r]['t.fieldtoremove']);
		//$keyforclass = 'surveyInterventionLine'; $keyforclassfile='/interventionsurvey/class/interventionsurvey.class.php'; $keyforelement='interventionsurveyline@interventionsurvey'; $keyforalias='tl';
		//include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
        $keyforselect='interventionsurvey'; $keyforaliasextra='extra'; $keyforelement='interventionsurvey@interventionsurvey';
        include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
        //$keyforselect='interventionsurveyline'; $keyforaliasextra='extraline'; $keyforelement='interventionsurveyline@interventionsurvey';
        //include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
        //$this->export_dependencies_array[$r] = array('interventionsurveyline'=>array('tl.rowid','tl.ref')); // To force to activate one or several fields if we select some fields that need same (like to select a unique key if we ask a field of a child to avoid the DISTINCT to discard them, or for computed field than need several other fields)
        //$this->export_special_array[$r] = array('t.field'=>'...');
        //$this->export_examplevalues_array[$r] = array('t.field'=>'Example');
        //$this->export_help_array[$r] = array('t.field'=>'FieldDescHelp');
        $this->export_sql_start[$r]='SELECT DISTINCT ';
        $this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'interventionsurvey as t';
        //$this->export_sql_end[$r]  =' LEFT JOIN '.MAIN_DB_PREFIX.'interventionsurvey_line as tl ON tl.fk_interventionsurvey = t.rowid';
        $this->export_sql_end[$r] .=' WHERE 1 = 1';
        $this->export_sql_end[$r] .=' AND t.entity IN ('.getEntity('interventionsurvey').')';
        $r++; */
        /* END MODULEBUILDER EXPORT INTERVENTIONSURVEY */

        // Imports profiles provided by this module
        $r = 1;
        /* BEGIN MODULEBUILDER IMPORT INTERVENTIONSURVEY */
        /*
         $langs->load("interventionsurvey@interventionsurvey");
         $this->export_code[$r]=$this->rights_class.'_'.$r;
         $this->export_label[$r]='surveyInterventionLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
         $this->export_icon[$r]='interventionsurvey@interventionsurvey';
         $keyforclass = 'interventionSurvey'; $keyforclassfile='/mymobule/class/interventionsurvey.class.php'; $keyforelement='interventionsurvey@interventionsurvey';
         include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
         $keyforselect='interventionsurvey'; $keyforaliasextra='extra'; $keyforelement='interventionsurvey@interventionsurvey';
         include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
         //$this->export_dependencies_array[$r]=array('mysubobject'=>'ts.rowid', 't.myfield'=>array('t.myfield2','t.myfield3')); // To force to activate one or several fields if we select some fields that need same (like to select a unique key if we ask a field of a child to avoid the DISTINCT to discard them, or for computed field than need several other fields)
         $this->export_sql_start[$r]='SELECT DISTINCT ';
         $this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'interventionsurvey as t';
         $this->export_sql_end[$r] .=' WHERE 1 = 1';
         $this->export_sql_end[$r] .=' AND t.entity IN ('.getEntity('interventionsurvey').')';
         $r++; */
        /* END MODULEBUILDER IMPORT INTERVENTIONSURVEY */
    }

    /**
     *  Function called when module is enabled.
     *  The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
     *  It also creates data directories
     *
     *  @param      string  $options    Options when enabling module ('', 'noboxes')
     *  @return     int             	1 if OK, 0 if KO
     */
    public function init($options = '')
    {
        global $conf, $langs;
        // Create tables of all dictionaries
        include_once DOL_DOCUMENT_ROOT.'/custom/advancedictionaries/class/dictionary.class.php';
        $dictionaries = Dictionary::fetchAllDictionaries($this->db, 'interventionsurvey');
        foreach ($dictionaries as $dictionary) {
            if ($dictionary->createTables() < 0) {
                setEventMessage('Error create dictionary table: ' . $dictionary->errorsToString(), 'errors');
            }
        }

        $result = $this->_load_tables('/interventionsurvey/sql/');
        if ($result < 0) return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')

        // Create extrafields during init
        $langs->load("interventionsurvey@interventionsurvey");
        include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
        $extrafields = new ExtraFields($this->db);
        $result1=$extrafields->addExtraField('involved_users', $langs->trans('InterventionSurveyInvolvedUserLabel'), 'chkbxlst', 1000,  NULL, 'fichinterdet',   0, 1, NULL, array('options'=>array('user:firstname|lastname:rowid::statut = 1 AND fk_soc IS NULL'=>null)), 0, '', 1, 0, '', '', 'interventionsurvey@interventionsurvey', '$conf->interventionsurvey->enabled');
        $result2=$extrafields->addExtraField('stakeholder_signature', $langs->trans('InterventionSurveyStakeholderSignatureLabel'), 'text', 1000,  NULL, 'fichinter',   0, 0, NULL, NULL, 0, '', 0, 0, '', '', 'interventionsurvey@interventionsurvey', '$conf->interventionsurvey->enabled');
        $result3=$extrafields->addExtraField('customer_signature', $langs->trans('InterventionSurveyCustomerSignatureLabel'), 'text', 1000,  NULL, 'fichinter',   0, 0, NULL, NULL, 0, '', 0, 0, '', '', 'interventionsurvey@interventionsurvey', '$conf->interventionsurvey->enabled');
        // Permissions
        $this->remove($options);

        $sql = array();

        // ODT template
        /*
        $src=DOL_DOCUMENT_ROOT.'/install/doctemplates/interventionsurvey/template_interventionsurveys.odt';
        $dirodt=DOL_DATA_ROOT.'/doctemplates/interventionsurvey';
        $dest=$dirodt.'/template_interventionsurveys.odt';

        if (file_exists($src) && ! file_exists($dest))
        {
            require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
            dol_mkdir($dirodt);
            $result=dol_copy($src, $dest, 0, 0);
            if ($result < 0)
            {
                $langs->load("errors");
                $this->error=$langs->trans('ErrorFailToCopyFile', $src, $dest);
                return 0;
            }
        }

        $sql = array(
            "DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = '".$this->db->escape($this->const[0][2])."' AND type = 'interventionsurvey' AND entity = ".$conf->entity,
            "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('".$this->db->escape($this->const[0][2])."','interventionsurvey',".$conf->entity.")"
        );
        */

        return $this->_init($sql, $options);
    }

    /**
     *  Function called when module is disabled.
     *  Remove from database constants, boxes and permissions from Dolibarr database.
     *  Data directories are not deleted
     *
     *  @param      string	$options    Options when enabling module ('', 'noboxes')
     *  @return     int                 1 if OK, 0 if KO
     */
    public function remove($options = '')
    {
        $sql = array();
        return $this->_remove($sql, $options);
    }
}
