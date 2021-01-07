<?php
/* Copyright (C) 2014-2017		charlie Benke	<charlie@patas-monkey.com>
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
 * 	\file	   htdocs/customlink/class/actions_customlink.class.php
 * 	\ingroup	customlink
 * 	\brief	  Fichier de la classe des actions/hooks des customlink
 */

class ActionsCustomlink // extends CommonObject
{

	/** Overloading the doActions function : replacing the parent's function with the one below
	 *  @param	  parameters  meta datas of the hook (context, etc...)
	 *  @param	  object			 the object you want to process
	 *  @param	  action			 current action (if set). Generally create or edit or null
	 *  @return	   void
	 */
	function showLinkedObjectBlock($parameters, $object, $action)
	{
		global $conf, $langs;

		$langs->load("customlink@customlink");
		dol_include_once("/customlink/core/lib/customlink.lib.php");

		print "<br>";
		print_titre($langs->trans('AddNewTag'));
		print '<form action="'.dol_buildpath("/customlink", 1).'/addtag.php" method="POST">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

		// If link is http
		if (empty($_SERVER["HTTPS"]) || $_SERVER["HTTPS"] != 'on')
			$szhttp="http";
		else
			$szhttp="https";

		print '<input type="hidden" name="redirect" value="'.$szhttp.'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'">';

		print '<input type="hidden" name="type_source" value="'.$object->element.'">';
		print '<input type="hidden" name="fk_source" value="';
		print ($object->rowid?$id=$object->rowid:$object->id).'">';
		print "<table class='noborder allwidth'>";
		print "<tr class='liste_titre'>";
		print "<td>".$langs->trans("ElementTags")."</td>";
		print "<td align=right>";
		print "<input type=submit name=join value=".$langs->trans("Add")."></td>";
		print "</tr>";
		print '<tr><td colspan=2>';
		// pr�voir plus tard une liste de tag s�lectionnable
		print '<input type="text" name=tag value="">';
		print '</td></tr>';
		print "</table>";
		print "</form>";
		print_tag_list($object->element, ($object->rowid?$id=$object->rowid:$object->id));

		print "<br>";
		print_titre($langs->trans('AddNewLink'));
		print '<form action="'.dol_buildpath("/customlink", 1).'/addlink.php" method="POST">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="redirect" value="';
		print $szhttp.'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'">';
		print '<input type="hidden" name="type_source" value="'.$object->element.'">';
		print '<input type="hidden" name="fk_source" value="';
		print ($object->rowid?$id=$object->rowid:$object->id).'">';
		print "<table class='noborder allwidth'>";
		print "<tr class='liste_titre'>";
		print "<td>".$langs->trans("Element")."</td>";
		print "<td>".$langs->trans("Ref")."</td>";
		print "<td align=right>"."<input type=submit name=join value=";
		print $langs->trans("JoinElement")."></td>";
		print "</tr>";
		print '<tr><td>';
		select_element_type("", 'type_target', 0, 1);
		print '</td>';
		print '<td >';
		print '<input type="text" name=ref_target value="">';
		print '</td></tr>';
		print "</table>";
		print "</form>";

		$num = count($object->linkedObjects);

		//var_dump($object->linkedObjects);
		if (DOL_VERSION < "3.8.0") {
			foreach ($object->linkedObjects as $objecttype => $objects) {
				$tplpath = $element = $subelement = $objecttype;

				if (preg_match('/^([^_]+)_([^_]+)/i', $objecttype, $regs)) {
					$element = $regs[1];
					$subelement = $regs[2];
					$tplpath = $element.'/'.$subelement;
				}

				// To work with non standard path
				if ($objecttype == 'facture') {
					$tplpath = 'compta/'.$element;
					if (empty($conf->facture->enabled)) continue;	// Do not show if module disabled
				} else if ($objecttype == 'propal') {
					$tplpath = 'comm/'.$element;
					if (empty($conf->propal->enabled)) continue;	// Do not show if module disabled
				} else if ($objecttype == 'shipping') {
					$tplpath = 'expedition';
					if (empty($conf->expedition->enabled)) continue;	// Do not show if module disabled
				} else if ($objecttype == 'delivery') {
					$tplpath = 'livraison';
				} else if ($objecttype == 'invoice_supplier') {
					$tplpath = 'fourn/facture';
				} else if ($objecttype == 'order_supplier') {
					$tplpath = 'fourn/commande';
				}

				global $linkedObjectBlock;
				$linkedObjectBlock = $objects;

				// Output template part (modules that overwrite templates must declare this into descriptor)
				$dirtpls=array_merge($conf->modules_parts['tpl'], array('/'.$tplpath.'/tpl'));
				foreach ($dirtpls as $reldir) {
					$res=@include dol_buildpath($reldir.'/linkedobjectblock.tpl.php');
					if ($res) break;
				}
			}
		}
		$this->resprints=$num;
		return 0;
	}

		/** Overloading the doActions function : replacing the parent's function with the one below
	 *  @param	  parameters  meta datas of the hook (context, etc...)
	 *  @param	  object			 the object you want to process
	 *  @param	  action			 current action (if set). Generally create or edit or null
	 *  @return	   void
	 */
	// function printSearchForm($parameters, $object, $action)
	// {
	// 	global $conf, $langs;
	// 	if (DOL_VERSION < "3.9.1") {
	// 		$langs->load("customlink@customlink");
	// 		$title = img_object('', 'customlink@customlink').' '.$langs->trans("ElementTags");
	// 		$ret='';
	// 		$ret.='<div class="menu_titre">';
	// 		$ret.='<a class="vsmenu" href="'.dol_buildpath('/customlink/listetag.php', 1).'">';
	// 		$ret.=$title.'</a><br>';
	// 		$ret.='</div>';
	// 		$ret.='<form action="'.dol_buildpath('/customlink/listetag.php', 1).'" method="post">';
	// 		$ret.='<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	// 		$ret.='<input type="text" class="flat" ';
	// 		if (! empty($conf->global->MAIN_HTML5_PLACEHOLDER))
	// 			$ret.=' placeholder="'.$langs->trans("SearchOf").''.strip_tags($title).'"';
	// 		else
	// 			$ret.=' title="'.$langs->trans("SearchOf").''.strip_tags($title).'"';
	// 		$ret.=' name="tag" size="10" />&nbsp;';
	// 		$ret.='<input type="submit" class="button" value="'.$langs->trans("Go").'"';
	// 		$ret.=' style="padding-top: 4px; padding-bottom: 4px; padding-left: 6px; padding-right: 6px" >';
	// 		$ret.="</form>\n";
	// 		$this->resprints=$ret;
	// 	}
	// 	return 0;
	// }

	function addSearchEntry ($parameters, $object, $action)
	{
		global $confg, $langs;
		$resArray=array();
		$resArray['searchintocustomlink']=array(
						'text'=>img_picto('', 'object_customlink@customlink').' '.$langs->trans("CustomLink", GETPOST('q')),
						'url'=>dol_buildpath('/customlink/listetag.php?sall='.urlencode(GETPOST('q')), 1)
		);
		$this->results = $resArray;
		return 0;
	}
}
