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


require_once __DIR__.'/../../include/CWebTest.php';
require_once __DIR__.'/../behaviors/CMessageBehavior.php';
require_once __DIR__.'/../common/testWidgets.php';

/**
 * @backup dashboard
 *
 * @dataSource AllItemValueTypes
 *
 * @onBefore prepareData
 */
class testDashboardTrafficLightWidget extends testWidgets {

	private const DASHBOARD_NAME = 'Traffic light widget dashboard';
	private const ACTIVE_WIDGET = 'Traffic light active';
	private const UNSUPPORTED_WIDGET = 'Traffic light unsupported';
	private const NO_DATA_WIDGET = 'Traffic light no data';
	private const MISSING_WIDGET = 'Traffic light missing';

	private const GREEN_VALUE = 5;
	private const YELLOW_VALUE = 10;
	private const RED_VALUE = 20;

	private const DEFAULT_YELLOW_THRESHOLD = 50;
	private const DEFAULT_RED_THRESHOLD = 80;

	private static $dashboardid;
	private static $hostid;
	private static $itemids;
	private static $no_data_itemid;
	private static $deleted_itemid;

	public function getBehaviors() {
		return [CMessageBehavior::class];
	}

	public static function prepareData(): void {
		self::$hostid = CDataHelper::get('AllItemValueTypes.hostid');
		self::$itemids = CDataHelper::get('AllItemValueTypes.itemids');

		$items = CDataHelper::call('item.create', [
			[
				'hostid' => self::$hostid,
				'name' => 'Traffic light no data item',
				'key_' => 'traffic.light.no.data',
				'type' => ITEM_TYPE_TRAPPER,
				'value_type' => ITEM_VALUE_TYPE_FLOAT
			],
			[
				'hostid' => self::$hostid,
				'name' => 'Traffic light deleted item',
				'key_' => 'traffic.light.deleted',
				'type' => ITEM_TYPE_TRAPPER,
				'value_type' => ITEM_VALUE_TYPE_FLOAT
			]
		]);

		self::$no_data_itemid = $items['itemids'][0];
		self::$deleted_itemid = $items['itemids'][1];

		CDataHelper::addItemData(self::$itemids['Float item'], self::GREEN_VALUE);

		$dashboard = CDataHelper::call('dashboard.create', [
			[
				'name' => self::DASHBOARD_NAME,
				'auto_start' => 0,
				'pages' => [
					[
						'name' => 'Traffic light widget page',
						'widgets' => [
							self::getWidgetConfig(self::ACTIVE_WIDGET, self::$itemids['Float item'], 0, 0),
							self::getWidgetConfig(self::UNSUPPORTED_WIDGET, self::$itemids['Character item'], 8, 0),
							self::getWidgetConfig(self::NO_DATA_WIDGET, self::$no_data_itemid, 16, 0),
							self::getWidgetConfig(self::MISSING_WIDGET, self::$deleted_itemid, 24, 0)
						]
					]
				]
			]
		]);

		self::$dashboardid = $dashboard['dashboardids'][0];

		CDataHelper::call('item.delete', [self::$deleted_itemid]);
	}

	private static function getWidgetConfig(string $name, string $itemid, int $x, int $y): array {
		return [
			'type' => 'trafficlight',
			'name' => $name,
			'x' => $x,
			'y' => $y,
			'width' => 8,
			'height' => 4,
			'fields' => [
				[
					'type' => ZBX_WIDGET_FIELD_TYPE_ITEM,
					'name' => 'itemid',
					'value' => $itemid
				],
				[
					'type' => ZBX_WIDGET_FIELD_TYPE_STR,
					'name' => 'yellow_threshold',
					'value' => (string) self::YELLOW_VALUE
				],
				[
					'type' => ZBX_WIDGET_FIELD_TYPE_STR,
					'name' => 'red_threshold',
					'value' => (string) self::RED_VALUE
				]
			]
		];
	}

	public function testDashboardTrafficLightWidget_FormLayout(): void {
		$this->page->login()->open('zabbix.php?action=dashboard.view&dashboardid='.self::$dashboardid)->waitUntilReady();

		$form = CDashboardElement::find()->one()->edit()->addWidget()->asForm();
		$form->fill(['Type' => CFormElement::RELOADABLE_FILL('Traffic light')]);

		$form->checkValue([
			'Name' => '',
			'Refresh interval' => 'Default (1 minute)',
			'Item' => '',
			'Show header' => true,
			'Yellow threshold' => self::DEFAULT_YELLOW_THRESHOLD,
			'Red threshold' => self::DEFAULT_RED_THRESHOLD
		]);
	}

	public static function getValidationData(): array {
		return [
			'item is required' => [[
				'fields' => [
					'Item' => '',
					'Yellow threshold' => self::YELLOW_VALUE,
					'Red threshold' => self::RED_VALUE
				],
				'error' => ['Invalid parameter "Item": cannot be empty.']
			]],
			'thresholds must be numeric' => [[
				'fields' => [
					'Item' => 'Float item',
					'Yellow threshold' => 'not-a-number',
					'Red threshold' => self::RED_VALUE
				],
				'error' => ['Invalid parameter "Yellow threshold": a number is expected.']
			]],
			'red threshold must be above yellow threshold' => [[
				'fields' => [
					'Item' => 'Float item',
					'Yellow threshold' => self::RED_VALUE,
					'Red threshold' => self::YELLOW_VALUE
				],
				'error' => ['Invalid parameter "Red threshold": value must be greater than "Yellow threshold".']
			]],
			'unsupported item type is rejected' => [[
				'fields' => [
					'Item' => 'Character item',
					'Yellow threshold' => self::YELLOW_VALUE,
					'Red threshold' => self::RED_VALUE
				],
				'error' => ['Invalid parameter "Item": only numeric items are supported.']
			]]
		];
	}

	/**
	 * @dataProvider getValidationData
	 */
	public function testDashboardTrafficLightWidget_Validation(array $data): void {
		$this->page->login()->open('zabbix.php?action=dashboard.view&dashboardid='.self::$dashboardid)->waitUntilReady();

		$form = CDashboardElement::find()->one()->edit()->addWidget()->asForm();
		$form->fill(['Type' => CFormElement::RELOADABLE_FILL('Traffic light')]);
		$form->fill($data['fields']);
		$form->submit();

		$this->assertMessage(TEST_BAD, null, $data['error']);
	}

	public function testDashboardTrafficLightWidget_PersistsConfiguration(): void {
		$this->page->login()->open('zabbix.php?action=dashboard.view&dashboardid='.self::$dashboardid)->waitUntilReady();

		$dashboard = CDashboardElement::find()->one()->edit();
		$form = $dashboard->getWidget(self::ACTIVE_WIDGET)->edit();
		$form->checkValue([
			'Item' => 'Float item',
			'Yellow threshold' => self::YELLOW_VALUE,
			'Red threshold' => self::RED_VALUE
		]);

		$form->fill([
			'Yellow threshold' => 15,
			'Red threshold' => 30
		]);
		$form->submit();
		$dashboard->save();

		$this->assertMessage(TEST_GOOD, 'Dashboard updated');

		$this->page->open('zabbix.php?action=dashboard.view&dashboardid='.self::$dashboardid)->waitUntilReady();

		$form = CDashboardElement::find()->one()->edit()->getWidget(self::ACTIVE_WIDGET)->edit();
		$form->checkValue([
			'Item' => 'Float item',
			'Yellow threshold' => 15,
			'Red threshold' => 30
		]);

		$form->fill([
			'Yellow threshold' => self::YELLOW_VALUE,
			'Red threshold' => self::RED_VALUE
		]);
		$form->submit();
		CDashboardElement::find()->one()->save();
	}

	public function testDashboardTrafficLightWidget_StatesAndFallbacks(): void {
		$this->assertTrafficLightState(self::GREEN_VALUE, 'green');
		$this->assertTrafficLightState(self::YELLOW_VALUE, 'yellow');
		$this->assertTrafficLightState(self::RED_VALUE, 'red');

		$this->page->open('zabbix.php?action=dashboard.view&dashboardid='.self::$dashboardid)->waitUntilReady();
		$dashboard = CDashboardElement::find()->one()->waitUntilReady();

		$this->assertFallbackWidget($dashboard->getWidget(self::UNSUPPORTED_WIDGET), 'Only numeric items are supported.');
		$this->assertFallbackWidget($dashboard->getWidget(self::NO_DATA_WIDGET), 'No data');
		$this->assertFallbackWidget($dashboard->getWidget(self::MISSING_WIDGET),
			'No permissions to referred object or it does not exist!'
		);
	}

	private function assertTrafficLightState(float $value, string $active_color): void {
		CDataHelper::addItemData(self::$itemids['Float item'], $value, time());

		$this->page->login()->open('zabbix.php?action=dashboard.view&dashboardid='.self::$dashboardid)->waitUntilReady();

		$widget = CDashboardElement::find()->one()->getWidget(self::ACTIVE_WIDGET);
		$content = $widget->getContent();

		foreach (['green', 'yellow', 'red'] as $color) {
			$indicator = $content->query('xpath:.//*[contains(@class, "traffic-light-widget-indicator-'.$color.'")]')
				->one();

			$this->assertSame($color === $active_color,
				$indicator->hasClass('traffic-light-widget-indicator-active')
			);
		}
	}

	private function assertFallbackWidget(CWidgetElement $widget, string $message): void {
		$content = $widget->getContent();

		$this->assertEquals($message,
			$content->query('class:traffic-light-widget-message')->one()->getText()
		);
		$this->assertFalse($content->query('class:traffic-light-widget-value')->one(false)->isValid());
		$this->assertEquals(0,
			$content->query('xpath:.//*[contains(@class, "traffic-light-widget-indicator-active")]')->all()->count()
		);
	}
}
