<?php

	define('INC_FROM_CRON_SCRIPT',true);

	require 'config.php';

	set_time_limit(0);
	ini_set('memory_limit','128M');

	dol_include_once('/query/class/query.class.php');

	$PDOdb=new TPDOdb;

	$query=new TQuery;
	if($query->loadBy($PDOdb, GETPOST('uid'),'uid' )) {


		$query->type='RAW';
		$Tab =  $query->run();

		__out($Tab['champs'],'json');

	}
	else{
		exit('InvalidUID');
	}
