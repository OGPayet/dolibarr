<?php
/* Copyright (C) 2017  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2020 Alexis LAURIER <contact@alexislaurier.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file        class/digitalsignaturepeople.class.php
 * \ingroup     digitalsignaturemanager
 * \brief       This file is a CRUD class file for DigitalSignaturePeople (Create/Read/Update/Delete)
 */

dol_include_once("/core/class/extrafields.class.php");

/**
 * Class for DigitalSignaturePeople
 */
class ExtrafieldsToolbox extends ExtraFields
{
 /**
  * Method to clone extrafields from one elementtype to one another
  * @param string $sourceElementType Element type from which clone extrafields
  * @param string $destinationElementType Element type to which create and update extrafields
  * @param bool $removeOtherExtrafield Should we remove extrafield present in destination and not in source
  * @return string[] array of errors
  */
    public function cloneExtrafields($sourceElementType, $destinationElementType, $removeOtherExtrafield = true)
    {
        $errors = array();
        $this->fetch_name_optionals_label($sourceElementType, true);
        $this->fetch_name_optionals_label($destinationElementType, true);
        $sourceExtrafields = $this->attributes[$destinationElementType];
        $destinationExtrafields = $this->attributes[$destinationElementType];
        $sourceExtrafieldsKey = array_keys($sourceExtrafields['type']);
        $destinationExtrafieldsKey = array_keys($destinationExtrafields['type']);

        foreach ($sourceExtrafieldsKey as $sourceKey) {
            $attrname = $sourceKey;
            $label = $sourceExtrafields['label'][$sourceKey];
            $type = $sourceExtrafields['type'][$sourceKey];
            $length = $sourceExtrafields['size'][$sourceKey];
            $elementtype = $destinationElementType;
            $unique = $sourceExtrafields['unique'][$sourceKey];
            $required = $sourceExtrafields['required'][$sourceKey];
            $pos = $sourceExtrafields['pos'][$sourceKey];
            $param = $sourceExtrafields['param'][$sourceKey];
            $alwayseditable = $sourceExtrafields['alwayseditable'][$sourceKey];
            $perms = $sourceExtrafields['perms'][$sourceKey];
            $list = $sourceExtrafields['list'][$sourceKey];
            $help = $sourceExtrafields['help'][$sourceKey];
            $default = $sourceExtrafields['default'][$sourceKey];
            $computed = $sourceExtrafields['computed'][$sourceKey];
            $entity = $sourceExtrafields['entityid'][$sourceKey];
            $langfile = $sourceExtrafields['langfile'][$sourceKey];
            $enabled = $sourceExtrafields['enabled'][$sourceKey];
            $totalizable = $sourceExtrafields['totalizable'][$sourceKey];
            $printable = $sourceExtrafields['printable'][$sourceKey];

            $staticExtrafield = new Extrafields($this->db);
            if (in_array($sourceKey, $destinationExtrafieldsKey)) {
                //We update extrafields
                $staticExtrafield->update($attrname, $label, $type, $length, $elementtype, $unique, $required, $pos, $param, $alwayseditable, $perms, $list, $help, $default, $computed, $entity, $langfile, $enabled, $totalizable, $printable);
            } else {
                //We create extrafields
                $staticExtrafield->addExtraField($attrname, $label, $type, $pos, $size, $elementtype, $unique, $required, $default, $param, $alwayseditable, $perms, $list, $help, $computed, $entity, $langfile, $enabled, $totalizable, $printable);
            }
            $errors = array_merge($errors, $staticExtrafield->errors);
        }
        if ($removeOtherExtrafield) {
            foreach ($destinationExtrafields as $destinationKey) {
                if (!in_array($destinationKey, $sourceExtrafieldsKey)) {
                    $staticExtrafield = new Extrafields($this->db);
                    //We delete this extrafields
                    $staticExtrafield->delete($destinationKey, $destinationElementType)
                }
                $errors = array_merge($errors, $staticExtrafield->errors);
            }
        }
        return $errors;
    }
}
