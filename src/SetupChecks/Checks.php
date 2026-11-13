<?php
namespace Krokedil\SettingsPage\SetupChecks;

defined( 'ABSPATH' ) || exit;

/**
 * Ready-made setup checks that plugins can reuse.
 *
 * Every factory takes an `$args` array that overrides the defaults of the check, e.g. its
 * 'severity', 'label', 'message' or 'docs_url'. The arguments are listed in docs/setup-checks.md.
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
				'id'          => 'terms_page',
				'label'       => __( 'Terms and conditions page set', 'krokedil-settings' ),
				'description' => __( 'Customers must be able to read your terms and conditions in the checkout.', 'krokedil-settings' ),
				'severity'    => SetupCheck::SEVERITY_REQUIRED,
				'message'     => __( 'No terms and conditions page is set.', 'krokedil-settings' ),
				'fix_url'     => admin_url( 'admin.php?page=wc-settings&tab=advanced' ),
				'fix_label'   => __( 'Select a terms and conditions page', 'krokedil-settings' ),
				'callback'    => function () {
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
				'id'          => 'checkout_page',
				'label'       => __( 'Checkout page set', 'krokedil-settings' ),
				'description' => __( 'The checkout is shown on the checkout page selected in WooCommerce.', 'krokedil-settings' ),
				'severity'    => SetupCheck::SEVERITY_REQUIRED,
				'message'     => __( 'No checkout page is set.', 'krokedil-settings' ),
				'fix_url'     => admin_url( 'admin.php?page=wc-settings&tab=advanced' ),
				'fix_label'   => __( 'Select a checkout page', 'krokedil-settings' ),
				'callback'    => function () {
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
				'id'          => 'https',
				'label'       => __( 'Store served over HTTPS', 'krokedil-settings' ),
				'description' => __( 'Personal and payment details are sent in the checkout, which requires a secure connection.', 'krokedil-settings' ),
				'severity'    => SetupCheck::SEVERITY_REQUIRED,
				'message'     => __( 'Your store address does not use HTTPS. Install an SSL certificate and change the site address to https.', 'krokedil-settings' ),
				'fix_url'     => admin_url( 'options-general.php' ),
				'fix_label'   => __( 'Change the site address', 'krokedil-settings' ),
				'callback'    => function () {
					return wc_site_is_https();
				},
			)
		);
	}

	/**
	 * WordPress uses pretty permalinks.
	 *
	 * @param array<string, mixed> $args Overrides for the check arguments.
	 *
	 * @return Check
	 */
	public static function pretty_permalinks( array $args = array() ): Check {
		return self::make(
			$args,
			array(
				'id'          => 'pretty_permalinks',
				'label'       => __( 'Pretty permalinks configured', 'krokedil-settings' ),
				'description' => __( 'Callbacks from the payment provider rely on WordPress using pretty permalinks.', 'krokedil-settings' ),
				'severity'    => SetupCheck::SEVERITY_REQUIRED,
				'message'     => __( 'The permalink structure is set to "Plain", which is not supported.', 'krokedil-settings' ),
				'fix_url'     => admin_url( 'options-permalink.php' ),
				'fix_label'   => __( 'Change the permalink structure', 'krokedil-settings' ),
				'option'      => 'permalink_structure',
			)
		);
	}

	/**
	 * Prices use the given number of decimals.
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
				'id'          => 'price_decimals',
				/* translators: %d: number of decimals. */
				'label'       => sprintf( __( 'Number of decimals set to %d', 'krokedil-settings' ), $decimals ),
				'description' => __( 'Fewer decimals make WooCommerce round prices and taxes, which can make the order total differ from the amount the customer pays.', 'krokedil-settings' ),
				'severity'    => SetupCheck::SEVERITY_RECOMMENDED,
				/* translators: %d: number of decimals. */
				'message'     => sprintf( __( 'Currently set to %d.', 'krokedil-settings' ), wc_get_price_decimals() ),
				'fix_url'     => admin_url( 'admin.php?page=wc-settings&tab=general' ),
				'fix_label'   => __( 'Change the number of decimals', 'krokedil-settings' ),
				'callback'    => function () use ( $decimals ) {
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
				'id'          => 'currency',
				'label'       => __( 'Store currency supported', 'krokedil-settings' ),
				'description' => __( 'Payments can only be made in the currencies that the payment provider supports.', 'krokedil-settings' ),
				'severity'    => SetupCheck::SEVERITY_REQUIRED,
				/* translators: 1: the store currency code, 2: comma separated list of currency codes. */
				'message'     => sprintf( __( 'The store currency %1$s is not supported. Supported currencies: %2$s.', 'krokedil-settings' ), get_woocommerce_currency(), implode( ', ', $currencies ) ),
				'fix_url'     => admin_url( 'admin.php?page=wc-settings&tab=general' ),
				'fix_label'   => __( 'Change the store currency', 'krokedil-settings' ),
				'callback'    => function () use ( $currencies ) {
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
				'id'          => 'base_country',
				'label'       => __( 'Store country supported', 'krokedil-settings' ),
				'description' => __( 'The payment provider only serves stores in certain countries.', 'krokedil-settings' ),
				'severity'    => SetupCheck::SEVERITY_REQUIRED,
				/* translators: %s: comma separated list of country codes. */
				'message'     => sprintf( __( 'The store country is not supported. Supported countries: %s.', 'krokedil-settings' ), implode( ', ', $countries ) ),
				'fix_url'     => admin_url( 'admin.php?page=wc-settings&tab=general' ),
				'fix_label'   => __( 'Change the store country', 'krokedil-settings' ),
				'callback'    => function () use ( $countries ) {
					return in_array( WC()->countries->get_base_country(), $countries, true );
				},
			)
		);
	}

	/**
	 * Credentials are entered. Only checks that they exist, since the checks run on every page load.
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
				'id'          => 'credentials',
				'label'       => __( 'API credentials entered', 'krokedil-settings' ),
				'description' => __( 'The plugin needs your API credentials to connect to the payment provider.', 'krokedil-settings' ),
				'severity'    => SetupCheck::SEVERITY_REQUIRED,
				'message'     => __( 'No API credentials are entered in the plugin settings.', 'krokedil-settings' ),
				'callback'    => $callback,
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
				'id'          => 'enabled',
				'label'       => __( 'Enabled in the store', 'krokedil-settings' ),
				'description' => __( 'Customers can only use the plugin when it is enabled.', 'krokedil-settings' ),
				'severity'    => SetupCheck::SEVERITY_RECOMMENDED,
				'message'     => __( 'The plugin is not enabled, so customers cannot use it yet.', 'krokedil-settings' ),
				'setting'     => $setting,
				'valid'       => array( 'yes' ),
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
				'id'          => 'test_mode',
				'label'       => __( 'Test mode turned off', 'krokedil-settings' ),
				'description' => __( 'No real payments are made while test mode is active.', 'krokedil-settings' ),
				'severity'    => SetupCheck::SEVERITY_INFO,
				'message'     => __( 'Test mode is active. Turn it off before you take real orders.', 'krokedil-settings' ),
				'setting'     => $setting,
				'invalid'     => array( 'yes' ),
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
			__( 'Edit the page', 'krokedil-settings' )
		);
	}
}
