<?php
/* Copyright (C) 2015 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * or see http://www.gnu.org/
 */

/**
 *	\file			htdocs/core/init_events.inc.php
 *  \brief			Code for init on event of the request
 */

include DOL_DOCUMENT_ROOT . '/core/actions_changeselectedfields.inc.php';

// Do we click on purge search criteria ?
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
{
    $search_only_linked_to_request = 1;
    $search_include_event_other_request = 0;
    $search_include_linked_event_to_children_request = 1;
    $search_dont_show_selected_event_type_origin = 0;
    $search_ref = '';
    $search_origin = array();
    $search_thirdparty = '';
    $search_type = array();
    $search_except_type = array();
    $search_title = '';
    $search_description = '';
    $search_event_on_full_day = -1;
    $search_date_start = '';
    $search_date_end = '';
    $search_location = '';
    $search_owned_by = '';
    //$search_assigned_to = '';
    $search_done_by = '';
    $search_project = '';
    $search_priority = '';
    $search_internal_tag = '';
    $search_external_tag = '';
    $search_level_tag = -1;
    $search_author = '';
    $search_modified_by = '';
    $search_date_created = '';
    $search_date_modified = '';
    $search_status = '';
    $search_array_options = array();
}
if ($search_only_linked_to_request === '') $search_only_linked_to_request = 1;
if ($search_include_event_other_request === '') $search_include_event_other_request = 0;
if ($search_include_linked_event_to_children_request === '') $search_include_linked_event_to_children_request = 1;
if ($search_dont_show_selected_event_type_origin === '') $search_dont_show_selected_event_type_origin = 0;