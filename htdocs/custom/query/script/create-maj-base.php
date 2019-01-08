<?php
/*
 * Script créant et vérifiant que les champs requis s'ajoutent bien
 */



if(defined('INC_FROM_DOLIBARR')) {
	dol_include_once('/query/config.php');
	$PDOdb=new TPDOdb;
}
else{
	define('INC_FROM_CRON_SCRIPT', true);

	require('../config.php');
	$PDOdb=new TPDOdb;
	$PDOdb->db->debug=true;
}

dol_include_once('/query/class/query.class.php');
dol_include_once('/query/class/dashboard.class.php');
dol_include_once('/query/class/bddconnector.class.php');

$o=new TQuery($db);
$o->init_db_by_vars($PDOdb);

$o=new TQDashBoard($db);
$o->init_db_by_vars($PDOdb);

$o=new TQDashBoardQuery($db);
$o->init_db_by_vars($PDOdb);

$o=new TQueryMenu($db);
$o->init_db_by_vars($PDOdb);

$o=new TQueryRights();
$o->init_db_by_vars($PDOdb);

$o=new TBDDConnector($db);
$o->init_db_by_vars($PDOdb);


$PDOdb->Execute("INSERT INTO `llx_qdashboard` (`rowid`, `date_cre`, `date_maj`, `fk_user`, `fk_user_author`, `fk_usergroup`, `uid`, `send_by_mail`, `hook`, `title`, `refresh_dashboard`) VALUES
(1, '2015-12-04 16:20:45', '2015-12-09 17:48:27', 1, 2, 4, '5239255d1f9cb26f08f8a52125178c38', '', 'projectcard', 'Projets', '0'),
(2, '2015-12-05 17:16:18', '2015-12-09 17:45:24', 1, 2, 2, '9100b0751204b7521c91febb15c1920d', '', 'usercard', 'Commercial', '0'),
(4, '2015-12-09 12:28:02', '2015-12-09 12:29:42', 2, 2, 4, '887f97707d73f89288eb9dc0a76530ee', 'WEEK', 'usercard', 'Développeur', '0')");

$PDOdb->Execute("INSERT INTO `llx_qdashboard_query` (`rowid`, `date_cre`, `date_maj`, `fk_qdashboard`, `fk_query`, `width`, `height`, `posx`, `posy`, `title`) VALUES
(1, '2015-12-04 16:21:25', '2015-12-09 17:48:27', 1, 1, 2, 1, 1, 3, ''),
(2, '2015-12-04 16:57:41', '2015-12-09 17:48:27', 1, 2, 3, 2, 2, 1, ''),
(3, '2015-12-04 17:05:40', '2015-12-09 17:48:27', 1, 3, 1, 2, 1, 1, ''),
(5, '2015-12-04 23:33:32', '2015-12-09 17:48:27', 1, 5, 2, 1, 3, 3, ''),
(6, '2015-12-05 17:16:46', '2015-12-09 17:45:24', 2, 7, 1, 2, 1, 1, ''),
(8, '2015-12-05 17:17:01', '2015-12-09 17:45:24', 2, 6, 1, 2, 2, 1, ''),
(12, '2015-12-09 12:29:04', '2015-12-09 12:29:42', 4, 2, 3, 1, 1, 1, ''),
(11, '2015-12-09 12:28:31', '2015-12-09 12:29:42', 4, 5, 3, 1, 1, 2, '')");

$PDOdb->Execute("INSERT INTO `llx_query` (`rowid`, `date_cre`, `date_maj`, `sql_fields`, `sql_from`, `sql_where`, `sql_afterwhere`, `TField`, `TTable`, `TOrder`, `TTitle`, `TLink`, `THide`, `TTranslate`, `TMode`, `TOperator`, `TGroup`, `TFunction`, `TValue`, `TJoin`, `expert`, `title`, `type`, `xaxis`) VALUES
(1, '2015-12-04 16:14:01', '2015-12-04 16:20:40', 'llx_projet_task.duration_effective,llx_projet_task.planned_workload,llx_projet.ref,llx_projet.fk_statut', 'llx_projet_task LEFT JOIN llx_projet ON (llx_projet.rowid=llx_projet_task.fk_projet) ', '', NULL, 'a:4:{i:0;s:34:\"llx_projet_task.duration_effective\";i:1;s:32:\"llx_projet_task.planned_workload\";i:2;s:14:\"llx_projet.ref\";i:3;s:20:\"llx_projet.fk_statut\";}', 'a:2:{i:0;s:15:\"llx_projet_task\";i:1;s:10:\"llx_projet\";}', 'a:1:{s:14:\"llx_projet.ref\";s:3:\"ASC\";}', 'a:3:{s:34:\"llx_projet_task.duration_effective\";s:9:\"Effectué\";s:32:\"llx_projet_task.planned_workload\";s:6:\"Prévu\";s:14:\"llx_projet.ref\";s:6:\"Projet\";}', 'a:0:{}', 'a:2:{s:14:\"llx_projet.ref\";s:1:\"1\";s:20:\"llx_projet.fk_statut\";s:1:\"1\";}', NULL, 'a:4:{s:34:\"llx_projet_task.duration_effective\";s:5:\"value\";s:32:\"llx_projet_task.planned_workload\";s:5:\"value\";s:14:\"llx_projet.ref\";s:5:\"value\";s:20:\"llx_projet.fk_statut\";s:5:\"value\";}', 'a:0:{}', 'a:1:{i:0;s:14:\"llx_projet.ref\";}', 'a:3:{s:34:\"llx_projet_task.duration_effective\";s:19:\"SUM(@field@ / 3600)\";s:32:\"llx_projet_task.planned_workload\";s:19:\"SUM(@field@ / 3600)\";s:14:\"llx_projet.ref\";s:36:\"CONCAT(@field@,'' '',llx_projet.title)\";}', 'a:1:{s:20:\"llx_projet.fk_statut\";s:1:\"1\";}', 'a:1:{s:10:\"llx_projet\";a:2:{i:0;s:16:\"llx_projet.rowid\";i:1;s:25:\"llx_projet_task.fk_projet\";}}', 0, 'Temps passés / prévus', 'CHART', 'llx_projet.ref'),
(2, '2015-12-04 16:23:08', '2015-12-04 17:00:37', 'llx_projet_task_time.task_date,llx_projet_task_time.task_duration,llx_projet_task_time.fk_user,llx_user.login', 'llx_projet_task_time LEFT JOIN llx_projet_task ON (llx_projet_task.rowid=llx_projet_task_time.fk_task)  LEFT JOIN llx_projet ON (llx_projet.rowid=llx_projet_task.fk_projet)  LEFT JOIN llx_user ON (llx_user.rowid=llx_projet_task_time.fk_user) ', 'llx_projet_task_time.task_date >= (NOW() - INTERVAL 7 DAY)', NULL, 'a:4:{i:0;s:30:\"llx_projet_task_time.task_date\";i:1;s:34:\"llx_projet_task_time.task_duration\";i:2;s:28:\"llx_projet_task_time.fk_user\";i:3;s:14:\"llx_user.login\";}', 'a:4:{i:0;s:20:\"llx_projet_task_time\";i:1;s:15:\"llx_projet_task\";i:2;s:10:\"llx_projet\";i:3;s:8:\"llx_user\";}', 'a:2:{s:30:\"llx_projet_task_time.task_date\";s:3:\"ASC\";s:14:\"llx_user.login\";s:3:\"ASC\";}', 'a:2:{s:34:\"llx_projet_task_time.task_duration\";s:6:\"Durée\";s:14:\"llx_user.login\";s:11:\"Utilisateur\";}', 'a:0:{}', 'a:2:{s:30:\"llx_projet_task_time.task_date\";s:1:\"1\";s:28:\"llx_projet_task_time.fk_user\";s:1:\"1\";}', NULL, 'a:4:{s:30:\"llx_projet_task_time.task_date\";s:8:\"function\";s:34:\"llx_projet_task_time.task_duration\";s:5:\"value\";s:28:\"llx_projet_task_time.fk_user\";s:5:\"value\";s:14:\"llx_user.login\";s:5:\"value\";}', 'a:1:{s:30:\"llx_projet_task_time.task_date\";s:2:\">=\";}', 'a:1:{i:0;s:28:\"llx_projet_task_time.fk_user\";}', 'a:2:{s:30:\"llx_projet_task_time.task_date\";s:29:\"DATE_FORMAT(@field@, ''%m/%Y'')\";s:34:\"llx_projet_task_time.task_duration\";s:17:\"SUM(@field@/3600)\";}', 'a:1:{s:30:\"llx_projet_task_time.task_date\";s:22:\"NOW() - INTERVAL 7 DAY\";}', 'a:3:{s:15:\"llx_projet_task\";a:2:{i:0;s:21:\"llx_projet_task.rowid\";i:1;s:28:\"llx_projet_task_time.fk_task\";}s:10:\"llx_projet\";a:2:{i:0;s:16:\"llx_projet.rowid\";i:1;s:25:\"llx_projet_task.fk_projet\";}s:8:\"llx_user\";a:2:{i:0;s:14:\"llx_user.rowid\";i:1;s:28:\"llx_projet_task_time.fk_user\";}}', 0, 'Temps par développeur à la semaine', 'CHART', 'llx_user.login'),
(3, '2015-12-04 17:04:11', '2015-12-04 20:29:17', 'llx_projet_task_time.task_duration,llx_user.login,llx_projet_task_time.task_date', 'llx_projet_task_time LEFT JOIN llx_projet_task ON (llx_projet_task.rowid=llx_projet_task_time.fk_task)  LEFT JOIN llx_projet ON (llx_projet.rowid=llx_projet_task.fk_projet)  LEFT JOIN llx_user ON (llx_user.rowid=llx_projet_task_time.fk_user) ', 'llx_projet_task_time.task_date > (NOW() - INTERVAL 1 YEAR)', NULL, 'a:3:{i:0;s:30:\"llx_projet_task_time.task_date\";i:1;s:34:\"llx_projet_task_time.task_duration\";i:2;s:14:\"llx_user.login\";}', 'a:4:{i:0;s:20:\"llx_projet_task_time\";i:1;s:15:\"llx_projet_task\";i:2;s:10:\"llx_projet\";i:3;s:8:\"llx_user\";}', 'a:0:{}', 'a:2:{s:34:\"llx_projet_task_time.task_duration\";s:6:\"Durée\";s:14:\"llx_user.login\";s:12:\"Développeur\";}', 'a:0:{}', 'a:1:{s:30:\"llx_projet_task_time.task_date\";s:1:\"1\";}', NULL, 'a:3:{s:34:\"llx_projet_task_time.task_duration\";s:5:\"value\";s:14:\"llx_user.login\";s:5:\"value\";s:30:\"llx_projet_task_time.task_date\";s:8:\"function\";}', 'a:1:{s:30:\"llx_projet_task_time.task_date\";s:1:\">\";}', 'a:1:{i:0;s:14:\"llx_user.login\";}', 'a:1:{s:34:\"llx_projet_task_time.task_duration\";s:19:\"SUM(@field@ / 3600)\";}', 'a:1:{s:30:\"llx_projet_task_time.task_date\";s:23:\"NOW() - INTERVAL 1 YEAR\";}', 'a:3:{s:15:\"llx_projet_task\";a:2:{i:0;s:21:\"llx_projet_task.rowid\";i:1;s:28:\"llx_projet_task_time.fk_task\";}s:10:\"llx_projet\";a:2:{i:0;s:16:\"llx_projet.rowid\";i:1;s:25:\"llx_projet_task.fk_projet\";}s:8:\"llx_user\";a:2:{i:0;s:14:\"llx_user.rowid\";i:1;s:28:\"llx_projet_task_time.fk_user\";}}', 0, 'Temps par utilisateur sur 1 an', 'PIE', 'llx_user.login'),
(5, '2015-12-04 23:26:12', '2015-12-09 12:37:04', 'llx_projet_task_time.task_date,llx_projet_task_time.task_duration', 'llx_projet_task_time LEFT JOIN llx_projet_task ON (llx_projet_task.rowid=llx_projet_task_time.fk_task)  LEFT JOIN llx_projet ON (llx_projet.rowid=llx_projet_task.fk_projet)  LEFT JOIN llx_user ON (llx_user.rowid=llx_projet_task_time.fk_user) ', 'llx_projet_task_time.task_date >= (NOW() - INTERVAL 1 YEAR)', '', 'a:2:{i:0;s:30:\"llx_projet_task_time.task_date\";i:1;s:34:\"llx_projet_task_time.task_duration\";}', 'a:4:{i:0;s:20:\"llx_projet_task_time\";i:1;s:15:\"llx_projet_task\";i:2;s:10:\"llx_projet\";i:3;s:8:\"llx_user\";}', 'a:1:{s:30:\"llx_projet_task_time.task_date\";s:3:\"ASC\";}', 'a:2:{s:30:\"llx_projet_task_time.task_date\";s:4:\"Jour\";s:34:\"llx_projet_task_time.task_duration\";s:6:\"Durée\";}', 'a:0:{}', 'a:0:{}', NULL, 'a:2:{s:30:\"llx_projet_task_time.task_date\";s:8:\"function\";s:34:\"llx_projet_task_time.task_duration\";s:8:\"function\";}', 'a:1:{s:30:\"llx_projet_task_time.task_date\";s:2:\">=\";}', 'a:1:{i:0;s:30:\"llx_projet_task_time.task_date\";}', 'a:2:{s:30:\"llx_projet_task_time.task_date\";s:32:\"DATE_FORMAT(@field@, ''%d/%m/%Y'')\";s:34:\"llx_projet_task_time.task_duration\";s:17:\"SUM(@field@/3600)\";}', 'a:1:{s:30:\"llx_projet_task_time.task_date\";s:23:\"NOW() - INTERVAL 1 YEAR\";}', 'a:3:{s:15:\"llx_projet_task\";a:2:{i:0;s:21:\"llx_projet_task.rowid\";i:1;s:28:\"llx_projet_task_time.fk_task\";}s:10:\"llx_projet\";a:2:{i:0;s:16:\"llx_projet.rowid\";i:1;s:25:\"llx_projet_task.fk_projet\";}s:8:\"llx_user\";a:2:{i:0;s:14:\"llx_user.rowid\";i:1;s:28:\"llx_projet_task_time.fk_user\";}}', 0, 'Temps consacré par jour sur l''année', 'LINE', 'llx_projet_task_time.task_date'),
(6, '2015-12-05 17:04:59', '2015-12-09 16:49:58', 'llx_propal.tms,llx_propal.fk_statut,llx_propal.total_ht,llx_propal.total', 'llx_propal LEFT JOIN llx_user ON (llx_user.rowid=llx_propal.fk_user_author) ', 'llx_propal.tms >= (NOW() - INTERVAL 1 YEAR)', '', 'a:4:{i:0;s:14:\"llx_propal.tms\";i:1;s:20:\"llx_propal.fk_statut\";i:2;s:19:\"llx_propal.total_ht\";i:3;s:16:\"llx_propal.total\";}', 'a:2:{i:0;s:10:\"llx_propal\";i:1;s:8:\"llx_user\";}', 's:0:\"\";', 'a:3:{s:20:\"llx_propal.fk_statut\";s:6:\"Statut\";s:19:\"llx_propal.total_ht\";s:8:\"Total HT\";s:16:\"llx_propal.total\";s:6:\"Nombre\";}', 'a:0:{}', 'a:2:{s:14:\"llx_propal.tms\";s:1:\"1\";s:16:\"llx_propal.total\";s:1:\"1\";}', 'a:1:{s:20:\"llx_propal.fk_statut\";s:57:\"0:Brouillon,1:Ouverte,2:Signée,3:Non Signée,4:Facturée\";}', 'a:4:{s:14:\"llx_propal.tms\";s:8:\"function\";s:20:\"llx_propal.fk_statut\";s:5:\"value\";s:19:\"llx_propal.total_ht\";s:5:\"value\";s:16:\"llx_propal.total\";s:5:\"value\";}', 'a:1:{s:14:\"llx_propal.tms\";s:2:\">=\";}', 'a:1:{i:0;s:20:\"llx_propal.fk_statut\";}', 'a:2:{s:19:\"llx_propal.total_ht\";s:12:\"SUM(@field@)\";s:16:\"llx_propal.total\";s:8:\"COUNT(*)\";}', 'a:1:{s:14:\"llx_propal.tms\";s:23:\"NOW() - INTERVAL 1 YEAR\";}', 'a:1:{s:8:\"llx_user\";a:2:{i:0;s:14:\"llx_user.rowid\";i:1;s:25:\"llx_propal.fk_user_author\";}}', 0, 'Valeurs des propositions sur 1 an par statut', 'PIE', 'llx_propal.fk_statut'),
(7, '2015-12-05 17:04:59', '2015-12-09 17:25:51', 'llx_propal.tms,llx_propal.fk_statut,llx_propal.total_ht,llx_propal.total', 'llx_propal LEFT JOIN llx_user ON (llx_user.rowid=llx_propal.fk_user_author) ', 'llx_propal.tms >= (NOW() - INTERVAL 1 YEAR)', '', 'a:4:{i:0;s:14:\"llx_propal.tms\";i:1;s:20:\"llx_propal.fk_statut\";i:2;s:19:\"llx_propal.total_ht\";i:3;s:16:\"llx_propal.total\";}', 'a:2:{i:0;s:10:\"llx_propal\";i:1;s:8:\"llx_user\";}', 's:0:\"\";', 'a:3:{s:20:\"llx_propal.fk_statut\";s:6:\"Statut\";s:19:\"llx_propal.total_ht\";s:8:\"Total HT\";s:16:\"llx_propal.total\";s:6:\"Nombre\";}', 'a:0:{}', 'a:2:{s:14:\"llx_propal.tms\";s:1:\"1\";s:19:\"llx_propal.total_ht\";s:1:\"1\";}', 'a:1:{s:20:\"llx_propal.fk_statut\";s:57:\"0:Brouillon,1:Ouverte,2:Signée,3:Non Signée,4:Facturée\";}', 'a:4:{s:14:\"llx_propal.tms\";s:8:\"function\";s:20:\"llx_propal.fk_statut\";s:5:\"value\";s:19:\"llx_propal.total_ht\";s:5:\"value\";s:16:\"llx_propal.total\";s:5:\"value\";}', 'a:1:{s:14:\"llx_propal.tms\";s:2:\">=\";}', 'a:1:{i:0;s:20:\"llx_propal.fk_statut\";}', 'a:2:{s:19:\"llx_propal.total_ht\";s:12:\"SUM(@field@)\";s:16:\"llx_propal.total\";s:8:\"COUNT(*)\";}', 'a:1:{s:14:\"llx_propal.tms\";s:23:\"NOW() - INTERVAL 1 YEAR\";}', 'a:1:{s:8:\"llx_user\";a:2:{i:0;s:14:\"llx_user.rowid\";i:1;s:25:\"llx_propal.fk_user_author\";}}', 0, 'Nombre de propositions sur 1 an par statut', 'PIE', 'llx_propal.fk_statut')");
