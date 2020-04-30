<?php

namespace Drupal\markdown\Plugin\Markdown;

use Drupal\markdown\MarkdownExtensionPluginCollection;
use Drupal\markdown\Traits\MarkdownExtensionPluginManagerTrait;

abstract class ExtensibleMarkdownParserBase extends MarkdownParserBase implements ExtensibleMarkdownParserInterface {

  use MarkdownExtensionPluginManagerTrait;

  /**
   * @var array
   */
  protected $extensions = [];

  /**
   * A collection of MarkdownExtension plugins specific to the parser.
   *
   * @var \Drupal\markdown\MarkdownExtensionPluginCollection
   */
  protected $extensionCollection;

  /**
   * {@inheritdoc}
   */
  public function alterGuidelines(array &$guides = []) {
    // Allow enabled extensions to alter existing guides.
    foreach ($this->extensions() as $plugin_id => $extension) {
      if ($extension instanceof MarkdownGuidelinesAlterInterface) {
        $extension->alterGuidelines($guides);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getBundledExtensionIds() {
    return isset($this->pluginDefinition['bundledExtensions']) ? $this->pluginDefinition['bundledExtensions'] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    $configuration = parent::getConfiguration();

    // Normalize extensions and their settings.
    $extensions = [];
    $extensionCollection = $this->extensions();
    /** @var \Drupal\markdown\Plugin\Markdown\Extension\MarkdownExtensionInterface $extension */
    foreach ($extensionCollection as $extensionId => $extension) {
      // Check whether extension is required by another enabled extension.
      $required = FALSE;
      if ($requiredBy = $extension->requiredBy()) {
        foreach ($requiredBy as $dependent) {
          if ($extensionCollection->get($dependent)->isEnabled()) {
            $required = TRUE;
            break;
          }
        }
      }

      // Skip disabled extensions that aren't required.
      if (!$required && !$extension->isEnabled()) {
        continue;
      }

      $extensions[] = $extension->getConfiguration();
    }

    // Only add extensions if there are extensions to save.
    if (!empty($extensions)) {
      $configuration['extensions'] = $extensions;
    }

    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function getGuidelines() {
    $guides = parent::getGuidelines();

    // Allow enabled extensions to provide their own guides.
    foreach ($this->extensions() as $plugin_id => $extension) {
      if ($extension instanceof MarkdownGuidelinesInterface && ($element = $extension->getGuidelines())) {
        $guides['extensions'][$plugin_id] = $element;
      }
    }

    return $guides;
  }

  /**
   * {@inheritdoc}
   */
  public function extension($extensionId) {
    return $this->extensions()->get($extensionId);
  }


  /**
   * {@inheritdoc}
   */
  public function extensions() {
    if (!isset($this->extensionCollection)) {
      $this->extensionCollection = MarkdownExtensionPluginCollection::create($this->getContainer(), $this);
    }
    return $this->extensionCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return ['extensions' => $this->extensions()];
  }

  /**
   * Sets the configuration for an extension plugin instance.
   *
   * @param string $extensionId
   *   The identifier of the extension plugin to set the configuration for.
   * @param array $configuration
   *   The extension plugin configuration to set.
   *
   * @return static
   */
  public function setExtensionConfig($extensionId, array $configuration) {
    $this->extensions[$extensionId] = $configuration;
    if (isset($this->extensionCollection)) {
      $this->extensionCollection->setInstanceConfiguration($extensionId, $configuration);
    }
    return $this;
  }

}
