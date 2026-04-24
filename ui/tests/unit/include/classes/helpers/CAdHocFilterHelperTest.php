<?php declare(strict_types = 0);
/*
** Copyright (C) 2001-2026 Zabbix SIA
**
** This program is free software: you can redistribute it and/or modify it under the terms of
** the GNU Affero General Public License as published by the Free Software Foundation, version 3.
**
** This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the
** implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public
** License for more details.
**
** You should have received a copy of the GNU Affero General Public License along with this program.
** If not, see <https://www.gnu.org/licenses/>.
**/

use PHPUnit\Framework\TestCase;

class CAdHocFilterHelperTest extends TestCase {

	public function testNormalizeContext(): void {
		$context = CAdHocFilterHelper::normalizeContext([
			'groupids' => ['1', '', '2'],
			'hostids' => ['3', null, '4'],
			'tags' => [
				['tag' => 'env', 'value' => 'prod', 'operator' => TAG_OPERATOR_EQUAL],
				['tag' => '', 'value' => ''],
				'not-a-tag'
			],
			'evaltype' => TAG_EVAL_TYPE_OR,
			'severities' => ['0', '1', '99']
		]);

		$this->assertSame(['1', '2'], $context['groupids']);
		$this->assertSame(['3', '4'], $context['hostids']);
		$this->assertSame([
			['tag' => 'env', 'value' => 'prod', 'operator' => TAG_OPERATOR_EQUAL]
		], $context['tags']);
		$this->assertSame(TAG_EVAL_TYPE_OR, $context['evaltype']);
		$this->assertSame([0, 1], $context['severities']);
	}

	public function testMergeWidgetFilter(): void {
		$filter = CAdHocFilterHelper::mergeWidgetFilter([
			'groupids' => ['10', '20'],
			'hostids' => ['30'],
			'severities' => [TRIGGER_SEVERITY_AVERAGE, TRIGGER_SEVERITY_HIGH],
			'evaltype' => TAG_EVAL_TYPE_AND_OR,
			'tags' => [
				['tag' => 'role', 'value' => 'db', 'operator' => TAG_OPERATOR_EQUAL]
			]
		], [
			'groupids' => ['20', '40'],
			'hostids' => ['30', '50'],
			'severities' => [TRIGGER_SEVERITY_HIGH, TRIGGER_SEVERITY_DISASTER],
			'tags' => [
				['tag' => 'env', 'value' => 'prod', 'operator' => TAG_OPERATOR_LIKE]
			],
			'evaltype' => TAG_EVAL_TYPE_OR
		]);

		$this->assertSame(['20'], $filter['groupids']);
		$this->assertSame(['30'], $filter['hostids']);
		$this->assertSame([TRIGGER_SEVERITY_HIGH], $filter['severities']);
		$this->assertSame(TAG_EVAL_TYPE_AND_OR, $filter['evaltype']);
		$this->assertSame([
			['tag' => 'role', 'value' => 'db', 'operator' => TAG_OPERATOR_EQUAL],
			['tag' => 'env', 'value' => 'prod', 'operator' => TAG_OPERATOR_LIKE]
		], $filter['tags']);
	}

	public function testMergeWidgetFilterKeepsTagFiltersConstrainedWhenWidgetUsesOr(): void {
		$filter = CAdHocFilterHelper::mergeWidgetFilter([
			'groupids' => [],
			'hostids' => [],
			'evaltype' => TAG_EVAL_TYPE_OR,
			'tags' => [
				['tag' => 'role', 'value' => 'db', 'operator' => TAG_OPERATOR_EQUAL]
			]
		], [
			'tags' => [
				['tag' => 'env', 'value' => 'prod', 'operator' => TAG_OPERATOR_EQUAL]
			],
			'evaltype' => TAG_EVAL_TYPE_OR
		]);

		$this->assertSame(TAG_EVAL_TYPE_AND_OR, $filter['evaltype']);
		$this->assertSame([
			['tag' => 'role', 'value' => 'db', 'operator' => TAG_OPERATOR_EQUAL],
			['tag' => 'env', 'value' => 'prod', 'operator' => TAG_OPERATOR_EQUAL]
		], $filter['tags']);
	}

	public function testMergeWidgetFilterReturnsImpossibleMatchOnEmptyIntersection(): void {
		$filter = CAdHocFilterHelper::mergeWidgetFilter([
			'groupids' => ['10'],
			'hostids' => ['30'],
			'severities' => [TRIGGER_SEVERITY_HIGH]
		], [
			'groupids' => ['20'],
			'hostids' => ['40'],
			'severities' => [TRIGGER_SEVERITY_DISASTER]
		]);

		$this->assertSame([0], $filter['groupids']);
		$this->assertSame([0], $filter['hostids']);
		$this->assertSame([0], $filter['severities']);
	}
}
