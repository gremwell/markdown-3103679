<?php

namespace Drupal\markdown\Plugin\Markdown\CommonMark\Extension;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\markdown\Plugin\Markdown\CommonMark\BaseExtension;
use Drupal\markdown\Plugin\Markdown\SettingsInterface;
use Drupal\markdown\Traits\SettingsTrait;
use League\CommonMark\EnvironmentAwareInterface;
use League\CommonMark\EnvironmentInterface;
use League\CommonMark\Extension\SmartPunct\SmartPunctExtension as LeagueSmartPunctExtension;

/**
 * Smart Punctuation extension.
 *
 * @MarkdownExtension(
 *   id = "league/commonmark-ext-smart-punctuation",
 *   label = @Translation("Smart Punctuation"),
 *   installed = "\League\CommonMark\Extension\SmartPunct\SmartPunctExtension",
 *   description = @Translation("Intelligently converts ASCII quotes, dashes, and ellipses to their Unicode equivalents."),
 *   url = "https://commonmark.thephpleague.com/extensions/smart-punctuation/",
 * )
 */
class SmartPunctuationExtension extends BaseExtension implements EnvironmentAwareInterface, PluginFormInterface, SettingsInterface {

  use SettingsTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'double_quote_opener' => '“',
      'double_quote_closer' => '”',
      'single_quote_opener' => '‘',
      'single_quote_closer' => '’',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $element, FormStateInterface $form_state) {
    /** @var \Drupal\markdown\Form\SubformStateInterface $form_state */

    $element += $this->createSettingElement('double_quote_opener', [
      '#type' => 'textfield',
    ], $form_state);

    $element += $this->createSettingElement('double_quote_closer', [
      '#type' => 'textfield',
    ], $form_state);

    $element += $this->createSettingElement('single_quote_opener', [
      '#type' => 'textfield',
    ], $form_state);

    $element += $this->createSettingElement('single_quote_closer', [
      '#type' => 'textfield',
    ], $form_state);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsKey() {
    return 'smartpunct';
  }

  /**
   * {@inheritdoc}
   */
  public function setEnvironment(EnvironmentInterface $environment) {
    $environment->addExtension(new LeagueSmartPunctExtension());
  }

}