<?php
/* adds shortcode */
add_shortcode( 'digidiss_item', 'digidiss_item' );
add_shortcode( 'digidiss_single', 'digidiss_item' );
function digidiss_item( $atts ){
  $cache = get_transient(md5('digidiss'.serialize($atts)));
  
  if($cache) {
      return $cache;
  }
  $repo = digidiss_get_repo_from_pid($atts['id']);
  if ($repo != "digidiss"){$pid = explode(":",$atts['id']); $pid = $pid[1];} else {$pid = $atts['id'];}
  if (isset($atts['image-size'])){
    $num = $atts['image-size']-1;
  } else {
    $num = 3;
  }
  if ($repo == "digidiss"){
    $url = "https://repository.library.northeastern.edu/api/v1/files/" . $pid . "?solr_only=true";
    $data = get_response($url);
    $data = json_decode($data);
    write_log($data);
    $data = $data->_source;
    $thumbnail = "https://repository.library.northeastern.edu".$data->fields_thumbnail_list_tesim[$num];
    $master = "https://repository.library.northeastern.edu".$data->fields_thumbnail_list_tesim[4];

    $objects_url = "https://repository.library.northeastern.edu/api/v1/files/" . $pid . "/content_objects";
    $objects_data = get_response($objects_url);
    $objects_data = json_decode($objects_data);
    $data = (object) array_merge((array) $data, (array) $objects_data);

    foreach($data->content_objects as $key=>$val){
      if ($val == 'Large Image'){
        $master = $key;
      }
    }
    $data->mods = new StdClass;
    $data->mods->Title = $data->title_info_title_tesim;
    $abs = "Abstract/Description";
    $data->mods->$abs = $data->abstract_tesim;
    if (isset($data->creator_tesim)){
      $data->mods->Creator = $data->creator_tesim;
    }
    $dat = "Date Created";
    if (isset($data->key_date_ssi)){
      $data->mods->$dat = array($data->key_date_ssi);
    }
    if (isset($data->date_ssi)){
      $data->mods->$dat = array($data->date_ssi);
    }
  }
  if ($repo == "wp"){
    $post = get_post($pid);
    $data = new StdClass;
    $data->canonical_object = new StdClass;
    $url = $post->guid;
    if (strpos($post->post_mime_type, "audio") !== false){
      $type = "Audio File";
    } else if (strpos($post->post_mime_type, "video") !== false){
      $type = "Video File";
    } else {
      $type = "Master Image";
    }
    $data->canonical_object->$url = $type;
    $meta = wp_get_attachment_metadata($pid); //get sizes
    $thumb_base = wp_get_attachment_thumb_url($pid);
    if (isset($meta['sizes'])){
      $thumb_base = explode("/",$thumb_base);
      $arr = array_pop($thumb_base);
      $thumb_base = implode("/", $thumb_base);
      if ($num == 1){ $thumbnail = $thumb_base."/".$meta['sizes']['thumbnail']['file'];}
      if ($num == 2){ $thumbnail = $thumb_base."/".$meta['sizes']['medium']['file'];}
      if ($num == 3){ $thumbnail = $thumb_base."/".$meta['sizes']['medium']['file'];}
      if ($num == 4){
       if (isset($meta['sizes']['large'])){
         $thumbnail = $thumb_base."/".$meta['sizes']['large']['file'];
       } else {
         $thumbnail = digidiss_home_url()."/wp-content/uploads/".$meta['file'];
       }
      }
      if ($num == 5){
       if (isset($meta['sizes']['large'])){
         $thumbnail = $thumb_base."/".$meta['sizes']['large']['file'];
       } else {
         $thumbnail = digidiss_home_url()."/wp-content/uploads/".$meta['file'];
       }
      }
    } else {
      $thumbnail = null;
    }
    $master = $post->guid;
    $data->mods = new StdClass;
    $data->mods->title = array($post->post_title);
    $data->mods->caption = array($post->post_excerpt);
    $data->id = $post->ID;
  }
  if ($repo == "dpla"){
    $dpla = get_response("http://api.dp.la/v2/items/".$pid."?api_key=dfa5171da90bf85b229bdcc9ad374932");
    $dpla = json_decode($dpla);
    if (isset($dpla->docs[0]->object)){
      $url = $dpla->docs[0]->object;
    } else {
      $url = "https://dp.la/info/wp-content/themes/berkman_custom_dpla/images/logo.png";
    }
    $data = new StdClass;
    $data->canonical_object = new StdClass;
    $type = "Master Image";
    $data->canonical_object->$url = $type;
    $thumbnail = $url;
    $master = null;
    $data->mods = new StdClass;
    $title = array($dpla->docs[0]->sourceResource->title);
    $data->mods->Title = $title;
    if (isset($dpla->docs[0]->sourceResource->description)){
      $description = $dpla->docs[0]->sourceResource->description;
    } else {
      $description = "";
    }
    $abs = "Abstract/Description";
    $data->mods->$abs = $description;
    if (isset($dpla->docs[0]->sourceResource->creator)){
      $data->mods->Creator = is_array($dpla->docs[0]->sourceResource->creator) ? $dpla->docs[0]->sourceResource->creator : array($dpla->docs[0]->sourceResource->creator);
    }
    $dat = "Date Created";
    $data->mods->$dat = array($dpla->docs[0]->sourceResource->date->displayDate);
    $data->id = $pid;
  }



  $html = "<div class='digi-item'>";

  $jwplayer = false; // note: unneeded if there is only one canonical_object type

  if (isset($atts['display-video']) && isset($data->canonical_object)){
    foreach($data->canonical_object as $key=>$val){
      if (($val == 'Video File' || $val == 'Audio File') && $atts['display-video'] == "true" ){
        if ($repo == "wp"){
          $html .= do_shortcode('[video src="'.$master.'"]');
        } else {
          $html .= insert_jwplayer($key, $val, $data, $thumbnail);
        }
        $jwplayer = true;
      }
    }
  }

  if (!$jwplayer) {
    if (isset($atts['display-issuu']) && isset($data->digi_location_url_ssim)){
      $location_href = $data->digi_location_url_ssim[0];
      $issu_id = explode('?',$location_href);
      $issu_id = explode('=',$issu_id[1]);
      $issu_id = $issu_id[1];
      $html .= '<div data-configid="'.$issu_id.'" style="width:100%; height:500px;" class="issuuembed"></div><script type="text/javascript" src="//e.issuu.com/embed.js" async="true"></script>';
      $html .= "<a href='".digidiss_home_url()."item/".$atts['id']."'>View Item Details</a>";
    } else {
      $html .= "<a href='".digidiss_home_url()."item/".$atts['id']."'><img class='digi-item-img' id='".$atts['id']."-img' src='".$thumbnail."'";

      if (isset($atts['align'])){
        $html .= " data-align='".$atts['align']."'";
      }
      if (isset($atts['float'])){
        $html .= " data-float='".$atts['float']."'";
      }

      if (isset($atts['zoom']) && $atts['zoom'] == 'on' && $master != null && check_master($master) == true){
        $html .= " data-zoom-image='".$master."' data-zoom='on'";
        if (isset($atts['zoom_position'])){
          $html .= " data-zoom-position='".$atts['zoom_position']."'";
        }
      }

      $html .= "/></a>";
    }
  }

  // start item meta data
  $img_metadata = "";
  if (isset($atts['metadata'])){
    $metadata = explode(",",$atts['metadata']);
    foreach($metadata as $field){
      $this_field = $data->mods->$field;
      if (isset($this_field)){
        if (is_array($this_field)){
          foreach($this_field as $field_val){
            $img_metadata .= $field_val . "<br/>";
          }
        } else {
          if (isset($this_field[0])){
            $img_metadata .= $this_field[0] . "<br/>";
          }
        }
      }
    }
    $html .= "<div class='wp-caption-text digidiss-caption'";
    if (isset($atts['caption-align'])){
      $html .= " data-caption-align='".$atts['caption-align']."'";
    }
    if (isset($atts['caption-position'])){
      $html .= " data-caption-position='".$atts['caption-position']."'";
    }
    $html .= "><a href='".digidiss_home_url()."item/".$atts['id']."'>".$img_metadata."</a></div>";
  }

  // start hidden fields
  $html .= "<div class=\"hidden\">";
  foreach($data as $field => $val){
    if (is_array($val)){
      foreach($val as $field_val){
        $html .= $field_val . "<br/>";
      }
    } elseif(is_object($val)){
      // do nothing with objects
    } else {
      $html .= $val . "<br/>";
    }
  }
  $html .= "</div></div>";
  $cache_output = $html;
  $cache_time = 1000;
  set_transient(md5('digidiss'.serialize($atts)) , $cache_output, $cache_time * 60);
  return $html;
}

add_action( 'wp_ajax_get_item_solr_admin', 'item_solr_admin_ajax_handler' ); //for auth users

function item_solr_admin_ajax_handler() {
  $data = array();
  // Handle the ajax request
  check_ajax_referer( 'item_admin_nonce' );
  $url = "https://repository.library.northeastern.edu/api/v1/files/" . $_POST['pid'] . "?solr_only=true";
  $data = get_response($url);
  $data = json_decode($data);
  wp_send_json(json_encode($data));
  wp_die();
}

function digidiss_item_shortcode_scripts() {
  global $post, $VERSION, $wp_query, $digi_PLUGIN_URL;
  if( is_a( $post, 'WP_Post' ) && (has_shortcode( $post->post_content, 'digidiss_item') || has_shortcode( $post->post_content, 'digidiss_single')) && !isset($wp_query->query_vars['digidiss_template_type']) ) {
    wp_register_script('digidiss_elevatezoom', $digi_PLUGIN_URL.'/assets/js/elevatezoom/jquery.elevateZoom-3.0.8.min.js', array( 'jquery' ));
    wp_enqueue_script('digidiss_elevatezoom');
    wp_register_script( 'digidiss_zoom', $digi_PLUGIN_URL . '/assets/js/zoom.js', array( 'jquery' ));
    wp_enqueue_script('digidiss_zoom');
    wp_register_script('digidiss_jwplayer', $digi_PLUGIN_URL.'/assets/js/jwplayer/jwplayer.js', array(), $VERSION, false );
    wp_enqueue_script('digidiss_jwplayer');
  }
}

function check_master($master){
  // Create a cURL handle
  $ch = curl_init($master);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  // Execute
  curl_exec($ch);
  // Check HTTP status code
  if (!curl_errno($ch)) {
    switch ($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
      case 200:  # OK
        return true;
        break;
      default:
        return false;
    }
  }
  curl_close($ch);
}
add_action( 'wp_enqueue_scripts', 'digidiss_item_shortcode_scripts');
