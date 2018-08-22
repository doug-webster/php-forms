<?php
/**
 * models an HTML color file along with an associated label
 */

namespace GZMP\Form\FormElements;

class FileInput extends \GZMP\Form\FormElements\Input
{
    public $disallowed_file_extensions = array('.exe', '.dll', '.js'); // reject file uploads of these types
    public $allowed_file_extensions = array(); // limit file uploads to these types
    public $filepath = ''; // used with file types; path to location to save file

    /**
     * @param array $parameters - an array of settings for this element
     * @param string $method - the submission method used by the form to which this element belongs, if any; presently expected to be either "post" or "get"
     * @param array $record - an array of values (likely from a database record); if included, will attempt to find a default value in this array (if recordKey is not specified in parameters, will default to name attribute)
     */
    public function __construct(array $parameters = array(), string $method = 'post', array $record = null)
    {
        if (! empty($parameters['filepath']))
            $this->filepath = $parameters['filepath'];

        $parameters['type'] = 'file';
        parent::__construct($parameters, $method, $record);
    }

    /**
     * attempts to get element's submitted value from  PHP's superglobal $_FILES
     * @return mixed - returns an array of submitted file information or null if form not submitted or $name not found in $_FILES
     */
    protected function getSubmittedValue($name)
    {
        if (! $this->submitted())
            return;

        $this->updateRequestGlobals($name);

        // the file field seems to usually be present even if no file has been submitted
        // therefore the following check probably isn't very useful, but we'll leave it here in case.
        if (empty($_FILES[$name]))
            return;

        // convert $_FILES into one usable array
        if (! is_array($_FILES[$name]['name'])) {
            $files = array($_FILES[$name]);
        } else {
            foreach ($_FILES[$name]['name'] as $i => $filename) {
                foreach ($_FILES[$name] as $key => $value) {
                    $files["{$name}[{$i}]"][$key] = $value[$i];
                }
            }
        }
        return $files;
    }

    /**
     * determines whether or not the input receieved is valid
     * adds message(s) to object's errors if validation errors are found
     * @param bool $strict - unused here; present only so function signatures match
     * @param bool $change - unused here; present only so function signatures match
     */
    public function validateInput(bool $strict = false, bool $change = true)
    {
        $this->clearErrors();

        if (! is_null($this->getAttribute('readonly')))
            return;

        $_FILE_UPLOAD_ERROR_CODES = \GZMP\CommonData::getFileUploadErrorCodes();
        $label = new \GZMP\HTMLTag('span', array('class' => 'label'), $this->getIdentifyingText());

        $files = $this->value;
        // check each file
        foreach ($files as $key => $file) {
            if ($file['error'] == UPLOAD_ERR_NO_FILE) {
                unset($files[$key]);
                if (! is_null($this->getAttribute('required')) && is_null($this->getAttribute('disabled'))) {
                    return $this->addError("{$label->getHTML()} is a required field.");
                } else {
                    continue;
                }
            } elseif ($file['error'] != UPLOAD_ERR_OK) {
                unset($files[$key]);
                $msg = 'File upload error';
                if (array_key_exists($file['error'], $_FILE_UPLOAD_ERROR_CODES)) {
                    $msg .= ': ' . $_FILE_UPLOAD_ERROR_CODES[$file['error']] . "\r\n";
                } else {
                    $msg .= ".\r\n";
                }
                return $this->addError($msg);
            } else {
                array_walk_recursive($this->allowed_file_extensions, '\GZMP\Utility::strtolowerd');
                array_walk_recursive($this->disallowed_file_extensions, '\GZMP\Utility::strtolowerd');
                $ext = strtolower(strrchr($file['name'], '.'));
                if (empty($ext)
                    || in_array($ext, $this->disallowed_file_extensions)
                    || (! empty($this->allowed_file_extensions)
                    && ! in_array($ext, $this->allowed_file_extensions))) {
                    return $this->addError("{$label->getHTML()} contains a file type which is not allowed.");
                }
            }
        }

        $this->value = $files; // update the value; may have removed bad uploads
    }

    /**
     * utility for moving a temporary uploaded file to a more permanent local
     * @param string $dir - the path to the location where the file should be stored
     * @param array $files - an array of uploaded file information generally matching what is contained in PHP's $_FILES superglobal
     * @param bool $safe_filename - converts the filename to a "safe" version, making all lowercase (to prevent files with the same name but mixed case), and replacing non-letters and numbers with underscores
     * note that regarless of $safe_filename, the filename may have a number appended in order to prevent overwriting an existing file
     * @return array returns an array with "errors" and "filenames" keys; the former contains an array of errors (if any); the latter contains an array of final filenames as actually saved
     */
    public function saveUploadedFiles(string $dir = '', array $files = array(), bool $safe_filename = true)
    {
        if (empty($files))
            $files = $this->value;
        if (empty($files) || ! is_array($files))
            return;
        if (empty($dir))
            $dir = $this->filepath;
        $dir = rtrim($dir, '/\\'); // strip trailing slash or backslash
        $errors = array();
        $filenames = array();

        if (! is_dir($dir)) {
            if (! @mkdir($dir, 0777, true)) {
                $errors[] = "<div class='form-errors'>Can't create file directory {$dir}.</div>\n";
            }
        }
        if (is_dir($dir) && is_writable($dir)) {
            foreach($files as $i => $file) {
                // if filename already exists, rename current file
                $j = 0;
                $name = $file['name'];
                if ($safe_filename) {
                    $name = strtolower($name);
                    // \p indicates a unicode class; L includes letters and N numbers
                    // the u at the end apparently turns on unicode mode
                    // we're replacing non-letters and numbers with an underscore
                    $name = preg_replace('/[^\p{L}\p{N}\.]+/u', '_', $name);
                    $name = trim($name, '_');
                }
                $pieces = pathinfo($name);
                $ext = (isset($pieces['extension'])) ? ".{$pieces['extension']}" : '';
                while (file_exists("{$dir}/{$name}") && $j < 10000) {
                    $name = "{$pieces['filename']}{$j}{$ext}";
                    $j++;
                }

                // move temp file to new location
                if (! move_uploaded_file($file['tmp_name'], "{$dir}/{$name}")) {
                    $errors[] = "<div class='form-errors'>There was an error attempting to save an uploaded file.</div>\n";
                } else {
                    $this->value[$i]['name'] = $name;
                    $filenames[] = $name;
                }
            } // end loop for each file
        } else {
            $errors[] = "<div class='form-errors'>Can't write to file directory.</div>\n";
        }

        return array('errors' => $errors, 'filenames' => $filenames);
    }

    /**
     * @return mixed returns getProcessedValue() as a comma separated list of filenames
     */
    public function getValueForOutput()
    {
        $files =  $this->getProcessedValue();

        $output = array();
        foreach ($files as $file) {
            $output[] = $file['name'];
        }

        return implode(', ', $output);
    }
}
