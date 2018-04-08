<?php

if (! defined('NOCSRFCHECK')) define('NOCSRFCHECK','1');

set_include_path($_SERVER['DOCUMENT_ROOT'].'/htdocs');
if(is_file('../main.inc.php')) $dir = '../';
	else  if(is_file('../../../main.inc.php')) $dir = '../../../';
	else $dir = '../../';

require_once $dir.'master.inc.php';
require_once NUSOAP_PATH.'/nusoap.php';        // Include SOAP
require_once DOL_DOCUMENT_ROOT.'/core/lib/ws.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

$langs->load("main");

if (empty($conf->global->MAIN_MODULE_WEBSERVICES))
{
	$langs->load("admin");
	dol_syslog("Call Dolibarr webservices interfaces with module webservices disabled");
	print $langs->trans("WarningModuleNotActive",'WebServices').'.<br><br>';
	print $langs->trans("ToActivateModule");
	exit;
}

//***** Auto create table materiel
function testTableExist()
{
	global $db,$conf,$langs;

	$checktable = $db->query("SHOW TABLES LIKE '".MAIN_DB_PREFIX."societe_gps'");
	if (($checktable)&&($db->num_rows($checktable)<=0))
	{
		$db->query("CREATE TABLE ".MAIN_DB_PREFIX."societe_gps (fk_soc INT(6) PRIMARY KEY,latitude FLOAT(10,6),longitude FLOAT(10,6))");
	}
}

function getCoordinates($address)
{
    $address = urlencode($address);
    $url = "http://maps.google.com/maps/api/geocode/json?sensor=false&address=" . $address;
    $response = file_get_contents($url);
    $json = json_decode($response,true);

    $lat = $json['results'][0]['geometry']['location']['lat'];
    $lng = $json['results'][0]['geometry']['location']['lng'];

 if ($lat=='') var_dump($json);

    return array($lat, $lng);
}

function getGPSOfThirdParties()
{
    global $db,$conf,$langs,$mysoc;

	if ($authentication['entity'])
	{
		$conf->entity=$authentication['entity'];
		$conf->setValues($db);
		$mysoc->setMysoc($conf);
	}

    $sql  = "SELECT rowid,nom,address,zip,town,client";
    $sql .= " FROM ".MAIN_DB_PREFIX."societe";
    $sql.=" WHERE entity=".$conf->entity." AND client=1";

    $resql=$db->query($sql);
    if ($resql)
    {
        $num=$db->num_rows($resql);

        $i=0;
        while ($i < $num)
        {
		$obj=$db->fetch_object($resql);
		if ((rtrim($obj->zip)!="")&&(rtrim($obj->town)!=""))
		{
			$ad=utf8_encode($obj->address." ".$obj->zip." ".$obj->town);
			$coords = getCoordinates($ad);
			if ($coords[0]!='')
			{
					$sql ="SELECT fk_soc FROM ".MAIN_DB_PREFIX."societe_gps WHERE fk_soc=".$obj->rowid;
					$resqlGPS=$db->query($sql);
					if ($resqlGPS)
					{
						if ($db->num_rows($resqlGPS)<1)
						{
							$sql="INSERT INTO ".MAIN_DB_PREFIX."societe_gps(fk_soc,latitude,longitude) VALUES(".$obj->rowid.",'".$coords[0]."','".$coords[1]."')";
						}
						else
						{
							$sql="UPDATE ".MAIN_DB_PREFIX."societe_gps SET latitude='".$coords[0]."',longitude='".$coords[1]."' WHERE fk_soc=".$obj->rowid;
						}
						$db->free($resqlGPS);
						$db->query($sql);
					}
					echo "<br>(".$obj->rowid.") ".$obj->nom." : ".utf8_decode($obj->address." ".$obj->zip." ".$obj->town)." : lat=".$coords[0].", long=".$coords[1]."<br>";
				}
		}
		$i++;
        }
        $db->free($resql);
    }
    else
    {
	echo "Erreur SQL:".$db->lasterror();
    }
}

testTableExist();
getGPSOfThirdParties();

?>