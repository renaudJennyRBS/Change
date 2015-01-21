(function () {
	"use strict";
	var app = angular.module('RbsChangeApp');

	var useGoogleMap = false;

	window.__change.__resources.push(function() {
		var cfg = window.__change.Rbs_Geo_Config;
		if (cfg && cfg.Google && cfg.Google.APIKey) {
			window.__change.RBS_Geo.initGoogleMap(cfg.Google.APIKey);
			useGoogleMap = true;
		} else {
			window.__change.RBS_Geo.initLeafletMap();
		}
	});

	app.directive('rbsStoreshippingRelaymodeEditor', ['RbsChange.AjaxAPI', '$compile', function(AjaxAPI, $compile) {
		var baseTemplateURL = null;

		function getBaseTemplateURL() {
			if (baseTemplateURL === null) {
				var navigationContext = AjaxAPI.globalVar('navigationContext');
				var themeName = (angular.isObject(navigationContext) ? navigationContext.themeName : null) || 'Rbs_Base';
				baseTemplateURL = 'Theme/' + themeName.split('_').join('/');
			}
			return baseTemplateURL;
		}

		function templateEditorURL() {
			return getBaseTemplateURL() + '/Rbs_Storeshipping/shipping.twig';
		}

		return {
			restrict: 'A',
			templateUrl: templateEditorURL,
			require: '^rbsCommerceProcess',
			scope: {
				shippingMode: '=',
				shippingModeInfo: '=',
				userAddresses: '='
			},
			link: function (scope, element, attributes, processController) {
				scope.loading = false;
				scope.options = {};
				scope.searchAddress = null;
				scope.countries = [];
				scope.currentAddress = {};
				scope.currentPosition = {latitude:null, longitude:null};

				scope.markers = [];
				scope.map = null;

				scope.data = [];

				scope.useGoogleMap = useGoogleMap;

				function relayValid(returnData) {
					if (returnData) {
						var shippingMode = scope.shippingMode;
						var relay = angular.copy(shippingMode.options.relay);
						delete relay.address;
						relay.searchAddress = scope.searchAddress;
						relay.searchAtPosition = scope.currentPosition;
						return {
							id: scope.shippingModeInfo.common.id, title: scope.shippingModeInfo.common.title,
							lineKeys: shippingMode.lineKeys,
							address: scope.relayAddress,
							options: { category: 'relay', relay: relay }
						};
					}
					return (scope.shippingModeInfo.common.id == scope.shippingMode.id) && scope.relayAddress != null;
				}

				scope.$watch('shippingMode.shippingZone', function(zoneCode) {
					AjaxAPI.getData('Rbs/Geo/AddressFieldsCountries/', {zoneCode: zoneCode})
						.success(function(data) {
							scope.countries = data.items;
							if (scope.countries.length == 1) {
								scope.currentAddress.country = scope.countries[0].common.code;
							}
						})
						.error(function(data, status, headers) {
							console.log('addressFieldsCountries error', data, status, headers);
							scope.countries = [];
							scope.currentAddress.country = null;
						});
				});

				scope.$watch('shippingMode.id', function(id) {
					if (id == scope.shippingModeInfo.common.id) {
						scope.shippingMode.valid = relayValid;
						if (scope.map) {
							if (useGoogleMap) {
								google.maps.event.trigger(scope.map, 'resize');
							} else {
								scope.map.invalidateSize(false);
							}
						}
					}
				});

				scope.$watch('currentAddress.country', function(){
					scope.searchAddress = null;
					scope.data = [];
					scope.removeMarkers();
				});

				scope.setTaxZone = function(taxZone) {
					if (processController.getObjectData().common.zone != taxZone) {
						var actions = {
							setZone: {zone : taxZone}
						};
						processController.updateObjectData(actions);
					}
				};

				scope.loadMap = function() {
					var mapId = 'ShippingMap-' + scope.shippingModeInfo.common.id;
					if (useGoogleMap) {
						var mapOptions = {
							center: new google.maps.LatLng(scope.defaultLatitude, scope.defaultLongitude),
							zoom: scope.defaultZoom
						};
						scope.map = new google.maps.Map(document.getElementById(mapId), mapOptions);
					} else {
						scope.map = L.map(mapId, {
							center: [scope.defaultLatitude, scope.defaultLongitude], zoom: scope.defaultZoom
						});
						var l = new L.TileLayer('http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png');
						scope.map.addLayer(l);
					}
				};

				scope.drawMarkerToMap = function(relay, index, bounds) {
					var markerOptions = {};
					if (relay.options.iconUrl) {
						markerOptions.icon = L.icon({
							iconUrl: relay.options.iconUrl,
							iconSize: relay.options.iconSize ? relay.options.iconSize : [30, 36]
						});
					}
					var marker = L.marker([relay.latitude, relay.longitude], markerOptions).addTo(scope.map);
					marker.bindPopup('<div class="marker-popup-content"></div>', {minWidth:280, offset:L.point(0, -10)});

					marker.on('popupopen', function(e){
						scope.map.setView([relay.latitude, relay.longitude]);
						scope.popupRelay = relay;
						var popupContent = element.find('.marker-popup-content');
						var html = '<div data-rbs-storeshipping-popin-detail=""></div>';
						$compile(html)(scope, function (clone) {
							popupContent.append(clone);
						});

						var mapBounds = scope.map.getBounds();
						scope.map.setView([relay.latitude + (4*(Math.abs((relay.latitude - mapBounds._southWest.lat))/5)), relay.longitude]);
					});

					marker.on('popupclose', function(e){
						scope.popupRelay = null;
						var popupContent = element.find('.marker-popup-content');
						var collection = popupContent.children();
						collection.remove();
					});
					marker.on('click', function(e){
						scope.setDeliveryInformation(index);
						var scrollTo = element.find('#point'+index);
						if (scrollTo && scope.listDiv)
						{
							scope.listDiv.animate({
								scrollTop: scrollTo.offset().top - scope.listDiv.offset().top + scope.listDiv.scrollTop()
							}, 1000);
						}
					});

					scope.markers.push(marker);
					bounds.push(L.latLng(relay.latitude, relay.longitude));
					return marker;
				};

				scope.drawMarkerToGoogleMap = function(relay, index, bounds) {
					relay.idx = index;
					var latLng = new google.maps.LatLng(relay.latitude, relay.longitude);
					var markerOptions = {position: latLng, map: scope.map};
					if (relay.options.iconUrl) {

						var width = relay.options.iconSize[0],
							height = relay.options.iconSize[1];

						markerOptions.icon = {
							url: relay.options.iconUrl,
							size: new google.maps.Size(width, height),
							origin: new google.maps.Point(0,0),
							anchor: new google.maps.Point(width / 2, height)
						};
					}
					var marker = new google.maps.Marker(markerOptions);

					marker.openPopup = function() {
						if (scope.infoWindow) {
							scope.infoWindow.close();
							scope.popupRelay = null;
						}

						var html = '<div style="min-height: 350px;min-width: 280px"><div data-rbs-storeshipping-popin-detail="" ></div></div>';
						scope.infoWindow = new google.maps.InfoWindow({
							content: $compile(html)(scope)[0],
							maxWidth: 280
						});
						google.maps.event.addListener(scope.infoWindow, 'domready', function() {
							scope.popupRelay = relay;
							scope.$digest();
						});
						scope.infoWindow.open(scope.map, marker);
					};

					google.maps.event.addListener(marker, 'click', function() {
						scope.selectRelay(index);
						scope.$digest();
					});

					scope.markers.push(marker);
					bounds.extend(latLng);
					return marker;
				};

				scope.searchWithAddress = function() {
					if (scope.loading) {
						return;
					}

					var country = scope.currentAddress.country;
					angular.forEach(scope.countries, function(o) {
						if (o.common.code == country) {
							country = o.common.title;
						}
					});

					scope.loading = true;
					var address = {lines:['', scope.searchAddress, country]};
					AjaxAPI.getData('Rbs/Geo/CoordinatesByAddress', {address: address}).success(function(data) {
						scope.loading = false;
						if (data.dataSets && data.dataSets.latitude) {
							scope.map.setView([data.dataSets.latitude, data.dataSets.longitude]);
							scope.currentPosition = {latitude: data.dataSets.latitude, longitude: data.dataSets.longitude};
							scope.launchSearch();
						}
					}).error(function(data) {
						scope.loading = false;
					});
				};

				scope.searchGoogleAddress = function() {
					var place = scope.autocomplete.getPlace();
					if (!place.geometry) {
						return;
					}
					var location = place.geometry.location;
					scope.currentPosition = {latitude: location.lat(), longitude: location.lng()};
					scope.searchAddress = place.formatted_address;
					scope.launchSearch();
				};

				scope.locateMe = function locateMe() {
					navigator.geolocation.getCurrentPosition(
						function (position) {
							if (useGoogleMap) {
								scope.map.setCenter(new google.maps.LatLng(position.coords.latitude, position.coords.longitude));
							} else {
								scope.map.setView([position.coords.latitude, position.coords.longitude]);
							}
							scope.currentPosition  = {latitude: position.coords.latitude, longitude: position.coords.longitude};
							scope.searchAddress = null;
							scope.launchSearch();
						},
						function (error) {
							alert("Localisation failed : [" + error.code + "] " + error.message);
						}, {timeout: 5000, maximumAge: 0}
					);
				};

				scope.launchSearch = function() {
					scope.setDeliveryInformation(null);
					scope.loading = true;

					AjaxAPI.getData('Rbs/Geo/Points/',
						{address: scope.currentAddress, position: scope.currentPosition,
							options: scope.options,
							matchingZone: scope.shippingMode.shippingZone || scope.shippingMode.taxesZones})
						.success(function(data) {
							scope.data = data.items;
							scope.updateMarkers();
							scope.loading = false;
						})
						.error(function(data, status, headers) {
							console.log('launchSearch error', data, status);
							scope.data = [];
							scope.loading = false;
						});
				};

				scope.removeMarkers = function() {
					var i, markersLength = scope.markers.length;
					if (useGoogleMap) {
						if (scope.markers) {
							for (i = 0; i < markersLength; i++) {
								scope.markers[i].setMap(null);
							}
						}
					} else {
						for(i=0; i < markersLength; i++) {
							scope.map.removeLayer(scope.markers[i]);
						}
					}
					scope.markers = [];
				};

				scope.updateMarkers = function() {
					var i, dataLength, bounds;

					scope.removeMarkers();
					dataLength = scope.data.length;
					if (!dataLength) {
						return;
					}

					var prCode = scope.preSelectedRelay ? scope.preSelectedRelay.code : null, selectedIndex = false;

					if (useGoogleMap) {
						bounds = new google.maps.LatLngBounds();
						for(i = 0; i < dataLength; i++) {
							scope.drawMarkerToGoogleMap(scope.data[i], i, bounds);
							if (scope.data[i].code == prCode) {
								selectedIndex = i;
							}
						}
						scope.map.fitBounds(bounds);

					} else {
						bounds = [];
						for(i =0; i< dataLength; i++) {
							scope.drawMarkerToMap(scope.data[i], i, bounds);
							if (scope.data[i].code == prCode) {
								selectedIndex = i;
							}
						}
						scope.map.fitBounds(bounds);
					}

					if (selectedIndex !== false) {
						scope.selectRelay(selectedIndex);
					}
				};

				scope.selectRelay = function(index){
					scope.setDeliveryInformation(index);
					scope.markers[index].openPopup();
				};

				scope.setDeliveryInformation = function setDeliveryInformation(index) {
					scope.shippingMode.valid = relayValid;
					if (index == null) {
						scope.selectedIndex = null;
						scope.relayAddress = null;
						scope.shippingMode.options.relay = null;
					} else {
						scope.selectedIndex = index;
						scope.relayAddress = scope.data[index].address;
						var taxZone = scope.data[index].options.matchingZone;
						if (taxZone && taxZone !== true && scope.shippingMode.taxesZones) {
							scope.setTaxZone(taxZone);
						}
						scope.shippingMode.options.relay = scope.data[index];
					}
				};

				scope.$watch('shippingModeInfo', function(shippingModeInfo) {
					if (shippingModeInfo) {
						// Inititialize
						scope.defaultLatitude = shippingModeInfo.editor.defaultLatitude;
						scope.defaultLongitude = shippingModeInfo.editor.defaultLongitude;
						scope.defaultZoom = shippingModeInfo.editor.defaultZoom;
						scope.options = {modeId: shippingModeInfo.common.id};
						scope.preSelectedRelay = null;

						var launchSearch = false;
						if (scope.shippingMode.id == shippingModeInfo.common.id)  {
							scope.shippingMode.valid = relayValid;
							if (scope.shippingMode.options && scope.shippingMode.options.relay) {
								var relay = scope.shippingMode.options.relay;
								scope.searchAddress = relay.searchAddress;
								if (relay.searchAtPosition) {
									scope.preSelectedRelay = relay;
									scope.currentPosition = relay.searchAtPosition;
									if (relay.searchAtPosition.latitude) {
										scope.defaultLatitude = relay.searchAtPosition.latitude;
										scope.defaultLongitude = relay.searchAtPosition.longitude;
										launchSearch = true;
									}
								}
							}
						}

						if (useGoogleMap) {
							scope.autocomplete = new google.maps.places.Autocomplete(
								(document.getElementById('google_map_search_address_auto_complete')), { types: ['geocode'] });
							// When the user selects an address from the dropdown,
							// populate the address fields in the form.
							google.maps.event.addListener(scope.autocomplete, 'place_changed', function() {
								scope.searchGoogleAddress();
							});
						}

						scope.loadMap();

						if (launchSearch) {
							scope.launchSearch();
						}
					}
				});
			}
		}
	}]);

	app.directive('rbsStoreshippingRelaymodeSummary', ['RbsChange.AjaxAPI', function(AjaxAPI) {
		function templateSummaryURL() {
			var navigationContext = AjaxAPI.globalVar('navigationContext');
			var themeName = (angular.isObject(navigationContext) ? navigationContext.themeName : null) || 'Rbs_Base';
			return 'Theme/' + themeName.split('_').join('/') + '/Rbs_Storeshipping/shipping-readonly.twig';
		}

		return {
			restrict: 'A',
			templateUrl: templateSummaryURL,
			scope: {
				'shippingMode': '='
			},

			link: function (scope, element, attributes)
			{
				scope.relay = scope.shippingMode.options.relay;
			}
		}
	}]);

	function rbsStoreshippingPopinDetail(AjaxAPI, $sce) {

		function templatePopinDetailURL() {
			var navigationContext = AjaxAPI.globalVar('navigationContext');
			var themeName = (angular.isObject(navigationContext) ? navigationContext.themeName : null) || 'Rbs_Base';
			return 'Theme/' + themeName.split('_').join('/') + '/Rbs_Storeshipping/popin-detail.twig';
		}

		return {
			restrict: 'A',
			templateUrl: templatePopinDetailURL,
			link: function (scope, element, attributes) {
				scope.trustHtml = function(html) {
					return $sce.trustAsHtml(html);
				};

				scope.$watch('popupRelay', function(popupRelay) {
					if (popupRelay && popupRelay.options.distanceInfo) {
						var distanceInfo = popupRelay.options.distanceInfo;
						popupRelay.options.distanceUnit = distanceInfo[1];
						if (popupRelay.options.distanceUnit = 'km') {
							popupRelay.options.distance = Math.round(distanceInfo[0] * 10) / 10;
						} else {
							popupRelay.options.distance = Math.round(distanceInfo[0]);
						}
					}
				})
			}
		}
	}
	rbsStoreshippingPopinDetail.$inject = ['RbsChange.AjaxAPI', '$sce'];
	app.directive('rbsStoreshippingPopinDetail', rbsStoreshippingPopinDetail);
})();