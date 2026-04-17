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


require_once __DIR__.'/../common/testWidgets.php';

/**
 * @backup dashboard
 *
 * @onBefore prepareDashboardData
 */
class testDashboardTrafficLightWidget extends testWidgets {

	protected static $dashboardid;

	const GREEN_WIDGET_NAME = 'Traffic light green';
	const YELLOW_WIDGET_NAME = 'Traffic light yellow';
	const RED_WIDGET_NAME = 'Traffic light red';
	const NO_DATA_WIDGET_NAME = 'Traffic light no data';
	const INVALID_ORDER_WIDGET_NAME = 'Traffic light invalid order';
	const INVALID_PARSE_WIDGET_NAME = 'Traffic light invalid parse';

	public function prepareDashboardData() {
		CDataHelper::call('hostgroup.create', [
			[
				'name' => 'Host group for trafficlight widget'
			]
		]);
		$hostgrpid = CDataHelper::getIds('name');

		CDataHelper::call('host.create', [
			'host' => 'Host for trafficlight widget',
			'groups' => [
				['groupid' => $hostgrpid['Host group for trafficlight widget']]
			],
			'inventory_mode' => 0,
			'interfaces' => [
				'type' => 1,
				'main' => 1,
				'useip' => 1,
				'ip' => '192.168.3.217',
				'dns' => '',
				'port' => '10050'
			]
		]);
		$hostid = CDataHelper::getIds('host');
		$hostid = $hostid['Host for trafficlight widget'];

		$interfaceid = CDBHelper::getValue('SELECT interfaceid FROM interface WHERE hostid='.$hostid.' ORDER BY interfaceid LIMIT 1');

		$items_data = [
			[
				'hostid' => $hostid,
				'name' => 'Traffic light sensor green1',
				'key_' => 'trafficlight_green1',
				'type' => ITEM_TYPE_TRAPPER,
				'value_type' => ITEM_VALUE_TYPE_FLOAT,
				'interfaceid' => $interfaceid,
				'delay' => '1s'
			],
			[
				'hostid' => $hostid,
				'name' => 'Traffic light sensor green2',
				'key_' => 'trafficlight_green2',
				'type' => ITEM_TYPE_TRAPPER,
				'value_type' => ITEM_VALUE_TYPE_FLOAT,
				'interfaceid' => $interfaceid,
				'delay' => '1s'
			],
			[
				'hostid' => $hostid,
				'name' => 'Traffic light sensor yellow',
				'key_' => 'trafficlight_yellow',
				'type' => ITEM_TYPE_TRAPPER,
				'value_type' => ITEM_VALUE_TYPE_FLOAT,
				'interfaceid' => $interfaceid,
				'delay' => '1s'
			],
			[
				'hostid' => $hostid,
				'name' => 'Traffic light sensor red',
				'key_' => 'trafficlight_red',
				'type' => ITEM_TYPE_TRAPPER,
				'value_type' => ITEM_VALUE_TYPE_FLOAT,
				'interfaceid' => $interfaceid,
				'delay' => '1s'
			],
			[
				'hostid' => $hostid,
				'name' => 'Traffic light sensor missing1',
				'key_' => 'trafficlight_missing1',
				'type' => ITEM_TYPE_TRAPPER,
				'value_type' => ITEM_VALUE_TYPE_FLOAT,
				'interfaceid' => $interfaceid,
				'delay' => '1s'
			],
			[
				'hostid' => $hostid,
				'name' => 'Traffic light sensor missing2',
				'key_' => 'trafficlight_missing2',
				'type' => ITEM_TYPE_TRAPPER,
				'value_type' => ITEM_VALUE_TYPE_FLOAT,
				'interfaceid' => $interfaceid,
				'delay' => '1s'
			]
		];

		CDataHelper::call('item.create', $items_data);
		$itemids = CDataHelper::getIds('name');

		// Seed latest values:
		// yellow=10, red=20 (higher_is_worse).
		CDataHelper::addItemData($itemids['Traffic light sensor green1'], 5);
		CDataHelper::addItemData($itemids['Traffic light sensor green2'], 6);
		CDataHelper::addItemData($itemids['Traffic light sensor yellow'], 15);
		CDataHelper::addItemData($itemids['Traffic light sensor red'], 25);
		// missing sensors intentionally have no history data.

		CDataHelper::call('dashboard.create', [
			[
				'name' => 'Dashboard for trafficlight widget test',
				'auto_start' => 0,
				'pages' => [
					[
						'name' => 'Trafficlight widget page',
						'widgets' => [
							[
								'type' => 'trafficlight',
								'name' => self::GREEN_WIDGET_NAME,
								'x' => 0,
								'y' => 0,
								'width' => 6,
								'height' => 4,
								'fields' => [
									[
										'type' => 1,
										'name' => 'items.0',
										'value' => 'Traffic light sensor green1'
									],
									[
										'type' => 1,
										'name' => 'items.1',
										'value' => 'Traffic light sensor green2'
									],
									[
										'type' => 1,
										'name' => 'thresholds.0.color',
										'value' => 'FCCB1D'
									],
									[
										'type' => 1,
										'name' => 'thresholds.0.threshold',
										'value' => '10'
									],
									[
										'type' => 1,
										'name' => 'thresholds.1.color',
										'value' => 'E65660'
									],
									[
										'type' => 1,
										'name' => 'thresholds.1.threshold',
										'value' => '20'
									]
								]
							],
							[
								'type' => 'trafficlight',
								'name' => self::YELLOW_WIDGET_NAME,
								'x' => 6,
								'y' => 0,
								'width' => 6,
								'height' => 4,
								'fields' => [
									[
										'type' => 1,
										'name' => 'items.0',
										'value' => 'Traffic light sensor yellow'
									],
									[
										'type' => 1,
										'name' => 'items.1',
										'value' => 'Traffic light sensor green1'
									],
									[
										'type' => 1,
										'name' => 'thresholds.0.color',
										'value' => 'FCCB1D'
									],
									[
										'type' => 1,
										'name' => 'thresholds.0.threshold',
										'value' => '10'
									],
									[
										'type' => 1,
										'name' => 'thresholds.1.color',
										'value' => 'E65660'
									],
									[
										'type' => 1,
										'name' => 'thresholds.1.threshold',
										'value' => '20'
									]
								]
							],
							[
								'type' => 'trafficlight',
								'name' => self::RED_WIDGET_NAME,
								'x' => 12,
								'y' => 0,
								'width' => 6,
								'height' => 4,
								'fields' => [
									[
										'type' => 1,
										'name' => 'items.0',
										'value' => 'Traffic light sensor red'
									],
									[
										'type' => 1,
										'name' => 'items.1',
										'value' => 'Traffic light sensor green1'
									],
									[
										'type' => 1,
										'name' => 'thresholds.0.color',
										'value' => 'FCCB1D'
									],
									[
										'type' => 1,
										'name' => 'thresholds.0.threshold',
										'value' => '10'
									],
									[
										'type' => 1,
										'name' => 'thresholds.1.color',
										'value' => 'E65660'
									],
									[
										'type' => 1,
										'name' => 'thresholds.1.threshold',
										'value' => '20'
									]
								]
							],
							[
								'type' => 'trafficlight',
								'name' => self::NO_DATA_WIDGET_NAME,
								'x' => 18,
								'y' => 0,
								'width' => 6,
								'height' => 4,
								'fields' => [
									[
										'type' => 1,
										'name' => 'items.0',
										'value' => 'Traffic light sensor missing1'
									],
									[
										'type' => 1,
										'name' => 'items.1',
										'value' => 'Traffic light sensor missing2'
									],
									[
										'type' => 1,
										'name' => 'thresholds.0.color',
										'value' => 'FCCB1D'
									],
									[
										'type' => 1,
										'name' => 'thresholds.0.threshold',
										'value' => '10'
									],
									[
										'type' => 1,
										'name' => 'thresholds.1.color',
										'value' => 'E65660'
									],
									[
										'type' => 1,
										'name' => 'thresholds.1.threshold',
										'value' => '20'
									]
								]
							],
							[
								'type' => 'trafficlight',
								'name' => self::INVALID_ORDER_WIDGET_NAME,
								'x' => 24,
								'y' => 0,
								'width' => 6,
								'height' => 4,
								'fields' => [
									[
										'type' => 1,
										'name' => 'items.0',
										'value' => 'Traffic light sensor green1'
									],
									[
										'type' => 1,
										'name' => 'items.1',
										'value' => 'Traffic light sensor green2'
									],
									[
										'type' => 1,
										'name' => 'thresholds.0.color',
										'value' => 'FCCB1D'
									],
									[
										'type' => 1,
										'name' => 'thresholds.0.threshold',
										'value' => '20'
									],
									[
										'type' => 1,
										'name' => 'thresholds.1.color',
										'value' => 'E65660'
									],
									[
										'type' => 1,
										'name' => 'thresholds.1.threshold',
										'value' => '10'
									]
								]
							],
							[
								'type' => 'trafficlight',
								'name' => self::INVALID_PARSE_WIDGET_NAME,
								'x' => 30,
								'y' => 0,
								'width' => 6,
								'height' => 4,
								'fields' => [
									[
										'type' => 1,
										'name' => 'items.0',
										'value' => 'Traffic light sensor green1'
									],
									[
										'type' => 1,
										'name' => 'items.1',
										'value' => 'Traffic light sensor green2'
									],
									[
										'type' => 1,
										'name' => 'thresholds.0.color',
										'value' => 'FCCB1D'
									],
									[
										'type' => 1,
										'name' => 'thresholds.0.threshold',
										'value' => 'not-a-number'
									],
									[
										'type' => 1,
										'name' => 'thresholds.1.color',
										'value' => 'E65660'
									],
									[
										'type' => 1,
										'name' => 'thresholds.1.threshold',
										'value' => '20'
									]
								]
							]
						]
					]
				]
			]
		]);

		self::$dashboardid = CDataHelper::getIds('name')['Dashboard for trafficlight widget test'];
	}

	public function testDashboardTrafficLightWidget_States() {
		$this->page->login()->open('zabbix.php?action=dashboard.view&dashboardid='.self::$dashboardid)->waitUntilReady();

		$dashboard = CDashboardElement::find()->one();

		$green_widget = $dashboard->getWidget(self::GREEN_WIDGET_NAME);
		$green_widget->waitUntilReady();
		$green_root = $green_widget->getContent()->query('xpath:.//div[contains(@class,"trafficlight-widget")]')->one();
		$this->assertEquals('green', $green_root->getAttribute('data-state'));

		$active_lamps = $green_root->query('xpath:.//span[contains(@class,"is-active")]')->all()->count();
		$this->assertEquals(1, $active_lamps);

		$yellow_widget = $dashboard->getWidget(self::YELLOW_WIDGET_NAME);
		$yellow_widget->waitUntilReady();
		$yellow_root = $yellow_widget->getContent()->query('xpath:.//div[contains(@class,"trafficlight-widget")]')->one();
		$this->assertEquals('yellow', $yellow_root->getAttribute('data-state'));

		$red_widget = $dashboard->getWidget(self::RED_WIDGET_NAME);
		$red_widget->waitUntilReady();
		$red_root = $red_widget->getContent()->query('xpath:.//div[contains(@class,"trafficlight-widget")]')->one();
		$this->assertEquals('red', $red_root->getAttribute('data-state'));

		$no_data_widget = $dashboard->getWidget(self::NO_DATA_WIDGET_NAME);
		$no_data_widget->waitUntilReady();
		$no_data_message = $no_data_widget->getContent()->query('class:no-data-message')->one();
		$this->assertEquals('No data found', $no_data_message->getText());
		$this->assertFalse($no_data_widget->getContent()->query('class:trafficlight-widget')->one(false)->isValid());

		$invalid_order_widget = $dashboard->getWidget(self::INVALID_ORDER_WIDGET_NAME);
		$invalid_order_widget->waitUntilReady();
		$invalid_order_message = $invalid_order_widget->getContent()->query('class:no-data-message')->one();
		$this->assertEquals('Please update configuration', $invalid_order_message->getText());
		$this->assertFalse($invalid_order_widget->getContent()->query('class:trafficlight-widget')->one(false)->isValid());

		$invalid_parse_widget = $dashboard->getWidget(self::INVALID_PARSE_WIDGET_NAME);
		$invalid_parse_widget->waitUntilReady();
		$invalid_parse_message = $invalid_parse_widget->getContent()->query('class:no-data-message')->one();
		$this->assertEquals('Please update configuration', $invalid_parse_message->getText());
		$this->assertFalse($invalid_parse_widget->getContent()->query('class:trafficlight-widget')->one(false)->isValid());
	}
}
