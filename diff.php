<?php

/**
* PHP implementation of a line-based diff algorithm
*/

class diff {
  private $oldLines = array();
  private $newLines = array();
  private $context  = 3;

  private $actions  = array();
  
  private $lines = array();
  private $line = -1;
  
  const ALL = -1;
  
  const SAME = 1;
  const DEL  = 2;
  const ADD  = 3;
  
  public function __construct( $old, $new ) {
    $this->oldLines = split( "\n", $old );
    $this->newLines = split( "\n", $new );
    $this->determineActions();
  }
  
  public function showContext($context = self::ALL) {
    $this->context = $context;
    $this->prepareLines();
    return $this;
  }
  
  public function differs() {
    return count($this->lines) > 0;
  }

  public function getNextLine() {
    if( $this->line >= count($this->lines)-1 ) { return false; }
    $this->line++;
    return $this->lines[$this->line];
  }

  private function determineActions() {
    $actions_add = $this->diff( self::ADD );
    $actions_del = $this->diff( self::DEL );
    $this->actions = count($actions_add) < count( $actions_del) 
      ? $actions_add : $actions_del;
  }
  
  private function prepareLines() {
    $this->lines = array();
    $last = count($this->actions) - 1;
    $i = 0;
    $o = 0; $n = 0;
    while( $i <= $last ) {
      if( $this->actions[$i] == self::ADD ) {
        if( $n < count($this->newLines) ) {
          $this->lines[] = new diffLine( 'add', $this->newLines[$n], null, $n );
          $n++;
        }
        $i++;
      } elseif( $this->actions[$i] == self::DEL ) {
        $this->lines[] = new diffLine( 'del', $this->oldLines[$o], $o, null );
        $o++;
        $i++;
      } else { // SAME
        // find end of SAME block
        $end = $i;
        while( $end < $last && $this->actions[$end+1] == self::SAME ) { $end++; }
        if( $this->context == self::ALL || ($end - $i) < $this->context * 2 ) {
          // all of SAME block is context, nothing to skip
          while( $i<=$end ) {
            $i++;
            $this->lines[] = new diffLine( 'context', $this->newLines[$n], $o, $n );
            $o++; $n++;
          }
        } else { // context / skip / context
          if( $n != 0 ) { // don't display initial context
            for( $c=0; $c<$this->context; $c++ ) {
              $this->lines[] = new diffLine( 'context', $this->newLines[$n], $o, $n );
              $o++; $n++;
              $i++;
            }
          } else {
            $o += $this->context;
            $n += $this->context;
            $i += $this->context;
          }

          $skip = $end - $i - $this->context + 1;

          if( $n == $this->context && ($n + $skip) >= $last ) {
             // don't skip between no changes
          } else {
            $this->lines[] = new diffLine( 'skip', '...', null, null );
          }

          $o += $skip; $n += $skip;
          $i += $skip;

          if( $n < $last ) {  // don't display final context
            for( $c=0; $c<$this->context; $c++ ) {
              $this->lines[] = new diffLine( 'context', $this->newLines[$n], $o, $n );
              $o++; $n++;
              $i++;
            }
          } else {
            $o += $this->context;
            $n += $this->context;
            $i += $this->context;
          }
        }
      }
    }
  }

  // determine the actions that represent a diff between old and new arrays of
  // lines, with an optional preference towards adding or removing lines
  private function diff( $prefer = self::ADD ) {
    // walk through both arrays individually
    $old_idx = 0; $old_count = count($this->oldLines);
    $new_idx = 0; $new_count = count($this->newLines);

    $actions = array();

    // loop until the end of one of both arrays is encountered
    while( ( $old_idx < $old_count ) && ( $new_idx < $new_count ) ) {
      // if the current lines are identical, mark so and advance both indexes
      if( $this->oldLines[$old_idx] == $this->newLines[$new_idx] ) {
        $actions[] = self::SAME;
        $old_idx++;
        $new_idx++;
      } else {

        // find shortest list of additions and deletions to reach a common
        // line this is a Manhattan Distance problem, where additions and
        // deletions are the two directions we can move in, but not all
        // "streets" are open

        // start at the currently reached point and take one step at a time
        $depth = 0;
        // worst case situation: remove all/add all remaining
        $dels = count($this->oldLines) - $old_idx;
        $adds = count($this->newLines) - $new_idx;

        // keep looking for a better combo until the actual path ($dels+$adds)
        // is smaller than the depth we're looking at
        while( $depth * 2 < $dels + $adds ) {

          // find out how many lines must be deleted to reach the same line
          if( $new_idx+$depth < count( $this->newLines ) ) {
            $odist = $this->look_for( $this->newLines[$new_idx+$depth],
                                      $this->oldLines, $depth, $old_idx );
          }

          // find out how many lines should be added to reach the same line
          if( $old_idx+$depth >= count($this->oldLines) ) {
            $ndist = false;
          } else {
            $ndist = $this->look_for( $this->oldLines[$old_idx+$depth],
                                      $this->newLines, $depth, $new_idx );
          }

          // if we prefer to ADD lines and we found a distance to a new lines
          if( $prefer == self::ADD && $ndist) {
            $dels = $depth;  // delete up to this depth
            $adds = $ndist;  // step forward the distance
          } elseif( $odist ) {
            $dels = $odist;  // delete up to the distance
            $adds = $depth;  // step forward the distance
          }

          // with the current dels and adds we reach the next same line
          // step one step down, to see if a better combination comes up
          // if there is a next one (we found a line before the end), we might
          // find out that the next line is found closer (e.g. swapped lines)
          // we prefer this shorter path
          $depth++;
        }

        // move the indexes according to the shortest path
        $old_idx += $dels;
        $new_idx += $adds;

        // and add actions
        while( $dels > 0 ) { $actions[] = self::DEL; $dels--; }
        while( $adds > 0 ) { $actions[] = self::ADD; $adds--; }
      }
    }

    // the end of one array is reached, add actions to reach the other end
    while( $old_idx < $old_count ) {
      $actions[] = self::DEL;   // old items that have been removed
      $old_idx++;
    }
    while( $new_idx < $new_count ) {
      $actions[] = self::ADD;   // new items that have been added
      $new_idx++;
    }
      
    return $actions;
  }

  // looks for a line in an array starting at an index inceased with an offset
  private function look_for( $line, $array, $start, $offset ) {
    for( $d=$start; $d<count($array)-$offset; $d++ ) {
      if( $array[$d+$offset] == $line ) {
        return $d;
      }
    }
    return false;
  }
}

class diffLine {
  private $type;
  private $string = "";
  private $oldIndex = null, $newIndex = null;
  
  public function __construct($type, $string, $oldIndex, $newIndex) {
    $this->type     = $type;
    $this->string   = htmlspecialchars(isset($string) ? $string : "");
    $this->oldIndex = $oldIndex + 1;
    $this->newIndex = $newIndex + 1;
  }
  
  public function isAddition() { return $this->type == 'add';     }
  public function isDeletion() { return $this->type == 'del';     }
  public function isContext()  { return $this->type == 'context'; }
  public function isSkip()     { return $this->type == 'skip';    }

  public function getOldIndex() { return $this->oldIndex; }
  public function getNewIndex() { return $this->newIndex; }

  public function __toString() { return $this->string; }
}
