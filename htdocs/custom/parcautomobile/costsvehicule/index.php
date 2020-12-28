<?php
$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");       // For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php"); // For "custom"

require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';


dol_include_once('/parcautomobile/class/costsvehicule.class.php');
dol_include_once('/parcautomobile/class/typeintervention.class.php');
dol_include_once('/parcautomobile/class/vehiculeparc.class.php');
dol_include_once('/core/class/html.form.class.php');

$langs->load('parcautomobile@parcautomobile');

$modname = $langs->trans("costsvehicule");
// print_r(picto_from_langcode($langs->defaultlang));die();
// Initial Objects

$extrafields  = new ExtraFields($db);
$vehicules    = new vehiculeparc($db);
$object       = new costsvehicule($db);
$costs        = new costsvehicule($db);

$form         = new Form($db);

$var 				= true;
$sortfield 			= ($_GET['sortfield']) ? $_GET['sortfield'] : "rowid";
$sortorder 			= ($_GET['sortorder']) ? $_GET['sortorder'] : "DESC";
$id 				= $_GET['id'];
$action   			= $_GET['action'];

$extrafields->fetch_name_optionals_label($object->table_element);
$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

if (!$user->rights->parcautomobile->gestion->consulter) {
	accessforbidden();
}

// Extra fields
if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label']) > 0)
{
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val)
	{
		if (!empty($extrafields->attributes[$object->table_element]['list'][$key])){
			$arrayfields["ef.".$key] = array(
				'label'=>$extrafields->attributes[$object->table_element]['label'][$key],
				'checked'=>(($extrafields->attributes[$object->table_element]['list'][$key] < 0) ? 0 : 1),
				'position'=>$extrafields->attributes[$object->table_element]['pos'][$key],
				'enabled'=>(abs($extrafields->attributes[$object->table_element]['list'][$key]) != 3 && $extrafields->attributes[$object->table_element]['perms'][$key])
			);
		}
	}
}

$join = 'LEFT JOIN '.MAIN_DB_PREFIX.$object->table_element.'_extrafields as ef on ('.MAIN_DB_PREFIX.$object->table_element.'.rowid = ef.fk_object) ';


$srch_ref 		= GETPOST('srch_ref');
$srch_vehicule 		= GETPOST('srch_vehicule');
$srch_type    = GETPOST('srch_type');
$srch_prix    = GETPOST('srch_prix');
$srch_notes    = GETPOST('srch_notes');

if(GETPOST('srch_date')){
	$date = explode('/', GETPOST('srch_date'));
	$srch_date = $date[2]."-".$date[1]."-".$date[0];
}


$sql .= (!empty($srch_ref)) ? " AND rowid =".$srch_ref."" : "";

$sql .= (!empty($srch_vehicule)) ? " AND vehicule = ".$srch_vehicule."" : "";
$sql .= (!empty($srch_type)) ? " AND type like '%".$srch_type."%'" : "";
$sql .= (!empty($srch_prix)) ? " AND prix = ".$srch_prix."" : "";
$sql .= (!empty($srch_date)) ? " AND CAST(date as date) = '".$srch_date."'" : "";

if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label']) && !empty($search_array_options)) {
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
}

include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

$join = 'LEFT JOIN '.MAIN_DB_PREFIX.$object->table_element.'_extrafields as ef on ('.MAIN_DB_PREFIX.$object->table_element.'.rowid = ef.fk_object) ';


$param .= (!empty($srch_ref)) ? '&srch_ref='.urlencode($srch_ref) : '';
$param .= (!empty($srch_vehicule)) ? '&srch_vehicule='.urlencode($srch_vehicule) : '';
$param .= (!empty($srch_type)) ? '&srch_type='.urlencode($srch_type) : '';
$param .= (!empty($srch_prix)) ? '&srch_prix='.urlencode($srch_prix) : '';
$param .= (!empty($srch_date)) ? '&srch_date='.urlencode($srch_date) : '';


// die($sql);
$limit 	= $conf->liste_limit+1;

$page 	= GETPOST("page",'int');
$page = is_numeric($page) ? $page : 0;
$page = $page == -1 ? 0 : $page;
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter") || $page < 0) {

	$sql = "";
	$offset = 0;
	$sql = "";
	$srch_ref = "";
	$srch_type = "";
	$srch_vehicule = "";
	$srch_date = "";
	$srch_prix = "";
	$search_array_options = array();

}
if(!empty(GETPOST('srch_type')) || !empty(GETPOST('srch_vehicule'))){
	$cl = 'show';
}else{
	$cl='hide';
}

// echo $sql;

$nbrtotal = $costs->fetchAll($sortorder, $sortfield, $limit, $offset, $sql,$join);

$morejs  = array();
llxHeader(array(), $modname,'','','','',$morejs,0,0);

print_barre_liste($modname, $page, $_SERVER["PHP_SELF"], "", $sortfield, $sortorder, "", $nbrtotal, $nbrtotalnofiltr);


print '<form method="get" action="'.$_SERVER["PHP_SELF"].'" class="indexcostvehicul">'."\n";
	print '<input name="pagem" type="hidden" value="'.$page.'">';
	print '<input name="offsetm" type="hidden" value="'.$offset.'">';
	print '<input name="limitm" type="hidden" value="'.$limit.'">';
	print '<input name="filterm" type="hidden" value="'.$sql.'">';
	print '<input name="id_cv" type="hidden" value="'.$id_parcautomobile.'">';

	print '<div style="float: right; margin: 8px;">';
		print '<a href="card.php?action=add" class="butAction" >'.$langs->trans("Add").'</a>';
	print '</div>';

	print '<table id="table-1" class="noborder" style="width: 100%;" >';
		print '<thead>';

			print '<tr class="liste_titre">';
				print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"], "rowid", '', '', 'align="center"', $sortfield, $sortorder);
				print_liste_field_titre($langs->trans("vehicule"),$_SERVER["PHP_SELF"], "vehicule", '', '', 'align="left"', $sortfield, $sortorder);
				print_liste_field_titre($langs->trans("type"),$_SERVER["PHP_SELF"], "type", '', '', 'align="center"', $sortfield, $sortorder);
				print_liste_field_titre($langs->trans("prixT"),$_SERVER["PHP_SELF"], "prix", '', '', 'align="center"', $sortfield, $sortorder);
				print_liste_field_titre($langs->trans("date"),$_SERVER["PHP_SELF"], "date", '', '', 'align="center"', $sortfield, $sortorder);
				include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
				print '<th align="center"></th>';
			print '</tr>';

			print '<tr class="liste_titre nc_filtrage_tr">';
				print '<td align="center"><input style="max-width: 129px;" class="" type="text" class="" id="srch_ref" name="srch_ref" value="'.$srch_ref.'"/></td>';
				print '<td align="left">'.$vehicules->select_with_filter($srch_vehicule,'srch_vehicule').'</td>';
				print '<td align="center">'.$costs->select_types($srch_type,'srch_type').' </td>';
				print '<td align="center"><input style="max-width: 129px;" class="" type="number" step="0.001" class="" id="srch_prix" name="srch_prix" value="'.$srch_prix.'"/></td>';
				print '<td align="center"><input style="max-width: 129px;" class="datepickerparc" autocomplete="off" type="text" id="srch_date" name="srch_date" value="'.$srch_date.'"/></td>';
				include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';
				print '<td align="center">';
					print '<input type="image" name="button_search"  src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
					print '&nbsp;<input type="image" name="button_removefilter"  src="'.img_picto($langs->trans("Search"),'searchclear.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
				print '</td>';
			print '</tr>';
		print '</thead>';

		print '<tbody>';
			// print_r($extrafields->attributes[$object->table_element]);die();
			$colspn = 6;
			if($extrafields->attributes[$object->table_element]['label'])
				$colspn += count($extrafields->attributes[$object->table_element]['label']);
			$total=0;
			if (count($costs->rows) > 0) {
				for ($i=0; $i < count($costs->rows) ; $i++) {
					$var = !$var;
					$item = $costs->rows[$i];
					$total+=$item->prix;

					$obj = new costsvehicule($db);
					$obj->fetch($item->rowid);
				$obj->fetch_optionals();

					print '<tr '.$bc[$var].' >';
					print '<td align="center" style="">';
						print '<a href="'.dol_buildpath('/parcautomobile/costsvehicule/card.php?id='.$item->rowid,2).'" >';
							print $item->rowid;
						print '</a>';
					print '</td>';
						print '<td align="left">';
							$objvehicul = new vehiculeparc($db);
				$objvehicul->fetch($item->vehicule);
							print $objvehicul->get_nom_url($item->vehicule,1);
						print '</td>';
						print '<td align="center">'.$item->type.'</td>';
						$date=explode('-', $item->date);
						$date=$date[2].'/'.$date[1].'/'.$date[0];
						print '<td align="center">'.number_format($item->prix,2,","," ").'</td>';
						print '<td align="center">'.$date.'</td>';

						if($extrafields->attributes[$obj->table_element]['label'] && count($extrafields->attributes[$obj->table_element]['label'])){
						foreach ($extrafields->attributes[$obj->table_element]['label'] as $key => $val){
								if($extrafields->attributes[$obj->table_element]['list'][$key] == 2 || $extrafields->attributes[$obj->table_element]['list'][$key] == 1 || $extrafields->attributes[$obj->table_element]['list'][$key] == 4){
									print '<td align="center">';
										$value = $obj->array_options['options_'.$key];
										$tmpkey = 'options_'.$key;
										print $extrafields->showOutputField($key, $value, '', $obj->table_element);
									print '</td>';
							}
							}
						}

						print '<td align="center"><a href="./card.php?id='.$item->rowid.'&action=pdf" target="_blank" >'.img_mime('test.pdf').'</a></td>';

					print '</tr>';
				}
			}else{
				print '<tr><td align="center" colspan="'.$colspn.'">'.$langs->trans("NoResults").'</td></tr>';
			}
		print '</tbody>';
	print '</table>';

	print '<div id="total" class="'.$cl.'"><strong>'.$langs->trans('total_costs').'</strong> <div>'.number_format($total,2,',',' ').' &nbsp;('.$conf->currency.'-'.$langs->getCurrencySymbol($conf->currency).')</div></div>';
print '</form>';

?>
<script>
	$('.datepickerparc').datepicker({
		dateFormat:'dd/mm/yy',
	});
	$('#select_srch_type').select2();

</script>
<style>

</style>
<?php

llxFooter();