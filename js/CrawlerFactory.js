realEstateApp.factory('CrawlerFactory', function(CrawlerService, $http) {
	return {
		getSuburbCallMetadata: function(suburbs) {
			var meta = {};

			// for ea suburb
			for (var s = 0; s < suburbs.length; s++) {
				// add "status" key
				var suburb = suburbs[s];
				meta[suburb.toLowerCase()] = {
					callStatus: CrawlerService.callStatuses.QUEUED,
					listings: []
				};
			}

			return meta;
		},
		getSuburbListings: function(suburb) {
			return $http.post(CrawlerService.realEstateScraper, {
				suburb: suburb
			}).then(function(res) {
				return res.data;
			});
		}
	};
});