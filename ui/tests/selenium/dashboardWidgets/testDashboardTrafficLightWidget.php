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
require_once __DIR__.'/../behaviors/CMessageBehavior.php';

/**
 * @backup dashboard
 *
 * @onBefore prepareData
 */
class testDashboardTrafficLightWidget extends testWidgets {

	protected static $dashboardid;

	public function getBehaviors() {
		return [
			CMessageBehavior::class
		];
	}

	protected function getThresholdsTable() {
		return $this->query('id:thresholds-table')->asMultifieldTable([
			'mapping' => [
				'' => [
					'name' => 'color',
					'selector' => 'class:color-picker',
					'class' => 'CColorPickerElement'
				],
				'Threshold' => [
					'name' => 'threshold',
					'selector' => 'xpath:./input',
					'class' => 'CElement'
				]
			]
		])->waitUntilVisible()->one();
	}

	public static function prepareData() {
		CDataHelper::call('settings.update', [
			'default_lang' => 'en_US'
		]);

		$modules = CDataHelper::call('module.get', [
			'output' => ['moduleid'],
			'filter' => ['id' => ['trafficlight']]
		]);

		if ($modules === []) {
			CDataHelper::call('module.create', [[
				'id' => 'trafficlight',
				'relative_path' => 'widgets/trafficlight',
				'status' => MODULE_STATUS_ENABLED
			]]);
		}

		CDataHelper::call('hostgroup.create', [
			[
				'name' => 'Traffic light widget group'
			]
		]);
		$groupids = CDataHelper::getIds('name');

		$response = CDataHelper::createHosts([
			[
				'host' => 'Traffic light widget host',
				'groups' => [['groupid' => $groupids['Traffic light widget group']]],
				'items' => [
					[
						'name' => 'Traffic light sensor green',
						'key_' => 'traffic_light_green',
						'type' => ITEM_TYPE_TRAPPER,
						'value_type' => ITEM_VALUE_TYPE_UINT64
					],
					[
						'name' => 'Traffic light sensor yellow',
						'key_' => 'traffic_light_yellow',
						'type' => ITEM_TYPE_TRAPPER,
						'value_type' => ITEM_VALUE_TYPE_UINT64
					],
					[
						'name' => 'Traffic light sensor red',
						'key_' => 'traffic_light_red',
						'type' => ITEM_TYPE_TRAPPER,
						'value_type' => ITEM_VALUE_TYPE_UINT64
					],
					[
						'name' => 'Traffic light sensor text',
						'key_' => 'traffic_light_text',
						'type' => ITEM_TYPE_TRAPPER,
						'value_type' => ITEM_VALUE_TYPE_TEXT
					]
				]
			]
		]);

		CDataHelper::addItemData($response['itemids']['Traffic light widget host:traffic_light_green'], 5);
		CDataHelper::addItemData($response['itemids']['Traffic light widget host:traffic_light_yellow'], 15);
		CDataHelper::addItemData($response['itemids']['Traffic light widget host:traffic_light_red'], 25);

		$dashboard = CDataHelper::call('dashboard.create', [
			[
				'name' => 'Traffic light widget dashboard',
				'auto_start' => 0,
				'pages' => [
					[
						'widgets' => [
							self::makeWidget('Traffic light yellow', 'Traffic light sensor yellow', 0, 0, [
								['color' => 'E65660', 'threshold' => '20'],
								['color' => 'FCCB1D', 'threshold' => '10']
							]),
							self::makeWidget('Traffic light red', 'Traffic light sensor red', 6, 0),
							self::makeWidget('Traffic light no data', 'Traffic light sensor missing', 12, 0)
						]
					]
				]
			]
		]);

		self::$dashboardid = $dashboard['dashboardids'][0];
	}

	private static function makeWidget(string $name, string $item_pattern, int $x, int $y,
			array $thresholds = [
				['color' => 'FCCB1D', 'threshold' => '10'],
				['color' => 'E65660', 'threshold' => '20']
			]): array {
		$fields = [
			[
				'type' => 1,
				'name' => 'items.0',
				'value' => $item_pattern
			]
		];

		foreach (array_values($thresholds) as $index => $threshold) {
			$fields[] = [
				'type' => 1,
				'name' => 'thresholds.'.$index.'.color',
				'value' => $threshold['color']
			];
			$fields[] = [
				'type' => 1,
				'name' => 'thresholds.'.$index.'.threshold',
				'value' => $threshold['threshold']
			];
		}

		return [
			'type' => 'trafficlight',
			'name' => $name,
			'x' => $x,
			'y' => $y,
			'width' => 6,
			'height' => 4,
			'fields' => $fields
		];
	}

	public function testDashboardTrafficLightWidget_CreateAndRender() {
		$this->page->login()->open('zabbix.php?action=dashboard.view&dashboardid='.self::$dashboardid)->waitUntilReady();
		$dashboard = CDashboardElement::find()->one()->waitUntilReady()->edit();
		$form = $dashboard->addWidget()->asForm();
		$green_widget_thresholds = [
			['color' => 'FCCB1D', 'threshold' => '12'],
			['color' => 'E65660', 'threshold' => '22']
		];

		$form->fill(['Type' => CFormElement::RELOADABLE_FILL('Traffic light')]);
		$form->fill([
			'Name' => 'Traffic light green',
			'Host groups' => ['Traffic light widget group'],
			'Hosts' => ['Traffic light widget host'],
			'Item patterns' => ['Traffic light sensor green']
		]);

		$this->getThresholdsTable()->fill([
			['action' => USER_ACTION_UPDATE, 'index' => 0] + $green_widget_thresholds[0],
			['action' => USER_ACTION_UPDATE, 'index' => 1] + $green_widget_thresholds[1]
		]);

		$form->submit();
		$dashboard->getWidget('Traffic light green');
		$dashboard->save();
		$this->assertMessage(TEST_GOOD, 'Dashboard updated');

		$this->assertWidgetState('Traffic light green', 'green', [
			'matched_items' => '1',
			'items_with_data' => '1',
			'green_items' => '1',
			'yellow_items' => '0',
			'red_items' => '0'
		]);
		$this->assertWidgetState('Traffic light yellow', 'yellow', [
			'matched_items' => '1',
			'items_with_data' => '1',
			'green_items' => '0',
			'yellow_items' => '1',
			'red_items' => '0'
		]);
		$this->assertWidgetState('Traffic light red', 'red', [
			'matched_items' => '1',
			'items_with_data' => '1',
			'green_items' => '0',
			'yellow_items' => '0',
			'red_items' => '1'
		]);
		$this->assertWidgetState('Traffic light no data', 'no-data', [
			'matched_items' => '0',
			'items_with_data' => '0',
			'green_items' => '0',
			'yellow_items' => '0',
			'red_items' => '0'
		]);

		$this->page->open('zabbix.php?action=dashboard.view&dashboardid='.self::$dashboardid)->waitUntilReady();
		$dashboard = CDashboardElement::find()->one()->waitUntilReady()->edit();
		$form = $dashboard->getWidget('Traffic light green')->edit();
		$form->checkValue([
			'Name' => 'Traffic light green',
			'Host groups' => ['Traffic light widget group'],
			'Hosts' => ['Traffic light widget host'],
			'Item patterns' => ['Traffic light sensor green']
		]);
		$this->getThresholdsTable()->checkValue($green_widget_thresholds);
		COverlayDialogElement::find()->one()->close();
		$dashboard->cancelEditing();
	}

	public function testDashboardTrafficLightWidget_Validation() {
		$this->page->login()->open('zabbix.php?action=dashboard.view&dashboardid='.self::$dashboardid)->waitUntilReady();
		$dashboard = CDashboardElement::find()->one()->waitUntilReady()->edit();

		$form = $dashboard->addWidget()->asForm();
		$form->fill(['Type' => CFormElement::RELOADABLE_FILL('Traffic light')]);
		$form->fill([
			'Name' => 'Traffic light invalid thresholds',
			'Host groups' => ['Traffic light widget group'],
			'Hosts' => ['Traffic light widget host'],
			'Item patterns' => ['Traffic light sensor green']
		]);
		$this->getThresholdsTable()->fill([
			['action' => USER_ACTION_UPDATE, 'index' => 0, 'color' => 'E65660', 'threshold' => '10'],
			['action' => USER_ACTION_UPDATE, 'index' => 1, 'color' => 'FCCB1D', 'threshold' => '20']
		]);
		$form->submit();
		$this->assertMessage(TEST_BAD, null,
			'Invalid parameter "Thresholds": thresholds must define yellow then red states.'
		);
		COverlayDialogElement::find()->one()->close();

		$form = $dashboard->addWidget()->asForm();
		$form->fill(['Type' => CFormElement::RELOADABLE_FILL('Traffic light')]);
		$form->fill([
			'Name' => 'Traffic light invalid item type',
			'Host groups' => ['Traffic light widget group'],
			'Hosts' => ['Traffic light widget host'],
			'Item patterns' => ['Traffic light sensor text']
		]);
		$form->submit();
		$this->assertMessage(TEST_BAD, null,
			'Invalid parameter "Item patterns": only numeric items are supported.'
		);
		COverlayDialogElement::find()->one()->close();
		$dashboard->cancelEditing();
	}

	private function assertWidgetState(string $widget_name, string $state, array $expected_attributes): void {
		$widget = CDashboardElement::find()->one()->getWidget($widget_name)->getContent()
			->query('class:trafficlight-widget')->one();

		$this->assertEquals($state, $widget->getAttribute('data-state'));

		foreach ($expected_attributes as $attribute => $value) {
			$this->assertEquals($value, $widget->getAttribute('data-'.str_replace('_', '-', $attribute)));
		}

		foreach (['green', 'yellow', 'red'] as $lamp_state) {
			$lamp = $widget->query('class:trafficlight-lamp-'.$lamp_state)->one();

			if ($lamp_state === $state) {
				$this->assertTrue($lamp->hasClass('is-active'));
			}
			else {
				$this->assertTrue($lamp->hasClass('is-inactive'));
			}
		}
	}
}
