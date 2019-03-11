<?php
/* Copyright (C) 2018  Open-Dsi <support@open-dsi.fr>
 * Copyright (C) 2019      Alexis LAURIER        <alexis@alexislaurier.fr>
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
 * \file        core/dictionaries/requestmanagerstatus.dictionary.php
 * \ingroup     requestmanager
 * \brief       Class of the dictionary Status
 */

dol_include_once('/advancedictionaries/class/dictionary.class.php');

/**
 * Class for RequestManagerStatusDictionary
 */
class RequestManagerStatusDictionary extends Dictionary
{
    /**
     * @var int         Version of this dictionary
     */
    public $version = 12;

    /**
     * @var array       List of languages to load
     */
    public $langs = array('requestmanager@requestmanager');

    /**
     * @var string      Family name of which this dictionary belongs
     */
    public $family = 'requestmanager';

    /**
     * @var string      Family label for show in the list, translated if key found
     */
    public $familyLabel = 'Module163018Name';

    /**
     * @var int         Position of the dictionary into the family
     */
    public $familyPosition = 2;

    /**
     * @var string      Module name of which this dictionary belongs
     */
    public $module = 'requestmanager';

    /**
     * @var string      Module name for show in the list, translated if key found
     */
    public $moduleLabel = 'Module163018Name';

    /**
     * @var string      Name of this dictionary for show in the list, translated if key found
     */
    public $nameLabel = 'RequestManagerStatusDictionaryLabel';

    /**
     * @var string      Name of the dictionary table without prefix (ex: c_country)
     */
    public $table_name = 'c_requestmanager_status';

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
        'code' => array(
            'name'       => 'code',
            'label'      => 'Code',
            'type'       => 'varchar',
            'database'   => array(
              'length'   => 16,
            ),
            'is_require' => true,
        ),
        'label' => array(
            'name'       => 'label',
            'label'      => 'Label',
            'type'       => 'varchar',
            'database'   => array(
              'length'   => 255,
            ),
            'is_require' => true,
        ),
        'picto' => array(
            'name'       => 'picto',
            'label'      => 'RequestManagerStatusDictionaryPicto',
            'type'       => 'varchar',
            'database'   => array(
              'length'   => 255,
            ),
        ),
        'position' => array(
            'name'       => 'position',
            'label'      => 'Position',
            'type'       => 'int',
            'database'   => array(
              'length'   => 11,
            ),
            'td_title'  => array (
                'align'  => 'left',
            ),
            'td_output'  => array (
                'align'  => 'left',
            ),
            'td_search'  => array (
                'align'  => 'left',
            ),
            'td_input'  => array (
                'align'  => 'left',
            ),
        ),
        'type' => array(),
        'request_type' => array(),
        'operation' => array(
            'name'       => 'operation',
            'label'      => 'RequestManagerStatusDictionaryOperation',
            'help'       => 'RequestManagerStatusDictionaryOperationHelp',
            'type'       => 'int',
            'database'   => array(
              'length'   => 11,
            ),
            'td_title'  => array (
                'align'  => 'left',
            ),
            'td_output'  => array (
                'align'  => 'left',
            ),
            'td_search'  => array (
                'align'  => 'left',
            ),
            'td_input'  => array (
                'align'  => 'left',
            ),
        ),
        'operation_from_event' => array(
            'name'       => 'operation_from_event',
            'label'      => 'RequestManagerStatusDictionaryOperationFromEvent',
            'help'       => 'RequestManagerStatusDictionaryOperationFromEventHelp',
            'type'       => 'boolean',
        ),
        'deadline' => array(
            'name'       => 'deadline',
            'label'      => 'RequestManagerStatusDictionaryDeadLine',
            'help'       => 'RequestManagerStatusDictionaryDeadLineHelp',
            'type'       => 'int',
            'database'   => array(
              'length'   => 11,
            ),
            'td_title'  => array (
                'align'  => 'left',
            ),
            'td_output'  => array (
                'align'  => 'left',
            ),
            'td_search'  => array (
                'align'  => 'left',
            ),
            'td_input'  => array (
                'align'  => 'left',
                'positionLine' => 1,
            ),
        ),
        'deadline_rc_from_event' => array(
            'name'       => 'deadline_rc_from_event',
            'label'      => 'RequestManagerStatusDictionaryDeadlineRcFromEventEvent',
            'help'       => 'RequestManagerStatusDictionaryDeadlineRcFromEventEventHelp',
            'type'       => 'boolean',
            'td_input' => array(
                'positionLine' => 1,
            ),
        ),
        'deadline_from_event' => array(
            'name'       => 'deadline_from_event',
            'label'      => 'RequestManagerStatusDictionaryDeadlineFromEvent',
            'help'       => 'RequestManagerStatusDictionaryDeadlineFromEventHelp',
            'type'       => 'boolean',
            'td_input' => array(
                'positionLine' => 1,
            ),
        ),
        'close_all_event' => array(
            'name'       => 'close_all_event',
            'label'      => 'RequestManagerStatusDictionaryCloseAllEvent',
            'help'       => 'RequestManagerStatusDictionaryCloseAllEventHelp',
            'type'       => 'boolean',
            'td_input' => array(
                'positionLine' => 1,
            ),
        ),
        'authorized_user' => array(),
        'authorized_usergroup' => array(),
        'assigned_user_current' => array(
            'name'       => 'assigned_user_current',
            'label'      => 'RequestManagerStatusDictionaryAssignedUserCurrent',
            'help'       => 'RequestManagerStatusDictionaryAssignedUserCurrentHelp',
            'type'       => 'boolean',
            'td_input' => array(
                'positionLine' => 2,
            ),
        ),

        'assigned_user' => array(),
        'assigned_user_replaced' => array(
            'name'       => 'assigned_user_replaced',
            'label'      => 'RequestManagerStatusDictionaryAssignedUserReplaced',
            'help'       => 'RequestManagerStatusDictionaryAssignedUserReplacedHelp',
            'type'       => 'boolean',
            'td_input' => array(
                'positionLine' => 2,
            ),
        ),
        'assigned_usergroup' => array(),
        'assigned_usergroup_replaced' => array(
            'name'       => 'assigned_usergroup_replaced',
            'label'      => 'RequestManagerStatusDictionaryAssignedUserGroupReplaced',
            'help'       => 'RequestManagerStatusDictionaryAssignedUserGroupReplacedHelp',
            'type'       => 'boolean',
            'td_input' => array(
                'positionLine' => 2,
            ),
        ),
        'current_trigger' => array(
            'name'       => 'current_trigger',
            'label'      => 'RequestManagerStatusDictionaryCurrentTrigger',
            'help'       => 'RequestManagerStatusDictionaryCurrentTriggerHelp',
            'type'       => 'varchar',
            'database'   => array(
              'length'   => 255,
            ),
            'td_input' => array(
                'positionLine' => 3,
            ),
        ),
        'new_request_type' => array(),
        'new_request_type_auto' => array(
            'name'       => 'new_request_type_auto',
            'label'      => 'RequestManagerStatusDictionaryNewRequestTypeAuto',
            'help'       => 'RequestManagerStatusDictionaryNewRequestTypeAutoHelp',
            'type'       => 'boolean',
            'td_input' => array(
                'positionLine' => 3,
            ),
        ),
		'do_not_bloc_process' => array(
            'name'       => 'do_not_bloc_process',
            'label'      => 'RequestManagerStatusDictionaryDoNotBlocProcess',
            'help'       => 'RequestManagerStatusDictionaryDoNotBlocProcessHelp',
            'type'       => 'boolean',
            'td_input' => array(
                'positionLine' => 3,
            ),
        ),
        'new_request_type_next_status_auto' => array(
            'name'       => 'new_request_type_next_status_auto',
            'label'      => 'RequestManagerStatusDictionaryNewRequestTypeNextStatusAuto',
            'help'       => 'RequestManagerStatusDictionaryNewRequestTypeNextStatusAutoHelp',
            'type'       => 'boolean',
            'td_input' => array(
                'positionLine' => 3,
            ),
        ),
        'next_trigger' => array(
            'name'       => 'next_trigger',
            'label'      => 'RequestManagerStatusDictionaryNextTrigger',
            'help'       => 'RequestManagerStatusDictionaryNextTriggerHelp',
            'type'       => 'varchar',
            'database'   => array(
              'length'   => 255,
            ),
            'td_input' => array(
                'positionLine' => 3,
            ),
        ),
        'next_status_auto' => array(
            'name'       => 'next_status_auto',
            'label'      => 'RequestManagerStatusDictionaryNextStatusAuto',
            'help'       => 'RequestManagerStatusDictionaryNextStatusAutoHelp',
            'type'       => 'boolean',
            'td_input' => array(
                'positionLine' => 3,
            ),
        ),
        'next_status' => array(),
        'reason_resolution' => array(),
        'authorized_buttons' => array(),
    );

    /**
     * @var array  List of index for the database
     * array(
     *   'fields'    => array( ... ), // List of field name who constitute this index
     *   'is_unique' => bool,         // Set at true if this index is unique
     * )
     */
    public $indexes = array(
        0 => array(
            'fields'    => array('code'),
            'is_unique' => true,
        ),
    );

    /**
     * @var array  List of fields/indexes added, updated or deleted for a version
     * array(
     *   'version' => array(
     *     'fields' => array('field_name'=>'a', 'field_name'=>'u', 'field_name'=>'d', ...), // List of field name who is added(a) or updated(u) or deleted(d) for a version
     *     'indexes' => array('idx_number'=>'a', 'idx_number'=>'u', 'idx_number'=>'d', ...), // List of indexes number who is added(a) or updated(u) or deleted(d) for a version
     *   ),
     * )
     */
    public $updates = array(
        1 => array(
            'fields' => array(
                'assigned_user' => 'a',
                'assigned_usergroup' => 'a',
                'deadline' => 'a',
                'next_trigger' => 'a',
                'next_status' => 'a',
            )
        ),
        2 => array(
            'fields' => array(
                'operation' => 'a',
            )
        ),
        3 => array(
            'fields' => array(
                'picto' => 'u',
                'position' => 'u',
            )
        ),
        4 => array(
            'fields' => array(
                'assigned_user_replaced' => 'a',
                'assigned_usergroup_replaced' => 'a',
            )
        ),
        5 => array(
            'fields' => array(
                'new_request_type' => 'a',
            )
        ),
        6 => array(
            'fields' => array(
                'new_request_type_auto' => 'a',
            )
        ),
        7 => array(
            'fields' => array(
                'authorized_buttons' => 'a',
            )
        ),
        8 => array(
            'fields' => array(
                'authorized_user' => 'a',
                'authorized_usergroup' => 'a',
                'current_trigger' => 'a',
                'next_status_auto' => 'a',
            )
        ),
        9 => array(
            'fields' => array(
                'operation_from_event' => 'a',
                'deadline_rc_from_event' => 'a',
                'deadline_from_event' => 'a',
                'close_all_event' => 'a',
            )
        ),
        10 => array(
            'fields' => array(
                'new_request_type_next_status_auto' => 'a',
            )
        ),
        11 => array(
            'fields' => array(
                'assigned_user_current' => 'a',
            )
        ),
		12 => array(
            'fields' => array(
                'do_not_bloc_process' => 'a',
            )
        ),
    );

    /**
     * @var bool    Is multi entity (false = partaged, true = by entity)
     */
    public $is_multi_entity = true;

    /**
     * @var bool    Edit in the add form
     */
    public $edit_in_add_form = true;

    /**
     * @var Dictionary    Cache of the list of the request type for next_status show output
     */
    public $request_type_cache = null;
    /**
     * @var Dictionary    Cache of the list of the status for next_status show output
     */
    public $status_cache = null;

    /**
	 * Load the cache of the list of the request type for next_status show output
	 *
     * @return  void
	 */
    public function load_request_type()
    {
        if (!isset($this->request_type_cache)) {
            $this->request_type_cache = Dictionary::getDictionary($this->db, 'requestmanager', 'requestmanagerrequesttype');
            $this->request_type_cache->fetch_lines();
        }
    }

    /**
	 * Load the cache of the list of the status for next_status show output
	 *
     * @return  void
	 */
    public function load_status()
    {
        if (!isset($this->status_cache)) {
            $this->status_cache = new RequestManagerStatusDictionary($this->db);
            $this->status_cache->fetch_lines();
        }
    }

    /**
	 * Initialize the dictionary
	 *
     * @return  void
	 */
	protected function initialize()
    {
        global $conf, $langs, $user, $hookmanager;

        $langs->loadLangs(array('requestmanager@requestmanager', 'propal', 'orders', 'contracts', 'suppliers', 'projects', 'trips', 'bills', 'interventions', 'commercial'));

        $theme = $conf->theme;
        $path = 'theme/'.$theme;
        if (! empty($conf->global->MAIN_OVERWRITE_THEME_PATH)) $path = $conf->global->MAIN_OVERWRITE_THEME_PATH.'/theme/'.$theme;	// If the theme does not have the same name as the module
        else if (! empty($conf->global->MAIN_OVERWRITE_THEME_RES)) $path = $conf->global->MAIN_OVERWRITE_THEME_RES.'/theme/'.$conf->global->MAIN_OVERWRITE_THEME_RES;  // To allow an external module to overwrite image resources whatever is activated theme
        else if (! empty($conf->modules_parts['theme']) && array_key_exists($theme, $conf->modules_parts['theme'])) $path = $theme.'/theme/'.$theme;	// If the theme have the same name as the module
        $this->fields['picto']['help'] = $langs->trans('RequestManagerStatusDictionaryPictoHelp', $path);

        dol_include_once('/requestmanager/class/requestmanager.class.php');
        $this->fields['type'] = array(
            'name' => 'type',
            'label' => 'RequestManagerType',
            'type' => 'select',
            'options' => array(
                RequestManager::STATUS_TYPE_INITIAL => $langs->trans('RequestManagerTypeInitial'),
                RequestManager::STATUS_TYPE_IN_PROGRESS => $langs->trans('RequestManagerTypeInProgress'),
                RequestManager::STATUS_TYPE_RESOLVED => $langs->trans('RequestManagerTypeResolved'),
                RequestManager::STATUS_TYPE_CLOSED => $langs->trans('RequestManagerTypeClosed'),
            ),
            'is_require' => true,
        );
        $this->fields['request_type'] = array(
            'name' => 'request_type',
            'label' => 'RequestManagerRequestType',
            'type' => 'chkbxlst',
            'options' => 'c_requestmanager_request_type:label:rowid::active=1 and entity IN (' . getEntity('dictionary', 1) . ')',
            'td_output' => array(
                'moreAttributes' => 'width="20%"',
            ),
            'td_input' => array(
                'moreAttributes' => 'width="12.5%"',
            ),
            'is_require' => true,
        );

        require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
        $dol_sup_v6 = versioncompare(explode('.', DOL_VERSION), explode('.', '6.0.0')) >= 0;

        if ($dol_sup_v6) {
            if (!empty($conf->multicompany->enabled) && $conf->entity == 1 && ($conf->global->MULTICOMPANY_TRANSVERSE_MODE || ($user->admin && !$user->entity))) {
                $entity_filter = "entity IS NOT NULL";
            } else {
                $entity_filter = "entity IN (0," . $conf->entity . ")";
            }
        } else {
            if (!empty($conf->multicompany->enabled) && !empty($conf->multicompany->transverse_mode)) {
                $entity_filter = "entity IN (0,1)";
            } else {
                $entity_filter = "entity = " . $conf->entity;
            }
        }

        $this->fields['authorized_user'] = array(
            'name' => 'authorized_user',
            'label' => 'RequestManagerStatusDictionaryAuthorizedUser',
            'type' => 'chkbxlst',
            'options' => 'user:firstname|lastname:rowid::' . $entity_filter,
            'td_output' => array(
                'moreAttributes' => 'width="20%"',
            ),
            'td_input' => array(
                'moreAttributes' => 'width="25%"',
                'positionLine' => 1,
                'colspan' => 2,
            ),
        );

        $this->fields['authorized_usergroup'] = array(
            'name' => 'authorized_usergroup',
            'label' => 'RequestManagerStatusDictionaryAuthorizedUserGroup',
            'type' => 'chkbxlst',
            'options' => 'usergroup:nom:rowid::' . $entity_filter,
            'td_output' => array(
                'moreAttributes' => 'width="20%"',
            ),
            'td_input' => array(
                'moreAttributes' => 'width="25%"',
                'positionLine' => 1,
                'colspan' => 2,
            ),
        );

        $this->fields['assigned_user'] = array(
            'name' => 'assigned_user',
            'label' => 'RequestManagerStatusDictionaryAssignedUser',
            'type' => 'chkbxlst',
            'options' => 'user:firstname|lastname:rowid::' . $entity_filter,
            'td_output' => array(
                'moreAttributes' => 'width="20%"',
            ),
            'td_input' => array(
                'moreAttributes' => 'width="37.5%"',
                'positionLine' => 2,
                'colspan' => 2,
            ),
        );

        $this->fields['assigned_usergroup'] = array(
            'name' => 'assigned_usergroup',
            'label' => 'RequestManagerStatusDictionaryAssignedUserGroup',
            'type' => 'chkbxlst',
            'options' => 'usergroup:nom:rowid::' . $entity_filter,
            'td_output' => array(
                'moreAttributes' => 'width="20%"',
            ),
            'td_input' => array(
                'moreAttributes' => 'width="37.5%"',
                'positionLine' => 2,
                'colspan' => 3,
            ),
        );

        $this->fields['new_request_type'] = array(
            'name' => 'new_request_type',
            'label' => 'RequestManagerStatusDictionaryNewRequestType',
            'help' => 'RequestManagerStatusDictionaryNewRequestTypeHelp',
            'type' => 'chkbxlst',
            'options' => 'c_requestmanager_request_type:label:rowid::active=1 and entity IN (' . getEntity('dictionary', 1) . ')',
            'td_output' => array(
                'moreAttributes' => 'width="12.5%"',
            ),
            'td_input' => array(
                'moreAttributes' => 'width="12.5%"',
                'positionLine' => 3,
            ),
        );

		$this->fields['do_not_bloc_process'] = array(
            'name' => 'do_not_bloc_process',
            'label' => 'RequestManagerStatusDictionaryDoNotBlocProcess',
            'type' => 'boolean',
            'td_output' => array(
                'moreAttributes' => 'width="10%"',
            ),
			'td_input' => array(
                'positionLine' => 3,
                'colspan' => 1,
				'moreAttributes' => 'width="10%"',
            ),
        );

        $this->fields['next_status'] = array(
            'name' => 'next_status',
            'label' => 'RequestManagerStatusDictionaryNextStatus',
            'type' => 'chkbxlst',
            'options' => 'c_requestmanager_status:code|label:rowid::active=1 and entity IN (' . getEntity('dictionary', 1) . ')',
            'label_separator' => ' - ',
            'td_output' => array(
                'moreAttributes' => 'width="20%"',
            ),
            'td_input' => array(
                'moreAttributes' => 'width="25%"',
                'positionLine' => 3,
                'colspan' => 1,
            ),
        );

        $this->fields['reason_resolution'] = array(
            'name' => 'reason_resolution',
            'label' => 'RequestManagerReasonsResolution',
            'type' => 'chkbxlst',
            'options' => 'c_requestmanager_reason_resolution:label:rowid::active=1 and entity IN (' . getEntity('dictionary', 1) . ')',
            'td_output' => array(
                'moreAttributes' => 'width="50%"',
            ),
            'td_input' => array(
                'moreAttributes' => 'width="50%"',
                'positionLine' => 4,
                'colspan' => 4,
            ),
        );

        $authorized_buttons_list = array('create_request_manager' => $langs->trans('RequestManagerAddRequest'));
        if (!empty($conf->propal->enabled)) {
            $authorized_buttons_list = array_merge($authorized_buttons_list, array('create_propal' => $langs->trans('AddProp')));
        }
        if (!empty($conf->commande->enabled)) {
            $authorized_buttons_list = array_merge($authorized_buttons_list, array('create_order' => $langs->trans('AddOrder')));
        }
        if (!empty($conf->facture->enabled)) {
            $authorized_buttons_list = array_merge($authorized_buttons_list, array('create_invoice' => $langs->trans('AddBill')));
        }
        if (!empty($conf->supplier_proposal->enabled)) {
            $authorized_buttons_list = array_merge($authorized_buttons_list, array('create_supplier_proposal' => $langs->trans('AddSupplierProposal')));
        }
        if (!empty($conf->fournisseur->enabled)) {
            $authorized_buttons_list = array_merge($authorized_buttons_list, array('create_supplier_order' => $langs->trans('AddSupplierOrder'),'create_supplier_invoice' => $langs->trans('AddSupplierInvoice')));
        }
        if (!empty($conf->contrat->enabled)) {
            $authorized_buttons_list = array_merge($authorized_buttons_list, array('create_contract' => $langs->trans('AddContract')));
        }
        if (!empty($conf->ficheinter->enabled)) {
            $authorized_buttons_list = array_merge($authorized_buttons_list, array('create_inter' => $langs->trans('AddIntervention')));
        }
        if (!empty($conf->projet->enabled)) {
            $authorized_buttons_list = array_merge($authorized_buttons_list, array('create_project' => $langs->trans('AddProject')));
        }
        if (!empty($conf->deplacement->enabled)) {
            $authorized_buttons_list = array_merge($authorized_buttons_list, array('create_trip' => $langs->trans('AddTrip')));
        }
        if (!empty($conf->agenda->enabled)) {
            $authorized_buttons_list = array_merge($authorized_buttons_list, array('create_event' => $langs->trans('AddAction')));
        }

        // Add custom object
        $hookmanager->initHooks(array('requestmanagerdao'));
        $parameters = array();
        $reshook = $hookmanager->executeHooks('addRequestManagerAuthorizedButton', $parameters); // Note that $action and $object may have been
        if ($reshook) $authorized_buttons_list = array_merge($authorized_buttons_list, $hookmanager->resArray);

        // Sort list
        asort($authorized_buttons_list);
        $authorized_buttons_list = array_merge(
            array('no_buttons' => $langs->trans('RequestManagerStatusDictionaryNoButtons')),
            $authorized_buttons_list
        );

        $this->fields['authorized_buttons'] = array(
            'name'       => 'authorized_buttons',
            'label'      => 'RequestManagerStatusDictionaryAuthorizedButtons',
            'help'       => 'RequestManagerStatusDictionaryAuthorizedButtonsHelp',
            'type'       => 'checkbox',
            'options' => $authorized_buttons_list,
            'td_title'  => array (
                'align'  => 'left',
            ),
            'td_output'  => array (
                'align'  => 'left',
            ),
            'td_search'  => array (
                'align'  => 'left',
            ),
            'td_input'  => array (
                'moreAttributes' => 'width="50%"',
                'align'  => 'left',
                'positionLine' => 4,
            ),
        );
    }
}

class RequestManagerStatusDictionaryLine extends DictionaryLine
{
    public function checkFieldsValues($fieldsValue)
    {
        global $langs;

        $result = parent::checkFieldsValues($fieldsValue);
        if ($result < 0) {
            return $result;
        }

        dol_include_once('/requestmanager/class/requestmanager.class.php');
        if ($fieldsValue['type'] == RequestManager::STATUS_TYPE_INITIAL
		    // ||
            //$fieldsValue['type'] == RequestManager::STATUS_TYPE_RESOLVED ||
            //$fieldsValue['type'] == RequestManager::STATUS_TYPE_CLOSED
			) {
            $status = $this->dictionary->getCodeFromFilter('{{rowid}}', array('type' => array($fieldsValue['type']), 'request_type'=> explode(',', $fieldsValue['request_type'])));
            if (($status > 0 && $status != $this->id) || $status == -1) {
                $this->errors[] = $langs->trans('RequestManagerErrorOnlyOneStatusForThisTypeForEachRequestType');
                return -1;
            } elseif ($status == -2) {
                $this->errors[] = $this->dictionary->error;
                return $status;
            }
        }

        /*if ($this->id > 0 && !empty($fieldsValue['next_status']) && in_array($this->id, explode(',', $fieldsValue['next_status']))) {
            $this->errors[] = $langs->trans('RequestManagerStatusDictionaryNextStatusCanNotBeItself');
            return -1;
        }*/

        //todo check current trigger for each next status

        return $result;
    }

    /**
     * Return HTML string to put an output field into a page
     *
     * @param   string	$fieldName      Name of the field
     * @param   string	$value          Value to show
     * @return	string					Formatted value
     */
    function showOutputField($fieldName, $value = null)
    {
        global $langs;

        if ($fieldName == 'next_status') {
            if (isset($this->dictionary->fields[$fieldName])) {
                // Load status and request type infos into cache
                $this->dictionary->load_request_type();
                $this->dictionary->load_status();

                $field = $this->dictionary->fields[$fieldName];

                if ($value === null) $value = $this->fields[$fieldName];

                if (is_array($value)) {
                    $value_arr = $value;
                } else {
                    $value_arr = array_filter(explode(',', (string)$value), 'strlen');
                }

                // 0 : tableName
                // 1 : label field name
                // 2 : key fields name (if differ of rowid)
                // 3 : key field parent (for dependent lists)
                // 4 : where clause filter on column or table extrafield, syntax field='value' or extra.field=value
                $InfoFieldList = explode(":", (string)$field['options']);

                $selectkey = "rowid";
                $keyList = 'rowid';

                if (count($InfoFieldList) >= 3) {
                    $selectkey = $InfoFieldList[2];
                    $keyList = $InfoFieldList[2] . ' as rowid';
                }

                $fields_label = explode('|', $InfoFieldList[1]);
                if (is_array($fields_label)) {
                    $keyList .= ', ';
                    $keyList .= implode(', ', $fields_label);
                }

                $sql = 'SELECT ' . $keyList;
                $sql .= ' FROM ' . MAIN_DB_PREFIX . $InfoFieldList[0];
                if (strpos($InfoFieldList[4], 'extra') !== false) {
                    $sql .= ' as main';
                }
                $sql .= " WHERE " . $selectkey . " IN(" . implode(',', $value_arr) . ")";

                dol_syslog(__METHOD__ . ':showOutputField:$type=chkbxlst next_status', LOG_DEBUG);
                $resql = $this->db->query($sql);
                if ($resql) {
                    $value = ''; // value was used, so now we reste it to use it to build final output
                    $toprint = array();
                    $current_status_type = $this->fields['type'];
                    dol_include_once('/requestmanager/class/requestmanager.class.php');

                    while ($obj = $this->db->fetch_object($resql)) {
                        // Several field into label (eq table:code|libelle:rowid)
                        $fields_label = explode('|', $InfoFieldList[1]);
                        if (is_array($value_arr) && in_array($obj->rowid, $value_arr)) {
                            // Test if status is defined for the request type of this line
                            $request_type_cache = $this->dictionary->request_type_cache;
                            $status_cache = $this->dictionary->status_cache;
                            $status_title = array();

                            $status_not_in_request_type = array();
                            foreach (explode(',', $this->fields['request_type']) as $request_type) {
                                if (!in_array($request_type, explode(',', $status_cache->lines[$obj->rowid]->fields['request_type']))) {
                                    $status_not_in_request_type[] = '"' . $request_type_cache->lines[$request_type]->fields['label'] . '"';
                                }
                            }
                            if (count($status_not_in_request_type) > 0) {
                                $status_title[] = $langs->trans('RequestManagerStatusDictionaryStatusNotIntoRequestType', implode(", ", $status_not_in_request_type));
                            }

                            $status_type = $status_cache->lines[$obj->rowid]->fields['type'];
                            if (($current_status_type == RequestManager::STATUS_TYPE_INITIAL || $current_status_type == RequestManager::STATUS_TYPE_IN_PROGRESS) &&
                                $status_type != RequestManager::STATUS_TYPE_IN_PROGRESS && $status_type != RequestManager::STATUS_TYPE_RESOLVED) {
                                $status_title[] = $langs->trans('RequestManagerStatusDictionaryCanOnlyHaveInProgressAndResolvedForNextStatus');
                            } elseif ($current_status_type == RequestManager::STATUS_TYPE_RESOLVED && $status_type != RequestManager::STATUS_TYPE_CLOSED) {
                                $status_title[] = $langs->trans('RequestManagerStatusDictionaryCanOnlyHaveClosedForNextStatus');
                            }

                            if (count($status_title) > 0) {
                                $status_color_background = ' style="background: #caa"';
                                $status_title .= ' title="' . dol_escape_htmltag(implode("; ", $status_title)) . '"';
                            } else {
                                $status_color_background = ' style="background: #aaa"';
                                $status_title = '';
                            }

                            if (is_array($fields_label) && count($fields_label) > 1) {
                                $label_separator = isset($field['label_separator']) ? $field['label_separator'] : ' ';
                                $labelstoshow = array();
                                foreach ($fields_label as $field_toshow) {
                                    $translabel = $langs->trans($obj->$field_toshow);
                                    if ($translabel != $obj->$field_toshow) {
                                        $labelstoshow[] = dol_trunc($translabel, 18);
                                    } else {
                                        $labelstoshow[] = dol_trunc($obj->$field_toshow, 18);
                                    }
                                }
                                $toprint[] = '<li class="select2-search-choice-dolibarr noborderoncategories"'.$status_color_background.$status_title.'>' . implode($label_separator, $labelstoshow) . '</li>';
                            } else {
                                $translabel = '';
                                if (!empty($obj->{$InfoFieldList[1]})) {
                                    $translabel = $langs->trans($obj->{$InfoFieldList[1]});
                                }
                                if ($translabel != $obj->{$InfoFieldList[1]}) {
                                    $toprint[] = '<li class="select2-search-choice-dolibarr noborderoncategories"'.$status_color_background.$status_title.'>' . dol_trunc($translabel, 18) . '</li>';
                                } else {
                                    $toprint[] = '<li class="select2-search-choice-dolibarr noborderoncategories"'.$status_color_background.$status_title.'>' . $obj->{$InfoFieldList[1]} . '</li>';
                                }
                            }
                        }
                    }
                    $value = '<div class="select2-container-multi-dolibarr" style="width: 90%;"><ul class="select2-choices-dolibarr">' . implode(' ', $toprint) . '</ul></div>';

                } else {
                    dol_syslog(__METHOD__ . '::showOutputField error next_status' . $this->db->lasterror(), LOG_WARNING);
                }
                return $value;
            }
        }

        return parent::showOutputField($fieldName, $value);
    }

    /**
	 * Return HTML string to put an input field into a page
	 *
	 * @param  string  $fieldName      Name of the field
	 * @param  string  $value          Preselected value to show (for date type it must be in timestamp format, for amount or price it must be a php numeric value)
	 * @param  string  $keyprefix      Prefix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param  string  $keysuffix      Suffix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param  int     $objectid       Current object id
	 * @return string
	 */
	function showInputField($fieldName, $value=null, $keyprefix='', $keysuffix='', $objectid=0)
    {
        if ($fieldName == 'next_status') {
            dol_include_once('/requestmanager/class/requestmanager.class.php');
            $typeFieldHtmlName = $keyprefix . 'type' . $keysuffix;
            $operationFromEventFieldHtmlName = $keyprefix . 'operation_from_event' . $keysuffix;
            $deadlineRcFromEventFieldHtmlName = $keyprefix . 'deadline_rc_from_event' . $keysuffix;
            $deadlineFromEventFieldHtmlName = $keyprefix . 'deadline_from_event' . $keysuffix;
            $currentTriggerFieldHtmlName = $keyprefix . 'current_trigger' . $keysuffix;
            $newRequestTypeFieldHtmlName = $keyprefix . 'new_request_type' . $keysuffix;
            $newRequestTypeAutoFieldHtmlName = $keyprefix . 'new_request_type_auto' . $keysuffix;
            $newRequestTypeNextStatusAutoFieldHtmlName = $keyprefix . 'new_request_type_next_status_auto' . $keysuffix;
            $nextTriggerFieldHtmlName = $keyprefix . 'next_trigger' . $keysuffix;
            $nextStatusAutoFieldHtmlName = $keyprefix . 'next_status_auto' . $keysuffix;
            $nextStatusFieldHtmlName = $keyprefix . 'next_status' . $keysuffix;
            $reasonResolutionFieldHtmlName = $keyprefix . 'reason_resolution' . $keysuffix;
            $authorizedButtonsFieldHtmlName = $keyprefix . 'authorized_buttons' . $keysuffix;
            $updateDeadlineRcFromEventFunctionName = 'update_' . $deadlineRcFromEventFieldHtmlName;
            $updateDeadlineFromEventFunctionName = 'update_' . $deadlineFromEventFieldHtmlName;
            $updateCurrentTriggerFunctionName = 'update_' . $currentTriggerFieldHtmlName;
            $updateNewRequestTypeFunctionName = 'update_' . $newRequestTypeFieldHtmlName;
            $updateNewRequestTypeAutoFunctionName = 'update_' . $newRequestTypeAutoFieldHtmlName;
            $updateNewRequestTypeNextStatusAutoFunctionName = 'update_' . $newRequestTypeNextStatusAutoFieldHtmlName;
            $updateNextTriggerFunctionName = 'update_' . $nextTriggerFieldHtmlName;
            $updateNextStatusAutoFunctionName = 'update_' . $nextStatusAutoFieldHtmlName;
            $updateNextStatusFunctionName = 'update_' . $nextStatusFieldHtmlName;
            $updateReasonResolutionFunctionName = 'update_' . $reasonResolutionFieldHtmlName;
            $updateAuthorizedButtonsFunctionName = 'update_' . $authorizedButtonsFieldHtmlName;
            $initial_status = RequestManager::STATUS_TYPE_INITIAL;
            $inprogress_status = RequestManager::STATUS_TYPE_IN_PROGRESS;
            $resolved_status = RequestManager::STATUS_TYPE_RESOLVED;
            $closed_status = RequestManager::STATUS_TYPE_CLOSED;

            print <<<SCRIPT
            <input type="hidden" id="h_$deadlineRcFromEventFieldHtmlName" name="$deadlineRcFromEventFieldHtmlName" value="" disabled="disabled">
            <input type="hidden" id="h_$deadlineFromEventFieldHtmlName" name="$deadlineFromEventFieldHtmlName" value="" disabled="disabled">
            <input type="hidden" id="h_$currentTriggerFieldHtmlName" name="$currentTriggerFieldHtmlName" value="" disabled="disabled">
            <input type="hidden" id="h_$newRequestTypeAutoFieldHtmlName" name="$newRequestTypeAutoFieldHtmlName" value="" disabled="disabled">
            <input type="hidden" id="h_$newRequestTypeNextStatusAutoFieldHtmlName" name="$newRequestTypeNextStatusAutoFieldHtmlName" value="" disabled="disabled">
            <input type="hidden" id="h_$newRequestTypeFieldHtmlName" name="$newRequestTypeFieldHtmlName" value="" disabled="disabled">
            <input type="hidden" id="h_$nextTriggerFieldHtmlName" name="$nextTriggerFieldHtmlName" value="" disabled="disabled">
            <input type="hidden" id="h_$nextStatusAutoFieldHtmlName" name="$nextStatusAutoFieldHtmlName" value="" disabled="disabled">
            <input type="hidden" id="h_$nextStatusFieldHtmlName" name="$nextStatusFieldHtmlName" value="" disabled="disabled">
            <input type="hidden" id="h_$reasonResolutionFieldHtmlName" name="$reasonResolutionFieldHtmlName" value="" disabled="disabled">
            <input type="hidden" id="h_$authorizedButtonsFieldHtmlName" name="$authorizedButtonsFieldHtmlName" value="" disabled="disabled">

<script type="text/javascript">
    $(document).ready(function() {
        $updateDeadlineRcFromEventFunctionName();
        $updateDeadlineFromEventFunctionName();
        $updateCurrentTriggerFunctionName();
        $updateNewRequestTypeFunctionName();
        $updateNewRequestTypeAutoFunctionName();
        $updateNewRequestTypeNextStatusAutoFunctionName();
        $updateNextTriggerFunctionName();
        $updateNextStatusAutoFunctionName();
        $updateNextStatusFunctionName();
        $updateReasonResolutionFunctionName();
        $updateAuthorizedButtonsFunctionName();

        $('#$newRequestTypeFieldHtmlName').on('change', function() {
            $updateNextTriggerFunctionName();
            $updateNewRequestTypeAutoFunctionName();
            $updateNewRequestTypeNextStatusAutoFunctionName();
        });
        $('#$nextTriggerFieldHtmlName').on('keyup change', function() {
            $updateNewRequestTypeFunctionName();
            $updateNewRequestTypeAutoFunctionName();
            $updateNewRequestTypeNextStatusAutoFunctionName();
        });
        $('#$nextStatusFieldHtmlName').on('change', function() {
            $updateNewRequestTypeFunctionName();
            $updateNewRequestTypeAutoFunctionName();
            $updateNewRequestTypeNextStatusAutoFunctionName();
            $updateNextTriggerFunctionName();
            $updateNextStatusAutoFunctionName();
        });
        $('#$typeFieldHtmlName').on('change', function() {
            $updateCurrentTriggerFunctionName();
            $updateNewRequestTypeFunctionName();
            $updateNewRequestTypeAutoFunctionName();
            $updateNewRequestTypeNextStatusAutoFunctionName();
            $updateNextTriggerFunctionName();
            $updateNextStatusAutoFunctionName();
            $updateNextStatusFunctionName();
            $updateReasonResolutionFunctionName();
            $updateAuthorizedButtonsFunctionName();
        });
        $('#$operationFromEventFieldHtmlName').on('change', function() {
            $updateDeadlineRcFromEventFunctionName();
            $updateDeadlineFromEventFunctionName();
        });
        $('#$deadlineRcFromEventFieldHtmlName').on('change', function() {
            $updateDeadlineFromEventFunctionName();
        });
        $('#$deadlineFromEventFieldHtmlName').on('change', function() {
            $updateDeadlineRcFromEventFunctionName();
        });

        function $updateDeadlineRcFromEventFunctionName() {
            var disabled = !$('#$operationFromEventFieldHtmlName').is(':checked');
            var uncheck = $('#$deadlineFromEventFieldHtmlName').is(':checked');

            $('#$deadlineRcFromEventFieldHtmlName').prop('disabled', disabled);
            $('#h_$deadlineRcFromEventFieldHtmlName').prop('disabled', !disabled);
            if (disabled || uncheck) {
                $('#$deadlineRcFromEventFieldHtmlName').prop('checked', false);
            }
        }
        function $updateDeadlineFromEventFunctionName() {
            var disabled = !$('#$operationFromEventFieldHtmlName').is(':checked');
            var uncheck = $('#$deadlineRcFromEventFieldHtmlName').is(':checked');

            $('#$deadlineFromEventFieldHtmlName').prop('disabled', disabled);
            $('#h_$deadlineFromEventFieldHtmlName').prop('disabled', !disabled);
            if (disabled || uncheck) {
                $('#$deadlineFromEventFieldHtmlName').prop('checked', false);
            }
        }
        function $updateCurrentTriggerFunctionName() {
            var disabled = $('#$typeFieldHtmlName').val() == $initial_status;

            $('#$currentTriggerFieldHtmlName').prop('disabled', disabled);
            $('#h_$currentTriggerFieldHtmlName').prop('disabled', !disabled);
            if (disabled) {
                $('#$currentTriggerFieldHtmlName').val('');
            }
        }
        function $updateNewRequestTypeFunctionName() {
            var disabled = /*$('#$nextStatusFieldHtmlName').val().length > 1 ||*/ $('#$nextTriggerFieldHtmlName').val().length > 0 || ($('#$typeFieldHtmlName').val() == $initial_status || $('#$typeFieldHtmlName').val() == $closed_status);

            $('#$newRequestTypeFieldHtmlName').prop('disabled', disabled);
            $('#h_$newRequestTypeFieldHtmlName').prop('disabled', !disabled);
            if (disabled) {
                $('#$newRequestTypeFieldHtmlName').val(null).trigger('change');
            }
        }
        function $updateNewRequestTypeAutoFunctionName() {
            var disabled = $('#$nextTriggerFieldHtmlName').val().length > 0 || $('#$typeFieldHtmlName').val() != $inprogress_status || $('#$newRequestTypeFieldHtmlName').val().length == 0;
            var uncheck = $('#$newRequestTypeFieldHtmlName').val().length != 1 || $('#$typeFieldHtmlName').val() != $inprogress_status ||
                          $('#$nextTriggerFieldHtmlName').val().length != 0 || $('#$newRequestTypeFieldHtmlName').val().length == 0;

            $('#$newRequestTypeAutoFieldHtmlName').prop('disabled', disabled);
            $('#h_$newRequestTypeAutoFieldHtmlName').prop('disabled', !disabled);
            if (disabled || uncheck) {
                $('#$newRequestTypeAutoFieldHtmlName').prop('checked', false);
            }
        }
        function $updateNewRequestTypeNextStatusAutoFunctionName() {
            var disabled = $('#$nextStatusFieldHtmlName').val().length != 1 || $('#$typeFieldHtmlName').val() != $inprogress_status ||
                           $('#$newRequestTypeFieldHtmlName').val().length == 0 || $('#$nextTriggerFieldHtmlName').val().length > 0;
            var uncheck = $('#$newRequestTypeFieldHtmlName').val().length == 0 || $('#$typeFieldHtmlName').val() != $inprogress_status ||
                          $('#$nextStatusFieldHtmlName').val().length != 1 || $('#$nextTriggerFieldHtmlName').val().length > 0;

            $('#$newRequestTypeNextStatusAutoFieldHtmlName').prop('disabled', disabled);
            $('#h_$newRequestTypeNextStatusAutoFieldHtmlName').prop('disabled', !disabled);
            if (disabled || uncheck) {
                $('#$newRequestTypeNextStatusAutoFieldHtmlName').prop('checked', false);
            }
        }
        function $updateNextTriggerFunctionName() {
            var disabled = $('#$nextStatusFieldHtmlName').val().length > 1 || $('#$newRequestTypeFieldHtmlName').val().length > 0 || $('#$typeFieldHtmlName').val() == $closed_status;

            $('#$nextTriggerFieldHtmlName').prop('disabled', disabled);
            $('#h_$nextTriggerFieldHtmlName').prop('disabled', !disabled);
            if (disabled) {
                $('#$nextTriggerFieldHtmlName').val('');
            }
        }
        function $updateNextStatusAutoFunctionName() {
            var disabled = $('#$nextStatusFieldHtmlName').val().length != 1 || $('#$typeFieldHtmlName').val() != $resolved_status;
            var checked = $('#$nextStatusAutoFieldHtmlName').is(':checked') &&
                          $('#$typeFieldHtmlName').val() == $resolved_status && $('#$nextStatusFieldHtmlName').val().length == 1;

            $('#$nextStatusAutoFieldHtmlName').prop('disabled', disabled);
            $('#h_$nextStatusAutoFieldHtmlName').prop('disabled', !disabled);
            $('#$nextStatusAutoFieldHtmlName').prop('checked', checked);
            $('#h_$nextStatusAutoFieldHtmlName').val(checked ? '1' : '');
        }
        function $updateNextStatusFunctionName() {
            var disabled = $('#$typeFieldHtmlName').val() == $closed_status;

            $('#$nextStatusFieldHtmlName').prop('disabled', disabled);
            $('#h_$nextStatusFieldHtmlName').prop('disabled', !disabled);
            if (disabled) {
                $('#$nextStatusFieldHtmlName').val(null).trigger('change');
            }
        }
        function $updateReasonResolutionFunctionName() {
            var disabled = $('#$typeFieldHtmlName').val() != $resolved_status;

            $('#$reasonResolutionFieldHtmlName').prop('disabled', disabled);
            $('#h_$reasonResolutionFieldHtmlName').prop('disabled', !disabled);
            if (disabled) {
                $('#$reasonResolutionFieldHtmlName').val(null).trigger('change');
            }
        }
        function $updateAuthorizedButtonsFunctionName() {
            var disabled = $('#$typeFieldHtmlName').val() != $inprogress_status;

            $('#$authorizedButtonsFieldHtmlName').prop('disabled', disabled);
            $('#h_$authorizedButtonsFieldHtmlName').prop('disabled', !disabled);
            if (disabled) {
                $('#$authorizedButtonsFieldHtmlName').val(null).trigger('change');
            }
        }
    });
</script>
SCRIPT;
        }

        return parent::showInputField($fieldName, $value, $keyprefix, $keysuffix, $objectid);
    }
}
