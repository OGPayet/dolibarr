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




dol_include_once('/framework/class/apiregistration.class.php');


/**
	@class subRegistration
	@brief Thsis contain all methode for registration framework in provider , and declared all module producted by this editor
*/
Class subRegistration {

	/**
		@var param for view in tpl file segment of report name of current module for regsitration current module
	*/
	static public $submodisprev = true;
	/**
	*/
	public $apiregis;

	/**
	@fn __construct( PageConfigSubModule $Master)
	@brief
	@return none
	*/
	public function __construct( PageConfigSubModule $Master){

		$this->Master = $Master;

		$this->result = array();

		$this->urlapi = '';


		if(file_exists(  dol_buildPath( '/'.$this->Master->module .'/core/build.json', 0) ) )
				$build = json_decode( file_get_contents(dol_buildPath( '/'.$this->Master->module .'/core/build.json', 0) ) );
		elseif(file_exists(  dol_buildPath( '/framework/core/build.json', 0) ) )
		$build = json_decode( file_get_contents(dol_buildPath( '/framework/core/build.json', 0) ) );

		if(is_object($build)){
			global $conf;

			// editor
			$this->editor_name = $build->editor->name;
			// For update linked
			$this->urlapi = $build->editor->api;
			$this->Master->api_key = $this->api_key = $build->editor->apikey;


			$this->Master->cstname = "FRAMEWORKAPIKEYLINK".strtoupper(str_replace(' ', '', trim($this->editor_name)));

			$this->apiregis = new ApiRegistration( $build->editor->api, $build->editor->apikey, $conf->global->{$this->Master->cstname} );

		}

	}

	/**
		@fn PrepareContext()
		@brief
		@return none
		*/
	public function PrepareContext() {
		global $langs, $conf, $html, $mysoc, $result,  $master, $subarray;

		$html = new Form($this->Master->db);


		if (GETPOST("action") == 'setvalue') {
				// Data transmise à l'api de l'editeur
			$fields = array(
				'dolibarrurl' => urlencode(GETPOST('registrationurldoli')),
				'dolibarrip' => urlencode(GETPOST('registrationipdoli')),
				'company' => urlencode(GETPOST('registrationcompanydoli')),
				'email' => urlencode(GETPOST('registrationemaildoli')),
				'lang' => $langs->defaultlang
			);

			$result = $this->apiregis->SetRegistrationMaster( $fields );

			if( !$result )
				setEventMessages($langs->trans("FrameworkapikeyApiKeyRegisterNotOk", $this->editor_name), null, 'errors');
			else{

				dolibarr_set_const($this->Master->db,GETPOST("registrationnamekey"), strtoupper($result), 'chaine', 0, '', $conf->entity);

				setEventMessages($langs->trans("FrameworkapikeyApiKeyRegisterOk", $this->editor_name), null, 'mesgs');
			}
		}
		elseif (GETPOST("action") == 'check') {

			if( $this->apiregis->CheckTestInit($conf) )
				setEventMessages($langs->trans("FrameworkapikeyApiKeyOk"), null, 'mesgs');
			else{
				setEventMessages($langs->trans("FrameworkapikeyApiKeyNotOk"), null, 'warnings');

				dolibarr_set_const($this->Master->db, $this->Master->cstname, $this->Master->api_key, 'chaine', 0, '', $conf->entity);
			}
		}
		elseif (GETPOST("action") == 'addmod') {

			$fields = array(
				'module' => urlencode(GETPOST('newmod'))
			);

				$result = $this->apiregis->SetRegistrationModule($fields);
			if( $result['data'][0]['module'] == substr($conf->global->{$this->Master->cstname},2) )
				setEventMessages($langs->trans("FrameworkapikeyApiKeyOk"), null, 'mesgs');
		}

		// Fixed State registration
		$this->Check($conf);

	}

	/**
		@fn DisplayPage()
		@brief
		@return none
		*/
	public function DisplayPage() {
			global $langs, $conf, $html, $result, $currentmod,  $master, $subarray;

			// Translations
			$langs->load("admin");
			$langs->load($this->Master->filelang);

			$master = $this->Master;
			$subarray =  $this->apiregis->subarray;
			$currentmod = $this->Master->originalmodule;

			dol_include_once('/' . $this->Master->module . '/admin/tpl/' . $this->Master->currentpage . '.tpl');
	}


	/**
		@fn Check($conf)
		@brief CHeck test in Api Key, and list submodule last registration
		@return none
	*/
	protected function Check($conf){
		$r = $this->apiregis->CheckTestInit($conf);

		if(isset($this->apiregis->subarray) ){
			foreach($this->apiregis->subarray as $k=>$v)
				if( $v['module']==$this->Master->module)
					self::$submodisprev = false;
		}
	}


}
