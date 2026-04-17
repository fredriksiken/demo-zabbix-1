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

use API;
use CArrayHelper;
use CControllerResponseData;
use CControllerDashboardWidgetView;
use CItemGeneral;
use CMacrosResolverHelper;
use CNumberParser;
use CSettingsHelper;
use CWidget;
use CWidgetForm;
use CWidgetsData;
use CController;
use CParser;
use CTableInfo;
use CWidgetView;
use Widgets\TrafficLight\Includes\TrafficLightNumeric;
use Manager;
use Widgets\TrafficLight\Includes\WidgetForm;
use Zabbix\Core\CWidget as CoreCWidget;

class WidgetView extends CControllerDashboardWidgetView {

	private const LAMP_STATES = ['green', 'yellow', 'red'];

	protected function doAction(): void {
		$data = [
			'name' => $this->getInput('name', $this->widget->getDefaultName()),
			'user' => [
				'debug_mode' => $this->getDebugMode()
			]
		];

		$cover = null;

		// Direction is a fixed invariant (MVP: higher_is_worse). If a client tries to override it via payload,
		// treat it as invalid configuration and fall back to the standard widget cover UI.
		$raw_fields = $this->getInput('fields', []);
		if (is_array($raw_fields)) {
			$direction_override = array_key_exists('direction', $raw_fields);

			if (!$direction_override) {
				foreach ($raw_fields as $field) {
					if (is_array($field) && array_key_exists('name', $field) && $field['name'] === 'direction') {
						$direction_override = true;
						break;
					}
				}
			}

			if ($direction_override) {
				$cover = $this->makeCoverInvalidConfiguration();
			}
		}

		if ($cover === null) {
			$number_parser = new CNumberParser([
				'with_size_suffix' => true,
				'with_time_suffix' => true
			]);

			$number_parser->parse((string) ($this->fields_values['yellow_threshold'] ?? ''));
			$yellow_threshold_value = $number_parser->calcValue();

			$number_parser->parse((string) ($this->fields_values['red_threshold'] ?? ''));
			$red_threshold_value = $number_parser->calcValue();

			if (!is_numeric($yellow_threshold_value) || !is_numeric($red_threshold_value) || $red_threshold_value < $yellow_threshold_value) {
				$cover = $this->makeCoverInvalidThresholdConfiguration();
			}
		}

		$itemids = $this->fields_values['items'] ?? [];
		if ($cover === null && (!is_array($itemids) || $itemids === [])) {
			$cover = $this->makeCoverEmptySelection();
		}

		if ($cover === null) {
			$all_items = $this->getItemsByIds($itemids, false);
			$numeric_items = $this->getItemsByIds($itemids, true);

			// Any unsupported/inaccessible selection must produce a standard unsupported-items cover.
			$matched_all = count($all_items);
			$matched_numeric = count($numeric_items);
			$total_selected = count($itemids);

			if ($matched_all !== $total_selected || $matched_numeric !== $total_selected) {
				$cover = $this->makeCoverUnsupportedItems();
			}
		}

		if ($cover === null) {
			[$state, $summary, $state_label] = $this->evaluate($numeric_items, $yellow_threshold_value, $red_threshold_value);

			if ($state === null) {
				$cover = $this->makeCoverNoData($summary['items_without_data'] ?? 0);
			}

			$data['state'] = $state ?? 'green';
			$data['state_label'] = $state_label ?? '';
			$data['summary'] = $summary ?? [];
		}

		$data['cover'] = $cover;

		$this->setResponse(new CControllerResponseData($data));
	}

	/**
	 * Fetch widget input items by explicit item IDs.
	 *
	 * @param array $itemids list of item IDs
	 * @param bool $numeric_only if true, fetch only float/unsigned-integer items
	 *
	 * @return array itemid => item arrays (API return format)
	 */
	private function getItemsByIds(array $itemids, bool $numeric_only): array {
		if ($itemids === []) {
			return [];
		}

		$options = [
			'output' => ['itemid', 'hostid', 'value_type', 'name_resolved', 'key_'],
			'itemids' => $itemids,
			'monitored' => true,
			'webitems' => true,
			'preservekeys' => true,
			'filter' => [
				'status' => ITEM_STATUS_ACTIVE,
				'value_type' => $numeric_only ? [ITEM_VALUE_TYPE_FLOAT, ITEM_VALUE_TYPE_UINT64] : null
			]
		];

		return API::Item()->get($options);
	}

	/**
	 * Fetch widget input items matched by patterns/tags/host constraints.
	 *
	 * @param bool $numeric_only if true, fetch only float/unsigned-integer items.
	 *
	 * @return array itemid => item arrays (API return format)
	 */
	private function getItems(bool $numeric_only): array {
		$hostids = $this->getHostIds();

		$search_field = $this->isTemplateDashboard() ? 'name' : 'name_resolved';
		$patterns = $this->fields_values['items'] ?? [];

		$options = [
			'output' => ['itemid', 'hostid', 'value_type', 'name_resolved', 'key_'],
			'monitored' => true,
			'webitems' => true,
			'preservekeys' => true,
			'searchWildcardsEnabled' => true,
			'searchByAny' => true,
			'search' => [
				$search_field => in_array('*', $patterns, true) ? null : $patterns
			],
			'filter' => [
				'status' => ITEM_STATUS_ACTIVE,
				'value_type' => $numeric_only ? [ITEM_VALUE_TYPE_FLOAT, ITEM_VALUE_TYPE_UINT64] : null
			]
		];

		if ($hostids !== null) {
			$options['hostids'] = $hostids;
		}

		if ($this->fields_values['item_tags'] ?? null) {
			$options['tags'] = $this->fields_values['item_tags'];
			$options['evaltype'] = $this->fields_values['evaltype_item'] ?? TAG_EVAL_TYPE_AND_OR;
			$options['inheritedTags'] = true;
		}

		return API::Item()->get($options);
	}

	private function getHostIds(): ?array {
		if ($this->isTemplateDashboard()) {
			$override_hostid = $this->fields_values['override_hostid'] ?? [];

			return $override_hostid ?: null;
		}

		$groupids = $this->fields_values['groupids'] ? getSubGroups($this->fields_values['groupids']) : null;
		$hostids = $this->fields_values['hostids'] ?: null;
		$evaltype = $this->fields_values['evaltype_host'] ?? TAG_EVAL_TYPE_AND_OR;
		$tags = $this->fields_values['host_tags'] ?: null;

		$filter = ($this->fields_values['maintenance'] ?? 0) != 1
			? ['maintenance_status' => HOST_MAINTENANCE_STATUS_OFF]
			: null;

		if ($groupids !== null || $hostids !== null || $tags !== null || $filter !== null) {
			$db_hosts = API::Host()->get([
				'output' => [],
				'groupids' => $groupids,
				'hostids' => $hostids,
				'filter' => $filter,
				'evaltype' => $evaltype,
				'tags' => $tags,
				'inheritedTags' => true,
				'monitored_hosts' => true,
				'preservekeys' => true
			]);

			if (!$db_hosts) {
				return null;
			}

			return array_keys($db_hosts);
		}

		return null;
	}

	/**
	 * Evaluate latest values into a per-item + aggregate state.
	 *
	 * @return array{0: string|null, 1: array, 2: string|null}
	 */
	private function evaluate(array $items, $yellow_threshold_value, $red_threshold_value): array {
		$matched_items = count($items);

		if (!$items) {
			return [null, [
				'matched_items' => 0,
				'items_with_data' => 0,
				'items_without_data' => 0,
				'green_items' => 0,
				'yellow_items' => 0,
				'red_items' => 0
			], null];
		}

		$history_period = timeUnitToSeconds(CSettingsHelper::get(CSettingsHelper::HISTORY_PERIOD));
		$length = ZBX_HINTBOX_CONTENT_LIMIT + 1;

		$db_history = Manager::History()->getLastValues(array_values($items), 1, $history_period, $length);

		$number_parser = new CNumberParser([
			'with_size_suffix' => true,
			'with_time_suffix' => true
		]);

		$items_evaluated = [];

		foreach (array_values($items) as $item) {
			$itemid = $item['itemid'];

			// No latest history value => no-data.
			if (!array_key_exists($itemid, $db_history) || $db_history[$itemid] === []) {
				$items_evaluated[] = [
					'supported' => true,
					'value' => null
				];
				continue;
			}

			$value = $db_history[$itemid][0]['value'];

			// Parsing failures are treated as no-data (prevents false green state).
			if ($number_parser->parse((string) $value) !== CParser::PARSE_SUCCESS) {
				$items_evaluated[] = [
					'supported' => true,
					'value' => null
				];
				continue;
			}

			$items_evaluated[] = [
				'supported' => true,
				'value' => (float) $number_parser->calcValue()
			];
		}

		return TrafficLightNumeric::evaluateItems($items_evaluated, (float) $yellow_threshold_value, (float) $red_threshold_value);
	}

	private function makeCoverNotConfigured(): array {
		return [
			'message' => _('Widget is not fully configured'),
			'description' => _('Please update configuration'),
			'icon' => ZBX_ICON_WIDGET_NOT_CONFIGURED_LARGE
		];
	}

	private function makeCoverEmptySelection(): array {
		return [
			'message' => _('Widget is not fully configured'),
			'description' => _('Please update configuration'),
			'icon' => ZBX_ICON_WIDGET_EMPTY_REFERENCES_LARGE
		];
	}

	private function makeCoverUnsupportedItems(): array {
		return [
			'message' => _('Unsupported items selected'),
			'description' => _('Please select numeric float or unsigned integer items'),
			'icon' => ZBX_ICON_SEARCH_LARGE
		];
	}

	private function makeCoverInvalidConfiguration(): array {
		return [
			'message' => _('Widget is not fully configured'),
			'description' => _('Invalid configuration: direction is fixed to higher_is_worse.'),
			'icon' => ZBX_ICON_WIDGET_NOT_CONFIGURED_LARGE
		];
	}

	private function makeCoverInvalidThresholdConfiguration(): array {
		return [
			'message' => _('Widget is not fully configured'),
			'description' => _('Invalid configuration: thresholds must be valid numbers and require red_threshold >= yellow_threshold (higher is worse).'),
			'icon' => ZBX_ICON_WIDGET_NOT_CONFIGURED_LARGE
		];
	}

	private function makeCoverNoData(int $items_without_data): array {
		return [
			'message' => _('No data found'),
			'description' => $items_without_data > 0
				? _('Latest values are unavailable for the configured items')
				: null,
			'icon' => ZBX_ICON_SEARCH_LARGE
		];
	}
}
