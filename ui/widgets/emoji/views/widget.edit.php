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
 * Emoji widget form view.
 *
 * @var CView $this
 * @var array $data
 */

use Widgets\Emoji\Widget;

$form = new CWidgetFormView($data);

$form
	->addField(
		(new CWidgetFieldMultiSelectItemView($data['fields']['itemid']))
			->setPopupParameter('value_types', [ITEM_VALUE_TYPE_FLOAT, ITEM_VALUE_TYPE_UINT64])
	)
	->addField(
		(new CWidgetFieldEmojiThresholdsView($data['fields']['thresholds']))->addRowClass('js-row-thresholds')
	)
	->addField(
		(new CWidgetFieldTextBoxView($data['fields']['label']))->setAdaptiveWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
	)
	->addField(
		(new CWidgetFieldTextBoxView($data['fields']['fallback_emoji']))->setAdaptiveWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
	)
	->addField($data['templateid'] === null
		? new CWidgetFieldMultiSelectOverrideHostView($data['fields']['override_hostid'])
		: null
	)
	->includeJsFile('widget.edit.js.php')
	->initFormJs('widget_form.init();')
	->show();
