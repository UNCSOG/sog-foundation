<?php
/**
 * This route is used to retreview Layouts info, for now only collage default settings
 *
 *
 */
namespace ToolsetCommonEs\Rest\Route;

class LayoutsInfo extends ARoute {

	protected $name = 'LayoutsInfo';

	protected $version = 1;

	public function get_method() {
		return 'GET';
	}

	public function callback( \WP_REST_Request $rest_request ) {
		return [
			'collage' => [
				'settings' => [
					'gallery' => [
						2 => [
							'desktop' => [
								[
									'x' => 1,
									'y' => 1,
									'width' => 6,
									'height' => 6,
								],
								[
									'x' => 7,
									'y' => 1,
									'width' => 6,
									'height' => 3,
								],
								[
									'x' => 7,
									'y' => 4,
									'width' => 3,
									'height' => 3,
								],
								[
									'x' => 10,
									'y' => 4,
									'width' => 3,
									'height' => 3,
								],
							],
							'tablet' => [
								[
									'x' => 1,
									'y' => 1,
									'width' => 8,
									'height' => 6,
								],
								[
									'x' => 9,
									'y' => 1,
									'width' => 4,
									'height' => 6,
								],
								],
							'phone' => [
								[
									'x' => 1,
									'y' => 1,
									'width' => 12,
									'height' => 6,
								],
							],
						],
						3 => [
							'desktop' => [
								[
									'x' => 1,
									'y' => 1,
									'width' => 8,
									'height' => 6,
								],
								[
									'x' => 9,
									'y' => 1,
									'width' => 4,
									'height' => 2,
								],
								[
									'x' => 9,
									'y' => 3,
									'width' => 4,
									'height' => 4,
								],
							],
							'tablet' => [
								[
									'x' => 1,
									'y' => 1,
									'width' => 8,
									'height' => 6,
								],
								[
									'x' => 9,
									'y' => 1,
									'width' => 4,
									'height' => 6,
								],
							],
							'phone' => [
								[
									'x' => 1,
									'y' => 1,
									'width' => 12,
									'height' => 6,
								],
							],
						],
						4 => [
							'desktop' => [
								[
									'x' => 1,
									'y' => 1,
									'width' => 8,
									'height' => 4,
								],
								[
									'x' => 9,
									'y' => 1,
									'width' => 4,
									'height' => 4,
								],
								[
									'x' => 1,
									'y' => 5,
									'width' => 4,
									'height' => 2,
								],
								[
									'x' => 5,
									'y' => 5,
									'width' => 8,
									'height' => 2,
								],
							],
							'tablet' => [
								[
									'x' => 1,
									'y' => 1,
									'width' => 8,
									'height' => 4,
								],
								[
									'x' => 9,
									'y' => 1,
									'width' => 4,
									'height' => 4,
								],
								[
									'x' => 1,
									'y' => 5,
									'width' => 12,
									'height' => 2,
								],
							],
							'phone' => [
								[
									'x' => 1,
									'y' => 1,
									'width' => 12,
									'height' => 6,
								],
							],
						],
						5 => [
							'desktop' => [
								[
									'x' => 1,
									'y' => 1,
									'width' => 4,
									'height' => 2,
								],
								[
									'x' => 5,
									'y' => 1,
									'width' => 8,
									'height' => 2,
								],
								[
									'x' => 1,
									'y' => 3,
									'width' => 8,
									'height' => 4,
								],
								[
									'x' => 9,
									'y' => 3,
									'width' => 4,
									'height' => 2,
								],
								[
									'x' => 9,
									'y' => 5,
									'width' => 4,
									'height' => 2,
								],
							],
							'tablet' => [
								[
									'x' => 1,
									'y' => 1,
									'width' => 12,
									'height' => 2,
								],
								[
									'x' => 1,
									'y' => 3,
									'width' => 6,
									'height' => 4,
								],
								[
									'x' => 7,
									'y' => 3,
									'width' => 6,
									'height' => 4,
								],
							],
							'phone' => [
								[
									'x' => 1,
									'y' => 1,
									'width' => 12,
									'height' => 2,
								],
								[
									'x' => 1,
									'y' => 3,
									'width' => 6,
									'height' => 4,
								],
								[
									'x' => 7,
									'y' => 3,
									'width' => 6,
									'height' => 4,
								],
							],
						],
						6 => [
							'desktop' => [
								[
									'x' => 1,
									'y' => 1,
									'width' => 4,
									'height' => 2,
								],
								[
									'x' => 5,
									'y' => 1,
									'width' => 8,
									'height' => 2,
								],
								[
									'x' => 1,
									'y' => 3,
									'width' => 8,
									'height' => 4,
								],
								[
									'x' => 9,
									'y' => 3,
									'width' => 4,
									'height' => 2,
								],
								[
									'x' => 9,
									'y' => 5,
									'width' => 4,
									'height' => 1,
								],
								[
									'x' => 9,
									'y' => 6,
									'width' => 4,
									'height' => 1,
								],
							],
							'tablet' => [
								[
									'x' => 1,
									'y' => 1,
									'width' => 12,
									'height' => 2,
								],
								[
									'x' => 1,
									'y' => 3,
									'width' => 6,
									'height' => 4,
								],
								[
									'x' => 7,
									'y' => 3,
									'width' => 6,
									'height' => 2,
								],
								[
									'x' => 7,
									'y' => 5,
									'width' => 6,
									'height' => 2,
								],
							],
							'phone' => [
								[
									'x' => 1,
									'y' => 1,
									'width' => 12,
									'height' => 2,
								],
								[
									'x' => 1,
									'y' => 3,
									'width' => 6,
									'height' => 4,
								],
								[
									'x' => 7,
									'y' => 3,
									'width' => 6,
									'height' => 4,
								],
							],
						],
						7 => [
							'desktop' => [
								[
									'x' => 1,
									'y' => 1,
									'width' => 4,
									'height' => 2,
								],
								[
									'x' => 5,
									'y' => 1,
									'width' => 8,
									'height' => 2,
								],
								[
									'x' => 1,
									'y' => 3,
									'width' => 8,
									'height' => 4,
								],
								[
									'x' => 9,
									'y' => 3,
									'width' => 2,
									'height' => 2,
								],
								[
									'x' => 11,
									'y' => 3,
									'width' => 2,
									'height' => 2,
								],
								[
									'x' => 9,
									'y' => 5,
									'width' => 4,
									'height' => 1,
								],
								[
									'x' => 9,
									'y' => 6,
									'width' => 4,
									'height' => 1,
								],
							],
							'tablet' => [
								[
									'x' => 1,
									'y' => 1,
									'width' => 12,
									'height' => 2,
								],
								[
									'x' => 1,
									'y' => 3,
									'width' => 6,
									'height' => 4,
								],
								[
									'x' => 7,
									'y' => 3,
									'width' => 6,
									'height' => 2,
								],
								[
									'x' => 7,
									'y' => 5,
									'width' => 6,
									'height' => 2,
								],
							],
							'phone' => [
								[
									'x' => 1,
									'y' => 1,
									'width' => 12,
									'height' => 2,
								],
								[
									'x' => 1,
									'y' => 3,
									'width' => 6,
									'height' => 4,
								],
								[
									'x' => 7,
									'y' => 3,
									'width' => 6,
									'height' => 4,
								],
							],
						],
						8 => [
							'desktop' => [
								[
									'x' => 1,
									'y' => 1,
									'width' => 4,
									'height' => 2,
								],
								[
									'x' => 5,
									'y' => 1,
									'width' => 8,
									'height' => 2,
								],
								[
									'x' => 1,
									'y' => 3,
									'width' => 8,
									'height' => 4,
								],
								[
									'x' => 9,
									'y' => 3,
									'width' => 2,
									'height' => 1,
								],
								[
									'x' => 11,
									'y' => 3,
									'width' => 2,
									'height' => 1,
								],
								[
									'x' => 9,
									'y' => 4,
									'width' => 2,
									'height' => 1,
								],
								[
									'x' => 11,
									'y' => 4,
									'width' => 2,
									'height' => 1,
								],
								[
									'x' => 9,
									'y' => 5,
									'width' => 4,
									'height' => 2,
								],
							],
							'tablet' => [
								[
									'x' => 1,
									'y' => 1,
									'width' => 12,
									'height' => 2,
								],
								[
									'x' => 1,
									'y' => 3,
									'width' => 6,
									'height' => 4,
								],
								[
									'x' => 7,
									'y' => 3,
									'width' => 6,
									'height' => 2,
								],
								[
									'x' => 7,
									'y' => 5,
									'width' => 6,
									'height' => 2,
								],
							],
							'phone' => [
								[
									'x' => 1,
									'y' => 1,
									'width' => 12,
									'height' => 2,
								],
								[
									'x' => 1,
									'y' => 3,
									'width' => 6,
									'height' => 4,
								],
								[
									'x' => 7,
									'y' => 3,
									'width' => 6,
									'height' => 4,
								],
							],
						],
						9 => [
							'desktop' => [
								[
									'x' => 1,
									'y' => 1,
									'width' => 4,
									'height' => 2,
								],
								[
									'x' => 5,
									'y' => 1,
									'width' => 8,
									'height' => 2,
								],
								[
									'x' => 1,
									'y' => 3,
									'width' => 8,
									'height' => 3,
								],
								[
									'x' => 9,
									'y' => 3,
									'width' => 4,
									'height' => 2,
								],
								[
									'x' => 9,
									'y' => 5,
									'width' => 2,
									'height' => 1,
								],
								[
									'x' => 11,
									'y' => 5,
									'width' => 2,
									'height' => 1,
								],
								[
									'x' => 1,
									'y' => 6,
									'width' => 5,
									'height' => 1,
								],
								[
									'x' => 6,
									'y' => 6,
									'width' => 5,
									'height' => 1,
								],
								[
									'x' => 11,
									'y' => 6,
									'width' => 2,
									'height' => 1,
								],
							],
							'tablet' => [
								[
									'x' => 1,
									'y' => 1,
									'width' => 12,
									'height' => 2,
								],
								[
									'x' => 1,
									'y' => 3,
									'width' => 6,
									'height' => 4,
								],
								[
									'x' => 7,
									'y' => 3,
									'width' => 6,
									'height' => 2,
								],
								[
									'x' => 7,
									'y' => 5,
									'width' => 6,
									'height' => 2,
								],
							],
							'phone' => [
								[
									'x' => 1,
									'y' => 1,
									'width' => 12,
									'height' => 2,
								],
								[
									'x' => 1,
									'y' => 3,
									'width' => 6,
									'height' => 4,
								],
								[
									'x' => 7,
									'y' => 3,
									'width' => 6,
									'height' => 4,
								],
							],
						],
						10 => [
							'desktop' => [
								[
									'x' => 1,
									'y' => 1,
									'width' => 4,
									'height' => 2,
								],
								[
									'x' => 5,
									'y' => 1,
									'width' => 8,
									'height' => 2,
								],
								[
									'x' => 1,
									'y' => 3,
									'width' => 8,
									'height' => 3,
								],
								[
									'x' => 9,
									'y' => 3,
									'width' => 4,
									'height' => 2,
								],
								[
									'x' => 9,
									'y' => 5,
									'width' => 2,
									'height' => 1,
								],
								[
									'x' => 11,
									'y' => 5,
									'width' => 2,
									'height' => 1,
								],
								[
									'x' => 1,
									'y' => 6,
									'width' => 5,
									'height' => 1,
								],
								[
									'x' => 6,
									'y' => 6,
									'width' => 3,
									'height' => 1,
								],
								[
									'x' => 9,
									'y' => 6,
									'width' => 2,
									'height' => 1,
								],
								[
									'x' => 11,
									'y' => 6,
									'width' => 2,
									'height' => 1,
								],
							],
							'tablet' => [
								[
									'x' => 1,
									'y' => 1,
									'width' => 12,
									'height' => 2,
								],
								[
									'x' => 1,
									'y' => 3,
									'width' => 6,
									'height' => 4,
								],
								[
									'x' => 7,
									'y' => 3,
									'width' => 6,
									'height' => 2,
								],
								[
									'x' => 7,
									'y' => 5,
									'width' => 6,
									'height' => 2,
								],
							],
							'phone' => [
								[
									'x' => 1,
									'y' => 1,
									'width' => 12,
									'height' => 2,
								],
								[
									'x' => 1,
									'y' => 3,
									'width' => 6,
									'height' => 4,
								],
								[
									'x' => 7,
									'y' => 3,
									'width' => 6,
									'height' => 4,
								],
							],
						],
					],
					'views' => [
						2 => [
							'desktop' => [
								[
									'x' => 1,
									'y' => 1,
									'width' => 6,
									'height' => 6,
								],
								[
									'x' => 7,
									'y' => 1,
									'width' => 6,
									'height' => 3,
								],
								[
									'x' => 7,
									'y' => 4,
									'width' => 3,
									'height' => 3,
								],
								[
									'x' => 10,
									'y' => 4,
									'width' => 3,
									'height' => 3,
								],
							],
							'tablet' => [
								[
									'x' => 1,
									'y' => 1,
									'width' => 8,
									'height' => 6,
								],
								[
									'x' => 9,
									'y' => 1,
									'width' => 4,
									'height' => 6,
								],
								],
							'phone' => [
								[
									'x' => 1,
									'y' => 1,
									'width' => 12,
									'height' => 6,
								],
							],
						],
						3 => [
							'desktop' => [
								[
									'x' => 1,
									'y' => 1,
									'width' => 8,
									'height' => 6,
								],
								[
									'x' => 9,
									'y' => 1,
									'width' => 4,
									'height' => 2,
								],
								[
									'x' => 9,
									'y' => 3,
									'width' => 4,
									'height' => 4,
								],
							],
							'tablet' => [
								[
									'x' => 1,
									'y' => 1,
									'width' => 8,
									'height' => 6,
								],
								[
									'x' => 9,
									'y' => 1,
									'width' => 4,
									'height' => 6,
								],
							],
							'phone' => [
								[
									'x' => 1,
									'y' => 1,
									'width' => 12,
									'height' => 6,
								],
							],
						],
						4 => [
							'desktop' => [
								[
									'x' => 1,
									'y' => 1,
									'width' => 8,
									'height' => 4,
								],
								[
									'x' => 9,
									'y' => 1,
									'width' => 4,
									'height' => 4,
								],
								[
									'x' => 1,
									'y' => 5,
									'width' => 4,
									'height' => 2,
								],
								[
									'x' => 5,
									'y' => 5,
									'width' => 8,
									'height' => 2,
								],
							],
							'tablet' => [
								[
									'x' => 1,
									'y' => 1,
									'width' => 8,
									'height' => 4,
								],
								[
									'x' => 9,
									'y' => 1,
									'width' => 4,
									'height' => 4,
								],
								[
									'x' => 1,
									'y' => 5,
									'width' => 12,
									'height' => 2,
								],
							],
							'phone' => [
								[
									'x' => 1,
									'y' => 1,
									'width' => 12,
									'height' => 6,
								],
							],
						],
						5 => [
							'desktop' => [
								[
									'x' => 1,
									'y' => 1,
									'width' => 4,
									'height' => 2,
								],
								[
									'x' => 5,
									'y' => 1,
									'width' => 8,
									'height' => 2,
								],
								[
									'x' => 1,
									'y' => 3,
									'width' => 8,
									'height' => 4,
								],
								[
									'x' => 9,
									'y' => 3,
									'width' => 4,
									'height' => 2,
								],
								[
									'x' => 9,
									'y' => 5,
									'width' => 4,
									'height' => 2,
								],
							],
							'tablet' => [
								[
									'x' => 1,
									'y' => 1,
									'width' => 12,
									'height' => 3,
								],
								[
									'x' => 1,
									'y' => 4,
									'width' => 6,
									'height' => 4,
								],
								[
									'x' => 7,
									'y' => 4,
									'width' => 6,
									'height' => 4,
								],
							],
							'phone' => [
								[
									'x' => 1,
									'y' => 1,
									'width' => 12,
									'height' => 4,
								],
								[
									'x' => 1,
									'y' => 5,
									'width' => 12,
									'height' => 2,
								],
							],
						],
						6 => [
							'desktop' => [
								[
									'x' => 1,
									'y' => 1,
									'width' => 4,
									'height' => 2,
								],
								[
									'x' => 5,
									'y' => 1,
									'width' => 8,
									'height' => 2,
								],
								[
									'x' => 1,
									'y' => 3,
									'width' => 8,
									'height' => 4,
								],
								[
									'x' => 9,
									'y' => 3,
									'width' => 4,
									'height' => 2,
								],
								[
									'x' => 9,
									'y' => 5,
									'width' => 4,
									'height' => 1,
								],
								[
									'x' => 9,
									'y' => 6,
									'width' => 4,
									'height' => 1,
								],
							],
							'tablet' => [
								[
									'x' => 1,
									'y' => 1,
									'width' => 12,
									'height' => 2,
								],
								[
									'x' => 1,
									'y' => 3,
									'width' => 6,
									'height' => 4,
								],
								[
									'x' => 7,
									'y' => 3,
									'width' => 6,
									'height' => 2,
								],
								[
									'x' => 7,
									'y' => 5,
									'width' => 6,
									'height' => 2,
								],
							],
							'phone' => [
								[
									'x' => 1,
									'y' => 1,
									'width' => 12,
									'height' => 4,
								],
								[
									'x' => 1,
									'y' => 5,
									'width' => 12,
									'height' => 2,
								],
							],
						],
						7 => [
							'desktop' => [
								[
									'x' => 1,
									'y' => 1,
									'width' => 4,
									'height' => 2,
								],
								[
									'x' => 5,
									'y' => 1,
									'width' => 8,
									'height' => 2,
								],
								[
									'x' => 1,
									'y' => 3,
									'width' => 8,
									'height' => 4,
								],
								[
									'x' => 9,
									'y' => 3,
									'width' => 2,
									'height' => 2,
								],
								[
									'x' => 11,
									'y' => 3,
									'width' => 2,
									'height' => 2,
								],
								[
									'x' => 9,
									'y' => 5,
									'width' => 4,
									'height' => 1,
								],
								[
									'x' => 9,
									'y' => 6,
									'width' => 4,
									'height' => 1,
								],
							],
							'tablet' => [
								[
									'x' => 1,
									'y' => 1,
									'width' => 12,
									'height' => 2,
								],
								[
									'x' => 1,
									'y' => 3,
									'width' => 6,
									'height' => 4,
								],
								[
									'x' => 7,
									'y' => 3,
									'width' => 6,
									'height' => 2,
								],
								[
									'x' => 7,
									'y' => 5,
									'width' => 6,
									'height' => 2,
								],
							],
							'phone' => [
								[
									'x' => 1,
									'y' => 1,
									'width' => 12,
									'height' => 4,
								],
								[
									'x' => 1,
									'y' => 5,
									'width' => 12,
									'height' => 2,
								],
							],
						],
						8 => [
							'desktop' => [
								[
									'x' => 1,
									'y' => 1,
									'width' => 4,
									'height' => 2,
								],
								[
									'x' => 5,
									'y' => 1,
									'width' => 8,
									'height' => 2,
								],
								[
									'x' => 1,
									'y' => 3,
									'width' => 8,
									'height' => 4,
								],
								[
									'x' => 9,
									'y' => 3,
									'width' => 2,
									'height' => 1,
								],
								[
									'x' => 11,
									'y' => 3,
									'width' => 2,
									'height' => 1,
								],
								[
									'x' => 9,
									'y' => 4,
									'width' => 2,
									'height' => 1,
								],
								[
									'x' => 11,
									'y' => 4,
									'width' => 2,
									'height' => 1,
								],
								[
									'x' => 9,
									'y' => 5,
									'width' => 4,
									'height' => 2,
								],
							],
							'tablet' => [
								[
									'x' => 1,
									'y' => 1,
									'width' => 12,
									'height' => 2,
								],
								[
									'x' => 1,
									'y' => 3,
									'width' => 6,
									'height' => 4,
								],
								[
									'x' => 7,
									'y' => 3,
									'width' => 6,
									'height' => 2,
								],
								[
									'x' => 7,
									'y' => 5,
									'width' => 6,
									'height' => 2,
								],
							],
							'phone' => [
								[
									'x' => 1,
									'y' => 1,
									'width' => 12,
									'height' => 4,
								],
								[
									'x' => 1,
									'y' => 5,
									'width' => 12,
									'height' => 2,
								],
							],
						],
						9 => [
							'desktop' => [
								[
									'x' => 1,
									'y' => 1,
									'width' => 4,
									'height' => 2,
								],
								[
									'x' => 5,
									'y' => 1,
									'width' => 8,
									'height' => 2,
								],
								[
									'x' => 1,
									'y' => 3,
									'width' => 8,
									'height' => 3,
								],
								[
									'x' => 9,
									'y' => 3,
									'width' => 4,
									'height' => 2,
								],
								[
									'x' => 9,
									'y' => 5,
									'width' => 2,
									'height' => 1,
								],
								[
									'x' => 11,
									'y' => 5,
									'width' => 2,
									'height' => 1,
								],
								[
									'x' => 1,
									'y' => 6,
									'width' => 5,
									'height' => 1,
								],
								[
									'x' => 6,
									'y' => 6,
									'width' => 5,
									'height' => 1,
								],
								[
									'x' => 11,
									'y' => 6,
									'width' => 2,
									'height' => 1,
								],
							],
							'tablet' => [
								[
									'x' => 1,
									'y' => 1,
									'width' => 12,
									'height' => 2,
								],
								[
									'x' => 1,
									'y' => 3,
									'width' => 6,
									'height' => 4,
								],
								[
									'x' => 7,
									'y' => 3,
									'width' => 6,
									'height' => 2,
								],
								[
									'x' => 7,
									'y' => 5,
									'width' => 6,
									'height' => 2,
								],
							],
							'phone' => [
								[
									'x' => 1,
									'y' => 1,
									'width' => 12,
									'height' => 4,
								],
								[
									'x' => 1,
									'y' => 5,
									'width' => 12,
									'height' => 2,
								],
							],
						],
						10 => [
							'desktop' => [
								[
									'x' => 1,
									'y' => 1,
									'width' => 4,
									'height' => 2,
								],
								[
									'x' => 5,
									'y' => 1,
									'width' => 8,
									'height' => 2,
								],
								[
									'x' => 1,
									'y' => 3,
									'width' => 8,
									'height' => 3,
								],
								[
									'x' => 9,
									'y' => 3,
									'width' => 4,
									'height' => 2,
								],
								[
									'x' => 9,
									'y' => 5,
									'width' => 2,
									'height' => 1,
								],
								[
									'x' => 11,
									'y' => 5,
									'width' => 2,
									'height' => 1,
								],
								[
									'x' => 1,
									'y' => 6,
									'width' => 5,
									'height' => 1,
								],
								[
									'x' => 6,
									'y' => 6,
									'width' => 3,
									'height' => 1,
								],
								[
									'x' => 9,
									'y' => 6,
									'width' => 2,
									'height' => 1,
								],
								[
									'x' => 11,
									'y' => 6,
									'width' => 2,
									'height' => 1,
								],
							],
							'tablet' => [
								[
									'x' => 1,
									'y' => 1,
									'width' => 12,
									'height' => 2,
								],
								[
									'x' => 1,
									'y' => 3,
									'width' => 6,
									'height' => 4,
								],
								[
									'x' => 7,
									'y' => 3,
									'width' => 6,
									'height' => 2,
								],
								[
									'x' => 7,
									'y' => 5,
									'width' => 6,
									'height' => 2,
								],
							],
							'phone' => [
								[
									'x' => 1,
									'y' => 1,
									'width' => 12,
									'height' => 4,
								],
								[
									'x' => 1,
									'y' => 5,
									'width' => 12,
									'height' => 2,
								],
							],
						],
					],
				],
			],
		];
	}

	public function permission_callback() {
		// @todo check for Toolset Access permissions
		return $this->wp_user->current_user_can( 'edit_posts' );
	}
}
