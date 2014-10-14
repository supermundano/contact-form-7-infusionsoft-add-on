<?php
/**
 * Plugin Name: Contact Form 7 - Infusionsoft Add-on
 * Description: An add-on for Contact Form 7 that provides a way to capture leads, tag customers, and send contact form data to InfusionSoft.
 * Version: 1.0.2
 * Author: Ryan Nevius
 * Author URI: http://www.ryannevius.com
 * License: GPLv3
 */

require_once('src/isdk.php');
require_once('cf7-infusionsoft-options.php');
require_once('cf7-infusionsoft-modules.php');

/**
 * Verify CF7 dependencies.
 */
function cf7_infusionsoft_admin_notice() {
	// Verify that CF7 is active and updated to the required version (currently 3.9.0)
	if ( is_plugin_active('contact-form-7/wp-contact-form-7.php') ) {
		$wpcf7_path = plugin_dir_path( dirname(__FILE__) ) . 'contact-form-7/wp-contact-form-7.php';
		$wpcf7_plugin_data = get_plugin_data( $wpcf7_path, false, false);
		$wpcf7_version = (int)preg_replace('/[.]/', '', $wpcf7_plugin_data['Version']);
		// CF7 drops the ending ".0" for new major releases (e.g. Version 4.0 instead of 4.0.0...which would make the above version "40")
		// We need to make sure this value has a digit in the 100s place.
		if ( $wpcf7_version < 100 ) {
			$wpcf7_version = $wpcf7_version * 10;
		}
		// If CF7 version is < 3.9.0
		if ( $wpcf7_version < 390 ) {
			echo '<div class="update-nag"><p><strong>Warning: </strong>Contact Form 7 - InfusionSoft Add-on requires that you have the latest version of Contact Form 7 installed. Please upgrade now.</p></div>';
		}
	}
	// If it's not installed and activated, throw an error
    else {
	    echo '<div class="error"><p>Contact Form 7 is not activated. Contact Form 7 must be installed and activated before you can use the InfusionSoft Add-on.</p></div>';
	}
}
add_action( 'admin_notices', 'cf7_infusionsoft_admin_notice' );


/**
 * Enqueue Scripts with CF7 Dependencies
 */
// function cf7_infusionsoft_enqueue_scripts() {
// 	wp_enqueue_script( 'cf7-infusionsoft-scripts', plugin_dir_url(__FILE__) . 'cf7-infusionsoft-addon.js', ['jquery', 'wpcf7-admin-taggenerator', 'wpcf7-admin'], null, true );
// }
// add_action( 'admin_enqueue_scripts', 'cf7_infusionsoft_enqueue_scripts' );


/**
 * Enable the InfusionSoft tags in the tag generator
 */
function cf7_infusionsoft_add_tag_generator() {
	if(function_exists('wpcf7_add_tag_generator')) {
		wpcf7_add_tag_generator( 'infusionsoft', 'Infusionsoft Fields', 'wpcf7-tg-pane-infusionsoft', 'wpcf7_tg_pane_infusionsoft' );
	}
}
add_action( 'admin_init', 'cf7_infusionsoft_add_tag_generator', 20 );



/**
 * Add meta boxes to the form edit page.
 */
function cf7_infusionsoft_tag_meta_settings() {
	add_meta_box( 'cf7-infusionsoft-settings', 'InfusionSoft Settings', 'cf7_infusionsoft_addon_metaboxes', '', 'form', 'low');
}
add_action( 'wpcf7_add_meta_boxes', 'cf7_infusionsoft_tag_meta_settings' );


function cf7_infusionsoft_addon_metaboxes( $post ) {
	// Add an nonce field so we can check for it later.
	wp_nonce_field( 'cf7_infusionsoft_addon_metaboxes', 'cf7_infusionsoft_addon_metaboxes_nonce' );

	/*
	 * Use get_post_meta() to retrieve an existing value
	 * from the database and use the value for the form.
	 */
	$infusionsoft_addon_tag_value = get_post_meta( $post->id(), '_cf7_infusionsoft_addon_tag_key', true );

	echo '<label for="cf7_infusionsoft_addon_tag"><strong>Contact Tag: </strong></label> ';
	echo '<input type="text" placeholder="infusionsoft_tag_name" id="cf7_infusionsoft_addon_tag" name="cf7_infusionsoft_addon_tag" value="' . esc_attr( $infusionsoft_addon_tag_value ) . '" size="25" />';
}

// Store InfusionSoft tag
function cf7_infusionsoft_addon_save_contact_form( $contact_form ) {
	$contact_form_id = $contact_form->id();

	if ( !isset( $_POST ) || empty( $_POST ) || !isset( $_POST['cf7_infusionsoft_addon_tag'] ) || !isset( $_POST['cf7_infusionsoft_addon_metaboxes_nonce'] ) ) {
		return;
	}

	// Verify that the nonce is valid.
	if ( ! wp_verify_nonce( $_POST['cf7_infusionsoft_addon_metaboxes_nonce'], 'cf7_infusionsoft_addon_metaboxes' ) ) {
		return;
	}

	if ( isset( $_POST['cf7_infusionsoft_addon_tag'] ) ) {
        update_post_meta( $contact_form_id,
           '_cf7_infusionsoft_addon_tag_key',
            $_POST['cf7_infusionsoft_addon_tag']
        );
    }
}
add_action( 'wpcf7_save_contact_form', 'cf7_infusionsoft_addon_save_contact_form' );


function cf7_infusionsoft_addon_signup_form_submitted( $contact_form ) {
	$contact_form_id = $contact_form->id();

	$submission = WPCF7_Submission::get_instance();
  	$posted_data = $submission->get_posted_data();

  	$user_tag = get_post_meta( $contact_form_id, '_cf7_infusionsoft_addon_tag_key', true );

  	// If no tag was set, get out of here.
  	if ( empty( $user_tag ) ) {
  		return;
  	}		
  	
  	// If the email address is not set
  	if ( empty($posted_data['infusionsoft-email']) ) {
  		return;
  	}
  	// If all looks good, let's try to tag the user
	cf7_infusionsoft_addon_tag_user($contact_form_id, $posted_data, $user_tag);
}
add_action( 'wpcf7_mail_sent', 'cf7_infusionsoft_addon_signup_form_submitted' );


function cf7_infusionsoft_addon_tag_user($contact_form_id, $posted_data, $user_tag) {
	// Exit if the API credentials aren't entered
	$infusionsoft_app_name = get_option( 'infusionsoft_app_name');
	$infusionsoft_api_key = get_option( 'infusionsoft_api_key');

	if ( empty( $infusionsoft_app_name ) || empty( $infusionsoft_api_key ) ) {
		echo '<script>alert("You must configure the Contact Form 7 - InfusionSoft Add-on in the Admin.")</script>';
		return;
	}

	// Configure a new InfusionSoft connection
	$app = new iSDK();
    // If no connection is made, get out of here.
    if ( !( $app->cfgCon($infusionsoft_app_name) ) ) {
    	return;
    }

	// Get all existing InfusionSoft tag names
	$tag_names = $app->dsQuery( 'ContactGroup' , 1000 , 0 , array('Id' => '%') , array('GroupName') );
	// Assemble the names into a list of strings
	foreach ($tag_names as $tag_name) {
		$tag_list[] = $tag_name['GroupName'];
	}
	// If the tag is not a valid/existing InfusionSoft tag, get out of here.
	if ( !in_array($user_tag, $tag_list) ) {
		return;
	}
	// Or else, let's tag this shit
	else {
		// Assemble the contact data
		$contact_data = array(
				'FirstName' => ( !empty($posted_data['infusionsoft-first-name']) ) ? $posted_data['infusionsoft-first-name'] : '',
				'LastName' => ( !empty($posted_data['infusionsoft-last-name']) ) ? $posted_data['infusionsoft-last-name'] : '',
				'Email' => $posted_data['infusionsoft-email'],
				'Phone1' => ( !empty($posted_data['infusionsoft-phone']) ) ? $posted_data['infusionsoft-phone'] : '',
			);
		// Add the contact to InfusionSoft, with a duplicate check
		$contact_id = $app->addWithDupCheck($contact_data, 'EmailAndName');

		// Get the ID of the string tag
		$user_tag_id = $app->dsFind('ContactGroup', 1, 0, 'GroupName', $user_tag, array('Id'));

		// Finally, tag the user
		$tag_the_user = $app->grpAssign($contact_id, $user_tag_id[0]['Id']);

		// InfusionSoft requires a "reason" for setting the opt-in marketing status
		$reason = get_bloginfo('name') . ' Website Signup Form';

		// And allow them to receive email marketing
		$set_optin_status = $app->optIn($posted_data['infusionsoft-email'], $reason);
	}
}

?>