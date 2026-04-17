<?php declare(strict_types=1);
/*
 * Codex Workflow Guard integration-style simulation.
 */

require_once __DIR__ . '/../../../../tools/codex-workflow/CodexWorkflowHangDetector.php';
require_once __DIR__ . '/../../../../tools/codex-workflow/CodexWorkflowCleanup.php';
require_once __DIR__ . '/../../../../tools/codex-workflow/CodexWorkflowResumeState.php';

use PHPUnit\Framework\TestCase;

final class codexWorkflowGuardIntegrationTest extends TestCase {
	public function testGuardCleansRunScopedArtifactsAndPreservesResumeIndex(): void {
		$tmp = sys_get_temp_dir() . '/codex_guard_it_' . uniqid('', true);
		$this->assertTrue(@mkdir($tmp . '/.launcher/codex-state', 0775, true));
		$this->assertTrue(@mkdir($tmp . '/.launcher/tmp/codex', 0775, true));
		$this->assertTrue(@mkdir($tmp . '/.launcher/workspace-state', 0775, true));

		// Artifact that must be deleted by cleanup.
		$this->assertSame(1, file_put_contents($tmp . '/.launcher/codex-state/sentinel.txt', 'x'));
		$this->assertSame(1, file_put_contents($tmp . '/.launcher/tmp/codex/sentinel2.txt', 'y'));

			// Runner script glob match.
			$this->assertSame(strlen('echo hi'),
				file_put_contents($tmp . '/.codex_runner_custom-add-dashboard-widget-for-b4ef-implement_123.sh', 'echo hi'));

		$logPath = $tmp . '/run.log';
		$logText = "Execution stopped because CUSTOM_X failed.\nSoftTimeLimitExceeded()\nCodex CLI failed in QEMU for label CUSTOM_X\n";
		$this->assertSame(strlen($logText), file_put_contents($logPath, $logText));

		$resumeState = [
			'run_id' => 'fake_run',
			'step_boundary' => 'resume_from_3',
			// Some upstreams persist numeric step indexes as strings.
			'next_step_index' => '7',
		];
			$resumeStatePath = $tmp . '/resume_state.json';
			$this->assertTrue(is_dir(dirname($resumeStatePath)) || @mkdir(dirname($resumeStatePath), 0775, true));
			$this->assertNotFalse(file_put_contents($resumeStatePath, json_encode($resumeState)));

		$cmd = 'php ' . escapeshellarg(__DIR__ . '/../../../../tools/codex-workflow/run_guard.php')
			. ' --run-root=' . escapeshellarg($tmp)
			. ' --log-path=' . escapeshellarg($logPath)
			. ' --resume-state=' . escapeshellarg($resumeStatePath);

		$execOut = [];
		$rc = 0;
		// phpcs:ignore Squiz.PHP.LooksLikeFunctionCall.CallNotAllowed
		exec($cmd, $execOut, $rc);
		$this->assertSame(0, $rc);

		// Cleanup targets removed.
		$this->assertFileDoesNotExist($tmp . '/.launcher/codex-state/sentinel.txt');
		$this->assertFileDoesNotExist($tmp . '/.launcher/tmp/codex/sentinel2.txt');
		$this->assertFileDoesNotExist($tmp . '/.codex_runner_custom-add-dashboard-widget-for-b4ef-implement_123.sh');

		// Resume state preserved.
		$stateRaw = file_get_contents($resumeStatePath);
		$this->assertNotFalse($stateRaw);
		$state = json_decode($stateRaw, true, 512, JSON_THROW_ON_ERROR);

		$this->assertTrue($state['hang_detected']);
		$this->assertSame(7, $state['next_step_index']);
		$this->assertSame('resume_from_3', $state['step_boundary']);
		$this->assertNotSame('unknown', $state['hang_failure_signature'] ?? null);
	}

	public function testGuardCleansRunScopedArtifactsWhenRunRootIsSymlink(): void {
		$realRoot = sys_get_temp_dir() . '/codex_guard_symlink_real_' . uniqid('', true);
		$this->assertTrue(@mkdir($realRoot . '/.launcher/codex-state', 0775, true));
		$this->assertTrue(@mkdir($realRoot . '/.launcher/tmp/codex', 0775, true));
		$this->assertTrue(@mkdir($realRoot . '/.launcher/workspace-state', 0775, true));

		$this->assertSame(1, file_put_contents($realRoot . '/.launcher/codex-state/sentinel.txt', 'x'));
		$this->assertSame(1, file_put_contents($realRoot . '/.launcher/tmp/codex/sentinel2.txt', 'y'));

		$logPath = $realRoot . '/run.log';
		$logText = "Execution stopped because CUSTOM_X failed.\nSoftTimeLimitExceeded()\nCodex CLI failed in QEMU for label CUSTOM_X\n";
		$this->assertSame(strlen($logText), file_put_contents($logPath, $logText));

		$linkRoot = sys_get_temp_dir() . '/codex_guard_symlink_link_' . uniqid('', true);
		@unlink($linkRoot);
		if (!@symlink($realRoot, $linkRoot)) {
			$this->markTestSkipped('Symlink creation failed in this environment.');
		}

		$resumeStatePath = $realRoot . '/resume_state.json';
		$resumeState = [
			'run_id' => 'fake_run',
			'step_boundary' => 'resume_from_3',
			'next_step_index' => 7,
		];
		$this->assertTrue(is_dir(dirname($resumeStatePath)) || @mkdir(dirname($resumeStatePath), 0775, true));
		$this->assertNotFalse(file_put_contents($resumeStatePath, json_encode($resumeState)));

		$cmd = 'php ' . escapeshellarg(__DIR__ . '/../../../../tools/codex-workflow/run_guard.php')
			. ' --run-root=' . escapeshellarg($linkRoot)
			. ' --log-path=' . escapeshellarg($logPath)
			. ' --resume-state=' . escapeshellarg($resumeStatePath);

		$execOut = [];
		$rc = 0;
		// phpcs:ignore Squiz.PHP.LooksLikeFunctionCall.CallNotAllowed
		exec($cmd, $execOut, $rc);
		$this->assertSame(0, $rc);

		// Cleanup targets removed (under the real root).
		$this->assertFileDoesNotExist($realRoot . '/.launcher/codex-state/sentinel.txt');
		$this->assertFileDoesNotExist($realRoot . '/.launcher/tmp/codex/sentinel2.txt');
		$this->assertFileDoesNotExist($realRoot . '/.launcher/workspace-state');

		// Resume state preserved.
		$stateRaw = file_get_contents($resumeStatePath);
		$this->assertNotFalse($stateRaw);
		$state = json_decode($stateRaw, true, 512, JSON_THROW_ON_ERROR);
		$this->assertTrue($state['hang_detected']);
		$this->assertSame(7, $state['next_step_index']);
	}
}
