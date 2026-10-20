<?php
/**
 * 兼容转发文件（不要在这里写内容）。
 *
 * 历史：/contact/ 页面的模板原本就放在这个子目录里，页面 #21 的
 * _wp_page_template 存的是 "template-parts/page-contact.php" 这个子目录路径。
 * WordPress 的 locate_template() 能加载子目录文件，所以一直能正常工作，
 * 但不符合惯例，后台「页面模板」下拉也看不到它。
 *
 * 现状：模板正文已迁到主题根目录的 page-contact.php，
 * 页面 #21 的 _wp_page_template 也已改为 "page-contact.php"。
 *
 * 这个文件保留只为兼容两种情况：
 *   1. 万一有别的环境/子站点的 _wp_page_template 还指着这个旧路径；
 *   2. 主题更新时若数据库尚未迁移，不会出现空白页。
 *
 * ⚠️ 要改 /contact/ 的内容，请改主题根目录的 page-contact.php，不要改这里。
 *
 * @package TUSKOLIFT
 */

get_template_part( 'page-contact' );
