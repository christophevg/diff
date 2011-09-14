<?php

const SAME = 1;
const DEL  = 2;
const ADD  = 3;

// looks for a line in an array starting at an index inceased with an offset
function look_for( $line, $array, $start, $offset ) {
  for( $d=$start; $d<count($array)-$offset; $d++ ) {
    if( $array[$d+$offset] == $line ) {
      return $d;
    }
  }
  return false;
}

// returns the actions that represent a diff between old and new arrays of
// lines, with an optional preference towards adding or removing lines
function diff( $old, $new, $prefer = ADD ) {
  // walk through both arrays individually
  $old_idx = 0; $old_count = count($old);
  $new_idx = 0; $new_count = count($new);
  
  // build a list of actions indicating if an identical line, a deleted or 
  // added line was encountered
  $actions = array();
  
  // loop until the end of one of both arrays is encountered
  while( ( $old_idx < $old_count ) && ( $new_idx < $new_count ) ) {
    // if the current lines are identical, mark so and advance both indexes
    if( $old[$old_idx] == $new[$new_idx] ) {
      $actions[] = SAME;
      $old_idx++;
      $new_idx++;
    } else {
      // find shortest list of additions and deletions to reach a common line
      // this is a Manhattan Distance problem, where additions and deletions
      // are the two directions we can move in, but not all "streets" are open

      // start at the currently reached point and take one step at a time
      $depth = 0;
      // worst case situation: remove all/add all remaining
      $dels = count($old) - $old_idx;
      $adds = count($new) - $new_idx;
      
      // keep looking for a better combo until the actual path ($dels +$adds)
      // is smaller than the depth we're looking at
      while( $depth * 2 < $dels + $adds ) {

        // find out how many old lines must be deleted to reach the same line
        $odist = look_for( $new[$depth+$new_idx], $old, $depth, $old_idx );

        // find out how many new lines should be added to reach the same line
        $ndist = look_for( $old[$old_idx+$depth], $new, $depth, $new_idx );

        // if we prefer to ADD lines and we found a distance to a new lines
        if( $prefer == ADD && $ndist) {
          $dels = $depth; // delete up to this depth
          $adds = $ndist;  // step forward the distance
        } elseif( $odist ) {
          $dels = $odist;   // delete up to the distance
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
      while( $dels > 0 ) { $actions[] = DEL; $dels--; }  # deleted elements 
      while( $adds > 0 ) { $actions[] = ADD; $adds--; }  # added elements 
    }
  }
  
  // the end of one array is reached, add actions to reach the other end
  while( $old_idx < $old_count ) {
    $actions[] = DEL;               // old items that have been removed
    $old_idx++;
  }
  while( $new_idx < $new_count ) {
    $actions[] = ADD;               // new items that have been added
    $new_idx++;
  }
  
  return $actions;
}
