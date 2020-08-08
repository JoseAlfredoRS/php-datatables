<?php

/**
 * PHP library for HTML5 handler.
 *
 * @author    JoseAlfredoRS <alfredors.developer@gmail.com>
 * @copyright 2019 - 2020 (c) JoseAlfredoRS - PHP-HTML5
 * @license   https://opensource.org/licenses/MIT - The MIT License (MIT)
 * @link      https://github.com/JoseAlfredoRS/php-datatables
 * @since     1.0.0
 */

/**
 * HTML5 handler.
 *
 * @since 1.0.0
 */

class HTML5
{

    # @string,  Atributos de las etiquetas
    private $attribute;

    # @string,  Contenido de las etiquetas
    private $content;

    /**
     * Es lanzado al invocar un método inaccesible en un contexto estático. 
     * 
     * El parámetro $name corresponde al nombre del método al que se está llamando.
     * El parámetro $arguments es un array enumerado que contiene los parámetros que 
     * se han pasado al método $name
     *
     * @param  string   $name
     * @param  array    $arguments
     * @return void
     */
    public static function __callStatic($name, $arguments)
    {
        return (new self())->createElement($name, reset($arguments));
    }

    /**
     * Crea y retorna la etiqueta HTML
     *
     * @param  string   $tag
     * @param  array    $attributes
     * @return string
     */
    private function createElement($tag, $attributes)
    {

        if (!is_array($attributes)) {
            $this->content = $attributes;
            return "<$tag>" . $this->content . "</$tag>";
        }

        foreach ($attributes as $key => $value) {

            switch ($key) {
                case 'text':
                    $this->content .= $value;
                    break;

                case 'addText':
                    $this->content .= $value;
                    break;

                case 'addElement':
                    if (is_array($value))
                        $this->content .= implode(' ', $value);
                    else
                        $this->content .= $value;
                    break;

                default:
                    $this->attribute .= " {$key} = '{$value}' ";
                    break;
            }
        }

        if (in_array(strtolower($tag), ['input', 'img', 'hr', 'br']))
            return "<$tag " . $this->attribute . ">";
        else
            return "<$tag " . $this->attribute . ">" . $this->content . "</$tag>";
    }
}
