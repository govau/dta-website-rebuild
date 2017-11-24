<?php
namespace Drush;

use Consolidation\AnnotatedCommand\AnnotatedCommand;
use Consolidation\AnnotatedCommand\CommandFileDiscovery;
use Consolidation\Config\ConfigInterface;
use Drush\Boot\BootstrapManager;
use Drush\Runtime\TildeExpansionHook;
use Drush\SiteAlias\AliasManager;
use Drush\Log\LogLevel;
use Drush\Command\RemoteCommandProxy;
use Drush\Runtime\RedispatchHook;

use Robo\Common\ConfigAwareTrait;
use Robo\Contract\ConfigAwareInterface;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Our application object
 *
 * Note: Implementing *AwareInterface here does NOT automatically cause
 * that corresponding service to be injected into the Application. This
 * is because the application object is created prior to the DI container.
 * See DependencyInjection::injectApplicationServices() to add more services.
 */
class Application extends SymfonyApplication implements LoggerAwareInterface, ConfigAwareInterface
{
    use LoggerAwareTrait;
    use ConfigAwareTrait;

    /** @var BootstrapManager */
    protected $bootstrapManager;

    /** @var AliasManager */
    protected $aliasManager;

    /** @var RedispatchHook */
    protected $redispatchHook;

    /** @var TildeExpansionHook */
    protected $tildeExpansionHook;

    /**
     * Add global options to the Application and their default values to Config.
     */
    public function configureGlobalOptions()
    {
        $this->getDefinition()
            ->addOption(
                new InputOption('--debug', 'd', InputOption::VALUE_NONE, 'Equivalent to -vv')
            );

        $this->getDefinition()
            ->addOption(
                new InputOption('--yes', 'y', InputOption::VALUE_NONE, 'Equivalent to --no-interaction.')
            );

        // Note that -n belongs to Symfony Console's --no-interaction.
        $this->getDefinition()
            ->addOption(
                new InputOption('--no', null, InputOption::VALUE_NONE, 'Cancels at any confirmation prompt.')
            );

        $this->getDefinition()
            ->addOption(
                new InputOption('--remote-host', null, InputOption::VALUE_REQUIRED, 'Run on a remote server.')
            );

        $this->getDefinition()
            ->addOption(
                new InputOption('--remote-user', null, InputOption::VALUE_REQUIRED, 'The user to use in remote execution.')
            );

        $this->getDefinition()
            ->addOption(
                new InputOption('--root', '-r', InputOption::VALUE_REQUIRED, 'The Drupal root for this site.')
            );


        $this->getDefinition()
            ->addOption(
                new InputOption('--uri', '-l', InputOption::VALUE_REQUIRED, 'Which multisite from the selected root to use.')
            );

        $this->getDefinition()
            ->addOption(
                new InputOption('--simulate', null, InputOption::VALUE_NONE, 'Run in simulated mode (show what would have happened).')
            );

        // TODO: Implement handling for 'pipe'
        $this->getDefinition()
            ->addOption(
                new InputOption('--pipe', null, InputOption::VALUE_NONE, 'Select the canonical script-friendly output format.')
            );

        $this->getDefinition()
            ->addOption(
                new InputOption('--define', '-D', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Define a configuration item value.', [])
            );
    }

    public function bootstrapManager()
    {
        return $this->bootstrapManager;
    }

    public function setBootstrapManager(BootstrapManager $bootstrapManager)
    {
        $this->bootstrapManager = $bootstrapManager;
    }

    public function aliasManager()
    {
        return $this->aliasManager;
    }

    public function setAliasManager($aliasManager)
    {
        $this->aliasManager = $aliasManager;
    }

    public function setRedispatchHook(RedispatchHook $redispatchHook)
    {
        $this->redispatchHook = $redispatchHook;
    }

    public function setTildeExpansionHook(TildeExpansionHook $tildeExpansionHook)
    {
        $this->tildeExpansionHook = $tildeExpansionHook;
    }

    /**
     * Return the framework uri selected by the user.
     */
    public function getUri()
    {
        if (!$this->bootstrapManager) {
            return 'default';
        }
        return $this->bootstrapManager->getUri();
    }

    /**
     * If the user did not explicitly select a site URI,
     * then pick an appropriate site from the cwd.
     */
    public function refineUriSelection($cwd)
    {
        if (!$this->bootstrapManager || !$this->aliasManager) {
            return;
        }
        $selfAliasRecord = $this->aliasManager->getSelf();
        if (!$selfAliasRecord->hasRoot()) {
            return;
        }
        $uri = $selfAliasRecord->uri();

        if (empty($uri)) {
            $uri = $this->bootstrapManager()->selectUri($cwd);
            $selfAliasRecord->setUri($uri);
            $this->aliasManager->setSelf($selfAliasRecord);
        }
        // Update the uri in the bootstrap manager
        $this->bootstrapManager->setUri($uri);
    }

    /**
     * @inheritdoc
     */
    public function find($name)
    {
        $command = $this->bootstrapAndFind($name);
        // Avoid exception when help is being built by https://github.com/bamarni/symfony-console-autocomplete.
        // @todo Find a cleaner solution.
        if (Drush::config()->get('runtime.argv')[1] !== 'help') {
            $this->checkObsolete($command);
        }
        return $command;
    }

    /**
     * Look up a command. Bootstrap further if necessary.
     */
    protected function bootstrapAndFind($name)
    {
        try {
            return parent::find($name);
        } catch (CommandNotFoundException $e) {
            // Is the unknown command destined for a remote site?
            if ($this->aliasManager) {
                $selfAlias = $this->aliasManager->getSelf();
                if ($selfAlias->isRemote()) {
                    $command = new RemoteCommandProxy($name, $this->redispatchHook);
                    $command->setApplication($this);
                    return $command;
                }
            }
            // If we have no bootstrap manager, then just re-throw
            // the exception.
            if (!$this->bootstrapManager) {
                throw $e;
            }

            // TODO: We could also fail-fast (throw $e) if bootstrapMax made no progress.
            $this->logger->log(LogLevel::DEBUG, 'Bootstrap further to find {command}', ['command' => $name]);
            $this->bootstrapManager->bootstrapMax();
            $this->logger->log(LogLevel::DEBUG, 'Done with bootstrap max in Application::find(): trying to find {command} again.', ['command' => $name]);

            // Try to find it again. This time the exception will
            // not be caught if the command cannot be found.
            return parent::find($name);
        }
    }

    /**
     * If a command is annotated @obsolete, then we will throw an exception
     * immediately; the command will not run, and no hooks will be called.
     */
    protected function checkObsolete($command)
    {
        if (!$command instanceof AnnotatedCommand) {
            return;
        }

        $annotationData = $command->getAnnotationData();
        if (!$annotationData->has('obsolete')) {
            return;
        }

        $obsoleteMessage = $command->getDescription();
        throw new \Exception($obsoleteMessage);
    }

    /**
     * @inheritdoc
     *
     * Note: This method is called twice, as we wish to configure the IO
     * objects earlier than Symfony does. We could define a boolean class
     * field to record when this method is called, and do nothing on the
     * second call. At the moment, the work done here is trivial, so we let
     * it happen twice.
     */
    protected function configureIO(InputInterface $input, OutputInterface $output)
    {
        // Do default Symfony confguration.
        parent::configureIO($input, $output);

        // Process legacy Drush global options.
        // Note that `getParameterOption` returns the VALUE of the option if
        // it is found, or NULL if it finds an option with no value.
        if ($input->getParameterOption(['--yes', '-y', '--no', '-n'], false, true) !== false) {
            $input->setInteractive(false);
        }
        // Symfony will set these later, but we want it set upfront
        if ($input->getParameterOption(['--verbose', '-v'], false, true) !== false) {
            $output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
        }
        // We are not using "very verbose", but set this for completeness
        if ($input->getParameterOption(['-vv'], false, true) !== false) {
            $output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);
        }
        // Use -vvv of --debug for even more verbose logging.
        if ($input->getParameterOption(['--debug', '-d', '-vvv'], false, true) !== false) {
            $output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
        }
    }

    /**
     * Configure the application object and register all of the commandfiles
     * available in the search paths provided via Preflight
     */
    public function configureAndRegisterCommands(InputInterface $input, OutputInterface $output, $commandfileSearchpath)
    {
        // Symfony will call this method for us in run() (it will be
        // called again), but we want to call it up-front, here, so that
        // our $input and $output objects have been appropriately
        // configured in case we wish to use them (e.g. for logging) in
        // any of the configuration steps we do here.
        $this->configureIO($input, $output);

        $discovery = $this->commandDiscovery();
        $commandClasses = $discovery->discover($commandfileSearchpath, '\Drush');
        $this->loadCommandClasses($commandClasses);

        // Uncomment the lines below to use Console's built in help and list commands.
        // unset($commandClasses[__DIR__ . '/Commands/help/HelpCommands.php']);
        // unset($commandClasses[__DIR__ . '/Commands/help/ListCommands.php']);

        // Use the robo runner to register commands with Symfony application.
        // This method could / should be refactored in Robo so that we can use
        // it without creating a Runner object that we would not otherwise need.
        $runner = new \Robo\Runner();
        $runner->registerCommandClasses($this, $commandClasses);
    }

    /**
     * Ensure that any discovered class that is not part of the autoloader
     * is, in fact, included.
     */
    protected function loadCommandClasses($commandClasses)
    {
        foreach ($commandClasses as $file => $commandClass) {
            if (!class_exists($commandClass)) {
                include $file;
            }
        }
    }

    /**
     * Create a command file discovery object
     */
    protected function commandDiscovery()
    {
        $discovery = new CommandFileDiscovery();
        $discovery
            ->setIncludeFilesAtBase(true)
            ->setSearchLocations(['Commands', 'Hooks'])
            ->setSearchPattern('#.*(Command|Hook)s?.php$#');
        return $discovery;
    }
}
