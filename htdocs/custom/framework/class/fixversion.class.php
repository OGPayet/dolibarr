<?php
/* Copyright (C) 2016		 Oscss-Shop       <support@oscss-shop.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */


class Fixversion{

	public
	/**
		@var
	*/
			$tbl = array()
	/**
		@var
	*/
		, $col = array()
	/**
		@var
	*/
		, $last = array()
	/**
		@var
	*/
		, $corss=array(
					'thirdparty'=>array(
						'name'=>"Thirdparty",
						'class'=>'Societe',
						'table'=>'societe',
						'datefieldname'=>'datep',
						'path'=>'societe/',
						'tabs'=>'thirdparty',
						// tabs
						'nameid'=>'socid',
						'lang'=>'company'
						),

					'user'=>array(
						'name'=>"Users",
						'class'=>'User',
						'table'=>'user',
						'datefieldname'=>'datep',
						'path'=>'user/',
						'tabs'=>'user',
						// tabs
						'nameid'=>'id',
						'lang'=>'user'
						),

					'group'=>array(
						'name'=>"Groups",
						'class'=>'UserGroup',
						'table'=>'usergroup',
						'datefieldname'=>'datep',
						'path'=>'group/',
						'tabs'=>'group',
						// tabs
						'nameid'=>'id',
						'lang'=>'usergroup'
						),


					'propal'=>array(
						'name'=>"Proposals",
						'class'=>'Propal',
						'table'=>'propal',
						'datefieldname'=>'datep',
						'path'=>'comm/propal/',
						'tabs'=>'propal',
						// tabs
						'nameid'=>'id',
						'lang'=>'propal'
						),
					'order'=>array(
						'name'=>"CustomersOrders",
						'class'=>'Commande',
						'table'=>'commande',
						'datefieldname'=>'date_commande',
						'path'=>'commande/',
						// tabs
						'nameid'=>'id',
						'tabs'=>'order',
						'lang'=>'orders'
						),
					'invoice'=>array(
						'name'=>"CustomersInvoices",
						'class'=>'Facture',
						'table'=>'facture',
						'datefieldname'=>'datef',
						'path'=>'compta/facture/',
						// tabs
						'nameid'=>'facid',
						'tabs'=>'invoice',
						'lang'=>'bills',
						),

					'invoice_predefined'=>array(
						'name'=>"PredefinedInvoices",
						'class'=>'FactureRec',
						'table'=>'facture_rec',
						'datefieldname'=>'datec',
						'path'=>'supplier_proposal/',
						// tabs
						'nameid'=>'id',
						'tabs'=>'invoice_predefined',
						'lang'=>'bills'
						),
					'proposal_supplier'=>array(
						'name'=>"SuppliersProposals",
						'class'=>'SupplierProposal',
						'table'=>'supplier_proposal',
						'datefieldname'=>'date',
						'path'=>'fourn/facture/',
						// tabs
						'nameid'=>'id',
						'tabs'=>'proposal_supplier',

						'lang'=>'supplier_proposal'
						),
					'order_supplier'=>array(
						'name'=>"SuppliersOrders",
						'class'=>'CommandeFournisseur',
						'table'=>'commande_fournisseur',
						'datefieldname'=>'date_commande',
						'path'=>'fourn/commande/',
						// tabs
						'nameid'=>'id',
						'tabs'=>'supplier_order',

						'lang'=>'suppliers'
						),
					'invoice_supplier'=>array(
						'name'=>"BillsSuppliers",
						'class'=>'FactureFournisseur',
						'table'=>'facture_fourn',
						'datefieldname'=>'datef',
						'path'=>'fourn/facture/',
						// tabs
						'nameid'=>'facid',
						'tabs'=>'supplier_invoice',

						'lang'=>'suppliers'
						),
					'contract'=>array(
						'name'=>"Contracts",
						'title'=>"ListContractAssociatedQualityreport",
						'class'=>'Contrat',
						'table'=>'contrat',
						'datefieldname'=>'date_contrat',
						'path'=>'contrat/',
						// tabs
						'nameid'=>'id',
						'tabs'=>'contract',

						'lang'=>'contract'
						),
// 'intervention'=>array(
// 	'name'=>"Interventions",
// 	'title'=>"ListFichinterAssociatedQualityreport",
// 	'class'=>'Fichinter',
// 	'table'=>'fichinter',
// 	'datefieldname'=>'date_valid',
// 	'disableamount'=>1,
//     'urlnew'=>DOL_URL_ROOT.'/fichinter/card.php?action=create&origin=qualityreport&originid='.$id.'&socid='.$socid,
//     'lang'=>'interventions',
//     'buttonnew'=>'AddIntervention',
//     'testnew'=>$user->rights->ficheinter->creer,
//     'test'=>$conf->ficheinter->enabled && $user->rights->ficheinter->lire),
// 'trip'=>array(
// 	'name'=>"TripsAndExpenses",
// 	'title'=>"ListExpenseReportsAssociatedQualityreport",
// 	'class'=>'Deplacement',
// 	'table'=>'deplacement',
// 	'datefieldname'=>'dated',
// 	'margin'=>'minus',
// 	'disableamount'=>1,
//     'urlnew'=>DOL_URL_ROOT.'/deplacement/card.php?action=create&qualityreportid='.$id.'&socid='.$socid,
//     'lang'=>'trips',
//     'buttonnew'=>'AddTrip',
//     'testnew'=>$user->rights->deplacement->creer,
//     'test'=>$conf->deplacement->enabled && $user->rights->deplacement->lire),
// 'expensereport'=>array(
// 	'name'=>"ExpenseReports",
// 	'title'=>"ListExpenseReportsAssociatedQualityreport",
// 	'class'=>'ExpenseReportLine',
// 	'table'=>'expensereport_det',
// 	'datefieldname'=>'date',
// 	'margin'=>'minus',
// 	'disableamount'=>0,
//     'urlnew'=>DOL_URL_ROOT.'/expensereport/card.php?action=create&qualityreportid='.$id.'&socid='.$socid,
//     'lang'=>'trips',
//     'buttonnew'=>'AddTrip',
//     'testnew'=>$user->rights->expensereport->creer,
//     'test'=>$conf->expensereport->enabled && $user->rights->expensereport->lire),
// // 'donation'=>array(
// // 	'name'=>"Donation",
// // 	'title'=>"ListDonationsAssociatedQualityreport",
// // 	'class'=>'Don',
// // 	'margin'=>'add',
// // 	'table'=>'don',
// // 	'datefieldname'=>'datedon',
// // 	'disableamount'=>0,
// //     'urlnew'=>DOL_URL_ROOT.'/don/card.php?action=create&qualityreportid='.$id.'&socid='.$socid,
// //     'lang'=>'donations',
// //     'buttonnew'=>'AddDonation',
// //     'testnew'=>$user->rights->don->creer,
// //     'test'=>$conf->don->enabled && $user->rights->don->lire),
// 'agenda'=>array(
// 	'name'=>"Agenda",
// 	'title'=>"ListActionsAssociatedQualityreport",
// 	'class'=>'ActionComm',
// 	'table'=>'actioncomm',
// 	'datefieldname'=>'datep',
// 	'disableamount'=>1,
//     'urlnew'=>DOL_URL_ROOT.'/comm/action/card.php?action=create&qualityreportid='.$id.'&socid='.$socid,
//     'lang'=>'agenda',
//     'buttonnew'=>'AddEvent',
//     'testnew'=>$user->rights->agenda->myactions->create,
//     'test'=>$conf->agenda->enabled && $user->rights->agenda->myactions->read),
// 'taskin'=>array(
// 	'name'=>"TaskTimeValorised",
// 	'title'=>"ListTaskTimeUserQualityreport",
// 	'class'=>'Taskin',
// 	'margin'=>'minus',
// 	'table'=>'taskin',
// 	'datefieldname'=>'task_date',
// 	'disableamount'=>0,
// 	'test'=>$conf->qualityreport->enabled && $user->rights->qualityreport->lire && empty($conf->global->QUALITYREPORT_HIDE_TASKS)),
);

	/**
		@var protected
	*/
	protected
				$dolv = 0
		 , 	$shortdolv = 0
			/**
				@var array list ressource based on class name
			*/
		 , $byclassname = array();

	public function __construct($db){
		$this->db= $db;
		$this->dolv= DOL_VERSION;
		$this->shortdolv =  substr(DOL_VERSION, 0, -2);

		foreach($this->corss as $k=>$r){
			$r['type'] = $k;
			$this->byclassname[strtolower($r['class'])] = $r;

			$this->bytypename[strtolower($k)] = $r;
		}


	}

// 	GetClassByType
	/**
		@fn GetTbl($table)
		@brief
	*/
	public function __call($name, $args) {

		if(substr($name, 0,3) === 'Get')  {
			preg_match_all('#([A-Z][a-z]*)#', $name, $match);


			$vars = 'by'.strtolower($match[0][3]).'name';
			// call current val
			if(count($this->last)>0 && empty($args[0]) ){
					if(isset($this->last[strtolower($match[0][1])] ) )
						return $this->last[strtolower($match[0][1])];
			}
			elseif(isset($this->{$vars})) {
				if(isset($this->{$vars}[strtolower($args[0])])){
					$this->SetLast( $this->{$vars}[strtolower($args[0])] );

					if(isset($this->last[strtolower($match[0][1])] ) )
						return $this->last[strtolower($match[0][1])];
					else
						return false;
				}
				return $this->{$vars};
			}
		}
		return false;
	}

	public function SetLast($array){
		$this->last = $array;
	}
	/**
		@fn GetTbl($table)
		@brief
	*/
// 	public function GetClassByTypeName($typename){
// 		$class = strtolower($typename);
// 		if(isset($this->bytypename[$class]))
// 			return $this->bytypename[$class]['class'];
//
// 		return $class;
// 	}

	/**
		@fn GetTypeByClassName($table)
		@brief
	*/
// 	public function GetTypeByClassName($classname){
// 		$class = strtolower($classname);
// 		if(isset($this->byclassname[$class]))
// 			return $this->byclassname[$class]['type'];
//
// 		return $class;
// 	}


	/**
		@fn GetTbl($table)
		@brief
	*/
	public function GetTbl($table){
		if(isset($this->tbl[$table]))
			return $this->tbl[$table];

		return $table;
	}

	/**
		@fn GetCol($name, $table)
		@brief
	*/
	public function GetCol($name, $table){
		if(isset($this->col[$name]))
			return $this->col[$name];
		elseif(isset($this->col[$table][$name]))
			return $this->col[$table][$name];


		return $name;
	}
}
