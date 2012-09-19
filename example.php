<html>
<head>
<style>
  TABLE      { border-collapse : collapse; }
  TD         { padding         : 1px;
	             white-space: pre;
	             font-family: Menlo, Monaco;
	             font-size: 9pt;
	           }
  TD.index   { text-align: right; 
               background-color: #F7F7F7; color: #BBBBBB;
               padding-right: 3px;}
  TD.label   { padding-left: 3px; }
  TD.line    {}
  TR.add     { background-color: #D9FFE0; color: #008828; }
  TR.del     { background-color: #FFEEEE; color: #C2003D; }
  TR.skip    { background-color: #F7F7F7; color: #BBBBBB;    }
  TR.context { background-color: white;   color: black;   }
</style>
</head>
<body>
<?php

include_once 'diff.php';

$old = <<<EOT
Begin ...
little file
is a test
to see diff.php in action
let's see
if this might work
EOT;

$new = <<<EOT
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


$diff = new diff( $old, $new );
$diff->showContext(1);
$html = "<table>\n";
while($line = $diff->getNextLine()) {
  if( $line->isAddition() ) {
    $class = 'add';     $label = '+';
    $o = '';    $n = $line->getNewIndex();
  } elseif( $line->isDeletion() ) {
    $class = 'del';     $label = '-';
    $n = '';    $o = $line->getOldIndex();
  } elseif( $line->isContext() ) {
    $class = 'context'; $label = '';
    $o = $line->getOldIndex();    $n = $line->getNewIndex();
  } else { // ->isSkip()
    $class = 'skip';    $label = '';
    $o = '...';  $n = '...';
  }
  $html .= <<<EOT
<tr class="{$class}">
  <td class="index">{$o}</td>
  <td class="index">{$n}</td>
  <td class="label">{$label}</td>
  <td class="line">{$line}</td>
</tr>
EOT;

}
$html .= "</table>\n";

print $html;
?>
</body>
</html>