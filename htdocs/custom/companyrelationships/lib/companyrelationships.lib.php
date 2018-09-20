<?php
/* Copyright (C) 2018      Open-DSI             <support@open-dsi.fr>
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
 *	\file       htdocs/companyrelationships/lib/companyrelationships.lib.php
 * 	\ingroup	companyrelationships
 *	\brief      Functions for the module company relationships
 */

/**
 * Prepare array with list of tabs
 *
 * @return  array				Array of tabs to show
 */
function companyrelationships_admin_prepare_head()
{
    global $langs, $conf, $user;
    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath("/companyrelationships/admin/dictionaries.php", 1);
    $head[$h][1] = $langs->trans("Dictionaries");
    $head[$h][2] = 'dictionaries';
    $h++;

    $head[$h][0] = dol_buildpath("/companyrelationships/admin/about.php", 1);
    $head[$h][1] = $langs->trans("About");
    $head[$h][2] = 'about';
    $h++;

    $head[$h][0] = dol_buildpath("/companyrelationships/admin/changelog.php", 1);
    $head[$h][1] = $langs->trans("OpenDsiChangeLog");
    $head[$h][2] = 'changelog';
    $h++;

    complete_head_from_modules($conf,$langs,null,$head,$h,'companyrelationships_admin');

    return $head;
}

/**
 *  Show html area for list of companies relationship
 *
 * @param	Conf		$conf		Object conf
 * @param	Translate	$langs		Object langs
 * @param	DoliDB		$db			Database handler
 * @param	Societe		$object		Third party object
 * @param	int	        $mode		0: list principal companies, 1: list benefactor companies
 * @throws
 * @return	void
 */
function companyrelationships_show_companyrelationships($conf, $langs, $db, $object, $mode=0)
{
    global $user;
    global $action, $bc, $form;

    $prefix = $mode ? 'benefactor_' : 'principal_';

    $rowid = GETPOST('rowid', 'int');
    $list_mode = GETPOST("list_mode", 'int');
    $sortfield = GETPOST($prefix . "sortfield", 'alpha') ? GETPOST($prefix . "sortfield", 'alpha') : ($list_mode == $mode ? GETPOST("sortfield", 'alpha') : '');
    $sortorder = GETPOST($prefix . "sortorder", 'alpha') ? GETPOST($prefix . "sortorder", 'alpha') : ($list_mode == $mode ? GETPOST("sortorder", 'alpha') : '');
    $page = GETPOST($prefix . "page", 'int') ? GETPOST($prefix . "page", 'int') : ($list_mode == $mode ? GETPOST($prefix . "page", 'int') : '');
    $search_name = GETPOST($prefix . "search_name", 'alpha');
    $search_addressphone = GETPOST($prefix . "search_addressphone", 'alpha');
    $search_status = GETPOST($prefix . "search_status", 'int');
    if ($search_status == '') $search_status = 1; // always display activ customer first

    if (!$sortorder) $sortorder = "ASC";
    if (!$sortfield) $sortfield = "s.nom";

    $companystatic = new Societe($db);

    dol_include_once('/companyrelationships/class/companyrelationships.class.php');
    $companyrelationships = new CompanyRelationships($db);

    $modename = ($mode ? 'benefactor' : 'principal');
    $htmlname_main = $mode ? 'principal_socid' : 'benefactor_socid';
    $htmlname_choice = $mode ? 'benefactor_socid' : 'principal_socid';
    $label_choice = $langs->trans($mode ? 'CompanyRelationshipsBenefactorCompany' : 'CompanyRelationshipsPrincipalCompany');

    // Get all companies already into relationship
    $exclude_companies = array($object->id);
    $companies_into_relationship = $companyrelationships->getRelationships($object->id, 2);
    if (is_array($companies_into_relationship)) $exclude_companies = array_merge($exclude_companies, $companies_into_relationship);

    // Confirm update relationship
    if ($list_mode == $mode && $user->rights->societe->creer && $action == 'edit_relationship') {
        // Remove selected company to excluded
        $selected = GETPOST('edit_'.$htmlname_choice, 'int');
        $exclude_ids = $exclude_companies;
        if ($selected > 0) $exclude_ids = array_diff($exclude_ids, array($selected));
        $select_company_filter = 's.rowid NOT IN (' . implode(',', $exclude_ids) . ')';

        $formquestion = array(
            array(
                'name' => 'edit_'.$htmlname_choice,
                'label' => $label_choice,
                'type' => 'other',
                'value' => $form->select_company($selected, 'edit_'.$htmlname_choice, $select_company_filter, 'SelectThirdParty', 0, 0, array(), 0, 'minwidth200')
            )
        );

        $inputElements = '';
        if ($user->rights->companyrelationships->update_md->relationship) {
            $publicSpaceAvailbilityList = ($mode ? $companyrelationships->getAllPublicSpaceAvailability($object->id, $selected) : $companyrelationships->getAllPublicSpaceAvailability($selected, $object->id));
            if (is_array($publicSpaceAvailbilityList)) {
                $inputElementsNameArray = array();
                foreach ($publicSpaceAvailbilityList as $publicSpaceAvailability) {
                    $inputElementName    = 'publicspaceavailability_' . $publicSpaceAvailability['element'];
                    $inputElementChecked = '';
                    if (intval($publicSpaceAvailability[$modename]) > 0) {
                        $inputElementChecked = 'checked="checked"';
                    }
                    $inputElements .= '<input type="checkbox" id="' . $inputElementName .'" ' . 'name="' . $inputElementName . '" value="1" '. $inputElementChecked . ' /> ' . $publicSpaceAvailability['label'] . '<br />';
                    $inputElementsNameArray[] = $inputElementName;
                }

                $formquestion[] = array(
                    'name' => $inputElementsNameArray,
                    'type' => 'onecolumn',
                    'value' => '<div id="publicspaceavailability">' . $inputElements . '</div>'
                );
            }

        }

        print $form->formconfirm($_SERVER['PHP_SELF'] . '?socid=' . $object->id . '&list_mode=' . $list_mode . '&rowid=' . $rowid . '&edit_' . $htmlname_main . '=' . $object->id, $langs->trans("CompanyRelationshipsEditCompanyRelationships"), $langs->trans("CompanyRelationshipsConfirmEditCompanyRelationships"), "confirm_update_relationship", $formquestion, 0, 1, 300, 600);

        if ($user->rights->companyrelationships->update_md->relationship) {
            $out = '<script type="text/javascript" language="javascript">';
            $out .= 'jQuery(document).ready(function(){';
            $out .= '   jQuery("#edit_' . $htmlname_choice . '").change(function(){';
            $out .= '       jQuery.ajax({';
            $out .= '           data: {';
            if ($mode) {
                $out .= '       socid: ' . $object->id . ',';
                $out .= '       socid_benefactor: jQuery("#edit_' . $htmlname_choice . '").val(),';
            } else {
                $out .= '       socid: jQuery("#edit_' . $htmlname_choice . '").val(),';
                $out .= '       socid_benefactor: ' . $object->id . ',';
            }
            $out .= '           element: ""';
            $out .= '           },';
            $out .= '           dataType: "json",';
            $out .= '           method: "POST",';
            $out .= '           url: "' . dol_buildpath('/companyrelationships/ajax/publicspaceavailability.php', 1) . '",';
            $out .= '           success: function(dataList){';
            $out .= '               var nbElement = dataList.length;';
            $out .= '               if (nbElement > 0) {';
            //$out .= '                   var inputElements = "";';
            $out .= '                   for (var i=0; i<nbElement; i++) {';
            //$out .= '                       inputElements += \'<input type="checkbox" id="publicspaceavailability_\' + dataList[i].element + \'" name="publicspaceavailability_\' + dataList[i].element + \'" value="\' + dataList[i].principal + \'" /> \' + dataList[i].label + \'<br />\';';
            $out .= '                       jQuery("input[name=\'publicspaceavailability[" + dataList[i].rowid + "]\']").attr("checked", dataList[i].' . $modename . ');';
            $out .= '                   }';
            //$out .= '                   jQuery("#publicspaceavailability").html(inputElements);';
            $out .= '               }';
            $out .= '           },';
            $out .= '           error: function(){';
            $out .= '               jQuery("#publicspaceavailability").html("Error");';
            $out .= '           }';
            $out .= '       });';
            $out .= '   });';
            $out .= '});';
            $out .= '</script>';
            print $out;
        }
    }

    // Confirm deleting relationship
    if ($list_mode == $mode && $user->rights->societe->creer && $action == 'delete_relationship') {
        print $form->formconfirm($_SERVER['PHP_SELF'] . '?socid=' . $object->id . '&list_mode=' . $list_mode . '&rowid=' . $rowid, $langs->trans("CompanyRelationshipsDeleteCompanyRelationships"), $langs->trans("CompanyRelationshipsConfirmDeleteCompanyRelationships"), "confirm_delete_relationship", '', 0, 1);
    }

    print "\n";

    $title = $langs->trans($mode ? "CompanyRelationshipsListOfBenefactorCompanies" : "CompanyRelationshipsListOfPrincipalCompanies");
    print load_fiche_titre($title);

    if ($user->rights->societe->creer) {
        // Remove selected company to excluded
        $selected = $list_mode == $mode && $action == 'add_relationship' ? GETPOST('add_'.$htmlname_choice, 'int') : '';
        $exclude_ids = $exclude_companies;
        if ($selected > 0) $exclude_ids = array_diff($exclude_ids, array($selected));
        $select_company_filter = 's.rowid NOT IN (' . implode(',', $exclude_ids) . ')';

        print '<form method="GET" action="' . $_SERVER["PHP_SELF"] . '" name="formfilter">';
        print '<input type="hidden" name="action" value="add_relationship">';
        print '<input type="hidden" name="socid" value="' . $object->id . '">';
        print '<input type="hidden" name="' . $prefix . 'sortorder" value="' . $sortorder . '">';
        print '<input type="hidden" name="' . $prefix . 'sortfield" value="' . $sortfield . '">';
        print '<input type="hidden" name="' . $prefix . 'page" value="' . $page . '">';
        print '<input type="hidden" name="list_mode" value="' . $mode . '">';
        print '<input type="hidden" name="add_' . $htmlname_main . '" value="' . $object->id . '">';

        print '<table class="noborder" width="100%">' . "\n";
        print "<tr>\n";
        print "<td>" . $label_choice . "</td>\n";
        print "<td>" . $form->select_company($selected, 'add_'.$htmlname_choice, $select_company_filter, 'SelectThirdParty', 0, 0, array(), 0, 'minwidth200') . "</td>\n";
        print "<td width='150px' align='center'><input type='submit' class='button' name='add_relationship' id='add_relationship' value='" . $langs->trans('Add') . "'></td>\n";
        print "</tr>\n";
        print "</table>\n";

        print "</form>\n";
    }

    print '<form method="GET" action="' . $_SERVER["PHP_SELF"] . '" name="formfilter">';
    print '<input type="hidden" name="socid" value="' . $object->id . '">';
    print '<input type="hidden" name="' . $prefix . 'sortorder" value="' . $sortorder . '">';
    print '<input type="hidden" name="' . $prefix . 'sortfield" value="' . $sortfield . '">';
    print '<input type="hidden" name="' . $prefix . 'page" value="' . $page . '">';
    print '<input type="hidden" name="list_mode" value="' . $mode . '">';

    print "\n" . '<table class="noborder" width="100%">' . "\n";

    $param = "socid=" . $object->id . '&list_mode=' . $mode;
    if ($search_name != '') $param .= '&' . $prefix . 'search_name=' . urlencode($search_name);
    if ($search_addressphone != '') $param .= '&' . $prefix . 'search_addressphone=' . urlencode($search_addressphone);
    if ($search_status != '') $param .= '&' . $prefix . 'search_status=' . $search_status;

    $colspan = 4;
    print '<tr class="liste_titre">';
    print_liste_field_titre("Name", $_SERVER["PHP_SELF"], "s.nom", "", $param, ' width="30%"', $sortfield, $sortorder);
    print_liste_field_titre($langs->trans("Address") . ' / ' . $langs->trans("Phone") . ' / ' . $langs->trans("Email"), $_SERVER["PHP_SELF"], "", "", $param, '', $sortfield, $sortorder);
    print_liste_field_titre("Status", $_SERVER["PHP_SELF"], "s.statut", "", $param, ' width="150px"', $sortfield, $sortorder);
    // Edit
    print_liste_field_titre('', '', '', '', '', ' width="50px" align="right"');
    print "</tr>\n";

    $sql_key_join = $mode ? 'fk_soc_benefactor' : 'fk_soc';
    $sql_key_search = $mode ? 'fk_soc' : 'fk_soc_benefactor';

    $sql = "SELECT cr.rowid";
    $sql .= ", s.rowid as socid, s.entity, s.canvas, s.logo, s.status";
    $sql .= ", s.nom as name, s.name_alias";
    $sql .= ", s.client, s.fournisseur, s.code_client, s.code_fournisseur, s.code_compta, s.code_compta_fournisseur";
    $sql .= ", s.address, s.zip, s.town, s.fk_pays as country_id, c.code as country_code, s.fk_departement, d.code_departement as state_code";
    $sql .= ", s.email, s.skype, s.url, s.phone, s.fax";
    $sql .= " FROM " . MAIN_DB_PREFIX . "companyrelationships as cr";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as s ON (s.rowid = cr.$sql_key_join)";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_country as c ON (c.rowid = s.fk_pays)";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_departements as d ON (d.rowid = s.fk_departement)";
    // We'll need this table joined to the select in order to filter by sale
    if (!$user->rights->societe->client->voir) $sql .= ", " . MAIN_DB_PREFIX . "societe_commerciaux as sc";
    $sql .= " WHERE s.entity IN (" . getEntity('societe') . ")";
    if (!$user->rights->societe->client->voir) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " . $user->id;
    $sql .= " AND cr.$sql_key_search = " . $object->id;
    if ($search_name) $sql .= natural_search(array("s.nom", "s.name_alias"), $search_name);
    if ($search_addressphone) $sql .= natural_search(array("s.address", "s.zip", "s.town", "c.label", "d.nom", "s.email", "s.skype", "s.url", "s.phone", "s.fax"), $search_addressphone);
    if ($search_status >= 0) $sql .= " AND s.status = " . $db->escape($search_status);
    $sql .= " ORDER BY $sortfield $sortorder";

    dol_syslog('/companyrelationships/lib/companyrelationships.lib.php :: companyrelationships_show_companyrelationships', LOG_DEBUG);
    $result = $db->query($sql);
    if (!$result) dol_print_error($db);

    $var = true;
    $num = $db->num_rows($result);
    if ($num || (GETPOST('button_search') || GETPOST('button_search.x') || GETPOST('button_search_x'))) {
        print '<tr class="liste_titre">';

        // Name
        print '<td class="liste_titre">';
        print '<input type="text" class="flat" name="' . $prefix . 'search_name" size="20" value="' . dol_escape_htmltag($search_name) . '">';
        print '</td>';

        // Address - Phone - Email
        print '<td class="liste_titre">';
        print '<input type="text" class="flat" name="' . $prefix . 'search_addressphone" size="20" value="' . dol_escape_htmltag($search_addressphone) . '">';
        print '</td>';

        // Status
        print '<td class="liste_titre maxwidthonsmartphone">';
        print $form->selectarray($prefix . 'search_status', array('-1' => '', '0' => $companystatic->LibStatut(0, 1), '1' => $companystatic->LibStatut(1, 1)), $search_status);
        print '</td>';

        // Edit
        print '<td class="liste_titre" align="right">';
        print '<input type="image" class="liste_titre" name="button_search" src="' . img_picto($langs->trans("Search"), 'search.png', '', '', 1) . '" value="' . dol_escape_htmltag($langs->trans("Search")) . '" title="' . dol_escape_htmltag($langs->trans("Search")) . '">';
        print '</td>';

        print "</tr>";

        $i = 0;
        while ($i < $num) {
            $obj = $db->fetch_object($result);

            $companystatic->id = $obj->socid;
            $companystatic->entity = $obj->entity;
            $companystatic->canvas = $obj->canvas;
            $companystatic->logo = $obj->logo;
            $companystatic->status = $obj->status;

            $companystatic->name = $obj->name;
            $companystatic->name_alias = $obj->name_alias;

            $companystatic->client = $obj->client;
            $companystatic->fournisseur = $obj->fournisseur;
            $companystatic->code_client = $obj->code_client;
            $companystatic->code_fournisseur = $obj->code_fournisseur;
            $companystatic->code_compta = $obj->code_compta;
            $companystatic->code_compta_fournisseur = $obj->code_compta_fournisseur;

            $companystatic->address = $obj->address;
            $companystatic->zip = $obj->zip;
            $companystatic->town = $obj->town;
            $companystatic->country_id = $obj->country_id;
            $companystatic->country_code = $obj->country_id ? $obj->country_code : '';
            $companystatic->state_id = $obj->fk_departement;
            $companystatic->state_code = $obj->state_code;

            $companystatic->email = $obj->email;
            $companystatic->skype = $obj->skype;
            $companystatic->url = $obj->url;
            $companystatic->phone = $obj->phone;
            $companystatic->fax = $obj->fax;

            print "<tr>";

            // Name
            print '<td>';
            print $companystatic->getNomUrl(1);
            print '</td>';

            // Address - Phone - Email
            print '<td>';
            print $companystatic->getBannerAddress('societe', $object);
            print '</td>';

            // Status
            print '<td>' . $companystatic->getLibStatut(5) . '</td>';

            // Edit
            if ($user->rights->societe->creer) {
                print '<td align="right">';
                print '<a href="' . $_SERVER['PHP_SELF'] . '?action=edit_relationship&socid=' . $object->id . '&rowid=' . $obj->rowid . '&list_mode=' . $mode . '&edit_' . $htmlname_choice . '=' . $obj->socid . '">' . img_edit() . '</a>';
                print '<a href="' . $_SERVER['PHP_SELF'] . '?action=delete_relationship&socid=' . $object->id . '&rowid=' . $obj->rowid . '&list_mode=' . $mode . '">' . img_delete() . '</a>';
                print '</td>';
            } else print '<td>&nbsp;</td>';

            print "</tr>\n";
            $i++;
        }
    } else {
        print "<tr " . $bc[!$var] . ">";
        print '<td colspan="' . $colspan . '" class="opacitymedium">' . $langs->trans("None") . '</td>';
        print "</tr>\n";
    }
    print "\n</table>\n";

    print '</form>' . "\n";
}
