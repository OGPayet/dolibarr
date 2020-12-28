<?php
$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");       // For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php"); // For "custom"


dol_include_once('/parcautomobile/class/modeles.class.php');
dol_include_once('/parcautomobile/class/marques.class.php');
dol_include_once('/core/class/html.form.class.php');

$langs->load('parcautomobile@parcautomobile');

$modname = $langs->trans("modeles");
// print_r(picto_from_langcode($langs->defaultlang));die();
// Initial Objects
$modeles        = new modeles($db);
$marque         = new marques($db);
$form           = new Form($db);

$var 				= true;
$sortfield 			= ($_GET['sortfield']) ? $_GET['sortfield'] : "rowid";
$sortorder 			= ($_GET['sortorder']) ? $_GET['sortorder'] : "DESC";
$id 				= $_GET['id'];
$action   			= $_GET['action'];

if (!$user->rights->parcautomobile->gestion->consulter) {
	accessforbidden();
}


$srch_ref 		= GETPOST('srch_ref');
$srch_label 		= GETPOST('srch_label');
$srch_marque   = GETPOST('srch_marque');

$date = explode('/', $srch_date);
$date = $date[2]."-".$date[1]."-".$date[0];

$filter .= (!empty($srch_ref)) ? " AND rowid = ".$srch_ref."" : "";

$filter .= (!empty($srch_label)) ? " AND label like '%".$srch_label."%'" : "";

$filter .= (!empty($srch_marque)) ? " AND marque in (SELECT rowid from ".MAIN_DB_PREFIX."marques where label like '%".$srch_marque."%') " : "";


$limit 	= $conf->liste_limit+1;

$page 	= GETPOST("page",'int');
$page = is_numeric($page) ? $page : 0;
$page = $page == -1 ? 0 : $page;
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter") || $page < 0) {
	$filter = "";
	$offset = 0;
	$filter = "";
	$srch_label = "";
	$srch_ref = "";
	$srch_marque = "";
}

// echo $filter;

$nbrtotal = $modeles->fetchAll($sortorder, $sortfield, $limit, $offset, $filter);

$morejs  = array();
llxHeader(array(), $modname,'','','','',$morejs,0,0);

print_barre_liste($modname, $page, $_SERVER["PHP_SELF"], "", $sortfield, $sortorder, "", $nbrtotal, $nbrtotalnofiltr);


print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">'."\n";
	print '<input name="pagem" type="hidden" value="'.$page.'">';
	print '<input name="offsetm" type="hidden" value="'.$offset.'">';
	print '<input name="limitm" type="hidden" value="'.$limit.'">';
	print '<input name="filterm" type="hidden" value="'.$filter.'">';
	print '<input name="id_cv" type="hidden" value="'.$id_parcautomobile.'">';

	print '<div style="float: right; margin: 8px;">';
	print '<a href="card.php?action=add" class="butAction" >'.$langs->trans("Add").'</a>';
	print '</div>';

	print '<table id="table-1" class="noborder" style="width: 100%;" >';
		print '<thead>';

			print '<tr class="liste_titre">';
				print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"], "rowid", '', '', 'align="left"', $sortfield, $sortorder);
				print_liste_field_titre($langs->trans("label_model"),$_SERVER["PHP_SELF"], "label", '', '', 'align="left"', $sortfield, $sortorder);
				print_liste_field_titre($langs->trans("marque"),$_SERVER["PHP_SELF"], "marque", '', '', 'align="center"', $sortfield, $sortorder);
				print '<th align="center"></th>';
			print '</tr>';

			print '<tr class="liste_titre nc_filtrage_tr">';
				print '<td align="left"><input style="max-width: 129px;" class="" type="text" class="" id="srch_ref" name="srch_ref" value="'.$srch_ref.'"/></td>';
				print '<td align="left"><input class="" type="text" class="" id="srch_label" name="srch_label" value="'.$srch_label.'"/></td>';
				print '<td align="center"><input style="max-width: 129px;" class="" type="text" class="" id="srch_marque" name="srch_marque" value="'.$srch_marque.'"/></td>';
				print '<td align="center">';
					print '<input type="image" name="button_search"  src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
					print '&nbsp;<input type="image" name="button_removefilter"  src="'.img_picto($langs->trans("Search"),'searchclear.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'"></td>';
			print '</tr>';

		print '</thead>';
		print '<tbody>';
			$colspn = 7;
			if (count($modeles->rows) > 0) {
				for ($i=0; $i < count($modeles->rows) ; $i++) {
					$var = !$var;
					$item = $modeles->rows[$i];

					print '<tr '.$bc[$var].' >';
					print '<td align="left" style="">';
						print '<a href="'.dol_buildpath('/parcautomobile/modeles/card.php?id='.$item->rowid,2).'" >';
							print $item->rowid;
						print '</a>';
					print '</td>';

					print '<td align="left" style="">'.$item->label.'</td>';
					print '<td align="center" style="">';
						if($item->marque){
							$marque = new marques($db);
							$marque->fetch($item->marque);
							print '<a href="'.dol_buildpath('/parcautomobile/marques/card.php?id='.$item->marque,2).'">'.$marque->label.'</a>';
						}
						print '</td>';
						print '<td align="center"></td>';
					print '</tr>';
				}
			}else{
				print '<tr><td align="center" colspan="'.$colspn.'">'.$langs->trans("NoResults").'</td></tr>';
			}

		print '</tbody>';
	print '</table>';
print '</form>';

function field($titre,$champ){
	global $langs;
	print '<th class="" style="padding:5px; 0 5px 5px; text-align:center;">'.$langs->trans($titre).'<br>';
		print '<a href="?sortfield='.$champ.'&amp;sortorder=desc">';
		print '<span class="nowrap"><img src="'.dol_buildpath('/parcautomobile/img/1uparrow.png',2).'" alt="" title="Z-A" class="imgup" border="0"></span>';
		print '</a>';
		print '<a href="?sortfield='.$champ.'&amp;sortorder=asc">';
		print '<span class="nowrap"><img src="'.dol_buildpath('/parcautomobile/img/1downarrow.png',2).'" alt="" title="A-Z" class="imgup" border="0"></span>';
		print '</a>';
	print '</th>';
}

?>

<?php

llxFooter();