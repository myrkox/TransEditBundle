var urlDetect = 'https://translate.yandex.net/api/v1.5/tr.json/detect?key=trnsl.1.1.20160805T051117Z.6d58f41b9916e816.9e2904b625bf1cafc7af229d3ef1ae3d02fcb65a&text=';
var urlTrans = 'https://translate.yandex.net/api/v1.5/tr.json/translate?key=trnsl.1.1.20160805T051117Z.6d58f41b9916e816.9e2904b625bf1cafc7af229d3ef1ae3d02fcb65a&options=1&text=';

var translationRequest = function(text, lang, url, successCallback, errorCallback){
    $.ajax({
        type: 'GET',
        url: url + text + '&lang=' + lang,
        success: function (response) {
            var sourceLang = '';
            var destinationLang = '';
            var responseLang = response.lang;
            var langArray = responseLang.split('-');
            if(langArray.length == 2){
                sourceLang = getSynonymLocale(langArray.shift());
                destinationLang = getSynonymLocale(langArray.shift());
            }
            successCallback(response.text[0], sourceLang, destinationLang);
        },
        error: function () {
            errorCallback(text, lang);
        }
    })
};

function roundLocales(text, locales, successCallback, errorCallback) {
    var promises = [];
    for (var i = 0; i < locales.length; i++) {
        promises.push(translationRequest(text, getSynonymLocale(locales[i]), urlTrans, successCallback, errorCallback));
    }

    return promises;
}

function singleTranslation(text, locale, successCallback, errorCallback) {
    translationRequest(text, getSynonymLocale(locale), urlTrans, successCallback, errorCallback)
}

function multiTranslation(text, locales, successCallback, errorCallback, callbackAllDone) {
    $.when.apply(null, roundLocales(text, locales, successCallback, errorCallback))
        .done(function () {
            callbackAllDone();
        });
}

function getSynonymLocale(locale) {
    var synonyms = { 'ua' : 'uk' };

    if(synonyms[locale]){
        return synonyms[locale];
    }

    for(var key in synonyms){
        if(synonyms.hasOwnProperty(key) && synonyms[key] == locale) {
            return key;
        }
    }

    return locale;
}

function detectLanguage(text, successCallback, errorCallback){
    if (text != '') {
        $.ajax({
            type: "POST",
            url: urlDetect + testText,
            success: function (response) {
                var detectLang = getDetectLanguage(response);
                successCallback(text, detectLang);
            },
            error: function () {
                errorCallback(text);
            }
        });
    }
}

function getDetectLanguage(obj) {
    lang = obj.lang;

    return lang;
}
