<?php

	require '../config.php';
	dol_include_once('/query/class/query.class.php');

	$PDOdb = new TPDOdb;

	$l=new TListviewTBS('list1');

	llxHeader();

	$sql = "SELECT rowid,login, firstname, lastname FROM ".MAIN_DB_PREFIX."user";

	echo $l->render($PDOdb, $sql,array(

		'title'=>array(
			'firstname'=>$langs->trans('Firstname')
		)
		,'hide'=>array('rowid')
		/*,'link'=>array(
			'login'=>'<a href="?id=@rowid@">@val@</a>'
		)*/
		,'eval'=>array(
			'login'=>'_get_nom(@rowid@)'
		)
	));

	llxFooter();

function _get_nom($fk_user) {
	$r=  TQuery::getNomUrl("User", $fk_user);
	var_dump($r);
	return $r;
}
