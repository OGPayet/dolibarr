<?php
/* Copyright (C) 2012-2017		Charlie Benke	<charlie@patas-monkey.com>

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
 *  \file	   htdocs/equipement/core/modules/equipement/mod_atlantic.php
 *  \ingroup	equipement
 *  \brief	  File with atlantic numbering module for equipement
 */
dol_include_once("/equipement/core/modules/equipement/modules_equipement.php");

/**
 *	Class to manage numbering of intervention cards with rule Pacific.
 */
class mod_atlantic extends ModeleNumRefEquipement
{
	var $version='dolibarr';		// 'development', 'experimental', 'dolibarr'
	var $prefix='SN';
	var $error='';
	var $nom = 'atlantic';


	/**
	 *  Return description of numbering module
	 *
	*  @return	 string	  Text with description
	*/
	function info()
	{
		global $langs;
		return $langs->trans("SimpleNumRefModelDesc", $this->prefix);
	}

	/**
	 *  Renvoi un exemple de numerotation
	 *
	 *  @return	 string	  Example
	 */
	function getExample()
	{
		return $this->prefix."1408-00001";
	}

	/**
	 *  Test si les numeros deja en vigueur dans la base ne provoquent pas de
	 *  de conflits qui empechera cette numerotation de fonctionner.
	 *
	 *  @return	 boolean	 false si conflit, true si ok
	 */
	function canBeActivated()
	{
		global $langs, $conf;

		$langs->load("equipement@equipement");

		$fayymm=''; $max='';

		$posindice=8;
		$sql = "SELECT MAX(SUBSTRING(ref FROM ".$posindice.")) as max";
		$sql.= " FROM ".MAIN_DB_PREFIX."equipement";
		$sql.= " WHERE ref like '".$this->prefix."____-%'";
		$sql.= ' and entity IN (' . getEntity('equipement') . ')';

		$resql=$db->query($sql);
		if ($resql) {
			$row = $db->fetch_row($resql);
			if ($row) {
				$fayymm = substr($row[0], 0, 6);
				$max=$row[0];
			}
		}
		if (! $fayymm || preg_match('/'.$this->prefix.'[0-9][0-9][0-9][0-9]/i', $fayymm))
			return true;
		else {
			$langs->load("errors");
			$this->error=$langs->trans('ErrorNumRefModel', $max);
			return false;
		}
	}

	/**
	 * 	Return next free value
	 *
	 *  @param	Societe		$objsoc	 Object thirdparty
	 *  @param  Object		$object		Object we need next value for
	 *  @return string	  			Value if KO, <0 if KO
	 */
	function getNextValue($objsoc=0, $object='')
	{
		global $db, $conf;

		// D'abord on recupere la valeur max
		$posindice=8;
		$sql = "SELECT MAX(SUBSTRING(ref FROM ".$posindice.")) as max";
		$sql.= " FROM ".MAIN_DB_PREFIX."equipement";
		$sql.= " WHERE ref LIKE '".$this->prefix."____-%'";
		$sql.= ' AND entity IN (' . getEntity('equipement') . ')';

		$resql=$db->query($sql);
		if ($resql) {
			$obj = $db->fetch_object($resql);
			if ($obj) $max = intval($obj->max);
			else $max=0;
		}

		$date=time();
		//$date=$object->date;
		$yymm = strftime("%y%m", $date);
		$num = sprintf("%05s", $max+1);

		return $this->prefix.$yymm."-".$num;
	}

	/**
	 * 	Return next free value
	 *
	 *  @param	Societe	$objsoc	 Object third party
	 * 	@param	Object	$objforref	Object for number to search
	 *  @return string	  		Next free value
	 */
	function getNumRef($objsoc, $objforref)
	{
		return $this->getNextValue($objsoc, $objforref);
	}
}