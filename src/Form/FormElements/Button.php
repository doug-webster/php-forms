<?php
/**
 * model of HTML buttons
 */

namespace GZMP\Form\FormElements;

class Button extends \GZMP\Form\FormElement
{
    /**
     * @param array $parameters - an array of settings for this element
     * @param string $method - the submission method used by the form to which this element belongs, if any; presently expected to be either "post" or "get"
     * @param array $record - an array of values (likely from a database record); if included, will attempt to find a default value in this array (if recordKey is not specified in parameters, will default to name attribute)
     */
    public function __construct(array $parameters = array(), string $method = 'post', array $record = null)
    {
        $this->setTagName('button');
        $this->setIsEmptyTag(false);
        parent::__construct($parameters, $method, $record);
        $this->setContents($this->label);
    }

    /**
     * bypass validation for buttons
     */
    public function validateInput(bool $strict = false, bool $change = true)
    {
        $this->clearErrors();
    }

    /**
     * exclude button values from display output
     */
    public function getFormattedValue($return_type = 'html')
    {
        return '';
    }
}
