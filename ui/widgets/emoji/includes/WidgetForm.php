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

use API;

use Zabbix\Widgets\{
	CWidgetField,
	CWidgetForm
};

use Zabbix\Widgets\Fields\{
	CWidgetFieldMultiSelectItem,
	CWidgetFieldMultiSelectOverrideHost,
	CWidgetFieldTextBox
};

use Widgets\Emoji\Widget;

class WidgetForm extends CWidgetForm {

	private bool $is_binary_units = false;

	public function __construct(array $values, ?string $templateid) {
		parent::__construct($values, $templateid);

		if (array_key_exists('itemid', $this->values) && is_array($this->values['itemid'])
				&& !array_key_exists(CWidgetField::FOREIGN_REFERENCE_KEY, $this->values['itemid'])) {
			$items = API::Item()->get([
				'output' => ['units'],
				'itemids' => $this->values['itemid'],
				'webitems' => true
			]);

			$this->is_binary_units = $items && isBinaryUnits($items[0]['units']);
		}
	}

	public function addFields(): self {
		return $this
			->addField(
				(new CWidgetFieldMultiSelectItem('itemid', _('Item')))
					->setFlags(CWidgetField::FLAG_NOT_EMPTY | CWidgetField::FLAG_LABEL_ASTERISK)
					->setMultiple(false)
			)
			->addField(
				(new CWidgetFieldEmojiThresholds('thresholds', _('Thresholds'), $this->is_binary_units))
					->setFlags(CWidgetField::FLAG_NOT_EMPTY | CWidgetField::FLAG_LABEL_ASTERISK)
			)
			->addField(
				(new CWidgetFieldTextBox('label', _('Label')))
					->setDefault('')
					->setMaxLength(255)
			)
			->addField(
				(new CWidgetFieldTextBox('fallback_emoji', _('Fallback emoji')))
					->setDefault(Widget::DEFAULT_FALLBACK_EMOJI)
					->setMaxLength(255)
					->setFlags(CWidgetField::FLAG_NOT_EMPTY | CWidgetField::FLAG_LABEL_ASTERISK)
			)
			->addField(
				new CWidgetFieldMultiSelectOverrideHost()
			);
	}
}
