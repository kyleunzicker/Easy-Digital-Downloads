<?php
/**
 * User Functions
 *
 * Functions related to users / customers
 *
 * @package     Easy Digital Downloads
 * @subpackage  AJAX
 * @copyright   Copyright (c) 2012, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0.8.6
*/

/**
 * Get Users Purchases
 *
 * Retrieves a list of all purchases by a specific user.
 *
 * @access      public
 * @since       1.0 
 * @return      array
*/

function edd_get_users_purchases($user_id) {
	
	$purchases = get_transient('edd_user_' . $user_id . '_purchases');
	if(false === $purchases || edd_is_test_mode()) {
		$mode = edd_is_test_mode() ? 'test' : 'live';
		$purchases = get_posts(
			array(
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'key' => '_edd_payment_mode',
						'value' => $mode
					),
					array(
						'key' => '_edd_payment_user_id',
						'value' => $user_id
					)
				),
				'post_type' => 'edd_payment', 
				'posts_per_page' => -1
			)
		);
		set_transient('edd_user_' . $user_id . '_purchases', $purchases, 7200);
	}
	if($purchases) {
	    // return the download list
		return $purchases;
	}
	
	// no downloads	
	return false;	
}



/**
 * Has User Purchased
 *
 * Checks to see if a user has purchased a download.
 *
 * @access      public
 * @since       1.0 
 * @param       int $user_id - the ID of the user to check
 * @param       int $download_Id - the ID of the download to check for
 * @param       int $variable_price_id - the variable price ID to check for
 * @return      boolean - true if has purchased, false otherwise
*/

function edd_has_user_purchased($user_id, $download_id, $variable_price_id = null) {
	
	$users_purchases = edd_get_users_purchases($user_id);

	$return = false;

	if($users_purchases) {
		foreach($users_purchases as $purchase) {

			$purchase_meta = get_post_meta($purchase->ID, '_edd_payment_meta', true);
			$purchased_files = maybe_unserialize($purchase_meta['downloads']);

			if(is_array($purchased_files)) {

				foreach($purchased_files as $download) {

					if($download['id'] == $download_id) {

						if( !is_null( $variable_price_id ) && $variable_price_id !== false ) {

							if( $variable_price_id == $download['options']['price_id'] ) {
								
								return true;
							
							} else {
							
								$return = false;
							
							}

						} else {
							
							$return = true;
						
						}

					}

				}

			}
		}
	}
	return $return;
}


/**
 * Has Purchases
 *
 * Checks to see if a user has purchased at least one item.
 *
 * @access      public
 * @since       1.0 
 * @param       $user_id int - the ID of the user to check
 * @return      bool - true if has purchased, false other wise.
*/

function edd_has_purchases($user_id = null) {
	
	if(is_null($user_id)) {
		global $user_ID;
		$user_id = $user_ID;
	}
	
	if(edd_get_users_purchases($user_id)) {
		return true; // user has at least one purchase
	}
	return false; // user has never purchased anything
}



function edd_get_downloads_of_user_purchase($user_id, $download_id, $variable_price_id = null) {

	$purchased_files = array();
	$download_files = get_post_meta($download_id, 'edd_download_files', true);
	if( $download_files ) {
		if( !is_null( $variable_price_id ) ) {
			foreach( $download_files as $key => $file_info ) {
				if( $file_info['condition'] == $variable_price_id || $file_info['condition'] == 'all' ) {
					$purchased_files[$key] = $file_info;
				}
			}
		} else {
			$purchased_files = $download_files;
		}
	}

	return $purchased_files;
}