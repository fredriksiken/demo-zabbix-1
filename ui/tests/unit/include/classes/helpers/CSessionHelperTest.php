<?php declare(strict_types = 0);
/*
** Copyright (C) 2001-2026 Zabbix SIA
**
** This program is free software: you can redistribute it and/or modify it under
** the terms of the GNU Affero General Public License as published by the Free
** Software Foundation, version 3.
**
** This program is distributed in the hope that it will be useful, but WITHOUT
** ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
** FOR A PARTICULAR PURPOSE.
** See the GNU Affero General Public License for more details.
**
** You should have received a copy of the GNU Affero General Public License along
** with this program. If not, see <https://www.gnu.org/licenses/>.
**/

use PHPUnit\Framework\TestCase;

class CSessionHelperTest extends TestCase {

	private array $session_backup = [];

	protected function setUp(): void {
		$this->session_backup = $_SESSION ?? [];
		$_SESSION = [];
	}

	protected function tearDown(): void {
		$_SESSION = $this->session_backup;
	}

	public function testDashboardDemoModeLifecycle(): void {
		$this->assertFalse(CSessionHelper::loadDashboardDemoMode());

		CSessionHelper::saveDashboardDemoMode(true);
		$this->assertTrue(CSessionHelper::loadDashboardDemoMode());
		$this->assertSame(1, $_SESSION['web.dashboard.demo_mode']);

		$this->assertFalse(CSessionHelper::toggleDashboardDemoMode());
		$this->assertFalse(CSessionHelper::loadDashboardDemoMode());
		$this->assertSame(0, $_SESSION['web.dashboard.demo_mode']);
	}
}
