<html>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
<script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyC-no8HxYHKcAuRefopDRuGImK-aaXoiXA&callback=initMap" type="text/javascript"></script>
<script src="fancywebsocket.js"></script>
<link href="style.css" type="text/css" rel="stylesheet">
<body style="margin:0px; padding:0px;" >
<div class="content-wrapper">
    <div class="header"><img src="title.png"></div>
    <div id="map_canvas" style="height:500px; width:100%;"></div>
    <div class="country-name-wrapper"></div>
    <div class="footer">
        <div class="current-users summary">Current: <span class="number-user">0</span></div>
        <div class="total-users summary">Total: <span class="number-user">0</span></div>
    </div>
</div>
</body>
<script type="text/javascript">
    var map = null;
    var my_boundaries = {};
    var data_layer;
    var info_window;
    var Server;
    var defaultIcon = "marker.png";
    var new_marker;
    var host = 'http://haonguyen.me:5000/';


    function send( text ) {
        Server.send( 'message', text );
    }

    //log('Connecting...');
    Server = new FancyWebSocket('ws://haonguyen.me:3388');
    //initialize map on document ready
    $(document).ready(function(){
        $('#message').keypress(function(e) {
            if (e.keyCode == 13 && this.value) {
                //log('You: ' + this.value);
                send(this.value);
                $(this).val('');
            }
        });
        $('#map_canvas').on('click', 'button.cancel', function(e){
            if (new_marker != null){
                new_marker.setMap(null);
                new_marker = null;
            }
            if (info_window != null){
                info_window.setMap(null);
                info_window = null;
            }

        });
        $('#map_canvas').on('click', 'button.accept', function(e){
            var coordinate = $('input.coordinate').val();
            var countryCode = $('input.country-code').val();
            var createUrl = host + 'create';
            if ($.trim(countryCode) == ''){
                alert('Please enter country code!');
                return false;
            }
            var latlng = coordinate.split(',');
            var lat = parseFloat(latlng[0]);
            var lng = parseFloat(latlng[1]);
            var data = {
                "type": "Point",
                "coordinates": [lng, lat],
                "country_code": countryCode
            }
            $.ajax({
                "url": 'test.php',
                "beforeSend": function(xhrObj){
                    xhrObj.setRequestHeader("Content-Type","application/json");
                    xhrObj.setRequestHeader("Accept","application/json");
                },
                "data": JSON.stringify(data),
                "type": 'post',
                "crossDomain": true,
                "dataType": 'json',
                "contentType": 'application/json',
                success: function(resp) {
                    if (resp.error_code == 0){
                        send(JSON.stringify(data));
                    } else {
                        alert("There's an existing record with same country_code");
                    }

                },
                error: function() { alert('Failed!'); },
            })
        });

        //Let the user know we're connected
        Server.bind('open', function() {
            //log( "Connected." );
        });

        //OH NOES! Disconnection occurred.
        Server.bind('close', function( data ) {
        //log( "Disconnected." );
        });

        //Log any messages sent from server
        Server.bind('message', function( payload ) {
            //log( payload );
            console.log(payload,'payload');
            var object = JSON.parse(payload); 
            doAnimation(object);
            //set total and current
            setTotal(object.total); //get number from server;
            setCurrent(object.active);//get number from server;
        });

        Server.connect();
    });
    
    function replaceAll(str, find, replace) {
        return str.replace(new RegExp(find, 'g'), replace);
    }

    function setTotal(number){
        var numberTotal = number != undefined ? number : 0;
        $('.total-users > .number-user').text(numberTotal);
    }
    function setCurrent(number){
        var numberCurrent = number != undefined ? number : 0;
        $('.current-users > .number-user').text(numberCurrent);
    }
    function doAnimation(data){
        var country_code = data.country_code;
        $.get('https://restcountries.eu/rest/v2/alpha/' + country_code, function (resp) {
            if (resp.nativeName !== undefined){
                //hide infowindow
                if (info_window != null){
                    info_window.setMap(null);
                }

                if (new_marker != null){
                    new_marker.setMap(null);
                }
                info_window = null;
                new_marker = null;
                // var coordinates = data.coordinates;
                // console.log(data);
                //create new marker
                var marker = new google.maps.Marker({
                    position: {"lat": data.lat, "lng": data.lng},
                    map: map,
                    icon: {
                        url: defaultIcon,
                        size: new google.maps.Size(70, 70),
                        origin: new google.maps.Point(0, 0),
                        anchor: new google.maps.Point(40, 25),
                        scaledSize: new google.maps.Size(80, 80)
                    },
                    title: 'New position'
                });
                //amination
                marker.setAnimation(google.maps.Animation.BOUNCE);
                var center = new google.maps.LatLng(data.lat, data.lng);
                // using global variable:
                map.panTo(center);
                //append the country name
                var countryName = resp.nativeName;
                $('.country-name-wrapper').append('<span class="marquee">' + countryName + '</span>');
                setTimeout(function(){
                    marker.setMap(null);
                    marker = null;
                    $('.country-name-wrapper').html('');
                }, 10000)
            } else {
                alert('Invalid country code!');
            }

        })
    }
    function initMap(){
        var latlng = new google.maps.LatLng(40.723080, -73.984340); //you can use any location as center on map startup
        var myOptions = {
            zoom: 2,
            center: latlng,
            mapTypeControl: true,
            mapTypeControlOptions: {style: google.maps.MapTypeControlStyle.DROPDOWN_MENU},
            navigationControl: true,
            mapTypeId: google.maps.MapTypeId.ROADMAP
        };
        map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);
        google.maps.event.addListener(map, 'click', function(){
            if(info_window){
                info_window.setMap(null);
                info_window = null;
            }
        });
        initializeDataLayer();
    }

    function initializeDataLayer(){
        if(data_layer){
            data_layer.forEach(function(feature) {
                data_layer.remove(feature);
            });
            data_layer = null;
        }
        data_layer = new google.maps.Data({map: map}); //initialize data layer which contains the boundaries. It's possible to have multiple data layers on one map
        data_layer.setStyle({ //using set style we can set styles for all boundaries at once
            fillColor: 'white',
            strokeWeight: 1,
            fillOpacity: 0.1
        });

        map.addListener('dblclick', function(e) { //we can listen for a boundary click and identify boundary based on e.feature.getProperty('boundary_id'); we set when adding boundary to data layer
            console.log(e, 'latlong');
            var coordinate = e.latLng.lat() + ',' + e.latLng.lng();
            var html = '<div class="country-popop">' +
                '  <h3>Add position</h3>'
                +'<div class="content">'
                + '<div class="input-group">'
                + '<span class="label">Country code:</span><input type="text" class="text country-code"/></div>'
                + '<div class="input-group"><span class="label">Coordinate:</span><input type="text" value="' + coordinate + '" class="text coordinate" disabled/></div>'
                + '<div class="input-group button-group"> <button class="cancel">Cancel</button> <button class="accept">Ok</button> </div></div></div>';
            if(info_window){
                info_window.setMap(null);
                info_window = null;
            }
            if (new_marker){
                new_marker.setMap(null);
                new_marker = null;
            }
            new_marker = new google.maps.Marker({
                position: e.latLng,
                map: map,
                icon: {
                    url: defaultIcon,
                    size: new google.maps.Size(70, 70),
                    origin: new google.maps.Point(0, 0),
                    anchor: new google.maps.Point(40, 25),
                    scaledSize: new google.maps.Size(80, 80)
                },
                title: 'New position'
            });
            info_window = new google.maps.InfoWindow({
                size: new google.maps.Size(150,50),
                position: e.latLng,
                map: map,
                content: html
            });
            google.maps.event.addListener(info_window,'closeclick',function(){
                new_marker.setMap(null);
                new_marker = null;
            });
        });
    }
</script>
</html>
