<?php
/* Copyright (C) 2013-2017		Charlie Benke	<charlie@patas-monkey.com>
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
 *	\file	   htdocs/mylist/list.php
 *	\ingroup	Liste
 *	\brief	  Page liste des listes
 */

$res=@include("../main.inc.php");					// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../main.inc.php");		// For "custom" directory

dol_include_once('/mylist/class/mylist.class.php');

$langs->load('mylist@mylist');

if (!$user->rights->mylist->lire) accessforbidden();

$myliststatic = new Mylist($db);
$lists = $myliststatic->get_all_mylist();

/*
 *	Actions
 */
if ($action =="refresh") {
	require_once DOL_DOCUMENT_ROOT.'/core/class/menubase.class.php';

	foreach ($lists as $listinfo) {
		if ($listinfo['active'] == 1) {
			// gestion des menus
			// on commence par récupérer l'id du menu à supprimer
			$sql="SELECT m.rowid, l.titlemenu, l.mainmenu, l.leftmenu";
			$sql .= " FROM ".MAIN_DB_PREFIX."menu as m, ".MAIN_DB_PREFIX."mylist as l";
			$sql .= " WHERE l.rowid = '".$listinfo['rowid']."'";
			$sql .= " and l.titlemenu=m.titre";
			$sql .= " and m.module='mylist'";
			$sql .= " and l.mainmenu=m.fk_mainmenu";
			$sql .= " and l.leftmenu=m.fk_leftmenu";
			$sql .= " and m.entity = ".$conf->entity;

			dol_syslog(get_class($this)."::update sql=".$sql);
			if ($db->query($sql)) {
				if ($db->num_rows($resql) > 0) {
					$res = $db->fetch_array($resql);
					$sql="DELETE FROM ".MAIN_DB_PREFIX."menu WHERE rowid=".$res['rowid'];
					$db->query($sql);
				}
			}
			$posmenu = $myliststatic->getposmenu($res['titlemenu'], $res['mainmenu'], $res['leftmenu']);

			$dt = "";
			// si on affiche le mode datatable ou pas
			if ($res['datatable'] == 1) $dt = "dt";

			// on ajoute le menu
			$menu = new Menubase($db);
			if ($conf->global->MAIN_MENU_STANDARD == 'auguria_menu.php')
				$menu->menu_handler='auguria';
			else
				$menu->menu_handler='all';

			$menu->module='mylist';
			$menu->type='left';
			$myliststatic->mainmenu = $res['mainmenu'];
			$menu->fk_menu=$myliststatic->getidmenu($menu->menu_handler);
			$menu->fk_mainmenu = $res['mainmenu'];
			$menu->fk_leftmenu = $res['leftmenu'];
			$menu->titre = $res['titlemenu'];
			$menu->url = '/mylist/mylist'.$dt.'.php?rowid='.$res['rowid'];
			$menu->langs=$res['langs'];
			$menu->position=$posmenu;
			$menu->perms=$res['perms'];
			$menu->target="";
			$menu->user=2;
			$menu->enabled=1;
			$result=$menu->create($user);

			/// GESTION DES ONGLETS
			if ($listinfo['elementtab'] != "") {
				// on supprime l'onglet si il est present ou pas
				$sql="DELETE FROM ".MAIN_DB_PREFIX."const";
				$sql.=" WHERE name ='".$db->encrypt('MAIN_MODULE_MYLIST_TABS_'.$listinfo['rowid'], 1)."'";
				$db->query($sql);

				if ($listinfo['elementtab']=='commande')
						$elementtab = 'order';
					else
						$elementtab = $listinfo['element'];

				// on paramètre selon le type d'onglet les choix possibles
				switch($custinfo['datatable']) {
					case 1 :
						$tabinfo = $elementtab.':+mylist_'.$listinfo['rowid'].':'.$listinfo['label'];
						$tabinfo.= ':"":@mylist:/mylist/tabs/mylistdt.php?rowid='.$listinfo['rowid'].'&id=__ID__';
						break;
					case 0 :
						$tabinfo = $elementtab.':+mylist_'.$listinfo['rowid'].':'.$listinfo['label'];
						$tabinfo.= ':"":@mylist:/mylist/tabs/mylist.php?rowid='.$listinfo['rowid'].'&id=__ID__';
						break;
				}
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."const ";
				$sql.= " ( name, type, value, note, visible, entity)";
				$sql.= " VALUES (";
				$sql.= $db->encrypt('MAIN_MODULE_MYLIST_TABS_'.$listinfo['rowid'], 1);
				$sql.= ", 'chaine'";
				$sql.= ", ".$db->encrypt($tabinfo, 1);
				$sql.= ", null";
				$sql.= ", '0'";
				$sql.= ", ".$conf->entity;
				$sql.= ")";

				$db->query($sql);
			}
		}
	}
}



llxHeader("", "", $langs->trans("Mylist"));

print_fiche_titre($langs->trans("MylistSetting"));

if ($lists != -1) {
	if (count($lists) > 0) {
		print '<table id="listtable" class="noborder" width="100%">';
		print '<thead>';
		print '<tr class="liste_titre">';
		print '<th width=200px>'.$langs->trans("label").'</th>';
		print '<th width=200px>'.$langs->trans("menutitle").'</th>';
		print '<th width=120px>'.$langs->trans("mainmenu").'</th>';
		print '<th width=120px>'.$langs->trans("leftmenu").'</th>';
		print '<th>'.$langs->trans("perms").'</th>';
		print '<th width=120px>'.$langs->trans("author").'</th>';
		print '<th width=50px>'.$langs->trans("active").'</th>';
		print '<th width=70px>'.$langs->trans("NbFields").'</th>';
		print '</tr>';
		print '</thead>';
		print '<tbody>';
		$var=true;
		foreach ($lists as $list) {
			$var = ! $var;

			print "<tr ".$bc[$var].">\n";
			print "\t<td><a href='card.php?rowid=".$list['rowid']."'>".$list['label']."</a></td>\n";
			print "<td align='left'>".$list['titlemenu']."</td>\n";
			print "<td align='left'>".$list['mainmenu']."</td>\n";
			print "<td align='left'>".$list['leftmenu']."</td>\n";
			print "<td align='left'>".$list['perms']."</td>\n";
			print "<td align='right'>".$list['author']."</td>\n";
			print "<td align='right'>".yn($list['active'])."</td>\n";
			print "<td align='right'>".$list['nbFieldsUsable']."</td>\n";
			print "</tr>\n";
		}
		print '</tbody>';
		print "</table>";
	} else
		print "<br><h3>".$langs->trans("ImportExportFolderInfo")."</h3><br><br>";
}
else
	dol_print_error();

/*
 * Boutons actions
 */
print '<br>';
print '<div class="tabsAction">';
if ($user->rights->mylist->creer) {
	print '<a class="butAction" href="card.php?action=create">'.$langs->trans('NewList').'</a>';
	print '<a class="butAction" href="list.php?action=refresh">'.$langs->trans("RefreshMyListList").'</a>';
}

print "</div>";


if (!empty($conf->global->MAIN_USE_JQUERY_DATATABLES)) {
	print "\n";
	print '<script type="text/javascript">'."\n";
	print 'jQuery(document).ready(function() {'."\n";
	print 'jQuery("#listtable").dataTable( {'."\n";
	print '"sDom": \'ilCtpr\','."\n";
	print '"oColVis": {"buttonText": "'.$langs->trans('showhidecols').'" },'."\n";
	print '"buttons" : [ "colvis" ],';
	print '"language": { buttons: { "colvis": \''.$langs->trans('showhidecols').'\'} },';
	print '"bPaginate": true,'."\n";
	print '"bFilter": false,'."\n";
	print '"sPaginationType": "full_numbers",'."\n";
	print '"bJQueryUI": false,'."\n";
	print '"oLanguage": {"sUrl": "'.$langs->trans('datatabledict').'" },'."\n";
	print '"iDisplayLength": 25,'."\n";
	print '"aLengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],'."\n";
	print '"bSort": true'."\n";
	print '} );'."\n";
	print '});'."\n";
	print '</script>'."\n";
}
llxFooter();
$db->close();