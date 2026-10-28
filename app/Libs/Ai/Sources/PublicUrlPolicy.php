<?php

namespace App\Libs\Ai\Sources;

use App\Libs\Ai\Exceptions\AiException;

class PublicUrlPolicy
{
    public function __construct(private readonly NativeDnsResolver $resolver) {}

    /** @return array{url: string, host: string, port: int, addresses: list<string>} */
    public function resolve(string $url): array
    {
        $parts = parse_url($url);
        if (strlen($url) > 512 || ! filter_var($url, FILTER_VALIDATE_URL) || ! is_array($parts)
            || ! in_array($parts['scheme'] ?? '', ['http', 'https'], true)
            || isset($parts['user']) || isset($parts['pass'])) {
            throw new AiException('source_invalid');
        }
        $host = strtolower(trim($parts['host'] ?? '', '[]'));
        $port = $parts['port'] ?? ($parts['scheme'] === 'https' ? 443 : 80);
        if ($port !== ($parts['scheme'] === 'https' ? 443 : 80) || $host === ''
            || preg_match('/(^|\.)(localhost|local|internal|test|invalid)$/', $host)) {
            throw new AiException('source_invalid');
        }
        $addresses = $this->resolver->resolve($host);
        if ($addresses === []) {
            throw new AiException('source_invalid');
        }
        foreach ($addresses as $address) {
            if (! $this->isPublicAddress($address)) {
                throw new AiException('source_invalid');
            }
        }

        return ['url' => $url, 'host' => $host, 'port' => $port, 'addresses' => $addresses];
    }

    private function isPublicAddress(string $address): bool
    {
        if (! filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return false;
        }
        $packed = inet_pton($address);
        if (strlen($packed) === 16) {
            return ord($packed[0]) >= 0x20 && ord($packed[0]) <= 0x3F
                && ! $this->inRange($packed, '2001::', 23)
                && ! $this->inRange($packed, '2001:db8::', 32)
                && ! $this->inRange($packed, '2002::', 16);
        }
        foreach ([['0.0.0.0', 8], ['100.64.0.0', 10], ['192.0.0.0', 24], ['192.0.2.0', 24],
            ['198.18.0.0', 15], ['198.51.100.0', 24], ['203.0.113.0', 24], ['224.0.0.0', 3]] as [$network, $bits]) {
            if ($this->inRange($packed, $network, $bits)) {
                return false;
            }
        }

        return true;
    }

    private function inRange(string $address, string $network, int $bits): bool
    {
        $networkBytes = inet_pton($network);
        $wholeBytes = intdiv($bits, 8);
        $remainingBits = $bits % 8;
        if (substr($address, 0, $wholeBytes) !== substr($networkBytes, 0, $wholeBytes)) {
            return false;
        }

        return $remainingBits === 0 || (ord($address[$wholeBytes]) >> (8 - $remainingBits)) === (ord($networkBytes[$wholeBytes]) >> (8 - $remainingBits));
    }
}
