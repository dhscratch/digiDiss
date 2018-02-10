<?php
add_action('add_meta_boxes_digidiss_item_extension', 'adding_item_extension_meta_box');
function adding_item_extension_meta_box($post){
  add_meta_box(
    'digidiss_meta_box',
    __('Item ID'),
    'render_item_extension_meta_box',
    'digidiss_item_extension',
    'side',
    'high'
  );
  add_meta_box(
    'digidiss_url_meta_box',
    __('Item URL Alias'),
    'render_item_url_meta_box',
    'digidiss_item_extension',
    'side',
    'high'
  );
}

function render_item_extension_meta_box(){
  global $post;
  wp_nonce_field(basename(__FILE__), "meta-box-nonce");
  ?>
  <div>
    <small>ie. neu:123 or dpla:890342</small><br/>
    <input name="item-id" type="text" value="<?php echo get_post_meta($post->ID, "item-id", true); ?>">
  </div>
  <?php
}

function render_item_url_meta_box(){
  global $post;
  wp_nonce_field(basename(__FILE__), "meta-box-nonce");
  ?>
  <div>
    <small>Enter a custom URL alias for this item instead of its default, like item/neu:123. Do not include a leading slash. Examples could be "books/darwin" or "darwin"</small><br/>
    <input name="item-url" type="text" value="<?php echo get_post_meta($post->ID, "item-url", true); ?>">
  </div>
  <?php
}

function save_custom_meta_box($post_id, $post, $update){
  $home_url = get_option('digidiss_home_url');
  if (!isset($_POST["meta-box-nonce"]) || !wp_verify_nonce($_POST["meta-box-nonce"], basename(__FILE__)))
    return $post_id;

  if(!current_user_can("edit_post", $post_id))
      return $post_id;

  if(defined("DOING_AUTOSAVE") && DOING_AUTOSAVE)
    return $post_id;

  $slug = "digidiss_item_extension";
  if($slug != $post->post_type)
    return $post_id;

  $item_id = "";

  if(isset($_POST["item-id"])){
    $item_id = $_POST["item-id"];
  }
  update_post_meta($post_id, "item-id", $item_id);
  if(isset($_POST["item-url"])){
    $item_url = $_POST["item-url"];
  }
  update_post_meta($post_id, "item-url", $item_url);
}

add_action("save_post", "save_custom_meta_box", 10, 3);
