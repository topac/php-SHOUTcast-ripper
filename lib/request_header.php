<?php
  namespace SHOUTcastRipper;

  class RequestHeader {
    private $ip, $options, $crlf = "\r\n";

    public function __construct($ip, $options=array()){
      $default = array(
        'port'           => 80,
        'uri'            => '/',
        'getdata'        => array(),
        'custom_headers' => array()
      );
      $this->ip = $ip;
      $this->options = array_merge($default, $options);
    }

    private function generate(){
      $getdata_str  = (count($this->options['getdata']) ? '?' : '').$this->query_string($this->options['getdata']);
      $http_request = 'GET '.$this->options['uri'].$getdata_str.' HTTP/1.1'.$this->crlf;
      $http_request .= 'Host: '.$this->ip.':'.$this->options['port'].$this->crlf;
      $http_request .= 'User-Agent: php'.$this->crlf;
      $http_request .= 'Accept: */*'.$this->crlf;

      foreach ($this->options['custom_headers'] as $k => $v)
        $http_request .= $k .': '. $v . $this->crlf;

      $http_request .= $this->crlf;
      return $http_request;
    }

    public function __toString(){
      return $this->generate();
    }

    private function query_string($params){
      $query_string = '';
      foreach ($params as $k => $v)
        $query_string .= urlencode($k).'='.urlencode($v).'&';
      return $query_string;
    }
  }
?>