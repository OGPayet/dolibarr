<?php
/* Copyright (C) 2013-2017		Charlene Benke 		<charlie@patas-monkey.com>
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
 *	\file	   	htdocs/mylist/mylistdt.php
 *	\ingroup		mylist
 *	\brief	  	list of selected fields with datatable view
 */


$res=@include("../main.inc.php");					// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res)
	$res=@include("../../main.inc.php");		// For "custom" directory

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';


dol_include_once('/mylist/class/mylist.class.php');
dol_include_once("/mylist/core/modules/mylist/modules_mylist.php");

$socid=GETPOST('socid', 'int');
$rowid=GETPOST('rowid', 'int');
$action=GETPOST('action');

// load the mylist definition
$myliststatic = new Mylist($db);
$myliststatic->fetch($rowid);

if ($myliststatic->langs)
	foreach (explode(":", $myliststatic->langs) as $newlang)
		$langs->load($newlang);

$langs->load('mylist@mylist');
$langs->load('personalfields@mylist');

// Security check
$module='mylist';

if (! empty($user->societe_id))
	$socid=$user->societe_id;

if (! empty($socid)) {
	$objectid=$socid;
	$module='societe';
	$dbtable='&societe';
}

// voir les restriction
$result = restrictedArea($user, $module, $objectid, $dbtable);


/*
 * Builds the myList query
 */

$arrayTable =$myliststatic->listsUsed;
$sql = "SELECT DISTINCT ". $myliststatic->GetSqlFields($arrayTable);

// Replace the prefix tables
if ($dolibarr_main_db_prefix != 'llx_')
	$sql.= " ".preg_replace('/llx_/i', $dolibarr_main_db_prefix, $myliststatic->querylist);
else
	$sql.= " ".$myliststatic->querylist;

// init fields managment
if ($myliststatic->fieldinit) {
	$tblInitFields=explode(":", $myliststatic->fieldinit);
	foreach ($tblInitFields as $initfields) {
		$tblInitField=explode("=", $initfields);
		$valueinit = (GETPOST($tblInitField[0])?GETPOST($tblInitField[0]):$tblInitField[1]);
		// on prend la valeur par d�faut si la valeur n'est pas saisie...
		$sql=str_replace("#".$tblInitField[0]."#", $valueinit, $sql);
	}
}

// boucle sur les champs filtrables
// All tests are required to be compatible with all browsers
if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter.x") || GETPOST("button_removefilter"))
	$sqlfilter = "";	   	// on vire les filtre
else
	$sqlfilter= $myliststatic->GetSqlFilterQuery($arrayTable);

// pour g�rer le cas du where dans la query
// si y a des champs � filter et pas de where dans la requete de base
if ($sqlfilter && strpos(strtoupper($sql), "WHERE") == 0)
	$sqlfilter= " WHERE 1=1 ".$sqlfilter;

// pour g�rer le cas du filtrage selon utilisateur
if (strpos(strtoupper($sql), "#USER#") > 0)
	$sql=str_replace("#USER#", $user->id, $sql);

// pour g�rer le cas du filtrage selon utilisateur
if (strpos(strtoupper($sql), "#ENTITY#") > 0)
	$sql=str_replace("#ENTITY#", $conf->entity, $sql);


// pour g�rer le cas du filtrage selon utilisateur
if (strpos(strtoupper($sql), "#USERGROUP#") > 0) {
	$sqlg = "SELECT g.rowid, ug.entity as usergroup_entity";
	$sqlg.= " FROM ".MAIN_DB_PREFIX."usergroup as g, ".MAIN_DB_PREFIX."usergroup_user as ug";
	$sqlg.= " WHERE ug.fk_usergroup = g.rowid";
	$sqlg.= " AND ug.fk_user = ".$user->id;
	if (! empty($conf->multicompany->enabled) && $conf->entity == 1 && $user->admin && ! $user->entity)
		$sqlg.= " AND g.entity IS NOT NULL";
	else
		$sqlg.= " AND g.entity IN (0,".$conf->entity.")";

	$sqlg.= " ORDER BY g.nom";
	$result = $db->query($sqlg);
	$ret=array();

	if ($result)
		while ($obj = $db->fetch_object($result))
			if (! array_key_exists($obj->rowid, $ret))
				$ret[$obj->rowid]=$newgroup;
	$db->free($result);

	// if no usergroup associated on user
	$sql=str_replace("#USERGROUP#", (count($ret) > 0 ? implode(",", $ret):"0"), $sql);
}

// pour g�rer le cas du filtrage selon l'entit�
if (strpos(strtoupper($sql), "#ENTITY#") > 0)
	$sql=str_replace("#ENTITY#", $conf->entity, $sql);


// filtre sur l'id de l'�l�ment en mode tabs
$idreftab=(GETPOST('id')?GETPOST('id'):GETPOST('socid'));
$idcodereftab=GETPOST('code');

$form = new Form($db);

// mettre la gestion des onglets dans une librairie/class � part (customtabs like)
if (!empty($myliststatic->elementtab) && ($idreftab != "" || $idcodereftab !="")) {
	$myliststatic->element = $myliststatic->elementtab;
	$object=$myliststatic->element_setting();
	$myliststatic->element = "element";

	switch($myliststatic->elementtab) {
		case 'thirdparty' :
			$result = $object->fetch($idreftab, $idcodereftab);
			if ($idcodereftab !="")
				$idreftab = $object->id;
			$sqlfilter.=" AND s.rowid=".$idreftab;
			$sqlgroupby.=", s.rowid";
			break;

		case 'product' :
			$result = $object->fetch($idreftab, $idcodereftab);
			if ($idcodereftab !="")
				$idreftab = $object->id;
			$sqlfilter.=" AND p.rowid=".$idreftab;
			$sqlgroupby.=", p.rowid";
			break;

		case 'project' :
			$result = $object->fetch($idreftab, $idcodereftab);
			if ($idcodereftab !="")
				$idreftab = $object->id;
			$sqlfilter.=" AND p.rowid=".$idreftab;
			$sqlgroupby.=", p.rowid";
			break;

		case 'CategSociete' :
			$result = $object->fetch($idreftab, $idcodereftab);
			if ($idcodereftab !="")
				$idreftab = $object->id;
			$sqlfilter.=" AND c.rowid=".$idreftab;
			$sqlgroupby.=", c.rowid";
			break;

		case 'CategProduct' :
			$result = $object->fetch($idreftab, $idcodereftab);
			if ($idcodereftab !="")
				$idreftab = $object->id;
			$sqlfilter.=" AND c.rowid=".$idreftab;
			$sqlgroupby.=", c.rowid";
			break;
	}
}

// on positionne les champs � filter avant un group by et/ou un order by
if ((strpos(strtoupper($sql), 'GROUP BY') > 0) || (strpos(strtoupper($sql), 'ORDER BY') > 0)) {
	if (strpos(strtoupper($sql), 'GROUP BY') > 0) {
		// on d�coupe le sql
		$sqlleft=substr($sql, 0, strpos(strtoupper($sql), 'GROUP BY')-1);
		$sqlright=substr($sql, strpos(strtoupper($sql), 'GROUP BY'));
		$sql=$sqlleft." ".$sqlfilter." ".$sqlright;
	} elseif (strpos(strtoupper($sql), 'ORDER BY') > 0) {
		// on d�coupe le sql
		$sqlleft=substr($sql, 0, strpos(strtoupper($sql), 'ORDER BY')-1);
		$sqlright=substr($sql, strpos(strtoupper($sql), 'ORDER BY'));

		/* -- Open DSI - Add user filter before "order by" -- Begin */
		//$sql=$sqlleft." ".$sqlgroupby." ".$sqlright;
        $sql=$sqlleft." ".$sqlfilter." ".$sqlgroupby." ".$sqlright;
        /* -- Open DSI - Add user filter before "order by" -- End */
	}
} else
	$sql.= $sqlfilter;


// if we don't allready have a group by
if (strpos(strtoupper($sql), 'GROUP BY') == 0)
	$sql.= $myliststatic->GetGroupBy($arrayTable);

// Si il y a un order by pr�d�fini dans la requete ou un rollup on d�sactive le tri
if (stripos($myliststatic->querylist, 'ORDER BY') + stripos($myliststatic->querylist, 'WITH ROLLUP') == 0)
	$sql.= ' ORDER BY '.$sortfield.' '.$sortorder;

/*
 * Actions
 */


if (GETPOST('export')!="")
	include  './core/actions_export.inc.php';

if (GETPOST('dojob')!="") {
	// on r�cup�re les id � traiter
	$tbllistcheck= GETPOST('checksel');
	// on v�rifie qu'il y a au moins une ligne de coch�e
	if (is_array($tbllistcheck)) {
		foreach ($tbllistcheck as $rowidsel) {
			// on r�cup�re la requete � lancer
			$sqlQuerydo=$myliststatic->querydo;
			// on lance la requete
			$sqlQuerydo=str_replace("#ROWID#", $rowidsel, $sqlQuerydo);
			dol_syslog("mylist.php"."::sqlQuerydo=".$sqlQuerydo);
			//print $sqlQuerydo;
			$resultdo=$db->query($sqlQuerydo);
		}
	}
}

if ($action== 'builddoc') {
	/*
	 * Generate mylist document
	 * define into /core/modules/mylist/modules_mylist.php
	 */
	$ret = $myliststatic->fetch($rowid); // Reload to get new records
	// on conserve la requete sql pour l'�dition
	$myliststatic->sqlquery=$sql;

	// Save last template used to generate document
	$myliststatic->id= $rowid;
	if (GETPOST('model'))
		$myliststatic->setDocModel($user, GETPOST('model', 'alpha'));

	// Define output language
	$outputlangs = $langs;
	if (! empty($conf->global->MAIN_MULTILANGS)) {
		$outputlangs = new Translate("", $conf);
		$newlang = (GETPOST('lang_id') ? GETPOST('lang_id') : $myliststatic->thirdparty->default_lang);
		$outputlangs->setDefaultLang($newlang);
	}

//var_dump($myliststatic);

	$result=mylist_create($db, $myliststatic, GETPOST('model', 'alpha'), $outputlangs);

	if ($result <= 0) {
		setEventMessages($myliststatic->error, $myliststatic->errors, 'errors');
		$action='';
	}
}

// Remove file in doc form
if ($action == 'remove_file' ) {
	if ($myliststatic->rowid > 0) {
		require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

		$langs->load("other");
		$upload_dir = $conf->mylist->dir_output;
		$file = $upload_dir . '/' . GETPOST('file');
		$ret = dol_delete_file($file, 0, 0, 0, $myliststatic);
		if ($ret)
			setEventMessage($langs->trans("FileWasRemoved", GETPOST('file')));
		else
			setEventMessage($langs->trans("ErrorFailToDeleteFile", GETPOST('file')), 'errors');
	}
}

// hook pour les actions suppl�mentaires
$hookmanager->initHooks(array('mylist'));
$parameters=array('id'=>$myliststatic->rowid, 'sql'=>$sql);
// Note that $action and $object may have been modified by some hooks
$reshook=$hookmanager->executeHooks('doactions', $parameters, $myliststatic, $action);

/*
 * View
 */

$arrayofcss = array();
$arrayofjs = array();
// pour g�rer l'absence de datatable dans myList dans la V5 :-(
if (DOL_VERSION >= "5.0.0") {
	// on constitue le tableaux des js et css pour datatable
	$arrayofcss = array
		( '/mylist/includes/jquery/plugins/datatables/media/css/jquery.dataTables.min.css'
		, '/mylist/includes/jquery/plugins/datatables/extensions/Buttons/css/buttons.dataTables.min.css'
		, '/mylist/includes/jquery/plugins/datatables/extensions/ColReorder/css/colReorder.dataTables.min.css'
		);

	$arrayofjs = array
		( '/mylist/includes/jquery/plugins/datatables/media/js/jquery.dataTables.min.js'
		, '/mylist/includes/jquery/plugins/datatables/extensions/Buttons/js/dataTables.buttons.min.js'
		, '/mylist/includes/jquery/plugins/datatables/extensions/Buttons/js/buttons.colVis.min.js'
		, '/mylist/includes/jquery/plugins/datatables/extensions/colVis/js/dataTables.colVis.min.js'
		, '/mylist/includes/jquery/plugins/datatables/extensions/ColReorder/js/dataTables.colReorder.min.js'
		);
}
else
	$conf->global->MAIN_USE_JQUERY_DATATABLES=1;

$helpHeader = 'EN:mylist_EN|FR:mylist_FR|ES:mylist_ES';

// mode onglet : il est actif et une cl� est transmise
// on met la g�n�ration dans une classe (idem customTabs
if (!empty($myliststatic->elementtab) && $idreftab != "") {
	$form = new Form($db);
	$myliststatic->element = $myliststatic->elementtab;
	$myliststatic->tabs_head_element(
					$myliststatic->rowid, $myliststatic->label,
					$helpHeader, '', 0, 0, $arrayofjs, $arrayofcss, ''
	);
	$myliststatic->element = "element";
} else
	llxHeader('', $myliststatic->label, $helpHeader, '', 0, 0, $arrayofjs, $arrayofcss, '');

$form = new Form($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);

// gestion de la limite des lignes si on ne force pas � tous voir
if ($myliststatic->forceall != 1) {
	$limit = ($conf->global->MYLIST_NB_ROWS ? $conf->global->MYLIST_NB_ROWS : 25);
	$sql.= $db->plimit($limit * 4);
}

dol_syslog("mylistdt.php"."::sql=".$sql);
$result=$db->query($sql);

if ($result) {

	$num = $db->num_rows($resql);
	$i = 0;
	$titre = $myliststatic->label;
	if ($myliststatic->active == 0)  // lancement de la requete � partir du menu mylist
		$titre .=  " - <b>TEST MODE</b>";

	print load_fiche_titre($titre, '', 'mylist@mylist', 0, 0);

	print $myliststatic->description.'<br>';

	//  pour les tests on affiche la requete SQL pour les liste inactive
	if ($myliststatic->active == 0)  // lancement de la requete � partir du menu mylist
		print "<br>".$sql."<br><br>";

	// Lignes des champs de filtre
	print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="rowid" value="'.$rowid.'">';
	print '<input type="hidden" name="id" value="'.$idreftab.'">';

	// champs filtr�s, champ personnalis�s et case � cocher

	// boucle sur les champs filtrables
	print $myliststatic->GenFilterFieldsTables($arrayTable);

	// si il y a des champs param�trables
	//print '<div STYLE="float:left;"><table style="height:36px;" width=100% class="noborder">';
	// gestion des champs personnalis�s
	if (! empty($myliststatic->fieldinit)) {
		print '<div STYLE="float:left;">';
		print '<table style="height:36px;" width=100% class="noborder">';
		print '<tr class="liste_titre"><td height=31px>';
		print $myliststatic->GenFilterInitFieldsTables();
		print '</td></tr>';
		print '</table>';
		print '</div>';
	}

	print '<div STYLE="float:left;">';
	if (DOL_VERSION >= "4.0.0") {
		print '<table style="height:36px;" width=100% class="noborder">';
		print '<tr class="liste_titre"><td height=31px>';
		$searchpitco=$form->showFilterAndCheckAddButtons(0);
		print $searchpitco;
		print '</td></tr>';
		print '</table>';
	} else {
		print '<input type="image" name="button_search"';
		print ' src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png"';
		print ' value="'.dol_escape_htmltag($langs->trans("Search")).'"';
		print ' title="'.dol_escape_htmltag($langs->trans("Search")).'">';
		print '<input type="image" class="liste_titre" name="button_removefilter"';
		print ' src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/searchclear.png"';
		print ' value="'.dol_escape_htmltag($langs->trans("SearchClear")).'"';
		print ' title="'.dol_escape_htmltag($langs->trans("SearchClear")).'">';
	}
	print '</div>';

	// si il y a des cases � coch� d'activ�
	foreach ($arrayTable as $key => $fields) {
		if ($fields['type'] == 'Check')
			if ($fields['alias']!="")
				$lineid=$fields['alias'];
			else
				$lineid=str_replace(array('.', '-'), "_", $fields['field']);
	}

	print '<br><br><br><br>';
	print '<table id="listtable" class="noborder" width="100%">';
	print "<thead>\n";
	print '<tr class="liste_titre">';
	foreach ($arrayTable as $key => $fields)
		if ($fields['type'] != 'CategoriesFilter')
			print "<th align='".$fields['align']."'>".$langs->trans($fields['name'])."</th>";
	// on affiche la case � cocher si il y a un champ check
	if ($lineid)
		print "<th>Sel.</th>";
	print '</tr>';
	print "</thead>\n";
	print "<tbody>\n";

	$total=0;
	$subtotal=0;

	// en mode datatable si un filtre est appliqu�
	if ($sqlfilter !="" || $myliststatic->forceall == 1)
		$limit = $num;				// on affiche tous les enregistrements
	else
		$limit = min($num, $limit);	// sinon on affiche soit le nombre, soit la limite

	while ($i < $limit) {
		$objp = $db->fetch_object($result);

		print '<tr >';
		foreach ($arrayTable as $key => $fields) {
			if ($fields['alias']!="")
				$fieldsname=$fields['alias'];
			else
				$fieldsname=str_replace(array('.', '-'), "_", $fields['field']);
			$tblelement=explode(":", $fields['param']);

			// � placer dans une classe
			switch($fields ['type']) {
				case 'Statut':
					// pour les champs de type statut
					print '<td nowrap="nowrap" align="'.$fields['align'].'">';
					$objectstatic = new $tblelement[0]($db);
					$rowidfields=str_replace('fk_statut', 'rowid', $fields['field']);
					$rowidfieldsname=str_replace(array('.', '-'), "_", $rowidfields);
					if ($objp->$rowidfieldsname)
						$objectstatic->fetch($objp->$rowidfieldsname);
					$objectstatic->statut=$objp->$fieldsname;
					// for compatibility case
					$objectstatic->fk_statut=$objp->$fieldsname;
					print $objectstatic->getLibStatut(5);
					print '</td>';
					break;
				case 'List':
				case 'Text':
					if ($fields['param'] == "") {
						// si pas de param�tre, on affiche le champs de base
						print $myliststatic->genDefaultTD($fields['field'], $fields, $objp);
						break;
					}
					// pour les cl�s qui sont li� � un autre �l�ment
					print '<td nowrap="nowrap" align="'.$fields['align'].'">';

					switch(count($tblelement)) {
						// valeur issue d'une table
						case 3:
							$sqlelem = 'SELECT '.$tblelement[1].' as rowid, '.$tblelement[2].' as label';
							$sqlelem.= ' FROM '.MAIN_DB_PREFIX .$tblelement[0];
							$sqlelem.= ' WHERE '.$tblelement[1].'='.$objp->$fieldsname;
							$resqlf = $db->query($sqlelem);

							if ($resqlf) {
								$objf = $db->fetch_object($resqlf);
								print $objf->label;
							}
							break;

						// valeur li� � un �l�ment
						default :
							if ($tblelement[1]!="")
								dol_include_once($tblelement[1]);
							// seulement si le champs est renseign�
							if ($objp->$fieldsname) {
								$objectstatic = new $tblelement[0]($db);
								if ($fields ['type'] == 'List')
									$objectstatic->fetch($objp->$fieldsname);
								else
									$objectstatic->fetch(0, $objp->$fieldsname);
								if (method_exists($objectstatic, 'getNomUrl'))
									print $objectstatic->getNomUrl(1);
								else {
									//gerer le bug PHP 7 au cas ou
									$tmpfields=$tblelement[3];
									print $objectstatic->$tmpfields;
								}
							}
							break;
					}
					print '</td>';
					break;
				case 'TooltipList' :
					// requete de r�cup�ration des datas
					$tblquery=explode(":", $fields['param']);
					$sqltool= str_replace("#SEL#", "SELECT", $tblquery[0]);
					$sqltool= str_replace("#KEYID#", $objp->$fieldsname, $sqltool);
					if (MAIN_DB_PREFIX != 'llx_')
						$sqltool= " ".preg_replace('/llx_/i', MAIN_DB_PREFIX, $sqltool);

					$result=$db->query($sqltool);
					$num = 0;
					if ($result) {
						$num = $db->num_rows($resql);
						$tmptooltip='<table class="noborder">';
						$nbcol=0;
						$tmptooltip.='<tr class="liste_titre">';
						while ($finfo = $result->fetch_field()) {
							$tmptooltip.="<th>".$langs->trans($finfo->name)."</th>";
							$tabletype[$nbcol]=$finfo->type;
							$nbcol++;
						}
						$tmptooltip.='</tr>';

						$i = 0;
						while ($i < $num) {
							$objp = $db->fetch_object($result);
							$tmptooltip.='<tr>';
							foreach ($objp as $valfield ) {
								// boolean
								if ($tabletype[$nbcol]==1) {
									$tmptooltip.="<td align='center' >".yn($valfield)."</td>";
								} elseif ($tabletype[$nbcol]==10) {
									// date
									$tmptooltip.="<td align='center' width='70px' >";
									$tmptooltip.=dol_print_date($this->db->jdate($valfield), 'day')."</td>";
								} elseif ($tabletype[$nbcol]==253) {
									// text
									$tmptooltip.="<td align=left >".$valfield."</td>";
								} elseif ($tabletype[$nbcol]==5 || $tabletype[$nbcol]==3 || $tabletype[$nbcol]==246) {
									// num�rique
									$tmptooltip.="<td align=right  >".round($valfield, 2)."</td>";
								} else {
									// all the other type
									$tmptooltip.="<td align='center' title='type=".$tabletype[$nbcol]."'>";
									$tmptooltip.=$valfield."</td>";
								}
							}
							$tmptooltip.='</tr>';
							$i++;
						}
						$tmptooltip.='</table>';
					}

					if ($num > 0) {
						$link= '<a href=# title="'.str_replace("\"", "'", $tmptooltip).'"';
						$link.=' class="classfortooltip">'.$langs->trans("Tooltip").'('.$num.')</a>';
					}
					print '<td>'.$link.'</td>';

					break;
				case 'ExtrafieldList' :
					$tblinfolist=explode(":", $fields['param']);
					// param�trage de l'ExtraFields
					// 0 le nom de l'�l�ment contenant l'extrafields
					// 1 le nom du champs extrafields

					$elementtype=$tblinfolist[0];

					if ($elementtype == 'thirdparty') $elementtype='societe';
					if ($elementtype == 'contact') $elementtype='socpeople';

					// r�cup des valeurs possibles
					$sqllist = "SELECT param FROM ".MAIN_DB_PREFIX."extrafields";
					$sqllist.= " WHERE name = '".$tblinfolist[1]."'";
					$sqllist.= " AND elementtype = '".$elementtype."'";

					$resql=$db->query($sqllist);
					if ($resql) {
						$tab = $db->fetch_array($resql);
						$options = unserialize($tab['param']);
						$valList="";
						if (count($options['options']) > 0)
							foreach ($options['options'] as $key => $val)
								if ($objp->$fieldsname == $key)
									$valList = $val;
						print '<td>'.$valList.'</td>';
					}
					break;

				case 'CategoriesFilter' :
					break;
				default :
					// affichage par d�faut
					print $myliststatic->genDefaultTD($fields['field'], $fields, $objp);
					break;
			}
		}
		print "\n";
		if ($lineid) {
			print '<td align=right>';
			print '<input type="checkbox" name="checksel[]" value="'.$objp->$lineid.'">';
			print '</td>'."\n";
		}
		print "</tr>\n";
		$i++;
	}
	print '</tbody>';
	print '</table>';

	print '<br><br><table width=100%><tr>';
	print '<td width=50% >';

	if ($conf->global->MYLIST_ADDON_PDF && $myliststatic->model_pdf != -1 ) {
		$comref = dol_sanitizeFileName($myliststatic->label);
		$filedir = $conf->mylist->dir_output . '/' . $comref;
		$urlsource=$_SERVER["PHP_SELF"]."?rowid=".$myliststatic->rowid;
		$somethingshown=$formfile->show_documents(
						'mylist', $comref, $filedir, $urlsource, 1, 1, $myliststatic->model_pdf,
						1, 0, 0, 28, 0, '', '', '', $soc->default_lang
		);
	}
	print '</td>';
	print '<td align=left width=25% >';

	$sqlQuery=str_replace("SELECT", "#SEL#", $sql);
	print '<input type=hidden name=sqlquery value="'.$sqlQuery.'">';
	if ($conf->global->MYLIST_CSV_EXPORT =="1" && $myliststatic->export == 1)
		print "<input class='butAction' type=submit name='export' value='".$langs->trans("ExportCSV")."'>";

//	if (! empty($myliststatic->querydo))
//		print '<input class="butAction" type=submit name="dojob" value="'.$langs->trans('DoJob').'" >';

	print '</td>';
	print '<td align=left width=25% >';

	// on r�cup�re le tableau des champs � traiter
	$fieldsreport = array ();
	foreach ($myliststatic->listsUsed as $key) {
		$fieldreport=array();
		if ($key['sumreport']=="1")
			$fieldreport['sum']='1';

		if ($key['avgreport']=="1")
			$fieldreport['avg']=1;

		if ($fieldreport['sum'] + $fieldreport['avg'] > 0) {
			$fieldreport['name']=$key['name'];
			$fieldreport['totalsum']=0;
			if ($key['alias']!="")
				$codFields=$key['alias'];
			else
				$codFields=str_replace(array('.', '-'), "_", $key['field']);
			$fieldreport['alias']=$codFields;
			$fieldsreport[]=$fieldreport;
		}
	}

	// si il y a un tableau � r�aliser
	if (count($fieldsreport) >0) {
		$result=$db->query($sql);
		if ($result) {
			$i=0;
			$num = $db->num_rows($resql);
			while ($i < $num ) {
				$objp = $db->fetch_object($result);
				foreach ($fieldsreport as $key => $value) {
					if ($value['sum'] + $value['avg'] > 0) {
						// pour g�rer le pb php5 -> php7
						$valuealias=$value['alias'];
						$fieldsreport[$key]['totalsum']+=$objp->$valuealias;
					}
				}
				$i++;
			}
		}

		print  '<div class="titre">'.$langs->trans("SumAvgTable").'</div>';
		print '<table class="border" width="100%">';
		print '<tr class="liste_titre"><th>'.$langs->trans("FieldsUsed").'</td>';
		print '<td align=right>'.$langs->trans("Sum").'</td><td align=right>'.$langs->trans("Average").'</td></tr>';
		foreach ($fieldsreport as $key) {
			print '<tr><td align=left>'.$langs->trans($key['name']).'</td>';
			print '<td align=right>'.($key['sum']==1 ? $key['totalsum']:'').'</td>';
			print '<td align=right>'.($num > 0 ? ($key['avg']==1 ? price($key['totalsum']/$num, 2):''):'N/A');
			print '</td></tr>';
		}
		print '</table>';
	}

	print '</td>';
	print '</tr></table>';
	print '</form>';
} else {
	dol_print_error($db);
	print "<br><b>Query :</b><br>".$sql."<br><br>";
}


$parameters=array('id'=>$myliststatic->rowid, 'sql'=>$sql);
// Note that $action and $object may have been modified by some hooks
$reshook=$hookmanager->executeHooks('MylistOptions', $parameters, $myliststatic, $action);

print "\n";
print '<script type="text/javascript">'."\n";
print 'jQuery(document).ready(function() {'."\n";
print 'jQuery("#listtable").dataTable( {'."\n";
if (DOL_VERSION >="4.0.0")
	print '"sDom": \'Bltipr\','."\n";
else
	print '"sDom": \'lCtipr\','."\n";

print '"oColVis": {"buttonText": "'.$langs->trans('showhidecols').'" },'."\n";
print '"buttons" : [ "colvis" ],';
print '"language": { buttons: { "colvis": \''.$langs->trans('showhidecols').'\'} },';
print '"bPaginate": true,'."\n";
print '"bFilter": false,'."\n";
// need on new datables version
print '"colReorder": true,'."\n";
print '"sPaginationType": "full_numbers",'."\n";
// pour g�rer le format de certaine colonnes
print $myliststatic->gen_aoColumns($arrayTable, !empty($myliststatic->querydo));
// pour g�rer le trie par d�faut dans la requete SQL
print $myliststatic->gen_aasorting(1, "", $arrayTable, !empty($myliststatic->querydo));
print '"bJQueryUI": false,'."\n";
print '"oLanguage": {"sUrl": "'.$langs->trans('datatabledict').'" },'."\n";
print '"iDisplayLength": '.($conf->global->MYLIST_NB_ROWS?$conf->global->MYLIST_NB_ROWS:25).','."\n";
print '"aLengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],'."\n";
print '"bSort": true'."\n";
print '} );'."\n";
print '});'."\n";

// extension pour le trie

print 'jQuery.extend( jQuery.fn.dataTableExt.oSort, {';
// pour g�rer les . et les , des d�cimales et le blanc des milliers
print '"numeric-comma-pre": function ( a ) {';
print 'var x = (a == "-") ? 0 : a.replace( /,/, "." );';
print 'x = x.replace( " ", "" );';
print 'return parseFloat( x );';
print '},';
print '"numeric-comma-asc": function ( a, b ) {return ((a < b) ? -1 : ((a > b) ? 1 : 0));},';
print '"numeric-comma-desc": function ( a, b ) {return ((a < b) ? 1 : ((a > b) ? -1 : 0));},';

// pour g�rer les dates au format europ�enne
print '"date-euro-pre": function ( a ) {';
print 'if ($.trim(a) != "") {';
print 'var frDatea = $.trim(a).split("/");';
print 'var x = (frDatea[2] + frDatea[1] + frDatea[0]) * 1;';
print '} else { var x = 10000000000000; }';
print 'return x;';
print '},';
print '"date-euro-asc": function ( a, b ) {return a - b; },';
print '"date-euro-desc": function ( a, b ) {return b - a;}';
print '} );';
print "\n";
print '</script>'."\n";

print $myliststatic->genHideFields($arrayTable);

// End of page
llxFooter();
$db->close();