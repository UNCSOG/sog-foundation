<?php

namespace Wpae\WordPress;

class ExportQuery {

	private static $instance = null;
	private $options;
	private $exported;

	private $id;

	private function __construct() {
		// Forces use of getInstance.
	}

	public static function getInstance() {
		if (self::$instance === null) {
			self::$instance = new ExportQuery();
		}
		return self::$instance;
	}

	public function generate($options, $id, $export_obj = false, $cron = false, $post_id = false, $exported = 0) {

		$this->options = $options;
		$this->exported = $exported;
		$this->id = $id;

		$wp_uploads = wp_upload_dir();

		$functions = $wp_uploads['basedir'] . DIRECTORY_SEPARATOR . WP_ALL_EXPORT_UPLOADS_BASE_DIRECTORY . DIRECTORY_SEPARATOR . 'functions.php';
		$functions = apply_filters( 'wp_all_export_functions_file_path', $functions );
		if ( @file_exists( $functions ) ) {
			\Wpae\Integrations\CodeBox::requireFunctionsFile();
		}

		\XmlExportEngine::$exportOptions        = $this->options;
		\XmlExportEngine::$is_user_export       = $this->options['is_user_export'];
		\XmlExportEngine::$is_comment_export    = $this->options['is_comment_export'];
		\XmlExportEngine::$is_woo_review_export = empty( $this->options['is_woo_review_export'] ) ? false : $this->options['is_woo_review_export'];
		\XmlExportEngine::$is_taxonomy_export   = empty( $this->options['is_taxonomy_export'] ) ? false : $this->options['is_taxonomy_export'];
		\XmlExportEngine::$exportID             = $this->id;
		\XmlExportEngine::$exportRecord         = $this;
		\XmlExportEngine::$post_types           = $this->options['cpt'];

		if ( isset( $this->options['is_woo_customer_export'] ) ) {
			\XmlExportEngine::$is_woo_customer_export = $this->options['is_woo_customer_export'];
		}

		if ( class_exists( 'SitePress' ) && ! empty( \XmlExportEngine::$exportOptions['wpml_lang'] ) ) {

			if ( ! defined( 'WP_ADMIN' ) ) {
				define( 'WP_ADMIN', true );
			}

			do_action( 'wpml_switch_language', \XmlExportEngine::$exportOptions['wpml_lang'] );
		}

		if ( empty( \XmlExportEngine::$exportOptions['export_variations'] ) ) {
			\XmlExportEngine::$exportOptions['export_variations'] = \XmlExportEngine::VARIABLE_PRODUCTS_EXPORT_PARENT_AND_VARIATION;
		}
		if ( empty( \XmlExportEngine::$exportOptions['export_variations_title'] ) ) {
			\XmlExportEngine::$exportOptions['export_variations_title'] = \XmlExportEngine::VARIATION_USE_PARENT_TITLE;
		}

		if ( empty( \XmlExportEngine::$exportOptions['xml_template_type'] ) ) {
			\XmlExportEngine::$exportOptions['xml_template_type'] = 'simple';
		}

		$filter_args = array(
			'filter_rules_hierarhy'   => $this->options['filter_rules_hierarhy'],
			'product_matching_mode'   => $this->options['product_matching_mode'],
			'taxonomy_to_export'      => empty( $this->options['taxonomy_to_export'] ) ? '' : $this->options['taxonomy_to_export'],
			'sub_post_type_to_export' => empty( $this->options['sub_post_type_to_export'] ) ? '' : $this->options['sub_post_type_to_export']
		);

		$filters = \Wpae\Pro\Filtering\FilteringFactory::getFilterEngine();
		$filters->init( $filter_args );

		if ( 'advanced' == $this->options['export_type'] ) {
// [ Update where clause]
			$filters->parse();

			\XmlExportEngine::$exportOptions['whereclause'] = $filters->get( 'queryWhere' );
			\XmlExportEngine::$exportOptions['joinclause']  = $filters->get( 'queryJoin' );

			if($export_obj) {
				$export_obj->set( array( 'options' => \XmlExportEngine::$exportOptions ) )->update();
			}else{
				\PMXE_Plugin::$session->set('whereclause', $filters->get('queryWhere'));
				\PMXE_Plugin::$session->set('joinclause',  $filters->get('queryJoin'));
				\PMXE_Plugin::$session->save_data();
			}
// [\ Update where clause]

			if ( \XmlExportEngine::$is_user_export ) {
				if ( ! \XmlExportEngine::get_addons_service()->isUserAddonActive() ) {
					throw new \Wpae\App\Service\Addons\AddonNotFoundException( 'The User Export Add-On Pro is required to run this export. If you already own it, you can download the add-on here: <a href="https://www.wpallimport.com/portal/downloads" target="_blank">https://www.wpallimport.com/portal/downloads</a>' );
				}

				add_action( 'pre_user_query', 'wp_all_export_pre_user_query', 10, 1 );
				$exportQuery = eval( 'return new WP_User_Query(array(' . $this->options['wp_query'] . ', \'offset\' => ' . $this->exported . ', \'number\' => ' . $this->options['records_per_iteration'] . '));' );
				remove_action( 'pre_user_query', 'wp_all_export_pre_user_query' );
			} elseif ( \XmlExportEngine::$is_comment_export || \XmlExportEngine::$is_woo_review_export ) {
				add_action( 'comments_clauses', 'wp_all_export_comments_clauses', 10, 1 );
				$exportQuery = eval( 'return new WP_Comment_Query(array(' . $this->options['wp_query'] . ', \'offset\' => ' . $this->exported . ', \'number\' => ' . $this->options['records_per_iteration'] . '));' );
				remove_action( 'comments_clauses', 'wp_all_export_comments_clauses' );
			} else {
				remove_all_actions( 'parse_query' );
				remove_all_filters( 'posts_clauses' );
				wp_all_export_remove_before_post_except_toolset_actions();

				add_filter( 'posts_where', 'wp_all_export_posts_where', 10, 1 );
				add_filter( 'posts_join', 'wp_all_export_posts_join', 10, 1 );
				$exportQuery = eval( 'return new WP_Query(array(' . $this->options['wp_query'] . ', \'offset\' => ' . $this->exported . ', \'posts_per_page\' => ' . $this->options['records_per_iteration'] . '));' );
				remove_filter( 'posts_join', 'wp_all_export_posts_join' );
				remove_filter( 'posts_where', 'wp_all_export_posts_where' );
			}
		} else {
// [ Update where clause]
			$filters->parse();

			\XmlExportEngine::$exportOptions['whereclause'] = $filters->get( 'queryWhere' );
			\XmlExportEngine::$exportOptions['joinclause']  = $filters->get( 'queryJoin' );

			if($export_obj) {
				$export_obj->set( array( 'options' => \XmlExportEngine::$exportOptions ) )->update();
			}else{
				\PMXE_Plugin::$session->set('whereclause', $filters->get('queryWhere'));
				\PMXE_Plugin::$session->set('joinclause',  $filters->get('queryJoin'));
				\PMXE_Plugin::$session->save_data();
			}
// [\ Update where clause]

			if ( in_array( 'users', $this->options['cpt'] ) or in_array( 'shop_customer', $this->options['cpt'] ) ) {
				add_action( 'pre_user_query', 'wp_all_export_pre_user_query', 10, 1 );

				if ( $post_id ) {
					$exportQuery = new \WP_User_Query( array(
						'search'         => $post_id,
						'search_columns' => [ 'ID' ],
						'orderby'        => 'ID',
						'order'          => 'ASC'
					) );
				} else {
					$exportQuery = new \WP_User_Query( array(
						'orderby' => 'ID',
						'order'   => 'ASC',
						'number'  => $this->options['records_per_iteration'],
						'offset'  => $this->exported
					) );
				}

				remove_action( 'pre_user_query', 'wp_all_export_pre_user_query' );
			} elseif ( in_array( 'comments', $this->options['cpt'] ) ) {
				add_action( 'comments_clauses', 'wp_all_export_comments_clauses', 10, 1 );
				global $wp_version;

				if ( version_compare( $wp_version, '4.2.0', '>=' ) ) {
					if ( $post_id ) {
						$exportQuery = new \WP_Comment_Query( array(
							'comment__in' => [ $post_id ],
							'orderby'     => 'comment_ID',
							'order'       => 'ASC'
						) );

					} else {
						$exportQuery = new \WP_Comment_Query( array(
							'orderby' => 'comment_ID',
							'order'   => 'ASC',
							'number'  => $this->options['records_per_iteration'],
							'offset'  => $this->exported
						) );
					}

				} else {
					if ( $post_id ) {
						$exportQuery = get_comments( array(
							'comment__in' => [ $post_id ],
							'orderby'     => 'comment_ID',
							'order'       => 'ASC'
						) );

					} else {
						$exportQuery = get_comments( array(
							'orderby' => 'comment_ID',
							'order'   => 'ASC',
							'number'  => $this->options['records_per_iteration'],
							'offset'  => $this->exported
						) );
					}
				}
				remove_action( 'comments_clauses', 'wp_all_export_comments_clauses' );
			} elseif ( in_array( 'shop_review', $this->options['cpt'] ) ) {
				add_action( 'comments_clauses', 'wp_all_export_comments_clauses', 10, 1 );

				global $wp_version;

				if ( version_compare( $wp_version, '4.2.0', '>=' ) ) {
					if ( $post_id ) {
						$exportQuery = new \WP_Comment_Query( array(
							'comment__in' => [ $post_id ],
							'orderby'     => 'comment_ID',
							'order'       => 'ASC'
						) );

					} else {
						$exportQuery = new \WP_Comment_Query( array(
							'post_type' => 'product',
							'orderby'   => 'comment_ID',
							'order'     => 'ASC',
							'number'    => $this->options['records_per_iteration'],
							'offset'    => $this->exported
						) );
					}
				} else {
					if ( $post_id ) {
						$exportQuery = get_comments( array(
							'comment__in' => [ $post_id ],
							'orderby'     => 'comment_ID',
							'order'       => 'ASC'
						) );

					} else {
						$exportQuery = get_comments( array(
							'post_type' => 'product',
							'orderby'   => 'comment_ID',
							'order'     => 'ASC',
							'number'    => $this->options['records_per_iteration'],
							'offset'    => $this->exported
						) );
					}
				}

				remove_action( 'comments_clauses', 'wp_all_export_comments_clauses' );
			} elseif ( in_array( 'taxonomies', $this->options['cpt'] ) ) {
				add_filter( 'terms_clauses', 'wp_all_export_terms_clauses', 10, 3 );
				$exportQuery = new \WP_Term_Query( array(
					'taxonomy'   => $this->options['taxonomy_to_export'],
					'orderby'    => 'term_id',
					'order'      => 'ASC',
					'number'     => $this->options['records_per_iteration'],
					'offset'     => $this->exported,
					'hide_empty' => false
				) );
				$postCount   = count( $exportQuery->get_terms() );
				remove_filter( 'terms_clauses', 'wp_all_export_terms_clauses' );
			} else {
				if ( strpos( $this->options['cpt'][0], 'custom_' ) === 0 ) {

					if ( isset( $post_id ) && $post_id ) {

						$filter_rules_hierarhy = json_decode( $filter_args['filter_rules_hierarhy'], true );
						if ( count( $filter_rules_hierarhy ) ) {
							$filter_rules_hierarhy[ count( $filter_rules_hierarhy ) - 1 ]['clause'] = 'AND';
						} else {
						}


						$filter_rules_hierarhy[]              = [
							"item_id"   => "12345",
							"left"      => 2,
							"right"     => 3,
							"parent_id" => null,
							"element"   => "id",
							"title"     => "ID",
							"condition" => "equals",
							"value"     => $post_id,
							"clause"    => null
						];
						$filter_args['filter_rules_hierarhy'] = json_encode( $filter_rules_hierarhy );

						$addon = \GF_Export_Add_On::get_instance();
						$addon->run();
						$exportQuery = $addon->add_on->get_query( $this->exported, 0, $filter_args );
					} else {
						$addon = \GF_Export_Add_On::get_instance();
						$addon->run();
						$exportQuery = $addon->add_on->get_query( $this->exported, $this->options['records_per_iteration'], $filter_args );
					}

					$totalQuery = $addon->add_on->get_query( 0, 0, $filter_args );
					$foundPosts = count( $totalQuery->results );
					$postCount  = count( $exportQuery->results );

				} else if ( in_array( 'shop_order', $this->options['cpt'] ) && \PMXE_Plugin::hposEnabled() ) {
					add_filter( 'posts_where', 'wp_all_export_numbering_where', 15, 1 );

					if ( \XmlExportEngine::get_addons_service()->isWooCommerceAddonActive() || \XmlExportEngine::get_addons_service()->isWooCommerceOrderAddonActive()) {
						$exportQuery = new \Wpae\WordPress\OrderQuery();

						$totalOrders = $exportQuery->getOrders();
						$foundOrders = $exportQuery->getOrders( $this->exported, $this->options['records_per_iteration'], $post_id );

						$foundPosts = count( $totalOrders );
						$postCount  = count( $foundOrders );


						remove_filter( 'posts_where', 'wp_all_export_numbering_where' );

					}
				} else {
					remove_all_actions( 'parse_query' );
					remove_all_filters( 'posts_clauses' );
					wp_all_export_remove_before_post_except_toolset_actions();

					add_filter( 'posts_where', 'wp_all_export_posts_where', 10, 1 );
					add_filter( 'posts_join', 'wp_all_export_posts_join', 10, 1 );

					if ( $post_id ) {

						if ( in_array( 'shop_order', $this->options['cpt'] ) ) {
							$post_status = array_keys( wc_get_order_statuses() );
						} else {
							$post_status = 'any';
						}

						$exportQuery = new \WP_Query( array(
							'p'                   => $post_id,
							'post_type'           => $this->options['cpt'],
							'post_status'         => $post_status,
							'orderby'             => 'ID',
							'order'               => 'ASC',
							'ignore_sticky_posts' => 1,
							'offset'              => $this->exported,
							'posts_per_page'      => $this->options['records_per_iteration']
						) );

					} else {
						$exportQuery = new \WP_Query( array(
							'post_type'           => $this->options['cpt'],
							'post_status'         => 'any',
							'orderby'             => 'ID',
							'order'               => 'ASC',
							'ignore_sticky_posts' => 1,
							'offset'              => $this->exported,
							'posts_per_page'      => $this->options['records_per_iteration']
						) );
					}

					remove_filter( 'posts_join', 'wp_all_export_posts_join' );
					remove_filter( 'posts_where', 'wp_all_export_posts_where' );
				}
			}
		}

		return $exportQuery;
	}
}
