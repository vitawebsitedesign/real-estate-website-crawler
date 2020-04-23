realEstateApp.controller('CrawlerController', function(CrawlerService, CrawlerFactory, $scope, $http, $window, $filter, $q, $timeout) {
	$scope.getCallStatusLabel = function(callStatus) {
		return Object.keys(CrawlerService.callStatuses).find(key => CrawlerService.callStatuses[key] === callStatus);
	};

	$scope.isCallStatus = function(callStatus, targetCallStatus) {
		return callStatus === CrawlerService.callStatuses[targetCallStatus.toUpperCase()];
	};

	$scope.getAllListings = function() {
		return Object.keys($scope.callMetadata).reduce(function(prev, suburb) {
		  return prev.concat($scope.callMetadata[suburb].listings);
		}, []);
	};

	function init() {
		$scope.delayedGeocodes = CrawlerService.delayedGeocodes;
		initMap();
		showListings();
	}

	function showListings() {
		$scope.geocoder = new google.maps.Geocoder();
		$scope.callMetadata = CrawlerFactory.getSuburbCallMetadata(CrawlerService.suburbs);

		for (var suburb in $scope.callMetadata) {
			updateListingsCallStatus(suburb, CrawlerService.callStatuses.CALLING);
			CrawlerFactory.getSuburbListings(suburb).then(showSuburbListings);
		}
	}

	function updateListingsCallStatus(suburb, callStatus) {
		if (suburb) {
			$scope.callMetadata[suburb.toLowerCase()].callStatus = callStatus;
		} else {
			console.warn('updateListingsCallStatus(...) was called with the parameters: ' + suburb + ', callStatus: ' + callStatus);
		}
	}

	function showSuburbListings(listingsInWebsites) {
		for (var w = 0; w < listingsInWebsites.length; w++) {
			var website = listingsInWebsites[w];
			var suburb = website.suburb;
			updateListingsCallStatus(suburb, CrawlerService.callStatuses.CALLED);

			var listings = website.listings;
			$scope.callMetadata[suburb.toLowerCase()].listings = listings;

			for (var i = 0; i < listings.length; i++) {
				geocode(listings[i], showMarker);
			}
		}
	}

	function geocode(listing, callback) {
		$scope.geocoder.geocode({
			address: listing.address
		}, function(res, status) {
			switch (status) {
				case google.maps.GeocoderStatus.OK:
					clearDelayedGeocode(listing.id);
					if (res && res[0]) {
						callback(res[0].geometry.location, listing);
					}
					break;
				case google.maps.GeocoderStatus.OVER_QUERY_LIMIT:
					markDelayedGeocode(listing.id);
					$scope.showMarkersCounter = true;
					$timeout(function() {
						geocode(listing, callback);
					}, 2000);
					break;
				case google.maps.GeocoderStatus.ZERO_RESULTS:
					clearDelayedGeocode(listing.id);
					break;
				default:
					console.error(status);
					console.error(res);
			}
		});
	}

	function showMarker(coords, listing) {
		var marker = new google.maps.Marker({
			position: coords,
			map: $window.map,
			metadata: listing,
			icon: {
				strokeWeight: 2,
				strokeColor: CrawlerService.colours.stroke,
				strokeOpacity: CrawlerService.colours.opacity,
				fillColor: getMarkerColor(listing.price),
				fillOpacity: CrawlerService.colours.opacity,
				path: google.maps.SymbolPath.CIRCLE,
				scale: 25
			},
			label: $filter('currency')(listing.price)
		});
		
		marker.addListener('mouseup', function() {
			window.open(this.metadata.url, '_blank');
		});
	}

	function markDelayedGeocode(id) {
		var alreadyMarked = (indexOfDelayedGeocode(id) >= 0);
		if (!alreadyMarked) {
			CrawlerService.delayedGeocodes.push(id);
		}
	}

	function clearDelayedGeocode(id) {
		var index = indexOfDelayedGeocode(id);
		if (index >= 0) {
			$scope.$apply(function() {
				CrawlerService.delayedGeocodes.splice(index, 1);
			});
		}
	}

	function indexOfDelayedGeocode(id) {
		return CrawlerService.delayedGeocodes.indexOf(id);
	}

	function getMarkerColor(listingPrice) {
		var colours = CrawlerService.colours.marker;
		for (var colour in colours) {
			if (!colours.hasOwnProperty(colour)) {
				continue;
			}
			
			var priceThreshold = colours[colour];
			if (listingPrice <= priceThreshold) {
				return colour;
			}
		}
		
		return '';
	}

	function initMap() {
		var kensington = {lat: -33.909, lng: 151.223};
		$window.map = new google.maps.Map(document.getElementById('map'), {
			zoom: 15,
			center: kensington
		});
	}

	init();
});