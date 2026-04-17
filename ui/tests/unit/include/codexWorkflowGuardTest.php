<?php declare(strict_types=1);
/*
 * Codex Workflow Guard tests.
 */

require_once __DIR__ . '/../../../../tools/codex-workflow/CodexWorkflowHangDetector.php';
require_once __DIR__ . '/../../../../tools/codex-workflow/CodexWorkflowCleanup.php';
require_once __DIR__ . '/../../../../tools/codex-workflow/CodexWorkflowResumeState.php';

use PHPUnit\Framework\TestCase;

final class codexWorkflowGuardTest extends TestCase {
	public function testDetectHangSoftTimeLimitWithQemuContext(): void {
		$log = "Execution stopped because CUSTOM_X failed.\nSoftTimeLimitExceeded()\nCodex CLI failed in QEMU for label CUSTOM_X\n";
		$this->assertTrue(CodexWorkflowHangDetector::detectHang($log));
	}

	public function testDetectHangIgnoresGenericTimeoutWithoutQemu(): void {
		$log = "Execution stopped because CUSTOM_X failed.\ntimeout after 30s\n";
		$this->assertFalse(CodexWorkflowHangDetector::detectHang($log));
	}

	public function testCleanupIdempotentWhenTargetsMissing(): void {
		$tmp = sys_get_temp_dir() . '/codex_guard_' . uniqid('', true);
		$this->assertTrue(@mkdir($tmp, 0775, true));

		$report = CodexWorkflowCleanup::cleanup(
			[
				['path' => $tmp . '/does_not_exist', 'kind' => 'dir'],
				['path' => $tmp . '/does_not_exist.lock', 'kind' => 'file'],
			],
			$tmp,
			false
		);

		$this->assertTrue($report['ok']);
		foreach (($report['reports'] ?? []) as $r) {
			$this->assertTrue($r['ok']);
		}
		$this->assertTrue($this->deleteDir($tmp));
	}

	public function testCleanupReportsFailureWhenRmdirFails(): void {
		$tmp = sys_get_temp_dir() . '/codex_guard_' . uniqid('', true);
		$this->assertTrue(@mkdir($tmp, 0775, true));

		$dir = $tmp . '/child';
		$this->assertTrue(@mkdir($dir, 0775, true));

		$file = $dir . '/locked';
		// Create a file; unlink will still fail because we remove write permission on the directory.
		$this->assertNotFalse(@file_put_contents($file, 'x'));

		// Remove write permission so cleanup can't unlink the child file (hence rmdir fails too).
		@chmod($dir, 0555);

		$report = CodexWorkflowCleanup::cleanup(
			[['path' => $dir, 'kind' => 'dir']],
			$tmp,
			false
		);
		$this->assertFalse($report['ok']);
		foreach (($report['reports'] ?? []) as $r) {
			$this->assertFalse($r['ok']);
		}

		// Ensure we can still delete the temp tree after the assertion.
		$this->forceDeleteDir($tmp);
	}

	private function deleteDir(string $dir): bool {
		if (!is_dir($dir)) {
			return true;
		}
		$items = @scandir($dir);
		if ($items === false) {
			return false;
		}
		foreach ($items as $it) {
			if ($it === '.' || $it === '..') {
				continue;
			}
			$path = $dir . '/' . $it;
			if (is_dir($path)) {
				if (!$this->deleteDir($path)) {
					return false;
				}
			}
			else {
				@unlink($path);
			}
		}
		return @rmdir($dir);
	}

	private function forceDeleteDir(string $dir): void {
		if (!is_dir($dir)) {
			return;
		}
		$items = @scandir($dir);
		if ($items === false) {
			return;
		}
		foreach ($items as $it) {
			if ($it === '.' || $it === '..') {
				continue;
			}
			$path = $dir . '/' . $it;
			if (is_dir($path)) {
				$this->forceDeleteDir($path);
			}
			@chmod($path, 0777);
			@unlink($path);
		}
		@chmod($dir, 0777);
		@rmdir($dir);
	}
}
