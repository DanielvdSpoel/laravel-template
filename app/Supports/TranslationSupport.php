<?php

namespace App\Supports;

use DirectoryIterator;
use Exception;
use Illuminate\Support\Facades\App;
use SplFileInfo;

class TranslationSupport
{
    private array $availableLocales = ['en', 'nl'];

    public function getTranslationStrings(): string
    {
        $path = resource_path('lang');
        $dir = new DirectoryIterator($path);

        foreach ($dir as $fileinfo) {
            if (! $fileinfo->isDot()) {
                $files[] = $fileinfo->getRealPath();
            }
        }
        asort($files);

        foreach ($files as $fileName) {
            $fileinfo = new SplFileInfo($fileName);

            $noExt = $this->removeExtension($fileinfo->getFilename());
            if ($noExt !== '') {
                if (class_exists('App')) {
                    App::setLocale($noExt);
                }

                if ($fileinfo->isDir()) {
                    $local = $this->allocateLocaleArray($fileinfo->getRealPath());
                } else {
                    $local = $this->allocateLocaleJSON($fileinfo->getRealPath());
                    if ($local === null) {
                        continue;
                    }
                }

                if (isset($locales[$noExt])) {
                    $locales[$noExt] = array_merge($local, $locales[$noExt]);
                } else {
                    $locales[$noExt] = $local;
                }
            }
        }

        $locales = $this->adjustVendor($locales);

        $jsonLocales = json_encode($locales) . PHP_EOL;

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Could not generate JSON, error code ' . json_last_error());
        }

        return $jsonLocales;
    }

    /**
     * @param string $path
     * @return array|null
     * @throws Exception
     */
    private function allocateLocaleJSON(string $path): ?array
    {
        // Ignore non *.json files (ex.: .gitignore, vim swap files etc.)
        if (pathinfo($path, PATHINFO_EXTENSION) !== 'json') {
            return null;
        }
        $tmp = (array) json_decode(file_get_contents($path), true);
        if (gettype($tmp) !== 'array') {
            throw new Exception('Unexpected data while processing ' . $path);
        }

        return $this->adjustArray($tmp);
    }

    /**
     * @param string $path
     * @return array
     */
    private function allocateLocaleArray(string $path, $multiLocales = false): array
    {
        $data = [];
        $dir = new DirectoryIterator($path);
        $lastLocale = last($this->availableLocales);
        foreach ($dir as $fileinfo) {
            // Do not mess with dotfiles at all.
            if ($fileinfo->isDot()) {
                continue;
            }

            if ($fileinfo->isDir()) {
                // Recursivley iterate through subdirs, until everything is allocated.

                $data[$fileinfo->getFilename()] = $this->allocateLocaleArray($path . DIRECTORY_SEPARATOR . $fileinfo->getFilename());
            } else {
                $noExt = $this->removeExtension($fileinfo->getFilename());
                $fileName = $path . DIRECTORY_SEPARATOR . $fileinfo->getFilename();

                // Ignore non *.php files (ex.: .gitignore, vim swap files etc.)
                if (pathinfo($fileName, PATHINFO_EXTENSION) !== 'php') {
                    continue;
                }

                $tmp = include $fileName;

                if (gettype($tmp) !== 'array') {
                    throw new Exception('Unexpected data while processing ' . $fileName);

                    continue;
                }
                if ($lastLocale !== false) {
                    $root = realpath(resource_path('lang') . DIRECTORY_SEPARATOR . $lastLocale);
                    $filePath = $this->removeExtension(str_replace('\\', '_', ltrim(str_replace($root, '', realpath($fileName)), '\\')));
                    if ($filePath[0] === DIRECTORY_SEPARATOR) {
                        $filePath = substr($filePath, 1);
                    }
                    if ($multiLocales) {
                        $this->filesToCreate[$lastLocale][$lastLocale][$filePath] = $this->adjustArray($tmp);
                    } else {
                        $this->filesToCreate[$filePath][$lastLocale] = $this->adjustArray($tmp);
                    }
                }

                $data[$noExt] = $this->adjustArray($tmp);
            }
        }

        return $data;
    }

    private function adjustArray(array $arr): array
    {
        $res = [];
        foreach ($arr as $key => $val) {
            $key = $this->removeEscapeCharacter($this->adjustString($key));

            if (is_array($val)) {
                $res[$key] = $this->adjustArray($val);
            } else {
                $res[$key] = $this->removeEscapeCharacter($this->adjustString($val));
            }
        }

        return $res;
    }

    /**
     * Adjust vendor index placement.
     */
    private function adjustVendor(array $locales): array
    {
        if (isset($locales['vendor'])) {
            foreach ($locales['vendor'] as $vendor => $data) {
                foreach ($data as $key => $group) {
                    foreach ($group as $locale => $lang) {
                        $locales[$key]['vendor'][$vendor][$locale] = $lang;
                    }
                }
            }

            unset($locales['vendor']);
        }

        return $locales;
    }

    /**
     * Turn Laravel style ":link" into vue-i18n style "{link}" or vuex-i18n style ":::".
     */
    private function adjustString(string $s): string
    {
        if (! is_string($s)) {
            return $s;
        }

        $escaped_escape_char = preg_quote('!', '/');

        return preg_replace_callback(
            "/(?<!mailto|tel|{$escaped_escape_char}):\w+/",
            function ($matches) {
                return '{' . mb_substr($matches[0], 1) . '}';
            },
            $s
        );
    }

    /**
     * Removes escape character if translation string contains sequence that looks like
     * Laravel style ":link", but should not be interpreted as such and was therefore escaped.
     */
    private function removeEscapeCharacter(string $s): string
    {
        $escaped_escape_char = preg_quote('!', '/');

        return preg_replace_callback(
            "/{$escaped_escape_char}(:\w+)/",
            function ($matches) {
                return mb_substr($matches[0], 1);
            },
            $s
        );
    }

    /**
     * Returns filename, with extension stripped
     */
    private function removeExtension(string $filename): string
    {
        $pos = mb_strrpos($filename, '.');
        if ($pos === false) {
            return $filename;
        }

        return mb_substr($filename, 0, $pos);
    }
}
