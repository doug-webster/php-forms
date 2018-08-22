<?php
/**
 * models a list of HTML checkbox options, with one associated label for the list and individual labels for each option
 */

namespace GZMP\Form\FormElements;

class CheckboxList extends \GZMP\Form\FormElements\InputList
{
    /**
     * @return string returns a string of the type class to be used with the element's HTML wrappers. Differentiates between a single checkbox and checkbox list
     */
    public function getTypeClass()
    {
        return 'checkbox-list';
    }

    /**
     * @return mixed returns getValue() with writein options values replacing associated checkbox values
     */
    public function getProcessedValue()
    {
        $values = $this->getValue();
        if (! is_array($values))
            return $values;

        // if a custom (user entered) value has been selected for a checkbox list, use this as value
        foreach ($this->getCustomOptions() as $option_value => $option)
        {
            if (in_array($option->getAttribute('name'), $values)) {
                $key = array_keys($values, $option->getAttribute('name'))[0];
                $values[$key] = $option->getValue();
            }
        }

        return $values;
    }
}
