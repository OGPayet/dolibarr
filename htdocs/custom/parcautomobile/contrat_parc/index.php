<?php
$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");       // For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php"); // For "custom" 
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';


dol_include_once('/parcautomobile/class/contrat_parc.class.php');
dol_include_once('/parcautomobile/class/typecontrat.class.php');
dol_include_once('/parcautomobile/class/vehiculeparc.class.php');
dol_include_once('/core/class/html.form.class.php');

$langs->load('parcautomobile@parcautomobile');

$modname = $langs->trans("list_contrats");
// print_r(picto_from_langcode($langs->defaultlang));die();
// Initial Objects
$typecontrat        = new typecontrat($db);
$contrat        = new contrat_parc($db);
$vehicules        = new vehiculeparc($db);
$extrafields = new ExtraFields($db);
$form           = new Form($db);
$user_  = new User($db);
$soc = new Societe($db);
$object = new contrat_parc($db);

$var 				= true;
$sortfield 			= ($_GET['sortfield']) ? $_GET['sortfield'] : "ref_contrat";
$sortorder 			= ($_GET['sortorder']) ? $_GET['sortorder'] : "DESC";
$id 				= $_GET['id'];
$action   			= $_GET['action'];


$extrafields->fetch_name_optionals_label($object->table_element);
$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');	


if (!$user->rights->parcautomobile->lire) {
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
$srch_typecontrat    = GETPOST('srch_typecontrat');
$srch_prix    = GETPOST('srch_prix');
$srch_notes    = GETPOST('srch_notes');
$srch_debut    = GETPOST('srch_debut');
$srch_fin    = GETPOST('srch_fin');
$srch_fournisseur =GETPOST('srch_fournisseur');
$srch_conducteur =GETPOST('srch_conducteur');
$srch_montant = GETPOST('srch_montant');
$srch_type_montant = GETPOST('srch_type_montant');
$srch_statut = GETPOST('srch_statut');
$srch_ref = GETPOST('srch_ref');
if(GETPOST('srch_debut')){
	$srch_debut    = GETPOST('srch_debut');
}

if(GETPOST('srch_fin')){
	$srch_fin    = GETPOST('srch_fin');
}

$join = 'LEFT JOIN '.MAIN_DB_PREFIX.$object->table_element.'_extrafields as ef on ('.MAIN_DB_PREFIX.$object->table_element.'.rowid = ef.fk_object) ';


$date = explode('/', $srch_debut);
$date_d = $date[2]."-".$date[1]."-".$date[0];

$date = explode('/', $srch_fin);
$date_f = $date[2]."-".$date[1]."-".$date[0];

$sql .= (!empty($srch_ref)) ? " AND ".MAIN_DB_PREFIX.$object->table_element.".ref_contrat like '%".$srch_ref."%'" : "";
$sql .= (!empty($srch_vehicule)) ? " AND ".MAIN_DB_PREFIX.$object->table_element.".vehicule = ".$srch_vehicule."" : "";
$sql .= (!empty($srch_typecontrat)) ? " AND ".MAIN_DB_PREFIX.$object->table_element.".typecontrat = ".$srch_typecontrat."" : "";
$sql .= (!empty($srch_fournisseur)) ? " AND ".MAIN_DB_PREFIX.$object->table_element.".fournisseur = ".$srch_fournisseur."" : "";
$sql .= (!empty($srch_conducteur)) ? " AND ".MAIN_DB_PREFIX.$object->table_element.".conducteur = ".$srch_conducteur."" : "";
$sql .= (!empty($srch_prix)) ? " AND ".MAIN_DB_PREFIX.$object->table_element.".prix = ".$srch_prix."" : "";
$sql .= (!empty($srch_debut)) ? " AND CAST(".MAIN_DB_PREFIX.$object->table_element.".date_debut as date) = '".$date_d."'" : "";
$sql .= (!empty($srch_fin)) ? " AND CAST(".MAIN_DB_PREFIX.$object->table_element.".date_fin as date) = '".$date_f."'" : "";
$sql .= (!empty($srch_montant)) ? " AND ".MAIN_DB_PREFIX.$object->table_element.".montant_recurrent =".$srch_montant."" : "";
$sql .= (!empty($srch_type_montant)) ? " AND ".MAIN_DB_PREFIX.$object->table_element.".type_montant like '%".$srch_type_montant."%'" : "";
$sql .= (!empty($srch_statut)) ? " AND ".MAIN_DB_PREFIX.$object->table_element.".etat like '%".$srch_statut."%'" : "";
// die($sql);

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
	$srch_ref = '';
	$srch_typecontrat = "";
	$srch_vehicule = "";
	$srch_fournisseur = "";
	$srch_conducteur = "";
	$srch_debut = "";
	$srch_fin = "";
	$srch_prix = "";
	$srch_montant = "";
	$srch_type_montant = "";
	$srch_statut = "";
}

// echo $sql;

$nbrtotal = $contrat->fetchAll($sortorder, $sortfield, $limit, $offset, $sql, $join);

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
				print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"], "ref_contrat", '', '', 'align="center"', $sortfield, $sortorder);
				print_liste_field_titre($langs->trans("date_d"),$_SERVER["PHP_SELF"], "date_debut", '', '', 'align="center"', $sortfield, $sortorder);
				print_liste_field_titre($langs->trans("date_f"),$_SERVER["PHP_SELF"], "date_fin", '', '', 'align="center"', $sortfield, $sortorder);
				print_liste_field_titre($langs->trans("vehicule"),$_SERVER["PHP_SELF"], "vehicule", '', '', 'align="left"', $sortfield, $sortorder);
				print_liste_field_titre($langs->trans("typecontrat"),$_SERVER["PHP_SELF"], "typecontrat", '', '', 'align="center"', $sortfield, $sortorder);
				print_liste_field_titre($langs->trans("fournisseur"),$_SERVER["PHP_SELF"], "fournisseur", '', '', 'align="center"', $sortfield, $sortorder);
				print_liste_field_titre($langs->trans("conducteur"),$_SERVER["PHP_SELF"], "conducteur", '', '', 'align="center"', $sortfield, $sortorder);
				print_liste_field_titre($langs->trans("montant_recurrent"),$_SERVER["PHP_SELF"], "montant_recurrent", '', '', 'align="center"', $sortfield, $sortorder);
				print_liste_field_titre($langs->trans("type_montant"),$_SERVER["PHP_SELF"], "type_montant", '', '', 'align="center"', $sortfield, $sortorder);
				print_liste_field_titre($langs->trans("statut_contrat"),$_SERVER["PHP_SELF"], "etat", '', '', 'align="center"', $sortfield, $sortorder);
				include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';

				print '<th align="center"></th>';
			print '</tr>';

			print '<tr class="liste_titre nc_filtrage_tr">';

				print '<td align="center"><input style="max-width: 129px;" class="" type="text" class="" id="srch_ref" name="srch_ref" value="'.$srch_ref.'"/></td>';

				print '<td align="center"><input style="max-width: 129px;" class="datepickerparc" autocomplete="off" type="text" id="srch_debut" name="srch_debut" value="'.$srch_debut.'"/></td>';

				print '<td align="center"><input style="max-width: 129px;" class="datepickerparc" autocomplete="off" type="text" id="srch_fin" name="srch_fin" value="'.$srch_fin.'"/></td>';

				print '<td align="left">'.$vehicules->select_with_filter($srch_vehicule,'srch_vehicule').'</td>';

				print '<td align="center">'.$typecontrat->select_with_filter($srch_typecontrat,'srch_typecontrat').'</td>';

				print '<td align="center">'.$vehicules->select_fournisseur($srch_fournisseur,'srch_fournisseur').'</td>';

				print '<td align="center">'.$vehicules->select_conducteur($srch_conducteur,'srch_conducteur').'</td>';

				print '<td align="center"><input style="max-width: 129px;" class="" type="number" step="0.001" class="" id="srch_montant" name="srch_montant" value="'.$srch_montant.'"/></td>';

				print '<td align="center">'.$contrat->types_montant($srch_type_montant,'srch_type_montant').'</td>';

				print '<td align="center">'.$contrat->select_statut($srch_statut,'srch_statut').'</td>';

				include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

				print '<td align="center">';
					print '<input type="image" name="button_search"  src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
					print '&nbsp;<input type="image" name="button_removefilter"  src="'.img_picto($langs->trans("Search"),'searchclear.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
				print '</td>';
			print '</tr>';

		print '</thead>';
		print '<tbody>';

			$colspn = 11;
			if($extrafields->attributes[$object->table_element]['label'])
				$colspn += count($extrafields->attributes[$object->table_element]['label']);
			if (count($contrat->rows) > 0) {
				for ($i=0; $i < count($contrat->rows) ; $i++) {
					$var = !$var;
					$item = $contrat->rows[$i];

					$obj = new contrat_parc($db);
						$obj->fetch($item->rowid);
	    				$obj->fetch_optionals();
					print '<tr '.$bc[$var].' >';
			    		print '<td align="center" style="">'; 
				    		print '<a href="'.dol_buildpath('/parcautomobile/contrat_parc/card.php?id='.$item->rowid,2).'" >';
				    			print $item->rowid;
				    		print '</a>';
			    		print '</td>';
						$date=explode('-', $item->date_debut);
						$date_d=$date[2].'/'.$date[1].'/'.$date[0];

						$date=explode('-', $item->date_fin);
						$date_f=$date[2].'/'.$date[1].'/'.$date[0];

						print '<td align="center">'.$date_d.'</td>';

						print '<td align="center">'.$date_f.'</td>';

						$objvehicul = new vehiculeparc($db);
                        $objvehicul->fetch($item->vehicule);
						print '<td align="left">'.$objvehicul->get_nom_url($item->vehicule,1).'</td>';
						print '<td align="center">';
							if($item->typecontrat){
								$typecontrat = new typecontrat($db);
								$typecontrat->fetch($item->typecontrat);
								print $typecontrat->label;
							}
						print '</td>';

	                	print '<td align="center">';
							if($item->fournisseur){
								$soc = new Societe($db);
								$soc->fetch($item->fournisseur);
		                		print $soc->getNomUrl(1);
							}
	                	print '</td>';

						print '<td align="center">';
							if($item->conducteur > 0){
								$user_ = new User($db);
								$user_->fetch($item->conducteur);
								print $user_->getNomUrl(1);
							}
						print '</td>';

						print '<td align="center">'.number_format($item->montant_recurrent,2,","," ").'</td>';

						print '<td align="center">';
							if($item->type_montant)
							print $langs->trans($item->type_montant);
						print '</td>';

						print '<td align="center">'.$langs->trans($item->etat).'</td>';

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