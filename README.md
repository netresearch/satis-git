# netresearch/satis-git

[![Build Status](https://travis-ci.org/netresearch/satis-git.svg)](https://travis-ci.org/netresearch/satis-git)

[![CI](https://github.com/netresearch/satis-gitlab/actions/workflows/ci.yml/badge.svg)](https://github.com/netresearch/satis-gitlab/actions/workflows/ci.yml) [![Coverage Status](https://coveralls.io/repos/github/netresearch/satis-gitlab/badge.svg?branch=master)](https://coveralls.io/github/netresearch/satis-gitlab?branch=master)

[PHP composer/satis](https://github.com/composer/satis) application extended with the ability to automate Satis 
configuration according to git projects containing a `composer.json` file.

It also provides a way to mirror PHP dependencies to allow offline builds.

## Requirements

* PHP 8.2
* GitLab API v4 / GitHub API / Gogs API / Gitea API

## Usage

### 1) Create Satis project

```bash
git clone https://github.com/netresearch/satis-git
cd satis-git
composer install
```


### 2) Generate Satis configuration

```bash
# add --archive if you want to mirror tar archives
bin/satis-git git-to-config \
    --homepage https://satis.example.org \
    --output satis.json \
    https://git.example.org [AuthToken]
```

### 3) Use Satis as usual

```bash
bin/satis-git build satis.json web
```

### 4) Configure a static file server for the web directory

Use you're favorite tool to expose `web` directory as `https://satis.example.org`.

**satis.json should not be exposed, it contains the auth token by default (see `--no-token`)**

### 5) Configure clients

#### Option 1 : Configure projects to use Satis

Satis web page suggests to add the following configuration to composer.json in all your projects :

```json
{
  "repositories": [{
    "type": "composer",
    "url": "https://satis.example.org"
  }]
}
```

#### Option 2 : Configure composer to use Satis

Alternatively, composer can be configured globally to use Satis :

```bash
composer config --global repo.satis.example.org composer https://satis.example.org
```

(it makes a weaker link between your projects and your Satis instance(s))


## Advanced usage

### Filter by organization/groups and users

If you rely on gitlab.com, you will probably need to find projects according to groups and users:

```bash
bin/satis-git git-to-config https://gitlab.com \$AUTH_TOKEN -vv --users=git_username --orgs=organization_name
```

### GitHub

```bash
bin/satis-git git-to-config https://github.com \$AUTH_TOKEN --orgs=git_organization --users=git_username
bin/satis-git build --skip-errors satis.json web
```

(Note that AUTH_TOKEN is required to avoid rate request limitation)

### Gogs

```bash
bin/satis-git git-to-config https://gogs.mydomain.org \$AUTH_TOKEN
bin/satis-git build --skip-errors satis.json web
```

(Note that Gogs detection is based on hostname)

### Mirror dependencies

Note that `--archive` option allows to download `tar` archives for each tag and each branch in `web/dist` for :

* The git projects
* The dependencies of the git projects


### Expose only public repositories

Note that `AuthToken` is optional so that you can generate a Satis instance only for you're public repositories.


### Disable AuthToken saving

Note that `git-to-config` saves the `AuthToken` to `satis.json` configuration file (so far you expose only the `web` 
directory, it is not a problem). 

You may disable this option using `--no-token` option and use the following composer command to configure 
`$COMPOSER_HOME/auth.json` file :

`composer config -g git-token.satis.example.org AuthToken`


### Deep customization

Some command line options provide a basic customization options. You may also use `--template my-satis-template.json` 
to replace the default template :

[default-template.json](src/MBO/SatisGit/Resources/default-template.json)


## Supported PHP versions

PHP 8.2

## Testing

```bash
export SATIS_GITLAB_TOKEN=AnyGitlabToken
export SATIS_GITHUB_TOKEN=AnyGithubToken

make test
```

Note that an HTML coverage report is generated to `output/coverage/index.html`

## License

satis-git is licensed under the MIT License - see the [LICENSE](LICENSE) file for details
