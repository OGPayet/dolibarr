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
namespace CORE\FRAMEWORK;

use \Fixversion;
use \Form;

dol_include_once('/framework/class/fixversion.class.php');


Interface AutoTabsRequired{
	public function _Init();

	public function _Display();
}


Class AutoTabs {

	/**
	*/
	public
		$db
		/**
			@var array
		*/
		, $params
		/**
			@var string
		*/
		, $type
		/**
			@var object
		*/
		, $form
		/**
			@var string html put in banner
		*/
		,	$morehtmlref=''
		/**
			@var array
		*/
		,	$morejs=''
		/**
			@var array
		*/
		,	$morecss=''
		/**
			@var string html balise title of page
		*/
		, $TitlePage = ''
		/**
			@var object Fixversion class
		*/
		, $FV
		/**
			@var string, name of menu for construct adn display xxx.prepare_head
		*/
		, $printTabMenu=''
		/**
			@var array list of specific lib required for process
		*/
		, $libs=array(
			)

		;

	protected
		/**
			@var object
		*/
			$object
		/**
			@var object
		*/
		, $subobj


		;
	/**
	*/
	public function __construct($db){
		$this->db = $db ;
		$this->FV = new Fixversion($this->db);
	}


	/**
		@fn CardBanner()
		@brief
		@param
		@return
	*/
	public function CardBanner($path, $className){
// 	var_dump($path, $className);
		global $db ;
		$db = $this->db;
		$format = '/framework/tabs/'.$path.'generic.%s.class.php';

		if (dol_include_once(sprintf($format, 'current')))
			$className = $className;
		elseif (dol_include_once( sprintf($format, DOL_VERSION) ) )
				$className = $className;
		elseif (dol_include_once( sprintf($format, substr(DOL_VERSION, 0, -2)) ) )
				$className = $className;
		else{
			dol_include_once('/framework/class/generictabsforobjecttype.class.php');
			$className= 'ForObjectType';
		}

		$subclass = 'GenericTabs'.$className;

		$this->subobj = new $subclass($this->db);

	}

	/**
		@fn Process()
		@brief
		@return false
			false : error
	*/
	public function Process(){
		global $langs, $conf, $user;

		$res = $this->_Init();
		if(!$res)
			return false;
		elseif($res = 1 ) {
			$class = $this->FV->GetClassByType();
			if(empty($class) )
				return false;
			// load Specific context
			$this->object = new $class($this->db);
		}

// 		var_dump($this->type);

		$this->CardBanner($this->FV->GetPathByType(), $this->FV->GetClassByType());


		$this->subobj->PreProcess($this);

		$this->_Process();


	}




	/**
		@fn Display()
		@brief
		@param
		@return
	*/
	public function Display(){
		global $langs, $conf, $user;


		$this->form = $form = new Form($this->db);

		llxHeader('', $langs->trans($this->TitlePage), '', '', '', '', $this->morejs, $this->morecss, 0, 0);

// 		llxHeader('',$langs->trans($this->TitlePage),'');

		$this->_PrevBanner();


		$this->subobj->DisplayBanner($this, $this->object, $this->morehtmlref);


		$this->_Display();

		return true;
	}


	/**
		@fn GetObject()
		@brief
		@param
		@return
	*/
	public function GetObject(){
		return $this->object;
	}

	/**
		@fn CollectGetPost()
		@brief
		@param
		@return
	*/
	public function CollectGetPost($list = array()){
			if(count($list) <=0)
				$list = $this->refparam;

			foreach($list as $k=>$v){
// 				if($v=='alpha')
// 					$v = 'chaine';
// 				if($k ==='id' )
// 					if( is_string($this->FV->GetNameidByType($this->type) ) );
// 						$k = $this->FV->GetNameidByType($this->type);


				$this->params[$k] = GETPOST($k,$v) ;
			}

	}

	/**
		@fn GetParams()
		@brief
		@param string $key name of GETPOST var
		@return params or null
	*/
	public function GetParams($key){


		if( isset($this->params[$key]) ) {
			return $this->params[$key] ;
		}

		return null;
	}

	public function _Display(){}
	public function _Process(){}
	public function _PrevBanner(){}
}