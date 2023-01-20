<?php

namespace Drupal\current_year\Plugin\Filter;

use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\FilterProcessResult;

/**
 * FilterBase implementation.
 *
 * @Filter(
 *   id = "filter_current_year",
 *   title = @Translation("Current Year"),
 *   description = @Translation("This filter enables &year; to be replaced by the current year."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class CurrentYearFilter extends FilterBase {

  /**
   * Implementation of the process method for this filter.
   */
  public function process($text, $langcode) {
    return new FilterProcessResult($this->currentYearise($text));
  }

  /**
   * Replace &year; with the current year.
   */
  private function currentYearise($text = '') {
    $current_year_regex = '|\&(amp;)?year\;|';
    $text = preg_replace_callback($current_year_regex,
      function ($matches) {
        return date('Y');
      },
      $text);
    return $text;
  }

}
