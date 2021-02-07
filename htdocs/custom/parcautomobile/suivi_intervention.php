<?php
$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");       // For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php"); // For "custom" 


dol_include_once('/parcautomobile/class/interventions_parc.class.php');
dol_include_once('/parcautomobile/class/typeintervention.class.php');
dol_include_once('/parcautomobile/class/vehiculeparc.class.php');
dol_include_once('/core/class/html.form.class.php');
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

$langs->load('parcautomobile@parcautomobile');

$modname = $langs->trans("interventions_parc");
// print_r(picto_from_langcode($langs->defaultlang));die();
// Initial Objects
$typeintervention        = new typeintervention($db);
$interventions        = new interventions_parc($db);
$vehicules        = new vehiculeparc($db);
$user_        = new User($db);
$form           = new Form($db);

$var 				= true;
$sortfield 			= ($_GET['sortfield']) ? $_GET['sortfield'] : "rowid";
$sortorder 			= ($_GET['sortorder']) ? $_GET['sortorder'] : "DESC";
$id 				= $_GET['id'];
$action   			= $_GET['action'];

if (!$user->rights->parcautomobile->lire) {
	accessforbidden();
}


$srch_vehicule 		= GETPOST('srch_vehicule');
$srch_typeintervention    = GETPOST('srch_typeintervention');
$srch_prix    = GETPOST('srch_prix');
$srch_notes    = GETPOST('srch_notes');

if(GETPOST('srch_date')){
	$srch_date    = GETPOST('srch_date');
}

$date = explode('/', $srch_date);
$date = $date[2]."-".$date[1]."-".$date[0];

$filter .= (!empty($srch_label)) ? " AND label like '%".$srch_label."%'" : "";

$filter .= (!empty($srch_vehicule)) ? " AND vehicule = ".$srch_vehicule."" : "";
$filter .= (!empty($srch_typeintervention)) ? " AND typeintervention = ".$srch_typeintervention."" : "";
$filter .= (!empty($srch_prix)) ? " AND prix = ".$srch_prix."" : "";
$filter .= (!empty($srch_date)) ? " AND CAST(date as date) = ".$srch_date."" : "";
// $filter .= (!empty($srch_notes)) ? " AND notes like '%".$srch_notes."%'" : "";


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
	$srch_typeintervention = "";
	$srch_vehicule = "";
	$srch_date = "";
	$srch_prix = "";
}

// echo $filter;

$nbrtotal = $interventions->fetchAll($sortorder, $sortfield, $limit, $offset, $filter);

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
print '<a href="interventions_parc/card.php?action=add" class="butAction" >'.$langs->trans("Add").'</a>';
print '</div>';

print '<table id="table-1" class="noborder" style="width: 100%;" >';
print '<thead>';

print '<tr class="liste_titre">';


field($langs->trans("vehicule"),'vehicule');
field($langs->trans("label_typeintervention"),'typeintervention');
field($langs->trans("acheteur"),'acheteur');
field($langs->trans("fournisseur"),'fournisseur');
field($langs->trans("ref_facture"),'ref_facture');
field($langs->trans("date"),'date');
field($langs->trans("prix_inter"),'prix_inter');
print '<th align="center"></th>';


print '</tr>';
	print '<tr class="liste_titre nc_filtrage_tr">';

	print '<td align="center">'.$vehicules->select_with_filter($srch_vehicule).'</td>';

	print '<td align="center">'.$typeintervention->select_with_filter($srch_typeintervention).'</td>';

	print '<td align="center">'.$vehicules->select_conducteur($srch_acheteur).'</td>';

	print '<td align="center">'.$vehicules->select_fournisseur($srch_fournisseur).'</td>';

	print '<td align="center"><input style="max-width: 129px;" id="srch_ref_facture" name="srch_ref_facture" value="'.$srch_ref_facture.'"/></td>';
	print '<td align="center"><input style="max-width: 129px;" class="datepickerparc" autocomplete="off" id="srch_date" name="srch_date" value="'.$srch_date.'"/></td>';
	print '<td align="center"><input style="max-width: 129px;" id="srch_prix" name="srch_prix" value="'.$srch_prix.'"/></td>';

	print '<td align="center">';
		print '<input type="image" name="button_search"  src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
		print '&nbsp;<input type="image" name="button_removefilter"  src="'.img_picto($langs->trans("Search"),'searchclear.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
	print '</td>';
print '</tr>';



print '</thead><tbody>';

	$colspn = 8;
	if (count($interventions->rows) > 0) {
		for ($i=0; $i < count($interventions->rows) ; $i++) {
			$var = !$var;
			$item = $interventions->rows[$i];
			$vehicules->fetch($item->vehicule);

			print '<tr '.$bc[$var].' >';
	    		print '<td align="center" style="">'; 
		    		print '<a href="'.dol_buildpath('/parcautomobile/interventions_parc/card.php?id='.$item->rowid,2).'" >';
		    			print $item->rowid;
		    		print '</a>';
	    		print '</td>';
				print '<td align="center"><a href="'.dol_buildpath('/parcautomobile/card.php?id='.$item->vehicule,2).'" >'.$vehicules->get_nom($item->vehicule,1).'</a></td>';
				$typeintervention->fetch($item->typeintervention);
				print '<td align="center">'.$typeintervention->label.'</td>';
				$user_->fetch($vehicules->conducteur);
				print '<td align="center">'.$user_->getNomUrl(1).'</td>';
				$user_->fetch($item->fournisseur);
				print '<td align="center">'.$user_->getNomUrl(1).'</td>';
				print '<td align="center">'.$item->ref_facture.'</td>';
				$date=explode('-', $item->date);
				$date=$date[2].'/'.$date[1].'/'.$date[0];
				print '<td align="center">'.$date.'</td>';
				print '<td align="center">'.number_format($item->prix).'</td>';
				print '<td align="center"></td>';
			print '</tr>';
		}
	}else{
		print '<tr><td align="center" colspan="'.$colspn.'">'.$langs->trans("NoResults").'</td></tr>';
	}

print '</tbody></table></form>';

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
<script>
	$('.datepickerparc').datepicker({
		dateFormat:'dd/mm/yy',
	})
</script>
<?php

llxFooter();