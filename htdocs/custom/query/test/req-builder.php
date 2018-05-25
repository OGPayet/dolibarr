<?php

	require '../config.php';


	$PDOdb=new TPDOdb;

	$QB = new TQueryBuilder;

	$sql = $QB->select('user',array('login','pass'))
				->where("datelastlogin>'2010-01-01'")
				->build();

	echo $sql;

	var_dump($PDOdb->ExecuteAsArray($sql));
