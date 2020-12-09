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

dol_include_once('/digitalsignaturemanager/class/extendedEcm.class.php');
dol_include_once('/digitalsigaturemanager/class/digitalsignaturerequest.class.php');

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
	public function getLinkedDigitalSignatureRequest($forceUpdateOfCache = false)
	{
		if ($forceUpdateOfCache || !self::$cacheDigitalSignatureRequest || !self::$cacheDigitalSignatureRequest[$this->object->table_element][$this->object->id]) {
			$digitalSignatureManager = new DigitalSignatureRequest($this->db);
			self::$cacheDigitalSignatureRequest[$this->object->table_element][$this->object->id] = &$digitalSignatureManager->fetchAll('DESC', 'date_creation', 0, 0, array('elementtype' => $this->object->table_element, 'fk_object' => $this->object->id));
		}
		return self::$cacheDigitalSignatureRequest[$this->object->table_element][$this->object->id];
	}

	/**
	 * Get linked digital signature request with researchedStatus
	 * @param	string $researchedStatus Researched digital signature request
	 * @return DigitalSignatureRequest[]
	 */
	public function getDigitalSignatureRequestsWithStatus($researchedStatus)
	{
		$result = array();
		foreach ($this->getLinkedDigitalSignatureRequest() as &$digitalSignatureRequest) {
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
	 * @param CommonObject $object object on which user wishes to create a signature request
	 * @return bool
	 */
	public function isUserAbleToCreateRequest($user)
	{
		return true;
	}

	/**
	 * Function to know if an user is allowed to cancel a signature request
	 * @param User $user user requesting to do an action
	 * @param CommonObject $object object on which user wishes to cancel a signature request
	 * @return bool
	 */
	public function isUserAbleToCancelRequest($user)
	{
		return true;
	}

	/**
	 * Function to know if an user is allowed to refresh a signature request
	 * @param User $user user requesting to do an action
	 * @param CommonObject $object object on which user wishes to cancel a signature request
	 * @return bool
	 */
	public function isUserAbleToRefreshRequest($user)
	{
		return true;
	}

	/**
	 * Function to create digital signature request instance with document and signatory fields according to post content
	 * @param User $user user requesting creation of request
	 * @param ExtendedEcm[] $selectedFiles Array of files to sign
	 * @param DigitalSignaturePeople[][] $signatoryInstancePerEcmFileAndDictionaryRowId array('ecmFileId'=>array('dictionnaryRowId'=>DigitalSignaturePeopleInstance))
	 * @return DigitalSignatureRequest|null
	 */
	public function createDigitalSignatureRequestFromLinkedObject($user, $selectedFiles, $signatoryInstancePerEcmFileAndDictionaryRowId)
	{
		$this->db->begin();
		global $langs;
		$errors = array();
		$digitalSignatureRequest = new DigitalSignatureRequest($this->db);
		$digitalSignatureRequest->elementtype = $this->object->table_element;
		$digitalSignatureRequest->fk_object = $this->object->id;
		$digitalSignatureRequest->fk_soc = $this->object->socid;
		$digitalSignatureRequest->fk_project = $this->object->fk_project;

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
				} else {
					$arrayOfOriginalAndCopiedEcmFile[$copyEcmFile->id] = $ecmFile;
					$digitalSignatureDocument->ecmFile = $copyEcmFile;
					$digitalSignatureDocument->fk_ecm = $copyEcmFile->id;
					$digitalSignatureDocument->fk_digitalsignaturerequest = $digitalSignatureRequest->id;
					$digitalSignatureDocument->digitalSignatureRequest = $digitalSignatureRequest;
					$digitalSignatureDocument->create($user);
					$errors = array_merge($errors, $digitalSignatureDocument->errors);
					$digitalSignatureRequest->documents[] = $digitalSignatureDocument;
				}
			}
		}


		//We build digitalsignaturepeople array for this request
		$listOfSignatoryByIdentifier = array();
		$listOfSignatoryIdentifierByEcmFileAndSignatoryFieldDictionnaryId = array();
		foreach ($selectedFiles as $ecmFile) {
			foreach ($this->getSignatoryFieldsDictionaryLinesForFile($ecmFile) as $dictionaryItem) {
				$signatorySelected = $signatoryInstancePerEcmFileAndDictionaryRowId[$ecmFile->id][$dictionaryItem->c_rowid];
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
		foreach ($selectedFiles as $ecmFile) {
			$dictionaryItems = $this->getSignatoryFieldsDictionaryLinesForFile($ecmFile);
			foreach ($dictionaryItems as $dictionaryItem) {
				$selectedSignatory = $signatoryInstancePerEcmFileAndDictionaryRowId[$ecmFile->id][$dictionaryItem->c_rowid];
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
				$filesEffectivelyChosen[] = $selectableFiles[$id];
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
	 * @return	CommonObject|null
	 */
	public static function getLinkedObject($object)
	{
		global $db, $conf;

		if (!$object || !$object->fk_object || !$object->elementtype) {
			return null;
		}
		$objecttype = $object->elementtype;
		$objectid = $object->fk_object;

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
