<?php
/**
 * Shared fixtures for the Integration suite.
 *
 * Loaded from _bootstrap.php AFTER WordPress, WooCommerce and the plugins are
 * loaded, so it is safe to extend WooCommerce classes here.
 */

namespace Tests\Integration\Fixtures;

// WooCommerce only loads its admin field helpers (woocommerce_admin_fields())
// when in an admin request. The settings page renders those fields both as its
// default content and as the built-in fallback, so make sure they are available
// during integration tests.
if ( ! function_exists( 'woocommerce_admin_fields' ) ) {
	require_once \WC()->plugin_path() . '/includes/admin/wc-admin-functions.php';
}

/**
 * A minimal payment gateway used to exercise the Gateway settings page.
 */
class TestGateway extends \WC_Payment_Gateway {

	public function __construct() {
		$this->id                 = 'krokedil_test_gateway';
		$this->method_title       = 'Krokedil Test Gateway';
		$this->method_description = 'A test gateway for integration tests.';
		$this->init_form_fields();
		$this->init_settings();
	}

	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'   => 'Enable/Disable',
				'type'    => 'checkbox',
				'label'   => 'Enable Test Gateway',
				'default' => 'no',
			),
			'title'   => array(
				'title'   => 'Gateway Title Field',
				'type'    => 'text',
				'default' => 'Test Gateway',
			),
		);
	}
}

/**
 * A minimal shipping method used to exercise the Shipping settings page.
 */
class TestShippingMethod extends \WC_Shipping_Method {

	public function __construct( $instance_id = 0 ) {
		parent::__construct( $instance_id );

		$this->id                 = 'krokedil_test_shipping';
		$this->method_title       = 'Krokedil Test Shipping';
		$this->method_description = 'A test shipping method for integration tests.';
		$this->init_form_fields();
		$this->init_settings();
	}

	public function init_form_fields() {
		$this->form_fields = array(
			'title'      => array(
				'title'   => 'Shipping Title Field',
				'type'    => 'text',
				'default' => 'Test Shipping',
			),
			'tax_status' => array(
				'title'   => 'Tax status',
				'type'    => 'select',
				'default' => 'taxable',
				'options' => array(
					'taxable' => 'Taxable',
					'none'    => 'None',
				),
			),
		);
	}
}

/**
 * A WcSettingsPage whose custom content renderer always throws, used to verify
 * the graceful error handling in output_subsection_safe().
 */
class ThrowingSettingsPage extends \Krokedil\SettingsPage\WcSettingsPage {

	public function output_page_content(): void {
		throw new \RuntimeException( 'Boom from the content renderer.' );
	}
}
