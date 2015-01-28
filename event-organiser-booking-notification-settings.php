<?php
/**
 * Plugin Name: Event Organiser Booking Notification Settings
 * Plugin URI:  http://wp-event-organiser.com
 * Description: Adds UI settings so that you can change which emails are notified when a booking is made and/or confirmed.
 * Version:     1.0.0
 * Author:      Stephen Harris
 * Author URI:  http://stephenharris.info
 * License:     GPLv2+
 * Text Domain: event-organiser-booking-notification-settings
 * Domain Path: /languages
 */

/**
 * Copyright (c) 2014 Stephen Harris (email : contact@stephenharris.info)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

// Useful global constants
define( 'EOBNS_VERSION', '1.0.0' );
define( 'EOBNS_URL', plugin_dir_url( __FILE__ ) );
define( 'EOBNS_DIR', plugin_dir_path( __FILE__ ) );

/****** Install, activation & deactivation******/
require_once( EOBNS_DIR . 'includes/install.php' );

register_activation_hook( __FILE__, 'eobns_activate' ); 
register_deactivation_hook(  __FILE__, 'eobns_deactivate' );
register_uninstall_hook( __FILE__, 'eobns_uninstall' );

// Wireup actions
function eobns_init() {
	
	$version = defined( 'EOBNS_VERSION' ) ? EOBNS_VERSION : false;
	$ext = (defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG) ? '' : '.min';

	load_plugin_textdomain( 'eobns', false, EOBNS_DIR . '/languages/' );
	
}
add_action( 'plugins_loaded', 'eobns_init' );


/**
 * Regsister the settings & settings section
 */
function eventorganiser_bns_register_settings( $tab_id ) {
	
	register_setting( 'eventorganiser_bookings', 'eventorganiser_bns_notification_emails' );
	register_setting( 'eventorganiser_bookings', 'eventorganiser_bns_notify_organiser' );
	add_action( "load-settings_page_event-settings", 'eventorganiser_bns_add_fields', 20, 0 );
}
add_action( "eventorganiser_register_tab_bookings", 'eventorganiser_bns_register_settings' );


/**
 * Add settings fields
 */
function eventorganiser_bns_add_fields( $tab_id ='bookings' ) {

	if( 'bookings' != $tab_id ){
		return;
	}
	
	$notification_emails = get_option( 'eventorganiser_bns_notification_emails', eo_get_admin_email() );
	
	$notification_emails = str_replace( PHP_EOL, ",", $notification_emails );
	$notification_emails = array_map( 'trim', explode( ",", $notification_emails ) );
	$notification_emails = array_map( 'sanitize_email', $notification_emails );
	$notification_emails = array_filter( $notification_emails );
	$notification_emails = implode( PHP_EOL, $notification_emails ); 
	
	add_settings_field( 
		'bns_notification_emails',  
		__( 'Send notifications to', 'eventorganiserbns' ), 
		'eventorganiser_textarea_field' , 
		'eventorganiser_'.$tab_id, $tab_id,
		array(
			'label_for' => 'bns_notification_emails',
			'name'      => 'eventorganiser_bns_notification_emails',
			'value'     => $notification_emails,
			'help'      => 'Seperate multiple e-mail address by a newline or comma',
		) 
	);

	add_settings_field( 
		'bns_notify_organiser',  
		__( 'Notify the event organiser', 'eventorganiserbns' ), 
		'eventorganiser_checkbox_field' , 
		'eventorganiser_'.$tab_id, $tab_id,
		array(
			'label_for' => 'bns_notify_organiser',
			'name'      => 'eventorganiser_bns_notify_organiser',
			'checked'   => get_option( 'eventorganiser_bns_notify_organiser', 0 ),
			'options'   => 1,
		) 
	);

}

function eventorganiser_bns_booking_notification_email( $emails, $booking_id ){

	$notification_emails = get_option( 'eventorganiser_bns_notification_emails', eo_get_admin_email() );
	
	//Validate emails
	$notification_emails = str_replace( PHP_EOL, ",", $notification_emails );
	$notification_emails = array_map( 'trim', explode( ",", $notification_emails ) );
	$notification_emails = array_map( 'sanitize_email', $notification_emails );
	$notification_emails = array_filter( $notification_emails );


	if( get_option( 'eventorganiser_bns_notify_organiser', 0 ) ){
	
		//Get the event ID and organiser ID
		$event_id = eo_get_booking_meta( $booking_id, 'event_id' );

		//Get the event's organiser
		$organiser_id = get_post_field( 'post_author', $event_id );
		$user_obj = get_userdata( $organiser_id );

		//If the user exists, add their email to notify both organiser and admin
		if( $user_obj ){
			$notification_emails[] = $user_obj->user_email;
		}
	
	}
	
	if( $notification_emails ){
		$emails = $notification_emails;
	}

	return $emails;        
}
add_filter( 'eventorganiser_booking_notification_email', 'eventorganiser_bns_booking_notification_email', 500, 2 );