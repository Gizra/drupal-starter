<?php

declare(strict_types = 1);

use Robo\Tasks;
use RoboComponents\BootstrapTrait;
use RoboComponents\ReleaseNotesTrait;
use RoboComponents\ThemeTrait;
use RoboComponents\DeploymentTrait;
use RoboComponents\PhpcsTrait;
use RoboComponents\ElasticSearchTrait;

/**
 * Robo commands.
 */
class RoboFile extends Tasks {

  use BootstrapTrait;
  use DeploymentTrait;
  use ElasticSearchTrait;
  use ReleaseNotesTrait;
  use PhpcsTrait;
  use ThemeTrait;

}
