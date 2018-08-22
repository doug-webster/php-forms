<?php
/**
 * a model representing an HTML form
 */

namespace GZMP\Form;

class Form extends \GZMP\HTMLTag
{
    use \GZMP\Form\FormState {
        getErrorsHTML as FormState_getErrorsHTML;
    }

    protected $elements = array(); // array of FormElement objects
    protected $validationRun = false; // whether or not the validation function has been run
    protected $includeSpambotTest = true; // whether or not to include the anti-spambot test
    protected $labelsFromPlaceholders = false; // allows for quickly switching from use of labels to use of placeholders
    protected $placeholdersFromLabels = false; // allows for quickly switching from use of labels to use of placeholders
    protected $record = array(); // an array of data to use for form values initially

    /**
     * @param array $parameters - an array of settings for the form
     */
    public function __construct(array $parameters = array())
    {
        $this->setTagName('form');

        if (! empty($parameters['attributes']))
            $this->setAttributes($parameters['attributes']);

        if (! empty($parameters['labelsFromPlaceholders']))
            $this->setLlabelsFromPlaceholders(true);

        if (! empty($parameters['placeholdersFromLabels']))
            $this->setPlaceholdersFromLabels(true);

        if (! empty($parameters['record']))
            $this->setRecord($parameters['record']);

        // set defaults
        if (! $this->getAttribute('class'))
            $this->setAttribute('class', 'form-module');

        if (! $this->getAttribute('method'))
            $this->setAttribute('method', 'post');
        $this->setMethod($this->getAttribute('method'));

        if (! $this->getAttribute('action'))
            $this->setAttribute('action', basename($_SERVER['PHP_SELF']));

        $this->setSubmitted();
    }

    /**
     * adds FormElements to this form. Allows this form to access its elements
     * will adapt element's label/placeholder according to settings
     * will set the form's entype attribute as needed
     * @param \GZMP\Form\FormElement $element - the FormElement to add to this form
     */
    public function addElement(\GZMP\Form\FormElement $element)
    {
        if (! is_null($element->getAttribute('name')))
            $this->elements[$element->getAttribute('name')] = $element;
        else
            $this->elements[] = $element;

        // allow ability to use labels for placeholders or vice versa
        if ($this->placeholdersFromLabels())
            $element->setPlaceholderFromLabel();
        if ($this->labelsFromPlaceholders())
            $element->setLabelFromPlaceholder();

        // ensure forms with file inputs have the enctype set
        if (! $this->getAttribute('enctype')
            && strtolower($element->getAttribute('type')) == 'file') {
            $this->setAttribute('enctype', 'multipart/form-data');
        }
    }

    /**
     * @return mixed will return the form element specified by name or null if not found
     */
    public function getElement(string $name)
    {
        if (isset($this->elements[$name]))
            return $this->elements[$name];
    }

    public function getElements()
    {
        return $this->elements;
    }

    /**
     * @return string returns HTML of anti-spambot input and related script (implements honeypot method of bot detection)
     */
    public function getAntiSpambotField()
    {
        // anti-spambot test
        return <<<HTML
<input type="text" id="human-check" name="email_check" value="Please delete the contents of this field." size="40" />
<script type="text/javascript">
    var el = document.getElementById('human-check');
    el.value = '';
    el.style.display = 'none';
</script>\n
HTML;
    }

    /**
     * checks to see if the submitted antispambot test passes. Will add message to errors if fails
     */
    public function validateAntiSpambotTest()
    {
        // anti-spambot test
        if (strtolower($this->getAttribute('method')) == 'post'
            && isset($_POST['email_check'])
            && trim($_POST['email_check']) == '') {
                return true;
        } elseif (strtolower($this->getAttribute('method')) == 'get'
            && isset($_GET['email_check'])
            && trim($_GET['email_check']) == '') {
                return true;
        }

        $this->addError('Security Check Failed');
        return false;
    }

    /**
     * @return array returns an array of errors, including those of child form elements
     */
    public function getErrors()
    {
        $errors = $this->errors;

        foreach ($this->getElements() as $element) {
            $errors = array_merge($errors, $element->getErrors());
        }

        return $errors;
    }

    /**
     * @return string returns HTML of validation errors suitable for output
     */
    public function getErrorsHTML()
    {
        $errors_html = $this->FormState_getErrorsHTML();
        return (! empty($errors_html)) ? "<div class='form-errors'>\n{$errors_html}</div>\n" : '';
    }

    /**
     * @param mixed $contents - allows a way to specific the contents of the form, either as a string or an array of HTMLTags
     * @param array $exclude_elements - specify child elements to exclude from output
     * @return string returns HTML for the entire form, including errors if applicable, anit-spambot test, and all other child elements
     */
    public function getForm($contents = null, array $exclude_elements = array())
    {
        $html = array();

        if ($this->submitted() && ! $this->validationRun())
            $this->validateForm();

        // include errors
        $html[] = $this->getErrorsHTML();

        // include required string
        $html[] = "<div class='required'>Required fields.</div>\n";

        if ($this->includeSpambotTest())
            $html[] = $this->getAntiSpambotField();

        // default output for each field
        if (! $contents) {
            foreach ($this->getElements() as $element) {
                $name = $element->getAttribute('name');
                if (! is_null($name) && in_array($name, $exclude_elements))
                    continue;
                $html[] = $element->getWrappedHTML();
            }
        }
        // if text/html was specified
        elseif (is_string($contents)) {
            $html[] = $contents;
        }
        // if an array of HTMLTag objects was specified
        elseif (is_array($contents)) {
            foreach ($contents as $el) {
                if (is_a($el, '\GZMP\HTMLTag'))
                    $html[] = $el->getHTML();
            }
        }

        // set the contents of this form element
        $this->setContents(implode("\n", $html));

        return $this->getHTML();
    }

    /**
     * Intended for use on "review" page. Puts submitted values into hidden inputs in order to resubmit/recapture values after confirmation
     * @param \GZMP\Form\FormElement confirm_button - an optional button to include in the form
     * @param array $exclude_elements - specify child elements to exclude from output
     * @return string HTML of the hidden form
     */
    public function getHiddenForm(?\GZMP\Form\FormElement $confirm_button = null, array $exclude_elements = array())
    {
        $html = array();

        foreach ($this->getElements() as $el_name => $element) {
            $value = $element->value;
            if (in_array($el_name, $exclude_elements))
                continue;
            $name = HTML::escape($el_name);
            if (is_array($value)) {
                foreach ($value as $v) {
                    $v = HTML::escape($v);
                    $html[] = "<input type='hidden' name='{$name}[]' value='{$v}' />\n";
                }
            } else {
                $value = HTML::escape($value);
                $html[] = "<input type='hidden' name='{$name}' value='{$value}' />\n";
            }
        }

        if ($this->includeSpambotTest())
            $html[] = $this->getAntiSpambotField();

        if (! empty($confirm_button) && is_a($confirm_button, '\GZMP\Form\FormElement'))
            $html[] = $confirm_button->getWrappedHTML();

        // set the contents of this form element
        $this->setContents(implode("\n", $html));

        return $this->getHTML();
    }

    /**
     * @param string $return_type - either "html", "html_email", or "text"; defaults to "html" if not specified though invalid values default to "text"
     * @param array $exclude_elements - specify child elements to exclude from output
     * @return string returns a string of submitted form data in the format specified
     */
    public function getValues(string $return_type = 'html', array $exclude_elements = array())
    {
        $contents = '';
        foreach ($this->getElements() as $element) {
            if (in_array($element->getAttribute('name'), $exclude_elements))
                continue;

            $contents .= $element->getFormattedValue($return_type);
        }

        switch (strtolower($return_type)) {
            case 'html':
                return "<div class='form-values-html'>\n{$contents}</div>\n";
            case 'html_email':
                return $contents;
            case 'text':
            default:
                return $contents;
        }
    }

    /**
     * run form validation methods
     */
    public function validateForm()
    {
        $this->errors = array(); // reset errors

        foreach ($this->getElements() as $element)
            $element->validateInput();

        if ($this->includeSpambotTest())
            $this->validateAntiSpambotTest();

        $this->setValidationRun(true);
    }

    public function setValidationRun($validation_run)
    {
        $this->validationRun = (bool)$validation_run;
    }

    public function validationRun()
    {
        return (bool)$this->validationRun;
    }

    public function setIncludeSpambotTest($includeSpambotTest)
    {
        $this->includeSpambotTest = (bool)$includeSpambotTest;
    }

    public function includeSpambotTest()
    {
        return (bool)$this->includeSpambotTest;
    }

    public function setLlabelsFromPlaceholders($labelsFromPlaceholders)
    {
        $this->labelsFromPlaceholders = (bool)$labelsFromPlaceholders;
    }

    public function labelsFromPlaceholders()
    {
        return (bool)$this->labelsFromPlaceholders;
    }

    public function setPlaceholdersFromLabels($placeholdersFromLabels)
    {
        $this->placeholdersFromLabels = (bool)$placeholdersFromLabels;
    }

    public function placeholdersFromLabels()
    {
        return (bool)$this->placeholdersFromLabels;
    }

    public function setRecord(array $record)
    {
        $this->record = $record;
    }

    public function getRecord()
    {
        return $this->record;
    }

    /**
     * @param string $key - key of data record to search for
     * @return mixed return corresponding value if found or null if not
     */
    public function getRecordElement(string $key)
    {
        if (isset($this->record[$key]))
            return $this->record['key'];
    }

}
