var transKey;
var translationData;
var destinationLocales;
var newKeyUrl;
var saveKeyDataByAjaxUrl;

$(document).ready(function () {
    newKeyUrl = $('#keyValuesGroupFunctionality').attr("newKeyUrl");
    saveKeyDataByAjaxUrl = $('#keyValuesGroupFunctionality').attr("saveKeyDataByAjaxUrl");


    $('.dropdown-toggle').dropdown();

    $('.contextMenuItem').click(function () {
        var mode = $(this).attr('mode');
        var currentLocale = $(this).attr('locale');
        var destination = $(this).attr('destination');
        transKey = $(this).attr('key');
        var text = $('span.value[key="'+transKey+'"][locale="'+currentLocale+'"]').html();

        translationData = {};

        if(mode == 'getTranslation') {
            destinationLocales = [destination];
            singleTranslation(text, destination, successTranslationCallback, errorTranslationCallback);
        }
        if(mode == 'giveTranslations') {
            destinationLocales = destination.split(',');
            multiTranslation(text, destinationLocales, successTranslationCallback, errorTranslationCallback, translationsDone);
        }
    });

    $("button.edit-key").click(function (e) {
        e.preventDefault();

        var key = $(this).attr('key');
        window.location.replace(newKeyUrl + '/' + key);
    });

    $('.dropdown').on('hide.bs.dropdown', function () {
        $(this).find('.badge').removeClass('badge_active').addClass('badge_not_active');
    });
    $('.dropdown').on('show.bs.dropdown', function () {
        $(this).find('.badge').removeClass('badge_not_active').addClass('badge_active');
    });
});

function confirmTranslation() {
    destinationLocales.shift();

    if(destinationLocales.length > 0){
        return;
    }

    if( translationData.hasOwnProperty('key') ){
        var confirmString = '';
        for(var key in translationData) {
            if(key !== 'key' && translationData.hasOwnProperty(key)) {
                confirmString += '\n' + key + ': ' + translationData[key];
            }
        }
        if(confirmString != ''){
            confirmString = 'Set for key "' + translationData['key'] + '" and locales:\n' + confirmString + '\n';
            if (confirm(confirmString)) {
                sendNewKeyData()
            }
        }
    }
}

var successTranslationCallback = function (text, sourceLocale, destinationLocale) {
    translationData['key'] = transKey;
    translationData[destinationLocale] = text;

    confirmTranslation();
};

var errorTranslationCallback = function (text, lang) {
    console.log('Error translation:');
    console.log('Key: ' + transKey);
    console.log('Destination locale: ' + lang);
    console.log('Text: ' + text);

    confirmTranslation();
};

var translationsDone = function () {
    console.log("All translations are finished!");
};

function sendNewKeyData() {
    $.ajax({
        url: saveKeyDataByAjaxUrl,
        cache: false,
        type: "POST",
        data: 'data=' + JSON.stringify(translationData),
        success: function (result) {
            if (result["status"] === true) {
                showNewKeyData();
            } else {
                console.log("An error occurred during saving data.");
            }
        },
        error: function() {
            console.log("An error occurred while processing ajax request.");
        }
    });
}

function showNewKeyData() {
    if( translationData.hasOwnProperty('key') ){
        var transKey = translationData['key'];
        for(var key in translationData) {
            if(key !== 'key' && translationData.hasOwnProperty(key)) {
                $('span.value[key="'+transKey+'"][locale="' + key + '"]').html(translationData[key]);
            }
        }
    }
}
