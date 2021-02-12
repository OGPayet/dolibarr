<?php

class InterventionCheckFields {
    var $object;
    var $errors;

    /**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	*/
	function __construct($object) {
        $this->object = $object;
		$this->errors = array();
    }

    // Check array options
    public function isArrayOptionsEmpty() {
        global $langs;

        $error = array();

        if (empty($this->object->array_options)) {
            $error[] = $langs->trans('InterventionSurveyMissingArrayOptions', $this->object->id); 
        }

        return $error;
    }

    // Check stakeholder signature
    public function isStakeholderSignatureEmpty() {
        global $langs;

        $error = array();

        if (empty($this->object->array_options['options_stakeholder_signature'])) {
            $error[] = $langs->trans('InterventionSurveyMissingStakeholderSignature', $this->object->id); 
        } else if (empty(json_decode($this->object->array_options['options_stakeholder_signature'])->value)) {
            $error[] = $langs->trans('InterventionSurveyMissingStakeholderSignature', $this->object->id); 
        }

        return $error;
    }

    // Check customer signature
    public function isCustomerSignatureEmpty() {
        global $langs;

        $error = array();

        if (empty($this->object->array_options['options_customer_signature'])) {
            $error[] = $langs->trans('InterventionSurveyMissingCustomerSignature', $this->object->id); 
        } else if (empty(json_decode($this->object->array_options['options_customer_signature'])->value)) {
            if (!json_decode($this->object->array_options['options_customer_signature'])->isCustomerAbsent) {
                $error[] = $langs->trans('InterventionSurveyMissingCustomerSignature', $this->object->id); 
            }
        }

        return $error;
    }

    // Check intervention lines
    public function isInterventionLinesEmpty() {
        global $langs;

        $error = array();

        if (empty($this->object->lines)) {
            $error[] = $langs->trans('InterventionSurveyMissingInterventionLines', $this->object->label, $this->object->id); 
        }

        return $error;
    }

    public function checkInterventionFields() {
        return array_merge($this->isArrayOptionsEmpty(), $this->isStakeholderSignatureEmpty(), 
            $this->isCustomerSignatureEmpty(), $this->isInterventionLinesEmpty());
    }
}

?>
