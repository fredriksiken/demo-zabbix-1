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

use API;

use Widgets\TrafficLight\Widget;

use Zabbix\Widgets\{
	CWidgetField,
	CWidgetForm
};

use Zabbix\Widgets\Fields\{
	CWidgetFieldColor,
	CWidgetFieldMultiSelectItem,
	CWidgetFieldNumericBox,
	CWidgetFieldRadioButtonList,
	CWidgetFieldTextBox
};

class WidgetForm extends CWidgetForm {

	private const DEFAULT_WARNING_THRESHOLD = '70';
	private const DEFAULT_CRITICAL_THRESHOLD = '90';

	private const DEFAULT_GREEN_COLOR = '3BC97D';
	private const DEFAULT_YELLOW_COLOR = 'FCCB1D';
	private const DEFAULT_RED_COLOR = 'E65660';
	private const DEFAULT_NO_DATA_COLOR = '8899AA';

	public function __construct(array $values, ?string $templateid) {
		parent::__construct($values, $templateid);
	}

	protected function normalizeValues(array $values): array {
		return array_replace([
			'direction' => Widget::DIRECTION_HIGHER_IS_WORSE,
			'warning_threshold' => self::DEFAULT_WARNING_THRESHOLD,
			'critical_threshold' => self::DEFAULT_CRITICAL_THRESHOLD,
			'green_label' => _('Green'),
			'yellow_label' => _('Warning'),
			'red_label' => _('Critical'),
			'no_data_label' => _('No data'),
			'green_color' => self::DEFAULT_GREEN_COLOR,
			'yellow_color' => self::DEFAULT_YELLOW_COLOR,
			'red_color' => self::DEFAULT_RED_COLOR,
			'no_data_color' => self::DEFAULT_NO_DATA_COLOR
		], parent::normalizeValues($values));
	}

	public function addFields(): self {
		return $this
			->addField(
				(new CWidgetFieldMultiSelectItem('itemid', _('Item')))
					->setFlags(CWidgetField::FLAG_NOT_EMPTY | CWidgetField::FLAG_LABEL_ASTERISK)
					->setMultiple(false)
			)
			->addField(
				(new CWidgetFieldRadioButtonList('direction', _('Comparison direction'), [
					Widget::DIRECTION_HIGHER_IS_WORSE => _('Higher values are worse'),
					Widget::DIRECTION_LOWER_IS_WORSE => _('Lower values are worse')
				]))->setDefault(Widget::DIRECTION_HIGHER_IS_WORSE)
			)
			->addField(
				(new CWidgetFieldNumericBox('warning_threshold', _('Warning threshold')))
					->setDefault(self::DEFAULT_WARNING_THRESHOLD)
					->setFlags(CWidgetField::FLAG_NOT_EMPTY | CWidgetField::FLAG_LABEL_ASTERISK)
			)
			->addField(
				(new CWidgetFieldNumericBox('critical_threshold', _('Critical threshold')))
					->setDefault(self::DEFAULT_CRITICAL_THRESHOLD)
					->setFlags(CWidgetField::FLAG_NOT_EMPTY | CWidgetField::FLAG_LABEL_ASTERISK)
			)
			->addField(
				(new CWidgetFieldTextBox('green_label', _('Green label')))->setDefault(_('Green'))
			)
			->addField(
				(new CWidgetFieldTextBox('yellow_label', _('Warning label')))->setDefault(_('Warning'))
			)
			->addField(
				(new CWidgetFieldTextBox('red_label', _('Critical label')))->setDefault(_('Critical'))
			)
			->addField(
				(new CWidgetFieldTextBox('no_data_label', _('No-data label')))->setDefault(_('No data'))
			)
			->addField(
				(new CWidgetFieldColor('green_color', _('Green color')))->setDefault(self::DEFAULT_GREEN_COLOR)
			)
			->addField(
				(new CWidgetFieldColor('yellow_color', _('Warning color')))->setDefault(self::DEFAULT_YELLOW_COLOR)
			)
			->addField(
				(new CWidgetFieldColor('red_color', _('Critical color')))->setDefault(self::DEFAULT_RED_COLOR)
			)
			->addField(
				(new CWidgetFieldColor('no_data_color', _('No-data color')))->setDefault(self::DEFAULT_NO_DATA_COLOR)
			);
	}

	public function validate(bool $strict = false): array {
		$errors = parent::validate($strict);

		if ($errors) {
			return $errors;
		}

		$itemids = $this->getFieldValue('itemid');

		if (!array_key_exists(CWidgetField::FOREIGN_REFERENCE_KEY, $itemids) && $itemids !== []) {
			$items = API::Item()->get([
				'output' => ['itemid', 'value_type', 'units'],
				'itemids' => $itemids,
				'webitems' => true
			]);

			if (!$items) {
				$errors[] = _s('Invalid parameter "%1$s": %2$s.', _('Item'), _('cannot be found'));

				return $errors;
			}

			$value_type = (int) $items[0]['value_type'];

			if (!in_array($value_type, [ITEM_VALUE_TYPE_FLOAT, ITEM_VALUE_TYPE_UINT64], true)) {
				$errors[] = _s('Invalid parameter "%1$s": %2$s.', _('Item'),
					_('a numeric value is expected')
				);

				return $errors;
			}

			$is_binary_units = isBinaryUnits($items[0]['units']);
		}
		else {
			$is_binary_units = false;
		}

		$warning_threshold = Widget::parseThresholdValue($this->getFieldValue('warning_threshold'), $is_binary_units);
		$critical_threshold = Widget::parseThresholdValue($this->getFieldValue('critical_threshold'),
			$is_binary_units
		);

		if ($warning_threshold === null) {
			$errors[] = _s('Invalid parameter "%1$s": %2$s.', _('Warning threshold'),
				_('a numeric value is expected')
			);
		}

		if ($critical_threshold === null) {
			$errors[] = _s('Invalid parameter "%1$s": %2$s.', _('Critical threshold'),
				_('a numeric value is expected')
			);
		}

		if ($warning_threshold !== null) {
			$this->getField('warning_threshold')->setValue(Widget::formatThresholdValue($warning_threshold));
		}

		if ($critical_threshold !== null) {
			$this->getField('critical_threshold')->setValue(Widget::formatThresholdValue($critical_threshold));
		}

		if ($warning_threshold !== null && $critical_threshold !== null
				&& !Widget::validateThresholdOrder($warning_threshold, $critical_threshold,
					(int) $this->getFieldValue('direction')
				)) {
			$errors[] = _s('Invalid parameter "%1$s": %2$s.', _('Thresholds'),
				_('warning and critical thresholds are out of order')
			);
		}

		return $errors;
	}
}
