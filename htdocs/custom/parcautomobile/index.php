<?php
$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");       // For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php"); // For "custom"


require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';


dol_include_once('/parcautomobile/class/vehiculeparc.class.php');
dol_include_once('/parcautomobile/class/modeles.class.php');
dol_include_once('/parcautomobile/class/statut.class.php');

$langs->load('parcautomobile@parcautomobile');

$modname = $langs->trans("vehicules");
// print_r(picto_from_langcode($langs->defaultlang));die();
// Initial Objects


$extrafields  = new ExtraFields($db);
$parcautomobile     = new vehiculeparc($db);
$vehicules          = new vehiculeparc($db);
$model        		= new modeles($db);
$marque        		= new marques($db);
$statut = new statut($db);
$user_ = new User($db);
$form           = new Form($db);

$object     = new vehiculeparc($db);
$var 				= true;
$sortfield 			= ($_GET['sortfield']) ? $_GET['sortfield'] : "rowid";
$sortorder 			= ($_GET['sortorder']) ? $_GET['sortorder'] : "DESC";
$id 				= $_GET['id'];
$action   			= $_GET['action'];

$extrafields->fetch_name_optionals_label($object->table_element);
$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

$objdocs = new vehiculeparc($db);
global $dolibarr_main_data_root;
if (!dolibarr_get_const($db,'PARCAUTOMOBILE_CHANGEPATHDOCS',0)){
	$source = dol_buildpath('/uploads/parcautomobile');
	if(@is_dir($source)){
		$docdir = $dolibarr_main_data_root.'/parcautomobile';
		$dmkdir = dol_mkdir($docdir, '', 0755);
		if($dmkdir >= 0){
			@chmod($docdir, 0775);
			$dcopy = dolCopyDir($source, $docdir, 0775, 1);
			// if($dcopy >= 0){
				dolibarr_set_const($db,'PARCAUTOMOBILE_CHANGEPATHDOCS',1,'chaine',0,'',0);
				$objdocs->parcautomobilepermissionto($docdir);
			// }
		}
	}
}


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


$srch_ref 			= GETPOST('srch_ref');
$srch_plaque 		= GETPOST('srch_plaque');
$srch_model 		= GETPOST('srch_modele');
$srch_conducteur 		= GETPOST('srch_conducteur');
$srch_num_chassi 		= GETPOST('srch_num_chassi');
$srch_date_immatriculation 		= GETPOST('srch_date_immatriculation');
$srch_etat 		= GETPOST('srch_etat');

$date = explode('/', $srch_date_immatriculation);
$date = $date[2]."-".$date[1]."-".$date[0];

$sql .= (!empty($srch_plaque)) ? " AND plaque like '%".$srch_plaque."%'" : "";
$sql .= (!empty($srch_model)) ? " AND model = ".$srch_model."" : "";
$sql .= (!empty($srch_conducteur)) ? " AND conducteur = ".$srch_conducteur."" : "";
$sql .= (!empty($srch_num_chassi)) ? " AND num_chassi = ".$srch_num_chassi."" : "";
$sql .= (!empty($srch_date_immatriculation)) ? " AND CAST(date_immatriculation as date) = '".$date."'" : "";
$sql .= (!empty($srch_etat)) ? " AND statut = ".$srch_etat."" : "";

if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label']) && !empty($search_array_options)) {
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
}

include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

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
	$srch_plaque = "";
	$srch_model = "";
	$srch_conducteur = "";
	$srch_date_immatriculation = "";
	$srch_num_chassi = "";
	$srch_etat = "";
	$search_array_options = array();

}


$nbrtotal = $parcautomobile->fetchAll($sortorder, $sortfield, $limit, $offset, $sql, $join);

echo $sql;
$morejs  = array();
llxHeader(array(), $modname,'','','','',$morejs,0,0);

print_barre_liste($modname, $page, $_SERVER["PHP_SELF"], "", $sortfield, $sortorder, "", $nbrtotal, $nbrtotalnofiltr);


print '<form method="get" action="'.$_SERVER["PHP_SELF"].'" class="index_parc">'."\n";
	print '<input name="pagem" type="hidden" value="'.$page.'">';
	print '<input name="offsetm" type="hidden" value="'.$offset.'">';
	print '<input name="limitm" type="hidden" value="'.$limit.'">';
	print '<input name="filterm" type="hidden" value="'.$sql.'">';
	print '<input name="id_cv" type="hidden" value="'.$id_parcautomobile.'">';

	print '<div style="float: left; margin-bottom: 8px; width:100%;">';
		print '<div style="width:10%; float:left;" >';
		print '<a class="icon_list" data-type="list" href="'.dol_buildpath("/parcautomobile/index.php",2).'">';
		print '<img  src="'.dol_buildpath("/parcautomobile/img/list.png",2).'" style="height:30px" id="list" ></a>';
		print '<a class="icon_list" data-type="grid" href="'.dol_buildpath("/parcautomobile/kanban.php",2).'">';
		print '<img src="'.dol_buildpath("/parcautomobile/img/grip.png",2).'" style="height:30px" id="grid" ></a> ';
		print '</div>';

		print '<div class="statusdetailcolorsback" style="display: block;">';
			$statut->fetchAll();
			$arr_status=[];
			$vehicules->fetchAll();
			for ($i=0; $i <count($statut->rows); $i++) {
				$etape=$statut->rows[$i];
				$arr_status[$etape->rowid]=0;
				for ($j=0; $j < count($vehicules->rows) ; $j++) {
					$vehicule=$vehicules->rows[$j];
					if($vehicule->statut == $etape->rowid){ $arr_status[$etape->rowid]++; };
				}
					print '<span class="statusname STATUSPROPAL_0">';
						print '<span class="colorstatus" style="background:'.$etape->color.';"></span>';
						print '<span class="labelstatus"><span class="counteleme">'.$arr_status[$etape->rowid].'</span></span>&nbsp';
						print $langs->trans($etape->label);
					print '</span>';
			}
			// print_r($arr_etapes);die();
		print '</div>';

	    print '<div style="width:20%; float:right;" >';
	        print '<a href="card.php?action=add" class="butAction" id="add" >'.$langs->trans("Add").'</a>';
	    print '</div>';
	print '</div>';

	print '<table id="table-1" class="noborder" style="width: 100%;" >';

		print '<thead>';
			print '<tr class="liste_titre">';
				print_liste_field_titre($langs->trans("plaque"),$_SERVER["PHP_SELF"], "plaque", '', '', 'align="left"', $sortfield, $sortorder);
				print_liste_field_titre($langs->trans("model"),$_SERVER["PHP_SELF"], "model", '', '', 'align="center"', $sortfield, $sortorder);
				print_liste_field_titre($langs->trans("conducteur"),$_SERVER["PHP_SELF"], "conducteur", '', '', 'align="center"', $sortfield, $sortorder);
				print_liste_field_titre($langs->trans("date_immatriculation"),$_SERVER["PHP_SELF"], "date_immatriculation", '', '', 'align="center"', $sortfield, $sortorder);
				print_liste_field_titre($langs->trans("num_chassi"),$_SERVER["PHP_SELF"], "num_chassi", '', '', 'align="center"', $sortfield, $sortorder);
				print_liste_field_titre($langs->trans("etat"),$_SERVER["PHP_SELF"], "etat", '', '', 'align="center"', $sortfield, $sortorder);
				include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';

				print '<th align="center"></th>';
			print '</tr>';

			print '<tr class="liste_titre nc_filtrage_tr">';

				print '<td align="center"><input style="max-width: 129px;" class="" type="text" class="" id="srch_plaque" name="srch_plaque" value="'.$srch_plaque.'"/></td>';

				print '<td align="center">'.$model->select_with_filter($srch_model,'srch_modele').'</td>';
				print '<td align="center">'.$parcautomobile->select_conducteur($srch_conducteur,'srch_conducteur').'</td>';
				print '<td align="center"><input style="max-width: 129px;" type="text" class="datepickerparc" id="srch_date_immatriculation" name="srch_date_immatriculation" value="'.$srch_date_immatriculation.'" autocomplete="off" /></td>';
				print '<td align="center"><input style="max-width: 129px;" class="" type="number" min="0" class="" id="srch_num_chassi" name="srch_num_chassi" value="'.$srch_num_chassi.'"/></td>';
				print '<td align="center">'.$statut->select_with_filter($srch_etat,'srch_etat').'</td>';
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
			if (count($parcautomobile->rows) > 0) {
				for ($i=0; $i < count($parcautomobile->rows) ; $i++) {
					$var = !$var;
					$item = $parcautomobile->rows[$i];
					$date=explode('-', $item->date_immatriculation);
					$date_immatriculation = $date[2].'/'.$date[1].'/'.$date[0];

					$obj = new vehiculeparc($db);
					$obj->fetch($item->rowid);
				$obj->fetch_optionals();

					print '<tr '.$bc[$var].' >';
					print '<td align="center" style="" >';
						if($item->model){
							$model   = new modeles($db);
								$marque  = new marques($db);
							$model->fetch($item->model);
							$marque->fetch($model->marque);
							 if(!empty($marque->logo)){
			                        $minifile = getImageFileNameForSize($marque->logo, '');
			                        $dt_files = getAdvancedPreviewUrl('parcautomobile', '/marques/'.$marque->rowid.'/'.$minifile, 1, '&entity='.(!empty($object->entity)?$object->entity:$conf->entity));

			                        print '<img align="left" height="20px" class="photo" height="" title="'.$minifile.'" alt="Fichier binaire" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=parcautomobile&entity='.(!empty($object->entity)?$object->entity:$conf->entity).'&file=marques/'.$marque->rowid.'/'.$minifile.'&perm=download" border="0" name="image" >';
			                    }
						}
						print $obj->getNomUrl();
						// print '<a href="'.dol_buildpath('/parcautomobile/card.php?id='.$item->rowid,2).'" >';
						// 	print $item->plaque;
						// print '</a>';

					print '</td>';
					print '<td align="center" style="">';
						if(!empty($item->model)){
							$modele  = new modeles($db);
								$marque  = new marques($db);
							$modele->fetch($item->model);
							$marque->fetch($model->marque);
							print $marque->label.' / '.$model->label;
						}else{
							print '<b>_</b>';
						}
					print '</td>';
					print '<td align="center" style="">';
						if($item->conducteur){
							$user_ = new User($db);
							$user_->fetch($item->conducteur);
							print $user_->firstname.' '.$user_->lastname;
						}
					print '</td>';
					print '<td align="center" style="">'.$date_immatriculation.'</td>';
					print '<td align="center" style="">'.$item->num_chassi.'</td>';
						print '<td align="left"> ';
						if($item->statut){
							$statut = new statut($db);
						$statut->fetch($item->statut);
							print '<span class="sp_color" style="background-color:'.$statut->color.'"> </span>'.$langs->trans($statut->label);
						}
						print '</td>';

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


						print '<td align="center"><a  href="./card.php?id='.$item->rowid.'&action=pdf" target="_blank" >'.img_mime('test.pdf').'</a></td>';
					print '</tr>';
				}
			}else{
				print '<tr><td align="center" colspan="'.$colspn.'">'.$langs->trans("NoResults").'</td></tr>';
			}
		print '</tbody>';

	print '</table>';
print '</form>';

?>
<style>

</style>
<script>
	$(function(){
		$('#select_unite').select2();
		$('.datepickerparc').datepicker({
			dataFormat:'dd/mm/yyyy',
		});
	})
</script>

<?php

llxFooter();