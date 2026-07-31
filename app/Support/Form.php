<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

class Form
{
    public static function __callStatic($method, $parameters)
    {
        $instance = new static();
        return $instance->$method(...$parameters);
    }

    public function label($name, $value, $options = []): HtmlString
    {
        $options = $this->htmlAttributes($options);
        $for = $options['for'] ?? $name;
        unset($options['for']);

        return new HtmlString('<label for="' . e($for) . '"' . $options . '>' . e($value) . '</label>');
    }

    public function text($name, $value = null, $options = []): HtmlString
    {
        return $this->input('text', $name, $value, $options);
    }

    public function email($name, $value = null, $options = []): HtmlString
    {
        return $this->input('email', $name, $value, $options);
    }

    public function number($name, $value = null, $options = []): HtmlString
    {
        return $this->input('number', $name, $value, $options);
    }

    public function textarea($name, $value = null, $options = []): HtmlString
    {
        $options = $this->htmlAttributes($options);
        $name = e($name);
        $value = e($value);

        return new HtmlString('<textarea name="' . $name . '"' . $options . '>' . $value . '</textarea>');
    }

    public function select($name, $list = [], $selected = null, $selectAttrs = []): HtmlString
    {
        $selectAttrs = $this->htmlAttributes($selectAttrs);
        $name = e($name);

        $options = '';
        foreach ($list as $value => $display) {
            $isSelected = ((string) $value === (string) $selected) ? ' selected' : '';
            $options .= '<option value="' . e($value) . '"' . $isSelected . '>' . e($display) . '</option>';
        }

        return new HtmlString('<select name="' . $name . '"' . $selectAttrs . '>' . $options . '</select>');
    }

    public function submit($value = null, $options = []): HtmlString
    {
        $options = $this->htmlAttributes($options);
        $value = e($value);

        return new HtmlString('<button type="submit"' . $options . '>' . $value . '</button>');
    }

    protected function input($type, $name, $value = null, $options = []): HtmlString
    {
        $options = $this->htmlAttributes($options);
        $name = e($name);
        $value = e($value);

        return new HtmlString('<input type="' . $type . '" name="' . $name . '" value="' . $value . '"' . $options . '>');
    }

    protected function htmlAttributes(array $attributes): string
    {
        $html = '';
        foreach ($attributes as $key => $value) {
            if (is_numeric($key)) {
                $html .= ' ' . e($value);
            } else {
                $html .= ' ' . e($key) . '="' . e($value) . '"';
            }
        }
        return $html;
    }
}
