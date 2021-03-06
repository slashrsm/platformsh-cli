<?php

namespace Platformsh\Cli\Util;

use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Yaml\Yaml;

class PropertyFormatter
{
    const DEFAULT_DATE_FORMAT = 'c';

    /** @var int */
    public $yamlInline = 2;

    /** @var InputInterface|null */
    protected $input;

    public function __construct(InputInterface $input = null)
    {
        $this->input = $input;
    }

    /**
     * @param mixed  $value
     * @param string $property
     *
     * @return string
     */
    public function format($value, $property = null)
    {
        switch ($property) {
            case 'http_access':
                return $this->formatHttpAccess($value);

            case 'token':
                return '******';

            case 'created_at':
            case 'updated_at':
            case 'ssl.expires_on':
                return $this->formatDate($value);

            case 'ssl':
                if ($property === 'ssl' && is_array($value) && isset($value['expires_on'])) {
                    $value['expires_on'] = $this->formatDate($value['expires_on']);
                }
        }

        if (!is_string($value)) {
            $value = rtrim(Yaml::dump($value, $this->yamlInline));
        }

        return $value;
    }

    /**
     * Add options to a command's input definition.
     *
     * @param InputDefinition $definition
     */
    public static function configureInput(InputDefinition $definition)
    {
        $description = 'The date format (as a PHP date format string)';
        $option = new InputOption('date-fmt', null, InputOption::VALUE_REQUIRED, $description, self::DEFAULT_DATE_FORMAT);
        $definition->addOption($option);
    }

    /**
     * @param string $value
     *
     * @return string
     */
    protected function formatDate($value)
    {
        $format = null;
        if (isset($this->input) && $this->input->hasOption('date-fmt')) {
            $format = $this->input->getOption('date-fmt');
        }
        $format = $format ?: self::DEFAULT_DATE_FORMAT;

        // Workaround for the ssl.expires_on date, which is currently a
        // timestamp in milliseconds.
        if (substr($value, -3) === '000' && strlen($value) === 13) {
            $value = substr($value, 0, 10);
        }

        $timestamp = is_numeric($value) ? $value : strtotime($value);

        return date($format, $timestamp);
    }

    /**
     * @param array|string|null $httpAccess
     *
     * @return string
     */
    protected function formatHttpAccess($httpAccess)
    {
        $info = (array) $httpAccess;
        $info += [
            'addresses' => [],
            'basic_auth' => [],
            'is_enabled' => true,
        ];
        // Hide passwords.
        $info['basic_auth'] = array_map(function () {
            return '******';
        }, $info['basic_auth']);

        return $this->format($info);
    }
}
