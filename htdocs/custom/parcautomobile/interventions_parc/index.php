<?php
$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");       // For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php"); // For "custom"

require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';


dol_include_once('/parcautomobile/class/interventions_parc.class.php');
dol_include_once('/parcautomobile/class/typeintervention.class.php');
dol_include_once('/parcautomobile/class/vehiculeparc.class.php');
dol_include_once('/core/class/html.form.class.php');

$langs->load('parcautomobile@parcautomobile');

$modname = $langs->trans("suivi_intervention");
// print_r(picto_from_langcode($langs->defaultlang));die();
// Initial Objects
$typeintervention  = new typeintervention($db);
$interventions     = new interventions_parc($db);
$extrafields       = new ExtraFields($db);
$vehicules         = new vehiculeparc($db);
$object            = new interventions_parc($db);

$user_  = new User($db);
$form   = new Form($db);
$soc    = new Societe($db);

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

$srch_vehicule 		= GETPOST('srch_vehicule');
$srch_typeintervention    = GETPOST('srch_typeintervention');
$srch_prix    = GETPOST('srch_prix');
$srch_acheteur    = GETPOST('srch_acheteur');
$srch_fournisseur    = GETPOST('srch_fournisseur');
$srch_ref_facture    = GETPOST('srch_ref_facture');
$srch_rowid    = GETPOST('srch_rowid');

if(GETPOST('srch_datevalidate')){
	$srch_datevalidate    = GETPOST('srch_datevalidate');
}

$date = explode('/', $srch_datevalidate);
$date = $date[2]."-".$date[1]."-".$date[0];


$join = 'LEFT JOIN '.MAIN_DB_PREFIX.$object->table_element.'_extrafields as ef on ('.MAIN_DB_PREFIX.$object->table_element.'.rowid = ef.fk_object) ';


$sql .= (!empty($srch_ref_facture)) ? " AND ref_facture like '%".$srch_ref_facture."%'" : "";
$sql .= (!empty($srch_rowid)) ? " AND rowid = ".$srch_rowid."" : "";

$sql .= (!empty($srch_vehicule)) ? " AND vehicule = ".$srch_vehicule."" : "";
$sql .= (!empty($srch_typeintervention)) ? " AND typeintervention = ".$srch_typeintervention."" : "";
$sql .= (!empty($srch_prix)) ? " AND prix = ".$srch_prix."" : "";
$sql .= (!empty($srch_datevalidate)) ? " AND CAST(datevalidate as date) = '".$date."'" : "";
$sql .= (!empty($srch_acheteur)) ? " AND vehicule IN (select parc.rowid from ".MAIN_DB_PREFIX."vehiculeparc as parc where parc.conducteur = ".$srch_acheteur.")" : "";
// echo $sql;

if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label']) && !empty($search_array_options)) {
	// $sql .= " AND  ".MAIN_DB_PREFIX."pole_postes.rowid in (select fk_object from ".MAIN_DB_PREFIX."pole_postes_extrafields as  ef where 1 ";
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
	// $sql .= ')';
}

include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

$param .= (!empty($srch_ref_facture)) ? '&srch_ref_facture='.urlencode($srch_ref_facture) : '';
$param .= (!empty($srch_rowid)) ? '&srch_rowid='.urlencode($srch_rowid) : '';
$param .= (!empty($srch_vehicule)) ? '&srch_vehicule='.urlencode($srch_vehicule) : '';
$param .= (!empty($srch_typeintervention)) ? '&srch_typeintervention='.urlencode($srch_typeintervention) : '';
$param .= (!empty($srch_prix)) ? '&srch_prix='.urlencode($srch_prix) : '';
$param .= (!empty($srch_acheteur)) ? '&srch_acheteur='.urlencode($srch_acheteur) : '';

$param .= (!empty($srch_datevalidate)) ? '&srch_datevalidate='.urlencode($srch_datevalidate) : '';


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
	$srch_typeintervention = "";
	$srch_vehicule = "";
	$srch_datevalidate = "";
	$srch_prix = "";

	$srch_vehicule 		= '';
	$srch_typeintervention    = '';
	$srch_prix    = '';
	$srch_acheteur    = '';
	$srch_fournisseur    = '';
	$srch_ref_facture    = '';
	$srch_rowid    = '';

}

// echo $sql;

$nbrtotal = $interventions->fetchAll($sortorder, $sortfield, $limit, $offset, $sql);

$morejs  = array();
llxHeader(array(), $modname,'','','','',$morejs,0,0);

print_barre_liste($modname, $page, $_SERVER["PHP_SELF"], "", $sortfield, $sortorder, "", $nbrtotal, $nbrtotalnofiltr);


print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">'."\n";
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
				// print_liste_field_titre($langs->trans("ref_facture"),$_SERVER["PHP_SELF"], "ref_facture", '', '', 'align="center"', $sortfield, $sortorder);
				print_liste_field_titre($langs->trans("vehicule"),$_SERVER["PHP_SELF"], "vehicule", '', '', 'align="center"', $sortfield, $sortorder);
				print_liste_field_titre($langs->trans("label_typeintervention"),$_SERVER["PHP_SELF"], "typeintervention", '', '', 'align="center"', $sortfield, $sortorder);
				print_liste_field_titre($langs->trans("acheteur"),$_SERVER["PHP_SELF"], "acheteur", '', '', 'align="center"', $sortfield, $sortorder);
				print_liste_field_titre($langs->trans("fournisseur"),$_SERVER["PHP_SELF"], "fournisseur", '', '', 'align="center"', $sortfield, $sortorder);
				// print_liste_field_titre($langs->trans("date"),$_SERVER["PHP_SELF"], "date", '', '', 'align="center"', $sortfield, $sortorder);
				print_liste_field_titre($langs->trans("datevalidate"),$_SERVER["PHP_SELF"], "datevalidate", '', '', 'align="center"', $sortfield, $sortorder);
				print_liste_field_titre($langs->trans("prix_inter"),$_SERVER["PHP_SELF"], "prix_inter", '', '', 'align="center"', $sortfield, $sortorder);
				include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';


				print '<th align="center"></th>';

			print '</tr>';

			print '<tr class="liste_titre nc_filtrage_tr">';

				print '<td align="center" style="width:50px;"><input id="srch_rowid"  style="width:50px;" name="srch_rowid" value="'.$srch_rowid.'"/></td>';
				// print '<td align="center"><input id="srch_ref_facture" name="srch_ref_facture" value="'.$srch_ref_facture.'"/></td>';
				print '<td align="center">'.$vehicules->select_with_filter($srch_vehicule,'srch_vehicule').'</td>';
				print '<td align="center">'.$typeintervention->select_with_filter($srch_typeintervention,'srch_typeintervention').'</td>';
				print '<td align="center">'.$vehicules->select_conducteur($srch_acheteur,'srch_acheteur').'</td>';
				print '<td align="center">'.$vehicules->select_fournisseur($srch_fournisseur,'srch_fournisseur').'</td>';
				print '<td align="center"><input style="max-width: 129px;" class="datepickerparc" autocomplete="off" id="srch_datevalidate" name="srch_datevalidate" value="'.$srch_datevalidate.'"/></td>';
				print '<td align="center"><input style="max-width: 129px;" id="srch_prix" name="srch_prix" value="'.$srch_prix.'"/></td>';
				include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

				print '<td align="center">';
					print '<input type="image" name="button_search"  src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
					print '&nbsp;<input type="image" name="button_removefilter"  src="'.img_picto($langs->trans("Search"),'searchclear.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
				print '</td>';

			print '</tr>';

		print '</thead>';
		print '<tbody>';

			$colspn = 9;
			if($extrafields->attributes[$object->table_element]['label'])
				$colspn += count($extrafields->attributes[$object->table_element]['label']);
			if (count($interventions->rows) > 0) {
				for ($i=0; $i < count($interventions->rows) ; $i++) {
					$var = !$var;

					$item = $interventions->rows[$i];
					$vehicules->fetch($item->vehicule);

					$obj = new interventions_parc($db);
					$obj->fetch($item->rowid);
				$obj->fetch_optionals();

					print '<tr '.$bc[$var].' >';
					print '<td align="center" style="">';
						print '<a href="'.dol_buildpath('/parcautomobile/interventions_parc/card.php?id='.$item->rowid,2).'" >';
							print $item->rowid;
						print '</a>';
					print '</td>';
					// print '<td align="center" style="">';
					// 	// print '<a href="'.dol_buildpath('/parcautomobile/interventions_parc/card.php?id='.$item->rowid,2).'" >';
					// 		print $item->ref_facture;
					// 	// print '</a>';
					// print '</td>';
					$objvehicul = new vehiculeparc($db);
                        $objvehicul->fetch($item->vehicule);
						print '<td align="center">'.$objvehicul->get_nom_url($item->vehicule,1).'</td>';
						print '<td align="center">';
							if($item->typeintervention){
								$typeintervention = new typeintervention($db);
								$typeintervention->fetch($item->typeintervention);
								print $typeintervention->label;
							}
						print '</td>';
						print '<td align="center">';
                            if(!empty($vehicules->conducteur)){
                                $user_->fetch($vehicules->conducteur);
                                $ache = $user_->getNomUrl(1);
                                print_r($ache);
                            }
							// if($item->conducteur){
							// 	$user_ = new User($db);
							// 	$user_->fetch($vehicules->conducteur);
							// 	$user_->getNomUrl(1);
							// }
						print '</td>';
						print '<td align="center">';
							if($item->fournisseur){
								$soc = new Societe($db);
								$soc->fetch($item->fournisseur);
								print $soc->getNomUrl(1);
							}
						print '</td>';
						$date=explode('-', $item->datevalidate);
						$date=$date[2].'/'.$date[1].'/'.$date[0];
						print '<td align="center">'.$date.'</td>';
						print '<td align="center">'.number_format($item->prix,2,","," ").'</td>';
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
print '</form>';

?>
<script>
	$('.datepickerparc').datepicker({
		dateFormat:'dd/mm/yy',
	})
</script>
<?php

llxFooter();