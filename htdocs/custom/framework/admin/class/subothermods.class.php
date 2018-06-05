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


Class subOthermods {


	public function __construct( PageConfigSubModule $Master){

		$this->Master = $Master;
	}


	public function PrepareContext(){
		global $langs, $conf, $html;

	}

	/**
		@brief constructor
	*/
	public function DisplayPage(){
		global $langs, $conf, $html,$list;

		// Translations
		$langs->load("admin");
		$langs->load($this->Master->filelang);


		$required = json_decode( file_get_contents(dol_buildpath('/framework/core/requiredby.json' )));


		$list = array();

		if(is_array($required->requiredby))
			foreach($required->requiredby as $row )
			if( strtoupper($this->Master->originalmodule) != strtoupper($row->module) )
				$list[] = $row;


		dol_include_once( '/'.$this->Master->module .'/admin/tpl/othermodule.tpl');

	}

}
