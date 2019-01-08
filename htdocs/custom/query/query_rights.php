<?php

require 'config.php';

dol_include_once('/query/class/query.class.php');
dol_include_once('/query/lib/query.lib.php');

$PDOdb = new TPDOdb;

$langs->load('query@query');

if(!$user->rights->query->all->create) accessforbidden();

$action = GETPOST('action');
$query_id = GETPOST('id');

$query = new TQuery();
$query->load($PDOdb, $query_id);
$objectright=new TQueryRights;

switch ($action) {
	case 'add':
		$objectright->set_values($_REQUEST);
		$fk_element = (isset($_REQUEST['fk_group']) ? $_REQUEST['fk_group'] : $_REQUEST['fk_user']);
		$objectright->fk_element = $fk_element;
		if(!empty($objectright->fk_element) && !empty($objectright->element) && !empty($objectright->fk_query)) {
			$objectright->save($PDOdb);
			setEventMessage('RightSaved');
		}

		break;
	case 'remove':
		$objectright->load($PDOdb, GETPOST('rights_id'));
		$objectright->delete($PDOdb);
		setEventMessage('RightDeleted');

		break;
	default:

		break;
}

_list($PDOdb, $query);

function _list(&$PDOdb, &$query) {
	global $langs, $conf,$user,$db;

	$formdoli = new Form($db);

	llxHeader('', 'Query - '.$query->title);
	$head = queryPrepareHead($query);
	dol_fiche_head($head, 'rights', $langs->trans("Query"));

	echo $langs->trans('Title') . ' : ' . $query->title;
	echo '<br><br>';

	// Utilisateurs associés

	$l=new TListviewTBS('lRightsUser');
	$sql = "SELECT qr.rowid, qr.element, qr.fk_element
			, CASE WHEN qr.element = 'user' THEN CONCAT(u.firstname, ' ', u.lastname) ELSE g.nom END as label
			,'' as action
			FROM ".MAIN_DB_PREFIX."query_rights qr
			LEFT JOIN ".MAIN_DB_PREFIX."user u ON (u.rowid = qr.fk_element AND qr.element = 'user')
			LEFT JOIN ".MAIN_DB_PREFIX."usergroup g ON (g.rowid = qr.fk_element AND qr.element = 'group')
			WHERE fk_query = ".$query->getId()."
			ORDER BY element ASC";

	echo $l->render($PDOdb, $sql,array(

		'title'=>array(
			'title'=>$langs->trans('Title')
			,'fk_element'=>$langs->trans('LinkedElement')
			,'element'=>$langs->trans('Element')
			,'action'=>$langs->trans('Delete')
			,'label'=>$langs->trans('Name')
		)
		,'translate'=>array(
			'element'=> array('user'=>$langs->trans('User'), 'group'=>$langs->trans('Group'))
		)
		,'link'=>array(
			'action'=>'<a href="'.dol_buildpath('/query/query_rights.php',1).'?rights_id=@rowid@&id='.$query->getId().'&action=remove">'.img_delete().'</a>'
		)
		,'hide'=>array('rowid','fk_element')
		,'type'=>array(
		)
	));

	echo '<br>';

	$form = new TFormCore('auto','add_user');

	echo $form->hidden('action', 'add');
	echo $form->hidden('element', 'user');
	echo $form->hidden('fk_query', $query->getId());
	echo $form->hidden('id', $query->getId());

	echo $langs->trans('AddUser') . ' : ';
	echo $formdoli->select_dolusers('','fk_user');

	echo $form->btsubmit($langs->trans('Add'), 'add');

	echo $form->end_form();


	$form = new TFormCore('auto','add_group');

	echo $form->hidden('action', 'add');
	echo $form->hidden('element', 'group');
	echo $form->hidden('fk_query', $query->getId());
	echo $form->hidden('id', $query->getId());

	echo $langs->trans('AddGroup') . ' : ';
	echo $formdoli->select_dolgroups('','fk_group');

	echo $form->btsubmit($langs->trans('Add'), 'add');

	echo $form->end_form();

	// pied de page
	dol_fiche_end();
	llxFooter();
}
