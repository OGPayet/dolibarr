<?php

require 'config.php';
dol_include_once('/query/class/bddconnector.class.php');

if(empty($user->rights->query->bdd->write)) {
	accessforbidden();
}

$object = new TBDDConnector;
$PDOdb=new TPDOdb;
$action=GETPOST('action');

switch ($action) {

	case 'save':
		$object->load($PDOdb, GETPOST('id'));

		if(GETPOST('bt_delete')!='') {
			$object->delete($PDOdb);
			setEventMessage('BDDDeleted');
		}
		else{
			$object->set_values($_POST);
			$object->save($PDOdb);
			setEventMessage('BDDSaved');
		}


		_list($PDOdb);

		break;
	case 'edit':
		$object->load($PDOdb, GETPOST('id'));
		_card($PDOdb, $object);

		break;

	case 'new':

		_card($PDOdb,$object);

		break;

	default:

		_list($PDOdb);

		break;
}

function _card(&$PDOdb,&$object) {
	global $langs, $conf,$user,$db;

	if(empty($user->rights->query->bdd->use_other_db))return '';

	llxHeader();
	dol_fiche_head(array(),'bdd','BDD');

	$tbs = new TTemplateTBS;

	$object->connect();

	$formCore=new TFormCore('auto','formBDD','post');
	echo $formCore->hidden('action', 'save');
	echo $formCore->hidden('id', $object->getId());

	echo $tbs->render('tpl/bdd.html',array(
	),array(
		'object'=>array(
			'host'=>$formCore->texte('', 'host', $object->host, 30,128)
			,'db_name'=>$formCore->texte('', 'db_name', $object->db_name, 30,128)
			,'login'=>$formCore->texte('', 'login', $object->login, 30,128)
			,'password'=>$formCore->texte('', 'password', $object->password, 30,128)
			,'port'=>$formCore->texte('', 'port', $object->port, 5,5)
			,'charset'=>$formCore->texte('', 'charset', $object->charset, 10,128)
			,'db_type'=>$formCore->combo('', 'db_type', $object->TDBType, $object->db_type)
		)
		,'view'=>array(
			'langs'=>$langs
			,'buttons'=>$formCore->btsubmit($langs->trans('Delete'), 'bt_delete','','butActionDelete').' &nbsp; '. $formCore->btsubmit($langs->trans('Save'), 'bt_save')
		)
	));

	$formCore->end();

	dol_fiche_end();

	llxFooter();
}

function _list(&$PDOdb) {
	global $langs, $conf,$user,$db;
	llxHeader();


		$url = 'lib/adminer/?';

		//TODO genrate read profile
		/*
		$url.='&server='.$dolibarr_main_db_host;
		$url.='&db='.$dolibarr_main_db_name;
		$url.='&username='.$dolibarr_main_db_user;
		$url.='&password='.$dolibarr_main_db_pass;
		$url.='&driver='.$dolibarr_main_db_type;
		*/
?>

	<a href="<?php echo $url; ?>" class="butAction" target="_blank">Accès à la base de données</a>

<?php

	if(!empty($user->rights->query->bdd->use_other_db)) {


	dol_fiche_head(array(),'bdd','BDD');


	$l=new TListviewTBS('lMenu');
	$sql = "SELECT rowid, host, db_name,login,port,charset, '' as 'alive'
	FROM ".MAIN_DB_PREFIX."query_bdd_connector
	WHERE entity IN (0,".$conf->entity.")";

	echo $l->render($PDOdb, $sql,array(

		'title'=>array(
			'host'=>$langs->trans('Host')
			,'db_name'=>$langs->trans('DBName')
			,'port'=>$langs->trans('Port')
			,'charset'=>$langs->trans('Charset')
			,'login'=>$langs->trans('Login')
			,'alive'=>$langs->trans('Alive')

		)
		,'link'=>array(
			'host'=>'<a href="?id=@rowid@&action=edit">@val@</a>'
		)
		,'hide'=>array('rowid')
		,'type'=>array(
			'date_cre'=>'date'
		)
		,'eval'=>array(
			'alive'=>'_test_alive(@rowid@)'
		)

	));


	/*$kiwi = new TKiwi;
	$kiwi->fk_soc = $object->id;
	$kiwi->fk_product = 1;
	$kiwi->save($PDOdb);
	*/
	// pied de page
	dol_fiche_end();
	}

	llxFooter();

}

function _test_alive($fk_bbb) {
	$PDOdb=new TPDOdb;

	$bdd=new TBDDConnector;
	$bdd->load($PDOdb, $fk_bbb);
	ob_start();
	$alive = $bdd->connect();
	ob_clean();
	return $alive ? img_picto('Ok', 'on.png') : img_picto('KO','error.png');

}
