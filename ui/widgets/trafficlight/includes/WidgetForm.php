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

use CNumberParser;
use CParser;
use Zabbix\Widgets\{
	CWidgetField,
	CWidgetForm,
	Fields\CWidgetFieldPatternSelectItem,
	Fields\CWidgetFieldThresholds
};

/**
 * Traffic light widget form.
 */
class WidgetForm extends CWidgetForm {

	public function addFields(): self {
		return $this
			->addField(
				(new CWidgetFieldPatternSelectItem('items', _('Item patterns')))
					->setFlags(CWidgetField::FLAG_NOT_EMPTY | CWidgetField::FLAG_LABEL_ASTERISK)
			)
			->addField(
				new CWidgetFieldThresholds('thresholds', _('Thresholds'))
			);
	}

	public function validate(bool $strict = false): array {
		$errors = parent::validate($strict);

		if ($errors) {
			return $errors;
		}

		$thresholds = $this->getFieldValue('thresholds');

		// MVP contract: shared yellow/red thresholds (exactly two cutoffs).
		if (count($thresholds) !== 2) {
			$errors[] = _s('Invalid parameter "%1$s": %2$s.', _('Thresholds'), _('exactly two thresholds are required'));
		}

		if (count($thresholds) === 2) {
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
				$errors[] = _s('Invalid parameter "%1$s": %2$s.', _('Thresholds'), _('numeric values are required'));
			}
			elseif ($red_parsed < $yellow_parsed) {
				$errors[] = _s('Invalid parameter "%1$s": %2$s.', _('Thresholds'), _('must satisfy red threshold >= yellow threshold'));
			}
		}

		return $errors;
	}
}
