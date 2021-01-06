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
// $bloc
// $form


// Protection to avoid direct call of template
if (empty($bloc)) {
    print "Error, template page can't be called as URL";
    dol_syslog("Error, template page can't be called as URL : " . $_SERVER["PHP_SELF"] . "?" . $_SERVER["QUERY_STRING"], LOG_ERR);
    exit;
}
?>

<?php

if (isset($readonly) || !isset($bloc->description_editable)) {
    ?>
    <tr>
            <td><?php print $langs->trans('InterventionSurveyBlocQuestionDescriptionTitle') ?></td>
            <td><?php print $bloc->description ?></td>
        </tr>
    <?php
} else {
?>
        <tr>
            <td>
                <?php print $langs->trans('InterventionSurveyBlocQuestionDescriptionTitle'); ?>
            </td>
            <td>
                <input type="text" class="flat" name="<?php print 'bloc[' . $bloc->id . ']["description"]'; ?>" value="<?php print $bloc->description; ?>" />
            </td>
        </tr>
<?php
}
?>
