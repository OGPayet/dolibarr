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

/**
 *	\file       htdocs/quicklist/lib/quicklist.lib.php
 * 	\ingroup	quicklist
 *	\brief      Functions for the module QuickList
 */

/**
 * Prepare array with list of tabs
 *
 * @return  array				Array of tabs to show
 */
function quicklist_admin_prepare_head()
{
    global $langs, $conf, $user;
    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath("/quicklist/admin/setup.php", 1);
    $head[$h][1] = $langs->trans("Parameters");
    $head[$h][2] = 'settings';
    $h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
    complete_head_from_modules($conf, $langs, null, $head, $h, 'quicklist_admin');

    $head[$h][0] = dol_buildpath("/quicklist/admin/about.php", 1);
    $head[$h][1] = $langs->trans("About");
    $head[$h][2] = 'about';
    $h++;

    $head[$h][0] = dol_buildpath("/quicklist/admin/changelog.php", 1);
    $head[$h][1] = $langs->trans("OpenDsiChangeLog");
    $head[$h][2] = 'changelog';
    $h++;

    complete_head_from_modules($conf, $langs, null, $head, $h, 'quicklist_admin', 'remove');

    return $head;
}

/**
 * Get context of the list page
 *
 * @param   string  $context    All context
 *
 * @return  string				Context of the list page
 */
function quicklist_get_context($context)
{
    $context_list = preg_grep('/(.*list$)/i', explode(':', $context));
    if (count($context_list)) {
        foreach ($context_list as $value) {
            return $value;
        }
    }

    $result = preg_match('/.*([^\/]*\/[^\/]*)$/i', $_SERVER["SELF"], $matches);
    if ($result) {
        return $matches[1];
    }

    return '';
}

/**
 * Print confirm form
 *
 * @param   string  $formconfirm    Confirm form
 *
 * @return  void
 */
function quicklist_print_confirmform($formconfirm)
{
    $html = [];
    $scripts = [];
    $cursor_pos = 0;
    while ($begin_script_pos = strpos($formconfirm, '<script', $cursor_pos)) {
        $html[] = substr($formconfirm, $cursor_pos, $begin_script_pos - $cursor_pos);

        $end_script_pos = strpos($formconfirm, '</script>', $begin_script_pos);
        $cursor_pos = $end_script_pos + 9;
        $scripts[] = substr($formconfirm, $begin_script_pos, $cursor_pos - $begin_script_pos);
    }
    $html[] = substr($formconfirm, $cursor_pos);

    $confirm = str_replace(['"', "\n", "\r"], ['\\"', "", ""], implode('', $html));

    print '<script type="text/javascript" language="javascript">'."\n";
    print '$(document).ready(function () {'."\n";
    print '$("#id-right").append("'.$confirm.'");'."\n";
    print '});'."\n";
    print '</script>'."\n";
    print implode('', $scripts);
}
