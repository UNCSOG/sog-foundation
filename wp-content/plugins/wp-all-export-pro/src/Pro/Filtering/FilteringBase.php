<?php

namespace Wpae\Pro\Filtering;

/**
 * Class FilteringBase
 * @package Wpae\Filtering
 */
abstract class FilteringBase implements FilteringInterface
{
    /**
     * @var \wpdb
     */
    public $wpdb;

    /**
     * @var array|bool|int|mixed|null
     */
    public $exportId;

    /**
     * @var string
     */
    protected $queryWhere = "";
    /**
     * @var array
     */
    protected $queryJoin = array();
    /**
     * @var string
     */
    protected $userWhere = "";
    /**
     * @var array
     */
    protected $userJoin = array();
    /**
     * @var
     */
    protected $options;
    /**
     * @var bool
     */
    protected $tax_query = false;
    /**
     * @var bool
     */
    protected $meta_query = false;
    /**
     * @var array
     */
    protected $filterRules = array();

    /**
     * FilteringBase constructor.
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->exportId = $this->getExportId();
        add_filter('wp_all_export_single_filter_rule', array(&$this, 'parse_rule_value'), 10, 1);
    }

    /**
     * @param array $args
     */
    public function init($args = array()){
        $this->options = $args;
        $this->filterRules = empty($this->options['filter_rules_hierarhy']) ? array() : json_decode($this->options['filter_rules_hierarhy']);
    }

    /**
     * @param $rule
     * @return mixed
     */
    abstract public function parse_single_rule($rule);

    /**
     *
     */
    abstract public function parse();

    abstract protected function getExcludeQueryWhere($postsToExclude);

    abstract protected function getModifiedQueryWhere($export);

    /**
     * @return bool
     */
    protected function isFilteringAllowed(){
        // do not apply filters for child exports
        if ( ! empty(\XmlExportEngine::$exportRecord->parent_id) ) {
            $this->queryWhere = \XmlExportEngine::$exportRecord->options['whereclause'];
            $this->queryJoin  = \XmlExportEngine::$exportRecord->options['joinclause'];
            return FALSE;
        }
        return TRUE;
    }

    /**
     * @return bool
     */
    protected function isExportNewStuff(){
        return ( ! empty(\XmlExportEngine::$exportOptions['export_only_new_stuff']) and ! empty($this->exportId) && ! \PMXE_Plugin::isNewExport());
    }

    /**
     * @return bool
     */
    protected function isExportModifiedStuff(){
        return ( ! empty(\XmlExportEngine::$exportOptions['export_only_modified_stuff']) and ! empty($this->exportId) && ! \PMXE_Plugin::isNewExport());
    }

    /**
     * @param $rule
     */
    protected function parse_date_field(&$rule ){

        if (strpos($rule->value, "+") !== 0
            && strpos($rule->value, "-") !== 0
            && strpos($rule->value, 'first') !== 0
            && strpos($rule->value, 'last') !== 0
            && strpos($rule->value, "next") === false
            && strpos($rule->value, "last") === false
            && (strpos($rule->value, "second") !== false || strpos($rule->value, "minute") !== false || strpos($rule->value, "hour") !== false || (strpos($rule->value, "day") !== false && strpos($rule->value, "today") === false && strpos($rule->value, "yesterday") === false) || strpos($rule->value, "week") !== false || strpos($rule->value, "month") !== false || strpos($rule->value, "year") !== false))
        {
            $rule->value = "-" . trim(str_replace("ago", "", $rule->value));
        }

        if ( strpos($rule->value, ":") !== false ) {

            $rule->value = date("Y-m-d H:i:s", strtotime($rule->value));

        } else {

            if ( in_array($rule->condition, array('greater')) ) {
                if ( (strpos($rule->value, "-") !== 0 && strpos($rule->value, "-") !== false) || strpos($rule->value, "/") !== false ) {
                    $rule->value = date("Y-m-d", strtotime('+1 day', strtotime($rule->value)));
                } else {
                    $rule->value = date("Y-m-d H:i:s", strtotime($rule->value));
                }

            } else if( strpos($rule->value, 'day') !== false && in_array($rule->condition, array('equals_or_less'))) {
                $rule->value = date("Y-m-d", strtotime('+1 day', strtotime($rule->value)));
                $rule->condition = 'less';
            } else if (preg_match("/(\d{1,2})\/(\d{2})\/(\d{4})$/", $rule->value,$matches) && in_array($rule->condition, array('equals_or_less'))) {
                $rule->value = date("Y-m-d", strtotime('+1 day', strtotime($rule->value)));
                $rule->condition = 'less';
            }
            else {
                $rule->value = date("Y-m-d", strtotime($rule->value));
            }

        }

    }

    /**
     * @param $parent_rule
     * @param $callback
     */
    protected function recursion_parse_query($parent_rule){
        $filter_rules_hierarchy = json_decode($this->options['filter_rules_hierarhy']);
        $sub_rules = array();
        foreach ($filter_rules_hierarchy as $j => $rule) if ($rule->parent_id == $parent_rule->item_id and $rule->item_id != $parent_rule->item_id) { $sub_rules[] = $rule; }
        if ( ! empty($sub_rules) ){
            $this->queryWhere .= "(";
            foreach ($sub_rules as $rule){
                $this->parse_single_rule($rule);
            }
            $this->queryWhere .= ")";
        }
    }

    /**
     * @param $rule
     * @param bool $is_int
     * @param bool $table_alias
     * @return string
     */
    protected function parse_condition($rule, $is_int = false, $table_alias = false){

        $value = $rule->value;
        $q = "";
        switch ($rule->condition) {
            case 'equals':
                if ( in_array($rule->element, array('post_date', 'comment_date', 'user_registered', 'user_role')) )
                {
                    $q = "LIKE '%". $value ."%'";
                }
                else
                {
                    $q = "= " . (($is_int or is_numeric($value)) ? $value : "'" . addslashes($value) . "'");
                }
                break;
            case 'not_equals':
                if ( in_array($rule->element, array('post_date', 'comment_date', 'user_registered', 'user_role')) )
                {
                    $q = "NOT LIKE '%". $value ."%'";
                }
                else
                {
                    $q = "!= " . (($is_int or is_numeric($value)) ? $value : "'" . $value . "'");
                }
                break;
            case 'greater':
                $q = "> " . (($is_int or is_numeric($value)) ? $value : "'" . $value . "'");
                break;
            case 'equals_or_greater':
                $q = ">= " . (($is_int or is_numeric($value)) ? $value : "'" . $value . "'");
                break;
            case 'less':
                $q = "< " . (($is_int or is_numeric($value)) ? $value : "'" . $value . "'");
                break;
            case 'equals_or_less':
                $q = "<= " . (($is_int or is_numeric($value)) ? $value : "'" . $value . "'");
                break;
            case 'contains':
                $q = "LIKE '%". addslashes($value) ."%'";
                break;
            case 'not_contains':
                $q = "NOT LIKE '%". addslashes($value) ."%'";
                break;
            case 'is_empty':
                $q = " = ''";
                if ($table_alias) $q .= " OR $table_alias.meta_value IS NULL";
                break;
            case 'is_not_empty':
                $q = "IS NOT NULL ";
                if ($table_alias) $q .= " AND $table_alias.meta_value <> '' ";
                break;
	        case 'is_in_list':
		        $values = array_map('trim', explode(',', $value));
		        $values = array_map('esc_sql', $values);
		        $q = "IN ('" . implode("','", $values) . "')";
		        break;
	        case 'is_not_in_list':
		        $values = array_map('trim', explode(',', $value));
		        $values = array_map('esc_sql', $values);
		        $q = "NOT IN ('" . implode("','", $values) . "')";
		        break;
            default:
                # code...
                break;

        }

        if ( ! empty($rule->clause) ) $q .= " " . $rule->clause . " ";

        return $q;

    }

    /**
     * @param $rule
     * @return mixed
     */
    public function parse_rule_value($rule )
    {
        if ( preg_match("%^\[.*\]$%", $rule->value) )
        {
            $function = trim(trim($rule->value, "]"), "[");

            preg_match("/^(.+?)\((.*?)\)$/", $function, $match);

            if ( ! empty($match[1]) and function_exists($match[1]) )
            {
                // parse function arguments
                if ( ! empty($match[2]) )
                {
                    $arguments = array_map('trim', explode(',', $match[2]));

                    $rule->value = call_user_func_array($match[1], $arguments);
                }
                else
                {
                    $rule->value = call_user_func($match[1]);
                }
            }
        }

        return $rule;
    }

    /**
     * @return array|bool|int|mixed|null
     */
    public function getExportId(){
        $input  = new \PMXE_Input();
		// Don't use the GET value if it's a real time export as it is probably wrong.
	    if( ! (isset(\XmlExportEngine::$exportOptions['do_not_generate_file_on_new_records']) &&  \XmlExportEngine::$exportOptions['do_not_generate_file_on_new_records']) ){
		    $export_id = $input->get('id', 0);
	    }

        if (empty($export_id))
        {
            $export_id = $input->get('export_id', 0);
            if (empty($export_id)){
                $export_id = ( ! empty(\PMXE_Plugin::$session->update_previous)) ? \PMXE_Plugin::$session->update_previous : 0;
            }
            if (empty($export_id) and ! empty(\XmlExportEngine::$exportID)){
                $export_id = \XmlExportEngine::$exportID;
            }
        }
        return $export_id;
    }

    public function checkNewStuff(){

        $export = new \PMXE_Export_Record();
        $export->getById($this->exportId);

	    if(!empty($export)) {

            //If re-run, this export will only include records that have not been previously exported.
            if ($this->isExportNewStuff()) {

                if($export->iteration > 0) {
                    global $wpdb;
                    $postList = new \PMXE_Post_List();

                    $postsToExcludeSql = 'SELECT post_id FROM ' . $postList->getTable() . ' WHERE export_id = %d AND iteration < %d';
                    $postsToExcludeSql = $wpdb->prepare($postsToExcludeSql, $this->exportId, $export->iteration);

                    $this->queryWhere .= $this->getExcludeQueryWhere($postsToExcludeSql);
                }
            }

            if ($this->isExportModifiedStuff() && !empty($export->registered_on)) {
                $export = new \PMXE_Export_Record();
                $export->getById($this->exportId);

                $this->getModifiedQueryWhere($export);
            }
        }
    }

    /**
     * __get function.
     *
     * @access public
     * @param mixed $key
     * @return mixed
     */
    public function __get( $key ) {
        return $this->get( $key );
    }

    /**
     * Get a session variable
     *
     * @param string $key
     * @param  mixed $default used if the session variable isn't set
     * @return mixed value of session variable
     */
    public function get( $key, $default = null ) {

		switch( $key ){
			case 'queryWhere':
				if( ! empty($this->queryWhere) && apply_filters('pmxe_clean_query_where', true)) {
					$this->queryWhere = $this->cleanWhere( $this->queryWhere );
					$this->queryWhere = $this->insertMissingAndClauses( $this->queryWhere );
					return $this->queryWhere;
				}
				break;
		}

        return isset( $this->{$key} ) ? $this->{$key} : $default;
    }

	public function cleanWhere($query) {
		// Pattern to match AND/OR followed by a closing parenthesis with optional spaces.
		$pattern = '/\s*(AND|OR)\s*(?=\))/i';

		return preg_replace($pattern, '', $query);
	}

	public function insertMissingAndClauses($query) {
		// Pattern for ensuring there is an AND between certain fragments.
		$pattern = '/\(\s*([^()]+?)\s*\)(\s*\()/';
		$replacement = '($1) AND $2';

		while (preg_match($pattern, $query)) {
			$query = preg_replace($pattern, $replacement, $query);
		}

		return $query;
	}
}
