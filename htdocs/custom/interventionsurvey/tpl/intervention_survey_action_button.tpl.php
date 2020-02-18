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

<div class="right">
<div class="inline-block divButAction">
        <?php
        if (!$user->rights->interventionsurvey->survey->write) {
        ?>
                <a class="butActionRefused" href="#" title="<?php print $langs->trans("NotAllowed") ?>">
                <?php print $langs->trans("Modify") ?>
                </a>
        <?php
        } else if($readonly) {
        ?>
                        <a class="butAction" href="<?php print $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&survey_bloc_question_id=' . $bloc->id . '&action=edit_question_bloc#interventionsurvey_anchor_surveyblocquestion_' . $bloc->id ?>"> <?php print $langs->trans("Modify") ?>
        <?php
        }
        else
        {
        ?>
                <input type="submit" class="button" value="<?php print $langs->trans("Save") ?>">
                &nbsp;&nbsp;&nbsp;
                <input type="button" class="button" value="<?php print $langs->trans("Cancel") ?>">
        <?php
        }
        ?>
    </div>
 </div>
