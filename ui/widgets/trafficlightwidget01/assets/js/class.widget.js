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

class CWidgetTrafficLightWidget01 extends CWidget {

	onInitialize() {
		this._states = ['red', 'yellow', 'green'];
		this._state = 'red';
		this._demo_cycle_interval = null;

		this._events = {
			click: (e) => {
				if (this._is_edit_mode) {
					return;
				}

				e.preventDefault();
				this._cycleState();
			}
		};
	}

	onStart() {
		this._target.innerHTML = '';

		const root = document.createElement('div');
		root.classList.add('traffic-light-widget-01');
		root.setAttribute('role', 'group');
		root.setAttribute('aria-label', 'Traffic light widget');

		this._lights_el = document.createElement('div');
		this._lights_el.classList.add('traffic-light-widget-01__lights');

		for (const state of this._states) {
			const light = document.createElement('div');
			light.classList.add('traffic-light-widget-01__light', `traffic-light-widget-01__light--${state}`);

			const label = document.createElement('div');
			label.classList.add('traffic-light-widget-01__light-label');
			label.textContent = state[0].toUpperCase() + state.slice(1);

			light.appendChild(label);
			this._lights_el.appendChild(light);
		}

		this._indicator_el = document.createElement('div');
		this._indicator_el.classList.add('traffic-light-widget-01__indicator');
		this._indicator_el.setAttribute('role', 'status');

		const indicator_label = document.createElement('span');
		indicator_label.classList.add('traffic-light-widget-01__indicator-label');
		indicator_label.textContent = 'State:';

		this._indicator_value_el = document.createElement('span');
		this._indicator_value_el.classList.add('traffic-light-widget-01__indicator-value');
		this._indicator_value_el.setAttribute('aria-live', 'polite');

		this._indicator_el.appendChild(indicator_label);
		this._indicator_el.appendChild(this._indicator_value_el);

		this._demo_button_el = document.createElement('button');
		this._demo_button_el.classList.add('traffic-light-widget-01__demo-button');
		this._demo_button_el.type = 'button';
		this._demo_button_el.textContent = 'Cycle';

		root.appendChild(this._lights_el);
		root.appendChild(this._indicator_el);
		root.appendChild(this._demo_button_el);

		this._target.appendChild(root);

		this._renderState();
	}

	onActivate() {
		this._demo_button_el.addEventListener('click', this._events.click);

		// Minimal deterministic demo: auto-cycle only while active and not in edit mode.
		if (!this._is_edit_mode && this._demo_cycle_interval === null) {
			this._demo_cycle_interval = setInterval(() => this._cycleState(), 2500);
		}
	}

	onDeactivate() {
		if (this._demo_button_el) {
			this._demo_button_el.removeEventListener('click', this._events.click);
		}

		if (this._demo_cycle_interval !== null) {
			clearInterval(this._demo_cycle_interval);
			this._demo_cycle_interval = null;
		}
	}

	onEdit() {
		// If dashboard is switched to edit mode while active, stop demo so it doesn't interfere with editing.
		if (this._demo_cycle_interval !== null) {
			clearInterval(this._demo_cycle_interval);
			this._demo_cycle_interval = null;
		}
	}

	processUpdateResponse(response) {
		super.processUpdateResponse(response);

		if (response.widget_data?.state !== undefined) {
			if (this._states.includes(response.widget_data.state)) {
				this._state = response.widget_data.state;
			}
		}

		this._renderState();
	}

	_cycleState() {
		const idx = this._states.indexOf(this._state);

		if (idx < 0) {
			this._state = this._states[0];
			this._renderState();
			return;
		}

		this._state = this._states[(idx + 1) % this._states.length];
		this._renderState();
	}

	_renderState() {
		if (!this._lights_el || !this._indicator_value_el) {
			return;
		}

		this._lights_el.querySelectorAll('.traffic-light-widget-01__light').forEach((el) => {
			el.classList.remove('traffic-light-widget-01__light--active');
		});

		// Scope to the widget's own DOM to avoid matching unrelated elements on the page.
		const active_light = this._lights_el.querySelector(`.traffic-light-widget-01__light--${this._state}`);
		if (active_light !== null) {
			active_light.classList.add('traffic-light-widget-01__light--active');
		}

		const pretty = this._state[0].toUpperCase() + this._state.slice(1);
		this._indicator_value_el.textContent = pretty;
	}

	hasPadding() {
		return false;
	}
}
