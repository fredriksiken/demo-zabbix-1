<?php declare(strict_types = 0);
/*
** Copyright (C) 2001-2026 Zabbix SIA
**
** This program is free software: you can redistribute it and/or modify it under the terms of
** the GNU Affero General Public License as published by the Free Software Foundation, version 3.
** This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
** without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
** See the GNU Affero General Public License for more details.
**
** You should have received a copy of the GNU Affero General Public License along with this program.
** If not, see <https://www.gnu.org/licenses/>.
**/

namespace Widgets\TrafficLight\Includes;

use CNumberParser;
use CParser;

/**
 * Pure helper for traffic-light thresholds and aggregation semantics.
 */
class TrafficLightLogic {

	public const STATE_GREEN = 'green';
	public const STATE_YELLOW = 'yellow';
	public const STATE_RED = 'red';

	public const COVER_MESSAGE_NO_DATA = 'No data found';
	public const COVER_MESSAGE_INVALID_CONFIG = 'Please update configuration';
	public const COVER_MESSAGE_UNSUPPORTED_ITEMS = 'Unsupported items';

	/**
	 * @param array<int, array{threshold?: string|float|int}> $thresholds
	 * @return array{0: float, 1: float}|null array(yellow_threshold, red_threshold) or null if invalid config.
	 */
	public static function parseThresholds(array $thresholds): ?array {
		if (count($thresholds) !== 2) {
			return null;
		}

		$number_parser = new CNumberParser([
			'with_size_suffix' => true,
			'with_time_suffix' => true,
			'is_binary_size' => false
		]);

		$yellow_raw = $thresholds[0]['threshold'] ?? null;
		$red_raw = $thresholds[1]['threshold'] ?? null;

		$yellow_parsed = $yellow_raw !== null && $number_parser->parse($yellow_raw) === CParser::PARSE_SUCCESS
			? (float) $number_parser->calcValue()
			: null;
		$red_parsed = $red_raw !== null && $number_parser->parse($red_raw) === CParser::PARSE_SUCCESS
			? (float) $number_parser->calcValue()
			: null;

		if ($yellow_parsed === null || $red_parsed === null) {
			return null;
		}

		// MVP contract: red_threshold >= yellow_threshold (inclusive), with direction fixed to higher_is_worse.
		if ($red_parsed < $yellow_parsed) {
			return null;
		}

		return [$yellow_parsed, $red_parsed];
	}

	public static function isSupportedValueType(int $value_type): bool {
		return in_array($value_type, [ITEM_VALUE_TYPE_FLOAT, ITEM_VALUE_TYPE_UINT64], true);
	}

	public static function classifyValue(float $value, float $yellow_threshold, float $red_threshold): string {
		if ($value >= $red_threshold) {
			return self::STATE_RED;
		}

		if ($value >= $yellow_threshold) {
			return self::STATE_YELLOW;
		}

		return self::STATE_GREEN;
	}

	/**
	 * @return string one of STATE_RED/STATE_YELLOW/STATE_GREEN
	 */
	public static function reduceWorstState(int $green_items, int $yellow_items, int $red_items): string {
		// MVP semantics: worst-state-wins (any red => red, else any yellow => yellow, else green).
		if ($red_items > 0) {
			return self::STATE_RED;
		}

		if ($yellow_items > 0) {
			return self::STATE_YELLOW;
		}

		return self::STATE_GREEN;
	}

	/**
	 * Deterministic cover precedence for exceptional cases.
	 *
	 * @param bool $has_selection whether user selected 1+ item patterns (pre-validation).
	 * @param array{0: float, 1: float}|null $thresholds parsed thresholds or null for invalid config.
	 * @param bool $unsupported_items_present whether matched items include unsupported value types.
	 */
	public static function decideCoverMessage(bool $has_selection, ?array $thresholds, bool $unsupported_items_present): string {
		if ($thresholds === null) {
			return self::COVER_MESSAGE_INVALID_CONFIG;
		}

		if (!$has_selection) {
			return self::COVER_MESSAGE_NO_DATA;
		}

		if ($unsupported_items_present) {
			return self::COVER_MESSAGE_UNSUPPORTED_ITEMS;
		}

		return self::COVER_MESSAGE_NO_DATA;
	}
}

