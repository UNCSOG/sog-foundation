<?php
if ( ! defined( 'ABSPATH' ) ) {
	die();
}
?>
<?php
$cron_job_key = PMXE_Plugin::getInstance()->getOption( 'cron_job_key' );
$urlToExport  = site_url() . '/wp-load.php?security_token=' . substr( md5( $cron_job_key . $update_previous->id ), 0, 16 ) . '&export_id=' . $update_previous->id . '&action=get_data';

if ( current_user_can( PMXE_Plugin::$capabilities ) ) {
	?>
    <div id="export_finished" class="export-finished">

        <h2>What's next?</h2>
        <div class="wpallexport-content-section-wrap rte-complete">
            <div class="wpallexport-content-section rte-complete">
                <div class="wpallexport-collapsed-content">
                    <div class="wpallexport-collapsed-content-inner">

                        <div>
                            <p class="wp-all-export-paragraph">
                                Right now this export will run each time a new record is created that matches your
                                configured filters. However, the data won't be sent anywhere or processed further until
                                you set up something to do so. An easy, code free option is to use our Zapier
                                integration to handle all of the after export processing. You simply connect WP All
                                Export with Zapier then use their app to configure what happens with the exported
                                records.
                            </p>
                            <p class="wpallexport-admin-link-padded rte-complete">
								<?php echo wp_all_export_generate_link( 'Learn more at Zapier', 'https://zapier.com/zapbook/wp-all-export-pro/' ); ?>
                            </p>
                        </div>

                        <div>
                            <p class="wp-all-export-paragraph">
                                It's also possible to create your own integration using custom PHP code and our API. Our
                                documentation is a good place to start and contains examples for common uses.
                            </p>
                            <p class="wpallexport-admin-link-padded rte-complete">
								<?php echo wp_all_export_generate_link( 'View our API documentation', 'https://www.wpallimport.com/documentation/action-reference/#pmxe_after_export' ); ?>
                            </p>
                        </div>

                        <p class="wp-all-export-paragraph">
                            WP All Export has generated a file with a single record for testing purposes which can be
                            downloaded below.
                        </p>

                        <p class="wpallexport-admin-link rte-complete">
							<?php echo wp_all_export_generate_link( 'Read more about real time exports', 'https://www.wpallimport.com/documentation/how-to-run-real-time-exports/' ); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <div class="input">
            <button class="button button-primary button-hero wpallexport-large-button download_data"
                    style="width: 220px;"
                    rel="<?php echo add_query_arg( array( 'page'     => 'pmxe-admin-manage',
			                                              'action'   => 'download',
			                                              'id'       => $update_previous->id,
			                                              '_wpnonce' => wp_create_nonce( '_wpnonce-download_feed' )
			        ), $this->baseUrl ); ?>">Download
                Test <?php echo strtoupper( wp_all_export_get_export_format( $update_previous->options ) ); ?></button>
			<?php if ( ! empty( $update_previous->options['split_large_exports'] ) ): ?>
                <button class="button button-primary button-hero wpallexport-large-button download_data"
                        rel="<?php echo add_query_arg( array( 'page'     => 'pmxe-admin-manage',
				                                              'id'       => $update_previous->id,
				                                              'action'   => 'split_bundle',
				                                              '_wpnonce' => wp_create_nonce( '_wpnonce-download_split_bundle' )
				        ), $this->baseUrl ); ?>"><?php printf( __( 'Split %ss', 'wp_all_export_plugin' ), strtoupper( wp_all_export_get_export_format( $update_previous->options ) ) ); ?></button>
			<?php endif; ?>

        </div>
        <hr/>
    </div>
	<?php
}
?>
