<?php

interface PropertyWebsiteInterface {
	function getListingsForSuburb($suburb);
	function getSearchPageHtml($suburb, $pgNum);
	function getSearchPageUrl($suburb, $pgNum);
	function getListingsFromHtml($pageNum, $html);
	function getListingMatchesFromHtml($html);
}