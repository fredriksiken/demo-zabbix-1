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
use Zabbix\Widgets\CWidgetField;
use Widgets\TrafficLight\Includes\WidgetForm;
use Widgets\TrafficLight\Widget;

require_once dirname(__DIR__, 5).'/widgets/trafficlight/includes/WidgetForm.php';
require_once dirname(__DIR__, 5).'/widgets/trafficlight/Widget.php';

class TrafficLightWidgetTest extends TestCase {

	/**
	 * @dataProvider statusProvider
	 */
	public function testEvaluateStatus(?float $value, float $warning, float $critical, int $direction, string $status): void {
		$this->assertSame($status, Widget::evaluateStatus($value, $warning, $critical, $direction)['status']);
	}

	public static function statusProvider(): array {
		return [
			[null, 50.0, 80.0, Widget::DIRECTION_HIGHER_IS_WORSE, Widget::STATUS_NO_DATA],
			[10.0, 50.0, 80.0, Widget::DIRECTION_HIGHER_IS_WORSE, Widget::STATUS_GREEN],
			[60.0, 50.0, 80.0, Widget::DIRECTION_HIGHER_IS_WORSE, Widget::STATUS_YELLOW],
			[90.0, 50.0, 80.0, Widget::DIRECTION_HIGHER_IS_WORSE, Widget::STATUS_RED],
			[90.0, 80.0, 50.0, Widget::DIRECTION_LOWER_IS_WORSE, Widget::STATUS_GREEN],
			[60.0, 80.0, 50.0, Widget::DIRECTION_LOWER_IS_WORSE, Widget::STATUS_YELLOW],
			[40.0, 80.0, 50.0, Widget::DIRECTION_LOWER_IS_WORSE, Widget::STATUS_RED]
		];
	}

	/**
	 * @dataProvider orderProvider
	 */
	public function testValidateThresholdOrder(float $warning, float $critical, int $direction, bool $expected): void {
		$this->assertSame($expected, Widget::validateThresholdOrder($warning, $critical, $direction));
	}

	public static function orderProvider(): array {
		return [
			[50.0, 80.0, Widget::DIRECTION_HIGHER_IS_WORSE, true],
			[80.0, 50.0, Widget::DIRECTION_HIGHER_IS_WORSE, false],
			[80.0, 50.0, Widget::DIRECTION_LOWER_IS_WORSE, true],
			[50.0, 80.0, Widget::DIRECTION_LOWER_IS_WORSE, false]
		];
	}

	/**
	 * @dataProvider parseProvider
	 */
	public function testParseThresholdValue(string $input, ?float $expected): void {
		$this->assertSame($expected, Widget::parseThresholdValue($input));
	}

	public static function parseProvider(): array {
		return [
			['10', 10.0],
			[' 1.5K ', 1500.0],
			['not-a-number', null]
		];
	}

	public function testEvaluateStatusWithInvalidDirection(): void {
		$this->assertSame(Widget::STATUS_INVALID_CONFIG, Widget::evaluateStatus(10.0, 1.0, 2.0, 99)['status']);
	}

	public function testValidateCanonicalizesThresholdsForForeignReferences(): void {
		$form = (new WidgetForm([
			'itemid' => [CWidgetField::FOREIGN_REFERENCE_KEY => CWidgetField::REFERENCE_DASHBOARD],
			'direction' => Widget::DIRECTION_HIGHER_IS_WORSE,
			'warning_threshold' => '1.5K',
			'critical_threshold' => '2K'
		], null))
			->addFields()
			->setFieldsValues();

		$this->assertSame([], $form->validate());
		$this->assertSame('1.5', $form->getFieldValue('warning_threshold'));
		$this->assertSame('2', $form->getFieldValue('critical_threshold'));
	}
}
