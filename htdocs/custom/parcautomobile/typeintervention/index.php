<?php
$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");       // For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php"); // For "custom" 


dol_include_once('/parcautomobile/class/typeintervention.class.php');
dol_include_once('/core/class/html.form.class.php');
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

$langs->load('parcautomobile@parcautomobile');

$modname = $langs->trans("typeintervention");
// print_r(picto_from_langcode($langs->defaultlang));die();
// Initial Objects
$typeintervention        = new typeintervention($db);
$form           = new Form($db);

$var 				= true;
$sortfield 			= ($_GET['sortfield']) ? $_GET['sortfield'] : "rowid";
$sortorder 			= ($_GET['sortorder']) ? $_GET['sortorder'] : "DESC";
$id 				= $_GET['id'];
$action   			= $_GET['action'];

if (!$user->rights->parcautomobile->lire) {
	accessforbidden();
}


$srch_label 		= GETPOST('srch_label');
$srch_color    = GETPOST('srch_color');
$srch_rowid    = GETPOST('srch_rowid');

$date = explode('/', $srch_date);
$date = $date[2]."-".$date[1]."-".$date[0];

$filter .= (!empty($srch_label)) ? " AND label like '%".$srch_label."%'" : "";

$filter .= (!empty($srch_rowid)) ? " AND rowid = ".$srch_rowid."" : "";


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
	$srch_color = "";
	$srch_module = "";
	$srch_date = "";
}

// echo $filter;

$nbrtotal = $typeintervention->fetchAll($sortorder, $sortfield, $limit, $offset, $filter);

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
					print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"], "rowid", '', '', 'align="center"', $sortfield, $sortorder);
					print_liste_field_titre($langs->trans("label_typeintervention"),$_SERVER["PHP_SELF"], "label", '', '', 'align="left"', $sortfield, $sortorder);
					print '<th align="center"></th>';
				print '</tr>';

				print '<tr class="liste_titre nc_filtrage_tr">';
					print '<td align="center"><input style="max-width: 129px;" class="" type="text" class="" id="srch_rowid" name="srch_rowid" value="'.$srch_rowid.'"/></td>';
					print '<td align="left"><input class="" type="text" class="" id="srch_label" name="srch_label" value="'.$srch_label.'"/></td>';
					print '<td align="center">';
						print '<input type="image" name="button_search"  src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
						print '&nbsp;<input type="image" name="button_removefilter"  src="'.img_picto($langs->trans("Search"),'searchclear.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'"></td>';
				print '</tr>';
			print '</thead>';

			print '<tbody>';
				$colspn = 7;
				if (count($typeintervention->rows) > 0) {
					for ($i=0; $i < count($typeintervention->rows) ; $i++) {
						$var = !$var;
						$item = $typeintervention->rows[$i];

						print '<tr '.$bc[$var].' >';
				    		print '<td align="center" style="">'; 
					    		print '<a href="'.dol_buildpath('/parcautomobile/typeintervention/card.php?id='.$item->rowid,2).'" >';
					    			print $item->rowid;
					    		print '</a>';
				    		print '</td>';
							print '<td align="left">'.$item->label.'</td>';
							print '<td align="center"></td>';
						print '</tr>';
					}
				}else{
					print '<tr><td align="center" colspan="'.$colspn.'">'.$langs->trans("NoResults").'</td></tr>';
				}
			print '</tbody>';
		print '</table>';
	print '</form>';


?>

<?php

llxFooter();