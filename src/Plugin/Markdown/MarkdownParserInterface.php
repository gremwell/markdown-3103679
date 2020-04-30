<?php

namespace Drupal\markdown\Plugin\Markdown;

use Drupal\Core\Language\LanguageInterface;

/**
 * Interface MarkdownInterface.
 */
interface MarkdownParserInterface extends MarkdownInstallablePluginInterface {

  /**
   * Converts Markdown into HTML.
   *
   * Note: this method is not guaranteed to be safe from XSS attacks. This
   * returns the raw output from the parser itself. If you need to render
   * this output you should wrap it in a ParsedMarkdown object or use the
   * \Drupal\markdown\Plugin\Markdown\MarkdownParserInterface::parse() method
   * instead.
   *
   * @param string $markdown
   *   The markdown string to convert.
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   Optional. The language of the text that is being converted.
   *
   * @return string
   *   The raw parsed HTML returned from the parser.
   *
   * @see \Drupal\markdown\ParsedMarkdownInterface
   * @see \Drupal\markdown\Plugin\Markdown\MarkdownParserInterface::parse()
   */
  public function convertToHtml($markdown, LanguageInterface $language = NULL);

  /**
   * Retrieves a short summary of what the MarkdownParser does.
   *
   * @return array
   *   A render array.
   */
  public function getSummary();

  /**
   * Parses markdown into HTML.
   *
   * @param string $markdown
   *   The markdown string to parse.
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   Optional. The language of the markdown that is being parsed.
   *
   * @return \Drupal\markdown\ParsedMarkdownInterface
   *   A safe ParsedMarkdown object.
   *
   * @see \Drupal\markdown\ParsedMarkdownInterface
   * @see \Drupal\markdown\Plugin\Markdown\MarkdownParserInterface::convertToHtml()
   */
  public function parse($markdown, LanguageInterface $language = NULL);

}
