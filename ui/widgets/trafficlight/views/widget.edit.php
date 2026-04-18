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
 * Traffic light widget form view.
 *
 * @var CView $this
 * @var array $data
 */

$form = new CWidgetFormView($data);

$form
	->addField(
		(new CWidgetFieldMultiSelectItemView($data['fields']['itemid']))
			->setPopupParameter('value_types', [ITEM_VALUE_TYPE_FLOAT, ITEM_VALUE_TYPE_UINT64])
	)
	->addField(
		(new CWidgetFieldNumericBoxView($data['fields']['yellow_threshold']))->setFieldHint(
			makeHelpIcon(_('Values lower than the yellow threshold stay green.'))
		)
	)
	->addField(
		(new CWidgetFieldNumericBoxView($data['fields']['red_threshold']))->setFieldHint(
			makeHelpIcon(_('Values from the yellow threshold stay yellow until the red threshold, which is inclusive.'))
		)
	)
	->addItem(
		(new CFormField([
			makeWarningIcon(_('Red threshold must be greater than yellow threshold.'))
				->addStyle('display: none')
				->setId('traffic-light-threshold-warning')
		]))->addClass(CFormField::ZBX_STYLE_FORM_FIELD_FLUID)
	)
	->includeJsFile('widget.edit.js.php')
	->initFormJs('widget_form.init();')
	->show();
