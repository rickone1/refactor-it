<?php
namespace Refactor\Console;

use Joli\JoliNotif\Notification;
use Joli\JoliNotif\NotifierFactory;
use Refactor\Common\CommandInterface;
use Refactor\Common\NotifierInterface;
use Refactor\Config\Rules;
use Refactor\Exception\FileNotFoundException;
use Refactor\Utility\PathUtility;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * Class Fixer
 * @package Refactor\Fixer
 */
class Fixer implements CommandInterface, NotifierInterface
{
    /** @var Animal */
    private $animal;

    /** @var GarbageCollector */
    private $garbageCollector;

    /** @var Finder */
    private $finder;

    /**
     * Fixer constructor.
     */
    public function __construct()
    {
        $this->animal = new Animal();
        $this->garbageCollector = new GarbageCollector();
        $this->finder = new Finder();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param HelperSet $helperSet
     * @param array|null $parameters
     * @throws FileNotFoundException
     * @throws \Refactor\Exception\UnknownVcsTypeException
     * @throws \Refactor\Exception\WrongVcsTypeException
     */
    public function execute(InputInterface $input, OutputInterface $output, HelperSet $helperSet, array $parameters = null)
    {
        $this->runRefactor(
            $this->finder->findAdjustedFiles(),
            $output
        );
    }

    /**
     * @param array $files
     * @param OutputInterface $output
     * @throws FileNotFoundException
     */
    private function runRefactor(array $files, OutputInterface $output)
    {
        if (empty($files)) {
            $output->writeln('<comment>' . $this->animal->speak('There are no files yet to refactor!') . '</comment>');

            return;
        }

        $output->writeln('<info>Refactoring...</info>');
        $output->writeln('');

        $progressBar = new ProgressBar($output, count($files));
        $progressBar->start();

        foreach ($files as $file) {
            $process = Process::fromShellCommandline(implode(' ', $this->getRefactorCommand($file)));
            $process->run();

            if ($process->isSuccessful()) {
                $output->writeln('<info> ' . $file . '</info>');
            } else {
                $output->writeln('<error>' . $process->getOutput() . '</error>');
            }

            $progressBar->advance();
        }

        $this->cleanUp();
        $progressBar->finish();

        $this->pushNotification(
            'Refactor complete',
            'The refactor process is completed!',
            false
        );

        $output->writeln('');
        $output->writeln('<info>' . $this->animal->speak("All done... \nYour code has been refactored!") . '</info>');
        $output->writeln('<info>' . Signature::write() . '</info>');
    }

    /**
     * @param string $file
     * @throws FileNotFoundException
     * @return array
     */
    private function getRefactorCommand(string $file): array
    {
        $executable = realpath(dirname(__DIR__).'/../../vendor/bin/');
        return [
            'php',
            $executable . '/php-cs-fixer',
            'fix',
            $file,
            '--format=json',
            '--allow-risky=yes',
            '--using-cache=no',
            "--rules='{$this->getInlineRules($this->getRules()->toJSON())}'"
        ];
    }

    /**
     * @throws FileNotFoundException
     * @return Rules
     */
    private function getRules(): Rules
    {
        if (file_exists(PathUtility::getRefactorItRulesFile())) {
            $rules = new Rules();
            $json = file_get_contents(PathUtility::getRefactorItRulesFile());
            $rules->fromJSON(json_decode($json, true));
        } else {
            throw new FileNotFoundException(
                'The refactor rules file was not found! Try running refactor config in the root of your project',
                1560437366837
            );
        }

        return $rules;
    }

    /**
     * Removes the php cs fixer cache file
     */
    private function cleanUp()
    {
        $this->garbageCollector->cleanUpCacheFile();
    }

    /**
     * @param string $rules
     * @return false|string
     */
    private function getInlineRules(string $rules):? string
    {
        $inlineRules = json_decode($rules, true);

        return json_encode($inlineRules);
    }

    /**
     * @param string $title
     * @param string $body
     * @param bool $exception
     */
    public function pushNotification(string $title, string $body, bool $exception): void
    {
        $notifier = NotifierFactory::create();
        $notification = new Notification();
        $notification
            ->setTitle($title)
            ->setBody($body)
            ->setIcon($exception ? NotifierInterface::SUCCESS_ICON : NotifierInterface::FAIL_ICON);

        $notifier->send($notification);
    }
}
