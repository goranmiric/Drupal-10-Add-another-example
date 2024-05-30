<?php

namespace Drupal\add_another_example\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * SocialNetworksSettingsForm class.
 */
class SocialNetworksSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'add_another_example.social_networks',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'add_another_example_social_networks_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $data = $this->config('add_another_example.social_networks')->get();

    // Define draggable table.
    $form['table-row'] = [
      '#type' => 'table',
      '#header' => [
        $this->t(''),
        $this->t('Icon'),
        $this->t('Label'),
        $this->t('Url'),
        $this->t('Operations'),
        $this->t('Weight'),
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'table-sort-weight',
        ],
      ],
      '#prefix' => '<div id="wrapper">',
      '#suffix' => '</div>',
    ];

    // Calculate the number of rows.
    // If there is no "num_of_rows" state value, count number of config items,
    // and it there is no config items, set the number of rows to 1,
    // so there is at least one row.
    if (!$num_of_rows = $form_state->get('num_of_rows')) {
      $num_of_rows = count($data['items']) ?: 1;
      $form_state->set('num_of_rows', $num_of_rows);
    }

    // Build the table rows and columns.
    for ($i = 0; $i < $num_of_rows; $i++) {
      $form['table-row'][$i]['#attributes']['class'][] = 'draggable';

      // Sort the table row according to its weight.
      $form['table-row'][$i]['#weight'] = $i;

      // This is needed to avoid writing additional css to make all inline.
      $form['table-row'][$i]['id'] = [
        '#markup' => ''
      ];

      $form['table-row'][$i]['icon'] = [
        '#type' => 'textfield',
        '#default_value' => $data['items'][$i]['icon'] ?? '',
      ];

      $form['table-row'][$i]['label'] = [
        '#type' => 'textfield',
        '#default_value' => $data['items'][$i]['label'] ?? '',
      ];

      $form['table-row'][$i]['url'] = [
        '#type' => 'url',
        '#default_value' => $data['items'][$i]['url'] ?? '',
      ];

      $form['table-row'][$i]['op'] = [
        '#type' => 'submit',
        '#name' => $i . '-row',
        '#value' => $this->t('Remove'),
        '#disabled' => $num_of_rows <= 1,
        '#submit' => ['::removeSubmit'],
        '#ajax' => [
          'callback' => '::addRemoveCallback',
          'wrapper' => 'wrapper',
        ],
      ];

      // Weight element.
      $form['table-row'][$i]['weight'] = [
        '#type' => 'weight',
        '#title_display' => 'invisible',
        '#default_value' => $i,
        // Classify the weight element for #tabledrag.
        '#attributes' => [
          'class' => [
            'table-sort-weight'
          ]
        ],
      ];

      $form['add_another'] = [
        '#type' => 'submit',
        '#value' => $this->t('Add another'),
        '#submit' => ['::addAnotherSubmit'],
        '#ajax' => [
          'callback' => '::addRemoveCallback',
          'wrapper' => 'wrapper',
        ],
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $data = $form_state->getValue('table-row');

    // Filter data.
    $networks = [];
    foreach ($data as $item) {
      if ($item['icon'] && $item['icon'] && $item['url']) {

        $networks[] = [
          'icon' => $item['icon'],
          'label' => $item['label'],
          'url' => $item['url'],
        ];
      }
    }

    // Save config.
    $this->config('add_another_example.social_networks')
      ->setData([
        'items' => $networks,
      ])
      ->save();
  }

  /**
   * Callback for add another button.
   */
  public function addRemoveCallback(array &$form, FormStateInterface $form_state) {
    return $form['table-row'];
  }

  /**
   * Submit handler for the "Add another" button.
   *
   * Increments the counter and causes a rebuild.
   */
  public function addAnotherSubmit(array &$form, FormStateInterface $form_state) {
    // Increase number of rows count.
    $num_of_rows = $form_state->get('num_of_rows');
    $form_state->set('num_of_rows', $num_of_rows + 1);

    // Rebuild form.
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "Remove" button.
   */
  public function removeSubmit(array &$form, FormStateInterface $form_state) {
    // Get the row id of the remove input.
    $row_id = $form_state->getTriggeringElement()['#parents'][1];

    // Remove the row from the user input and reindex table-row data.
    $input = $form_state->getUserInput();
    unset($input['table-row'][$row_id]);
    $input['table-row'] = array_values($input['table-row']);

    // Update user input and data object.
    $form_state->setUserInput($input);
    $form_state->set('data', $input['table-row']);

    // Decrease number of rows count.
    $num_of_rows = $form_state->get('num_of_rows');
    $form_state->set('num_of_rows', $num_of_rows - 1);

    // Rebuild form state.
    $form_state->setRebuild();
  }

}
