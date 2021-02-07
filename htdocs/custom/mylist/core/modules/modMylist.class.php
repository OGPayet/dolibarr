<?php
/* Copyright (C) 2013-2017	Charlie benke	<charlie@patas-monkey.com>
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
 *  \defgroup   projet	 Module Mylist
 *	\brief	  Module to Manage Dolibarr Lists
 *  \file	   htdocs/core/modules/modMylist.class.php
 *	\ingroup	projet
 *	\brief	  Fichier de description et activation du module Mylist
 */

include_once(DOL_DOCUMENT_ROOT ."/core/modules/DolibarrModules.class.php");


/**
 *	Classe de description et activation du module Projet
 */
class modmylist extends DolibarrModules
{
	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param	  DoliDB		$db	  Database handler
	 */
	function __construct($db)
	{
		global $conf, $langs;

		$langs->load('mylist@mylist');

		$this->db = $db;
		$this->numero = 160210;

		$this->family = "Patas-Tools";

		$this->editor_name = "<b>Patas-Monkey</b>";
		$this->editor_web = "http://www.patas-monkey.com";

		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found
		$this->name = preg_replace('/^mod/i', '', get_class($this));

		$this->description = $langs->trans("InfoModulesMyList");

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = $this->getLocalVersion();

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 0;
		//$this->config_page_url = array("mylist.php@mylist");
		$this->picto= $this->name.'.png@'.$this->name;

		// Set this to relative path of css if module has its own css file
		$this->module_parts = array(
			'css' => '/mylist/css/patastools.css',
			'models' => 1
		);

		// Data directories to create when module is enabled
		$this->dirs = array("/mylist/temp");

		// Config pages. Put here list of php page, stored into webmail/admin directory, to use to setup module.
		$this->config_page_url = array("admin.php@".$this->name);

		// Dependancies
		$this->depends = array();
		$this->requiredby = array();

		// Constants
		$this->const = array();
		$r=0;

		// par d�faut le nombre de ligne par parge est de 25
		$conf->global->MYLIST_NB_ROWS =25;

		// Permissions
		$this->rights = array();
		$this->rights_class = $this->name;
		$r=0;

		$r++;
		$this->rights[$r][0] = 160211; // id de la permission
		$this->rights[$r][1] = "Lire les listes personnalis&eacute;es"; // libelle de la permission
		$this->rights[$r][2] = 'r'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 1; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'lire';

		$r++;
		$this->rights[$r][0] = 160212; // id de la permission
		$this->rights[$r][1] = "Administrer les listes personnalis&eacute;es"; // libelle de la permission
		$this->rights[$r][2] = 'w'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'setup';

		$r++;
		$this->rights[$r][0] = 160213; // id de la permission
		$this->rights[$r][1] = "Modifier les listes personnalis&eacute;es"; // libelle de la permission
		$this->rights[$r][2] = 'c'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'creer';

		$r++;
		$this->rights[$r][0] = 160214; // id de la permission
		$this->rights[$r][1] = "Supprimer les listes personnalis&eacute;es"; // libelle de la permission
		$this->rights[$r][2] = 'd'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'supprimer';

		// Left-Menu of Equipement module
		$r=0;
		if ($this->no_topmenu()) {
			$this->menu[$r]=array('fk_menu'=>0,
						'type'=>'top',
						'titre'=>'PatasTools',
						'mainmenu'=>'patastools',
						'leftmenu'=>'mylist',
						'url'=>'/mylist/core/patastools.php?mainmenu=patastools&leftmenu=mylist',
						'langs'=>'mylist@mylist',
						'position'=>100, 'enabled'=>'1',
						'perms'=>'$user->admin',
						'target'=>'', 'user'=>0);
			$r++; //1
		}
		$this->menu[$r]=array('fk_menu'=>'fk_mainmenu=patastools',
					'type'=>'left',
					'titre'=>'myList',
					'mainmenu'=>'patastools',
					'leftmenu'=>'mylist',
					'url'=>'/mylist/index.php',
					'langs'=>'mylist@mylist',
					'position'=>110, 'enabled'=>'1',
					'perms'=>'$user->rights->mylist->setup',
					'target'=>'', 'user'=>2);
		$r++;
		$this->menu[$r]=array('fk_menu'=>'fk_mainmenu=patastools,fk_leftmenu=mylist',
					'type'=>'left',
					'titre'=>'NewList',
					'mainmenu'=>'', 'leftmenu'=>'',
					'url'=>'/mylist/card.php?action=create',
					'langs'=>'mylist@mylist',
					'position'=>110, 'enabled'=>'1',
					'perms'=>'$user->rights->mylist->setup',
					'target'=>'', 'user'=>2);
		$r++;
		$this->menu[$r]=array('fk_menu'=>'fk_mainmenu=patastools,fk_leftmenu=mylist',
					'type'=>'left',
					'titre'=>'mylist',
					'mainmenu'=>'', 'leftmenu'=>'',
					'url'=>'/mylist/list.php',
					'langs'=>'mylist@mylist',
					'position'=>110, 'enabled'=>'1',
					'perms'=>'$user->rights->mylist->setup',
					'target'=>'', 'user'=>2);
		$r++;
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=patastools,fk_leftmenu=mylist',
					'type'=>'left',
					'titre'=>'ImportList',
					'mainmenu'=>'', 'leftmenu'=>'',
					'url'=>'/mylist/card.php?action=importexport',
					'langs'=>'mylist@mylist',
					'position'=>110, 'enabled'=>'1',
					'perms'=>'$user->rights->mylist->setup',
					'target'=>'', 'user'=>2);
	}


	/**
	 *		Function called when module is enabled.
	 *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *		It also creates data directories
	 *
	 *	  @param	  string	$options	Options when enabling module ('', 'noboxes')
	 *	  @return	 int			 	1 if OK, 0 if KO
	 */
	function init($options='')
	{
		global $conf;

		// Permissions
		$this->remove($options);

		$sql = array();
		$result=$this->load_tables();

		return $this->_init($sql, $options);
	}

	/**
	 *		Function called when module is disabled.
	 *	  Remove from database constants, boxes and permissions from Dolibarr database.
	 *		Data directories are not deleted
	 *
	 *	  @param	  string	$options	Options when enabling module ('', 'noboxes')
	 *	  @return	 int			 	1 if OK, 0 if KO
	 */
	function remove($options='')
	{
		$sql = array();

		return $this->_remove($sql, $options);
	}

	/**
	 *		Create tables, keys and data required by module
	 * 		Files llx_table1.sql, llx_table1.key.sql llx_data.sql with create table, create keys
	 * 		and create data commands must be stored in directory /mymodule/sql/
	 *		This function is called by this->init.
	 *
	 * 		@return		int		<=0 if KO, >0 if OK
	 */
	function load_tables()
	{
		return $this->_load_tables('/mylist/sql/');
	}

	/*  Is the top menu already exist */
	function no_topmenu()
	{
		global $conf;
		// gestion de la position du menu
		$sql="SELECT rowid FROM ".MAIN_DB_PREFIX."menu";
		$sql.=" WHERE mainmenu ='patastools'";
		//$sql.=" AND module ='patastools'";
		$sql.=" AND entity = ".(int) $conf->entity;
		$sql.=" AND type = 'top'";
		$resql = $this->db->query($sql);
		if ($resql) {
			// il y a un top menu on renvoie 0 : pas besoin d'en cr�er un nouveau
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
		$changelog = file_get_contents(dol_buildpath($this->name, 0).'/changelog.xml', false, $context);
		$sxelast = simplexml_load_string(nl2br($changelog));
		if ($sxelast === false) {
			foreach (libxml_get_errors() as $error)
				$title.="-".$error->message;

			return '<a href=# title="'.$title.'">'.$langs->trans("ChangelogXMLError").'</a>';
		} else {
			$tblversionslast=$sxelast->Version;
			$currentversion = $tblversionslast[count($tblversionslast)-1]->attributes()->Number;
			$tblDolibarr=$sxelast->Dolibarr;
			$minversionDolibarr=$tblDolibarr->attributes()->minVersion;
			if (DOL_VERSION < $minversionDolibarr) {
				$this->dolibarrminversion=$minversionDolibarr;
				//$this->disabled = true;
			}
		}
		return $currentversion;
	}
}
