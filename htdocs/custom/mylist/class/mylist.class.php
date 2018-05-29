<?php
/* Copyright (C) 2013-2017		Charlie BENKE		<charlie@patas-monkey.com>
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
 */

/**
 *	\file	   htdocs/mylist/class/mylist.class.php
 *	\ingroup	base
 *	\brief	  File of class to manage personnalised lists
 */

dol_include_once('/mylist/class/commonobjectpatas.class.php');


/**
 *	Class to manage Mylist
 */
class Mylist extends CommonObjectPatas
{
	public $element='mylist';
	public $table_element='mylist';

	// champs des listes
	var $rowid;
	// var $code;
	var $label;
	var $titlemenu;
	var $mainmenu;
	var $leftmenu;
	var $posmenu;
	var $elementtab;
	var $idmenu;
	var $description;
	var $listsUsed=array();			// Tableau des colonnes paramétrés de la liste
	var $OLDlistsUsed=array();		// Tableau des colonnes paramétrés de la liste ancien mode

	var $fieldinit;					// permet de gérer les paramètres supplémentaires
	var $perms;
	var $langs;
	var $author;
	var $active;
	var $datatable;				// new indique si on affiche la liste en mode classique ou datatable
	var $export;
	var $model_pdf;
	var $querylist;
	var $querydo;
	var $forceall;

	// champs des champs de la liste
	var $idfield;		// clé numérique associé au champ
	var $name;			// libelle du champs dans la base
	var $field;			// nom du champs dans la base
	var $alias;
	var $param;		 	// permet de gérer les liste et les clées
	var $type;
	var $pos;
	var	$align;
	var $enabled;
	var	$visible;
	var $filter;
	var $sumreport;
	var $avgreport;
	var $width;			// la taille de la colonne
	var $widthpdf;			// la taille de la colonne
	var $filterinit;	// une valeur de filtrage par défaut
	var $updatekey;		// pour la mise à jour


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db	 Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
	}

	function GenFilterInitFieldsTables ()
	{
		global $langs, $conf; // $form,
		// datatables mode or not
		$bdatatablesON= (! empty($conf->global->MAIN_USE_JQUERY_DATATABLES));

		$tblInitFields=explode(":", $this->fieldinit);
		foreach ($tblInitFields as $initfields ) {
			$tblInitField=explode("=", $initfields);
			$fieldinit =$tblInitField[0];
			// on prend la valeur par défaut si la valeur n'est pas saisie...
			$valueinit = (GETPOST($fieldinit)?GETPOST($fieldinit):$tblInitField[1]);

			if ($bdatatablesON) {
				$tmp.= '<div STYLE="float:left;"><table width=100%>';
				$tmp.= '<tr class="liste_titre">';

				$tmp.= '<td>'.$langs->trans($fieldinit). ' : '.'</td>';
				$tmp.= '<td align="left">';
				$tmp.='<input type="text" name='.$fieldinit." value='".$valueinit."'>";
				$tmp.= '</td>';

				$tmp.= '</tr></table></div>';
			} else {
				$tmp.= '<td>'.$langs->trans($fieldinit). ': '.'</td>';
				// $fields pas défini??
				$tmp.= '<td align="'.$fields['align'].'">';
				$tmp.='<input type="text" name="'.$fieldinit.'" value="'.$valueinit.'">';
				$tmp.= '</td>';
			}
		}
		return $tmp;
	}

	function GenParamFilterInitFields ()
	{
		// global $langs, $form, $conf;
		$tblInitFields=explode(":", $this->fieldinit);
		foreach ($tblInitFields as $initfields ) {
			$tblInitField=explode("=", $initfields);
			$fieldinit =$tblInitField[0];
			// on prend la valeur par défaut si la valeur n'est pas saisie...
			$valueinit = (GETPOST($fieldinit)?GETPOST($fieldinit):$tblInitField[1]);
			$tmp.='&'.$fieldinit."=".$valueinit;
		}
		return $tmp;
	}

	// gère le format et la taille des champs
	function gen_aoColumns($arrayOfFields, $bckecked)
	{
		$tmp='"aoColumns": [';
		// boucle sur les champs pour en définir le type pour le trie
		foreach ($arrayOfFields as $key => $fields) {
			// selon le type de données
			switch($fields['type']) {
				case "Number":
				case "Price":
				case "Percent":
					$tmp.= '{ "sType": "numeric-comma" ';
					if ($fields['width'] >0 )
						$tmp.= ', "sWidth": "'.$fields['width'].'"' ;
					else	// longueur par défaut pour le champs date
						$tmp.= ', "sWidth": "100px"' ;
					$tmp.= ' },';
					break;

				case "Date":
					$tmp.= '{ "sType": "date-euro"';
					if ($fields['width'] >0 )
						$tmp.= ', "sWidth": "'.$fields['width'].'"' ;
					else	// longueur par défaut pour le champs date
						$tmp.= ', "sWidth": "80px"' ;
					$tmp.= ' },';
					break;

				case "CategoriesFilter":
					break;

				default:
					if ($fields['width'] >0 )
						$tmp.= '{ "sWidth": "'.$fields['width'].'"},' ;
					else
						$tmp.= 'null,';
					break;
			}
		}
		// si on peu cocher les lignes on ajoute une colonne
		if ($bckecked)
			$tmp.= 'null,';

		// on vire la derniere virgule et on ajoute le crochet et le saut de lignes
		$tmp= substr($tmp, 0, -1).'],'."\n";
		return $tmp;
	}

	function gen_aasorting($sortfield, $sortorder, $arrayOfFields, $bckecked)
	{
		// si il y a un trie par défaut
		$posOrderby=strpos(strtoupper($this->querylist), 'ORDER BY');
		$tmp="";
		if ($sortfield ==1 && $posOrderby > 0 ) {
				// un petit espace après l'accolade pour gérer la suppression si rien à trier
			$tmp='"aaSorting":[ ';
			$stringorderby=substr($this->querylist, strpos(strtoupper($this->querylist), 'ORDER BY')+8);
			// on fabrique la ligne de trie par défaut
			if (strpos($stringorderby, ',') > 0)
				$tblorderby = explode(",", $stringorderby);
			else
				$tblorderby[0] = $stringorderby;

			// boucle sur les champs du order by
			foreach ($tblorderby as $orderfield) {
				$tblorderbyfield = explode(" ", trim($orderfield));
				$poscol=0;
				// boucle sur les champs de la liste
				foreach ($arrayOfFields as $key => $fields) {
					if ($tblorderbyfield[0] == $fields["field"])
						$tmp.= '['.$poscol.",".(strtoupper($tblorderbyfield[1])=="ASC"?"'asc'":"'desc'")."],";
					$poscol++;
				}
				// si le champs à trier n'est pas dans la liste, il est ignoré
			}

			// si on peu cocher les ligne on ajoute une colonne
			if ($bckecked)
				$tmp.= 'null,';
			// on vire la derniere virgule et on ajoute le crochet final
			$tmp= substr($tmp, 0, -1)."],\n";
		}
		return $tmp;
	}

	function GenParamFilterFields($arrayOfFields)
	{
		// pour savoir si il s'agit d'une seconde recherche
		$tmp="&filterinit=1";
		// boucle sur les champs filtrables
		foreach ($arrayOfFields as $key => $fields)
			if ($fields['filter']=='1')
				$tmp.= "&".$fields['name']."=".GETPOST($fields['name']);
		return $tmp;
	}

	function GenFilterFieldsTables ($arrayOfFields)
	{
		global $langs, $form; //, $conf;

		$tmp="";
		// boucle sur les champs filtrables
		foreach ($arrayOfFields as $key => $fields) {
			if ($fields['filter']=='1') {
				$tmp.= '<div STYLE="float:left"><table width=100% class="noborder">';
				$tmp.= '<tr class="liste_titre" ><td style="height:33px;">'.$langs->trans($fields['name']). ': '.'</td>';
				$tmp.= '<td >';
	//			$tmp.= '<td align="'.$fields['align'].'">';
				$namefield=str_replace(array('.', '-'), '_', $fields['field']);

				// récupération du filtrage saisie
				if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter.x") || GETPOST("button_removefilter"))
					$filtervalue="";
				else
					$filtervalue=GETPOST($namefield);
				// gestion du filtrage par défaut (si il y en a un et que l'on est pas au premier appel
				if ($fields['filterinit'] != "" && GETPOST("filterinit") != 1 )
					$filtervalue=$fields['filterinit'];

				$tmp.= $form->textwithpicto(
								$this->build_filterField(
												$fields['type'],
												$namefield,
												$filtervalue,
												$fields['param']
								), $this->genDocFilter($fields['type'])
				);
				$tmp.= '</td></tr>';
				$tmp.= '</table></div>';
			} else
				if ($fields['visible']=='1') $tmp.= '<td>&nbsp;</td>';
		}
		return $tmp;
	}

	function GetParamFilter ($arrayOfFields)
	{
		//global $langs, $form, $conf;
		// datatables mode or not

		$tmp="";
		// boucle sur les champs filtrables
		foreach ($arrayOfFields as $key => $fields) {
			if ($fields['filter']=='1') {
				$namefield=str_replace(array('.', '-'), '_', $fields['field']);
				$tmp.="&".$namefield."=".GETPOST($namefield);
			}
		}
		return $tmp;
	}

	/**
	 *	  Build an input field used to filter the query
	 *
	 *	  @param		string	$TypeField		Type of Field to filter
	 *	  @param		string	$NameField		Name of the field to filter
	 *	  @param		string	$ValueField		Initial value of the field to filter
	 *	  @return		string					html string of the input field ex : "<input type=text name=... value=...>"
	 */
	function build_filterField($typeField, $nameField, $valueField, $elementField)
	{
		//global $conf, $langs;
		$szFilterField='';
		$infoFieldList = explode(":", $elementField);

		// build the input field on depend of the type of file
		switch ($typeField) {
			case 'Text':
			case 'DateTime':
				$szFilterField='<input type="text" name="'.$nameField.'" value="'.$valueField.'"';
				$szFilterField.=' style="box-sizing: border-box; width: 100%;">';
				break;

			case 'Date':
			case 'Hours':
			case 'Duration':
			case 'Number':
			case 'Price':
			case 'Percent':
			case 'Sum':
				$szFilterField='<input type="text" size=5 name="'.$nameField.'" value="'.$valueField.'"';
				$szFilterField.=' style="box-sizing: border-box; width: 100%;">';
				break;

			case 'Boolean':
				$szFilterField='<select name="'.$nameField.'" class="flat">';
				$szFilterField.='<option ';
				if ($valueField=='') $szFilterField.=' selected ';
				$szFilterField.=' value="">&nbsp;</option>';

				$szFilterField.='<option ';
				if ($valueField=='yes') $szFilterField.=' selected ';
				$szFilterField.=' value="yes">'.yn(1).'</option>';

				$szFilterField.='<option ';
				if ($valueField=='no') $szFilterField.=' selected ';
				$szFilterField.=' value="no">'.yn(0).'</option>';
				$szFilterField.="</select>";
				break;

			case 'List':
				switch(count($infoFieldList)) {
					case 5 : 		// avec un filtre
						$sql = 'SELECT  rowid, '.$infoFieldList[3].' as label';
						$sql.= ' FROM '.MAIN_DB_PREFIX .$infoFieldList[2];
						$sql.= ' WHERE '.$infoFieldList[4];
						$sql.= ' ORDER BY label';
						break;
					case 4 :		// cas des clés primaires (Class:fichier:table:label)
						// cas de la class action commerciale avec id au lieu de rowid
						if ($infoFieldList[0]=='Actioncomm' || $infoFieldList[0]=='Ctypent' )
							$sql = 'SELECT id as rowid , '.$infoFieldList[3].' as label';
						else
							$sql = 'SELECT rowid, '.$infoFieldList[3].' as label';
						$sql.= ' FROM '.MAIN_DB_PREFIX .$infoFieldList[2];
						$sql.= ' ORDER BY label';
						break;
					case 3 : // cas table simple (table:id:label)
						$sql = 'SELECT '.$infoFieldList[1].' as rowid, '.$infoFieldList[2].' as label';
						$sql.= ' FROM '.MAIN_DB_PREFIX .$infoFieldList[0];
						$sql.= ' ORDER BY label';
						break;
					default :
						$sql = 'SELECT rowid, '.$infoFieldList[1].' as label';
						$sql.= ' FROM '.MAIN_DB_PREFIX .$infoFieldList[0];
						$sql.= ' ORDER BY label';
						break;
				}

				$resql = $this->db->query($sql);
				if ($resql) {
					$szFilterField='<select class="flat" name="'.$nameField.'">';
					$szFilterField.='<option value="">&nbsp;</option>';
					$num = $this->db->num_rows($resql);

					$i = 0;
					if ($num) {
						while ($i < $num) {
							$obj = $this->db->fetch_object($resql);
							if ($obj->label == '-') {
								// Discard entry '-'
								$i++;
								continue;
							}
							$labeltoshow=dol_trunc($obj->label, 18);
							if (!empty($valueField) && $valueField == $obj->rowid)
								$szFilterField.='<option value="'.$obj->rowid.'" selected="selected">'.$labeltoshow.'</option>';
							else
								$szFilterField.='<option value="'.$obj->rowid.'" >'.$labeltoshow.'</option>';
							$i++;
						}
					}
					$szFilterField.="</select>";
					$this->db->free();
				}
				break;

			case 'Statut':
				$tblselectedstatut=explode("#", $infoFieldList[2]);
				$szFilterField='<select class="flat" name="'.$nameField.'">';
				$szFilterField.='<option value="" ></option>';
				if ($infoFieldList[1]!="")
					require_once DOL_DOCUMENT_ROOT.$infoFieldList[1];
				$objectstatic = new $infoFieldList[0]($this->db);

				foreach ($tblselectedstatut as $key ) {
					// pour cette daube d'état 'paye' dans les factures
					if ($key =='P') {
						$objectstatic->statut= 3;
						$objectstatic->paye= 1;
					}
					if ($key =='B') {
						// idem pour les commandes
						$objectstatic->statut= 3;
						$objectstatic->billed= 1;
					} else
						$objectstatic->statut= $key;

					$labeltoshow=$objectstatic->getLibStatut(1);

					if (!$valueField && $valueField == $key)
						$szFilterField.='<option value="'.$key.'" selected="selected">'.$labeltoshow.'</option>';
					else
						$szFilterField.='<option value="'.$key.'" >'.$labeltoshow.'</option>';
				}
				$szFilterField.="</select>";
				break;

			case 'TooltipList' :
				if (count($infoFieldList > 2)) {
					$sql = str_replace("#SEL#", "SELECT", $infoFieldList[1]);
					if (MAIN_DB_PREFIX != 'llx_')
						$sql = " ".preg_replace('/llx_/i', MAIN_DB_PREFIX, $sql);
					$result = $this->db->query($sql);
					if ($result) {
						$num = $this->db->num_rows($resql);
						$tmptooltip='<select class="flat" name="'.$nameField.'">';
						$tmptooltip.="<option name=''></option>";
						$i = 0;
						while ($i < $num) {
							$objp = $this->db->fetch_object($result);
							$selected= "";
							if ($valueField == $objp->rowid) $selected= " selected ";
							$tmptooltip.="<option ".$selected." value='".$objp->rowid."'>".$objp->label."</option>";
							$i++;
						}
						$tmptooltip.='</select>';
					}
					$szFilterField = $tmptooltip;
				} else {
					$szFilterField = '<input type="text" name="'.$nameField.'" value="'.$valueField.'"';
					$szFilterField.= ' style="box-sizing: border-box; width: 100%;">';;
				}
				break;

			case 'ExtrafieldList' :
				// paramétrage de l'ExtraFields
				// 0 le nom de l'élément contenant l'extrafields
				// 1 le nom du champs extrafields
				$elementtype=$infoFieldList[0];

				if ($elementtype == 'thirdparty') $elementtype='societe';
				if ($elementtype == 'contact') $elementtype='socpeople';

				// récup des valeurs possibles
				$sql = "SELECT param";
				$sql.= " FROM ".MAIN_DB_PREFIX."extrafields";
				$sql.= " WHERE name = '".$infoFieldList[1]."'";
				$sql.= " AND elementtype = '".$elementtype."'";

				$resql=$this->db->query($sql);
				if ($resql) {
					$tab = $this->db->fetch_array($resql);
					$options = unserialize($tab['param']);

					$out.='<select class="flat" name="'.$nameField.'" id="'.$nameField.'" >';
					$out.='<option value="0">&nbsp;</option>';

					foreach ($options['options'] as $key => $val) {
						if ($key == '') continue;
						$out.='<option value="'.$key.'"';
						$out.= ($valueField==$key?' selected':'');
						$out.= (!empty($parent)?' parent="'.$parent.'"':'');
						$out.='>'.$val.'</option>';
					}
					$out.='</select>';
				}
				$szFilterField = $out;
				break;

			case 'CategoriesFilter' :
				require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
				$form=new Form($this->db);
				// paramétrage de la catégorie
				// 0 le type catégorie
				// 1 l'alias de la table caf
				//contact:fk_socpeople:4
				//supplier/fournisseur:fk_soc:1
				//member:fk_member:3
				//product:fk_product:0
				//project:fk_project:6
				//customer/societe:fk_soc:2
				//user:fk_user:4

				//var_dump($infoFieldList);;
				// on convertie le nom de la catégorie en numéro de catégorie
				switch($infoFieldList[0]) {
					case 'project' :
						$elementtype=6;
						break;
					case 'customer' :
						$elementtype=2;
						break;
					case 'member' :
						$elementtype=3;
						break;
					case 'supplier' :
						$elementtype=1;
						break;
					case 'product' :
						$elementtype=0;
						break;
					case 'contact' :
					case 'user' :
						$elementtype=4;
						break;
				}

				$cate_arbo = $form->select_all_categories($elementtype, null, 'parent', null, null, 1);
				$out = $form->multiselectarray(
								$nameField, $cate_arbo, GETPOST($nameField, 'array'),
								null, null, null, null, '300px'
				);

				$szFilterField = $out;
				break;

			// some additionnal filter mode
			default :
				break;
		}
		return $szFilterField;
	}

	function GetSqlFilterQuery($arrayOfFields)
	{
		$tmp="";
		if (is_array($arrayOfFields)) {
			foreach ($arrayOfFields as $key => $fields)
				if ($fields['filter']=='1') {
					$namefield=str_replace(array('.', '-'), '_', $fields['field']);
					$tmp.= $this->build_filterQuery($fields, GETPOST($namefield));
				}
		}
		return $tmp;
	}

	/**
	 *	  Build the conditionnal string from filter the query
	 *
	 *	  @param		string	$TypeField		Type of Field to filter
	 *	  @param		string	$NameField		Name of the field to filter
	 *	  @param		string	$ValueField		Initial value of the field to filter
	 *	  @return		string					sql string of then field ex : "field='xxx'>"
	 */
	function build_filterQuery($fieldinfos, $valueField)
	{

		$typeField=$fieldinfos['type'];
		$nameField=$fieldinfos['field'];
		$defaultFilterValue=$fieldinfos['filterinit'];

		//print $typeField."=".$nameField."=".$ValueField.'/'.$DefaultFilterValue.'<br>';

		if ($valueField != "" || $defaultFilterValue != "") {
			// récupération du filtrage saisie
			$filtervalue=$valueField;
			// gestion du filtrage par défaut (si il y en a un et que l'on est pas au premier appel
			if ($defaultFilterValue !="" && GETPOST("filterinit") != 1 )
				$filtervalue=$defaultFilterValue;
			// build the input field on depend of the type of file
			switch ($typeField) {
				case 'Text':
					if (! (strpos($filtervalue, '%') == false))
						$szFilterQuery.=" and ".$nameField." LIKE '".$filtervalue."'";
					else
						$szFilterQuery.=" and ".$nameField." LIKE '%".$filtervalue."%'";
					break;
				case 'Date':
				case 'DateTime':
					if (strpos($filtervalue, "+") > 0) {
						// mode plage
						$valueArray = explode("+", $filtervalue);
						$szFilterQuery =" and (".$this->conditionDate($nameField, $valueArray[0], ">=");
						$szFilterQuery.=" AND ".$this->conditionDate($nameField, $valueArray[1], "<=").")";
					} else {
						if (is_numeric(substr($filtervalue, 0, 1)))
							$szFilterQuery=" and ".$this->conditionDate($nameField, $filtervalue, "=");
						else
							$szFilterQuery=" and ".$this->conditionDate(
											$nameField, substr($filtervalue, 1),
											substr($filtervalue, 0, 1)
							);
					}
					break;
				case 'Number':
				case 'Price':
				case 'Sum':
					// si le signe -
					if (strpos($filtervalue, "+") > 0) {
						// mode plage
						$valueArray = explode("+", $filtervalue);
						$szFilterQuery =" AND (".$nameField.">=".$valueArray[0];
						$szFilterQuery.=" AND ".$nameField."<=".$valueArray[1].")";
					} else {
						if (is_numeric(substr($filtervalue, 0, 1)))
							$szFilterQuery=" and ".$nameField."=".$filtervalue;
						else
							$szFilterQuery=" and ".$nameField.substr($filtervalue, 0, 1).substr($filtervalue, 1);
					}
					break;
				case 'Percent':
					// si le signe +
					if (strpos($filtervalue, "+") > 0) {
						// mode plage
						$valueArray = explode("+", $filtervalue);
						$valueArray[0] = $valueArray[0]/100;
						$valueArray[1] = $valueArray[1]/100;
						$szFilterQuery =" AND (".$nameField.">=".$valueArray[0];
						$szFilterQuery.=" AND ".$nameField."<=".$valueArray[1].")";
					} else {
						if (is_numeric(substr($filtervalue, 0, 1)))
							$szFilterQuery=" and ".$nameField."=".($filtervalue/100);
						else
							$szFilterQuery=" and ".$nameField.substr($filtervalue, 0, 1).substr($filtervalue, 1);
					}
					break;
				case 'Duration':
				case 'Hours':

					// si le signe 2:10+3:50
					if (strpos($filtervalue, "+") > 0) {
						// mode plage
						$valueArray = explode("+", $filtervalue);
						$valueArray[0] = explode(":", $valueArray[0]);
						$valueArray[1] = explode(":", $valueArray[1]);

						$valueArray[0] = convertTime2Seconds($valueArray[0][0], $valueArray[0][1]);
						$valueArray[1] = convertTime2Seconds($valueArray[1][0], $valueArray[1][1]);
						$szFilterQuery =" AND (".$nameField.">=".$valueArray[0];
						$szFilterQuery.=" AND ".$nameField."<=".$valueArray[1].")";
					} else {
						// si le premier caractère est > ou <
						if (is_numeric(substr($filtervalue, 0, 1))) {
							$filtervalue = explode(":", $filtervalue);
							$filtervalue = convertTime2Seconds($filtervalue[0], $filtervalue[1]);
							$szFilterQuery=" AND ".$nameField." = ".$filtervalue;
						} else {
							// valeur de type "> 3:20"
							$maxmin=substr($filtervalue, 0, 1);
							$filtervalue = explode(":", $filtervalue);
							$filtervalue = convertTime2Seconds(substr($filtervalue[0], 1),$filtervalue[1]);

							$szFilterQuery=" AND ".$nameField." ".$maxmin." ".$filtervalue;
						}
					}
					break;

				case 'Boolean':
					$szFilterQuery= " AND ".$nameField;
					$szFilterQuery.= "=".(is_numeric($filtervalue) ? $filtervalue : ($filtervalue =='yes' ? 1: 0) );
					break;
				case 'Statut':
					// pour gérer la merde des statut de facturation
					if ($filtervalue !='P')
						$szFilterQuery=" and ".$nameField."=".$filtervalue;
					else
						$szFilterQuery=" and ".$nameField."=2 and paye=1";
					break;
				case 'List':
					if (is_numeric($filtervalue))
						$szFilterQuery=" and ".$nameField."=".$filtervalue;
					else
						$szFilterQuery=" and ".$nameField."='".$filtervalue."'";
					break;
				case 'ExtrafieldList' :
					if ($filtervalue != 0)
						$szFilterQuery=" AND ".$nameField." =" .$filtervalue;
					break;

				case 'TooltipList' :
					$tblquery=explode(":", $querylist);
					if (count($tblquery) > 2) {
						$sql= str_replace("#SEL#", "SELECT", $tblquery[2]);
						$sql= str_replace("#KEYID#", $filtervalue, $sql);
						if (MAIN_DB_PREFIX != 'llx_')
							$sql= " ".preg_replace('/llx_/i', MAIN_DB_PREFIX, $sql);

						$szFilterQuery=" AND ".$nameField." in (".$sql.")";
					} else
						$szFilterQuery=" AND ".$nameField." = 0";
					break;

				case 'CategoriesFilter' :
					// récup du nom du champs de filtrage
					if (! empty($fieldinfos['alias']))
						$namefield=$fieldinfos['alias'];
					else
						$namefield=str_replace(array('.', '-'), '_', $fieldinfos['field']);

					//récup de la valeur de filtrage
					$valuefield=GETPOST($namefield, 'array');

					// on crée la condition pour les filtrages multiples
					if (count($valuefield) > 1) {
						//product:fk_product:0
						//supplier/fournisseur:fk_soc:1
						//customer/societe:fk_soc:2
						//member:fk_member:3
						//user:fk_user:4
						//contact:fk_socpeople:4
						//project:fk_project:6

						$paramsetting = explode(":", $fieldinfos['param']);
						switch($paramsetting[0]) {
							case 'supplier':
								$szFilterQuery = " AND ".$paramsetting[1].".fk_soc";
								$szFilterQuery.= " IN (SELECT fk_soc from ".MAIN_DB_PREFIX."categorie_fournisseur";
								$szFilterQuery.= " WHERE fk_categorie IN (".join(",", $valuefield).")";
								$szFilterQuery.= " GROUP BY fk_soc HAVING COUNT(*) = ".count($valuefield)." )";
								break;

							case 'customer':
								$szFilterQuery = " AND ".$paramsetting[1].".fk_soc";
								$szFilterQuery.= " IN (SELECT fk_soc from ".MAIN_DB_PREFIX."categorie_societe";
								$szFilterQuery.= " WHERE fk_categorie IN (".join(",", $valuefield).")";
								$szFilterQuery.= " GROUP BY fk_soc HAVING COUNT(*) = ".count($valuefield)." )";
								break;

							case 'contact':
								$szFilterQuery = " AND ".$paramsetting[1].".fk_socpeople";
								$szFilterQuery.= " IN (SELECT fk_socpeople from ".MAIN_DB_PREFIX."categorie_contact";
								$szFilterQuery.= " WHERE fk_categorie IN (".join(",", $valuefield).")";
								$szFilterQuery.= " GROUP BY fk_socpeople HAVING COUNT(*) = ".count($valuefield)." )";
								break;

							default:
								$elementname=$paramsetting[0];
								$szFilterQuery = " AND ".$paramsetting[1].".fk_".$elementname;
								$szFilterQuery.= " IN (SELECT fk_".$elementname." from ".MAIN_DB_PREFIX."categorie_".$elementname;
								$szFilterQuery.= " WHERE fk_categorie IN (".join(",", $valuefield).")";
								$szFilterQuery.= " GROUP BY fk_".$elementname." HAVING COUNT(*) = ".count($valuefield)." )";
								break;
						}
					} elseif (count($valuefield) == 1 ) // si un seul filtre de saisie, c'est filtrage classique
						$szFilterQuery = " AND ".$fieldinfos['field']." =".$valuefield[0];
					break;

				default :
					$szFilterQuery="";
					break;
			}
		}
		return $szFilterQuery;
	}

	function get_infolist($rowid, $elementField)
	{
		if (is_array($elementField))
			$infoFieldList = $elementField;
		else
			$infoFieldList = explode(":", $elementField);

		if (count($infoFieldList)==3)
			$keyList=$infoFieldList[2];
		else
			$keyList='rowid';

		$sql = 'SELECT '.$infoFieldList[1];
		$sql.= ' FROM '.MAIN_DB_PREFIX .$infoFieldList[0];
		$sql.= ' where '.$keyList.' = '. $rowid;
//print $sql;
		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			$labeltoshow=dol_trunc($obj->$infoFieldList[1], 18);
			$this->db->free();
		}
		return $labeltoshow;
	}

	/**
	 *	conditionDate
	 *
	 *  @param 	string	$Field		Field operand 1
	 *  @param 	string	$Value		Value operand 2
	 *  @param 	string	$Sens		Comparison operator
	 *  @return string
	 */
	function conditionDate($field, $value, $sens)
	{
		// FIXME date_format is forbidden, not performant and no portable. Use instead BETWEEN
		if (strlen($value)==4)
			$condition=" date_format(".$field.", '%Y') ".$sens." ".$value;
		elseif (strlen($value)==6)
			$condition=" date_format(".$field.", '%Y%m') ".$sens." '".$value."'";
		else
			$condition=" date_format(".$field.", '%Y%m%d') ".$sens." ".$value;
		return $condition;
	}

	/**
	 *	  Build the fields list for the SQL query
	 *
	 *	  @param		array	$arrayOfFields	definition array fields of the list
	 *	  @return		string					sql string of fields
	 */
	function GetSqlFields($arrayOfFields)
	{
		if (is_array($arrayOfFields)) {
			foreach ($arrayOfFields as $key => $fields) {
				// on n'affiche pas les filtres de catégorie
				if ($fields['type'] != 'CategoriesFilter') {
					$tmp.=$fields['field']." AS ";
					if (! empty($fields['alias']))
						$tmp.=$fields['alias'];
					else {
						// pour gérer les . des définitions de champs
						$tmp.=str_replace(array('.', '-'), '_', $fields['field']);
					}
					$tmp.=", ";
				}
			}
			// on enlève la dernière virgule et l'espace en fin de ligne
			return substr($tmp, 0, -2);
		}

	}

	/**
	 *	  Build the group by fields list for the SQL query
	 *
	 *	  @param		array	$arrayOfFields	definition array fields of the list
	 *	  @return		string					sql string of group by fields
	 */
	function GetGroupBy($arrayOfFields)
	{
		$btopGroupBy = false;
		$tmp=" GROUP BY ";
		// on boucle sur les champs
		if (is_array($arrayOfFields)) {
			foreach ($arrayOfFields as $key => $fields) {
				if (substr(strtoupper($key), 0, 4) == "SUM(")
					$btopGroupBy = true;
				elseif (substr(strtoupper($key), 0, 6) == "COUNT(")
					$btopGroupBy = true;
				else
					$tmp.=$fields['field'].", ";
			}
		}
		// on enlève la dernière virgule et l'espace en fin de ligne
		if ($btopGroupBy)
			return substr($tmp, 0, -2);
		else
			return "";
	}

	function genDefaultTD($keyName, $arrayfields, $objvalue)
	{
		global $langs, $conf;

		$tmp= "<td align=".$arrayfields['align'].">";
		// pour gérer l'aliassing des champs
		if (!empty($arrayfields['alias']))
			$codFields=$arrayfields['alias'];
		else
			$codFields=str_replace(array('.', '-'), "_", $arrayfields['field']);

		// selon le type de données
		switch($arrayfields['type']) {
			case "Price":
				$tmp.= price($objvalue->$codFields);
				if ($conf->global->MYLIST_DISPLAY_CURRENCY_PRICE)
					$tmp.= " ".$langs->trans("Currency" . $conf->currency);
				break;

			case "Number":
				$tmp.= price($objvalue->$codFields);
				break;

			case "Percent":
				$tmp.= price($objvalue->$codFields * 100)." %";
				break;

			case "Date":
				$tmp.= dol_print_date($this->db->jdate($objvalue->$codFields), 'day');
				break;

			case "DateTime":
				$tmp.= dol_print_date($this->db->jdate($objvalue->$codFields), 'dayhour');
				break;

			case "Duration":
				$tmp.= convertSecondToTime($objvalue->$codFields);
				break;

			case "Boolean":
				$tmp.= yn($objvalue->$codFields);
				break;

			default:
				$tmp.= $objvalue->$codFields;
				break;
		}
		$tmp.= '</td>';
		return $tmp;
	}

	function genHideFields($arrayfields)
	{
		//boucle sur les champs à afficher
		$tmp="<script>"."\n"."jQuery(document).ready(function() {"."\n";

		$i=0;
		foreach ($arrayfields as $key => $fields) {
			// si le champs n'est pas visible on le cache
			if ($fields['visible'] == '0')
				$tmp.= 'jQuery("#listtable").dataTable().fnSetColumnVis('.$i.', false );'."\n";
			$i++;
		}
		$tmp.= "});"."\n"."</script>"."\n";
		return $tmp;
	}

	/**
	 *	  Build an input field used to filter the query
	 *
	 *	  @param		string	$TypeField		Type of Field to filter
	 *	  @return		string					html string of the input field ex : "<input type=text name=... value=...>"
	 *	  TODO replace by translation
	 */
	function genDocFilter($typeField)
	{
		$szMsg='';
		$infoFieldList = explode(":", $typeField);
		// build the input field on depend of the type of file
		switch ($infoFieldList[0]) {
			case 'Text':
				$szMsg="% permet de remplacer un ou plusieurs caract&egrave;res dans la chaine";
				break;
			case 'Date':
			case 'DateTime':
				$szMsg ="'AAAA' 'AAAAMM' 'AAAAMMJJ' : filtre sur une ann&eacute;e/mois/jour <br>";
				$szMsg.="'AAAA+AAAA' 'AAAAMM+AAAAMM' 'AAAAMMJJ+AAAAMMJJ': filtre sur une plage d'ann&eacute;e/mois/jour <br>";
				$szMsg.="'&gt;AAAA' '&gt;AAAAMM' '&gt;AAAAMMJJ' filtre sur les ann&eacute;e/mois/jour suivants <br>";
				$szMsg.="'&lsaquo;AAAA' '&lsaquo;AAAAMM' '&lsaquo;AAAAMMJJ'";
				$szMsg.="'filtre sur les ann&eacute;e/mois/jour pr&eacute;c&eacute;dent <br>";
				break;

			case 'Duration':
			case 'Hours':
				$szMsg ="'HH:MM' filtre sur une valeur <br>";
				$szMsg.="'HH:MM+HH:MM' filtre sur une plage de valeur<br>";
				$szMsg.="'&lsaquo;HH:MM' filtre sur les valeurs inf&eacute;rieurs<br>";
				$szMsg.="'&gt;HH:MM' filtre sur les valeurs sup&eacute;rieurs<br>";
				break;

			case 'Number':
				$szMsg ="'NNNNN' filtre sur une valeur <br>";
				$szMsg.="'NNNNN+NNNNN' filtre sur une plage de valeur<br>";
				$szMsg.="'&lsaquo;NNNNN' filtre sur les valeurs inf&eacute;rieurs<br>";
				$szMsg.="'&gt;NNNNN' filtre sur les valeurs sup&eacute;rieurs<br>";
				break;
		}
		return $szMsg;
	}

	/**
	 * 	Load Listables into memory from database
	 *
	 * 	@param		int		$code		code of listable
	 * 	@return		int				<0 if KO, >0 if OK
	 */
	function fetch($rowid)
	{
		$sql = "SELECT rowid, label, description, fieldinit, fieldused, mainmenu, leftmenu, elementtab, perms,";
		$sql.= " datatable, querylist, querydo, titlemenu, langs, author, export, model_pdf, active, forceall";
		$sql.= " FROM ".MAIN_DB_PREFIX."mylist";
		$sql.= " WHERE rowid = ".$rowid;

		dol_syslog(get_class($this)."::fetch sql=".$sql);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql) > 0) {
				$res = $this->db->fetch_array($resql);

				$this->rowid		= $res['rowid'];
				$this->label		= $res['label'];
				$this->description	= $res['description'];

				$this->mainmenu		= $res['mainmenu'];
				$this->leftmenu		= $res['leftmenu'];
				$this->titlemenu	= $res['titlemenu'];
				$this->elementtab	= $res['elementtab'];
				$this->perms		= $res['perms'];
				$this->langs		= $res['langs'];
				$this->author		= $res['author'];
				$this->active		= $res['active'];
				$this->datatable	= $res['datatable'];
				$this->querylist	= $res['querylist'];
				$this->querydo		= $res['querydo'];
				$this->fieldinit	= $res['fieldinit'];
				$this->export		= $res['export'];
				$this->forceall		= $res['forceall'];
				$this->model_pdf	= $res['model_pdf'];
				$this->OLDlistsUsed	= json_decode($res['fieldused'], true);
				$this->db->free($resql);

				// pour gérer les anciennes versions
				$this->fillmylistdet();
				return 1;
			} else
				return 0;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}

	/*  Get the right position menu value for new */
	function getposmenu($titlemenu, $mainmenu, $leftmenu)
	{
		// gestion de la position du menu
		$sql="SELECT max(position) as posmenu FROM ".MAIN_DB_PREFIX."menu";
		$sql.=" WHERE fk_mainmenu ='".trim($mainmenu)."'";
		$sql.=" AND fk_leftmenu ='".trim($leftmenu)."'";
		$sql.=" AND titre <> '".trim($titlemenu)."'";
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql) > 0) {
				$res = $this->db->fetch_array($resql);
				// on rajoute 1 à la derniere liste présente
				if ($res['posmenu'] >= 100)
					return $res['posmenu']+1;
			}
		}
		// on renvoie la valeur par défaut dans tous les autres cas
		return 100;
	}

	/**
	 * 	Add mylist into database
	 *
	 * 	@param	User	$user		Object user
	 * 	@return	int 				-1 : erreur SQL

	 */
	function create($user='')
	{
		global $langs, $user; // $conf,
		$langs->load('mylist@mylist');

		//$this->code = trim($this->code);
		$this->label=(!is_array($this->label)?trim($this->label):'');
		$this->description=(!is_array($this->description)?trim($this->description):'');

		$this->perms=(!is_array($this->perms)?trim($this->perms):'');
		$this->langs=(!is_array($this->langs)?trim($this->langs):'');
		$this->titlemenu = trim($this->titlemenu);
		$this->mainmenu = trim($this->mainmenu);
		$this->leftmenu = trim($this->leftmenu);
		$this->datatable = trim($this->datatable);
		$this->elementtab = (!is_array($this->elementtab)?trim($this->elementtab):'');
		$this->author=(!is_array($this->author)?trim($this->author):'');
		$this->fieldinit=(!is_array($this->fieldinit)?trim($this->fieldinit):'');
		$this->querydo=(!is_array($this->querydo)?trim($this->querydo):'');

		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."mylist (";
		$sql.= " label,";
		$sql.= " description,";
		$sql.= " titlemenu,";
		$sql.= " perms,";
		$sql.= " langs,";
		$sql.= " mainmenu,";
		$sql.= " leftmenu,";
		$sql.= " elementtab,";
		$sql.= " author,";
		$sql.= " active,";
		$sql.= " datatable,";
		$sql.= " querylist,";
		$sql.= " querydo,";
		$sql.= " fieldinit";
		$sql.= ") VALUES (";
		$sql.= " '".$this->db->escape($this->label)."'";
		$sql.= ", '".$this->db->escape($this->description)."'";
		$sql.= ", '".$this->db->escape($this->titlemenu)."'";
		$sql.= ", '".$this->db->escape($this->perms)."'";
		$sql.= ", '".$this->db->escape($this->langs)."'";
		$sql.= ", '".$this->db->escape($this->mainmenu)."'";
		$sql.= ", '".$this->db->escape($this->leftmenu)."'";
		$sql.= ", '".$this->db->escape($this->elementtab)."'";
		$sql.= ", '".$this->db->escape($this->author)."'";
		$sql.= ", 0";  // by default the new list is not active
		if ($this->datatable == 1)
			$sql.= ", true";
		else
			$sql.= ", false";
		$sql.= ", '".$this->db->escape($this->querylist)."'";
		$sql.= ", '".$this->db->escape($this->querydo)."'";
		$sql.= ", '".$this->db->escape($this->fieldinit)."'";
		$sql.= ")";
//print $sql;
		dol_syslog(get_class($this).'::create sql='.$sql);
		if ($this->db->query($sql)) {
			$rowid=$this->db->last_insert_id(MAIN_DB_PREFIX."mylist");
			$this->db->commit();
			$this->rowid = $rowid;
			return $rowid;

		} else {
			$this->error=$this->db->error();
			dol_syslog(get_class($this)."::create error ".$this->error." sql=".$sql, LOG_ERR);
			$this->db->rollback();
			return 0;
		}
	}

	/**
	 * 	Delete fields
	 *
	 *	@param	User	$user		Object user
	 * 	@return	int		 			1 : OK
	 *		  					-1 : SQL error
	 *		  					-2 : invalid fields
	 */
	function deleteField($user='', $keychange=0)
	{
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."mylistdet";
		$sql.= " where rowid=".$keychange;
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->fillmylistdet();
			return 1;
		}
		return -1;
	}

	/**
	 * 	Update fields
	 *
	 *	@param	User	$user		Object user
	 * 	@return	int		 			1 : OK
	 *		  					-1 : SQL error
	 *		  					-2 : invalid fields
	 */
	function updateField($user='', $keychange=0)
	{
		$sql = "UPDATE ".MAIN_DB_PREFIX."mylistdet";
		$sql.= " SET name= '".$this->name."'";
		$sql.= " , fieldname= '".str_replace("'", "\'", $this->field)."'";
		$sql.= " , alias = '".$this->alias."'";
		$sql.= " , type = '".$this->type."'";
		$sql.= " , param = '".$this->param."'";
		$sql.= " , filterinit = '".$this->filterinit."'";
		$sql.= " , align= '".$this->align."'";
		$sql.= " , enabled = ".$this->db->escape($this->enabled);
		$sql.= " , visible = ".$this->db->escape($this->visible);
		$sql.= " , filter = ".$this->db->escape($this->filter);
		$sql.= " , width = ".$this->db->escape($this->width);
		$sql.= " , widthpdf = ".($this->widthpdf?$this->db->escape($this->widthpdf):'null');
		$sql.= " , sumreport = ".($this->sumreport?$this->db->escape($this->sumreport):'null');
		$sql.= " , avgreport = ".($this->avgreport?$this->db->escape($this->avgreport):'null');
		$sql.= " where rowid=".$keychange;
//print $sql;
		$resql = $this->db->query($sql);
		return $resql;
	}

	/**
	 * 	Add fields
	 *
	 *	@param	User	$user		Object user
	 * 	@return	int		 			1 : OK
	 *		  					-1 : SQL error
	 *		  					-2 : invalid fields
	 */
	function addField($user='', $idfield=0)
	{
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."mylistdet";
		$sql.= "(fk_mylist, fieldname, name, alias, type, pos, param,";
		$sql.= " align, enabled, visible, filter, width, widthpdf,";
		$sql.= " filterinit, sumreport, avgreport) values (";
		$sql.= $idfield.",";
		$sql.= "'".str_replace("'", "\'", $this->field)."',";
		$sql.= "'".$this->name."',";
		$sql.= "'".$this->alias."',";
		$sql.= "'".$this->type."',";
		$sql.= " ".$this->pos.",";
		$sql.= "'".$this->param."',";
		$sql.= "'".$this->align."',";
		$sql.= " ".$this->enabled.",";
		$sql.= " ".$this->visible.",";
		$sql.= " ".$this->filter.",";
		$sql.= " ".($this->width ? $this->width : 0).",";
		$sql.= " ".($this->widthpdf ? $this->widthpdf : 0).",";
		$sql.= "'".$this->filterinit."',";
		$sql.= " ".($this->sumreport?$this->sumreport:0).",";
		$sql.= " ".($this->avgreport?$this->avgreport:0).")";
		$resql = $this->db->query($sql);

		if ($resql) {
			$rowid=$this->db->last_insert_id(MAIN_DB_PREFIX."mylistdet");
			return $rowid;
		}

		$this->error = $sql;
		return -1;
	}

	function getlastpos($mylistid)
	{
		// gestion de la position du menu
		$sql="SELECT max(pos) as lastpos FROM ".MAIN_DB_PREFIX."mylistdet";
		$sql.=" WHERE fk_mylist =".$mylistid;
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql) > 0) {
				$res = $this->db->fetch_array($resql);
				// on rajoute 1 à la derniere liste présente
					return $res['posmenu']+1;
			}
		}
		// on renvoie la valeur par défaut dans tous les autres cas
		$this->error = $sql;
		return 1;
	}

	/**
	 * 	Update mylist, and create menu if activate
	 *
	 *	@param	User	$user		Object user
	 * 	@return	int		 			1 : OK
	 *		  					-1 : SQL error
	 *		  					-2 : invalid category
	 */
	function update($user='')
	{
		global $conf; //, $langs;
		$this->db->begin();

		// on commence par récupérer l'id du menu à supprimer
		$sql="SELECT m.rowid FROM ".MAIN_DB_PREFIX."menu as m, ".MAIN_DB_PREFIX."mylist as l";
		$sql .= " WHERE l.rowid = '".$this->rowid."'";
		$sql .= " and l.titlemenu=m.titre";
		$sql .= " and m.module='mylist'";
		$sql .= " and l.mainmenu=m.fk_mainmenu";
		$sql .= " and l.leftmenu=m.fk_leftmenu";
		$sql .= " and m.entity = ".$conf->entity;

		dol_syslog(get_class($this)."::update sql=".$sql);
		if ($this->db->query($sql)) {
			if ($this->db->num_rows($resql) > 0) {
				$res = $this->db->fetch_array($resql);
				$sql="DELETE FROM ".MAIN_DB_PREFIX."menu WHERE rowid=".$res['rowid'];
				$this->db->query($sql);
			}
		}
		$this->posmenu=$this->getposmenu($this->titlemenu, $this->mainmenu, $this->leftmenu);

		// on supprime l'onglet si il est present ou pas
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."const";
		$sql.= " WHERE name =".$this->db->encrypt('MAIN_MODULE_MYLIST_TABS_'.$this->rowid, 1);
		$sql.= " AND entity = ".$conf->entity;
		$this->db->query($sql);

		// si il y a un onglet on fait de meme
		$sql = "UPDATE ".MAIN_DB_PREFIX."mylist";
		$sql .= " SET label = '".$this->db->escape($this->label)."'";
		$sql .= ", description ='".$this->db->escape($this->description)."'";
		$sql .= ", perms ='".$this->db->escape($this->perms)."'";
		$sql .= ", langs ='".$this->db->escape($this->langs)."'";
		$sql .= ", titlemenu ='".$this->db->escape($this->titlemenu)."'";
		$sql .= ", mainmenu ='".$this->db->escape($this->mainmenu)."'";
		$sql .= ", leftmenu ='".$this->db->escape($this->leftmenu)."'";
		$sql .= ", posmenu =".$this->posmenu;
		$sql .= ", elementtab ='".$this->db->escape($this->elementtab)."'";
		$sql .= ", querylist ='".$this->db->escape($this->querylist)."'";
		$sql .= ", querydo ='".$this->db->escape($this->querydo)."'";
		$sql .= ", fieldinit ='".$this->db->escape($this->fieldinit)."'";
		$sql .= ", author ='".$this->db->escape($this->author)."'";
		$sql .= ", datatable = ".($this->datatable ==1 ? "true" : "false");
		$sql .= ", active =".$this->db->escape($this->active);
		$sql .= ", forceall=".$this->db->escape($this->forceall);
		$sql .= ", export =".($this->export?$this->db->escape($this->export):0);
		$sql .= ", model_pdf ='".$this->db->escape($this->model_pdf)."'";
		$sql .= " WHERE rowid =".$this->rowid;

		dol_syslog(get_class($this)."::update sql=".$sql);

		if ($this->db->query($sql)) {
			// si la liste est active
			if ($this->active) {
				$dt = "";
				// si on affiche le mode datatable ou pas
				if ($this->datatable == 1) $dt = "dt";

				// on ajoute le menu
				require_once DOL_DOCUMENT_ROOT.'/core/class/menubase.class.php';
				$menu = new Menubase($this->db);
				if ($conf->global->MAIN_MENU_STANDARD == 'auguria_menu.php')
					$menu->menu_handler='auguria';
				else
					$menu->menu_handler='all';
				$menu->module='mylist';
				$menu->type='left';
				$menu->fk_menu=$this->getidmenu($menu->menu_handler);
				$menu->fk_mainmenu=$this->mainmenu;
				$menu->fk_leftmenu=$this->leftmenu;
				$menu->titre=$this->titlemenu;
				$menu->url='/mylist/mylist'.$dt.'.php?rowid='.$this->rowid;
				$menu->langs=$this->langs;
				$menu->position=$this->posmenu;
				$menu->perms=$this->perms;
				$menu->target="";
				$menu->user=2;
				$menu->enabled=1;
				$result=$menu->create($user);

				// on crée l'onglet
				if ($this->elementtab) {
					switch($this->elementtab) {
						case 'Societe' :
							$tabinfo='thirdparty';
							break;

						case 'CategProduct' :
							$tabinfo='categories_0';
							break;

						case 'CategSociete' :
							$tabinfo='categories_2';
							break;

						default:
							// dans tous les autres cas on est propre
							$tabinfo=$this->elementtab;
							break;
					}
					$tabinfo.=':+mylist_'.$this->rowid.':'.$this->titlemenu;
					$tabinfo.=':"":@mylist:/mylist/mylist'.$dt.'.php?rowid='.$this->rowid.'&id=__ID__';

					$sql = "INSERT INTO ".MAIN_DB_PREFIX."const ";
					$sql.= " ( name, type, value, note, visible, entity)";
					$sql.= " VALUES (";
					$sql.= $this->db->encrypt('MAIN_MODULE_MYLIST_TABS_'.$this->rowid, 1);
					$sql.= ", 'chaine'";
					$sql.= ", ".$this->db->encrypt($tabinfo, 1);
					$sql.= ", null";
					$sql.= ", '0'";
					$sql.= ", ".$conf->entity;
					$sql.= ")";

					dol_syslog(get_class($this)."::update insert_const_tabs sql=".$sql);
					$resql=$this->db->query($sql);
				}
			}
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 * 	Return the last id of menu
	 *
	 *	@return	id of  menu , -1 if normal
	 */
	function getidmenu($menuhandler='all')
	{
		$sql="SELECT min(m.rowid) AS minmenu FROM ".MAIN_DB_PREFIX."menu AS m";
		$sql .= " WHERE m.mainmenu='".$this->mainmenu."'";
		$sql .= " AND m.fk_menu != -1";
		$sql .= " AND m.menu_handler = '".$menuhandler."'";

//print $sql;
		dol_syslog(get_class($this)."::getidmenu sql=".$sql);
		if ($this->db->query($sql)) {
			if ($this->db->num_rows($resql) > 0) {
				$res = $this->db->fetch_array($resql);
				if ($res['minmenu'] != 0)
					return($res['minmenu']);
			}
		}
		return -1;
	}


	/**
	 * 	Delete a list from database
	 *
	 * 	@param	User	$user		Object user that ask to delete
	 *	@return	void
	 */
	function delete($user)
	{
		global $conf; //, $langs;
		$error=0;

		dol_syslog(get_class($this)."::delete");

		// on vire le menu si il existe, normalement pas nécessaire (liste désactivé) mais on sait jamais
		// on commence par récupérer l'id du menu à supprimer
		// on commence par récupérer l'id du menu à supprimer
		$sql="SELECT m.rowid FROM ".MAIN_DB_PREFIX."menu as m, ".MAIN_DB_PREFIX."mylist as l";
		$sql .= " WHERE l.rowid = '".$this->rowid."'";
		$sql .= " and l.titlemenu=m.titre";
		$sql .= " and m.module='mylist'";
		$sql .= " and l.mainmenu=m.fk_mainmenu";
		$sql .= " and l.leftmenu=m.fk_leftmenu";
		$sql .= " and m.entity = ".$conf->entity;

		dol_syslog(get_class($this)."::delete sql=".$sql);
		if ($this->db->query($sql)) {
			if ($this->db->num_rows($resql) > 0) {
				$res = $this->db->fetch_array($resql);
				$sql="delete from ".MAIN_DB_PREFIX."menu where rowid=".$res['rowid'];
				$this->db->query($sql);
			}
		}

		// on vire ensuite le parametrage
		$sql  = "DELETE FROM ".MAIN_DB_PREFIX."mylistdet";
		$sql .= " WHERE fk_mylist = ".$this->rowid;
		if (!$this->db->query($sql)) {
			$this->error=$this->db->lasterror();
			dol_syslog("Error sql=".$sql." ".$this->error, LOG_ERR);
			$error++;
		}

		// on vire ensuite le parametrage
		$sql  = "DELETE FROM ".MAIN_DB_PREFIX."mylist";
		$sql .= " WHERE rowid = ".$this->rowid;
		if (!$this->db->query($sql)) {
			$this->error=$this->db->lasterror();
			dol_syslog("Error sql=".$sql." ".$this->error, LOG_ERR);
			$error++;
		}
	}

	/**
	 * 	Retourne toutes les listes
	 *
	 *	@return	array					Tableau d'objet list
	 */
	function get_all_mylist()
	{
		$sql = "SELECT rowid, label, perms, langs, fieldinit, fieldused, titlemenu,";
		$sql.= " mainmenu, leftmenu, author, active, datatable FROM ".MAIN_DB_PREFIX."mylist";

		$res = $this->db->query($sql);
		if ($res) {
			$cats = array ();
			while ($rec = $this->db->fetch_array($res)) {
				$cat = array ();
				//$cat['code']		= $rec['code'];
				$cat['rowid']		= $rec['rowid'];
				$cat['label']		= $rec['label'];
				$cat['titlemenu']	= $rec['titlemenu'];
				$cat['mainmenu']	= $rec['mainmenu'];
				$cat['leftmenu']	= $rec['leftmenu'];
				$cat['elementtab']	= $rec['elementtab'];
				$cat['perms']		= $rec['perms'];
				$cat['langs']		= $rec['langs'];
				$cat['export']		= $rec['export'];
				$cat['author']		= $rec['author'];
				$cat['datatable']	= $rec['datatable'];
				$cat['active']		= $rec['active'];
				// analyse du paramétrage
				$cat['nbFieldsUsable']	= $this->nbFieldsUsable($rec['rowid']);
				$cat['nbFieldsShow']	= $this->nbFieldsShow($rec['rowid']);
				$cat['nbFilters']		= $this->nbFilters($rec['rowid']);
				$cats[$rec['rowid']] = $cat;
			}
			return $cats;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}

	function nbFieldsUsable($rowid)
	{
		$this->getChampsArray($rowid);
		return count($this->listsUsed);
	}

	function nbFieldsShow($rowid, $keyfield="visible")
	{
		$nbFields=0;
		$this->getChampsArray($rowid);
		if (is_array($this->listsUsed))
			foreach ($this->listsUsed as $key )
				if ($key[$keyfield]=="1") $nbFields++;
		return $nbFields;
	}

	function nbFilters($rowid)
	{
		return $this->nbFieldsShow($rowid, 'filter');
//		$nbFields=0;
//		$this->getChampsArray($rowid);
//		if (is_array($this->listsUsed))
//			foreach ($this->listsUsed as $key )
//				if ($key['filter']=="1") $nbFields++;
//		return $nbFields;
	}

		/**
	 * Return list fields of a mylist
	 *
	 * @return 	array				Array of fieldS
	 */
	function getChampsArray($rowid=0)
	{

		// on récupère les champs de la liste dans un Tableau
		$sql ="SELECT * FROM ".MAIN_DB_PREFIX."mylistdet ";
		if ($rowid > 0)
			$sql.= " WHERE fk_mylist=".$rowid;
		else
			$sql.= " WHERE fk_mylist=".$this->rowid;
		$sql.= " ORDER BY pos";

		dol_syslog(get_class($this)."::getChampsArray sql=".$sql);
//print $sql;
		$result=$this->db->query($sql);
		if ($result) {
			$num = $this->db->num_rows($result);
			$newArrays=array();

			$i = 1;
			while ($i < $num+1) {
				$objp = $this->db->fetch_object($result);

				$newArray=array();
				$newArray['rowid']		= $objp->rowid;
				$newArray['name']		= $objp->name;
				$newArray['alias']		= $objp->alias;
				$newArray['field']		= $objp->fieldname;
				$newArray['type']		= $objp->type;
				$newArray['pos']		= $objp->pos;
				$newArray['param']		= $objp->param;
				$newArray['align']		= $objp->align;
				$newArray['enabled']	= $objp->enabled;
				$newArray['visible']	= $objp->visible;
				$newArray['filter']		= $objp->filter;
				$newArray['width']		= $objp->width;
				$newArray['widthpdf']	= $objp->widthpdf;
				$newArray['sumreport']	= $objp->sumreport;
				$newArray['avgreport']	= $objp->avgreport;
				$newArray['filterinit']	= $objp->filterinit;
				// on rajoute à la liste
				$newArrays[$i] = $newArray;

				$i++;
			}
			$this->listsUsed = $newArrays;
			return 1;
		} else {
			dol_print_error($this->db);
			return 0;
		}
	}

		/**
	 * Return list fields of a mylist
	 *
	 * @return 	array				Array of fieldS
	 */
	function FetchChampArray($rowid)
	{
// on récupère les champs de la liste dans un Tableau
		$sql ="select * FROM ".MAIN_DB_PREFIX."mylistdet ";
		$sql.= " WHERE rowid=".$rowid;

		dol_syslog(get_class($this)."::FetchChampArray sql=".$sql);

		$result=$this->db->query($sql);
		if ($result) {
			$objp = $this->db->fetch_object($result);

			$newArray=array();
			$newArray['rowid']		= $objp->rowid;
			$newArray['name']		= $objp->name;
			$newArray['alias']		= $objp->alias;
			$newArray['field']		= $objp->fieldname;
			$newArray['type']		= $objp->type;
			$newArray['pos']		= $objp->pos;
			$newArray['param']		= $objp->param;
			$newArray['align']		= $objp->align;
			$newArray['enabled']	= $objp->enabled;
			$newArray['visible']	= $objp->visible;
			$newArray['filter']		= $objp->filter;
			$newArray['width']		= $objp->width;
			$newArray['widthpdf']	= $objp->widthpdf;
			$newArray['sumreport']	= $objp->sumreport;
			$newArray['avgreport']	= $objp->avgreport;
			$newArray['filterinit']	= $objp->filterinit;

			return $newArray;
		} else {
			dol_print_error($this->db);
			return array();
		}
	}


	function getexporttable($rowid)
	{
		$this->fetch($rowid);
		$tmp.="<?xml version='1.0' encoding='ISO-8859-1'?><mylist>\n";
		$tmp.="<label>".$this->label."</label>\n";
		$tmp.="<titlemenu>".$this->titlemenu."</titlemenu>\n";
		$tmp.="<mainmenu>".$this->mainmenu."</mainmenu>\n";
		$tmp.="<leftmenu>".$this->leftmenu."</leftmenu>\n";
		$tmp.="<elementtab>".$this->elementtab."</elementtab>\n";
		$tmp.="<perms>".$this->perms."</perms>\n";

		$tmp.="<datatable>".($this->datatable==1?"true":"false")."</datatable>\n";

		$tmp.="<langs>".$this->langs."</langs>\n";
		$tmp.="<export>".$this->export."</export>\n";
		$tmp.="<model_pdf>".$this->model_pdf."</model_pdf>\n";
		$tmp.="<author>".$this->author."</author>\n";
		$tmp.="<querylist>"."\n".htmlspecialchars(htmlspecialchars($this->querylist))."\n"."</querylist>"."\n";
		$tmp.="<fieldinit>"."\n".htmlspecialchars(htmlspecialchars($this->fieldinit))."\n"."</fieldinit>"."\n";
		$tmp.="<querydo>"."\n".htmlspecialchars(htmlspecialchars($this->querydo))."\n"."</querydo>"."\n";
		$tmp.="<fields>\n";
		$this->getChampsArray($rowid);
		foreach ($this->listsUsed as $key=> $value ) {
			$tmp.="\t".'<field >'."\n";
			//$tmp.="\t \t<key>".$key."</key>\n";
			$tmp.="\t \t<name>".$value['name']."</name>\n";
			$tmp.="\t \t<field>".$value['field']."</field>\n";
			$tmp.="\t \t<alias>".$value['alias']."</alias>\n";
			$tmp.="\t \t<type>".$value['type']."</type>\n";
			$tmp.="\t \t<pos>".$value['pos']."</pos>\n";
			$tmp.="\t \t<param>".$value['param']."</param>\n";
			$tmp.="\t \t<align>".$value['align']."</align>\n";
			$tmp.="\t \t<enabled>".$value['enabled']."</enabled>\n";

			$tmp.="\t \t<sumreport>".$value['sumreport']."</sumreport>\n";
			$tmp.="\t \t<avgreport>".$value['avgreport']."</avgreport>\n";

			$tmp.="\t \t<visible>".$value['visible']."</visible>\n";
			$tmp.="\t \t<filter>".$value['filter']."</filter>\n";
			$tmp.="\t \t<width>".$value['width']."</width>\n";
			$tmp.="\t \t<widthpdf>".$value['widthpdf']."</widthpdf>\n";
			$tmp.="\t \t<filterinit>".$value['filterinit']."</filterinit>\n";

			$tmp.="\t</field>\n";
		}
		$tmp.="</fields>\n";
		$tmp.="</mylist>\n";
		return $tmp;
	}

	function importlist($xml)
	{
		global $user;
		// on récupère le fichier et on le parse
		libxml_use_internal_errors(true);
		$sxe = simplexml_load_string($xml);
		if ($sxe === false) {
			echo "Erreur lors du chargement du XML\n";
			foreach (libxml_get_errors() as $error)
				echo "\t", $error->message;
			exit;
		}
		else
			$arraydata = json_decode(json_encode($sxe), true);
		$this->label=		$arraydata['label'];
		$this->titlemenu=	$arraydata['titlemenu'];
		$this->mainmenu= 	(!is_array($arraydata['mainmenu'])?is_array($arraydata['mainmenu']):"");
		$this->leftmenu= 	(!is_array($arraydata['leftmenu'])?is_array($arraydata['leftmenu']):"");
		$this->elementtab=	$arraydata['elementtab'];
		$this->perms=		$arraydata['perms'];
		$this->langs=		$arraydata['langs'];
		$this->author=		$arraydata['author'];
		$this->querylist=	$arraydata['querylist'];
		$this->querydo=		$arraydata['querydo'];
		$this->datatable=	($arraydata['datatable']=="true"?1:0);
		$this->fieldinit=	$arraydata['fieldinit'];
		$this->export= 		(!is_array($arraydata['export'])?is_array($arraydata['export']):0);
		$this->model_pdf=	$arraydata['model_pdf'];

		// on supprime dans mylist
		// Si on part d'une ancienne liste
		if ($this->rowid)
			$this->delete($user);

		// on crée une nouvelle liste
		$fk_mylist = $this->create($user);

		$tblfields=$arraydata['fields']['field'];
		foreach ($tblfields as $fields) {
			$this->name =		$fields['name'];
			$this->field =		$fields['field'];
			$this->alias =		(!is_array($fields['alias'])? $fields['alias']:'');
			$this->type =		$fields['type'];
			$this->param =		($fields['param']? $fields['param']:($fields['elementfield'] ? $fields['elementfield']:''));
			$this->align =		$fields['align'];
			$this->pos =		($fields['pos']? $fields['pos']:$fields['key']);
			if ($fields['enabled'] == 1 || $fields['enabled'] == 'true')
				$this->enabled = 1;
			else
				$this->enabled = 0;

			$this->sumreport =	$fields['sumreport'];
			$this->avgreport =	$fields['avgreport'];
			if ($fields['visible'] == 1 || $fields['visible'] == 'true')
				$this->visible = 1;
			else
				$this->visible = 0;
			if ($fields['filter'] == 1 || $fields['filter'] == 'true')
				$this->filter = 1;
			else
				$this->filter = 0;

			$this->width =		(!is_array($fields['width'])? $fields['width']:'');
			$this->filterinit =	(!is_array($fields['filterinit'])? $fields['filterinit']:'');

			$this->addField($user, $fk_mylist);
		}
		$this->rowid = $fk_mylist ;
		$this->fillmylistdet();

	}

	function getSelectTypeFields($selected )
	{
		global $conf, $langs;

		$tmp="<select name=type>";
		$tmp.="<option value='Text' ".($selected=="Text"?" selected ":"").">".$langs->trans("Text")."</option>";
		$tmp.="<option value='Number' ".($selected=="Number"?" selected ":"").">".$langs->trans("Number")."</option>";
		$tmp.="<option value='Price' ".($selected=="Price"?" selected ":"").">".$langs->trans("Price")."</option>";
		$tmp.="<option value='Percent' ".($selected=="Percent"?" selected ":"").">".$langs->trans("Percent")."</option>";
		$tmp.="<option value='Duration' ".($selected=="Duration"?" selected ":"").">".$langs->trans("Duration")."</option>";
		$tmp.="<option value='Date' ".($selected=="Date"?" selected ":"").">".$langs->trans("Date")."</option>";
		$tmp.="<option value='DateTime' ".($selected=="DateTime"?" selected ":"").">".$langs->trans("DateTime")."</option>";
		$tmp.="<option value='Hours' ".($selected=="Hours"?" selected ":"").">".$langs->trans("Hours")."</option>";

		$tmp.="<option value='Boolean' ".($selected=="Boolean"?" selected ":"").">".$langs->trans("Boolean")."</option>";
		$tmp.="<option value='Statut' ".($selected=="Statut"?" selected ":"").">".$langs->trans("StatutType")."</option>";
		$tmp.="<option value='List' ".($selected=="List"?" selected ":"").">".$langs->trans("List")."</option>";
		$tmp.="<option value='TooltipList' ".($selected=="TooltipList"?" selected ":"").">";
		$tmp.=$langs->trans("TooltipList")."</option>";
		$tmp.="<option value='ExtrafieldList' ".($selected=="ExtrafieldList"?" selected ":"").">";
		$tmp.=$langs->trans("ExtrafieldList")."</option>";
		$tmp.="<option value='CategoriesFilter' ".($selected=="CategoriesFilter"?" selected ":"").">";
		$tmp.=$langs->trans("CategoriesFilter")."</option>";
		// PLUS D'ACTUALITE le champ servant de clé principal est activé qu'avec mylistmore
		// la fonction est de retour
		$tmp.="<option value='Check' ".($selected=="Check"?" selected ":"").">".$langs->trans("Checkable")."</option>";

		$tmp.="</select>";
		return $tmp;
	}

	function getSelectelementTab($selected)
	{
		global $langs;

		$tmp="<select name=elementtab>";
		$tmp.="<option value='' >".$langs->trans("NotInTab")."</option>";
		$tmp.="<option value='thirdparty' ".($selected=="thirdparty" || $selected=="Societe"?" selected ":"").">";
		$tmp.=$langs->trans("Societe")."</option>";
		$tmp.="<option value='product' ".($selected=="product"?" selected ":"").">".$langs->trans("Product")."</option>";
		$tmp.="<option value='project' ".($selected=="project"?" selected ":"").">".$langs->trans("Project")."</option>";
		$tmp.="<option value='categproduct' ".($selected=="categproduct"?" selected ":"").">";
		$tmp.=$langs->trans("CategProduct")."</option>";
		$tmp.="<option value='categsociete' ".($selected=="categsociete"?" selected ":"").">";
		$tmp.=$langs->trans("CategSociete")."</option>";
		$tmp.="</select>";
		return $tmp;
	}

	/* permet de convertir l'ancien paramétrage des champs vers la table des champs */
	function fillmylistdet()
	{
		// pour l'ancienne compatibilité
		if (is_array($this->OLDlistsUsed)) {
			// on insère en base
			foreach ($this->OLDlistsUsed as $key=> $value) {
				//var_dump($value);
				$sql="INSERT INTO ".MAIN_DB_PREFIX."mylistdet ";
				$sql.="( fk_mylist, pos, fieldname, name, alias, type, param, align,";
				$sql.="  enabled, visible, filter, width, widthpdf, filterinit";
				$sql.=") values ";
				$sql.="( ". $this->rowid;
				$sql.=", ".( $key ? $key : 0); // lors de la reprise la position c'est la key
				$sql.=", '".$this->db->escape($value['field'])."'";
				$sql.=", '".$this->db->escape($value['name'])."'";
				$sql.=", '".$this->db->escape($value['alias'])."'";
				$sql.=", '".$this->db->escape($value['type'])."'";
				$sql.=", '".$this->db->escape(($value['elementfield']?$value['elementfield']:$value['param']))."'";
				$sql.=", '".$this->db->escape($value['align'])."'";
				$sql.=", ". $value['enabled'];
				$sql.=", ". $value['visible'];
				$sql.=", ". $value['filter'];
				$sql.=", ".($value['width']?$value['width']:0);
				$sql.=", ".($value['widthpdf']?$value['widthpdf']:0);
				$sql.=", '".$this->db->escape($value['filterinit'])."')";
//print $sql."<br>";
				$resql = $this->db->query($sql);
			}
			// on purge la variable

			// on vérifie que tout est ok avant de purger
			$sql ="select * FROM ".MAIN_DB_PREFIX."mylistdet ";
			$sql.= " WHERE fk_mylist=".$this->rowid;
			$sql.= " ORDER BY pos";

			dol_syslog(get_class($this)."::fillmylistdet sql=".$sql);

			$result=$this->db->query($sql);
			if ($result) {
				if ($this->db->num_rows($resql) == count($this->OLDlistsUsed)) {
					$this->OLDlistsUsed="";
					// on purge du champs de la table
					$sql = "UPDATE ".MAIN_DB_PREFIX."mylist";
					$sql.= " SET fieldused=''";
					$sql.= " where rowid=".$this->rowid;
					$resql = $this->db->query($sql);
				}
			}
		}
		$this->getChampsArray($this->rowid);
		return 0;
	}
}

// TODO sortir les fonction lié au champs de la classe principale
class Mylistdet extends CommonObject
{
	public $element='mylistdet';
	public $table_element='mylistdet';

	// champs des champs de la liste
	var $idfield;		// clé numérique associé au champ
	var $name;			// libelle du champs dans la base
	var $field;			// nom du champs dans la base
	var $alias;
	//var $elementfield; 	// permet de gérer les liste et les clées
	var $param;		 	// permet de gérer les liste et les clées
	var $type;
	var $pos;
	var	$align;
	var $enabled;
	var	$visible;
	var $filter;
	var $sumreport;
	var $avgreport;
	var $width;			// la taille de la colonne
	var $widthpdf;		// la taille de la colonne dans l'édition pdf
	var $filterinit;	// une valeur de filtrage par défaut
	var $updatekey;		// pour la mise à jour
}