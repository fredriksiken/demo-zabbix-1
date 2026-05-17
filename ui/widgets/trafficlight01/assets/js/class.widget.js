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


class CWidgetTrafficLight01 extends CWidget {

	static STATE_RED = 'red';
	static STATE_YELLOW = 'yellow';
	static STATE_GREEN = 'green';

	static CONTROLS = {
		CYCLE: 'trafficlight-cycle-btn',
		TOGGLE_AUTO: 'trafficlight-toggle-auto-btn'
	};

	onInitialize() {
		this._current_state = null;
		this._auto_timer = null;
		this._auto_interval_ms = null;
		this._demo_mode = 0;
		this._valid = true;

		this._interval_pending = false;
	}

	onActivate() {
		// Wire up listeners once the widget becomes active.
		this._wireControls();
		this._syncAutoMode();
		// Render after auto-mode sync so the toggle button text (Resume/Pause)
		// matches the actual timer state.
		this._renderFromState();
	}

	onDeactivate() {
		this._stopAuto();
	}

	processUpdateResponse(response) {
		super.processUpdateResponse(response);

		if (response.current_state !== undefined) {
			this._current_state = response.current_state;
		}

		if (response.auto_interval_ms !== undefined) {
			this._auto_interval_ms = response.auto_interval_ms;
		}

		if (response.demo_mode !== undefined) {
			this._demo_mode = response.demo_mode;
		}

		if (response.valid !== undefined) {
			this._valid = response.valid;
		}

		this._renderFromState();
		this._syncAutoMode();
	}

	_wireControls() {
		const cycle_btn = this._target.querySelector(`#${CWidgetTrafficLight01.CONTROLS.CYCLE}`);
		const toggle_auto_btn = this._target.querySelector(`#${CWidgetTrafficLight01.CONTROLS.TOGGLE_AUTO}`);

		if (cycle_btn !== null) {
			cycle_btn.addEventListener('click', () => this._cycleState());
		}

		if (toggle_auto_btn !== null) {
			toggle_auto_btn.addEventListener('click', () => this._togglePauseResume());
		}
	}

	_renderFromState() {
		if (!this._valid) {
			// Invalid configuration: still render a sane default, but don't start auto mode.
			this._current_state = CWidgetTrafficLight01.STATE_RED;
			this._stopAuto();
		}

		const value = this._target.querySelector('.trafficlight-indicator-value');
		if (value !== null) {
			const state_label = this._getStateLabel(this._current_state);
			value.textContent = state_label;
		}

		const lights = this._target.querySelectorAll('.trafficlight-light');
		for (const light of lights) {
			light.classList.remove('active');
		}

		const active_class = this._getStateActiveClass(this._current_state);
		if (active_class !== null) {
			const active_light = this._target.querySelector(`.trafficlight-light.${active_class}`);
			if (active_light !== null) {
				active_light.classList.add('active');
			}
		}

		const toggle_auto_btn = this._target.querySelector(`#${CWidgetTrafficLight01.CONTROLS.TOGGLE_AUTO}`);
		if (toggle_auto_btn !== null) {
			toggle_auto_btn.disabled = this._demo_mode !== 1;
			toggle_auto_btn.textContent = this._auto_timer === null ? _('Resume') : _('Pause');
		}
	}

	_getStateLabel(state) {
		switch (state) {
			case CWidgetTrafficLight01.STATE_RED:
				return _('Red');
			case CWidgetTrafficLight01.STATE_YELLOW:
				return _('Yellow');
			case CWidgetTrafficLight01.STATE_GREEN:
				return _('Green');
			default:
				return _('Unknown');
		}
	}

	_getStateActiveClass(state) {
		switch (state) {
			case CWidgetTrafficLight01.STATE_RED:
				return 'trafficlight-red';
			case CWidgetTrafficLight01.STATE_YELLOW:
				return 'trafficlight-yellow';
			case CWidgetTrafficLight01.STATE_GREEN:
				return 'trafficlight-green';
			default:
				return null;
		}
	}

	_cycleState() {
		switch (this._current_state) {
			case CWidgetTrafficLight01.STATE_RED:
				this._current_state = CWidgetTrafficLight01.STATE_YELLOW;
				break;
			case CWidgetTrafficLight01.STATE_YELLOW:
				this._current_state = CWidgetTrafficLight01.STATE_GREEN;
				break;
			case CWidgetTrafficLight01.STATE_GREEN:
				this._current_state = CWidgetTrafficLight01.STATE_RED;
				break;
			default:
				this._current_state = CWidgetTrafficLight01.STATE_RED;
		}

		this._renderFromState();
	}

	_syncAutoMode() {
		if (this._demo_mode !== 1 || !this._valid) {
			this._stopAuto();
			return;
		}

		if (this._auto_interval_ms === null) {
			return;
		}

		if (this._auto_timer === null) {
			this._auto_timer = setInterval(() => this._cycleState(), this._auto_interval_ms);
		}
	}

	_stopAuto() {
		if (this._auto_timer !== null) {
			clearInterval(this._auto_timer);
			this._auto_timer = null;
		}
	}

	_togglePauseResume() {
		if (this._demo_mode !== 1) {
			return;
		}

		if (this._auto_timer === null) {
			// Resume.
			if (this._auto_interval_ms !== null) {
				this._auto_timer = setInterval(() => this._cycleState(), this._auto_interval_ms);
			}
		}
		else {
			this._stopAuto();
		}

		this._renderFromState();
	}
}
