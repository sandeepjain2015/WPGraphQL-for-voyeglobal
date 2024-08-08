<?php
add_action('graphql_register_types', function() {
    register_graphql_mutation('sendOTP', [
        'inputFields'  => [
            'email' => [
                'type' => 'String',
                'description' => 'The email address to send the OTP to',
            ],
        ],
        'outputFields' => [
            'success' => [
                'type' => 'Boolean',
                'description' => 'Whether the OTP was sent successfully',
            ],
            'message' => [
                'type' => 'String',
                'description' => 'A message indicating the result of the operation',
            ],
        ],
        'mutateAndGetPayload' => function($input) {
            $email = sanitize_email($input['email']);
            $instance = new LoginHelper();
            $result = $instance->send_otp($email);

            return [
                'success' => $result,
                'message' => $result ? 'OTP Code sent' : 'Failed to send OTP Code',
            ];
        },
    ]);
});

add_action('graphql_register_types', function() {
    register_graphql_mutation('loginWithOTP', [
        'inputFields' => [
            'email' => [
                'type' => 'String',
                'description' => 'The email address of the user',
            ],
            'otp' => [
                'type' => 'String',
                'description' => 'The OTP code sent to the user',
            ],
            'redirect' => [
                'type' => 'String',
                'description' => 'The URL to redirect the user upon successful login',
            ],
        ],
        'outputFields' => [
            'success' => [
                'type' => 'Boolean',
                'description' => 'Indicates whether the login was successful',
            ],
            'returnUrl' => [
                'type' => 'String',
                'description' => 'The URL to which the user should be redirected',
            ],
            'message' => [
                'type' => 'String',
                'description' => 'A message indicating the result of the login attempt',
            ],
        ],
        'mutateAndGetPayload' => function($input) {
            $email = sanitize_email($input['email']);
            $otp = sanitize_text_field($input['otp']);
            $redirect = isset($input['redirect']) ? sanitize_text_field($input['redirect']) : '';
            $instance = new LoginHelper();
            $results = $instance->ajax_login_otp($email, $otp, $redirect);
            return [
                'success' => $results['success'],
                'returnUrl' => $results['success'] ? $results['returnUrl'] : '',
                'message' => $results['message'],
            ];
        }
    ]);
});
?>