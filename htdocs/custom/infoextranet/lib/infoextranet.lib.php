<?php
/* Copyright (C) 2018 SuperAdmin
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
 * \file    infoextranet/lib/infoextranet.lib.php
 * \ingroup infoextranet
 * \brief   Library files with common functions for InfoExtranet
 */

/**
 * Prepare admin pages header
 *
 * @return array
 */
function infoextranetAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("infoextranet@infoextranet");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/infoextranet/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@infoextranet:/infoextranet/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@infoextranet:/infoextranet/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'infoextranet');

	return $head;
}

/**
 * Get all extrafield of a section (using name of extrafield)
 *
 * @param   array       $extra          Extrafields array (containing name attribute)
 * @param   string      $section        Section searched in extrafield name (ex: searching 'c42H' in 'c42H_nb_post')
 * @return  array
 */
function getSectionFromExtrafields($extra, $section)
{
    $extraSection = array();

    foreach ($extra as $key => $field)
    {
        if (strpos($field['name'], $section) !== false)
            $extraSection[] = $field;
    }

    return $extraSection;
}

/**
 * Function that get the contract of a section "Etat de Parc"
 *
 * @param $extra           ExtraFields
 * @param $section
 * @return array
 */
function getContractFromSection($extra, $section)
{
    $contract = array();

    foreach ($extra as $key => $field)
    {
        if ($field['name'] == $section.'contract')
            $contract = $field;
    }

    return $contract;
}

/**
 * Get all c42 extrafields name from db
 *
 * @param   string      $section        Search extrafield from specific section (ex: 'H', 'R', 'M', 'P')
 * @return  array                       array with attribute 'name' & 'label' & 'type'
 */
function getExtrafields($section = '')
{
    global $db;

    $extra = array();

    $sql = "SELECT e.name, e.label, e.type, e.pos FROM ".MAIN_DB_PREFIX."extrafields AS e WHERE e.name LIKE 'c42".$section."%' ORDER BY e.pos";

    $resql = $db->query($sql);
    if ($resql)
    {
        $num = $resql->num_rows;
        for ($i = 0; $i < $num; $i++)
            $extra[] = $resql->fetch_assoc();
    }

    return $extra;
}

/**
 * Function that get the extrafield
 *
 * @param $id
 * @param $extra
 * @return array
 */
function getExtrafieldsOf($id, $extra)
{
    global $db;

    $sql = "SELECT ";
    $entered = false;
    foreach($extra as $key => $field)
    {
        $entered = true;
        $sql.= $field['name'].", ";
    }

    // Delete last coma + space
    if ($entered)
        $sql = substr($sql, 0, -2);

    $sql.= " FROM ".MAIN_DB_PREFIX."societe_extrafields WHERE fk_object = '".$id."'";

    $extra = array();

    dol_syslog($sql, LOG_DEBUG);
    $resql = $db->query($sql);
    if ($resql)
    {
        $num = $resql->num_rows;
        for ($i = 0; $i < $num; $i++)
            $extra = $resql->fetch_assoc();
    }
    return $extra;
}

/**
 *      Get default contact of a society for billing mode
 *
 *      @param      Database        $db         The object given by dolibarr global (global $db)
 *      @param      int             $id         id of the society
 *      @return     array
 */
function getDefaultContactsOf($db, $id)
{
    $arr = array();
    $sql = "SELECT sc.fk_socpeople, t.element, t.source, t.code, t.libelle FROM llx_societe_contact AS sc INNER JOIN llx_c_type_contact
            AS t ON t.rowid = sc.fk_c_type_contact WHERE sc.element_id = " .
        $id . " AND t.active = '1' ORDER BY t.source DESC, t.rowid DESC";

    $resql = $db->query($sql);
    if ($resql)
    {
        foreach($resql as $key => $field)
            $arr[] = $field;
    }

    return $arr;
}

/**
 * Function that update the extrafields of a Thirdparty in "Etat de Parc"
 * @param $id
 * @param $toupdate
 * @return int
 */
function updateExtrafields($id, $toupdate)
{
    global $db;

    if (!empty($toupdate))
    {
        $sql = "UPDATE ".MAIN_DB_PREFIX."societe_extrafields SET ";
        $entered = false;

        foreach ($toupdate as $key => $field)
        {
            $entered = true;
            if ($field == null)
                $sql.= $key." = NULL, ";
            else
                $sql.= $key." = '".$db->escape($field)."', ";
        }
        // Delete coma and space if enter foreach
        if ($entered)
            $sql = substr($sql, 0, -2);

        $sql.= " WHERE fk_object = ".$id;

        $resql = $db->query($sql);
        if ($resql)
        {
            dol_syslog($sql, LOG_DEBUG);
            return 1;
        }
        else
        {
            dol_syslog("Error with : ".$sql, LOG_ERR);
            return -1;
        }
    }
    return -1;
}

/**
 * Print custom header with contactdefault, tags and numcli extrafield if exist
 *
 * @param   int         $socid                  Id of thirdparty
 * @param   Societe     $object                 Societe object associated to id
 * @param   Form        $form                   Form object
 * @return  void
 */
function printCustomHeader($socid, $object, $form)
{
    global $langs, $conf, $user, $db;

    if (isset($object->array_options['options_tiers_numcli']))
    {
        print '<div><strong>'.$langs->trans('TiersNumCli').' :</strong> <span class="badgeSoc" style="background-color: #ea7600;font-size: 15px;">'.$object->array_options['options_tiers_numcli'].'</span></div>';
    }

    /*
     *  Contact default / tag section
     */
    print '<div class="mDivRow">';
    $checkcontact = false;
    if (! empty($conf->global->MAIN_MODULE_CONTACTDEFAULT))
    {
        $checkcontact = true;
        print '<div class="mDivLeft">';
        print '<h2><i class="fa fa-user"></i> ' . $langs->trans("ContactDefault") . '</h2>';
        print '<table class="noborder" style="text-align: center">';
        print_liste_field_titre('');
        print_liste_field_titre($langs->trans('UserContact'));
        print_liste_field_titre($langs->trans('Mail'));
        print_liste_field_titre($langs->trans('Phone'));
        print_liste_field_titre($langs->trans('TypeContact'));
        $contacts = getDefaultContactsOf($db, $socid);
        foreach($contacts as $key => $value)
        {
            $socpeople = new Contact($db);
            $socpeople->fetch($value['fk_socpeople']);
            if ($value['source'] == 'internal') {
                $socpeople = new User($db);
                $socpeople->fetch($value['fk_socpeople']);
            }

            // To change backgroud color : .poste_Name td {background-color : ... }
            print '<tr class="poste_'.$socpeople->poste.'">';

            // Source (internal / external)
            print '<td>';
            if ($value['source'] == 'internal')
                print '<i class="fa fa-user" style="color: #ea7600;"></i>';
            else
                print '<i class="fa fa-user"></i>';

            print '</td>';

            // Name
            print '<td>'.$socpeople->getNomUrl(0).'</td>';

            // Mail
            print '<td>'.$socpeople->email.'</td>';

            // Phone (Get phone_mobile if phone_pro is empty)
            $phone = $socpeople->phone_pro;
            if (empty($phone))
                $phone = $socpeople->phone_mobile;

            print '<td>'.$phone.'</td>';

            print '<td>'.ucfirst($value['element']).' - '.$value['libelle'].'</td>';
            print '</tr>';

        }
        if (count($contacts) == 0)
            print '<tr><td colspan="4" class="opacitymedium">Aucun</td></tr>';

        print '</table>';
        print '</div>';
    }

    // Tags / categories
    if (! empty($conf->categorie->enabled)  && ! empty($user->rights->categorie->lire))
    {
        if ($checkcontact)
            print '<div class="mDivRight">';
        else
            print '<div class="mDivLeft">';

        // Customer
        if ($object->prospect || $object->client) {
            print '<h2><i class="fa fa-tag"></i> ' . $langs->trans("CustomersCategoriesShort") . '</h2>';
            print $form->showCategories($object->id, 'customer', 1);
        }

        // Supplier
        if ($object->fournisseur) {
            print '<h2><i class="fa fa-tag"></i> ' . $langs->trans("SuppliersCategoriesShort") . '</h2>';
            print $form->showCategories($object->id, 'supplier', 1);
        }
        print '</div>';
    }
    print '</div>';

    print '<div style="clear:both"></div>';
    /* End contact default / tag section */
}

/**
 * Get a string between 2 delimiter
 *
 * @param   string      $content        Original string
 * @param   string      $start          First delimiter
 * @param   string      $end            Second delimiter
 * @return  string
 */
function getBetween($content, $start, $end)
{
    $r = explode($start, $content);
    if (isset($r[1])) {
        $r = explode($end, $r[1]);
        return $r[0];
    }
    return '';
}
