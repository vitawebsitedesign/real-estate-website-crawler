<?php

require_once('listing-adapters/ListingAdapterInterface.php');

class RentListingAdapter implements ListingAdapterInterface, JsonSerializable {
	private $patterns = [
		'id' => '/[0-9]+(?=\?search_token=)/',
		'price' => '/(?<=<span class="price">\$)[0-9]+/',
		'address' => '/(?<=<span class="line">)[0-9a-zA-Z\/\s]+/',
		'url' => '/(?<=href=").+(?=")/'
	];
	private $currencySign = '$';
	private $constantsClass = 'Constants.php';
	private $id;
	private $price;
	private $address;
	private $url;
	
	function __construct($html) {
		$this->id = $this->extractId($html);
		$this->price = $this->extractPrice($html);
		$this->address = $this->extractAddress($html);
		$this->url = $this->extractUrl($html);
	}
	
	function jsonSerialize() {
		return [
			'id' => $this->id,
			'price' => $this->price,
			'address' => $this->address,
			'url' => $this->url
		];
	}
	
	function getPrice() {
		return $this->price;
	}
	
	function extractId($html) {
		return $this->applyPattern('id', $html);
	}
	
	function extractPrice($html) {
		$priceWithCurrencySign = $this->applyPattern('price', $html);
		$price = str_replace($this->currencySign, '', $priceWithCurrencySign);
		return floatval($price);
	}
	
	function extractAddress($html) {
		return $this->applyPattern('address', $html);
	}
	
	function extractUrl($html) {
		return $this->applyPattern('url', $html);
	}	
	
	function applyPattern($patternName, $html) {
		$pattern = $this->patterns[$patternName];
		if (!$pattern) {
			throw new Exception('Unrecognized pattern to apply against a listing: ' . $patternName);
		}

		preg_match($pattern, $html, $match);
		if (!isset($match[0])) {
			return null;
		}

		return $match[0];
	}
}