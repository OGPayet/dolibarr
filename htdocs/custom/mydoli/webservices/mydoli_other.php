<?php
/* Copyright (C) 2006-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**
 *       \file       htdocs/webservices/server_other.php
 *       \brief      File that is entry point to call Dolibarr WebServices
 */

if (! defined('NOCSRFCHECK')) define('NOCSRFCHECK','1');

// This is to make Dolibarr working with Plesk
set_include_path($_SERVER['DOCUMENT_ROOT'].'/htdocs');
if(is_file('../main.inc.php')) $dir = '../';
	else  if(is_file('../../../main.inc.php')) $dir = '../../../';
	else $dir = '../../';

require_once $dir.'master.inc.php';
require_once NUSOAP_PATH.'/nusoap.php';        // Include SOAP
require_once DOL_DOCUMENT_ROOT.'/core/lib/ws.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

dol_syslog("Call Dolibarr webservices interfaces");

$langs->load("main");

// Enable and test if module web services is enabled
if (empty($conf->global->MAIN_MODULE_WEBSERVICES))
{
	$langs->load("admin");
	dol_syslog("Call Dolibarr webservices interfaces with module webservices disabled");
	print $langs->trans("WarningModuleNotActive",'WebServices').'.<br><br>';
	print $langs->trans("ToActivateModule");
	exit;
}

// Create the soap Object
$server = new nusoap_server();
$server->soap_defencoding='UTF-8';
$server->decode_utf8=false;
$ns='http://www.dolibarr.org/ns/';
$server->configureWSDL('WebServicesDolibarrOther',$ns);
$server->wsdl->schemaTargetNamespace=$ns;


// Define WSDL Authentication object
$server->wsdl->addComplexType(
    'authentication',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'dolibarrkey' => array('name'=>'dolibarrkey','type'=>'xsd:string'),
	'sourceapplication' => array('name'=>'sourceapplication','type'=>'xsd:string'),
	'login' => array('name'=>'login','type'=>'xsd:string'),
        'password' => array('name'=>'password','type'=>'xsd:string'),
        'entity' => array('name'=>'entity','type'=>'xsd:string'),
    )
);
// Define WSDL Return object
$server->wsdl->addComplexType(
    'result',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'result_code' => array('name'=>'result_code','type'=>'xsd:string'),
        'result_label' => array('name'=>'result_label','type'=>'xsd:string'),
    )
);

// Define WSDL Return object for document
$server->wsdl->addComplexType(
	'document',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'filename' => array('name'=>'filename','type'=>'xsd:string'),
		'mimetype' => array('name'=>'mimetype','type'=>'xsd:string'),
		'content' => array('name'=>'content','type'=>'xsd:string'),
		'length' => array('name'=>'length','type'=>'xsd:string')
	)
);
$server->wsdl->addComplexType(
	'documentArray2',
	'complexType',
	'array',
	'sequence',
	'',
	array(
		'document' => array(
		'name' => 'document',
		'type' => 'tns:document',
		'minOccurs' => '0',
		'maxOccurs' => 'unbounded'
	)
	)
);
// Define WSDL Return object for bankAccount
$server->wsdl->addComplexType(
	'bankAccount',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'id' => array('name'=>'id','type'=>'xsd:int'),
		'ref' => array('name'=>'ref','type'=>'xsd:string'),
		'label' => array('name'=>'label','type'=>'xsd:string'),
		'current' => array('name'=>'current','type'=>'xsd:int')
	)
);
$server->wsdl->addComplexType(
	'bankAccountArray2',
	'complexType',
	'array',
	'sequence',
	'',
	array(
		'bankAccount' => array(
		'name' => 'bankAccount',
		'type' => 'tns:bankAccount',
		'minOccurs' => '0',
		'maxOccurs' => 'unbounded'
	)
	)
);

// Define WSDL Return object for paiementModes
$server->wsdl->addComplexType(
	'paiementMode',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'id' => array('name'=>'id','type'=>'xsd:int'),
		'code' => array('name'=>'code','type'=>'xsd:string'),
		'label' => array('name'=>'label','type'=>'xsd:string')
	)
);
$server->wsdl->addComplexType(
	'paiementModeArray2',
	'complexType',
	'array',
	'sequence',
	'',
	array(
		'paiementMode' => array(
		'name' => 'paiementMode',
		'type' => 'tns:paiementMode',
		'minOccurs' => '0',
		'maxOccurs' => 'unbounded'
	)
	)
);

// Define WSDL Return object for tvaRates
$server->wsdl->addComplexType(
	'tvaRate',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'rate' => array('name'=>'rate','type'=>'xsd:double')
	)
);
$server->wsdl->addComplexType(
	'tvaRateArray2',
	'complexType',
	'array',
	'sequence',
	'',
	array(
		'tvaRate' => array(
		'name' => 'tvaRate',
		'type' => 'tns:tvaRate',
		'minOccurs' => '0',
		'maxOccurs' => 'unbounded'
	)
	)
);
// Define WSDL Return object for users
$server->wsdl->addComplexType(
	'user',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'id' => array('name'=>'id','type'=>'xsd:int'),
		'login' => array('name'=>'login','type'=>'xsd:string'),
		'name' => array('name'=>'name','type'=>'xsd:string'),
		'mobile' => array('name'=>'mobile','type'=>'xsd:string')
	)
);
$server->wsdl->addComplexType(
	'userArray2',
	'complexType',
	'array',
	'sequence',
	'',
	array(
		'user' => array(
		'name' => 'user',
		'type' => 'tns:user',
		'minOccurs' => '0',
		'maxOccurs' => 'unbounded'
	)
	)
);

// Define WSDL Return object for paiementModes
$server->wsdl->addComplexType(
	'typeRendezVous',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'id' => array('name'=>'id','type'=>'xsd:int'),
		'code' => array('name'=>'code','type'=>'xsd:string'),
		'label' => array('name'=>'label','type'=>'xsd:string'),
		'pos' => array('name'=>'id','type'=>'xsd:int'),
	)
);
$server->wsdl->addComplexType(
	'typeRendezVousArray2',
	'complexType',
	'array',
	'sequence',
	'',
	array(
		'typeRendezVous' => array(
		'name' => 'typeRendezVous',
		'type' => 'tns:typeRendezVous',
		'minOccurs' => '0',
		'maxOccurs' => 'unbounded'
	)
	)
);

$server->wsdl->addComplexType(
	'companie',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'id' => array('name'=>'id','type'=>'xsd:int'),
		'name' => array('name'=>'code','type'=>'xsd:string')
	)
);
$server->wsdl->addComplexType(
	'companiesArray2',
	'complexType',
	'array',
	'sequence',
	'',
	array(
		'companie' => array(
		'name' => 'companie',
		'type' => 'tns:companie',
		'minOccurs' => '0',
		'maxOccurs' => 'unbounded'
	)
	)
);
// Define other specific objects
// None


// 5 styles: RPC/encoded, RPC/literal, Document/encoded (not WS-I compliant), Document/literal, Document/literal wrapped
// Style merely dictates how to translate a WSDL binding to a SOAP message. Nothing more. You can use either style with any programming model.
// http://www.ibm.com/developerworks/webservices/library/ws-whichwsdl/
$styledoc='rpc';       // rpc/document (document is an extend into SOAP 1.0 to support unstructured messages)
$styleuse='encoded';   // encoded/literal/literal wrapped
// Better choice is document/literal wrapped but literal wrapped not supported by nusoap.

// Register WSDL
$server->register(
    'connect',
    // Entry values
    array('authentication'=>'tns:authentication'),
    // Exit values
    array('result'=>'tns:result','admin'=>'xsd:int','name'=>'xsd:string','version'=>'xsd:string','versionModule'=>'xsd:string','companies'=>'tns:companiesArray2'),
    $ns,
    $ns.'#connect',
    $styledoc,
    $styleuse,
    'WS to connect'
);
// Register WSDL
$server->register(
    'getVersions',
    // Entry values
    array('authentication'=>'tns:authentication'),
    // Exit values
    array('result'=>'tns:result','dolibarr'=>'xsd:string','os'=>'xsd:string','php'=>'xsd:string','webserver'=>'xsd:string','societe'=>'xsd:string','adresse'=>'xsd:string','codepostal'=>'xsd:string','ville'=>'xsd:string','tel'=>'xsd:string','fax'=>'xsd:string','email'=>'xsd:string','web'=>'xsd:string','logo'=>'xsd:string','country_id'=>'xsd:string'),
    $ns,
    $ns.'#getVersions',
    $styledoc,
    $styleuse,
    'WS to get Versions'
);

// Register WSDL
$server->register(
	'getDocument',
	// Entry values
	array('authentication'=>'tns:authentication', 'modulepart'=>'xsd:string', 'file'=>'xsd:string' ),
	// Exit values
	array('result'=>'tns:result','document'=>'tns:document'),
	$ns,
	$ns.'#getDocument',
	$styledoc,
	$styleuse,
	'WS to get a document'
);
$server->register(
	'getAllDocuments',
	// Entry values
	array('authentication'=>'tns:authentication', 'modulepart'=>'xsd:string', 'idthirdparty'=>'xsd:string' ),
	// Exit values
	array('result'=>'tns:result','documents'=>'tns:documentArray2'),
	$ns,
	$ns.'#getAllDocuments',
	$styledoc,
	$styleuse,
	'WS to get the list of documents from a thirdparty'
);
$server->register(
	'deleteDocument',
	// Entry values
	array('authentication'=>'tns:authentication', 'modulepart'=>'xsd:string', 'idthirdparty'=>'xsd:string', 'file'=>'xsd:string' ),
	// Exit values
	array('result'=>'tns:result','document'=>'tns:document'),
	$ns,
	$ns.'#deleteDocument',
	$styledoc,
	$styleuse,
	'WS to delete a document'
);

// Register WSDL
$server->register(
	'sendDocument',
	// Entry values
	array('authentication'=>'tns:authentication', 'modulepart'=>'xsd:string', 'idthirdparty'=>'xsd:string', 'file'=>'xsd:string', 'content'=>'xsd:string' ),
	// Exit values
	array('result'=>'tns:result','path'=>'xsd:string'),
	$ns,
	$ns.'#sendDocument',
	$styledoc,
	$styleuse,
	'WS to send a document to a piece/tiers'
);

$server->register(
	'getBankAccounts',
	// Entry values
	array('authentication'=>'tns:authentication'),
	// Exit values
	array('result'=>'tns:result','bankAccounts'=>'tns:bankAccountArray2'),
	$ns,
	$ns.'#getBankAccounts',
	$styledoc,
	$styleuse,
	'WS to get bank accounts'
);

$server->register(
	'getPaiementModes',
	// Entry values
	array('authentication'=>'tns:authentication'),
	// Exit values
	array('result'=>'tns:result','paiementModes'=>'tns:paiementModeArray2'),
	$ns,
	$ns.'#getPaiementModes',
	$styledoc,
	$styleuse,
	'WS to get paiements modes'
);
$server->register(
	'getCountryVatRates',
	// Entry values
	array('authentication'=>'tns:authentication'),
	// Exit values
	array('result'=>'tns:result','tvaRates'=>'tns:tvaRateArray2'),
	$ns,
	$ns.'#getCountryVatRates',
	$styledoc,
	$styleuse,
	'WS to get VAT rates vs Counrty'
);
$server->register(
	'getUsers',
	// Entry values
	array('authentication'=>'tns:authentication'),
	// Exit values
	array('result'=>'tns:result','users'=>'tns:userArray2'),
	$ns,
	$ns.'#getUsers',
	$styledoc,
	$styleuse,
	'WS to get users list'
);
$server->register(
	'getTypesRendezVous',
	// Entry values
	array('authentication'=>'tns:authentication'),
	// Exit values
	array('result'=>'tns:result','typesRendezVous'=>'tns:typeRendezVousArray2'),
	$ns,
	$ns.'#getTypesRendezVous',
	$styledoc,
	$styleuse,
	'WS to get types of rendez-vous'
);

// Full methods code
function connect($authentication)
{
	global $db,$conf,$langs,$mysoc;

	dol_syslog("Function: connect login=".$authentication['login']);

	if ($authentication['entity'])
	{
		$conf->entity=$authentication['entity'];
		$conf->setValues($db);
		$mysoc->setMysoc($conf);
	}

    // Init and check authentication
    $objectresp=array();
    $errorcode='';$errorlabel='';
    $error=0;
    $fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);
    // Check parameters

    if (! $error)
	{
		$objectresp['result']=array('result_code'=>'OK', 'result_label'=>'');
		$objectresp['admin']=$fuser->admin;
		$objectresp['name']=$fuser->firstname." ".$fuser->lastname;
		$objectresp['version']="1.0.160614";
		$objectresp['versionModule']="1.5.4.0";

		$sql = "SELECT rowid, label,visible,active FROM ".MAIN_DB_PREFIX."entity WHERE visible=1 AND active=1 ORDER BY rowid";

		$resql=$db->query($sql);
		if ($resql)
		{
			$lines=array();
			$num=$db->num_rows($resql);
			$i=0;
			while ($i < $num)
			{
				// En attendant remplissage par boucle
				$obj=$db->fetch_object($resql);
				$lines[]=array(
				'id' => $obj->rowid,
				'name' => $obj->label
				);

				$i++;
			}
			$objectresp['companies']=$lines;
		}

	}

	if ($error)
	{
		$objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
	}

	return $objectresp;
}

function getVersions($authentication)
{
	global $db,$conf,$langs,$mysoc;

	dol_syslog("Function: getVersions login=".$authentication['login']);

	if ($authentication['entity'])
	{
		$conf->entity=$authentication['entity'];
		$conf->setValues($db);
		$mysoc->setMysoc($conf);
	}
    // Init and check authentication
    $objectresp=array();
    $errorcode='';$errorlabel='';
    $error=0;
    $fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);
    // Check parameters


    if (! $error)
	{
		$objectresp['result']=array('result_code'=>'OK', 'result_label'=>'');
		$objectresp['dolibarr']=version_dolibarr();
		$objectresp['php']=phpversion();
		$objectresp['societe']=$conf->global->MAIN_INFO_SOCIETE_NOM;
		$objectresp['adresse']=$conf->global->MAIN_INFO_SOCIETE_ADDRESS;
		$objectresp['codepostal']=$conf->global->MAIN_INFO_SOCIETE_ZIP;
		$objectresp['ville']=$conf->global->MAIN_INFO_SOCIETE_TOWN;
		$objectresp['tel']=$conf->global->MAIN_INFO_SOCIETE_TEL;
		$objectresp['fax']=$conf->global->MAIN_INFO_SOCIETE_FAX;
		$objectresp['email']=$conf->global->MAIN_INFO_SOCIETE_MAIL;
		$objectresp['web']=$conf->global->MAIN_INFO_SOCIETE_WEB;
		$objectresp['logo']=$conf->global->MAIN_INFO_SOCIETE_LOGO;
		$objectresp['country_id']=$conf->global->MAIN_INFO_SOCIETE_COUNTRY;
	}

	if ($error)
	{
		$objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
	}

	return $objectresp;
}


/**
 * Method to get a document by webservice
 *
 * @param 	array	$authentication		Array with permissions
 * @param 	string	$modulepart		 	Properties of document
 * @param	string	$file				Relative path
 * @param	string	$refname			Ref of object to check permission for external users (autodetect if not provided)
 * @return	void
 */
function getDocument($authentication, $modulepart, $file, $refname='')
{
	global $db,$conf,$langs,$mysoc;

	dol_syslog("Function: getDocument login=".$authentication['login'].' - modulepart='.$modulepart.' - file='.$file);

	if ($authentication['entity'])
	{
		$conf->entity=$authentication['entity'];
		$conf->setValues($db);
		$mysoc->setMysoc($conf);
	}

	$objectresp=array();
	$errorcode='';$errorlabel='';
	$error=0;

	// Properties of doc
	$original_file = $file;
	$type=dol_mimetype($original_file);
	//$relativefilepath = $ref . "/";
	//$relativepath = $relativefilepath . $ref.'.pdf';

	$accessallowed=0;

	$fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);

	if ($fuser->societe_id) $socid=$fuser->societe_id;

	// Check parameters
	if (! $error && ( ! $file || ! $modulepart ) )
	{
		$error++;
		$errorcode='BAD_PARAMETERS'; $errorlabel="Parameter file and modulepart must be both provided.";
	}

	if (! $error)
	{
		$fuser->getrights();

		// Suppression de la chaine de caractere ../ dans $original_file
		$original_file = str_replace("../","/", $original_file);

		// find the subdirectory name as the reference
		if (empty($refname)) $refname=basename(dirname($original_file)."/");

		// Security check
		$check_access = dol_check_secure_access_document($modulepart,$original_file,$conf->entity,$fuser,$refname);
		$accessallowed              = $check_access['accessallowed'];
		$sqlprotectagainstexternals = $check_access['sqlprotectagainstexternals'];
		$original_file              = $check_access['original_file'];

		// Basic protection (against external users only)
		if ($fuser->societe_id > 0)
		{
			if ($sqlprotectagainstexternals)
			{
				$resql = $db->query($sqlprotectagainstexternals);
				if ($resql)
				{
					$num=$db->num_rows($resql);
					$i=0;
					while ($i < $num)
					{
						$obj = $db->fetch_object($resql);
						if ($fuser->societe_id != $obj->fk_soc)
						{
							$accessallowed=0;
							break;
						}
						$i++;
					}
				}
			}
		}

		// Security:
		// Limite acces si droits non corrects
		if (! $accessallowed)
		{
			$errorcode='NOT_PERMITTED';
			$errorlabel='Access not allowed';
			$error++;
		}

		// Security:
		// On interdit les remontees de repertoire ainsi que les pipe dans
		// les noms de fichiers.
		if (preg_match('/\.\./',$original_file) || preg_match('/[<>|]/',$original_file))
		{
			dol_syslog("Refused to deliver file ".$original_file);
			$errorcode='REFUSED';
			$errorlabel='';
			$error++;
		}

		clearstatcache();

		if(!$error)
		{
			if(file_exists($original_file))
			{
				dol_syslog("Function: getDocument $original_file $filename content-type=$type");

				$f = fopen($original_file,'r');
				$content_file = fread($f,filesize($original_file));

				$objectret = array(
					'filename' => basename($original_file),
					'mimetype' => dol_mimetype($original_file),
					'content' => base64_encode($content_file),
					'length' => filesize($original_file)
				);

				// Create return object
				$objectresp = array(
					'result'=>array('result_code'=>'OK', 'result_label'=>''),
					'document'=>$objectret
				);
			}
			else
			{
				dol_syslog("File doesn't exist ".$original_file);
				$errorcode='NOT_FOUND';
				$errorlabel=$original_file;
				$error++;
			}
		}
	}

	if ($error)
	{
		$objectresp = array(
		'result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel)
		);
	}

	return $objectresp;
}
function getAllDocuments($authentication, $modulepart, $idthirdparty)
{
	global $db,$conf,$langs,$mysoc;

	dol_syslog("Function: getDocument login=".$authentication['login'].' - modulepart='.$modulepart.' - id='.$idthirdparty);

	if ($authentication['entity'])
	{
		$conf->entity=$authentication['entity'];
		$conf->setValues($db);
		$mysoc->setMysoc($conf);
	}

	$objectresp=array();
	$errorcode='';$errorlabel='';
	$error=0;

	$fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);

	$object = new Societe($db);
	if ($idthirdparty > 0)
	{
		$result = $object->fetch($idthirdparty, "");

		$upload_dir = $conf->societe->multidir_output[$object->entity] . "/" . $object->id ;
		//$courrier_dir = $conf->societe->multidir_output[$object->entity] . "/courrier/" . get_exdir($object->id);
		$tabDocs=array();
		clearstatcache();
		//** boucle $file=fichiers du rep
		if ($dir = @opendir($upload_dir))
		{
			while (false !== ($file = readdir($dir)))
			{
			if ($file!='.' && $file!='..' && !is_dir($upload_dir."/".$file))
			{
				$original_file = $upload_dir."/".$file;
				$original_file = str_replace("../","/", $original_file);
				$refname=basename(dirname($original_file)."/");

				$check_access = dol_check_secure_access_document($modulepart,$original_file,$conf->entity,$fuser,$refname);
				$accessallowed              = $check_access['accessallowed'];
				$sqlprotectagainstexternals = $check_access['sqlprotectagainstexternals'];
				//$original_file              = $check_access['original_file'];

				if($accessallowed)
				{
					$thumb = pathinfo($original_file, PATHINFO_FILENAME);
					$ext = pathinfo($original_file, PATHINFO_EXTENSION);
					if(file_exists($upload_dir."/thumbs/".$thumb."_mini.".$ext))
					{
						$original_file=$upload_dir."/thumbs/".$thumb."_mini.".$ext;
					}
					$f = fopen($original_file,'r');
					$content_file = fread($f,filesize($original_file));

					$tabDocs[] = array(
						'filename' => basename($original_file),
						'mimetype' => dol_mimetype($original_file),
						'content' => base64_encode($content_file),
						'length' => filesize($original_file)
					);
				}
			}
			}
			@closedir($dir);
		}
		$objectresp=array(
				'result'=>array('result_code'=>'OK', 'result_label'=>''),
				'documents'=>$tabDocs
				);
	}
	else
	{
		$error++;
		$errorcode='BAD_PARAMETERS'; $errorlabel="Parameter id is provided.";
	}

	if ($error)
	{
		$objectresp = array(
		'result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel)
		);
	}

	return $objectresp;
}
function deleteDocument($authentication, $modulepart, $idthirdparty, $file, $refname='')
{
	global $db,$conf,$langs,$mysoc;

	dol_syslog("Function: deleteDocument login=".$authentication['login'].' - modulepart='.$modulepart.' - file='.$file);

	if ($authentication['entity'])
	{
		$conf->entity=$authentication['entity'];
		$conf->setValues($db);
		$mysoc->setMysoc($conf);
	}

	$objectresp=array();
	$errorcode='';$errorlabel='';
	$error=0;

	$object = new Societe($db);
	if ($idthirdparty > 0)
	{
		$result = $object->fetch($idthirdparty, "");

		$upload_dir = $conf->societe->multidir_output[$object->entity] . "/" . $object->id ;

		// Properties of doc
		//$original_file = $file;
		//$type=dol_mimetype($original_file);
		//$relativefilepath = $ref . "/";
		//$relativepath = $relativefilepath . $ref.'.pdf';

		$accessallowed=0;

		$fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);

		if ($fuser->societe_id) $socid=$fuser->societe_id;

		// Check parameters
		if (! $error && ( ! $file || ! $modulepart ) )
		{
			$error++;
			$errorcode='BAD_PARAMETERS'; $errorlabel="Parameter file and modulepart must be both provided.";
		}

		if (! $error)
		{
			$fuser->getrights();

			$original_file = $upload_dir."/".$file;
			// Suppression de la chaine de caractere ../ dans $original_file
			$original_file = str_replace("../","/", $original_file);

			// find the subdirectory name as the reference
			if (empty($refname)) $refname=basename(dirname($original_file)."/");

			// Security check
			$check_access = dol_check_secure_access_document($modulepart,$original_file,$conf->entity,$fuser,$refname);
			$accessallowed              = $check_access['accessallowed'];
			$sqlprotectagainstexternals = $check_access['sqlprotectagainstexternals'];
			//$original_file              = $check_access['original_file'];

			// Basic protection (against external users only)
			if ($fuser->societe_id > 0)
			{
				if ($sqlprotectagainstexternals)
				{
					$resql = $db->query($sqlprotectagainstexternals);
					if ($resql)
					{
						$num=$db->num_rows($resql);
						$i=0;
						while ($i < $num)
						{
							$obj = $db->fetch_object($resql);
							if ($fuser->societe_id != $obj->fk_soc)
							{
								$accessallowed=0;
								break;
							}
							$i++;
						}
					}
				}
			}

			// Security:
			// Limite acces si droits non corrects
			if (! $accessallowed)
			{
				$errorcode='NOT_PERMITTED';
				$errorlabel='Access not allowed';
				$error++;
			}

			// Security:
			// On interdit les remontees de repertoire ainsi que les pipe dans
			// les noms de fichiers.
			if (preg_match('/\.\./',$original_file) || preg_match('/[<>|]/',$original_file))
			{
				dol_syslog("Refused to deliver file ".$original_file);
				$errorcode='REFUSED';
				$errorlabel='';
				$error++;
			}

			clearstatcache();

			if(!$error)
			{
		//echo $original_file;
				if(file_exists($original_file))
				{
					$f = unlink($original_file);

					$path_parts = pathinfo($original_file);
					$thumb=$upload_dir."/thumbs/".$path_parts['filename']."_small.".$path_parts['extension'];
					if(file_exists($thumb)) unlink($thumb);
					$thumb=$upload_dir."/thumbs/".$path_parts['filename']."_mini.".$path_parts['extension'];
					if(file_exists($thumb)) unlink($thumb);
					$objectret = array(
						'filename' => basename($original_file),
						'mimetype' => dol_mimetype($original_file),
						'content' => "",
						'length' => 0
					);

					// Create return object
					$objectresp = array(
						'result'=>array('result_code'=>'OK', 'result_label'=>''),
						'document'=>$objectret
					);
				}
				else
				{
					dol_syslog("File doesn't exist ".$original_file);
					$errorcode='NOT_FOUND';
					$errorlabel=$original_file;
					$error++;
				}
			}
		}
	}
	else
	{
		$error++;
		$errorcode='BAD_PARAMETERS'; $errorlabel="Parameter id is provided.";
	}

	if ($error)
	{
		$objectresp = array(
		'result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel)
		);
	}

	return $objectresp;
}

/**
 * Method to send a document by webservice
 *
 * @param 	array	$authentication		Array with permissions
 * @param 	string	$modulepart		 	Properties of document
 * @param	string	$file				Relative path
 * @param	string	$refname			Ref of object to check permission for external users (autodetect if not provided)
 * @return	void
 */
function sendDocument($authentication, $modulepart, $idthirdparty, $file, $content='')
{
	global $db,$conf,$langs,$mysoc;

	dol_syslog("Function: sendDocument login=".$authentication['login'].' - modulepart='.$modulepart.' - file='.$file);

	if ($authentication['entity'])
	{
		$conf->entity=$authentication['entity'];
		$conf->setValues($db);
		$mysoc->setMysoc($conf);
	}

	$objectresp=array();
	$errorcode='';$errorlabel='';
	$error=0;

	// Properties of doc
	$original_file = $file;
	$type=dol_mimetype($original_file);

	$accessallowed=0;

	$fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);

	if ($fuser->societe_id) $socid=$fuser->societe_id;

	// Check parameters
	if (! $error && ( ! $file || ! $modulepart ) )
	{
		$error++;
		$errorcode='BAD_PARAMETERS'; $errorlabel="Parameter document must be provided.";
	}

	if (! $error)
	{
		$fuser->getrights();

		// Suppression de la chaine de caractere ../ dans $original_file
		$original_file = str_replace("../","/", $original_file);

		// find the subdirectory name as the reference
		if (empty($refname)) $refname=basename(dirname($original_file)."/");

		// Security check
		$check_access = dol_check_secure_access_document($modulepart,$original_file,$conf->entity,$fuser,$refname);
		$accessallowed              = $check_access['accessallowed'];
		$sqlprotectagainstexternals = $check_access['sqlprotectagainstexternals'];
		$original_file              = $check_access['original_file'];

	// Basic protection (against external users only)
		if ($fuser->societe_id > 0)
		{
			if ($sqlprotectagainstexternals)
			{
				$resql = $db->query($sqlprotectagainstexternals);
				if ($resql)
				{
					$num=$db->num_rows($resql);
					$i=0;
					while ($i < $num)
					{
						$obj = $db->fetch_object($resql);
						if ($fuser->societe_id != $obj->fk_soc)
						{
							$accessallowed=0;
							break;
						}
						$i++;
					}
				}
			}
		}

		// Security:
		// Limite acces si droits non corrects
		if (! $accessallowed)
		{
			$errorcode='NOT_PERMITTED';
			$errorlabel='Access not allowed';
			$error++;
		}

		// Security:
		// On interdit les remontees de repertoire ainsi que les pipe dans
		// les noms de fichiers.
		if (preg_match('/\.\./',$original_file) || preg_match('/[<>|]/',$original_file))
		{
			dol_syslog("Refused to deliver file ".$original_file);
			$errorcode='REFUSED';
			$errorlabel='';
			$error++;
		}

		clearstatcache();

		if(!$error)
		{
			if ($content!='')
			{
				$content=base64_decode($content);

				if ($idthirdparty>0)
				{
					$object = new Societe($db);
					$result = $object->fetch($idthirdparty, "");

					$upload_dir = $conf->societe->multidir_output[$object->entity] . "/" . $object->id ;

					$original_file = $upload_dir."/".$file;
					if (!is_dir($upload_dir))
					{
						@mkdir($upload_dir, 0755, true);
					}
				}

				if (file_put_contents($original_file,$content)>0)
				{

					vignette($original_file, 160, 120, '_small', 50, "thumbs");
					vignette($original_file, 160, 120, '_mini', 50, "thumbs");

					// Create return object
					$objectresp = array(
						'result'=>array('result_code'=>'OK', 'result_label'=>''),
						'path'=>$original_file
					);
				}
				else
				{
					$errorcode='REFUSED';
					$errorlabel='';
					$error++;
				}
			}
			else
			{
				$errorcode='REFUSED';
				$errorlabel='';
				$error++;
			}
		}
	}
	if ($error)
	{
		$objectresp = array(
		'result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel)
		);
	}

	return $objectresp;
}

function getBankAccounts($authentication)
{
	global $db,$conf,$langs,$mysoc;

	dol_syslog("Function: getBankAccounts login=".$authentication['login']);

	if ($authentication['entity'])
	{
		$conf->entity=$authentication['entity'];
		$conf->setValues($db);
		$mysoc->setMysoc($conf);
	}

	// Init and check authentication
	$objectresp=array();
	$errorcode='';$errorlabel='';
	$error=0;
	$fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);

	if (! $error)
	{
		$sql = "SELECT rowid, ref, label, courant, clos FROM ".MAIN_DB_PREFIX."bank_account WHERE clos=0 ORDER BY rowid";

		$resql=$db->query($sql);
		if ($resql)
		{
			$lines=array();
			$num=$db->num_rows($resql);
			$i=0;
			while ($i < $num)
			{
				// En attendant remplissage par boucle
				$obj=$db->fetch_object($resql);

				$lines[]=array(
				'id' => $obj->rowid,
				'ref' => $obj->ref,
				'label' => $obj->label,
				'current' => $obj->courant
				);

				$i++;
			}

			$objectresp=array(
			'result'=>array('result_code'=>'OK', 'result_label'=>''),
			'bankAccounts'=>$lines

			);
		}
		else
		{
			$error++;
			$errorcode=$db->lasterrno(); $errorlabel=$db->lasterror();
		}
	}

	if ($error)
	{
		$objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
	}

	return $objectresp;
}

function getPaiementModes($authentication)
{
	global $db,$conf,$langs,$mysoc;

	dol_syslog("Function: getPaiementModes login=".$authentication['login']);

	if ($authentication['entity'])
	{
		$conf->entity=$authentication['entity'];
		$conf->setValues($db);
		$mysoc->setMysoc($conf);
	}

	// Init and check authentication
	$objectresp=array();
	$errorcode='';$errorlabel='';
	$error=0;
	$fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);

	if (! $error)
	{
		$sql = "SELECT id, code, libelle, type, active FROM ".MAIN_DB_PREFIX."c_paiement WHERE id!=0 AND type=2 AND active=1 ORDER BY id";

		$resql=$db->query($sql);
		if ($resql)
		{
			$lines=array();
			$num=$db->num_rows($resql);
			$i=0;
			while ($i < $num)
			{
				// En attendant remplissage par boucle
				$obj=$db->fetch_object($resql);

				$lines[]=array(
				'id' => $obj->id,
				'code' => $obj->code,
				'label' => $obj->libelle
				);

				$i++;
			}

			$objectresp=array(
			'result'=>array('result_code'=>'OK', 'result_label'=>''),
			'paiementModes'=>$lines

			);
		}
		else
		{
			$error++;
			$errorcode=$db->lasterrno(); $errorlabel=$db->lasterror();
		}
	}

	if ($error)
	{
		$objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
	}

	return $objectresp;
}
function getCountryVatRates($authentication)
{
	global $db,$conf,$langs,$mysoc;

	dol_syslog("Function: getCountryVatRates login=".$authentication['login']);

	if ($authentication['entity'])
	{
		$conf->entity=$authentication['entity'];
		$conf->setValues($db);
		$mysoc->setMysoc($conf);
	}

	// Init and check authentication
	$objectresp=array();
	$errorcode='';$errorlabel='';
	$error=0;
	$fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);

	$tmp=$conf->global->MAIN_INFO_SOCIETE_COUNTRY;
	if ($tmp=="") $tmp="1:FR:France";
	$tabTmp=explode(":",$tmp);
	$country_id=intval($tabTmp[0]);

	if (! $error)
	{
		$sql = "SELECT fk_pays, taux, active FROM ".MAIN_DB_PREFIX."c_tva WHERE fk_pays=".$country_id." AND active=1 ORDER BY taux";

		$resql=$db->query($sql);
		if ($resql)
		{
			$lines=array();
			$num=$db->num_rows($resql);
			$i=0;
			while ($i < $num)
			{
				// En attendant remplissage par boucle
				$obj=$db->fetch_object($resql);

				$lines[]=array(
				'rate' => $obj->taux
				);

				$i++;
			}

			$objectresp=array(
			'result'=>array('result_code'=>'OK', 'result_label'=>''),
			'tvaRates'=>$lines

			);
		}
		else
		{
			$error++;
			$errorcode=$db->lasterrno(); $errorlabel=$db->lasterror();
		}
	}

	if ($error)
	{
		$objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
	}

	return $objectresp;
}
function getUsers($authentication)
{
	global $db,$conf,$langs,$mysoc;

	dol_syslog("Function: getUsers login=".$authentication['login']);

	if ($authentication['entity'])
	{
		$conf->entity=$authentication['entity'];
		$conf->setValues($db);
		$mysoc->setMysoc($conf);
	}

	// Init and check authentication
	$objectresp=array();
	$errorcode='';$errorlabel='';
	$error=0;
	$fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);

	if (! $error)
	{
		$sql = "SELECT rowid, login, lastname, firstname, user_mobile FROM ".MAIN_DB_PREFIX."user ORDER BY rowid";

		$resql=$db->query($sql);
		if ($resql)
		{
			$lines=array();
			$num=$db->num_rows($resql);
			$i=0;
			while ($i < $num)
			{
				// En attendant remplissage par boucle
				$obj=$db->fetch_object($resql);

				$lines[]=array(
				'id' => $obj->rowid,
				'login' => $obj->login,
				'name' => $obj->firstname." ".$obj->lastname,
				'mobile' => $obj->user_mobile
				);

				$i++;
			}

			$objectresp=array(
			'result'=>array('result_code'=>'OK', 'result_label'=>''),
			'users'=>$lines

			);
		}
		else
		{
			$error++;
			$errorcode=$db->lasterrno(); $errorlabel=$db->lasterror();
		}
	}

	if ($error)
	{
		$objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
	}

	return $objectresp;
}
function getTypesRendezVous($authentication)
{
	global $db,$conf,$langs,$mysoc;

	dol_syslog("Function: getTypesRendezVous login=".$authentication['login']);

	if ($authentication['entity'])
	{
		$conf->entity=$authentication['entity'];
		$conf->setValues($db);
		$mysoc->setMysoc($conf);
	}

	// Init and check authentication
	$langs->load("commercial");
	$objectresp=array();
	$errorcode='';$errorlabel='';
	$error=0;
	$fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);

	if (! $error)
	{
		$sql = "SELECT id, code, libelle, position, active FROM ".MAIN_DB_PREFIX."c_actioncomm WHERE id!=0 AND id!=40 AND active=1 ORDER BY id";

		$resql=$db->query($sql);
		if ($resql)
		{
			$lines=array();
			$num=$db->num_rows($resql);
			$i=0;
			while ($i < $num)
			{
				// En attendant remplissage par boucle
				$obj=$db->fetch_object($resql);
				$key=$langs->trans("Action".strtoupper($obj->code));
				if ($key=="Action".strtoupper($obj->code)) $key=$obj->libelle;
				$lines[]=array(
				'id' => $obj->id,
				'code' => $obj->code,
				'label' => $key,
				'pos' => $obj->position
				);

				$i++;
			}

			$objectresp=array(
			'result'=>array('result_code'=>'OK', 'result_label'=>''),
			'typesRendezVous'=>$lines

			);
		}
		else
		{
			$error++;
			$errorcode=$db->lasterrno(); $errorlabel=$db->lasterror();
		}
	}

	if ($error)
	{
		$objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
	}

	return $objectresp;
}

// Return the results.
$server->service(file_get_contents("php://input"));
