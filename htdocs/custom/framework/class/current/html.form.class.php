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

// dol_include_once('/compta/facture/class/facture.class.php'); // used for constant
// dol_include_once('/product/class/product.class.php'); // used for invoice supplier
//


namespace CORE;
dol_include_once('/core/class/html.form.class.php');


namespace CORE\FRAMEWORK;
use \CORE\FRAMEWORK\Form as Form ;



class Form
	extends \Form{


    public $OSE_loaded_version = DOL_VERSION;

}
