"use strict";

jQuery( document ).ready( function() {
	

	function woo_vou_cleanup_process_start(result ) {		

		if( result.status != 'completed' ){

			// Trigger upgrades on page load
			var data = { 
				action: 'woo_vou_cleanup_product_meta',
				product_ids: result.proccessed_product_ids
			};
			var response_status = 'process';

			jQuery.post( WooVouUpgrd.ajaxurl, data, function (response) {
				
			}).done(function( response ) {				
				if( response.status  == 'completed' ) {
					jQuery('#vou-upgrade-loader').hide();
					document.location.href = 'index.php?woo-vou-upgrades-db-voucher-cleanup=success'; // Redirect to the welcome page
				}
				else{
					woo_vou_cleanup_process_start( response );
				}

			});
		}
	}

	woo_vou_cleanup_process_start({status:"process",proccessed_product_ids:[]});
});
