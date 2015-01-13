(function () {

	"use strict";
	var app = angular.module('RbsChange');

	app.directive('rbsGeoAddressField', ['RbsChange.REST', function(REST) {
		return {
			restrict: 'A',
			require: 'ngModel',
			scope: {},
			templateUrl: 'Rbs/Geo/address-editor.twig',

			link: function(scope, elm, attrs, ngModel) {
				scope.address = {common:{}, fields:{}, lines:[]};
				scope.fieldsDef = [];

				scope.$watch('address.common.addressFieldsId', function(newValue) {
					if (newValue) {
						REST.resource('Rbs_Geo_AddressFields', newValue).then(scope.generateFieldsEditor);
					}
				});

				scope.$watch('address.fields', function(value, oldValue) {
					ngModel.$setViewValue(scope.address);
				}, true);

				scope.$watch('innerForm.$valid', function(value, oldValue) {
					if (value!== oldValue && (value === true || value === false)) {
						ngModel.$setViewValue(scope.address);
					}
				});

				scope.generateFieldsEditor = function(addressFields) {
					var fields = angular.copy(addressFields.fields), field = null;
					scope.fieldsDef = fields;
					if (angular.isArray(scope.fieldsDef)) {
						var address = ngModel.$viewValue;
						var fieldValues = address.fields;
						for (var i = 0; i < fields.length; i++) {
							field = fields[i];
							var currentLocalization = field.LCID[field.refLCID]; // TODO: Use current LCID of the interface.
							field.title = currentLocalization.title;
							field.matchErrorMessage = currentLocalization.matchErrorMessage;
							var v = null;
							if (fieldValues.hasOwnProperty(field.code)) {
								v = fieldValues[field.code];
							}
							if (v === null) {
								v = field.defaultValue;
								fieldValues[field.code] = v;
							}
						}
					}
				};

				ngModel.$parsers.push(function(value) {
					var valid = scope.innerForm ? scope.innerForm.$valid : false;
					ngModel.$setValidity('fields', valid);
					if (!valid || ngModel.$isEmpty(value)) {
						return null;
					}
					return value;
				});

				ngModel.$formatters.push(function(value) {
					if (ngModel.$isEmpty(value)) {
						value  = {common:{}, fields:{}, lines:[]};
					}
					return value;
				});

				ngModel.$render = function() {

					scope.address = ngModel.$viewValue;
				};

				ngModel.$isEmpty = function(value) {
					return !!(!angular.isObject(value) || !angular.isObject(value.fields)
					|| !angular.isObject(value.common)
					|| !value.common.addressFieldsId || !value.fields.countryCode);
				};
			}
		};
	}]);

	var mapId = 0;

	app.directive('rbsGeoCoordinatesField', ['RbsChange.REST', '$http', '$rootScope', '$timeout', function(REST, $http, $rootScope, $timeout) {
		return {
			restrict: 'A',
			require: 'ngModel',
			scope: {
				'readOnly' : '=',
				'address': '='
			},
			templateUrl: 'Rbs/Geo/coordinates-editor.twig',

			link: function(scope, elm, attrs, ngModel) {

				scope.locate = false;
				scope.defaultCenter = [48.856578,  2.351828];
				scope.defaultZoom = 11;
				scope.coordinates = {latitude:null, longitude:null};
				scope.mapId = 'map-' + (++mapId);
				scope.map = null;
				scope.layersToLoad = [{title: 'OpenStreetMap', code:'OSM'}];

				scope.initMap = function(center, defaultZoom) {
					scope.map = L.map(scope.mapId, {center: center, zoom: defaultZoom});
					scope.map.on('dblclick' , function(e) {
						if (!scope.locate && e.hasOwnProperty('latlng')) {
							var latLng = e['latlng'];
							scope.coordinates.latitude = latLng.lat;
							scope.coordinates.longitude = latLng.lng;
							ngModel.$setViewValue(scope.coordinates);
							scope.setMarkerCoordinates(scope.coordinates, false);
							$rootScope.$digest();
						}
					});

					var layers = {};
					var nbLayers = 0;

					for (var i = 0; i < scope.layersToLoad.length;  i++){
						var l = null;
						if (scope.layersToLoad[i].code == 'OSM') {
							l = new L.TileLayer('http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png');
							layers[scope.layersToLoad[i]['title']] = l;
							scope.map.addLayer(l);
							nbLayers++;
						}
					}
					if (nbLayers > 1) {
						scope.map.addControl(new L.Control.Layers( layers, {}));
					}
				};

				scope.setMarkerCoordinates = function(coordinates, centerView) {
					var c = [coordinates.latitude, coordinates.longitude];
					var latLng = {lat: coordinates.latitude, lng: coordinates.longitude};

					if (!scope.map) {
						scope.initMap(c, scope.defaultZoom);
					} else if (centerView) {
						scope.map.setView(latLng);
					}

					if (!scope.marker) {
						scope.marker = L.marker(latLng, {draggable:true}).addTo(scope.map);
						scope.marker.on('dragend' , function(e) {
							var latLng = scope.marker.getLatLng();
							scope.coordinates.latitude = latLng.lat;
							scope.coordinates.longitude = latLng.lng;
							ngModel.$setViewValue(scope.coordinates);
							$rootScope.$digest();
						});
					} else {
						scope.marker.setLatLng(latLng);
					}
				};

				scope.locateAddress = function(address) {
					scope.locate = true;
					if (!address || !address.common || !address.fields) {
						navigator.geolocation.getCurrentPosition(
							function (position) {
								scope.locate = false;
								scope.coordinates.latitude = 48.5838646; //parseFloat(position.coords.latitude);
								scope.coordinates.longitude = 7.753678799999999; //parseFloat(position.coords.longitude);
								ngModel.$setViewValue(scope.coordinates);
								scope.setMarkerCoordinates(scope.coordinates, true);
								$rootScope.$digest();
							},
							function (error) {scope.locate = false; alert("Localisation failed : [" + error.code + "] " + error.message);},
							{timeout: 5000, maximumAge: 0}
						);
					} else {
						$http.post(REST.getBaseUrl('Rbs/Geo/CoordinatesByAddress'), {
							address: address
						}).success(function(data) {
							scope.locate = false;
							scope.coordinates.latitude = data.latitude;
							scope.coordinates.longitude = data.longitude;
							ngModel.$setViewValue(scope.coordinates);
							scope.setMarkerCoordinates(scope.coordinates, true);
						}).error(function(dataError) {
							scope.locate = false;
							console.log(dataError);
						});
					}
				};

				//TAB CHanged on Bo editor
				scope.$on('Change:EditorSectionChanged', function(event, section) {
					if (scope.map) {
						scope.map.invalidateSize(false);
					}
				});

				//Coordinates changed by user
				scope.coordinateChange = function() {
					ngModel.$setViewValue(scope.coordinates);
					if (!ngModel.$isEmpty(scope.coordinates)) {
						scope.setMarkerCoordinates(scope.coordinates, true);
					}
				};

				scope.$watch('innerForm.$valid', function(value, oldValue) {
					if (value!== oldValue && (value === true || value === false)) {
						ngModel.$setViewValue(scope.coordinates);
					}
				});

				ngModel.$parsers.push(function(value) {
					var valid = scope.innerForm ? scope.innerForm.$valid : false;
					ngModel.$setValidity('fields', valid);
					if (!valid || ngModel.$isEmpty(value)) {
						return null;
					}
					return angular.copy(value);
				});

				ngModel.$formatters.push(function(value) {
					if (ngModel.$isEmpty(value)) {
						return {latitude:null, longitude:null};
					}
					return angular.copy(value);
				});

				ngModel.$render = function() {
					scope.coordinates = ngModel.$viewValue;
					if (!scope.map) {
						$timeout(function() {
							if (!scope.map) {
								if (!ngModel.$isEmpty(scope.coordinates)) {
									scope.setMarkerCoordinates(scope.coordinates, true);
								} else {
									scope.initMap(scope.defaultCenter, scope.defaultZoom);
								}
							}
						});
					} else if (!ngModel.$isEmpty(scope.coordinates)) {
						scope.setMarkerCoordinates(scope.coordinates, true);
					}
				};

				ngModel.$isEmpty = function(value) {
					return !!(!angular.isObject(value) || !angular.isNumber(value.latitude)
					|| !angular.isNumber(value.longitude));
				};
			}
		};
	}]);
})();