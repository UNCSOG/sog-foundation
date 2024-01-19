<?php
class Cred_Generic_Field {
    
    public $_formHelper;
    public $shortcode_tags;

    public function __construct($_formHelper) {
        $this->_formHelper = $_formHelper;
    }  
    
    function filter_post_data( $data , $postarr ) {
        $content = sanitize_text_field($data['post_content']);
        
//        $content = $this->removeHtmlComments($content);       
//        $content = stripslashes($content);
//        
//        $this->add_shortcode( 'cred-generic-field', array(&$this, 'cred_generic_field_shortcodes') );
//        $this->add_shortcode( 'cred_generic_field', array(&$this, 'cred_generic_field_shortcodes') );
//        
//        $pattern = "\[(\[?)(cred\-generic\-field|cred_generic_field)\b([^\]\/]*(?:\/(?!\])[^\]\/]*)*?)(?:(\/)\]|\](?:([^\[]*+(?:\[(?!\/\2\])[^\[]*+)*+)\[\/\2\])?)(\]?)";
//        $res = preg_replace_callback( "/$pattern/s", array(&$this,'do_shortcode_tag'), $content );
        
        $content = stripslashes($content);        
        $res = preg_replace_callback('/\[cred\-generic\-field(.*?)\]/msi', array(&$this,'parse_quote'), $content);
        
        exit;
        return $data;
    }
    
    function parse_quote($matches) {
        $bbcode = '';
        preg_match_all('/(\w*?)=\'(.*?)\'/msi', $matches[1], $attr_matches);
        $attributes = array_combine($attr_matches[1], $attr_matches[2]);
        
        if(!empty($attributes))
        {
            $attribute_strings = array();
            foreach($attributes as $key => $value)
            {
                echo "<br>$key - $value";
                switch($key)
                {
                    case 'id':
                        $attribute_strings[] = '<a href="http://domain.com/forums/findpost/'.$value.'">Permalink</a>';
                    break;
                    case 'name':
                        if(isset($quote['attributes']['user_id']))
                        {
                            $attribute_strings[] = 'By <a href="http://domain.com/user/profile/'.$attributes['user_id'].'/'.$value.'">'.$value.'</a>';
                        }
                        else
                        {
                            $attribute_strings[] = 'By '.$value;
                        }
                    break;
                    case 'timestamp':
                        $attribute_strings[] = 'On '.date('d F Y - H:i A', $value);
                    break;
                }
            }


            {
                $citation = '<p class="citation">'.implode(' | ', $attribute_strings).'</p>'."\n";
            }
        }
        else
        {
            $citation = '';
        }

        return $citation.'<blockquote>';
    }
}

?>
