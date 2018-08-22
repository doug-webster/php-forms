<?php
/**
 * models an HTML select along with an associated label
 */

namespace GZMP\Form\FormElements;

class Select extends \GZMP\Form\FormElements\OptionsList
{
    protected $disabled_options = array();

    /**
     * @param array $parameters - an array of settings for this element
     * @param string $method - the submission method used by the form to which this element belongs, if any; presently expected to be either "post" or "get"
     * @param array $record - an array of values (likely from a database record); if included, will attempt to find a default value in this array (if recordKey is not specified in parameters, will default to name attribute)
     */
    public function __construct(array $parameters = array(), string $method = 'post', array $record = null)
    {
        $this->setTagName('select');
        $this->setIsEmptyTag(false);
        if (isset($parameters['disabled_options']))
            $this->disabled_options = $parameters['disabled_options'];
        parent::__construct($parameters, $method, $record);
    }

    /**
     * @param array $exclude_attributes - an array of attribute names to exclued from the returned array
     * @return string - returns an HTML string of this tag including it's attributes and contents
     */
    public function getHTML(array $exclude_attributes = array())
    {
        $options = $this->getOptions();
        if (! is_array($options))
            return '';

        $option_objects = array();

        $placeholder = (! is_null($this->getAttribute('placeholder'))) ? \GZMP\HTML::escape($this->getAttribute('placeholder')) : '';
        // automatically show a placeholder if not required and not multiple and size not set, or if a placeholder has been set
        if ((is_null($this->getAttribute('required'))
            && is_null($this->getAttribute('multiple'))
            && is_null($this->getAttribute('size')))
            || ! empty($placeholder)) {
            $attributes = array('value' => '', 'class' => 'placeholder');
            $option_objects[] = new \GZMP\HTMLTag('option', $attributes, $placeholder);
        }

        $option_objects = array_merge($option_objects, self::getOptionsObjectsFromOptionsArray($options));

        $this->setContents($option_objects);

        $exclude_attributes = array('type', 'value', 'placeholder');
        return parent::getHTML($exclude_attributes);
    }

    /**
     * @param array $options - an array of value => label pairs; label can potentially be an array which indicates an optgroup
     * @return array returns an of HTMLTag objects corresponding to options
     */
    private function getOptionsObjectsFromOptionsArray(array $options)
    {
        $option_objects = array();
        foreach ($options as $option_value => $option_text) {
            // indicates an optgroup
            if (is_array($option_text)) {
                $attributes = (isset($option_text['attributes'])) ? $option_text['attributes'] : array();
                $optgroup = new \GZMP\HTMLTag('optgroup', $attributes);
                $optgroup_options = (isset($option_text['options'])) ? $option_text['options'] : array();
                $optgroup->setContents(self::getOptionsObjectsFromOptionsArray($optgroup_options));
                $option_objects[] = $optgroup;
            } else {
                $attributes = array('value' => $option_value);
                if ($this->isOptionSelected($option_value))
                    $attributes['selected'] = 'selected';
                if (in_array($option_value, $this->disabled_options))
                    $attributes['disabled'] = 'disabled';
                $option_objects[] = new \GZMP\HTMLTag('option', $attributes, $option_text);
            }
        }
        return $option_objects;
    }
}
