<?php
/* Copyright (C) 2016		 Oscss-Shop       <support@oscss-shop.fr>
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

class ActionsMasterlink
{

    /**
     * Overloading the doActions function : replacing the parent's function with the one below
     *
     * @param   array()         $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          &$action        Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    function updateSession($parameters, &$object, &$action, $hookmanager)
    {

        global $conf, $db;


        switch ($parameters['currentcontext']) {
            case 'main':

                dol_include_once('/framework/class/masterlink.class.php');
                $ml = new Masterlink($db);

                $location = false;
                if (isset($_SERVER['SCRIPT_URL']) && !empty($_SERVER['SCRIPT_URL'])) $url      = $_SERVER['SCRIPT_URL'];
                else $url      = $_SERVER['PHP_SELF'];

                $path     = substr($_SERVER['SCRIPT_URL'], strlen(DOL_URL_ROOT));
                if ($path !== '' && $ml->fetch(0, $path) === 0 && $path != $ml->custom
                ) $location = dol_buildPath($ml->custom, 2);

                if ($location) {
                    $res = '';
                    if (isset($_POST) && is_array($_POST)) foreach ($_POST as $k => $v)
                            $res .= '&'.$k.'='.urlencode(GETPOST($k));

                    header('location: '.$location.'?'.$_SERVER['QUERY_STRING'].$res);
                    exit;
                }

                break;
        }


        return 0; // or return 1 to replace standard code
    }
}