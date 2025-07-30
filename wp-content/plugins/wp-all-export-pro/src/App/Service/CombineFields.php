<?php

namespace Wpae\App\Service;


use Wpae\App\Service\SnippetParser;

class CombineFields {
	const DOUBLEQUOTES = "**DOUBLEQUOTES**";

	/** @var  SnippetParser */
	private $snippetParser;

	public function __construct() {
		$this->snippetParser = new SnippetParser();
	}

	/**
	 * @param $article
	 * @param $subjectString
	 * @param $searchKey
	 *
	 * @return array|false|mixed|string|string[]
	 */
	private static function parsePlaceholders( $article, $subjectString = false, $searchKey = false ) {

		if ( $searchKey !== false ) {
			if ( isset( $article[ $searchKey ] ) ) {
				return $article[ $searchKey ];
			}
		} else {
			foreach ( $article as $snippetName => $articleValue ) {
				// We use var_export and html_entity_decode to ensure the strings passed to contain the expected data.
				// For non-string values the data could be serialized so we need to unserialize it before passing it to the function.
				$subjectString = str_replace( "{" . $snippetName . "}", var_export( \maybe_unserialize(html_entity_decode( $articleValue )), 'true' ), $subjectString );

			}

			// For any as of yet unreplaced values we can check if they exist in the article.
			// If they exist then we swap in their reference in the $article for eval processing.
			$subjectString = preg_replace_callback(
				'/{([^}]*)}/',
				function($matches) use ($article) {
					$key = preg_replace('/\s\(.*\)/', '', $matches[1]);
					return isset($article[$key]) ? "\$article['$key']" : $matches[0];
				},
				$subjectString
			);

		}

		return $subjectString;
	}

	/**
	 * @param string $articles
	 * @param bool $processSingleArticle
	 * @param bool $singleFieldValue
	 *
	 * @return string
	 * @internal param $snippetParser
	 */
	public static function prepareMultipleFieldsValue( &$articles = '', $processSingleArticle = false, $singleFieldValue = false, $preview = false ) {
		$exportOptions = \XmlExportEngine::$exportOptions;
		$combineFields = new CombineFields();

		// Process all available articles.
		if ( $processSingleArticle === false ) {
			if ( isset( $exportOptions['cc_combine_multiple_fields'] ) && is_array( $exportOptions['cc_combine_multiple_fields'] ) ) {
				foreach ( $exportOptions['cc_combine_multiple_fields'] as $ID => $value ) {
					if ( $value ) {
						$label = $exportOptions['cc_name'][ $ID ];
						foreach ( $articles as $articleKey => $article ) {
							$multipleFieldsValue = $exportOptions['cc_combine_multiple_fields_value'][ $ID ];

							$multipleFieldsValue = self::parseFunctionsWithValues( $multipleFieldsValue, $combineFields, $article );

							if($preview) {
								$multipleFieldsValue = trim(preg_replace('~[\r\n]+~', ' ', htmlspecialchars($multipleFieldsValue)));
							}

							$articles[ $articleKey ][ $label ] = $multipleFieldsValue;

						}
					}
				}
			}
		} else {
			// Process only the single article requested.
			$multipleFieldsValue = self::parseFunctionsWithValues( $singleFieldValue, $combineFields, $articles );

			if($preview) {
				$multipleFieldsValue = trim(preg_replace('~[\r\n]+~', ' ', htmlspecialchars($multipleFieldsValue)));
			}

			return $multipleFieldsValue;
		}

		// Return something to indicate completion.
		return true;
	}

	/**
	 * @param $multipleFieldsValue
	 * @param $combineFields
	 * @param $article
	 *
	 * @return array|string|string[]
	 */
	private static function parseFunctionsWithValues( $multipleFieldsValue, $combineFields, $article ) {
		// The functions MUST be parsed before the placeholders are replaced with values to prevent RCE vulnerabilities.
		$multipleFieldsValue = html_entity_decode( $multipleFieldsValue );
		$functions           = $combineFields->snippetParser->parseFunctions( $multipleFieldsValue );

		foreach ( $functions as $functionKey => $function ) {
			if ( ! empty( $function ) ) {

				$originalFunction = $function;

				$function = self::parsePlaceholders( $article, $function );

				$function = preg_replace( '/\{(.*?)\}/i', "''", $function );

				try {
					$multipleFieldsValue = str_replace( '[' . $originalFunction . ']', eval( 'return ' . $function . ';' ), $multipleFieldsValue );
				} catch ( \Throwable $e ) {
					// If WP_DEBUG is true then log the errors. Otherwise just ignore them as it's probably just exported data related.
					if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
						$identifier = date( 'Y-m-d H:i:s' ) . ' WP All Export user provided function failure: ';
						error_log( $identifier . $e->getMessage() );
						error_log( $identifier . $e->getTraceAsString() );
						// Export the error to simplify locating the issue.
						$multipleFieldsValue = $identifier . $e->getMessage();
					}
				}
			}
		}

		foreach ( $article as $tmpArticleKey => $vl ) {
			$multipleFieldsValue = str_replace( '{' . $tmpArticleKey . '}', str_replace( self::DOUBLEQUOTES, "\"", $vl ), $multipleFieldsValue );
		}

		$snippets = $combineFields->snippetParser->parseSnippets( $multipleFieldsValue );

		// Replace empty snippets with empty string
		foreach ( $snippets as $snippet ) {
			$multipleFieldsValue = str_replace( '{' . $snippet . '}', '', $multipleFieldsValue );
		}

		return $multipleFieldsValue;
	}
}