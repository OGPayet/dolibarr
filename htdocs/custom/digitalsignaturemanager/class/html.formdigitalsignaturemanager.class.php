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

	/**
     *	Return list of all contacts (for a third party or all)
     *
     *	@param	int		$socid      	Id ot third party or 0 for all
     *	@param  string	$selected   	Id contact pre-selectionne
     *	@param  string	$htmlname  	    Name of HTML field ('none' for a not editable field)
     *	@param  int		$showempty     	0=no empty value, 1=add an empty value, 2=add line 'Internal' (used by user edit)
     *	@param  string	$exclude        List of contacts id to exclude
     *	@param	string	$limitto		Disable answers that are not id in this array list
     *	@param	integer	$showfunction   Add function into label
     *	@param	string	$moreclass		Add more class to class style
     *	@param	bool	$options_only	Return options only (for ajax treatment)
     *	@param	integer	$showsoc	    Add company into label
     * 	@param	int		$forcecombo		Force to use combo box
     *  @param	array	$events			Event options. Example: array(array('method'=>'getContacts', 'url'=>dol_buildpath('/core/ajax/contacts.php',1), 'htmlname'=>'contactid', 'params'=>array('add-customer-contact'=>'disabled')))
     *	@return	 int					<0 if KO, Nb of contact in list if OK
     */
    function selectcontacts($socid, $selected = '', $htmlname = 'contactid', $showempty = 0, $exclude = '', $limitto = '', $showfunction = 0, $moreclass = '', $options_only = false, $showsoc = 0, $forcecombo = 0, $events = array(), $hideDisabledItem = false)
    {
        global $conf,$langs;

        $langs->load('companies');

        $out='';

        // On recherche les societes
        $sql = "SELECT sp.rowid, sp.lastname, sp.statut, sp.firstname, sp.poste";
        if ($showsoc > 0) $sql.= " , s.nom as company";
        $sql.= " FROM ".MAIN_DB_PREFIX ."socpeople as sp";
        if ($showsoc > 0) $sql.= " LEFT OUTER JOIN  ".MAIN_DB_PREFIX ."societe as s ON s.rowid=sp.fk_soc";
        $sql.= " WHERE sp.entity IN (".getEntity('societe').")";
        if ($socid > 0) $sql.= " AND sp.fk_soc=".$socid;
        if (! empty($conf->global->CONTACT_HIDE_INACTIVE_IN_COMBOBOX)) $sql.= " AND sp.statut <> 0";
        $sql.= " ORDER BY sp.lastname ASC";

        dol_syslog(get_class($this)."::select_contacts", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $num=$this->db->num_rows($resql);

            if ($conf->use_javascript_ajax && ! $forcecombo && ! $options_only)
            {
				include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
		$comboenhancement = ajax_combobox($htmlname, $events, $conf->global->CONTACT_USE_SEARCH_TO_SELECT);
		$out.= $comboenhancement;
            }

            if ($htmlname != 'none' || $options_only) $out.= '<select class="flat'.($moreclass?' '.$moreclass:'').'" id="'.$htmlname.'" name="'.$htmlname.'">';
            if ($showempty == 1) $out.= '<option value="0"'.($selected=='0'?' selected':'').'></option>';
            if ($showempty == 2) $out.= '<option value="0"'.($selected=='0'?' selected':'').'>'.$langs->trans("Internal").'</option>';
            $num = $this->db->num_rows($resql);
            $i = 0;
            if ($num)
            {
                include_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
                $contactstatic=new Contact($this->db);

                while ($i < $num)
                {
                    $obj = $this->db->fetch_object($resql);

                    $contactstatic->id=$obj->rowid;
                    $contactstatic->lastname=$obj->lastname;
                    $contactstatic->firstname=$obj->firstname;
					if ($obj->statut == 1){
						if ($htmlname != 'none')
						{
							$disabled=0;
							if (is_array($exclude) && count($exclude) && in_array($obj->rowid, $exclude)) $disabled=1;
							if (is_array($limitto) && count($limitto) && ! in_array($obj->rowid, $limitto)) $disabled=1;
							if($disabled == 1 && $hideDisabledItem) {
								continue;
							}
							if ($selected && $selected == $obj->rowid)
							{
								$out.= '<option value="'.$obj->rowid.'"';
								if ($disabled) $out.= ' disabled';
								$out.= ' selected>';
								$out.= $contactstatic->getFullName($langs);
								if ($showfunction && $obj->poste) $out.= ' ('.$obj->poste.')';
								if (($showsoc > 0) && $obj->company) $out.= ' - ('.$obj->company.')';
								$out.= '</option>';
							}
							else
							{
								$out.= '<option value="'.$obj->rowid.'"';
								if ($disabled) $out.= ' disabled';
								$out.= '>';
								$out.= $contactstatic->getFullName($langs);
								if ($showfunction && $obj->poste) $out.= ' ('.$obj->poste.')';
								if (($showsoc > 0) && $obj->company) $out.= ' - ('.$obj->company.')';
								$out.= '</option>';
							}
						}
						else
						{
							if ($selected == $obj->rowid)
							{
								$out.= $contactstatic->getFullName($langs);
								if ($showfunction && $obj->poste) $out.= ' ('.$obj->poste.')';
								if (($showsoc > 0) && $obj->company) $out.= ' - ('.$obj->company.')';
							}
						}
					}
                    $i++;
                }
            }
            else
			{
		$out.= '<option value="-1"'.($showempty==2?'':' selected').' disabled>'.$langs->trans($socid?"NoContactDefinedForThirdParty":"NoContactDefined").'</option>';
            }
            if ($htmlname != 'none' || $options_only)
            {
                $out.= '</select>';
            }

            $this->num = $num;
            return $out;
        }
        else
        {
            dol_print_error($this->db);
            return -1;
        }
    }
}
