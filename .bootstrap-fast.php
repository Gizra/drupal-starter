<?php
/**
 * @file
 *   A bootstrap file for `phpunit` test runner.
 *
 * This bootstrap file from DTT is fast and customizable.
 *
 * If you get 'class not found' errors while running tests, you should copy this
 * file to a location inside your code-base --such as `/scripts`. Then add the
 * missing namespaces to the bottom of the copied field. Specify your custom
 * `bootstrap-fast.php` file as the bootstrap in `phpunit.xml`.
 *
 * Alternatively, use the bootstrap.php file, in this same directory, which is
 * slower but registers all the namespaces that Drupal tests expect.
 */

use Drupal\TestTools\PhpUnitCompatibility\PhpUnit8\ClassWriter;
use weitzman\DrupalTestTraits\AddPsr4;

list($finder, $class_loader) = AddPsr4::add();
$root = $finder->getDrupalRoot();

// So that test cases may be simultaneously compatible with multiple major versions of PHPUnit.
$class_loader->addPsr4('Drupal\TestTools\\', "$root/core/tests");
if (class_exists('Drupal\TestTools\PhpUnitCompatibility\PhpUnit8\ClassWriter')) {
    ClassWriter::mutateTestBase($class_loader);
}

// Register more namespaces, as needed.
$class_loader->addPsr4('Drupal\Tests\search_api\\', "$root/modules/contrib/search_api/tests/src");
$class_loader->addPsr4('Drupal\Tests\server_general\\', "$root/modules/custom/server_general/tests/src");
$class_loader->addPsr4('Drupal\Tests\drupal_test_assertions\\', "$root/modules/contrib/drupal_test_assertions/tests/src");
