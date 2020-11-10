<?php
/* Copyright (c) 2020  Alexis LAURIER    <contact@alexislaurier.fr>
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
 *	\file       digitalsignaturemanager/class/html.formdigitalsignaturemanager.class.php
 *  \ingroup    core
 *	\brief      File of class with all html predefined components
 */

/**
 *	Class to manage generation of HTML components
 *	Only common components must be here.
 */
class FormDigitalSignatureManager
{
	/**
     * @var DoliDb		Database handler (result of a new DoliDB)
     */
	public $db;

    /**
     * @var Form  Instance of the form
     */
    public $form;

    /**
     * @var array
     */
	public static $errors = array();

    /**
     * Constructor
     *
     * @param   DoliDB $db Database handler
     */
    public function __construct(DoliDb $db)
    {
        $this->db = $db;

        require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
		$this->form = new Form($db);
    }

	/**
     *  Show list of actions for element
     *
	 *  @param	int		$digitalSignatureRequestId
	 *  @param	int		$lineId
	 *  @param	int		$currentLineIndex	Current index for the line
	 *	@param	int		$numberOfDocumentLines	number of document of the linked request
	 *  @param 	string 	$upActionName
	 *  @param	string  $downActionName
	 * 	@param	string  $paramLineIdName
     *	@return	void
     */
    public function showMoveActionButtonsForLine($digitalSignatureRequestId, $lineId, $currentLineIndex, $numberOfDocumentLines, $upActionName, $downActionName, $paramLineIdName)
    {
		global $conf;
		if (!empty($conf->browser->phone)) {
			print '<td align="center" class="linecolmove tdlineupdown">';
			if($currentLineIndex > 0) {
				print '<a class="lineupdown" href="' . $_SERVER["PHP_SELF"] . '?id=' . $digitalSignatureRequestId . '&amp;action=' . $upActionName . '&amp;' . $paramLineIdName . '='.$lineId . '">';
				print img_up('default', 0, 'imgupforline');
				print '</a>';
			}

			if($currentLineIndex != $numberOfDocumentLines - 1) {
				print '<a class="lineupdown" href="' . $_SERVER["PHP_SELF"] . '?id=' . $digitalSignatureRequestId . '&amp;action=' . $downActionName . '&amp;' . $paramLineIdName . '='.$lineId . '">';
				print img_down('default', 0, 'imgdownforline');
				print '</a>';
			}
			print '</td>';
		}
		elseif ($numberOfDocumentLines > 1) {
			print '<td align="center" class="linecolmove tdlineupdown"></td>';
		}
	}
}
