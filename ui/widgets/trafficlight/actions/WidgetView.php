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


namespace Widgets\TrafficLight\Actions;

use API,
	CNumberParser,
	CControllerDashboardWidgetView,
	CControllerResponseData,
	CSettingsHelper,
	CUrl,
	Manager,
	Throwable;

use Widgets\TrafficLight\Widget;

class WidgetView extends CControllerDashboardWidgetView {

	protected function doAction(): void {
		$data = [
			'name' => $this->getInput('name', $this->widget->getDefaultName()),
			'user' => [
				'debug_mode' => $this->getDebugMode()
			]
		];

		$item = $this->getItem();

		if ($item === null) {
			$this->setResponse(new CControllerResponseData($data + [
				'error' => _('No permissions to referred object or it does not exist!')
			]));

			return;
		}

		$data['vars'] = [
			// Keep config names aligned with JS class.
			'yellowCutoff' => $this->parseNumberFieldValue('yellow_cutoff'),
			'greenCutoff' => $this->parseNumberFieldValue('green_cutoff')
		];

		$data['vars'] += $this->getValueData($item);

		$this->setResponse(new CControllerResponseData($data));
	}

	private function getItem(): ?array {
		$item_options = [
			'output' => ['itemid', 'hostid', 'name_resolved', 'value_type', 'units'],
			'selectHosts' => null,
			'filter' => [
				'value_type' => [ITEM_VALUE_TYPE_FLOAT, ITEM_VALUE_TYPE_UINT64]
			],
			'webitems' => true
		];

		if ($this->isTemplateDashboard()) {
			$item_options['itemids'] = $this->fields_values['itemid'];
		}
		else {
			$item_options['itemids'] = $this->fields_values['itemid'];
		}

		$items = API::Item()->get($item_options);

		if (!$items) {
			return null;
		}

		return $items[0];
	}

	private function parseNumberFieldValue(string $field_name): float {
		$number_parser = new CNumberParser([
			'with_size_suffix' => true,
			'with_time_suffix' => true
		]);

		$number_parser->parse($this->fields_values[$field_name]);

		return (float) $number_parser->calcValue();
	}

	private function getValueData(array $item): array {
		// Template dashboards do not have runtime history values.
		if ($this->isTemplateDashboard()) {
			return ['value' => null];
		}

		$history_period = timeUnitToSeconds(CSettingsHelper::get(CSettingsHelper::HISTORY_PERIOD));
		$history = Manager::History()->getLastValues([$item], 1, $history_period);

		if (!$history || !array_key_exists($item['itemid'], $history) || !isset($history[$item['itemid']][0])) {
			return ['value' => null];
		}

		$value = $history[$item['itemid']][0]['value'] ?? null;

		if ($value === null || $value === '') {
			return ['value' => null];
		}

		if (!is_numeric($value)) {
			// The widget is numeric-threshold-based; treat non-numeric/empty as no data.
			return ['value' => null];
		}

		return ['value' => (float) $value];
	}
}

