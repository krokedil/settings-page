<?php
namespace Krokedil\SettingsPage\SetupChecks;

defined( 'ABSPATH' ) || exit;

/**
 * Ready-made setup checks that plugins can reuse.
 *
 * Every factory takes an `$args` array that overrides the defaults of the check, e.g. its
 * 'severity', 'label', 'message' or 'fix_url'. See Check for the supported arguments.
 */
class Checks {
	/**
	 * A published terms and conditions page is selected in WooCommerce.
	 *
	 * @param array<string, mixed> $args Overrides for the check arguments.
	 *
	 * @return Check
	 */
	public static function terms_page( array $args = array() ): Check {
		return self::make(
			$args,
			array(
				'id'        => 'terms_page',
				'label'     => __( 'Terms and conditions page', 'krokedil-settings' ),
				'severity'  => SetupCheck::SEVERITY_REQUIRED,
				'message'   => __( 'Select a terms and conditions page in the WooCommerce settings.', 'krokedil-settings' ),
				'fix_url'   => admin_url( 'admin.php?page=wc-settings&tab=advanced' ),
				'fix_label' => __( 'Select page', 'krokedil-settings' ),
				'callback'  => function () {
					return self::check_page( (int) wc_terms_and_conditions_page_id() );
				},
			)
		);
	}

	/**
	 * A published checkout page is selected in WooCommerce.
	 *
	 * @param array<string, mixed> $args Overrides for the check arguments.
	 *
	 * @return Check
	 */
	public static function checkout_page( array $args = array() ): Check {
		return self::make(
			$args,
			array(
				'id'        => 'checkout_page',
				'label'     => __( 'Checkout page', 'krokedil-settings' ),
				'severity'  => SetupCheck::SEVERITY_REQUIRED,
				'message'   => __( 'Select a checkout page in the WooCommerce settings.', 'krokedil-settings' ),
				'fix_url'   => admin_url( 'admin.php?page=wc-settings&tab=advanced' ),
				'fix_label' => __( 'Select page', 'krokedil-settings' ),
				'callback'  => function () {
					return self::check_page( (int) wc_get_page_id( 'checkout' ) );
				},
			)
		);
	}

	/**
	 * The store is served over HTTPS.
	 *
	 * @param array<string, mixed> $args Overrides for the check arguments.
	 *
	 * @return Check
	 */
	public static function https( array $args = array() ): Check {
		return self::make(
			$args,
			array(
				'id'        => 'https',
				'label'     => __( 'Secure connection (HTTPS)', 'krokedil-settings' ),
				'severity'  => SetupCheck::SEVERITY_REQUIRED,
				'message'   => __( 'The store address does not use HTTPS. Install an SSL certificate and change the site address to https.', 'krokedil-settings' ),
				'fix_url'   => admin_url( 'options-general.php' ),
				'fix_label' => __( 'Change address', 'krokedil-settings' ),
				'callback'  => function () {
					return wc_site_is_https();
				},
			)
		);
	}

	/**
	 * WordPress uses pretty permalinks, which callbacks and the REST API routes rely on.
	 *
	 * @param array<string, mixed> $args Overrides for the check arguments.
	 *
	 * @return Check
	 */
	public static function pretty_permalinks( array $args = array() ): Check {
		return self::make(
			$args,
			array(
				'id'        => 'pretty_permalinks',
				'label'     => __( 'Permalinks', 'krokedil-settings' ),
				'severity'  => SetupCheck::SEVERITY_REQUIRED,
				'message'   => __( 'Plain permalinks are not supported. Select another permalink structure.', 'krokedil-settings' ),
				'fix_url'   => admin_url( 'options-permalink.php' ),
				'fix_label' => __( 'Change permalinks', 'krokedil-settings' ),
				'option'    => 'permalink_structure',
			)
		);
	}

	/**
	 * Prices use the given number of decimals. Fewer decimals cause rounding differences.
	 *
	 * @param int                  $decimals The number of decimals to expect.
	 * @param array<string, mixed> $args     Overrides for the check arguments.
	 *
	 * @return Check
	 */
	public static function price_decimals( int $decimals = 2, array $args = array() ): Check {
		return self::make(
			$args,
			array(
				'id'        => 'price_decimals',
				'label'     => __( 'Number of decimals', 'krokedil-settings' ),
				'severity'  => SetupCheck::SEVERITY_RECOMMENDED,
				/* translators: %d: number of decimals. */
				'message'   => sprintf( __( 'Set the number of decimals for prices to %d to avoid rounding differences.', 'krokedil-settings' ), $decimals ),
				'fix_url'   => admin_url( 'admin.php?page=wc-settings&tab=general' ),
				'fix_label' => __( 'Change decimals', 'krokedil-settings' ),
				'callback'  => function () use ( $decimals ) {
					return wc_get_price_decimals() === $decimals;
				},
			)
		);
	}

	/**
	 * The store currency is one of the supported currencies.
	 *
	 * @param array<int, string>   $currencies The supported currency codes, e.g. array( 'SEK', 'EUR' ).
	 * @param array<string, mixed> $args       Overrides for the check arguments.
	 *
	 * @return Check
	 */
	public static function currency( array $currencies, array $args = array() ): Check {
		return self::make(
			$args,
			array(
				'id'        => 'currency',
				'label'     => __( 'Store currency', 'krokedil-settings' ),
				'severity'  => SetupCheck::SEVERITY_REQUIRED,
				/* translators: %s: comma separated list of currency codes. */
				'message'   => sprintf( __( 'The store currency is not supported. Supported currencies: %s.', 'krokedil-settings' ), implode( ', ', $currencies ) ),
				'fix_url'   => admin_url( 'admin.php?page=wc-settings&tab=general' ),
				'fix_label' => __( 'Change currency', 'krokedil-settings' ),
				'callback'  => function () use ( $currencies ) {
					return in_array( get_woocommerce_currency(), $currencies, true );
				},
			)
		);
	}

	/**
	 * The store is located in one of the supported countries.
	 *
	 * @param array<int, string>   $countries The supported country codes, e.g. array( 'SE', 'NO' ).
	 * @param array<string, mixed> $args      Overrides for the check arguments.
	 *
	 * @return Check
	 */
	public static function base_country( array $countries, array $args = array() ): Check {
		return self::make(
			$args,
			array(
				'id'        => 'base_country',
				'label'     => __( 'Store country', 'krokedil-settings' ),
				'severity'  => SetupCheck::SEVERITY_REQUIRED,
				/* translators: %s: comma separated list of country codes. */
				'message'   => sprintf( __( 'The store country is not supported. Supported countries: %s.', 'krokedil-settings' ), implode( ', ', $countries ) ),
				'fix_url'   => admin_url( 'admin.php?page=wc-settings&tab=general' ),
				'fix_label' => __( 'Change country', 'krokedil-settings' ),
				'callback'  => function () use ( $countries ) {
					return in_array( WC()->countries->get_base_country(), $countries, true );
				},
			)
		);
	}

	/**
	 * Credentials are entered.
	 *
	 * Only checks that they exist. Do not verify them against an API here, the checks run
	 * on every load of the settings page.
	 *
	 * @param callable|array<int, string> $credentials A callable that receives the page owner and returns whether credentials are entered,
	 *                                                 or a list of setting keys on the page owner that all have to be filled in.
	 * @param array<string, mixed>        $args        Overrides for the check arguments.
	 *
	 * @return Check
	 */
	public static function credentials( $credentials, array $args = array() ): Check {
		$callback = $credentials;

		if ( ! is_callable( $credentials ) ) {
			$callback = function ( $owner ) use ( $credentials ) {
				if ( ! is_object( $owner ) || ! is_callable( array( $owner, 'get_option' ) ) ) {
					return null;
				}

				foreach ( (array) $credentials as $key ) {
					if ( empty( $owner->get_option( $key ) ) ) {
						return false;
					}
				}

				return true;
			};
		}

		return self::make(
			$args,
			array(
				'id'       => 'credentials',
				'label'    => __( 'API credentials', 'krokedil-settings' ),
				'severity' => SetupCheck::SEVERITY_REQUIRED,
				'message'  => __( 'Enter your API credentials in the plugin settings.', 'krokedil-settings' ),
				'callback' => $callback,
			)
		);
	}

	/**
	 * The payment or shipping method the page belongs to is enabled.
	 *
	 * @param string               $setting The key of the setting that enables the method.
	 * @param array<string, mixed> $args    Overrides for the check arguments.
	 *
	 * @return Check
	 */
	public static function enabled( string $setting = 'enabled', array $args = array() ): Check {
		return self::make(
			$args,
			array(
				'id'       => 'enabled',
				'label'    => __( 'Enabled in the store', 'krokedil-settings' ),
				'severity' => SetupCheck::SEVERITY_RECOMMENDED,
				'message'  => __( 'The plugin is not enabled, so customers cannot use it yet.', 'krokedil-settings' ),
				'setting'  => $setting,
				'valid'    => array( 'yes' ),
			)
		);
	}

	/**
	 * Test mode is turned off.
	 *
	 * @param string               $setting The key of the setting that turns test mode on.
	 * @param array<string, mixed> $args    Overrides for the check arguments.
	 *
	 * @return Check
	 */
	public static function test_mode_off( string $setting = 'testmode', array $args = array() ): Check {
		return self::make(
			$args,
			array(
				'id'       => 'test_mode',
				'label'    => __( 'Live mode', 'krokedil-settings' ),
				'severity' => SetupCheck::SEVERITY_INFO,
				'message'  => __( 'Test mode is active. Turn it off before you take real orders.', 'krokedil-settings' ),
				'setting'  => $setting,
				'invalid'  => array( 'yes' ),
			)
		);
	}

	/**
	 * Build a check from its defaults and the overrides of the plugin.
	 *
	 * @param array<string, mixed> $args     Overrides for the check arguments.
	 * @param array<string, mixed> $defaults The default check arguments.
	 *
	 * @return Check
	 */
	protected static function make( array $args, array $defaults ): Check {
		return new Check( array_merge( $defaults, $args ) );
	}

	/**
	 * Check that a page is selected and published.
	 *
	 * WooCommerce accepts an unpublished page in its page settings, but customers cannot open it.
	 *
	 * @param int $page_id The page id.
	 *
	 * @return bool|CheckResult False when no page is selected, a failed result when the selected page is not published.
	 */
	protected static function check_page( int $page_id ) {
		$status = $page_id > 0 ? get_post_status( $page_id ) : false;

		if ( false === $status ) {
			return false;
		}

		if ( 'publish' === $status ) {
			return true;
		}

		return CheckResult::failed(
			/* translators: %s: page title. */
			sprintf( __( 'The selected page "%s" is not published, so customers cannot open it.', 'krokedil-settings' ), get_the_title( $page_id ) ),
			(string) get_edit_post_link( $page_id, 'raw' ),
			__( 'Edit page', 'krokedil-settings' )
		);
	}
}
