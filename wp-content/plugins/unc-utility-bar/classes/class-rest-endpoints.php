<?php

/**
 * All the rest endpoints
 * try to make these short and have the relevant functions in the classes that pertain to the operation
 * 
 * @package Utility_Bar
 * 
 */

namespace Utility_Bar;

use Closure;
use \WP_REST_Server;
use \WP_REST_Response;
use \Utility_Bar\Alert_Banner_Storage as Storage;

if (!defined('ABSPATH')) exit;

class Rest_Endpoints {
    /**
     * the signleton instance
     *
     * @var [Rest_Endpoints]
     */
    private static $instance;

    /**
     * check nonces
     * will we check the nonce on rest request?
     * @var boolean
     */
    private $check_nonces = true;

    /**
     * the nonce
     *
     * @var [nonce]
     */
    private $nonce;

    /**
     * check application_passwords 
     * will we check the application_passwords on rest request?
     * set this when initializing the class with ->check_passwords( false );
     *
     * @var boolean
     */
    private $check_application_passwords = true;


    /**
     * build it
     */
    function __construct() {
        if (is_multisite()) {
            add_action('rest_api_init', array($this, 'rest_init'));
            add_filter('rest_pre_serve_request', array($this, 'maybe_xml_the_rest_response'), 10, 4);
        }
    }

    /**
     * rest_endpoints and the nonce
     * register rest routes
     * @return void
     */
    function rest_init() {

        $this->nonce = wp_create_nonce(Core::$nonce);
        //$this->check_nonces = false;


        //for posting a CAP message
        //requires an application account and password
        \register_rest_route(
            Core::$rest_prefix,
            '/cap/',
            $this->maybe_check_application_password(array(
                'permission_callback' => '__return_true',
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => array($this, 'cap_post_endpoint'),
                
            ))
        );

        //getting a cap message
        \register_rest_route(
            Core::$rest_prefix,
            '/cap/(?P<form>([json|xml])+)',
            array(
                'permission_callback' => '__return_true',
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'cap_get_endpoint'),
            )
        );

        //setting the published status
        \register_rest_route(
            Core::$rest_prefix,
            '/status/(?P<status>([published|unpublished])+)',
            $this->maybe_add_validate_nonce_arg(array(
                'permission_callback' => '__return_true',
                'methods' => WP_REST_Server::ALLMETHODS,
                'callback' => array($this, 'cap_status_set_endpoint'),
            ))
        );

        //getting the published status
        \register_rest_route(
            Core::$rest_prefix,
            '/status/',
            $this->maybe_check_application_password(array(
                'permission_callback' => '__return_true',
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'cap_status_get_endpoint'),
            ))
        );
    }

    /********************* endpoints *********************/

    /**
     * endpoint to post a cap message to
     *
     * @param [WP_REST_Request] $request
     * @return [WP_REST_Response] a json response with some info about what happened
     */
    function cap_post_endpoint($request) {
        // error_log('hello world');
        $body = $request->get_body();
        //error_log( 'cap_post_endpoint:getBody: ' . var_export( $body, true ) );
        //bail early if the body is empty
        if ('' == $body) {
            error_log('no body');
            return;
        }

        //bail early if the body of the POST is not a CAP message or XML
        if (!Cap::is_cap($body)) {
            error_log('not a cap');
            return new WP_REST_Response(array(
                "status" => "failure",
                "msg" => "the body is not a cap message"
            ));
        }

        //if its a cap turn it into a cap object
        $cap = new Cap($body);

        //log the cap json to the log file
        $log_array = array(
            'method' => $request->get_method(),
            'body' => $cap->get_json()
        );

        $log_string = json_encode($log_array);
        $log = new Log();
        $logged = $log->log_to_file($log_string);
        //error_log( var_export($cap->get_actype(),true));
        //cap storage
        if ('Normal' === $cap->get_actype()) {
            //its a normal, un-publish the current cap feed
            $unpublished = Storage::unpublish_stored_alert();
            //save the normal
            $stored = Storage::store_alert($cap);

            if (!$unpublished) {
                return new WP_REST_Response(array(
                    "status" => "failure",
                    "msg" => "failed to unpublish the message",
                    "publish-status" =>  Storage::get_published_status(),
                ));
            } else {
                return new WP_REST_Response(array(
                    "status" => "success",
                    "msg" => "the alert was unpublished",
                    "publish-status" =>  Storage::get_published_status(),
                    "cap" => $cap->get_cap_array()
                ));
            }

            //error_log( 'the normal sets the alert to unpublished' );

        } else {
            //publish the feed, replace what's there if there is something there that's published
            $stored = Storage::store_alert($cap);
            $published = Storage::publish_stored_alert();

            if (!$stored) {
                return new WP_REST_Response(array(
                    "status" => "failure",
                    "msg" => "failed to store the message",
                    "publish-status" =>  Storage::get_published_status(),
                ));
            }

            if (!$published) {
                return new WP_REST_Response(array(
                    "status" => "failure",
                    "msg" => "failed to publish the message",
                    "publish-status" =>  Storage::get_published_status(),
                ));
            }

            //error_log( 'the the alert has been published' );
        }

        //error_log( 'status:' . var_export( Storage::get_published_status(), true ) );
        //error_log( 'alert ' . var_export( Storage::get_stored_alert(), true ) );

        //if we've gotten this far respond with success
        return new WP_REST_Response(array(
            "status" => "success",
            "cap" => $cap->get_cap_array(),
            "msg" => "a new alert has been published",
            "publish-status" =>  Storage::get_published_status(),
        ));
    }

    /**
     * gets the cap data
     *
     * @param [WP_REST_Request] $request
     * @return [WP_REST_Response] a json response with a json version of the cap and the publish status
     */
    function cap_get_endpoint($request) {

        $form = $request->get_param('form');
        $cap_data = Storage::get_stored_alert();
        if ($cap_data) {
            //make a cap object
            $cap = new Cap($cap_data);
            //error_log( var_export( $cap->get_cap_array() , true  ) );
            if ($cap->get_cap_array()) {

                if ('json' === $form) {
                    //return a formal json response
                    return new WP_REST_Response(array(
                        "status" => "success",
                        "publish-status" =>  Storage::get_published_status(),
                        "cap" => $cap->get_cap_array()
                    ));
                } elseif ('xml'  === $form) {
                    //return the xml, 
                    //this will be run though maybe_xml_the_rest_response
                    return $cap->get_cap_xml();
                }
            } else {
                return new WP_REST_Response(array(
                    "status" => "failure",
                    "msg" => "unable to get the cap data"
                ));
            }
        }
    }

    /**
     * sets the publish status of the cap message
     *
     * @param [WP_REST_Request] $request
     * @return [WP_REST_Response] a json response with some info about what happened
     */
    function cap_status_set_endpoint($request) {
        $status = $request->get_param('status');
        $outcome = [];

        if ('unpublished' === $status) {
            $outcome['return'] = Storage::unpublish_stored_alert();
        } else if ('published' === $status) {
            $outcome['return'] = Storage::publish_stored_alert();
        }

        if ($outcome['return']) {
            $outcome['status'] = 'success';
            $outcome['msg'] = 'alert has been ' . $status;
            
        } else {
            $outcome['status'] = 'failure';
            $outcome['msg'] = 'alert has not been ' . $status;
        }

        return new WP_REST_Response($outcome);
    }

    /**
     * gets the publish status of the cap message
     *
     * @param [WP_REST_Request] $request
     * @return [WP_REST_Response] a json response with the publish status
     */
    function cap_status_get_endpoint($request) {
    }


    /********************* utilities *********************/
    /**
     * maybe return an xml feed 
     * depending on the endpoint callback name
     *
     * @param [type] $served
     * @param [type] $result
     * @param [type] $request
     * @param [type] $server
     * @return void
     */
    function maybe_xml_the_rest_response($served, $result, $request, $server) {
        //bail earliy if its a 400 error
        //error_log( var_export( $request, true ) );
        $data = $result->get_data();
        //error_log( var_export( $data, true ) );
        if( $data instanceof \Closure ){
            return $served;
        }

        if (is_array($data) && isset($data['data']['status']) && 400 == $data['data']['status']) {
            return $served;
        }

        if( !isset($request->get_attributes()['callback']) ){
            return $served;
        }

        if( isset($request->get_attributes()['callback']) && 'object' === gettype($request->get_attributes()['callback']) && 'Closure' == get_class($request->get_attributes()['callback']) ){
            return $served;
        }
        
        if (
            $result->get_data()
            && isset($request->get_attributes()['callback'][1])
            && 'cap_get_endpoint' == $request->get_attributes()['callback'][1]
            && 'xml' == $request->get_param('form')
        ) {
            // Send headers.
            $server->send_header('Content-Type', 'text/xml');
            $server->send_header('Cache-Control', 'no-cache, must-revalidate, max-age=0');

            // Echo the XML that's returned by the endpoint().
            echo trim($data);
            $served = true;
        }

        return $served;
    }

    /**
     * figure out if we are checking nonces
     *
     * @return [bool] if we are checking nonces
     */
    function are_we_checking_nonces() {
        if (true === $this->check_nonces) {
            //error_log( 'we are checking nonces' );
            return true;
        } elseif (false === $this->check_nonces) {
            //error_log( 'we are not checking nonces' );
            return false;
        }
    }

    /**
     * sets $check_nonces
     *
     * @param boolean $check_nonces
     * @return void
     */
    function check_nonces($check_nonces = true) {
        $this->check_nonces = $check_nonces;
    }

    /**
     * maybe_add_validate_nonce_arg
     * more for testing,  adds or removes the repeated arg to check the ?nonce=$nonce field
     * acts as a filter
     * controlled with public $check_nonces property of this class
     * @param [array] $other_args
     * @return [array] $other_args
     */
    function maybe_add_validate_nonce_arg($rest_array) {

        if ($this->are_we_checking_nonces()) {
            $rest_array['args'] = array(Core::$nonce . '-nonce' => array(
                'validate_callback' => function ($param, $request, $key) {
                    //error_log( var_export( $param, true ) );
                    return true;
                },
                'required' => true
            ));
        }

        return $rest_array;
    }

    /**
     * figure out if we are checking application passwords
     *
     * @return [bool] if we are checking application passwords
     */
    function are_we_checking_passwords() {
        if (true === $this->check_application_passwords) {
            //error_log( 'we are checking passwords' );
            return true;
        } elseif (false === $this->check_application_passwords) {
            //error_log( 'we are not checking passwords' );
            return false;
        }
    }

    /**
     * sets $check_application_passwords
     *
     * @param boolean $check_application_passwords
     * @return void
     */
    function check_passwords($check_application_passwords = true) {
        $this->check_application_passwords = $check_application_passwords;
    }

    /**
     * check against application passwords
     * the param login and auth need to be in the get request like: 
     *  ?login={username}&auth={password}
     * 
     * @param [array] $rest_array
     * @return [bool] $valid
     */
    function maybe_check_application_password($rest_array) {
        if ($this->are_we_checking_passwords()) {
            //error_log( var_export( $rest_array, true ) );
            $rest_array['args'] = array('auth' => array(
                'validate_callback' => function ($param, $request, $key) {

                    //check if using hashed AUTH_KEY
                    //auth early if this is the validator looking for the aggregate
                    $hash = $request->get_param('auth_key');
                    if ($hash && 'validator' == $param) {
                        $expected = crypt(AUTH_KEY, AUTH_SALT);
                        if (hash_equals($expected, $hash)) {
                            return true;
                        }
                    }

                    //we need a username
                    $login = $request->get_param('login');
                    if (!$login) {
                        return false;
                    }

                    //check if user exists
                    $user = get_user_by('login', $login);
                    if (!$user) {
                        return new \WP_Error(
                            'rest_error',
                            'Alert_Service_Rest_Endpoints::maybe_check_application_password() could not find the user ' . $login,
                            array(
                                'status' => 403
                            )
                        );
                    }

                    //check if user has access to that callback
                    $attributes = $request->get_attributes();
                    //error_log( \var_export( "Alert_Service-" . $attributes['callback'][1], true ) );
                    if (!\WP_Application_Passwords::application_name_exists_for_user($user->ID, "Alert_Service-" . $attributes['callback'][1])) {
                        return new \WP_Error(
                            'rest_error',
                            'Alert_Service_Rest_Endpoints::maybe_check_application_password() user ' . $login . ' does not have access to that endpoint',
                            array(
                                'status' => 403
                            )
                        );
                    }

                    //checks if password is correct
                    if (!is_wp_error(wp_authenticate_application_password(null, $login, $param))) {
                        return true;
                    } else {
                        return new \WP_Error(
                            'rest_error',
                            'Alert_Service_Rest_Endpoints::maybe_check_application_password() user ' . $login . ' could not validate password',
                            array(
                                'status' => 403
                            )
                        );
                    }
                },
                'required' => true
            ));
        }

        return $rest_array;
    }

    /**
     * you only need this once
     *
     * @return Rest_Endpoints
     */
    public static function getInstance(): Rest_Endpoints {
        static::$instance = static::$instance ?? new static();
        return static::$instance;
    }
}
