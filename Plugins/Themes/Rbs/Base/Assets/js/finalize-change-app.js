(function (__change) {
	"use strict";
	for (var i =0; i < __change.__resources.length; i++) {
		__change.__resources[i]();
	}
})(window.__change);