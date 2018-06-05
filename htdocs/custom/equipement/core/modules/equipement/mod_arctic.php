<?php
/* Copyright (C) 2012-2016	Charlie Benke	<charlie@patas-monkey.com>
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
 *	\file	   htdocs/equipement/core/modules/equipement/mod_arctic.php
 *	\ingroup	produit
 *	\brief	  File with Arctic numbering module for equipement
 */
dol_include_once("/equipement/core/modules/equipement/modules_equipement.php");

/**
 *	Class to manage numbering of equipement cards with rule Artic.
 */
class mod_arctic extends ModeleNumRefEquipement
{
	var $version='dolibarr';		// 'development', 'experimental', 'dolibarr'
	var $error = '';
	var $nom = 'arctic';

	/**
	 *  Renvoi la description du modele de numerotation
	 *
	 *  @return	 string	  Texte descripif
	 */
	function info()
	{
		global $conf, $langs;

		$langs->load("bills");

		$form = new Form($this->db);

		$texte = $langs->trans('GenericNumRefModelDesc')."<br>\n";
		$texte.= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		$texte.= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		$texte.= '<input type="hidden" name="action" value="updateMask">';
		$texte.= '<input type="hidden" name="maskconst" value="EQUIPEMENT_ARTIC_MASK">';
		$texte.= '<table class="nobordernopadding" width="100%">';

		$tooltip=$langs->trans(
						"GenericMaskCodes",
						$langs->transnoentities("EquipementCard"),
						$langs->transnoentities("EquipementCard")
		);
		$tooltip.=$langs->trans("GenericMaskCodes2");
		$tooltip.=$langs->trans("GenericMaskCodes3");
		$tooltip.=$langs->trans(
						"GenericMaskCodes4a",
						$langs->transnoentities("EquipementCard"),
						$langs->transnoentities("EquipementCard")
		);
		$tooltip.=$langs->trans("GenericMaskCodes5");

		// Parametrage du prefix
		$texte.= '<tr><td>'.$langs->trans("Mask").':</td>';
		$texte.= '<td align="right">';
		$texte.= $form->textwithpicto(
						'<input type="text" class="flat" size="24" name="maskvalue" value="'.$conf->global->EQUIPEMENT_ARTIC_MASK.'">',
						$tooltip, 1, 1
		).'</td>';
		$texte.= '<td align="left" rowspan="2">';
		$texte.= '&nbsp; <input type="submit" class="button" value="'.$langs->trans("Modify").'" name="Button">';
		$texte.= '</td></tr>';

		$texte.= '</table>';
		$texte.= '</form>';

		return $texte;
	}

	/**
	 * Renvoi un exemple de numerotation
	 *
	 * @return	 string	  Example
	 */
	function getExample()
	{
		global $langs, $mysoc; // $conf,

		$old_code_client=$mysoc->code_client;
		$mysoc->code_client='CCCCCCCCCC';
		$numExample = $this->getNextValue($mysoc, '');
		$mysoc->code_client=$old_code_client;

		if (! $numExample)
			$numExample = $langs->trans('NotConfigured');

		return $numExample;
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

		require_once(DOL_DOCUMENT_ROOT ."/core/lib/functions2.lib.php");

		// On défini critere recherche compteur
		$mask=$conf->global->EQUIPEMENT_ARTIC_MASK;

		if (! $mask) {
			$this->error='NotConfigured';
			return 0;
		}

		$numFinal=get_next_value($db, $mask, 'equipement', 'ref', '', $objsoc->code_client, $object->date);

		return  $numFinal;
	}

	/**
	* 	Return next free value
	*
	*  @param	Societe		$objsoc	 Object third party
	* 	@param	Object		$objforref	Object for number to search
	*  @return string	  			Next free value
	*/
	function getNumRef($objsoc, $objforref)
	{
		return $this->getNextValue($objsoc, $objforref);
	}
}