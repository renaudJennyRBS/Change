/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
(function ($, $script) {

	var gMapsAlreadyLoaded = false;
	var mapIndex = 0;

	"use strict";

	/**
	 * @name rbsGpsCoordinatesSelector
	 */
	angular.module('RbsChange').directive('rbsGpsGoogleMaps', ['RbsChange.Dialog', '$rootScope', 'RbsChange.i18n', 'RbsChange.Settings', '$q', function (Dialog, $rootScope, i18n, Settings, $q) {

		return {
			restrict    : 'E',
			template    : '<div data-id="map-canvas" style="height: 300px;"></div>' +
				'<input data-id="pac-input" class="controls map-input" type="text" placeholder="' +
				i18n.trans('m.rbs.admin.admin.gps_address_search', ['ucf']) + '">',
			scope       : {
				mapMarkers: '='
			},

			compile : function (tElement) {

				var mapId = ++mapIndex;
				tElement.find('[data-id=map-canvas]').attr('id', 'map-canvas-' + mapId);
				tElement.find('[data-id=pac-input]').attr('id', 'pac-input-' + mapId);

				return function (scope, elm, attrs) {
					var map = null;

					window['gMapsInitialize'] = function (){
						gMapsAlreadyLoaded = true;
						scope.onGMapsInitialized();
					};

					scope.onGMapsInitialized = function () {
						var mapOptions = {
							zoom: 8,
							center: new google.maps.LatLng(0, 0)
						};

						map = new google.maps.Map(document.getElementById('map-canvas-' + mapId),
							mapOptions);

						if (scope.mapMarkers.length > 0) {
							//first marker will be the center
							var firstMarker = scope.mapMarkers[0];
							setMapCenter(firstMarker);
							if (attrs.mode == 'coordinates') {
								startCoordinatesMode();
							}
							else {
								angular.forEach(scope.mapMarkers, function (marker) {
									var gMapMarker = new google.maps.Marker({
										position: new google.maps.LatLng(marker.latitude, marker.longitude),
										map: map,
										title: marker.name
									});
								});
							}
						}
						else {
							console.error('mapMarkers is empty');
						}
					};

					if (!gMapsAlreadyLoaded) {
						$script('https://maps.googleapis.com/maps/api/js?key=AIzaSyDhe1HnnjjXEoVPlQvZbNseWK1SRXA_IXI&sensor=false&callback=gMapsInitialize&libraries=places');
					}
					//if the script is already called once, it won't call gMapsInitialize again.
					else {
						scope.onGMapsInitialized();
					}

					function startCoordinatesMode() {

						//let user drop a marker.
						var gMapMarker = new google.maps.Marker({
							map: map,
							draggable: true,
							title: i18n.trans('m.rbs.admin.admin.gps_the_position')
						});

						google.maps.event.addListener(map, 'click', function (mouseEvent) {
							var pos = mouseEvent.latLng;

							gMapMarker.setPosition(pos);

							var marker = {
								name: 'position',
								latitude: gMapMarker.getPosition().lat(),
								longitude: gMapMarker.getPosition().lng(),
								address: ''
							};

							scope.$apply(function (){
								scope.mapMarkers = [marker];
							});
						});

						//If there is available data in mapMarkers, take the first one.
						if (scope.mapMarkers.length > 0) {
							var mapMarker = scope.mapMarkers[0];
							gMapMarker.setPosition(new google.maps.LatLng(mapMarker.latitude, mapMarker.longitude));
						}

						google.maps.event.addListener(gMapMarker, 'drag', function () {
							var marker = {
								name: 'position',
								latitude: gMapMarker.getPosition().lat(),
								longitude: gMapMarker.getPosition().lng(),
								address: ''
							};

							scope.$apply(function (){
								scope.mapMarkers = [marker];
							});
						});

						addSearchBox(gMapMarker);
					}

					/**
					 * @param [marker]
					 */
					function setMapCenter(marker) {
						//get position from marker
						if (angular.isObject(marker) && marker.hasOwnProperty('latitude') && marker.hasOwnProperty('longitude')) {
							map.setCenter(new google.maps.LatLng(marker.latitude, marker.longitude));
						}
						else if (navigator.geolocation) {
							navigator.geolocation.getCurrentPosition(function(position) {
								map.setCenter(new google.maps.LatLng(position.coords.latitude, position.coords.longitude));
							});
						}
						else {
							map.setCenter(new google.maps.LatLng(48.8584, 2.2944));
						}
					}

					/**
					 * found on https://developers.google.com/maps/documentation/javascript/examples/places-searchbox
					 */
					function addSearchBox(gMapMarker) {
						var markers = [];

						// Create the search box and link it to the UI element.
						var input = (document.getElementById('pac-input-' + mapId));
						map.controls[google.maps.ControlPosition.TOP_LEFT].push(input);

						var searchBox = new google.maps.places.SearchBox((input));

						// Listen for the event fired when the user selects an item from the
						// pick list. Retrieve the matching places for that item.
						google.maps.event.addListener(searchBox, 'places_changed', function() {
							var places = searchBox.getPlaces();

							for (var i = 0, marker; marker = markers[i]; i++) {
								marker.setMap(null);
							}

							// For each place, get the icon, place name, and location.
							markers = [];
							var bounds = new google.maps.LatLngBounds();
							for (var i = 0, place; place = places[i]; i++) {
								var image = {
									url: place.icon,
									size: new google.maps.Size(71, 71),
									origin: new google.maps.Point(0, 0),
									anchor: new google.maps.Point(17, 34),
									scaledSize: new google.maps.Size(25, 25)
								};

								// Create a marker for each place.
								var marker = new google.maps.Marker({
									map: map,
									icon: image,
									title: place.name,
									position: place.geometry.location
								});

								//Add listener on click to choose the good one
								google.maps.event.addListener(marker, 'click', function (mouseEvent) {
									var pos = mouseEvent.latLng;

									gMapMarker.setPosition(pos);

									var marker = {
										name: 'position',
										latitude: gMapMarker.getPosition().lat(),
										longitude: gMapMarker.getPosition().lng(),
										address: ''
									};

									scope.$apply(function (){
										scope.mapMarkers = [marker];
									});
								});

								markers.push(marker);

								bounds.extend(place.geometry.location);
							}

							map.fitBounds(bounds);
						});

						// Bias the SearchBox results towards places that are within the bounds of the
						// current map's viewport.
						google.maps.event.addListener(map, 'bounds_changed', function() {
							var bounds = map.getBounds();
							searchBox.setBounds(bounds);
						});
					}
				};
			}
		};
	}]);

})(window.jQuery, $script);