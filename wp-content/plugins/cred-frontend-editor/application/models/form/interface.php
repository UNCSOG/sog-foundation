<?php

interface ICRED_Form_Base {

    public function print_form();

    public function validate_form($error_files);

    public function do_render_form($messages = "", $js_messages = "");

}
