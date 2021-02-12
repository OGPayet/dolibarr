<?php

class InterventionCheckFields {
    var $langs;
    var $object;
    var $errors;

    /**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	*/
	function __construct($object) {
        global $langs;

        $langs->load("interventionsurvey@interventionsurvey");

        $this->langs = $langs;
        $this->object = $object;
		$this->errors = array();
    }

    // Check array options
    public function isArrayOptionsEmpty() {
        $error = array();

        if (empty($this->object->array_options)) {
            $error[] = $this->langs->trans('InterventionSurveyMissingArrayOptions', $this->object->id); 
        }

        return $error;
    }

    // Check stakeholder signature
    public function isStakeholderSignatureEmpty() {
        $error = array();

        if (empty($this->object->array_options['options_stakeholder_signature'])) {
            $error[] = $this->langs->trans('InterventionSurveyMissingStakeholderSignature', $this->object->id); 
        } else if (empty(json_decode($this->object->array_options['options_stakeholder_signature'])->value)) {
            $error[] = $this->langs->trans('InterventionSurveyMissingStakeholderSignature', $this->object->id); 
        }

        return $error;
    }

    // Check customer signature
    public function isCustomerSignatureEmpty() {
        $error = array();

        if (empty($this->object->array_options['options_customer_signature'])) {
            $error[] = $this->langs->trans('InterventionSurveyMissingCustomerSignature', $this->object->id); 
        } else if (empty(json_decode($this->object->array_options['options_customer_signature'])->value)) {
            if (!json_decode($this->object->array_options['options_customer_signature'])->isCustomerAbsent) {
                $error[] = $this->langs->trans('InterventionSurveyMissingCustomerSignature', $this->object->id); 
            }
        }

        return $error;
    }

    // Check intervention lines
    public function isInterventionLinesEmpty() {
        $error = array();

        if (empty($this->object->lines)) {
            $error[] = $this->langs->trans('InterventionSurveyMissingInterventionLines', $this->object->label, $this->object->id); 
        }

        return $error;
    }

    public function checkInterventionFields() {
        return array_merge($this->isArrayOptionsEmpty(), $this->isStakeholderSignatureEmpty(), 
            $this->isCustomerSignatureEmpty(), $this->isInterventionLinesEmpty());
    }
}

?>
