<?php

namespace Podlove\Modules\ShownotesShortcode;
use \Podlove\Model;

class Shownotes_Shortcode extends \Podlove\Modules\Base {
  protected $module_name        = 'OSF Shownotes Shortcode';
  protected $module_description = 'Adds Shownotes to episodes.';
  public static $taxonomy_name  = 'podlove-shownotes';

  public function load() {
    $osf_starttime = 0;
    include_once 'shownotes/osf.php';

    add_action('add_meta_boxes', function() {
      add_meta_box(
        'shownotesdiv-' . \Podlove\Modules\ShownotesShortcode\Shownotes_Shortcode::$taxonomy_name,
        __('Shownotes','podlove'),
        array(
          '\Podlove\Modules\ShownotesShortcode\Shownotes_Shortcode',
          'add_shownotes_textarea'),
          'podcast',
          'advanced',
          'default');
    });
    add_action('save_post',
               array(
                 $this,
                 'save_shownotes'
    ));
  }

  public function add_shownotes_textarea($post) {
    $post_id   = get_the_ID();
    $shownotes = get_post_meta($post_id, 'shownotes', true);
    echo '<div id="add_shownotes" class="shownotesdiv"><p><textarea id="shownotes" name="shownotes" style="height:280px" class="large-text">' . $shownotes . '</textarea></p></div>';
  }

  public function save_shownotes() {
    $post_id   = get_the_ID();
    $old       = get_post_meta($post_id, 'shownotes', true);
    $new       = $_POST['shownotes'];
    $shownotes = $old;
    if ($new && $new != $old) {
      update_post_meta($post_id, 'shownotes', $new);
      $shownotes = $new;
    } elseif ('' == $new && $old) {
      delete_post_meta($post_id, 'shownotes', $old);
    }
  }
}

?>