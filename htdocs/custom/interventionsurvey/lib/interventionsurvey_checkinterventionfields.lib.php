<?php

class InterventionCheckFields {
    var $object;

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
        if (empty($this->object->array_options)) {
            return true;
        }

        return false;
    }

    // Check stakeholder signature
    public function isStakeholderSignatureEmpty() {
        if (empty($this->object->array_options['options_stakeholder_signature'])) {
            return true;
        } else if (empty(json_decode($this->object->array_options['options_stakeholder_signature'])->value)) {
            return true;
        }

        return false;
    }

    // Check customer signature
    public function isCustomerSignatureEmpty() {
        if (empty($this->object->array_options['options_customer_signature'])) {
            return true;
        } else if (empty(json_decode($this->object->array_options['options_customer_signature'])->value)) {
            if (!json_decode($this->object->array_options['options_customer_signature'])->isCustomerAbsent) {
                return true;
            }
        }

        return false;
    }

    // Check intervention lines
    public function isInterventionLinesEmpty() {
        if (empty($this->object->lines)) {
            return true;
        }

        return false;
    }

    public function checkInterventionFields() {
        return array_merge($this->isArrayOptionsEmpty(), $this->isStakeholderSignatureEmpty(), 
            $this->isCustomerSignatureEmpty(), $this->isInterventionLinesEmpty());
    }
}

?>