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
 * \file        class/digitalsignaturesignatoryfield.class.php
 * \ingroup     digitalsignaturemanager
 * \brief       This file is a CRUD class file for DigitalSignatureSignatoryField (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

/**
 * Function to be used with usort to sort array of signatory according to document and signatory orders
 * @param DigitalSignatureSignatoryField $a first element
 * @param DigitalSignatureSignatoryField $b second element
 * @return int proper int according to usort
 */
function sortSignatoryByDocumentAndSignatoryOrder($a, $b)
{
	$aDocument = $a->getChosenDigitalSignatureDocument();
	$aDocumentOrder = $aDocument ? $aDocument->position : null;

	$bDocument = $b->getChosenDigitalSignatureDocument();
	$bDocumentOrder = $bDocument ? $bDocument->position : null;

	if ($aDocumentOrder== $bDocumentOrder) {
		$aSignatory = $a->getChosenDigitalSignaturePeople();
		$aSignatoryOrder = $aSignatory ? $aSignatory->position : null;

		$bSignatory = $b->getChosenDigitalSignaturePeople();
		$bSignatoryOrder = $bSignatory ? $bSignatory->position : null;

		if($aSignatoryOrder == $bSignatoryOrder) {
			return 0;
		}
		else {
			return $aSignatoryOrder < $bSignatoryOrder ? -1 : 1;
		}
	}
	return $aDocumentOrder < $bDocumentOrder ? -1 : 1;
}

/**
 * Class for DigitalSignatureSignatoryField
 */
class DigitalSignatureSignatoryField extends CommonObject
{
	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'digitalsignaturesignatoryfield';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'digitalsignaturemanager_digitalsignaturesignatoryfield';

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
	 * @var string String with name of icon for digitalsignaturesignatoryfield. Must be the part after the 'object_' into object_signaturedocumentsignatoryfield.png
	 */
	public $picto = 'digitalsignaturesignatoryfield@digitalsignaturemanager';

	/**
	 * @var DigitalSignatureRequest linkedDigitalSignatureRequest
	 */
	public $digitalSignatureRequest;

	/**
	 * @var DigitalSignatureDocument Chosen Digital signature Document
	 */
	public $digitalSignatureDocument;

	/**
	 * @var DigitalSignaturePeople Chosen Digital signature people
	 */
	public $digitalSignaturePeople;

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
		'c_rowid' => array('type'=>'integer', 'label'=>'Id from dictionary which generate this request', 'enabled'=>'1', 'position'=>1, 'notnull'=>1, 'visible'=>0, 'noteditable'=>'1', 'index'=>1, 'comment'=>"Id"),
		'fk_digitalsignaturerequest' => array('type'=>'integer:DigitalSignatureRequest:digitalsignaturemanager/class/digitalsignaturerequest.class.php', 'label'=>'Linked Digital Signature Request', 'enabled'=>'1', 'position'=>10, 'notnull'=>1, 'visible'=>1, 'index'=>1,),
		'fk_chosen_digitalsignaturepeople' => array('type'=>'integer:DigitalSignaturePeople:digitalsignaturemanager/class/digitalsignaturepeople.class.php', 'label'=>'Chosen Digital Signature people', 'enabled'=>'1', 'position'=>10, 'notnull'=>1, 'visible'=>1, 'index'=>1,),
		'fk_chosen_digitalsignaturedocument' => array('type'=>'integer:DigitalSignatureDocument:digitalsignaturemanager/class/digitalsignaturedocument.class.php', 'label'=>'Chosen Digital Signature Document', 'enabled'=>'1', 'position'=>11, 'notnull'=>1, 'visible'=>1, 'index'=>1,),
		'x' => array('type'=>'integer', 'label'=>'X axis coordinate', 'enabled'=>'1', 'position'=>12, 'notnull'=>0, 'visible'=>1,),
		'y' => array('type'=>'integer', 'label'=>'Y axis coordinate', 'enabled'=>'1', 'position'=>13, 'notnull'=>0, 'visible'=>1,),
		'page' => array('type'=>'integer', 'label'=>'page Number into documents', 'enabled'=>'1', 'position'=>14, 'notnull'=>0, 'visible'=>1,),
		'label' => array('type'=>'varchar(255)', 'label'=>'Signature Field Label', 'enabled'=>'1', 'position'=>15, 'notnull'=>0, 'visible'=>1,),
		'date_creation' => array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>'1', 'position'=>500, 'notnull'=>1, 'visible'=>-2,),
		'tms' => array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>'1', 'position'=>501, 'notnull'=>0, 'visible'=>-2,),
	);
	public $rowid;
	public $c_rowid;
	public $fk_digitalsignaturerequest;
	public $fk_chosen_digitalsignaturepeople;
	public $fk_chosen_digitalsignaturedocument;
	public $x;
	public $y;
	public $page;
	public $label;
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
		global $conf, $langs;

		$this->db = $db;

		if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && isset($this->fields['rowid'])) $this->fields['rowid']['visible'] = 0;
		if (empty($conf->multicompany->enabled) && isset($this->fields['entity'])) $this->fields['entity']['enabled'] = 0;

		// Unset fields that are disabled
		foreach ($this->fields as $key => $val)
		{
			if (isset($val['enabled']) && empty($val['enabled']))
			{
				unset($this->fields[$key]);
			}
		}

		// Translate some data of arrayofkeyval
		if (is_object($langs))
		{
			foreach ($this->fields as $key => $val)
			{
				if (is_array($val['arrayofkeyval']))
				{
					foreach ($val['arrayofkeyval'] as $key2 => $val2)
					{
						$this->fields[$key]['arrayofkeyval'][$key2] = $langs->trans($val2);
					}
				}
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
		return $this->fetchCommon($id, $ref);
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
		return $this->deleteCommon($user, $notrigger);
	}

	/**
	 * Function to fetch all people linked to a digital signature request
	 * @param DigitalSignatureRequest $digitalSignatureRequest linked digital signature request
	 * @return array|int array of digital signature people
	 */
	public function fetchSignatoryFieldForDigitalSignatureRequest(&$digitalSignatureRequest)
	{
		if(!$digitalSignatureRequest || !$digitalSignatureRequest->id) {
			return 0;
		}
		$result = $this->fetchAll('ASC', 'rowid', 0, 0, array('fk_digitalsignaturerequest'=>$digitalSignatureRequest->id));
		if(is_array($result)) {
			foreach($result as $signatoryField) {
				$signatoryField->digitalSignatureRequest = $digitalSignatureRequest;
				$signatoryField->fetch_optionals();
			}
			usort($result, 'sortSignatoryByDocumentAndSignatoryOrder');
			return $result;
		}
		return -1;
	}

	/**
	 * Function to check page number value validity
	 * @return string[] array of errors
	 */
	public function checkPageNumberValidity()
	{
		global $langs;
		$errors = array();
		if($this->page == null || $this->page <= 0 || !((int) $this->page)) {
			$errors[] = $langs->trans('DigitalSignatureManagerSignatoryFieldPageValueIncorrect');
		}
		$linkedDocument = $this->getChosenDigitalSignatureDocument();
		if($linkedDocument) {
			$numberOfPage = $linkedDocument->getNumberOfPage();
			if($this->page > $numberOfPage) {
				$errors[] = $langs->trans('DigitalSignatureManagerSignatoryFieldPageValueTooHigh', $numberOfPage);
			}
		}
		return $errors;
	}


	/**
	 * Function to x axis value
	 * @return string[] array of errors
	 */
	public function checkXAxisValidity()
	{
		global $langs;
		$errors = array();
		if($this->x == null) {
			$errors[] = $langs->trans('DigitalSignatureManagerSignatoryFieldXAxisValueIncorrect');
		}
		return $errors;
	}


	/**
	 * Function to y axis value
	 * @return string[] array of errors
	 */
	public function checkYAxisValidity()
	{
		global $langs;
		$errors = array();
		if($this->y == null) {
			$errors[] = $langs->trans('DigitalSignatureManagerSignatoryFieldYAxisValueIncorrect');
		}
		return $errors;
	}

	/**
	 * Function to check linked document value
	 * @return string [] array of errors
	 * */
	public function checkLinkedDocumentValidity()
	{
		global $langs;
		$errors = array();
		if($this->fk_chosen_digitalsignaturedocument == null) {
			$errors[] = $langs->trans('DigitalSignatureManagerSignatoryFieldDocumentNotChosen');
		}
		if(!$this->getChosenDigitalSignatureDocument()) {
			$errors[] = $langs->trans('DigitalSignatureManagerSignatoryFieldChosenDocumentDoesNotExistAnymore');
		}
		return $errors;
	}

		/**
	 * Function to check linked signatory value
	 * @return string [] array of errors
	 * */
	public function checkLinkedSignatoryValidity()
	{
		global $langs;
		$errors = array();
		if($this->fk_chosen_digitalsignaturepeople == null) {
			$errors[] = $langs->trans('DigitalSignatureManagerSignatoryFieldDSignatoryNotChosen');
		}
		if(!$this->getChosenDigitalSignaturePeople()) {
			$errors[] = $langs->trans('DigitalSignatureManagerSignatoryFieldChosenSignatoryDoesNotExistAnymore');
		}
		return $errors;
	}
	 /**
	 * Function to fetch linked digital signature request
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetchLinkedDigitalSignatureRequest()
	{
		dol_include_once('/digitalsignaturemanager/class/digitalsignaturerequest.class.php');
		$digitalSignatureRequestStatic = new DigitalSignatureRequest($this->db);
		if(!$this->fk_digitalsignaturerequest) {
			return 0;
		}
		$result = $digitalSignatureRequestStatic->fetch($this->fk_digitalsignaturerequest);
		if($result > 0) {
			$this->digitalSignatureRequest = $digitalSignatureRequestStatic;
		}
		return $result;
	}

	/**
	 * Function to get linked digital signature document
	 * @return DigitalSignatureDocument|null researched digital signature document
	 */
	public function getChosenDigitalSignatureDocument()
	{
		$digitalSignatureRequestStatic = $this->getLinkedDigitalSignatureRequest();
		return $digitalSignatureRequestStatic ? $digitalSignatureRequestStatic->getLinkedDocumentById($this->fk_chosen_digitalsignaturedocument) : null;
	}

	/**
	 * Function to get linked digital signature people
	 * @return DigitalSignaturePeople|null researched digital signature people
	 */
	public function getChosenDigitalSignaturePeople()
	{
		$digitalSignatureRequestStatic = $this->getLinkedDigitalSignatureRequest();
		return $digitalSignatureRequestStatic ? $digitalSignatureRequestStatic->getLinkedPeopleById($this->fk_chosen_digitalsignaturepeople) : null;
	}

	/**
	 * Function to get linked digital signature request
	 * @return DigitalSignatureRequest
	 */
	public function getLinkedDigitalSignatureRequest()
	{
		if(!$this->digitalSignatureRequest) {
			$this->fetchLinkedDigitalSignatureRequest();
		}
		return $this->digitalSignatureRequest;
	}

	/**
	 * Function to check that data are valid for this signatory field
	 * @return string[] array of errors when validating this signatory field
	 */
	public function checkDataValidForCreateRequestOnProvider()
	{
		return array_merge(
			$this->checkLinkedDocumentValidity(),
			$this->checkLinkedSignatoryValidity(),
			$this->checkPageNumberValidity(),
			$this->checkXAxisValidity(),
			$this->checkYAxisValidity()
		);
	}

	/**
	 * Clone an object into another one
	 *
	 * @param  	User 	$user      	User that creates
	 * @param  	int 	$fromid     Id of object to clone
	 * @param 	int     $newDigitalSignatureRequestId change digital signature request id with this id
	 * @param   int[]   $arrayOfOldPeopleIdAndClonedPeopleId When digital signature request is cloned, in order to update chosen people id
	 * @param   int[]   $arrayOfOldDocumentIdAndCloneDocumentId When digital signature request is cloned, in order to update chosen document id
	 * @return 	mixed 				New object created, <0 if KO
	 */
	public function createFromClone(User $user, $fromid, $newDigitalSignatureRequestId = 0, $arrayOfOldPeopleIdAndClonedPeopleId = null, $arrayOfOldDocumentIdAndCloneDocumentId = null)
	{
		global $extrafields;
		$error = 0;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$object = new self($this->db);

		$this->db->begin();

		// Load source object
		$object->fetch($fromid);

		// Reset some properties
		$object->id = null;
		$object->fk_user_creat = null;
		$object->import_key = null;

		if($newDigitalSignatureRequestId > 0) {
			$object->fk_digitalsignaturerequest = $newDigitalSignatureRequestId;
		}

		if(isset($arrayOfOldPeopleIdAndClonedPeopleId)) {
			$object->fk_chosen_digitalsignaturepeople = $arrayOfOldPeopleIdAndClonedPeopleId[$object->fk_chosen_digitalsignaturepeople];
		}

		if(isset($arrayOfOldDocumentIdAndCloneDocumentId)) {
			$object->fk_chosen_digitalsignaturedocument = $arrayOfOldDocumentIdAndCloneDocumentId[$object->fk_chosen_digitalsignaturedocument];
		}

		// Clear extrafields that are unique
		if (is_array($object->array_options) && count($object->array_options) > 0)
		{
			$extrafields->fetch_name_optionals_label($this->table_element);
			foreach ($object->array_options as $key => $option)
			{
				$shortkey = preg_replace('/options_/', '', $key);
				if (!empty($extrafields->attributes[$this->element]['unique'][$shortkey]))
				{
					unset($object->array_options[$key]);
				}
			}
		}

		// Create clone
		$object->context['createfromclone'] = 'createfromclone';
		$result = $object->create($user);
		if ($result < 0) {
			$error++;
			$this->error = $object->error;
			$this->errors = $object->errors;
		}

		unset($object->context['createfromclone']);

		// End
		if (!$error) {
			$this->db->commit();
			return $object;
		} else {
			$this->db->rollback();
			return -1;
		}
	}
}
