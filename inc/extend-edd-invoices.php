<?php
/**
 * Additionally load the language from `/languages/edd-invoices`
 * with .mo and .po converted from edd-invoices/languages/edd_inv.pot in
 * your preferred language.
 */
namespace SLUG\SLUG_EDD_INVVOICE;

/**
 * Note: Update SLUG/slug with your own uuid and update the variables below.
 */
define( 'SLUG_BASE_CURRENCY', 'USD' );

define( 'SLUG_TARGET_CURRENCY', 'BGN' );

/**
 * Register to https://apilayer.com/ and subscribe to `Fixer` & `Exchange Rates Data` APIs
 *
 * Both use the same data return strucutre.
 *
 * Use their free options and you will give you 200 requests per month; by using transients
 * for 30 days these 200 request can go a long way and can be enough if you don't have a huge
 * store.
 */
define( 'SLUG_API_LAYER_APIKEY', 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX' );

define( 'SLUG_COMPANY_ADDRESS', '<div class="invoice-label">ACME Inc.</div>123 Main Street<br /> #1<br />City, Postal Code<br />Country<br /><br /><em>Contact Person: Full Name</em></div>' );

define(
	'SLUG_STATUSSES',
	wp_json_encode(
		array(
			// Update these values with your own language.
			'publish'            => 'Пуликувано',
			'complete'           => 'Завършено',
			'revoked'            => 'Отменено',
			'refunded'           => 'Везтановено',
			'partially_refunded' => 'Частично Възтановено',
		)
	)
);

/**
 * Show the EDD Inovoices extentions on for admin users.
 */
if ( current_user_can( 'manage_options' ) ) {
	/**
	 * Additionlly you can add an option under Settings to toggle
	 * the translation and override the plugin functions.
	 */

	// if ( ! get_option( 'uuid_enable_edd_invoice_extension' ) ) {
	// 	return;
	// }

	/**
	 * Unload the original EDD Invoice textdomain the load the
	 * translation and reload the original.
	 */
	function invoices_override_language() {
		unload_textdomain( 'edd-invoices' );
		load_textdomain( 'edd-invoices', get_stylesheet_directory() . '/languages/edd-invoices/edd_inv-bg_BG.mo' );
		load_textdomain( 'edd-invoices', WP_PLUGIN_DIR . '/edd-invoices/languages/edd_inv.pot' );
	}

	add_action( 'after_setup_theme', __NAMESPACE__ . '\invoices_override_language' );

	/**
	 * Override the invoice logo section.
	 */
	function invoices_do_invoice_logo() {
		$logo = edd_get_option( 'edd-invoices-logo' );
		if ( ! $logo ) {
			return;
		}
		?>
		<!-- Logo -->
		<div class="logo">
			<!-- Add company logo manually -->
			<img src="<?php echo esc_url( $logo ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" height="60" />
		</div>
		<?php
	}

	remove_action( 'edd_invoices_invoice_header', 'edd_invoices_do_invoice_logo' );
	add_action( 'edd_invoices_invoice_header', __NAMESPACE__ . '\invoices_do_invoice_logo' );

	/**
	 * Override the invoice order heading section.
	 */
	function invoices_do_invoice_order_heading( $order ) {
		?>
		<!-- Invoice Details -->
		<div class="invoice-details">
			<div class="order-number">
				<?php
				printf(
					'<h1>%s</h1>',
					sprintf(
						/* translators: the invoice number */
						esc_html__( 'Invoice %s', 'edd-invoices' ),
						esc_html( edd_get_payment_number( $order->ID ) )
					)
				);
				?>

				<?php
				switch ( $order->status ) {
					case 'publish':
					case 'complete':
					case 'edd_subscription':
						$status_label = __( 'Paid', 'edd-invoices' );
						break;
					case 'refunded':
						$status_label = __( 'Refunded', 'edd-invoices' );
						break;
					case 'partially_refunded':
						$status_label = __( 'Partially Refunded', 'edd-invoices' );
						break;
					default:
						$status_label = false;
				}

				if ( $status_label ) {
					printf(
						'<div class="payment-status-badge payment-%s">%s</div>',
						esc_attr( $order->status ),
						esc_html( $status_label )
					);
				}
				?>
			</div>

			<?php
			$date = edd_invoices_get_order_date( $order );
			if ( $date ) {
				?>
				<div class="date">
					<!-- Purchase Date -->
					<?php
					$date_format   = get_option( 'date_format' );
					$purchase_date = date_i18n( $date_format, strtotime( $date ) );

					/* Translators: %s - Date of purchase */
					printf( esc_html__( 'Purchase Date: %s', 'edd-invoices' ), esc_html( $purchase_date ) );
					?>
				</div>
				<?php
			}
			?>
		</div>
		<?php
	}

	remove_action( 'edd_invoices_invoice_header', 'edd_invoices_do_invoice_order_heading', 11 );
	add_action( 'edd_invoices_invoice_header', __NAMESPACE__ . '\invoices_do_invoice_order_heading', 11 );

	/**
	 * Override the invoice contacts section.
	 */
	function invoices_do_invoice_contacts( $order ) {
		?>
			<div class="storefront">
				<header>
					<?php esc_html_e( 'Invoice From:', 'edd-invoices' ); ?>
				</header>

				<article>
					<div class="address">
						<!-- Add your company name, address, etc. manually in your language. -->
						<?php echo SLUG_COMPANY_ADDRESS; ?>

					<?php
					// Vendor Company Registration #
					$company_reg = edd_get_option( 'edd-invoices-number' );
					if ( $company_reg ) {
						?>
						<!-- Vendor Company Registration # -->
						<div class="storefront__registration">
							<span class="invoice-label"><?php esc_html_e( 'Registration:', 'edd-invoices' ); ?></span> XXXXXXXXX <?php //echo esc_html( $company_reg ); ?>
						</div>
						<?php
					}

					// Vendor Tax/VAT #
					$tax_id = edd_get_option( 'edd-invoices-tax' );
					if ( $tax_id ) {
						?>
						<br />
						<!-- Vendor Tax/VAT # -->
						<div class="storefront__vat">
							<span class="invoice-label"><?php esc_html_e( 'Tax/VAT:', 'edd-invoices' ); ?></span> <?php echo esc_html( $tax_id ); ?>
						</div>
						<?php
					}

					/**
					 * Fires at the end of the company details.
					 *
					 * @since 1.3.2
					 * @param \EDD\Orders\Order|EDD_Payment $order The order/payment object.
					 */
					do_action( 'edd_invoices_after_company_details', $order );
					?>
				</article>
			</div>
			<div class="customer">
				<header><?php esc_html_e( 'Invoice To:', 'edd-invoices' ); ?></header>

				<article>
					<?php edd_invoices_do_invoice_edit_button( $order ); ?>

					<div class="address">
						<?php
						$address = edd_invoices_get_order_address( $order );
						$company = edd_invoices_get_custom_order_meta( $order, 'invoices_company' );
						if ( ! empty( $company ) ) {
							?>
							<div class="invoice-label"><?php echo esc_html( $company ); ?></div>
							<?php
						}
						if ( ! empty( $address['name'] ) ) {
							?>
							<div class="invoice-label"><?php echo esc_html( $address['name'] ); ?></div>
							<?php
						}
						$keys = array( 'line1', 'line2', 'city', 'zip', 'state', 'country' );
						foreach ( $keys as $key ) {
							if ( ! empty( $address[ $key ] ) ) {
								echo esc_html( $address[ $key ] ) . '<br />';
							}
						}
						?>
					</div>
					<?php
					// Customer Tax/VAT #
					$vat = edd_invoices_get_custom_order_meta( $order, 'invoices_vat' );
					if ( $vat ) {
						?>
						<!-- Customer Tax/VAT # -->
						<div class="customer-vat">
							<span class="invoice-label"><?php esc_html_e( 'Tax/VAT:', 'edd-invoices' ); ?></span> <?php echo esc_html( $vat ); ?>
						</div>
						<?php
					}

					/**
					 * Fires at the end of the customer details.
					 *
					 * @since 1.3.2
					 * @param \EDD\Orders\Order|EDD_Payment $order The order/payment object.
					 */
					do_action( 'edd_invoices_after_customer_details', $order );
					?>
				</article>
			</div>
		<?php
	}

	remove_action( 'edd_invoices_invoice_contacts', 'edd_invoices_do_invoice_contacts' );
	add_action( 'edd_invoices_invoice_contacts', __NAMESPACE__ . '\invoices_do_invoice_contacts' );

	/**
	 * Override the invoice items section section.
	 */
	function invoices_do_invoice_items_table( $order ) {
		// Get the exchange rate.
		$exchange_rate = get_exchange_rate( SLUG_BASE_CURRENCY, SLUG_TARGET_CURRENCY, gmdate( 'Y-m-d', strtotime( $order->date_created ) ) );
		?>
			<header>
				<?php esc_html_e( 'Invoice Items:', 'edd-invoices' ); ?>
			</header>

			<table>
				<tbody>
				<?php
				$items = edd_invoices_get_order_items( $order );
				if ( $items ) {
					foreach ( $items as $key => $item ) {
						?>
						<tr>
							<td class="name"><?php echo wp_kses_post( $item['name'] ); ?></td>
							<?php if ( false !== $exchange_rate ) : ?>
								<td class="price">
									<?php display_price_target_currency( $item['price'] * $exchange_rate ); ?>
								</td>
							<?php else : ?>
								<td class="price">
									<?php echo esc_html( edd_currency_filter( edd_format_amount( $item['price'] ), $order->currency ) ); ?>
								</td>
							<?php endif; ?>
						</tr>
						<?php
					}
				}
				$fees = edd_get_payment_fees( $order->ID );
				if ( $fees ) {
					?>
					<!-- Fees -->
					<?php
					foreach ( $fees as $key => $fee ) {
						?>
						<tr>
							<td class="name"><?php echo ! empty( $fee['label'] ) ? esc_html( $fee['label'] ) : esc_html__( 'Order Fee', 'edd-invoices' ); ?></td>
							<?php if ( false !== $exchange_rate ) : ?>
								<td class="price">
									<?php display_price_target_currency( $fee['amount'] * $exchange_rate ); ?>
								</td>
							<?php else : ?>
								<td class="price">
									<?php echo esc_html( edd_currency_filter( edd_format_amount( $fee['amount'] ), $order->currency ) ); ?>
								</td>
							<?php endif; ?>
						</tr>
						<?php
					}
				}
				?>
				</tbody>
				<tfoot>
					<tr>
						<td class="name"><?php esc_html_e( 'Subtotal:', 'edd-invoices' ); ?></td>
						<?php if ( false !== $exchange_rate ) : ?>
							<td class="price">
								<?php display_price_target_currency( $order->subtotal * $exchange_rate ); ?>
							</td>
						<?php else : ?>
							<td class="price">
								<?php echo esc_html( edd_currency_filter( edd_format_amount( $order->subtotal ), $order->currency ) ); ?>
							</td>
						<?php endif; ?>
					</tr>
				<?php
				$discounts = edd_invoices_get_order_discounts( $order );
				if ( $discounts ) {
					?>
					<!-- Discounts -->
					<?php
					foreach ( $discounts as $discount ) {
						?>
						<tr>
							<td class="name"><?php echo esc_html( $discount['name'] ); ?>:</td>

							<?php if ( false !== $exchange_rate ) : ?>
								<td class="price">
								<?php
									// TODO: This need to updated for if the base currency other than USD, e.g. Euro
									$discount_amount = str_replace( '&#36;', '', trim( $discount['amount'] ) ); // &#36; => $ symbol

									display_price_target_currency( $discount_amount * $exchange_rate );
								?>
								</td>
							<?php else : ?>
								<td class="price">
									<?php echo esc_html( $discount['amount'] ); ?>
								</td>
							<?php endif; ?>
						</tr>
						<?php
					}
				}

				if ( $order->tax > 0 ) {
					$label = __( 'Tax:', 'edd-invoices' );
					$rate  = edd_invoices_get_tax_rate( $order );
					if ( $rate ) {
						/* translators: the order tax rate. */
						$label = sprintf( __( 'Tax (%s%%):', 'edd-invoices' ), $rate );
					}
					?>
					<!-- Tax -->
					<tr>
						<td class="name"><?php echo esc_html( $label ); ?></td>
						<?php if ( false !== $exchange_rate ) : ?>
							<?php
								// TODO: This need to updated for if the base currency other than USD, e.g. Euro
								$payment_tax = str_replace( '&#36;', '', trim( edd_payment_tax( $order->ID ) ) ); // &#36; => $ symbol

								display_price_target_currency( $payment_tax * $exchange_rate );
							?>
						<?php else : ?>
							<td class="price">
								<?php echo esc_html( edd_payment_tax( $order->ID ) ); ?>
							</td>
						<?php endif; ?>
					</tr>
					<?php
				}
				?>

				<!-- Total -->
				<tr>
					<td class="name"><?php esc_html_e( 'Total:', 'edd-invoices' ); ?></td>
					<?php if ( false !== $exchange_rate ) : ?>
						<td class="price">
							<?php
								// TODO: This need to updated for if the base currency other than USD, e.g. Euro
								$order_total = str_replace( '&#36;', '', trim( edd_payment_amount( $order->ID ) ) ); // &#36; => $ symbol

								display_price_target_currency( $order_total * $exchange_rate );
							?>
						</td>
					<?php else : ?>
						<td class="price"><?php echo esc_html( edd_payment_amount( $order->ID ) ); ?></td>
					<?php endif; ?>
				</tr>

				<!-- Paid -->
				<tr>
					<td class="name"><?php esc_html_e( 'Payment Status:', 'edd-invoices' ); ?></td>
					<td class="price">
						<?php
							// This will return an array.
							$statuses = json_decode( SLUG_STATUSSES, true );

							echo array_key_exists( $order->status, $statuses )
								? esc_html( $statuses[ $order->status ] )
								: esc_html( $order->status );
						?>
					</td>
				</tr>

				<?php
				$refunds = false;

				if ( function_exists( 'edd_get_orders' ) ) {
					$refunds = edd_get_orders(
						array(
							'parent' => $order->ID,
							'type'   => 'refund',
						)
					);
					if ( $refunds ) {
						?>
						<tr>
							<td class="name">
								<?php esc_html_e( 'Refunded:', 'edd-invoices' ); ?>
								<br />
								<?php
								foreach ( $refunds as $refund ) {
									printf(
										'<span class="date">%s</span>',
										esc_html( date_i18n( get_option( 'date_format' ), $refund->date_created ) )
									);
									echo '<br />';
								}
								?>
							</td>
							<td class="price">
								<br />
								<?php
								foreach ( $refunds as $refund ) {
									// Override refunds may different dates from the main order date.
									$exchange_rate = get_exchange_rate( SLUG_BASE_CURRENCY, SLUG_TARGET_CURRENCY, gmdate( 'Y-m-d', strtotime( $order->date_created ) ) );

									if ( false !== $exchange_rate ) {
										display_price_target_currency( $refund->total * $exchange_rate );
									} else {
										echo esc_html( edd_currency_filter( edd_format_amount( $refund->total ), $refund->currency ) );
									}

									echo '<br />';
								}
								?>
							</td>
						</tr>
						<?php
					}
				}
				?>
				</tfoot>
			</table>
		<?php
	}

	remove_action( 'edd_invoices_invoice_items_table', 'edd_invoices_do_invoice_items_table' );
	add_action( 'edd_invoices_invoice_items_table', __NAMESPACE__ . '\invoices_do_invoice_items_table' );

	/**
	 * Display, filter and format the calculated amount in the local currency.
	 */
	function display_price_target_currency( $amount ) {
		echo esc_html( edd_currency_filter( edd_format_amount( $amount ), SLUG_TARGET_CURRENCY ) );
	}

	/**
	 * Using https://apilayer.com/ and Fixer & Exchange Rates Data API.
	 *
	 * Use with a free accounts that has 100 request per month.
	 * Save the exchange rates by date in transients to minimize the API requests.
	 *
	 * https://api.apilayer.com/{fixer|exchangerate_data}/{$date}?symbols={$target_currency}&base={$base_currency}
	 */
	function get_exchange_rate( $base_currency, $target_currency, $date ) {
		$transient_slug = 'slug_exchange_rate_' . str_replace( '-', '_', $date );

		// Return exchange rate from transient.
		if ( false !== get_transient( $transient_slug ) ) {
			return get_transient( $transient_slug );
		}

		$data = json_decode( api_exchange_rate( "https://api.apilayer.com/exchangerate_data/{$date}?symbols={$target_currency}&base={$base_currency}" ), true );

		// Try alt APIi if the limit for the 1st one has been reached.
		if ( false === array_key_exists( 'rates', $data ) ) {
			$data = json_decode( api_exchange_rate( "https://api.apilayer.com/fixer/{$date}?symbols={$target_currency}&base={$base_currency}" ), true );
		}

		// Return exchange rate from API req or false if all credits are used.
		if ( false !== array_key_exists( 'rates', $data ) ) {
			$date_exchange_rate = $data['rates'][ $target_currency ];

			set_transient( $transient_slug, $date_exchange_rate, 60 * 60 * 24 * 30 ); // Save it for 30 days.

			return $date_exchange_rate;
		} else {
			return false;
		}
	}

	/**
	 * Make an API call and get the taget currency exchange rate for a specific date.
	 */
	function api_exchange_rate( $url ) {
		$curl = curl_init();

		curl_setopt_array(
			$curl,
			array(
				CURLOPT_URL            => $url,
				CURLOPT_HTTPHEADER     => array(
					'Content-Type: text/plain',
					'apikey: ' . SLUG_API_LAYER_APIKEY,
				),
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING       => '',
				CURLOPT_MAXREDIRS      => 10,
				CURLOPT_TIMEOUT        => 0,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST  => 'GET',
			)
		);

		$response = curl_exec( $curl );

		curl_close( $curl );

		return $response;
	}
}
