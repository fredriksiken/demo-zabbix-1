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

use Zabbix\Widgets\Fields\{
	CWidgetFieldMultiSelectItem,
	CWidgetFieldNumericBox
};

use Zabbix\Widgets\CWidgetForm;

/**
 * Traffic Light widget form.
 */
class WidgetForm extends CWidgetForm {

	public const DEFAULT_YELLOW_CUTOFF = 10;
	public const DEFAULT_GREEN_CUTOFF = 20;

	public function validate(bool $strict = false): array {
		$errors = parent::validate($strict);

		if ($errors) {
			return $errors;
		}

		$number_parser = new CNumberParser([
			'with_size_suffix' => true,
			'with_time_suffix' => true
		]);

		$number_parser->parse($this->getFieldValue('yellow_cutoff'));
		$yellow_cutoff = $number_parser->calcValue();

		$number_parser->parse($this->getFieldValue('green_cutoff'));
		$green_cutoff = $number_parser->calcValue();

		// Threshold model:
		//   green:  value >= green_cutoff
		//   yellow: yellow_cutoff <= value < green_cutoff
		//   red:    value < yellow_cutoff
		if ($yellow_cutoff > $green_cutoff) {
			$errors[] = _s('Invalid parameter "%1$s": %2$s.', _('Thresholds'),
				_('yellow cutoff must be no greater than green cutoff')
			);
		}

		return $errors;
	}

	public function addFields(): self {
		return $this
			->addField(
				(new CWidgetFieldMultiSelectItem('itemid', _('Item')))
					->setFlags(\Zabbix\Widgets\CWidgetField::FLAG_NOT_EMPTY | \Zabbix\Widgets\CWidgetField::FLAG_LABEL_ASTERISK)
					->setMultiple(false)
			)
			->addField(
				(new CWidgetFieldNumericBox('yellow_cutoff', _('Yellow cutoff')))
					->setDefault(self::DEFAULT_YELLOW_CUTOFF)
					->setFlags(\Zabbix\Widgets\CWidgetField::FLAG_NOT_EMPTY | \Zabbix\Widgets\CWidgetField::FLAG_LABEL_ASTERISK)
			)
			->addField(
				(new CWidgetFieldNumericBox('green_cutoff', _('Green cutoff')))
					->setDefault(self::DEFAULT_GREEN_CUTOFF)
					->setFlags(\Zabbix\Widgets\CWidgetField::FLAG_NOT_EMPTY | \Zabbix\Widgets\CWidgetField::FLAG_LABEL_ASTERISK)
			);
	}
}

