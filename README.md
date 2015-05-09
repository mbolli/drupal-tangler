# Drupal Tangler

This library provides tools for an opinionated composer workflow with Drupal.

When invoked, it creates a Drupal root that can respond to requests routed to
it from a web server. This allows you to develop at the module and/or project
level and treat Drupal itself as a dependency.

# Installation

Use composer.

# Usage

The algorithm is something like this:

1. copy `drupal/drupal` out of vendor and into the given drupal path (default:
  `./www`)
2. link modules and themes installed with composer from vendor into the drupal
   root
3. link directories from the `./modules` directory into `sites/all/modules`
4. link directories from the `./themes` directory into `sites/all/themes`
5. link files that look like module files into a directory in
   `sites/all/modules` according to the basename of the `*.info` file
6. link `cnf/settings.php` into `sites/default`
7. link `vendor` into `sites/default`
8. link `cnf/files` into `sites/default`

You have the choice of using a small commandline application or a script
handler.

## Commandline

```
vendor/bin/drupal_tangle -h
Usage:
drupal:tangle [options]

Options:
  -p, --project=PROJECT  Path to project to tangle
  -d, --drupal=DRUPAL    Path to drupal in which to tangle [default: "www"]
  -c, --copy             Copy all files and directories
  -h, --help             Display this help message
  -q, --quiet            Do not output any message
  -V, --version          Display this application version
  --ansi             Force ANSI output
  --no-ansi          Disable ANSI output
  -n, --no-interaction   Do not ask any interactive question
  -v|vv|vvv, --verbose   Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
```

## Composer Script Configuration

You can automate the use of the tangler in response to composer events like so:

```
{
...
    "scripts": {
        "post-install-cmd": [
          "Drupal\\Tangler\\ScriptHandler::postUpdate",
        ],
        "post-update-cmd": [
          "Drupal\\Tangler\\ScriptHandler::postUpdate"
        ]
    },
...
}
```

You can also pass parametors to the script using the extra portion of your
composer file like so:

```
{
...
    "extra": {
        "tangler": {
            "project": "/path/to/my/project"
            "drupal": "/path/to/my/document/root"
            "copy": true
        }
    }
...
}
```

By default if you don't specify, the parameters are as follows (cwd is current
working directory):

* project - cwd
* drupal - cwd/www
* copy - false (unless your machine cannot make symlinks, then true)

Note that you can just trigger the executable with these events, in which case
the values for the different `*-cmd` events above would be like this:

```
[
  "vendor/bin/drupal_tangle"
]
```

*Windows users cannot use the syntax above*


## Roadmap

* Allow appropriate configuration, such as name of drupal subdir, and origin of
  `settings.php`
* Support development of a theme or profile
* Support for placing things in the `sites/all/libraries` directory (not
  because this is ever a good idea, but because some projects require it)

## Not Ever Going To Be On The Roadmap

* Support for multi-site
