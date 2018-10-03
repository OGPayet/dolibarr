<?php
/* Copyright (C) 2018 		Netlogic			<info@netlogic.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    doliesign/class/yousignsoap.class.php
 * \ingroup doliesign
 * \brief   Library files with common functions for DoliEsign
 */


require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

dol_include_once('/doliesign/lib/YsApi.php');
dol_include_once('/doliesign/class/doliesign.class.php');
dol_include_once('/doliesign/class/config.class.php');
dol_include_once('/contact/class/contact.class.php');

/**
 * Class ActionsDoliEsign
 */
class YousignSoap extends DoliEsign
{

	/**
	 * Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		parent::__construct($db);
	}

	/**
	 * Connect to Yousign rest service
	 *
	 * @return object Yousign resource
	 */

	function signConnect()
	{
		global $conf;

		$errors = array();
		$environment="yousign-demo";
		$login="";
		$password="";
		$apiKey="";
		if (! empty($conf->global->DOLIESIGN_ENVIRONMENT)) $environment = $conf->global->DOLIESIGN_ENVIRONMENT;
		if ($environment == 'yousign-prod') {
			if (! empty($conf->global->DOLIESIGN_LOGIN_PROD)) $login=$conf->global->DOLIESIGN_LOGIN_PROD;
			if (! empty($conf->global->DOLIESIGN_PASSWORD_PROD)) $password=$conf->global->DOLIESIGN_PASSWORD_PROD;
			if (! empty($conf->global->DOLIESIGN_API_KEY_PROD)) $apiKey=$conf->global->DOLIESIGN_API_KEY_PROD;
		} else if ($environment == 'yousign-demo') {
			if (! empty($conf->global->DOLIESIGN_LOGIN)) $login=$conf->global->DOLIESIGN_LOGIN;
			if (! empty($conf->global->DOLIESIGN_PASSWORD)) $password=$conf->global->DOLIESIGN_PASSWORD;
			if (! empty($conf->global->DOLIESIGN_API_KEY)) $apiKey=$conf->global->DOLIESIGN_API_KEY;
		} else {
			$this->errors[] = 'DoliEsignApiError';
			return false;
		}

		// Inclusion NUSOAP
		require_once NUSOAP_PATH.'/nusoap.php';     // Include SOAP
		$ys = new \YousignAPI\YsApi();

		if (!empty($login) && ! empty($password) && ! empty($apiKey)) {
			$ys->setEnvironment($environment);
			$ys->setLogin($login);
			if (! empty($conf->global->DOLIESIGN_ISENCRYPTEDPASSWORD)) {
				$password = $ys->encryptPassword($password);
			}
			$ys->setPassword($password);
			$ys->setApiKey($apiKey);
			$ys->connect();
		} else {
			$this->errors[] = 'DoliEsignSetupError';
			return false;
		}
		return $ys;
	}

	/**
	 * Init to Yousign signature
	 *
	 * @param object	$user 				user who initiates signing
	 * @param object	$object				dolibarr object to sign
	 * @param string	$emailInitTemplate	template type for request mail
	 * @param string	$emailEndTemplate	template type for confirm mail
	 * @param string	$authMode			authentication mode for signing sms or mail
	 *
	 * @return int 0 = OK, < 0 NOK
	 */

	function signInit($user, $object, $dir, $emailInitTemplate = '', $emailEndTemplate = '', $authMode = "sms")
	{
		require_once TCPDF_PATH.'/tcpdf.php';	// required for fpdi
		require_once TCPDI_PATH.'/tcpdi.php';     // fpdi for pagecount
		require_once DOL_DOCUMENT_ROOT .'/core/class/html.formmail.class.php';

		global $mysoc,$conf,$langs;

		$listPerson = array();
		$listId = array();
		$coordinates = array();
		$errors = array();
		$formMail = new FormMail($object->db);
		$initTemplate = new stdClass;
		$endTemplate = new stdClass;
		$error = 0;

		$ys = $this->signConnect();

		if (! $ys->isAuthenticated()) {
			$errors = $ys->getErrors();
			if (empty($errors)) {
				$this->errors[] = "DoliEsignConnectError";
			}
			return --$error;
		}

		if (! empty($conf->global->DOLIESIGN_CGV_REL_PATH)) $cgvPath=$conf->global->DOLIESIGN_CGV_REL_PATH;
		if (! empty($conf->global->DOLIESIGN_CGV_FILENAME)) $cgvFilename=$conf->global->DOLIESIGN_CGV_FILENAME;
		if (! empty($object->thirdparty->default_lang)) {
			$outputLang = new stdClass;
			$outputLang->defaultlang = $object->thirdparty->default_lang;
		} else {
			$outputLang = $langs;
		}
		$defaultLang = new stdClass;
		$defaultLang->defaultlang = 'en_US';
		$config = new DoliEsignConfig($object->db);
		$configIds = $config->fetchListId($object->element);
		$typeContacts = $config->get_type_contact_code($object->element, 'all');
		if (is_object($object) && is_array($configIds)) {
			$dolDirList = dol_dir_list($dir);
			foreach($dolDirList as $dolDir) {
				if ($dolDir['type'] == 'file' && basename($dolDir['name'],'.pdf') == $object->ref) {
					$file = $dolDir['fullname'];
				}
			}
			$pdf = new TCPDI();
			if (dol_is_file($file)) {
				// Placement des signatures sur le document
				$pageCount = $pdf->setSourceFile($file);
				$listFiles = array (
					array (
						'name' => basename($file),
						'content' => base64_encode(file_get_contents($file)),
						'idFile' => $file
					)
				);
			} else {
				$this->errors[] = "DoliEsignFileMissing";
				return --$error;
			}
			if ($conf->global->MAIN_MULTILANGS) {
				$cgv_pdf=DOL_DATA_ROOT.$cgvPath."/".$outputLang->defaultlang."/".$cgvFilename;
			}else{
				$cgv_pdf=DOL_DATA_ROOT.$cgvPath."/".$cgvFilename;
			}
			if (dol_is_file($cgv_pdf)) {
				$cgvPageCount = $pdf->setSourceFile($cgv_pdf);
			} else {
				$cgvPageCount = 0;
			}
			foreach($configIds as $configId) {
				$res = $config->fetch($configId);
				if ($res < 0) {
					$this->errors = $config->errors;
					return --$error;
				}
				if ($res == 0) {
					$this->errors[] = "DoliEsignContactMissing";
					return --$error;
				}

				if ($cgvPageCount > 0) {
					$pageCount -= $cgvPageCount;
					if(empty($config->cgv_sign_coordinate)) {
						$cgvPageCount = 0; // no signature on terms section or modules set with no terms section present
					}
				}

				$contactCode = $typeContacts[$config->fk_c_type_contact];
				$contactIds = $object->getIdContact('external', $contactCode);
				$userIds = $object->getIdContact('internal', $contactCode);
				if (count($contactIds) > 0) {
					foreach ($contactIds as $key => $contactId) {
						$contact = new Contact($object->db);
						if ($result = $contact->fetch($contactId) > 0){
							if (empty($contact->email)) {
								$this->errors[] = "DoliEsignContactEmailMissing";
								return --$error;
							}
							if ($authMode == 'sms' && empty($contact->phone_mobile)) {
								$this->errors[] = "DoliEsignContactPhoneMobileMissing";
								return --$error;
							} else if ($authMode == 'sms') {
								$contact->phone_mobile = doliEsignFixMobile($contact->phone_mobile, $contact->country_code);
								if (empty($contact->phone_mobile)) {
									$this->errors[] = "DoliEsignContactPhoneMobileWrongFormat";
									return --$error;
								}
							} else {
								$contact->phone_mobile = '';
							}
							$idObject = new stdClass;
							$idObject->id = $contact->id;
							$idObject->type = 'contact';
							$listId[] = $idObject;
							$listPerson[] = array (
								'firstName' => $contact->firstname,
								'lastName' => $contact->lastname,
								'mail' => $contact->email,
								'phone' => $contact->phone_mobile,
								'proofLevel' => 'LOW',
								'authenticationMode' => $authMode
							);
							$coordinates[] = array (
								'visibleSignaturePage' => $pageCount,
								'isVisibleSignature' => true,
								'visibleRectangleSignature' => $config->sign_coordinate,
								'mail' => $contact->email
							);
							if ($cgvPageCount > 0) {
								$coordinates[] = array (
									'visibleSignaturePage' => $pageCount + $cgvPageCount,
									'isVisibleSignature' => true,
									'visibleRectangleSignature' => $config->cgv_sign_coordinate,
									'mail' => $contact->email
								);
							}
						}
					}
				}
				if (count($userIds) > 0) {
					foreach ($userIds as $key => $userId) {
						$user = new User($object->db);
						if ($result = $user->fetch($userId) > 0){
							if (empty($user->email)) {
								$this->errors[] = "DoliEsignUserEmailMissing";
								return --$error;
							}
							if ($authMode == 'sms' && empty($user->user_mobile)) {
								$errors[] = "DoliEsignUserPhoneMobileMissing";
								return $errors;
							} else if ($authMode == 'sms') {
								$user->user_mobile = doliEsignFixMobile($user->user_mobile, $user->country_code);
								if (empty($user->user_mobile)) {
									$this->errors[] = "DoliEsignUserPhoneMobileWrongFormat";
									return --$error;
								}
							} else {
								$user->user_mobile = '';
							}
							$idObject = new stdClass;
							$idObject->id = $user->id;
							$idObject->type = 'user';
							$listId[] = $idObject;
							$listPerson[] = array (
								'firstName' => $user->firstname,
								'lastName' => $user->lastname,
								'mail' => $user->email,
								'phone' => $user->user_mobile,
								'proofLevel' => 'LOW',
								'authenticationMode' => $authMode
							);

							$coordinates[] = array (
								'visibleSignaturePage' => $pageCount,
								'isVisibleSignature' => true,
								'visibleRectangleSignature' => $config->sign_coordinate,
								'mail' => $user->email
							);
							if ($cgvPageCount > 0) {
								$coordinates[] = array (
									'visibleSignaturePage' => $pageCount + $cgvPageCount,
									'isVisibleSignature' => true,
									'visibleRectangleSignature' => $config->cgv_sign_coordinate,
									'mail' => $user->email
								);
							}
						}
					}
				}
			}
		}

		if (count($listPerson) == 0) {
			$this->errors[] = "DoliEsignContactMissing";
			return --$error;
		}

		$visibleOptions = array
		(
			// Placement des signatures pour le 1er document
			$listFiles[0]['idFile'] => $coordinates
		);

		$message = '';

		if (DoliEsign::checkDolVersion('7.0')) {
			if (! empty($emailInitTemplate) && (($template = $formMail->getEMailTemplate($object->db, $emailInitTemplate, $user, $outputLang)) > 0)) {
				$initTemplate->topic=$template['topic'];
				$initTemplate->content=$template['content'];
			} else {
				if (! empty($emailInitTemplate) && (($template = $formMail->getEMailTemplate($object->db, $emailInitTemplate, $user, $defaultLang)) > 0)) {
					$initTemplate->topic=$template['topic'];
					$initTemplate->content=$template['content'];
					setEventMessages($langs->trans('DoliEsignSendInitDefaultEnUs'), null , 'warnings');
				} else {
					setEventMessages($langs->trans('DoliEsignSendInitDefaultYousign'), null , 'warnings');
					$initTemplate->topic = '';
					$initTemplate->content = '';
				}
			}
			if (! empty($emailEndTemplate) && (($template = $formMail->getEMailTemplate($object->db, $emailEndTemplate, $user, $outputLang)) > 0)) {
				$endTemplate->topic=$template['topic'];
				$endTemplate->content=$template['content'];
			} else {
				if (! empty($emailEndTemplate) && (($template = $formMail->getEMailTemplate($object->db, $emailEndTemplate, $user, $defaultLang)) > 0)) {
					setEventMessages($langs->trans('DoliEsignSendEndDefaultEnUs'), null , 'warnings');
					$endTemplate->topic=$template['topic'];
					$endTemplate->content=$template['content'];
				} else {
					setEventMessages($langs->trans('DoliEsignSendEndDefaultYousign'), null , 'warnings');
					$initTemplate->topic = '';
					$initTemplate->content = '';
				}
			}
		} else {
			$formMail->lines_model = array();
			if (! empty($emailInitTemplate) && ($formMail->fetchAllEMailTemplate($emailInitTemplate, $user, $outputLang, 1) > 0)) {
				$initTemplate=$formMail->lines_model[0];
			} else {
				if (! empty($emailInitTemplate) && ($formMail->fetchAllEMailTemplate($emailInitTemplate, $user, $defaultLang, 1) > 0)) {
					$initTemplate=$formMail->lines_model[0];
					setEventMessages($langs->trans('DoliEsignSendInitDefaultEnUs'), null , 'warnings');
				} else {
					setEventMessages($langs->trans('DoliEsignSendInitDefaultYousign'), null , 'warnings');
					$initTemplate->topic = '';
					$initTemplate->content = '';
				}
			}
			$formMail->lines_model = array();
			if (! empty($emailEndTemplate) && ($formMail->fetchAllEMailTemplate($emailEndTemplate, $user, $outputLang, 1) > 0)) {
				$endTemplate=$formMail->lines_model[0];
			} else {
				if (! empty($emailEndTemplate) && ($formMail->fetchAllEMailTemplate($emailEndTemplate, $user, $defaultLang, 1) > 0)) {
					setEventMessages($langs->trans('DoliEsignSendEndDefaultEnUs'), null , 'warnings');
					$endTemplate=$formMail->lines_model[0];
				} else {
					setEventMessages($langs->trans('DoliEsignSendEndDefaultYousign'), null , 'warnings');
					$initTemplate->topic = '';
					$initTemplate->content = '';
				}
			}
		}


		if (DoliEsign::checkDolVersion('6.0')) {
			$substitutionarray=getCommonSubstitutionArray($langs);
		} else {
			$substitutionarray=doliEsignGetCommonSubstitutionArray($langs);
		}

		$initMailSubject = make_substitutions($initTemplate->topic, $substitutionarray);
		$initMailSubst = make_substitutions($initTemplate->content, $substitutionarray);
		$endMailSubject = make_substitutions($endTemplate->topic, $substitutionarray);
		$endMailSubst = make_substitutions($endTemplate->content, $substitutionarray);

		$logosmall=$mysoc->logo_small;
		if (! empty($logosmall) && is_readable($conf->mycompany->dir_output.'/logos/thumbs/'.$logosmall))
		{
			$logo=file_get_contents($conf->mycompany->dir_output.'/logos/thumbs/'.$logosmall);
			$initMail = preg_replace('/__LOGO__/','<img src="data:image/png;base64,'.base64_encode($logo).'"></img>',$initMailSubst);
			$endMail = preg_replace('/__LOGO__/','<img src="data:image/png;base64,'.base64_encode($logo).'"></img>',$endMailSubst);
		} else {
			$initMail = preg_replace('/__LOGO__/','',$initMailSubst);
			$endMail = preg_replace('/__LOGO__/','',$endMailSubst);
		}

		$options = array(
			'mode' => '',
			'archive' => false,
			'initMailSubject' => $initMailSubject,
			'initMail' => $initMail,
			'endMailSubject' => $endMailSubject,
			'endMail' => $endMail
		);

		$ysResult = $ys->initCoSign($listFiles, $listPerson, $visibleOptions, $message, $options);

		if($ysResult === false) {
			$errors = $ys->getErrors();
			if (empty($errors)) {
				$this->errors[] = "DoliEsignApiError";
			}
			return --$error;
		}
		else
		{
			$refs = array();
			if (count($listPerson) > 1) {
				foreach($ysResult['tokens'] as $token) {
					$refs[] = $token['token'];
				}
			} else {
				$refs[] = $ysResult['tokens']['token'];
			}
			foreach($refs as $key=>$ref) {
				// create doliesign object
				$this->ref = $ref;
				$this->id_file = $ysResult['fileInfos']['idFile'];
				$this->hash_file = $ysResult['fileInfos']['sha1'];
				$this->path_file = $file;
				$this->sign_id = $ysResult['idDemand'];
				$this->sign_status = 'COSIGNATURE_FILE_REQUEST_PENDING';
				$this->status = DoliEsign::STATUS_WAITING;
				$this->date_creation = dol_now();
				$this->fk_object = $object->id;
				$this->object_type = $object->element;
				if ($listId[$key]->type == 'contact') $this->fk_contact_sign = $listId[$key]->id;
				if ($listId[$key]->type == 'user') $this->fk_user_sign = $listId[$key]->id;
				$this->fk_user_creat = $user->id;
				$this->fk_soc = $object->socid;
				$this->api_name = 'Yousign SOAP';
				$res = $this->create($user);
				if ($res < 0) {
					$this->errors = $this->errors;
					return --$error;
				}
			}
			// store date creation of yousign
			$res = signInfo($user, $object, 'info', $ys);
			if ($res < 0) {
				return $res;
			} else {
				return 0;
			}
		}
	}

	/**
	 * get info on Yousign signature
	 *
	 * @param object	$user 				user who gets signing info
	 * @param object	$object				dolibarr object
	 * @param string	$mode				'sync' or 'dolibarr object type
	 * @param object	$ys					yousign api instance
	 *
	 * @return int with null = no info, lowest status = OK, < 0 if NOK
	 */
	function signInfo($user, $object, $mode='sync', $ys = null)
	{
		global $langs;

		$error = 0;
		$objectId = $object->id;
		$objectType = $object->element;
		$status = null;
		$tokens = $this->fetchTokens($objectId, $objectType);
		if (is_array($tokens) && count($tokens) > 0) {
			foreach ($tokens as $token) {
				if ($token->status == DoliEsign::STATUS_WAITING || $token->status == DoliEsign::STATUS_ERROR || $mode == 'info') {
					if (empty($ysResult)) {
						if (! is_object($ys)) {
							$ys = $this->signConnect();
						}
						if (! $ys->isAuthenticated()) {
							$errors = $ys->getErrors();
							if (empty($errors)) {
								$this->errors[] = "DoliEsignConnectError";
							}
							return --$error;
						}
						$ysResult = $ys->getCosignInfoFromIdDemand($token->idDemand);
						if($ysResult === false) {
							$errors = $ys->getErrors();
							if (empty($errors)) {
								$this->errors[] = "DoliEsignApiError";
							}
							return --$error;
						}
					}
					$res = $this->fetch($token->id);
					if ($res < 0) {
						return $res;
					} else {
						$this->sign_status = $ysResult['status'];
						$dateCreate = new DateTime($ysResult['dateCreation']);
						$this->date_creation = $dateCreate->getTimestamp();
						if (count($tokens) > 1 && is_array($ysResult['fileInfos']['cosignersWithStatus'])) {
							foreach ($ysResult['fileInfos']['cosignersWithStatus'] as $signerStatus) {
								foreach ($ysResult['cosignerInfos'] as $signerInfo) {
									if ($signerStatus['id'] == $signerInfo['id'] && $signerInfo['token'] == $token->ref) {
										if (array_key_exists('signatureDate', $signerStatus)) {
											$dateSign = new DateTime($signerStatus['signatureDate']);
										}
										$statusSign = $signerStatus['status'];
									}
								}
							}
						} else {
							if (array_key_exists('signatureDate', $ysResult['fileInfos']['cosignersWithStatus'])) {
								$dateSign = new DateTime($ysResult['fileInfos']['cosignersWithStatus']['signatureDate']);
							}
							$statusSign = $ysResult['fileInfos']['cosignersWithStatus']['status'];
						}
						if (isset($dateSign)) {
							$this->date_sign = $dateSign->getTimestamp();
							$dateSign = null;
						}
						if (isset($statusSign)) {
							$this->sign_status = $statusSign;
							$statusSign = null;
						}
						if ($ysResult['status'] == 'COSIGNATURE_EVENT_REQUEST_PENDING' || $ysResult['status'] == 'COSIGNATURE_EVENT_PROCESSING') {
							$this->status = DoliEsign::STATUS_WAITING;
						} elseif ($ysResult['status'] == 'COSIGNATURE_EVENT_OK') {
							// signed
							$this->status = DoliEsign::STATUS_SIGNED;
						} elseif ($ysResult['status'] == 'COSIGNATURE_EVENT_CANCELLED') {
							// canceled
							$this->status = DoliEsign::STATUS_CANCELED;
						} elseif ($ysResult['status'] == 'COSIGNATURE_EVENT_PARTIAL_ERROR') {
							// Error
							$this->status = DoliEsign::STATUS_ERROR;
						}
						$status = $this->status;
						$this->fk_user_modif=$user->id;
						$res = $this->update($user);
						if ($res < 0) {
							return $res;
						}
					}
				} else {
					$status = $token->status;
				}
			}
			if ($objectType == 'propal' && $status == DoliEsign::STATUS_SIGNED) {
				if (! $this->fk_object > 0 && $token->id > 0) {
					$this->fetch($token->id);
				}
				$propal = new Propal($this->db);
				$res = $propal->fetch($this->fk_object);
				if ($res > 0 && $propal->statut == Propal::STATUS_VALIDATED) {
					$res = $propal->cloture($user, Propal::STATUS_SIGNED, $langs->trans('SignedByDoliEsign'));
				}
				if ($res < 0) {
					$this->errors = $propal->errors;
					return $res;
				}
			}
		}
		return $status;
	}

	/**
	 * cancel Yousign signature
	 *
	 * @param object	$user 				user who cancels signing
	 *
	 * @return int 0 = OK, < 0 if NOK
	 */
	function signCancel($user)
	{
		$error = 0;
		$idDemand = $this->sign_id;

		if (! empty($idDemand)) {
			$ys = $this->signConnect();

			if (! $ys->isAuthenticated()) {
				$errors = $ys->getErrors();
				if (empty($errors)) {
					$this->errors[] = "DoliEsignConnectError";
				}
				return --$error;
			}

			$result = $ys->deleteCosignDemand($idDemand);

			// Affichage des résultats
			if($result === false) {
				$errors = $ys->getErrors();
				if (empty($errors)) {
					$this->errors[] = "DoliEsignApiError";
				}
				return --$error;
			} elseif ($result == 'true') {
				// canceled
				$this->status = DoliEsign::STATUS_CANCELED;
				$this->fk_user_modif=$user->id;
				$res = $this->update($user);
				if ($res < 0) {
					return $res;
				} else {
					return 0;
				}
			}
		}
	}

	/**
	 * fetch signed document from Yousign service
	 *
	 * @param object	$user 				user who gets document
	 * @param object	$object				dolibarr object
	 *
	 * @return int 0 = OK, < 0 if NOK
	 */
	function signFetch($user, $object)
	{
		$result = 0;
		$error = 0;
		$ysResult = null;
		$tokens = $this->fetchTokens($object->id, $object->element);
		if (is_array($tokens) && count($tokens) > 0) {
			foreach ($tokens as $token) {
				if ($token->status == DoliEsign::STATUS_SIGNED || $token->status == DoliEsign::STATUS_FILE_FETCHED) {
					$res = $this->fetch($token->id);
					if ($res < 0) {
						return $res;
					} elseif (! empty($token->idDemand) && ! empty($this->id_file)) {
						if (! isset($ysResult)) {
							$ys = $this->signConnect();
							if (! $ys->isAuthenticated()) {
								$errors = $ys->getErrors();
								if (empty($errors)) {
									$this->errors[] = "DoliEsignConnectError";
								}
								return --$error;
							}
							$ysResult = $ys->getCosignedFileFromIdDemand($token->idDemand, $this->id_file);
							if($ysResult === false) {
								$errors = $ys->getErrors();
								$this->errors[] = "DoliEsignApiError";
								return --$error;
							} else {
								$file = $this->path_file;
								$res = file_put_contents($file, base64_decode($ysResult['file']));
								if ($res > 0) {
									$result = 1;
								} else {
									$this->errors[] = "DoliEsignCreateFileError";
									return --$error;
								}
							}
						}
						if ($result == 1) {
							$this->status = DoliEsign::STATUS_FILE_FETCHED;
							$this->fk_user_modif=$user->id;
							$res = $this->update($user);
							if ($res < 0) {
								return $res;
							}
						}
					}
				}
			}
		}
		return $result;
	}
}