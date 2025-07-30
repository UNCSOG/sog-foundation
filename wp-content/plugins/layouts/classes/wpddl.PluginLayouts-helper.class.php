<?php
class WPDDL_Plugin_Layouts_Helper{

    private $process_results = null;
    private $dialogs_box;

    public function __construct(){
        add_action('wp_ajax_duplicate_layout', array(&$this, 'duplicate_layout_callback'));
        add_action( 'admin_print_scripts', array( &$this, 'init_display') );
        add_filter('ddl_duplicate_layouts', array(&$this, 'duplicate_layout'), 10, 3 );
        $this->dialogs_box = new WPDDL_DialogBoxesDuplicate( array( 'toolset_page_dd_layouts') );
    }

    function init_display(){

        $this->dialogs_box->init_screen_render();
    }

    public function duplicate_layout_callback()
    {

        // Clear any errors that may have been rendered that we don't have control of.
        if (ob_get_length()) {
            ob_clean();
        }
        if( user_can_create_layouts() === false ){
            die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
        }

        if ($_POST && wp_verify_nonce($_POST['layout-duplicate-layout-nonce'], 'layout-duplicate-layout-nonce')) {
            global $wpddlayout;

            $layout_id = $_POST['layout_id'];
            $toolset_assets = isset( $_POST['toolset_assets'] ) && $_POST['toolset_assets'] ? $_POST['toolset_assets'] : false;

            if( $toolset_assets ){
                $post_id = apply_filters('ddl_duplicate_layouts', 0, $layout_id, array('toolset_assets' => $toolset_assets ) );
            } else {
                $post_id = apply_filters('ddl_duplicate_layouts', 0, $layout_id );
            }

            if( $post_id ){

                $send = $wpddlayout->listing_page->get_send(isset($_GET['status']) && $_GET['status'] === 'trash' ? $_GET['status'] : 'publish', false, $post_id, array('layout_id'=>$post_id, 'toolset_assets' => $this->process_results), $_POST);

            } else {
                $send = wp_json_encode(array('error' => __(sprintf('Problem: apparently we cannot duplicate the selected layout: %s', $layout_id), 'ddl-layouts')));

            }

        } else {
            $send = wp_json_encode(array('error' => __(sprintf('Nonce problem: apparently we do not know where the request comes from. %s', __METHOD__), 'ddl-layouts')));
        }

        die($send);
    }

    public function duplicate_layout( $post_id, $id, $args = array() ){

        global $wpdb, $wpddlayout;

        $result = $wpdb->get_row($wpdb->prepare("SELECT ID, post_name FROM {$wpdb->posts} WHERE post_type=%s AND ID=%d AND post_status = 'publish'", WPDDL_LAYOUTS_POST_TYPE, $id ));

        if ($result) {

            $layout_json = WPDD_Layouts::get_layout_settings($result->ID);

            $layout_array = json_decode($layout_json, true);

            $layout_name_base = __('Copy of ', 'ddl-layouts') . str_replace('\\', '\\\\', $layout_array['name']);

            $layout_name = $layout_name_base;

            $count = 1;

            while ( $wpddlayout->does_layout_with_this_name_exist($layout_name) ) {
                $layout_name = $layout_name_base . ' - ' . $count;
                $count++;
            }

            $postarr = array(
                'post_title' => $layout_name,
                'post_content' => '',
                'post_status' => 'publish',
                'post_type' => WPDDL_LAYOUTS_POST_TYPE
            );
            $post_id = wp_insert_post($postarr);

            $post_slug = $wpdb->get_var($wpdb->prepare("SELECT post_name FROM {$wpdb->posts} WHERE post_type=%s AND ID=%d", WPDDL_LAYOUTS_POST_TYPE, $post_id));

            $layout_array['name'] = $layout_name;
            $layout_array['slug'] = $post_slug;

            if( isset( $args['toolset_assets'] ) && $args['toolset_assets'] ){
                $duplicator = new WPDDL_HandleAssetsDuplication( $layout_array, $args['toolset_assets' ]);
                $duplicator->process_assets();
                $layout_array = $duplicator->get_layout();
                $this->process_results = $duplicator->get_results();
            }

            WPDD_Layouts::save_layout_settings( $post_id, $layout_array );

            WPDD_Layouts::register_strings_for_translation( $post_id );
        }

        return $post_id;

    }
}

class WPDDL_HandleAssetsDuplication{
        private $layout;
        private $assets;
        private $results = array();

    public function __construct( $layout, $assets ){
        $this->results['duplicate'] = array();
        $this->results['remap'] = array();
        $this->layout = $layout;
        $this->assets = array_map( array(&$this, 'array_to_object' ), $assets );
    }

    function array_to_object( $asset ){
        return (object) $asset;
    }

    private function route( $asset ){
            $infos = self::type_to_classes();

            if( !isset($infos[$asset->type]) ){
                throw new Exception( __(sprintf("The resource type you're trying to duplicate does not exist: %s", $this->old_name->get_error_message() ), 'ddl-layouts') );
            }

            return $infos[$asset->type];
    }

    private static function type_to_classes(){
        return array(
            "cell-content-template" => "Toolset_ContentTemplate_Duplicator",
            "post-loop-views-cell" => "Toolset_Views_Duplicator",
            "views-content-grid-cell" => "Toolset_Views_Duplicator",
            "cred-cell" => "Toolset_CRED_Duplicator",
            "cred-user-cell" => "Toolset_CRED_User_Duplicator"
        );
    }

    function process_assets( ){
        foreach( $this->assets as $asset ){
            $asset = $asset;

            try{
                $class = $this->route( $asset );
            } catch( Exception $e){
                $this->results['duplicate'] = new WP_Error( 'Type error', $e->getMessage() );
                return $this->results;
            }

            $instance = new $class( $asset->id, $asset->type );

            try{
                $instance->duplicate();

                $this->remap_layout_cells( $asset, $instance );

                $this->results['duplicate'][] = (object) array(
                    'id' => $instance->get_new_id(),
                    'new_name' => $instance->get_new_name(),
                    'old_name' => $instance->get_old_name(),
                    'old_id' => $asset->id,
                    'type' => $asset->type
                );

            } catch( Exception $e ){

                $this->results['duplicate'][] = (object) array(
                    'id' => 0,
                    'new_name' => sprintf( __('Something went wrong %s', 'ddl-layouts'), $e->getMessage() ),
                    'old_name' => $e->getMessage(),
                    'old_id' => $asset->id,
                    'type' => $asset->type,
                 );

            }

        }

        return $this->results;
    }

    private function remap_layout_cells( $asset, $new ){

        $property = WPDD_Utils::get_property_from_cell_type( $asset->type, "property" );

        $args = array(
            'layout' => $this->layout,
            'cell_id' => $asset->cell['id'],
            'property' => $property,
            'cell_type' => $asset->type,
            'new_value' => $new->get_new_id(),
            'old_value' => $asset->id,
            'old_name' => $new->get_old_name(),
            'new_name' => $new->get_new_name()
        );
        $remap = new WPDDL_RemapLayouts(
            $args
        );

        $this->layout = $remap->process_layouts_properties( );
        $this->results['remap'][] = $remap->get_process_results();
    }

    function get_results( ){
        return $this->results;
    }

    function get_layout(){
        return $this->layout;
    }

}

interface ToolsetDuplicator{
    public function duplicate();
}

abstract class Toolset_Resource_Duplicator implements ToolsetDuplicator{

    protected $resource_id;
    protected $new_name = null;
    protected $new_id = null;
    protected $type;
    protected $old_name;

    public function __construct( $resource_id, $type ){
        $this->resource_id = (int) $resource_id;
        $this->type = $type;
        $this->build_new_temp_name();
    }

    public function duplicate(){}

    public function build_new_temp_name( ){
        $this->old_name = get_post_field( 'post_title', $this->resource_id );

        if( is_wp_error( $this->old_name ) ) {
            throw new Exception( __(sprintf("Something went wrong: %s", $this->old_name->get_error_message() ), 'ddl-layouts') );
        } else {
            $this->new_name = $this->old_name . ' copy';
        }
    }

    public function build_new_name( $id ){
        $name = get_post_field( 'post_title', $id );

        if( is_wp_error( $name ) ) {
            throw new Exception( __(sprintf("Something went wrong: %s", $name->get_error_message() ), 'ddl-layouts') );
        } else {
            $this->set_new_name( $name );
        }
    }

    public function set_new_name( $name ){
        $this->new_name = $name;
    }

    function get_new_name(){
        return $this->new_name;
    }

    function get_resource_id(){
        return $this->resource_id;
    }

    function get_old_name(){
        return $this->old_name;
    }

    function get_new_id(){
        return $this->new_id;
    }
}

class Toolset_CRED_Duplicator extends Toolset_Resource_Duplicator implements ToolsetDuplicator{

    public function duplicate(){

        if( class_exists('CRED_Loader') === false ){
            throw new Exception(__(sprintf("%s is not defined, is Toolset Forms plugin active?", 'CRED_Loader'), 'ddl-layouts'));
        } else{
            $forms_model = CRED_Loader::get('MODEL/Forms');
            $this->new_id = $forms_model->cloneForm( $this->get_resource_id(), $this->get_new_name() );
            $this->build_new_name( $this->new_id );
        }

        return $this->new_id;
    }
}


class Toolset_CRED_User_Duplicator extends Toolset_Resource_Duplicator implements ToolsetDuplicator{

    public function duplicate(){

        if( class_exists('CRED_Loader') === false ){
            throw new Exception(__(sprintf("%s is not defined, is Toolset Forms plugin active?", 'CRED_Loader'), 'ddl-layouts'));
        } else{
            $forms_model = CRED_Loader::get('MODEL/UserForms');
            $this->new_id = $forms_model->cloneForm( $this->get_resource_id(), $this->get_new_name() );
            $this->build_new_name( $this->new_id );
        }

        return $this->new_id;
    }
}


class Toolset_ContentTemplate_Duplicator extends Toolset_Resource_Duplicator implements ToolsetDuplicator{

    public function duplicate(){
        if( class_exists('WPV_Content_Template') === false ){

            throw new Exception(__(sprintf("%s is not defined, is Views Plugin active?", 'WPV_Content_Template'), 'ddl-layouts'));

        } else {
            $ct = WPV_Content_Template::get_instance( $this->get_resource_id() );

            if( null == $ct ) {
                throw new Exception( __(sprintf("The %s does not exists can you please recheck?", $this->get_old_name() ), 'ddl-layouts') );
            }
            if( method_exists($ct, 'duplicate') ){
                $result = $ct->duplicate( $this->get_new_name(), true );
            } elseif( method_exists($ct, 'clone_this') ){
                $result = $ct->clone_this( $this->get_new_name(), true );
            }


            if( null === $result ) {
                throw new Exception( __(sprintf("There was a problem duplicating %s Content Template.", $this->get_old_name() ), 'ddl-layouts') );

            } else {
                $this->new_id = $result->id;
                $this->build_new_name( $this->new_id );
            }
        }

        return $this->new_id;
    }
}

class Toolset_Views_Duplicator extends Toolset_Resource_Duplicator implements ToolsetDuplicator{

    public function duplicate(){
        if( class_exists('WPV_View_Base') === false ){

            throw new Exception(__(sprintf("%s is not defined, is Views Plugin active?", 'WPV_View_Base'), 'ddl-layouts'));

        } else {

            $new_post_title = $this->get_new_name();

            while( WPV_View_Base::is_name_used( $new_post_title ) ) {
                $new_post_title .= ' copy';
            }

            $view = WPV_View::get_instance( $this->get_resource_id() );

            if( null == $view ) {
                throw new Exception( __(sprintf("The %s does not exists can you please recheck?", $this->get_old_name() ), 'ddl-layouts') );
            }

            $this->new_id = $view->duplicate( $new_post_title );

            if( null == $this->new_id ) {
                throw new Exception( __(sprintf("There was a problem duplicating %s View.", $this->get_old_name() ), 'ddl-layouts') );

            } else {
                $this->build_new_name( $this->new_id );
            }
        }

        return $this->new_id;
    }
}

class Toolset_ViewsArchive_Duplicator extends Toolset_Resource_Duplicator implements ToolsetDuplicator{

    public function duplicate(){

        $this->new_id = apply_filters(
            'wpv_duplicate_wordpress_archive', 0, $this->get_resource_id(), $this->get_new_name(), false
        );

        if( 0 == (int) $this->new_id ) {
            throw new Exception( __(sprintf("There was a problem duplicating %s Archive View.", $this->get_old_name() ), 'ddl-layouts') );
        } else {
            $this->build_new_name( $this->new_id );
        }

        return $this->new_id;
    }
}

class WPDDL_DialogBoxesDuplicate extends Toolset_DialogBoxes{

        function __construct( $screens ){
            parent::__construct( $screens );
        }

        function template(){

                ob_start();?>

            <script type="text/html" id="ddl-duplicate-template">

                <div id="js-dialog-dialog-container ddl-dialog ddl-duplicate-template-wrap">
                    <div class="ddl-dialog-content ddl-dialog-content-main ddl-popup-tab" id="js-dialog-content-dialog">

                        <# if( ddl.cells ) { #>
                            <h4><?php _e('This layout uses the following Toolset resources:', 'ddl-layouts'); ?> </h4>
                            <table class="ddl-duplicate-selection-table">
                                <tr class="ddl-duplicate-selection-table-title-row">
                                    <th><?php _e('Resource', 'ddl-layouts'); ?></th>
                                    <th><?php _e('Resource type', 'ddl-layouts'); ?></th>
                                    <th><?php _e('Duplicate', 'ddl-layouts'); ?> <a class="ddl-duplicate-anchor js-duplicate-select-all" data-select=".js-duplicate-checkbox" data-deselect=".js-keep-checkbox"><?php _e('Select all', 'ddl-layouts'); ?></a></th>
                                    <th><?php _e('Keep original', 'ddl-layouts'); ?> <a class="ddl-duplicate-anchor js-keep-original-select-all" data-deselect=".js-duplicate-checkbox" data-select=".js-keep-checkbox"><?php _e('Select all', 'ddl-layouts'); ?></a></th>
                                </tr>
                                <#    _.each(ddl.cells, function(cell){
                                        var resource_id = cell.content[DDLayout.ListingMain.ToolsetResourcesHandler.cell_type_to_content_field(cell.cell_type)];
                                    #>
                                    <tr class="ddl-duplicate-selection-table-data-row">
                                        <td>{{{cell.name}}} </td>
                                        <td><# print( _.escape( DDLayout.ListingMain.ToolsetResourcesHandler.cell_type_to_resource_label(cell.cell_type) ) ); #></td>
                                        <td class="ddl-duplicate-selection-table-data-column-input-wrap"><input type="radio" name="{{{resource_id}}}" value="1" class="js-duplicate-checkbox js-ddl-duplicate-input" data-cell_type="{{{cell.cell_type}}}" data-other=".js-keep-checkbox" data-me=".js-duplicate-checkbox" checked="checked" /></td>
                                        <td class="ddl-duplicate-selection-table-data-column-input-wrap"><input type="radio" name="{{{resource_id}}}" value="0" class="js-keep-checkbox js-ddl-duplicate-input"  data-cell_type="{{{cell.cell_type}}}" data-other=".js-duplicate-checkbox"  data-me=".js-keep-checkbox" /></td>
                                    </tr>

                                    <#
                                        });
                                        #>
                            </table>
                            <#
                                }
                                #>
                                <div class="alert toolset-alert toolset-alert-info ddl-duplicate-template-alert">
                                    <?php _e("By default, all Toolset elements will be duplicated too. This way, when you edit the duplicated layout and its content, the original layout doesn't change. If you want to use the original Toolset elements for the duplicate layout, please change in the table above.", 'ddl-layouts'); ;?>
                                </div>
                    </div>

                </div>
            </script>


            <script type="text/html" id="ddl-duplicate-response-template">

                <div id="js-dialog-dialog-container ddl-dialog">
                    <div class="ddl-dialog-content ddl-dialog-content-main ddl-popup-tab" id="js-dialog-content-dialog">
                        <div class="ddl-duplicate-message-box">
                                <div class="alert toolset-alert toolset-alert-info ddl-duplicate-template-alert">{{{ddl.duplicate_message}}}</div>
                                <a class="ddl-duplicate-show-details js-ddl-duplicate-show-details">{{{ddl.show_details_anchor_text}}}</a>
                        </div>
                        <div class="ddl-duplicate-info-details js-ddl-duplicate-info-details">
                        <# if( ddl.duplicate ) { #>
                            <h4><?php _e('These resources has been duplicated:', 'ddl-layouts'); ?> </h4>
                            <table class="ddl-duplicate-selection-table">
                                <tr class="ddl-duplicate-selection-table-title-row">
                                    <th><?php _e('Resource type', 'ddl-layouts'); ?></th>
                                    <th><?php _e('Original name', 'ddl-layouts'); ?></th>
                                    <th><?php _e('Duplicate name', 'ddl-layouts'); ?></th>
                                    <th><?php _e('New resource id', 'ddl-layouts'); ?></th>
                                </tr>
                                <#    _.each(ddl.duplicate, function(cell){
                                    #>
                                    <tr class="ddl-duplicate-selection-table-data-row">
                                        <td class="ddl-duplicate-result-first"><# print( _.escape( DDLayout.ListingMain.ToolsetResourcesHandler.cell_type_to_resource_label(cell.type) ) ); #></td>
                                        <td class="ddl-duplicate-result-middle">{{{cell.old_name}}}</td>
                                        <td class="ddl-duplicate-result-middle">{{{cell.new_name}}}</td>
                                        <td class="ddl-duplicate-result-last">{{{cell.id}}}</td>
                                    </tr>

                                    <#
                                        });
                                        #>
                            </table>
                            <#
                                }
                                #>
                                </div>
                    </div>


                </div>
            </script>

                <?php
                echo ob_get_clean();

        }
}
