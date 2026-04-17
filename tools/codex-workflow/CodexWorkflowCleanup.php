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
			$abs = self::normalizePath($path);
			$rootAbs = self::normalizePath($runRootDir);
			$within = $abs === $rootAbs || str_starts_with($abs, $rootAbs . '/');
			if (!$within) {
				$reports[] = ['target' => $t, 'ok' => false, 'skipped' => true, 'reason' => 'outside_run_root'];
				continue;
			}

			$exists = file_exists($abs) || is_link($abs);
			if (!$exists) {
				$reports[] = ['target' => $t, 'ok' => true, 'skipped' => true, 'reason' => 'missing'];
				continue;
			}

			if ($dryRun) {
				$reports[] = ['target' => $t, 'ok' => true, 'skipped' => false, 'reason' => 'dry_run'];
				continue;
			}

			$ok = self::deleteRecursively($abs);
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
					$failed = true;
				}
			}
			$failed = $failed ?? false;
			@rmdir($path);
			return !$failed;
		}

		return true;
	}
}

