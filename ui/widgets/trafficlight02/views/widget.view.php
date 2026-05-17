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
 * Traffic Light #02 widget view.
 *
 * @var CView $this
 * @var array $data
 */

use Zabbix\Core\CWidgetView;

$view = new CWidgetView($data);

// Purely presentational widget: deterministic demo controls are driven by frontend state.
// No backend in/out configuration is required for this widget.
$view
	->addItem(
		(new CDiv())
			->addClass('traffic-light02')
			->addStyle('display:flex;flex-direction:column;gap:6px;justify-content:center;')
			->addItem(
				(new CDiv())
					->addClass('traffic-light02-lights')
					->addStyle('display:flex;flex-direction:column;gap:4px;align-items:flex-start;')
					->addItem((new CDiv())
						->addClass('traffic-light02-light traffic-light02-red')
						->addStyle('width:20px;height:20px;border-radius:50%;background:#7f7f7f;opacity:0.50;border:2px solid rgba(0,0,0,0.35);transition:opacity .15s ease, transform .15s ease, border-color .15s ease;'))
					->addItem((new CDiv())
						->addClass('traffic-light02-light traffic-light02-yellow')
						->addStyle('width:20px;height:20px;border-radius:50%;background:#c8a300;opacity:0.50;border:2px solid rgba(0,0,0,0.35);transition:opacity .15s ease, transform .15s ease, border-color .15s ease;'))
					->addItem((new CDiv())
						->addClass('traffic-light02-light traffic-light02-green')
						->addStyle('width:20px;height:20px;border-radius:50%;background:#00a000;opacity:0.50;border:2px solid rgba(0,0,0,0.35);transition:opacity .15s ease, transform .15s ease, border-color .15s ease;'))
			)
			->addItem(
				(new CDiv())
					->addClass('traffic-light02-indicator')
					->addStyle('font-size:12px;line-height:1.2;')
					->addItem((new CDiv())->addClass('traffic-light02-indicator-label')->addItem(_('Current state: ')))
					->addItem(
						(new CDiv())
							->addClass('traffic-light02-indicator-value')
							->addAttribute('role', 'status')
							->addAttribute('aria-live', 'polite')
							->addAttribute('aria-atomic', 'true')
							->addStyle('font-weight:700;')
							->addItem(_('RED'))
					)
			)
			->addItem(
				(new CDiv())
					->addClass('traffic-light02-demo')
					->addStyle('display:flex;gap:6px;flex-wrap:wrap;')
					->addItem(
						(new CButton('traffic-light02-demo-red', _('Red')))
							->addClass('traffic-light02-demo-btn')
							->addAttribute('type', 'button')
							->addStyle('padding:4px 6px;font-size:12px;')
							->addAttribute('data-state', 'red')
					)
					->addItem(
						(new CButton('traffic-light02-demo-yellow', _('Yellow')))
							->addClass('traffic-light02-demo-btn')
							->addAttribute('type', 'button')
							->addStyle('padding:4px 6px;font-size:12px;')
							->addStyle('background:#d9b200;')
							->addAttribute('data-state', 'yellow')
					)
					->addItem(
						(new CButton('traffic-light02-demo-green', _('Green')))
							->addClass('traffic-light02-demo-btn')
							->addAttribute('type', 'button')
							->addStyle('padding:4px 6px;font-size:12px;')
							->addStyle('background:#0c9b0c;')
							->addAttribute('data-state', 'green')
					)
			)
	)->show();
