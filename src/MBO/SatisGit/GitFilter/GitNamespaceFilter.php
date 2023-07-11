<?php

namespace MBO\SatisGit\GitFilter;

use Psr\Log\LoggerInterface;

use MBO\RemoteGit\ProjectInterface;
use MBO\RemoteGit\ProjectFilterInterface;
use MBO\RemoteGit\ClientInterface as GitClientInterface;

/**
 * Filter projects based on project namespace name or id.
 * 
 * @author roygoldman
 */
class GitNamespaceFilter implements ProjectFilterInterface
{
    /**
     * @var string[]
     */
    protected $groups;

    /**
     * @var GitClientInterface
     */
    protected $gitClient;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * GitNamespaceFilter constructor.
     */
    public function __construct(string $groups)
    {
        assert(!empty($groups));
        $this->groups = explode(',', strtolower($groups));
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(): string
    {
        return "Namespace should be one of [" . implode(', ', $this->groups) . "]";
    }

    /**
     * {@inheritDoc}
     */
    public function isAccepted(ProjectInterface $project): bool
    {
        $project_info = $project->getRawMetadata();
        if (isset($project_info['namespace'])) {
            
            // Extra data from namespace to patch on.
            $valid_keys = [
                'name' => 'name',
                'id'   => 'id',
            ];
            $namespace_info = array_intersect_key($project_info['namespace'], $valid_keys);
            $namespace_info = array_map('strtolower', $namespace_info);
            
            if (!empty($namespace_info) && !empty(array_intersect($namespace_info, $this->groups))) {
                // Accept any package with a permitted namespace name or id.
                return true;
            }
        }

        return false;
    }
}
