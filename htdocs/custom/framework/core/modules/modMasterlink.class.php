<?php
/* Copyright (C) 2016		 Oscss-Shop       <support@oscss-shop.fr>
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
 *	\defgroup   masterkink
 *	\brief
 */

/**
 *	\file       /framework/core/modules/modMasterlink.class.php
 *	\ingroup    masterlink / framework
 */



global $conf;
if (isset($conf->framework) && $conf->framework->enabled) {
    dol_include_once('/framework/class/dolmodexts.class.php');
    global $langs;
    $langs->load('framework');
    class_alias('DolModExts', 'DolibarrMoMa');
}
else{
	include_once(DOL_DOCUMENT_ROOT ."/core/modules/DolibarrModules.class.php");
	class_alias('DolibarrModules', 'DolibarrMoMa');
}


/**
 \class      modMasterlink
 \brief      Classe de description et activation du module
 */

class modMasterlink
	extends DolibarrMoMa
{

	/**
		@var greffon
	*/
	public $greffon = 1;
	/**
		@var greffon parent string
	*/
	public $herit = 'framework';

	/**
	 *   \brief      Constructeur. Definit les noms, constantes et boites
	 *   \param      DB      handler d'acces base
	 */
	function __construct($DB)
	{
		global $langs, $conf;

		$this->db = $DB ;
		$this->numero = 121013 ;


		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "Masterlink";


		if (isset($conf->framework) && $conf->framework->enabled) {
			$this->GetFileBuild();
			$this->loadOscssConf();
			// Boites
// 			$this->loadbox('/'.$this->code.'/core/boxes/', '\CORE\MASTERLINK\\');
		}
		else
			// Boites
			$this->boxes = array();

			$this->FixGreffon();


		// Data directories to create when module is enabled
		$this->dirs = array("/Masterlink");


		// Dependencies
		$this->depends = array('modframework' );
		$this->requiredby = array();

		$this->conflictwith = array();

		// Defined all module parts (triggers, login, substitutions, menus, etc...) (0=disable,1=enable)
		$this->module_parts = array(
				 'hooks' => array(
										'greffon'
										, 'framework'
										, 'main'
					),

					'greffon'=>array('framework')
			);


			/**
				@remarks End loaded config ans auto-config construct
			*/
			if (isset($conf->framework) && $conf->framework->enabled)
			$this->EndLoader();
	}

		/**
	 * Function called when module is enabled.
	 * The init function adds tabs, constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 * It also creates data directories
	 *
	 * @param string $options   Options when enabling module ('', 'newboxdefonly', 'noboxes')
     *                          'noboxes' = Do not insert boxes
     *                          'newboxdefonly' = For boxes, insert def of boxes only and not boxes activation
	 * @return int				1 if OK, 0 if KO
	 */
	public function init($options = '')
		{
			global $conf;

			$sql = array();

			$this->_load_tables('/framework/sql/');

			return $this->_init($sql);
		}

    /**
     *      \brief      Function called when module is disabled.
     *                  Remove from database constants, boxes and permissions from Dolibarr database.
     *                  Data directories are not deleted.
     *      \return     int             1 if OK, 0 if KO
     */
		function remove($options = '')
		{
		global $conf;

		$sql = array(
		);

		return $this->_remove($sql, $options);
		}



		/**
			@fn FixGreffon()
			@brief Specific for greffon by framework engine
			@return none
		*/
		public function FixGreffon(){

			// No listen in general config module
			$this->special = 5;
			$this->hidden = true;

			$this->family = "Configuration";
		}

		/**
			@fn ListFile()
			@brief Specific for greffon by framework engine
			@return none
		*/
    public function ListFile(){
			$this->files = array(
				 '/core/modules/modMasterklink.php'
				,'/class/action_masterlink.class.php'
				,'/class/html.masterlink.class.php'
				,'/class/masterlink.class.php'
				,'/sql/llx_masterlink.key.sql'
				,'/sql/llx_masterlink.sql'

// 				,'/ChangeLog'

			);


    }
}
