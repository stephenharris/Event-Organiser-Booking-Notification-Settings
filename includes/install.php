<?php

/**
 * Activate the plugin
 */
function eobns_activate() {
}

/**
 * Deactivate the plugin
 */
function eobns_deactivate() {
}

/**
 * Uninstall the plug-in
 */
function eobns_uninstall() {

	delete_option( 'eventorganiser_bns_notification_emails' );
	delete_option( 'eventorganiser_bns_notify_organiser' );
}
