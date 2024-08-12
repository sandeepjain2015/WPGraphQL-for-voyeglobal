<?php
/**
 * Class RegisterUserMutation
 *
 * This class handles the registration of a custom GraphQL mutation for user registration.
 *
 * @package VoyeglobalGraphql
 */

namespace VoyeglobalGraphql\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WC_Customer;
/**
 * Class RegisterUserMutation
 *
 * Registers a custom GraphQL mutation for user registration.
 */
class RegisterUserMutation {

	/**
	 * Initializes the class by hooking into WordPress actions.
	 */
	public static function init() {
		add_action( 'graphql_register_types', array( __CLASS__, 'register_mutation' ), 11 );
	}

	/**
	 * Registers the custom GraphQL mutation.
	 */
	public static function register_mutation() {
		register_graphql_mutation(
			'RegisterUserMutation',
			array(
				'inputFields'         => array(
					'email'               => array(
						'type'        => 'String',
						'description' => __( 'User email address', 'your-textdomain' ),
					),
					'fullname'            => array(
						'type'        => 'String',
						'description' => __( 'User full name', 'your-textdomain' ),
					),
					'phoneCode'           => array(
						'type'        => 'String',
						'description' => __( 'User phone code', 'your-textdomain' ),
					),
					'phone'               => array(
						'type'        => 'String',
						'description' => __( 'User phone number', 'your-textdomain' ),
					),
					'subscribeNewsletter' => array(
						'type'        => 'Boolean',
						'description' => __( 'Whether user wants to subscribe to newsletter', 'your-textdomain' ),
					),
					'password'            => array(
						'type'        => 'String',
						'description' => __( 'User password', 'your-textdomain' ),
					),
				),
				'outputFields'        => array(
					'success'      => array(
						'type'        => 'Boolean',
						'description' => __( 'Whether registration was successful', 'your-textdomain' ),
					),
					'errorMessage' => array(
						'type'        => 'String',
						'description' => __( 'Error message if registration failed', 'your-textdomain' ),
					),
				),
				'mutateAndGetPayload' => array( __CLASS__, 'mutate_and_get_payload' ),
			)
		);
	}

	/**
	 * Handles the mutation and returns the payload.
	 *
	 * @param array $input The input fields from the mutation.
	 * @return array The result of the mutation with success status and an error message, if any.
	 */
	public static function mutate_and_get_payload( $input ) {
		$user_email           = sanitize_email( $input['email'] );
		$user_fullname        = sanitize_text_field( $input['fullname'] );
		$user_phone_code      = isset( $input['phoneCode'] ) ? sanitize_text_field( $input['phoneCode'] ) : '';
		$user_phone           = sanitize_text_field( $input['phone'] );
		$subscribe_newsletter = isset( $input['subscribeNewsletter'] ) ? (bool) $input['subscribeNewsletter'] : false;
		$password             = sanitize_text_field( $input['password'] );

		// Check if email is valid.
		if ( ! is_email( $user_email ) ) {
			return array(
				'success'      => false,
				'errorMessage' => __( 'Invalid email address', 'your-textdomain' ),
			);
		}

		// Check if customer already exists.
		$existing_customer = email_exists( $user_email );
		if ( $existing_customer ) {
			return array(
				'success'      => false,
				'errorMessage' => __( 'Email address already registered', 'your-textdomain' ),
			);
		}

		// Create a new customer.
		$customer = new WC_Customer();
		$customer->set_email( $user_email );
		$customer->set_first_name( $user_fullname );
		$customer->set_billing_phone( $user_phone );
		$customer->set_meta_data( 'phone_code', $user_phone_code );
		$customer->set_password( $password );

		// Save customer.
		$customer_id = $customer->save();
		if ( is_wp_error( $customer_id ) ) {
			return array(
				'success'      => false,
				'errorMessage' => $customer_id->get_error_message(),
			);
		}

		// Update newsletter subscription status.
		update_field( 'newsletter', $subscribe_newsletter, 'user_' . $customer_id );

		return array(
			'success'      => true,
			'errorMessage' => null,
		);
	}
}

// Initialize the class to set up the mutation.
RegisterUserMutation::init();
