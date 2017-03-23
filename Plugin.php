<?php

$dir = plugin_dir_url(__FILE__);
define('PLUGIN_JS', $dir . 'js');

class BuildingManager {

    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'plugin_activated'));
        register_deactivation_hook(__FILE__, array($this, 'plugin_deactivated'));
        add_action('admin_menu', array($this,'register_plugin_menu'));
        add_action('init', array($this, 'custom_post_type'));
        add_action('init', array($this, 'create_manager_post_tax'));
        add_shortcode('register-new-post', array($this, 'register_new_post'));
        add_shortcode('get-user-post', array($this, 'get_all_posts'));
        add_action('wp_enqueue_scripts', array($this, 'wp_enqueue_scripts'));
        add_action('wp_ajax_add_new_post', array($this, 'add_post_ajax_handler'));
        add_action('wp_ajax_nopriv_add_new_post', array($this, 'add_post_ajax_handler'));
        add_action('wp_ajax_delete_single_post', array($this, 'delete_post_ajax_handler'));
        add_action('wp_ajax_nopriv_delete_single_post', array($this, 'delete_post_ajax_handler'));
        add_action('wp_enqueue_scripts', array($this, 'add_media_upload_scripts'));
    }

    function wp_enqueue_scripts() {
        wp_register_script('validator-js', PLUGIN_JS . '/validator.js', array(), '1.0.0', 'all');
    }

    public function plugin_activated() {
        
    }

    function add_media_upload_scripts() {
        wp_enqueue_media();
    }

    public function plugin_deactivated() {
        // This will run when the plugin is deactivated, use to delete the database
    }

    function register_plugin_menu() {
        add_menu_page('Manager post', 'Manager Post', 'manage_options', 'my-plugin-settings', array($this, 'manager_post_settings_page'));
    }

    function manager_post_settings_page() {
        echo "jhlsfjhsdkhfkjshfkjshfksjf";
    }

    function register_new_post($post_id = FALSE) {
        wp_enqueue_script('validator-js');
        include 'add_new_post_widget.php';
    }

    function get_all_posts() {
        include 'get_all_posts.php';
    }

    function delete_post_ajax_handler() {
        $arr = array();
        $post_id = $_REQUEST['post_id'];
        wp_delete_post($post_id);
        $arr['msg'] = get_the_title($post_id) . " has been deleted successfully";
        echo json_encode($arr);
        exit();
    }

    function custom_post_type() {
        $labels = array(
            'name' => _x('Manager post', 'Post Type General Name', 'twentythirteen'),
            'singular_name' => _x('Manager post', 'Post Type Singular Name', 'twentythirteen'),
            'menu_name' => __('Manager post', 'twentythirteen'),
            'parent_item_colon' => __('Parent Manager post', 'twentythirteen'),
            'all_items' => __('All Manager post', 'twentythirteen'),
            'view_item' => __('View Manager post', 'twentythirteen'),
            'add_new_item' => __('Add Manager post', 'twentythirteen'),
            'add_new' => __('Add New', 'twentythirteen'),
            'edit_item' => __('Edit Manager post', 'twentythirteen'),
            'update_item' => __('Update Manager post', 'twentythirteen'),
            'search_items' => __('Search Manager post', 'twentythirteen'),
            'not_found' => __('Not Found', 'twentythirteen'),
            'not_found_in_trash' => __('Not found in Trash', 'twentythirteen'),
        );
        $args = array(
            'label' => __('Manager post', 'twentythirteen'),
            'labels' => $labels,
            'supports' => array('title', 'editor', 'excerpt', 'author', 'thumbnail', 'comments', 'revisions', 'custom-fields',),
            'hierarchical' => false,
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_nav_menus' => true,
            'show_in_admin_bar' => true,
            'menu_position' => 5,
            'can_export' => true,
            'has_archive' => true,
            'exclude_from_search' => false,
            'publicly_queryable' => true,
            'capability_type' => 'page',
        );

        // Registering your Custom Post Type
        //  register_post_type('manager-post', $args);
    }

    function create_manager_post_tax() {
        register_taxonomy(
                'manager-category', 'manager-post', array(
            'label' => __('Manager Categories'),
            'rewrite' => array('slug' => 'manager-category'),
            'hierarchical' => true,
                )
        );
    }

    function add_post_ajax_handler() {
        $arr = array();
        $title = esc_html($_REQUEST['post_title']);
        $description = esc_html($_REQUEST['post_data']);
        $attach_id = $_REQUEST['feature_image'];
        $type = $_REQUEST['post_type'];
        $page_id = $_REQUEST['page_id'];
        $post_id = $_REQUEST['post_id'];
        $my_post = array(
            'post_title' => wp_strip_all_tags($title),
            'post_content' => $description,
            'post_status' => 'publish',
            'post_type' => $type,
            'post_author' => get_current_user_id(),
        );
        if ($post_id) {
            $my_post['ID'] = $post_id;
            wp_update_post($my_post);
            $arr['url'] = get_permalink($page_id) . "?edit_id=" . $post_id;
            $arr['msg'] = "Post has been updated successfully";
        } else {
            $post_id = wp_insert_post($my_post);
            $arr['msg'] = "Post has been inserted successfully";
            $arr['url'] = get_permalink($page_id);
        }
if($attach_id){
        set_post_thumbnail($post_id, $attach_id);
}
else{
   delete_post_thumbnail( $post_id );
}

        echo json_encode($arr);
        exit();
    }

    function wpex_pagination($total) {

        $prev_arrow = is_rtl() ? '→' : '←';
        $next_arrow = is_rtl() ? '←' : '→';

        global $wp_query;
        // $total = $wp_query->max_num_pages;
        $big = 999999999; // need an unlikely integer
        if ($total > 1) {
            if (!$current_page = get_query_var('paged'))
                $current_page = 1;
            if (get_option('permalink_structure')) {
                $format = 'page/%#%/';
            } else {
                $format = '&paged=%#%';
            }
            echo paginate_links(array(
                'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                'format' => $format,
                'current' => max(1, get_query_var('paged')),
                'total' => $total,
                'mid_size' => 3,
                'type' => 'list',
                'prev_text' => $prev_arrow,
                'next_text' => $next_arrow,
            ));
        }
    }

}

new BuildingManager();

