	<?php
/* Copyright (C) 2001-2006	Rodolphe Quiedeville<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011	Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin	   <regis.houssin@capnetworks.com>
 * Copyright (C) 2013		Cédric Salvador	 <csalvador@gpcsolutions.fr>
 * Copyright (C) 2013		Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2015-2017	Charlene Benke		<charlie@patas-monkey.com>
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
 *	\file	   /portofolio/list.php
 *	\ingroup	commercial portofolio management
 *	\brief	  List of customers
 */

// Dolibarr environment
$res=0;
if (! $res && file_exists("../main.inc.php"))
	$res=@include("../main.inc.php");		// For root directory
if (! $res && file_exists("../../main.inc.php"))
	$res=@include("../../main.inc.php");	// For "custom" directory

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';

dol_include_once('/portofolio/core/lib/portofolio.lib.php');


$langs->load("companies");
$langs->load("customers");
$langs->load("suppliers");
$langs->load("commercial");
$langs->load("portofolio@portofolio");

// Security check
$socid = GETPOST('socid', 'int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe', $socid, '');

$action  = GETPOST("action");


$sortorder="ASC";
$sortfield="s.nom";

$searchcompany		= GETPOST("searchcompany");
$searchstatus		= GETPOST("searchstatus", 'int');
$searchcountry		= GETPOST("searchcountry", 'int');

$searchzipcode		= GETPOST("searchzipcode", 'alpha');
$searchtown			= GETPOST("searchtown", 'alpha');


$search_type 		= GETPOST("search_type",'alpha');

$searchtypent		= GETPOST("searchtypent", 'int');
$searcheffectif		= GETPOST("searcheffectif", 'int');
$affectedCustomer	= (GETPOST("affectedCustomer", 'int')? GETPOST("affectedCustomer", 'int'):0);

$searchusergroup	= GETPOST("searchusergroup", 'int');
$searchenableduser	= GETPOST("searchenableduser", 'int');

// Load sale and categ filters
$searchsale			= GETPOST("searchsale");
$searchnotsale		= GETPOST("searchnotsale");

$searchcateg		= GETPOST("searchcateg", 'int');

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('customerlist'));


/*
 * Actions
 */

$parameters=array();
// Note that $action and $object may have been modified by some hooks
$reshook=$hookmanager->executeHooks('doActions', $parameters);
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

// Get list of users allowed to be viewed
$sqlUsr = "SELECT u.rowid, u.lastname, u.firstname, u.statut, u.login, u.color";
$sqlUsr.= " FROM ".MAIN_DB_PREFIX."user as u";
if ($searchusergroup > 0 )
	$sqlUsr.= " LEFT JOIN ".MAIN_DB_PREFIX."usergroup_user as ugu ON u.rowid = ugu.fk_user";

$sqlUsr.= " WHERE u.entity IN (0, ".$conf->entity.")";
if ($searchenableduser >= 0 )
	$sqlUsr.= " AND u.statut =".$searchenableduser;

if ($searchusergroup > 0 )
	$sqlUsr.= " AND ugu.fk_usergroup =".$searchusergroup;


$sqlUsr.= " ORDER BY lastname ASC";


if ($searchstatus=='') $searchstatus=-1; // always display activ customer first

if ($action=='change' && GETPOST("updatecheck") == $langs->trans("UpdateCheck")) {
	//on les réaffecte de nouveau
	$idlist = explode(',', GETPOST("idlist"));
	foreach ($idlist as $key) {
		$resqlUsr = $db->query($sqlUsr);
		if ($resqlUsr) {
			$num_usr = $db->num_rows($resqlUsr);
			while ($obj_usr = $db->fetch_object($resqlUsr)) {
				$sql = "DELETE from ".MAIN_DB_PREFIX."societe_commerciaux";
				$sql.= " WHERE fk_user = ".$obj_usr->rowid;
				$sql.= " AND fk_soc = ".$key;
				$result = $db->query($sql);

				if (GETPOST("chk-".$key.'-'.$obj_usr->rowid)) {
					$sql = "INSERT INTO ".MAIN_DB_PREFIX."societe_commerciaux (fk_soc, fk_user)";
					$sql.= " VALUES ( ".$key.", ".$obj_usr->rowid.")";
					$result = $db->query($sql);
				}
			}
			$db->free($resqlUsr);
		}
	}
}

/*
 * view
 */

$formother=new FormOther($db);
$form = new Form($db);
$formcompany = new FormCompany($db);
$thirdpartystatic=new Societe($db);

$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('', $langs->trans("Portofolio"), $help_url);

$sql = "SELECT DISTINCT s.rowid, s.nom as name, s.client, s.zip, s.town,";
if (DOL_VERSION < "3.7.0")
	$sql.= "  c.libelle as pays,";
else
	$sql.= "  c.label as pays,";
$sql.= " te.libelle as typent, ef.libelle as effectif,";
$sql.= " s.prefix_comm, s.code_client, s.code_compta, s.status as status,";
$sql.= " s.datec, s.canvas";

if ($searchsale && $affectedCustomer=="0")
	$sql.= " FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc, ".MAIN_DB_PREFIX."societe as s";
else
	$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";

if (! empty($searchcateg)) {
	// We need this table joined to the select in order to filter by categ
	if (DOL_VERSION < "3.8.0")
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."categorie_societe as cs ON s.rowid = cs.fk_societe";
	else
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."categorie_societe as cs ON s.rowid = cs.fk_soc";
}

if (DOL_VERSION < "3.7.0")
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_pays as c ON c.rowid = s.fk_pays";
else
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as c ON c.rowid = s.fk_pays";

$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX."c_typent as te ON te.id = s.fk_typent";
$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX."c_effectif as ef ON ef.id = s.fk_effectif";
$sql.= ' WHERE s.entity IN ('.getEntity('societe', 1).')';

if ($searchsale && $affectedCustomer==0)
	$sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$searchsale;
if ($affectedCustomer == 1)
	$sql.= " AND s.rowid not in (select distinct sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc)";
if ($searchnotsale && $affectedCustomer==2) {
	$sql.= " AND s.rowid not in ";
	$sql.= " (SELECT DISTINCT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux AS sc";
	$sql.= "  WHERE sc.fk_user = ".$searchnotsale.")";
}
if ($searchcateg > 0)		$sql.= " AND cs.fk_categorie = ".$searchcateg;
if ($searchcateg == -2)		$sql.= " AND cs.fk_categorie IS NULL";
if ($searchcountry > 0)		$sql.= " AND s.fk_pays = ".$searchcountry;
if ($searcheffectif > 0)	$sql.= " AND s.fk_effectif = ".$searcheffectif;
if ($searchtypent > 0)		$sql.= " AND s.fk_typent = ".$searchtypent;

if ($searchstatus == 1 || $searchstatus == 0)	$sql.= " AND s.status = ".$searchstatus;

if ($searchcompany)
	$sql .= natural_search('s.nom', $searchcompany);

if ($searchzipcode)
	$sql.= " AND s.zip LIKE '".$db->escape($searchzipcode)."%'";

if ($searchtown)
	$sql .= natural_search('s.town', $searchtown);

if ($search_type > 0 && in_array($search_type, array('1,3','2,3')))
	$sql .= " AND s.client IN (".$db->escape($search_type).")";
if ($search_type > 0 && in_array($search_type, array('4')))
	$sql .= " AND s.fournisseur = 1";
if ($search_type == '0')
	$sql .= " AND s.client = 0 AND s.fournisseur = 0";

$sql.= $db->order($sortfield, $sortorder);
// si pas de filtre on limite à 150 enregs
if	( 	empty($searchcompany) && empty($searchzipcode) && empty($searchtown)
		&& ($searchsale < 1 )
		&& ($searchcateg < 1 )
		&& ($searchcountry < 1)
		&& ($searcheffectif < 1)
		&& ($searchtypent < 1)
	)
	$sql.= $db->plimit(150);

dol_syslog('comm/list.php:', LOG_DEBUG);
//print $sql;
$result = $db->query($sql);
if ($result) {
	$num = $db->num_rows($result);

	$picto="portofolio@portofolio";
	print_fiche_titre($langs->trans("CustomerFilterElement"), '', $picto);

	print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">'."\n";

	dol_fiche_head();

	$i = 0;
	print "<input type=hidden name=action value='change'>\n";
	// Filter on categories
	$moreforfilter='';
	if (! empty($conf->categorie->enabled)) {
		$moreforfilter.=$langs->trans('Categories'). ': ';
		$moreforfilter.=$formother->select_categories(2, $searchcateg, 'searchcateg', 1);
		$moreforfilter.=' &nbsp; &nbsp; &nbsp; ';
	}

	$moreforfilter.=$langs->trans('Company'). ': ';
	$moreforfilter.='<input type="text" class="flat" name="searchcompany" value="'.$searchcompany.'" size="20">';
	$moreforfilter.=' <br><br> ';

	$moreforfilter.=$langs->trans('Zip'). ': ';
	$moreforfilter.='<input type="text" class="flat" name="searchzipcode" value="'.$searchzipcode.'" size="10">';
	$moreforfilter.=' &nbsp; &nbsp; &nbsp; ';

	$moreforfilter.=$langs->trans('Town'). ': ';
	$moreforfilter.='<input type="text" class="flat" name="searchtown" value="'.$searchtown.'" size="10">';
	$moreforfilter.=' &nbsp; &nbsp; &nbsp; ';

	$moreforfilter.="";
	$moreforfilter.=$langs->trans('Country'). ': ';
	$moreforfilter.=$form->select_country($searchcountry, 'searchcountry');
	$moreforfilter.=' &nbsp; &nbsp; &nbsp; ';
	$moreforfilter.=' <br><br> ';
	$moreforfilter.=$langs->trans("Staff").': ';
	$moreforfilter.=$form->selectarray(
					"searcheffectif", $formcompany->effectif_array(0), $searcheffectif,
					0, 0, 0, '', 0, 0, 0, '', '', 1
	);
	$moreforfilter.=' &nbsp; &nbsp; &nbsp; ';

	$moreforfilter.=$langs->trans("ThirdPartyType").': ';
	$moreforfilter.=$form->selectarray(
					"searchtypent", $formcompany->typent_array(0), $searchtypent,
					0, 0, 0, '', 0, 0, 0,
					(empty($conf->global->SOCIETE_SORT_ON_TYPEENT)?'ASC':$conf->global->SOCIETE_SORT_ON_TYPEENT),
					0, 1
	);
	$moreforfilter.=' &nbsp; &nbsp; &nbsp; ';
	$moreforfilter.=$langs->trans("Status").': ';
	$arrayStatus=array('-1'=>'','0'=>$langs->trans('ActivityCeased'),'1'=>$langs->trans('InActivity'));
	$moreforfilter.=$form->selectarray('searchstatus', $arrayStatus, $searchstatus);

	$moreforfilter.=' &nbsp; &nbsp; &nbsp; ';
	$moreforfilter.=$langs->trans("ProspectCustomer").': ';
	$moreforfilter.='<select class="flat" name="search_type">';
	$moreforfilter.='<option value="-1"'.($search_type==''?' selected':'').'>&nbsp;</option>';
	if (empty($conf->global->SOCIETE_DISABLE_CUSTOMERS))
		$moreforfilter.='<option value="1,3"'.($search_type=='1,3'?' selected':'').'>'.$langs->trans('Customer').'</option>';
	if (empty($conf->global->SOCIETE_DISABLE_PROSPECTS))
		$moreforfilter.='<option value="2,3"'.($search_type=='2,3'?' selected':'').'>'.$langs->trans('Prospect').'</option>';
	$moreforfilter.='<option value="4"'.($search_type=='4'?' selected':'').'>'.$langs->trans('Supplier').'</option>';
	$moreforfilter.='<option value="0"'.($search_type=='0'?' selected':'').'>'.$langs->trans('Others').'</option>';
	$moreforfilter.='</select>';


	$moreforfilter.="<hr>";
	$moreforfilter.=$langs->trans("GroupUser").': ';
	$moreforfilter.=$form->select_dolgroups($searchusergroup, "searchusergroup", 1);
	$moreforfilter.=' &nbsp; &nbsp; &nbsp; ';
	$moreforfilter.=$langs->trans("UserStatus").': ';
	$tbluserstatus = array('-1'=>'', '0'=>$langs->trans('DisabledUser'), '1'=>$langs->trans('EnabledUser'));
	$moreforfilter.=$form->selectarray('searchenableduser', $tbluserstatus, $searchenableduser);


	$moreforfilter.="<br><br>";
	$moreforfilter.="<input type=radio value=0 ".($affectedCustomer==0?'checked':'')." name=affectedCustomer>";
	$moreforfilter.="&nbsp;".$langs->trans('CustomerAffectedToUser'). ": ";
	$moreforfilter.=$formother->select_salesrepresentatives($searchsale, 'searchsale', $user);
	$moreforfilter.=' &nbsp; &nbsp; &nbsp; ';
	$moreforfilter.="<input type=radio value=1 ".($affectedCustomer==1?'checked':'')." name=affectedCustomer>";
	$moreforfilter.="&nbsp;".$langs->trans('CustomerNotAffected');
	$moreforfilter.=" &nbsp; &nbsp; &nbsp;";
	$moreforfilter.="<input type=radio value=2 ".($affectedCustomer==2?'checked':'')." name=affectedCustomer>";
	$moreforfilter.="&nbsp;".$langs->trans('CustomerNotAffectedToUser'). ': ';
	$moreforfilter.=$formother->select_salesrepresentatives($searchnotsale, 'searchnotsale', $user);
	$moreforfilter.=" &nbsp; &nbsp; &nbsp;";
	$moreforfilter.='<input type="submit" class=button';
	$moreforfilter.=' value="'.dol_escape_htmltag($langs->trans("Search")).'"';
	$moreforfilter.=' title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print $moreforfilter;

	dol_fiche_end();

	print_barre_liste(
					$langs->trans("ListOfThirdParties"), $page, $_SERVER["PHP_SELF"],
					'', $sortfield, $sortorder, '', $num, 0, 'title_companies'
	);

	print '<table id="listtable" class="noborder" width="100%">';
	print "<thead>\n";
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Company"));
	print_liste_field_titre($langs->trans("Zip"));
	print_liste_field_titre($langs->trans("Town"));
	print_liste_field_titre($langs->trans("Country"));
	print_liste_field_titre($langs->trans("Staff"));
	print_liste_field_titre($langs->trans("ThirdPartyType"));
	print_liste_field_titre($langs->trans("Datec"));
	print_liste_field_titre($langs->trans("Status"));

	$resqlUsr = $db->query($sqlUsr);
	if ($resqlUsr) {
		$num_usr = $db->num_rows($resqlUsr);
		while ($obj_usr = $db->fetch_object($resqlUsr)) {
			print '<th class="liste_titre" width=100px>';
			print '<input type=checkbox class="chkall" id="chkid'.$obj_usr->rowid.'" >&nbsp;';
			print dolGetFirstLastname($obj_usr->firstname, $obj_usr->lastname);
			print '</th>';
		}
		$db->free($resqlUsr);
	}
	print "</tr>\n";
	print "</thead>\n";
	print "<tbody>\n";
	$var=True;
	$idlist="";

	while ($i < $num) {
		$obj = $db->fetch_object($result);
		$idlist.=$obj->rowid.",";
		$var=!$var;

		print "<tr ".$bc[$var].">";
		print '<td>';
		$thirdpartystatic->id=$obj->rowid;
		$thirdpartystatic->name=$obj->name;
		$thirdpartystatic->client=$obj->client;
		$thirdpartystatic->code_client=$obj->code_client;
		$thirdpartystatic->canvas=$obj->canvas;
		$thirdpartystatic->status=$obj->status;
		print $thirdpartystatic->getNomUrl(1);
		print '</td>';
		print '<td>'.$obj->zip.'</td>';
		print '<td>'.$obj->town.'</td>';
		print '<td>'.$obj->pays.'</td>';
		print '<td>'.$obj->effectif.'</td>';
		print '<td>'.$obj->typent.'</td>';
		print '<td align="left">'.dol_print_date($db->jdate($obj->datec), 'day').'</td>';
		print '<td align="left">'.$thirdpartystatic->getLibStatut(2).'</td>';
		$resqlUsr = $db->query($sqlUsr);
		if ($resqlUsr) {
			while ($obj_usr = $db->fetch_object($resqlUsr)) {
				print '<td align="center"';
				if ($obj_usr->color)
					print ' bgcolor="'.$obj_usr->color.'" ';
				print '>';
				print '<input type=checkbox class="chkid'.$obj_usr->rowid.'" value=1';
				print ' name="chk-'.$obj->rowid.'-'.$obj_usr->rowid.'"';
				if (commercial_affected($obj->rowid, $obj_usr->rowid))
					print ' checked ';
				print '>';
				print '</td>';
			}
		}

		print "</tr>\n";
		$i++;
	}
	print "</tbody>\n";
	print "<tfoot>";
	print "<tr class='liste_total'>";
	print "<th class='liste_total' colspan=8 align=left>";
	print "<input type=hidden size=50 name=idlist value='".substr($idlist, 0, -1)."'>\n";
	print "</th>";
	print "<th class='liste_total' colspan=".$num_usr." align=center>";
	if ($user->rights->portofolio->setup)
		print "<input type=submit class=button name='updatecheck' value='".$langs->trans("UpdateCheck")."'>";
	print "</th>";
	print "</tr>\n";
	print "</tfoot>";
	print "</table>\n";

	print "</form>\n";
	$db->free($result);

	$parameters=array('sql' => $sql);
	// Note that $action and $object may have been modified by hook
	$formconfirm=$hookmanager->executeHooks('printFieldListFooter', $parameters);
} else
	dol_print_error($db);

llxFooter();
$db->close();
// si datatable est actif - jQuery datatables
if (! empty($conf->global->MAIN_USE_JQUERY_DATATABLES)
	|| (defined('REQUIRE_JQUERY_DATATABLES')
	&& constant('REQUIRE_JQUERY_DATATABLES'))) {
	print "\n";
	print '<script type="text/javascript">'."\n";
	print 'jQuery(document).ready(function() {'."\n";
	print 'jQuery("#listtable").dataTable( {'."\n";
	print '"sDom": \'<>ltip\','."\n";
	print '"oColVis": {"buttonText": "'.$langs->trans('showhidecols').'" },'."\n";
	print '"bPaginate": true,'."\n";
	print '"bFilter": false,'."\n";
	print '"sPaginationType": "full_numbers",'."\n";
	print '"aoColumns": [null,{ "sType": "numeric"},null,null,null,null,{ "sType": "date-euro"},null ';
	for ($i=0; $i < $num_usr ; $i++)
		print ",null";

	print '],'."\n";
	print '"bJQueryUI": false,'."\n";
	print '"oLanguage": {"sUrl": "'.$langs->trans('datatabledict').'" },'."\n";
	print '"iDisplayLength": 25,'."\n";
	print '"aLengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],'."\n";
	print '"bSort": true'."\n";
	print '} );'."\n";
	print '});'."\n";

	// extension pour le trie
	print 'jQuery.extend( jQuery.fn.dataTableExt.oSort, {';
	// pour gérer les . et les , des décimales et le blanc des milliers
	print '"numeric-comma-pre": function ( a ) {';
	print 'var x = (a == "-") ? 0 : a.replace( /,/, "." );';
	print 'x = x.replace( " ", "" );';
	print 'return parseFloat( x );';
	print '},';
	print '"numeric-comma-asc": function ( a, b ) {return ((a < b) ? -1 : ((a > b) ? 1 : 0));},';
	print '"numeric-comma-desc": function ( a, b ) {return ((a < b) ? 1 : ((a > b) ? -1 : 0));},';

	// pour gérer les dates au format européenne
	print '"date-euro-pre": function ( a ) {';
	print 'if ($.trim(a) != "") {';
	print 'var frDatea = $.trim(a).split("/");';
	print 'var x = (frDatea[2] + frDatea[1] + frDatea[0]) * 1;';
	print '} else { var x = 10000000000000; }';
	print 'return x;';
	print '},';
	print '"date-euro-asc": function ( a, b ) {return a - b; },';
	print '"date-euro-desc": function ( a, b ) {return b - a;}';
	print '} );';
	print "\n";
	print '</script>'."\n";
}

?>
<script>
$(document).ready(function() {
	$('.chkall').click(function(event) {
		if (this.checked) { // check select status
			$('.'+ event.target.id).each(function() { //loop through each checkbox
				this.checked = true;
			});
		}else{
			$('.'+ event.target.id).each(function() { //loop through each checkbox
				this.checked = false;
			});
		}
	});
});
</script>