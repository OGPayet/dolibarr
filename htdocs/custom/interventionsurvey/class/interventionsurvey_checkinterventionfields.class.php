<?php
/* Copyright (C) 2020 Alexis LAURIER
 *
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
 * \file    interventionsurvey/class/interventionsurvey_checkinterventionfields.class.php
 * \ingroup interventionsurvey
 *
 * Class to check data on intervention and intervention survey
 */

/**
 * Class ActionsInterventionSurvey
 */
class InterventionCheckFields
{
    /**
     * @var Trans language object to use
     */
    public $langs;

    /**
     * @var InterventionSurvey Instance on which check data
     */
    public $object;

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
    */
    public function __construct($object, $langs)
    {
        $langs->load("interventionsurvey@interventionsurvey");
        $this->langs = $langs;
        $this->object = $object;
    }

    /**
     * Check if stakeholder signature contains correct data
     * @return string[] return array of validation errors
     **/
    public function isStakeholderSignatureEmpty()
    {
        $errors = array();
        if (empty($this->object->array_options['options_stakeholder_signature'])
            || empty(json_decode($this->object->array_options['options_stakeholder_signature'])->value)
            ) {
            $errors[] = $this->langs->trans('InterventionSurveyMissingStakeholderSignature', $this->object->id);
        }
        return $errors;
    }

    /**
     * Check if customer signature contains correct data
     * @return string[] return array of validation errors
     **/
    public function isCustomerSignatureEmpty()
    {
        $errors = array();
        if (empty($this->object->array_options['options_customer_signature'])
            || (
                empty(json_decode($this->object->array_options['options_customer_signature'])->value)
                && !json_decode($this->object->array_options['options_customer_signature'])->isCustomerAbsent)
            ) {
            $errors[] = $this->langs->trans('InterventionSurveyMissingCustomerSignature', $this->object->id);
        }
        return $errors;
    }

    /**
     * Check if intervention lines contain correct data
     * @return string[] return array of validation errors
     **/
    public function isInterventionLinesEmpty()
    {
        $errors = array();
        if (empty($this->object->lines)) {
            $errors[] = $this->langs->trans('InterventionSurveyMissingInterventionLines', $this->object->label, $this->object->id);
        }
        return $errors;
    }

    /**
     * Check intervention data
     * @return string[] return array of validation errors
     **/
    public function checkIntervention()
    {
        return array_merge(
            $this->isStakeholderSignatureEmpty(),
            $this->isCustomerSignatureEmpty(),
            $this->isInterventionLinesEmpty()
        );
    }
}
