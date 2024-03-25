<?php 
/**
 * Plugin Name: Post View Count
 * Plugin URI: https://mdibrahim.net
 * Description: A simple plugin to count post views
 * Version: 1.0
 * Author: Elysium Developer
 * Author URI: https://mdibrahim.net
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ed-post-view-count
 * Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

include_once(ABSPATH . 'wp-admin/includes/plugin.php'); // for dynamic version control
class EDPostViewCount {
    public function __construct(){
        add_action('init', [$this, 'edpvc_init']); // Initiate the plugin
    }
    public function edpvc_init(){
        add_action('plugins_loaded', [$this, 'load_textdomain']); // Load the text domain
        add_action('wp_enqueue_scripts', [$this, 'edpvc_enqueue_scripts']); // Enqueue the assets
        add_action('wp_head', [$this, 'edpvc_count_view']); // Get count view
        add_filter('manage_posts_columns', [$this, 'edpvc_add_posts_view_count_columns']); // Add New View Count Column to post
        add_action('manage_posts_custom_column', [$this, 'edpvc_manage_posts_view_count_columns'], 10, 2); // Add Content to the View Count Column
        add_filter('manage_edit-post_sortable_columns', [$this, 'edpvc_manage_edit_post_sortable_columns']); // Add sortable view count column
        add_action('pre_get_posts', [$this, 'edpvc_manage_sortable_columns']); // Make sure the View Count sorting works
        add_shortcode('ed_post_view_count', [$this, 'edpvc_display_view_count_in_post_content']); // Display view count in the post content
    }
    // Load the text domain
    public function load_textdomain(){
        load_plugin_textdomain('ed-post-view-count', false, dirname(plugin_basename(__FILE__)).'/languages');
    }
    // Enqueue the assets
    public function edpvc_enqueue_scripts(){
        // For plugin dynamic version control 
        $plugin_data = get_plugin_data(__FILE__);
        $version = $plugin_data['Version'];

        wp_enqueue_style('edpvc-style', plugin_dir_url(__FILE__).'assets/css/style.css', [], $version, 'all');
    }
    // Get count view
    public function edpvc_count_view(){
        if(is_single()){
            global $post;
            $post_id = $post->ID;
            $count_key = 'ed_post_view_count';
            $count = get_post_meta($post_id, $count_key, true);
            if($count==''){
                $count = 0;
                delete_post_meta($post_id, $count_key);
                add_post_meta($post_id, $count_key, '1');
            }else{
                $count++;
                update_post_meta($post_id, $count_key, $count);
            }
        }
    }
    // Add New View Count Column to post
    public function edpvc_add_posts_view_count_columns($columns){
        $columns['ed_post_view_count'] = __('View Count', 'ed-post-view-count');
        return $columns;
    }
    // Add Content to the View Count Column
    public function edpvc_manage_posts_view_count_columns($column_name, $id){
        if($column_name === 'ed_post_view_count'){
            $view_count = get_post_meta($id, 'ed_post_view_count', true);
            $view_count = $view_count ? number_format($view_count) : 0;
            echo esc_html($view_count);
        }
    }

    // Add sortable view count column
    public function edpvc_manage_edit_post_sortable_columns($columns){
        $columns['ed_post_view_count'] = 'ed_post_view_count';
        return $columns;
    }

    // Make sure the View Count sorting works
    public function edpvc_manage_sortable_columns($query){
        if(!is_admin()){
            return;
        }
        $orderby = $query->get('orderby');
        if('ed_post_view_count' == $orderby){
            $query->set('meta_key', 'ed_post_view_count');
            $query->set('orderby', 'meta_value_num');
        }
    }

    // Display view count in the post content
    public function edpvc_display_view_count_in_post_content($atts){

        $atts = shortcode_atts([
            'id' => null
        ], $atts);

        global $post;
        $post_id = $post->ID;
        $view_count_key = 'ed_post_view_count';
        $view_count = get_post_meta($post_id, $view_count_key, true);
        $view_count = $view_count ? number_format($view_count) : 0;

        $html_output = '';

        $html_output = "<div class='edpvc_box'>";
        $html_output .= "<span class='edpvc-title'>".esc_html(__('Post Views', 'ed-post-view-count'))."</span>";
        $html_output .= "<span class='edpvc-subtitle'>".esc_html(__('Total views', 'ed-post-view-count'))."</span>";
        $html_output .= "<span class='edpvc-count'>".esc_html($view_count)."</span>";
        $html_output .= "</div>";

        return $html_output;
    }
}
new EDPostViewCount();