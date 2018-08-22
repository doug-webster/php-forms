<?php
/**
 * intended as an implementation of the factory design pattern; a factory for creating FormElements
 */

namespace GZMP\Form;

class FormElementFactory
{
    /**
     * create form elements
     * @param array $parameters - an array of settings for the element to be created
     * @param \GZMP\Form\Form $form - a Form object; allows a form element to be aware of certain properties of a form to which it belongs
     * @return FormElement the created form element
     */
    public static function create(array $parameters = array(), \GZMP\Form\Form $form = null)
    {
        $type = (isset($parameters['attributes']['type']))
                ? $parameters['attributes']['type'] : '';
        switch (strtolower($type)) {
            case 'textarea':
                $class = 'Textarea';
                break;
            case 'select':
                $class = 'Select';
                break;
            case 'checkbox':
                if (empty($parameters['options']) || ! is_array($parameters['options']))
                    $class = 'CheckboxInput';
                else
                    $class = 'CheckboxList';
                    break;
            case 'radio':
                $class = 'RadioList';
                break;
            case 'button':
            case 'submit':
            case 'reset':
            //case 'image': // not currently supported
                $class = 'Button';
                break;
            case 'file':
                $class = 'FileInput';
                break;
            case 'color':
                $class = 'ColorInput';
                break;
            case 'email':
                $class = 'EmailInput';
                break;
            case 'search':
                $class = 'SearchInput';
                break;
            case 'url':
                $class = 'URLInput';
                break;
            case 'date':
                $class = 'DateInput';
                break;
            // datetime was removed from HTML spec
            //case 'datetime':
            case 'datetime-local':
                $class = 'DatetimeLocalInput';
                break;
            case 'month':
                $class = 'MonthInput';
                break;
            case 'week':
                $class = 'WeekInput';
                break;
            case 'time':
                $class = 'TimeInput';
                break;
            case 'number':
                $class = 'NumberInput';
                break;
            case 'tel':
                $class = 'PhoneInput';
                break;
            case 'range':
                $class = 'RangeInput';
                break;
            case 'password':
                $class = 'PasswordInput';
                break;
            case 'hidden':
                $class = 'HiddenInput';
                break;
            case 'text':
            default:
                $class = 'TextInput';
        }

        $class = "\GZMP\Form\FormElements\\$class";
        if (! class_exists($class))
            $element = 'FormElement';
        if (isset($form))
            $element = new $class($parameters, $form->getMethod(), $form->getRecord());
        else
            $element = new $class($parameters);

        return $element;
    }

}