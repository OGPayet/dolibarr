<?php

require('config.php');

if (isset($conf->global->QUERY_SET_TIME_LIMIT)) set_time_limit((int) $conf->global->QUERY_SET_TIME_LIMIT);

dol_include_once('/query/class/query.class.php');
dol_include_once('/query/lib/query.lib.php');

$langs->load('query@query');


$action = GETPOST('action');

$query=new TQuery;
$PDOdb=new TPDOdb;


switch ($action) {

	case 'up_query':


		if(empty($_FILES['query_to_upload']['error']) && !empty($_FILES['query_to_upload']['name'])) {

			$query = unserialize( gzuncompress( file_get_contents($_FILES['query_to_upload']['tmp_name']) ));
			if($query!==false) {

				$query->save($PDOdb);

				setEventMessage($langs->trans('QueryUploadSuccess', $query->title));

			}


		}

		liste();

		break;

	case 'export':
		$query->load($PDOdb, GETPOST('id'));
		$query->rowid = 0;
		unset($query->pdodb,$query->bdd);
		$gz = gzcompress( serialize($query) );

		header('Content-Type: application/octet-stream');
	    header('Content-disposition: attachment; filename=query-'. Tools::url_format($query->title) .'.query');
	    header('Pragma: no-cache');
	    header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
	    header('Expires: 0');

		echo $gz;
		exit;

		break;

	case 'clone':
		$query->load($PDOdb, GETPOST('id'));
		$query->rowid = 0;
		$query->title.=' ('.$langs->trans('Copy').')';
		$newId = $query->save($PDOdb);

		setEventMessage($langs->trans('QueryCloned'));

		header('location:'.dol_buildpath('/query/query.php?action=view&id='.$newId,1));
		exit;

		break;
	case 'delete':
		$query->load($PDOdb, GETPOST('id'));
		$query->delete($PDOdb);
		setEventMessage($langs->trans('DeleteSuccess'));
		header('Location:query.php');
		exit;

		break;

	case 'set-expert':
		$query->load($PDOdb, GETPOST('id'));
		$query->expert = 1;
		$query->save($PDOdb);
		fiche($query);

		break;
	case 'set-free':
		$query->load($PDOdb, GETPOST('id'));
		$query->expert = 2;
		$query->save($PDOdb);
		fiche($query);

		break;
	case 'unset-expert':
		$query->load($PDOdb, GETPOST('id'));
		$query->expert = 0;
		$query->save($PDOdb);
		fiche($query);

		break;
	case 'view':

		$query->load($PDOdb, GETPOST('id'));
		fiche($query);

		break;
	case 'add':

		if(empty($user->rights->query->all->create)) accessforbidden();

		fiche($query);

		break;

	case 'run':
		$query->load($PDOdb, GETPOST('id'));
		run($PDOdb, $query);

		break;

	case 'run-in':
		$query->load($PDOdb, GETPOST('id'));
		run($PDOdb, $query,2);


		break;
	case 'preview':
		$query->load($PDOdb, GETPOST('id'));
		run($PDOdb, $query, true);

		break;

	default:

		liste();

		break;
}




function run(&$PDOdb, &$query, $preview = false) {
	global $conf,$langs,$user;

	if(!$query->userHasRights($PDOdb, $user)) {
		accessforbidden();
	}

	if(!$preview) {
		llxHeader('', 'Query', '', '', 0, 0, array() , array('/query/css/query.css') );
		$head = TQueryMenu::getHeadForObject(GETPOST('tab_object'),GETPOST('fk_object'));
		dol_fiche_head($head, 'tabQuery'.GETPOST('menuId'), 'Query');

	}
	else{

		?><html>
			<head>
				<link rel="stylesheet" type="text/css" href="<?php echo dol_buildpath('/theme/eldy/style.css.php',1) ?>">
				<link rel="stylesheet" type="text/css" href="<?php echo dol_buildpath('/query/css/query.css',1) ?>">
				<script type="text/javascript" src="<?php echo dol_buildpath('/includes/jquery/js/jquery.min.js',1) ?>"></script>
				<meta http-equiv="content-type" content="text/html; charset=utf-8" />
			</head>
		<body style="margin:0 0 0 0;padding:0 0 0 0;"><?php

	}

	if(empty($query->sql_from)) die('InvalidQuery');

	$show_details = GETPOST('_a') == '' ? true : false;

	if($preview === true) {
		$query->preview = true;

	}
	$tab_object = GETPOST('tab_object');
	$table_element = GETPOST('table_element');
	$fk_object = GETPOST('fk_object');

	if(empty($table_element)) {
		if($tab_object == 'thirdparty') $table_element = 'societe';
		else if($tab_object == 'project') $table_element = 'projet';
		else $table_element = $tab_object;
	}


	echo $query->run($show_details,0,$table_element, $fk_object,-1, GETPOST('show_as_list'));

	if(!$preview) {

		echo '<p><a href="'.dol_buildpath('/query/get-json.php',1).'?'.http_build_query(array('TListTBS'=>$_REQUEST['TListTBS'])).'&uid='.$query->uid.'" target="_blank">'.$langs->trans('QueryAsJSON').'</a></p>';

		dol_fiche_end();
		llxFooter();

	}
	else{
		?></body></html><?php
	}
}


function liste() {

	global $langs, $conf,$user;

	$PDOdb=new TPDOdb;

	llxHeader('', 'Query', '', '', 0, 0, array() , array('/query/css/query.css') );
	dol_fiche_head();

	if($user->admin == 1) {
		$sql="SELECT rowid as 'Id', type,nb_result_max, title,expert,0 as 'action'
			FROM ".MAIN_DB_PREFIX."query
			WHERE 1
		";
	} else {
		$sql="SELECT DISTINCT q.rowid as 'Id', q.type, q.nb_result_max, q.title, q.expert, 0 as 'action'
			FROM ".MAIN_DB_PREFIX."query q
			LEFT JOIN ".MAIN_DB_PREFIX."query_rights qr ON (qr.fk_query = q.rowid)
			LEFT JOIN ".MAIN_DB_PREFIX."usergroup_user uu ON (uu.fk_usergroup = qr.fk_element)
			WHERE (qr.element = 'user' AND qr.fk_element = ".$user->id.")
			OR (qr.element = 'group' AND uu.fk_user = ".$user->id.")
			OR qr.rowid IS NULL
		";
	}

	$formCore=new TFormCore('auto','formQ','get');

	$r=new TListviewTBS('lQuery');
	echo $r->render($PDOdb, $sql,array(
		'link'=>array(
			//'Id'=>'<a href="?action=view&id=@val@">'.img_picto('Edit', 'edit.png').' @val@</a>'
			'title'=>'<a href="?action=run&id=@Id@">'.img_picto('Run', 'object_cron.png').' @val@</a>'
			,'action'=>'<a href="?action=view&id=@Id@">'.img_picto('Edit', 'edit.png').'</a> <a href="?action=delete&id=@Id@" onclick="return(confirm(\''.$langs->trans('ConfirmDeleteMessage').'\'));">'.img_picto('Delete', 'delete.png').'</a>'
		)
		,'orderBy'=>array('title'=>'ASC')
		,'hide'=>array('type','nb_result_max','Id')
		,'title'=>array(
			'title'=>$langs->trans('Title')
			,'expert'=>$langs->trans('Expert')
			,'action'=>''
		)
		,'translate'=>array(
			'expert'=>array( 0=>$langs->trans('No'), 1=>$langs->trans('Yes'),2=>$langs->trans('Free') )

		)
		,'search'=>array(
			'title'=>array('recherche'=>true)
		)
		,'orderby'=>array(
			'noOrder'=>array('action')
		)
	));


	$formCore->end();

	$formCore=new TFormCore('auto','formUPQ','post',true);
	echo $formCore->hidden('action','up_query');
	echo $formCore->fichier($langs->trans('QueryToUpload'), 'query_to_upload', '', 10).' '.$formCore->btsubmit($langs->trans('UploadQuery'),'bt_upquery');
	$formCore->end();

	dol_fiche_end();

	llxFooter();
}

function init_js(&$query) {

	if(!empty($query->TMode)) {
		foreach($query->TMode as $f=>$v) {

			echo ' $(\'#fields [sql-act="mode"][field="'.addslashes($f).'"]\').val("'. addslashes($v) .'").change(); ';

		}

	}

	if(!empty($query->TNull)) {
		foreach($query->TNull as $f=>$v) {

			echo ' $(\'#fields [sql-act="null"][field="'.addslashes($f).'"]\').val("'. addslashes($v) .'").change(); ';

		}

	}

	if(!empty($query->TOrder)) {
		foreach($query->TOrder as $f=>$v) {

			echo ' $("#fields [sql-act=\'order\'][field=\''.$f.'\']").val("'. addslashes($v) .'"); ';

		}
	}

	if(!empty($query->TOperator)) {
		foreach($query->TOperator as $f=>$v) {

			echo ' $("#fields [sql-act=\'operator\'][field=\''.$f.'\']").val("'. addslashes($v) .'"); ';

		}
	}

	if(!empty($query->TValue)) {
		foreach($query->TValue as $f=>$v) {

			echo ' $("#fields [sql-act=\'value\'][field=\''.$f.'\']").val("'. addslashes($v) .'"); ';

		}
	}

	if(!empty($query->THide)) {
		foreach($query->THide as $f=>$v) {

			echo ' $("#fieldsview [sql-act=\'hide\'][field=\''.$f.'\']").val("'. addslashes($v) .'"); ';

		}
	}

	if(!empty($query->TTitle)) {
		foreach($query->TTitle as $f=>$v) {

			echo ' $("#fieldsview [sql-act=\'title\'][field=\''.$f.'\']").val("'. addslashes($v) .'"); ';

		}
	}

	if(!empty($query->TTranslate)) {
		foreach($query->TTranslate as $f=>$v) {

			echo ' $("#fieldsview [sql-act=\'translate\'][field=\''.$f.'\']").val("'. addslashes($v) .'"); ';

		}
	}

	if(!empty($query->TFunction)) {
		foreach($query->TFunction as $f=>$v) {

			echo ' $("#fields [sql-act=\'function\'][field=\''.$f.'\']").val("'. addslashes($v) .'"); ';

		}
	}

	if(!empty($query->TGroup)) {
		foreach($query->TGroup as $f) {

			echo ' $("#fields [sql-act=\'group\'][field=\''.$f.'\']").val(1); ';

		}
	}

	if(!empty($query->TTotal)) {
		foreach($query->TTotal as $f=>$v) {
			if(is_array($v)) {
				echo ' $("[sql-act=\'total\'][field=\''.$f.'\']").val("'. addslashes($v[0]) .'").change(); ';
				echo ' $("[sql-act=\'field-total-group\'][field=\''.$f.'\']").val("'. addslashes($v[1]) .'"); ';
			}
			else{
				echo ' $("[sql-act=\'total\'][field=\''.$f.'\']").val("'. addslashes($v) .'"); ';
			}


		}
	}

	if(!empty($query->TFilter)) {
		foreach($query->TFilter as $f=>$v) {

			echo ' $("[sql-act=\'filter\'][field=\''.$f.'\']").val("'. addslashes($v) .'"); $("[sql-act=\'filter\'][field=\''.$f.'\']").show(); ';

		}
	}

	if(!empty($query->TType)) {
		foreach($query->TType as $f=>$v) {

			echo ' $("[sql-act=\'type\'][field=\''.$f.'\']").val("'. addslashes($v) .'"); ';

		}
	}

	if(!empty($query->TClass)) {
		foreach($query->TClass as $f=>$v) {

			echo ' $("[sql-act=\'class\'][field=\''.$f.'\'],[sql-act=\'class-select\'][field=\''.$f.'\']").val("'. addslashes($v) .'"); ';

		}
	}

	if(!empty($query->TMethod)) {
		foreach($query->TMethod as $f=>$v) {

			echo ' $("[sql-act=\'class-method\'][field=\''.$f.'\'],[sql-act=\'class-method-select\'][field=\''.$f.'\']").val("'. addslashes($v) .'"); ';

		}
	}

}

function fiche(&$query) {
	global $langs, $conf,$user;

	if(!$user->rights->query->all->create) accessforbidden();

	llxHeader('', 'Query - '.$query->title, '', '', 0, 0, array('/query/js/query.js'/*,'/query/js/jquery.base64.min.js'*/) , array('/query/css/query.css') );

	$head = queryPrepareHead($query);
	dol_fiche_head($head, 'query', $langs->trans("Query"));

	if($query->expert == 1) {
		if(!empty($query->sql_fields)) {
			$TField = explode_brackets( $query->sql_fields );
			$query->TField = $TField;
		}

	}

	?>
	<script type="text/javascript">
		var MODQUERY_INTERFACE = "<?php echo dol_buildpath('/query/script/interface.php',1); ?>";
		var MODQUERY_QUERYID = <?php echo $query->getId(); ?>;
		var MODQUERY_EXPERT = <?php echo (int)$query->expert; ?>;
		var MODQUERY_PREFIX = "<?php echo MAIN_DB_PREFIX ?>";
		var DOL_VERSION = <?php echo (float)DOL_VERSION ?>;

		var select_equal = '<select sql-act="operator"> '
					+ '<option value=""> </option>'

					+ '<option value="LIKE">LIKE</option>'
					/*+ '<option value="=">=</option>'*/
					+ '<option value="!=">!=</option>'
					+ '<option value="&lt;">&lt;</option>'
					+ '<option value="&lt;=">&lt;=</option>'
					+ '<option value="&gt;">&gt;</option>'
					+ '<option value="&gt;=">&gt;=</option>'
					+ '<option value="IN">IN</option>'
					+ '</select>';

		var select_mode	= '<select sql-act="mode"> '
					+ '<option value="value">valeur</option>'
					+ '<option value="var">variable</option>'
					+ '<option value="function">fonction</option>'
					+ '</select> <input type="text" value="" sql-act="value" />';

		var select_null	= '<select sql-act="null"> '
					+ '<option value=""></option>'
					+ '<option value="1"><?php echo $langs->transnoentities('YesAllowTestOnNull') ?></option>'
					+ '</select>';

		var select_order	= '<select sql-act="order"> '
					+ '<option value=""> </option>'
					+ '<option value="ASC">Ascendant</option>'
					+ '<option value="DESC">Descendant</option>'
					+ '</select>';

		var select_filter	= '<select sql-act="filter"> '
					+ '<option value="">Libre</option>'
					+ '<option value="calendar">Date</option>'
					+ '<option value="calendars">Dates</option>'
					+ '</select>';



		var select_hide	= '<select sql-act="hide"> '
					+ '<option value=""> </option>'
					+ '<option value="1"><?php echo $langs->trans('Hidden') ?></option>'
					+ '</select>';

		var select_group	= '<select sql-act="group"> '
					+ '<option value=""> </option>'
					+ '<option value="1">Groupé</option>'
					+ '</select>';

		var select_total	= '<select sql-act="total"> '
					+ '<option value=""> </option>'
					+ '<option value="sum"><?php echo $langs->trans('Total'); ?></option>'
					+ '<option value="groupsum"><?php echo $langs->trans('TotalGroup'); ?></option>'
					+ '<option value="average"><?php echo $langs->trans('Average'); ?></option>'
					+ '<option value="count"><?php echo $langs->trans('CountOf'); ?></option>'
					+ '</select>';

		var select_total_group_field = '<select sql-act="field-total-group">'
						<?php
						foreach($query->TField as $field) {
							echo ' + \'<option value="'._getFieldName($field).'">'._getFieldName($field).'</option>\' ';
						}
						?>
						+'</select>';

		var select_type	= '<select sql-act="type"> '
					+ '<option value=""> </option>'
					+ '<option value="number">Nombre</option>'
					+ '<option value="integer">Entier</option>'
					+ '<option value="datetime">Date/Heure</option>'
					+ '<option value="date">Date</option>'
					+ '<option value="hour">Heure</option>'
					+ '</select>';

		var select_function	= '<input type="text" size="10" sql-act="function" value="" placeholder="<?php echo $langs->trans('FunctionToApply') ?>" /><select sql-act="function-select"> '
					+ '<option value=""> </option>'
					+ '<option value="SUM(@field@)"><?php echo $langs->trans('Sum') ?></option>'
					+ '<option value="ROUND(@field@,2)"><?php echo $langs->trans('Round2dec') ?></option>'
					+ '<option value="COUNT(@field@)"><?php echo $langs->trans('CountOf') ?></option>'
					+ '<option value="MIN(@field@)"><?php echo $langs->trans('Minimum') ?></option>'
					+ '<option value="MAX(@field@)"><?php echo $langs->trans('Maximum') ?></option>'
					+ '<option value="MONTH(@field@)"><?php echo $langs->trans('Month') ?></option>'
					+ '<option value="YEAR"><?php echo $langs->trans('Year') ?></option>'
					+ '<option value="DATE_FORMAT(@field@, \'%m/%Y\')"><?php echo $langs->trans('YearMonth') ?></option>'
					//+ '<option value="FROM_UNIXTIME(@field@,\'%H:%i\')">Timestamp</option>'
					+ '<option value="SEC_TO_TIME(@field@)"><?php echo $langs->trans('Timestamp') ?></option>'
					//+ '<option value="(@field@ / 3600)">/ 3600</option>'
					+ '</select>';

		var select_class = '<input type="text" size="10" sql-act="class" value="" placeholder="<?php echo $langs->trans('Classname'); ?>" /><select sql-act="class-select"> '
						+ '<option value=""> </option>'

			<?php
				foreach($query->TClassName as $class=>$label) {
					echo ' +\'<option value="'.$class.'">'.$label.'</option>\'';
				}
			?>
			+ '</select>';

		var select_method = '<input type="text" size="10" sql-act="class-method" value="" placeholder="<?php echo $langs->trans('Method'); ?>" /><select sql-act="class-method-select"> '
						+ '<option value=""> </option>'

			<?php

			if(!empty($query->TMethodName)) {

				foreach($query->TMethodName as $method=>$label) {
					echo ' +\'<option value="'.$method.'">'.addslashes($label).'</option>\'';
				}

			}

			?>
			+ '</select>';

		function _init_query() {

			<?php

			if($query->getId()>0) {

				if($query->expert == 2) {

					echo 'showQueryPreview('.$query->getId().');';
					init_js($query);
				}
				else if($query->expert == 1) {

					echo 'showQueryPreview('.$query->getId().');';

					if(!empty($query->TField )) {

						foreach($query->TField as $field) {

							list($f,$t) = _getFieldAndTableName($field);

							if(!empty($t)) $field = $t.'.'.$f;
							else $field = $f;

							echo ' refresh_field_param("'.$field.'","'.$t.'"); ';

						}
					}
					init_js($query);
				}
				else {

					foreach($query->TTable as $table) {

						echo 'addTable("'.$table.'"); ';

					}

					if(empty($query->TField) && !empty($query->sql_fields)) {
						$query->TField = explode(',', $query->sql_fields );
					}
					//$TField =
					if(!empty($query->TField )) {

						foreach($query->TField as $field) {
							echo ' checkField("'.$field.'"); ';
						}

						echo 'showQueryPreview('.$query->getId().');';

					}

					init_js($query);

					if(!empty($query->TJoin)) {
						foreach($query->TJoin as $t=>$join) {

							?>
							$("td[rel=from] select[jointure='<?php echo $t; ?>']").val("<?php echo $join[0]; ?>");
							$("td[rel=to] select[jointure-to='<?php echo $t; ?>']").val("<?php echo $join[1]; ?>");

							TJoin['<?php echo $t; ?>'] = ["<?php echo $join[0]; ?>", "<?php echo $join[1]; ?>"];
							<?php

						}
					}

					?>
					refresh_sql();
					<?php

				}
			}

			?>
		}

	</script>

	<form name="formQuery" id="formQuery">
		<input type="hidden" name="id" value="<?php echo $query->getId(); ?>" />

	<div>
		<?php
			if($query->getId()>0 && !empty($user->rights->query->all->expert) ) {
				?><div style="float:right;z-index:999; position:relative; top:40px;"><?php

				if($query->expert == 0) {

					?><a onclick="if (!confirm('<?php echo dol_escape_js($langs->trans('query_warnings_set_expert_mode')); ?>')) return false;" class="butAction" href="?action=set-expert&id=<?php echo $query->getId() ?>"><?php echo $langs->trans('setExpertMode') ?></a><?php

				}
				else if($query->expert == 2) {
					?><a class="butAction" href="?action=set-expert&id=<?php echo $query->getId() ?>"><?php echo $langs->trans('setExpertMode') ?></a><?php
					?><br /><br /><a class="butAction" href="?action=unset-expert&id=<?php echo $query->getId() ?>"><?php echo $langs->trans('unsetExpertMode') ?></a><?php
				}
				else {
					?><a onclick="if (!confirm('<?php echo dol_escape_js($langs->trans('query_warnings_set_expert_free_mode')); ?>')) return false;"  class="butAction" href="?action=set-free&id=<?php echo $query->getId() ?>"><?php echo $langs->trans('setExpertFreeMode') ?></a><?php
					?><br /><br /><a class="butAction" href="?action=unset-expert&id=<?php echo $query->getId() ?>"><?php echo $langs->trans('unsetExpertMode') ?></a><?php
				}

				?><br /><br /><a class="butAction" href="?action=clone&id=<?php echo $query->getId() ?>"><?php echo $langs->trans('cloneQuery') ?></a><?php
				?><br /><br /><a class="butAction" href="?action=export&id=<?php echo $query->getId() ?>"><?php echo $langs->trans('ExportQuery') ?></a><?php

				?></div><?php

			}
		?>
		<div id="query_header" style="padding: 0 20px 20px 0; background-color: #fff;z-index:1;">
			<?php echo $langs->trans('Title') ?> :
			<input type="text" name="title" size="80" value="<?php echo $query->title; ?>" />
			<?php
				$form=new TFormCore;

				if(!empty($user->rights->query->bdd->use_other_db)) {
					dol_include_once('/query/class/bddconnector.class.php');
					$PDOdb=new TPDOdb;

					$formCoreBDD = new TFormCore;

					if($query->getId()>0)$formCoreBDD->Set_typeaff('view');

					echo $formCoreBDD->combo(' - '.$langs->trans('BDD'),'fk_bdd',  TBDDConnector::getCombo($PDOdb, true), $query->fk_bdd);
				}

				echo $form->combo('- '.$langs->trans('Type').' : ', 'type', $query->TGraphiqueType, $query->type);
				echo '- '.$langs->trans('XAxis').' : <select name="xaxis" initValue="'.$query->xaxis.'"></select>';
			?>
			<input class="button" type="button" id="save_query" value="<?php echo $langs->trans('SaveQuery') ?>" />
		</div>
		<?php
		if($query->getId()>0 && !$query->expert) {
		?>
		<div>
			<?php echo $langs->trans('AddOneOfThisTables') ?> : <select id="tables"></select>
			<input class="button" type="button" id="add_this_table" value="<?php echo $langs->trans('AddThisTable') ?>" />
		</div>

		<div id="selected_tables">

		</div>
		<?php
		}
		?>
	</div>
	<?php
		if($query->getId()>0 && $query->expert!=2) {
			?>
			<div class="selected_fields">
				<div class="border" id="fields"><div class="liste_titre"><?php echo $langs->trans('FieldsOrder'); ?></div></div>
			</div>
			<?php
		}

		if($query->getId()>0) {
	?>
	<div id="results" style="display:<?php echo $query->expert > 0 ? 'block':'none'; ?>;">
		<div>
		<?php echo $langs->trans('Fields'); ?><br />
		<textarea id="sql_query_fields" name="sql_fields" <?php echo $query->expert==2 ? ' style="width:700px;height:100px;" ' : '' ?>><?php
			echo htmlentities($query->sql_fields,0,ini_get("default_charset"));
		?></textarea>
		</div>

		<div>
		<?php echo $langs->trans('From'); ?><br />
		<textarea id="sql_query_from" name="sql_from" <?php echo $query->expert==2 ? 'style="width:700px;height:100px;"' : '' ?>><?php
			 echo htmlentities($query->sql_from,0,ini_get("default_charset"));
		?></textarea>
		</div>

		<div>
		<?php echo $langs->trans('Where'); ?><br />
		<textarea id="sql_query_where" name="sql_where" <?php echo $query->expert==2 ? 'style="width:700px;height:200px;"' : '' ?>><?php
			echo htmlentities($query->sql_where,0,ini_get("default_charset"));
		?></textarea>

		<?php
			if($query->expert>0) {
				echo $langs->trans('AfterWhere'); ?><br /><textarea id="sql_query_afterwhere" name="sql_afterwhere" <?php echo $query->expert==2 ? 'style="width:700px;height:100px;"' : '' ?>><?php
					echo htmlentities($query->sql_afterwhere,0,ini_get("default_charset"));
				?></textarea><?php
			}
			else {
				?><input type="hidden" id="sql_query_afterwhere" name="sql_afterwhere" value="" /><?php
			}

		?>

		</div>
	</div>

	<div style="clear:both; border-top:1px solid #000;"></div>
	<?php
		if($query->getId()>0 && $query->expert!=2) {
		?>
		<div class="selected_fields_view">
			<div class="border" id="fieldsview"><div class="liste_titre"><?php echo $langs->trans('FieldsView'); ?></div></div>
		</div>
		<?php
		}
	?>
	<div id="previewRequete" style="display: none;">
		<iframe src="#" width="100%" bgcolor="#fff" frameborder="0" onload="this.height = this.contentWindow.document.body.scrollHeight + 'px'"></iframe>
	</div>

	<?php
		}
	?>
	</form>



	<?php
	dol_fiche_end();

	llxFooter();
}



