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

namespace Widgets\TrafficLight\Includes;

/**
 * Numeric traffic-light evaluation helper.
 *
 * Semantics:
 * - higher_is_worse
 * - inclusive cutoffs:
 *   - value >= red_threshold => red
 *   - else if value >= yellow_threshold => yellow
 *   - else => green
 * - worst-state-wins aggregation:
 *   - any unsupported => unsupported
 *   - invalid thresholds => invalid
 *   - any red => red
 *   - else any yellow => yellow
 *   - else:
 *     - if all values are missing => null (no-data)
 *     - otherwise green
 */
class TrafficLightNumeric {

	public const STATE_GREEN = 'green';
	public const STATE_YELLOW = 'yellow';
	public const STATE_RED = 'red';
	public const STATE_NO_DATA = 'no-data';
	public const STATE_INVALID = 'invalid';
	public const STATE_UNSUPPORTED = 'unsupported';

	/**
	 * Classify a single numeric value using inclusive cutoffs.
	 */
	public static function classify(float $value, float $yellow_cutoff, float $red_cutoff): string {
		if ($value >= $red_cutoff) {
			return self::STATE_RED;
		}

		if ($value >= $yellow_cutoff) {
			return self::STATE_YELLOW;
		}

		return self::STATE_GREEN;
	}

	/**
	 * Aggregate per-item states using worst-state-wins.
	 *
	 * @param array<int, string> $states
	 */
	public static function aggregateWorstStateWins(array $states): string {
		$hasRed = false;
		$hasYellow = false;

		foreach ($states as $state) {
			if ($state === self::STATE_RED) {
				$hasRed = true;
				break;
			}

			if ($state === self::STATE_YELLOW) {
				$hasYellow = true;
			}
		}

		if ($hasRed) {
			return self::STATE_RED;
		}

		if ($hasYellow) {
			return self::STATE_YELLOW;
		}

		return self::STATE_GREEN;
	}

	/**
	 * Evaluate supported numeric items into an aggregated state.
	 *
	 * @param array<int, array{supported: bool, value: float|null}> $items
	 *
	 * @return array{0: string|null, 1: array, 2: string|null}
	 */
	public static function evaluateItems(array $items, float $yellow_cutoff, float $red_cutoff): array {
		if ($red_cutoff < $yellow_cutoff) {
			return [
				self::STATE_INVALID,
				[
					'matched_items' => count($items),
					'items_with_data' => 0,
					'items_without_data' => count($items),
					'green_items' => 0,
					'yellow_items' => 0,
					'red_items' => 0
				],
				null
			];
		}

		$matched_items = count($items);
		$items_with_data = 0;
		$items_without_data = $matched_items;

		$green = 0;
		$yellow = 0;
		$red = 0;

		$states = [];

		foreach ($items as $item) {
			if (!($item['supported'] ?? false)) {
				return [
					self::STATE_UNSUPPORTED,
					[
						'matched_items' => $matched_items,
						'items_with_data' => $items_with_data,
						'items_without_data' => $items_without_data,
						'green_items' => 0,
						'yellow_items' => 0,
						'red_items' => 0
					],
					null
				];
			}

			$value = $item['value'] ?? null;
			if ($value === null) {
				continue;
			}

			$items_with_data++;
			$items_without_data--;

			$state = self::classify($value, $yellow_cutoff, $red_cutoff);
			$states[] = $state;

			switch ($state) {
				case self::STATE_RED:
					$red++;
					break;
				case self::STATE_YELLOW:
					$yellow++;
					break;
				default:
					$green++;
					break;
			}
		}

		// MVP semantics: any missing latest value makes the whole widget resolve to no-data.
		if ($items_without_data > 0) {
			return [
				null,
				[
					'matched_items' => $matched_items,
					'items_with_data' => $items_with_data,
					'items_without_data' => $items_without_data,
					'green_items' => $green,
					'yellow_items' => $yellow,
					'red_items' => $red
				],
				null
			];
		}

		if ($items_with_data === 0) {
			return [
				null,
				[
					'matched_items' => $matched_items,
					'items_with_data' => 0,
					'items_without_data' => $matched_items,
					'green_items' => 0,
					'yellow_items' => 0,
					'red_items' => 0
				],
				null
			];
		}

		$state = self::aggregateWorstStateWins($states);

		$state_label = match ($state) {
			self::STATE_RED => _('Red'),
			self::STATE_YELLOW => _('Yellow'),
			default => _('Green')
		};

		return [
			$state,
			[
				'matched_items' => $matched_items,
				'items_with_data' => $items_with_data,
				'items_without_data' => $items_without_data,
				'green_items' => $green,
				'yellow_items' => $yellow,
				'red_items' => $red
			],
			$state_label
		];
	}
}
