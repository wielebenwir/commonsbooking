<?php

namespace CommonsBooking\Service;

class Holiday {
	/**
	 * Returns state mapping. According to https://de.wikipedia.org/wiki/Land_(Deutschland)#Amtliche_bzw._Eigenbezeichnungen
	 * @return string[]
	 */
	static function returnStates(): array {
		return [
			'BW' => 'BADEN WUERTEMBERG',
			'BY' => 'BAYERN',
			'BE' => 'BERLIN',
			'BB' => 'BRANDENBURG',
			'HB' => 'BREMEN',
			'HH' => 'HAMBURG',
			'HE' => 'HESSEN',
			'MV' => 'MECKLENBURG VORPOMMERN',
			'NI' => 'NIEDERSACHSEN',
			'NW' => 'NORDRHEIN WESTPHALEN',
			'RP' => 'RHEINLAND PFALZ',
			'SL' => 'SAARLAND',
			'SN' => 'SACHSEN',
			'ST' => 'SACHSEN ANHALT',
			'SH' => 'SCHLESWIG HOLSTEIN',
			'TH' => 'THUERINGEN',
			'BUND' => 'NATIONAL'
		];
	}
}