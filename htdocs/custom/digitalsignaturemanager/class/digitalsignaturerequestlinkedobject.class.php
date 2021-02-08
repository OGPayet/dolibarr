<?php
/* Copyright (c) 2020  Alexis LAURIER    <contact@alexislaurier.fr>
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

dol_include_once('/atlantis/class/extendedEcm.class.php');
dol_include_once('/digitalsignaturemanager/class/digitalsignaturerequest.class.php');

/**
 *	\file       digitalsignaturemanager/class/digitalsignaturerequestlinkedobject.class.php
 *  \ingroup    core
 *	\brief      File of class DigitalSignatureRequestLinkedObject to manage interraction with common object instance to sign them
 */
class DigitalSignatureRequestLinkedObject
{
	/**
	 * @var DoliDb		Database handler (result of a new DoliDB)
	 */
	public $db;

	/**
	 * @var CommonObject  Instance of the dolibarr object to sign
	 */
	public $object;

	/**
	 * @var string[]  Array of errors
	 */
	public $errors = array();

	/**
	 * @var DigitalSignatureRequest[]
	 */
	public static $cacheDigitalSignatureRequest = array();

	/**
	 * Constructor
	 *
	 * @param   CommonObject $object from dolibarr to be handle
	 */
	public function __construct(CommonObject &$object)
	{
		$this->db = $object->db;
		$this->object = $object;
	}

	/**
	 * Get linked digital signature request to an object
	 * @param bool $forceUpdateOfCache should we forget cached data
	 * @return  DigitalSignatureRequest[]|null
	 */
	public function getLinkedDigitalSignatureRequests($forceUpdateOfCache = false)
	{
		//We are looking for linked object on digital signature request
		//Indeed it would be better to look for digital signature document relating to this common object
		//And then get digital signature request
		//Dolibarr linked elements should only be used for visual purpose and standard workflow

		if(!$this->object->linkedObjects || $forceUpdateOfCache) {
			$this->object->fetchObjectLinked();
		}
		$result = array_values($this->object->linkedObjects[DigitalSignatureRequest::$staticElement] ?? array());
		uasort($result, array($this, 'sortLinkedDigitalSignatureRequestToAnObject'));
		return array_values($result);
	}

	/**
	 * Sort digital signature request when fetching linked digital signature request to a common object
	 * @param DigitalSignatureRequest $a first element
	 * @param DigitalSignatureRequest $b second element
	 * @return int values for uasort
	 */
	public static function sortLinkedDigitalSignatureRequestToAnObject($a, $b) {
		if (!isset($a) || !isset($a->date_creation) || is_null($a->date_creation)) {
			$result = -1;
		}
		else if (!isset($b) || !isset($b->date_creation) || is_null($b->date_creation)) {
			$result = 1;
		}
		else if ($a->date_creation == $b->date_creation) {
			$result = 0;
		}
		else {
			$result = $a->date_creation < $b->date_creation ? 1 : -1;
		}
		return $result;
	}

	/**
	 * Get linked digital signature request with researchedStatus
	 * @param	string $researchedStatus Researched digital signature request
	 * @return DigitalSignatureRequest[]
	 */
	public function getDigitalSignatureRequestsWithStatus($researchedStatus)
	{
		$result = array();
		foreach ($this->getLinkedDigitalSignatureRequests() as &$digitalSignatureRequest) {
			if ($digitalSignatureRequest->status == $researchedStatus) {
				$result[] = &$digitalSignatureRequest;
			}
		}
		return $result;
	}

	/**
	 * Get current in progress digital signature request to an object
	 * @param	string $researchedStatus Researched digital signature request
	 * @return  DigitalSignatureRequest|null
	 * */
	public function getFirstDigitalSignatureRequestWithStatut($researchedStatus)
	{
		$researchedLinkedRequest = $this->getDigitalSignatureRequestsWithStatus($researchedStatus);
		return array_shift($researchedLinkedRequest);
	}


	/**
	 * Get current in progress digital signature requests to an object
	 * @return  DigitalSignatureRequest[]
	 * */
	public function getInProgressDigitalSignatureRequests()
	{
		return $this->getDigitalSignatureRequestsWithStatus(DigitalSignatureRequest::STATUS_IN_PROGRESS);
	}

	/**
	 * Get current in progress digital signature request to an object
	 * @return  DigitalSignatureRequest|null
	 * */
	public function getInProgressDigitalSignatureRequest()
	{
		return $this->getFirstDigitalSignatureRequestWithStatut(DigitalSignatureRequest::STATUS_IN_PROGRESS);
	}

	/**
	 * Get current ended signature with data not considered as staled
	 * @return DigitalSignatureRequest|null
	 */
	public function getEndedLinkedSignatureWithNoStaledData()
	{
		$crudeList = $this->getDigitalSignatureRequestsWithStatus(DigitalSignatureRequest::STATUS_SUCCESS);
		foreach($crudeList as $request) {
			if(!$request->is_staled_according_to_source_object) {
				return $request;
			}
		}
		return null;
	}

	/**
	 * Function to know if there is a digital signature in progress for an object
	 * @return  bool
	 */
	public function isThereADigitalSignatureInProgress()
	{
		return $this->getInProgressDigitalSignatureRequest() != null;
	}

	/**
	 * Function to know if there is a digital signature in progress for an object
	 * @return  bool
	 */
	public function isThereADigitalSignatureInDraft()
	{
		return $this->getFirstDigitalSignatureRequestWithStatut(DigitalSignatureRequest::STATUS_DRAFT) != null;
	}

	/**
	 * Function to know if an user is allowed to create a signature request
	 * @param User $user user requesting to do an action
	 * @return bool
	 */
	public function isUserAbleToCreateRequest($user)
	{
		return !empty($this->object) && !empty($user->rights->digitalsignaturemanager->request->create);
	}

	/**
	 * Function to know if an user is allowed to cancel a signature request
	 * @param User $user user requesting to do an action
	 * @return bool
	 */
	public function isUserAbleToCancelRequest($user)
	{
		return !empty($this->object) && !empty($user->rights->digitalsignaturemanager->request->edit);
	}

	/**
	 * Function to know if an user is allowed to refresh a signature request
	 * @param User $user user requesting to do an action
	 * @return bool
	 */
	public function isUserAbleToRefreshRequest($user)
	{
		return !empty($this->object) && !empty($user->rights->digitalsignaturemanager->request->read);
	}

	/**
	 * Function to know if user is allowed to reset a signature request
	 * @param User $user user requesting to do an action
	 * @return bool
	 */
	public function isUserAbleToResetRequest($user)
	{
		return !empty($this->object)
		&& !empty($user->rights->digitalsignaturemanager->request->create)
		&& !empty($user->rights->digitalsignaturemanager->request->delete);
	}

	/**
	 * Function to create digital signature request instance with document and signatory fields according to post content
	 * @param User $user user requesting creation of request
	 * @param ExtendedEcm[] $selectedFiles Array of files to sign
	 * @param DigitalSignaturePeople[][] $signatoryInstancePerEcmFileAndDictionaryRowId array('ecmFileId'=>array('dictionnaryRowId'=>DigitalSignaturePeopleInstance))
	 * @param String $invitationMessage Message to use to invite users
	 * @return DigitalSignatureRequest|null
	 */
	public function createDigitalSignatureRequestFromLinkedObject($user, $selectedFiles, $signatoryInstancePerEcmFileAndDictionaryRowId, $invitationMessage = null)
	{
		$this->db->begin();
		global $langs;
		$errors = array();
		$digitalSignatureRequest = new DigitalSignatureRequest($this->db);
		$digitalSignatureRequest->fk_soc = $this->object->socid;
		$digitalSignatureRequest->fk_project = $this->object->fk_project;
		$digitalSignatureRequest->invitation_message = $invitationMessage;

		if ($digitalSignatureRequest->create($user) < 0) {
			$errors = array_merge($errors, $digitalSignatureRequest->errors);
		}

		$arrayOfOriginalAndCopiedEcmFile = array();
		//We build digitalsignaturedocument array for this request
		foreach ($selectedFiles as $ecmFile) {
			if (empty($errors)) {
				$digitalSignatureDocument = new DigitalSignatureDocument($this->db);
				$copyEcmFile = $ecmFile->copyFileTo($digitalSignatureRequest->getRelativePathToDolDataRootForFilesToSign());
				if (!$copyEcmFile) {
					$errors[] = $langs->trans("DigitalSignatureManagerErrorWhileCopyingFile", $ecmFile->filename);
					$errors = array_merge($ecmFile->errors);
				} else {
					$arrayOfOriginalAndCopiedEcmFile[$copyEcmFile->id] = $ecmFile;
					$digitalSignatureDocument->ecmFile = $copyEcmFile;
					$digitalSignatureDocument->fk_ecm = $copyEcmFile->id;
					$digitalSignatureDocument->fk_digitalsignaturerequest = $digitalSignatureRequest->id;
					$digitalSignatureDocument->digitalSignatureRequest = $digitalSignatureRequest;
					$digitalSignatureDocument->elementtype = $ecmFile->elementtype;
					$digitalSignatureDocument->fk_object = $ecmFile->fk_object;
					$digitalSignatureDocument->create($user);
					$errors = array_merge($errors, $digitalSignatureDocument->errors);
					$digitalSignatureRequest->documents[] = $digitalSignatureDocument;
				}
			}
		}


		//We build digitalsignaturepeople array for this request
		$listOfSignatoryByIdentifier = array();
		$listOfSignatoryIdentifierByEcmFileAndSignatoryFieldDictionnaryId = array();
		foreach ($selectedFiles as $id => $ecmFile) {
			foreach ($this->getSignatoryFieldsDictionaryLinesForFile($ecmFile) as $dictionaryItem) {
				$signatorySelected = $signatoryInstancePerEcmFileAndDictionaryRowId[$id][$dictionaryItem->c_rowid];
				if ($signatorySelected && $signatorySelected->generateUniqueIdentifier()) {
					$signatoryIdentifier = $signatorySelected->generateUniqueIdentifier();
					if (!$listOfSignatoryByIdentifier[$signatoryIdentifier]) {
						$signatorySelected->fk_digitalsignaturerequest = $digitalSignatureRequest->id;
						$signatorySelected->digitalSignatureRequest = $digitalSignatureRequest;
						$signatorySelected->create($user);
						$errors = array_merge($errors, $signatorySelected->errors);
						$listOfSignatoryByIdentifier[$signatoryIdentifier] = $signatorySelected;
					}
					$listOfSignatoryIdentifierByEcmFileAndSignatoryFieldDictionnaryId[$dictionaryItem->c_rowid][$ecmFile->id] = $signatoryIdentifier;
				}
			}
		}

		$digitalSignatureRequest->people = array_values($listOfSignatoryByIdentifier);

		//We create signatory fields

		foreach ($digitalSignatureRequest->documents as $document) {
			if (empty($errors)) {
				$originalEcmFile = $arrayOfOriginalAndCopiedEcmFile[$document->fk_ecm];
				$dictionaryItems = $this->getSignatoryFieldsDictionaryLinesForFile($originalEcmFile);
				foreach ($dictionaryItems as $dictionaryItem) {
					$digitalSignatureSignatoryField = new DigitalSignatureSignatoryField($this->db);
					$digitalSignatureSignatoryField->c_rowid = $dictionaryItem->c_rowid;
					$digitalSignatureSignatoryField->label = $dictionaryItem->label;
					$digitalSignatureSignatoryField->page = $document->correctPageNumber($dictionaryItem->pageNumber);
					$digitalSignatureSignatoryField->x = (int) $dictionaryItem->x;
					$digitalSignatureSignatoryField->y = (int) $dictionaryItem->y;
					$digitalSignatureSignatoryField->fk_digitalsignaturerequest = $digitalSignatureRequest->id;
					$digitalSignatureSignatoryField->fk_chosen_digitalsignaturedocument = $document->id;
					$signatoryIdentifierForThisField = $listOfSignatoryIdentifierByEcmFileAndSignatoryFieldDictionnaryId[$dictionaryItem->c_rowid][$originalEcmFile->id];
					$signatoryOfThisField = $listOfSignatoryByIdentifier[$signatoryIdentifierForThisField];
					$digitalSignatureSignatoryField->fk_chosen_digitalsignaturepeople = $signatoryOfThisField->id;
					$digitalSignatureSignatoryField->create($user);
					$errors = array_merge($errors, $digitalSignatureSignatoryField->errors);
					$digitalSignatureRequest->signatoryFields[] = $digitalSignatureSignatoryField;
				}
			}
		}

		//We linked dolibarr object to this request
		$alreadyObjectLinkedTypeAndIds = array();
		foreach($digitalSignatureRequest->documents as $document)
		{
			if(!empty($document->elementtype) && !empty($document->fk_object) && empty($alreadyObjectLinkedTypeAndIds[$document->elementtype][$document->fk_object]))
			{
				if($digitalSignatureRequest->add_object_linked($document->elementtype, $document->fk_object) < 0)
				{
					$errors[] = $digitalSignatureRequest->error;
				}
				else
				{
					$alreadyObjectLinkedTypeAndIds[$document->elementtype][$document->fk_object] = true;
				}
			}
		}

		$this->errors = array_merge($this->errors, $errors);
		if (empty($errors)) {
			$this->db->commit();
			//dolibarr bullshit adaptation - as createCommon change some values into database but do not reflect it on object
			$digitalSignatureRequest->fetch($digitalSignatureRequest->id);
			if (!self::$cacheDigitalSignatureRequest[$this->object->table_element][$this->object->id]) {
				self::$cacheDigitalSignatureRequest[$this->object->table_element][$this->object->id] = array();
			}
			self::$cacheDigitalSignatureRequest[$this->object->table_element][$this->object->id][] = $digitalSignatureRequest;
			return $digitalSignatureRequest;
		} else {
			//We remove files
			$digitalSignatureRequest->deleteFilesToSign();
			$this->db->rollback();
			return null;
		}
	}

	/**
	 * Function to get list of files to sign for a digital signature manager request
	 * @param string $directory Directory to be watched
	 * @return ExtendedEcm[]
	 */
	public function getEcmListForDirectory($directory)
	{
		$relativePathToDolDataRoot = ExtendedEcm::getRelativeDirectoryOfADirectory($directory);
		$result = array();
		if ($relativePathToDolDataRoot) {
			global $user;
			ExtendedEcm::cleanEcmFileDatabase($this->db, $relativePathToDolDataRoot, $user);
			$extendedEcm = new ExtendedEcm($this->db);
			$crudeResult = $extendedEcm->fetchAll('DESC', 'GREATEST(date_c, date_m) DESC, rowid ', 0, 0, array('filepath' => $relativePathToDolDataRoot, 'filename' => '.pdf'));
			foreach ($crudeResult as &$ecm) {
				$result[$ecm->id] = $ecm;
			}
		}
		return $result;
	}

	/**
	 * Function to get directory where search files for propal
	 * @param Propal $object object on which search where are stored files
	 * @return string|null
	 */
	private function getPropalDirectory()
	{
		if ($this->object->ref) {
			global $conf;
			return $conf->propal->dir_output . "/" . dol_sanitizeFileName($this->object->ref);
		}
		return null;
	}

	/**
	 * Function to get selectable files for an object
	 * @return ExtendedEcm[]
	 */
	public function getEcmFiles()
	{
		$result = array();
		if ($this->object->element == 'propal') {
			$result = $this->getEcmListForDirectory($this->getPropalDirectory($this->object));
		} elseif ($this->object->element == 'sepamandatmanager_sepamandat') {
			$result = $this->getEcmListForDirectory($this->object->getAbsolutePath());
		}
		return $result;
	}

	/**
	 * Function to check that all needed information asked on displayCreateFromSelectedFiles have been asked
	 * @param ExtendedEcm[] $selectedFiles Array of files to sign
	 * @param DigitalSignaturePeople[][] $signatoryInstancePerEcmFileAndDictionaryRowId array('ecmFileId'=>array('dictionnaryRowId'=>DigitalSignaturePeopleInstance))
	 * @return string[] array of validation errors
	 */
	public function checkContentFromSelectedFiles($selectedFiles, $signatoryInstancePerEcmFileAndDictionaryRowId)
	{
		global $langs;
		$errors = array();
		foreach ($selectedFiles as $id => $ecmFile) {
			$dictionaryItems = $this->getSignatoryFieldsDictionaryLinesForFile($ecmFile);
			foreach ($dictionaryItems as $dictionaryItem) {
				$selectedSignatory = $signatoryInstancePerEcmFileAndDictionaryRowId[$id][$dictionaryItem->c_rowid];
				$isFreeSignatoryAllowed = in_array(DigitalSignaturePeople::LINKED_OBJECT_FREE_TYPE, $dictionaryItem->linkedContactType);
				$isContactOrUserSignatorySourceAllowed = in_array(DigitalSignaturePeople::LINKED_OBJECT_USER_TYPE, $dictionaryItem->linkedContactType) || in_array(DigitalSignaturePeople::LINKED_OBJECT_CONTACT_TYPE, $dictionaryItem->linkedContactType);
				if (!$selectedSignatory && !$isFreeSignatoryAllowed) {
					$errors[] = $langs->trans('DigitalSignatureManagerNoSignatorySelected', $dictionaryItem->label, $ecmFile->filename);
				} elseif ($isFreeSignatoryAllowed && !$isContactOrUserSignatorySourceAllowed && !empty($selectedSignatory->checkDataValidForCreateRequestOnProvider())) {
					$errors[] = $langs->trans('DigitalSignatureManagerFreeSignatoryIncomplete', $dictionaryItem->label, $ecmFile->filename);
				}
			}
		}
		return $errors;
	}

	/**
	 * Function to get selected files instance
	 * @param int[] $ecmFileIds Ecm id of files to fetch
	 * @return ExtendedEcm[]
	 */
	public function getFilesByIds($ecmFileIds)
	{
		$selectableFiles = $this->getEcmFiles();
		$filesEffectivelyChosen = array();
		foreach ($ecmFileIds as $id) {
			if ($selectableFiles[$id]) {
				$filesEffectivelyChosen[$id] = $selectableFiles[$id];
			}
		}
		return $filesEffectivelyChosen;
	}

	/**
	 * Function to get signatory field from dictionary according to document file origin
	 * @param ExtendedEcm $ecmFile ecm file on which search predefined signatory fields
	 * @return DigitalSignatureSignatoryFieldsDictionary[]
	 */
	public function getSignatoryFieldsDictionaryLinesForFile($ecmFile)
	{
		$digitalSignatureDocument = new DigitalSignatureDocument($this->db);
		$digitalSignatureDocument->fk_ecm = $ecmFile->id;
		$digitalSignatureDocument->ecmFile = $ecmFile;
		return $digitalSignatureDocument->getDictionarySignatoryFieldsOfThisDocument();
	}


	/**
	 * Static function to get linked object of a digital signature request
	 *
	 * @param 	DigitalSignatureRequest		$object Instance on which we are looking to fetch linked instance
	 * @param	Bool	$forceUpdateOfCache should we force update of linked objects cache
	 * @return	CommonObject[]|null
	 */
	public static function getLinkedObjects($object, $forceUpdateOfCache = false)
	{
		if(!$object->linkedObjects || $forceUpdateOfCache) {
			$object->fetchObjectLinked();
		}
		$result = array();
		foreach($object->linkedObjects as $linkIdsAndLinkedObjects) {
			$result = array_merge($result, array_values($linkIdsAndLinkedObjects));
		}
		return $result;
	}
}
