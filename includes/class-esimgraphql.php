<?php
/**
 * Provide query for esim plans for user.
 *
 * @package VoyeglobalGraphql
 */

namespace VoyeglobalGraphql\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Class EsimGraphQL
 *
 * Handles eSIM Plan GraphQL types and queries.
 */
class EsimGraphQL {
	/**
	 * EsimGraphQL constructor.
	 */
	public function __construct() {
		add_action( 'graphql_register_types', array( $this, 'register_esim_plan_type' ) );
		add_action( 'graphql_register_types', array( $this, 'register_esim_plans_query' ) );
	}
	/**
	 * Registers the eSIM Plan GraphQL type.
	 *
	 * @return void
	 */
	public function register_esim_plan_type() {
		register_graphql_object_type(
			'EsimPlan',
			array(
				'description' => 'Represents an eSIM plan.',
				'fields'      => array(
					'planId'         => array(
						'type'        => 'String',
						'description' => 'The ID of the plan.',
					),
					'planActive'     => array(
						'type'        => 'Boolean',
						'description' => 'Whether the plan is active.',
					),
					'usageData'      => array(
						'type'        => 'Int',
						'description' => 'The amount of usage data for the plan.',
					),
					'status'         => array(
						'type'        => 'String',
						'description' => 'The status of the plan.',
					),
					'activationDate' => array(
						'type'        => 'String',
						'description' => 'The activation date of the plan.',
					),
				),
			)
		);
	}

	/**
	 * Registers the query to fetch eSIM plans.
	 *
	 * @return void
	 */
	public function register_esim_plans_query() {
		register_graphql_field(
			'RootQuery',
			'displayEsimPlans',
			array(
				'type'        => array( 'list_of' => 'EsimPlan' ),
				'description' => 'Fetch a list of eSIM plans for the current user.',
				'resolve'     => function() {
					$user_id = get_current_user_id();
					// Check if user is logged in.
					if ( ! $user_id ) {
						return new WP_Error( 'authentication_error', 'Please authenticate first.' );
					}

					$customer_esims = array();

					if ( class_exists( 'Webbing_Int' ) ) {
						$webbing_plugin = new Webbing_Int();
						$customer_esims = $webbing_plugin->get_customer_esims( $user_id );
					}

					$plans = array();

					if ( $customer_esims ) {
						foreach ( $customer_esims as $customer_esim ) {
							$esim_id        = $customer_esim['service_id'];
							$usage          = $webbing_plugin->get_customer_plan_device_usage( $esim_id );
							$assigned_plans = array();
							$sim_activated  = $webbing_plugin->check_esim_status( $esim_id );
							$esim_plans     = $webbing_plugin->get_customer_plans_by_esim( $esim_id );

							foreach ( $esim_plans as $esim_plan ) {
								if ( (int) $esim_plan['user_id'] !== $user_id ) {
									continue;
								}

								$plan_id         = strtolower( $esim_plan['plan_id'] );
								$service_plan_id = strtolower( $esim_plan['service_plan_id'] );
								$plan_active     = false;
								$usage_data      = 0;
								$status          = '';
								$activation_date = '';

								if ( isset( $usage[0] ) ) {
									foreach ( $usage as $plan_usage ) {
										if ( strtolower( $plan_usage['CustomerPlanID'] ) === $plan_id && strtolower( $plan_usage['ServiceDeviceCustomerPlanID'] ) === $service_plan_id ) {
											$plan_active     = true;
											$usage_data      = $plan_usage['Usage'];
											$status          = $plan_usage['Status'];
											$activation_date = $plan_usage['ActivationTime'];
											break;
										}
									}
								} elseif ( isset( $usage['CustomerPlanID'] ) ) {
									if ( strtolower( $usage['CustomerPlanID'] ) === $plan_id && strtolower( $usage['ServiceDeviceCustomerPlanID'] ) === $service_plan_id ) {
										$plan_active     = true;
										$usage_data      = $usage['Usage'];
										$status          = $usage['Status'];
										$activation_date = $usage['ActivationTime'];
									}
								}

								$assigned_plans[] = array(
									'planId'         => $plan_id,
									'planActive'     => $plan_active,
									'usageData'      => $usage_data,
									'status'         => $status,
									'activationDate' => $activation_date,
								);
							}

							usort(
								$assigned_plans,
								function( $a, $b ) {
									return ( $b['planActive'] - $a['planActive'] );
								}
							);

							$plans = array_merge( $plans, $assigned_plans );
						}
					}

					return $plans;
				},
			)
		);
	}
}
new EsimGraphQL();

