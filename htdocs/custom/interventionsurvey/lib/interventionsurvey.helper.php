<?php
/* Copyright (C) 2020 Alexis LAURIER
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
 * \file    interventionsurvey/lib/interventionsurvey.helper.php
 * \ingroup interventionsurvey
 * \brief   Library files with helper functions for InterventionSurvey
 */


/**
 * * Get element from an array according to an array of parameters set with "fieldName"=>valueToMatch
*/
function getItemFromThisArray(array &$array, array $arrayOfParameters = array(), bool $returnPosition = false)
    {
        $result = null;
        foreach ($array as $index => &$item) {
            $test = true;
            foreach ($arrayOfParameters as $fieldName => $searchValue) {
                if (!($item->$fieldName == $searchValue)) {
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


//Function to get object from an array having the same id field than the given parameter
function getItemWithSameFieldsValue(array &$array, &$object, array &$fieldName = array('id'), $returnPosition = false){
    $parameters = array();
    foreach($fieldName as $name){
        $parameters[$name] = $object->$name;
    }
    return getItemFromThisArray($array,$parameters, $returnPosition);
}


//Function to get missing item into the second array according to the first array identified by an array of field
function getMissingItem(array &$oldData, array &$newData, array &$arrayOfIdentifierField = array('id')){
    $missingItems = array();
    foreach($oldData as $index=>&$oldObject){
        $newObject = getItemWithSameFieldsValue($newData, $oldObject,$arrayOfIdentifierField);
        if(!$newObject){
            //Item is missing
            $missingItems[$index] = $oldObject;
        }
    }
    return $missingItems;
}

//Function to get missing item into the second array according to the first array identified by an array of field
function getCommonItem(array &$oldData, array &$newData, array $arrayOfIdentifierField = array('id')){
    $commonItem = array();
    foreach($oldData as &$oldObject){
        $newObjectPosition = getItemWithSameFieldsValue($newData, $oldObject,$arrayOfIdentifierField, true);
        if($newObjectPosition !== null){
            //Item is common
            $commonItem[$newObjectPosition]["oldObject"] = $oldObject;
            $commonItem[$newObjectPosition]["newObject"] = $newData[$newObjectPosition];
        }
    }
    return $commonItem;
}


//Function to get a list of item that have been deleted between oldData and newData based on their field name. Both parameters are array
function getDeletedItem(array &$oldData,array &$newData, array &$arrayOfIdentifierField){
    return getMissingItem($oldData, $newData, $arrayOfIdentifierField);
}

//Function to get a list of item that have been added between oldData and newData based on their field name. Both parameters are array
function getAddedItem(array &$oldData,array &$newData, array &$arrayOfIdentifierField){
    return getMissingItem($newData, $oldData, $arrayOfIdentifierField);
}

//Function to get a list of item that are in both array based on their field name. Return an array of array("oldObject"=>oldObject, "newObject"=>newObject)

function getItemToUpdate(array &$oldData,array &$newData, array &$arrayOfIdentifierField){
    return getCommonItem($oldData, $newData, $arrayOfIdentifierField);
}

//generic function to merge two object and launch merge options on sub object
//array of parameters must be like this :
// "propertyContainingArrayOfObjectInBothObject"=>array(
//       "identifierPropertiesName"=>array(nameOfThePropertyToIdentifySubItem1,nameOfThePropertyToIdentifySubItem2),
//       "mergeSubItemNameMethod"=>nameOfTheMethodToUpdateSubObject))

function mergeSubItemFromObject(&$user, &$oldObject, &$newObject, array &$arrayOfParameters, bool $saveUpdatedItemToBdd = false, bool $noTrigger = false){
    $errors = array();
    foreach($arrayOfParameters as $propertyContainingArrayOfObjectInBothObject=>$parameters){
        $subObjectIdentifiersField = $parameters["identifierPropertiesName"];
        $itemToUpdate = getItemToUpdate($oldObject->$propertyContainingArrayOfObjectInBothObject, $newObject->$propertyContainingArrayOfObjectInBothObject, $subObjectIdentifiersField);
        $itemToDelete = getDeletedItem($oldObject->$propertyContainingArrayOfObjectInBothObject, $newObject->$propertyContainingArrayOfObjectInBothObject, $subObjectIdentifiersField);
        $itemToAdd = getAddedItem($oldObject->$propertyContainingArrayOfObjectInBothObject, $newObject->$propertyContainingArrayOfObjectInBothObject, $subObjectIdentifiersField);
        foreach($itemToDelete as $index=>&$item){
            $item->delete($user, $noTrigger);//We delete item in bdd
            unset($oldObject->{$propertyContainingArrayOfObjectInBothObject}[$index]);//We remove item from memory
            $errors = array_merge($errors,$item->errors);
        }
        foreach($itemToAdd as &$item){
            $item->save($user);
            $errors = array_merge($errors,$item->errors);
        }
        unset($itemToDelete);
        unset($itemToAdd);
        foreach($itemToUpdate as $position => &$coupleOfItem){
            $oldItem = &$coupleOfItem["oldObject"];
            $newItem = &$coupleOfItem["newObject"];
            $nameOfTheMergeMethodOfTheseObject = $parameters["mergeSubItemNameMethod"];
            if(method_exists($oldItem, $nameOfTheMergeMethodOfTheseObject)){
                $oldItem->{$nameOfTheMergeMethodOfTheseObject}($user, $newItem, $saveUpdatedItemToBdd, $position, $noTrigger);
                $errors = array_merge($errors,$oldItem->errors);
            }
        }
    }
    return $errors;
}

    function isUserLinkedToThisCompanyDirectlyOrBySalesRepresentative(User &$user, Societe &$company, int $id = null){
        if(!$id){
            $id = $company->id ?? -1;
        }
        if($id == $user->socid){
            return true;
        }
        else
        {
            if(!$id){
                $company->fetch($id);
            }
            $listOfSalesRepresentative = $company->getSalesRepresentatives($user);
            return in_array($user->socid,$listOfSalesRepresentative);
        }
    }
    function fetchACompanyObjectById(int $id = null, $db){
        if($id){
            dol_include_once('/societe/class/societe.call.php');
            $company = new Societe($db);
            $company->fetch($id);
            return $company;
        }
    }


    /**
     * Fetch parent object common
     */

    function fetchParentCommon($classname, $id, &$field, &$db)
    {
        if (!$field) {
            $parent = new $classname($db);
            if ($parent->fetch($id) > 0) {
                $field = $parent;
            }
        }
        if (method_exists($field, "fetchParent")) {
            $field->fetchParent();
        }
    }


    /**
     * Load object in memory from the database
     *
     * @param	string	$morewhere		More SQL filters (' AND ...')
     * @return 	int         			<0 if KO, 0 if not found, >0 if OK
     */
    function interventionSurveyFetchLinesCommon($morewhere = '', $objectlineclassname = null, &$resultValue, &$context)
    {

        if (!class_exists($objectlineclassname)) {
            $context->errors[] = 'Error, class ' . $objectlineclassname . ' not found during call of fetchLinesCommon';
            return -1;
        }

        $objectline = new $objectlineclassname($context->db);

        $sql = 'SELECT ' . $objectline->getFieldList();
        $sql .= ' FROM ' . MAIN_DB_PREFIX . $objectline->table_element;
        $sql .= ' WHERE fk_' . $context->element . ' = ' . $context->id;
        if ($morewhere)   $sql .= $morewhere;

        $resql = $context->db->query($sql);
        if ($resql) {
            $num_rows = $context->db->num_rows($resql);
            $i = 0;
            while ($i < $num_rows) {
                $obj = $context->db->fetch_object($resql);
                if ($obj) {
                    $newline = new $objectlineclassname($context->db);
                    $newline->setVarsFromFetchObj($obj);
                    if (method_exists($newline, "fetchLines")) {
                        $newline->fetchLines($context);
                    }
                    $resultValue[] = $newline;
                    $context->errors = array_merge($context->errors, $newline->errors);
                }
                $i++;
            }
        } else {
            $context->errors[] = $context->db->lasterror();
            return -1;
        }
        return empty($context->errors) ? 1 : -1;
    }
