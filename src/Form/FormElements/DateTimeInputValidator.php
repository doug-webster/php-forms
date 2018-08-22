<?php
/**
 * validation method shared among date/time input types
 */

namespace GZMP\Form\FormElements;

trait DateTimeInputValidator
{
    /**
     * performs extra validation for this element
     * @param bool $change - if true the function will attempt to modify an invalid value such that it becomes valid
     */
    public function validateDateTimeInput(bool $change = true)
    {
        $label = new \GZMP\HTMLTag('span', array('class' => 'label'), $this->getIdentifyingText());
        $type = strtolower($this->getAttribute('type'));

        // check to make sure value can be interpreted as a date/time
        $utc = strtotime($this->value);
        if ($utc === false || $utc == -1)
            return $this->addError("{$label->getHTML()} is not in the correct format." . (isset($this->human_readable) ? " ({$this->human_readable})" : ''));

        // check to make sure value is in range
        if (! is_null($this->getAttribute('min'))) {
            $utc_min = strtotime($this->getAttribute('min'));
            if (($utc_min === false || $utc_min == -1) && $utc < $utc_min) {
                if ($change && !empty($this->date_format)) {
                    $this->value = date($this->date_format, $utc_min);
                } else {
                    return $this->addError("{$label->getHTML()} is below minimum allowed value of {$this->getAttribute('min')}.");
                }
            }
        }

        if (! is_null($this->getAttribute('max'))) {
            $utc_max = strtotime($this->getAttribute('max'));
            if (($utc_max === false || $utc_max == -1) && $utc > $utc_max) {
                if ($change && !empty($this->date_format)) {
                    $this->value = date($this->date_format, $utc_max);
                } else {
                    return $this->addError("{$label->getHTML()} is above maximum allowed value of {$this->getAttribute('max')}.");
                }
            }
        }

        // I don't know how date/time steps can be handled simply

        return true;
    } 

}
