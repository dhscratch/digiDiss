<?php

add_action( 'wp_ajax_reload_filtered_set', 'reload_filtered_set_ajax_handler' ); //for auth users
add_action( 'wp_ajax_nopriv_reload_filtered_set', 'reload_filtered_set_ajax_handler' ); //for nonauth users
function reload_filtered_set_ajax_handler()
{
  $collection_pid = digidiss_get_pid();
    if ($_POST['reloadWhat'] == "mapReload") {
        echo digidiss_map($_POST['atts'], $_POST['params']);
    }
    else if($_POST['reloadWhat'] == "facetReload") {
        if (isset($_POST['atts']['collection_id'])) {
            $url = "https://repository.library.northeastern.edu/api/v1/search/geo/".$collection_pid."?per_page=10";
            if (isset($_POST['params']['f'])) {
                foreach ($_POST['params']['f'] as $facet => $facet_val) {
                    $url .= "&f[" . $facet . "][]=" . urlencode($facet_val);
                }
            }
            if (isset($_POST['params']['q']) && $_POST['params']['q'] != ''){
                $url .= "&q=". urlencode(sanitize_text_field($_POST['params']['q']));
            }
            $data1 = get_response($url);
            $data1 = json_decode($data1);
            $facets_info_data = $data1;
            wp_send_json($facets_info_data);
        }
    }
    die();
}


add_action( 'wp_ajax_reloadRemainingMap', 'reloadRemainingMap_ajax_handler' ); //for auth users
add_action( 'wp_ajax_nopriv_reloadRemainingMap', 'reloadRemainingMap_ajax_handler' ); //for nonauth users
function reloadRemainingMap_ajax_handler()
{
    $a = get_post($_POST['post_id'])->post_content;
    $parsed_a = shortcode_parse_atts($a);
    echo digidiss_map($parsed_a, $_POST['params']);
    die();
}

/* adds shortcode */
add_shortcode( 'digidiss_map', 'digidiss_map' );
function digidiss_map( $atts , $params){
    global $errors, $digi_PLUGIN_URL;
  $cache = get_transient(md5('PREFIX'.serialize($atts)));
  if($cache != NULL && (!(isset($params)) || $params == NULL) && !(isset($atts['collection_id']))) {
    return $cache;
  }

    if(!isset($atts['collection_id'])) {
        $items = array_map('trim', explode(',', $atts['id']));
    }
  $map_api_key = digidiss_get_map_api_key();
  $map_project_key = digidiss_get_map_project_key();
  $story = isset($atts['story']) ? $atts['story'] : "no";
  $map_html = "";


  $shortcode = "<div id='map' data-story='".$story."' data-map_api_key='".$map_api_key."' data-map_project_key='".$map_project_key."'";
  foreach($atts as $key => $value){
        if(preg_match('/(.*)_color_desc_id/',$key)){
            $shortcode .= " data-".$key."='".$atts[$key]."'";
        }
        if(preg_match('/(.*)_color_hex/',$key)){
            $shortcode .= " data-".$key."='".$atts[$key]."'";
        }
    }

  /*
    If collection_id attribute is set, then load the digi-dissitems directly using the search API.
  */
    $collectionItemsId = array();

    $facets_info_data = array();

    if(isset($atts['collection_id'])){

        $url = "https://repository.library.northeastern.edu/api/v1/search/geo/".digidiss_get_pid()."?per_page=10";

        if(isset($params['page_no'])){
            $url .= "&page=" . $params['page_no'];
        }

        if (isset($params['f'])) {
            foreach ($params['f'] as $facet => $facet_val) {
                $url .= "&f[" . $facet . "][]=" . urlencode($facet_val);
            }
        }

        if (isset($params['q']) && $params['q'] != ''){
            $url .= "&q=". urlencode(sanitize_text_field($params['q']));
        }

        $data1 = get_response($url);
        $data1 = json_decode($data1);
        $facets_info_data = $data1;

        $num_pages = $data1->pagination->table->num_pages;

        if($num_pages == 0){
            return "No Result";
        }
        if(isset($params['page_no']) && $params['page_no'] > $num_pages){
            return "All_Pages_Loaded";
        }

        $docs2 = $data1->response->response->docs;
        foreach($docs2 as $docItem){
            $collectionItemsId [] = $docItem->id;
        }
        $items = $collectionItemsId;
    }

    foreach($items as $item){
    $repo = digidiss_get_repo_from_pid($item);
    if ($repo != "digidiss"){$pid = explode(":",$item); $pid = $pid[1];} else {$pid = $item;}
    if ($repo == "digidiss"){
      $url = "https://repository.library.northeastern.edu/api/v1/files/" . $item . "?solr_only=true";
      $data = get_response($url);
      $data = json_decode($data);
      if (!isset($data->error)){
        $data = $data->_source;
        $pid = $data->id;

        $coordinates = "";
        if(isset($data->subject_cartographics_coordinates_tesim)) {
          $coordinates = $data->subject_cartographics_coordinates_tesim[0];
        } else if (isset($data->subject_geographic_tesim)){
          $location = $data->subject_geographic_tesim[0];
          $locationUrl = "http://maps.google.com/maps/api/geocode/json?address=" . urlencode($location);
          $locationData = get_response($locationUrl);
          $locationData = json_decode($locationData);
          if (!isset($locationData->error)) {
            $coordinates = $locationData->results[0]->geometry->location->lat . "," . $locationData->results[0]->geometry->location->lng;
          }
        } else { //no geo data, skip it
          $coordinates = "";
        }
        if ($coordinates == ""){
          continue;
        }

        $title = $data->full_title_ssi;
        $permanentUrl = digidiss_home_url() . "item/".$pid;
        $map_html .= "<div class='coordinates' data-pid='".$pid."' data-url='".$permanentUrl."' data-coordinates='".$coordinates."' data-title='".htmlspecialchars($title, ENT_QUOTES, 'UTF-8')."'";
        if (isset($atts['metadata'])){
          $map_metadata = '';
          $metadata = explode(",",$atts['metadata']);
          foreach($metadata as $field){
             if (isset($data->$field)){
               $this_field = $data->$field;
              if (isset($this_field)){
                if (is_array($this_field)){
                  foreach($this_field as $val){
                    if (is_array($val)){
                      $map_metadata .= implode("<br/>",$val) . "<br/>";
                    } else {
                      $map_metadata .= $val ."<br/>";
                    }
                  }
                } else {
                  $map_metadata .= $this_field . "<br/>";
                }
              }
            }
          }
          $map_html .= " data-metadata='".$map_metadata."'";
        }


        $canonical_object = "";
        if (isset($data->canonical_class_tesim)){
          if ($data->canonical_class_tesim[0] == "AudioFile" || $data->canonical_class_tesim[0] == "VideoFile"){
            $objects_url = "https://repository.library.northeastern.edu/api/v1/files/" . $item . "/content_objects";
            $objects_data = get_response($objects_url);
            $objects_data = json_decode($objects_data);
            $data = (object) array_merge((array) $data, (array) $objects_data);
            if (isset($objects_data->canonical_object)){
              $canonical_object = insert_jwplayer($key, $val, $data, $data->fields_thumbnail_list_tesim[2]);
            }
          } else {
            $canonical_object = '<img src="http://repository.library.northeastern.edu'.$data->fields_thumbnail_list_tesim[2].'"/>';
          }
        }
        $map_html .= " data-media-content='".str_replace("'","\"", htmlentities($canonical_object))."'";

        $map_html .= "></div>";

      } else {
        $map_html = $errors['shortcodes']['fail'];
      }
    }
    if ($repo == "wp"){
      $post = get_post($pid);
      $url = $post->guid;
      $title = $post->post_title;
      $description = $post->post_excerpt;
      $custom = get_post_custom($pid);
      if (isset($custom['_map_coords'])){
        $coordinates = $custom['_map_coords'][0];
      } else {
        $coordinates = "";
        continue;
      }
      $data = new StdClass;
      $data->full_title_ssi = array($post->post_title);
      $data->abstract_tesim = array($post->post_excerpt);
      $data->date_ssi = array($custom['_timeline_date'][0]);
      $data->canonical_object = new StdClass;
      $url = $post->guid;
      if (strpos($post->post_mime_type, "audio") !== false){
        $type = "AudioFile";
      } else if (strpos($post->post_mime_type, "video") !== false){
        $type = "VideoFile";
      } else {
        $type = "ImageMasterFile";
      }
      $data->canonical_class_tesim = $type;
      $data->canonical_object->$url = $type;
      $data->id=$post->ID;
      if(!is_numeric($coordinates[0])) {
        $location = $coordinates;
        $locationUrl = "http://maps.google.com/maps/api/geocode/json?address=" . urlencode($location);
        $locationData = get_response($locationUrl);
        $locationData = json_decode($locationData);
        if (!isset($locationData->error)) {
          $coordinates = $locationData->results[0]->geometry->location->lat . "," . $locationData->results[0]->geometry->location->lng;
        }
      }
      $permanentUrl = digidiss_home_url() . "item/wp:".$post->ID;
      $map_html .= "<div class='coordinates' data-pid='".$pid."' data-url='".$permanentUrl."' data-coordinates='".$coordinates."' data-title='".htmlspecialchars($title, ENT_QUOTES, 'UTF-8')."'";

      if (isset($atts['metadata'])){
        $map_metadata = '';
        $metadata = explode(",",$atts['metadata']);
        foreach($metadata as $field){
           if (isset($data->$field)){
             $this_field = $data->$field;
            if (isset($this_field)){
              if (is_array($this_field)){
                foreach($this_field as $val){
                  if (is_array($val)){
                    $map_metadata .= implode("<br/>",$val) . "<br/>";
                  } else {
                    $map_metadata .= $val ."<br/>";
                  }
                }
              } else {
                $map_metadata .= $this_field . "<br/>";
              }
            }
          }
        }
        $map_html .= " data-metadata='".$map_metadata."'";
      }
      $canonical_object = "";
      if (isset($data->canonical_object)){
        foreach($data->canonical_object as $key=>$val){
          if ($val == 'VideoFile' || $val == 'AudioFile'){
            $canonical_object = do_shortcode('[video src="'.$post->guid.'"]');
          } else {
            $canonical_object = '<img src="'.$post->guid.'"/>';
          }
        }
      }
      $map_html .= " data-media-content='".str_replace("'","\"", htmlentities($canonical_object))."'";

      $map_html .= "></div>";
    }

    if ($repo == "dpla"){
      $url = "http://api.dp.la/v2/items/".$pid."?api_key=dfa5171da90bf85b229bdcc9ad374932";
      $data = get_response($url);
      $data = json_decode($data);
      if (isset($data->docs[0]->object)){
        $url = $data->docs[0]->object;
      } else {
        $url = "https://dp.la/info/wp-content/themes/berkman_custom_dpla/images/logo.png";
      }
      $title = $data->docs[0]->sourceResource->title;
      if (isset($data->docs[0]->sourceResource->description)){
        $description = $data->docs[0]->sourceResource->description;
      } else {
        $description = "";
      }
      if (!is_array($title)){
        $title = array($title);
      }
      $data->full_title_ssi = $title;
      $data->abstract_tesim = $description;
      $cre = "Creator,Contributor";
      if (isset($data->docs[0]->sourceResource->creator)){
        $data->creator_tesim = $data->docs[0]->sourceResource->creator;
      } else {
        $data->creator_tesim = "";
      }
      $date = "Date Created";
      $data->key_date_ssi = isset($data->docs[0]->sourceResource->date->displayDate) ? $data->docs[0]->sourceResource->date->displayDate : array();
      $data->canonical_object = new StdClass;
      $data->canonical_object->$url = "Master Image";
      if (!isset($data->docs[0]->sourceResource->spatial)){
        $coordinates = "";
        continue;
      }

      if(!isset($data->docs[0]->sourceResource->spatial[0]->coordinates)) {
        $location = $data->docs[0]->sourceResource->spatial[count($data->docs[0]->sourceResource->spatial)-1]->name;// . $data->docs[0]->sourceResource->spatial[0]->state;
        $locationUrl = "http://maps.google.com/maps/api/geocode/json?address=" . urlencode($location);
        $locationData = get_response($locationUrl);
        $locationData = json_decode($locationData);
        if (!isset($locationData->error)) {
          $coordinates = $locationData->results[0]->geometry->location->lat . "," . $locationData->results[0]->geometry->location->lng;
        }
      } else {
        $coordinates = $data->docs[0]->sourceResource->spatial[0]->coordinates;
      }
      $permanentUrl = digidiss_home_url() . "item/dpla:".$pid;
      $map_html .= "<div class='coordinates' data-pid='".$pid."' data-url='".$permanentUrl."' data-coordinates='".$coordinates."' data-title='".htmlspecialchars($title[0], ENT_QUOTES, 'UTF-8')."'";

      if (isset($atts['metadata'])){
        $map_metadata = '';
        $metadata = explode(",",$atts['metadata']);
        foreach($metadata as $field){
           if (isset($data->$field)){
             $this_field = $data->$field;
            if (isset($this_field)){
              if (is_array($this_field)){
                foreach($this_field as $val){
                  if (is_array($val)){
                    $map_metadata .= implode("<br/>",$val) . "<br/>";
                  } else {
                    $map_metadata .= $val ."<br/>";
                  }
                }
              } else {
                $map_metadata .= $this_field . "<br/>";
              }
            }
          }
        }
        $map_html .= " data-metadata='".$map_metadata."'";
      }
      $canonical_object = '<img src="'.$url.'"/>';
      $map_html .= " data-media-content='".str_replace("'","\"", htmlentities($canonical_object))."'";

      $map_html .= "></div>";
    }
  }


  if (isset($atts['custom_map_urls']) && ($atts['custom_map_urls'] != '')) {
    $custom_map_urls = explode(",",$atts['custom_map_urls']);
    $custom_map_titles = explode(",",$atts['custom_map_titles']);
    $custom_map_descriptions = explode(",",$atts['custom_map_descriptions']);
    $custom_map_locations = explode(",",$atts['custom_map_locations']);
    $custom_map_color_groups = explode(",",$atts['custom_map_color_groups']);
    foreach($custom_map_urls as $key=>$value) {
      $url = $value;
      $title = $custom_map_titles[$key];
      $title = trim($title,'\'');
      $description = $custom_map_descriptions[$key];
      $description = trim($description,'\'');
      $location = $custom_map_locations[$key];
      $colorGroup = $custom_map_color_groups[$key];

      $coordinates = "";
      $locationUrl = "http://maps.google.com/maps/api/geocode/json?address=" . urlencode($location);
      $locationData = get_response($locationUrl);
      $locationData = json_decode($locationData);
      if (!isset($locationData->error)) {
        $coordinates = $locationData->results[0]->geometry->location->lat . "," . $locationData->results[0]->geometry->location->lng;
      }

      $map_html .= "<div class='custom-coordinates' data-url=".$url." data-coordinates='".$coordinates."' data-title='".htmlspecialchars($title, ENT_QUOTES, 'UTF-8')."' data-description='".htmlspecialchars($description, ENT_QUOTES, 'UTF-8')."' data-colorGroup=".$colorGroup."";
      $map_html .= "></div>";
    }
  }

  $shortcode .= ">".$map_html."</div>";
  $cache_output = $shortcode;
  $cache_time = 1000;
  set_transient(md5('PREFIX'.serialize($atts)) , $cache_output, $cache_time * 60);

    if(isset($atts['collection_id'])) {
        wp_register_script('digidiss_map_col', $digi_PLUGIN_URL . '/assets/js/mapCollection.js', array('jquery'));
        wp_enqueue_script('digidiss_map_col');

        $reload_filtered_set_digi_nonce = wp_create_nonce('reload_filtered_set_digidiss');

        $map_nonce = wp_create_nonce('map_nonce');

        $map_obj = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => $map_nonce,
            'home_url' => digidiss_home_url()
        );

        $facets_info_data_obj = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => $reload_filtered_set_digi_nonce,
            'data' => $facets_info_data,
            'home_url' => digidiss_home_url(),
            "atts" => $atts,
            "map_obj" => $map_obj
        );
        wp_localize_script('digidiss_map_col', 'facets_info_data_obj', $facets_info_data_obj);
    }

    return $shortcode;
}

function digidiss_map_shortcode_scripts() {

  global $post, $wp_query, $digi_PLUGIN_URL;

    if( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'digidiss_map') && !isset($wp_query->query_vars['digidiss_template_type']) ) {
    wp_register_script('digidiss_leaflet',
        $digi_PLUGIN_URL .'/assets/js/leaflet/leaflet.js',
        array( 'jquery' ));
    wp_enqueue_script('digidiss_leaflet');

    wp_register_script('digidiss_leaflet_marker_cluster',
        $digi_PLUGIN_URL.'/assets/js/leaflet/leaflet.markercluster-src.js',
        array('jquery', 'digidiss_leaflet'));
    wp_enqueue_script('digidiss_leaflet_marker_cluster');

    wp_register_script('digidiss_leaflet_message_box',
        $digi_PLUGIN_URL.'/assets/js/leaflet/leaflet.messagebox-src.js',
        array('jquery', 'digidiss_leaflet'));
    wp_enqueue_script('digidiss_leaflet_message_box');

    wp_register_script('digidiss_leaflet_easy_button',
        $digi_PLUGIN_URL.'/assets/js/leaflet/leaflet.easybutton-src.js',
        array('jquery', 'digidiss_leaflet'));
    wp_enqueue_script('digidiss_leaflet_easy_button');

    wp_register_style('digidiss_leaflet_css',
        $digi_PLUGIN_URL.'/assets/css/leaflet.css');
    wp_enqueue_style('digidiss_leaflet_css');
    wp_register_script( 'digidiss_map',
        $digi_PLUGIN_URL. '/assets/js/map.js',
        array( 'jquery' ));
    wp_enqueue_script('digidiss_map');

    $map_nonce = wp_create_nonce( 'map_nonce' );
    $temp =  shortcode_parse_atts($post->post_content);
    $collectionSet = "";

    if(isset($temp['collection_id']) && $temp['collection_id'] != ''){
        $collectionSet = "checked";
    }
    $map_obj = array(
      'ajax_url' => admin_url('admin-ajax.php'),
      'nonce'    => $map_nonce,
      'home_url' => digidiss_home_url(),
      'post_id' => $post->ID,
      'collectionSet' => $collectionSet
    );
    wp_localize_script( 'digidiss_map', 'map_obj', $map_obj );
  }
}
add_action( 'wp_enqueue_scripts', 'digidiss_map_shortcode_scripts');
