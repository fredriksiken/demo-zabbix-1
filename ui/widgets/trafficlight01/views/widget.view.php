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


/**
 * Traffic Light widget view.
 *
 * @var CView $this
 * @var array $data
 */

use Widgets\TrafficLight01\Widget;

$view = new CWidgetView($data);

$valid = array_key_exists('valid', $data) ? (bool) $data['valid'] : true;
$current_state = (string) ($data['current_state'] ?? Widget::DEFAULT_STATE);

$state_label = Widget::getStateLabel($current_state);

$view->setVar('valid', $valid);
$view->setVar('current_state', $current_state);
$view->setVar('state_label', $state_label);

$classes = [
	Widget::STATE_RED => 'trafficlight-red',
	Widget::STATE_YELLOW => 'trafficlight-yellow',
	Widget::STATE_GREEN => 'trafficlight-green'
];

$view->setVar('state_classes', $classes);

if (!$valid) {
	$view->addItem((new CTableInfo())->setNoDataMessage(_('Invalid configuration')));
}
else {
	$view->addItem(
		(new CDiv())->addClass('trafficlight-widget')
			->addItem(
				(new CDiv())
					->addClass('trafficlight-circles')
					->addItem((new CDiv())->addClass('trafficlight-light trafficlight-red')->addItem(new CDiv()))
					->addItem((new CDiv())->addClass('trafficlight-light trafficlight-yellow')->addItem(new CDiv()))
					->addItem((new CDiv())->addClass('trafficlight-light trafficlight-green')->addItem(new CDiv()))
			)
			->addItem(
				(new CDiv())
					->addClass('trafficlight-indicator')
					->addItem(
						(new CSpan(_('Current state: ')))->addClass('trafficlight-indicator-label')
					)
					->addItem(
						(new CSpan())->addClass('trafficlight-indicator-value')->addItem(new CDiv($state_label))
					)
			)
			->addItem(
				(new CDiv())->addClass('trafficlight-controls')
					->addItem(
						(new CButton('trafficlight-cycle-btn', _('Cycle')))->addClass('trafficlight-btn trafficlight-cycle')
					)
					->addItem(
						(new CButton('trafficlight-toggle-auto-btn', _('Pause')))->addClass('trafficlight-btn trafficlight-auto')
					)
			)
	);
}

$view->show();
