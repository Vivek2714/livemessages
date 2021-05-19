<?php
/*
Plugin Name: Live messages GF Customization
Description: Gravity form customization
version: 1.0
Author: Vivek V.
*/

class customizedLiveMessages{

  public $customTableName = 'gform_entries_custom'; 

  public $form =[
      'id' => '1',
      'fields' => [
          'id'                     => 'id',
          'latitude'               => 14,
          'longitude'              => 15,
          'name'                   => 2,
          'street'                 => 3,
          'house_number'           => 20,
          'zip'                    => 7,
          'city'                   => 9,
          'state'                  => 10,
          'country'                => 11,
          'screen'                 => 12,
          'start_date'             => 19,
          'digistore_affiliate_id' => 16,
          'digistore_package'      => 17
      ]
  ];

  const ERROR_CODE   = 0;
  const SUCCESS_CODE = 1;

  public function __construct(){
    ## check if Gravity forms plugin exists
    if( !class_exists('GFAPI') ){
      return;
    }
    ## Embed styles and scripts
    add_action( 'wp_enqueue_scripts', array( $this, 'lm_scripts' ) );

    ## Summary board shortcode
    add_shortcode( 'live-board-summary', array( $this, 'live_board_summary' ) );

    ## Autopopulate locations
    add_filter( 'gform_pre_render', array( $this, 'auto_populate_locations' ) );

    ## Custom redirect to payment page
    // add_action( 'gform_after_submission', array( $this, 'after_submission_completed' ), 10, 2 );

    ## Change submit button text
    add_filter( 'gform_submit_button', array( $this, 'change_submit_button_text' ), 10, 2 );

    ## Ajax handling
    add_action( 'wp_ajax_search_locations', array( $this, 'search_locations' ) );
    add_action( 'wp_ajax_nopriv_search_locations', array( $this, 'search_locations' ) );

    /* Insert Gravity form entries into custom table */
    if( isset($_GET['insert-entries-into-custom-table']) ){
        add_action( 'init', [ $this, 'insert_entries' ] );
    }

    // $this->get_Filter_entries( 47.4097672, 15.271513, 50 );

  }

  ## send ajax response
  public function send_response( $code, $message, $data = [] ){
    echo json_encode( [
      'status' => [
        'code'    => $code,
        'message' => $message,
      ],
      'body'  => $data
    ] );
    wp_die();
  }

  ## Adding styles and scripts
  public function lm_scripts() {
    wp_enqueue_style( 'lm-style', plugin_dir_url( __FILE__ ). 'css/style.css?'.time() );
    wp_enqueue_script( 'lm-js', plugin_dir_url( __FILE__ ). 'js/script.js', array('jquery'), '', true );
    wp_localize_script( 'lm-js', 'livemessagesObj', array( 
        'adminAjax' => admin_url( 'admin-ajax.php' ) 
    ) );
  }

  ## Live board summary
  public function live_board_summary($args){
    $output = "";
    if( !empty($args['label']) ){
      $output .="<label class='gfield_label'>".$args['label']."</label>";
    }
    // $output .="<center class='custom-html'>
    //           <span class='selected-locations-preview'>-</span> Livepoints<br>
    //           <span class='screens-available-preview'>-</span> Monitore<br>
    //           <span class='duration-preview'>-</span> Tage<br>
    //           <span class='add-views-preview'>-</span> Sichtkontakte<br>
    //           </center>";

    $output .="<center class='custom-html'>
                <table>
                  <tbody>
                  <tr>
                    <td><span class='selected-locations-preview'>-</span></td>
                    <td> Livepoints</td>
                  </tr>
                  <tr>
                    <td><span class='screens-available-preview'>-</span></td>
                    <td> Monitore</td>
                  </tr>
                  <tr>
                    <td><span class='duration-preview'>-</span></td>
                    <td> Tage</td>
                  </tr>
                  <tr>
                    <td><span class='add-views-preview'>-</span></td>
                    <td> Anzeigeneinblendungen</td>
                  </tr>
                </tbody>
                </table>
              </center>";
    $output .="<center style='background: #37424e;color:#fff;font-size:20px;padding: 20px;margin-bottom: 10px; font-weight: bold'>€ <span class='amount-preview'>-</span></center>";
    return $output;
  }

  ## Get form entries
  public function get_form_entries( $formId = 0, $filter = [], $columns = false ){

    if($formId != 0){ // 0 is a valid form ID
      // Get form object
      $form = GFAPI::get_form($formId);
      if( $form === false ){
        return [];
      }
    }

    $tempEntries   = [];

    $search_criteria['status'] = 'active';
    if( !empty($filter) ){
      foreach( $filter as $key => $value){
        
        if( $key == "mode"){
          $search_criteria['field_filters']['mode'] = $value;
          continue;
        }

        if( gettype($value) === 'array' ){
          $value['key'] = $key;
          $search_criteria['field_filters'][] = $value;
          continue;
        }
        $search_criteria['field_filters'][] = array( 'key' => $key, 'value' => $value  );
      }
    }

    ## Related AR-4456
    $offset = 0;  
    $requestAtOnce = 100;
    $entries = [];  
    while( 1 < $requestAtOnce ){
      ## Paging parameters
      $paging = [ 
        'offset'    => $offset, 
        'page_size' => $requestAtOnce  
      ];

      // Get entries
      $fetchEntries = GFAPI::get_entries( $formId, $search_criteria, [], $paging );
      $entries      = array_merge( $entries, $fetchEntries );

      ## Break if total count is less than total requred, It means we don't have more entries to fetch
      if( count($fetchEntries) < $requestAtOnce ){
        break;
      }

      $offset = $offset + $requestAtOnce;
    }
    
    if( !empty( $entries ) ){
      foreach( $entries as $entry ){
        if( empty($columns) ){
          $tempEntries[] = $entry;
          continue;              
        }

        if(in_array( gettype($columns)  , ["integer", "string", "double"] )){
          if(isset( $entry[ $columns ] )){
            $tempEntries[] = $entry[ $columns ];
          }
          continue;                
        }

        $temp = [];
        foreach($columns as $sColumn){
          if(isset($entry[$sColumn])){
            $temp[ $sColumn ] = $entry[$sColumn];
          }
        }
        $tempEntries[] = $temp;
      }
    }
    return $tempEntries;
  }
  
  ## Pre render all locations
  public function auto_populate_locations( $form ) {
    
    $search  = [];
    $columns = [ 'id', '2', '7', '12' ];    ## ID, Name, Zip, Screens
    $locations = $this->get_form_entries( 1 , $search, $columns);
    if( empty($locations) ){
      return $form;
    }

    foreach( $form['fields'] as &$field ) {
      if( in_array( 'all-locations', explode( " ", $field->cssClass ) ) ){
        foreach ( $locations as $location ) {
          if(empty($location['12'])){
            continue;
          }
          $items[] = array( 'value' => $location['12'], 'text' => $location['2'] );
        }
        $field->choices = $items;
      }
    }
    return $form;
  }

  ## Redirect to payment page
  public function after_submission_completed(){
    wp_redirect('https://www.digistore24.com/product/388487/?first_name=Steffen&last_name=Knoedler&email=uwe.hiltmann@uhdigital.net&company=airtango+AG&street=');
  }

  ## Change button text
  public function change_submit_button_text( $button, $form ) {
      return "<button style='float: right;' class='button gform_button' id='gform_submit_button_{$form['id']}'><span>BUCHUNG ABSCHLIESSEN</span></button>";
  }

  ## Getting Gravity forms all entries
  public function get_all_entries( $formId = 0, $filter = [], $columns = false ){

    if($formId != 0){ // 0 is a valid form ID
      // Get form object
      $form = GFAPI::get_form($formId);
      if( $form === false ){
        return [];
      }
    }

    $tempEntries   = [];

    $search_criteria['status'] = 'active';
    if( !empty($filter) ){
      foreach( $filter as $key => $value){
        
        if( $key == "mode"){
          $search_criteria['field_filters']['mode'] = $value;
          continue;
        }

        if( gettype($value) === 'array' ){
          $value['key'] = $key;
          $search_criteria['field_filters'][] = $value;
          continue;
        }
        $search_criteria['field_filters'][] = array( 'key' => $key, 'value' => $value  );
      }
    }

    ## Related AR-4456
    $offset = 0;  
    $requestAtOnce = 100;
    $entries = [];  
    while( 1 < $requestAtOnce ){
      ## Paging parameters
      $paging = [ 
        'offset'    => $offset, 
        'page_size' => $requestAtOnce  
      ];

      // Get entries
      $fetchEntries = GFAPI::get_entries( $formId, $search_criteria, [], $paging );
      $entries      = array_merge( $entries, $fetchEntries );

      ## Break if total count is less than total requred, It means we don't have more entries to fetch
      if( count($fetchEntries) < $requestAtOnce ){
        break;
      }

      $offset = $offset + $requestAtOnce;
    }
    
    if( !empty( $entries ) ){
      foreach( $entries as $entry ){
        if( empty($columns) ){
          $tempEntries[] = $entry;
          continue;              
        }

        if(in_array( gettype($columns)  , ["integer", "string", "double"] )){
          if(isset( $entry[ $columns ] )){
            $tempEntries[] = $entry[ $columns ];
          }
          continue;                
        }

        $temp = [];
        foreach($columns as $sColumn){
          if(isset($entry[$sColumn])){
            $temp[ $sColumn ] = $entry[$sColumn];
          }
        }
        $tempEntries[] = $temp;
      }
    }
    return $tempEntries;
  }

  ## Insert Gravity form entries into custom table
  public function insert_entries(){
      
    global $wpdb;
    $fields = $this->form['fields'];

    ## Get Gravity form all entries
    $search  = $columns = [];                
    foreach( $fields as $fieldKey => $fieldID ){
      $columns[] = $fieldID;
    }

    $entries = $this->get_all_entries( $this->form['id'], $search, $columns );
    if( empty($entries) ){
      return;
    }

    ## Insert Gravity form entries into custom table
    foreach( $entries as $entry ){
      $wpdb->insert(
        $wpdb->prefix.$this->customTableName,
        [
            'entry_id'  => rgar( $entry, 'id' ),
            'latitude'  => str_replace( ",", ".", rgar( $entry, $fields['latitude'] ) ),
            'longitude' => str_replace( ",", ".", rgar( $entry, $fields['longitude'] ) ),
            'name'                   => rgar( $entry, $fields['name'] ),
            'street'                 => rgar( $entry, $fields['street'] ),
            'house_number'           => rgar( $entry, $fields['house_number'] ),
            'zip'                    => rgar( $entry, $fields['zip'] ),
            'city'                   => rgar( $entry, $fields['city'] ),
            'state'                  => rgar( $entry, $fields['state'] ),
            'country'                => rgar( $entry, $fields['country'] ),
            'screen'                 => rgar( $entry, $fields['screen'] ),
            'start_date'             => rgar( $entry, $fields['start_date'] ),
            'digistore_affiliate_id' => rgar( $entry, $fields['digistore_affiliate_id'] ),
            'digistore_package'      => rgar( $entry, $fields['digistore_package'] ) 
        ]
      );
    }
  }

  ## Get entries based on lat and long
  public function get_Filter_entries( $lat = 0, $long = 0, $distance = 50 ){
    global $wpdb;
    $table = $wpdb->prefix.$this->customTableName;
    $sql = "SELECT
            id,
            entry_id,
            latitude,
            longitude,
            name,
            street,
            house_number,
            zip,
            city,
            state,
            country,
            screen,
            start_date,
            digistore_affiliate_id,
            digistore_package,
            ROUND(
              ( 
                6371 * acos (
                cos ( radians($lat) )
                * cos( radians( latitude ) )
                * cos( radians( longitude ) - radians($long) )
                + sin ( radians($lat) )
                * sin( radians( latitude ) )
              )
            ), 2) AS distance
          FROM
            $table
          HAVING 
            distance < $distance
          ORDER BY distance ASC
        ";
    $entries = $wpdb->get_results($sql);
    return $entries;
  }

  ## Get search entries
  public function search_locations(){

    $latitude  = isset( $_POST['latitude'] ) ? $_POST['latitude'] : null;
    $longitude = isset( $_POST['longitude'] ) ? $_POST['longitude'] : null;

    ## Validation
    if( 
      empty($latitude) || 
      empty($longitude) 
    ){
      $this->send_response( self::ERROR_CODE, "Validation error" );
    }

    $radius = !empty( $_POST['radius'] ) ? $_POST['radius'] : 50;    
    $data   = $this->get_Filter_entries( $latitude, $longitude, $radius );
    $this->send_response( self::SUCCESS_CODE, "Results fetched", $data );
  }

}

add_action( 'plugins_loaded', function() {
  new customizedLiveMessages();
});