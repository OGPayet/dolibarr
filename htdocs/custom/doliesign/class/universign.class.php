<?php
/* Copyright (C) 2018 		Netlogic			<info@netlogic.fr>
 * Copyright (C) 2018      Alexis LAURIER             <alexis@alexislaurier.fr>
 * Copyright (C) 2018      Synergies-Tech             <infra@synergies-france.fr>
 *
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
 * \file    doliesign/class/universign.class.php
 * \ingroup doliesign
 * \brief   Permet au module DoliEsign d'utiliser Universign
 */


require_once DOL_DOCUMENT_ROOT .'/core/class/CMailFile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

dol_include_once('/doliesign/lib/xmlrpc.inc');
dol_include_once('/doliesign/class/doliesign.class.php');
dol_include_once('/doliesign/class/config.class.php');
dol_include_once('/contact/class/contact.class.php');

$GLOBALS['xmlrpc_internalenconding']='UTF-8';

/**
 * Class ActionDoliEsign
*/
class Universign extends DoliEsign
{
	const API_ENV_DEMO = 'universign-demo';
	const API_ENV_PROD = 'universign-prod';

    const API_URL_PROD = "ws.universign.eu/sign/rpc/";
    const API_URL_DEMO = "sign.test.cryptolog.com/sign/rpc/";

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
	 * Instance du client rest.
	 *
	 * @var \RestClient
	 */
	private $client;


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
				$this->setUrlApi(self::API_URL_PROD);

				return $this;

			// Par défaut, environnement de démo
			case self::API_URL_DEMO:
			default:
				$this->setUrlApi(self::API_URL_DEMO);

				return $this;
		}
	}


    /**
     * Connexion à l'API.
     *
     * @return boolean true connexion réussi, false connexion échoué
     */
    public function connect()
    {
        $this->client = "https://" .
            $this->_login . ":" .
            $this->_password ."@" .
            $this->urlApi;

        $this->client = new xmlrpc_client($this->client);

        if($this->client->errno != null) {
            $this->errors[] = $this->client->errstr;
            $this->errors[] = 'DoliEsignApiError';
            return false;
        }
        else {
            $this->isAuthenticated = true;
            return true;
        }
    }

    /**
     * Connexion à l'API Universign
     *
     * @return boolean true connexion réussi, false connexion échoué
     */
    function signConnect()
    {
        global $conf;

        $result = false;
        $login= "";
		$password= "";
		$environment= "universign-demo";

		if (! empty($conf->global->DOLIESIGN_ENVIRONMENT)) $environment = $conf->global->DOLIESIGN_ENVIRONMENT;
		if ($environment == 'universign-prod') {
			if (! empty($conf->global->DOLIESIGN_LOGIN_UNIVERSIGN_PROD)) $login= $conf->global->DOLIESIGN_LOGIN_UNIVERSIGN_PROD;
			if (! empty($conf->global->DOLIESIGN_PASSWORD_UNIVERSIGN_PROD)) $password= $conf->global->DOLIESIGN_PASSWORD_UNIVERSIGN_PROD;
		} else if ($environment == 'universign-demo') {
			if (! empty($conf->global->DOLIESIGN_LOGIN_UNIVERSIGN_DEMO)) $login= $conf->global->DOLIESIGN_LOGIN_UNIVERSIGN_DEMO;
		if (! empty($conf->global->DOLIESIGN_PASSWORD_UNIVERSIGN_DEMO)) $password= $conf->global->DOLIESIGN_PASSWORD_UNIVERSIGN_DEMO;
		} else {
			$this->errors[] = 'DoliEsignApiError';
			return false;
		}

        if(!empty($login) && ! empty($password)) {
			$this->setEnvironment($environment);
            $this->setLogin($login);
            $this->setPassword($password);
            $result = $this->connect();
        }
        else {
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
	 *             'name' => new xmlrpcval('Fichier 1', "string"),
	 *             'content' => new xmlrpcval('base64 du fichier', "base64"),
     *             'signatureFields' => $signatureFieldsDoc
	 *         ),
	 *         array(
	 *             'name' => new xmlrpcval('Fichier 2', "string"),
	 *             'content' => new xmlrpcval('base64 du fichier', "base64"),
     *             'signatureFields' => new xmlrpcval($signatureFieldsDoc, "array")
	 *         )
	 *     );
	 *
	 *     $lstMembers = array(
	 *         array(
	 *             'firstname' => new xmlrpcval('Prenom 1', "string"),
	 *             'lastname' => new xmlrpcval('Nom 1', "string"),
	 *             'emailAddress' => new xmlrpcval('prenom.nom1@mail.com', "string"),
	 *             'phoneNum' => new xmlrpcval('+33123456789', "string")
	 *         ),
	 *         array(
	 *             'firstName' => new xmlrpcval('Prenom 1', "string"),
	 *             'lastName' => new xmlrpcval('Nom 1', "string"),
	 *             'emailAddress' => new xmlrpcval('prenom.nom2@mail.com', "string"),
	 *             'phoneNum' => new xmlrpcval('+33123456789', "string")
	 *         )
	 *     );
	 *
	 * @param array  $lstFiles       : Liste du/des fichiers à signer, chaque fichier doit définir:
	 *                                   - name : Nom du fichier à signer
	 *                                   - content : Contenu du fichier à signer encodé en base64
     *                                   - signatureFields : Objet contenant les informations des cartouches de signature
	 * @param array  $lstMembers     : Liste des signataires, chaque signataire doit définir:
	 *                                   - firstName : Le prénom du signataire
	 *                                   - lastName : Le nom du signataire
	 *                                   - emailAddress : L'email du signataire (ou un id si c'est en mode Iframe)
	 *                                   - phoneNum : Le numéro de téléphone du signataire (indicatif requis, exemple: +33612326554)
	 * @param array  $email          : Email envoyer aux utilisateur pour signer
	 *
	 * @param string				Redirection vers la page de signature, vrai ou faux
	 *
	 * @return array api response
	 */
    public function initProcedure(&$lstFiles, $lstMembers, $email = '', $redirectSign)
    {
		global $conf;

		$sendMail = "false";
        $sendMailAll ="false";
        $certificateType = "simple";
        $language = "fr";
		$handwritenSign = "1";
		$contactFirstSigner = true;
		$chainingMode = "email";

		//Définition de l'url de retour
		$returnUrl = (@$_SERVER["HTTPS"] == "on") ? "https://" : "http://";
		$returnUrl .= $_SERVER["SERVER_NAME"];
		$returnUrl .= $_SERVER["SCRIPT_NAME"] . "?id=" . $_REQUEST['id'] . "&action=doliesignsync";

		// Récupération d'une Url forcée
		if (! empty($conf->global->DOLIESIGN_RETURN_URL_UNIVERSIGN)) $returnUrl=$conf->global->DOLIESIGN_RETURN_URL_UNIVERSIGN;

        //Récupération des options de signature dans config
        if (! empty($conf->global->DOLIESIGN_SEND_MAIL_UNIVERSIGN)) $sendMail= $conf->global->DOLIESIGN_SEND_MAIL_UNIVERSIGN;
        if (! empty($conf->global->DOLIESIGN_SEND_MAIL_ALL_UNIVERSIGN)) $sendMailAll= $conf->global->DOLIESIGN_SEND_MAIL_ALL_UNIVERSIGN;
        if (! empty($conf->global->DOLIESIGN_CERTIFICATE_TYPE_UNIVERSIGN)) $certificateType= $conf->global->DOLIESIGN_CERTIFICATE_TYPE_UNIVERSIGN;
        if (! empty($conf->global->DOLIESIGN_LANGUAGE_UNIVERSIGN)) $language= $conf->global->DOLIESIGN_LANGUAGE_UNIVERSIGN;
        if (! empty($conf->global->DOLIESIGN_HANDWRITEN_SIGN_UNIVERSIGN)) $handwritenSign= $conf->global->DOLIESIGN_HANDWRITEN_SIGN_UNIVERSIGN;

		//Vérification si le mail personalisé existe
		if (! empty($email) || $redirectSign == 'true') {
			$contactFirstSigner = false;
			$chainingMode = 'none';
		}

		//Constitution de la liste des emails en copie une fois le document signé
		$lstCCEmail=array("demo@example.fr");

		if (! empty($conf->global->DOLIESIGN_SEND_CC_MAIL_LIST))
		{
		$lstCCEmail=array();
		array_push($lstCCEmail,new xmlrpcval($conf->global->DOLIESIGN_SEND_CC_MAIL_LIST, "string"));
		}

        // Création de la requette avec toutes les informations
        $request = array(
            "documents" => new xmlrpcval($lstFiles, "array"),
            "signers" => new xmlrpcval($lstMembers, "array"),

            //contact du premier signataire par mail
            "mustContactFirstSigner" => new xmlrpcval($contactFirstSigner, "boolean"),

            //contact des autres signataires par mail
            "chainingMode" => new xmlrpcval($chainingMode, "string"),

            //Envoi des dossier par mail à la fin de la signature
			"finalDocRequesterSent" => new xmlrpcval($sendMail,"boolean"),

			//Envoi des dossier par mail à la fin de la signature à tout les signataires
			"finalDocSent" => new xmlrpcval($sendMailAll,"boolean"),

			//Envoi des fichiers signés via la fonctionnalité cc à la liste des emails
			"finalDocCCeMails" => new xmlrpcval($lstCCEmail, "array"),

            //Type de certificat utilisé
            "certificateType" => new xmlrpcval($certificateType, "string"),

			//Url de retour
			"successURL" => new xmlrpcval($returnUrl, "string"),

            //Langage choisi (français ou anglais)
            "language" => new xmlrpcval($language, "string"),

            //Signature écrite a la main (0 pas de signature manuscrite, 1 signature masucrite, 2 signature manuscrite si saisi tactile)
            "handwrittenSignatureMode" => new xmlrpcval($handwritenSign, "int")
        );

        //Création de la transaction
        $transaction = new xmlrpcmsg('requester.requestTransaction', array(new xmlrpcval($request, "struct")));

        //Active le mode Débug
		$this->client->setDebug(0);

		//Vérifie le certificat de l'hôte qui reçois la requette(0 aucune vérification, 1 vérification si le certificat existe, 2 verifie si le certificat existe et si il correspond à l'url)
		$this->client->setSSLVerifyHost(2);
		$this->client->setSSLVerifyPeer(2);

        //Envoi la requette
		$result = &$this->client->send($transaction);

        if($result->faultCode()) {
            //Requette échoué
            $this->errors[] = "DoliEsignApiError";
            $this->errors[] = $result->faultCode();
            $this->errors[] = $result->faultString();
        } else {
			return $result;
		}
    }
    /**
     * Initialise la signature avec Universign
     *
     * @param object    $user           L'utilisateur qui initialise la signature
     * @param object    $object         Objet Dolibarr à signer
	 * @param string	$dir			chemin du document contenant le fichier
	 * @param string	$emailInitTemplate	Template type du mail de requette
	 * @param string	$emailEndTemplate	Template type du mail de confirmation
	 * @param string	$redirectSign	Redirection vers la page de signature, vrai ou faux
     */
    function signInit($user, $object, $dir, $emailInitTemplate = '', $emailEndTemplate = '', $redirectSign = 'false')
    {
		//Déclarations des prérequis
		require_once TCPDF_PATH.'/tcpdf.php';	//Requis pour fpdi
		require_once TCPDI_PATH.'/tcpdi.php';     //fpdi pour compter les pages
		require_once DOL_DOCUMENT_ROOT .'/core/class/html.formmail.class.php'; //Pour envoyer les mails

		//Définition des variables globales
		global $mysoc,$conf,$langs;

		//Définition des variables utilisé par cette fonction
		$listPerson = array();
		$listId = array();
		$coordinates = array();
		$errors = array();
		$memberEmail ='';
		$formMail = new FormMail($object->db);
		$initTemplate = new stdClass;
		$endTemplate = new stdClass;
		$error = 0;
		if (empty($redirectSign)) $redirectSign = "false";

        // vérifie si la connexion avec l'API existe déjà sinon se connecte
        if (! $this->isAuthenticated()) {
			$this->signConnect();
		}
        //Si la connexion n'existe toujours pas une erreur
		if (! $this->isAuthenticated()) {
			return --$error;
        }

		//Récupération des constantes
		if (! empty($conf->global->DOLIESIGN_CGV_REL_PATH)) $cgvPath=$conf->global->DOLIESIGN_CGV_REL_PATH;
		if (! empty($conf->global->DOLIESIGN_CGV_FILENAME)) $cgvFilename=$conf->global->DOLIESIGN_CGV_FILENAME;

		//Vérifie la langue utilisé par dolibarr
		if (! empty($object->thirdparty->default_lang)) {
			$outputLang = new stdClass;
			$outputLang->defaultlang = $object->thirdparty->default_lang;
		} else {
			$outputLang = $langs;
		}

		//Si aucune langue détecté on assigne une langue par défaut
		$defaultLang = new stdClass;
		$defaultLang->defaultlang = 'fr_FR';

		//Récupération des différentes DoliEsignConfig
		$config = new DoliEsignConfig($object->db);
		$configIds = array_reverse($config->fetchListId($object->element));
		$typeContacts = $config->get_type_contact_code($object->element, 'all');
		$sourceContacts = $config->get_source_contact_code($object->element);

		//Récupération et formatage des informations
		if (is_object($object) && is_array($configIds)) {

			//Récupère le fichier pdf a signer
			$dolDirList = dol_dir_list($dir);
			foreach($dolDirList as $dolDir) {
				if ($dolDir['type'] == 'file' && basename($dolDir['name'],'.pdf') == $object->ref) {
					$file = $dolDir['fullname'];
				}
			}
			$pdf = new TCPDI();

			//Vérifie si le fichier existe
			if (dol_is_file($file)) {
				//Récupère le nombre de page du fichier
				$indexFile = 0;
				$pageCount = $pdf->setSourceFile($file);
			} else {
				$this->errors[] = "DoliEsignFileMissing";
				return --$error;
			}

			$cgv_pdf=DOL_DATA_ROOT.$cgvPath."/".$cgvFilename;

			//Vérifie si le fichier CGV existe sinon met son numéro de page à 0
			if (dol_is_file($cgv_pdf)) {
				$cgvPageCount = $pdf->setSourceFile($cgv_pdf);
			} else {
				$cgvPageCount = 0;
			}

			$indexMember = 0;

			//Parcourt les ID de configuration
			foreach($configIds as $configId) {

				//Récupére les configurations disponible
				$res = $config->fetch($configId);

				//Si le nombre de configuration est en dessous de 0 retourne une erreur
				if ($res < 0) {
					$this->errors = $config->errors;
					return --$error;
				}

				//Si il est égal à 0 il n'y a aucune configurations
				if ($res == 0) {
					$this->errors[] = "DoliEsignContactMissing";
					return --$error;
				}

				//Vérifie si le fichier CGV a plus d'une page
				if ($cgvPageCount > 0) {
					//Si les coordonées de signature du fichier CGV son vide pas de signature sur celui ci
					if(empty($config->cgv_sign_coordinate)) {
						$cgvPageCount = 0;
					}
				}

				//On réinitialise les user et contacts
				$userIds = null;
				$contactIds = null;

				//Récupère les informations du contact et de l'utilisateur
				$contactCode = $typeContacts[$config->fk_c_type_contact];
				$contactSource = $sourceContacts[$config->fk_c_type_contact];
				if ($contactSource == "internal") {
					$userIds = $object->getIdContact('internal', $contactCode);
				}
				else if ($contactSource == "external") {
					$contactIds = $object->getIdContact('external', $contactCode);
				}

				//Définition des coordonées de signature
				$coordinates = explode(",", $config->sign_coordinate);
				$cgvCoordinates = explode(",", $config->cgv_sign_coordinate);

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
							$signatureName = 'sign_external [' . sprintf("%03d", $indexMember + 1) . ']';
							$listMembers[] = new xmlrpcval(
								array (
									'firstname' => new xmlrpcval($contact->firstname, "string"),
									'lastname' => new xmlrpcval($contact->lastname, "string"),
									'emailAddress' => new xmlrpcval($contact->email, "string"),
									'phoneNum' => new xmlrpcval($contact->phone_mobile, "string")
							), "struct");
							$signatureFieldsDoc[] = new xmlrpcval(
								array (
									'page' => new xmlrpcval($pageCount, "int"),
									'name' =>new xmlrpcval($signatureName, "string"),
									'x' => new xmlrpcval($coordinates[0], "int"),
									'y' => new xmlrpcval($coordinates[1], "int"),
									'signerIndex' => new xmlrpcval($indexMember,"int")
							), "struct");
							if ($cgvPageCount > 0) {
								$signatureFieldsDocCGV[] = new xmlrpcval(
									array (
										'page' => new xmlrpcval($cgvPageCount, "int"),
										'x' => new xmlrpcval($cgvCoordinates[0], "int"),
										'y' => new xmlrpcval($cgvCoordinates[1], "int"),
										'signerIndex' => new xmlrpcval($indexMember, "int")
								), "struct");
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
							$signatureName = 'sign_internal [' . sprintf("%03d", $indexMember + 1) . ']';
							$listMembers[] = new xmlrpcval(
								array (
									'firstname' => new xmlrpcval($user->firstname, "string"),
									'lastname' => new xmlrpcval($user->lastname, "string"),
									'emailAddress' => new xmlrpcval($user->email, "string"),
									'phoneNum' => new xmlrpcval($user->phone_mobile, "string")
							), "struct");
							$signatureFieldsDoc[] = new xmlrpcval(
								array(
									'page' => new xmlrpcval($pageCount, "int"),
									'name' =>new xmlrpcval($signatureName, "string"),
									'x' => new xmlrpcval($coordinates[0], "int"),
									'y' => new xmlrpcval($coordinates[1], "int"),
									'signerIndex' => new xmlrpcval($indexMember,"int")
							), "struct");
							if ($cgvPageCount > 0) {
								$signatureFieldsDocCGV[] = new xmlrpcval(
									array(
										'page' => new xmlrpcval($cgvPageCount, "int"),
										'x' => new xmlrpcval($cgvCoordinates[0], "int"),
										'y' => new xmlrpcval($cgvCoordinates[1], "int"),
										'signerIndex' => new xmlrpcval($indexMember, "int")
								), "struct");
							}
							$indexMember++;
						}
					}
				}
			}

			if (dol_is_file($cgv_pdf)) {
				//Création de listFiles avec les informations du fichier et les CGV
				$listFiles[] = new xmlrpcval(
					array (
						'name' => new xmlrpcval(basename($file),"string"),
						'content' => new xmlrpcval(file_get_contents($file),"base64"),
						'signatureFields' => new xmlrpcval($signatureFieldsDoc, "array")
					), "struct");
					if ($signatureFieldsDocCGV !== null) {
						$listFiles[] = new xmlrpcval(
							array (
								'name' => new xmlrpcval(basename($cgv_pdf),"string"),
								'content' => new xmlrpcval(file_get_contents($cgv_pdf),"base64"),
								'signatureFields' => new xmlrpcval($signatureFieldsDocCGV, "array")
						), "struct");
					}
			} else {
				//Création de listFiles avec les informations du fichier sans les CGV
				$listFiles = array(new xmlrpcval(
					array (
						'name' => new xmlrpcval(basename($file),"string"),
						'content' => new xmlrpcval(file_get_contents($file),"base64"),
						'signatureFields' => new xmlrpcval($signatureFieldsDoc, "array")
					), "struct"));
			}
		}

		//Aucun contact trouvé
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

		// if (DoliEsign::checkDolVersion('6.0')) {
		// 	$substitutionarray=getCommonSubstitutionArray($langs, 0, null, $object);
		// } else {
			$substitutionarray=doliEsignGetCommonSubstitutionArray($langs, 0, null, $object);
		// }

		$initMailSubject = make_substitutions($initTemplate->topic, $substitutionarray);
		$initMailSubst = make_substitutions($initTemplate->content, $substitutionarray);

		$logosmall=$mysoc->logo_small;
		if (! empty($logosmall) && is_readable($conf->mycompany->dir_output.'/logos/thumbs/'.$logosmall))
		{
			$logo=file_get_contents($conf->mycompany->dir_output.'/logos/thumbs/'.$logosmall);
			$initMail = preg_replace('/__LOGO__/','<img src="data:image/png;base64,'.base64_encode($logo).'"></img>',$initMailSubst);
		} else {
			$initMail = preg_replace('/__LOGO__/','',$initMailSubst);
		}

		//Appel de la fonction initProcedure pour envoyer la requette
		$response = $this->initProcedure($listFiles, $listMembers, $initMail, $redirectSign);

		//Si il y a une erreur elle est retourner
		if(count($this->errors) > 0) {
			return --$error;
		}

		//Sinon on créer une entrée dans la base de donnée
		else
		{
			$urlReplace = 'https://ws.universign.eu/sig/#/?id=';
			if ($this->urlApi = 'sign.test.cryptolog.com/sign/rpc/') $urlReplace = 'https://sign.test.universign.eu/sig/#/?id=';

			//On récupère les signataire
			$requestSign = new xmlrpcmsg('requester.getTransactionInfo', array(new xmlrpcval($response->value()->structMem('id')->scalarVal(), "string")));
			$info = &$this->client->send($requestSign);
			$signers = $info->value()->structMem('signerInfos')->scalarVal();
			foreach($signers as $key => $signer) {
				//Récupération de l'url pour le mail
				$contentMail = str_replace('{yousignUrl}', '<br><br><a href="'. $signer->me['struct']['url']->me['string'] .'" rel="notrack" style="background:#085e7e; margin:0; border:0 solid #085e7e; color:#fefefe; display:inline-block; font-family:Helvetica,Arial,sans-serif; font-size:14px; font-weight:bold; line-height:1.3; margin:0; padding:12px 24px 12px 24px; text-align:left; text-decoration:none" target="_blank">Accéder
				aux documents</a><br>', $initMail);
				$contentMail = str_replace('__CONTACTCIVNAME__', $signer->me['struct']['firstName']->me['string'] . ' ' . $signer->me['struct']['lastName']->me['string'] , $contentMail);

				//Si la redirection vers la page de signature automatique est activé on n'envoi pas de mail au premier signataire
				if ($redirectSign == 'false' || ($redirectSign == 'true' && $key != 0)) {
					//Création du mail personalisé et envoi de celui ci
					$mailSign = new CMailFile(
						$initMailSubject,
						$signer->me['struct']['email']->me['string'],
						//Ajout de la possiblité de forcer un email autre que celui des notifications natives dolibarr
						empty($conf->global->DOLIESIGN_SEND_MAIL_FROM_ADDRESS) ? $conf->notification->email_from : $conf->global->DOLIESIGN_SEND_MAIL_FROM_ADDRESS,
						$contentMail,
						array(),
						array(),
						array(),
						'',
						'',
						0,
						1
					);

					$mailSign->sendFile();
				}
				else {
					$redirectionURL = $signer->me['struct']['url']->me['string'];
				}

				//Création d'un objet DoliEsign
				$this->ref = str_replace($urlReplace, '', $signer->me['struct']['url']->me['string']);
				$this->id_file = '';
				$this->hash_file = '';
				$this->path_file = $file;
				$this->sign_id = $response->value()->structMem('id')->scalarVal();
				$this->sign_status = $signer->me['struct']['status']->me['string'];
				$this->status = DoliEsign::STATUS_WAITING;
				$dateCreate = new DateTime($info->value()->structMem('creationDate')->scalarVal());
				$this->date_creation = $dateCreate->getTimestamp();
				$this->fk_object = $object->id;
				$this->object_type = $object->element;
				if ($listId[$key]->type == 'contact') $this->fk_contact_sign = $listId[$key]->id;
				if ($listId[$key]->type == 'user') $this->fk_user_sign = $listId[$key]->id;
				$this->fk_user_creat = $user->id;
				$this->fk_soc = $object->socid;
				$this->api_name = 'Universign';
				$this->date_sign = null;
				$res = $this->create($user);
				if ($res < 0) {
					$this->errors = $this->errors;
					return $res;
				} else {
					//$this->createEvent($user, $object);
				}
			}
			if ($redirectSign == "true") header("Location: ".$redirectionURL);
			return 0;
		}
	}
	/**
	 * Récupére l'avancement de la signature
	 *
	 * @param object	$user 				Utilisateur qui récupère les infos
	 * @param object	$object				L'objet Dolibarr
	 * @param string	$mode				'sync' ou 'type d'objet dolibarr'
	 *
	 * @return int avec null = aucune info ou l'état de la signature
	 */
	function signInfo($user, $object, $mode='sync')
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

					//Vérification de la connexion si non connecté se connecte
					if (! $this->isAuthenticated()) {
						$this->signConnect();
					}

					//Si il n'est toujours pas connecter retourne une erreur
					if (! $this->isAuthenticated()) {
						return --$error;
					}

					//Création de la requette
					$request = new xmlrpcmsg('requester.getTransactionInfo', array(new xmlrpcval($token->idDemand, "string")));

					//Envoi de la requette
					$result = &$this->client->send($request);

					//Vérification si il n'y a pas eu de code d'erreur
					if ($result->faultCode()) {
						$this->errors[] = "DoliEsignApiError";
						$this->errors[] = $result->faultString();

					} else {
						//On récupère le statut de la signature et l'id du dernier signataire
						$response = $result->value()->structMem('status')->scalarVal();
						$members = $result->value()->structMem('signerInfos')->scalarVal();
						$creationDate = $result->value()->structMem('creationDate')->scalarVal();

						$res = $this->fetch($token->id);
						if ($res < 0) {
							return $res;
						} else {
							if ($response == 'ready') {
								$this->status = DoliEsign::STATUS_WAITING;
							} else if ($response == 'completed') {
								$this->status = DoliEsign::STATUS_SIGNED;
							} else if ($response == 'canceled' || $response == 'expired') {
								$this->status = DoliEsign::STATUS_CANCELED;
							} else {
								$this->status = DoliEsign::STATUS_ERROR;
							}
							$dateCreate = new DateTime($creationDate);
							$this->date_creation = $dateCreate->getTimestamp();
							$dateCreate = null;

							foreach($members as $member) {
								if (!empty($member)) {
									$dateSign = new DateTime($member->me['struct']['actionDate']->me['string']);
									$this->sign_status = $member->me['struct']['status']->me['string'];
								}
							}

							if (!empty($dateSign)) {
								$this->date_sign = $dateSign->getTimestamp();
								$dateSign = null;
							}

							$status = $this->status;
							$this->fk_user_modif=$user->id;
							$res = $this->update($user);
							if ($res < 0) {
								return $res;
							}
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
	 * Annule la signature
	 *
	 * @param object	$user 				L'utilisateur qui a arreté la signature
	 *
	 * @return int 0 = OK, < 0 if NOK
	 */
	function signCancel($user)
	{
		$error = 0;
		$idDemand = $this->sign_id;

		if (! empty($idDemand)) {

			//Vérification de la connexion et connexion si aucune connexion
			if (! $this->isAuthenticated()) {
				$this->signConnect();
			}

			//Si toujours aucune connexion retourne une erreur
			if (! $this->isAuthenticated()) {
				return --$error;
			}

			//Création de la requette
			$request = new xmlrpcmsg('requester.cancelTransaction', array(new xmlrpcval($idDemand, "string")));

			//Envoi de la requette
			$result = &$this->client->send($request);

			//Si il a eu une erreur on la retourne sinon on rentre le statut annuler dans la bdd
			if ($result->faultCode()) {
				$this->errors[] = 'DoliEsignApiError';
				$this->errors[] = $result->faultString();
				return --$error;
			} else {
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
	 * Récupére les documents signé
	 *
	 * @param object	$user 				L'utilisateur qui récupère les documents
	 * @param object	$object				L'objet Dolibarr
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
					} elseif (! empty($token->idDemand)) {
						if (! $this->isAuthenticated()) {
							$this->signConnect();
						}
						if (! $this->isAuthenticated()) {
							return --$error;
						}

						//Création de la requette
						$request = new xmlrpcmsg('requester.getDocuments', array(new xmlrpcval($token->idDemand, "string")));

						//Envoi de la requette
						$result = &$this->client->send($request);
						if ($result->faultCode()) {
							$this->errors[] = 'DoliEsignApiError';
							$this->errors[] = $result->faultString();
							return --$error;
						} else {
							for($i = 0; $i < $result->value()->arraySize(); $i++) {
								if (strpos($this->path_file, $result->value()->arrayMem($i)->structMem('fileName')->scalarVal()) !== false) {
									$file = str_replace(".pdf", "_doliesign.pdf", $this->path_file);
									$res = file_put_contents($file, $result->value()->arrayMem($i)->structMem('content')->scalarVal());
								}
								else {
									$file = str_replace($result->value()->arrayMem($i -1)->structMem('fileName')->scalarVal(), "CGV.pdf", $this->path_file);
									$res = file_put_contents($file, $result->value()->arrayMem($i)->structMem('content')->scalarVal());
								}
							}
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
