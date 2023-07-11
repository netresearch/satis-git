<?php

namespace MBO\SatisGit\Satis;

/**
 * Incremental satis configuration builder
 * 
 * @author mborne
 */
class ConfigBuilder
{
    /**
     * resulting configuration
     */
    protected array $config = [];

    /**
     * Init configuration with a template
     * @param $templatePath string path to the template
     */
    public function __construct(String $templatePath = null)
    {
        if (empty($templatePath)) {
            $templatePath = __DIR__ . '/../Resources/default-template.json';
        }
        $this->config = json_decode(file_get_contents($templatePath),true);
    }

    /**
     * Get resulting configuration
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Set name
     */
    public function setName(String $name)
    {
        $this->config['name'] = $name;
    }

    /**
     * Set homepage
     */
    public function setHomepage(String $homepage)
    {
        $this->config['homepage'] = $homepage;
    }

    /**
     * Turn on mirror mode
     */
    public function enableArchive()
    {
        $this->config['archive'] = [
            'directory' => 'dist',
            'format'    => 'tar',
            'skip-dev'  => true,
        ];
    }

    /**
     * Add git domain to config
     */
    public function addGitDomain(String $gitDomain)
    {
        if (! isset($this->config['config'])) {
            $this->config['config'] = [];
        }
        if (! isset($this->config['config']['gitlab-domains'])) {
            $this->config['config']['gitlab-domains'] = array();
        }

        $this->config['config']['gitlab-domains'][] = $gitDomain;
    }

    /**
     * Add gitlab token
     * 
     * TODO : Ensure addGitlabDomain is invoked?
     *
     * @return self
     */
    public function addGitlabToken(String $gitlabDomain, String $gitlabAuthToken)
    {
        if (! isset($this->config['config']['gitlab-token'])) {
            $this->config['config']['gitlab-token'] = [];
        }
        $this->config['config']['gitlab-token'][$gitlabDomain] = $gitlabAuthToken;

        return $this;
    }

    /**
     * Save github token to satis.json
     *
     * @return self
     */
    public function addGithubToken(String $githubToken){
        $this->config['config']['github-oauth'] = [
            'github.com' => $githubToken
        ];
        return $this;
    }


    /**
     * Add a repository to satis
     *  
     * @param string $projectName "{vendorName}/{componentName}"
     * @param string $projectUrl
     * @param boolean $unsafeSsl allows to disable ssl checks 
     * 
     * @return void
     */
    public function addRepository(
        String $projectName,
        String $projectUrl,
        Bool $unsafeSsl = false
    ) {
        if (! isset($this->config['repositories'])) {
            $this->config['repositories'] = [];
        }

        $repository = [
            'type' => 'vcs',
            'url'  => $projectUrl,
        ];

        if ($unsafeSsl) {
            $repository['options'] = [
                "ssl" => [
                    "verify_peer"       => false,
                    "verify_peer_name"  => false,
                    "allow_self_signed" => true,
                ],
            ];
        }

        $this->config['repositories'][] = $repository;
        $this->config['require'][$projectName] = '*';
    }
}