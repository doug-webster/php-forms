<?php
/**
 * a base class for option lists: select, checkbox, and radio
 */

namespace GZMP\Form\FormElements;

abstract class OptionsList extends \GZMP\Form\FormElement
{
    protected $options = array(); // for radio, checkbox, and select inputs, an array of value => option pairs

    /**
     * @param array $parameters - an array of settings for this element
     * @param string $method - the submission method used by the form to which this element belongs, if any; presently expected to be either "post" or "get"
     * @param array $record - an array of values (likely from a database record); if included, will attempt to find a default value in this array (if recordKey is not specified in parameters, will default to name attribute)
     */
    public function __construct(array $parameters = array(), string $method = 'post', array $record = null)
    {
        if (! empty($parameters['options']))
            $this->setOptions($parameters['options'], $method, $record);

        parent::__construct($parameters, $method, $record);
    }

    // returns true or false if the submitted value is selected (or checked)
    public function isOptionSelected(string $option_value)
    {
        $value = ($this->submitted() && is_null($this->getAttribute('disabled')))
            ? $this->value : (! is_null($this->getAttribute('value'))
            ? $this->getAttribute('value') : '');
        $option_value = trim($option_value);
        if (! is_null($value)) {
            // check for array since multiple options can be selected (if multiple attribute set)
            if ((is_array($value) && in_array($option_value, $value)) 
                || (! is_array($value) && $value == $option_value)) {
                return true;
            }
        }
        return false;
    }

    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @return string returns a string of the selected option(s) with option labels replacing values (which may not be user friendly)
     */
    public function getValueForOutput()
    {
        $value = $this->getProcessedValue();

        // replace value attribute values with display text
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                if (! empty($this->options[$v]))
                    $value[$k] = $this->options[$v];
            }
            $value = \GZMP\Utility::implode_recursive(', ', $value);
        } elseif (is_string($value) && ! empty($this->options[$value])) {
            // for display, use option value rather than key
            $value = $this->options[$value];
        }

        return $value;
    }
}
