<?php

add_action('admin_footer-edit.php', 'mpd_bulk_admin_script');
 
function mpd_bulk_admin_script() {

    if( is_multisite() ){

        $args     = array('network_id' => null);
        $sites    = wp_get_sites($args);
        $options  = get_option( 'mdp_settings' );

      
        if(isset($options['add_bulk_settings']) || !$options ) : ?>

            <script type="text/javascript">

              jQuery(document).ready(function() {

                <?php foreach ($sites as $site) :?>

                  <?php $blog_details = get_blog_details($site['blog_id']); ?> 

                    <?php if($site['blog_id'] != get_current_blog_id() && current_user_can_for_blog($site['blog_id'], 'publish_posts')):?> 

                      jQuery('<option>').val("dup-<?php echo $site['blog_id'] ?>").text('<?php _e('Duplicate to ')?><?php echo $blog_details->blogname; ?>').appendTo("select[name='action']");
                      jQuery('<option>').val("dup-<?php echo $site['blog_id'] ?>").text('<?php _e('Duplicate to ')?><?php echo $blog_details->blogname; ?>').appendTo("select[name='action2']");

                    <?php endif; ?>
                    
                  <?php endforeach; ?>

              });

            </script>

        <?php endif; ?>
        

      <?php 

    }
    
}

add_action('load-edit.php', 'mpd_bulk_action');
 
function mpd_bulk_action() {
 
  $wp_list_table  = _get_list_table('WP_Posts_List_Table');
  $action         = $wp_list_table->current_action();

  if (0 === strpos($action, 'dup')) {
      
      preg_match("/(?<=dup-)\d+/", $action, $get_site);
      
      if(isset($_REQUEST['post'])) {
            $post_ids = array_map('intval', $_REQUEST['post']);
      }

      $results = array();

      foreach($post_ids as $post_id){
          
          $results[] = mpd_duplicate_over_multisite(
              
              $post_id, 
              $get_site[0],
              $_REQUEST['post_type'],
              get_current_user_id(),
              mpd_get_prefix(),
              'draft'

          );

          

      }
      add_action('admin_notices', 'mpd_bulk_admin_notices');
      $countBatch = count($results);
      $destination_name = get_blog_details($get_site[0])->blogname;
      $notice = '<div class="updated"><p>'. __('You succesfully duplicated $countBatch post to', MPD_DOMAIN ) ." ". $destination_name;
      update_option('mpd_admin_bulk_notice', $notice );

  }
 
}
 
function mpd_bulk_admin_notices() {
 
  global $pagenow;

  
    if($notices= get_option('mpd_admin_bulk_notice')){

         echo $notices;

    }

    //delete_option('mpd_admin_bulk_notice');

  

}

add_action( 'mdp_end_plugin_setting_page', 'add_bulk_settings');

function add_bulk_settings(){

    mpd_settings_field('add_bulk_settings', __( 'Allow batch duplication?', MPD_DOMAIN ), 'mdp_default_batch_render');
     
}

function mdp_default_batch_render(){

  $options = get_option( 'mdp_settings' );
  ?>
  <input type='checkbox' name='mdp_settings[add_bulk_settings]' <?php mpd_checked_lookup($options, 'add_bulk_settings', 'allow-batch') ;?> value='allow-batch'>

  <p class="mpdtip"><?php _e('Having this option checked will allow you to duplicate muliple page att a time via the batch processing options on the WordPress post list page', MPD_DOMAIN)?></p>
  <?php

}