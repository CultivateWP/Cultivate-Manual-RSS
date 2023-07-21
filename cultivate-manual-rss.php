<?php
/**
 * Plugin Name: Cultivate Manual RSS
 * Plugin URI:  https://cultivatewp.com/our-plugins/cultivate-manual-rss/
 * Description: Build RSS feeds. Posts are sorted by the date added to a category, not publish date.
 * Author:      CultivateWP
 * Author URI:  https://cultivatewp.com/
 * Version:     1.1.0
 * Text Domain: cultivate-manual-rss
 *
 * Cultivate Manual RSS is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * Cultivate Manual RSS is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Cultivate Manual RSS. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Cultivate\Manual_RSS;
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

/**
 * Register taxonomy
 */
function register_taxonomy() {
	$labels = array(
		'name'                       => _x( 'RSS Categories', 'Taxonomy General Name', 'cultivate-manual-rss' ),
		'singular_name'              => _x( 'RSS Category', 'Taxonomy Singular Name', 'cultivate-manual-rss' ),
		'menu_name'                  => __( 'RSS Categories', 'cultivate-manual-rss' ),
		'all_items'                  => __( 'All Items', 'cultivate-manual-rss' ),
		'parent_item'                => __( 'Parent Item', 'cultivate-manual-rss' ),
		'parent_item_colon'          => __( 'Parent Item:', 'cultivate-manual-rss' ),
		'new_item_name'              => __( 'New Item Name', 'cultivate-manual-rss' ),
		'add_new_item'               => __( 'Add New Item', 'cultivate-manual-rss' ),
		'edit_item'                  => __( 'Edit Item', 'cultivate-manual-rss' ),
		'update_item'                => __( 'Update Item', 'cultivate-manual-rss' ),
		'view_item'                  => __( 'View Item', 'cultivate-manual-rss' ),
		'separate_items_with_commas' => __( 'Separate items with commas', 'cultivate-manual-rss' ),
		'add_or_remove_items'        => __( 'Add or remove items', 'cultivate-manual-rss' ),
		'choose_from_most_used'      => __( 'Choose from the most used', 'cultivate-manual-rss' ),
		'popular_items'              => __( 'Popular Items', 'cultivate-manual-rss' ),
		'search_items'               => __( 'Search Items', 'cultivate-manual-rss' ),
		'not_found'                  => __( 'Not Found', 'cultivate-manual-rss' ),
		'no_terms'                   => __( 'No items', 'cultivate-manual-rss' ),
		'items_list'                 => __( 'Items list', 'cultivate-manual-rss' ),
		'items_list_navigation'      => __( 'Items list navigation', 'cultivate-manual-rss' ),
	);
	$args = array(
		'labels'                     => $labels,
		'hierarchical'               => true,
		'public'                     => true,
		'show_ui'                    => true,
		'show_admin_column'          => false,
		'show_in_nav_menus'          => false,
		'show_tagcloud'              => false,
		'show_in_rest'               => true,
	);
	\register_taxonomy( 'cultivate_rss', apply_filters( 'cultivate_manual_rss_post_types', [ 'post' ] ), apply_filters( 'cultivate_manual_rss_args', $args ) );
}
add_action( 'init', __NAMESPACE__ . '\register_taxonomy' );

/**
 * Activation hook
 */
function activation() {
	$option = 'cultivate_manual_rss_flush_rewrite_rules';
	$option_value = get_option( $option );
	if ( empty( $option_value ) ) {
		add_option( $option, true );
	}
}
register_activation_hook(__FILE__, __NAMESPACE__ . '\activation' );



/**
 * Maybe flush rewrite rules
 */
function maybe_flush_rewrite_rules() {
	$option = 'cultivate_manual_rss_flush_rewrite_rules';
	$option_value = get_option( $option );
	if ( true === $option_value ) {
		\flush_rewrite_rules();
		delete_option( $option );
	}
}
add_action( 'init', __NAMESPACE__ . '\maybe_flush_rewrite_rules', 20 );

/**
 * Save datetime when adding term
 */
function save_datetime( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
	if ( 'cultivate_rss' !== $taxonomy ) {
		return;
	}

	$new_terms = array_diff( $tt_ids, $old_tt_ids );
	if ( ! empty( $new_terms ) ) {
		foreach( $new_terms as $term_id ) {
			$term = get_term_by( 'term_taxonomy_id', $term_id, $taxonomy );
			update_post_meta( $object_id, $term->slug . '_datetime', (new \DateTime())->format('Y-m-d H:i:s') );
		}
	}
}
add_action( 'set_object_terms', __NAMESPACE__ . '\save_datetime', 10, 6 );

/**
 * Sort archive page
 */
function archive_query( $query ) {
	if ( $query->is_main_query() && ! is_admin() && is_tax( 'cultivate_rss' ) ) {
		$query->set( 'orderby', 'meta_value' );
		$query->set( 'meta_key', get_queried_object()->slug . '_datetime' );
	}
}
add_action( 'pre_get_posts', __NAMESPACE__ . '\archive_query', 20 );

/**
 * Updater
 */
function updater() {

	require plugin_dir_path( __FILE__ ) . 'includes/updater/plugin-update-checker.php';
	$myUpdateChecker = PucFactory::buildUpdateChecker(
		'https://github.com/CultivateWP/Cultivate-Manual-RSS/',
		__FILE__, //Full path to the main plugin file or functions.php.
		'cultivate-manual-rss'
	);

}
add_action( 'admin_init', __NAMESPACE__ . '\updater' );
