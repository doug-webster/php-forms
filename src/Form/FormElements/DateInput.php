<?php
/**
 * models an HTML date input along with an associated label
 */

namespace GZMP\Form\FormElements;

class DateInput extends \GZMP\Form\FormElements\Input
{
    use DateTimeInputValidator; // use trait

    private $date_format = 'Y-m-d';
    private $human_readable = 'YYYY-MM-DD';

    /**
     * @param array $parameters - an array of settings for this element
     * @param string $method - the submission method used by the form to which this element belongs, if any; presently expected to be either "post" or "get"
     * @param array $record - an array of values (likely from a database record); if included, will attempt to find a default value in this array (if recordKey is not specified in parameters, will default to name attribute)
     */
    public function __construct(array $parameters = array(), string $method = 'post', array $record = null)
    {
        $parameters['type'] = 'date';
        parent::__construct($parameters, $method, $record);
    }

    /**
     * performs extra validation for this element
     * @param bool $change - if true the function will attempt to modify an invalid value such that it becomes valid
     */
    public function validateExtra(bool $change = true)
    {
        return $this->validateDateTimeInput($change);
    }
}
