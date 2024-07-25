<?php
/**
 * EsimProductsQuery class file.
 *
 * Use as:
 *
 * query FetchEsimProducts($category: String!, $currency: String, $place: String) {
 *   esimProducts(category: $category, currency: $currency, place: $place) {
 *     id
 *     data
 *     valid_for
 *     price
 *     coverage
 *   }
 * }
 *
 * @package VoyeglobalGraphql
 */

namespace VoyeglobalGraphql\Includes;

/**
 * Class EsimProductsQuery
 *
 * Registers a custom GraphQL query to fetch products by eSIM category.
 */
class EsimProductsQuery {

	/**
	 * EsimProductsQuery constructor.
	 *
	 * Hooks into graphql_register_types to register the query.
	 */
	public function __construct() {
		add_action( 'graphql_register_types', array( $this, 'register_query' ) );
	}

	/**
	 * Registers the esimProducts GraphQL query.
	 *
	 * The query fetches products by an eSIM category based on the type (local, global, regional).
	 */
	public function register_query() {
		register_graphql_field(
			'RootQuery',
			'esimProducts',
			array(
				'type'        => array( 'list_of' => 'EsimProduct' ),
				'description' => __( 'Fetch products by eSIM category', 'voye' ),
				'args'        => array(
					'type'     => array(
						'type'        => 'String',
						'description' => __( 'Type of eSIM category (local, global, regional)', 'voye' ),
					),
					'category' => array(
						'type'        => 'String',
						'description' => __( 'The name of the eSIM category', 'voye' ),
					),
					'currency' => array(
						'type'        => 'String',
						'description' => __( 'The currency to display the product prices in, defaults to USD', 'voye' ),
					),
					'place'    => array(
						'type'        => 'String',
						'description' => __( 'The place to fetch coverage info for', 'voye' ),
					),
				),
				'resolve'     => array( $this, 'resolve_esim_products' ),
			)
		);

		register_graphql_object_type(
			'EsimProduct',
			array(
				'description' => __( 'A product in the eSIM category', 'voye' ),
				'fields'      => array(
					'id'        => array(
						'type'        => 'Integer',
						'description' => __( 'The id of the product', 'voye' ),
					),
					'data'      => array(
						'type'        => 'String',
						'description' => __( 'The data allowance of the product', 'voye' ),
					),
					'valid_for' => array(
						'type'        => 'String',
						'description' => __( 'The validity period of the product', 'voye' ),
					),
					'price'     => array(
						'type'        => 'String',
						'description' => __( 'The price of the product', 'voye' ),
					),
					'coverage'  => array(
						'type'        => 'String',
						'description' => __( 'Coverage information based on the place', 'voye' ),
					),
				),
			)
		);
	}

	/**
	 * Resolves the esimProducts query.
	 *
	 * @param array $root The root query.
	 * @param array $args The query arguments.
	 *
	 * @return array The product details in the specified eSIM category.
	 */
	public function resolve_esim_products( $root, $args ) {
		global $global_countries;
		$cache_key   = 'esim_products_' . md5( wp_json_encode( $args ) );
		$cache_group = 'voyeglobal_graphql';
		$products    = wp_cache_get( $cache_key, $cache_group );

		if ( false === $products ) {
			$products = array();
			$args     = wp_parse_args(
				$args,
				array(
					'currency' => 'USD',
					'place'    => '',
				)
			);

			if ( ! empty( $args['category'] ) ) {
				$category = get_term_by( 'name', $args['category'], 'product_cat' );

				if ( $category ) {
					$query_args = array(
						'post_type'      => 'product',
						'posts_per_page' => -1,
						'meta_key'       => '_price',
						'orderby'        => 'meta_value_num',
						'order'          => 'ASC',
						'tax_query'      => array(
							array(
								'taxonomy' => 'product_cat',
								'field'    => 'term_id',
								'terms'    => $category->term_id,
								'operator' => 'IN',
							),
						),
					);

					$product_query = new \WP_Query( $query_args );

					$currency = $args['currency'];
					$place    = $args['place'];
					while ( $product_query->have_posts() ) {
						$product_query->the_post();
						global $product;

						$coverage = $this->get_coverage( $place, $args['category'], $product );

						$products[] = array(
							'id'        => get_the_ID(),
							'data'      => esc_html( get_field( 'data' ) ) . ' ' . __( 'GB', 'voye' ),
							'valid_for' => esc_html( get_field( 'valid_for' ) ) . ' ' . __( 'Days', 'voye' ),
							'price'     => apply_filters( 'wcml_formatted_price', $product->get_price(), $currency ),
							'coverage'  => $coverage,
						);
					}
					wp_reset_postdata();
				}
			}

			wp_cache_set( $cache_key, $products, $cache_group, HOUR_IN_SECONDS );
		}

		return $products;
	}

	/**
	 * Gets coverage information based on the type and place.
	 *
	 * @param string $place The place to get coverage info for.
	 * @param string $category_name The name of the product category.
	 * @param object $product The product object.
	 *
	 * @return string Coverage information.
	 */
	private function get_coverage( $place, $category_name = null, $product = null ) {
		global $global_countries;

		switch ( $place ) {
			case 'global':
				return $global_countries ? sprintf( '%d %s', count( $global_countries ), __( 'Countries', 'voye' ) ) : '';
			case 'regional':
				if ( $product ) {
					$category_ids = $product->get_category_ids();
					if ( ! empty( $category_ids ) ) {
						$first_category = get_term_by( 'id', $category_ids[0], 'product_cat' );
						$place          = get_field( 'place', 'product_cat_' . $first_category->term_id );
						if ( 'regional' === $place ) {
							$local_cat = get_terms(
								array(
									'taxonomy'   => 'product_cat',
									'hide_empty' => false,
									'fields'     => 'ids',
									'meta_query' => array(
										array(
											'key'     => 'regional_category',
											'value'   => $first_category->term_id,
											'compare' => 'LIKE',
										),
									),
								)
							);
							return sprintf( '%d %s', count( $local_cat ), __( 'Countries', 'voye' ) );
						}
					}
				}
				return __( 'No specific coverage info available', 'voye' );
			case 'local':
				return esc_html( $category_name );
			default:
				return __( 'No specific coverage info available', 'voye' );
		}
	}
}

new EsimProductsQuery();
