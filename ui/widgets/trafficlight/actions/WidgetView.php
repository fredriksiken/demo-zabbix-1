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
	CControllerDashboardWidgetView,
	CControllerResponseData,
	CNumberParser,
	CSettingsHelper,
	Manager;

class WidgetView extends CControllerDashboardWidgetView {

	private const STATE_NO_DATA = 'no-data';
	private const STATE_GREEN = 'green';
	private const STATE_YELLOW = 'yellow';
	private const STATE_RED = 'red';

	protected function doAction(): void {
		$summary = $this->getSummary();

		$this->setResponse(new CControllerResponseData([
			'name' => $this->getInput('name', $this->widget->getDefaultName()),
			'state' => $summary['state'],
			'state_label' => $this->getStateLabel($summary['state']),
			'summary' => $summary,
			'user' => [
				'debug_mode' => $this->getDebugMode()
			]
		]));
	}

	private function getSummary(): array {
		if ($this->isTemplateDashboard() && !$this->fields_values['override_hostid']) {
			return $this->makeNoDataSummary();
		}

		$hostids = $this->getFilteredHostIds();

		if ($hostids === []) {
			return $this->makeNoDataSummary();
		}

		$items = $this->getItems($hostids);

		if ($items === []) {
			return $this->makeNoDataSummary();
		}

		$history = Manager::History()->getLastValues(
			$items,
			1,
			timeUnitToSeconds(CSettingsHelper::get(CSettingsHelper::HISTORY_PERIOD))
		);

		$counts = [
			self::STATE_GREEN => 0,
			self::STATE_YELLOW => 0,
			self::STATE_RED => 0
		];

		foreach ($items as $item) {
			if (!array_key_exists($item['itemid'], $history)) {
				continue;
			}

			$state = $this->classifyValue((float) $history[$item['itemid']][0]['value']);
			$counts[$state]++;
		}

		$items_with_data = array_sum($counts);

		if ($items_with_data === 0) {
			return $this->makeNoDataSummary(count($items));
		}

		return [
			'state' => $counts[self::STATE_RED] > 0
				? self::STATE_RED
				: ($counts[self::STATE_YELLOW] > 0 ? self::STATE_YELLOW : self::STATE_GREEN),
			'matched_items' => count($items),
			'items_with_data' => $items_with_data,
			'items_without_data' => count($items) - $items_with_data,
			'green_items' => $counts[self::STATE_GREEN],
			'yellow_items' => $counts[self::STATE_YELLOW],
			'red_items' => $counts[self::STATE_RED]
		];
	}

	private function getFilteredHostIds(): ?array {
		if ($this->isTemplateDashboard()) {
			if ($this->fields_values['maintenance'] == 1) {
				return $this->fields_values['override_hostid'];
			}

			$db_hosts = API::Host()->get([
				'output' => [],
				'hostids' => $this->fields_values['override_hostid'],
				'filter' => ['maintenance_status' => HOST_MAINTENANCE_STATUS_OFF],
				'monitored_hosts' => true,
				'preservekeys' => true
			]);

			return array_keys($db_hosts);
		}

		$hostids = $this->fields_values['hostids'] ?: null;
		$groupids = $this->fields_values['groupids'] ? getSubGroups($this->fields_values['groupids']) : null;
		$host_tags = $this->fields_values['host_tags'] ?: null;
		$filter = $this->fields_values['maintenance'] != 1
			? ['maintenance_status' => HOST_MAINTENANCE_STATUS_OFF]
			: null;

		if ($groupids === null && $hostids === null && $host_tags === null && $filter === null) {
			return null;
		}

		$db_hosts = API::Host()->get([
			'output' => [],
			'groupids' => $groupids,
			'hostids' => $hostids,
			'filter' => $filter,
			'evaltype' => $this->fields_values['evaltype_host'],
			'tags' => $host_tags,
			'inheritedTags' => true,
			'monitored_hosts' => true,
			'preservekeys' => true
		]);

		return array_keys($db_hosts);
	}

	private function getItems(?array $hostids): array {
		$search_field = $this->isTemplateDashboard() ? 'name' : 'name_resolved';

		$db_items = API::Item()->get([
			'output' => ['itemid', 'value_type'],
			'webitems' => true,
			'hostids' => $hostids,
			'evaltype' => $this->fields_values['evaltype_item'],
			'tags' => $this->fields_values['item_tags'] ?: null,
			'inheritedTags' => true,
			'searchWildcardsEnabled' => true,
			'searchByAny' => true,
			'search' => [
				$search_field => in_array('*', $this->fields_values['items'], true)
					? null
					: $this->fields_values['items']
			],
			'filter' => ['status' => ITEM_STATUS_ACTIVE],
			'monitored' => true
		]);

		return array_values(array_filter($db_items,
			static fn(array $item): bool => in_array($item['value_type'], [ITEM_VALUE_TYPE_FLOAT, ITEM_VALUE_TYPE_UINT64])
		));
	}

	private function classifyValue(float $value): string {
		$threshold_values = $this->getThresholdValues();

		if ($threshold_values === []) {
			return self::STATE_GREEN;
		}

		$yellow_threshold = $threshold_values[0];
		$red_threshold = count($threshold_values) > 1 ? end($threshold_values) : null;

		if ($red_threshold !== null && $value >= $red_threshold) {
			return self::STATE_RED;
		}

		if ($value >= $yellow_threshold) {
			return self::STATE_YELLOW;
		}

		return self::STATE_GREEN;
	}

	private function getThresholdValues(): array {
		$number_parser = new CNumberParser([
			'with_size_suffix' => true,
			'with_time_suffix' => true
		]);

		$threshold_values = [];

		foreach ($this->fields_values['thresholds'] as $threshold) {
			$number_parser->parse($threshold['threshold']);
			$threshold_values[] = $number_parser->calcValue();
		}

		sort($threshold_values, SORT_NUMERIC);

		return $threshold_values;
	}

	private function makeNoDataSummary(int $matched_items = 0): array {
		return [
			'state' => self::STATE_NO_DATA,
			'matched_items' => $matched_items,
			'items_with_data' => 0,
			'items_without_data' => $matched_items,
			'green_items' => 0,
			'yellow_items' => 0,
			'red_items' => 0
		];
	}

	private function getStateLabel(string $state): string {
		switch ($state) {
			case self::STATE_GREEN:
				return _('Green');

			case self::STATE_YELLOW:
				return _('Yellow');

			case self::STATE_RED:
				return _('Red');

			default:
				return _('No data');
		}
	}
}
