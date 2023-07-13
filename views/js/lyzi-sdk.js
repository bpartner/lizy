const env = 'development'; //development,staging,production

const localUrl = "http://127.0.0.1:4200";
const devUrl = "https://admin-dev.lyzi.fr";
const stagingUrl = "https://admin-dev.lyzi.fr";
const prodUrl = "https://admin.lyzi.fr";

const localApiUrl = "http://127.0.0.1:8000/api";
const devApiUrl = "https://api-dev.lyzi.fr/api";
const prodApiUrl = "https://api.lyzi.fr/api";

const eventMethod = window.addEventListener ? "addEventListener" : "attachEvent";
const eventer = window[eventMethod];
const messageEvent = "attachEvent" == eventMethod ? "onmessage" : "message";

var getUrl = (env) => {
    switch (env) {
        case 'development':
            return { base: devUrl, api: devApiUrl };
        case 'staging':
            return { base: stagingUrl, api: devApiUrl };
        case 'production':
            return { base: prodUrl, api: prodApiUrl };
        default:
            return { base: localUrl, api: localApiUrl };
    }
}

function encodeQS(object) {
    function reducer(obj, parentPrefix = null) {
        return function (prev, key) {
            const val = obj[key];
            key = encodeURIComponent(key);
            const prefix = parentPrefix ? `${parentPrefix}[${key}]` : key;

            if (val == null || typeof val === 'function') {
                prev.push(`${prefix}=`);
                return prev;
            }

            if (typeof val === 'boolean') {
                prev.push(`${prefix}=${val.toString().toUpperCase()}`);
                return prev;
            }

            if (['number', 'string'].includes(typeof val)) {
                prev.push(`${prefix}=${encodeURIComponent(val)}`);
                return prev;
            }

            prev.push(
                Object.keys(val).reduce(reducer(val, prefix), []).join('&')
            );
            return prev;
        };
    }
    return Object.keys(object).reduce(reducer(object), []).join('&');
}

function decodeQS(querystring) {
    function parseValue(value) {
        if (value === 'TRUE') return true;
        if (value === 'FALSE') return false;
        // return isNaN(Number(value)) ? value : Number(value);
        return value;
    }

    function dec(list, isArray = false) {
        let obj = isArray ? [] : {};

        let recs = list.filter((item) => {
            if (item.keys.length > 1) return true;
            obj[item.keys[0]] = parseValue(item.value);
        });

        let attrs = {};
        recs.map((item) => {
            item.key = item.keys.shift();
            attrs[item.key] = [];
            return item;
        }).forEach((item) => attrs[item.key].push(item));

        Object.keys(attrs).forEach((attr) => {
            let nextKey = attrs[attr][0].keys[0];
            obj[attr] = dec(attrs[attr], typeof nextKey === 'number');
        });

        return obj;
    }

    return dec(
        querystring
            .split('&')
            .map((item) => item.split('=').map((x) => decodeURIComponent(x)))
            .map((item) => {
                return {
                    keys: item[0]
                        .split(/[\[\]]/g)
                        .filter((n) => n)
                        .map((key) => (isNaN(Number(key)) ? key : Number(key))),
                    value: item[1],
                };
            })
    );
}

function buildUrl(url, parameters) {
    function cleanUpParams(obj) {
        for (var propName in obj) {
            if (obj[propName] === null || obj[propName] === undefined) {
                delete obj[propName];
            }

            if (typeof obj[propName] === 'object') {
                for (var nPropName in obj[propName]) {
                    if (obj[propName][nPropName] === null || obj[propName][nPropName] === undefined) {
                        delete obj[propName][nPropName];
                    }
                }
            }
        }
        return obj
    }

    function cleanUpUrl(urlToClean) {
        let cleanedUrl, extractedParams = {};

        var hasQs = urlToClean.split('?');
        if (hasQs.length > 1) {
            cleanedUrl = hasQs[0];
            hasQs[1].split('&').forEach(params => {
                var param = params.split('=');
                extractedParams[param[0]] = param[1]
            });
        }

        return {
            cleanedUrl, extractedParams
        }
    }

    var { cleanedUrl, extractedParams } = cleanUpUrl(url);
    if (cleanedUrl) {
        url = cleanedUrl;
    }
    if (extractedParams) {
        parameters = {
            ...parameters,
            ...extractedParams
        }
    }

    var qs = encodeQS(cleanUpParams(parameters));
    if (qs.length > 0) {
        // qs = qs.substring(0, qs.length - 1); //chop off last "&"
        url = url + "?" + qs;
    }
    return url;
}

const checkUrlValidity = (url) => {
    const reg = new RegExp(/^http(s?):\/\/((\w+.)?\w+.\w+\w.+|((2[0-5]{2}|1[0-9]{2}|[0-9]{1,2}).){3}(2[0-5]{3}|1[0-9]{3}|[0-9]{1,2})(:\d{1,5})?)(((\/)(.+)?)?)$/gm);
    return reg.test(url);
}

const messageHandler = (type, message) => {
    const logType = console[type];
    return logType(message);
}

(function (window) {
    // declare
    let baseUrl, apiUrl, lyziToken, conversionCode, sessionId;
    let initConfig = {
        env: env,
        buttonId: undefined,
        orderRef: null,
        price: null,
        currency: 'EUR',
        buttonAppUrl: undefined,
        callbackUrl: null,
        buttonName: null,
        goods: {}
    }

    let snippetConfig = {
        type: 'default',
        display: 'popup',
    }

    let subscribeTransactionConfig = {
        code: undefined,
        codeType: "transactionCode",
        interval: 5000,
        callbackUrl: initConfig.callbackUrl,
        success: () => { },
        error: () => { }
    }

    const lyziBuyButton = function () {
        const { base, api } = getUrl(env);
        baseUrl = base;
        apiUrl = api;

        eventer(messageEvent, function (e) {
            let t = e.data;
            if (Object(t).hasOwnProperty('lyziToken')) lyziToken = t.lyziToken ?? undefined;
            if (Object(t).hasOwnProperty('sessionId')) sessionId = t.sessionId ?? undefined;
            if (Object(t).hasOwnProperty('conversionCode')) {
                conversionCode = t.conversionCode ?? undefined;
                if (conversionCode) {
                    messageHandler('info', 'conversion code generated');
                }
            }
        })
        return
    };

    var initSDK = (options = {}, elementSelector = undefined) => {
        if (!options.buttonId || !options) throw new Error('buttonId is required');
        if (options.goods){
            if(!options.goods.goodsName) throw new Error('goodsName is required')
            if(!options.goods.goodsType) throw new Error('goodsType is required')
            if(!options.goods.goodsCategory) throw new Error('goodsCategory is required')
        };

        const { base, api } = getUrl(options.env ?? initConfig.env);
        baseUrl = base;
        apiUrl = api;

        let defaultOptions = {
            buttonAppUrl: `${baseUrl}/buy-button/landing`,
            orderRef: '',
        }

        initConfig = {
            ...initConfig,
            ...defaultOptions,
            ...options
        }

        messageHandler('log', 'init buy button sdk');

        if (initConfig.buttonAppUrl) {
            var isValidUrl = checkUrlValidity(initConfig.buttonAppUrl);
            if (!isValidUrl) {
                console.log('buttonAppUrl', initConfig.buttonAppUrl)
                throw new Error('please re-check your buttonAppUrl and make sure it\'s a valid url')
            }
        }

        if (initConfig.callbackUrl) {
            var isValidUrl = checkUrlValidity(initConfig.callbackUrl);
            if (!isValidUrl) {
                console.log('callbackUrl', initConfig.callbackUrl)
                throw new Error('please re-check your callbackUrl and make sure it\'s a valid url')
            }
        }

        if (elementSelector) {
            var { modalSnippet, modalJsSnippet, buttonSnippet, cssHrefSnippet } = generateSnippet();

            var snip = cssHrefSnippet;
            snip += buttonSnippet;
            snip += modalJsSnippet;
            var html = document.createRange().createContextualFragment(snip);
            if (elementSelector.tagName == "BUTTON" || elementSelector.tagName == "A") {
                var newTag = document.createRange().createContextualFragment(`<div id="${elementSelector.id}"></div>`);
                newTag.append(html);
                elementSelector.replaceWith(newTag);
            } else {
                elementSelector.append(html);
            }
        }

        if (initConfig.autoSubscribeTransaction) {
            subscribeTransaction({
                code: conversionCode,
                callbackUrl: initConfig.callbackUrl,
                success: (res) => {
                    console.log('subscribe transaction response', res)
                },
                error: (error) => {
                    console.error('subscribe transaction response', error)
                }
            })
        }
    }

    var removeSession = () => {
        if (lyziToken && sessionId) {
            var xhr = new XMLHttpRequest();
            var url = apiUrl + "/buy-button/sessions/" + sessionId
            xhr.open("DELETE", url, false);
            xhr.setRequestHeader("Authorization", "Bearer " + lyziToken);
            xhr.send();
        }
    }

    var getButtonUrl = () => {
        var params = {
            id: initConfig.buttonId,
            orderRef: initConfig.orderRef,
            price: initConfig.price,
            currency: initConfig.currency,
            callbackUrl: initConfig.callbackUrl,
            goods: initConfig.goods,
            buttonName: initConfig.buttonName
        }

        return buildUrl(initConfig.buttonAppUrl, params);
    }

    var generateSnippet = (options = {}) => {
        let modalSnippet, modalJsSnippet, buttonSnippet, cssHrefSnippet;

        snippetConfig = {
            ...snippetConfig,
            ...options
        }

        try {
            if (!initConfig.buttonId) throw new Error('undefined buttonId');
            if (!initConfig.buttonAppUrl) throw new Error('undefined buttonAppUrl');
            if (!snippetConfig.type) throw new Error('undefined type');
            if (!snippetConfig.display) throw new Error('undefined display');

            const buttonUrl = getButtonUrl();

            modalSnippet = `<div class=lyzi-modal> <div class=lyzi-modal-content><span class=lyzi-modal-close-btn>&times;</span><iframe id=app-frame></iframe></div></div>`;
            // modalJsSnippet = `<script src="${baseUrl}/assets/buy-button/modal.js" defer></script>`;
            modalJsSnippet = '<script type="text/javascript">if(lyziBuyButton){lyziBuyButton.handleModal();}else{console.error("lyzi modal not initialized")}</script>';
            buttonSnippet = ` <div class=lyzi> <form id=lyzi-form action="${buttonUrl}"> <img class=cryptos-icon src=${baseUrl}/assets/buy-button/Coins.svg alt=cryptos> <button class=pay-button> Payer en crypto </button> <div class=powered>Powered By <img class=lyzi-logo src=${baseUrl}/assets/buy-button/lyzi-logo.png alt="lyzi logo"> </div></form> </div>`;
            cssHrefSnippet = `<link href="${baseUrl}/assets/buy-button/buy-button.css" rel="stylesheet">`;

            return {
                modalSnippet, modalJsSnippet, buttonSnippet, cssHrefSnippet
            }

        } catch (error) {
            messageHandler('error', error);
        }
    }

    var getTransactionCode = () => {
        if (!conversionCode) return messageHandler('error', 'transaction code not generated yet');

        return conversionCode
    }

    var subscribeTransaction = (options = {}) => {
        if (!initConfig.autoSubscribeTransaction && (!options.code || !options)) throw new Error('code is required');

        subscribeTransactionConfig = {
            ...subscribeTransactionConfig,
            ...options
        }

        const getStatus = () => {
            var url = `${apiUrl}/confirm_conversion/status/${subscribeTransactionConfig.code}`;

            if (subscribeTransactionConfig.codeType == 'orderRef') {
                url = `${apiUrl}/confirm_conversion/status/order/${subscribeTransactionConfig.code}`;
            }

            var xhr = new XMLHttpRequest();
            xhr.open("GET", url);

            xhr.setRequestHeader("Authorization", "Bearer " + lyziToken);

            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4) {
                    const jsonResponse = JSON.parse(xhr.responseText);
                    const { success, data, error } = jsonResponse;

                    if (success == false || error) {
                        // clearInterval(doCheck);
                        return subscribeTransactionConfig.error({ ...jsonResponse, success: false });
                    }

                    if (data) {
                        messageHandler('info', `transaction state: ${data.state}`)
                    }

                    if (data && data.state.includes('state:expired')) {
                        // clearInterval(doCheck);
                        return subscribeTransactionConfig.error({ ...jsonResponse, success: false });
                    }

                    if (data && data.state.includes('state:executed')) {
                        clearInterval(doCheck);
                        return subscribeTransactionConfig.success(jsonResponse)
                    }

                    return subscribeTransactionConfig.success(jsonResponse)
                }
            };

            xhr.send();
        }

        const doCheck = setInterval(() => {
            try {
                if (!lyziToken || !conversionCode) {
                    return messageHandler('info', 'awaiting transaction...');
                }

                getStatus()
            } catch (error) {
                messageHandler('error', error)
            }
        }, subscribeTransactionConfig.interval);
        messageHandler('info', 'transaction subscribed')
    }


    var handleLyziModal = () => {
        //declare modal
        const lyziLoadingImg = "data:image/svg+xml;base64,PHN2ZyBjbGFzcz0ic3ZnLWxvYWRlciIgdmVyc2lvbj0iMS4xIiBpZD0iTDQiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIKICAgIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIiB4PSIwcHgiIHk9IjBweCIgZW5hYmxlLWJhY2tncm91bmQ9Im5ldyAwIDAgMCAwIiB4bWw6c3BhY2U9InByZXNlcnZlIgogICAgdmlld0JveD0iMCA0NCA1MiAxMiI+CiAgICA8Y2lyY2xlIGZpbGw9IiMxZTg2ZWMiIHN0cm9rZT0ibm9uZSIgY3g9IjYiIGN5PSI1MCIgcj0iNiI+CiAgICAgIDxhbmltYXRlIGF0dHJpYnV0ZU5hbWU9Im9wYWNpdHkiIGR1cj0iMXMiIHZhbHVlcz0iMDsxOzAiIHJlcGVhdENvdW50PSJpbmRlZmluaXRlIiBiZWdpbj0iMC4xIj48L2FuaW1hdGU+CiAgICA8L2NpcmNsZT4KICAgIDxjaXJjbGUgZmlsbD0iIzFlODZlYyIgc3Ryb2tlPSJub25lIiBjeD0iMjYiIGN5PSI1MCIgcj0iNiI+CiAgICAgIDxhbmltYXRlIGF0dHJpYnV0ZU5hbWU9Im9wYWNpdHkiIGR1cj0iMXMiIHZhbHVlcz0iMDsxOzAiIHJlcGVhdENvdW50PSJpbmRlZmluaXRlIiBiZWdpbj0iMC4yIj48L2FuaW1hdGU+CiAgICA8L2NpcmNsZT4KICAgIDxjaXJjbGUgZmlsbD0iIzFlODZlYyIgc3Ryb2tlPSJub25lIiBjeD0iNDYiIGN5PSI1MCIgcj0iNiI+CiAgICAgIDxhbmltYXRlIGF0dHJpYnV0ZU5hbWU9Im9wYWNpdHkiIGR1cj0iMXMiIHZhbHVlcz0iMDsxOzAiIHJlcGVhdENvdW50PSJpbmRlZmluaXRlIiBiZWdpbj0iMC4zIj48L2FuaW1hdGU+CiAgICA8L2NpcmNsZT4KICA8L3N2Zz4=";

        const body = document.querySelector("body");
        body.innerHTML += '<div class=lyzi-modal> <div class=lyzi-modal-content><span class=lyzi-modal-close-btn>&times;</span><img id="lyzi-img-loading" src="' + lyziLoadingImg + '"/><iframe id="app-frame"></iframe></div></div>';
        let lyziModal = document.querySelector(".lyzi-modal"),
            lyziModalCloseBtn = document.querySelector(".lyzi-modal-close-btn"),
            lyziModalContent = document.querySelector(".lyzi-modal-content"),
            lyziAppFrame = document.getElementById("app-frame"),
            lyziForm = document.getElementById("lyzi-form"),
            lyziLoader = document.getElementById("lyzi-img-loading");

        eventer(
            messageEvent,
            function (e) {
                let t = e.data;
                if (Object(t).hasOwnProperty('height') && Object(t).hasOwnProperty('width')) {
                    (lyziAppFrame.style.height = t.height + "px"), (lyziAppFrame.style.width = t.width + "px"), (lyziLoader.style.display = "none"), (lyziAppFrame.style.display = "block");
                }
                if (Object(t).hasOwnProperty('closeModal') == true) {
                    (lyziModal.style.display = "none");
                }
            },
            !1
        );
        lyziAppFrame.onload = function (e) {
            // lyziAppFrame.style.display = "none";
            lyziLoader.style.width = "200px"
            lyziLoader.style.margin = "0 auto"
        };
        lyziForm.onsubmit = function (e) {
            e.preventDefault(), (lyziAppFrame.src = lyziForm.action), (lyziModal.style.display = "block"), (lyziAppFrame.style.display = "none"), (lyziLoader.style.display = "block");
        };
        lyziModalCloseBtn.onclick = function () {
            lyziModal.style.display = "none";
        };
        window.onclick = function (e) {
            e.target == lyziModal && (lyziModal.style.display = "none");
        };
    }

    // sdk prototypes
    lyziBuyButton.prototype.init = initSDK
    lyziBuyButton.prototype.getButtonUrl = getButtonUrl
    lyziBuyButton.prototype.generateSnippet = generateSnippet
    lyziBuyButton.prototype.subscribeTransaction = subscribeTransaction
    lyziBuyButton.prototype.getTransactionCode = getTransactionCode
    lyziBuyButton.prototype.removeSession = removeSession
    lyziBuyButton.prototype.handleModal = handleLyziModal
    lyziBuyButton.prototype.encodeQS = encodeQS
    lyziBuyButton.prototype.decodeQS = decodeQS
    lyziBuyButton.prototype.buildUrl = buildUrl

    // define namespace
    window.lyziBuyButton = new lyziBuyButton();

})(window, undefined);