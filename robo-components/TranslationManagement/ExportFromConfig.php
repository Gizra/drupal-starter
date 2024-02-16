<?php

namespace RoboComponents\TranslationManagement;

use Gettext\Generator\PoGenerator;
use Gettext\Translation;
use Gettext\Translations;

/**
 * Logic to export terms from Drupal config.
 *
 * Exports translations into "config/po_files/[langcode]_config.po" files.
 */
trait ExportFromConfig {

  /**
   * Export the translations from config.
   *
   * @param string $managed_config_file
   *   The file that contains the list of config IDs to export.
   */
  public function localeExportFromConfig(string $managed_config_file = 'managed-config.txt'): void {
    if (!file_exists($managed_config_file)) {
      throw new \Exception('The config list does not exist.');
    }

    // Gather the list of configurations & keys to export translations for.
    $configs = file($managed_config_file);

    // Term map will hold mappings of terms to config entities (contexts) for
    // each language. This will allow us to reduce duplication when translation
    // source phrases are repeated in different configurations, so that the
    // translator needs to translate it only once.
    $term_map = [];
    // Translated map will hold mappings of terms to translations for each
    // language.
    $translated_map = [];

    // Gather the mappings for each language.
    foreach (self::INSTALLED_LANGUAGES as $langcode) {
      foreach ($configs as $config) {
        $config = trim($config);
        [$name, $key] = explode(':', $config);
        if (empty($name) || empty($key)) {
          continue;
        }
        $extracted_strings = self::listConfigs($name, [$key], $langcode);

        foreach ($extracted_strings as $item) {
          $term = $item[1];
          $translated = $item[2] ?? '';
          $term_map[$langcode][$term][] = trim($config);
          $translated_map[$langcode][$term] = $translated;
        }
      }
    }

    $po_generator = new PoGenerator();
    // Generate the po files.
    foreach (self::INSTALLED_LANGUAGES as $langcode) {
      // Create a new translations object, which will hold individual
      // translation objects.
      $translations = Translations::create();
      // Set some defaults.
      $translations->setLanguage($langcode);
      $translations->getHeaders()
        ->set('MIME-Version', '1.0')
        ->set('Content-Type', 'text/plain; charset=utf-8')
        ->set('Content-Transfer-Encoding', '8bit')
        ->set('Plural-Forms', 'nplurals=2; plural=(n > 1);');

      // Create the Translation objects and add to the Translations wrapper.
      foreach ($term_map[$langcode] as $term => $context) {
        $translated = $translated_map[$langcode][$term] ?? $term;
        $translation = Translation::create(implode(',', $context), $term);
        $translation->translate($translated);
        $translations->add($translation);
      }

      // Create the file for the language.
      $filename = "config/po_files/{$langcode}_config.po";
      $po_generator->generateFile($translations, $filename);
      $this->say("$langcode - exported translatable configuration strings to $filename");
    }
  }

  /**
   * Gets the English version of a configuration key.
   *
   * @param string $name
   *   A config name. Example field.field.node.video_story.body .
   * @param string $key
   *   The key to extract the value. display.page.display_options.menu.title .
   *
   * @return string
   *   Returns the English version of the $name:$key config.
   */
  protected function configKeyUnstranslated(string $name, string $key) {
    $configFactory = \Drupal::service('config.factory');
    $base_config = $configFactory->get($name);
    return $base_config->get($key);
  }

  /**
   * Gets the translated version of a configuration key.
   *
   * @param string $name
   *   A config name. Example "field.field.node.video_story.body".
   * @param string $key
   *   The key to extract the value.
   *   For example: "display.page.display_options.menu.title".
   * @param string $langcode
   *   The langcode of the translation to fetch. For example: "zh-hans".
   *
   * @return string
   *   Returns the translated version of the $name:$key config in $langcode.
   */
  protected function configKeyTranslated(string $name, string $key, string $langcode) {
    /** @var \Drupal\language\ConfigurableLanguageManager $language_manager */
    $language_manager = \Drupal::service('language_manager');
    $config = $language_manager->getLanguageConfigOverride($langcode, $name);
    return $config->get($key);
  }

  /**
   * Returns the translations of a config entity for the specified keys.
   *
   * @param string $prefix
   *   A prefix to load configs. Example 'field.field.node.'.
   * @param array $keys
   *   An array of keys to extract the value. Example: ['label', 'menu.title'].
   * @param string $langcode
   *   The langcode of the translations to fetch.
   *
   * @return array
   *   A list of original value and the ID.
   */
  protected function listConfigs(string $prefix, array $keys, string $langcode) {
    $configFactory = \Drupal::service('config.factory');
    $fields = $configFactory->listAll($prefix);
    $rows = [];
    foreach ($fields as $field_config) {
      foreach ($keys as $key) {
        $columns = [];
        $columns[] = $field_config . ':' . $key;
        $columns[] = self::configKeyUnstranslated($field_config, $key);
        $columns[] = self::configKeyTranslated($field_config, $key, $langcode);

        $rows[] = $columns;
      }
    }
    return $rows;
  }

}
