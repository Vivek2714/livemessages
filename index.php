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
    // add_filter( 'gform_pre_render', array( $this, 'auto_populate_locations' ) );

    ## Custom redirect to payment page
    // add_action( 'gform_after_submission', array( $this, 'after_submission_completed' ), 10, 2 );

    ## Change submit button text
    add_filter( 'gform_submit_button', array( $this, 'change_submit_button_text' ), 10, 2 );

    ## Ajax handling
    add_action( 'wp_ajax_search_locations', array( $this, 'search_locations' ) );
    add_action( 'wp_ajax_nopriv_search_locations', array( $this, 'search_locations' ) );
    add_action( 'wp_ajax_create_image_preview', array( $this, 'create_image_preview' ) );
    add_action( 'wp_ajax_nopriv_create_image_preview', array( $this, 'create_image_preview' ) );

    /* Insert Gravity form entries into custom table */
    // if( isset($_GET['insert-entries-into-custom-table']) ){
    //     add_action( 'init', [ $this, 'insert_entries' ] );
    // }

    add_filter( 'gform_validation_'.$this->form['id'], [ $this, 'customValidation' ] );
    add_filter( 'gform_entry_post_save', [ $this, 'changeUploadImages' ], 10, 2 );
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
    $parentURL = parse_url($_SERVER['HTTP_REFERER']);
    if( file_exists( 'css/'.$parentURL['host'].'.css' ) ){
      wp_enqueue_style( 'lm-style', plugin_dir_url( __FILE__ ). 'css/'.$parentURL['host'].'.css?'.time() );
    }else{
      wp_enqueue_style( 'lm-style', plugin_dir_url( __FILE__ ). 'css/style.css?'.time() );
    }
    wp_enqueue_script( 'lm-js', plugin_dir_url( __FILE__ ). 'js/script.js', array('jquery'), '', true );
    wp_localize_script( 'lm-js', 'livemessagesObj', array( 
        'adminAjax' => admin_url( 'admin-ajax.php' ),
        'homeURL'   => home_url() 
    ) );
  }

  ## Live board summary
  public function live_board_summary($args){
    $output = "";
    if( !empty($args['label']) ){
      $output .="<label class='gfield_label'>".$args['label']."</label>";
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
                  <tr title='". $this->get_description_by_class('duration')  ."'>
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
                  </tr>
                </tbody>
                </table>
              </center>";
    $output .="<center style='background: #37424e;color:#4FADC6;font-size:20px;padding: 20px;margin-bottom: 10px; font-weight: bold'>€ <span class='amount-preview'>-</span></center>";
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
            start_date,
            digistore_affiliate_id,
            digistore_package,
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
  public function writeImageLocally( $size = 'big', $filepath = 'filename.png', $tagline ="", $textLine1 = "", $textLine2 = "", $additionalImage = "logo.png" ){

    if( !class_exists('Imagick') ){
      return;
    }

    $image = new Imagick();

    ## Upload file
    $imageWidth   = ( $size == 'small' ) ? 980 : 3000;
    $imageHeight  = ( $size == 'small' ) ? 160 : 426;

    ## Create image background
    $image->newImage( $imageWidth, $imageHeight, '#464A5B' );
    $image->setImageOpacity(0.5);

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

    $text = implode(" ", $string );

    if( $size == 'small' ){
      $areaWidth = 850;
      if( empty($additionalImage) ){
        $drawText->setGravity(imagick::GRAVITY_CENTER);
        $xpos = 0;
      }else{
        $drawText->setGravity(4);
        $xpos = 25;
        $areaWidth = 800;
      }
    }else{
      $areaWidth = 2800;
      if( empty($additionalImage) ){
        $drawText->setGravity(imagick::GRAVITY_CENTER);
        $xpos = 0;
      }else{
        $drawText->setGravity(4);
        $xpos = 75;
        $areaWidth = 2500;
      }
    }
    
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
    }else{
      $logo = json_decode( rgar( $entry, $formField['logo-image'] ) );
      $additionalImage = !empty( $logo ) ? $fielduploadDir['path'].end( explode( '/', $logo[0] ) ) : ''; 
    }

    $smallImageFile  = $time.'-small.png';
    $bigImageFile    = $time.'-big.png';

    ## Add Image
    ## Create small image
    $this->writeImageLocally( 'small', $fielduploadDir['path'].$smallImageFile, $tagline, $textLine1, $textLine2, $additionalImage );
    ## Update field value
    GFAPI::update_entry_field( $entry['id'], $formField['small-image'], $fielduploadDir['url'].$smallImageFile ); 
    $entry[$formField['small-image']] = $fielduploadDir['url'].$smallImageFile;

    ## Big Add Image
    ## Create big image
    $this->writeImageLocally( 'big', $fielduploadDir['path'].$bigImageFile, $tagline, $textLine1, $textLine2, $additionalImage  );
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

    $this->writeImageLocally( 'small', $fielduploadDir['path'].$smallImageFile, $tagline, $textLine1, $textLine2, $additionalImage  );
    echo $fielduploadDir['url'].$smallImageFile.'?'.time();
    wp_die();
  }

}

add_action( 'plugins_loaded', function() {
  new customizedLiveMessages();
});