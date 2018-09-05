# php-forms
A package for creating and managing HTML5 forms with PHP.

The PHP Forms package is a set of PHP classes intended to minimize the redundant work of creating and managing HTML forms and inputs. Input parameters are defined once. Default options will be used for most parameters to aid in ease of setup. Once an input is created, the object automates output of HTML, validation, error handling, retrieving values to save and/or display, and more. This package also brings some consistency between those inputs which use the input tag and those using other tags (select, textarea, etc.) which allows for the ability to quickly change between types. It is intended to be HTML5 compliant and should conform to PSR 1, 2, and 4. The only inputs which are not supported are "datetime", which has been removed from the HTML spec, and "image" (an image button).

In order to use the PHP Forms package, you will need to add it to your project (most likely in the vendors directory) and set up an autoloader (composer recommended).

Here is a common, basic usage example:
```php
use GZMP\Form\Form;
use GZMP\Form\FormElementFactory;

$form = new Form();

$form->addElement(FormElementFactory::create(array(
	'label' => 'Text Input Label',
	'attributes' => array(
		'name' => 'text1',
	),
), $form));

echo $form->getForm();
```
The form defaults to the post method with an action target of the current script. If no type is specified (or an invalid type submitted), the input will default to a text type input. The FormElementFactory::create method accepts an array of options as it first argument. (This array of options is passed to form element's constructor.) Though no options are strictly required, one will in most cases want to set ['attributes']['name'] at the least. The "attribute" option is an array of attribute names and values corresponding to attributes for the element's HTML tag. With a few exceptions, all the attributes here will be included in the element's HTML output.

The element's type is specified via the ['attribute']['type'] parameter. The possible values are the same as that of an HTML input tag (with the exception of "image") with a few additions. "select", "textarea", will create the corresponding HTML elements. Button types ("button", "reset", "submit") will generate button elements.

Three elements are often or always used as lists of options: select, radio, and checkbox. (A checkbox can also be used as a standalone input.) Options for these are specified using an "option" parameter set to an array of value-label pairs. The array key is the value which will be submitted, while the array value will be used as an option label. Setting elements up this way allows for easy switching between types. Converting from a select to a checkbox list is as simple as changing the type parameter!

```php
$form->addElement(FormElementFactory::create(array(
	'label' => 'Select',
	'attributes' => array(
		'name' => 'select1',
		'type' => 'select',
	),
	'options' => array(
		'opt1' => 'Option 1',
		'opt2' => 'Option 2',
		'opt3' => 'Option 3',
		'opt4' => 'Option 4',
	),
), $form));
```
There are a couple of possible modifications to the options array. First, for selects, optgroups can be created like so:
```php
	'options' => array(
		'submited value text1' => 'Displayed text 1',
		'submited value text2' => 'Displayed text 2',
		array(
			'attributes' => array(
				'label' => "Optgroup Label",
			),
			'options' => array(
				'submited value text3' => 'Displayed text 3',
				'submited value text4' => 'Displayed text 4',
			)
		),
		'submited value text5' => 'Displayed text 5',
		'submited value text6' => 'Displayed text 6',
	),
```
Second, for checkbox and radio lists, it is possible to add custom write-in options! To do so, submit an array of parameters for a new form element.
```php
	'options' => array(
		'submited value text1' => 'Normal option',
		'submited value text2' => array(), // custom input using defaults
		array(
			'attributes' => array(
				'placeholder' => 'Optional placeholder',
				'type' => 'date', // any input can be used
				'name' => 'an_alternate_name', // a name can be specified though this is not necessary
			),
		),
	),
```
Though `$form->getForm()` will do everything for you except for defining the inputs, in most cases I've needed greater control over the output. Here is a more common example:
```php
use GZMP\Form\Form;
use GZMP\Form\FormElementFactory;

$form = new Form();

$form->addElement(FormElementFactory::create(array(
	'label' => 'Text Input Label',
	'attributes' => array(
		'name' => 'text1',
	),
), $form));

// do these after all the form elements are added to the form
if ($form->submitted() && ! $form->validationRun())
	$form->validateForm();

// process valid form submission
if ($form->submitted() && empty($form->getErrors())) {
	// handle form submission here
}

echo $form->getErrorsHTML();
?>

<form <?= $form->getAttributeString(); ?>>
	<?= $form->getElement('text1')->getWrappedHTML(); ?>
	<button type='submit'>Submit</button>
	<!-- can create PHP button form elements, but in most cases, there is no advantage in doing so -->
</form>
```
