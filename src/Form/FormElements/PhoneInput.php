<?php
/**
 * models an HTML phone (tel) input along with an associated label
 */

namespace GZMP\Form\FormElements;

class PhoneInput extends \GZMP\Form\FormElements\Input
{
    /**
     * @param array $parameters - an array of settings for this element
     * @param string $method - the submission method used by the form to which this element belongs, if any; presently expected to be either "post" or "get"
     * @param array $record - an array of values (likely from a database record); if included, will attempt to find a default value in this array (if recordKey is not specified in parameters, will default to name attribute)
     */
    public function __construct(array $parameters = array(), string $method = 'post', array $record = null)
    {
        $parameters['type'] = 'tel';
        parent::__construct($parameters, $method, $record);
    }

    // attempts to format a string as a phone number; returns false on error
    // not internationalized beyond North American Numbering Plan
    public static function formatPhoneNumber($number)
    {
        $phone = explode('x', preg_replace('/[^0-9x]+/', '', strtolower($number))); // remove everything except for digits and "x"
        $ext = (! empty($phone[1])) ? $phone[1] : ''; // assume anything after an x is an extension
        $phone = $phone[0];
        if (strpos($phone, '1') === 0) {
            // remove '1' from beginning of number
            $phone = substr($phone, 1);
        }
        
        // a valid North American phone number ought to be ten digits at this point
        if (strlen($phone) != 10)
            return false;

        $areaCode = substr($phone, 0, 3);
        $prefix = substr($phone, 3, 3);
        $digits = substr($phone, 6, 4);
        return "{$areaCode}-{$prefix}-{$digits}" . ((! empty($ext)) ? " ext. {$ext}" : '');
    }

    /**
     * performs extra validation for this element
     * adds message(s) to object's errors if validation errors are found
     * @param bool $change - if true the function will attempt to modify an invalid value such that it becomes valid
     */
    public function validateExtra(bool $change = true)
    {
        $label = new \GZMP\HTMLTag('span', array('class' => 'label'), $this->getIdentifyingText());
        // will return false if there is an error
        if (! ($phone = self::formatPhoneNumber($this->value))) {
            return $this->addError("{$label->getHTML()} does not seem to be a valid phone number.");
        }
        // formats the phone number consistently
        if ($change)
            $this->value = $phone;
        
        return true;
    } 
    
}
