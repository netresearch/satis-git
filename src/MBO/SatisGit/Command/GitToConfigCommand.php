<?php

namespace MBO\SatisGit\Command;

use Composer\Command\BaseCommand;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;

use MBO\SatisGit\Satis\ConfigBuilder;

use MBO\RemoteGit\ClientFactory;
use MBO\RemoteGit\FindOptions;
use MBO\RemoteGit\ProjectInterface;
use MBO\RemoteGit\ClientOptions;
use MBO\RemoteGit\Filter\FilterCollection;
use MBO\RemoteGit\Filter\IgnoreRegexpFilter;
use MBO\RemoteGit\Filter\ComposerProjectFilter;
use MBO\RemoteGit\Filter\RequiredFileFilter;

use MBO\SatisGit\GitFilter\GitNamespaceFilter;
use MBO\RemoteGit\Gitlab\GitlabClient;
use MBO\RemoteGit\Github\GithubClient;

/**
 * Generate SATIS configuration scanning git repositories
 *
 * @author mborne
 * @author roygoldman
 * @author ochorocho
 * @author fantoine
 * @author SilverFire
 * @author kaystrobach
 */
class GitToConfigCommand extends BaseCommand
{
    protected function configure()
    {
        $templatePath = realpath(__DIR__ . '/../Resources/default-template.json');

        $this
            // the name of the command (the part after "bin/console")
            ->setName('git-to-config')

            // the short description shown while running "php bin/console list"
            ->setDescription('generate satis configuration scanning git repositories')
            ->setHelp('look for composer.json in default git branch, extract project name and register them in SATIS configuration')
            
            /* 
             * Git client options 
             */
            ->addArgument('git-url', InputArgument::REQUIRED)
            ->addArgument('git-token')
            ->addOption('type', 't', InputOption::VALUE_REQUIRED, 'Git repository type: "github", "gitlab", "gogs"')
            ->addOption('unsafe-ssl', null, InputOption::VALUE_NONE, 'allows to ignore SSL problems')

            /*
             * Project listing options (hosted git api level)
             */
            ->addOption('orgs', 'o', InputOption::VALUE_REQUIRED, 'Find projects according to given organization names')
            ->addOption('users', 'u', InputOption::VALUE_REQUIRED, 'Find projects according to given user names')
            ->addOption('projectFilter', 'p', InputOption::VALUE_OPTIONAL, '[DEPRECATED] filter for projects (deprecated : see organization and users)', null)

            /*
             * Project filters
             */
            ->addOption('ignore', 'i', InputOption::VALUE_REQUIRED, 'ignore project according to a regexp, for ex : "(^phpstorm|^typo3\/library)"', null)
            ->addOption('include-if-has-file',null,InputOption::VALUE_REQUIRED, 'include in satis config if project contains a given file, for ex : ".satisinclude"', null)
            ->addOption('project-type',null,InputOption::VALUE_REQUIRED, 'include in satis config if project is of a specified type, for ex : "library"', null)
            ->addOption('gitlab-namespace',null,InputOption::VALUE_REQUIRED, '[DEPRECATED] include in satis config if gitlab project namespace is in the list, for ex : "2,Diaspora"', null)
            /* 
             * satis config generation options 
             */
            // deep customization : template file extended with default configuration
            ->addOption('template', null, InputOption::VALUE_REQUIRED, 'template satis.json extended with git repositories', $templatePath)

            // simple customization on default-template.json
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'satis repository name')
            ->addOption('homepage', null, InputOption::VALUE_REQUIRED, 'satis homepage')
            ->addOption('archive', null, InputOption::VALUE_NONE, 'enable archive mirroring')
            ->addOption('no-token', null, InputOption::VALUE_NONE, 'disable token writing in output configuration')

            /* 
             * output options
             */
            ->addOption('file', 'f', InputOption::VALUE_REQUIRED, 'JSON file to use', './satis.json')
        ;
    }

    /**
     * @{inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger = $this->createLogger($output);

        /*
         * Create git client according to parameters
         */
        $clientOptions = new ClientOptions();
        $clientOptions->setUrl($input->getArgument('git-url'));
        $clientOptions->setToken($input->getArgument('git-token'));

        $gitType = $input->getOption('type');
        $className = '\MBO\RemoteGit\\' . $gitType . '\\' . $gitType . 'Client';
        if (class_exists($className)) {
            $clientOptions->setType($className::TYPE);
        }

        if ( $input->getOption('unsafe-ssl') ){
            $clientOptions->setUnsafeSsl(true);
        }

        $client = ClientFactory::createClient(
            $clientOptions,
            $logger
        );

        $jsonFile = $input->getOption('file');

        /*
         * Create repository listing filter (git level)
         */
        $findOptions = new FindOptions();
        /* orgs option */
        $orgs = $input->getOption('orgs');
        if ( ! empty($orgs) ){
            $findOptions->setOrganizations(explode(',',$orgs));
        }
        /* users option */
        $users = $input->getOption('users');
        if ( ! empty($users) ){
            $findOptions->setUsers(explode(',',$users));
        }

        /* projectFilter option */
        $projectFilter = $input->getOption('projectFilter');
        if ( ! empty($projectFilter) ) {
            $output->writeln('<warning>--projectFilter is deprecated, prefer --orgs and --users which gives a better control</warning>');
            $output->writeln(sprintf("<info>Project filter : %s...</info>", $projectFilter));
            $findOptions->setSearch($projectFilter);
        }
        
        /*
         * Create project filters according to input arguments
         */
        $filterCollection = new FilterCollection($logger);
        $findOptions->setFilter($filterCollection);

        /*
         * Filter according to "composer.json" file
         */
        $composerFilter = new ComposerProjectFilter($client,$logger);
        /* project-type option */
        if ( ! empty($input->getOption('project-type')) ){
            $composerFilter->setProjectType($input->getOption('project-type'));
        }
        $filterCollection->addFilter($composerFilter);


        /* include-if-has-file option (TODO : project listing level) */
        if ( ! empty($input->getOption('include-if-has-file')) ){
            $filterCollection->addFilter(new RequiredFileFilter(
                $client,
                $input->getOption('include-if-has-file'),
                $logger
            ));
        }

        /*
         * Filter according to git project properties
         */

        /* ignore option */
        if ( ! empty($input->getOption('ignore')) ){
            $filterCollection->addFilter(new IgnoreRegexpFilter(
                $input->getOption('ignore')
            ));
        }
        
        /* gitlab-namespace option */
        if ( ! empty($input->getOption('gitlab-namespace')) ){
            $logger->warning(sprintf("--gitlab-namespace is deprecated, prefer --orgs to filter groups at gitlab API level"));
            $filterCollection->addFilter(new GitNamespaceFilter(
                $input->getOption('gitlab-namespace')
            ));
        }

        if (! file_exists($jsonFile)) {
            $command = $this->getApplication()->find('init');

            $arguments = [
                'command'          => 'init',
                'file'             => $jsonFile,
                '--name'           => 'Satis',
                '--homepage'       => $input->getOption('homepage'),
            ];

            $greetInput = new ArrayInput($arguments);
            $greetInput->setInteractive(false);
            $command->run($greetInput, $output);
        }

        /*
         * Create configuration builder
         */
        $output->writeln(sprintf("<info>Loading JSON file %s...</info>", $jsonFile));
        $configBuilder = new ConfigBuilder($jsonFile);

        /*
         * customize according to command line options
         */
        $name = $input->getOption('name');
        if ( ! empty($name) ){
            $configBuilder->setName($name);
        }

        $homepage = $input->getOption('homepage');
        if ( ! empty($homepage) ){
            $configBuilder->setHomepage($homepage);
        }

        // mirroring
        if ($input->getOption('archive')) {
            $configBuilder->enableArchive();
        }

        /*
         * Register git domain to enable composer git-* authentications
         */
        $gitDomain = parse_url($clientOptions->getUrl(), PHP_URL_HOST);
        $configBuilder->addGitDomain($gitDomain);

        if (! $input->getOption('no-token') && $clientOptions->hasToken()) {
            if ($client instanceof GitlabClient) {
                $configBuilder->addGitlabToken(
                    $gitDomain, 
                    $clientOptions->getToken()
                );
            } elseif ($client instanceof GithubClient) {
                $configBuilder->addGithubToken(
                    $clientOptions->getToken()
                );
            }
        }

        /*
         * Write resulting config
         */
        $satis = $configBuilder->getConfig();
        $output->writeln(sprintf("<info>Generate JSON configuration file : %s</info>", $jsonFile));
        $result = json_encode($satis, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents($jsonFile, $result);

        /*
         * SCAN git projects to find composer.json file in default branch
         */
        $output->writeln(sprintf(
            "<info>Listing repositories from %s (API : %s)...</info>",
            $clientOptions->getUrl(),
            $clientOptions->getType()
        ));

        /*
         * Find projects
         */
        $projects = $client->find($findOptions);
        
        /* Generate SATIS configuration */
        $projectCount = 0;

        $addCommand = $this->getApplication()->find('add');

        $addArguments = [
            'command' => 'add',
            'file'    => $jsonFile,
            '--type'  => $input->getOption('type'),
        ];

        foreach ($projects as $project) {
            try {
                /* look for composer.json in default branch */
                $json = $client->getRawFile(
                    $project, 
                    'composer.json', 
                    $project->getDefaultBranch()
                );

                /* retrieve project name from composer.json content */
                $composer = json_decode($json, true);
                $projectName = isset($composer['name']) ? $composer['name'] : null;
                if (is_null($projectName)) {
                    $logger->error($this->createProjectMessage(
                        $project,
                        "name not defined in composer.json"
                    ));
                    continue;
                }

                /* add project to satis config */
                $projectCount++;
                $logger->info($this->createProjectMessage(
                    $project,
                    "$projectName:*"
                ));

                $addArguments['url'] = $project->getHttpUrl();

                $addCommand->run(new ArrayInput($addArguments), $output);

            } catch (\Exception $e) {
                $logger->debug($e->getMessage());
                $logger->warning($this->createProjectMessage(
                    $project,
                    'composer.json not found'
                ));
            }
        }

        /* notify number of project found */
        if ($projectCount == 0) {
            $logger->error("No projects found!");
        } else {
            $logger->info(sprintf(
                "Number of projects found: %s",
                $projectCount
            ));
        }

        /*
         * Write resulting config
         */
        $satis = $configBuilder->getConfig();
        $logger->info("Generate satis configuration file : $jsonFile");
        $result = json_encode($satis, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents($jsonFile, $result);

        return static::SUCCESS;
    }


    /**
     * Create message for a given project
     * @param ProjectInterface $project
     * @param string $message
     * @return string
     */
    protected function createProjectMessage(
        ProjectInterface $project,
        $message
    ){
        return sprintf(
            '%s (branch %s) : %s',
            $project->getName(),
            $project->getDefaultBranch(),
            $message
        );
    }

    /**
     * Create console logger
     * @param OutputInterface $output
     * @return ConsoleLogger
     */
    protected function createLogger(OutputInterface $output)
    {
        return new ConsoleLogger($output);
    }
}
