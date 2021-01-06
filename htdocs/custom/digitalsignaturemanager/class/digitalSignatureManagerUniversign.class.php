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
	 * @param DigitalSignatureRequest $db Database handler
	 */
	public function __construct(&$digitalSignatureRequest) {
		$this->digitalSignatureRequest = $digitalSignatureRequest;
	}

	/**
	 * Create a signature request on universign
	 * @return null|array   return the signature ID and linked or null if some error appends
	 */
	public function create()
	{
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
				->setPhoneNum($people->phoneNumber)
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
			$universignDocument->setPath($document->getLinkedFileAbsolutePath())
				->setSignatureFields($documentSignatureFieldsByLinkedDocumentId[$document->id]);
			$universignDocuments[] = $universignDocument;
		}

		$request = new \Globalis\Universign\Request\TransactionRequest();
		foreach($universignDocuments as $universignDocument) {
			$request->addDocument($universignDocument);
		}
		$request->setSigners($universignSigners)
            ->setHandwrittenSignatureMode(
        \Globalis\Universign\Request\TransactionRequest::HANDWRITTEN_SIGNATURE_MODE_DIGITAL
		)
            ->setMustContactFirstSigner(false)
            ->setFinalDocRequesterSent(true)
            ->setChainingMode(
        \Globalis\Universign\Request\TransactionRequest::CHAINING_MODE_WEB
		)
            ->setDescription("Demonstration de la signature Universign")
            ->setCertificateTypes('simple')
			->setLanguage('fr')
			->setCustomId($this->digitalSignatureRequest->id);

		$requester = $this->getUniversignRequester();
		try{
			$response = $requester->requestTransaction($request);
			return array('id'=>$response->id, 'url'=>$response->url);
		}
		catch(Exception $e) {
			$this->digitalSignatureRequest->errors[] = $e;
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
			foreach($signerInfos as $index=>$signer) {
				$currentSigner = $this->digitalSignatureRequest->people[$index];
				if($currentSigner->url != $signer->url) {
					$currentSigner->url = $signer->url;
					$res = $currentSigner->update($user);
					if($res < 0) {
						$this->digitalSignatureRequest->errors = array_merge($this->digitalSignatureRequest->errors, $currentSigner->errors);
						$this->db->rollback();
						return false;
					}
				}
				//We merge people status
				$statusToSetFromUniversign = $this->universignSignersDigitalSignaturePeopleLink[$signer->status];
				if($statusToSetFromUniversign && $statusToSetFromUniversign != $currentSigner->statut)
				{
					//we update status
					$res = $currentSigner->setStatus($user, $statusToSetFromUniversign);
					if($res < 0) {
						$this->digitalSignatureRequest->errors = array_merge($this->digitalSignatureRequest->errors, $currentSigner->errors);
						$this->db->rollback();
						return false;
					}
				}
			}
			//we update request statut
			$oldStatus = $this->digitalSignatureRequest->statut;
			$newStatus = $this->universignSignersDigitalSignatureRequestLink[$transactionInfo->status];
			//We manage cancel status as it may have been canceled by opsy and not only signers
			if($transactionInfo->status == \Globalis\Universign\Response\TransactionInfo::STATUS_CANCELED && $digitalSignatureRequest->statut != $digitalSignatureRequest::STATUS_CANCELED_BY_OPSY) {
				//request has been indeed been canceled by a signers
				$newStatus = $this->digitalSignatureRequest::STATUS_CANCELED_BY_SIGNERS;
			}
			if($newStatus) {
				$result = $this->digitalSignatureRequest->setStatus($user, $newStatus);
				if($result < 0) {
					$this->db->rollback();
					return false;
				}
			}
			else {
				global $langs;
				$this->digitalSignatureRequest->errors[] = $langs->trans('DigitalSignatureManagerUnknownStatusFromProvider');
				$this->db->rollback();
				return false;
			}

			//We have successfully update data
			$this->db->commit();

			if($oldStatus != $newStatus && $newStatus == $this->digitalSignatureRequest::STATUS_SUCCESS) {
				//request process has just been finished
				//we download files
				return $this->downloadSignedDocuments($digitalSignatureRequest);
			}
			return true;
		}
		catch (Exception $e) {
			$this->digitalSignatureRequest->errors = array_merge($this->digitalSignatureRequest->errors, $e);
			$this->db->rollback();
			return false;
		}
	}

	/**
	 * Download signed documents
	 * @param DigitalSignatureRequest $digitalSignatureRequest current request data
	 * @return bool true if files have succesfully been downloaded
	 */
	public function downloadSignedDocuments(&$digitalSignatureRequest)
	{
		global $langs;
		$errors = array();
		$requester = $this->getUniversignRequester();
		$transactionId = $digitalSignatureRequest->externalId;
		$response = $requester->getTransactionInfo($transactionId);
		if ($response->status === \Globalis\Universign\Response\TransactionInfo::STATUS_COMPLETED) {
			$docs = $requester->getDocuments($transactionId);
			foreach ($docs as $doc) {
				$res = file_put_contents($digitalSignatureRequest->getUploadDirOfSignedFiles() . '/' . $doc->name, $doc->content);
				if(!$res) {
					$errors[] = $langs->trans('DigitalSignatureManagerUniversignErrorSavingFileInServer', $doc->name);
				}
			}
		}
		$digitalSignatureRequest->errors = array_merge($digitalSignatureRequest->errors, $errors);
		return empty($errors);
	}

	/**
	 * Get information about a signature request on universign
	 * @param string $universignRequestId universign Request id to be canceled
	 * @return bool return success of cancelation of request
	 */
	public function cancel($universignRequestId)
	{
		$requester = $this->getUniversignRequester();
		$response = $requester->cancelTransaction($universignRequestId);
		return $response->status === \Globalis\Universign\Response\TransactionInfo::STATUS_CANCELED;
	}

	/**
	 * Get Universign Globalis Requester object
	 * @return \Globalis\Universign\Requester
	 */
	private function getUniversignRequester()
	{
		// Create XmlRpc Client
		$client = new \PhpXmlRpc\Client('https://url.to.universign/end_point/');

		$client->setCredentials(
		'UNIVERSIGN_USER',
		'UNIVERSIGN_PASSWORD'
		);

		return new \Globalis\Universign\Requester($client);
	}
}
