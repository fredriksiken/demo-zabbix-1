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

namespace {

	use PHPUnit\Framework\TestCase;
	use Widgets\TrafficLight01\Widget;
	use Widgets\TrafficLight01\Includes\WidgetForm;
	use Widgets\TrafficLight01\Actions\WidgetView;

	class TrafficLight01WidgetTest extends TestCase {

		public static function setUpBeforeClass(): void {
			// PHPUnit unit bootstrap autoloads Zabbix core include/classes, but not ui/widgets custom widget classes.
			require_once __DIR__.'/../../../widgets/trafficlight01/Widget.php';
			require_once __DIR__.'/../../../include/classes/mvc/CController.php';
			require_once __DIR__.'/../../../include/classes/mvc/CControllerResponse.php';
			require_once __DIR__.'/../../../include/classes/mvc/CControllerResponseData.php';
			require_once __DIR__.'/../../../app/controllers/CControllerDashboardWidgetView.php';
			require_once __DIR__.'/../../../widgets/trafficlight01/actions/WidgetView.php';
			require_once __DIR__.'/../../../widgets/trafficlight01/includes/WidgetForm.php';
		}

		public function testGetStateLabelMapsKnownStates(): void {
			$this->assertNotSame(_('Unknown'), Widget::getStateLabel(Widget::STATE_RED));
			$this->assertNotSame(_('Unknown'), Widget::getStateLabel(Widget::STATE_YELLOW));
			$this->assertNotSame(_('Unknown'), Widget::getStateLabel(Widget::STATE_GREEN));
		}

		public function testWidgetFormValidateRejectsInvalidCurrentState(): void {
			$form = (new WidgetForm([
				'current_state' => 'invalid',
				'demo_mode' => 1,
				'auto_interval_sec' => 2
			]))->addFields();

			$form->setFieldsValues();

			$errors = $form->validate();
			$this->assertNotEmpty($errors);
		}

		public function testWidgetViewResponseFlagsInvalidCurrentStateAndUsesDefault(): void {
			$controller = new class extends WidgetView {
				public ?\CControllerResponseData $response = null;

				protected function getInput($key, $default = null) {
					return $default;
				}

				protected function getDebugMode(): bool {
					return false;
				}

				public function setResponse($response): void {
					$this->response = $response;
				}

				public function runDoAction(): void {
					parent::doAction();
				}
			};

			$manifest = json_decode(file_get_contents(__DIR__.'/../../../widgets/trafficlight01/manifest.json'), true);
			$widget = new Widget($manifest, $manifest['id'] ?? 'trafficlight01', 'widgets/trafficlight01');
			$ref = new \ReflectionObject($controller);
			$ref->getProperty('widget')->setAccessible(true);
			$ref->getProperty('widget')->setValue($controller, $widget);

			$ref->getProperty('fields_values')->setAccessible(true);
			$ref->getProperty('fields_values')->setValue($controller, [
				'current_state' => 'invalid',
				'demo_mode' => 1,
				'auto_interval_sec' => 2
			]);

			$controller->runDoAction();

			$this->assertInstanceOf(\CControllerResponseData::class, $controller->response);
			$data = $controller->response->getData();

			$this->assertSame(Widget::DEFAULT_STATE, $data['current_state']);
			$this->assertFalse($data['valid']);
			$this->assertSame(2000, $data['auto_interval_ms']);
		}

		public function testWidgetViewResponseFlagsInvalidAutoIntervalAndUsesDefault(): void {
			$controller = new class extends WidgetView {
				public ?CControllerResponseData $response = null;

				protected function getInput($key, $default = null) {
					return $default;
				}

				protected function getDebugMode(): bool {
					return false;
				}

				public function setResponse($response): void {
					$this->response = $response;
				}

				public function runDoAction(): void {
					parent::doAction();
				}
			};

			$manifest = json_decode(file_get_contents(__DIR__.'/../../../widgets/trafficlight01/manifest.json'), true);
			$widget = new Widget($manifest, $manifest['id'] ?? 'trafficlight01', 'widgets/trafficlight01');
			$ref = new \ReflectionObject($controller);
			$ref->getProperty('widget')->setAccessible(true);
			$ref->getProperty('widget')->setValue($controller, $widget);

			$ref->getProperty('fields_values')->setAccessible(true);
			$ref->getProperty('fields_values')->setValue($controller, [
				'current_state' => Widget::STATE_GREEN,
				'demo_mode' => 1,
				'auto_interval_sec' => 999
			]);

			$controller->runDoAction();

			$data = $controller->response->getData();
			$this->assertFalse($data['valid']);
			$this->assertSame(Widget::STATE_GREEN, $data['current_state']);
			$this->assertSame(2000, $data['auto_interval_ms']); // default auto_interval_sec=2
		}

		public function testViewStateLabelAndActiveClassMappingMatchesWidgetConstants(): void {
			$expected = [
				Widget::STATE_RED => [
					'label' => 'Red',
					'class' => 'trafficlight-red'
				],
				Widget::STATE_YELLOW => [
					'label' => 'Yellow',
					'class' => 'trafficlight-yellow'
				],
				Widget::STATE_GREEN => [
					'label' => 'Green',
					'class' => 'trafficlight-green'
				]
			];

			foreach (Widget::STATES as $state) {
				$this->assertSame($expected[$state]['label'], Widget::getStateLabel($state));
				$this->assertSame($expected[$state]['class'], [
					Widget::STATE_RED => 'trafficlight-red',
					Widget::STATE_YELLOW => 'trafficlight-yellow',
					Widget::STATE_GREEN => 'trafficlight-green'
				][$state]);
			}
		}
	}
}
