<?php
	chdir(__DIR__);
	require 'config.php';
	dol_include_once('/query/class/query.class.php');
	dol_include_once('/query/class/dashboard.class.php');

	$PDOdb=new TPDOdb;

	$dash=new TQDashboard;
	if($dash->loadBy($PDOdb, GETPOST('uid'),'uid',false)) {
		try {

			if(!class_exists('Wkhtmltopdf')) dol_include_once('/query/lib/Wkhtmltopdf.php');

	        $wkhtmltopdf = new Wkhtmltopdf(array('path' => sys_get_temp_dir()));

	        $wkhtmltopdf->setTitle($dash->title);
			$wkhtmltopdf->setOrientation('landscape');
	        $wkhtmltopdf->setUrl(dol_buildpath('/query/dashboard.php',2).'?action=run&uid='.$dash->uid);
			$wkhtmltopdf->_bin = !empty($conf->global->ABRICOT_WKHTMLTOPDF_CMD) ? $conf->global->ABRICOT_WKHTMLTOPDF_CMD : 'wkhtmltopdf';
	        $wkhtmltopdf->output(Wkhtmltopdf::MODE_DOWNLOAD,$dash->uid.'.pdf');

	    } catch (Exception $e) {
	        echo $e->getMessage();
	    }
	}