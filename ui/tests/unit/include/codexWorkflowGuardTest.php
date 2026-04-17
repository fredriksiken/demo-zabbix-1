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

	public function testDetectHangAcceptsTimeoutWithWorkflowContextWithoutQemu(): void {
		$log = "Execution stopped because CUSTOM_X failed.\nSoftTimeLimitExceeded()\nCodex workflow stalled on worker stale-run.\n";
		$this->assertTrue(CodexWorkflowHangDetector::detectHang($log));
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

	public function testCleanupDeletesSymlinkWithinRunRootEvenIfTargetOutside(): void {
		$tmp = sys_get_temp_dir() . '/codex_guard_symlink_outside_' . uniqid('', true);
		$this->assertTrue(@mkdir($tmp, 0775, true));

		$runRoot = $tmp . '/runroot';
		$outside = $tmp . '/outside';
		$this->assertTrue(@mkdir($runRoot . '/.launcher', 0775, true));
		$this->assertTrue(@mkdir($outside, 0775, true));

		// Put a sentinel in the outside directory; cleanup must not follow the symlink
		// and delete this real directory.
		$outsideSentinel = $outside . '/sentinel.txt';
		$this->assertSame(1, file_put_contents($outsideSentinel, 'x'));

		$symlinkTarget = $outside;
		$symlinkPath = $runRoot . '/.launcher/codex-state';
		@unlink($symlinkPath);
		if (!@symlink($symlinkTarget, $symlinkPath)) {
			$this->markTestSkipped('Symlink creation failed in this environment.');
		}

		$report = CodexWorkflowCleanup::cleanup(
			[['path' => $symlinkPath, 'kind' => 'dir']],
			$runRoot,
			false
		);
		$this->assertTrue($report['ok']);

		// The symlink should be removed from inside runRoot.
		$this->assertFalse(file_exists($symlinkPath) || is_link($symlinkPath));
		// The outside directory must remain untouched.
		$this->assertFileExists($outsideSentinel);

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
