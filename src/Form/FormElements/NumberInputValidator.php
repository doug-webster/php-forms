<?php
/**
 * shared validation for number type inputs (number and range)
 */

namespace GZMP\Form\FormElements;

trait NumberInputValidator
{
    /**
     * performs extra validation for number input types
     * adds message(s) to object's errors if validation errors are found
     * @param bool $change - if true the function will attempt to modify an invalid value such that it becomes valid
     */
    public function validateExtra(bool $change = true)
    {
        $label = new \GZMP\HTMLTag('span', array('class' => 'label'), $this->getIdentifyingText());

        // check to make sure in range, numeric
        if (!is_numeric($this->value))
            return "{$label->getHTML()} must be numeric.";

        // check this before minimum and maximum so that if value is changed, we make sure it's not outside the proper range
        $step = $this->getAttribute('step');
        if (! is_null($step) && is_numeric($step) && ($remainder = fmod($this->value, $step)) != 0) {
            if ($change) {
                // set value to closest matching increment step
                $this->value = $this->value - $remainder + ($remainder > ($step / 2) ? $step : 0);
            } else {
                return $this->addError("{$label->getHTML()} isn't in a correct increment of {$step}.");
            }
        }

        $min = $this->getAttribute('min');
        if (! is_null($min) && is_numeric($min) && $this->value < $min) {
            if ($change) {
                $this->value = $min;
            } else {
                return $this->addError("{$label->getHTML()} is below minimum allowed value of {$min}.");
            }
        }

        $max = $this->getAttribute('max');
        if (! is_null($max) && is_numeric($max) && $this->value > $max) {
            if ($change) {
                $this->value = $max;
            } else {
                return $this->addError("{$label->getHTML()} is above maximum allowed value of {$max}.");
            }
        }

        return true;
    } 
    
}
