Danslo_LibraryRewrite
=====================

Why?
----

Please read [this](http://magento.stackexchange.com/questions/38555/modern-way-of-rewriting-lib-files) StackExchange post. In short: How magento is currently set up, we cannot change libraries and/or abstract classes without copying them entirely to an earlier loaded codepool (basically, include path) and editing them there. That really sucks when you need to make a bunch of changes to core libraries.

How does it work?
------------------

1. We register an autoloader.
2. While loading a class, the autoloader checks Magento configuration to see if you have any library rewrites configured.
3. When you do, it will:
    - Wrap the class in a namespace.
    - Replace extends for that class to use the global namespace (to prevent the Magento autoloader from choking).
    - Do the same for static property or class constant lookups (damn that Magento autoloader).
    - Stick this class in a temporary folder (currently ``/var/tmp/library_rewrite``).
    - Include the (namespaced) class.
    - Include your (non-namespaced) class.
4. You can now extend from the namespaced class and only change *some* of the functionality.

Installing
----------

1. If composer is not already installed, [do so](https://getcomposer.org/download/).
2. Create a ``composer.json`` with the following (or similar) contents:

```json
{
    "require": {
        "danslo/libraryrewrite": "dev-master"
    },
    "minimum-stability": "dev",
    "extra": {
        "magento-root-dir": "."
    },
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/danslo/LibraryRewrite.git"
        }
    ]
}
```

Finally, issue the ``composer install`` command.

Registering rewrites
--------------------

- Create an ordinary Magento module that depends on ``Danslo_LibraryRewrite``.
- In your ``config.xml``, add something like:

```xml
<?xml version="1.0"?>
<config>
    <global>
        <libraries>
            <rewrite>
                <The_Library_Class_To_Rewrite>YourNamespace_YourModule</The_Library_Class_To_Rewrite>
            </rewrite>
        </libraries>
    </global>
</config>
```

- Create a file in ``app/code/<YourCodePool>/<YourNamespace>/<YourModule>/lib/<PathToTheLibrary>``.
- The contents of the class should itself live in global namespace and extend from the Magento namespace:

```php
<?php

class The_Library_Class_To_Rewrite extends Magento\The_Library_Class_To_Rewrite
{
    // Rewrite any method in here.
}
```


License
-------

The MIT License (MIT)

Copyright (c) 2014 Daniel Sloof

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
