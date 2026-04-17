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
	CNumberParser,
	CSettingsHelper,
	Manager;

use Widgets\TrafficLight\Includes\WidgetForm;
use Widgets\TrafficLight\Widget;

class WidgetView extends CControllerDashboardWidgetView {

	private const COVER_MESSAGE_NO_DATA = 'No data found';
	private const COVER_MESSAGE_INVALID_CONFIG = 'Please update configuration';

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

		$items = $this->getMatchedItems();
		$thresholds = $this->getThresholdsOrNull();

		if (!$items || $thresholds === null) {
			$data['state'] = 'no-data';
			$data['state_label'] = _('No data');
			$data['cover_message'] = !$items ? self::COVER_MESSAGE_NO_DATA : self::COVER_MESSAGE_INVALID_CONFIG;
			$data['summary'] = [
				'matched_items' => 0,
				'items_with_data' => 0,
				'items_without_data' => 0,
				'green_items' => 0,
				'yellow_items' => 0,
				'red_items' => 0
			];

			$this->setResponse(new CControllerResponseData($data));

			return;
		}

		[$yellow_threshold, $red_threshold] = $thresholds;

		$history_period = timeUnitToSeconds(CSettingsHelper::get(CSettingsHelper::HISTORY_PERIOD));

		$matched_items = count($items);
		$items_with_data = 0;
		$state_counts = [
			'green' => 0,
			'yellow' => 0,
			'red' => 0
		];

		if ($matched_items === 0) {
			$data['state'] = 'no-data';
			$data['state_label'] = _('No data');
			$data['cover_message'] = self::COVER_MESSAGE_NO_DATA;
			$data['summary'] = [
				'matched_items' => 0,
				'items_with_data' => 0,
				'items_without_data' => 0,
				'green_items' => 0,
				'yellow_items' => 0,
				'red_items' => 0
			];

			$this->setResponse(new CControllerResponseData($data));

			return;
		}

		$length = ZBX_HINTBOX_CONTENT_LIMIT + 1;
		$db_history = Manager::History()->getLastValues($items, 1, $history_period, $length);

		foreach ($items as $item) {
			if (!array_key_exists($item['itemid'], $db_history)) {
				continue;
			}

			$items_with_data++;
			$value = (float) $db_history[$item['itemid']][0]['value'];

			if ($value >= $red_threshold) {
				$state_counts['red']++;
			}
			elseif ($value >= $yellow_threshold) {
				$state_counts['yellow']++;
			}
			else {
				$state_counts['green']++;
			}
		}

		if ($items_with_data === 0) {
			$data['state'] = 'no-data';
			$data['state_label'] = _('No data');
			$data['cover_message'] = self::COVER_MESSAGE_NO_DATA;
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
		$state = $state_counts['red'] > 0 ? 'red'
			: ($state_counts['yellow'] > 0 ? 'yellow' : 'green');

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
				'status' => ITEM_STATUS_ACTIVE,
				'value_type' => [ITEM_VALUE_TYPE_FLOAT, ITEM_VALUE_TYPE_UINT64]
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
		$thresholds = $this->fields_values['thresholds'] ?? [];

		if (count($thresholds) !== 2) {
			return null;
		}

		$number_parser = new CNumberParser([
			'with_size_suffix' => true,
			'with_time_suffix' => true,
			'is_binary_size' => false
		]);

		$yellow_raw = $thresholds[0]['threshold'] ?? null;
		$red_raw = $thresholds[1]['threshold'] ?? null;

		$yellow_parsed = $yellow_raw !== null && $number_parser->parse($yellow_raw) === CParser::PARSE_SUCCESS
			? (float) $number_parser->calcValue()
			: null;
		$red_parsed = $red_raw !== null && $number_parser->parse($red_raw) === CParser::PARSE_SUCCESS
			? (float) $number_parser->calcValue()
			: null;

		if ($yellow_parsed === null || $red_parsed === null) {
			return null;
		}

		// MVP contract: red_threshold >= yellow_threshold (inclusive), with direction fixed to higher_is_worse.
		if ($red_parsed < $yellow_parsed) {
			return null;
		}

		return [$yellow_parsed, $red_parsed];
	}
}
