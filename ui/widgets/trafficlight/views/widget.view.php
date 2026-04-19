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
 * Traffic light widget view.
 *
 * @var CView $this
 * @var array $data
 */

$traffic_light = $data['traffic_light'];

$body = (new CDiv())
	->addClass('traffic-light-widget')
	->addClass('traffic-light-widget-state-'.strtolower($traffic_light['state']));

$indicators = (new CDiv())->addClass('traffic-light-widget-indicators');

foreach ([
	CTrafficLightWidgetHelper::STATE_GREEN => 'green',
	CTrafficLightWidgetHelper::STATE_YELLOW => 'yellow',
	CTrafficLightWidgetHelper::STATE_RED => 'red'
] as $state => $color) {
	$indicator = (new CDiv())
		->addClass('traffic-light-widget-indicator')
		->addClass('traffic-light-widget-indicator-'.$color);

	if ($traffic_light['state'] === $state && !$traffic_light['is_fallback']) {
		$indicator->addClass('traffic-light-widget-indicator-active');
	}

	$indicators->addItem($indicator);
}

$body->addItem($indicators);

$details = (new CDiv())->addClass('traffic-light-widget-details');

if ($traffic_light['label'] !== '') {
	$details->addItem(
		(new CDiv($traffic_light['label']))->addClass('traffic-light-widget-label')
	);
}

if ($traffic_light['value'] !== '') {
	$details->addItem(
		(new CDiv($traffic_light['value']))->addClass('traffic-light-widget-value')
	);
}

$details->addItem(
	(new CDiv($traffic_light['is_fallback'] ? $traffic_light['message'] : $traffic_light['state']))
		->addClass('traffic-light-widget-message')
);

$body->addItem($details);

if (!$traffic_light['is_fallback'] && $traffic_light['url'] !== '') {
	$body = new CLink($body, $traffic_light['url']);
}

(new CWidgetView($data))
	->addItem($body)
	->show();
