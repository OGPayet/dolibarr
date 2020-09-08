<?php
/* Copyright (C) 2020 Alexis LAURIER
 * *
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    lib/interventionsurvey_jupiter.lib.php
 * \ingroup interventionsurvey
 * \brief   Library files with common functions for jupiter pdf
 */

 /**
  * Function to know if there is interesting content into a text
  * @param $text String
  * @return boolean
  */
function isThereInterestingTextContent($text = "")
{
    if(empty($text)){
        return false;
    }
    $text = dol_htmlentitiesbr_decode($text);
    $text = preg_replace("/[^a-zA-Z0-9]+/", "", $text);
    $text = trim($text);
    return !empty($text);
}
