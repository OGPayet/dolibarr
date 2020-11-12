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
 * \file        class/digitalsignaturedocument.class.php
 * \ingroup     digitalsignaturemanager
 * \brief       This file is a CRUD class file for DigitalSignatureDocument (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
dol_include_once('/ecm/class/ecmfiles.class.php');
dol_include_once('/digitalsignaturemanager/lib/digitalsignaturedocument.helper.php');

/**
 *
 * Function to sort digital signature documents
 */
function digitalsignaturedocument_cmp($a, $b)
{
    if ($a->position == $b->position) {
        return 0;
    }
    return $a->position < $b->position ? -1 : 1;
}
/**
 * Class for DigitalSignatureDocument
 */
class DigitalSignatureDocument extends CommonObject
{
	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'digitalsignaturedocument';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'digitalsignaturemanager_digitalsignaturedocument';

	/**
	 * @var int  Does this object support multicompany module ?
	 * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
	 */
	public $ismultientitymanaged = 0;

	/**
	 * @var int  Does object support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 1;

	/**
	 * @var string String with name of icon for digitalsignaturedocument. Must be the part after the 'object_' into object_digitalsignaturedocument.png
	 */
	public $picto = 'digitalsignaturedocument@digitalsignaturemanager';

	/**
	 * @var DigitalSignatureRequest linkedDigitalSignatureRequest
	 */
	public $digitalSignatureRequest;

	/**
	 * @var Ecmfiles linked ecm files
	 */
	public $ecmFile;

	/**
	 *  'type' if the field format ('integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter]]', 'varchar(x)', 'double(24,8)', 'real', 'price', 'text', 'html', 'date', 'datetime', 'timestamp', 'duration', 'mail', 'phone', 'url', 'password')
	 *         Note: Filter can be a string like "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.nature:is:NULL)"
	 *  'label' the translation key.
	 *  'enabled' is a condition when the field must be managed (Example: 1 or '$conf->global->MY_SETUP_PARAM)
	 *  'position' is the sort order of field.
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'noteditable' says if field is not editable (1 or 0)
	 *  'default' is a default value for creation (can still be overwrote by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 if you want to have a total on list for this field. Field type must be summable like integer or double(24,8).
	 *  'css' is the CSS style to use on field. For example: 'maxwidth200'
	 *  'help' is a string visible as a tooltip on field
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
	 *  'arraykeyval' to set list of value if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel")
	 *  'autofocusoncreate' to have field having the focus on a create form. Only 1 field should have this property set to 1.
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *
	 *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
	 */

	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields=array(
		'rowid' => array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>'1', 'position'=>1, 'notnull'=>1, 'visible'=>0, 'noteditable'=>'1', 'index'=>1, 'comment'=>"Id"),
		'fk_digitalsignaturerequest' => array('type'=>'integer:DigitalSignatureRequest:digitalsignaturemanager/class/digitalsignaturerequest.class.php', 'label'=>'Linked Digital Signature request', 'enabled'=>'1', 'position'=>10, 'notnull'=>1, 'visible'=>1, 'index'=>1,),
		'fk_ecm' => array('type'=>'integer:EcmFiles:ecm/class/ecmfiles.class.php', 'label'=>'Linked To ECM Files', 'enabled'=>'1', 'position'=>11, 'notnull'=>1, 'visible'=>1, 'index'=>1,),
		'position' => array('type'=>'integer', 'label'=>'Position of files in digital signature request', 'enabled'=>'1', 'position'=>12, 'notnull'=>0, 'visible'=>1,),
		'date_creation' => array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>'1', 'position'=>500, 'notnull'=>1, 'visible'=>-2,),
		'tms' => array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>'1', 'position'=>501, 'notnull'=>0, 'visible'=>-2,),
	);
	public $rowid;
	public $fk_digitalsignaturerequest;
	public $fk_ecm;
	public $position;
	public $date_creation;
	public $tms;
	// END MODULEBUILDER PROPERTIES

	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf;

		$this->db = $db;

		if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && isset($this->fields['rowid'])) $this->fields['rowid']['visible'] = 0;
		if (empty($conf->multicompany->enabled) && isset($this->fields['entity'])) $this->fields['entity']['enabled'] = 0;

		// Example to show how to set values of fields definition dynamically
		/*if ($user->rights->digitalsignaturemanager->digitalsignaturedocument->read) {
		$this->fields['myfield']['visible'] = 1;
		$this->fields['myfield']['noteditable'] = 0;
		}*/

		// Unset fields that are disabled
		foreach ($this->fields as $key => $val)
		{
			if (isset($val['enabled']) && empty($val['enabled']))
			{
				unset($this->fields[$key]);
			}
		}
	}

	/**
	 * Create object into database
	 *
	 * @param  User $user      User that creates
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = false)
	{
		if(!$this->position) {
			$this->position = 0;
		}
		return $this->createCommon($user, $notrigger);
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int    $id   Id object
	 * @param string $ref  Ref
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null)
	{
		$result = $this->fetchCommon($id, $ref);
		if($result > 0) {
			$result = $this->fetchLinkedEcmFile();
		}
		if($result > 0) {
			$result = $this->fetchLinkedDigitalSignatureRequest();
		}
		return $result;
	}


	/**
	 * Function to fetch linked ecm file
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */

	 public function fetchLinkedEcmFile() {
		 $staticEcm = new EcmFiles($this->db);
		 $result = $staticEcm->fetch($this->fk_ecm);
		 if($result > 0) {
			 $this->ecmFile = $staticEcm;
		 }
		 return $result;
	 }

	 /**
	 * Function to fetch linked digital signature request
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */

	public function fetchLinkedDigitalSignatureRequest()
	{
		dol_include_once('/digitalsignaturemanager/class/digitalsignaturerequest.class.php');
		$digitalSignatureRequestStatic = new DigitalSignatureRequest($this->db);
		$result = $digitalSignatureRequestStatic->fetch($this->fk_digitalsignaturerequest);
		if($result > 0) {
			$this->digitalSignatureRequest = $digitalSignatureRequestStatic;
		};
		return $result;
	}

	/**
	 * Load list of objects in memory from the database.
	 *
	 * @param  string      $sortorder    Sort Order
	 * @param  string      $sortfield    Sort field
	 * @param  int         $limit        limit
	 * @param  int         $offset       Offset
	 * @param  array       $filter       Filter array. Example array('field'=>'valueforlike', 'customurl'=>...)
	 * @param  string      $filtermode   Filter mode (AND or OR)
	 * @return array|int                 int <0 if KO, array of pages if OK
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND')
	{
		global $conf;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$records = array();

		$sql = 'SELECT ';
		$sql .= $this->getFieldList();
		$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) $sql .= ' WHERE t.entity IN ('.getEntity($this->table_element).')';
		else $sql .= ' WHERE 1 = 1';
		// Manage filter
		$sqlwhere = array();
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				if ($key == 't.rowid') {
					$sqlwhere[] = $key.'='.$value;
				}
				elseif (strpos($key, 'date') !== false) {
					$sqlwhere[] = $key.' = \''.$this->db->idate($value).'\'';
				}
				elseif ($key == 'customsql') {
					$sqlwhere[] = $value;
				}
				else {
					$sqlwhere[] = $key.' LIKE \'%'.$this->db->escape($value).'%\'';
				}
			}
		}
		if (count($sqlwhere) > 0) {
			$sql .= ' AND ('.implode(' '.$filtermode.' ', $sqlwhere).')';
		}

		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (!empty($limit)) {
			$sql .= ' '.$this->db->plimit($limit, $offset);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < ($limit ? min($limit, $num) : $num))
			{
				$obj = $this->db->fetch_object($resql);

				$record = new self($this->db);
				$record->setVarsFromFetchObj($obj);

				$records[$record->id] = $record;

				$i++;
			}
			$this->db->free($resql);

			return $records;
		} else {
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);

			return -1;
		}
	}

	/**
	 * Update object into database
	 *
	 * @param  User $user      User that modifies
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = false)
	{
		return $this->updateCommon($user, $notrigger);
	}

	/**
	 * Delete object in database
	 *
	 * @param User $user       User that deletes
	 * @param bool $notrigger  false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = false)
	{
		$result = $this->deleteCommon($user, $notrigger);
		if($result > 0) {
			//we delete file
			$result = dol_delete_file($this->getLinkedFileAbsolutePath()) ? 1 : -1;
		}
		return $result;
	}

	/**
	* Fetch documents for digital signature request
	* @param DigitalSignatureRequest $digitalSignatureRequest linked digitalsignaturerequestobject
	* @param User $user user to use in case of staled database data
	* @return array|int
	*/
	public function fetchDocumentForDigitalSignature(&$digitalSignatureRequest, $user)
	{
		$this->db->begin();
		$errors = array();
		$staticEcm = new EcmFiles($this->db);
		//We have to clean ecm database table as some file must be present into it and not on disk
		self::cleanEcmFileDatabase($this->db, $digitalSignatureRequest->getUploadDirOfFilesToSign(), $digitalSignatureRequest->getRelativePathForFilesToSign(), $user);
		$staticEcm->fetchAll('ASC', 'rowid', null, null, array('filepath'=>$digitalSignatureRequest->getRelativePathForFilesToSign()));
		if(!empty($staticEcm->errors)) {
			$errors = array_merge($errors, $staticEcm->errors);
		} elseif(!empty($staticEcm->error)) {
			$errors[] = $staticEcm->error;
		}
		$ecmFiles = $staticEcm->lines;
		$digitalSignatureDocuments = $this->fetchAll('ASC', 'position', null, null, array('fk_digitalsignaturerequest'=>$digitalSignatureRequest->id));
		$maxPositionOfDigitalSignatureDocumentsAlreadyFetched = self::getLastPositionOfDocument($digitalSignatureDocuments);
		$errors = array_merge($errors, $this->errors);
		$effectiveDigitalSignatureDocuments = array();
		foreach($ecmFiles as $ecm) {
			$linkedDocumentObject = findObjectInArrayByProperty($digitalSignatureDocuments, 'fk_ecm', $ecm->id);
			if(!$linkedDocumentObject) {
				$linkedDocumentObject = new self($this->db);
				$linkedDocumentObject->fk_ecm = $ecm->id;
				$linkedDocumentObject->fk_digitalsignaturerequest = $digitalSignatureRequest->id;
				$linkedDocumentObject->ecmFile = $ecm;
				$linkedDocumentObject->position = $maxPositionOfDigitalSignatureDocumentsAlreadyFetched++;
				$successfullyCreated = $linkedDocumentObject->create($user);
				if($successfullyCreated < 0) {
					$errors = array_merge($errors, $linkedDocumentObject->errors);
				}
			}
			$linkedDocumentObject->digitalSignatureRequest = $digitalSignatureRequest;
			$linkedDocumentObject->ecmFile = $ecm;
			$effectiveDigitalSignatureDocuments[] = $linkedDocumentObject;
		}
		foreach($digitalSignatureDocuments as $digitalSignatureDocument) {
			$linkedEcm = findObjectInArrayByProperty($ecmFiles, 'id', $digitalSignatureDocument->fk_ecm);
			if(!$linkedEcm) {
				$digitalSignatureDocument->delete($user);
				$errors = array_merge($errors, $digitalSignatureDocument->errors);
			}
		}

		usort($effectiveDigitalSignatureDocuments, 'digitalsignaturedocument_cmp');

		if(empty($errors)) {
			$this->db->commit();
			return $effectiveDigitalSignatureDocuments;
		}
		else {
			$this->db->rollback();
			$this->errors = $errors;
			return -1;
		}
	}

	/**
	 * Function to get display name of the document
	 * @return string label or filename of the document
	 */
	public function getDocumentName()
	{
		if(!$this->ecmFile) {
			$this->fetchLinkedEcmFile();
		}
		if($this->ecmFile) {
			return $this->ecmFile->filename;
		}
	}
	/**
	 * Function to get full path of the file linked to this document
	 * @return string|null label or filename of the document
	 */
	public function getLinkedFileAbsolutePath()
	{
		if(!$this->digitalSignatureRequest) {
			$this->fetchLinkedDigitalSignatureRequest();
		}
		if($this->digitalSignatureRequest) {
			return $this->digitalSignatureRequest->getBaseUploadDir() . "/" . $this->getLinkedFileRelativePath();
		}
	}

	/**
	 * Function to get full path of the file linked to this document
	 * @return string label or filename of the document
	 */
	public function getLinkedFileRelativePath()
	{
		if(!$this->digitalSignatureRequest) {
			$this->fetchLinkedDigitalSignatureRequest();
		}
		if($this->digitalSignatureRequest) {
			return $this->digitalSignatureRequest->getRelativePathForFilesToSign() . "/" . $this->getDocumentName();
		}
	}

	/**
	 * Function to get the entity linked to this file
	 * @return string label or filename of the document
	 */
	public function getEntity()
	{
		return $this->digitalSignatureRequest->entity;
	}

	/**
	 * Function to get the max position of the list of given document
	 * @param array $listOfDocuments
	 * @return int
	 */
	public static function getLastPositionOfDocument($listOfDocuments) {
		$maximum = 0;
		foreach($listOfDocuments as $document) {
			if($document->position > $maximum) {
				$maximum = $document->position;
			}
		}
		return $maximum;
	}

	/**
	 * Function to clean the ecm file database
	 * @param DoliDB $db database instance
	 * @param string $absoluteDirectoryOnDisk Absolute directory into system where check files
	 * @param string $relativeDirectoryInDatabase relative directory into database
	 * @param User $user calling clean of database
	 * @return array $array of errors
	 */
	public static function cleanEcmFileDatabase($db, $absoluteDirectoryOnDisk, $relativeDirectoryInDatabase, $user) {
		$listOfFileOnDisk = dol_dir_list($absoluteDirectoryOnDisk, 'files');
		$staticEcm = new EcmFiles($db);
		$staticEcm->fetchAll('ASC', 'rowid', null, null, array('filepath'=>$relativeDirectoryInDatabase));
		$listOfFilesIntoDatabase = $staticEcm->lines;
		$errors = array();
		foreach($listOfFilesIntoDatabase as $ecm) {
			$fileInDisk = findObjectInArrayByProperty($listOfFileOnDisk, 'name', $ecm->filename);
			if(!$fileInDisk) {
				//Dolibarr bullshit adaptation
				$properEcmObject = new EcmFiles($db);
				$properEcmObject->fetch($ecm->id);
				$properEcmObject->delete($user);
				$errors = array_merge($errors, $properEcmObject->errors);
			}
		}
		return $errors;
	}

	/**
	 * Function to get ecm instance of a file based thanks to its directory
	 * @param DoliDB $db database instance to use
	 * @param string $relativePath relative directory path to use
	 * @param string $filename filename to find
	 * @return EcmFiles|null
	 */

	public static function getEcmInstanceOfFile($db, $relativePath, $filename)
	{
		$ecmStatic = new EcmFiles($db);
		$isThisFileIntoDatabase = $ecmStatic->fetch(null, null, 'digitalsignaturemanager/' . $relativePath . '/' . $filename);
		return $isThisFileIntoDatabase ? $ecmStatic : null;
	}
}
