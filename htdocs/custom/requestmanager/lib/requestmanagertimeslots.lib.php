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
 *	\file       htdocs/requestmanager/lib/requestmanagertimeslots.lib.php
 * 	\ingroup	requestmanager
 *	\brief      Functions for the module Request Manager Time Slots
 */

/**
 *  Check periods string option
 *
 * @param	string              $periods        Periods option to check
 * @return	array|string                        List of periods into a array or error message
 */
function requestmanagertimeslots_get_periods($periods)
{
    global $langs;

    $out = array();

    if (trim($periods) == "")
        return $out;

    $langs->load('requestmanager@requestmanager');

    // Get array of periods
    $periods = explode(',', $periods);

    foreach ($periods as $period) {
        // Get dates of the periods
        $dates = explode('-', $period);
        if (count($dates) != 2) {
            return $langs->trans('RequestManagerErrorTimeSlotsBadPeriodFormat', $period);
        }

        if (preg_replace('/\s/', '', $dates[0]) == preg_replace('/\s/', '', $dates[1])) {
            return $langs->trans('RequestManagerErrorTimeSlotsPeriodDatesIsEqual', $period);
        }

        // Check date format 1
        $date_format_1 = requestmanagertimeslots_get_date($dates[0]);
        if (is_string($date_format_1)) {
            return $date_format_1;
        }

        // Check date format 2
        $date_format_2 = requestmanagertimeslots_get_date($dates[1]);
        if (is_string($date_format_2)) {
            return $date_format_2;
        }

        if ($date_format_1['type'] != $date_format_2['type']) {
            return $langs->trans('RequestManagerErrorTimeSlotsDateFormatMustBeEqual', $period);
        }

        // Chronological test
        $test_date_1 = dol_mktime(
            (isset($date_format_1['hour']) && !empty($date_format_1['hour']) ? $date_format_1['hour'] : 0),
            (isset($date_format_1['minute']) && !empty($date_format_1['minute']) ? $date_format_1['minute'] : 0),
            0,
            (isset($date_format_1['month']) && !empty($date_format_1['month']) ? $date_format_1['month'] : 0),
            (isset($date_format_1['day']) && !empty($date_format_1['day']) ? $date_format_1['day'] : 0),
            (isset($date_format_1['year']) && !empty($date_format_1['year']) ? $date_format_1['year'] : 0)
        );
        $test_date_2 = dol_mktime(
            (isset($date_format_2['hour']) && !empty($date_format_2['hour']) ? $date_format_2['hour'] : 0),
            (isset($date_format_2['minute']) && !empty($date_format_2['minute']) ? $date_format_2['minute'] : 0),
            0,
            (isset($date_format_2['month']) && !empty($date_format_2['month']) ? $date_format_2['month'] : 0),
            (isset($date_format_2['day']) && !empty($date_format_2['day']) ? $date_format_2['day'] : 0),
            (isset($date_format_2['year']) && !empty($date_format_2['year']) ? $date_format_2['year'] : 0)
        );
        if ($test_date_1 > $test_date_2) {
            return $langs->trans('RequestManagerErrorTimeSlotsDateMustBeChronological', $period);
        }

        $out[] = array('begin' => $date_format_1, 'end' => $date_format_2);
    }

    return $out;
}

/**
 *  Check date string
 *
 * @param	string              $date           Date option to check
 * @return	array|string                        Array with information of the date or error message
 */
function requestmanagertimeslots_get_date($date)
{
    global $langs;

    if (preg_match('/^\s*(?:(\d{4})Y)?(?:(\d{2})M)?(?:(\d{1,2})(D|W))?\s+(\d{2})h(\d{2})\s*$/i', $date, $matches)) {
        $year    = !empty($matches[1]) ? intval($matches[1]) : null;
        $month   = !empty($matches[2]) ? intval($matches[2]) : null;
        $day     = !empty($matches[3]) ? intval($matches[3]) : null;
        $daytype = !empty($matches[4]) ? strtoupper($matches[4]) : null;
        $hour    = !empty($matches[5]) ? intval($matches[5]) : null;
        $minute  = !empty($matches[6]) ? intval($matches[6]) : null;

        if ((!isset($month) || (0 < $month && $month < 13)) &&
            (!isset($day) || ($daytype == 'D' && 0 < $day && $day < 32) || ($daytype == 'W' && -1 < $day && $day < 7)) &&
            (!isset($hour) || (0 < $hour && $hour < 25)) &&
            (!isset($minute) || (-1 < $minute && $minute < 60))
        ) {
            $type = (isset($year) ? 1 : 0) . (isset($month) ? 1 : 0) . (isset($day) ? 1 : 0) . (isset($daytype) ? 1 : 0) . (isset($hour) ? 1 : 0) . (isset($minute) ? 1 : 0);
            return array('type' => $type, 'year' => $year, 'month' => $month, 'day' => $day, 'daytype' => $daytype, 'hour' => $hour, 'minute' => $minute);
        }
    }

    $langs->load('requestmanager@requestmanager');

    return $langs->trans('RequestManagerErrorTimeSlotsBadDateFormat', $date);
}

/**
 *  Check if is in the time slot
 *
 * @param	int              $soc_id        ID of the company
 * @param	int              $date          Date to check
 * @return	boolean|array                   False or time slot info
 */
function requestmanagertimeslots_is_in_time_slot($soc_id, $date)
{
    global $conf, $db;

    $periods = '';

    if ($conf->contrat->enabled) {
        require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
        $contract = new Contrat($db);
        $contract->socid = $soc_id;
        $contract_list = $contract->getListOfContracts();

        if (is_array($contract_list) && count($contract_list) > 0) {
            $period_list = array();
            foreach ($contract_list as $contract) {
                $contract->fetch_optionals();
                if (!empty($contract->array_options['options_rm_timeslots_periods']))
                    $period_list[] = $contract->array_options['options_rm_timeslots_periods'];
            }
            $periods = implode(', ', $period_list);
        }
    }

    if (empty($periods)) $periods = $conf->global->REQUESTMANAGER_TIMESLOTS_PERIODS;

    if (!empty($periods)) {
        $date_infos = dol_getdate($date);
        $date_year = $date_infos['year'];
        $date_month = $date_infos['mon'];
        $date_day = $date_infos['mday'];
        $date_weekday = $date_infos['wday'];
        $date_hour = $date_infos['hours'] * 60 + $date_infos['minutes'];

        $period_list = requestmanagertimeslots_get_periods($periods);
        foreach ($period_list as $period) {
            $begin_year = $period['begin']['year'];
            $begin_month = $period['begin']['month'];
            $begin_day = $period['begin']['day'];
            $begin_daytype = $period['begin']['daytype'];
            $begin_hour = $period['begin']['hour'] * 60 + $period['begin']['minute'];

            $end_year = $period['end']['year'];
            $end_month = $period['end']['month'];
            $end_day = $period['end']['day'];
            $end_hour = $period['end']['hour'] * 60 + $period['end']['minute'];

            if ((!isset($begin_year) || ($begin_year <= $date_year && $date_year <= $end_year)) &&
                (!isset($begin_month) || ($begin_month <= $date_month && $date_month <= $end_month)) &&
                (!isset($begin_day) || ($begin_daytype == 'D' && $begin_day <= $date_day && $date_day <= $end_day) || ($begin_daytype == 'W' && $begin_day <= $date_weekday && $date_weekday <= $end_day)) &&
                (!isset($begin_hour) || ($begin_hour <= $date_hour && $date_hour <= $end_hour))
            ) {
                return $period;
            }
        }
    } else {
        return true;
    }

    return false;
}

/**
 *  Get list of out of time infos for a company
 *
 * @param	int              $soc_id        ID of the company
 * @return	array|string
 */
function requestmanagertimeslots_get_out_of_time_infos($soc_id)
{
    global $db;

    $sql = "SELECT MONTH(ac.datec) AS oot_month, YEAR(ac.datec) AS oot_year, COUNT(*) AS nb_forced FROM " . MAIN_DB_PREFIX . "actioncomm AS ac" .
        " WHERE ac.code = 'AC_RM_FCOOT' AND ac.fk_soc = " . $soc_id .
        " GROUP BY oot_year, oot_month" .
        " ORDER BY oot_year DESC, oot_month DESC";

    $resql = $db->query($sql);
    if (!$resql) {
        return $db->lasterror();
    }

    $current_year = dol_print_date(dol_now(), '%Y');
    $infos = array();
    while ($obj = $db->fetch_object($resql)) {
        if ($current_year == $obj->oot_year) {
            $infos[$obj->oot_year . '-' . $obj->oot_month] = array('year' => $obj->oot_year, 'month' => $obj->oot_month, 'count' => $obj->nb_forced);
        } else {
            if (!isset($infos[$obj->oot_year])) {
                $infos[$obj->oot_year] = array('year' => $obj->oot_year, 'count' => $obj->nb_forced);
            } else {
                $infos[$obj->oot_year]['count'] = $infos[$obj->oot_year]['count'] + $obj->nb_forced;
            }
        }
    }

    return $infos;
}
