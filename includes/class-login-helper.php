<?php 
class LoginHelper {
 

    /**
     * OTP Login - send code
     *
     * @param  mixed $email
     * @return bool
     */
    public function send_otp($email) {
        session_start();
        $otp_code = mt_rand(111111, 999999);

        if (isset(WC()->session)) {
            WC()->session->set('user_otp', [
                'user_email' => $email,
                'code'       => $otp_code,
            ]);
            WC()->session->set_customer_session_cookie(true);
        }

        $_SESSION['user_otp'] = [
            'user_email' => $email,
            'code'       => $otp_code,
        ];

        $subject = __('Voye OTP (One Time Password)', 'voye');
        $heading = __('Voye OTP (One Time Password)', 'voye');
        $message = '<p>' . __('Hi, Please use the following OTP (One Time Password) below:', 'voye') . '</p>
        <div style="font-weight:bold">' . $otp_code . '</div>
        <br>' . get_field('otp_email_text', 'option');
        // return send_wc_mail($email, $subject, $heading, $message);
        return Mailer::sendEmail($email,  $subject, $message);
    }

    /**
     * OTP Login - resend code
     *
     * @return void
     */
    public function ajax_resend_otp() {
        $results = [
            'success' => false,
            'message' => __('Form Invalid', 'voye'),
        ];

        $email = sanitize_email($_POST['email']);
        if ($email && is_email($email)) {
            if (isset($_SESSION['otp_attempts']) && $_SESSION['otp_attempts'] >= 3) {
                $results['message'] = __('Maximum attempts reached. Please try again later.', 'voye');
            } else {
                if (isset(WC()->session)) {
                    $user_otp = WC()->session->get('user_otp');
                    if ($user_otp && isset($user_otp['user_email']) && $user_otp['user_email'] === $email) {
                        $this->send_otp($email);
                        $results = [
                            'success' => true,
                            'email'   => $email,
                            'message' => __('OTP Code sent', 'voye'),
                        ];
                    }
                }
            }
        }

        wp_send_json($results);
        wp_die();
    }

    /**
     * OTP Login - send otp code to user email
     *
     * @return void
     */
    public function ajax_login() {
        $email   = sanitize_email($_POST['username']);
        $results = [
            'success' => false,
            'message' => __("User doesn't exist", 'voye'),
        ];

        if ($email && is_email($email)) {
            $user_check = get_user_by('email', $email);
            if ($user_check) {
                $this->send_otp($email);
                $results = [
                    'success' => true,
                    'email'   => $email,
                ];
            }
        }

        wp_send_json($results);
        wp_die();
    }

    /**
     * Login user
     *
     * @param  mixed $user
     * @return void
     */
    private function login_user($user) {
        wp_set_current_user($user->ID, $user->data->user_login);
        wp_set_auth_cookie($user->ID);
        do_action('wp_login', $user->data->user_login, $user);
    }

   /**
	 * OTP Login - validate otp code and login
	 *
	 * @return void
	 */
	public function ajax_login_otp() {
		if (session_status() == PHP_SESSION_NONE) {
			session_start();
		} else{
			error_log('session already started');
		}
		
		$results = array(
			'success' => false,
			'message' => __( 'General Error.' ),
		);
		$user_otp_session_demo = WC()->session->get('user_otp_demo');
    error_log('WooCommerce session data: ' . print_r($user_otp_session_demo, true));
		WC()->session->set('debug', true);

// Check session data
$session_data = WC()->session->get_session_data();
error_log('Session data: ' . print_r($session_data, true));
		$custom_user_otp = WC()->session->get('user_otp');

		error_log('all session data '.print_r(WC()->session,1));
		// $custom_user_otp = isset($_SESSION['user_otp']) ? $_SESSION['user_otp'] : array();

		// print_r(WC()->session->get( 'user_otp' )); die('stop');
		$user_otp_session = WC()->session->get('user_otp');
		error_log('WooCommerce session data with login otp: ' . print_r($user_otp_session, true));
		$digit1 = sanitize_text_field( $_POST['digit-1'] );
		$digit2 = sanitize_text_field( $_POST['digit-2'] );
		$digit3 = sanitize_text_field( $_POST['digit-3'] );
		$digit4 = sanitize_text_field( $_POST['digit-4'] );
		$digit5 = sanitize_text_field( $_POST['digit-5'] );
		$digit6 = sanitize_text_field( $_POST['digit-6'] );

		$user_otp = $digit1 . $digit2 . $digit3 . $digit4 . $digit5 . $digit6;

		$email    = sanitize_email( $_POST['email'] );
		$redirect = sanitize_text_field( $_POST['redirect'] );
		
		if ( $email && is_email( $email ) ) {
			
			$user_otp_session = isset( WC()->session ) ? WC()->session->get( 'user_otp' ) : array();
			// $user_otp_session = $custom_user_otp;
			
			if ( $user_otp && strlen( $user_otp ) === 6 ) {

				if ( $user_otp_session ) {
					if ( $user_otp_session['code'] === (int) $user_otp && $user_otp_session['user_email'] === $email ) {
					
						$user_check = get_user_by( 'email', $email );

						if ( $user_check && $user_check->ID ) {
							$this->login_user( $user_check );

							$results = array(
								'success'   => true,
								'returnUrl' => $redirect ? $redirect : get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ),
							);
						} else {
							$results['message'] = __( "Can't find user." );

						}
					} else {
						$results['message']  = __( 'OTP Code Invalid.' );
						$results['attempts'] = WC()->session->get( 'otp_attempts' );

						if ( isset( WC()->session ) && WC()->session->get( 'otp_attempts' ) ) {
							$attempts = WC()->session->get( 'otp_attempts' );
							WC()->session->set( 'otp_attempts', (int) $attempts + 1 );
							$_SESSION['otp_attempts']++;
						} else {
							$_SESSION['otp_attempts'] = 1;
							WC()->session->set( 'otp_attempts', 1 );

						}

						if ( isset( WC()->session ) && (int) WC()->session->get( 'otp_attempts' ) >= 3 ) {
							$results['message'] = __( 'Maximum attempts reached. Please try again later.' );
						}
					}
				} else {
					$results['message'] = __( 'Session Error.' );

				}
			} else {
				$results['message'] = __( 'Email invalid.' );

			}
		}

		wp_send_json( $results );
		wp_die();
	}
}

// Instantiate the class
new LoginHelper();
?>