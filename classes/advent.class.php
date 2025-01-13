<?php

class advent
{
    public static function populate(): void
    {
        global $db;

        $res = $db->getOne("SELECT count(*) AS count FROM advent_calendar");

        if (!$res) {
            // Table is empty

            $db->execute("INSERT INTO advent_calendar (day, img, package) VALUES
                (1, 'http://orig00.deviantart.net/1b08/f/2010/196/f/7/christmas_in_garry__s_mod_by_xxzealxx82.jpg', NULL),
                (2, 'http://i388.photobucket.com/albums/oo324/Dark_Magician_MF/TF%202/Garrys%20Mod/Christmas2010.jpg', NULL),
                (3, 'http://s2.dmcdn.net/IJofj/1280x720-eF-.jpg', NULL),
                (4, 'http://media.moddb.com/images/downloads/1/40/39016/dm_christmas_in_the_suburbs0004.jpg', NULL),
                (5, 'http://media.moddb.com/images/downloads/1/39/38675/dm_christmas_bungalow0001.jpg', NULL),
                (6, 'http://s1.dmcdn.net/IG6Kr/1280x720-fpc.jpg', NULL),
                (7, 'http://bbsimg.ngfiles.com/15/20521000/ngbbs4b34011fa9154.jpg', NULL),
                (8, 'http://img09.deviantart.net/8fcf/i/2013/358/9/c/_garry_s_mod__and_thus_i_shall_be_christmas_tree__by_mechaelite-d6z98so.jpg', NULL),
                (9, 'http://local-static0.forum-files.fobby.net/forum_attachments/0020/0210/gm_snowstruct20000.jpg', NULL),
                (10, 'http://orig08.deviantart.net/394e/f/2012/338/d/a/gmod__christmas_party_by_jayemeraldover9000x-d5n0xsd.jpg', NULL),
                (11, 'http://content.ytmnd.com/content/b/8/6/b86df0da6b862be2569b64eacf6fcf98.jpg', NULL),
                (12, 'http://i.cubeupload.com/Air7I2.jpg', NULL),
                (13, 'http://bbsimg.ngfiles.com/15/20514000/ngbbs4b32351345bc7.jpg', NULL),
                (14, 'https://i.ytimg.com/vi/9lpcHaqi1lw/maxresdefault.jpg', NULL),
                (15, 'http://i.ytimg.com/vi/WAxbjqtkYvE/maxresdefault.jpg', NULL),
                (16, 'http://media.tumblr.com/tumblr_lwpcy26NXg1qliuhb.png', NULL),
                (17, 'http://fc08.deviantart.net/fs71/i/2012/358/2/9/merry_christmas_from_gmod_by_segajosh3-d5p2tmv.jpg', NULL),
                (18, 'http://orig10.deviantart.net/bd51/f/2009/352/7/d/rp_christmastown___gmod_by_theofficalmiga.jpg', NULL),
                (19, 'http://img05.deviantart.net/2360/i/2010/336/8/c/gmod_christmas_by_dinmamma3000-d341zld.jpg', NULL),
                (20, 'http://cloud-2.steamusercontent.com/ugc/542945375824654298/CBD312F9F24BD6E0F8702333DE7D0D4014EE4156/637x358.resizedimage', NULL),
                (21, 'http://files.gamebanana.com/img/ss/maps/4eda543066eb8.jpg', NULL),
                (22, 'https://files.garrysmods.org/14499/1/1024x768.jpg', NULL),
                (23, 'http://fc08.deviantart.net/fs70/f/2013/262/6/d/invasion_of_christmas_trees_by_madman5333-d6my5bt.jpg', NULL),
                (24, 'http://img13.deviantart.net/70cd/i/2010/120/b/e/christmas_cheers_by_garrys_mod_dude.jpg', NULL)
            ");
        }
    }

    public static function update($values, $pkg): void
    {
        global $db;

        for ($i=1; $i <= 24; $i++) {
            if (!isset($pkg[$i])) {
                $pkg[$i] = [];
            }

            $image = $values['image'][$i];
            $random = empty($values['random'][$i]) ? null : (int)$values['random'][$i];
            $credits = empty($values['credits'][$i]) ? null : $values['credits'][$i];

            $db->execute("UPDATE advent_calendar SET img = ?, random = ?, credits = ?, package = ? WHERE day = ?", [
                $image, $random, $credits, json_encode($pkg[$i]), $i
            ]);
        }
    }

    public static function getForm(): string
    {
        global $db;

        $ret = '';
        $res = $db->getAll("SELECT * FROM advent_calendar");

        $template = '
            <div class="col-md-4">
                <div class="form-group darker-box">
                    <h6>%DAY%</h6>

                    <select class="selectpicker bs-select-hidden" multiple="" data-live-search="true" name="pkg[%PLAINDAY%][]" title="Select packages\'s" data-style="btn-prom" data-width="100%">
                        %OPTIONS%
                    </select>
                    
                    <input type="number" step="1" max="100" min="0" name="random[%PLAINDAY%]" class="form-control" placeholder="Random chance (0 or 100 for a 100% chance)" style="margin-top: 5px;" value="%RANDOM%">
                    
                    <input type="number" step="1" name="credits[%PLAINDAY%]" class="form-control" placeholder="Credits to give (0 or blank if none)" style="margin-top: 5px;" value="%CREDITS%">
                    
                    <input type="text" name="image[%PLAINDAY%]" class="form-control" placeholder="Image background" style="margin-top: 10px;" value="%IMAGE%">
                </div>
            </div>
        ';

        if ($res) {
            $packages = $db->getAll("SELECT id, title FROM packages");

            foreach ($res as $row) {
                $image = $row['img'];
                $day = lang('day') . ' ' . $row['day'];
                $plainday = $row['day'];
                $random = $row['random'];
                $package = $row['package'];
                $credits = $row['credits'];

                if (empty($package) || $package === 'null') {
                    $package = '[]';
                }

                $package = json_decode($package, true);

                $options = '';

                foreach ($packages as $pkg) {
                    $selected = '';

                    $id = $pkg['id'];
                    $title = $pkg['title'];

                    if (in_array($id, $package)) {
                        $selected = 'selected';
                    }

                    $options .= '<option value="'.$id.'" '.$selected.'>'.$title.'</option>';
                }

                $temp = $template;

                $temp = str_replace([
                    '%DAY%',
                    '%OPTIONS%',
                    '%IMAGE%',
                    '%RANDOM%',
                    '%CREDITS%',
                    '%PLAINDAY%'
                ], [
                    $day,
                    $options,
                    $image,
                    $random,
                    $credits,
                    $plainday
                ], $temp);

                $ret .= $temp;
            }
        }

        return $ret;
    }

    public static function getPage(): string
    {
        global $db;

        $ret = '';
        $res = $db->getAll("SELECT * FROM advent_calendar");

        $template = '
            <div class="col-lg-2 col-md-3">
                %LINKSTART%
                <div class="advent-box %CLAIMED% %NOTYET%">
                    <img src="%IMAGE%">
                    <span>%DAY%</span>
                </div>
                %LINKEND%
            </div>
        ';

        if ($res) {
            $claimedArray = advent::claimedAll();

            foreach ($res as $row) {
                $notyet = 'notyet';
                $claimed = '';

                $image = $row['img'];
                $day = lang('day') . ' ' . $row['day'];
                $plainday = $row['day'];

                $linkstart = '';
                $linkend = '';

                if (advent::canClaim($plainday)) {
                    $notyet = '';
                    $linkstart = '<a href="store.php?page=advent&claim='.$plainday.'">';
                    $linkend = '</a>';
                }

                if (in_array($plainday, $claimedArray)) {
                    $claimed = 'claimed';
                    $linkstart = '';
                    $linkend = '';
                }

                $temp = $template;

                $temp = str_replace([
                    '%DAY%',
                    '%IMAGE%',
                    '%PLAINDAY%',
                    '%CLAIMED%',
                    '%NOTYET%',
                    '%LINKSTART%',
                    '%LINKEND%'
                ], [
                    $day,
                    $image,
                    $plainday,
                    $claimed,
                    $notyet,
                    $linkstart,
                    $linkend
                ], $temp);

                $ret .= $temp;
            }
        }

        return $ret;
    }

    public static function canClaim($day): bool
    {
        $year = date('Y');

        if (new DateTime() >= new DateTime("$year-12-$day 00:00:00")) {
            if (getSetting('advent_claim_current_day_only', 'value2')) {
                $currentDay = (int)date('d');

                if ($day < $currentDay) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    public static function claimedAll(): array
    {
        global $db, $UID;

        $ids = [];
        $year = date('Y');

        $res = $db->getAll("SELECT adv_id FROM advent_claims WHERE uid = ? AND YEAR(timestamp) = ?", [$UID, $year]);

        if ($res) {
            foreach ($res as $row) {
                $ids[] = $row['adv_id'];
            }
        }

        if (getSetting('advent_claim_current_day_only', 'value2')) {
            $currentDay = date('d');

            for ($i = 1; $i < $currentDay; $i++) {
                if (!in_array($i, $ids)) {
                    $ids[] = $i;
                }
            }
        }

        return $ids;
    }

    public static function claimed($day): bool
    {
        global $db, $UID;

        $year = date('Y');
        $res = $db->getAll("SELECT * FROM advent_claims WHERE adv_id = ? AND uid = ? AND YEAR(timestamp) = ?", [
            $day, $UID, $year
        ]);

        if ($res) {
            return true;
        }

        if (getSetting('advent_claim_current_day_only', 'value2')) {
            $currentDay = (int)date('d');

            if ($day < $currentDay) {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws Exception
     */
    public static function claim($day)
    {
        global $db, $UID;

        if (isset($_SESSION['lastPurchase'])) {
            $lastPurchase = $_SESSION['lastPurchase'];

            if (time() <= $lastPurchase + 10) {
                die('You are a bit too quick there! Slow down!');
            }
        }

        $_SESSION['lastPurchase'] = time();

        // Give the user the pkg
        $info = $db->getOne("SELECT * FROM advent_calendar WHERE day = ?", $day);

        $pkg = $info['package'];
        $credits = $info['credits'];
        $random = $info['random'];

        $give = true;
        if (!empty($random)) {
            $probability = random_int(1, 100);
            if ($probability > (int)$random) {
                $give = false;
            }
        }

        $package_id = null;
        if (!empty($pkg) && $pkg !== 'null' && $give) {
            $packages = json_decode($pkg, true);

            if (count($packages) !== 0) {
                $package_id = $packages[random_int(0, count($packages) - 1)];

                $p_array = array(
                    "id" => $package_id,
                    "trans_id" => 0,
                    "uid" => $UID,
                    "type" => 1
                );
                addAction($p_array);
            }
        }

        // give credits
        $credits_amount = null;
        if (!empty($credits) && $give) {
            $credits_amount = $credits;

            $credits_has = $db->getOne("SELECT credits FROM players WHERE uid = ?", $UID);

            $amt = $credits_has + $credits_amount;

            credits::set($UID, $amt);
        }

        if ($give) {
            $name = 'Assigned from advent calendar';
            $email = 'Assigned from advent calendar';
            $txn_id = 'Assigned from advent calendar';
            $price = 0;

            $db->execute("INSERT INTO transactions SET name = ?, email = ?, uid = ?, package = ?, credits = ?, price = ?, txn_id = ?", [
                $name, $email, $UID, $package_id, $credits_amount, $price, $txn_id,
            ]);
        }

        $db->execute("INSERT INTO advent_claims SET adv_id = ?, uid = ?", [$day, $UID]);

        return compact('credits_amount', 'package_id');
    }
}
