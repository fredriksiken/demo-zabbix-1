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
	CArrayHelper,
	CControllerDashboardWidgetView,
	CControllerResponseData,
	CSettingsHelper,
	CUrl,
	Manager;

use Widgets\TrafficLight\Widget;

class WidgetView extends CControllerDashboardWidgetView {

	protected function doAction(): void {
		$item = $this->getItem();

		if ($item === null) {
			$this->setResponse(new CControllerResponseData([
				'name' => $this->getInput('name', $this->widget->getDefaultName()),
				'body' => $this->renderState([
					'status' => Widget::STATUS_UNSUPPORTED,
					'state_label' => _('Unsupported'),
					'value_text' => _('No data'),
					'details' => _('The selected item cannot be resolved.')
				]),
				'user' => [
					'debug_mode' => $this->getDebugMode()
				]
			]));

			return;
		}

		$status_data = $this->getStatusData($item);

		$this->setResponse(new CControllerResponseData([
			'name' => $this->getInput('name', $this->widget->getDefaultName()),
			'body' => $this->renderState($status_data + ['item' => $item]),
			'user' => [
				'debug_mode' => $this->getDebugMode()
			]
		]));
	}

	private function getItem(): ?array {
		$db_items = API::Item()->get([
			'output' => ['itemid', 'hostid', 'name', 'name_resolved', 'value_type', 'units', 'status'],
			'selectHosts' => ['name'],
			'itemids' => $this->fields_values['itemid'],
			'webitems' => true
		]);

		if (!$db_items) {
			return null;
		}

		$item = reset($db_items);

		if (!$this->isTemplateDashboard()) {
			$item = CArrayHelper::renameKeys($item, ['name_resolved' => 'name']);
		}

		if (!in_array((int) $item['value_type'], [ITEM_VALUE_TYPE_FLOAT, ITEM_VALUE_TYPE_UINT64], true)) {
			return null;
		}

		return $item;
	}

	private function getStatusData(array $item): array {
		$warning_threshold = Widget::parseThresholdValue($this->fields_values['warning_threshold'],
			isBinaryUnits($item['units'])
		);
		$critical_threshold = Widget::parseThresholdValue($this->fields_values['critical_threshold'],
			isBinaryUnits($item['units'])
		);

		if ($warning_threshold === null || $critical_threshold === null
				|| !Widget::validateThresholdOrder($warning_threshold, $critical_threshold,
					(int) $this->fields_values['direction']
				)) {
			return [
				'status' => Widget::STATUS_INVALID_CONFIG,
				'state_label' => _('Invalid configuration'),
				'value_text' => null,
				'details' => _('The configured thresholds are invalid.')
			];
		}

		$history_period = timeUnitToSeconds(CSettingsHelper::get(CSettingsHelper::HISTORY_PERIOD));
		$history = Manager::History()->getLastValues([$item], 1, $history_period);

		if (!$history || !array_key_exists($item['itemid'], $history) || !$history[$item['itemid']]) {
			return [
				'status' => Widget::STATUS_NO_DATA,
				'state_label' => _('No data'),
				'value_text' => null,
				'details' => _('No latest value is available.')
			];
		}

		$value = (float) $history[$item['itemid']][0]['value'];
		$status = Widget::evaluateStatus($value, $warning_threshold, $critical_threshold,
			(int) $this->fields_values['direction']
		);
		$labels = formatHistoryValueRaw($value, $item, false, [
			'decimals' => 2,
			'decimals_exact' => false,
			'small_scientific' => false,
			'zero_as_zero' => false
		]);

		return [
			'status' => $status['status'],
			'state_label' => $this->getCustomLabel($status['status'], $status['state_label']),
			'value_text' => $labels['value'].($labels['units'] !== '' ? ' '.$labels['units'] : ''),
			'details' => _s('Warning: %1$s, critical: %2$s.', $this->fields_values['warning_threshold'],
				$this->fields_values['critical_threshold']
			)
		];
	}

	private function getCustomLabel(string $status, string $fallback_label): string {
		return match ($status) {
			Widget::STATUS_GREEN => $this->fields_values['green_label'] !== ''
				? $this->fields_values['green_label']
				: $fallback_label,
			Widget::STATUS_YELLOW => $this->fields_values['yellow_label'] !== ''
				? $this->fields_values['yellow_label']
				: $fallback_label,
			Widget::STATUS_RED => $this->fields_values['red_label'] !== ''
				? $this->fields_values['red_label']
				: $fallback_label,
			Widget::STATUS_NO_DATA => $this->fields_values['no_data_label'] !== ''
				? $this->fields_values['no_data_label']
				: $fallback_label,
			default => $fallback_label
		};
	}

	private function renderState(array $data): string {
		$status = $data['status'];
		$color = match ($status) {
			Widget::STATUS_GREEN => $this->fields_values['green_color'],
			Widget::STATUS_YELLOW => $this->fields_values['yellow_color'],
			Widget::STATUS_RED => $this->fields_values['red_color'],
			Widget::STATUS_NO_DATA => $this->fields_values['no_data_color'],
			default => $this->fields_values['no_data_color']
		};

		$details = $data['details'] ?? '';
		$value_text = $data['value_text'] ?? '';

		$summary = [
			(new CDiv($data['state_label']))->addClass('traffic-light-summary__label'),
			$value_text !== '' ? (new CDiv($value_text))->addClass('traffic-light-summary__value') : null,
			$details !== '' ? (new CDiv($details))->addClass('traffic-light-summary__details') : null
		];

		$summary = array_values(array_filter($summary));

		return (new CDiv([
			(new CDiv())->addClass('traffic-light-summary__dot')
				->addStyle('background-color: #'.$color.';'),
			(new CDiv($summary))->addClass('traffic-light-summary__content')
		]))
			->addClass('traffic-light-summary')
			->addClass('traffic-light-summary--'.$status)
			->setAttribute('role', 'status')
			->setAttribute('aria-label', trim($data['state_label'].' '.$value_text.' '.$details))
			->toString();
	}
}
