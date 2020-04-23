<?php

interface ListingAdapterInterface {
	function extractId($html);
	function extractPrice($html);
	function extractAddress($html);
	function extractUrl($html);
	function jsonSerialize();
	function applyPattern($patternName, $html);
	function getPrice();
}