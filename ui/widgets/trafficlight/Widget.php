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


namespace Widgets\TrafficLight;

use CNumberParser,
	CParser;

use Zabbix\Core\CWidget;

class Widget extends CWidget {

	public const DIRECTION_HIGHER_IS_WORSE = 0;
	public const DIRECTION_LOWER_IS_WORSE = 1;

	public const STATUS_GREEN = 'green';
	public const STATUS_YELLOW = 'yellow';
	public const STATUS_RED = 'red';
	public const STATUS_NO_DATA = 'no-data';
	public const STATUS_UNSUPPORTED = 'unsupported';
	public const STATUS_INVALID_CONFIG = 'invalid-config';

	public function getDefaultName(): string {
		return _('Traffic light');
	}

	/**
	 * Parse a numeric threshold using the same suffix rules as other numeric widgets.
	 */
	public static function parseThresholdValue(string $value, bool $is_binary_units = false): ?float {
		$number_parser = new CNumberParser([
			'with_size_suffix' => true,
			'with_time_suffix' => true,
			'is_binary_size' => $is_binary_units
		]);

		if ($number_parser->parse(trim($value)) !== CParser::PARSE_SUCCESS) {
			return null;
		}

		return $number_parser->calcValue();
	}

	/**
	 * Canonicalize a parsed threshold so save/load and render-time parsing use the same value.
	 */
	public static function formatThresholdValue(float $value): string {
		return (string) $value;
	}

	public static function validateThresholdOrder(float $warning_threshold, float $critical_threshold, int $direction): bool {
		return match ($direction) {
			self::DIRECTION_HIGHER_IS_WORSE => $warning_threshold < $critical_threshold,
			self::DIRECTION_LOWER_IS_WORSE => $warning_threshold > $critical_threshold,
			default => false
		};
	}

	/**
	 * Convert a current value and threshold bounds into a traffic-light status payload.
	 *
	 * @return array{status: string, state_label: string}
	 */
	public static function evaluateStatus(?float $value, float $warning_threshold, float $critical_threshold,
			int $direction): array {
		if ($value === null) {
			return [
				'status' => self::STATUS_NO_DATA,
				'state_label' => _('No data')
			];
		}

		if ($direction === self::DIRECTION_HIGHER_IS_WORSE) {
			if ($value < $warning_threshold) {
				return ['status' => self::STATUS_GREEN, 'state_label' => _('Green')];
			}

			if ($value < $critical_threshold) {
				return ['status' => self::STATUS_YELLOW, 'state_label' => _('Warning')];
			}

			return ['status' => self::STATUS_RED, 'state_label' => _('Critical')];
		}

		if ($direction === self::DIRECTION_LOWER_IS_WORSE) {
			if ($value > $warning_threshold) {
				return ['status' => self::STATUS_GREEN, 'state_label' => _('Green')];
			}

			if ($value > $critical_threshold) {
				return ['status' => self::STATUS_YELLOW, 'state_label' => _('Warning')];
			}

			return ['status' => self::STATUS_RED, 'state_label' => _('Critical')];
		}

		return [
			'status' => self::STATUS_INVALID_CONFIG,
			'state_label' => _('Invalid configuration')
		];
	}
}
