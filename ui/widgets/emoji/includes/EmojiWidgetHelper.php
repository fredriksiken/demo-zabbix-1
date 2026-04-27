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


namespace Widgets\Emoji\Includes;

use CNumberParser,
	CParser;

final class EmojiWidgetHelper {

	public static function normalizeThresholds(array $thresholds, bool $is_binary_units = false): array {
		$number_parser = new CNumberParser([
			'with_size_suffix' => true,
			'with_time_suffix' => true,
			'is_binary_size' => $is_binary_units
		]);

		$normalized = [];

		foreach ($thresholds as $threshold) {
			$threshold_value = trim((string) ($threshold['threshold'] ?? ''));
			$emoji = trim((string) ($threshold['emoji'] ?? ''));

			if ($threshold_value === '' && $emoji === '') {
				continue;
			}

			if ($number_parser->parse($threshold_value) !== CParser::PARSE_SUCCESS) {
				$threshold['threshold'] = $threshold_value;
				$threshold['emoji'] = $emoji;
				$normalized[] = $threshold;
				continue;
			}

			$normalized[] = [
				'threshold' => $threshold_value,
				'emoji' => $emoji,
				'threshold_value' => $number_parser->calcValue()
			];
		}

		uasort($normalized, static function (array $left, array $right): int {
			$left_value = $left['threshold_value'] ?? PHP_FLOAT_MIN;
			$right_value = $right['threshold_value'] ?? PHP_FLOAT_MIN;

			return $left_value <=> $right_value;
		});

		foreach ($normalized as &$threshold) {
			unset($threshold['threshold_value']);
		}
		unset($threshold);

		return array_values($normalized);
	}

	public static function selectThreshold(array $thresholds, float $value, bool $is_binary_units = false): ?array {
		$number_parser = new CNumberParser([
			'with_size_suffix' => true,
			'with_time_suffix' => true,
			'is_binary_size' => $is_binary_units
		]);

		$selected = null;

		foreach ($thresholds as $threshold) {
			$threshold_value = trim((string) ($threshold['threshold'] ?? ''));

			if ($number_parser->parse($threshold_value) !== CParser::PARSE_SUCCESS) {
				continue;
			}

			if ($number_parser->calcValue() > $value) {
				break;
			}

			$selected = $threshold;
		}

		return $selected;
	}
}
