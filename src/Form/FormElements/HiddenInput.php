<?php
/**
 * models an HTML hidden input along with an associated label
 */

namespace GZMP\Form\FormElements;

class HiddenInput extends \GZMP\Form\FormElements\Input
{
    /**
     * @param array $parameters - an array of settings for this element
     * @param string $method - the submission method used by the form to which this element belongs, if any; presently expected to be either "post" or "get"
     * @param array $record - an array of values (likely from a database record); if included, will attempt to find a default value in this array (if recordKey is not specified in parameters, will default to name attribute)
     */
    public function __construct(array $parameters = array(), string $method = 'post', array $record = null)
    {
        $parameters['type'] = 'checkbox';
        parent::__construct($parameters, $method, $record);
    }

    /**
     * bypass validation for hidden inputs
     */
    public function validateInput(bool $strict = false, bool $change = true)
    {
        $this->clearErrors();
    }

    /**
     * since hidden inputs are hidden, we shouldn't ever need a label nor HTML wrappers; therefore, we'll just pass back only the input's HTML
     */
    public function getWrappedHTML(bool $inc_label = true, string $input = '')
    {
        return (! empty($input)) ? $input : $this->getHTML();
    }

    /**
     * exclude hidden values from display output
     */
    public function getFormattedValue(string $return_type = 'html')
    {
        return '';
    }
}
