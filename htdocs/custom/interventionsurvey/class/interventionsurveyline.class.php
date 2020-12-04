<?php
/* Copyright (C) 2018       Open-DSI            <support@open-dsi.fr>
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
 * \file    htdocs/extendedintervention/class/extendedintervention.class.php
 * \ingroup extendedintervention
 * \brief
 */

class InterventionSurveyLine extends FichinterLigne
{

    /**
     * Array of cache data for massive api call
     * @var array
     * array('InterventionSurveyLineId'=>objectOfSqlResult))
     */

    static public $DB_CACHE = array();

    /**
     * Array of cache data for massive api call
     * @var array
     * array('interventionSurveyId'=>array('interventionSurveyLineId'=>true)))
     */

    static public $DB_CACHE_FROM_FICHINTER = array();

    /**
     * Extrafields cache - cache of extrafields data
     */
    static public $DB_CACHE_EXTRAFIELDS = array();

    /**
     *
     * Override getFieldList to change method accessibility
     *
     */
    public function getFieldList()
    {
        $fieldList = array("rowid", "fk_fichinter", "date", "description", "duree", "rang");
        return implode(',', $fieldList);
    }

    public static function fillCacheFromParentObjectIds($arrayOfFichInterIds)
    {
        global $db;
        $object = new self($db);
        commonLoadCacheForItemWithFollowingSqlFilter($object, $db, self::$DB_CACHE, ' WHERE fk_fichinter IN ( ' . implode(",", $arrayOfFichInterIds) . ')');
        $interventionSurveyLineIds = getCachedElementIds(self::$DB_CACHE);
        commonLoadExtrafieldCacheForItemWithIds($object, $db, self::$DB_CACHE_EXTRAFIELDS, $interventionSurveyLineIds);
        commonLoadCacheIdForLinkedObject(self::$DB_CACHE_FROM_FICHINTER, 'fk_fichinter', self::$DB_CACHE);
    }


    /**
     *	{@inheritdoc}
     */
    public function setVarsFromFetchObj($objp)
    {
            $this->rowid          	= $objp->rowid;
			$this->id = $objp->rowid;
			$this->fk_fichinter   	= $objp->fk_fichinter;
			$this->datei = $this->db->jdate($objp->date);
			$this->desc           	= $objp->description;
			$this->duration       	= $objp->duree;
			$this->rang           	= $objp->rang;
    }
}
