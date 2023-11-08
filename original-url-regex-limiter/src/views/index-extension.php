<?php

// no direct call
if (!defined('YOURLS_ABSPATH')) die();

// load dependencies
require_once(dirname(__DIR__) . '/controllers/enforcer.php');
require_once(dirname(__DIR__) . '/models/dict.php');
require_once(dirname(__DIR__) . '/models/options.php');

/**
 * This page extends the admin/index.php core page with functionality provided by our plugin.
 */
class UniwueUrlLimiterIndexExtensionView
{
    // url regex limiter filter (select) key and values
    private const PARAM_KEY_LIMITER_STATUS = 'uniwue_url_limiter_status_filter';
    private const PARAM_VALUE_LIMITER_STATUS_ALL = 'all';
    private const PARAM_VALUE_LIMITER_STATUS_ALLOWED = 'allowed';
    private const PARAM_VALUE_LIMITER_STATUS_BLOCKED = 'blocked';
    private const PARAM_VALUE_LIMITER_STATUS_UNDECIDED = 'undecided';


    // hooks

    /**
     * Adds the url regex limiter status filter to the SQL where clause.
     *
     * @param array $where
     * @return array
     */
    public static function filter_where(array $where): array
    {
        // gather required options
        $options = UniwueUrlLimiterOptions::get_instance();
        $url_allow_list = $options->get_url_allow_list()->to_sql_string(
            UniwueUrlLimiterRegexFormList::REGEX_MATCH_ALL
        );
        $url_block_list = $options->get_url_block_list()->to_sql_string(
            UniwueUrlLimiterRegexFormList::REGEX_MATCH_NONE
        );

        // add where filter depending on the requested status
        switch ($_REQUEST[self::PARAM_KEY_LIMITER_STATUS] ?? null) {
            case self::PARAM_VALUE_LIMITER_STATUS_ALLOWED:
                $where['sql'] .= " AND (`url` REGEXP '$url_allow_list') AND (`url` NOT REGEXP '$url_block_list')";
                break;
            case self::PARAM_VALUE_LIMITER_STATUS_BLOCKED:
                $where['sql'] .= " AND (`url` NOT REGEXP '$url_allow_list') OR (`url` REGEXP '$url_block_list')";
                break;
            case self::PARAM_VALUE_LIMITER_STATUS_UNDECIDED:
                $where['sql'] .= " AND (`url` NOT REGEXP '$url_allow_list') AND (`url` NOT REGEXP '$url_block_list')";
                break;
        }

        return $where;
    }

    /**
     * Pre-renders the url regex limiter status filter select to the footer at the index page.
     *
     * @return void
     */
    public static function action_footer(): void
    {
        if (!self::is_index_page()) {
            return;
        }

        $_select = yourls_html_select(
            self::PARAM_KEY_LIMITER_STATUS,
            [
                self::PARAM_VALUE_LIMITER_STATUS_ALL => UniwueUrlLimiterDict::translate('status_all'),
                self::PARAM_VALUE_LIMITER_STATUS_ALLOWED => UniwueUrlLimiterDict::translate('status_allowed'),
                self::PARAM_VALUE_LIMITER_STATUS_BLOCKED => UniwueUrlLimiterDict::translate('status_blocked'),
                self::PARAM_VALUE_LIMITER_STATUS_UNDECIDED => UniwueUrlLimiterDict::translate('status_undecided'),
            ],
            $_GET[self::PARAM_KEY_LIMITER_STATUS] ?? self::PARAM_VALUE_LIMITER_STATUS_ALL,
            false
        );
        printf(
            '<div id="uniwue-url-limiter-table-control-limiter-status">%s</div>',
            sprintf(UniwueUrlLimiterDict::translate('status_filter_template'), $_select)
        );
    }

    /**
     * Adds the url regex limiter status head in the main table.
     *
     * @param array $cells
     * @return array
     */
    public static function filter_head(array $cells): array
    {
        array_splice($cells, 2, 0, ['' => UniwueUrlLimiterDict::translate('status')]);

        return $cells;
    }

    /**
     * Adds the url regex limiter status cells in the main table.
     *
     * @param array $cells
     * @param string $keyword
     * @param string $url
     * @param string $title
     * @param string $ip
     * @param integer $clicks
     * @param integer $timestamp
     * @return array
     */
    public static function filter_cells(array $cells, string $keyword, string $url, string $title, string $ip, int $clicks, int $timestamp): array
    {
        $id = $cells['actions']['id'];
        $is_allowed = UniwueUrlLimiterEnforcerController::is_url_allowed($url);
        $base_url = yourls_plugin_url(dirname(__DIR__, 2));

        $url_limiter_status_cell = [
            'template' => sprintf(
                '<div class="uniwue-url-limiter-table-url-column">
                    <img id="uniwue-url-limiter-icon-check-%s" class="uniwue-url-limiter-table-url-icon" src="%s/assets/imgs/check.svg" style="display: %s" title="%s"/>
                    <img id="uniwue-url-limiter-icon-cross-%s" class="uniwue-url-limiter-table-url-icon" src="%s/assets/imgs/cross.svg" style="display: %s" title="%s"/>
                </div>',
                $id,
                $base_url,
                $is_allowed ? 'inline-block' : 'none',
                UniwueUrlLimiterDict::translate('success_status_allowed'),
                $id,
                $base_url,
                $is_allowed ? 'none' : 'inline-block',
                UniwueUrlLimiterDict::translate('error_status_blocked', true)
            )
        ];

        return array_slice($cells, 0, 2, true) + ['uniwue-url-limiter-status' => $url_limiter_status_cell] + array_slice($cells, 2, null, true);
    }

    /**
     * Adds the "toggle by domain" action to the list of actions for eligible users.
     *
     * @param array $actions
     * @param string $keyword
     * @return array
     */
    public static function filter_actions(array $actions, string $keyword): array
    {
        if (!UniwueUrlLimiterAuthController::has_capability(UniwueUrlLimiterAuthController::ACTION_TOGGLE_BY_DOMAIN)) {
            return $actions;
        }

        $id = substr($actions['share']['id'], 13);
        $url = yourls_plugin_url(__DIR__);
        $action = UniwueUrlLimiterAuthController::ACTION_TOGGLE_BY_DOMAIN;
        $nonce = yourls_create_nonce(UNIWUE_URL_LIMITER_PREFIX . $action);
        $title = UniwueUrlLimiterDict::translate('action_toggle_domain');
        $fail_msg = UniwueUrlLimiterDict::translate('error', true);

        $actions['uniwue-url-limiter-toggle-by-domain'] = [
            'href'    => "$url/ajax.php?id=$id&action=$action&keyword=$keyword&nonce=$nonce",
            'id'      => "$action-$id",
            'title'   => $title,
            'anchor'  => $title,
            'onclick' => "uniwue_url_limiter_status_toggle_by_domain('$id', '$fail_msg');return false;"
        ];

        return $actions;
    }


    // utility function

    /**
     * Returns whether the current page is the index page.
     *
     * @return boolean
     */
    private static function is_index_page(): bool
    {
        return strpos($_SERVER['REQUEST_URI'], parse_url(yourls_admin_url() . 'index.php', PHP_URL_PATH)) === 0;
    }
}


// register hooks
yourls_add_filter('admin_list_where', [UniwueUrlLimiterIndexExtensionView::class, 'filter_where']);
yourls_add_filter('table_head_cells', [UniwueUrlLimiterIndexExtensionView::class, 'filter_head']);
yourls_add_filter('table_add_row_cell_array', [UniwueUrlLimiterIndexExtensionView::class, 'filter_cells']);
yourls_add_filter('table_add_row_action_array', [UniwueUrlLimiterIndexExtensionView::class, 'filter_actions']);
yourls_add_action('html_footer', [UniwueUrlLimiterIndexExtensionView::class, 'action_footer']);
