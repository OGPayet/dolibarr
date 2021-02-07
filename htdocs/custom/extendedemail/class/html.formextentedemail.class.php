<?php
/* Copyright (c) 2017       Open-Dsi      <support@open-dsi.fr>
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
 *	\file       htdocs/extendedemail/core/class/html.formextendedemail.class.php
 *  \ingroup    extendedemail
 *	\brief      File of class with all html Extended Email components
 */


/**
 *	Class to manage generation of HTML components
 *	Only Extended Email components must be here.
 *
 */
class FormExtentedEmail
{
    var $db;
    var $error;
    var $num;

    /**
     * Constructor
     *
     * @param		DoliDB		$db      Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     *	Return select list of generic email
     *
     *  @param	string	$selected       Id group preselected
     *  @param  string	$htmlname       Field name in form
     *  @param  int		$show_empty     0=liste sans valeur nulle, 1=ajoute valeur inconnue
     *  @param  string	$exclude        Array list of groups id to exclude
     * 	@param	int		$disabled		If select list must be disabled
     *  @param  string	$include        Array list of groups id to include
     * 	@param	int		$enableonly		Array list of groups id to be enabled. All other must be disabled
     * 	@param	int		$force_entity	0 or Id of environment to force
     *  @return	string
     */
    function select_genericemails($selected='', $htmlname='genericemailid', $show_empty=0, $exclude='', $disabled=0, $include='', $enableonly='', $force_entity=0)
    {
        global $conf,$user,$langs;

        $langs->load('extendedemail@extendedemail');

        // Permettre l'exclusion de groupes
        if (is_array($exclude))	$excludeGroups = implode("','",$exclude);
        // Permettre l'inclusion de groupes
        if (is_array($include))	$includeGroups = implode("','",$include);

        $out='';

        // On recherche les groupes
        $sql = "SELECT cge.rowid, cge.email, cge.name";
        if (! empty($conf->multicompany->enabled) && $conf->entity == 1 && $user->admin && ! $user->entity)
        {
            $sql.= ", e.label";
        }
        $sql.= " FROM ".MAIN_DB_PREFIX."c_extentedemail_generic_email as cge";
        if (! empty($conf->multicompany->enabled) && $conf->entity == 1 && $user->admin && ! $user->entity)
        {
            $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."entity as e ON e.rowid=cge.entity";
            if ($force_entity) $sql.= " WHERE cge.entity IN (0,".$force_entity.")";
            else $sql.= " WHERE cge.entity IS NOT NULL";
        }
        else
        {
            $sql.= " WHERE cge.entity IN (0,".$conf->entity.")";
        }
        $sql.= " AND cge.active = 1";
        if (is_array($exclude) && $excludeGroups) $sql.= " AND cge.rowid NOT IN ('".$excludeGroups."')";
        if (is_array($include) && $includeGroups) $sql.= " AND cge.rowid IN ('".$includeGroups."')";
        $sql.= " ORDER BY cge.name ASC";

        dol_syslog(get_class($this)."::select_genericemails", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
    		// Enhance with select2
	        if ($conf->use_javascript_ajax)
	        {
				include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
	           	$comboenhancement = ajax_combobox($htmlname);
                $out.= $comboenhancement;
                $nodatarole=($comboenhancement?' data-role="none"':'');
            }

            $out.= '<select class="flat minwidth200" id="'.$htmlname.'" name="'.$htmlname.'"'.($disabled?' disabled':'').$nodatarole.'>';

        	$num = $this->db->num_rows($resql);
            $i = 0;
            if ($num)
            {
                if ($show_empty) $out.= '<option value="-1"'.($selected==-1?' selected':'').'>&nbsp;</option>'."\n";

                while ($i < $num)
                {
                    $obj = $this->db->fetch_object($resql);
                    $disableline=0;
                    if (is_array($enableonly) && count($enableonly) && ! in_array($obj->rowid,$enableonly)) $disableline=1;

                    $out.= '<option value="'.$obj->rowid.'"';
                    if ($disableline) $out.= ' disabled';
                    if ((is_object($selected) && $selected->id == $obj->rowid) || (! is_object($selected) && $selected == $obj->rowid))
                    {
                        $out.= ' selected';
                    }
                    $out.= '>';

                    $out.= !empty($obj->name) ? "{$obj->name} &lt;{$obj->email}&gt;" : "{$obj->email}";
                    if (! empty($conf->multicompany->enabled) && empty($conf->multicompany->transverse_mode) && $conf->entity == 1)
                    {
                        $out.= " (".$obj->label.")";
                    }

                    $out.= '</option>';
                    $i++;
                }
            }
            else
            {
                if ($show_empty) $out.= '<option value="-1"'.($selected==-1?' selected':'').'></option>'."\n";
                $out.= '<option value="" disabled>'.$langs->trans("ExtendedEmailNoGenericEmailDefined").'</option>';
            }
            $out.= '</select>';
        }
        else
        {
            dol_print_error($this->db);
        }

        return $out;
    }
}
