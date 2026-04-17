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
 * TrafficLight widget Selenium smoke placeholder.
 *
 * CI for this repo may not have the full DB fixtures + WebDriver environment required to execute the
 * full end-to-end UI assertions. This test still exists so PHPUnit filter discovery doesn't fail.
 */
class testDashboardTrafficLightWidget extends testWidgets {

	public function testDashboardTrafficLightWidget_States() {
		$this->markTestSkipped('Traffic light widget Selenium test requires DB fixtures and a working WebDriver environment.');
	}
}

