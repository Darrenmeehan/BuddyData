<?php

// create api slug
function buddydata_create_api_slug() {
  global $wp, $wp_query;

  $page_slug = 'api';

  //check if user is requesting our slug
  if( strtolower( $wp->request ) == $page_slug  ){

  	include( BD_PLUGIN_DIR . 'includes/bd-api.php' );
    die();

  }

}
add_filter('template_redirect','buddydata_create_api_slug');