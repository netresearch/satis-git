<?php

namespace MBO\SatisGitlab\Satis;

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
    protected $config ;

    /**
     * Init configuration with a template
     * @param $templatePath string path to the template
     */
    public function __construct($templatePath = null)
    {
        if (empty($templatePath)) {
            $templatePath = __DIR__ . '/../Resources/default-template.json';
        }
        $this->config = json_decode(file_get_contents($templatePath),true);
    }

    /**
     * Get resulting configuration
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Set homepage
     * @param string $homepage
     * @return void
     */
    public function setHomepage($homepage)
    {
        $this->config['homepage'] = $homepage;
    }

    /**
     * Turn on mirror mode
     *
     * @return void
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
     * Add gitlab domain to config
     *
     * @param string $gitlabDomain
     * @return void
     */
    public function addGitlabDomain($gitlabDomain)
    {
        if (! isset($this->config['config'])) {
            $this->config['config'] = [];
        }
        if (! isset($this->config['config']['gitlab-domains'])) {
            $this->config['config']['gitlab-domains'] = array();
        }

        $this->config['config']['gitlab-domains'][] = $gitlabDomain;
    }

    /**
     * Add gitlab token
     * 
     * TODO : Ensure addGitlabDomain is invoked?
     *
     * @return self
     */
    public function addGitlabToken($gitlabDomain, $gitlabAuthToken)
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
     * @param  string $githubToken
     * @return self
     */
    public function addGithubToken($githubToken){
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
        $projectName,
        $projectUrl,
        $unsafeSsl = false
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