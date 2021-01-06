<?php
/* Copyright (C) 2015-2017 	Charlie BENKE  <charlie@patas-monkey.com>
 * Module pour gerer les droits sur les tiers
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
include_once(DOL_DOCUMENT_ROOT ."/core/modules/DolibarrModules.class.php");

/**
 * 		\class	  modteamrights
 *	  \brief	  Description and activation class for module MyModule
 */
class modportofolio extends DolibarrModules
{
	/**
	 *   \brief	  Constructor. Define names, constants, directories, boxes, permissions
	 *   \param	  DB	  Database handler
	 */
	public function __construct($db)
	{
		global $langs, $conf;

		$this->db = $db;

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 160213;

		$this->editor_name = "<b>Patas-Monkey</b>";
		$this->editor_web = "http://www.patas-monkey.com";

		// Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
		// It is used to group modules in module setup page
		$this->family = "Patas-Tools";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		// Module description, used if translation string 'ModuleXXXDesc' not found
		$this->description = "Gestion des habilitations d'accès aux tiers en masse";

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = $this->getLocalVersion();
		// Key used in llx_const table to save module status enabled/disabled
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
		$this->special = 0;
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		$this->picto=$this->name.'.png@'.$this->name;

		// Defined if the directory /mymodule/inc/triggers/ contains triggers or not
				$this->module_parts = array(
			'hooks' => array('thirdpartycard', 'propalcard'),
			'css' => '/portofolio/css/patastools.css',	   // Set this to relative path of css if module has its own css file
			'triggers' => 1
		);

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/mymodule/temp");
		$this->dirs = array();
		$r=0;

		// Dependencies
		// List of modules id that must be enabled if this module is enabled
		$this->requiredby = array();	// List of modules id to disable if this one is disabled
		$this->phpmin = array(4,3);					// Minimum version of PHP required by module
		$this->need_dolibarr_version = array(3,4);	// Minimum version of Dolibarr required by module

		$this->langfiles = array($this->name."@".$this->name);

		// Config pages
		$this->config_page_url = array("setup.php@".$this->name);

		// Constants
		$this->const = array();	// List of particular constants to add when module is enabled

		// Array to add new pages in new tabs
		$this->tabs = array();

		// Boxes
		$this->boxes = array();			// List of boxes

		// Permissions
		$this->rights = array();
		$this->rights_class = $this->name;
		$r=0;

		$r++;
		$this->rights[$r][0] = 1602131; // id de la permission
		$this->rights[$r][1] = "Lire les groupes/catégories"; // libelle de la permission
		$this->rights[$r][2] = 'r'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 1; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'lire';

		$r++;
		$this->rights[$r][0] = 1602132; // id de la permission
		$this->rights[$r][1] = "Modifier les groupes/catégories"; // libelle de la permission
		$this->rights[$r][2] = 'w'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'setup';

		// Left-Menu of portofolio module
		$r=0;
		if ($this->no_topmenu()) {
			$this->menu[$r]=array(	'fk_menu'=>0,
						'type'=>'top',
						'titre'=>'PatasTools',
						'mainmenu'=>'patastools',
						'leftmenu'=>'portofolio',
						'url'=>'/portofolio/core/patastools.php?mainmenu=patastools&leftmenu=myfield',
						'langs'=>'portofolio@portofolio',
						'position'=>100, 'enabled'=>'1',
						'perms'=>'admin', 'target'=>'',
						'user'=>0);
			$r++; //1
		}

		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=patastools',
					'type'=>'left',
					'titre'=>'Portofolio',
					'mainmenu'=>'patastools',
					'leftmenu'=>'portofolio',
					'url'=>'/portofolio/index.php?mainmenu=patastools',
					'langs'=>'portofolio@portofolio',
					'position'=>110, 'enabled'=>'1',
					'perms'=>'$user->rights->portofolio->lire', 'target'=>'',
					'user'=>2);
		$r++; //1


		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=patastools,fk_leftmenu=portofolio',
					'type'=>'left',
					'titre'=>'ThirdPartiesGroupchange',
					'mainmenu'=>'',
					'leftmenu'=>'',
					'url'=>'/portofolio/list.php?mainmenu=patastools&leftmenu=portofolio',
					'langs'=>'portofolio@portofolio',
					'position'=>110, 'enabled'=>'1',
					'perms'=>'$user->rights->portofolio->lire', 'target'=>'',
					'user'=>2);
		$r++; //1

		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=patastools,fk_leftmenu=portofolio',
					'type'=>'left',
					'titre'=>'UserGroupchange',
					'mainmenu'=>'',
					'leftmenu'=>'',
					'url'=>'/portofolio/grpchange.php?mainmenu=patastools&leftmenu=portofolio',
					'langs'=>'portofolio@portofolio',
					'position'=>110, 'enabled'=>'1',
					'perms'=>'$user->rights->portofolio->lire', 'target'=>'',
					'user'=>0);

		if ($conf->category->enabled) {
			$r++;
			$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=patastools,fk_leftmenu=portofolio',
						'type'=>'left',
						'titre'=>'CategoriesChange',
						'mainmenu'=>'',
						'leftmenu'=>'',
						'url'=>'/portofolio/catchange.php?mainmenu=patastools&leftmenu=portofolio',
						'langs'=>'portofolio@portofolio',
						'position'=>110, 'enabled'=>'1',
						'perms'=>'$user->rights->portofolio->lire', 'target'=>'',
						'user'=>0);
		}

		if ($conf->project->enabled) {
			$r++;
			$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=patastools,fk_leftmenu=portofolio',
						'type'=>'left',
						'titre'=>'ProjectsAffect',
						'mainmenu'=>'',
						'leftmenu'=>'',
						'url'=>'/portofolio/projectaffect.php?mainmenu=patastools&leftmenu=portofolio',
						'langs'=>'portofolio@portofolio',
						'position'=>110, 'enabled'=>'1',
						'perms'=>'$user->rights->portofolio->lire', 'target'=>'',
						'user'=>0);
			$r++;

			$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=patastools,fk_leftmenu=portofolio',
						'type'=>'left',
						'titre'=>'TasksAffect',
						'mainmenu'=>'',
						'leftmenu'=>'',
						'url'=>'/portofolio/tasksaffect.php?mainmenu=patastools&leftmenu=portofolio',
						'langs'=>'portofolio@portofolio',
						'position'=>110, 'enabled'=>'1',
						'perms'=>'$user->rights->portofolio->lire', 'target'=>'',
						'user'=>0);
		}

		// additional tabs
		$this->tabs = array(
			'project:+portofolio:Portofolio:@portofolio:/portofolio/projectsaffect.php?id=__ID__',
			'user:+portofolio:Portofolio:@portofolio:/portofolio/useraffectproject.php?id=__ID__'
		);

	}

	/**
	 *		\brief	  Function called when module is enabled.
	 *					The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *					It also creates data directories.
	 *	  \return	 int			 1 if OK, 0 if KO
	 */
	function init($options='')
	{
		// Permissions
		$this->remove($options);

		$sql = array();

		//$result=$this->load_tables();

		return $this->_init($sql, $options);
	}

	/**
	 *		\brief		Function called when module is disabled.
	 *			  	Remove from database constants, boxes and permissions from Dolibarr database.
	 *					Data directories are not deleted.
	 *	  \return	 int			 1 if OK, 0 if KO
	 */
	function remove($options='')
	{
		$sql = array();
		return $this->_remove($sql, $options);
	}

	function load_tables()
	{
		return $this->_load_tables('/portofolio/sql/');
	}

	/*  Is the top menu already exist */
	function no_topmenu()
	{
		// gestion de la position du menu
		$sql="SELECT rowid FROM ".MAIN_DB_PREFIX."menu";
		$sql.=" WHERE mainmenu ='patastools'";
		//$sql.=" AND module ='patastools'";
		$sql.=" AND type = 'top'";
		$resql = $this->db->query($sql);
		if ($resql) {
			// il y a un top menu on renvoie 0 : pas besoin d'en créer un nouveau
			if ($this->db->num_rows($resql) > 0)
				return 0;
		}
		// pas de top menu on renvoie 1
		return 1;
	}

	function getChangeLog()
	{
		// Libraries
		dol_include_once("/".$this->name."/core/lib/patasmonkey.lib.php");
		return getChangeLog($this->name);
	}

	function getVersion($translated = 1)
	{
		global $langs, $conf;
		$currentversion = $this->version;

		if ($conf->global->PATASMONKEY_SKIP_CHECKVERSION == 1)
			return $currentversion;

		if ($this->disabled) {
			$newversion= $langs->trans("DolibarrMinVersionRequiered")." : ".$this->dolibarrminversion;
			$currentversion="<font color=red><b>".img_error($newversion).$currentversion."</b></font>";
			return $currentversion;
		}

		$context  = stream_context_create(array('http' => array('header' => 'Accept: application/xml')));
		$changelog = @file_get_contents(
						str_replace("www", "dlbdemo", $this->editor_web).'/htdocs/custom/'.$this->name.'/changelog.xml',
						false, $context
		);
		//$htmlversion = @file_get_contents($this->editor_web.$this->editor_version_folder.$this->name.'/');

		if ($htmlversion === false)	// not connected
			return $currentversion;
		else {
			$sxelast = simplexml_load_string(nl2br($changelog));
			if ($sxelast === false)
				return $currentversion;
			else
				$tblversionslast=$sxelast->Version;

			$lastversion = $tblversionslast[count($tblversionslast)-1]->attributes()->Number;

			if ($lastversion != (string) $this->version) {
				if ($lastversion > (string) $this->version) {
					$newversion= $langs->trans("NewVersionAviable")." : ".$lastversion;
					$currentversion="<font title='".$newversion."' color=orange><b>".$currentversion."</b></font>";
				} else
					$currentversion="<font title='Version Pilote' color=red><b>".$currentversion."</b></font>";
			}
		}
		return $currentversion;
	}


	function getLocalVersion()
	{
		global $langs;
		$context  = stream_context_create(array('http' => array('header' => 'Accept: application/xml')));
		$changelog = @file_get_contents(dol_buildpath($this->name, 0).'/changelog.xml', false, $context);
		$sxelast = simplexml_load_string(nl2br($changelog));

		if ($sxelast === false)
			return $langs->trans("ChangelogXMLError");
		else {
			$tblversionslast=$sxelast->Version;
			$currentversion = $tblversionslast[count($tblversionslast)-1]->attributes()->Number;
			$tblDolibarr=$sxelast->Dolibarr;
			$minversionDolibarr=$tblDolibarr->attributes()->minVersion;
			if (DOL_VERSION < $minversionDolibarr) {
				$this->dolibarrminversion=$minversionDolibarr;
				$this->disabled = true;
			}
		}
		return $currentversion;
	}
}
