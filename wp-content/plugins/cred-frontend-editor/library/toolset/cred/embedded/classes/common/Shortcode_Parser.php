<?php

/**
 * Shortcode Parser Class, uses same WP Code to parse shortcodes
 * parses internal shortcodes, 
 * can parse recursive (internal) shortcodes and shortcodes in attributes of other shortcodes as well
 * 
 * */
final class CRED_Shortcode_Parser {

    public $shortcode_tags = array();    // used to parse shortcodes internally, in same manner as WP    
    public $depth = 0;
    public $child_groups = array();

    public function __construct() {
        $this->shortcode_tags = array();
        $this->depth = 0;
        $this->child_groups = array();
    }

    // remove HTML comments from content, before parsing (allow to comment out parts)
    public function removeHtmlComments($content) {
        $content = preg_replace('/<!--(.*)-->/Uis', '', $content);
        return $content;
    }

    public function add_shortcode($tag, $func) {
        if (is_callable($func))
            $this->shortcode_tags[$tag] = $func;
        // chainable
        return $this;
    }

    public function remove_shortcode($tag) {
        unset($this->shortcode_tags[$tag]);
        // chainable
        return $this;
    }

    public function remove_all_shortcodes() {
        $this->shortcode_tags = array();
        $this->depth = 0;
        $this->child_groups = array();
        // chainable
        return $this;
    }

    public function do_shortcode_in_attributes($content) {
        $short_pattern = get_shortcode_regex();
        $patterns = array(
            "'" => "/\'$short_pattern\'/s",
            '"' => '/\"' . $short_pattern . '\"/s'
        );
        // parse shortcodes in quotes first
        foreach ($patterns as $q => $p) {
            while (preg_match($p, $content, $matches)) {
                if (!empty($matches[0]))
                    $content = str_replace($matches[0], $q . do_shortcode(substr($matches[0], 1, -1)) . $q, $content);
            }
        }
        return $content;
    }

    private function find_tag($tag, $content, &$matches) {

        $matches = array();
        $counts = 0;
        $pos = strpos($content, '[' . $tag);

        while ($pos !== false) {
            $start = $pos;

            $end = strpos($content, '[/' . $tag . ']', $start);

            $next = strpos($content, '[' . $tag, $start + 1);

            // Only add the tag if it doesn't contain another tag inside it.
            if (!$next || $next > $end) {
                $matches[] = substr($content, $start, $end - $start + strlen('[/' . $tag . ']'));
                $counts++;
            }

            $pos = $next;
        }

        return $counts;
    }

    // parse shortcodes internally (uses wp code found at shortcodes.php)
    public function do_recursive_shortcode($tag, $content) {
        $this->depth = 0;
        $tag = preg_quote($tag);

        // Call our own function for finding shortcode tags that don't contain the same tag.
        // This is needed because preg_match_all was failing with complete data.        
        $counts = $this->find_tag($tag, $content, $matches);

        while ($counts) {
            foreach ($matches as $match) {
                $shortcode = $this->do_shortcode($match);
                $content = str_replace($match, $shortcode, $content);
            }
            $counts = $this->find_tag($tag, $content, $matches);
            $this->depth++;
        }
        return $content;
    }

    public function do_shortcode($content) {
        if (empty($this->shortcode_tags) || !is_array($this->shortcode_tags))
            return $content;

        $pattern = $this->get_shortcode_regex();
        return preg_replace_callback("/$pattern/s", array(&$this, 'do_shortcode_tag'), $content);
    }

    public function shortcode_parse_atts($text) {
        $atts = array();
        $pattern = '/(\w+)\s*=\s*"([^"]*)"(?:\s|$)|(\w+)\s*=\s*\'([^\']*)\'(?:\s|$)|(\w+)\s*=\s*([^\s\'"]+)(?:\s|$)|"([^"]*)"(?:\s|$)|(\S+)(?:\s|$)/';
        $text = preg_replace("/[\x{00a0}\x{200b}]+/u", " ", $text);
        if (preg_match_all($pattern, $text, $match, PREG_SET_ORDER)) {
            foreach ($match as $m) {
                if (!empty($m[1])) {
                    //Fix https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/196964297/comments
                    //regex pattern cannot be stripslashed
                    if (strtolower($m[1]) == 'if' && strpos($m[2], "REGEX") !== FALSE)
                        $atts[strtolower($m[1])] = $m[2]; //stripcslashes($m[2]);
                    else
                        $atts[strtolower($m[1])] = stripcslashes($m[2]);
                } elseif (!empty($m[3]))
                    $atts[strtolower($m[3])] = stripcslashes($m[4]);
                elseif (!empty($m[5]))
                    $atts[strtolower($m[5])] = stripcslashes($m[6]);
                elseif (isset($m[7]) and strlen($m[7]))
                    $atts[] = stripcslashes($m[7]);
                elseif (isset($m[8]))
                    $atts[] = stripcslashes($m[8]);
            }
        } else {
            $atts = ltrim($text);
        }
        return $atts;
    }

    public function do_shortcode_tag($m) {
        // allow [[foo]] syntax for escaping a tag
        if ($m[1] == '[' && $m[6] == ']') {
            return substr($m[0], 1, -1);
        }

        $tag = $m[2];
        $attr = $this->shortcode_parse_atts($m[3]);

        if (isset($m[5])) {
            // enclosing tag - extra parameter
            return $m[1] . call_user_func($this->shortcode_tags[$tag], $attr, $m[5], $tag) . $m[6];
        } else {
            // self-closing tag
            return $m[1] . call_user_func($this->shortcode_tags[$tag], $attr, null, $tag) . $m[6];
        }
    }

    public function get_shortcode_regex() {
        $tagnames = array_keys($this->shortcode_tags);
        $tagregexp = join('|', array_map('preg_quote', $tagnames));

        // WARNING! Do not change this regex without changing do_shortcode_tag() and strip_shortcode_tag()
        return
                '\\['                              // Opening bracket
                . '(\\[?)'                           // 1: Optional second opening bracket for escaping shortcodes: [[tag]]
                . "($tagregexp)"                     // 2: Shortcode name
                . '\\b'                              // Word boundary
                . '('                                // 3: Unroll the loop: Inside the opening shortcode tag
                . '[^\\]\\/]*'                   // Not a closing bracket or forward slash
                . '(?:'
                . '\\/(?!\\])'               // A forward slash not followed by a closing bracket
                . '[^\\]\\/]*'               // Not a closing bracket or forward slash
                . ')*?'
                . ')'
                . '(?:'
                . '(\\/)'                        // 4: Self closing tag ...
                . '\\]'                          // ... and closing bracket
                . '|'
                . '\\]'                          // Closing bracket
                . '(?:'
                . '('                        // 5: Unroll the loop: Optionally, anything between the opening and closing shortcode tags
                . '[^\\[]*+'             // Not an opening bracket
                . '(?:'
                . '\\[(?!\\/\\2\\])' // An opening bracket not followed by the closing shortcode tag
                . '[^\\[]*+'         // Not an opening bracket
                . ')*+'
                . ')'
                . '\\[\\/\\2\\]'             // Closing shortcode tag
                . ')?'
                . ')'
                . '(\\]?)';                          // 6: Optional second closing brocket for escaping shortcodes: [[tag]]
    }

    /**
     * Parse shortcodes in the page content
     * @param string page content to be evaluated for internal shortcodes
     */
    public function parse_content_shortcodes($content) {
        $inner_expressions = $custom_inner_shortcodes = array();
        // cred_custom_inner_shortcodes filter
        $custom_inner_shortcodes = apply_filters('cred_custom_inner_shortcodes', $custom_inner_shortcodes);
        $custom_inner_shortcodes[] = 'wpml-string';
        // remove duplicates
        $custom_inner_shortcodes = array_unique($custom_inner_shortcodes);
        // add the custom inner shortcodes, whether they are self-closing or not
        if (sizeof($custom_inner_shortcodes) > 0) {
            foreach ($custom_inner_shortcodes as $custom_inner_shortcode) {
                $inner_expressions[] = "/\\[" . $custom_inner_shortcode . ".*?\\].*?\\[\\/" . $custom_inner_shortcode . "\\]/i";
            }
            $inner_expressions[] = "/\\[(" . implode('|', $custom_inner_shortcodes) . ").*?\\]/i";
        }
        // search for shortcodes
        $matches = array();
        $counts = $this->_find_outer_brackets($content, $matches);

        // iterate 0-level shortcode elements
        if ($counts > 0) {
            foreach ($matches as $match) {

                foreach ($inner_expressions as $inner_expression) {
                    $inner_counts = preg_match_all($inner_expression, $match, $inner_matches);

                    // replace all 1-level inner shortcode matches
                    if ($inner_counts > 0) {
                        foreach ($inner_matches[0] as &$inner_match) {
                            // execute shortcode content and replace
                            $replacement = do_shortcode($inner_match);
                            $resolved_match = $replacement;
                            $content = str_replace($inner_match, $resolved_match, $content);
                            $match = str_replace($inner_match, $resolved_match, $match);
                        }
                    }
                }
            }
        }

        return $this->do_shortcode($content);
    }

    private function _find_outer_brackets($content, &$matches) {
        $count = 0;

        $first = strpos($content, '[');
        if ($first !== FALSE) {
            $length = strlen($content);
            $brace_count = 0;
            $brace_start = -1;
            for ($i = $first; $i < $length; $i++) {
                if ($content[$i] == '[') {
                    if ($brace_count == 0) {
                        $brace_start = $i + 1;
                    }
                    $brace_count++;
                }
                if ($content[$i] == ']') {
                    if ($brace_count > 0) {
                        $brace_count--;
                        if ($brace_count == 0) {
                            $matches[] = substr($content, $brace_start, $i - $brace_start);
                            $count++;
                        }
                    }
                }
            }
        }

        return $count;
    }

}
