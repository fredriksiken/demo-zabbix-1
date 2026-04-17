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
	CParser,
	CSettingsHelper,
	Manager;

use Widgets\TrafficLight\Includes\WidgetForm;
use Widgets\TrafficLight\Includes\TrafficLightLogic;
use Widgets\TrafficLight\Widget;

class WidgetView extends CControllerDashboardWidgetView {

	protected function init(): void {
		parent::init();

		$this->addValidationRules([
			'with_config' => 'in 1'
		]);
	}

	protected function doAction(): void {
		$data = [
			'name' => $this->getInput('name', $this->widget->getDefaultName()),
			'user' => [
				'debug_mode' => $this->getDebugMode()
			]
		];

		// Pre-validation inputs used for deterministic cover precedence.
		$has_selection = !empty($this->fields_values['items'] ?? []);

		$items = $this->getMatchedItems();
		$thresholds = $this->getThresholdsOrNull();

		$unsupported_items_present = false;
		if ($items) {
			foreach ($items as $item) {
				if (!TrafficLightLogic::isSupportedValueType((int) $item['value_type'])) {
					$unsupported_items_present = true;
					break;
				}
			}
		}

		if ($thresholds === null || !$has_selection || !$items || $unsupported_items_present) {
			$data['state'] = 'no-data';
			$data['state_label'] = _('No data');
			$data['cover_message'] = TrafficLightLogic::decideCoverMessage(
				$has_selection,
				$thresholds,
				$unsupported_items_present
			);

			$matched_items = count($items);
			$data['summary'] = [
				'matched_items' => $matched_items,
				'items_with_data' => 0,
				'items_without_data' => $matched_items,
				'green_items' => 0,
				'yellow_items' => 0,
				'red_items' => 0
			];

			$this->setResponse(new CControllerResponseData($data));

			return;
		}

		[$yellow_threshold, $red_threshold] = $thresholds;

		$history_period = timeUnitToSeconds(CSettingsHelper::get(CSettingsHelper::HISTORY_PERIOD));

		$items_with_data = 0;
		$state_counts = [
			'green' => 0,
			'yellow' => 0,
			'red' => 0
		];

		$length = ZBX_HINTBOX_CONTENT_LIMIT + 1;
		$matched_items = count($items);
		$db_history = Manager::History()->getLastValues($items, 1, $history_period, $length);

		foreach ($items as $item) {
			if (!array_key_exists($item['itemid'], $db_history)) {
				continue;
			}

			$items_with_data++;
			$value = (float) $db_history[$item['itemid']][0]['value'];

			$state = TrafficLightLogic::classifyValue($value, $yellow_threshold, $red_threshold);

			$state_counts[$state]++;
		}

		if ($items_with_data === 0) {
			$data['state'] = 'no-data';
			$data['state_label'] = _('No data');
			$data['cover_message'] = TrafficLightLogic::COVER_MESSAGE_NO_DATA;
			$data['summary'] = [
				'matched_items' => $matched_items,
				'items_with_data' => 0,
				'items_without_data' => $matched_items,
				'green_items' => 0,
				'yellow_items' => 0,
				'red_items' => 0
			];

			$this->setResponse(new CControllerResponseData($data));

			return;
		}

		// MVP semantics: worst-state-wins (any red => red, else any yellow => yellow, else green).
		$state = TrafficLightLogic::reduceWorstState($state_counts['green'], $state_counts['yellow'], $state_counts['red']);

		$data['state'] = $state;
		$data['state_label'] = _s('Traffic light %1$s', $state);
		$data['summary'] = [
			'matched_items' => $matched_items,
			'items_with_data' => $items_with_data,
			'items_without_data' => $matched_items - $items_with_data,
			'green_items' => $state_counts['green'],
			'yellow_items' => $state_counts['yellow'],
			'red_items' => $state_counts['red']
		];

		$this->setResponse(new CControllerResponseData($data));
	}

	/**
	 * @return array<int, array{itemid: string|int}>
	 */
	private function getMatchedItems(): array {
		$patterns = $this->fields_values['items'] ?? [];

		if (!$patterns) {
			return [];
		}

		$search_field = $this->isTemplateDashboard() ? 'name' : 'name_resolved';

		$item_options = [
			'output' => ['itemid', 'hostid', 'value_type', 'units', 'name_resolved', 'key_'],
			'webitems' => true,
			'filter' => [
				'status' => ITEM_STATUS_ACTIVE
			],
			'selectHosts' => ['name'],
			'searchWildcardsEnabled' => true,
			'searchByAny' => true,
			'search' => [
				$search_field => in_array('*', $patterns, true) ? null : $patterns
			]
		];

		$items = API::Item()->get($item_options);

		if (!$items) {
			return [];
		}

		return CArrayHelper::renameObjectsKeys($items, ['name_resolved' => 'name_resolved']);
	}

	/**
	 * @return array{float, float}|null array(yellow_threshold, red_threshold) or null on invalid config
	 */
	private function getThresholdsOrNull(): ?array {
		return TrafficLightLogic::parseThresholds($this->fields_values['thresholds'] ?? []);
	}
}
