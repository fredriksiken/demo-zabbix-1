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


namespace Widgets\Emoji\Includes;

use Zabbix\Widgets\CWidgetField;

class CWidgetFieldEmojiThresholds extends CWidgetField {

	public const DEFAULT_VIEW = CWidgetFieldEmojiThresholdsView::class;
	public const DEFAULT_VALUE = [
		[
			'threshold' => '',
			'emoji' => ''
		]
	];

	private bool $is_binary_units;

	public function __construct(string $name, ?string $label = null, bool $is_binary_units = false) {
		parent::__construct($name, $label);

		$this->is_binary_units = $is_binary_units;

		$this
			->setDefault(self::DEFAULT_VALUE)
			->setValidationRules(['type' => API_OBJECTS, 'uniq' => [['threshold']], 'fields' => [
				'threshold' => ['type' => API_NUMERIC, 'flags' => API_REQUIRED],
				'emoji' => ['type' => API_STRING_UTF8, 'flags' => API_REQUIRED | API_NOT_EMPTY, 'length' => 255]
			]]);
	}

	public function getValue() {
		$thresholds = parent::getValue();

		foreach ($thresholds as $index => $threshold) {
			if (trim((string) ($threshold['threshold'] ?? '')) === '' && trim((string) ($threshold['emoji'] ?? '')) === '') {
				unset($thresholds[$index]);
			}
		}

		return array_values($thresholds);
	}

	public function setValue($value): self {
		$this->value = (array) $value;

		return $this;
	}

	public function validate(bool $strict = false): array {
		if ($errors = parent::validate($strict)) {
			return $errors;
		}

		$this->setValue(EmojiWidgetHelper::normalizeThresholds($this->getValue(), $this->is_binary_units));

		return [];
	}

	public function toApi(array &$widget_fields = []): void {
		foreach ($this->getValue() as $index => $value) {
			$widget_fields[] = [
				'type' => $this->save_type,
				'name' => $this->name.'.'.$index.'.threshold',
				'value' => $value['threshold']
			];
			$widget_fields[] = [
				'type' => $this->save_type,
				'name' => $this->name.'.'.$index.'.emoji',
				'value' => $value['emoji']
			];
		}
	}
}
