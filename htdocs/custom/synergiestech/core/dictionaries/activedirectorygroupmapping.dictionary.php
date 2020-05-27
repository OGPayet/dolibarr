<?php
/* Copyright (C) 2018  Open-Dsi <support@open-dsi.fr>
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
 * \file        core/dictionaries/activedirectorygroupmapping.dictionary.php
 * \ingroup     synergiestech
 * \brief       Class of the Time Slot
 */

dol_include_once('/advancedictionaries/class/dictionary.class.php');
dol_include_once('/core/class/ldap.class.php');

/**
 * Class for SynergiesTechActiveDirectoryGroupMappingDictionary
 */
class ActiveDirectoryGroupMappingDictionary extends Dictionary
{
    /**
     * @var array       List of languages to load
     */
    public $langs = array('synergiestech@synergiestech');

    /**
     * @var string      Family name of which this dictionary belongs
     */
    public $family = 'synergiestech';

    /**
     * @var string      Family label for show in the list, translated if key found
     */
    public $familyLabel = 'Module500100Name';

    /**
     * @var int         Position of the dictionary into the family
     */
    public $familyPosition = 6;

    /**
     * @var string      Module name of which this dictionary belongs
     */
    public $module = 'synergiestech';

    /**
     * @var string      Module name for show in the list, translated if key found
     */
    public $moduleLabel = 'Module500100Name';

    /**
     * @var string      Name of this dictionary for show in the list, translated if key found
     */
    public $nameLabel = 'SynergiesTechActiveDirectoryGroupMappingLabel';

    /**
     * @var string      Name of the dictionary table without prefix (ex: c_country)
     */
    public $table_name = 'c_synergiestech_ad_group';

    /**
     * @var array  Fields of the dictionary table
     * 'name' => array(
     *   'name'       => string,         // Name of the field
     *   'label'      => string,         // Label of the field, translated if key found
     *   'type'       => string,         // Type of the field (varchar, text, int, double, date, datetime, boolean, price, phone, mail, url,
     *                                                         password, select, sellist, radio, checkbox, chkbxlst, link, custom)
     *   'database' => array(            // Description of the field in the database always rewrite default value if set
     *     'type'      => string,        // Data type
     *     'length'    => string,        // Length of the data type (require)
     *     'default'   => string,        // Default value in the database
     *   ),
     *   'is_require' => bool,           // Set at true if this field is required
     *   'options'    => array()|string, // Parameters same as extrafields (ex: 'table:label:rowid::active=1' or array(1=>'value1', 2=>'value2') )
     *                                      string: sellist, chkbxlst, link | array: select, radio, checkbox
     *                                      The key of the value must be not contains the character ',' and for chkbxlst it's a rowid
     *   'is_not_show'       => bool,    // Set at true if this field is not show must be set at true if you want to search or edit
     *   'td_title'          => array (
     *      'moreClasses'    => string,  // Add more classes in the title balise td
     *      'moreAttributes' => string,  // Add more attributes in the title balise td
     *      'align'          => string,  // Overwrirte the align by default
     *   ),
     *   'td_output'         => array (
     *      'moreClasses'    => string,  // Add more classes in the output balise td
     *      'moreAttributes' => string,  // Add more attributes in the output balise td
     *      'align'          => string,  // Overwrirte the align by default
     *   ),
     *   'show_output'       => array (
     *      'moreAttributes' => string,  // Add more attributes in when show output field
     *   ),
     *   'is_not_searchable' => bool,    // Set at true if this field is not searchable
     *   'td_search'         => array (
     *      'moreClasses'    => string,  // Add more classes in the search input balise td
     *      'moreAttributes' => string,  // Add more attributes in the search input balise td
     *      'align'          => string,  // Overwrirte the align by default
     *   ),
     *   'show_search_input' => array (
     *      'size'           => int,     // Size attribute of the search input field (input text)
     *      'moreClasses'    => string,  // Add more classes in the search input field
     *      'moreAttributes' => string,  // Add more attributes in the search input field
     *   ),
     *   'is_not_addable'    => bool,    // Set at true if this field is not addable
     *   'is_not_editable'   => bool,    // Set at true if this field is not editable
     *   'td_input'         => array (
     *      'moreClasses'    => string,  // Add more classes in the input balise td
     *      'moreAttributes' => string,  // Add more attributes in the input balise td
     *      'align'          => string,  // Overwrirte the align by default
     *   ),
     *   'show_input'        => array (
     *      'moreClasses'    => string,  // Add more classes in the input field
     *      'moreAttributes' => string,  // Add more attributes in the input field
     *   ),
     *   'help' => '',                   // Help text for this field or url, translated if key found
     *   'is_not_sortable'   => bool,    // Set at true if this field is not sortable
     *   'min'               => int,     // Value minimum (include) if type is int, double or price
     *   'max'               => int,     // Value maximum (include) if type is int, double or price
     * )
     */
    public $fields = array(
        'dolibarrGroup' => array(),
        'linkEntity' => array(),
        'activeDirectoryGroup' => array()
    );

    /**
     * @var bool    Is multi entity (false = partaged, true = by entity)
     */
    public $is_multi_entity = false;

    /**
     * Initialize the dictionary
     *
     * @return  void
     */
    protected function initialize()
    {
        global $conf;


        $this->fields['dolibarrGroup'] = array(
            'name' => 'dolibarrGroup',
            'label' => 'SynergiesTechGroupDictionaryLabel',
            'type' => 'chkbxlst',
            'options' => 'usergroup:nom:rowid::entity IN (' . getEntity('usergroup') . ')',
            'is_require' => true,
            'td_input' => array(
                'moreAttributes' => 'width="33.33%"',
            ),
        );
        $this->fields['linkEntity'] = array(
            'name' => 'linkEntity',
            'label' => 'SynergiesTechGroupEntityLabel',
            'type' => 'chkbxlst',
            'options' => 'entity:label:rowid::active = 1',
            'is_require' => true,
            'td_input' => array(
                'moreAttributes' => 'width="33.33%"',
            ),
        );

        $availableGroupList = array();

        if (! function_exists("ldap_connect"))
		{
			$this->error='LDAPFunctionsNotAvailableOnPHP';
			dol_syslog(get_class($this)."::connect_bind ".$this->error, LOG_WARNING);
			return -1;
		}
        $ldap = new Ldap();
        $result = $ldap->connect_bind();

        if ($result >= 0 || true) {
            // List of fields to get from LDAP
            $required_fields = array(
                $conf->global->LDAP_KEY_GROUPS,
                $conf->global->LDAP_GROUP_FIELD_FULLNAME,
                $conf->global->LDAP_GROUP_FIELD_DESCRIPTION,
                $conf->global->LDAP_GROUP_FIELD_GROUPMEMBERS,
            );

            // Remove from required_fields all entries not configured in LDAP (empty) and duplicated
            $required_fields = array_unique(array_values(array_filter($required_fields, "dolValidElement")));

            if ($conf->global->LDAP_SERVER_TYPE == 'activedirectory') {
                $ldapRecords = $ldap->getRecords('*', $conf->global->LDAP_GROUP_DN, 'cn', $required_fields, "*");
            } else {
                $ldapRecords = $ldap->getRecords('*', $conf->global->LDAP_GROUP_DN, $conf->global->LDAP_KEY_GROUPS, $required_fields);
            }
            if (is_array($ldapRecords)) {
                foreach($ldapRecords as $cn=>$ldapObject){
                    $availableGroupList[$cn] = $ldapObject[$conf->global->LDAP_GROUP_FIELD_FULLNAME];
                }
            }
        }

        $this->fields['activeDirectoryGroup'] = array(
            'name' => 'activeDirectoryGroup',
            'label' => 'SynergiesTechActiveDirectoryGroupLabel',
            'type' => 'checkbox',
            'options' => $availableGroupList,
            'is_require' => true,
            'td_input' => array(
                'moreAttributes' => 'width="33.33%"',
            ),
        );
    }
}
