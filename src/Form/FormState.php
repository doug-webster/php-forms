<?php
/**
 * certain common traits shared by both Forms and FormElements
 */

namespace GZMP\Form;

trait FormState
{
    protected $submitted; // whether or not the form was submitted
    protected $method; // the HTTP method used to submit form
    protected $errors = array(); // validation errors

    public function setMethod(string $method)
    {
        $this->method = $method;
    }

    public function getMethod()
    {
        return $this->method;
    }

    /**
     * determines whether or not the form has been submitted and sets the submitted variable accordingly
     * @param mixed $submitted - if specified, the submitted property will be set to the corresponding value
     */
    public function setSubmitted($submitted = null)
    {
        // by default, assumes form has been submitted if form method's corresponding global variable is not empty
        if (! isset($submitted)) {
            $submitted = false;
            $method = strtolower($this->getMethod());
            if (($method == 'post' && (! empty($_POST)|| ! empty($_FILES)))
                || ($method == 'get' && ! empty($_GET)))
                $submitted = true;
        }
        $this->submitted = $submitted;
    }

    public function submitted()
    {
        return (bool)$this->submitted;
    }

    public function setErrors(array $errors)
    {
        $this->errors = $errors;
    }

    public function addError(string $error)
    {
        $this->errors[] = $error;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @return string HTML of object's validation error(s)
     */
    public function getErrorsHTML()
    {
        $html = array();
        foreach ($this->getErrors() as $error) {
            $html[] = "<div class='form-error'>{$error}</div>\n";
        }
        return implode("\n", $html);
    }

    /**
     * remove any existing errors
     */
    public function clearErrors()
    {
        $this->setErrors(array());
    }
}
