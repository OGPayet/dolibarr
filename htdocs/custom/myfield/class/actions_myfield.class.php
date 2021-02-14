<?php
/* Copyright (C) 2015-2017		Charlie Benke	<charlie@patas-monkey.com>
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
 * 	\file	   htdocs/myfield/class/actions_myfield.class.php
 * 	\ingroup	myfield
 * 	\brief	  Fichier de la classe des actions/hooks de myfield
 */

class ActionsMyfield // extends CommonObject
{

	/** Overloading the formObjectOptions function
	 *  @param	  parameters  meta datas of the hook (context, etc...)
	 *  @param	  object			 the object you want to process
	 *  @param	  action			 current action (if set).
	 *  @return	   void
	 */

// sur les fiches en cr�ation (sans tabs) on appel quand m�me le bon trigger
function formObjectOptions($parameters, $object, $action)
{
//	global $conf, $langs, $db, $user;
	if ($action == 'create'  )
		$this->printTabsHead($parameters, $object, $action);
	return 0;
}

function doActions($parameters, $object, $action)
{
	global $conf, $langs, $db, $user;
	global $arrayfields, $hookmanager;

	dol_include_once('/myfield/class/myfield.class.php');
	$myField = new Myfield($db);

	// gestion des champs de liste
	$listfield = $myField->get_all_myfield($parameters['context'], 3);

	foreach ($listfield  as $currfield) {
		if (strrpos($parameters['context'], $currfield['context']) !== false ) {
			$user_specials_rights = $myField->getUserSpecialsRights($currfield['rowid'], $user, $currfield['querydisplay']);
			$label = $currfield['label'];
			$keyvalue= array_search ($label, array_column($arrayfields, 'label'));
			$arraykeys=array_keys($arrayfields);
			// si autoris� en mode lecture
			if ($user_specials_rights['read']) {
				if ($keyvalue !== false) {
					if ($currfield['active'] == 2) {
						unset($arrayfields[$arraykeys[$keyvalue]]);
					}
					if ($currfield['replacement']) {
						$arrayfields[$arraykeys[$keyvalue]]['label'] =  $currfield['replacement'];
					}
				}
			} else {
				// si pas de droit en lecture on vire l'affichage du champs
				unset($arrayfields[$arraykeys[$keyvalue]]);
			}
		}
	}
}

// sur toute les fiches / on g�re la mise � jour des nom
function printCommonFooter($parameters, $object, $action)
{
	global  $langs, $db, $user, $conf;
	// check if db is not close -> bad writing of page
	if ($db->connected) {

		if (property_exists($conf->global, 'MYFIELD_INPUT_BACKGROUND') && $conf->global->MYFIELD_INPUT_BACKGROUND) {
			print "<script>\n";
			print "jQuery(document).ready(function () {\n";

			print "$(':input').css({'background-color': 'rgb(".$conf->global->MYFIELD_INPUT_BACKGROUND.")'});";
			print "$('.select2').css({'border': 'solid 2px rgb(".$conf->global->MYFIELD_INPUT_BACKGROUND.")'});";

			print "})\n;";
			print "</script>\n";
		}

		if (property_exists($conf->global, 'MYFIELD_ENABLE_SMALL_BUTTON') && $conf->global->MYFIELD_ENABLE_SMALL_BUTTON =="1")
			print '<script src="'.dol_buildpath('/myfield/js/jquery.chgbutton.js', 1).'"></script>';

		dol_include_once('/myfield/class/myfield.class.php');
		$myField = new Myfield($db);

		// uniquement les fields de type champs
		$listfield = $myField->get_all_myfield($parameters['context'], 0);

		$bvisibility=false;
		print '<script src="'.dol_buildpath('/myfield/js/jquery.maskedinput.min.js', 1).'"></script>';
		print "<script>\n";
		print "jQuery(document).ready(function () {\n";
		foreach ($listfield  as $currfield) {
			$user_specials_rights = $myField->getUserSpecialsRights($currfield['rowid'], $user, $currfield['querydisplay']);
	//		print "/// user read=".$user_specials_rights['read']."\n";
	//		print "/// user write=".$user_specials_rights['write']."\n";
			// on m�morise la ligne du tableau et les colonnes de celui-ci
			$label = $currfield['label'];
			$namevalue=getNameValue($label);
			print $namevalue.'=$(\'td:contains("'.str_replace("'", "\'", $label).'")\').filter(function () {return ($(this).text() == "'.$label.'")}).parent();'."\n";

			if ($user_specials_rights['read']) {
			//var_dump($currfield);
				// D�placement
				if ($currfield['movefield'] < 0) {
					for ($i=0; $i < (-1 * $currfield['movefield']); $i++)
						print $namevalue.".next().after(".$namevalue.");"."\n";
				} elseif ($currfield['movefield'] > 0) {
					for ($i=0; $i <  $currfield['movefield']; $i++)
						print $namevalue.".parent().prev().before(".$namevalue.".parent());"."\n";
				}

				print "/// user can read\n";
				if ($currfield['replacement']) {
					print "/// remplacement feature\n";
					print "textchange=".$namevalue.'.find("td").eq(0).html();'."\n";
					print 'if (textchange)';
					print '{'."\n";
						print 'textchange=textchange.replace("'.$label.'","'.$currfield['replacement'].'");'."\n";;
						print $namevalue.'.find("td").eq(0).html(textchange);'."\n";
						print "textchange=".$namevalue.'.find("td").eq(2).html();'."\n";
						print 'if (textchange)';
						print '{'."\n";
							print 'textchange=textchange.replace("'.$label.'","'.$currfield['replacement'].'");'."\n";;
							print $namevalue.'.find("td").eq(2).html(textchange);'."\n";
						print '}'."\n";
					print "}";
					$label = $currfield['replacement'];
				}

				if ($currfield['active'] == 2) { // invisibility mode with reappear feature
					// visibility hidden

					if (strrpos($parameters['context'], "thirdpartycard") == 0) {
						print $namevalue.'.css("visibility", "hidden");'."\n";
						print $namevalue.'.find("td").attr("class", "fieldvisible");'."\n";
					} else {
						print 'if ( '.$namevalue.'.find("td").eq(0).text() == "'.$label.'")'."\n";
						print "{\n";
						print $namevalue.'.find("td").eq(1).css("visibility", "hidden");'."\n";
						print $namevalue.'.find("td").eq(0).css("visibility", "hidden");'."\n";
						print $namevalue.'.find("td").eq(1).attr("class", "fieldvisible");'."\n";
						print $namevalue.'.find("td").eq(0).attr("class", "fieldvisible");'."\n";
						print "}else{\n";
						print $namevalue.'.find("td").eq(3).css("visibility", "hidden");'."\n";
						print $namevalue.'.find("td").eq(2).css("visibility", "hidden");'."\n";
						print $namevalue.'.find("td").eq(3).attr("class", "fieldvisible");'."\n";
						print $namevalue.'.find("td").eq(2).attr("class", "fieldvisible");'."\n";

						print "}\n";
					}
					// if click on the empty area : they reappear
					$bvisibility=true;
				}
				if ($currfield['color']) {	// si la premi�re colonne contient le libell�
					if (strrpos($parameters['context'], "thirdpartycard") == 0)
						print $namevalue.'.attr("bgcolor", "'.$currfield['color'].'");'."\n";
					else {
						// si on est sur le tiers qui est merdique
						print 'if ( '.$namevalue.'.find("td").eq(0).text() == "'.$label.'")'."\n";
						print "{\n";
						print $namevalue.'.find("td").eq(0).attr("bgcolor", "'.$currfield['color'].'");'."\n";
						print $namevalue.'.find("td").eq(1).attr("bgcolor", "'.$currfield['color'].'");'."\n";
						print "}else{\n";
						print $namevalue.'.find("td").eq(2).attr("bgcolor", "'.$currfield['color'].'");'."\n";
						print $namevalue.'.find("td").eq(3).attr("bgcolor", "'.$currfield['color'].'");'."\n";
						print "}\n";
					}
				}

				// on ajoute un test d'initialisation (meme si vide)
				if ($currfield['initvalue'] != '' ) {
					print 'if ('.$namevalue.'.find("input").val() == "")'."\n";
					print $namevalue.'.find("input").val("'.$currfield['initvalue'].'");'."\n";
				}
				if ($currfield['sizefield'] > 0) // change size of input field
					print $namevalue.'.find("input").attr("size", "'.$currfield['sizefield'].'");'."\n";
				// on d�sactive la zone de saisie si on y a pas l'acc�s
				if ($user_specials_rights['write'] == 0) {
					print "// not read"."\n";
					print $namevalue.'.find("input").attr("disabled", "disabled");'."\n";
					print $namevalue.'.find("select").attr("disabled", "disabled");'."\n";
				} else {
					// si la zone n'est pas d�sactiv� et quelle est obligatoire
					if ($currfield['compulsory'] == 1) {
						print $namevalue.'.find("input").attr("required", "required");'."\n";
						print $namevalue.'.find("select").attr("required", "required");'."\n";
					}
					// mise en forme
					if ($currfield['formatfield']) {
						if ($currfield['formatfield'] == "UPPERCASE")
							print $namevalue.'.find("input").keyup(function() {		$(this).val($(this).val().toUpperCase());	});'."\n";
						elseif ($currfield['formatfield'] == "LOWERCASE")
							print $namevalue.'.find("input").keyup(function() {		$(this).val($(this).val().toLowerCase());	});'."\n";
						else
							print $namevalue.'.find("input").mask("'.$currfield['formatfield'].'")'."\n";
					}
				}
				// le remove en dernier
				if ($currfield['active'] == 1) {
					if (strrpos($parameters['context'], "thirdpartycard") == 0)
						print $namevalue.'.remove();'."\n";
					else
					{
						print 'if ( '.$namevalue.'.find("td").eq(0).text() == "'.$label.'")'."\n";
						print "{\n";
						print $namevalue.'.find("td").eq(1).remove();'."\n";
						print $namevalue.'.find("td").eq(0).remove();'."\n";
						print "}else{\n";
						print $namevalue.'.find("td").eq(3).remove();'."\n";
						print $namevalue.'.find("td").eq(2).remove();'."\n";
						print "}\n";
					}
				}
			} else {
				print "/// user not read\n";
				if (strrpos($parameters['context'], "thirdpartycard") == 0)
					print $namevalue.'.css("display", "none");'."\n";
				else {
					print 'if ( '.$namevalue.'.find("td").eq(0).text() == "'.$label.'")'."\n";
					print "{\n";
					print $namevalue.'.find("td").eq(1).css("display","none");'."\n";
					print $namevalue.'.find("td").eq(0).css("display","none");'."\n";
					print "}else{\n";
					print $namevalue.'.find("td").eq(3).css("display","none");'."\n";
					print $namevalue.'.find("td").eq(2).css("display","none");'."\n";
					print "}\n";
				}
			}
		}

		// menus

		// menu principal
		$listfield = $myField->get_all_myfield('tmenu', 2);
		foreach ($listfield  as $currfield) {
			$user_specials_rights = $myField->getUserSpecialsRights($currfield['rowid'], $user, $currfield['querydisplay']);
			$label = $currfield['label'];
			$namevalue=getNameValue($label);
			print $namevalue.'=$(\'.mainmenuaspan:contains("'.str_replace("'", "\'", $label).'")\');'."\n";
			print $namevalue.'='.$namevalue.'.filter(function () {return ($(this).text()== "'.str_replace("'", "\'", $label).'")});';

			// D�placement
			if ($currfield['movefield'] < 0) {
				for ($i=0; $i < (-1 * $currfield['movefield']); $i++)
					print $namevalue.".parent().parent().parent().prev().before(".$namevalue.".parent().parent().parent());"."\n";
			} elseif ($currfield['movefield'] > 0) {
				for ($i=0; $i <  $currfield['movefield']; $i++)
					print $namevalue.".parent().parent().parent().next().after(".$namevalue.".parent().parent().parent());"."\n";
			}

			if ($currfield['replacement'])
				print genRemplacement($currfield['replacement'], ".mainmenuaspan", $label, $namevalue);

			if ($currfield['active'] == 1 || $user_specials_rights['read'] == 0)
				print $namevalue.'.parent().parent().parent().remove();'."\n";

			if ($currfield['formatfield'] != '' )
				print $namevalue.'.parent().parent().find("a").attr("href", "'.$currfield['formatfield'].'");'."\n";

			if ($currfield['color'])
				print $namevalue.'.parent().parent().css("background", "#'.$currfield['color'].'");'."\n";
		}

		// menu gauche premier niveau
		$listfield = $myField->get_all_myfield('vmenu', 2);
		foreach ($listfield  as $currfield) {
			$user_specials_rights = $myField->getUserSpecialsRights($currfield['rowid'], $user, $currfield['querydisplay']);
			$label = $currfield['label'];
			$namevalue=getNameValue($label);

			print $namevalue.'=$(\'a.vmenu:contains("'.str_replace("'", "\'", $label).'")\');'."\n";
			print $namevalue.'='.$namevalue.'.filter(function () {return ($(this).text()== "'.str_replace("'", "\'", $label).'")});';

			// D�placement
			if ($currfield['movefield'] < 0) {
				for ($i=0; $i < (-1 * $currfield['movefield']); $i++)
					print $namevalue.".parent().parent().next().after(".$namevalue.".parent().parent());"."\n";
			} elseif ($currfield['movefield'] > 0) {
				for ($i=0; $i <  $currfield['movefield']; $i++)
					print $namevalue.".parent().parent().prev().before(".$namevalue.".parent().parent());"."\n";
			}

			if ($currfield['replacement'])
				print genRemplacement($currfield['replacement'], "a.vmenu", $label, $namevalue);

			if ($currfield['active'] == 1 || $user_specials_rights['read'] == 0)
				print $namevalue.'.parent().parent().remove();'."\n";

			if ($currfield['formatfield'] != '' )
				print $namevalue.'.attr("href", "'.$currfield['formatfield'].'");'."\n";

			if ($currfield['color'])
				print $namevalue.'.parent().parent().css("background", "#'.$currfield['color'].'");'."\n";
		}

		// menu gauche second niveau}
		$listfield = $myField->get_all_myfield('vsmenu', 2);
		foreach ($listfield  as $currfield) {
			$user_specials_rights = $myField->getUserSpecialsRights($currfield['rowid'], $user, $currfield['querydisplay']);
			$label = $currfield['label'];
			// pour les sous menu ambigue, on ajoute le menu principal avec # en s�paration
			if (strpos($label, "#") > 0) {
				$tblmenu=explode("#", $label);
				$namevalueparent=getNameValue($tblmenu[0]);
				$label=str_replace("'", "\'", $tblmenu[1]);
				$namevalue=getNameValue($label);
				// on r�cup�re le parent puis le menu en dessous
				print $namevalueparent.'=$(\'a.vmenu:contains("'.str_replace("'", "\'", $tblmenu[0]).'")\').parent().parent();'."\n";
				print $namevalue.'='.$namevalueparent.'.find(\'a.vsmenu:contains("'.$label.'")\');'."\n";
			} else {
				$namevalue=getNameValue($label);
				print $namevalue.'=$(\'a.vsmenu:contains("'.str_replace("'", "\'", $label).'")\');'."\n";
			}
			print $namevalue.'='.$namevalue.'.filter(function () {return ($(this).text() == "'.$label.'")});';

			// D�placement
			if ($currfield['movefield'] < 0) {
				for ($i=0; $i < (-1 * $currfield['movefield']); $i++)
					print $namevalue.".parent().next().after(".$namevalue.".parent());"."\n";
			} elseif ($currfield['moveefield'] > 0) {
				for ($i=0; $i < $currfield['movefield']; $i++)
					print $namevalue.".parent().prev().before(".$namevalue.".parent());"."\n";
			}

			// remplacement // si pb apostrophie il faut revoir le str_replace plus haut
			if ($currfield['replacement'])
				print genRemplacement($currfield['replacement'], "a.vsmenu", $label, $namevalue);

			// suppression
			if ($currfield['active'] == 1 || $user_specials_rights['read'] == 0)
				print $namevalue.'.parent().remove();'."\n";

			// changement d'url
			if ($currfield['formatfield'] != '' )
				print $namevalue.'.attr("href", "'.$currfield['formatfield'].'");'."\n";

			if ($currfield['color'])
				print $namevalue.'.parent().css("background", "#'.$currfield['color'].'");'."\n";
		}

		print "})\n;";
		print "</script>\n";
		return 0;
	}

	//print $langs->trans("MyFieldsDBCloseOrderingError");
	return -1;
}

// pour g�rer la d�sactivation des onglets et des menus
function printTabsHead($parameters, $object, $action)
{
	global $user, $db, $conf;
	$tblcontext=explode(":", $parameters['currentcontext']);
	if ($conf->global->MYFIELD_CONTEXT_VIEW =="1" )
		var_dump($tblcontext);

	dol_include_once('/myfield/class/myfield.class.php');
	$myField = new Myfield($db);

	print "<script>"."\n";
	print 'jQuery(document).ready(function () {'."\n";

	// le context de l'onglet correspond � au nom de l'onglet
	$listfield = $myField->get_all_myfield($parameters['currentcontext'], 1);

	foreach ($listfield  as $currfield) {
		$user_specials_rights = $myField->getUserSpecialsRights($currfield['rowid'], $user, $currfield['querydisplay']);
		$label = $currfield['label'];
		$namevalue=getNameValue($label);

		print $namevalue.'=$(\'a.tab:contains("'.str_replace("'", "\'", $label).'")\');'."\n";

		// D�placement
		if ($currfield['movefield'] < 0) {
			for ($i=0; $i < (-1 * $currfield['movefield']);$i++)
				print $namevalue.".parent().prev().before(".$namevalue.".parent());"."\n";
		} elseif ($currfield['movefield'] > 0) {
			for ($i=0; $i <  $currfield['movefield']; $i++)
				print $namevalue.".parent().next().after(".$namevalue.".parent());"."\n";
		}

		if ($currfield['replacement'])
			print genRemplacement($currfield['replacement'], "a.tab", $label, $namevalue);

		// suppression
		if ($currfield['active'] == 1 || $user_specials_rights['read'] == 0)
			print $namevalue.'.parent().remove();'."\n";

		// changement d'url
		if ($currfield['initvalue'] != '' )
			print $namevalue.'.attr("href","'.dol_buildpath($currfield['initvalue'], 1).'");'."\n";

		if ($currfield['color'])
			print $namevalue.'.parent().css("background","#'.$currfield['color'].'");'."\n";

	}

	print "});";
	print "</script>";

	// todo verif la pr�sence du champs � show/hide sur la page (sinon on affiche pas le champs)
	$listfield = $myField->get_all_myfield($parameters['context'], 0);

	$bvisibility=false;
	foreach ($listfield as $currfield)
		if ($currfield['active'] == 2)  // invisibility mode with reappear feature
			$bvisibility=true;

	if ($bvisibility) {
		print "<script>"."\n";
		print 'jQuery(document).ready(function () {'."\n";
		print 'var elementvisible = $(".fieldvisible");';
		print 'if (elementvisible.length){';
		print "$('#fieldshow').css('visibility','hidden');";
		print "$('#fieldhide').css('visibility','hidden');";
		print "}";
		print "$('#fieldshow').click(function(){ $('.fieldvisible').css('visibility','visible'); });";
		print "$('#fieldhide').click(function(){ $('.fieldvisible').css('visibility','hidden'); });";
		print "});";
		print "</script>"."\n";

		print "<div id='fieldshow' style='float:left;' href=#>Show /</div>";
		print "<div id='fieldhide' style='float:left;' href=#>&nbsp;Hide</div>";
	}
}
}

// fonctions de refactoring
function getNameValue($label)
{
	// on vire tous les caract�res pouvant g�ner
	$namevalue=str_replace(" ", "_", $label);
	return "mf".preg_replace('#[^A-Za-z0-9]+#', '_', $namevalue);
}

function genRemplacement($fieldreplacement, $elementcontain, $label, $namevalue)
{
	$res = 'textchange='.$namevalue.'.html();'."\n";
	$res.= 'if (textchange) {'."\n";
	$res.= "\t".'textchange=textchange.replace("'.$label.'", "'.$fieldreplacement.'");'."\n";
	$res.= "\t".$namevalue.'.html(textchange);'."\n";
	$res.= "}\n";
	$res.= $namevalue.'=$(\''.$elementcontain.':contains("'.str_replace("'", "\'", $fieldreplacement).'")\');'."\n";
	return $res;
}
