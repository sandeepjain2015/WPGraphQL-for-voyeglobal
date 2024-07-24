<?php
/**
 * LocalEsimProductsQuery class file.
 *
 * Use as:
 *
 * query FetchLocalEsimProducts($category: String!, $currency: String) {
 *   localEsimProducts(category: $category, currency: $currency) {
 *     id
 *     data
 *     valid_for
 *     price
 *   }
 * }
 *
 * @package VoyeglobalGraphql
 */

namespace VoyeglobalGraphql\Includes;

/**
 * Class LocalEsimProductsQuery
 *
 * Registers a custom GraphQL query to fetch products by local eSIM category.
 */
class LocalEsimProductsQuery {

	/**
	 * LocalEsimProductsQuery constructor.
	 *
	 * Hooks into graphql_register_types to register the query.
	 */
	public function __construct() {
		add_action( 'graphql_register_types', array( $this, 'register_query' ) );
	}

	/**
	 * Registers the localEsimProducts GraphQL query.
	 *
	 * The query fetches products by a local eSIM category.
	 */
	public function register_query() {
		register_graphql_field(
			'RootQuery',
			'localEsimProducts',
			array(
				'type'        => array( 'list_of' => 'LocalEsimProduct' ),
				'description' => __( 'Fetch products by local eSIM category', 'voye' ),
				'args'        => array(
					'category' => array(
						'type'        => 'String',
						'description' => __( 'The name of the local eSIM category', 'voye' ),
					),
					'currency' => array(
						'type'        => 'String',
						'description' => __( 'The currency to display the product prices in, defaults to USD', 'voye' ),
					),
				),
				'resolve'     => array( $this, 'resolve_local_esim_products' ),
			)
		);

		register_graphql_object_type(
			'LocalEsimProduct',
			array(
				'description' => __( 'A product in the local eSIM category', 'voye' ),
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
				),
			)
		);
	}

	/**
	 * Resolves the localEsimProducts query.
	 *
	 * @param array $root The root query.
	 * @param array $args The query arguments.
	 *
	 * @return array The product details in the specified local eSIM category.
	 */
	public function resolve_local_esim_products( $root, $args ) {
		$cache_key   = 'local_esim_products_' . md5( wp_json_encode( $args ) );
		$cache_group = 'voyeglobal_graphql';
		$products    = wp_cache_get( $cache_key, $cache_group );

		if ( false === $products ) {
			$products = array();

			if ( isset( $args['category'] ) ) {
				$category_name = $args['category'];
				$category      = get_term_by( 'name', $category_name, 'product_cat' );

				if ( $category ) {
					$local_args = array(
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

					$local_query = new \WP_Query( $local_args );
					$currency    = isset( $args['currency'] ) && $args['currency'] ? $args['currency'] : 'USD';

					while ( $local_query->have_posts() ) {
						$local_query->the_post();
						global $product;
						$products[] = array(
							'id'        => get_the_ID(),
							'data'      => get_field( 'data' ) . ' ' . __( 'GB', 'voye' ),
							'valid_for' => get_field( 'valid_for' ) . ' ' . __( 'Days', 'voye' ),
							'price'     => apply_filters( 'wcml_formatted_price', $product->get_price(), $currency ),
						);
					}
					wp_reset_postdata();
				}
			}

			wp_cache_set( $cache_key, $products, $cache_group, HOUR_IN_SECONDS );
		}

		return $products;
	}
}

new LocalEsimProductsQuery();
