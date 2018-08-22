<?php
/**
 * models a list of HTML radio options, with one associated label for the list and individual labels for each option
 */

namespace GZMP\Form\FormElements;

class RadioList extends \GZMP\Form\FormElements\InputList
{
    /**
     * @return string returns a string of the type class to be used with the element's HTML wrappers. Differentiates between a single checkbox and checkbox list
     */
    public function getTypeClass()
    {
        return 'radio-list';
    }

    /**
     * @return mixed returns getValue() with writein options values replacing associated checkbox values
     */
    public function getProcessedValue()
    {
        $value = $this->getValue();

        // if a custom (user entered) value has been selected for a radio list, use this as value
        foreach ($this->getCustomOptions() as $option_value => $option)
        {
            // if ($value == $option_value)
            if ($value == $option->getAttribute('name'))
                $value = $option->getValue();
        }

        return $value;
    }
}
