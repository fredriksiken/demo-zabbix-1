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


class CWidgetTrafficLight extends CWidget {

	static RED = 'red';
	static YELLOW = 'yellow';
	static GREEN = 'green';

	static COLORS = {
		red: '#E65660',
		yellow: '#FCCB1D',
		green: '#3BC97D',
		inactive: '#D0D0D0'
	};

	#renderLight({state, has_value}) {
		const {red, yellow, green, inactive} = CWidgetTrafficLight.COLORS;

		const mk = ({background, is_active}) => {
			const style = [
				'width: 100%',
				'height: 100%',
				'border-radius: 999px',
				'background: '+background,
				'opacity: '+(is_active ? '1' : '0.35'),
				'border: 2px solid '+(is_active ? background : inactive),
				'box-sizing: border-box',
				'flex: 1 1 0'
			].join(';');

			return `<div style="${style}" aria-hidden="true"></div>`;
		};

		if (!has_value) {
			return {
				markup: [
					mk({background: inactive, is_active: false}),
					mk({background: inactive, is_active: false}),
					mk({background: inactive, is_active: false})
				].join(''),
				state_label: null
			};
		}

		return {
			markup: [
				mk({background: red, is_active: state === CWidgetTrafficLight.RED}),
				mk({background: yellow, is_active: state === CWidgetTrafficLight.YELLOW}),
				mk({background: green, is_active: state === CWidgetTrafficLight.GREEN})
			].join(''),
			state_label: state
		};
	}

	#evaluateState({value, yellowCutoff, greenCutoff}) {
		if (value === null || value === undefined || !Number.isFinite(value)) {
			return null;
		}

		if (value >= greenCutoff) {
			return CWidgetTrafficLight.GREEN;
		}

		if (value >= yellowCutoff) {
			return CWidgetTrafficLight.YELLOW;
		}

		return CWidgetTrafficLight.RED;
	}

	setContents(response) {
		// Handle rare server-side fallback (e.g. permission errors) using standard HTML body.
		if ('body' in response) {
			this._body.innerHTML = response.body ?? '';
			return;
		}

		this._body.innerHTML = '';

		const value = response.value ?? null;
		const yellowCutoff = response.yellowCutoff;
		const greenCutoff = response.greenCutoff;

		const state = this.#evaluateState({
			value,
			yellowCutoff,
			greenCutoff
		});

		if (state === null) {
			this.setCoverMessage({message: t('Awaiting data')});
			return;
		}

		const value_text = Number.isFinite(value) ? value : '';

		const {markup, state_label} = this.#renderLight({state, has_value: true});

		this._body.innerHTML = `
			<div class="zbx-trafficlight" role="status" aria-live="polite"
				aria-label="Traffic Light: ${state_label.toUpperCase()}${value_text !== '' ? ' ('+value_text+')' : ''}">
				<div style="display:flex; gap:6px; align-items:center; width:100%; height:100%;">
					${markup}
				</div>
				<div style="margin-top:6px; font-size:12px; font-weight:600; text-transform:uppercase;">
					State: ${state_label}
				</div>
			</div>
		`;
	}
}

