jQuery(document).ready(function(){

  /* Hidden field that has been taken to show and hide filter results */ 
  var showFilteredResults = jQuery('.is-filtered-enabled input'); 

  var circleObj = null;
  var markerArray = [];
  var mapObject = null;
  
  /*On Clicking filter button*/
  jQuery('body').on('click', '.ids-form .search-location .search-filter-button', function(){
    showFilteredResults.val("");
    showFilteredResults.trigger("change");
    /* Show loading image */
    jQuery('.loading-results').show();
    // console.log('Search start');
    var data = {
      'action'   : 'search_locations',
      'latitude' : jQuery('li.latitude input').val(),
      'longitude': jQuery('li.longitude input').val(),
      'radius'   : jQuery('li.gfield.area select').val()
    };

    // Removing existing circle and marker

    if( circleObj !== null){
      // remove circle object
      circleObj.setMap(null);
    }

    for(var i=0; i < markerArray.length; i++){
      // Remove Marker;
      markerArray[i].setMap(null);
    }
   
    var mapID = jQuery("li.gfield.live-map-view").attr("id").replace("field_","");

    // console.log(mapID);

    if ( typeof(GF_Geo) !== 'undefined' 
    && typeof(GF_Geo.maps) !== 'undefined'
    &&  typeof(GF_Geo.maps[mapID]) !== 'undefined'
    && typeof(GF_Geo.maps[mapID].map) !== 'undefined'){
      mapObject = GF_Geo.maps[mapID].map;
    }

    circleObj = null;
    markerArray = [];

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
          for( i in tempResponse["body"]) {
            index = parseInt(i) + 1;
            var singleObject = tempResponse["body"][i];
            options += '<li class="gchoice_'+formID+'_'+fieldID+'_'+index+'">'
                        +'<input name="input_'+fieldID+'.'+index+'" type="checkbox" value="'+tempResponse["body"][i].screen+'" id="choice_'+formID+'_'+fieldID+'_'+index+'">'
                        +'<label for="choice_'+formID+'_'+fieldID+'_'+index+'" id="label_'+formID+'_'+fieldID+'_'+index+'">'+tempResponse["body"][i].name+' <span>('+tempResponse["body"][i].distance+'km)</span></label>'
                      +'</li>';

            // console.log('singleObject', singleObject);

            // Adding Marker
            if( mapObject !== null 
              && typeof( singleObject.latitude ) !== 'undefined'
              && typeof( singleObject.longitude ) !== 'undefined'){

              var latitude = parseFloat(singleObject.latitude);
              var longitude = parseFloat(singleObject.longitude);
              var name = singleObject.name || "";

              // console.log({longitude, latitude, name});

              if( isNaN(latitude) || isNaN(longitude)){
                continue;
              }
 
              var marker = new google.maps.Marker({
                position: {
                  lat: latitude,
                  lng: longitude
                },
                title: name
              });       
              // To add the marker to the map, call setMap();
              marker.setMap(mapObject);
              markerArray.push(marker);
            }

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
                strokeColor: "#17afc9",
                strokeOpacity: 0.8,
                strokeWeight: 2,
                fillColor: "#17afc9",
                fillOpacity: 0.35,
                map: mapObject,
                center:{ 
                    lat: latitude, 
                    lng: longitude 
                },
                radius: radius * 1000,
            });
            }
          }

          // // Adding Radius
          // circleObj = null;
          // markerArray = [];
        }
        // console.log(options);
        jQuery('li.all-locations ul.gfield_checkbox').html(options);
        showFilteredResults.val("1");
        showFilteredResults.trigger("change");
        jQuery('.ids-form .lm-packages input:checked').trigger("change");
        
        //  Seleect all checkboxes
        jQuery('.ids-form .all-locations ul.gfield_checkbox  li > input').attr("checked", true);
        jQuery('.ids-form .all-locations ul.gfield_checkbox  li > input').trigger("change");
      }
      /* Hide loading image */
      jQuery('.loading-results').hide();
    }).fail(function () {
      //alert('oops something went wrong while saving data');
      console.log('oops something went wrong while saving data');
      /* Hide loading image */
      jQuery('.loading-results').hide();
    });
  });

  /* Reset fields */
  jQuery('body').on( 'click', '.ids-form .gfgeo-reset-location-button', function(){
    showFilteredResults.val("");
    showFilteredResults.trigger("change");
    jQuery('.ids-form')[0].reset();
  });

  jQuery('body').on( 'change', '.ids-form .all-locations input', function(){
    var totalMoniter = 0;
    var selectedLocationsElement = jQuery('.ids-form .all-locations input:checked');
    var screenAvailableElement = jQuery('.ids-form .screens-available input');
    var selectedLocations = selectedLocationsElement.length;
    jQuery('.ids-form .selected-locations input').val( selectedLocations );
    jQuery('.ids-form .selected-locations input').trigger( "change" );
    selectedLocationsElement.each(function(){
      if( typeof(jQuery(this).val()) != "undefined" && jQuery(this).val() != "" ){
        totalMoniter = parseInt( totalMoniter ) + parseInt( jQuery(this).val() );
      }
    });
    screenAvailableElement.val( totalMoniter );
    screenAvailableElement.trigger( "change" );
  });

  jQuery('body').on( 'change', '.ids-form .selected-locations input', function(){
    jQuery('.selected-locations-preview').text( jQuery(this).val() );
  });

  jQuery('body').on( 'change', '.ids-form .screens-available input', function(){
    jQuery('.screens-available-preview').text( jQuery(this).val() );
  });

  jQuery('body').on( 'change', '.ids-form .duration input', function(){
    jQuery('.duration-preview').text( jQuery(this).val() );
  });

  jQuery('body').on( 'change', '.ids-form .add-views input', function(){
    jQuery('.add-views-preview').text( jQuery(this).val() );
  });

  jQuery('body').on( 'change', '.ids-form .amount input', function(){
    jQuery('.amount-preview').text( jQuery(this).val() );
  });

  /* Trigger all the hidden fields, that are used for live summary */ 
  jQuery(document).on('gform_page_loaded', function(event, form_id, current_page){
    jQuery('.ids-form .selected-locations input').trigger('change');
    jQuery('.ids-form .screens-available input').trigger('change');
    jQuery('.ids-form .duration input').trigger('change');
    jQuery('.ids-form .add-views input').trigger('change');
    jQuery('.ids-form .amount input').trigger('change');
    jQuery('.ids-form .add-preview button').trigger('click');
  });

  /*View Add*/
  jQuery('body').on( 'click', '.ids-form .add-preview button', function(){
    var AddressLine1 = jQuery(".add-line-1 input").val();
    var AddressLine2 = jQuery(".add-line-2 input").val();
    var img = '';
    var uploadedFiles = jQuery('input[name="gform_uploaded_files"]').val();
    var tempFile= "";
    if( typeof(uploadedFiles) != 'undefined' && uploadedFiles != "" ){
      var parseValue = JSON.parse(uploadedFiles);
      if( typeof(parseValue["input_41"] ) != 'undefined' ){
        tempFile = '/wp-content/uploads/gravity_forms/2-67a1c7bea46b0c596f2e3b01d6e007d0/tmp/'+parseValue["input_41"][0]["temp_filename"];   
      }
    }

    if( jQuery('#input_2_35').val() != "" ){
      tempFile= "/wp-content/uploads/2021/05/simple_qrcode.png";
    }

    img = '<img src="'+tempFile+'">';
    jQuery('.ids-form .add-preview-html').html('<div class="ad-preview-left"><span id="address-line-1">'+AddressLine1+'</span> <br> <span id="address-line-2">'+AddressLine2+'</span></div><div class="ad-preview-right"><span id="qr-image">'+img+'</span></div>');
  });
  
  /* On change packages */ 
  jQuery('body').on( 'change', '.ids-form .lm-packages input', function(){
    var packageText = jQuery('.ids-form .lm-packages input:checked').next('label').text();;
    var packageAttr = packageText.split('-');
    /* Auto fill views */
    if( typeof(packageAttr['0']) != 'indefined' ){
      var packageValue = packageAttr['0'].replace("Package","");
      jQuery('.ids-form .add-views input').val( packageValue.split('.').join("") );
      jQuery('.ids-form .add-views input').trigger('change');
    }
    /* Auto fill price */
    if( typeof(packageAttr['0']) != 'indefined' ){
      jQuery('.ids-form .amount input').val(packageAttr['1'].replace("Euro",""));
      jQuery('.ids-form .amount input').trigger('change');
    }
  });

});