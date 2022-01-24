<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

/**
 * Class Disciple_Tools_OM_SSO_Menu
 */
class Disciple_Tools_OM_SSO_Menu {

    public $token = 'disciple_tools_om_sso';

    private static $_instance = null;

    /**
     * Disciple_Tools_OM_SSO_Menu Instance
     *
     * Ensures only one instance of Disciple_Tools_OM_SSO_Menu is loaded or can be loaded.
     *
     * @return Disciple_Tools_OM_SSO_Menu instance
     * @since 0.1.0
     * @static
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    } // End instance()


    /**
     * Constructor function.
     * @access  public
     * @since   0.1.0
     */
    public function __construct() {

        add_action( "admin_menu", array( $this, "register_menu" ) );

    } // End __construct()


    /**
     * Loads the subnav page
     * @since 0.1
     */
    public function register_menu() {
        add_submenu_page( 'dt_extensions', 'OM SSO', 'OM SSO', 'manage_dt', $this->token, [ $this, 'content' ] );
    }

    /**
     * Menu stub. Replaced when Disciple Tools Theme fully loads.
     */
    public function extensions_menu() {
    }

    /**
     * Builds page contents
     * @since 0.1
     */
    public function content() {

        if ( ! current_user_can( 'manage_dt' ) ) { // manage dt is a permission that is specific to Disciple Tools and allows admins, strategists and dispatchers into the wp-admin
            wp_die( 'You do not have sufficient permissions to access this page.' );
        }

        if ( isset( $_GET["tab"] ) ) {
            $tab = sanitize_key( wp_unslash( $_GET["tab"] ) );
        } else {
            $tab = 'general';
        }

        $link = 'admin.php?page=' . $this->token . '&tab=';

        ?>
        <div class="wrap">
            <h2>DISCIPLE TOOLS : OM SSO</h2>
            <h2 class="nav-tab-wrapper">
                <a href="<?php echo esc_attr( $link ) . 'general' ?>"
                   class="nav-tab <?php echo esc_html( ( $tab == 'general' || ! isset( $tab ) ) ? 'nav-tab-active' : '' ); ?>">General</a>
            </h2>

            <?php
            switch ( $tab ) {
                case "general":
                    $object = new Disciple_Tools_OM_SSO_Tab_General();
                    $object->content();
                    break;
                default:
                    break;
            }
            ?>

        </div><!-- End wrap -->

        <?php
    }
}

Disciple_Tools_OM_SSO_Menu::instance();

/**
 * Class Disciple_Tools_OM_SSO_Tab_General
 */
class Disciple_Tools_OM_SSO_Tab_General {

    public function __construct() {

        // Load scripts and styles
        wp_enqueue_script( 'dt_om_sso_general_script', plugin_dir_url( __FILE__ ) . 'js/general-tab.js', [
            'jquery',
            'lodash'
        ], filemtime( dirname( __FILE__ ) . '/js/general-tab.js' ), true );
        wp_localize_script(
            "dt_om_sso_general_script", "dt_om_sso", array(
                't_b_c' => []
            )
        );

        // First, handle update submissions
        $this->process_updates();
    }

    private function process_updates() {
        if ( isset( $_POST['sso_main_col_settings_form_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['sso_main_col_settings_form_nonce'] ) ), 'sso_main_col_settings_form_nonce' ) ) {
            if ( isset( $_POST['sso_main_col_settings_form_enabled'] ) ) {
                update_option( 'dt_om_sso_enabled', sanitize_text_field( wp_unslash( $_POST['sso_main_col_settings_form_enabled'] ) ) );
            }
            if ( isset( $_POST['sso_main_col_settings_form_tenant_id'] ) ) {
                update_option( 'dt_om_sso_tenant_id', sanitize_text_field( wp_unslash( $_POST['sso_main_col_settings_form_tenant_id'] ) ) );
            }
            if ( isset( $_POST['sso_main_col_settings_form_client_id'] ) ) {
                update_option( 'dt_om_sso_client_id', sanitize_text_field( wp_unslash( $_POST['sso_main_col_settings_form_client_id'] ) ) );
            }
            if ( isset( $_POST['sso_main_col_settings_form_client_secret'] ) ) {
                update_option( 'dt_om_sso_client_secret', sanitize_text_field( wp_unslash( $_POST['sso_main_col_settings_form_client_secret'] ) ) );
            }
        }
    }

    public function content() {
        ?>
        <div class="wrap">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        <!-- Main Column -->

                        <?php $this->main_column() ?>

                        <!-- End Main Column -->
                    </div><!-- end post-body-content -->
                    <div id="postbox-container-1" class="postbox-container">
                        <!-- Right Column -->

                        <?php /* $this->right_column() */ ?>

                        <!-- End Right Column -->
                    </div><!-- postbox-container 1 -->
                    <div id="postbox-container-2" class="postbox-container">
                    </div><!-- postbox-container 2 -->
                </div><!-- post-body meta box container -->
            </div><!--poststuff end -->
        </div><!-- wrap end -->
        <?php
    }

    public function main_column() {
        ?>
        <!-- Box -->
        <table class="widefat striped">
            <thead>
            <tr>
                <th>Microsoft Azure Settings</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    <?php $this->main_column_settings(); ?>
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <?php
    }

    public function right_column() {
        ?>
        <!-- Box -->
        <table class="widefat striped">
            <thead>
            <tr>
                <th>Information</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    Content
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <?php
    }

    private function main_column_settings() {
        ?>
        <table class="widefat striped">
            <tr>
                <td style="vertical-align: middle;">Enabled</td>
                <td>
                    <?php $enabled = get_option( 'dt_om_sso_enabled' ); ?>
                    <input type="checkbox"
                           id="sso_main_col_settings_enabled" <?php echo esc_attr( ! empty( $enabled ) && boolval( $enabled ) ? 'checked' : '' ); ?>/>
                </td>
            </tr>
            <tr>
                <td style="vertical-align: middle;">Tenant ID</td>
                <td>
                    <?php $tenant_id = get_option( 'dt_om_sso_tenant_id' ); ?>
                    <input style="min-width: 100%;" type="password" id="sso_main_col_settings_tenant_id"
                           value="<?php echo esc_attr( ! empty( $tenant_id ) ? $tenant_id : '' ); ?>"/>
                    <br>
                    <input type="checkbox" id="sso_main_col_settings_tenant_id_show">Show Tenant ID
                </td>
            </tr>
            <tr>
                <td style="vertical-align: middle;">Client ID</td>
                <td>
                    <?php $client_id = get_option( 'dt_om_sso_client_id' ); ?>
                    <input style="min-width: 100%;" type="password" id="sso_main_col_settings_client_id"
                           value="<?php echo esc_attr( ! empty( $client_id ) ? $client_id : '' ); ?>"/>
                    <br>
                    <input type="checkbox" id="sso_main_col_settings_client_id_show">Show Client ID
                </td>
            </tr>
            <tr>
                <td style="vertical-align: middle;">Client Secret</td>
                <td>
                    <?php $client_secret = get_option( 'dt_om_sso_client_secret' ); ?>
                    <input style="min-width: 100%;" type="password" id="sso_main_col_settings_client_secret"
                           value="<?php echo esc_attr( ! empty( $client_secret ) ? $client_secret : '' ); ?>"/>
                    <br>
                    <input type="checkbox" id="sso_main_col_settings_client_secret_show">Show Client Secret
                </td>
            </tr>
        </table>
        <br>
        <span style="float:right;">
            <button type="submit" id="sso_main_col_settings_update"
                    class="button float-right"><?php esc_html_e( "Update", 'disciple_tools' ) ?></button>
        </span>

        <!-- [Submission Form] -->
        <form method="post" id="sso_main_col_settings_form">
            <input type="hidden" id="sso_main_col_settings_form_nonce" name="sso_main_col_settings_form_nonce"
                   value="<?php echo esc_attr( wp_create_nonce( 'sso_main_col_settings_form_nonce' ) ) ?>"/>

            <input type="hidden" value="" id="sso_main_col_settings_form_enabled"
                   name="sso_main_col_settings_form_enabled"/>

            <input type="hidden" value="" id="sso_main_col_settings_form_tenant_id"
                   name="sso_main_col_settings_form_tenant_id"/>

            <input type="hidden" value="" id="sso_main_col_settings_form_client_id"
                   name="sso_main_col_settings_form_client_id"/>

            <input type="hidden" value="" id="sso_main_col_settings_form_client_secret"
                   name="sso_main_col_settings_form_client_secret"/>
        </form>
        <?php
    }
}
