<?php declare(strict_types = 1);
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

use PHPUnit\Framework\TestCase;
use Widgets\TrafficLight\Actions\WidgetView;
use Widgets\TrafficLight\Widget;

require_once __DIR__.'/../../../../../../include/classes/mvc/CController.php';
require_once __DIR__.'/../../../../../../include/classes/mvc/CControllerResponse.php';
require_once __DIR__.'/../../../../../../include/classes/mvc/CControllerResponseData.php';
require_once __DIR__.'/../../../../../../app/controllers/CControllerDashboardWidgetView.php';
require_once __DIR__.'/../../../../../../widgets/trafficlight/actions/WidgetView.php';
require_once __DIR__.'/../../../../../../widgets/trafficlight/Widget.php';

// PHPUnit unit bootstrap does not load PHP icon constants (they are typically available in full web bootstrap).
// Define the subset used by this test so `WidgetView::doAction()` can build cover payloads.
if (!defined('ZBX_ICON_WIDGET_EMPTY_REFERENCES_LARGE')) {
	define('ZBX_ICON_WIDGET_EMPTY_REFERENCES_LARGE', 'zi-widget-empty-references-large');
}
if (!defined('ZBX_ICON_WIDGET_NOT_CONFIGURED_LARGE')) {
	define('ZBX_ICON_WIDGET_NOT_CONFIGURED_LARGE', ZBX_ICON_WIDGET_EMPTY_REFERENCES_LARGE);
}

class TrafficLightWidgetViewTest extends TestCase {

	public function testDirectionOverrideDetectedInvalidConfigurationCover(): void {
		$view = new class extends WidgetView {
			public function execute(): void {
				$this->doAction();
			}

			public function setInput(array $input): void {
				$this->input = $input;
			}

			public function setFieldsValues(array $fields_values): void {
				$this->fields_values = $fields_values;
			}

			public function setWidget(Widget $widget): void {
				$this->widget = $widget;
			}

			protected function getDebugMode() {
				return false;
			}
		};

		$widget = (new ReflectionClass(Widget::class))->newInstanceWithoutConstructor();
		$view->setWidget($widget);
		$view->setFieldsValues([
			'yellow_threshold' => '10',
			'red_threshold' => '20',
			'items' => [1, 2]
		]);
		$view->setInput([
			'name' => 'Traffic light widget',
			'fields' => [
				['name' => 'direction', 'value' => 'lower_is_worse']
			]
		]);

		$view->execute();

		$data = $view->getResponse()->getData();
		$this->assertSame(ZBX_ICON_WIDGET_NOT_CONFIGURED_LARGE, $data['cover']['icon']);
		$this->assertStringContainsString('direction is fixed to higher_is_worse', $data['cover']['description']);
	}

	public function testEmptySelectionUsesStandardEmptySelectionCover(): void {
		$view = new class extends WidgetView {
			public function execute(): void {
				$this->doAction();
			}

			public function setInput(array $input): void {
				$this->input = $input;
			}

			public function setFieldsValues(array $fields_values): void {
				$this->fields_values = $fields_values;
			}

			public function setWidget(Widget $widget): void {
				$this->widget = $widget;
			}

			protected function getDebugMode() {
				return false;
			}
		};

		$widget = (new ReflectionClass(Widget::class))->newInstanceWithoutConstructor();
		$view->setWidget($widget);
		$view->setFieldsValues([
			'yellow_threshold' => '10',
			'red_threshold' => '20',
			'items' => []
		]);
		$view->setInput([
			'name' => 'Traffic light widget',
			'fields' => []
		]);

		$view->execute();

		$data = $view->getResponse()->getData();
		$this->assertSame(ZBX_ICON_WIDGET_EMPTY_REFERENCES_LARGE, $data['cover']['icon']);
		$this->assertSame(_('Widget is not fully configured'), $data['cover']['message']);
	}
}
