<?php
/* Copyright (C) 2002-2005 	Rodolphe Quiedeville 	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 	Laurent Destailleur  	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2015	  	Alexandre Spangaro   	<aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2016-2017	Charlie Benke			<charlie@patas-monkey.com>
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
 *	  \file	   portofolio/catchange.php
 * 		\ingroup	portofolio
 *	  \brief	  Page of change categories selection
 */

$res=0;
if (! $res && file_exists("../main.inc.php"))
	$res=@include("../main.inc.php");		// For root directory
if (! $res && file_exists("../../main.inc.php"))
	$res=@include("../../main.inc.php");	// For "custom" directory

require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

if (! $user->rights->portofolio->lire && ! $user->admin)
	accessforbidden();

$langs->load("users");
$langs->load("companies");
$langs->load("members");

$langs->load("portofolio@portofolio");

// Security check (for external users)
$socid=0;
if ($user->societe_id > 0)
	$socid = $user->societe_id;

$action=GETPOST('action', 'alpha');
$showall=GETPOST('showall');

$key = GETPOST('key', 'alpha');
$companyfilterkey = GETPOST('companyfilterkey');

$qteStock = GETPOST('qteStock');

$statut_buy =GETPOST('statut_buy');
if ($statut_buy == '') $statut_buy = -1;

$statut_sell =GETPOST('statut_sell');
if ($statut_sell == '') $statut_sell = -1;

$type = GETPOST('type');
if ($type == '') $type = -1;

$morphy = GETPOST('morphy');
if ($morphy == '') $morphy = -1;

$statut = GETPOST('statut');
if ($statut == '') $statut = -1;

$client = GETPOST('client');
if ($client == '') $client = -1;

$categselect=GETPOST('categselect', 'alpha');
if ($categselect == '') $categselect='product';

$categcolfilter=GETPOST('categcolfilter');
if ($categcolfilter == '') $categcolfilter =-1;

$form = new Form($db);

/*
 * View
 */

llxHeader('', $langs->trans("MassCategorieAffect"));


$buttonmulticompany="";
$picto="portofolio@portofolio";
print_fiche_titre($langs->trans("MassCategorieAffect"), $buttonmulticompany, $picto);

// on ajoute selon les catégories présentes
$categoriesarray = array();
if (! empty($conf->product->enabled) || ! empty($conf->product->enabled));
	$categoriesarray=array_merge($categoriesarray, array('product'			=> $langs->trans('Products')));
if (! empty($conf->societe->enabled)) {
	$categoriesarray=array_merge($categoriesarray, array('societe'			=> $langs->trans('Companys')));
	if (! empty($conf->fournisseur->enabled))
		$categoriesarray=array_merge($categoriesarray, array('fournisseur'	=> $langs->trans('Fournish')));
	$categoriesarray=array_merge($categoriesarray, array('socpeople'		=> $langs->trans('Contacts')));
}
if (! empty($conf->adherent->enabled))
	$categoriesarray=array_merge($categoriesarray, array('member'			=> $langs->trans('Members')));

if (! empty($conf->projet->enabled) && DOL_VERSION >= "5.0.0")
	$categoriesarray=array_merge($categoriesarray, array('project'			=> $langs->trans('Projects')));

$categoriesarray=array_merge($categoriesarray, array('user'					=> $langs->trans('Users')));

$categoriestatic	= new Categorie($db);

print '<br>';
print '<form method=post>';
// on ne voit tous que si il y  a eu passage dans la sélection

print '<table class=nobordernopadding width="800px">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans('SelectCategories').'</td><td align=right >';
print $form->selectarray("categselect", $categoriesarray, $categselect, 0);
print '</td><td width=150px align=center>';
print '<input type=submit value="'.$langs->trans('SelectFilter').'">';
print '</td></tr>';
print '</table>';
print '</form>';
print '<br>';
print '<form method=post>';
print '<input type=hidden name=categselect value="'.$categselect.'">';
print '<input type=hidden name=showall value="1">';
print '<table class="border" width="800px">';

switch ($categselect)
{
	case 'product' :
		print '<tr class="liste_titre"><td>'.$langs->trans('ProductCategorieColFilter').'</td><td>';
		print $form->select_all_categories('product', $categcolfilter, 'categcolfilter');
		print '</td>';
		break;

	case 'societe' :
		print '<tr class="liste_titre"><td>'.$langs->trans('CustomerCategColFilter').'</td><td>';
		print $form->select_all_categories('customer', GETPOST('categcolfilter'), 'categcolfilter');
		print '</td>';
		break;

	case 'fournisseur' :
		print '<tr class="liste_titre"><td>'.$langs->trans('SupplierCategColFilter').'</td><td>';
		print $form->select_all_categories('supplier', GETPOST('categcolfilter'), 'categcolfilter');
		print '</td>';
		break;

	case 'member' :
		print '<tr class="liste_titre"><td>'.$langs->trans('MemberCategorieColFilter').'</td><td>';
		print $form->select_all_categories('member', $categcolfilter, 'categcolfilter');
		print '</td>';
		break;

	case 'socpeople' :
		print '<tr class="liste_titre"><td>'.$langs->trans('ContactCategorieColFilter').'</td><td>';
		print $form->select_all_categories('contact', $categcolfilter, 'categcolfilter');
		print '</td>';
		break;

	case 'user' :
		print '<tr class="liste_titre"><td>'.$langs->trans('UserCategorieColFilter').'</td><td>';
		print $form->select_all_categories('user', $categcolfilter, 'categcolfilter');
		print '</td>';
		break;

	case 'project' :
		print '<tr class="liste_titre"><td>'.$langs->trans('ProjectCategorieColFilter').'</td><td>';
		print $form->select_all_categories('project', $categcolfilter, 'categcolfilter');
		print '</td>';
		break;

}



print '<td width=150px  rowspan=10 align=center><input type=submit value="'.$langs->trans('ApplyFilter').'"></td></tr>';
print '<tr><td colspan=2 align=center><b>'.$langs->trans('AssociatedFilter').'</b></td></tr>';
switch ($categselect)
{
	case 'product' :
		print '<tr><td>'.$langs->trans('ProductFilter').'</td><td>';
		print '<input type=text name=key value="'.$key.'">';
		print '</td></tr>';

		print '<tr><td>'.$langs->trans('QtyStock').'</td><td>';
		$szMsg ="'NNNNN' filtre sur une valeur <br>";
		$szMsg.="'NNNNN+NNNNN' filtre sur une plage de valeur<br>";
		$szMsg.="'&lt; NNNNN' filtre sur les valeurs inf&eacute;rieurs<br>";
		$szMsg.="'&gt; NNNNN' filtre sur les valeurs sup&eacute;rieurs<br>";

		print $form->textwithpicto('<input type=text name=qteStock value="'.$qteStock.'">', $szMsg);

		print '</td></tr>';

		print '<tr><td>'.$langs->trans('ProductType').'</td><td>';
		$typearray=array(	'0' => $langs->trans("Products"),
							'1' => $langs->trans("Services"));
		print $form->selectarray('type', $typearray, $type, 1);
		print '</td></tr>';

		print '<tr><td class="fieldrequired">'.$langs->trans("Status").' ('.$langs->trans("Sell").')</td>';
		print '<td>';
		$statutarray=array(	'1' => $langs->trans("OnSell"),
							'0' => $langs->trans("NotOnSell"));
		print $form->selectarray('statut_sell', $statutarray, $statut_sell, 1);
		print '</td></tr>';

		// To buy
		print '<tr><td class="fieldrequired">'.$langs->trans("Status").' ('.$langs->trans("Buy").')</td><td >';
		$statutarray=array(	'1' => $langs->trans("ProductStatusOnBuy"),
							'0' => $langs->trans("ProductStatusNotOnBuy"));
		print $form->selectarray('statut_buy', $statutarray, $statut_buy, 1);
		print '</td></tr>';
		break;

	case 'societe' :
	case 'fournisseur' :
		print '<tr><td>'.$langs->trans('ThirdPartiesFilter').'</td><td>';
		print '<input type=text name=key value="'.$key.'">';
		print '</td></tr>';
		if (DOL_VERSION >= "3.8.0")
			print '<tr><td >'.fieldLabel('ProspectCustomer', 'customerprospect', 1).'</td>';
		else
			print '<tr><td >'.$langs->trans('ProspectCustomer').'</td>';

		print '<td ><select class="flat" name="client" id="customerprospect">';

		print '<option value="-1"></option>';
		if (empty($conf->global->SOCIETE_DISABLE_PROSPECTS))
			print '<option value="2"'.($client==2?' selected':'').'>'.$langs->trans('Prospect').'</option>';
		if ( empty($conf->global->SOCIETE_DISABLE_PROSPECTS)
		  && empty($conf->global->SOCIETE_DISABLE_CUSTOMERS))
			print '<option value="3"'.($client==3?' selected':'').'>'.$langs->trans('ProspectCustomer').'</option>';
		if (empty($conf->global->SOCIETE_DISABLE_CUSTOMERS))
			print '<option value="1"'.($client==1?' selected':'').'>'.$langs->trans('Customer').'</option>';
		print '<option value="0"'.((string) $client == '0'?' selected':'').'>';
		print $langs->trans('NorProspectNorCustomer').'</option>';
		print '</select></td></tr>';

		print '<tr><td>'.$langs->trans('Statut').'</td><td>';
		$statutarray=array('1' => $langs->trans("InActivity"), '0' => $langs->trans("ActivityCeased"));
		print $form->selectarray('statut', $statutarray, GETPOST('statut'), 1);
		print '</td></tr>';
		break;

	case 'member' :
		print '<tr><td>'.$langs->trans('MemberFilter').'</td><td>';
		print '<input type=text name=key value="'.$key.'">';
		print '</td></tr>';

		// Type
		print '<tr><td class="fieldrequired">'.$langs->trans("MemberType").'</td><td>';

		require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';
		$adhtype = new AdherentType($db);
		$listetype=$adhtype->liste_array();
		if (count($listetype)) {
			print $form->selectarray("typeid", $listetype, $type, 1);
		} else {
			print '<font class="error">'.$langs->trans("NoTypeDefinedGoToSetup").'</font>';
		}
		print "</td></tr>\n";

		// Morphy
		$morphys["phy"] = $langs->trans("Physical");
		$morphys["mor"] = $langs->trans("Moral");
		print '<tr><td class="fieldrequired">'.$langs->trans("Nature")."</td><td>\n";
		print $form->selectarray("morphy", $morphys, $morphy, 1);
		print "</td></tr>\n";

		print '<tr><td>'.$langs->trans('Statut').'</td><td>';
		$statutarray=array(	'1' => $langs->trans("Enabled"),
							'0' => $langs->trans("Disabled"));
		print $form->selectarray('statut', $statutarray, GETPOST('statut'), 1);
		print '</td></tr>';
		break;

	case 'socpeople' :
		print '<tr><td>'.$langs->trans('ContactFilter').'</td><td>';
		print '<input type=text name=key value="'.$key.'">';
		print '</td></tr>';

		print '<tr><td>'.$langs->trans('CompanyFilter').'</td><td>';
		print '<input type=text name=companyfilterkey value="'.$companyfilterkey.'">';

		print '<tr><td>'.$langs->trans('Statut').'</td><td>';
		$statutarray=array(	'1' => $langs->trans("Enabled"),
							'0' => $langs->trans("Disabled"));
		print $form->selectarray('statut', $statutarray, GETPOST('statut'), 1);
		print '</td></tr>';
		break;

	case 'user' :
		print '<tr><td>'.$langs->trans('UserFilter').'</td><td>';
		print '<input type=text name=key value="'.$key.'">';
		print '</td></tr>';

		print '<tr><td>'.$langs->trans('Statut').'</td><td>';
		$statutarray=array(	'1' => $langs->trans("Enabled"),
							'0' => $langs->trans("Disabled"));
		print $form->selectarray('statut', $statutarray, GETPOST('statut'), 1);
		print '</td></tr>';
		break;

	case 'project' :
		print '<tr><td>'.$langs->trans('ProjectFilter').'</td><td>';
		print '<input type=text name=key value="'.$key.'">';
		print '</td></tr>';

		print '<tr><td>'.$langs->trans('Statut').'</td><td>';
		$statutarray=array(	'0' => $langs->trans("Draft"),
							'1' => $langs->trans("Enabled"),
							'2' => $langs->trans("Close"),
							'99' => $langs->trans("NotClose"));
		print $form->selectarray('statut', $statutarray, GETPOST('statut'), 1);
		print '</td></tr>';
		break;
}
print '</table>';
print '</form>';
print '<br><br>';


switch ($categselect) {
	case 'product' :
		$productstatic		= new Product($db);
		// requete de sélection des produits
		$sql = "SELECT rowid, label, tobuy, tosell FROM ".MAIN_DB_PREFIX."product";
		$sql.= " WHERE 1=1";
		if ($key != "") {
			$sql.= " AND ( ref LIKE '%".$key."%'";
			$sql.= " OR	label LIKE '%".$key."%'";
			$sql.= " OR	description LIKE '%".$key."%')";
		}

		if ($statut_buy != -1)
			$sql.= " AND tobuy =".$statut_buy;

		if ($statut_sell != -1)
			$sql.= " AND tosell =".$statut_sell;

		if ($type != -1)
			$sql.= " AND fk_product_type =".$type;

		// filtrage des quantité selon astuce
		if ($qteStock != '') {
			if (strpos($qteStock, "+") > 0) {
				// mode plage
				$valueArray = explode("+", $qteStock);
				$sql.= " AND (stock  >=".$valueArray [0];
				$sql.= " AND stock  <=".$valueArray [1].")";
			} else {
				if (is_numeric($qteStock))
					$sql.=" and stock = ".$qteStock;
				else
					$sql.=" and stock ".substr($qteStock, 0, 1).substr($qteStock, 1);
			}
		}

		$tblcats = _get_all_categories(0, $categcolfilter);
		break;

	case 'societe' :
		$companystatic		= new Societe($db);
		// requete de sélection des societe
		$sql = "SELECT rowid, status FROM ".MAIN_DB_PREFIX."societe";
		$sql.= " WHERE 1=1";
		if ($key != "") {
			$sql.= " AND ( nom LIKE '%".$key."%'";
			$sql.= " OR	name_alias LIKE '%".$key."%')";
		}
		if ($statut != -1)
			$sql.= " AND status =".$statut;

		if ($client != -1)
			$sql.= " AND client=".$client;

		$sql .= " ORDER by nom";

		$tblcats = _get_all_categories(2, $categcolfilter);
		break;

	case 'fournisseur' :
		$companystatic		= new Societe($db);
		// requete de sélection des fournisseur
		$sql = "SELECT rowid, status FROM ".MAIN_DB_PREFIX."societe";
		$sql .= " WHERE fournisseur=1";
		if ($key != "") {
			$sql.= " AND ( nom LIKE '%".$key."%'";
			$sql.= " OR	name_alias LIKE '%".$key."%')";
		}
		if ($statut != -1)
			$sql.= " AND status =".$statut;

		$sql .= " ORDER by nom";

		$tblcats = _get_all_categories(1, $categcolfilter);

		break;

	case 'member' :
		require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
		$adherentstatic		= new Adherent($db);

		// requete de sélection des adherent
		$sql = "SELECT rowid, statut  FROM ".MAIN_DB_PREFIX."adherent";
		$sql.= " WHERE 1=1";
		if ($key != "") {
			$sql.= " AND ( lastname LIKE '%".$key."%'";
			$sql.= " OR	firstname LIKE '%".$key."%')";
		}
		if ($statut != -1)
			$sql.= " AND statut =".$statut;

		$tblcats = _get_all_categories(3, $categcolfilter);
		break;
	case 'socpeople' :
		$companystatic		= new Societe($db);
		$contactstatic		= new Contact($db);
		// requete de sélection des adherent
		$sql = "SELECT rowid, statut, fk_soc FROM ".MAIN_DB_PREFIX."socpeople";
		$sql.= " WHERE 1=1";
		if ($key != "") {
			$sql.= " AND ( lastname LIKE '%".$key."%'";
			$sql.= " OR	firstname LIKE '%".$key."%')";
		}
		if ($statut != -1)
			$sql.= " AND statut =".$statut;

		$tblcats = _get_all_categories(4, $categcolfilter);
		break;

	case 'user' :
		$userstatic			= new User($db);
		$sql = "SELECT  rowid, statut FROM ".MAIN_DB_PREFIX."user";
		$sql.= " WHERE 1=1";
		if ($key != "") {
			$sql.= " AND ( login LIKE '%".$key."%'";
			$sql.= " OR	lastname LIKE '%".$key."%'";
			$sql.= " OR	firstname LIKE '%".$key."%')";
		}
		if ($statut != -1)
			$sql.= " AND statut =".$statut;

		$tblcats = _get_all_categories(4, $categcolfilter);
		break;

	case 'project' :
		$projectstatic			= new Project($db);
		$sql = "SELECT  rowid, fk_statut FROM ".MAIN_DB_PREFIX."projet";
		$sql.= " WHERE 1=1";
		if ($key != "") {
			$sql.= " AND ( ref 			LIKE '%".$key."%'";
			$sql.= " OR	title 		LIKE '%".$key."%'";
			$sql.= " OR	description 	LIKE '%".$key."%')";
		}
		if ($statut != -1)
			$sql.= " AND fk_statut =".$statut;

		$tblcats = _get_all_categories(6, $categcolfilter);
		break;
}


$resqlline = $db->query($sql);
$sqlbis=$sql;
if ($resqlline) {
	$i=0;
	$num = $db->num_rows($resqlline);
	if ($action == 'chgcateg') {
		while ($i < $num) {
			$objp = $db->fetch_object($resqlline);

			// la liste des catégories présentes
			if (count($tblcats) > 0) {
				foreach ($tblcats as $categ) {
					$tablecateg=($categselect=='socpeople'?'contact':$categselect);
					if (DOL_VERSION < "3.8.0")
						$categtable=(($categselect=='societe' || $categselect=='fournisseur' ) ?'societe':$categselect);
					else
						$categtable=(($categselect=='societe' || $categselect=='fournisseur' ) ?'soc':$categselect);

					// on supprime toujours
					$sql= " DELETE FROM " . MAIN_DB_PREFIX . "categorie_" . $tablecateg;
					$sql .= " WHERE fk_categorie = ".$categ->id;
					$sql .= " AND fk_" . $categtable . " = ".$objp->rowid;
					$result = $db->query($sql);

					// on ajoute parfois
					if (GETPOST('chk-'.$categ->id."-".$objp->rowid)==1) {
						$sql= " INSERT INTO ".MAIN_DB_PREFIX."categorie_" . $tablecateg;
						$sql.= "(fk_categorie, fk_" . $categtable.") values ";
						$sql.= " ( ".$categ->id;
						$sql.= " , ".$objp->rowid;
						$sql.= ")";
						$result = $db->query($sql);
						$checked=' checked ';
					}
				}
			}
			// mise à jours des statut
			switch ($categselect) {
				case 'product' :
					// si on a changé quelque chose on met à jour sinon on ne fait rien
					if ($objp->tosell != GETPOST('statut_'.$objp->rowid)
					||	$objp->tobuy  != GETPOST('statut_buy_'.$objp->rowid)) {
						$sql= " UPDATE ".MAIN_DB_PREFIX."product";
						$sql.= " SET tosell =".GETPOST('statut_'.$objp->rowid);
						$sql.= " ,   tobuy =".GETPOST('statut_buy_'.$objp->rowid);
						$sql.= " WHERE rowid=".$objp->rowid;
						$result = $db->query($sql);
						$productstatic->fetch($objp->rowid);
					}
					break;
				case 'societe' :
				case 'fournisseur' :
					// si on a changé quelque chose on met à jour sinon on ne fait rien
					if ($objp->statut != GETPOST('statut_'.$objp->rowid)) {
						$sql = " UPDATE ".MAIN_DB_PREFIX."societe";
						$sql.= " SET status =".GETPOST('statut_'.$objp->rowid);
						$sql.= " WHERE rowid=".$objp->rowid;
						$result = $db->query($sql);
						$companystatic->fetch($objp->rowid);
					}
					break;
				case 'member' :
					// si on a changé quelque chose on met à jour sinon on ne fait rien
					if ($objp->statut != GETPOST('statut_'.$objp->rowid)) {
						$sql = " UPDATE ".MAIN_DB_PREFIX."adherent";
						$sql.= " SET statut =".GETPOST('statut_'.$objp->rowid);
						$sql.= " WHERE rowid=".$objp->rowid;
						$result = $db->query($sql);
						$adherentstatic->fetch($objp->rowid);
					}
					break;
				case 'socpeople' :
					// si on a changé quelque chose on met à jour sinon on ne fait rien
					if ($objp->statut != GETPOST('statut_'.$objp->rowid)) {
						$sql = " UPDATE ".MAIN_DB_PREFIX."socpeople";
						$sql.= " SET statut =".GETPOST('statut_'.$objp->rowid);
						$sql.= " WHERE rowid=".$objp->rowid;
						$result = $db->query($sql);
						$contactstatic->fetch($objp->rowid);
					}
					break;
				case 'user' :
					// si on a changé quelque chose on met à jour sinon on ne fait rien
					if ($objp->statut != GETPOST('statut_'.$objp->rowid)) {
						$sql = " UPDATE ".MAIN_DB_PREFIX."user";
						$sql.= " SET statut =".GETPOST('statut_'.$objp->rowid);
						$sql.= " WHERE rowid=".$objp->rowid;
						$result = $db->query($sql);
						$user->fetch($objp->rowid);
					}
					break;
			}
			$i++;
		}
	}

	print '<br><form action="" method="POST" name="LinkedOrder">';

	print '<input type=hidden name=categselect value="'.$categselect.'">';
	print '<input type=hidden name=categcolfilter value="'.$categcolfilter.'">';

	print '<input type=hidden name=key value="'.$key.'">';
	print '<input type=hidden name=companyfilterkey value="'.$companyfilterkey.'">';
	print '<input type=hidden name=statut_buy value="'.$statut_buy.'">';
	print '<input type=hidden name=statut_sell value="'.$statut_sell.'">';
	print '<input type=hidden name=type value="'.$type.'">';
	print '<input type=hidden name=morphy value="'.$morphy.'">';
	print '<input type=hidden name=statut value="'.$statut.'">';
	print '<input type=hidden name=client value="'.$client.'">';

	print '<input type=hidden name=action value="chgcateg">';
	print '<input type=hidden name=selectedcateg value="'.$categselect.'">';
	print '<table class="noborder">';
	print '<tr class="liste_titre">';
	print '<th align="left" width=20%>' . $langs->trans("Ref") . '</th>';
	if ($conf->global->PORTOFOLIO_ENABLE_SCORING =="1" && ($categselect=='societe' || $categselect=='member'))
		print '<th align="left" width=20%>' . $langs->trans("Scoring"). '</th>';
	print '<th align="left" width=20%>' . $langs->trans("Status") . '</th>';
	if (count($tblcats) > 0) {
		foreach ($tblcats as $categ) {
			print '<th align=center alt="'.$categ->description.'">';
			print '<input type=checkbox class="dochkall" id="chkid'.$categ->id.'">';
			print '&nbsp;'.$categ->label;
			print '</th>';
		}
	}
	else
		print '<th ></th>';

	print '</tr>';
	$var=true;
	$resqldata = $db->query($sqlbis);
	// si c'est le premier accès et plus de 200 lignes, on n'affiche que les 200 premières lignes
	if ($showall=="")
		$numshow = min(array($num, 200));
	else
		$numshow = $num; // après filtrage on ouvre toute les vannes
	$i = 0;
	while ($i < $numshow) {
		$var=!$var;
		$objp = $db->fetch_object($resqldata);
		print '<tr '.$bc[$var].'>';
		print '<td >';
		switch ($categselect) {
			case 'product' :
				$productstatic->fetch($objp->rowid);
				print $productstatic->getNomUrl(2).' - '.$productstatic->label;
				print " (".$productstatic->stock_reel.")";
				break;
			case 'societe' :
				$companystatic->fetch($objp->rowid);
				print $companystatic->getNomUrl(3);
				break;
			case 'fournisseur' :
				$companystatic->fetch($objp->rowid);
				print $companystatic->getNomUrl(3);
				break;
			case 'member' :
				$adherentstatic->fetch($objp->rowid);
				print $adherentstatic->getNomUrl(3);
				break;
			case 'socpeople' :
				$contactstatic->fetch($objp->rowid);
				print $contactstatic->getNomUrl(2);
				if ($objp->fk_soc >0 ) {
					$companystatic->fetch($objp->fk_soc);
					print " - ".$companystatic->getNomUrl(3);
				}
				break;
			case 'user' :
				$userstatic->fetch($objp->rowid);
				print $userstatic->getNomUrl(3);
				break;
			case 'project' :
				$projectstatic->fetch($objp->rowid);
				print $projectstatic->getNomUrl(3);
				break;

		}
		print '</td>';
		if ($conf->global->PORTOFOLIO_ENABLE_SCORING =="1"
			&& ($categselect=='societe' || $categselect=='member')) {
			// ici les infos de Scoring
			print '<td align=right>';
			switch ($categselect) {
				case 'societe' :
					$outstandingBills = $companystatic->get_OutstandingBill();
					$outstandinglimit = $companystatic->outstanding_limit;
					$warn = '';
					if ($outstandinglimit != "" ) {
						if ($outstandinglimit < $outstandingBills)
							$warn = img_warning($langs->trans("OutstandingBillReached"));
					}
					print price($outstandingBills).($outstandinglimit?" / ".price($outstandinglimit):" ").$warn;
					break;
				case 'member':
					print '<table width=100% border=0><tr>';
					print '<td align=left width=80px>'.$adherentstatic->getLibStatut(2).'</td>';
					if ($adherentstatic->datefin) {
						print '<td align="center" class="nowrap">';
						print dol_print_date($adherentstatic->datefin, 'day');
						if ($adherentstatic->hasDelay())
							print img_warning($langs->trans("SubscriptionLate"));
					} else {
						//var_dump($adherentstatic);
						print '<td align="left" class="nowrap">';
						if ($adherentstatic->need_subscription == 1) {
							print $langs->trans("SubscriptionNotReceived");
							if ($adherentstatic->statut > 0) print " ".img_warning();
						}
						else
							print '&nbsp;';
					}
					print '</td></tr></table>';
					break;
			}
			print '</td>';
		}
		// on ajoute les statuts modifiables
		print '<td >';
		switch ($categselect) {
			case 'product' :
				print $form->selectarray(
								'statut_'.$objp->rowid,
								array( 1 => $langs->trans("OnSell"),  0 => $langs->trans("NotOnSell")),
								$productstatic->status
				);
				print $form->selectarray(
								'statut_buy_'.$objp->rowid,
								array( 1 => $langs->trans("ProductStatusOnBuy"),  0 => $langs->trans("ProductStatusNotOnBuy")),
								$productstatic->status_buy
				);
				break;
			case 'societe' :
			case 'fournisseur' :
				print $form->selectarray(
								'statut_'.$objp->rowid, array( 0=>$langs->trans('ActivityCeased'), 1=>$langs->trans('InActivity')),
								$objp->status
				);
				break;
			case 'member' :
				print $form->selectarray(
								'statut_'.$objp->rowid,
								array(
									-1=>$langs->trans('MemberStatusDraftShort'),
									1=>$langs->trans('MemberStatusActiveShort'),
									0=>$langs->trans('MemberStatusResiliatedShort')),
								$objp->status
				);
				break;
			case 'socpeople' :
				print $form->selectarray(
								'statut_'.$objp->rowid,
								array( 0=>$langs->trans('Disabled'), 1=>$langs->trans('Enabled')),
								$objp->statut
				);
				break;
			case 'user' :
				print $form->selectarray(
								'statut_'.$objp->rowid,
								array(0=>$langs->trans('Disabled'), 1=>$langs->trans('Enabled')),
								$objp->statut
				);
				break;
			case 'project' :
				print $form->selectarray(
								'statut_'.$objp->rowid,
								array( 0=>$langs->trans('Draft'), 1=>$langs->trans('Enabled'),
								2=>$langs->trans('Close')),
								$objp->fk_status
				);
				break;
		}
		print '</td>';

		// la liste des catégories présentes
		if (count($tblcats) > 0) {
			foreach ($tblcats as $categ) {
				$tablecateg=($categselect=='socpeople'?'contact':$categselect);
				if (DOL_VERSION < "3.8.0")
					$categtable=(($categselect=='societe' || $categselect=='fournisseur' ) ?'societe':$categselect);
				else
					$categtable=(($categselect=='societe' || $categselect=='fournisseur' ) ?'soc':$categselect);
				$colorcateg="";
				if ($categ->color)
					$colorcateg = " bgcolor='#".$categ->color."' ";
				print '<td align=center '.$colorcateg.'>';

				$checked="";
				$sql = "SELECT fk_categorie";
				$sql .= " FROM " . MAIN_DB_PREFIX . "categorie_" . $tablecateg;
				$sql .= " WHERE fk_categorie = ".$categ->id;
				$sql .= " AND fk_" . $categtable . " = ".$objp->rowid;
//				print $sql;
				$rescateg = $db->query($sql);
				if ($db->num_rows($rescateg) == 1)
					$checked=' checked ';

				print '<input type=checkbox title="'.$categ->label.'" class="chkid'.$categ->id.'"';
				print ' value=1 '.$checked.' name=chk-'.$categ->id."-".$objp->rowid.'>';
				print '</td>';
			}
		}
		else
			print '<td></td>';
		print '</tr>';
		$i++;
	}
	print '<tr><td colspan=3 align=center >';
	if ($numshow == $num ) {
		if ($user->rights->portofolio->setup)
			print '<input type=submit value="'. $langs->trans("ApplyChange").'">';
	} else
		print $langs->trans("TooMuchDataPleaseFilterBeforeChange");

	print '</td>';
	print '<td '.(count($tblcats) >0?'colspan="'.count($tblcats).'"':"").'">';
	print '</td></tr>';
	print '</table>';
	print '</form>';
}


function _get_all_categories($type=null, $parent=-1)
{
	global $db;

	$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."categorie";
	$sql.= " WHERE entity IN (".getEntity('category', 1).")";
	if (! is_null($type))
		$sql.= " AND type = ".$type;
	if ($parent > 0 ) {
		$sql .= " AND (rowid = ".$parent ;
		$sql .= " OR fk_parent = ".$parent ;
		$sql .= ")";
	}

	$res = $db->query($sql);
	if ($res) {
		$cats = array ();
		while ($rec = $db->fetch_array($res)) {
			$cat = new Categorie($db);
			$cat->fetch($rec['rowid']);
			$cats[$rec['rowid']] = $cat;
		}
		return $cats;
	} else {
		dol_print_error($db);
		return -1;
	}
}
llxFooter();
$db->close();
?>
<script>
$(document).ready(function() {
	$('#showreflist').click(function() {  //on click
		$('#reflist').toggle();
	});
	$('.dochkall').click(function(event) {
		if (this.checked) {
			// check select status
			$('.'+ event.target.id).each(function() { //loop through each checkbox
				this.checked = true;
			});
		}else {
			$('.'+ event.target.id).each(function() { //loop through each checkbox
				this.checked = false;
			});
		}
	});
});
</script>