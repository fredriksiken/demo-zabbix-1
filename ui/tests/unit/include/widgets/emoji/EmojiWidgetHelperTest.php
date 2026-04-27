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


use PHPUnit\Framework\TestCase;
use Widgets\Emoji\Includes\EmojiWidgetHelper;

require_once __DIR__.'/../../../../../widgets/emoji/includes/EmojiWidgetHelper.php';

class EmojiWidgetHelperTest extends TestCase {

	public function testNormalizeThresholdsSortsRowsAndDropsBlanks(): void {
		$thresholds = EmojiWidgetHelper::normalizeThresholds([
			['threshold' => ' 80 ', 'emoji' => '😄'],
			['threshold' => '', 'emoji' => ''],
			['threshold' => '10', 'emoji' => '😐']
		]);

		$this->assertSame([
			['threshold' => '10', 'emoji' => '😐'],
			['threshold' => '80', 'emoji' => '😄']
		], $thresholds);
	}

	/**
	 * @dataProvider providerSelectThreshold
	 */
	public function testSelectThreshold(array $thresholds, float $value, ?string $expected_emoji): void {
		$selected = EmojiWidgetHelper::selectThreshold($thresholds, $value);

		$this->assertSame($expected_emoji, $selected['emoji'] ?? null);
	}

	public static function providerSelectThreshold(): array {
		return [
			'no match below first boundary' => [
				[['threshold' => '10', 'emoji' => '😐'], ['threshold' => '80', 'emoji' => '😄']],
				5,
				null
			],
			'exact boundary' => [
				[['threshold' => '10', 'emoji' => '😐'], ['threshold' => '80', 'emoji' => '😄']],
				10,
				'😐'
			],
			'highest matching boundary wins' => [
				[['threshold' => '10', 'emoji' => '😐'], ['threshold' => '80', 'emoji' => '😄']],
				72,
				'😐'
			],
			'last boundary' => [
				[['threshold' => '10', 'emoji' => '😐'], ['threshold' => '80', 'emoji' => '😄']],
				90,
				'😄'
			],
			'exact upper boundary' => [
				[['threshold' => '10', 'emoji' => '😐'], ['threshold' => '80', 'emoji' => '😄']],
				80,
				'😄'
			]
		];
	}
}
