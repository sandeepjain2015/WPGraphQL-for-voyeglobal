<?php
/**
 * EsimCategoriesQuery class file.
 *
 * This file contains the definition of the `EsimCategoriesQuery` class which
 * registers a custom GraphQL query to fetch eSIM categories based on place.
 *
 * Use as:
 *
 * query FetchEsimCategories($place: String!) {
 *   esimCategories(place: $place) {
 *     name
 *     image
 *   }
 * }
 *
 * This GraphQL query retrieves a list of eSIM categories based on the specified place.
 *
 * @package VoyeglobalGraphql
 */

namespace VoyeglobalGraphql\Includes;

/**
 * Class EsimCategoriesQuery.
 *
 * Registers a custom GraphQL query to fetch eSIM categories based on place.
 */
class EsimCategoriesQuery {

	/**
	 * EsimCategoriesQuery constructor.
	 *
	 * Hooks into graphql_register_types to register the query.
	 */
	public function __construct() {
		add_action( 'graphql_register_types', array( $this, 'register_query' ) );
	}

	/**
	 * Registers the esimCategories GraphQL query.
	 *
	 * The query fetches eSIM categories based on the specified place.
	 */
	public function register_query() {
		register_graphql_field(
			'RootQuery',
			'esimCategories',
			array(
				'type'        => array( 'list_of' => 'EsimCategory' ),
				'description' => __( 'Fetch eSIM categories based on place', 'voye' ),
				'args'        => array(
					'place' => array(
						'type'        => 'String',
						'description' => __( 'Place identifier (local, regional, global)', 'voye' ),
					),
				),
				'resolve'     => array( $this, 'resolve_esim_categories' ),
			)
		);

		register_graphql_object_type(
			'EsimCategory',
			array(
				'description' => __( 'eSIM category type', 'voye' ),
				'fields'      => array(
					'name'  => array(
						'type'        => 'String',
						'description' => __( 'The name of the category', 'voye' ),
					),
					'image' => array(
						'type'        => 'String',
						'description' => __( 'The URL of the category image', 'voye' ),
					),
				),
			)
		);
	}

	/**
	 * Resolves the esimCategories query based on place.
	 *
	 * @param array $root The query root.
	 * @param array $args The query arguments.
	 * @return array The names of eSIM categories for the specified place.
	 */
	public function resolve_esim_categories( $root, $args ) {
		$place = isset( $args['place'] ) ? $args['place'] : 'local';
		$categories = array();

		$categories_terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => true,
				'fields'     => 'all',
				'meta_query' => array(
					array(
						'key'   => 'place',
						'value' => $place,
					),
				),
			)
		);

		if ( ! is_wp_error( $categories_terms ) ) {
			foreach ( $categories_terms as $term ) {
				$thumbnail_id = get_term_meta( $term->term_id, 'thumbnail_id', true );
				$image_url    = wp_get_attachment_url( $thumbnail_id );

				$categories[] = array(
					'name'  => $term->name,
					'image' => $image_url,
				);
			}
		}

		return $categories;
	}
}

new EsimCategoriesQuery();
