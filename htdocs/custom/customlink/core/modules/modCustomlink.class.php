<?php
/* Copyright (C) 2014-2017	Charlie BENKE	<charlie@patas-monkey.com>
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
 * or see http://www.gnu.org/
 */

/**
 *	\defgroup	patasTools	 Module customLink
 *	\brief	  	Module to manage Links between dolibarr element
 *	\file		customLink/core/modules/modCustomlink.class.php
 *	\ingroup	PatasTools
 *	\brief		Fichier de description et activation du module de gestion des liens entre éléments
 */

include_once(DOL_DOCUMENT_ROOT ."/core/modules/DolibarrModules.class.php");

/**
 *	\class	modCustomlink
 *	\brief	Classe de description et activation du module customLink
 */
class modcustomlink extends DolibarrModules
{
	/**
	*   Constructor. Define names, constants, directories, boxes, permissions
	*
	*   @param	DoliDB		$db	  Database handler
	*/
	function __construct($db)
	{
		global $conf, $langs;

		$langs->load('customlink@customlink');

		$this->db = $db;
		$this->numero = 160080;

		$this->editor_name = "<b>Patas-Monkey</b>";
		$this->editor_web = "https://www.patas-monkey.com";

		$this->family = "Patas-Tools";

		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = $langs->trans("InfoCustomLinkModules");

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = $this->getLocalVersion();

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 0;
		$this->picto = $this->name."@".$this->name;

		// Data directories to create when module is enabled
		$this->dirs = array("/customlink/temp");

		// Config pages
		$this->config_page_url = array("setup.php@".$this->name);

		// Dependencies
		$this->depends = array();
		$this->requiredby = array();
		$this->conflictwith = array();
		$this->langfiles = array($this->name."@".$this->name);

		// Constantes
		$this->const = array(0=>array(
						'MAIN_SUPPORT_CONTACT_TYPE_FOR_THIRDPARTIES','chaine','1',
						'With this constants on, third party code type is present on contact type',0,'current',1
		));

		// hook pour la recherche  d'éléments et ajout d'une boite en bas
		$this->module_parts = array(
			'models' => 1,
			'hooks' => array('searchform','commonobject'),
			'css' => '/customlink/css/patastools.css'	   // Set this to relative path of css if module has its own css file
		);

		// Boites
//		$this->boxes = array();
//		$r=0;
//		$this->boxes[$r][1] = "box_customlink.php";

		// Permissions
		$this->rights = array();
		$this->rights_class = $this->name;
		$r=0;

		$this->rights[$r][0] = 160081;
		$this->rights[$r][1] = 'Lire des liens';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'lire';

		$r++;
		$this->rights[$r][0] = 160082;
		$this->rights[$r][1] = 'Creer/modifier des liens';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'creer';

		$r++;
		$this->rights[$r][0] = 160083;
		$this->rights[$r][1] = 'Supprimer des liens';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'supprimer';

		$r++;
		$this->rights[$r][0] = 160084;
		$this->rights[$r][1] = 'Exporter les liens';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'export';



		// Left-Menu of customLink module
		$r=0;
		// on crée le top menu si il n'existe pas
		if ($this->no_topmenu()) {
			$this->menu[$r]=array(	'fk_menu'=>'',
						'type'=>'top',
						'titre'=>'PatasTools',
						'mainmenu'=>'patastools',
						'leftmenu'=>'customlink',
						'url'=>'/customlink/core/patastools.php?mainmenu=patastools&leftmenu=customlink',
						'langs'=>'customlink@customlink',
						'position'=>100,
						'enabled'=>'1',
						'perms'=>'$user->admin',
						'target'=>'',
						'user'=>0);
			$r++; //1
		}
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=patastools',
					'type'=>'left',
					'titre'=>'CustomLink',
					'mainmenu'=>'patastools',
					'leftmenu'=>'customlink',
					'url'=>'/customlink/index.php?leftmenu=customlink',
					'langs'=>'customlink@customlink',
					'position'=>140,
					'enabled'=>'1',
					'perms'=>'1',
					'target'=>'',
					'user'=>2);
		$r++;
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=patastools,fk_leftmenu=customlink',
					'type'=>'left',
					'titre'=>'CreateLink',
					'mainmenu'=>'',
					'leftmenu'=>'',
					'url'=>'/customlink/fichelink.php?leftmenu=customlink',
					'langs'=>'customlink@customlink',
					'position'=>141,
					'enabled'=>'1',
					'perms'=>'1',
					'target'=>'',
					'user'=>2);
		$r++;
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=patastools,fk_leftmenu=customlink',
					'type'=>'left',
					'titre'=>'ListOfLinks',
					'mainmenu'=>'',
					'leftmenu'=>'',
					'url'=>'/customlink/listelink.php?leftmenu=customlink',
					'langs'=>'customlink@customlink',
					'position'=>142,
					'enabled'=>'1',
					'perms'=>'1',
					'target'=>'',
					'user'=>2);
		$r++;
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=patastools,fk_leftmenu=customlink',
					'type'=>'left',
					'titre'=>'CreateTag',
					'mainmenu'=>'',
					'leftmenu'=>'',
					'url'=>'/customlink/fichetag.php?leftmenu=customlink',
					'langs'=>'customlink@customlink',
					'position'=>143,
					'enabled'=>'1',
					'perms'=>'1',
					'target'=>'',
					'user'=>2);
		$r++;
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=patastools,fk_leftmenu=customlink',
					'type'=>'left',
					'titre'=>'ListOfTags',
					'mainmenu'=>'',
					'leftmenu'=>'',
					'url'=>'/customlink/listetag.php?leftmenu=customlink',
					'langs'=>'customlink@customlink',
					'position'=>144,
					'enabled'=>'1',
					'perms'=>'1',
					'target'=>'',
					'user'=>2);
//		$r++;
//		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=companies,fk_leftmenu=contacts',
//					'type'=>'left',
//					'titre'=>'ListOfContacts',
//					'mainmenu'=>'',
//					'leftmenu'=>'',
//					'url'=>'/customlink/listecontact.php?leftmenu=contacts',
//					'langs'=>'customlink@customlink',
//					'position'=>110,
//					'enabled'=>'1',
//					'perms'=>'1',
//					'target'=>'',
//					'user'=>2);
//		$r++;
//		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=home,fk_leftmenu=users',
//					'type'=>'left',
//					'titre'=>'ListOfUsers',
//					'mainmenu'=>'',
//					'leftmenu'=>'',
//					'url'=>'/customlink/listeuser.php?leftmenu=users',
//					'langs'=>'customlink@customlink',
//					'position'=>110,
//					'enabled'=>'1',
//					'perms'=>'1',
//					'target'=>'',
//					'user'=>2);
		$r++;
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=accountancy,fk_leftmenu=suppliers_bills',
					'type'=>'left',
					'titre'=>'ListOfVentilations',
					'mainmenu'=>'',
					'leftmenu'=>'',
					'url'=>'/customlink/listeventilation.php?leftmenu=suppliers_bills',
					'langs'=>'customlink@customlink',
					'position'=>100,
					'enabled'=>'1',
					'perms'=>'1',
					'target'=>'',
					'user'=>2);

//		$r++;
//		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=accountancy,fk_leftmenu=customers_bills',
//					'type'=>'left',
//					'titre'=>'BillsTracking',
//					'mainmenu'=>'',
//					'leftmenu'=>'',
//					'url'=>'/customlink/impayees.php?leftmenu=customers_bills',
//					'langs'=>'customlink@customlink',
//					'position'=>110,
//					'enabled'=>'1',
//					'perms'=>'1',
//					'target'=>'',
//					'user'=>2);

		// Additionnals customlink tabs in other modules
		$this->tabs = array(
			'thirdparty:+customlink:ExternalContact:@customlink:/customlink/tabs/societecontact.php?id=__ID__',
			'thirdparty:+externalbill:ExternalBills:@customlink:/customlink/tabs/paiement.php?id=__ID__',
			'thirdparty:+externalsupplierbill:ExternalSupplierBills:@customlink:/customlink/tabs/paiementfourn.php?id=__ID__',

			'contact:+customlink:AssociatedElement:@customlink:/customlink/tabs/contactelement.php?id=__ID__',
			'user:+customlink:AssociatedElement:@customlink:/customlink/tabs/userelement.php?id=__ID__',
			'factory:+customlink:InvoiceDivision:@customlink:/customlink/tabs/factoryventil.php?id=__ID__',
			'invoice:+customlink:InvoiceDivision:@customlink:/customlink/tabs/factureventil.php?id=__ID__',
			'supplier_invoice:+customlink:InvoiceDivision:@customlink:/customlink/tabs/facturefournventil.php?id=__ID__');

		// dictionnarys
		//--------

		//Exports
		//--------

		// Imports
		//--------

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
//		global $conf;

		// Permissions
		$this->remove($options);

		$sql = array();

		$this->load_tables();

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
		return $this->_load_tables('/customlink/sql/');
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
		return getChangeLog($this->name, $this->editor_web);
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
			if (version_compare(DOL_VERSION, $minversionDolibarr) < 0) {
				$this->dolibarrminversion=$minversionDolibarr;
				$this->disabled = true;
			}
		}
		return $currentversion;
	}
}
