<?php
/**
 * A base class for various form elements
 */

namespace GZMP\Form;

abstract class FormElement extends \GZMP\HTMLTag
{
    use \GZMP\Form\FormState;

    protected $label; // the text label for the field
    protected $value; // raw value submitted, if present
    protected $note; // a note with further instructions about the input
    protected $trim = true; // whether or not to trim value
    protected $recordKey; // key/index of corresponding data in Form::record

    /**
     * @param array $parameters - an array of settings for this element
     * @param string $method - the submission method used by the form to which this element belongs, if any; presently expected to be either "post" or "get"
     * @param array $record - an array of values (likely from a database record); if included, will attempt to find a default value in this array (if recordKey is not specified in parameters, will default to name attribute)
     */
    public function __construct(array $parameters = array(), string $method = 'post', array $record = null)
    {
        if (! empty($parameters['attributes']))
            $this->setAttributes($parameters['attributes']);

        if (! empty($parameters['label']))
            $this->setLabel(trim($parameters['label']));

        if (! empty($parameters['note']))
            $this->setNote($parameters['note']);

        if (! empty($parameters['recordKey'])) {
            $this->setRecordKey($parameters['recordKey']);
        } else {
            if (! empty($this->getAttribute('name'))) {
                // default to same name as form input name
                $this->setRecordKey($this->getAttribute('name'));
            }
        }

        $this->setId();
        $this->setMethod($method);
        if (isset($record))
            $this->setValueFromRecord($record);
        $this->setSubmitted(); // depends on method
        $this->setSubmittedValue(); // depends on method and type
    }

    /**
     * sets the placeholder value to the value of the label property (if set)
     * @param bool $overwrite - if true, method will overwrite any existing placeholder; defaults to false
     */
    public function setPlaceholderFromLabel(bool $overwrite = false)
    {
        if (! empty($this->label)
                && (empty($this->getAttribute('placeholder'))
                || $overwrite))
            $this->setAttribute('placeholder', $this->label);
    }

    /**
     * sets the label property to the value of the placeholder attribute (if set)
     * @param bool $overwrite - if true, method will overwrite any existing label; defaults to false
     */
    public function setLabelFromPlaceholder(bool $overwrite = false)
    {
        if (! empty($this->getAttribute('placeholder'))
                && (empty($this->label) || $overwrite))
            $this->label = $this->getAttribute('placeholder');
    }

    /**
     * PHP converts periods and spaces in names to underscores, which can cause
     * trouble finding the values. This function attempts to convert them back (modifies PHP's superglobals)
     * @param string $name - the value of the name attribute of this element which should match the key to search for in PHP's superglobals
     */
    protected function updateRequestGlobals(string $name)
    {
        if (! $this->submitted()
            || empty($name)
            || empty($this->getMethod()))
            return;

        $alt_name = preg_replace('/ |\./', '_', $name, -1, $count);
        if ($count) {
            if (isset($_REQUEST[$alt_name])) {
                $_REQUEST[$name] = $_REQUEST[$alt_name];
                unset($_REQUEST[$alt_name]);
            }
            if (strtolower($this->getMethod()) == 'get' && isset($_GET[$alt_name]))  {
                $_GET[$name] = $_GET[$alt_name];
                unset($_GET[$alt_name]);
            } elseif (strtolower($this->getMethod()) == 'post' && isset($_POST[$alt_name])) {
                $_POST[$name] = $_POST[$alt_name];
                unset($_POST[$alt_name]);
            }
        }
    }

    /**
     * attempts to get element's submitted value from  PHP's superglobals
     * @return mixed - the element's submitted value (which could be a string or an array) or null if not found
     */
    protected function getSubmittedValue(string $name)
    {
        if (! $this->submitted() || empty($this->getMethod()))
            return;

        $this->updateRequestGlobals($name);

        if (strtolower($this->getMethod()) == 'get' && isset($_GET[$name]))  {
            return $_GET[$name];
        } elseif (strtolower($this->getMethod()) == 'post' && isset($_POST[$name])) {
            return $_POST[$name];
        }
    }

    /**
     * set element's value property to it's submitted value if applicable and possible
     * also cleans the value: trim (if set) and reverses magic quotes if applicable
     */
    // sets $this->value to what was submitted if possible
    public function setSubmittedValue()
    {
        if (! $this->submitted() || empty($this->getAttribute('name')))
            return;

        $name = $this->getAttribute('name');
        // if [*] found in name, remove; $matches used below
        if ($count = preg_match_all('/\[.*?\]/', $name, $matches)) {
            $this->value = null; // useful?
            if (! empty($matches[0])) {
                $name = str_replace($matches[0], '', $name);
            }
        }

        // set value
        $this->value = $this->getSubmittedValue($name);

        if (strtolower($this->getAttribute('type')) == 'file')
            return;

        // if specific array indexes are imbedded in name, check array for these keys
        // for example, if name is "field[1]", then $matches[0] = '[1]' and
        // $this->value = array(1 => 'value1');
        // we want $this->value = 'value1'
        if (! empty($matches[0])) {
            $i = 0;
            while (is_array($this->value) && count($this->value) <= 1 && ! empty($matches[0][$i]) && $i < $count) {
                $key = str_replace(array('[', ']'), '', $matches[0][$i]);
                $this->value = $this->value[$key];
                $i++;
            }
        }

        // clean value
        if (is_array($this->value)) {
            if ($this->trim())
                array_walk_recursive($this->value, '\GZMP\Utility::trimd');
            array_walk_recursive($this->value, '\GZMP\Utility::reverse_magic_quotes');
        } else {
            if ($this->trim())
                \GZMP\Utility::trimd($this->value);
            \GZMP\Utility::reverse_magic_quotes($this->value);
        }
    }

    /**
     * differs from HTMLTag method in that it will include the submitted value if applicable,
     * and also will append "[]" to the element's name if necessary
     * @param array $exclude_attributes - an array of attribute names to exclued from the returned array
     * @return string - returns a string of this tag's attributes and values, appropriate for insertion into the HTML tag
     */
    public function getAttributeString($exclude_attributes = array())
    {
        $attributes = $this->getAttributes($exclude_attributes);

        // if form submitted, use submitted value rather than initial value
        // this will also set the value if submitted and no value attribute specified
        if ($this->submitted()
            && is_null($this->getAttribute('disabled'))
            && ! in_array($this->getAttribute('type'), array('select', 'checkbox', 'radio'))) {
            $attributes['value'] = $this->value;
        }

        $attrs = array();
        foreach ($attributes as $attribute => $attribute_value) {
            if (is_array($attribute_value)) // shouldn't be an array, but just in case
                continue;

            $attribute_value = \GZMP\HTML::escape($attribute_value);

            // adding '[]' allows for multiple checkboxes to be captured as an array by PHP
            if ($attribute == 'name' && ! is_null($this->getAttribute('multiple')))
                $attribute_value .= '[]';

            $attrs[] = "{$attribute}='{$attribute_value}'";
        }

        return implode(' ', $attrs);
    }

    /**
     * set id if not explicitly set
     */
    public function setId()
    {
        if (! empty($this->getAttribute('id')))
            return;

        $replace = '_';
        $name = $this->getAttribute('name');
        $name = preg_replace('/[^0-9a-zA-Z]+/', $replace, $name);
        $name = trim($name, $replace);
        $name = \GZMP\HTML::escape($name);
        $this->setAttribute('id', "i_{$name}");
    }

    public function setLabel(string $label)
    {
        $this->label = $label;
    }

    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @return string HTML of a label tag for this element
     */
    public function getLabelHTML()
    {
        $attributes = array(
            'for' => (! is_null($this->getAttribute('id'))) ? $this->getAttribute('id') : '',
        );
        $label = new \GZMP\HTMLTag('label', $attributes);

        $label->setContents($this->getLabel(), true);

        if (! is_null($this->getAttribute('required')))
            $label->appendToAttribute('class', 'required');

        if (! empty($this->getErrors()))
            $label->appendToAttribute('class', 'attention');

        return $label->getHTML() . "\n";
    }

    /**
     * @return string returns a string to use to identify this element; defaults to label if not empty or placeholder
     */
    public function getIdentifyingText()
    {
        return (empty($this->label) && ! empty($this->getAttribute('placeholder')))
            ? $this->getAttribute('placeholder') : $this->label;
    }

    /**
     * @return string returns a string of the type class to be used with the element's HTML wrappers. In most cases (but not all) this will be the element's type
     */
    public function getTypeClass()
    {
        return strtolower($this->getAttribute('type'));
    }

    /**
     * returns extended HTML for this element including label and HTML wrappers
     * @param bool $inc_label - whether or not to include the element's lable. While this defaults to true, some elements (buttons) will never have labels
     * @param string $input - allows specifying of HTML to be wrapped
     * @return string HTML of this element including label and HTML wrappers
     */
    public function getWrappedHTML(bool $inc_label = true, string $input = '')
    {
        $html = array();
        $type = strtolower($this->getAttribute('type'));

        $classes = array();
        $type_class = $this->getTypeClass();
        if (! empty($type_class))
            $classes[] = $type_class;
        if (! empty($this->getErrors()))
            $classes[] = 'attention';
        if (!is_null($this->getAttribute('disabled')))
            $classes[] = 'disabled';
        if (in_array($type, array('submit', 'reset', 'button')))
            $classes[] = 'aligned';
        $classes = implode(' ', $classes);

        $html[] = "<div class='form-element-wrapper {$classes}' id='form_" . $this->getAttribute('id') . "'>\n";

        if (in_array($type, array('submit', 'reset', 'button')))
            $inc_label = false;
        if ($inc_label)
            $html[] = $this->getLabelHTML();

        if (! empty($input)) {
            $html[] = $input;
        } else {
            $html[] = '<span class="input-wrapper">' . $this->getHTML() . '</span>';
            if (! empty($this->getNote())) {
                $html[] = '<span class="input-note">' . \GZMP\HTML::escape($this->getNote()) . '</span>';
            }
        }

        $html[] = "</div>\n";

        return implode("\n", $html);
    }

    /**
     * adds message to object's errors if element is required but the submitted value is blank
     * @return mixed will return a boolean value if the submitted value is blank, null otherwise
     */
    public function validateRequired()
    {
        $label = new \GZMP\HTMLTag('span', array('class' => 'label'), $this->getIdentifyingText());
        // if required, check to make sure value exists and is not blank
        if (is_null($this->value) || $this->value == '' || $this->value == array()) {
            if (! is_null($this->getAttribute('required')) && is_null($this->getAttribute('disabled'))) {
                $this->addError("{$label->getHTML()} is a required field.");
                return false;
            } else {
                return true;
            }
        }
    }

    /**
     * adds message to object's errors if submitted value doesn't match either the pattern attribute (if specified) or patterns determined by element's type
     * @param bool $strict - if true, requires submitted value to exactly match the pattern defined in the HTML spec (if any); defaults to false
     * not to be confused with date/time inputs validations found in DateTimeInputValidator
     */
    public function validatePattern(bool $strict = false)
    {
        $label = new \GZMP\HTMLTag('span', array('class' => 'label'), $this->getIdentifyingText());
        $match_value = $this->getProcessedValue();
        if (is_array($match_value))
            $match_value = \GZMP\Utility::implode_recursive(', ', $match_value);

        // if necessary, check to make sure value matches pattern
        if ($this->getAttribute('pattern') && ! preg_match("/{$this->getAttribute('pattern')}/", $match_value))
            return $this->addError("{$label->getHTML()} is not in the correct format.");

        if ($pattern  = \GZMP\CommonData::getPattern(strtolower($this->getAttribute('type')))) {
            if ($strict && ! preg_match("/^{$pattern}$/", $match_value))
                return $this->addError("{$label->getHTML()} is not in the correct format." . (isset($this->human_readable) ? " ({$this->human_readable})" : ''));
        }

        return true;
    }

    /**
     * determines whether or not the input receieved is valid
     * adds message(s) to object's errors if validation errors are found
     * @param bool $strict - if true, requires submitted value to exactly match the pattern defined in the HTML spec (if any); defaults to false
     * @param bool $change - if true the function will attempt to modify an invalid value such that it becomes valid
     */
    public function validateInput(bool $strict = false, bool $change = true)
    {
        $this->clearErrors();

        if (! is_null($this->getAttribute('readonly')))
            return true;

        // return if required and blank, and don't run other checks if not required and blank
        if (gettype($this->validateRequired()) == 'boolean')
            return;

        $this->validatePattern($strict);

        if (method_exists($this, 'validateExtra'))
            $this->validateExtra($change);
    }

    /**
     * @return mixed returns the value as set by setSubmittedValue method
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return mixed returns getValue() with additional processing (allows writein options values to replace associated checkbox values)
     */
    public function getProcessedValue()
    {
        return $this->getValue();
    }

    /**
     * @return mixed returns getProcessedValue() with additional processing intended for display (allows for option labels to replace corresponding values which may not be user friendly)
     */
    public function getValueForOutput()
    {
        return $this->getProcessedValue();
    }

    /**
     * @param string $return_type - either "html", "html_email", or "text"; defaults to "html" if not specified though invalid values default to "text"
     * @return string returns getValueForOutput() transformed to a string (if not already) and formatted as specified
     */
    public function getFormattedValue(string $return_type = 'html')
    {
        $return_type = strtolower($return_type);
        if (! in_array($return_type, array('html', 'text', 'html_email')))
            $return_type = 'text';

        $value = $this->getValueForOutput();

        if (is_array($value))
            $value = \GZMP\Utility::implode_recursive(', ', $value);
        $value = trim($value);

        $label = $this->getIdentifyingText();

        if ($return_type == 'text')
            return rtrim($label, ':') . ":\n    {$value}\n";

        $label = \GZMP\HTML::escape($label);
        $htmlSafeValue = \GZMP\HTML::escape($value);
        if ($return_type = 'html')
            return "<div><label>{$label}</label> <span class='value'>{$htmlSafeValue}</span></div>\n";
        if ($return_type = 'html_email')
            return "<p><b>{$label}</b><br>\n&nbsp;&nbsp;&nbsp;&nbsp;<span>{$htmlSafeValue}</span></p>\n";
    }

    public function setNote(string $note)
    {
        $this->note = $note;
    }

    public function getNote()
    {
        return $this->note;
    }

    public function setTrim($trim)
    {
        $this->trim = (bool)$trim;
    }

    public function trim()
    {
        return (bool)$this->trim;
    }

    /**
     * attempts to set default value attribute value from a record
     * @param array $record - an array of values (likely from a database record)
     */
    public function setValueFromRecord(array $record)
    {
        $key = $this->getRecordKey();
        // set value of "value" attribute to corresponding record value if present
        if (! empty($key) && isset($record[$key])) {
            $v = $record[$key];
            if (! empty($v) || $v === '0')
                $this->setAttribute('value', $v);
        }
    }

    public function setRecordKey($key)
    {
        $this->recordKey = $key;
    }

    public function getRecordKey()
    {
        return $this->recordKey;
    }

}
