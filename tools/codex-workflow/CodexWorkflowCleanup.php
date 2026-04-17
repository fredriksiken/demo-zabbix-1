<?php declare(strict_types=1);
/*
 * Codex Workflow Guard - cleanup
 */

final class CodexWorkflowCleanup {
	/**
	 * @param array<int, array{path: string, kind?: string}> $targets
	 */
	public static function cleanup(array $targets, string $runRootDir, bool $dryRun = false): array {
		$reports = [];
		$runRootDir = rtrim($runRootDir, '/');

		foreach ($targets as $t) {
			$path = $t['path'] ?? '';
			$kind = $t['kind'] ?? 'path';
			if ($path === '') {
				$reports[] = ['target' => $t, 'ok' => true, 'skipped' => true, 'reason' => 'empty_path'];
				continue;
			}

			// Safety: never delete outside run root.
			//
			// CI may pass `--run-root` as a symlink path, while child targets resolve to real
			// paths. If we only compare normalized string paths, we can mistakenly skip deletion
			// and leave stale workflow state behind. Use `realpath()` when possible but fall
			// back to normalization to keep behavior deterministic when paths don't exist.
			$absNorm = self::normalizePath($path);
			$rootAbsNorm = self::normalizePath($runRootDir);
			$rootAbs = realpath($runRootDir);
			$absResolved = realpath($absNorm);

			// Containment must be evaluated against:
			// - the non-resolved (symlink-preserving) path, so we can delete symlinks that live
			//   under `runRoot` even if the symlink target resolves outside.
			// - the resolved path, when both resolve cleanly.
			$withinNorm = $absNorm === $rootAbsNorm || str_starts_with($absNorm, $rootAbsNorm . '/');
			$withinResolved = false;
			if ($rootAbs !== false && $absResolved !== false) {
				$withinResolved = $absResolved === $rootAbs || str_starts_with($absResolved, $rootAbs . '/');
			}
			$within = $withinNorm || $withinResolved;
			if (!$within) {
				$reports[] = ['target' => $t, 'ok' => false, 'skipped' => true, 'reason' => 'outside_run_root'];
				continue;
			}

			// Use the non-resolved path for existence checks and deletion so that
			// symlinks are unlinked at the run-root path rather than deleting their
			// resolved targets elsewhere.
			$exists = file_exists($absNorm) || is_link($absNorm);
			if (!$exists) {
				$reports[] = ['target' => $t, 'ok' => true, 'skipped' => true, 'reason' => 'missing'];
				continue;
			}

			if ($dryRun) {
				$reports[] = ['target' => $t, 'ok' => true, 'skipped' => false, 'reason' => 'dry_run'];
				continue;
			}

			$ok = self::deleteRecursively($absNorm);
			$reports[] = ['target' => $t, 'ok' => $ok, 'skipped' => false, 'reason' => $ok ? 'deleted' : 'delete_failed'];
		}

		return ['ok' => self::allOk($reports), 'reports' => $reports];
	}

	private static function allOk(array $reports): bool {
		foreach ($reports as $r) {
			if (($r['ok'] ?? false) !== true) {
				return false;
			}
		}
		return true;
	}

	private static function normalizePath(string $path): string {
		// Normalize without requiring existence.
		$path = str_replace('\\', '/', $path);
		$path = preg_replace('#/+#', '/', $path);
		$cwd = getcwd() ?: '';
		if ($path === '') {
			return $cwd;
		}
		if ($path[0] !== '/' && !preg_match('/^[A-Za-z]:/', $path)) {
			$path = rtrim($cwd, '/') . '/' . $path;
		}
		$parts = [];
		foreach (explode('/', $path) as $p) {
			if ($p === '' || $p === '.') {
				continue;
			}
			if ($p === '..') {
				array_pop($parts);
				continue;
			}
			$parts[] = $p;
		}
		return '/' . implode('/', $parts);
	}

	private static function deleteRecursively(string $path): bool {
		if (is_link($path) || is_file($path)) {
			return @unlink($path);
		}
		if (is_dir($path)) {
			$hadFailures = false;
			$items = @scandir($path);
			if ($items === false) {
				return false;
			}
			foreach ($items as $it) {
				if ($it === '.' || $it === '..') {
					continue;
				}
				if (!self::deleteRecursively($path . '/' . $it)) {
					// Continue attempting but remember failure.
					$hadFailures = true;
				}
			}

			$rmdirOk = @rmdir($path);
			// If children were successfully deleted but rmdir failed due to permissions or
			// race conditions, treat that as a failure so callers can react deterministically.
			if ($hadFailures) {
				return false;
			}
			if ($rmdirOk) {
				return true;
			}
			// If the directory already disappeared between scandir and rmdir, consider it OK.
			return !is_dir($path) && !file_exists($path);
		}

		return true;
	}
}
