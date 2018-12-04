<?php
/* Copyright (C) 2012-2017	Charlie BENKE	 <charlie@patas-monkey.com>
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

/**
 *	\defgroup   Equipement	 Module Equipement cards
 *	\brief	  Module to manage Equipement cards
 *	\file	   htdocs/core/modules/modEquipement.class.php
 *	\ingroup	Matériels
 *	\brief	  Fichier de description et activation du module Equipement
 */

include_once(DOL_DOCUMENT_ROOT ."/core/modules/DolibarrModules.class.php");


/**
 *	\class	  modEquipement
 *	\brief	  Classe de description et activation du module Equipement
 */
class modequipement extends DolibarrModules
{
	/**
	*   Constructor. Define names, constants, directories, boxes, permissions
	*
	*   @param	  DoliDB		$db	  Database handler
	*/
	function __construct($db)
	{
		global $conf;

		$this->db = $db;
		$this->numero = 160070;

		$this->family = "products";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = "Gestion des équipements et des numéros de série";

		$this->version = $this->getLocalVersion();

		$this->editor_name = "<b>Patas-Monkey</b>";
		$this->editor_web = "http://www.patas-monkey.com";

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 0;
		$this->picto = $this->name."@".$this->name;

		// Data directories to create when module is enabled
		$this->dirs = array("/equipement/temp");

		// Dependencies
		$this->depends = array("modProduct");
		$this->requiredby = array();
		$this->conflictwith = array();
		$this->langfiles = array("products","companies", $this->name."@".$this->name);

		// Config pages
		$this->config_page_url = array($this->name.".php@".$this->name);

		// Constantes
		$this->const = array();
		$r=0;

		$this->const[$r][0] = "EQUIPEMENT_ADDON_PDF";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "soleil";
		$this->const[$r][4] = 1;
		$r++;

		$this->const[$r][0] = "EQUIPEMENT_ADDON";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "mod_atlantic";
		$this->const[$r][4] = 1;
		$r++;

		// hook pour la recherche
		$this->module_parts = array(
			'hooks' => array('searchform'),
			'tpllinkable' => 1,
			'contactelement' => 1,
			'models' => 1
		);

		// contact element setting
		$this->contactelement=1;

		// Boites
		$this->boxes = array();
		$r=0;
		$this->boxes[$r][1] = "box_equipement.php@equipement";
		$r++;
		$this->boxes[$r][1] = "box_equipementevt.php@equipement";

		// Permissions
		$r=0;
		$this->rights = array();
		$this->rights_class = $this->name;

		$r++;
		$this->rights[$r][0] = 160071;
		$this->rights[$r][1] = 'Lire des equipements';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'lire';

		$r++;
		$this->rights[$r][0] = 160072;
		$this->rights[$r][1] = 'Creer/modifier des equipements';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'creer';

		$r++;
		$this->rights[$r][0] = 160073;
		$this->rights[$r][1] = 'Supprimer des equipements';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'supprimer';

		$r++;
		$this->rights[$r][0] = 160074;
		$this->rights[$r][1] = 'Exporter les equipements';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'export';

		$r++;
		$this->rights[$r][0] = 160075;
		$this->rights[$r][1] = 'Modifier numéro de série des équipements';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'majserial';


		// Left-Menu of Equipement module
		$r=1;
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=products',
					'type'=>'left',
					'titre'=>'Equipement',
					'mainmenu'=>'products',
					'leftmenu'=>'equipement',
					'url'=>'/equipement/index.php?leftmenu=equipement',
					'langs'=>'equipement@equipement',
					'position'=>110, 'enabled'=>'1',
					'perms'=>'1',
					'target'=>'', 'user'=>2);
		$r++;
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=products,fk_leftmenu=equipement',
					'type'=>'left',
					'titre'=>'NewEquipement',
					'mainmenu'=>'', 'leftmenu'=>'',
					'url'=>'/equipement/card.php?action=create&leftmenu=equipement',
					'langs'=>'equipement@equipement',
					'position'=>110, 'enabled'=>'1',
					'perms'=>'1',
					'target'=>'', 'user'=>2);
		$r++;
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=products,fk_leftmenu=equipement',
					'type'=>'left',
					'titre'=>'ListOfEquipements',
					'mainmenu'=>'', 'leftmenu'=>'',
					'url'=>'/equipement/list.php?leftmenu=equipement',
					'langs'=>'equipement@equipement',
					'position'=>110, 'enabled'=>'1',
					'perms'=>'1',
					'target'=>'', 'user'=>2);
		$r++;
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=products,fk_leftmenu=equipement',
					'type'=>'left',
					'titre'=>'NewEvents',
					'mainmenu'=>'', 'leftmenu'=>'',
					'url'=>'/equipement/cardevents.php?leftmenu=equipement',
					'langs'=>'equipement@equipement',
					'position'=>110, 'enabled'=>'1',
					'perms'=>'1',
					'target'=>'', 'user'=>2);
		$r++;
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=products,fk_leftmenu=equipement',
					'type'=>'left',
					'titre'=>'ListOfEquipEvents',
					'mainmenu'=>'', 'leftmenu'=>'',
					'url'=>'/equipement/listEvent.php?leftmenu=equipement',
					'langs'=>'equipement@equipement',
					'position'=>110, 'enabled'=>'1',
					'perms'=>'1',
					'target'=>'', 'user'=>2);

		// si la composition est active on gère la tracabilité composant
		if ($conf->global->MAIN_MODULE_FACTORY) {
			$r++;
			$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=products,fk_leftmenu=equipement',
						'type'=>'left',
						'titre'=>'ListOfSubEquipement',
						'mainmenu'=>'', 'leftmenu'=>'',
						'url'=>'/equipement/listSubEquip.php?leftmenu=equipement',
						'langs'=>'equipement@equipement',
						'position'=>110, 'enabled'=>'1',
						'perms'=>'1',
						'target'=>'', 'user'=>2);
		}

		$r++;
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=products,fk_leftmenu=equipement',
					'type'=>'left',
					'titre'=>'CalendarEvents',
					'mainmenu'=>'', 'leftmenu'=>'',
					'url'=>'/equipement/agendaevent.php?leftmenu=equipement',
					'langs'=>'equipement@equipement',
					'position'=>110, 'enabled'=>'1',
					'perms'=>'1',
					'target'=>'', 'user'=>2);


		// Additionnals Equipement tabs in other modules
		// Additionnals Equipement tabs for adding Events in other modules
		$this->tabs = array('thirdparty:+equipement:Equipements:@equipement:/equipement/tabs/societe.php?id=__ID__',
			'invoice:+equipement:Equipements:@equipement:/equipement/tabs/facture.php?id=__ID__',
			'supplier_invoice:+equipement:Equipements:@equipement:/equipement/tabs/facturefourn.php?id=__ID__',
//			'contract:+equipement:Equipements:@equipement:/equipement/tabs/contrat.php?id=__ID__',
//			'contract:+eventadd:EventsAdd:@equipement:/equipement/tabs/contratAdd.php?id=__ID__',
			'intervention:+equipement:Equipements:@equipement:/equipement/tabs/fichinter.php?id=__ID__',
			'intervention:+eventadd:EventsAdd:@equipement:/equipement/tabs/fichinterAdd.php?id=__ID__',
			'delivery:+equipement:Equipements:@equipement:/equipement/tabs/expedition.php?id=__ID__',
			'delivery:+eventadd:EventsAdd:@equipement:/equipement/tabs/expeditionAdd.php?id=__ID__',
			'stock:+equipement:Equipements:@equipement:/equipement/tabs/entrepot.php?id=__ID__',
			'supplier_order:+equipement:Equipements:@equipement:/equipement/tabs/supplier_order.php?id=__ID__',
			'project:+equipement:Equipements:@equipement:/equipement/tabs/project.php?id=__ID__',
			'project:+eventadd:EventsAdd:@equipement:/equipement/tabs/projectAdd.php?id=__ID__',
			'factory:+equipement:Equipements:@equipement:/equipement/tabs/factory.php?id=__ID__',
			'product:+equipement:Equipements:@equipement:/equipement/tabs/produit.php?id=__ID__');

		// dictionnarys
		$this->dictionnaries = array('langs'=>array('equipement@equipement'),
		'tabname'=>array(MAIN_DB_PREFIX."c_equipement_etat",MAIN_DB_PREFIX."c_equipementevt_type"),
		'tablib'=>array("Etat d'équipement","Type d'évènement equipement"),
		'tabsql'=>array("SELECT rowid, code, libelle, active FROM ".MAIN_DB_PREFIX."c_equipement_etat",
						"SELECT rowid, code, libelle, active, color FROM ".MAIN_DB_PREFIX."c_equipementevt_type"),
		'tabsqlsort'=>array("code ASC, libelle ASC","code ASC, libelle ASC"),
		'tabfield'=>array("code,libelle","code,libelle,color"),
		'tabfieldvalue'=>array("code,libelle", "code,libelle,color"),
		'tabfieldinsert'=>array("code,libelle", "code,libelle,color"),
		'tabrowid'=>array("rowid","rowid"), 'tabcond'=>array($conf->equipement->enabled, $conf->equipement->enabled));

		//Exports
		//--------
		$r=1;

		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='EquipementList';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_permission[$r]=array(array("equipement","export"));
		$this->export_fields_array[$r]=array('e.rowid'=>"EquipId",'e.ref'=>"EquipRef",'e.fk_product'=>"IdProduct",
		'p.ref'=>"RefProduit",'e.numversion'=>"VersionNumber",'e.datec'=>"EquipDateCreation",'e.fk_statut'=>'EquipStatus',
		'e.description'=>"EquipNote",'e.dateo'=>"Dateo",'e.datee'=>"Datee",'e.fk_etatequipement'=>"EtatEquip",
		'e.numimmocompta'=>"NumImmoCompta", 'e.fk_soc_fourn'=>"CompanyFournish",'e.fk_soc_client'=>"CompanyClient",
		'e.fk_facture_fourn'=>"RefFactFourn",'e.fk_facture'=>"RefFactClient",'e.fk_entrepot'=>"EntrepotStock",
		'ee.rowid'=>'EquipLineId','ee.datec'=>"EquipLineDate",'ee.description'=>"EquipLineDesc",
		'ee.fk_equipementevt_type'=>"TypeofEquipementEvent",'ee.dateo'=>"Dateo",'ee.datee'=>"Datee",
		'ee.fulldayevent'=>"FullDayEvent",'ee.total_ht'=>"EquipementLineTotalHT",
		'ee.fk_fichinter'=>'Interventions','ee.fk_contrat'=>"Contracts",'ee.fk_expedition'=>"Deliveries");

		/// type de champs possible
		// Text / Numeric / Date / List:NomTable:ChampLib / Duree  / Boolean
		$this->export_TypeFields_array[$r]=array('e.ref'=>"Text",'e.fk_product'=>"List:product:label",'p.ref'=>"Text",
		'e.numversion'=>"Text",'e.datec'=>"Date",'e.fk_statut'=>'Statut','e.description'=>"Text",
		'e.dateo'=>"Date",'e.datee'=>"Date",'e.fk_etatequipement'=>"List:c_equipement_etat:libelle",'e.numimmocompta'=>"Text",
		'e.fk_soc_fourn'=>"List:societe:nom",'e.fk_soc_client'=>"List:societe:nom",
		'e.fk_facture_fourn'=>"List:facture_fourn:ref_ext",'e.fk_facture'=>"List:facture:facnumber",
		'e.fk_entrepot'=>"List:entrepot:label",'ee.datec'=>"Date",'ee.duree'=>"Duree",'ee.description'=>"Text",
		'ee.fk_equipementevt_type'=>"List:c_equipementevt_type:libelle",'ee.dateo'=>"Date",'ee.datee'=>"Date",
		'ee.fulldayevent'=>"Boolean",'ee.total_ht'=>"Number",'ee.fk_fichinter'=>'List:fichinter:ref',
		'ee.fk_contrat'=>"List:contrat:ref",'ee.fk_expedition'=>"List:expedition:ref");

		$this->export_entities_array[$r]=array(
		'e.rowid'=>"equipement@equipement:Equipement",'e.ref'=>"equipement@equipement:Equipement",
		'e.fk_product'=>"product",'p.ref'=>"product",'e.numversion'=>"equipement@equipement:Equipement",
		'e.datec'=>"equipement@equipement:Equipement",'e.fk_statut'=>'equipement@equipement:Equipement',
		'e.description'=>"equipement@equipement:Equipement",'e.dateo'=>"equipement@equipement:Equipement",
		'e.datee'=>"equipement@equipement:Equipement",'e.fk_etatequipement'=>"equipement@equipement:Equipement",
		'e.numimmocompta'=>"equipement@equipement:Equipement",'e.fk_soc_fourn'=>"company",'e.fk_soc_client'=>"company",
		'e.fk_facture_fourn'=>"invoice",'e.fk_facture'=>"invoice",'e.fk_entrepot'=>"stock",
		'ee.rowid'=>'equipement@equipement:Equipement','ee.datec'=>"equipement@equipement:Equipement",
		'ee.duree'=>"equipement@equipement:Equipement",'ee.description'=>"equipement@equipement:Equipement",
		'ee.fk_equipementevt_type'=>"equipement@equipement:Equipement",'ee.dateo'=>"equipement@equipement:Equipement",
		'ee.datee'=>"equipement@equipement:Equipement",'ee.fulldayevent'=>"equipement@equipement:Equipement",
		'ee.total_ht'=>"equipement@equipement:Equipement",
		'ee.fk_fichinter'=>'intervention','ee.fk_contrat'=>"contract",'ee.fk_expedition'=>"sending");
		if (DOL_VERSION >= "3.9.0") {

			$keyforselect='equipement'; $keyforelement='equipement@equipement:Equipement'; $keyforaliasextra='eex';
			include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';

			$keyforselect='equipementevt'; $keyforelement='equipement@equipement:Equipement'; $keyforaliasextra='eeex';
			include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		}

		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r] =' FROM '.MAIN_DB_PREFIX.'equipement as e ';
		$this->export_sql_end[$r].=' LEFT JOIN '.MAIN_DB_PREFIX.'equipement_extrafields as eex ON e.rowid = eex.fk_object';
		$this->export_sql_end[$r].=' LEFT JOIN '.MAIN_DB_PREFIX.'equipementevt as ee ON (e.rowid = ee.fk_equipement)';
		$this->export_sql_end[$r].=' LEFT JOIN '.MAIN_DB_PREFIX.'equipementevt_extrafields as eeex ON ee.rowid = eeex.fk_object';
		$this->export_sql_end[$r].=' , '.MAIN_DB_PREFIX.'product as p ';

//		$this->export_sql_end[$r].=' LEFT JOIN  '.MAIN_DB_PREFIX.'societe as sclient ON (e.fk_soc_client = sclient.rowid)';
//		$this->export_sql_end[$r].=' LEFT JOIN '.MAIN_DB_PREFIX.'societe as sfourn ON (e.fk_soc_fourn = sfourn.rowid)';

		$this->export_sql_end[$r].=' WHERE e.entity = '.$conf->entity;
		$this->export_sql_end[$r].=' AND e.fk_product = p.rowid';


		// Imports
		//--------
		$r=1;

		$this->import_code[$r]=$this->rights_class.'_'.$r;
		$this->import_label[$r]="Equipements";	// Translation key
		$this->import_icon[$r]=$this->picto;
		$this->import_entities_array[$r]=array('e.fk_soc_client'=>'company','e.fk_soc_fourn'=>'company',
			'e.fk_facture'=>'bill','e.fk_facture_fourn'=>'bill','e.fk_entrepot'=>'stock','e.fk_product'=>'product');
		$this->import_tables_array[$r]=array(
						'e'=>MAIN_DB_PREFIX.'equipement',
						'extra'=>MAIN_DB_PREFIX.'equipement_extrafields'
		);
		//$this->import_tables_creator_array[$r]=array('e'=>'fk_user_author');	// Fields to store import user id
		$this->import_fields_array[$r]=array(	'e.ref'=>"Ref*",
			'e.description'=>"Description",
			'e.fk_product'=>"ProductID",
			'e.numversion'=>"NumVersion",
			'e.fk_statut'=>"StatutId",
			'e.fk_soc_client'=>"SocClientid",
			'e.fk_soc_fourn'=>"SocFournid",
			'e.fk_facture'=>"FactClientid",
			'e.fk_facture_fourn'=>"FactFournid",
			'e.fk_entrepot'=>"Entrepotid",
			'e.note_private'=>"NotePrivate",
			'e.note_public'=>"NotePublic",
			'e.numImmoCompta'=>"NumImmoCompta",
			'e.dateo'=>'DateStart',
			'e.datee'=>'DateEnd',
			'e.datec'=>'DateCreation'
		);
		// aliastable.field => ('user->id' or 'lastrowid-'.tableparent)
		$this->import_fieldshidden_array[$r]=array('s.fk_user_creat'=>'user->id');
		$this->import_convertvalue_array[$r]=array(
			'e.fk_product'=>array('rule'=>'fetchidfromref','classfile'=>'/product/class/product.class.php','class'=>'Product','method'=>'fetch','element'=>'product'),
			'e.fk_soc_client'=>array('rule'=>'fetchidfromref','classfile'=>'/societe/class/societe.class.php','class'=>'Societe','method'=>'fetch','element'=>'ThirdParty'),
			'e.fk_soc_fourn'=>array('rule'=>'fetchidfromref','classfile'=>'/societe/class/societe.class.php','class'=>'Societe','method'=>'fetch','element'=>'ThirdParty'),
			'e.fk_entrepot'=>array('rule'=>'fetchidfromref','classfile'=>'/product/stock/class/entrepot.class.php','class'=>'Entrepot','method'=>'fetch','element'=>'Entrepot'),
			'e.fk_facture'=>array('rule'=>'fetchidfromref','classfile'=>'/compta/facture/class/facture.class.php','class'=>'Facture','method'=>'fetch','element'=>'facture'),
			'e.fk_facture_fourn'=>array('rule'=>'fetchidfromref','classfile'=>'/fourn/class/fournisseur.facture.class.php','class'=>'FactureFournisseur','method'=>'fetch','element'=>'FactureFournisseur')
		);

		$this->import_examplevalues_array[$r]=array(
						'e.ref'=>"SN1111111",
						'e.fk_product'=>"PRDTEST",
						'e.numversion'=>"1",
						'e.fk_statut'=>"0",
						'e.fk_soc_client'=>"PATAS-MONKEY.COM",
						'e.fk_soc_fourn'=>"",
						'e.fk_facture'=>"FA1211-0041",
						'e.fk_facture_fourn'=>"",
						'e.fk_entrepot'=>"refEntreprot",
						'e.note_private'=>"Note Privée",
						'e.note_public'=>"Note Publique",
						'e.numImmoCompta'=>"",
						'e.dateo'=>'',
						'e.datee'=>'',
						'e.datec'=>'12-11-2012'
		);
		// Add extra fields
		$import_extrafield_sample=array();
		$sql = "SELECT name, label, fieldrequired FROM ".MAIN_DB_PREFIX."extrafields ";
		$sql.= " WHERE elementtype = 'equipement' AND entity IN (0, ".$conf->entity.')';
		$resql=$this->db->query($sql);
		if ($resql) {
			// This can fail when class is used on old database (during migration for example)
			while ($obj=$this->db->fetch_object($resql)) {
				$fieldname='extra.'.$obj->name;
				$fieldlabel=ucfirst($obj->label);
				$this->import_fields_array[$r][$fieldname]=$fieldlabel.($obj->fieldrequired?'*':'');
				$import_extrafield_sample[$fieldname]=$fieldlabel;
			}
		}
		// End add extra fields

//
		$r++;
		$this->import_code[$r]=$this->rights_class.'_'.$r;
		$this->import_label[$r]="ActionsOnEquipement";	// Translation key
		$this->import_icon[$r]=$this->picto;
		// icons for contract, intervention and expedition
		$this->import_entities_array[$r]=array();
		$this->import_tables_array[$r]=array(
						'ee'=>MAIN_DB_PREFIX.'equipementevt',
						'extra'=>MAIN_DB_PREFIX.'equipementevt_extrafields'
		);
		$this->import_fields_array[$r]=array(
						'ee.fk_equipement'=>"RefEquipement",
						'ee.description'=>"Description",
						'ee.fulldayevent'=>"FullDayEvent",
						'ee.total_ht'=>"Total_ht",
						'ee.fk_fichinter'=>"Fichinterid",
						'ee.fk_contrat'=>"Contractid",
						'ee.fk_expedition'=>"Expeditionid",
						'ee.dateo'=>'DateStart',
						'ee.datee'=>'DateEnd',
						'ee.datec'=>'DateCreation'
		);
		// Add extra fields
		$import_extrafield_sample=array();
		$sql="SELECT name, label, fieldrequired FROM ".MAIN_DB_PREFIX."extrafields as extra";
		$sql.=" WHERE elementtype = 'equipementevt' AND entity IN (0, ".$conf->entity.')';
		$resql=$this->db->query($sql);
		if ($resql) {
			// This can fail when class is used on old database (during migration for example)
			while ($obj=$this->db->fetch_object($resql)) {
				$fieldname='extra.'.$obj->name;
				$fieldlabel=ucfirst($obj->label);
				$this->import_fields_array[$r][$fieldname]=$fieldlabel.($obj->fieldrequired?'*':'');
				$import_extrafield_sample[$fieldname]=$fieldlabel;
			}
		}
		// End add extra fields

		$this->import_fieldshidden_array[$r] = array(
			's.fk_user_creat'=>'user->id',
			'extra.fk_object'=>'lastrowid-'.MAIN_DB_PREFIX.'product');	// aliastable.field => ('user->id' or 'lastrowid-'.tableparent)
		$this->import_convertvalue_array[$r]=array(
			'ee.fk_fichinter'=>array('rule'=>'fetchidfromref','classfile'=>'/fichinter/class/fichinter.class.php','class'=>'Intervention','method'=>'fetch','element'=>'product'),
			'ee.fk_contrat'=>array('rule'=>'fetchidfromref','classfile'=>'/contract/class/contract.class.php','class'=>'Contract','method'=>'fetch','element'=>'contract'),
			'ee.fk_expedition'=>array('rule'=>'fetchidfromref','classfile'=>'/expedition/class/expedition.class.php','class'=>'Expedition','method'=>'fetch','element'=>'expedition')
		);

		$this->import_examplevalues_array[$r]=array('e.ref'=>"SN1111111",
			'ee.description'=>"Description evènement equipement",
			'ee.fulldayevent'=>"1",
			'ee.total_ht'=>"120",
			'ee.fk_fichinter'=>"FI1206-0002",
			'ee.fk_contrat'=>"CO1211-0001",
			'ee.fk_expedition'=>"",
			'ee.dateo'=>'',
			'ee.datee'=>'',
			'ee.datec'=>'2012-11-29'
		);
	}

	/**
	 *		Function called when module is enabled.
	 *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *		It also creates data directories
	 *
	 *	  @param	  string	$options	Options when enabling module ('', 'noboxes')
	 *	  @return	 int			 	1 if OK, 0 if KO
	 */
	function init($options='')
	{
		global $conf;
		// Permissions
		$this->remove($options);

		$sql = array();

		$result=$this->load_tables();

		return $this->_init($sql, $options);
	}

	/**
	 *		Function called when module is disabled.
	 *	  Remove from database constants, boxes and permissions from Dolibarr database.
	 *		Data directories are not deleted
	 *
	 *	  @param	  string	$options	Options when enabling module ('', 'noboxes')
	 *	  @return	 int			 	1 if OK, 0 if KO
	 */
	function remove($options='')
	{
		$sql = array();
		return $this->_remove($sql, $options);
	}

	/**
	 *		Create tables, keys and data required by module
	 * 		Files llx_table1.sql, llx_table1.key.sql llx_data.sql with create table, create keys
	 * 		and create data commands must be stored in directory /mymodule/sql/
	 *		This function is called by this->init.
	 *
	 * 		@return		int		<=0 if KO, >0 if OK
	 */
	function load_tables()
	{
		return $this->_load_tables('/equipement/sql/');
	}

	function getChangeLog()
	{
		// Libraries
		dol_include_once("/".$this->name."/core/lib/patasmonkey.lib.php");
		return getChangeLog($this->name);
	}

	function getVersion($translated = 1)
	{
		global $langs, $conf;
		$currentversion = $this->version;
		if ($conf->global->PATASMONKEY_SKIP_CHECKVERSION == 1)
			return $currentversion;

		if ($this->disabled) {
			$newversion= $langs->trans("DolibarrMinVersionRequiered")." : ".$this->dolibarrminversion;
			$currentversion="<font color=red><b>".img_error($newversion).$currentversion."</b></font>";
			return $currentversion;
		}

		$context  = stream_context_create(array('http' => array('header' => 'Accept: application/xml')));
		$changelog = @file_get_contents(
						str_replace("www", "dlbdemo", $this->editor_web).'/htdocs/custom/'.$this->name.'/changelog.xml',
						false, $context
		);
		//$htmlversion = @file_get_contents($this->editor_web.$this->editor_version_folder.$this->name.'/');

		if ($htmlversion === false)	// not connected
			return $currentversion;
		else {
			$sxelast = simplexml_load_string(nl2br($changelog));
			if ($sxelast === false)
				return $currentversion;
			else
				$tblversionslast=$sxelast->Version;

			$lastversion = $tblversionslast[count($tblversionslast)-1]->attributes()->Number;

			if ($lastversion != (string) $this->version) {
				if ($lastversion > $this->version) {
					$newversion= $langs->trans("NewVersionAviable")." : ".$lastversion;
					$currentversion="<font title='".$newversion."' color=#FF6600><b>".$currentversion."</b></font>";
				} else
					$currentversion="<font title='Version Pilote' color=red><b>".$currentversion."</b></font>";
			}
		}
		return $currentversion;
	}

	function getLocalVersion()
	{
		global $langs;
		$context  = stream_context_create(array('http' => array('header' => 'Accept: application/xml')));
		$changelog = @file_get_contents(dol_buildpath($this->name, 0).'/changelog.xml', false, $context);
		$sxelast = simplexml_load_string(nl2br($changelog));
		if ($sxelast === false)
			return $langs->trans("ChangelogXMLError");
		else {
			$tblversionslast=$sxelast->Version;
			$currentversion = $tblversionslast[count($tblversionslast)-1]->attributes()->Number;
			$tblDolibarr=$sxelast->Dolibarr;
			$minversionDolibarr=$tblDolibarr->attributes()->minVersion;
			if (DOL_VERSION < $minversionDolibarr) {
				$this->dolibarrminversion=$minversionDolibarr;
				$this->disabled = true;
			}
		}
		return $currentversion;
	}
}
