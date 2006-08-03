<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
  "http://www.w3.org/TR/html4/loose.dtd">
<html lang="en">
<head>
  <meta http-equiv="content-type" content="text/html; charset=utf-8" />
  <meta name="robots" content="NONE,NOARCHIVE" />
  <title><?php $o($desc);?></title>
  <link rel="stylesheet" href="design/exception.css" type="text/css" title="ExceptionHandler" />
  <script src="design/exception.js" type="text/javascript"></script>
</head>
<body>

<div id="summary">
  <h1><?php $o($desc);?></h1>
  <h2><?php
    if ( $e->getCode() ) { $o($e->getCode()). ' : '; }
    ?> <?php $o($e->getMessage()); ?></h2>
  <table>
    <tr>
      <th>PHP</th>
      <td><?php $o($e->getFile()); ?>, line <?php $o($e->getLine()); ?></td>
    </tr>
    <tr>
      <th>URI</th>
      <td><?php $o($_SERVER['REQUEST_METHOD'].' '.
        $_SERVER['REQUEST_URI']);?></td>
    </tr>
  </table>
</div>

<div id="traceback">
  <h2>Stacktrace
    <a href='#' onclick="return sectionToggle('tb_switch','tb_list')">
    <span id="tb_switch">▶</span></a></h2>
  <ul id="tb_list" class="traceback">
    <?php $frames = $e->getTrace(); foreach ( $frames as $frame_id => $frame ) { ?>
      <li class="frame">
        <?php echo $sub($frame); ?>
        [<?php $o($frame['file']); ?>, line <?php $o($frame['line']);?>]
        <?php
        if ( count($frame['args']) > 0 ) {
          $params = $parms($frame);
        ?>
          <div class="commands">
              <a href='#' onclick="return varToggle(this, '<?php
              $o($frame_id); ?>','v')"><span>▶</span> Args</a>
          </div>
          <table class="vars" id="v<?php $o($frame_id); ?>">
            <thead>
              <tr>
                <th>Arg</th>
                <th>Name</th>
                <th>Value</th>
              </tr>
            </thead>
            <tbody>
                <?php
                foreach ( $frame['args'] as $k => $v ) {
                  $name = isset($params[$k]) ? '$'.$params[$k]->name : '?';
                ?>
                <tr>
                  <td><?php $o($k); ?></td>
                  <td><?php $o($name);?></td>
                  <td class="code">
                    <div><?php highlight_string(var_export($v,TRUE));?></div>
                  </td>
                </tr>
                <?php
                }
                ?>
            </tbody>
          </table>
        <?php } if ( is_readable($frame['file']) ) { ?>
        <div class="commands">
            <a href='#' onclick="return varToggle(this, '<?php
            $o($frame_id); ?>','c')"><span>▶</span> Src</a>
        </div>
        <div class="context" id="c<?php $o($frame_id); ?>">
          <?php
          $lines = $src2lines($frame['file']);
          $start = $frame['line'] < 5 ?
            0 : $frame['line'] -5; $end = $start + 10;
          $out = '';
          foreach ( $lines as $k => $line ) {
            if ( $k > $end ) { break; }
            $line = trim(strip_tags($line));
            if ( $k < $start && isset($frames[$frame_id+1]["function"])
              && preg_match(
                '/function( )*'.preg_quote($frames[$frame_id+1]["function"]).'/',
                  $line) ) {
              $start = $k;
            }
            if ( $k >= $start ) {
              if ( $k != $frame['line'] ) {
                $out .= '<li><code>'.$clean($line).'</code></li>'."\n"; }
              else {
                $out .= '<li class="current-line"><code>'.
                  $clean($line).'</code></li>'."\n"; }
            }
          }
          echo "<ol start=\"$start\">\n".$out. "</ol>\n";
          ?>
        </div>
        <?php } else { ?>
        <div class="commands">No src available</div>
        <?php } ?>
      </li>
    <?php } ?>
  </ul>

</div>

<div id="request">
  <h2>Request
    <a href='#' onclick="return sectionToggle('req_switch','req_list')">
    <span id="req_switch">▶</span></a></h2>
  <div id="req_list" class="section">
    <?php
    if ( function_exists('apache_request_headers') ) {
    ?>
    <h3>Request <span>(raw)</span></h3>
    <?php
      $req_headers = apache_request_headers();
        ?>
      <h4>HEADERS</h4>
      <?php
      if ( count($req_headers) > 0 ) {
      ?>
        <p class="headers">
        <?php
        foreach ( $req_headers as $req_h_name => $req_h_val ) {
          $o($req_h_name.': '.$req_h_val);
          echo '<br>';
        }
        ?>

        </p>
      <?php } else { ?>
        <p>No headers.</p>
      <?php } ?>

      <?php
      $req_body = file_get_contents('php://input');
      if ( strlen( $req_body ) > 0 ) {
      ?>
      <h4>Body</h4>
      <p class="req" style="padding-bottom: 2em"><code>
        <?php $o($req_body); ?>
      </code></p>
      <?php } ?>
    <?php } ?>
    <h3>Request <span>(parsed)</span></h3>
    <?php
    $superglobals = array('$_GET','$_POST','$_COOKIE','$_SERVER','$_ENV');
    foreach ( $superglobals as $sglobal ) {
      $sfn = create_function('','return '.$sglobal.';');
    ?>
    <h4><?php echo $sglobal; ?></h4>
      <?php
      if ( count($sfn()) > 0 ) {
      ?>
      <table class="req">
        <thead>
          <tr>
            <th>Variable</th>
            <th>Value</th>
          </tr>
        </thead>
        <tbody>
          <?php
          foreach ( $sfn() as $k => $v ) {
          ?>
            <tr>
              <td><?php $o($k); ?></td>
              <td class="code">
                <div><?php $o(var_export($v,TRUE)); ?></div>
                </td>
            </tr>
          <?php } ?>
        </tbody>
      </table>
      <?php } else { ?>
      <p class="whitemsg">No data</p>
      <?php } } ?>

  </div>
</div>

<?php if ( function_exists('headers_list') ) { ?>
<div id="response">

  <h2>Response
    <a href='#' onclick="return sectionToggle('resp_switch','resp_list')">
    <span id="resp_switch">▶</span></a></h2>

  <div id="resp_list" class="section">

    <h3>Headers</h3>
    <?php
    $resp_headers = headers_list();
    if ( count($resp_headers) > 0 ) {
    ?>
    <p class="headers">
      <?php
      foreach ( $resp_headers as $resp_h ) {
        $o($resp_h);
        echo '<br>';
      }
      ?>
    </p>
    <?php } else { ?>
      <p>No headers.</p>
    <?php } ?>
</div>
<?php } ?>

</body>
</html>
