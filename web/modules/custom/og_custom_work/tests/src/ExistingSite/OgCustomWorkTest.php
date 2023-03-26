<?php

use weitzman\DrupalTestTraits\ExistingSiteBase;

class OgCustomWorkTest extends ExistingSiteBase {

  /**
   * {@inheritdoc}
   */
  protected static array $modules = [
    'node',
    'og',
    'options',
    'pluggable_entity_view_builder',
  ];

  public function testOgGreeting() {

    $author = $this->createUser();

    $group = $this->createNode([
      'title' => 'Test Group',
      'type' => 'group',
      'uid' => $author->id(),
    ]);

    $this->drupalGet($group->toUrl());
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalLogin($author);
    $this->drupalGet($group->toUrl());

  }

}
