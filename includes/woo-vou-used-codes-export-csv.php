<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Export to CSV for Voucher
 * 
 * Handles to Export to CSV on run time when 
 * user will execute the url which is sent to
 * user email with purchase receipt
 * 
 * @package WooCommerce - PDF Vouchers
 * @since 1.0.0
 */

function woo_vou_code_export_to_csv(){
	
	// Get prefix
	$prefix = WOO_VOU_META_PREFIX;	
	
	if( !empty( $_GET['woo-vou-used-exp-csv'] ) && $_GET['woo-vou-used-exp-csv'] == '1'
		&& !empty($_GET['product_id'] )  && current_user_can ( 'publish_products' ) ) {
		
		global $current_user,$woo_vou_model, $woo_vou_voucher, $post;
		
		//model class
		$model = $woo_vou_model;
	
		$postid = $_GET['product_id']; 
		
		$exports = '';

		$columns = array(
			esc_html__( 'Voucher Code', 'woovoucher' ),
			esc_html__( 'Buyer\'s Information', 'woovoucher' ),
			esc_html__( 'Order Information', 'woovoucher' ),
			esc_html__( 'Voucher Information', 'woovoucher' ),	
			esc_html__( 'Recipient Information', 'woovoucher' ),
		);



		// Get current action
		$action = isset( $_GET['woo_vou_action'] ) ? isset( $_GET['woo_vou_action'] ) : '';
		
		// Check action is used codes
		if( $action == 'used' ) {
		
		 	//Get Voucher Details by post id
		 	$voucodes = woo_vou_get_used_codes_by_product_id( $postid );
		 	
			$vou_file_name = 'woo-used-voucher-codes-{current_date}';
			
		} elseif ( $action == 'expired' ) {
            
            //Get Unused Voucher Details by post id
            $voucodes = woo_vou_get_unused_codes_by_product_id( $postid );
            $vou_file_name = 'woo-unused-voucher-codes-{current_date}';
        } else {
			
		 	//Get Voucher Details by post id
		 	$voucodes = woo_vou_get_purchased_codes_by_product_id( $postid );
		 	
			$vou_csv_name = get_option( 'vou_csv_name' );
			$vou_file_name = !empty( $vou_csv_name ) ? $vou_csv_name : 'woo-purchased-voucher-codes-{current_date}';
		}
					     
		if( isset( $_GET['woo_vou_action'] ) && $_GET['woo_vou_action'] == 'used' ) {
			$new_columns	= array( esc_html__('Redeem Information', 'woovoucher' ) );
			$columns 		= array_merge ( $columns , $new_columns );	
		}

        // Put the name of all fields
		foreach ($columns as $column) {
			$exports .= '"'.$column.'",';
		}
		$exports .="\n";

		if( !empty( $voucodes ) &&  count( $voucodes ) > 0 ) { 

			foreach ( $voucodes as $key => $voucodes_data ) { 
                
                $voucher_codes = explode(',', $voucodes_data['vou_codes'] );
                foreach ( $voucher_codes as $voucher_code ) { 
                                    
                    //voucher order id
                    $orderid 		= $voucodes_data['order_id'];

                    //voucher code purchased/used
                    $voucode 		= $voucher_code;;

                    // Get order information 
                    $order_info = woo_vou_display_order_info_html( $orderid, 'csv' );

                    // Get Recipient information 
                    $recipient_info = woo_vou_display_recipient_info_html( $orderid, $voucode, 'csv' );

                    // Get Voucher information 
                    $voucher_info = woo_vou_display_voucher_info_html( $orderid, $voucodes_data['voucode_id'], $voucode, 'csv' );

                    // Get Buyer information 
                    $buyer_details		 = $model->woo_vou_get_buyer_information( $orderid );
                    $buyer_details_html  = 'Name: '.$buyer_details['first_name'].' '.$buyer_details['last_name']."\n";
                    $buyer_details_html .= 'Email: '.$buyer_details['email']."\n";
                    $buyer_details_html .= 'Address: '.$buyer_details['address_1'].' '.$buyer_details['address_2']."\n";
                    $buyer_details_html .= $buyer_details['city'].' '.$buyer_details['state'].' '.$buyer_details['country'].' - '.$buyer_details['postcode']."\n";
                    $buyer_details_html .= 'Phone: '.$buyer_details['phone'];

                    $buyer_details_html = apply_filters('woo_vou_csv_buyer_info',$buyer_details_html,$buyer_details);


                    $buyerinfo = $buyer_details_html;	


                    //this line should be on start of loop
                    $exports .= '"'.html_entity_decode($voucode).'",';
                    $exports .= '"'.$buyerinfo.'",';
                    $exports .= '"'.$order_info.'",';
                    $exports .= '"'.$voucher_info.'",';
                    $exports .= '"'.$recipient_info.'",';

                    if( isset( $_GET['woo_vou_action'] ) && $_GET['woo_vou_action'] == 'used' ) {

                        // get voucher Redeem by information 
                        $redeeminfo = woo_vou_display_redeem_info_html( $voucodes_data['voucode_id'], $orderid, 'csv' );
                        $exports .= '"'.$redeeminfo.'",';
                    }
                    ob_start();

                    $added_column = ob_get_clean();

                    $exports .= $added_column;

                    $exports .="\n";
                }
			}
		}

		// Apply filter to allow 3rd party people to change it
		$date_format = apply_filters( 'woo_vou_voucher_date_format', 'Y-m-d' );

		$vou_file_name = str_replace( '{current_date}', date('Y-m-d'), $vou_file_name );
		
		// Output to browser with appropriate mime type, you choose ;)
		header("Content-type: text/x-csv");
		header("Content-Disposition: attachment; filename=".$vou_file_name.".csv");
		echo $exports;
		exit;
		
	}
	
	// generate csv for voucher code
	if( !empty( $_GET['woo-vou-voucher-exp-csv'] ) && $_GET['woo-vou-voucher-exp-csv'] == '1' )  {

		global $current_user,$woo_vou_model, $woo_vou_voucher, $post, $woo_vou_vendor_role;

		// Taking parameter
		$orderby 	= isset( $_GET['orderby'] ) ? urldecode( $_GET['orderby'] ) : 'ID';
		$order		= isset( $_GET['order'] ) ? $_GET['order'] : 'DESC';

		$args = array(
			'orderby'	=> $orderby,
			'order'		=> $order,
		);

		$admin_roles	= woo_vou_assigned_admin_roles();
		
		//Get user role
		$user_roles	= isset( $current_user->roles ) ? $current_user->roles : array();
		$user_role	= array_shift( $user_roles );

		if ( empty ( $current_user->ID ) || empty ( $current_user->roles ) ) {
		    return;
		}
		
		//model class
		$model = $woo_vou_model;

		// Get option whether to allow all vendor to redeem voucher codes
	    $vou_enable_vendor_access_all_voucodes = get_option('vou_enable_vendor_access_all_voucodes');
	
		$exports = '';
		
		// Check action is used codes
		if( isset( $_GET['woo_vou_action'] ) && $_GET['woo_vou_action'] == 'used' ) {
		
			$args['meta_query'] = array( array(
				'key'		=> $prefix.'used_codes',
				'value'		=> '',
				'compare'	=> '!=',
			) );

			//Get user role
			$user_roles	= isset( $current_user->roles ) ? $current_user->roles : array();
			$user_role	= array_shift( $user_roles );
			
			//voucher admin roles
			$admin_roles	= woo_vou_assigned_admin_roles();

			if( in_array( $user_role, $woo_vou_vendor_role ) 
				&& ( empty($vou_enable_vendor_access_all_voucodes) || $vou_enable_vendor_access_all_voucodes == 'no' ) ) {// voucher admin can redeem all codes

				$args['author'] = $current_user->ID;

			} elseif( !in_array( $user_role, $woo_vou_vendor_role ) && !in_array( $user_role, $admin_roles ) ) {
		
				$args['meta_query'] =	array(
					'relation' => 'AND',
					( $args['meta_query'] ),
					array(
						array(
							'key'		=> $prefix.'customer_user',
							'value'		=> $current_user->ID,
							'compare'	=> '=',
						)
					)
				);
			}

			if( isset( $_GET['woo_vou_post_id'] ) && !empty( $_GET['woo_vou_post_id'] ) ) {
				$args['post_parent'] = $_GET['woo_vou_post_id'];
			}
			
			if( isset( $_GET['woo_vou_user_id'] ) && !empty( $_GET['woo_vou_user_id'] ) ) {
				
				$args['meta_query'] =	array(
					'relation' => 'AND',
					($args['meta_query']),
					array(
						array(
							'key'		=> $prefix.'redeem_by',
							'value'		=> $_GET['woo_vou_user_id'],
							'compare'	=> '=',
						)
					)
				);
			}
			
			if( isset( $_GET['woo_vou_start_date'] ) && !empty( $_GET['woo_vou_start_date'] ) ) {
				
				$args['meta_query'] =	array(
					'relation' => 'AND',
					($args['meta_query']),
					array(
						array(
							'key'		=> $prefix.'used_code_date',
							'value'		=> date( "Y-m-d H:i:s", strtotime( $_GET['woo_vou_start_date'] ) ),
							'compare'	=> '>=',
						)
					)
				);
			}
			
			if( isset( $_GET['woo_vou_end_date'] ) && !empty( $_GET['woo_vou_end_date'] ) ) {
				
				$args['meta_query'] =	array(
					'relation' => 'AND',
					($args['meta_query']),
					array(
						array(
							'key'		=> $prefix.'used_code_date',
							'value'		=> date( "Y-m-d H:i:s", strtotime( $_GET['woo_vou_end_date'] ) ),
							'compare'	=> '<=',
						)
					)
				);
			}

			// If Partially Used checkbox is ticked than only show voucher codes which are used partially
			if( !empty( $_GET['woo_vou_partial_used_voucode'] ) && $_GET['woo_vou_partial_used_voucode'] == 'yes' ){

				// Search for code having meta key _woo_vou_redeem_method and meta value partial
				$args['meta_query'] = array_merge(array( array( 
					'key'	=> $prefix . 'redeem_method',
					'value'	=> 'partial',
				) ), $args['meta_query']);
			}

			if( isset( $_GET['s'] ) && !empty( $_GET['s'] ) ) {
				
				//$args['s'] = $_GET['s'];
				$args['meta_query'] = array(
					'relation' => 'AND',
					($args['meta_query']),
					array(
						'relation'	=> 'OR',
						array(
							'key'		=> $prefix.'used_codes',
							'value'		=> $_GET['s'],
							'compare'	=> 'LIKE',
						),
						array(
							'key'		=> $prefix.'first_name',
							'value'		=> $_GET['s'],
							'compare'	=> 'LIKE',
						),
						array(
							'key'		=> $prefix.'last_name',
							'value'		=> $_GET['s'],
							'compare'	=> 'LIKE',
						),
						array(
							'key'		=> $prefix.'order_id',
							'value'		=> $_GET['s'],
							'compare'	=> 'LIKE',
						),
						array(
							'key'		=> $prefix.'order_date',
							'value'		=> $_GET['s'],
							'compare'	=> 'LIKE',
						),
					)
				);
			}

			$args = apply_filters('woo_vou_used_codes_gen_csv', $args);

		 	//Get Voucher Details by post id
		 	$voucodes = woo_vou_get_voucher_details( $args );
		 	
		 	$vou_file_name = 'woo-used-voucher-codes-{current_date}';
			
		} else { // Get expired codes
		 	
		 	if( isset( $_GET['vou-data'] ) && $_GET['vou-data'] == 'expired') {
		 		
	 			$args['meta_query'] = array(
					array(
						'key'		=> $prefix . 'purchased_codes',
						'value'		=> '',
						'compare'	=> '!='
					),
					array(
						'key'		=> $prefix . 'used_codes',
						'compare'	=> 'NOT EXISTS'
					 ),
					array(
						'key' =>  $prefix .'exp_date',
						'compare'	=> '<=',
							'type'	=> 'DATE',
							'value'	=> $model->woo_vou_current_date()
					)
				);
									
				$vou_file_name = 'woo-expired-voucher-codes-{current_date}';
		 		
		 	} else {
		 		
				$args['meta_query'] = array(
						array(
							'key' 		=> $prefix . 'purchased_codes',
							'value'		=> '',
							'compare' 	=> '!='
						),
					array(
							'key'		=> $prefix . 'used_codes',
							'compare' 	=> 'NOT EXISTS'
						 ),
					array(
						'relation' => 'OR', // Optional, defaults to "AND"
						array(
							'key'     => $prefix .'exp_date',
							'value'   => '',
							'compare' => '='
						),
						array(
							'key' =>  $prefix .'exp_date',
							'compare' => '>=',
  							'type'    => 'DATE',
  							'value' => $model->woo_vou_current_date()
						)
					)
				);
										
				$vou_csv_name  = get_option( 'vou_csv_name' );
				$vou_file_name = !empty( $vou_csv_name )? $vou_csv_name : 'woo-purchased-voucher-codes-{current_date}';
		 	}
			//Get user role
			$user_roles	= isset( $current_user->roles ) ? $current_user->roles : array();
			$user_role	= array_shift( $user_roles );

			//voucher admin roles
			$admin_roles	= woo_vou_assigned_admin_roles();
	
			if( in_array( $user_role, $woo_vou_vendor_role ) && 
				( empty($vou_enable_vendor_access_all_voucodes) || $vou_enable_vendor_access_all_voucodes == 'no' ) ) {// voucher admin can redeem all codes

				$args['author'] = $current_user->ID;

			} elseif( !in_array( $user_role, $woo_vou_vendor_role ) && !in_array( $user_role, $admin_roles ) ) {
		
				$args['meta_query'] =	array(
					'relation' => 'AND',
					($args['meta_query']),
					array(
						array(
							'key'		=> $prefix.'customer_user',
							'value'		=> $current_user->ID,
							'compare'	=> '=',
						)
					)
				);
			}

			if( isset( $_GET['woo_vou_post_id'] ) && !empty( $_GET['woo_vou_post_id'] ) ) {
				$args['post_parent'] = $_GET['woo_vou_post_id'];
			}
			
			if( isset( $_GET['s'] ) && !empty( $_GET['s'] ) ) {
				
				$args['meta_query'] = 
					array(
					'relation' => 'AND',
					($args['meta_query']),
				array(
					'relation'	=> 'OR',
						array(
							'key'		=> $prefix.'purchased_codes',
							'value'		=> $_GET['s'],
							'compare'	=> 'LIKE',
						),
						array(
							'key'		=> $prefix.'first_name',
							'value'		=> $_GET['s'],
							'compare'	=> 'LIKE',
						),
						array(
							'key'		=> $prefix.'last_name',
							'value'		=> $_GET['s'],
							'compare'	=> 'LIKE',
						),
						array(
							'key'		=> $prefix.'order_id',
							'value'		=> $_GET['s'],
							'compare'	=> 'LIKE',
						),
						array(
							'key'		=> $prefix.'order_date',
							'value'		=> $_GET['s'],
							'compare'	=> 'LIKE',
						),
					)
				);
			}

			if( isset( $_GET['woo_vou_start_date'] ) && !empty( $_GET['woo_vou_start_date'] ) ) {

				$args['date_query'][] = array(
					'column' => 'post_date',
					'after'  => $_GET['woo_vou_start_date'],
				);
			}

			if( isset( $_GET['woo_vou_end_date'] ) && !empty( $_GET['woo_vou_end_date'] ) ) {

				$args['date_query'][] = array(
					'column' => 'post_date',
					'before'  => $_GET['woo_vou_end_date'],
				);
			}

			// If Partially Used checkbox is ticked than only show voucher codes which are used partially
			if( !empty( $_GET['woo_vou_partial_used_voucode'] ) && $_GET['woo_vou_partial_used_voucode'] == 'yes' ){

				// Search for code having meta key _woo_vou_redeem_method and meta value partial
				$args['meta_query'] = array_merge(array( array( 
					'key' 		=> $prefix . 'redeem_method',
					'value'		=> 'partial',
				) ), $args['meta_query']);
			}

			$args = apply_filters('woo_vou_expired_codes_gen_csv', $args);

		 	//Get Voucher Details by post id
		 	$voucodes = woo_vou_get_voucher_details( $args );
		}
		$columns = array(	
			esc_html__( 'Voucher Code', 'woovoucher' ),
			esc_html__( 'Product Information', 'woovoucher' ),
			esc_html__( 'Buyer\'s Information', 'woovoucher' ),
			esc_html__( 'Order Information', 'woovoucher' ),
			esc_html__( 'Voucher Information', 'woovoucher' ),	
			esc_html__( 'Recipient Information', 'woovoucher' ),
	     );
					     
		if( isset( $_GET['woo_vou_action'] ) && ( $_GET['woo_vou_action'] == 'used' || $_GET['woo_vou_action'] == 'partially' ) ) {
			
			$new_columns	= array(
				esc_html__( 'Redeem Information', 'woovoucher' ),
				esc_html__( 'Associates Products', 'woovoucher' ),
				esc_html__( 'Associates Product IDs', 'woovoucher' ),
			);
			$columns 		= array_merge ( $columns , $new_columns );
		}	
		
		$csv_type	= isset( $_GET['woo_vou_action'] ) ? $_GET['woo_vou_action'] : 'purchased';
		$columns	= apply_filters( 'woo_vou_generate_csv_columns', $columns, $csv_type );
		
        // Put the name of all fields
		foreach ($columns as $column) {
			$exports .= '"'.$column.'",';
		}
		$exports .="\n";
		
		if( !empty( $voucodes ) &&  count( $voucodes ) > 0 ) { 
												
			foreach ( $voucodes as $key => $voucodes_data ) {

				if(isset( $_GET['woo_vou_action'] ) && $_GET['woo_vou_action'] == 'partially') {
					//voucher order id
					$orderid = get_post_meta( $voucodes[$key]['post_parent'], $prefix.'order_id', true );
				} else{
					//voucher order id
					$orderid = get_post_meta( $voucodes_data['ID'], $prefix.'order_id', true );
				}

				// get order detail
	            $order = wc_get_order($orderid);
	            if (empty($order)) {
	            	continue;
	            }

				if(isset( $_GET['woo_vou_action'] ) && $_GET['woo_vou_action'] == 'partially') { 
					
					//voucher order date
					$orderdate 		= get_post_meta( $voucodes[$key]['post_parent'], $prefix.'order_date', true );
					$orderdate 		= !empty( $orderdate ) ? $model->woo_vou_get_date_format( $orderdate, true ) : '';
					
					//voucher code purchased/used
					$voucode 		= get_post_meta( $voucodes[$key]['post_parent'], $prefix.'purchased_codes', true );
					
					// Get user details
					$user_id 	 	= get_post_meta( $voucodes_data['ID'], $prefix.'redeem_by', true );
					$user_detail 	= get_userdata( $user_id );
					$redeem_by 		= isset( $user_detail->display_name ) ? $user_detail->display_name : 'N/A';
					
					if ( $user_id == '0' ) {
						$redeem_by = esc_html__( 'Guest User', 'woovoucher' );
					}
					
					// Get redeem Date
					$redeem_date	= get_post_meta( $voucodes_data['ID'], $prefix.'used_code_date', true );
					$redeem_date 	= !empty( $redeem_date ) ? $model->woo_vou_get_date_format( $redeem_date, true ) : '';
					
					// Get Redeem by information 
					$redeeminfo 	=	woo_vou_display_redeem_info_html( $voucodes_data['ID'], $orderid ,'csv', 'partially' ); 
					$redeeminfo = strip_tags( $redeeminfo);	
					// Get buyer information 
					$buyer_details		 = $model->woo_vou_get_buyer_information( $orderid );
					$buyer_details_html  = 'Name: '.$buyer_details['first_name'].' '.$buyer_details['last_name']."\n";
					$buyer_details_html .= 'Email: '.$buyer_details['email']."\n";
					$buyer_details_html .= 'Address: '.$buyer_details['address_1'].' '.$buyer_details['address_2']."\n";
					$buyer_details_html .= $buyer_details['city'].' '.$buyer_details['state'].' '.$buyer_details['country'].' - '.$buyer_details['postcode']."\n";
					$buyer_details_html .= 'Phone: '.$buyer_details['phone'];


					$buyer_details_html = apply_filters('woo_vou_csv_buyer_info',$buyer_details_html,$buyer_details);
				
					$buyerinfo = $buyer_details_html;			
				
				} else {
					
					//voucher order date
					$orderdate 		= get_post_meta( $voucodes_data['ID'], $prefix.'order_date', true );
					$orderdate 		= !empty( $orderdate ) ? $model->woo_vou_get_date_format( $orderdate, true ) : '';
					
					//voucher code purchased/used
					$voucode 		= get_post_meta( $voucodes_data['ID'], $prefix.'purchased_codes', true );
					
					// Get user information 
					$user_id 	 	= get_post_meta( $voucodes_data['ID'], $prefix.'redeem_by', true );
					$user_detail 	= get_userdata( $user_id );
					$redeem_by 		= isset( $user_detail->display_name ) ? $user_detail->display_name : 'N/A';
					
					// Get voucher redeem date
					$redeem_date	= get_post_meta( $voucodes_data['ID'], $prefix.'used_code_date', true );
					$redeem_date 	= !empty( $redeem_date ) ? $model->woo_vou_get_date_format( $redeem_date, true ) : '';
					
					// get voucher Redeem by information 
					$redeeminfo = woo_vou_display_redeem_info_html( $voucodes_data['ID'], $orderid, 'csv' );
					
					// Get Buyer information 
					$buyer_details		 = $model->woo_vou_get_buyer_information( $orderid );
					$buyer_details_html  = 'Name: '.$buyer_details['first_name'].' '.$buyer_details['last_name']."\n";
					$buyer_details_html .= 'Email: '.$buyer_details['email']."\n";
					$buyer_details_html .= 'Address: '.$buyer_details['address_1'].' '.$buyer_details['address_2']."\n";
					$buyer_details_html .= $buyer_details['city'].' '.$buyer_details['state'].' '.$buyer_details['country'].' - '.$buyer_details['postcode']."\n";
					$buyer_details_html .= 'Phone: '.$buyer_details['phone'];

					$buyer_details_html = apply_filters('woo_vou_csv_buyer_info',$buyer_details_html,$buyer_details);
					
					$buyerinfo = $buyer_details_html;			
				}

				// Get associates ids
				$redeem_on	= get_post_meta( $voucodes_data['ID'], $prefix . 'redeemed_on', true );

				$redeemOnProds = $redeemOnProdsIds = array();
				if ( !empty($redeem_on) && is_numeric($redeem_on) ) {
					$redeemOnOrder = wc_get_order( $redeem_on );
					if ( $redeemOnOrder && $items = $redeemOnOrder->get_items() ) {
						foreach ( $items as $item ) {

							$redeemOnProds[] = $item->get_name();

							// Get from meta, as deleted ids will not display
							$rProdId	= wc_get_order_item_meta( $item->get_id(), '_product_id', true );
							$rvarId		= wc_get_order_item_meta( $item->get_id(), '_variation_id', true );

							$redeemOnProdsId = !empty( $rvarId ) ? $rvarId : $rProdId;
							if ( !empty($redeemOnProdsId) && !in_array( $redeemOnProdsId, $redeemOnProdsIds ) ) {
								$redeemOnProdsIds[] = $redeemOnProdsId;
							}
						}
					}
				}

				// get partial reedeem childres
				$partRedeemedPosts = get_children( array(
					'post_type'		=> 'woovoupartredeem',
					'numberposts'	=> -1,
					'post_parent'	=> $voucodes_data['ID'],
					'fields'		=> 'ids',
				) );

				if ( !empty($partRedeemedPosts) ) {
					foreach ( $partRedeemedPosts as $partPost ) {
						$redeemOrderID = get_post_meta( $partPost, $prefix . 'redeemed_on', true );
						$redeemOnOrder = wc_get_order( $redeemOrderID );
						if ( is_numeric($redeemOrderID) && $redeemOnOrder && $items = $redeemOnOrder->get_items() ) {
							foreach ( $items as $item ) {

								$redeemOnProds[] = $item->get_name();

								// Get from meta, as deleted ids will not display
								$rProdId	= wc_get_order_item_meta( $item->get_id(), '_product_id', true );
								$rvarId		= wc_get_order_item_meta( $item->get_id(), '_variation_id', true );

								$redeemOnProdsId = !empty( $rvarId ) ? $rvarId : $rProdId;
								if ( !empty($redeemOnProdsId) && !in_array( $redeemOnProdsId, $redeemOnProdsIds ) ) {
									$redeemOnProdsIds[] = $redeemOnProdsId;
								}
							}
						}
					}
				}
					
				// get order detail
				$order = new WC_Order( $orderid );
				
				// get Buyer id, if buyer is guest then user id will be zero
				if ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) == -1 )
					$user_id = $order->user_id;
				else
					$user_id = $order->get_user_id();				
				
				// Get Product information 
				$product_info = woo_vou_display_product_info_html( $orderid, $voucode, 'csv' );
				
				// Get order information 
				$order_info = woo_vou_display_order_info_html( $orderid, 'csv' );
				
				// Get Recipient information 
				$recipient_info = woo_vou_display_recipient_info_html( $orderid, $voucode, 'csv' );

				// Get Voucher information 
				$voucher_info = woo_vou_display_voucher_info_html( $orderid, $voucodes_data['ID'], $voucode, 'csv' );
				
				//this line should be on start of loop
				$export = '"'.html_entity_decode(woo_vou_secure_voucher_code($voucode,$voucodes_data['ID'])).'",';
				$export .= '"'.$product_info.'",';
				$export .= '"'.$buyerinfo.'",';
				$export .= '"'.$order_info.'",';
				$export .= '"'.$voucher_info.'",';
				$export .= '"'.$recipient_info.'",';

				if( isset( $_GET['woo_vou_action'] ) && ( $_GET['woo_vou_action'] == 'used' || $_GET['woo_vou_action'] == 'partially' ) ) {
					$export .= '"'.$redeeminfo.'",';
					$export .= '"'.implode( ",\n", $redeemOnProds ).'",';
					$export .= '"'.implode( ", ", $redeemOnProdsIds ).'",';
				}
				$exports .= apply_filters( 'woo_vou_generate_csv_add_column_after', $export, $orderid, $voucode, $voucodes_data['ID'] );
				
				$exports .="\n";
			}
		}

		// Apply filter to allow 3rd party people to change it
		$date_format = apply_filters( 'woo_vou_voucher_date_format', 'Y-m-d' );

		$vou_file_name = str_replace( '{current_date}', date( $date_format ), $vou_file_name );

		// Output to browser with appropriate mime type, you choose ;)
		header("Content-type: text/x-csv");
		header("Content-Disposition: attachment; filename=".$vou_file_name.".csv");
		echo $exports;
		exit;
		
	}

	// generate csv for voucher code
	if( !empty( $_GET['woo-vou-voucher-advexp-csv'] ) && $_GET['woo-vou-voucher-advexp-csv'] == '1' )  {	
		global $current_user,$woo_vou_model, $woo_vou_voucher, $post, $woo_vou_vendor_role;

		// Taking parameter
		$orderby 	= isset( $_GET['orderby'] ) ? urldecode( $_GET['orderby'] ) : 'ID';
		$order		= isset( $_GET['order'] ) ? $_GET['order'] : 'DESC';

		$args = array(
			'orderby'	=> $orderby,
			'order'		=> $order,
		);

		$admin_roles	= woo_vou_assigned_admin_roles();
		
		//Get user role
		$user_roles	= isset( $current_user->roles ) ? $current_user->roles : array();
		$user_role	= array_shift( $user_roles );

		if ( empty ( $current_user->ID ) || empty ( $current_user->roles ) ) {
		    return;
		}
		
		// Model class
		$model = $woo_vou_model;

		// Get option whether to allow all vendor to redeem voucher codes
	    $vou_enable_vendor_access_all_voucodes = get_option( 'vou_enable_vendor_access_all_voucodes' );
	
		$exports = '';

		// Get redeem type
		$redeemType = !empty( $_GET['woo_vou_partial_used_voucode'] ) ? sanitize_text_field( $_GET['woo_vou_partial_used_voucode'] ) : '';

		// Get current action
		$woo_vou_action = isset( $_GET['woo_vou_action'] ) ? $_GET['woo_vou_action'] : '';
		
		// Check action is used codes
		if ( 'used' == $woo_vou_action ) {

			// If Partially Used checkbox is ticked than only show voucher codes which are used partially
			if ( ! empty($redeemType) ) {

				// List only fully listed codes
				if ( 'full' == $redeemType ) {
					$args['meta_query'] = array( array( 
						'key' 		=> $prefix . 'used_codes',
						'value' 	=> '',
						'compare' 	=> '!='
					) );

				} elseif ( 'partial' == $redeemType ) { // List only partialy used codes

					// Search for code having meta key _woo_vou_redeem_method and meta value partial
					$args['meta_query'] = array( array(
						'relation' => 'AND',
						array(
							'key'		=> $prefix . 'used_codes',
							'value'		=> '',
							'compare'	=> '!='
						),
						array( 
							'key'		=> $prefix . 'redeem_method',
							'value'		=> 'partial',
						) )
					);
				}
				
			} else {

				// For all, redeem and partial redeem data
				$args['meta_query'] = 	array( array(
					'relation' => 'OR',
					array(
						'key' 		=> $prefix . 'used_codes',
						'value' 	=> '',
						'compare' 	=> '!='
					),
					array( 
						'key'		=> $prefix . 'redeem_method',
						'value'		=> 'partial',
					) )
				);
			}

			//Get user role
			$user_roles	= isset( $current_user->roles ) ? $current_user->roles : array();
			$user_role	= array_shift( $user_roles );
			
			// Voucher admin roles
			$admin_roles	= woo_vou_assigned_admin_roles();

			if ( in_array( $user_role, $woo_vou_vendor_role ) 
				&& ( empty($vou_enable_vendor_access_all_voucodes) || $vou_enable_vendor_access_all_voucodes == 'no' ) ) {// voucher admin can redeem all codes

				$args['author'] = $current_user->ID;

			} elseif ( !in_array( $user_role, $woo_vou_vendor_role ) && !in_array( $user_role, $admin_roles ) ) {
		
				$args['meta_query'] =	array(
					'relation' => 'AND',
					( $args['meta_query'] ),
					array( array(
						'key'		=> $prefix . 'customer_user',
						'value'		=> $current_user->ID,
						'compare'	=> '=',
					) )
				);
			}

			if ( ! empty( $_GET['woo_vou_post_id'] ) ) {
				$args['post_parent'] = $_GET['woo_vou_post_id'];
			}
			
			if ( ! empty( $_GET['woo_vou_user_id'] ) ) {
				$args['meta_query'] =	array(
					'relation' => 'AND',
					( $args['meta_query'] ),
					array( array(
						'key'		=> $prefix.'redeem_by',
						'value'		=> $_GET['woo_vou_user_id'],
						'compare'	=> '=',
					) )
				);
			}
			
			if( isset( $_GET['woo_vou_start_date'] ) && !empty( $_GET['woo_vou_start_date'] ) ) {
				$args['meta_query'] =	array(
					'relation' => 'AND',
					($args['meta_query']),
					array(
						array(
							'key'		=> $prefix.'used_code_date',
							'value'		=> date( "Y-m-d H:i:s", strtotime( $_GET['woo_vou_start_date'] ) ),
							'compare'	=> '>=',
						)
					)
				);
			}
			
			if( isset( $_GET['woo_vou_end_date'] ) && !empty( $_GET['woo_vou_end_date'] ) ) {
				$args['meta_query'] =	array(
					'relation' => 'AND',
					($args['meta_query']),
					array(
						array(
							'key'		=> $prefix.'used_code_date',
							'value'		=> date( "Y-m-d H:i:s", strtotime( $_GET['woo_vou_end_date'] ) ),
							'compare'	=> '<=',
						)
					)
				);
			}

			if( isset( $_GET['s'] ) && !empty( $_GET['s'] ) ) {
				
				//$args['s'] = $_GET['s'];
				$args['meta_query'] = array(
					'relation' => 'AND',
					($args['meta_query']),
					array(
						'relation'	=> 'OR',
						array(
							'key'		=> $prefix.'used_codes',
							'value'		=> $_GET['s'],
							'compare'	=> 'LIKE',
						),
						array(
							'key'		=> $prefix.'first_name',
							'value'		=> $_GET['s'],
							'compare'	=> 'LIKE',
						),
						array(
							'key'		=> $prefix.'last_name',
							'value'		=> $_GET['s'],
							'compare'	=> 'LIKE',
						),
						array(
							'key'		=> $prefix.'order_id',
							'value'		=> $_GET['s'],
							'compare'	=> 'LIKE',
						),
						array(
							'key'		=> $prefix.'order_date',
							'value'		=> $_GET['s'],
							'compare'	=> 'LIKE',
						),
					)
				);
			}

			$args = apply_filters('woo_vou_used_codes_gen_csv', $args);

		 	//Get Voucher Details by post id
		 	$voucodes = woo_vou_get_voucher_details( $args );
		 	
		 	$vou_file_name = 'woo-used-voucher-codes-{current_date}';
			
		} else { // Get expired codes
		 	
		 	if( isset( $_GET['vou-data'] ) && $_GET['vou-data'] == 'expired') {
		 		
	 			$args['meta_query'] = array(
					array(
						'key'		=> $prefix . 'purchased_codes',
						'value'		=> '',
						'compare'	=> '!='
					),
					array(
						'key'		=> $prefix . 'used_codes',
						'compare'	=> 'NOT EXISTS'
					 ),
					array(
						'key' =>  $prefix .'exp_date',
						'compare'	=> '<=',
							'type'	=> 'DATE',
							'value'	=> $model->woo_vou_current_date()
					)
				);
									
				$vou_file_name = 'woo-expired-voucher-codes-{current_date}';
		 		
		 	} else {

				// If Partially Used checkbox is ticked than only show voucher codes which are used partially
				if ( ! empty($redeemType) ) {

					// List only fully listed codes
					if ( 'unredeem' == $redeemType ) {
						$args['meta_query'] = array(
							'relation' => 'AND',
							array(
								'key'		=> $prefix . 'purchased_codes',
								'value'		=> '',
								'compare'	=> '!='
							),
							array(
								'key'		=> $prefix . 'used_codes',
								'compare'	=> 'NOT EXISTS'
							),
							array( 
								'key'		=> $prefix . 'redeem_method',
								'compare'	=> 'NOT EXISTS'
							)
						);

					} elseif ( 'partial' == $redeemType ) { // List only partialy used codes

						// Search for code having meta key _woo_vou_redeem_method and meta value partial
						$args['meta_query'] = array(
							'relation' => 'AND',
							array(
								'key' 		=> $prefix . 'used_codes',
								'value' 	=> '',
								'compare' 	=> '!='
							),
							array( 
								'key'		=> $prefix . 'redeem_method',
								'value'		=> 'partial',
							)
						);
					}
					
				} else {

					// For all, redeem and partial redeem data
					$args['meta_query'] = 	array(
						'relation' => 'OR',
						array(
							'relation' => 'AND',
							array(
								'key'		=> $prefix . 'purchased_codes',
								'value'		=> '',
								'compare'	=> '!='
							),
							array(
								'key'		=> $prefix . 'used_codes',
								'compare'	=> 'NOT EXISTS'
							),
							array( 
								'key'		=> $prefix . 'redeem_method',
								'compare'	=> 'NOT EXISTS'
							)
						),
						array(
							'relation' => 'AND',
							array(
								'key' 		=> $prefix . 'used_codes',
								'value' 	=> '',
								'compare' 	=> '!='
							),
							array( 
								'key'		=> $prefix . 'redeem_method',
								'value'		=> 'partial',
							)
						)
					);
				}
		 	}

		 	// Add date meta query
		 	$args['meta_query'] = array(
				( $args['meta_query'] ),
				array(
					'relation' => 'OR', // Optional, defaults to "AND"
					array(
						'key'     => $prefix .'exp_date',
						'value'   => '',
						'compare' => '='
					),
					array(
						'key' =>  $prefix .'exp_date',
						'compare' => '>=',
							'type'    => 'DATE',
							'value' => $model->woo_vou_current_date()
					)
				)
			);

		 	$vou_csv_name  = get_option( 'vou_csv_name' );
			$vou_file_name = !empty( $vou_csv_name ) ? $vou_csv_name : 'woo-purchased-voucher-codes-{current_date}';

			// Get user role
			$user_roles	= isset( $current_user->roles ) ? $current_user->roles : array();
			$user_role	= array_shift( $user_roles );

			// Voucher admin roles
			$admin_roles	= woo_vou_assigned_admin_roles();
	
			if( in_array( $user_role, $woo_vou_vendor_role ) && 
				( empty($vou_enable_vendor_access_all_voucodes) || $vou_enable_vendor_access_all_voucodes == 'no' ) ) {// voucher admin can redeem all codes

				$args['author'] = $current_user->ID;

			} elseif( !in_array( $user_role, $woo_vou_vendor_role ) && !in_array( $user_role, $admin_roles ) ) {
		
				$args['meta_query'] = array(
					'relation' => 'AND',
					($args['meta_query']),
					array(
						array(
							'key'		=> $prefix.'customer_user',
							'value'		=> $current_user->ID,
							'compare'	=> '=',
						)
					)
				);
			}

			if ( isset( $_GET['woo_vou_post_id'] ) && !empty( $_GET['woo_vou_post_id'] ) ) {
				$args['post_parent'] = $_GET['woo_vou_post_id'];
			}
			
			if ( isset( $_GET['s'] ) && !empty( $_GET['s'] ) ) {
				$args['meta_query'] = array(
					'relation' => 'AND',
					( $args['meta_query'] ),
					array(
						'relation'	=> 'OR',
						array(
							'key'		=> $prefix.'purchased_codes',
							'value'		=> $_GET['s'],
							'compare'	=> 'LIKE',
						),
						array(
							'key'		=> $prefix.'first_name',
							'value'		=> $_GET['s'],
							'compare'	=> 'LIKE',
						),
						array(
							'key'		=> $prefix.'last_name',
							'value'		=> $_GET['s'],
							'compare'	=> 'LIKE',
						),
						array(
							'key'		=> $prefix.'order_id',
							'value'		=> $_GET['s'],
							'compare'	=> 'LIKE',
						),
						array(
							'key'		=> $prefix.'order_date',
							'value'		=> $_GET['s'],
							'compare'	=> 'LIKE',
						),
					)
				);
			}

			if( isset( $_GET['woo_vou_start_date'] ) && !empty( $_GET['woo_vou_start_date'] ) ) {
				$args['date_query'][] = array(
					'column' => 'post_date',
					'after'  => $_GET['woo_vou_start_date'],
				);
			}

			if( isset( $_GET['woo_vou_end_date'] ) && !empty( $_GET['woo_vou_end_date'] ) ) {
				$args['date_query'][] = array(
					'column' => 'post_date',
					'before'  => $_GET['woo_vou_end_date'],
				);
			}

			$args = apply_filters('woo_vou_expired_codes_gen_csv', $args);

		 	//Get Voucher Details by post id
		 	$voucodes = woo_vou_get_voucher_details( $args );
		}

		$columns = array(	
			esc_html__( 'Voucher Code', 'woovoucher' ),
			esc_html__( 'Product Name', 'woovoucher' ),
			esc_html__( 'Product Price', 'woovoucher' ),
			esc_html__( 'Buyer Name', 'woovoucher' ),
			esc_html__( 'Buyer Email', 'woovoucher' ),
			esc_html__( 'Buyer Address', 'woovoucher' ),
			esc_html__( 'Buyer Phone', 'woovoucher' ),
			esc_html__( 'Order ID', 'woovoucher' ),
			esc_html__( 'Order Date', 'woovoucher' ),
			esc_html__( 'Payment Method', 'woovoucher' ),
			esc_html__( 'Order Total', 'woovoucher' ),
			esc_html__( 'Order Discount', 'woovoucher' ),
			esc_html__( 'Vendor\'s Address', 'woovoucher' ),	
			esc_html__( 'Expires', 'woovoucher' ),
			esc_html__( 'PDF Template', 'woovoucher' ),
			esc_html__( 'Recipient Info', 'woovoucher' ),
		);
					     
		if ( $woo_vou_action == 'used' || $woo_vou_action == 'partially' ) {
			
			$new_columns = array(
				esc_html__( 'Redeemed By', 'woovoucher' ),
				esc_html__( 'Redeemed Time', 'woovoucher' ),
				esc_html__( 'Associates Products', 'woovoucher' ),
				esc_html__( 'Associates Products IDs', 'woovoucher' ),
			);
			$columns = array_merge ( $columns , $new_columns );
		}

		// If redeem type is partial
		if ( 'partial' == $redeemType || ('used' == $woo_vou_action && empty($redeemType)) ) {
			$columns[] = esc_html__( 'Redeem Information', 'woovoucher' );
		}
		
		$csv_type	= isset( $_GET['woo_vou_action'] ) ? $_GET['woo_vou_action'] : 'purchased';
		$columns	= apply_filters( 'woo_vou_generate_csv_columns', $columns, $csv_type );
		
        // Put the name of all fields
		foreach ($columns as $column) {
			$exports .= '"'.$column.'",';
		}
		$exports .="\n";
		
		if( !empty( $voucodes ) &&  count( $voucodes ) > 0 ) {
			foreach ( $voucodes as $key => $voucodes_data ) {

				if ( $woo_vou_action == 'partially' ) {
					// Voucher order id
					$orderid = get_post_meta( $voucodes[$key]['post_parent'], $prefix.'order_id', true );
				} else {
					// Voucher order id
					$orderid = get_post_meta( $voucodes_data['ID'], $prefix.'order_id', true );
				}

				// Get order detail
	            $order = wc_get_order( $orderid );
	            if ( empty($order) ) {
	            	continue;
	            }

				if ( $woo_vou_action == 'partially') { 
					
					//voucher order date
					$orderdate 		= get_post_meta( $voucodes[$key]['post_parent'], $prefix.'order_date', true );
					$orderdate 		= !empty( $orderdate ) ? $model->woo_vou_get_date_format( $orderdate, true ) : '';
					
					//voucher code purchased/used
					$voucode 		= get_post_meta( $voucodes[$key]['post_parent'], $prefix.'purchased_codes', true );
					
					// Get user details
					$user_id 	 	= get_post_meta( $voucodes_data['ID'], $prefix.'redeem_by', true );
					$user_detail 	= get_userdata( $user_id );
					$redeem_by 		= isset( $user_detail->display_name ) ? $user_detail->display_name : 'N/A';
					
					if ( $user_id == '0' ) {
						$redeem_by = esc_html__( 'Guest User', 'woovoucher' );
					}
					
					// Get redeem Date
					$redeem_date	= get_post_meta( $voucodes_data['ID'], $prefix.'used_code_date', true );
					$redeem_date 	= !empty( $redeem_date ) ? $model->woo_vou_get_date_format( $redeem_date, true ) : '';
					
					// Get Redeem by information 
					$redeeminfo 	=	woo_vou_display_redeem_info_data( $voucodes_data['ID'], $orderid ,'csv', 'partially' ); 
					$redeeminfo = strip_tags( $redeeminfo);

					// Get buyer information 
					$buyer_details	= $model->woo_vou_get_buyer_information( $orderid );
					$buyer_name		= $buyer_details['first_name'].' '.$buyer_details['last_name'];
					$buyer_email	= $buyer_details['email'];
					$buyer_address	= $buyer_details['address_1'].' '.$buyer_details['address_2']."\n";
					$buyer_address .= $buyer_details['city'] . ' ' . $buyer_details['state'] . ' ' . $buyer_details['country'] . ' - ' . $buyer_details['postcode'];
					$buyer_phone 	= $buyer_details['phone'];

				} else {
					
					//voucher order date
					$orderdate 		= get_post_meta( $voucodes_data['ID'], $prefix.'order_date', true );
					$orderdate 		= !empty( $orderdate ) ? $model->woo_vou_get_date_format( $orderdate, true ) : '';
					
					//voucher code purchased/used
					$voucode 		= get_post_meta( $voucodes_data['ID'], $prefix.'purchased_codes', true );
					
					// Get user information 
					$user_id 	 	= get_post_meta( $voucodes_data['ID'], $prefix.'redeem_by', true );
					$user_detail 	= get_userdata( $user_id );
					$redeem_by 		= isset( $user_detail->display_name ) ? $user_detail->display_name : 'N/A';
					
					// Get voucher redeem date
					$redeem_date	= get_post_meta( $voucodes_data['ID'], $prefix.'used_code_date', true );
					$redeem_date 	= !empty( $redeem_date ) ? $model->woo_vou_get_date_format( $redeem_date, true ) : '';
					
					// Get voucher Redeem by information 
					$redeeminfo = woo_vou_display_redeem_info_data( $voucodes_data['ID'], $orderid, 'csv' );
					
					// Get Buyer information 
					$buyer_details	= $model->woo_vou_get_buyer_information( $orderid );
					$buyer_name		= $buyer_details['first_name'] . ' ' . $buyer_details['last_name'];
					$buyer_email	= $buyer_details['email'];
					$buyer_address	= $buyer_details['address_1'] . ' ' . $buyer_details['address_2'] . "\n";
					$buyer_address .= $buyer_details['city'] . ' ' . $buyer_details['state'] . ' ' . $buyer_details['country'] . ' - ' . $buyer_details['postcode'];
					$buyer_phone 	= $buyer_details['phone'];
				}

				// Get associates ids
				$redeem_on	= get_post_meta( $voucodes_data['ID'], $prefix . 'redeemed_on', true );

				$redeemOnProds = $redeemOnProdsIds = array();
				if ( !empty($redeem_on) && is_numeric($redeem_on) ) {
					$redeemOnOrder = wc_get_order( $redeem_on );
					if ( $redeemOnOrder && $items = $redeemOnOrder->get_items() ) {
						foreach ( $items as $item ) {

							$redeemOnProds[] = $item->get_name();

							// Get from meta, as deleted ids will not display
							$rProdId	= wc_get_order_item_meta( $item->get_id(), '_product_id', true );
							$rvarId		= wc_get_order_item_meta( $item->get_id(), '_variation_id', true );

							$redeemOnProdsId = !empty( $rvarId ) ? $rvarId : $rProdId;
							if ( !empty($redeemOnProdsId) && !in_array( $redeemOnProdsId, $redeemOnProdsIds ) ) {
								$redeemOnProdsIds[] = $redeemOnProdsId;
							}
						}
					}
				}

				// Order currency code
				$order_currency = $order->get_currency();

				// Get Product information 
				$product_info = woo_vou_display_product_info_data( $orderid, $voucode, 'csv' );

				// Get redeem method
				$redeem_method = get_post_meta( $voucodes_data['ID'], $prefix . 'redeem_method', true );

				// redeem info
				$redeem_prices = array();
				if ( 'full' == $redeem_method ) {

					// Redeem date
					if ( empty($redeem_date) ) {
						$redeem_date = get_post_meta( $voucodes_data['ID'], $prefix . 'used_code_date', true );
						$redeem_date = !empty( $redeem_date ) ? $model->woo_vou_get_date_format($redeem_date, true) : '';
					}

					$redeem_prices[] = array(
						'by'	=> $redeeminfo['redeem_by'],
						'price'	=> strip_tags( $product_info['price'] ),
						'date'	=> $redeem_date
					);
				}

				// get partial reedeem childres
				$partRedeemedPosts = get_children( array(
					'post_type'		=> 'woovoupartredeem',
					'numberposts'	=> -1,
					'post_parent'	=> $voucodes_data['ID'],
					'fields'		=> 'ids',
				) );

				if ( !empty($partRedeemedPosts) ) {
					foreach ( $partRedeemedPosts as $partPost ) {
						$redeemOrderID = get_post_meta( $partPost, $prefix . 'redeemed_on', true );
						$redeemOnOrder = wc_get_order( $redeemOrderID );
						if ( is_numeric($redeemOrderID) && $redeemOnOrder && $items = $redeemOnOrder->get_items() ) {
							foreach ( $items as $item ) {

								$redeemOnProds[] = $item->get_name();

								// Get from meta, as deleted ids will not display
								$rProdId	= wc_get_order_item_meta( $item->get_id(), '_product_id', true );
								$rvarId		= wc_get_order_item_meta( $item->get_id(), '_variation_id', true );

								$redeemOnProdsId = !empty( $rvarId ) ? $rvarId : $rProdId;
								if ( !empty($redeemOnProdsId) && !in_array( $redeemOnProdsId, $redeemOnProdsIds ) ) {
									$redeemOnProdsIds[] = $redeemOnProdsId;
								}
							}
						}

						if ( 'full' != $redeem_method ) {

							// Get redeem method
							$part_redeem_by		= get_post_meta( $partPost, $prefix . 'redeem_by', true );
							$part_redeem_amount	= get_post_meta( $partPost, $prefix . 'partial_redeem_amount', true );

							$part_user			= get_userdata( $part_redeem_by );
							$part_display_name	= isset( $part_user->display_name ) ? $part_user->display_name : '';
							if ( empty($part_user) && empty($part_display_name) ) {
								$part_display_name = esc_html__( 'Guest User', 'woovoucher' );
							}

							$part_redeem_date = get_post_meta( $partPost, $prefix . 'used_code_date', true );
							$part_redeem_date = !empty( $part_redeem_date ) ? $model->woo_vou_get_date_format( $part_redeem_date, true ) : '';

							$redeem_prices[] = array(
								'by'	=> $part_display_name,
								'price'	=> strip_tags( wc_price($part_redeem_amount) ),
								'date'	=> $part_redeem_date
							);
						}
					}
				}

				$redeem_price_html = '';
				if ( !empty($redeem_prices) ) {
					foreach ( $redeem_prices as $redeem_price ) {
						$redeem_price_html .= $redeem_price['price'] . ' ' . $redeem_price['by'] . ' ' . $redeem_price['date'] . "\n";
					}
				}
					
				// get order detail
				$order = new WC_Order( $orderid );
				
				// get Buyer id, if buyer is guest then user id will be zero
				if ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) == -1 ) {
					$user_id = $order->user_id;
				} else {
					$user_id = $order->get_user_id();
				}
				
				// Get order information 
				$order_info = woo_vou_display_order_info_data( $orderid, 'csv' );
				
				// Get Recipient information 
				$recipient_info = woo_vou_display_recipient_info_html( $orderid, $voucode, 'csv' );

				// Get Voucher information 
				$voucher_info = woo_vou_display_voucher_info_data( $orderid, $voucodes_data['ID'], $voucode, 'csv' );
				
				//this line should be on start of loop
				$export  = '"' . html_entity_decode(woo_vou_secure_voucher_code($voucode, $voucodes_data['ID'])) . '",';
				$export .= '"' . $product_info['name'] . '",';
				$export .= '"' . html_entity_decode( strip_tags( $product_info['price'] ) ) . '",';
				$export .= '"' . $buyer_name . '",';
				$export .= '"' . $buyer_email . '",';
				$export .= '"' . $buyer_address . '",';
				$export .= '"' . $buyer_phone . '",';
				$export .= '"' . $order_info['order_id'] . '",';
				$export .= '"' . $order_info['order_date'] . '",';
				$export .= '"' . $order_info['payment_method'] . '",';
				$export .= '"' . html_entity_decode( $order_info['order_total'] ) . '",';
				$export .= '"' . html_entity_decode( strip_tags( $order_info['order_discount'] ) ) . '",';

				$export .= '"' . $voucher_info['vandor_address'] . '",';
				$export .= '"' . $voucher_info['expires'] . '",';
				$export .= '"' . $voucher_info['pdf_template'] . '",';
				$export .= '"' . $recipient_info . '",';

				if ( $woo_vou_action == 'used' || $woo_vou_action == 'partially' ) {
					$export .= '"' . $redeeminfo['redeem_by'] . '",';
					$export .= '"' . $redeeminfo['redeem_time'] . '",';
					$export .= '"' . implode( ",\n", $redeemOnProds ) . '",';
					$export .= '"' . implode( ", ", $redeemOnProdsIds ) . '",';
				}

				// If redeem type is partial
				if ( 'partial' == $redeemType || ('used' == $woo_vou_action && empty($redeemType)) ) {
					$export .= '"' . html_entity_decode( $redeem_price_html ) . '",';
				}

				$exports .= apply_filters( 'woo_vou_generate_advance_csv_add_column_after', $export, $orderid, $voucode, $voucodes_data['ID'] );
				
				$exports .= "\n";
			}
		}

		// Apply filter to allow 3rd party people to change it
		$date_format = apply_filters( 'woo_vou_voucher_date_format', 'Y-m-d' );

		$vou_file_name = str_replace( '{current_date}', date( $date_format ), $vou_file_name );
		
		// Output to browser with appropriate mime type, you choose ;)
		header("Content-Encoding: UTF-8");
		header("Content-type: text/csv; charset=UTF-8");
		header("Content-Transfer-Encoding: binary");
		// header ( 'Content-Type: application/vnd.ms-excel');
		header("Content-Disposition: attachment; filename=".$vou_file_name.".csv");
		echo mb_convert_encoding( $exports, "UTF-16LE", "UTF-8" );
		exit;
		
	}
}
add_action( 'init', 'woo_vou_code_export_to_csv' );