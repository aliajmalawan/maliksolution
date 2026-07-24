<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Request;

class PageView
{
    public static function record(Request $request): void
    {
        Database::query(
            'INSERT INTO page_views (url, ip, user_agent, referrer, created_at) VALUES (?, ?, ?, ?, NOW())',
            [
                substr($request->path(), 0, 500),
                $request->ip(),
                $request->userAgent(),
                isset($_SERVER['HTTP_REFERER']) ? substr($_SERVER['HTTP_REFERER'], 0, 500) : null,
            ]
        );
    }

    public static function countTotal(): int
    {
        return (int) Database::one('SELECT COUNT(*) AS c FROM page_views')['c'];
    }

    public static function countSince(int $days): int
    {
        return (int) Database::one(
            'SELECT COUNT(*) AS c FROM page_views WHERE created_at >= (NOW() - INTERVAL ? DAY)',
            [$days]
        )['c'];
    }

    /** @return array<string,int> date (Y-m-d) => view count, zero-filled for days with no views */
    public static function dailyCounts(int $days): array
    {
        $rows = Database::all(
            "SELECT DATE(created_at) AS day, COUNT(*) AS c FROM page_views
             WHERE created_at >= (NOW() - INTERVAL ? DAY)
             GROUP BY DATE(created_at)",
            [$days]
        );

        $byDay = array_column($rows, 'c', 'day');

        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $result[$date] = (int) ($byDay[$date] ?? 0);
        }

        return $result;
    }

    public static function topPages(int $limit = 5): array
    {
        return Database::all(
            'SELECT url, COUNT(*) AS views FROM page_views GROUP BY url ORDER BY views DESC LIMIT ' . (int) $limit
        );
    }

    public static function uniqueVisitorsSince(int $days): int
    {
        return (int) Database::one(
            'SELECT COUNT(DISTINCT ip) AS c FROM page_views WHERE created_at >= (NOW() - INTERVAL ? DAY)',
            [$days]
        )['c'];
    }

    public static function onlineNow(int $minutes = 5): int
    {
        return (int) Database::one(
            'SELECT COUNT(DISTINCT ip) AS c FROM page_views WHERE created_at >= (NOW() - INTERVAL ? MINUTE)',
            [$minutes]
        )['c'];
    }

    public static function countToday(): int
    {
        return (int) Database::one('SELECT COUNT(*) AS c FROM page_views WHERE DATE(created_at) = CURDATE()')['c'];
    }

    public static function countYesterday(): int
    {
        return (int) Database::one('SELECT COUNT(*) AS c FROM page_views WHERE DATE(created_at) = (CURDATE() - INTERVAL 1 DAY)')['c'];
    }

    public static function countThisMonth(): int
    {
        return (int) Database::one("SELECT COUNT(*) AS c FROM page_views WHERE YEAR(created_at) = YEAR(NOW()) AND MONTH(created_at) = MONTH(NOW())")['c'];
    }

    public static function countThisYear(): int
    {
        return (int) Database::one('SELECT COUNT(*) AS c FROM page_views WHERE YEAR(created_at) = YEAR(NOW())')['c'];
    }

    public static function recent(int $limit = 20): array
    {
        return Database::all('SELECT url, ip, user_agent, referrer, created_at FROM page_views ORDER BY id DESC LIMIT ' . (int) $limit);
    }

    /** @return array{browsers: array<string,int>, systems: array<string,int>, devices: array<string,int>} */
    public static function agentBreakdown(int $days = 30): array
    {
        $rows = Database::all(
            'SELECT user_agent, COUNT(*) AS c FROM page_views WHERE created_at >= (NOW() - INTERVAL ? DAY) GROUP BY user_agent',
            [$days]
        );

        $browsers = [];
        $systems = [];
        $devices = [];
        foreach ($rows as $row) {
            $count = (int) $row['c'];
            $parsed = self::classifyUserAgent((string) $row['user_agent']);
            $browsers[$parsed['browser']] = ($browsers[$parsed['browser']] ?? 0) + $count;
            $systems[$parsed['system']] = ($systems[$parsed['system']] ?? 0) + $count;
            $devices[$parsed['device']] = ($devices[$parsed['device']] ?? 0) + $count;
        }
        arsort($browsers);
        arsort($systems);
        arsort($devices);

        return ['browsers' => $browsers, 'systems' => $systems, 'devices' => $devices];
    }

    /** @return array{browser: string, system: string, device: string} */
    public static function classifyUserAgent(string $ua): array
    {
        $browser = 'Other';
        if (stripos($ua, 'Edg') !== false) { $browser = 'Edge'; }
        elseif (stripos($ua, 'OPR') !== false || stripos($ua, 'Opera') !== false) { $browser = 'Opera'; }
        elseif (stripos($ua, 'SamsungBrowser') !== false) { $browser = 'Samsung Internet'; }
        elseif (stripos($ua, 'Firefox') !== false) { $browser = 'Firefox'; }
        elseif (stripos($ua, 'Chrome') !== false || stripos($ua, 'CriOS') !== false) { $browser = 'Chrome'; }
        elseif (stripos($ua, 'Safari') !== false) { $browser = 'Safari'; }
        elseif ($ua !== '' && $ua !== 'unknown') { $browser = 'Other'; }

        $system = 'Other';
        if (stripos($ua, 'Windows') !== false) { $system = 'Windows'; }
        elseif (stripos($ua, 'Android') !== false) { $system = 'Android'; }
        elseif (stripos($ua, 'iPhone') !== false || stripos($ua, 'iPad') !== false || stripos($ua, 'iOS') !== false) { $system = 'iOS'; }
        elseif (stripos($ua, 'Mac OS') !== false || stripos($ua, 'Macintosh') !== false) { $system = 'macOS'; }
        elseif (stripos($ua, 'Linux') !== false) { $system = 'Linux'; }

        $device = 'Desktop';
        if (stripos($ua, 'iPad') !== false || stripos($ua, 'Tablet') !== false) { $device = 'Tablet'; }
        elseif (stripos($ua, 'Mobi') !== false || stripos($ua, 'Android') !== false || stripos($ua, 'iPhone') !== false) { $device = 'Mobile'; }

        return ['browser' => $browser, 'system' => $system, 'device' => $device];
    }

    /** External referrers only — visits that came from another website. */
    public static function topReferrers(int $limit = 10): array
    {
        return Database::all(
            "SELECT referrer, COUNT(*) AS views FROM page_views
             WHERE referrer IS NOT NULL AND referrer != ''
               AND referrer NOT LIKE CONCAT('%', ?, '%')
             GROUP BY referrer ORDER BY views DESC LIMIT " . (int) $limit,
            [$_SERVER['HTTP_HOST'] ?? 'localhost']
        );
    }
}
