<?php

include_once 'diff.php';

$old_string = <<<EOT
  Begin ...
  little file
  something out
  Hello world this
  is a test
  to see diff.php in action
  let's see
  if this might work
EOT;

$new_string = <<<EOT
  Hello world this
  Begin ...
  little file
  is a test
  to see diff.php in action
  let us see
  now
  if this might work
  let's see
  End ...
EOT;

$old = split( "\n", $old_string );
$new = split( "\n", $new_string );

// calculate the diff with both preferences
$actions_add = diff( $old, $new, ADD );
$actions_del = diff( $old, $new, DEL );

// pick the set of actions that has the least number of actions
$actions = count($actions_add) < count( $actions_del) 
  ? $actions_add : $actions_del;

// print out a simple table
print "<table>";
$o = 0;
$n = 0;
$i = 0;
$a = 0;
foreach( $actions as $action ) {
  switch( $action ) {
    case SAME:
      print "<tr><td>$i</td><td></td><td>$old[$o]</td><td>$new[$n]</td></tr>";
      $o++; $n++;
      break;
    case DEL:
      print "<tr><td>$i</td><td>-</td><td>$old[$o]</td><td></td></tr>";
      $o++;
      $a++;
      break;
    case ADD:
      print "<tr><td>$i</td><td>+</td><td></td><td>$new[$n]</td></tr>";
      $n++;
      $a++;
      break;
  }
  $i++;
}
print "</table>";

print "$a actions were required<br>\n";
