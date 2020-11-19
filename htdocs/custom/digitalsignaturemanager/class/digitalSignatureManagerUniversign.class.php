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
	public $universignSignersDigitalSignaturePeopleLink = array(
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
	public $universignSignersDigitalSignatureRequestLink = array(
		\Globalis\Universign\Response\TransactionInfo::STATUS_COMPLETED => DigitalSignatureRequest::STATUS_SUCCESS,
		\Globalis\Universign\Response\TransactionInfo::STATUS_EXPIRED => DigitalSignatureRequest::STATUS_EXPIRED,
		\Globalis\Universign\Response\TransactionInfo::STATUS_FAILED => DigitalSignatureRequest::STATUS_FAILED,
		\Globalis\Universign\Response\TransactionInfo::STATUS_READY => DigitalSignatureRequest::STATUS_IN_PROGRESS,
		\Globalis\Universign\Response\TransactionInfo::STATUS_CANCELED => DigitalSignatureRequest::STATUS_CANCELED_BY_OPSY,
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
			foreach($document->checkBoxes as $checkBox) {
				$checkBoxTexts[] = $checkBox->label;
			}
			if(!empty($conf->global->DIGITALSIGNATUREMANAGER_CHECKBOX_ADDNUMBEROFPAGE)) {
				$checkBoxTexts[] = $langs->trans('DigitalSignatureManagerPageNumberCheckBox', $document->getNumberOfPage());
			}
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
            ->setMustContactFirstSigner(false)
            ->setFinalDocRequesterSent(true)
            ->setChainingMode(
        \Globalis\Universign\Request\TransactionRequest::CHAINING_MODE_EMAIL
		)
            ->setDescription($this->digitalSignatureRequest->getUniversignPublicLabel())
			->setLanguage($this->getUniversignLanguageCode())
			->setCertificateType('simple')
			->setCustomId($this->digitalSignatureRequest->id);

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
		$db = $this->digitalSignatureRequest->db;
		$db->begin();
		$requester = $this->getUniversignRequester();
		$universignRequestId = $this->digitalSignatureRequest->externalId;
		try{
			$transactionInfo = $requester->getTransactionInfo($universignRequestId);
			$signerInfos = $transactionInfo->signerInfos;
			foreach($signerInfos as $index => $signer) {
				$currentSigner = $this->digitalSignatureRequest->getSignerByIndex($index);
				if($currentSigner->externalUrl != $signer->url) {
					$currentSigner->externalUrl = $signer->url;
					$res = $currentSigner->update($user);
					if($res < 0) {
						$this->digitalSignatureRequest->errors = array_merge($this->digitalSignatureRequest->errors, $currentSigner->errors);
						$db->rollback();
						return false;
					}
				}
				//We merge people status
				$statusToSetFromUniversign = $this->universignSignersDigitalSignaturePeopleLink[$signer->status];
				if($statusToSetFromUniversign && $statusToSetFromUniversign != $currentSigner->status)
				{
					//we update status
					$res = $currentSigner->setStatus($user, $statusToSetFromUniversign);
					if($res < 0) {
						$this->digitalSignatureRequest->errors = array_merge($this->digitalSignatureRequest->errors, $currentSigner->errors);
						$db->rollback();
						return false;
					}
				}
			}
			//we update request statut
			$oldStatus = $this->digitalSignatureRequest->status;
			$newStatus = $this->universignSignersDigitalSignatureRequestLink[$transactionInfo->status];
			//We manage cancel status as it may have been canceled by opsy and not only signers
			if($transactionInfo->status == \Globalis\Universign\Response\TransactionInfo::STATUS_CANCELED && $this->digitalSignatureRequest->status != $this->digitalSignatureRequest::STATUS_CANCELED_BY_OPSY) {
				//request has been indeed been canceled by a signers
				$newStatus = $this->digitalSignatureRequest::STATUS_CANCELED_BY_SIGNERS;
			}
			if($newStatus) {
				if($newStatus != $oldStatus && $this->digitalSignatureRequest->setStatus($user, $newStatus) < 0 )
				{
					$db->rollback();
					return false;
				}
			}
			else {
				global $langs;
				$this->digitalSignatureRequest->errors[] = $langs->trans('DigitalSignatureManagerUnknownStatusFromProvider');
				$db->rollback();
				return false;
			}

			if($transactionInfo->status == \Globalis\Universign\Response\TransactionInfo::STATUS_CANCELED && $this->digitalSignatureRequest->status == $this->digitalSignatureRequest::STATUS_CANCELED_BY_OPSY) {
				foreach($this->digitalSignatureRequest->people as $people) {
					if($people->hasThisPeopleBeenOfferedSomething() || $people->status == $people::STATUS_SHOULD_SIGN) {
						$people->setStatus($user, $people::STATUS_PROCESS_STOPPED_BEFORE);
					}
				}
			}

			//We have successfully update data
			$db->commit();

			if($oldStatus != $newStatus && $newStatus == $this->digitalSignatureRequest::STATUS_SUCCESS) {
				//request process has just been finished
				//we download files
				return $this->downloadSignedDocuments($this->digitalSignatureRequest);
			}
			return true;
		}
		catch (Exception $e) {
			$this->digitalSignatureRequest->errors = array_merge($this->digitalSignatureRequest->errors, $e);
			$db->rollback();
			return false;
		}
	}

	/**
	 * Download signed documents
	 * @return bool true if files have succesfully been downloaded
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
				$res = file_put_contents($this->digitalSignatureRequest->getUploadDirOfSignedFiles() . '/' . $doc->name, $doc->content);
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
		try{
			$requester = $this->getUniversignRequester();
			//We update data
			if($this->getAndUpdateData($user) > 0) {
				if(!$this->digitalSignatureRequest->statut == $this->digitalSignatureRequest::STATUS_IN_PROGRESS) {
					$requester->cancelTransaction($this->digitalSignatureRequest->externalId);
					return $this->getAndUpdateData($user) > 0;
				}
				else {
					//request is not anymore cancelable
					global $langs;
					$this->digitalSignatureRequest->errors[] = $langs->trans('DigitalSignatureManagerRequestNotAnymoreCancelable');
				}
			}
		}
		catch(Exception $e) {
			$this->digitalSignatureRequest->errors[] = $e->getMessage();
			return false;
		}
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
}
