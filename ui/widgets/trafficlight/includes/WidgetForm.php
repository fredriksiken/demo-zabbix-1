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
use CTrafficLightWidgetHelper;

use Zabbix\Widgets\{
	CWidgetField,
	CWidgetForm
};

use Zabbix\Widgets\Fields\{
	CWidgetFieldMultiSelectItem,
	CWidgetFieldNumericBox
};

class WidgetForm extends CWidgetForm {

	private const DEFAULT_YELLOW_THRESHOLD = 50;
	private const DEFAULT_RED_THRESHOLD = 80;

	private ?array $item = null;

	public function __construct(array $values, ?string $templateid) {
		parent::__construct($values, $templateid);

		$itemids = $this->getSelectedItemIds($this->values['itemid'] ?? null);

		if ($itemids !== null) {
			$items = API::Item()->get([
				'output' => ['itemid', 'value_type', 'units'],
				'itemids' => $itemids,
				'webitems' => true
			]);

			if ($items) {
				$this->item = $items[0];
			}
		}
	}

	public function validate(bool $strict = false): array {
		$errors = parent::validate($strict);

		if ($errors) {
			return $errors;
		}

		$thresholds = CTrafficLightWidgetHelper::parseThresholds(
			$this->getFieldValue('yellow_threshold'),
			$this->getFieldValue('red_threshold'),
			$this->item !== null && isBinaryUnits($this->item['units'])
		);

		if ($thresholds['yellow'] >= $thresholds['red']) {
			$errors[] = _s('Invalid parameter "%1$s": %2$s.', _('Red threshold'),
				_s('value must be greater than "%1$s"', _('Yellow threshold'))
			);
		}

		if ($strict && $this->item !== null
				&& !CTrafficLightWidgetHelper::isSupportedItemValueType((int) $this->item['value_type'])) {
			$errors[] = _s('Invalid parameter "%1$s": %2$s.', _('Item'), _('only numeric items are supported'));
		}

		return $errors;
	}

	public function addFields(): self {
		return $this
			->addField(
				(new CWidgetFieldMultiSelectItem('itemid', _('Item')))
					->setFlags(CWidgetField::FLAG_NOT_EMPTY | CWidgetField::FLAG_LABEL_ASTERISK)
					->setMultiple(false)
			)
			->addField(
				(new CWidgetFieldNumericBox('yellow_threshold', _('Yellow threshold')))
					->setDefault(self::DEFAULT_YELLOW_THRESHOLD)
					->setFlags(CWidgetField::FLAG_NOT_EMPTY | CWidgetField::FLAG_LABEL_ASTERISK)
			)
			->addField(
				(new CWidgetFieldNumericBox('red_threshold', _('Red threshold')))
					->setDefault(self::DEFAULT_RED_THRESHOLD)
					->setFlags(CWidgetField::FLAG_NOT_EMPTY | CWidgetField::FLAG_LABEL_ASTERISK)
			);
	}

	private function getSelectedItemIds($value): ?array {
		if ($value === null || $value === '') {
			return null;
		}

		if (is_array($value)) {
			if (array_key_exists(CWidgetField::FOREIGN_REFERENCE_KEY, $value)) {
				return null;
			}

			$itemids = array_values(array_filter($value, static fn($itemid): bool => $itemid !== ''));

			return $itemids !== [] ? $itemids : null;
		}

		return [(string) $value];
	}
}
