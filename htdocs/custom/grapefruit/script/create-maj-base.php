<?php
/*
 * Script créant et vérifiant que les champs requis s'ajoutent bien
 */

if(!defined('INC_FROM_DOLIBARR')) {
	define('INC_FROM_CRON_SCRIPT', true);

	require('../config.php');

}


/* uncomment


dol_include_once('/mymodule/class/xxx.class.php');

$PDOdb=new TPDOdb;

$o=new TXXX($db);
$o->init_db_by_vars($PDOdb);
*/
global $db;
$sql = "INSERT INTO `".MAIN_DB_PREFIX."c_actioncomm` (`id`, `code`, `type`, `libelle`, `module`, `active`, `todo`, `position`, `color`) VALUES ('104997', 'AC_STI_BILL', 'module', 'Relance Facture', 'grapefruit', '1', NULL, '101', NULL);";
$res = $db->query($sql);

//
