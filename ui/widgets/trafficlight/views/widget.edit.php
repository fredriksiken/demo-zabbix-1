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
 * Traffic-light widget form view.
 *
 * @var CView $this
 * @var array $data
 */

use Zabbix\Widgets\Fields\CWidgetFieldMultiSelectItem;

$form = new CWidgetFormView($data);

$item_view = new CWidgetFieldMultiSelectItemView($data['fields']['itemid']);
$item_view->setPopupParameter('value_types', [ITEM_VALUE_TYPE_FLOAT, ITEM_VALUE_TYPE_UINT64]);

$form
	->addField($item_view)
	->addField(new CWidgetFieldRadioButtonListView($data['fields']['direction']))
	->addField(new CWidgetFieldNumericBoxView($data['fields']['warning_threshold']))
	->addField(new CWidgetFieldNumericBoxView($data['fields']['critical_threshold']))
	->addFieldset(
		(new CWidgetFormFieldsetCollapsibleView(_('Display customization')))
			->addField(new CWidgetFieldTextBoxView($data['fields']['green_label']))
			->addField(new CWidgetFieldTextBoxView($data['fields']['yellow_label']))
			->addField(new CWidgetFieldTextBoxView($data['fields']['red_label']))
			->addField(new CWidgetFieldTextBoxView($data['fields']['no_data_label']))
			->addField(new CWidgetFieldColorView($data['fields']['green_color']))
			->addField(new CWidgetFieldColorView($data['fields']['yellow_color']))
			->addField(new CWidgetFieldColorView($data['fields']['red_color']))
			->addField(new CWidgetFieldColorView($data['fields']['no_data_color']))
	)
	->show();
