<?php
$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");       // For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php"); // For "custom" 
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';


dol_include_once('/parcautomobile/class/kilometrage.class.php');
dol_include_once('/parcautomobile/class/vehiculeparc.class.php');
dol_include_once('/parcautomobile/class/marques.class.php');
dol_include_once('/parcautomobile/class/modeles.class.php');
dol_include_once('/core/class/html.form.class.php');

$langs->load('parcautomobile@parcautomobile');

$modname = $langs->trans("releve_kilometrique");
// print_r(picto_from_langcode($langs->defaultlang));die();
// Initial Objects

$vehicule           = new vehiculeparc($db);
$kilometrage        = new kilometrage($db);
$kilometrage2       = new kilometrage($db);
$extrafields        = new ExtraFields($db);
$marque        		= new marques($db);
$model        		= new modeles($db);
$form           	= new Form($db);
$object  	        = new kilometrage($db);


$var 				= true;
$sortfield 			= ($_GET['sortfield']) ? $_GET['sortfield'] : "rowid";
$sortorder 			= ($_GET['sortorder']) ? $_GET['sortorder'] : "DESC";
$id 				= $_GET['id'];
$action   			= $_GET['action'];
$extrafields->fetch_name_optionals_label('kilometrage');
$search_array_options = $extrafields->getOptionalsFromPost('kilometrage', '', 'search_');


if (!$user->rights->parcautomobile->lire) {
	accessforbidden();
}


// Extra fields
if (is_array($extrafields->attributes[$kilometrage->table_element]['label']) && count($extrafields->attributes[$kilometrage->table_element]['label']) > 0)
{
	foreach ($extrafields->attributes[$kilometrage->table_element]['label'] as $key => $val)
	{
		if (!empty($extrafields->attributes[$kilometrage->table_element]['list'][$key])){
			$arrayfields["ef.".$key] = array(
				'label'=>$extrafields->attributes[$kilometrage->table_element]['label'][$key],
				'checked'=>(($extrafields->attributes[$kilometrage->table_element]['list'][$key] < 0) ? 0 : 1),
				'position'=>$extrafields->attributes[$kilometrage->table_element]['pos'][$key], 
				'enabled'=>(abs($extrafields->attributes[$kilometrage->table_element]['list'][$key]) != 3 && $extrafields->attributes[$kilometrage->table_element]['perms'][$key])
			);
		}
	}
}

$srch_ref 		= GETPOST('srch_ref');
$srch_vehicule 		= GETPOST('srch_vehicule');
$srch_conducteur 		= GETPOST('srch_conducteur');

$srch_kilometre 		= GETPOST('srch_kilometre');
$srch_unite     =  GETPOST('srch_unite');
$unite 		= GETPOST('unite');
$srch_date = GETPOST('date');
$date = "";


if($srch_date){
	$d  = explode('/', GETPOST('date'));
	$date = $d[2].'-'.$d[1].'-'.$d[0];
}

$join = 'LEFT JOIN '.MAIN_DB_PREFIX.$object->table_element.'_extrafields as ef on ('.MAIN_DB_PREFIX.$object->table_element.'.rowid = ef.fk_object) ';


$sql .= (!empty($srch_ref)) ? " AND ".MAIN_DB_PREFIX.$object->table_element.".rowid = ".$srch_ref."" : "";
$sql .= (!empty($srch_vehicule)) ? " AND ".MAIN_DB_PREFIX.$object->table_element.".vehicule = ".$srch_vehicule."" : "";
$sql .= (!empty($srch_kilometre)) ? " AND ".MAIN_DB_PREFIX.$object->table_element.".kilometrage = ".$srch_kilometre."" : "";
$sql .= (!empty($srch_unite)) ? " AND ".MAIN_DB_PREFIX.$object->table_element.".vehicule IN (select parc.rowid from ".MAIN_DB_PREFIX."vehiculeparc as parc where parc.unite like '%".$srch_unite."%')" : "";
$sql .= (!empty($srch_conducteur)) ? " AND ".MAIN_DB_PREFIX.$object->table_element.".vehicule IN (select parc.rowid from ".MAIN_DB_PREFIX."vehiculeparc as parc where parc.conducteur = ".$srch_conducteur.")" : "";
$sql .= (!empty($date)) ? " AND CAST(".MAIN_DB_PREFIX.$object->table_element."date as date) = '".$date."'" : "";

if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label']) && !empty($search_array_options)) {
	// $sql .= " AND  ".MAIN_DB_PREFIX."pole_postes.rowid in (select fk_object from ".MAIN_DB_PREFIX."pole_postes_extrafields as  ef where 1 ";
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
	// $sql .= ')';
}

include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

$param .= (!empty($srch_vehicule)) ? '&srch_vehicule='.urlencode($srch_vehicule) : '';
$param .= (!empty($srch_kilometre)) ? '&srch_kilometre='.urlencode($srch_kilometre) : '';
$param .= (!empty($srch_unite)) ? '&srch_unite='.urlencode($srch_unite) : '';

$param .= (!empty($srch_date)) ? '&srch_date='.urlencode($srch_date) : '';


$limit 	= $conf->liste_limit+1;

$page 	= GETPOST("page",'int');
$page = is_numeric($page) ? $page : 0;
$page = $page == -1 ? 0 : $page;
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.$limit;

if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter") || $page < 0) 
{
	$sql = "";
	$offset = 0;
	$filter = "";
	$srch_ref = "";
	$srch_vehicule = "";
	$srch_kilometre = "";
	$srch_unite = "";
	$srch_date = "";
	$search_array_options = array();

}

$morejs  = array();
llxHeader(array(), $modname,'','','','',$morejs,0,0);


$nbrtotal = $kilometrage->fetchAll($sortorder, $sortfield, $limit, $offset, $sql, $join);
$nbrtotalnofiltr = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$nbrtotalnofiltr = $kilometrage2->fetchAll($sortorder, $sortfield, "", "", $sql,$join);
	if (($page * $limit) > $nbrtotalnofiltr)	// if total resultset is smaller then paging size (filtering), goto and load page 0
	{
		$page = 0;
		$offset = 0;
	}
}




print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">'."\n";
	print '<input name="pagem" type="hidden" value="'.$page.'">';
	print '<input name="offsetm" type="hidden" value="'.$offset.'">';
	print '<input name="limitm" type="hidden" value="'.$limit.'">';
	print '<input name="filterm" type="hidden" value="'.$sql.'">';
	print '<input name="id_cv" type="hidden" value="'.$id_parcautomobile.'">';

	print_barre_liste($modname, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, "", $nbrtotal, $nbrtotalnofiltr);
	
	print '<div style="float: right; margin: 8px;">';
	print '<a href="card.php?action=add" class="butAction" >'.$langs->trans("Add").'</a>';
	print '</div>';

	print '<table id="table-1" class="noborder" style="width: 100%;" >';
		print '<thead>';

			print '<tr class="liste_titre">';
				print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"], "rowid", '', '', 'align="center"', $sortfield, $sortorder);
				print_liste_field_titre($langs->trans("vehicule"),$_SERVER["PHP_SELF"], "vehicule", '', '', 'align="left"', $sortfield, $sortorder);
				print_liste_field_titre($langs->trans("conducteur"),$_SERVER["PHP_SELF"], "vehicule", '', '', 'align="left"', $sortfield, $sortorder);
				print_liste_field_titre($langs->trans("kilometrage"),$_SERVER["PHP_SELF"], "kilometrage", '', '', 'align="center"', $sortfield, $sortorder);
				print_liste_field_titre($langs->trans("unite"),$_SERVER["PHP_SELF"], "unite", '', '', 'align="center"', $sortfield, $sortorder);
				print_liste_field_titre($langs->trans("date"),$_SERVER["PHP_SELF"], "date", '', '', 'align="center"', $sortfield, $sortorder);
				include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
				print '<th align="center"></th>';
			print '</tr>';

			print '<tr class="liste_titre nc_filtrage_tr">';
				print '<td align="center"><input style="max-width: 129px;" class="" type="text" class="" id="srch_ref" name="srch_ref" value="'.$srch_ref.'"/></td>';

				print '<td align="left">'.$vehicule->select_with_filter($srch_vehicule,'srch_vehicule').'</td>';
				print '<td align="left">'.$vehicule->select_conducteur($srch_conducteur,'srch_conducteur').'</td>';

				print '<td align="center"><input style="max-width: 129px;" class="" type="text" class="" id="srch_kilometre" name="srch_kilometre" value="'.$srch_kilometre.'"/></td>';
				print '<td align="center">'.$vehicule->select_unite($srch_unite,'srch_unite').'</td>';
				print '<td align="center"><input style="max-width: 129px;" class="datepickerparc" type="text" class="" id="srch_date" name="srch_date" value=""/></td>';

				include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';
				print '<td align="center">';
					print '<input type="image" name="button_search"  src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
					print '&nbsp;<input type="image" name="button_removefilter"  src="'.img_picto($langs->trans("Search"),'searchclear.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
				print '</td>';

			print '</tr>';

		print '</thead>';

	    print '<tbody>';
			$colspn = 7;
			if($extrafields->attributes[$object->table_element]['label'])
				$colspn += count($extrafields->attributes[$object->table_element]['label']);
			if (count($kilometrage->rows) > 0) {
				for ($i=0; $i < count($kilometrage->rows) ; $i++) {
					$var = !$var;
					$item = $kilometrage->rows[$i];

					$obj = new kilometrage($db);
					$obj->fetch($item->rowid);
    				$obj->fetch_optionals();

					print '<tr '.$bc[$var].' >';
			    		print '<td align="center" style="">'; 
				    		print '<a href="'.dol_buildpath('/parcautomobile/kilometrage/card.php?id='.$item->rowid,2).'" >';
				    			print $item->rowid;
				    		print '</a>';
			    		print '</td>';

			    		print '<td align="left" style="">';
				    		if($item->vehicule){
				    			$vehicule = new vehiculeparc($db);
				    			$vehicule->fetch($item->vehicule);
				    			$conducteur = $vehicule->conducteur;
					    		
				    			print $vehicule->get_nom_url($item->vehicule,1);
				    		}
			    		print '</td>';
			    		print '<td align="left" style="">';
				    		if($conducteur){
				    			$conduct = new User($db);
				    			$conduct->fetch($conducteur);
				    			print $conduct->getNomUrl(1);
				    			
				    		}
			    		print '</td>';
			    		
						print '<td align="center"> '.$item->kilometrage.'</td>';
						print '<td align="center">';
							if($vehicule->unite){
								print $langs->trans($vehicule->unite);
							}
						print '</td>';
						$date=explode('-', $item->date);
						$date_= $date[2].'/'.$date[1].'/'.$date[0];
						print '<td align="center">'.$date_.'</td>';

						if($extrafields->attributes[$object->table_element]['label'] && count($extrafields->attributes[$object->table_element]['label'])){
				    		foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val){
								if($extrafields->attributes[$object->table_element]['list'][$key] == 2 || $extrafields->attributes[$object->table_element]['list'][$key] == 1 || $extrafields->attributes[$object->table_element]['list'][$key] == 4){
									print '<td align="center">';
										$value = $obj->array_options['options_'.$key];
										$tmpkey = 'options_'.$key;
										print $extrafields->showOutputField($key, $value, '', $object->table_element);
									print '</td>';
	        					}
							}
						}

						// print '<td align="center"></td>';
						print '<td align="center"><a href="./card.php?id='.$item->rowid.'&action=pdf" target="_blank" >'.img_mime('test.pdf').'</a></td>';
						
					print '</tr>';
				}
			}else{
				print '<tr><td align="center" colspan="'.$colspn.'">'.$langs->trans("NoResults").'</td></tr>';
			}
		print '</tbody>';
	print '</table>';
print '</form>';

?>
<script>
    $(function(){
        $('.datepickerparc').datepicker({
            dateFormat:'dd/dm/yy',
        });
        $('#select_unite').select2();
        $('#select_srch_vehicule').select2();
    })
</script>
<?php

llxFooter();