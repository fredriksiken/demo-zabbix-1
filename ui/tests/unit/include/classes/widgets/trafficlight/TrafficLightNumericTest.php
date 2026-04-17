<?php declare(strict_types = 1);
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
use Widgets\TrafficLight\Includes\TrafficLightNumeric;

require_once __DIR__.'/../../../../../../widgets/trafficlight/includes/TrafficLightNumeric.php';

class TrafficLightNumericTest extends TestCase {

	public function testClassifyInclusiveBoundaries(): void {
		$yellow = 10;
		$red = 20;

		$this->assertSame(TrafficLightNumeric::STATE_GREEN, TrafficLightNumeric::classify(0, $yellow, $red));
		$this->assertSame(TrafficLightNumeric::STATE_YELLOW, TrafficLightNumeric::classify(10, $yellow, $red));
		$this->assertSame(TrafficLightNumeric::STATE_RED, TrafficLightNumeric::classify(20, $yellow, $red));

		$this->assertSame(TrafficLightNumeric::STATE_YELLOW, TrafficLightNumeric::classify(19.999, $yellow, $red));
		$this->assertSame(TrafficLightNumeric::STATE_GREEN, TrafficLightNumeric::classify(9.999, $yellow, $red));
	}

	public function testAggregateWorstStateWinsRedTakesPrecedence(): void {
		$this->assertSame(TrafficLightNumeric::STATE_RED,
			TrafficLightNumeric::aggregateWorstStateWins([
				TrafficLightNumeric::STATE_GREEN,
				TrafficLightNumeric::STATE_YELLOW,
				TrafficLightNumeric::STATE_RED
			])
		);

		$this->assertSame(TrafficLightNumeric::STATE_YELLOW,
			TrafficLightNumeric::aggregateWorstStateWins([
				TrafficLightNumeric::STATE_GREEN,
				TrafficLightNumeric::STATE_YELLOW
			])
		);

		$this->assertSame(TrafficLightNumeric::STATE_GREEN,
			TrafficLightNumeric::aggregateWorstStateWins([
				TrafficLightNumeric::STATE_GREEN,
				TrafficLightNumeric::STATE_GREEN
			])
		);
	}

	public function testEvaluateNoDataAllMissingLatestValues(): void {
		[$state, $summary, $label] = TrafficLightNumeric::evaluateItems([
			['supported' => true, 'value' => null],
			['supported' => true, 'value' => null]
		], 10, 20);

		$this->assertNull($state);
		$this->assertSame(0, $summary['items_with_data']);
		$this->assertSame(2, $summary['items_without_data']);
		$this->assertNull($label);
	}

	public function testEvaluateNoDataIfAnyItemMissingLatestValue(): void {
		[$state, $summary, $label] = TrafficLightNumeric::evaluateItems([
			['supported' => true, 'value' => 20.0],
			['supported' => true, 'value' => null]
		], 10, 20);

		$this->assertNull($state);
		$this->assertSame(1, $summary['items_with_data']);
		$this->assertSame(1, $summary['items_without_data']);
		$this->assertNull($label);
	}

	public function testEvaluateInvalidThresholdOrderingReturnsInvalidState(): void {
		[$state, $summary, $label] = TrafficLightNumeric::evaluateItems([
			['supported' => true, 'value' => 1.0]
		], 20, 10);

		$this->assertSame(TrafficLightNumeric::STATE_INVALID, $state);
		$this->assertSame(0, $summary['items_with_data']);
		$this->assertNull($label);
	}

	public function testEvaluateUnsupportedPrecedenceWinsOverMissingValues(): void {
		[$state, $summary, $label] = TrafficLightNumeric::evaluateItems([
			['supported' => false, 'value' => null],
			['supported' => true, 'value' => null]
		], 10, 20);

		$this->assertSame(TrafficLightNumeric::STATE_UNSUPPORTED, $state);
		$this->assertSame(0, $summary['items_with_data']);
		$this->assertNull($label);
	}

	public function testEvaluateAggregationWorstStateWinsFromInclusiveClassification(): void {
		// red_threshold is 20 => item with value 20 is RED (inclusive).
		// yellow_threshold is 10 => item with value 10 is YELLOW (inclusive).
		[$state, $summary, $label] = TrafficLightNumeric::evaluateItems([
			['supported' => true, 'value' => 20.0],
			['supported' => true, 'value' => 10.0],
			['supported' => true, 'value' => 0.0]
		], 10, 20);

		$this->assertSame(TrafficLightNumeric::STATE_RED, $state);
		$this->assertSame(3, $summary['items_with_data']);
		$this->assertSame(1, $summary['red_items']);
		$this->assertSame(1, $summary['yellow_items']);
		$this->assertSame(1, $summary['green_items']);
		$this->assertSame('Red', $label);
	}
}
