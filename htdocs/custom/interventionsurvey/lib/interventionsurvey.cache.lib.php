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
 * Function to fill cache of items fetched with sql filter
 * @param object $object instance of object to be fetched
 * @param DoliDB $db Database instance
 * @param array $cache cache value to save result too
 * @param string $sqlFilter sql filter in order to limit number of object fetch
 * @return array value sql result fetch by this request
 */

function commonLoadCacheForItemWithFollowingSqlFilter($object, $db, &$cache, $sqlFilter)
{
    $sql = 'SELECT ' . $object->getFieldList();
    $sql .= ' FROM ' . MAIN_DB_PREFIX . $object->table_element;
    if($sqlFilter) {
        $sql .= $sqlFilter;
    }

    $errors = array();

    $resql = $db->query($sql);
    if ($resql) {
        $num_rows = $db->num_rows($resql);
        $i = 0;
        while ($i < $num_rows) {
            $obj = $db->fetch_object($resql);
            if ($obj) {
                $cache[$obj->rowid] = $obj;
            }
            $i++;
        }
    } else {
        $errors[] = $db->lasterror();
        return -1;
    }
    $object->errors = array_merge($object->errors, $errors);
    return empty($errors) ? 1 : -1;
}
/**
 * Function to fill cache of ids of linked object
 * @param array $cacheOfLinkedObjectIds array of linked property ids and object ids
 * @param string $fieldNameOfElementToBeGrouped property key
 * @param array $cacheOfObject array of sql result object
 * @return array $cacheOfLinkedObjectIds
 */

function commonLoadCacheIdForLinkedObject(&$cacheOfLinkedObjectIds, $fieldNameOfElementToBeGrouped, &$cacheOfObject)
{
	foreach($cacheOfObject as $id=>$object) {
        $linkedId = $object->$fieldNameOfElementToBeGrouped;
        if(!$cacheOfLinkedObjectIds[$linkedId]){
            $cacheOfLinkedObjectIds[$linkedId] = array();
        }
        $cacheOfLinkedObjectIds[$linkedId][$id] = true;
    }
    return $cacheOfLinkedObjectIds;
}

/**
 * Function to remove staled cache data
 * @param number $objectId object id to remove cache
 * @param array $cacheOfObject cache of object
 * @param array $arrayOfCacheLinkedObjectIdsAndPropertyValue array of linkedObject ids and property value  array(array('propertyValue'=>linkedObjectIdsOfDeletedObject, 'cacheLinkedIds'=>cache of linked ids))
 * @return void
 */
function removeStaledDataCache($objectId, &$cacheOfObject, &$arrayOfCacheLinkedObjectIdsAndPropertyValue)
{
    if(is_array($cacheOfObject) && $cacheOfObject[$objectId]){
        unset($cacheOfObject[$objectId]);
    }
    foreach($arrayOfCacheLinkedObjectIdsAndPropertyValue as &$cacheLinkedObjectIdsAndPropertyValue) {
        $propertyValue = $cacheLinkedObjectIdsAndPropertyValue['propertyValue'];
        $arrayOfCacheLinkedIds = $cacheLinkedObjectIdsAndPropertyValue['cacheLinkedIds'];
        if(is_array($arrayOfCacheLinkedIds) && $arrayOfCacheLinkedIds[$propertyValue]) {
            unset($arrayOfCacheLinkedIds[$propertyValue][$propertyValue]);
        }
    }
}

/**
 * Function to get ids of cache object
 * @param array $cacheOfObject
 * @return array array of id (integer)
 */
function getCachedElementIds(&$cacheOfObject)
{
    return array_keys($cacheOfObject);
}

/**
 * Function to get cache object with its id
 * @param int $id searched object ids
 * @param array $arrayofobject
 * @return object|null
 */
function getCacheObject($objectId, &$cacheOfObject)
{
    return $cacheOfObject && $cacheOfObject[$objectId] ? $cacheOfObject[$objectId] : null;
}

/**
 * Function to get cache of object thanks to its linked object ids property
 * @param string|int $linkedObjectPropertyValue value of the linked property value
 * @param array $cacheOfLinkedObjectIds cache of this linked property ids mapping
 * @param array $cacheOfObject cache of object sql instance
 * @return array
 */
function getCachedObjectFromLinkedPropertyValue($linkedObjectPropertyValue, &$cacheOfLinkedObjectIds, &$cacheOfObject)
{
    $result = array();
    if($cacheOfLinkedObjectIds && is_array($cacheOfLinkedObjectIds[$linkedObjectPropertyValue])) {
        foreach($cacheOfLinkedObjectIds[$linkedObjectPropertyValue] as $id=>$notUsed) {
            $result[] = getCacheObject($id, $cacheOfObject);
        }
    }
    return $result;
}

/**
 * Function to fetch object from cache or from database
 * @param int $id id to fetch
 * @param int|string $ref reference of object to fetch
 * @param array $cacheOfObject cache of object
 * @param object $object object instance on which we fetch
 * @return int if >0 return object id
 */
function interventionSurveyFetchCommonWithCache($id, $ref, $cacheOfObject, $object)
{
    $cacheObject = getCacheObject($id, $cacheOfObject);
    if($cacheObject) {
        $object->id = $id;
        $object->set_vars_by_db($cacheObject);
        $object->date_creation = $object->db->idate($cacheObject->date_creation);
        $object->tms = $object->db->idate($cacheObject->tms);
        return $object->id;
    }
    else {
        return $object->fetchCommon($id, $ref);
    }
}

/**
 * Function to fetch object lines from cache or from database
 */
function interventionSurveyFetchCommonLineWithCache($morewhere = '', $objectlineclassname = null, &$resultValue, &$context, $cacheOfLinkedObjectIds, $cacheOfObject, $forceDataFromCache = false)
{
    $cacheOfLinkedObject = getCachedObjectFromLinkedPropertyValue($context->id, $cacheOfLinkedObjectIds, $cacheOfObject);
	if($forceDataFromCache || !empty($cacheOfLinkedObject)) {
		foreach($cacheOfLinkedObject as $obj) {
            $newline = new $objectlineclassname($context->db);
                $newline->setVarsFromFetchObj($obj, $context);
			if (method_exists($newline, "fetchLines")) {
                $newline->fetchLines($forceDataFromCache);
			}
                $resultValue[] = $newline;
                $context->errors = array_merge($context->errors, $newline->errors);
		}
		return empty($context->errors) ? 1 : -1;
	}
	else {
		return interventionSurveyFetchLinesCommon($morewhere, $objectlineclassname, $resultValue, $context);
	}
}

 /**
 * Load object in memory from the database for lines
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
                $newline->setVarsFromFetchObj($obj, $context);
                if (method_exists($newline, "fetchLines")) {
                    $newline->fetchLines();
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
