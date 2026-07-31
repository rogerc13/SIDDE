<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

class Form
{
    public static function label($name, $value, $options = []): HtmlString
    {
        $for = $options['for'] ?? $name;
        unset($options['for']);
        $options = self::htmlAttributes($options);

        return new HtmlString('<label for="' . e($for) . '"' . $options . '>' . e($value) . '</label>');
    }

    public static function text($name, $value = null, $options = []): HtmlString
    {
        return self::input('text', $name, $value, $options);
    }

    public static function email($name, $value = null, $options = []): HtmlString
    {
        return self::input('email', $name, $value, $options);
    }

    public static function number($name, $value = null, $options = []): HtmlString
    {
        return self::input('number', $name, $value, $options);
    }

    public static function textarea($name, $value = null, $options = []): HtmlString
    {
        $id = $options['id'] ?? str_replace(['[', ']'], ['-', ''], $name);
        $options = self::htmlAttributes($options);
        $name = e($name);
        $value = e($value);

        return new HtmlString('<textarea id="' . e($id) . '" name="' . $name . '"' . $options . '>' . $value . '</textarea>');
    }

    public static function select($name, $list = [], $selected = null, $selectAttrs = []): HtmlString
    {
        $id = $selectAttrs['id'] ?? str_replace(['[', ']'], ['-', ''], $name);
        $selectAttrs = self::htmlAttributes($selectAttrs);
        $name = e($name);

        $options = '';
        foreach ($list as $value => $display) {
            $isSelected = ((string) $value === (string) $selected) ? ' selected' : '';
            $options .= '<option value="' . e($value) . '"' . $isSelected . '>' . e($display) . '</option>';
        }

        return new HtmlString('<select id="' . e($id) . '" name="' . $name . '"' . $selectAttrs . '>' . $options . '</select>');
    }

    public static function submit($value = null, $options = []): HtmlString
    {
        $options = self::htmlAttributes($options);
        $value = e($value);

        return new HtmlString('<button type="submit"' . $options . '>' . $value . '</button>');
    }

    protected static function input($type, $name, $value = null, $options = []): HtmlString
    {
        $id = $options['id'] ?? str_replace(['[', ']'], ['-', ''], $name);
        $options = self::htmlAttributes($options);
        $name = e($name);
        $value = e($value);

        return new HtmlString('<input type="' . $type . '" id="' . e($id) . '" name="' . $name . '" value="' . $value . '"' . $options . '>');
    }

    protected static function htmlAttributes(array $attributes): string
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
