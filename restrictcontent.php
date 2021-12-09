<?php
/**
 * Plugin Name: Restrict Content
 * Plugin URI: https://restrictcontentpro.com
 * Description: Set up a complete membership system for your WordPress site and deliver premium content to your members. Unlimited membership packages, membership management, discount codes, registration / login forms, and more.
 * Version: 3.0.2
 * Author: iThemes
 * Author URI: https://ithemes.com/
 * Text Domain: rcp
 * Domain Path: languages
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$rc_options = get_option( 'rc_settings' );

/**
 * Class RC_Requirements_Check
 *
 * @since 3.0
 */
final class RC_Requirements_Check {

	/**
	 * Plugin file
	 *
	 * @since 3.0
	 * @var string
	 */
	private $file = '';

	/**
	 * Plugin basename
	 *
	 * @since 3.0
	 * @var string
	 */
	private $base = '';

    /**
     * Plugin version number
     *
     * @since 3.0
     * @var float
     */
    private $version = 3.0;

	/**
	 * Requirements array
	 *Yeah
	 * @var array
	 * @since 3.0
	 */
	private $requirements = array(

		// PHP
		'php' => array(
			'minimum' => '5.6.0',
			'name'    => 'PHP',
			'exists'  => true,
			'current' => false,
			'checked' => false,
			'met'     => false
		),

		// WordPress
		'wp' => array(
			'minimum' => '4.4.0',
			'name'    => 'WordPress',
			'exists'  => true,
			'current' => false,
			'checked' => false,
			'met'     => false
		)
	);

	/**
	 * Setup plugin requirements
	 *
	 * @since 3.0
	 */
	public function __construct() {
		// Setup file & base
		$this->file = __FILE__;
		$this->base = plugin_basename( $this->file );

		// Always load translations
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

		// Load or quit
		$this->met()
			? $this->load()
			: $this->quit();
	}

	/**
	 * Quit without loading
	 *
	 * @since 3.0
	 */
	private function quit() {
		add_action( 'admin_head',                        array( $this, 'admin_head'        ) );
		add_filter( "plugin_action_links_{$this->base}", array( $this, 'plugin_row_links'  ) );
		add_action( "after_plugin_row_{$this->base}",    array( $this, 'plugin_row_notice' ) );
	}

	/** Specific Methods ******************************************************/

	/**
	 * Load normally
	 *
	 * @since 3.0
	 */
	private function load() {

        // If the user has expressly chosen a version then load that version.
        if ( rc_option_exists( 'restrict_content_chosen_version' ) ) {
            $user_selected_version = get_option( 'restrict_content_chosen_version');

            // If 3.0 load 3.0
            if ( $user_selected_version === '3.0' ) {
                $this->load_restrict_content_3();
            }
            // Else load legacy
            else {
                $this->load_legacy_restrict_content();
            }
        }
        // Else choose a version to load.
        else {
            // Does the rc_settings option exist? Load legacy if true else load 3.0
            if ( rc_option_exists( 'rc_settings' ) ) {
                // Set chosen version
                update_option( 'restrict_content_chosen_version', 'legacy' );
                $this->load_legacy_restrict_content();
            } else {
                // Set chosen version
                update_option( 'restrict_content_chosen_version', '3.0' );
                $this->load_restrict_content_3();
            }
        }
	}

    /**
     * Load version of Restrict pre 3.0
     *
     * @since 3.0
     */
    private function load_legacy_restrict_content() {
        require_once dirname( $this->file ) . '/legacy/restrictcontent.php';
    }

    private function load_restrict_content_3() {
        // Maybe include the bundled bootstrapper
        if ( ! class_exists( 'Restrict_Content_Pro' ) ) {
            require_once dirname( $this->file ) . '/core/includes/class-restrict-content.php';
        }

        // Maybe hook-in the bootstrapper
        if ( class_exists( 'Restrict_Content_Pro' ) ) {

            // Bootstrap to plugins_loaded before priority 10 to make sure
            // add-ons are loaded after us.
            add_action( 'plugins_loaded', array( $this, 'bootstrap' ), 4 );

            // Register the activation hook
            register_activation_hook( $this->file, array( $this, 'install' ) );
        }
    }

	/**
	 * Install, usually on an activation hook.
	 *
	 * @since 3.0
	 */
	public function install() {
		// Bootstrap to include all of the necessary files
		$this->bootstrap();

		// Network wide?
		$network_wide = ! empty( $_GET['networkwide'] )
			? (bool) $_GET['networkwide']
			: false;

		// Call the installer directly during the activation hook
		rcp_options_install( $network_wide );
	}

	/**
	 * Bootstrap everything.
	 *
	 * @since 3.0
	 */
	public function bootstrap() {
		Restrict_Content_Pro::instance( $this->file );
	}

	/**
	 * Plugin specific URL for an external requirements page.
	 *
	 * @since 3.0
	 * @return string
	 */
	private function unmet_requirements_url() {
		return 'https://docs.restrictcontentpro.com/article/2077-minimum-requirements';
	}

	/**
	 * Plugin specific text to quickly explain what's wrong.
	 *
	 * @since 3.0
	 * @return void
	 */
	private function unmet_requirements_text() {
		esc_html_e( 'This plugin is not fully active.', 'rcp' );
	}

	/**
	 * Plugin specific text to describe a single unmet requirement.
	 *
	 * @since 3.0
	 * @return string
	 */
	private function unmet_requirements_description_text() {
		return esc_html__( 'Requires %s (%s), but (%s) is installed.', 'rcp' );
	}

	/**
	 * Plugin specific text to describe a single missing requirement.
	 *
	 * @since 3.0
	 * @return string
	 */
	private function unmet_requirements_missing_text() {
		return esc_html__( 'Requires %s (%s), but it appears to be missing.', 'rcp' );
	}

	/**
	 * Plugin specific text used to link to an external requirements page.
	 *
	 * @since 3.0
	 * @return string
	 */
	private function unmet_requirements_link() {
		return esc_html__( 'Requirements', 'rcp' );
	}

	/**
	 * Plugin specific aria label text to describe the requirements link.
	 *
	 * @since 3.0
	 * @return string
	 */
	private function unmet_requirements_label() {
		return esc_html__( 'Restrict Content Pro Requirements', 'rcp' );
	}

	/**
	 * Plugin specific text used in CSS to identify attribute IDs and classes.
	 *
	 * @since 3.0
	 * @return string
	 */
	private function unmet_requirements_name() {
		return 'rcp-requirements';
	}

	/** Agnostic Methods ******************************************************/

	/**
	 * Plugin agnostic method to output the additional plugin row
	 *
	 * @since 3.0
	 */
	public function plugin_row_notice() {
		?><tr class="active <?php echo esc_attr( $this->unmet_requirements_name() ); ?>-row">
		<th class="check-column">
			<span class="dashicons dashicons-warning"></span>
		</th>
		<td class="column-primary">
			<?php $this->unmet_requirements_text(); ?>
		</td>
		<td class="column-description">
			<?php $this->unmet_requirements_description(); ?>
		</td>
		</tr><?php
	}

	/**
	 * Plugin agnostic method used to output all unmet requirement information
	 *
	 * @since 3.0
	 */
	private function unmet_requirements_description() {
		foreach ( $this->requirements as $properties ) {
			if ( empty( $properties['met'] ) ) {
				$this->unmet_requirement_description( $properties );
			}
		}
	}

	/**
	 * Plugin agnostic method to output specific unmet requirement information
	 *
	 * @since 3.0
	 * @param array $requirement
	 */
	private function unmet_requirement_description( $requirement = array() ) {

		// Requirement exists, but is out of date
		if ( ! empty( $requirement['exists'] ) ) {
			$text = sprintf(
				$this->unmet_requirements_description_text(),
				'<strong>' . esc_html( $requirement['name']    ) . '</strong>',
				'<strong>' . esc_html( $requirement['minimum'] ) . '</strong>',
				'<strong>' . esc_html( $requirement['current'] ) . '</strong>'
			);

			// Requirement could not be found
		} else {
			$text = sprintf(
				$this->unmet_requirements_missing_text(),
				'<strong>' . esc_html( $requirement['name']    ) . '</strong>',
				'<strong>' . esc_html( $requirement['minimum'] ) . '</strong>'
			);
		}

		// Output the description
		echo '<p>' . $text . '</p>';
	}

	/**
	 * Plugin agnostic method to output unmet requirements styling
	 *
	 * @since 3.0
	 */
	public function admin_head() {

		// Get the requirements row name
		$name = $this->unmet_requirements_name(); ?>

		<style id="<?php echo esc_attr( $name ); ?>">
			.plugins tr[data-plugin="<?php echo esc_html( $this->base ); ?>"] th,
			.plugins tr[data-plugin="<?php echo esc_html( $this->base ); ?>"] td,
			.plugins .<?php echo esc_html( $name ); ?>-row th,
			.plugins .<?php echo esc_html( $name ); ?>-row td {
				background: #fff5f5;
			}
			.plugins tr[data-plugin="<?php echo esc_html( $this->base ); ?>"] th {
				box-shadow: none;
			}
			.plugins .<?php echo esc_html( $name ); ?>-row th span {
				margin-left: 6px;
				color: #dc3232;
			}
			.plugins tr[data-plugin="<?php echo esc_html( $this->base ); ?>"] th,
			.plugins .<?php echo esc_html( $name ); ?>-row th.check-column {
				border-left: 4px solid #dc3232 !important;
			}
			.plugins .<?php echo esc_html( $name ); ?>-row .column-description p {
				margin: 0;
				padding: 0;
			}
			.plugins .<?php echo esc_html( $name ); ?>-row .column-description p:not(:last-of-type) {
				margin-bottom: 8px;
			}
		</style>
		<?php
	}

	/**
	 * Plugin agnostic method to add the "Requirements" link to row actions
	 *
	 * @since 3.0
	 * @param array $links
	 * @return array
	 */
	public function plugin_row_links( $links = array() ) {

		// Add the Requirements link
		$links['requirements'] =
			'<a href="' . esc_url( $this->unmet_requirements_url() ) . '" aria-label="' . esc_attr( $this->unmet_requirements_label() ) . '">'
			. esc_html( $this->unmet_requirements_link() )
			. '</a>';

		// Return links with Requirements link
		return $links;
	}

	/** Checkers **************************************************************/

	/**
	 * Plugin specific requirements checker
	 *
	 * @since 3.0
	 */
	private function check() {

		// Loop through requirements
		foreach ( $this->requirements as $dependency => $properties ) {

			// Which dependency are we checking?
			switch ( $dependency ) {

				// PHP
				case 'php' :
					$version = phpversion();
					break;

				// WP
				case 'wp' :
					$version = get_bloginfo( 'version' );
					break;

				// Unknown
				default :
					$version = false;
					break;
			}

			// Merge to original array
			if ( ! empty( $version ) ) {
				$this->requirements[ $dependency ] = array_merge( $this->requirements[ $dependency ], array(
					'current' => $version,
					'checked' => true,
					'met'     => version_compare( $version, $properties['minimum'], '>=' )
				) );
			}
		}
	}

	/**
	 * Have all requirements been met?
	 *
	 * @since 3.0
	 *
	 * @return boolean
	 */
	public function met() {

		// Run the check
		$this->check();

		// Default to true (any false below wins)
		$retval  = true;
		$to_meet = wp_list_pluck( $this->requirements, 'met' );

		// Look for unmet dependencies, and exit if so
		foreach ( $to_meet as $met ) {
			if ( empty( $met ) ) {
				$retval = false;
				continue;
			}
		}

		// Return
		return $retval;
	}

	/** Translations **********************************************************/

	/**
	 * Plugin specific text-domain loader.
	 *
	 * @since 1.4
	 * @return void
	 */
	public function load_textdomain() {

		// Set filter for plugin's languages directory
		$rcp_lang_dir = dirname( $this->base ) . '/languages/';
		$rcp_lang_dir = apply_filters( 'rcp_languages_directory', $rcp_lang_dir );


		// Traditional WordPress plugin locale filter

		$get_locale = get_locale();

		if ( version_compare( get_bloginfo( 'version' ), '4.7', '>=' ) ) {

			$get_locale = get_user_locale();
		}

		/**
		 * Defines the plugin language locale used in RCP.
		 *
		 * @var string $get_locale The locale to use. Uses get_user_locale()` in WordPress 4.7 or greater,
		 *                  otherwise uses `get_locale()`.
		 */
		$locale        = apply_filters( 'plugin_locale',  $get_locale, 'rcp' );
		$mofile        = sprintf( '%1$s-%2$s.mo', 'rcp', $locale );

		// Setup paths to current locale file
		$mofile_local  = $rcp_lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/rcp/' . $mofile;

		if ( file_exists( $mofile_global ) ) {
			// Look in global /wp-content/languages/rcp folder
			load_textdomain( 'rcp', $mofile_global );
		} elseif ( file_exists( $mofile_local ) ) {
			// Look in local /wp-content/plugins/rcp/languages/ folder
			load_textdomain( 'rcp', $mofile_local );
		} else {
			// Load the default language files
			load_plugin_textdomain( 'rcp', false, $rcp_lang_dir );
		}

	}

}

// Invoke the checker
new RC_Requirements_Check();

function rc_option_exists( $name ) {
    global $wpdb;

    $table = $wpdb->options;

    return $wpdb->query( $wpdb->prepare( "SELECT * FROM {$table} WHERE option_name = %s LIMIT 1" , $name ) );
}

/**
 * Process the switch between Legacy Restrict Content and Restrict Content 3.0
 *
 * @since 3.0
 */
function rc_process_legacy_switch() {

    if ( ! isset( $_POST['rc_process_legacy_nonce'] ) || ! wp_verify_nonce( $_POST['rc_process_legacy_nonce'], 'rc_process_legacy_nonce' ) ) {
        wp_send_json_error( array(
            'success' => false,
            'errors' => 'invalid nonce',
        ) );
        return;
    }

    if ( rc_option_exists( 'restrict_content_chosen_version' ) ) {
        if ( get_option( 'restrict_content_chosen_version' ) == 'legacy' ) {
            $redirectUrl = admin_url( 'admin.php?page=restrict-content-settings' );
            update_option( 'restrict_content_chosen_version', '3.0' );
            wp_send_json_success( array(
                'success'  => true,
                'data'     => array(
                    'redirect' => $redirectUrl
                ),
            ) );
        } else {
            $redirectUrl = admin_url( 'admin.php?page=rcp-members' );
            update_option( 'restrict_content_chosen_version', 'legacy' );
            wp_send_json_success( array(
                'success'  => true,
                'data'     => array(
                    'redirect' => $redirectUrl
                )
            ) );
        }
    } else {
        $redirectUrl = admin_url( 'admin.php?page=restrict-content-settings' );
        update_option( 'restrict_content_chosen_version', 'legacy' );
        wp_send_json_success( array(
            'success'  => true,
            'data'     => array(
                'redirect' => $redirectUrl
            )
        ) );
    }
}
add_action( 'wp_ajax_rc_process_legacy_switch', 'rc_process_legacy_switch' );

function restrict_content_add_legacy_button_to_pro() {
    ?>
    <table>
        <tr>
            <td>
                <input
                        type="hidden"
                        name="rcp_settings_nonce"
                        id="rcp_settings_nonce"
                        value="<?php echo wp_create_nonce( 'rc_process_legacy_nonce' ); ?>"
                />
                <input
                        type="button"
                        id="restrict_content_legacy_switch"
                        class="button-secondary danger"
                        value="<?php _e( 'Downgrade to Legacy Restrict Content?', 'LION' ); ?>"
                />
            </td>
        </tr>
        <tr>
            <td>
                <?php _e( 'After downgrading, you will lose access to most of the features in Restrict Content 3, including membership levels and collecting payments. Additionally, content restrictions made in Restrict Content 3 will be lost after downgrading. Learn More', 'LION' ); ?>
            </td>
        </tr>
    </table>
    <?php
}
add_action( 'rcp_misc_settings', 'restrict_content_add_legacy_button_to_pro' );

/**
 * Load admin styles
 */
function rc_admin_styles_primary( $hook_suffix ) {

    if ( get_option( 'restrict_content_chosen_version' ) == '3.0' ) {
        // Only load admin CSS on Restrict Content Settings page
        if (
            'toplevel_page_restrict-content-settings' == $hook_suffix ||
            'restrict_page_rcp-why-go-pro' == $hook_suffix
        ) {
            wp_enqueue_style('rcp-settings', trailingslashit(plugins_url()) . 'restrict-content/legacy/includes/assets/css/rc-settings.css', array(), RCP_PLUGIN_VERSION);
            wp_enqueue_script('rcp-admin-settings-functionality', trailingslashit(plugins_url()) . 'restrict-content/legacy/includes/assets/js/rc-settings.js', array(), RCP_PLUGIN_VERSION);
            wp_localize_script(
                'rcp-admin-settings-functionality',
                'rcp_admin_settings_options',
                array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'rc_process_legacy_nonce' => wp_create_nonce('rcp-settings-nonce')
                )
            );
        }

        if ('admin_page_restrict-content-welcome' == $hook_suffix || 'restrict_page_rcp-need-help' == $hook_suffix) {
            wp_enqueue_style('rcp-settings', trailingslashit(plugins_url()) . 'restrict-content/legacy/includes/assets/css/rc-settings.css', array(), RCP_PLUGIN_VERSION);
            wp_enqueue_style('rcp-wp-overrides', trailingslashit(plugins_url()) . 'restrict-content/legacy/includes/assets/css/rc-wp-overrides.css', array(), RCP_PLUGIN_VERSION);
            wp_enqueue_script('rcp-admin-settings', trailingslashit(plugins_url()) . 'restrict-content/legacy/includes/assets/js/rc-admin.js', array(), RCP_PLUGIN_VERSION);
        }

        wp_enqueue_style('rcp-metabox', trailingslashit(plugins_url()) . 'restrict-content/legacy/includes/assets/css/rc-metabox.css', array(), RCP_PLUGIN_VERSION);
    }
}
add_action( 'admin_enqueue_scripts', 'rc_admin_styles_primary' );

/**
 * This function is used to add the application fee amount to Stripe purchases
 *
 * @param array $intent_args
 * @param RCP_Payment_Gateway_Stripe $rcp_stripe
 *
 * @since 3.0
 *
 * @return array
 */
function restrict_content_add_application_fee(array $intent_args, RCP_Payment_Gateway_Stripe $rcp_stripe ): array
{
    $intent_args['application_fee_amount'] = restrict_content_stripe_get_application_fee_amount( $intent_args['amount'] );

    return $intent_args;
}
add_filter( 'rcp_stripe_create_payment_intent_args', 'restrict_content_add_application_fee', 10, 2 );

/**
 * This function is used to calculate application fee amount.
 *
 * @param int $amount Donation amount.
 *
 * @since 2.5.0
 *
 * @return int
 */
function restrict_content_stripe_get_application_fee_amount( $amount ): int
{
    return round( $amount * restrict_content_stripe_get_application_fee_percentage() / 100, 0 );
}

/**
 * This function is used to get application fee percentage.
 *
 * Note: This function is for internal purpose only.
 *
 * @since 2.5.0
 *
 * @return int
 */
function restrict_content_stripe_get_application_fee_percentage(): int
{
    return 2;
}

register_activation_hook( __FILE__, function() {
    if ( current_user_can( 'manage_options' ) ) {
        add_option( 'Restrict_Content_Plugin_Activated', 'restrict-content' );
    }
} );

function restrict_content_plugin_activation_redirect() {
    if ( is_admin() && get_option( 'Restrict_Content_Plugin_Activated' ) === 'restrict-content' ) {
        delete_option('Restrict_Content_Plugin_Activated' );
        wp_safe_redirect( admin_url( 'admin.php?page=restrict-content-welcome' ) );
        die();
    }
}
add_action( 'admin_init', 'restrict_content_plugin_activation_redirect' );

function restrict_content_add_stripe_fee_notice() {
    ?>
    <p><?php _e( "Note: The Restrict Content Stripe payment gateway integration includes an additional 2% processing fee. You can remove the processing fee by upgrading to Restrict Content Pro.", "LION" ) ?></p>
    <?php
}
add_action( 'rcp_after_stripe_help_box_admin', 'restrict_content_add_stripe_fee_notice' );

function restrict_content_add_stripe_marketing_email_capture() {
    if ( get_option( 'restrict_content_shown_stripe_marketing' ) == false ) :
        $rc_stripe_marketing_nonce = wp_create_nonce( 'restrict_content_shown_stripe_marketing' );
    ?>
    <tr>
        <th id="rcp_stripe_marketing_container" class="rcp_stripe_help_box" colspan=2 style="display: none;">
            <div id="rcp_stripe_marketing_container_inner_container" class="rcp_stripe_help_box_inner_container">
                <div class="rcp_stripe_help_box_content">
                    <h2><?php _e( 'Activate the Stripe Payment Gateway', 'LION' ); ?></h2>
                    <p><?php _e( 'Enter your email to setup the Stripe payment gateway and get tips about using Restrict Content', 'LION' ); ?></p>
                    <input id="stripe_mailing_list" name="stripe_mailing_list_email" type="email" placeholder="<?php _e( 'Email Address' ); ?>">
                    <input type="checkbox" value="1" name="rc_accept_privacy_policy" id="rc_accept_privacy_policy" class="rc_accept_privacy_policy" <?php checked( true, isset( $rcp_options['disable_active_email'] ) ); ?>/>
                    <span><?php _e( 'Accept Privacy Policy', 'rcp' ); ?></span>
                    <input type="hidden" name="restrict_content_shown_stripe_marketing" id="restrict_content_shown_stripe_marketing" value="<?php echo $rc_stripe_marketing_nonce; ?>" >
                    <div class="stripe_submit_container">
                        <button id="restrict-content-stripe-marketing-submit" class="restrict-content-welcome-button">
                            <?php _e( 'Setup Stripe and Subscribe', 'LION' ); ?>
                        </button>
                        <p class="small"><a href="#payments" id="skip_stripe_marketing_setup"><?php _e( 'Skip, setup payment gateway', 'LION' ); ?></a></p>
                    </div>
                </div>
            </div>
        </th>
    </tr>
    <?php
    endif;
    // Set option so that the marketing is not shown again after this point.
    // update_option( 'restrict_content_shown_stripe_marketing', TRUE );
}
add_action( 'rcp_payments_settings', 'restrict_content_add_stripe_marketing_email_capture' );

/**
 * Load admin styles
 */
function restrict_content_add_stripe_marketing_logic( $hook_suffix ) {
    if ( 'restrict_page_rcp-settings' == $hook_suffix && get_option( 'restrict_content_shown_stripe_marketing' ) == false ) {
        wp_enqueue_script(
                'restrict-content-stripe-marketing',
                trailingslashit( plugins_url() ) . 'restrict-content/core/includes/js/restrict-content-stripe-marketing.js',
                array(),
                RCP_PLUGIN_VERSION
        );
        wp_localize_script(
            'restrict-content-stripe-marketing',
            'rcp_admin_stripe_marketing',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
            )
        );
    }
}
add_action( 'admin_enqueue_scripts', 'restrict_content_add_stripe_marketing_logic' );

function restrict_content_submit_data_to_stripe_mailing_list() {
    if( isset( $_POST['restrict_content_shown_stripe_marketing'] ) && wp_verify_nonce( $_POST['restrict_content_shown_stripe_marketing'], 'restrict_content_shown_stripe_marketing' ) ) {

        $body = array(
            'account' => 'rcp',
            'list_id' => 'ebb8b55cda',
            'tags'    => ['RC-Stripe-Activation'],
            'email'   => $_POST['stripe_mailing_list_email']
        );

        $fields = array(
            'body'   => json_encode( $body )
        );

        $response = wp_remote_post( 'https://api-dev.ithemes.com/newsletter/subscribe', $fields );

        if ( ! is_wp_error( $response ) ) {
            update_option( 'restrict_content_shown_stripe_marketing', true );
            return $response;
        } else {
            rcp_log( json_encode( $response ), true );
        }
    }
}
add_action( 'wp_ajax_rcp_add_to_stripe_mailing_list', 'restrict_content_submit_data_to_stripe_mailing_list' );

/**
 * Deactivates the plugin if Restrict Content Pro is activated.
 *
 * @since 2.2.1
 */
function rc_deactivate_plugin() {
    if ( is_plugin_active('restrict-content-pro/restrict-content-pro.php') ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
    }
}
add_action( 'admin_init', 'rc_deactivate_plugin' );

function restrict_content_3_update_notification() {
    if ( ! get_option( 'dismissed-restrict-content-upgrade-notice', false ) ) {
        ?>
        <div class="notice restrict-content-upgrade-notice notice-info is-dismissible">
            <p>
                <?php
                printf(
                        __( 'Restrict Content 3.0 is here with powerful new features for memberships and content access. <a target="_blank" href="%s">See What\'s New →</a>', 'LION'),
                        'https://restrictcontentpro.com/restrict-content-3-0/?utm_source=restrictcontent&utm_medium=plugin&utm_campaign=rc3_release&utm_content=dashboard-notice'
                );
                ?>
            </p>
        </div>
        <?php
    }
}
add_action( 'admin_notices', 'restrict_content_3_update_notification');