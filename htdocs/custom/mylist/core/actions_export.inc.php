<?php
$sql = (GETPOST('sqlquery') ? GETPOST('sqlquery') : $sql);
$sql = str_replace("#SEL#", "SELECT", $sql);
// pour virer la limite de l'export
$sql = str_replace($db->plimit($limit + 1, $offset), "", $sql);

$sep = ($conf->global->MYLIST_EXPORT_SEPARATOR ? $conf->global->MYLIST_EXPORT_SEPARATOR : ";");
if ($sep == "__B__")
		$sep = '\t';

header('Content-Encoding: UTF-8');
header('Content-type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment;filename=mylist_export'.$rowid.'.csv');
print "\xEF\xBB\xBF"; // UTF-8 BOM
$tmp="";
// génération des entete des colonnesw
foreach ($arrayTable as $key => $fields) {
	if (! empty($fields['alias']))
		$tmp.=$fields['alias'];
	else
		// pour gérer les . des définitions de champs
		$tmp.=str_replace(array('.', '-'), '_', $fields['field']);
	$tmp.=$sep;
}
// on enlève la dernière virgule et l'espace en fin de ligne
print substr($tmp, 0, -1)."\n";

dol_syslog("mylist.php"."::export sql=".$sql);
$result=$db->query($sql);
//print $sql;
// génération des lignes d'exports
if ($result) {
	$num = $db->num_rows($resql);

	$i = 0;
	// on boucle sur les lignes de résultats
	while ($i < $num) {
		$objp = $db->fetch_object($result);
		$tmp="";

		//var_dump($objp);
		foreach ($arrayTable as $key => $fields) {
			if ($fields['alias']!="")
				$fieldsname=$fields['alias'];
			else
				$fieldsname=str_replace(array('.', '-'), "_", $fields['field']);

			if (strpos($fields['field'], 'fk_statut') > 0) {
				$tblelement=explode(":", $fields['param']);
				if ($tblelement[1]!="")
					dol_include_once($tblelement[1]);

				$objectstatic = new $tblelement[0]($db);
				$objectstatic->statut=$objp->$fieldsname;
				// for compatibility case
				$objectstatic->fk_statut=$objp->$fieldsname;
				if ($objp->f_paye == 1)
					$objectstatic->paye=1;
				$tmp.=html_entity_decode(
								strip_tags($objectstatic->getLibStatut(1)),
								ENT_COMPAT | ENT_HTML401, "ISO-8859-1"
				);
				$tmp.=$sep;
			} elseif (
				(	strpos($fields['field'], '.rowid') > 0
					|| strpos($fields['field'], '.id') > 0
					|| strpos($fields['field'], '.fk_') > 0
				) && $fields['param']) {
				// pour virer les url des champs de type lien
				// seulement si le champs est renseigné
				if ($objp->$fieldsname) {
					// pour les clés qui sont lié à un autre élément
					$tblelement=explode(":", $fields['param']);
					if (count($tblelement) <= 3)
						$tmp.=$myliststatic->get_infolist($objp->$fieldsname, $fields['param']);
					else {
						if ($tblelement[1]!="")
							dol_include_once($tblelement[1]);

						$objectstatic = new $tblelement[0]($db);
						$objectstatic->id=$objp->$fieldsname;
						$objectstatic->fetch($objp->$fieldsname);
						$url=$objectstatic->getNomUrl(0);

						if ($objectstatic->label)
							$info = $objectstatic->label;
						elseif ($objectstatic->nom)
							$info = $objectstatic->nom;
						elseif ($objectstatic->ref !="")
							$info = $objectstatic->ref;

						$tmp.=$info;
					}
				}
				$tmp.=$sep;
			} else {
				// selon le type de données
				switch($fields['type']) {
					case "Price":
					case "Number":
						$tmp.=price($objp->$fieldsname);
						break;

					case "Percent":
						$tmp.=price($objp->$fieldsname * 100)." %";
						break;

					case "Date":
						$tmp.=dol_print_date($db->jdate($objp->$fieldsname), 'day');
						break;

					case "Boolean":
						$tmp.=yn($objp->$fieldsname);
						break;

					case 'ExtrafieldList' :
						$tblinfolist = explode(":", $fields['param']);
						$elementtype = $tblinfolist[0];

						if ($elementtype == 'thirdparty')
							$elementtype='societe';
						if ($elementtype == 'contact')
							$elementtype='socpeople';

						// récup des valeurs possibles
						$sql = "SELECT param";
						$sql.= " FROM ".MAIN_DB_PREFIX."extrafields";
						$sql.= " WHERE name = '".$tblinfolist[1]."'";
						$sql.= " AND elementtype = '".$elementtype."'";

						$resql=$db->query($sql);
						$out="";
						if ($resql) {
							$tab = $db->fetch_array($resql);
							$options = unserialize($tab['param']);

							if (count($options['options']) > 0)
								foreach ($options['options'] as $key => $val)
									if ($objp->$fieldsname == $key)
										$out = $val;
						}
						$tmp.= $out;
						break;

					default:
						$value=$objp->$fieldsname;
						if ($conf->global->MYLIST_CRLF_REPLACE)
							$value=str_replace("\n", $conf->global->MYLIST_CRLF_REPLACE, $value);
						$tmp.='"';
						$tmp.=$value;
						//$tmp.=html_entity_decode ($value, ENT_COMPAT | ENT_HTML401, "UTF-8");
						$tmp.='"';
						break;
				}
				$tmp.=$sep;
			}
		}
		// et on vire toujours la dernière virgule
		print substr($tmp, 0, -1)."\n";
		$i++;
	}
}
$db->close();
exit;