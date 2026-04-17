<?php

namespace Facebook\WebDriver;

/**
 * Minimal stub for CI environments that don't vendor the full facebook/webdriver package.
 *
 * SeleniumTests.php only checks for class existence; some test code may also reference factory methods.
 */
class WebDriverBy {
	private string $mechanism;
	private string $value;

	private function __construct(string $mechanism, string $value) {
		$this->mechanism = $mechanism;
		$this->value = $value;
	}

	public static function id(string $id): self {
		return new self('id', $id);
	}

	public static function xpath(string $xpath): self {
		return new self('xpath', $xpath);
	}

	public static function className(string $class_name): self {
		return new self('class name', $class_name);
	}

	public static function cssSelector(string $css_selector): self {
		return new self('css selector', $css_selector);
	}

	public static function name(string $name): self {
		return new self('name', $name);
	}

	public static function linkText(string $link_text): self {
		return new self('link text', $link_text);
	}

	public static function partialLinkText(string $partial_link_text): self {
		return new self('partial link text', $partial_link_text);
	}

	public static function tagName(string $tag_name): self {
		return new self('tag name', $tag_name);
	}
}

