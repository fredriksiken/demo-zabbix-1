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


namespace Widgets\TrafficLight01\Includes;

use Zabbix\Widgets\{
	CWidgetForm
};

use Zabbix\Widgets\Fields\{
	CWidgetFieldCheckBox,
	CWidgetFieldSelect
};

use Widgets\TrafficLight01\Widget;

class WidgetForm extends CWidgetForm {

	public function addFields(): self {
		$this->addField(
			(new CWidgetFieldSelect('current_state', _('Current state'), [
				Widget::STATE_RED => Widget::getStateLabel(Widget::STATE_RED),
				Widget::STATE_YELLOW => Widget::getStateLabel(Widget::STATE_YELLOW),
				Widget::STATE_GREEN => Widget::getStateLabel(Widget::STATE_GREEN)
			]))
		);

		$this->addField(
			(new CWidgetFieldCheckBox('demo_mode', _('Demo mode')))->setDefault(1)
		);

		$this->addField(
			(new CWidgetFieldSelect('auto_interval_sec', _('Auto-cycle interval (seconds)'), [
				1 => _('1 second'),
				2 => _('2 seconds'),
				3 => _('3 seconds'),
				5 => _('5 seconds')
			]))->setDefault(2)
		);

		return $this;
	}

	public function validate(bool $strict = false): array {
		$errors = parent::validate($strict);

		if ($errors) {
			return $errors;
		}

		$current_state = (string) ($this->values['current_state'] ?? '');

		if (!in_array($current_state, Widget::STATES, true)) {
			$errors[] = _s('Invalid parameter "%1$s": %2$s.', _('Current state'), _('must be one of Red, Yellow, Green'));
		}

		return $errors;
	}
}
