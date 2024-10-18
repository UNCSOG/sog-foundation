<?php

namespace OTGS_Views\KubAT\PhpSimple;

require 'lib' . \DIRECTORY_SEPARATOR . 'simple_html_dom.php';
class HtmlDomParser
{
    public static function file_get_html()
    {
        return \call_user_func_array('OTGS_Views\\simple_html_dom\\file_get_html', \func_get_args());
    }
    public static function str_get_html()
    {
        return \call_user_func_array('OTGS_Views\\simple_html_dom\\str_get_html', \func_get_args());
    }
}
