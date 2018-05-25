<?php

	require '../config.php';

	$TBS=new TTemplateTBS;

	$TBS->render(__FILE__,array(),array(),array(),array(
		'outFile'=>__DIR__.'/out.html'
		,'convertToPDF'=>1
	));
