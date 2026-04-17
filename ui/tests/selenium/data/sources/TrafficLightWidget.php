<?php
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

class TrafficLightWidget {

	public static function load() {
		// The selenium smoke test may be rerun against an already seeded DB.
		// Ensure this fixture is idempotent by using unique host/dashboard names.
		$suffix = substr(hash('sha256', microtime(true).random_bytes(4)), 0, 10);
		$hostName = 'Host for traffic-light widget '.$suffix;
		$dashboardName = 'Dashboard for Traffic-light widget '.$suffix;

		$hosts = CDataHelper::createHosts([
			[
				'host' => $hostName,
				'groups' => [['groupid' => 4]], // Zabbix servers.
				'items' => [
					[
						'name' => 'Traffic-light float item',
						'key_' => 'trafficlight_float',
						'type' => ITEM_TYPE_TRAPPER,
						'value_type' => ITEM_VALUE_TYPE_FLOAT
					],
					[
						'name' => 'Traffic-light uint item 1',
						'key_' => 'trafficlight_uint_1',
						'type' => ITEM_TYPE_TRAPPER,
						'value_type' => ITEM_VALUE_TYPE_UINT64
					],
					[
						'name' => 'Traffic-light uint item 2',
						'key_' => 'trafficlight_uint_2',
						'type' => ITEM_TYPE_TRAPPER,
						'value_type' => ITEM_VALUE_TYPE_UINT64
					]
				]
			]
		]);

		$hostid = $hosts['hostids'][$hostName];
		$itemids = $hosts['itemids'];

		$float_itemid = $itemids[$hostName.':trafficlight_float'];
		$uint_itemid_1 = $itemids[$hostName.':trafficlight_uint_1'];
		$uint_itemid_2 = $itemids[$hostName.':trafficlight_uint_2'];

		// thresholds: yellow=10, red=20, higher_is_worse, inclusive boundaries.
		// - float item = 20 => red
		// - uint item 1 = 10 => yellow
		// - uint item 2 = 5 => green
		CDataHelper::addItemData($float_itemid, 20);
		CDataHelper::addItemData($uint_itemid_1, 10);
		CDataHelper::addItemData($uint_itemid_2, 5);

		$dashboardid = CDataHelper::call('dashboard.create', [
			[
				'name' => $dashboardName,
				'auto_start' => 0,
				'pages' => [
					[
						'name' => 'Traffic-light widget page',
						'widgets' => [
							[
								'type' => 'trafficlight',
								'name' => 'Traffic-light widget',
								'x' => 0,
								'y' => 0,
								'width' => 18,
								'height' => 4,
						'fields' => [
							[
								'type' => ZBX_WIDGET_FIELD_TYPE_HOST,
								'name' => 'hostids.0',
								'value' => $hostid
							],
							[
								'type' => ZBX_WIDGET_FIELD_TYPE_ITEM,
								'name' => 'items.0',
								'value' => $float_itemid
							],
							[
								'type' => ZBX_WIDGET_FIELD_TYPE_ITEM,
								'name' => 'items.1',
								'value' => $uint_itemid_1
							],
							[
								'type' => ZBX_WIDGET_FIELD_TYPE_ITEM,
								'name' => 'items.2',
								'value' => $uint_itemid_2
							],
							[
								'type' => ZBX_WIDGET_FIELD_TYPE_STR,
								'name' => 'yellow_threshold',
								'value' => '10'
							],
							[
								'type' => ZBX_WIDGET_FIELD_TYPE_STR,
								'name' => 'red_threshold',
								'value' => '20'
							]
						]
					]
				]
			]
				]
			]
		])['dashboardids'][0];

		return [
			'dashboardid' => $dashboardid,
			'hostid' => $hostid,
			'itemids' => [
				'float' => $float_itemid,
				'uint_1' => $uint_itemid_1,
				'uint_2' => $uint_itemid_2
			]
		];
	}
}
