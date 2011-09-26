<?php

class Devel_Module extends Core_ModuleBase
{
    public static $engine = null;

    /**
     * Creates the module information object
     * @return Core_ModuleInfo
     */
    protected function createModuleInfo() {
        return new Core_ModuleInfo
        (
            "Development Tools",
            "Adds development information to the footer",
            "Patrick Heeney"
        );
    }

    public function subscribeEvents() {
        $continue = Phpr::$config->get('ENABLE_DEVELOPER_TOOLS', false);
        if( !$continue )
            return;

        self::$engine = Devel_Class::create();

        Backend::$events->addEvent('core:onInitialize', self::$engine, 'core_initialize');
        //Backend::$events->addEvent('core:onUninitialize', self::$engine, 'core_uninitialize');

        if( isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == "XMLHttpRequest" ) {
            Backend::$events->addEvent('cms:onBeforeHandleAjax', self::$engine, 'before_handle_ajax');
            Backend::$events->addEvent('cms:onAfterHandleAjax', self::$engine, 'after_handle_ajax');
        } else {
            Backend::$events->addEvent('cms:onBeforeDisplay', self::$engine, 'before_page_display');
            Backend::$events->addEvent('cms:onAfterDisplay', self::$engine, 'after_page_display');
        }

        Backend::$events->addEvent('core:onBeforeDatabaseQuery', self::$engine, 'on_before_query');
        Backend::$events->addEvent('core:onAfterDatabaseQuery', self::$engine, 'on_after_query');
    }
}
