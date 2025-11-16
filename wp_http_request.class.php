<?php

# WP_HTTP_Request PHP class by taken from php.net comments
# http://php.net/manual/en/function.fopen.php#58099
# Author: info AT b1g.de

# Usage:
#   $r = new WP_HTTP_Request('http://www.php.net');
#   echo $r->DownloadToString();

class WP_HTTP_Request {
  var $_fp;       # HTTP socket
  var $_url;      # Full URL
  var $_host;     # HTTP host
  var $_protocol; # Protocol (HTTP/HTTPS)
  var $_uri;      # Request URI
  var $_port;     # Port

  # scan url
  function _scan_url() {
    $req = $this->_url;

    $pos = strpos($req, '://');
    $this->_protocol = strtolower(substr($req, 0, $pos));

    $req = substr($req, $pos+3);
    $pos = strpos($req, '/');

    if ($pos === false) $pos = strlen($req);

    $host = substr($req, 0, $pos);

    if (strpos($host, ':') !== false) {
      list($this->_host, $this->_port) = explode(':', $host);
    } else {
      $this->_host = $host;
      $this->_port = ($this->_protocol == 'https') ? 443 : 80;
    }

    $this->_uri = substr($req, $pos);

    if ($this->_uri == '') $this->_uri = '/';
  }

  # constructor
  function __construct($url) {
    $this->_url = $url;
    $this->_scan_url();
  }

  # download URL to string
  function DownloadToString() {
    $crlf = "\r\n";
    $language = 'Accept-Language: en-US,en';

    # generate request
    $user_agent = 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36';

    $req =
      'GET ' . $this->_uri . ' HTTP/1.0' . $crlf .
      'Host: ' . $this->_host . $crlf .
      $user_agent . $crlf .
      $language . $crlf .
      $crlf;

    # fetch
    $response = '';
    $this->_fp = fsockopen(($this->_protocol == 'https' ? 'ssl://' : '') . $this->_host, $this->_port);
    fwrite($this->_fp, $req);

    while(is_resource($this->_fp) && $this->_fp && !feof($this->_fp))
      $response .= fread($this->_fp, 1024);

    fclose($this->_fp);

    # split header and body
    $pos = strpos($response, $crlf . $crlf);

    if ($pos === false) return($response);

    $header = substr($response, 0, $pos);
    $body = substr($response, $pos + 2 * strlen($crlf));

    # parse headers
    $headers = array();
    $lines = explode($crlf, $header);

    foreach ($lines as $line)
      if (($pos = strpos($line, ':')) !== false)
        $headers[strtolower(trim(substr($line, 0, $pos)))] = trim(substr($line, $pos+1));

    # redirection?
    if (isset($headers['location'])) {
      $http = new WP_HTTP_Request($headers['location']);
      return $http->DownloadToString($http);
    }
    else {
      return($body);
    }
  }
}
?>
