(function (__change) {
	"use strict";

	__change.RBS_Geo = {
		initLeafletMap: function () {
			if (!this.initLeafletMapLoaded) {
				this.initLeafletMapLoaded = true;
				document.write('<scr' +
				'ipt type="text/javascript" src="http://cdn.leafletjs.com/leaflet-0.7.3/leaflet.js"></scr' + 'ipt>');

				//document.write('<scr' +
				//'ipt type="text/javascript" src="http://maps.google.com/maps/api/js?v=3.2&sensor=false"></scr' + 'ipt>');
				//document.write('<scr' +
				//'ipt type="text/javascript" src="http://matchingnotes.com/javascripts/leaflet-google.js"></scr' + 'ipt>');

				var head = document.getElementsByTagName('head')[0];
				var link = document.createElement('link');
				link.rel = 'stylesheet';
				link.type = 'text/css';
				link.href = 'http://cdn.leafletjs.com/leaflet-0.7.3/leaflet.css';
				link.media = 'all';
				head.appendChild(link);
			}
		},

		initGoogleMap: function (googleAPIKey) {
			if (!this.initGoogleMapLoaded) {
				this.initGoogleMapLoaded = googleAPIKey;
				document.write('<scr' + 'ipt type="text/javascript" src="//maps.googleapis.com/maps/api/js?key=' + googleAPIKey + '&v=3.exp&libraries=places"></scr' + 'ipt>');
			}
		}
	}
})(window.__change);
