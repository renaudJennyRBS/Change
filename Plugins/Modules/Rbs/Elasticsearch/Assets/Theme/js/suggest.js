(function ($) {
	$('input.elastic-search').typeahead([
		{
			name: 'suggest',
			remote: {
				url: 'Action/Rbs/Elasticsearch/Suggest?searchText=%QUERY',
				filter: function(parsedResponse) {
					return parsedResponse.items;
				}
			}
		}
	]);
})(window.jQuery);