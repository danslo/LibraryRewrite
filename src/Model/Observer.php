<?php

class Danslo_LibraryRewrite_Model_Observer
{

    /**
     * Since we use a very generic observer that is called multiple times,
     * make sure we only add the autoloader once.
     *
     * @var bool
     */
    protected static $_addAutoloader = true;

    /**
     * Instantiates and registers the autoloader.
     *
     * @return void
     */
    public function addAutoloader()
    {
        if (!self::$_addAutoloader) {
            return;
        }
        Mage::getModel('library_rewrite/autoloader')->register();
        self::$_addAutoloader = false;
    }

}
