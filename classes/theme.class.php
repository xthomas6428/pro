<?php

class theme
{
    /**
     * @return array|bool|string|null
     */
    public static function current()
    {
        return getSetting('theme', 'value');
    }

    /**
     * @param  bool  $default
     * @param  bool  $settings
     * @return string
     */
    public static function options(bool $default = true, bool $settings = false): string
    {
        $current = self::current();

        if (!$settings && isset($_COOKIE['prometheus_theme']) && !getSetting('disable_theme_selector', 'value2')) {
            $current = $_COOKIE['prometheus_theme'];
        }

        $dirs = scandir('themes');
        unset($dirs[0], $dirs[1]);

        $ret = '';
        if ($default) {
            $ret = '<option value="">Default</option>';
        }

        $active = '';

        foreach ($dirs as $themes) {
            if ($themes === $current && $default) {
                $active = 'selected';
            }

            $ret .= '<option value="'.$themes.'" '.$active.'>'.$themes.'</option>';
            $active = '';
        }

        return $ret;
    }

    /**
     * @param $theme
     */
    public static function del($theme): void
    {
        recursiveDelete('themes/'.$theme);
    }
}
