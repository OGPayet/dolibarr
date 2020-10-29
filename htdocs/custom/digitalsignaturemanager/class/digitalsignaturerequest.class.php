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
 * \file        class/digitalsignaturerequest.class.php
 * \ingroup     digitalsignaturemanager
 * \brief       This file is a CRUD class file for DigitalSignatureRequest (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
dol_include_once('/digitalsignaturemanager/class/digitalsignaturepeople.class.php');
dol_include_once('/digitalsignaturemanager/class/digitalsignaturedocument.class.php');
dol_include_once('/digitalsignaturemanager/class/digitalsignaturesignatoryfield.class.php');
dol_include_once('/digitalsignaturemanager/lib/digitalsignaturedocument.helper.php');
dol_include_once('/digitalsignaturemanager/class/digitalSignatureManagerUniversign.class.php');


/**
 * Class for DigitalSignatureRequest
 */
class DigitalSignatureRequest extends CommonObject
{
	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'digitalsignaturerequest';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'digitalsignaturemanager_digitalsignaturerequest';

	/**
	 * @var int  Does this object support multicompany module ?
	 * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
	 */
	public $ismultientitymanaged = 1;

	/**
	 * @var int  Does object support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 1;

	/**
	 * @var string String with name of icon for digitalsignaturerequest. Must be the part after the 'object_' into object_digitalsignaturerequest.png
	 */
	public $picto = 'digitalsignaturerequest@digitalsignaturemanager';


	const STATUS_DRAFT = 0;
	const STATUS_IN_PROGRESS = 1;
	const STATUS_CANCELED_BY_OPSY = 2;
	const STATUS_CANCELED_BY_SIGNERS = 5;
	const STATUS_SUCCESS = 6;
	const STATUS_FAILED = 8;
	const STATUS_EXPIRED = 8;
	const STATUS_DELETED_FROM_SIGNATURE_SERVICE = 9;

	/**
	 * Array of status type to manage picto
	 */
	public $statusType = array();

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
		'entity' => array('type'=>'int', 'label'=>'Entité', 'enabled'=>'1', 'position'=>2, 'notnull'=>1, 'visible'=>0,),
		'ref' => array('type'=>'varchar(128)', 'label'=>'Ref', 'enabled'=>'1', 'position'=>10, 'notnull'=>1, 'visible'=>4, 'noteditable'=>'1', 'default'=>'(PROV)', 'index'=>1, 'searchall'=>1, 'showoncombobox'=>'1', 'comment'=>"Reference of object"),
		'ref_client' => array('type'=>'varchar(255)', 'label'=>'Ref client', 'enabled'=>'1', 'position'=>40, 'notnull'=>0, 'visible'=>-1,),
		'fk_soc' => array('type'=>'integer:Societe:societe/class/societe.class.php:1:status=1 AND entity IN (__SHARED_ENTITIES__)', 'label'=>'ThirdParty', 'enabled'=>'1', 'position'=>50, 'notnull'=>1, 'visible'=>1, 'index'=>1, 'help'=>"DigitalSignatureManagerLinkToThirparty",),
		'fk_project' => array('type'=>'integer:Project:projet/class/project.class.php:1', 'label'=>'Project', 'enabled'=>'$conf->projet->enabled', 'position'=>52, 'notnull'=>-1, 'visible'=>-1, 'index'=>1,),
		'note_public' => array('type'=>'html', 'label'=>'NotePublic', 'enabled'=>'1', 'position'=>61, 'notnull'=>0, 'visible'=>0,),
		'note_private' => array('type'=>'html', 'label'=>'NotePrivate', 'enabled'=>'1', 'position'=>62, 'notnull'=>0, 'visible'=>0,),
		'date_creation' => array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>'1', 'position'=>500, 'notnull'=>1, 'visible'=>-2,),
		'tms' => array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>'1', 'position'=>501, 'notnull'=>0, 'visible'=>-2,),
		'fk_user_creat' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserAuthor', 'enabled'=>'1', 'position'=>510, 'notnull'=>1, 'visible'=>-2, 'foreignkey'=>'user.rowid',),
		'fk_user_modif' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserModif', 'enabled'=>'1', 'position'=>511, 'notnull'=>-1, 'visible'=>-2,),
		'import_key' => array('type'=>'varchar(14)', 'label'=>'ImportId', 'enabled'=>'1', 'position'=>1000, 'notnull'=>-1, 'visible'=>-2,),
		'status' => array('type'=>'smallint', 'label'=>'Status', 'enabled'=>'1', 'position'=>1000, 'notnull'=>1, 'visible'=>0, 'noteditable'=>'1', 'default'=>'0', 'index'=>1, 'arrayofkeyval'=>array('0'=>'Brouillon', '1'=>'Processus de signature en cours', '2'=>'Annul&eacute;', '3'=>'Signature termin&eacute;e', '9'=>'Erreur Technique'),),
		'externalId' => array('type'=>'varchar(255)', 'label'=>'Id of the signature process at external provider', 'enabled'=>'1', 'position'=>1002, 'notnull'=>0, 'visible'=>-5, 'index'=>1,),
		'externalUrl' => array('type'=>'varchar(255)', 'label'=>'Url given by universign after request have been created', 'enabled'=>'1', 'position'=>1002, 'notnull'=>0, 'visible'=>-5, 'index'=>1,),
		'elementtype' => array('type'=>'varchar(128)', 'label'=>'Linked Element Type', 'enabled'=>'1', 'position'=>1003, 'notnull'=>0, 'visible'=>0, 'index'=>1,),
		'fk_object' => array('type'=>'integer', 'label'=>'Id of the dolibarr object we sign', 'enabled'=>'1', 'position'=>1004, 'notnull'=>0, 'visible'=>0, 'index'=>1,),
		'listOfFileNameToSign' => array('type'=>'array', 'label'=>'Ordered Array of filename to be signed', 'enabled'=>'1', 'position'=>1005, 'notnull'=>0, 'visible'=>0,),
	);
	public $rowid;
	public $entity;
	public $ref;
	public $ref_client;
	public $fk_soc;
	public $fk_project;
	public $note_public;
	public $note_private;
	public $date_creation;
	public $tms;
	public $fk_user_creat;
	public $fk_user_modif;
	public $import_key;
	public $status;
	public $externalId;
	public $externalUrl;
	public $elementtype;
	public $fk_object;
	public $listOfFileNameToSign;
	// END MODULEBUILDER PROPERTIES

	/**
     * @var DigitalSignaturePeople[]     Array of Signatory people
     */

	public $people = array();


	/**
	 * @var DigitalSignatureDocument[] Array of digital signature documents
	 */
	public $documents = array();

	/**
	 * @var DigitalSignatureSignatoryField[] Array of digital signature documents
	 */
	public $signatoryFields = array();

	/**
	 * @var DigitalSignatureManagerUniversign Accessible service for this request
	 */
	public $externalProviderService;

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

		// Example to show how to set values of fields definition dynamically
		/*if ($user->rights->digitalsignaturemanager->digitalsignaturerequest->read) {
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
		$this->status = self::STATUS_DRAFT;
		$langs->load("digitalsignaturemanager@digitalsignaturemanager");
		$this->labelStatus = array(
			self::STATUS_DRAFT => $langs->trans('DigitalSignatureDraft'),
			self::STATUS_IN_PROGRESS => $langs->trans('DigitalSignatureInProgress'),
			self::STATUS_CANCELED_BY_OPSY => $langs->trans('DigitalSignatureCanceledInOpsy'),
			self::STATUS_CANCELED_BY_SIGNERS => $langs->trans('DigitalSignatureCanceledBySigners'),
			self::STATUS_FAILED => $langs->trans('DigitalSignatureFailed'),
			self::STATUS_EXPIRED => $langs->trans('DigitalSignatureExpired'),
			self::STATUS_SUCCESS => $langs->trans('DigitalSignatureSuccess'),
			self::STATUS_DELETED_FROM_SIGNATURE_SERVICE => $langs->trans('DigitalSignatureErrorDeletedInSignatureService')
		);

		$this->labelStatusShort = array(
			self::STATUS_DRAFT => $langs->trans('DigitalSignatureDraftShort'),
			self::STATUS_IN_PROGRESS => $langs->trans('DigitalSignatureInProgressShort'),
			self::STATUS_CANCELED_BY_OPSY => $langs->trans('DigitalSignatureCanceledInOpsyShort'),
			self::STATUS_CANCELED_BY_SIGNERS => $langs->trans('DigitalSignatureCanceledBySignersShort'),
			self::STATUS_FAILED => $langs->trans('DigitalSignatureFailedShort'),
			self::STATUS_EXPIRED => $langs->trans('DigitalSignatureExpired'),
			self::STATUS_SUCCESS => $langs->trans('DigitalSignatureSuccessShort'),
			self::STATUS_DELETED_FROM_SIGNATURE_SERVICE => $langs->trans('DigitalSignatureErrorDeletedInSignatureServiceShort')
		);

		$this->statusType = array(
			self::STATUS_DRAFT => 'status0',
			self::STATUS_IN_PROGRESS => 'status3',
			self::STATUS_CANCELED_BY_OPSY => 'status5',
			self::STATUS_CANCELED_BY_SIGNERS => 'status5',
			self::STATUS_SUCCESS => 'status4',
			self::STATUS_FAILED => 'status8',
			self::STATUS_EXPIRED => 'status8',
			self::STATUS_DELETED_FROM_SIGNATURE_SERVICE => 'status8'
		);

		$this->externalProviderService = new DigitalSignatureManagerUniversign($this);
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
	 * Update object into database
	 *
	 * @param  User $user      User that modifies
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = false)
	{
		if(empty($this->fk_project)){
			$this->fk_project = null;
		}
		return $this->updateCommon($user, $notrigger);
	}



	/**
	 * Create or Update object into database
	 *
	 * @param  User $user      User that creates
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, Id of created object if OK
	 */
	public function createOrUpdate(User $user, $notrigger = false)
	{
		$this->db->begin();
		if($this->id) {
			$result = $this->update($user, $notrigger);
		}
		else {
			$result = $this->create($user, $notrigger);
		}
		if($result > 0) {
			foreach($this->people as $people) {
				$result = $people->createOrUpdate($user, $notrigger);
				$this->errors = array_merge($this->errors, $people->errors);
				if($result < 0 ) {
					break;
				}
			}
		}
		if($result > 0) {
			$this->db->commit();
		}
		else {
			$this->db->rollback();
		}
		return $result;
	}

	/**
	 * Clone an object into another one
	 *
	 * @param  	User 	$user      	User that creates
	 * @param  	int 	$fromid     Id of object to clone
	 * @return 	mixed 				New object created, <0 if KO
	 */
	public function createFromClone(User $user, $fromid)
	{
		global $langs, $extrafields;
		$error = 0;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$object = new self($this->db);

		$this->db->begin();

		// Load source object
		$object->fetch($fromid);

		// Reset some properties
		unset($object->id);
		unset($object->fk_user_creat);
		unset($object->import_key);


		// Clear fields
		$object->ref = empty($this->fields['ref']['default']) ? "copy_of_".$object->ref : $this->fields['ref']['default'];
		$object->label = empty($this->fields['label']['default']) ? $langs->trans("CopyOf")." ".$object->label : $this->fields['label']['default'];
		$object->status = self::STATUS_DRAFT;
		// ...
		// Clear extrafields that are unique
		if (is_array($object->array_options) && count($object->array_options) > 0)
		{
			$extrafields->fetch_name_optionals_label($this->table_element);
			foreach ($object->array_options as $key => $option)
			{
				$shortkey = preg_replace('/options_/', '', $key);
				if (!empty($extrafields->attributes[$this->element]['unique'][$shortkey]))
				{
					//var_dump($key); var_dump($clonedObj->array_options[$key]); exit;
					unset($object->array_options[$key]);
				}
			}
		}

		// Create clone
		$object->context['createfromclone'] = 'createfromclone';
		$result = $object->createOrUpdate($user);
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
			$result = $this->fetch_optionals();
		}
		if ($result >= 0) {
			$result = $this->fetchPeople();
		}
		if($result >=0) {
			$result = $this->fetchDocuments();
		}

		if($result >=0) {
			$result = $this->fetchSignatoryField();
		}
		return $result;
	}

	/**
	 * Load object people in memory from the database
	 *
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetchPeople()
	{
		$staticDigitalSignaturePeople = new DigitalSignaturePeople($this->db);
		$fetchedPeople = $staticDigitalSignaturePeople->fetchPeopleOfDigitalSignatureRequest($this);
		$this->errors = array_merge($this->errors, $staticDigitalSignaturePeople->errors);
		if(is_array($fetchedPeople)) {
			$this->people = $fetchedPeople;
		}
		return empty($staticDigitalSignaturePeople->errors) ? 1 : -1;
	}

	/**
	* Load documents in memory from the database
	* @return int         <0 if KO, 0 if not found, >0 if OK
	*/
	public function fetchDocuments()
	{
		global $user;
		$staticDigitalSignatureDocument = new DigitalSignatureDocument($this->db);
		$linkedDocuments = $staticDigitalSignatureDocument->fetchDocumentForDigitalSignature($this, $user);
		$this->errors = array_merge($this->errors, $staticDigitalSignatureDocument->errors);
		if(is_array($linkedDocuments)) {
			$this->documents = $linkedDocuments;
		}
		return empty($staticDigitalSignatureDocument->errors) ? 1 : -1;
	}

	/**
	* Load signatory fields in memory from the database
	* @return int         <0 if KO, 0 if not found, >0 if OK
	*/
	public function fetchSignatoryField()
	{
		global $user;
		$staticDigitalSignatureSignatoryField = new DigitalSignatureSignatoryField($this->db);
		$linkedSignatoryField = $staticDigitalSignatureSignatoryField->fetchSignatoryFieldForDigitalSignatureRequest($this, $user);
		$this->errors = array_merge($this->errors, $staticDigitalSignatureSignatoryField->errors);
		if(is_array($linkedSignatoryField)) {
			$this->signatoryFields = $linkedSignatoryField;
		}
		return empty($staticDigitalSignatureSignatoryField->errors) ? 1 : -1;
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
		$this->db->begin();
		foreach($this->people as $people) {
			$result = $people->delete($user, $notrigger);
			$this->errors = array_merge($this->errors, $people->errors);
			if($result < 0) {
				break;
			}
		}
		if($result > 0) {
			$result = $this->deleteCommon($user, $notrigger);
		}
		if($result > 0) {
			$this->db->commit();
		}
		else {
			$this->db->rollback();
		}
		return $result;
	}

	/**
	 * Function to validate that all needed data have been put in order to be able to create one request
	 * @return array arrayOfErrors
	 */
	public function checkDataValidForCreateRequestOnProvider()
	{
	    global $langs;
		$errors = array();
		if(empty($this->people)) {
			$errors[] = $langs->trans('DigitalSignatureMissingSignatory');
		}

		if(empty($this->documents)) {
			$errors[] = $langs->trans('DigitalSignatureMissingFilesToSign');
		}

		foreach($this->documents as $document)
		{
			foreach($this->people as $people)
			{
				$signatureFieldForThisSignatoryOnThisDocument = getItemFromThisArray($this->signatoryFields, array(
					'fk_chosen_digitalsignaturedocument'=>$document->id,
					'fk_chosen_digitalsignaturepeople'=>$people->id,
				));
				if(!$signatureFieldForThisSignatoryOnThisDocument) {
					$errors = $langs->trans('DigitalSignatureMissingSignatoryField', $people->displayName(), $document->getDocumentName());
				}
			}
		}

		foreach($this->people as $people) {
			$errors = array_merge($errors, $people->checkDataValidForCreateRequestOnProvider());
		}
		return $errors;
	}

	/**
	 *	Create request on the provider and change status of this object in case of success
	 *
	 *	@param	User	$user			Object user that modify
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int						<0 if KO, 0=Nothing done, >0 if OK
	 */
	public function validateAndCreateRequestOnTheProvider($user, $notrigger = 0)
	{
		$this->db->begin();
		$validationErrors = $this->checkDataValidForCreateRequestOnProvider();
		if(!empty($validationErrors)) {
			$this->errors = array_merge($this->errors, $validationErrors);
			$this->db->rollback();
			return -1;
		}

		try {
			$returnedValues = $this->externalProviderService->create($this);
			if($returnedValues['id']) {
				$this->externalId = $returnedValues['id'];
			}
			if($returnedValues['url']) {
				$this->externalId = $returnedValues['url'];
			}
			$signatureRequestSuccessfullyCreated = true;
			$this->update($user);
		}
		catch (Exception $e) {
			$this->errors = array_merge($this->errors, $e);
			$signatureRequestSuccessfullyCreated = false;
		}
		$result = $this->setStatusCommon($user, self::STATUS_IN_PROGRESS, $notrigger, 'DIGITALSIGNATUREREQUEST_VALIDATE');
		if($result > 0 && $signatureRequestSuccessfullyCreated) {
			$this->db->commit();
			return 1;
		}
		else {
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 * We have detected that request has been deleted on the provider server
	 * We manage this event
	 * 	@param	User	$user			Object user that modify
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int						<0 if KO, 0=Nothing done, >0 if OK
	 */
	public function manageRequestDeletedInProvider($user, $notrigger = 0)
	{
		return $this->setStatusCommon($user, self::STATUS_DELETED_FROM_SIGNATURE_SERVICE, $notrigger, 'DIGITALSIGNATUREREQUEST_DELETEDINPROVIDER');
	}

	/**
	 *	We have to cancel this request on the provider side and manage success of it in this app
	 *
	 *	@param	User	$user			Object user that modify
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int						<0 if KO, 0=Nothing done, >0 if OK
	 */
	public function cancelRequest($user, $notrigger = 0)
	{
		if($this->externalId && $this->externalProviderService->cancel($this->externalId)) {
			return $this->setStatus($user, self::STATUS_CANCELED_BY_OPSY, $notrigger);
		}
	}


	/**
	 * Function to manage status change with proper trigger
	 * 	@param	User	$user			Object user that modify
	 * 	@param	string	$statusValue	status code value
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int						<0 if KO, 0=Nothing done, >0 if OK$
	 */
	public function setStatus($user, $statusValue, $notrigger = false)
	{
		$statusAndTriggerCode = array(
			self::STATUS_IN_PROGRESS => 'DIGITALSIGNATUREREQUEST_INPROGRESS',
			self::STATUS_CANCELED_BY_OPSY => 'DIGITALSIGNATUREREQUEST_CANCELEDBYOPSY',
			self::STATUS_CANCELED_BY_SIGNERS => 'DIGITALSIGNATUREREQUEST_CANCELEDBYSIGNERS',
			self::STATUS_SUCCESS => 'DIGITALSIGNATUREREQUEST_SUCCESS',
			self::STATUS_FAILED => 'DIGITALSIGNATUREREQUEST_FAILED',
			self::STATUS_EXPIRED => 'DIGITALSIGNATUREREQUEST_EXPIRED',
			self::STATUS_DELETED_FROM_SIGNATURE_SERVICE => 'DIGITALSIGNATUREREQUEST_DELETEDINPROVIDER'
		);
		if($statusValue != $this->statut) {
			return $this->setStatusCommon($user, $statusValue, $notrigger, $statusAndTriggerCode[$statusValue]);
		}
		else {
			return 0;
		}
	}

	/**
	 *  Return a link to the object card (with optionaly the picto)
	 *
	 *  @param  int     $withpicto                  Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 *  @param  string  $option                     On what the link point to ('nolink', ...)
	 *  @param  int     $notooltip                  1=Disable tooltip
	 *  @param  string  $morecss                    Add more css on link
	 *  @param  int     $save_lastsearch_value      -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *  @return	string                              String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
	{
		global $conf, $langs, $hookmanager;

		if (!empty($conf->dol_no_mouse_hover)) $notooltip = 1; // Force disable tooltips

		$result = '';

		$label = '<u>'.$langs->trans("DigitalSignatureRequest").'</u>';
		$label .= '<br>';
		$label .= '<b>'.$langs->trans('Ref').':</b> '.$this->ref;
		if (isset($this->status)) {
			$label .= '<br><b>'.$langs->trans("Status").":</b> ".$this->getLibStatut(5);
		}

		$url = dol_buildpath('/digitalsignaturemanager/digitalsignaturerequest_card.php', 1).'?id='.$this->id;

		if ($option != 'nolink')
		{
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) $add_save_lastsearch_values = 1;
			if ($add_save_lastsearch_values) $url .= '&save_lastsearch_values=1';
		}

		$linkclose = '';
		if (empty($notooltip))
		{
			if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
			{
				$label = $langs->trans("ShowDigitalSignatureRequest");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ' title="'.dol_escape_htmltag($label, 1).'"';
			$linkclose .= ' class="classfortooltip'.($morecss ? ' '.$morecss : '').'"';
		}
		else $linkclose = ($morecss ? ' class="'.$morecss.'"' : '');

		$linkstart = '<a href="'.$url.'"';
		$linkstart .= $linkclose.'>';
		$linkend = '</a>';

		$result .= $linkstart;

		if (empty($this->showphoto_on_popup)) {
			if ($withpicto) $result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
		} else {
			if ($withpicto) {
				require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

				list($class, $module) = explode('@', $this->picto);
				$upload_dir = $conf->$module->multidir_output[$conf->entity]."/$class/".dol_sanitizeFileName($this->ref);
				$filearray = dol_dir_list($upload_dir, "files");
				$filename = $filearray[0]['name'];
				if (!empty($filename)) {
					$pospoint = strpos($filearray[0]['name'], '.');

					$pathtophoto = $class.'/'.$this->ref.'/thumbs/'.substr($filename, 0, $pospoint).'_mini'.substr($filename, $pospoint);
					if (empty($conf->global->{strtoupper($module.'_'.$class).'_FORMATLISTPHOTOSASUSERS'})) {
						$result .= '<div class="floatleft inline-block valignmiddle divphotoref"><div class="photoref"><img class="photo'.$module.'" alt="No photo" border="0" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$module.'&entity='.$conf->entity.'&file='.urlencode($pathtophoto).'"></div></div>';
					}
					else {
						$result .= '<div class="floatleft inline-block valignmiddle divphotoref"><img class="photouserphoto userphoto" alt="No photo" border="0" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$module.'&entity='.$conf->entity.'&file='.urlencode($pathtophoto).'"></div>';
					}

					$result .= '</div>';
				}
				else {
					$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
				}
			}
		}

		if ($withpicto != 2) $result .= $this->ref;

		$result .= $linkend;
		//if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

		global $action, $hookmanager;
		$hookmanager->initHooks(array('digitalsignaturerequestdao'));
		$parameters = array('id'=>$this->id, 'getnomurl'=>$result);
		$reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) $result = $hookmanager->resPrint;
		else $result .= $hookmanager->resPrint;

		return $result;
	}

	/**
	 *  Return label of the status
	 *
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return	string 			       Label of status
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return the status
	 *
	 *  @param	int		$status        Id status
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return string 			       Label of status
	 */
	public function LibStatut($status, $mode = 0)
	{
		global $langs;
		$labelStatus = $this->labelStatus[$status];
		$labelStatusShort = $this->labelStatusShort[$status];
		if($status == self::STATUS_IN_PROGRESS){
			$labelStatus .= $langs->trans('DigitalSignatureRequestActionToDoForPeople') . ' ' . $this->getInProgressStatusLabelSuffix();
		}
		elseif($status == self::STATUS_CANCELED_BY_SIGNERS){
			$labelStatus .= $langs->trans('DigitalSignatureRequestActionCanceledBy') . ' ' . $this->getCanceledStatusLabelSuffix();
		}
		return dolGetStatus($labelStatus, $labelStatusShort, '', $this->statusType[$status], $mode);
	}

	/**
	 *	Load the info information in the object
	 *
	 *	@param  int		$id       Id of object
	 *	@return	void
	 */
	public function info($id)
	{
		$sql = 'SELECT rowid, date_creation as datec, tms as datem,';
		$sql .= ' fk_user_creat, fk_user_modif';
		$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';
		$sql .= ' WHERE t.rowid = '.$id;
		$result = $this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);
				$this->id = $obj->rowid;
				if ($obj->fk_user_author)
				{
					$cuser = new User($this->db);
					$cuser->fetch($obj->fk_user_author);
					$this->user_creation = $cuser;
				}

				if ($obj->fk_user_valid)
				{
					$vuser = new User($this->db);
					$vuser->fetch($obj->fk_user_valid);
					$this->user_validation = $vuser;
				}

				if ($obj->fk_user_cloture)
				{
					$cluser = new User($this->db);
					$cluser->fetch($obj->fk_user_cloture);
					$this->user_cloture = $cluser;
				}

				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->datem);
				$this->date_validation   = $this->db->jdate($obj->datev);
			}

			$this->db->free($result);
		}
		else
		{
			dol_print_error($this->db);
		}
	}

	/**
	 *  Returns the reference to the following non used object depending on the active numbering module.
	 *
	 *  @return string      		Object free reference
	 */
	public function getNextNumRef()
	{
		global $langs, $conf;
		$langs->load("digitalsignaturemanager@digitalsignaturerequest");

		if (empty($conf->global->DIGITALSIGNATUREMANAGER_DIGITALSIGNATUREREQUEST_ADDON)) {
			$conf->global->DIGITALSIGNATUREMANAGER_DIGITALSIGNATUREREQUEST_ADDON = 'mod_digitalsignaturerequest_standard';
		}

		if (!empty($conf->global->DIGITALSIGNATUREMANAGER_DIGITALSIGNATUREREQUEST_ADDON))
		{
			$mybool = false;

			$file = $conf->global->DIGITALSIGNATUREMANAGER_DIGITALSIGNATUREREQUEST_ADDON.".php";
			$classname = $conf->global->DIGITALSIGNATUREMANAGER_DIGITALSIGNATUREREQUEST_ADDON;

			// Include file with class
			$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
			foreach ($dirmodels as $reldir)
			{
				$dir = dol_buildpath($reldir."core/modules/digitalsignaturemanager/");

				// Load file with numbering class (if found)
				$mybool |= @include_once $dir.$file;
			}

			if ($mybool === false)
			{
				dol_print_error('', "Failed to include file ".$file);
				return '';
			}

			if (class_exists($classname)) {
				$obj = new $classname();
				$numref = $obj->getNextValue($this);

				if ($numref != '' && $numref != '-1')
				{
					return $numref;
				}
				else
				{
					$this->error = $obj->error;
					//dol_print_error($this->db,get_class($this)."::getNextNumRef ".$obj->error);
					return "";
				}
			} else {
				print $langs->trans("Error")." ".$langs->trans("ClassNotFound").' '.$classname;
				return "";
			}
		}
		else
		{
			print $langs->trans("ErrorNumberingModuleNotSetup", $this->element);
			return "";
		}
	}

	/**
	 * Action executed by scheduler
	 * CAN BE A CRON TASK. In such a case, parameters come from the schedule job setup field 'Parameters'
	 * Use public function doScheduledJob($param1, $param2, ...) to get parameters
	 *
	 * @return	int			0 if OK, <>0 if KO (this function is used also by cron so only 0 is OK)
	 */
	public function doScheduledJob()
	{
		global $conf, $langs;

		//$conf->global->SYSLOG_FILE = 'DOL_DATA_ROOT/dolibarr_mydedicatedlofile.log';

		$error = 0;
		$this->output = '';
		$this->error = '';

		dol_syslog(__METHOD__, LOG_DEBUG);

		$now = dol_now();

		$this->db->begin();

		// ...

		$this->db->commit();

		return $error;
	}

	/**
	 * Get base upload dir for object of this module
	 * @return string local path
	 */
	public function getBaseUploadDir()
	{
		global $conf;
		return $conf->digitalsignaturemanager->multidir_output[$this->entity ? $this->entity : $conf->entity];
	}

	/**
	 * Get relative upload dir for files to sign
	 * @return string relative path
	 */
	public function getRelativePathForFilesToSign()
	{
		return "digitalsignaturerequest/" . dol_sanitizeFileName($this->id) . "/filesToSign";
	}

	/**
	 * Get relative upload dir for signed files
	 * @return string relative path
	 */
	public function getRelativePathForSignedFiles()
	{
		return "digitalsignaturerequest/" . dol_sanitizeFileName($this->id) . "/signedFiles";
	}

	/**
	 * Get upload dir of files to be signed
	 * @return string local path
	 */
	public function getUploadDirOfFilesToSign()
	{
		return $this->getBaseUploadDir() . "/" . $this->getRelativePathForFilesToSign();
	}

	/**
	 * Get upload dir of signed files
	 * @return string local path
	 */
	public function getUploadDirOfSignedFiles()
	{
		return $this->getBaseUploadDir() . "/" .$this->getRelativePathForSignedFiles();
	}


	/**
	 * Get List Of Files To Sign
	 *  @param	string		$types        	Can be "directories", "files", or "all"
	 *  @param	int			$recursive		Determines whether subdirectories are searched
	 *  @param	string		$filter        	Regex filter to restrict list. This regex value must be escaped for '/' by doing preg_quote($var,'/'), since this char is used for preg_match function,
	 *                                      but must not contains the start and end '/'. Filter is checked into basename only.
	 *  @param	array		$excludefilter  Array of Regex for exclude filter (example: array('(\.meta|_preview.*\.png)$','^\.')). Exclude is checked both into fullpath and into basename (So '^xxx' may exclude 'xxx/dirscanned/...' and dirscanned/xxx').
	 *  @param	string		$sortcriteria	Sort criteria ("","fullname","relativename","name","date","size")
	 *  @param	string		$sortorder		Sort order (SORT_ASC, SORT_DESC)
	 *	@param	int			$mode			0=Return array minimum keys loaded (faster), 1=Force all keys like date and size to be loaded (slower), 2=Force load of date only, 3=Force load of size only
	 *  @param	int			$nohook			Disable all hooks
	 *  @param	string		$relativename	For recursive purpose only. Must be "" at first call.
	 *  @return	array						Array of array('name'=>'xxx','fullname'=>'/abc/xxx','date'=>'yyy','size'=>99,'type'=>'dir|file',...)
	 *  @see dol_dir_list_indatabase
	 * @return array
	 */
	public function getListOfFilesToSign($types = "all", $recursive = 0, $filter = "", $excludefilter = "", $sortcriteria = "name", $sortorder = SORT_ASC, $mode = 1, $nohook = 0, $relativename = "")
	{
		return dol_dir_list($this->getUploadDirOfFilesToSign(), $types, $recursive, $filter, $excludefilter, $sortcriteria, $sortorder, $mode, $nohook, $relativename);
	}

	/**
	 * Get List Of Signed Files
	 *  @param	string		$types        	Can be "directories", "files", or "all"
	 *  @param	int			$recursive		Determines whether subdirectories are searched
	 *  @param	string		$filter        	Regex filter to restrict list. This regex value must be escaped for '/' by doing preg_quote($var,'/'), since this char is used for preg_match function,
	 *                                      but must not contains the start and end '/'. Filter is checked into basename only.
	 *  @param	array		$excludefilter  Array of Regex for exclude filter (example: array('(\.meta|_preview.*\.png)$','^\.')). Exclude is checked both into fullpath and into basename (So '^xxx' may exclude 'xxx/dirscanned/...' and dirscanned/xxx').
	 *  @param	string		$sortcriteria	Sort criteria ("","fullname","relativename","name","date","size")
	 *  @param	string		$sortorder		Sort order (SORT_ASC, SORT_DESC)
	 *	@param	int			$mode			0=Return array minimum keys loaded (faster), 1=Force all keys like date and size to be loaded (slower), 2=Force load of date only, 3=Force load of size only
	 *  @param	int			$nohook			Disable all hooks
	 *  @param	string		$relativename	For recursive purpose only. Must be "" at first call.
	 *  @return	array						Array of array('name'=>'xxx','fullname'=>'/abc/xxx','date'=>'yyy','size'=>99,'type'=>'dir|file',...)
	 *  @see dol_dir_list_indatabase
	 * @return array
	 */
	public function getListOfSignedFiles($types = "all", $recursive = 0, $filter = "", $excludefilter = "", $sortcriteria = "name", $sortorder = SORT_ASC, $mode = 1, $nohook = 0, $relativename = "")
	{
		return dol_dir_list($this->getUploadDirOfSignedFiles(), $types, $recursive, $filter, $excludefilter, $sortcriteria, $sortorder, $mode, $nohook, $relativename);
	}

	/**
	 * Return digitalsignaturepeople that should do an action
	 * @return DigitalSignaturePeople|null
	 */
	private function getPeopleThatShouldDoAnAction()
	{
		$result = null;
		foreach($this->people as &$people){
			if($people->status == $people::STATUS_WAITING_TO_SIGN){
				$result = $people;
				break;
			}
		}
		return $result;
	}

	/**
	 * Return last digitalsignaturepeople that have done an action
	 * @return DigitalSignaturePeople|null
	 */
	public function getLastPeopleThatDidAnAction()
	{
		$result = null;
		foreach(array_reverse($this->people) as &$people){
			if($people->status == $people::STATUS_SUCCESS || $people->status == $people::STATUS_REFUSED){
				$result = $people;
				break;
			}
		}
		return $result;
	}

	/**
	 * Return digitalsignaturepeople that canceled progress
	 * @return DigitalSignaturePeople|null
	 */
	public function getPeopleThatCanceledProcess()
	{
		$result = null;
		foreach(array_reverse($this->people) as &$people){
			if($people->status == $people::STATUS_REFUSED){
				$result = $people;
				break;
			}
		}
		return $result;
	}

	/**
	 * Get label for in_progress status
	 * @return string
	 */
	private function getInProgressStatusLabelSuffix()
	{
		$result = "";
		$currentSignatory = $this->getPeopleThatShouldDoAnAction();
		if($currentSignatory){
			$result = $currentSignatory->displayName();
		}
		return $result;
	}

	/**
	 * Get label for canceled status
	 * @return string
	 */
	private function getCanceledStatusLabelSuffix()
	{
		$result = "";
		$peopleThatCanceled = $this->getPeopleThatCanceledProcess();
		if($peopleThatCanceled){
			$result = $peopleThatCanceled->displayName();
		}
		else
		{
			global $langs;
			$result = $langs->trans("DigitalSignatureRequestCanceledFromOpsy");
		}
		return $result;
	}

	/**
	 * Is this object editable by the user
	 * @return boolean
	*/
	public function isEditable()
	{
		return $this->statut == self::STATUS_DRAFT;
	}

	/**
	 * Update data from external service
	 */
	public function updateDataFromExternalService($user) {
		return $this->externalProviderService->getAndUpdateData($this)
	}
}
