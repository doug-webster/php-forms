<?php
/**
 * a class which models an HTML tag
 */

namespace GZMP;

class HTMLTag
{
    private $name;
    // an array of attributes to be included in the HTML tag
    protected $attributes = array();
    private $contents; // either a string or an array of HTMLTag objects
    private $isEmptyTag;

    /**
     * @param string $tag_name - the HTML tag to create, for example, "div" or "span"
     * @param array $attributes - an array of key-value pairs corresponding to attributes and values for the HTML tag
     * @param mixed $contents - either a string or an array of HTMLTag objects which comprise the contents of this HTML tag
     * @param bool $isEmptyTag - wether or not this tag is an "empty" tag (<tag /> as opposed to <tag></tag>)
     */
    public function __construct(string $tag_name = null, array $attributes = null, $contents = null, bool $isEmptyTag = false)
    {
        if (isset($tag_name))
            $this->setTagName($tag_name);

        if (isset($attributes) && is_array($attributes))
            $this->setAttributes($attributes);

        if (isset($contents))
            $this->setContents($contents);

        $this->setIsEmptyTag($isEmptyTag);
    }

    public function setTagName(string $name)
    {
        $this->name = $name;
    }

    public function getTagName()
    {
        return $this->name;
    }

    /**
     * @param mixed $contents - either a string or an array of HTMLTag objects which comprise the contents of this HTML tag
     */
    public function setContents($contents, bool $html_escape = false)
    {
        if (is_string($contents) && $html_escape)
            $this->contents = \GZMP\HTML::escape($contents);
        else
            $this->contents = $contents;
    }

    public function getContents()
    {
        return $this->contents;
    }

    /**
     * @return string - return a string of the conents of this HTML tag
     */
    public function getContentsHTML()
    {
        if (is_string($this->contents)) {
            return $this->contents;
        } elseif (is_array($this->contents)) {
            $html = array();
            foreach ($this->contents as $el) {
                if (is_a($el, '\GZMP\HTMLTag')) {
                    $html[] = $el->getHTML();
                }
            }
            return implode("\n", $html);
        }
    }

    public function setIsEmptyTag(bool $isEmptyTag)
    {
        $this->isEmptyTag = (bool)$isEmptyTag;
    }

    public function isEmptyTag()
    {
        return (bool)$this->isEmptyTag;
    }

    /**
     * set the specified attribute
     * @param string $name - name of attribute
     * @param string $value - value of attribute
     */
    public function setAttribute(string $name, string $value)
    {
        $this->attributes[$name] = $value;
    }

    /**
     * append value to end of specified attribute
     * @param string $name - name of attribute
     * @param string $value - value to append to attribute
     * @param string $separator - a string used to separate the new value from the existing value
     */
    public function appendToAttribute(string $name, string $value, string $separator = ' ')
    {
        $attr_value = (string)$this->getAttribute($name);
        if (is_null($attr_value))
            $attr_value = '';

        if ($attr_value != '')
            $attr_value .= $separator;

        $this->setAttribute($name, $attr_value . $value);
    }

    public function setAttributes(array $attributes = array())
    {
        $this->attributes = $attributes;
    }

    /**
     * @param array $exclude_attributes - an array of attribute names to exclued from the returned array
     * @return array return an array of attribute name-value pairs
     */
    public function getAttributes(array $exclude_attributes = array())
    {
        return array_diff($this->attributes, $exclude_attributes);
    }

    /**
     * @return mixed - return the value of the specifed attribute or null if not found
     */
    public function getAttribute(string $name)
    {
        if (isset($this->attributes[$name]))
            return $this->attributes[$name];
        return null;
    }

    public function removeAttribute(string $name)
    {
        unset($this->attributes[$name]);
    }

    /**
     * @param array $exclude_attributes - an array of attribute names to exclued from the returned array
     * @return string - returns a string of this tag's attributes and values, appropriate for insertion into the HTML tag
     */
    public function getAttributeString(array $exclude_attributes = array())
    {
        $attributes = array();
        foreach ($this->getAttributes($exclude_attributes) as $attribute => $attribute_value) {
            // shouldn't be an array, but just in case
            if (is_array($attribute_value))
                continue;

            $attribute_value = \GZMP\HTML::escape($attribute_value);

            $attributes[] = "{$attribute}='{$attribute_value}'";
        }
        return implode(' ', $attributes);
    }

    /**
     * @param array $exclude_attributes - an array of attribute names to exclued from the returned array
     * @return string - returns an HTML string of this tag including it's attributes and contents
     */
    public function getHTML(array $exclude_attributes = array())
    {
        $name = $this->getTagName();
        $contents = $this->getContentsHTML();
        $attributes = $this->getAttributeString($exclude_attributes);

        if ($this->isEmptyTag())
            return "<{$name} {$attributes} />";
        else
            return "<{$name} {$attributes}>{$contents}</{$name}>";
    }

}
