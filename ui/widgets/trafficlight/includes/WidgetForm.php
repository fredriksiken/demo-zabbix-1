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


namespace Widgets\TrafficLight\Includes;

use API;

use Zabbix\Widgets\{
	CWidgetField,
	CWidgetForm
};

use Zabbix\Widgets\Fields\{
	CWidgetFieldCheckBox,
	CWidgetFieldMultiSelectGroup,
	CWidgetFieldMultiSelectHost,
	CWidgetFieldMultiSelectOverrideHost,
	CWidgetFieldPatternSelectItem,
	CWidgetFieldRadioButtonList,
	CWidgetFieldTags,
	CWidgetFieldThresholds
};

class WidgetForm extends CWidgetForm {

	private const SUPPORTED_ITEM_VALUE_TYPES = [ITEM_VALUE_TYPE_FLOAT, ITEM_VALUE_TYPE_UINT64];
	private const STATE_THRESHOLD_COLORS = ['FCCB1D', 'E65660'];
	private const DEFAULT_THRESHOLDS = [
		['color' => 'FCCB1D', 'threshold' => '10'],
		['color' => 'E65660', 'threshold' => '20']
	];

	public function validate(bool $strict = false): array {
		$errors = parent::validate($strict);

		if ($errors) {
			return $errors;
		}

		$thresholds = $this->getFieldValue('thresholds');

		if (count($thresholds) !== count(self::STATE_THRESHOLD_COLORS)) {
			$errors[] = _s('Invalid parameter "%1$s": %2$s.', _('Thresholds'),
				_('exactly two thresholds are required')
			);

			return $errors;
		}

		$threshold_colors = array_map('strtoupper', array_column($thresholds, 'color'));

		if ($threshold_colors !== self::STATE_THRESHOLD_COLORS) {
			$errors[] = _s('Invalid parameter "%1$s": %2$s.', _('Thresholds'),
				_('thresholds must define yellow then red states')
			);
		}

		if ($strict) {
			$errors = array_merge($errors, $this->validateSupportedItemTypes());
		}

		return $errors;
	}

	public function addFields(): self {
		return $this
			->addField($this->isTemplateDashboard()
				? new CWidgetFieldMultiSelectOverrideHost()
				: new CWidgetFieldMultiSelectGroup('groupids', _('Host groups'))
			)
			->addField($this->isTemplateDashboard()
				? null
				: new CWidgetFieldMultiSelectHost('hostids', _('Hosts'))
			)
			->addField($this->isTemplateDashboard()
				? null
				: (new CWidgetFieldRadioButtonList('evaltype_host', _('Host tags'), [
					TAG_EVAL_TYPE_AND_OR => _('And/Or'),
					TAG_EVAL_TYPE_OR => _('Or')
				]))->setDefault(TAG_EVAL_TYPE_AND_OR)
			)
			->addField($this->isTemplateDashboard()
				? null
				: new CWidgetFieldTags('host_tags')
			)
			->addField(
				(new CWidgetFieldPatternSelectItem('items', _('Item patterns')))
					->setFlags(CWidgetField::FLAG_NOT_EMPTY | CWidgetField::FLAG_LABEL_ASTERISK)
			)
			->addField(
				(new CWidgetFieldRadioButtonList('evaltype_item', _('Item tags'), [
					TAG_EVAL_TYPE_AND_OR => _('And/Or'),
					TAG_EVAL_TYPE_OR => _('Or')
				]))->setDefault(TAG_EVAL_TYPE_AND_OR)
			)
			->addField(
				new CWidgetFieldTags('item_tags')
			)
			->addField(
				new CWidgetFieldCheckBox('maintenance',
					$this->isTemplateDashboard() ? _('Show data in maintenance') : _('Show hosts in maintenance')
				)
			)
			->addField(
				(new CWidgetFieldThresholds('thresholds', _('Thresholds')))
					->setDefault(self::DEFAULT_THRESHOLDS)
			);
	}

	private function validateSupportedItemTypes(): array {
		$hostids = $this->getFilteredHostIds();

		if ($hostids === []) {
			return [];
		}

		$search_field = $this->isTemplateDashboard() ? 'name' : 'name_resolved';
		$db_items = API::Item()->get([
			'output' => ['value_type'],
			'webitems' => true,
			'hostids' => $hostids,
			'evaltype' => $this->getFieldValue('evaltype_item'),
			'tags' => $this->getFieldValue('item_tags') ?: null,
			'inheritedTags' => true,
			'searchWildcardsEnabled' => true,
			'searchByAny' => true,
			'search' => [
				$search_field => in_array('*', $this->getFieldValue('items'), true)
					? null
					: $this->getFieldValue('items')
			],
			'filter' => ['status' => ITEM_STATUS_ACTIVE],
			'monitored' => true
		]);

		foreach ($db_items as $item) {
			if (!in_array($item['value_type'], self::SUPPORTED_ITEM_VALUE_TYPES)) {
				return [_s('Invalid parameter "%1$s": %2$s.', _('Item patterns'),
					_('only numeric items are supported')
				)];
			}
		}

		return [];
	}

	private function getFilteredHostIds(): ?array {
		if ($this->isTemplateDashboard()) {
			if (!$this->getFieldValue('override_hostid')) {
				return [];
			}

			if ($this->getFieldValue('maintenance') == 1) {
				return $this->getFieldValue('override_hostid');
			}

			$db_hosts = API::Host()->get([
				'output' => [],
				'hostids' => $this->getFieldValue('override_hostid'),
				'filter' => ['maintenance_status' => HOST_MAINTENANCE_STATUS_OFF],
				'monitored_hosts' => true,
				'preservekeys' => true
			]);

			return array_keys($db_hosts);
		}

		$hostids = $this->getFieldValue('hostids') ?: null;
		$groupids = $this->getFieldValue('groupids')
			? getSubGroups($this->getFieldValue('groupids'))
			: null;
		$host_tags = $this->getFieldValue('host_tags') ?: null;
		$filter = $this->getFieldValue('maintenance') != 1
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
			'evaltype' => $this->getFieldValue('evaltype_host'),
			'tags' => $host_tags,
			'inheritedTags' => true,
			'monitored_hosts' => true,
			'preservekeys' => true
		]);

		return array_keys($db_hosts);
	}
}
