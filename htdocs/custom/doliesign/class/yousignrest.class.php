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
 * \file    doliesign/class/yousignrest.class.php
 * \ingroup doliesign
 * \brief   Library files with common functions for DoliEsign
 */


require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

dol_include_once('/doliesign/lib/restclient.php');
dol_include_once('/doliesign/class/doliesign.class.php');
dol_include_once('/doliesign/class/config.class.php');
dol_include_once('/contact/class/contact.class.php');

/**
 * Class ActionsDoliEsign
 */
class YousignRest extends DoliEsign
{
	const API_NAMESPACE = 'http://www.yousign.com';

	const API_ENV_DEMO = 'demo';
	const API_ENV_PROD = 'prod';

	const API_URL_DEMO = 'https://staging-api.yousign.com/';
	const API_URL_PROD = 'https://api.yousign.com/';

	const IFRAME_URL_DEMO = 'https://staging-app.yousign.com';
	const IFRAME_URL_PROD = 'https://webapp.yousign.com';

	/**
	 * Contient le login de connexion au web service de l'utilisateur courant.
	 *
	 * @var string
	 */
	private $_login = '';

	/**
	 * Contient le mot de passe de connexion au web service en sha1.
	 *
	 * @var string
	 */
	private $_password = '';

	/**
	 * La clé d'API.
	 *
	 * @var string
	 */
	private $apikey = '';

	/**
	 * Url d'accès à l'API.
	 *
	 * @var string
	 */
	private $urlApi = '';

	/**
	 * URL d'accès à l'Iframe.
	 *
	 * @var string
	 */
	private $urlIframe = '';

	/**
	 * Définit si l'utilisateur est bien authentifié ou non.
	 *
	 * @var bool
	 */
	private $isAuthenticated = false;

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
	 * Modifie l'url d'accès à l'API.
	 *
	 * @param $urlApi
	 * @return $this
	 */
	public function setUrlApi($urlApi)
	{
		$this->urlApi = $urlApi;

		return $this;
	}

	/**
	 * Modifie l'URL d'accès à l'Iframe.
	 *
	 * @param $urlIframe
	 * @return $this
	 */
	public function setUrlIframe($urlIframe)
	{
		$this->urlIframe = $urlIframe;

		return $this;
	}

	/**
	 * Modification de l'identifiant d'accès à l'API.
	 *
	 * @param $login
	 * @return $this
	 */
	public function setLogin($login)
	{
		$this->_login = $login;

		return $this;
	}

	/**
	 * Modification du mot de passe d'accès à l'API.
	 *
	 * @param $password
	 * @return $this
	 */
	public function setPassword($password)
	{
		$this->_password = $password;

		return $this;
	}

	/**
	 * Modification de la clé d'API Yousign.
	 *
	 * @param $apikey
	 * @return $this
	 */
	public function setApiKey($apikey)
	{
		$this->apikey = $apikey;

		return $this;
	}

	/**
	 * Instance du client rest.
	 *
	 * @var \RestClient
	 */
	private $client;

	/**
	 * Permet de générer les headers nécessaire à l'authentification de l'utilisateur final.
	 *
	 * @param bool $withUser
	 * @return string
	 */
	private function createHeaders($withUser = true)
	{
		if ($withUser === true) {
			return  array(
				'Content-Type' => 'application/json',
				'Authorization' => 'Bearer ' . $this->apikey,
				'username' => $this->_login,
				'password' => $this->_password,
			);
		} else {
			return array(
				'Content-Type' => 'application/json',
				'Authorization' => 'Bearer ' . $this->apikey,
			);
		}
	}

	private function getUserAgent()
	{
		dol_include_once('/doliesign/core/modules/modDoliEsign.class.php');

		$module = new modDoliEsign($db);
		return $module->name.'/'.$module->version;
	}

	/**
	 * Retourne l'état d'authentification de l'utilisateur courant.
	 *
	 * @return bool
	 */
	public function isAuthenticated()
	{
		return $this->isAuthenticated;
	}

	/**
	 * Modifie l'environnement de l'API utilisé. (env|prod)
	 *
	 * @param $environment
	 * @return $this
	 */
	public function setEnvironment($environment)
	{
		switch ($environment) {
			// Environnement de production
			case self::API_ENV_PROD:
				$this->setUrlIframe(self::IFRAME_URL_PROD);
				$this->setUrlApi(self::API_URL_PROD);

				return $this;

			// Par défaut, environnement de démo
			case self::API_URL_DEMO:
			default:
				$this->setUrlIframe(self::IFRAME_URL_DEMO);
				$this->setUrlApi(self::API_URL_DEMO);

				return $this;
		}
	}

	/**
	 * Retourne l'instance du client soap utilisé.
	 *
	 * @return \SoapClient
	 */
	public function getClient()
	{
		return $this->client;
	}

	/**
	 * Cryptage du mot de passe.
	 *
	 * @param $password
	 * @return string
	 */
	public function encryptPassword($password)
	{
		return sha1(sha1($password).sha1($password));
	}

	/**
	 * Connexion à l'API.
	 *
	 * @return boolean true connected, false not connected
	 */
	public function connect()
	{
		$this->client = new RestClient(array(
			'base_url' => $this->urlApi,
			'format' => "json",
			'user_agent' => $this->getUserAgent(),
			'headers' => $this->createHeaders(true),
		));

		$result = $this->client->get("users");
		// $result->info->http_code
		// $result->response
		//var_dump($result);
		//var_dump($this->client);

		if ($result->info->http_code != 200) {
			$this->errors[] = $result->response;
			$this->errors[] = 'DoliEsignApiError';
			return false;
		} else {
			$this->isAuthenticated = true;
			return true;
		}
	}

	/**
	 * Connect to Yousign rest service
	 *
	 * @return boolean true is connectected, false is not connected
	 */

	function signConnect()
	{
		global $conf;

		$result = false;
		$environment="demo";
		$login="";
		$password="";
		$apiKey="";
		if (! empty($conf->global->DOLIESIGN_ENVIRONMENT)) $environment = $conf->global->DOLIESIGN_ENVIRONMENT;
		if ($environment == 'yousign-api') {
			if (! empty($conf->global->DOLIESIGN_LOGIN_PROD)) $login=$conf->global->DOLIESIGN_LOGIN_PROD;
			if (! empty($conf->global->DOLIESIGN_PASSWORD_PROD)) $password=$conf->global->DOLIESIGN_PASSWORD_PROD;
			if (! empty($conf->global->DOLIESIGN_API_KEY_PROD)) $apiKey=$conf->global->DOLIESIGN_API_KEY_PROD;
		} else if ($environment == 'yousign-staging-api') {
			if (! empty($conf->global->DOLIESIGN_LOGIN)) $login=$conf->global->DOLIESIGN_LOGIN;
			if (! empty($conf->global->DOLIESIGN_PASSWORD)) $password=$conf->global->DOLIESIGN_PASSWORD;
			if (! empty($conf->global->DOLIESIGN_API_KEY)) $apiKey=$conf->global->DOLIESIGN_API_KEY;
		} else {
			$this->errors[] = 'DoliEsignApiError';
			return false;
		}

		if (!empty($login) && ! empty($password) && ! empty($apiKey)) {
			$this->setEnvironment($environment);
			$this->setLogin($login);
			if (! empty($conf->global->DOLIESIGN_ISENCRYPTEDPASSWORD)) {
				$password = $this->encryptPassword($password);
			}
			$this->setPassword($password);
			$this->setApiKey($apiKey);
			$result = $this->connect();
		} else {
			$this->errors[] = 'DoliEsignSetupError';
			return false;
		}
		return $result;
	}

	/**
	 * Cette méthode est utilisée pour initialiser une demande de signature.
	 * Vous passerez en paramètre une liste de fichiers à signer ainsi qu'une liste d'informations des signataires.
	 *
	 * Ils recevront ensuite un email contenant une URL unique pour accéder à l'interface de signature du/des documents afin de le/les signer.
	 *
	 * example:
	 * ----------
	 *     $listFiles = array(
	 *         array(
	 *             'name' => 'Fichier 1',
	 *             'content' => 'base64 du fichier'
	 *         ),
	 *         array(
	 *             'name' => 'Fichier 2',
	 *             'content' => 'base64 du fichier'
	 *         ),
	 *     );
	 *
	 *     $lstMembers = array
	 *     (
	 *         array(
	 *             'firstName' => 'Prenom 1',
	 *             'lastName' => 'Nom 1',
	 *             'mail' => 'prenom.nom1@mail.com',
	 *             'phone' => '+33123456789'
	 *         ),
	 *         array(
	 *             'firstName' => 'Prenom 1',
	 *             'lastName' => 'Nom 1',
	 *             'mail' => 'prenom.nom2@mail.com',
	 *             'phone' => '+33123456789'
	 *         ),
	 *     );
	 *
	 *     $fileObjects = array
	 *     (
	 *            array(
	 *                 'page' => '1',
	 *                 'position' => 'llx,lly,urx,ury',
	 *                 'indexMember' => 0,
	 *                 'indexFile' => 0
	 *             ),
	 *             array(
	 *                 'page' => '2',
	 *                 'position' => 'llx,lly,urx,ury',
	 *                 'indexMember' => 1,
	 *                 'indexFile' => 0
	 *             )
	 *     );
	 *
	 *     $procedure = array
	 *     (
	 *            'name' => 'My first procedure',
	 *            'description' => 'My first description of my first procedure'
	 *     )
	 *
	 *     $email = array
	 *     (
	 *         'procedure.started' = array(
	 *             'subject' => 'Sujet de l\'email',
	 *             'message' => 'Contenu de l\'email'
	 *         ),
	 *         'procedure.finished' = array(
	 *             'subject' => 'Sujet de l\'email',
	 *             'message' => 'Contenu de l\'email'
	 *         )
	 *     );
	 *
	 * @param array  $lstFiles       : Liste du/des fichiers à signer, chaque fichier doit définir:
	 *                                   - name : Nom du fichier à signer
	 *                                   - content : Contenu du fichier à signer encodé en base64
	 * @param array  $lstMembers     : Liste des signataires, chaque signataire doit définir:
	 *                                   - firstName : Le prénom du signataire
	 *                                   - lastName : Le nom du signataire
	 *                                   - mail : L'email du signataire (ou un id si c'est en mode Iframe)
	 *                                   - phone : Le numéro de téléphone du signataire (indicatif requis, exemple: +33612326554)
	 * @param array  $fileObjects    : Liste d'informations requis pour le placement des signatures
	 *                                   - page : Numéro de la page contenant les signatures
	 *                                   - position : Les coordonnées de l'image de signature (ignoré si "isVisibleSignature" est à false)
	 *                                     Le format est "llx,lly,urx,ury" avec:
	 *                                         * llx: left lower x coordinate
	 *                                         * lly: left lower y coordinate
	 *                                         * urx: upper right x coordinate
	 *                                         * ury: upper right y coordinate
	 *                                   - indexMember : index of $lstMembers
	 *                                   - indexFile   : index of $lstFiles
	 * @param array  $procedure      : Description procedure
	 * @param array  $email          : Message de l'email qui sera envoyé aux signataires
	 *                                 - procedure.started
	 *                                   - subject : Sujet de l'email envoyé à tous les signataires à la création de la signature
	 *                                   - message : Corps de l'email envoyé à tous les signataires à la création de la signature.
	 *                                 - procedure.finished
	 *                                   - subject : Sujet de l'email envoyé à tous les signataires à la finition de la signature
	 *                                   - message : Corps de l'email envoyé à tous les signataires à la finition de la signature.
	 *
	 *
	 * @return array api response
	 */
	public function initProcedure(&$lstFiles, $lstMembers, $fileObjects, $procedure, $email = array())
	{
		// Les fichiers avec ses positions
		foreach ($fileObjects as &$fileObject) {
			foreach ($lstFiles as $indexFile => &$file) {
				$result = $this->client->post('files', json_encode($file));
				$response = json_decode($result->response, true);
				if ($result->info->http_code != 201) {
					$this->errors[] = 'DoliEsignApiError';
					$this->errors[] = $response['detail'];
					return $response;
				} else {
					$file['file'] = $response['id'];
					if ($fileObject['indexFile'] == $indexFile) {
						unset($fileObject['indexFile']);
						$fileObject['file'] = $file['file'];
					}
				}
			}
		}

		// Les members
		foreach ($lstMembers as $indexMember => &$member) {
			foreach ($fileObjects as &$fileObject) {
				if ($fileObject['indexMember'] == $indexMember) {
					unset($fileObject['indexMember']);
					$member['fileObjects'][] = $fileObject;
				}
			}
			$procedure['members'][] = $member;
		}

		// send to all members
		$email['procedure.started'][0]['to'] = array('@members');
		$email['procedure.finished'][0]['to'] = array('@members');
		$procedure['config']['email'] = $email;

		// start procedure
		$procedure['start'] = true;
		$adummy = json_encode($procedure);
		$result = $this->client->post('procedures', json_encode($procedure));
		$response = json_decode($result->response, true);
		if ($result->info->http_code != 201) {
			$this->errors[] = 'DoliEsignApiError';
			$this->errors[] = $response['detail'];
		}
		return $response;
	}

	/**
	 * Init to Yousign signature
	 *
	 * @param object	$user 				user who initiates signing
	 * @param object	$object				dolibarr object to sign
	 * @param string	$emailInitTemplate	template type for request mail
	 * @param string	$emailEndTemplate	template type for confirm mail
	 * @param string	$authMode			authentication mode not used for REST
	 *
	 * @return int 0 = OK, < 0 NOK
	 */

	function signInit($user, $object, $dir, $emailInitTemplate = '', $emailEndTemplate = '', $authMode = "")
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

		if (! $this->isAuthenticated()) {
			$this->signConnect();
		}

		if (! $this->isAuthenticated()) {
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
				$indexFile = 0;
				$pageCount = $pdf->setSourceFile($file);
				if(!empty($conf->global->DOLIESIGN_CGV_NB_PAGE)) {
					$pageCount -= $conf->global->DOLIESIGN_CGV_NB_PAGE;
				}
				$listFiles = array (
					array (
						'name' => basename($file),
						'content' => base64_encode(file_get_contents($file))
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
				$indexMember = 0;
				if (count($contactIds) > 0) {
					foreach ($contactIds as $key => $contactId) {
						$contact = new Contact($object->db);
						if ($result = $contact->fetch($contactId) > 0){
							if (empty($contact->email)) {
								$this->errors[] = "DoliEsignContactEmailMissing";
								return --$error;
							}
							$contact->phone_mobile = doliEsignFixMobile($contact->phone_mobile, $contact->country_code);
							$idObject = new stdClass;
							$idObject->id = $contact->id;
							$idObject->type = 'contact';
							$listId[] = $idObject;
							$listMembers[] = array (
								'firstname' => $contact->firstname,
								'lastname' => $contact->lastname,
								'email' => $contact->email,
								'phone' => $contact->phone_mobile
							);
							$fileObjects[] = array (
								'page' => $pageCount,
								'position' => $config->sign_coordinate,
								'indexMember' => $indexMember,
								'indexFile' => $indexFile
							);
							if ($cgvPageCount > 0) {
								$fileObjects[] = array (
									'page' => $pageCount + $cgvPageCount,
									'position' => $config->cgv_sign_coordinate,
									'indexMember' => $indexMember,
									'indexFile' => $indexFile
								);
							}
							$indexMember++;
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
							$user->user_mobile = doliEsignFixMobile($user->user_mobile, $user->country_code);
							$idObject = new stdClass;
							$idObject->id = $user->id;
							$idObject->type = 'user';
							$listId[] = $idObject;
							$listMembers[] = array (
								'firstname' => $user->firstname,
								'lastname' => $user->lastname,
								'email' => $user->email,
								'phone' => $user->user_mobile
							);
							$fileObjects[] = array (
								'page' => $pageCount,
								'position' => $config->sign_coordinate,
								'indexMember' => $indexMember,
								'indexFile' => $indexFile
							);
							if ($cgvPageCount > 0) {
								$fileObjects[] = array (
									'page' => $pageCount + $cgvPageCount,
									'position' => $config->cgv_sign_coordinate,
									'indexMember' => $indexMember,
									'indexFile' => $indexFile
								);
							}
							$indexMember++;
						}
					}
				}
			}
		}

		if (count($listMembers) == 0) {
			$this->errors[] = "DoliEsignContactMissing";
			return --$error;
		}

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

		$initMail = str_replace('{yousignUrl}', '<br><br>{{ components.button("Accéder aux documents", url) }}', $initMail);
		$endMail = str_replace('{yousignUrl}', '<br><br>{{ components.button("Accéder aux documents", url) }}', $endMail);

		$email = array(
			'procedure.started' => array(array(
				'subject' => $initMailSubject,
				'message' => $initMail
			)),
			'procedure.finished' => array(array(
				'subject' => $endMailSubject,
				'message' => $endMail
			))
		);

		$procedure = array(
			'name' => $outputLang->trans($object->element) . ' ' . $object->ref,
			'description' => $outputLang->trans('CreateUpdateDoliEsign')
		);

		$response = $this->initProcedure($listFiles, $listMembers, $fileObjects, $procedure, $email);

		if(count($this->errors) > 0) {
			return --$error;
		}
		else
		{
			foreach($response['members'] as $key=>$member) {
				// create doliesign object
				$this->ref = str_replace('/members/', '', $member['id']);
				$this->id_file = str_replace('/files/', '', $listFiles[$indexFile]['file']);
				$this->hash_file = '';
				$this->path_file = $file;
				$this->sign_id = str_replace('/procedures/', '', $response['id']);
				$this->sign_status = $response['status'];
				$this->status = DoliEsign::STATUS_WAITING;
				$dateCreate = new DateTime($response['createdAt']);
				$this->date_creation = $dateCreate->getTimestamp();
				$this->fk_object = $object->id;
				$this->object_type = $object->element;
				if ($listId[$key]->type == 'contact') $this->fk_contact_sign = $listId[$key]->id;
				if ($listId[$key]->type == 'user') $this->fk_user_sign = $listId[$key]->id;
				$this->fk_user_creat = $user->id;
				$this->fk_soc = $object->socid;
				$this->api_name = 'Yousign Rest';
				$res = $this->create($user);
				if ($res < 0) {
					$this->errors = $this->errors;
					return $res;
				}
			}
			return 0;
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
					if (! $this->isAuthenticated()) {
						$this->signConnect();
					}
					if (! $this->isAuthenticated()) {
						return --$error;
					}
					$result = $this->client->get('procedures/'.$token->idDemand);
					$response = json_decode($result->response, true);
					if ($result->info->http_code != 200) {
						$this->errors[] = 'DoliEsignApiError';
						$this->errors[] = $response['detail'];
						return --$error;
					}
					$res = $this->fetch($token->id);
					if ($res < 0) {
						return $res;
					} else {
						if ($response['status'] == 'draft' || $response['status'] == 'active') {
							$this->status = DoliEsign::STATUS_WAITING;
						} else if ($response['status'] == 'finished') {
							$this->status = DoliEsign::STATUS_SIGNED;
						} else if ($response['status'] == 'expired') {
							$this->status = DoliEsign::STATUS_CANCELED;
						} else {
							$this->status = DoliEsign::STATUS_ERROR;
						}
						$dateCreate = new DateTime($response['createdAt']);
						$this->date_creation = $dateCreate->getTimestamp();
						$dateCreate = null;

						foreach ($response['members'] as $member) {
							if (!empty($member['finishedAt']) && $member['id'] == '/members/'.$token->ref) {
								$dateSign = new DateTime($member['finishedAt']);
								$this->sign_status = $member['status'];
							}
						}
						if (!empty($dateSign)) {
							$this->date_sign = $dateSign->getTimestamp();
							$dateSign = null;
						}
						/*if ($this->sign_status == 'pending' || $this->sign_status == 'processing') {
							$this->status = DoliEsign::STATUS_WAITING;
						} elseif ($this->sign_status == 'done') {
							// signed
							$this->status = DoliEsign::STATUS_SIGNED;
						} elseif ($this->sign_status == 'refused') {
							// canceled
							$this->status = DoliEsign::STATUS_CANCELED;
						} else {
							// Error
							$this->status = DoliEsign::STATUS_ERROR;
						}*/
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
			if (! $this->isAuthenticated()) {
				$this->signConnect();
			}
			if (! $this->isAuthenticated()) {
				return --$error;
			}
			$result = $this->client->delete('procedures/'.$idDemand);
			$response = json_decode($result->response, true);
			if ($result->info->http_code != 204) {
				$this->errors[] = 'DoliEsignApiError';
				$this->errors[] = $response['detail'];
				return --$error;
			} else {
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

		$tokens = $this->fetchTokens($object->id, $object->element);
		if (is_array($tokens) && count($tokens) > 0) {
			foreach ($tokens as $token) {
				if ($token->status == DoliEsign::STATUS_SIGNED || $token->status == DoliEsign::STATUS_FILE_FETCHED) {
					$res = $this->fetch($token->id);
					if ($res < 0) {
						return $res;
					} elseif (! empty($token->idDemand) && ! empty($this->id_file)) {
						if (! $this->isAuthenticated()) {
							$this->signConnect();
						}
						if (! $this->isAuthenticated()) {
							return --$error;
						}
						$result = $this->client->get('files/'.$this->id_file.'/download');
						$response = json_decode($result->response, true);
						if ($result->info->http_code != 200) {
							$this->errors[] = 'DoliEsignApiError';
							$this->errors[] = $response['detail'];
							return --$error;
						} else {
							$file = $this->path_file;
							$res = file_put_contents($file, base64_decode($response));
							if ($res > 0) {
								$result = 1;
							} else {
								$this->errors[] = "DoliEsignCreateFileError";
								return --$error;
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