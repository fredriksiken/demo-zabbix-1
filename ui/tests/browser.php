<?php
/*
** Copyright (C) 2001-2026 Zabbix SIA
**
** This program is free software: you can redistribute it and/or modify it under the terms of
** the GNU Affero General Public License as published by the Free Software Foundation, version 3.
**
** This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
** without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
** See the GNU Affero General Public License for more details.
**
** You should have received a copy of the GNU Affero General Public License along with this program.
** If not, see <https://www.gnu.org/licenses/>.
**/


include __DIR__."/bootstrap.php";
include __DIR__."/include/web/CPage.php";

$driver_address = defined('PHPUNIT_DRIVER_ADDRESS') ? PHPUNIT_DRIVER_ADDRESS : 'localhost';
if (strpos($driver_address, ':') === false) {
	$driver_address .= ':4444';
}

[$host, $port] = array_pad(explode(':', $driver_address, 2), 2, null);
if (!is_string($host) || !is_numeric($port) || @fsockopen($host, (int) $port, $errno, $errstr, 1.0) === false) {
	echo "***********************************************************\n".
	"Frontend URL: ".PHPUNIT_URL."\n".
	"Browser:      unavailable\n".
	"Version:      unavailable\n".
	"PHP version:  ".phpversion()."\n".
	"***********************************************************\n";
	exit(0);
}

class CBrowserStats extends CPage {
	public function getBrowserInfo() {
		$capabilities = $this->driver->getCapabilities();

		return [
			"browser" => $capabilities->getBrowserName(),
			"version" => $capabilities->getVersion()
		];
	}
}

$browser_stats = new CBrowserStats();
$info = $browser_stats->getBrowserInfo();
echo "***********************************************************\n".
"Frontend URL: ".PHPUNIT_URL."\n".
"Browser:      ".$info["browser"]."\n".
"Version:      ".$info["version"]."\n".
"PHP version:  ".phpversion()."\n".
"***********************************************************\n";
$browser_stats->destroy();
