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





Class ApiRegistration {

	/**
	*/
	public $url;
	/**
	*/
	public $api_key_base;
	/**
		@var
	*/
	public $ValueConst;
	/**
		@var public external presonnalied key
	*/
	public $apikey;
	/**
		@var public external prefix key
	*/
	public $apiprefix;
	/**
		@var
	*/
	public $submodisprev;
	/**
		@var
	*/
	public $subarray = array();

	/**
	*/
	public function __construct($url, $api_key, $ValueConst){
		$this->url = trim($url);
		$this->api_key_base = trim($api_key);
		$this->ValueConst = trim($ValueConst);
	}

	/**
		@brief Test a l'init pour verif master key and list module last activated
		@param $conf object dolibarr $conf var
		@return boolean treu / false for not ok
	*/
	public function CheckTestInit($conf){

		// fix key
		$this->SetConfigKey();


		// test master key
		$result = $this->Get(
			'accounts/'.$this->apiprefix.'/?api_key='.$this->apikey.'&dolibarrurl='.urlencode($_SERVER['SERVER_NAME'])
			,array()
			);

		if(!$result)
			return false;
		if(!is_array($result) )
			return false;
// 		if( $result['data'][0]['api_key'] != $this->apikey )
// 			return false;

		//
// 		echo 'submodule/?api_key='. $this->apikey.'&dolibarrurl='.urlencode($_SERVER['SERVER_NAME']);
		$result = $this->Get(
				'submodule/?api_key='. $this->apikey.'&dolibarrurl='.urlencode($_SERVER['SERVER_NAME'])
				,array()
				);
// 			print_r($result);
// 			exit;

		if(!$result)
			return false;

// 			global /*$submodisprev,*/ $currentmod;

// 			if(!empty($this->Master->originalmodule))
// 				$currentmod = $this->Master->originalmodule;
// 			else
// 				$currentmod = $this->Master->module;

			$this->subarray = @$result['data'];

			$this->submodisprev =/*$submodisprev =*/ true;
			foreach((array)@$result['data'] as $line){
// 				if($currentmod === $line['module'])d
					$this->submodisprev = /*$submodisprev = */ false;
			}

// 		print_r($this->subarray) ;


		return true;
	}

	/**
	*/
	public function SetRegistrationMaster($fields){
		$result = $this->Get(
			'registration/?api_key='.$this->api_key_base
			,$fields
			);

// 			print_r($result);
// 			exit;
			if(!isset($result['data'][0]['api_key']) || empty($result['data'][0]['api_key']) )
// 		if( $result['data'][0]['api_key'] != $this->apikey )
			return false;

		return $result['data'][0]['id'] . $result['data'][0]['api_key'];
	}

	/**
	*/
	public function SetRegistrationModule($fields){
	// fix key
		$this->SetConfigKey();


		$result = $this->Get(
			'submodule/?api_key='.$this->apikey.'&dolibarrurl='.urlencode($_SERVER['SERVER_NAME'])
			,$fields
			);
		if( $result['data'][0]['api_key'] != $this->apikey )
			return false;

		return $result['data'][0]['id'] . $result['data'][0]['api_key'];
	}

	/**
	*/
	public function GetApi(){

	}

	/**
		@fn SetConfigKey()
		@brief the value : $this->ValueConst is key, if is personal key, this is composedkey, but if is default or edotor key, this key is not composed
		@return none;
	*/
	protected function SetConfigKey(){
		if(strlen($this->ValueConst) >29 ){
			$this->apikey = substr($this->ValueConst,2);
			$this->apiprefix = substr($this->ValueConst,0,2);
		}
		else {
			$this->apikey = $this->ValueConst;
			$this->apiprefix = '00';
		}
	}

	/**
		@brief
		@param $fields
	*/
	public function Get( $url, $fields = array() ){
// 			echo $this->url .$url ;
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL, $this->url .$url );
		if( is_array($fields) && count($fields) ){

			foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
						rtrim($fields_string, '&');

			curl_setopt($ch,CURLOPT_POST, count($fields));
			curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
		}
		curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch,CURLOPT_HEADER, false);
		curl_setopt($ch,CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($ch,CURLOPT_CONNECTTIMEOUT, 120);
		curl_setopt($ch,CURLOPT_TIMEOUT, 120);

		$result = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);

		if(!$result)
			return false;

		$result = json_decode($result,true);

		return $result;
	}



// 	5C6494A713AA6599BB98475528B4C
// 0668cc65e7a38fdc3eee5336d8d4a34d98
}
