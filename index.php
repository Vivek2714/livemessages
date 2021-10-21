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
          'location_id'            => 8,
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
          'digistore_package'      => 17,
          'weekly_contacts'        => 25
      ]
  ];

  public $sourceForm = [
    'id'     => 2,
    'fields' => [
      'logo-image'   => 41,
      'small-image'  => 95,
      'big-image'    => 96,
      'text-line-1'  => 33,
      'text-line-2'  => 36,
      'qr-code-link' => 35,
      'location'     => 78,
      'date'         => 27,
      'package'      => 84,
      'latitude'     => 81,
      'longitude'    => 80,
      'distance'     => 76,
      // AdditionalFIelds
      '_salutation'                => 91,
      '_firstname'                 => '48_3',
      '_lastname'                  => '48_6',
      '_company'                   => 49,
      '_email'                     => 156,
      '_phone_number'              => 51,
      '_street_address'            => '47_1',
      '_address_line_2'            => '47_2',
      '_city'                      => '47_3',
      '_state'                     => '47_4',
      '_zip'                       => '47_5',
      '_country'                   => '47_6',
      '_tax_id'                    => 93,
      '_coupon_code'               => 94,
      '_user_category'             => 126,
      '_username'                  => 157,
      'domain'                     => 106,
      'iframe_url'                 => 138
    ]
  ];

  public $notFoundOrder = false;
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

    ## Login at top right
    add_shortcode( 'custom-login-lm', array( $this, 'custom_login_lm' ) );

    ## Autopopulate locations
    add_filter( 'gform_pre_render_'.$this->sourceForm['id'], array( $this, 'prepopulate_site_domain' ), 20, 2 );
    add_filter( 'gform_pre_render_'.$this->sourceForm['id'], array( $this, 'changeFormTitle' ), 99, 2 );
    // add_filter( 'gform_pre_render_'.$this->sourceForm['id'], array( $this, 'hideDigistoreID' ), 10, 2 );
    add_filter( 'gform_validation_'.$this->sourceForm['id'], [ $this, 'emailValidation' ] );

    // add_filter( 'gform_pre_render_'.$this->sourceForm['id'], array( $this, 'auto_populate_locations' ) );
    
    add_filter( 'gform_pre_render_16', array( $this, 'prepopulate_parent_domain' ), 20, 2 );
    
    add_filter( 'gform_pre_render_'.$this->sourceForm['id'], array( $this, 'prepopulate_user_orders' ), 10, 2 );
    add_action( 'gform_after_submission_'.$this->sourceForm['id'], [ $this, 'linkUserOrder' ], 10, 2 );

    ## Change submit button text
    add_filter( 'gform_submit_button', array( $this, 'change_submit_button_text' ), 10, 2 );
    add_filter( 'gform_validation_'.$this->sourceForm['id'], [ $this, 'customDateValidation' ] );

    ## Ajax handling
    add_action( 'wp_ajax_search_locations', array( $this, 'search_locations' ) );
    add_action( 'wp_ajax_nopriv_search_locations', array( $this, 'search_locations' ) );
    add_action( 'wp_ajax_create_image_preview', array( $this, 'create_image_preview' ) );
    add_action( 'wp_ajax_nopriv_create_image_preview', array( $this, 'create_image_preview' ) );

    add_action( 'wp_ajax_get_order_details', array( $this, 'get_order_details' ) );
    add_action( 'wp_ajax_nopriv_get_order_details', array( $this, 'get_order_details' ) );

    // add_filter( 'gform_validation_'.$this->form['id'], [ $this, 'customValidation' ] );
    add_filter( 'gform_entry_post_save', [ $this, 'changeUploadImages' ], 10, 2 );
    
    ## Adding iframe customize settings
    add_action('init', [ $this, 'iframeCustomizerSettings' ] );
    add_action( 'wp_head', [ $this, 'addCustomDesign' ] );

    ## Custom validation for login
    add_filter( 'gform_validation_16', [ $this, 'loginAuthentication' ] );

    ## Remove login entry
    // add_action( 'gform_after_submission_16', [ $this, 'removeLoginEntry' ], 10, 2 );

    ## Insert Gravity form entries into custom table */
    // if( isset($_GET['insert-entries-into-custom-table']) ){
    //     add_action( 'init', [ $this, 'insert_entries' ] );
    // }

    // delete_user_meta( get_current_user_id(), '_lm_orders' );

    add_action( 'init', [ $this, 'updateOrder' ] );
    
    ## Add order field
    add_action( 'show_user_profile', [$this , 'orderSettings' ] );  
    add_action( 'edit_user_profile', [$this , 'orderSettings' ] );

    ## Disable admin bar
    if( !current_user_can('administrator') ){
      /* Disable WordPress Admin Bar for all users */
      add_filter( 'show_admin_bar', '__return_false' );
    }
    
    ## Allow access in iframe
    remove_action( 'login_init', 'send_frame_options_header' );
    remove_action( 'admin_init', 'send_frame_options_header' );
    add_filter( 'mod_rewrite_rules', [$this ,'mod_rewrite_rules']);

    ## Add digistore ID
    add_filter( 'gform_pre_render_'.$this->sourceForm['id'], array( $this, 'prepopulate_digistore_ID' ), 10, 2 );

    ## Add Iframe type
    add_filter( 'gform_pre_render_'.$this->sourceForm['id'], array( $this, 'prepopulate_iframe_type' ), 10, 2 );

    ## Add Iframe type
    add_filter( 'gform_pre_render_'.$this->sourceForm['id'], array( $this, 'prepopulate_static_logo' ), 10, 2 );
  }

  ## Show orders in user profile
  public function orderSettings( $user ) {
    $tempOrders = $orders = [];
    $oldOrders = explode( ",", get_user_meta( $_GET['user_id'], '_lm_orders', true ) );
    $entry = GFAPI::get_entry( 4881 );
    // echo "<pre>";
    //   print_r($entry);
    // echo "</pre>";  
    if( !empty($oldOrders) ){
      foreach( $oldOrders as $oldOrder ){		
        if( !empty($oldOrder) ){	
          $entry = GFAPI::get_entry( $oldOrder );
          if( !is_wp_error($entry) && !empty( $entry['129'] ) && !in_array( $entry['129'], $tempOrders ) && $entry['status'] == 'active' ){
            $orders[] = 'Buchung '.$entry['129'] ;
            $tempOrders[] = $entry['129'];
          }
        }
      }
    }
  ?>
    <table class="form-table">
    <tr>
        <th><label for="Past orders"><strong><?php _e("Past orders"); ?></strong></label></th>
        <td>
            <select id="_past_orders" class="regular-text" />
                <?php foreach($orders as $order ){ ?>
                  <option><?php echo $order; ?></option>
                <?php } ?>
            </select>
        </td>
    </tr>
    </table>
  <?php }

  public function mod_rewrite_rules($rules){
    ob_start();
    ?><IfModule mod_headers.c>
  Header always edit Set-Cookie (.*) "$1; SameSite=None; Secure
</IfModule><?php
    $append = ob_get_clean();
    return $rules. $append;
  }

  ## Get field label by class name
  public function get_title_by_class( $className = "" ){

    if( empty($className) ){
      return "";
    }

    $form = GFAPI::get_form(2);
    if( $form === false ){
      return "";
    }

    $label = "";
    foreach( $form['fields'] as $field ){
      if( in_array( $className, explode( " ", $field->cssClass ) ) ){
        $label = $field->label;
      }
    }

    return $label;
  }

  ## Get field description by class name
  public function get_description_by_class( $className = "" ){

    if( empty($className) ){
      return "";
    }

    $form = GFAPI::get_form(2);
    if( $form === false ){
      return "";
    }

    $description = "";
    foreach( $form['fields'] as $field ){
      if( in_array( $className, explode( " ", $field->cssClass ) ) ){
        $description = $field->description;
      }
    }

    return $description;
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

    global $wpdb;
    $parentURL = parse_url($_SERVER['HTTP_REFERER']);
    if( !empty( $_GET['parent'] ) ){
      $parentURL['host'] = $_GET['parent'];
    }
    $postid = $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_title = '" . $parentURL['host'] . "' AND post_type='iframe-settings' " );
    
    $circleColor = '#17afc9';
    $iconImage   = 'http://livemessages.1a-lokal-marketing.de/wp-content/uploads/2021/05/airtango-map-icon-50px-1.png';

    if( !empty($postid) ){
      $circleColor = get_post_meta( $postid, 'circle_background', true );
      $icon        = get_post( get_post_meta( $postid, 'map_icon', true ) );
      $iconImage   = !empty( $icon->guid ) ? $icon->guid : $iconImage;
    }

    wp_enqueue_style( 'lm-style', plugin_dir_url( __FILE__ ). 'css/style.css?'.time() );
    // wp_enqueue_style( 'lm-fontawesome', plugin_dir_url( __FILE__ ). 'font-icon/fontawesome.min.css?'.time() );
    // wp_enqueue_style( 'lm-fontawesome', 'http://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.3/css/fontawesome.min.css?'.time() );    
    wp_enqueue_script( 'lm-js', plugin_dir_url( __FILE__ ). 'js/script.js?v='.time(), array('jquery'), '', true );
    wp_localize_script( 'lm-js', 'livemessagesObj', array( 
        'adminAjax' => admin_url( 'admin-ajax.php' ),
        'homeURL'   => home_url(),
        'circleColor' => $circleColor,
        'iconImage'   => $iconImage
    ) );
  }

  ## Live board summary
  public function live_board_summary($args){
    $output = "";
    if( !empty($args['label']) ){ 
      $output .="<label class='gfield_label'>".$args['label']."</label>";
    }

    global $wpdb;
    $domain = $this->getDomain();
    $showCOmpleteBoard = true;
    $postid = $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_title = '" .$domain. "' AND post_type='iframe-settings' " );
    if( !empty($postid) ){
      $iframeUrl = get_post_meta( $postid, 'iframe_type', true );
      if( $iframeUrl == 'regionews' ){
        $showCOmpleteBoard = false;
      }
    }

    $output .="<center class='custom-html'>
                <table>
                  <tbody>
                  <tr title='". $this->get_description_by_class('selected-locations')  ."'>
                    <td><span class='selected-locations-preview'>-</span></td>
                    <td>". $this->get_title_by_class('selected-locations')  ."</td>
                  </tr>
                  <tr title='". $this->get_description_by_class('screens-available')  ."'>
                    <td><span class='screens-available-preview'>-</span></td>
                    <td>". $this->get_title_by_class('screens-available')  ."</td>
                  </tr>
                  <tr title='". $this->get_description_by_class('days-frequency')  ."'>
                    <td><span class='days-frequency-preview'>-</span></td>
                    <td>". $this->get_title_by_class('days-frequency')  ."</td>
                  </tr>";

    if( $showCOmpleteBoard === true ){
        $output .="<tr title='". $this->get_description_by_class('duration')  ."'>
                    <td><span class='duration-preview'>-</span></td>
                      <td>". $this->get_title_by_class('duration')  ."</td>
                    </tr>
                    <tr title='". $this->get_description_by_class('add-views')  ."'>
                    <td><span class='add-views-preview'>-</span></td>
                    <td>". $this->get_title_by_class('add-views')  ."</td>
                  </tr>
                  <tr title='". $this->get_description_by_class('overlays')  ."'>
                    <td><span class='overlay-preview'>-</span></td>
                    <td>". $this->get_title_by_class('overlays')  ."</td>
                  </tr>";
    }else{
      $output .="<tr title='". $this->get_description_by_class('duration')  ."'>
                  <td><span class='duration-previe-r'>-</span></td>
                  <td>". $this->get_title_by_class('duration')  ."</td>
                </tr>";
    }
    $output .="</tbody>
                </table>
              </center>";

    if( $showCOmpleteBoard === true ){

      if( $args['field'] != '64' &&  $args['field'] != '152' ){
          $output .='<span class="ids-faq monitre">
            <span class="gfield single-column-form">
              <label class="gfield_label">Auswahl der Frequenz pro Monitor</label>
              <div class="ginput_container ginput_container_radio frequency-radios" id="test-'.$args['field'].'">
                <ul class="gfield_radio" id="input_2_165">
                  <li class="choice_input_radio_0">
                    <input name="input_radio" type="radio" value="1" id="choice_input_radio_0">
                    <label for="choice_input_radio_0" id="label_input_radio_0">Längere Laufzeit </label>
                    <span>1 x pro h</span>
                  </li>
                  <li class="choice_input_radio_1" style="padding: 0 10px !important;">
                    <input name="input_radio" type="radio" value="2" id="choice_input_radio_1">
                    <label class="custom-child"for="choice_input_radio_1" id="label_input_radio_1"> Basis Laufzeit </label>
                    <span>2 x pro h</span>
                  </li>
                  <li class="choice_input_radio_2">
                    <input name="input_radio" type="radio" value="4" id="choice_input_radio_2">
                    <label for="choice_input_radio_2" id="label_input_radio_2">Steigerung Werbedruck </label>
                    <span>4 x pro h</span>
                  </li>
                  <li class="choice_input_radio_3">
                    <input name="input_radio" type="radio" value="6" id="choice_input_radio_3">
                    <label for="choice_input_radio_3" id="label_input_radio_3">Maximaler Werbedruck </label>
                    <span>6 x pro h</span>
                  </li>
                </ul>
              </div>
            </span>
          </span>';
        }
        $output .= '<span class="ids-faq currency">
                    <span class="gfield single-column-form">
                      <label class="gfield_label">Ihre investition</label>
                    </span>
                  </span>';
        $output .="<center class='custom-html price-icon' style='font-size:20px;padding: 20px;margin-bottom: 10px; font-weight: bold'>€ <span class='amount-preview'>-</span></center>";
    }
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

  ## USer login
  public function custom_login_lm(){
    
    if(is_user_logged_in()){
      global $current_user;
      wp_get_current_user() ;
      $username = $current_user->first_name.' '.$current_user->last_name;
      if(
        empty($current_user->first_name) && 
        empty($current_user->last_name) 
      ){
        $username = $current_user->user_email;
      }
      return '<a style="text-transform:initial" class="login-button">Angemeldet als '.$username.'</a>';
    }
    $output = '<a class="login-button">Login</a>
                <div class="custom-popup">
                <div class="ol"></div>
                <a class="custom-close">×</a>
                  [gravityform id="16" title="true" description="false" ajax="true"]
                </div>';
    return $output;
  }

  ## Get domain
  public function getDomain(){
    $parentURL = parse_url($_SERVER['HTTP_REFERER']);
    if( !empty($_SERVER['QUERY_STRING']) ){
      parse_str( $_SERVER['QUERY_STRING'], $QS);
      if( !empty($QS['parent']) ){
        $parentURL['host'] = $QS['parent'];
      }
    }
    if( !empty( $_POST['input_'.$this->sourceForm['fields']['domain']] ) ){
      $parentURL['host'] = $_POST['input_'.$this->sourceForm['fields']['domain']];
    }

    return $parentURL['host'];
  }

  ## Pre render site domain
  public function prepopulate_parent_domain( $form ) {
    foreach( $form['fields'] as &$field ) {
      if( $field->id == 4 ){
        $field->defaultValue = $this->getDomain();
      }
    }
    return $form;
  }

  ## Pre render site domain
  public function prepopulate_digistore_ID( $form ) {
    global $wpdb;
    foreach( $form['fields'] as &$field ) {
      if( $field->id == 98 ){
        $domain = $this->getDomain();
        $postid = $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_title = '" .$domain. "' AND post_type='iframe-settings' " );
        if( !empty($postid) ){
          $field->defaultValue = get_post_meta( $postid, 'digistore24_id', true );
        }
      }
    }
    return $form;
  }

  ## Pre render site domain
  public function prepopulate_iframe_type( $form ) {
    global $wpdb;
    foreach( $form['fields'] as &$field ) {
      if( $field->id == 138 ){
        $domain = $this->getDomain();
        $postid = $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_title = '" .$domain. "' AND post_type='iframe-settings' " );
        if( !empty($postid) ){
          $field->defaultValue = get_post_meta( $postid, 'iframe_type', true );
        }
      }
    }
    return $form;
  }

  ## Pre render site domain
  public function prepopulate_static_logo( $form ) {
    global $wpdb;
    foreach( $form['fields'] as &$field ) {
      if( $field->id == 135 ){
        $conditionalField = GFAPI::get_field( $form['id'], 118 );
        $domain = $this->getDomain();
        $allowedDomains = [];
        if( !empty($conditionalField->conditionalLogic) ){
          foreach( $conditionalField->conditionalLogic['rules']  as $rules){
            $allowedDomains[] = $rules['value'];
          }
        }

        if( in_array( $domain , $allowedDomains ) ){
          $staticLogoImage = wp_get_attachment_image_src( 474, 'full' ); 
          $field->defaultValue = $staticLogoImage[0];
        }
      }
    }
    return $form;
  }
    
  ## Change form title
  public function changeFormTitle( $form ) {
    
    // $form['title'] = "Form title";
    global $wpdb;
    $domain = $this->getDomain();
    $postid = $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_title = '" . $domain . "' AND post_type='iframe-settings' " );
    if( empty($postid) ){
      return $form;
    }

    $entry[  $this->sourceForm['fields']['iframe_url'] ] = get_post_meta( $postid, 'iframe_type', true );
    foreach( $form['fields'] as &$field )  {
      if( in_array( 'lm-form-title', explode( " ", $field->cssClass ) ) ){
        if( !GFFormsModel::is_field_hidden( $form, $field, array(), $entry ) ){ 
          $form['title'] = $field->content;
        }
      }
    }

    return $form;
  }

  ## Change form title
  public function hideDigistoreID( $form ) {
    
    global $wpdb;
    $domain = $_POST['input_'.$this->sourceForm['fields']['domain']];
    $postid = $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_title = '" . $domain . "' AND post_type='iframe-settings' " );
    if( empty($postid) ){
      return $form;
    }

    $hideDigistoreId = get_post_meta( $postid, 'hide_digistore24_id', true );
    if( $hideDigistoreId != "yes" ){
      return $form;
    }

    foreach( $form['fields'] as &$field )  {
      if( in_array( 'digistore-id', explode( " ", $field->cssClass ) ) ){
        $field->cssClass = $field->cssClass. " gform_hidden";
      }
    }

    return $form;
  }

  ## Pre render site domain
  public function prepopulate_site_domain( $form ) {

    // echo "<pre>";
    //   print_r( $_POST );
    // echo "</pre>";

    $parentURL = parse_url($_SERVER['HTTP_REFERER']);
    foreach( $form['fields'] as &$field ) {

      ## Do not populate order ID
      if( $field->id == 129 ){
        continue;
      }

      if( $field->id == 106 ){
        if( !empty( $_GET['parent'] ) ){
          $parentURL['host'] = $_GET['parent'];
        }
        $field->defaultValue = $parentURL['host'];
        continue;
      }
    }

    ## Logged in user ID
    $userID    = get_current_user_id();

    if( 
      ( !is_user_logged_in() && !isset( $_POST['gform_submit'] ) ) ||
      ( $this->notFoundOrder === false && !isset( $_POST['gform_submit'] ) )
    ){
      $userCategories = [ 'powerseller', 'premiumpartner' ];
      if( !in_array( strtolower( get_user_meta( $userID, '_user_category', true ) ), $userCategories ) ){
        GFFormDisplay::$submission[$form['id']]["page_number"] = 2;
        return $form;
      }
    }

    ## Edit case
    $entryID = isset( $_POST['input_125'] ) ? $_POST['input_125'] : '';
    $formField = $this->sourceForm['fields'];
    if( empty($entryID) || $_POST['gform_target_page_number_2'] < 2 ){
      ## Details to be prefilled based on user meta
      $user      = wp_get_current_user();
      if( empty($_POST['input_'.$formField['_salutation']]) ){
        $_POST['input_'.$formField['_salutation']] = get_user_meta( $userID, '_salutation', true );
      }
      if( empty($_POST['input_'.$formField['_firstname']]) ){
        $_POST['input_'.$formField['_firstname']] = get_user_meta( $userID, 'first_name', true );
      }
      if( empty($_POST['input_'.$formField['_lastname']]) ){
        $_POST['input_'.$formField['_lastname']] = get_user_meta( $userID, 'last_name', true );
      }
      if( empty($_POST['input_'.$formField['_company']]) ){ 
        $_POST['input_'.$formField['_company']] = get_user_meta( $userID, '_company', true );
      }
      if( empty($_POST['input_'.$formField['_email']]) ){
        $_POST['input_'.$formField['_email']] = $user->user_email;
      }
      if( empty($_POST['input_'.$formField['_username']]) ){
        $_POST['input_'.$formField['_username']] = $user->user_email;
      }
      if( empty($_POST['input_'.$formField['_phone_number']]) ){
        $_POST['input_'.$formField['_phone_number']] = get_user_meta( $userID, '_phone_number', true );
      }
      if( empty($_POST['input_'.$formField['_street_address']]) ){    
        $_POST['input_'.$formField['_street_address']] = get_user_meta( $userID, '_street_address', true );
      }
      if( empty($_POST['input_'.$formField['_address_line_2']]) ){
        $_POST['input_'.$formField['_address_line_2']] = get_user_meta( $userID, '_address_line_2', true );
      }
      if( empty($_POST['input_'.$formField['_city']]) ){
        $_POST['input_'.$formField['_city']] = get_user_meta( $userID, '_city', true );
      }
      if( empty($_POST['input_'.$formField['_state']]) ){  
        $_POST['input_'.$formField['_state']] = get_user_meta( $userID, '_state', true );
      }
      if( empty($_POST['input_'.$formField['_zip']]) ){
        $_POST['input_'.$formField['_zip']] = get_user_meta( $userID, '_zip', true );
      }
      if( empty($_POST['input_'.$formField['_country']]) ){
        $_POST['input_'.$formField['_country']] = get_user_meta( $userID, '_country', true );
      }
      if( empty($_POST['input_'.$formField['_tax_id']]) ){
        $_POST['input_'.$formField['_tax_id']] = get_user_meta( $userID, '_tax_id', true );
      }
      if( empty($_POST['input_'.$formField['_coupon_code']]) ){
        $_POST['input_'.$formField['_coupon_code']] = get_user_meta( $userID, '_coupon_code', true );
      }
      if( empty($_POST['input_'.$formField['_user_category']]) ){
        $_POST['input_'.$formField['_user_category']][] = strtolower( get_user_meta( $userID, '_user_category', true ) );
      }
      return $form;
    }

    $entry = GFAPI::get_entry( $entryID );
    ## Validate entry
    if( is_wp_error($entry) ){
      return $form;
    }

    foreach( $form['fields'] as &$field )  {
			$items = array();

      if( $field->id == 125 || $field->id == 138 || $field->id == 106 ){
        continue;
      }

      if( $field->id == 126 ){
        $_POST['input_'.$formField['_user_category']][] = strtolower( get_user_meta( $userID, '_user_category', true ) );
        continue;
      }

      if( $field->id == 130 ){
        $_POST['input_'.$field->id] = rgar( $entry, '95' );
        continue;
      }

			switch( $field->type ) {
				case 'name':
					$items = array();
					foreach( $field->inputs as $input ){
            if( empty($_POST['input_'.str_replace( ".", "_", $input['id'] ) ]) ){
  						// $items[] = array_merge( $input, array( 'defaultValue' => rgar( $entry, $input['id'] ) ) );
              $_POST['input_'.str_replace( ".", "_", $input['id'] ) ] = rgar( $entry, $input['id'] );
            }
          }
					// $field->inputs = $items;
				break;
				case 'address':
					$items = array();
					foreach( $field->inputs as $input ){
            if( empty($_POST['input_'.str_replace( ".", "_", $input['id'] ) ]) ){
  						// $items[] = array_merge( $input, array( 'defaultValue' => rgar( $entry, $input['id'] ) ) );
              $_POST['input_'.str_replace( ".", "_", $input['id'] ) ] = rgar( $entry, $input['id'] );
            }
         }
					// $field->inputs = $items;
				break;
				case 'date':
          // $field->defaultValue = rgar( $entry, $field->id );
          if( empty($_POST['input_'.$field->id]) ){
            $_POST['input_'.$field->id] = rgar( $entry, $field->id );
          }
        break;
        case 'product':
          // if( empty($_POST['input_'.$field->id]) ){
          //   $_POST['input_'.$field->id] = rgar( $entry, $field->id );
          // }
          $items = array();
          $price = explode( "|", rgar( $entry, $field->id ) );        
          foreach( $field->choices as $choice ){
            $selected = false;
            if( $price[1] == str_replace( ".", "", floatVal( $choice['price'] ) ) ){
              $selected = true;
            } 
            $items[] = array(  'text' => $choice['text'], 'value' => $choice['value'], 'isSelected' =>  $selected, 'price' => $choice['price'] );
          }
          $field->choices = $items;
				break;
				case 'checkbox':
          $items = array();
          $i = 1;
					foreach( $field->choices as $choice ){			
						// $selected = false;
						if( !empty(rgar( $entry, $field->id.'.'.$i ) ) ){
							// $selected = true;
              if( empty($_POST['input_'.$field->id.'_'.$i]) ){
                $_POST['input_'.$field->id.'_'.$i] = rgar( $entry, $field->id.'.'.$i );
              }
             }  
						// $items[] = array( 'value' => $choice['value'], 'text' => $choice['text'], 'isSelected' =>  $selected );
             $i++;
          }
					// $field->choices = $items;
				break;    
				default:
					# code...
					// $field->defaultValue = rgar( $entry, $field->id );
          if( empty($_POST['input_'.$field->id]) ){
            $_POST['input_'.$field->id] = rgar( $entry, $field->id );
          }
				break;

        ## Use this code for GF multiselect field
        // case 'multiselect':
        //   // skip non selected values
        //   if( empty(rgar( $entry, $field->id )) ){
        //     break;
        //   }
        //   $_POST['input_'.$field->id] = json_decode( rgar( $entry, $field->id ) );
        // break;
			}
	  }

    return $form;
  }

  ## Pre render site domain
  public function prepopulate_user_orders( $form ) {
    
    $orders = false;
    $tempOrders = [];
    foreach( $form['fields'] as &$field )  {
      if( $field->id == 125 ){
        $oldOrders = explode( ",", get_user_meta( get_current_user_id(), '_lm_orders', true ) );
        $items = array();
        if( !empty($oldOrders) ){
          rsort($oldOrders);
          $items[] = array( 'value' => '', 'text' => 'Neue Bestellung hinzufügen' );
          foreach( $oldOrders as $oldOrder ){		
            if( !empty($oldOrder) ){	
              $entry = GFAPI::get_entry( $oldOrder );
              // $items[] = array( 'value' => $oldOrder, 'text' => 'Buchung '.$oldOrder );
              if( !is_wp_error($entry) && !empty( $entry['129'] ) && !in_array( $entry['129'], $tempOrders ) && $entry['status'] == 'active' ){
                $items[] = array( 'value' => $oldOrder, 'text' => 'Buchung '.$entry['129'].' vom '.date( 'd.m.Y', strtotime($entry['date_created']) ) );
                $orders = true;
                $tempOrders [] = $entry['129'];
                $this->notFoundOrder = true;
              }
            }
          }
        }
        if( $orders === false ){
          $items = array();
          $items[] = array( 'value' => '', 'text' => 'Neue Bestellung hinzufügen' );
        }
        $field->choices = $items;
      }
    }
    return $form;
  }

  ## Custom validation for login authentication
  public function emailValidation( $validation_result ) {

    if( $_POST['gform_source_page_number_2'] != 4 ){
      return $validation_result;
    }

    $fieldID = 156;
    $email = $_POST['input_'.$fieldID];
    $form = $validation_result['form'];
 
    //supposing we don't want input 1 to be a value of 86
    if( !filter_var($email, FILTER_VALIDATE_EMAIL) ) {
 
        // set the form validation to false
        $validation_result['is_valid'] = false;
 
        //finding Field with ID of 1 and marking it as failed validation
        foreach( $form['fields'] as &$field ) {
 
            //NOTE: replace 1 with the field you would like to validate
            if ( $field->id == $fieldID ) {
                $field->failed_validation = true;
                $field->validation_message = 'Bitte gib eine gültige E-Mail-Adresse an.';
                break;
            }
        }
 
    }
 
    //Assign modified $form object back to the validation result
    $validation_result['form'] = $form;

    return $validation_result;
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

  ## Change button text
  public function change_submit_button_text( $button, $form ) {
      return "<button style='float: right;' class='button gform_button' id='gform_submit_button_{$form['id']}'><span>BUCHUNG ABSCHLIESSEN</span></button>";
  }

  #Custom date field validation
  public function customDateValidation( $validationResult ){
    $form = $validationResult['form'];
    foreach( $form['fields'] as &$field ) {
      if ( $field->id == $this->sourceForm['fields']['date'] && !empty($_POST[ 'input_'.$this->sourceForm['fields']['date'] ]) ) {
          $selectedDate = strtotime( $_POST[ 'input_'.$this->sourceForm['fields']['date'] ] );
          $addThreeDays = strtotime( Date('d-m-Y', strtotime('+3 days')) );
          if( $selectedDate < $addThreeDays ){
            $validationResult['is_valid'] = false;
            $field->failed_validation = true;
            $field->validation_message = 'Das Datum muss mindestens drei Tage in der Zukunft liegen.';
            break;
          }
      }
    }
    //Assign modified $form object back to the validation result
    $validationResult['form'] = $form;
    return $validationResult;
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
            'entry_id'    => rgar( $entry, 'id' ),
            'location_id' => rgar( $entry, $fields['location_id'] ),
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
            'digistore_package'      => rgar( $entry, $fields['digistore_package'] ),
            'weekly_contacts'        => rgar( $entry, $fields['weekly_contacts'] ),    
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
            location_id,
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
            weekly_contacts,
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

  ## Get gravity form upload folder info
  public function getUploadInfo(){
    $form_id          = $this->sourceForm['id'];
    $time             = current_time( 'mysql' );
    $y                = substr( $time, 0, 4 );
    $m                = substr( $time, 5, 2 );
    $target_root      = GFFormsModel::get_upload_path( $form_id ) . "/$y/$m/";
    $target_root_url  = GFFormsModel::get_upload_url( $form_id ) . "/$y/$m/";
    $upload_root_info = array( 'path' => $target_root, 'url' => $target_root_url );
    $upload_root_info = gf_apply_filters( 'gform_upload_path', $form_id, $upload_root_info, $form_id );
    return $upload_root_info;
  }

  ## Customize validation
  public function customValidation( $validation_result ){
    $form = $validation_result['form'];
    $formField = $this->sourceForm['fields'];
    foreach( $form['fields'] as &$field ) {
      if ( in_array( $field->id, array( $formField['logo-image'], $formField['small-image'], $formField['big-image'] ) ) ) {
        $field->failed_validation = false;
      }
    }
    return $validation_result;
  }

  ## Create image using PHP
  public function writeImageLocally( $size = 'big', $filepath = 'filename.png', $tagline ="", $textLine1 = "", $textLine2 = "", $additionalImage = "logo.png" , $domain ){

    if( !class_exists('Imagick') ){
      return;
    }

    $image = new Imagick();
    global $wpdb;
    $backgroundColor = '#464A5B';
    $opacity = '0.5';
    $postid = $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_title = '" . $domain . "' AND post_type='iframe-settings' " );
    if( !empty($postid) ){
      ## Image background
      $backgroundColor = !empty( get_post_meta( $postid, 'ad_image_background', true ) ) ? get_post_meta( $postid, 'ad_image_background', true ) : $backgroundColor;
      ## Image opacity
      $opacity = !empty( get_post_meta( $postid, 'ad_image_opacity', true ) ) ? get_post_meta( $postid, 'ad_image_opacity', true ) : $opacity;
    }

    ## Upload file
    $imageWidth   = ( $size == 'small' ) ? 980 : 3000;
    $imageHeight  = ( $size == 'small' ) ? 160 : 426;

    ## Create image background
    $image->newImage( $imageWidth, $imageHeight, $backgroundColor );
    $image->setImageOpacity($opacity);

    ## Draw tagline
    $drawTagline = new ImagickDraw();
    $drawTagline->setFont( plugin_dir_path(__FILE__).'font/Roboto-Light.ttf' );
    $drawTagline->setFillColor('#FFFFFF');
    if( $size == 'small' ){
      $drawTagline->setFontSize( 10 );
      $image->annotateImage( $drawTagline, 10, 15, 0, $tagline);
    }else{
      $drawTagline->setFontSize( 30 );
      $image->annotateImage( $drawTagline, 25, 53, 0, $tagline);
    }

    ## Add text
    $drawText = new ImagickDraw();
    $drawText->setFont( plugin_dir_path(__FILE__).'font/Roboto-Black.ttf' );
    $drawText->setFillColor('#FFFFFF');

    if( $size == 'small' ){
      $drawText->setFontSize( 34 );
    }else{
      $drawText->setFontSize( 106 );
    }

    $string = [];
    if( !empty($textLine1) ){
        $string[] = $textLine1;
    }
    if( !empty($textLine2) ){
        $string[] = $textLine2;
    }

    $text = implode("\n", $string );

    if( $size == 'small' ){
      $areaWidth = 850;
      if( empty($additionalImage) ){
        $drawText->setGravity(imagick::GRAVITY_CENTER);
        $xpos = 0;
      }else{
        // $drawText->setGravity(4);
        $drawText->setGravity(imagick::GRAVITY_CENTER);
        $xpos = -72;
        $areaWidth = 800;
      }
    }else{
      $areaWidth = 2800;
      if( empty($additionalImage) ){
        $drawText->setGravity(imagick::GRAVITY_CENTER);
        $xpos = 0;
      }else{
        // $drawText->setGravity(4);
        $drawText->setGravity(imagick::GRAVITY_CENTER);
        $xpos = -226;
        $areaWidth = 2500;
      }
    }
    
    $words = explode(" ", $text);
    $containesExceedLength = false;
    foreach( $words as $word ){
      if( strlen($word) > 26 ){
        $containesExceedLength = true;   
        break;
      }
    }

    if( $containesExceedLength ){
      $wordWrap = wordwrap($text, 26, "\n", true);
      $image->annotateImage($drawText, $xpos, 0, 0, $wordWrap);
    }else{
      list($lines, $lineHeight) = $this->wordWrapAnnotation($image, $drawText, $text, $areaWidth);
      $ypos = 0;
      if( count($lines) > 1 ){
        if( $size == 'small' ){
          $ypos = -20;
        }else{
          $ypos = -50;
        }
      }
      for($i = 0; $i < count($lines); $i++){
        if( $i > 1 ){
          break;
        }
        $image->annotateImage($drawText, $xpos, $ypos + $i*$lineHeight, 0, $lines[$i]);
      }
    }

    ## Create instance of the Watermark image
    if( !empty($additionalImage) ){
      $addLogo = new Imagick();
      $addLogo->readImage( $additionalImage );
      if( $size == 'small' ){
        ## The start coordinates where the file should be printed
        // $addLogo->scaleImage( 142, 142 );
        $addLogo->resizeImage(142, 142, Imagick::FILTER_LANCZOS, 1, true);
        $qrCodeW = $addLogo->getImageWidth();
        $qrCodeH = $addLogo->getImageHeight();
        $x = $image->getImageWidth() - $qrCodeW - 10;
        $y = $image->getImageHeight() / 2 - $qrCodeH / 2;
        ## Draw watermark on the image file with the given coordinates
        $image->compositeImage($addLogo, Imagick::COMPOSITE_OVER, $x, $y);
      }else{
        ## The start coordinates where the file should be printed
        // $addLogo->scaleImage( 376, 376 );
        $addLogo->resizeImage(376, 376, Imagick::FILTER_LANCZOS, 1, true);
        $qrCodeW = $addLogo->getImageWidth();
        $qrCodeH = $addLogo->getImageHeight();
        $x = $image->getImageWidth() - $qrCodeW - 25;
        $y = $image->getImageHeight() / 2 - $qrCodeH / 2;
        ## Draw watermark on the image file with the given coordinates
        $image->compositeImage($addLogo, Imagick::COMPOSITE_OVER, $x, $y);
      }
    }

    ## Create image
    $image->setImageFormat( "png" );
    $image->writeImage( $filepath );
    $image->destroy();
  }

  ## Update uploaded images fields
  public function changeUploadImages( $entry, $form ) {
    
    if( $form['id'] != $this->sourceForm['id'] ){
      return $entry;
    }

    $fielduploadDir = $this->getUploadInfo();
    $formField = $this->sourceForm['fields'];

    ## Content
    $tagline   = 'ANZEIGE';
    $textLine1 = rgar( $entry, $formField['text-line-1'] );
    $textLine2 = rgar( $entry, $formField['text-line-2'] );

    $time = time();
    $additionalImage = '';
    if( !empty( rgar( $entry, $formField['qr-code-link'] ) ) ){
      $QRCODE = $fielduploadDir['path'].$time.'-qr.png';
      
      $QRCODEGENRATELINK = 'https://chart.googleapis.com/chart?chs=536x536&cht=qr&chl='.urlencode(rgar( $entry, $formField['qr-code-link'] )).'&choe=UTF-8&chld=1|1';

      ## Directory does not exist, so lets create it.
      if(!is_dir($fielduploadDir['path'])){
        mkdir($fielduploadDir['path'], 0755);
      }

      file_put_contents( $QRCODE, file_get_contents($QRCODEGENRATELINK));
      $additionalImage = $QRCODE;
      
      ## Update logo image url to custom text feild
      GFAPI::update_entry_field( $entry['id'], '135', $QRCODEGENRATELINK );

    }else{
      $logo = json_decode( rgar( $entry, $formField['logo-image'] ) );
      $additionalImage = !empty( $logo ) ? $fielduploadDir['path'].end( explode( '/', $logo[0] ) ) : $entry['135']; 

      ## Update logo image url to custom text feild
      GFAPI::update_entry_field( $entry['id'], '135', $additionalImage );

    }

    $smallImageFile  = $time.'-small.png';
    $bigImageFile    = $time.'-big.png';

    $domain = !empty( rgar( $entry, $formField['domain'] ) ) ? rgar( $entry, $formField['domain'] ) : $this->getDomain();

    ## Add Image
    ## Create small image
    $this->writeImageLocally( 'small', $fielduploadDir['path'].$smallImageFile, $tagline, $textLine1, $textLine2, $additionalImage, $domain );
    ## Update field value
    GFAPI::update_entry_field( $entry['id'], $formField['small-image'], $fielduploadDir['url'].$smallImageFile ); 
    $entry[$formField['small-image']] = $fielduploadDir['url'].$smallImageFile;

    ## Big Add Image
    ## Create big image
    $this->writeImageLocally( 'big', $fielduploadDir['path'].$bigImageFile, $tagline, $textLine1, $textLine2, $additionalImage, $domain );
    ## Update field value
    GFAPI::update_entry_field( $entry['id'], $formField['big-image'],  $fielduploadDir['url'].$bigImageFile ); 
    $entry[$formField['big-image']] = $fielduploadDir['url'].$bigImageFile;

    ## Entry object
    return $entry;
  }

  public function wordWrapAnnotation(&$image, &$draw, $text, $maxWidth){
    $words = explode(" ", $text);
    $lines = array();
    $i = 0;
    $lineHeight = 0;
    while($i < count($words) )
    {
        $currentLine = $words[$i];
        if($i+1 >= count($words))
        {
            $lines[] = $currentLine;
            break;
        }
        //Check to see if we can add another word to this line
        $metrics = $image->queryFontMetrics($draw, $currentLine . ' ' . $words[$i+1]);
        while($metrics['textWidth'] <= $maxWidth)
        {
            //If so, do it and keep doing it!
            $currentLine .= ' ' . $words[++$i];
            if($i+1 >= count($words))
                break;
            $metrics = $image->queryFontMetrics($draw, $currentLine . ' ' . $words[$i+1]);
        }
        //We can't add the next word to this line, so loop to the next line
        $lines[] = $currentLine;
        $i++;
        //Finally, update line height
        if($metrics['textHeight'] > $lineHeight)
            $lineHeight = $metrics['textHeight'];
    }
    return array($lines, $lineHeight);
  }

  ## Create image preview
  public function create_image_preview(){

    $image = "";

    $fielduploadDir = $this->getUploadInfo();
    $formField = $this->sourceForm['fields'];

    ## Content
    $smallImageFile  = get_current_user_id().'-temp-small.png';
    $tagline   = 'ANZEIGE';
    $textLine1 = !empty( $_POST['AddressLine1'] ) ? $_POST['AddressLine1'] : "";
    $textLine2 = !empty( $_POST['AddressLine2'] ) ?  $_POST['AddressLine2'] : "";
    $logoImage = !empty( $_POST['logo'] ) ? $_POST['logo'] : "";

    $additionalImage = "";
    if( !empty($logoImage) ){
      ## Directory does not exist, so lets create it.
      if(!is_dir($fielduploadDir['path'])){
        mkdir($fielduploadDir['path'], 0755);
      }

      $QRCODE = $fielduploadDir['path'].$time.'-qr.png';
      file_put_contents( $QRCODE, file_get_contents($logoImage));
      $additionalImage = $QRCODE;
    }
    
    ## Get domain value
    $domain = isset( $_POST['domain'] ) ? $_POST['domain'] : $this->getDomain();

    $this->writeImageLocally( 'small', $fielduploadDir['path'].$smallImageFile, $tagline, $textLine1, $textLine2, $additionalImage, $domain  );
    echo $fielduploadDir['url'].$smallImageFile.'?'.time();
    wp_die();
  }

  ## Get post custom fields
  public function getCustomPost( $posttitle ){

    if( $_SERVER['HTTP_SEC_FETCH_DEST'] != "iframe" ){
      return;
    }

    global $wpdb;
    $postid = $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_title = '" . $posttitle . "' AND post_type='iframe-settings' " );
    if( empty($postid) ){
      return;
    }

    ## Form background
    $formBackground = get_post_meta( $postid, 'form_background_color', true );
    $formLabelColor = get_post_meta( $postid, 'form_title', true );
    $removeFormHeader   = get_post_meta( $postid, 'remove_header', true );
    $removeSocialBubble = get_post_meta( $postid, 'remove_social_proof_bubble', true );
    $formfontFamily = get_post( get_post_meta( $postid, 'form_font_family', true ) );
    $loginImageIcon = get_post( get_post_meta( $postid, 'login_icon', true ) );
    $errorMessageColor = get_post_meta( $postid, 'error_message_color', true );

    ## Menu color & background
    $menuBackground = get_post_meta( $postid, 'menu_bar_background_color', true );
    $menuColor      = get_post_meta( $postid, 'menu_bar_text_color', true );

    ## HTML Content fontsize, line height, color
    $HTMLFontSize   = get_post_meta( $postid, 'html_font_size', true );
    $HTMLLineHeight = get_post_meta( $postid, 'html_line_height', true );
    $HTMLColor      = get_post_meta( $postid, 'html_color', true );

    ## Field label, color, background
    $fieldLabelColor  = get_post_meta( $postid, 'fields_label_color', true );
    $feildBackground  = get_post_meta( $postid, 'fields_background', true );
    $feildplaceholder = get_post_meta( $postid, 'fields_placeholder_text_color', true );
    $fieldHover       = get_post_meta( $postid, 'text_hover', true );
    $fieldFocus       = get_post_meta( $postid, 'fields_focus_background', true );

    ## Buttons
    $buttonColor       = get_post_meta( $postid, 'button_color', true );
    $buttonBackground  = get_post_meta( $postid, 'button_background', true );
    $buttonBorder      = get_post_meta( $postid, 'button_border_color', true );
    $buttonHover       = get_post_meta( $postid, 'button_hover', true );
    $buttonHoverColor  = get_post_meta( $postid, 'button_hover_color', true );

    ## Back button
    $backbuttonColor       = get_post_meta( $postid, 'back_button_color', true );
    $backbuttonBackground  = get_post_meta( $postid, 'back_button_background', true );
    $backbuttonBorder      = get_post_meta( $postid, 'back_button_border_color', true );
    $backbuttonHover       = get_post_meta( $postid, 'back_button_hover', true );
    $backbuttonHoverColor  = get_post_meta( $postid, 'back_button_hover_color', true );

    ## Links and icons
    $linkColor       = get_post_meta( $postid, 'link_color', true );
    $iconBackground  = get_post_meta( $postid, 'icon_background', true );
    $iconColor       = get_post_meta( $postid, 'icon_color', true );
    $datepickerIcon  = get_post( get_post_meta( $postid, 'datepicker_icon', true ) );
    $humberIcon      = get_post( get_post_meta( $postid, 'humber_icon', true ) );
    $locationIcon    = get_post( get_post_meta( $postid, 'location_icon', true ) );
    $monitreIcon    = get_post( get_post_meta( $postid, 'moniter_image_icon', true ) );
    $currencyIcon    = get_post( get_post_meta( $postid, 'currency_image_icon', true ) );
    $refreshIcon     = get_post( get_post_meta( $postid, 'refresh_icon', true ) );
    $qrIcon          = get_post( get_post_meta( $postid, 'qr_code_icon', true ) );
    $imageUploadIcon = get_post( get_post_meta( $postid, 'image_upload_icon', true ) );    
     
    ## Options
    $optionColor       = get_post_meta( $postid, 'option_color', true );
    $optionBackground  = get_post_meta( $postid, 'option_background', true );
    $optionBorder      = get_post_meta( $postid, 'option_border_color', true );
    $optionHover       = get_post_meta( $postid, 'option_hover_background', true );
    $optionHoverColor  = get_post_meta( $postid, 'option_hover_color', true );

    ## Summary
    $summaryLabel       = get_post_meta( $postid, 'summary_label', true );
    $summaryText        = get_post_meta( $postid, 'summary_text', true );
    $summaryDescription = get_post_meta( $postid, 'summary_description', true );
    $summaryBackground  = get_post_meta( $postid, 'summary_background', true );
    $summaryBorder      = get_post_meta( $postid, 'summary_border', true );

    ## Listing
    $listingColor       = get_post_meta( $postid, 'listing_color', true );
    $listingChecked     = get_post_meta( $postid, 'listing_checked_color', true );
    $listinghover       = get_post_meta( $postid, 'filter_location_hover', true );
    $listinghoverText      = get_post_meta( $postid, 'filter_location_text_hover', true );
    ?>
    <style>
      <?php 
      if( !empty($formfontFamily) && $formfontFamily->post_mime_type == 'application/x-font-truetype' ){
      ?>
      @font-face {
        font-family: <?php echo $formfontFamily->post_title; ?>;
        src: url(<?php echo $formfontFamily->guid; ?>);
      }
      html, body {
        font-family: <?php echo $formfontFamily->post_title; ?> !important;
      }
      <?php
      }
      if( $removeFormHeader == 'yes' ){
      ?>
      header#masthead,
      footer#colophon {
          display: none;
      }
      .site-content {
          padding: 0px !important;
      }
      .page-id-27 .wrap {
          max-width: 100%;
          padding: 0px;
      }
      body .gform_wrapper {
          margin-bottom: 0px;
          margin-top: 0px;
      }
      <?php
      }
      ?>
      body .gform_wrapper ul li.gfield.gfield_html .add-preview-html img{
        width:100%;
      }
      .ids-form.theme-color,
      body .elementor-27 .elementor-element.elementor-element-300dd28c > .elementor-background-overlay,
      body #custom-login .custom-popup,
      body .custom-popup-inner,
      body{
        background: <?php echo $formBackground; ?> !important;
      }
      .elementor-27 .elementor-element.elementor-element-25ea33c5:not(.elementor-motion-effects-element-type-background) > .elementor-widget-wrap, .elementor-27 .elementor-element.elementor-element-25ea33c5 > .elementor-widget-wrap > .elementor-motion-effects-container > .elementor-motion-effects-layer{
        background: <?php echo $formBackground; ?> !important;
      }
      .elementor-27 .elementor-element.elementor-element-300dd28c{
        box-shadow:none !important;
      }
      .ids-form.theme-color .gform_title,
      body #custom-login a,
      .custom-popup-inner h3,
      #custom-login a.login-button,
      .custom-popup-inner label{
          color: <?php echo $formLabelColor; ?>;
      }
      body .custom-popup-inner{
        border-color: <?php echo $menuBackground; ?>;;
      }
      .ids-form.theme-color .gf_page_steps>.gf_step.gf_step_active:before,
      .ids-form.theme-color .gf_page_steps>.gf_step.gf_step_completed:before,
      .ids-form.theme-color .gf_page_steps>.gf_step.gf_step_active:after,
      .ids-form.theme-color .gf_page_steps>.gf_step.gf_step_completed:after,
      .ids-form.theme-color .gf_page_steps>.gf_step.gf_step_active~.gf_step:before,
      body .gform_wrapper .ids-form.login-form  {
          border-color: <?php echo $menuBackground; ?>;
          color: <?php echo $menuColor; ?>;
      }
      .ids-form.theme-color .gf_page_steps>.gf_step:after,
      .ids-form.theme-color .gf_page_steps>.gf_step.gf_step_active:after,
      .ids-form.theme-color .gf_page_steps>.gf_step.gf_step_active~.gf_step:after {
          background-color: <?php echo $menuBackground; ?>;
      }
      .ids-form.theme-color .gf_page_steps>.gf_step.gf_step_active:before,
      .ids-form.theme-color .gf_page_steps>.gf_step.gf_step_completed:before {
          background-color: <?php echo $formBackground; ?>;
      }
      .ids-form.theme-color .gf_page_steps>.gf_step.gf_step_active~.gf_step:before,
      .ids-form.theme-color .gf_page_steps>.gf_step.gf_step_completed:before{
          background: <?php echo $formBackground; ?>;
      }
      .ids-form.theme-color li.theme-text-color,
      .ids-form.theme-color li .description-text{
          line-height: <?php echo $HTMLLineHeight; ?>px;
          font-size: <?php echo $HTMLFontSize; ?>px;
          color: <?php echo $HTMLColor; ?>;
      }

      /* .gform_wrapper .top_label .ids-form .theme-color .custom-label label.gfield_label, */
      .gform_wrapper .ids-form.theme-color .top_label .gfield_label,
      .gform_wrapper .ids-form .field_sublabel_below .ginput_complex.ginput_container label,
      .gform_wrapper .ids-form .field_sublabel_above .ginput_complex.ginput_container label,
      .col-3 span p{
          color: <?php echo $fieldLabelColor; ?>;
      }
      .gform_wrapper .ids-form input:not([type=radio]):not([type=checkbox]):not([type=submit]):not([type=button]):not([type=image]):not([type=file]), 
      .gform_wrapper .ids-form ul.gform_fields li.gfield div.ginput_complex span.ginput_left select, 
      .gform_wrapper .ids-form ul.gform_fields li.gfield div.ginput_complex span.ginput_right select, 
      .gform_wrapper .ids-form ul.gform_fields li.gfield input[type=radio], 
      .gform_wrapper .ids-form ul.gform_fields li.gfield select,
      body input.gfgeo-reset-location-button,
      body .custom-popup-inner .col-left span, 
      body .custom-popup-inner .col-right span{
          background-color: <?php echo $feildBackground; ?>;
          color: <?php echo $feildplaceholder; ?>;
          border-color: <?php echo $feildBackground; ?>;
      }
      .gform_wrapper .ids-form input:not([type=radio]):not([type=checkbox]):not([type=submit]):not([type=button]):not([type=image]):not([type=file]):focus{
        background-color: <?php echo $fieldFocus; ?>;
      }
      body .charleft.ginput_counter,
      .gfield_description i{
          color: <?php echo $feildplaceholder; ?> !important;
      }
      input[type="text"]::placeholder {
          color: <?php echo $feildplaceholder; ?>;
      }
      input[type="text"]::placeholder {
          /* Chrome, Firefox, Opera, Safari 10.1+ */
          color: <?php echo $feildplaceholder; ?>;
          opacity: 1;
          /* Firefox */
      }
      input[type="text"]:-ms-input-placeholder {
          /* Internet Explorer 10-11 */
          color: <?php echo $feildplaceholder; ?>;
      }
      body .pac-container,
      .add-preview-html{
          background-color: <?php echo $feildBackground; ?> !important;
      }
      body .pac-container .pac-item {
          border-color: <?php echo $feildBackground; ?> !important;
          background-color: <?php echo $feildBackground; ?> !important;
      }
      body .pac-container .pac-item:hover{
          background-color: <?php echo $fieldHover; ?> !important;
      }
      ::selection {
          background: <?php echo $fieldHover; ?> !important;
          / WebKit/Blink Browsers /
      }
      ::-moz-selection {
          background: <?php echo $fieldHover; ?> !important;
          / Gecko Browsers /
      }
      body .pac-container .pac-item span {
          color: <?php echo $feildplaceholder; ?> !important;
      }

      #field_2_76 select option {
          background-color: <?php echo $feildBackground; ?> !important;
      }
      .ui-datepicker-header,
      .ui-datepicker-calendar th,
      .ui-datepicker-calendar td,
      .ui-datepicker-calendar td span,
      .ui-datepicker-calendar td a,
      .ui-datepicker-title select {
          background: <?php echo $feildBackground; ?> !important;
          color: <?php echo $feildplaceholder; ?> !important;
          text-shadow: none !important;
      }
      .ui-datepicker-calendar td a:hover {
          background: <?php echo $fieldHover; ?> !important;
      }
      .gform_wrapper .top_label .custom-label label.gfield_label{
        background: <?php echo $feildBackground; ?> !important;
        color: <?php echo $buttonColor; ?> !important;
      }
      body input.search-filter-button,
      .custom-radio.inline-list ul#input_2_118 li label {
        border-color: <?php echo $buttonBorder; ?> !important;
        background: <?php echo $buttonBackground; ?> !important;
        color: <?php echo $buttonColor; ?> !important;
    }
    .col-3 span p:last-child{
      color: <?php echo $menuBackground; ?> !important;  
    }
    .ids-eur span{
      background: <?php echo $menuBackground; ?> !important;  
    }
    .custom-radio.inline-list ul#input_2_118 li label {
      background: <?php echo $feildBackground; ?> !important;
    }
    .custom-button button {
        background-color: <?php echo $buttonBackground; ?>;
        color: <?php echo $buttonColor; ?>; 
        border-color: <?php echo $buttonBorder; ?>;
    }
     body input.search-filter-button:hover, 
     body input.gfgeo-reset-location-button:hover, 
     .custom-button button:hover,
     .gform_wrapper .ids-form input[type="button"]:focus,
     .custom-radio.inline-list ul#input_2_118 li label:hover,
     .custom-radio.inline-list ul#input_2_118 li label:focus,
    .custom-radio.inline-list ul#input_2_118 li input[type=radio]:checked+label {
        background-color: <?php echo $buttonHover; ?> !important;
        color: <?php echo $buttonHoverColor; ?> !important;
        border-color: <?php echo $buttonHover; ?> !important;
    }
    .gform_wrapper .ids-form .gform_page_footer .button.gform_next_button,
    .gform_wrapper .ids-form .button.gform_next_button,
    .gform_wrapper .ids-form .gform_page_footer #gform_submit_button_2 {
        border-color: <?php echo $buttonBorder; ?>;
        background-color: <?php echo $buttonBackground; ?>;
        color: <?php echo $buttonColor; ?>;
    }
    .gform_wrapper .ids-form .gform_page_footer .gform_previous_button.button{
        border-color: <?php echo $backbuttonBorder; ?>;
        background-color: <?php echo $backbuttonBackground; ?>;
        color: <?php echo $backbuttonColor; ?>;  
    }
    .custom-checkbox2 .gfield_checkbox li label:after {
      background-color: <?php echo $feildBackground; ?> !important;
    }
    .gform_wrapper .custom-checkbox2 ul.gfield_checkbox li input[type=checkbox]+label{
      color: <?php echo $feildplaceholder; ?> !important;
    }
    .gform_wrapper .custom-checkbox2 ul.gfield_checkbox li input[type=checkbox]:checked+label {
      color: <?php echo $buttonColor; ?> !important;
    }
    .custom-checkbox2 .gfield_checkbox li label:before {
      border-color: <?php echo $buttonColor; ?> !important;
    }
    .gform_wrapper .ids-form .gform_page_footer .gform_previous_button.button:hover{
      background-color: <?php echo $backbuttonHover; ?> !important;
      color: <?php echo $backbuttonHoverColor; ?> !important;
      border-color: <?php echo $backbuttonHover; ?> !important; 
    }
    .gform_wrapper .ids-form .gform_page_footer #gform_submit_button_2:hover,
    .gform_wrapper .ids-form .gform_page_footer .button.gform_next_button:hover,
    .gform_wrapper .ids-form .button.gform_next_button:hover{
        background-color: <?php echo $buttonHover; ?> !important;
        color: <?php echo $buttonHoverColor; ?> !important;
        border-color: <?php echo $buttonHover; ?> !important;
    }
    .gform_wrapper .ids-form ul.gform_fields li.gfield a,
    .tooltip_content a{
      color: <?php echo $linkColor; ?> !important;
    }
    .gform_wrapper .ids-form .custom-radio .gfield_radio li label {
      background: <?php echo $optionBackground; ?>;
      color: <?php echo $optionColor; ?>;
      border-color: <?php echo $optionBorder; ?>;
    }
    .gform_wrapper .ids-form .custom-radio .gfield_radio li label:hover,
    .custom-radio .gfield_radio li input[type=radio]:checked+label {
      background: <?php echo $optionHover; ?>;
      color: <?php echo $optionHoverColor; ?>;
      border-color: <?php echo $optionHover; ?>;
    }
    body .easygf-tooltip.icon:after {
      background: <?php echo $iconBackground; ?>;
      color: <?php echo $iconColor; ?>;
    }
    .custom-html td {
        color: <?php echo $summaryText; ?>;
    }
    center.custom-html.price-icon {
        color: <?php echo $summaryLabel; ?>;;
    }
    center.custom-html span,
    .single-column-form .gfield_radio li span{
      color: <?php echo $summaryLabel; ?>;
    }
    .custom-html p{
      color: <?php echo $summaryDescription; ?>;
    }
    center.custom-html,
    .frequency-radios{
      background: <?php echo $summaryBackground; ?> !important;
      border:2px solid <?php echo $summaryBorder; ?>;
    }
    .single-column-form ul.gfield_checkbox li input[type=checkbox]:checked+label,
    .single-column-form ul.gfield_radio li input[type=radio]:checked+label,
    .single-column-form [type="checkbox"]:not(:checked)+label:after,
    .single-column-form [type="checkbox"]:checked+label:after,
    .single-column-form [type="radio"]:not(:checked)+label:after,
    .single-column-form [type="radio"]:checked+label:after,
    #field_2_37 .gfield_checkbox p{
      color: <?php echo $listingChecked; ?> !important;
      /* font-weight:bold !important; */
    }
    .single-column-form [type="radio"]:checked+label:before{
      border-color: <?php echo $listingChecked; ?> !important;
    }
    .single-column-form ul.gfield_checkbox li input[type=checkbox]:not(:checked)+label,
    .single-column-form ul.gfield_radio li input[type=radio]:not(:checked)+label{
      color: <?php echo $listingColor; ?>;
      /* font-weight:bold !important; */
    }
    .single-column-form [type="checkbox"]:checked+label:before {
       border: 2px solid <?php echo $listingChecked; ?>;
    }
    .gfgeo-reset-location-button-wrapper:before {
      background-image: url(<?php echo $refreshIcon->guid ?>) !important;
    }
    .gform_wrapper .top_label .icon-humber label.gfield_label:before {
      background-image: url(<?php echo $humberIcon->guid ?>) !important;
    }
    .gform_wrapper .top_label .icon-location label.gfield_label:before {
      background-image: url(<?php echo $locationIcon->guid ?>) !important;
    }
    .gform_wrapper .top_label .icon-location .monitre label.gfield_label:before {
      background-image: url(<?php echo $monitreIcon->guid ?>) !important;
      background-size: 25px;
    } 
    .gform_wrapper .top_label .icon-location .currency label.gfield_label:before {
      background-image: url(<?php echo $currencyIcon->guid ?>) !important;
    }    
    .custom-radio.inline-list ul li.gchoice_2_118_0:before{
      background-image: url(<?php echo $qrIcon->guid ?>) !important;
    }
    .custom-radio.inline-list ul li.gchoice_2_118_2:before{
      background-image: url(<?php echo $imageUploadIcon->guid ?>) !important;
    }
    #custom-login a.login-button:before,
    .login-form .gform_heading .gform_title:before{
      background-image: url(<?php echo $loginImageIcon->guid ?>) !important;
    }
    .ui-datepicker-trigger{
      visibility:hidden;
    }
    .ginput_container.ginput_container_date:before {
        content: "ddsds";
        position: absolute;
        background-image: url(<?php echo $datepickerIcon->guid ?>) !important;
        color: transparent;
        width: 22px;
        background-repeat: no-repeat;
        height: 22px;
        background-size: 20px;
        text-align: center;
        left: 12px;
        top: 10px;
    }
    .col-right span:before{
      background-image: url(<?php echo $datepickerIcon->guid ?>) !important;
    }
    .ginput_container.ginput_container_date {
        position: relative;
    }
    input:-webkit-autofill {
        -webkit-text-fill-color: <?php echo $feildplaceholder; ?>;
        -webkit-box-shadow: 0 0 0 50px <?php echo $fieldFocus; ?> inset;
    }
    input:-webkit-autofill:focus {
         -webkit-text-fill-color: <?php echo $feildplaceholder; ?>;
        -webkit-box-shadow: 0 0 0 50px <?php echo $fieldFocus; ?> inset;
    }
    .gform_wrapper .ids-form div.validation_error {
      color: <?php echo $errorMessageColor; ?> !important;
      border-color: <?php echo $errorMessageColor; ?> !important;
    }

    .gform_wrapper .ids-form .validation_message {
      color: <?php echo $errorMessageColor; ?> !important;
    }

    .gform_wrapper .ids-form li.gfield_error input:not([type=radio]):not([type=checkbox]):not([type=submit]):not([type=button]):not([type=image]):not([type=file]),
    .gform_wrapper .ids-form li.gfield_error textarea {
      border-color: <?php echo $errorMessageColor; ?> !important;
    }
    .gform_wrapper .ids-form li.all-locations ul li label:hover,
    .gform_wrapper .ids-form li.all-locations ul li label:hover:after{
      background:<?php echo $listinghover; ?>;
      color: <?php echo $listinghoverText;  ?> !important;
    }
    .gform_wrapper .ids-form li.all-locations ul li label:hover:before{
      border-color: <?php echo $listinghoverText;  ?> !important;
      top:10px !important;
    }
    .custom-radio.inline-list ul#input_2_118 li.gchoice_2_118_1 label{
        background:none !important;
        color:<?php echo $fieldLabelColor; ?> !important;
    }
    .gform_wrapper .ids-form .gform_page_footer{
        margin-bottom: 150px;
    }
    <?php
    if( $removeSocialBubble == 'yes' ){
    ?>
    #ds24_social_proof_21155{
      display:none !important;
    }
    <?php 
    }
    ?>
    </style>
    <?php
  }

  ## Change authentication failed message 
  public function customLoginAuthFailedMessage( $message, $form ){
    return "<div class='validation_error'>Ungültige Authentifizierung</div>";
  }

  ## Custom validation for login authentication
  public function loginAuthentication( $validation_result ) {
    $username = $_POST['input_1'];
    $password = $_POST['input_2'];

    $authentication = wp_authenticate( $username, $password );
    if( is_wp_error($authentication) ){
      ## Return error
      add_filter( 'gform_validation_message', [ $this, 'customLoginAuthFailedMessage' ], 10, 2 );
      $validation_result['is_valid'] = 0;
      return $validation_result;
    }

    wp_set_current_user( $authentication->ID );
    wp_set_auth_cookie( $authentication->ID );
    return $validation_result;
  }

  ## Remove login wntry
  public function removeLoginEntry( $entry, $form ) {
    GFAPI::delete_entry($entry['id']);
  }

  ## Add user orders in usermeta
  public function linkUserOrder( $entry, $form ) {
    $userID = get_current_user_id();
    $oldOrders = get_user_meta( $userID, '_lm_orders', true );
    if( $oldOrders ){
      $allOrders = explode( ",", $oldOrders );
    }
    $allOrders[] = $entry['id'];
    update_user_meta( $userID, '_lm_orders', implode( ",", $allOrders ) );
  }

  ## GEt order details
  public function get_order_details(){

    $formField = $this->sourceForm['fields'];
    $entry = GFAPI::get_entry( 	$_POST['order_id'] );
    if( is_wp_error($entry) ){
      echo 'Invalid ID';
      wp_die();
    }

    $packageValue = '';
    $field = GFAPI::get_field( 2, 84 );
    $price = explode( "|", rgar( $entry, $formField['package'] ) );
    foreach( $field->choices as $choice ){
      if( $price[1] == str_replace( ".", "", floatVal( $choice['price'] ) ) ){
        // $packageValue = explode( "–", str_replace( "//", " ", strip_tags( $choice['text'] ) ) );
        $packageValue = explode( "–", strip_tags( $choice['text'] ) );
        $packageValueSplit = explode( "//", strip_tags( $packageValue[0] ) );
      } 
    }

    $formField = $this->sourceForm['fields'];
    $entry = GFAPI::get_entry( 	$_POST['order_id'] );
    if( is_wp_error($entry) ){
      echo 'Invalid ID';
      wp_die();
    }
    $domain = empty($_POST['domain']) ? "livemessages.1a-lokal-marketing.de" : $_POST['domain'];
    $locationIconimage    = "/wp-content/uploads/2021/06/01.2_Icon_airtango_livepoint_tyrkis.png";
    $datepickerIconimage  = "/wp-content/uploads/2021/06/06.3_Icon_Kalender_Zeitraum_tyrkis.png";
    $distanceIconimage    = "/wp-content/uploads/2021/07/icon-d2.png";
    if( $domain != "livemessages.1a-lokal-marketing.de" ){
      global $wpdb;
      $postid = $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_title = '" . $domain . "' AND post_type='iframe-settings' " );
      $locationIcon    = get_post( get_post_meta( $postid, 'location_icon', true ) );
      $datepickerIcon  = get_post( get_post_meta( $postid, 'datepicker_icon', true ) );
      $distanceIcon    = get_post( get_post_meta( $postid, 'distance_image_icon', true ) );
      $locationIconimage    = $locationIcon->guid;
      $datepickerIconimage  = $datepickerIcon->guid;
      $distanceIconimage    = $distanceIcon->guid;
    }
    ?>
    <div class="col-3">
      <span>
        <img src="<?php echo $locationIconimage; ?>" style="width:34px;">
        <p>PLZ/Ort</p>
        <p><b><?php echo rgar( $entry, $formField['location'] ); ?></b></p>
      </span>
      <span>
      <img src="<?php echo $distanceIconimage; ?>">
        <p>Umkreis</p>
        <p><b><?php echo rgar( $entry, $formField['distance'] ); ?> km</b></p>
      </span>
      <span>
        <img src="<?php echo $datepickerIconimage; ?>">
        <p>Kampagnenstart</p>
        <p><b><?php echo implode( '.', array_reverse( explode( '-', rgar( $entry, $formField['date'] ) ) ) ); ?></b></p>
      </span>
    </div>

    <img src="<?php echo rgar( $entry, $formField['small-image'] ); ?>">
    <div class="col-3 ids-eur">
      <span><b><?php echo trim($packageValueSplit[0]); ?></b></span>
      <span><b><?php echo trim($packageValueSplit[1]); ?></b></span>
    </div>
      <!--div class="col-left">
        <label>PLZ / ORT</label>
        <span><?php //echo rgar( $entry, $formField['location'] ); ?></span>
      </div>
      <div class="col-right">
        <label>START DER KAMPAGNE</label>
        <span><?php //echo implode( '.', array_reverse( explode( '-', rgar( $entry, $formField['date'] ) ) ) ); ?></span>
      </div-->
      <!--div class="col-right">
        <label>Umkreis</label>
        <span><?php //echo rgar( $entry, $formField['distance'] ); ?> km</span>
      </div>
      </div>
      <h3 style="margin-bottom:0;padding-bottom:10px;"><center><?php //echo $packageValue[0]; ?></center></h3>
      <img src="<?php //echo rgar( $entry, $formField['small-image'] ); ?>">
    <?php
    wp_die();
  }

  ## Update entry order
  public function updateOrder(){
    
    if( !empty( $_GET['eid'] ) ){
      GFAPI::update_entry_field( $_GET['_lid'], '129', $_GET['eid'] ); 
    }

    if( !empty( $_GET['order'] ) && !empty( $_GET['custom'] ) ){
      GFAPI::update_entry_field( $_GET['custom'], '129', $_GET['order_id'] ); 
    }
  }

  ## Adding custom post type
  public function iframeCustomizerSettings() {
    /*
    * The $labels describes how the post type appears.
    */
    $labels = array(
        'name'          => 'Iframe settings', // Plural name
        'singular_name' => 'Iframe settings'   // Singular name
    );

    /*
    * The $supports parameter describes what the post type supports
    */
    $supports = array(
        'title',        // Post title
    );

    /*
    * The $args parameter holds important parameters for the custom post type
    */
    $args = array(
        'labels'              => $labels,
        'description'         => 'Post type iframe settings', // Description
        'supports'            => $supports,
        'taxonomies'          => array( 'category', 'post_tag' ), // Allowed taxonomies
        'hierarchical'        => false, // Allows hierarchical categorization, if set to false, the Custom Post Type will behave like Post, else it will behave like Page
        'public'              => true,  // Makes the post type public
        'show_ui'             => true,  // Displays an interface for this post type
        'show_in_menu'        => true,  // Displays in the Admin Menu (the left panel)
        'show_in_nav_menus'   => true,  // Displays in Appearance -> Menus
        'show_in_admin_bar'   => true,  // Displays in the black admin bar
        'menu_position'       => 5,     // The position number in the left menu
        'menu_icon'           => true,  // The URL for the icon used for this post type
        'can_export'          => true,  // Allows content export using Tools -> Export
        'has_archive'         => true,  // Enables post type archive (by month, date, or year)
        'exclude_from_search' => false, // Excludes posts of this type in the front-end search result page if set to true, include them if set to false
        'publicly_queryable'  => true,  // Allows queries to be performed on the front-end part if set to true
        'capability_type'     => 'post' // Allows read, edit, delete like “Post”
    );

    register_post_type('iframe-settings', $args); //Create a post type with the slug is iframe-settings and arguments in $args.
  }

  ## Adding iframe settings
  public function addCustomDesign(){
    $parentURL = parse_url($_SERVER['HTTP_REFERER']);
    if( !empty($_SERVER['QUERY_STRING']) ){
      parse_str( $_SERVER['QUERY_STRING'], $QS);
      if( !empty($QS['parent']) ){
        $parentURL['host'] = $QS['parent'];
      }
    }
    $this->getCustomPost( $parentURL['host'] );
  }

}

add_action( 'plugins_loaded', function() {
  new customizedLiveMessages();
});