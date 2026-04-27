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


namespace Widgets\Emoji\Actions;

use API,
	CArrayHelper,
	CControllerDashboardWidgetView,
	CControllerResponseData,
	CSettingsHelper,
	Manager;

use Widgets\Emoji\Includes\EmojiWidgetHelper;
use Widgets\Emoji\Widget;

class WidgetView extends CControllerDashboardWidgetView {

	protected function doAction(): void {
		$item = $this->getItem();

		$data = [
			'name' => $this->getInput('name', $this->widget->getDefaultName()),
			'error' => null,
			'state' => 'fallback',
			'emoji' => (string) $this->fields_values['fallback_emoji'],
			'label' => trim((string) $this->fields_values['label']),
			'value' => null,
			'user' => [
				'debug_mode' => $this->getDebugMode()
			]
		];

		if ($item === null) {
			$data['error'] = _('No permissions to referred object or it does not exist!');
			$this->setResponse(new CControllerResponseData($data));

			return;
		}

		$data['item'] = $item;

		$value = $this->getItemValue($item);

		if ($value === null) {
			$data['state'] = 'no_data';
		}
		else {
			$data['value'] = $value;
			$threshold = EmojiWidgetHelper::selectThreshold($this->fields_values['thresholds'], $value,
				$this->isBinaryUnits($item)
			);

			if ($threshold !== null) {
				$data['state'] = 'matched';
				$data['emoji'] = $threshold['emoji'];
			}
		}

		$this->setResponse(new CControllerResponseData($data));
	}

	private function getItem(): ?array {
		$resolve_macros = !$this->isTemplateDashboard() || $this->fields_values['override_hostid'];

		$item_options = [
			'output' => ['itemid', 'hostid', $resolve_macros ? 'name_resolved' : 'name', 'history', 'trends',
				'value_type', 'units', 'key_'
			],
			'selectHosts' => !$this->isTemplateDashboard() ? ['name'] : null,
			'webitems' => true
		];

		if (!$this->isTemplateDashboard() && $this->fields_values['override_hostid']) {
			$src_items = API::Item()->get([
				'output' => ['key_'],
				'itemids' => $this->fields_values['itemid'],
				'webitems' => true
			]);

			if (!$src_items) {
				return null;
			}

			$item_options['hostids'] = $this->fields_values['override_hostid'];
			$item_options['filter']['key_'] = $src_items[0]['key_'];
		}
		else {
			$item_options['itemids'] = $this->fields_values['itemid'];
		}

		$items = API::Item()->get($item_options);

		if (!$items) {
			return null;
		}

		return $resolve_macros ? CArrayHelper::renameKeys($items[0], ['name_resolved' => 'name']) : $items[0];
	}

	private function getItemValue(array $item): ?float {
		if ($this->isTemplateDashboard() && !$this->fields_values['override_hostid']) {
			return null;
		}

		$history_period = timeUnitToSeconds(CSettingsHelper::get(CSettingsHelper::HISTORY_PERIOD));
		$history = Manager::History()->getLastValues([$item], 1, $history_period);

		if (!$history || !array_key_exists($item['itemid'], $history) || !$history[$item['itemid']]) {
			return null;
		}

		$value = $history[$item['itemid']][0]['value'];

		return is_numeric($value) ? (float) $value : null;
	}

	private function isBinaryUnits(array $item): bool {
		return array_key_exists('units', $item) && $item['units'] !== '' ? isBinaryUnits($item['units']) : false;
	}
}
