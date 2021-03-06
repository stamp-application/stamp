<?php

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Stamp\Console\Application;
use Symfony\Component\Console\Tester\ApplicationTester;
use PhpSpec\Matcher\MatchersProviderInterface;
use Matcher\ApplicationOutputMatcher;
use Matcher\ApplicationOutputRegexMatcher;


/**
 * Defines application features from the specific context.
 */
class ApplicationContext implements Context, MatchersProviderInterface, SnippetAcceptingContext
{
    /**
     * @var Application
     */
    private $application;

    /**
     * @var integer
     */
    private $lastExitCode;

    /**
     * @var ApplicationTester
     */
    private $tester;

    /**
     * @beforeScenario
     */
    public function setupApplication()
    {
        $this->application = new Application();
        $this->application->setAutoExit(false);
        $this->tester = new ApplicationTester($this->application);
    }

    /**
     * @When I run command :command
     */
    public function iRunCommand($command = null)
    {
        $arguments = array (
            'command' => $command
        );

        $this->lastExitCode = $this->tester->run($arguments);
    }

    /**
     * @When I run command :command in verbose mode
     */
    public function iRunCommandInVerboseMode($command = null)
    {
        $arguments = array (
            'command' => $command
        );

        $this->addOptionToArguments('verbose', $arguments);

        $this->lastExitCode = $this->tester->run($arguments);
    }

    /**
     * @When I run command :command in verbose dry-run mode
     */
    public function iRunCommandInVerboseDryRunMode($command = null)
    {
        $arguments = array (
            'command' => $command
        );

        $this->addOptionToArguments('verbose', $arguments);
        $this->addOptionToArguments('dry-run', $arguments);

        $this->lastExitCode = $this->tester->run($arguments);
    }

    /**
     * @When I run stamp with :option option
     */
    public function iRunStampWithOption($option)
    {
        $arguments = array ();

        $this->addOptionToArguments($option, $arguments);

        $this->lastExitCode = $this->tester->run($arguments);
    }

    /**
     * @param string $option
     * @param array $arguments
     */
    private function addOptionToArguments($option, array &$arguments)
    {
        if ($option) {
            if (preg_match('/(?P<option>[a-z-]+)=(?P<value>[a-z.]+)/', $option, $matches)) {
                $arguments[$matches['option']] = $matches['value'];
            } else {
                $arguments['--' . trim($option, '"')] = true;
            }
        }
    }

    /**
     * @Then I should see :output
     */
    public function iShouldSee($output)
    {
        expect($this->tester)->toHaveOutput((string)$output);
    }

    /**
     * @Then I should see text matching :regex
     */
    public function iShouldSeeTextMatching($regex)
    {
        expect($this->tester)->toHaveOutputMatching($regex);
    }

    /**
     * @Then the output should contain:
     */
    public function theOutputShouldContain(PyStringNode $output)
    {
        expect($this->tester)->toHaveOutput((string)$output);
    }

    /**
     * Custom matchers
     *
     * @return array
     */
    public function getMatchers()
    {
        return array(
            new ApplicationOutputMatcher(),
            new ApplicationOutputRegexMatcher(),
        );
    }

}
