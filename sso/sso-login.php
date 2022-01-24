<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Detect user log in from O365
if ( isset( $_GET['code'] ) && isset( $_GET['state'] ) && $_GET['state'] === 'dt_om_sso' ) {

    // Package user session tokens
    $fields        = array(
        "client_id"     => get_option( 'dt_om_sso_client_id' ),
        "redirect_uri"  => wp_login_url(),
        "scope"         => 'openid%20offline_access',
        "code"          => $_GET["code"],
        "grant_type"    => 'authorization_code',
        "client_secret" => get_option( 'dt_om_sso_client_secret' )
    );
    $fields_string = "";
    foreach ( $fields as $key => $value ) {
        $fields_string .= $key . "=" . $value . "&";
    }
    $fields_string = rtrim( $fields_string, "&" );

    // Initialise curl instance
    $curl = curl_init();
    if ( $curl === false ) {
        echo '<div id="login_error">' . apply_filters( 'login_errors', "<strong>Error</strong>: There is an error trying to initialize 'curl'." ) . "</div>\n";

        return new WP_Error( 'authentication_failed', "There is an error trying to initialize 'curl'." );
    } else {

        // Build curl payload
        $tenant_id = get_option( 'dt_om_sso_tenant_id' );
        $token_uri = 'https://login.microsoftonline.com/' . $tenant_id . '/oauth2/v2.0/token';
        curl_setopt_array( $curl, array(
            CURLOPT_URL            => $token_uri,
            CURLOPT_HTTPHEADER     => array( "Content-Type: application/x-www-form-urlencoded" ),
            CURLOPT_POST           => count( $fields ),
            CURLOPT_POSTFIELDS     => $fields_string,
            CURLOPT_RETURNTRANSFER => true,
        ) );

        // Dispatch curl payload
        $result = curl_exec( $curl );
        if ( $result === false ) {
            echo '<div id="login_error">' . apply_filters( 'login_errors', "<strong>Error</strong>: CURL Error (" . curl_errno( $curl ) . "): '" . curl_error( $curl ) . "'." ) . "</div>\n";

            return new WP_Error( 'authentication_failed', "There is an error trying to initialize 'curl'." );
        } else {

            // Process response results
            $result = json_decode( $result );
            $return = array();
            if ( property_exists( $result, 'error' ) ) {
                $return['error'] = $result;
            } else {
                $return['success'] = $result;
            }
            curl_close( $curl );

            // Handle success state and fetch corresponding DT user details
            if ( isset( $return['success'] ) ) {

                // Check if O365 response is correct
                if ( isset( $return['success']->access_token ) ) {

                    // Decode 'access_token' to get user email
                    $user_profile_info = json_decode( base64_decode( str_replace( '_', '/', str_replace( '-', '+', explode( '.', $return['success']->access_token )[1] ) ) ) );

                    // Only proceed if a corresponding DT user account details exists!
                    $user_id = email_exists( strtolower( $user_profile_info->upn ) );
                    if ( $user_id !== false || ! is_wp_error( $user_id ) ) {
                        $user_data = get_user_by( 'ID', $user_id );
                        $user_info = get_user_by( 'login', $user_data->data->user_login );
                        if ( $user_info ) {

                            // Log in identified DT user
                            wp_set_current_user( $user_info->ID, $user_info->data->user_login );
                            wp_set_auth_cookie( $user_info->ID );
                            do_action( 'wp_login', $user_info->data->user_login, $user_info );
                            wp_redirect( site_url() );

                        } else {
                            echo '<div id="login_error">' . apply_filters( 'login_errors', "<strong>Error</strong>: Trying to authenticate the user with no success" ) . "</div>\n";

                            return new WP_Error( 'authentication_failed', 'Trying to authenticate the user with no success' );
                        }

                    } else {
                        echo '<div id="login_error">' . apply_filters( 'login_errors', "<strong>Error</strong>: There is not a user linked to your email in this site." ) . "</div>\n";

                        return new WP_Error( 'authentication_failed', 'There is not a user linked to your email in this site.' );
                    }

                } else {
                    echo '<div id="login_error">' . apply_filters( 'login_errors', "<strong>Error</strong>: There are not 'access_token' in the response." ) . "</div>\n";

                    return new WP_Error( 'authentication_failed', "There are not 'access_token' in the response." );
                }

            } else if ( isset( $return['error'] ) ) {
                echo '<div id="login_error">' . apply_filters( 'login_errors', "<strong>Error</strong>: " . $return['error']->error_description ) . "</div>\n";

                return new WP_Error( 'authentication_failed', $return['error']->error_description );
            }
        }
    }
    exit();
}

add_action( 'login_footer', 'display_sso_login_button', 10, 0 );
function display_sso_login_button() {

    // Ensure feature has been enabled, with valid values
    if ( ! empty( get_option( 'dt_om_sso_enabled' ) ) && boolval( get_option( 'dt_om_sso_enabled' ) ) ) {
        if ( ! empty( get_option( 'dt_om_sso_tenant_id' ) ) && ! empty( get_option( 'dt_om_sso_client_id' ) ) && ! empty( get_option( 'dt_om_sso_client_secret' ) ) ) {

            $tenant_id     = get_option( 'dt_om_sso_tenant_id' );
            $client_id     = get_option( 'dt_om_sso_client_id' );
            $redirect_uri  = wp_login_url();
            $scope         = 'openid%20offline_access';
            $response_type = 'code';
            $response_mode = 'query';
            $state         = 'dt_om_sso';
            $authorize_url = 'https://login.microsoftonline.com/' . $tenant_id . '/oauth2/v2.0/authorize?client_id=' . $client_id . '&redirect_uri=' . $redirect_uri . '&scope=' . $scope . '&response_type=' . $response_type . '&response_mode=' . $response_mode . '&state=' . $state;
            ?>
            <a class="loginLink" href="<?php echo $authorize_url; ?>"></a>
            <style>
                .loginLink {
                    background-image: none, url(<?php echo plugin_dir_url(__FILE__) . '0365_login_link.svg'; ?>);
                    background-repeat: no-repeat;
                    display: block;
                    margin-left: auto;
                    margin-right: auto;
                    margin-top: 30px;
                    width: 215px;
                    height: 41px;
                }
            </style>
            <?php
        }
    }
}
