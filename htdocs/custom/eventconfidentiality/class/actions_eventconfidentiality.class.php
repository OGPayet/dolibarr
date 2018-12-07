<?php
/*  Copyright (C) 2018      Open-DSI             <support@open-dsi.fr>
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
 * \file    htdocs/eventconfidentiality/class/actions_eventconfidentiality.class.php
 * \ingroup eventconfidentiality
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';

/**
 * Class ActionsEventConfidentiality
 */
class ActionsEventConfidentiality
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
     * Constructor
     *
     * @param        DoliDB $db Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Overloading the formObjectOptions function : replacing the parent's function with the one below
     *
     * @param   array()         $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          &$action        Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    function formObjectOptions($parameters, &$object, &$action, $hookmanager)
    {
        global $langs, $user;

        if (is_array($parameters) && !empty($parameters)) {
            foreach ($parameters as $key => $value) {
                $$key = $value;
            }
        }
        $contexts = explode(':', $parameters['context']);

        if (in_array('actioncard', $contexts)) {
            $id = GETPOST('id', 'int');
            if (!empty($id)) {
                $object->fetch($id);
            }

            if ($user->rights->eventconfidentiality->manage) {
                $out = '';

                // Get all tags
                dol_include_once('/advancedictionaries/class/dictionary.class.php');
                $dictionary = Dictionary::getDictionary($this->db, 'eventconfidentiality', 'eventconfidentialitytag');
                $result = $dictionary->fetch_lines(1, array(), array('label' => 'ASC'));
                if ($result < 0) {
                    $this->error = $dictionary->error;
                    $this->errors = $dictionary->errors;
                    return -1;
                }

                // Get tags set or default tags
                dol_include_once('/eventconfidentiality/class/eventconfidentiality.class.php');
                $eventconfidentiality = new EventConfidentiality($this->db);
                if ($action == 'create') {
                    $elementtype = GETPOST('origin', 'alpha');
                    $default_tags = $eventconfidentiality->getDefaultTags($elementtype);
                    if (!is_array($default_tags)) {
                        $this->error = $eventconfidentiality->error;
                        $this->errors = $eventconfidentiality->errors;
                        return -1;
                    }
                } else {
                    $tags_set = $eventconfidentiality->fetchAllTagsOfEvent($object->id);
                    if (!is_array($tags_set)) {
                        $this->error = $eventconfidentiality->error;
                        $this->errors = $eventconfidentiality->errors;
                        return -1;
                    }
                }

                if ($action == 'create' || $action == 'edit') {
                    // Format out tags lines
                    $internal_tags = '';
                    $external_tags = '';
                    $initialize_tags = true;
                    foreach ($dictionary->lines as $line) {
                        if (isset($_POST['ec_mode_' . $line->id])) $initialize_tags = false;
                        $mode = isset($_POST['ec_mode_' . $line->id]) ? GETPOST('ec_mode_' . $line->id, 'int') : (isset($tags_set) ? $tags_set[$line->id]['mode'] : EventConfidentiality::MODE_HIDDEN);
                        $tmp = '<tr id="' . $line->id . '">';
                        $tmp .= '<td>' . $line->fields['label'] . '</td>';
                        $tmp .= '<td>';
                        $tmp .= '<input type="radio" id="ec_mode_' . $line->id . '_' . EventConfidentiality::MODE_VISIBLE . '" name="ec_mode_' . $line->id . '" value="0"' . ($mode == EventConfidentiality::MODE_VISIBLE ? ' checked="checked"' : "") . '><label for="ec_mode_' . $line->id . '_' . EventConfidentiality::MODE_VISIBLE . '">' . $langs->trans('EventConfidentialityModeVisible') . '</label>';
                        $tmp .= '&nbsp;<input type="radio" id="ec_mode_' . $line->id . '_' . EventConfidentiality::MODE_BLURRED . '" name="ec_mode_' . $line->id . '" value="1"' . ($mode == EventConfidentiality::MODE_BLURRED ? ' checked="checked"' : "") . '><label for="ec_mode_' . $line->id . '_' . EventConfidentiality::MODE_BLURRED . '">' . $langs->trans('EventConfidentialityModeBlurred') . '</label>';
                        $tmp .= '&nbsp;<input type="radio" id="ec_mode_' . $line->id . '_' . EventConfidentiality::MODE_HIDDEN . '" name="ec_mode_' . $line->id . '" value="2"' . ($mode == EventConfidentiality::MODE_HIDDEN ? ' checked="checked"' : "") . '><label for="ec_mode_' . $line->id . '_' . EventConfidentiality::MODE_HIDDEN . '">' . $langs->trans('EventConfidentialityModeHidden') . '</label>';
                        $tmp .= '</td>';
                        $tmp .= '</tr>';
                        if (empty($line->fields['external'])) {
                            $internal_tags .= $tmp;
                        } else {
                            $external_tags .= $tmp;
                        }
                    }
                    // Internal tags
                    $out .= '<tr>';
                    $out .= '<td class="nowrap" class="titlefield">' . $langs->trans("EventConfidentialityTagInterneLabel") . '</td>';
                    $out .= '<td colspan="3"><table class="noborder margintable centpercent">';
                    $out .= '<tr><th class="liste_titre" width="40%">Tags</th><th class="liste_titre">Mode</th></tr>';
                    $out .= $internal_tags;
                    $out .= '</table></td>';
                    $out .= '</tr>';
                    // External tags
                    $out .= '<tr>';
                    $out .= '<td class="nowrap" class="titlefield">' . $langs->trans("EventConfidentialityTagExterneLabel") . '</td>';
                    $out .= '<td colspan="3"><table class="noborder margintable centpercent">';
                    $out .= '<tr><th class="liste_titre" width="40%">Tags</th><th class="liste_titre">Mode</th></tr>';
                    $out .= $external_tags;
                    $out .= '</table></td>';
                    $out .= '</tr>';
                    if ($action == 'create') {
                        $default_tags = json_encode($default_tags);
                        $hidden_mode = EventConfidentiality::MODE_HIDDEN;
                        $out .= <<<SCRIPT
    <script type="text/javascript" language="javascript">
        $(document).ready(function () {
            var ec_default_tags = $default_tags;
            var ec_actioncode = $('select[name="actioncode"]');
            if (ec_actioncode.length == 0) ec_actioncode = $('input[name="actioncode"]');

            if ($initialize_tags) ec_update_tags();
            ec_actioncode.on('change', function() {
                ec_update_tags();
            });

            function ec_update_tags() {
                var action_code = ec_actioncode.val();

                if (action_code in ec_default_tags) {
                    $.map(ec_default_tags[action_code], function(val, idx) {
                        $('#ec_mode_' + idx + '_' + val['mode']).prop('checked', true);
                    })
                } else {
                    $.map($('[id^="ec_mode_"][id$="_$hidden_mode"]'), function(val, idx) {
                        $(val).prop('checked', true);
                    });
                }
            }
        });
    </script>
SCRIPT;
                    } else {
                        // Get mode for the user and event
                        $user_f = isset($user) ? $user : DolibarrApiAccess::$user;
                        $mode = $eventconfidentiality->getModeForUserAndEvent($user_f, $object->id);
                        if ($mode < 0) {
                            $this->error = $eventconfidentiality->error;
                            $this->errors = $eventconfidentiality->errors;
                            return -1;
                        }

                        if ($mode == EventConfidentiality::MODE_BLURRED) {
                            // Get html input names to hide
                            $hidden_html_input_names = array();
                            foreach (EventConfidentiality::$blurred_properties as $key => $html_input_names) {
                                foreach ($html_input_names as $input_name) {
                                    $hidden_html_input_names[] = $input_name;
                                }
                            }
                            $hidden_html_input_names = array_unique($hidden_html_input_names);
                            $hidden_html_input_names = json_encode($hidden_html_input_names);

                            $out .= <<<SCRIPT
    <script type="text/javascript" language="javascript">
        $(document).ready(function () {
            var ec_hidden_html_input_names = $hidden_html_input_names;

            $.map(ec_hidden_html_input_names, function(item) {
                var input = $('[name="'+item+'"]');
                if (input.length > 0) {
                    input.parent().append('<input type="hidden" name="'+item+'" value="1">');
                } else {
                    input =  $('#'+item);
                }
                input.remove();
            })
        });
    </script>
SCRIPT;
                        }
                    }
                } else {
                    // Format out tags lines
                    $internal_tags = array();
                    $external_tags = array();
                    foreach ($tags_set as $tag) {
                        if ($tag['mode'] != EventConfidentiality::MODE_HIDDEN) {
                            $tmp = '<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #aaa">' . $tag['label'] . ' : ' . $tag['mode_label'] . '</li>';
                            if (empty($tag['external'])) {
                                $internal_tags[] = $tmp;
                            } else {
                                $external_tags[] = $tmp;
                            }
                        }
                    }
                    // Internal tags
                    $out .= '<tr>';
                    $out .= '<td class="nowrap" class="titlefield">' . $langs->trans("EventConfidentialityTagInterneLabel") . '</td>';
                    $out .= '<td colspan="3"><div class="select2-container-multi-dolibarr" style="width: 90%;"><ul class="select2-choices-dolibarr">' . implode(' ', $internal_tags) . '</ul></div></td>';
                    $out .= '</tr>';
                    // External tags
                    $out .= '<tr>';
                    $out .= '<td class="nowrap" class="titlefield">' . $langs->trans("EventConfidentialityTagExterneLabel") . '</td>';
                    $out .= '<td colspan="3"><div class="select2-container-multi-dolibarr" style="width: 90%;"><ul class="select2-choices-dolibarr">' . implode(' ', $external_tags) . '</ul></div></td>';
                    $out .= '</tr>';
                }
                $this->resprints = $out;
            }
        }

        return 0;
    }

    /**
     * Overloading the afterObjectFetch function : replacing the parent's function with the one below
     *
     * @param   array()         $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          &$action        Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    function afterObjectFetch($parameters, &$object, &$action, $hookmanager)
    {
        global $user;

        $contexts = explode(':', $parameters['context']);

        if (in_array('actiondao', $contexts)) {
            if ($object->id > 0) {
                $user_f = isset($user) ? $user : DolibarrApiAccess::$user;

                dol_include_once('/eventconfidentiality/class/eventconfidentiality.class.php');
                $eventconfidentiality = new EventConfidentiality($this->db);

                // Get mode for the user and event
                $mode = $eventconfidentiality->getModeForUserAndEvent($user_f, $object->id);
                if ($mode < 0) {
                    $this->error = $eventconfidentiality->error;
                    $this->errors = $eventconfidentiality->errors;
                    return -1;
                }

                if ($user->rights->eventconfidentiality->manage) {
                    // Get all tags of the event
                    $tags_set = $eventconfidentiality->fetchAllTagsOfEvent($object->id);
                    if (!is_array($tags_set)) {
                        $this->error = $eventconfidentiality->error;
                        $this->errors = $eventconfidentiality->errors;
                        return -1;
                    }

                    // Add custom fields for the event object
                    $object->ec_mode_tags = array();
                    $object->ec_tags = array();
                    foreach ($tags_set as $tag_id => $tag) {
                        $object->ec_mode_tags[$tag_id] = intval($tag['mode']);
                        $object->ec_tags[$tag_id] = $tag;
                    }
                }

                // Manage the mode
                if ($mode == EventConfidentiality::MODE_HIDDEN) {
                    if (in_array('actioncard', $contexts)) {
                        accessforbidden();
                    } else {
                        foreach ($object as $key => $value) {
                            unset($object->$key);
                        }

                        $parameters['num'] = 0;
                    }
                } elseif ($mode == EventConfidentiality::MODE_BLURRED) {
                    $object->ec_save_values = array();
                    foreach (EventConfidentiality::$blurred_properties as $key => $html_input_names) {
                        $object->ec_save_values[$key] = $object->$key;
                        unset($object->$key);
                    }
                }
            }
        }

        return 0;
    }

    /**
     * Overloading the afterSQLFetch function : replacing the parent's function with the one below
     *
     * @param   array()         $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          &$action        Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    function afterSQLFetch($parameters, &$object, &$action, $hookmanager)
    {
        global $user;

        $contexts = explode(':', $parameters['context']);

        if (in_array('agenda', $contexts) || in_array('agendalist', $contexts)) {
            if ($object->id > 0) {
                $user_f = isset($user) ? $user : DolibarrApiAccess::$user;

                dol_include_once('/eventconfidentiality/class/eventconfidentiality.class.php');
                $eventconfidentiality = new EventConfidentiality($this->db);

                // Get mode for the user and event
                $mode = $eventconfidentiality->getModeForUserAndEvent($user_f, $object->id);
                if ($mode < 0) {
                    $this->error = $eventconfidentiality->error;
                    $this->errors = $eventconfidentiality->errors;
                    return -1;
                }

                // Manage the mode
                if ($mode == EventConfidentiality::MODE_HIDDEN) {
                    foreach ($object as $key => $value) {
                        unset($object->$key);
                    }
                } elseif ($mode == EventConfidentiality::MODE_BLURRED) {
                    foreach (EventConfidentiality::$blurred_properties as $key => $html_input_names) {
                        unset($object->$key);
                    }
                }
            }
        }

        return 0;
    }
}
