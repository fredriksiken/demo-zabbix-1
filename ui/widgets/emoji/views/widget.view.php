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
 * Emoji widget view.
 *
 * @var CView $this
 * @var array $data
 */

if ($data['error'] !== null) {
	$body = (new CTableInfo())->setNoDataMessage($data['error']);
}
else {
	$subtitle = match ($data['state']) {
		'no_data' => _('No data'),
		'matched' => '',
		default => _('Fallback')
	};

	$emoji = (new CDiv($data['emoji']))
		->addClass('emoji-widget-emoji')
		->addStyle('font-size: clamp(2.5rem, 8vw, 6rem); line-height: 1;');

	$parts = [$emoji];

	if ($data['label'] !== '') {
		$parts[] = (new CDiv($data['label']))
			->addClass('emoji-widget-label')
			->addStyle('margin-top: .35rem; font-size: .95rem; opacity: .85;');
	}

	if ($subtitle !== '') {
		$parts[] = (new CDiv($subtitle))
			->addClass('emoji-widget-subtitle')
			->addStyle('margin-top: .15rem; font-size: .8rem; opacity: .65;');
	}

	$body = (new CDiv($parts))
		->addClass('emoji-widget-body')
		->addStyle('display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%;');
}

(new CWidgetView($data))
	->addItem($body)
	->show();
