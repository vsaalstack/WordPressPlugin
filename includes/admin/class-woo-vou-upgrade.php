<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Upgrade Class
 *
 * Handles generic Upgrade functionality and AJAX requests.
 *
 * @package WooCommerce - PDF Vouchers
 * @since 2.3.0
 */
class WOO_Vou_Upgrade {
	
	var $scripts, $model, $render;
	
	public function __construct() {
		
		global $woo_vou_scripts,$woo_vou_model,
				$woo_vou_render, $woo_vou_admin_meta;
		
		$this->scripts 	= $woo_vou_scripts;
		$this->model 	= $woo_vou_model;
	}
	
	/**
	 * Adding Upgrade Submenu Page
	 * 
	 * Handles to adding upgrade submenu page
	 * 
	 * @package WooCommerce - PDF Vouchers
	 * @since 2.3.0
	 */
	public function woo_vou_upgrade_submenu() {

		//add page for upgrates, changed install_plugins to manage_woocommerce
		$vou_upgrades_screen = add_submenu_page( null, esc_html__( 'PDF Voucher Upgrades', 'woovoucher' ), esc_html__( 'PDF Voucher Upgrades', 'woovoucher' ), 'manage_woocommerce', 'vou-upgrades', array( $this, 'woo_vou_upgrades_screen' ) );
	}
	
	/**
	 * Render Upgrades Screen
	 * 
	 * Handle to render upgrade screen
	 * 
	 * @package WooCommerce - PDF Vouchers
	 * @since 2.3.0
	 */
	function woo_vou_upgrades_screen() {
		?>
		<div class="wrap">
			<h2><?php echo __( 'PDF Vouchers - Upgrades', 'woovoucher' ); ?></h2>
			<div id="vou-upgrade-status">
				<p>
					<?php echo esc_html__( 'The upgrade process has started, please be patient. This could take several minutes. You will be automatically redirected when the upgrade is finished.', 'woovoucher' ); ?>
					<img src="<?php echo esc_url(WOO_VOU_URL) . '/includes/images/ajax-loader.gif'; ?>" id="vou-upgrade-loader"/>
				</p>
			</div>
		</div><?php
	}
	
	/**
	 * Display Upgrade Notices
	 *
	 * @package WooCommerce - PDF Vouchers
	 * @since 2.3.0
	 */
	function woo_vou_show_upgrade_notices() {

		$woo_vou_plugin_version = get_option( 'woo_vou_plugin_version' );
	
		if ( ! $woo_vou_plugin_version ) {
			// 2.2.1 is the first version to use this option so we must add it
			$woo_vou_plugin_version = '2.2.1';
		}
	
		//Get Valid version number
		$woo_vou_plugin_version = preg_replace( '/[^0-9.].*/', '', $woo_vou_plugin_version );
	
		if ( version_compare( $woo_vou_plugin_version, '2.3.0', '<' ) ) {
			printf(
				'<div class="updated"><p>' . esc_html__( 'WooCommerce Pdf Voucher needs to upgrade the order database, click %shere%s to start the upgrade.', 'woovoucher' ) . '</p></div>',
				'<a href="' . esc_url( admin_url( 'index.php?page=vou-upgrades&vou-upgrade=upgrade_db' ) ) . '">',
				'</a>'
			);
		}
		
		// If version number is less than 2.9.7 than update vendor and admin capabilities
		if ( version_compare( $woo_vou_plugin_version, '2.9.7', '<' ) ) {

			printf(
				'<div class="updated"><p>' . esc_html__( 'WooCommerce Pdf Voucher needs to upgrade the database for voucher templates capability, click %shere%s to start the upgrade.', 'woovoucher' ) . '</p></div>',
				'<a href="' . esc_url( admin_url( '?vou-upgrade=upgrade_capability' ) ) . '">',
				'</a>'
			);
		}

		// Success message when capabilities get successfully upgraded
		if ( !empty ( $_GET['vou_success'] ) && $_GET['vou_success'] == 'true' ) {

			// Success Message
			printf('<div class="updated"><p>' . esc_html__( 'Capabilities have been upgraded successfully.', 'woovoucher' ) . '</p></div>', 'woovoucher');
		}
	
		// If version number is less than 3.7.0 than add customerID in voucher code meta.
		if ( version_compare( $woo_vou_plugin_version, '3.7.0', '<' ) ) {
			printf(
				'<div class="updated"><p>' . esc_html__( 'WooCommerce Pdf Voucher needs to upgrade the voucher codes database, click %shere%s to start the upgrade.', 'woovoucher' ) . '</p></div>',
				'<a href="' . esc_url( admin_url( 'index.php?page=vou-upgrades&vou-upgrade=upgrade_db' ) ) . '">',
				'</a>'
			);
		}

		// If version number is less than 4.2.13 than add customerID in voucher code meta.
		if ( version_compare( $woo_vou_plugin_version, '4.2.12', '<' ) ) {
			printf(
				'<div class="updated"><p>' . esc_html__( 'WooCommerce Pdf Voucher needs to upgrade the voucher codes database, click %shere%s to start the upgrade.', 'woovoucher' ) . '</p></div>',
				'<a href="' . esc_url( admin_url( 'index.php?page=vou-upgrades&vou-upgrade=upgrade_db' ) ) . '">',
				'</a>'
			);
		}
	}
	
	/**
	 * Triggers all upgrade functions
	 *
	 * This function is usually triggered via AJAX
	 *
	 * @package WooCommerce - PDF Vouchers
	 * @since 2.3.0
	 */
	function woo_vou_trigger_upgrades() {
	
		if( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to do shop upgrades', 'woovoucher' ), esc_html__( 'Error', 'woovoucher' ), array( 'response' => 403 ) );
		}
		
		$woo_vou_plugin_version = get_option( 'woo_vou_plugin_version' );
		
		if ( ! $woo_vou_plugin_version ) {
			// 2.2.1 is the first version to use this option so we must add it
			$woo_vou_plugin_version = '2.2.1';
			add_option( 'woo_vou_plugin_version', $woo_vou_plugin_version );
		}
		
		if ( version_compare( $woo_vou_plugin_version, '2.3.0', '<' ) ) {
			$this->woo_vou_v230_upgrades();
		}

		// If version number is less than 3.7.0 than add customerID in voucher code meta.
		if ( version_compare( $woo_vou_plugin_version, '3.7.0', '<' ) ) {
			$this->woo_vou_v370_upgrades();
		}
		
		update_option( 'woo_vou_plugin_version', WOO_VOU_PLUGIN_VERSION );
		
		if ( DOING_AJAX ) {
			die( 'complete' ); // Let AJAX know that the upgrade is complete
		}
	}
	
	/**
	 * Upgrades for PDF Voucher v2.3.0
	 * 
	 * @package WooCommerce - PDF Vouchers
	 * @since 2.3.0
	 */
	function woo_vou_v230_upgrades() {
		
		//Run upgarade old orders script
		$this->woo_vou_admin_run_udater_script();
		
		//Sleep for 10 seconds
		sleep( 10 );
	}
	
	/**
	 * Updater Scripts
	 * 
	 * Handle to update options which need for version
	 * 
	 * @package WooCommerce - PDF Vouchers
	 * @since 2.3.0
	 */
	public function woo_vou_admin_run_udater_script() {
		
		$prefix = WOO_VOU_META_PREFIX;
		
		$args	= array( 
			'post_type'			=> WOO_VOU_MAIN_SHOP_POST_TYPE,
			'posts_per_page'	=> -1,
			'post_status'		=> 'any',
			'meta_query'		=> array(
				array(
					'key'		=> $prefix . 'order_details',
					'compare'	=> 'EXISTS',
				)
			)
		);
		
		//Get results
		$results	= new WP_Query( $args );
		
		// Get OLD Orders
		$orders		= isset( $results->posts ) ? $results->posts : '';
		
		if( !empty( $orders ) ) {//If or der is not empty
			
			foreach ( $orders as $shop_order ) {//For all old orders
				
				$order_id	= isset( $shop_order->ID ) ? $shop_order->ID : '';
				
				if( !empty( $order_id ) ) {// If order_id not empty
					
					$order_vouchers = array();
					
					$old_meta		= get_post_meta( $order_id, $prefix.'order_details', true );
					$order			= new WC_Order( $order_id );
					$order_items	= $order->get_items();
					
					if( !empty( $order_items ) ) { // If not empty items
						
						foreach ( $order_items as $item_key => $item_data ) {
							
							// If product is variable product take variation id else product id
							$data_id	= ( !empty( $item_data['variation_id'] ) ) ? $item_data['variation_id'] : $item_data['product_id'];
							
							//Order Vouchers Meta
							$order_vouchers[$item_key] = isset( $old_meta[$data_id]['codes'] ) ? $old_meta[$data_id]['codes'] : '';
							
						}
					}
					
					if( !empty( $order_vouchers ) ) {//If not empty old order meta
						foreach ( $order_vouchers as $order_item_key => $order_voucher ) {
							
							wc_update_order_item_meta( $order_item_key, $prefix.'codes', $order_voucher );
						}
					}
					
					//Delete older voucher codes post meta
					delete_post_meta( $order_id, $prefix.'order_details' );
				}
			}
		}
	}
	
	/**
	 * Upgrades for PDF Voucher v3.7.0
	 * 
	 * @package WooCommerce - PDF Vouchers
	 * @since 3.7.0
	 */
	function woo_vou_v370_upgrades() {
		
		//Run upgarade old voucher code script
		$this->woo_vou_admin_run_v370_udater_script();
		
		//Sleep for 10 seconds
		sleep( 10 );
	}
	
	/**
	 * Add customerID in voucher code meta.
	 * 
	 * @package WooCommerce - PDF Vouchers
	 * @since 3.7.0
	 */
	public function woo_vou_admin_run_v370_udater_script() {
		
		$prefix = WOO_VOU_META_PREFIX;
		
		$args	= array( 
			'post_type'			=> WOO_VOU_CODE_POST_TYPE,
			'posts_per_page'	=> -1,
			'post_status'		=> 'any',
			'meta_query'		=> array(
				array(
					'key'		=> $prefix . 'customer_user',
					'compare'	=> 'NOT EXISTS',
				)
			)
		);

		//Get results
		$results	= new WP_Query( $args );
		
		// Get OLD Voucher codes
		$old_voucher_codes = isset( $results->posts ) ? $results->posts : '';

		if( !empty( $old_voucher_codes ) ) {//If old voucher codes is not empty
			
			foreach ( $old_voucher_codes as $voucher_code ) {//For all old voucher codes
				
				$voucher_code_id	= isset( $voucher_code->ID ) ? $voucher_code->ID : '';

				if( !empty( $voucher_code_id ) ) {// If voucher_code_id not empty

					// OrderID and CustomerID
					$order_id = get_post_meta($voucher_code_id, $prefix . 'order_id', true);
					$order_customer_id = get_post_meta($order_id, '_customer_user', true);

	                // update customer id
	                update_post_meta($voucher_code_id, $prefix . 'customer_user', $order_customer_id);

				}
			}
		}
	}

	/**
	 * Add redeemed on meta for all vouchers
	 * 
	 * @package WooCommerce - PDF Vouchers
	 * @since 4.3.0
	 */
	public function woo_vou_admin_run_v430_udater_script() {

		global $wpdb;

		$migrated = get_option( 'woo_vou_voucher_redeem_on_migration' );
		if( 'yes' == $migrated ) {
			echo 'completed';
			exit;
		}

		$args = array(
			'post_type'		=> 'woovouchercodes',
			'numberposts'	=> 10,
			'fields'		=> 'ids',
			'meta_query'	=> array(
				'relation' => 'AND',
				array(
					'key'		=> '_woo_vou_redeemed_on',
					'compare'	=> 'NOT EXISTS',
				),
				array(
					'key' => '_woo_vou_redeem_method',
					'compare' => 'EXISTS'
				), array(
					'key' => '_woo_vou_redeemed_data_migrated',
					'compare' => 'NOT EXISTS'
				)
			)
		);

		$vou_code_ids = get_posts( $args );
		if ( ! empty($vou_code_ids) ) {

			$itm		= $wpdb->prefix . 'woocommerce_order_items';
			$itmMeta	= $wpdb->prefix . 'woocommerce_order_itemmeta';

			foreach ( $vou_code_ids as $vou_code_id ) {

				// check if method is full
				$method = get_post_meta( $vou_code_id, '_woo_vou_redeem_method', true );

				// Get code
				$vou_code = get_post_meta( $vou_code_id, '_woo_vou_purchased_codes', true );

				// get partial reedeem childres
				$partRedeemedPosts = get_children( array(
					'post_type'		=> 'woovoupartredeem',
					'numberposts'	=> -1,
					'post_parent'	=> $vou_code_id,
					'fields'		=> 'ids',
					'meta_query'	=> array(
						array(
							'key'		=> '_woo_vou_redeemed_on',
							'compare'	=> 'NOT EXISTS',
						)
					)
				) );

				// Get order id and item id with order item id.
				$query = "SELECT order_item_id, order_id FROM {$itm} WHERE order_item_name = '{$vou_code}' AND order_item_type = 'coupon';";

				$orders = $wpdb->get_results( $query, ARRAY_A );

				// If partial redeem exists
				if ( !empty( $partRedeemedPosts ) ) {

					// Check if not empty add discount amount, to match with partial redeemtion
					if ( !empty($orders) ) {
						foreach ( $orders as $i => $order ) {

							$discount	= wc_get_order_item_meta( $order['order_item_id'], 'discount_amount', true );
							$tax		= wc_get_order_item_meta( $order['order_item_id'], 'discount_amount_tax', true );

							if ( !empty($tax) ) {
								$discount =  $discount + $tax;
							}
							$orders[$i]['discount'] = $discount;
						}
					}

					foreach ( $partRedeemedPosts as $partPost ) {
						if ( empty($orders) ) {
							update_post_meta( $partPost, '_woo_vou_redeemed_on', 'offline' );
						} else {

							// Get discount amount
							$amount = get_post_meta( $partPost, '_woo_vou_partial_redeem_amount', true );

							// Check in order with amount
							$found = false;
							foreach ( $orders as $i => $order ) {
								if ( $order['discount'] == $amount ) {
									$found = true;
									update_post_meta( $partPost, '_woo_vou_redeemed_on', $order['order_id'] );
									unset( $orders[$i] );
								}
							}

							// Check if no order found
							if( ! $found ) {
								update_post_meta( $partPost, '_woo_vou_redeemed_on', 'offline' );
							}
						}
					}
				}

				// else check for full
				if ( 'full' == $method ) {
					if ( !empty( $order[0]['order_id'] ) ) {
						update_post_meta( $vou_code_id, '_woo_vou_redeemed_on', $order[0]['order_id'] );
					} else {
						update_post_meta( $vou_code_id, '_woo_vou_redeemed_on', 'offline' );
					}
				}

				update_post_meta( $vou_code_id, '_woo_vou_redeemed_data_migrated', 'yes' );
			}

			echo 'process';
			exit;
		}

		update_option( 'woo_vou_voucher_redeem_on_migration', 'yes' );
		echo 'completed';
		exit;
	}
	
	/**
	 * Handles to upgrade add capability of vendor user
	 * when plugin updated above 2.9.7 version
	 * 
	 * @package WooCommerce - PDF Vouchers
	 * @since 2.3.0
	 */
	public function woo_vou_add_capability_to_vendor() {
		
		if ( !empty ( $_GET['success'] ) && $_GET['success'] == 'true' ) {
			
		}

		if ( !empty ( $_GET['vou-upgrade'] ) && $_GET['vou-upgrade'] == 'upgrade_capability' ) {

			// Add capabilities to roles
			woo_vou_add_role_capabilities();
			// Update option for pdf voucher version
			update_option( 'woo_vou_plugin_version', WOO_VOU_PLUGIN_VERSION );
			// Redirect to admin page
			wp_redirect ( admin_url( '?vou_success=true' ) );
		}
	}

	/**
	 * Create vendor role 
	 * Add capatibilities to vendor and admin role
	 * 
	 * @package WooCommerce - PDF Vouchers
	 * @since 3.7.10
	 */
	public function woo_vou_create_roles() {
		
		if( version_compare( get_option( 'woo_vou_plugin_version' ), '3.7.10', '<' ) ) {

			global $wp_roles;

			if ( ! class_exists( 'WP_Roles' ) ) {
				return;
			}

			if ( ! isset( $wp_roles ) ) {
				$wp_roles = new WP_Roles(); // @codingStandardsIgnoreLine
			}
			
			//get vendor role
			$vendor_role = get_role( WOO_VOU_VENDOR_ROLE );
			if( empty( $vendor_role ) ) { //check vendor role
				
				$capabilities = array(
					WOO_VOU_VENDOR_LEVEL	=> true,  // true allows add vendor level
					'read' 					=> true
				);
				add_role( WOO_VOU_VENDOR_ROLE,esc_html__( 'Voucher Vendor', 'woovoucher' ), $capabilities );

				$vendor_role = get_role( WOO_VOU_VENDOR_ROLE );
				$vendor_role->add_cap( WOO_VOU_VENDOR_LEVEL );
			} else {
				
				$vendor_role->add_cap( WOO_VOU_VENDOR_LEVEL );
			}
			
			$role = get_role( 'administrator' );
			$role->add_cap( WOO_VOU_VENDOR_LEVEL );

			// update the plugin to current version
			update_option( 'woo_vou_plugin_version', WOO_VOU_PLUGIN_VERSION );
		}
	}

	/**
	 * Migrate vouchers based on purchase
	 * 
	 * @package WooCommerce - PDF Vouchers
	 * @since 4.3.0
	 */
	public function woo_vou_migrate_voucher_based_purchase_database() {

		global $woo_vou_model;

		$prefix = WOO_VOU_META_PREFIX;

		$vouargs = array(
			'post_type' => WOO_VOU_CODE_POST_TYPE, 
			'post_status' => 'publish',
			'posts_per_page' => '10',
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'relation' => 'OR',
					array(
					'key' => $prefix.'start_date',
					'value' => '',
					'compare' => '='
					),
					array(
					'key' => $prefix.'start_date',
					'compare' => 'NOT EXISTS'
					),
				),
				array(
					'key' => $prefix.'migrated_startdate',
					'compare' => 'NOT EXISTS'
				)
			)
		);

		$results = new WP_Query($vouargs);
		
		// Get vouchers
		$vouchers = isset( $results->posts ) ? $results->posts : '';

		if( !empty( $vouchers ) ) {
			foreach ( $vouchers as $voucher) {
				$voucher_id = $voucher->ID;
				$order_id = $voucher->post_title;

				$order_meta_details_arr = get_post_meta($order_id, $prefix.'meta_order_details',true);
				if( !empty( $order_meta_details_arr ) ){

					foreach ( $order_meta_details_arr as $key => $order_meta_details ) {

						if( isset( $order_meta_details['exp_type'] ) && $order_meta_details['exp_type'] == 'based_on_purchase' ) {

							$order = wc_get_order($order_id);

							if( !empty( $order ) ) {

								$order_date = $woo_vou_model->woo_vou_get_order_date_from_order($order);
								update_post_meta( $voucher_id, $prefix.'start_date', date('Y-m-d H:i:s', strtotime($order_date) ) );
							}
						}
						elseif( isset( $order_meta_details['exp_type'] ) && $order_meta_details['exp_type'] == 'based_on_gift_date' ) {
							$order = wc_get_order($order_id);

							if( !empty( $order ) ) {
								$items 			= $order->get_items();
								foreach ($items as $item_id => $product_data) {
									
									$recipient_giftdate = wc_get_order_item_meta($item_id, $prefix . 'recipient_giftdate');

									if( empty( $recipient_giftdate ) ){
										$recipient_giftdate = $woo_vou_model->woo_vou_get_order_date_from_order($order);
									}

									update_post_meta( $voucher_id, $prefix.'start_date', date('Y-m-d H:i:s', strtotime($recipient_giftdate) ) );
								}
							}
						}
					}
				}
				update_post_meta( $voucher_id, $prefix.'migrated_startdate', '1' );
			}
			echo 'process';
			exit;
		} else{ // no voucher found to update start date
			update_option( 'woo_vou_voucher_order_start_date_migration', '1' );
			echo 'completed';
			exit;
		}
	}

	/**
	 * Admin notice to display
	 * Migrate vouchers to the database
	 * 
	 * @package WooCommerce - PDF Vouchers
	 * @since 4.3.0
	 */
	public function woo_vou_admin_notice_for_migrate_voucher_database() {

        $woo_vou_voucher_order_start_date_migration = get_option( 'woo_vou_voucher_order_start_date_migration' );
        $woo_vou_plugin_version = get_option( 'woo_vou_plugin_version' );

        if ( empty( $woo_vou_voucher_order_start_date_migration ) && !isset( $_GET['woo-vou-upgrade'] ) && version_compare( $woo_vou_plugin_version, '4.1.0', '<=' ) ) {
            printf(
                '<div class="updated"><p>' . esc_html__( 'WooCommerce Pdf Voucher needs to upgrade the vouchers database, click %shere%s to start the upgrade.', 'woovoucher' ) . '</p></div>',
                '<a href="' . esc_url( admin_url( 'index.php?page=woo-vou-upgrades-voucher&woo-vou-upgrade=upgrade_voucher_db' ) ) . '">',
                '</a>'
            );
        }

        // Success message when capabilities get successfully upgraded
		if ( isset( $_GET['woo-vou-upgrades-db-voucher'] ) && $_GET['woo-vou-upgrades-db-voucher'] == 'success' ) {

			// Success Message
			printf('<div class="updated"><p>' . esc_html__( 'WooCommerce Pdf Voucher - Voucher Database have been upgraded successfully.', 'woovoucher' ) . '</p></div>', 'woovoucher');
		}

		// Success message when capabilities get successfully upgraded
		if ( isset( $_GET['woo-vou-upgrades-db-voucher-cleanup'] ) && $_GET['woo-vou-upgrades-db-voucher-cleanup'] == 'success' ) {

			// Success Message
			printf('<div class="updated"><p>' . esc_html__( 'WooCommerce Pdf Voucher Database update complete. Thank you for updating to the latest version.', 'woovoucher' ) . '</p></div>', 'woovoucher');
		}
    }


	/**
	 * Notice for remove unnecessary meta data from voucher products
	 * 
	 * @package WooCommerce - PDF Vouchers
	 * @since 4.3.0
	 */
    public function woo_vou_admin_notice_for_clean_voucher_product_meta() {

    	$woo_vou_voucher_cleanup_migration = get_option( 'woo_vou_voucher_cleanup_migration' );
        $woo_vou_plugin_version = get_option( 'woo_vou_plugin_version' );

        if ( empty( $woo_vou_voucher_cleanup_migration ) && !isset( $_GET['woo-vou-upgrade'] ) && version_compare( $woo_vou_plugin_version, '4.2.9', '<=' ) ) {

            printf(
                '<div class="updated"><p>' . esc_html__( 'WooCommerce Pdf Voucher needs to cleanup unnecessary vouchers product data from the database, click %shere%s to start the cleanup.', 'woovoucher' ) . '</p></div>',
                '<a href="' . esc_url( admin_url( 'index.php?page=woo-vou-upgrades-voucher&woo-vou-upgrade=cleanup_voucher_db' ) ) . '">',
                '</a>'
            );
        }

        // Success message when capabilities get successfully upgraded
		if ( isset( $_GET['woo-vou-cleanup-db-voucher'] ) && $_GET['woo-vou-cleanup-db-voucher'] == 'success' ) {

			// Success Message
			printf('<div class="updated"><p>' . esc_html__( 'WooCommerce Pdf Voucher - Voucher Database cleanup have been successfully completed.', 'woovoucher' ) . '</p></div>', 'woovoucher');
		}

    }

    /**
     * Migrate voucher redeem meta,
     * Admin notice
     * 
     * @package WooCommerce - PDF Vouchers
	 * @since 4.3.0
     */
    public function woo_vou_admin_notice_for_migrate_voucher_redeem_meta() {

    	global $wp;

    	$woo_vou_voucher_redeem_on_migration = get_option( 'woo_vou_voucher_redeem_on_migration' );
        $woo_vou_plugin_version = get_option( 'woo_vou_plugin_version' );

        if ( empty( $woo_vou_voucher_redeem_on_migration ) && !isset( $_GET['woo-vou-upgrade'] ) && version_compare( $woo_vou_plugin_version, '4.3.0', '<=' ) ) {
 
			$obj_id = get_queried_object_id();
        	$URL = add_query_arg( array(
        		'page' => 'woo-vou-upgrades-voucher',
        		'woo-vou-upgrade' => 'migrate-voucher-data',
        		'return' => base64_encode( add_query_arg( NULL, NULL ) ),
        	), admin_url('index.php') );

            printf(
                '<div class="updated"><p>' . esc_html__( 'WooCommerce Pdf Voucher needs to migrate redeem vouchers data from the database, click %shere%s to start the migration.', 'woovoucher' ) . '</p></div>',
                '<a href="' . esc_url( $URL ) . '">',
                '</a>'
            );
        }

        // Success message when capabilities get successfully upgraded
		if ( isset( $_GET['woo-vou-migrate-voucher-data'] ) && $_GET['woo-vou-migrate-voucher-data'] == 'success' ) {

			// Success Message
			printf('<div class="updated"><p>' . esc_html__( 'WooCommerce Pdf Voucher - Voucher redeem data has been migrated.', 'woovoucher' ) . '</p></div>', 'woovoucher');
		}
    }

    /**
	 * Adding Upgrade Submenu for voucher upgrader Page
	 * 
	 * 
	 * @package WooCommerce - PDF Vouchers
	 * @since 2.3.0
	 */
	public function woo_vou_upgrade_voucher_data_submenu() {

		//add page for upgrates 
		$vou_upgrades_screen = add_submenu_page( null, esc_html__( 'PDF Voucher Upgrades', 'woovoucher' ), esc_html__( 'PDF Voucher Upgrades', 'woovoucher' ), 'install_plugins', 'woo-vou-upgrades-voucher', array( $this, 'woo_vou_upgrades_voucher_screen' ) );
	}

	public function woo_vou_upgrades_voucher_screen(){
		?>
		<div class="wrap">
			<h2><?php echo __( 'PDF Vouchers - Upgrades', 'woovoucher' ); ?></h2>
			<div id="vou-upgrade-status">
				<p>
					<?php echo esc_html__( 'The upgrade process has started, please be patient. This could take several minutes. You will be automatically redirected when the upgrade is finished.', 'woovoucher' ); ?>
					<img src="<?php echo esc_url(WOO_VOU_URL) . '/includes/images/ajax-loader.gif'; ?>" id="vou-upgrade-loader"/>
				</p>

				<?php
				$return = !empty( $_GET['return'] ) ? $_GET['return'] : ''; ?>
				<input type="hidden" name="" class="woo-vou-upgrade-return" value="<?php echo base64_decode( $return ); ?>" />
			</div>
		</div><?php
	}

	/**
	 * Handle ajax call for remove unnecessary meta data from voucher products
	 * 
	 * @package WooCommerce - PDF Vouchers
	 * @since 4.3.0
	 * 
	 */
	public function woo_vou_cleanup_product_meta(){

		global $woo_vou_model;
		
		$response = array();

		$new_product_ids = array();
		if(isset($_POST['product_ids']) && !empty($_POST['product_ids']) ){
			$new_product_ids = $_POST['product_ids'];
		}

		$prefix = WOO_VOU_META_PREFIX;

		$productargs = array(
			'post_type' 		=> 'product', 
			'post_status' 		=> 'publish',
			'posts_per_page' 	=> '5'
		);

		if(!empty($new_product_ids)){
			$productargs['post__not_in'] = $new_product_ids;			
		}
		
		$results = new WP_Query($productargs);	
		
		// Get vouchers
		$products = isset( $results->posts ) ? $results->posts : '';

		if( !empty( $products ) ){
			
			foreach ( $products as $product) {

				$post_id = $product->ID;

				$is_downloadable = get_post_meta($post_id, '_downloadable', true);

				$recipient_details = woo_vou_voucher_recipient_details();
				

				$meta_keys  = array(
					'enable',
					'enable_recipient_delivery_method',
					'recipient_delivery',
					'recipient_delivery_label',
					'recipient_detail_order',
					'enable_pdf_template_selection',
					'pdf_template_selection_label',
					'pdf_template_selection_is_required',
					'pdf_template_selection',
					'pdf_selection_desc',
					'enable_pdf_preview',
					'enable_coupon_code',
					'enable_multiple_pdf',
					'disable_redeem_day',
					'voucher_price',
					'pdf_template',
					'vendor_user',
					'voucher_uses_limit',
					'voucher_delivery',
					'sec_vendor_users',
					'using_type',
					'exp_type',
					'days_diff',
					'custom_days',
					'product_start_date',
					'product_exp_date',
					'coupon_products',
					'coupon_exclude_products',
					'coupon_categories',
					'coupon_exclude_categories',
					'discount_on_tax_type',
					'coupon_minimum_spend_amount',
					'coupon_maximum_spend_amount',
					'start_date',
					'exp_date',
					'codes',
					'logo',
					'address_phone',
					'website',
					'how_to_use',
					'is_variable_voucher',
					'avail_locations',
    			);

				// Looping on recipient columns
				foreach( $recipient_details as $recipient_key => $recipient_val ) {
					
					if( empty( get_post_meta( $post_id, $prefix.'enable_'.$recipient_key,true) ) ){
						delete_post_meta( $post_id, $prefix.'enable_'.$recipient_key );
					}
					if( empty( get_post_meta( $post_id, $prefix.$recipient_key.'_max_length',true) ) ){
						delete_post_meta( $post_id, $prefix.$recipient_key.'_max_length' );
					}
					if( empty( get_post_meta( $post_id, $prefix.$recipient_key.'_label',true) ) ){
						delete_post_meta( $post_id, $prefix.$recipient_key.'_label' );
					}

					if( empty( get_post_meta( $post_id, $prefix.$recipient_key.'_is_required',true) ) ){
						delete_post_meta( $post_id, $prefix.$recipient_key.'_is_required' );
					}
					if( empty( get_post_meta( $post_id, $prefix.$recipient_key.'_desc',true) ) ){
						delete_post_meta( $post_id, $prefix.$recipient_key.'_desc');
					}
				}    				  

				foreach ($meta_keys as  $meta_key) {    					
					
					$meta_value = get_post_meta($post_id, $prefix.$meta_key ,true);

					if( empty( $meta_value ) && $meta_value == '' ){
						delete_post_meta( $post_id , $prefix.$meta_key );    						
					}
				}											

				$proccessed_product_ids[] = $product->ID;
			}

			$response['status'] = 'process';
			$response['proccessed_product_ids'] = array_merge($new_product_ids,$proccessed_product_ids);
		}
		else{ // no product found
			update_option( 'woo_vou_voucher_cleanup_migration', '1' );			
			$response['status'] = 'completed';
		}		
		
		wp_send_json( $response);
		die();
	}
	
	/**
	 * Adding Hooks
	 *
	 * @package WooCommerce - PDF Vouchers
	 * @since 1.0.0
	 */
	public function add_hooks() {
		
		//add submenu page
		add_action( 'admin_menu', array( $this, 'woo_vou_upgrade_submenu' ) );
		
		//Woocomerce PDF voucher updater script
		add_action( 'admin_notices', array( $this, 'woo_vou_show_upgrade_notices' ) );
		add_action( 'wp_ajax_woo_vou_trigger_upgrades', array( $this, 'woo_vou_trigger_upgrades' ) );
		
		// Add action to add capability to vendor
		add_action( 'init', array( $this, 'woo_vou_add_capability_to_vendor' ) );

		// Add action to create voucher vendor role and add capatibilities
		add_action( 'init', array( $this, 'woo_vou_create_roles' ) );

		// Add action to add voucher start date meta to the for exp type = based on purchase
		add_action( 'wp_ajax_woo_vou_migrate_voucher_based_purchase_database', array( $this, 'woo_vou_migrate_voucher_based_purchase_database' ) );

		// Add action to cleanup product meta
		add_action( 'wp_ajax_woo_vou_cleanup_product_meta', array($this, 'woo_vou_cleanup_product_meta') );

		// Add action to migrate vouchers with redeem information
		add_action( 'wp_ajax_woo_vou_migrate_voucher_redeem_data', array($this, 'woo_vou_admin_run_v430_udater_script') );

		// Admin notice to migrate voucher database
		add_action( 'admin_notices', array( $this, 'woo_vou_admin_notice_for_migrate_voucher_database') );

		// Add notice to remove unnecessary data from voucher products
		add_action( 'admin_notices', array( $this, 'woo_vou_admin_notice_for_clean_voucher_product_meta') );

		// Display admin notice for migrate voucher data with redeem information
		add_action('admin_notices', array( $this, 'woo_vou_admin_notice_for_migrate_voucher_redeem_meta') );

		//add submenu page for voucher data upgrade
		add_action( 'admin_menu', array( $this, 'woo_vou_upgrade_voucher_data_submenu' ) );
		
	}
}