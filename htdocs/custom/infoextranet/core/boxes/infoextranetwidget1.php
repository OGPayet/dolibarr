<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2018 SuperAdmin
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
 * \file    modulebuilder/template/core/boxes/infoextranetwidget1.php
 * \ingroup infoextranet
 * \brief   Widget provided by InfoExtranet
 *
 * Put detailed description here.
 */

/** Includes */
include_once DOL_DOCUMENT_ROOT . "/core/boxes/modules_boxes.php";
require_once  DOL_DOCUMENT_ROOT . "/societe/class/societe.class.php";

/**
 * Class to manage the box
 *
 * Warning: for the box to be detected correctly by dolibarr,
 * the filename should be the lowercase classname
 */
class infoextranetwidget1 extends ModeleBoxes
{
	/**
	 * @var string Alphanumeric ID. Populated by the constructor.
	 */
	public $boxcode = "infoextranetbox1";

	/**
	 * @var string Box icon (in configuration page)
	 * Automatically calls the icon named with the corresponding "object_" prefix
	 */
	public $boximg = "infoextranet@infoextranet";

	/**
	 * @var string Box label (in configuration page)
	 */
	public $boxlabel;

	/**
	 * @var string[] Module dependencies
	 */
	public $depends = array('infoextranet');

	/**
	 * @var DoliDb Database handler
	 */
	public $db;

	/**
	 * @var mixed More parameters
	 */
	public $param;

	/**
	 * @var array Header informations. Usually created at runtime by loadBox().
	 */
	public $info_box_head = array();

	/**
	 * @var array Contents informations. Usually created at runtime by loadBox().
	 */
	public $info_box_contents = array();

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 * @param string $param More parameters
	 */
	public function __construct(DoliDB $db, $param = '')
	{
		global $user, $conf, $langs;
		$langs->load("boxes");
		$langs->load('infoextranet@infoextranet');

		parent::__construct($db, $param);

		$this->boxlabel = $langs->transnoentitiesnoconv("Last5Modified");

		$this->param = $param;

		//$this->enabled = $conf->global->FEATURES_LEVEL > 0;         // Condition when module is enabled or not
		$this->hidden = ! ($user->rights->infoextranet->read);   // Condition when module is visible by user (test on permission)
	}

	/**
	 * Load data into info_box_contents array to show array later. Called by Dolibarr before displaying the box.
	 *
	 * @param int $max Maximum number of records to load
	 * @return void
	 */
	public function loadBox($max = 5)
	{
		global $langs, $db;

		// Use configuration value for max lines count
		$this->max = $max;

		//include_once DOL_DOCUMENT_ROOT . "/infoextranet/class/infoextranet.class.php";

		// Populate the head at runtime
		$text = $langs->trans("InfoExtranetBoxDescription", $max);
		$this->info_box_head = array(
			// Title text
			'text' => $text,
			// Add a link
			'sublink' => '',
			// Sublink icon placed after the text
			'subpicto' => 'infoextranet_32@infoextranet',
			// Sublink icon HTML alt text
			'subtext' => '',
			// Sublink HTML target
			'target' => '',
			// HTML class attached to the picto and link
			'subclass' => 'center',
			// Limit and truncate with "…" the displayed text lenght, 0 = disabled
			'limit' => 0,
			// Adds translated " (Graph)" to a hidden form value's input (?)
			'graph' => false
		);

		$sql = "SELECT fk_object, tms FROM ".MAIN_DB_PREFIX."societe_extrafields WHERE c42M_contract > 0 ORDER BY tms DESC LIMIT 5";
		$resql = $db->query($sql);
		$i = 0;
        foreach ($resql as $key => $row)
        {
            $tiers = new Societe($db);
            $tiers->fetch($row['fk_object']);
            $this->info_box_contents[$i][] = array('td' => 'class="tdoverflowmax150 maxwidth150onsmartphone"',
                'text' => $tiers->getNomUrl(1),
                'asis' => 1
            );
            $this->info_box_contents[$i][] = array('td' => 'class="right"',
                'text' => dol_print_date($row['tms'], '%d/%m/%Y'),
                'asis' => 1
            );
            $this->info_box_contents[$i][] = array('td' => 'class="right"',
                'text' => '<i class="fa fa-check" style="color: green;"></i>',
                'asis' => 1
            );
            $i++;
        }
	}

	/**
	 * Method to show box. Called by Dolibarr eatch time it wants to display the box.
	 *
	 * @param array $head Array with properties of box title
	 * @param array $contents Array with properties of box lines
	 * @return void
	 */
	public function showBox($head = null, $contents = null, $nooutput = 0)
	{
		// You may make your own code here…
		// … or use the parent's class function using the provided head and contents templates
		parent::showBox($this->info_box_head, $this->info_box_contents);
	}
}
