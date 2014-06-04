/**
 * A distance widget that will display a circle that can be resized and will
 * provide the radius in km.
 *
 * @param {google.maps.Map} map The map to attach to.
 *
 * @constructor
 */

(function() {
    'use strict';
    var checking, map, nb_target, max_target;
    /**
     * A radius widget that add a circle to a map and centers on a marker.
     *
     * @constructor
     */

    function RadiusWidget() {
        var circle = new google.maps.Circle();

        var _distance = 4 / Math.pow(2, map.getZoom() - 13);
        this.set('distance', _distance);
        this.set('active', false);

        // Bind the RadiusWidget bounds property to the circle bounds property.
        this.bindTo('bounds', circle);

        // Bind the circle center to the RadiusWidget center property
        circle.bindTo('center', this);

        // Bind the circle map to the RadiusWidget map
        circle.bindTo('map', this);

        // Bind the circle radius property to the RadiusWidget radius property
        circle.bindTo('radius', this);

        circle.bindTo('strokeWeight', this);
        circle.bindTo('strokeColor', this);
        circle.bindTo('strokeOpacity', this);
        circle.bindTo('fillColor', this);
        circle.bindTo('fillOpacity', this);
        circle.bindTo('editable', this);

        circle.setOptions({
            'strokeWeight': 1
        });

        // Add the sizer marker
        var sizer = this.addSizer_();

        this.bindTo('visible', sizer);
        this.bindTo('draggable', sizer);

        google.maps.event.addListener(circle, 'click', function(event) {
            new DistanceWidget(map, event.latLng, {
                map: map
            });
        });
        google.maps.event.addListener(circle, "rightclick", function(event) {
            check(event.latLng.lat(), event.latLng.lng());
        });
    }
    RadiusWidget.prototype = new google.maps.MVCObject();

    /**
     * Update the radius when the distance has changed.
     */
    RadiusWidget.prototype.distance_changed = function() {
        this.set('radius', this.get('distance') * 1000);
    };


    /**
     * Add the sizer marker to the map.
     *
     * @private
     */
    RadiusWidget.prototype.addSizer_ = function() {
        var sizer = new google.maps.Marker({
            draggable: true,
            raiseOnDrag: false,
            crossOnDrag: true,
            title: 'Changer la taille du radar',
            icon: 'img/resize-off.png'
        });

        sizer.bindTo('map', this);
        sizer.bindTo('position', this, 'sizer_position');

        var me = this;

        google.maps.event.addListener(sizer, 'dragstart', function() {
            me.set('active', true);
        });

        google.maps.event.addListener(sizer, 'drag', function() {
            // Set the circle distance (radius)
            me.setDistance();
        });

        google.maps.event.addListener(sizer, 'dragend', function() {
            me.set('active', false);
        });

        return sizer;
    };

    /**
     * Update the center of the circle and position the sizer back on the line.
     *
     * Position is bound to the DistanceWidget so this is expected to change when
     * the position of the distance widget is changed.
     */
    RadiusWidget.prototype.center_changed = function() {
        var bounds = this.get('bounds');

        // Bounds might not always be set so check that it exists first.
        if (bounds) {
            var lng = bounds.getNorthEast().lng();

            // Put the sizer at center, right on the circle.
            var position = new google.maps.LatLng(this.get('center').lat(), lng);
            this.set('sizer_position', position);
        }
    };


    /**
     * Calculates the distance between two latlng points in km.
     * @see http://www.movable-type.co.uk/scripts/latlong.html
     *
     * @param {google.maps.LatLng} p1 The first lat lng point.
     * @param {google.maps.LatLng} p2 The second lat lng point.
     * @return {number} The distance between the two points in km.
     * @private
     */
    RadiusWidget.prototype.distanceBetweenPoints_ = function(p1, p2) {
        if (!p1 || !p2) {
            return 0;
        }

        var R = 6371; // Radius of the Earth in km
        var dLat = (p2.lat() - p1.lat()) * Math.PI / 180;
        var dLon = (p2.lng() - p1.lng()) * Math.PI / 180;
        var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(p1.lat() * Math.PI / 180) * Math.cos(p2.lat() * Math.PI / 180) *
            Math.sin(dLon / 2) * Math.sin(dLon / 2);
        var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        var d = R * c;
        return d;
    };

    /**
     *
     */
    RadiusWidget.prototype.valid_target = function(marker, radiusWidget) {

        marker.setDraggable(false);
        marker.setVisible(false);
        this.set('editable', false);
        //sizer marker
        this.set('visible', false);
        this.set('draggable', false);

        var target = this,
            data;
        $.ajax({
            url: "set.php",
            method: 'POST',
            data: {
                lat: marker.getPosition().lat(),
                lng: marker.getPosition().lng(),
                radius: radiusWidget.radius
            },
            success: function(response) {
                data = $.parseJSON(response);
                if (!data || data === "" || typeof data != 'object') {
                    return false;
                }
                if (data && data.success) {
                    if (data.inside) {
                        target.set('strokeColor', 'black');
                        target.set('strokeOpacity', '0.9');
                        target.set('fillColor', 'green');
                        target.set('fillOpacity', '0.1');
                    } else {
                        target.set('strokeColor', 'black');
                        target.set('strokeWeight', 0.9);
                        target.set('fillColor', 'red');
                        target.set('fillOpacity', '0.7');
                    }
                } else {
                    alert('Erreur: ' + data.message);
                }
            }
        });
    };

    /**
     * Set the distance of the circle based on the position of the sizer.
     */
    RadiusWidget.prototype.setDistance = function() {
        // As the sizer is being dragged, its position changes.  Because the
        // RadiusWidget's sizer_position is bound to the sizer's position, it will
        // change as well.
        var distance = this.distanceBetweenPoints_(this.get('center'), this.get('sizer_position')).toFixed(3);

        if (distance <= 0) {
            distance = 0.001;
        }
        // Set the distance property for any objects that are bound to it
        this.set('distance', distance);
    };

    var DistanceWidget = function(map, location, opt_options) {

        if (nb_target >= max_target) {
            google.maps.event.clearListeners(map, 'click');
            return false;
        }
        nb_target++;

        var options = opt_options || {};
        this.setValues(options);

        this.set('map', map);
        this.set('position', location);

        var marker = new google.maps.Marker({
            draggable: true,
            title: 'Changer la position',
            icon: 'http://maps.google.com/mapfiles/marker_black.png'
        });

        // Bind the marker map property to the DistanceWidget map property
        marker.bindTo('map', this);
        // Bind the marker position property to the DistanceWidget position property
        marker.bindTo('position', this);

        // Create a new radius widget
        var radiusWidget = new RadiusWidget();

        // Bind the radiusWidget map to the DistanceWidget map
        radiusWidget.bindTo('map', this);

        radiusWidget.bindTo('valid_target', this);

        // Bind the radiusWidget center to the DistanceWidget position
        radiusWidget.bindTo('center', this, 'position');

        // Bind to the radiusWidgets' distance property
        this.bindTo('distance', radiusWidget);

        // Bind to the radiusWidgets' bounds property
        this.bindTo('bounds', radiusWidget);

        google.maps.event.addListener(marker, 'dblclick', function() {
            radiusWidget.valid_target(marker, radiusWidget);
        });
    }
    DistanceWidget.prototype = new google.maps.MVCObject();

    var addTarget = function(wpt) {
        var location = new google.maps.LatLng(parseFloat(wpt.getAttribute("lat")), parseFloat(wpt.getAttribute("lng")));
        var radius = parseInt(wpt.getAttribute("radius"));
        var inside = !! parseInt(wpt.getAttribute("inside"));

        var strokeColor = 'black';
        var strokeOpacity = 0.9;
        var fillColor = 'red';
        var fillOpacity = 0.5;
        if (inside) {
            strokeColor = 'black';
            strokeOpacity = 0.9;
            fillColor = 'green';
            fillOpacity = 0.1;
        }
        var circle = new google.maps.Circle({
            map: map,
            radius: radius,
            center: new google.maps.LatLng(location.lat(), location.lng()),
            strokeWeight: 1,
            strokeColor: strokeColor,
            strokeOpacity: strokeOpacity,
            fillColor: fillColor,
            fillOpacity: fillOpacity
        });

        google.maps.event.addListener(circle, 'click', function(event) {
            new DistanceWidget(map, event.latLng, {
                map: map
            });
        });

        google.maps.event.addListener(circle, 'rightclick', function(event) {
            check(event.latLng.lat(), event.latLng.lng());
        });
    };

    var check = function(lat, lng) {
        if (checking || map.getZoom() < 21) {
            return false;
        }
        checking = true;
        $('.coordinates').html('<strong>' + lat.toFixed(5) + ', ' + lng.toFixed(5) + '</strong> ' +
            '<br /><img src="img/radar.gif" class="ajaxloader" alt="" title="Vérification en cours..." />');

        var chk_target;

        $.ajax({
            url: "check.php",
            method: 'POST',
            data: {
                lat: lat,
                lng: lng,
            },
            beforeSend: function() {
                chk_target = new google.maps.Circle({
                    map: map,
                    radius: 1,
                    center: new google.maps.LatLng(lat, lng),
                    strokeWeight: 0,
                    fillColor: 'orange',
                    fillOpacity: 0.6
                });
            },
            success: function(response) {
                $('.ajaxloader').hide();
                var data = $.parseJSON(response);
                if (!data || data === "" || typeof data != 'object') {
                    return false;
                }

                if (data && data.success) {
                    alert('Vous avez trouvé et désamorcé la bombe, félicitations !\n\nVoilà les coordonnées de la cache :\n' + data.coordinates);
                } else {
                    $('.coordinates').html('La bombe n\'est pas ici :-(');
                }
            },
            failure: function() {
                $('.ajaxloader').hide();
                $('.coordinates').html('');
            },
            complete: function() {
                checking = false;
                chk_target.setMap(null);
            }
        });
    };

    var init = function() {
        resize_map();
        checking = false;

        var currentLatitude = 48.856578,
            currentLongitude = 2.351828,
            currentZoom = 12;

        if (window.sessionStorage) {
            currentLatitude = sessionStorage.getItem('latitude') || currentLatitude;
            currentLongitude = sessionStorage.getItem('longitude') || currentLongitude;
            currentZoom = sessionStorage.getItem('zoom') || currentZoom;
        }

        map = new google.maps.Map(document.getElementById('map-canvas'), {
            center: new google.maps.LatLng(Number(currentLatitude), Number(currentLongitude)),
            zoom: Number(currentZoom),
            mapTypeId: google.maps.MapTypeId.ROADMAP,
            streetViewControl: false,
            scaleControl: true
        });

        var savePosition = function() {
            sessionStorage.setItem('zoom', map.getZoom());
            sessionStorage.setItem('latitude', map.getCenter().lat().toFixed(5));
            sessionStorage.setItem('longitude', map.getCenter().lng().toFixed(5));
        };

        google.maps.event.addListener(map, 'zoom_changed', savePosition);
        google.maps.event.addListener(map, 'center_changed', savePosition);

        $.ajax({
            url: "get.php",
            success: function(response) {
                var targets = response.documentElement.getElementsByTagName("marker");
                for (var i = 0, il = targets.length; i < il; i++) {
                    var wpt = targets[i];
                    addTarget(wpt);
                }
            },
            failure: function(response) {
                var data = $.parseJSON(response);
                alert('Error:\n' + data);
            }
        });

        $.ajax({
            url: "init.php",
            success: function(response) {
                var data = $.parseJSON(response);
                nb_target = data.nb_target;
                max_target = data.max_target;
                if (nb_target < max_target) {
                    google.maps.event.addListener(map, 'click', function(event) {
                        new DistanceWidget(map, event.latLng, {
                            map: map
                        });
                    });

                }
                if (data.message) {
                    alert(data.message);
                }
            },
            error: function(response) {
                var data = $.parseJSON(response);
                alert('Error:\n' + data);
            }
        });
    };

    var resize_map = function() {
        $('#map-canvas').height($(window).height());
    };

    $(window).resize(resize_map);

    //Refresh de la page au jour d'apres
    var date = new Date();
    var current_day = date.getDay();
    window.setInterval(function() {
        var date = new Date();
        if (current_day != date.getDay()) {
            if (window.sessionStorage) {
                sessionStorage.clear();
            }
            window.location.reload();
        }
    }, 15000);

    if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|mobile/i.test(navigator.userAgent)) {
        alert('Désolé, le jeu n\'est pas compatible sur les plateformes mobiles ou tablettes.');
    } else {
        google.maps.visualRefresh = true;
        google.maps.event.addDomListener(window, 'load', init);
    }

}());
