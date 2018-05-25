<?php

	require '../config.php';
	dol_include_once('/query/class/query.class.php');
	dol_include_once('/query/class/dashboard.class.php');

	$PDOdb=new TPDOdb;

	$get = GETPOST('get');
	$put = GETPOST('put');

	switch ($get) {
		case 'tables':
			$query=new TQuery;
			$query->load($PDOdb, GETPOST('id'));
			__out( TQuery::getTables($query->pdodb) , 'json' );

			break;
		case 'fields':
			$query=new TQuery;
			$query->load($PDOdb, GETPOST('id'));
			__out( TQuery::getFields($query->pdodb, GETPOST('table')) , 'json' );

			break;

		default:

			break;
	}


	switch ($put) {
		case 'query':

			$query = new TQuery;
			$query->load($PDOdb, GETPOST('id'));

			$query->set_values($_REQUEST);

			$query->THide = GETPOST('THide');
			$query->TOperator = GETPOST('TOperator');
			$query->TGroup = GETPOST('TGroup');
			$query->TFunction = GETPOST('TFunction');
			$query->TValue = GETPOST('TValue');
			$query->TNull = GETPOST('TNull');
			$query->TOrder = GETPOST('TOrder');
			$query->TTranslate = GETPOST('TTranslate');
			$query->TFilter = GETPOST('TFilter');
			$query->TType = GETPOST('TType');
			$query->TClass = GETPOST('TClass');
			$query->TMethod = GETPOST('TMethod');

			$query->sql_fields = base64_decode(GETPOST('sql_fields'));
			$query->sql_from = base64_decode(GETPOST('sql_from'));
			$query->sql_where = base64_decode(GETPOST('sql_where'));
			$query->sql_afterwhere = base64_decode(GETPOST('sql_afterwhere'));
//			$PDOdb->debug=true;
			$query->save($PDOdb);

			print $query->getId();

			break;

		case 'dashboard':
			$dash=new TQDashBoard;
			$dash->load($PDOdb, GETPOST('id'));

			$dash->set_values($_REQUEST);

			$dash->save($PDOdb);

			print $dash->getId();

			break;

		case 'dashboard-query-link':
			$dash=new TQDashBoard;
			if($dash->load($PDOdb, GETPOST('fk_qdashboard'))) {

				$TCoord = $_REQUEST['TCoord'];

				foreach($TCoord as &$coord) {
					$dash->TQDashBoardQuery[(int)$coord['k']]->set_values($coord);
				}

				$dash->save($PDOdb);

				print 1;

			}
			else {
				print 0;
			}

			break;

		case 'dashboard-query-remove':
			$tile = new TQDashBoardQuery;
			$tile->load($PDOdb, GETPOST('id'));
			$tile->delete($PDOdb);

			echo 1;

			break;
		case 'dashboard-query':
			$dash=new TQDashBoard;
			if($dash->load($PDOdb, GETPOST('fk_qdashboard'))) {
				$k = $dash->addChild($PDOdb, 'TQDashBoardQuery');

				$dash->TQDashBoardQuery[$k]->fk_query = GETPOST('fk_query');

				$dash->save($PDOdb);

				print $k;

			}
			else {
				print 0;
			}

			break;
	}
