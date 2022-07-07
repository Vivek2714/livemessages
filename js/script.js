var processTageCalculation = function(){

    // Variables
    var AVGOpeningHours = screensAvailable = multiplier = totalOfMultiplier = 0;

    // Get frequency 
    // field ID : 165  
    var frequency = jQuery('.ids-form input[name="input_165"]:checked').val();

     // Get add views 
    // field ID : 84     
    var packageValue = jQuery('.ids-form .add-views input').val();

    // Get selected locations
    jQuery('.all-locations input[type="checkbox"]:checked').each(function(){

        // Get from DB
        // column name 'AVGOpeningHours'
        AVGOpeningHours  = jQuery(this).attr('data-avg-hours');

        // Get from DB
        // column name 'screen'
        screensAvailable = jQuery(this).attr('data-value');
        if( 
          typeof(screensAvailable) != 'undefined' && 
          screensAvailable != '' &&
          typeof(AVGOpeningHours) != 'undefined' &&
          AVGOpeningHours != '' 
        ){
          
          // Multiply screens and AVGOpeningHOurs for each location
          multiplier = ( parseInt(screensAvailable) * parseInt(AVGOpeningHours) );

          // Multiplier of screens for each location and AVGOpeningHours
          totalOfMultiplier = parseInt(totalOfMultiplier) + parseInt(multiplier);

          // console.log("I am in");
        }

    });

    // Original formula
    // {Einblendungen:85} /  ( {Monitore:58}  *  {Auswahl der Frequenz pro Monitor:165}  *  18)

    // Calculated number of days i.e 'Tage'
    var tage = parseInt( packageValue.split(".").join("") ) /  ( parseInt(totalOfMultiplier) * parseInt(frequency) );
    if( isNaN(tage) || tage == "Infinity"){
      return 0;
    }

    // console.log(tage);

    return Math.ceil(tage);
}

var processSichtkontakteCalculation = function(frequency){

  var totalMoniter = 0;
  var Sichtkontakte = weeklyContact = moniters = 0;

  var screenAvailableElement = jQuery('.ids-form .screens-available input');
  
  // TOtal selected locations
  var selectedLocationsElement = jQuery('.ids-form .all-locations input:checked');
  var selectedLocations = selectedLocationsElement.length;
  
  // Calculated days
  var tage = processTageCalculation();

  // Loop through all selected locations
  selectedLocationsElement.each(function(){
    if( typeof(jQuery(this).attr('data-value')) != "undefined" && jQuery(this).attr('data-value') != "" ){

      // Total available screens
      totalMoniter = parseInt( totalMoniter ) + parseInt( jQuery(this).attr('data-value') );

      weeklyContact = jQuery(this).attr("data-weekly-contacts");
      if( weeklyContact == "" ){
        weeklyContact = 0;
      }

      moniters  = jQuery(this).attr('data-value');
      AVGOpeningHours = jQuery(this).attr("data-avg-hours");   
      
      Sichtkontakte = Sichtkontakte + ( parseInt( weeklyContact ) / 7 / 24 / 10 * parseInt(frequency) * parseFloat(AVGOpeningHours) * tage );  
    }

  });

 // Fill Livepoints value  
 jQuery('.ids-form .selected-locations input').val( selectedLocations );
 jQuery('.ids-form .selected-locations input').trigger( "change" );

 // Fill Monitore value
 screenAvailableElement.val( totalMoniter );
 screenAvailableElement.trigger( "change" );

  // Fill Sichtkontakte value
  Sichtkontakte = Math.floor(parseFloat(Sichtkontakte));
  jQuery('.ids-form .overlays input').val( new Number(Sichtkontakte).toLocaleString("de-DE") );
  jQuery('.ids-form .overlays input').trigger( "change" );

  // console.log( tage );
  // console.log( totalMoniter );
  // console.log( Sichtkontakte ); 
}

jQuery(document).ready(function(){

  jQuery(document).on( 'keypress', '.gform_wrapper', function (e) {
      var code = e.keyCode || e.which;
      if ( code == 13 && ! jQuery( e.target ).is( 'textarea,input[type="submit"],input[type="button"]' ) ) {
          e.preventDefault();
          return false;
      }
  } );

  setTimeout( function(){
    jQuery('.ids-form #input_2_125').trigger('change');
    jQuery(document).trigger('gform_page_loaded', [ 2, 2]);
  },100);

  // Show login form
  jQuery("body").on("click", ".login-button",function(){
    // jQuery(".custom-popup").addClass('active');
    jQuery(".custom-popup").fadeIn("2000");
  });

  // forced login form submission
  jQuery("body").on("click", "#field_16_3 .gform_next_button",function(){
    // jQuery(".custom-popup").addClass('active');
    jQuery(".gform_footer.top_label #gform_submit_button_16").trigger("click");
  });

  // Show login form
  jQuery("body").on("click", ".custom-close",function(){
    // jQuery(".custom-popup").addClass('active');
    jQuery(".custom-popup").fadeOut("2000");
  });

  /* Hidden field that has been taken to show and hide filter results */ 
  var circleObj = null;
  var markerArray = [];
  var mapObject = null;
  var zoomLevel = {
      "5"    : 12,
      "10"   : 11,
      "15"   : 10,
      "20"   : 10,
      "25"   : 9,
      "30"   : 9,
      "40"   : 9,
      "50"   : 8,
      "75"   : 8,
      "100"  : 7,
      "150"  : 7,
      "200"  : 6,
      "300"  : 6,
      "400"  : 5,
      "500"  : 5,
      "750"  : 4,
      "1000" : 4,
      "1500" : 3
  };

  jQuery('body').on('click', '.ids-form #gform_next_button_custom', function(){
    jQuery('#gform_next_button_2_20').trigger('click');
  });

  /* show all packages */
  jQuery('body').on('click', '.ids-form #load-more-packages', function(){
    jQuery('li.lm-packages ul').addClass("show-all");
    jQuery('.ids-form #load-more-packages').hide();
    jQuery('.ids-form #load-less-packages').show();
    jQuery('li.show-packages input').val("more");
    jQuery('li.show-packages input').trigger("change");
  });

   /* show few packages */
  jQuery('body').on('click', '.ids-form #load-less-packages', function(){
    jQuery('li.lm-packages ul').removeClass("show-all");
    jQuery('.ids-form #load-less-packages').hide();
    jQuery('.ids-form #load-more-packages').show();
    jQuery('li.show-packages input').val("less");
    jQuery('li.show-packages input').trigger("change");
  });

  // Selected locations
  jQuery('body').on( 'change', '.ids-form li.area select', function(){
    jQuery('.ids-form li.selected-location-option input').val("");
  });

  // Selected locations
  jQuery('body').on( 'change', '.ids-form .all-locations input', function(){
    var selectedItems = [];
    jQuery('.ids-form .all-locations ul.gfield_checkbox  li > input:checked').each( function(){
      selectedItems.push(jQuery(this).attr('location-id'));
    });
    jQuery('.ids-form li.selected-location-option input').val( selectedItems.join(',') );
  });
  
  /* Reset fields */
  jQuery('body').on( 'click', '.ids-form .gfgeo-reset-location-button', function(){
    var showFilteredResults = jQuery('.is-filtered-enabled input'); 
    showFilteredResults.val("");
    showFilteredResults.trigger("change");
    jQuery('.ids-form')[0].reset();
  });
  
  jQuery('body').on( 'change', '.ids-form .selected-locations input', function(){
    jQuery('.selected-locations-preview').text( jQuery(this).val() );
  });

  jQuery('body').on( 'change', '.ids-form .screens-available input', function(){
    jQuery('.screens-available-preview').text( jQuery(this).val() );
  });

  // Custom calculations for 'Tage' field in GF
  // Field ID : 75
  jQuery('body').on( 'change', '.ids-form .duration input', function(){
    var tage = processTageCalculation();
    jQuery(this).val( tage );
    // Show Tage value in summary section
    jQuery('.duration-preview').text( jQuery(this).val() );
    jQuery(".start-date input").trigger("change");
  });

  jQuery('body').on( 'change', '.ids-form .add-views input', function(){
    jQuery('.add-views-preview').text( jQuery(this).val() );
  });

  jQuery('body').on( 'change', '.ids-form .overlays input', function(){
    jQuery('.overlay-preview').text( jQuery(this).val() );
  });

  jQuery('body').on( 'change', '.ids-form .amount input', function(){
    jQuery('.amount-preview').text( jQuery(this).val() );
  });

  /* Trigger all the hidden fields, that are used for live summary */ 
  jQuery(document).on('gform_page_loaded', function(event, form_id, current_page){
    if( form_id != 2){
      return;
    }

    if( current_page == 1 ){
      jQuery('input[name="gform_uploaded_files"]').val("");
    }
    
    jQuery('.ids-form .selected-locations input').trigger('change');
    jQuery('.ids-form .screens-available input').trigger('change');
    // jQuery('.ids-form .duration input').trigger('change');
    jQuery('.ids-form .add-views input').trigger('change');
    jQuery('.ids-form .overlays input').trigger('change');
    jQuery('.ids-form li.manual-days select').trigger('change');
    // jQuery('.ids-form input[name="input_165"]:checked').trigger('change');

    var AddressLine1 = jQuery(".add-line-1 input").val();
    var AddressLine2 = jQuery(".add-line-2 input").val();
    if( !(AddressLine1 == "" && AddressLine2 == "" ) ){
      jQuery('.ids-form .add-preview button').trigger('click');
    }
    
    jQuery('.ids-form #input_2_125').trigger('change');
    if( jQuery('li.show-packages input:checked').val() == 'less' ){
      jQuery('#load-less-packages').trigger('click');
    }
    
    if( jQuery('li.show-packages input:checked').val() == 'more' ){
      jQuery('#load-more-packages').trigger('click');
    }

    jQuery('.ids-form .lm-packages input').each(function(){
      var value = jQuery(this).val();
      var packageAttr = value.split('|');
      /* Auto fill views */
      if( typeof(packageAttr['1']) != 'undefined' && packageAttr['1'] == jQuery('li.amount input').val().replace(".","") ){
        jQuery(this).prop("checked", true);
        jQuery(".ids-form .amount-preview").text(jQuery('li.amount input').val());
      }
    });

    if( jQuery("#input_2_156").val() != "" ){
      if( !jQuery("li#field_2_156").hasClass("gfield_error") && !jQuery("li#field_2_157").hasClass("gfield_error") ){
        jQuery("#input_2_156").attr("readonly", "readonly");  
      }
    }

    if( jQuery("#input_2_157").val() != "" ){
      jQuery("#input_2_157").attr("readonly", "readonly");  
    }

    GF_Geo.geocoder_fields[ "2_79"].gfgeo_map_marker_url = "#";
    setTimeout(function(){ 
      jQuery('.ids-form .search-location .search-filter-button').trigger('click');
      // Auto select frequency
      var frequency = jQuery('.ids-form input[name="input_165"]:checked').val();
      var ID = jQuery( ".frequency-radios input[value='"+frequency+"']" ).attr("id");
      jQuery("#"+ID).prop( "checked", true );
      jQuery("#"+ID).trigger( "change" );
    }, 100);

  });

  jQuery('body').on( 'keyup', '#input_2_156', function(){
    var val = jQuery(this).val();
    var elem =  jQuery("#input_2_157");
    elem.attr("readonly", "readonly");  
    elem.val(val);
    // Update next email address
    jQuery("#input_2_170").val(val);
  });

  /*View Add*/
  jQuery('body').on( 'click', '.ids-form .add-preview button', function(){

    var self = jQuery(this);
    self.attr( 'disabled', true );
    var AddressLine1 = jQuery(".add-line-1 input").val();
    var AddressLine2 = jQuery(".add-line-2 input").val();
    var domain       = jQuery(".domain input").val();

    var img = '';
    var uploadedFiles = jQuery('input[name="gform_uploaded_files"]').val();
    var tempFile= "";
    if( typeof(uploadedFiles) != 'undefined' && uploadedFiles != "" ){
      var parseValue = JSON.parse(uploadedFiles);
      if( typeof(parseValue["input_41"] ) != 'undefined' ){
        if( parseValue["input_41"].length > 0 ){
          tempFile = livemessagesObj.homeURL+'/wp-content/uploads/gravity_forms/2-67a1c7bea46b0c596f2e3b01d6e007d0/tmp/'+parseValue["input_41"][0]["temp_filename"];  
        } 
      }
    }

    if( jQuery('.qrcode input').val() != "" && jQuery('input[name="input_118"]:checked').val() == "qr_code" ){
      tempFile= "https://chart.googleapis.com/chart?chs=536x536&cht=qr&chl="+jQuery('.qrcode input').val()+"&choe=UTF-8&chld=1|1";
    }

    if( uploadedFiles == "" && jQuery('.qrcode input').val() == "" ) {
      tempFile = jQuery("#input_2_135").val();
    }

    // Show loader
    jQuery('.ids-form .add-preview-html').html('<img src="/wp-content/uploads/2021/05/loading-buffering.gif" style="width: 50px !important; margin: 0 auto; padding: 60px 0;">');

    // Creating image
    var data = {
      'action'       : 'create_image_preview',
      'AddressLine1' : AddressLine1,
      'AddressLine2' : AddressLine2,
      'logo'         : tempFile,
      'domain'       : domain
    };
    jQuery.post(livemessagesObj.adminAjax, data, function (response) {
      // console.log( response );
      jQuery('.ids-form .add-preview-html').css('height','auto');
      jQuery('.ids-form .add-preview-html').html('<img src="'+response+'">');
      self.removeAttr('disabled');
    }).fail(function () {
      self.removeAttr('disabled');
    });

  });

  // Show add preview
  jQuery('body').on( 'change', '.ids-form #input_2_125', function(){
    var orderID = jQuery( this ).val();
    // Creating image
    var domain       = jQuery(".domain input").val();
    var data = {
      'action'       : 'get_order_details',
      'order_id'     : orderID,
      'domain'       : domain
    };
    jQuery.post(livemessagesObj.adminAjax, data, function (response) {
      jQuery('#order-details-preview').html(response);
    });
  });

  // On change packages 
  jQuery('body').on( 'change', '.ids-form .lm-packages input', function(){
    var packageValue = jQuery('.ids-form .lm-packages input:checked').next('label').text();
    var packageAttr = packageValue.split('|');
    /* Auto fill views */
    if( typeof(packageAttr['0']) != 'undefined' ){
      var views = packageAttr['0'].split(' ');
      jQuery('.ids-form .add-views input').val( views['0'] );
      jQuery('.ids-form .add-views input').trigger('change');
      // jQuery('.ids-form .all-locations input').trigger('change');
      jQuery( "#field_2_165 input:checked" ).trigger( "click" );
    }
  });

  // Trigger next button
  jQuery('body').on( 'change', '.ids-form .welcome-buttons input', function(){
    jQuery('#gform_page_2_1 .gform_page_footer .gform_next_button').trigger("click");
  });
  
  jQuery('body').on( 'change', '.ids-form .custom-radio.inline-list ul li input[type="radio"]', function(){
    jQuery(this).parents('ul').find('li').removeClass('checked');
    if ( jQuery(this).is(':checked') ){
      jQuery(this).parent().addClass('checked');
    }
  });

  // Show manual days ( only for premium partner )
  jQuery('body').on( 'change', '.ids-form li.manual-days select', function(){
    jQuery('.ids-form .duration-previe-r').text( jQuery(this).val() );
  });

  // Change button text 
  jQuery(".booking-select-box select").change( function(){
    var val = jQuery(this).val();
    if( typeof(val) != "undefined" && val != "" ){
      jQuery("#gform_next_button_2_123").val("BUCHUNG WIEDERVERWENDEN >");
    }else{
      jQuery("#gform_next_button_2_123").val("NEUE BUCHUNG >");
    }
  });

  // Modify date into normal date format i.e yyyy-mm-dd
  var simpleDateFormat = function(date){
    return date.split(".").reverse().join("-");
  }

  // Display end data
  jQuery('body').on( 'change', '.start-date input.datepicker', function(){
    
    if( jQuery(this).val() == "" ){
      return;
    }

    // Campaign start date
    const startDate = new Date( simpleDateFormat( jQuery(this).val() ) );

    // Calculated duration
    var tage = parseInt(jQuery(".duration input").val());  
    
    // Add days
    var endDate = new Date(startDate.addDays(tage));
    jQuery(".end-date input").val( endDate.getDate()+"."+(endDate.getMonth() + 1)+"."+endDate.getFullYear() );

  });

  Date.prototype.addDays = function (days) {
    const date = new Date(this.valueOf());
    date.setDate(date.getDate() + days);
    return date;
  };

  var cachingLocations = false;

  var cachingLocationOptions = false;

  /*On Clicking filter button*/
  jQuery('body').on('click', '.ids-form .search-location .search-filter-button', function(){

    cachingLocationOptions = true;

    var showFilteredResults = jQuery('.is-filtered-enabled input'); 

    //  Seleect all checkboxes
    var selectedItems = jQuery('.ids-form li.selected-location-option input').val();

    /* Show loading image */
    jQuery('.loading-results').show();

    // Removing existing circle and marker
    showFilteredResults.trigger("change");

    if( circleObj !== null){
      // remove circle object
      circleObj.setMap(null);
    }
   
    for(var i=0; i < markerArray.length; i++){
      // Remove Marker;
      markerArray[i].setMap(null);
    }

    //console.log(markerArray);
    var mapID = "0";
    if ( typeof(jQuery("li.gfield.live-map-view:visible").attr("id")) != 'undefined'){
      // return;
      mapID = jQuery("li.gfield.live-map-view:visible").attr("id").replace("field_","");
    }

    // console.log(mapID);

    if ( typeof(GF_Geo) !== 'undefined' 
    && typeof(GF_Geo.maps) !== 'undefined'
    &&  typeof(GF_Geo.maps[mapID]) !== 'undefined'
    && typeof(GF_Geo.maps[mapID].map) !== 'undefined'){
      mapObject = GF_Geo.maps[mapID].map;
    }

    circleObj = null;
    markerArray = [];
    
    var iconImage   = livemessagesObj.iconImage;
    var circleColor = livemessagesObj.circleColor; 

    if( cachingLocations !== false && selectedItems.length != 0 && !jQuery('#gform_page_2_2').is(':visible') ){
      options = cachingLocations;
      jQuery('li.all-locations ul.gfield_checkbox').html(options);
      
      showFilteredResults.val("1");
      showFilteredResults.trigger("change");
      
      if( selectedItems.length == 0 ){
        jQuery('.ids-form .all-locations ul.gfield_checkbox  li > input').attr("checked", true);
        // jQuery('.ids-form .all-locations ul.gfield_checkbox  li > input').trigger("change");
      }else{

        var i = 1;
        jQuery('.ids-form .all-locations ul.gfield_checkbox  li > input').each( function(){
          var optionID = jQuery(this).attr('location-id');
          if(jQuery.inArray( optionID, selectedItems.split(',') ) !== -1){
            jQuery('.ids-form .all-locations ul.gfield_checkbox  li > input[location-id="'+optionID+'"]').attr("checked", true);
            // jQuery('.ids-form .all-locations ul.gfield_checkbox  li > input[location-id="'+optionID+'"]').trigger("change");
          }
          if( jQuery('.ids-form .all-locations ul.gfield_checkbox  li > input').length == i ){
            cachingLocationOptions = false;
          }
          i = i + 1;
        });
      }

      /* Hide loading image */
      jQuery('.ids-form .duration input').trigger('change');
      jQuery('.loading-results').hide();
      return;
    }

    showFilteredResults.val("");
    showFilteredResults.trigger("change");

    // console.log('Search start');
    var data = {
      'action'   : 'search_locations',
      'latitude' : jQuery('li.latitude input').val(),
      'longitude': jQuery('li.longitude input').val(),
      'radius'   : jQuery('li.gfield.area select').val()
    };

    // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
    jQuery.post(livemessagesObj.adminAjax, data, function (response) {
      // console.log('Search complete');
      var tempResponse = JSON.parse(response);
      if( tempResponse["status"].code == 1 ){
        // console.log(tempResponse["body"].length); 
        var options = "<p style='color:#fff;text-align:center;'>No result found</p>";
        if( tempResponse["body"].length > 0 ){
          var listID = jQuery('li.all-locations').attr('id').split("_");
          var formID  = listID[1];
          var fieldID = listID[2];
          options = "";
          var serial = 0;
          for( i in tempResponse["body"]) {
            index = parseInt(i) + 1;
            var singleObject = tempResponse["body"][i];
            options += '<li class="gchoice_'+formID+'_'+fieldID+'_'+index+'">'
                        +'<input name="input_'+fieldID+'.'+index+'" type="checkbox" location-id="'+tempResponse["body"][i].location_id+'"  data-latitude="'+tempResponse["body"][i].latitude+'" data-longitude="'+tempResponse["body"][i].longitude+'" data-avg-hours="'+tempResponse["body"][i].AVGOpeningHours+'" data-value="'+tempResponse["body"][i].screen+'" value="'+tempResponse["body"][i].name+" - "+tempResponse["body"][i].location_id+'" id="choice_'+formID+'_'+fieldID+'_'+index+'" data-weekly-contacts="'+tempResponse["body"][i].weekly_contacts+'" data-serial="'+serial+'">'
                        +'<label for="choice_'+formID+'_'+fieldID+'_'+index+'" id="label_'+formID+'_'+fieldID+'_'+index+'">'+tempResponse["body"][i].name+' <span>('+tempResponse["body"][i].distance+'km)</span></label>'
                      +'</li>';

            //  console.log('singleObject', singleObject);
            //  console.log('option', options);

            // Adding Marker
            if( mapObject !== null 
              && typeof( singleObject.latitude ) !== 'undefined'
              && typeof( singleObject.longitude ) !== 'undefined'){

              var latitude = parseFloat(singleObject.latitude);
              var longitude = parseFloat(singleObject.longitude);
              var name = singleObject.name || "";

              if( isNaN(latitude) || isNaN(longitude)){
                continue;
              }
             var marker = new google.maps.Marker({
            
                position: {
                  lat: latitude,
                  lng: longitude
                },
                title: name,
                icon: iconImage
              });       
              // To add the marker to the map, call setMap();
              marker.setMap(mapObject);
              markerArray.push(marker);

              }   
               serial = parseInt(serial)+1;

              }
      
              if( mapObject !== null){

                var radius = jQuery('li.gfield.area select').val();
                var latitude = jQuery('.latitude input').val();
                var longitude = jQuery('.longitude input').val();

                latitude = parseFloat(latitude);
                longitude = parseFloat(longitude);
                radius = parseFloat(radius);
                
                if( !isNaN(latitude) && !isNaN(longitude) && !isNaN(radius)){
                  circleObj =  new google.maps.Circle({
                    strokeColor: circleColor,
                    strokeOpacity: 0.8,
                    strokeWeight: 2,
                    fillColor: circleColor,
                    fillOpacity: 0.35,
                    map: mapObject,
                    // gestureHandling: "greedy",
                    center:{ 
                        lat: latitude, 
                        lng: longitude 
                    },
                    radius: radius * 1000,
                  });

                  mapObject.setZoom( zoomLevel[radius] );
                }
                
              }

          // // Adding Radius
          // circleObj = null;
          // markerArray = [];
        }
        // console.log(options);

        cachingLocations = options;

        jQuery('li.all-locations ul.gfield_checkbox').html(options);
        showFilteredResults.val("1");
        showFilteredResults.trigger("change");
        
        if( selectedItems.length == 0 ){

          var i = 1;
          jQuery('.ids-form .all-locations ul.gfield_checkbox  li > input').each( function(){
            if( jQuery('.ids-form .all-locations ul.gfield_checkbox  li > input').length == i ){
              cachingLocationOptions = false;
            }
            jQuery(this).attr("checked", true);
            jQuery(this).trigger("change");
            i = i + 1;
          });

        }else{
          var i = 1;
          jQuery('.ids-form .all-locations ul.gfield_checkbox  li > input').each( function(){
            var optionID = jQuery(this).attr('location-id');
            if(jQuery.inArray( optionID, selectedItems.split(',') ) !== -1){
              jQuery('.ids-form .all-locations ul.gfield_checkbox  li > input[location-id="'+optionID+'"]').attr("checked", true);
              jQuery('.ids-form .all-locations ul.gfield_checkbox  li > input[location-id="'+optionID+'"]').trigger("change");
            }
            if( jQuery('.ids-form .all-locations ul.gfield_checkbox  li > input').length == i ){
              cachingLocationOptions = false;
            }
            i = i + 1;
          });
        }
      }

      /* Hide loading image */
      jQuery('.loading-results').hide();
      var val = jQuery('.ids-form input[name="input_165"]:checked').val();
      jQuery( ".ids-faq.monitre input[value='"+val+"']" ).prop( "checked", true );
      jQuery( ".ids-faq.monitre input[value='"+val+"']" ).trigger( "click" );
    }).fail(function () {
      //alert('oops something went wrong while saving data');
      // console.log('oops something went wrong while saving data');
      /* Hide loading image */
      jQuery('.loading-results').hide();
    });

  });

  // Calculate add view and days on location change
  jQuery('body').on( 'change', '.ids-form .all-locations input', function(){

    //remove and add marker on checkbox click
    jQuery(this).click(function() {
      var indexID  = jQuery(this).attr('data-serial');
      if( typeof(indexID) != "undefined" && indexID != "" && typeof(markerArray[indexID]) != "undefined" ){
        markerArray[indexID].setMap(null);
        if (jQuery(this).prop('checked')==false) {
          markerArray[indexID].setMap(null);
        }
        if (jQuery(this).prop('checked')==true) {
          markerArray[indexID].setMap(mapObject);
        }
      }
    });

    // Selected frequency
    if( cachingLocationOptions === false ){
      var frequency = jQuery('.ids-form input[name="input_165"]:checked').val();
      processSichtkontakteCalculation(frequency);
    }
  });

  // Remove locations
  jQuery(document).on( 'keyup', '#input_2_78', function () {
    jQuery('.ids-form li.selected-location-option input').val("");
    cachingLocations = false;
  });

  jQuery(document).on( 'change', '#input_2_76', function () {
    cachingLocations = false;
  });

});

// Restrict datepicker date to till next three days
gform.addFilter( 'gform_datepicker_options_pre_init', function( optionsObj, formId, fieldId ) {
  if ( formId == 2 && fieldId == 27 ) {
    // optionsObj.minDate = 0;
    optionsObj.minDate = '+3 d';
  }
  return optionsObj;
} );

// Trigger tooltip on gfprm page load
jQuery(document).on('gform_page_loaded', function(event, form_id, current_page){

  setTimeout( function(){
    /*Remove hidden object for frequency field*/ 
    if( livemessagesObj.parentHost == 'www.regioads24.de' ){
      jQuery('#field_2_40').remove();
    }
  },100);

  jQuery('.easygf-tooltip').tooltipster({
      trigger: 'custom',
      triggerOpen: {
        tap: true,
        mouseenter: true,
        click: false,
      },
      triggerClose: {
        mouseleave: true,
        click: false,
      },
      functionInit: function(instance, helper){
        var content = jQuery(helper.origin).find('.tooltip_content').detach();
        instance.content(content);
      },		
     theme: 'tooltipster-light',
     animation: 'grow',
     delay: 300,
     side: 'top',
     contentAsHTML: 'true',
  });

  // Change upload logo text
  jQuery('.qr-image .gform_drop_instructions').text('Logo oder Grafik hochladen');  

  // Change calculations on frequency change
  jQuery('body').on( 'click', '.ids-form input[name="input_165"]:checked', function(){
    var val = jQuery( this ).val();
    jQuery(".days-frequency-preview").html(val);
    processSichtkontakteCalculation(val);
  });
  
  jQuery('body').on( 'change', '.ids-form input[name="input_radio"]', function(){
    var val = jQuery( this ).val();
    jQuery( "#field_2_165 input[value='"+val+"']" ).prop( "checked", true );
    jQuery( "#field_2_165 input[value='"+val+"']" ).trigger( "click" );
  });

  var val = jQuery(".booking-select-box select").val();
  if( typeof(val) != "undefined" && val != "" ){
    jQuery("#gform_next_button_2_123").val("BUCHUNG WIEDERVERWENDEN >");
  }else{
    jQuery("#gform_next_button_2_123").val("NEUE BUCHUNG >");
  }

  // Change button
  jQuery(".booking-select-box select").change( function(){
    var val = jQuery(this).val();
    if( typeof(val) != "undefined" && val != "" ){
      jQuery("#gform_next_button_2_123").val("BUCHUNG WIEDERVERWENDEN >");
    }else{
      jQuery("#gform_next_button_2_123").val("NEUE BUCHUNG >");
    }
  });

  // 
  jQuery("#field_2_131 input").change( function(){
    jQuery("#field_2_156 input").trigger("keyup");
  });

  var firstChoice  = jQuery("#field_2_121");
  var secondChoice = jQuery("#field_2_159");
  var leftHTML  = "<li id='"+firstChoice.attr("id")+"' class='"+firstChoice.attr("class")+"'>"+firstChoice.html()+"</li>";
  var rightHTML = "<li id='"+secondChoice.attr("id")+"' class='"+secondChoice.attr("class")+"'>"+secondChoice.html()+"</li>";
  firstChoice.remove();
  secondChoice.remove();
  jQuery("#field_2_64").before("<div class='terms-wrapper'>"+leftHTML+rightHTML+"</div>");

  if( current_page == 2 ){
    // Modify date into normal date format i.e yyyy-mm-dd
    var simpleDateFormat = function(date){
      return date.split(".").reverse().join("-");
    }

    jQuery('body').on( 'change', '.start-date input.datepicker', function(){
      
      if( jQuery(this).val() == "" ){
        return;
      }

      // Campaign start date
      const startDate = new Date( simpleDateFormat( jQuery(this).val() ) );

      // Calculated duration
      var tage = parseInt(jQuery(".duration input").val());  
      
      // Add days
      var endDate = new Date(startDate.addDays(tage));
      jQuery(".end-date input").val( endDate.getDate()+"."+(endDate.getMonth() + 1)+"."+endDate.getFullYear() );

    });

    Date.prototype.addDays = function (days) {
      const date = new Date(this.valueOf());
      date.setDate(date.getDate() + days);
      return date;
    };
    jQuery(".start-date input").trigger("change");
  }

  jQuery("#input_2_156").trigger("keyup");
  
});