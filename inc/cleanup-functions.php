<?php 

add_action('wp_ajax_mlz_reset_cpt', 'mlz_reset_cpt');
add_action('wp_ajax_nopriv_mlz_reset_cpt', 'mlz_reset_cpt');
function mlz_reset_cpt() {
    $custom_post_type = isset($_POST['custom_post_type']) ? sanitize_text_field($_POST['custom_post_type']) : '';
    $delete_images = isset($_POST['delete_images']) ? intval($_POST['delete_images']) : 0;
    $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
    $totalPosts = isset($_POST['totalPosts']) ? intval($_POST['totalPosts']) : 0;
    
    $all_posts = get_posts( array(
        'fields' => 'ids',
        'post_type' => $custom_post_type,
        'numberposts' => 1,
        'post_status' => 'any',
        //'offset' => $offset, 
    ) );

    if (empty($all_posts)) {
        echo json_encode(array('progress' => 100, 'log' => __('Deletion completed', 'reset-custom-post')));
        wp_die();
    }
    $progressPercentage = (($offset / $totalPosts) * 100);
    $post_id = $all_posts[0];
    $image_ids = [];
    if ($delete_images) {
        $attachments = get_attached_media('image', $post_id);
        foreach ($attachments as $attachment) {
            $image_ids[] = $attachment->ID;
        }
    }
    $post_title = get_the_title($post_id);
    $res = wp_delete_post( $post_id, true );

    $log_message = __('The post <strong>'.$post_title.'</strong> - ID : <strong>'.$post_id .'</strong> is deleted', 'reset-custom-post');
    echo json_encode(array( 'offset' => $offset, 'progress' => $progressPercentage, 'totalPosts' => $totalPosts, 'post_id' => $post_id , 'post_title' => $post_title, 'imagesIds' => $image_ids, 'log' => $log_message));

    ob_flush();
    flush();
    wp_die();
}

add_action('wp_ajax_mlz_reset_cpt_image', 'mlz_reset_cpt_image');
add_action('wp_ajax_nopriv_mlz_reset_cpt_image', 'mlz_reset_cpt_image');

function mlz_reset_cpt_image() {
    $image_id = isset($_POST['image_id']) ? intval($_POST['image_id']) : 0;
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    
    if ($image_id <= 0) {
        echo json_encode(array('log' => __('Invalid image ID', 'reset-custom-post') ));
        wp_die();
    }

    $delete_result = wp_delete_attachment($image_id, true);
    //$delete_result = true;

    if ($delete_result !== false) {
        $log_message = "<p>L'image avec l'ID <strong>$image_id</strong> du post <strong>$post_id</strong> est supprimée</p>";
        echo json_encode(array('post_id' => $post_id, 'image_id' => $image_id, 'image_title' =>  get_the_title($image_id), 'log' => $log_message));
    } else {
        echo json_encode(array('log' => __('Error deleting image', 'reset-custom-post') ));
    }

    wp_die();
}

add_action('wp_ajax_get_total_posts', 'get_total_posts_callback');
add_action('wp_ajax_nopriv_get_total_posts', 'get_total_posts_callback');

function get_total_posts_callback() {
    $custom_post_type = isset($_POST['custom_post_type']) ? sanitize_text_field($_POST['custom_post_type']) : '';
    $args = array(
        'post_type' => $custom_post_type,
        'posts_per_page' => -1,
    );
    $query = new WP_Query($args);
    $total_posts = $query->found_posts;
    $post_type_object = get_post_type_object($custom_post_type);
    if ($post_type_object) {
        $cpt = $post_type_object->labels->singular_name;
    }

    echo json_encode(array('total' => $total_posts, 'msg' => __('Delete '.$total_posts.' ' .$cpt, 'reset-custom-post'), 'log' => __('Custom post changed to <strong>' .$cpt. '</strong> ', 'reset-custom-post') ));

    wp_die();
}