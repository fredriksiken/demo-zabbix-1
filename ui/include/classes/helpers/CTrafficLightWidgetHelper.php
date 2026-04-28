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

class CTrafficLightWidgetHelper {

	public const STATE_GREEN = 'GREEN';
	public const STATE_YELLOW = 'YELLOW';
	public const STATE_RED = 'RED';
	public const STATE_NO_DATA = 'NO_DATA';
	public const STATE_UNSUPPORTED = 'UNSUPPORTED';
	public const STATE_INACCESSIBLE = 'INACCESSIBLE';

	public static function isSupportedItemValueType(int $value_type): bool {
		return in_array($value_type, [ITEM_VALUE_TYPE_FLOAT, ITEM_VALUE_TYPE_UINT64]);
	}

	public static function parseThreshold(string $threshold, bool $is_binary_units = false): float {
		$number_parser = new CNumberParser([
			'with_size_suffix' => true,
			'with_time_suffix' => true,
			'is_binary_size' => $is_binary_units
		]);

		$number_parser->parse($threshold);

		return (float) $number_parser->calcValue();
	}

	public static function parseThresholds(string $yellow_threshold, string $red_threshold,
			bool $is_binary_units = false): array {
		return [
			'yellow' => self::parseThreshold($yellow_threshold, $is_binary_units),
			'red' => self::parseThreshold($red_threshold, $is_binary_units)
		];
	}

	public static function evaluate(float $value, float $yellow_threshold, float $red_threshold): string {
		if ($value < $yellow_threshold) {
			return self::STATE_GREEN;
		}

		if ($value < $red_threshold) {
			return self::STATE_YELLOW;
		}

		return self::STATE_RED;
	}
}
