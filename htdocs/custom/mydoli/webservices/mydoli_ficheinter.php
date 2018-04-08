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
 *       \file       htdocs/webservices/server_invoice.php
 *       \brief      File that is entry point to call Dolibarr WebServices
 */

if (! defined('NOCSRFCHECK')) define('NOCSRFCHECK','1');

// This is to make Dolibarr working with Plesk
set_include_path($_SERVER['DOCUMENT_ROOT'].'/htdocs');
if(is_file('../main.inc.php')) $dir = '../';
	else  if(is_file('../../../main.inc.php')) $dir = '../../../';
	else $dir = '../../';

require_once $dir.'master.inc.php';
require_once NUSOAP_PATH.'/nusoap.php';		// Include SOAP
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ws.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/fichinter/modules_fichinter.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/fichinter.lib.php';
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
$server->configureWSDL('WebServicesDolibarrFicheinter',$ns);
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
        'entity' => array('name'=>'entity','type'=>'xsd:string')
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

$server->wsdl->addComplexType(
    'extras',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'code' => array('name'=>'code','type'=>'xsd:string'),
        'label' => array('name'=>'label','type'=>'xsd:string')
    )
);

$server->wsdl->addComplexType(
    'extrasArray',
    'complexType',
    'array',
    'sequence',
    '',
    array(
        'extras' => array(
            'name' => 'extras',
            'type' => 'tns:extras',
            'minOccurs' => '0',
            'maxOccurs' => 'unbounded'
        )
    ),
    null,
    'tns:extras'
);

$server->wsdl->addComplexType(
    'line',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'id' => array('name'=>'id','type'=>'xsd:int'),
        'label' => array('name'=>'label','type'=>'xsd:string'),
        'duree' => array('name'=>'duree','type'=>'xsd:string')
    )
);

$server->wsdl->addComplexType(
    'LinesArray',
    'complexType',
    'array',
    'sequence',
    '',
    array(
        'line' => array(
            'name' => 'line',
            'type' => 'tns:line',
            'minOccurs' => '0',
            'maxOccurs' => 'unbounded'
        )
    ),
    null,
    'tns:line'
);

$ficheInter_fields = array(
    'id' => array('name'=>'id','type'=>'xsd:int'),
    'ref' => array('name'=>'ref','type'=>'xsd:string'),
    'ref_client' => array('name'=>'ref_client','type'=>'xsd:string'),
    'id_client' => array('name'=>'id_client','type'=>'xsd:int'),
    'date' => array('name'=>'date','type'=>'xsd:date'),
    'heure' => array('name'=>'heure','type'=>'xsd:string'),
    'duree' => array('name'=>'duree','type'=>'xsd:int'),
    'description' => array('name'=>'description','type'=>'xsd:string'),
    'lines' => array('name'=>'lines','type'=>'tns:LinesArray')
);

$extrafields=new ExtraFields($db);
$extralabels=$extrafields->fetch_name_optionals_label('fichinter',true);
if (count($extrafields)>0)
{
	$extrafield_array = array();

	foreach($extrafields->attribute_label as $key=>$label)
	{
		$type =$extrafields->attribute_type[$key];
		if ($type=='int') {$type='xsd:int';}
		else {$type='xsd:string';}

		$extrafield_array[$key]=array('name'=>$key,'type'=>$type);
	}
	$ficheInter_fields=array_merge($ficheInter_fields,$extrafield_array);
}
$server->wsdl->addComplexType(
    'ficheinter',
    'complexType',
    'struct',
    'all',
    '',
    $ficheInter_fields
);

$server->wsdl->addComplexType(
    'FicheinterArray',
    'complexType',
    'array',
    'sequence',
    '',
    array(
        'ficheinter' => array(
            'name' => 'ficheinter',
            'type' => 'tns:ficheinter',
            'minOccurs' => '0',
            'maxOccurs' => 'unbounded'
        )
    ),
    null,
    'tns:ficheinter'
);

// 5 styles: RPC/encoded, RPC/literal, Document/encoded (not WS-I compliant), Document/literal, Document/literal wrapped
// Style merely dictates how to translate a WSDL binding to a SOAP message. Nothing more. You can use either style with any programming model.
// http://www.ibm.com/developerworks/webservices/library/ws-whichwsdl/
$styledoc='rpc';       // rpc/document (document is an extend into SOAP 1.0 to support unstructured messages)
$styleuse='encoded';   // encoded/literal/literal wrapped
// Better choice is document/literal wrapped but literal wrapped not supported by nusoap.

// Register WSDL
$server->register(
    'getFicheinter',
    // Entry values
    array('authentication'=>'tns:authentication','id'=>'xsd:string','ref'=>'xsd:string','ref_ext'=>'xsd:string'),
    // Exit values
    array('result'=>'tns:result','ficheinter'=>'tns:ficheinter'),
    $ns,
    $ns.'#getFicheinter',
    $styledoc,
    $styleuse,
    'WS to get a particular intervention'
);

$server->register(
    'getExtras',
    // Entry values
    array('authentication'=>'tns:authentication'),
    // Exit values
    array('result'=>'tns:result','extras'=>'tns:extrasArray'),
    $ns,
    $ns.'#getExtras',
    $styledoc,
    $styleuse,
    'WS to get all extras for Interventions'
);

$server->register(
    'getFicheinterForThirdParty',
    // Entry values
    array('authentication'=>'tns:authentication','idthirdparty'=>'xsd:string'),
    // Exit values
    array('result'=>'tns:result','fichesinter'=>'tns:FicheinterArray'),
    $ns,
    $ns.'#getFicheinterForThirdParty',
    $styledoc,
    $styleuse,
    'WS to get all interventions of a third party'
);

$server->register(
    'createFicheinter',
    // Entry values
    array('authentication'=>'tns:authentication','ficheinter'=>'tns:ficheinter'),
    // Exit values
    array('result'=>'tns:result','id'=>'xsd:string','ref'=>'xsd:string'),
    $ns,
    $ns.'#createFicheinter',
    $styledoc,
    $styleuse,
    'WS to create a intervention'
);

$server->register(
    'updateFicheinter',
    // Entry values
    array('authentication'=>'tns:authentication','id'=>'xsd:string','ficheinter'=>'tns:ficheinter'),
    // Exit values
    array('result'=>'tns:result','id'=>'xsd:string','ref'=>'xsd:string'),
    $ns,
    $ns.'#updateFicheinter',
    $styledoc,
    $styleuse,
    'WS to update a intervention'
);

$server->register(
    'deleteFicheinter',
    // Entry values
    array('authentication'=>'tns:authentication','id'=>'xsd:string','ref'=>'xsd:string','ref_ext'=>'xsd:string'),
    // Exit values
    array('result'=>'tns:result','id'=>'xsd:string'),
    $ns,
    $ns.'#deleteFicheinter',
    $styledoc,
    $styleuse,
    'WS to delete a particular intervention'
);

function isCommercial($idTiers,$idUser)
{
	global $db,$conf,$langs;

	$sql= "SELECT fk_soc,fk_user FROM ".MAIN_DB_PREFIX."societe_commerciaux WHERE fk_soc=".$idTiers." AND fk_user=".$idUser;

    $resql=$db->query($sql);
    if ($resql)
    {
          $num=$db->num_rows($resql);
          if ($num>=1)
          {
		$db->free($resql);
		return 1;
          }
	}
	$db->free($resql);
	$sql= "SELECT fk_soc,fk_user FROM ".MAIN_DB_PREFIX."societe_commerciaux WHERE fk_soc=".$idTiers;
    $resql=$db->query($sql);
    if ($resql)
    {
          $num=$db->num_rows($resql);
          if ($num<1)
          {
		$db->free($resql);
		return 1;
          }
	}
	$db->free($resql);

	return 0;
}
/**
 * Get devis from id, ref or ref_ext.
 *
 * @param	array		$authentication		Array of authentication information
 * @param	int			$id					Id
 * @param	string		$ref				Ref
 * @param	string		$ref_ext			Ref_ext
 * @return	array							Array result
 */
function getFicheinter($authentication,$id='',$ref='',$ref_ext='')
{
	global $db,$conf,$langs,$mysoc;

	dol_syslog("Function: getFicheinter login=".$authentication['login']." id=".$id." ref=".$ref." ref_ext=".$ref_ext);

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
	if (! $error && (($id && $ref) || ($id && $ref_ext) || ($ref && $ref_ext)))
	{
		$error++;
		$errorcode='BAD_PARAMETERS'; $errorlabel="Parameter id, ref and ref_ext can't be both provided. You must choose one or other but not both.";
	}

	if (! $error)
	{
		$fuser->getrights();

		//if ($fuser->rights->ficheinter->lire)
		//{
			$linesFiches=array();
			$sql = "SELECT rowid, ref, description, fk_soc, fk_statut,";
			$sql.= " datec,";
			$sql.= " date_valid,";
			$sql.= " tms,";
			$sql.= " duree, fk_projet, note_public, note_private, model_pdf, extraparams, fk_contrat";
			$sql.= " FROM ".MAIN_DB_PREFIX."fichinter";
			$sql.=" WHERE rowid = ".$id;

			$resql=$db->query($sql);
			if ($resql)
			{

				$obj=$db->fetch_object($resql);

				$ficheInter=new FichInter($db);
				$result=$ficheInter->fetch($obj->rowid);
				if ($result > 0)
				{
					$ok=1;
					if ($fuser->admin==0)
					{
						$ok=isCommercial($obj->fk_soc,$fuser->id);
					}
					if($ok==1)
					{
						$linesresp=array();
						$rang=1;
						foreach($ficheInter->lines as $line)
						{
							//var_dump($line); exit;
							$linesresp[]=array(
								'id'=>$rang,
								'label'=>dol_htmlcleanlastbr($line->desc),
								'duree'=>$line->duration
							);
							$rang++;
						}

						$extrafields=new ExtraFields($db);
						$extralabels=$extrafields->fetch_name_optionals_label('fichinter',true);

						$linesFiches=array(
							'id' => $obj->rowid,
							'ref' => $obj->ref,
							'ref_client' => $obj->description,
							'id_client' => $obj->fk_soc,
							'date' => $obj->datec?substr($obj->datec,0,10):'',
							'heure' => $obj->datec?substr($obj->datec,11,5):'',
							'duree' => $obj->duree,
							'description'=>dol_htmlcleanlastbr($obj->note_public),
							'lines' => $linesresp
						);

						//Get extrafield values

						$ficheInter->fetch_optionals($obj->rowid,$extralabels);
						foreach($extrafields->attribute_label as $key=>$label)
						{
						//echo $key."=".$ficheInter->array_options[$key].";";
							$linesFiches=array_merge($linesFiches,array($key => intval($ficheInter->array_options["options_".$key])));
						}

						$objectresp=array(
							'result'=>array('result_code'=>'OK', 'result_label'=>''),
							'ficheinter'=>$linesFiches );
					}
					else
					{
						$error++;
						$errorcode='PERMISSION_DENIED'; $errorlabel='User does not have permission for this request';
					}
				}
				else
				{
					$error++;
					$errorcode='NOT_FOUND'; $errorlabel='Object not found for id='.$id.' nor ref='.$ref.' nor ref_ext='.$ref_ext;
				}
			}
			else
			{
				$error++;
				$errorcode='NOT_FOUND'; $errorlabel='Object not found for id='.$id.' nor ref='.$ref.' nor ref_ext='.$ref_ext;
			}
		//}
		//else
		//{
		//	$error++;
		//	$errorcode='PERMISSION_DENIED'; $errorlabel='User does not have permission for this request';
		//}
	}

	if ($error)
	{
		$objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
	}

	return $objectresp;
}


/**
 * Get list of invoices for third party
 *
 * @param	array		$authentication		Array of authentication information
 * @param	int			$idthirdparty		Id thirdparty
 * @return	array							Array result
 */
function getFicheinterForThirdParty($authentication,$idthirdparty)
{
	global $db,$conf,$langs,$mysoc;

	dol_syslog("Function: getFicheinterForThirdParty login=".$authentication['login']." idthirdparty=".$idthirdparty);

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

	if ($fuser->societe_id) $socid=$fuser->societe_id;

	if (! $error && empty($idthirdparty))
	{
		$error++;
		$errorcode='BAD_PARAMETERS'; $errorlabel="Parameter idthirdparty is provided.";
	}
	if (! $error)
	{
		$linesFiches=array();
		$sql = "SELECT rowid, ref, description, fk_soc, fk_statut,";
		$sql.= " datec,";
		$sql.= " date_valid,";
		$sql.= " tms,";
		$sql.= " duree, fk_projet, note_public, note_private, model_pdf, extraparams, fk_contrat";
		$sql.= " FROM ".MAIN_DB_PREFIX."fichinter";
		if ($idthirdparty != 'all' ) $sql.=" WHERE fk_soc = ".$db->escape($idthirdparty);

		$resql=$db->query($sql);
		if ($resql)
		{
			$extrafields=new ExtraFields($db);
			$extralabels=$extrafields->fetch_name_optionals_label('fichinter',true);
			$num=$db->num_rows($resql);
			$i=0;
			while ($i < $num)
			{

				// En attendant remplissage par boucle
				$obj=$db->fetch_object($resql);

			    $ficheInter=new FichInter($db);
				$result=$ficheInter->fetch($obj->rowid);

				if ($result > 0)
				{

					// Sécurité pour utilisateur externe
			//	    if( $socid && ( $socid != $devis->socid) )
			//	    {
			//	    	$error++;
			//	    	$errorcode='PERMISSION_DENIED'; $errorlabel=$invoice->socid.' User does not have permission for this request';
			//	    }

					$ok=1;
					if ($fuser->admin==0)
					{
						$ok=isCommercial($obj->fk_soc,$fuser->id);
					}
					if((!$error)&&($ok==1))
					{

						$linesresp=array();
						$rang=1;
						// foreach($ficheInter->lines as $line)
// 						{
// 							//var_dump($line); exit;
// 							$linesresp[]=array(
// 								'id'=>$rang,
// 								'label'=>dol_htmlcleanlastbr($line->desc),
// 								'duree'=>$line->duration
// 							);
// 							$rang++;
// 						}

						if(!$error)
						{
							$linesFiches[]=array(
								'id' => $obj->rowid,
								'ref' => $obj->ref,
								'ref_client' => $obj->description,
								'id_client' => $obj->fk_soc,
								'date' => $obj->datec?substr($obj->datec,0,10):'',
								'heure' => $obj->datec?substr($obj->datec,11,5):'',
								'duree' => $obj->duree,
								'description'=>dol_htmlcleanlastbr($obj->note_public),
								'lines' => $linesresp
							);

							//Get extrafield values
							// $ficheInter->fetch_optionals($obj->rowid,$extralabels);
// 							foreach($extrafields->attribute_label as $key=>$label)
// 							{
// 							//echo $key."=".$ficheInter->array_options[$key].";";
// 								$linesFiches[$i]=array_merge($linesFiches[$i],array($key => intval($ficheInter->array_options["options_".$key])));
// 							}

						}
					}
				}
				$i++;
			}

			$objectresp=array(
				'result'=>array('result_code'=>'OK', 'result_label'=>''),
				'fichesinter'=>$linesFiches );
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
function getExtras($authentication)
{
	global $db,$conf,$langs,$mysoc;

	dol_syslog("Function: getExtras login=".$authentication['login']);

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

	if ($fuser->societe_id) $socid=$fuser->societe_id;

	if (! $error)
	{
		$linesFiches=array();

		$sql = "SELECT name,label,elementtype,entity FROM ".MAIN_DB_PREFIX."extrafields WHERE elementtype='fichinter' AND entity=".$conf->entity." ORDER BY name";
		$resql=$db->query($sql);
		if ($resql)
		{
			$num=$db->num_rows($resql);
			$i=0;
			while ($i < $num)
			{
				$obj=$db->fetch_object($resql);

				$linesFiches[]=array('code' => $obj->name,'label'=> $obj->label);
				$i++;
			}
			$objectresp=array(
				'result'=>array('result_code'=>'OK', 'result_label'=>''),
				'extras'=>$linesFiches );
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


/**
 * Create a Devis
 *
 * @param	array		$authentication		Array of authentication information
 * @param	Devis		$devis				Devis
 * @return	array							Array result
 */
function createFicheinter($authentication,$ficheinter)
{
    global $db,$conf,$langs,$mysoc;

    $now=dol_now();

    dol_syslog("Function: createFicheinter login=".$authentication['login']);

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
        $newobject=new Fichinter($db);
        $newobject->socid=$ficheinter['id_client'];
        $newobject->datec=$ficheinter['date'].' '.$ficheinter['heure'];
        $newobject->description=$ficheinter['ref_client'];
        $newobject->note_public=$ficheinter['description'];

        //*** valide controles et mesures
        $extrafields=new ExtraFields($db);
		$extralabels=$extrafields->fetch_name_optionals_label('fichinter',true);
		foreach($extrafields->attribute_label as $key=>$label)
		{
			$key2="options_".$key;
			$newobject->array_options[$key2]=$ficheinter[$key];
		}

        $result=$newobject->create($fuser,0);
        if ($result < 0)
        {
            $error++;
        }

        if (! $error)
        {
		$arrayoflines=array();
			if (isset($ficheinter['lines']['line'][0])) $arrayoflines=$ficheinter['lines']['line'];
			else $arrayoflines=$ficheinter['lines'];
			foreach($arrayoflines as $key => $line)
			{
				$newobject->addline($fuser,$newobject->id, $line['label'], $newobject->datec, $line['duree']);
			}

		$newobject->fetch_lines();
			$outputlangs = $langs;
			$newlang='';
			if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$newobject->client->default_lang;
			if (! empty($newlang))
			{
				$outputlangs = new Translate("",$conf);
				$outputlangs->setDefaultLang($newlang);
			}
			$newobject->setValid($fuser);
			$v=floatval(version_dolibarr());
            if ($v<4)
		$result=fichinter_create($db, $newobject, 'mydoli_soleil', $outputlangs);
            else
		$result=fichinter_create($db, $newobject, 'mydoli_soleil4', $outputlangs);


		//$newobject->generateDocument("mydoli_soleil", $langs);

            $objectresp=array('result'=>array('result_code'=>'OK', 'result_label'=>''),'id'=>$newobject->id,'ref'=>$newobject->ref);
        }
        else
        {
            $error++;
            $errorcode='KO';
            $errorlabel="err(".$result.") ".$newobject->error;
        }

    }

    if ($error)
    {
        $objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
    }

    return $objectresp;
}

/**
 * Update a Devis
 *
 * @param	array		$authentication		Array of authentication information
 * @param	Devis		$devis				Devis
 * @return	array							Array result
 */
function updateFicheinter($authentication,$id,$ficheinter)
{
    global $db,$conf,$langs,$mysoc;

    $now=dol_now();

    dol_syslog("Function: updateFicheinter login=".$authentication['login']);

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
        $fuser->getrights();

		$newobject=new Fichinter($db);
		$result=$newobject->fetch($id,"","");
		if ($result > 0)
		{
			$newobject->setDraft($fuser);
			$newobject->statut=0;
	//		if (($newobject->statut==0)&&(intval($devis['status'])>0)) $newobject->valid($fuser);
			$newobject->socid=$ficheinter['id_client'];
			$newobject->datec=$ficheinter['date'].' '.$ficheinter['heure'];
			$newobject->description=$ficheinter['ref_client'];
			$newobject->note_public=$ficheinter['description'];

			 //*** valide controles et mesures
			$extrafields=new ExtraFields($db);
			$extralabels=$extrafields->fetch_name_optionals_label('fichinter',true);
			foreach($extrafields->attribute_label as $key=>$label)
			{
				$key2="options_".$key;
				$newobject->array_options[$key2]=$ficheinter[$key];
			}

			$result=$newobject->update($fuser,0);
			if ($result < 0)
			{
				$error++;
			}

			if (! $error)
			{
				$sql = "DELETE FROM ".MAIN_DB_PREFIX."fichinterdet WHERE fk_fichinter = ".$id;
				$resql = $db->query($sql);
				$arrayoflines=array();
				if (isset($ficheinter['lines']['line'][0])) $arrayoflines=$ficheinter['lines']['line'];
				else $arrayoflines=$ficheinter['lines'];
				foreach($arrayoflines as $key => $line)
				{
					$newobject->addline($fuser,$newobject->id, $line['label'], $newobject->datec, $line['duree']);
				}
				$newobject->setValid($fuser);
				$newobject->fetch_lines();
				$outputlangs = $langs;
				$newlang='';
				if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$newobject->client->default_lang;
				if (! empty($newlang))
				{
					$outputlangs = new Translate("",$conf);
					$outputlangs->setDefaultLang($newlang);
				}
				$result=$newobject->fetch($id,"","");
				$v=floatval(version_dolibarr());
		if ($v<4)
			$result=fichinter_create($db, $newobject, 'mydoli_soleil', $outputlangs);
			else
			$result=fichinter_create($db, $newobject, 'mydoli_soleil4', $outputlangs);

				//$newobject->generateDocument("mydoli_soleil", $langs);
			}
		}

        if (! $error)
        {
            $objectresp=array('result'=>array('result_code'=>'OK', 'result_label'=>''),'id'=>$newobject->id,'ref'=>$newobject->ref);
        }
    }

    if ($error)
    {
        $objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
    }

    return $objectresp;
}

function deleteFicheinter($authentication,$id='',$ref='',$ref_ext='')
{
	global $db,$conf,$langs,$mysoc;

	dol_syslog("Function: deleteFicheInter login=".$authentication['login']." id=".$id." ref=".$ref." ref_ext=".$ref_ext);

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
	if (! $error && (($id && $ref) || ($id && $ref_ext) || ($ref && $ref_ext)))
	{
		$error++;
		$errorcode='BAD_PARAMETERS'; $errorlabel="Parameter id, ref and ref_ext can't be both provided. You must choose one or other but not both.";
	}

	if (! $error)
	{
		$fuser->getrights();

		if ($fuser->rights->ficheinter->lire)
		{
			$devis=new Fichinter($db);
			$result=$devis->fetch($id,$ref,$ref_ext);
			if ($result > 0)
			{
				$result=$devis->delete($fuser,0);
				if ($result > 0)
				{
					$objectresp=array('result'=>array('result_code'=>'OK', 'result_label'=>''),'id'=>$id);
				}
				else
				{
					$objectresp=array('result'=>array('result_code'=>'OK', 'result_label'=>''),'id'=>'0');
				}
			}
			else
			{
				$error++;
				$errorcode='NOT_FOUND'; $errorlabel='Object not found for id='.$id.' nor ref='.$ref.' nor ref_ext='.$ref_ext;
			}
		}
		else
		{
			$error++;
			$errorcode='PERMISSION_DENIED'; $errorlabel='User does not have permission for this request';
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
