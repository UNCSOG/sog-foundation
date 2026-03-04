<?php
/**
 * The cap message as an object
 * 
 * @package RaveAlertNotifications
 * 
 */

namespace Utility_Bar;

Class Cap{
    /**
     * the generated identifier
     *
     * @var [string]
     */
    private $identifier;

    /**
     * an array of msg data
     *
     * @var [array]
     */
    private $info;

    /**
     * the type of message
     * examples are Alert, Cancel
     * camel case against my better sensibilities
     *
     * @var [string]
     */
    private $msgType;

    /**
     * the props that we use from the cap feeds
     *
     * @var array
     */
    private $props_array = array(
        'identifier',
        'sender',
        'sent',
        'status',
        'msgType',
        'scope',
        'info'
    );

    /**
     * the audience the alert is meant for 
     *
     * @var [type]
     */
    private $scope;

    /**
     * the cap channel the cap alert was sent from
     *
     * @var [type]
     */
    private $sender;

    /**
     * the time the alert was sent
     *
     * @var [type]
     */
    private $sent;

    /**
     * some arbitrary status set in getrave
     * examples are 
     *
     * @var [type]
     */
    private $status;

    /**
     * XML Namespaces provide a method to avoid element name conflicts.
     *
     * @var [string]
     */
    private $xmlns;

    /**
     * build the object
     *
     * @param [mixed] $cap_data or an array
     */
    function __construct( $cap_data = NULL ){
        
        if( NULL !== $cap_data ){
            $this->make( $cap_data );
        }

    }

    /**
     * inject the xml into the object
     *
     * @param [mixed] $cap_data or an array
     * @return void
     */
    public function make( $cap_data ){

        //name space means reading the xml doc
        $this->xmlns = self::get_xmlns_from_cap( $cap_data );

        if( is_array( $cap_data ) ){
            // it's already an array, probably came from the db
            $cap_array = $cap_data;
        } else {
            //get the cap as an array
            $cap_array = self::xml_string_to_array( $cap_data );
        }
    
        //build the object
        //these are the props we are using
        //loop through and add them to the object
        foreach( $this->get( 'props_array' ) as $prop ){
            if( isset( $cap_array[ $prop ] ) ){
                $this->set( $prop, $cap_array[ $prop ] );
            }
        }
    }

    /******************************** Getters ***********************************/

    /**
     * get a property from the object
     *
     * @param [string] $property
     * @return [mixed] $the value
     */
    public function get( $property ){

        $value = $this->{$property};
       
        if( $value ){
            /**
             * Filters the $value when a value is being got from the object
             *
             * @param   [mixed] $value the value of the property
             * @param   [string] $property
             * @param   [\Alert_Service\Cap] $this The Cap Object
             */

            return apply_filters( 'alert_service_get_property', $value, $property, $this );
        } else {
            return false;
        }
        
    }

    /**
     * gets a bit of info from the info array
     *
     * @param [string] $info_field
     * @return [mixed] $info_field_value or false if not found
     */
    public function get_info( $info_field ){
        $info_array = $this->get( 'info' );
        if( \is_array( $info_array ) && !empty( $info_array ) && isset( $info_array[$info_field] ) ){

            /**
             * Filters the info $value when a value is being got from the object
             *
             * @param   [mixed] $value the value of the property
             * @param   [string] $info_field the name of the info field
             * @param   [\Alert_Service\Cap] $this The Cap Object
             */

            return apply_filters( 'alert_service_get_info', $info_array[$info_field], $info_field, $this );
        } else {
            return false;
        }
    }

    /**
     * gets the actype on a cap message
     *
     * @return void
     */
    public function get_actype(){
        $info_array = $this->get( 'info' );
        
        if( $info_array && is_array( $info_array['parameter'] ) && !empty( $info_array['parameter'] ) ){
            if( $this->is_multi( $info_array['parameter'] ) ){
                foreach( $info_array['parameter'] as $key => $val ){
                    if( 'ACTYPE' == $val['valueName'] ){
                        return $val['value'];
                    }
                }
            } else {
                return $info_array['parameter']['value'];
            }
        }

        //if we make it here , return false
        return false;
    }

    /**
     * get a php array of the cap values
     *
     * @return array
     */
    public function get_cap_array(){
        $output_array = array( 'xmlns' => $this->xmlns );

        foreach( $this->get( 'props_array' ) as $prop ){
            if( isset( $this->{ $prop } ) ){
                $output_array[$prop] = $this->{$prop};
            }
        }

        return $output_array;
    }

    /**
     * build an xml cap message
     *
     * @return void
     */
    public function get_cap_xml(){

        $doc = new \DOMDocument('1.0', 'UTF-8' );
        $doc->formatOutput = true;
        $doc->xmlStandalone = 'no';
        //setup the alert wrapper
        $alert_node = $doc->createElementNS( $this->xmlns, 'alert');
       
        //add the props
        foreach( $this->get( 'props_array' ) as $prop ){
            $prop_value = $this->get( $prop );
            
            if( is_string( $prop_value ) && "" != $prop_value ){
                //if its a string nothing complicated has to happen
                $this_node = $doc->createElement( $prop, $prop_value );
                $alert_node->appendChild( $this_node );

            } elseif( is_array( $prop_value ) && !empty( $prop_value ) ) {
                
                //create a simplesml element 
                $sxe = new \SimpleXMLElement('<' . $prop . '/>');

                //if it's an array we have to walk through the array reursivly
                $this->to_xml( $sxe, $prop_value );

                //convert to DOMDocument and append
                $dom_sxe = dom_import_simplexml( $sxe );
                $dom_sxe = $doc->importNode( $dom_sxe, true);
                $alert_node->appendChild( $dom_sxe );
            }
        }

        $doc->appendChild( $alert_node );
        return $doc->saveXML();
    }

     /**
     * return the cap object as a json string
     *
     * @return void
     */
    public function get_json(){
        $array = $this->get_cap_array();
        return json_encode( $array );
    }

    /******************************** Setters ***********************************/

    public function set( $property, $value ){

         /**
         * Filters the $value when a value is being set in the object
         *
         * @param   [mixed] $value the value of the property
         * @param   [string] $the name of the property being used
         * @param   [\Alert_Service\Cap] $this The Cap Object
         */

        $value = apply_filters( 'alert_service_set_property', $value, $property, $this );

        $this->{$property} = $value;
    }

    /******************************** Utilities *********************************/

    /**
     * turn an xml string into a php array
     * todo, needs error checking
     *
     * @param [string] $xml_string
     * @return [array] $cap_array
     */
    private static function xml_string_to_array( $xml_string ){
        if( $xml_string !== 'empty'){
            $simple_xml = simplexml_load_string( $xml_string );
            $json = json_encode( $simple_xml );
            $array = json_decode( $json, true);
            return $array;
        }
    }


    /**
     * determin if some xml is a cap alert msg
     *
     * @param [xml string] $xml
     * @return boolean
     */
    public static function is_cap( $xml_string ){

        //bail early if xml is false
        if( !$xml_string ){
            return false;
        }

        if( 'urn:oasis:names:tc:emergency:cap:1.2' == self::get_xmlns_from_cap( $xml_string ) ){
            return true;
        }
        
        return false;

    }

    /**
     * determin the xmlns of a cap
     * takes a string or DOMDocument
     *
     * @param [DOMDocument or xml string] $cap
     * @return void
     */
    private static function get_xmlns_from_cap( $cap ){
        //var_export(  $cap );
        $doc = false;
        if( 'string' == gettype( $cap ) && $cap !== 'empty' ){
            $doc = new \DOMDocument();
            $doc->loadXML( $cap );
        } elseif ( 'object' == gettype( $cap ) && 'DOMDocument' == get_class( $cap ) ){
            $doc = $cap;
        } elseif( is_array( $cap ) ){
            if( isset( $cap['xmlns'] ) ){
                return $cap['xmlns'];
            }
        }
       
        if( $doc ){
            //error_log( var_export( $doc->documentElement->namespaceURI, true ) );
            return $doc->documentElement->namespaceURI;
        }
        
        return false;
    }

    /**
     * recursivly add an array to a simplexmlelement
     *
     * @param \SimpleXMLElement $object
     * @param array $data
     * @return void
     */
    private function to_xml( \SimpleXMLElement $object, array $data ){
        foreach ($data as $key => $value) {
            if ( is_array( $value ) ) {
                $new_object = $object->addChild( $key );
                $this->to_xml( $new_object, $value );
            } else {
                // if the key is an integer, it needs text with it to actually work.
                if ($key != 0 && $key == (int) $key) {
                    $key = "key_$key";
                }

                $object->addChild( $key, $value );
            }   
        }  
    }

    /**
     * checks if there is a multidimentional array
     *
     * @param [type] $array
     * @return boolean
     */
    private function is_multi($array) {
        return (count($array) != count($array, 1));
    }
}