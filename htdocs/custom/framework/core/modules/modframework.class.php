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
 * 	\defgroup   framework      Module framework
 * 	\brief      Supportof other module
 */
/**
 * 	\file       htdocs/includes/modules/modoscsshsopexts.class.php
 * 	\ingroup    framework
 * 	\brief      Fichier de description et activation du module de click to Dial
 * 	\author     Oscim <mail support@oscss-shop.fr>
 * 	\version    $Id: modCalling.php,v 1.56 2012/06/10 15:28:01 oscim Exp $
 */



if(!class_exists('DolModExts'))
dol_include_once('/framework/class/dolmodexts.class.php');

/**
  \class      modoscsshsopexts
  \brief      Classe de description et activation du module de Click to Dial
 */
class modframework
	extends DolModExts
{

    /**
     *   \brief      Constructeur. Definit les noms, constantes et boites
     *   \param      DB      handler d'acces base
     */
    function __construct($DB)
    {
				global $langs, $conf;

        $this->db     = $DB;
        $this->numero = 121014;

        // Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
        $this->name        = preg_replace('/^mod/i', '', get_class($this));

        $this->GetFileBuild();
        $this->loadOscssConf();


        $this->description = "Framework devellopped by oscss-shop";

        // Data directories to create when module is enabled
        $this->dirs = array("/framework");

        $this->phpmin = array(5, 3);

        $this->need_dolibarr_version = array(3, 5);



        $required = json_decode(file_get_contents(dol_buildPath('/framework/core/requiredby.json',0)));

        // Dependencies
        $this->depends = array();

        if (is_array($required->depends))
                foreach ($required->depends as $row)
                $this->depends[] = $row->module;

        $this->requiredby = array();

        if (is_array($required->requiredby))
                foreach ($required->requiredby as $row)
                $this->requiredby[] = $row->module;

        $this->conflictwith = array('modoscssshopexts');


				$this->module_parts = array(
						'hooks' => array(
													'main'
												, 'globalcard'
											),
					);



				$this->tabs = array(
            'modulebuildermodule:+framework:frameworkTitleTab:framework@framework:/framework/tabs/modulebuildermodule.framework.php?module=__ID__',
				);


        $this->const[$r][0] = "FRAMEWORKAPIKEYLINK";
        $this->const[$r][1] = "chaine";
        $this->const[$r][2] = "2FC3D1F725B83FBFAB12C5EBAD1A9";
        $this->const[$r][3] = 'API Key for registration in OScss-Shop Office';
        $this->const[$r][4] = 0;
        $r++;


        $this->const[$r] = array(
							0 => "MAIN_FEATURES_LEVEL"
						, 1 => "chaine"
						, 2 => "7"
						, 3 => "UseLevelOfFeaturesToShow"
						, 4 => 0
						, 9 => array(
									'method' => 'SelectSearchMode'
								, 'group' => 'setmainoptions'
								, 'arrayselect' => array(
										'-1'=>'stable+deprecated',
										'0'=>'stable only (default)',
										'1'=>'stable+experimental',
										'2'=>'stable+experimental+development',
									)
							)
						);
				$r++;


				$this->const[$r] = array(
							0 => "MAIN_MAIL_USE_MULTI_PART"
						, 1 => "chaine"
						, 2 => "7"
						, 3 => "MainMailUseMultiPart"
						, 4 => 0
						, 9 => array(
									'method' => 'SelectYesNo'
								, 'group' => 'setmainoptions'
							)
						);
				$r++;



				$this->const[$r] = array(
							0 => "THIRDPARTY_NOTSUPPLIER_BY_DEFAULT"
						, 1 => "chaine"
						, 2 => "7"
						, 3 => "ThirdpartyNotsupplierBydefault"
						, 4 => 0
						, 9 => array(
									'method' => 'SelectYesNo'
								, 'group' => 'setmainoptions'
							)
						);
				$r++;


				$this->const[$r] = array(
							0 => "MAIN_DIRECT_STATUS_UPDATE"
						, 1 => "chaine"
						, 2 => "7"
						, 3 => "MainDirectStatusUpdate"
						, 4 => 0
						, 9 => array(
									'method' => 'SelectYesNo'
								, 'group' => 'setmainoptions'
							)
						);
				$r++;

				$this->const[$r] = array(
							0 => "SUPPLIER_ORDER_DISABLE_STOCK_DISPATCH_WHEN_TOTAL_REACHED"
						, 1 => "chaine"
						, 2 => "7"
						, 3 => "SupplierOrderDisableStockDispatchWhenTotalReached"
						, 4 => 0
						, 9 => array(
									'method' => 'SelectYesNo'
								, 'group' => 'setmainoptions'
							)
						);
				$r++;


        /**
					@remarks End loaded config ans auto-config construct
        */
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

			return $this->_init($sql);
	}

	/**
	 * Function called when module is disabled.
	 * The remove function removes tabs, constants, boxes, permissions and menus from Dolibarr database.
	 * Data directories are not deleted
	 *
	 * @param      string	$options    Options when enabling module ('', 'noboxes')
	 * @return     int             		1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
			global $conf;
			$sql    = array(
			);
			return $this->_remove($sql, $options);
	}

}
