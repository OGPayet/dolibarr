<?php

class TQuery extends TObjetStd {

	function __construct() {
        global $langs;

		$langs->load("stocks");
		$langs->load("orders");
		$langs->load("bills");

        parent::set_table(MAIN_DB_PREFIX.'query');
        parent::add_champs('sql_fields,sql_from,sql_where,sql_afterwhere',array('type'=>'text'));
		parent::add_champs('TField,TTable,TOrder,TTitle,TTotal,TLink,TAlias,THide,TTranslate,TNull,TMode,TOperator,TGroup,TFunction,TValue,TJoin,TFilter,TType,TClass,TMethod',array('type'=>'array'));
		parent::add_champs('expert,nb_result_max,fk_bdd',array('type'=>'integer'));

		parent::add_champs('uid',array('index'=>true, 'length'=>32));

        parent::_init_vars('title,type,xaxis');
        parent::start();

		$this->TGraphiqueType = array(
			'LIST'=>$langs->trans('List')
			,'SIMPLELIST'=>$langs->trans('SimpleList')
			,'CHART'=>$langs->trans('Columns')
			,'LINE'=>$langs->trans('Lines')
			,'PIE'=>$langs->trans('Pie')
			,'AREA'=>$langs->trans('Area')
		);

		$this->TClassName = array(
			'User'=>$langs->trans('User')
			,'Societe'=>$langs->trans('Company')
			,'Contact'=>$langs->trans('Contact')
			,'Facture'=>$langs->trans('Invoice')
			,'Propal'=>$langs->trans('Propal')
			,'Commande'=>$langs->trans('Order')
			,'Task'=>$langs->trans('Task')
			,'Project'=>$langs->trans('Project')
			,'Product'=>$langs->trans('Product')
			,'Entrepot'=>$langs->trans('Warehouse')
			,'CommandeFournisseur'=>$langs->trans('SupplierOrder')
			,'FactureFournisseur'=>$langs->trans('SupplierInvoice')
			,'ActionComm'=>$langs->trans('Event')
		);

		$this->TMethodName = array(
			'getNomUrl'=>$langs->trans('getNomUrl')
			,'getLibStatut'=>$langs->trans('getLibStatut')
			,'getNomUrl,getLibStatut'=>$langs->trans('getNomUrlAndLibStatus')

		);

		$this->show_details = true;
		$this->preview = false;
    }

	static function getQueries(&$PDOdb) {

		$Tab = array();

		$Tab = TRequeteCore::_get_id_by_sql($PDOdb, "SELECT rowid, title FROM ".MAIN_DB_PREFIX."query WHERE 1 ORDER BY title", 'title', 'rowid');

		return $Tab;
	}

	static function getTables(&$PDOdb) {

		$PDOdb->Execute("SHOW TABLES");

		$Tab = array();
		while($obj = $PDOdb->Get_line()) {


			$t = array_values((array)$obj);

			$Tab[] = $t[0];

		}

		return $Tab;

	}

	static function getFields(&$PDOdb, $table) {
		global $langs;

		$PDOdb->Execute("DESCRIBE ".$table);

		$Tab = array();
		while($obj = $PDOdb->Get_line()) {

			$Tab[] = $obj;

		}

		foreach($Tab as &$obj) {

			$obj->label = $langs->trans(ucfirst($obj->Field));

			$TValue = $PDOdb->ExecuteAsArray("SELECT DISTINCT ".$obj->Field." as 'val' FROM ".$table."
				WHERE ".$obj->Field." IS NOT NULL AND ".$obj->Field."!='' LIMIT 6");
			$sample = '';
			foreach($TValue as $k=>$v) {
				if($k>4){ $sample.=', ...';  break;}

				if(!empty($sample))$sample.=', ';
				$sample.=$v->val;
			}

			$obj->sample = dol_escape_js($sample);

		}

		return $Tab;


	}

	function run($show_details = true, $height=0, $table_element='', $objectid=0, $preview = -1, $force_list_mode = false) {
		global $conf;

		$PDOdb = &$this->pdodb;

		$this->show_details = $show_details;
		if($preview!==-1) $this->preview = $preview;

		if(empty($this->nb_result_max)) $this->nb_result_max =empty($conf->global->ABRICOT_NB_MAX_RESULT_SQL) ? 2000 : $conf->global->ABRICOT_NB_MAX_RESULT_SQL;

		if($this->preview)$this->nb_result_max = 10;
		if(!empty($height)) $this->height = $height;


		if($force_list_mode) {
			$list= load_fiche_titre($this->title).$this->runList($PDOdb,'',$table_element,$objectid);
		}
		else if($this->type == 'CHART') {
			$list= $this->runChart($PDOdb,'ColumnChart',$table_element,$objectid);
		}
		else if($this->type == 'LINE') {
			$list= $this->runChart($PDOdb,'LineChart',$table_element,$objectid);
		}else if($this->type == 'PIE') {
			$list= $this->runChart($PDOdb,'PieChart',$table_element,$objectid);
		}
		else if($this->type == 'AREA') {
			$list= $this->runChart($PDOdb,'AreaChart',$table_element,$objectid);
		}
		else if($this->type == 'RAW' ) {
			return $this->runRAW($PDOdb,$table_element,$objectid);
		}
		else if($this->type == 'SIMPLELIST' || $this->preview) {
			$list= load_fiche_titre($this->title).$this->runList($PDOdb,dol_buildpath('/query/tpl/html.simplelist.tbs.html'),$table_element,$objectid);
		}
		else {
			$list= load_fiche_titre($this->title).$this->runList($PDOdb,'',$table_element,$objectid);
		}


		$form=new TFormCore();
		$html.= $form->begin_form('auto','formQuery'. $this->getId(),'get');
		$action=GETPOST('action')!='' ? GETPOST('action') : 'run';
		$html.=  $form->hidden('action', $action);
		$html.=  $form->hidden('id', GETPOST('id') ? GETPOST('id') : $this->getId());

		$html.=$list;

		$html.=$form->end();

		return $html;
	}

	private function getField($field) {

		list($t, $fname) = explode('.', $field);
                $fname_concat = empty($fname) ? $t : $t.'_'.$fname;

		return  $fname_concat;
	}

	function load(&$PDOdb, $id, $loadChild=true) {

		$res = parent::load($PDOdb, $id, $loadChild);

		if($this->expert == 1) {
			$this->sql_fields = $this->getSQLFieldsWithAlias();
		}

		$this->connect($PDOdb);

		return $res;
	}

	function connect(&$PDOdb) {

		dol_include_once('/query/class/bddconnector.class.php');

		$this->bdd=new TBDDConnector;
		if($this->fk_bdd>0) $this->bdd->load($PDOdb, $this->fk_bdd);

		$this->bdd->connect();

		$this->pdodb = &$this->bdd->pdodb;


	}

	private function extractAliasFromSQL() {

		dol_include_once('/query/lib/query.lib.php');

		$this->TAlias = array();
		$TField = explode_brackets($this->sql_fields);
		foreach($TField as $field) {
			$pos = strripos($field, ' as ') ;
			if($pos !== false) {

				$alias = trim(substr($field, $pos + 4));
				$field = trim(substr($field, 0, $pos));

				list($field,$table) = _getFieldAndTableName($field);

				$this->TAlias[$alias] = array($field,$table);
			}

		}

		//$this->TField = $TField;


	}

	function save(&$PDOdb) {

		if(empty($this->uid))$this->uid = md5( time().$this->title.rand(1000,999999) );

		$this->extractAliasFromSQL();

		return parent::save($PDOdb);

	}

	private function getSQLFieldsWithAlias () {

		dol_include_once('/query/lib/query.lib.php');

		$TField = explode_brackets($this->sql_fields);

		foreach($TField as &$field) {

			if(strripos($field, ' as ') === false) {
				$field.= ' as '.$this->getField($field);
			}


		}

		return implode(',', $TField);

	}

	private function getRequestParam($sql) {

		$sql = preg_replace_callback('/(@REQUEST_[a-zA-z0-9_-]+@)/i',function($matches) {

			$field = substr($matches[0] , 9, -1);


			if(!empty($field) ) {

				$val = GETPOST($field);

				return $val;

			}


		}, $sql);

		return $sql;
	}

	function getSQL($table_element='',$objectid=0) {

		global $conf, $user;

		$sql = '';

		if($this->expert == 2) {
			$sql = $this->getRequestParam("SELECT ".$this->sql_fields."
				FROM ".$this->sql_from."
	                        WHERE (".($this->sql_where ? $this->sql_where : 1 ).")
		                ".$this->sql_afterwhere);
		}
		else if($this->expert == 1) {

			if(empty($this->sql_afterwhere) && !empty($this->TGroup)) {
					$this->sql_afterwhere=" GROUP BY ".implode(',', $this->TGroup);
			}

			$sql = "SELECT ".$this->getSQLFieldsWithAlias() ."
				FROM ".$this->sql_from."
	                        WHERE (".($this->sql_where ? $this->sql_where : 1 ).")
		                ".$this->sql_afterwhere;
		}
		else {
			$this->sql_fields = '';
			foreach($this->TField as $field) {

				if(!empty($this->sql_fields))$this->sql_fields.=',';

				$fname_concat = $this->getField($field);

				$this->getNonAliasField($this->TFunction);
				if(!empty($this->TFunction[$field])) {
					$this->sql_fields.=strtr($this->TFunction[$field], array('@field@'=> $field)).' as "'.$fname_concat.'"';
				}
				else{
					$this->sql_fields.=$field.' as "'.$fname_concat.'"';
				}

			}

			$sql="SELECT ".($this->sql_fields ? $this->sql_fields : '*') ."
				FROM ".$this->sql_from;

			if(empty($this->TFunction) || !empty($conf->global->QUERY_DO_NOT_USE_HAVING))	{
				$sql.= " WHERE (".($this->sql_where ? $this->sql_where : 1 ).") ";
			} else {
				$sql.= " WHERE 1"; // on évite que le contenu du prochain if() se retrouve dans le FROM
			}

			if(!empty($table_element) && strpos($sql, $table_element)!==false) {
				$sql.=' AND '.MAIN_DB_PREFIX.$table_element.'.rowid='.$objectid;
			}

			if(!empty($this->TGroup)) {
				$sql.=" GROUP BY ".implode(',', $this->TGroup);
			}

			if(!empty($this->TFunction) && !empty($this->sql_where) && empty($conf->global->QUERY_DO_NOT_USE_HAVING)) {
				$sql.=' HAVING '. $this->getSQLHavingBindFunction($this->sql_where);
			}

			if($this->preview && stripos($sql,'LIMIT ') === false) $sql.=" LIMIT 5";

		}

		// Merge some generic fields from Dolibarr
		$TDoliData = array(
			'{entity}' => $conf->entity
			,'{userid}' => $user->id
		);
		$sql = strtr($sql, $TDoliData);

		return $sql;
	}

	private function getSQLHavingBindFunction($sql) {

		$TFunction = & $this->TFunction;

		$sql = preg_replace_callback('/([a-z_]+\.{1}[a-z_]+)/i',function($matches) use($TFunction) {
			$field = $matches[0];

			 if(isset($TFunction[$field])) {

				$r=  strtr( $TFunction[$field], array('@field@'=>$field));
				return $r;
			 }
			else{
				return $field;
			}

		}, $sql);

		return $sql;
	}

	function getBind() {

		return array(); // desactivation du mode bind qui présente trop de problème avec l'interprétation des requêtes

		$TBind = array();
		if(!empty($this->TMode)) {
			/*$this->getNonAliasField($this->TMode);
			$this->getNonAliasField($this->TOperator);
			$this->getNonAliasField($this->TValue);*/

			foreach($this->TMode as $f=>$m) {

				if(empty($this->TOperator[$f])) continue;

				$fBind  = strtr($f, '.', '_');
				$fSearch = strtr($f,'.','_');

				if($m == 'function') {
					null;
				}
				else if($m == 'var') {

					if($this->TOperator[$f] == '=') {
						null; //impossible case
					}
					else if($this->TOperator[$f] == '<') {
						$TBind[$fBind] = PHP_INT_MAX;
					}
					else if($this->TOperator[$f] == '>') {
						$TBind[$fBind] = ~PHP_INT_MAX;
					}
					else {
						$TBind[$fBind] = '%';
					}

				}
				else if(!empty($this->TValue[$f])) {
					$TBind[$fBind] = $this->TValue[$f];
				}
				else {
					$TBind[$fBind] = NULL; // mauvaise définition de la valeur à chercher
				}


			}

		}

		return $TBind ;

	}

	function getOperator() {
		$Tab = array();
		if(!empty($this->TOperator)) {
			$this->getNonAliasField($this->TOperator);
			foreach($this->TOperator as $f=>$v) {
				if($v) {
					$fSearch = strtr($f,'.','_');
					$Tab[$fSearch]= $v;
				}

			}

		}
		return $Tab;
	}

	function getTitle() {


		$Tab = array();
		if(!empty($this->TTitle)) {
			$this->getNonAliasField($this->TTitle);
			foreach($this->TTitle as $f=>$v) {
				if($v) {
					$fSearch = strtr($f,'.','_');
					$Tab[$fSearch]= $v;
				}

			}

		}
		return $Tab;
	}

	function getEval() {

		$Tab=array();
		$TabTMP=array();

		if(!empty($this->TClass)) {

			$this->getNonAliasField($this->TClass);

			foreach($this->TClass as $f=>$v) {
				if($v) {
					$fSearch = strtr($f,'.','_');

					$method = empty($this->TMethod[$fSearch]) ? 'getNomUrl' : $this->TMethod[$fSearch];

					$Tab[$fSearch]= 'TQuery::getCustomMethodForObject("'.$v.'", "@val@", "'.$method.'")';
				}

			}

		}

		return $Tab;
	}

	private function getNonAliasField(&$Tab) {

		return false; // AA disabled

		if(empty($Tab) || $this->expert == 0) return false;

		$Tmp = $Tab;

		foreach($Tmp as $f=>$v) {

			if(strpos($f, '.')!==false) {

				list($table, $field) = explode('.', $f);
				$field = trim($field);
				if(!empty($field) && empty($Tab[ $field ]))$Tab[ $field ] = $v;

			}

		}

		return true;
	}

	static function getNomUrlAndLibStatut($type, $id, $get_nom_url=true, $get_lib_status=true) {

		$TRes = '';

		if($get_nom_url) $TRes[] = self::getNomUrl($type, $id);
		if($get_lib_status) $TRes[] = self::getLibStatut($type, $id);

		return implode(' ', $TRes);
	}

	static function getNomUrl($type, $id) {
			return self::getCustomMethodForObject($type,$id,'getNomUrl');
	}

	static function getCustomMethodForObject($type, $id, $methods) {
		global $langs, $db, $conf;

		if(empty($methods) || empty($id)) return '';

		list($classname, $include) = explode(',', $type);

		dol_include_once('/core/class/html.form.class.php');

		if(empty($include)) {
			if($classname == 'User') dol_include_once('/user/class/user.class.php');
			else if($classname == 'Facture') dol_include_once('/compta/facture/class/facture.class.php');
			else if($classname == 'Propal') dol_include_once('/comm/propal/class/propal.class.php');
			else if($classname == 'Commande') dol_include_once('/commande/class/commande.class.php');
			else if($classname == 'Task') dol_include_once('/projet/class/task.class.php');
			else if($classname == 'Projet' || $classname == 'Project') dol_include_once('/projet/class/project.class.php');
			else if($classname == 'Product') dol_include_once('/product/class/product.class.php');
			else if($classname == 'Societe') dol_include_once('/societe/class/societe.class.php');
			else if($classname == 'Contact') dol_include_once('/contact/class/contact.class.php');
			else if($classname == 'Entrepot') dol_include_once('/product/stock/class/entrepot.class.php');
			else if($classname == 'CommandeFournisseur') dol_include_once('/fourn/class/fournisseur.commande.class.php');
			else if($classname == 'FactureFournisseur') dol_include_once('/fourn/class/fournisseur.facture.class.php');
			else if($classname == 'ActionComm') dol_include_once('/comm/action/class/actioncomm.class.php');
			else {
				return $langs->trans('ImpossibleToIncludeClass').' : '.$classname;
			}
		}
		else{
			if(!dol_include_once($include)) {
				return $langs->trans('ImpossibleToIncludeClass').' : '.$include;
			}
		}

		if(!class_exists($classname))return $langs->trans('ClassNotIncluded');

		$o=new $classname($db);
		if(method_exists($o, 'fetch')) {
			if(is_numeric($id)) $o->fetch($id);
			else $o->fetch(0,$id);
		}
		else if(method_exists($o, 'load')) {
			$PDOdb=new TPDOdb;
			$o->load($PDOdb,$id);
		}
		else{
			return $langs->trans('ImpossibleToLoadObject').' : '.$classname;
		}

		$TMethod = explode(',', $methods);
		$TResult = array();
		foreach($TMethod as $method) {
			if(method_exists($o, $method)) {

				$param1 = null;
				if($method == 'getNomUrl')$param1 = 1;
				else if($method == 'getLibStatut')$param1 = 3;

				if(!is_null($param1))$TResult[] = $o->$method($param1);
				else $TResult[] = $o->$method();
			}
			else {
				$TResult[] = $langs->trans('Method').' '.$methods.' '. $langs->trans('NotExist');
			}

		}

//var_dump('getCustomMethodForObject',$type, $id, $methods,$TResult); exit();
		return implode(' ',$TResult);
	}

	static function getLibStatut($type, $id) {

		self::getCustomMethodForObject($type,$id,'getLibStatut');

	}

	function getType() {


		$Tab = array();
		if(!empty($this->TType)) {
			$this->getNonAliasField($this->TType);
			foreach($this->TType as $f=>$v) {
				if($v) {
					$fSearch = strtr($f,'.','_');
					$Tab[$fSearch]= $v;
				}

			}

		}
		return $Tab;
	}
	function getHide() {

		$THide = array();
		if(!empty($this->THide)) {
			$this->getNonAliasField($this->THide);
			foreach($this->THide as $f=>$v) {
				if($v) {
					$fSearch = strtr($f,'.','_');
					$THide[]= $fSearch;
				}

			}

		}
		return $THide;
	}
	function getTranslate() {

		$Tab = array();
		if(!empty($this->TTranslate)) {
			$this->getNonAliasField($this->TTranslate);
			foreach($this->TTranslate as $f=>$v) {
				$fSearch = strtr($f,'.','_');
				$Tab[$fSearch]=array();

				$TPair = str_getcsv($v, ',','"');

				foreach($TPair as $pair) {

					$pos = strpos($pair,':');
					if($pos!==false) {
						$from = substr($pair, 0, $pos );
						$to = substr($pair, $pos+1 );

						$Tab[$fSearch]["$from"] = $to;
					}


				}

			}

		}

		return $Tab;
	}
	function getTotal() {


		$Tab = array();
		if(!empty($this->TTotal)) {
			$this->getNonAliasField($this->TTotal);
			foreach($this->TTotal as $f=>$v) {

				if(is_array($v)) {
					$v[1] = strtr($v[1],'.','_');
				}

				$fSearch = strtr($f,'.','_');
				$Tab[$fSearch]=$v;
			}

		}

		return $Tab;
	}
	function getSearch() {

		$TSearch = array();

		$TTranslate = $this->getTranslate();

		if($this->preview) return array(); // mode preview, pas de recherche

		if(!empty($this->TMode)) {
			//var_dump($this->TFilter);
			foreach($this->TMode as $f=>$m) {

				if(($this->expert==0 && empty($this->TOperator[$f])) || $m!='var') continue;

				if(!empty($this->TAlias[$f])) {
					list($field,$tbl) = $this->TAlias[$f];

				}
				else{
					list($tbl, $field) = explode('.', $f);

					if(empty($field)) {
						$field = $tbl; $tbl = '';
					}
				}
				$fSearch = strtr($f,'.','_');

				if(!empty($this->TFilter[$f])) {
					$filter = $this->TFilter[$f];
				}
				else if(!empty($TTranslate[$fSearch]) && is_array($TTranslate[$fSearch])) {
					$filter = $TTranslate[$fSearch];
				}
				else {
					$filter = true;
				}

				$TSearch[$fSearch] = array(
					'recherche'=>$filter
					,'table'=>$tbl
					,'field'=>$field
					,'allow_is_null'=>(!empty( $this->TNull[$f] ) ? 1 : 0)
				);

			}

		}
		//var_dump('$TSearch',$TSearch);
		return $TSearch;
	}
	function runChart(&$PDOdb, $type = 'LineChart',$table_element='',$objectid=0) {
		global $conf,$langs;

		$sql=$this->getSQL($table_element,$objectid);
		$TBind = $this->getBind();
		$TSearch = $this->getSearch();
		$THide = $this->getHide();
		$TTitle = $this->getTitle();
		$TTranslate = $this->getTranslate();
		$TOperator = $this->getOperator();

		//list($xTable, $xaxis) = explode('.',$this->xaxis);
		$xaxis = strtr($this->xaxis,'.','_');

		$html = '';

		if($this->show_details) $html.= '<div class="query">'.$sql.'</div>';

		$r=new TListviewTBS('chart'.$this->getId());
		$html .= $r->render($PDOdb, $sql,array(
			'view_type'=>'chart'
			,'chartType'=>$type
			,'translate'=>$TTranslate
			,'liste'=>array(
				'titre'=>$this->title
			)
			,'no-select'=>true
			,'limit'=>array('global'=> $this->nb_result_max)
			,'title'=>$TTitle
			,'xaxis'=>$xaxis
			,'hide'=>$THide
			,'search'=>$TSearch
			,'height'=>$this->height
			,'curveType'=>$conf->global->QUERY_GRAPH_LINESTYLE
			,'pieHole'=>$conf->global->QUERY_GRAPH_PIEHOLE
			,'operator'=>$TOperator
		),$TBind);

		if($this->show_details) {
				$html.=  '<div class="query">';
				$Tab=array();
				foreach($r->TBind as $f=>$v) {
					$Tab[] = $f.' : '.$v;
				}
				$html.=  implode(', ', $Tab);
				$html.=  '</div>';

		}

		if($this->preview<=0) $html.='<div class="tabsAction"> <input type="submit" class="butAction" name="show_as_list" value="'.$langs->trans('ShowGraphAsList').'" /> </div>';

		return $html;
	}

	function runRAW(&$PDOdb, $table_element='',$objectid=0) {
		global $conf,$langs;
		$sql=$this->getSQL($table_element,$objectid);
		$TBind = $this->getBind();
		$TSearch = $this->getSearch();
		$THide = $this->getHide();
		$TTranslate = $this->getTranslate();
		$TTotal = $this->getTotal();
		$TOperator = $this->getOperator();
		$TType = $this->getType();
		$TEval = $this->getEval();
		$TTitle=$this->getTitle();

		$r=new TListviewTBS('lRunQuery'. $this->getId());

		return $r->render($PDOdb, $sql,array(
				'link'=>$this->TLink
				,'view_type'=>'raw'
				,'no-select'=>true
				,'hide'=>$THide
				,'title'=>$TTitle
				,'liste'=>array(
					'titre'=>''
				)
				,'limit'=>array('global'=> $this->nb_result_max)
				,'type'=>$TType
				,'orderBy'=>$this->TOrder
				,'translate'=>$TTranslate
				,'search'=>$TSearch
				,'export'=>array(
					'CSV','TXT'
				)
				,'operator'=>$TOperator
				,'math'=>$TTotal
				,'eval'=>$TEval
			)
			,$TBind);
	}
	function runList(&$PDOdb, $template = '',$table_element='',$objectid=0) {
			global $conf,$langs;
			$html = '';

			$sql=$this->getSQL($table_element,$objectid);
			$TBind = $this->getBind();
			$TSearch = $this->getSearch();
			$THide = $this->getHide();
			$TTranslate = $this->getTranslate();
			$TTotal = $this->getTotal();
			$TOperator = $this->getOperator();
			$TType = $this->getType();
			$TEval = $this->getEval();


			if($this->show_details) $html.= '<div class="query">'.$sql.'</div>';

			$TTitle=$this->getTitle();

			$r=new TListviewTBS('lRunQuery'. $this->getId(), $template);
//echo 3;
			$html.=  $r->render($PDOdb, $sql,array(
				'link'=>$this->TLink
				,'no-select'=>true
				,'hide'=>$THide
				,'title'=>$TTitle
				,'liste'=>array(
					'titre'=>''
				)
				,'limit'=>array('global'=> $this->nb_result_max)
				,'type'=>$TType
				,'orderBy'=>$this->TOrder
				,'translate'=>$TTranslate
				,'search'=>$TSearch
				,'export'=>array(
					'CSV','TXT'
				)
				,'operator'=>$TOperator
				,'math'=>$TTotal
				,'eval'=>$TEval
			)
			,$TBind);
//echo 4;
			if($this->show_details) {
				$html.=  '<div class="query">';
				$Tab=array();
				foreach($r->TBind as $f=>$v) {
					$Tab[] = $f.' : '.$v;
				}
				$html.=  implode(', ', $Tab);
				$html.=  '</div>';

			}

			if($this->type=='CHART' || $this->type=='LINE' || $this->type=='PIE' || $this->type=='AREA') {
				$html.='<div class="tabsAction"> <input type="submit" class="butAction" name="show_as_graph" value="'.$langs->trans('ShowGraphNormal').'" /> </div>';
			}


			return $html;
	}


	/**
	 * Permet de asvoir si l'utilisateur connecté est autorisé à accéder à la requête
	 */
	function userHasRights(&$PDOdb, &$user) {

		// On part du principe que les admin ont accès à toutes les requêtes
		if($user->admin) return true;

		/**
		 * Vérification que la requête est soit
		 * - publique (aucun droit défini)
		 * - autorisée à l'utilisateur
		 * - autorisée à un de ses groupes
		 */
		$sql="SELECT q.rowid
			FROM ".MAIN_DB_PREFIX."query q
			LEFT JOIN ".MAIN_DB_PREFIX."query_rights qr ON (qr.fk_query = q.rowid)
			LEFT JOIN ".MAIN_DB_PREFIX."usergroup_user uu ON (uu.fk_usergroup = qr.fk_element)
			WHERE q.rowid = ".$this->getId()."
			AND (
				(qr.element = 'user' AND qr.fk_element = ".$user->id.")
				OR (qr.element = 'group' AND uu.fk_user = ".$user->id.")
				OR qr.rowid IS NULL
			)
		 ";

		$PDOdb->Execute($sql);
		if($PDOdb->Get_Recordcount() > 0) return true;

		return false;
	}
}


dol_include_once('/core/class/menubase.class.php');

class TQueryMenu extends TObjetStd {
	function __construct() {
        global $langs;

        parent::set_table(MAIN_DB_PREFIX.'query_menu');
        parent::add_champs('fk_menu,fk_const_tab,fk_query,fk_dashboard,entity',array('type'=>'int','index'=>true));
		parent::_init_vars('title,perms,mainmenu,leftmenu,type_menu,tab_object');
        parent::start();

		$this->type_menu = 'MENU';
		$this->TTypeMenu=array(
			'MENU'=>$langs->trans('Menu')
			,'TAB'=>$langs->trans('Tab')
		);

		$this->TTabObject=array(

			'thirdparty'=>$langs->trans('Thirdparty')
			,'contact'=>$langs->trans('Contact')
			,'product'=>$langs->trans('Product')
			,'user'=>$langs->trans('User')
			,'group'=>$langs->trans('Group')
			,'project'=>$langs->trans('Project')
	        // 'intervention'		to add a tab in intervention view
	        // 'order_supplier'		to add a tab in supplier order view
	        // 'invoice_supplier'	to add a tab in supplier invoice view
	        // 'invoice'			to add a tab in customer invoice view
	        // 'order'				to add a tab in customer order view
	        // 'product'			to add a tab in product view
	        // 'stock'				to add a tab in stock view
	        // 'propal'				to add a tab in propal view
	        // 'member'				to add a tab in fundation member view
	        // 'contract'			to add a tab in contract view
	        // 'user'				to add a tab in user view
	        // 'group'				to add a tab in group view
	        // 'contact'

		);

	}

	private function getUrl() {
		if($this->fk_query>0) $url = '/query/query.php?action=run&id='.$this->fk_query.'&_a='.time();
		else if($this->fk_dashboard>0)$url = '/query/dashboard.php?action=run&id='.$this->fk_dashboard.'&_a='.time();

		return $url;
	}

	private function setMenu() {
	global $db,$langs,$conf,$user;
		if($this->fk_menu <= 0) {
			$menu = new Menubase($db,'all');

		    $menu->module='query';
			$menu->type='left';
	        $menu->mainmenu=$menu->fk_mainmenu=$this->mainmenu;
	        $menu->fk_leftmenu=$this->leftmenu;

			$menu->leftmenu = 'querymenu'.$this->getId();

	        $menu->fk_menu=-1;

	        $menu->position=500 + $this->getId();

	        $menu->url=$this->getUrl();

	        $menu->target='';
	        $menu->titre=$this->title;
	        $menu->langs='query.lang';
	        $menu->perms=$this->perms;
	        $menu->enabled=0;
	        $menu->user=2;

	        $menu->level=0;
			$res = $menu->create($user);

			if($res<=0) {
				var_dump($menu);
				exit('Erreur lors de la création du menu');
			}

			$this->fk_menu = $menu->id;
		}
		else{

			$menu = new Menubase($db,'all');
			if($menu->fetch($this->fk_menu)>0) {

				$menu->mainmenu=$menu->fk_mainmenu=$this->mainmenu;
			$menu->fk_leftmenu=$this->leftmenu;
				$menu->url=$this->getUrl();
				$menu->leftmenu = 'querymenu'.$this->getId();
				$menu->position=500 + $this->getId();
				$menu->titre=$this->title;
				$menu->enabled=0;
				$menu->level=0;
				$menu->user=2;
				$menu->update($user);

			}

		}

	}


	private function setTab() {
		global $db;
		dol_include_once('/core/lib/admin.lib.php');
		$tab = $this->tab_object.':+tabQuery'.$this->getId().':'.$this->title.':query@query:'.$this->getUrl()
			.'&tab_object='.$this->tab_object.'&fk_object=__ID__&menuId='.$this->getId();

		dolibarr_set_const($db,'MAIN_MODULE_QUERY_TABS_'.$this->getId(), $tab);

	}


	function save(&$PDOdb) {

		global $db,$conf,$user;
		$this->entity = $conf->entity;

		if($this->type_menu=='MENU') {
			$this->setMenu();
			$this->deleteTab();
		}

		parent::save($PDOdb);

		if($this->type_menu=='TAB'){
			$this->deleteMenu();
			$this->setTab();
		}


	}

	function delete(&$PDOdb) {

		parent::delete($PDOdb);

		$this->deleteMenu();
		$this->deleteTab();
	}
	private function deleteMenu() {
		if($this->fk_menu > 0) {
			global $db,$conf,$user;
			$menu = new Menubase($db,'all');
			if($menu->fetch($this->fk_menu)>0) {
				$menu->delete($user);
			}
		}
	}

	private function deleteTab() {
			global $db,$conf,$user;
			dol_include_once('/core/lib/admin.lib.php');

	                dolibarr_del_const($db,'MAIN_MODULE_QUERY_TABS_'.$this->getId());

	}

	static function getMenu(&$PDOdb, $type) {
		global $langs;

		if($type == 'main') {
			$Tab = TRequeteCore::_get_id_by_sql($PDOdb, "SELECT DISTINCT mainmenu FROM ".MAIN_DB_PREFIX."menu WHERE 1 ORDER BY mainmenu", 'mainmenu', 'mainmenu');
			$Tab['companies'] = 'companies';
		}
		else{
			$Tab = TRequeteCore::_get_id_by_sql($PDOdb, "SELECT DISTINCT leftmenu FROM ".MAIN_DB_PREFIX."menu WHERE 1 ORDER BY leftmenu", 'leftmenu', 'leftmenu');
			$Tab['thirdparties'] = 'thirdparties';
			$Tab['query'] = 'query';
			$Tab['projects'] = 'projects';
			$Tab['customers_bills'] = 'customers_bills';
		}


		return $Tab;
	}

	static function getHeadForObject($tab_object,$fk_object) {
		global $db,$conf,$langs,$user;
		$head = array();

		if(empty($tab_object)) return $head;

		if($tab_object === 'product' ) {
			dol_include_once('/product/class/product.class.php');
			dol_include_once('/core/lib/product.lib.php');
			$object = new Product($db);
			$object->fetch($fk_object);
			$head=product_prepare_head($object);

		}
		else if($tab_object === 'thirdparty' ) {
			dol_include_once('/societe/class/societe.class.php');
			dol_include_once('/core/lib/company.lib.php');
			$object = new Societe($db);
			$object->fetch($fk_object);
			$head=societe_prepare_head($object);

		}
		else if($tab_object === 'contact' ) {
			dol_include_once('/contact/class/contact.class.php');
			dol_include_once('/core/lib/contact.lib.php');
			$object = new Contact($db);
			$object->fetch($fk_object);
			$head=contact_prepare_head($object);

		}
		else if($tab_object === 'user' ) {
			dol_include_once('/user/class/user.class.php');
			dol_include_once('/core/lib/usergroups.lib.php');
			$object = new User($db);
			$object->fetch($fk_object);
			$head=user_prepare_head($object);

		}
		else if($tab_object === 'group' ) {
			dol_include_once('/user/class/usergroup.class.php');
			dol_include_once('/lib/usergroups.lib.php');
			$object = new UserGroup($db);
			$object->fetch($fk_object);
			$head=group_prepare_head($object);

		}
		else if($tab_object === 'project' ) {
			dol_include_once('/projet/class/project.class.php');
			dol_include_once('/core/lib/project.lib.php');
			$object = new Project($db);
			$object->fetch($fk_object);
			$head=project_prepare_head($object);

		}



		return $head;
	}

}

class TQueryRights extends TObjetStd {
	function __construct() {
        global $langs;

		parent::set_table(MAIN_DB_PREFIX.'query_rights');
		parent::add_champs('entity,fk_query,fk_element',array('type'=>'int','index'=>true));
		parent::_init_vars('element');
		parent::start();
	}
}