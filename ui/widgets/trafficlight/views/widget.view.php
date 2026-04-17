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

$lamps = (new CDiv())->addClass('trafficlight-lamps');

foreach (['green', 'yellow', 'red'] as $lamp_state) {
	$lamps->addItem(
		(new CSpan())
			->addClass('trafficlight-lamp')
			->addClass('trafficlight-lamp-'.$lamp_state)
			->addClass($data['state'] === $lamp_state ? 'is-active' : 'is-inactive')
	);
}

$summary = (new CDiv())
	->addClass('trafficlight-summary')
	->addItem((new CSpan($data['state_label']))->addClass('trafficlight-state-label'))
	->addItem(
		(new CSpan(_s('%1$d matched / %2$d with data',
			$data['summary']['matched_items'],
			$data['summary']['items_with_data']
		)))->addClass('trafficlight-state-counts')
	);

$body = (new CDiv([$lamps, $summary]))
	->addClass('trafficlight-widget')
	->addClass('is-state-'.$data['state'])
	->setAttribute('data-state', $data['state'])
	->setAttribute('data-matched-items', (string) $data['summary']['matched_items'])
	->setAttribute('data-items-with-data', (string) $data['summary']['items_with_data'])
	->setAttribute('data-items-without-data', (string) $data['summary']['items_without_data'])
	->setAttribute('data-green-items', (string) $data['summary']['green_items'])
	->setAttribute('data-yellow-items', (string) $data['summary']['yellow_items'])
	->setAttribute('data-red-items', (string) $data['summary']['red_items']);

(new CWidgetView($data))
	->addItem($body)
	->show();

