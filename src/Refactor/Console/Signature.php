<?php
namespace Refactor\Console;

/**
 * Class Signature
 * @package Refactor\Console
 * @codeCoverageIgnore
 */
class Signature
{
    /**
     * @return string
     */
    public static function write(): string
    {
        return "\nCoded with ♥ by Rick in 't Veld \nrick.in.t.veld@opinity.nl";
    }

    /**
     * @return string
     */
    public static function noob(): string
    {
        return 'NOOB';
    }

    /**
     * @return string
     */
    public static function team(): string
    {
        return 'Refactor-it';
    }
}
