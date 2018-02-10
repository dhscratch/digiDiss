<?php
/* adds shortcode */
add_shortcode( 'digidiss_collection_playlist', 'digidiss_collection_playlist' );
add_shortcode( 'digidiss_media', 'digidiss_collection_playlist' );
function digidiss_collection_playlist($atts){
  global $errors;
  $cache = get_transient(md5('digidiss'.serialize($atts)));
  if($cache) {
    return $cache;
  }
    $collection = array_map('trim', explode(',', $atts['id']));
    $playlists = '';
    if (isset($atts['height']) && $atts['height'] != 0){
      $height = $atts['height'];
    } else {
      $height = '270';
    }
    if (isset($atts['width']) && $atts['width'] != 0){
      $width = $atts['width'];
    } else {
      $width = '100%';
    }
    if (isset($atts['aspectratio'])){
      $aspectratio = $atts['aspectratio'];
    } else {
      $aspectratio = '16:9';
    }
    if (isset($atts['skin'])){
      $skin = $atts['skin'];
    }
    if (isset($atts['listbarwidth']) && $atts['listbarwidth'] != 0){
      $listbarwidth = $atts['listbarwidth'];
    } else {
      $listbarwidth = '250';
    }
    foreach($collection as $video){
      $repo = digidiss_get_repo_from_pid($video);
      if ($repo != "digidiss"){$pid = explode(":",$video); $pid = $pid[1];} else {$pid = $video;}
      $poster;
      if ($repo == "digidiss"){
        $url = "https://repository.library.northeastern.edu/api/v1/files/" . $video . "?solr_only=true";
        $data = get_response($url);
        $data = json_decode($data);
        $data = $data->_source;
        $objects_url = "https://repository.library.northeastern.edu/api/v1/files/" . $video . "/content_objects";
        $objects_data = get_response($objects_url);
        $objects_data = json_decode($objects_data);
        $data = (object) array_merge((array) $data, (array) $objects_data);
        if (!isset($data->error)){

          $poster[] = "https://repository.library.northeastern.edu".$data->fields_thumbnail_list_tesim[4];
          $this_poster = "https://repository.library.northeastern.edu".$data->fields_thumbnail_list_tesim[4];
          $title = $data->title_info_title_ssi;
          $title = str_replace('"','\"', $title);
          foreach($data->canonical_object as $key=>$val){
            $pid = $key;
            $pid = explode("/", $pid);
            $pid = end($pid);
            $encoded = str_replace(':','%3A', $pid);
            $dir = substr(md5("info:fedora/".$pid."/content/content.0"), 0, 2);
            $full_pid = "info%3Afedora%2F".$encoded."%2Fcontent%2Fcontent.0";
            if ($val == 'Audio File'){
              $rtmp = 'rtmp://libwowza.neu.edu:1935/vod/_definst_/MP3:datastreamStore/cerberusData/newfedoradata/datastreamStore/'.$dir.'/info%3Afedora%2F'.$encoded.'%2Fcontent%2Fcontent.0';
              $playlist = 'http://libwowza.neu.edu:1935/vod/_definst_/datastreamStore/cerberusData/newfedoradata/datastreamStore/'.$dir.'/MP3:"+encodeURIComponent("'. $full_pid .'")+"/playlist.m3u8';
              $no_flash = 'http://libwowza.neu.edu/datastreamStore/cerberusData/newfedoradata/datastreamStore/' . $dir . '/' . urlencode($full_pid);
              $type = 'MP3';
              $provider = 'sound';
            }
            if ($val == 'Video File'){
              $rtmp = 'rtmp://libwowza.neu.edu:1935/vod/_definst_/MP4:datastreamStore/cerberusData/newfedoradata/datastreamStore/'.$dir.'/info%3Afedora%2F'.$encoded.'%2Fcontent%2Fcontent.0';
              $playlist = 'http://libwowza.neu.edu:1935/vod/_definst_/datastreamStore/cerberusData/newfedoradata/datastreamStore/'.$dir.'/MP4:"+encodeURIComponent("'. $full_pid .'")+"/playlist.m3u8';
              $no_flash = 'http://libwowza.neu.edu/datastreamStore/cerberusData/newfedoradata/datastreamStore/'.$dir.'/'.urlencode($full_pid);
              $type = 'MP4';
              $provider = 'video';
            }
            $playlists .= '{ sources: [ { file: "' . $no_flash . '", type: "'.strtolower($type).'" },';
            $playlists .= '{ file: "' .  $rtmp . '"},';
            $playlists .= ' { file: "' . $playlist . '"},';
            $playlists .= ' ], image: "' . $this_poster . '", title: "' . $title . '" },';
          }
        } else {
          return $errors['shortcodes']['fail'];
        }

      }
      if ($repo == "wp"){
        $post = get_post($pid);
        $this_poster = "";
        $poster[0] = "";
        $title = $post->post_title;
        $title = str_replace('"','\"', $title);
        if (strpos($post->post_mime_type, "video") !== false){
          $provider = 'sound';
        }
        if (strpos($post->post_mime_type, "audio") !== false){
          $provider = 'audio';
        }
        $playlists .= '{sources:[{file:"'.$post->guid.'",title:"'.$title.'"}],title:"'.$title.'"}';
        $playlist = $post->guid;
      }

      $download = 'download';

    }
    $pid_selector = "digi-item-video-".str_replace(':', "-", $pid);
    $cache_output = '<div id="'.$pid_selector.'">
        <img style="width: 100%;" src="' . $poster[0] .'" />
      </div>
      <script type="text/javascript">
      jwplayer.key="6keHwedw4fQnScJOPJbFMey9UxSWktA1KWf1vIe5fGc=";
        var primary = "html5";
        var provider = "'.$provider.'";
        var is_chrome = navigator.userAgent.indexOf(\'Chrome\') > -1;
        var is_safari = navigator.userAgent.indexOf("Safari") > -1;
        if ((is_chrome)&&(is_safari)) {is_safari=false;}
        jQuery(document).ready(function($){
        jwplayer("'.$pid_selector.'").setup({
          width: "'.$width.'",
          height: "'.$height.'",
          rtmp: { bufferlength: 5 } ,
          image: "'.$this_poster.'",
          provider: "'.$provider.'",
          androidhls: "true",
          hlshtml: "true",
          aspectratio:"'.$aspectratio.'",';
          if (isset($skin)){
            $cache_output .= 'skin: "'.$skin.'",';
          }
          $cache_output .='primary: primary,';
    if(count($collection) > 1){
        $cache_output .= 'listbar: {
          position: "right",
          size: 250,
          layout: "basic"
        },';
    }
      $cache_output .= 'playlist: [ '. $playlists . ']
        });
        jwplayer("'.$pid_selector.'").on("ready", function() {
         if (is_safari){
           //defaulting to m3u8 stream for safari since it functions better
           jwplayer("'.$pid_selector.'").load([{image: "'.$this_poster.'", sources:[{ file: "'.$playlist.'"}]}]);
           // Set poster image for video element to avoid black background for audio-only programs.
           $("'.$pid_selector.' video").attr("poster", "'.$this_poster.'");
         }
        });
        function errorMessage() {
          $("#digi-item-video").before("<div class=\'alert alert-warning\'>'.$errors['item']['jwplayer_fail'].'<br /><strong>Error Message:</strong> "+e.message+"</div>");
        }
        jwplayer("'.$pid_selector.'").on(\'error\', function(){
          errorMessage();
        });
        jwplayer("'.$pid_selector.'").on(\'setupError\', function(){
          errorMessage();
        });
        jwplayer("'.$pid_selector.'").on(\'buffer\', function() {
          theTimeout = setTimeout(function(e) {
            errorMessage(e);
          }, 5000);
        });
        jwplayer("'.$pid_selector.'").on("play", function(){
           clearTimeout(theTimeout);
         });
      });
      </script>';
    $cache_time = 1000;
    set_transient(md5('digidiss'.serialize($atts)) , $cache_output, $cache_time * 60);
    return $cache_output;

}

function digidiss_video_shortcode_scripts() {
    global $post, $VERSION, $wp_query, $digi_PLUGIN_URL;
    if( is_a( $post, 'WP_Post' ) && (has_shortcode( $post->post_content, 'digidiss_collection_playlist') || has_shortcode( $post->post_content, 'digidiss_media')) && !isset($wp_query->query_vars['digidiss_template_type']) ) {
      wp_register_script('digidiss_jwplayer7', $digi_PLUGIN_URL . '/assets/js/jwplayer/jwplayer.js', array(), $VERSION, false );
      wp_enqueue_script('digidiss_jwplayer7');
    }
}
add_action( 'wp_enqueue_scripts', 'digidiss_video_shortcode_scripts');
