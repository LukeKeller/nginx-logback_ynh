<?php
// Nginx Logger — echoes the metadata of the incoming request and shows the
// tail of this route's nginx access/error logs.
// Deployed by the YunoHost package; __APP__ is substituted at install time.

$LOG_DIR    = '/var/log/__APP__';
$ACCESS_LOG = $LOG_DIR . '/access.log';
$ERROR_LOG  = $LOG_DIR . '/error.log';
$TAIL_LINES = 50;

function h($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Read the last N lines of a (possibly large) file without loading all of it.
function tail_file($path, $lines) {
    if (!is_readable($path)) {
        return null;
    }
    $f = @fopen($path, 'rb');
    if (!$f) {
        return null;
    }
    $buffer = 4096;
    fseek($f, 0, SEEK_END);
    $pos = ftell($f);
    $data = '';
    while ($pos > 0 && substr_count($data, "\n") <= $lines) {
        $read = ($pos - $buffer) > 0 ? $buffer : $pos;
        $pos -= $read;
        fseek($f, $pos);
        $data = fread($f, $read) . $data;
    }
    fclose($f);
    $arr = explode("\n", rtrim($data, "\n"));
    if ($arr === array('')) {
        return array();
    }
    return array_slice($arr, -$lines);
}

// --- Gather request metadata -------------------------------------------------
$method   = $_SERVER['REQUEST_METHOD']  ?? '-';
$uri      = $_SERVER['REQUEST_URI']     ?? '-';
$proto    = $_SERVER['SERVER_PROTOCOL'] ?? '-';
$host     = $_SERVER['HTTP_HOST']       ?? '-';
$remote   = $_SERVER['REMOTE_ADDR']     ?? '-';
$ua       = $_SERVER['HTTP_USER_AGENT'] ?? '-';
$path     = parse_url($uri, PHP_URL_PATH) ?? '-';

// Query params
parse_str($_SERVER['QUERY_STRING'] ?? '', $query);

// Body / POST params
$rawBody = file_get_contents('php://input');
$postParams = $_POST;
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
// If JSON body, try to decode for display
$jsonBody = null;
if ($rawBody !== '' && stripos($contentType, 'application/json') !== false) {
    $decoded = json_decode($rawBody, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $jsonBody = $decoded;
    }
}

// Request headers
$headers = array();
foreach ($_SERVER as $k => $v) {
    if (strpos($k, 'HTTP_') === 0) {
        $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($k, 5)))));
        $headers[$name] = $v;
    }
}
if (isset($_SERVER['CONTENT_TYPE']))   $headers['Content-Type']   = $_SERVER['CONTENT_TYPE'];
if (isset($_SERVER['CONTENT_LENGTH'])) $headers['Content-Length'] = $_SERVER['CONTENT_LENGTH'];
ksort($headers);

function kv_table($rows) {
    if (empty($rows)) {
        return '<p class="empty">(none)</p>';
    }
    $out = '<table>';
    foreach ($rows as $k => $v) {
        if (is_array($v)) {
            $v = json_encode($v, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        $out .= '<tr><th>' . h($k) . '</th><td>' . h($v) . '</td></tr>';
    }
    $out .= '</table>';
    return $out;
}

$accessTail = tail_file($ACCESS_LOG, $TAIL_LINES);
$errorTail  = tail_file($ERROR_LOG, $TAIL_LINES);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>nginx-logger — <?= h($method) ?> <?= h($path) ?></title>
<style>
  :root { color-scheme: dark; }
  * { box-sizing: border-box; }
  body {
    margin: 0; padding: 2rem 1.25rem;
    font: 14px/1.5 ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    background: #0d1117; color: #c9d1d9;
  }
  .wrap { max-width: 960px; margin: 0 auto; }
  h1 { font-size: 1.1rem; margin: 0 0 .25rem; color: #58a6ff; }
  .sub { color: #8b949e; margin: 0 0 1.5rem; }
  section { background: #161b22; border: 1px solid #30363d; border-radius: 8px; margin: 0 0 1rem; }
  section > h2 {
    font-size: .8rem; text-transform: uppercase; letter-spacing: .06em;
    margin: 0; padding: .6rem .9rem; color: #8b949e;
    border-bottom: 1px solid #30363d; background: #11161d;
    border-radius: 8px 8px 0 0;
  }
  .body { padding: .5rem .9rem .8rem; }
  table { width: 100%; border-collapse: collapse; }
  th, td { text-align: left; vertical-align: top; padding: .3rem .5rem; border-bottom: 1px solid #21262d; }
  th { color: #79c0ff; font-weight: 600; white-space: nowrap; width: 1%; }
  td { color: #c9d1d9; word-break: break-word; }
  tr:last-child th, tr:last-child td { border-bottom: 0; }
  .empty { color: #6e7681; margin: .3rem 0; }
  pre { margin: 0; white-space: pre-wrap; word-break: break-word; color: #c9d1d9; }
  .badge {
    display: inline-block; padding: .1rem .5rem; border-radius: 4px;
    background: #1f6feb; color: #fff; font-weight: 700; margin-right: .4rem;
  }
  .logs pre { font-size: 12px; color: #8b949e; max-height: 320px; overflow: auto; }
  footer { color: #6e7681; margin-top: 1rem; font-size: 12px; }
  a { color: #58a6ff; }
</style>
</head>
<body>
<div class="wrap">
  <h1><span class="badge"><?= h($method) ?></span><?= h($uri) ?></h1>
  <p class="sub"><?= h($proto) ?> &middot; <?= h(gmdate('Y-m-d H:i:s')) ?> UTC &middot; from <?= h($remote) ?></p>

  <section>
    <h2>Request</h2>
    <div class="body"><?= kv_table(array(
        'Method'     => $method,
        'Host'       => $host,
        'Path'       => $path,
        'Full URI'   => $uri,
        'Protocol'   => $proto,
        'Remote IP'  => $remote,
        'User-Agent' => $ua,
    )) ?></div>
  </section>

  <section>
    <h2>Query params</h2>
    <div class="body"><?= kv_table($query) ?></div>
  </section>

  <section>
    <h2>POST / form params</h2>
    <div class="body"><?= kv_table($postParams) ?></div>
  </section>

  <?php if ($jsonBody !== null): ?>
  <section>
    <h2>JSON body</h2>
    <div class="body"><pre><?= h(json_encode($jsonBody, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?></pre></div>
  </section>
  <?php endif; ?>

  <section>
    <h2>Raw body (<?= h(strlen($rawBody)) ?> bytes)</h2>
    <div class="body"><?php if ($rawBody === ''): ?><p class="empty">(empty)</p><?php else: ?><pre><?= h($rawBody) ?></pre><?php endif; ?></div>
  </section>

  <section>
    <h2>Headers</h2>
    <div class="body"><?= kv_table($headers) ?></div>
  </section>

  <section class="logs">
    <h2>nginx access log (last <?= (int)$TAIL_LINES ?>)</h2>
    <div class="body"><?php
      if ($accessTail === null) {
          echo '<p class="empty">(log not readable yet)</p>';
      } elseif (empty($accessTail)) {
          echo '<p class="empty">(empty)</p>';
      } else {
          echo '<pre>' . h(implode("\n", array_reverse($accessTail))) . '</pre>';
      }
    ?></div>
  </section>

  <section class="logs">
    <h2>nginx error log (last <?= (int)$TAIL_LINES ?>)</h2>
    <div class="body"><?php
      if ($errorTail === null) {
          echo '<p class="empty">(log not readable yet)</p>';
      } elseif (empty($errorTail)) {
          echo '<p class="empty">(empty)</p>';
      } else {
          echo '<pre>' . h(implode("\n", array_reverse($errorTail))) . '</pre>';
      }
    ?></div>
  </section>

  <footer>nginx-logger &middot; every request to any sub-route is echoed here and written to the logs above.</footer>
</div>
</body>
</html>
