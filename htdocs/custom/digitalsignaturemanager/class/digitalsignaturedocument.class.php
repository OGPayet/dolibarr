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
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';
dol_include_once('/digitalsignaturemanager/class/extendedEcm.class.php');
dol_include_once('/digitalsignaturemanager/lib/digitalsignaturedocument.helper.php');
dol_include_once('/digitalsignaturemanager/vendor/autoload.php');
dol_include_once('/digitalsignaturemanager/core/dictionaries/digitalsignaturedocumenttypemanager.class.php');


use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfReader;

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
	 * @var ExtendedEcm linked ecm file
	 */
	public $ecmFile;

	/**
	 * @var ExtendedEcm signed linked ecm file
	 */
	public $signedEcmFile;

	/**
	 * @var DigitalSignatureCheckBox[] linked checkbox of this document
	 */
	public $checkBoxes;

	/**
	 * @var Array[] Dictionary Checkbox data cache
	 */
	public static $cache_checkbox_dictionary = null;

	/**
	 * @var Array[] Dictionary signatory fields cache
	 */
	public static $cache_signatoryfields_dictionary = null;

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
	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => '1', 'position' => 1, 'notnull' => 1, 'visible' => 0, 'noteditable' => '1', 'index' => 1, 'comment' => "Id"),
		'fk_digitalsignaturerequest' => array('type' => 'integer:DigitalSignatureRequest:digitalsignaturemanager/class/digitalsignaturerequest.class.php', 'label' => 'Linked Digital Signature request', 'enabled' => '1', 'position' => 10, 'notnull' => 1, 'visible' => 1, 'index' => 1,),
		'fk_ecm' => array('type' => 'integer:ExtendedEcm:digitalsignaturemanager/class/extendedEcm.class.php', 'label' => 'Linked To ECM File', 'enabled' => '1', 'position' => 11, 'notnull' => 1, 'visible' => 1, 'index' => 1),
		'fk_ecm_signed' => array('type' => 'integer:ExtendedEcm:digitalsignaturemanager/class/extendedEcm.class.php', 'label' => 'Linked To ECM signed file', 'enabled' => '1', 'position' => 11, 'notnull' => 0, 'visible' => 1, 'index' => 1),
		'position' => array('type' => 'integer', 'label' => 'Position of files in digital signature request', 'enabled' => '1', 'position' => 12, 'notnull' => 0, 'visible' => 1,),
		'date_creation' => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => '1', 'position' => 500, 'notnull' => 1, 'visible' => -2,),
		'tms' => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => '1', 'position' => 501, 'notnull' => 0, 'visible' => -2,),
		'check_box_ids' => array('type' => 'array', 'label' => 'List of attached check box on this document', 'enabled' => 1, 'position' => 32, 'notnull' => 0, 'visible' => 0,),
		'elementtype' => array('type' => 'varchar(128)', 'label' => 'Linked Element Type', 'enabled' => '1', 'position' => 1003, 'notnull' => 0, 'visible' => 0, 'index' => 1,),
		'fk_object' => array('type' => 'integer', 'label' => 'Id of the dolibarr object we sign', 'enabled' => '1', 'position' => 1004, 'notnull' => 0, 'visible' => 0, 'index' => 1,)
	);
	public $rowid;
	public $fk_digitalsignaturerequest;
	public $fk_ecm;
	public $fk_ecm_signed;
	public $position;
	public $date_creation;
	public $tms;
	public $check_box_ids;
	public $elementtype;
	public $fk_object;
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

		// Unset fields that are disabled
		foreach ($this->fields as $key => $val) {
			if (isset($val['enabled']) && empty($val['enabled'])) {
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
		if (!$this->position) {
			$this->position = 0;
		}
		$this->addDefaultCheckbox();
		$result = $this->createMissingCheckBoxes($user, $notrigger) ? 1 : -1;

		if ($result > 0) {
			$result = $this->createCommon($user, $notrigger);
		}

		return $result;
	}

	/**
	 * Add to chosen checkbox ids checkbox that should be added by default according to document type
	 * @return void
	 */
	public function addDefaultCheckbox()
	{
		foreach($this->getAvailableCheckBox() as $id => $checkBox) {
			$documentType = $this->getSourceOfThisFile();
			if($documentType && in_array($documentType, $checkBox->added_by_default) && !in_array($id, $this->check_box_ids)) {
				$this->check_box_ids[] = $id;
			}
		}
	}

	/**
	 * Create checkboxes chosen from dictionary into database
	 *
	 * @param  User $user      User that creates
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, Id of created object if OK
	 */
	public function createMissingCheckBoxes($user, $notrigger)
	{
		$this->db->begin();
		$errors = array();
		$oldIds = $this->check_box_ids;
		if (!empty($this->check_box_ids)) {
			$availableCheckBoxes = $this->getAvailableCheckBox();
			foreach ($availableCheckBoxes as $id => $checkBox) {
				foreach ($this->check_box_ids as $index => $chosenId) {
					if ($id == $chosenId) {
						if (!$checkBox->id > 0) {
							$checkBox->position = $checkBox::getLastPosition($this->getLinkedDigitalSignatureRequest()->availableCheckBox);

							$checkBox->create($user, $notrigger);
							$errors = array_merge($errors, $checkBox->errors);
							if ($checkBox->id > 0) {
								$id = $checkBox->id;
								$this->check_box_ids[$index] = (int) $id;
							}
						}
						break;
					}
				}
			}
		}

		$this->errors = array_merge($this->errors, $errors);
		if (empty($errors)) {
			$this->db->commit();
			return 1;
		} else {
			$this->check_box_ids = $oldIds;
			$this->db->rollback();
			return -1;
		}
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
		if ($result > 0) {
			$result = $this->fetchLinkedEcmFile();
		}
		if ($result > 0) {
			$result = $this->fetchLinkedDigitalSignatureRequest();
		}
		if ($result > 0) {
			$this->fetchDigitalSignatureCheckBox();
		}
		return $result;
	}


	/**
	 * Function to fetch linked ecm file
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetchLinkedEcmFile()
	{
		$staticEcm = new ExtendedEcm($this->db);
		$result = $staticEcm->fetch($this->fk_ecm);
		if ($result > 0) {
			$this->ecmFile = $staticEcm;
		}
		return $result;
	}

	/**
	 * Function to fetch linked ecm signed file
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetchSignedLinkedEcmFile()
	{
		$staticEcm = new ExtendedEcm($this->db);
		$result = $staticEcm->fetch($this->fk_ecm_signed);
		if ($result > 0) {
			$this->signedEcmFile = $staticEcm;
		}
		return $result;
	}

	/**
	 * Function to get linked ecm files
	 * @return null|ExtendedEcm
	 */
	public function getLinkedEcmFile()
	{
		if(!$this->ecmFile) {
			$this->fetchLinkedEcmFile();
		}
		return $this->ecmFile ?? null;
	}

	/**
	 * Function to get linked ecm signed files
	 * @return null|ExtendedEcm
	 */
	public function getSignedLinkedEcmFile()
	{
		if(!$this->ecmFile) {
			$this->fetchSignedLinkedEcmFile();
		}
		return $this->signedEcmFile ?? null;
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
		if ($result > 0) {
			$this->digitalSignatureRequest = $digitalSignatureRequestStatic;
		}
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
	 * @return DigitalSignatureDocument[]|int                 int <0 if KO, array of pages if OK
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND')
	{
		global $conf;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$records = array();

		$sql = 'SELECT ';
		$sql .= $this->getFieldList();
		$sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . ' as t';
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) $sql .= ' WHERE t.entity IN (' . getEntity($this->table_element) . ')';
		else $sql .= ' WHERE 1 = 1';
		// Manage filter
		$sqlwhere = array();
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				if ($key == 't.rowid') {
					$sqlwhere[] = $key . '=' . $value;
				} elseif (strpos($key, 'date') !== false) {
					$sqlwhere[] = $key . ' = \'' . $this->db->idate($value) . '\'';
				} elseif ($key == 'customsql') {
					$sqlwhere[] = $value;
				} else {
					$sqlwhere[] = $key . ' LIKE \'%' . $this->db->escape($value) . '%\'';
				}
			}
		}
		if (count($sqlwhere) > 0) {
			$sql .= ' AND (' . implode(' ' . $filtermode . ' ', $sqlwhere) . ')';
		}

		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (!empty($limit)) {
			$sql .= ' ' . $this->db->plimit($limit, $offset);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < ($limit ? min($limit, $num) : $num)) {
				$obj = $this->db->fetch_object($resql);

				$record = new self($this->db);
				$record->setVarsFromFetchObj($obj);

				$records[$record->id] = $record;

				$i++;
			}
			$this->db->free($resql);

			return $records;
		} else {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);

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
		$result = $this->createMissingCheckBoxes($user, $notrigger) ? 1 : -1;
		if ($result > 0) {
			$result = $this->updateCommon($user, $notrigger);
		}
		return $result;
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
		global $langs;
		$errors = array();
		if ($this->deleteCommon($user, $notrigger) > 0 && $this->getLinkedEcmFile() && !$this->getLinkedEcmFile()->deleteFile()) {
			$errors[] = $langs->trans('DigitalSignatureManagerErrorWhileDeletingFile', $this->getLinkedEcmFile()->filename, $this->getLinkedDigitalSignatureRequest()->ref);
		}
		$this->errors = array_merge($this->errors, $errors);
		return empty($errors) ? 1 : -1;
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
		//We have to clean ecm database table as some file must be present into it and not on disk
		$errors = ExtendedEcm::cleanEcmFileDatabase($this->db, $digitalSignatureRequest->getRelativePathToDolDataRootForFilesToSign(), $user);

		$digitalSignatureDocuments = $this->fetchAll('ASC', 'position', null, null, array('fk_digitalsignaturerequest' => $digitalSignatureRequest->id));
		$errors = array_merge($errors, $this->errors);
		$effectiveDigitalSignatureDocuments = array();
		foreach($digitalSignatureDocuments as $document)
		{
			//we check that each file of each document is always here
			//otherwise we delete document
			if(!$document->getLinkedEcmFile()) {
				$document->delete($user);
				$errors = array_merge($errors, $document->errors);
			}
			else {
				$document->digitalSignatureRequest = $digitalSignatureRequest;
				$effectiveDigitalSignatureDocuments[] = $document;
			}
		}
		usort($effectiveDigitalSignatureDocuments, 'digitalsignaturedocument_cmp');

		if (empty($errors)) {
			$this->db->commit();
			return $effectiveDigitalSignatureDocuments;
		} else {
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
		return $this->getLinkedEcmFile() ? $this->getLinkedEcmFile()->filename : null;
	}
	/**
	 * Function to get full path of the file linked to this document
	 * @return string|null label or filename of the document
	 */
	public function getLinkedFileAbsolutePath()
	{
		return $this->getLinkedEcmFile() ? $this->getLinkedEcmFile()->getFileFullPath() : null;
	}

	/**
	 * Function to get linked digital signature request instance
	 * @return DigitalSignatureRequest
	 */
	public function getLinkedDigitalSignatureRequest()
	{
		if (!$this->digitalSignatureRequest) {
			$this->fetchLinkedDigitalSignatureRequest();
		}
		return $this->digitalSignatureRequest;
	}

	/**
	 * Function to get full path of the file linked to this document
	 * @return string label or filename of the document
	 */
	public function getLinkedFileRelativePath()
	{
		return $this->getLinkedEcmFile() ? $this->getLinkedEcmFile()->getRelativePathToModule('digitalsignaturemanager') : null;
	}

	/**
	 * Function to get the entity linked to this file
	 * @return string label or filename of the document
	 */
	public function getEntity()
	{
		return $this->getLinkedDigitalSignatureRequest() ? $this->getLinkedDigitalSignatureRequest()->entity : null;
	}

	/**
	 * Function to get the max position of the list of given document
	 * @param self[] $listOfDocuments Documents to analyze
	 * @return int
	 */
	public static function getLastPositionOfDocument($listOfDocuments)
	{
		$maximum = 0;
		foreach ($listOfDocuments as $document) {
			if ($document->position > $maximum) {
				$maximum = $document->position;
			}
		}
		return $maximum;
	}

	/**
	 * Function to rename file
	 * @param string $newFilename wishes file name for the file
	 * @param User $user user requesting the modification
	 * @return bool true if sucessfully removed. False otherwise
	 */
	public function renameDocument($newFilename, $user)
	{
		$result = $this->getLinkedEcmFile()->renameFile($newFilename, $user);
		$this->errors = array_merge($this->errors, $this->getLinkedEcmFile()->errors);
		return $result;
	}

	/**
	 * Fetch available CheckBoxes that could be chosen for this digital signature document and request
	 * @return DigitalSignatureCheckBox[]
	 */
	public function getAvailableCheckBox()
	{
		$linkedDigitalSignatureRequest = $this->getLinkedDigitalSignatureRequest();
		$availableCheckBoxes = array();
		$alreadyCheckboxTakenFromDictionary = array();
		if ($linkedDigitalSignatureRequest) {
			$checkboxFromRequest = $linkedDigitalSignatureRequest->availableCheckBox;
			foreach ($checkboxFromRequest as $index => $checkbox) {
				$alreadyCheckboxTakenFromDictionary[] = $checkbox->c_rowid;
				$availableCheckBoxes[(string) $index] = $checkbox;
			}
		}

		//We use checkbox from dictionary
		$notFilteredCheckboxFromDictionary = self::getAvailableCheckboxFromDictionary($this->db);
		foreach ($notFilteredCheckboxFromDictionary as $dictionaryCheckbox) {
			if (!in_array($dictionaryCheckbox->c_rowid, $alreadyCheckboxTakenFromDictionary) && (empty($dictionaryCheckbox->availableOnlyForMasks) || $this->doesThisDocumentComeFromOneOfTheSource($dictionaryCheckbox->availableOnlyForMasks))) {
				//This checkbox can be used for this document
				$checkbox = new DigitalSignatureCheckBox($this->db);
				$checkbox->setVarsFromFetchObj($dictionaryCheckbox);
				$checkbox->added_by_default = $dictionaryCheckbox->added_by_default; //Dolibarr does not manage array into setVarsFromFetchObj
				$checkbox->digitalSignatureRequest = $linkedDigitalSignatureRequest;
				$checkbox->fk_digitalsignaturerequest = $this->fk_digitalsignaturerequest;
				$availableCheckBoxes["dictionary_" . $checkbox->c_rowid] = $checkbox;
			}
		}
		return $availableCheckBoxes;
	}

	/**
	 * Function to get substitution array of this document
	 * @return string[]
	 */
	public function getSubstitutionArray()
	{
		return array(
			'__TOTALPAGENUMBER__' => $this->getNumberOfPage()
		);
	}

	/**
	 * Get source mask of this document
	 * @return string Name of the mask which generate this document
	 */
	public function getSourceOfThisFile()
	{
		$result = null;
		if ($this->getLinkedEcmFile()) {
			if($this->getLinkedEcmFile()->gen_or_uploaded == "uploaded") {
				$result = DigitalSignatureDocumentTypeManager::FREE_DOCUMENT_TYPE;
			} else {
				$result = $this->getLinkedEcmFile()->mask;
			}
		}
		return $result;
	}

	/**
	 * Does this document come from one of the source
	 * @param string[] $arrayOfAllowedSource Names of the researched sources (mask names)
	 * @return bool
	 */
	public function doesThisDocumentComeFromOneOfTheSource($arrayOfAllowedSource)
	{
		return in_array($this->getSourceOfThisFile(), $arrayOfAllowedSource);
	}

	/**
	 * Fetch linked checkbox of this digital signature document
	 * @return void
	 */
	public function fetchDigitalSignatureCheckBox()
	{
		$result = array();
		foreach ($this->getAvailableCheckBox() as $id => $checkbox) {
			foreach ($this->check_box_ids as $chosenId) {
				if ($id == $chosenId) {
					$result[] = $checkbox;
					break;
				}
			}
		}
		$this->checkBoxes = $result;
	}

	/**
	 * Function to get digital signature checkboxes
	 * @return DigitalSignatureCheckbox[]
	 */
	public function getCheckBoxes()
	{
		if(!$this->checkBoxes) {
			$this->fetchDigitalSignatureCheckBox();
		}
		return $this->checkBoxes ?? array();
	}

	/**
	 * Function to check that data are valid for this document
	 * @return string[] array of errors when validating this document
	 */
	public function checkDataValidForCreateRequestOnProvider()
	{
		$errors = array();
		//We could validate document field here

		foreach ($this->getCheckBoxes() as $checkbox) {
			$errors = array_merge($errors, $checkbox->checkDataValidForCreateRequestOnProvider($this));
		}
		return $errors;
	}

	/**
	 * Clone an object into another one
	 *
	 * @param  	User 	$user      	User that creates
	 * @param  	int 	$fromId     Id of object to clone
	 * @param 	DigitalSignatureRequest     $linkedDigitalSignatureRequest Linked digital signature request instance if different from $this
	 * @param   int[]   $arrayOfOldCheckBoxIdAndClonedCheckBoxId When digital signature request is cloned, in order to update chosen check box id
	 * @return 	mixed 				New object created, <0 if KO
	 */
	public function createFromClone(User $user, $fromId, $linkedDigitalSignatureRequest = null, $arrayOfOldCheckBoxIdAndClonedCheckBoxId = null)
	{
		global $langs, $extrafields;
		dol_syslog(__METHOD__, LOG_DEBUG);
		$errors = array();
		$object = new self($this->db);

		$this->db->begin();

		// Load source object
		$object->fetch($fromId);

		// Reset some properties
		$object->id = null;
		$object->fk_user_creat = null;
		$object->import_key = null;

		if (isset($arrayOfOldCheckBoxIdAndClonedCheckBoxId)) {
			$oldChosenCheckBoxIds = $object->check_box_ids;
			$newChosenCheckBoxIds = array();
			foreach ($oldChosenCheckBoxIds as $id) {
				$newChosenCheckBoxIds[] = $arrayOfOldCheckBoxIdAndClonedCheckBoxId[$id];
			}
			$object->check_box_ids = array_filter($newChosenCheckBoxIds);
		}

		// Clear extrafields that are unique
		if (is_array($object->array_options) && count($object->array_options) > 0) {
			$extrafields->fetch_name_optionals_label($this->table_element);
			foreach ($object->array_options as $key => $option) {
				$shortkey = preg_replace('/options_/', '', $key);
				if (!empty($extrafields->attributes[$this->element]['unique'][$shortkey])) {
					unset($object->array_options[$key]);
				}
			}
		}

		//we update linked digital signature request object as it is a mandatory field
		if (!$linkedDigitalSignatureRequest) {
			$linkedDigitalSignatureRequest = $this->getLinkedDigitalSignatureRequest();
		}
		if ($linkedDigitalSignatureRequest->id == $this->fk_digitalsignaturerequest) {
			//we are cloning document into the same request
			//We have to rename file in order to have two documents
			$newDocumentName = 'copy-' . $this->getDocumentName();
		}

		//We copy files
		$newEcmFile = $this->getLinkedEcmFile()->copyFileTo($linkedDigitalSignatureRequest->getRelativePathToDolDataRootForFilesToSign(), $newDocumentName);
		if (!$newEcmFile) {
			$errors[] = $langs->trans('DigitalSignatureManagerErrorWhileCopyingFile', $newDocumentName);
		} else {
			//We update ecm id and digital signature request
			$object->fk_digitalsignaturerequest = $linkedDigitalSignatureRequest->id;
			$object->fk_ecm = $newEcmFile->id;
			$object->ecmFile = $newEcmFile;
			// Create clone
			$object->context['createfromclone'] = 'createfromclone';
			$result = $object->create($user);
			if ($result < 0) {
				$errors = array_merge($errors, $object->errors);
			}
			unset($object->context['createfromclone']);
		}

		// End
		if (empty($errors)) {
			$this->db->commit();
			return $object;
		} else {
			$this->errors = array_merge($this->errors, $errors);
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 * Get number of page of the document
	 * @return int|null number of page of this file
	 */
	public function getNumberOfPage()
	{
		$fullpath = $this->getLinkedFileAbsolutePath();
		if (dol_is_file($fullpath)) {
			try {
				$pdf = pdf_getInstance();
				$pageCount = $pdf->setSourceFile($fullpath);
			} catch (Exception $e) {
				global $langs;
				$this->errors[] = $langs->trans("DigitalSignatureManagerCantOpenPdf");
			}
		}
		return $pageCount;
	}

	/**
	 * Prepare data fetched from dictionary in order to have simple and proper array of data
	 * @param DoliDB $db Database instance to use
	 * @param string $dictionaryName Dictionary name of data to fetch
	 * @param string[] $fieldToKeep array of field which should be kept
	 * @param string[] $fieldsToTransformInArray array of fields which should be transformed into array
	 * @return stdClass[]
	 */
	public static function fetchProperDataFromDictionary($db, $dictionaryName, $fieldToKeep = array(), $fieldsToTransformInArray = array())
	{
		dol_include_once('/advancedictionaries/class/dictionary.class.php');
		$data = Dictionary::getJSONDictionary($db, 'digitalsignaturemanager', $dictionaryName);
		foreach ($data as $index => $value) {
			$data[$index] = json_decode(json_encode($value));
		}
		uasort($data, 'digitalsignaturedocument_cmp');
		$result = array();
		foreach ($data as $value) {
			$temp = new StdClass();
			$temp->c_rowid = $value->rowid;
			foreach ($fieldsToTransformInArray as $field) {
				$value->$field = array_filter(explode(",", $value->$field));
			}
			foreach ($fieldToKeep as $field) {
				$temp->$field = $value->$field;
			}
			$result[$temp->c_rowid] = json_decode(json_encode($temp));
		}
		return $result;
	}

	/**
	 * Get Dictionary checkbox data
	 * @param DoliDB $db Database instance to use
	 * @return stdClass[]
	 */
	public static function getAvailableCheckboxFromDictionary($db)
	{
		if (!self::$cache_checkbox_dictionary) {
			self::fetchCheckboxDictionary($db);
		}
		return self::$cache_checkbox_dictionary;
	}

	/**
	 * Get Dictionary checkbox data
	 * @param DoliDB $db Database instance to use
	 * @return stdClass[]
	 */
	public static function getSignatoryFieldsFromDictionary($db)
	{
		if (!self::$cache_checkbox_dictionary) {
			self::fetchSignatoryFieldsDictionary($db);
		}
		return self::$cache_signatoryfields_dictionary;
	}

	/**
	 * Get signatory fields from dictionary of this document
	 * @return DigitalSignatureSignatoryFieldsDictionary[]
	 */
	public function getDictionarySignatoryFieldsOfThisDocument()
	{
		$result = array();
		foreach(self::getSignatoryFieldsFromDictionary($this->db) as $signatoryField) {
			if(in_array($this->getSourceOfThisFile(), $signatoryField->concernedMask)) {
				$result[$signatoryField->c_rowid] = $signatoryField;
			}
		}
		return $result;
	}

	/**
	 * Load checkbox dictionary into cache
	 * @param DoliDB $db Database instance to use
	 * @return void
	 */
	public static function fetchCheckboxDictionary($db)
	{
		self::$cache_checkbox_dictionary = self::fetchProperDataFromDictionary(
			$db,
			'digitalsignaturecheckbox',
			array("position", "label", "availableOnlyForMasks", "added_by_default"),
			array("availableOnlyForMasks", "added_by_default")
		);
	}

		/**
	 * Load checkbox dictionary into cache
	 * @param DoliDB $db Database instance to use
	 * @return void
	 */
	public static function fetchSignatoryFieldsDictionary($db)
	{
		self::$cache_signatoryfields_dictionary = self::fetchProperDataFromDictionary(
			$db,
			'digitalsignaturesignatoryfields',
			array("concernedMask", "position", "label", "pageNumber", "x", "y", "linkedContactType"),
			array("concernedMask", "linkedContactType")
		);
	}

	/**
	 * Function to add documents to a given request thanks to ecmFiles instance
	 * @param DigitalSignatureRequest $digitalSignatureRequest Digital signature request on which add documents
	 * @param User $user User adding files to this request
	 * @param ExtendedEcm[] $filesToAdd Ecm instance of files to add
	 * @return self[] array of instance
	 */
	public function addTheseFilesToADigitalSignatureRequest(&$digitalSignatureRequest, &$user, &$filesToAdd)
	{
		global $langs;
		$result = array();

		foreach ($filesToAdd as $ecmFile) {
			$digitalSignatureDocument = clone $this;
			$digitalSignatureDocument->id = null;
			$digitalSignatureDocument->errors = array();
			$digitalSignatureDocument->fk_digitalsignaturerequest = $digitalSignatureRequest->id;
			$digitalSignatureDocument->digitalSignatureRequest = $digitalSignatureRequest;
			$digitalSignatureDocument->fk_ecm = $ecmFile->id;
			$digitalSignatureDocument->ecmFile = $ecmFile;
			$digitalSignatureDocument->position = self::getLastPositionOfDocument($digitalSignatureRequest->documents) + 1;
			//We create document
			if ($digitalSignatureDocument->create($user) < 0) {
				$this->errors[] = $digitalSignatureDocument->errors;
			} else {
				$digitalSignatureRequest->documents[] = $digitalSignatureDocument;
				$result[] = $digitalSignatureDocument;
			}
		}
		return $result;
	}

	/**
	 * Function to get a corrected page number on this document according to a requested page (can be negative)
	 * @param int $requestedPageNumber wishes page number (can be null or negative)
	 * @return int positive integer between 1 and $this->getNumberOfPage()
	 */
	public function correctPageNumber($requestedPageNumber)
	{
		$totalNumberOfPage = $this->getNumberOfPage();
		$requestedPageNumber = (int) $requestedPageNumber;
		if($requestedPageNumber == 0) {
			$requestedPageNumber = 1;
		}
		elseif($requestedPageNumber < 0) {
			$requestedPageNumber = $totalNumberOfPage + $requestedPageNumber + 1;
		}
		if($requestedPageNumber <=0 || $requestedPageNumber > $totalNumberOfPage) {
			$result = 1;
		}
		else {
			$result = $requestedPageNumber;
		}
		return $result;
	}

	/**
	 * Static function to get linked object of a digital signature request
	 *
	 * @return	CommonObject|null
	 */
	public function getLinkedObject()
	{
		global $db, $conf;

		if (!$this->fk_object || !$this->elementtype) {
			return null;
		}
		$objecttype = $this->elementtype;
		$objectid = $this->fk_object;

		$ret = '';

		// Parse element/subelement (ex: project_task)
		$module = $element = $subelement = $objecttype;
		if (preg_match('/^([^_]+)_([^_]+)/i', $objecttype, $regs)) {
			$module = $element = $regs[1];
			$subelement = $regs[2];
		}

		$classpath = $element . '/class';

		// To work with non standard path
		if ($objecttype == 'facture' || $objecttype == 'invoice') {
			$classpath = 'compta/facture/class';
			$module = 'facture';
			$subelement = 'facture';
		}
		if ($objecttype == 'commande' || $objecttype == 'order') {
			$classpath = 'commande/class';
			$module = 'commande';
			$subelement = 'commande';
		}
		if ($objecttype == 'propal') {
			$classpath = 'comm/propal/class';
		}
		if ($objecttype == 'supplier_proposal') {
			$classpath = 'supplier_proposal/class';
		}
		if ($objecttype == 'shipping') {
			$classpath = 'expedition/class';
			$subelement = 'expedition';
			$module = 'expedition_bon';
		}
		if ($objecttype == 'delivery') {
			$classpath = 'livraison/class';
			$subelement = 'livraison';
			$module = 'livraison_bon';
		}
		if ($objecttype == 'contract') {
			$classpath = 'contrat/class';
			$module = 'contrat';
			$subelement = 'contrat';
		}
		if ($objecttype == 'member') {
			$classpath = 'adherents/class';
			$module = 'adherent';
			$subelement = 'adherent';
		}
		if ($objecttype == 'cabinetmed_cons') {
			$classpath = 'cabinetmed/class';
			$module = 'cabinetmed';
			$subelement = 'cabinetmedcons';
		}
		if ($objecttype == 'fichinter') {
			$classpath = 'fichinter/class';
			$module = 'ficheinter';
			$subelement = 'fichinter';
		}
		if ($objecttype == 'task') {
			$classpath = 'projet/class';
			$module = 'projet';
			$subelement = 'task';
		}

		//print "objecttype=".$objecttype." module=".$module." subelement=".$subelement;

		$classfile = strtolower($subelement);
		$classname = ucfirst($subelement);
		if ($objecttype == 'invoice_supplier') {
			$classfile = 'fournisseur.facture';
			$classname = 'FactureFournisseur';
			$classpath = 'fourn/class';
			$module = 'fournisseur';
		}
		if ($objecttype == 'order_supplier') {
			$classfile = 'fournisseur.commande';
			$classname = 'CommandeFournisseur';
			$classpath = 'fourn/class';
			$module = 'fournisseur';
		}

		if (!empty($conf->$module->enabled)) {
			$res = dol_include_once('/' . $classpath . '/' . $classfile . '.class.php');
			if ($res) {
				$object = new $classname($db);
				$res = $object->fetch($objectid);
				if ($res > 0) {
					return $object;
				}
			}
		}
		return null;
	}
}
