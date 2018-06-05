#!/usr/bin/php
<?php

	chdir(__DIR__);
//echo __DIR__."\n";
	define('INC_FROM_CRON_SCRIPT',true);
	require 'config.php';

	dol_include_once('/user/class/usergroup.class.php');
	dol_include_once('/core/class/CMailFile.class.php');

	//dol_include_once('/query/lib/Wkhtmltopdf.php');
	$PDOdb=new TPDOdb;

	if(!is_dir('files')) mkdir('files',0777);
//var_dump($argv);
	$frequence = $argv[1];
	if(empty($frequence)) $frequence = 'DAY';
	$step = (int)$argv[2];
	if(empty($step)) $step = 1;

	$sql="SELECT uid,rowid, title, fk_usergroup,fk_user_author FROM ".MAIN_DB_PREFIX."qdashboard WHERE send_by_mail = '".$frequence."' AND fk_usergroup>0";
	$Tab = $PDOdb->ExecuteAsArray($sql);
	$f1 = fopen('files/convert.sh','w');
	fputs($f1,"cd ".__DIR__."/files \n");
//	var_dump($sql,$Tab);


	foreach($Tab as $row) {

		$author = new User($db);
		$author->fetch($row->fk_user_author);
//var_dump(  is_file( 'files/'.$row->uid.'.pdf'), dol_buildpath('/query/files/'.$row->uid.'.pdf') );
		if($step == 1) {
			$url = dol_buildpath('/query/dashboard.php',2).'?action=run&uid='.$row->uid;
//echo $url;
			fputs($f1, "wkhtmltopdf --orientation Landscape ".escapeshellarg($url)." ".$row->uid.".pdf  \n");

		}
		elseif($step == 2 && is_file( dol_buildpath('/query/files/'.$row->uid.'.pdf') ) ) {
			$g=new UserGroup($db);
			if($g->fetch($row->fk_usergroup)>0) {
				$TUser = $g->listUsersForGroup();
				foreach($TUser as &$u) {
					if($u->statut == 1) {

						$mailto = $u->email;

						if(!empty($mailto)) {
							print "$mailto \n";

							$m=new TReponseMail($author->email,$mailto,$langs->trans('Report').' : '.$row->title,$langs->trans('PleaseFindYourReportHasAttachement')." : ".dol_buildpath('/query/dashboard.php?action=run&id='.$row->rowid ,2) );
							$m->add_piece_jointe($row->uid.'.pdf',  dol_buildpath('/query/files/'.$row->uid.'.pdf') );
							$m->send();

						}

					}
				}

			}

			unlink(dol_buildpath('/query/files/'.$row->uid.'.pdf')); // suppresion du pdf après envoi par mail
		}
	}
	fclose($f1);
