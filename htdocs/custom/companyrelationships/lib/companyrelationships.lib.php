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

dol_include_once('/companyrelationships/class/companyrelationships.class.php');

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
 * Form confirm of thirdparty in relation
 *
 * @param	DoliDB		            $db			            Database handler
 * @param	Societe		            $societe                Thirdparty object
 * @param	CompanyRelationships    $companyrelationships   Relation thirdpaty object
 * @param	int	                    $relation_type		    Relation type
 * @return  string
 *
 * @throws  Exception
 */
function companyrelationships_formconfirm_relation_thirdparty($db, $societe, CompanyRelationships $companyrelationships, $relation_type)
{
    global $langs, $user;
    global $action, $form;

    $formconfirm = '';

    // Confirm update thirdparty relationship
    $relation_type_name = $companyrelationships->getRelationTypeName($relation_type);
    // get relation thirdparty
    $relationThirdparty = $companyrelationships->getRelationshipThirdparty($societe->id, $relation_type);
    $relationThirdparty = is_object($relationThirdparty) ? $relationThirdparty : NULL;

    $relationThirdpartySelectedId = $relationThirdparty ? $relationThirdparty->id : '';

    if ($action=='edit_relationship_'.$relation_type_name && $user->rights->societe->creer && !empty($relationThirdpartySelectedId)) {
        $thirdparty_htmlname = $relation_type_name . '_socid';
        $thirdparty_label    = $langs->trans('CompanyRelationships' . ucfirst($relation_type_name) . 'Company');

        $formquestion = array(
            array(
                'name'  => $thirdparty_htmlname,
                'label' => $thirdparty_label,
                'type'  => 'other',
                'value' => $form->select_company($relationThirdpartySelectedId, $thirdparty_htmlname, '', '', 0, 0, array(), 0, 'maxwidth300')
            )
        );

        if ($user->rights->companyrelationships->update_md->relationship) {
            $publicSpaceAvailabilityList = $companyrelationships->getAllPublicSpaceAvailabilityThirdparty($societe->id, $relation_type, $relationThirdpartySelectedId);
            if (is_array($publicSpaceAvailabilityList)) {
                $inputElements = '';
                $inputElementsNameArray = array();

                foreach ($publicSpaceAvailabilityList as $publicSpaceAvailability) {
                    $inputElementName    = 'publicspaceavailability_' . $publicSpaceAvailability['element'];
                    $inputElementChecked = '';
                    if (intval($publicSpaceAvailability[$relation_type_name]) > 0) {
                        $inputElementChecked = 'checked="checked"';
                    }
                    $value = '<input type="checkbox" id="' . $inputElementName .'" ' . 'name="' . $inputElementName . '" value="1" '. $inputElementChecked . ' /> ' . $publicSpaceAvailability['label'] . '<br />';
                    //$inputElementsNameArray[] = $inputElementName;
                    $formquestion[] = array(
                        'name' => $inputElementName,
                        'type' => 'onecolumn',
                        'value' => $value
                    );
                }

                // $formquestion[] = array(
                //     'name' => $inputElementsNameArray,
                //     'type' => 'onecolumn',
                //     'value' => '<div id="publicspaceavailability">' . $inputElements . '</div>'
                // );
            }
        }

        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?socid=' . $societe->id . '&relation_type=' . $relation_type . '&' . $thirdparty_htmlname . '=' . $societe->id, $langs->trans("CompanyRelationshipsEditCompanyRelationships"), $langs->trans("CompanyRelationshipsConfirmEditCompanyRelationships"), 'confirm_update_relationship_' . $relation_type_name, $formquestion, 0, 1, 400, 600);
    }

    // Confirm deleting relationship
    if ($action=='delete_relationship_'.$relation_type_name && $user->rights->societe->creer) {
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?socid=' . $societe->id . '&relation_type=' . $relation_type, $langs->trans("CompanyRelationshipsDeleteCompanyRelationships"), $langs->trans("CompanyRelationshipsConfirmDeleteCompanyRelationships"), 'confirm_delete_relationship_' . $relation_type_name, '', 0, 1);
    }

    return $formconfirm;
}

/**
 * Show html line of thirdpaty in relation
 *
 * @param	DoliDB		            $db			            Database handler
 * @param	Societe		            $societe                Thirdparty object
 * @param	CompanyRelationships    $companyrelationships   Relation thirdpaty object
 * @param	int	                    $relation_type		    Relation type
 * @return  void
 *
 * @throws  Exception
 */
function companyrelationships_show_relation_thirdparty($db, $societe, CompanyRelationships $companyrelationships, $relation_type)
{
    global $langs, $user;
    global $action, $form;

    $relation_type_name = $companyrelationships->getRelationTypeName($relation_type);
    // get relation thirdparty
    $relationThirdparty = $companyrelationships->getRelationshipThirdparty($societe->id, $relation_type);
    $relationThirdparty = is_object($relationThirdparty) ? $relationThirdparty : NULL;

    $relationThirdpartySelectedId = $relationThirdparty ? $relationThirdparty->id : '';

    print '<tr><td>';
    print '<table class="nobordernopadding" width="100%"><tr><td>';
    print $langs->trans('CompanyRelationshipsWatcherCompany');
    print '</td>';
    if ($action!='edit_thirdparty_'.$relation_type_name && $user->rights->societe->creer)
        print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=edit_thirdparty_'.$relation_type_name . '&socid=' . $societe->id . '&relation_type=' . $relation_type . '">' . img_edit($langs->trans('CompanyRelationshipsSetThirdpartyWatcher'), 1) . '</a></td>';
    print '</tr></table>';
    print '</td><td>';
    if ($action=='edit_thirdparty_'.$relation_type_name && $user->rights->societe->creer) {
        print '<form name="edit_thirdparty_'.$relation_type_name.'" action="' . $_SERVER["PHP_SELF"] . '?socid=' . $societe->id . '" method="post">';
        print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '" />';
        print '<input type="hidden" name="action" value="set_thirdparty_'.$relation_type_name.'" />';
        print '<input type="hidden" name="relation_type" value="'.$relation_type.'" />';
        print $form->select_company($relationThirdpartySelectedId, $relation_type_name.'_socid', '(s.client = 1 OR s.client = 2 OR s.client = 3) AND status=1', 'SelectThirdparty', 0, 0, null, 0, 'minwidth300');
        print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '" />';
        print '</form>';
    } else {
        if ($relationThirdparty) {
            print $companyrelationships->getNomUrlForSociete($relationThirdparty, 1, 'companyrelationships');
        }
    }
    print '</td></tr>';
}

/**
 * Show html line of thirdparty public space availability in relation
 *
 * @param	DoliDB		            $db			            Database handler
 * @param	Societe		            $societe                Thirdparty object
 * @param	CompanyRelationships    $companyrelationships   Relation thirdpaty object
 * @param	int	                    $relation_type		    Relation type
 * @return  void
 *
 * @throws  Exception
 */
function companyrelationships_show_relation_psa($db, $societe, $companyrelationships, $relation_type)
{
    global $langs, $user;
    global $action;

    $relation_type_name = $companyrelationships->getRelationTypeName($relation_type);
    // get relation thirdparty
    $relationThirdparty = $companyrelationships->getRelationshipThirdparty($societe->id, $relation_type);
    $relationThirdparty = is_object($relationThirdparty) ? $relationThirdparty : NULL;

    $relationThirdpartyId = $relationThirdparty ? $relationThirdparty->id : 0;
    $publicSpaceAvailabilityList = array();
    if ($relationThirdpartyId > 0) {
        $publicSpaceAvailabilityList = $companyrelationships->getAllPublicSpaceAvailabilityThirdparty($societe->id, $relation_type, $relationThirdpartyId);
    }
    $htmlPublicSpaceAvailabilityList = array($relation_type => '');
    if (is_array($publicSpaceAvailabilityList)) {
        foreach($publicSpaceAvailabilityList as $publicSpaceAvailability) {
            if ($publicSpaceAvailability[$relation_type_name]==1) {
                $htmlPublicSpaceAvailabilityList[$relation_type] .= $publicSpaceAvailability['label'] . '<br />';
            }
        }
    }

    print '<tr><td>';
    print '<table class="nobordernopadding" width="100%"><tr><td>';
    print $langs->trans('CompanyRelationshipsPublicSpaceAvailability' . ucfirst($relation_type_name));
    print '</td>';
    if ($action!='edit_thirdparty_'.$relation_type_name  && $user->rights->societe->creer) {
        print '<td align="right">';
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=edit_relationship_' . $relation_type_name . '&socid=' . $societe->id . '&relation_type=' . $relation_type . '">' . img_edit() . '</a>';
        //print '<a href="' . $_SERVER['PHP_SELF'] . '?action=delete_relationship_' . $relation_type_name . '&socid=' . $societe->id . '&relation_type=' . $relation_type . '">' . img_delete() . '</a>';
        print '</td>';
    }
    print '</tr></table>';
    print '</td><td>';
    print $htmlPublicSpaceAvailabilityList[$relation_type];
    print '</td></tr>';
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
    $companyrelationships = new CompanyRelationships($db);

    $modename = $mode ? 'benefactor' : 'principal';
    $htmlname_main = $mode ? 'principal_socid' : 'benefactor_socid';
    $htmlname_choice = $mode ? 'benefactor_socid' : 'principal_socid';
    $label_choice = $langs->trans($mode ? 'CompanyRelationshipsBenefactorCompany' : 'CompanyRelationshipsPrincipalCompany');

    // Get all companies already into relationship
    $exclude_companies = array($object->id);
    $companies_into_relationship = $companyrelationships->getRelationshipsThirdparty($object->id, CompanyRelationships::RELATION_TYPE_BENEFACTOR, 2);
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

        if ($user->rights->companyrelationships->update_md->relationship) {
            $publicSpaceAvailbilityList = ($mode ? $companyrelationships->getAllPublicSpaceAvailabilityThirdparty($object->id, CompanyRelationships::RELATION_TYPE_BENEFACTOR, $selected) : $companyrelationships->getAllPublicSpaceAvailabilityThirdparty($selected, CompanyRelationships::RELATION_TYPE_BENEFACTOR, $object->id));
            if (is_array($publicSpaceAvailbilityList)) {
                $inputElements = '';
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

        print $form->formconfirm($_SERVER['PHP_SELF'] . '?socid=' . $object->id . '&list_mode=' . $list_mode . '&rowid=' . $rowid . '&edit_' . $htmlname_main . '=' . $object->id, $langs->trans("CompanyRelationshipsEditCompanyRelationships"), $langs->trans("CompanyRelationshipsConfirmEditCompanyRelationships"), "confirm_update_relationship", $formquestion, 0, 1, 400, 600);

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
            $out .= '           url: "' . dol_buildpath('/companyrelationships/ajax/allpublicspaceavailability.php', 1) . '",';
            $out .= '           success: function(dataList){';
            $out .= '               var nbElement = dataList.length;';
            $out .= '               if (nbElement > 0) {';
            $out .= '                   for (var i=0; i<nbElement; i++) {';
            $out .= '                       jQuery("#publicspaceavailability_" + dataList[i].element).prop("checked", dataList[i].' . $modename . ');';
            $out .= '                   }';
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
    print_liste_field_titre("Name", $_SERVER["PHP_SELF"], "s.nom", "", $param, '', $sortfield, $sortorder);
    print_liste_field_titre($langs->trans("Address") . ' / ' . $langs->trans("Phone") . ' / ' . $langs->trans("Email"), $_SERVER["PHP_SELF"], "", "", $param, '', $sortfield, $sortorder);
    if ($mode) {
        print_liste_field_titre('CompanyRelationshipsPublicSpaceAvailabilityBenefactor');
        print_liste_field_titre($langs->trans('CompanyRelationshipsPublicSpaceAvailabilityPrincipal') . '<br />' . $object->nom);
    } else {
        print_liste_field_titre('CompanyRelationshipsPublicSpaceAvailabilityPrincipal');
        print_liste_field_titre($langs->trans('CompanyRelationshipsPublicSpaceAvailabilityBenefactor') . '<br />' . $object->nom);
    }

    //print_liste_field_titre("Status", $_SERVER["PHP_SELF"], "s.statut", "", $param, ' width="150px"', $sortfield, $sortorder);
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

        // principal and benefactor companies
        print '<td class="liste_titre"></td>';
        print '<td class="liste_titre"></td>';

        // Status
        //print '<td class="liste_titre maxwidthonsmartphone">';
        //print $form->selectarray($prefix . 'search_status', array('-1' => '', '0' => $companystatic->LibStatut(0, 1), '1' => $companystatic->LibStatut(1, 1)), $search_status);
        //print '</td>';

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

            // get public space availability for all elements
            if ($mode) {
                // benefactor
                $publicSpaceAvailabilityList = $companyrelationships->getAllPublicSpaceAvailabilityThirdparty($object->id, CompanyRelationships::RELATION_TYPE_BENEFACTOR, $companystatic->id);
            } else {
                // principal
                $publicSpaceAvailabilityList = $companyrelationships->getAllPublicSpaceAvailabilityThirdparty($companystatic->id, CompanyRelationships::RELATION_TYPE_BENEFACTOR, $object->id);
            }
            $htmlPublicSpaceAvailabilityList = array(0 => '', 1 => '');
            if (is_array($publicSpaceAvailabilityList)) {
                foreach($publicSpaceAvailabilityList as $publicSpaceAvailability) {
                    if ($publicSpaceAvailability['principal']==1) {
                        $htmlPublicSpaceAvailabilityList[0] .= $publicSpaceAvailability['label'] . '<br />';
                    }

                    if ($publicSpaceAvailability['benefactor']==1) {
                        $htmlPublicSpaceAvailabilityList[1] .= $publicSpaceAvailability['label'] . '<br />';
                    }
                }
            }

            print "<tr>";

            // Name
            print '<td>';
            //print $companystatic->getNomUrl(1);
            print $companyrelationships->getNomUrlForSociete($companystatic, 1, 'companyrelationships');
            print '</td>';

            // Address - Phone - Email
            print '<td>';
            print $companystatic->getBannerAddress('societe', $object);
            print '</td>';

            // Public space availability
            if ($mode) {
                // public space availability for benefactor
                print '<td>';
                print $htmlPublicSpaceAvailabilityList[1];
                print '</td>';

                // public space availability for principal
                print '<td>';
                print $htmlPublicSpaceAvailabilityList[0];
                print '</td>';
            } else {
                // public space availability for principal
                print '<td>';
                print $htmlPublicSpaceAvailabilityList[0];
                print '</td>';

                // public space availability for benefactor
                print '<td>';
                print $htmlPublicSpaceAvailabilityList[1];
                print '</td>';
            }

            // Status
            //print '<td>' . $companystatic->getLibStatut(5) . '</td>';

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
