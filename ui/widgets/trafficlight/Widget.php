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


namespace Widgets\TrafficLight;

use Zabbix\Core\CWidget;

class Widget extends CWidget {

	/**
	 * Threshold color palette used by the editor UI.
	 *
	 * The widget logic itself is threshold-value based and does not rely on these colors.
	 */
	public const DEFAULT_COLOR_PALETTE = ['FCCB1D', 'E65660']; // Yellow, Red.

	public function getDefaultName(): string {
		return _('Traffic light');
	}

	public function getInitialFieldsValues(): array {
		// Keep defaults aligned with the MVP semantics (higher-is-worse, inclusive boundaries).
		return [
			'yellow_threshold' => '10',
			'red_threshold' => '20',
			'items' => []
		];
	}
}
