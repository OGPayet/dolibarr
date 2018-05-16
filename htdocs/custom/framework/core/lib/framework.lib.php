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
 * 	\file       htdocs/core/lib/framework.lib.php
 * 	\brief      Ensemble de fonctions de base pour le module
 * 	\ingroup    message
 */



  /**
 * 	@fn       loadClass( $className)
 * 	\brief      Chargemennt des class support
	@param string name for class
	@param string filename of class or null
 */
function loadClass(/* string */ $className,$filename='') {
	global $db, $user;

	if(empty($filename))
		$filename = $className;

	if (dol_include_once('/framework/class/' . DOL_VERSION . '/' . strtolower($filename) . '.class.php')) {
			$className = $className;
	}
	elseif (dol_include_once('/framework/class/' . substr(DOL_VERSION, 0, -2) . '/' . strtolower($filename) . '.class.php')) {
			$className = $className;
	}
	elseif (dol_include_once('/framework/class/current/' . strtolower($filename) . '.class.php')) {
		$className = $className;
	}
// 	else
	elseif (dol_include_once('/framework/class/' . DOL_VERSION . '/' . substr(strtolower($filename), 3 ) . '.class.php')) {
			$className = $className;
	}
	elseif (dol_include_once('/framework/class/' . substr(DOL_VERSION, 0, -2) . '/' . substr(strtolower($filename), 3 ) . '.class.php')) {
			$className = $className;
	}
	elseif (dol_include_once('/framework/class/' .strtolower($filename) . '.class.php')) {
			$className = $className;
	}
	else {
			return false;
	}


	return $className;
}

/**
 * 	\file       htdocs/core/lib/oscimmods.lib.php
 * 	\brief      Ensemble de fonctions de base pour le module message
 * 	\ingroup    message
 */
function newClass(/* string */ $className) {

    $numArgs = func_num_args();
    $argList = func_get_args();
    array_shift($argList);
    global $db, $user;

		$className = loadClass(/* string */ $className) ;


    if (class_exists($className)) {
        if ($numArgs == 1) {
            return new $className($db);
        } elseif ($numArgs > 1) {
            $r = new ReflectionClass($className);
            return $r->newInstanceArgs($argList);
        } else {
            return false;
        }
    }

    else {
        return false;
    }
}


global $conf;

if( version_compare(DOL_VERSION , '5.0', '<=') ){

	dol_include_once('/framework/core/lib/complet4.0.lib.php');
}
if( version_compare(DOL_VERSION , '4.0', '<=') ){

}