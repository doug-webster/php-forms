<?php
/**
 * models an HTML email input along with an associated label
 */

namespace GZMP\Form\FormElements;

class EmailInput extends \GZMP\Form\FormElements\Input
{
    /**
     * @param array $parameters - an array of settings for this element
     * @param string $method - the submission method used by the form to which this element belongs, if any; presently expected to be either "post" or "get"
     * @param array $record - an array of values (likely from a database record); if included, will attempt to find a default value in this array (if recordKey is not specified in parameters, will default to name attribute)
     */
    public function __construct(array $parameters = array(), string $method = 'post', array $record = null)
    {
        $parameters['type'] = 'email';
        parent::__construct($parameters, $method, $record);
    }

    /**
     * adds message to object's errors if submitted value doesn't match either the pattern attribute (if specified) or patterns determined by element's type
     * @param bool $strict - ignored; set strict to true when calling parent function so that email addresses will always be validated
     */
    public function validatePattern(bool $strict = false)
    {
        return parent::validatePattern(true);
    }
}
