<?php
/**
 * models an HTML color input along with an associated label
 */

namespace GZMP\Form\FormElements;

class ColorInput extends \GZMP\Form\FormElements\Input
{
    /**
     * @param array $parameters - an array of settings for this element
     * @param string $method - the submission method used by the form to which this element belongs, if any; presently expected to be either "post" or "get"
     * @param array $record - an array of values (likely from a database record); if included, will attempt to find a default value in this array (if recordKey is not specified in parameters, will default to name attribute)
     */
    public function __construct(array $parameters = array(), string $method = 'post', array $record = null)
    {
        $parameters['type'] = 'color';
        parent::__construct($parameters, $method, $record);
    }

    /**
     * determines whether or not the input receieved is valid
     * adds message(s) to object's errors if validation errors are found
     * @param bool $strict - if true, requires submitted value to exactly match the pattern defined in the HTML spec, otherwise attempts to match as any valid CSS color value; defaults to false
     * @param bool $change - unused here; present only so function signatures match
     */
    public function validateInput(bool $strict = false, bool $change = true)
    {
        $this->clearErrors();

        if (! is_null($this->getAttribute('readonly')))
            return;

        $_PATTERNS = \GZMP\CommonData::getCommonRegexPatterns();
        $label = new \GZMP\HTMLTag('span', array('class' => 'label'), $this->getIdentifyingText());
        $type = (! empty($this->getAttribute('type'))) ? strtolower($this->getAttribute('type')) : '';
        $match_value = (! is_array($this->value)) ? trim($this->value) : \GZMP\Utility::implode_recursive(', ', $this->value);

        // return error message if required and blank
        // don't run other checks if not required and blank
        if ($this->validateRequired() === false)
            return;

        // if necessary, check to make sure value matches pattern
        if (! empty($this->getAttribute('pattern')) && ! preg_match("/{$this->getAttribute('pattern')}/", $match_value))
            return $this->addError("{$label->getHTML()} is not in the correct format.");

        if ($strict) {
            $color_type = 'hex_strict';
        } elseif (strpos($match_value, '#') === 0) {
            $color_type = 'hex';
        } elseif (stripos($match_value, 'rgba') === 0) {
            $this->value = strtolower($this->value);
            $color_type = 'rgba';
        } elseif (stripos($match_value, 'rgb') === 0) {
            $this->value = strtolower($this->value);
            $color_type = 'rgb';
        } elseif (stripos($match_value, 'hsla') === 0) {
            $this->value = strtolower($this->value);
            $color_type = 'hsla';
        } elseif (stripos($match_value, 'hsl') === 0) {
            $this->value = strtolower($this->value);
            $color_type = 'hsl';
        } elseif (array_key_exists(strtolower($this->value), \GZMP\CommonData::getCSSColorNames())) {
            // valid value; no action necessary here
        } else {
            return $this->addError("{$label->getHTML()} is not a recognized color value.");
        }

        if (! empty($color_type) && ! empty($_PATTERNS[$type][$color_type]) && ! preg_match("/^{$_PATTERNS[$type][$color_type]}$/", $match_value))
            return $this->addError("{$label->getHTML()} is not a recognized color value.");
    }
    
}
