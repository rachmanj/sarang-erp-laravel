<?php

namespace App\Support;

class EnvFileUpdater
{
    /**
     * @param  array<string, scalar|null>  $values
     */
    public static function update(array $values): void
    {
        $path = base_path('.env');

        if (! file_exists($path)) {
            throw new \RuntimeException('.env file not found.');
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new \RuntimeException('Unable to read .env file.');
        }

        foreach ($values as $key => $value) {
            $formatted = self::formatValue($value);
            $pattern = '/^'.preg_quote($key, '/').'=.*/m';

            if (preg_match($pattern, $contents)) {
                $contents = preg_replace($pattern, $key.'='.$formatted, $contents) ?? $contents;
            } else {
                $contents = rtrim($contents).PHP_EOL.$key.'='.$formatted.PHP_EOL;
            }
        }

        file_put_contents($path, $contents);
    }

    private static function formatValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        $stringValue = (string) $value;

        if ($stringValue === '') {
            return '';
        }

        if (preg_match('/\s|#|=|"/', $stringValue)) {
            return '"'.str_replace('"', '\\"', $stringValue).'"';
        }

        return $stringValue;
    }
}
