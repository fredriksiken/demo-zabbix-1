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


use PHPUnit\Framework\TestCase;

class CTrafficLightWidgetHelperTest extends TestCase {

	public function dataProviderEvaluate(): array {
		return [
			'below yellow is green' => [9.9, 10.0, 20.0, CTrafficLightWidgetHelper::STATE_GREEN],
			'yellow boundary is yellow' => [10.0, 10.0, 20.0, CTrafficLightWidgetHelper::STATE_YELLOW],
			'between thresholds is yellow' => [19.9, 10.0, 20.0, CTrafficLightWidgetHelper::STATE_YELLOW],
			'red boundary is red' => [20.0, 10.0, 20.0, CTrafficLightWidgetHelper::STATE_RED],
			'above red is red' => [42.0, 10.0, 20.0, CTrafficLightWidgetHelper::STATE_RED]
		];
	}

	/**
	 * @dataProvider dataProviderEvaluate
	 */
	public function testEvaluate(float $value, float $yellow_threshold, float $red_threshold, string $expected): void {
		$this->assertSame($expected,
			CTrafficLightWidgetHelper::evaluate($value, $yellow_threshold, $red_threshold)
		);
	}

	public function dataProviderParseThresholds(): array {
		return [
			'plain numbers' => ['10', '20', false, ['yellow' => 10.0, 'red' => 20.0]],
			'decimal values' => ['10.5', '20.25', false, ['yellow' => 10.5, 'red' => 20.25]],
			'decimal suffixes' => ['1K', '2K', false, ['yellow' => 1000.0, 'red' => 2000.0]],
			'binary suffixes' => ['1K', '2K', true, ['yellow' => 1024.0, 'red' => 2048.0]]
		];
	}

	/**
	 * @dataProvider dataProviderParseThresholds
	 */
	public function testParseThresholds(string $yellow_threshold, string $red_threshold, bool $is_binary_units,
			array $expected): void {
		$this->assertSame($expected,
			CTrafficLightWidgetHelper::parseThresholds($yellow_threshold, $red_threshold, $is_binary_units)
		);
	}

	public function testSupportedItemValueTypes(): void {
		$this->assertTrue(CTrafficLightWidgetHelper::isSupportedItemValueType(ITEM_VALUE_TYPE_FLOAT));
		$this->assertTrue(CTrafficLightWidgetHelper::isSupportedItemValueType(ITEM_VALUE_TYPE_UINT64));
		$this->assertFalse(CTrafficLightWidgetHelper::isSupportedItemValueType(ITEM_VALUE_TYPE_STR));
		$this->assertFalse(CTrafficLightWidgetHelper::isSupportedItemValueType(ITEM_VALUE_TYPE_LOG));
	}
}
