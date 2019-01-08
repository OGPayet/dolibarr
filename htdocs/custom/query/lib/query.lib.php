<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2013 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file		lib/query.lib.php
 *	\ingroup	query
 *	\brief		This file is an example module library
 *				Put some comments here
 */

function queryPrepareHead($object)
{
    global $langs, $conf;

    $langs->load("query@query");

    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath("/query/query.php?action=view&id=".$object->getID(), 1);
    $head[$h][1] = $langs->trans("Card");
    $head[$h][2] = 'query';
    $h++;
    $head[$h][0] = dol_buildpath("/query/query_rights.php?id=".$object->getID(), 1);
    $head[$h][1] = $langs->trans("Rights");
    $head[$h][2] = 'rights';
    $h++;

    complete_head_from_modules($conf, $langs, $object, $head, $h, 'query');

    return $head;
}

function queryAdminPrepareHead()
{
    global $langs, $conf;

    $langs->load("query@query");

    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath("/query/admin/query_setup.php", 1);
    $head[$h][1] = $langs->trans("Settings");
    $head[$h][2] = 'settings';
    $h++;
    $head[$h][0] = dol_buildpath("/query/admin/query_about.php", 1);
    $head[$h][1] = $langs->trans("About");
    $head[$h][2] = 'about';
    $h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    //$this->tabs = array(
    //	'entity:+tabname:Title:@query:/query/mypage.php?id=__ID__'
    //); // to add new tab
    //$this->tabs = array(
    //	'entity:-tabname:Title:@query:/query/mypage.php?id=__ID__'
    //); // to remove a tab
    complete_head_from_modules($conf, $langs, $object, $head, $h, 'query');

    return $head;
}
function explode_brackets($str, $separator=",", $leftbracket="(", $rightbracket=")", $quote="'", $ignore_escaped_quotes=true ) {

    $buffer = '';
    $stack = array();
    $depth = 0;
    $betweenquotes = false;
    $len = strlen($str);
    for ($i=0; $i<$len; $i++) {
      $previouschar = $char;
      $char = $str[$i];
      switch ($char) {
        case $separator:
          if (!$betweenquotes) {
            if (!$depth) {
              if ($buffer !== '') {
                $stack[] = $buffer;
                $buffer = '';
              }
              continue 2;
            }
          }
          break;
        case $quote:
          if ($ignore_escaped_quotes) {
            if ($previouschar!="\\") {
              $betweenquotes = !$betweenquotes;
            }
          } else {
            $betweenquotes = !$betweenquotes;
          }
          break;
        case $leftbracket:
          if (!$betweenquotes) {
            $depth++;
          }
          break;
        case $rightbracket:
          if (!$betweenquotes) {
            if ($depth) {
              $depth--;
            } else {
              $stack[] = $buffer.$char;
              $buffer = '';
              continue 2;
            }
          }
          break;
        }
        $buffer .= $char;
    }
    if ($buffer !== '') {
      $stack[] = $buffer;
    }

    return $stack;
}

function _getFieldAndTableName($field) {

	$field = trim($field);
	$pos = strrpos(strtolower($field),' as ');

	if($pos!==false) {
		// Si un "as" est présent, on prend que ce qu'il y a avant
		$field = substr($field, 0, $pos);
	}

	// Si $field est en fait une sous-requête (Traduction : la query contient dans son SELECT un sous SELECT)
	if (stripos($field, 'select') !== false)
	{
		$table = '';
	}
	else
	{
		list($t,$f) = explode('.',$field);
		$field = empty($f) ? $t : $f;
		$table = empty($f) ? '' : $t;
	}


	return array($field,$table);
}

function _getFieldName($field){

	list($f,$t) = _getFieldAndTableName($field);

	return $f;

}
