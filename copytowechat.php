<?php
/**
 * Plugin Name:       自媒体平台快速发布插件
 * Plugin URI:        https://www.wpstore.app/
 * Description:       自媒体平台快速发布插件支持通过在文章页面添加 ?wx 后缀，生成微信公众号后台的样式和快速复制按钮，帮助用户快速完成从 WordPress 到微信公众号文章发布。
 * Version:           0.0.4
 * Requires at least: 5.9
 * Requires PHP:      7.2
 * Author:            Bestony
 * Author URI:        https://www.ixiqin.com
 * License:           GPL v3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Update URI:        https: //www.wpstore.app/copytowechat
 * Text Domain:       copytowechat
 * Domain Path:       /languages
 */
class WPStoreApp_CopytToWeChatSettings
{
    public function init()
    {
        add_action('admin_init', [$this, 'settings_init']);

    }
    public function settings_init()
    {
        register_setting('reading', 'copy_to_wechat_css');
        add_settings_section(
            'settings_section',
            'Copy To WeChat Settings Section', [$this, 'settings_section_callback'],
            'reading'
        );
        add_settings_field(
            'settings_field',
            'CSS', [$this, 'settings_field_callback'],
            'reading',
            'settings_section'
        );
    }
    public function settings_section_callback()
    {
        echo '<p>Paste CSS Code here</p>';
    }

    public function settings_field_callback()
    {
        // get the value of the setting we've registered with register_setting()
        $setting = get_option('copy_to_wechat_css');
        // output the field
        ?>
            <textarea cols='40' rows='5'  name="copy_to_wechat_css"><?php echo isset($setting) ? esc_attr($setting) : ''; ?></textarea>
        <?php
}
}
class WPStoreApp_CopyToWechat
{
    public function init()
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_script_and_style']);
        add_filter('the_content', [$this, 'add_button_at_content_top']);
        add_filter('query_vars', [$this, 'add_custom_query_var']);
    }
    public function add_custom_query_var($vars)
    {
        $vars[] = "wx";
        return $vars;
    }
    public function enqueue_script_and_style()
    {
        if (!$this->is_should_show()) {
            return;
        }
        wp_enqueue_script('copy-to-wechat-clipboard', plugins_url('/js/clipboard.min.js', __FILE__));
        wp_enqueue_script('copy-to-wechat', plugins_url('/js/copytowechat.js', __FILE__));
    }
    public function add_button_at_content_top($content)
    {
        $copyButton = $this->build_copy_button();

        if (has_post_thumbnail()) {
            $imgCode = get_the_post_thumbnail();
        } else {
            $imgCode = '';

        }

        if ($this->is_should_show()) {
            $css = get_option('copy_to_wechat_css');
            $cssCode = '<style>' . $css . '</style>';
            return $cssCode . $copyButton . '<div id="copytowechat-content">' . $imgCode . $content . '</div>';
        } else {
            return $content;

        }
    }
    public function build_copy_button()
    {
        $contentButton = '<button class="copytowechat" data-clipboard-target="#copytowechat-content">复制文章内容</button>';
        $titleButton = '<button class="copytowechat" data-clipboard-text="' . get_the_title() . '">复制文章标题</button>';
        $linkButton = '<button class="copytowechat" data-clipboard-text="' . get_permalink() . '">复制阅读原文链接</button>';
        $userButton = '<button class="copytowechat" data-clipboard-text="' . get_the_author() . '">复制阅读作者名</button>';
        return '<p>' . $titleButton .$userButton . $contentButton . $linkButton . '</p>';
    }
    private function is_should_show()
    {
        // 是管理员 & 当前页面是文章页面/单页面/使用了 ?wx / 不在首页
        return current_user_can('administrator') && is_single() && array_key_exists('wx', $_GET) && !is_home();
    }
}
$plugin = new WPStoreApp_CopyToWechat();
$plugin->init();

$pluginSettings = new WPStoreApp_CopytToWeChatSettings();
$pluginSettings->init();