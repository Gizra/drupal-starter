<?php
namespace Drupal\server_general\Plugin\EntityViewBuilder;
use Drupal\node\NodeInterface;
use Drupal\server_general\EntityViewBuilder\NodeViewBuilderAbstract;
use Drupal\user\Entity\User;
use Drupal\Core\Url;
/**
 * The "Node Group" plugin.
 *
 * @EntityViewBuilder(
 *   id = "node.group",
 *   label = @Translation("Node - Group"),
 *   description = "Node view builder for Group bundle."
 * )
 */
class NodeGroup extends NodeViewBuilderAbstract {
  /**
   * Build full view mode for Group nodes.
   *
   * @param array $build
   *   The existing render array.
   * @param \Drupal\node\NodeInterface $entity
   *   The Group node entity.
   *
   * @return array
   *   The modified render array.
   */
  public function buildFull(array $build, NodeInterface $entity) {
    $current_user = \Drupal::currentUser();
    
    // Debug: Log that we're in the buildFull method
    \Drupal::logger('server_general')->debug('NodeGroup buildFull called for node @nid', ['@nid' => $entity->id()]);
    
    if ($current_user->isAuthenticated()) {
      $account = User::load($current_user->id());
      
      // Debug: Log user info
      \Drupal::logger('server_general')->debug('Authenticated user @uid (@name) viewing group @label', [
        '@uid' => $current_user->id(),
        '@name' => $account->getDisplayName(),
        '@label' => $entity->label(),
      ]);
      
      // Get OG services directly
      $membership_manager = \Drupal::service('og.membership_manager');
      $og_access = \Drupal::service('og.access');
      
      // Check if the user is already a member of the group
      $is_member = $membership_manager->isMember($entity, $current_user->id());
      
      // Debug: Log membership status
      \Drupal::logger('server_general')->debug('User @uid membership status: @status', [
        '@uid' => $current_user->id(),
        '@status' => $is_member ? 'member' : 'not member',
      ]);
      
      // Check if the user is allowed to subscribe (join) the group
      $can_subscribe = $og_access->userAccess($entity, 'subscribe', $account)->isAllowed();
      
      // Debug: Log subscription permission
      \Drupal::logger('server_general')->debug('User @uid can subscribe: @can', [
        '@uid' => $current_user->id(),
        '@can' => $can_subscribe ? 'yes' : 'no',
      ]);
      
      if ($is_member) {
        // User is already a member - show welcome message
        \Drupal::logger('server_general')->debug('Adding member welcome message for user @uid', ['@uid' => $current_user->id()]);
        
        $build['og_member_status'] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['group-member-status', 'member-welcome'],
          ],
          '#weight' => -10,
        ];
        
        $build['og_member_status']['message'] = [
          '#type' => 'markup',
          '#markup' => $this->t(
            '✓ Welcome back @name! You are a member of @label.',
            [
              '@name' => $account->getDisplayName(),
              '@label' => $entity->label(),
            ]
          ),
          '#prefix' => '<div class="member-welcome-message">',
          '#suffix' => '</div>',
        ];
        
        // Optional: Add unsubscribe link (commented out until route is created)
        /*
        $build['og_member_status']['unsubscribe_link'] = [
          '#type' => 'markup',
          '#markup' => $this->t(
            '<div class="member-actions"><a href=":url" class="unsubscribe-link">Leave this group</a></div>',
            [
              ':url' => Url::fromRoute('server_general.group_unsubscribe', [
                'node' => $entity->id(),
              ])->toString(),
            ]
          ),
        ];
        */
        
      } elseif ($can_subscribe) {
        // User is not a member but can subscribe
        \Drupal::logger('server_general')->debug('Adding subscription message for user @uid', ['@uid' => $current_user->id()]);
        
        $build['og_subscribe_message'] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['group-subscription-container'],
          ],
          '#weight' => -10,
        ];
        
        $build['og_subscribe_message']['message'] = [
          '#type' => 'markup',
          '#markup' => $this->t(
            'Hi @name, would you like to join @label?',
            [
              '@name' => $account->getDisplayName(),
              '@label' => $entity->label(),
            ]
          ),
          '#prefix' => '<div class="subscription-message">',
          '#suffix' => '</div>',
        ];
        
        $build['og_subscribe_message']['subscribe_button'] = [
          '#type' => 'link',
          '#title' => $this->t('Join Group'),
          '#url' => Url::fromRoute('server_general.group_subscribe', [
            'node' => $entity->id(),
          ]),
          '#attributes' => [
            'class' => ['button', 'button--primary', 'join-group-btn'],
          ],
        ];
        
      } else {
        // User cannot subscribe (insufficient permissions)
        \Drupal::logger('server_general')->debug('User cannot subscribe - showing info message for user @uid', ['@uid' => $current_user->id()]);
        
        $build['og_no_access_message'] = [
          '#type' => 'markup',
          '#markup' => $this->t(
            'Hi @name, this is a private group. Contact an administrator if you would like to join.',
            [
              '@name' => $account->getDisplayName(),
            ]
          ),
          '#prefix' => '<div class="group-no-access-message">',
          '#suffix' => '</div>',
          '#weight' => -10,
        ];
      }
    } else {
      // Anonymous user
      \Drupal::logger('server_general')->debug('Anonymous user viewing group - showing login message');
      
      $build['og_anonymous_message'] = [
        '#type' => 'markup',
        '#markup' => $this->t(
          '<a href=":login_url">Login</a> or <a href=":register_url">register</a> to join this group.',
          [
            ':login_url' => Url::fromRoute('user.login')->toString(),
            ':register_url' => Url::fromRoute('user.register')->toString(),
          ]
        ),
        '#prefix' => '<div class="group-anonymous-message">',
        '#suffix' => '</div>',
        '#weight' => -10,
      ];
    }
    
    return $build;
  }
  
  /**
   * Build teaser view mode.
   *
   * For now, just fallback to parent implementation.
   */
  public function buildTeaser(array $build, NodeInterface $entity) {
    return parent::buildTeaser($build, $entity);
  }
}