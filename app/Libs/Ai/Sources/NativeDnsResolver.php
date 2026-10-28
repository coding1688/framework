<?php

namespace App\Libs\Ai\Sources;

class NativeDnsResolver
{
    /** @return list<string> */
    public function resolve(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return [$host];
        }
        $records = dns_get_record($host, DNS_A | DNS_AAAA);
        if ($records === false) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            fn (array $record): ?string => $record['ip'] ?? $record['ipv6'] ?? null, $records,
        ))));
    }
}
