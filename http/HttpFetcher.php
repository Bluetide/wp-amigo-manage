<?php

declare(strict_types=1);

namespace WPAmigoManage\Http;

use WPAmigoManage\Exceptions\BadGatewayException;

final class HttpFetcher
{

  private const DEFAULT_METHOD  = 'GET';
  private const DEFAULT_TIMEOUT = 15;

  public function get(string $url, array $args = []): HttpResponse
  {
    return $this->request('GET', $url, $args);
  }

  public function post(string $url, mixed $body = [], array $args = []): HttpResponse
  {
    $args['body'] = $body;
    
    return $this->request('POST', $url, $args);
  }

  private function request(string $method, string $url, array $args = []): HttpResponse
  {
    $default_config = [
      'method'  => $method ?? self::DEFAULT_METHOD,
      'timeout' => self::DEFAULT_TIMEOUT,
      'headers' => ['Accept' => 'application/json'],
    ];

    $response = wp_remote_request($url, array_merge($default_config, $args));

    if (is_wp_error($response)) {
      throw new BadGatewayException(
        reason: 'WP_REMOTE_FAILED',
        message: $response->get_error_message(),
        details: ['wp_error_code' => $response->get_error_code()],
      );
    }

    $body   = wp_remote_retrieve_body($response);
    $status = wp_remote_retrieve_response_code($response);
   
    return new HttpResponse(
      raw: $body,
      status: $status,
      headers: wp_remote_retrieve_headers($response)->getAll()
    );
  }
}
