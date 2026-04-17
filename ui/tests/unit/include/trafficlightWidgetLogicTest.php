<?php declare(strict_types = 0);
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

require_once __DIR__.'/../../../widgets/trafficlight/includes/TrafficLightLogic.php';

use PHPUnit\Framework\TestCase;
use Widgets\TrafficLight\Includes\TrafficLightLogic;

class trafficlightWidgetLogicTest extends TestCase {

	public function testParseThresholdsValid(): void {
		$thresholds = [
			['threshold' => '10'],
			['threshold' => '20']
		];

		$this->assertSame([10.0, 20.0], TrafficLightLogic::parseThresholds($thresholds));
	}

	/**
	 * @dataProvider providerParseThresholdsInvalid
	 */
	public function testParseThresholdsInvalid(?array $thresholds): void {
		$this->assertNull(TrafficLightLogic::parseThresholds($thresholds ?? []));
	}

	public static function providerParseThresholdsInvalid(): array {
		return [
			[[['threshold' => '20'], ['threshold' => '10']]], // ordering red < yellow
			[[['threshold' => 'abc'], ['threshold' => '20']]], // invalid numeric
			[[['threshold' => '10']]], // count != 2
			[[]] // empty
		];
	}

	/**
	 * @dataProvider providerClassifyValueInclusiveBoundaries
	 */
	public function testClassifyValueInclusiveBoundaries(float $value, float $yellow, float $red, string $expected_state): void {
		$this->assertSame($expected_state, TrafficLightLogic::classifyValue($value, $yellow, $red));
	}

	public static function providerClassifyValueInclusiveBoundaries(): array {
		return [
			[20.0, 10.0, 20.0, TrafficLightLogic::STATE_RED],
			[10.0, 10.0, 20.0, TrafficLightLogic::STATE_YELLOW],
			[19.999, 10.0, 20.0, TrafficLightLogic::STATE_YELLOW],
			[9.999, 10.0, 20.0, TrafficLightLogic::STATE_GREEN]
		];
	}

	public function testReduceWorstState(): void {
		$this->assertSame(TrafficLightLogic::STATE_GREEN,
			TrafficLightLogic::reduceWorstState(10, 0, 0));

		$this->assertSame(TrafficLightLogic::STATE_YELLOW,
			TrafficLightLogic::reduceWorstState(10, 1, 0));

		$this->assertSame(TrafficLightLogic::STATE_RED,
			TrafficLightLogic::reduceWorstState(10, 1, 2));
	}

	/**
	 * @dataProvider providerDecideCoverMessage
	 */
	public function testDecideCoverMessage(bool $has_selection, ?array $thresholds, bool $unsupported_items_present, string $expected_message): void {
		$this->assertSame($expected_message,
			TrafficLightLogic::decideCoverMessage($has_selection, $thresholds, $unsupported_items_present));
	}

	public static function providerDecideCoverMessage(): array {
		return [
			// invalid config beats everything else
			[true, null, true, TrafficLightLogic::COVER_MESSAGE_INVALID_CONFIG],

			// empty selection
			[false, [10.0, 20.0], false, TrafficLightLogic::COVER_MESSAGE_NO_DATA],

			// unsupported items
			[true, [10.0, 20.0], true, TrafficLightLogic::COVER_MESSAGE_UNSUPPORTED_ITEMS],

			// no-data fallback
			[true, [10.0, 20.0], false, TrafficLightLogic::COVER_MESSAGE_NO_DATA]
		];
	}

	public function testIsSupportedValueType(): void {
		$this->assertTrue(TrafficLightLogic::isSupportedValueType(ITEM_VALUE_TYPE_FLOAT));
		$this->assertTrue(TrafficLightLogic::isSupportedValueType(ITEM_VALUE_TYPE_UINT64));

		$this->assertFalse(TrafficLightLogic::isSupportedValueType(ITEM_VALUE_TYPE_STR));
	}
}
