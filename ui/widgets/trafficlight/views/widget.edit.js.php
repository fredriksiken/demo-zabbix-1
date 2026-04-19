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

?>

window.widget_form = new class extends CWidgetForm {

	/**
	 * @type {HTMLFormElement}
	 */
	#form;

	init() {
		this.#form = this.getForm();

		for (const element of [document.getElementById('yellow_threshold'), document.getElementById('red_threshold')]) {
			element.addEventListener('input', () => this.#updateThresholdWarning());
		}

		this.#updateThresholdWarning();
		this.ready();
	}

	submit() {
		if (this.#hasClientError()) {
			this.#showError([
				'<?= addslashes(_s('Invalid parameter "%1$s": %2$s.', _('Red threshold'),
					_s('value must be greater than "%1$s"', _('Yellow threshold'))
				)) ?>'
			]);

			return;
		}

		super.submit();
	}

	#hasClientError() {
		const yellow_input = document.getElementById('yellow_threshold').value.trim();
		const red_input = document.getElementById('red_threshold').value.trim();
		const plain_number = /^[+-]?(?:\d+(?:\.\d*)?|\.\d+)(?:e[+-]?\d+)?$/i;

		if (!plain_number.test(yellow_input) || !plain_number.test(red_input)) {
			return false;
		}

		const yellow = Number.parseFloat(yellow_input);
		const red = Number.parseFloat(red_input);

		return Number.isFinite(yellow) && Number.isFinite(red) && yellow >= red;
	}

	#updateThresholdWarning() {
		document.getElementById('traffic-light-threshold-warning').style.display = this.#hasClientError() ? '' : 'none';
	}

	#showError(messages) {
		for (const element of this.#form.parentNode.children) {
			if (element.matches('.msg-good, .msg-bad, .msg-warning')) {
				element.parentNode.removeChild(element);
			}
		}

		this.#form.parentNode.insertBefore(makeMessageBox('bad', messages)[0], this.#form);
	}
};
