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
	 * Create a signature request on universign
	 * @param DigitalSignatureRequest $digitalSignatureRequest	local request to send
	 * @return bool|string   return the signature ID or false if some error appends
	 */
	public function create($digitalSignatureRequest)
	{
		//We prepare some data linked to people who will sign
		$signersIndexAndId = array();
		$signersIdAndDisplayName = array();
		$index = 0;
		foreach($digitalSignatureRequest->people as $people) {
			$signersIndexAndId[$people->id] = $index;
			$signersIdAndDisplayName[$people->id] = $people->displayName();
			$index += 1;
		}

		//We declare signers
		$universignSigners = array();

		foreach($digitalSignatureRequest->people as $people) {
			$signer = new \Globalis\Universign\Request\TransactionSigner();
			$signer->setFirstname($people->firstName)
				->setLastname($people->lastName)
				->setPhoneNum($people->phoneNumber)
				->setEmailAddress($people->mail);
			$universignSigners[] = $signer;
		}

		//We declare document signature field
		$documentSignatureFieldsByLinkedDocumentId = array();
		foreach($digitalSignatureRequest->signatoryFields as $signatoryField) {
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
		foreach($digitalSignatureRequest->documents as $document) {
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
            ->setProfile("profile_demo")
            ->setCertificateTypes('simple')
            ->setLanguage('fr');

		$requester = $this->getUniversignRequester();
		$response = $requester->requestTransaction($request);

		return array('id'=>$response->id, 'url'=>$response->url);
	}

	/**
	 * Get information about a signature request on universign
	 *@param DigitalSignatureRequest $digitalSignatureRequest	local request to send
	 * @return bool|string   return the signature ID or false if some error appends
	 */
	public function getAndUpdateData($digitalSignatureRequest)
	{
		$requester = $this->getUniversignRequester();
		$universignRequestId = $digitalSignatureRequest->externalId;
		$requester->getTransactionInfo($universignRequestId);

		//toDo
		//update digitalSignatureRequest status
		//update digitalSignaturePeople status

		return 0;
	}

	/**
	 * Get information about a signature request on universign
	 * @param string $universignRequestId
	 * @return bool|string   return the signature ID or false if some error appends
	 */
	public function cancel($universignRequestId)
	{
		$requester = $this->getUniversignRequester();
		$response = $requester->cancelTransaction($universignRequestId);
		return $response->status === \Globalis\UniversitransactionIdgn\Response\TransactionInfo::STATUS_CANCELED;
	}

	private function getUniversignRequester() {
		// Create XmlRpc Client
		$client = new \PhpXmlRpc\Client('https://url.to.universign/end_point/');

		$client->setCredentials(
		'UNIVERSIGN_USER',
		'UNIVERSIGN_PASSWORD'
		);

		return new \Globalis\Universign\Requester($client);
	}
}
