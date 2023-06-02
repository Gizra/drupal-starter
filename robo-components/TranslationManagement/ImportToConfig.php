<?php

namespace RoboComponents\TranslationManagement;

use Gettext\Loader\PoLoader;
use Gettext\Translations;

/**
 * Logic to import translations into Drupal configuration.
 *
 * Imports translations from "config/po_files/[langcode]_config.po" files.
 */
trait ImportToConfig {

  /**
   * Import the translations from po files to config.
   */
  public function localeImportToConfig(): void {
    foreach (self::INSTALLED_LANGUAGES as $langcode) {
      $counter = 0;
      $translations = self::getTranslations($langcode);

      /** @var \Gettext\Translation $translation */
      foreach ($translations as $translation) {
        $contexts = explode(',', $translation->getContext());
        $original = $translation->getOriginal();
        $translated = $translation->getTranslation();

        if (empty($translated) || $original === $translated) {
          // If it's empty or same as original, don't bother importing.
          continue;
        }

        foreach ($contexts as $context) {
          [$config, $key] = explode(':', $context);

          if (empty($config) || empty($key)) {
            // Missing one of the essential data pieces. Skip.
            continue;
          }

          self::translateConfig(trim($config), trim($key), $langcode, trim($translated));
          $counter++;
        }
      }
      $this->say("$langcode - imported $counter translations into configuration");
    }
  }

  /**
   * Translates a configuration key with a new translation.
   *
   * @param string $name
   *   A config name. Example field.field.node.video_story.body .
   * @param string $key
   *   The key to extract the value. display.page.display_options.menu.title .
   * @param string $langcode
   *   The langcode key. Example: 'fr'.
   * @param string $translation
   *   The text to use as translation.
   */
  protected function translateConfig($name, $key, $langcode, $translation) {
    /** @var \Drupal\language\ConfigurableLanguageManager $languageManager */
    $languageManager = \Drupal::service('language_manager');
    $config_translation = $languageManager->getLanguageConfigOverride($langcode, $name);
    $config_translation->set($key, $translation);
    $config_translation->save();
  }

  /**
   * Load the translations from a PO file as Gettext Translations object.
   *
   * @param string $langcode
   *   The langcode.
   *
   * @return \Gettext\Translations
   *   The translations.
   */
  protected static function getTranslations(string $langcode): Translations {
    $loader = new PoLoader();
    return $loader->loadFile("config/po_files/{$langcode}_config.po");
  }

}
