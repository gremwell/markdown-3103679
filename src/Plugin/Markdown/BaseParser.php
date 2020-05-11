<?php

namespace Drupal\markdown\Plugin\Markdown;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\RefinableCacheableDependencyTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Url;
use Drupal\filter\Entity\FilterFormat;
use Drupal\filter\Plugin\FilterInterface;
use Drupal\markdown\Config\ImmutableMarkdownConfig;
use Drupal\markdown\Render\ParsedMarkdown;
use Drupal\markdown\Traits\FilterAwareTrait;
use Drupal\markdown\Traits\SettingsTrait;
use Drupal\markdown\Util\FilterAwareInterface;
use Drupal\markdown\Util\FilterFormatAwareInterface;
use Drupal\markdown\Util\FilterHtml;
use Drupal\markdown\Util\ParserAwareInterface;

/**
 * Base class form Markdown Parser instances.
 *
 * @property \Drupal\markdown\Config\ImmutableMarkdownConfig $config
 */
abstract class BaseParser extends InstallablePluginBase implements FilterAwareInterface, ParserInterface, MarkdownGuidelinesInterface, PluginFormInterface {

  use FilterAwareTrait;
  use RefinableCacheableDependencyTrait;
  use SettingsTrait {
    getConfiguration as getConfigurationTrait;
  }

  /**
   * Converts Markdown into HTML.
   *
   * Note: this method is not guaranteed to be safe from XSS attacks. This
   * returns the raw output from the parser itself.
   *
   * If you need to render this output you should use the
   * \Drupal\markdown\Plugin\Markdown\MarkdownParserInterface::parse()
   * method instead.
   *
   * @param string $markdown
   *   The markdown string to convert.
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   Optional. The language of the text that is being converted.
   *
   * @return string
   *   The raw parsed HTML returned from the parser.
   *
   * @see \Drupal\markdown\Render\ParsedMarkdownInterface
   * @see \Drupal\markdown\Plugin\Markdown\ParserInterface::parse()
   *
   * @internal
   */
  abstract protected function convertToHtml($markdown, LanguageInterface $language = NULL);

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllowedHtml() {
    return $this->config()->get('render_strategy.allowed_html') ?: FilterHtml::ALLOWED_HTML;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllowedHtmlPlugins() {
    return $this->config()->get('render_strategy.plugins') ?: [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getConfigClass() {
    return ImmutableMarkdownConfig::class;
  }

  /**
   * {@inheritdoc}
   */
  protected function getConfigType() {
    return 'markdown_parser';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    $configuration = $this->getConfigurationTrait();

    $renderStrategy = $this->getRenderStrategy();
    $configuration['render_strategy'] = ['type' => $renderStrategy];
    if ($renderStrategy === static::FILTER_OUTPUT) {
      $configuration['render_strategy']['allowed_html'] = $this->getAllowedHtml();
      $configuration['render_strategy']['plugins'] = $this->getAllowedHtmlPlugins();
    }
    return $configuration;
  }

  /**
   * Builds context around a markdown parser's hierarchy filter format chain.
   *
   * @param array $context
   *   Additional context to pass.
   *
   * @return array
   *   The context, including references to various parser and filter instances.
   */
  protected function getContext(array $context = []) {
    $parser = NULL;
    if ($this instanceof ParserAwareInterface) {
      $parser = $this->getParser();
    }
    elseif ($this instanceof ParserInterface) {
      $parser = $this;
    }

    $filter = NULL;
    if ($this instanceof FilterAwareInterface) {
      $filter = $this->getFilter();
    }
    elseif ($parser instanceof FilterAwareInterface) {
      $filter = $parser->getFilter();
    }
    elseif ($this instanceof FilterInterface) {
      $filter = $this;
    }

    $format = NULL;
    if ($this instanceof FilterFormatAwareInterface) {
      $format = $this->getFilterFormat();
    }
    elseif ($parser instanceof FilterFormatAwareInterface) {
      $format = $parser->getFilterFormat();
    }
    elseif ($filter instanceof FilterFormatAwareInterface) {
      $format = $filter->getFilterFormat();
    }
    elseif ($this instanceof FilterFormat) {
      $format = $this;
    }

    return [
      'parser' => $parser,
      'filter' => $filter,
      'format' => $format,
    ] + $context;
  }

  /**
   * {@inheritdoc}
   */
  public function getGuidelines() {
    $base_url = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();
    $site_name = \Drupal::config('system.site')->get('name');

    // Define default groups.
    $guides = [
      'general' => ['title' => $this->t('General'), 'items' => []],
      'blockquotes' => ['title' => $this->t('Block Quotes'), 'items' => []],
      'code' => ['title' => $this->t('Code'), 'items' => []],
      'headings' => ['title' => $this->t('Headings'), 'items' => []],
      'images' => ['title' => $this->t('Images'), 'items' => []],
      'links' => ['title' => $this->t('Links'), 'items' => []],
      'lists' => ['title' => $this->t('Lists'), 'items' => []],
    ];

    // General.
    $guides['general']['items'][] = [
      'title' => $this->t('Paragraphs'),
      'description' => $this->t('Paragraphs are simply one or more consecutive lines of text, separated by one or more blank lines.'),
      'strip_p' => FALSE,
      'tags' => [
        'p' => [
          sprintf("%s\n\n%s", $this->t('Paragraph one.'), $this->t('Paragraph two.')),
        ],
      ],
    ];
    $guides['general']['items'][] = [
      'title' => $this->t('Line Breaks'),
      'description' => $this->t('If you want to insert a <kbd>&lt;br /&gt;</kbd> break tag, end a line with two or more spaces, then type return.'),
      'strip_p' => FALSE,
      'tags' => [
        'br' => [$this->t("Text with  \nline break")],
      ],
    ];
    $guides['general']['items'][] = [
      'title' => $this->t('Horizontal Rule'),
      'tags' => [
        'hr' => ['---', '___', '***'],
      ],
    ];
    $guides['general']['items'][] = [
      'title' => $this->t('Deleted text'),
      'description' => $this->t('The CommonMark spec does not (yet) have syntax for <kbd>&lt;del&gt;</kbd> formatting. You must manually specify them.'),
      'tags' => [
        sprintf("<del>%s</del>", $this->t('Deleted')),
      ],
    ];
    $guides['general']['items'][] = [
      'title' => $this->t('Emphasized text'),
      'tags' => [
        'em' => [
          sprintf("_%s_", $this->t('Emphasized')),
          sprintf("*%s*", $this->t('Emphasized')),
        ],
      ],
    ];
    $guides['general']['items'][] = [
      'title' => $this->t('Strong text'),
      'tags' => [
        'strong' => [
          sprintf("__%s__", $this->t('Strong', [], ['context' => 'Font weight'])),
          sprintf("**%s**", $this->t('Strong', [], ['context' => 'Font weight'])),
        ],
      ],
    ];

    // Blockquotes.
    /* @noinspection SpellCheckingInspection */
    $guides['blockquotes']['items'][] = [
      'tags' => [
        'blockquote' => [
          sprintf("> %s\n\n%s", $this->t("Block quoted"), $this->t("Normal text")),
          sprintf("> %s\n\n%s", $this->t("Nested block quotes\n>> Nested block quotes\n>>> Nested block quotes\n>>>> Nested block quotes"), $this->t("Normal text")),
          sprintf("> %s\n\n%s", $this->t("Lorem ipsum dolor sit amet, consectetur adipiscing elit. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Lorem ipsum dolor sit amet, consectetur adipiscing elit."), $this->t("Normal text")),
        ],
      ],
    ];

    // Code.
    $guides['code']['items'][] = [
      'title' => $this->t('Inline code'),
      'tags' => [
        'code' => sprintf("`%s`", $this->t('Inline code')),
      ],
    ];
    $guides['code']['items'][] = [
      'title' => $this->t('Fenced code blocks'),
      'tags' => [
        'pre' => [
          sprintf("```\n%s\n```", $this->t('Fenced code block')),
          sprintf("~~~\n%s\n~~~", $this->t('Fenced code block')),
          sprintf("    %s", $this->t('Fenced code block - indented using 4+ spaces')),
          sprintf("\t%s", $this->t('Fenced code block - indented using tab')),
        ],
      ],
    ];
    $guides['code']['items'][] = [
      'title' => $this->t('Fenced code blocks (using languages)'),
      'tags' => [
        'pre' => [
          "```css\n.selector {\n  color: #ff0;\n  font-size: 10px;\n  content: 'string';\n}\n```",
          "```js\nvar \$selector = \$('#id');\n\$selector.foo('bar', {\n  'baz': true,\n  'value': 1\n});\n```",
          "```php\n\$build['table'] = array(\n  '#theme' => 'table',\n  '#header' => \$header,\n  '#rows' => \$rows,\n  '#sticky' => FALSE,\n);\nprint \Drupal::service('renderer')->renderPlain(\$build);\n```",
        ],
      ],
    ];

    // Headings.
    $guides['headings']['items'][] = [
      'tags' => [
        'h1' => '# ' . $this->t('Heading 1'),
        'h2' => '## ' . $this->t('Heading 2'),
        'h3' => '### ' . $this->t('Heading 3'),
        'h4' => '#### ' . $this->t('Heading 4'),
        'h5' => '##### ' . $this->t('Heading 5'),
        'h6' => '###### ' . $this->t('Heading 6'),
      ],
    ];

    // Images.
    $guides['images']['items'][] = [
      'title' => $this->t('Images'),
      'tags' => [
        'img' => [
          sprintf("![%s](http://lorempixel.com/400/200/ \"%s\")", $this->t('Alt text'), $this->t('Title text')),
        ],
      ],
    ];
    /* @noinspection SpellCheckingInspection */
    $guides['images']['items'][] = [
      'title' => $this->t('Referenced images'),
      'strip_p' => FALSE,
      'tags' => [
        'img' => [
          sprintf("Lorem ipsum dolor sit amet\n\n![%s]\n\nLorem ipsum dolor sit amet\n\n[%s]: http://lorempixel.com/400/200/ \"%s\"", $this->t('Alt text'), $this->t('Alt text'), $this->t('Title text')),
        ],
      ],
    ];

    // Links.
    $guides['links']['items'][] = [
      'title' => $this->t('Links'),
      'tags' => [
        'a' => [
          "<$base_url>",
          "[$site_name]($base_url)",
          "<john.doe@example.com>",
          "[Email: $site_name](mailto:john.doe@example.com)",
        ],
      ],
    ];
    /* @noinspection SpellCheckingInspection */
    $guides['links']['items'][] = [
      'title' => $this->t('Referenced links'),
      'description' => $this->t('Link references are very useful if you use the same words through out a document and wish to link them all to the same link.'),
      'tags' => [
        'a' => [
          sprintf("[$site_name]\n\n[$site_name]: $base_url \"%s\"", $this->t('My title')),
          sprintf("Lorem ipsum [dolor] sit amet, consectetur adipiscing elit.\nLorem ipsum [dolor] sit amet, consectetur adipiscing elit.\nLorem ipsum [dolor] sit amet, consectetur adipiscing elit.\n\n[dolor]: $base_url \"%s\"", $this->t('My title')),
        ],
      ],
    ];
    $guides['links']['items'][] = [
      'title' => $this->t('Fragments (anchors)'),
      'tags' => [
        'a' => [
          "[$site_name]($base_url#fragment)",
          "[$site_name](#element-id)",
        ],
      ],
    ];

    // Lists.
    $guides['lists']['items'][] = [
      'title' => $this->t('Ordered lists'),
      'tags' => [
        'ol' => [
          sprintf("1. %s\n2. %s\n3. %s\n4. %s", $this->t('First item'), $this->t('Second item'), $this->t('Third item'), $this->t('Fourth item')),
          sprintf("1) %s\n2) %s\n3) %s\n4) %s", $this->t('First item'), $this->t('Second item'), $this->t('Third item'), $this->t('Fourth item')),
          sprintf("1. %s\n1. %s\n1. %s\n1. %s", $this->t('All start with 1'), $this->t('All start with 1'), $this->t('All start with 1'), $this->t('Rendered with correct numbers')),
          sprintf("1. %s\n2. %s\n   1. %s\n   2. %s\n      1. %s", $this->t('First item'), $this->t('Second item'), $this->t('First nested item'), $this->t('Second nested item'), $this->t('Deep nested item')),
          sprintf("5. %s\n6. %s\n7. %s\n8. %s", $this->t('Start at fifth item'), $this->t('Sixth item'), $this->t('Seventh item'), $this->t('Eighth item')),
        ],
      ],
    ];
    $guides['lists']['items'][] = [
      'title' => $this->t('Unordered lists'),
      'tags' => [
        'ul' => [
          sprintf("- %s\n- %s", $this->t('First item'), $this->t('Second item')),
          sprintf("- %s\n- %s\n  - %s\n  - %s\n    - %s", $this->t('First item'), $this->t('Second item'), $this->t('First nested item'), $this->t('Second nested item'), $this->t('Deep nested item')),
          sprintf("* %s\n* %s", $this->t('First item'), $this->t('Second item')),
          sprintf("+ %s\n+ %s", $this->t('First item'), $this->t('Second item')),
        ],
      ],
    ];

    return $guides;
  }

  /**
   * {@inheritdoc}
   */
  public function getRenderStrategy() {
    return $this->config()->get('render_strategy.type') ?: static::FILTER_OUTPUT;
  }

  /**
   * {@inheritdoc}
   */
  public function parse($markdown, LanguageInterface $language = NULL) {
    $moduleHandler = \Drupal::moduleHandler();

    $renderStrategy = $this->getRenderStrategy();
    if ($renderStrategy === static::ESCAPE_INPUT) {
      $markdown = Html::escape($markdown);
    }
    elseif ($renderStrategy === static::STRIP_INPUT) {
      $markdown = strip_tags($markdown);
    }

    // Invoke hook_markdown_alter().
    $context = $this->getContext(['language' => $language]);
    $moduleHandler->alter('markdown', $markdown, $context);

    // Convert markdown to HTML.
    $html = $this->convertToHtml($markdown, $language);

    // Invoke hook_markdown_html_alter().
    $context['markdown'] = $markdown;
    $moduleHandler->alter('markdown_html', $html, $context);

    // Filter all HTML output.
    if ($renderStrategy === static::FILTER_OUTPUT) {
      $html = (string) FilterHtml::fromParser($this)->process($html, $language ? $language->getId() : NULL);
    }

    return ParsedMarkdown::create($markdown, $html, $language);
  }

  /**
   * A description explaining why a setting is disabled due to render strategy.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   The rendered description.
   */
  protected function renderStrategyDisabledSetting(FormStateInterface $form_state) {
    /** @var \Drupal\markdown\Form\SubformStateInterface $form_state */
    $parents = $form_state->getAllParents();
    while (end($parents) !== 'parser') {
      array_pop($parents);
    }
    $parents = array_merge($parents, ['render_strategy', 'type']);
    $selector = ':input[name="' . array_shift($parents) . '[' . implode('][', $parents) . ']"]';

    /** @var \Drupal\markdown\Form\SubformStateInterface $form_state */
    $moreInfo = [
      '#type' => 'link',
      '#title' => $this->t('[More Info]'),
      '#url' => Url::fromUri(RenderStrategyInterface::MARKDOWN_XSS_URL),
      '#options' => [
        'attributes' => [
          'target' => '_blank',
        ],
      ],
      '#prefix' => ' ',
    ];
    return new FormattableMarkup('@disabled@warning', [
      '@disabled' => $form_state->conditionalElement([
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'form-item--description',
            'is-disabled',
          ],
        ],
        [
          '#markup' => $this->t('<strong>NOTE:</strong> This setting is disabled when a render strategy is being used.'),
        ],
        $moreInfo, // phpcs:ignore
      ], 'visible', $selector, ['!value' => static::NONE]),
      '@warning' => $form_state->conditionalElement([
        '#type' => 'container',
        '#theme_wrappers' => ['container__markdown_disabled_setting__render_strategy__warning'],
        '#attributes' => [
          'class' => [
            'form-item__error-message',
            'form-item--error-message',
          ],
        ],
        [
          '#markup' => $this->t('<strong>WARNING:</strong> This setting does not guarantee protection against malicious JavaScript from being injected. It is recommended to use the "Filter Output" render strategy.'),
        ],
        $moreInfo, // phpcs:ignore
      ], 'visible', $selector, ['value' => static::NONE]),
    ]);
  }

  /**
   * Adds a conditional state for a setting element based on render strategy.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   * @param array $element
   *   The element to modify, passed by reference.
   * @param string|string[] $state
   *   Optional. Additional states to trigger when setting is disabled, e.g.
   *   unchecked, etc.
   * @param array $conditions
   *   The conditions for which to trigger the state(s).
   */
  protected function renderStrategyDisabledSettingState(FormStateInterface $form_state, array &$element, $state = 'disabled', array $conditions = ['!value' => self::NONE]) {
    /** @var \Drupal\markdown\Form\SubformStateInterface $form_state */
    $parents = $form_state->getAllParents();
    while (end($parents) !== 'parser') {
      array_pop($parents);
    }
    $parents = array_merge($parents, ['render_strategy', 'type']);
    $selector = ':input[name="' . array_shift($parents) . '[' . implode('][', $parents) . ']"]';

    $states = (array) $state;
    foreach ($states as $state) {
      $form_state->addElementState($element, $state, $selector, $conditions);
    }

    // Add a conditional description explaining why the setting is disabled.
    if (!isset($element['#description'])) {
      $element['#description'] = $this->renderStrategyDisabledSetting($form_state);
    }
    else {
      $element['#description'] = new FormattableMarkup('@description @renderStrategyDisabledSetting', [
        '@description' => $element['#description'],
        '@renderStrategyDisabledSetting' => $this->renderStrategyDisabledSetting($form_state),
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Intentionally do nothing. This is just required to be implemented.
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Intentionally do nothing. This is just required to be implemented.
  }

}
