<?php
/**
 * Math webservice definition.
 */

/**
 * Math webservice.
 */
class Math {
	/**
	 * Add two float values.
	 * @param float $a First float value.
	 * @param float $b Second float value.
	 * @return float Sum of both parameters.
	 */
	function add($a, $b) {
		return (float) $a + (float) $b;
	}
}

define('SERVICE_CLASS', Math);
require '/usr/share/php/libthomsoft-webservice-php/libthomsoft-webservice.php';
