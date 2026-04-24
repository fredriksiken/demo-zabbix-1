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

final class CAdHocFilterHelper {

	public static function normalizeContext(array $context): array {
		$context += [
			'groupids' => [],
			'hostids' => [],
			'tags' => [],
			'evaltype' => TAG_EVAL_TYPE_AND_OR,
			'severities' => []
		];

		$context['groupids'] = array_values(array_filter(array_map('strval', (array) $context['groupids']),
			static fn($groupid) => $groupid !== ''
		));
		$context['groupids'] = $context['groupids'] ? array_values(getSubGroups($context['groupids'])) : [];
		$context['hostids'] = array_values(array_filter(array_map('strval', (array) $context['hostids']),
			static fn($hostid) => $hostid !== ''
		));
		$context['severities'] = array_values(array_filter(array_map('intval', (array) $context['severities']),
			static fn($severity) => $severity >= TRIGGER_SEVERITY_NOT_CLASSIFIED
				&& $severity < TRIGGER_SEVERITY_COUNT
		));
		$context['evaltype'] = in_array((int) $context['evaltype'], [TAG_EVAL_TYPE_AND_OR, TAG_EVAL_TYPE_OR], true)
			? (int) $context['evaltype']
			: TAG_EVAL_TYPE_AND_OR;

		$tags = [];
		foreach ((array) $context['tags'] as $tag) {
			if (!is_array($tag)) {
				continue;
			}

			$tag += ['tag' => '', 'value' => '', 'operator' => TAG_OPERATOR_LIKE];

			if ($tag['tag'] === '' && $tag['value'] === '') {
				continue;
			}

			$tags[] = [
				'tag' => (string) $tag['tag'],
				'value' => (string) $tag['value'],
				'operator' => (int) $tag['operator']
			];
		}
		$context['tags'] = $tags;

		return $context;
	}

	public static function hasContext(array $context): bool {
		foreach (['groupids', 'hostids', 'tags', 'severities'] as $key) {
			if (!empty($context[$key])) {
				return true;
			}
		}

		return false;
	}

	public static function mergeGroupIds(?array $values, array $context_groupids): ?array {
		if (!$context_groupids) {
			return $values;
		}

		if ($values === null || $values === []) {
			return array_values($context_groupids);
		}

		$values = array_values(array_intersect($values, $context_groupids));

		return $values ?: [0];
	}

	public static function mergeHostIds(?array $values, array $context_hostids): ?array {
		if (!$context_hostids) {
			return $values;
		}

		if ($values === null || $values === []) {
			return array_values($context_hostids);
		}

		$values = array_values(array_intersect($values, $context_hostids));

		return $values ?: [0];
	}

	public static function mergeSeverities(?array $values, array $context_severities): ?array {
		if (!$context_severities) {
			return $values;
		}

		if ($values === null || $values === []) {
			return array_values($context_severities);
		}

		$values = array_values(array_intersect($values, $context_severities));

		return $values ?: [0];
	}

	public static function mergeTags(?array $values, array $context_tags): ?array {
		if (!$context_tags) {
			return $values;
		}

		if ($values === null || $values === []) {
			return array_values($context_tags);
		}

		return array_values(array_merge($values, $context_tags));
	}

	public static function mergeWidgetFilter(array $filter, array $context): array {
		$context = self::normalizeContext($context);
		$filter_has_tags = !empty($filter['tags']);
		$context_has_tags = !empty($context['tags']);

		if (!$context['groupids'] && !$context['hostids'] && !$context['tags'] && !$context['severities']) {
			return $filter;
		}

		$filter['groupids'] = self::mergeGroupIds($filter['groupids'] ?? null, $context['groupids']);
		$filter['hostids'] = self::mergeHostIds($filter['hostids'] ?? null, $context['hostids']);
		$filter['tags'] = self::mergeTags($filter['tags'] ?? null, $context['tags']);

		if (array_key_exists('severities', $filter)) {
			$filter['severities'] = self::mergeSeverities($filter['severities'] ?? null, $context['severities']);
		}
		else {
			$filter['severities'] = $context['severities'];
		}

		if (!array_key_exists('evaltype', $filter)) {
			$filter['evaltype'] = $context['evaltype'];
		}
		elseif ($filter_has_tags && $context_has_tags) {
			$filter['evaltype'] = TAG_EVAL_TYPE_AND_OR;
		}

		return $filter;
	}
}
