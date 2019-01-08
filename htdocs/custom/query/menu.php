<?php

	require 'config.php';

	dol_include_once('/query/class/query.class.php');
	dol_include_once('/query/class/dashboard.class.php');

	$PDOdb = new TPDOdb;

	$langs->load('query@query');

	$action = GETPOST('action');

	$object=new TQueryMenu;

	switch ($action) {
		case 'save':
			$object->load($PDOdb, GETPOST('id'));

			if(GETPOST('bt_delete')!='') {
				$object->delete($PDOdb);
				setEventMessage('MenuDeleted');
			}
			else{
				$object->set_values($_POST);
				$object->save($PDOdb);
				setEventMessage('MenuSaved');
			}


			_list($PDOdb);

			break;
		case 'edit':
			$object->load($PDOdb, GETPOST('id'));
			_card($PDOdb, $object);

			break;
		case 'add':
			_card($PDOdb, $object);
			break;
		default:

			_list($PDOdb);

			break;
	}

function _card(&$PDOdb, &$object) {

	global $langs, $conf,$user,$db;

	llxHeader();
	dol_fiche_head(array(),'menu','Menu');

	$formCore=new TFormCore('auto','formMenu','post');
	echo $formCore->hidden('action', 'save');
	echo $formCore->hidden('id', $object->getId());

	$TQuery = array('0'=>'----') + TQuery::getQueries($PDOdb);
	$TDashBoard = array('0'=>'----') + TQDashBoard::getDashboard($PDOdb,'',0,true);

	$buttons = '';
	if (!empty($object->getId())) $buttons.= $formCore->btsubmit($langs->trans('Delete'), 'bt_delete','','buttonDelete');

	$buttons.= ' &nbsp; '. $formCore->btsubmit($langs->trans('Save'), 'bt_save');

	$tbs=new TTemplateTBS;
	echo $tbs->render('tpl/menu.html',array(),array(
		'menu'=>array(
			'type_menu'=>$formCore->combo('', 'type_menu', $object->TTypeMenu, $object->type_menu)
			,'tab_object'=>$formCore->combo('', 'tab_object', $object->TTabObject, $object->tab_object)
			,'mainmenu'=>$formCore->combo('', 'mainmenu', TQueryMenu::getMenu($PDOdb, 'main'), $object->mainmenu)
			,'leftmenu'=>$formCore->combo('', 'leftmenu', TQueryMenu::getMenu($PDOdb, 'left'), $object->leftmenu)
			,'fk_query'=>$formCore->combo('', 'fk_query', $TQuery, $object->fk_query)
			,'fk_dashboard'=>$formCore->combo('', 'fk_dashboard', $TDashBoard, $object->fk_dashboard)
			,'title'=>$formCore->texte('','title', $object->title,80,255)
			,'perms'=>$formCore->texte('','perms', $object->perms,80,255)
		)
		,'view'=>array(
			'langs'=>$langs
			,'buttons'=>$buttons
		)
		,'conf'=>$conf
	));

	$formCore->end();

	dol_fiche_end();
	llxFooter();

}

function _list(&$PDOdb) {
	global $langs, $conf,$user,$db;

	llxHeader();
	dol_fiche_head(array(),'menu','Menu');


	$l=new TListviewTBS('lMenu');
	$sql = "SELECT rowid,title, type_menu, tab_object, mainmenu,leftmenu,date_cre
	FROM ".MAIN_DB_PREFIX."query_menu
	WHERE entity IN (0,".$conf->entity.")";

	$menu_static = new TQueryMenu;

	echo $l->render($PDOdb, $sql,array(

		'title'=>array(
			'title'=>$langs->trans('Title')
			,'leftmenu'=>$langs->trans('LeftMenu')
			,'mainmenu'=>$langs->trans('MainMenu')
			,'date_cre'=>$langs->trans('Date')
			,'tab_object'=>$langs->trans('TabsObject')
			,'type_menu'=>$langs->trans('TypeMenu')
		)
		,'translate'=>array(
			'tab_object'=>$menu_static->TTabObject
			,'type_menu'=>$menu_static->TTypeMenu
		)
		,'link'=>array(
			'title'=>'<a href="?id=@rowid@&action=edit">@val@</a>'
		)
		,'hide'=>array('rowid')
		,'type'=>array(
			'date_cre'=>'date'
		)

	));


	/*$kiwi = new TKiwi;
	$kiwi->fk_soc = $object->id;
	$kiwi->fk_product = 1;
	$kiwi->save($PDOdb);
	*/
	// pied de page
	dol_fiche_end();
	llxFooter();

}
