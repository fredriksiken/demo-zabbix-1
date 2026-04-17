#!/usr/bin/env php
<?php declare(strict_types=1);
/*
 * Codex Workflow Guard entrypoint.
 *
 * This script is a repo-managed helper that:
 * - detects whether a run failed due to Codex QEMU timeout/hang
 * - runs idempotent cleanup of stale run-scoped artifacts
 * - writes resume boundary state so the next resume continues from the correct step boundary
 */

require_once __DIR__ . '/CodexWorkflowHangDetector.php';
require_once __DIR__ . '/CodexWorkflowCleanup.php';
require_once __DIR__ . '/CodexWorkflowResumeState.php';

function usage(): void {
	fwrite(STDERR, "Usage: php run_guard.php --run-root=<dir> --log-path=<path> --resume-state=<path> [--dry-run]\n");
	exit(2);
}

$opts = getopt('', ['run-root:', 'log-path:', 'resume-state:', 'dry-run']);
if (!is_array($opts) || empty($opts['run-root']) || empty($opts['log-path']) || empty($opts['resume-state'])) {
	usage();
}

$runRoot = (string) $opts['run-root'];
$logPath = (string) $opts['log-path'];
$resumeStatePath = (string) $opts['resume-state'];
$dryRun = array_key_exists('dry-run', $opts);

$logText = file_exists($logPath) ? (string) file_get_contents($logPath) : '';
if ($logText === '') {
	// Still write resume state (no cleanup) so callers can proceed deterministically.
	$state = CodexWorkflowResumeState::read($resumeStatePath);
	if ($state === []) {
		$state = ['run_id' => basename($runRoot), 'step_boundary' => 'resume_from_0', 'next_step_index' => 0];
	}
	$state['hang_detected'] = false;
	CodexWorkflowResumeState::write($state, $resumeStatePath);
	exit(0);
}

$hang = CodexWorkflowHangDetector::detectHang($logText);
$state = CodexWorkflowResumeState::read($resumeStatePath);
if ($state === []) {
	$state = ['run_id' => basename($runRoot), 'step_boundary' => 'resume_from_0', 'next_step_index' => 0];
}

if ($hang) {
	$sig = CodexWorkflowHangDetector::extractFailureSignature($logText);

	// Default cleanup targets are run-scoped and idempotent.
	$targets = [
		['path' => $runRoot . '/.launcher/codex-state', 'kind' => 'codex_state_dir'],
		['path' => $runRoot . '/.launcher/tmp/codex', 'kind' => 'codex_tmp_dir'],
		['path' => $runRoot . '/.codex_runner_*.sh', 'kind' => 'runner_scripts_glob'],
		['path' => $runRoot . '/.launcher/workspace-state', 'kind' => 'workspace_state_dir'],
	];

	// Expand glob patterns inside run root.
	$expanded = [];
	foreach ($targets as $t) {
		$p = $t['path'];
		if (str_contains($p, '*')) {
			$globMatches = glob($p, GLOB_NOSORT) ?: [];
			foreach ($globMatches as $m) {
				$expanded[] = ['path' => $m, 'kind' => $t['kind']];
			}
		}
		else {
			$expanded[] = $t;
		}
	}

	$cleanupReport = CodexWorkflowCleanup::cleanup($expanded, $runRoot, $dryRun);
	$state['hang_detected'] = true;
	$state['hang_failure_signature'] = $sig['failure_signature'];
	$state['cleanup'] = $cleanupReport;

	// Resume boundary safety:
	// If we detected a hang, the next resume should continue from the recorded step_boundary,
	// not from "step 0". We treat "next_step_index" as already computed by the runner.
	// Here we only ensure it's not reset to 0 on failure.
	if (!isset($state['next_step_index']) || !is_int($state['next_step_index'])) {
		$state['next_step_index'] = 1;
	}
}
else {
	$state['hang_detected'] = false;
}

CodexWorkflowResumeState::write($state, $resumeStatePath);
exit(0);
