<?php
/* Copyright (C) 2013-2017	Charlie BENKE	<charlie@patas-monkey.com>
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
 *	\defgroup   factory	 Module gestion de la fabrication
 *	\brief	  Module pour gerer les process de fabrication
 *	\file	   htdocs/factory/core/modules/modFactory.class.php
 *	\ingroup	factory
 *	\brief	  Fichier de description et activation du module factory
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


/**
 *	Classe de description et activation du module Propale
 */
class modRestock extends DolibarrModules
{

	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param	  DoliDB		$db	  Database handler
	 */
	function __construct($db)
	{
		global $conf;

		$this->db = $db;
		$this->numero = 160320;

		$this->family = "products";


		$this->editor_name = "<b>Patas-Monkey</b>";
		$this->editor_web = "http://www.patas-monkey.com";

		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found )
		$this->name = "restock";
		$this->description = "Gestion du r&eacute;approvisionnement";

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = $this->getLocalVersion();

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 0;
		$this->picto='restock@restock';

		// hook pour la recherche et mod�les additionnels
		$this->module_parts = array(
			'hooks' => array('ordersuppliercard'),
			'models' => 1
		);


		// Dependancies
		$this->depends = array();
		$this->requiredby = array();
		$this->config_page_url = array("setup.php@restock");
		$this->langfiles = array("propal","order","project","companies","products","restock@restock");

		$this->need_dolibarr_version = array(3, 4);

		// Constants
		$this->const = array();
		$r=0;

		// Permissions
		$this->rights = array();
		$this->rights_class = 'restock';
		$r=0;

		$r++;
		$this->rights[$r][0] = 160321; // id de la permission
		$this->rights[$r][1] = 'Acc&egrave;s au reStock complet'; // libelle de la permission
		$this->rights[$r][2] = 'r'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 1; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'lire';

		$r++;
		$this->rights[$r][0] = 160322; // id de la permission
		$this->rights[$r][1] = 'Acc&egrave;s au reStock sur factures pay&eacute;es'; // libelle de la permission
		$this->rights[$r][2] = 'w'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'bill';
		$this->rights[$r][5] = 'use';

		$r++;
		$this->rights[$r][0] = 160323; // id de la permission
		$this->rights[$r][1] = 'Acc&egrave;s au reStock sur commandes clients'; // libelle de la permission
		$this->rights[$r][2] = 'c'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'order';
		$this->rights[$r][5] = 'use';

		// Restock Feature
		$r=0;
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=products,fk_leftmenu=product',
					'type'=>'left',
					'titre'=>'Restock',
					'mainmenu'=>'',
					'leftmenu'=>'',
					'url'=>'/restock/restock.php',
					'langs'=>'restock@restock',
					'position'=>100,
					'enabled'=>'1',
					'perms'=>'1',
					'target'=>'',
					'user'=>2);
		$r++;

		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=accountancy,fk_leftmenu=customers_bills',
					'type'=>'left',
					'titre'=>'RestockBill',
					'mainmenu'=>'',
					'leftmenu'=>'',
					'url'=>'/restock/restockFactClient.php',
					'langs'=>'restock@restock',
					'position'=>100,
					'enabled'=>'$user->rights->restock->bill->use',
					'perms'=>'1',
					'target'=>'',
					'user'=>2);
		$r++;

		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=commercial,fk_leftmenu=orders_suppliers',
					'type'=>'left',
					'titre'=>'ReStockOrders',
					'mainmenu'=>'',
					'leftmenu'=>'',
					'url'=>'/restock/restockCmdeClient.php',
					'langs'=>'restock@restock',
					'position'=>100,
					'enabled'=>'$user->rights->restock->order->use',
					'perms'=>'1',
					'target'=>'',
					'user'=>2);
		$r++;

		// pas encore d�velopp�
		if ($conf->global->MAIN_MODULE_FACTORY && false) {
			$this->menu[$r]=array( 'fk_menu'=>'fk_mainmenu=products,fk_leftmenu=factory',
						'type'=>'left',
						'titre'=>'FactoryRestock',
						'mainmenu'=>'',
						'leftmenu'=>'',
						'url'=>'/restock/factory.php',
						'langs'=>'restock@restock',
						'position'=>110, 'enabled'=>'1',
						'perms'=>'1', 'target'=>'',
						'user'=>2);
			$r++;
		}

		// additional tabs
		$this->tabs = array(
			  'product:+restock:RestockProduct:@Produit:/restock/tabs/restockProduct.php?id=__ID__'
			, 'order:+restock:RestockOrder:@order:/restock/tabs/restockCmdClient.php?id=__ID__'
			, 'order:+restocklink:RestockOrderLink:@order:/restock/tabs/restockCmdClientLink.php?id=__ID__'
			, 'supplier_order:+restock:RestockOrderLink:@order:/restock/tabs/restockCmdFournLink.php?id=__ID__'
			, 'project:+restock:RestockOrder:@order:/restock/tabs/restockCmdeProjet.php?id=__ID__'
			, 'factory:+restock:RestockOrder:@order:/restock/tabs/restockFactory.php?id=__ID__'
		);


		// Exports
		//--------
		$r=0;

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
		return $this->_load_tables('/restock/sql/');
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
		$changelog = @file_get_contents(dol_buildpath($this->name,0).'/changelog.xml', false, $context);
		$sxelast = simplexml_load_string(nl2br ($changelog));
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
?>
