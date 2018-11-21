'use strict';

//  ---------------------------------------------------------------------------

const Exchange = require ('./base/Exchange');
const { AuthenticationError, BadRequest, DDoSProtection, ExchangeError, ExchangeNotAvailable, InvalidOrder, OrderNotFound, PermissionDenied, NotSupported } = require ('./base/errors');

//  ---------------------------------------------------------------------------

module.exports = class bitmex extends Exchange {
    describe () {
        return this.deepExtend (super.describe (), {
            'id': 'bitmex',
            'name': 'BitMEX',
            'countries': [ 'SC' ], // Seychelles
            'version': 'v1',
            'userAgent': undefined,
            'rateLimit': 2000,
            'has': {
                'CORS': false,
                'fetchOHLCV': true,
                'withdraw': true,
                'editOrder': true,
                'fetchOrder': true,
                'fetchOrders': true,
                'fetchOpenOrders': true,
                'fetchClosedOrders': true,
            },
            'timeframes': {
                '1m': '1m',
                '5m': '5m',
                '1h': '1h',
                '1d': '1d',
            },
            'urls': {
                'test': 'https://testnet.bitmex.com',
                'logo': 'https://user-images.githubusercontent.com/1294454/27766319-f653c6e6-5ed4-11e7-933d-f0bc3699ae8f.jpg',
                'api': 'https://www.bitmex.com',
                'www': 'https://www.bitmex.com',
                'doc': [
                    'https://www.bitmex.com/app/apiOverview',
                    'https://github.com/BitMEX/api-connectors/tree/master/official-http',
                ],
                'fees': 'https://www.bitmex.com/app/fees',
                'referral': 'https://www.bitmex.com/register/rm3C16',
            },
            'api': {
                'public': {
                    'get': [
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
                    ],
                },
                'private': {
                    'get': [
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
                    ],
                    'post': [
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
                    ],
                    'put': [
                        'order',
                        'order/bulk',
                        'user',
                    ],
                    'delete': [
                        'apiKey',
                        'order',
                        'order/all',
                    ],
                },
            },
            'wsconf': {
                'conx-tpls': {
                    'default': {
                        'type': 'ws',
                        'baseurl': 'wss://www.bitmex.com/realtime',
                    },
                },
                'methodmap': {
                    '_websocketTimeoutSendPing': '_websocketTimeoutSendPing',
                    '_websocketTimeoutRemoveNonce': '_websocketTimeoutRemoveNonce',
                },
                'events': {
                    'ob': {
                        'conx-tpl': 'default',
                        'conx-param': {
                            'url': '{baseurl}',
                            'id': '{id}',
                        },
                    },
                },
            },
            'exceptions': {
                'exact': {
                    'Invalid API Key.': AuthenticationError,
                    'Access Denied': PermissionDenied,
                    'Duplicate clOrdID': InvalidOrder,
                },
                'broad': {
                    'overloaded': ExchangeNotAvailable,
                },
            },
            'options': {
                'fetchTickerQuotes': false,
            },
        });
    }

    async fetchMarkets () {
        let markets = await this.publicGetInstrumentActiveAndIndices ();
        let result = [];
        for (let p = 0; p < markets.length; p++) {
            let market = markets[p];
            let active = (market['state'] !== 'Unlisted');
            let id = market['symbol'];
            let baseId = market['underlying'];
            let quoteId = market['quoteCurrency'];
            let type = undefined;
            let future = false;
            let prediction = false;
            let basequote = baseId + quoteId;
            let base = this.commonCurrencyCode (baseId);
            let quote = this.commonCurrencyCode (quoteId);
            let swap = (id === basequote);
            let symbol = id;
            if (swap) {
                type = 'swap';
                symbol = base + '/' + quote;
            } else if (id.indexOf ('B_') >= 0) {
                prediction = true;
                type = 'prediction';
            } else {
                future = true;
                type = 'future';
            }
            let precision = {
                'amount': undefined,
                'price': undefined,
            };
            if (market['lotSize'])
                precision['amount'] = this.precisionFromString (this.truncate_to_string (market['lotSize'], 16));
            if (market['tickSize'])
                precision['price'] = this.precisionFromString (this.truncate_to_string (market['tickSize'], 16));
            result.push ({
                'id': id,
                'symbol': symbol,
                'base': base,
                'quote': quote,
                'baseId': baseId,
                'quoteId': quoteId,
                'active': active,
                'precision': precision,
                'limits': {
                    'amount': {
                        'min': market['lotSize'],
                        'max': market['maxOrderQty'],
                    },
                    'price': {
                        'min': market['tickSize'],
                        'max': market['maxPrice'],
                    },
                },
                'taker': market['takerFee'],
                'maker': market['makerFee'],
                'type': type,
                'spot': false,
                'swap': swap,
                'future': future,
                'prediction': prediction,
                'info': market,
            });
        }
        return result;
    }

    async fetchBalance (params = {}) {
        await this.loadMarkets ();
        let response = await this.privateGetUserMargin ({ 'currency': 'all' });
        let result = { 'info': response };
        for (let b = 0; b < response.length; b++) {
            let balance = response[b];
            let currency = balance['currency'].toUpperCase ();
            currency = this.commonCurrencyCode (currency);
            let account = {
                'free': balance['availableMargin'],
                'used': 0.0,
                'total': balance['marginBalance'],
            };
            if (currency === 'BTC') {
                account['free'] = account['free'] * 0.00000001;
                account['total'] = account['total'] * 0.00000001;
            }
            account['used'] = account['total'] - account['free'];
            result[currency] = account;
        }
        return this.parseBalance (result);
    }

    async fetchOrderBook (symbol, limit = undefined, params = {}) {
        await this.loadMarkets ();
        let market = this.market (symbol);
        let request = {
            'symbol': market['id'],
        };
        if (limit !== undefined)
            request['depth'] = limit;
        let orderbook = await this.publicGetOrderBookL2 (this.extend (request, params));
        let result = {
            'bids': [],
            'asks': [],
            'timestamp': undefined,
            'datetime': undefined,
            'nonce': undefined,
        };
        for (let o = 0; o < orderbook.length; o++) {
            let order = orderbook[o];
            let side = (order['side'] === 'Sell') ? 'asks' : 'bids';
            let amount = order['size'];
            let price = order['price'];
            result[side].push ([ price, amount ]);
        }
        result['bids'] = this.sortBy (result['bids'], 0, true);
        result['asks'] = this.sortBy (result['asks'], 0);
        return result;
    }

    async fetchOrder (id, symbol = undefined, params = {}) {
        let filter = { 'filter': { 'orderID': id }};
        let result = await this.fetchOrders (symbol, undefined, undefined, this.deepExtend (filter, params));
        let numResults = result.length;
        if (numResults === 1)
            return result[0];
        throw new OrderNotFound (this.id + ': The order ' + id + ' not found.');
    }

    async fetchOrders (symbol = undefined, since = undefined, limit = undefined, params = {}) {
        await this.loadMarkets ();
        let market = undefined;
        let request = {};
        if (symbol !== undefined) {
            market = this.market (symbol);
            request['symbol'] = market['id'];
        }
        if (since !== undefined)
            request['startTime'] = this.iso8601 (since);
        if (limit !== undefined)
            request['count'] = limit;
        request = this.deepExtend (request, params);
        // why the hassle? urlencode in python is kinda broken for nested dicts.
        // E.g. self.urlencode({"filter": {"open": True}}) will return "filter={'open':+True}"
        // Bitmex doesn't like that. Hence resorting to this hack.
        if ('filter' in request)
            request['filter'] = this.json (request['filter']);
        let response = await this.privateGetOrder (request);
        return this.parseOrders (response, market, since, limit);
    }

    async fetchOpenOrders (symbol = undefined, since = undefined, limit = undefined, params = {}) {
        let filter_params = { 'filter': { 'open': true }};
        return await this.fetchOrders (symbol, since, limit, this.deepExtend (filter_params, params));
    }

    async fetchClosedOrders (symbol = undefined, since = undefined, limit = undefined, params = {}) {
        // Bitmex barfs if you set 'open': false in the filter...
        let orders = await this.fetchOrders (symbol, since, limit, params);
        return this.filterBy (orders, 'status', 'closed');
    }

    async fetchTicker (symbol, params = {}) {
        await this.loadMarkets ();
        let market = this.market (symbol);
        if (!market['active'])
            throw new ExchangeError (this.id + ': symbol ' + symbol + ' is delisted');
        let request = this.extend ({
            'symbol': market['id'],
            'binSize': '1d',
            'partial': true,
            'count': 1,
            'reverse': true,
        }, params);
        let bid = undefined;
        let ask = undefined;
        if (this.options['fetchTickerQuotes']) {
            let quotes = await this.publicGetQuoteBucketed (request);
            let quotesLength = quotes.length;
            let quote = quotes[quotesLength - 1];
            bid = this.safeFloat (quote, 'bidPrice');
            ask = this.safeFloat (quote, 'askPrice');
        }
        let tickers = await this.publicGetTradeBucketed (request);
        let ticker = tickers[0];
        let timestamp = this.milliseconds ();
        let open = this.safeFloat (ticker, 'open');
        let close = this.safeFloat (ticker, 'close');
        let change = close - open;
        return {
            'symbol': symbol,
            'timestamp': timestamp,
            'datetime': this.iso8601 (timestamp),
            'high': this.safeFloat (ticker, 'high'),
            'low': this.safeFloat (ticker, 'low'),
            'bid': bid,
            'bidVolume': undefined,
            'ask': ask,
            'askVolume': undefined,
            'vwap': this.safeFloat (ticker, 'vwap'),
            'open': open,
            'close': close,
            'last': close,
            'previousClose': undefined,
            'change': change,
            'percentage': change / open * 100,
            'average': this.sum (open, close) / 2,
            'baseVolume': this.safeFloat (ticker, 'homeNotional'),
            'quoteVolume': this.safeFloat (ticker, 'foreignNotional'),
            'info': ticker,
        };
    }

    parseOHLCV (ohlcv, market = undefined, timeframe = '1m', since = undefined, limit = undefined) {
        let timestamp = this.parse8601 (ohlcv['timestamp']) - this.parseTimeframe (timeframe) * 1000;
        return [
            timestamp,
            ohlcv['open'],
            ohlcv['high'],
            ohlcv['low'],
            ohlcv['close'],
            ohlcv['volume'],
        ];
    }

    async fetchOHLCV (symbol, timeframe = '1m', since = undefined, limit = undefined, params = {}) {
        await this.loadMarkets ();
        // send JSON key/value pairs, such as {"key": "value"}
        // filter by individual fields and do advanced queries on timestamps
        // let filter = { 'key': 'value' };
        // send a bare series (e.g. XBU) to nearest expiring contract in that series
        // you can also send a timeframe, e.g. XBU:monthly
        // timeframes: daily, weekly, monthly, quarterly, and biquarterly
        let market = this.market (symbol);
        let request = {
            'symbol': market['id'],
            'binSize': this.timeframes[timeframe],
            'partial': true,     // true == include yet-incomplete current bins
            // 'filter': filter, // filter by individual fields and do advanced queries
            // 'columns': [],    // will return all columns if omitted
            // 'start': 0,       // starting point for results (wtf?)
            // 'reverse': false, // true == newest first
            // 'endTime': '',    // ending date filter for results
        };
        if (limit !== undefined)
            request['count'] = limit; // default 100, max 500
        // if since is not set, they will return candles starting from 2017-01-01
        if (since !== undefined) {
            let ymdhms = this.ymdhms (since);
            let ymdhm = ymdhms.slice (0, 16);
            request['startTime'] = ymdhm; // starting date filter for results
        }
        let response = await this.publicGetTradeBucketed (this.extend (request, params));
        return this.parseOHLCVs (response, market, timeframe, since, limit);
    }

    parseTrade (trade, market = undefined) {
        let timestamp = this.parse8601 (trade['timestamp']);
        let symbol = undefined;
        if (market === undefined) {
            if ('symbol' in trade)
                market = this.markets_by_id[trade['symbol']];
        }
        if (market)
            symbol = market['symbol'];
        return {
            'id': trade['trdMatchID'],
            'info': trade,
            'timestamp': timestamp,
            'datetime': this.iso8601 (timestamp),
            'symbol': symbol,
            'order': undefined,
            'type': undefined,
            'side': trade['side'].toLowerCase (),
            'price': trade['price'],
            'amount': trade['size'],
        };
    }

    parseOrderStatus (status) {
        let statuses = {
            'New': 'open',
            'PartiallyFilled': 'open',
            'Filled': 'closed',
            'DoneForDay': 'open',
            'Canceled': 'canceled',
            'PendingCancel': 'open',
            'PendingNew': 'open',
            'Rejected': 'rejected',
            'Expired': 'expired',
            'Stopped': 'open',
            'Untriggered': 'open',
            'Triggered': 'open',
        };
        return this.safeString (statuses, status, status);
    }

    parseOrder (order, market = undefined) {
        let status = this.parseOrderStatus (this.safeString (order, 'ordStatus'));
        let symbol = undefined;
        if (market !== undefined) {
            symbol = market['symbol'];
        } else {
            let id = order['symbol'];
            if (id in this.markets_by_id) {
                market = this.markets_by_id[id];
                symbol = market['symbol'];
            }
        }
        let timestamp = this.parse8601 (this.safeString (order, 'timestamp'));
        let lastTradeTimestamp = this.parse8601 (this.safeString (order, 'transactTime'));
        let price = this.safeFloat (order, 'price');
        let amount = this.safeFloat (order, 'orderQty');
        let filled = this.safeFloat (order, 'cumQty', 0.0);
        let remaining = undefined;
        if (amount !== undefined) {
            if (filled !== undefined) {
                remaining = Math.max (amount - filled, 0.0);
            }
        }
        let cost = undefined;
        if (price !== undefined)
            if (filled !== undefined)
                cost = price * filled;
        let result = {
            'info': order,
            'id': order['orderID'].toString (),
            'timestamp': timestamp,
            'datetime': this.iso8601 (timestamp),
            'lastTradeTimestamp': lastTradeTimestamp,
            'symbol': symbol,
            'type': order['ordType'].toLowerCase (),
            'side': order['side'].toLowerCase (),
            'price': price,
            'amount': amount,
            'cost': cost,
            'filled': filled,
            'remaining': remaining,
            'status': status,
            'fee': undefined,
        };
        return result;
    }

    async fetchTrades (symbol, since = undefined, limit = undefined, params = {}) {
        await this.loadMarkets ();
        let market = this.market (symbol);
        let request = {
            'symbol': market['id'],
        };
        if (since !== undefined)
            request['startTime'] = this.iso8601 (since);
        if (limit !== undefined)
            request['count'] = limit;
        let response = await this.publicGetTrade (this.extend (request, params));
        return this.parseTrades (response, market);
    }

    async createOrder (symbol, type, side, amount, price = undefined, params = {}) {
        await this.loadMarkets ();
        let request = {
            'symbol': this.marketId (symbol),
            'side': this.capitalize (side),
            'orderQty': amount,
            'ordType': this.capitalize (type),
        };
        if (price !== undefined)
            request['price'] = price;
        let response = await this.privatePostOrder (this.extend (request, params));
        let order = this.parseOrder (response);
        let id = order['id'];
        this.orders[id] = order;
        return this.extend ({ 'info': response }, order);
    }

    async editOrder (id, symbol, type, side, amount = undefined, price = undefined, params = {}) {
        await this.loadMarkets ();
        let request = {
            'orderID': id,
        };
        if (amount !== undefined)
            request['orderQty'] = amount;
        if (price !== undefined)
            request['price'] = price;
        let response = await this.privatePutOrder (this.extend (request, params));
        let order = this.parseOrder (response);
        this.orders[order['id']] = order;
        return this.extend ({ 'info': response }, order);
    }

    async cancelOrder (id, symbol = undefined, params = {}) {
        await this.loadMarkets ();
        let response = await this.privateDeleteOrder (this.extend ({ 'orderID': id }, params));
        let order = response[0];
        let error = this.safeString (order, 'error');
        if (error !== undefined)
            if (error.indexOf ('Unable to cancel order due to existing state') >= 0)
                throw new OrderNotFound (this.id + ' cancelOrder() failed: ' + error);
        order = this.parseOrder (order);
        this.orders[order['id']] = order;
        return this.extend ({ 'info': response }, order);
    }

    isFiat (currency) {
        if (currency === 'EUR')
            return true;
        if (currency === 'PLN')
            return true;
        return false;
    }

    async withdraw (code, amount, address, tag = undefined, params = {}) {
        this.checkAddress (address);
        await this.loadMarkets ();
        // let currency = this.currency (code);
        if (code !== 'BTC') {
            throw new ExchangeError (this.id + ' supoprts BTC withdrawals only, other currencies coming soon...');
        }
        let request = {
            'currency': 'XBt', // temporarily
            'amount': amount,
            'address': address,
            // 'otpToken': '123456', // requires if two-factor auth (OTP) is enabled
            // 'fee': 0.001, // bitcoin network fee
        };
        let response = await this.privatePostUserRequestWithdrawal (this.extend (request, params));
        return {
            'info': response,
            'id': response['transactID'],
        };
    }

    handleErrors (code, reason, url, method, headers, body) {
        if (code === 429)
            throw new DDoSProtection (this.id + ' ' + body);
        if (code >= 400) {
            if (body) {
                if (body[0] === '{') {
                    let response = JSON.parse (body);
                    const error = this.safeValue (response, 'error', {});
                    const message = this.safeString (error, 'message');
                    const feedback = this.id + ' ' + body;
                    const exact = this.exceptions['exact'];
                    if (message in exact) {
                        throw new exact[message] (feedback);
                    }
                    const broad = this.exceptions['broad'];
                    const broadKey = this.findBroadlyMatchedKey (broad, message);
                    if (broadKey !== undefined) {
                        throw new broad[broadKey] (feedback);
                    }
                    if (code === 400) {
                        throw new BadRequest (feedback);
                    }
                    throw new ExchangeError (feedback); // unknown message
                }
            }
        }
    }

    nonce () {
        return this.milliseconds ();
    }

    sign (path, api = 'public', method = 'GET', params = {}, headers = undefined, body = undefined) {
        let query = '/api' + '/' + this.version + '/' + path;
        if (method !== 'PUT')
            if (Object.keys (params).length)
                query += '?' + this.urlencode (params);
        let url = this.urls['api'] + query;
        if (api === 'private') {
            this.checkRequiredCredentials ();
            let nonce = this.nonce ().toString ();
            let auth = method + query + nonce;
            if (method === 'POST' || method === 'PUT') {
                if (Object.keys (params).length) {
                    body = this.json (params);
                    auth += body;
                }
            }
            headers = {
                'Content-Type': 'application/json',
                'api-nonce': nonce,
                'api-key': this.apiKey,
                'api-signature': this.hmac (this.encode (auth), this.encode (this.secret)),
            };
        }
        return { 'url': url, 'method': method, 'body': body, 'headers': headers };
    }

    _websocketOnOpen (contextId, websocketOptions) { // eslint-disable-line no-unused-vars
        let lastTimer = this._contextGet (contextId, 'timer');
        if (typeof lastTimer !== 'undefined') {
            this._cancelTimeout (lastTimer);
        }
        lastTimer = this._setTimeout (5000, this._websocketMethodMap ('_websocketTimeoutSendPing'), []);
        this._contextSet (contextId, 'timer', lastTimer);
        let dbids = {};
        this._contextSet (contextId, 'dbids', dbids);
        // send auth
        // let nonce = this.nonce ();
        // let signature = this.hmac (this.encode ('GET/realtime' + nonce.toString ()), this.encode (this.secret));
        // let payload = {
        //     'op': 'authKeyExpires',
        //     'args': [this.apiKey, nonce, signature]
        //  };
        // this.asyncSendJson (payload);
    }

    _websocketOnMessage (contextId, data) {
        // send ping after 5 seconds if not message received
        if (data === 'pong') {
            return;
        }
        let msg = JSON.parse (data);
        let table = this.safeString (msg, 'table');
        let subscribe = this.safeString (msg, 'subscribe');
        let unsubscribe = this.safeString (msg, 'unsubscribe');
        let status = this.safeInteger (msg, 'status');
        if (typeof subscribe !== 'undefined') {
            this._websocketHandleSubscription (contextId, 'ob', msg);
        } else if (typeof unsubscribe !== 'undefined') {
            this._websocketHandleUnsubscription (contextId, 'ob', msg);
        } else if (typeof table !== 'undefined') {
            if (table === 'orderBookL2') {
                this._websocketHandleOb (contextId, msg);
            }
        } else if (typeof status !== 'undefined') {
            this._websocketHandleError (contextId, msg);
        }
    }

    _websocketTimeoutSendPing () {
        this.websocketSend ('ping');
    }

    _websocketHandleError (contextId, msg) {
        let status = this.safeInteger (msg, 'status');
        let error = this.safeString (msg, 'error');
        this.emit ('err', new ExchangeError (this.id + ' status ' + status + ':' + error), contextId);
    }

    _websocketHandleSubscription (contextId, event, msg) {
        let success = this.safeValue (msg, 'success');
        let subscribe = this.safeString (msg, 'subscribe');
        let parts = subscribe.split (':');
        let partsLen = parts.length;
        if (partsLen === 2) {
            if (parts[0] === 'orderBookL2') {
                let symbol = this.findSymbol (parts[1]);
                let symbolData = this._contextGetSymbolData (contextId, event, symbol);
                if ('sub-nonces' in symbolData) {
                    let nonces = symbolData['sub-nonces'];
                    const keys = Object.keys (nonces);
                    for (let i = 0; i < keys.length; i++) {
                        let nonce = keys[i];
                        this._cancelTimeout (nonces[nonce]);
                        this.emit (nonce, success);
                    }
                    symbolData['sub-nonces'] = {};
                    this._contextSetSymbolData (contextId, event, symbol, symbolData);
                }
            }
        }
    }

    _websocketHandleUnsubscription (contextId, event, msg) {
        let success = this.safeValue (msg, 'success');
        let unsubscribe = this.safeString (msg, 'unsubscribe');
        let parts = unsubscribe.split (':');
        let partsLen = parts.length;
        if (partsLen === 2) {
            if (parts[0] === 'orderBookL2') {
                let symbol = this.findSymbol (parts[1]);
                if (success) {
                    let dbids = this._contextGet (contextId, 'dbids');
                    if (symbol in dbids) {
                        this.omit (dbids, symbol);
                        this._contextSet (contextId, 'dbids', dbids);
                    }
                }
                let symbolData = this._contextGetSymbolData (contextId, event, symbol);
                if ('unsub-nonces' in symbolData) {
                    let nonces = symbolData['unsub-nonces'];
                    const keys = Object.keys (nonces);
                    for (let i = 0; i < keys.length; i++) {
                        let nonce = keys[i];
                        this._cancelTimeout (nonces[nonce]);
                        this.emit (nonce, success);
                    }
                    symbolData['unsub-nonces'] = {};
                    this._contextSetSymbolData (contextId, event, symbol, symbolData);
                }
            }
        }
    }

    _websocketHandleOb (contextId, msg) {
        let action = this.safeString (msg, 'action');
        let data = this.safeValue (msg, 'data');
        let symbol = this.safeString (data[0], 'symbol');
        symbol = this.findSymbol (symbol);
        let dbids = this._contextGet (contextId, 'dbids');
        let symbolData = this._contextGetSymbolData (contextId, 'ob', symbol);
        if (action === 'partial') {
            let ob = {
                'bids': [],
                'asks': [],
                'timestamp': undefined,
                'datetime': undefined,
                'nonce': undefined,
            };
            let obIds = {};
            for (let o = 0; o < data.length; o++) {
                let order = data[o];
                let side = (order['side'] === 'Sell') ? 'asks' : 'bids';
                let amount = order['size'];
                let price = order['price'];
                let priceId = order['id'];
                ob[side].push ([ price, amount ]);
                obIds[priceId] = price;
            }
            ob['bids'] = this.sortBy (ob['bids'], 0, true);
            ob['asks'] = this.sortBy (ob['asks'], 0);
            symbolData['ob'] = ob;
            dbids[symbol] = obIds;
            this.emit ('ob', symbol, this._cloneOrderBook (ob, symbolData['limit']));
        } else if (action === 'update') {
            if (symbol in dbids) {
                let obIds = dbids[symbol];
                let curob = symbolData['ob'];
                for (let o = 0; o < data.length; o++) {
                    let order = data[o];
                    let amount = order['size'];
                    let side = (order['side'] === 'Sell') ? 'asks' : 'bids';
                    let priceId = order['id'];
                    let price = obIds[priceId];
                    this.updateBidAsk ([price, amount], curob[side], order['side'] === 'Buy');
                }
                symbolData['ob'] = curob;
                this.emit ('ob', symbol, this._cloneOrderBook (curob, symbolData['limit']));
            }
        } else if (action === 'insert') {
            if (symbol in dbids) {
                let curob = symbolData['ob'];
                for (let o = 0; o < data.length; o++) {
                    let order = data[o];
                    let amount = order['size'];
                    let side = (order['side'] === 'Sell') ? 'asks' : 'bids';
                    let priceId = order['id'];
                    let price = order['price'];
                    this.updateBidAsk ([price, amount], curob[side], order['side'] === 'Buy');
                    dbids[symbol][priceId] = price;
                }
                symbolData['ob'] = curob;
                this.emit ('ob', symbol, this._cloneOrderBook (curob, symbolData['limit']));
            }
        } else if (action === 'delete') {
            if (symbol in dbids) {
                let obIds = dbids[symbol];
                let curob = symbolData['ob'];
                for (let o = 0; o < data.length; o++) {
                    let order = data[o];
                    let side = (order['side'] === 'Sell') ? 'asks' : 'bids';
                    let priceId = order['id'];
                    let price = obIds[priceId];
                    this.updateBidAsk ([price, 0], curob[side], order['side'] === 'Buy');
                    this.omit (dbids[symbol], priceId);
                }
                symbolData['ob'] = curob;
                this.emit ('ob', symbol, this._cloneOrderBook (curob, symbolData['limit']));
            }
        } else {
            this.emit ('err', new ExchangeError (this.id + ' invalid orderbook message'));
        }
        this._contextSet (contextId, 'dbids', dbids);
        this._contextSetSymbolData (contextId, 'ob', symbol, symbolData);
    }

    _websocketSubscribe (contextId, event, symbol, nonce, params = {}) {
        if (event !== 'ob') {
            throw new NotSupported ('subscribe ' + event + '(' + symbol + ') not supported for exchange ' + this.id);
        }
        let id = this.market_id (symbol).toUpperCase ();
        let payload = {
            'op': 'subscribe',
            'args': ['orderBookL2:' + id],
        };
        let symbolData = this._contextGetSymbolData (contextId, event, symbol);
        if (!('sub-nonces' in symbolData)) {
            symbolData['sub-nonces'] = {};
        }
        symbolData['limit'] = this.safeInteger (params, 'limit', undefined);
        let nonceStr = nonce.toString ();
        let handle = this._setTimeout (this.timeout, this._websocketMethodMap ('_websocketTimeoutRemoveNonce'), [contextId, nonceStr, event, symbol, 'sub-nonce']);
        symbolData['sub-nonces'][nonceStr] = handle;
        this._contextSetSymbolData (contextId, event, symbol, symbolData);
        this.websocketSendJson (payload);
    }

    _websocketUnsubscribe (contextId, event, symbol, nonce, params = {}) {
        if (event !== 'ob') {
            throw new NotSupported ('unsubscribe ' + event + '(' + symbol + ') not supported for exchange ' + this.id);
        }
        let id = this.market_id (symbol).toUpperCase ();
        let payload = {
            'op': 'unsubscribe',
            'args': ['orderBookL2:' + id],
        };
        let symbolData = this._contextGetSymbolData (contextId, event, symbol);
        if (!('unsub-nonces' in symbolData)) {
            symbolData['unsub-nonces'] = {};
        }
        let nonceStr = nonce.toString ();
        let handle = this._setTimeout (this.timeout, this._websocketMethodMap ('_websocketTimeoutRemoveNonce'), [contextId, nonceStr, event, symbol, 'unsub-nonces']);
        symbolData['unsub-nonces'][nonceStr] = handle;
        this._contextSetSymbolData (contextId, event, symbol, symbolData);
        this.websocketSendJson (payload);
    }

    _websocketTimeoutRemoveNonce (contextId, timerNonce, event, symbol, key) {
        let symbolData = this._contextGetSymbolData (contextId, event, symbol);
        if (key in symbolData) {
            let nonces = symbolData[key];
            if (timerNonce in nonces) {
                this.omit (symbolData[key], timerNonce);
                this._contextSetSymbolData (contextId, event, symbol, symbolData);
            }
        }
    }

    _getCurrentWebsocketOrderbook (contextId, symbol, limit) {
        let data = this._contextGetSymbolData (contextId, 'ob', symbol);
        if (('ob' in data) && (typeof data['ob'] !== 'undefined')) {
            return this._cloneOrderBook (data['ob'], limit);
        }
        return undefined;
    }
};
