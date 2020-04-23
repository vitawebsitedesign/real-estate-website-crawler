realEstateApp.service('CrawlerService', function() {
	this.realEstateScraper = 'php/get-listings-from-all-websites.php';
	this.delayedGeocodes = [];

	this.colours = {
		stroke: 'white',
		opacity: 0.75,
		marker: {
			'#d1ffd1': 180,
			'#efc795': 210,
			'#ef9595': Number.MAX_VALUE
		}
	};
	this.suburbs = [
		'Mascot',
		'Eastlakes',
		'Zetland',
		'Erskineville',
		'St Peters',
		'Marrickville',
		'Earlwood',
		'Daceyville',
		'Botany',
		'Randwick',
		'Kingsford',
		'Kensington',
		'Newtown',
		'Ultimo',
		'Rosebery',
		'Hillsdale',
		'Chifley',
		'Phillip Bay',
		'Little Bay',
		'Malabar',
		'Maroubra',
		'Surry',
		'redfern',
		'wolli creek',
		'hurstville',
		'sutherland',
		'wynyard',
		'milsons',
		'leonards',
		'chatswood',
		'eastwood'
	];
	this.callStatuses = {
		QUEUED: 0,
		CALLING: 1,
		CALLED: 2
	};
});