# mbo/satis-gitlab

[PHP composer/satis](https://github.com/composer/satis) application extended with the hability to generate configuration using GITLAB API to list repositories with composer.json file.

It aims at to provide a way to automatically mirror the dependencies of a GITLAB projects to allow offline builds.

## Usage

1) Generate SATIS configuration

```
# add --archive if you want to mirror tar archives
bin/satis-gitlab gitlab-to-config \
    --homepage https://satis.example.org/satis \
    --output satis.json \
    https://gitlab.example.org GitlabToken
```

2) Use SATIS as usual

```
bin/satis-gitlab build satis.json web
```

## Requirements

* GITLAB API v4
 