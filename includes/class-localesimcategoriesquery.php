<?php
/**
 * LocalEsimCategoriesQuery class file.
 *
 * This file contains the definition of the `LocalEsimCategoriesQuery` class which
 * registers a custom GraphQL query to fetch all local eSIM categories.
 *
 * Use as:
 *
 * query FetchLocalEsimCategories {
 *   localEsimCategories {
 *     name
 *   }
 * }
 *
 * This GraphQL query retrieves a list of local eSIM categories.
 *
 * @package VoyeglobalGraphql
 */

namespace VoyeglobalGraphql\Includes;

/**
 * Class LocalEsimCategoriesQuery.
 *
 * Registers a custom GraphQL query to fetch all local eSIM categories.
 */
class LocalEsimCategoriesQuery {

	/**
	 * LocalEsimCategoriesQuery constructor.
	 *
	 * Hooks into graphql_register_types to register the query.
	 */
	public function __construct() {
		add_action( 'graphql_register_types', array( $this, 'register_query' ) );
	}

	/**
	 * Registers the localEsimCategories GraphQL query.
	 *
	 * The query fetches all local eSIM categories.
	 */
	public function register_query() {
		register_graphql_field(
			'RootQuery',
			'localEsimCategories',
			array(
				'type'        => array( 'list_of' => 'String' ),
				'description' => 'Fetch all local eSIM categories',
				'resolve'     => array( $this, 'resolve_local_esim_categories' ),
			)
		);
	}

	/**
	 * Resolves the localEsimCategories query.
	 *
	 * @return array The names of all local eSIM categories.
	 */
	public function resolve_local_esim_categories() {
		$categories = array();

		$local_categories = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => true,
				'fields'     => 'names',
				'meta_query' => array(
					array(
						'key'   => 'place',
						'value' => 'local',
					),
				),
			)
		);

		if ( ! is_wp_error( $local_categories ) ) {
			$categories = $local_categories;
		}

		return $categories;
	}
}

new LocalEsimCategoriesQuery();
