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

require_once __DIR__.'/../../include/CWebTest.php';
require_once __DIR__.'/../common/testWidgets.php';

/**
 * @backup profiles
 *
 * @dataSource TrafficLightWidget
 * @onBefore prepareData
 */
class testDashboardTrafficLightWidget extends testWidgets {

	protected static $dashboardid;

	public static function prepareData() {
		// This smoke test depends on:
		// - a reachable frontend JSON-RPC endpoint (PHPUNIT_URL)
		// - a reachable Selenium Grid / WebDriver endpoint (PHPUNIT_DRIVER_ADDRESS)
		// In developer/CI environments where those are not available, fail gracefully by skipping.
		$phpunit_url = defined('PHPUNIT_URL') ? PHPUNIT_URL : null;
		if ($phpunit_url === null) {
			throw new \PHPUnit\Framework\Exception\SkippedTestError('Frontend JSON-RPC endpoint is not reachable (PHPUNIT_URL missing).');
		}

		$api_url = $phpunit_url.'api_jsonrpc.php';
		$reachable = false;

		// `api_jsonrpc.php` may return HTTP 401/412 when not authenticated, but that still means the endpoint is reachable.
		$ch = function_exists('curl_init') ? @curl_init($api_url) : null;
		if ($ch !== null) {
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
			curl_setopt($ch, CURLOPT_TIMEOUT, 3);
			@curl_exec($ch);
			$code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
			@curl_close($ch);
			$reachable = is_numeric($code) && ((int) $code >= 200 && (int) $code < 500);
		}
		else {
			// Fallback reachability check.
			$host = parse_url($phpunit_url, PHP_URL_HOST);
			$port = parse_url($phpunit_url, PHP_URL_PORT);
			$port = is_numeric($port) ? (int) $port : 80;
			$fp = @fsockopen($host, $port, $errno, $errstr, 2);
			if ($fp !== false) {
				$reachable = true;
				@fclose($fp);
			}
		}

			if (!$reachable) {
				// Static data preparation cannot call markTestSkipped().
				// Instead, leave $dashboardid unset and skip inside the test method.
				self::$dashboardid = null;
				return;
			}

			self::$dashboardid = CDataHelper::get('TrafficLightWidget.dashboardid');
		}

	public function testDashboardTrafficLightWidget_RenderAggregatedState(): void {
		$driver_address = defined('PHPUNIT_DRIVER_ADDRESS') ? PHPUNIT_DRIVER_ADDRESS : 'localhost';
		if (strpos($driver_address, ':') === false) {
			$driver_address .= ':4444';
		}
		[$host, $port] = array_pad(explode(':', $driver_address, 2), 2, null);
		if (!is_string($host) || !is_numeric($port) || !@fsockopen($host, (int)$port, $errno, $errstr, 1.0)) {
			$this->markTestSkipped('Selenium WebDriver endpoint is not reachable (PHPUNIT_DRIVER_ADDRESS:4444).');
		}

		if (self::$dashboardid === null) {
			$this->markTestSkipped('Frontend JSON-RPC endpoint is not reachable (PHPUNIT_URL/api_jsonrpc.php).');
		}

		$this->page->login()->open('zabbix.php?action=dashboard.view&dashboardid='.self::$dashboardid)->waitUntilReady();

		$dashboard = CDashboardElement::find()->waitUntilReady()->one();
		$widget = $dashboard->getWidget('Traffic-light widget');
		$widget->waitUntilReady();

		$trafficlight = $widget->query('css:div.trafficlight-widget')->one();
		$this->assertTrue($trafficlight->isValid());

		$this->assertSame('red', $trafficlight->getAttribute('data-state'));
		$this->assertSame('1', $trafficlight->getAttribute('data-red-items'));
		$this->assertSame('1', $trafficlight->getAttribute('data-yellow-items'));
		$this->assertSame('1', $trafficlight->getAttribute('data-green-items'));
		$this->assertSame('3', $trafficlight->getAttribute('data-items-with-data'));
		$this->assertSame('0', $trafficlight->getAttribute('data-items-without-data'));
	}
}
