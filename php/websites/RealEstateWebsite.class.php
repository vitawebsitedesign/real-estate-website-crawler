<?php

require_once('PropertyWebsiteInterface.php');

class RealEstateWebsite implements PropertyWebsiteInterface {
	private $urlTemplate = 'http://www.realestate.com.au/rent/in-$suburb/list-$pageNum?activeSort=price-asc';
	private $urlTemplateSuburb = '$suburb';
	private $urlTemplatePageNum = '$pageNum';
	private $listingsPatt = '/<article class="resultBody (first )*standard platinum tier1 rui-clearfix"[\w\W]+?>[\w\W]+?<\/article>/';
	private $listingClass = 'listing-adapters/RealEstateListingAdapter.class.php';
	private $ch = null;

	function __construct() {
		$this->ch = curl_init();
		curl_setopt($this->ch, CURLOPT_HEADER, false);
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
	}

	function __deconstruct() {
		curl_close($this->ch);
		$this->ch = null;
	}

	function getListingsForSuburb($suburb) {
		$res = [
			'suburb' => $suburb,
			'listings' => []
		];

		for ($p = 1; $p < 3; $p++) {
			// get suburb page html
			$html = $this->getSearchPageHtml($suburb, $p);
			// get js data embedded in html
			$res['listings'] = array_merge($res['listings'], $this->getListingsFromHtml($p, $html));
		}		

		error_log('Finished crawling ' . $suburb);

		return $res;
	}
	
	function getSearchPageHtml($suburb, $pgNum) {
		curl_setopt($this->ch, CURLOPT_URL, $this->getSearchPageUrl($suburb, $pgNum));
		return curl_exec($this->ch);
	}
	
	function getSearchPageUrl($suburb, $pgNum) {
		$validParams = (strlen($suburb) && $pgNum > 0);
		if (!$validParams) {
			throw new Exception('Failed to get search page URL, because ' . $suburb . ' or ' . $pgNum . ' are not valid suburbs/page numbers');
		}
		
		$urlWithSuburb = str_replace($this->urlTemplateSuburb, $suburb, $this->urlTemplate);
		return str_replace($this->urlTemplatePageNum, $pgNum, $urlWithSuburb);
	}
	
	function getListingsFromHtml($pageNum, $html) {
		$listingMatches = $this->getListingMatchesFromHtml($html);
		$listings = [];
		
		require_once($this->listingClass);
		for ($i = 0; $i < count($listingMatches); $i++) {
			$html = $listingMatches[$i];
			$listing = new RealEstateListingAdapter($html);
			$pricedListing = (is_numeric($listing->getPrice()) && $listing->getPrice() > 0);
			if ($pricedListing) {
				$listings[] = $listing;
			}
		}
		
		return $listings;
	}
	
	function getListingMatchesFromHtml($html) {
		preg_match_all($this->listingsPatt, $html, $matchedJsonData);
		if (!$matchedJsonData || !isset($matchedJsonData[0])) {
			throw new Exception('Failed to get json data from html');
		}

		return $matchedJsonData[0];
	}
}