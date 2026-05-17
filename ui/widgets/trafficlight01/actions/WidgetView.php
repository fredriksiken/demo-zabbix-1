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


namespace Widgets\TrafficLight01\Actions;

use CControllerDashboardWidgetView;
use CControllerResponseData;

use Widgets\TrafficLight01\Widget;

class WidgetView extends CControllerDashboardWidgetView {

	protected function doAction(): void {
		$config_defaults = [
			'name' => $this->widget->getDefaultName(),
			'current_state' => Widget::DEFAULT_STATE,
			'demo_mode' => 1,
			'auto_interval_sec' => 2,
			'valid' => true
		];

		$current_state = (string) ($this->fields_values['current_state'] ?? $config_defaults['current_state']);
		$demo_mode = (int) ($this->fields_values['demo_mode'] ?? $config_defaults['demo_mode']);
		$auto_interval_sec = (int) ($this->fields_values['auto_interval_sec'] ?? $config_defaults['auto_interval_sec']);

		if (!in_array($current_state, Widget::STATES, true)) {
			$current_state = $config_defaults['current_state'];
			$config_defaults['valid'] = false;
		}

		if ($auto_interval_sec < 1 || $auto_interval_sec > 60) {
			$auto_interval_sec = $config_defaults['auto_interval_sec'];
			$config_defaults['valid'] = false;
		}

		$this->setResponse(new CControllerResponseData([
			'name' => $this->getInput('name', $config_defaults['name']),
			'current_state' => $current_state,
			'demo_mode' => $demo_mode,
			'auto_interval_ms' => $auto_interval_sec * 1000,
			'valid' => (bool) $config_defaults['valid'],
			'user' => [
				'debug_mode' => $this->getDebugMode()
			]
		]));
	}
}
