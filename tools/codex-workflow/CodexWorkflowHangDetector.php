<?php declare(strict_types=1);
/*
 * Codex Workflow Guard - hang detector
 *
 * Detects Codex QEMU timeout/hung workflow signatures from a runner log.
 * This is intentionally heuristic and must stay deterministic.
 */

final class CodexWorkflowHangDetector {
	public const HANG_MARKERS = [
		// Canonical timeout/hang signal surfaced in backlog logs.
		'SoftTimeLimitExceeded()',
		'SoftTimeLimitExceeded',
		// Generic timeout language.
		'timeout',
		'timed out',
		'QEMU timeout',
		'QEMU',
		// Generic hang language.
		'hung workflow',
		'hang',
	];

	/**
	 * Returns true when the log contains at least one hang/timeout marker plus a QEMU/workflow context marker.
	 */
	public static function detectHang(string $logText): bool {
		$needle = strtolower($logText);

		// Timeout/hang indicator.
		$hasTimeout = self::containsAny($needle, [
			'softtimelimitexceeded',
			'timed out',
			'timeout',
			'execution stopped because',
		]);

		// Context marker.
		$hasQemuContext = self::containsAny($needle, [
			'qemu',
			'codex cli failed in qemu',
			'hung workflow',
		]);

		// If we only see generic "timeout" without context, prefer false positives avoidance.
		return $hasTimeout && $hasQemuContext;
	}

	/**
	 * @return array{failure_signature: string, markers: string[]}
	 */
	public static function extractFailureSignature(string $logText): array {
		$matches = [];
		$lower = strtolower($logText);
		foreach (self::HANG_MARKERS as $m) {
			$ml = strtolower($m);
			if ($ml !== '' && str_contains($lower, $ml)) {
				$matches[] = $m;
			}
		}

		// Prefer explicit "Execution stopped because ..." substring when present.
		$signature = 'unknown';
		if (preg_match('/Execution stopped because.*$/m', $logText, $m)) {
			$signature = trim($m[0]);
		}
		elseif (preg_match('/SoftTimeLimitExceeded.*$/m', $logText, $m)) {
			$signature = trim($m[0]);
		}

		return ['failure_signature' => $signature, 'markers' => $matches];
	}

	private static function containsAny(string $haystackLower, array $needlesLower): bool {
		foreach ($needlesLower as $n) {
			$n = strtolower($n);
			if ($n !== '' && str_contains($haystackLower, $n)) {
				return true;
			}
		}
		return false;
	}
}

