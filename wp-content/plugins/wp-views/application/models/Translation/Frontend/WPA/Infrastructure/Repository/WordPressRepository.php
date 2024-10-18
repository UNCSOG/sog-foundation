<?php

namespace OTGS\Toolset\Views\Models\Translation\Frontend\WPA\Infrastructure\Repository;

// Domain Dependencies
use OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\TranslatableComponent\SearchContainer;
use OTGS\Toolset\Views\Models\Translation\Frontend\WPA\Domain\IRepository;
use OTGS\Toolset\Views\Models\Translation\Frontend\WPA\Domain\PostContentType;
use OTGS\Toolset\Views\Models\Translation\Frontend\WPA\Domain\WPA;
use OTGS\Toolset\Views\Models\Translation\Frontend\WPA\Domain\BlockContent;

// Common Depndencies
use OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\BlockContent as CommonBlockContent;
use OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\PostContent;
use OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\TranslatableStructure\OutputBeforeSearch;
use OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\TranslatableStructure\OutputWithoutSearch;
use OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\TranslatableStructure\SearchBeforeOutput;
use OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\Settings;

/**
 * Class WordPressRepository
 *
 * @package OTGS\Toolset\Views\Models\Translation\Frontend\WPA\Infrastructure\Repository
 * @codeCoverageIgnore No need to test how the domain is build. Very obvious if a mistake is being done here.
 *
 * @since TB 1.3
 */
class WordPressRepository implements IRepository {
	/** @var CommonBlockContent */
	private $common_block_content;

	/** @var WPA[] */
	public $WPAs = [];

	/**
	 * WordPressRepository constructor.
	 *
	 * @param CommonBlockContent $common
	 */
	public function __construct( CommonBlockContent $common ) {
		$this->common_block_content = $common;
	}

	/**
	 * @inheritDoc
	 */
	public function get_wpa_by_id( $id ) {
		$id = (int) $id;
		if( empty( $id ) ) {
			throw new \InvalidArgumentException( "$id must be an integer and greater than 0." );
		}

		if( array_key_exists( $id, $this->WPAs ) ) {
			// Already requested WPA - no need for another fetch.
			return $this->WPAs[ $id ];
		}

		/** @var \wpdb */
		global $wpdb;

		// Get WPA Helper.
		// There might be some function somewhere in the deeps of View doing exactly this.
		$wpa_helper_id_query = "
			SELECT ID
			FROM {$wpdb->prefix}posts
			WHERE post_parent = {$id}
			LIMIT 1
		";

		$wpa_helper_id = $wpdb->get_var( $wpa_helper_id_query );

		if( empty( $wpa_helper_id ) ) {
			throw new \InvalidArgumentException( "$id is not an id of a WPA." );
		}

		// Get translated WPA Helper.
		$wpa_helper_id_translated = apply_filters( 'wpml_object_id', $wpa_helper_id, 'wpa-helper' );

		if( empty( $wpa_helper_id_translated ) ) {
			throw new \RuntimeException( "No translation found for the WPA." );
		}

		$wpa_helper_translated = get_post( $wpa_helper_id_translated );

		// WPA Block Markup.
		$wpa_block_markup = new BlockContent( $wpa_helper_translated->post_content, $this->common_block_content );

		$wpa = new WPA( new PostContent( new PostContentType() ), $wpa_block_markup );
		$wpa->add_translatable_component(
			new SearchBeforeOutput(
				new OutputBeforeSearch(
					new OutputWithoutSearch()
				)
			)
		);

		return $this->WPAs[ $id ] = $wpa;
	}

	public function get_wpa_with_settings( $wpa_id, $wpa_settings ) {
		if( ! array_key_exists( $wpa_id, $this->WPAs ) ) {
			// Already requested WPA - no need for another fetch.
			throw new \LogicException( "$wpa_id could not be found.");
		}

		// Settings.
		$wpa_settings = new Settings( $wpa_settings );

		// Add settings to WPA.
		$this->WPAs[ $wpa_id ]->set_settings( $wpa_settings );
		$this->WPAs[ $wpa_id ]->add_translatable_component( new SearchContainer() );

		return $this->WPAs[ $wpa_id ];
	}
}
