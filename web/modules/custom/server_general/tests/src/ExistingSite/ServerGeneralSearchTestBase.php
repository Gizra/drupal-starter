<?php

declare(strict_types=1);

namespace Drupal\Tests\server_general\ExistingSite;

use Drupal\Tests\search_api\Kernel\PostRequestIndexingTrait;

/**
 * Base class for search tests involving ElasticSearch and Search API.
 */
class ServerGeneralSearchTestBase extends ServerGeneralTestBase {

  use PostRequestIndexingTrait;

  const ES_WAIT_SECONDS = 2;

  const ES_RETRY_LIMIT = 20;

  /**
   * Wait for Solr index to be updated.
   *
   * As Solr takes a while to index, we repeat the same assertions for
   * several attempts, in case we fail.
   *
   * @param callable $callable
   *   A callable function.
   *
   * @throws \Exception
   *
   * @see ES_RETRY_LIMIT
   * @see ES_WAIT_SECONDS
   */
  protected function waitForSearchIndex(callable $callable): void {
    $attempts = 0;
    do {
      sleep(self::ES_WAIT_SECONDS);
      try {
        $callable();
      }
      catch (\Exception $e) {
        $attempts++;
        if ($attempts > self::ES_RETRY_LIMIT) {
          throw $e;
        }
        continue;
      }
      break;
    } while (TRUE);
  }

}
