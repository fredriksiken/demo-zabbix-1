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


class CControllerDashboardDemoModeToggle extends CController {

	protected function checkInput(): bool {
		$fields = [
			'dashboardid' => 'db dashboard.dashboardid',
			'hostid' => 'db hosts.hostid',
			'return_action' => 'in dashboard.view,host.dashboard.view',
			'from' => 'range_time',
			'to' => 'range_time',
			'new' => 'in 1',
			'clone' => 'in 1'
		];

		$ret = $this->validateInput($fields) && $this->validateTimeSelectorPeriod();

		if (!$ret) {
			$this->setResponse(new CControllerResponseFatal());
		}

		return $ret;
	}

	protected function checkPermissions(): bool {
		return $this->checkAccess(CRoleHelper::UI_MONITORING_DASHBOARD)
			|| $this->checkAccess(CRoleHelper::UI_MONITORING_HOSTS);
	}

	protected function doAction(): void {
		CSessionHelper::toggleDashboardDemoMode();

		$url = (new CUrl('zabbix.php'))
			->setArgument('action', $this->getInput('return_action', 'dashboard.view'));

		foreach (['dashboardid', 'hostid', 'from', 'to'] as $argument) {
			if ($this->hasInput($argument)) {
				$url->setArgument($argument, $this->getInput($argument));
			}
		}

		if ($this->hasInput('new')) {
			$url->setArgument('new', 1);
		}

		if ($this->hasInput('clone')) {
			$url->setArgument('clone', 1);
		}

		$this->setResponse(new CControllerResponseRedirect($url));
	}
}
