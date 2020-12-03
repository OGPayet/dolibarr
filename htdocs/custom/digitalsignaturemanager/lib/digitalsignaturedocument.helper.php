<?php
/* Copyright (C) 2020 Alexis LAURIER <alexis@alexislaurier.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    lib/digitalsignaturemanager_digitalsignaturerequest.lib.php
 * \ingroup digitalsignaturemanager
 * \brief   Library files with common functions for DigitalSignatureRequest
 */

 /**
  * Function to find an object in an array of object thanks to a property name and value
  * @param array $arrayOfObject array of object on which search object
  * @param string $propertyName property name of object to test value on
  * @param any $propertyValue value of property to find
  * @return null|any
  */
function findObjectInArrayByProperty(&$arrayOfObject, $propertyName, $propertyValue)
{
	return getItemFromThisArray($arrayOfObject, array($propertyName=>$propertyValue));
}
/**
 * Custom compare value with soft value comparaison if both value are not null
 * @param any $a first value
 * @param any $b second value to compare
 * @return boolean
 */
function compareValues($a, $b)
{
    if ($a === null || $b === null) {
        return $a === $b;
    } else {
        return $a == $b;
    }
}


/**
 * * Get element from an array according to an array of parameters set with "fieldName"=>valueToMatch
  * @param array $arrayOfObject array of object on which search object
  * @param array $arrayOfPropertyNameAndValue property name and searched of object to test value on
  * @param boolean $returnPosition return position of object in array instead of the object
  * @return null|any
 */
function getItemFromThisArray(&$array, array $arrayOfParameters = array(), bool $returnPosition = false)
{
    $result = null;
    foreach ($array as $index => &$item) {
        $test = false;
        foreach ($arrayOfParameters as $fieldName => $searchValue) {
            if(is_array($item)){
                $itemValue = $item[$fieldName];
            }
            else
            {
                $itemValue = $item->$fieldName;
            }
            if (compareValues($itemValue, $searchValue)) {
                $test = true;
            } else {
                $test = false;
                break;
            }
        }
        if ($test) {
            $result = $index;
            break;
        }
    }
    return $returnPosition ? $result : $array[$result];
}
