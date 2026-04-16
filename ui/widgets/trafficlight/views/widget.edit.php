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

$groupids_field = array_key_exists('groupids', $data['fields'])
	? new CWidgetFieldMultiSelectGroupView($data['fields']['groupids'])
	: null;

$host_field = array_key_exists('hostids', $data['fields'])
	? (new CWidgetFieldMultiSelectHostView($data['fields']['hostids']))
		->setFilterPreselect([
			'id' => $groupids_field->getId(),
			'accept' => CMultiSelect::FILTER_PRESELECT_ACCEPT_ID,
			'submit_as' => 'groupid'
		])
	: (array_key_exists('override_hostid', $data['fields'])
		? new CWidgetFieldMultiSelectOverrideHostView($data['fields']['override_hostid'])
		: null
	);

$items_field = (new CWidgetFieldPatternSelectItemView($data['fields']['items']))
	->setFilterPreselect($host_field !== null
		? [
			'id' => $host_field->getId(),
			'accept' => CMultiSelect::FILTER_PRESELECT_ACCEPT_ID,
			'submit_as' => 'hostid'
		]
		: []
	);

$form
	->addField($groupids_field)
	->addField($host_field)
	->addField(array_key_exists('evaltype_host', $data['fields'])
		? new CWidgetFieldRadioButtonListView($data['fields']['evaltype_host'])
		: null
	)
	->addField(array_key_exists('host_tags', $data['fields'])
		? new CWidgetFieldTagsView($data['fields']['host_tags'])
		: null
	)
	->addField($items_field)
	->addField(new CWidgetFieldRadioButtonListView($data['fields']['evaltype_item']))
	->addField(new CWidgetFieldTagsView($data['fields']['item_tags']))
	->addField(new CWidgetFieldCheckBoxView($data['fields']['maintenance']))
	->addField(new CWidgetFieldThresholdsView($data['fields']['thresholds']))
	->includeJsFile('widget.edit.js.php')
	->initFormJs('widget_form.init();')
	->show();
