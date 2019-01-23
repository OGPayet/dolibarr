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
 *	\file       htdocs/extendedintervention/lib/extendedintervention.lib.php
 * 	\ingroup	extendedintervention
 *	\brief      Functions for the module Request Manager
 */

/**
 * Prepare array with list of tabs for admin
 *
 * @return  array				Array of tabs to show
 */
function extendedintervention_admin_prepare_head()
{
    global $langs, $conf, $user;
    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath("/extendedintervention/admin/setup.php", 1);
    $head[$h][1] = $langs->trans("Parameters");
    $head[$h][2] = 'settings';
    $h++;

    $head[$h][0] = dol_buildpath("/extendedintervention/admin/dictionaries.php", 1);
    $head[$h][1] = $langs->trans("Dictionary");
    $head[$h][2] = 'dictionaries';
    $h++;

    $head[$h][0] = dol_buildpath("/extendedintervention/admin/extendedinterventionquestionbloc_extrafields.php", 1);
    $head[$h][1] = $langs->trans("ExtendedInterventionQuestionBlocExtraFields");
    $head[$h][2] = 'extendedintervention_question_block_attributes';
    $h++;

    $head[$h][0] = dol_buildpath("/extendedintervention/admin/extendedinterventionquestion_extrafields.php", 1);
    $head[$h][1] = $langs->trans("ExtendedInterventionQuestionExtraFields");
    $head[$h][2] = 'extendedintervention_question_attributes';
    $h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
    complete_head_from_modules($conf, $langs, null, $head, $h, 'extendedintervention_admin');

    $head[$h][0] = dol_buildpath("/extendedintervention/admin/about.php", 1);
    $head[$h][1] = $langs->trans("About");
    $head[$h][2] = 'about';
    $h++;

    $head[$h][0] = dol_buildpath("/extendedintervention/admin/changelog.php", 1);
    $head[$h][1] = $langs->trans("OpenDsiChangeLog");
    $head[$h][2] = 'changelog';
    $h++;

    complete_head_from_modules($conf, $langs, null, $head, $h, 'extendedintervention_admin', 'remove');

    return $head;
}
