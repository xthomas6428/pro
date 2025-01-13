<?php

class options
{
    public static function getPackages($p = '', $t = '', $cat = 'none'): string
    {
        global $db;

        $ret = '';

        if ($t === 'coupons') {
            $res = $db->getAll("SELECT id, title FROM packages");

            if (!empty($p)) {
                $packages = $db->getOne("SELECT packages FROM coupons WHERE id = ?", $p);
                $packages = json_decode($packages, true);
            }

            foreach ($res as $row) {
                $id = $row['id'];
                $title = $row['title'];

                $selected = '';
                if (!empty($p) && in_array($id, $packages)) {
                    $selected = 'selected';
                }

                $ret .= '
                    <option value="'.$id.'" '.$selected.'>'.$id.' - '.$title.'</option>
                ';
            }
        }

        if ((empty($p) && empty($t)) || (empty($p) && $t === 'raffle')) {
            $res = $db->getAll("SELECT id, title FROM packages");

            foreach ($res as $row) {
                $id = $row['id'];
                $title = $row['title'];

                $ret .= '
                    <option value="'.$id.'">'.$id.' - '.$title.'</option>
                ';
            }
        }

        if (!empty($p) && empty($t)) {
            if ($cat === 'none') {
                $res = $db->getAll("SELECT id, title FROM packages WHERE servers LIKE ?", '%"'.$p.'"%');
            } else {
                $res = $db->getAll("SELECT id, title FROM packages WHERE servers LIKE ? AND category = ?", [
                    '%"'.$p.'"%', $cat
                ]);
            }

            $ret = "";

            foreach ($res as $row) {
                $id = $row['id'];
                $title = $row['title'];

                $ret .= '
                    <option value="'.$id.'">'.$id.' - '.$title.'</option>
                ';
            }
        }

        if (empty($p) && $t === 'featured') {
            $featured = (int) getSetting('featured_package', 'value2');
            $ret = '';

            $res = $db->getAll("SELECT id, title FROM packages WHERE id != ?", $featured);
            $optionName = $db->getOne("SELECT title FROM packages WHERE id = ?", $featured);

            if ($featured !== 0) {
                $ret .= '
                    <option value="'.$featured.'" selected>'.$featured.' - '.$optionName.'</option>
                ';
            }

            foreach ($res as $row) {
                $id = $row['id'];
                $title = $row['title'];

                $ret .= '
                    <option value="'.$id.'">'.$id.' - '.$title.'</option>
                ';
            }
        }

        if (!empty($p) && $t === 'raffle') {
            $exc = $db->getAll("SELECT id, title FROM packages WHERE id = ?", $p);
            $ret = "";

            foreach ($exc as $row) {
                $ret .= '
                    <option value="'.$row['id'].'">'.$row['id'].' - '.$row['title'].'</option>
                ';
            }

            $res = $db->getAll("SELECT id, title FROM packages WHERE id != ?", $p);

            foreach ($res as $row) {
                $id = $row['id'];
                $title = $row['title'];

                $ret .= '
                    <option value="'.$id.'">'.$id.' - '.$title.'</option>
                ';
            }
        }

        return $ret;
    }

    public static function getServerGame($id = ''): string
    {
        global $db;

        $ret = '';

        $gameArray = [
            'gmod' => "Garry's Mod",
            'sm' => "SourceMod Enabled Game",
        ];

        if ($id !== '') {
            $game = $db->getOne("SELECT game FROM servers WHERE id = ?", $id);

            foreach ($gameArray as $key => $value) {
                $selected = '';
                if ($game === $key) {
                    $selected = 'selected';
                }

                $ret .= '
						<option value="'.$key.'" '.$selected.'>'.$value.'</option>
					';
            }
        } else {
            foreach ($gameArray as $key => $value) {
                $ret .= '
                    <option value="'.$key.'">'.$value.'</option>
                ';
            }
        }

        return $ret;
    }

    public static function getServers($p = false): string
    {
        global $db;

        $ret = '';

        if (!$p) {
            $res = $db->getAll("SELECT * FROM servers");
        } else {
            $server_id = $db->getOne("SELECT server FROM packages WHERE id = ?", [$p])['server'];
            $res = $db->getAll("SELECT * FROM servers WHERE id = ?", [$server_id]);
            foreach ($res as $row) {
                $id = $row['id'];
                $name = $row['name'];

                $ret .= '
                    <option value="'.$id.'" selected="selected">'.$name.'</option>
                ';
            }

            $res = $db->getAll("SELECT * FROM servers WHERE id != ?", [$server_id]);
        }

        foreach ($res as $row) {
            $id = $row['id'];
            $name = $row['name'];

            $ret .= '
                <option value="'.$id.'">'.$name.'</option>
            ';
        }

        return $ret;
    }

    public static function getCategories($p = false): string
    {
        global $db;

        $ret = '';

        if (empty($p)) {
            $res = $db->getAll("SELECT * FROM categories");
        } else {
            $cat_id = $db->getOne("SELECT category FROM packages WHERE id = ?", [$p])['category'];
            $res = $db->getAll("SELECT * FROM categories WHERE id = ?", [$cat_id]);
            foreach ($res as $row) {
                $id = $row['id'];
                $name = $row['name'];

                $ret .= '
                    <option value="'.$id.'" selected="selected">'.$name.'</option>
                ';
            }

            $res = $db->getAll("SELECT * FROM categories WHERE id != ?", [$cat_id]);
        }
        foreach ($res as $row) {
            $id = $row['id'];
            $name = $row['name'];

            $ret .= '
                <option value="'.$id.'">'.$name.'</option>
            ';
        }

        return $ret;
    }

    public static function getRaffles(): string
    {
        global $db;

        $res = $db->getAll("SELECT id, title FROM raffles ORDER BY id");
        $ret = '';
        foreach ($res as $row) {
            $title = $row['title'];
            $id = $row['id'];

            $ret .= '
                <option value="'.$id.'">'.$id.' - '.$title.'</option>
            ';
        }

        return $ret;
    }

    public static function getCurrencies($p = '', $t = ''): string
    {
        global $db;

        $ret = '';

        if (empty($p)) {
            $res = $db->getAll("SELECT * FROM currencies");
            foreach ($res as $row) {
                $id = $row['id'];
                $cc = $row['cc'];

                $ret .= '
                    <option value="'.$id.'">
                        '.$cc.'
                    </option>
                ';
            }
        }

        if (!empty($p) && empty($t)) {
            $res = $db->getAll("SELECT * FROM currencies WHERE id = ?", [$p]);
            foreach ($res as $row) {
                $id = $row['id'];
                $cc = $row['cc'];

                $ret .= '
                    <option value="'.$id.'" selected="selected">
                        '.$cc.'
                    </option>
                ';
            }

            $res = $db->getAll("SELECT * FROM currencies WHERE id != ?", [$p]);
            foreach ($res as $row) {
                $id = $row['id'];
                $cc = $row['cc'];

                $ret .= '
                    <option value="'.$id.'">
                        '.$cc.'
                    </option>
                ';
            }
        }

        if (!empty($p) && $t === 'packages') {
            $cur_id = $db->getOne("SELECT currency FROM packages WHERE id = ?", [$p])['currency'];
            $res = $db->getAll("SELECT * FROM currencies WHERE id = ?", [$cur_id]);
            foreach ($res as $row) {
                $id = $row['id'];
                $cc = $row['cc'];

                $ret .= '
                    <option value="'.$id.'" selected="selected">
                        '.$cc.'
                    </option>
                ';
            }

            $res = $db->getAll("SELECT * FROM currencies WHERE id != ?", [$cur_id]);
            foreach ($res as $row) {
                $id = $row['id'];
                $cc = $row['cc'];

                $ret .= '
                    <option value="'.$id.'">
                        '.$cc.'
                    </option>
                ';
            }
        }

        if (!empty($p) && $t === 'credits') {
            $cur_id = $db->getOne("SELECT currency FROM credit_packages WHERE id = ?", [$p])['currency'];
            $res = $db->getAll("SELECT * FROM currencies WHERE id = ?", [$cur_id]);
            foreach ($res as $row) {
                $id = $row['id'];
                $cc = $row['cc'];

                $ret .= '
                    <option value="'.$id.'" selected="selected">
                        '.$cc.'
                    </option>
                ';
            }

            $res = $db->getAll("SELECT * FROM currencies WHERE id != ?", [$cur_id]);
            foreach ($res as $row) {
                $id = $row['id'];
                $cc = $row['cc'];

                $ret .= '
                    <option value="'.$id.'">
                        '.$cc.'
                    </option>
                ';
            }
        }

        if (!empty($p) && $t === 'raffle') {
            $cur_id = $db->getOne("SELECT currency FROM raffles WHERE id = ?", [$p])['currency'];
            $res = $db->getAll("SELECT * FROM currencies WHERE id = ?", [$cur_id]);
            foreach ($res as $row) {
                $id = $row['id'];
                $cc = $row['cc'];

                $ret .= '
                    <option value="'.$id.'" selected="selected">
                        '.$cc.'
                    </option>
                ';
            }

            $res = $db->getAll("SELECT * FROM currencies WHERE id != ?", [$cur_id]);
            foreach ($res as $row) {
                $id = $row['id'];
                $cc = $row['cc'];

                $ret .= '
                    <option value="'.$id.'">
                        '.$cc.'
                    </option>
                ';
            }
        }

        return $ret;
    }

    public static function getCreditPackages(): string
    {
        global $db;

        $ret = '';

        $res = $db->getAll("SELECT * FROM credit_packages");
        foreach ($res as $row) {
            $id = $row['id'];
            $title = $row['title'];

            $ret .= '
                <option value="'.$id.'">
                    '.$title.'
                </option>
            ';
        }

        return $ret;
    }

    public static function getRuntype($id, $type): string
    {
        global $db;

        $ret = '';

        $runtype_a = [
            0 => "At first join(Default)",
            1 => "At every join",
            2 => "At every spawn",
            3 => "Instant",
        ];

        if (!empty($id)) {
            $res = $db->getOne("SELECT actions FROM packages WHERE id = ?", [$id])['actions'];
            $array = json_decode($res, true);

            $runtype = isset($array[$type]) ? $array[$type]['runtype'] : 0;

            foreach ($runtype_a as $key => $value) {
                $selected = '';
                if ($key == $runtype) {
                    $selected = 'selected';
                }

                if ($type === 'weapons' && $key == 3) {
                    break;
                }

                $ret .= '
                    <option value="' . $key . '" ' . $selected . '>' . $value . '</option>
                ';
            }
        } elseif ($type === 'weapons') {
            $ret = '
                <option value="0">At first join</option>
                <option value="1">At every join</option>
                <option value="2">At every spawn</option>
            ';
        } else {
            $ret = '
                <option value="0">At first join(Default)</option>
                <option value="1">At every join</option>
                <option value="2">At every spawn</option>
                <option value="3">Instant</option>
            ';
        }

        return $ret;
    }

    public static function getCoupons(): string
    {
        global $db;

        $ret = '';
        $res = $db->getAll("SELECT id, coupon FROM coupons");

        if ($res) {
            foreach ($res as $row) {
                $id = $row['id'];
                $coupon = $row['coupon'];

                $ret .= '<option value="'.$id.'">'.$coupon.'</option>';
            }
        }

        return $ret;
    }

    public static function languages(): string
    {
        global $spl_root, $language;

        // list mods
        $exclude = [
            '.',
            '..',
            '.git',
            'README.txt',
        ];

        $scan = scandir($spl_root.'lang');
        $files = array_diff($scan, $exclude);

        $ret = '';

        if (isset($_COOKIE['prometheus_language']) && !empty($_COOKIE['prometheus_language'])) {
            $language = $_COOKIE['prometheus_language'];
        }

        foreach ($files as $file) {
            $file = explode('.', $file);
            $file = $file[0];

            $selected = '';
            if ($file === $language) {
                $selected = ' selected';
            }

            $ret .= '<option value="'.$file.'"'.$selected.'>'.$file.'</option>';
        }

        return $ret;
    }
}
