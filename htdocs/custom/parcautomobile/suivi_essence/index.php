	<?php
	$res=0;
	if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");       // For root directory
	if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php"); // For "custom"

	require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

	dol_include_once('/parcautomobile/class/suivi_essence.class.php');
	dol_include_once('/parcautomobile/class/vehiculeparc.class.php');
	dol_include_once('/parcautomobile/class/suivi_essence.class.php');
	dol_include_once('/core/class/html.form.class.php');


	$langs->load('parcautomobile@parcautomobile');

	$modname = $langs->trans("suivi_essence");
	// print_r(picto_from_langcode($langs->defaultlang));die();
	// Initial Objects
	$suivi_essence = new suivi_essence($db);
	$suivi_essence2 = new suivi_essence($db);
	$extrafields   = new ExtraFields($db);
	$vehicules     = new vehiculeparc($db);
	$object  	   = new suivi_essence($db);

	$user_         = new User($db);
	$form          = new Form($db);
	$soc           = new Societe($db);



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

	$srch_ref 			= GETPOST('srch_ref');
	$srch_vehicule 		= GETPOST('srch_vehicule');
	$srch_acheteur    = GETPOST('srch_acheteur');
	$srch_date 		= GETPOST('srch_date');
	$srch_litre 		= GETPOST('srch_litre');
	$srch_prix 		= GETPOST('srch_prix');
	$srch_date 		= GETPOST('srch_date');
	$srch_kilometrage 		= GETPOST('srch_kilometrage');
	$srch_unite 		= GETPOST('srch_unite');
	$d=explode('/', $srch_date);
	$date=$d[2].'-'.$d[1].'-'.$d[0];

	$join = 'LEFT JOIN '.MAIN_DB_PREFIX.$object->table_element.'_extrafields as ef on ('.MAIN_DB_PREFIX.$object->table_element.'.rowid = ef.fk_object) ';


	$sql .= (!empty($srch_ref)) ? " AND ".MAIN_DB_PREFIX.$object->table_element.".rowid = ".$srch_ref."" : "";
	$sql .= (!empty($srch_vehicule)) ? " AND ".MAIN_DB_PREFIX.$object->table_element.".vehicule = ".$srch_vehicule."" : "";
	$sql .= (!empty($srch_acheteur)) ? " AND ".MAIN_DB_PREFIX.$object->table_element.".vehicule IN (select parc.rowid from ".MAIN_DB_PREFIX."vehiculeparc as parc where parc.conducteur = ".$srch_acheteur.")" : "";
	$sql .= (!empty($srch_date)) ? " AND CAST(".MAIN_DB_PREFIX.$object->table_element.".date as date) = '".$date."'" : "";
	$sql .= (!empty($srch_kilometrage)) ? " AND ".MAIN_DB_PREFIX.$object->table_element.".kilometrage = ".$srch_kilometrage."" : "";
	$sql .= (!empty($srch_unite)) ? " AND ".MAIN_DB_PREFIX.$object->table_element.".vehicule IN (select parc.rowid from ".MAIN_DB_PREFIX."vehiculeparc as parc where parc.unite like '%".$srch_unite."%' )" : "";
	$sql .= (!empty($srch_litre)) ? " AND ".MAIN_DB_PREFIX.$object->table_element.".litre = ".$srch_litre."" : "";
	$sql .= (!empty($srch_prix)) ? " AND ".MAIN_DB_PREFIX.$object->table_element.".litre*prix = ".$srch_prix."" : "";

	// die($sql);

	if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label']) && !empty($search_array_options)) {
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
	}

	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

	$param .= (!empty($srch_vehicule)) ? '&srch_vehicule='.urlencode($srch_vehicule) : '';
	$param .= (!empty($srch_acheteur)) ? '&srch_acheteur='.urlencode($srch_acheteur) : '';
	$param .= (!empty($srch_litre)) ? '&srch_litre='.urlencode($srch_litre) : '';
	$param .= (!empty($srch_prix)) ? '&srch_prix='.urlencode($srch_prix) : '';
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

	if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter") || $page < 0) {
		$sql = "";
		$offset = 0;
		$sql = "";
		$srch_ref = "";
		$srch_vehicule = "";
		$srch_acheteur = "";
		$srch_date = "";
		$srch_kilometrage = "";
		$srch_unite = "";
		$srch_litre = "";
		$srch_prix = "";
	}

	$morejs  = array();
	llxHeader(array(), $modname,'','','','',$morejs,0,0);
	// echo $sql;

	$nbrtotal = $suivi_essence->fetchAll($sortorder, $sortfield, $limit, $offset, $sql, $join);
	$nbrtotalnofiltr = '';
	if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
	{
		$nbrtotalnofiltr = $suivi_essence2->fetchAll($sortorder, $sortfield, "", "", $sql,$join);
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

		print_barre_liste($modname, $page, $_SERVER["PHP_SELF"], "", $sortfield, $sortorder, "", $nbrtotal, $nbrtotalnofiltr);

		print '<div style="float: right; margin: 8px;">';
		print '<a href="card.php?action=add" class="butAction" >'.$langs->trans("Add").'</a>';
		print '</div>';

		print '<table id="table-1" class="noborder" style="width: 100%;" >';
			print '<thead>';

				print '<tr class="liste_titre">';
					print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"], "rowid", '', '', 'align="center"', $sortfield, $sortorder);
					print_liste_field_titre($langs->trans("vehicule"),$_SERVER["PHP_SELF"], "vehicule", '', '', 'align="left"', $sortfield, $sortorder);
					print_liste_field_titre($langs->trans("acheteur"),$_SERVER["PHP_SELF"], "acheteur", '', '', 'align="center"', $sortfield, $sortorder);
					print_liste_field_titre($langs->trans("date"),$_SERVER["PHP_SELF"], "date", '', '', 'align="center"', $sortfield, $sortorder);
					print_liste_field_titre($langs->trans("kilometrage"),$_SERVER["PHP_SELF"], "kilometrage", '', '', 'align="center"', $sortfield, $sortorder);
					print_liste_field_titre($langs->trans("unite"),$_SERVER["PHP_SELF"], "", '', '', 'align="center"', $sortfield, $sortorder);
					print_liste_field_titre($langs->trans("litre"),$_SERVER["PHP_SELF"], "litre", '', '', 'align="left"', $sortfield, $sortorder);
					print_liste_field_titre($langs->trans("prixT"),$_SERVER["PHP_SELF"], "prix", '', '', 'align="center"', $sortfield, $sortorder);
					include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';

					print '<th align="center"></th>';
				print '</tr>';

				print '<tr class="liste_titre nc_filtrage_tr">';

					print '<td align="center"><input style="max-width: 129px;" class="" type="text" class="" id="srch_ref" name="srch_ref" value="'.$srch_ref.'"/></td>';

					print '<td align="left">'.$vehicules->select_with_filter($srch_vehicule,'srch_vehicule').'</td>';

					print '<td align="center">'.$vehicules->select_conducteur($srch_acheteur,'srch_acheteur').'</td>';

					print '<td align="center"><input style="max-width: 129px;" type="text" class="datepickerparc" id="srch_date" name="srch_date" value="'.$srch_date.'" autocomplete="off"/></td>';

					print '<td align="center"><input style="max-width: 129px;" type="text"  id="srch_kilometrage" name="srch_kilometrage" value="'.$srch_kilometrage.'"/></td>';

					print '<td align="center">'.$vehicules->select_unite($srch_unite,"srch_unite").'</td>';

					print '<td align="center"><input style="max-width: 129px;" type="text"  id="srch_litre" name="srch_litre" value="'.$srch_litre.'"/></td>';

					print '<td align="center"><input style="max-width: 129px;" type="text"  id="srch_prix" name="srch_prix" value="'.$srch_prix.'"/></td>';
					include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

					print '<td align="center">';
						print '<input type="image" name="button_search"  src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
						print '&nbsp;<input type="image" name="button_removefilter"  src="'.img_picto($langs->trans("Search"),'searchclear.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'"></td>';
				print '</tr>';
			print '</thead>';

			print '<tbody>';

				$colspn = 9;
				if($extrafields->attributes[$object->table_element]['label'])
					$colspn += count($extrafields->attributes[$object->table_element]['label']);
				if (count($suivi_essence->rows) > 0) {
					for ($i=0; $i < count($suivi_essence->rows) ; $i++) {
						$var = !$var;
						$item = $suivi_essence->rows[$i];
						$vehicules->fetch($item->vehicule);
					$user_->fetch($vehicules->conducteur);
			            $soc->fetch($item->fournisseur);

			            $obj = new suivi_essence($db);
						$obj->fetch($item->rowid);
					$obj->fetch_optionals();

					$d=explode('-', $item->date);
					$date=$d[2].'/'.$d[1].'/'.$d[0];
						print '<tr '.$bc[$var].' >';
						print '<td align="center" style="">';
							print '<a href="'.dol_buildpath('/parcautomobile/suivi_essence/card.php?id='.$item->rowid,2).'" >';
								print $item->rowid;
							print '</a>';
						print '</td>';
							print '<td align="center"><a href="'.dol_buildpath('/parcautomobile/card.php?id='.$item->vehicule,2).'" >'.$vehicules->get_nom($item->vehicule,1).'</a></td>';
						print '<td align="center" style="">'.$user_->getNomUrl(1).'</td>';
							print '<td align="center">'.$date.'</td>';
							print '<td align="center">'.$item->kilometrage.'</td>';
							print '<td align="center">'.$langs->trans($vehicules->unite).'</td>';
							print '<td align="center">'.$item->litre.'</td>';
							$prix_T=$item->litre*$item->prix;
							print '<td align="center">'.number_format($prix_T,2,',',' ').'</td>';

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
		print'</table>';
	print '</form>';
	?>
	<script>
		$(function(){
			$('.datepickerparc').datepicker({ dateFormat:'dd/mm/yy',});
		});
	</script>
	<?php

	llxFooter();