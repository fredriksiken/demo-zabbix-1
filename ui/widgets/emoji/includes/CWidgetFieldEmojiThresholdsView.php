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


use Widgets\Emoji\Includes\CWidgetFieldEmojiThresholds;

class CWidgetFieldEmojiThresholdsView extends CWidgetFieldView {

	public function __construct(CWidgetFieldEmojiThresholds $field) {
		$this->field = $field;
	}

	public function getView(): CTable {
		$thresholds = $this->field->getValue();

		if (!$thresholds) {
			$thresholds = CWidgetFieldEmojiThresholds::DEFAULT_VALUE;
		}

		$thresholds_table = (new CTable())
			->setId($this->field->getName().'-table')
			->addClass(ZBX_STYLE_TABLE_FORMS)
			->setHeader([
				'',
				_('Threshold'),
				_('Emoji'),
				(new CColHeader(''))->setWidth('100%')
			])
			->setFooter(new CRow(
				(new CCol((new CButtonLink(_('Add')))->addClass('element-table-add')))
					->setColSpan(4)
			));

		foreach ($thresholds as $i => $threshold) {
			$thresholds_table->addRow($this->getRowTemplate($i, $threshold['threshold'], $threshold['emoji']));
		}

		return (new CDiv($thresholds_table))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH);
	}

	public function getTemplates(): array {
		return [
			new CTemplateTag($this->field->getName().'-row-tmpl', $this->getRowTemplate())
		];
	}

	private function getRowTemplate($row_num = '#{rowNum}', $threshold = '#{threshold}', $emoji = '#{emoji}'): CRow {
		return (new CRow([
			(new CCol((new CSpan())->addClass(ZBX_STYLE_DRAG_ICON)))->addClass(ZBX_STYLE_TD_DRAG_ICON),
			(new CTextBox($this->field->getName().'['.$row_num.'][threshold]', $threshold, false))
				->setWidth(ZBX_TEXTAREA_TINY_WIDTH)
				->setEnabled(!$this->isDisabled() || $row_num === '#{rowNum}'),
			(new CTextBox($this->field->getName().'['.$row_num.'][emoji]', $emoji, false))
				->setWidth(ZBX_TEXTAREA_SMALL_WIDTH)
				->setAttribute('placeholder', '🙂')
				->setEnabled(!$this->isDisabled() || $row_num === '#{rowNum}'),
			(new CCol(
				(new CButton($this->field->getName().'['.$row_num.'][remove]', _('Remove')))
					->addClass(ZBX_STYLE_BTN_LINK)
					->addClass('element-table-remove')
					->setEnabled(!$this->isDisabled() || $row_num === '#{rowNum}')
			))->addClass(ZBX_STYLE_NOWRAP)
		]))->addClass('form_row');
	}
}
