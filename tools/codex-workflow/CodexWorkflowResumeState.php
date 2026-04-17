<?php declare(strict_types=1);
/*
 * Codex Workflow Guard - resume state
 *
 * Writes resume boundary markers in a deterministic JSON format.
 */

final class CodexWorkflowResumeState {
	/**
	 * Expected shape:
	 * {
	 *   "run_id": "...",
	 *   "step_boundary": "resume_from_<N>",
	 *   "next_step_index": <int>,
	 *   "updated_at": "<iso8601>"
	 * }
	 */
	public static function read(string $path): array {
		if (!file_exists($path)) {
			return [];
		}
		$raw = file_get_contents($path);
		if ($raw === false) {
			throw new RuntimeException('Failed to read resume state: ' . $path);
		}
		$data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
		return is_array($data) ? $data : [];
	}

	public static function write(array $state, string $path): void {
		$dir = dirname($path);
		if (!is_dir($dir)) {
			if (!@mkdir($dir, 0775, true) && !is_dir($dir)) {
				throw new RuntimeException('Failed to create resume state dir: ' . $dir);
			}
		}
		$state['updated_at'] = gmdate('c');
		$json = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
		if ($json === false) {
			throw new RuntimeException('Failed to encode resume state JSON');
		}
		if (@file_put_contents($path, $json . "\n") === false) {
			throw new RuntimeException('Failed to write resume state: ' . $path);
		}
	}
}

