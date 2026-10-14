<?php
/**
 * Content Guard — 违法有害信息过滤 + 用户黑名单 (2026-10-14)
 *
 * 用途: 公安《交互式服务安全检查表》条款的落实
 *   - 9.3.3 违法有害信息过滤 (关键词屏蔽)
 *   - 9.2.2 c) 用户黑名单制度
 *
 * 接入点: comments-api.php (评论) / treehole.php (树洞) / login.php (注册用户名)
 * 设计: 命中禁止词 = 直接拒收 (先审后发站的过滤在"发布前"生效, 而非事后屏蔽)
 */

/**
 * 禁止词表 —— 基础违法有害信息关键词。
 * 类别: 赌博 / 诈骗非法交易 / 色情 / 毒品 / 暴恐武器 / 黑产广告。
 * 可按需扩充 (如接入公开违禁词库); 增删只改本函数即可, 全站生效。
 */
function guard_words(): array {
    static $words = null;
    if ($words === null) {
        $words = [
            // 赌博类
            '博彩', '赌博', '六合彩', '时时彩', '百家乐', '老虎机',
            // 诈骗 / 非法交易类
            '代开发票', '刷单', '洗钱', '高利贷', '无抵押贷款', '出售银行卡',
            '买卖账号', '个人信息贩卖', '数据出售',
            // 色情类
            '色情', '约炮', '裸聊', '楼凤',
            // 毒品类
            '冰毒', '海洛因', '摇头丸', '氯胺酮', '麻古',
            // 暴恐 / 武器类
            '爆炸物', '雷管', '管制刀具', '枪支弹药',
            // 黑产广告类
            '黑客接单',
        ];
    }
    return $words;
}

/** 检查文本是否含禁止词; 返回命中的词, 通过则返回 '' */
function guard_hit(string $text): string {
    if ($text === '') return '';
    $lower = mb_strtolower($text);
    foreach (guard_words() as $w) {
        if (mb_strpos($lower, mb_strtolower($w)) !== false) return $w;
    }
    return '';
}

/** 用户是否在黑名单 (users.json 的 banned 字段); 匿名/未注册返回 false */
function guard_user_banned(string $username): bool {
    if ($username === '') return false;
    $users = json_decode(@file_get_contents(__DIR__ . '/../users.json') ?: '[]', true);
    if (!is_array($users)) return false;
    foreach ($users as $u) {
        if (($u['username'] ?? '') === $username) return !empty($u['banned']);
    }
    return false;
}
