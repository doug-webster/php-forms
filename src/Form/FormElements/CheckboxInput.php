<?php
/**
 * models a single HTML checkbox input along with an associated label
 */

namespace GZMP\Form\FormElements;

class checkboxInput extends \GZMP\Form\FormElements\Input
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
     * @param array $exclude_attributes - an array of attribute names to exclued from the returned array
     * @return string - returns an HTML string of this tag including it's attributes and contents
     */
    public function getHTML(array $exclude_attributes = array())
    {
        if ($this->submitted() && is_null($this->getAttribute('disabled'))) {
            if (! is_null($this->value)) {
                $this->setAttribute('checked', 'checked');
            } else {
                $this->removeAttribute('checked');
            }
        }

        return parent::getHTML($exclude_attributes);
    }

    /**
     * attempts to set default checked state from a record
     * @param array $record - an array of values (likely from a database record)
     */
    public function setValueFromRecord(array $record)
    {
        // set value of "value" attribute to corresponding record value if present
        if (! empty($this->recordKey) && isset($record[$this->recordKey])) {
            if (! empty($record[$this->recordKey]))
                $this->setAttribute('checked', 'checked');
            else
                $this->removeAttribute('checked');
        }
    }

}
