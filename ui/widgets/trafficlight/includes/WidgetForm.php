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

use Zabbix\Widgets\CWidgetField;
use Zabbix\Widgets\CWidgetForm;
use Zabbix\Widgets\Fields\{
	CWidgetFieldCheckBox,
	CWidgetFieldMultiSelectGroup,
	CWidgetFieldMultiSelectHost,
	CWidgetFieldMultiSelectOverrideHost,
	CWidgetFieldRadioButtonList,
	CWidgetFieldTags,
	CWidgetFieldTextBox,
	CWidgetFieldMultiSelectItem
};

/**
 * Traffic light widget form.
 */
class WidgetForm extends CWidgetForm {

	public function addFields(): self {
		return $this
			->addField(
				$this->isTemplateDashboard()
					? new CWidgetFieldMultiSelectOverrideHost()
					: new CWidgetFieldMultiSelectGroup('groupids', _('Host groups'))
			)
			->addField(
				(new CWidgetFieldMultiSelectHost('hostids', _('Hosts')))
					->setDefault(
						$this->isTemplateDashboard()
							? [
								CWidgetField::FOREIGN_REFERENCE_KEY => CWidgetField::createTypedReference(
									CWidgetField::REFERENCE_DASHBOARD, CWidgetField::DATA_TYPE_HOST_IDS
								)
							]
							: []
					)
			)
			->addField(
				$this->isTemplateDashboard()
					? null
					: (new CWidgetFieldRadioButtonList('evaltype_host', _('Host tags'), [
						TAG_EVAL_TYPE_AND_OR => _('And/Or'),
						TAG_EVAL_TYPE_OR => _('Or')
					]))->setDefault(TAG_EVAL_TYPE_AND_OR)
			)
			->addField(
				$this->isTemplateDashboard()
					? null
					: new CWidgetFieldTags('host_tags')
			)
			->addField(
				(new CWidgetFieldMultiSelectItem('items', _('Items')))
					->setFlags(CWidgetField::FLAG_NOT_EMPTY | CWidgetField::FLAG_LABEL_ASTERISK)
			)
			->addField(
				new CWidgetFieldCheckBox('maintenance', $this->isTemplateDashboard()
					? _('Show data in maintenance')
					: _('Show hosts in maintenance')
				)
			)
			->addField(
				(new CWidgetFieldTextBox('yellow_threshold', _('Yellow threshold')))
			)
			->addField(
				(new CWidgetFieldTextBox('red_threshold', _('Red threshold')))
			);
	}

	public function validate(bool $strict = false): array {
		$errors = parent::validate($strict);

		if ($errors) {
			return $errors;
		}

		return [];
	}
}
