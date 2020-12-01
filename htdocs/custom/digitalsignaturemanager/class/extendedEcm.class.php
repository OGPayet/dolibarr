<?php
/* Copyright (C) 2017  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file        class/digitalsignaturepeople.class.php
 * \ingroup     digitalsignaturemanager
 * \brief       This file is a CRUD class file for DigitalSignaturePeople (Create/Read/Update/Delete)
 */

dol_include_once("/ecm/class/ecmfiles.class.php");

/**
 * Class for DigitalSignaturePeople
 */
class ExtendedEcm extends EcmFiles
{
	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public static $extended_table_element = 'digitalsignaturemanager_extended_ecm';

	/**
	 * @var string Name of the model used to generate file
	 */
	public $mask;

	/**
	 * Load object in memory from the database
	 *
	 * @param  int    $id          Id object
	 * @param  string $ref         Not used yet. Will contains a hash id from filename+filepath
	 * @param  string $fullpath    Full path of file (relative path to document directory)
	 * @return int                 <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null, $fullpath = '')
	{
		$result = parent::fetch($id, $ref, $fullpath);
		if($result > 0) {
			$result = self::fetchAdditionalPropertyForInstance($this->db, $this);
		}
		return $result;
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param string $sortorder Sort Order
	 * @param string $sortfield Sort field
	 * @param int    $limit     offset limit
	 * @param int    $offset    offset limit
	 * @param array  $filter    filter array
	 * @param string $filtermode filter mode (AND or OR)
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND')
	{
		$result = parent::fetchAll($sortorder, $sortfield, $limit, $offset, $filter, $filtermode);
		foreach($this->lines as $line) {
			self::fetchAdditionalPropertyForInstance($this->db, $line);
		}
		return $result;
	}

	/**
	 * Create object into database
	 *
	 * @param  User $user      User that creates
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 *
	 * @return int <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = false)
	{
		$result = parent::create($user, $notrigger);
		if($result > 0) {
			$result = self::saveAdditionalPropertyForInstance($this->db, $this);
		}
		return $result;
	}
	/**
	 * Update object into database
	 *
	 * @param  User $user      User that modifies
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = false)
	{
		$result =  parent::update($user, $notrigger);
		if($result > 0) {
			$result = self::saveAdditionalPropertyForInstance($this->db, $this);
		}
		return $result;
	}
	/**
	 * Delete object in database
	 *
	 * @param User $user      User that deletes
	 * @param bool $notrigger false=launch triggers after, true=disable triggers
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = false)
	{
		$result = parent::delete($user, $notrigger);
		if($result > 0) {
			$result = self::deleteAdditionalProperty($this->db, $this->id);
		}
		return $result;
	}

	/**
	 * Static function to fetch and add additional property to an instance
	 * @param DoliDB $db Database instance to use
	 * @param self|EcmfilesLine $instance instance on which add property values
	 * @return int
	 */
	public static function fetchAdditionalPropertyForInstance($db, &$instance)
	{
		$sql = "SELECT mask FROM " . MAIN_DB_PREFIX . self::$extended_table_element . " WHERE fk_ecm = " . $instance->id;
		$resql = $db->query($sql);
		if($resql) {
			$result = 1;
			$numrows = $db->num_rows($resql);
			if ($numrows) {
				$obj = $db->fetch_object($resql);
				$instance->mask = $obj->mask;
			}
		}
		else {
			$result = -1;
			$instance->errors[] = $db->error();
		}
		return $result;
	}

	/**
	 * Static function to save additional property
	 * @param DoliDB $db Database instance to use
	 * @param self|EcmfilesLines $instance instance on which save additionnal property
	 * @return int
	 */
	public static function saveAdditionalPropertyForInstance($db, &$instance)
	{
		self::deleteAdditionalProperty($db, $instance->id);
		$sql = "INSERT INTO " . MAIN_DB_PREFIX . self::$extended_table_element . " (fk_ecm, mask) VALUES ";
		$sqlId = $instance->id ? $db->escape($instance->id) : "NULL";
		$maskSql = $instance->mask ?  $db->escape($instance->mask) : "NULL";
		$sql .= "(" . $sqlId . ',' . $maskSql . ')';
		$resql = $instance->db->query($sql);
		if($resql) {
			$result = 1;
		}
		else {
			$result = -1;
			$instance->errors[] = $instance->db->error();
		}
		return $result;
	}

	/**
	 * Static function to delete additional property
	 * @param DoliDB $db Database instance to use
	 * @param int $id $id of the instance to delete
	 * @return int
	 */
	public static function deleteAdditionalProperty($db, $id)
	{
		$sql = "DELETE FROM " . MAIN_DB_PREFIX . self::$extended_table_element . " WHERE fk_ecm = " . $id;
		return !$db->query($sql) ? -1 : 1;
	}

	/**
	 * Static function to clone additional property of ecm file
	 * @param DoliDB $db Database instance to use
	 * @param int $fromId id of metadata to clone
	 * @param int $newId Id of metadata to be set
	 * @return int
	 */
	public static function cloneAdditionalProperty($db, $fromId, $newId)
	{
		$obj = new self($db);
		$obj->id = $fromId;
		self::fetchAdditionalPropertyForInstance($db, $obj);
		$obj->id = $newId;
		return self::saveAdditionalPropertyForInstance($db, $obj);
	}
}
