<?php
/**
 * models an HTML input tag along with an associated label; used as a base class for the various input types
 */

namespace GZMP\Form\FormElements;

class Input extends \GZMP\Form\FormElement
{
    /**
     * @param array $parameters - an array of settings for this element
     * @param string $method - the submission method used by the form to which this element belongs, if any; presently expected to be either "post" or "get"
     * @param array $record - an array of values (likely from a database record); if included, will attempt to find a default value in this array (if recordKey is not specified in parameters, will default to name attribute)
     */
    public function __construct(array $parameters = array(), string $method = 'post', array $record = null)
    {
        $this->setTagName('input');
        $this->setIsEmptyTag(true);
        parent::__construct($parameters, $method, $record);
    }

}
