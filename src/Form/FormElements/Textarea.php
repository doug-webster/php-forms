<?php
/**
 * models an HTML textarea along with an associated label
 */

namespace GZMP\Form\FormElements;

class Textarea extends \GZMP\Form\FormElement
{
    /**
     * @param array $parameters - an array of settings for this element
     * @param string $method - the submission method used by the form to which this element belongs, if any; presently expected to be either "post" or "get"
     * @param array $record - an array of values (likely from a database record); if included, will attempt to find a default value in this array (if recordKey is not specified in parameters, will default to name attribute)
     */
    public function __construct(array $parameters = array(), string $method = 'post', array $record = null)
    {
        $this->setTagName('textarea');
        $this->setIsEmptyTag(false);
        parent::__construct($parameters, $method, $record);
    }

    /**
     * @param array $exclude_attributes - an array of attribute names to exclued from the returned array
     * @return string - returns an HTML string of this tag including it's attributes and contents
     */
    public function getHTML(array $exclude_attributes = array())
    {
        if ($this->submitted() && is_null($this->getAttribute('disabled'))) {
            $value = $this->getValue();
        } else {
            $value = (! is_null($this->getAttribute('value'))) ? $this->getAttribute('value') : '';
        }

        $this->setContents($value, true);

        $exclude_attributes = array('type', 'value');
        return parent::getHTML($exclude_attributes);
    }

}
