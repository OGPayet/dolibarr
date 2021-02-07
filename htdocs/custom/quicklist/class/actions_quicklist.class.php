<?php
/* Copyright (C) 2017      Open-DSI             <support@open-dsi.fr>
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

//require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
dol_include_once('/quicklist/class/quicklist.class.php');
dol_include_once('/quicklist/lib/quicklist.lib.php');

/**
 *  \file       htdocs/quicklist/class/actions_quicklist.class.php
 *  \ingroup    quicklist
 *  \brief      File for hooks
 */

class ActionsQuickList
{
    /**
     * @var DoliDB Database handler.
     */
    public $db;
    /**
     * @var string Error
     */
    public $error = '';
    /**
     * @var array Errors
     */
    public $errors = array();

    /**
     * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
     */
    public $results = array();

    /**
     * @var string String displayed by executeHook() immediately after return
     */
    public $resprints;

    /**
     * @var array
     */
    private $invalid_params = [ 'token', 'confirm', 'formfilteraction',
        'button_search_x', 'button_search.x', 'button_search',
        'button_removefilte_x', 'button_removefilter.x', 'button_removefilter',
        'action', 'massaction', 'confirmmassaction', 'checkallactions', 'toselect[]',
        'button_quicklist_addfilter_x', 'button_quicklist_addfilter.x', 'button_quicklist_addfilter',
        'filter_name', 'filter_scope', 'filter_scope_usergroup', 'filter_update_url', 'filter_menu', 'filter_id',
        'filter_hashtag', 'filter_default', 'mainmenu', 'leftmenu',
    ];

	/**
	 * @var array
	 */
	private $authorized_invalid_params = [ 'token', 'confirm', 'formfilteraction',
		'action', 'massaction', 'confirmmassaction', 'checkallactions', 'toselect[]',
		'button_quicklist_addfilter_x', 'button_quicklist_addfilter.x', 'button_quicklist_addfilter',
		'filter_name', 'filter_scope', 'filter_scope_usergroup', 'filter_update_url', 'filter_menu',
		'filter_hashtag', 'filter_default',
	];

    /**
     * @var array
     */
    private $ids_params = [ 'id', 'socid', 'contact_id', 'ref', 'lineid', 'idmenu' ];

    /**
     * Constructor
     *
     * @param        DoliDB $db Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Overloading the doActions function : replacing the parent's function with the one below
     *
     * @param   array() $parameters Hook metadatas (context, etc...)
     * @param   CommonObject &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string &$action Current action (if set). Generally create or edit or null
     * @param   HookManager $hookmanager Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    function doActions($parameters, &$object, &$action, $hookmanager)
	{
		global $langs, $user;

		$langs->load('quicklist@quicklist');
		$context = $this->getListContext($parameters['context']);
		if (empty($context))
			return 0;

		$quicklist = new QuickList($this->db);
		$act = $action;

        //--------------------------------------------------------------------
        // Base url
        //--------------------------------------------------------------------
        $params = array();
        foreach (array_merge($_POST, $_GET) as $key => $value) {
            if (!in_array($key, $this->invalid_params) && !in_array($key, $this->ids_params) && $value != '') {
                $params[$key] = $value;
            }
        }
        $params_url = count($params) ? http_build_query($params, '', '&') : '';

		//--------------------------------------------------------------------
		// Get filter by params or reset filter selected
		//--------------------------------------------------------------------
		// All tests are required to be compatible with all browsers
		$btn_search = GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha');
		$btn_remove_filter = GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha');
		if ($btn_search || $btn_remove_filter) {
			if (isset($_SESSION['quicklist_current_filter'][$context])) unset($_SESSION['quicklist_current_filter'][$context]);
			$act = '';
		}

		//--------------------------------------------------------------------
		// Get filter
		//--------------------------------------------------------------------
		$result = 0;
		$filter_id = GETPOST("filter_id", 'int');
		if (!($filter_id > 0) && isset($_SESSION['quicklist_current_filter'][$context])) $filter_id = $_SESSION['quicklist_current_filter'][$context];
		if (!empty($params_url) && !isset($_SESSION['quicklist_current_filter'][$context]) && !$btn_remove_filter) {
			$result = $quicklist->fetch(0, $params_url, $context);
			if ($result < 0) {
				setEventMessages($quicklist->error, $quicklist->errors, 'errors');
				return 0;
			}
		}
		if ($result == 0 && $filter_id > 0) {
			$result = $quicklist->fetch($filter_id);
			if ($result < 0) {
				setEventMessages($quicklist->error, $quicklist->errors, 'errors');
				return 0;
			}
		}
		if ($result == 0 && $act != 'quicklist_addfilter_confirm' && !$btn_search && (empty($params_url)) && !isset($_SESSION['quicklist_current_filter'][$context])) {
			if (isset($_SESSION['quicklist_current_filter'][$context])) return 0;
			$result = $quicklist->fetch_default($context);
			if ($result < 0) {
				setEventMessages($quicklist->error, $quicklist->errors, 'errors');
				return 0;
			} elseif ($result == 0) return 0;
		}

		//--------------------------------------------------------------------
		// params of ids for the url (cards case)
		//--------------------------------------------------------------------
		$ids_params = array();
		foreach (array_merge($_POST, $_GET) as $key => $value) {
			if (in_array($key, $this->ids_params) && $value != '') {
				$ids_params[$key] = $value;
			}
		}
		$ids_params_url = count($ids_params) ? http_build_query($ids_params, '', '&') : '';

		//--------------------------------------------------------------------
		// params of invalid for the url (cards case)
		//--------------------------------------------------------------------
		$invalid_params_url = array();
		foreach (array_merge($_POST, $_GET) as $key => $value) {
			if (in_array($key, $this->authorized_invalid_params) && $value != '') {
				$invalid_params_url[$key] = $value;
			}
		}
		$invalid_params_url = count($invalid_params_url) ? http_build_query($invalid_params_url, '', '&') : '';

		//--------------------------------------------------------------------
        // Add filter
        //--------------------------------------------------------------------
        if ($act == 'quicklist_addfilter_confirm') {
            $result = true;
            $fk_menu = null;
            if (!empty(GETPOST("filter_menu", 'alpha'))) {
                // TODO create menu
                $fk_menu = null;
            }

            if ($result) {
                $quicklist->name = GETPOST("filter_name", 'alpha');
                $quicklist->page_context = $context;
                $quicklist->params = $params_url;
                $quicklist->scope = GETPOST("filter_scope", 'alpha');
                $quicklist->fk_menu = $fk_menu;
                $quicklist->default = GETPOST("filter_default", 'alpha') ? 1 : 0;
                $quicklist->hash_tag = urldecode(GETPOST("filter_hashtag", 'alpha'));
                $result = $quicklist->create($user);
                if ($result < 0) {
                    setEventMessages($langs->trans('QuickListFilterSavedError', $quicklist->errorsToString()), null, 'errors');
                }
            }

            if ($result) {
                $usergroup_ids = $quicklist->scope == QuickList::QUICKLIST_SCOPE_USERGROUP ? explode(',',GETPOST("filter_scope_usergroup")) : array();
                $quicklist->set_usergroup($usergroup_ids);

                setEventMessages($langs->trans('QuickListFilterSaved'), null);
                header('Location: ' . $_SERVER["PHP_SELF"] . (!empty($params_url) ? '?' . $params_url : '') . (!empty($ids_params_url) ? (!empty($params_url) ? '&' : '?') . $ids_params_url : '') . $quicklist->hash_tag);
                exit;
            }
        }
        //--------------------------------------------------------------------
        // Edit filter
        //--------------------------------------------------------------------
        elseif ($act == 'quicklist_editfilter_confirm' && $quicklist->fk_user_author == $user->id) {
            $result = true;
            $fk_menu = null;
            if (!empty(GETPOST("filter_menu", 'alpha'))) {
                // TODO update url menu
                if (!empty(GETPOST("filter_update_url", 'alpha'))) {

                }
                $fk_menu = $quicklist->fk_menu;
            } else {
                // TODO delete menu
            }

            if ($result) {
                $quicklist->name = GETPOST("filter_name", 'alpha');
                if (!empty(GETPOST("filter_update_url", 'alpha'))) {
                    $quicklist->params = $params_url;
                    $quicklist->hash_tag = urldecode(GETPOST("filter_hashtag", 'alpha'));
                }
                $quicklist->scope = GETPOST("filter_scope", 'alpha');
                $quicklist->fk_menu = $fk_menu;
                $quicklist->default = GETPOST("filter_default", 'alpha') ? 1 : 0;
                $result = $quicklist->update($user);
                if ($result < 0) {
                    setEventMessages($langs->trans('QuickListFilterSavedError', $quicklist->errorsToString()), null, 'errors');
                }
            }

            if ($result) {
                $usergroup_ids = $quicklist->scope == QuickList::QUICKLIST_SCOPE_USERGROUP ? explode(',',GETPOST("filter_scope_usergroup")) : array();
                $quicklist->set_usergroup($usergroup_ids);

                setEventMessages($langs->trans('QuickListFilterSaved'), null);
                header('Location: ' . $_SERVER["PHP_SELF"] . (!empty($params_url) ? '?' . $params_url : '') . (!empty($ids_params_url) ? (!empty($params_url) ? '&' : '?') . $ids_params_url : '') . $quicklist->hash_tag);
                exit;
            }
        }
        //--------------------------------------------------------------------
        // Delete filter
        //--------------------------------------------------------------------
        elseif ($act == 'quicklist_deletefilter_confirm' && $quicklist->fk_user_author == $user->id) {
            $result = true;
            // TODO delete menu
            if ($result) {
                $result = $quicklist->delete($user);
                if ($result < 0) {
                    setEventMessages($langs->trans('QuickListFilterDeletedError', $quicklist->errorsToString()), null, 'errors');
                }
            }

            if ($result) {
                setEventMessages($langs->trans('QuickListFilterDeleted'), null);
                header('Location: ' . $_SERVER["PHP_SELF"] . (!empty($params_url) ? '?' . $params_url : '') . (!empty($ids_params_url) ? (!empty($params_url) ? '&' : '?') . $ids_params_url : '') . $quicklist->hash_tag);
                exit;
            }
        }
        //--------------------------------------------------------------------
        // Set filter
        //--------------------------------------------------------------------
        elseif (!empty($quicklist->params) && !isset($_SESSION['quicklist_filter_applied'][$context]) && empty($act)) {
            $_SESSION['quicklist_current_filter'][$context] = $quicklist->id;
			$_SESSION['quicklist_filter_applied'][$context] = true;
			$location_params = array();
			if (!empty($quicklist->params)) $location_params[] = $quicklist->params;
			if (!empty($ids_params_url)) $location_params[] = $ids_params_url;
			if (!empty($invalid_params_url)) $location_params[] = $invalid_params_url;
            header('Location: ' . $_SERVER["PHP_SELF"] . (!empty($location_params) ? '?' . implode('&', $location_params) : ''));
            exit;
        }

        if (isset($_SESSION['quicklist_filter_applied'][$context])) unset($_SESSION['quicklist_filter_applied'][$context]);
        return 0;
    }

    /**
     * Overloading the printFieldListOption function : replacing the parent's function with the one below
     *
     * @param   array() $parameters Hook metadatas (context, etc...)
     * @param   CommonObject &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string &$action Current action (if set). Generally create or edit or null
     * @param   HookManager $hookmanager Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    function printFieldListOption($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $langs, $user;

        $context = $this->getListContext($parameters['context']);
        if (empty($context))
            return 0;

        $langs->load('quicklist@quicklist');

        $act = GETPOST("action", 'alpha');
        $addfilter = GETPOST("button_quicklist_addfilter_x") || GETPOST("button_quicklist_addfilter.x") || GETPOST("button_quicklist_addfilter");

        //--------------------------------------------------------------------
        // params of ids for the url (cards case)
        //--------------------------------------------------------------------
        $ids_params = array();
        foreach (array_merge($_POST, $_GET) as $key => $value) {
            if (in_array($key, $this->ids_params) && $value != '') {
                $ids_params[$key] = $value;
            }
        }
        $ids_params_url = count($ids_params) ? http_build_query($ids_params, '', '&') : '';

        //--------------------------------------------------------------------
        // Base url
        //--------------------------------------------------------------------
        $params = array();
        foreach (array_merge($_POST, $_GET) as $key => $value) {
            if (!in_array($key, $this->invalid_params) && !in_array($key, $this->ids_params) && $value != '') {
                $params[$key] = $value;
            }
        }
        $params_url = count($params) ? http_build_query($params, '', '&') : '';
        $base_url = $_SERVER["PHP_SELF"] . (!empty($ids_params_url) ? "?" . $ids_params_url : '') . (!empty($params_url) ? (!empty($ids_params_url) ? '&' : '?') . $params_url : '');

        //--------------------------------------------------------------------
        // Formulaire de Ajout / modification / confirmation de suppression
        //--------------------------------------------------------------------
        if ($act == 'quicklist_editfilter' || $act == 'quicklist_deletefilter' || $addfilter) {
            $form = new Form($this->db);
            dol_include_once('/quicklist/class/html.formquicklist.class.php');
            $formquicklist = new FormQuickList($this->db);

            // Get groups of the user
//            require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';
//            $usergroup = new UserGroup($this->db);
//            $usergroups = $usergroup->listGroupsForUser($user->id);
//            if (!is_array($usergroups)) {
//                $this->error = $usergroup->error;
//                $this->errors = $usergroup->errors;
//                return -1;
//            }

            //--------------------------------------------------------------------
            // Get filter
            //--------------------------------------------------------------------
            $filter_id = GETPOST("filter_id", 'int');
            $quicklist = new QuickList($this->db);
            $quicklist->fetch($filter_id);
            $quicklist->fetch_usergroup();

            //--------------------------------------------------------------------
            // Formulaire de Ajout / modification
            //--------------------------------------------------------------------
            if ($addfilter || $act == 'quicklist_editfilter') {
                $formquestion = array(
                    array(
                        'name' => 'filter_id',
                        'type' => 'hidden',
                        'value' => $filter_id
                    ),
                    array(
                        'name' => 'filter_scope',
                        'type' => 'hidden',
                        'value' => (isset($quicklist->scope) ? $quicklist->scope : QuickList::QUICKLIST_SCOPE_PRIVATE)
                    ),
                    array(
                        'name' => 'filter_hashtag',
                        'type' => 'hidden',
                        'value' => ''
                    ),
                    array(
                        'name' => 'filter_name',
                        'label' => $langs->trans('QuickListFilterName'),
                        'type' => 'text',
                        'value' => (isset($quicklist->name) ? $quicklist->name : '')
                    ),
                    array(
                        'name' => 'filter_scope_r',
                        'label' => $langs->trans('QuickListFilterScope'),
                        'type' => 'radio',
                        'values' => array(
                            QuickList::QUICKLIST_SCOPE_PRIVATE => '<label for="filter_scope_' . QuickList::QUICKLIST_SCOPE_PRIVATE . '">' . $langs->trans('QuickListScopePrivate') . '</label>',
                            QuickList::QUICKLIST_SCOPE_USERGROUP => '<label for="filter_scope_' . QuickList::QUICKLIST_SCOPE_USERGROUP . '">' . $langs->trans('QuickListScopeUserGroup') . '</label>',
                            QuickList::QUICKLIST_SCOPE_PUBLIC => '<label for="filter_scope_' . QuickList::QUICKLIST_SCOPE_PUBLIC . '">' . $langs->trans('QuickListScopePublic') . '</label>',
                        )
                    ),
                    array(
                        'name' => 'filter_scope_usergroup',
                        'label' => $langs->trans('QuickListFilterScopeUserGroup'),
                        'type' => 'other',
                        'value' => $formquicklist->multiselect_dolgroups((isset($quicklist->usergroups) ? array_keys($quicklist->usergroups) : array()), 'filter_scope_usergroup')//, '', 0, array_keys($usergroups))
                    ),
                    array(
                        'name' => 'filter_default',
                        'label' => $langs->trans('QuickListFilterDefault'),
                        'type' => 'checkbox',
                        'value' => !empty($quicklist->default)
                    ),
                );

                if ($act == 'quicklist_editfilter') {
                    $formquestion[] = array(
                        'name' => 'filter_update_url',
                        'label' => $langs->trans('QuickListFilterUpdateUrl'),
                        'type' => 'checkbox',
                        'value' => true
                    );
                }

                /*$formquestion[] = array(
                    'name' => 'filter_menu',
                    'label' => $langs->trans('QuickListFilterMenu'),
                    'type' => 'checkbox',
                    'value' => (empty($quicklist->fk_menu) ? false : true)
                );*/

                $form_title = $addfilter ? $langs->trans("QuickListAddFilter") : $langs->trans("QuickListEditFilter");
                $form_action = $addfilter ? "quicklist_addfilter_confirm" : "quicklist_editfilter_confirm";

                $formconfirm = $form->formconfirm($base_url, $form_title, '', $form_action, $formquestion, 'yes', 1, 350, 800);
                $formconfirm .= '<script type="text/javascript" language="javascript">' . "\n";
                $formconfirm .= '$(document).ready(function () {' . "\n";
                $formconfirm .= '  $("#dialog-confirm").on("dialogopen", function(event, ui) {' . "\n";
                $formconfirm .= '    $("#dialog-confirm").val(encodeURIComponent(window.location.hash));' . "\n";
                $formconfirm .= '    $(\'input[name=filter_hashtag]\').val(encodeURIComponent(window.location.hash));' . "\n";
                $formconfirm .= '    $(\'input[name=filter_name]\').closest(\'tr\').find(\'td:first-child\').attr(\'width\', \'25%\');' . "\n";
                $formconfirm .= '    $.map($(\'input[type=radio][name=filter_scope_r]\'), function(item) { $(item).attr(\'id\', \'filter_scope_\' + $(item).val()); });' . "\n";
                $formconfirm .= '    $(\'input[type=radio][name=filter_scope_r]\').filter(\'[value=' . (isset($quicklist->scope) ? $quicklist->scope : QuickList::QUICKLIST_SCOPE_PRIVATE) . ']\').prop(\'checked\', true);' . "\n";
                if ($quicklist->scope != QuickList::QUICKLIST_SCOPE_USERGROUP) {
                    $formconfirm .= '    $("#filter_scope_usergroup").closest("tr").hide();' . "\n";
                }
                $formconfirm .= '    $(\'input[type=radio][name=filter_scope_r]\').change(function() {' . "\n";
                $formconfirm .= '      $(\'input[type=hidden][name=filter_scope]\').val(this.value);' . "\n";
                $formconfirm .= '      if (this.value == ' . QuickList::QUICKLIST_SCOPE_USERGROUP . ') {' . "\n";
                $formconfirm .= '        $("#filter_scope_usergroup").closest("tr").show();' . "\n";
                $formconfirm .= '      } else {' . "\n";
                $formconfirm .= '        $("#filter_scope_usergroup").closest("tr").hide();' . "\n";
                $formconfirm .= '      }' . "\n";
                $formconfirm .= '    });' . "\n";
                $formconfirm .= '    $(\'select#filter_scope_usergroup\').closest(\'td\').find(\'li.select2-search.select2-search--inline\').addClass(\'minwidth300\');' . "\n";
                $formconfirm .= '  });' . "\n";
                $formconfirm .= '});' . "\n";
                $formconfirm .= '</script>' . "\n";
            }
            //--------------------------------------------------------------------
            // Formulaire de confirmation de suppression
            //--------------------------------------------------------------------
            else {
                $formquestion = array(
                    array(
                        'name' => 'filter_id',
                        'type' => 'hidden',
                        'value' => $filter_id
                    ),
                );
                $formconfirm = $form->formconfirm($base_url, $langs->trans("QuickListDeleteFilter"), $langs->trans("QuickListConfirmDeleteFilter", $quicklist->name), "quicklist_deletefilter_confirm", $formquestion, 0, 1);
            }

            quicklist_print_confirmform($formconfirm);
        }

        //--------------------------------------------------------------------
        // Remplacement du bouton de remise à zero des filtres
        //--------------------------------------------------------------------
        // Get filter list of user
        $quicklist = new QuickList($this->db);
        $filters_list = $quicklist->liste_array($context);
        $filters = [ 'private' => [], 'usergroup' => [], 'public' => [] ];
        if (is_array($filters_list)) {
            foreach ($filters_list as $filter) {
                $has_selected_fields = strpos($filter->params, '&selectedfields=') != -1 || strpos($filter->params, '?selectedfields=') != -1;
                $tmp = array("filter_id=" . $filter->id);
                if (!empty($ids_params_url)) $tmp[] = $ids_params_url;
                if ($has_selected_fields) $tmp[] = 'formfilteraction=listafterchangingselectedfields';
                $url = $_SERVER["PHP_SELF"] . "?" . implode('&', $tmp);
                $value = [
                	'id' => $filter->id, 'name' => $filter->name, 'url' => $url,
                    'hash_tag' => $filter->hash_tag, 'author' => $filter->fk_user_author == $user->id, 'default' => !empty($filter->default),
					'selected' => $_SESSION['quicklist_current_filter'][$context] == $filter->id
				];
                switch ($filter->scope) {
                    case QuickList::QUICKLIST_SCOPE_PRIVATE:
                        $filters['private'][] = $value;
                        break;
                    case QuickList::QUICKLIST_SCOPE_USERGROUP:
                        $filters['usergroup'][] = $value;
                        break;
                    case QuickList::QUICKLIST_SCOPE_PUBLIC:
                        $filters['public'][] = $value;
                        break;
                }
            }
        }

        print '<script type="text/javascript" language="javascript">' . "\n";
        print '$(document).ready(function () {' . "\n";
        print 'quicklist_replace_button_removefilter("' . str_replace('"', '\\"', $base_url) . '",' . json_encode($filters) . ');' . "\n";
        if (!empty($conf->global->QUICKLIST_SHOW_FILTERS_LIST_ABOVE_TABLE)) {
            print 'quicklist_show_filters_list_button(' . json_encode($filters) . ');' . "\n";
        }
        print '});';
        print '</script>' . "\n";

        return 0;
    }

	/**
	 * Get context of the list page
	 *
	 * @param   string  $context    All context
	 *
	 * @return  string				Context of the list page
	 */
	protected function getListContext($context)
	{
		$context_list = preg_grep('/(.*list$)/i', explode(':', $context));
		if (count($context_list)) {
			foreach ($context_list as $value) {
				return $value;
			}
		}

		return '';
	}
}