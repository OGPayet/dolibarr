<?php
/* Copyright (C) 2018   Open-DSI            <support@open-dsi.fr>
/* Copyright (C) 2020   Alexis LAURIER      <contact@alexislaurier.fr>
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

// Need to have following variables defined:
// $user
// $langs
// $hookmanager
// $action
// $form
// $bloc
// $object - must be an interventionsurvey object, which extends fichinter
// $extrafields_interventionsurvey_surveyblocquestion
// $extrafields_interventionsurvey_surveyquestion

// Protection to avoid direct call of template
if (empty($bloc)) {
    print "Error, template page can't be called as URL";
    dol_syslog("Error, template page can't be called as URL : " . $_SERVER["PHP_SELF"] . "?" . $_SERVER["QUERY_STRING"], LOG_ERR);
    exit;
}
?>

<?php
$attached_files = array();
foreach ($bloc->attached_files as $filename) {
    $attached_files[] = isset($object->attached_files[$filename]) ? $object->attached_files[$filename] : $langs->trans('InterventionSurveyErrorFileNotFound', $filename);
}
?>
<tr>
    <td><?php print $langs->trans('Documents') ?></td>
    <td><?php print implode(' , ', $attached_files) ?></td>
</tr>
