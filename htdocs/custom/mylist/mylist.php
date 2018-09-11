<?php
/* Copyright (C) 2013-2017		Charlie Benke 		<charlie@patas-monkey.com>
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
 *	\file	   	htdocs/mylist/mylist.php
 *	\ingroup		mylist
 *	\brief	  	list of selected fields
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
$tabsid=GETPOST('tabsid', 'int');
$action=GETPOST('action');

// load the mylist definition
$myliststatic = new Mylist($db);
$myliststatic->fetch(($rowid?$rowid:$tabsid));


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

// restricted area
$result = restrictedArea($user, $module, $objectid, $dbtable);

/*
 * Actions
 */

$limit = GETPOST("limit")?GETPOST("limit", "int"):$conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOST("page", 'int');
if ($page == -1) $page = 0;
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield='1';
if (! $sortorder) $sortorder='DESC';

if (empty($action)) $action='list';

$arrayTable =$myliststatic->listsUsed;

$arrayfields=array();
foreach ($arrayTable as $value) {
	if (! empty($value['alias']))
		$namefield=$value['alias'];
	else
		$namefield=str_replace(array('.', '-'), '_', $value['field']);

	// on n'affiche pas la colonne catégorie : uniquement pour le filtrage
	if ($value['type'] !='CategoriesFilter')
		$arrayfields[$namefield]=array(
						'label'=>$langs->trans($value['name']),
						'field'=>$value['field'], // compatibilité
						'alias'=>$namefield,
						'param'=>$value['param'],
						'type'=>$value['type'],
						'checked'=>$value['visible'],
						'position'=>$value['width'],
						'align'=>$value['align']
		);
}


if (GETPOST('cancel')) {
	$action='list';
	$massaction='';
}
if (! GETPOST('confirmmassaction') && $massaction != 'presend' && $massaction != 'confirm_presend')
	$massaction='';

$parameters=array();
// Note that $action and $object may have been modified by some hooks
$reshook=$hookmanager->executeHooks('doActions', $parameters, $object, $action);
if ($reshook < 0)
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

$contextpage="MYLIST_".$rowid;
$tmpvar="MAIN_SELECTEDFIELDS_".$contextpage;

if (empty($reshook)) {
	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	// All tests are required to be compatible with all browsers
	if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter.x") || GETPOST("button_removefilter")) {
		$user->conf->$tmpvar="";
		$sqlfilter = "";	   	// on vire les filtre
	} else
		$sqlfilter= $myliststatic->GetSqlFilterQuery($arrayTable);
}

// pour reseter le filtrage quand cela déconne
// si on a des anciens champs dans le paramétrage, on reset la config (idem si reset des filtres)
if (strpos($user->conf->$tmpvar, ".") > 0 || $user->conf->$tmpvar =="") {
	foreach ($arrayfields as $key => $value)
		if ($value['checked']=="1")
			$tmpfieldsactive.=$value['alias'].",";
	$user->conf->$tmpvar=$tmpfieldsactive;
}


$sql = "SELECT DISTINCT ". $myliststatic->GetSqlFields($arrayTable);

// Replace the prefix tables
if ($dolibarr_main_db_prefix != 'llx_')
	$sql.= " ".preg_replace('/llx_/i', $dolibarr_main_db_prefix, $myliststatic->querylist);
else
	$sql.= " ".$myliststatic->querylist;

// init fields managment
if ($myliststatic->fieldinit) {
	$tblInitFields=explode(":", $myliststatic->fieldinit);
	foreach ($tblInitFields as $initfields ) {
		$tblInitField=explode("=", $initfields);
		$valueinit = (GETPOST($tblInitField[0])?GETPOST($tblInitField[0]):$tblInitField[1]);
		// on prend la valeur par défaut si la valeur n'est pas saisie...
		$sql=str_replace("#".$tblInitField[0]."#", $valueinit, $sql);
	}
}

// boucle sur les champs filtrables
//$sqlfilter= $myliststatic->GetSqlFilterQuery($arrayTable);

// pour gérer le cas du where dans la query
// si y a des champs à filter et pas de where dans la requete de base
if ($sqlfilter && strpos(strtoupper($sql), "WHERE") == 0)
	$sqlfilter= " WHERE 1=1 ".$sqlfilter;


// pour gérer le cas du filtrage selon utilisateur
if (strpos(strtoupper($sql), "#USER#") > 0)
	$sql=str_replace("#USER#", $user->id, $sql);

// pour gérer le cas du filtrage selon utilisateur
if (strpos(strtoupper($sql), "#ENTITY#") > 0)
	$sql=str_replace("#ENTITY#", $conf->entity, $sql);

// pour gérer le cas du filtrage selon le groupe d'utilisateur
if (strpos(strtoupper($sql), "#USERGROUP#") > 0) {
	$sqlg = "SELECT g.rowid, ug.entity as usergroup_entity";
	$sqlg.= " FROM ".MAIN_DB_PREFIX."usergroup as g,";
	$sqlg.= " ".MAIN_DB_PREFIX."usergroup_user as ug";
	$sqlg.= " WHERE ug.fk_usergroup = g.rowid";
	$sqlg.= " AND ug.fk_user = ".$user->id;
	if (! empty($conf->multicompany->enabled) && $conf->entity == 1 && $user->admin && ! $user->entity)
		$sqlg.= " AND g.entity IS NOT NULL";
	else
		$sqlg.= " AND g.entity IN (0,".$conf->entity.")";

	$sqlg.= " ORDER BY g.nom";
	$result = $db->query($sqlg);
	$ret=array();

	if ($result) {
		while ($obj = $db->fetch_object($result)) {
			if (! array_key_exists($obj->rowid, $ret))
				$ret[$obj->rowid]=$newgroup;
		}
		$db->free($result);
	}

	// if no usergroup associated on user
	$sql=str_replace("#USERGROUP#", (count($ret) > 0 ? implode(",", $ret):"0"), $sql);
}

// pour gérer le cas du filtrage selon l'entité
if (strpos(strtoupper($sql), "#ENTITY#") > 0)
	$sql=str_replace("#ENTITY#", $conf->entity, $sql);


// filtre sur l'id de l'élément en mode tabs
$idreftab=(GETPOST('id')?GETPOST('id'):GETPOST('socid'));
$idcodereftab=GETPOST('code');

$form = new Form($db);

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

// on positionne les champs à filter avant un group by ou un order by
if ((strpos(strtoupper($sql), 'GROUP BY') > 0) || (strpos(strtoupper($sql), 'ORDER BY') > 0)) {
	if (strpos(strtoupper($sql), 'GROUP BY') > 0) {
		// on découpe le sql
		$sqlleft=substr($sql, 0, strpos(strtoupper($sql), 'GROUP BY')-1);
		$sqlright=substr($sql, strpos(strtoupper($sql), 'GROUP BY'));
		$sql=$sqlleft." ".$sqlfilter." ".$sqlright;
	} elseif (strpos(strtoupper($sql), 'ORDER BY') > 0) {
		// on découpe le sql
		$sqlleft=substr($sql, 0, strpos(strtoupper($sql), 'ORDER BY')-1);
		$sqlright=substr($sql, strpos(strtoupper($sql), 'ORDER BY'));
		$sql=$sqlleft." ".$sqlgroupby." ".$sqlright;
	}
} else
	$sql.= $sqlfilter;

// if we don't allready have a group by
if (strpos(strtoupper($sql), 'GROUP BY') == 0)
	$sql.= $myliststatic->GetGroupBy($arrayTable);

// Si il y a un order by prédéfini dans la requete ou un rollup on désactive le tri
if (stripos($myliststatic->querylist, 'ORDER BY') + stripos($myliststatic->querylist, 'WITH ROLLUP') == 0)
	$sql.= ' ORDER BY '.$sortfield.' '.$sortorder;


if (GETPOST('export')!="")
	include  './core/actions_export.inc.php';

if (GETPOST('dojob')!="") {
	// on récupère les id à traiter
	$tbllistcheck= GETPOST('checksel');
	// on vérifie qu'il y a au moins une ligne de cochée
	if (is_array($tbllistcheck)) {
		foreach ($tbllistcheck as $rowidsel) {
			// on récupère la requete à lancer
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
	// on conserve la requete sql pour l'édition
	$myliststatic->sqlquery=$sql;

	// Save last template used to generate document
	$myliststatic->id= $rowid;
	if (GETPOST('model'))
		$myliststatic->setDocModel($user, GETPOST('model', 'alpha'));

	// Define output language
	$outputlangs = $langs;
	if (! empty($conf->global->MAIN_MULTILANGS)) {
		$outputlangs = new Translate("", $conf);
		$newlang = (GETPOST('lang_id') ? GETPOST('lang_id') : $object->thirdparty->default_lang);
		$outputlangs->setDefaultLang($newlang);
	}

//var_dump($myliststatic);
	$result=mylist_create($db, $myliststatic, GETPOST('model', 'alpha'), $outputlangs);

	if ($result <= 0) {
		setEventMessages($object->error, $object->errors, 'errors');
		$action='';
	}
} else if ($action == 'remove_file' ) {
	// Remove file in doc form
	if ($myliststatic->rowid > 0) {
		require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

		$langs->load("other");
		$upload_dir = $conf->mylist->dir_output;
		$file = $upload_dir . '/' . GETPOST('file');
		$ret = dol_delete_file($file, 0, 0, 0, $object);
		if ($ret)
			setEventMessage($langs->trans("FileWasRemoved", GETPOST('file')));
		else
			setEventMessage($langs->trans("ErrorFailToDeleteFile", GETPOST('file')), 'errors');
	}
}

/*
 * View
 */

$htmlother=new FormOther($db);

$helpHeader = 'EN:mylist_EN|FR:mylist_FR|ES:mylist_ES';

// mode onglet : il est actif et une clé est transmise
if (!empty($myliststatic->elementtab) && $idreftab != "") {
	// attention le header est maintenant inclus dans le commonobjectpatas-tabs_head_element

	$form = new Form($db);
	$myliststatic->element = $myliststatic->elementtab;
	$myliststatic->tabs_head_element($myliststatic->rowid, $myliststatic->label, $helpHeader);
	$myliststatic->element = "element";
} else {
	llxHeader('', $myliststatic->label, $helpHeader);
}

$form = new Form($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);

// construction de la requete sql
// gestion de la limite des lignes si on ne force pas à tous voir
if ($myliststatic->forceall != 1) {
	$limit = $conf->global->MYLIST_NB_ROWS;
	if (!$limit)
		$limit = 25;
	$sql.= $db->plimit($limit + 1, $offset);
} else
	$sql.= $db->plimit($limit + 1, $offset);

$titre= $myliststatic->label;
//  pour les tests on affiche la requete SQL
if ($myliststatic->active ==0)  // lancement de la requete à partir du menu mylist
	$titre.=" - <b>TEST MODE</b>";

dol_syslog("mylist.php"."::sql=".$sql);
$result=$db->query($sql);

if ($result) {
	$num = $db->num_rows($resql);
	$i = 0;

	// All tests are required to be compatible with all browsers
	if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter.x") || GETPOST("button_removefilter"))
		$param="rowid=".$rowid;
	else
		$param="rowid=".$rowid.$myliststatic->GetParamFilter($arrayTable);

	if ($idreftab >0 )
		$param.="&id=".$idreftab;

	print_barre_liste(
					$titre, $page, $_SERVER["PHP_SELF"], "&".$param,
					$sortfield, $sortorder,
					'', $num, $nbtotalofrecords,
					'mylist.png@mylist', 0, '', '', $limit
	);

	print $myliststatic->description.'<br>';

	if ($myliststatic->active ==0)  // lancement de la requete à partir du menu mylist
		 print"<br>".$sql."<br><br>";

	// Lignes des champs de filtre
	print '<form action="'.$_SERVER["PHP_SELF"].'" method="post" name="formulaire">';
	print '<input type="hidden" name="rowid" value="'.$rowid.'">';
	print '<input type="hidden" name="id" value="'.$idreftab.'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';

	// champs filtrés, champ personnalisés et case à cocher
	$varpage="MYLIST_".$rowid;

	// This also change content of $arrayfields
	$selectedfields=$form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);

	print $myliststatic->GenFilterFieldsTables($arrayTable);

	print '<div STYLE="float:left;" ><table width=100% class="noborder">';
	print '<tr class="liste_titre">';
	if (! empty($myliststatic->fieldinit))
		print $myliststatic->GenFilterInitFieldsTables();

	print '<td height=31px >';
	if (DOL_VERSION >= "4.0.0")
		print $form->showFilterAndCheckAddButtons(0);
	else {
		print '<input type="image" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png"';
		print ' value="'.dol_escape_htmltag($langs->trans("Search")).'"';
		print ' title="'.dol_escape_htmltag($langs->trans("Search")).'">';
		print '<input type="image" class="liste_titre" name="button_removefilter"';
		print ' src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/searchclear.png"';
		print ' value="'.dol_escape_htmltag($langs->trans("SearchClear")).'"';
		print ' title="'.dol_escape_htmltag($langs->trans("SearchClear")).'">';
	}
	print '</td></tr>';
	print '</table></div>';

	print '<div STYLE="float:left; width:100%;" class="div-table-responsive">';
	print '<table class="noborder">'."\n";
	print '<tr class="liste_titre">';

	// si il y a une requete de mise à jour
	foreach ($arrayfields as $key => $fields)
		if ($fields['checked']=='1')
			print_liste_field_titre(
							$fields['label'], $_SERVER["PHP_SELF"], $fields['alias'], '',
							$param, 'width="'.$fields['position'].'" align="'.$fields['align'].'"',
							$sortfield, $sortorder
			);
	if ($lineid)
		print "<th></th>";
	print_liste_field_titre(
					$selectedfields, $_SERVER["PHP_SELF"], "", '',
					'', 'align="right"', $sortfield, $sortorder, 'maxwidthsearch '
	);

	if ($lineid)
		print '<th class="liste_titre" align="right"></th>';

	print "</tr>\n";
	print "<tbody>\n";
	$var=true;
	$total=0;
	$subtotal=0;

	// en mode standard on affiche la limite au max
	$limit=min($num, $limit);

	while ($i < $limit) {
		$objp = $db->fetch_object($result);
		$var=!$var;
		print '<tr '.$bc[$var].'>';

		foreach ($arrayfields as $key => $fields) {
			if ($fields['checked']=='1') {
				if ($fields['alias']!="")
					$fieldsname=$fields['alias'];
				else
					$fieldsname=str_replace(array('.', '-'), "_", $fields['field']);
				$tblelement=explode(":", $fields['param']);

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
							print $myliststatic->genDefaultTD($fields['field'], $fields, $objp);
							break;
						}
						// pour les clés qui sont lié à un autre élément
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

							// valeur lié à un élément
							default :
								if ($tblelement[1]!="")
									dol_include_once($tblelement[1]);
								// seulement si le champs est renseigné
								if ($objp->$fieldsname) {
									$objectstatic = new $tblelement[0]($db);
									if ($fields ['type'] == 'List')
										$objectstatic->fetch($objp->$fieldsname);
									else
										$objectstatic->fetch(0, $objp->$fieldsname);
									if (method_exists($objectstatic, 'getNomUrl'))
										print $objectstatic->getNomUrl(1);
									else
										print $objectstatic->$tblelement[3];
								}
								break;
						}
						print '</td>';
						break;
					case 'TooltipList' :
							print gettooltiplist($fields['param'], $objp->$fieldsname);
						break;
					case 'ExtrafieldList' :
						$tblinfolist = explode(":", $fields['param']);
						$elementtype = $tblinfolist[0];

						if ($elementtype == 'thirdparty')
							$elementtype='societe';
						if ($elementtype == 'contact')
							$elementtype='socpeople';

						// récup des valeurs possibles
						$sqlexf = "SELECT param";
						$sqlexf.= " FROM ".MAIN_DB_PREFIX."extrafields";
						$sqlexf.= " WHERE name = '".$tblinfolist[1]."'";
						$sqlexf.= " AND elementtype = '".$elementtype."'";

						$resql=$db->query($sqlexf);
						$out="";
						if ($resql) {
							$tab = $db->fetch_array($resql);
							$options = unserialize($tab['param']);

							if (count($options['options']) > 0)
								foreach ($options['options'] as $key => $val)
									if ($objp->$fieldsname == $key)
										$out = $val;
						}
						print '<td>'.$out.'</td>';
						break;

					default :
						// affichage par défaut
						print $myliststatic->genDefaultTD($fields['field'], $fields, $objp);
						break;
				}
			}
		}
		// si il y a une requete de mise à jour
		if ($lineid) {
			print "\n";
			print '<td align=right>';
			print '<input type="checkbox" name="checksel[]" value="'.$objp->$lineid.'">';
			print '</td>';
		}
		print "<td></td></tr>\n";
		$i++;
	}
	print '</tbody>';
	print '</table>';
	print '</div>';

	print '<br><br><table width=100%><tr>';
	print '<td width=50% >';

	if ($conf->global->MYLIST_ADDON_PDF && $myliststatic->model_pdf != -1) {
		$comref = dol_sanitizeFileName($myliststatic->label);
		$filedir = $conf->mylist->dir_output . '/' . $comref;
		$urlsource=$_SERVER["PHP_SELF"]."?rowid=".$myliststatic->rowid;
		$somethingshown=$formfile->show_documents(
						'mylist', $comref, $filedir, $urlsource, 1, 1, $myliststatic->model_pdf,
						1, 0, 0, 28, 0, '', '', '', $soc->default_lang
		);
	}
	print '</td>';
	print '<td align=left width=25% valign=top >';

	$sqlQuery=str_replace("SELECT", "#SEL#", $sql);
	print '<input type=hidden name=sqlquery value="'.$sqlQuery.'">';
	if ($conf->global->MYLIST_CSV_EXPORT =="1" && $myliststatic->export == 1)
		print "<input class='butAction' type=submit name='export' value='".$langs->trans("ExportCSV")."'>";

	if ($lineid)
		print '<input class="butAction" type=submit name="dojob" value="'.$langs->trans('DoJob').'" >';

	print '</td>';
	print '<td align=left width=25% >';
	$hookmanager->initHooks(array('mylist'));
	$parameters=array('id'=>$myliststatic->rowid, 'sql'=>$sql);
	// Note that $action and $object may have been modified by some hooks
	$reshook=$hookmanager->executeHooks('MylistOptions', $parameters, $myliststatic, $action);
	print '</td>';
	print '</tr></table>';
	print '</form>';
}
else
	dol_print_error($db);

	print "\n";
	print '<script type="text/javascript">'."\n";
	print 'jQuery(document).ready(function() {'."\n";

	print "$('.chkall').click(function(event) {"."\n";
	print "	if (this.checked) { // check select status"."\n";
	print "$('.'+ event.target.id).each(function() { //loop through each checkbox"."\n";
	print "this.checked = true;  "."\n";
	print "});"."\n";
	print "}else{"."\n";
	print "$('.'+ event.target.id).each(function() { //loop through each checkbox"."\n";
	print "this.checked = false; "."\n";
	print "});"."\n";
	print "}	});"."\n";

	print '} );';
	print "\n";
	print '</script>'."\n";
// End of page
llxFooter();
$db->close();