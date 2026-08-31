<?php

enum CarouselArrowsPosition
{
    case Top;
    case Bottom;
    case LeftAndRight;
    case Hidden;
}

interface ElementConfigCarouselInterface extends ElementConfigInterface {

  public function header() : array;
  public function items() : array;
  public function footer() : array;

  public function settings() : array;
  public function arrowsPosition() : CarouselArrowsPosition;
  public function isInfinite() : bool;
  public function itemsPerPage() : array;

}
