//TODO Refactor
var onDOMLoadedCallbacks = [];

function registerDOMLoadedCallback(callback)
{
	onDOMLoadedCallbacks.push(callback);
}

function onDOMLoaded(selectorPrefix)
{
	if (selectorPrefix)
	{
		selectorPrefix += ' ';
	}
	for (var i in onDOMLoadedCallbacks)
	{
		onDOMLoadedCallbacks[i](selectorPrefix);
	}
}

// Register core scripts.
registerDOMLoadedCallback(function (selectorPrefix)
{
	// Section hidden if JavaScript is enabled or disabled.
	jQuery(selectorPrefix + '.nojs').hide();
	jQuery(selectorPrefix + '.js').removeClass('js');
});

// On document ready, apply onDOMLoaded on the full document.
jQuery(document).ready(function ()
{
	onDOMLoaded('');
});