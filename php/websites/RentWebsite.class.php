<?php

require_once('PropertyWebsiteInterface.php');

class RentWebsite implements PropertyWebsiteInterface {
	private $urlTemplate = 'http://rent.com.au/properties/$suburb-$state-$postcode/p$pageNum?order_by=rent_low&surrounding_suburbs=1';
	private $urlTemplateSuburb = '$suburb';
	private $urlTemplateState = '$state';
	private $urlTemplatePostcode = '$postcode';
	private $urlTemplatePageNum = '$pageNum';
	private $urlState = 'nsw';
	private $urlPostcodeLookup = 'v0.postcodeapi.com.au/suburbs.json?name=$suburb';
	private $urlPostcodeLookupSuburb = '$suburb';
	private $listingsPatt = '/<div class="overview " data-auto-compact>[\w\W]+?<\/div>/';
	private $rentListingAdapterPath = 'listing-adapters/RentListingAdapter.class.php';
	private $constantsClass = 'config/Constants.php';
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
		
		$postcode = $this->postcodeForSuburb($suburb);
		if ($postcode === -1) {
			throw new Exception('Failed to get postcode for suburb: ' . $suburb);
		}

		$urlWithSuburb = str_replace($this->urlTemplateSuburb, $suburb, $this->urlTemplate);
		$urlWithState = str_replace($this->urlTemplateState, $this->urlState, $urlWithSuburb);
		$urlWithPostcode = str_replace($this->urlTemplatePostcode, $postcode, $urlWithState);
		$urlWithPageNum = str_replace($this->urlTemplatePageNum, $pgNum, $urlWithPostcode);
		return $urlWithPageNum;
	}
	
	function getListingsFromHtml($pageNum, $html) {
		$listingMatches = $this->getListingMatchesFromHtml($html);
		$listings = [];
		
		require_once($this->rentListingAdapterPath);
		for ($i = 0; $i < count($listingMatches); $i++) {
			$html = $listingMatches[$i];
			$listing = new RentListingAdapter($html);
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
	
	function postcodeForSuburb($suburb) {
		$suburbLower = strtolower($suburb);
		require_once($this->constantsClass);

		$cached = isset(Constants::$postcodes[$suburbLower]);
		if ($cached) {
			return Constants::$postcodes[$suburbLower];
		}
		
		$suburbLowerUrlParam = urlencode($suburbLower);
		$url = str_replace($this->urlPostcodeLookupSuburb, $suburbLowerUrlParam, $this->urlPostcodeLookup);
		curl_setopt($this->ch, CURLOPT_URL, $url);
		$jsonStr = curl_exec($this->ch);
		$json = json_decode($jsonStr, true);
		
		if (!$json) {
			throw new Exception('Failed to get postcode from API (it didnt return anything)');
		}
		return $this->getPostcodeFromJson($json);
	}
	
	private function getPostcodeFromJson($json) {
		switch (count($json)) {
			case 0:
				error_log('Postcode API lookup didnt get any postcodes for ' . $suburb);
				return -1;
				break;
			case 1:
				$postcode = $json[0]['postcode'];
				return $postcode;
				break;
			default:
				foreach ($json as $postcodeObj) {
					if (isset($postcodeObj['state']['abbreviation'])) {
						$state = strtolower($postcodeObj['state']['abbreviation']);
						$targetStateLower = strtolower($this->urlState);
						if ($state === $targetStateLower && isset($postcodeObj['postcode'])) {
							return $postcodeObj['postcode'];
						}
					}
				}

				throw new Exception('Couldnt get the ' . $this->urlState . ' postcode from the API response: ' . json_encode($json));
				break;
		}
	}
}