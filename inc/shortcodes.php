<?php
//allows modals in admin

function add_digi_button() {
  echo '<a href="#" id="digi-backbone_modal" class="button" title="Add toy Shortcodes">Add toy Shortcodes</a>';
}
add_action('media_buttons', 'add_digi_button', 1000);


/*enques extra js*/
function digidiss_enqueue_page_scripts( $hook ) {
  global $errors, $digi_PLUGIN_PATH, $digi_PLUGIN_URL;
    wp_enqueue_style( 'digidiss_admin_js', $digi_PLUGIN_URL . '/assets/css/admin.css' );
    if ($hook == 'post.php' || $hook == 'post-new.php') {

      include $digi_PLUGIN_PATH.'templates/modal.php';
      wp_enqueue_script( 'digidiss_admin_js', $digi_PLUGIN_URL . '/assets/js/admin.js', array(
        'jquery',
        'jquery-ui-core',
        'backbone',
        'underscore',
        'wp-util',
        'jquery-ui-sortable'
      ) );
      wp_localize_script( 'digidiss_admin_js', 'digidiss_backbone_modal_l10n',
        array(
          'replace_message' => __( 'Choose a method of embedding digi-dissand/or DPLA item(s).<br/><br/><table><tr><td><a class="button" href="#one">Single Item</a></td><td><a class="button" href="#four">Media Playlist</a></td></tr><tr><td><a class="button" href="#two">Tile Gallery</a></td><td><a class="button" href="#five">Map</a></td></tr><tr><td><a class="button" href="#three">Gallery Slider</a></td><td><a class="button" href="#six">Timeline</a></td></tr></table>', 'backbone_modal' ),
          'collection_id' => digidiss_get_pid(),
        ) );
      wp_enqueue_style( 'digidiss_jquery_ui', 'http://code.jquery.com/ui/1.12.0/themes/base/jquery-ui.css');

   //this creates a unique nonce to pass back and forth from js/php to protect
   $item_admin_nonce = wp_create_nonce( 'item_admin_nonce' );
   //this allows an ajax call from admin.js

   wp_localize_script( 'digidiss_admin_js', 'item_admin_obj', array(
      'ajax_url' => admin_url( 'admin-ajax.php' ),
      'item_admin_nonce'    => $item_admin_nonce,
      'pid' => '',
      'errors' => json_encode($errors),
   ) );

   $digi_ajax_nonce = wp_create_nonce( 'digi_ajax_nonce');
   wp_localize_script( 'digidiss_admin_js', 'digi_ajax_obj', array(
     'ajax_url' => admin_url('admin-ajax.php'),
     'digi_ajax_nonce' => $digi_ajax_nonce,
   ));

   $dpla_ajax_nonce = wp_create_nonce( 'dpla_ajax_nonce');
   wp_localize_script( 'digidiss_admin_js', 'dpla_ajax_obj', array(
     'ajax_url' => admin_url('admin-ajax.php'),
     'dpla_ajax_nonce' => $dpla_ajax_nonce,
   ));


 } else {
   return;
 }
}
add_action('admin_enqueue_scripts', 'digidiss_enqueue_page_scripts');
add_action( 'wp_ajax_get_digi_code', 'digidiss_get_digi_items' ); //for auth users

function digidiss_get_digi_items(){
  check_ajax_referer( 'digi_ajax_nonce' );
  $col_pid = digidiss_get_pid();
    $url = "https://repository.library.northeastern.edu/api/v1/search";
    if (isset($_POST['params']['spatialfilter'])){
      $url .= "/geo";
    }
    if (isset($_POST['params']['avfilter'])){
      $url .= "/av";
    }
    if (isset($_POST['params']['timefilter'])){
      $url .= "/date";
    }


    $url .= "/".$col_pid."?per_page=20";

    if (isset($_POST['params']['q'])){
      $url .= "&q=". urlencode(sanitize_text_field($_POST['params']['q']));
    }

    if (isset($_POST['params']['page'])) {
      $url .= "&page=" . $_POST['params']['page'];
    }
    if (isset($_POST['params']['sort'])) {
      $sort = $_POST['params']['sort'];
      switch ($sort) {
        case "title":
            $sort = "title_ssi";
            break;
        case "creator":
            $sort = "creator_ssi";
            break;
        case "date":
            $sort = "date_ssi";
            break;
      }
      if ($sort != ""){
        $url .= "&sort=".$sort."+asc";
      } else {
        $url .= "&sort=score+desc";
      }
    } else {
      $url .= "&sort=score+desc";
    }
    if (isset($_POST['params']['facets'])){
      $facets = $_POST['params']['facets'];
      foreach($facets as $facet_name=>$facet_val){
        if ($facet_name == "creator"){
          if (is_array($facet_val)){
            foreach($facet_val as $facet_value){
              $url .= "&".urlencode("f[creator_sim][]")."=".urlencode($facet_value);
            }
          } else {
            $url .= "&".urlencode("f[creator_sim][]")."=".urlencode($facet_val);
          }
        }
        if ($facet_name == "type"){
          if (is_array($facet_val)){
            foreach($facet_val as $facet_value){
              $url .= "&".urlencode("f[type_sim][]")."=".urlencode($facet_value);
            }
          } else {
            $url .= "&".urlencode("f[type_sim][]")."=".urlencode($facet_val);
          }
        }
        if ($facet_name == "subject"){
          if (is_array($facet_val)){
            foreach($facet_val as $facet_value){
              $url .= "&".urlencode("f[subject_sim][]")."=".urlencode($facet_value);
            }
          } else {
            $url .= "&".urlencode("f[subject_sim][]")."=".urlencode($facet_val);
          }
        }
        if ($facet_name == "date"){
          if (!is_array($facet_val)){
            $url .= "&".urlencode("f[creation_year_sim][]")."=".urlencode($facet_val);
          }
        }
      }
    }
    $data = get_response($url);
    $json = json_decode($data);
    if (isset($json->error)) {
      wp_send_json(json_encode( "There was an error: " . $json->error));
      wp_die();
      return;
    }
    wp_send_json($data);
    wp_die();
}

add_action( 'wp_ajax_get_dpla_code', 'digidiss_get_dpla_items' ); //for auth users

function digidiss_get_dpla_items(){
  check_ajax_referer( 'dpla_ajax_nonce' );
    $url = "http://api.dp.la/v2/items?api_key=dfa5171da90bf85b229bdcc9ad374932&page_size=20";
    if (isset($_POST['params']['q'])){
      $url .= "&q=". urlencode(sanitize_text_field($_POST['params']['q']));
    }
    // if (isset($_POST['params']['avfilter'])){
    //   $url .= '&sourceResource.type=%22moving%20image%22+OR+%22sound%22';
    // } //This won't work because there are no links avail through the api for the actual files
    if (isset($_POST['params']['spatialfilter'])){
      $url .= '&sourceResource.spatial=**';
    }
    if (isset($_POST['params']['timefilter'])){
      $url .= '&sourceResource.date.displayDate=**';
    }
    if (isset($_POST['params']['page'])) {
      $url .= "&page=" . $_POST['params']['page'];
    }
    if (isset($_POST['params']['sort'])) {
      $sort = $_POST['params']['sort'];
      switch ($sort) {
        case "title":
            $sort = "sourceResource.title";
            break;
        case "creator":
            $sort = "sourceResource.contributor";
            break;
        case "date":
            $sort = "sourceResource.date.begin";
            break;
      }
      if ($sort != ""){
        $url .= "&sort_by=".$sort;
      }
    }
    if (isset($_POST['params']['facets'])){
      $facets = $_POST['params']['facets'];
      foreach($facets as $facet_name=>$facet_val){
        if ($facet_name == "creator"){
          if (is_array($facet_val)){
            foreach($facet_val as $facet_value){
              $url .= "&sourceResource.contributor=\"".urlencode($facet_value)."\"";
            }
          } else {
            $url .= "&sourceResource.contributor=\"".urlencode($facet_val)."\"";
          }
        }
        if ($facet_name == "type"){
          if (is_array($facet_val)){
            foreach($facet_val as $facet_value){
              $url .= "&sourceResource.type=\"".urlencode($facet_value)."\"";
            }
          } else {
            $url .= "&sourceResource.type=\"".urlencode($facet_val)."\"";
          }
        }
        if ($facet_name == "subject"){
          if (is_array($facet_val)){
            foreach($facet_val as $facet_value){
              $url .= "&sourceResource.subject.name=\"".urlencode($facet_value)."\"";
            }
          } else {
            $url .= "&sourceResource.subject.name=\"".urlencode($facet_val)."\"";
          }
        }
        if ($facet_name == "date"){
          if (is_array($facet_val)){
            $url .= "&sourceResource.date.after=". urlencode($facet_val[0]). "-01-01&sourceResource.date.before=". urlencode($facet_val[1]). "-01-01";
          }
        }
      }
    }
    $url .= "&facets=sourceResource.contributor,sourceResource.date.begin,sourceResource.date.end,sourceResource.subject.name,sourceResource.type";
    $data = get_response($url);
    $json = json_decode($data);
    if (isset($json->error)) {
      wp_send_json(json_encode( "There was an error: " . $json->error));
      wp_die();
      return;
    }
    wp_send_json($data);
    wp_die();
}
function digidiss_get_jstor_items(){
  check_ajax_referer( 'jstor_ajax_nonce' );
    $url = "http://api.dp.la/v2/items?api_key=dfa5171da90bf85b229bdcc9ad374932&page_size=20";
    if (isset($_POST['params']['q'])){
      $url .= "&q=". urlencode(sanitize_text_field($_POST['params']['q']));
    }
    // if (isset($_POST['params']['avfilter'])){
    //   $url .= '&sourceResource.type=%22moving%20image%22+OR+%22sound%22';
    // } //This won't work because there are no links avail through the api for the actual files
    if (isset($_POST['params']['spatialfilter'])){
      $url .= '&sourceResource.spatial=**';
    }
    if (isset($_POST['params']['timefilter'])){
      $url .= '&sourceResource.date.displayDate=**';
    }
    if (isset($_POST['params']['page'])) {
      $url .= "&page=" . $_POST['params']['page'];
    }
    if (isset($_POST['params']['sort'])) {
      $sort = $_POST['params']['sort'];
      switch ($sort) {
        case "title":
            $sort = "sourceResource.title";
            break;
        case "creator":
            $sort = "sourceResource.contributor";
            break;
        case "date":
            $sort = "sourceResource.date.begin";
            break;
      }
      if ($sort != ""){
        $url .= "&sort_by=".$sort;
      }
    }
    if (isset($_POST['params']['facets'])){
      $facets = $_POST['params']['facets'];
      foreach($facets as $facet_name=>$facet_val){
        if ($facet_name == "creator"){
          if (is_array($facet_val)){
            foreach($facet_val as $facet_value){
              $url .= "&sourceResource.contributor=\"".urlencode($facet_value)."\"";
            }
          } else {
            $url .= "&sourceResource.contributor=\"".urlencode($facet_val)."\"";
          }
        }
        if ($facet_name == "type"){
          if (is_array($facet_val)){
            foreach($facet_val as $facet_value){
              $url .= "&sourceResource.type=\"".urlencode($facet_value)."\"";
            }
          } else {
            $url .= "&sourceResource.type=\"".urlencode($facet_val)."\"";
          }
        }
        if ($facet_name == "subject"){
          if (is_array($facet_val)){
            foreach($facet_val as $facet_value){
              $url .= "&sourceResource.subject.name=\"".urlencode($facet_value)."\"";
            }
          } else {
            $url .= "&sourceResource.subject.name=\"".urlencode($facet_val)."\"";
          }
        }
        if ($facet_name == "date"){
          if (is_array($facet_val)){
            $url .= "&sourceResource.date.after=". urlencode($facet_val[0]). "-01-01&sourceResource.date.before=". urlencode($facet_val[1]). "-01-01";
          }
        }
      }
    }
    $url .= "&facets=sourceResource.contributor,sourceResource.date.begin,sourceResource.date.end,sourceResource.subject.name,sourceResource.type";
    $data = get_response($url);
    $json = json_decode($data);
    if (isset($json->error)) {
      wp_send_json(json_encode( "There was an error: " . $json->error));
      wp_die();
      return;
    }
    wp_send_json($data);
    wp_die();
}

add_action('wp_ajax_get_custom_meta', 'digidiss_get_custom_meta');
function digidiss_get_custom_meta(){
  check_ajax_referer('item_admin_nonce');
  $id = $_POST['pid'];
  $data = get_post_custom($id);
  wp_send_json($data);
  wp_die();
}

add_action('wp_ajax_get_post_meta', 'digidiss_get_post_meta');
function digidiss_get_post_meta(){
  check_ajax_referer('item_admin_nonce');
  $id = $_POST['pid'];
  $data = get_post($id);
  wp_send_json($data);
  wp_die();
}
