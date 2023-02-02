<?php

declare(strict_types = 1);

use Robo\Tasks;
use RoboComponents\ReleaseNotes;
use RoboComponents\Theme;
use RoboComponents\Deployment;
use RoboComponents\Phpcs;
use RoboComponents\ElasticSearch;

/**
 * Robo commands.
 */
class RoboFile extends Tasks {

  use Deployment;
  use ElasticSearch;
  use ReleaseNotes;
  use Phpcs;
  use Theme;

}
