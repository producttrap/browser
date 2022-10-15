<?php

declare(strict_types=1);

namespace ProductTrap\Browser;

use DateTime;
use Exception;
use ProductTrap\Contracts\BrowserDriver;
use ProductTrap\DTOs\ScrapeResult;
use ProductTrap\Exceptions\BrowserResultEmptyException;

class Browser implements BrowserDriver
{
    public const IDENTIFIER = 'basic_chromium';


    protected array $faked = [];

    public function __construct(protected array $config = [])
    {
    }

    public function getName(): string
    {
        return 'Basic Chromium';
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function setConfig(array $config): self
    {
        $this->config = $config;

        return $this;
    }

    public function convertOptionsToArguments(array $options): string
    {
        $args = [];

        foreach ($options as $key => $value) {
            if ($value === false) {
                continue;
            }

            $key = (string) $key;

            $format = ($value === true) ? '--%s' : '--%s=%s';
            $bindings = ($value === true) ? [$key] : [$key, json_encode($value)];

            $args[] = vsprintf($format, $bindings);
        }

        return implode(' ', $args);
    }

    public function buildCommand(string $binary, array $options, string $url, string $output)
    {
        $args = $this->convertOptionsToArguments($options);

        return sprintf(
            '%s %s %s > %s',
            $binary,           // chromium-browser
            $args,             // --headless --dump-dom --user-agent="..." --wait-until="networkidle2"
            $url,              // https://www.woolworths.com.au/shop/productdetails/257360/john-west-tuna-olive-oil-blend
            $output,           // /tmp/producttrap/abc123
        );
    }

    public function crawl(string $url, array $parameters = []): ScrapeResult
    {
        // Generate output file
        if (($output = tempnam(sys_get_temp_dir(), 'producttrap')) === false) {
            throw new Exception('Could not create temporary file for browser output file.');
        }

        // Build configuration from defaults and user specified
        $config = array_replace([
            'binary' => '/snap/bin/chromium',
            'user_agent' => 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:107.0) Gecko/20100101 Firefox/106.0',
        ], $this->getConfig());

        // Build headless chromium cli options from configuration options and user specified
        $options = array_replace([
            'headless' => true,
            'dump-dom' => true,
            'user-agent' => $config['user_agent'],
            'wait-until' => 'networkidle2',
        ], $config['arguments'] ?? []);

        // Build the command to run
        $cmd = $this->buildCommand($config['binary'], $options, $url, $output);

        // Specify the current result (before running CLI as null)
        $html = null;

        // If this browser was given faked responses we'll check to see if
        if (!empty($this->faked)) {
            // Look to see if this exact URL was faked
            if (isset($this->faked[$url])) {
                /** @var ScrapeResult $html */
                $html = $this->faked[$url];
            }

            // Otherwise look to see if a wildcard URL was faked
            if (is_null($html) && isset($this->faked['*'])) {
                /** @var ScrapeResult $html */
                $html = $this->faked['*'];
            }

            // If not matching either, an empty response will be returned (no command to be executed)
            $html ??= '';

            // We've faked the output, may as well unlink the temp file :shrug:
            @unlink($output);
        }

        // Run the command if the HTML hasn't been faked
        if ($html === null) {
            exec($cmd);

            // If the output doesn't exist, then fail this connection
            if (! file_exists($output)) {
                return new ScrapeResult(result: null);
            }
        }

        // If faked, retain the html, otherwise read the response from the output file
        $html ??= trim(file_get_contents($output));
        @unlink($output);

        // If the html is empty, then fail this connection
        if (empty((string) $html)) {
            return new ScrapeResult(result: null);
        }

        if (! $html instanceof ScrapeResult) {
            $html = new ScrapeResult(
                html: (string) $html,
            );
        }

        $html->data = [
            'scraped_at' => (new DateTime()),
            'connection' => (!empty($this->faked)) ? 'faked' : 'live',
        ];

        return $html;
    }

    /**
     * Create a new browser that has the given responses faked
     */
    public static function fake(array $responses): self
    {
        $browser = new self();

        $browser->setResponses(
            array_map(
                fn (string|ScrapeResult $result) => ($result instanceof ScrapeResult)
                    ? $result
                    : new ScrapeResult(
                        result: $result,
                    ),
                $responses
            ),
        );

        return $browser;
    }

    /**
     * Set faked responses, empty array to disable faking
     */
    public function setResponses(array $responses = []): self
    {
        $this->faked = $responses;

        return $this;
    }
}
