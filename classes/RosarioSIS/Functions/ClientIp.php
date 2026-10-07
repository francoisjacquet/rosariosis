<?php
/**
 * Client IP functions class
 *
 * @since 13.1
 *
 * @package RosarioSIS
 */

namespace RosarioSIS\Functions;

class ClientIp
{
	/** @var array Trusted proxies: IP addresses or CIDR ranges (IPv4 or IPv6) */
	protected $trusted_proxies = [];

	/**
	 * Constructor
	 *
	 * Falls back to the `$TrustedProxies` global config variable (or empty) when no argument is given.
	 * This allows to inject the dependency (dependency injection) later,
	 * without having to change the calling code.
	 *
	 * @param string|null $trusted_proxies Trusted proxies, CSV list of IP addresses or CIDR ranges.
	 */
	public function __construct( $trusted_proxies = null )
	{
		if ( $trusted_proxies === null )
		{
			global $TrustedProxies;

			$trusted_proxies = isset( $TrustedProxies ) ? $TrustedProxies : '';
		}

		$this->trusted_proxies = $this->parseTrustedProxies( $trusted_proxies );
	}

	/**
	 * Get the client IP address
	 *
	 * The `X-Forwarded-For` HTTP header can be forged by the client:
	 * it is only used if the request comes from a trusted proxy,
	 * as defined in the `$TrustedProxies` config variable (CSV list), or given to the constructor.
	 * Otherwise, the IP address of the TCP connection (`REMOTE_ADDR`) is returned.
	 *
	 * Header is read from right to left, the first IP address that is not a trusted proxy is the client.
	 *
	 * @example $ip = ( new RosarioSIS\Functions\ClientIp )->get();
	 *
	 * @return string Client IP address.
	 */
	public function get()
	{
		$remote_addr = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : '';

		if ( empty( $this->trusted_proxies )
			|| empty( $_SERVER['HTTP_X_FORWARDED_FOR'] )
			|| ! $this->isTrustedProxy( $remote_addr, $this->trusted_proxies ) )
		{
			return $remote_addr;
		}

		$forwarded_ips = array_reverse( array_map( 'trim', explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );

		foreach ( $forwarded_ips as $forwarded_ip )
		{
			// Filter IP, HTTP_* headers can be forged.
			if ( ! filter_var( $forwarded_ip, FILTER_VALIDATE_IP ) )
			{
				// Malformed header: do not trust it.
				return $remote_addr;
			}

			if ( ! $this->isTrustedProxy( $forwarded_ip, $this->trusted_proxies ) )
			{
				return $forwarded_ip;
			}
		}

		return $remote_addr;
	}

	/**
	 * Parse trusted proxies CSV list
	 *
	 * @param string $csv IP addresses or CIDR ranges (IPv4 or IPv6), separated by commas.
	 *
	 * @return array
	 */
	protected function parseTrustedProxies( $csv )
	{
		if ( ! is_string( $csv ) )
		{
			return [];
		}

		return array_values( array_filter( array_map( 'trim', explode( ',', $csv ) ), 'strlen' ) );
	}

	/**
	 * Is IP address a trusted proxy?
	 *
	 * @param string $ip              IP address.
	 * @param array  $trusted_proxies IP addresses or CIDR ranges (IPv4 or IPv6).
	 *
	 * @return bool
	 */
	protected function isTrustedProxy( $ip, $trusted_proxies )
	{
		$ip_bin = @inet_pton( $ip );

		if ( $ip_bin === false )
		{
			return false;
		}

		foreach ( (array) $trusted_proxies as $proxy )
		{
			$proxy = trim( (string) $proxy );

			$bits = null;

			if ( strpos( $proxy, '/' ) !== false )
			{
				list( $proxy, $bits ) = explode( '/', $proxy, 2 );

				if ( ! ctype_digit( $bits ) )
				{
					continue;
				}

				$bits = (int) $bits;
			}

			$proxy_bin = @inet_pton( $proxy );

			if ( $proxy_bin === false
				|| strlen( $proxy_bin ) !== strlen( $ip_bin ) )
			{
				// Invalid proxy or IPv4 / IPv6 mismatch.
				continue;
			}

			if ( $bits === null )
			{
				$bits = strlen( $proxy_bin ) * 8;
			}

			if ( $bits > strlen( $proxy_bin ) * 8 )
			{
				continue;
			}

			$full_bytes = intdiv( $bits, 8 );

			if ( substr( $ip_bin, 0, $full_bytes ) !== substr( $proxy_bin, 0, $full_bytes ) )
			{
				continue;
			}

			$remaining_bits = $bits % 8;

			if ( $remaining_bits === 0 )
			{
				return true;
			}

			$mask = ( 0xFF << ( 8 - $remaining_bits ) ) & 0xFF;

			if ( ( ord( $ip_bin[ $full_bytes ] ) & $mask ) === ( ord( $proxy_bin[ $full_bytes ] ) & $mask ) )
			{
				return true;
			}
		}

		return false;
	}
}
