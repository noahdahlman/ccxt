<?php

namespace ccxt;

// PLEASE DO NOT EDIT THIS FILE, IT IS GENERATED AND WILL BE OVERWRITTEN:
// https://github.com/ccxt/ccxt/blob/master/CONTRIBUTING.md#how-to-contribute-code

use Exception as Exception; // a common import

class bitmex extends Exchange {

    public function describe () {
        return array_replace_recursive (parent::describe (), array (
            'id' => 'bitmex',
            'name' => 'BitMEX',
            'countries' => array ( 'SC' ), // Seychelles
            'version' => 'v1',
            'userAgent' => null,
            'rateLimit' => 2000,
            'has' => array (
                'CORS' => false,
                'fetchOHLCV' => true,
                'withdraw' => true,
                'editOrder' => true,
                'fetchOrder' => true,
                'fetchOrders' => true,
                'fetchOpenOrders' => true,
                'fetchClosedOrders' => true,
            ),
            'timeframes' => array (
                '1m' => '1m',
                '5m' => '5m',
                '1h' => '1h',
                '1d' => '1d',
            ),
            'urls' => array (
                'test' => 'https://testnet.bitmex.com',
                'logo' => 'https://user-images.githubusercontent.com/1294454/27766319-f653c6e6-5ed4-11e7-933d-f0bc3699ae8f.jpg',
                'api' => 'https://www.bitmex.com',
                'www' => 'https://www.bitmex.com',
                'doc' => array (
                    'https://www.bitmex.com/app/apiOverview',
                    'https://github.com/BitMEX/api-connectors/tree/master/official-http',
                ),
                'fees' => 'https://www.bitmex.com/app/fees',
                'referral' => 'https://www.bitmex.com/register/rm3C16',
            ),
            'api' => array (
                'public' => array (
                    'get' => array (
                        'announcement',
                        'announcement/urgent',
                        'funding',
                        'instrument',
                        'instrument/active',
                        'instrument/activeAndIndices',
                        'instrument/activeIntervals',
                        'instrument/compositeIndex',
                        'instrument/indices',
                        'insurance',
                        'leaderboard',
                        'liquidation',
                        'orderBook',
                        'orderBook/L2',
                        'quote',
                        'quote/bucketed',
                        'schema',
                        'schema/websocketHelp',
                        'settlement',
                        'stats',
                        'stats/history',
                        'trade',
                        'trade/bucketed',
                    ),
                ),
                'private' => array (
                    'get' => array (
                        'apiKey',
                        'chat',
                        'chat/channels',
                        'chat/connected',
                        'execution',
                        'execution/tradeHistory',
                        'notification',
                        'order',
                        'position',
                        'user',
                        'user/affiliateStatus',
                        'user/checkReferralCode',
                        'user/commission',
                        'user/depositAddress',
                        'user/margin',
                        'user/minWithdrawalFee',
                        'user/wallet',
                        'user/walletHistory',
                        'user/walletSummary',
                    ),
                    'post' => array (
                        'apiKey',
                        'apiKey/disable',
                        'apiKey/enable',
                        'chat',
                        'order',
                        'order/bulk',
                        'order/cancelAllAfter',
                        'order/closePosition',
                        'position/isolate',
                        'position/leverage',
                        'position/riskLimit',
                        'position/transferMargin',
                        'user/cancelWithdrawal',
                        'user/confirmEmail',
                        'user/confirmEnableTFA',
                        'user/confirmWithdrawal',
                        'user/disableTFA',
                        'user/logout',
                        'user/logoutAll',
                        'user/preferences',
                        'user/requestEnableTFA',
                        'user/requestWithdrawal',
                    ),
                    'put' => array (
                        'order',
                        'order/bulk',
                        'user',
                    ),
                    'delete' => array (
                        'apiKey',
                        'order',
                        'order/all',
                    ),
                ),
            ),
            'wsconf' => array (
                'conx-tpls' => array (
                    'default' => array (
                        'type' => 'ws',
                        'baseurl' => 'wss://www.bitmex.com/realtime',
                    ),
                ),
                'methodmap' => array (
                    '_websocketTimeoutSendPing' => '_websocketTimeoutSendPing',
                    '_websocketTimeoutRemoveNonce' => '_websocketTimeoutRemoveNonce',
                ),
                'events' => array (
                    'ob' => array (
                        'conx-tpl' => 'default',
                        'conx-param' => array (
                            'url' => '{baseurl}',
                            'id' => '{id}',
                        ),
                    ),
                ),
            ),
            'exceptions' => array (
                'exact' => array (
                    'Invalid API Key.' => '\\ccxt\\AuthenticationError',
                    'Access Denied' => '\\ccxt\\PermissionDenied',
                    'Duplicate clOrdID' => '\\ccxt\\InvalidOrder',
                ),
                'broad' => array (
                    'overloaded' => '\\ccxt\\ExchangeNotAvailable',
                ),
            ),
            'options' => array (
                'fetchTickerQuotes' => false,
            ),
        ));
    }

    public function fetch_markets () {
        $markets = $this->publicGetInstrumentActiveAndIndices ();
        $result = array ();
        for ($p = 0; $p < count ($markets); $p++) {
            $market = $markets[$p];
            $active = ($market['state'] !== 'Unlisted');
            $id = $market['symbol'];
            $baseId = $market['underlying'];
            $quoteId = $market['quoteCurrency'];
            $type = null;
            $future = false;
            $prediction = false;
            $basequote = $baseId . $quoteId;
            $base = $this->common_currency_code($baseId);
            $quote = $this->common_currency_code($quoteId);
            $swap = ($id === $basequote);
            $symbol = $id;
            if ($swap) {
                $type = 'swap';
                $symbol = $base . '/' . $quote;
            } else if (mb_strpos ($id, 'B_') !== false) {
                $prediction = true;
                $type = 'prediction';
            } else {
                $future = true;
                $type = 'future';
            }
            $precision = array (
                'amount' => null,
                'price' => null,
            );
            if ($market['lotSize'])
                $precision['amount'] = $this->precision_from_string($this->truncate_to_string ($market['lotSize'], 16));
            if ($market['tickSize'])
                $precision['price'] = $this->precision_from_string($this->truncate_to_string ($market['tickSize'], 16));
            $result[] = array (
                'id' => $id,
                'symbol' => $symbol,
                'base' => $base,
                'quote' => $quote,
                'baseId' => $baseId,
                'quoteId' => $quoteId,
                'active' => $active,
                'precision' => $precision,
                'limits' => array (
                    'amount' => array (
                        'min' => $market['lotSize'],
                        'max' => $market['maxOrderQty'],
                    ),
                    'price' => array (
                        'min' => $market['tickSize'],
                        'max' => $market['maxPrice'],
                    ),
                ),
                'taker' => $market['takerFee'],
                'maker' => $market['makerFee'],
                'type' => $type,
                'spot' => false,
                'swap' => $swap,
                'future' => $future,
                'prediction' => $prediction,
                'info' => $market,
            );
        }
        return $result;
    }

    public function fetch_balance ($params = array ()) {
        $this->load_markets();
        $response = $this->privateGetUserMargin (array ( 'currency' => 'all' ));
        $result = array ( 'info' => $response );
        for ($b = 0; $b < count ($response); $b++) {
            $balance = $response[$b];
            $currency = strtoupper ($balance['currency']);
            $currency = $this->common_currency_code($currency);
            $account = array (
                'free' => $balance['availableMargin'],
                'used' => 0.0,
                'total' => $balance['marginBalance'],
            );
            if ($currency === 'BTC') {
                $account['free'] = $account['free'] * 0.00000001;
                $account['total'] = $account['total'] * 0.00000001;
            }
            $account['used'] = $account['total'] - $account['free'];
            $result[$currency] = $account;
        }
        return $this->parse_balance($result);
    }

    public function fetch_order_book ($symbol, $limit = null, $params = array ()) {
        $this->load_markets();
        $market = $this->market ($symbol);
        $request = array (
            'symbol' => $market['id'],
        );
        if ($limit !== null)
            $request['depth'] = $limit;
        $orderbook = $this->publicGetOrderBookL2 (array_merge ($request, $params));
        $result = array (
            'bids' => array (),
            'asks' => array (),
            'timestamp' => null,
            'datetime' => null,
            'nonce' => null,
        );
        for ($o = 0; $o < count ($orderbook); $o++) {
            $order = $orderbook[$o];
            $side = ($order['side'] === 'Sell') ? 'asks' : 'bids';
            $amount = $order['size'];
            $price = $order['price'];
            $result[$side][] = array ( $price, $amount );
        }
        $result['bids'] = $this->sort_by($result['bids'], 0, true);
        $result['asks'] = $this->sort_by($result['asks'], 0);
        return $result;
    }

    public function fetch_order ($id, $symbol = null, $params = array ()) {
        $filter = array ( 'filter' => array ( 'orderID' => $id ));
        $result = $this->fetch_orders($symbol, null, null, array_replace_recursive ($filter, $params));
        $numResults = is_array ($result) ? count ($result) : 0;
        if ($numResults === 1)
            return $result[0];
        throw new OrderNotFound ($this->id . ' => The order ' . $id . ' not found.');
    }

    public function fetch_orders ($symbol = null, $since = null, $limit = null, $params = array ()) {
        $this->load_markets();
        $market = null;
        $request = array ();
        if ($symbol !== null) {
            $market = $this->market ($symbol);
            $request['symbol'] = $market['id'];
        }
        if ($since !== null)
            $request['startTime'] = $this->iso8601 ($since);
        if ($limit !== null)
            $request['count'] = $limit;
        $request = array_replace_recursive ($request, $params);
        // why the hassle? urlencode in python is kinda broken for nested dicts.
        // E.g. self.urlencode(array ("filter" => array ("open" => True))) will return "filter=array ('open':+True)"
        // Bitmex doesn't like that. Hence resorting to this hack.
        if (is_array ($request) && array_key_exists ('filter', $request))
            $request['filter'] = $this->json ($request['filter']);
        $response = $this->privateGetOrder ($request);
        return $this->parse_orders($response, $market, $since, $limit);
    }

    public function fetch_open_orders ($symbol = null, $since = null, $limit = null, $params = array ()) {
        $filter_params = array ( 'filter' => array ( 'open' => true ));
        return $this->fetch_orders($symbol, $since, $limit, array_replace_recursive ($filter_params, $params));
    }

    public function fetch_closed_orders ($symbol = null, $since = null, $limit = null, $params = array ()) {
        // Bitmex barfs if you set 'open' => false in the filter...
        $orders = $this->fetch_orders($symbol, $since, $limit, $params);
        return $this->filter_by($orders, 'status', 'closed');
    }

    public function fetch_ticker ($symbol, $params = array ()) {
        $this->load_markets();
        $market = $this->market ($symbol);
        if (!$market['active'])
            throw new ExchangeError ($this->id . ' => $symbol ' . $symbol . ' is delisted');
        $request = array_merge (array (
            'symbol' => $market['id'],
            'binSize' => '1d',
            'partial' => true,
            'count' => 1,
            'reverse' => true,
        ), $params);
        $bid = null;
        $ask = null;
        if ($this->options['fetchTickerQuotes']) {
            $quotes = $this->publicGetQuoteBucketed ($request);
            $quotesLength = is_array ($quotes) ? count ($quotes) : 0;
            $quote = $quotes[$quotesLength - 1];
            $bid = $this->safe_float($quote, 'bidPrice');
            $ask = $this->safe_float($quote, 'askPrice');
        }
        $tickers = $this->publicGetTradeBucketed ($request);
        $ticker = $tickers[0];
        $timestamp = $this->milliseconds ();
        $open = $this->safe_float($ticker, 'open');
        $close = $this->safe_float($ticker, 'close');
        $change = $close - $open;
        return array (
            'symbol' => $symbol,
            'timestamp' => $timestamp,
            'datetime' => $this->iso8601 ($timestamp),
            'high' => $this->safe_float($ticker, 'high'),
            'low' => $this->safe_float($ticker, 'low'),
            'bid' => $bid,
            'bidVolume' => null,
            'ask' => $ask,
            'askVolume' => null,
            'vwap' => $this->safe_float($ticker, 'vwap'),
            'open' => $open,
            'close' => $close,
            'last' => $close,
            'previousClose' => null,
            'change' => $change,
            'percentage' => $change / $open * 100,
            'average' => $this->sum ($open, $close) / 2,
            'baseVolume' => $this->safe_float($ticker, 'homeNotional'),
            'quoteVolume' => $this->safe_float($ticker, 'foreignNotional'),
            'info' => $ticker,
        );
    }

    public function parse_ohlcv ($ohlcv, $market = null, $timeframe = '1m', $since = null, $limit = null) {
        $timestamp = $this->parse8601 ($ohlcv['timestamp']) - $this->parse_timeframe($timeframe) * 1000;
        return [
            $timestamp,
            $ohlcv['open'],
            $ohlcv['high'],
            $ohlcv['low'],
            $ohlcv['close'],
            $ohlcv['volume'],
        ];
    }

    public function fetch_ohlcv ($symbol, $timeframe = '1m', $since = null, $limit = null, $params = array ()) {
        $this->load_markets();
        // send JSON key/value pairs, such as array ("key" => "value")
        // $filter by individual fields and do advanced queries on timestamps
        // $filter = array ( 'key' => 'value' );
        // send a bare series (e.g. XBU) to nearest expiring contract in that series
        // you can also send a $timeframe, e.g. XBU:monthly
        // timeframes => daily, weekly, monthly, quarterly, and biquarterly
        $market = $this->market ($symbol);
        $request = array (
            'symbol' => $market['id'],
            'binSize' => $this->timeframes[$timeframe],
            'partial' => true,     // true == include yet-incomplete current bins
            // 'filter' => $filter, // $filter by individual fields and do advanced queries
            // 'columns' => array (),    // will return all columns if omitted
            // 'start' => 0,       // starting point for results (wtf?)
            // 'reverse' => false, // true == newest first
            // 'endTime' => '',    // ending date $filter for results
        );
        if ($limit !== null)
            $request['count'] = $limit; // default 100, max 500
        // if $since is not set, they will return candles starting from 2017-01-01
        if ($since !== null) {
            $ymdhms = $this->ymdhms ($since);
            $ymdhm = mb_substr ($ymdhms, 0, 16);
            $request['startTime'] = $ymdhm; // starting date $filter for results
        }
        $response = $this->publicGetTradeBucketed (array_merge ($request, $params));
        return $this->parse_ohlcvs($response, $market, $timeframe, $since, $limit);
    }

    public function parse_trade ($trade, $market = null) {
        $timestamp = $this->parse8601 ($trade['timestamp']);
        $symbol = null;
        if ($market === null) {
            if (is_array ($trade) && array_key_exists ('symbol', $trade))
                $market = $this->markets_by_id[$trade['symbol']];
        }
        if ($market)
            $symbol = $market['symbol'];
        return array (
            'id' => $trade['trdMatchID'],
            'info' => $trade,
            'timestamp' => $timestamp,
            'datetime' => $this->iso8601 ($timestamp),
            'symbol' => $symbol,
            'order' => null,
            'type' => null,
            'side' => strtolower ($trade['side']),
            'price' => $trade['price'],
            'amount' => $trade['size'],
        );
    }

    public function parse_order_status ($status) {
        $statuses = array (
            'New' => 'open',
            'PartiallyFilled' => 'open',
            'Filled' => 'closed',
            'DoneForDay' => 'open',
            'Canceled' => 'canceled',
            'PendingCancel' => 'open',
            'PendingNew' => 'open',
            'Rejected' => 'rejected',
            'Expired' => 'expired',
            'Stopped' => 'open',
            'Untriggered' => 'open',
            'Triggered' => 'open',
        );
        return $this->safe_string($statuses, $status, $status);
    }

    public function parse_order ($order, $market = null) {
        $status = $this->parse_order_status($this->safe_string($order, 'ordStatus'));
        $symbol = null;
        if ($market !== null) {
            $symbol = $market['symbol'];
        } else {
            $id = $order['symbol'];
            if (is_array ($this->markets_by_id) && array_key_exists ($id, $this->markets_by_id)) {
                $market = $this->markets_by_id[$id];
                $symbol = $market['symbol'];
            }
        }
        $timestamp = $this->parse8601 ($this->safe_string($order, 'timestamp'));
        $lastTradeTimestamp = $this->parse8601 ($this->safe_string($order, 'transactTime'));
        $price = $this->safe_float($order, 'price');
        $amount = $this->safe_float($order, 'orderQty');
        $filled = $this->safe_float($order, 'cumQty', 0.0);
        $remaining = null;
        if ($amount !== null) {
            if ($filled !== null) {
                $remaining = max ($amount - $filled, 0.0);
            }
        }
        $cost = null;
        if ($price !== null)
            if ($filled !== null)
                $cost = $price * $filled;
        $result = array (
            'info' => $order,
            'id' => (string) $order['orderID'],
            'timestamp' => $timestamp,
            'datetime' => $this->iso8601 ($timestamp),
            'lastTradeTimestamp' => $lastTradeTimestamp,
            'symbol' => $symbol,
            'type' => strtolower ($order['ordType']),
            'side' => strtolower ($order['side']),
            'price' => $price,
            'amount' => $amount,
            'cost' => $cost,
            'filled' => $filled,
            'remaining' => $remaining,
            'status' => $status,
            'fee' => null,
        );
        return $result;
    }

    public function fetch_trades ($symbol, $since = null, $limit = null, $params = array ()) {
        $this->load_markets();
        $market = $this->market ($symbol);
        $request = array (
            'symbol' => $market['id'],
        );
        if ($since !== null)
            $request['startTime'] = $this->iso8601 ($since);
        if ($limit !== null)
            $request['count'] = $limit;
        $response = $this->publicGetTrade (array_merge ($request, $params));
        return $this->parse_trades($response, $market);
    }

    public function create_order ($symbol, $type, $side, $amount, $price = null, $params = array ()) {
        $this->load_markets();
        $request = array (
            'symbol' => $this->market_id($symbol),
            'side' => $this->capitalize ($side),
            'orderQty' => $amount,
            'ordType' => $this->capitalize ($type),
        );
        if ($price !== null)
            $request['price'] = $price;
        $response = $this->privatePostOrder (array_merge ($request, $params));
        $order = $this->parse_order($response);
        $id = $order['id'];
        $this->orders[$id] = $order;
        return array_merge (array ( 'info' => $response ), $order);
    }

    public function edit_order ($id, $symbol, $type, $side, $amount = null, $price = null, $params = array ()) {
        $this->load_markets();
        $request = array (
            'orderID' => $id,
        );
        if ($amount !== null)
            $request['orderQty'] = $amount;
        if ($price !== null)
            $request['price'] = $price;
        $response = $this->privatePutOrder (array_merge ($request, $params));
        $order = $this->parse_order($response);
        $this->orders[$order['id']] = $order;
        return array_merge (array ( 'info' => $response ), $order);
    }

    public function cancel_order ($id, $symbol = null, $params = array ()) {
        $this->load_markets();
        $response = $this->privateDeleteOrder (array_merge (array ( 'orderID' => $id ), $params));
        $order = $response[0];
        $error = $this->safe_string($order, 'error');
        if ($error !== null)
            if (mb_strpos ($error, 'Unable to cancel $order due to existing state') !== false)
                throw new OrderNotFound ($this->id . ' cancelOrder() failed => ' . $error);
        $order = $this->parse_order($order);
        $this->orders[$order['id']] = $order;
        return array_merge (array ( 'info' => $response ), $order);
    }

    public function is_fiat ($currency) {
        if ($currency === 'EUR')
            return true;
        if ($currency === 'PLN')
            return true;
        return false;
    }

    public function withdraw ($code, $amount, $address, $tag = null, $params = array ()) {
        $this->check_address($address);
        $this->load_markets();
        // $currency = $this->currency ($code);
        if ($code !== 'BTC') {
            throw new ExchangeError ($this->id . ' supoprts BTC withdrawals only, other currencies coming soon...');
        }
        $request = array (
            'currency' => 'XBt', // temporarily
            'amount' => $amount,
            'address' => $address,
            // 'otpToken' => '123456', // requires if two-factor auth (OTP) is enabled
            // 'fee' => 0.001, // bitcoin network fee
        );
        $response = $this->privatePostUserRequestWithdrawal (array_merge ($request, $params));
        return array (
            'info' => $response,
            'id' => $response['transactID'],
        );
    }

    public function handle_errors ($code, $reason, $url, $method, $headers, $body) {
        if ($code === 429)
            throw new DDoSProtection ($this->id . ' ' . $body);
        if ($code >= 400) {
            if ($body) {
                if ($body[0] === '{') {
                    $response = json_decode ($body, $as_associative_array = true);
                    $error = $this->safe_value($response, 'error', array ());
                    $message = $this->safe_string($error, 'message');
                    $feedback = $this->id . ' ' . $body;
                    $exact = $this->exceptions['exact'];
                    if (is_array ($exact) && array_key_exists ($message, $exact)) {
                        throw new $exact[$message] ($feedback);
                    }
                    $broad = $this->exceptions['broad'];
                    $broadKey = $this->findBroadlyMatchedKey ($broad, $message);
                    if ($broadKey !== null) {
                        throw new $broad[$broadKey] ($feedback);
                    }
                    if ($code === 400) {
                        throw new BadRequest ($feedback);
                    }
                    throw new ExchangeError ($feedback); // unknown $message
                }
            }
        }
    }

    public function nonce () {
        return $this->milliseconds ();
    }

    public function sign ($path, $api = 'public', $method = 'GET', $params = array (), $headers = null, $body = null) {
        $query = '/api' . '/' . $this->version . '/' . $path;
        if ($method !== 'PUT')
            if ($params)
                $query .= '?' . $this->urlencode ($params);
        $url = $this->urls['api'] . $query;
        if ($api === 'private') {
            $this->check_required_credentials();
            $nonce = (string) $this->nonce ();
            $auth = $method . $query . $nonce;
            if ($method === 'POST' || $method === 'PUT') {
                if ($params) {
                    $body = $this->json ($params);
                    $auth .= $body;
                }
            }
            $headers = array (
                'Content-Type' => 'application/json',
                'api-nonce' => $nonce,
                'api-key' => $this->apiKey,
                'api-signature' => $this->hmac ($this->encode ($auth), $this->encode ($this->secret)),
            );
        }
        return array ( 'url' => $url, 'method' => $method, 'body' => $body, 'headers' => $headers );
    }

    public function _websocket_on_open ($contextId, $websocketOptions) {
        $lastTimer = $this->_contextGet ($contextId, 'timer');
        if ($lastTimer !== null) {
            $this->_cancelTimeout ($lastTimer);
        }
        $lastTimer = $this->_setTimeout (5000, $this->_websocketMethodMap ('_websocketTimeoutSendPing'), array ());
        $this->_contextSet ($contextId, 'timer', $lastTimer);
        $dbids = array ();
        $this->_contextSet ($contextId, 'dbids', $dbids);
        // send auth
        // $nonce = $this->nonce ();
        // $signature = $this->hmac ($this->encode ('GET/realtime' . (string) $nonce), $this->encode ($this->secret));
        // $payload = array (
        //     'op' => 'authKeyExpires',
        //     'args' => [$this->apiKey, $nonce, $signature]
        //  );
        // $this->asyncSendJson ($payload);
    }

    public function _websocket_on_message ($contextId, $data) {
        // send ping after 5 seconds if not message received
        if ($data === 'pong') {
            return;
        }
        $msg = json_decode ($data, $as_associative_array = true);
        $table = $this->safe_string($msg, 'table');
        $subscribe = $this->safe_string($msg, 'subscribe');
        $unsubscribe = $this->safe_string($msg, 'unsubscribe');
        $status = $this->safe_integer($msg, 'status');
        if ($subscribe !== null) {
            $this->_websocket_handle_subscription ($contextId, 'ob', $msg);
        } else if ($unsubscribe !== null) {
            $this->_websocket_handle_unsubscription ($contextId, 'ob', $msg);
        } else if ($table !== null) {
            if ($table === 'orderBookL2') {
                $this->_websocket_handle_ob ($contextId, $msg);
            }
        } else if ($status !== null) {
            $this->_websocket_handle_error ($contextId, $msg);
        }
    }

    public function _websocket_timeout_send_ping () {
        $this->websocketSend ('ping');
    }

    public function _websocket_handle_error ($contextId, $msg) {
        $status = $this->safe_integer($msg, 'status');
        $error = $this->safe_string($msg, 'error');
        $this->emit ('err', new ExchangeError ($this->id . ' $status ' . $status . ':' . $error), $contextId);
    }

    public function _websocket_handle_subscription ($contextId, $event, $msg) {
        $success = $this->safe_value($msg, 'success');
        $subscribe = $this->safe_string($msg, 'subscribe');
        $parts = explode (':', $subscribe);
        $partsLen = is_array ($parts) ? count ($parts) : 0;
        if ($partsLen === 2) {
            if ($parts[0] === 'orderBookL2') {
                $symbol = $this->find_symbol($parts[1]);
                $symbolData = $this->_contextGetSymbolData ($contextId, $event, $symbol);
                if (is_array ($symbolData) && array_key_exists ('sub-nonces', $symbolData)) {
                    $nonces = $symbolData['sub-nonces'];
                    $keys = is_array ($nonces) ? array_keys ($nonces) : array ();
                    for ($i = 0; $i < count ($keys); $i++) {
                        $nonce = $keys[$i];
                        $this->_cancelTimeout ($nonces[$nonce]);
                        $this->emit ($nonce, $success);
                    }
                    $symbolData['sub-nonces'] = array ();
                    $this->_contextSetSymbolData ($contextId, $event, $symbol, $symbolData);
                }
            }
        }
    }

    public function _websocket_handle_unsubscription ($contextId, $event, $msg) {
        $success = $this->safe_value($msg, 'success');
        $unsubscribe = $this->safe_string($msg, 'unsubscribe');
        $parts = explode (':', $unsubscribe);
        $partsLen = is_array ($parts) ? count ($parts) : 0;
        if ($partsLen === 2) {
            if ($parts[0] === 'orderBookL2') {
                $symbol = $this->find_symbol($parts[1]);
                if ($success) {
                    $dbids = $this->_contextGet ($contextId, 'dbids');
                    if (is_array ($dbids) && array_key_exists ($symbol, $dbids)) {
                        $this->omit ($dbids, $symbol);
                        $this->_contextSet ($contextId, 'dbids', $dbids);
                    }
                }
                $symbolData = $this->_contextGetSymbolData ($contextId, $event, $symbol);
                if (is_array ($symbolData) && array_key_exists ('unsub-nonces', $symbolData)) {
                    $nonces = $symbolData['unsub-nonces'];
                    $keys = is_array ($nonces) ? array_keys ($nonces) : array ();
                    for ($i = 0; $i < count ($keys); $i++) {
                        $nonce = $keys[$i];
                        $this->_cancelTimeout ($nonces[$nonce]);
                        $this->emit ($nonce, $success);
                    }
                    $symbolData['unsub-nonces'] = array ();
                    $this->_contextSetSymbolData ($contextId, $event, $symbol, $symbolData);
                }
            }
        }
    }

    public function _websocket_handle_ob ($contextId, $msg) {
        $action = $this->safe_string($msg, 'action');
        $data = $this->safe_value($msg, 'data');
        $symbol = $this->safe_string($data[0], 'symbol');
        $symbol = $this->find_symbol($symbol);
        $dbids = $this->_contextGet ($contextId, 'dbids');
        $symbolData = $this->_contextGetSymbolData ($contextId, 'ob', $symbol);
        if ($action === 'partial') {
            $ob = array (
                'bids' => array (),
                'asks' => array (),
                'timestamp' => null,
                'datetime' => null,
                'nonce' => null,
            );
            $obIds = array ();
            for ($o = 0; $o < count ($data); $o++) {
                $order = $data[$o];
                $side = ($order['side'] === 'Sell') ? 'asks' : 'bids';
                $amount = $order['size'];
                $price = $order['price'];
                $priceId = $order['id'];
                $ob[$side][] = array ( $price, $amount );
                $obIds[$priceId] = $price;
            }
            $ob['bids'] = $this->sort_by($ob['bids'], 0, true);
            $ob['asks'] = $this->sort_by($ob['asks'], 0);
            $symbolData['ob'] = $ob;
            $dbids[$symbol] = $obIds;
            $this->emit ('ob', $symbol, $this->_cloneOrderBook ($ob, $symbolData['limit']));
        } else if ($action === 'update') {
            if (is_array ($dbids) && array_key_exists ($symbol, $dbids)) {
                $obIds = $dbids[$symbol];
                $curob = $symbolData['ob'];
                for ($o = 0; $o < count ($data); $o++) {
                    $order = $data[$o];
                    $amount = $order['size'];
                    $side = ($order['side'] === 'Sell') ? 'asks' : 'bids';
                    $priceId = $order['id'];
                    $price = $obIds[$priceId];
                    $this->updateBidAsk ([$price, $amount], $curob[$side], $order['side'] === 'Buy');
                }
                $symbolData['ob'] = $curob;
                $this->emit ('ob', $symbol, $this->_cloneOrderBook ($curob, $symbolData['limit']));
            }
        } else if ($action === 'insert') {
            if (is_array ($dbids) && array_key_exists ($symbol, $dbids)) {
                $curob = $symbolData['ob'];
                for ($o = 0; $o < count ($data); $o++) {
                    $order = $data[$o];
                    $amount = $order['size'];
                    $side = ($order['side'] === 'Sell') ? 'asks' : 'bids';
                    $priceId = $order['id'];
                    $price = $order['price'];
                    $this->updateBidAsk ([$price, $amount], $curob[$side], $order['side'] === 'Buy');
                    $dbids[$symbol][$priceId] = $price;
                }
                $symbolData['ob'] = $curob;
                $this->emit ('ob', $symbol, $this->_cloneOrderBook ($curob, $symbolData['limit']));
            }
        } else if ($action === 'delete') {
            if (is_array ($dbids) && array_key_exists ($symbol, $dbids)) {
                $obIds = $dbids[$symbol];
                $curob = $symbolData['ob'];
                for ($o = 0; $o < count ($data); $o++) {
                    $order = $data[$o];
                    $side = ($order['side'] === 'Sell') ? 'asks' : 'bids';
                    $priceId = $order['id'];
                    $price = $obIds[$priceId];
                    $this->updateBidAsk ([$price, 0], $curob[$side], $order['side'] === 'Buy');
                    $this->omit ($dbids[$symbol], $priceId);
                }
                $symbolData['ob'] = $curob;
                $this->emit ('ob', $symbol, $this->_cloneOrderBook ($curob, $symbolData['limit']));
            }
        } else {
            $this->emit ('err', new ExchangeError ($this->id . ' invalid orderbook message'));
        }
        $this->_contextSet ($contextId, 'dbids', $dbids);
        $this->_contextSetSymbolData ($contextId, 'ob', $symbol, $symbolData);
    }

    public function _websocket_subscribe ($contextId, $event, $symbol, $nonce, $params = array ()) {
        if ($event !== 'ob') {
            throw new NotSupported ('subscribe ' . $event . '(' . $symbol . ') not supported for exchange ' . $this->id);
        }
        $id = strtoupper ($this->market_id ($symbol));
        $payload = array (
            'op' => 'subscribe',
            'args' => ['orderBookL2:' . $id],
        );
        $symbolData = $this->_contextGetSymbolData ($contextId, $event, $symbol);
        if (!(is_array ($symbolData) && array_key_exists ('sub-nonces', $symbolData))) {
            $symbolData['sub-nonces'] = array ();
        }
        $symbolData['limit'] = $this->safe_integer($params, 'limit', null);
        $nonceStr = (string) $nonce;
        $handle = $this->_setTimeout ($this->timeout, $this->_websocketMethodMap ('_websocketTimeoutRemoveNonce'), [$contextId, $nonceStr, $event, $symbol, 'sub-nonce']);
        $symbolData['sub-nonces'][$nonceStr] = $handle;
        $this->_contextSetSymbolData ($contextId, $event, $symbol, $symbolData);
        $this->websocketSendJson ($payload);
    }

    public function _websocket_unsubscribe ($contextId, $event, $symbol, $nonce, $params = array ()) {
        if ($event !== 'ob') {
            throw new NotSupported ('unsubscribe ' . $event . '(' . $symbol . ') not supported for exchange ' . $this->id);
        }
        $id = strtoupper ($this->market_id ($symbol));
        $payload = array (
            'op' => 'unsubscribe',
            'args' => ['orderBookL2:' . $id],
        );
        $symbolData = $this->_contextGetSymbolData ($contextId, $event, $symbol);
        if (!(is_array ($symbolData) && array_key_exists ('unsub-nonces', $symbolData))) {
            $symbolData['unsub-nonces'] = array ();
        }
        $nonceStr = (string) $nonce;
        $handle = $this->_setTimeout ($this->timeout, $this->_websocketMethodMap ('_websocketTimeoutRemoveNonce'), [$contextId, $nonceStr, $event, $symbol, 'unsub-nonces']);
        $symbolData['unsub-nonces'][$nonceStr] = $handle;
        $this->_contextSetSymbolData ($contextId, $event, $symbol, $symbolData);
        $this->websocketSendJson ($payload);
    }

    public function _websocket_timeout_remove_nonce ($contextId, $timerNonce, $event, $symbol, $key) {
        $symbolData = $this->_contextGetSymbolData ($contextId, $event, $symbol);
        if (is_array ($symbolData) && array_key_exists ($key, $symbolData)) {
            $nonces = $symbolData[$key];
            if (is_array ($nonces) && array_key_exists ($timerNonce, $nonces)) {
                $this->omit ($symbolData[$key], $timerNonce);
                $this->_contextSetSymbolData ($contextId, $event, $symbol, $symbolData);
            }
        }
    }

    public function _get_current_websocket_orderbook ($contextId, $symbol, $limit) {
        $data = $this->_contextGetSymbolData ($contextId, 'ob', $symbol);
        if ((is_array ($data) && array_key_exists ('ob', $data)) && ($data['ob'] !== null)) {
            return $this->_cloneOrderBook ($data['ob'], $limit);
        }
        return null;
    }
}
