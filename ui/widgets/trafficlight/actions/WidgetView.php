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
	CItemHelper,
	CSettingsHelper,
	CTrafficLightWidgetHelper,
	CUrl,
	Manager;

class WidgetView extends CControllerDashboardWidgetView {

	protected function doAction(): void {
		$item = $this->getItem();

		$data = [
			'name' => $this->getName($item),
			'user' => [
				'debug_mode' => $this->getDebugMode()
			]
		];

		if ($item === null) {
			$data['traffic_light'] = $this->makeFallbackData(CTrafficLightWidgetHelper::STATE_INACCESSIBLE);
		}
		elseif (!CTrafficLightWidgetHelper::isSupportedItemValueType((int) $item['value_type'])) {
			$data['traffic_light'] = $this->makeFallbackData(CTrafficLightWidgetHelper::STATE_UNSUPPORTED, $item);
		}
		else {
			$latest_value = $this->getLatestValue($item);

			if ($latest_value === null) {
				$data['traffic_light'] = $this->makeFallbackData(CTrafficLightWidgetHelper::STATE_NO_DATA, $item);
			}
			else {
				$thresholds = CTrafficLightWidgetHelper::parseThresholds(
					$this->fields_values['yellow_threshold'],
					$this->fields_values['red_threshold'],
					isBinaryUnits($item['units'])
				);

				$data['traffic_light'] = [
					'state' => CTrafficLightWidgetHelper::evaluate(
						(float) $latest_value['value'],
						$thresholds['yellow'],
						$thresholds['red']
					),
					'is_fallback' => false,
					'label' => $this->getItemLabel($item),
					'value' => formatHistoryValue((string) $latest_value['value'], $item, false),
					'message' => '',
					'url' => $this->getHistoryUrl($item)
				];
			}
		}

		$this->setResponse(new CControllerResponseData($data));
	}

	private function getItem(): ?array {
		$resolve_macros = !$this->isTemplateDashboard();

		$items = API::Item()->get([
			'output' => ['itemid', $resolve_macros ? 'name_resolved' : 'name', 'value_type', 'units', 'history', 'trends'],
			'selectHosts' => !$this->isTemplateDashboard() ? ['name'] : null,
			'selectValueMap' => ['mappings'],
			'itemids' => $this->fields_values['itemid'],
			'webitems' => true
		]);

		if (!$items) {
			return null;
		}

		return $resolve_macros ? CArrayHelper::renameKeys($items[0], ['name_resolved' => 'name']) : $items[0];
	}

	private function getLatestValue(array $item): ?array {
		$history_period = timeUnitToSeconds(CSettingsHelper::get(CSettingsHelper::HISTORY_PERIOD));
		[$item] = CItemHelper::addDataSource([$item], time() - $history_period);

		if ($item['source'] === 'trends') {
			$data = Manager::History()->getAggregatedValues([$item], AGGREGATE_LAST, time() - $history_period);

			return $data ? reset($data) : null;
		}

		$history = Manager::History()->getLastValues([$item], 1, $history_period);

		if (!$history || !array_key_exists($item['itemid'], $history) || !$history[$item['itemid']]) {
			return null;
		}

		return $history[$item['itemid']][0];
	}

	private function getName(?array $item): string {
		if ($this->getInput('name', '') !== '') {
			return $this->getInput('name');
		}

		if ($item === null) {
			return $this->widget->getDefaultName();
		}

		return $this->getItemLabel($item);
	}

	private function getItemLabel(array $item): string {
		if ($this->isTemplateDashboard()) {
			return $item['name'];
		}

		return $item['hosts'][0]['name'].NAME_DELIMITER.$item['name'];
	}

	private function getHistoryUrl(array $item): string {
		return (new CUrl('history.php'))
			->setArgument('action', HISTORY_GRAPH)
			->setArgument('itemids[]', $item['itemid'])
			->getUrl();
	}

	private function makeFallbackData(string $state, ?array $item = null): array {
		$messages = [
			CTrafficLightWidgetHelper::STATE_INACCESSIBLE => _('No permissions to referred object or it does not exist!'),
			CTrafficLightWidgetHelper::STATE_UNSUPPORTED => _('Only numeric items are supported.'),
			CTrafficLightWidgetHelper::STATE_NO_DATA => _('No data')
		];

		return [
			'state' => $state,
			'is_fallback' => true,
			'label' => $item !== null ? $this->getItemLabel($item) : '',
			'value' => '',
			'message' => $messages[$state],
			'url' => ''
		];
	}
}
