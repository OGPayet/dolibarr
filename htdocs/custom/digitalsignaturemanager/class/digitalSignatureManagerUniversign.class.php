<?php
 /* Copyright (C) 2020 Alexis LAURIER - <contact@alexislaurier.fr>
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

dol_include_once('/digitalsignaturemanager/vendor/autoload.php');
dol_include_once('/digitalsignaturemanager/class/digitalsignaturerequest.class.php');
dol_include_once('/digitalsignaturemanager/class/digitalsignaturepeople.class.php');

//require_once '../vendor/autoload.php';

/**
 * \file        class/universign.class.php
 * \ingroup     digitalsignaturemanager
 */

Class DigitalSignatureManagerUniversign
{
	/**
	 * @var DoliDB Database connector instance
	*/
	public $db;

	/**
	 * @var string username to connect to the api
	 */
	public $username;

	/**
	 * @var string password to connect to the api
	 */
	public $password;

	/**
	 * @var string end point to connect to the api
	 */
	public $endPoint;

	/**
	 * @var string[] Array of errors
	 */
	public $errors = array();

	/**
	 * @var string[] Mapping between dolibarr lang code and universign lang code
	 */
	public $langCodeMapping = array(
		'bg_BG' => 'bg',
		'ca_ES' => 'ca',
		'de_DE' => 'de',
		'es_ES' => 'es',
		'fr_FR' => 'fr',
		'it_IT' => 'it',
		'nl_NL' => 'nl',
		'pl_PL' => 'pl',
		'pt_PT' => 'pt',
		'ro_RO' => 'ro'
	);

	/**
	 * @var int[] Link between universign signer status and digitalsignaturepeople status
	 */
	const UNIVERSIGN_STATUS_SIGNERS_DICTIONNARY = array(
		\Globalis\Universign\Response\SignerInfo::STATUS_WAITING => DigitalSignaturePeople::STATUS_WAITING_TO_SIGN,
		\Globalis\Universign\Response\SignerInfo::STATUS_READY => DigitalSignaturePeople::STATUS_SHOULD_SIGN,
		\Globalis\Universign\Response\SignerInfo::STATUS_ACCESSED => DigitalSignaturePeople::STATUS_ACCESSED,
		\Globalis\Universign\Response\SignerInfo::STATUS_CODE => DigitalSignaturePeople::STATUS_CODE,
		\Globalis\Universign\Response\SignerInfo::STATUS_SIGNED => DigitalSignaturePeople::STATUS_SUCCESS,
		\Globalis\Universign\Response\SignerInfo::STATUS_PENDING_ID_DOCS => DigitalSignaturePeople::STATUS_PENDING_ID_DOCS,
		\Globalis\Universign\Response\SignerInfo::STATUS_PENDING_VALIDATION => DigitalSignaturePeople::STATUS_PENDING_VALIDATION,
		\Globalis\Universign\Response\SignerInfo::STATUS_CANCELED => DigitalSignaturePeople::STATUS_REFUSED,
		\Globalis\Universign\Response\SignerInfo::STATUS_FAILED => DigitalSignaturePeople::STATUS_FAILED,
		);

	/**
	 * @var int[] Link between universign request status and digitalsignaturerequest status
	 */
	const UNIVERSIGN_STATUS_REQUEST_DICTIONNARY = array(
		\Globalis\Universign\Response\TransactionInfo::STATUS_COMPLETED => DigitalSignatureRequest::STATUS_SUCCESS,
		\Globalis\Universign\Response\TransactionInfo::STATUS_EXPIRED => DigitalSignatureRequest::STATUS_EXPIRED,
		\Globalis\Universign\Response\TransactionInfo::STATUS_FAILED => DigitalSignatureRequest::STATUS_FAILED,
		\Globalis\Universign\Response\TransactionInfo::STATUS_READY => DigitalSignatureRequest::STATUS_IN_PROGRESS,
		\Globalis\Universign\Response\TransactionInfo::STATUS_CANCELED => DigitalSignatureRequest::STATUS_CANCELED_BY_SIGNERS,
	);

	/**
	 * @var DigitalSignatureRequest	digital signature request linked to this service
	 */
	public $digitalSignatureRequest;

	/**
	 * Constructor
	 *
	 * @param DigitalSignatureRequest $digitalSignatureRequest Linked digital signature request
	 */
	public function __construct(&$digitalSignatureRequest)
	{
		$this->digitalSignatureRequest = $digitalSignatureRequest;
		$this->db = $this->digitalSignatureRequest->db;
		$this->loadConnectionSettings();
	}

	/**
	 * Create a signature request on universign
	 * @return null|array   return the signature ID and linked or null if some error appends
	 */
	public function create()
	{
		global $conf, $langs;
		//We prepare some data linked to people who will sign
		$signersIndexAndId = array();
		$signersIdAndDisplayName = array();
		$index = 0;
		foreach($this->digitalSignatureRequest->people as $people) {
			$signersIndexAndId[$people->id] = $index;
			$signersIdAndDisplayName[$people->id] = $people->displayName();
			$index += 1;
		}

		//We declare signers
		$universignSigners = array();

		foreach($this->digitalSignatureRequest->people as $people) {
			$signer = new \Globalis\Universign\Request\TransactionSigner();
			$signer->setFirstname($people->firstName)
				->setLastname($people->lastName)
				->setPhoneNum($people->getInternationalPhoneNumber())
				->setEmailAddress($people->mail);
			$universignSigners[] = $signer;
		}

		//We declare document signature field
		$documentSignatureFieldsByLinkedDocumentId = array();
		foreach($this->digitalSignatureRequest->signatoryFields as $signatoryField) {
			$universignSignatureField = new \Globalis\Universign\Request\DocSignatureField();
			$universignSignatureField->setPage($signatoryField->page)
			->setX($signatoryField->x)
			->setY($signatoryField->y)
			->setSignerIndex($signersIndexAndId[$signatoryField->fk_chosen_digitalsignaturepeople])
			->setPatternName('default')
				->setLabel($signersIdAndDisplayName[$signatoryField->fk_chosen_digitalsignaturepeople]);
			if(!$documentSignatureFieldsByLinkedDocumentId[$signatoryField->fk_chosen_digitalsignaturedocument]) {
				$documentSignatureFieldsByLinkedDocumentId[$signatoryField->fk_chosen_digitalsignaturedocument] = array();
			}
			$documentSignatureFieldsByLinkedDocumentId[$signatoryField->fk_chosen_digitalsignaturedocument][] = $universignSignatureField;
		}

		//We declare documents
		$universignDocuments = array();
		foreach($this->digitalSignatureRequest->documents as $document) {
			$universignDocument = new \Globalis\Universign\Request\TransactionDocument();
			$universignDocument->setPath($document->getLinkedFileAbsolutePath());
			if(!empty($documentSignatureFieldsByLinkedDocumentId[$document->id])) {
				$universignDocument->setSignatureFields($documentSignatureFieldsByLinkedDocumentId[$document->id]);
			}
			$checkBoxTexts = array();
			foreach($document->getCheckBoxes() as $checkBox) {
				$checkBoxTexts[] = $checkBox->label;
			}
			// if(!empty($conf->global->DIGITALSIGNATUREMANAGER_CHECKBOX_ADDNUMBEROFPAGE)) {
			// 	$checkBoxTexts[] = $langs->trans('DigitalSignatureManagerPageNumberCheckBox', $document->getNumberOfPage());
			// }
			if(!empty($checkBoxTexts)) {
				$universignDocument->setCheckBoxTexts($checkBoxTexts);
			}
			$universignDocuments[] = $universignDocument;
		}

		$request = new \Globalis\Universign\Request\TransactionRequest();
		foreach($universignDocuments as $universignDocument) {
			$request->addDocument($universignDocument);
		}
		$request->setSigners($universignSigners)
            ->setHandwrittenSignatureMode(
        \Globalis\Universign\Request\TransactionRequest::HANDWRITTEN_SIGNATURE_MODE_BASIC
		)
            ->setMustContactFirstSigner(true)
            ->setFinalDocRequesterSent(true)
            ->setChainingMode(
        \Globalis\Universign\Request\TransactionRequest::CHAINING_MODE_EMAIL
		)
            ->setDescription($this->digitalSignatureRequest->getUniversignPublicLabel())
			->setLanguage($this->getUniversignLanguageCode())
			->setCertificateType('simple')
			->setCustomId((string) $this->digitalSignatureRequest->id);

		$requester = $this->getUniversignRequester();
		try{
			$response = $requester->requestTransaction($request);
			return array('id'=>$response->id, 'url'=>$response->url);
		}
		catch(Exception $e) {
			$this->digitalSignatureRequest->errors[] = $e->getMessage();
			return null;
		}
	}

	/**
	 * Get information about a signature request on universign
	 * @param User $user user requesting update of data
	 * @return bool turn true if successfully updated
	 */
	public function getAndUpdateData($user)
	{
		$this->db->begin();
		$requester = $this->getUniversignRequester();
		$universignRequestId = $this->digitalSignatureRequest->externalId;
		try{
			$transactionInfo = $requester->getTransactionInfo($universignRequestId);
			$signerInfos = $transactionInfo->signerInfos;
		}
		catch (Exception $e) {
			$this->digitalSignatureRequest->errors[] = $e->getMessage();
			$this->db->rollback();
			return false;
		}

		//we update request information
		$resultOfRequestInformationUpdate = self::updateRequestInformation($this->db, $this->digitalSignatureRequest, $transactionInfo, $user);
		//We save request status
		$oldRequestStatus = $this->digitalSignatureRequest->status;
		//we update request status
		$resultOfRequestStatusUpdate = true;
		if($this->digitalSignatureRequest->status != $this->digitalSignatureRequest::STATUS_CANCELED_BY_OPSY || $transactionInfo->status != \Globalis\Universign\Response\TransactionInfo::STATUS_CANCELED) {
			//request is not canceled on opsy or not canceled on universign
			$resultOfRequestStatusUpdate = self::updateRequestStatus($this->db, $this->digitalSignatureRequest, $transactionInfo, $user);
		}
		//We update signers information
		$resultOfSignerInformationUpdates = array();
		foreach($signerInfos as $index => $signerInfo) {
			$people = $this->digitalSignatureRequest->getSignerByIndex($index);
			$resultOfSignerInformationUpdates[$people->id] = self::updateSignerInformation($this->db, $this->digitalSignatureRequest, $people, $signerInfo, $user);
		}
		//We update signer status
		if($this->digitalSignatureRequest->status == DigitalSignatureRequest::STATUS_CANCELED_BY_OPSY) {
			$resultOfSignerStatusUpdate = self::updateDigitalSignaturePeopleStatusWhenRequestCanceledFromOpsy($this->db, $this->digitalSignatureRequest, $signerInfos, $user);
		}
		elseif($this->digitalSignatureRequest->status == DigitalSignatureRequest::STATUS_CANCELED_BY_SIGNERS || $this->digitalSignatureRequest->status == DigitalSignatureRequest::STATUS_FAILED) {
			$resultOfSignerStatusUpdate = self::updateDigitalSignaturePeopleStatusWhenRequestCanceledBySignersOrFailed($this->db, $this->digitalSignatureRequest, $signerInfos, $user);
		}
		else {
			$resultOfSignerStatusUpdate = self::updateDigitalSignaturePeopleStatusWhenRequestNotCanceledOrFailed($this->db, $this->digitalSignatureRequest, $signerInfos, $user);
		}

		$areAllOperationBeenASuccess = self::areAllValuesOfThisReturnArrayOnlySuccess(array(
			$resultOfRequestInformationUpdate,
			$resultOfRequestStatusUpdate,
			self::areAllValuesOfThisReturnArrayOnlySuccess($resultOfSignerInformationUpdates),
			$resultOfSignerStatusUpdate
		));

		if($areAllOperationBeenASuccess && $oldRequestStatus != $this->digitalSignatureRequest->status && $this->digitalSignatureRequest->status == $this->digitalSignatureRequest::STATUS_SUCCESS) {
			$areAllOperationBeenASuccess = $this->downloadSignedDocuments($this->digitalSignatureRequest);
		}

		if($areAllOperationBeenASuccess) {
			$this->db->commit();
			return true;
		}
		else {
			$this->db->rollback();
			return false;
		}
	}

	/**
	 * Download signed documents
	 * @return bool true if files have successfully been downloaded
	 */
	public function downloadSignedDocuments()
	{
		global $langs;
		$errorOfThisProcess = array();
		$requester = $this->getUniversignRequester();
		$transactionId = $this->digitalSignatureRequest->externalId;
		$response = $requester->getTransactionInfo($transactionId);
		if ($response->status === \Globalis\Universign\Response\TransactionInfo::STATUS_COMPLETED) {
			$docs = $requester->getDocuments($transactionId);
			foreach ($docs as $doc) {
				$res = file_put_contents($this->digitalSignatureRequest->getAbsoluteDirectoryOfSignedFiles() . '/' . $doc->name, $doc->content);
				if(!$res) {
					$errorOfThisProcess[] = $langs->trans('DigitalSignatureManagerUniversignErrorSavingFileInServer', $doc->name);
				}
			}
		}
		$this->digitalSignatureRequest->errors = array_merge($this->digitalSignatureRequest->errors, $errorOfThisProcess);
		return empty($errorOfThisProcess);
	}

	/**
	 * Get information about a signature request on universign
	 * @return bool return success of cancelation of request
	 */
	public function cancel($user)
	{
		global $langs;
		$errorsOfThisProcess = array();
		//to begin we update data from universign
		if($this->getAndUpdateData($user)) {
			//We cancel request if it still be possible
			if($this->digitalSignatureRequest->status != $this->digitalSignatureRequest::STATUS_IN_PROGRESS)
			{
				$errorsOfThisProcess = $langs->trans('DigitalSignatureManagerRequestNotAnymoreCancelable');
			}
			else {
				try {
					$requester = $this->getUniversignRequester();
					$requester->cancelTransaction($this->digitalSignatureRequest->externalId);
					if($this->digitalSignatureRequest->setStatus($user, $this->digitalSignatureRequest::STATUS_CANCELED_BY_OPSY) > 0 && !$this->getAndUpdateData($user)) {
						$errorsOfThisProcess = $langs->trans('DigitalSignatureManagerErrorWhileRefreshingData');
					}
				}
				catch(Exception $e) {
					$errorsOfThisProcess[] = $e->getMessage();
				}
			}
		}
		else {
			$errorsOfThisProcess = $langs->trans('DigitalSignatureManagerErrorWhileRefreshingData');
		}
		$this->digitalSignatureRequest->errors = array_merge($this->digitalSignatureRequest->errors, $errorsOfThisProcess);
		return empty($errorsOfThisProcess);
	}

	/**
	 * Get Universign Globalis Requester object
	 * @return \Globalis\Universign\Requester
	 */
	private function getUniversignRequester()
	{
		// Create XmlRpc Client
		$client = new \PhpXmlRpc\Client($this->endPoint);

		$client->setCredentials(
			$this->username,
			$this->password
		);

		return new \Globalis\Universign\Requester($client);
	}

	/**
	 * Get Universign parameter to use
	 * @return void
	 */
	private function loadConnectionSettings()
	{
		global $conf;
		if(!empty($conf->global->DIGITALSIGNATUREMANAGER_TESTMODE)) {
			$this->endPoint = $conf->global->DIGITALSIGNATUREMANAGER_UNIVERSIGNTESTURL;
			$this->username = $conf->global->DIGITALSIGNATUREMANAGER_UNIVERSIGNTESTUSERNAME;
			$this->password = $conf->global->DIGITALSIGNATUREMANAGER_UNIVERSIGNTESTPASSWORD;
		}
		else {
			$this->endPoint = $conf->global->DIGITALSIGNATUREMANAGER_UNIVERSIGNPRODUCTIONURL;
			$this->username = $conf->global->DIGITALSIGNATUREMANAGER_UNIVERSIGNPRODUCTIONUSERNAME;
			$this->password = $conf->global->DIGITALSIGNATUREMANAGER_UNIVERSIGNPRODUCTIONPASSWORD;
		}
	}

	/**
	 * Get universign langage code
	 * @return string Language code to be used on request
	 */
	public function getUniversignLanguageCode()
	{
		global $langs;
		$dolibarrLanguageCode = $langs->defaultlang;
		$universignLanguageCode = $this->langCodeMapping[$dolibarrLanguageCode];
		return empty($universignLanguageCode) ? 'fr' : $universignLanguageCode;
	}

	/**
	 * Update digital signature people status when request is running or have succeed
	 * @param DoliDb $db Database instance
	 * @param DigitalSignatureRequest $digitalSignatureRequest local instance to be updated
	 * @param Globalis\Universign\Response\SignerInfo[] $signerInfos signer information from universign
	 * @param User $user user performing the update
	 * @return bool true if successfully done
	 */
	public static function updateDigitalSignaturePeopleStatusWhenRequestNotCanceledOrFailed(&$db, &$digitalSignatureRequest, &$signerInfos, &$user)
	{
		$db->begin();
		$errors = array();
		foreach($signerInfos as $index => $signerInfo) {
			$people = $digitalSignatureRequest->getSignerByIndex($index);
			$newStatus = self::UNIVERSIGN_STATUS_SIGNERS_DICTIONNARY[$signerInfo->status];
			if($people->status != $newStatus && $people->setStatus($user, $newStatus) < 0) {
				$errors = array_merge($errors, $people->errors);
			}
		}
		$digitalSignatureRequest->errors = array_merge($digitalSignatureRequest->errors, $errors);
		if(empty($errors)) {
			$db->commit();
			return true;
		}
		else {
			$db->rollback();
			return false;
		}
	}


	/**
	 * Update digital signature people status when request has been canceled from Opsy
	 * @param DoliDb $db Database instance
	 * @param DigitalSignatureRequest $digitalSignatureRequest local instance to be updated
	 * @param Globalis\Universign\Response\SignerInfo[] $signerInfos signer information from universign
     * @param User $user user performing the update
	 * @return bool true if successfully done
	 */
	public static function updateDigitalSignaturePeopleStatusWhenRequestCanceledFromOpsy(&$db, &$digitalSignatureRequest, &$signerInfos, &$user)
	{
		$db->begin();
		$errors = array();
		foreach($signerInfos as $index => $signerInfo) {
			$people = $digitalSignatureRequest->getSignerByIndex($index);
			//We have only to set process stopped before on people who didn't succeed in signing and have a fail status for universign
			if($people->status != $people::STATUS_SUCCESS
			&& $signerInfo->status == Globalis\Universign\Response\SignerInfo::STATUS_CANCELED
			&& $people->setStatus($user, $people::STATUS_PROCESS_STOPPED_BEFORE) < 0)
			{
				$errors = array_merge($errors, $people->errors);
			}
		}
		$digitalSignatureRequest->errors = array_merge($digitalSignatureRequest->errors, $errors);
		if(empty($errors)) {
			$db->commit();
			return true;
		}
		else {
			$db->rollback();
			return false;
		}
	}

	/**
	 * Update digital signature people status when request has been canceled by a signers
	* @param DoliDb $db Database instance
	 * @param DigitalSignatureRequest $digitalSignatureRequest local instance to be updated
	 * @param Globalis\Universign\Response\SignerInfo[] $signerInfos signer information from universign
	 * @param User $user user performing the update
	 * @return bool true if successfully done
	 */
	public static function updateDigitalSignaturePeopleStatusWhenRequestCanceledBySignersOrFailed(&$db, &$digitalSignatureRequest, &$signerInfos, &$user)
	{
		$db->begin();
		$errors = array();
		//we update status of user that have signed before request has been canceled or failed
		//We update status of signer which refused to sign to refused to sign or failed
		//We update status of signer who can't sign to DigitalSignaturePeople::PROCESS_STOPPED_BEFORE
		$cancelerFound = false;
		foreach($signerInfos as $index => $signerInfo) {
			$people = $digitalSignatureRequest->getSignerByIndex($index);
			if($signerInfo->status == Globalis\Universign\Response\SignerInfo::STATUS_SIGNED) {
				//This people have sucessfully signed
				$newPeopleStatus = DigitalSignaturePeople::STATUS_SUCCESS;
			}
			elseif(!$cancelerFound && ($signerInfo->status == Globalis\Universign\Response\SignerInfo::STATUS_CANCELED || $signerInfo->status == Globalis\Universign\Response\SignerInfo::STATUS_FAILED)) {
				//it is the first people with canceled or failed status - it is the request cancelation cause
				$newPeopleStatus = self::UNIVERSIGN_STATUS_SIGNERS_DICTIONNARY[$signerInfo->status];
				$cancelerFound = true;
			}
			elseif ($signerInfo->status == Globalis\Universign\Response\SignerInfo::STATUS_CANCELED || $signerInfo->status == Globalis\Universign\Response\SignerInfo::STATUS_FAILED){
				//Universign set a cancel status to him as previous requester canceled request
				$newPeopleStatus = DigitalSignaturePeople::STATUS_PROCESS_STOPPED_BEFORE;
			}
			else {
				//We are in a non standard case. We manage status thanks to universign information
				$newPeopleStatus = self::UNIVERSIGN_STATUS_SIGNERS_DICTIONNARY[$signerInfo->status];
			}
			if($people->status != $newPeopleStatus && $people->setStatus($user, $newPeopleStatus)) {
				$errors = array_merge($people->errors, $errors);
			}
		}
		$digitalSignatureRequest->errors = array_merge($digitalSignatureRequest->errors, $errors);
		if(empty($errors)) {
			$db->commit();
			return true;
		}
		else {
			$db->rollback();
			return false;
		}
	}

	/**
	 * Update signer information
	 * @param DoliDb $db Database instance
	 * @param DigitalSignatureRequest $digitalSignatureRequest local instance to be updated
	 * @param DigitalSignaturePeople $peopleToBeUpdated local instance to be update
	 * @param Globalis\Universign\Response\SignerInfo $signerInfo data send by universign
	 * @param User $user user performing the update
	 * @return bool
	 */
	public static function updateSignerInformation(&$db, &$digitalSignatureRequest, &$peopleToBeUpdated, &$signerInfo, &$user)
	{
		$db->begin();
		$isThereAFieldToUpdate = false;
		if($peopleToBeUpdated->externalUrl != $signerInfo->url) {
			$peopleToBeUpdated->externalUrl = $signerInfo->url;
			$isThereAFieldToUpdate = true;
		}
		if(! $isThereAFieldToUpdate || $peopleToBeUpdated->update($user) > 0) {
			$db->commit();
			return true;
		}
		else {
			$digitalSignatureRequest->errors = array_merge($digitalSignatureRequest->errors, $peopleToBeUpdated->errors);
			$db->rollback();
			return false;
		}
	}

	/**
	 * Update request information
	 * @param DoliDb $db Database instance
	 * @param DigitalSignatureRequest $digitalSignatureRequest local instance to be updated
	 * @param Globalis\Universign\Response\TransactionInfo $requestInfo data send by universign
	 * @param User $user user performing the update
	 * @return bool
	 */
	public static function updateRequestInformation(&$db, &$digitalSignatureRequest, &$requestInfo, &$user)
	{
		$db->begin();
		$arrayOfFieldToUpdate = array('externalId'=>$requestInfo->transactionId, 'last_update_from_provider'=>dol_now());
		$isThereSomeThingToUpdate = false;
		foreach($arrayOfFieldToUpdate as $propertyName => $universignValue) {
			$propertyValue = $digitalSignatureRequest->$propertyName;
			$newValue = $universignValue;
			//if($digitalSignatureRequest->$propertyName != $requestInfo->$universignPropertyName)
			if($propertyValue != $newValue)
			{
				$digitalSignatureRequest->$propertyName = $universignValue;
				$isThereSomeThingToUpdate = true;
			}
		}
		if(! $isThereSomeThingToUpdate || $digitalSignatureRequest->update($user) > 0) {
			$db->commit();
			return true;
		}
		else {
			$digitalSignatureRequest->errors = array_merge($digitalSignatureRequest->errors, $digitalSignatureRequest->errors);
			$db->rollback();
			return false;
		}
	}

	/**
	 * Update request status
	 * @param DoliDb $db Database instance
	 * @param DigitalSignatureRequest $digitalSignatureRequest local instance to be updated
	 * @param Globalis\Universign\Response\TransactionInfo $requestInfo data send by universign
	 * @param User $user user performing the update
	 * @return bool
	 */
	public static function updateRequestStatus(&$db, &$digitalSignatureRequest, &$requestInfo, &$user)
	{
		$db->begin();
		$newStatus = self::UNIVERSIGN_STATUS_REQUEST_DICTIONNARY[$requestInfo->status];
		if(  $digitalSignatureRequest->status != $newStatus &&
		  $digitalSignatureRequest->setStatus($user, $newStatus) < 0) {
			$digitalSignatureRequest->errors = array_merge($digitalSignatureRequest->errors, $digitalSignatureRequest->errors);
			$db->rollback();
			return false;
		}
		else {
			$db->commit();
			return true;
		}
	}

	/**
	 * Function to check that all values of an array could be considered as true
	 * @param array $valuesToCheck array containing values to check
	 * @return bool
	 */
	private static function areAllValuesOfThisReturnArrayOnlySuccess($valuesToCheck)
	{
		foreach($valuesToCheck as $value) {
			if(!$value || $value < 0 ) {
				return false;
			}
			return true;
		}
	}
}
