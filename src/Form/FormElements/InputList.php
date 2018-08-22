<?php
/**
 * models a list of input options; used as a base class for checkbox and radio lists
 */

namespace GZMP\Form\FormElements;

use \GZMP\Form\FormElementFactory;
use \GZMP\Form\Form;

abstract class InputList extends \GZMP\Form\FormElements\OptionsList
{
    public $options_one_line = false; // if true, attempt to show checkbox or radio options on one line

    /**
     * @param array $parameters - an array of settings for this element
     * @param string $method - the submission method used by the form to which this element belongs, if any; presently expected to be either "post" or "get"
     * @param array $record - an array of values (likely from a database record); if included, will attempt to find a default value in this array (if recordKey is not specified in parameters, will default to name attribute)
     */
    public function __construct(array $parameters = array(), string $method = 'post', array $record = null)
    {
        if (! empty($parameters['options_one_line']))
            $this->options_one_line = true;
        
        // this allows us to get the name in setOptions wince the attributes aren't yet set when it is run intitially
        if (! empty($parameters['attributes']['name']))
            $this->setAttribute('name', $parameters['attributes']['name']);

        parent::__construct($parameters, $method, $record);
    }

    /**
     * sets this elements options
     * @param array $options - an array of value => label pairs for the various options; if the "label" (array value) is an array, it is assumed to be a parameter set for creating a "write-in" option
     * @param string $method - the submission method used by the form to which this element belongs, if any; presently expected to be either "post" or "get"
     * @param array $record - an array of values (likely from a database record); if included, will attempt to find a default value in this array (if recordKey is not specified in parameters, will default to name attribute)
     */
    public function setOptions(array $options, string $method = 'post', array $record = null)
    {
        // convert custom "write-in" options to FormElement objects
        $i = 1;
        foreach ($options as $option_value => $option_text) {
            if (! is_array($option_text))
                continue;

            $params = $option_text;

            // set up default name
            if (! isset($params['attributes']['name']))
                $params['attributes']['name'] = $this->getAttribute('name') . '_writein' . $i++;

            if (! isset($params['attributes']['placeholder']))
                $params['attributes']['placeholder'] = 'Other (please specify)';

            $form = new Form(array('method' => $method, 'record' => $record));
            $options[$option_value] = FormElementFactory::create($params, $form);
        }

        $this->options = $options;
    }

    /**
     * @param array $exclude_attributes - an array of attribute names to exclued from the returned array
     * @return string - returns an HTML string of this tag including it's attributes and options
     */
    public function getHTML(array $exclude_attributes = array())
    {
        $html = array();

        if (empty($this->options) || ! is_array($this->options))
            return '';

        $exclude_attributes = array('id', 'value');

        // if required set for checkboxes, will require every one to be selected - not what we want here
        if ($this->getAttribute('type') == 'checkbox' && count($this->options) > 1)
            $exclude_attributes[] = 'required';

        // allow for multiple checkboxes or radio options under one primary label
        if ($this->getAttribute('type') == 'checkbox' && (count($this->options) > 1 || ! is_null($this->getAttribute('multiple'))))
            $this->appendToAttribute('name', '[]', '');

        $attributes = $this->getAttributes($exclude_attributes);

        $id = (! empty($this->getAttribute('id'))) ? $this->getAttribute('id') : '';

        $wrapper = new \GZMP\HTMLTag('div', array('class' => 'form-options'));
        if ($this->options_one_line)
            $wrapper->appendToAttribute('class', 'one-line');

        $i = 0;
        $has_writeins = false;
        foreach ($this->getOptions() as $option_value => $option_text) {
            ++$i;

            $is_writein = (is_a($option_text, '\GZMP\Form\FormElement'));

            if ($is_writein) {
                $writein = &$option_text;
                $has_writeins = true;
            }

            $input_attrs = $attributes;

            $input = new \GZMP\HTMLTag('input', $input_attrs, null, true);

            if ($is_writein)
                $option_value = $writein->getAttribute('name');

            if ($this->isOptionSelected($option_value))
                $input->setAttribute('checked', 'checked');
            else
                $input->removeAttribute('checked');

            $input->setAttribute('id', "{$id}-{$i}");
            $input->setAttribute('value', $option_value);
            if ($is_writein)
                $input->appendToAttribute('onchange', 'toggleWriteInRequire(this.id, this.value);');

            $html[] = $input->getHTML();

            if ($is_writein) {
                // set up so that entering the write-in automatically checks the corresponding option
                $writein->appendToAttribute('onkeyup', "if (this.value != '') document.getElementById(\"{$id}-{$i}\").checked = true;", '; ');

                if (! empty($writein->label))
                    $html[] = $writein->getLabelHTML();
                $html[] = $writein->getHTML();
            } else {
                $option_text = \GZMP\HTML::escape($option_text);
                $label = new \GZMP\HTMLTag('label', array('for' => "", 'class' => 'inline'), $option_text);
                $html[] = $label->getHTML();
            }

            if (! $this->options_one_line)
                $html[] = "<br />\n";
        }

        // need to toggle required based on whether or not the corresponding checkbox/radio is checked
        if ($has_writeins)
            $html[] = <<<SCRIPT
<script>
function toggleWriteInRequire(id, name) {
    console.log(id);
    console.log(name);
    if (!id) return;
    var checkbox = document.getElementById(id);
    var writein = document.querySelector('[name="'+name+'"]');
    if (checkbox.checked)
        writein.setAttribute('required', 'required');
    else
        writein.removeAttribute('required');
}
</script>
SCRIPT;

        $wrapper->setContents(implode("\n", $html));
        
        return $wrapper->getHTML();
    }

    /**
     * @return array returns an array of custom "write-in" options
     */
    public function getCustomOptions()
    {
        $custom_options = array();
        foreach ($this->getOptions() as $key => $option) {
            if (is_a($option, '\GZMP\Form\FormElement'))
                $custom_options[$key] = $option;
        }
        return $custom_options;
    }

    /**
     * performs extra validation for this element: checks whether custom "write-in" options should be required and then validates these
     * @param bool $change - unused here; present only so function signatures match
     */
    public function validateExtra(bool $change = true)
    {
        foreach ($this->getCustomOptions() as $key => $option) {
            // if this is a custom input and the corresponding checkbox/radio is checked, require the input to be filled in
            if ($this->isOptionSelected($option->getAttribute('name')))
                $option->setAttribute('required', 'required');
            else
                $option->removeAttribute('required');

            $option->validateInput();
        }
    }

    /**
     * returns an array of errors, including those of custom "write-in" options
     */
    public function getErrors()
    {
        $errors = $this->errors;

        foreach ($this->getCustomOptions() as $key => $option) {
            $errors = array_merge($errors, $option->getErrors());
        }

        return $errors;
    }

}
