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
	@file    class/actions_framework.class.php
	@ingroup framework
	@brief   Hook for framework
		Support of Greffons
*/

dol_include_once('/framework/class/hookgreffon.class.php');

/**
	@class ActionsFramework
	@brief
*/
class ActionsFramework
	Extends HookGreffon {



	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          &$action        Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	function updateSession($parameters, &$object, &$action, $hookmanager)
	{


// 		$parameters['context'].= ':greffon:framework';
		return $this->{'___'.__FUNCTION__}($parameters, $object, $action, $hookmanager);
	}


	/**
		@fn GetTbl($table)
		@brief
	*/
	public function __call($name, $args) {
		global $conf;

		if(substr($name, 0,3) === '___')
			$name = substr($name, 3);
		else
			return 0;



			$return= 0 ;
			$args[0]['context'].= ':greffon:framework';
			$contexts = explode(':',$args[0]['context']);

			$this->initGreffon($contexts);

			if(in_array('greffon', $contexts) && in_array('framework', $contexts)  ){

			if(isset($this->greffon['framework']) && is_array($this->greffon['framework']))
				foreach($this->greffon['framework'] as $k=>$r){

					if(method_exists(get_class($r), $name) ){
						$result = $r->$name($args[0], $args[1], $args[2], $args[3]);

						if($result)
						foreach($r->results as $key=>$v)
							$res[$key] = $v;
					}
				}

				$this->resprints='';

				if(isset($args[0]['titles']) && is_array($args[0]['titles']))
				foreach($args[0]['titles'] as $k=>$arr){
					if(isset($res[$k]) ){
						$this->resprints .= $res[$k];
					}
					else
						$this->resprints .= $arr;
				}

				return 1;
			}

			return $return;

	}
}