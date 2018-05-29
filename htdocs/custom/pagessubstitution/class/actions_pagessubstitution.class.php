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
 *  \file       htdocs/pagessubstitution/class/actions_pagessubstitution.class.php
 *  \ingroup    pagessubstitution
 *  \brief      File for hooks
 */

dol_include_once('pagessubstitution/lib/pagessubstitution.lib.php');

class ActionsPagesSubstitution
{
    /**
     * Overloading the updateSession function : replacing the parent's function with the one below
     *
     * @param   array()         $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          &$action        Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    function updateSession($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $user, $langs, $db;

        $path_src = preg_replace('/^'.preg_quote(DOL_URL_ROOT,'/').'/i','', $_SERVER["PHP_SELF"]);

        if (!pagessubstitution_is_substitution_page($path_src)) {
            if (!empty($conf->global->PAGESSUBSTITUTION_HOOK_TEST)) {
                if (isset($langs)) {
                    $langs->load('pagessubstitution@pagessubstitution');
                    $message = $langs->trans('PagesSubstitutionSubstitutionPagesSupported', $path_src);
                } else {
                    $message = 'Supported page for substitution: '.$path_src;
                }
                setEventMessage($message, 'warnings');
            } else {
                $url = pagessubstitution_get_substitution_url($path_src);
                if (!empty($url)) {
                    $params = array_merge($_POST, $_GET);
                    $params = http_build_query($params);
                    header("Location: " . $url . (!empty($params) ? '?' . $params : ''));
                    exit;
                }
            }
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the afterLogin function : replacing the parent's function with the one below
     *
     * @param   array()         $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          &$action        Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    function afterLogin($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $user, $langs, $db;

        $path_src = preg_replace('/^'.preg_quote(DOL_URL_ROOT,'/').'/i','', $_SERVER["PHP_SELF"]);

        if (!pagessubstitution_is_substitution_page($path_src)) {
            if (!empty($conf->global->PAGESSUBSTITUTION_HOOK_TEST)) {
                if (isset($langs)) {
                    $langs->load('pagessubstitution@pagessubstitution');
                    $message = $langs->trans('PagesSubstitutionSubstitutionPagesSupported', $path_src);
                } else {
                    $message = 'Supported page for substitution: '.$path_src;
                }
                setEventMessage($message, 'warnings');
            } else {
                $url = pagessubstitution_get_substitution_url($path_src);
                if (!empty($url)) {
                    $params = array_merge($_POST, $_GET);
                    $params = http_build_query($params);
                    header("Location: " . $url . (!empty($params) ? '?' . $params : ''));
                    exit;
                }
            }
        }

        return 0; // or return 1 to replace standard code
    }
}