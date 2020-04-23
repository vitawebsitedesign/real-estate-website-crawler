<?php

function getPostData() {
	if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($_POST)) {
		$requestContent = file_get_contents('php://input');
		return (array)json_decode($requestContent, true);
	}
	return $_POST;
}

function getListingsFromAllWebsites() {	
	require_once('websites/RealEstateWebsite.class.php');
	require_once('websites/DomainWebsite.class.php');
	require_once('websites/RentWebsite.class.php');
	$websites = [new RealEstateWebsite()/*, new DomainWebsite()*/, new RentWebsite()];
	$listings = [];

	foreach ($websites as $website) {
		$listings[] = $website->getListingsForSuburb(getPostData()['suburb']);
	}

	return $listings;
}

echo json_encode(getListingsFromAllWebsites());
die();