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


namespace Widgets\TrafficLight01;

use Zabbix\Core\CWidget;

class Widget extends CWidget {

	public const STATE_RED = 'red';
	public const STATE_YELLOW = 'yellow';
	public const STATE_GREEN = 'green';

	public const STATES = [
		self::STATE_RED,
		self::STATE_YELLOW,
		self::STATE_GREEN
	];

	public const DEFAULT_STATE = self::STATE_RED;

	public function getDefaultName(): string {
		return _('Traffic Light #01');
	}

	public static function getStateLabel(string $state): string {
		switch ($state) {
			case self::STATE_RED:
				return _('Red');
			case self::STATE_YELLOW:
				return _('Yellow');
			case self::STATE_GREEN:
				return _('Green');
		}

		return _('Unknown');
	}

	public function getInitialFieldsValues(): array {
		return [
			'current_state' => self::DEFAULT_STATE,
			'demo_mode' => 1,
			'auto_interval_sec' => 2
		];
	}
}
