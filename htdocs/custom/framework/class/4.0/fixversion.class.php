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


namespace CORE;
dol_include_once('/framework/class/fixversion.class.php');


namespace CORE\FRAMEWORK;
use \CORE\FRAMEWORK\Fixversion as Fixversion ;

class Fixversion
	extends \Fixversion{


	public $col = array(
			'bordereau_cheque'=>array(
					'ref'=>'number'
				)
			);

}

?>
