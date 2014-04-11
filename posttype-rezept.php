<?php
/*
Plugin Name: Recipe Post Type
Description: Create custom post type for cooking recipes
Custom Taxonomy: Rezept-Art
Custom Post Type: Rezept */

add_action('init', 'register_rezept');

function register_rezept() {
  $labels = array(
    'name'                  => _x( 'Rezepte', 'llgrid-rezepte'),
    'description'           => _x( 'Unsere leckeren Rezepte', 'llgrid-rezepte'),
    'singular_name'         => _x( 'Rezept', 'llgrid-rezepte'),
    'add_new'               => _x( 'Hinzufügen', 'llgrid-rezepte'),
    'add_new_item'          => _x( 'Rezept hinzufügen', 'llgrid-rezepte'),
    'edit_item'             => _x( 'Rezepte bearbeiten', 'llgrid-rezepte'),
    'new_item'              => _x( 'Neues Rezept', 'llgrid-rezepte'),
    'view_item'             => _x( 'Rezept anzeigen', 'llgrid-rezepte'),
    'search_item'           => _x( 'Rezept suchen', 'llgrid-rezepte'),
    'not_found'             => _x( 'Keine Rezepte gefunden', 'llgrid-rezepte'),
    'not_found_in_trash'    => _x( 'Keine Rezepte im Papierkorb gefunden', 'llgrid-rezepte'),
    'parent_item_colon'     => '',
    'menu_name'             => _x( 'Rezepte', 'llgrid-rezepte')
  );
  $args = array(
    'labels'                => $labels,
    'public'                => true,
    'publicly_queryable'    => true,
    'show_ui'               => true,
    'show_in_menu'          => true,
    'show_in_nav_menus'     => true,
    'exclude_from_search'   => false,
    'hierarchical'          => false,
    'has_archive'           => true,
    'rewrite'               => array('slug' => 'rezept'),
    'supports'              => array('title', 'author', 'thumbnail', 'excerpt',
                                     'comments', 'revisions', 'editor')
  );
  register_post_type('rezept', $args);
}

function register_rezept_taxonomies() {
  register_taxonomy(
    'Rezept-Art',
    'rezept',
    array(
      'label' => 'Rezept-Art',
      'rewrite' => array('slug' => 'rezepte'),
    )
  );
  register_taxonomy(
    'Allergiefrei',
    'rezept',
    array(
      'label' => 'Allergien',
      'rewrite' => array('slug' => 'allergien')
    )
  );
}

add_action('init', 'register_rezept_taxonomies');

/* Meta-Boxes for Rezept post type */

function add_recipe_meta_box() {
  add_meta_box(
    'recipe_metabox', // $id
    'Rezept', // $title
    'show_recipe_meta_box', // $callback
    'rezept', // $page
    'normal', // $context
    'high' // $priority
  );
}

add_action('add_meta_boxes', 'add_recipe_meta_box');

// Now for the fields

$recipe_fields = array(
  array(
    'name'  => __( 'Rezept-Art', 'llgrid-rezepte' ),
    'desc'  => __( 'Art des Rezepts, etwa Nachspeise', 'llgrid-rezepte'),
    'id'    => 'recipe_type',
    'type'  => 'select',
    'options'=> array(
      'vorspeise' => array(
        'label' => __( 'Vorspeise', 'llgrid-rezepte'),
        'value' => 'vorspeise'
      ),
      'hauptspeise' => array(
        'label' => __( 'Hauptspeise', 'llgrid-rezepte'),
        'value' => 'hauptspeise'
      ),
      'nachspeise' => array(
        'label' => __( 'Nachspeise', 'llgrid-rezepte'),
        'value' => 'nachspeise'
      ),
      'snack' => array(
        'label' => __( 'Snack', 'llgrid-rezepte'),
        'value' => 'snack'
      )
    )
  ),

  array(
    'name'  => __( 'Zutaten', 'llgrid-rezepte' ),
    'desc'  => __( 'Eine Zutat pro Zeile', 'llgrid-rezepte' ),
    'id'    => 'recipe_ingredients',
    'type'  => 'textarea',
  ),

);

function show_recipe_meta_box() {
  global $recipe_fields, $post;
  // nonce for verification
  echo '<input type="hidden" name="recipe_nonce" value="'.wp_create_nonce(basename(__FILE__)).'" />';
  echo '<table class="form-table">';
  foreach ($recipe_fields as $field) {
    $meta = get_post_meta($post->ID, $field['id'], true);
    echo '<tr>
      <th><label for="'.$field['id'].'">'.$field['name'].'</label></th>
      <td>';
    switch($field['type']) {
      case 'text':
        echo '<input type="text" name="'.$field['id'].'" id="'.$field['id'].'" value="'.$meta.'" size="30" />
          <br /><span class="description">'.$field['desc'].'</span>';
      break;
      case 'textarea':
        echo '<textarea name="'.$field['id'].'" id="'.$field['id'].'" cols="60" rows="4">'.$meta.'</textarea>
          <br /><span class="description">'.$field['desc'].'</span>';
      break;
      case 'select':
        echo '<select name="'.$field['id'].'" id="'.$field['id'].'">';
        foreach ($field['options'] as $option) {
            echo '<option', $meta == $option['value'] ? ' selected="selected"' : '', ' value="'.$option['value'].'">'.$option['label'].'</option>';
        }
        echo '</select><br /><span class="description">'.$field['desc'].'</span>';
      break;
    }
    echo '</td></tr>';
  }
  echo '</table>';
}

function save_recipe_meta($post_id) {
  global $recipe_fields;

  if (!wp_verify_nonce($_POST['recipe_nonce'], basename(__FILE__))) {
    return $post_id;
  }
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
    return $post_id;
  }
  if ($_POST['post_type'] == 'rezept') {
    if (!current_user_can('edit_page', $post_id)) {
      return $post_id;
    } elseif (!current_user_can('edit_post', $post_id)) {
      return $post_id;
    }
  }
  foreach ($recipe_fields as $field) {
    $old = get_post_meta($post_id, $field['id'], true);
    $new = $_POST[$field['id']];
    if ($new && $new != $old) {
      update_post_meta($post_id, $field['id'], $new);
    } else if ($new == '' && $old) {
      delete_post_meta($post_id, $field['id'], $old);
    }
  }
}
add_action('save_post', 'save_recipe_meta');

/* format for template */
function ingredient_list () {
  global $post;
  $get_items = get_post_meta($post->ID, 'recipe_ingredients', true);
  $items = explode(PHP_EOL, $get_items);
  $list = '<ul>';
  foreach ($items as $item) {
    $list .= '<li>' . trim($item) . '</li>';
  }
  $list .= '</ul>';
  return $list;
}
?>
