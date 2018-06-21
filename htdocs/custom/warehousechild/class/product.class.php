<?php
/*
 * Copyright (C) 2017		 Oscss-Shop       <support@oscss-shop.fr>.
 *
 * This program is free software; you can redistribute it and/or modifyion 2.0 (the "License");
 * it under the terms of the GNU General Public License as published bypliance with the License.
 * the Free Software Foundation; either version 3 of the License, or
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

namespace CORE\WAREHOUSECHILD;

dol_include_once('/warehousechild/class/warehousechild.class.php');

use \warehousechild as warehousechild;

/**
 * Description of product
 *
 * @author Oscss Shop <support@oscss-shop.fr>
 */
class Product extends \Product
{
    //put your code here
    function addFav($idFav)
    {
        $wc = new warehousechild($this->db);
        $wc->fetch($idFav);
        $wc->add_object_linked('product', $this->id);
    }

    //put your code here
    function delFav($idFav)
    {
        $wc = new warehousechild($this->db);
        $wc->fetch($idFav);
        $wc->deleteObjectLinked($this->id, 'product');
    }
}