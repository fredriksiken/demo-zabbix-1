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
use Widgets\Emoji\Includes\CWidgetFieldEmojiThresholds;

require_once __DIR__.'/../../../../../../widgets/emoji/includes/CWidgetFieldEmojiThresholds.php';

class CWidgetFieldEmojiThresholdsTest extends TestCase {

	public function testValidateSortsAndKeepsConfiguredRows(): void {
		$field = new CWidgetFieldEmojiThresholds('thresholds', 'Thresholds');
		$field->setValue([
			['threshold' => '80', 'emoji' => '😄'],
			['threshold' => '10', 'emoji' => '😐']
		]);

		$this->assertSame([], $field->validate());
		$this->assertSame([
			['threshold' => '10', 'emoji' => '😐'],
			['threshold' => '80', 'emoji' => '😄']
		], $field->getValue());
	}

	public function testValidateRejectsMissingEmoji(): void {
		$field = new CWidgetFieldEmojiThresholds('thresholds', 'Thresholds');
		$field->setValue([
			['threshold' => '10', 'emoji' => '']
		]);

		$this->assertNotEmpty($field->validate(true));
	}
}
