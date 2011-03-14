README
======

What is XHProf?
---------------

XHProf is a hierarchical profiler for PHP. It reports function-level call counts and inclusive and exclusive metrics such as wall (elapsed) time, CPU time and memory usage.
A function's profile can be broken down by callers or callees. The raw data collection component is implemented in C as a PHP Zend extension called xhprof. 
XHProf has a simple HTML based user interface (written in PHP). The browser based UI for viewing profiler results makes it easy to view results or to share results with peers. 
A callgraph image view is also supported.

See [official documentation][1]

What does this Symfony 2 Bundle do?
-----------------------------------

This bundle helps you to easily use the XHProf bundle with the web debug toolbar in Symfony 2.


## Installation

### Get the bundle

To install the bundle, place it in the `src/Jns/Bundle` directory of your project
(so that it lives at `src/Jns/Bundle/XhprofBundle`). You can do this by adding
the bundle as a submodule, cloning it, or simply downloading the source.

    git submodule add https://github.com/jonaswouters/XhprofBundle.git src/Jns/Bundle/XhprofBundle

### Add the Jns namespace to your autoloader

If this is the first Jns bundle in your Symfony 2 project, you'll
need to add the `Jns` namespace to your autoloader. This file is usually located at `app/autoload.php`.

    $loader->registerNamespaces(array(
        'Jns'                       => __DIR__.'/../src'
        // ...
    ));

### Initializing the bundle

To initialize the bundle, you'll need to add it in your kernel. This
file is usually located at `app/AppKernel.php`. Loading it only in your dev environment is recommended.

    public function registerBundles()
    {
        // ...

        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
            // ...
            $bundles[] = new Jns\Bundle\XhprofBundle\JnsXhprofBundle();
        }
    )


### Configure the XHProf locations.

The Bundle comes preconfigured for the macports php5-xhprof default installation, 
with the xhprof web located at http://xhprof.localhost.
To change these settings for your environment you can override the defaults by
defining the following settings in your config. The config is usually located at `app/config/config.yml`.

    jns_xhprof:
        location.lib: /opt/local/www/php5-xhprof/xhprof_lib/utils/xhprof_lib.php
        location.runs: /opt/local/www/php5-xhprof/xhprof_lib/utils/xhprof_runs.php
        location.web: http://xhprof.localhost

[1]: http://mirror.facebook.net/facebook/xhprof/doc.html
