<?php
/**
 * Plugin Name: WellCON Event Year
 * Description: Auto-updating event year and edition. Use [event_year]/[event_edition] in pages, or {event_year}/{event_edition} in titles, menus, product names and Gravity Forms. Rolls over after the event season.
 * Version: 1.3
 * Author: SMPLFY
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// From this date (MM-DD) the site shows next year's event.
const WELLCON_ROLLOVER = '06-01';

function wellcon_event_year() {
	$year = (int) current_time( 'Y' );
	return current_time( 'm-d' ) >= WELLCON_ROLLOVER ? $year + 1 : $year;
}

// First WellCON (then CryoCON) was 2021, so 2027 is the 7th.
const WELLCON_FIRST_YEAR = 2020;

function wellcon_event_edition() {
	$n = wellcon_event_year() - WELLCON_FIRST_YEAR;
	$suffix = ( $n % 100 >= 11 && $n % 100 <= 13 ) ? 'th' : array( 'th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th' )[ $n % 10 ];
	return $n . $suffix;
}

function wellcon_year_token( $text ) {
	if ( ! is_string( $text ) ) return $text;
	if ( strpos( $text, '{event_year}' ) !== false ) {
		$text = str_replace( '{event_year}', wellcon_event_year(), $text );
	}
	if ( strpos( $text, '{event_edition}' ) !== false ) {
		$text = str_replace( '{event_edition}', wellcon_event_edition(), $text );
	}
	return $text;
}

// [event_year] shortcode.
add_shortcode( 'event_year', 'wellcon_event_year' );
add_shortcode( 'event_edition', 'wellcon_event_edition' );

// {event_year} token in site text (frontend only, so editors still see the token).
if ( ! is_admin() || wp_doing_ajax() ) {
	$filters = array(
		'the_title', 'the_content', 'widget_text', 'nav_menu_item_title',
		'woocommerce_product_get_name', 'woocommerce_cart_item_name', 'woocommerce_order_item_name',
	);
	foreach ( $filters as $filter ) {
		add_filter( $filter, 'wellcon_year_token', 20 );
	}
}

// Genesis header/footer scripts (e.g. attendee dashboard header).
add_filter( 'genesis_header_scripts', 'wellcon_year_token' );
add_filter( 'genesis_footer_scripts', 'wellcon_year_token' );

// Woo email subjects/headings/content.
add_filter( 'woocommerce_mail_content', 'wellcon_year_token' );
add_filter( 'woocommerce_email_subject_customer_completed_order', 'wellcon_year_token' );
add_filter( 'woocommerce_email_heading_customer_completed_order', 'wellcon_year_token' );

// Gravity Forms: notifications, confirmations and anything using merge tags.
add_filter( 'gform_replace_merge_tags', 'wellcon_year_token' );

// Gravity Forms: form title, labels, descriptions, HTML fields and choices.
function wellcon_year_form( $form ) {
	$form['title']       = wellcon_year_token( $form['title'] ?? '' );
	$form['description'] = wellcon_year_token( $form['description'] ?? '' );
	foreach ( $form['fields'] as $field ) {
		$field->label       = wellcon_year_token( $field->label );
		$field->description = wellcon_year_token( $field->description );
		$field->content     = wellcon_year_token( $field->content );
		$field->defaultValue = wellcon_year_token( $field->defaultValue );
		if ( is_array( $field->choices ) ) {
			foreach ( $field->choices as $i => $choice ) {
				$field->choices[ $i ]['text'] = wellcon_year_token( $choice['text'] );
			}
		}
	}
	return $form;
}
add_filter( 'gform_pre_render', 'wellcon_year_form' );
add_filter( 'gform_pre_validation', 'wellcon_year_form' );
add_filter( 'gform_pre_submission_filter', 'wellcon_year_form' );
