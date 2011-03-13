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


Configuration
-------------

  1. Define the module in your app/AppKernel.php file

        $bundles[] = new Jns\Bundle\XhprofBundle\JnsXhprofBundle();

  2. Add the Jns namespace to your app/autoload.php file


  3. Configure the XHProf locations.

        Work in progres...

[1]: http://mirror.facebook.net/facebook/xhprof/doc.html
