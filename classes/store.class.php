<?php

class store
{
    private $type;

    /**
     * @var int
     */
    private $server;

    /**
     * @var array
     */
    private $sortOptions;

    public function __construct($type)
    {
        $this->type = $type;
    }

    /**
     * setServer
     * @param int $sid serverid - Has to be defined before any package options can be called
     */
    public function setServer($sid): void
    {
        $this->server = $sid;
    }

    /**
     * setSortOptions
     * @param array $array Array of sort options
     *
     * Possible values:
     * - sortby
     * - category
     *
     */
    public function setSortOptions($array): void
    {
        $this->sortOptions = $array;
    }

    /**
     * getSidebar
     * @return string Sidebar
     */
    public function getSidebar(): string
    {
        global $db;

        $ret = '
        <form method="POST" id="storeSidebar">
            <div class="row mb-2">
                <div class="col-md-6 col-12">
                    <h6>' . lang("search_by", "Search") . '</h6>
                    <input type="text" name="search" class="form-control" placeholder="' . lang("search_one", "Search") . '"> 
                </div>
                
        ';

        $credits_enabled = '';
        $specific = '';

        /**
         * Only for package
         */
        if ($this->type === 'package') {
            $specific = '
                <option value="permanent">Permanent</option>
                <option value="non_permanent">Non permanent</option>
            ';
        }

        if (gateways::enabled('credits')) {
            $credits_enabled = '
                <option value="credits_low">' . lang("lowtohigh", "Price(Low to High)") . ')</option>
                <option value="credits_high">' . lang("hightolow", "Price(High to Low)") . '</option>
            ';
        }

        /**
         * Nain content
         */
        $ret .= '
				<div class="col-md-6 col-12">
					<h6>' . lang("sort_by", "Sort By") . '</h6>
					<select class="form-control" id="sortby" onchange="$(\'#storeSidebar\').submit()">
						<option value="id">' . lang("id_search", "ID(Default)") . '</option>
						<option value="price_low">' . lang("lowtohigh", "Price(Low to High)") . '</option>
						<option value="price_high">' . lang("hightolow", "Price(High to Low)") . '</option>
						' . $credits_enabled . '
						' . $specific . '
					</select>
				</div>
			</div>
		';

        /**
         * Only for package
         */
        if ($this->type === 'package') {
            if ($this->server == '*') {
                $servers = $db->getAll("SELECT * FROM servers");
                $s = '[';
                foreach ($servers as $server) {
                    $id = $server['id'];
                    $s .= '"' . $id . '",';
                }
                $s = rtrim($s, ',');
                $s .= ']';
            } else {
                $s = '%"' . $this->server . '"%';
            }

            $categories = $db->getAll("SELECT DISTINCT(p.category), c.name, c.order_id FROM packages p JOIN categories c ON p.category = c.id WHERE p.servers LIKE ? AND p.enabled = 1 ORDER BY c.order_id ASC", $s);
            $cat_list = '<button type="submit" class="categoryLink" value="none">' . lang("category_all", "All") . '</button>';

            foreach ($categories as $cat) {
                $count = $db->getOne("SELECT count(*) AS value FROM packages WHERE enabled = 1 AND category = ? AND servers LIKE ?", [
                    $cat['category'], $s
                ])['value'];

                $cat_list .= '
                    <button type="submit" class="categoryLink" value="' . $cat['category'] . '">' . $cat['name'] . ' <span>[' . $count . ']</span></button>
                ';
            }


            $ret .= '
                <div class="row">
                    <div class="col-12">
                        <h6>' . lang("categories_store", "Categories") . '</h6>
                        ' . $cat_list . '
                    </div>
                </div>
            ';
        }


        $ret .= '
                </div>
            </div>
        </form>
        ';

        return $ret;
    }

    public function display()
    {
        global $db;

        if ($this->type === 'package') {
            $server = $this->server;
            $cat = $this->sortOptions['cat'];
        }

        $sortby = $this->sortOptions['sortby'];
        $search = $this->sortOptions['search'];

        switch ($sortby) {
            case 'id':
                if ($this->type === 'package') {
                    $orderby = 'order_id';
                } else {
                    $orderby = 'id';
                }

                $descAsc = 'ASC';
                break;

            case 'price_low':
                $orderby = 'price';
                $descAsc = 'ASC';
                break;

            case 'price_high':
                $orderby = 'price';
                $descAsc = 'DESC';
                break;

            case 'credits_low':
                if ($this->type !== 'credits') {
                    $orderby = 'credits';
                } else {
                    $orderby = 'amount';
                }

                $descAsc = 'ASC';
                break;

            case 'credits_high':
                if ($this->type !== 'credits') {
                    $orderby = 'credits';
                } else {
                    $orderby = 'amount';
                }

                $descAsc = 'DESC';
                break;

            case 'permanent':
                $orderby = 'permanent';
                $descAsc = 'DESC';
                break;

            case 'non_permanent':
                $orderby = 'permanent';
                $descAsc = 'ASC';
                break;

            default:
                $orderby = 'id';
                $descAsc = 'ASC';
                break;
        }

        if ($search === '' || $search === '%') {
            $search = '%';
        } else {
            $search = '%' . $search . '%';
        }

        /**
         * COMMON CODE
         */
        if (prometheus::loggedIn()) {
            $b_uid = $_SESSION['uid'];
        } else {
            $b_uid = 'none';
        }

        $sale_ar = getSetting('sale_packages', 'value');
        $sale_ar = json_decode($sale_ar, true);
        $perc = getSetting('sale_percentage', 'value2');

        $curID = getSetting('dashboard_main_cc', 'value2');
        $cc = $db->getOne("SELECT cc FROM currencies WHERE id = ?", $curID);

        /**
         * Packages
         * @var string
         */
        if ($this->type === 'package') {
            if ($server !== '*') {
                $s = $server;

                $featured_package = $db->getOne("SELECT featured_package FROM servers WHERE id = ?", $s);

                $s = '%"' . $s . '"%';
            } else {
                $featured_package = 0;

                $servers = $db->getAll("SELECT * FROM servers");
                $s = '[';
                foreach ($servers as $server) {
                    $id = $server['id'];
                    $s .= '"' . $id . '",';
                }
                $s = rtrim($s, ',');
                $s .= ']';
            }

            if ($cat === 'none') {
                $res = $db->getAll("SELECT * FROM packages WHERE servers LIKE ? AND (title LIKE ? OR lower_text LIKE ? OR labels LIKE ?) AND enabled = 1 ORDER BY CASE WHEN id = ? THEN 1 ELSE 2 END, $orderby $descAsc", [
                    $s, $search, $search, $search, $featured_package
                ]);
            } else {
                $res = $db->getAll("SELECT * FROM packages WHERE servers LIKE ? AND category = ? AND (title LIKE ? OR lower_text LIKE ? OR labels LIKE ?) AND enabled = 1 ORDER BY CASE WHEN id = ? THEN 1 ELSE 2 END, $orderby $descAsc", [
                    $s, $cat, $search, $search, $search, $featured_package
                ]);
            }

            $ret = '';

            $i = 1;

            $display = getSetting('store_packageDisplay', 'value2');
            if ($display === 0) {
                $num = 12;
            } elseif ($display === 1) {
                $num = 6;
            } elseif ($display === 2) {
                $num = 4;
            } elseif ($display === 3) {
                $num = 3;
            }

            foreach ($res as $row) {
                $id = $row['id'];
                $title = htmlspecialchars($row['title']);
                $lower_text = $row['lower_text'];
                $price = (float) $row['price'];
                $credits = (float) $row['credits'];
                $img = $row['img'];
                $labels = $row['labels'];
                $permanent = (int) $row['permanent'];
                $enabled = (int) $row['enabled'];
                $custom_price = (float) $row['custom_price'];
                $custom_price_min = (float) $row['custom_price_min'];
                $no_owned = (int) $row['no_owned'];

                $customjob = (bool) actions::get($id, 'customjob', '');

                if (isset($_SESSION['uid']) && $no_owned === 1) {
                    $no_res = $db->getAll("SELECT * FROM actions WHERE uid = ? AND active = 1", $_SESSION['uid']);

                    if ($no_res) {
                        $no_owned = false;
                    } else {
                        $no_owned = true;
                    }
                } else {
                    $no_owned = false;
                }

                if ($img === '' || $img === null) {
                    $img = '';
                    $img_style = 'border-bottom: 0px;';
                } else {
                    $img = '<img src="' . $img . '" width="100%" height="240px" />';
                    $img_style = '';
                }

                $label = '';

                if ($labels !== '[]' && $labels !== null) {
                    $labels = json_decode($labels, true);

                    if (count($labels) !== 0) {
                        foreach ($labels as $value) {
                            $label .= '
                                <li>' . $value . '</li>
                            ';
                        }
                    }
                }

                $featured = '';
                if ($featured_package !== 0 && $featured_package === $id) {
                    $featured = '
                        <div class="featured-header" style="margin-top: 15px;"">
                            ' . lang('featured_pkg') . '
                        </div>
                    ';
                }

                if ($permanent === 0) {
                    $perm = lang('non_permanent');
                } else {
                    $perm = lang('permanent');
                }

                // CHECK IF PKG IS FREE OR NOT
                if (gateways::enabled('credits') === false) {
                    if ($price == 0 && !$custom_price) {
                        $free = true;
                        $purchase = '<i class="fas fa-money-bill fa-fw"></i> ' . lang('purchase_free', 'Get this package for free!');
                    } else {
                        $free = false;
                        $purchase = '<i class="fas fa-money-bill fa-fw"></i> ' . lang('purchase', 'Purchase');
                    }
                } elseif ($credits == 0 && !$custom_price) {
                    $free = true;
                    $purchase = '<i class="fas fa-money-bill fa-fw"></i> ' . lang('purchase_free', 'Get this package for free!');
                } else {
                    $free = false;
                    $purchase = '<i class="fas fa-money-bill fa-fw"></i> ' . lang('purchase', 'Purchase');
                }

                $upgrade_block = '';
                $upgrade = packages::upgradeable($id);
                if ($upgrade) {
                    $pkg = packages::upgradeable($id, 'list');
                    $credits = packages::upgradeInfo($id, $pkg, 'credits', $credits);
                    $price = (float) packages::upgradeInfo($id, $pkg, 'price', $price);
                    $pkg_name = htmlentities(packages::upgradeInfo($id, $pkg, 'name'));

                    $upgrade_block = '
                        <span style="font-family: \'Open Sans\',serif; font-weight: normal; font-size: 15px; padding-bottom: 15px; padding-top: 15px; background: #1d1d1d;">
                        ' . lang("pkg_discounted", "This package is discounted because you own") . ' ' . $pkg_name . '
                        </span>
                    ';
                }

                $oldprice = '';
                $credits_old = '';

                if (!$custom_price) { #&& !$upgrade
                    if (store::sale($id, $sale_ar, $perc)) {
                        $oldprice = '<s style="font-size: 30px; color: #c10000;">' . $price . ' ' . $cc . '</s>';
                        $orgprice = $price;
                        $price = $perc / 100 * $orgprice;
                        $price = $orgprice - $price;
                        $price = number_format($price, 2, '.', '');

                        $credits_old = '<s style="font-size: 30px; color: #c10000;">' . $credits . '</s>';
                        $credits = $credits * (100 - $perc) / 100;
                    }
                }

                if (prometheus::loggedIn()) {
                    $class = 'buy-btn';
                }

                if ($free) {
                    $class = 'buy-btn buy-btn-free';
                }

                $style = '';

                /*
                if(packages::alreadyOwn($id) && $rebuyable != 1){
                    $class = 'buy-btn disabled';
                    $style = 'background-color: green;';
                    $purchase = lang('buy_already_own');
                }

                if (packages::notCompatible($id)) {
                    $class = 'buy-btn disabled';
                    $purchase = lang('buy_not_compatible');
                }
                */

                if (packages::ownsFree($id) && !$custom_price) {
                    $class = 'buy-btn disabled';
                    $purchase = lang('buy_own_free', 'You already own this free package. You can\'t claim it twice!');
                }

                if (tos::getLast() < getSetting('tos_lastedited', 'value3')) {
                    $class = 'buy-btn disabled';
                    $purchase = lang('tos_must_accept');
                }

                if (!prometheus::loggedIn()) {
                    $class = 'buy-btn disabled';
                    $purchase = lang('buy_sign_in');
                }

                $price_block = '';
                if (!getSetting('credits_only', 'value2') && !gateways::enabled('credits')) {
                    $price_block = '<span>' . $price . ' ' . $cc . ' ' . $oldprice . '</span>';
                } elseif (!getSetting('credits_only', 'value2') && gateways::enabled('credits')) {
                    $price_block = '
                        <span>
                            ' . $price . ' ' . $cc . ' ' . $oldprice . '<br>
                            ' . $credits . ' ' . lang("credits") . ' ' . $credits_old . '
                        </span>
                    ';
                } elseif (getSetting('credits_only', 'value2')) {
                    $price_block = '
                        <span>
                            ' . $credits . ' ' . lang("credits") . ' ' . $credits_old . '
                        </span>
                    ';
                }

                if ($enabled && !$no_owned) {
                    if ($num === 3 && $i === 1) {
                        $ret .= '<div class="row">';
                    }

                    if ($num === 4 && $i === 1) {
                        $ret .= '<div class="row">';
                    }

                    if ($num === 6 && $i === 1) {
                        $ret .= '<div class="row">';
                    }

                    if ($num === 12) {
                        $ret .= '<div class="row">';
                    }

                    $lower_text_all = '';
//                    if(getSetting('store_disable_read_more', 'value2') == 0) {
//                        $lower_text_all .= '<div class="store-box-lower store-box-lower-all" style="display: none;">';
//                        $lines = preg_split('/<br[^>]*>/i', $lower_text);
//
//                        $lineCount = count($lines);
//                        $maxLines = 10;
//
//                        $lower_text_all .= $lower_text;
//                        if ($lineCount > $maxLines) {
//                            $lower_text = '';
//                            $i = 1;
//
//                            foreach ($lines as $line) {
//                                if ($i > $maxLines) continue;
//
//                                $lower_text .= $line . '<br>';
//
//                                $i++;
//                            }
//
//                            $lower_text .= '<a href="#" class="read-more" data-package-id="' . $id . '">Read more</a>';
//                        }
//
//                        $lower_text_all .= '</div>';
//                    }

                    if (!$custom_price && !$free && !$customjob) {
                        $ret .= '
                            <div class="col-12 col-md-' . $num . '">
                                ' . $featured . '
                                <div class="store-box-header">' . $title . '</div>
                                
                                <div class="store-box" data-package-id="' . $id . '">
                                    <div class="store-box-upper">
                                        ' . $upgrade_block . '
                                        ' . $price_block . '
                                        <ul style="margin-bottom: 0;">
                                            <li style="border-top: 0px;' . $img_style . '"><b>' . $perm . '</b></li>
                                        </ul>
                                        ' . $img . '
                                        <ul>
                                            ' . $label . '
                                        </ul>
                                    </div>
                                    
                                    <div class="store-box-lower">
                                        ' . $lower_text . '
                                    </div>
                                    
                                    ' . $lower_text_all . '
                                </div>
                                
                                <a href="store.php?page=purchase&type=pkg&pid=' . $id . '" class="' . $class . '" style="' . $style . '">' . $purchase . '</a>
                            </div>
                        ';
                    }

                    $coupon = $_GET['coupon'] ?? '';

                    if (!$custom_price && $free && !$customjob) {
                        $ret .= '
                            <div class="col-12 col-md-' . $num . '">
                                ' . $featured . '
                                <div class="store-box-header">' . $title . '</div>
                                <div class="store-box">
                                    <div class="store-box-upper">
                                        <span>' . lang('free', 'Free') . '</span>
                                        <ul style="margin-bottom: 0;">
                                            <li style="border-top: 0px;' . $img_style . '"><b>' . $perm . '</b></li>
                                        </ul>
                                        ' . $img . '
                                        <ul>
                                            ' . $label . '
                                        </ul>
                                    </div>
                                    
                                    <div class="store-box-lower">
                                        ' . $lower_text . '
                                    </div>
                                    
                                    ' . $lower_text_all . '
                                </div>
                                
                                <form method="POST" action="inc/credits.php">
                                    <input type="hidden" name="csrf_token" value="' . csrf_token() . '">
                                    <input type="hidden" name="type" value="package">
                                    <input type="hidden" name="itemID" value="' . $id . '">
                                    <input type="hidden" name="uid" value="' . $b_uid . '">
                                    <input type="hidden" name="coupon" value="' . $coupon . '">
                                    <button name="submit" class="' . $class . '" style="' . $style . '" id="free_btn">' . $purchase . '</button>
                                </form>
                            </div>
                        ';
                    }

                    if ($custom_price && !$customjob) {
                        $ret .= '
                            <div class="col-12 col-md-' . $num . '">
                                ' . $featured . '
                                <div class="store-box-header">' . $title . '</div>
                                <div class="store-box">
                                    <div class="store-box-upper">
                                        <span>' . lang('packages_custom_amount') . '</span>
                                        <ul style="margin-bottom: 0;">
                                            <li style="border-top: 0; ' . $img_style . '"><b>' . $perm . '</b></li>
                                        </ul>
                                        ' . $img . '
                                        <ul>
                                            ' . $label . '
                                        </ul>
                                    </div>
                                    
                                    <div class="store-box-lower">
                                        ' . $lower_text . '
                                    </div>
                                    
                                    ' . $lower_text_all . '
                                </div>
                                <form method="post">
                                    <input type="hidden" name="csrf_token" value="' . csrf_token() . '">
                                    <input type="hidden" name="pid" value="' . $id . '">
                                    <input type="number" step="any" min="' . $custom_price_min . '" name="amount" placeholder="Price (Minimum ' . $custom_price_min . ') Press enter if button grays out" class="form-control">
                                    <button type="submit" name="customprice_submit" class="' . $class . '" style="' . $style . '">' . $purchase . '</button>
                                </form>
                            </div>
                        ';
                    }

                    if (!$custom_price && $customjob && !$free) {
                        $ret .= '
                            <div class="col-12 col-md-' . $num . '">
                                ' . $featured . '
                                <div class="store-box-header">' . $title . '</div>
                                <div class="store-box">
                                    <div class="store-box-upper">
                                        ' . $upgrade_block . '
                                        ' . $price_block . '
                                        <ul style="margin-bottom: 0;">
                                            <li style="border-top: 0;' . $img_style . '"><b>' . $perm . '</b></li>
                                        </ul>
                                        ' . $img . '
                                        <ul>
                                            ' . $label . '
                                        </ul>
                                    </div>
                                    
                                    <div class="store-box-lower">
                                        ' . $lower_text . '
                                    </div>
                                    
                                    ' . $lower_text_all . '
                                </div>
                                <a href="store.php?page=customjob&pid=' . $id . '" class="' . $class . '" style="' . $style . '">' . $purchase . '</a>
                            </div>
                        ';
                    }

                    if (!$custom_price && $customjob && $free) {
                        $ret .= '
                            <div class="col-12 col-md-' . $num . '">
                                ' . $featured . '
                                <div class="store-box-header">' . $title . '</div>
                                
                                <div class="store-box">
                                    <div class="store-box-upper">
                                        <span>' . lang('free', 'Free') . '</span>
                                        <ul style="margin-bottom: 0;">
                                            <li style="border-top: 0;' . $img_style . '"><b>' . $perm . '</b></li>
                                        </ul>
                                        ' . $img . '
                                        <ul>
                                            ' . $label . '
                                        </ul>
                                    </div>
                                    
                                    <div class="store-box-lower">
                                        ' . $lower_text . '
                                    </div>
                                    
                                    ' . $lower_text_all . '
                                </div>
                                
                                <a href="store.php?page=customjob&pid=' . $id . '" class="' . $class . '" style="' . $style . '">' . $purchase . '</a>
                            </div>
                        ';
                    }

                    if ($num === 3) {
                        if ($i === 4) {
                            $ret .= '</div>';

                            $i = 1;
                        } else {
                            $i++;
                        }
                    }

                    if ($num === 4) {
                        if ($i === 3) {
                            $ret .= '</div>';

                            $i = 1;
                        } else {
                            $i++;
                        }
                    }

                    if ($num === 6) {
                        if ($i === 2) {
                            $ret .= '</div>';

                            $i = 1;
                        } else {
                            $i++;
                        }
                    }

                    if ($num === 12) {
                        $ret .= '</div>';
                    }
                }
            }

            if (!$res) {
                $ret = '<br>' . lang('packages_not_available');
            }
        }

        /**
         * Credits
         * @var string
         */
        if ($this->type === 'credits') {
            $ret = '';
            $res = $db->getAll("SELECT * FROM credit_packages WHERE title LIKE ? OR descr LIKE ? ORDER BY $orderby $descAsc", [
                $search, $search
            ]);

            $i = 1;
            foreach ($res as $row) {
                $id = $row['id'];
                $title = $row['title'];
                $price = $row['price'];
                $descr = $row['descr'];
                $amt = $row['amount'];

                $purchase = '<i class="fas fa-money-bill fa-fw"></i> ' . lang('purchase');
                $style = '';

                $class = 'buy-btn';
                if (tos::getLast() < getSetting('tos_lastedited', 'value3')) {
                    $class = 'buy-btn disabled';
                    $purchase = lang('tos_must_accept');
                }

                if (!prometheus::loggedIn()) {
                    $class = 'buy-btn disabled';
                    $purchase = lang('buy_sign_in');
                }

                if (!isset($_SESSION['uid'])) {
                    $_SESSION['uid'] = '';
                }

                if ($i === 1) {
                    $ret .= '<div class="row">';
                }

                $ret .= '
                    <div class="col-12 col-md-4">
                        <div class="credit-box">
                            <div class="stat-box-header">
                                ' . $title . '
                            </div>
                            <div class="credit-content">
                                <span>' . $price . ' ' . $cc . '</span>
                                <span>' . $amt . ' Credits</span>
                                <span>' . $descr . '</span>
                            </div>
                            <a href="store.php?page=purchase&type=credits&pid=' . $id . '" class="' . $class . '" style="' . $style . '">' . $purchase . '</a>
                        </div>
                    </div>
                ';

                if ($i === 3) {
                    $ret .= '</div>';
                }

                if ($i === 3) {
                    $i = 1;
                } else {
                    $i++;
                }
            }

            if ($i <= 3) {
                $ret .= '</div>';
            }

            if (!$res) {
                $ret = lang('credit_not_available');
            }
        }

        /**
         * Raffles
         * @var string
         */
        if ($this->type === 'raffle') {
            $ret = '';
            $res = $db->getAll("SELECT * FROM raffles WHERE enabled = 1 AND (title LIKE ? OR descr LIKE ?) ORDER BY $orderby $descAsc", [
                $search, $search
            ]);

            $i = 1;

            foreach ($res as $row) {
                $id = (int) $row['id'];
                $package = $row['package'];
                $title = $row['title'];
                $price = (float) $row['price'];
                $credits = (float) $row['credits'];
                $descr = $row['descr'];
                $max_per_person = $row['max_per_person'];
                $end_amount = $row['end_amount'];
                $ended = $row['ended'];
                $winner = $row['winner'];
                $img = $row['img'];

                $img_style = '';
                if (!empty($img)) {
                    $img = '<img src="' . $img . '" width="100%" height="240px" />';
                } else {
                    $img_style = 'border-bottom: 0;';
                }

                $winner_name = htmlentities($db->getOne("SELECT name FROM players WHERE uid = ?", $winner));
                $times = $max_per_person - $db->getOne("SELECT count(*) AS value FROM raffle_tickets WHERE uid = ? AND raffle_id = ?", array($_SESSION['uid'], $id))['value'];
                $pkg_name = htmlentities($db->getOne("SELECT title FROM packages WHERE id = ?", $package));
                $owned_amt = (int) $db->getOne("SELECT count(*) AS value FROM raffle_tickets WHERE uid = ? AND raffle_id = ?", array($_SESSION['uid'], $id))['value'];
                $rebuyable = (int) $db->getOne("SELECT rebuyable FROM packages WHERE id = ?", $package);
                $amt = (int) $db->getOne("SELECT count(*) AS value FROM raffle_tickets WHERE raffle_id = ?", [$id])['value'];

                $purchase = '<i class="fas fa-ticket-alt fa-fw"></i> ' . lang('raffle_ticket');

                $style = '';
                $class = 'buy-btn';
                $attributes = '';

                if (getSetting('credits_only', 'value2') === 0 && gateways::enabled('credits')) {
                    $price_span = '<span style="' . $img_style . '">' . $price . ' ' . $cc . '<br>' . $credits . ' ' . lang("credits", "Credits") . '</span>';
                } elseif (!gateways::enabled('credits')) {
                    $price_span = '<span style="' . $img_style . '">' . $price . ' ' . $cc . '</span>';
                } elseif (getSetting('credits_only', 'value2')) {
                    $price_span = '<span style="' . $img_style . '">' . $credits . ' ' . lang("credits", "Credits") . '</span>';
                }

                if ((Gateways::enabled('credits') && !getSetting('credits_only',
                            'value2') && ($price == 0 || $credits == 0))
                    || (Gateways::enabled('credits') && getSetting('credits_only', 'value2') && $credits == 0)
                    || (!Gateways::enabled('credits') && $price == 0)) {
                    $free = true;
                    $price_span = '<span style="' . $img_style . '">' . lang("free", "Free") . '</span>';
                    $purchase = lang('raffle_free', 'Enter for free');
                } else {
                    $free = false;
                }

                if ($owned_amt === $max_per_person) {
                    $class = 'buy-btn disabled';
                    $attributes .= ' disabled';
                    $style = 'background-color: green;';
                    $purchase = lang('raffle_reached_max');
                }

                if (packages::alreadyOwn($package) && $rebuyable !== 1) {
                    $class = 'buy-btn disabled';
                    $style = 'background-color: green;';
                    $purchase = lang('raffle_already_own');
                }
                if (packages::notCompatible($package)) {
                    $class = 'buy-btn disabled';
                    $purchase = lang('raffle_not_compatible');
                }

                if (tos::getLast() < getSetting('tos_lastedited', 'value3')) {
                    $class = 'buy-btn disabled';
                    $purchase = lang('tos_must_accept');
                }

                if (!prometheus::loggedIn()) {
                    $class = 'buy-btn disabled';
                    $purchase = lang('buy_sign_in');
                }

                if ($ended == 1) {
                    $class = 'buy-btn disabled';
                    $style = 'background-color: green;';
                    $purchase = lang('raffle_ended') . $winner_name;
                }

                if (!isset($_SESSION['uid'])) {
                    $_SESSION['uid'] = '';
                }

                $uid = $_SESSION['uid'];

                if ($i === 1) {
                    $ret .= '<div class="row">';
                }

                if (!$free) {
                    $ret .= '
                        <div class="col-6">
                            <div class="store-box-header">' . $title . '</div>
                            <div class="store-box">
                                <div class="store-box-upper">
                                    ' . $price_span . '
                                    ' . $img . '
                                    <ul>
                                        <li>' . $pkg_name . '</li>
                                        <li>' . $amt . ' / ' . $end_amount . ' ' . lang('entries', 'entries') . '</li>
                                        <li>' . lang('you_can_enter', 'You can enter') . ' ' . $times . ' ' . lang('times_more', 'time(s) more') . '</li>
                                    </ul>
                                    <div class="store-box-lower">
                                        ' . $descr . '
                                    </div>
                                </div>
                            </div>
                            <a href="store.php?page=purchase&type=raffle&pid=' . $id . '" class="' . $class . '" style="' . $style . '">' . $purchase . '</a>
                        </div>
                    ';
                } else {
                    $ret .= '
                        <div class="col-6">
                            <div class="store-box-header">' . $title . '</div>
                            <div class="store-box">
                                <div class="store-box-upper">
                                    ' . $price_span . '
                                    ' . $img . '
                                    <ul>
                                        <li>' . $pkg_name . '</li>
                                        <li>' . $amt . ' / ' . $end_amount . ' ' . lang('entries', 'entries') . '</li>
                                        <li>' . lang('you_can_enter', 'You can enter') . ' ' . $times . ' ' . lang('times_more', 'time(s) more') . '</li>
                                    </ul>
                                    <div class="store-box-lower">
                                        ' . $descr . '
                                    </div>
                                </div>
                            </div>
                            <form method="POST" action="inc/credits.php">
                                <input type="hidden" name="csrf_token" value="' . csrf_token() . '">
                                <input type="hidden" name="type" value="raffle">
                                <input type="hidden" name="itemID" value="' . $id . '">
                                <input type="hidden" name="uid" value="' . $uid . '">
                                <button name="submit" class="' . $class . '" style="' . $style . '" id="free_btn"'.$attributes.'>' . $purchase . '</button>
                            </form>
                        </div>
                    ';
                }

                if ($i === 2) {
                    $ret .= '</div>';
                }

                if ($i === 2) {
                    $i = 1;
                } else {
                    $i++;
                }
            }

            if (!$res) {
                $ret = '<div class="col-12">' . lang('raffle_not_available') . '</div>';
            }
        }

        return $ret;
    }

    public static function packageExists($id)
    {
        global $db;

        $res = $db->getAll("SELECT * FROM packages WHERE id = ?", $id);
        if ($res) {
            return true;
        } else {
            return false;
        }
    }

    public static function getOnlyServerID()
    {
        global $db;

        return $db->getOne("SELECT id FROM servers");
    }

    public static function getServers()
    {
        global $db;

        $ret = cache::get("servers");

        if ($ret == null) {
            $ret = '';
            $res = $db->getAll("SELECT * FROM servers ORDER BY order_id ASC");
            foreach ($res as $row) {
                $id = $row['id'];
                $name = htmlspecialchars($row['name']);
                $image = $row['image_link'];

                if ($image != null) {
                    $style = 'background: url(' . $image . '); background-size: auto; background-repeat: no-repeat; background-position: center;';
                    $icon = '<div style="padding-top: 30px; margin-top: 30px;"></div>';
                } else {
                    $icon = '<i class="fas fa-server fa-4x fa-fw"></i>';
                    $style = '';
                }

                $ret .= '
						<div class="col-12 col-md-4">
							<a href="store.php?page=packages&id=' . $id . '">
								<div class="srv-box" style="' . $style . '">
									' . $icon . '
									<div class="srv-label">' . $name . '</div>
								</div>
							</a>
						</div>
					';
            }

            if (!$res) {
                $ret = '<div class="col-md-4">' . lang('no_servers', 'No servers available at the moment.') . '</div>';
            }

            cache::set("servers", $ret, 600 * 6 * 24);
        }

        return $ret;
    }

    public static function countGlobals()
    {
        global $db;

        $servers = $db->getAll("SELECT * FROM servers");
        $all_servers = '[';

        if ($servers) {
            foreach ($servers as $server) {
                $id = $server['id'];
                $all_servers .= '"' . $id . '",';
            }
        }

        $all_servers = rtrim($all_servers, ',');
        $all_servers .= ']';

        $res = $db->getOne("SELECT count(*) AS value FROM packages WHERE servers = ? AND enabled = 1", [$all_servers])['value'];

        return $res;
    }

    public static function countServers()
    {
        global $db;

        $res = $db->getOne("SELECT count(*) AS value FROM servers")['value'];

        return $res;
    }

    public static function countPackages()
    {
        global $db;

        $res = $db->getOne("SELECT count(*) AS value FROM packages WHERE enabled = 1")['value'];

        return $res;
    }

    public static function sale($id, $sale_ar = null, $perc = null)
    {
        $sale_ar = getSetting('sale_packages', 'value');
        $sale_ar = json_decode($sale_ar, true);

        try {
            $sale_end = new datetime(getSetting('sale_enddate', 'value'));
            $valid_date = true;
        } catch (Exception $e) {
            $sale_end = null;
            $valid_date = false;
        }

        if ($sale_ar != null && in_array($id, $sale_ar) && $valid_date && $sale_end > new datetime()) {
            return true;
        } else {
            return false;
        }
    }
}
