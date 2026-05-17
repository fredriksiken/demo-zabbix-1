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

class CWidgetTrafficLight02 extends CWidget {
	static STATE_RED = 'red';
	static STATE_YELLOW = 'yellow';
	static STATE_GREEN = 'green';

	static INDICATOR_LABELS = {
		[CWidgetTrafficLight02.STATE_RED]: 'RED',
		[CWidgetTrafficLight02.STATE_YELLOW]: 'YELLOW',
		[CWidgetTrafficLight02.STATE_GREEN]: 'GREEN'
	};

	onInitialize() {
		this._state = CWidgetTrafficLight02.STATE_RED;
		this._demo_buttons = [];
		this._onDemoClick = null;
	}

	onActivate() {
		this._target.classList.add('traffic-light02-ready');

		this._demo_buttons = [
			...this._target.querySelectorAll('.traffic-light02-demo-btn[data-state]')
		];

		this._onDemoClick = (e) => {
			const state = e.currentTarget?.dataset?.state;
			if (state === CWidgetTrafficLight02.STATE_RED
				|| state === CWidgetTrafficLight02.STATE_YELLOW
				|| state === CWidgetTrafficLight02.STATE_GREEN) {
				this.setState(state);
			}
		};

		for (const button of this._demo_buttons) {
			button.addEventListener('click', this._onDemoClick);
		}

		this._renderState();
	}

	onDeactivate() {
		if (this._onDemoClick) {
			for (const button of this._demo_buttons) {
				button.removeEventListener('click', this._onDemoClick);
			}
		}

		this._demo_buttons = [];
		this._onDemoClick = null;
	}

	setState(state) {
		if (this._state === state) {
			return;
		}

		this._state = state;
		this._renderState();
	}

	_renderState() {
		const lights = {
			[CWidgetTrafficLight02.STATE_RED]: this._target.querySelector('.traffic-light02-red'),
			[CWidgetTrafficLight02.STATE_YELLOW]: this._target.querySelector('.traffic-light02-yellow'),
			[CWidgetTrafficLight02.STATE_GREEN]: this._target.querySelector('.traffic-light02-green')
		};

		for (const [state, light] of Object.entries(lights)) {
			if (!light) {
				continue;
			}

			const is_active = state === this._state;
			light.classList.toggle('active', is_active);

			light.style.opacity = is_active ? '1' : '0.50';
			light.style.transform = is_active ? 'scale(1.05)' : 'scale(1)';
			light.style.borderColor = is_active ? 'transparent' : 'rgba(0,0,0,0.35)';
		}

		const indicatorValue = this._target.querySelector('.traffic-light02-indicator-value');
		if (indicatorValue) {
			indicatorValue.textContent = CWidgetTrafficLight02.INDICATOR_LABELS[this._state] ?? '';
		}
	}

	processUpdateResponse(response) {
		super.processUpdateResponse(response);

		// This widget is presentational: ignore backend response if any.
	}
}
