<?php
/* Copyright (C) 2018       Open-DSI                <support@open-dsi.fr>
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
 */

/**
 *	\file       htdocs/module/lib/opendsi_tools.lib.php
 * 	\ingroup	module
 *	\brief      Common tools functions opendsi for the module
 */

/**
 *  Test if old and new value of the object is equal
 *
 * @param   object   $object            Object instance
 * @param   string   $property_name     Property name
 * @return  bool
 */
function opendsi_is_updated_property(&$object, $property_name) {
    if (!isset($object->oldcopy)) {
        return false;
    }

    return $object->oldcopy->$property_name != $object->$property_name;
}
