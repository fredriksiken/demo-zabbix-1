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


namespace Widgets\TrafficLightWidget01\Actions;

use CControllerDashboardWidgetView,
	CControllerResponseData;

use Widgets\TrafficLightWidget01\Widget;

class WidgetView extends CControllerDashboardWidgetView {

	protected function doAction(): void {
		$this->setResponse(new CControllerResponseData([
			'name' => $this->widget->getDefaultName(),
			// Keep widget_data empty so frontend demo state isn't reset on each refresh/re-render.
			// The widget starts on red via its JS onInitialize() logic.
			'widget_data' => (object)[],
			'styles' => (object)[],
			'classes' => (object)[]
		]));
	}

}
